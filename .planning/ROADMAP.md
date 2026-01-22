# Video Wizard Development Roadmap

## Milestone 1: Stability & Bug Fixes
**Target:** Eliminate critical bugs, establish stable baseline
**Status:** 80% Complete

| Task | Status | Priority |
|------|--------|----------|
| Fix dialogue parsing by speaker | Done | P0 |
| Fix needsLipSync on all dialogue shots | Done | P0 |
| Fix Collage Preview empty state | Done | P0 |
| Remove duplicate methods | Done | P0 |
| Add AI retry logic | Pending | P1 |
| Fix error handling | Pending | P1 |

---

## Milestone 1.5: Automatic Speech Flow System -- COMPLETE
**Target:** Remove Character Intelligence bottleneck, connect Speech Segments to Character Bible for automatic flow
**Status:** Complete (2026-01-23)
**Plans:** 4 plans in 3 waves -- ALL COMPLETE

**Goal:** Automatic, effortless, Hollywood-quality output from button clicks. Script is auto-parsed into speech segments, speakers are auto-linked to Character Bible, and data flows through to video generation without manual intervention.

Plans:
- [x] 1.5-01-PLAN.md -- Auto-parse script into segments after AI generation
- [x] 1.5-02-PLAN.md -- Replace Character Intelligence UI with Detection Summary
- [x] 1.5-03-PLAN.md -- Backward compatibility for characterIntelligence
- [x] 1.5-04-PLAN.md -- Ensure segment data flows to shots/video generation

| Wave | Plans | Focus | Status |
|------|-------|-------|--------|
| 1 | 1.5-01 | Core auto-parsing infrastructure | Done |
| 2 | 1.5-02, 1.5-03 | UI replacement + backward compatibility | Done |
| 3 | 1.5-04 | End-to-end data flow verification | Done |

---

## Milestone 2: Narrative Intelligence <-- CURRENT
**Target:** Each shot captures unique moment with emotional arc
**Status:** Planned (2026-01-23)
**Plans:** 3 plans in 2 waves

**Goal:** Integrate existing NarrativeMomentService (711 lines, 80% complete) into shot generation workflow. Each shot gets a unique narrative moment with emotional intensity mapping to shot type.

**Key Finding:** NarrativeMomentService already exists with AI-first decomposition, 47 action-emotion mappings, and Hollywood-standard intensity-to-shot-type mapping. Phase 2 is INTEGRATION, not implementation.

Plans:
- [ ] 02-01-PLAN.md -- Wire NarrativeMomentService into ShotIntelligenceService
- [ ] 02-02-PLAN.md -- Enhance AI prompt with narrative moments
- [ ] 02-03-PLAN.md -- Add action uniqueness validation

| Wave | Plans | Focus |
|------|-------|-------|
| 1 | 02-01 | Service dependency injection |
| 2 | 02-02, 02-03 | Prompt enhancement + deduplication (parallel) |

**Hollywood-Informed Intensity Mapping:**
- 0.85-1.0: Extreme close-up (peak emotional)
- 0.7-0.85: Close-up (high emotion)
- 0.55-0.7: Medium close-up
- 0.4-0.55: Medium (dialogue)
- 0.25-0.4: Wide (context)
- 0.0-0.25: Establishing

---

## Milestone 3: Character Consistency
**Target:** Same character face across all shots
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Extract portraits from collage | Pending | HIGH |
| Store in Character Bible | Pending | HIGH |
| Pass reference to generation | Pending | HIGH |
| Validate visual consistency | Pending | MEDIUM |

---

## Milestone 4: Dialogue Scene Excellence
**Target:** Hollywood-style Shot/Reverse Shot coverage
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Implement S/RS pattern | Pending | HIGH |
| OTS shot detection | Pending | MEDIUM |
| Reaction shot placeholders | Pending | MEDIUM |
| Two-character coverage | Pending | HIGH |

---

## Milestone 5: Emotional Arc System
**Target:** Intensity-driven cinematography
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Build intensity curve extraction | Pending | HIGH |
| Emotion to shot type mapping | Pending | HIGH |
| Climax framing logic | Pending | MEDIUM |
| Arc visualization | Pending | LOW |

---

## Milestone 6: UI/UX Polish
**Target:** Professional, intuitive interface
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Dialogue text in shot cards | Pending | MEDIUM |
| Shot type badges | Pending | LOW |
| Progress indicators | Pending | MEDIUM |
| Live preview updates | Pending | LOW |

---

## Progress Overview

```
Milestone 1:   ████████░░ 80%
Milestone 1.5: ██████████ 100% COMPLETE
Milestone 2:   █░░░░░░░░░ 10% PLANNED
Milestone 3:   ░░░░░░░░░░  0%
Milestone 4:   ░░░░░░░░░░  0%
Milestone 5:   ░░░░░░░░░░  0%
Milestone 6:   ░░░░░░░░░░  0%
─────────────────────────
Overall:       ██░░░░░░░░ 28%
```

---

## Success Metrics

| Metric | Target | Current |
|--------|--------|---------|
| Duplicate shot descriptions | 0 | Unknown |
| Character consistency | 100% | ~60% |
| Lip-sync coverage | 100% | ~50% |
| AI retry success | 95% | 0% |
| User satisfaction | High | Medium |

---

*Last Updated: 2026-01-23*
