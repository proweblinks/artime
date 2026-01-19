# Multitalk Shot-Level Integration Plan

## Executive Summary

Enable shot-level lip-sync animation using Multitalk by adding voiceover generation at the shot level. The infrastructure is 80% complete - we need to bridge the gap between shot decomposition (which already detects `needsLipSync`) and the animation service (which already supports Multitalk).

---

## Current State Analysis

### What's Already Working

| Component | File | Status | Description |
|-----------|------|--------|-------------|
| Shot Intelligence | `ShotIntelligenceService.php:679-680` | ✅ Ready | Detects `needsLipSync`, sets `recommendedModel: 'multitalk'` |
| Video Prompt Builder | `VideoPromptBuilderService.php:260-291` | ✅ Ready | Adds lip-sync guidance to prompts |
| Animation Service | `AnimationService.php:generateWithMultitalk()` | ✅ Ready | Full Multitalk provider with RunPod |
| RunPod Handler | `rps-multitalk/handler.py` | ✅ Ready | ComfyUI workflow execution |
| Video Model Selector UI | `multi-shot.blade.php:608-655` | ✅ Ready | MiniMax/Multitalk selection popup |
| Voiceover Service | `VoiceoverService.php` | ✅ Ready | OpenAI TTS (scene-level only) |
| Settings | `VwSettingSeeder.php` | ✅ Ready | All Multitalk settings configured |

### The Gap (What's Missing)

```
Shot Decomposition → [MISSING: Shot Audio] → Animation Service
     ↓                                              ↓
needsLipSync: true                           requiresAudio: true
recommendedModel: 'multitalk'                $audioUrl = null ❌
```

**Problem:** When user clicks "Animate" → selects "Multitalk", the animation fails because:

```php
// VideoWizard.php:20194-20204
$audioUrl = null;
if ($selectedModel === 'multitalk') {
    $audioUrl = $shot['audioUrl'] ?? $shot['voiceoverUrl'] ?? null;
    // ↑ BOTH ARE NULL - shots don't have audio attached!
}
```

---

## Integration Plan

### Phase 1: Shot Data Structure Enhancement

**File:** `VideoWizard.php`

Add to shot data structure during decomposition:

```php
// Enhanced shot structure
$shot = [
    // Existing fields
    'type' => 'close-up',
    'duration' => 10,
    'needsLipSync' => true,
    'recommendedModel' => 'multitalk',
    'imageUrl' => '...',
    'videoUrl' => null,

    // NEW: Audio fields for Multitalk
    'monologue' => null,           // Text content for voiceover
    'audioUrl' => null,            // URL of generated voiceover
    'audioDuration' => null,       // Duration of audio in seconds
    'voiceId' => 'nova',           // Selected voice for this shot
    'audioStatus' => 'pending',    // pending | generating | ready | error
];
```

---

### Phase 2: Monologue Text Extraction

**New Method:** `VideoWizard::extractShotMonologue()`

Extract or generate inner thoughts/dialogue for close-up shots from scene narration.

