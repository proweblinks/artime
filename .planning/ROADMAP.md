# Video Wizard Development Roadmap

## Milestone 8: Cinematic Shot Architecture

**Target:** Transform scene decomposition so every shot is purposeful, speech-driven, and cinematically connected
**Status:** In Progress (2026-01-23)
**Total requirements:** 16 (4 categories)
**Phases:** 11-14 (continues from M7)

---

## Overview

Cinematic Shot Architecture fixes the fundamental issue where speech segments are distributed proportionally across shots instead of driving shot creation. This produces non-cinematic results where dialogue doesn't flow naturally with alternating characters.

The refactored system creates shots FROM speech segments: each dialogue/monologue segment becomes its own shot with proper shot/reverse-shot patterns, dynamic camera selection based on emotion and position, and continuous cinematic flow. Narrator segments overlay across multiple shots rather than receiving dedicated shots.

This builds on the M4 DialogueSceneDecomposerService foundation while inverting the segment-to-shot relationship.

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 11 | Speech-Driven Shot Creation | Shots created FROM speech segments, not distributed TO them | CSA-01, CSA-02, CSA-03, CSA-04, SCNE-01 | 5 |
| 12 | Shot/Reverse-Shot Patterns | Proper alternating character coverage for conversations | FLOW-01, FLOW-02, FLOW-04, SCNE-04 | 4 |
| 13 | Dynamic Camera Intelligence | Smart camera selection based on emotion and position | CAM-01, CAM-02, CAM-03, CAM-04 | 4 |
| 14 | Cinematic Flow & Action Scenes | Seamless transitions and improved non-dialogue handling | FLOW-03, SCNE-02, SCNE-03 | 3 |

**Total:** 4 phases | 16 requirements | 16 success criteria

---

## Phase 11: Speech-Driven Shot Creation

**Goal:** Refactor decomposition so speech segments CREATE shots instead of being distributed to them

**Status:** Pending

**Dependencies:** None (starts new milestone)

**Requirements:**
- CSA-01: Each dialogue segment creates its own shot (1:1 mapping)
- CSA-02: Each monologue segment creates its own shot (1:1 mapping)
- CSA-03: Narrator segments overlay across multiple shots (not dedicated shots)
- CSA-04: Internal thought segments handled as voiceover (no dedicated shot)
- SCNE-01: No artificial limit on shots per scene (10+ if speech demands)

**Success Criteria:**
1. Scene with 5 dialogue exchanges produces 5+ shots (one per speaker turn)
2. Monologue scene creates shots matching monologue segments
3. Narrator text appears as metadata on shots, not as separate shots
4. Internal thought segments flagged as voiceover-only, no visual shot
5. Scene with 12 speech segments produces 12+ shots without error

**Key changes:**
- Invert `distributeSpeechSegmentsToShots()` → `createShotsFromSpeechSegments()`
- Separate narrator handling from dialogue/monologue
- Remove shot count caps that limit cinematic expression

---

## Phase 12: Shot/Reverse-Shot Patterns

**Goal:** Implement proper Hollywood conversation coverage with alternating characters

**Status:** Pending

**Dependencies:** Phase 11 (requires shots to exist from speech)

**Requirements:**
- FLOW-01: Shot/reverse-shot pattern for 2-character conversations
- FLOW-02: Single character visible per shot (model constraint enforced)
- FLOW-04: Alternating character shots in dialogue sequences
- SCNE-04: Scene maintains 180-degree rule throughout

**Success Criteria:**
1. Two-character dialogue alternates Character A → Character B → A → B
2. Each shot shows exactly one speaking character (validated before generation)
3. Camera stays on same side of action axis (180-degree rule)
4. OTS shots show foreground character blurred, background in focus

**Key changes:**
- Enhance `DialogueSceneDecomposerService` to enforce single-character shots
- Validate shot sequence before generation
- Add 180-degree continuity check across full scene

---

## Phase 13: Dynamic Camera Intelligence

**Goal:** Smart camera selection that responds to emotion and conversation position

**Status:** Pending

**Dependencies:** Phase 12 (requires pattern working)

**Requirements:**
- CAM-01: Dynamic CU/MS/OTS selection based on emotional intensity
- CAM-02: Camera variety based on position in conversation (opening vs climax)
- CAM-03: Shot type matches speaker's emotional state
- CAM-04: Establishing shot at conversation start, tight framing at climax

