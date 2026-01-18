# Video Wizard Hollywood-Level Upgrade Plan
## Character & Location Consistency System Overhaul

---

## Executive Summary

This plan addresses critical gaps in the Video Wizard's visual consistency system to achieve **Hollywood-level production quality**. The current infrastructure exists but is underutilized. This plan implements a **smart scene-to-scene reference system** where Scene 1 establishes the visual foundation, and subsequent scenes automatically reference it for character and location consistency.

---

## Current State Analysis

### What Works
- Bible infrastructure exists (Character, Location, Style, Scene DNA)
- Image generation pipeline can accept reference images
- Gemini API supports up to 5 character references and 14 total reference images
- Scene DNA is designed to unify all Bible data per scene

### Critical Gaps Identified

| Issue | Impact | Root Cause |
|-------|--------|------------|
| `charactersInScene` always empty | Scenes don't know which characters to use | Auto-detection not linking to scenes |
| `locationRef` always null | No location consistency | Location Bible not connected to scenes |
| Reference images rarely generated | No visual anchor for consistency | User must manually generate |
| Character Look System fields empty | Hair, wardrobe, makeup not defined | Auto-detection doesn't extract details |
| Scene 0 vs Scene 1 indexing inconsistent | Data misalignment | Mixed 0-based and 1-based indexing |
| Scene DNA scenes often null | Unified data not built | `buildSceneDNA()` not triggered properly |

---

## Hollywood-Level Vision

### The "Reference Cascade" System

```
SCENE 1 (Foundation Scene)
├── Generate "Hero Frame" → Establishes visual baseline
├── Extract character portraits → Face geometry locked
├── Extract location context → Environment anchored
├── Lock style reference → Lighting/color palette set
│
SCENE 2 (Inherits from Scene 1)
├── Character Reference: Scene 1 portrait images
├── Location Reference: Scene 1 environment (if same location)
├── Style Reference: Scene 1 visual style
├── Action Variation: Different pose/activity
│
SCENE 3+ (Chain continues)
└── Each scene references BOTH:
    ├── Original hero images (identity anchor)
    └── Previous scene (continuity anchor)
```

---

## Implementation Plan

### Phase 1: Foundation Fixes (Critical)
**Goal: Fix data integrity and scene-Bible linkage**

#### 1.1 Fix Scene Indexing Consistency
- Standardize on 1-based indexing throughout
- Update `autoDetectCharacters()` to use correct scene numbers
- Fix `buildSceneDNA()` scene key generation

#### 1.2 Auto-Link Characters to Scenes
- After script generation, parse `visualDescription` for character mentions
- Match character names to Character Bible entries
- Populate `charactersInScene` array automatically
- Store character roles per scene (protagonist, supporting, background)

#### 1.3 Auto-Link Locations to Scenes
- Parse scene `visualDescription` for location keywords
- Match to Location Bible entries
- Populate `locationRef` with location ID
- Handle location transitions intelligently

#### 1.4 Trigger Scene DNA Rebuild
- Call `buildSceneDNA()` after every Bible modification
- Validate Scene DNA has data for all scenes
- Add data integrity checks before image generation

---

### Phase 2: Smart Reference Generation (High Priority)
**Goal: Automatically generate reference images as foundation**

#### 2.1 "Hero Frame" Auto-Generation
When user clicks "Generate All Images":
1. **First Pass**: Generate Scene 1 only
2. **Extract & Store**:
   - Crop/extract character faces from Scene 1
   - Store as `referenceImageBase64` in Character Bible
   - Mark `referenceImageStatus: 'ready'`
3. **Second Pass**: Generate remaining scenes using references

#### 2.2 Character Portrait Auto-Extraction
```
Scene 1 Generated Image
    ↓
Face Detection API (Google Vision / local)
    ↓
For each detected face:
    ├── Match to character in scene
    ├── Crop portrait (head + shoulders)
    ├── Store in Character Bible
    └── Mark as reference image
```

#### 2.3 Location Reference Auto-Capture
- Use first scene at each location as reference
- Store full frame as location reference
- Extract environment details (lighting, color palette)

---