```php
/**
 * Extract or generate monologue text for a shot.
 *
 * @param int $sceneIndex
 * @param int $shotIndex
 * @return array ['text' => string, 'source' => 'extracted'|'generated']
 */
public function extractShotMonologue(int $sceneIndex, int $shotIndex): array
{
    $scene = $this->script['scenes'][$sceneIndex] ?? [];
    $shot = $this->storyboard['scenes'][$sceneIndex]['decomposition']['shots'][$shotIndex] ?? [];

    // Get character in this shot
    $characters = $scene['characters'] ?? [];
    $mainCharacter = $characters[0] ?? 'the character';

    // Get shot's narrative beat/action
    $narrativeBeat = $shot['narrativeBeat'] ?? [];
    $action = $narrativeBeat['action'] ?? $shot['subjectAction'] ?? '';

    // Get scene narration
    $narration = $scene['narration'] ?? '';

    // Option 1: Extract dialogue from quotes in narration
    if (preg_match_all('/"([^"]+)"/', $narration, $matches)) {
        // Found quoted text - use as dialogue
        $dialogue = $matches[1][0] ?? '';
        if (strlen($dialogue) > 10) {
            return ['text' => $dialogue, 'source' => 'extracted'];
        }
    }

    // Option 2: Generate inner monologue using AI
    $prompt = $this->buildMonologuePrompt($scene, $shot, $mainCharacter);
    $result = $this->generateAIContent($prompt, 'monologue');

    return [
        'text' => $result['text'] ?? '',
        'source' => 'generated',
        'characterName' => $mainCharacter,
    ];
}

/**
 * Build AI prompt for generating shot monologue.
 */
protected function buildMonologuePrompt(array $scene, array $shot, string $character): string
{
    $narration = $scene['narration'] ?? '';
    $action = $shot['subjectAction'] ?? $shot['action'] ?? '';
    $shotType = $shot['type'] ?? 'close-up';
    $duration = $shot['duration'] ?? 10;

    // Calculate word count for duration (150 wpm speaking rate)
    $wordCount = floor(($duration * 150) / 60);

    return <<<PROMPT
Generate inner monologue/thoughts for a character in a film scene.

CHARACTER: {$character}
SCENE CONTEXT: {$narration}
SHOT TYPE: {$shotType}
SHOT ACTION: {$action}
DURATION: {$duration} seconds

Requirements:
1. Write EXACTLY {$wordCount} words (for {$duration}s at 150 wpm)
2. Inner thoughts reflecting character's mental state during this action
3. First-person perspective
4. Match the emotional tone of the scene
5. Keep it natural - how someone would actually think
6. NO stage directions, just the thoughts

Return ONLY the monologue text, nothing else.
PROMPT;
}
```

---

### Phase 3: Shot-Level Voiceover Generation

**New Method:** `VideoWizard::generateShotVoiceover()`

```php
/**
 * Generate voiceover audio for a specific shot.
 */
public function generateShotVoiceover(int $sceneIndex, int $shotIndex, array $options = []): void
{
    $shot = &$this->storyboard['scenes'][$sceneIndex]['decomposition']['shots'][$shotIndex];

    // Update status
    $shot['audioStatus'] = 'generating';
    $this->dispatch('shot-audio-generating', [
        'sceneIndex' => $sceneIndex,
        'shotIndex' => $shotIndex,
    ]);

    try {
        // Get or generate monologue text
        $text = $shot['monologue'] ?? null;
        if (empty($text)) {
            $monologueResult = $this->extractShotMonologue($sceneIndex, $shotIndex);
            $text = $monologueResult['text'];
            $shot['monologue'] = $text;
        }

        if (empty($text)) {
            throw new \Exception('No monologue text available for voiceover');
        }

        // Get voice selection
        $voiceId = $options['voice'] ?? $shot['voiceId'] ?? $this->getCharacterVoice($sceneIndex, $shotIndex);

        // Generate audio using VoiceoverService
        $project = WizardProject::findOrFail($this->projectId);
        $voiceoverService = app(VoiceoverService::class);

        $result = $voiceoverService->generateSceneVoiceover($project, [
            'id' => "shot_{$sceneIndex}_{$shotIndex}",
            'narration' => $text,
            'title' => "Shot {$shotIndex + 1} Voiceover",
        ], [
            'voice' => $voiceId,
            'speed' => $options['speed'] ?? 1.0,
            'sceneIndex' => $sceneIndex,
            'teamId' => session('current_team_id', 0),
        ]);

        // Update shot with audio data
        $shot['audioUrl'] = $result['audioUrl'];
        $shot['audioDuration'] = $result['duration'];
        $shot['voiceId'] = $voiceId;
        $shot['audioStatus'] = 'ready';

        $this->saveProject();

        $this->dispatch('shot-audio-ready', [
            'sceneIndex' => $sceneIndex,
            'shotIndex' => $shotIndex,
            'audioUrl' => $result['audioUrl'],
        ]);

    } catch (\Exception $e) {
        Log::error('Shot voiceover generation failed', [
            'sceneIndex' => $sceneIndex,
            'shotIndex' => $shotIndex,
            'error' => $e->getMessage(),
        ]);

        $shot['audioStatus'] = 'error';
        $shot['audioError'] = $e->getMessage();

        $this->dispatch('shot-audio-error', [
            'sceneIndex' => $sceneIndex,
            'shotIndex' => $shotIndex,
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Get character voice for a shot.
 */
protected function getCharacterVoice(int $sceneIndex, int $shotIndex): string
{
    // Check if character has assigned voice in Character Bible
    $scene = $this->script['scenes'][$sceneIndex] ?? [];
    $characters = $scene['characters'] ?? [];

    if (!empty($characters[0])) {
        $charName = $characters[0];
        $charBible = $this->sceneMemory['characterBible']['characters'] ?? [];

        foreach ($charBible as $char) {
            if (($char['name'] ?? '') === $charName && !empty($char['voice'])) {
                return $char['voice'];
            }
        }
    }

    // Default voice based on character gender or setting
    return $this->storyboard['defaultVoice'] ?? 'nova';
}
```

