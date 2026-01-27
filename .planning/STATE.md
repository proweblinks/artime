# Video Wizard - Current State

> Last Updated: 2026-01-27
> Session: Milestone 11 - Hollywood-Quality Prompt Pipeline

---

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-25)

**Core value:** Automatic, effortless, Hollywood-quality output from button clicks
**Current focus:** Phase 24 - Video Temporal Expansion (IN PROGRESS)

---

## Current Position

**Milestone:** 11 (Hollywood-Quality Prompt Pipeline)
**Phase:** 24 of 28 (Video Temporal Expansion)
**Plan:** 3 of 4
**Status:** In progress

```
Phase 24: ███████████████░░░░░░░ 75% (3/4 plans complete)
─────────────────────
M11:      ███████░░░░ 44% (11/25 requirements)
```

**Last activity:** 2026-01-27 - Completed 24-03-PLAN.md (Transition Vocabulary + Temporal Movement)

---

## Performance Metrics

**Velocity:**
- Total plans completed: 11 (M11)
- Average duration: 9.3 min
- Total execution time: 102 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 22 | 3/3 | 34 min | 11.3 min |
| 23 | 4/4 | 42 min | 10.5 min |
| 24 | 3/4 | 26 min | 8.7 min |

**Recent Trend:**
- Last 5 plans: 23-03 (6m), 23-04 (8m), 24-01 (9m), 24-02 (9m), 24-03 (8m)
- Trend: Consistent (~8min average for Phase 24)

*Updated after each plan completion*

---

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- [M11 Start]: Pivot from M10 to M11 - prompt quality higher priority than performance
- [M11 Start]: Research first - study Hollywood cinematography patterns before coding
- [22-01]: Lens psychology includes reasoning ("creates intimacy") not just specs
- [22-01]: Word budgets sum to exactly 100% for predictable allocation
- [22-01]: 10 shot types for comprehensive cinematic coverage
- [22-02]: BPE tokenizer with word-estimate fallback when library unavailable
- [22-02]: Subject NEVER removed during compression (priority 1)
- [22-02]: Style markers removed first (8K, photorealistic, etc.)
- [22-02]: CLIP limit is 77 tokens; Gemini models get full prompts unchanged
- [22-03]: Prompt adaptation occurs just before provider routing for maximum flexibility
- [22-03]: Cascade path also adapted with dedicated logging
- [22-03]: Hollywood vocabulary wrapped in semantic markers [LENS:], [LIGHTING:], [FRAME:]
- [23-01]: Physical manifestations (jaw, brow, posture) instead of abstract emotion labels
- [23-01]: No FACS AU codes - research showed they don't work for image models
- [23-01]: face/eyes/body/breath four-component structure for each emotion
- [23-01]: buildEnhancedEmotionDescription ready for Bible trait integration (Plan 04)
- [23-02]: 8 core emotions for mise-en-scene: anxiety, tension, peace, isolation, danger, hope, intimacy, chaos
- [23-02]: Emotion aliases map 30+ casual terms to core emotions
- [23-02]: Tension scale uses 10 levels with thresholds at 1,3,5,7,10
- [23-02]: Blending intensity 0.0-1.0 allows gradual emotional overlay
- [23-03]: Expression presets use physical descriptions (face/eyes), not FACS AU codes
- [23-03]: ANCHOR_PRIORITY has three levels: primary (identity), secondary (continuity), tertiary (scene)
- [23-03]: Bridge method allows progressive enhancement from presets to full psychology
- [23-03]: Anchor conflict detection uses 70% similarity threshold with severity levels
- [23-04]: Psychology layer only generated when emotion is specified
- [23-04]: Shot-type emphasis: close-up=face, wide=body, medium=both
- [23-04]: Scene DNA path extracts emotion from sceneDNAEntry first, then falls back to options
- [23-04]: Bible defining_features woven into psychology expressions (INF-02)
- [24-02]: Edward Hall's 4 proxemic zones for spatial vocabulary (intimate 0-18in, personal 18in-4ft, social 4-12ft, public 12+ft)
- [24-02]: Power positioning uses frame position (higher=dominant, lower=subordinate)
- [24-02]: 5 character path categories: approach, retreat, stationary_motion, crossing, gestural
- [24-02]: Path duration estimates aligned with VideoTemporalService (2-5s typical)
- [24-03]: Transition vocabulary is editorial (shot endings), NOT post-production (dissolves/wipes)
- [24-03]: Mood-to-transition mapping: energetic->match_cut, tense->hard_cut, contemplative->soft_transition
- [24-03]: Duration clamped to typical_duration_min/max from VwCameraMovement model
- [24-03]: Psychology appended with comma separator for natural reading
- [24-03]: 80% rule - movement duration max 80% of clip duration

### Phase 24 Progress

**Phase 24: Video Temporal Expansion is IN PROGRESS (3/4 plans complete).**

Delivered:
1. 24-01: VideoTemporalService - Clip duration calculation and temporal pacing patterns
2. 24-02: CharacterDynamicsService - Character path, blocking, and transition vocabulary
3. 24-03: TransitionVocabulary + CameraMovementService - Shot ending states and temporal movement prompts

Remaining:
4. 24-04: Integration - Temporal services integrated into video generation pipeline

### Pending Todos

None.

### Blockers/Concerns

None currently.

### Roadmap Evolution

- Phase 28 added: Voice Production Excellence (2026-01-27) — Voice registry, continuity validation, multi-speaker dialogue

---

## Session Continuity

