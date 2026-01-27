# Video Wizard - Current State

> Last Updated: 2026-01-28
> Session: Phase 22 Plan 03 Complete

---

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-27)

**Core value:** Automatic, effortless, Hollywood-quality output from button clicks
**Current focus:** v10 Livewire Performance (Phases 20-21) + Phase 22 Cinematic Storytelling

---

## Current Position

**Milestone:** v10 (Livewire Performance Architecture) — In Progress
**Phase:** 22 (Cinematic Storytelling Research) — Complete
**Plan:** 3 of 3 complete
**Status:** Phase 22 complete, all cinematic storytelling features implemented

```
Phase 19:   xxxxxxxxxx 100% (4/4 plans complete)
Phase 20:   xxxxxxxxxx 100% (3/3 plans complete)
Phase 21:   xx........ 20% (2/? plans complete)
Phase 22:   xxxxxxxxxx 100% (3/3 plans complete)
---------------------
v10:        xxxxxxxx.. 85% (PERF-06 complete, PERF-07 pending)
```

**Last activity:** 2026-01-28 - Completed 22-03-PLAN.md (action verb library)

---

## What Shipped (Phase 22 Complete)

**Plan 01 - Anti-Portrait Negative Prompts:**
- getAntiPortraitNegativePrompts() method with 14 anti-portrait terms
- buildNegativePrompt() helper combining user + anti-portrait prompts
- All 5 image generation call sites updated to use centralized method

**Plan 02 - Environmental Storytelling (Gaze Directions):**
- GAZE_TEMPLATES constant with shot-specific gaze directions
- getGazeDirectionForShot() method for explicit gaze control
- Integration into buildStoryVisualContent() prompt building

**Plan 03 - Dynamic Action Poses:**
- ACTION_VERBS constant with 17 mood categories and 70+ verb phrases
- getActionVerbForScene() method with intelligent mood matching
- enhanceStoryAction() integration for narrative frame generation

**Files modified:**
- modules/AppVideoWizard/app/Livewire/VideoWizard.php

**Key outcome:** Three-layer cinematic prompt enhancement preventing portrait-style AI generation:
1. Anti-portrait negative prompts prevent camera gaze
2. Gaze direction templates specify where characters look
3. Action verbs transform static descriptions into narrative moments

---

## Accumulated Context

### Key Decisions (v10 Phase 19-22)

| Date       | Plan  | Decision                                            |
|------------|-------|-----------------------------------------------------|
| 2026-01-25 | 19-01 | 8 properties marked #[Locked] for read-only state   |
| 2026-01-25 | 19-01 | 5 computed methods for derived counts/status        |
| 2026-01-25 | 19-02 | 58 wire:model.blur bindings on textareas            |
| 2026-01-25 | 19-02 | wire:model.live reduced from ~70 to 49              |
| 2026-01-25 | 19-03 | referenceImageStorageKey pattern for Base64 storage |
| 2026-01-25 | 19-03 | loadedBase64Cache as #[Locked] runtime cache        |
| 2026-01-25 | 19-04 | debouncedBuildSceneDNA with 2-second threshold      |
| 2026-01-27 | 20-01 | Helper methods shared across bibles stay in VideoWizard.php |
| 2026-01-27 | 20-01 | Traits access parent properties via $this->          |
| 2026-01-27 | 20-01 | Keep generateAllMissingReferences() in VideoWizard   |
| 2026-01-27 | 20-02 | Portrait generation stays in parent (complex service orchestration) |
| 2026-01-27 | 20-02 | Use event dispatch for heavy operations to maintain separation |
| 2026-01-27 | 20-03 | Reference generation stays in parent (needs ImageGenerationService) |
| 2026-01-27 | 20-03 | Child dispatches events, parent handles heavy operations |
| 2026-01-27 | 20-03 | Scene data passed as prop, not modelable             |
| 2026-01-27 | 21-01 | Migration timestamps 100001-100003 to avoid conflict with existing |
| 2026-01-27 | 21-01 | scene_metadata/shot_metadata JSON for less-frequent fields |
| 2026-01-27 | 21-01 | usesNormalizedData() pattern for backward compatibility |
| 2026-01-27 | 21-01 | getSceneCount() checks normalized first, falls back to JSON |
| 2026-01-27 | 21-02 | Transaction-wrapped migration with rollback on error |
| 2026-01-27 | 21-02 | Dry-run mode for safe preview of migrations         |
| 2026-01-27 | 21-02 | 5-minute cache on sceneIds() to reduce DB queries   |
| 2026-01-27 | 21-02 | normalizedSceneToArray() for backward compatibility |
| 2026-01-28 | 22-01 | Anti-portrait prompts always appended, never replace user prompts |
| 2026-01-28 | 22-01 | 14 anti-portrait terms from research document       |
| 2026-01-28 | 22-01 | Centralized buildNegativePrompt() for DRY principle |
| 2026-01-28 | 22-02 | GAZE_TEMPLATES defines gaze per shot type           |
| 2026-01-28 | 22-02 | Empty gaze for environment/POV shots (no subject)   |
| 2026-01-28 | 22-03 | 17 mood categories for action verb variation        |
| 2026-01-28 | 22-03 | variationIndex ensures different actions per shot   |
| 2026-01-28 | 22-03 | Dynamic verb detection prevents double-verbing      |

