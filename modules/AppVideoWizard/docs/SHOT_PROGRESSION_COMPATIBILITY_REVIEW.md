# Shot Progression & Narrative Development System
## Compatibility Review & Integration Analysis

**Document Version:** 1.0
**Date:** January 2026
**Branch:** `claude/review-upgrade-compatibility-odLhP`

---

## Executive Summary

This document provides a thorough compatibility analysis between the proposed **Video Shot Progression & Narrative Development System** and the existing ArTime Video Wizard architecture. The goal is to ensure new features integrate seamlessly without causing regressions or duplicating existing functionality.

### Key Findings

| Category | Status | Notes |
|----------|--------|-------|
| Continuity DNA (Character) | **EXISTS** | CharacterExtractionService already extracts hair, wardrobe, makeup, accessories DNA |
| Continuity DNA (Location) | **EXISTS** | LocationExtractionService already extracts location DNA with state changes |
| Visual Style | **EXISTS** | VwGenrePreset + Style Bible system already handles visual consistency |
| Narrative Beats | **PARTIAL** | VwEmotionalBeat exists but lacks shot-specific story beat tracking |
| Shot Continuity | **EXISTS** | ShotContinuityService implements 30-degree rule, compatibility matrix |
| Camera Movement | **EXISTS** | CameraMovementService with 50+ presets and movement stacking |
| Action Progression | **GAP** | Shot-to-shot action causality chain NOT tracked |
| Temporal Linking | **GAP** | Shot temporal relationships NOT explicit |
| Eyeline Continuity | **GAP** | Spatial/eyeline tracking NOT implemented |
| Atmospheric Evolution | **PARTIAL** | Basic atmosphere in prompts, no progression tracking |

---

## 1. Feature Mapping: Proposed vs Existing

### 1.1 CONTINUITY ANCHOR (What Stays Consistent)

#### CHARACTER REFERENCE
| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Physical appearance | `CharacterExtractionService.php:281-311` - Full DNA extraction | **EXISTS** |
| Hair DNA (color, style, length, texture) | `normalizeHairDNA()` lines 344-361 | **EXISTS** |
| Wardrobe DNA (outfit, colors, style, footwear) | `normalizeWardrobeDNA()` lines 367-384 | **EXISTS** |
| Makeup DNA (style, details) | `normalizeMakeupDNA()` lines 390-403 | **EXISTS** |
| Accessories | `normalizeAccessories()` lines 409-427 | **EXISTS** |
| appearsInScenes tracking | Line 300: `appearsInScenes` field | **EXISTS** |
| Visual mode enforcement | Lines 140-158: Master visual style compliance | **EXISTS** |

**Recommendation:** No new character continuity features needed. Existing system is comprehensive.

#### LOCATION LOCK
| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Location name & description | `LocationExtractionService.php:302-319` | **EXISTS** |
| Type (interior/exterior/abstract) | `normalizeLocationType()` lines 332-337 | **EXISTS** |
| Time of day | `normalizeTimeOfDay()` lines 342-362 | **EXISTS** |
| Weather conditions | `normalizeWeather()` lines 368-391 | **EXISTS** |
| Atmosphere | Line 312: `atmosphere` field | **EXISTS** |
| Mood | Line 313: `mood` field | **EXISTS** |
| Lighting style | Line 314: `lightingStyle` field | **EXISTS** |
| State changes across scenes | `normalizeStateChanges()` lines 415-431 | **EXISTS** |
| Visual mode enforcement | Lines 140-158: cinematic-realistic compliance | **EXISTS** |

**Recommendation:** No new location continuity features needed. Existing system is comprehensive.

#### VISUAL STYLE DNA
| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Cinematography style | `VideoPromptBuilderService.php:29-36` - 6 PROMPT_COMPONENTS | **EXISTS** |
| Color grading | `VwGenrePreset.color_grade` field | **EXISTS** |
| Lighting signature | `buildLightingComponent()` lines 271-305 | **EXISTS** |
| Quality markers | `QUALITY_MARKERS` const lines 41-46 | **EXISTS** |
| Film grain/aesthetic | Quality markers include `film grain` | **EXISTS** |

**Recommendation:** Existing style system is adequate. Consider adding lens specification persistence.

---

### 1.2 NARRATIVE BEAT (Story Purpose of Shot)

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Story beat types | `VwEmotionalBeat.php` model | **PARTIAL** |
| Three-act structure | `getGroupedByAct()` lines 71-91 | **EXISTS** |
| Story position mapping | `story_position` field (act1_setup, act2_midpoint, etc.) | **EXISTS** |
| Beat intensity | `intensity_level` field | **EXISTS** |
| Pacing suggestion | `pacing_suggestion` field | **EXISTS** |
| Shot-specific beat purpose | NOT IMPLEMENTED | **GAP** |
| Beat causality (THEREFORE/BUT) | NOT IMPLEMENTED | **GAP** |

