# Shot Progression Intelligence Engine
## Phase 6 Implementation Plan

**Version:** 1.0
**Date:** January 2026
**Status:** APPROVED FOR IMPLEMENTATION

---

## Overview

This plan introduces **Shot Progression Intelligence** as **Phase 6** of the Video Wizard Intelligence Engine, building upon the existing 5-phase architecture without disruption.

### Current Architecture (Phases 1-5)
```
┌─────────────────────────────────────────────────────────────────────┐
│                    VIDEO WIZARD INTELLIGENCE ENGINE                  │
├─────────────────────────────────────────────────────────────────────┤
│ Phase 1: Shot Intelligence        → Shot count & type selection     │
│ Phase 2: Motion Intelligence      → Camera movement selection       │
│ Phase 3: Shot Continuity          → 30° rule, compatibility matrix  │
│ Phase 4: Scene Detection          → Scene type classification       │
│ Phase 5: Full Integration         → AI-powered shot analysis        │
├─────────────────────────────────────────────────────────────────────┤
│ Phase 6: SHOT PROGRESSION (NEW)   → Narrative flow & action chains  │
└─────────────────────────────────────────────────────────────────────┘
```

### What Phase 6 Adds
- **Story Beat Assignment** - Each shot gets a narrative purpose
- **Action Progression Chains** - Shots causally connect (THEREFORE/BUT logic)
- **Temporal Linking** - Clear time relationships between shots
- **Atmosphere Arc** - Mood/energy evolution across sequences
- **Progression Validation** - Detect identical/disconnected shots

---

## Architecture Design

### New Service: `ShotProgressionService`

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ShotProgressionService                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────┐   ┌──────────────────┐   ┌─────────────────┐ │
│  │  Story Beat      │   │  Action Chain    │   │  Atmosphere     │ │
│  │  Analyzer        │   │  Builder         │   │  Arc Manager    │ │
│  └────────┬─────────┘   └────────┬─────────┘   └────────┬────────┘ │
│           │                      │                       │          │
│           ▼                      ▼                       ▼          │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │                    Progression Validator                       │ │
│  │  • Detects identical shots (no progression)                   │ │
│  │  • Validates action causality                                 │ │
│  │  • Checks temporal logic                                      │ │
│  │  • Scores progression quality (0-100)                         │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Integration Points

```
ShotIntelligenceService (Phase 5)
        │
        ▼
analyzeScene() ────────────────────┐
        │                          │
        ▼                          ▼
addCameraMovements()       addProgressionAnalysis() ◄── NEW
        │                          │
        ▼                          ▼
addVideoPrompts()          enrichPromptWithProgression() ◄── NEW
        │                          │
        ▼                          ▼
addContinuityAnalysis()    validateProgression() ◄── NEW
        │
        ▼
    RETURN
```

---

## Database Schema

### New Model: `VwStoryBeat`

```sql
CREATE TABLE vw_story_beats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Beat Classification
    beat_type ENUM('establishing', 'discovery', 'decision', 'action',
                   'reaction', 'revelation', 'transition', 'escalation',
                   'resolution', 'cliffhanger') NOT NULL,

    -- Narrative Properties
    narrative_function TEXT,           -- What this beat accomplishes
    typical_position VARCHAR(50),      -- 'opening', 'early', 'middle', 'late', 'closing'

    -- Shot Recommendations
    recommended_shot_types JSON,       -- ['close-up', 'medium', 'reaction']
    recommended_movements JSON,        -- ['push-in', 'static', 'slow-zoom']
    recommended_duration_range JSON,   -- {"min": 5, "max": 10}

    -- Prompt Enhancement
    prompt_prefix TEXT,                -- Text to prepend to video prompt
    prompt_suffix TEXT,                -- Text to append to video prompt
    action_verbs JSON,                 -- ['notices', 'realizes', 'decides']

    -- Causality Rules
    can_follow JSON,                   -- ['establishing', 'action'] - what beats can precede
    typically_leads_to JSON,           -- ['decision', 'reaction'] - what beats follow

    -- Metadata
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_beat_type (beat_type),
    INDEX idx_active_sort (is_active, sort_order)
);
```

### New Model: `VwAtmospherePreset`