### Architecture Context

**VideoWizard.php stats (after Phase 22 Complete):**
- ~31,500 lines (added cinematic storytelling features)
- 7 wizard steps in single component
- Character/Location Bible methods now in traits
- Both CharacterBibleModal and LocationBibleModal extracted as child components
- Dual-mode data access: normalized tables + JSON fallback
- Three-layer cinematic prompt enhancement:
  - Anti-portrait negative prompts
  - Gaze direction templates
  - Action verb injection

**Phase 20 complete:**
- Plan 01: Bible trait extraction (DONE)
- Plan 02: Character Bible Modal extraction (DONE)
- Plan 03: Location Bible Modal extraction (DONE)

**Phase 21 progress:**
- Plan 01: Database schema and models (DONE)
- Plan 02: Data migration command and dual-mode access (DONE)
- PERF-06: WizardScene, WizardShot models + migration command complete
- PERF-07: Lazy loading pending (requires lazy-loaded scene card components)

**Phase 22 complete:**
- Plan 01: Anti-portrait negative prompts (DONE)
- Plan 02: Environmental storytelling / gaze directions (DONE)
- Plan 03: Dynamic action poses / verb library (DONE)

### Pending Todos

None.

### Blockers/Concerns

**Data migration testing:**
- Need to run wizard:normalize-data on test projects before production
- Consider --dry-run first to verify data mapping

**Backward compatibility confirmed:**
- JSON columns kept in wizard_projects
- usesNormalizedData() detects which mode to use
- VideoWizard methods transparently fall back to JSON

### Roadmap Evolution

- Phase 22 Cinematic Storytelling Research COMPLETE
  - Plan 01: Anti-portrait negative prompts (COMPLETE)
  - Plan 02: Environmental storytelling / gaze directions (COMPLETE)
  - Plan 03: Dynamic action poses / verb library (COMPLETE)

---

## Session Continuity

Last session: 2026-01-28
Stopped at: Completed 22-03-PLAN.md (action verb library)
Resume file: None
Next step: Phase 21 PERF-07 (lazy loading) or next milestone planning

---

## Archive Reference

Milestone artifacts archived to `.planning/milestones/`:
- v11-ROADMAP.md, v11-REQUIREMENTS.md, v11-MILESTONE-AUDIT.md, v11-INTEGRATION-CHECK.md
- M11.2-ROADMAP.md, M11.2-REQUIREMENTS.md, M11.2-AUDIT.md

Phase directories in `.planning/phases/`:
- 19-quick-wins/ (v10 Phase 19 - complete)
- 20-component-splitting/ (v10 Phase 20 - complete)
- 21-data-normalization/ (v10 Phase 21 - in progress)
- 22-cinematic-storytelling-research/ (Phase 22 - complete)
- 22-* through 29.1-* (v11, M11.1, M11.2)