**Gap Analysis:** The existing `VwEmotionalBeat` tracks story-level emotional moments but does NOT track:
- Shot-specific purpose (DISCOVERY, DECISION, REVELATION, etc.)
- Causality chains between shots (what causes the next shot)
- Beat progression within a single scene

**Recommendation:** Extend shot data model to include `storyBeat` field with values like:
- `establishing` - Sets up the scene/status quo
- `discovery` - Character becomes aware of something
- `decision` - Character makes a choice
- `action` - Character takes action
- `reaction` - Character responds to event
- `revelation` - Something is revealed to viewer/character
- `transition` - Bridges to next scene/beat

---

### 1.3 SPECIFIC ACTION (What Changes from Previous Shot)

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Subject action per shot | `ShotIntelligenceService.php:581` - `subjectAction` field | **EXISTS** |
| Physical action description | AI prompt requests this at lines 897-903 | **EXISTS** |
| Action progression tracking | NOT IMPLEMENTED | **GAP** |
| Action causality chain | NOT IMPLEMENTED | **GAP** |
| State change tracking | NOT IMPLEMENTED | **GAP** |

**Gap Analysis:** Individual shots have `subjectAction` but:
- No validation that Shot 2 action follows from Shot 1
- No tracking of character state changes across shots
- No "match on action" continuity validation

**Recommendation:** Add to shot analysis:
```php
// New fields for VwShotType or shot data
'previousActionContinuation' => true|false,  // Does this continue previous action?
'characterState' => 'seated|standing|moving|...',
'stateTransition' => 'rises|sits|turns|...',
```

---

### 1.4 CAMERA LANGUAGE (Shot Type + Movement + Psychology)

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Shot type | `VwShotType.php` - 360+ shot types | **EXISTS** |
| Shot categories | `getShotCategory()` in ShotContinuityService | **EXISTS** |
| Camera movement | `VwCameraMovement.php` - 50+ movements | **EXISTS** |
| Movement intensity | `MOTION_INTENSITY` const in VideoPromptBuilderService | **EXISTS** |
| Movement stacking | `stackable_with` field in VwCameraMovement | **EXISTS** |
| Psychological framing | `emotional_beats` field in VwShotType | **EXISTS** |
| Shot compatibility | `SHOT_COMPATIBILITY` matrix (98 lines) | **EXISTS** |
| Lens recommendations | `default_lens`, `default_aperture` in VwShotType | **EXISTS** |

**Recommendation:** Camera language system is comprehensive. No changes needed.

---

### 1.5 TEMPORAL PROGRESSION (Time Relationship to Previous Shot)

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Continuous action linking | NOT IMPLEMENTED | **GAP** |
| Time ellipsis tracking | NOT IMPLEMENTED | **GAP** |
| Match on action detection | NOT IMPLEMENTED | **GAP** |
| Pacing indicators | `recommended_pacing` in VwCoveragePattern | **PARTIAL** |

**Gap Analysis:** System tracks pacing at scene level but NOT:
- Shot-to-shot temporal relationship
- Whether cuts are continuous or elliptical
- Match-on-action cut opportunities

**Recommendation:** Add temporal link field to shot data:
```php
'temporalLink' => [
    'type' => 'continuous|ellipsis|flashback|flash_forward',
    'matchOnAction' => true|false,
    'timeGap' => null|'seconds'|'minutes'|'hours',
]
```

---

### 1.6 EYELINE & SPATIAL CONTINUITY

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| 30-degree rule | `check30DegreeRule()` in ShotContinuityService:245-292 | **EXISTS** |
| Eyeline direction tracking | NOT IMPLEMENTED | **GAP** |
| 180-degree rule validation | NOT IMPLEMENTED | **GAP** |
| Spatial position tracking | NOT IMPLEMENTED | **GAP** |
| Screen direction consistency | NOT IMPLEMENTED | **GAP** |

**Gap Analysis:** The 30-degree rule is implemented but:
- No eyeline direction tracking (left/right/center)
- No 180-degree axis crossing detection
- No spatial relationship between subjects

**Recommendation:** Add spatial continuity fields:
```php
'eyeline' => 'left|right|center|down|up',
'subjectPosition' => 'left|center|right',
'cameraAxis' => 'angle_degrees_0_to_360',
```

---

