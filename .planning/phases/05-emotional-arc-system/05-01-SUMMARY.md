---
phase: 05-emotional-arc-system
plan: 01
subsystem: narrative-intelligence
tags: [climax-detection, peak-detection, emotional-arc, narrative-analysis]
dependency-graph:
  requires: [02-narrative-intelligence]
  provides: [intelligent-climax-detection, content-analysis, peak-detection]
  affects: [05-02-arc-templates]
tech-stack:
  added: []
  patterns: [keyword-analysis, peak-detection-algorithm, combined-scoring]
key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/NarrativeMomentService.php
decisions:
  - id: climax-detection-method
    choice: content + intensity + position combined scoring
    reason: More accurate than position-only detection
  - id: peak-threshold
    choice: 0.15 default, 0.1 for multi-peak
    reason: Balance between sensitivity and noise
  - id: climax-score-weights
    choice: 40% intensity, 40% content, 10% prominence, 10% position bonus
    reason: Equal weight to what content says vs measured intensity
metrics:
  duration: ~15min
  completed: 2026-01-23
---

# Phase 5 Plan 01: Intelligent Climax Detection Summary

Content-based climax detection replacing fixed 70% position rule.

## What Was Built

### 1. Climax Keyword Detection (Task 1)

Added keyword constants for identifying narrative peaks:

```php
protected const CLIMAX_KEYWORDS = [
    // Action peaks
    'reveals', 'discovers', 'confronts', 'attacks', 'escapes',
    'transforms', 'explodes', 'crashes', 'collapses', 'breaks',
    // Emotional peaks
    'screams', 'cries', 'confesses', 'declares', 'realizes',
    // Narrative turns
    'finally', 'suddenly', 'at last', 'the truth', 'moment of truth',
    // Conflict peaks
    'showdown', 'battle', 'fight', 'duel', 'confrontation',
];

protected const RESOLUTION_KEYWORDS = [
    'peace', 'calm', 'quiet', 'settles', 'rests', 'heals',
    'forgiven', 'reconciled', 'together', 'home', 'safe',
];
```

**Method:** `analyzeClimaxIndicators(string $text): array`
- Checks text against CLIMAX_KEYWORDS (+0.15 per match)
- Checks against RESOLUTION_KEYWORDS (-0.1 per match)
- Analyzes punctuation (exclamations +0.1 each, max 0.3)
- Detects ALL CAPS words for emphasis (+0.1 each, max 0.2)
- Returns: score (0-1), triggers array, isLikelyClimax (score >= 0.3)

### 2. Peak Detection Algorithm (Task 2)

**Method:** `detectIntensityPeaks(array $intensities, float $threshold = 0.15): array`
- Finds local maxima in intensity array
- Peak = value greater than both neighbors
- Prominence = minimum difference from neighbors
- Filters peaks by threshold
- Returns sorted by intensity (descending)
- Fallback: returns max value position if no peaks found

**Method:** `identifyPrimaryClimax(array $moments): int`
- Extracts intensities from moments
- Calls detectIntensityPeaks()
- Scores each peak using combined formula:
  - 40% intensity weight
  - 40% content analysis score
  - 10% prominence
  - 10% position bonus (if 50-85% through narrative)
- Returns highest-scored peak index

### 3. Unified Climax Detection (Task 3)

**Method:** `detectClimaxFromContent(array $moments): array` (public)

Returns comprehensive climax data:
```php
[
    'index' => $climaxIndex,           // Which moment is climax
    'confidence' => $confidence,        // 0-1 detection confidence
    'method' => 'content'|'intensity_peak'|'position_fallback',
    'position' => $climaxIndex / ($count - 1), // 0-1 position
    'intensity' => $climaxIntensity,    // Intensity at climax
    'triggers' => ['reveals', 'confronts'], // Keywords found
    'peaks' => [...top 3 peaks...],     // Multi-climax support
    'isMultiClimax' => true|false,      // Has multiple peaks
]
```

Logs climax detection for debugging with position %, method, and triggers.

### 4. Updated applyEmotionalArc (Task 4)

Replaced fixed 70% climax rule:
```php
// OLD: $climaxIndex = max(1, intval($count * 0.7));
// NEW:
$climaxData = $this->detectClimaxFromContent($moments);
$climaxIndex = $climaxData['index'];
```

Climax moments now include metadata:
```php
$moment['isClimax'] = true;
$moment['climaxMetadata'] = [
    'method' => 'content',
    'confidence' => 0.75,
    'isMultiClimax' => false,
];
```

## Key Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Keyword scoring | +0.15 per climax keyword | Balances sensitivity with false positive prevention |
| Resolution penalty | -0.1 per resolution keyword | Prevents resolution scenes being marked as climax |
| Peak threshold | 0.15 (default), 0.1 (multi-peak) | Lower threshold for finding secondary peaks |
| Combined scoring | 40/40/10/10 weights | Equal emphasis on content analysis and measured intensity |
| Position bonus | 50-85% range | Hollywood three-act structure typical climax zone |

## How It Works

1. **Content Analysis:** Text scanned for climax indicators (keywords, punctuation, emphasis)
2. **Peak Detection:** Intensity array analyzed for local maxima
3. **Combined Scoring:** Each peak scored by intensity + content + prominence + position
4. **Primary Selection:** Highest combined score wins
5. **Arc Application:** Intensity curve shaped around detected climax

## Verification

All methods verified present:
- `CLIMAX_KEYWORDS` constant at line 28
- `RESOLUTION_KEYWORDS` constant at line 49
- `analyzeClimaxIndicators()` at line 636
- `detectIntensityPeaks()` at line 683
- `identifyPrimaryClimax()` at line 734
- `detectClimaxFromContent()` at line 784
- `applyEmotionalArc()` updated at line 580

## Deviations from Plan

None - plan executed exactly as written.

## Next Phase Readiness

Plan 05-01 provides foundation for:
- **05-02:** Arc templates can use detected climax position
- **05-03:** Pacing analysis can build on peak detection
- Future: Multi-climax narratives can use `isMultiClimax` flag

## Files Modified

| File | Changes |
|------|---------|
| `NarrativeMomentService.php` | +CLIMAX_KEYWORDS, +RESOLUTION_KEYWORDS, +analyzeClimaxIndicators(), +detectIntensityPeaks(), +identifyPrimaryClimax(), +detectClimaxFromContent(), updated applyEmotionalArc() |
