---
phase: 18-multi-speaker-support
plan: 01
subsystem: livewire
tags: [voice, tts, multi-speaker, voc-06, voice-registry, dialogue]

# Dependency graph
requires:
  - phase: 17-02
    provides: VoiceRegistryService integration in VideoWizard with registry-first lookups
provides:
  - Multi-speaker shot data structure with speakers array
  - buildSpeakersArray() helper method for structured speaker entries
  - VoiceRegistry integration for multi-speaker voice lookups
  - Backward-compatible single-speaker fields
affects: [tts-generation, voice-continuity, multi-voice-dialogue]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - buildSpeakersArray() pattern for structured speaker entries
    - Multi-speaker array with name, voiceId, text, order fields
    - Backward compatibility with speakingCharacter and voiceId fields

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "buildSpeakersArray() method: Centralized logic for building speaker entries with VoiceRegistry integration"
  - "Backward compatibility pattern: Always populate speakingCharacter and voiceId from first speaker"
  - "Empty text filtering: Skip speakers with empty text using VOC-02 pattern"
  - "VoiceRegistry integration: Use voiceRegistry->getVoiceForCharacter with fallback to getVoiceForCharacterName"

patterns-established:
  - "Speaker entry structure: {name, voiceId, text, order} with optional duration and audioUrl"
  - "Multi-speaker metadata: speakers array, speakerCount, isMultiSpeaker fields on shots"
  - "Null-safe registry usage: Check voiceRegistry !== null before calling"

# Metrics
duration: 5min
completed: 2026-01-25
---

# Phase 18 Plan 01: Multi-Speaker Support Summary

**Multi-speaker shot data structure with speakers array, VoiceRegistry integration, and backward-compatible single-speaker fields (VOC-06)**

## Performance

- **Duration:** 5 min
- **Started:** 2026-01-25T17:07:48Z
- **Completed:** 2026-01-25T17:12:36Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments
- Added buildSpeakersArray() helper method for creating structured speaker entries
- Refactored both speaker extraction locations (assignDialogueToShots, distributeSpeechSegmentsToShots)
- Shots now track all speakers with voice IDs, not just first speaker
- VoiceRegistryService integration ensures consistent voice assignment
- Backward compatibility maintained with speakingCharacter and voiceId fields

## Task Commits

Each task was committed atomically:

1. **Task 1: Add helper method for building speaker entries** - `8fad04f` (feat)
2. **Task 2: Refactor first speaker extraction in assignDialogueToShots** - `5b08507` (feat)
3. **Task 3: Refactor first speaker extraction in distributeSpeechSegmentsToShots** - `68c4aff` (feat)

## Files Created/Modified
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added buildSpeakersArray() method and refactored two speaker extraction locations

## What Was Done

### Task 1: Add buildSpeakersArray() helper method (8fad04f)
- Added protected method buildSpeakersArray() after getVoiceForCharacterName() method (line 23406)
- Method iterates all speakers (not just first) to build structured array
- Uses VoiceRegistryService if available with fallback to getVoiceForCharacterName()
- Filters empty speaker text using VOC-02 pattern (empty text validation)
- Returns array with entries: {name, voiceId, text, order}
- Tagged with VOC-06 documentation

### Task 2: Refactor assignDialogueToShots() (5b08507)
- Located first speaker extraction around line 23335
- Replaced `$firstSpeaker = array_keys($shotSpeakers)[0]` with buildSpeakersArray() call
- Populates multi-speaker data: speakers array, speakerCount, isMultiSpeaker fields
- Maintains backward compatibility: speakingCharacter and voiceId from first speaker
- Tagged with VOC-06 comment

### Task 3: Refactor distributeSpeechSegmentsToShots() (68c4aff)
- Located second speaker extraction around line 23918
- Replaced `$firstSpeaker = array_keys($speakers)[0]` with buildSpeakersArray() call
- Same pattern as Task 2: multi-speaker data + backward compatibility
- Both speaker extraction locations now use consistent buildSpeakersArray() pattern

## Key Changes

| Location | Before | After |
|----------|--------|-------|
| Line 23335 | `$firstSpeaker = array_keys($shotSpeakers)[0]` | `buildSpeakersArray($shotSpeakers)` |
| Line 23918 | `$firstSpeaker = array_keys($speakers)[0]` | `buildSpeakersArray($speakers)` |
| Shot data | Only voiceId field | speakers array + speakerCount + isMultiSpeaker fields |

## Decisions Made

1. **buildSpeakersArray() centralization:** Created single method for building speaker entries rather than duplicating logic in both locations
2. **VoiceRegistry integration pattern:** Use null-check (`$this->voiceRegistry !== null`) before calling registry, fallback to direct method
3. **Backward compatibility approach:** Always populate speakingCharacter and voiceId from first speaker to avoid breaking existing code
4. **Empty text filtering:** Apply VOC-02 pattern (skip empty text) within buildSpeakersArray() to prevent empty strings in speakers array

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

| Check | Result |
|-------|--------|
| buildSpeakersArray() method exists | Line 23406 |
| VOC-06 tags present | 4 occurrences (1 in method doc, 1 in method body, 2 in call sites) |
| buildSpeakersArray() calls | 3 occurrences (1 definition + 2 calls) |
| isMultiSpeaker field | 2 occurrences (both locations) |
| speakerCount field | 2 occurrences (both locations) |
| Backward compatibility | 2 comments, speakingCharacter/voiceId populated in both locations |

## VOC-06 Requirement Satisfaction

The multi-speaker shot support requirement (VOC-06) is now satisfied:

1. **All speakers tracked:** Shots with multiple speakers have complete speakers array
2. **Structured entries:** Each speaker has {name, voiceId, text, order}
3. **VoiceRegistry integration:** Voice lookups use VoiceRegistryService for consistency
4. **Empty text filtering:** VOC-02 pattern applied (skip empty speaker text)
5. **Backward compatibility:** speakingCharacter and voiceId still populated from first speaker

## Issues Encountered

None.

## Next Phase Readiness

Phase 18 (Multi-Speaker Support) is now complete with Plan 01 delivered.

**What's ready:**
- Shot data structure supports multiple speakers per shot
- VoiceRegistry ensures consistent voice assignment across speakers
- Downstream TTS processing can now access all speakers via `speakers` array
- Backward compatibility maintained with existing single-speaker code

**Future work:**
- VoiceoverService can be extended to process `speakers` array for multi-speaker TTS
- DialogueSceneDecomposerService can leverage `isMultiSpeaker` flag for shot intelligence
- UI can display multi-speaker metadata (speaker names, voice IDs)

---
*Phase: 18-multi-speaker-support*
*Completed: 2026-01-25*
