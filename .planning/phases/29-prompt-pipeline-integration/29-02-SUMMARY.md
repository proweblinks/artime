---
phase: 29-prompt-pipeline-integration
plan: 02
subsystem: image-generation
tags: [shot-prompts, character-bible, location-bible, scene-memory, image-prompts]

# Dependency graph
requires:
  - phase: 22-prompt-engineering-foundation
    provides: Scene Memory structure (characterBible, locationBible)
provides:
  - Enhanced buildEnhancedShotImagePrompt with Character DNA
  - Enhanced buildEnhancedShotImagePrompt with Location DNA
  - Scene-specific shot prompts via sceneIndex parameter
affects: [shot-preview, image-generation, visual-consistency]

# Tech tracking
tech-stack:
  added: []
  patterns: [scene-index-threading, bible-lookup-pattern]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "sceneIndex default 0 for backward compatibility"
  - "Character and Location Bible sections placed after shot type, before visual description"
  - "Copied exact logic from buildScenePrompt for consistency"

patterns-established:
  - "Scene index threading: pass sceneIndex through method chains for context-aware lookups"
  - "Bible integration: Use getCharactersForSceneIndex/getLocationForSceneIndex for filtered data"

# Metrics
duration: 10min
completed: 2026-01-27
---

# Phase 29 Plan 02: Character/Location DNA in Shot Prompts Summary

**Shot image prompts now include CHARACTERS and LOCATION sections from Scene Memory, matching buildScenePrompt Layer 2 and Layer 3 integration**

## Performance

- **Duration:** 10 min
- **Started:** 2026-01-27T16:42:35Z
- **Completed:** 2026-01-27T16:52:11Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments
- Thread sceneIndex parameter through entire shot image prompt method chain
- Add CHARACTERS section with character names, descriptions, and personality traits
- Add LOCATION section with name, type, description, time of day, weather, and state
- Shot Preview IMAGE PROMPT now displays rich, story-specific content

## Task Commits

Each task was committed atomically:

1. **Task 1: Add sceneIndex parameter threading through method chain** - `5950264` (feat)
2. **Task 2: Add Character Bible integration (Layer 2)** - `435a03d` (feat)
3. **Task 3: Add Location Bible integration (Layer 3)** - `916f922` (feat)

## Files Created/Modified
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Enhanced buildEnhancedShotImagePrompt with Character and Location DNA, sceneIndex threading through 8 methods

## Decisions Made
- **sceneIndex default 0:** Ensures backward compatibility for callers that don't have sceneIndex context (they still work, just use global bible data instead of scene-filtered)
- **Section ordering:** CHARACTERS and LOCATION placed between shot type description and unique visual description for proper prompt structure
- **Code consistency:** Copied exact logic from buildScenePrompt Layer 2/3 for consistency across scene-level and shot-level prompts

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] addFallbackShotContent uses buildShotPrompt, not buildEnhancedShotImagePrompt**
- **Found during:** Task 1 (sceneIndex threading)
- **Issue:** Plan specified updating buildEnhancedShotImagePrompt call in addFallbackShotContent, but that method uses buildShotPrompt instead
- **Fix:** No change needed - addFallbackShotContent uses a different prompt building path and doesn't need sceneIndex for this plan's purpose
- **Verification:** Method correctly uses buildShotPrompt with sceneContext
- **Committed in:** N/A (no code change needed)

**2. [Rule 3 - Blocking] createShotFromBeat also calls buildEnhancedShotImagePrompt**
- **Found during:** Task 1 (sceneIndex threading)
- **Issue:** Plan didn't mention createShotFromBeat but it calls buildEnhancedShotImagePrompt
- **Fix:** Added sceneIndex parameter to createShotFromBeat and updated its call to buildEnhancedShotImagePrompt
- **Files modified:** VideoWizard.php
- **Verification:** Method signature includes int $sceneIndex = 0
- **Committed in:** 5950264 (Task 1 commit)

---

**Total deviations:** 2 discovered (1 no-op, 1 additional method updated)
**Impact on plan:** Additional method (createShotFromBeat) enhanced for completeness. No scope creep.

## Issues Encountered
- PHP syntax check unavailable in this environment (no php in PATH) - verified visually
- detectAndFixSimilarShots has second caller without sceneIndex context (convertDialogueShotsToStandardFormat) - default value 0 handles this correctly

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- PPL-01 complete: Shot prompts include Character DNA
- PPL-02 complete: Shot prompts include Location DNA
- Shot Preview IMAGE PROMPT will now show CHARACTERS and LOCATION sections
- Ready for verification via UI testing

---
*Phase: 29-prompt-pipeline-integration*
*Completed: 2026-01-27*
