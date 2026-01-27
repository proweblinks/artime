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
| 20 | Component Splitting | Extract wizard steps and modals into child components | PERF-04, PERF-05 | 4 |
| 21 | Data Normalization | Database models and lazy loading for scenes/shots | PERF-06, PERF-07 | 4 |

**Total:** 2 remaining phases | 4 requirements | 8 success criteria

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

**Goal:** Extract wizard steps and modals into separate Livewire components to reduce main component size

**Status:** Not started

**Plans:** TBD (planning needed)

**Dependencies:** Phase 19 complete

**Requirements:**
- PERF-04: Child components — separate Livewire components per wizard step
- PERF-05: Modal components — separate components for Character Bible, Location Bible, etc.

**Success Criteria** (what must be TRUE):
1. Each wizard step (Concept, Characters, Script, Storyboard, Animation, Audio, Export) has its own Livewire component
2. Character Bible modal is a separate Livewire component
3. Location Bible modal is a separate Livewire component
4. Parent VideoWizard component coordinates child components via events

**Architectural Considerations:**
- VideoWizard.php is ~31k lines — significant refactoring
- State sharing between parent/child components
- Event-based communication patterns
- Backward compatibility with existing functionality

---

## Phase 21: Data Normalization

**Goal:** Replace nested arrays with database models and implement lazy loading

**Status:** Not started

**Plans:** TBD (planning needed)

**Dependencies:** Phase 20 complete (components need stable data interface)

**Requirements:**
- PERF-06: Database models — WizardScene, WizardShot models instead of nested arrays
- PERF-07: Lazy loading — scene data loaded on-demand, not all at once

**Success Criteria** (what must be TRUE):
1. WizardScene model exists with proper relationships
2. WizardShot model exists with proper relationships
3. Scene data is loaded on-demand when scene is viewed
4. Shot data is loaded on-demand when shot is expanded

**Architectural Considerations:**
- Migration strategy for existing project data
- Relationships: Project → Scenes → Shots → SpeechSegments
- JSON fields vs. normalized columns
- Performance benchmarking before/after

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 19: Quick Wins | Complete | PERF-01, PERF-02, PERF-03, PERF-08 | 8/8 |
| Phase 20: Component Splitting | Not started | PERF-04, PERF-05 | 0/4 |
| Phase 21: Data Normalization | Not started | PERF-06, PERF-07 | 0/4 |

**Overall Progress:**

```
Phase 19:   ██████████ 100%
Phase 20:   ░░░░░░░░░░ 0%
Phase 21:   ░░░░░░░░░░ 0%
─────────────────────────
v10:        ████░░░░░░ 50% (4/8 requirements)
```

---

## Dependencies

```
Phase 19 (Quick Wins) [COMPLETE]
    |
    v
Phase 20 (Component Splitting)
    |
    +-- PERF-04: Wizard step components
    +-- PERF-05: Modal components
    |
    v
Phase 21 (Data Normalization)
    |
    +-- PERF-06: Database models
    +-- PERF-07: Lazy loading
```

---

*v10 resumed: 2026-01-27*
*Phase 19 context: .planning/phases/19-quick-wins/19-VERIFICATION.md*
