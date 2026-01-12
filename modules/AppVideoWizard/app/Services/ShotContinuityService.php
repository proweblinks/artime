<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwCoveragePattern;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwShotType;

/**
 * ShotContinuityService - Manages professional shot sequencing and continuity.
 *
 * Ensures cinematic coherence by:
 * - Enforcing the 30-degree rule for angle changes
 * - Managing shot compatibility (which shots flow well together)
 * - Suggesting coverage patterns (Master → OTS A → OTS B → CU A → CU B)
 * - Maintaining frame chaining continuity between shots
 * - Ensuring smooth camera movement transitions
 *
 * Based on Hollywood editing conventions and professional filmmaking standards.
 */
class ShotContinuityService
{
    protected const CACHE_TTL = 3600;
    protected const CACHE_PREFIX = 'shot_continuity_';

    protected CameraMovementService $cameraMovementService;

    /**
     * Coverage patterns for different scene types.
     * Order matters - shots earlier in pattern should appear first.
     */
    public const COVERAGE_PATTERNS = [
        'dialogue' => [
            'master'           => 1,  // Establishes the scene
            'two-shot'         => 2,  // Both characters
            'over-shoulder'    => 3,  // OTS shots
            'medium'           => 4,  // Individual medium
            'close-up'         => 5,  // Emotional emphasis
            'reaction'         => 6,  // Quick reactions
        ],
        'action' => [
            'establishing'     => 1,  // Sets location
            'wide'             => 2,  // Full action context
            'medium'           => 3,  // Character action
            'tracking'         => 4,  // Following movement
            'close-up'         => 5,  // Detail/impact
            'insert'           => 6,  // Specific detail
        ],
        'montage' => [
            'establishing'     => 1,
            'medium'           => 2,
            'close-up'         => 3,
            'detail'           => 4,
            'cutaway'          => 5,
        ],
        'emotional' => [
            'wide'             => 1,  // Context
            'medium'           => 2,  // Build up
            'close-up'         => 3,  // Peak emotion
            'extreme-close-up' => 4,  // Intimate detail
        ],
        'establishing' => [
            'extreme-wide'     => 1,  // Location overview
            'wide'             => 2,  // Environment
            'medium-wide'      => 3,  // Subject in context
        ],
    ];

    /**
     * Shot compatibility matrix.
     * Defines which shot types transition well to others.
     * Higher scores = smoother transition.
     */
    public const SHOT_COMPATIBILITY = [
        'establishing' => ['wide' => 90, 'extreme-wide' => 85, 'medium-wide' => 80, 'medium' => 70, 'aerial' => 85],
        'extreme-wide' => ['wide' => 90, 'establishing' => 85, 'medium-wide' => 75, 'aerial' => 80],
        'wide' => ['medium-wide' => 90, 'medium' => 85, 'establishing' => 80, 'two-shot' => 80],
        'medium-wide' => ['medium' => 90, 'wide' => 85, 'two-shot' => 85, 'over-shoulder' => 80],
        'medium' => ['medium-close-up' => 90, 'close-up' => 85, 'over-shoulder' => 85, 'two-shot' => 80, 'reaction' => 80],
        'medium-close-up' => ['close-up' => 90, 'medium' => 85, 'reaction' => 80],
        'close-up' => ['extreme-close-up' => 85, 'medium' => 80, 'reaction' => 90, 'insert' => 75, 'close-up' => 70],
        'extreme-close-up' => ['close-up' => 85, 'medium' => 70, 'detail' => 80],
        'over-shoulder' => ['over-shoulder' => 85, 'close-up' => 85, 'medium' => 80, 'two-shot' => 90, 'reaction' => 85],
        'two-shot' => ['over-shoulder' => 90, 'close-up' => 85, 'medium' => 85, 'wide' => 70],
        'reaction' => ['close-up' => 90, 'medium' => 85, 'over-shoulder' => 80],
        'insert' => ['close-up' => 80, 'medium' => 75, 'detail' => 85],
        'detail' => ['close-up' => 80, 'insert' => 85, 'medium' => 70],
        'pov' => ['close-up' => 80, 'medium' => 85, 'reaction' => 90],
        'aerial' => ['wide' => 90, 'establishing' => 85, 'extreme-wide' => 90],
        'low-angle' => ['medium' => 80, 'close-up' => 85, 'wide' => 70],
        'high-angle' => ['medium' => 80, 'close-up' => 85, 'wide' => 75],
        'dutch-angle' => ['medium' => 70, 'close-up' => 75, 'dutch-angle' => 60],
        'tracking' => ['medium' => 85, 'wide' => 80, 'close-up' => 70],
        'master' => ['two-shot' => 90, 'over-shoulder' => 85, 'medium' => 80, 'wide' => 85],
    ];

