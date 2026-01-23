---
phase: 06-ui-ux-polish
plan: 02
name: Shot Type Badges
subsystem: storyboard-ui
tags: [badges, shot-types, css, ui-polish]
completed: 2026-01-23
duration: ~11min

dependency-graph:
  requires:
    - "06-01: Dialogue display (badge system foundation)"
  provides:
    - "Shot type badge CSS classes"
    - "getShotTypeBadgeClass() helper function"
    - "getShotTypeLabel() helper function"
    - "getCameraMovementIcon() helper function"
    - "Shot type badges in multi-shot modal"
    - "Shot type summary on scene cards"
  affects:
    - "Future UI polish phases"
    - "Shot card visual identification"

tech-stack:
  added: []
  patterns:
    - "Color-coded shot type system (red=tight to blue=wide)"
    - "Blade helper functions for badge generation"
    - "function_exists() guard for cross-template reuse"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php
    - modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php

decisions:
  - id: color-gradient
    choice: "Red (XCU) -> Orange (CU) -> Amber (MCU) -> Green (MED) -> Blue (Wide/Est)"
    reason: "Intuitive color mapping: warm colors for tight/emotional, cool for establishing"
  - id: climax-gradient
    choice: "Purple-to-pink gradient with border for climax badge"
    reason: "Makes climax shots visually distinct and important-looking"
  - id: helper-guards
    choice: "Use function_exists() wrapper in multi-shot modal"
    reason: "Prevents redeclaration errors when both templates load same functions"
---

# Phase 6 Plan 02: Shot Type Badges Summary

**One-liner:** Color-coded shot type badges (XCU/CU/MCU/MED/WIDE/EST) with climax indicator and camera movement icons for instant visual shot identification.

## Objectives Achieved

1. **Shot type badge CSS system** - Complete color-coded badge styles from red (XCU) to blue (Wide)
2. **Badge helper functions** - getShotTypeBadgeClass(), getShotTypeLabel(), getCameraMovementIcon()
3. **Multi-shot modal badges** - Shot type, purpose, camera movement, and climax badges on each shot card
4. **Scene card summary** - Shot type count summary (e.g., "2x CU", "1x WIDE") on main scene cards

## Implementation Details

### CSS Badge Classes

| Class | Color | Use Case |
|-------|-------|----------|
| vw-shot-badge-xcu | Red (239, 68, 68) | Extreme close-up |
| vw-shot-badge-cu | Orange (249, 115, 22) | Close-up |
| vw-shot-badge-mcu | Amber (245, 158, 11) | Medium close-up |
| vw-shot-badge-med | Green (34, 197, 94) | Medium shot |
| vw-shot-badge-wide | Blue (59, 130, 246) | Wide shot |
| vw-shot-badge-est | Indigo (99, 102, 241) | Establishing |
| vw-shot-badge-ots | Purple (139, 92, 246) | Over-the-shoulder |
| vw-shot-badge-reaction | Pink (236, 72, 153) | Reaction shot |
| vw-shot-badge-two-shot | Teal (20, 184, 166) | Two-shot |
| vw-shot-badge-movement | Gray (168, 162, 158) | Camera movement |
| vw-shot-badge-climax | Purple-Pink gradient | Climax moment |

### Helper Functions

```php
getShotTypeBadgeClass($type)  // Maps shot type to CSS class suffix
getShotTypeLabel($type)       // Returns abbreviated label (e.g., "MCU", "WIDE")
getCameraMovementIcon($move)  // Returns unicode icon for camera movement
```

### Multi-Shot Modal Integration

Each shot card now displays:
- Primary shot type badge (always visible)
- Purpose badge (if different from type)
- Camera movement indicator (if not static)
- CLIMAX badge for climax shots

### Scene Card Summary

When a scene is decomposed, the multi-shot badge now includes a breakdown of shot types with counts, allowing quick verification of shot variety at the scene level.

## Verification Results

- [x] CSS classes for each shot type defined
- [x] Color gradient from red (XCU) to blue (Wide/Est)
- [x] Purpose badges have distinct colors
- [x] Climax badge has special gradient
- [x] Multi-shot modal shows shot type badges
- [x] Scene cards show shot type summary when decomposed
- [x] No PHP/Blade/CSS syntax errors

## Notes

This plan was executed alongside Plan 06-01 (Dialogue Display) and both features were committed together in commit `88a2da8`. The badge system provides the visual foundation for understanding shot composition at a glance.

## Files Modified

1. **storyboard.blade.php**
   - Added PHASE 6 shot badge CSS (14 badge classes)
   - Added helper functions (getShotTypeBadgeClass, getShotTypeLabel, getCameraMovementIcon)
   - Enhanced Multi-Shot Badge with shot type summary

2. **multi-shot.blade.php**
   - Added helper functions with function_exists() guards
   - Added CSS badge classes
   - Added shot type badges section to each shot card
