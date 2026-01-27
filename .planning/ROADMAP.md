# Video Wizard Development Roadmap

## Milestone 11.1: Voice Production Excellence

**Target:** Complete voice production pipeline with registry, continuity validation, and multi-speaker support
**Status:** In Progress (2026-01-27)
**Total requirements:** 6 (P0-P1 priorities from Phase 28 context)
**Phases:** 28

---

## Overview

Voice Production Excellence completes the Hollywood-Quality Prompt Pipeline by adding voice consistency infrastructure. Phase 25 created VoicePromptBuilderService with emotional direction and pacing, but the service is currently orphaned (not integrated into UI). This milestone adds Voice Registry for character-voice persistence, continuity validation across scenes, and multi-speaker dialogue support.

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 28 | Voice Production Excellence | Users get consistent character voices across scenes with multi-speaker dialogue support | VOC-07, VOC-08, VOC-09, VOC-10, VOC-11, VOC-12 | 4 |

**Total:** 1 phase | 6 requirements | 4 success criteria

---

## Phase 28: Voice Production Excellence

**Goal:** Users get consistent character voices across scenes with multi-speaker dialogue support

**Status:** Planned

**Plans:** 5 plans

Plans:
- [ ] 28-01-PLAN.md — Voice Registry persistence to Scene DNA (VOC-07)
- [ ] 28-02-PLAN.md — Emotion preview UI in Character Bible (VOC-12)
- [ ] 28-03-PLAN.md — VoiceContinuityValidator service (VOC-08)
- [ ] 28-04-PLAN.md — VoicePromptBuilder integration into VoiceoverService (VOC-09, VOC-11)
- [ ] 28-05-PLAN.md — MultiSpeakerDialogueBuilder for unified audio (VOC-10)

**Wave Structure:**
| Wave | Plans | Description |
|------|-------|-------------|
| 1 | 28-01, 28-02 | Foundation: Registry persistence + UI enhancements |
| 2 | 28-03, 28-04 | Validation + Integration: Continuity validator + TTS pipeline |
| 3 | 28-05 | Multi-speaker: Unified dialogue generation |

**Dependencies:** Phase 25 (VoicePromptBuilderService must exist)

**Requirements:**
- VOC-07: Voice Registry persists character-voice mappings across scenes
- VOC-08: Voice Continuity Validation ensures settings match across scenes
- VOC-09: Enhanced SSML Markup with full emotional direction support
- VOC-10: Multi-Speaker Dialogue handles conversations in single generation
- VOC-11: VoicePromptBuilderService integration into wizard UI
- VOC-12: Voice selection UI in Character Bible modal

**Success Criteria** (what must be TRUE):
1. Character voice selections persist across all scenes — same character always uses same voice without manual re-selection
2. Voice continuity warnings appear when settings drift — user notified if voice parameters change unexpectedly
3. Multi-speaker dialogue generates without manual splitting — conversations with 2+ characters produce unified audio
4. Voice prompts from Phase 25 flow through to generation — emotional direction tags appear in TTS requests

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 28: Voice Production Excellence | Planned | VOC-07 through VOC-12 (6) | 0/4 |

**Overall Progress:**

```
Phase 28: ░░░░░░░░░░ 0%
─────────────────────
M11.1:    ░░░░░░░░░░ 0% (0/6 requirements)
```

**Coverage:** 6/6 requirements mapped (100%)

---

## Dependencies

```
Phase 25 (Voice Prompt Enhancement) [v11 - SHIPPED]
    |
    v
Phase 28 (Voice Production Excellence)
    |
    +-- Plan 01 (Wave 1): Voice Registry Persistence
    +-- Plan 02 (Wave 1): Emotion Preview UI
    |
    +-- Plan 03 (Wave 2): VoiceContinuityValidator
    +-- Plan 04 (Wave 2): VoicePromptBuilder Integration
    |
    +-- Plan 05 (Wave 3): MultiSpeakerDialogueBuilder
```

Phase 28 builds on Phase 25's VoicePromptBuilderService.

---

*Milestone 11.1 roadmap created: 2026-01-27*
*Phase 28 planned: 2026-01-27 (5 plans, 3 waves)*
*Source: .planning/phases/28-voice-production-excellence/28-CONTEXT.md*