    /**
     * Movement continuity rules.
     * When previous shot ends with certain state, next shot should consider these.
     */
    public const MOVEMENT_CONTINUITY = [
        'static' => ['static' => 90, 'subtle' => 85, 'moderate' => 70, 'dynamic' => 50],
        'subtle' => ['static' => 85, 'subtle' => 90, 'moderate' => 80, 'dynamic' => 60],
        'moderate' => ['static' => 70, 'subtle' => 80, 'moderate' => 90, 'dynamic' => 75],
        'dynamic' => ['static' => 50, 'subtle' => 60, 'moderate' => 75, 'dynamic' => 90, 'intense' => 80],
        'intense' => ['moderate' => 60, 'dynamic' => 80, 'intense' => 85],
    ];

    public function __construct(CameraMovementService $cameraMovementService)
    {
        $this->cameraMovementService = $cameraMovementService;
    }

    /**
     * Analyze shot sequence for continuity issues.
     *
     * @param array $shots Array of shots with type, movement, etc.
     * @return array Analysis with issues and suggestions
     */
    public function analyzeSequence(array $shots): array
    {
        if (!$this->isContinuityEnabled()) {
            return [
                'enabled' => false,
                'issues' => [],
                'suggestions' => [],
                'score' => 100,
            ];
        }

        $issues = [];
        $suggestions = [];
        $transitionScores = [];

        for ($i = 1; $i < count($shots); $i++) {
            $prevShot = $shots[$i - 1];
            $currShot = $shots[$i];

            // Check shot compatibility
            $compatibility = $this->checkShotCompatibility($prevShot, $currShot);
            if ($compatibility['score'] < $this->getMinCompatibilityScore()) {
                $issues[] = [
                    'type' => 'shot_compatibility',
                    'position' => $i,
                    'message' => "Awkward transition from '{$prevShot['type']}' to '{$currShot['type']}'",
                    'score' => $compatibility['score'],
                    'suggestion' => $compatibility['suggestion'],
                ];
            }
            $transitionScores[] = $compatibility['score'];

            // Check 30-degree rule
            $angleCheck = $this->check30DegreeRule($prevShot, $currShot);
            if (!$angleCheck['valid']) {
                $issues[] = [
                    'type' => '30_degree_rule',
                    'position' => $i,
                    'message' => $angleCheck['message'],
                    'suggestion' => $angleCheck['suggestion'],
                ];
            }

            // Check movement continuity
            $movementCheck = $this->checkMovementContinuity($prevShot, $currShot);
            if ($movementCheck['score'] < 70) {
                $suggestions[] = [
                    'type' => 'movement_continuity',
                    'position' => $i,
                    'message' => $movementCheck['message'],
                    'suggestion' => $movementCheck['suggestion'],
                ];
            }

            // Check for jump cuts
            $jumpCutCheck = $this->checkJumpCut($prevShot, $currShot);
            if ($jumpCutCheck['isJumpCut']) {
                $issues[] = [
                    'type' => 'jump_cut',
                    'position' => $i,
                    'message' => "Potential jump cut between shots {$i} and " . ($i + 1),
                    'suggestion' => $jumpCutCheck['suggestion'],
                ];
            }
        }

        // Calculate overall score
        $avgTransitionScore = !empty($transitionScores)
            ? array_sum($transitionScores) / count($transitionScores)
            : 100;

        $issuePenalty = count($issues) * 10;
        $overallScore = max(0, min(100, $avgTransitionScore - $issuePenalty));

        return [
            'enabled' => true,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'score' => round($overallScore),
            'transitionScores' => $transitionScores,
            'shotCount' => count($shots),
        ];
    }

