<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwCoveragePattern;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * SceneTypeDetectorService - Automatic scene classification for coverage patterns.
 *
 * Analyzes scene content (narration, visual description, mood) to determine:
 * - Scene type (dialogue, action, emotional, montage, etc.)
 * - Recommended coverage pattern
 * - Pacing and movement suggestions
 * - Shot count recommendations
 *
 * Uses keyword matching, visual cue analysis, and contextual understanding
 * to provide Hollywood-quality scene classification.
 */
class SceneTypeDetectorService
{
    protected const CACHE_PREFIX = 'scene_detector_';
    protected const CACHE_TTL = 1800; // 30 minutes

    /**
     * Detect scene type and recommend coverage pattern.
     *
     * @param array $scene Scene data with narration, visualDescription, mood, etc.
     * @param array $context Additional context (genre, previousScene, etc.)
     * @return array Detection result with type, pattern, confidence, and recommendations
     */
    public function detectSceneType(array $scene, array $context = []): array
    {
        if (!$this->isDetectionEnabled()) {
            return $this->getFallbackDetection($scene, $context);
        }

        try {
            // Get all active patterns ordered by priority
            $patterns = VwCoveragePattern::getAllActive();

            if (empty($patterns)) {
                return $this->getFallbackDetection($scene, $context);
            }

            // Analyze scene content
            $analysisText = $this->buildAnalysisText($scene);

            // Score each pattern
            $scores = [];
            foreach ($patterns as $slug => $pattern) {
                $score = $this->scorePattern($pattern, $analysisText, $scene, $context);
                if ($score > 0) {
                    $scores[$slug] = [
                        'pattern' => $pattern,
                        'score' => $score,
                    ];
                }
            }

            // Sort by score
            uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

            // Get best match
            $bestMatch = reset($scores);
            $bestSlug = key($scores);

            if (!$bestMatch || $bestMatch['score'] < $this->getMinConfidenceScore()) {
                return $this->getFallbackDetection($scene, $context);
            }

            $pattern = $bestMatch['pattern'];

            // Build result
            $result = [
                'detected' => true,
                'sceneType' => $pattern['scene_type'],
                'patternSlug' => $bestSlug,
                'patternName' => $pattern['name'],
                'confidence' => min(100, $bestMatch['score']),
                'shotSequence' => $pattern['shot_sequence'],
                'recommendations' => [
                    'pacing' => $pattern['recommended_pacing'],
                    'minShots' => $pattern['min_shots'],
                    'maxShots' => $pattern['max_shots'],
                    'typicalDuration' => $pattern['typical_shot_duration'],
                    'movementIntensity' => $pattern['default_movement_intensity'],
                    'preferredMovements' => $pattern['preferred_movements'] ?? [],
                ],
                'alternatives' => $this->getAlternatives($scores, $bestSlug),
            ];

            // Log detection
            Log::info('SceneTypeDetectorService: Scene type detected', [
                'scene_type' => $result['sceneType'],
                'pattern' => $bestSlug,
                'confidence' => $result['confidence'],
            ]);

            // Increment usage counter
            $this->incrementPatternUsage($bestSlug);

            return $result;

        } catch (\Throwable $e) {
            Log::error('SceneTypeDetectorService: Detection failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->getFallbackDetection($scene, $context);
        }
    }

    /**
     * Build analysis text from scene data.
     */
    protected function buildAnalysisText(array $scene): string
    {
        $parts = [];

        // Narration
        if (!empty($scene['narration'])) {
            $parts[] = $scene['narration'];
        }

        // Visual description
        $visual = $scene['visualDescription'] ?? $scene['visual'] ?? '';
        if (!empty($visual)) {
            $parts[] = $visual;
        }

        // Mood
        if (!empty($scene['mood'])) {
            $parts[] = 'mood: ' . $scene['mood'];
        }

        // Dialogue content
        if (!empty($scene['dialogue'])) {
            $parts[] = 'dialogue: ' . (is_array($scene['dialogue']) ? implode(' ', $scene['dialogue']) : $scene['dialogue']);
        }

        // Action description
        if (!empty($scene['action'])) {
            $parts[] = 'action: ' . $scene['action'];
        }

        return strtolower(implode(' ', $parts));
    }

    /**
     * Score a pattern against the analysis text.
     */
    protected function scorePattern(array $pattern, string $text, array $scene, array $context): int
    {
        $score = 0;

        // Check negative keywords first (instant disqualification)
        $negativeKeywords = $pattern['negative_keywords'] ?? [];
        foreach ($negativeKeywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return 0;
            }
        }

        // Score based on detection keywords
        $keywords = $pattern['detection_keywords'] ?? [];
        if (!empty($keywords)) {
            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, strtolower($keyword)) !== false) {
                    $matchCount++;
                }
            }
            // Calculate keyword score (up to 60 points)
            $keywordScore = count($keywords) > 0
                ? (int) (($matchCount / count($keywords)) * 60)
                : 30;
            $score += $keywordScore;
        } else {
            $score += 30; // Neutral score if no keywords
        }

        // Boost based on pattern priority (up to 20 points)
        $priorityBoost = (int) (($pattern['priority'] ?? 50) / 5);
        $score += $priorityBoost;

        // Context matching (up to 20 points)
        $contextScore = $this->scoreContext($pattern, $scene, $context);
        $score += $contextScore;

        return min(100, $score);
    }

    /**
     * Score contextual factors.
     */
    protected function scoreContext(array $pattern, array $scene, array $context): int
    {
        $score = 0;

        // Genre matching
        $genre = $context['genre'] ?? '';
        $sceneType = $pattern['scene_type'];

        $genreTypeAffinity = [
            'action' => ['action' => 15, 'establishing' => 10],
            'drama' => ['dialogue' => 15, 'emotional' => 15],
            'comedy' => ['dialogue' => 15, 'montage' => 10],
            'thriller' => ['action' => 10, 'dialogue' => 10, 'emotional' => 10],
            'horror' => ['emotional' => 10, 'establishing' => 10],
            'romance' => ['dialogue' => 15, 'emotional' => 15],
            'documentary' => ['interview' => 20, 'documentary' => 20, 'establishing' => 10],
        ];

        if (isset($genreTypeAffinity[$genre][$sceneType])) {
            $score += $genreTypeAffinity[$genre][$sceneType];
        }

        // Previous scene continuity
        $previousSceneType = $context['previousSceneType'] ?? null;
        if ($previousSceneType) {
            // Different scene type after same scene type is slightly preferred (variety)
            if ($previousSceneType !== $sceneType) {
                $score += 5;
            }
        }

        // Pacing context
        $requestedPacing = $context['pacing'] ?? 'balanced';
        $patternPacing = $pattern['recommended_pacing'] ?? 'balanced';
        if ($requestedPacing === $patternPacing) {
            $score += 5;
        }

        return min(20, $score);
    }

    /**
     * Get alternative patterns.
     */
    protected function getAlternatives(array $scores, string $exclude, int $limit = 3): array
    {
        $alternatives = [];

        foreach ($scores as $slug => $data) {
            if ($slug === $exclude) continue;
            if ($data['score'] < 30) continue;

            $alternatives[] = [
                'patternSlug' => $slug,
                'patternName' => $data['pattern']['name'],
                'sceneType' => $data['pattern']['scene_type'],
                'confidence' => min(100, $data['score']),
            ];

            if (count($alternatives) >= $limit) break;
        }

        return $alternatives;
    }

    /**
     * Get fallback detection when main detection fails.
     */
    protected function getFallbackDetection(array $scene, array $context): array
    {
        // Use simple keyword detection
        $text = $this->buildAnalysisText($scene);

        $fallbackRules = [
            'dialogue' => ['says', 'asks', 'speaks', 'conversation', 'dialogue'],
            'action' => ['runs', 'fights', 'chases', 'attacks', 'explodes'],
            'emotional' => ['cries', 'tears', 'emotional', 'heartbreak', 'grief'],
            'establishing' => ['arrives', 'location', 'exterior', 'building'],
            'montage' => ['montage', 'training', 'prepares'],
        ];

        $detectedType = 'dialogue'; // Default
        $maxMatches = 0;

        foreach ($fallbackRules as $type => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $detectedType = $type;
            }
        }

        // Get default pattern for detected type
        $defaultPatterns = [
            'dialogue' => ['master', 'two-shot', 'over-shoulder', 'close-up', 'reaction'],
            'action' => ['wide', 'tracking', 'medium', 'close-up', 'insert'],
            'emotional' => ['wide', 'medium', 'close-up', 'extreme-close-up'],
            'establishing' => ['extreme-wide', 'wide', 'medium-wide'],
            'montage' => ['wide', 'medium', 'close-up', 'detail', 'cutaway'],
        ];

        return [
            'detected' => true,
            'sceneType' => $detectedType,
            'patternSlug' => $detectedType . '-fallback',
            'patternName' => ucfirst($detectedType) . ' (Fallback)',
            'confidence' => 50,
            'shotSequence' => $defaultPatterns[$detectedType] ?? $defaultPatterns['dialogue'],
            'recommendations' => [
                'pacing' => 'balanced',
                'minShots' => 2,
                'maxShots' => 8,
                'typicalDuration' => 6,
                'movementIntensity' => 'moderate',
                'preferredMovements' => ['static', 'push-in'],
            ],
            'alternatives' => [],
            'isFallback' => true,
        ];
    }

    /**
     * Analyze multiple scenes for continuity.
     *
     * @param array $scenes Array of scene data
     * @param array $context Project context
     * @return array Analysis with detected types for all scenes
     */
    public function analyzeSceneSequence(array $scenes, array $context = []): array
    {
        $results = [];
        $previousSceneType = null;

        foreach ($scenes as $index => $scene) {
            $sceneContext = array_merge($context, [
                'sceneIndex' => $index,
                'previousSceneType' => $previousSceneType,
                'totalScenes' => count($scenes),
                'position' => $index / max(1, count($scenes) - 1), // 0 to 1
            ]);

            // Detect scene type
            $detection = $this->detectSceneType($scene, $sceneContext);

            // Add position-based suggestions
            $detection['positionSuggestions'] = $this->getPositionSuggestions(
                $index,
                count($scenes),
                $detection['sceneType']
            );

            $results[] = $detection;
            $previousSceneType = $detection['sceneType'];
        }

        // Calculate overall analysis
        $typeDistribution = [];
        $totalConfidence = 0;
        foreach ($results as $result) {
            $type = $result['sceneType'];
            $typeDistribution[$type] = ($typeDistribution[$type] ?? 0) + 1;
            $totalConfidence += $result['confidence'];
        }

        return [
            'scenes' => $results,
            'summary' => [
                'totalScenes' => count($scenes),
                'typeDistribution' => $typeDistribution,
                'averageConfidence' => count($results) > 0 ? round($totalConfidence / count($results)) : 0,
                'dominantType' => !empty($typeDistribution) ? array_keys($typeDistribution, max($typeDistribution))[0] : 'dialogue',
            ],
        ];
    }

    /**
     * Get position-based suggestions (beginning, middle, end of story).
     */
    protected function getPositionSuggestions(int $index, int $total, string $sceneType): array
    {
        $position = $total > 1 ? $index / ($total - 1) : 0.5;
        $suggestions = [];

        if ($position < 0.15) {
            // Beginning - establishing shots preferred
            $suggestions[] = 'Consider establishing shots to set the scene';
            if ($sceneType !== 'establishing') {
                $suggestions[] = 'Opening scenes often benefit from wider shots';
            }
        } elseif ($position > 0.85) {
            // End - emotional closure
            $suggestions[] = 'Final scenes often build to emotional peaks';
            if ($sceneType === 'action') {
                $suggestions[] = 'Consider resolving action with aftermath shots';
            }
        } elseif ($position > 0.4 && $position < 0.6) {
            // Middle - peak conflict
            $suggestions[] = 'Midpoint is ideal for major revelations or confrontations';
        }

        return $suggestions;
    }

    /**
     * Get recommended pattern for a scene type.
     *
     * @param string $sceneType Scene type
     * @return array|null Pattern data or null
     */
    public function getRecommendedPattern(string $sceneType): ?array
    {
        $patterns = VwCoveragePattern::getBySceneType($sceneType);

        if (empty($patterns)) {
            return null;
        }

        // Return highest priority pattern
        return $patterns[0];
    }

    /**
     * Get all patterns for selection.
     */
    public function getAllPatternsForSelection(): array
    {
        $patterns = VwCoveragePattern::getAllActive();
        $grouped = [];

        foreach ($patterns as $slug => $pattern) {
            $type = $pattern['scene_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = [
                'slug' => $slug,
                'name' => $pattern['name'],
                'description' => $pattern['description'] ?? '',
                'shotCount' => count($pattern['shot_sequence'] ?? []),
            ];
        }

        return $grouped;
    }

    /**
     * Increment pattern usage counter.
     */
    protected function incrementPatternUsage(string $slug): void
    {
        try {
            $pattern = VwCoveragePattern::where('slug', $slug)->first();
            if ($pattern) {
                $pattern->incrementUsage();
            }
        } catch (\Throwable $e) {
            // Silently fail - usage tracking is non-critical
        }
    }

    /**
     * Provide feedback on pattern detection accuracy.
     */
    public function provideFeedback(string $slug, bool $wasAccurate): void
    {
        try {
            $pattern = VwCoveragePattern::where('slug', $slug)->first();
            if ($pattern) {
                $pattern->updateSuccessRate($wasAccurate);
            }
        } catch (\Throwable $e) {
            Log::warning('SceneTypeDetectorService: Failed to record feedback', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =====================================
    // SETTINGS
    // =====================================

    /**
     * Check if scene detection is enabled.
     */
    public function isDetectionEnabled(): bool
    {
        return (bool) VwSetting::getValue('scene_detection_enabled', true);
    }

    /**
     * Get minimum confidence score for detection.
     */
    public function getMinConfidenceScore(): int
    {
        return (int) VwSetting::getValue('scene_detection_min_confidence', 40);
    }

    /**
     * Check if auto-detection is enabled.
     */
    public function isAutoDetectionEnabled(): bool
    {
        return (bool) VwSetting::getValue('scene_detection_auto', true);
    }

    /**
     * Get default scene type when detection fails.
     */
    public function getDefaultSceneType(): string
    {
        return VwSetting::getValue('scene_detection_default_type', 'dialogue');
    }
}
