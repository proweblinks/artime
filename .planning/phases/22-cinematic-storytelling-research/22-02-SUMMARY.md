---
phase: 22-cinematic-storytelling-research
plan: 02
subsystem: video-wizard
tags: [prompt-engineering, gaze-direction, cinematic, ai-generation]

# Dependency graph
requires:
  - phase: 22-01
    provides: Anti-portrait negative prompts and buildNegativePrompt() method
provides:
  - GAZE_TEMPLATES constant with 16 shot-type-specific gaze directions
  - getGazeDirectionForShot() method for dynamic gaze retrieval
  - Gaze direction integration in buildShotPrompt()
affects: [22-03, future-prompt-enhancements]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Shot-type-to-behavior mapping via constants"
    - "Context-aware template string replacement"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Empty gaze for POV/establishing/detail shots (no subject or environment focus)"
  - "Gaze direction placed after story content, before dialogue in prompt order"
  - "Character name substitution in conversation shots when context available"

patterns-established:
  - "GAZE_TEMPLATES: Shot type maps to gaze behavior, empty string for inapplicable shots"
  - "Prompt order: Story > Gaze > Dialogue > Camera > Technical"

# Metrics
duration: 12min
completed: 2026-01-28
---

# Phase 22 Plan 02: Gaze Direction Templates Summary

**Shot-specific gaze direction templates preventing AI "looking at camera" default by explicitly specifying WHERE characters look for each shot type**

## Performance

- **Duration:** 12 min
- **Started:** 2026-01-28T00:00:00Z
- **Completed:** 2026-01-28T00:12:00Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- Added GAZE_TEMPLATES constant mapping 16 shot types to appropriate gaze directions
- Created getGazeDirectionForShot() method with context-aware character name substitution
- Integrated gaze direction into buildShotPrompt() prompt assembly pipeline
- POV, establishing, and detail shots correctly return empty gaze (no subject visible)
- Close-up and reaction shots explicitly direct gaze off-screen

## Task Commits

Each task was committed atomically:

1. **Task 1: Add GAZE_TEMPLATES constant** - `109b46b` (feat)
2. **Task 2: Create getGazeDirectionForShot() method** - `62f4153` (feat) - Note: Committed alongside Plan 22-03 Task 1
3. **Task 3: Integrate gaze direction into buildShotPrompt()** - `901b772` (feat)

## Files Created/Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php`
  - Added GAZE_TEMPLATES constant (lines 662-690)
  - Added getGazeDirectionForShot() method (lines 21668-21690)
  - Modified buildShotPrompt() to include gaze direction (lines 21176-21181)

## Decisions Made

1. **Empty gaze for certain shot types:** POV (we ARE the viewer), establishing/extreme-wide (environment focus), and detail/insert shots (object focus) have empty gaze templates. No gaze instruction is better than a wrong one for these shots.

2. **Gaze placement in prompt:** Gaze direction comes after story content but before dialogue. This creates the flow: "What's happening" > "Where they're looking" > "What they're saying" > "How it's framed".

3. **Context-aware character substitution:** For conversation shots (medium, medium-close, over-shoulder, two-shot), if multiple characters are in context, the second character name replaces generic "other character" and "conversation partner" placeholders.

## Deviations from Plan

### Cross-plan Commit Interleaving

**1. [Observation] Task 2 committed with Plan 22-03 Task 1**
- **Situation:** The getGazeDirectionForShot() method was committed in commit `62f4153` which is labeled as Plan 22-03
- **Cause:** Plans 22-02 and 22-03 were being executed concurrently or in quick succession
- **Impact:** None functional - the method is correctly implemented and in the codebase
- **Verification:** Method exists and is called correctly by buildShotPrompt()

---

**Total deviations:** 1 observation (commit labeling only)
**Impact on plan:** No functional impact. All three tasks complete and working correctly.

## Issues Encountered

None - plan executed as specified with minor commit labeling variation.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Gaze direction system complete and integrated
- Ready for Plan 22-03 (Dynamic Action Poses) which adds action verb variety
- Combined with Plan 22-01 (anti-portrait negatives), the prompt system now has three layers of anti-portrait protection:
  1. Negative prompts blocking "looking at camera" terminology
  2. Positive gaze directions specifying WHERE to look
  3. (Plan 22-03) Action verbs creating dynamic narrative moments

---
*Phase: 22-cinematic-storytelling-research*
*Completed: 2026-01-28*