```sql
CREATE TABLE vw_atmosphere_presets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Atmosphere Properties
    mood VARCHAR(50),                  -- 'tense', 'peaceful', 'mysterious', 'urgent'
    energy_level INT CHECK (energy_level BETWEEN 1 AND 10),

    -- Visual Elements
    lighting_keywords TEXT,            -- 'low-key, dramatic shadows, rim light'
    color_palette TEXT,                -- 'desaturated blues, warm amber highlights'
    environmental_effects TEXT,        -- 'light fog, dust particles, lens flare'

    -- Audio Hints (for future)
    audio_mood VARCHAR(50),

    -- Prompt Enhancement
    prompt_additions TEXT,             -- Text to add to video prompts

    -- Metadata
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Extended Shot Data Schema

```php
// Additional fields for shot analysis (stored in processing job JSON)
$shotProgressionData = [
    // Story Beat
    'storyBeat' => [
        'type' => 'discovery',           // From VwStoryBeat
        'purpose' => 'Character notices the glowing scroll',
        'beatSlug' => 'discovery-awareness',
    ],

    // Action Progression
    'actionProgression' => [
        'previousAction' => 'meditating in lotus position',
        'currentAction' => 'eyes open, gaze shifts left toward scroll',
        'actionChange' => 'internal_to_external',  // focus shifts
        'continuityType' => 'continuous',          // continuous|ellipsis|reaction
        'matchOnAction' => false,
    ],

    // Character State
    'characterState' => [
        'physicalState' => 'seated',              // seated|standing|moving|lying
        'emotionalState' => 'peaceful_to_alert',  // transition
        'focusTarget' => 'scroll_shelf',          // what character looks at
    ],

    // Temporal Link
    'temporalLink' => [
        'relationship' => 'continuous',     // continuous|seconds_later|minutes_later|hours_later
        'cutType' => 'standard',            // standard|match_on_action|j_cut|l_cut
        'impliedGap' => null,               // null for continuous, '2_seconds' etc
    ],

    // Atmosphere
    'atmosphere' => [
        'presetSlug' => 'tension-building',
        'energyLevel' => 4,                 // 1-10, should progress
        'moodShift' => 'peaceful_to_curious',
    ],

    // Progression Score
    'progressionScore' => [
        'overall' => 85,
        'beatClarity' => 90,
        'actionDifference' => 80,
        'atmosphereCoherence' => 85,
    ],
];
```

---

## Service Implementation

### `ShotProgressionService.php`

```php
<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwStoryBeat;
use Modules\AppVideoWizard\Models\VwAtmospherePreset;

/**
 * ShotProgressionService - Phase 6 Intelligence Engine
 *
 * Manages narrative progression across shot sequences:
 * - Story beat assignment for each shot
 * - Action causality chain building
 * - Temporal relationship tracking
 * - Atmosphere evolution management
 * - Progression quality validation
 */
class ShotProgressionService
{
    protected ?CameraMovementService $movementService = null;

    /**
     * Story beat types with their narrative functions.
     */
    public const BEAT_TYPES = [
        'establishing' => 'Sets up scene, introduces status quo',
        'discovery' => 'Character becomes aware of something new',
        'decision' => 'Character makes a choice or commitment',
        'action' => 'Character takes physical or verbal action',
        'reaction' => 'Character responds to stimulus/event',
        'revelation' => 'Information revealed to character or audience',
        'transition' => 'Bridges between story beats or scenes',
        'escalation' => 'Tension or stakes increase',
        'resolution' => 'Conflict resolved, question answered',
        'cliffhanger' => 'Leaves audience in suspense',
    ];

    /**
     * Action continuity types.
     */
    public const ACTION_CONTINUITY = [
        'continuous' => 'Same action continues from previous shot',
        'develops' => 'Action progresses/evolves from previous',
        'reaction' => 'Response to previous shot\'s action',
        'parallel' => 'Simultaneous action elsewhere',
        'contrast' => 'Deliberately different for effect',
        'new_beat' => 'Fresh action starting new story beat',
    ];

