# Requirements: Video Wizard - Hollywood-Quality Prompt Pipeline

**Defined:** 2026-01-25
**Core Value:** Automatic, effortless, Hollywood-quality output from button clicks

## v11 Requirements

Requirements for Milestone 11. Each maps to roadmap phases.

### Image Prompts (IMG)

**Table Stakes:**
- [ ] **IMG-01**: Image prompts include camera specs with psychological reasoning (lens choice affects viewer perception)
- [ ] **IMG-02**: Image prompts include quantified framing (percentage of frame, compositional geometry)
- [ ] **IMG-03**: Image prompts include lighting with specific ratios (key/fill/back, color temperatures in Kelvin)
- [x] **IMG-04**: Image prompts include micro-expressions using physical manifestations (research: FACS AU codes don't work for image models)
- [x] **IMG-05**: Image prompts include body language with specific posture/gesture descriptions
- [x] **IMG-06**: Image prompts include emotional state visible in physicality (not labels like "sad" but physical manifestations)

**Differentiators:**
- [x] **IMG-07**: Image prompts include subtext layer (what character hides vs reveals through body language)
- [x] **IMG-08**: Image prompts include mise-en-scene integration (environment reflects/contrasts emotional state)
- [x] **IMG-09**: Image prompts include continuity anchors (exact details that must persist across shots)

### Video Prompts (VID)

**Table Stakes:**
- [x] **VID-01**: Video prompts include all image prompt features
- [x] **VID-02**: Video prompts include temporal progression with beat-by-beat timing (0-2s: action, 2-4s: reaction)
- [x] **VID-03**: Video prompts include camera movement with duration and psychological purpose
- [x] **VID-04**: Video prompts include character movement paths within frame

**Differentiators:**
- [x] **VID-05**: Video prompts include inter-character dynamics (mirroring, spatial power relationships)
- [x] **VID-06**: Video prompts include breath and micro-movements for realism
- [x] **VID-07**: Video prompts include transition suggestions to next shot

### Voice Prompts (VOC)

**Table Stakes:**
- [ ] **VOC-01**: Voice prompts include emotional direction tags (trembling, whisper, cracking)
- [ ] **VOC-02**: Voice prompts include pacing markers with timing ([PAUSE 2.5s])
- [ ] **VOC-03**: Voice prompts include vocal quality descriptions (gravelly, exhausted, breathless)

**Differentiators:**
- [ ] **VOC-04**: Voice prompts include ambient audio cues for scene atmosphere
- [ ] **VOC-05**: Voice prompts include breath and non-verbal sounds
- [ ] **VOC-06**: Voice prompts include emotional arc direction across dialogue sequence

### Infrastructure (INF)

**Table Stakes:**
- [ ] **INF-01**: Model adapters handle token limits (77-token CLIP limit for image models)
- [x] **INF-02**: Bible integration preserves character/location/style data in expanded prompts
- [ ] **INF-03**: Template library organized by shot type (close-up needs face detail, wide needs environment)

**Differentiators:**
- [ ] **INF-04**: LLM-powered expansion for complex shots that exceed template capability
- [ ] **INF-05**: Prompt caching for performance (avoid re-expanding identical contexts)
- [ ] **INF-06**: Prompt comparison view in UI (before/after expansion, word count)

## Future Requirements

Deferred to later milestones.

### Advanced Continuity (v12+)

- **CONT-01**: Wardrobe tracking across scenes (same clothing details)
- **CONT-02**: Prop continuity (same items in consistent positions)
- **CONT-03**: Time-of-day consistency (matching shadows, light quality)
- **CONT-04**: Character aging/progression over timeline

### Multi-Model Optimization (v12+)

- **OPT-01**: Model-specific prompt variants (Midjourney vs Stable Diffusion vs Flux)
- **OPT-02**: A/B testing of prompt variations for quality scoring
- **OPT-03**: Automatic prompt tuning based on generation results

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Real-time prompt editing | Complexity, prompts are auto-generated |
| User-written custom prompts | Against "effortless" core value |
| Prompt marketplace/sharing | Not core to video creation |
| Training custom models | Requires ML infrastructure beyond scope |
| Multi-language prompts | English-first, internationalization later |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| INF-01 | Phase 22 | Complete |
| INF-02 | Phase 23 | Complete |
| INF-03 | Phase 22 | Complete |
| INF-04 | Phase 26 | Pending |
| INF-05 | Phase 27 | Pending |
| INF-06 | Phase 27 | Pending |
| IMG-01 | Phase 22 | Complete |
| IMG-02 | Phase 22 | Complete |
| IMG-03 | Phase 22 | Complete |
| IMG-04 | Phase 23 | Complete |
| IMG-05 | Phase 23 | Complete |
| IMG-06 | Phase 23 | Complete |
| IMG-07 | Phase 23 | Complete |
| IMG-08 | Phase 23 | Complete |
| IMG-09 | Phase 23 | Complete |
| VID-01 | Phase 24 | Complete |
| VID-02 | Phase 24 | Complete |
| VID-03 | Phase 24 | Complete |
| VID-04 | Phase 24 | Complete |
| VID-05 | Phase 24 | Complete |
| VID-06 | Phase 24 | Complete |
| VID-07 | Phase 24 | Complete |
| VOC-01 | Phase 25 | Pending |
| VOC-02 | Phase 25 | Pending |
| VOC-03 | Phase 25 | Pending |
| VOC-04 | Phase 25 | Pending |
| VOC-05 | Phase 25 | Pending |
| VOC-06 | Phase 25 | Pending |

**Coverage:**
- v11 requirements: 25 total
- Mapped to phases: 25
- Unmapped: 0

---
*Requirements defined: 2026-01-25*
*Roadmap created: 2026-01-25*
*Source: Research .planning/research/SUMMARY.md*
