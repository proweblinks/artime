# Video Wizard Development Roadmap

## Milestone 11.2: Prompt Pipeline Integration (COMPLETE)

**Target:** Wire comprehensive prompts to shot generation, add voice prompt display, fix default model
**Status:** Complete (2026-01-27)
**Total requirements:** 4 (PPL-01 through PPL-04) + tech debt closure
**Phases:** 29, 29.1

---

## Milestone 11.1: Voice Production Excellence (COMPLETE)

**Target:** Complete voice production pipeline with registry, continuity validation, and multi-speaker support
**Status:** Complete (2026-01-27)
**Total requirements:** 6 (P0-P1 priorities from Phase 28 context)
**Phases:** 28

---

## Overview

Voice Production Excellence completes the Hollywood-Quality Prompt Pipeline by adding voice consistency infrastructure. Phase 25 created VoicePromptBuilderService with emotional direction and pacing, but the service is currently orphaned (not integrated into UI). This milestone adds Voice Registry for character-voice persistence, continuity validation across scenes, and multi-speaker dialogue support.

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 29 | Prompt Pipeline Integration | Shot prompts include full Character/Location DNA, voice prompts displayed, quality defaults | PPL-01, PPL-02, PPL-03, PPL-04 | 4 |
| 29.1 | Integration Consistency Fixes | Close tech debt from M11.2 audit: consistent fallbacks, emotion data flow, voice prompt enhancement | DEBT-01, DEBT-02, DEBT-03 | 3 |

**Total:** 2 phases | 4 requirements + 3 debt items | 7 success criteria

---

## Phase 29: Prompt Pipeline Integration

**Goal:** Shot prompts include full Character/Location DNA, voice prompts displayed in UI, nanobanana-pro default

**Status:** Complete (2026-01-27)

**Plans:** 3 plans

Plans:
- [x] 29-01-PLAN.md — Default image model to nanobanana-pro (PPL-04)
- [x] 29-02-PLAN.md — Character/Location DNA in shot prompts (PPL-01, PPL-02)
- [x] 29-03-PLAN.md — Voice prompt display in Shot Preview (PPL-03)

**Wave Structure:**
| Wave | Plans | Description |
|------|-------|-------------|
| 1 | 29-01, 29-02, 29-03 | All parallel: model fix, prompt enhancement, UI display |

**Dependencies:** Phase 28 complete (Voice Production Excellence)

**Requirements:**
- PPL-01: Shot prompts include Character DNA (character descriptions from Scene Memory)
- PPL-02: Shot prompts include Location DNA (location details from Scene Memory)
- PPL-03: Voice prompt displayed in Shot Preview modal
- PPL-04: Default image model is nanobanana-pro (3 tokens)

**Success Criteria** (what must be TRUE):
1. Shot Preview IMAGE PROMPT shows full prompt with CHARACTERS and LOCATION sections
2. Shot Preview has VOICE PROMPT section showing narration/dialogue and emotional direction
3. New projects default to nanobanana-pro model
4. Images generated are visually richer and story-specific

---

## Phase 29.1: Integration Consistency Fixes

**Goal:** Close tech debt from M11.2 audit — consistent imageModel fallbacks, emotion data inheritance, voice prompt enhancement in UI

**Status:** Complete (2026-01-27)

**Plans:** 2 plans

Plans:
- [x] 29.1-01-PLAN.md — Consistent imageModel fallbacks + emotion data inheritance (DEBT-01, DEBT-02)
- [x] 29.1-02-PLAN.md — Wire VoicePromptBuilderService to Shot Preview (DEBT-03)

**Wave Structure:**
| Wave | Plans | Description |
|------|-------|-------------|
| 1 | 29.1-01, 29.1-02 | All parallel: backend fixes and UI enhancement |

**Gap Closure:** Closes tech debt from M11.2-MILESTONE-AUDIT.md

**Dependencies:** Phase 29 complete

**Tech Debt Addressed:**
- DEBT-01: Update 3 remaining imageModel fallbacks from 'hidream' to 'nanobanana-pro' (lines 26503, 26918, 27255)
- DEBT-02: Add emotion field to shot creation in createShotsFromSpeechSegments (line 25244)
- DEBT-03: Wire VoicePromptBuilderService to Shot Preview for enhanced voice prompts

**Success Criteria** (what must be TRUE):
1. All imageModel fallbacks use 'nanobanana-pro' consistently
2. Shots created from speech segments include emotion data
3. Shot Preview VOICE PROMPT shows enhanced prompts with emotional direction and pacing markers

---

## Phase Overview (M11.1 - Complete)

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 28 | Voice Production Excellence | Users get consistent character voices across scenes with multi-speaker dialogue support | VOC-07, VOC-08, VOC-09, VOC-10, VOC-11, VOC-12 | 4 |

**Total:** 1 phase | 6 requirements | 4 success criteria

---

## Phase 28: Voice Production Excellence

**Goal:** Users get consistent character voices across scenes with multi-speaker dialogue support

**Status:** Complete

**Plans:** 5 plans

Plans:
- [x] 28-01-PLAN.md — Voice Registry persistence to Scene DNA (VOC-07)
- [x] 28-02-PLAN.md — Emotion preview UI in Character Bible (VOC-12)
- [x] 28-03-PLAN.md — VoiceContinuityValidator service (VOC-08)
- [x] 28-04-PLAN.md — VoicePromptBuilder integration into VoiceoverService (VOC-09, VOC-11)
- [x] 28-05-PLAN.md — MultiSpeakerDialogueBuilder for unified audio (VOC-10)

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
| Phase 29: Prompt Pipeline Integration | Complete | PPL-01 through PPL-04 (4) | 4/4 |
| Phase 29.1: Integration Consistency Fixes | Complete | DEBT-01, DEBT-02, DEBT-03 (3) | 3/3 |
| Phase 28: Voice Production Excellence | Complete | VOC-07 through VOC-12 (6) | 4/4 |

**Overall Progress:**

```
Phase 29:   ██████████ 100%
Phase 29.1: ██████████ 100%
─────────────────────────
M11.2:      ██████████ 100% (4/4 requirements + 3/3 debt)
```

**Coverage:** 4/4 requirements mapped + 3 debt items (100%)

---

## Dependencies

```
Phase 25 (Voice Prompt Enhancement) [v11 - SHIPPED]
    |
    v
Phase 28 (Voice Production Excellence) [M11.1 - COMPLETE]
    |
    v
Phase 29 (Prompt Pipeline Integration) [M11.2 - COMPLETE]
    |
    +-- PPL-01: Character DNA in shot prompts ✓
    +-- PPL-02: Location DNA in shot prompts ✓
    +-- PPL-03: Voice prompt display in Shot Preview ✓
    +-- PPL-04: Default to nanobanana-pro ✓
    |
    v
Phase 29.1 (Integration Consistency Fixes) [M11.2 - COMPLETE]
    |
    +-- DEBT-01: Consistent imageModel fallbacks ✓
    +-- DEBT-02: Emotion data inheritance ✓
    +-- DEBT-03: VoicePromptBuilder in Shot Preview ✓
```

Phase 29 wires existing services to UI display and fixes defaults.
Phase 29.1 closes tech debt identified during M11.2 audit.

---

*Milestone 11.1 completed: 2026-01-27*
*Milestone 11.2 started: 2026-01-27*
*Phase 29 context: .planning/phases/29-prompt-pipeline-integration/29-CONTEXT.md*
