# Video Wizard - Current State

> Last Updated: 2026-01-27
> Session: Milestone 11 - Hollywood-Quality Prompt Pipeline

---

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-25)

**Core value:** Automatic, effortless, Hollywood-quality output from button clicks
**Current focus:** Phase 26 - LLM-Powered Expansion (COMPLETE with gap closure)

---

## Current Position

**Milestone:** 11 (Hollywood-Quality Prompt Pipeline)
**Phase:** 26 of 28 (LLM-Powered Expansion)
**Plan:** 4 of 4 (gap closure complete)
**Status:** Phase complete

```
Phase 26: ████████████████████████ 100% (4/4 plans complete)
─────────────────────
M11:      ██████████████░ 76% (19/25 requirements)
```

**Last activity:** 2026-01-27 - Completed 26-04-PLAN.md (Gap Closure)

---

## Performance Metrics

**Velocity:**
- Total plans completed: 19 (M11)
- Average duration: 7.9 min
- Total execution time: 151 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 22 | 3/3 | 34 min | 11.3 min |
| 23 | 4/4 | 42 min | 10.5 min |
| 24 | 4/4 | 34 min | 8.5 min |
| 25 | 3/3 | 20 min | 6.7 min |
| 26 | 4/4 | 21 min | 5.3 min |

**Recent Trend:**
- Last 5 plans: 25-03 (8m), 26-01 (6m), 26-02 (6m), 26-03 (5m), 26-04 (4m)
- Trend: Fast execution continuing (Phase 26 averaging 5.3 min)

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
- [24-01]: Simple actions 2-3s, complex motions 4-5s for natural video pacing
- [24-01]: MAX_ACTIONS_PER_DURATION prevents overpacked clips (5s=2, 10s=4, 15s=5)
- [24-01]: Shot type determines visible micro-movements (close-up=face, wide=none)
- [24-01]: Emotion-to-variant mapping for micro-movements (tense=held breath, anxious=rapid breath)
- [24-02]: Edward Hall's 4 proxemic zones for spatial vocabulary (intimate 0-18in, personal 18in-4ft, social 4-12ft, public 12+ft)
- [24-02]: Power positioning uses frame position (higher=dominant, lower=subordinate)
- [24-02]: 5 character path categories: approach, retreat, stationary_motion, crossing, gestural
- [24-02]: Path duration estimates aligned with VideoTemporalService (2-5s typical)
- [24-03]: Transition vocabulary is editorial (shot endings), NOT post-production (dissolves/wipes)
- [24-03]: Mood-to-transition mapping: energetic->match_cut, tense->hard_cut, contemplative->soft_transition
- [24-03]: Duration clamped to typical_duration_min/max from VwCameraMovement model
- [24-03]: Psychology appended with comma separator for natural reading
- [24-03]: 80% rule - movement duration max 80% of clip duration
- [24-04]: buildTemporalVideoPrompt builds on buildHollywoodPrompt (inherits all image features)
- [24-04]: Auto-generate temporal beats when none provided, using action classification
- [24-04]: Emotion maps to psychology key for camera movement purpose
- [24-04]: Transition setup stored in metadata (editorial info), not in main prompt
- [24-04]: Prompt assembly: Camera -> Subject+Dynamics -> Beats -> Micro-movements -> Base
- [25-01]: No FACS AU codes for TTS - research confirmed they don't work
- [25-01]: ElevenLabs uses inline bracketed tags; OpenAI uses system instructions
- [25-01]: 8 emotions aligned with CharacterPsychologyService (grief, anxiety, fear, contempt)
- [25-01]: Provider-specific tags stored in separate array keys, not embedded
- [25-02]: Five named pause types: beat (0.5s), short (1s), medium (2s), long (3s), breath (0.3s)
- [25-02]: Five rate modifiers: slow (0.85x), measured (0.9x), normal (1.0x), urgent (1.1x), rushed (1.2x)
- [25-02]: SSML uses milliseconds for sub-second precision, seconds for whole numbers
- [25-02]: toSSML converts both custom [PAUSE Xs] and named [beat] markers
- [25-03]: 8 ambient audio cues: intimate, outdoor, crowded, tense, storm, night, office, vehicle
- [25-03]: 6 emotional arc patterns: building, crashing, recovering, masking, revealing, confronting
- [25-03]: Arc distribution: 4 stages distributed proportionally across any segment count
- [25-03]: Provider output: ElevenLabs=inline tags, OpenAI=separate instructions, Kokoro=descriptive text
- [25-03]: Unknown scene types fall back to 'intimate' ambient cue
- [26-01]: Five complexity dimensions: multi_character, emotional_complexity, environment_novelty, combination_novelty, token_budget_risk
- [26-01]: Weighted scoring: multi_character 30%, emotional 25%, environment 20%, combination 15%, token_budget 10%
- [26-01]: Single dimension >= 0.7 triggers complexity
- [26-01]: Total weighted score >= 0.6 triggers complexity
- [26-01]: 3+ characters ALWAYS triggers complexity regardless of other scores
- [26-02]: Meta-prompting with vocabulary constraints over few-shot examples for consistency
- [26-02]: Grok (grok-4-fast) as primary LLM - cost-effective at $0.20/1M input
- [26-02]: Temperature 0.4 for controlled creativity
- [26-02]: 200 word limit for expanded prompts
- [26-02]: Minimum 2 semantic markers required for valid LLM output
- [26-02]: Multi-character scenes MUST include [DYNAMICS:] marker
- [26-03]: Lazy LLMExpansionService initialization via app() to avoid circular dependencies
- [26-03]: buildHollywoodPrompt() as new entry point wrapping build() with LLM routing
- [26-03]: llm_expansion option defaults to true - must explicitly disable
- [26-03]: Scene DNA characters extracted before character_bible for complexity check
- [26-04]: build() delegates to buildHollywoodPrompt() for automatic LLM routing
- [26-04]: Original build() renamed to buildTemplate() for template-only path
- [26-04]: buildHollywoodPrompt() calls buildTemplate() (not build()) to avoid circular dependency