### 1.7 ATMOSPHERIC EVOLUTION (How Mood/Energy Changes)

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Atmospheric components | `buildAtmosphereComponent()` in VideoPromptBuilderService | **EXISTS** |
| Weather effects | Context `weather` field used | **EXISTS** |
| Environmental particles | Context `effects` field used | **EXISTS** |
| Lighting progression | NOT IMPLEMENTED | **GAP** |
| Mood evolution tracking | NOT IMPLEMENTED | **GAP** |
| Energy arc across shots | NOT IMPLEMENTED | **GAP** |

**Gap Analysis:** Individual shots have atmosphere but:
- No tracking of how atmosphere should evolve across shots
- No validation that mood progression is coherent
- No energy arc planning

**Recommendation:** Add atmosphere progression to scene context:
```php
'atmosphereArc' => [
    'start' => ['mood' => 'peaceful', 'energy' => 3],
    'end' => ['mood' => 'tense', 'energy' => 7],
    'progression' => 'gradual|sudden|wave',
]
```

---

### 1.8 TECHNICAL SPECIFICATIONS

| Proposed Feature | Existing Implementation | Status |
|-----------------|------------------------|--------|
| Camera specs | `camera_specs` field in VwShotType | **EXISTS** |
| Lens selection | `default_lens` field | **EXISTS** |
| Aperture settings | `default_aperture` field | **EXISTS** |
| Frame rate | `QUALITY_MARKERS` reference | **PARTIAL** |
| Aspect ratio | NOT SPECIFIED | **GAP** |
| Motion fluidity guidance | `getNegativeGuidance()` lines 388-409 | **EXISTS** |

**Recommendation:** Add aspect ratio to quality settings:
```php
'aspectRatios' => [
    'cinematic' => '2.35:1',
    'widescreen' => '16:9',
    'standard' => '4:3',
    'vertical' => '9:16',
]
```

---

## 2. Conflict Analysis

### 2.1 No Conflicts Detected

The proposed system design complements existing architecture without conflicts:

1. **Prompt Structure:** Existing 6-component prompt (Style + Subject + Action + Camera + Lighting + Atmosphere) can be extended, not replaced.

2. **Shot Types:** Existing VwShotType model already supports all proposed shot categories.

3. **Continuity Service:** Existing ShotContinuityService can be extended with new validation methods.

4. **Character/Location DNA:** Existing extraction services are comprehensive and don't need modification.

### 2.2 Potential Regression Risks

| Risk | Mitigation |
|------|------------|
| Adding fields to shot data may break existing consumers | Use optional fields with defaults |
| New continuity validations may reject previously valid sequences | Make new validations warnings, not errors by default |
| Enhanced prompts may exceed API token limits | Add prompt truncation/summarization for long sequences |

---

## 3. Recommended Integration Strategy

### Phase 1: Extend Shot Data Model (Low Risk)

Add new optional fields to shot data without modifying existing fields:

```php
// New fields for shot data (all optional with defaults)
[
    // Narrative Beat
    'storyBeat' => null,           // establishing|discovery|decision|action|reaction|revelation|transition
    'beatPurpose' => '',           // Free-text description of shot's narrative purpose

    // Action Progression
    'actionContinuity' => null,    // continues_previous|new_action|reaction_to
    'characterState' => null,      // seated|standing|walking|running|etc
    'stateTransition' => null,     // rises|sits|turns|enters|exits|etc

    // Temporal Linking
    'temporalLink' => 'continuous', // continuous|ellipsis|flashback|parallel
    'matchOnAction' => false,       // true if cut during motion

    // Spatial Continuity
    'eyeline' => null,             // frame_left|frame_right|camera|down|up
    'subjectPosition' => 'center', // left_third|center|right_third

    // Atmospheric Evolution
    'moodProgression' => null,     // builds_tension|releases_tension|maintains|shifts_to_X
    'energyLevel' => null,         // 1-10 scale
]
```

### Phase 2: Extend ShotContinuityService (Medium Risk)

Add new validation methods that can be enabled/disabled via settings:

```php
// New methods to add to ShotContinuityService

/**
 * Validate action causality between shots.
 */
public function validateActionCausality(array $prevShot, array $currShot): array;

/**
 * Check eyeline continuity (180-degree rule).
 */
public function checkEyelineMatch(array $prevShot, array $currShot): array;

/**
 * Validate temporal progression logic.
 */
public function validateTemporalProgression(array $prevShot, array $currShot): array;

/**
 * Check atmosphere evolution coherence.
 */
public function validateAtmosphereProgression(array $shots): array;
```

### Phase 3: Enhance AI Prompt Template (Low Risk)

Update the AI prompt in ShotIntelligenceService to request new fields:

