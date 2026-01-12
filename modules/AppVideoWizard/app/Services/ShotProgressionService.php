<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * ShotProgressionService - Phase 6 Shot Progression Intelligence
 *
 * Leverages existing wizard configuration:
 * - Tension Curves → Energy levels per shot (1-10 scale)
 * - Emotional Journeys → Mood progression per shot
 * - Story Arcs → Beat assignment by position
 *
 * Adds NEW validation:
 * - Action progression (prevents identical consecutive shots)
 * - Causality checking (THEREFORE/BUT logic)
 * - Prompt enhancement with progression context
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
     * Position ranges represent where in the sequence (0.0 = start, 1.0 = end)
     * a beat type is most appropriate.
     */
    public const BEAT_TYPES = [
        'establishing' => [
            'position' => [0.0, 0.15],
            'function' => 'Sets up scene, introduces status quo',
            'keywords' => ['setting the scene', 'establishing context'],
        ],
        'discovery' => [
            'position' => [0.10, 0.35],
            'function' => 'Character notices or realizes something',
            'keywords' => ['moment of realization', 'awareness dawns'],
        ],
        'rising' => [
            'position' => [0.20, 0.50],
            'function' => 'Action or tension builds',
            'keywords' => ['building momentum', 'increasing tension'],
        ],
        'decision' => [
            'position' => [0.35, 0.55],
            'function' => 'Character makes a choice or commitment',
            'keywords' => ['decisive moment', 'point of commitment'],
        ],
        'escalation' => [
            'position' => [0.45, 0.70],
            'function' => 'Stakes increase, tension heightens',
            'keywords' => ['rising stakes', 'intensifying conflict'],
        ],
        'climax' => [
            'position' => [0.65, 0.85],
            'function' => 'Peak intensity, main confrontation',
            'keywords' => ['peak dramatic moment', 'climactic intensity'],
        ],
        'reaction' => [
            'position' => [0.50, 0.90],
            'function' => 'Response to an event or revelation',
            'keywords' => ['emotional response', 'character reaction'],
        ],
        'resolution' => [
            'position' => [0.80, 1.00],
            'function' => 'Conflict resolved, closure achieved',
            'keywords' => ['resolution and closure', 'narrative completion'],
        ],
    ];

    /**
     * Action continuity types for classification.
     */
    public const CONTINUITY_TYPES = [
        'scene_start' => 'Opening shot of sequence',
        'continuous' => 'Same action continues from previous',
        'develops' => 'Action progresses/evolves naturally',
        'reaction' => 'Response to previous shot action',
        'contrast' => 'Deliberately different for effect',
        'static' => 'No meaningful change (PROBLEM)',
    ];

    /**
     * Analyze and enrich shots with progression data.
     *
     * @param array $shots Shot array from ShotIntelligenceService
     * @param array $context Scene context including tensionCurve, emotionalJourney, etc.
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

        if ($totalShots === 0) {
            return [
                'enabled' => true,
                'shots' => [],
                'progressionScore' => 100,
                'issues' => [],
                'suggestions' => [],
            ];
        }

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
                'positionPercent' => round($position * 100),
            ];

            // 6. Generate prompt enhancement
            if ($this->isPromptEnhancementEnabled()) {
                $shot['progressionPrompt'] = $this->buildProgressionPrompt($shot['progression']);
            }

            $enrichedShots[] = $shot;
            $previousShot = $shot;
        }

        // Calculate overall progression score
        $score = $this->calculateProgressionScore($enrichedShots, $issues);

        // Build atmosphere arc summary
        $atmosphereArc = $this->buildAtmosphereArc($enrichedShots, $tensionCurve, $emotionalJourney);

        Log::info('ShotProgressionService: Analyzed progression', [
            'shot_count' => $totalShots,
            'tension_curve' => $tensionCurve,
            'emotional_journey' => $emotionalJourney,
            'score' => $score,
            'issues_count' => count($issues),
        ]);

        return [
            'enabled' => true,
            'shots' => $enrichedShots,
            'progressionScore' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'atmosphereArc' => $atmosphereArc,
        ];
    }

    /**
     * Map tension curve to energy level (1-10) for a given position.
     *
     * Uses existing config: config('appvideowizard.tension_curves')
     *
     * @param string $curveId Tension curve slug (e.g., 'rollercoaster')
     * @param float $position Position in sequence (0.0 to 1.0)
     * @return array Energy data with level, raw value, and description
     */
    protected function mapTensionCurveToEnergy(string $curveId, float $position): array
    {
        $curves = $this->getTensionCurves();
        $curve = $curves[$curveId] ?? $curves['steady-build'] ?? null;

        // Default curve if none found
        $curveData = $curve['curve'] ?? [10, 20, 30, 40, 50, 60, 70, 80, 90, 95];

        // Clamp position to valid range
        $position = max(0, min(1, $position));

        // Interpolate curve value at position
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
            'curveId' => $curveId,
        ];
    }

    /**
     * Map emotional journey to mood for a given position.
     *
     * Uses existing config: config('appvideowizard.emotional_journeys')
     *
     * @param string $journeyId Emotional journey slug (e.g., 'triumph')
     * @param float $position Position in sequence (0.0 to 1.0)
     * @return array Mood data with current emotion, shift type, and keywords
     */
    protected function mapEmotionalJourneyToMood(string $journeyId, float $position): array
    {
        $journeys = $this->getEmotionalJourneys();
        $journey = $journeys[$journeyId] ?? $journeys['triumph'] ?? null;

        // Default emotion arc if none found
        $emotionArc = $journey['emotionArc'] ?? ['neutral'];
        $endFeeling = $journey['endFeeling'] ?? 'neutral';

        // Clamp position and map to arc index
        $position = max(0, min(1, $position));
        $arcIndex = (int) floor($position * (count($emotionArc) - 1));
        $arcIndex = min($arcIndex, count($emotionArc) - 1);

        $currentEmotion = $emotionArc[$arcIndex] ?? 'neutral';

        // Determine mood shift from previous position
        $prevIndex = max(0, $arcIndex - 1);
        $prevEmotion = $emotionArc[$prevIndex] ?? $currentEmotion;

        $moodShift = 'maintains';
        if ($prevEmotion !== $currentEmotion) {
            $moodShift = 'shifts';
        }

        return [
            'current' => $currentEmotion,
            'previous' => $prevEmotion,
            'shift' => $moodShift,
            'endFeeling' => $endFeeling,
            'keywords' => $this->getMoodKeywords($currentEmotion),
            'journeyId' => $journeyId,
        ];
    }

    /**
     * Assign story beat based on position, shot type, and context.
     *
     * @param float $position Position in sequence (0.0 to 1.0)
     * @param array $shot Shot data
     * @param array $context Scene context
     * @return array Story beat data with type, function, and confidence
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

            // Check if position is within this beat's range
            if ($position >= $range[0] && $position <= $range[1]) {
                // Calculate fit score (center of range = higher score)
                $center = ($range[0] + $range[1]) / 2;
                $rangeSize = $range[1] - $range[0];
                $distance = abs($position - $center);
                $score = 1 - ($distance / ($rangeSize / 2 + 0.01)); // Avoid division by zero

                // Boost for shot type that matches beat
                if ($this->shotTypeMatchesBeat($shotType, $beatType)) {
                    $score += 0.25;
                }

                // Boost for scene type that matches beat
                if ($this->sceneTypeMatchesBeat($sceneType, $beatType)) {
                    $score += 0.15;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestBeat = $beatType;
                }
            }
        }

        $beatInfo = self::BEAT_TYPES[$bestBeat] ?? [];

        return [
            'type' => $bestBeat,
            'function' => $beatInfo['function'] ?? 'Advances the narrative',
            'confidence' => round(min(1, $bestScore), 2),
            'keywords' => $beatInfo['keywords'] ?? [],
        ];
    }

    /**
     * Check if shot type naturally matches a beat type.
     */
    protected function shotTypeMatchesBeat(string $shotType, string $beatType): bool
    {
        $matches = [
            'establishing' => ['wide', 'extreme-wide', 'establishing', 'aerial', 'medium-wide'],
            'discovery' => ['close-up', 'medium-close-up', 'pov', 'insert'],
            'rising' => ['medium', 'medium-wide', 'tracking'],
            'decision' => ['close-up', 'medium', 'reaction', 'medium-close-up'],
            'escalation' => ['medium', 'close-up', 'tracking', 'dynamic'],
            'climax' => ['close-up', 'extreme-close-up', 'dynamic', 'reaction'],
            'reaction' => ['close-up', 'reaction', 'medium-close-up', 'insert'],
            'resolution' => ['medium', 'wide', 'two-shot', 'medium-wide'],
        ];

        $matchingShotTypes = $matches[$beatType] ?? [];
        return in_array($shotType, $matchingShotTypes);
    }

    /**
     * Check if scene type naturally matches a beat type.
     */
    protected function sceneTypeMatchesBeat(string $sceneType, string $beatType): bool
    {
        $matches = [
            'dialogue' => ['establishing', 'reaction', 'decision'],
            'action' => ['escalation', 'climax', 'rising'],
            'emotional' => ['discovery', 'reaction', 'resolution'],
            'establishing' => ['establishing'],
            'montage' => ['rising', 'escalation'],
        ];

        $matchingBeats = $matches[$sceneType] ?? [];
        return in_array($beatType, $matchingBeats);
    }

    /**
     * CORE NEW FEATURE: Validate action progression between shots.
     * Detects identical/static shots that lack narrative progression.
     *
     * @param array $shot Current shot
     * @param array|null $previousShot Previous shot (null if first)
     * @param int $index Current shot index
     * @return array Validation result with continuity type and any issues
     */
    protected function validateActionProgression(array $shot, ?array $previousShot, int $index): array
    {
        // First shot has no previous to compare
        if (!$previousShot) {
            return [
                'hasIssue' => false,
                'continuityType' => 'scene_start',
                'similarity' => 0,
            ];
        }

        // Skip validation if disabled
        if (!$this->isActionContinuityEnabled()) {
            return [
                'hasIssue' => false,
                'continuityType' => 'develops',
                'similarity' => 0,
            ];
        }

        // Get action descriptions
        $prevAction = $previousShot['subjectAction'] ?? $previousShot['action'] ?? '';
        $currAction = $shot['subjectAction'] ?? $shot['action'] ?? '';

        // Skip if either action is empty
        if (empty($prevAction) || empty($currAction)) {
            return [
                'hasIssue' => false,
                'continuityType' => 'develops',
                'similarity' => 0,
            ];
        }

        // Calculate similarity
        $similarity = $this->calculateTextSimilarity($prevAction, $currAction);
        $threshold = $this->getSimilarityThreshold() / 100;

        // Check for problematic similarity
        if ($similarity > $threshold && $this->isFlagStaticShotsEnabled()) {
            $shotNum = $index + 1;
            $prevShotNum = $index;

            return [
                'hasIssue' => true,
                'continuityType' => 'static',
                'similarity' => round($similarity * 100),
                'issue' => [
                    'type' => 'identical_action',
                    'position' => $index,
                    'severity' => $similarity > 0.95 ? 'high' : 'medium',
                    'message' => "Shot {$shotNum} has " . round($similarity * 100) . "% similar action to shot {$prevShotNum}",
                    'prevAction' => $this->truncate($prevAction, 60),
                    'currAction' => $this->truncate($currAction, 60),
                ],
                'suggestion' => [
                    'position' => $index,
                    'message' => $this->generateActionSuggestion($prevAction, $currAction, $shot),
                ],
            ];
        }

        // Determine continuity type for non-problematic shots
        $continuityType = $this->inferContinuityType($prevAction, $currAction);

        return [
            'hasIssue' => false,
            'continuityType' => $continuityType,
            'similarity' => round($similarity * 100),
        ];
    }

    /**
     * Calculate text similarity using Jaccard index on words.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0.0 to 1.0)
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

        // Tokenize
        $words1 = array_unique(preg_split('/[\s,\.]+/', $text1));
        $words2 = array_unique(preg_split('/[\s,\.]+/', $text2));

        // Remove common filler/stop words
        $stopwords = [
            'the', 'a', 'an', 'in', 'on', 'at', 'to', 'is', 'are', 'with',
            'and', 'or', 'of', 'for', 'by', 'as', 'this', 'that', 'their', 'its',
        ];
        $words1 = array_diff($words1, $stopwords);
        $words2 = array_diff($words2, $stopwords);

        // Remove empty strings
        $words1 = array_filter($words1);
        $words2 = array_filter($words2);

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        // Jaccard similarity
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Infer the type of action continuity between shots.
     */
    protected function inferContinuityType(string $prevAction, string $currAction): string
    {
        $currLower = strtolower($currAction);

        // Check for explicit reaction words
        $reactionWords = ['reacts', 'responds', 'notices', 'sees', 'hears', 'realizes', 'turns to', 'looks at'];
        foreach ($reactionWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'reaction';
            }
        }

        // Check for continuation words
        $continuationWords = ['continues', 'keeps', 'still', 'remains', 'ongoing'];
        foreach ($continuationWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'continuous';
            }
        }

        // Check for state change words (indicates development)
        $stateChangeWords = [
            'rises', 'stands', 'sits', 'turns', 'moves', 'walks', 'runs',
            'opens', 'closes', 'reaches', 'grabs', 'releases', 'steps',
        ];
        foreach ($stateChangeWords as $word) {
            if (strpos($currLower, $word) !== false) {
                return 'develops';
            }
        }

        return 'develops';
    }

    /**
     * Generate a helpful suggestion for fixing static/identical action.
     */
    protected function generateActionSuggestion(string $prevAction, string $currAction, array $shot): string
    {
        $shotType = $shot['type'] ?? 'medium';
        $beatType = $shot['progression']['storyBeat']['type'] ?? 'action';

        // Shot-type specific suggestions
        $shotSuggestions = [
            'close-up' => [
                "Add facial expression change: 'expression shifts from X to Y'",
                "Add eye movement: 'gaze moves toward...'",
                "Add subtle reaction: 'brow furrows' or 'eyes widen'",
            ],
            'extreme-close-up' => [
                "Focus on micro-expression: 'lip trembles' or 'pupil dilates'",
                "Show detail change: 'tear forms' or 'muscle tenses'",
            ],
            'medium' => [
                "Add body movement: 'leans forward' or 'shifts weight'",
                "Add gesture: 'hand reaches toward...' or 'arms cross'",
                "Add state change: 'rises from seated position'",
            ],
            'wide' => [
                "Add positional change: 'moves toward...' or 'steps back'",
                "Add environmental interaction: 'approaches the door'",
                "Show relationship shift: 'distance between them grows'",
            ],
            'reaction' => [
                "Specify the reaction: 'steps back in surprise'",
                "Add emotional response: 'face falls with disappointment'",
            ],
        ];

        // Beat-type specific suggestions
        $beatSuggestions = [
            'discovery' => "Show realization: 'eyes widen as understanding dawns'",
            'decision' => "Show resolve: 'jaw sets with determination'",
            'reaction' => "Show response: 'flinches' or 'freezes in place'",
            'climax' => "Show peak action: 'springs into motion' or 'confronts directly'",
            'resolution' => "Show conclusion: 'shoulders relax' or 'releases held breath'",
        ];

        // Build suggestion
        $suggestions = $shotSuggestions[$shotType] ?? $shotSuggestions['medium'];
        $mainSuggestion = $suggestions[array_rand($suggestions)];

        if (isset($beatSuggestions[$beatType])) {
            return "For this {$beatType} beat: " . $beatSuggestions[$beatType] . " OR " . $mainSuggestion;
        }

        return "Add progression: " . $mainSuggestion;
    }

    /**
     * Build prompt enhancement string from progression data.
     */
    protected function buildProgressionPrompt(array $progression): string
    {
        $parts = [];

        // Energy descriptor
        $energy = $progression['energy']['level'] ?? 5;
        $energyPhrases = [
            1 => 'very calm, still atmosphere',
            2 => 'relaxed, quiet mood',
            3 => 'gentle, subdued energy',
            4 => 'moderate pace',
            5 => 'balanced tension',
            6 => 'engaged, active mood',
            7 => 'building intensity',
            8 => 'dramatic tension',
            9 => 'climactic energy',
            10 => 'peak dramatic intensity',
        ];
        $parts[] = $energyPhrases[$energy] ?? 'balanced tension';

        // Mood keywords (take first 2)
        $moodKeywords = $progression['mood']['keywords'] ?? [];
        if (!empty($moodKeywords)) {
            $parts[] = implode(', ', array_slice($moodKeywords, 0, 2));
        }

        // Beat context
        $beatType = $progression['storyBeat']['type'] ?? '';
        $beatKeywords = $progression['storyBeat']['keywords'] ?? [];
        if (!empty($beatKeywords)) {
            $parts[] = $beatKeywords[0];
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Build atmosphere arc summary for the sequence.
     */
    protected function buildAtmosphereArc(array $shots, string $tensionCurve, string $emotionalJourney): array
    {
        $energyLevels = [];
        $moods = [];

        foreach ($shots as $shot) {
            $progression = $shot['progression'] ?? [];
            $energyLevels[] = $progression['energy']['level'] ?? 5;
            $moods[] = $progression['mood']['current'] ?? 'neutral';
        }

        $peakEnergy = !empty($energyLevels) ? max($energyLevels) : 5;
        $peakPosition = !empty($energyLevels) ? array_search($peakEnergy, $energyLevels) : 0;

        return [
            'tensionCurve' => $tensionCurve,
            'emotionalJourney' => $emotionalJourney,
            'energyLevels' => $energyLevels,
            'moods' => $moods,
            'startEnergy' => $energyLevels[0] ?? 5,
            'peakEnergy' => $peakEnergy,
            'peakPosition' => $peakPosition,
            'endEnergy' => end($energyLevels) ?: 5,
            'pattern' => $this->identifyArcPattern($energyLevels),
        ];
    }

    /**
     * Identify the narrative arc pattern from energy levels.
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

        // Analyze pattern
        if ($peak - $start < 2 && $peak - $end < 2) {
            return 'flat';
        }
        if ($peakPos > 0.7 && $end >= $peak - 1) {
            return 'rising_climax';
        }
        if ($peakPos < 0.3 && $start >= $peak - 1) {
            return 'front_loaded';
        }
        if ($peakPos > 0.4 && $peakPos < 0.7) {
            return 'classic_arc';
        }
        if ($end < $start - 2) {
            return 'descending';
        }

        return 'variable';
    }

    /**
     * Calculate overall progression score for the sequence.
     */
    protected function calculateProgressionScore(array $shots, array $issues): int
    {
        $baseScore = 85;

        // Penalize for issues by severity
        $highSeverity = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'high'));
        $mediumSeverity = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'medium'));
        $lowSeverity = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'low'));

        $penalty = ($highSeverity * 15) + ($mediumSeverity * 8) + ($lowSeverity * 3);

        // Bonus for good beat distribution
        $beats = [];
        foreach ($shots as $shot) {
            $beatType = $shot['progression']['storyBeat']['type'] ?? null;
            if ($beatType) {
                $beats[] = $beatType;
            }
        }
        $uniqueBeats = count(array_unique($beats));
        $beatBonus = min(10, $uniqueBeats * 2);

        // Bonus for good energy variation
        $energyLevels = [];
        foreach ($shots as $shot) {
            $energyLevels[] = $shot['progression']['energy']['level'] ?? 5;
        }
        $energyVariation = count($energyLevels) > 1
            ? max($energyLevels) - min($energyLevels)
            : 0;
        $variationBonus = min(5, $energyVariation);

        $finalScore = $baseScore - $penalty + $beatBonus + $variationBonus;

        return max(0, min(100, (int) $finalScore));
    }

    // ====================
    // HELPER METHODS
    // ====================

    /**
     * Get energy level description.
     */
    protected function getEnergyDescription(int $level): string
    {
        $descriptions = [
            1 => 'very calm',
            2 => 'relaxed',
            3 => 'gentle',
            4 => 'moderate',
            5 => 'balanced',
            6 => 'engaged',
            7 => 'intense',
            8 => 'dramatic',
            9 => 'climactic',
            10 => 'peak intensity',
        ];
        return $descriptions[$level] ?? 'moderate';
    }

    /**
     * Get mood keywords for an emotion.
     */
    protected function getMoodKeywords(string $emotion): array
    {
        $keywords = [
            // Triumph journey
            'doubt' => ['uncertain', 'hesitant', 'questioning'],
            'hope' => ['optimistic', 'anticipating', 'brightening'],
            'setback' => ['challenged', 'struggling', 'tested'],
            'determination' => ['focused', 'resolute', 'committed'],
            'breakthrough' => ['triumphant', 'victorious', 'achieving'],
            'celebration' => ['joyful', 'exuberant', 'celebrating'],

            // Thriller journey
            'intrigue' => ['curious', 'intrigued', 'questioning'],
            'unease' => ['uneasy', 'unsettled', 'wary'],
            'suspicion' => ['suspicious', 'alert', 'guarded'],
            'fear' => ['fearful', 'tense', 'anxious'],
            'revelation' => ['shocked', 'stunned', 'reeling'],
            'shock' => ['horrified', 'overwhelmed', 'shaken'],

            // Horror journey
            'normalcy' => ['calm', 'ordinary', 'routine'],
            'dread' => ['ominous', 'foreboding', 'creeping fear'],
            'terror' => ['terrifying', 'horrifying', 'nightmarish'],
            'survival' => ['desperate', 'fighting', 'clinging'],
            'lingering_fear' => ['haunted', 'unsettled', 'changed'],

            // Educational journey
            'curiosity' => ['curious', 'interested', 'engaged'],
            'confusion' => ['puzzled', 'questioning', 'uncertain'],
            'understanding' => ['clarifying', 'connecting', 'grasping'],
            'application' => ['applying', 'practicing', 'testing'],
            'mastery' => ['confident', 'capable', 'skilled'],
            'confidence' => ['assured', 'competent', 'empowered'],

            // General emotions
            'peace' => ['serene', 'tranquil', 'calm'],
            'longing' => ['wistful', 'yearning', 'nostalgic'],
            'pride' => ['proud', 'accomplished', 'satisfied'],
            'shame' => ['ashamed', 'regretful', 'humbled'],
            'reflection' => ['contemplative', 'thoughtful', 'introspective'],
            'change' => ['transforming', 'shifting', 'evolving'],
            'redemption' => ['redeemed', 'renewed', 'restored'],
            'neutral' => ['calm', 'steady'],
        ];

        return $keywords[$emotion] ?? ['emotional', $emotion];
    }

    /**
     * Get tension curves from config (cached).
     */
    protected function getTensionCurves(): array
    {
        if ($this->tensionCurves === null) {
            $this->tensionCurves = config('appvideowizard.tension_curves', [
                'steady-build' => [
                    'curve' => [10, 20, 30, 40, 50, 60, 70, 80, 90, 95],
                ],
            ]);
        }
        return $this->tensionCurves;
    }

    /**
     * Get emotional journeys from config (cached).
     */
    protected function getEmotionalJourneys(): array
    {
        if ($this->emotionalJourneys === null) {
            $this->emotionalJourneys = config('appvideowizard.emotional_journeys', [
                'triumph' => [
                    'emotionArc' => ['doubt', 'hope', 'setback', 'determination', 'breakthrough', 'celebration'],
                    'endFeeling' => 'empowered',
                ],
            ]);
        }
        return $this->emotionalJourneys;
    }

    /**
     * Truncate text to specified length.
     */
    protected function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    // ====================
    // SETTINGS METHODS
    // ====================

    /**
     * Check if Shot Progression Intelligence is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_enabled', true);
    }

    /**
     * Check if action continuity validation is enabled.
     */
    public function isActionContinuityEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_action_continuity', true);
    }

    /**
     * Check if flagging static shots is enabled.
     */
    public function isFlagStaticShotsEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_flag_static', true);
    }

    /**
     * Check if prompt enhancement is enabled.
     */
    public function isPromptEnhancementEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_progression_enhance_prompts', true);
    }

    /**
     * Get minimum progression score threshold.
     */
    public function getMinProgressionScore(): int
    {
        return (int) VwSetting::getValue('shot_progression_min_score', 60);
    }

    /**
     * Get similarity threshold for flagging identical actions (0-100).
     */
    public function getSimilarityThreshold(): int
    {
        return (int) VwSetting::getValue('shot_progression_similarity_threshold', 85);
    }

    /**
     * Validate action progression between two action strings.
     * Public interface for checking if consecutive actions are too similar.
     *
     * @param string $currentAction Current shot's action description
     * @param string $previousAction Previous shot's action description
     * @return array Validation result with valid, similarity, message, and suggestion
     */
    public function validateActionStrings(string $currentAction, string $previousAction): array
    {
        // Skip if either action is empty
        if (empty($previousAction) || empty($currentAction)) {
            return [
                'valid' => true,
                'similarity' => 0,
            ];
        }

        // Skip validation if disabled
        if (!$this->isActionContinuityEnabled()) {
            return [
                'valid' => true,
                'similarity' => 0,
            ];
        }

        // Calculate similarity
        $similarity = $this->calculateTextSimilarity($previousAction, $currentAction);
        $threshold = $this->getSimilarityThreshold() / 100;

        // Check for problematic similarity
        if ($similarity > $threshold && $this->isFlagStaticShotsEnabled()) {
            return [
                'valid' => false,
                'similarity' => round($similarity * 100),
                'message' => 'Consecutive shots have ' . round($similarity * 100) . '% similar actions',
                'suggestion' => $this->generateActionSuggestion($previousAction, $currentAction, []),
            ];
        }

        return [
            'valid' => true,
            'similarity' => round($similarity * 100),
        ];
    }
}
