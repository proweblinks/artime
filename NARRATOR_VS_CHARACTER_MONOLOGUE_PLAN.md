# Comprehensive Plan: Narrator vs Character Monologue Distinction

## Executive Summary

The Storyboard Studio system needs to properly distinguish between **narrator monologue** (off-screen voice requiring TTS only) and **character monologue** (on-screen character requiring Multitalk lip-sync video generation).

### Current State
- **Data Model**: ✅ Well-defined (SpeechSegment class with TYPE_NARRATOR, TYPE_MONOLOGUE, TYPE_DIALOGUE, TYPE_INTERNAL)
- **UI Layer**: ✅ Speech type selection works correctly
- **Parser**: ✅ Can parse [NARRATOR], [MONOLOGUE: CHARACTER] markers
- **Generation Routing**: ❌ CRITICAL GAP - Both types route to TTS, no Multitalk routing for character monologue

### Target State
- Narrator monologue → TTS only → Audio track (background voiceover)
- Character monologue → Multitalk → Lip-sync video with embedded audio

---

## Phase 1: Audit & Verification (Research Complete)

### Key Files Identified

| File | Location | Role |
|------|----------|------|
| `SpeechSegment.php` | `modules/AppVideoWizard/app/Services/` | Core data model |
| `SpeechSegmentParser.php` | `modules/AppVideoWizard/app/Services/` | Text parsing |
| `VoiceoverService.php` | `modules/AppVideoWizard/app/Services/` | Audio generation |
| `DialogueSceneDecomposerService.php` | `modules/AppVideoWizard/app/Services/` | Shot decomposition |
| `VideoWizard.php` | `modules/AppVideoWizard/app/Livewire/` | Main Livewire component |
| `storyboard.blade.php` | `modules/AppVideoWizard/resources/views/livewire/steps/` | Storyboard UI |
| `multi-shot.blade.php` | `modules/AppVideoWizard/resources/views/livewire/modals/` | Multi-shot modal |
| `shot-preview.blade.php` | `modules/AppVideoWizard/resources/views/livewire/modals/` | Shot preview modal |

### Current Speech Type Constants
```php
// SpeechSegment.php
public const TYPE_NARRATOR = 'narrator';   // Off-screen voice, TTS only
public const TYPE_DIALOGUE = 'dialogue';   // Character talking, needs lip-sync
public const TYPE_INTERNAL = 'internal';   // Inner thoughts, TTS only
public const TYPE_MONOLOGUE = 'monologue'; // Character speaking alone, needs lip-sync

public const LIP_SYNC_TYPES = [TYPE_DIALOGUE, TYPE_MONOLOGUE];
public const VOICEOVER_ONLY_TYPES = [TYPE_NARRATOR, TYPE_INTERNAL];
```

---

## Phase 2: Schema & Data Model Updates

### 2.1 Verify Shot Data Structure
Each shot should include:
```php
[
    'id' => 'shot-uuid',
    'speechType' => 'narrator|dialogue|internal|monologue',
    'needsLipSync' => true|false,           // Derived from speechType
    'useMultitalk' => true|false,           // Explicit flag for generation routing
    'speaker' => 'Character Name',          // null for narrator
    'characterId' => 'character-uuid',      // Reference to Character Bible
    'voiceId' => 'nova|echo|custom',        // TTS voice to use
    'monologue' => 'Text content',          // The speech text

    // Generation results
    'audioUrl' => null,                     // TTS result (for narrator/internal)
    'videoUrl' => null,                     // Multitalk result (for monologue/dialogue)
    'generationMethod' => 'tts|multitalk',  // How it was generated
]
```

### 2.2 Add Missing Fields to Shot Creation
**File:** `VideoWizard.php`

Ensure all shot creation methods include:
- `speechType` - explicit type
- `needsLipSync` - calculated flag
- `useMultitalk` - explicit routing flag
- `generationMethod` - track which API was used

---

## Phase 3: Backend Generation Routing

### 3.1 Create Generation Router Service
**New File:** `modules/AppVideoWizard/app/Services/SpeechGenerationRouter.php`

```php
<?php

namespace Modules\AppVideoWizard\app\Services;

class SpeechGenerationRouter
{
    /**
     * Determine the appropriate generation method for a speech segment
     */
    public function getGenerationMethod(array $shot): string
    {
        $speechType = $shot['speechType'] ?? 'narrator';
        $needsLipSync = $shot['needsLipSync'] ?? false;

        // Explicit check for lip-sync types
        if (in_array($speechType, SpeechSegment::LIP_SYNC_TYPES, true)) {
            return 'multitalk';
        }

        // Voiceover-only types
        if (in_array($speechType, SpeechSegment::VOICEOVER_ONLY_TYPES, true)) {
            return 'tts';
        }

        // Fallback based on needsLipSync flag
        return $needsLipSync ? 'multitalk' : 'tts';
    }

    /**
     * Route shot to appropriate generation service
     */
    public function generateSpeechForShot(array $shot, array $config): array
    {
        $method = $this->getGenerationMethod($shot);

        if ($method === 'multitalk') {
            return $this->generateMultitalk($shot, $config);
        }

        return $this->generateTTS($shot, $config);
    }

    /**
     * Generate TTS audio only (narrator, internal thoughts)
     */
    protected function generateTTS(array $shot, array $config): array
    {
        // Use VoiceoverService for TTS
        // Returns: ['audioUrl' => '...', 'duration' => X]
    }

    /**
     * Generate Multitalk lip-sync video (monologue, dialogue)
     */
    protected function generateMultitalk(array $shot, array $config): array
    {
        // Use Multitalk API for lip-sync video
        // Returns: ['videoUrl' => '...', 'audioUrl' => '...', 'duration' => X]
    }
}
```

