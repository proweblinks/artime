# Requirements: Video Wizard - Cinematic Shot Architecture

**Defined:** 2026-01-23
**Core Value:** Automatic, effortless, Hollywood-quality output from button clicks

## v1 Requirements

Requirements for Milestone 8. Each maps to roadmap phases.

### Speech-to-Shot Architecture

- [ ] **CSA-01**: Each dialogue segment creates its own shot (1:1 mapping)
- [ ] **CSA-02**: Each monologue segment creates its own shot (1:1 mapping)
- [ ] **CSA-03**: Narrator segments overlay across multiple shots (not dedicated shots)
- [ ] **CSA-04**: Internal thought segments handled as voiceover (no dedicated shot)

### Shot Pattern & Flow

- [ ] **FLOW-01**: Shot/reverse-shot pattern for 2-character conversations
- [ ] **FLOW-02**: Single character visible per shot (model constraint enforced)
- [ ] **FLOW-03**: Shots build cinematically on each other (no jarring cuts)
- [ ] **FLOW-04**: Alternating character shots in dialogue sequences

### Camera Selection

- [ ] **CAM-01**: Dynamic CU/MS/OTS selection based on emotional intensity
- [ ] **CAM-02**: Camera variety based on position in conversation (opening vs climax)
- [ ] **CAM-03**: Shot type matches speaker's emotional state
- [ ] **CAM-04**: Establishing shot at conversation start, tight framing at climax

### Scene Handling

- [ ] **SCNE-01**: No artificial limit on shots per scene (10+ if speech demands)
- [ ] **SCNE-02**: Non-dialogue scenes get improved action decomposition
- [ ] **SCNE-03**: Mixed scenes (dialogue + action) handled smoothly
- [ ] **SCNE-04**: Scene maintains 180-degree rule throughout

## Future Requirements

Deferred to later milestones.

### Advanced Dialogue

- **ADV-01**: 3+ character conversation handling (group scenes)
- **ADV-02**: Cross-cutting between parallel conversations
- **ADV-03**: Flashback/memory shot integration

### Performance

- **PERF-01**: Shot generation preview before committing
- **PERF-02**: Batch regeneration of specific shots

## Out of Scope

Explicitly excluded.

| Feature | Reason |
|---------|--------|
| Multi-character in single shot | Multitalk model limitation, embrace as creative constraint |
| Manual shot reordering | Keep automatic flow, users can regenerate |
| Custom camera angles | Automatic selection is core value |
| Split-screen effects | Not standard Hollywood cinematography |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| CSA-01 | Phase 11 | Pending |
| CSA-02 | Phase 11 | Pending |
| CSA-03 | Phase 11 | Pending |
| CSA-04 | Phase 11 | Pending |
| SCNE-01 | Phase 11 | Pending |
| FLOW-01 | Phase 12 | Pending |
| FLOW-02 | Phase 12 | Pending |
| FLOW-04 | Phase 12 | Pending |
| SCNE-04 | Phase 12 | Pending |
| CAM-01 | Phase 13 | Pending |
| CAM-02 | Phase 13 | Pending |
| CAM-03 | Phase 13 | Pending |
| CAM-04 | Phase 13 | Pending |
| FLOW-03 | Phase 14 | Pending |
| SCNE-02 | Phase 14 | Pending |
| SCNE-03 | Phase 14 | Pending |

**Coverage:**
- v1 requirements: 16 total
- Mapped to phases: 16 (100%)
- Unmapped: 0 ✓

**Phase distribution:**
- Phase 11 (Speech-Driven): 5 requirements (CSA-01 to CSA-04, SCNE-01)
- Phase 12 (Shot/Reverse-Shot): 4 requirements (FLOW-01, FLOW-02, FLOW-04, SCNE-04)
- Phase 13 (Camera Intelligence): 4 requirements (CAM-01 to CAM-04)
- Phase 14 (Flow & Action): 3 requirements (FLOW-03, SCNE-02, SCNE-03)

---
*Requirements defined: 2026-01-23*
*Last updated: 2026-01-23 after initial definition*
