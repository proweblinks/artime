# Phase 5 Plan 02: Intensity Curve Smoothing Summary

> Smoothed intensity curves with genre-specific arc templates for cinematic pacing

---

## Metadata

| Field | Value |
|-------|-------|
| Phase | 05-emotional-arc-system |
| Plan | 02 |
| Completed | 2026-01-23 |
| Duration | ~10 minutes |

---

## What Was Built

### Task 1: Intensity Smoothing Algorithm
Added two smoothing methods:

1. **smoothIntensityCurve()** - Weighted moving average
   - Uses odd window size (default 3)
   - Weight decreases with distance from center: `1 / (1 + abs(j))`
   - Preserves climax peaks (restores values > 0.8 that were smoothed too much)

2. **exponentialSmooth()** - Exponential smoothing
   - Alpha factor 0.3 by default (lower = smoother)
   - Good for gradual transitions

### Task 2: Arc Shape Templates
Added `ARC_TEMPLATES` constant with 6 genre-specific intensity curves:

| Template | Character | Climax Position |
|----------|-----------|-----------------|
| hollywood | Standard 3-act | 70% |
| action | Multiple peaks | 80% |
| drama | Slow build | 75% |
| thriller | Sustained tension | 75% |
| comedy | Lighter tone | 70% |
| documentary | Flat/even | N/A |

Each template defines intensity targets at key narrative positions (0%, 25%, 50%, etc.).

### Task 3: Curve Blending
Added methods for blending actual intensities with templates:

1. **getArcTargetIntensity()** - Linear interpolation between template points
2. **blendWithArcTemplate()** - Blends actual with template (default 50% weight)
3. **getProcessedIntensityCurve()** - Main entry point combining:
   - Raw intensity extraction
   - Template blending (40% weight)
   - Smoothing
   - Statistics calculation

### Task 4: Enhanced extractEmotionalArc()
Updated method signature to accept template and smoothing parameters:

```php
public function extractEmotionalArc(
    array $moments,
    string $template = 'hollywood',
    bool $smooth = true
): array
```

Now returns:
- `values` - Raw intensities
- `smoothed` - Processed intensities
- `template` - Template used
- `stats` - Curve statistics (min, max, average, range)

---

## Key Files Modified

| File | Changes |
|------|---------|
| `Services/NarrativeMomentService.php` | +551 lines, -24 lines |

---

## Commits

| Hash | Message |
|------|---------|
| `ab71ada` | feat(05-02): add intensity curve smoothing and arc templates |

---

## Technical Decisions

| Decision | Rationale |
|----------|-----------|
| Weighted moving average | More responsive than simple average, respects local peaks |
| 40% template blend | Preserves content emotion while ensuring cinematic structure |
| Peak preservation | Climax moments should not be smoothed away |
| Protected methods | Smoothing/interpolation are internal; only blendWithArcTemplate and getProcessedIntensityCurve are public |

---

## Deviations from Plan

None - plan executed exactly as written.

---

## Integration Points

- `extractEmotionalArc()` - Now returns smoothed values for shot intensity assignment
- `getProcessedIntensityCurve()` - Main entry point for intensity processing
- `blendWithArcTemplate()` - Can be called directly for custom blending

---

## Next Steps

Plan 05-03: Shot-to-Beat Mapping will use these smoothed intensities to:
- Align shots with narrative beats
- Apply intensity-appropriate cinematography
- Create professional pacing