### Phase 26 Progress

**Phase 26: LLM-Powered Expansion is COMPLETE (4/4 plans including gap closure).**

Delivered:
1. 26-01: ComplexityDetectorService - Multi-dimensional complexity scoring for LLM routing
2. 26-02: LLMExpansionService - AI-powered expansion with vocabulary constraints and fallback cascade
3. 26-03: Integration - LLM routing in StructuredPromptBuilderService with buildHollywoodPrompt()
4. 26-04: Gap Closure - build() delegates to buildHollywoodPrompt() for backward compatibility

**LLM Requirements Complete:**
- LLM-01: Complexity detection for LLM routing - COMPLETE
- LLM-02: Prompt expansion for complex shots - COMPLETE
- LLM-03: Integration with existing prompt pipeline - COMPLETE
- LLM-04: Backward compatibility for existing callers - COMPLETE (gap closure)

### Phase 25 Progress

**Phase 25: Voice Prompt Enhancement is COMPLETE (3/3 plans).**

Delivered:
1. 25-01: VoiceDirectionVocabulary - Emotional direction tags, vocal qualities, non-verbal sounds
2. 25-02: VoicePacingService - Timing markers, pause notation, SSML conversion
3. 25-03: VoicePromptBuilderService - Integration with ambient cues and emotional arcs

**VOC Requirements Complete:**
- VOC-01: Emotional direction tags [trembling], [whisper], [voice cracks] - COMPLETE
- VOC-02: Pacing markers with specific timing [PAUSE 2.5s] - COMPLETE
- VOC-03: Vocal quality descriptions (gravelly, exhausted, breathless) - COMPLETE
- VOC-04: Ambient audio cues for scene atmosphere - COMPLETE
- VOC-05: Breath and non-verbal sound markers [sighs], [gasps], [stammers] - COMPLETE
- VOC-06: Emotional arc direction across dialogue sequences - COMPLETE

### Phase 24 Progress

**Phase 24: Video Temporal Expansion is COMPLETE (4/4 plans).**

Delivered:
1. 24-01: VideoTemporalService + MicroMovementService - Temporal beats with timing markers and micro-movement vocabulary
2. 24-02: CharacterDynamicsService + CharacterPathService - Character path, blocking, and spatial dynamics
3. 24-03: TransitionVocabulary + CameraMovementService - Shot ending states and temporal movement prompts
4. 24-04: Integration - buildTemporalVideoPrompt integrating all temporal services

**VID Requirements Complete:**
- VID-01: Video prompts contain all image features (camera, lighting, psychology)
- VID-02: Temporal beat structure with timing [00:00-00:02] format
- VID-03: Camera movement with "over X seconds" duration and psychology phrase
- VID-04: Character movement paths when movement_intent provided
- VID-05: Multi-character spatial dynamics with proxemic zones
- VID-06: Close-up micro-movements (breathing, eyes); wide shots omit them
- VID-07: Transition setup with ending_state and next_shot_suggestion

### Pending Todos

None.

### Blockers/Concerns

None currently.

### Roadmap Evolution

- Phase 28 added: Voice Production Excellence (2026-01-27) — Voice registry, continuity validation, multi-speaker dialogue

---

## Session Continuity

Last session: 2026-01-27
Stopped at: Completed 26-04-PLAN.md (Gap Closure)
Resume file: None
Next step: Phase 27 or Milestone wrap-up

---

## Phase 26 Artifacts (COMPLETE)

- `.planning/phases/26-llm-powered-expansion/26-RESEARCH.md`
- `.planning/phases/26-llm-powered-expansion/26-01-PLAN.md` (ComplexityDetectorService) - COMPLETE
- `.planning/phases/26-llm-powered-expansion/26-01-SUMMARY.md`
- `.planning/phases/26-llm-powered-expansion/26-02-PLAN.md` (LLMExpansionService) - COMPLETE
- `.planning/phases/26-llm-powered-expansion/26-02-SUMMARY.md`
- `.planning/phases/26-llm-powered-expansion/26-03-PLAN.md` (Integration) - COMPLETE
- `.planning/phases/26-llm-powered-expansion/26-03-SUMMARY.md`
- `.planning/phases/26-llm-powered-expansion/26-04-PLAN.md` (Gap Closure) - COMPLETE
- `.planning/phases/26-llm-powered-expansion/26-04-SUMMARY.md`