    /**
     * Analyze and assign progression data to shots.
     */
    public function analyzeProgression(array $shots, array $context = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'shots' => $shots,
                'progressionScore' => null,
            ];
        }

        $enrichedShots = [];
        $previousShot = null;

        foreach ($shots as $index => $shot) {
            $enriched = $this->enrichShotWithProgression(
                $shot,
                $previousShot,
                $index,
                count($shots),
                $context
            );
            $enrichedShots[] = $enriched;
            $previousShot = $enriched;
        }

        // Validate overall progression
        $validation = $this->validateProgression($enrichedShots);

        // Calculate atmosphere arc
        $atmosphereArc = $this->calculateAtmosphereArc($enrichedShots, $context);

        return [
            'enabled' => true,
            'shots' => $enrichedShots,
            'progressionScore' => $validation['score'],
            'progressionIssues' => $validation['issues'],
            'progressionSuggestions' => $validation['suggestions'],
            'atmosphereArc' => $atmosphereArc,
        ];
    }

    /**
     * Enrich a single shot with progression data.
     */
    protected function enrichShotWithProgression(
        array $shot,
        ?array $previousShot,
        int $index,
        int $totalShots,
        array $context
    ): array {
        // 1. Assign story beat
        $storyBeat = $this->assignStoryBeat($shot, $previousShot, $index, $totalShots, $context);
        $shot['storyBeat'] = $storyBeat;

        // 2. Analyze action progression
        $actionProgression = $this->analyzeActionProgression($shot, $previousShot);
        $shot['actionProgression'] = $actionProgression;

        // 3. Determine temporal link
        $temporalLink = $this->determineTemporalLink($shot, $previousShot, $storyBeat);
        $shot['temporalLink'] = $temporalLink;

        // 4. Assign atmosphere
        $atmosphere = $this->assignAtmosphere($shot, $previousShot, $index, $totalShots, $context);
        $shot['atmosphere'] = $atmosphere;

        // 5. Calculate shot progression score
        $shot['progressionScore'] = $this->calculateShotProgressionScore($shot, $previousShot);

        return $shot;
    }

    /**
     * Assign story beat based on shot position and content.
     */
    protected function assignStoryBeat(
        array $shot,
        ?array $previousShot,
        int $index,
        int $totalShots,
        array $context
    ): array {
        $position = $index / max(1, $totalShots - 1);
        $shotType = $shot['type'] ?? 'medium';
        $sceneType = $context['sceneType'] ?? 'dialogue';

        // Position-based beat suggestion
        $suggestedBeat = $this->suggestBeatByPosition($position, $shotType, $sceneType);

        // Refine based on previous shot's beat
        if ($previousShot && isset($previousShot['storyBeat']['type'])) {
            $suggestedBeat = $this->refineBeatByCausality(
                $suggestedBeat,
                $previousShot['storyBeat']['type']
            );
        }

        // Get beat details from database or defaults
        $beatData = VwStoryBeat::getBySlug($suggestedBeat) ?? $this->getDefaultBeatData($suggestedBeat);

        return [
            'type' => $suggestedBeat,
            'purpose' => $this->generateBeatPurpose($shot, $suggestedBeat, $context),
            'narrativeFunction' => $beatData['narrative_function'] ?? self::BEAT_TYPES[$suggestedBeat] ?? '',
            'promptEnhancement' => $beatData['prompt_prefix'] ?? '',
        ];
    }

    /**
     * Suggest beat type based on position in sequence.
     */
    protected function suggestBeatByPosition(float $position, string $shotType, string $sceneType): string
    {
        // Opening shots
        if ($position < 0.15) {
            return 'establishing';
        }

        // Early development
        if ($position < 0.35) {
            if (in_array($shotType, ['close-up', 'extreme-close-up'])) {
                return 'discovery';
            }
            return 'action';
        }

        // Middle - peak activity
        if ($position < 0.65) {
            if ($shotType === 'reaction') {
                return 'reaction';
            }
            if (in_array($shotType, ['close-up', 'medium-close-up'])) {
                return 'decision';
            }
            return 'escalation';
        }

        // Late - climax/resolution
        if ($position < 0.85) {
            if ($shotType === 'reaction') {
                return 'revelation';
            }
            return 'action';
        }

        // Closing
        return $sceneType === 'emotional' ? 'resolution' : 'transition';
    }

    /**
     * Refine beat based on what came before (causality).
     */
    protected function refineBeatByCausality(string $suggestedBeat, string $previousBeat): string
    {
        // Causality rules: What typically follows what
        $causalityMap = [
            'establishing' => ['discovery', 'action'],
            'discovery' => ['decision', 'reaction', 'revelation'],
            'decision' => ['action', 'escalation'],
            'action' => ['reaction', 'escalation', 'action'],
            'reaction' => ['action', 'decision', 'revelation'],
            'revelation' => ['reaction', 'decision', 'escalation'],
            'escalation' => ['action', 'revelation', 'resolution'],
            'resolution' => ['transition', 'establishing'],
            'transition' => ['establishing', 'action'],
            'cliffhanger' => ['establishing', 'reaction'],
        ];

        $validFollowers = $causalityMap[$previousBeat] ?? [];

        // If suggested beat is valid follower, keep it
        if (in_array($suggestedBeat, $validFollowers)) {
            return $suggestedBeat;
        }

        // Otherwise, pick the first valid follower
        return $validFollowers[0] ?? $suggestedBeat;
    }

    /**
     * Analyze how action progresses from previous shot.
     */
    protected function analyzeActionProgression(array $shot, ?array $previousShot): array
    {
        if (!$previousShot) {
            return [
                'type' => 'new_beat',
                'previousAction' => null,
                'currentAction' => $shot['subjectAction'] ?? $shot['action'] ?? '',
                'actionChange' => 'scene_opening',
            ];
        }

        $prevAction = $previousShot['subjectAction'] ?? $previousShot['action'] ?? '';
        $currAction = $shot['subjectAction'] ?? $shot['action'] ?? '';

        // Detect if actions are identical (BAD - no progression)
        $similarity = $this->calculateActionSimilarity($prevAction, $currAction);

        if ($similarity > 0.9) {
            // Almost identical - flag as issue
            return [
                'type' => 'static',  // Problem: no progression
                'previousAction' => $prevAction,
                'currentAction' => $currAction,
                'actionChange' => 'none',
                'warning' => 'Actions are nearly identical - add progression',
            ];
        }

        // Determine continuity type
        $continuityType = $this->inferContinuityType($prevAction, $currAction, $shot, $previousShot);

        return [
            'type' => $continuityType,
            'previousAction' => $prevAction,
            'currentAction' => $currAction,
            'actionChange' => $this->describeActionChange($prevAction, $currAction),
        ];
    }

    /**
     * Calculate similarity between two action descriptions.
     */
    protected function calculateActionSimilarity(string $action1, string $action2): float
    {
        if (empty($action1) || empty($action2)) {
            return 0.0;
        }

        $action1 = strtolower(trim($action1));
        $action2 = strtolower(trim($action2));

        if ($action1 === $action2) {
            return 1.0;
        }

        // Simple word overlap similarity
        $words1 = array_unique(preg_split('/\s+/', $action1));
        $words2 = array_unique(preg_split('/\s+/', $action2));

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Infer the type of action continuity.
     */
    protected function inferContinuityType(
        string $prevAction,
        string $currAction,
        array $shot,
        array $previousShot
    ): string {
        // Check for reaction indicators
        $reactionWords = ['reacts', 'responds', 'notices', 'sees', 'hears', 'realizes'];
        foreach ($reactionWords as $word) {
            if (stripos($currAction, $word) !== false) {
                return 'reaction';
            }
        }

        // Check for continuation indicators
        $continuationWords = ['continues', 'keeps', 'still', 'remains'];
        foreach ($continuationWords as $word) {
            if (stripos($currAction, $word) !== false) {
                return 'continuous';
            }
        }

        // Check shot type for clues
        if ($shot['type'] === 'reaction') {
            return 'reaction';
        }

        // Default based on beat type
        $currentBeat = $shot['storyBeat']['type'] ?? '';
        if ($currentBeat === 'reaction') {
            return 'reaction';
        }
        if ($currentBeat === 'establishing') {
            return 'new_beat';
        }

        return 'develops';
    }

    /**
     * Describe how action changed between shots.
     */
    protected function describeActionChange(string $prevAction, string $currAction): string
    {
        // Extract key verbs/states
        $prevVerbs = $this->extractActionVerbs($prevAction);
        $currVerbs = $this->extractActionVerbs($currAction);

        if (empty($prevVerbs) || empty($currVerbs)) {
            return 'action_shift';
        }

        // Check for state changes
        $stateChanges = [
            ['sits', 'stands'] => 'rises',
            ['stands', 'sits'] => 'sits_down',
            ['still', 'moves'] => 'begins_moving',
            ['moves', 'still'] => 'stops',
            ['closed', 'open'] => 'opens',
            ['open', 'closed'] => 'closes',
        ];

        foreach ($stateChanges as $pair => $change) {
            if (in_array($pair[0], $prevVerbs) && in_array($pair[1], $currVerbs)) {
                return $change;
            }
        }

        return 'action_develops';
    }

    /**
     * Extract action verbs from description.
     */
    protected function extractActionVerbs(string $action): array
    {
        $verbs = [];
        $action = strtolower($action);

        $commonVerbs = [
            'sits', 'stands', 'walks', 'runs', 'looks', 'turns', 'reaches',
            'speaks', 'listens', 'watches', 'waits', 'moves', 'stops',
            'opens', 'closes', 'takes', 'gives', 'holds', 'drops',
            'enters', 'exits', 'approaches', 'retreats', 'rises', 'falls',
            'meditates', 'contemplates', 'observes', 'reacts', 'responds',
        ];

        foreach ($commonVerbs as $verb) {
            if (strpos($action, $verb) !== false) {
                $verbs[] = $verb;
            }
        }

        return $verbs;
    }

    /**
     * Determine temporal relationship to previous shot.
     */
    protected function determineTemporalLink(array $shot, ?array $previousShot, array $storyBeat): array
    {
        if (!$previousShot) {
            return [
                'relationship' => 'scene_start',
                'cutType' => 'standard',
                'impliedGap' => null,
            ];
        }

        // Determine based on beat type
        $beatType = $storyBeat['type'] ?? '';

        // Establishing shots often imply time jump
        if ($beatType === 'establishing') {
            return [
                'relationship' => 'time_jump',
                'cutType' => 'standard',
                'impliedGap' => 'scene_change',
            ];
        }

        // Reactions are typically immediate
        if ($beatType === 'reaction') {
            return [
                'relationship' => 'continuous',
                'cutType' => 'reaction_cut',
                'impliedGap' => null,
            ];
        }

        // Check for match-on-action potential
        $actionProg = $shot['actionProgression'] ?? [];
        if ($actionProg['type'] === 'continuous') {
            return [
                'relationship' => 'continuous',
                'cutType' => 'match_on_action',
                'impliedGap' => null,
            ];
        }

        // Default: continuous action
        return [
            'relationship' => 'continuous',
            'cutType' => 'standard',
            'impliedGap' => null,
        ];
    }

    /**
     * Assign atmosphere based on position and context.
     */
    protected function assignAtmosphere(
        array $shot,
        ?array $previousShot,
        int $index,
        int $totalShots,
        array $context
    ): array {
        $position = $index / max(1, $totalShots - 1);
        $sceneType = $context['sceneType'] ?? 'dialogue';
        $sceneMood = $context['mood'] ?? 'neutral';

        // Calculate energy level based on position (narrative arc)
        $energyLevel = $this->calculateEnergyLevel($position, $sceneType, $sceneMood);

        // Determine mood progression
        $previousEnergy = $previousShot['atmosphere']['energyLevel'] ?? $energyLevel;
        $moodShift = $this->describeMoodShift($previousEnergy, $energyLevel);

        // Get atmosphere keywords
        $atmosphereKeywords = $this->getAtmosphereKeywords($energyLevel, $sceneMood);

        return [
            'energyLevel' => $energyLevel,
            'moodShift' => $moodShift,
            'keywords' => $atmosphereKeywords,
            'promptAddition' => implode(', ', $atmosphereKeywords),
        ];
    }

    /**
     * Calculate energy level based on narrative position.
     */
    protected function calculateEnergyLevel(float $position, string $sceneType, string $sceneMood): int
    {
        // Base energy curves by scene type
        $curves = [
            'action' => fn($p) => 5 + (int)($p * 5),           // 5→10 (rising)
            'dialogue' => fn($p) => 4 + (int)(sin($p * M_PI) * 3), // 4→7→4 (arc)
            'emotional' => fn($p) => 3 + (int)($p * 4),        // 3→7 (building)
            'establishing' => fn($p) => 3,                      // 3 (steady, calm)
            'montage' => fn($p) => 5 + (int)(sin($p * 4 * M_PI) * 2), // 5 (oscillating)
        ];

        $curve = $curves[$sceneType] ?? $curves['dialogue'];
        $baseEnergy = $curve($position);

        // Mood modifier
        $moodModifiers = [
            'tense' => 2,
            'urgent' => 2,
            'peaceful' => -2,
            'melancholic' => -1,
            'energetic' => 2,
            'mysterious' => 1,
        ];

        $modifier = $moodModifiers[$sceneMood] ?? 0;

        return max(1, min(10, $baseEnergy + $modifier));
    }

    /**
     * Describe the mood shift between energy levels.
     */
    protected function describeMoodShift(int $previousEnergy, int $currentEnergy): string
    {
        $diff = $currentEnergy - $previousEnergy;

        if (abs($diff) <= 1) {
            return 'maintains';
        }
        if ($diff >= 2) {
            return 'intensifies';
        }
        if ($diff <= -2) {
            return 'releases';
        }
        if ($diff > 0) {
            return 'builds_slightly';
        }
        return 'eases_slightly';
    }

    /**
     * Get atmosphere keywords based on energy and mood.
     */
    protected function getAtmosphereKeywords(int $energyLevel, string $sceneMood): array
    {
        $keywords = [];

        // Energy-based keywords
        if ($energyLevel <= 3) {
            $keywords[] = 'calm atmosphere';
            $keywords[] = 'gentle ambiance';
        } elseif ($energyLevel <= 5) {
            $keywords[] = 'balanced mood';
        } elseif ($energyLevel <= 7) {
            $keywords[] = 'building tension';
        } else {
            $keywords[] = 'intense atmosphere';
            $keywords[] = 'heightened energy';
        }

        // Mood-specific additions
        $moodKeywords = [
            'tense' => ['suspenseful', 'tight framing'],
            'peaceful' => ['serene', 'soft lighting'],
            'mysterious' => ['enigmatic', 'shadowy'],
            'urgent' => ['dynamic', 'fast-paced'],
            'romantic' => ['warm glow', 'intimate'],
            'melancholic' => ['wistful', 'muted tones'],
        ];

        if (isset($moodKeywords[$sceneMood])) {
            $keywords = array_merge($keywords, $moodKeywords[$sceneMood]);
        }

        return $keywords;
    }

    /**
     * Validate overall progression of shot sequence.
     */
    public function validateProgression(array $shots): array
    {
        $issues = [];
        $suggestions = [];
        $scores = [];

        for ($i = 1; $i < count($shots); $i++) {
            $prevShot = $shots[$i - 1];
            $currShot = $shots[$i];

            // Check for static/identical shots
            $actionProg = $currShot['actionProgression'] ?? [];
            if (($actionProg['type'] ?? '') === 'static') {
                $issues[] = [
                    'type' => 'no_progression',
                    'position' => $i,
                    'message' => "Shot {$i} has nearly identical action to shot " . ($i - 1),
                    'severity' => 'high',
                ];
                $suggestions[] = [
                    'position' => $i,
                    'suggestion' => 'Add specific action change: what does the character DO differently?',
                ];
            }

            // Check beat causality
            $prevBeat = $prevShot['storyBeat']['type'] ?? '';
            $currBeat = $currShot['storyBeat']['type'] ?? '';
            if (!$this->isValidBeatSequence($prevBeat, $currBeat)) {
                $issues[] = [
                    'type' => 'weak_causality',
                    'position' => $i,
                    'message' => "'{$currBeat}' beat doesn't naturally follow '{$prevBeat}'",
                    'severity' => 'medium',
                ];
            }

            // Check energy progression coherence
            $prevEnergy = $prevShot['atmosphere']['energyLevel'] ?? 5;
            $currEnergy = $currShot['atmosphere']['energyLevel'] ?? 5;
            if (abs($currEnergy - $prevEnergy) > 3) {
                $issues[] = [
                    'type' => 'energy_jump',
                    'position' => $i,
                    'message' => "Abrupt energy change from {$prevEnergy} to {$currEnergy}",
                    'severity' => 'low',
                ];
            }

            // Calculate per-shot score
            $scores[] = $currShot['progressionScore']['overall'] ?? 70;
        }

        // Overall score
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 100;
        $issuePenalty = count(array_filter($issues, fn($i) => $i['severity'] === 'high')) * 15
                      + count(array_filter($issues, fn($i) => $i['severity'] === 'medium')) * 8
                      + count(array_filter($issues, fn($i) => $i['severity'] === 'low')) * 3;

        $finalScore = max(0, min(100, $avgScore - $issuePenalty));

        return [
            'score' => (int) $finalScore,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'shotScores' => $scores,
        ];
    }

    /**
     * Check if beat sequence is valid.
     */
    protected function isValidBeatSequence(string $prevBeat, string $currBeat): bool
    {
        $validSequences = [
            'establishing' => ['discovery', 'action', 'transition'],
            'discovery' => ['decision', 'reaction', 'revelation', 'action'],
            'decision' => ['action', 'escalation', 'revelation'],
            'action' => ['reaction', 'escalation', 'action', 'resolution'],
            'reaction' => ['action', 'decision', 'revelation', 'reaction'],
            'revelation' => ['reaction', 'decision', 'escalation'],
            'escalation' => ['action', 'revelation', 'resolution', 'cliffhanger'],
            'resolution' => ['transition', 'establishing'],
            'transition' => ['establishing', 'action', 'discovery'],
            'cliffhanger' => ['establishing', 'reaction'],
        ];

        $valid = $validSequences[$prevBeat] ?? [];
        return empty($valid) || in_array($currBeat, $valid);
    }

    /**
     * Calculate progression score for a single shot.
     */
    protected function calculateShotProgressionScore(array $shot, ?array $previousShot): array
    {
        $scores = [
            'beatClarity' => 80,
            'actionDifference' => 80,
            'atmosphereCoherence' => 80,
        ];

        // Beat clarity - does it have a clear purpose?
        $beatPurpose = $shot['storyBeat']['purpose'] ?? '';
        if (strlen($beatPurpose) > 20) {
            $scores['beatClarity'] = 90;
        }
        if (strlen($beatPurpose) > 50) {
            $scores['beatClarity'] = 95;
        }

        // Action difference
        $actionProg = $shot['actionProgression'] ?? [];
        if (($actionProg['type'] ?? '') === 'static') {
            $scores['actionDifference'] = 30;
        } elseif (in_array($actionProg['type'] ?? '', ['develops', 'reaction'])) {
            $scores['actionDifference'] = 90;
        }

        // Atmosphere coherence
        if ($previousShot) {
            $prevEnergy = $previousShot['atmosphere']['energyLevel'] ?? 5;
            $currEnergy = $shot['atmosphere']['energyLevel'] ?? 5;
            $diff = abs($currEnergy - $prevEnergy);
            $scores['atmosphereCoherence'] = max(50, 100 - ($diff * 10));
        }

        $scores['overall'] = (int) array_sum($scores) / count($scores);

        return $scores;
    }

    /**
     * Calculate atmosphere arc for entire sequence.
     */
    public function calculateAtmosphereArc(array $shots, array $context): array
    {
        $energyLevels = array_map(
            fn($s) => $s['atmosphere']['energyLevel'] ?? 5,
            $shots
        );

        return [
            'startEnergy' => $energyLevels[0] ?? 5,
            'peakEnergy' => max($energyLevels),
            'endEnergy' => end($energyLevels) ?: 5,
            'peakPosition' => array_search(max($energyLevels), $energyLevels),
            'curve' => $energyLevels,
            'pattern' => $this->identifyArcPattern($energyLevels),
        ];
    }

    /**
     * Identify the narrative arc pattern.
     */
    protected function identifyArcPattern(array $energyLevels): string
    {
        if (count($energyLevels) < 2) {
            return 'flat';
        }

        $start = $energyLevels[0];
        $end = end($energyLevels);
        $peak = max($energyLevels);
        $peakPos = array_search($peak, $energyLevels) / (count($energyLevels) - 1);

        if ($peak - $start < 2 && $peak - $end < 2) {
            return 'flat';
        }
        if ($peakPos > 0.6 && $peak > $start + 2) {
            return 'rising_climax';
        }
        if ($peakPos < 0.4 && $peak > $end + 2) {
            return 'front_loaded';
        }
        if ($peakPos > 0.3 && $peakPos < 0.7) {
            return 'classic_arc';
        }

        return 'variable';
    }

    /**
     * Generate beat purpose description.
     */
    protected function generateBeatPurpose(array $shot, string $beatType, array $context): string
    {
        $action = $shot['subjectAction'] ?? $shot['action'] ?? 'subject in frame';

        $templates = [
            'establishing' => "Establishes the scene: {$action}",
            'discovery' => "Character discovers/notices something: {$action}",
            'decision' => "Character makes a choice: {$action}",
            'action' => "Character takes action: {$action}",
            'reaction' => "Character reacts to event: {$action}",
            'revelation' => "Something is revealed: {$action}",
            'escalation' => "Tension/stakes increase: {$action}",
            'resolution' => "Conflict resolves: {$action}",
            'transition' => "Bridges to next beat: {$action}",
            'cliffhanger' => "Creates suspense: {$action}",
        ];

        return $templates[$beatType] ?? "Shot purpose: {$action}";
    }

    /**
     * Get default beat data for fallback.
     */
    protected function getDefaultBeatData(string $beatType): array
    {
        return [
            'narrative_function' => self::BEAT_TYPES[$beatType] ?? 'Advances the story',
            'prompt_prefix' => '',
        ];
    }

    /**
     * Enhance video prompt with progression data.
     */
    public function enhancePromptWithProgression(string $basePrompt, array $shot): string
    {
        $enhancements = [];

        // Add beat-based enhancement
        if (isset($shot['storyBeat']['promptEnhancement']) && !empty($shot['storyBeat']['promptEnhancement'])) {
            $enhancements[] = $shot['storyBeat']['promptEnhancement'];
        }

        // Add action progression clarity
        if (isset($shot['actionProgression']['actionChange'])) {
            $change = $shot['actionProgression']['actionChange'];
            if ($change !== 'none' && $change !== 'action_shift') {
                $enhancements[] = "showing {$change}";
            }
        }

        // Add atmosphere keywords
        if (isset($shot['atmosphere']['promptAddition']) && !empty($shot['atmosphere']['promptAddition'])) {
            $enhancements[] = $shot['atmosphere']['promptAddition'];
        }

        if (empty($enhancements)) {
            return $basePrompt;
        }

        return $basePrompt . '. ' . implode(', ', $enhancements);
    }

    // ===================
    // SETTINGS
    // ===================

    public function isEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_enabled', true);
    }

    public function isStoryBeatsEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_story_beats', true);
    }

    public function isActionContinuityEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_action_continuity', true);
    }

    public function isAtmosphereArcEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_atmosphere_arc', true);
    }

    public function getMinProgressionScore(): int
    {
        return (int) VwSetting::getValue('shot_progression_min_score', 60);
    }
}
```

---

## Admin Panel Integration

### New Settings Category: `shot_progression`

Add to `VwSettingSeeder.php`:

```php
// ===================================
// SHOT PROGRESSION INTELLIGENCE (Phase 6)
// ===================================
[
    'slug' => 'shot_progression_enabled',
    'name' => 'Enable Shot Progression Intelligence',
    'description' => 'Master toggle for Phase 6 shot progression system. Analyzes narrative flow, action chains, and atmosphere evolution.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 1,
],
[
    'slug' => 'shot_progression_story_beats',
    'name' => 'Story Beat Assignment',
    'description' => 'Assign narrative purpose to each shot (establishing, discovery, decision, action, reaction, etc.).',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 2,
],
[
    'slug' => 'shot_progression_action_continuity',
    'name' => 'Action Continuity Validation',
    'description' => 'Detect and flag shots with identical or disconnected actions. Ensures progression between shots.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 3,
],
[
    'slug' => 'shot_progression_temporal_linking',
    'name' => 'Temporal Link Analysis',
    'description' => 'Track time relationships between shots (continuous, ellipsis, match-on-action).',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 4,
],
[
    'slug' => 'shot_progression_atmosphere_arc',
    'name' => 'Atmosphere Arc Management',
    'description' => 'Calculate and validate energy/mood progression across shot sequence.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 5,
],
[
    'slug' => 'shot_progression_min_score',
    'name' => 'Minimum Progression Score',
    'description' => 'Warn when progression score falls below this threshold (0-100).',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'number',
    'default_value' => '60',
    'sort_order' => 6,
],
[
    'slug' => 'shot_progression_auto_enhance_prompts',
    'name' => 'Auto-Enhance Video Prompts',
    'description' => 'Automatically add progression context to video generation prompts.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 7,
],
[
    'slug' => 'shot_progression_flag_static_shots',
    'name' => 'Flag Static/Identical Shots',
    'description' => 'Show warnings when consecutive shots have nearly identical actions.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 8,
],
```

### New Admin Page: Story Beats Manager

**Route:** `/admin/video-wizard/cinematography/story-beats`

**Features:**
- Full CRUD for story beat types
- Define causality rules (what beats can follow each beat)
- Set prompt enhancements per beat
- Preview beat in context
- Seed default Hollywood beats

### New Admin Page: Progression Dashboard

**Route:** `/admin/video-wizard/cinematography/progression`

**Dashboard Cards:**
1. **Story Beats** - Count, active beats, beat type distribution
2. **Atmosphere Presets** - Count, energy range distribution
3. **Progression Analytics** - Average scores, common issues
4. **Quick Test** - Test progression analysis on sample scene

---

## Integration with ShotIntelligenceService

### Modified `analyzeScene()` Method

```php
public function analyzeScene(array $scene, array $context = []): array
{
    // ... existing Phase 1-4 code ...

    // PHASE 5: Parse AI response
    $analysis = $this->parseAIResponse($aiResponse['response'], $scene, $minShots, $maxShots);

    // PHASE 1: Add camera movements
    $analysis = $this->addCameraMovements($analysis, $scene, $context);

    // PHASE 2: Generate video prompts
    $analysis = $this->addVideoPrompts($analysis, $scene, $context);

    // PHASE 3: Continuity analysis
    $analysis = $this->addContinuityAnalysis($analysis, $context);

    // ========== NEW: PHASE 6 ==========
    $analysis = $this->addProgressionAnalysis($analysis, $scene, $context);
    // ==================================

    return $analysis;
}

