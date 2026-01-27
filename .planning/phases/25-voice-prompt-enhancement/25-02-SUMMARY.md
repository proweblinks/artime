---
phase: 25-voice-prompt-enhancement
plan: 02
subsystem: voice, tts
tags: [voice-pacing, ssml, timing, pause-markers, rate-modifiers]

# Dependency graph
requires:
  - phase: 25-01
    provides: VoiceDirectionVocabulary for emotional delivery context
provides:
  - VoicePacingService with timing markers and SSML conversion
  - PAUSE_TYPES constant (beat, short, medium, long, breath)
  - PACING_MODIFIERS constant (slow, measured, normal, urgent, rushed)
  - insertPauseMarker for custom timing [PAUSE Xs]
  - toSSML for SSML break tag conversion
  - buildPacingInstruction for modifier+pause combinations
  - estimatePacingDuration for total pause calculation
affects: [25-03, voice-prompt-integration, tts-providers]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Named pause types with durations and SSML mappings
    - Rate modifiers for delivery speed control
    - Human-readable notation converted to SSML on demand

key-files:
  created:
    - modules/AppVideoWizard/app/Services/VoicePacingService.php
    - tests/Unit/VideoWizard/VoicePacingServiceTest.php
  modified: []

key-decisions:
  - "Five named pause types: beat (0.5s), short (1s), medium (2s), long (3s), breath (0.3s)"
  - "Five rate modifiers: slow (0.85x), measured (0.9x), normal (1.0x), urgent (1.1x), rushed (1.2x)"
  - "SSML uses milliseconds for sub-second precision, seconds for whole numbers"
  - "toSSML converts both custom [PAUSE Xs] and named [beat] markers"

patterns-established:
  - "Pacing service pattern: constants with notation/ssml/description for each type"
  - "Duration estimation pattern: scan text for all pause markers and sum durations"
  - "SSML conversion pattern: regex-based marker replacement preserving surrounding text"

# Metrics
duration: 8min
completed: 2026-01-27
---

# Phase 25 Plan 02: Voice Pacing Service Summary

**VoicePacingService with timing markers ([PAUSE 2.5s], [beat]), rate modifiers ([SLOW], [urgent]), and SSML break tag conversion for TTS providers**

## Performance

- **Duration:** 8 min
- **Started:** 2026-01-27T04:15:00Z
- **Completed:** 2026-01-27T04:23:00Z
- **Tasks:** 2
- **Files created:** 2

## Accomplishments

- Created VoicePacingService with PAUSE_TYPES and PACING_MODIFIERS constants
- Implemented insertPauseMarker for custom timing notation [PAUSE Xs]
- Implemented toSSML for converting markers to SSML break tags
- Implemented buildPacingInstruction for combining modifiers with pauses
- Created comprehensive unit tests (428 lines, 40+ test cases)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create VoicePacingService with timing constants** - `5313dcf` (feat)
2. **Task 2: Create unit tests for VoicePacingService** - `a714a7c` (test)

## Files Created/Modified

- `modules/AppVideoWizard/app/Services/VoicePacingService.php` - Pacing markers, timing notation, SSML conversion
- `tests/Unit/VideoWizard/VoicePacingServiceTest.php` - Comprehensive unit tests for all pacing functionality

## Key Constants

### PAUSE_TYPES

| Type | Duration | Notation | SSML | Description |
|------|----------|----------|------|-------------|
| beat | 0.5s | [beat] | `<break time="500ms"/>` | micro-pause for emphasis |
| short | 1.0s | [short pause] | `<break time="1s"/>` | brief pause for breath |
| medium | 2.0s | [pause] | `<break time="2s"/>` | standard dramatic pause |
| long | 3.0s | [long pause] | `<break time="3s"/>` | extended dramatic silence |
| breath | 0.3s | [breath] | `<break time="300ms"/>` | natural breathing pause |

### PACING_MODIFIERS

| Modifier | Rate | Notation | SSML Rate | Description |
|----------|------|----------|-----------|-------------|
| slow | 0.85x | [SLOW] | -15% | deliberate, measured delivery |
| measured | 0.9x | [measured] | -10% | careful, thoughtful pace |
| normal | 1.0x | (none) | 0% | standard speaking pace |
| urgent | 1.1x | [urgent] | +10% | pressured, time-sensitive |
| rushed | 1.2x | [rushed] | +20% | hurried, breathless delivery |

## Key Methods

- `insertPauseMarker(2.5)` returns `[PAUSE 2.5s]`
- `getPauseNotation('beat')` returns `[beat]`
- `getPauseDuration('medium')` returns `2.0`
- `getModifierNotation('slow')` returns `[SLOW]`
- `toSSML('[PAUSE 2s] Hello [beat] world')` returns `<break time="2s"/> Hello <break time="500ms"/> world`
- `buildPacingInstruction('slow', 'medium')` returns `[SLOW] [pause]`
- `estimatePacingDuration('[pause] [beat]')` returns `2.5` (2.0 + 0.5)

## Decisions Made

1. **Five named pause types** - beat (0.5s), short (1s), medium (2s), long (3s), breath (0.3s) cover the full range from micro-pauses to dramatic silences
2. **Five rate modifiers** - slow through rushed cover deliberate to hurried delivery
3. **SSML precision** - Uses milliseconds for sub-second values (e.g., 500ms), seconds for whole numbers (e.g., 2s)
4. **Case-insensitive matching** - Both `[PAUSE 2s]` and `[pause 2s]` convert correctly
5. **Normal modifier empty notation** - Normal rate has no visible marker to keep prompts clean

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- VoicePacingService ready for integration with VoicePromptBuilderService (25-03)
- Pacing markers can be inserted into voice prompts for timing control
- SSML conversion available for providers that support break tags
- Duration estimation enables total pause time calculation for scenes

---
*Phase: 25-voice-prompt-enhancement*
*Plan: 02*
*Completed: 2026-01-27*
