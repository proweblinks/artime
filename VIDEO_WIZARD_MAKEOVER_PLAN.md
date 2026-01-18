# Video Wizard Steps 1-4: Complete Makeover Plan

## Executive Summary

The first 4 steps of the Video Wizard (Platform, Concept, Script, Storyboard) have accumulated technical debt and recurring issues due to incremental fixes. A comprehensive makeover is needed to fix all holes, establish proper data flow, and ensure reliable operation.

---

## Current Architecture Overview

### Step Flow
```
Step 1: Platform → Step 2: Concept → Step 3: Script → Step 4: Storyboard
   ↓                    ↓                 ↓                  ↓
 format              concept         script.scenes      storyboard.scenes
 productionType      characterIntel   storyBible         sceneMemory
 content.visualMode  conceptVars      scriptGeneration   multiShotMode
```

### Key Files
- **Main Component**: `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (19,303 lines)
- **Step Views**: `modules/AppVideoWizard/resources/views/livewire/steps/*.blade.php`
- **Modals**: `modules/AppVideoWizard/resources/views/livewire/modals/*.blade.php`
- **Services**: 50+ services in `modules/AppVideoWizard/app/Services/`

---

## CRITICAL ISSUES TO FIX

### 1. Location Bible Issues (HIGH PRIORITY)

#### Problem 1.1: Scene Assignment Confusion
**Current State**: The Location Bible has confusing "No scenes assigned - this location applies to ALL scenes by default" logic.
**Issue**: When no scenes are assigned, the UI implies the location applies to all scenes, but this isn't how the image generation actually uses it.
**Fix Required**:
- Remove the "applies to ALL scenes by default" logic
- Make scene assignment explicit and required
- When auto-detecting locations, always assign detected scenes
- Add validation to prevent locations with 0 scenes

**Files to modify**:
- `location-bible.blade.php` lines 275-279
- `VideoWizard.php` - `syncStoryBibleToLocationBible()`, `autoDetectLocationsFromScript()`

#### Problem 1.2: Story Bible Sync Not Working Properly
**Current State**: The "Sync from Story Bible" button exists but scene auto-detection during sync is unreliable.
**Issue**: The sync uses text matching to detect scenes, but the matching algorithm is too strict (requires 6+ character words).
**Fix Required**:
- Improve the scene detection algorithm to use:
  - Direct location name mentions
  - Setting/environment keywords from Story Bible
  - Scene `location` field if available
- Add manual scene assignment fallback when auto-detection fails
- Show warning when a synced location has 0 detected scenes

**Files to modify**:
- `VideoWizard.php` lines 10273-10350 (`syncStoryBibleToLocationBible`)

#### Problem 1.3: Reference Image Generation Not Using Location Data
**Current State**: Generate Reference button exists but doesn't properly incorporate all location attributes.
**Issue**: The prompt for image generation may not include mood, lightingStyle, weather properly.
**Fix Required**:
- Audit `generateLocationReference()` to ensure all fields are used:
  - `type` (interior/exterior/abstract)
  - `timeOfDay`
  - `weather`
  - `mood`
  - `lightingStyle`
  - `description`

---

### 2. Character Bible Issues (HIGH PRIORITY)

#### Problem 2.1: Scene Assignment Inconsistency
**Current State**: Character uses `appliedScenes` while Location uses `scenes` array.
**Issue**: Inconsistent naming makes code harder to maintain.
**Fix Required**:
- Standardize to use `scenes` array for both Character and Location
- Update all references in blade templates and PHP methods

**Files to modify**:
- `character-bible.blade.php`
- `VideoWizard.php` - all character-related methods

#### Problem 2.2: Character DNA Not Flowing to Image Generation
**Current State**: Detailed Character DNA (hair, wardrobe, makeup, accessories) is collected but may not be included in prompts.
**Issue**: Character consistency in generated images is poor.
**Fix Required**:
- Audit the prompt building pipeline to ensure Character DNA is included
- Create a `buildCharacterPromptSegment()` method that compiles all character attributes

---

### 3. Scene DNA / Story Bible Synchronization Issues (HIGH PRIORITY)

#### Problem 3.1: Field Name Mismatches
**Current State**: Code has dual support for both `sceneIndex`/`stateDescription` and `scene`/`state` field names.
**Issue**: Legacy field names cause confusion and potential bugs.
**Fix Required**:
- Migrate all data to use consistent field names
- Remove legacy field name support
- Add data migration for existing projects

**Files to modify**:
- `VideoWizard.php` - state change handling
- `location-bible.blade.php` lines 331-336

#### Problem 3.2: Scene DNA Continuity Issues Not Displaying
**Current State**: `sceneMemory.sceneDNA.continuityIssues` array exists but detection is incomplete.
**Issue**: Users aren't warned about visual inconsistencies between scenes.
**Fix Required**:
- Implement proper continuity validation:
  - Character appearance changes across scenes
  - Location state inconsistencies
  - Time of day mismatches
- Display warnings in the Scene DNA panel

---

### 4. Script Generation Issues (MEDIUM PRIORITY)

#### Problem 4.1: Story Bible Generation Dependency
**Current State**: Story Bible must be generated before script, but no hard enforcement.
**Issue**: Users can skip Story Bible and get inconsistent scripts.
**Fix Required**:
- Add hard gate: cannot proceed to script generation without Story Bible
- Auto-generate Story Bible when entering Step 3 if not present
- Show clear status indicator for Story Bible state

#### Problem 4.2: Scene Visual Prompt Quality
**Current State**: Each scene has `visualDescription` and `visualPrompt` fields.
**Issue**: The distinction between these fields is unclear and may cause confusion in downstream processing.
**Fix Required**:
- Document the purpose of each field
- `visualDescription`: Human-readable scene description
- `visualPrompt`: Optimized prompt for image generation
- Ensure script generation populates both correctly

---

### 5. Storyboard Stage Issues (MEDIUM PRIORITY)

#### Problem 5.1: Multi-Shot Mode Complexity
**Current State**: Multi-shot mode decomposes scenes into 3-10 shots.
**Issue**: The decomposition status tracking is fragmented across multiple arrays.
**Fix Required**:
- Consolidate multi-shot state into a single coherent structure
- Clear status progression: pending → decomposing → ready
- Handle edge cases (0 shots, failed decomposition)

#### Problem 5.2: Visual Style Not Persisting
**Current State**: Visual style dropdowns (mood, lighting, color palette, composition) in storyboard.
**Issue**: Style selections may not persist when navigating between steps.
**Fix Required**:
- Ensure `storyboard.visualStyle` is saved on every change
- Load saved styles when returning to Step 4

#### Problem 5.3: Prompt Chain Processing Unclear
**Current State**: Prompt Chain section shows "Ready/Processing/Not Processed" status.
**Issue**: Users don't understand what Prompt Chain does or when to use it.
**Fix Required**:
- Add tooltip/help text explaining Prompt Chain
- Auto-process prompt chain when entering Step 4 with valid script
- Show clear before/after comparison of prompts

---

### 6. Data Flow & Pipeline Issues (HIGH PRIORITY)

#### Problem 6.1: State Synchronization Between Steps
**Current State**: Each step reads from previous step's data, but sync points are unclear.
**Issue**: Changes in Step 2 don't always propagate to Step 3 properly.
**Fix Required**:
- Implement explicit sync points:
  - `syncConceptToScript()` - when entering Step 3
  - `syncScriptToStoryboard()` - when entering Step 4
  - `syncBiblesToSceneMemory()` - when Bible changes
- Add sync status indicators

#### Problem 6.2: Bible-First Architecture Not Enforced
**Current State**: Story Bible is supposed to be generated before script (Bible-First).
**Issue**: The flow allows skipping Bible generation.
**Fix Required**:
- Gate progression: Step 3 requires Story Bible
- Auto-trigger Story Bible generation when entering Step 3
- Character/Location Bibles should auto-populate from Story Bible

---

### 7. Performance Issues (MEDIUM PRIORITY)

#### Problem 7.1: Large Component File
**Current State**: VideoWizard.php is 19,303 lines.
**Issue**: Difficult to maintain, slow IDE performance.
**Fix Required** (Phase 2):
- Extract step-specific logic into traits or child Livewire components
- Extract Bible management into separate components
- Create dedicated services for complex operations

#### Problem 7.2: Excessive Re-rendering
**Current State**: Many `wire:model.live` bindings cause frequent updates.
**Issue**: UI feels sluggish during typing.
**Fix Required**:
- Use `wire:model.blur` for text inputs
- Use `wire:model.debounce.500ms` for search/filter inputs
- Batch updates where possible (already partially done with `$isBatchUpdating`)

---

## IMPLEMENTATION PLAN

### Phase 1: Data Model Cleanup (Required First)

1. **Standardize field names**
   - Location: `scenes` (keep as is)
   - Character: Change `appliedScenes` to `scenes`
   - State changes: Migrate to `sceneIndex`/`stateDescription`

2. **Add data validation**
   - Location must have at least 1 scene
   - Character must have name and description
   - Story Bible required for script generation

3. **Fix sync methods**
   - `syncStoryBibleToLocationBible()` - improve scene detection
   - `syncStoryBibleToCharacterBible()` - ensure character attributes flow

### Phase 2: UI/UX Fixes

1. **Location Bible Modal**
   - Remove "applies to ALL scenes" confusing message
   - Add "Select scenes" requirement indicator
   - Improve reference image generation with full attribute inclusion

2. **Character Bible Modal**
   - Standardize to use `scenes` instead of `appliedScenes`
   - Ensure Character DNA flows to prompt generation

3. **Script Stage**
   - Add Story Bible gate
   - Clear status indicators for Bible state

4. **Storyboard Stage**
   - Simplify multi-shot status tracking
   - Auto-process prompt chain
   - Persist visual style settings

### Phase 3: Pipeline Enforcement

1. **Add sync points between steps**
   - Explicit method calls when navigating forward
   - Validation before allowing progression

2. **Add continuity validation**
   - Detect character appearance changes
   - Detect location state inconsistencies
   - Display warnings in Scene DNA panel

### Phase 4: Testing & Validation

1. **Create end-to-end test scenarios**
   - Full wizard flow from Platform to Storyboard
   - Bible sync verification
   - Scene assignment verification

2. **Add logging for debugging**
   - Log sync operations
   - Log prompt building steps
   - Log validation failures

---

## SPECIFIC CODE CHANGES NEEDED

### VideoWizard.php Changes

```php
// 1. Standardize Character scene field
// Change all 'appliedScenes' references to 'scenes'

// 2. Add Bible gate in goToStep()
public function goToStep(int $step): void
{
    // Add this validation for Step 3
    if ($step === 3 && $this->storyBible['status'] !== 'ready') {
        // Auto-generate Story Bible or show error
        $this->generateStoryBible();
    }

    // Add sync points
    if ($step === 3) {
        $this->syncConceptToScript();
    }
    if ($step === 4) {
        $this->syncScriptToStoryboard();
        $this->syncBiblesToSceneMemory();
    }

    // ... existing logic
}

// 3. Improve location scene detection in sync
protected function syncStoryBibleToLocationBible(): void
{
    // Improve scene matching:
    // - Use location name
    // - Use location keywords (from Story Bible)
    // - Use scene's explicit location field if set
    // - Fall back to visual description matching
}

// 4. Add continuity validation
public function validateSceneContinuity(): array
{
    $issues = [];
    // Check character appearances across scenes
    // Check location states
    // Check time of day consistency
    return $issues;
}
```

### Blade Template Changes

```blade
{{-- location-bible.blade.php: Remove confusing "applies to ALL" message --}}
{{-- Line 275-279: Change to: --}}
@if($assignedScenesCount === 0 && $totalScenes > 0)
    <div style="...background: rgba(239,68,68,0.1)...">
        <span style="color: #fca5a5;">⚠️ {{ __('Please assign at least one scene to this location') }}</span>
    </div>
@endif

{{-- character-bible.blade.php: Standardize field name --}}
{{-- Change 'appliedScenes' to 'scenes' throughout --}}
```

---

## SUCCESS CRITERIA

After the makeover, the following should work correctly:

1. **Location Bible**
   - Auto-detect locations from script with accurate scene assignments
   - Sync from Story Bible works and assigns relevant scenes
   - Reference image generation uses ALL location attributes
   - No location with 0 scenes allowed

2. **Character Bible**
   - Consistent field naming with Location Bible
   - Character DNA (hair, wardrobe, makeup, accessories) flows to image generation
   - Scene assignments work correctly

3. **Story Bible Integration**
   - Story Bible is required before script generation
   - Changes to Story Bible propagate to Character/Location Bibles
   - Scene DNA tracks continuity issues

4. **Pipeline Flow**
   - Clear progression: Platform → Concept → Script (with Bible) → Storyboard
   - Data syncs at each step transition
   - Visual styles persist correctly

5. **Performance**
   - No sluggish typing
   - Batch updates work correctly
   - No excessive re-rendering

---

## ESTIMATED EFFORT

- Phase 1 (Data Model): 4-6 hours
- Phase 2 (UI/UX): 4-6 hours
- Phase 3 (Pipeline): 4-6 hours
- Phase 4 (Testing): 2-4 hours

**Total: 14-22 hours of focused work**

---

## NOTES

This document serves as the comprehensive plan for fixing all issues in Video Wizard Steps 1-4. Each issue has been identified through code analysis and commit history review. The fixes should be implemented in the order specified to avoid cascading issues.
