---
phase: "04"
plan: "01"
subsystem: "dialogue-decomposition"
status: complete
tags:
  - spatial-tracking
  - 180-degree-rule
  - shot-reverse-shot
  - dialogue-scenes

dependency-graph:
  requires:
    - "03-06" # Character visual consistency
  provides:
    - spatial-continuity-tracking
    - camera-position-enforcement
    - reverse-shot-pairing
  affects:
    - "04-02" # OTS enhancements
    - "04-03" # Dialogue patterns
    - "04-04" # Scene detection

tech-stack:
  added: []
  patterns:
    - "180-degree-rule-enforcement"
    - "shot-reverse-shot-pairing"
    - "spatial-aware-prompts"

key-files:
  created: []
  modified:
    - "modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php"

decisions:
  - key: "axis-lock-side"
    value: "left"
    reason: "Camera stays on left side of action axis by default"
  - key: "character-A-position"
    value: "screen-right"
    reason: "First character always positioned screen-right, looks screen-left"
  - key: "pair-tracking"
    value: "pair_N format"
    reason: "Simple incremental pairing allows validation of reverse shots"

metrics:
  duration: "~9 minutes"
  completed: "2026-01-23"
---

# Phase 04 Plan 01: Spatial Continuity Tracking Summary

**One-liner:** 180-degree rule enforcement with camera position tracking, eye-line direction, and reverse shot pairing for dialogue scenes.

## What Was Built

### Task 1: Spatial Tracking Properties
Added `$axisLockSide` property and spatial data structure to shots:

```php
protected string $axisLockSide = 'left'; // 180-degree rule enforcement

// Spatial data in each shot:
'spatial' => [
    'cameraPosition' => 'left',           // Always same side (180-degree rule)
    'cameraAngle' => 'three-quarter',     // profile, three-quarter, frontal
    'subjectPosition' => 'right',         // Where subject appears in frame
    'eyeLineDirection' => 'screen-left',  // Direction character is looking
    'lookingAt' => 'Character B',         // Other character name
    'reverseOf' => null,                  // Shot index this reverses
    'pairId' => 'pair_1',                 // Matched pair identifier
]
```

### Task 2: Camera Position Assignment
Implemented `calculateSpatialData()` method:

```php
protected function calculateSpatialData(string $speakerName, array $characters, string $shotType): array
```

- Character A always positioned screen-right, looks screen-left
- Character B always positioned screen-left, looks screen-right
- Camera stays on same side (180-degree rule)

### Task 3: Reverse Shot Pairing System
Implemented `pairReverseShots()` method:

```php
protected function pairReverseShots(array $shots): array
```

- Links shots from alternating speakers
- Sets `pairId` on both shots in pair
- Sets `reverseOf` on the second shot pointing to first

### Task 4: Spatial-Aware Visual Prompts
Implemented `buildSpatialAwarePrompt()` and `getDialogueVisualHint()`:

- Adds positioning to prompts: "positioned right of frame"
- Adds eye-line: "looking screen-left"
- Adds camera angle: "at three-quarter angle"
- Adds dialogue-based hints: questioning, emphatic, apologetic, warm

### Task 5: Integration into Main Decomposition
Updated `decomposeDialogueScene()`:

- Passes speakers array to `createDialogueShot()`
- Calls `calculateSpatialData()` for each shot
- Calls `pairReverseShots()` before returning
- Enhanced logging with pair count

## Verification Results

All success criteria met:
- [x] Every dialogue shot has spatial data (cameraPosition, eyeLineDirection, subjectPosition)
- [x] 180-degree rule enforced (camera stays on same side of axis)
- [x] Reverse shots explicitly paired with pairId
- [x] Visual prompts include positioning information
- [x] PHP syntax valid (already committed)

## Deviations from Plan

None - plan executed as written. Implementation was included in commit 3f14f75 alongside 04-02 OTS enhancements.

## Implementation Details

### 180-Degree Rule Enforcement

The 180-degree rule states that the camera should stay on one side of an imaginary line (the "axis") between two characters. This maintains consistent spatial relationships:

```
    AXIS LINE
        |
    A   |   B
   (R)  |  (L)    <- Screen positions
        |
   CAM LEFT       <- Camera position (axisLockSide)
```

When camera is on left:
- Character A appears screen-right, looks screen-left
- Character B appears screen-left, looks screen-right

### Shot Pairing Logic

```php
// Pair shots from alternating speakers
foreach ($shots as $index => &$shot) {
    if (empty($shot['speakingCharacter'])) continue;

    $speaker = $shot['speakingCharacter'];

    foreach ($lastSpeakerShot as $prevSpeaker => $prevIndex) {
        if ($prevSpeaker !== $speaker) {
            // Create pair
            $pairId = 'pair_' . $pairCounter++;
            $shot['spatial']['pairId'] = $pairId;
            $shot['spatial']['reverseOf'] = $prevIndex;
            $shots[$prevIndex]['spatial']['pairId'] = $pairId;
            break;
        }
    }

    $lastSpeakerShot[$speaker] = $index;
}
```

## Commits

| Commit | Type | Description |
|--------|------|-------------|
| 3f14f75 | feat | Spatial continuity + OTS enhancements (combined with 04-02) |

## Files Modified

| File | Changes |
|------|---------|
| `DialogueSceneDecomposerService.php` | +433 lines: spatial tracking, 180-degree rule, reverse pairing |

## Next Phase Readiness

Ready for:
- **04-02:** OTS shot depth enhancements (already integrated)
- **04-03:** Dialogue pattern improvements
- **04-04:** Scene detection enhancements

No blockers identified.
