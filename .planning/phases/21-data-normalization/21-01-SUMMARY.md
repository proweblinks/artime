---
phase: 21-data-normalization
plan: 01
subsystem: database
tags: [eloquent, mysql, laravel-migrations, has-many, belongs-to, normalization]

# Dependency graph
requires:
  - phase: 20-component-splitting
    provides: Component architecture ready for lazy loading
provides:
  - wizard_scenes table schema with FK to wizard_projects
  - wizard_shots table schema with FK to wizard_scenes
  - wizard_speech_segments table schema with FK to wizard_scenes
  - WizardScene Eloquent model with project/shots/speechSegments relationships
  - WizardShot Eloquent model with scene relationship
  - WizardSpeechSegment Eloquent model with scene relationship
  - WizardProject.scenes() HasMany relationship
  - WizardProject.usesNormalizedData() detection helper
affects: [21-02, 21-03, lazy-loading, scene-card-components]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Normalized scene data in wizard_scenes table replaces script/storyboard/animation JSON arrays"
    - "Cascade delete FKs ensure referential integrity"
    - "usesNormalizedData() enables backward compatibility during transition"
    - "getSceneCount() checks normalized first, falls back to JSON"

key-files:
  created:
    - modules/AppVideoWizard/database/migrations/2026_01_27_100001_create_wizard_scenes_table.php
    - modules/AppVideoWizard/database/migrations/2026_01_27_100002_create_wizard_shots_table.php
    - modules/AppVideoWizard/database/migrations/2026_01_27_100003_create_wizard_speech_segments_table.php
    - modules/AppVideoWizard/app/Models/WizardScene.php
    - modules/AppVideoWizard/app/Models/WizardShot.php
    - modules/AppVideoWizard/app/Models/WizardSpeechSegment.php
  modified:
    - modules/AppVideoWizard/app/Models/WizardProject.php

key-decisions:
  - "Migration timestamps use 100001-100003 to avoid conflict with existing 000001 migration"
  - "scene_metadata JSON column for less-frequent fields (voiceover settings, character associations)"
  - "shot_metadata JSON column for speaking_characters array and additional shot-specific data"
  - "Kept JSON columns in wizard_projects for backward compatibility during transition"

patterns-established:
  - "usesNormalizedData() pattern for detecting normalized vs JSON data"
  - "getSceneCount() dual-mode pattern checking normalized first, fallback to JSON"
  - "HasMany relationships ordered by 'order' column for consistent scene/shot ordering"

# Metrics
duration: 5min
completed: 2026-01-27
---

# Phase 21 Plan 01: Database Schema and Models Summary

**WizardScene, WizardShot, WizardSpeechSegment Eloquent models with normalized database tables replacing JSON arrays for lazy loading support (PERF-06)**

## Performance

- **Duration:** 5 min
- **Started:** 2026-01-27T20:42:00Z
- **Completed:** 2026-01-27T20:47:20Z
- **Tasks:** 2
- **Files modified:** 7

## Accomplishments

- Created three database migrations for normalized scene/shot/speech data
- Created WizardScene model with BelongsTo (Project) and HasMany (Shots, SpeechSegments) relationships
- Created WizardShot model with BelongsTo (Scene) relationship
- Created WizardSpeechSegment model with BelongsTo (Scene) relationship
- Updated WizardProject with scenes() relationship and usesNormalizedData() helper
- Implemented dual-mode getSceneCount() for backward compatibility

## Task Commits

Each task was committed atomically:

1. **Task 1: Create database migrations** - `f973e93` (feat)
2. **Task 2: Create Eloquent models with relationships** - `0cbd005` (feat)

## Files Created/Modified

**Created:**
- `modules/AppVideoWizard/database/migrations/2026_01_27_100001_create_wizard_scenes_table.php` - wizard_scenes table schema (65 lines)
- `modules/AppVideoWizard/database/migrations/2026_01_27_100002_create_wizard_shots_table.php` - wizard_shots table schema (66 lines)
- `modules/AppVideoWizard/database/migrations/2026_01_27_100003_create_wizard_speech_segments_table.php` - wizard_speech_segments table schema (60 lines)
- `modules/AppVideoWizard/app/Models/WizardScene.php` - Scene model with relationships (115 lines)
- `modules/AppVideoWizard/app/Models/WizardShot.php` - Shot model with BelongsTo (91 lines)
- `modules/AppVideoWizard/app/Models/WizardSpeechSegment.php` - SpeechSegment model with BelongsTo (98 lines)

**Modified:**
- `modules/AppVideoWizard/app/Models/WizardProject.php` - Added scenes() relationship and usesNormalizedData() helper

## Decisions Made

1. **Migration timestamp offset:** Used 100001-100003 prefix to avoid conflict with existing 2026_01_27_000001 migration
2. **Metadata JSON columns:** Added scene_metadata and shot_metadata for less-frequent fields while keeping primary data in dedicated columns
3. **Backward compatibility:** Kept JSON columns in wizard_projects, added usesNormalizedData() detection
4. **Ordered relationships:** All HasMany relationships use orderBy('order') for consistent ordering

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all tasks completed successfully without issues.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

**Ready for Plan 02:**
- Database tables can be created with `php artisan migrate`
- Models ready for data migration command implementation
- usesNormalizedData() helper enables gradual rollout

**Prerequisites met:**
- wizard_scenes, wizard_shots, wizard_speech_segments tables defined
- Eloquent models with proper relationships
- WizardProject->scenes() relationship works
- Backward compatibility via dual-mode helpers

---
*Phase: 21-data-normalization*
*Plan: 01*
*Completed: 2026-01-27*
