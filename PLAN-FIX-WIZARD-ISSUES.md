# Video Wizard Critical Fixes Plan

## Overview
Two critical issues identified plus a major architectural improvement:

1. **TypeError on Character Portrait Generation** - Type mismatch when generating character portraits
2. **Progressive Scene Generation System** - Replace broken interpolation with batch-based generation

---

## Issue 1: Character Portrait TypeError

### Root Cause
**File:** `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (line 5750)
**Error:** `ImageGenerationService::buildImagePrompt(): Argument #6 ($sceneIndex) must be of type ?int, string given`

```php
// PROBLEM: Passing string when ?int expected
'sceneIndex' => 'char_' . $index  // ❌ String 'char_0'
```

### Fix
Pass `null` for sceneIndex (portraits don't belong to scenes):
```php
'sceneIndex' => null,  // ✅ Correct
```

---

## Issue 2: Progressive Scene Generation System

### The Problem
Current system tries to generate ALL scenes in one AI call:
- For 180-second video with 6s/scene = 30 scenes needed
- AI struggles to generate 30+ quality scenes at once
- Returns fewer scenes than requested
- System fills gaps with FAKE "Transition" placeholder scenes
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
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│  Preview: Scene 15                                              │
│  "As the sun sets over the horizon, our journey takes..."      │
└─────────────────────────────────────────────────────────────────┘
```

---

## Architecture Design

### 1. Scene Count Calculation (Exact)

```php
// In ScriptGenerationService.php
public function calculateExactSceneCount(int $targetDuration, string $productionType = 'standard'): int
{
    // Scene duration based on production type
    $sceneDurations = [
        'tiktok-viral' => 3,      // Fast cuts, 3s per scene
        'youtube-short' => 5,     // Medium pace, 5s per scene
        'standard' => 6,          // Standard pace, 6s per scene
        'cinematic' => 8,         // Slower, cinematic, 8s per scene
        'documentary' => 10,      // Documentary style, 10s per scene
    ];

    $sceneDuration = $sceneDurations[$productionType] ?? 6;

    return (int) ceil($targetDuration / $sceneDuration);
}
```

### 2. Batch Generation State

```php
// New properties in VideoWizard.php
public array $scriptGeneration = [
    'status' => 'idle',              // 'idle' | 'generating' | 'paused' | 'complete'
    'targetSceneCount' => 0,         // Total scenes needed (e.g., 30)
    'generatedSceneCount' => 0,      // Scenes generated so far (e.g., 15)
    'batchSize' => 5,                // Scenes per batch
    'currentBatch' => 0,             // Current batch number (0-indexed)
    'totalBatches' => 0,             // Total batches needed
    'batches' => [],                 // Batch status tracking
    'autoGenerate' => false,         // Auto-continue to next batch
];
```

### 3. Batch Status Structure

```php
$batches = [
    [
        'batchNumber' => 1,
        'startScene' => 1,
        'endScene' => 5,
        'status' => 'complete',      // 'pending' | 'generating' | 'complete' | 'error'
        'generatedAt' => '2024-01-08 12:00:00',
        'sceneIds' => ['scene-1', 'scene-2', 'scene-3', 'scene-4', 'scene-5'],
    ],
    [
        'batchNumber' => 2,
        'startScene' => 6,
        'endScene' => 10,
        'status' => 'generating',
        // ...
    ],
    // ...
];
```

### 4. Context Continuity System

Each batch receives context from previous scenes to ensure narrative flow:

```php
protected function buildBatchContext(array $existingScenes, int $batchNumber): string
{
    if (empty($existingScenes)) {
        return "This is the OPENING of the video. Establish the hook and introduce the topic.";
    }

    $context = "PREVIOUSLY GENERATED SCENES:\n\n";

    // Include last 3 scenes for direct continuity
    $recentScenes = array_slice($existingScenes, -3);
    foreach ($recentScenes as $scene) {
        $context .= "Scene {$scene['id']}: {$scene['title']}\n";
        $context .= "Narration: " . substr($scene['narration'], 0, 150) . "...\n\n";
    }

    // Include narrative arc position
    $totalBatches = $this->scriptGeneration['totalBatches'];
    $position = $batchNumber / $totalBatches;

    if ($position < 0.2) {
        $context .= "\nNARRATIVE POSITION: Opening Act - Establish premise, hook viewer\n";
    } elseif ($position < 0.5) {
        $context .= "\nNARRATIVE POSITION: Rising Action - Build tension, develop story\n";
    } elseif ($position < 0.8) {
        $context .= "\nNARRATIVE POSITION: Climax - Peak moment, key revelations\n";
    } else {
        $context .= "\nNARRATIVE POSITION: Resolution - Conclude story, call to action\n";
    }

    return $context;
}
```

### 5. Batch Generation Prompt

```php
protected function buildBatchPrompt(
    string $topic,
    int $batchNumber,
    int $startScene,
    int $endScene,
    int $totalScenes,
    string $context,
    array $options
): string {
    $sceneCount = $endScene - $startScene + 1;
    $sceneDuration = $options['sceneDuration'] ?? 6;

    return <<<PROMPT
You are an expert video scriptwriter creating scenes {$startScene} to {$endScene} of a {$totalScenes}-scene video.

TOPIC: {$topic}

{$context}

REQUIREMENTS:
- Generate EXACTLY {$sceneCount} scenes (scenes {$startScene} to {$endScene})
- Each scene duration: {$sceneDuration} seconds
- Maintain narrative continuity with previous scenes
- Each scene must have unique, meaningful content
- NO generic transitions or filler content

RESPOND WITH THIS JSON STRUCTURE:
{
  "scenes": [
    {
      "id": "scene-{$startScene}",
      "title": "Descriptive scene title",
      "narration": "Full narrator script for this scene (match {$sceneDuration}s duration)",
      "visualDescription": "Detailed visual description for AI image generation",
      "mood": "Scene emotional tone",
      "transition": "cut|fade|dissolve"
    }
    // ... exactly {$sceneCount} scenes
  ]
}
PROMPT;
}
```

---

## UI Components

### 1. Script Step - Progressive Generation Panel

**File:** `modules/AppVideoWizard/resources/views/livewire/steps/script.blade.php`

```blade
{{-- Progressive Generation Panel --}}
@if($scriptGeneration['status'] !== 'idle')
<div class="progressive-generation-panel" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">

    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 style="color: white; font-size: 1.1rem; font-weight: 600; margin: 0;">
                🎬 Script Generation
            </h3>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0.25rem 0 0 0;">
                {{ $targetDuration }}s video → {{ $scriptGeneration['targetSceneCount'] }} scenes
            </p>
        </div>
        <div style="text-align: right;">
            <span style="color: #8b5cf6; font-size: 1.5rem; font-weight: 700;">
                {{ $scriptGeneration['generatedSceneCount'] }} / {{ $scriptGeneration['targetSceneCount'] }}
            </span>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin: 0;">scenes generated</p>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div style="background: rgba(255,255,255,0.1); border-radius: 0.5rem; height: 12px; overflow: hidden; margin-bottom: 1rem;">
        @php
            $progress = $scriptGeneration['targetSceneCount'] > 0
                ? ($scriptGeneration['generatedSceneCount'] / $scriptGeneration['targetSceneCount']) * 100
                : 0;
        @endphp
        <div style="background: linear-gradient(90deg, #8b5cf6, #06b6d4); height: 100%; width: {{ $progress }}%; transition: width 0.5s ease;"></div>
    </div>

    {{-- Batch List --}}
    <div style="display: grid; gap: 0.5rem; margin-bottom: 1rem;">
        @foreach($scriptGeneration['batches'] as $batch)
            @php
                $statusColors = [
                    'complete' => ['bg' => 'rgba(16, 185, 129, 0.2)', 'border' => 'rgba(16, 185, 129, 0.4)', 'icon' => '✅'],
                    'generating' => ['bg' => 'rgba(251, 191, 36, 0.2)', 'border' => 'rgba(251, 191, 36, 0.4)', 'icon' => '⏳'],
                    'pending' => ['bg' => 'rgba(255, 255, 255, 0.05)', 'border' => 'rgba(255, 255, 255, 0.1)', 'icon' => '⏸️'],
                    'error' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'border' => 'rgba(239, 68, 68, 0.4)', 'icon' => '❌'],
                ];
                $style = $statusColors[$batch['status']] ?? $statusColors['pending'];
            @endphp
            <div style="background: {{ $style['bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 0.5rem; padding: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.1rem;">{{ $style['icon'] }}</span>
                <div style="flex: 1;">
                    <span style="color: white; font-weight: 500;">Batch {{ $batch['batchNumber'] }}</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-left: 0.5rem;">
                        Scenes {{ $batch['startScene'] }}-{{ $batch['endScene'] }}
                    </span>
                </div>
                <span style="color: rgba(255,255,255,0.6); font-size: 0.8rem; text-transform: capitalize;">
                    {{ $batch['status'] }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Action Buttons --}}
    <div style="display: flex; gap: 1rem;">
        @if($scriptGeneration['status'] === 'paused' || $scriptGeneration['status'] === 'generating')
            <button
                wire:click="generateNextBatch"
                wire:loading.attr="disabled"
                wire:target="generateNextBatch"
                @if($scriptGeneration['status'] === 'generating') disabled @endif
                style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
            >
                <span wire:loading.remove wire:target="generateNextBatch">🚀 Generate Next Batch</span>
                <span wire:loading wire:target="generateNextBatch">⏳ Generating...</span>
            </button>

            <button
                wire:click="generateAllRemaining"
                wire:loading.attr="disabled"
                @if($scriptGeneration['status'] === 'generating') disabled @endif
                style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #06b6d4, #0891b2); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
            >
                ⚡ Auto-Generate All
            </button>
        @endif

        @if($scriptGeneration['status'] === 'complete')
            <div style="flex: 1; padding: 0.75rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.5rem; text-align: center;">
                <span style="color: #10b981; font-weight: 600;">✅ All {{ $scriptGeneration['targetSceneCount'] }} scenes generated!</span>
            </div>
        @endif
    </div>
