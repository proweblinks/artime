# Video Wizard

## What This Is

AI-powered video creation platform built with Laravel and Livewire. Users input a concept and the system automatically generates scripts, storyboards, images, and videos with Hollywood-quality cinematography. The wizard guides users through 7 steps: Concept → Characters → Script → Storyboard → Animation → Audio → Export.

## Core Value

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

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

### Active

<!-- Current scope. Building toward these. -->

- [ ] **STI-01**: Scene Text Inspector modal with full transparency
- [ ] **STI-02**: Correct speech type labels (NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE)
- [ ] **STI-03**: Type-specific icons and colors per segment
- [ ] **STI-04**: Auto-detection of speech types from content
- [ ] **STI-05**: Full prompts visible (image, video, metadata)

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Real-time collaboration — complexity, not core to video creation
- Mobile app — web-first approach
- Video editing timeline — use external tools for post-production

## Context

**Technical environment:**
- Laravel 10 + Livewire 3
- Main component: VideoWizard.php (~18k lines)
- Services: SpeechSegmentParser, SpeechSegment, NarrativeMomentService, ShotIntelligenceService
- Image generation: HiDream, NanoBanana Pro, NanoBanana
- Video generation: Runway, other providers

**Current issue:**
- Storyboard shows hardcoded "Dialogue" label for ALL text, even narrator
- Users cannot see full text content (truncated to 80 chars, max 2 segments)
- No way to inspect all prompts and metadata for a scene

## Constraints

- **Tech stack**: Laravel + Livewire (existing architecture)
- **File structure**: Must follow existing module pattern in `modules/AppVideoWizard/`
- **UI consistency**: Must match existing vw-* CSS class naming

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| SpeechSegment types: narrator, dialogue, internal, monologue | Cover all Hollywood speech patterns | ✓ Good |
| Lip-sync only for dialogue/monologue | Narrator and internal are voiceover only | ✓ Good |
| Purple for speaker names | Consistent with app color scheme | ✓ Good |
| Type icons: 🎙️💬💭🗣️ | Immediate visual recognition | — Pending |

---
*Last updated: 2026-01-23 after Milestone 7 start*