### Phase 3: Reference Cascade Implementation (Core Feature)
**Goal: Scene-to-scene consistency pipeline**

#### 3.1 Reference Priority System
```php
function getReferencesForScene($sceneIndex) {
    $references = [];

    // 1. Character References (from Character Bible)
    foreach ($scene->charactersInScene as $charId) {
        $char = $this->getCharacterFromBible($charId);
        if ($char->hasReferenceImage()) {
            $references['characters'][] = $char->referenceImageBase64;
        }
    }

    // 2. Location Reference (from Location Bible or previous scene)
    $location = $this->getLocationForScene($sceneIndex);
    if ($location->hasReferenceImage()) {
        $references['location'] = $location->referenceImageBase64;
    } else if ($previousSceneAtLocation = $this->findPreviousSceneAtLocation($location)) {
        $references['location'] = $previousSceneAtLocation->imageUrl;
    }

    // 3. Style Reference (global)
    if ($this->styleBible->hasReferenceImage()) {
        $references['style'] = $this->styleBible->referenceImageBase64;
    }

    // 4. Continuity Reference (previous scene for smooth transitions)
    if ($sceneIndex > 0) {
        $references['continuity'] = $this->scenes[$sceneIndex - 1]->imageUrl;
    }

    return $references;
}
```

#### 3.2 Enhanced Prompt Building with References
Modify `StructuredPromptBuilderService.php`:

```php
function buildPromptWithReferences($scene, $references) {
    $prompt = "";

    // Identity Anchor (repeated in every prompt)
    if ($references['characters']) {
        $prompt .= "CRITICAL: Maintain EXACT appearance of reference character(s). ";
        $prompt .= "Same face shape, same eyes, same hair style and color. ";
    }

    // Scene-specific action
    $prompt .= $scene->visualDescription;

    // Location consistency
    if ($references['location']) {
        $prompt .= " Environment matches reference: same architecture, same lighting direction, same color palette.";
    }

    return $prompt;
}
```

#### 3.3 Gemini API Integration Enhancement
Leverage Gemini 3 Pro's capabilities:
- Use up to 5 character reference images per generation
- Pass location reference as 6th image
- Include style reference for visual consistency
- Request same aspect ratio and resolution as references

---

### Phase 4: Character Look System Enhancement (Quality)
**Goal: Rich character detail for prompt building**

#### 4.1 Auto-Extract Character Details from Description
Use AI to parse `visualDescription` and populate:
- **Hair**: color, style, length, texture
- **Wardrobe**: outfit type, colors, style, footwear
- **Makeup**: style, details
- **Accessories**: list of items
- **Physical**: age range, build, distinctive features

#### 4.2 Character DNA Template System
Pre-built templates for common archetypes:
- Action Hero (athletic build, practical clothing)
- Tech Professional (modern attire, glasses optional)
- Mysterious Figure (dark colors, hood/hat)
- Narrator (professional, neutral appearance)

#### 4.3 Wardrobe Continuity Tracking
- Track what each character wears per scene
- Flag wardrobe changes (intentional vs. error)
- Allow planned wardrobe evolution

---

### Phase 5: Intelligent Ordering & UI (Polish)
**Goal: Professional-grade user experience**

#### 5.1 Character Bible Smart Ordering
Priority order:
1. Protagonist(s) - most scenes
2. Supporting characters - recurring
3. Background characters - single scene
4. Crowd/extras - generic

Display: Show scene count, thumbnail, status badge

#### 5.2 Location Bible Smart Ordering
Priority order:
1. By first appearance (story order)
2. By frequency (most used first)
3. Alphabetical (fallback)

Display: Show scene list, interior/exterior, time of day

#### 5.3 Visual Consistency Dashboard
New UI panel showing:
- Reference image status per character
- Consistency score (0-100%)
- Potential issues flagged
- One-click "Generate All References"

---

### Phase 6: Advanced Features (Future)
**Goal: Industry-leading capabilities**

#### 6.1 LoRA Training Integration
- Option to train custom LoRA on character references
- Deeper identity preservation for long projects
- Integration with Stable Diffusion backends

