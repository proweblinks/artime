<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * EnhancedPromptService - Unified facade for all intelligence systems.
 *
 * PHASE 5: Provides a single entry point for the frontend to access:
 * - Scene type detection (Phase 4)
 * - Camera movement suggestions (Phase 1)
 * - Video prompt generation (Phase 2)
 * - Shot continuity validation (Phase 3)
 * - Full shot breakdown analysis
 *
 * This service orchestrates all the individual services and provides
 * convenient methods for common operations.
 */
class EnhancedPromptService
{
    protected ShotIntelligenceService $shotIntelligence;
    protected SceneTypeDetectorService $sceneTypeDetector;
    protected CameraMovementService $cameraMovement;
    protected VideoPromptBuilderService $videoPromptBuilder;
    protected ShotContinuityService $shotContinuity;

    public function __construct()
    {
        // Initialize all services
        $this->cameraMovement = new CameraMovementService();
        $this->videoPromptBuilder = new VideoPromptBuilderService();
        $this->shotContinuity = new ShotContinuityService($this->cameraMovement);
        $this->sceneTypeDetector = new SceneTypeDetectorService();

        // Create shot intelligence with all dependencies
        $this->shotIntelligence = new ShotIntelligenceService(
            $this->shotContinuity,
            $this->sceneTypeDetector,
            $this->cameraMovement,
            $this->videoPromptBuilder
        );
    }

    /**
     * Analyze a complete scene with all intelligence systems.
     *
     * This is the main method for the frontend to call.
     * Returns a complete breakdown with:
     * - Detected scene type
     * - Shot sequence with camera movements
     * - Video prompts for each shot
     * - Continuity analysis
     *
     * @param array $scene Scene data
     * @param array $context Optional context (genre, characters, etc.)
     * @return array Complete analysis result
     */
    public function analyzeScene(array $scene, array $context = []): array
    {
        return $this->shotIntelligence->analyzeScene($scene, $context);
    }

    /**
     * Analyze multiple scenes for a complete project.
     *
     * @param array $scenes Array of scene data
     * @param array $context Project context
     * @return array Analysis for all scenes with continuity
     */
    public function analyzeProject(array $scenes, array $context = []): array
    {
        $results = [];
        $previousSceneType = null;

        foreach ($scenes as $index => $scene) {
            $sceneContext = array_merge($context, [
                'sceneIndex' => $index,
                'previousSceneType' => $previousSceneType,
                'totalScenes' => count($scenes),
            ]);

            $analysis = $this->analyzeScene($scene, $sceneContext);
            $results[] = $analysis;

            $previousSceneType = $analysis['sceneTypeDetection']['sceneType'] ?? null;
        }

        // Calculate project-level metrics
        $totalShots = array_sum(array_column($results, 'shotCount'));
        $totalDuration = array_sum(array_column($results, 'totalDuration'));
        $avgContinuityScore = 0;
        $continuityScores = [];

        foreach ($results as $r) {
            if (isset($r['continuity']['score'])) {
                $continuityScores[] = $r['continuity']['score'];
            }
        }

        if (!empty($continuityScores)) {
            $avgContinuityScore = array_sum($continuityScores) / count($continuityScores);
        }

        return [
            'scenes' => $results,
            'projectStats' => [
                'totalScenes' => count($scenes),
                'totalShots' => $totalShots,
                'totalDuration' => $totalDuration,
                'averageContinuityScore' => round($avgContinuityScore),
                'estimatedRuntime' => $this->formatDuration($totalDuration),
            ],
        ];
    }

    /**
     * Quick scene type detection only.
     *
     * @param array $scene Scene data
     * @param array $context Optional context
     * @return array Detection result
     */
    public function detectSceneType(array $scene, array $context = []): array
    {
        return $this->sceneTypeDetector->detectSceneType($scene, $context);
    }

    /**
     * Get suggested camera movement for a shot.
     *
     * @param array $params Shot parameters
     * @return array Movement suggestion
     */
    public function suggestCameraMovement(array $params): array
    {
        return $this->cameraMovement->suggestMovement($params);
    }

    /**
     * Build a video prompt for a shot.
     *
     * @param array $shot Shot data
     * @param array $context Context
     * @return array Prompt result
     */
    public function buildVideoPrompt(array $shot, array $context = []): array
    {
        return $this->videoPromptBuilder->buildPrompt($shot, $context);
    }