### 3.2 Update VoiceoverService
**File:** `modules/AppVideoWizard/app/Services/VoiceoverService.php`

Add method to check generation type before processing:

```php
/**
 * Generate audio/video based on speech type
 */
public function generateForShot(array $shot, array $options = []): array
{
    $speechType = $shot['speechType'] ?? 'narrator';
    $needsLipSync = in_array($speechType, SpeechSegment::LIP_SYNC_TYPES, true);

    if ($needsLipSync) {
        // Route to Multitalk for lip-sync video
        return $this->generateMultitalkVideo($shot, $options);
    }

    // Route to TTS for audio-only
    return $this->generateVoiceoverAudio($shot, $options);
}
```

### 3.3 Update Shot Generation Pipeline
**File:** `VideoWizard.php`

In the generation methods, use the router:

```php
protected function generateShotMedia(array $shot, int $sceneIndex, int $shotIndex): void
{
    $router = app(SpeechGenerationRouter::class);

    $result = $router->generateSpeechForShot($shot, [
        'characterBible' => $this->characterBible,
        'narratorVoice' => $this->animation['voiceover']['voice'] ?? 'nova',
    ]);

    // Update shot with results
    $this->updateShotGenerationResult($sceneIndex, $shotIndex, $result);
}
```

---

## Phase 4: UI Updates

### 4.1 Shot Preview Modal - Center Content
**File:** `modules/AppVideoWizard/resources/views/livewire/modals/shot-preview.blade.php`

Fix centering of image/video:
```css
.shot-preview-media {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.shot-preview-media img,
.shot-preview-media video {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
```

### 4.2 Speech Type Badge Enhancement
Show clear distinction in UI:

```php
// Different badges for different types
@if($speechType === 'narrator')
    <span class="speech-badge narrator">
        🎙️ NARRATOR (Voiceover Only)
    </span>
@elseif($speechType === 'monologue')
    <span class="speech-badge monologue">
        🗣️ CHARACTER MONOLOGUE (Lip-Sync)
    </span>
@elseif($speechType === 'dialogue')
    <span class="speech-badge dialogue">
        💬 DIALOGUE (Lip-Sync)
    </span>
@elseif($speechType === 'internal')
    <span class="speech-badge internal">
        💭 INTERNAL THOUGHTS (Voiceover Only)
    </span>
@endif
```

### 4.3 Generation Method Indicator
Show which API will be used:

```php
<div class="generation-method">
    @if($needsLipSync)
        <span class="method-badge multitalk">
            🎬 Will use Multitalk (Lip-Sync Video)
        </span>
    @else
        <span class="method-badge tts">
            🔊 Will use TTS (Audio Only)
        </span>
    @endif
</div>
```

### 4.4 Storyboard Scene Card Updates
Show speech type at scene level with generation indicator.

---

## Phase 5: Testing & Validation

### 5.1 Test Cases

| Scenario | Speech Type | Expected Method | Expected Output |
|----------|-------------|-----------------|-----------------|
| Narrator describes scene | narrator | TTS | Audio track only |
| Character thinks to self | internal | TTS | Audio track only |
| Character speaks alone on screen | monologue | Multitalk | Lip-sync video |
| Two characters talking | dialogue | Multitalk | Lip-sync video |
| Mixed scene (narrator + dialogue) | mixed | Both | Segmented by type |

### 5.2 Validation Checklist
- [ ] Speech type persists through all pipeline stages
- [ ] `needsLipSync` flag correctly derived from type
- [ ] Router correctly identifies generation method
- [ ] TTS generates audio-only for narrator/internal
- [ ] Multitalk generates video for monologue/dialogue
- [ ] UI clearly shows which method will be used
- [ ] Timeline correctly places audio vs video tracks

---

## Phase 6: Implementation Priority

### High Priority (Critical Path)
1. **Fix shot preview modal centering** (UI issue from user report)
2. **Add SpeechGenerationRouter service** (Core routing logic)
3. **Update VoiceoverService** to use router
4. **Update VideoWizard generation methods** to use router

### Medium Priority (Enhancement)
5. **Update UI badges** to show generation method
6. **Add generation method tracking** to shot data
7. **Update multi-shot modal** to show lip-sync indicator

### Low Priority (Polish)
8. **Add logging** for generation routing decisions
9. **Add user preferences** for default generation settings
10. **Add preview/test** for TTS vs Multitalk before full generation

---

## Files to Modify

### New Files
- `modules/AppVideoWizard/app/Services/SpeechGenerationRouter.php`

### Modified Files
1. `modules/AppVideoWizard/app/Services/VoiceoverService.php` - Add routing logic
2. `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Use router in generation
3. `modules/AppVideoWizard/resources/views/livewire/modals/shot-preview.blade.php` - Center content, show type
4. `modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php` - Show generation method
5. `modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php` - Show lip-sync indicator

---

## Summary

The core issue is that the system has proper **data model support** for speech types but lacks **generation routing logic** to direct narrator content to TTS and character content to Multitalk.

**Key Insight:** The `needsLipSync` flag already exists and is correctly set. The fix is to actually USE this flag in the generation pipeline to route to the appropriate API.

### Quick Win
The fastest fix is to add a check in the generation methods:
```php
if ($shot['needsLipSync'] ?? false) {
    // Use Multitalk
} else {
    // Use TTS
}
```

This leverages the existing infrastructure without major refactoring.
