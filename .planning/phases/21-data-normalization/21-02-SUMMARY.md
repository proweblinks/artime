---
phase: 21-data-normalization
plan: 02
subsystem: database
tags: [eloquent, artisan, livewire, json-migration, normalized-tables]

# Dependency graph
requires:
  - phase: 21-01
    provides: WizardScene, WizardShot, WizardSpeechSegment models and database migrations
provides:
  - wizard:normalize-data artisan command for JSON-to-table migration
  - VideoWizard dual-mode data access (normalized + JSON fallback)
  - sceneIds() computed property with cache
  - getSceneData() method with automatic source detection
affects: [21-03, 21-04, perf-07, lazy-loading]

# Tech tracking
tech-stack:
  added: []
  patterns: [artisan-command-migration, dual-mode-data-access, computed-property-cache]

key-files:
  created:
    - modules/AppVideoWizard/app/Console/Commands/NormalizeProjectData.php
  modified:
    - modules/AppVideoWizard/app/Providers/AppVideoWizardServiceProvider.php
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Transaction-wrapped migration with rollback on error"
  - "Dry-run mode for safe preview of migrations"
  - "5-minute cache on sceneIds() to reduce DB queries"
  - "normalizedSceneToArray() transforms models to legacy JSON format for backward compatibility"

patterns-established:
  - "Artisan command pattern: wizard:* namespace for AppVideoWizard commands"
  - "Dual-mode access: Check usesNormalizedData() before querying, fallback to JSON"
  - "Computed property with persist: true for expensive queries"

# Metrics
duration: 12min
completed: 2026-01-27
---

# Phase 21 Plan 02: Data Migration Command and Dual-Mode Access Summary

**wizard:normalize-data artisan command for JSON migration and VideoWizard updated for normalized table access with JSON fallback**

## Performance

- **Duration:** 12 min
- **Started:** 2026-01-27T15:13:30Z
- **Completed:** 2026-01-27T15:25:30Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Created wizard:normalize-data artisan command with --project, --dry-run, --force options
- Transaction-wrapped migration ensures atomic data transfer with rollback on error
- VideoWizard now supports both JSON arrays and normalized tables transparently
- Backward compatibility maintained - existing $script/$storyboard properties unchanged

## Task Commits

Each task was committed atomically:

1. **Task 1: Create data migration artisan command** - `06f4e9d` (feat)
2. **Task 2: Update VideoWizard for dual-mode data access** - `44cdd2b` (feat)

## Files Created/Modified
- `modules/AppVideoWizard/app/Console/Commands/NormalizeProjectData.php` - Artisan command for JSON-to-table migration (312 lines)
- `modules/AppVideoWizard/app/Providers/AppVideoWizardServiceProvider.php` - Command registration
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added 5 methods for normalized data access

## Decisions Made
- **Transaction wrapping:** Entire project migration wrapped in DB::transaction() to ensure atomicity
- **Dry-run mode:** --dry-run flag simulates migration without database changes for safe preview
- **Force mode:** --force flag allows re-migration of already-normalized projects
- **5-minute cache:** sceneIds() uses `#[Computed(persist: true, seconds: 300)]` to reduce repeated queries
- **Model-to-array transform:** normalizedSceneToArray() converts WizardScene models to legacy JSON format for existing Blade templates

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- PHP artisan commands cannot be verified in the terminal environment (no PHP in PATH), but the command file was verified via grep to contain correct signature and registration

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Migration command ready for testing on actual project data
- VideoWizard can now read from normalized tables when available
- Ready for Plan 03: Lazy-loaded scene card components
- Consider running migration on development data before production rollout

---
*Phase: 21-data-normalization*
*Completed: 2026-01-27*
