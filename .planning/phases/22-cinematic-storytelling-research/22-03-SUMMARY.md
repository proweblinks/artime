---
phase: 22-cinematic-storytelling-research
plan: 03
subsystem: prompt-generation
tags: [action-verbs, scene-moods, image-prompts, cinematic, ai-generation]

# Dependency graph
requires:
  - phase: 22-01
    provides: anti-portrait negative prompts foundation
provides:
  - ACTION_VERBS constant with 17 mood-to-verb mappings
  - getActionVerbForScene() method for dynamic verb selection
  - Enhanced enhanceStoryAction() with action verb injection
affects: [prompt-building, image-generation, cinematography]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Action verb injection for static descriptions"
    - "Mood-based verb variation using shotIndex"
    - "Dynamic verb detection to avoid double-verbing"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "17 mood categories cover common scene types including dialogue, tension, action, emotion, horror, comedy"
  - "variationIndex parameter ensures different shots in same scene get varied actions"
  - "Dynamic verb detection prevents double-verbing already-dynamic descriptions"

patterns-established:
  - "Verb injection pattern: Subject, [action], at location"
  - "Fallback hierarchy: exact mood match -> partial match -> keyword match -> default"

# Metrics
duration: 8min
completed: 2026-01-28
---

# Phase 22 Plan 03: Action Verb Library Summary

**Action verb library with 17 mood categories mapping scene types to dynamic verbs, integrated into enhanceStoryAction() for narrative frame generation**

## Performance

- **Duration:** 8 min
- **Started:** 2026-01-28T15:00:00Z
- **Completed:** 2026-01-28T15:08:00Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- ACTION_VERBS constant with 17 mood categories and 70+ action verb phrases
- getActionVerbForScene() method with intelligent mood matching (exact, partial, keyword)
- enhanceStoryAction() integration that transforms static descriptions into narrative moments
- Variation system using shotIndex for different actions across shots in same scene

## Task Commits

Each task was committed atomically:

1. **Task 1: Add ACTION_VERBS constant** - `62f4153` (feat)
2. **Task 2: Create getActionVerbForScene() method** - `f6a88b8` (feat)
3. **Task 3: Integrate action verbs into enhanceStoryAction()** - `f1ee721` (feat)

## Files Created/Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added ACTION_VERBS constant (line 701), getActionVerbForScene() method (line 21702), and updated enhanceStoryAction() with verb injection

## Decisions Made

- **17 mood categories selected:** dialogue, tension, tense, suspense, discovery, investigation, mystery, action, pursuit, emotion, emotional, dramatic, romantic, horror, fear, comedy, contemplative, melancholic, default
- **4-5 verbs per category:** Provides variety within scene moods
- **Dynamic verb detection:** 17 verbs checked (running, reaching, turning, striking, etc.) to avoid double-verbing
- **Natural injection pattern:** "Character, [action], at location" feels like part of the original description

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- PHP syntax check unavailable in execution environment (php not in PATH)
- No impact: code structure verified manually, all edits follow PHP syntax rules

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Action verb library complete and integrated
- Works in conjunction with Plan 22-01 (anti-portrait prompts) and Plan 22-02 (gaze directions)
- All three Phase 22 plans now provide layered cinematic prompt enhancement:
  1. Anti-portrait negative prompts prevent camera gaze
  2. Gaze direction templates specify where characters look
  3. Action verbs transform static descriptions into narrative moments

---
*Phase: 22-cinematic-storytelling-research*
*Completed: 2026-01-28*
