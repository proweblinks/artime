# Video Wizard

## What This Is

AI-powered video creation platform built with Laravel and Livewire. Users input a concept and the system automatically generates scripts, storyboards, images, and videos with Hollywood-quality cinematography. The wizard guides users through 7 steps: Concept → Characters → Script → Storyboard → Animation → Audio → Export.

## Core Value

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

## Current Milestone: v8 Cinematic Shot Architecture

**Goal:** Transform scene decomposition so every shot is purposeful, speech-driven, and cinematically connected.

**Target features:**
- Speech-to-Shot Mapping — Each dialogue/monologue segment creates its own shot(s)
- Shot/Reverse-Shot Pattern — Proper conversation coverage with alternating characters
- Dynamic Camera Selection — Vary CU/MS/OTS based on emotional intensity and position
- Continuous Flow — Shots build cinematically on each other
- Single-Character Focus — One character per shot (model constraint → feature)
- Narrator Overlay — Narrator spans multiple shots, not dedicated shots
- Unlimited Shots — 10+ shots per scene if speech demands it
- Action Scene Improvement — Better decomposition for non-dialogue scenes

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

### Active

<!-- Current scope. Building toward these. -->

- [ ] **CSA-01**: Speech-driven shot count — each dialogue/monologue segment creates at least one shot
- [ ] **CSA-02**: Narrator overlay — narrator segments span multiple shots, not dedicated
- [ ] **CSA-03**: Single character per shot — enforce one speaking character per shot
- [ ] **CSA-04**: Dynamic camera variety — vary CU/MS/OTS based on intensity AND position
- [ ] **CSA-05**: Shot flow continuity — shots connect cinematically (no jarring transitions)
- [ ] **CSA-06**: Improved action decomposition — non-dialogue scenes get better shot variety
- [ ] **CSA-07**: Unlimited shots per scene — remove artificial limits, speech drives count
- [ ] **CSA-08**: Character emotion matching — shot type matches speaker's emotional state

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Real-time collaboration — complexity, not core to video creation
- Mobile app — web-first approach
- Video editing timeline — use external tools for post-production
- Multi-character in single shot — model limitation, embrace as creative constraint

## Context

**Technical environment:**
- Laravel 10 + Livewire 3
- Main component: VideoWizard.php (~18k lines)
- Services: SpeechSegmentParser, SpeechSegment, NarrativeMomentService, ShotIntelligenceService
- Image generation: HiDream, NanoBanana Pro, NanoBanana
- Video generation: Runway, Multitalk (single character lip-sync)

**Existing architecture (from M4):**
- DialogueSceneDecomposerService — shot/reverse-shot, 180-degree rule, reactions
- DynamicShotEngine — content-driven shot count, intensity mapping
- Speech segment distribution — currently proportional (needs to become 1:1)

**Current issue:**
- Speech segments distributed proportionally across shots instead of driving shot creation
- Scenes don't produce continuous, cinematic shot sequences
- Dialogue doesn't naturally flow shot-to-shot with alternating characters

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
| Speech-to-shot 1:1 mapping | Each speech segment drives its own shot | — Pending (M8) |
| Narrator overlay pattern | Narrator spans shots, not dedicated | — Pending (M8) |

---
*Last updated: 2026-01-23 after Milestone 8 start*