/**
 * PHASE 6: Add shot progression analysis.
 */
protected function addProgressionAnalysis(array $analysis, array $scene, array $context): array
{
    if (!$this->progressionService || !$this->progressionService->isEnabled()) {
        $analysis['progression'] = ['enabled' => false];
        return $analysis;
    }

    // Analyze progression
    $progressionResult = $this->progressionService->analyzeProgression(
        $analysis['shots'] ?? [],
        $context
    );

    // Update shots with progression data
    $analysis['shots'] = $progressionResult['shots'];

    // Add progression metadata
    $analysis['progression'] = [
        'enabled' => true,
        'score' => $progressionResult['progressionScore'],
        'issues' => $progressionResult['progressionIssues'],
        'suggestions' => $progressionResult['progressionSuggestions'],
        'atmosphereArc' => $progressionResult['atmosphereArc'],
    ];

    // Enhance video prompts with progression context
    if ($this->progressionService->isEnabled() &&
        VwSetting::getValue('shot_progression_auto_enhance_prompts', true)) {

        foreach ($analysis['shots'] as $index => &$shot) {
            if (isset($shot['videoPrompt'])) {
                $shot['videoPrompt'] = $this->progressionService->enhancePromptWithProgression(
                    $shot['videoPrompt'],
                    $shot
                );
            }
        }
    }

    return $analysis;
}
```

---

## Updated AI Prompt Template

Add to `getDefaultPrompt()`:

```php
// Additional instructions for Phase 6
'SHOT PROGRESSION (CRITICAL - Each shot must DIFFER from the previous):
- Shot 1→2: What CHANGES? (action, position, focus, emotion)
- Avoid identical descriptions across shots
- Use causality: "THEREFORE the character..." or "BUT then..."
- Include state transitions: "rises from seated", "turns to face", "opens eyes"

