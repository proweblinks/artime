---
phase: 06-ui-ux-polish
plan: 04
name: Scene Timeline Improvements
subsystem: ui-polish
tags: [speaker-names, arc-selector, camera-icons, css, visual-consistency]
completed: 2026-01-23
duration: ~8min

dependency-graph:
  requires:
    - "06-01: Dialogue display (CSS foundation)"
    - "06-02: Shot type badges (helper functions)"
  provides:
    - "Speaker names in animation step"
    - "Arc template selector in storyboard"
    - "Enhanced camera movement SVG icons"
    - "Visual consistency CSS improvements"
  affects:
    - "Animation step UI"
    - "Storyboard step UI"
    - "Multi-shot modal UI"

tech-stack:
  added: []
  patterns:
    - "Inline SVG icons for camera movements"
    - "getCameraMovementSvgPath() helper function"
    - "Purple highlight for speakers with assigned voice"
    - "Gray highlight for speakers without voice"

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/resources/views/livewire/steps/animation.blade.php
    - modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php
    - modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php

decisions:
  - id: speaker-voice-indicator
    choice: "Purple for speakers with voice, gray for those without"
    reason: "Consistent with app color scheme, immediately shows voice assignment status"
  - id: arc-selector-location
    choice: "Above scene stats bar in storyboard"
    reason: "Visible without scrolling, logically grouped with scene information"
  - id: svg-camera-icons
    choice: "Inline SVG paths for camera movement icons"
    reason: "Crisp rendering at any size, consistent with other UI icons"
---

# Phase 6 Plan 04: Scene Timeline Improvements Summary

**One-liner:** Final UI polish with speaker names in animation, arc template selector, camera movement SVG icons, and visual consistency CSS.

## Objectives Achieved

1. **Speaker names in animation step** - Shows who is speaking in each scene with voice assignment status
2. **Arc template selector** - Dropdown to change emotional arc template with summary stats
3. **Camera movement icons** - SVG icons for different camera movements (push-in, pan, tilt, etc.)
4. **Visual consistency CSS** - Card hover effects, smooth transitions, focus states, scrollbar styling

## Implementation Details

### Task 1: Speaker Names in Animation Step

Added speaker info display below scene progress in the animation step left panel:

```blade
@foreach($speakers as $speaker)
    <div style="background: {{ $hasVoice ? 'rgba(139, 92, 246, 0.2)' : 'rgba(168, 162, 158, 0.2)' }};">
        <svg><!-- microphone icon --></svg>
        <span>{{ $speaker }}</span>
        @if(!$hasVoice)
            <span>(no voice)</span>
        @endif
    </div>
@endforeach
```

**Visual indicators:**
- Purple background/text: Speaker has assigned voice
- Gray background/text: Speaker without voice assignment
- Microphone icon next to name
- "(no voice)" label when unassigned

### Task 2: Arc Template Selector

Added above the scene stats bar in storyboard:

```blade
@if(!empty($emotionalArcData['values']))
    <select wire:model.live="arcTemplate" wire:change="setArcTemplate($event.target.value)">
        @foreach($arcTemplates as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </select>

    {{-- Summary: Shots, Peak, Climax --}}
@endif
```

**Available templates:**
- Hollywood (Standard)
- Action (Multiple Peaks)
- Drama (Slow Build)
- Thriller (Tension Build)
- Comedy (Light Touch)
- Documentary (Even Pace)

**Summary displays:**
- Shot count
- Peak intensity percentage
- Climax scene number

### Task 3: Camera Movement SVG Icons

Added `getCameraMovementSvgPath()` helper function with 10 movement types:

| Movement | SVG Path Description |
|----------|---------------------|
| push-in | Arrow pointing right with dot |
| pull-out | Arrow pointing left with dot |
| pan-left | Arrow pointing left |
| pan-right | Arrow pointing right |
| tilt-up | Arrow pointing up |
| tilt-down | Arrow pointing down |
| static | Circle (dot) |
| slow-push | Dashed arrow right |
| slight-drift | Curved line |
| dolly | Line with wheels |

### Task 4: Visual Consistency CSS

Added comprehensive CSS improvements:

```css
/* Card hover effects */
.vw-scene-card {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.vw-scene-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}

/* Badge hover effects */
.vw-shot-badge:hover { filter: brightness(1.1); }

/* Focus states for accessibility */
select:focus, button:focus {
    outline: 2px solid rgba(139, 92, 246, 0.5);
    outline-offset: 2px;
}

/* Consistent scrollbar styling */
::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
}
```

## Commits

| Hash | Description |
|------|-------------|
| 31dc278 | feat(06-04): add speaker names to animation step |
| aa0913c | feat(06-04): add arc template selector to storyboard |
| f6f5f75 | feat(06-04): add camera movement icons to multi-shot modal |
| 8009380 | feat(06-04): add visual consistency improvements (CSS) |

## Verification Results

- [x] Speaker names displayed in animation view
- [x] Purple highlight for speakers with assigned voice
- [x] Gray highlight for speakers without voice
- [x] Microphone icon next to name
- [x] Template dropdown appears when arc data exists
- [x] All 6 templates available
- [x] Selecting template triggers setArcTemplate()
- [x] Summary shows shot count, peak, climax scene
- [x] Camera movement badge shows SVG icon
- [x] Different icons for push-in, pull-out, pan, tilt, static
- [x] Tooltip shows full movement name
- [x] Card hover effects work
- [x] Badge hover effects work
- [x] Smooth transitions for status changes
- [x] Focus states for accessibility
- [x] Consistent scrollbar styling

## Deviations from Plan

None - plan executed exactly as written.

## Files Modified

1. **animation.blade.php**
   - Added PHASE 6: Speaker Info section
   - Extracts unique speakers from speechSegments
   - Checks characterBible for voice assignment
   - Displays with microphone icon and status

2. **storyboard.blade.php**
   - Added PHASE 6: Arc Template Selector section
   - Template dropdown with 6 options
   - Arc summary display (shots, peak, climax)
   - Added PHASE 6: Visual Consistency CSS section
   - Card hover effects, badge effects, focus states, scrollbars

3. **multi-shot.blade.php**
   - Added getCameraMovementSvgPath() helper function
   - Updated camera movement badge to use SVG icons
   - Icons rendered inline with movement abbreviation