---

### Phase 4: Enhanced Video Model Selector UI

**File:** `multi-shot.blade.php`

Modify the video model selector to handle Multitalk audio requirements:

```blade
{{-- Video Model Selector with Multitalk Audio Support --}}
@if($showVideoModelSelector ?? false)
<div class="msm-popup-overlay" wire:click.self="closeVideoModelSelector">
    <div class="msm-model-selector">
        <h3 class="msm-model-title">Select Animation Model</h3>

        {{-- MiniMax Option --}}
        <div class="msm-model-opt" wire:click="setVideoModel('minimax')">
            <div class="msm-model-header">
                <strong>MiniMax</strong>
                <span class="msm-model-badge">Standard I2V</span>
            </div>
            <span class="msm-model-desc">High quality image-to-video without audio</span>
        </div>

        {{-- Multitalk Option --}}
        @php
            $selectedShot = $storyboard['scenes'][$videoModelSelectorSceneIndex]['decomposition']['shots'][$videoModelSelectorShotIndex] ?? null;
            $hasAudio = !empty($selectedShot['audioUrl']) && $selectedShot['audioStatus'] === 'ready';
            $mtAvail = !empty(VwSetting::getValue('api_runpod_multitalk_endpoint'));
        @endphp

        <div class="msm-model-opt msm-model-multitalk {{ !$mtAvail ? 'disabled' : '' }}">
            <div class="msm-model-header">
                <strong>Multitalk</strong>
                <span class="msm-model-badge msm-badge-lip">Lip-Sync</span>
            </div>

            @if(!$mtAvail)
                <span class="msm-model-desc msm-desc-disabled">Multitalk endpoint not configured</span>
            @elseif($hasAudio)
                {{-- Audio ready - can animate directly --}}
                <span class="msm-model-desc msm-desc-ready">
                    ✓ Audio ready ({{ $selectedShot['voiceId'] ?? 'unknown' }} voice)
                </span>
                <button wire:click="setVideoModel('multitalk')"
                        class="msm-btn msm-btn-primary mt-2">
                    Animate with Lip-Sync
                </button>
            @else
                {{-- No audio - need to generate first --}}
                <span class="msm-model-desc">Lip-sync requires voiceover audio</span>

                {{-- Voice Selection --}}
                <div class="msm-voice-select mt-3">
                    <label class="msm-voice-label">Select Voice:</label>
                    <select wire:model="shotVoiceSelection" class="msm-voice-dropdown">
                        <option value="alloy">Alloy (Neutral)</option>
                        <option value="echo">Echo (Male)</option>
                        <option value="fable">Fable (Storytelling)</option>
                        <option value="onyx">Onyx (Deep Male)</option>
                        <option value="nova" selected>Nova (Female)</option>
                        <option value="shimmer">Shimmer (Bright Female)</option>
                    </select>
                </div>

                {{-- Monologue Preview/Edit --}}
                @if(!empty($selectedShot['monologue']))
                <div class="msm-monologue-preview mt-2">
                    <label class="msm-voice-label">Dialogue:</label>
                    <textarea wire:model.lazy="shotMonologueEdit"
                              class="msm-monologue-textarea"
                              rows="3">{{ $selectedShot['monologue'] }}</textarea>
                </div>
                @endif

                {{-- Generate Voice Button --}}
                <button wire:click="generateShotVoiceover({{ $videoModelSelectorSceneIndex }}, {{ $videoModelSelectorShotIndex }}, { voice: $shotVoiceSelection })"
                        wire:loading.attr="disabled"
                        class="msm-btn msm-btn-secondary mt-2">
                    <span wire:loading.remove wire:target="generateShotVoiceover">
                        🎤 Generate Voice First
                    </span>
                    <span wire:loading wire:target="generateShotVoiceover">
                        ⏳ Generating...
                    </span>
                </button>
            @endif
        </div>

        {{-- Cancel Button --}}
        <button wire:click="closeVideoModelSelector" class="msm-btn msm-btn-cancel mt-3">
            Cancel
        </button>
    </div>
</div>

<style>
.msm-model-multitalk {
    border: 1px solid rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.05);
}
.msm-badge-lip {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
}
.msm-desc-ready {
    color: #10b981;
}
.msm-voice-select {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.msm-voice-label {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.6);
}
.msm-voice-dropdown {
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 0.375rem;
    padding: 0.5rem;
    color: white;
    font-size: 0.875rem;
}
.msm-monologue-textarea {
    width: 100%;
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 0.375rem;
    padding: 0.5rem;
    color: white;
    font-size: 0.8rem;
    resize: vertical;
}
.msm-btn {
    width: 100%;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.msm-btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}
.msm-btn-secondary {
    background: rgba(139, 92, 246, 0.2);
    border: 1px solid rgba(139, 92, 246, 0.5);
    color: white;
}
.msm-btn-cancel {
    background: rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.7);
}
</style>
@endif
```

