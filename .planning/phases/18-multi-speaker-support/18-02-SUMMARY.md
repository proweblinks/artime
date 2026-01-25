---
phase: 18-multi-speaker-support
plan: 02
subsystem: services
tags: [voice, tts, multi-speaker, voc-06, voice-registry, dialogue-scene-decomposer, voiceover-service]

# Dependency graph
requires:
  - phase: 18-01
    provides: buildSpeakersArray() helper method with VoiceRegistry integration
provides:
  - DialogueSceneDecomposerService speakers array initialization in createDialogueShot()
  - VoiceoverService getSpeakersFromShot() helper for backward compatibility
  - VoiceoverService processMultiSpeakerShot() method for sequential TTS generation
  - Multi-speaker shot processing pipeline with timing data
affects: [tts-generation, multi-voice-dialogue, audio-timing, shot-processing]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Speakers array initialization in shot creation (DialogueSceneDecomposerService)
    - Backward-compatible speaker extraction (getSpeakersFromShot helper)
    - Sequential multi-speaker TTS with timing (processMultiSpeakerShot)
    - Word-count-based duration estimation (estimateDuration helper)

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php
    - modules/AppVideoWizard/app/Services/VoiceoverService.php

key-decisions:
  - "Speakers initialization: Single-entry array in DialogueSceneDecomposerService (VideoWizard merges additional speakers)"
  - "Backward compatibility: getSpeakersFromShot() handles both new and legacy shot formats"
  - "Sequential TTS processing: Generate audio for each speaker in order, track timing"
  - "Empty text filtering: Apply VOC-02 pattern (skip empty speaker text) in processMultiSpeakerShot"

patterns-established:
  - "Single-speaker initialization in shot creation (expanded by VideoWizard as needed)"
  - "Speaker extraction abstraction (getSpeakersFromShot) for format flexibility"
  - "Multi-speaker TTS pipeline with startTime/duration tracking"
  - "Word-count estimation fallback (150 words/min = 2.5 words/sec)"

# Metrics
duration: 4min
completed: 2026-01-25
---

# Phase 18 Plan 02: Service Layer Multi-Speaker Integration Summary

**DialogueSceneDecomposerService speakers array initialization and VoiceoverService multi-speaker TTS processing with timing data (VOC-06)**

## Performance

- **Duration:** 4 min
- **Started:** 2026-01-25T12:16:59Z
- **Completed:** 2026-01-25T12:20:38Z
- **Tasks:** 3
- **Files modified:** 2

## Accomplishments
- DialogueSceneDecomposerService::createDialogueShot() initializes speakers array with single entry
- VoiceoverService::getSpeakersFromShot() provides backward-compatible speaker extraction
- VoiceoverService::processMultiSpeakerShot() generates TTS for each speaker sequentially
- Audio timing tracked with startTime and duration for concatenation
- Empty text validation applied (VOC-02 pattern)
- All changes tagged with VOC-06 for traceability

## Task Commits

Each task was committed atomically:

1. **Task 1: Initialize speakers array in createDialogueShot** - `078214c` (feat)
2. **Task 2: Add getSpeakersFromShot helper to VoiceoverService** - `d935705` (feat)
3. **Task 3: Add processMultiSpeakerShot method to VoiceoverService** - `f266bc5` (feat)

## Files Created/Modified
- `modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php` - Speakers array initialization in createDialogueShot()
- `modules/AppVideoWizard/app/Services/VoiceoverService.php` - Multi-speaker TTS processing methods

## What Was Done

### Task 1: Initialize speakers array in createDialogueShot (078214c)
- Located shot array construction in createDialogueShot() around line 1442
- Added speakers array initialization after 'spatial' field
- Single speaker entry with name, voiceId, text, order fields
- Added speakerCount = 1 and isMultiSpeaker = false metadata
- Tagged with VOC-06 comment
- Note: VideoWizard.php buildSpeakersArray() merges additional speakers into this array

