# Project Milestones: Video Wizard

## M11.2 Prompt Pipeline Integration (Shipped: 2026-01-27)

**Delivered:** Wired comprehensive prompts to shot generation, added voice prompt visibility in UI, fixed default model to nanobanana-pro. Closed all tech debt from audit.

**Phases completed:** 29, 29.1 (5 plans total)

**Key accomplishments:**

- Shot prompts now include Character DNA (descriptions from Scene Memory)
- Shot prompts now include Location DNA (details from Scene Memory)
- Voice prompts visible in Shot Preview modal with emotional direction
- Default image model upgraded from nanobanana (1t) to nanobanana-pro (3t)
- VoicePromptBuilderService wired to UI with enhanced prompts
- All 6 imageModel fallback locations consistent

**Tech debt closed:**

- DEBT-01: Consistent imageModel fallbacks across all generation paths
- DEBT-02: Emotion data inheritance from speech segments to shots
- DEBT-03: VoicePromptBuilderService integration in Shot Preview

**Stats:**

- 4 files modified
- 2 phases, 5 plans
- ~150 lines of PHP added
- 1 day from start to ship

**Git range:** `feat(29-01): update default imageModel` → `docs(29.1): complete integration consistency fixes phase`

**What's next:** M11.3 or v12 planning

---

## M11.1 Voice Production Excellence (Shipped: 2026-01-27)

**Delivered:** Complete voice production pipeline with registry, continuity validation, and multi-speaker support.

**Phases completed:** 28 (5 plans total)

**Key accomplishments:**

- Voice Registry persists character-voice mappings across scenes
- Voice Continuity Validation ensures settings match across scenes
- Enhanced SSML Markup with full emotional direction support
- Multi-Speaker Dialogue handles conversations in single generation
- Voice selection UI in Character Bible modal

**Stats:**

- ~500 lines of PHP added
- 1 phase, 5 plans
- 6 requirements (VOC-07 through VOC-12)

**Git range:** Phase 28 commits

**What's next:** M11.2 (Prompt Pipeline Integration)

---

## v11 Hollywood-Quality Prompt Pipeline (Shipped: 2026-01-27)

**Delivered:** Transformed prompt generation from 50-80 words to 600-1000 word Hollywood screenplay-level prompts for image, video, and voice generation with automatic LLM expansion for complex shots.

**Phases completed:** 22-27 (21 plans total)

**Key accomplishments:**

- CinematographyVocabulary with lens psychology, lighting ratios, and color temperatures
- CharacterPsychologyService mapping emotions to physical manifestations (not FACS AU codes)
- VideoTemporalService with beat-by-beat timing and camera movement psychology
- VoicePromptBuilderService with emotional arcs and provider-specific formatting
- LLMExpansionService for AI-enhanced prompts on complex shots (3+ characters)
- ModelPromptAdapterService with CLIP tokenization (77-token compression)

**Stats:**

- 34 files created/modified
- ~6,700 lines of PHP
- 6 phases, 21 plans
- 3 days from start to ship

**Git range:** `docs(22): gather context` → `docs(m11): complete milestone audit`

**What's next:** Phase 28 (Voice Production Excellence) or v12 planning

---
