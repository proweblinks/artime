# Video Wizard - Current State

> Last Updated: 2026-01-27
> Session: Executing M11.2

---

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-27)

**Core value:** Automatic, effortless, Hollywood-quality output from button clicks
**Current focus:** M11.2 Prompt Pipeline Integration (Phase 29)

---

## Current Position

**Milestone:** 11.2 (Prompt Pipeline Integration)
**Phase:** 29 of 29 (Prompt Pipeline Integration)
**Plan:** 3 of 4 (Voice Prompt Display in Shot Preview)
**Status:** In progress

```
Phase 29: ███░░░░░░░ 30% (3/10 tasks estimated)
---------------------
M11.2:    ███░░░░░░░ 30% (1/4 requirements)
```

**Last activity:** 2026-01-27 - Completed 29-03-PLAN.md (Voice Prompt Display)

---

## What Shipped (v11)

**6 phases, 21 plans, 25 requirements:**

- Phase 22: Foundation & Model Adapters (CinematographyVocabulary, CLIP tokenization)
- Phase 23: Character Psychology & Bible (physical manifestations, mise-en-scene)
- Phase 24: Video Temporal Expansion (beats, dynamics, transitions)
- Phase 25: Voice Prompt Enhancement (emotional direction, pacing, SSML)
- Phase 26: LLM-Powered Expansion (complexity detection, AI expansion)
- Phase 27: UI & Performance Polish (caching, comparison, toggle)

**Key services created:**
- CinematographyVocabulary, PromptTemplateLibrary, ModelPromptAdapterService
- CharacterPsychologyService, MiseEnSceneService, ContinuityAnchorService
- VideoTemporalService, MicroMovementService, CharacterDynamicsService, CharacterPathService, TransitionVocabulary
- VoiceDirectionVocabulary, VoicePacingService, VoicePromptBuilderService
- ComplexityDetectorService, LLMExpansionService

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
| 2026-01-27 | 29-03 | Purple styling for voice prompts (rgba(139, 92, 246))       |
| 2026-01-27 | 29-03 | Dialogue > monologue > narration cascade for voice text     |
| 2026-01-27 | 29-03 | Emotion tag in pink to differentiate from voice text        |

### Roadmap Evolution

- Phase 29 in progress (Prompt Pipeline Integration)
- v10 remains paused (Livewire Performance)

### Pending Todos

None.

### Blockers/Concerns

None.

---

## Session Continuity

Last session: 2026-01-27
Stopped at: Completed 29-03-PLAN.md
Resume file: None
Next step: Execute 29-04-PLAN.md

---

## Archive Reference

Milestone artifacts archived to `.planning/milestones/`:
- v11-ROADMAP.md
- v11-REQUIREMENTS.md
- v11-MILESTONE-AUDIT.md
- v11-INTEGRATION-CHECK.md

Phase directories remain in `.planning/phases/` (22-* through 27-*).
