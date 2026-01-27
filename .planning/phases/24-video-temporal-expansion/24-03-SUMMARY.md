# Phase 24 Plan 03: Transition Vocabulary and Temporal Movement Summary

---
phase: 24
plan: 03
subsystem: video-temporal
tags: [transition-vocabulary, camera-movement, temporal-prompts, psychology]
dependency-graph:
  requires: []
  provides: [TransitionVocabulary, buildTemporalMovementPrompt, getRecommendedDuration]
  affects: [24-04 integration]
tech-stack:
  added: []
  patterns: [vocabulary-constants, parameter-substitution, duration-clamping]
key-files:
  created:
    - modules/AppVideoWizard/app/Services/TransitionVocabulary.php
    - tests/Unit/VideoWizard/TransitionVocabularyTest.php
    - tests/Unit/VideoWizard/CameraMovementServiceTemporalTest.php
  modified:
    - modules/AppVideoWizard/app/Services/CameraMovementService.php
decisions:
  - transition-vocabulary-editorial: "Transition vocabulary describes shot endings, NOT post-production dissolves/wipes"
  - mood-to-transition-mapping: "Energetic moods -> match_cut, tense -> hard_cut, contemplative -> soft_transition"
  - duration-clamping: "Duration clamped to typical_duration_min/max from VwCameraMovement model"
  - psychology-append: "Psychological purpose appended to movement prompt with comma separator"
  - 80-percent-rule: "Movement duration max 80% of clip duration"
metrics:
  duration: 8m
  completed: 2026-01-27
---

**One-liner:** TransitionVocabulary for shot endings with match/hard/soft cut setups, plus CameraMovementService temporal prompts with duration and psychology.

## What Was Built

### Task 1: TransitionVocabulary Service
Created new service for describing how shots should end to motivate editorial cuts.

**Constants defined:**
- `TRANSITION_MOTIVATIONS`: 3 transition types (match_cut_setup, hard_cut_setup, soft_transition_setup) with variants
- `SHOT_ENDING_STATES`: 6 ending states with parameter templates
- `NEXT_SHOT_SUGGESTIONS`: Editorial guidance for each ending state

**Methods implemented:**
- `buildTransitionSetup(type, variant, params)`: Returns ending state description
- `suggestTransitionForMood(mood)`: Maps mood to transition type
- `getNextShotSuggestion(endingState)`: Returns editorial guidance
- `buildEndingStateDescription(state, params)`: Fills parameters into ending state template

**Example output:**
- `buildTransitionSetup('match_cut_setup', 'motion')` -> "ends mid-movement, action continues in next shot"
- `suggestTransitionForMood('energetic')` -> "match_cut_setup"
- `getNextShotSuggestion('look_direction')` -> "Cut to: POV of what character sees, or reaction shot"

### Task 2: CameraMovementService Temporal Extension
Extended existing service with duration and psychological purpose.

**Constants added:**
- `MOVEMENT_PSYCHOLOGY`: 10 psychological purposes (intimacy, tension, reveal, isolation, power, vulnerability, urgency, contemplation, discovery, departure)

**Methods added:**
- `buildTemporalMovementPrompt(slug, duration, psychology, intensity)`: Builds prompt with duration and psychology
- `getRecommendedDuration(slug, clipDuration)`: Calculates optimal duration respecting 80% rule
- `getAvailablePsychology()`: Returns list of psychology keys
- `getPsychologyDescription(key)`: Returns psychology description

**Example output:**
- `buildTemporalMovementPrompt('dolly-in', 4, 'intimacy')` -> "camera over 4 seconds dollies in toward subject, closing distance as emotional connection deepens"
- `buildTemporalMovementPrompt('crane-up', 6, 'power', 'intense')` -> "camera dramatically over 6 seconds cranes up revealing scene from above, asserting dominance through elevated perspective"

## Commits

| Hash | Type | Description |
|------|------|-------------|
| 53ee2e5 | feat | Create TransitionVocabulary for shot ending states |
| 98725e3 | feat | Extend CameraMovementService with temporal prompt building |

## Tests Added

**TransitionVocabularyTest.php (25+ tests):**
- Constant structure tests (TRANSITION_MOTIVATIONS, SHOT_ENDING_STATES, NEXT_SHOT_SUGGESTIONS)
- buildTransitionSetup tests for all transition types
- suggestTransitionForMood tests for mood mapping
- getNextShotSuggestion tests for editorial guidance
- buildEndingStateDescription tests with parameter substitution
- Helper method tests

**CameraMovementServiceTemporalTest.php (25+ tests):**
- MOVEMENT_PSYCHOLOGY constant structure tests
- buildTemporalMovementPrompt duration inclusion tests
- buildTemporalMovementPrompt psychology inclusion tests
- Duration clamping to typical_duration_min/max tests
- getRecommendedDuration 80% rule tests
- Intensity modifier tests
- Helper method tests

## Decisions Made

1. **Transition vocabulary is editorial, not post-production**: Describes how shots END to motivate cuts, not dissolves/wipes
2. **Mood mapping strategy**: Energetic moods -> match cuts (dynamic flow), tense moods -> hard cuts (impact), contemplative moods -> soft transitions (breathing room)
3. **Duration clamping**: Uses VwCameraMovement.typical_duration_min/max to keep duration realistic for movement type
4. **Psychology append pattern**: Psychology phrases appended with comma separator for natural reading
5. **80% rule for duration**: Movement shouldn't occupy more than 80% of clip to allow for transition breathing room

## Deviations from Plan

None - plan executed exactly as written.

## Verification

All verification criteria met:
- [x] TransitionVocabulary.php exists with TRANSITION_MOTIVATIONS, SHOT_ENDING_STATES, NEXT_SHOT_SUGGESTIONS
- [x] CameraMovementService.php has new MOVEMENT_PSYCHOLOGY constant and buildTemporalMovementPrompt method
- [x] Existing CameraMovementService methods untouched (buildMovementPrompt still works)
- [x] New tests verify temporal features without modifying existing tests
- [x] Movement prompts include duration ("over 4 seconds") and psychology ("closing distance as emotional connection deepens")

## Success Criteria Met

- [x] TransitionVocabulary.buildTransitionSetup produces ending state descriptions like "ends mid-movement, action continues in next shot"
- [x] TransitionVocabulary.getNextShotSuggestion provides editorial guidance
- [x] CameraMovementService.buildTemporalMovementPrompt produces prompts like "camera dollies in over 4 seconds, closing distance as emotional connection deepens"
- [x] Duration is clamped to typical_duration_min/max from VwCameraMovement model
- [x] All existing CameraMovementService tests unmodified
- [x] All new tests added

## Next Phase Readiness

Ready for 24-04 integration. This plan provides:
- TransitionVocabulary for VID-07 (transition suggestions)
- buildTemporalMovementPrompt for VID-03 (camera movement with duration and psychology)
- getRecommendedDuration for automatic duration calculation

No blockers or concerns for integration.