```php
// Additions to getDefaultPrompt()
'storyBeat': "REQUIRED: What narrative function does this shot serve? (establishing|discovery|decision|action|reaction|revelation|transition)",
'actionContinuity': "How does this action relate to previous shot? (continues_previous|new_action|reaction_to)",
'eyeline': "Subject's gaze direction (frame_left|frame_right|camera|down|up)",
'moodProgression': "How does mood evolve from previous shot? (builds_tension|releases_tension|maintains|shifts)",
```

### Phase 4: Add New Settings (Low Risk)

Add new settings to VwSetting for feature toggling:

```php
// New settings
'shot_progression_enabled' => true,
'shot_progression_story_beats' => true,
'shot_progression_action_continuity' => true,
'shot_progression_eyeline_tracking' => false,  // Off by default (more complex)
'shot_progression_atmosphere_arc' => true,
'shot_progression_temporal_linking' => true,
```

---

## 4. Implementation Priority

### High Priority (Should Implement)
1. **Story Beat tracking** - Adds significant narrative value
2. **Action Continuity** - Prevents identical/disconnected shots
3. **Temporal Linking** - Clarifies shot relationships

### Medium Priority (Nice to Have)
4. **Atmosphere Evolution** - Enhances mood coherence
5. **Energy Arc** - Improves pacing

### Low Priority (Future Enhancement)
6. **Eyeline Tracking** - Complex, requires spatial modeling
7. **180-degree Rule Validation** - Requires camera axis tracking
8. **Match-on-Action Detection** - Requires motion analysis

---

## 5. Database Schema Considerations

### No Schema Changes Required Initially

All new fields can be stored in existing JSON columns:
- Shot data is already flexible JSON in processing jobs
- Scene context already accepts arbitrary fields
- Settings table handles feature flags

### Future Schema Additions (If High Usage)

```sql
-- Only if performance requires dedicated columns
ALTER TABLE wizard_processing_jobs
ADD COLUMN shot_progression_data JSON NULL;

-- Or add to vw_shot_types for template data
ALTER TABLE vw_shot_types
ADD COLUMN story_beat_recommendations JSON NULL,
ADD COLUMN action_continuity_rules JSON NULL;
```

---

## 6. Testing Strategy

### Regression Tests
1. Existing shot analysis produces same results with new fields as optional
2. Existing continuity validation scores remain unchanged
3. Video prompts generated are backward compatible

### New Feature Tests
1. Story beat assignment validates correctly
2. Action continuity detects disconnected shots
3. Temporal linking correctly identifies cut types
4. Settings toggle features on/off properly

---

## 7. Conclusion

The proposed Shot Progression & Narrative Development System can be safely integrated into the existing ArTime Video Wizard architecture because:

1. **No Duplicate Features** - All truly new functionality fills identified gaps
2. **Existing Systems Preserved** - Character DNA, Location DNA, Camera Movement, and Continuity systems remain unchanged
3. **Additive Approach** - New features add to, rather than replace, existing functionality
4. **Feature Flags** - All new features can be disabled if issues arise
5. **Optional Fields** - New shot data fields have sensible defaults

The main gaps to fill are:
- **Shot-specific story beats** with narrative purpose
- **Action progression tracking** between shots
- **Temporal relationship** specification
- **Atmosphere/energy evolution** across sequences

These can be implemented incrementally without risking regression.

---

## Appendix A: Existing Service File Locations

| Service | Location | Lines |
|---------|----------|-------|
| VideoPromptBuilderService | `app/Services/VideoPromptBuilderService.php` | 596 |
| ShotContinuityService | `app/Services/ShotContinuityService.php` | 985 |
| ShotIntelligenceService | `app/Services/ShotIntelligenceService.php` | 1117 |
| CharacterExtractionService | `app/Services/CharacterExtractionService.php` | 549 |
| LocationExtractionService | `app/Services/LocationExtractionService.php` | 432 |
| CameraMovementService | `app/Services/CameraMovementService.php` | 384 |
| SceneTypeDetectorService | `app/Services/SceneTypeDetectorService.php` | 516 |

## Appendix B: Existing Model File Locations

| Model | Location | Purpose |
|-------|----------|---------|
| VwShotType | `app/Models/VwShotType.php` | Shot type definitions |
| VwEmotionalBeat | `app/Models/VwEmotionalBeat.php` | Story emotional moments |
| VwCoveragePattern | `app/Models/VwCoveragePattern.php` | Scene coverage patterns |
| VwCameraMovement | `app/Models/VwCameraMovement.php` | Camera movement presets |
| VwGenrePreset | `app/Models/VwGenrePreset.php` | Visual style presets |
| VwSetting | `app/Models/VwSetting.php` | System settings |