    /**
     * Check compatibility between two sequential shots.
     */
    public function checkShotCompatibility(array $prevShot, array $currShot): array
    {
        $prevType = $this->normalizeType($prevShot['type'] ?? 'medium');
        $currType = $this->normalizeType($currShot['type'] ?? 'medium');

        // Get compatibility score from matrix
        $compatibilityMap = self::SHOT_COMPATIBILITY[$prevType] ?? [];
        $score = $compatibilityMap[$currType] ?? 50; // Default to mediocre if not in matrix

        // Boost score if types are significantly different (good visual variety)
        if ($this->getShotCategory($prevType) !== $this->getShotCategory($currType)) {
            $score = min(100, $score + 10);
        }

        // Get best alternative if score is low
        $suggestion = null;
        if ($score < 70) {
            $bestAlternative = $this->getBestTransitionShot($prevType);
            if ($bestAlternative && $bestAlternative !== $currType) {
                $suggestion = "Consider using '{$bestAlternative}' for smoother transition";
            }
        }

        return [
            'score' => $score,
            'prevType' => $prevType,
            'currType' => $currType,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * Check 30-degree rule compliance.
     * Camera should move at least 30 degrees between similar shot sizes.
     */
    public function check30DegreeRule(array $prevShot, array $currShot): array
    {
        $prevType = $this->normalizeType($prevShot['type'] ?? 'medium');
        $currType = $this->normalizeType($currShot['type'] ?? 'medium');

        // Rule mainly applies to same or similar shot sizes
        if ($this->getShotSize($prevType) !== $this->getShotSize($currType)) {
            return [
                'valid' => true,
                'message' => 'Different shot sizes - 30-degree rule not applicable',
            ];
        }

        // Check if angle is specified
        $prevAngle = $prevShot['angle'] ?? $prevShot['cameraAngle'] ?? null;
        $currAngle = $currShot['angle'] ?? $currShot['cameraAngle'] ?? null;

        // If both shots have same type and no angle change, potential violation
        if ($prevType === $currType) {
            // Check for camera movement that could create angle change
            $prevMovement = $prevShot['cameraMovement'] ?? '';
            $currMovement = $currShot['cameraMovement'] ?? '';

            $angleChangingMovements = ['pan', 'arc', 'orbit', 'truck', 'dolly'];
            $hasAngleChange = false;

            foreach ($angleChangingMovements as $movement) {
                if (stripos($prevMovement, $movement) !== false ||
                    stripos($currMovement, $movement) !== false) {
                    $hasAngleChange = true;
                    break;
                }
            }

            if (!$hasAngleChange) {
                return [
                    'valid' => false,
                    'message' => "Same shot type '{$prevType}' without apparent angle change",
                    'suggestion' => 'Add camera movement (pan, arc, or truck) or insert a cutaway between these shots',
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Angle change appears sufficient',
        ];
    }

    /**
     * Check movement continuity between shots.
     */
    public function checkMovementContinuity(array $prevShot, array $currShot): array
    {
        // Get movement intensity levels
        $prevIntensity = $prevShot['movementIntensity'] ?? $this->inferIntensity($prevShot);
        $currIntensity = $currShot['movementIntensity'] ?? $this->inferIntensity($currShot);

        // Get continuity score from matrix
        $continuityMap = self::MOVEMENT_CONTINUITY[$prevIntensity] ?? [];
        $score = $continuityMap[$currIntensity] ?? 70;

        $message = null;
        $suggestion = null;

        if ($score < 70) {
            $message = "Abrupt movement change from '{$prevIntensity}' to '{$currIntensity}'";

            // Suggest intermediate intensity
            $intermediates = [
                'static_dynamic' => 'subtle or moderate movement',
                'dynamic_static' => 'moderate movement to ease transition',
                'intense_static' => 'dynamic to moderate transition shot',
            ];

            $key = "{$prevIntensity}_{$currIntensity}";
            $suggestion = $intermediates[$key] ?? "Consider transitional movement between '{$prevIntensity}' and '{$currIntensity}'";
        }

        return [
            'score' => $score,
            'prevIntensity' => $prevIntensity,
            'currIntensity' => $currIntensity,
            'message' => $message,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * Check for potential jump cut.
     * Jump cuts occur when same subject has minor position change.
     */
    public function checkJumpCut(array $prevShot, array $currShot): array
    {
        $prevType = $this->normalizeType($prevShot['type'] ?? 'medium');
        $currType = $this->normalizeType($currShot['type'] ?? 'medium');

        // Jump cuts typically happen with similar shot sizes
        $prevSize = $this->getShotSize($prevType);
        $currSize = $this->getShotSize($currType);

        // If sizes differ significantly, not a jump cut
        if (abs($prevSize - $currSize) > 1) {
            return [
                'isJumpCut' => false,
            ];
        }

        // Same type is highest risk
        $isJumpCut = ($prevType === $currType);

        // Adjacent sizes with same angle can also be jarring
        if (!$isJumpCut && abs($prevSize - $currSize) === 1) {
            // Check if there's significant change (movement, angle)
            $hasSignificantChange = false;

            $prevMovement = $prevShot['cameraMovement'] ?? '';
            $currMovement = $currShot['cameraMovement'] ?? '';

            if ($prevMovement !== $currMovement && !empty($currMovement)) {
                $hasSignificantChange = true;
            }

            if (!$hasSignificantChange) {
                $isJumpCut = true;
            }
        }

        return [
            'isJumpCut' => $isJumpCut,
            'suggestion' => $isJumpCut
                ? 'Insert a cutaway, reaction shot, or change camera angle significantly'
                : null,
        ];
    }

    /**
     * Suggest next shot based on current shot and scene type.
     *
     * @param array $currentShot Current shot data
     * @param string $sceneType Type of scene (dialogue, action, montage, etc.)
     * @param array $usedShots Array of shot types already used in sequence
     * @return array Suggested shots with reasoning
     */
    public function suggestNextShot(array $currentShot, string $sceneType = 'dialogue', array $usedShots = []): array
    {
        $currentType = $this->normalizeType($currentShot['type'] ?? 'medium');
        $pattern = self::COVERAGE_PATTERNS[$sceneType] ?? self::COVERAGE_PATTERNS['dialogue'];

        $suggestions = [];

        // Get compatible shots from matrix
        $compatibilityMap = self::SHOT_COMPATIBILITY[$currentType] ?? [];

        // Sort by compatibility score
        arsort($compatibilityMap);

        foreach ($compatibilityMap as $shotType => $score) {
            if ($score < 70) continue;

            // Prefer shots that fit the coverage pattern
            $patternScore = isset($pattern[$shotType]) ? (10 - $pattern[$shotType]) * 5 : 0;

            // Reduce score for shots already heavily used
            $usageCount = count(array_filter($usedShots, fn($s) => $s === $shotType));
            $usagePenalty = $usageCount * 10;

            $totalScore = $score + $patternScore - $usagePenalty;

            if ($totalScore > 60) {
                $suggestions[] = [
                    'type' => $shotType,
                    'compatibilityScore' => $score,
                    'totalScore' => $totalScore,
                    'reason' => $this->getSuggestionReason($currentType, $shotType, $sceneType),
                ];
            }
        }

        // Sort by total score
        usort($suggestions, fn($a, $b) => $b['totalScore'] <=> $a['totalScore']);

        return array_slice($suggestions, 0, 5);
    }

    /**
     * Get coverage pattern for a scene type.
     */
    public function getCoveragePattern(string $sceneType): array
    {
        // Try to get from database first (Phase 4 patterns)
        $dbPatterns = VwCoveragePattern::getBySceneType($sceneType);
        if (!empty($dbPatterns)) {
            // Use highest priority pattern
            $dbPattern = $dbPatterns[0];
            $sequence = $dbPattern['shot_sequence'] ?? [];

            $result = [];
            foreach ($sequence as $order => $shotType) {
                $shotInfo = VwShotType::getBySlug($shotType);
                $result[] = [
                    'type' => $shotType,
                    'order' => $order + 1,
                    'name' => $shotInfo['name'] ?? ucwords(str_replace('-', ' ', $shotType)),
                    'description' => $shotInfo['description'] ?? '',
                ];
            }

            return $result;
        }

        // Fallback to static patterns
        $pattern = self::COVERAGE_PATTERNS[$sceneType] ?? self::COVERAGE_PATTERNS['dialogue'];

        // Sort by order
        asort($pattern);

        $result = [];
        foreach ($pattern as $shotType => $order) {
            $shotInfo = VwShotType::getBySlug($shotType);
            $result[] = [
                'type' => $shotType,
                'order' => $order,
                'name' => $shotInfo['name'] ?? ucwords(str_replace('-', ' ', $shotType)),
                'description' => $shotInfo['description'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Get coverage pattern with full metadata from database.
     *
     * @param string $sceneType Scene type
     * @return array|null Pattern data with recommendations
     */
    public function getCoveragePatternWithMetadata(string $sceneType): ?array
    {
        $dbPatterns = VwCoveragePattern::getBySceneType($sceneType);
        if (empty($dbPatterns)) {
            return null;
        }

        $pattern = $dbPatterns[0];

        return [
            'slug' => $pattern['slug'],
            'name' => $pattern['name'],
            'sceneType' => $pattern['scene_type'],
            'description' => $pattern['description'] ?? '',
            'shotSequence' => $pattern['shot_sequence'],
            'recommendations' => [
                'pacing' => $pattern['recommended_pacing'],
                'minShots' => $pattern['min_shots'],
                'maxShots' => $pattern['max_shots'],
                'typicalDuration' => $pattern['typical_shot_duration'],
                'movementIntensity' => $pattern['default_movement_intensity'],
                'preferredMovements' => $pattern['preferred_movements'] ?? [],
            ],
            'transitionRules' => $pattern['transition_rules'] ?? [],
        ];
    }

    /**
     * Generate a complete shot sequence suggestion based on scene parameters.
     *
     * @param array $sceneParams Scene parameters (type, duration, characters, etc.)
     * @return array Suggested shot sequence
     */
    public function generateCoverageSequence(array $sceneParams): array
    {
        $sceneType = $sceneParams['type'] ?? 'dialogue';
        $duration = $sceneParams['duration'] ?? 30;
        $hasMultipleCharacters = ($sceneParams['characterCount'] ?? 1) > 1;
        $intensity = $sceneParams['intensity'] ?? 'moderate';

        $pattern = self::COVERAGE_PATTERNS[$sceneType] ?? self::COVERAGE_PATTERNS['dialogue'];
        asort($pattern);

        $sequence = [];
        $shotTypes = array_keys($pattern);

        // Calculate approximate number of shots based on duration
        $avgShotDuration = $intensity === 'dynamic' ? 4 : ($intensity === 'subtle' ? 8 : 6);
        $targetShotCount = max(2, min(10, (int) ceil($duration / $avgShotDuration)));

        // Build sequence following pattern
        $currentIndex = 0;
        for ($i = 0; $i < $targetShotCount; $i++) {
            $shotType = $shotTypes[$currentIndex % count($shotTypes)];

            // Skip two-shot and over-shoulder if single character
            if (!$hasMultipleCharacters && in_array($shotType, ['two-shot', 'over-shoulder'])) {
                $currentIndex++;
                $shotType = $shotTypes[$currentIndex % count($shotTypes)];
            }

            // Get movement suggestion for this shot
            $movement = $this->cameraMovementService->suggestMovement([
                'shotType' => $shotType,
                'sceneType' => $sceneType,
                'isFirstShot' => ($i === 0),
                'previousMovement' => $sequence[$i - 1]['movement']['slug'] ?? null,
            ]);

            $sequence[] = [
                'position' => $i + 1,
                'type' => $shotType,
                'movement' => $movement['movement'] ?? null,
                'suggestedIntensity' => $movement['suggestedIntensity'] ?? $intensity,
                'reason' => $this->getSequencePositionReason($i, $targetShotCount, $sceneType),
            ];

            $currentIndex++;
        }

        return [
            'sceneType' => $sceneType,
            'targetDuration' => $duration,
            'shotCount' => count($sequence),
            'sequence' => $sequence,
            'coverageComplete' => $this->isCoverageComplete($sequence, $pattern),
        ];
    }

    /**
     * Validate a user-provided shot sequence.
     */
    public function validateSequence(array $shots): array
    {
        $analysis = $this->analyzeSequence($shots);

        $validation = [
            'valid' => $analysis['score'] >= 70,
            'score' => $analysis['score'],
            'issues' => $analysis['issues'],
            'suggestions' => $analysis['suggestions'],
        ];

        // Add detailed per-shot feedback
        $shotFeedback = [];
        for ($i = 0; $i < count($shots); $i++) {
            $feedback = [
                'position' => $i + 1,
                'type' => $shots[$i]['type'] ?? 'unknown',
                'status' => 'ok',
            ];

            // Check if this shot has issues
            foreach ($analysis['issues'] as $issue) {
                if ($issue['position'] === $i) {
                    $feedback['status'] = 'warning';
                    $feedback['issue'] = $issue['message'];
                    $feedback['suggestion'] = $issue['suggestion'] ?? null;
                }
            }

            $shotFeedback[] = $feedback;
        }

        $validation['shotFeedback'] = $shotFeedback;

        return $validation;
    }

    /**
     * Optimize an existing shot sequence for better continuity.
     */
    public function optimizeSequence(array $shots, string $sceneType = 'dialogue'): array
    {
        $optimized = $shots;
        $changes = [];

        // First pass: fix major issues
        for ($i = 1; $i < count($optimized); $i++) {
            $compatibility = $this->checkShotCompatibility($optimized[$i - 1], $optimized[$i]);

            if ($compatibility['score'] < 60) {
                // Try to find better intermediate shot
                $bestIntermediate = $this->findBestIntermediateShot(
                    $optimized[$i - 1],
                    $optimized[$i],
                    $sceneType
                );

                if ($bestIntermediate) {
                    // Insert intermediate shot
                    array_splice($optimized, $i, 0, [$bestIntermediate]);
                    $changes[] = [
                        'action' => 'insert',
                        'position' => $i,
                        'shot' => $bestIntermediate,
                        'reason' => "Inserted '{$bestIntermediate['type']}' for smoother transition",
                    ];
                    $i++; // Skip the inserted shot
                }
            }
        }

        // Second pass: add movement continuity
        for ($i = 1; $i < count($optimized); $i++) {
            $movementCheck = $this->checkMovementContinuity($optimized[$i - 1], $optimized[$i]);

            if ($movementCheck['score'] < 60) {
                // Adjust movement intensity
                $suggestedIntensity = $this->getIntermediateIntensity(
                    $optimized[$i - 1]['movementIntensity'] ?? 'moderate',
                    $optimized[$i]['movementIntensity'] ?? 'moderate'
                );

                if ($suggestedIntensity) {
                    $optimized[$i]['suggestedIntensity'] = $suggestedIntensity;
                    $changes[] = [
                        'action' => 'adjust_intensity',
                        'position' => $i,
                        'intensity' => $suggestedIntensity,
                        'reason' => "Adjusted movement to '{$suggestedIntensity}' for smoother flow",
                    ];
                }
            }
        }

        // Re-analyze
        $newAnalysis = $this->analyzeSequence($optimized);

        return [
            'original' => $shots,
            'optimized' => $optimized,
            'changes' => $changes,
            'originalScore' => $this->analyzeSequence($shots)['score'],
            'optimizedScore' => $newAnalysis['score'],
            'improvement' => $newAnalysis['score'] - $this->analyzeSequence($shots)['score'],
        ];
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * Normalize shot type to standard slug.
     */
    protected function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = str_replace(' ', '-', $type);
        $type = preg_replace('/-shot$/', '', $type);

        $mappings = [
            'closeup' => 'close-up',
            'close' => 'close-up',
            'wideshot' => 'wide',
            'mediumshot' => 'medium',
            'ots' => 'over-shoulder',
            'over-the-shoulder' => 'over-shoulder',
            'ecu' => 'extreme-close-up',
            'ews' => 'extreme-wide',
            'mcu' => 'medium-close-up',
            'mws' => 'medium-wide',
        ];

        return $mappings[$type] ?? $type;
    }

    /**
     * Get shot category for grouping.
     */
    protected function getShotCategory(string $type): string
    {
        $categories = [
            'close-up' => 'close',
            'extreme-close-up' => 'close',
            'medium-close-up' => 'close',
            'medium' => 'medium',
            'medium-wide' => 'medium',
            'wide' => 'wide',
            'extreme-wide' => 'wide',
            'establishing' => 'wide',
            'aerial' => 'wide',
            'over-shoulder' => 'coverage',
            'two-shot' => 'coverage',
            'reaction' => 'insert',
            'insert' => 'insert',
            'detail' => 'insert',
            'cutaway' => 'insert',
        ];

        return $categories[$type] ?? 'other';
    }

    /**
     * Get shot size as numeric value (1=close, 2=medium, 3=wide).
     */
    protected function getShotSize(string $type): int
    {
        $sizes = [
            'extreme-close-up' => 1,
            'close-up' => 1,
            'medium-close-up' => 2,
            'medium' => 2,
            'medium-wide' => 3,
            'wide' => 4,
            'extreme-wide' => 5,
            'establishing' => 5,
        ];

        return $sizes[$type] ?? 2;
    }

    /**
     * Infer movement intensity from shot data.
     */
    protected function inferIntensity(array $shot): string
    {
        $movement = $shot['cameraMovement'] ?? $shot['movement'] ?? '';
        $movement = strtolower($movement);

        if (empty($movement) || strpos($movement, 'static') !== false) {
            return 'static';
        }

        $intensityKeywords = [
            'subtle' => ['slow', 'gentle', 'slight', 'soft', 'subtle'],
            'moderate' => ['steady', 'smooth', 'controlled', 'fluid'],
            'dynamic' => ['tracking', 'following', 'active', 'energetic', 'swift'],
            'intense' => ['rapid', 'dramatic', 'whip', 'crash', 'fast'],
        ];

        foreach ($intensityKeywords as $intensity => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($movement, $keyword) !== false) {
                    return $intensity;
                }
            }
        }

        return 'moderate';
    }

    /**
     * Get best transition shot from a given shot type.
     */
    protected function getBestTransitionShot(string $fromType): ?string
    {
        $compatibilityMap = self::SHOT_COMPATIBILITY[$fromType] ?? [];

        if (empty($compatibilityMap)) {
            return null;
        }

        arsort($compatibilityMap);
        return array_key_first($compatibilityMap);
    }

    /**
     * Get reason for shot suggestion.
     */
    protected function getSuggestionReason(string $currentType, string $suggestedType, string $sceneType): string
    {
        $reasons = [
            'close-up' => [
                'reaction' => 'Reaction shot provides emotional counterpoint',
                'medium' => 'Medium shot provides context after close detail',
            ],
            'wide' => [
                'medium' => 'Medium shot focuses attention after establishing',
                'medium-wide' => 'Gradual approach to subjects',
            ],
            'medium' => [
                'close-up' => 'Close-up emphasizes emotional beats',
                'over-shoulder' => 'OTS creates engagement in dialogue',
            ],
            'over-shoulder' => [
                'over-shoulder' => 'Reverse angle maintains dialogue rhythm',
                'close-up' => 'Close-up punctuates important dialogue',
                'reaction' => 'Reaction shows listener response',
            ],
            'establishing' => [
                'wide' => 'Wide shot continues location establishment',
                'medium' => 'Medium shot introduces characters',
            ],
        ];

        return $reasons[$currentType][$suggestedType]
            ?? "Provides good visual variety for {$sceneType} scene";
    }

    /**
     * Get reason for sequence position.
     */
    protected function getSequencePositionReason(int $position, int $total, string $sceneType): string
    {
        $progress = $position / max(1, $total - 1);

        if ($position === 0) {
            return 'Opening shot establishes the scene';
        } elseif ($progress < 0.3) {
            return 'Early shot builds context';
        } elseif ($progress < 0.7) {
            return 'Middle shot develops narrative';
        } elseif ($position === $total - 1) {
            return 'Final shot provides closure or transition';
        } else {
            return 'Shot builds toward conclusion';
        }
    }

    /**
     * Find best intermediate shot between two incompatible shots.
     */
    protected function findBestIntermediateShot(array $shotA, array $shotB, string $sceneType): ?array
    {
        $typeA = $this->normalizeType($shotA['type'] ?? 'medium');
        $typeB = $this->normalizeType($shotB['type'] ?? 'medium');

        // Get shots compatible with both
        $compatA = self::SHOT_COMPATIBILITY[$typeA] ?? [];
        $compatB = array_flip(array_keys(self::SHOT_COMPATIBILITY));

        // Find shots that are good transitions from A
        $candidates = [];
        foreach ($compatA as $shotType => $scoreFromA) {
            if ($scoreFromA < 70) continue;

            // Check if this shot can transition to B
            $compatFromThis = self::SHOT_COMPATIBILITY[$shotType] ?? [];
            $scoreToB = $compatFromThis[$typeB] ?? 50;

            if ($scoreToB >= 70) {
                $candidates[$shotType] = ($scoreFromA + $scoreToB) / 2;
            }
        }

        if (empty($candidates)) {
            // Fallback to safe options
            $safeOptions = ['cutaway', 'insert', 'reaction'];
            foreach ($safeOptions as $option) {
                if ($option !== $typeA && $option !== $typeB) {
                    return [
                        'type' => $option,
                        'isIntermediate' => true,
                        'reason' => 'Inserted for smoother transition',
                    ];
                }
            }
            return null;
        }

        arsort($candidates);
        $bestType = array_key_first($candidates);

        return [
            'type' => $bestType,
            'isIntermediate' => true,
            'reason' => "Bridges transition from '{$typeA}' to '{$typeB}'",
        ];
    }

    /**
     * Get intermediate intensity between two intensities.
     */
    protected function getIntermediateIntensity(string $from, string $to): ?string
    {
        $levels = ['static', 'subtle', 'moderate', 'dynamic', 'intense'];
        $fromIndex = array_search($from, $levels);
        $toIndex = array_search($to, $levels);

        if ($fromIndex === false || $toIndex === false) {
            return null;
        }

        $diff = abs($toIndex - $fromIndex);
        if ($diff <= 1) {
            return null; // No intermediate needed
        }

        // Return middle value
        $midIndex = (int) (($fromIndex + $toIndex) / 2);
        return $levels[$midIndex];
    }

    /**
     * Check if coverage is complete for a pattern.
     */
    protected function isCoverageComplete(array $sequence, array $pattern): bool
    {
        $usedTypes = array_column($sequence, 'type');
        $requiredTypes = array_keys($pattern);

        // Check if at least 70% of pattern is covered
        $covered = count(array_intersect($usedTypes, $requiredTypes));
        return ($covered / count($requiredTypes)) >= 0.7;
    }

    // =====================================
    // SETTINGS
    // =====================================

    /**
     * Check if continuity checking is enabled.
     */
    public function isContinuityEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_continuity_enabled', true);
    }

    /**
     * Get minimum compatibility score for warnings.
     */
    public function getMinCompatibilityScore(): int
    {
        return (int) VwSetting::getValue('shot_continuity_min_score', 60);
    }

    /**
     * Check if 30-degree rule enforcement is enabled.
     */
    public function is30DegreeRuleEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_continuity_30_degree_rule', true);
    }

    /**
     * Check if auto-optimization is enabled.
     */
    public function isAutoOptimizationEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_continuity_auto_optimize', false);
    }

    /**
     * Clear service caches.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'patterns');
        Cache::forget(self::CACHE_PREFIX . 'compatibility');
    }
}
