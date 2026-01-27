# Video Wizard Development Roadmap

## Milestone 11: Hollywood-Quality Prompt Pipeline

**Target:** Transform prompt generation from 50-80 words to 600-1000 word Hollywood screenplay-level prompts for image, video, and voice generation
**Status:** In Progress (2026-01-25)
**Total requirements:** 25 (4 categories)
**Phases:** 22-28 (continues from M10)

---

## Overview

Hollywood-Quality Prompt Pipeline transforms the Video Wizard's AI prompts from basic descriptions into professional cinematography-level detail. Current prompts are 50-80 words; target is 600-1000 words with micro-expressions, FACS terminology, camera psychology, temporal beats, and emotional direction. The system builds template-driven expansion with Story Bible integration, model-specific adapters (CLIP 77-token limits, Gemini paragraphs, Runway concise), and optional LLM-powered expansion for complex shots. No new libraries needed — existing Laravel/Livewire architecture with enhanced prompt engineering.

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 22 | Foundation & Model Adapters | Model-aware prompt infrastructure with token limits and basic templates | INF-01, INF-03, IMG-01, IMG-02, IMG-03 | 5 |
| 23 | Character Psychology & Bible Integration | Prompts include human behavior detail and Story Bible context | INF-02, IMG-04, IMG-05, IMG-06, IMG-07, IMG-08, IMG-09 | 4 |
| 24 | Video Temporal Expansion | Video prompts include motion, timing, and character dynamics | VID-01, VID-02, VID-03, VID-04, VID-05, VID-06, VID-07 | 4 |
| 25 | Voice Prompt Enhancement | Voice prompts include emotional direction and performance cues | VOC-01, VOC-02, VOC-03, VOC-04, VOC-05, VOC-06 | 3 |
| 26 | LLM-Powered Expansion | AI expands complex shots beyond template capability | INF-04 | 2 |
| 27 | UI & Performance Polish | Prompt preview, caching, and comparison tools | INF-05, INF-06 | 3 |
| 28 | Voice Production Excellence | Voice registry, continuity validation, multi-speaker dialogue | TBD | TBD |

**Total:** 7 phases | 25+ requirements | 21+ success criteria

---

## Phase 22: Foundation & Model Adapters ✓

**Goal:** Users get model-appropriate prompts with proper token limits and professional camera/lighting vocabulary

**Status:** Complete (2026-01-26)

**Plans:** 3 plans in 3 waves

Plans:
- [x] 22-01-PLAN.md — CinematographyVocabulary and PromptTemplateLibrary (Wave 1)
- [x] 22-02-PLAN.md — ModelPromptAdapterService with CLIP tokenization (Wave 2)
- [x] 22-03-PLAN.md — Integration into ImageGenerationService (Wave 3)

**Dependencies:** None (starts new milestone)

**Requirements:**
- INF-01: Model adapters handle token limits (77-token CLIP limit for image models)
- INF-03: Template library organized by shot type (close-up needs face detail, wide needs environment)
- IMG-01: Image prompts include camera specs with psychological reasoning
- IMG-02: Image prompts include quantified framing (percentage of frame, compositional geometry)
- IMG-03: Image prompts include lighting with specific ratios (key/fill/back, color temperatures in Kelvin)

**Success Criteria** (what must be TRUE):
1. Generated image prompts respect model token limits — CLIP-based models receive compressed prompts under 77 tokens, Gemini receives full paragraph detail
2. Template library returns different prompt structures based on shot type — close-ups emphasize facial detail, wide shots emphasize environment
3. Camera specifications appear in prompts with psychological reasoning — "85mm lens creates intimate compression, isolating subject from background"
4. Framing descriptions include quantified positions — "subject occupies 40% of frame, positioned at left third intersection"
5. Lighting descriptions include specific values — "key light 45 degrees camera-left at 5600K, fill at -2 stops, rim light from behind"

---

## Phase 23: Character Psychology & Bible Integration ✓

**Goal:** Users see prompts that capture nuanced human behavior and maintain Story Bible consistency

**Status:** Complete (2026-01-27)

**Plans:** 4 plans in 3 waves

Plans:
- [x] 23-01-PLAN.md — CharacterPsychologyService with emotion-to-physical mappings (Wave 1)
- [x] 23-02-PLAN.md — MiseEnSceneService for environment-emotion integration (Wave 1)
- [x] 23-03-PLAN.md — ContinuityAnchorService and CharacterLookService expression presets (Wave 2)
- [x] 23-04-PLAN.md — StructuredPromptBuilderService integration (Wave 3)

**Dependencies:** Phase 22 (templates and adapters must exist)

**Requirements:**
- INF-02: Bible integration preserves character/location/style data in expanded prompts
- IMG-04: Image prompts include micro-expressions using physical manifestations (NOT FACS AU codes per research)
- IMG-05: Image prompts include body language with specific posture/gesture descriptions
- IMG-06: Image prompts include emotional state visible in physicality (not labels)
- IMG-07: Image prompts include subtext layer (what character hides vs reveals)
- IMG-08: Image prompts include mise-en-scene integration (environment reflects emotional state)
- IMG-09: Image prompts include continuity anchors (exact details that persist across shots)

