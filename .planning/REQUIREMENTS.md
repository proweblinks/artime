# Requirements: Video Wizard - Livewire Performance Architecture

**Defined:** 2026-01-25
**Core Value:** Automatic, effortless, Hollywood-quality output from button clicks

## v10 Requirements

Requirements for Milestone 10. Each maps to roadmap phases.

### Quick Wins (P0)

- [ ] **PERF-01**: Livewire 3 attributes — #[Locked] for constants, #[Computed] for derived values
- [ ] **PERF-02**: Debounced bindings — wire:model.blur and .debounce instead of .live for text inputs
- [ ] **PERF-03**: Base64 storage migration — images stored in files, lazy-loaded only for API calls
- [ ] **PERF-08**: Updated hook optimization — efficient property change handling

### Component Architecture (P1)

- [ ] **PERF-04**: Child components — separate Livewire components per wizard step
- [ ] **PERF-05**: Modal components — separate components for Character Bible, Location Bible, Shot Preview

### Data Normalization (P2)

- [ ] **PERF-06**: Database models — WizardScene, WizardShot models instead of nested arrays
- [ ] **PERF-07**: Lazy loading — scene data loaded on-demand, not all at once

## Previous Milestones (Complete)

### Milestone 9: Voice Production Excellence

- [x] **VOC-01**: Narrator voice assigned to shots
- [x] **VOC-02**: Empty text validation before TTS
- [x] **VOC-03**: Unified distribution strategy
- [x] **VOC-04**: Voice continuity validation
- [x] **VOC-05**: Voice Registry centralization
- [x] **VOC-06**: Multi-speaker shot support

## Future Requirements

Deferred to later milestones.

### SSML Integration (P3)

- **SSML-01**: SSML-style speech markup for better TTS control
- **SSML-02**: Voice switching with speaker tags
- **SSML-03**: Style control with express-as elements for emotional consistency

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Complete rewrite | Incremental improvements while maintaining functionality |
| Real-time collaboration | Complexity, not core to video creation |
| Mobile app | Web-first approach |
| Video editing timeline | Use external tools for post-production |
| Custom voice training | Requires external service integration |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| PERF-01 | Phase 19 | Pending |
| PERF-02 | Phase 19 | Pending |
| PERF-03 | Phase 19 | Pending |
| PERF-08 | Phase 19 | Pending |
| PERF-04 | Phase 20 | Pending |
| PERF-05 | Phase 20 | Pending |
| PERF-06 | Phase 21 | Pending |
| PERF-07 | Phase 21 | Pending |

**Coverage:**
- v10 requirements: 8 total
- Mapped to phases: 8 (100%)
- Unmapped: 0 ✓

**Phase distribution:**
- Phase 19 (Quick Wins): 4 requirements (PERF-01, PERF-02, PERF-03, PERF-08)
- Phase 20 (Component Splitting): 2 requirements (PERF-04, PERF-05)
- Phase 21 (Data Normalization): 2 requirements (PERF-06, PERF-07)

## Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Payload size | 500KB-2MB | <50KB |
| Interaction latency | 2-5 seconds | <500ms |
| Component lines | 31,489 | <2,000 per component |
| wire:model.live bindings | 154+ | <20 |
| Base64 in state | Multiple images | 0 |

---
*Requirements defined: 2026-01-25*
*Source: Debug analysis .planning/debug/livewire-performance.md*
