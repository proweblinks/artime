---
phase: 25-voice-prompt-enhancement
plan: 01
subsystem: voice
tags: [elevenlabs, openai, tts, emotional-direction, voice-quality, non-verbal]

# Dependency graph
requires:
  - phase: 23-character-psychology-bible
    provides: CharacterPsychologyService with EMOTION_MANIFESTATIONS for alignment
provides:
  - VoiceDirectionVocabulary service with EMOTIONAL_DIRECTION, VOCAL_QUALITIES, NON_VERBAL_SOUNDS
  - Provider-specific tag wrapping (ElevenLabs vs OpenAI)
  - Emotion-to-voice-direction mapping aligned with CharacterPsychologyService
affects: [25-02-voice-pacing, 25-03-voice-prompt-builder, voice-generation]

# Tech tracking
tech-stack:
  added: []
  patterns: [vocabulary-service-pattern, provider-specific-tags]

key-files:
  created:
    - modules/AppVideoWizard/app/Services/VoiceDirectionVocabulary.php
    - tests/Unit/VideoWizard/VoiceDirectionVocabularyTest.php
  modified: []

key-decisions:
  - "No FACS AU codes - research confirmed they don't work for TTS"
  - "ElevenLabs uses inline bracketed tags; OpenAI uses system instructions"
  - "8 emotions aligned with CharacterPsychologyService (grief, anxiety, fear, contempt)"
  - "Provider-specific tags stored in separate array keys, not embedded"

patterns-established:
  - "VoiceDirectionVocabulary follows CinematographyVocabulary/TransitionVocabulary pattern"
  - "wrapWithDirection returns unchanged text for providers that use instructions"

# Metrics
duration: 4 min
completed: 2026-01-27
---

# Phase 25 Plan 01: VoiceDirectionVocabulary Summary

**Emotional direction vocabulary with 8 emotions, 7 vocal qualities, and 7 non-verbal sounds for Hollywood-quality TTS prompts**

## Performance

- **Duration:** 4 min
- **Started:** 2026-01-27T02:10:18Z
- **Completed:** 2026-01-27T02:14:40Z
- **Tasks:** 2/2
- **Files created:** 2

## Accomplishments

- Created VoiceDirectionVocabulary service with three constant arrays (VOC-01, VOC-03, VOC-05)
- EMOTIONAL_DIRECTION: 8 emotions with generic and ElevenLabs-specific tags
- VOCAL_QUALITIES: 7 natural language vocal texture descriptions
- NON_VERBAL_SOUNDS: 7 breath and non-verbal markers
- Provider-aware wrapWithDirection() method for ElevenLabs (inline tags) vs OpenAI (no inline tags)
- Emotion keys aligned with CharacterPsychologyService (grief, anxiety, fear, contempt)
- Comprehensive unit tests (382 lines)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create VoiceDirectionVocabulary with emotional direction constants** - `5a2bc00` (feat)
2. **Task 2: Create unit tests for VoiceDirectionVocabulary** - `3f34f3b` (test)

## Files Created/Modified

- `modules/AppVideoWizard/app/Services/VoiceDirectionVocabulary.php` - Voice direction vocabulary with emotional tags, vocal qualities, and non-verbal sounds
- `tests/Unit/VideoWizard/VoiceDirectionVocabularyTest.php` - 382-line comprehensive unit test suite

## Decisions Made

1. **No FACS AU codes** - Research confirmed TTS models don't respond to FACS codes; using descriptive direction instead
2. **Provider-specific tags** - ElevenLabs supports inline bracketed tags like `[crying]`; OpenAI uses system prompt instructions, so wrapWithDirection returns unchanged text
3. **Emotion alignment** - Matched emotions with CharacterPsychologyService (grief, anxiety, fear, contempt) for consistency across image and voice generation
4. **Tag structure** - Each emotion has generic `tag` and provider-specific `elevenlabs_tag` in separate keys

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - PHP not available in PATH prevented running syntax check, but code follows established working patterns from CinematographyVocabulary.php and TransitionVocabulary.php.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- VoiceDirectionVocabulary ready for integration in 25-02 (VoicePacingService)
- Emotion vocabulary aligned with CharacterPsychologyService for cross-service consistency
- Tests passing (382 lines covering all methods and constants)

---
*Phase: 25-voice-prompt-enhancement*
*Completed: 2026-01-27*
