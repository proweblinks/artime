---
phase: 14-cinematic-flow-action-scenes
plan: 02
subsystem: scene-decomposition
tags: [action-scenes, scene-type-detection, visual-continuity, coverage-patterns]

dependency-graph:
  requires: [14-01]
  provides:
    - decomposeActionScene method
    - scene type routing
    - visual continuity metadata
  affects: [downstream-prompt-builders]

tech-stack:
  added: []
  patterns:
    - "ShotContinuityService getCoveragePattern('action')"
    - "SceneTypeDetectorService detectSceneType"
    - "Visual continuity context (location, time, weather)"

key-files:
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

decisions:
  - id: action-pattern-source
    choice: "Use ShotContinuityService.getCoveragePattern('action') instead of hardcoding"
    rationale: "Single source of truth for coverage patterns, already exists"
  - id: action-type-mapping
    choice: "'tracking' maps to 'medium' with cameraMovement, 'insert' maps to 'extreme-close-up'"
    rationale: "Action patterns need to map to existing dialogueShotTypes"
  - id: visual-continuity-metadata
    choice: "Attach sceneContext and visualContinuityApplied flag to each shot"
    rationale: "Non-invasive - lets downstream prompt builders use context optionally"
  - id: mixed-scene-handling
    choice: "Mixed scenes use speech-driven path (full hybrid deferred)"
    rationale: "Speech segments already drive shots; complex interleaving can be future enhancement"

metrics:
  duration: ~4 minutes
  completed: 2026-01-23
---

# Phase 14 Plan 02: Scene Type Routing and Action Scene Decomposition Summary

Action scenes produce varied shot types (establishing, wide, medium, tracking, close-up, insert) using ShotContinuityService's getCoveragePattern('action'), with SceneTypeDetectorService routing scenes to appropriate decomposition paths.

## Tasks Completed

### Task 1: Add decomposeActionScene method to DialogueSceneDecomposerService

- Added `decomposeActionScene(array $scene, int $sceneIndex, array $context = [])` method
- Uses `ShotContinuityService->getCoveragePattern('action')` for Hollywood coverage pattern
- Extracts "action beats" from narration using `extractActionBeats()` helper
- Creates one shot per beat, cycling through: establishing -> wide -> medium -> tracking -> close-up -> insert
- Sets `isActionShot=true` flag on all action shots
- Applies transition validation and visual continuity

Helper methods added:
- `mapActionTypeToDialogueType()` - maps action types to dialogueShotTypes ('tracking' -> 'medium', 'insert' -> 'extreme-close-up')
- `getCameraMovementForActionType()` - returns appropriate camera movement per action type
- `extractActionBeats()` - splits narration by sentences, semicolons, "and then" patterns
- `calculateActionBeatIntensity()` - position-based + keyword intensity calculation
- `calculateActionBeatDuration()` - 3-10 seconds based on text length

### Task 2: Add scene type detection and routing in VideoWizard

- Integrated `SceneTypeDetectorService` in `decomposeSceneWithDynamicEngine()`
- Calls `detectSceneType()` at start of method (before speech-driven path)
- Routes `sceneType === 'action' && empty($lipSyncSegments)` to `decomposeActionScene()`
- Logs scene type and confidence for debugging
- Mixed scenes (SCNE-03) continue to use speech-driven path
- Added documentation comment for future hybrid handling

### Task 3: Add visual prompt continuity helper

- Added `ensureVisualContinuity(array $shots, array $scene)` method
- Extracts context: location, timeOfDay, weather from narration
- Attaches `sceneContext` array to each shot
- Sets `visualContinuityApplied=true` flag for downstream builders
- Called at end of both `enhanceShotsWithDialoguePatterns()` and `decomposeActionScene()`
- Placed AFTER `validateAndFixTransitions()` per Plan 14-01 ordering

Helper methods added:
- `extractLocation()` - finds "in the", "at the" patterns + common location keywords
- `extractTimeOfDay()` - detects morning/afternoon/evening/night/dawn/dusk
- `extractWeather()` - detects rain/sun/storm/fog/snow keywords

## Deviations from Plan

None - plan executed exactly as written.

## Commits

| Hash | Message |
|------|---------|
| 9c25919 | feat(14-02): add decomposeActionScene method for action scene coverage (SCNE-02) |
| c65259a | feat(14-02): add scene type detection and routing in VideoWizard |

## Key Code Patterns

### Scene Type Detection (VideoWizard.php)
```php
$sceneTypeDetector = app(\Modules\AppVideoWizard\Services\SceneTypeDetectorService::class);
$sceneTypeResult = $sceneTypeDetector->detectSceneType($scene, [
    'sceneIndex' => $sceneIndex,
    'totalScenes' => count($this->script['scenes'] ?? []),
]);
$sceneType = $sceneTypeResult['sceneType'] ?? 'dialogue';
```

### Action Coverage Pattern Usage
```php
$shotContinuityService = app(\Modules\AppVideoWizard\Services\ShotContinuityService::class);
$actionCoveragePattern = $shotContinuityService->getCoveragePattern('action');
// Returns: [{type: 'establishing'}, {type: 'wide'}, {type: 'medium'}, {type: 'tracking'}, {type: 'close-up'}, {type: 'insert'}]
```

### Visual Continuity Metadata
```php
$shot['sceneContext'] = [
    'location' => 'Office',      // or null
    'timeOfDay' => 'evening',    // or null
    'weather' => 'rain',         // or null
];
$shot['visualContinuityApplied'] = true;
```

## Verification Results

All verification criteria passed:
1. Scene type detection integrated: `detectSceneType` in VideoWizard.php
2. Action decomposition exists: `decomposeActionScene` in DialogueSceneDecomposerService.php
3. Uses ShotContinuityService for pattern: `getCoveragePattern('action')` call
4. Visual continuity helper exists: `ensureVisualContinuity` method
5. Routing logs exist: `action-coverage` and `Action scene detected` in VideoWizard.php

## Success Criteria Met

1. **Action scenes route to decomposeActionScene**: Scenes with `sceneType === 'action'` and no lip-sync segments use action decomposition
2. **Shot types follow Hollywood pattern**: Uses ShotContinuityService's action coverage pattern (establishing -> wide -> medium -> tracking -> close-up -> insert)
3. **Scene type detected and logged**: SceneTypeDetectorService integration with debug logging
4. **Visual continuity metadata attached**: sceneContext with location/time/weather on all shots
5. **Mixed scenes use speech-driven path**: Documented, full hybrid deferred to future enhancement

## Next Phase Readiness

Phase 14 is now complete. Both plans executed:
- **Plan 14-01**: Transition validation (jump cut detection, scale adjustment)
- **Plan 14-02**: Action scene decomposition (coverage patterns, scene routing, visual continuity)

System is ready for Milestone 8 completion:
- All speech-driven shot creation working (Phase 11)
- Shot/reverse-shot patterns validated (Phase 12)
- Dynamic camera intelligence applied (Phase 13)
- Cinematic flow ensured (Phase 14)

---
*Executed by Claude Opus 4.5*
*Duration: ~4 minutes*