STORY BEAT ASSIGNMENT:
- Opening shots: establishing (set the scene)
- Discovery: character notices/realizes something
- Decision: character makes a choice
- Action: character does something
- Reaction: character responds
- Revelation: information is revealed
- Resolution: conflict/question resolved

For each shot, include:
"storyBeat": "establishing|discovery|decision|action|reaction|revelation",
"actionChange": "what specifically changes from previous shot",'
```

---

## Implementation Timeline

### Week 1: Foundation
- [ ] Create database migrations for `vw_story_beats` and `vw_atmosphere_presets`
- [ ] Create `VwStoryBeat` and `VwAtmospherePreset` models
- [ ] Create seeders with default story beats
- [ ] Add `CATEGORY_SHOT_PROGRESSION` to `VwSetting`
- [ ] Add progression settings to seeder

### Week 2: Core Service
- [ ] Implement `ShotProgressionService` (full implementation above)
- [ ] Add dependency injection to `ShotIntelligenceService`
- [ ] Implement `addProgressionAnalysis()` integration
- [ ] Add progression data to shot output

### Week 3: Admin Interface
- [ ] Create `StoryBeatController` with full CRUD
- [ ] Create story beats admin views
- [ ] Add progression dashboard to cinematography section
- [ ] Add settings category tab with icon

### Week 4: Testing & Polish
- [ ] Unit tests for `ShotProgressionService`
- [ ] Integration tests for full pipeline
- [ ] Test with real video generation
- [ ] Documentation and release notes

---

## Files to Create/Modify

### New Files
```
app/Models/VwStoryBeat.php
app/Models/VwAtmospherePreset.php
app/Services/ShotProgressionService.php
app/Http/Controllers/Admin/StoryBeatController.php
app/Http/Controllers/Admin/AtmospherePresetController.php
database/migrations/xxxx_create_vw_story_beats_table.php
database/migrations/xxxx_create_vw_atmosphere_presets_table.php
database/seeders/VwStoryBeatSeeder.php
database/seeders/VwAtmospherePresetSeeder.php
resources/views/admin/cinematography/story-beats/index.blade.php
resources/views/admin/cinematography/story-beats/create.blade.php
resources/views/admin/cinematography/story-beats/edit.blade.php
resources/views/admin/cinematography/progression/index.blade.php
```

### Modified Files
```
app/Models/VwSetting.php                    → Add CATEGORY_SHOT_PROGRESSION
app/Services/ShotIntelligenceService.php    → Add Phase 6 integration
database/seeders/VwSettingSeeder.php        → Add progression settings
routes/admin.php                            → Add story beats routes
resources/views/admin/cinematography/index.blade.php → Add progression card
```

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Progression Score | >75 average | Dashboard analytics |
| Static Shot Rate | <5% | Flagged shots / total shots |
| Beat Assignment Coverage | 100% | Shots with beat / total shots |
| Prompt Enhancement | Active | Settings enabled |
| User Satisfaction | Positive | Feedback on video quality |

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Performance impact | All new analysis is optional via settings |
| Breaking existing flows | Progression data is additive, no existing fields modified |
| AI token limits | Prompt additions are concise |
| Complex causality rules | Start with simple rules, expand based on feedback |

---

## Conclusion

Phase 6 Shot Progression Intelligence completes the Video Wizard's cinematic capabilities by ensuring each shot in a sequence:
1. Has a clear **narrative purpose** (story beat)
2. **Differs meaningfully** from adjacent shots (action progression)
3. **Connects logically** in time (temporal linking)
4. **Evolves the mood** coherently (atmosphere arc)

This transforms video sequences from static repetitions into dynamic, professionally-paced narratives that follow Hollywood storytelling principles.