Last session: 2026-01-27
Stopped at: Completed 24-03-PLAN.md (Transition Vocabulary + Temporal Movement)
Resume file: None
Next step: Continue with 24-04-PLAN.md (Integration)

---

## Phase 24 Artifacts (IN PROGRESS)

- `.planning/phases/24-video-temporal-expansion/24-RESEARCH.md`
- `.planning/phases/24-video-temporal-expansion/24-01-PLAN.md` (VideoTemporalService) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-01-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-02-PLAN.md` (CharacterDynamicsService) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-02-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-03-PLAN.md` (Transition Vocabulary + Temporal Movement) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-03-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-04-PLAN.md` (Integration) - PENDING

Key Files Created (Phase 24):
- `modules/AppVideoWizard/app/Services/VideoTemporalService.php`
- `modules/AppVideoWizard/app/Services/CharacterDynamicsService.php`
- `modules/AppVideoWizard/app/Services/CharacterPathService.php`
- `modules/AppVideoWizard/app/Services/TransitionVocabulary.php`
- `tests/Unit/VideoWizard/VideoTemporalServiceTest.php`
- `tests/Unit/VideoWizard/CharacterDynamicsServiceTest.php`
- `tests/Unit/VideoWizard/CharacterPathServiceTest.php`
- `tests/Unit/VideoWizard/TransitionVocabularyTest.php`
- `tests/Unit/VideoWizard/CameraMovementServiceTemporalTest.php`

Key Files Modified (Phase 24):
- `modules/AppVideoWizard/app/Services/CameraMovementService.php` (MOVEMENT_PSYCHOLOGY, buildTemporalMovementPrompt)

Tests (Phase 24):
- `tests/Unit/VideoWizard/VideoTemporalServiceTest.php`
- `tests/Unit/VideoWizard/CharacterDynamicsServiceTest.php`
- `tests/Unit/VideoWizard/CharacterPathServiceTest.php`
- `tests/Unit/VideoWizard/TransitionVocabularyTest.php`
- `tests/Unit/VideoWizard/CameraMovementServiceTemporalTest.php`

---

## Phase 23 Artifacts (COMPLETE)

- `.planning/phases/23-character-psychology-bible/23-CONTEXT.md`
- `.planning/phases/23-character-psychology-bible/23-RESEARCH.md`
- `.planning/phases/23-character-psychology-bible/23-01-PLAN.md` (CharacterPsychologyService) - COMPLETE
- `.planning/phases/23-character-psychology-bible/23-01-SUMMARY.md`
- `.planning/phases/23-character-psychology-bible/23-02-PLAN.md` (MiseEnSceneService) - COMPLETE
- `.planning/phases/23-character-psychology-bible/23-02-SUMMARY.md`
- `.planning/phases/23-character-psychology-bible/23-03-PLAN.md` (ContinuityAnchorService) - COMPLETE
- `.planning/phases/23-character-psychology-bible/23-03-SUMMARY.md`
- `.planning/phases/23-character-psychology-bible/23-04-PLAN.md` (Integration) - COMPLETE
- `.planning/phases/23-character-psychology-bible/23-04-SUMMARY.md`

Key Files Created (Phase 23):
- `modules/AppVideoWizard/app/Services/CharacterPsychologyService.php`
- `modules/AppVideoWizard/app/Services/MiseEnSceneService.php`
- `modules/AppVideoWizard/app/Services/ContinuityAnchorService.php`
- `tests/Feature/VideoWizard/PsychologyPromptIntegrationTest.php`

Key Files Modified (Phase 23):
- `modules/AppVideoWizard/app/Services/CharacterLookService.php` (added EXPRESSION_PRESETS)
- `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php` (psychology layer integration)

Tests (Phase 23):
- `tests/Unit/VideoWizard/CharacterPsychologyServiceTest.php`
- `tests/Unit/VideoWizard/MiseEnSceneServiceTest.php`
- `tests/Unit/VideoWizard/ContinuityAnchorServiceTest.php`
- `tests/Feature/VideoWizard/PsychologyPromptIntegrationTest.php`

---

## Phase 22 Artifacts (Complete)

- `.planning/phases/22-foundation-model-adapters/22-CONTEXT.md`
- `.planning/phases/22-foundation-model-adapters/22-RESEARCH.md`
- `.planning/phases/22-foundation-model-adapters/22-01-PLAN.md` (Cinematography Vocabulary)
- `.planning/phases/22-foundation-model-adapters/22-01-SUMMARY.md`
- `.planning/phases/22-foundation-model-adapters/22-02-PLAN.md` (Model Prompt Adapter)
- `.planning/phases/22-foundation-model-adapters/22-02-SUMMARY.md`
- `.planning/phases/22-foundation-model-adapters/22-03-PLAN.md` (Integration)
- `.planning/phases/22-foundation-model-adapters/22-03-SUMMARY.md`

Key Files Created:
- `modules/AppVideoWizard/app/Services/CinematographyVocabulary.php`
- `modules/AppVideoWizard/app/Services/PromptTemplateLibrary.php`
- `modules/AppVideoWizard/app/Services/ModelPromptAdapterService.php`
- `storage/app/clip_vocab/bpe_simple_vocab_16e6.txt`

Key Files Modified:
- `modules/AppVideoWizard/app/Services/ImageGenerationService.php`
- `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php`

Tests:
- `tests/Unit/CinematographyVocabularyTest.php`
- `tests/Unit/PromptTemplateLibraryTest.php`
- `tests/Unit/ModelPromptAdapterServiceTest.php`
- `tests/Feature/VideoWizard/PromptAdaptationIntegrationTest.php`