</div>
@endif
```

### 2. Initial Generation Button

```blade
{{-- Start Progressive Generation --}}
@if($scriptGeneration['status'] === 'idle' && !empty($concept))
<div style="text-align: center; padding: 2rem;">
    <p style="color: rgba(255,255,255,0.7); margin-bottom: 1rem;">
        Ready to generate {{ $this->calculateSceneCount() }} scenes for your {{ $targetDuration }}s video
    </p>
    <button
        wire:click="startProgressiveGeneration"
        style="padding: 1rem 2rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.75rem; color: white; font-size: 1.1rem; font-weight: 700; cursor: pointer;"
    >
        🎬 Start Script Generation
    </button>
</div>
@endif
```

---

## Backend Methods

### 1. Start Progressive Generation

```php
// In VideoWizard.php
public function startProgressiveGeneration(): void
{
    $targetDuration = $this->targetDuration ?? 60;
    $productionType = $this->production['type'] ?? 'standard';

    $scriptService = app(ScriptGenerationService::class);
    $targetSceneCount = $scriptService->calculateExactSceneCount($targetDuration, $productionType);

    $batchSize = 5;
    $totalBatches = (int) ceil($targetSceneCount / $batchSize);

    // Initialize batch tracking
    $batches = [];
    for ($i = 0; $i < $totalBatches; $i++) {
        $startScene = ($i * $batchSize) + 1;
        $endScene = min(($i + 1) * $batchSize, $targetSceneCount);

        $batches[] = [
            'batchNumber' => $i + 1,
            'startScene' => $startScene,
            'endScene' => $endScene,
            'status' => 'pending',
            'generatedAt' => null,
            'sceneIds' => [],
        ];
    }

    $this->scriptGeneration = [
        'status' => 'paused',
        'targetSceneCount' => $targetSceneCount,
        'generatedSceneCount' => 0,
        'batchSize' => $batchSize,
        'currentBatch' => 0,
        'totalBatches' => $totalBatches,
        'batches' => $batches,
        'autoGenerate' => false,
    ];

    // Initialize empty script
    $this->script = [
        'title' => $this->concept['refinedConcept'] ?? 'Untitled',
        'scenes' => [],
        'status' => 'generating',
    ];

    $this->saveProject();

    // Start first batch
    $this->generateNextBatch();
}
```

### 2. Generate Next Batch

```php
public function generateNextBatch(): void
{
    $currentBatchIndex = $this->scriptGeneration['currentBatch'];

    if ($currentBatchIndex >= $this->scriptGeneration['totalBatches']) {
        $this->scriptGeneration['status'] = 'complete';
        return;
    }

    $batch = &$this->scriptGeneration['batches'][$currentBatchIndex];
    $batch['status'] = 'generating';
    $this->scriptGeneration['status'] = 'generating';

    try {
        $scriptService = app(ScriptGenerationService::class);

        // Build context from existing scenes
        $context = $scriptService->buildBatchContext(
            $this->script['scenes'] ?? [],
            $batch['batchNumber'],
            $this->scriptGeneration['totalBatches']
        );

        // Generate this batch
        $result = $scriptService->generateSceneBatch(
            $this->projectId,
            $batch['startScene'],
            $batch['endScene'],
            $this->scriptGeneration['targetSceneCount'],
            $context,
            [
                'topic' => $this->concept['refinedConcept'] ?? '',
                'tone' => $this->content['tone'] ?? 'engaging',
                'productionType' => $this->production['type'] ?? 'standard',
            ]
        );

        if ($result['success'] && !empty($result['scenes'])) {
            // Append new scenes
            $this->script['scenes'] = array_merge(
                $this->script['scenes'] ?? [],
                $result['scenes']
            );

            // Update batch status
            $batch['status'] = 'complete';
            $batch['generatedAt'] = now()->toDateTimeString();
            $batch['sceneIds'] = array_column($result['scenes'], 'id');

            // Update counts
            $this->scriptGeneration['generatedSceneCount'] = count($this->script['scenes']);
            $this->scriptGeneration['currentBatch']++;

            // Check if complete
            if ($this->scriptGeneration['currentBatch'] >= $this->scriptGeneration['totalBatches']) {
                $this->scriptGeneration['status'] = 'complete';
                $this->script['status'] = 'ready';
            } else {
                $this->scriptGeneration['status'] = 'paused';

                // Auto-continue if enabled
                if ($this->scriptGeneration['autoGenerate']) {
                    $this->generateNextBatch();
                }
            }
        } else {
            $batch['status'] = 'error';
            $this->scriptGeneration['status'] = 'paused';
            $this->error = $result['error'] ?? 'Failed to generate batch';
        }

    } catch (\Exception $e) {
        $batch['status'] = 'error';
        $this->scriptGeneration['status'] = 'paused';
        $this->error = 'Batch generation failed: ' . $e->getMessage();
    }

    $this->saveProject();
}
```

### 3. Generate All Remaining

```php
public function generateAllRemaining(): void
{
    $this->scriptGeneration['autoGenerate'] = true;
    $this->generateNextBatch();
}
```

### 4. Scene Batch Generation in Service

```php
// In ScriptGenerationService.php
public function generateSceneBatch(
    int $projectId,
    int $startScene,
    int $endScene,
    int $totalScenes,
    string $context,
    array $options = []
): array {
    $project = WizardProject::find($projectId);
    if (!$project) {
        return ['success' => false, 'error' => 'Project not found'];
    }

    $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
    $topic = $options['topic'] ?? $project->concept['refinedConcept'] ?? '';

    $prompt = $this->buildBatchPrompt(
        $topic,
        (int) ceil($startScene / 5),
        $startScene,
        $endScene,
        $totalScenes,
        $context,
        $options
    );

    $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

    if (!empty($result['error'])) {
        return ['success' => false, 'error' => $result['error']];
    }

    $response = $result['data'][0] ?? '';

    try {
        $parsed = $this->parseBatchResponse($response, $startScene, $endScene);
        return ['success' => true, 'scenes' => $parsed['scenes']];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

public function buildBatchContext(array $existingScenes, int $batchNumber, int $totalBatches): string
{
    $context = "";

    if (empty($existingScenes)) {
        $context .= "This is the OPENING of the video.\n";
        $context .= "- Establish a strong hook in the first scene\n";
        $context .= "- Introduce the main topic/premise\n";
        $context .= "- Set the tone and style for the entire video\n";
        return $context;
    }

    // Summary of narrative so far
    $context .= "=== STORY SO FAR ===\n";
    $context .= "Generated " . count($existingScenes) . " scenes.\n\n";

    // Last 3 scenes for direct continuity
    $context .= "RECENT SCENES (for continuity):\n";
    $recentScenes = array_slice($existingScenes, -3);
    foreach ($recentScenes as $scene) {
        $context .= "• {$scene['title']}: " . substr($scene['narration'] ?? '', 0, 100) . "...\n";
    }
    $context .= "\n";

    // Narrative position guidance
    $position = $batchNumber / $totalBatches;
    $context .= "=== NARRATIVE POSITION ===\n";

    if ($position < 0.25) {
        $context .= "You are in the SETUP phase (first quarter).\n";
        $context .= "- Continue building the foundation\n";
        $context .= "- Introduce key concepts/characters\n";
        $context .= "- Maintain viewer engagement with interesting hooks\n";
    } elseif ($position < 0.5) {
        $context .= "You are in the DEVELOPMENT phase (second quarter).\n";
        $context .= "- Deepen the narrative\n";
        $context .= "- Add complexity and detail\n";
        $context .= "- Build towards the midpoint\n";
    } elseif ($position < 0.75) {
        $context .= "You are in the ESCALATION phase (third quarter).\n";
        $context .= "- Increase intensity/importance\n";
        $context .= "- Present key revelations or turning points\n";
        $context .= "- Build towards the climax\n";
    } else {
        $context .= "You are in the RESOLUTION phase (final quarter).\n";
        $context .= "- Bring the narrative to a satisfying conclusion\n";
        $context .= "- Deliver the main message/takeaway\n";
        $context .= "- Include a strong call-to-action in the final scene\n";
    }

    return $context;
}
```

---

## Files to Modify

### Phase 1: Immediate Fixes
| File | Change |
|------|--------|
| `VideoWizard.php` | Fix portrait sceneIndex (pass `null`) |

### Phase 2: Progressive Generation System
| File | Change |
|------|--------|
| `VideoWizard.php` | Add `$scriptGeneration` state, batch methods |
| `ScriptGenerationService.php` | Add `generateSceneBatch()`, `buildBatchContext()`, `calculateExactSceneCount()` |
| `script.blade.php` | Add progressive generation UI panel |

### Phase 3: Remove Old Code
| File | Change |
|------|--------|
| `ScriptGenerationService.php` | Remove `interpolateScenes()` and transition scene methods |

---

## Benefits of This Architecture

| Aspect | Before | After |
|--------|--------|-------|
| Scene Count | Approximate, often wrong | Exact, based on duration |
| Quality | Degrades with more scenes | Consistent (5 at a time) |
| Fake Content | "Transition" placeholders | Never - all real content |
| User Control | All-or-nothing | Batch by batch |
| Visibility | Black box | Clear progress tracking |
| Reliability | Often fails on long videos | Robust batching |
| Narrative Flow | Can be disjointed | Context-aware continuity |

---

## Testing Checklist

- [ ] Scene count exactly matches duration calculation
- [ ] Each batch generates exactly 5 scenes (or remaining)
- [ ] Context from previous scenes is passed correctly
- [ ] Narrative arc guidance changes based on position
- [ ] Progress UI shows correct batch status
- [ ] "Generate Next Batch" works correctly
- [ ] "Auto-Generate All" continues until complete
- [ ] No "Transition" placeholder scenes ever created
- [ ] Character portrait generation works without TypeError
- [ ] Long videos (180s+) generate all scenes successfully
