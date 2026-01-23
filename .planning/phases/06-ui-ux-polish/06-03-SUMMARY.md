---
phase: 06-ui-ux-polish
plan: 03
name: Enhanced Progress Indicators
subsystem: storyboard-ui
tags: [progress, status-badges, intensity, ui-polish]
completed: 2026-01-23
duration: ~8min

dependency-graph:
  requires:
    - "06-01: Dialogue display (badge system foundation)"
    - "06-02: Shot type badges (badge CSS patterns)"
  provides:
    - "Status badge CSS classes (pending/generating/complete/error)"
    - "Intensity bar CSS with color gradient"
    - "Mini progress ring styles"
    - "Per-shot status badges in multi-shot modal"
    - "Enhanced progress summary in multi-shot header"
    - "Scene intensity indicator on storyboard cards"
  affects:
    - "User progress visibility during generation"
    - "Emotional arc visualization"

tech-stack:
  added: []
  patterns:
    - "Status-based color coding (gray=pending, amber=generating, green=complete, red=error)"
    - "Intensity gradient (blue=low, amber=medium, red=high, gradient=climax)"
    - "SVG progress rings with stroke-dashoffset animation"
    - "Conditional rendering based on imageStatus/videoStatus"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php
    - modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php

decisions:
  - id: status-colors
    choice: "Gray (pending), Amber (generating), Green (complete/ready), Red (error)"
    reason: "Standard color conventions for state indication"
  - id: intensity-gradient
    choice: "Blue (low) -> Amber (medium) -> Red (high) -> Purple-pink (climax)"
    reason: "Consistent with shot type color system, climax matches climax badge"
  - id: pulse-animation
    choice: "CSS pulse animation for generating state"
    reason: "Visual feedback that generation is in progress"
---

# Phase 6 Plan 03: Enhanced Progress Indicators Summary

**One-liner:** Per-shot status badges (IMG/VID), intensity bars, mini progress rings, and enhanced header summary for detailed generation progress visibility.

## Objectives Achieved

1. **Status badge CSS** - Complete styling for pending, generating, complete, and error states
2. **Intensity bar system** - Color-coded bars showing emotional intensity level
3. **Per-shot status badges** - IMG and VID status indicators on each shot card
4. **Enhanced progress summary** - Header with image/video counts and progress rings
5. **Scene intensity indicator** - Intensity bar on main scene cards with climax badge

## Implementation Details

### CSS Classes Added

| Class | Purpose | Visual |
|-------|---------|--------|
| vw-status-badge | Base badge styling | Inline-flex, uppercase |
| vw-status-pending | Pending state | Gray background |
| vw-status-generating | Active generation | Amber with pulse animation |
| vw-status-ready/complete | Complete state | Green background |
| vw-status-error | Error state | Red background |
| vw-intensity-bar | Intensity container | 3px height, rounded |
| vw-intensity-fill | Intensity level | Transition animation |
| vw-intensity-low | Low intensity | Blue |
| vw-intensity-medium | Medium intensity | Amber |
| vw-intensity-high | High intensity | Red |
| vw-intensity-climax | Climax moment | Purple-pink gradient |
| vw-mini-progress | Progress ring container | 16x16px SVG |
| vw-mini-progress-bg | Ring background | Gray stroke |
| vw-mini-progress-fill | Ring progress | Colored stroke with dashoffset |

### Per-Shot Status Badges (Multi-Shot Modal)

Each shot card now displays:
- **IMG badge** - Shows image status (pending/generating/ready/error) with icon
- **VID badge** - Shows video status (only when lip-sync needed or not pending)
- **Intensity bar** - Visual representation of shot's emotional intensity

### Enhanced Progress Summary (Multi-Shot Header)

When a scene is decomposed, the header now shows:
- **Image progress** - Mini ring + "X/Y" count + "N generating" badge if active
- **Video progress** - Mini ring + "X/Y" count (only if lip-sync shots exist)
- **Average intensity** - Percentage with color-coded display

### Scene Intensity Indicator (Storyboard Cards)

Decomposed scenes now show:
- **Intensity bar** - Average emotional intensity of all shots
- **CLIMAX badge** - If any shot in scene is marked as climax
- **Percentage** - Numeric intensity value

## Verification Results

- [x] Status badge classes defined (pending, generating, complete, error)
- [x] Generating state has pulse animation
- [x] Intensity bar with fill variations (low, medium, high, climax)
- [x] Mini progress ring styles defined
- [x] Per-shot status badges show in multi-shot modal
- [x] Video status badge shows when applicable
- [x] Progress summary shows image/video counts
- [x] Generating count shown when active
- [x] Scene cards show intensity indicator when decomposed
- [x] Climax badge shows if scene contains climax shot
- [x] No PHP/Blade/CSS syntax errors

## Notes

This plan's implementation was distributed across multiple commits due to concurrent execution:
- `aa0913c` - Status Badge CSS (Task 1)
- `f6f5f75` - Per-shot badges and Progress Summary (Tasks 2 & 3)
- `5f02319` - Scene Intensity Indicator (Task 4)

All features are fully functional and provide comprehensive progress visibility.

## Files Modified

1. **storyboard.blade.php**
   - Added PHASE 6 Status Badges CSS section
   - Added intensity bar CSS classes
   - Added mini progress ring styles
   - Added scene intensity indicator to decomposed scene cards

2. **multi-shot.blade.php**
   - Added enhanced progress summary after header
   - Added per-shot status badges (IMG/VID) to shot cards
   - Added intensity bars to shot cards
