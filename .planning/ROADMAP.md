# Video Wizard Development Roadmap

## Milestone 7: Scene Text Inspector

**Target:** Full transparency into scene text, prompts, and metadata
**Status:** In Progress (2026-01-23)
**Total requirements:** 28 (5 categories)

---

## Overview

Scene Text Inspector provides complete visibility into all scene text content currently truncated in the storyboard view. Users can inspect full speech segments (with correct type labels and icons), complete AI prompts (image/video), scene metadata (duration, transitions, characters), and copy prompts to clipboard. This milestone fixes the hardcoded "Dialogue" label bug and enables users to see all generated content transparently.

The implementation follows established modal patterns from Character Bible and Location Bible modals, with critical attention to Livewire performance pitfalls (payload bloat, re-render cascades) and mobile UX requirements. Research validates this as a 6-8 hour implementation with zero new dependencies.

---

## Phase 7: Foundation - Modal Shell + Scene Card Fixes + Metadata

**Goal:** Users can open a working inspector modal and see scene metadata correctly displayed

**Dependencies:** None (starts new milestone)

**Requirements:**
- MODL-01: User can open inspector from scene card
- MODL-02: Modal shows scene number and title
- MODL-03: Modal content is scrollable for long scenes
- MODL-04: Modal has close button
- CARD-01: Scene card shows dynamic label based on segment types present
- CARD-02: Scene card shows type-specific icons for segments
- CARD-03: Scene card indicates "click to view all" when truncated
- META-01: User can view scene duration
- META-02: User can view scene transition type
- META-03: User can view scene location
- META-04: User can view characters present in scene
- META-05: User can view emotional intensity indicator
- META-06: Climax scenes show climax badge

**Success Criteria:**
1. User clicks Inspect button (magnifying glass icon) on scene card and modal opens immediately
2. Modal displays correct scene number and title in header with close button (X and ESC key work)
3. Scene card shows accurate segment type summary (not hardcoded "Dialogue") with type-specific icons matching segment content
4. Modal displays scene metadata badges including duration, transition, location, characters, and intensity with climax badge for climax scenes
5. Modal content scrolls smoothly for long scenes without body scroll behind modal

**Estimated time:** 2 hours

---

## Phase 8: Speech Segments Display

**Goal:** Users can view all speech segments with correct type labels, icons, and speaker attribution

**Dependencies:** Phase 7 (requires modal shell)

**Requirements:**
- SPCH-01: User can view ALL speech segments for a scene (not truncated)
- SPCH-02: Each segment shows correct type label (NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE)
- SPCH-03: Each segment shows type-specific icon (microphone/speech bubble/thought bubble/speaking)
- SPCH-04: Each segment shows speaker name (if applicable)
- SPCH-05: Each segment shows lip-sync indicator (YES for dialogue/monologue, NO for narrator/internal)
- SPCH-06: Each segment shows estimated duration
- SPCH-07: Speaker matched to Character Bible shows character indicator

**Success Criteria:**
1. Modal displays complete speech segment list with no truncation, showing full text content with proper line wrapping
2. Each segment displays correct type badge (NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE) with matching icon (microphone, speech bubble, thought bubble, speaking)
3. Dialogue and monologue segments show speaker name in purple with lip-sync badge showing YES/NO
4. Each segment displays estimated duration and character indicator when speaker exists in Character Bible
5. Scrollable segment list handles 10+ segments smoothly without layout issues

**Estimated time:** 2 hours

---

## Phase 9: Prompts Display + Copy-to-Clipboard

**Goal:** Users can view full prompts and copy them to clipboard with visual feedback

**Dependencies:** Phase 8 (requires content structure)

**Requirements:**
- PRMT-01: User can view full image prompt (not truncated)
- PRMT-02: User can view full video prompt (not truncated)
- PRMT-03: User can copy image prompt to clipboard with one click
- PRMT-04: User can copy video prompt to clipboard with one click
- PRMT-05: Shot type badge displayed with prompt
- PRMT-06: Camera movement indicator displayed

**Success Criteria:**
1. Modal displays complete image prompt and video prompt in separate collapsible sections with full text visible
2. Each prompt section has copy button that copies text to clipboard and shows immediate visual feedback (button text changes or toast notification)
3. Copy functionality works reliably across modern browsers including iOS Safari with execCommand fallback
4. Prompt sections display shot type badge (XCU, CU, MCU, etc.) and camera movement indicator with icons
5. User can copy individual prompts or speech segments independently

**Estimated time:** 2-3 hours

---

## Phase 10: Mobile Responsiveness + Polish

**Goal:** Users have excellent experience on mobile devices with professional visual consistency

**Dependencies:** Phase 9 (requires all features complete)

**Requirements:**
- MODL-05: Modal works on mobile (responsive)

**Success Criteria:**
1. Modal displays fullscreen on mobile devices (under 768px) and centered box on desktop
2. Close button positioned in bottom-right thumb zone on mobile for one-handed operation
3. Body scroll locked on iOS Safari when modal open (no background scrolling)
4. Modal styling matches existing Character Bible and Location Bible modals (consistent colors, spacing, typography)
5. All interactive elements (copy buttons, close button, collapsible sections) work smoothly on touch devices

**Estimated time:** 1-2 hours

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 7: Foundation | Pending | MODL-01 to MODL-04, CARD-01 to CARD-03, META-01 to META-06 (14) | 5 criteria |
| Phase 8: Speech Segments | Pending | SPCH-01 to SPCH-07 (7) | 5 criteria |
| Phase 9: Prompts + Copy | Pending | PRMT-01 to PRMT-06 (6) | 5 criteria |
| Phase 10: Mobile + Polish | Pending | MODL-05 (1) | 5 criteria |

**Overall Progress:**

```
Phase 7:  ░░░░░░░░░░ 0%
Phase 8:  ░░░░░░░░░░ 0%
Phase 9:  ░░░░░░░░░░ 0%
Phase 10: ░░░░░░░░░░ 0%
─────────────────────
Overall:  ░░░░░░░░░░ 0%
```

**Coverage:** 28/28 requirements mapped (100%)

---

## Research Validation

Research confirms:
- Zero new dependencies (Laravel 10 + Livewire 3 + Alpine.js sufficient)
- Clipboard API validated in existing timeline component
- Modal patterns established in Character Bible, Location Bible, Scene DNA modals
- Critical pitfalls identified: payload bloat, re-render cascades, clipboard reliability, mobile UX

**Implementation guidance:**
- Use computed properties for scene data (not public properties) to avoid payload bloat
- Apply wire:ignore on storyboard content to prevent re-render cascades
- Implement native Clipboard API with execCommand fallback
- Mobile-first design with fullscreen on mobile, thumb-friendly close button
- Test on actual iPhone for iOS Safari scroll lock validation

---

## Success Metrics

| Metric | Target | Current |
|--------|--------|---------|
| Requirements coverage | 100% | 100% |
| Modal open time | <300ms | TBD |
| Copy success rate | >98% | TBD |
| Mobile usability | Thumb-friendly | TBD |
| Type label accuracy | 100% | 0% (hardcoded) |

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

Full transparency into generated content maintains trust and enables users to understand and reproduce AI outputs. The inspector provides complete visibility without requiring users to dig through code or logs.

---

*Milestone 7 roadmap created: 2026-01-23*
*Ready for phase planning: Phase 7*