### Task 2: Add getSpeakersFromShot helper (d935705)
- Added protected helper method after getVoiceForSegment() (around line 1004)
- Handles both new multi-speaker format (shot['speakers']) and legacy format
- Legacy fallback: extracts speakingCharacter, voiceId, dialogue/monologue
- Returns standardized array format: [{name, voiceId, text, order}]
- Enables backward compatibility for existing single-speaker shots

### Task 3: Add processMultiSpeakerShot method (f266bc5)
- Added public method after generateSceneVoiceover() (around line 117)
- Uses getSpeakersFromShot() to extract speakers from shot
- Iterates each speaker, generates TTS with their voiceId
- Skips empty text using VOC-02 validation pattern
- Tracks timing: startTime and duration for each speaker
- Returns structured result with speakers array, totalDuration, success status
- Added estimateDuration() helper: 150 words/min = 2.5 words/sec
- Comprehensive logging with VOC-06 tags

## Key Changes

| Location | What Changed | Impact |
|----------|--------------|--------|
| DialogueSceneDecomposerService::createDialogueShot() | Added speakers array initialization | Every dialogue shot now has speakers structure |
| VoiceoverService::getSpeakersFromShot() | Speaker extraction helper | Abstracts format differences (new vs legacy) |
| VoiceoverService::processMultiSpeakerShot() | Multi-speaker TTS pipeline | Enables sequential TTS generation with timing |
| VoiceoverService::estimateDuration() | Word-count duration estimation | Fallback when actual duration unavailable |

## Decisions Made

1. **Single-speaker initialization in DialogueSceneDecomposerService:** Each shot starts with single speaker in array; VideoWizard.buildSpeakersArray() merges additional speakers when processing speech segments
2. **getSpeakersFromShot() abstraction:** Centralized method handles both multi-speaker array and legacy single-speaker fields for smooth migration
3. **Sequential TTS processing:** processMultiSpeakerShot() generates audio one speaker at a time, not parallel (simpler timing, preserves order)
4. **Timing calculation strategy:** Track cumulative currentTime during sequential generation; use actual duration from TTS result, fallback to estimate
5. **Empty text filtering location:** Applied in processMultiSpeakerShot (not getSpeakersFromShot) to preserve speaker entries for metadata while skipping TTS

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

| Check | Result |
|-------|--------|
| DialogueSceneDecomposerService speakers initialization | Line 1444-1453 (speakers array + metadata fields) |
| VoiceoverService getSpeakersFromShot() | Line 1004 |
| VoiceoverService processMultiSpeakerShot() | Line 117 |
| VoiceoverService estimateDuration() | Line 213 |
| VOC-06 tags in VoiceoverService | 5 occurrences |
| PHP syntax valid (both files) | No errors |

## VOC-06 Requirement Satisfaction

The multi-speaker service integration (VOC-06) is now complete:

1. **Shot initialization:** DialogueSceneDecomposerService creates shots with speakers array structure
2. **Format abstraction:** getSpeakersFromShot() handles both new and legacy formats transparently
3. **TTS processing:** processMultiSpeakerShot() generates audio for each speaker with their voice
4. **Timing data:** Audio segments tracked with startTime, duration for concatenation
5. **Empty text handling:** VOC-02 pattern applied (skip empty speaker text)
6. **Backward compatibility:** Legacy single-speaker shots work without modification

## Issues Encountered

None.

## Next Phase Readiness

Phase 18 (Multi-Speaker Support) is now complete with both plans delivered.

**What's ready:**
- Complete multi-speaker pipeline: shot initialization → speaker extraction → TTS processing
- DialogueSceneDecomposerService creates shots with speakers array
- VideoWizard.buildSpeakersArray() merges additional speakers (18-01)
- VoiceoverService can process multi-speaker shots for TTS generation
- Timing data available for audio concatenation
- Backward compatibility maintained throughout

**Integration points:**
- VideoWizard can call processMultiSpeakerShot() when processing shots with multiple speakers
- Audio timing data (startTime, duration) enables downstream concatenation
- getSpeakersFromShot() provides consistent speaker extraction across codebase

**Milestone 9 Status:**
All 4 phases complete (15, 16, 17, 18) - Voice Production Excellence milestone delivered.

---
*Phase: 18-multi-speaker-support*
*Completed: 2026-01-25*
