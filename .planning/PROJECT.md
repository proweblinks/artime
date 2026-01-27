# Video Wizard

## What This Is

AI-powered video creation platform built with Laravel and Livewire. Users input a concept and the system automatically generates scripts, storyboards, images, and videos with Hollywood-quality cinematography. The wizard guides users through 7 steps: Concept → Characters → Script → Storyboard → Animation → Audio → Export.

## Core Value

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

## Current State

**Shipped:** M11.2 Prompt Pipeline Integration (2026-01-27)
- Shot prompts include Character DNA (descriptions from Scene Memory)
- Shot prompts include Location DNA (details from Scene Memory)
- Voice prompts visible in Shot Preview modal with emotional direction
- Default image model upgraded to nanobanana-pro (3 tokens)
- VoicePromptBuilderService wired to UI with enhanced prompts
- All tech debt from M11.2 audit closed

**Also shipped:** M11.1 Voice Production Excellence (2026-01-27)
- Voice Registry persists character-voice mappings across scenes
- Voice Continuity Validation ensures settings match
- Enhanced SSML Markup with emotional direction
- Multi-Speaker Dialogue handles conversations in single generation
- Voice selection UI in Character Bible modal

**Foundation:** v11 Hollywood-Quality Prompt Pipeline (2026-01-27)
- 600-1000 word prompts with camera psychology, lighting ratios, physical manifestations
- Video temporal beats, character dynamics, camera movement psychology
- Voice emotional direction, pacing markers, provider-specific formatting
- LLM expansion for complex shots (3+ characters)
- CLIP tokenization (77-token compression)

**Paused:** v10 Livewire Performance Architecture
- Phase 19 complete (Quick Wins)
- Phases 20-21 deferred (Component Splitting, Data Normalization)

**Next:** v12 planning or new milestone

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ **M1**: Stability & Bug Fixes — dialogue parsing, needsLipSync, error handling
- ✓ **M1.5**: Automatic Speech Flow — auto-parse scripts, Detection Summary UI, segment data flow
- ✓ **M2**: Narrative Intelligence — NarrativeMomentService integration, unique moments per shot
- ✓ **M3**: Hollywood Production System — Hollywood shot sequences, auto-proceed, smart retry, character consistency
- ✓ **M4**: Dialogue Scene Excellence — 180-degree rule, OTS depth, reaction shots, coverage validation
- ✓ **M5**: Emotional Arc System — climax detection, intensity smoothing, arc templates
- ✓ **M6**: UI/UX Polish — dialogue display, shot badges, progress indicators, visual consistency
- ✓ **M7**: Scene Text Inspector — full transparency modal, speech segments, prompts, copy-to-clipboard
- ✓ **M8**: Cinematic Shot Architecture — speech-driven shots, shot/reverse-shot, dynamic camera, action scenes
- ✓ **M9**: Voice Production Excellence — narrator voice, validation, continuity, registry, multi-speaker
- ✓ **M11**: Hollywood-Quality Prompt Pipeline — camera psychology, physical manifestations, temporal beats, LLM expansion, CLIP tokenization (25 requirements)
- ✓ **M11.1**: Voice Production Excellence — voice registry, continuity validation, multi-speaker, SSML (6 requirements)
- ✓ **M11.2**: Prompt Pipeline Integration — Character/Location DNA in shots, voice prompt UI, quality defaults (7 requirements)

### Active

<!-- Current scope. Building toward these. -->

- [ ] **PERF-01**: Livewire 3 attributes — #[Locked] for constants, #[Computed] for derived values
- [ ] **PERF-02**: Debounced bindings — wire:model.blur and .debounce instead of .live
- [ ] **PERF-03**: Base64 storage migration — images stored in files, lazy-loaded for API calls
- [ ] **PERF-04**: Child components — separate Livewire components per wizard step
- [ ] **PERF-05**: Modal components — separate components for Character Bible, Location Bible, etc.
- [ ] **PERF-06**: Database models — WizardScene, WizardShot models instead of nested arrays
- [ ] **PERF-07**: Lazy loading — scene data loaded on-demand, not all at once
- [ ] **PERF-08**: Updated hook optimization — efficient property change handling

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Real-time collaboration — complexity, not core to video creation
- Mobile app — web-first approach
- Video editing timeline — use external tools for post-production
- Multi-character in single shot — model limitation, embrace as creative constraint

## Context

**Technical environment:**
- Laravel 10 + Livewire 3
- Main component: VideoWizard.php (~31k lines — performance bottleneck)
- Services: SpeechSegmentParser, SpeechSegment, NarrativeMomentService, ShotIntelligenceService
- Image generation: HiDream, NanoBanana Pro, NanoBanana
- Video generation: Runway, Multitalk (single character lip-sync)

**M8 Foundation (complete):**
- DialogueSceneDecomposerService — speech-driven shots, shot/reverse-shot, emotion analysis
- SceneTypeDetectorService — routes dialogue/action/mixed scenes
- ShotContinuityService — jump cut prevention, coverage patterns
- Transition validation — scale changes enforced between consecutive shots

**Current issues (from audit):**
- Narrator voice not assigned — overlayNarratorSegments() sets narratorText but NOT narratorVoiceId
- Single speaker per shot — only first speaker's voice used: array_keys($speakers)[0]
- No voice continuity — same character could get different voices across scenes
- Internal thought asymmetry — narrator uses word-split, internal uses segment-split
- Silent type coercion — missing segment type defaults to 'narrator' without error
- Empty text validation — empty segments can reach TTS generation

**Industry standards (2025):**
- Dia 1.6B TTS — speaker tags [S1], [S2] for consistent multi-voice dialogue
- Microsoft VibeVoice — 90 min speech with 4 distinct speakers
- Google Gemini 2.5 TTS — seamless dialogue with consistent character voices
- MultiTalk (MeiGen-AI) — audio-driven multi-person conversational video

## Constraints

- **Tech stack**: Laravel + Livewire (existing architecture)
- **File structure**: Must follow existing module pattern in `modules/AppVideoWizard/`
- **UI consistency**: Must match existing vw-* CSS class naming
- **Video model**: Multitalk supports single character per shot — design around this

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| SpeechSegment types: narrator, dialogue, internal, monologue | Cover all Hollywood speech patterns | ✓ Good |
| Lip-sync only for dialogue/monologue | Narrator and internal are voiceover only | ✓ Good |
| Purple for speaker names | Consistent with app color scheme | ✓ Good |
| Type icons: 🎙️💬💭🗣️ | Immediate visual recognition | ✓ Good |
| M4 DialogueSceneDecomposerService | Foundation for shot/reverse-shot | ✓ Good - will extend |
| Speech-to-shot 1:1 mapping | Each speech segment drives its own shot | ✓ Good (M8) |
| Narrator overlay pattern | Narrator spans shots, not dedicated | ✓ Good (M8) |
| Jump cut prevention | Validate transitions, enforce scale changes | ✓ Good (M8) |
| Action coverage pattern | Use ShotContinuityService for action scenes | ✓ Good (M8) |
| Voice Registry pattern | Centralized voice assignment (from audit) | — Pending (M9) |
| Multi-speaker tracking | Multiple speakers per shot for dialogue | — Pending (M9) |

---
*Last updated: 2026-01-27 after M11.2 milestone completion*
