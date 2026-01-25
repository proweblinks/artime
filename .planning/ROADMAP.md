# Video Wizard Development Roadmap

## Milestone 10: Livewire Performance Architecture

**Target:** Transform the Video Wizard from a monolithic 31k-line component into a performant, maintainable architecture with sub-second interactions
**Status:** In Progress (2026-01-25)
**Total requirements:** 8 (3 categories)
**Phases:** 19-21 (continues from M9)

---

## Overview

Livewire Performance Architecture addresses critical performance issues identified through debug analysis. The current implementation has a 31,489-line monolithic component with 500KB-2MB payloads per request, base64 images in component state, and 154+ wire:model.live bindings causing constant syncs.

This milestone applies a three-phase optimization strategy: Quick Wins (Livewire 3 attributes, debounced bindings, storage migration), Component Splitting (child components per wizard step and modal), and Data Normalization (database models replacing nested arrays).

**Target Metrics:**
- Payload size: <50KB per request (from 500KB-2MB)
- Interaction latency: <500ms (from 2-5 seconds)
- Component lines: <2,000 per component (from 31,489)

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 19 | Quick Wins | Reduce payload and latency with minimal architectural changes | PERF-01, PERF-02, PERF-03, PERF-08 | 4 |
| 20 | Component Splitting | Isolate wizard steps into independent, focused components | PERF-04, PERF-05 | 4 |
| 21 | Data Normalization | Replace nested arrays with database-backed models | PERF-06, PERF-07 | 3 |

**Total:** 3 phases | 8 requirements | 11 success criteria

---

## Phase 19: Quick Wins

**Goal:** Reduce payload size and interaction latency with minimal architectural changes

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 19-01-PLAN.md — TBD

**Dependencies:** None (starts new milestone)

**Requirements:**
- PERF-01: Livewire 3 attributes — #[Locked] for constants, #[Computed] for derived values
- PERF-02: Debounced bindings — wire:model.blur and .debounce instead of .live for text inputs
- PERF-03: Base64 storage migration — images stored in files, lazy-loaded only for API calls
- PERF-08: Updated hook optimization — efficient property change handling

**Success Criteria:**
1. Properties marked with #[Locked] do not serialize on every request (wizard constants, step configs)
2. Derived values use #[Computed] with caching — no recalculation unless dependencies change
3. Text inputs use wire:model.blur or wire:model.debounce — no full sync on every keystroke
4. Base64 images stored in files, not component state — images only loaded when needed for API calls

**Key changes:**
- Add #[Locked] attribute to read-only properties (step definitions, configs)
- Add #[Computed] attribute to derived values (scene counts, progress calculations)
- Change wire:model.live to wire:model.blur/.debounce in Blade templates
- Move referenceImageBase64 from component state to file storage with lazy loading
- Refactor updated() hook for efficient property change handling

---

## Phase 20: Component Splitting

**Goal:** Isolate wizard steps into independent, focused components

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 20-01-PLAN.md — TBD

**Dependencies:** Phase 19 (optimizations applied before splitting)

**Requirements:**
- PERF-04: Child components — separate Livewire components per wizard step
- PERF-05: Modal components — separate components for Character Bible, Location Bible, Shot Preview

**Success Criteria:**
1. Each wizard step (Concept, Characters, Script, Storyboard, Animation, Audio, Export) is a separate Livewire component
2. Step transitions emit events — parent orchestrates navigation, children own step data
3. Modal components (Character Bible, Location Bible, Shot Preview) are standalone — open/close without main component re-render
4. Each child component is under 2,000 lines — focused, single-responsibility

**Key changes:**
- Extract ConceptStep, CharactersStep, ScriptStep, etc. as child components
- Create CharacterBibleModal, LocationBibleModal, ShotPreviewModal components
- Implement parent-child event communication for step navigation
- Move step-specific properties from main component to respective children

---

## Phase 21: Data Normalization

**Goal:** Replace nested arrays with database-backed models for scalable data management

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 21-01-PLAN.md — TBD

**Dependencies:** Phase 20 (components need clear data boundaries before normalization)

**Requirements:**
- PERF-06: Database models — WizardScene, WizardShot models instead of nested arrays
- PERF-07: Lazy loading — scene data loaded on-demand, not all at once

**Success Criteria:**
1. WizardScene and WizardShot Eloquent models exist with proper relationships
2. Scene and shot data persisted to database — not serialized in component state
3. Active scene data loaded on-demand — only current scene's shots in memory at any time

**Key changes:**
- Create WizardScene model (belongs to Project, has many shots)
- Create WizardShot model (belongs to Scene, stores shot data)
- Migrate script.scenes, storyboard.scenes, multiShotMode.decomposedScenes to database
- Implement lazy loading — load scene data when navigating to scene
- Update component to query models instead of accessing nested arrays

---

## Dependencies

```
Phase 19 (Quick Wins)
    |
Phase 20 (Component Splitting) <- depends on optimizations first
    |
Phase 21 (Data Normalization) <- depends on clear component boundaries
```

Sequential execution required. Each phase builds on the previous.

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 19: Quick Wins | Not started | PERF-01, PERF-02, PERF-03, PERF-08 (4) | 0/4 |
| Phase 20: Component Splitting | Not started | PERF-04, PERF-05 (2) | 0/4 |
| Phase 21: Data Normalization | Not started | PERF-06, PERF-07 (2) | 0/3 |

**Overall Progress:**

```
Phase 19: ░░░░░░░░░░ 0%
Phase 20: ░░░░░░░░░░ 0%
Phase 21: ░░░░░░░░░░ 0%
─────────────────────
Overall:  ░░░░░░░░░░ 0% (0/8 requirements)
```

**Coverage:** 8/8 requirements mapped (100%)

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing wizard functionality | HIGH | Incremental changes, test each step before proceeding |
| Component communication complexity | MEDIUM | Keep parent simple, children own their state |
| Database migration for active projects | MEDIUM | Migration script with rollback, test on copies |
| Livewire 3 attribute edge cases | LOW | Test attributes on small subset first |

---

## Verification Strategy

After each phase:
1. Measure payload size (target: <50KB by end of Phase 19, maintain through 20-21)
2. Measure interaction latency (target: <500ms)
3. Run through complete wizard flow (all 7 steps)
4. Test with project containing 45+ scenes (stress test)
5. Verify no regression in video generation quality

---

## Previous Milestone (Complete)

### Milestone 9: Voice Production Excellence - COMPLETE

**Status:** 100% complete (6/6 requirements)
**Phases:** 15-18

| Phase | Status |
|-------|--------|
| Phase 15: Critical Fixes | Complete |
| Phase 16: Consistency Layer | Complete |
| Phase 17: Voice Registry | Complete |
| Phase 18: Multi-Speaker Support | Complete |

**Key achievements:**
- Narrator voice assigned to shots (narratorVoiceId flows through overlayNarratorSegments)
- Empty text validation before TTS (invalid segments caught early)
- Unified distribution strategy (narrator and internal thoughts use same word-split)
- Voice continuity validation (same character maintains same voice)
- Voice Registry centralization (single source of truth for all voices)
- Multi-speaker shot support (multiple speakers tracked per shot)

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

Livewire Performance Architecture ensures the wizard responds instantly to user input. Sub-second interactions mean users focus on creative decisions, not waiting for the UI. Maintainable architecture enables continued feature development without drowning in technical debt.

---

*Milestone 10 roadmap created: 2026-01-25*
*Phases 19-21 defined*
*Source: Debug analysis .planning/debug/livewire-performance.md*
