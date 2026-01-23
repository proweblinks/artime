---
phase: 03-hollywood-production-system
plan: 04
subsystem: wizard-automation
tags: ["auto-proceed", "pipeline", "automation", "progress-tracking"]

dependency-graph:
  requires: ["03-01", "03-02", "03-03"]
  provides: ["auto-proceed-pipeline", "progress-indicator"]
  affects: ["03-05", "03-06", "future-ui-components"]

tech-stack:
  added: []
  patterns: ["event-driven-automation", "livewire-computed-properties", "progress-weighting"]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

decisions:
  - id: "auto-proceed-default"
    title: "Auto-proceed disabled by default"
    choice: "false"
    rationale: "Users may want manual control; enable explicitly"
  - id: "progress-weights"
    title: "Progress percentage weights"
    choice: "Script 20%, Storyboard 40%, Animation 30%, Assembly 10%"
    rationale: "Reflects actual processing time and effort per stage"
  - id: "event-dispatch-pattern"
    title: "Use Livewire events for auto-proceed triggers"
    choice: "dispatch() + #[On()] listeners"
    rationale: "Allows async handling and decoupling"
  - id: "video-check-multishot"
    title: "Check multiShotMode first for video completion"
    choice: "multiShotMode['decomposedScenes'] primary, animation fallback"
    rationale: "Current implementation uses multi-shot mode as primary"

metrics:
  duration: "4 minutes"
  completed: "2026-01-23"
---

# Phase 3 Plan 04: Auto-Proceed Pipeline Summary

**One-liner:** Auto-proceed functionality with progress tracking that flows script -> storyboard -> animation -> assembly automatically when enabled.

## What Was Built

### 1. Auto-Proceed Property (Task 1)
Added new property to control auto-proceed behavior:
```php
public bool $autoProceedEnabled = false;
```
- Located in PHASE 3: Auto-Proceed Pipeline section
- Defaults to false for user control
- Can be toggled via UI (future task)

### 2. Script -> Storyboard Auto-Proceed (Task 2)
After `generateScript()` completes successfully:
```php
if ($this->autoProceedEnabled && !empty($this->script['scenes'])) {
    $this->goToStep(4);
    $this->dispatch('auto-proceed-storyboard');
}
```
- Navigates to Storyboard step (4)
- Dispatches event to trigger batch image generation

### 3. Storyboard -> Animation Auto-Proceed (Task 3)
In `pollImageJobs()` when all images complete:
```php
if ($pendingCount === 0 && $this->autoProceedEnabled && $this->allScenesHaveImages()) {
    $this->goToStep(5);
    $this->dispatch('auto-proceed-animation');
}
```
Helper method `allScenesHaveImages()`:
- Checks storyboard scenes for imageUrl
- Supports multi-shot mode (checks all shots)
- Returns true only when ALL scenes have images

### 4. Animation -> Assembly Auto-Proceed (Task 4)
In `pollVideoJobs()` when all videos complete:
```php
if ($this->autoProceedEnabled && $this->allScenesHaveVideos()) {
    $this->goToStep(6);
    $this->dispatch('notify', ['type' => 'success', 'message' => '...']);
}
```
Helper method `allScenesHaveVideos()`:
- Checks multiShotMode['decomposedScenes'] first (primary)
- Falls back to animation['scenes'] (legacy)
- Returns true only when ALL scenes have videos

### 5. Overall Progress Indicator (Task 5)
Computed property `getOverallProgressProperty()` returns:
```php
[
    'percentage' => 0-100,
    'stage' => 'Not started' | 'Script ready' | 'Generating images' | 'Animating' | 'Complete',
    'details' => [
        'script' => 0-100,
        'storyboard' => 0-100,
        'animation' => 0-100,
        'assembly' => 0-100
    ]
]
```
Weights:
- Script: 20% (quick AI generation)
- Storyboard: 40% (most AI calls, longest wait)
- Animation: 30% (video generation)
- Assembly: 10% (mostly review)

Supporting helper methods:
- `calculateStoryboardProgress()`: Handles multi-shot mode
- `calculateAnimationProgress()`: Checks multiShotMode first

## Event Listeners Added

| Event | Handler | Purpose |
|-------|---------|---------|
| `auto-proceed-storyboard` | `handleAutoProceedStoryboard()` | Triggers batch image generation |
| `auto-proceed-animation` | `handleAutoProceedAnimation()` | Triggers batch video generation |

## Key Methods Added

| Method | Type | Purpose |
|--------|------|---------|
| `$autoProceedEnabled` | Property | Toggle for auto-proceed |
| `handleAutoProceedStoryboard()` | Event Listener | Start batch images |
| `handleAutoProceedAnimation()` | Event Listener | Start batch videos |
| `allScenesHaveImages()` | Protected | Check image completion |
| `allScenesHaveVideos()` | Protected | Check video completion |
| `getOverallProgressProperty()` | Computed | Overall pipeline progress |
| `calculateStoryboardProgress()` | Protected | Image generation progress |
| `calculateAnimationProgress()` | Protected | Video generation progress |

## Flow Diagram

```
[Script Generation]
        |
        v
(autoProceedEnabled?) --Yes--> [goToStep(4)] --> [dispatch('auto-proceed-storyboard')]
                                                          |
                                                          v
                                                   [Batch Image Gen]
                                                          |
                                                          v
                                               (allScenesHaveImages?) --Yes--> [goToStep(5)]
                                                                                    |
                                                                              [dispatch('auto-proceed-animation')]
                                                                                    |
                                                                                    v
                                                                             [Batch Video Gen]
                                                                                    |
                                                                                    v
                                                                         (allScenesHaveVideos?) --Yes--> [goToStep(6)]
                                                                                                              |
                                                                                                              v
                                                                                                       [Notify Success]
```

## Deviations from Plan

None - plan executed exactly as written.

## Testing Notes

To verify auto-proceed functionality:
1. Enable `autoProceedEnabled = true` via console or future UI toggle
2. Generate a script
3. Observe automatic progression through steps 4, 5, 6
4. Check logs for "Auto-proceeding" messages

To verify progress indicator:
1. Access `$this->overallProgress` in Blade template
2. Check percentage updates as each stage completes
3. Verify stage labels match current activity

## Next Phase Readiness

Ready for:
- **03-05**: UI toggle for auto-proceed (checkbox/switch)
- **03-06**: Progress bar component using `overallProgress`
- Future: Pause/resume auto-proceed, step skip options

## Commits

| Hash | Message |
|------|---------|
| `ebc8159` | feat(03-04): add auto-proceed pipeline for wizard steps |

## Files Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (+108 lines)
  - Added auto-proceed property section
  - Added event listeners for storyboard/animation auto-proceed
  - Added helper methods for completion checks
  - Added computed property for progress tracking