    /**
     * Validate shot sequence continuity.
     *
     * @param array $shots Array of shots
     * @return array Validation result
     */
    public function validateContinuity(array $shots): array
    {
        return $this->shotContinuity->validateSequence($shots);
    }

    /**
     * Get coverage pattern for a scene type.
     *
     * @param string $sceneType Scene type
     * @return array Coverage pattern
     */
    public function getCoveragePattern(string $sceneType): array
    {
        return $this->shotContinuity->getCoveragePattern($sceneType);
    }

    /**
     * Get all available camera movements.
     *
     * @return array Movements grouped by category
     */
    public function getAvailableMovements(): array
    {
        return $this->cameraMovement->getAvailableMovements();
    }

    /**
     * Get all available coverage patterns.
     *
     * @return array Patterns grouped by scene type
     */
    public function getAvailablePatterns(): array
    {
        return $this->sceneTypeDetector->getAllPatternsForSelection();
    }

    /**
     * Generate enhanced video prompt from basic prompt.
     *
     * Takes a simple prompt and enhances it with:
     * - Camera movement
     * - Lighting suggestions
     * - Quality markers
     *
     * @param string $basicPrompt Original prompt
     * @param array $options Enhancement options
     * @return array Enhanced prompt result
     */
    public function enhancePrompt(string $basicPrompt, array $options = []): array
    {
        $shot = [
            'subjectAction' => $basicPrompt,
            'type' => $options['shotType'] ?? 'medium',
            'cameraMovement' => $options['cameraMovement'] ?? null,
        ];

        $context = [
            'mood' => $options['mood'] ?? 'neutral',
            'genre' => $options['genre'] ?? 'general',
            'qualityLevel' => $options['quality'] ?? 'cinematic',
        ];

        // Add camera movement if not specified
        if (empty($shot['cameraMovement'])) {
            $movementSuggestion = $this->suggestCameraMovement([
                'shotType' => $shot['type'],
                'sceneType' => $options['sceneType'] ?? 'dialogue',
                'mood' => $context['mood'],
            ]);

            if (isset($movementSuggestion['movement'])) {
                $shot['cameraMovement'] = $movementSuggestion['movement']['prompt_syntax'] ?? '';
            }
        }

        return $this->buildVideoPrompt($shot, $context);
    }

    /**
     * Get system status for all services.
     *
     * @return array Status of each service
     */
    public function getSystemStatus(): array
    {
        return [
            'shotIntelligence' => [
                'enabled' => ShotIntelligenceService::isEnabled(),
                'description' => 'AI-driven shot decomposition',
            ],
            'sceneTypeDetection' => [
                'enabled' => $this->sceneTypeDetector->isDetectionEnabled(),
                'autoDetect' => $this->sceneTypeDetector->isAutoDetectionEnabled(),
                'description' => 'Automatic scene classification',
            ],
            'shotContinuity' => [
                'enabled' => $this->shotContinuity->isContinuityEnabled(),
                'autoOptimize' => $this->shotContinuity->isAutoOptimizationEnabled(),
                'description' => '30-degree rule and shot sequencing',
            ],
            'cameraMovement' => [
                'enabled' => (bool) VwSetting::getValue('motion_intelligence_enabled', true),
                'description' => 'Professional camera movements',
            ],
            'videoPromptBuilder' => [
                'enabled' => (bool) VwSetting::getValue('video_prompt_enabled', true),
                'qualityLevel' => VwSetting::getValue('video_prompt_quality_level', 'cinematic'),
                'description' => 'Higgsfield formula prompts',
            ],
        ];
    }

    /**
     * Format duration in human-readable format.
     */
    protected function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$seconds}s";
    }

    // =====================================
    // STATIC FACTORY METHODS
    // =====================================

    /**
     * Create a new instance with default configuration.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Quick method to analyze a scene.
     */
    public static function analyze(array $scene, array $context = []): array
    {
        return (new self())->analyzeScene($scene, $context);
    }

    /**
     * Quick method to enhance a prompt.
     */
    public static function enhance(string $prompt, array $options = []): array
    {
        return (new self())->enhancePrompt($prompt, $options);
    }
}
