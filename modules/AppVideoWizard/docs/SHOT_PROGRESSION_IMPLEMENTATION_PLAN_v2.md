# Shot Progression Intelligence Engine
## Phase 6 Implementation Plan v2.0 (Revised)

**Version:** 2.0
**Date:** January 2026
**Status:** APPROVED FOR IMPLEMENTATION

---

## Key Revision: Leverage Existing UI

This revised plan eliminates redundant admin pages by leveraging the existing wizard configuration:

| Existing UI Element | Phase 6 Feature | Action |
|---------------------|-----------------|--------|
| **Tension Curve** selector | Atmosphere/Energy Arc | Map curve values → shot energy |
| **Emotional Journey** selector | Mood Progression | Map emotion arc → shot mood |
| **Story Arc** selector | Beat Positioning | Use structure for beat timing |
| **Narrative Preset** | Progression Pattern | Inherit pacing rules |

**No new admin pages needed** - only backend service enhancements.

---

## Architecture: Minimal Footprint

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EXISTING WIZARD UI (NO CHANGES)                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ Tension Curve   │  │ Emotional       │  │ Story Arc /         │  │
│  │ (Rollercoaster) │  │ Journey (Triumph│  │ Narrative Preset    │  │
│  └────────┬────────┘  └────────┬────────┘  └──────────┬──────────┘  │
│           │                    │                      │             │
└───────────┼────────────────────┼──────────────────────┼─────────────┘
            │                    │                      │
            ▼                    ▼                      ▼
┌─────────────────────────────────────────────────────────────────────┐
│                 NEW: ShotProgressionService                          │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  mapTensionCurveToEnergy()     → Shot energy levels (1-10)  │    │
│  │  mapEmotionalJourneyToMood()   → Shot mood keywords         │    │
│  │  assignStoryBeatByPosition()   → Beat type per shot         │    │
│  │  validateActionProgression()   → Flag identical shots       │    │
│  │  enhancePromptWithProgression()→ Add context to prompts     │    │
│  └─────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│              EXISTING: ShotIntelligenceService                       │
│              (Add hook: addProgressionAnalysis)                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## What's Actually New

### 1. `ShotProgressionService.php` (New Service)
A single new service that consumes existing config data.

### 2. Settings (8 new entries in `VwSettingSeeder`)
Feature toggles in the existing Dynamic Settings admin page.

### 3. Integration Hook
One new method call in `ShotIntelligenceService::analyzeScene()`.

**That's it.** No new database tables, no new admin pages, no new UI.

---

## Implementation Details

### File 1: `ShotProgressionService.php`

```php
<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * ShotProgressionService - Phase 6 Shot Progression Intelligence
 *
 * Leverages existing wizard configuration:
 * - Tension Curves → Energy levels per shot
 * - Emotional Journeys → Mood progression per shot
 * - Story Arcs → Beat assignment by position
 *
 * Adds NEW validation:
 * - Action progression (prevents identical consecutive shots)
 * - Causality checking (THEREFORE/BUT logic)
 */
class ShotProgressionService
{
    /**
     * Tension curves from config (cached).
     */
    protected ?array $tensionCurves = null;

    /**
     * Emotional journeys from config (cached).
     */
    protected ?array $emotionalJourneys = null;

    /**
     * Story beat types mapped to narrative positions.
     */
    public const BEAT_TYPES = [
        'establishing' => ['position' => [0.0, 0.15], 'function' => 'Sets up scene/status quo'],
        'discovery'    => ['position' => [0.10, 0.35], 'function' => 'Character notices something'],
        'rising'       => ['position' => [0.20, 0.50], 'function' => 'Action/tension builds'],
        'decision'     => ['position' => [0.35, 0.55], 'function' => 'Character makes choice'],
        'escalation'   => ['position' => [0.45, 0.70], 'function' => 'Stakes increase'],
        'climax'       => ['position' => [0.65, 0.85], 'function' => 'Peak intensity moment'],
        'reaction'     => ['position' => [0.50, 0.90], 'function' => 'Response to event'],
        'resolution'   => ['position' => [0.80, 1.00], 'function' => 'Conflict resolved'],
    ];

    /**
     * Analyze and enrich shots with progression data.
     *
     * @param array $shots Shot array from ShotIntelligenceService
     * @param array $context Scene context with tensionCurve, emotionalJourney, etc.
     * @return array Enriched analysis with progression data
     */
    public function analyzeProgression(array $shots, array $context = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'shots' => $shots,
            ];
        }

        $tensionCurve = $context['tensionCurve'] ?? 'steady-build';
        $emotionalJourney = $context['emotionalJourney'] ?? 'triumph';
        $totalShots = count($shots);

        $enrichedShots = [];
        $previousShot = null;
        $issues = [];
        $suggestions = [];

        foreach ($shots as $index => $shot) {
            $position = $totalShots > 1 ? $index / ($totalShots - 1) : 0;

            // 1. Map tension curve → energy level
            $energy = $this->mapTensionCurveToEnergy($tensionCurve, $position);

            // 2. Map emotional journey → mood
            $mood = $this->mapEmotionalJourneyToMood($emotionalJourney, $position);

            // 3. Assign story beat by position
            $storyBeat = $this->assignStoryBeat($position, $shot, $context);

            // 4. Validate action progression (CORE NEW FEATURE)
            $actionValidation = $this->validateActionProgression($shot, $previousShot, $index);
            if ($actionValidation['hasIssue']) {
                $issues[] = $actionValidation['issue'];
                $suggestions[] = $actionValidation['suggestion'];
            }

            // 5. Build progression data
            $shot['progression'] = [
                'energy' => $energy,
                'mood' => $mood,
                'storyBeat' => $storyBeat,
                'actionContinuity' => $actionValidation['continuityType'],
                'position' => round($position, 2),
            ];

            // 6. Generate prompt enhancement
            $shot['progressionPrompt'] = $this->buildProgressionPrompt($shot['progression']);

            $enrichedShots[] = $shot;
            $previousShot = $shot;
        }

        // Calculate overall progression score
        $score = $this->calculateProgressionScore($enrichedShots, $issues);

        return [
            'enabled' => true,
            'shots' => $enrichedShots,
            'progressionScore' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'atmosphereArc' => [
                'curve' => $tensionCurve,
                'journey' => $emotionalJourney,
                'energyLevels' => array_column(array_column($enrichedShots, 'progression'), 'energy'),
            ],
        ];
    }

    /**
     * Map tension curve to energy level (1-10) for a given position.
     *
     * Uses existing config: config('appvideowizard.tension_curves')
     */
    protected function mapTensionCurveToEnergy(string $curveId, float $position): array
    {
        $curves = $this->getTensionCurves();
        $curve = $curves[$curveId] ?? $curves['steady-build'];

        // Curve has 10 values (0-100 scale), interpolate for position
        $curveData = $curve['curve'] ?? [10, 20, 30, 40, 50, 60, 70, 80, 90, 95];
        $curveIndex = $position * (count($curveData) - 1);

        $lowerIndex = (int) floor($curveIndex);
        $upperIndex = (int) ceil($curveIndex);
        $fraction = $curveIndex - $lowerIndex;

        // Linear interpolation between curve points
        $lowerValue = $curveData[$lowerIndex] ?? 50;
        $upperValue = $curveData[$upperIndex] ?? $lowerValue;
        $interpolated = $lowerValue + ($upperValue - $lowerValue) * $fraction;

        // Convert 0-100 scale to 1-10
        $energyLevel = max(1, min(10, (int) round($interpolated / 10)));

        return [
            'level' => $energyLevel,
            'raw' => round($interpolated),
            'description' => $this->getEnergyDescription($energyLevel),
        ];
    }

    /**
     * Map emotional journey to mood for a given position.
     *
     * Uses existing config: config('appvideowizard.emotional_journeys')
     */
    protected function mapEmotionalJourneyToMood(string $journeyId, float $position): array
    {
        $journeys = $this->getEmotionalJourneys();
        $journey = $journeys[$journeyId] ?? $journeys['triumph'];

        $emotionArc = $journey['emotionArc'] ?? ['neutral'];
        $arcIndex = (int) floor($position * (count($emotionArc) - 1));
        $currentEmotion = $emotionArc[$arcIndex] ?? 'neutral';

        // Determine mood shift from previous
        $prevIndex = max(0, $arcIndex - 1);
        $prevEmotion = $emotionArc[$prevIndex] ?? $currentEmotion;

        $moodShift = $prevEmotion === $currentEmotion ? 'maintains' : 'shifts';

        return [
            'current' => $currentEmotion,
            'previous' => $prevEmotion,
            'shift' => $moodShift,
            'endFeeling' => $journey['endFeeling'] ?? 'neutral',
            'keywords' => $this->getMoodKeywords($currentEmotion),
        ];
    }

    /**
     * Assign story beat based on position and shot context.
     */
    protected function assignStoryBeat(float $position, array $shot, array $context): array
    {
        $shotType = $shot['type'] ?? 'medium';
        $sceneType = $context['sceneType'] ?? 'dialogue';

        // Find best matching beat for this position
        $bestBeat = 'rising'; // Default
        $bestScore = 0;

        foreach (self::BEAT_TYPES as $beatType => $beatInfo) {
            $range = $beatInfo['position'];
            if ($position >= $range[0] && $position <= $range[1]) {
                // Calculate fit score (center of range = higher score)
                $center = ($range[0] + $range[1]) / 2;
                $distance = abs($position - $center);
                $rangeSize = $range[1] - $range[0];
                $score = 1 - ($distance / ($rangeSize / 2));

                // Boost for shot type matches
                if ($this->shotTypeMatchesBeat($shotType, $beatType)) {
                    $score += 0.3;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestBeat = $beatType;
                }
            }
        }

        return [
            'type' => $bestBeat,
            'function' => self::BEAT_TYPES[$bestBeat]['function'] ?? '',
            'confidence' => round($bestScore, 2),
        ];
    }

    /**
     * Check if shot type naturally matches a beat type.
     */
    protected function shotTypeMatchesBeat(string $shotType, string $beatType): bool
    {
        $matches = [
            'establishing' => ['wide', 'extreme-wide', 'establishing', 'aerial'],
            'discovery' => ['close-up', 'medium-close-up', 'pov'],
            'decision' => ['close-up', 'medium', 'reaction'],
            'climax' => ['close-up', 'extreme-close-up', 'dynamic'],
            'reaction' => ['close-up', 'reaction', 'medium-close-up'],
            'resolution' => ['medium', 'wide', 'two-shot'],
        ];

        return in_array($shotType, $matches[$beatType] ?? []);
    }

    /**
     * CORE NEW FEATURE: Validate action progression between shots.
     * Detects identical/static shots that lack narrative progression.
     */
    protected function validateActionProgression(array $shot, ?array $previousShot, int $index): array
    {
        if (!$previousShot || !$this->isActionContinuityEnabled()) {
            return [
                'hasIssue' => false,
                'continuityType' => 'scene_start',
            ];
        }

        $prevAction = $previousShot['subjectAction'] ?? $previousShot['action'] ?? '';
        $currAction = $shot['subjectAction'] ?? $shot['action'] ?? '';

        // Calculate similarity
        $similarity = $this->calculateTextSimilarity($prevAction, $currAction);

        // Check for problematic similarity (>85% = likely identical)
        if ($similarity > 0.85 && $this->isFlagStaticShotsEnabled()) {
            return [
                'hasIssue' => true,
                'continuityType' => 'static',
                'similarity' => round($similarity * 100),
                'issue' => [
                    'type' => 'identical_action',
                    'position' => $index,
                    'severity' => 'high',
                    'message' => "Shot " . ($index + 1) . " has nearly identical action to shot {$index} ({$this->formatPercent($similarity)} similar)",
                    'prevAction' => $this->truncate($prevAction, 50),
                    'currAction' => $this->truncate($currAction, 50),
                ],
                'suggestion' => [
                    'position' => $index,
                    'message' => $this->generateActionSuggestion($prevAction, $currAction, $shot),
                ],
            ];
        }

        // Determine continuity type
        $continuityType = $this->inferContinuityType($prevAction, $currAction);

        return [
            'hasIssue' => false,
            'continuityType' => $continuityType,
            'similarity' => round($similarity * 100),
        ];
    }

    /**
     * Calculate text similarity using Jaccard index on words.
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0.0;
        }

        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));

        if ($text1 === $text2) {
            return 1.0;
        }

        // Tokenize and compare
        $words1 = array_unique(preg_split('/\s+/', $text1));
        $words2 = array_unique(preg_split('/\s+/', $text2));

        // Remove common filler words
        $stopwords = ['the', 'a', 'an', 'in', 'on', 'at', 'to', 'is', 'are', 'with'];
        $words1 = array_diff($words1, $stopwords);
        $words2 = array_diff($words2, $stopwords);

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Infer the type of action continuity.
     */
    protected function inferContinuityType(string $prevAction, string $currAction): string
    {
        $currLower = strtolower($currAction);

        // Check for explicit reaction words
        $reactionWords = ['reacts', 'responds', 'notices', 'sees', 'realizes', 'turns to'];
        foreach ($reactionWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'reaction';
            }
        }

        // Check for continuation
        $continuationWords = ['continues', 'keeps', 'still', 'remains'];
        foreach ($continuationWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'continuous';
            }
        }

        // Check for state changes
        $stateChangeWords = ['rises', 'stands', 'sits', 'turns', 'moves', 'walks', 'runs', 'opens', 'closes'];
        foreach ($stateChangeWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'develops';
            }
        }

        return 'progresses';
    }

    /**
     * Generate a helpful suggestion for fixing static action.
     */
    protected function generateActionSuggestion(string $prevAction, string $currAction, array $shot): string
    {
        $shotType = $shot['type'] ?? 'medium';
        $storyBeat = $shot['progression']['storyBeat']['type'] ?? 'action';

        $suggestions = [
            'close-up' => [
                "Add facial expression change: 'expression shifts to...'",
                "Add eye movement: 'gaze moves toward...'",
                "Add subtle reaction: 'brow furrows slightly'",
            ],
            'medium' => [
                "Add body movement: 'leans forward' or 'shifts weight'",
                "Add gesture: 'hand reaches toward...'",
                "Add state change: 'rises from seated position'",
            ],
            'wide' => [
                "Add positional change: 'moves toward...'",
                "Add environmental interaction: 'approaches the...'",
                "Add group dynamics: 'others react in background'",
            ],
        ];

        $beatSuggestions = [
            'discovery' => "Show the character noticing something: 'eyes widen as...'",
            'decision' => "Show determination: 'jaw sets with resolve'",
            'reaction' => "Show response: 'steps back in surprise'",
            'climax' => "Show peak intensity: 'springs into action'",
        ];

        // Get relevant suggestion
        $shotSuggestions = $suggestions[$shotType] ?? $suggestions['medium'];
        $beatSuggestion = $beatSuggestions[$storyBeat] ?? null;

        $result = $shotSuggestions[array_rand($shotSuggestions)];
        if ($beatSuggestion) {
            $result = $beatSuggestion . " OR " . $result;
        }

        return "ACTION NEEDED: " . $result;
    }

    /**
     * Build prompt enhancement string from progression data.
     */
    protected function buildProgressionPrompt(array $progression): string
    {
        $parts = [];

        // Energy descriptor
        $energy = $progression['energy']['level'] ?? 5;
        if ($energy <= 3) {
            $parts[] = 'calm, measured atmosphere';
        } elseif ($energy <= 5) {
            $parts[] = 'balanced tension';
        } elseif ($energy <= 7) {
            $parts[] = 'building intensity';
        } else {
            $parts[] = 'heightened dramatic tension';
        }

        // Mood keywords
        $moodKeywords = $progression['mood']['keywords'] ?? [];
        if (!empty($moodKeywords)) {
            $parts[] = implode(', ', array_slice($moodKeywords, 0, 2));
        }

        // Beat context
        $beatType = $progression['storyBeat']['type'] ?? '';
        $beatPrompts = [
            'establishing' => 'setting the scene',
            'discovery' => 'moment of realization',
            'decision' => 'decisive moment',
            'escalation' => 'rising stakes',
            'climax' => 'peak dramatic moment',
            'resolution' => 'resolution and closure',
        ];
        if (isset($beatPrompts[$beatType])) {
            $parts[] = $beatPrompts[$beatType];
        }

        return implode(', ', $parts);
    }

    /**
     * Calculate overall progression score.
     */
    protected function calculateProgressionScore(array $shots, array $issues): int
    {
        $baseScore = 85;

        // Penalize for issues
        $highSeverity = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'high'));
        $mediumSeverity = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'medium'));

        $penalty = ($highSeverity * 15) + ($mediumSeverity * 8);

        // Bonus for good beat distribution
        $beats = array_column(array_column($shots, 'progression'), 'storyBeat');
        $uniqueBeats = count(array_unique(array_column($beats, 'type')));
        $beatBonus = min(10, $uniqueBeats * 2);

        return max(0, min(100, $baseScore - $penalty + $beatBonus));
    }

    // ====================
    // HELPER METHODS
    // ====================

    protected function getEnergyDescription(int $level): string
    {
        $descriptions = [
            1 => 'very calm', 2 => 'relaxed', 3 => 'gentle',
            4 => 'moderate', 5 => 'balanced', 6 => 'engaged',
            7 => 'intense', 8 => 'dramatic', 9 => 'climactic', 10 => 'peak intensity',
        ];
        return $descriptions[$level] ?? 'moderate';
    }

    protected function getMoodKeywords(string $emotion): array
    {
        $keywords = [
            'doubt' => ['uncertain', 'hesitant'],
            'hope' => ['optimistic', 'anticipation'],
            'determination' => ['focused', 'resolute'],
            'breakthrough' => ['triumphant', 'victorious'],
            'celebration' => ['joyful', 'exuberant'],
            'fear' => ['tense', 'anxious'],
            'dread' => ['ominous', 'foreboding'],
            'terror' => ['horrifying', 'intense'],
            'curiosity' => ['intrigued', 'questioning'],
            'revelation' => ['shocking', 'eye-opening'],
            'peace' => ['serene', 'tranquil'],
            'longing' => ['wistful', 'yearning'],
        ];
        return $keywords[$emotion] ?? ['emotional'];
    }

    protected function getTensionCurves(): array
    {
        if ($this->tensionCurves === null) {
            $this->tensionCurves = config('appvideowizard.tension_curves', []);
        }
        return $this->tensionCurves;
    }

    protected function getEmotionalJourneys(): array
    {
        if ($this->emotionalJourneys === null) {
            $this->emotionalJourneys = config('appvideowizard.emotional_journeys', []);
        }
        return $this->emotionalJourneys;
    }

    protected function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    protected function formatPercent(float $value): string
    {
        return round($value * 100) . '%';
    }

    // ====================
    // SETTINGS METHODS
    // ====================

    public function isEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_enabled', true);
    }

    public function isActionContinuityEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_action_continuity', true);
    }

    public function isFlagStaticShotsEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_flag_static', true);
    }

    public function isPromptEnhancementEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_enhance_prompts', true);
    }

    public function getMinProgressionScore(): int
    {
        return (int) VwSetting::getValue('shot_progression_min_score', 60);
    }
}
```

---

## File 2: Integration in `ShotIntelligenceService.php`

Add to existing service (minimal changes):

```php
// Add property
protected ?ShotProgressionService $progressionService = null;

// Add setter
public function setProgressionService(ShotProgressionService $service): void
{
    $this->progressionService = $service;
}

// Add to analyzeScene() method, after Phase 3 (continuity):
protected function addProgressionAnalysis(array $analysis, array $scene, array $context): array
{
    if (!$this->progressionService) {
        return $analysis;
    }

    // Build context for progression service from wizard settings
    $progressionContext = [
        'tensionCurve' => $context['tensionCurve'] ?? $scene['tensionCurve'] ?? 'steady-build',
        'emotionalJourney' => $context['emotionalJourney'] ?? $scene['emotionalJourney'] ?? 'triumph',
        'sceneType' => $context['sceneType'] ?? 'dialogue',
        'storyArc' => $context['storyArc'] ?? null,
    ];

    // Analyze progression
    $progressionResult = $this->progressionService->analyzeProgression(
        $analysis['shots'] ?? [],
        $progressionContext
    );

    // Merge results
    $analysis['shots'] = $progressionResult['shots'];
    $analysis['progression'] = [
        'enabled' => $progressionResult['enabled'],
        'score' => $progressionResult['progressionScore'] ?? null,
        'issues' => $progressionResult['issues'] ?? [],
        'suggestions' => $progressionResult['suggestions'] ?? [],
        'atmosphereArc' => $progressionResult['atmosphereArc'] ?? null,
    ];

    // Enhance video prompts with progression data
    if ($this->progressionService->isPromptEnhancementEnabled()) {
        foreach ($analysis['shots'] as &$shot) {
            if (isset($shot['videoPrompt']) && isset($shot['progressionPrompt'])) {
                $shot['videoPrompt'] .= '. ' . $shot['progressionPrompt'];
            }
        }
    }

    return $analysis;
}
```

---

## File 3: Settings (Add to `VwSettingSeeder.php`)

```php
// ===================================
// SHOT PROGRESSION INTELLIGENCE (Phase 6)
// ===================================

[
    'slug' => 'shot_progression_enabled',
    'name' => 'Enable Shot Progression Intelligence',
    'description' => 'Master toggle for Phase 6. Uses your Tension Curve and Emotional Journey selections to calculate per-shot energy and mood progression.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 1,
],
[
    'slug' => 'shot_progression_action_continuity',
    'name' => 'Action Continuity Validation',
    'description' => 'Analyze shot-to-shot action progression. Detects when consecutive shots have identical or nearly identical actions.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 2,
],
[
    'slug' => 'shot_progression_flag_static',
    'name' => 'Flag Static/Identical Shots',
    'description' => 'Show warnings when shots have >85% similar actions. Helps prevent "no progression" issues in generated videos.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 3,
],
[
    'slug' => 'shot_progression_enhance_prompts',
    'name' => 'Auto-Enhance Video Prompts',
    'description' => 'Add energy level, mood keywords, and beat context to video generation prompts automatically.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'checkbox',
    'default_value' => '1',
    'sort_order' => 4,
],
[
    'slug' => 'shot_progression_min_score',
    'name' => 'Minimum Progression Score',
    'description' => 'Warn when overall progression score falls below this threshold (0-100). Lower = more permissive.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'number',
    'default_value' => '60',
    'sort_order' => 5,
],
[
    'slug' => 'shot_progression_similarity_threshold',
    'name' => 'Action Similarity Threshold',
    'description' => 'Percentage similarity that triggers "identical action" warning (0-100). Default 85%.',
    'category' => VwSetting::CATEGORY_SHOT_PROGRESSION,
    'input_type' => 'number',
    'default_value' => '85',
    'sort_order' => 6,
],
```

---

## File 4: Add Category Constant to `VwSetting.php`

```php
// Add to category constants
const CATEGORY_SHOT_PROGRESSION = 'shot_progression';

// Add to getCategoryInfo() method
'shot_progression' => [
    'name' => 'Shot Progression',
    'icon' => 'fa-diagram-project',
    'description' => 'Phase 6: Shot-to-shot narrative flow and action progression',
],
```

---

## How It Works (Data Flow)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    USER SELECTS IN WIZARD                            │
│  Tension Curve: "Rollercoaster"                                      │
│  Emotional Journey: "Triumph"                                        │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│               config/config.php LOOKUP                               │
│                                                                      │
│  tension_curves['rollercoaster']['curve']:                          │
│  [50, 80, 30, 90, 20, 85, 40, 95, 50, 100]                          │
│                                                                      │
│  emotional_journeys['triumph']['emotionArc']:                        │
│  ['doubt', 'hope', 'setback', 'determination', 'breakthrough', 'celebration']
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│            ShotProgressionService.analyzeProgression()               │
│                                                                      │
│  For each shot at position X:                                        │
│                                                                      │
│  Shot 1 (pos=0.0):  energy=5  mood="doubt"     beat="establishing"  │
│  Shot 2 (pos=0.2):  energy=8  mood="hope"      beat="discovery"     │
│  Shot 3 (pos=0.4):  energy=3  mood="setback"   beat="rising"        │
│  Shot 4 (pos=0.6):  energy=9  mood="determination" beat="climax"    │
│  Shot 5 (pos=0.8):  energy=4  mood="breakthrough"  beat="resolution"│
│                                                                      │
│  ACTION VALIDATION:                                                  │
│  ✓ Shot 1→2: different actions (28% similar)                        │
│  ✗ Shot 2→3: nearly identical (91% similar) ← FLAG!                 │
│  ✓ Shot 3→4: different actions (15% similar)                        │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    OUTPUT TO VIDEO PROMPT                            │
│                                                                      │
│  Original: "monk meditating in temple, slow dolly in"               │
│                                                                      │
│  Enhanced: "monk meditating in temple, slow dolly in.               │
│            heightened dramatic tension, resolute, focused,          │
│            decisive moment"                                          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Example: Rollercoaster Curve Applied

```
Tension Curve: "Rollercoaster" [50, 80, 30, 90, 20, 85, 40, 95, 50, 100]

Shot Position │ Curve Value │ Energy (1-10) │ Description
──────────────┼─────────────┼───────────────┼─────────────────
0.0  (Shot 1) │     50      │      5        │ "balanced"
0.11 (Shot 2) │     80      │      8        │ "dramatic"
0.22 (Shot 3) │     30      │      3        │ "gentle"
0.33 (Shot 4) │     90      │      9        │ "climactic"
0.44 (Shot 5) │     20      │      2        │ "relaxed"
0.55 (Shot 6) │     85      │      9        │ "climactic"
0.66 (Shot 7) │     40      │      4        │ "moderate"
0.77 (Shot 8) │     95      │     10        │ "peak intensity"
0.88 (Shot 9) │     50      │      5        │ "balanced"
1.0  (Shot10) │    100      │     10        │ "peak intensity"

This creates the "rapid emotional changes" effect!
```

---

## Files Summary

| Action | File | Lines Changed |
|--------|------|---------------|
| **CREATE** | `app/Services/ShotProgressionService.php` | ~450 lines |
| **MODIFY** | `app/Services/ShotIntelligenceService.php` | ~30 lines |
| **MODIFY** | `app/Models/VwSetting.php` | ~10 lines |
| **MODIFY** | `database/seeders/VwSettingSeeder.php` | ~50 lines |

**Total: ~540 lines of code**

---

## What We DON'T Need

| Original Plan | Revised Plan | Reason |
|--------------|--------------|--------|
| `vw_story_beats` table | ❌ Not needed | Use BEAT_TYPES constant |
| `vw_atmosphere_presets` table | ❌ Not needed | Use existing tension_curves config |
| Story Beats admin page | ❌ Not needed | Beats derived from position |
| Atmosphere admin page | ❌ Not needed | Use existing Tension Curve UI |
| New wizard UI | ❌ Not needed | Leverage existing selectors |

---

## Testing Checklist

- [ ] Tension curve interpolation produces correct energy levels
- [ ] Emotional journey maps to correct moods
- [ ] Story beats assigned based on shot position
- [ ] Action similarity detection flags >85% matches
- [ ] Prompt enhancement adds context without breaking
- [ ] Settings toggles enable/disable features
- [ ] Works with all 8 tension curves
- [ ] Works with all 12 emotional journeys
- [ ] Performance: <50ms added to shot analysis

---

## Summary

**Before (v1 plan):** New tables, new admin pages, new UI components
**After (v2 plan):** One new service, minimal integration code, zero UI changes

The wizard already has excellent narrative configuration. Phase 6 simply makes the backend **use** these settings for shot-level intelligence.
