# Requirements: Video Wizard - Voice Production Excellence

**Defined:** 2026-01-24
**Core Value:** Automatic, effortless, Hollywood-quality output from button clicks

## v1 Requirements

Requirements for Milestone 9. Each maps to roadmap phases.

### Critical Fixes (P0)

- [x] **VOC-01**: Narrator voice assigned to shots (narratorVoiceId flows through overlayNarratorSegments)
- [x] **VOC-02**: Empty text validation before TTS (empty/invalid segments caught early)

### Consistency Layer (P1)

- [x] **VOC-03**: Unified distribution strategy (narrator and internal thoughts use same word-split approach)
- [x] **VOC-04**: Voice continuity validation (same character maintains same voice across all scenes)

### Voice Architecture (P2)

- [ ] **VOC-05**: Voice Registry centralization (single source of truth for narrator, internal, character voices)
- [ ] **VOC-06**: Multi-speaker shot support (multiple speakers tracked per shot for dialogue scenes)

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
| Real-time voice preview | High complexity, not critical for production |
| Custom voice training | Requires external service integration |
| Multi-language TTS | English-first, expand later |
| Audio editing timeline | Use external tools for post-production |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| VOC-01 | Phase 15 | Complete |
| VOC-02 | Phase 15 | Complete |
| VOC-03 | Phase 16 | Complete |
| VOC-04 | Phase 16 | Complete |
| VOC-05 | Phase 17 | Pending |
| VOC-06 | Phase 18 | Pending |

**Coverage:**
- v1 requirements: 6 total
- Mapped to phases: 6 (100%)
- Unmapped: 0 ✓

**Phase distribution:**
- Phase 15 (Critical Fixes): 2 requirements (VOC-01, VOC-02)
- Phase 16 (Consistency Layer): 2 requirements (VOC-03, VOC-04)
- Phase 17 (Voice Registry): 1 requirement (VOC-05)
- Phase 18 (Multi-Speaker): 1 requirement (VOC-06)

---
*Requirements defined: 2026-01-24*
*Source: Comprehensive TTS/Lip-Sync audit*
