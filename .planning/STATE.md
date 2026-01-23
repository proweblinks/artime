# Video Wizard - Current State

> Last Updated: 2026-01-23
> Session: Phase 4 - Dialogue Scene Excellence

---

## Current Position

**Phase:** 4 of ongoing (Dialogue Scene Excellence)
**Plan:** 01-02 of 4 (in phase) - COMPLETE
**Status:** In Progress

**Progress:** [##--------] Phase 4 (2/4 plans complete - 04-01, 04-02)

---

## Current Focus

**Phase 4: Dialogue Scene Excellence**

Enhance dialogue scenes with proper OTS shots, reaction variety, and spatial continuity.

Plans:
1. ~~Spatial Continuity~~ COMPLETE
2. ~~OTS Shot Depth and Framing~~ COMPLETE
3. Reaction Shot Variety (pending)
4. Additional enhancements (pending)

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

---

## Completed This Session

### Plan 04-01: Spatial Continuity Tracking (COMPLETE)
**Summary:** 180-degree rule enforcement with camera position tracking, eye-line direction, and reverse shot pairing

**Tasks:**
1. [x] Add spatial tracking properties ($axisLockSide, spatial array)
2. [x] Implement camera position assignment (calculateSpatialData)
3. [x] Add reverse shot pairing system (pairReverseShots)
4. [x] Enhance visual prompts with spatial info (buildSpatialAwarePrompt)
5. [x] Integrate into main decomposition

**Commits:**
- `3f14f75` - feat(04-02): includes spatial continuity (combined commit)

**SUMMARY:** `.planning/phases/04-dialogue-scene-excellence/04-01-SUMMARY.md`

### Plan 04-02: OTS Shot Depth and Framing (COMPLETE)
**Summary:** OTS shots now specify foreground shoulder, blur depth, and profile angle for Hollywood-style depth framing

**Tasks:**
1. [x] Add OTS-specific shot data structure (buildOTSData)
2. [x] Create OTS-specific visual prompt builder (buildOTSPrompt)
3. [x] Integrate OTS detection into shot generation (shouldUseOTS)
4. [x] Update DynamicShotEngine dialogue pattern with OTS specs

**Commits:**
- `3f14f75` - feat(04-02): add OTS shot depth and framing enhancements

**SUMMARY:** `.planning/phases/04-dialogue-scene-excellence/04-02-SUMMARY.md`

---

## Previous Phases (Complete)

### Phase 3: Hollywood Production System - COMPLETE

All 7 plans successfully executed:
1. Activate Hollywood Shot Sequence
2. Eliminate Placeholder Moments
3. Enable Hollywood Features by Default
4. Auto-Proceed Pipeline
5. Smart Retry Logic for Batch Generation
6. Character Visual Consistency
7. Smart Defaults from Concept

See: `.planning/phases/03-hollywood-production-system/` for summaries.

### Phase 2: Narrative Intelligence - COMPLETE

All 3 plans successfully executed:
1. Wire NarrativeMomentService into ShotIntelligenceService
2. Enhance buildAnalysisPrompt with narrative moments
3. Map narrative moments to shot recommendations

See: `.planning/phases/02-narrative-intelligence/` for summaries.

### Milestone 1.5: Automatic Speech Flow System - COMPLETE

All 4 plans successfully executed:
1. Automatic Speech Segment Parsing
2. Detection Summary UI
3. characterIntelligence Backward Compatibility
4. Segment Data Flow to Shots

See: `.planning/phases/1.5-automatic-speech-flow/1.5-CONTEXT.md` for implementation decisions.

---

## Decisions Made

| Date | Area | Decision | Context |
|------|------|----------|---------|
| 2026-01-23 | Axis Lock | Camera stays on left side of axis | 180-degree rule enforcement |
| 2026-01-23 | Character A Position | Always screen-right, looks screen-left | Consistent spatial relationships |
| 2026-01-23 | Pair Tracking | pair_N format for reverse shots | Simple incremental pairing |
| 2026-01-23 | OTS Shoulder | Left when speaker screen-right, right when screen-left | Follows 180-degree rule |
| 2026-01-23 | OTS Detection | Alternating for medium shots 0.3-0.7 intensity | Creates shot/reverse-shot rhythm |
| 2026-01-23 | OTS Pattern | Mirrored shoulders between OTS and reverse | Maintains visual continuity |
| 2026-01-23 | Character Reference | Use sceneMemory['characterBible'] structure | Existing data structure has all needed fields |
| 2026-01-23 | Portrait Trigger | Event dispatch on storyboard step entry | Non-blocking async portrait generation |
| 2026-01-23 | Portrait Tracking | Save in content_config | Persists across sessions |
| 2026-01-23 | Smart Defaults | Keyword-first, AI-optional | Fast response for common cases, AI for complex concepts |
| 2026-01-23 | Overwrite Default | false by default | Respect user's manual configuration choices |
| 2026-01-23 | Platform Aspect Ratio | Auto-set based on platform | TikTok/Instagram = 9:16, YouTube/LinkedIn = 16:9 |
| 2026-01-23 | Duration Heuristic | Word count based | < 20 words = 30s, < 50 = 60s, < 100 = 120s, 100+ = 180s |
| 2026-01-23 | Auto-proceed Default | Disabled by default (false) | Users may want manual control; enable explicitly |
| 2026-01-23 | Progress Weights | Script 20%, Storyboard 40%, Animation 30%, Assembly 10% | Reflects actual processing time per stage |
| 2026-01-23 | Event Pattern | Use dispatch() + #[On()] for auto-proceed | Allows async handling and decoupling |
| 2026-01-23 | Video Check | Check multiShotMode first, animation fallback | Current implementation uses multi-shot mode |
| 2026-01-23 | Retry Pattern | Exponential backoff (2s, 4s, 8s) | Standard retry pattern for API reliability |
| 2026-01-23 | Max Retries | 3 attempts per item | Balance between recovery and failure detection |
| 2026-01-23 | Status Tracking | Item keys: scene_{i}, scene_{i}_shot_{j}, video_scene_{i} | Unique identification for mixed batch operations |
| 2026-01-23 | Settings Category | Use 'hollywood' group for new feature settings | Separate from existing categories |
| 2026-01-23 | Runtime Initialization | Create settings on mount if missing | Ensures Hollywood features work in dev |
| 2026-01-23 | Shot Variety | DynamicShotEngine handles variety through Hollywood patterns | Not ShotProgressionService |
| 2026-01-23 | Service Creation | Inline creation of NarrativeMomentService | Matches existing VideoWizard pattern |
| 2026-01-23 | Fallback Strategy | Two-tier fallback (narration analysis -> narrative arc) | Ensures meaningful output |

---

## Phase 4 Progress - What Was Built

### Plan 04-01: Spatial Continuity Tracking
1. **Axis Lock:** `$axisLockSide = 'left'` for 180-degree rule
2. **Spatial Data:** `calculateSpatialData()` computes cameraPosition, eyeLineDirection, subjectPosition
3. **Camera Angle:** `determineCameraAngle()` maps shot types to angles
4. **Reverse Pairing:** `pairReverseShots()` links alternating speaker shots
5. **Spatial Prompts:** `buildSpatialAwarePrompt()` adds positioning to visual prompts
6. **Integration:** Spatial data in every dialogue shot, pair count in logs

### Plan 04-02: OTS Shot Depth and Framing
1. **OTS Data Structure:** `buildOTSData()` with foreground/background specification
2. **OTS Prompts:** `buildOTSPrompt()` for Hollywood-style OTS framing
3. **Emotion Detection:** `detectDialogueEmotion()` for dialogue mood analysis
4. **OTS Detection:** `shouldUseOTS()` for intelligent OTS triggering
5. **Integration:** OTS logic integrated into `createDialogueShot()` flow
6. **Dialogue Pattern:** DynamicShotEngine `$dialoguePattern` has `otsSpecs`

---

## OTS Data Structure

| Field | Description | Values |
|-------|-------------|--------|
| `foregroundCharacter` | Listener (blurred) | Character name |
| `foregroundShoulder` | Which shoulder visible | left / right |
| `foregroundBlur` | Blur foreground | true |
| `foregroundVisible` | What's visible | "shoulder and partial head" |
| `backgroundCharacter` | Speaker (in focus) | Character name |
| `backgroundPosition` | Screen position | left / right |
| `depthOfField` | DoF setting | shallow |
| `focusOn` | Who is sharp | Speaker name |
| `profileAngle` | Camera angle | left-three-quarter / right-three-quarter |

---

## Blockers

None currently

---

## Key Files

| File | Purpose | Status |
|------|---------|--------|
| `.planning/phases/04-dialogue-scene-excellence/04-01-SUMMARY.md` | Plan 01 summary | **Created** |
| `.planning/phases/04-dialogue-scene-excellence/04-02-SUMMARY.md` | Plan 02 summary | Created |
| `Services/DialogueSceneDecomposerService.php` | Spatial + OTS data + prompts | **Updated** |
| `Services/DynamicShotEngine.php` | Dialogue pattern with OTS specs | Updated |

---

## Session Continuity

**Last session:** 2026-01-23
**Stopped at:** Completed 04-01-PLAN.md (Spatial Continuity Tracking)
**Resume file:** None
**Phase 4 Status:** In Progress (2/4 plans complete)

---

*Session: Phase 4 - Dialogue Scene Excellence*
*Plan 04-01 COMPLETE - Spatial continuity tracking*
*Plan 04-02 COMPLETE - OTS shot depth and framing enhancements*
