# Video Wizard Development Roadmap

## Milestone 9: Voice Production Excellence

**Target:** Professional-grade voice continuity and TTS production pipeline aligned with modern industry standards
**Status:** In Progress (2026-01-24)
**Total requirements:** 6 (3 categories)
**Phases:** 15-18 (continues from M8)

---

## Overview

Voice Production Excellence addresses critical gaps in the TTS/lip-sync pipeline identified through comprehensive audit. The current implementation has solid foundations (SpeechSegment class, flexible parsing, good voice assignment) but lacks voice continuity, proper narrator voice assignment, and multi-speaker support.

Modern industry standards (Dia 1.6B, VibeVoice, Gemini 2.5 TTS, MultiTalk) demonstrate that multi-speaker dialogue with consistent character voices and smooth turn-taking is now standard. This milestone brings the Video Wizard's voice pipeline to professional grade.

**Key gaps from audit:**
- Narrator voice not assigned to shots (overlayNarratorSegments sets narratorText but NOT narratorVoiceId)
- Single speaker per shot limitation (only first speaker's voice used)
- No voice continuity validation (same character could get different voices)
- Silent type coercion (missing segment type defaults to 'narrator' without error)
- Empty text validation missing (empty segments can reach TTS)

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 15 | Critical Fixes | Fix immediate voice assignment and validation gaps | VOC-01, VOC-02 | 2 |
| 16 | Consistency Layer | Unify distribution strategies and validate continuity | VOC-03, VOC-04 | 2 |
| 17 | Voice Registry | Centralize voice assignment as single source of truth | VOC-05 | 1 |
| 18 | Multi-Speaker Support | Track multiple speakers per shot for dialogue | VOC-06 | 1 |

**Total:** 4 phases | 6 requirements | 6 success criteria

---

## Phase 15: Critical Fixes

**Goal:** Fix immediate voice assignment and validation gaps that cause TTS failures

**Status:** Complete (2026-01-24)

**Plans:** 1 plan

Plans:
- [x] 15-01-PLAN.md — Narrator voice assignment + empty text validation

**Dependencies:** None (starts new milestone)

**Requirements:**
- VOC-01: Narrator voice assigned to shots (narratorVoiceId flows through overlayNarratorSegments)
- VOC-02: Empty text validation before TTS (empty/invalid segments caught early)

**Success Criteria:**
1. overlayNarratorSegments() sets narratorVoiceId on each shot (from getNarratorVoice())
2. Empty segment text caught before reaching TTS generation
3. Missing segment type logged as error (not silently coerced to 'narrator')
4. TTS generation receives valid, non-empty text for all segments

**Key changes:**
- Add narratorVoiceId assignment in overlayNarratorSegments() (~line 23701)
- Add empty text check before TTS calls
- Log errors for missing segment types instead of silent coercion

---

## Phase 16: Consistency Layer

**Goal:** Unify distribution strategies and validate voice continuity across scenes

**Status:** Complete (2026-01-25)

**Plans:** 2 plans

Plans:
- [x] 16-01-PLAN.md — Unified distribution strategy (word-split for internal thoughts)
- [x] 16-02-PLAN.md — Voice continuity validation (validateVoiceContinuity method)

**Dependencies:** Phase 15 (requires validation working)

**Requirements:**
- VOC-03: Unified distribution strategy (narrator and internal thoughts use same word-split approach)
- VOC-04: Voice continuity validation (same character maintains same voice across all scenes)

**Success Criteria:**
1. Narrator and internal thought segments use identical word-split distribution algorithm
2. validateVoiceContinuity() method checks character-to-voice consistency
3. Voice mismatches logged as warnings (non-blocking, same as M8 validation pattern)
4. Same character never receives different voices across scenes
5. Internal thought overlay behavior matches narrator overlay behavior

**Key changes:**
- Refactor internal thought distribution to use same word-split as narrator
- Add validateVoiceContinuity() to check voice assignments
- Log voice continuity warnings without blocking generation

**Industry alignment:**
- Microsoft VibeVoice: 90 minutes of speech with 4 distinct speakers maintaining consistency
- Google Gemini 2.5 TTS: Seamless dialogue with consistent character voices

---

## Phase 17: Voice Registry

**Goal:** Centralize voice assignment as single source of truth

**Status:** Planned (2026-01-25)

**Plans:** 2 plans

Plans:
- [ ] 17-01-PLAN.md — Create VoiceRegistryService class
- [ ] 17-02-PLAN.md — Integrate registry into VideoWizard.php

**Dependencies:** Phase 16 (requires continuity validation)

**Requirements:**
- VOC-05: Voice Registry centralization (single source of truth for narrator, internal, character voices)

**Success Criteria:**
1. VoiceRegistry class created with narrator, internal, and character voice properties
2. All voice lookups go through VoiceRegistry instead of multiple resolution paths
3. Character Bible voice assignments flow into VoiceRegistry
4. validateContinuity() method on VoiceRegistry returns issues array
5. Voice assignment debugging simplified (single place to check)

**Key changes:**
- Create VoiceRegistry class (as proposed in audit)
- Refactor voice lookup calls to use registry
- Wire Character Bible voices into registry
- Add registry-level continuity validation

**Proposed interface (from audit):**
```php
class VoiceRegistry {
    public ?string $narratorVoiceId;
    public ?string $internalVoiceId;
    public array $characterVoices = [];
    public function validateContinuity(): array;
}
```

---

## Phase 18: Multi-Speaker Support

**Goal:** Track multiple speakers per shot for complex dialogue scenes

**Status:** Planned (2026-01-24)

**Dependencies:** Phase 17 (requires registry working)

**Requirements:**
- VOC-06: Multi-speaker shot support (multiple speakers tracked per shot for dialogue)

**Success Criteria:**
1. Shot structure supports multiple speakers array (not just first speaker)
2. Each speaker entry includes name, voiceId, and text
3. DialogueSceneDecomposerService creates multi-speaker shot data
4. Downstream TTS processing can handle multiple voices per shot
5. Shot/reverse-shot patterns still work (single visible character, multiple voice tracks)

**Key changes:**
- Expand shot structure from single speaker to speakers array
- Refactor `$firstSpeaker = array_keys($speakers)[0]` pattern (~line 23630)
- Create multi-speaker shot data in decomposition
- Update TTS processing to handle multiple voices

**Current limitation (from audit):**
```php
// Current (line 23630):
$firstSpeaker = array_keys($speakers)[0] ?? null;

// Proposed:
$shot['speakers'] = [
    ['name' => 'HERO', 'voiceId' => 'xxx', 'text' => '...'],
    ['name' => 'VILLAIN', 'voiceId' => 'yyy', 'text' => '...'],
];
```

---

## Dependencies

```
Phase 15 (Critical Fixes)
    |
Phase 16 (Consistency Layer) <- depends on validation working
    |
Phase 17 (Voice Registry) <- depends on continuity validation
    |
Phase 18 (Multi-Speaker) <- depends on registry working
```

Sequential execution required.

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 15: Critical Fixes | Complete | VOC-01, VOC-02 (2) | 4/4 |
| Phase 16: Consistency Layer | Complete | VOC-03, VOC-04 (2) | 5/5 |
| Phase 17: Voice Registry | Planned | VOC-05 (1) | 0/5 |
| Phase 18: Multi-Speaker | Planned | VOC-06 (1) | 0/5 |

**Overall Progress:**

```
Phase 15: ██████████ 100%
Phase 16: ██████████ 100%
Phase 17: ░░░░░░░░░░ 0%
Phase 18: ░░░░░░░░░░ 0%
─────────────────────
Overall:  ██████░░░░ 66% (4/6 requirements)
```

**Coverage:** 6/6 requirements mapped (100%)

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing TTS flow | HIGH | Add validation without changing happy path first |
| Voice Registry refactor scope | MEDIUM | Keep registry as wrapper, don't rewrite voice lookup |
| Multi-speaker complexity | MEDIUM | Start with data structure, TTS processing later |
| Performance with validation | LOW | Validation is lightweight string checks |

---

## Verification Strategy

After each phase:
1. Test with scene containing narrator segments (voice assignment)
2. Test with empty/malformed segments (validation)
3. Test with same character across multiple scenes (continuity)
4. Test with multi-character dialogue (multi-speaker)
5. Verify TTS generation produces correct audio for all segment types

---

## Previous Milestone (Complete)

### Milestone 8: Cinematic Shot Architecture - COMPLETE

**Status:** 100% complete (16/16 requirements)
**Phases:** 11-14

| Phase | Status |
|-------|--------|
| Phase 11: Speech-Driven | Complete |
| Phase 12: Shot/Reverse-Shot | Complete |
| Phase 13: Camera Intelligence | Complete |
| Phase 14: Flow & Action | Complete |

**Key achievements:**
- Speech-driven shot creation (1:1 mapping)
- Shot/reverse-shot patterns with 180-degree rule
- Dynamic camera selection based on emotion and position
- Jump cut prevention with transition validation
- Action scene decomposition with coverage patterns
- Visual continuity metadata for prompts

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

Voice Production Excellence ensures users get professional-quality audio automatically. Each character maintains their voice throughout the video, narrator segments have proper voice assignment, and multi-speaker dialogue flows naturally with smooth turn-taking.

---

*Milestone 9 roadmap created: 2026-01-24*
*Phases 15-18 defined*
*Phase 17 planned: 2026-01-25*
*Source: Comprehensive TTS/Lip-Sync audit*
