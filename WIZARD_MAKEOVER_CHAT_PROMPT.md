# New Chat Prompt: Video Wizard Steps 1-4 Makeover

Copy and paste the following into a new conversation:

---

## Task: Complete Makeover of Video Wizard Steps 1-4

I need a comprehensive makeover of the Video Wizard's first 4 steps (Platform, Concept, Script, Storyboard). We've been doing incremental fixes but issues keep recurring. It's time for a proper overhaul.

### The Problem

The wizard has accumulated technical debt from incremental fixes. Key issues include:

1. **Location Bible**
   - "No scenes assigned - applies to ALL scenes by default" logic is confusing and wrong
   - Story Bible sync doesn't properly assign scenes (uses overly strict 6+ char matching)
   - Reference image generation doesn't use all location attributes (mood, lighting, weather)
   - Locations can have 0 scenes which breaks downstream processing

2. **Character Bible**
   - Uses `appliedScenes` while Location uses `scenes` (inconsistent naming)
   - Character DNA (hair, wardrobe, makeup, accessories) doesn't flow to image prompts
   - Scene assignments have similar issues to locations

3. **Story Bible / Scene DNA**
   - Field name mismatches (`sceneIndex`/`stateDescription` vs `scene`/`state`)
   - Continuity issues not being detected or displayed
   - Bible-First architecture not enforced (can skip Story Bible)

4. **Data Flow Pipeline**
   - No explicit sync points between steps
   - Changes in Step 2 don't propagate to Step 3 properly
   - Bibles don't auto-populate from Story Bible

### Key Files

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (19,303 lines - main component)
- `modules/AppVideoWizard/resources/views/livewire/steps/platform.blade.php`
- `modules/AppVideoWizard/resources/views/livewire/steps/concept.blade.php`
- `modules/AppVideoWizard/resources/views/livewire/steps/script.blade.php`
- `modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php`
- `modules/AppVideoWizard/resources/views/livewire/modals/location-bible.blade.php`
- `modules/AppVideoWizard/resources/views/livewire/modals/character-bible.blade.php`

### What I Need

1. **Fix Location Bible**:
   - Remove "applies to ALL scenes" confusing logic - require explicit scene assignment
   - Fix `syncStoryBibleToLocationBible()` scene detection algorithm
   - Ensure `generateLocationReference()` uses ALL location fields
   - Validate: no location can have 0 scenes

2. **Fix Character Bible**:
   - Change `appliedScenes` to `scenes` everywhere for consistency
   - Ensure Character DNA flows to prompt building
   - Apply same scene validation as locations

3. **Fix Data Model**:
   - Standardize state change fields to `sceneIndex`/`stateDescription`
   - Remove legacy field name support
   - Add proper data validation

4. **Fix Pipeline Flow**:
   - Add explicit sync methods: `syncConceptToScript()`, `syncScriptToStoryboard()`
   - Gate Step 3: require Story Bible before script generation
   - Auto-populate Character/Location Bibles from Story Bible

5. **Add Continuity Validation**:
   - Detect character appearance inconsistencies across scenes
   - Detect location state mismatches
   - Display warnings in Scene DNA panel

### Recent Commit History (for context)

```
af2e89a Fix Scene DNA configuration and realism settings for image generation
a87f692 Fix Scene DNA continuity issues display and field name mismatches
edca883 Phase 5: Monitoring & Async Processing for Video Wizard
308f534 Phase 4: Algorithm Improvements for Video Wizard
72da296 Phase 3: Bible System Synchronization for Video Wizard
d270f1a Phase 2: Save & Hash Optimization for Video Wizard
a6b0d24 Phase 1: Quick performance wins for Video Wizard
e6e9709 Fix Location Bible scene conflicts with ownership map
fbc838d Fix Location Bible conflicts and major performance improvements
0cb7ab9 Fix Location Bible: scene distribution and detection improvements
c9fb9ad Fix location scene distribution: prevent first location from getting "all scenes"
213e9a3 Fix Bible system: location selection, scene assignments, and continuity validation
```

As you can see, we've been fixing the same areas repeatedly. We need a comprehensive fix, not more patches.

### Approach

Please:
1. First, read the key files to understand current implementation
2. Create a todo list with all the fixes needed
3. Implement fixes systematically, starting with data model standardization
4. Test by tracing the data flow from Step 1 through Step 4
5. Commit with descriptive message when complete

I've created `VIDEO_WIZARD_MAKEOVER_PLAN.md` in the repo root with a detailed plan. Please read it first.

---

End of prompt.