Key Files Created (Phase 26):
- `modules/AppVideoWizard/app/Services/ComplexityDetectorService.php`
- `modules/AppVideoWizard/app/Services/LLMExpansionService.php`
- `tests/Unit/VideoWizard/ComplexityDetectorServiceTest.php`
- `tests/Unit/VideoWizard/LLMExpansionServiceTest.php`
- `tests/Feature/VideoWizard/LLMExpansionIntegrationTest.php`

Key Files Modified (Phase 26):
- `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php` (buildHollywoodPrompt, LLM routing, build() delegation)
- `tests/Feature/VideoWizard/LLMExpansionIntegrationTest.php` (added test_build_delegates_to_hollywood_prompt)

---

## Phase 25 Artifacts (COMPLETE)

- `.planning/phases/25-voice-prompt-enhancement/25-RESEARCH.md`
- `.planning/phases/25-voice-prompt-enhancement/25-01-PLAN.md` (VoiceDirectionVocabulary) - COMPLETE
- `.planning/phases/25-voice-prompt-enhancement/25-01-SUMMARY.md`
- `.planning/phases/25-voice-prompt-enhancement/25-02-PLAN.md` (VoicePacingService) - COMPLETE
- `.planning/phases/25-voice-prompt-enhancement/25-02-SUMMARY.md`
- `.planning/phases/25-voice-prompt-enhancement/25-03-PLAN.md` (VoicePromptBuilder Integration) - COMPLETE
- `.planning/phases/25-voice-prompt-enhancement/25-03-SUMMARY.md`

Key Files Created (Phase 25):
- `modules/AppVideoWizard/app/Services/VoiceDirectionVocabulary.php`
- `modules/AppVideoWizard/app/Services/VoicePacingService.php`
- `modules/AppVideoWizard/app/Services/VoicePromptBuilderService.php`
- `tests/Unit/VideoWizard/VoiceDirectionVocabularyTest.php`
- `tests/Unit/VideoWizard/VoicePacingServiceTest.php`
- `tests/Unit/VideoWizard/VoicePromptBuilderServiceTest.php`
- `tests/Feature/VideoWizard/VoicePromptIntegrationTest.php`

---

## Phase 24 Artifacts (COMPLETE)

- `.planning/phases/24-video-temporal-expansion/24-RESEARCH.md`
- `.planning/phases/24-video-temporal-expansion/24-01-PLAN.md` (VideoTemporalService) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-01-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-02-PLAN.md` (CharacterDynamicsService) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-02-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-03-PLAN.md` (Transition Vocabulary + Temporal Movement) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-03-SUMMARY.md`
- `.planning/phases/24-video-temporal-expansion/24-04-PLAN.md` (Integration) - COMPLETE
- `.planning/phases/24-video-temporal-expansion/24-04-SUMMARY.md`

Key Files Created (Phase 24):
- `modules/AppVideoWizard/app/Services/VideoTemporalService.php`
- `modules/AppVideoWizard/app/Services/MicroMovementService.php`
- `modules/AppVideoWizard/app/Services/CharacterDynamicsService.php`
- `modules/AppVideoWizard/app/Services/CharacterPathService.php`
- `modules/AppVideoWizard/app/Services/TransitionVocabulary.php`
- `tests/Unit/VideoWizard/VideoTemporalServiceTest.php`
- `tests/Unit/VideoWizard/MicroMovementServiceTest.php`
- `tests/Unit/VideoWizard/CharacterDynamicsServiceTest.php`
- `tests/Unit/VideoWizard/CharacterPathServiceTest.php`
- `tests/Unit/VideoWizard/TransitionVocabularyTest.php`
- `tests/Unit/VideoWizard/CameraMovementServiceTemporalTest.php`
- `tests/Feature/VideoWizard/VideoTemporalIntegrationTest.php`

Key Files Modified (Phase 24):
- `modules/AppVideoWizard/app/Services/CameraMovementService.php` (MOVEMENT_PSYCHOLOGY, buildTemporalMovementPrompt)
- `modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php` (buildTemporalVideoPrompt integration)

Tests (Phase 24):
- `tests/Unit/VideoWizard/VideoTemporalServiceTest.php`
- `tests/Unit/VideoWizard/MicroMovementServiceTest.php`
- `tests/Unit/VideoWizard/CharacterDynamicsServiceTest.php`
- `tests/Unit/VideoWizard/CharacterPathServiceTest.php`
- `tests/Unit/VideoWizard/TransitionVocabularyTest.php`
- `tests/Unit/VideoWizard/CameraMovementServiceTemporalTest.php`
- `tests/Feature/VideoWizard/VideoTemporalIntegrationTest.php`

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