**Success Criteria:**
1. High-intensity dialogue (anger, fear) uses close-up framing
2. Conversation opening uses establishing or medium shots
3. Conversation climax uses tight close-ups
4. Neutral dialogue uses medium shots with OTS variety
5. Each speaker's shot type reflects their emotional state from script

**Key changes:**
- Enhance `selectShotTypeForIntensity()` with conversation position awareness
- Add per-speaker emotion analysis
- Implement shot progression arc (wide → medium → tight as tension builds)

---

## Phase 14: Cinematic Flow & Action Scenes

**Goal:** Smooth shot transitions and improved non-dialogue scene handling

**Status:** Pending

**Dependencies:** Phase 13 (requires camera working)

**Requirements:**
- FLOW-03: Shots build cinematically on each other (no jarring cuts)
- SCNE-02: Non-dialogue scenes get improved action decomposition
- SCNE-03: Mixed scenes (dialogue + action) handled smoothly

**Success Criteria:**
1. No jump cuts (same character, same framing back-to-back)
2. Shot scale changes by at least one step (CU → MS, not CU → CU)
3. Action scenes produce varied shot types (establishing, action, reaction, detail)
4. Mixed dialogue/action scenes transition smoothly between modes
5. Visual prompt continuity verified across shot sequence

**Key changes:**
- Add transition validator to prevent jarring cuts
- Improve `DynamicShotEngine` for action-only scenes
- Create hybrid decomposer for dialogue+action scenes

---

## Dependencies

```
Phase 11 (Speech-Driven)
    ↓
Phase 12 (Shot/Reverse-Shot) ← depends on shots existing
    ↓
Phase 13 (Camera Intelligence) ← depends on pattern working
    ↓
Phase 14 (Flow & Polish) ← depends on camera working
```

Sequential execution required.

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 11: Speech-Driven | ○ Pending | CSA-01 to CSA-04, SCNE-01 (5) | 0/5 |
| Phase 12: Shot/Reverse-Shot | ○ Pending | FLOW-01, FLOW-02, FLOW-04, SCNE-04 (4) | 0/4 |
| Phase 13: Camera Intelligence | ○ Pending | CAM-01 to CAM-04 (4) | 0/4 |
| Phase 14: Flow & Action | ○ Pending | FLOW-03, SCNE-02, SCNE-03 (3) | 0/3 |

**Overall Progress:**

```
Phase 11: ░░░░░░░░░░ 0%
Phase 12: ░░░░░░░░░░ 0%
Phase 13: ░░░░░░░░░░ 0%
Phase 14: ░░░░░░░░░░ 0%
─────────────────────
Overall:  ░░░░░░░░░░ 0%
```

**Coverage:** 16/16 requirements mapped (100%)

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Many shots overwhelm UI | HIGH | Update storyboard grid to handle 10+ shots per scene |
| Breaking existing scenes | HIGH | Preserve fallback to current decomposition for non-dialogue |
| Performance with 10+ shots | MEDIUM | Lazy load shots, paginate if needed |
| Complex prompt chains | MEDIUM | Validate prompts maintain character continuity |

---

## Verification Strategy

After each phase:
1. Test with sample dialogue scene (2 characters, 6+ exchanges)
2. Test with monologue scene (single character, 4+ segments)
3. Test with action scene (no dialogue)
4. Test with mixed scene (dialogue + action)
5. Verify generated images show correct character in correct framing

---

## Previous Milestone (Complete)

### Milestone 7: Scene Text Inspector - COMPLETE

**Status:** 100% complete (28/28 requirements)
**Phases:** 7-10

| Phase | Status |
|-------|--------|
| Phase 7: Foundation | ✓ Complete |
| Phase 8: Speech Segments | ✓ Complete |
| Phase 9: Prompts + Copy | ✓ Complete |
| Phase 10: Mobile + Polish | ✓ Complete |

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

Cinematic shot architecture ensures users get professional-quality shot sequences automatically. Each button click produces shots that flow like a Hollywood film, with proper coverage, dynamic cameras, and continuous visual storytelling.

---

*Milestone 8 roadmap created: 2026-01-23*
*Phases 11-14 defined*
