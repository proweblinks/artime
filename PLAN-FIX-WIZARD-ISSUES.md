# Video Wizard Critical Fixes Plan

## Implementation Status: COMPLETE

| Phase | Status | Commits |
|-------|--------|---------|
| Phase 1: Immediate Fixes | ✅ Complete | `4b56e0d` |
| Phase 2: Progressive Generation | ✅ Complete | `a586156`, `c142ded` |
| Phase 3: Scene Overwrite Modal | ✅ Complete | `93dc859` |
| Medium Priority: Error Recovery | ✅ Complete | `9e7fcc5` |
| Low Priority: Unit Tests | ✅ Complete | (latest commit) |

---

## Overview

Two critical issues identified plus major architectural improvements:

1. **TypeError on Character Portrait Generation** - Type mismatch when generating character portraits
2. **Progressive Scene Generation System** - Replace broken interpolation with batch-based generation

---

## Issue 1: Character Portrait TypeError ✅ FIXED

### Root Cause
**File:** `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (line 5750)
**Error:** `ImageGenerationService::buildImagePrompt(): Argument #6 ($sceneIndex) must be of type ?int, string given`

```php
// PROBLEM: Passing string when ?int expected
'sceneIndex' => 'char_' . $index  // ❌ String 'char_0'
```

### Fix Applied
Pass `null` for sceneIndex (portraits don't belong to scenes):
```php
'sceneIndex' => null,  // ✅ Correct
```

**Fixed in locations:**
- Character portrait generation
- Style reference generation
- Location reference generation
- Shot image generation (uses actual `$sceneIndex` integer)

---

## Issue 2: Progressive Scene Generation System ✅ IMPLEMENTED

### The Problem
Current system tried to generate ALL scenes in one AI call:
- For 180-second video with 6s/scene = 30 scenes needed
- AI struggled to generate 30+ quality scenes at once
- Returned fewer scenes than requested
- System filled gaps with FAKE "Transition" placeholder scenes
- Result: Bad quality, generic content

### The Solution: Batch-Based Progressive Generation

Generate scenes in controlled batches of 5, with full context continuity.

```
┌─────────────────────────────────────────────────────────────────┐
│  🎬 Script Generation                                           │
│                                                                 │
│  Target: 180 seconds → 30 scenes (6s each)                     │
│                                                                 │
│  ████████████████░░░░░░░░░░░░░░░░  15 / 30 scenes              │
│                                                                 │
│  ✅ Batch 1: Scenes 1-5    [Complete]                          │
│  ✅ Batch 2: Scenes 6-10   [Complete]                          │
│  ✅ Batch 3: Scenes 11-15  [Complete]                          │
│  ⏳ Batch 4: Scenes 16-20  [Pending]                           │
│  ⏳ Batch 5: Scenes 21-25  [Pending]                           │
│  ⏳ Batch 6: Scenes 26-30  [Pending]                           │
│                                                                 │
│  [🚀 Generate Next Batch]     [⚡ Auto-Generate All Remaining] │
└─────────────────────────────────────────────────────────────────┘
```

---

## Implemented Architecture

### 1. Scene Count Calculation (Exact) ✅

```php
// In VideoWizard.php
public function calculateSceneCount(): int
{
    $targetDuration = $this->targetDuration ?? 60;
    $productionType = $this->productionType ?? $this->production['type'] ?? 'standard';

    $sceneDurations = [
        'tiktok-viral' => 3,
        'youtube-short' => 5,
        'short-form' => 4,
        'standard' => 6,
        'cinematic' => 8,
        'documentary' => 10,
        'long-form' => 8,
    ];

    $sceneDuration = $sceneDurations[$productionType] ?? 6;
    return (int) ceil($targetDuration / $sceneDuration);
}
```

### 2. Batch Generation State ✅

```php
public array $scriptGeneration = [
    'status' => 'idle',              // 'idle' | 'generating' | 'paused' | 'complete'
    'targetSceneCount' => 0,         // Total scenes needed
    'generatedSceneCount' => 0,      // Scenes generated so far
    'batchSize' => 5,                // Scenes per batch
    'currentBatch' => 0,             // Current batch index (0-indexed)
    'totalBatches' => 0,             // Total batches needed
    'batches' => [],                 // Batch status tracking
    'autoGenerate' => false,         // Auto-continue to next batch
    'maxRetries' => 3,               // Max retry attempts per batch
    'retryDelayMs' => 1000,          // Base delay for exponential backoff
];
```

### 3. Batch Status Structure ✅

```php
$batches = [
    [
        'batchNumber' => 1,
        'startScene' => 1,
        'endScene' => 5,
        'status' => 'complete',      // 'pending' | 'generating' | 'retrying' | 'complete' | 'error'
        'generatedAt' => '2024-01-08 12:00:00',
        'sceneIds' => ['scene-1', 'scene-2', ...],
        'retryCount' => 0,           // Track retry attempts
        'lastError' => null,         // Store error message for debugging
    ],
    // ...
];
```

### 4. Context Continuity System ✅

Each batch receives context from previous scenes to ensure narrative flow:

```php
public function buildBatchContext(array $existingScenes, int $batchNumber, int $totalBatches): string
{
    // Opening context for first batch
    if (empty($existingScenes)) {
        return "=== NARRATIVE POSITION: OPENING ===\n...";
    }

    // Summary + last 3 scenes for continuity
    // + narrative position guidance (Setup → Development → Escalation → Resolution)
}
```

### 5. Error Recovery with Exponential Backoff ✅

```php
protected function handleBatchError(int $batchIndex, string $errorMessage): void
{
    $batch = &$this->scriptGeneration['batches'][$batchIndex];
    $batch['retryCount']++;

    if ($batch['retryCount'] < $maxRetries) {
        // Calculate delay: 1s, 2s, 4s
        $delayMs = $baseDelay * pow(2, $retryCount - 1);
        $batch['status'] = 'retrying';

        // Dispatch delayed retry event
        $this->dispatch('retry-batch-delayed', [...]);
    } else {
        $batch['status'] = 'error';
    }
}
```

### 6. Progress Persistence ✅

Script generation state is saved to database via `content_config` and restored on page reload:

```php
// In saveProject()
'content_config' => [
    'scriptGeneration' => $this->scriptGeneration,
    // ...
],

// In loadProject()
if (isset($config['scriptGeneration'])) {
    $this->scriptGeneration = array_merge($this->scriptGeneration, $config['scriptGeneration']);
    // Auto-set generating/retrying to paused on reload
}
```

---

## UI Components Implemented

### 1. Progressive Generation Panel ✅

**File:** `modules/AppVideoWizard/resources/views/livewire/steps/script.blade.php`

Features:
- Progress bar with percentage
- Batch list with status icons (✅⏳🔄⏸️❌)
- Pulse animation for active batches
- Retry count display (1/3, 2/3, etc.)
- "Generate Next Batch" button
- "Auto-Generate All" button
- Reset button on completion

### 2. Scene Overwrite Confirmation Modal ✅

When clicking "Generate Script" with existing scenes:
- ⚠️ Warning modal appears
- 🔄 **Replace All** - Delete existing and start fresh
- ➕ **Add More** - Keep existing and generate additional scenes
- Cancel option

### 3. Loading States ✅

- Spinner animations during generation
- Pulse effect on active batches
- Disabled state on buttons during generation
- Wire loading indicators

---

## Files Modified

### Phase 1: Immediate Fixes ✅
| File | Change |
|------|--------|
| `VideoWizard.php` | Fixed portrait/location/style sceneIndex (pass `null` or actual int) |

### Phase 2: Progressive Generation System ✅
| File | Change |
|------|--------|
| `VideoWizard.php` | Added `$scriptGeneration` state, batch methods, modal handling |
| `ScriptGenerationService.php` | Added `generateSceneBatch()`, `buildBatchContext()`, `parseBatchResponse()` |
| `script.blade.php` | Added progressive generation UI panel, modal, JavaScript handlers |

### Phase 3: Remove Old Code ✅
| File | Change |
|------|--------|
| `ScriptGenerationService.php` | Removed `interpolateScenes()` and transition scene methods |

### Medium Priority ✅
| File | Change |
|------|--------|
| `VideoWizard.php` | Error recovery with exponential backoff, progress persistence |
| `script.blade.php` | Retrying status, pulse animations, retry count display |

### Low Priority ✅
| File | Change |
|------|--------|
| `tests/Feature/VideoWizard/ScriptGenerationBatchTest.php` | Unit tests for batch generation |

---

## Benefits Achieved

| Aspect | Before | After |
|--------|--------|-------|
| Scene Count | Approximate, often wrong | Exact, based on duration |
| Quality | Degrades with more scenes | Consistent (5 at a time) |
| Fake Content | "Transition" placeholders | Never - all real content |
| User Control | All-or-nothing | Batch by batch |
| Visibility | Black box | Clear progress tracking |
| Reliability | Often fails on long videos | Robust batching with retry |
| Narrative Flow | Can be disjointed | Context-aware continuity |
| Error Handling | Single failure = total loss | Auto-retry with backoff |
| Browser Refresh | Loses progress | Resumes from saved state |

---

## Testing Checklist

- [x] Scene count exactly matches duration calculation
- [x] Each batch generates exactly 5 scenes (or remaining)
- [x] Context from previous scenes is passed correctly
- [x] Narrative arc guidance changes based on position
- [x] Progress UI shows correct batch status
- [x] "Generate Next Batch" works correctly
- [x] "Auto-Generate All" continues until complete
- [x] No "Transition" placeholder scenes ever created
- [x] Character portrait generation works without TypeError
- [x] Long videos (180s+) generate all scenes successfully
- [x] Error recovery with exponential backoff works
- [x] Progress persists across browser refresh
- [x] Scene overwrite modal shows for existing scenes
- [x] Replace/Append options work correctly

---

## Commit History

```
9e7fcc5 Add error recovery, progress persistence, and enhanced loading states
93dc859 Add scene overwrite confirmation modal for progressive generation
c142ded Remove deprecated interpolateScenes and transition scene methods
a586156 Implement progressive batch-based scene generation system
4b56e0d Fix sceneIndex type errors in image generation calls
164b098 Update plan with progressive batch-based scene generation architecture
486d3f0 Add detailed fix plan for character portrait and transition scenes issues
55311ce Add robust JSON parsing recovery for script generation
```

---

## Deploy Commands

```bash
cd /var/www/artime
git fetch origin claude/fix-scene-count-issue-FIOtX
git checkout claude/fix-scene-count-issue-FIOtX
git pull origin claude/fix-scene-count-issue-FIOtX
php artisan view:clear
php artisan cache:clear
```

---

## Unit Tests

Run tests with:
```bash
php artisan test tests/Feature/VideoWizard/ScriptGenerationBatchTest.php
```

Tests cover:
- `buildBatchContext` - Opening, Setup, Development, Escalation, Resolution phases
- Scene count calculations for short/medium/long videos
- Batch structure initialization
- Exponential backoff calculations
- State persistence and reload handling