#### 6.2 Face Correction Post-Processing
- Detect face inconsistencies across scenes
- Auto-correct minor variations
- Manual override option

#### 6.3 Scene Memory Export/Import
- Export Bible data as JSON
- Import for sequel/spin-off projects
- Share character packs between projects

---

## Implementation Priority Matrix

| Phase | Priority | Effort | Impact | Dependencies |
|-------|----------|--------|--------|--------------|
| Phase 1 | CRITICAL | Medium | High | None |
| Phase 2 | HIGH | High | Very High | Phase 1 |
| Phase 3 | HIGH | High | Very High | Phase 1, 2 |
| Phase 4 | MEDIUM | Medium | Medium | Phase 1 |
| Phase 5 | MEDIUM | Low | Medium | Phase 1, 4 |
| Phase 6 | LOW | High | High | Phase 1-5 |

---

## Technical Implementation Details

### Files to Modify

#### Phase 1 Files
- `VideoWizard.php` - Fix indexing, add scene-Bible linking
- `ScriptService.php` - Add character/location extraction
- `buildSceneDNA()` - Fix scene key generation

#### Phase 2 Files
- `ImageGenerationService.php` - Add hero frame logic
- `VideoWizard.php` - Add auto-extraction after generation
- New: `CharacterExtractionService.php` - Face detection/cropping

#### Phase 3 Files
- `ImageGenerationService.php` - Reference cascade logic
- `StructuredPromptBuilderService.php` - Enhanced prompt building
- `GeminiService.php` - Multi-reference API calls

---

## Success Metrics

### Technical KPIs
- [ ] 100% of scenes have `charactersInScene` populated
- [ ] 100% of scenes have `locationRef` populated
- [ ] 90%+ characters have reference images after Scene 1
- [ ] Scene DNA rebuilt on every Bible change

### Quality KPIs
- [ ] Character face consistency score > 85%
- [ ] Location consistency score > 90%
- [ ] Zero "orphan" characters (in Bible but no scenes)
- [ ] Zero "unknown" characters (in scene but not in Bible)

### User Experience KPIs
- [ ] One-click reference generation
- [ ] Clear consistency status indicators
- [ ] < 3 seconds for Bible modal load
- [ ] Intuitive character/location ordering

---

## Research Sources

### Character Consistency Best Practices
- [Skywork AI - Consistent Characters Prompt Patterns 2025](https://skywork.ai/blog/how-to-consistent-characters-ai-scenes-prompt-patterns-2025/)
- [Artlist - Consistent Character AI Pro Tips](https://artlist.io/blog/consistent-character-ai/)
- [Medium - Design Consistent AI Characters 2025](https://medium.com/design-bootcamp/how-to-design-consistent-ai-characters-with-prompts-diffusion-reference-control-2025-a1bf1757655d)

### Gemini API Capabilities
- [Google AI - Nano Banana Image Generation](https://ai.google.dev/gemini-api/docs/image-generation)
- [Google Developers - Gemini 3 Pro Image](https://blog.google/technology/developers/gemini-3-pro-image-developers/)
- [Google Codelabs - Consistent Imagery with Gemini](https://codelabs.developers.google.com/gemini-consistent-imagery-notebook)

### Professional Workflow References
- [LTX Studio - AI Video Production](https://ltx.studio/)
- [Higgsfield - Cinema Studio](https://higgsfield.ai/cinematic-video-generator)
- [Katalist - Storyboard AI](https://www.katalist.ai/)

---

## Conclusion

This plan transforms the Video Wizard from a basic AI image generator into a **Hollywood-grade visual consistency system**. By implementing the "Reference Cascade" approach, every scene automatically inherits visual identity from its predecessors, ensuring characters look identical across all shots while performing different actions.

The key innovation is making Scene 1 the "foundation scene" that establishes all visual references automatically, eliminating the need for manual reference generation while achieving professional-level consistency.

**Estimated Total Implementation Time**: 4-6 weeks for Phases 1-3 (core functionality)

---

*Plan created: January 19, 2026*
*Author: Claude Code (Opus 4.5)*
*Project: Artime.ai Video Wizard*
