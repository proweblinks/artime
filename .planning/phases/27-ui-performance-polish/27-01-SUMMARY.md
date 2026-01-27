---
phase: 27-ui-performance-polish
plan: 01
subsystem: api
tags: [caching, llm, prompt-generation, laravel-cache, vw-settings]

# Dependency graph
requires:
  - phase: 26-llm-powered-expansion
    provides: LLM expansion service and buildHollywoodPrompt() method
provides:
  - Prompt caching layer for LLM-expanded prompts
  - Global toggle setting for Hollywood expansion
  - Cache key generation based on shot context
affects: [27-02, 27-03, prompt-performance]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Cache-aside pattern for LLM prompts
    - Global feature toggle via VwSetting

key-files:
  created:
    - modules/AppVideoWizard/database/migrations/2026_01_27_000001_add_hollywood_expansion_setting.php
  modified:
    - modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php
    - modules/AppVideoWizard/database/seeders/VwSettingSeeder.php

key-decisions:
  - "24-hour cache TTL for LLM-expanded prompts"
  - "Cache key uses MD5 hash of shot data + Bible context"
  - "Template-only results NOT cached (fast enough already)"
  - "Toggle defaults to true (expansion enabled)"

patterns-established:
  - "Cache-aside pattern: check cache before expensive LLM calls"
  - "Feature toggle via VwSetting for LLM features"

# Metrics
duration: 3min
completed: 2026-01-27
---

# Phase 27 Plan 01: Prompt Caching & Expansion Toggle Summary

**Cache-aside pattern for LLM-expanded prompts with 24-hour TTL and global VwSetting toggle**

## Performance

- **Duration:** 3 min
- **Started:** 2026-01-27T13:16:00Z
- **Completed:** 2026-01-27T13:18:56Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Added prompt caching layer to buildHollywoodPrompt() with Cache::get/put
- Created generatePromptCacheKey() helper using MD5 of shot context data
- Added hollywood_expansion_enabled VwSetting toggle in production_intelligence category
- Toggle check at start of shouldUseLLMExpansion() for fast bypass

## Task Commits

Each task was committed atomically:

1. **Task 1: Add caching to buildHollywoodPrompt()** - `1c59fca` (feat)
2. **Task 2: Add expansion toggle setting and check** - `4443ff0` (feat)

## Files Created/Modified
- `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php` - Added Cache import, cache lookup/store in buildHollywoodPrompt(), generatePromptCacheKey() helper, VwSetting import and toggle check
- `modules/AppVideoWizard/database/migrations/2026_01_27_000001_add_hollywood_expansion_setting.php` - Migration to create hollywood_expansion_enabled setting
- `modules/AppVideoWizard/database/seeders/VwSettingSeeder.php` - Added hollywood_expansion_enabled to production_intelligence category

## Decisions Made
- **24-hour cache TTL:** Balance between cache freshness and LLM cost savings
- **MD5 hash for cache key:** Fast, deterministic, includes all shot-affecting data
- **Template results not cached:** Template path is fast enough (~ms), caching adds overhead without benefit
- **Toggle defaults to true:** Backward compatible - existing behavior preserved unless explicitly disabled

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Caching layer ready for performance improvements
- Toggle setting available in admin UI for user control
- Ready for Plan 02: Skeleton loading states and progressive enhancement

---
*Phase: 27-ui-performance-polish*
*Plan: 01*
*Completed: 2026-01-27*