---

### Phase 5: Update Animation Flow

**File:** `VideoWizard.php` - Modify `generateShotVideo()`

```php
/**
 * Generate video for a shot (existing method - MODIFIED).
 */
public function generateShotVideo(int $sceneIndex, int $shotIndex, ?int $pageIndex = null, ?int $regionIndex = null): void
{
    // ... existing code ...

    $selectedModel = $this->selectedVideoModel ?? 'minimax';

    // Get audio URL for Multitalk lip-sync
    $audioUrl = null;
    if ($selectedModel === 'multitalk') {
        $audioUrl = $shot['audioUrl'] ?? $shot['voiceoverUrl'] ?? null;

        // NEW: If Multitalk selected but no audio, show error
        if (empty($audioUrl)) {
            $this->dispatch('generation-error', [
                'message' => __('Multitalk requires audio. Please generate voiceover first.'),
                'type' => 'multitalk_no_audio',
                'sceneIndex' => $sceneIndex,
                'shotIndex' => $shotIndex,
            ]);
            return;
        }
    }

    // Build motion prompt optimized for selected model
    $motionPrompt = $this->buildShotMotionPrompt($shot, $scene, $selectedModel);

    // ... rest of existing code ...
}

/**
 * Build motion prompt optimized for the animation model.
 */
protected function buildShotMotionPrompt(array $shot, array $scene, string $model): string
{
    $basePrompt = $shot['videoPrompt'] ?? $shot['action'] ?? '';

    if ($model === 'multitalk') {
        // Multitalk-specific prompt optimization
        // Focus on lip movement, facial expressions, subtle body language
        return $this->buildMultitalkPrompt($shot, $scene);
    }

    // MiniMax/standard prompt
    return $basePrompt;
}

/**
 * Build prompt optimized for Multitalk lip-sync.
 */
protected function buildMultitalkPrompt(array $shot, array $scene): string
{
    $character = $scene['characters'][0] ?? 'the subject';
    $emotion = $shot['emotion'] ?? $scene['mood'] ?? 'neutral';
    $action = $shot['subjectAction'] ?? $shot['action'] ?? 'speaking';

    // Multitalk works best with minimal prompt - focus on expression
    $prompt = "{$character} speaking with natural lip movement, ";
    $prompt .= "{$emotion} expression, ";
    $prompt .= "subtle head movement, ";
    $prompt .= "eyes alive with thought, ";
    $prompt .= "realistic facial micro-expressions";

    return $prompt;
}
```

