# v10 Livewire Performance Roadmap (Resumed)

## Overview

Resume v10 performance work after Phase 19 (Quick Wins) shipped. Phases 20-21 tackle architectural changes: component splitting and data normalization.

**Target:** Reduce VideoWizard.php from ~31k lines, improve Livewire serialization performance
**Status:** In Progress (resumed 2026-01-27)
**Total requirements:** 4 (PERF-04 through PERF-07)
**Phases:** 20, 21

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 19 | Quick Wins | Low-risk performance improvements | PERF-01, PERF-02, PERF-03, PERF-08 | Complete |
| 20 | Component Splitting | PHP traits and modal child components | PERF-05 (partial PERF-04) | 4 |
| 21 | Data Normalization | Database models and lazy loading for scenes/shots | PERF-06, PERF-07 | 4 |
| 22 | Cinematic Storytelling Research | Fix prompt pipeline for Hollywood-quality frames | QUAL-01 | 4 |

**Total:** 3 remaining phases | 5 requirements | 12 success criteria

---

## Phase 19: Quick Wins (Complete)

**Goal:** Low-risk Livewire 3 optimizations without architectural changes

**Status:** Complete (2026-01-25)

**Plans:** 4 plans

Plans:
- [x] 19-01-PLAN.md — Livewire 3 attributes (#[Locked], #[Computed])
- [x] 19-02-PLAN.md — Debounced bindings (wire:model.blur)
- [x] 19-03-PLAN.md — Base64 storage migration (ReferenceImageStorageService)
- [x] 19-04-PLAN.md — Updated hook optimization

**Requirements Completed:**
- PERF-01: Livewire 3 attributes
- PERF-02: Debounced bindings
- PERF-03: Base64 storage migration
- PERF-08: Updated hook optimization

---

## Phase 20: Component Splitting

**Goal:** Organize VideoWizard code with PHP traits and extract Bible modals into Livewire child components

**Status:** Complete (2026-01-27)

**Plans:** 3 plans

Plans:
- [x] 20-01-PLAN.md — PHP traits for code organization (WithCharacterBible, WithLocationBible)
- [x] 20-02-PLAN.md — CharacterBibleModal child component extraction
- [x] 20-03-PLAN.md — LocationBibleModal child component extraction

**Dependencies:** Phase 19 complete

**Requirements:**
- PERF-04 (partial): PHP traits for code organization — step components deferred to future phase
- PERF-05: Modal components — Character Bible and Location Bible as child components

**Success Criteria** (what must be TRUE):
1. Character Bible methods organized in WithCharacterBible trait
2. Location Bible methods organized in WithLocationBible trait
3. Character Bible modal is a separate Livewire child component
4. Location Bible modal is a separate Livewire child component

**Scope Adjustment (from research):**
- Wizard step extraction (PERF-04 full) deferred — HIGH RISK due to deep state interdependencies
- Focus on LOW/MEDIUM risk: traits (zero-risk code organization) + modal components (isolated state)
- Full step extraction recommended for Phase 22+ after data normalization stabilizes

**Architectural Pattern:**
- Traits: Code organization without state changes
- Modal components: Event-based parent-child communication
- Parent dispatches events, child responds
- Child updates via #[Modelable] wire:model binding

---

## Phase 21: Data Normalization

**Goal:** Replace nested arrays with database models and implement lazy loading

**Status:** Planned (2026-01-27)

**Plans:** 3 plans

Plans:
- [ ] 21-01-PLAN.md — Database schema and Eloquent models (WizardScene, WizardShot, WizardSpeechSegment)
- [ ] 21-02-PLAN.md — Data migration command and VideoWizard dual-mode support
- [ ] 21-03-PLAN.md — Lazy-loaded SceneCard component for on-demand loading

**Dependencies:** Phase 20 complete (components need stable data interface)

**Requirements:**
- PERF-06: Database models — WizardScene, WizardShot models instead of nested arrays
- PERF-07: Lazy loading — scene data loaded on-demand, not all at once

**Success Criteria** (what must be TRUE):
1. WizardScene model exists with proper relationships
2. WizardShot model exists with proper relationships
3. Scene data is loaded on-demand when scene is viewed
4. Shot data is loaded on-demand when shot is expanded

**Wave Structure:**
- Wave 1: Plan 01 (database foundation)
- Wave 2: Plan 02 (migration infrastructure, depends on 01)
- Wave 3: Plan 03 (lazy loading UI, depends on 02)

**Architectural Pattern:**
- Normalized tables with foreign keys (cascade delete)
- Dual-mode support: normalized tables preferred, JSON fallback for non-migrated
- Livewire #[Lazy] components for viewport-based loading
- #[Computed] properties to avoid serializing scene arrays

---

## Phase 22: Cinematic Storytelling Research

**Goal:** Implement prompt pipeline fixes for Hollywood-quality cinematic frames based on research findings

**Status:** Planned (2026-01-28)

**Plans:** 3 plans

Plans:
- [ ] 22-01-PLAN.md — Anti-portrait negative prompts (foundation for all shots)
- [ ] 22-02-PLAN.md — Shot-type-specific gaze direction templates
- [ ] 22-03-PLAN.md — Action verb library and scene-type integration

**Dependencies:** None (independent of Phase 21)

**Requirements:**
- QUAL-01: Cinematic prompt pipeline — Prompts produce storytelling frames, not portraits

**Research Complete (22-RESEARCH.md):**
1. Root causes identified: Training data bias, "looking at viewer" tag frequency, static prompts
2. Core insight: Verbs create narrative; adjectives create portraits
3. Anti-portrait negative prompts documented
4. Shot-type-specific gaze templates defined
5. Action verb library by scene type created

**Success Criteria** (what must be TRUE):
1. Anti-portrait negative prompts applied to ALL shot generation
2. Each shot type has appropriate gaze direction template
3. Action verbs replace static descriptions in prompts
4. Generated images show characters engaged with scene, not camera

**Wave Structure:**
- Wave 1: Plan 01 (anti-portrait negatives - foundation)
- Wave 2: Plans 02, 03 (gaze templates + action verbs - parallel, both depend on 01)

**Implementation Pattern:**
- Add getAntiPortraitNegativePrompts() + buildNegativePrompt() helper
- Add GAZE_TEMPLATES constant + getGazeDirectionForShot() method
- Add ACTION_VERBS constant + getActionVerbForScene() method
- Integrate into buildShotPrompt() and enhanceStoryAction()

---

## Future: Phase 23+ (Deferred)

**Goal:** Extract wizard steps into child components (PERF-04 full)

**Why deferred:**
1. Deep state interdependencies ($script, $storyboard, $sceneMemory all interconnected)
2. Complex service orchestration spans multiple steps
3. Progressive generation state machine spans steps
4. Phase 21 data normalization will simplify step extraction
5. Research rated wizard step extraction as HIGH RISK

**Prerequisites:**
- Phase 20 complete (traits + modal components establish patterns)
- Phase 21 complete (data normalization reduces state coupling)
- Phase 22 complete (cinematic quality research informs prompt changes)

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 19: Quick Wins | Complete | PERF-01, PERF-02, PERF-03, PERF-08 | 8/8 |
| Phase 20: Component Splitting | Complete | PERF-05, PERF-04 (partial) | 4/4 |
| Phase 21: Data Normalization | Planned | PERF-06, PERF-07 | 0/4 |
| Phase 22: Cinematic Storytelling | Planned | QUAL-01 | 0/4 |

**Overall Progress:**

```
Phase 19:   ██████████ 100%
Phase 20:   ██████████ 100%
Phase 21:   ░░░░░░░░░░ 0%
Phase 22:   ░░░░░░░░░░ 0%
─────────────────────────
v10:        ███████░░░ 75% (6/8 requirements)
```

---

## Dependencies

```
Phase 19 (Quick Wins) [COMPLETE]
    |
    v
Phase 20 (Component Splitting) [COMPLETE]
    |
    +-- Plan 01: PHP Traits (LOW RISK)
    +-- Plan 02: CharacterBibleModal (MEDIUM RISK)
    +-- Plan 03: LocationBibleModal (MEDIUM RISK)
    |
    v
Phase 21 (Data Normalization) [PLANNED]
    |
    +-- Wave 1: Plan 01 - Database models (MEDIUM RISK)
    +-- Wave 2: Plan 02 - Migration command (MEDIUM RISK)
    +-- Wave 3: Plan 03 - Lazy SceneCard (MEDIUM RISK)

Phase 22 (Cinematic Storytelling) [PLANNED] -- Independent track
    |
    +-- Wave 1: Plan 01 - Anti-portrait negatives (LOW RISK)
    +-- Wave 2: Plan 02 - Gaze templates (LOW RISK)
    +-- Wave 2: Plan 03 - Action verbs (LOW RISK)
    |
    v
Phase 23+ (Deferred)
    |
    +-- PERF-04 full: Wizard step components
```

---

*v10 resumed: 2026-01-27*
*Phase 20 completed: 2026-01-27*
*Phase 21 planned: 2026-01-27*
*Phase 22 planned: 2026-01-28*
*Phase 19 context: .planning/phases/19-quick-wins/19-VERIFICATION.md*
