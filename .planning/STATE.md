# Video Wizard - Current State

> Last Updated: 2026-01-27
> Session: M11.2 Complete

---

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-27)

**Core value:** Automatic, effortless, Hollywood-quality output from button clicks
**Current focus:** Ready for next milestone

---

## Current Position

**Milestone:** 11.2 (Prompt Pipeline Integration) — Complete
**Phase:** All complete
**Status:** Milestone shipped

```
Phase 29:   ██████████ 100% (3/3 plans complete)
Phase 29.1: ██████████ 100% (2/2 plans complete)
---------------------
M11.2:      ██████████ 100% (4/4 requirements + 3/3 debt)
```

**Last activity:** 2026-01-27 - M11.2 milestone completed and archived

---

## What Shipped (v11 + M11.1 + M11.2)

**8 phases, 31 plans, 38 requirements:**

### v11 (Phases 22-27)
- Phase 22: Foundation & Model Adapters (CinematographyVocabulary, CLIP tokenization)
- Phase 23: Character Psychology & Bible (physical manifestations, mise-en-scene)
- Phase 24: Video Temporal Expansion (beats, dynamics, transitions)
- Phase 25: Voice Prompt Enhancement (emotional direction, pacing, SSML)
- Phase 26: LLM-Powered Expansion (complexity detection, AI expansion)
- Phase 27: UI & Performance Polish (caching, comparison, toggle)

### M11.1 (Phase 28)
- Phase 28: Voice Production Excellence (registry, continuity, multi-speaker)

### M11.2 (Phases 29, 29.1)
- Phase 29: Prompt Pipeline Integration (Character/Location DNA, voice UI, defaults)
- Phase 29.1: Integration Consistency Fixes (fallbacks, emotion inheritance, enhanced prompts)

**Key services created:**
- CinematographyVocabulary, PromptTemplateLibrary, ModelPromptAdapterService
- CharacterPsychologyService, MiseEnSceneService, ContinuityAnchorService
- VideoTemporalService, MicroMovementService, CharacterDynamicsService, CharacterPathService, TransitionVocabulary
- VoiceDirectionVocabulary, VoicePacingService, VoicePromptBuilderService
- ComplexityDetectorService, LLMExpansionService
- VoiceContinuityValidator, MultiSpeakerDialogueBuilder

---

## Accumulated Context

### Key Decisions (v11)

Major decisions logged in PROJECT.md. Highlights:

- Physical manifestations over FACS AU codes (research validation)
- Grok as primary LLM for expansion ($0.20/1M tokens)
- 3+ characters ALWAYS triggers LLM complexity
- Subject NEVER removed during CLIP compression

### Key Decisions (M11.1)

| Date       | Plan  | Decision                                            |
|------------|-------|-----------------------------------------------------|
| 2026-01-27 | 28-01 | Voice registry stored in sceneDNA.voiceRegistry    |
| 2026-01-27 | 28-01 | Restoration in loadProject after sceneMemory merge |
| 2026-01-27 | 28-02 | VoicePromptBuilderService for emotion preview      |
| 2026-01-27 | 28-02 | Emotions from VoiceDirectionVocabulary constants   |
| 2026-01-27 | 28-03 | Named validateVoiceContinuityForUI to avoid method collision |
| 2026-01-27 | 28-03 | Only ISSUE_VOICE_DRIFT shown in UI (not info-level issues)   |
| 2026-01-27 | 28-04 | Enhancement applied before TTS in scene/segment flows        |
| 2026-01-27 | 28-04 | Instructions via options for backward compatibility          |
| 2026-01-27 | 28-05 | 0.3s speaker transition pause for natural dialogue flow      |
| 2026-01-27 | 28-05 | Hash-based fallback for consistent voice assignment          |

### Key Decisions (M11.2)

| Date       | Plan  | Decision                                            |
|------------|-------|-----------------------------------------------------|
| 2026-01-27 | 29-01 | All 6 locations updated for nanobanana-pro default          |
| 2026-01-27 | 29-02 | sceneIndex default 0 for backward compatibility             |
| 2026-01-27 | 29-02 | Character/Location Bible placed after shot type, before visual description |
| 2026-01-27 | 29-03 | Purple styling for voice prompts (rgba(139, 92, 246))       |
| 2026-01-27 | 29-03 | Dialogue > monologue > narration cascade for voice text     |
| 2026-01-27 | 29-03 | Emotion tag in pink to differentiate from voice text        |
| 2026-01-27 | 29.1-01 | Emotion field placed after monologue in shot array         |
| 2026-01-27 | 29.1-01 | replace_all used for batch hidream->nanobanana-pro update  |
| 2026-01-27 | 29.1-02 | Static buildSimpleEnhancedPrompt for blade template usage  |
| 2026-01-27 | 29.1-02 | Direction description amber/gold, emotion tag pink         |

### Roadmap Evolution

- v11 complete (Phases 22-27)
- M11.1 complete (Phase 28)
- M11.2 complete (Phases 29, 29.1)
- v10 remains paused (Livewire Performance)

### Pending Todos

None.

### Blockers/Concerns

None.

---

## Session Continuity

Last session: 2026-01-27
Stopped at: M11.2 milestone completed and archived
Resume file: None
Next step: /gsd:new-milestone for next milestone

---

## Archive Reference

Milestone artifacts archived to `.planning/milestones/`:
- v11-ROADMAP.md
- v11-REQUIREMENTS.md
- v11-MILESTONE-AUDIT.md
- v11-INTEGRATION-CHECK.md
- M11.2-ROADMAP.md
- M11.2-REQUIREMENTS.md
- M11.2-AUDIT.md

Phase directories remain in `.planning/phases/` (22-* through 29.1-*)