**Success Criteria** (what must be TRUE):
1. Generated prompts include physical manifestations — "jaw muscles visibly tensed, brow lowered creating vertical crease" instead of "angry expression" (research showed FACS AU codes don't work for image models)
2. Character Bible data appears in prompts — character name, defining features, wardrobe details from Bible entries flow into every shot of that character
3. Emotional states expressed through physical manifestations — "shoulders hunched forward, fingers gripping armrest, jaw muscles visibly clenched" not "she is anxious"
4. Continuity anchors persist across related shots — if character wears a red scarf in shot 1, prompt explicitly includes "red wool scarf loosely draped" in shots 2-5

---

## Phase 24: Video Temporal Expansion ✓

**Goal:** Users see video prompts that choreograph motion, timing, and multi-character dynamics

**Status:** Complete (2026-01-27)

**Plans:** 4 plans in 2 waves

Plans:
- [x] 24-01-PLAN.md — VideoTemporalService and MicroMovementService (Wave 1)
- [x] 24-02-PLAN.md — CharacterDynamicsService and CharacterPathService (Wave 1)
- [x] 24-03-PLAN.md — TransitionVocabulary and CameraMovementService temporal extension (Wave 1)
- [x] 24-04-PLAN.md — VideoPromptBuilderService integration (Wave 2)

**Dependencies:** Phase 23 (image prompt features are inherited by video)

**Requirements:**
- VID-01: Video prompts include all image prompt features
- VID-02: Video prompts include temporal progression with beat-by-beat timing
- VID-03: Video prompts include camera movement with duration and psychological purpose
- VID-04: Video prompts include character movement paths within frame
- VID-05: Video prompts include inter-character dynamics (mirroring, spatial power relationships)
- VID-06: Video prompts include breath and micro-movements for realism
- VID-07: Video prompts include transition suggestions to next shot

**Success Criteria** (what must be TRUE):
1. Video prompts contain all image prompt elements plus temporal structure — camera specs, physical expressions, lighting all present alongside motion timing
2. Temporal beats appear with specific timing — "[00:00-00:02] character turns head left, [00:02-00:04] eyes widen in recognition, [00:04-00:07] slow smile spreads"
3. Camera movement includes duration and psychological framing — "dolly in over 4 seconds, closing distance as emotional connection deepens"
4. Multi-character shots describe spatial power dynamics — "dominant character positioned higher in frame, subordinate character's eyeline directed upward"

---

## Phase 25: Voice Prompt Enhancement

**Goal:** Users see voice prompts that direct emotional performance with specific delivery cues

**Status:** Planned (2026-01-27)

**Plans:** 3 plans in 2 waves

Plans:
- [ ] 25-01-PLAN.md — VoiceDirectionVocabulary: emotional direction tags, vocal qualities, non-verbal sounds (Wave 1)
- [ ] 25-02-PLAN.md — VoicePacingService: timing markers, pause notation, SSML conversion (Wave 1)
- [ ] 25-03-PLAN.md — VoicePromptBuilderService: ambient cues, emotional arc, integration (Wave 2)

**Dependencies:** Phase 22 (template infrastructure needed)

**Requirements:**
- VOC-01: Voice prompts include emotional direction tags (trembling, whisper, cracking)
- VOC-02: Voice prompts include pacing markers with timing
- VOC-03: Voice prompts include vocal quality descriptions (gravelly, exhausted, breathless)
- VOC-04: Voice prompts include ambient audio cues for scene atmosphere
- VOC-05: Voice prompts include breath and non-verbal sounds
- VOC-06: Voice prompts include emotional arc direction across dialogue sequence

**Success Criteria** (what must be TRUE):
1. Voice prompts include bracketed direction tags — "[trembling] I thought you were gone [voice cracks] forever"
2. Pacing markers appear with specific timing — "[PAUSE 2.5s] before delivering the revelation, [SLOW] for emphasis on key phrase"
3. Emotional arc direction spans dialogue sequences — "start defeated and quiet, build to desperate pleading by third line, crack into tears on final word"

---

## Phase 26: LLM-Powered Expansion

**Goal:** Users get AI-enhanced prompts for complex shots that exceed template capability

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 26-01: TBD

**Dependencies:** Phases 22-25 (templates and model adapters must exist first)

**Requirements:**
- INF-04: LLM-powered expansion for complex shots that exceed template capability

**Success Criteria** (what must be TRUE):
1. Complex shots trigger LLM expansion — shots with multiple characters, unusual settings, or high emotional complexity automatically route to AI expansion
2. LLM-expanded prompts maintain template structure and vocabulary — AI expansion produces same professional terminology (FACS, camera psychology, lighting ratios) as templates

---

## Phase 27: UI & Performance Polish

**Goal:** Users can preview, compare, and efficiently use expanded prompts

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 27-01: TBD

**Dependencies:** Phases 22-26 (prompt system must be complete)

**Requirements:**
- INF-05: Prompt caching for performance (avoid re-expanding identical contexts)
- INF-06: Prompt comparison view in UI (before/after expansion, word count)

**Success Criteria** (what must be TRUE):
1. Identical contexts return cached prompts — same shot with same Bible context returns cached expansion without re-processing
2. UI shows before/after prompt comparison — original brief prompt alongside expanded Hollywood prompt with word count difference visible
3. Prompt expansion toggle available in settings — users can disable expansion for faster generation or enable for quality

---

## Phase 28: Voice Production Excellence

**Goal:** Users get consistent voice production with registry, continuity validation, and multi-speaker support

**Status:** Not started

**Plans:** TBD

Plans:
- [ ] 28-01: TBD (run /gsd:plan-phase 28 to break down)

**Dependencies:** Phase 27

**Requirements:**
- TBD (run /gsd:plan-phase 28 to define from context)

**Success Criteria** (what must be TRUE):
- TBD (will be derived from requirements during planning)

**Context Available:**
- Comprehensive audit document saved as 28-CONTEXT.md
- Covers Voice Registry, SSML markup, multi-speaker dialogue, lip-sync production
- Priority matrix (P0-P3) defined

---

## Dependencies

```
Phase 22 (Foundation & Model Adapters)
    |
    +---> Phase 23 (Character Psychology & Bible)
    |         |
    |         v
    |     Phase 24 (Video Temporal Expansion)
    |
    +---> Phase 25 (Voice Prompt Enhancement)
              |
              v
          Phase 26 (LLM-Powered Expansion) <- also depends on 22-24
              |
              v
          Phase 27 (UI & Performance Polish)
              |
              v
          Phase 28 (Voice Production Excellence)
```

Phase 25 can run in parallel with 23-24 (voice is independent of image/video progression).
Phase 26 requires all prompt types complete before AI expansion.
Phase 27 is final polish after all prompt systems work.
Phase 28 enhances voice production with registry and multi-speaker capabilities.

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 22: Foundation & Model Adapters | Complete ✓ | INF-01, INF-03, IMG-01, IMG-02, IMG-03 (5) | 5/5 |
| Phase 23: Character Psychology & Bible | Complete ✓ | INF-02, IMG-04, IMG-05, IMG-06, IMG-07, IMG-08, IMG-09 (7) | 4/4 |
| Phase 24: Video Temporal Expansion | Complete ✓ | VID-01, VID-02, VID-03, VID-04, VID-05, VID-06, VID-07 (7) | 4/4 |
| Phase 25: Voice Prompt Enhancement | Planned | VOC-01, VOC-02, VOC-03, VOC-04, VOC-05, VOC-06 (6) | 0/3 |
| Phase 26: LLM-Powered Expansion | Not started | INF-04 (1) | 0/2 |
| Phase 27: UI & Performance Polish | Not started | INF-05, INF-06 (2) | 0/3 |
| Phase 28: Voice Production Excellence | Not started | TBD | 0/TBD |

**Overall Progress:**

```
Phase 22: ██████████ 100% ✓
Phase 23: ██████████ 100% ✓
Phase 24: ██████████ 100% ✓
Phase 25: ░░░░░░░░░░ 0%
Phase 26: ░░░░░░░░░░ 0%
Phase 27: ░░░░░░░░░░ 0%
Phase 28: ░░░░░░░░░░ 0%
─────────────────────
Overall:  ███████░░░ 76% (19/25 requirements)
```

**Coverage:** 25/25 requirements mapped (100%)

---

## Verification Strategy

After each phase:
1. Generate prompts for test shots (close-up, wide, action, dialogue)
2. Verify prompt word count reaches target (600-1000 for image/video)
3. Check professional vocabulary present (FACS, camera psychology, Kelvin values)
4. Confirm Story Bible data appears in prompts
5. Test model adapter compression (77 tokens for CLIP models)
6. Run complete wizard flow to verify no regression

---

## Previous Milestone (Paused)

### Milestone 10: Livewire Performance Architecture - PAUSED

**Status:** Paused after Phase 19 (Quick Wins complete)
**Phases:** 19-21

| Phase | Status |
|-------|--------|
| Phase 19: Quick Wins | Complete |
| Phase 20: Component Splitting | Deferred |
| Phase 21: Data Normalization | Deferred |

**Key achievements:**
- Livewire 3 attributes (#[Locked], #[Computed]) applied
- Debounced bindings in Blade templates
- Base64 storage migration to files
- Updated hook optimization

**Reason for pause:** Prompt quality is higher priority than performance optimization.

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

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

Hollywood-Quality Prompt Pipeline delivers on the "Hollywood-quality" promise. Users click buttons and receive prompts that would satisfy a professional cinematographer — specific camera psychology, FACS micro-expressions, precise lighting ratios, and choreographed motion timing. The system does the expertise; users provide the vision.

---

*Milestone 11 roadmap created: 2026-01-25*
*Phase 22 planned: 2026-01-26*
*Phase 23 planned: 2026-01-27*
*Phase 24 planned: 2026-01-27*
*Phase 25 planned: 2026-01-27*
*Phase 28 added: 2026-01-27*
*Phases 22-28 defined*
*Source: Research .planning/research/SUMMARY.md*