---

### Phase 6: Auto-Generate Monologue on Decomposition

**File:** `VideoWizard.php` - Modify collage decomposition

Add automatic monologue extraction for shots marked with `needsLipSync`:

```php
/**
 * During decomposition, pre-generate monologue for lip-sync shots.
 */
protected function enrichShotsWithMonologue(array &$shots, int $sceneIndex): void
{
    foreach ($shots as $index => &$shot) {
        if (!empty($shot['needsLipSync']) && empty($shot['monologue'])) {
            // Extract/generate monologue for this shot
            try {
                $result = $this->extractShotMonologue($sceneIndex, $index);
                $shot['monologue'] = $result['text'];
                $shot['monologueSource'] = $result['source'];
                $shot['audioStatus'] = 'pending';
            } catch (\Exception $e) {
                Log::warning('Failed to extract monologue for shot', [
                    'sceneIndex' => $sceneIndex,
                    'shotIndex' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

---

## File Changes Summary

| File | Changes |
|------|---------|
| `VideoWizard.php` | Add `extractShotMonologue()`, `generateShotVoiceover()`, `getCharacterVoice()`, `buildMultitalkPrompt()`, modify `generateShotVideo()` |
| `multi-shot.blade.php` | Enhance video model selector with voice selection and audio generation UI |
| `VoiceoverService.php` | No changes needed - already supports required functionality |
| `AnimationService.php` | No changes needed - already has Multitalk provider |

---

## Implementation Order

1. **Phase 1:** Add shot data structure fields (30 min)
2. **Phase 2:** Implement `extractShotMonologue()` (1 hour)
3. **Phase 3:** Implement `generateShotVoiceover()` (1 hour)
4. **Phase 4:** Update video model selector UI (1 hour)
5. **Phase 5:** Update `generateShotVideo()` flow (30 min)
6. **Phase 6:** Auto-enrich shots during decomposition (30 min)

**Total Estimated Work:** ~5 hours

---

## Testing Checklist

### Unit Tests
- [ ] `extractShotMonologue()` extracts quoted dialogue
- [ ] `extractShotMonologue()` generates AI monologue when no quotes
- [ ] `generateShotVoiceover()` creates audio file
- [ ] Audio URL is properly stored in shot data

### Integration Tests
- [ ] Video Model Selector shows voice options for Multitalk
- [ ] "Generate Voice First" button triggers voiceover generation
- [ ] After voiceover ready, "Animate with Lip-Sync" becomes available
- [ ] Animation uses correct audio URL

### End-to-End Tests
1. Create project with Character Bible
2. Generate script with dialogue scene
3. Generate scene image
4. Open Multi-Shot Decomposition
5. Generate collage (shots have `needsLipSync` where applicable)
6. Click "Animate" on close-up shot
7. Select "Multitalk"
8. Select voice and click "Generate Voice First"
9. Wait for audio generation
10. Click "Animate with Lip-Sync"
11. Verify video has lip-synced speech

---

## Rollback Plan

If issues arise:
1. Set `animation_auto_select_model` to `false` in settings
2. Users can still use MiniMax for all animations
3. Multitalk button can be hidden via CSS if needed

---

## Future Enhancements

1. **Character Voice Assignment** in Character Bible (map character → voice)
2. **Batch Audio Generation** - Generate all shot voiceovers at once
3. **Audio Timeline Preview** - Show audio waveform with shot timeline
4. **Voice Cloning** - Use custom voice samples for characters
5. **Multi-Character Dialogue** - Different voices for different characters in same shot
