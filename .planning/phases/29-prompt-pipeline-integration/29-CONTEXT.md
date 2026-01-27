# Phase 29: Prompt Pipeline Integration

## Discovery Source

User-reported issues from manual testing after M11.1 completion. Screenshots provided showing:
1. Shot Preview modal with short/generic image prompts
2. Missing VOICE PROMPT section in modal
3. Low-quality image output from wrong default model

## Issues Identified

### Issue 1: Short/Generic Image Prompts (Critical)

**Symptom:** IMAGE PROMPT in Shot Preview shows basic text like "Establishing shot revealing scene geography..."

**Root Cause:** Two prompt building methods exist:
- `buildScenePrompt()` (lines 11157-11276+) — **COMPREHENSIVE** with 4 layers:
  - Layer 1: Style Bible (visual DNA, color grade, atmosphere, camera)
  - Layer 2: Character Bible (character descriptions + personality traits)
  - Layer 3: Location Bible (location name, type, description, time of day, weather)
  - Layer 4: Scene content (visual style, mood, lighting, color palette)

- `buildEnhancedShotImagePrompt()` (lines 20862-20910) — **INCOMPLETE**, only uses:
  - Shot type description
  - uniqueVisualDescription or description
  - Subject and action
  - Lens
  - Style Bible style (just one field, not full bible)
  - Technical specs

**The Gap:** Shot-level prompts don't include Character DNA or Location DNA from Scene Memory. The comprehensive `buildScenePrompt()` is used for prompt chain processing but NOT for individual shot image generation.

**Impact:** Images are generic because they lack character appearance info, location context, and scene-specific details.

### Issue 2: Missing Voice Prompt Display

**Symptom:** Shot Preview modal shows IMAGE PROMPT and VIDEO PROMPT sections but NO VOICE PROMPT.

**Root Cause:**
- `VoicePromptBuilderService` exists (Phase 25) with `buildEnhancedVoicePrompt()`
- VoiceoverService now calls it (Phase 28, Plan 04)
- BUT: Voice prompts are generated at TTS time, not stored in shot structure
- Shot Preview modal has no `voicePrompt` field to display

**The Gap:** Voice direction (narrator text, dialogue, emotional direction) is not:
1. Pre-computed when shots are created
2. Stored in shot data structure
3. Displayed in Shot Preview modal

**Impact:** Users can't preview what voice content will be generated for each shot.

### Issue 3: Wrong Default Model

**Symptom:** Images are lower quality than expected.

**Root Cause:** Default `imageModel` is `'nanobanana'` (1 token, "Quick drafts, lower cost") instead of `'nanobanana-pro'` (3 tokens, "High quality, fast generation").

**Locations:**
- Line 721: Initial storyboard default
- Line 1739: Storyboard reset
- Line 11947: Another reset location

**Impact:** 1/3 the quality budget spent on image generation by default.

## Requirements

| ID | Description | Priority |
|----|-------------|----------|
| PPL-01 | Shot prompts include Character DNA (character descriptions from Scene Memory) | P0 |
| PPL-02 | Shot prompts include Location DNA (location details from Scene Memory) | P0 |
| PPL-03 | Voice prompt displayed in Shot Preview modal | P1 |
| PPL-04 | Default image model is nanobanana-pro (3 tokens) | P0 |

## Success Criteria

1. Shot Preview IMAGE PROMPT shows full prompt with CHARACTERS and LOCATION sections
2. Shot Preview has VOICE PROMPT section showing narration/dialogue and emotional direction
3. New projects default to nanobanana-pro model
4. Images generated are visually richer and story-specific

## Technical Approach

### PPL-01 & PPL-02: Enhance buildEnhancedShotImagePrompt()

Modify `buildEnhancedShotImagePrompt()` to call into the same bible integration logic used by `buildScenePrompt()`. Either:
- Extract bible integration into shared helper methods
- Call `buildScenePrompt()` and merge with shot-specific data
- Inline the Character Bible and Location Bible integration (copy Layer 2 and Layer 3)

The key missing data:
```php
// From buildScenePrompt() - needs to be added to buildEnhancedShotImagePrompt()
// CHARACTERS: {name}: {description} (personality: {traits})
// LOCATION: {name} ({type}), {description}, {timeOfDay}, {weather}
```

### PPL-03: Add Voice Prompt to Shot Structure and UI

1. When creating/decomposing shots, generate voice prompt:
   - Use `VoicePromptBuilderService::buildEnhancedVoicePrompt()`
   - Store in `$shot['voicePrompt']` or `$shot['voiceDirection']`

2. Add UI section to shot-preview.blade.php:
   ```blade
   {{-- Voice Prompt --}}
   <div style="background: rgba(139, 92, 246, 0.1); ...">
       <div>🎤 {{ __('VOICE PROMPT') }}</div>
       <div>{{ $shot['voicePrompt'] ?? ... }}</div>
   </div>
   ```

### PPL-04: Change Default Model

Simple string replacement in 3 locations:
```php
'imageModel' => 'nanobanana-pro',  // Was 'nanobanana'
```

## Files to Modify

| File | Changes |
|------|---------|
| VideoWizard.php | Enhance `buildEnhancedShotImagePrompt()`, change default model |
| shot-preview.blade.php | Add VOICE PROMPT section |

## Dependencies

- Phase 25: VoicePromptBuilderService (exists, working)
- Phase 28: Voice registry integration (exists, working)
- Scene Memory system (characterBible, locationBible) must be populated

## Notes

These issues predate M11.1. The voice production work (Phase 28) correctly integrated voice prompts into TTS generation, but the display layer was never implemented. Similarly, the comprehensive prompt chain (`buildScenePrompt`) was built but shot-level generation uses a separate, incomplete method.
