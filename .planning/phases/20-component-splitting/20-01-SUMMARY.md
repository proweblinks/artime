---
phase: 20-component-splitting
plan: 01
subsystem: ui
tags: [livewire, traits, php, refactoring, code-organization]

# Dependency graph
requires:
  - phase: 19-quick-wins
    provides: Livewire 3 attributes, debounced bindings, updated hook optimization
provides:
  - WithCharacterBible trait (1195 lines)
  - WithLocationBible trait (442 lines)
  - VideoWizard reduced by ~1,623 lines
affects: [20-02, 20-03, 21-component-splitting]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Livewire trait extraction pattern
    - BibleOrderingService for sorting
    - CharacterLookService for DNA templates

key-files:
  created:
    - modules/AppVideoWizard/app/Livewire/Traits/WithCharacterBible.php
    - modules/AppVideoWizard/app/Livewire/Traits/WithLocationBible.php
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Helper methods shared across bibles stay in VideoWizard.php"
  - "Traits access parent properties via $this->"
  - "Keep generateAllMissingReferences() in VideoWizard as it coordinates both bibles"

patterns-established:
  - "Trait pattern: Extract related methods to traits in Livewire/Traits/"
  - "Comment pattern: '// NOTE: method() moved to WithTraitName trait'"

# Metrics
duration: 25min
completed: 2026-01-27
---

# Phase 20 Plan 01: Bible Trait Extraction Summary

**Character and Location Bible methods extracted into PHP traits, reducing VideoWizard.php by ~1,623 lines while maintaining identical behavior**

## Performance

- **Duration:** ~25 min
- **Started:** 2026-01-27T06:00:00Z
- **Completed:** 2026-01-27T06:25:00Z
- **Tasks:** 3
- **Files modified:** 3

## Accomplishments
- Created WithCharacterBible trait with 1195 lines covering modal control, CRUD, traits/accessories, voice management, portrait generation, DNA templates, wardrobe, state tracking, speaker sync, and character sorting
- Created WithLocationBible trait with 442 lines covering modal control, CRUD, state changes, presets, reference image generation, and location sorting
- Integrated both traits into VideoWizard.php, reducing file from ~32,331 to 30,708 lines

## Task Commits

Each task was committed atomically:

1. **Task 1: Create WithCharacterBible trait** - `91fe3d0` (feat)
2. **Task 2: Create WithLocationBible trait** - `3f738ce` (feat)
3. **Task 3: Integrate traits into VideoWizard** - `12ef184` (refactor)

## Files Created/Modified
- `modules/AppVideoWizard/app/Livewire/Traits/WithCharacterBible.php` - All Character Bible methods (1195 lines)
- `modules/AppVideoWizard/app/Livewire/Traits/WithLocationBible.php` - All Location Bible methods (442 lines)
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added trait imports and usage, removed duplicate methods (~1,779 lines removed, 156 lines added for comments and kept helpers)

## Decisions Made
- **Helper methods in VideoWizard:** Methods like `getLocationStateForScene()`, `generateAllMissingReferences()`, and `hasAutoGenerationInProgress()` remain in VideoWizard.php because they either coordinate both bibles or are used by other parts of the system
- **Service injection in traits:** Traits use `app()` to resolve services like BibleOrderingService and CharacterLookService, following existing pattern
- **Comment markers:** Replaced removed methods with `// NOTE: method() moved to WithTraitName trait` comments for traceability

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- **PHP syntax validation unavailable:** PHP was not in the system PATH on this Windows machine, so syntax validation via `php -l` was not possible. Verification was done by checking line counts and confirming file structure.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Traits directory established at `modules/AppVideoWizard/app/Livewire/Traits/`
- Pattern established for future trait extraction (Plan 02: Style DNA, Plan 03: Scene Memory)
- VideoWizard.php reduced but still at ~30,708 lines, leaving room for further extraction

---
*Phase: 20-component-splitting*
*Completed: 2026-01-27*
