---
phase: 11-speech-driven-shot-creation
plan: 02
subsystem: video-generation
tags: [narrator, voiceover, internal-thought, overlay, speech-segments]

# Dependency graph
requires:
  - phase: 11-01
    provides: createShotsFromSpeechSegments for 1:1 dialogue/monologue to shot mapping
provides:
  - overlayNarratorSegments() method for narrator as metadata overlay
  - markInternalThoughtAsVoiceover() for internal thought voiceover handling
  - createBaseVisualShotsForNarrator() for narrator-only scenes
  - Speech segment processing order documentation
affects: [12-shot-reverse-shot, audio-generation, voiceover-rendering]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Narrator segments overlay across multiple shots (not dedicated visual shots)"
    - "Internal thought segments flagged as voiceover-only (no lip-sync)"
    - "Speech segment processing order: Dialogue/Monologue -> Narrator -> Internal"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Narrator segments stored as narratorOverlay array on shots, not as separate visual shots"
  - "Internal thoughts distributed as voiceover with speaker-based voice selection"
  - "Narrator-only scenes automatically get 3-5 base visual shots created"
  - "Processing order documented in overlayNarratorSegments() PHPDoc"

patterns-established:
  - "Overlay pattern: Non-visual segments (narrator, internal) become metadata on visual shots"
  - "Visual-only shots: Base shots for narrator voiceover with no lip-sync requirement"
  - "Defensive logging: Skip reasons logged for debugging segment type filtering"

# Metrics
duration: 12min
completed: 2026-01-23
---

# Phase 11 Plan 02: Narrator Overlay and Voiceover Handling Summary

**Narrator and internal thought segments handled as voiceover overlays across existing visual shots, not as dedicated visual shots**

## Performance

- **Duration:** 12 min
- **Started:** 2026-01-23T16:25:37Z
- **Completed:** 2026-01-23T16:37:42Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- Narrator segments distributed as metadata overlay across existing dialogue/monologue shots
- Internal thought segments flagged for voiceover-only processing (no visual shot needed)
- Narrator-only scenes automatically get 3-5 base visual shots created
- Processing order documented: Dialogue/Monologue -> Narrator -> Internal Thought
- Defensive logging and validation for segment type filtering

## Task Commits

All 3 tasks were committed atomically:

1. **Task 1: Create narrator overlay distribution system** - `3ae4b41` (feat)
2. **Task 2: Handle internal thought segments as voiceover-only** - `3ae4b41` (feat)
3. **Task 3: Add segment type filtering to shot creation** - `3ae4b41` (feat)

_Note: All tasks committed together as they form a cohesive feature_

## Files Created/Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added narrator overlay system:
  - `overlayNarratorSegments()` at line 23449
  - `createBaseVisualShotsForNarrator()` at line 23516
  - `selectShotTypeForNarrator()` at line 23565
  - `markInternalThoughtAsVoiceover()` at line 23591
  - Integration in `enrichShotsWithMonologueStored()` at line 23143
  - Enhanced `createShotsFromSpeechSegments()` with defensive logging

## Key Changes

### overlayNarratorSegments() Method
```php
protected function overlayNarratorSegments(int $sceneIndex, array $shots, array $speechSegments): array
```
- Extracts narrator segments from speechSegments
- Distributes evenly across existing visual shots
- Stores as `narratorOverlay` array and `narratorText` combined text
- Sets `hasNarratorVoiceover` flag for voiceover generation

### markInternalThoughtAsVoiceover() Method
```php
protected function markInternalThoughtAsVoiceover(int $sceneIndex, array $shots, array $speechSegments): array
```
- Extracts internal thought segments
- Stores as `internalThoughtOverlay` array with `hasInternalVoiceover` flag
- Includes speaker-based voice selection via `internalVoiceId`

### createBaseVisualShotsForNarrator() Method
```php
protected function createBaseVisualShotsForNarrator(int $sceneIndex, array $scene): array
```
- Creates 3-5 visual shots for narrator-only scenes
- Uses establishing/wide/medium shot progression
- Sets `visualOnly` flag and `needsLipSync` = false

## Decisions Made

1. **Narrator as overlay, not dedicated shots** - Narrator voiceover should span multiple visual shots (like documentary narration), not have its own visual shot
2. **Internal thought = voiceover-only** - Character thinking produces audio but no lip movement, so no visual shot needed
3. **Narrator-only scene handling** - When scene has only narrator segments (no dialogue/monologue), create base visual shots automatically
4. **Processing order** - Document and enforce Dialogue/Monologue -> Narrator -> Internal order for predictable behavior

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - implementation proceeded smoothly.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Narrator overlay system complete and integrated
- Ready for audio generation to use narratorOverlay and internalThoughtOverlay data
- Ready for Phase 12 (Shot/Reverse-Shot Enhancement) to build on speech-driven foundation
- CSA-03 and CSA-04 requirements from Milestone 8 roadmap are now complete

---
*Phase: 11-speech-driven-shot-creation*
*Completed: 2026-01-23*
