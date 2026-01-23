---
phase: 06-ui-ux-polish
plan: 01
subsystem: storyboard-ui
tags: [dialogue, ui, blade, css, scene-cards]

dependency-graph:
  requires: [05-emotional-arc-system]
  provides: [dialogue-display, scene-context-visibility]
  affects: [user-experience, storyboard-workflow]

tech-stack:
  added: []
  patterns: [css-classes, blade-conditionals, speech-segment-rendering]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php
    - modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php

decisions:
  - area: dialogue-styling
    decision: Blue border-left with purple speaker names
    rationale: Consistent with project color scheme, visually distinct from prompts

metrics:
  duration: 8 minutes
  completed: 2026-01-23
---

# Phase 6 Plan 01: Dialogue Display on Scene Cards Summary

**One-liner:** Dialogue and narration text displayed on scene cards with speaker names in purple and per-shot dialogue in multi-shot modal.

## What Was Built

### Task 1 & 4: Dialogue Section on Storyboard Scene Cards
Added a dialogue section that appears above the Prompt section on scene cards:
- Displays `speechSegments` from script scene data
- Falls back to `narration` if no speech segments
- Shows up to 2 segments with "+N more" indicator
- Speaker names in purple (rgba(139, 92, 246))
- Blue left border for visual distinction

**Location:** Between shot chain and prompt section in scene card

### Task 2: Per-Shot Dialogue in Multi-Shot Modal
Added dialogue display to individual shot cards:
- Shows `shot['dialogue']` text when available
- Displays `shot['speakingCharacter']` name in purple
- Lip sync indicator badge when `needsLipSync` is true but no dialogue text
- Microphone SVG icon for lip sync indicator

**Location:** After shot type badges, before shot preview

### Task 3: CSS Classes for Dialogue Styling
Added reusable CSS classes in the style block:
- `.vw-scene-dialogue` - Container with blue border-left, max-height scroll
- `.vw-dialogue-label` - "DIALOGUE" label with chat icon
- `.vw-dialogue-speaker` - Purple speaker name styling
- `.vw-dialogue-text` - White text for dialogue content
- `.vw-dialogue-more` - Italic gray "+N more" indicator

## Key Changes

| File | Changes |
|------|---------|
| storyboard.blade.php | +48 lines CSS, +40 lines Blade for dialogue section |
| multi-shot.blade.php | +40 lines for shot dialogue and lip sync indicator |

## Technical Details

### Data Sources
- `$script['scenes'][$index]['speechSegments']` - Array of speech segments with `speaker` and `text`
- `$script['scenes'][$index]['narration']` - Fallback narration text
- `$shot['dialogue']` - Per-shot dialogue in multi-shot
- `$shot['speakingCharacter']` - Speaker name for shot
- `$shot['needsLipSync']` - Boolean for lip sync requirement

### Styling
- Blue accent: `rgba(59, 130, 246, 0.x)` for borders and backgrounds
- Purple speaker: `rgba(139, 92, 246, 0.9)` for character names
- Text: `rgba(255, 255, 255, 0.85)` for dialogue content
- Scrollable container with 80px max-height

## Commits

| Hash | Message |
|------|---------|
| 88a2da8 | feat(06-01): add dialogue display to scene cards and multi-shot modal |

## Deviations from Plan

None - plan executed exactly as written.

## Verification Checklist

- [x] Dialogue section appears on scene cards
- [x] Shows speechSegments if available, else narration
- [x] Speaker names displayed in purple
- [x] Limited to 2 segments with "+N more" indicator
- [x] Multi-shot cards show dialogue text
- [x] Speaker character name displayed
- [x] Lip sync indicator for dialogue shots without text
- [x] CSS classes added for consistent styling
- [x] No blade syntax errors

## Next Phase Readiness

Ready for additional UI/UX polish plans. No blockers identified.
