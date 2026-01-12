<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Services\ShotContinuityService;
use Modules\AppVideoWizard\Services\SceneTypeDetectorService;
use Modules\AppVideoWizard\Services\CameraMovementService;
use Modules\AppVideoWizard\Services\VideoPromptBuilderService;
use Modules\AppVideoWizard\Services\ShotProgressionService;

/**
 * ShotIntelligenceService - AI-driven shot decomposition for scenes.
 *
 * PHASE 5 INTEGRATION: Connects all intelligence systems:
 * - Phase 1: CameraMovementService (motion intelligence)
 * - Phase 2: VideoPromptBuilderService (Higgsfield formula prompts)
 * - Phase 3: ShotContinuityService (30-degree rule, coverage patterns)
 * - Phase 4: SceneTypeDetectorService (automatic scene classification)
 * - Phase 6: ShotProgressionService (narrative progression, story beats, action continuity)
 *
 * Analyzes scene content (narration, visual description, mood) and determines:
 * - Optimal number of shots based on pacing and content
 * - Per-shot duration based on action/dialogue density
 * - Shot type sequence for professional cinematography
 * - Camera movements for each shot
 * - Which shots need lip-sync (Multitalk) vs standard animation (MiniMax)
 * - Story beats and energy progression per shot (Phase 6)
 */
class ShotIntelligenceService
{
    /**
     * Available shot types for AI to choose from.
     */
    protected array $shotTypes;

    /**
     * AI provider service for making LLM calls.
     */
    protected $aiService;

    /**
     * Phase 3: Shot continuity service for sequence validation.
     */
    protected ?ShotContinuityService $continuityService = null;

    /**
     * Phase 4: Scene type detector for auto-classification.
     */
    protected ?SceneTypeDetectorService $sceneTypeDetector = null;

    /**
     * Phase 1: Camera movement service for motion intelligence.
     */
    protected ?CameraMovementService $cameraMovementService = null;

    /**
     * Phase 2: Video prompt builder for Higgsfield formula.
     */
    protected ?VideoPromptBuilderService $videoPromptBuilder = null;

    /**
     * Phase 6: Shot progression service for narrative development.
     */
    protected ?ShotProgressionService $progressionService = null;

    public function __construct(
        ?ShotContinuityService $continuityService = null,
        ?SceneTypeDetectorService $sceneTypeDetector = null,
        ?CameraMovementService $cameraMovementService = null,
        ?VideoPromptBuilderService $videoPromptBuilder = null,
        ?ShotProgressionService $progressionService = null
    ) {
        $this->shotTypes = VwShotType::getAllActive();
        $this->continuityService = $continuityService;
        $this->sceneTypeDetector = $sceneTypeDetector;
        $this->cameraMovementService = $cameraMovementService;
        $this->videoPromptBuilder = $videoPromptBuilder;
        $this->progressionService = $progressionService;
    }

    /**
     * Set the continuity service (for dependency injection).
     */
    public function setContinuityService(ShotContinuityService $service): void
    {
        $this->continuityService = $service;
    }

    /**
     * Set the scene type detector (for dependency injection).
     */
    public function setSceneTypeDetector(SceneTypeDetectorService $detector): void
    {
        $this->sceneTypeDetector = $detector;
    }

    /**
     * Set the camera movement service (for dependency injection).
     */
    public function setCameraMovementService(CameraMovementService $service): void
    {
        $this->cameraMovementService = $service;
    }

    /**
     * Set the video prompt builder (for dependency injection).
     */
    public function setVideoPromptBuilder(VideoPromptBuilderService $builder): void
    {
        $this->videoPromptBuilder = $builder;
    }

    /**
     * Set the shot progression service (for dependency injection).
     */
    public function setProgressionService(ShotProgressionService $service): void
    {
        $this->progressionService = $service;
    }

    /**
     * Analyze a scene and determine optimal shot breakdown.
     *
     * PHASE 5 INTEGRATION: This method now integrates all phases:
     * - Phase 4: Auto-detect scene type first
     * - Phase 1: Add camera movements to each shot
     * - Phase 2: Generate video prompts for each shot
     * - Phase 3: Validate and optimize continuity
     * - Phase 6: Add progression analysis (story beats, energy, action continuity)
     *
     * @param array $scene Scene data with narration, visualDescription, duration, etc.
     * @param array $context Additional context (genre, pacing, characters, tensionCurve, emotionalJourney, etc.)
     * @return array Shot breakdown with shots array and metadata
     */
    public function analyzeScene(array $scene, array $context = []): array
    {
        try {
            // PHASE 4: Auto-detect scene type if detector is available
            $sceneTypeDetection = $this->detectSceneTypeIfEnabled($scene, $context);
            if ($sceneTypeDetection) {
                $context['sceneType'] = $sceneTypeDetection['sceneType'];
                $context['coveragePattern'] = $sceneTypeDetection['patternSlug'] ?? null;
                $context['sceneTypeConfidence'] = $sceneTypeDetection['confidence'] ?? 0;
            }

            // Get settings
            $minShots = (int) VwSetting::getValue('shot_min_per_scene', 1);
            $maxShots = (int) VwSetting::getValue('shot_max_per_scene', 20);
            $aiPromptTemplate = VwSetting::getValue('shot_ai_prompt', $this->getDefaultPrompt());

            // Build the analysis prompt
            $prompt = $this->buildAnalysisPrompt($scene, $context, $aiPromptTemplate);

            // Call AI service
            $aiResponse = $this->callAI($prompt, $context);

            if (!$aiResponse['success']) {
                Log::warning('ShotIntelligenceService: AI analysis failed, using fallback', [
                    'error' => $aiResponse['error'] ?? 'Unknown error',
                ]);
                return $this->getFallbackAnalysis($scene, $context, $minShots, $maxShots);
            }

            // Parse and validate AI response
            $analysis = $this->parseAIResponse($aiResponse['response'], $scene, $minShots, $maxShots);

            // PHASE 1: Add camera movements to each shot
            $analysis = $this->addCameraMovements($analysis, $scene, $context);

            // PHASE 2: Generate video prompts for each shot
            $analysis = $this->addVideoPrompts($analysis, $scene, $context);

            // PHASE 3: Add continuity analysis
            $analysis = $this->addContinuityAnalysis($analysis, $context);

            // PHASE 6: Add progression analysis (story beats, energy levels, action continuity)
            $analysis = $this->addProgressionAnalysis($analysis, $scene, $context);

            // Add scene type detection results
            if ($sceneTypeDetection) {
                $analysis['sceneTypeDetection'] = $sceneTypeDetection;
            }

            Log::info('ShotIntelligenceService: Scene analyzed with full integration', [
                'scene_id' => $scene['id'] ?? 'unknown',
                'shot_count' => $analysis['shotCount'],
                'total_duration' => $analysis['totalDuration'],
                'scene_type' => $context['sceneType'] ?? 'unknown',
                'continuity_score' => $analysis['continuity']['score'] ?? null,
                'progression_score' => $analysis['progression']['overallScore'] ?? null,
            ]);

            return $analysis;

        } catch (\Throwable $e) {
            Log::error('ShotIntelligenceService: Exception during analysis', [
                'error' => $e->getMessage(),
                'scene_id' => $scene['id'] ?? 'unknown',
            ]);

            return $this->getFallbackAnalysis($scene, $context,
                (int) VwSetting::getValue('shot_min_per_scene', 1),
                (int) VwSetting::getValue('shot_max_per_scene', 20)
            );
        }
    }

    /**
     * PHASE 4: Detect scene type if detector is available and enabled.
     */
    protected function detectSceneTypeIfEnabled(array $scene, array $context): ?array
    {
        if (!$this->sceneTypeDetector || !$this->sceneTypeDetector->isAutoDetectionEnabled()) {
            return null;
        }

        try {
            return $this->sceneTypeDetector->detectSceneType($scene, $context);
        } catch (\Throwable $e) {
            Log::warning('ShotIntelligenceService: Scene type detection failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * PHASE 1: Add camera movements to each shot in the analysis.
     */
    protected function addCameraMovements(array $analysis, array $scene, array $context): array
    {
        if (!$this->cameraMovementService) {
            return $analysis;
        }

        $sceneType = $context['sceneType'] ?? 'dialogue';
        $previousMovement = null;

        foreach ($analysis['shots'] as $index => &$shot) {
            try {
                $movementSuggestion = $this->cameraMovementService->suggestMovement([
                    'shotType' => $shot['type'] ?? 'medium',
                    'sceneType' => $sceneType,
                    'isFirstShot' => ($index === 0),
                    'previousMovement' => $previousMovement,
                    'mood' => $scene['mood'] ?? $context['mood'] ?? 'neutral',
                ]);

                if ($movementSuggestion && isset($movementSuggestion['movement'])) {
                    $shot['cameraMovement'] = $movementSuggestion['movement']['prompt_syntax'] ?? $movementSuggestion['movement']['name'] ?? '';
                    $shot['movementSlug'] = $movementSuggestion['movement']['slug'] ?? '';
                    $shot['movementIntensity'] = $movementSuggestion['suggestedIntensity'] ?? 'moderate';
                    $previousMovement = $shot['movementSlug'];
                }
            } catch (\Throwable $e) {
                Log::warning('ShotIntelligenceService: Camera movement suggestion failed for shot', [
                    'shot_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $analysis;
    }

    /**
     * PHASE 2: Add video prompts to each shot using Higgsfield formula.
     */
    protected function addVideoPrompts(array $analysis, array $scene, array $context): array
    {
        if (!$this->videoPromptBuilder) {
            return $analysis;
        }

        foreach ($analysis['shots'] as $index => &$shot) {
            try {
                $promptResult = $this->videoPromptBuilder->buildPrompt($shot, [
                    'scene' => $scene,
                    'context' => $context,
                    'shotIndex' => $index,
                    'totalShots' => count($analysis['shots']),
                ]);

                if ($promptResult && isset($promptResult['prompt'])) {
                    $shot['videoPrompt'] = $promptResult['prompt'];
                    $shot['promptComponents'] = $promptResult['components'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('ShotIntelligenceService: Video prompt generation failed for shot', [
                    'shot_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $analysis;
    }

    /**
     * Build the AI analysis prompt from template and scene data.
     */
    protected function buildAnalysisPrompt(array $scene, array $context, string $template): string
    {
        $variables = [
            'scene_description' => $scene['visualDescription'] ?? $scene['visual'] ?? '',
            'narration' => $scene['narration'] ?? '',
            'duration' => $scene['duration'] ?? 30,
            'mood' => $scene['mood'] ?? $context['mood'] ?? 'neutral',
            'genre' => $context['genre'] ?? 'general',
            'pacing' => $context['pacing'] ?? 'balanced',
            'has_dialogue' => !empty($scene['dialogue']) || $this->detectDialogue($scene['narration'] ?? '') ? 'yes' : 'no',
            'characters' => implode(', ', $context['characters'] ?? []),
            'available_shot_types' => $this->getAvailableShotTypesForPrompt(),
            // Phase 4 & 5: Scene type and coverage pattern for enhanced prompts
            'scene_type' => $context['sceneType'] ?? 'dialogue',
            'coverage_pattern' => $context['coveragePattern'] ?? 'dialogue-standard',
        ];

        // Replace template variables
        $prompt = $template;
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Call AI service to analyze the scene.
     */
    protected function callAI(string $prompt, array $context): array
    {
        try {
            // Use the AI model tier from context or default to economy
            $modelTier = $context['aiModelTier'] ?? 'economy';

            // Get AI service based on tier
            $aiConfig = $this->getAIConfig($modelTier);

            // Make the API call
            $response = $this->makeAIRequest($prompt, $aiConfig);

            return [
                'success' => true,
                'response' => $response,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AI configuration based on model tier.
     * Uses VwSetting for provider/model configuration with fallback to defaults.
     */
    protected function getAIConfig(string $tier): array
    {
        // Get provider and model from unified settings
        $provider = VwSetting::getValue('ai_shot_analysis_provider', 'openai');
        $model = VwSetting::getValue('ai_shot_analysis_model', 'gpt-4');

        // Tier-based model overrides (user can still set base provider in admin)
        $tierModels = [
            'economy' => [
                'openai' => 'gpt-4o-mini',
                'grok' => 'grok-2-fast',
                'gemini' => 'gemini-1.5-flash',
                'anthropic' => 'claude-3-haiku',
            ],
            'standard' => [
                'openai' => 'gpt-4o-mini',
                'grok' => 'grok-2',
                'gemini' => 'gemini-1.5-pro',
                'anthropic' => 'claude-3-5-sonnet',
            ],
            'premium' => [
                'openai' => 'gpt-4o',
                'grok' => 'grok-3',
                'gemini' => 'gemini-2.0-flash-exp',
                'anthropic' => 'claude-3-5-opus',
            ],
        ];

        // Use tier-specific model if available, otherwise use the configured model
        $tierConfig = $tierModels[$tier] ?? $tierModels['economy'];
        $finalModel = $tierConfig[$provider] ?? $model;

        return [
            'provider' => $provider,
            'model' => $finalModel,
        ];
    }

    /**
     * Make the actual AI API request.
     */
    protected function makeAIRequest(string $prompt, array $config): string
    {
        $provider = $config['provider'];
        $model = $config['model'];

        // Use existing AI infrastructure
        if ($provider === 'openai') {
            return $this->callOpenAI($prompt, $model);
        } elseif ($provider === 'grok') {
            return $this->callGrok($prompt, $model);
        } elseif ($provider === 'gemini') {
            return $this->callGemini($prompt, $model);
        }

        throw new \Exception("Unsupported AI provider: {$provider}");
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAI(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_openai_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional film director and cinematographer. Analyze scenes and provide optimal shot breakdowns in JSON format only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Call Grok API (xAI).
     */
    protected function callGrok(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_grok_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('Grok API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.x.ai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional film director and cinematographer. Analyze scenes and provide optimal shot breakdowns in JSON format only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Grok API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Call Gemini API.
     */
    protected function callGemini(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_gemini_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2000,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    /**
     * Parse AI response and validate shot breakdown.
     */
    protected function parseAIResponse(string $response, array $scene, int $minShots, int $maxShots): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $jsonStr = $response;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $jsonStr = trim($matches[1]);
        }

        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::warning('ShotIntelligenceService: Failed to parse AI response as JSON', [
                'response' => substr($response, 0, 500),
            ]);
            return $this->getFallbackAnalysis($scene, [], $minShots, $maxShots);
        }

        // Validate and normalize the response
        $shotCount = isset($data['shotCount']) ? (int) $data['shotCount'] : count($data['shots'] ?? []);
        $shotCount = max($minShots, min($maxShots, $shotCount));

        $shots = [];
        $totalDuration = 0;

        foreach (($data['shots'] ?? []) as $index => $shotData) {
            if ($index >= $shotCount) break;

            $shot = $this->normalizeShot($shotData, $index, $scene);
            $shots[] = $shot;
            $totalDuration += $shot['duration'];
        }

        // If AI returned fewer shots than expected, fill with defaults
        while (count($shots) < $shotCount) {
            $index = count($shots);
            $shots[] = $this->createDefaultShot($index, $shotCount, $scene);
            $totalDuration += $shots[count($shots) - 1]['duration'];
        }

        return [
            'success' => true,
            'shotCount' => count($shots),
            'shots' => $shots,
            'totalDuration' => $totalDuration,
            'reasoning' => $data['reasoning'] ?? 'AI-optimized shot breakdown',
            'source' => 'ai',
        ];
    }

    /**
     * Normalize a shot from AI response.
     */
    protected function normalizeShot(array $shotData, int $index, array $scene): array
    {
        // Get shot type first (needed for duration calculation)
        $type = $shotData['type'] ?? $shotData['shot_type'] ?? 'medium';
        $type = $this->normalizeTypeName($type);

        // Determine if lip-sync is needed
        $needsLipSync = $shotData['needsLipSync'] ?? $shotData['needs_lip_sync'] ?? false;
        $model = $needsLipSync ? 'multitalk' : 'minimax';

        // Get available durations for the model
        $availableDurations = $this->getAvailableDurations($model);

        // Get duration - use AI-provided or intelligent default based on shot type
        $aiProvidedDuration = $shotData['duration'] ?? null;

        if ($aiProvidedDuration !== null && $aiProvidedDuration > 0) {
            // AI provided a duration - snap it to available options
            $duration = $this->snapToAvailableDuration((int) $aiProvidedDuration, $availableDurations);
        } else {
            // No AI duration - use intelligent shot-type based duration
            $duration = $this->getOptimalDurationForShotType($type, $needsLipSync, $model);
        }

        // Get shot type info if available
        $shotTypeInfo = $this->shotTypes[$type] ?? null;

        return [
            'type' => $type,
            'duration' => $duration,
            'purpose' => $shotData['purpose'] ?? 'narrative',
            'cameraMovement' => $shotData['cameraMovement'] ?? $shotData['camera_movement'] ?? $this->getDefaultCameraMovement($type),
            'needsLipSync' => $needsLipSync,
            'recommendedModel' => $model,
            'description' => $shotData['description'] ?? $shotTypeInfo['description'] ?? '',
            'lens' => $shotTypeInfo['defaultLens'] ?? 'standard 50mm',
            'aiRecommended' => true,
            // Subject action for Hollywood-quality video prompts (critical for animation quality)
            'subjectAction' => $shotData['subjectAction'] ?? $shotData['subject_action'] ?? null,
        ];
    }

    /**
     * Snap duration to nearest available value.
     */
    protected function snapToAvailableDuration(int $duration, array $available): int
    {
        if (in_array($duration, $available)) {
            return $duration;
        }

        // Find nearest available duration
        $nearest = $available[0];
        $minDiff = abs($duration - $nearest);

        foreach ($available as $avail) {
            $diff = abs($duration - $avail);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $avail;
            }
        }

        return $nearest;
    }

    /**
     * Get available durations for a model.
     */
    protected function getAvailableDurations(string $model): array
    {
        $settingSlug = $model === 'multitalk'
            ? 'animation_multitalk_durations'
            : 'animation_minimax_durations';

        $defaults = $model === 'multitalk' ? [5, 10, 15, 20] : [5, 6, 10];
        $durations = VwSetting::getValue($settingSlug, $defaults);

        if (is_string($durations)) {
            $durations = json_decode($durations, true) ?? $defaults;
        }

        return array_map('intval', (array) $durations);
    }

    /**
     * Normalize shot type name to slug format.
     */
    protected function normalizeTypeName(string $type): string
    {
        // Convert "Close Up" to "close-up", "Medium Shot" to "medium", etc.
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+shot$/i', '', $type);
        $type = str_replace(' ', '-', $type);

        // Map common variations
        $mappings = [
            'closeup' => 'close-up',
            'close' => 'close-up',
            'wide' => 'wide',
            'medium' => 'medium',
            'establishing' => 'establishing',
            'extreme-wide' => 'extreme-wide',
            'extreme-close-up' => 'extreme-close-up',
            'over-the-shoulder' => 'over-shoulder',
            'over-shoulder' => 'over-shoulder',
            'two-shot' => 'two-shot',
            'reaction' => 'reaction',
            'insert' => 'insert',
            'pov' => 'pov',
            'dutch' => 'dutch-angle',
            'dutch-angle' => 'dutch-angle',
            'low-angle' => 'low-angle',
            'high-angle' => 'high-angle',
            'birds-eye' => 'birds-eye',
            'worms-eye' => 'worms-eye',
        ];

        return $mappings[$type] ?? $type;
    }

    /**
     * Get default camera movement for shot type.
     */
    protected function getDefaultCameraMovement(string $type): string
    {
        $movements = [
            'establishing' => 'slow pan',
            'wide' => 'static or slow pan',
            'medium' => 'subtle movement',
            'close-up' => 'slight push in',
            'extreme-close-up' => 'static',
            'reaction' => 'static',
            'over-shoulder' => 'subtle drift',
            'two-shot' => 'gentle track',
            'pov' => 'handheld movement',
            'dutch-angle' => 'slow rotation',
        ];

        return $movements[$type] ?? 'subtle movement';
    }

    /**
     * Get optimal duration based on shot type.
     * This provides intelligent defaults when AI doesn't specify or as fallback.
     */
    protected function getOptimalDurationForShotType(string $type, bool $needsLipSync = false, string $model = 'minimax'): int
    {
        // If needs lip-sync, use longer durations (multitalk supports 5-20s)
        if ($needsLipSync) {
            return 10; // Default dialogue duration
        }

        // Shot-type based durations (cinematography best practices)
        $durationMap = [
            // Opening/establishing shots - longer to set the scene
            'establishing' => 6,
            'extreme-wide' => 10,
            'wide' => 6,

            // Standard narrative shots
            'medium' => 6,
            'medium-wide' => 6,
            'full' => 6,
            'two-shot' => 6,
            'over-shoulder' => 6,

            // Close shots - quicker for impact
            'close-up' => 5,
            'extreme-close-up' => 5,
            'detail' => 5,
            'insert' => 5,

            // Reaction and quick cuts
            'reaction' => 5,
            'cutaway' => 5,

            // POV and special shots
            'pov' => 6,
            'dutch-angle' => 5,
            'low-angle' => 6,
            'high-angle' => 6,
            'birds-eye' => 6,
            'worms-eye' => 5,
        ];

        $duration = $durationMap[$type] ?? 6;

        // Snap to available durations for the model
        $availableDurations = $this->getAvailableDurations($model);
        return $this->snapToAvailableDuration($duration, $availableDurations);
    }

    /**
     * Create a default shot for filling gaps.
     */
    protected function createDefaultShot(int $index, int $totalShots, array $scene): array
    {
        // Determine shot type based on position in sequence
        $position = $index / max(1, $totalShots - 1);

        if ($index === 0) {
            $type = 'establishing';
        } elseif ($position < 0.3) {
            $type = 'wide';
        } elseif ($position < 0.7) {
            $type = 'medium';
        } else {
            $type = 'close-up';
        }

        // Check if this shot might have dialogue
        $hasDialogue = $this->detectDialogue($scene['narration'] ?? '');
        $needsLipSync = $hasDialogue && $position > 0.4; // Dialogue more likely in middle/end shots

        // Get optimal duration based on shot type
        $model = $needsLipSync ? 'multitalk' : 'minimax';
        $duration = $this->getOptimalDurationForShotType($type, $needsLipSync, $model);

        return [
            'type' => $type,
            'duration' => $duration,
            'purpose' => 'narrative',
            'cameraMovement' => $this->getDefaultCameraMovement($type),
            'needsLipSync' => $needsLipSync,
            'recommendedModel' => $model,
            'description' => '',
            'lens' => 'standard 50mm',
            'aiRecommended' => false,
        ];
    }

    /**
     * Get fallback analysis when AI fails.
     */
    protected function getFallbackAnalysis(array $scene, array $context, int $minShots, int $maxShots): array
    {
        $sceneDuration = $scene['duration'] ?? 30;
        $clipDuration = (int) VwSetting::getValue('duration_shot_default', 6);

        // Calculate shot count based on scene duration
        $shotCount = max($minShots, min($maxShots, (int) ceil($sceneDuration / $clipDuration)));

        $shots = [];
        $totalDuration = 0;

        for ($i = 0; $i < $shotCount; $i++) {
            $shot = $this->createDefaultShot($i, $shotCount, $scene);
            $shots[] = $shot;
            $totalDuration += $shot['duration'];
        }

        return [
            'success' => true,
            'shotCount' => $shotCount,
            'shots' => $shots,
            'totalDuration' => $totalDuration,
            'reasoning' => 'Standard shot breakdown (AI unavailable)',
            'source' => 'fallback',
        ];
    }

    /**
     * Detect if narration contains dialogue (speaking characters).
     */
    protected function detectDialogue(string $narration): bool
    {
        // Check for dialogue indicators
        $dialoguePatterns = [
            '/["\'](.*?)["\']/', // Quoted text
            '/\bsays?\b/i',
            '/\btells?\b/i',
            '/\basks?\b/i',
            '/\breplies?\b/i',
            '/\bexclaims?\b/i',
            '/\bwhispers?\b/i',
            '/\bshouts?\b/i',
            '/\bspeaks?\b/i',
            '/\bdialogue\b/i',
            '/\bconversation\b/i',
        ];

        foreach ($dialoguePatterns as $pattern) {
            if (preg_match($pattern, $narration)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available shot types formatted for the AI prompt.
     */
    protected function getAvailableShotTypesForPrompt(): string
    {
        $types = [];
        foreach ($this->shotTypes as $slug => $info) {
            $types[] = $slug . ' (' . ($info['description'] ?? $info['name'] ?? $slug) . ')';
        }

        return implode(', ', array_slice($types, 0, 20)); // Limit to prevent prompt bloat
    }

    /**
     * Get the default AI prompt template.
     * PHASE 5: Enhanced with scene type detection and camera movement guidance.
     */
    protected function getDefaultPrompt(): string
    {
        return 'Analyze this scene and determine the optimal cinematic shot breakdown.

SCENE: {{scene_description}}
NARRATION: {{narration}}
DURATION: {{duration}} seconds
MOOD: {{mood}}
GENRE: {{genre}}
PACING: {{pacing}}
HAS DIALOGUE: {{has_dialogue}}
SCENE TYPE: {{scene_type}}
COVERAGE PATTERN: {{coverage_pattern}}

SCENE TYPE COVERAGE PATTERNS:
- DIALOGUE: Master → Two-Shot → Over-Shoulder → Close-up → Reaction (build intimacy)
- ACTION: Wide → Tracking → Medium → Close-up → Insert (maintain energy)
- EMOTIONAL: Wide → Medium → Close-up → Extreme Close-up (build intensity)
- ESTABLISHING: Extreme-Wide → Wide → Medium-Wide (set location)
- MONTAGE: Mix shot sizes and angles for visual variety

DURATION RULES (CRITICAL - vary durations based on shot type and content):
- Establishing/Wide shots: 6s or 10s (longer to set the scene)
- Medium shots: 6s (standard narrative)
- Close-up shots: 5s (quick emotional impact)
- Detail/Insert shots: 5s (brief focus)
- Reaction shots: 5s (quick cut)
- Dialogue shots with lip-sync: 10s, 15s, or 20s (match dialogue length)
- Action sequences: 5s (fast pacing)
- Emotional/contemplative moments: 6s or 10s (let it breathe)

CAMERA MOVEMENT GUIDANCE:
- Opening/Establishing shots: slow pan, crane, or aerial
- Dialogue scenes: static, subtle push-in, gentle drift
- Action scenes: tracking, handheld, whip-pan
- Emotional peaks: slow push-in to close-up
- Transitions: match movement between shots for continuity

30-DEGREE RULE: When cutting between similar shot sizes, camera angle should change at least 30 degrees to avoid jump cuts.

Consider:
1. Pacing - {{pacing}} pacing affects shot duration (fast=shorter, slow=longer)
2. Dialogue - shots with speaking characters need lip-sync (needsLipSync: true) and LONGER durations (10-20s)
3. Visual variety - mix shot types AND durations for professional look
4. Story beats - establish (6-10s), develop (5-6s), climax (5s for impact)
5. Scene mood - {{mood}} mood affects rhythm
6. Scene type - follow {{scene_type}} coverage pattern guidelines
7. SUBJECT ACTION (CRITICAL for video animation):
   - Each shot MUST describe what the subject/characters are DOING
   - Use "the subject" or simple pronouns for image-to-video compatibility
   - For chained shots (shot 2+), describe continuation or transition of action
   - Include emotional state/expression for close-ups
   - Examples: "The subject looks around in bewilderment", "The subjects orient themselves", "The subject\'s expression shifts to determination"

Available shot types: {{available_shot_types}}

Return ONLY valid JSON (no markdown, no explanation):
{
  "shotCount": number,
  "reasoning": "brief explanation of shot and DURATION choices based on scene type",
  "shots": [
    {
      "type": "shot_type_slug",
      "duration": number (MUST vary: 5 for close-ups/action, 6 for medium/standard, 10 for establishing/dialogue, 15-20 for long dialogue),
      "purpose": "why this shot fits the {{scene_type}} pattern",
      "cameraMovement": "specific movement (e.g., slow push-in, static, tracking left)",
      "subjectAction": "REQUIRED: what the subject/characters are doing (e.g. \'The subject looks around with growing awareness\', \'The subjects react with surprise\')",
      "needsLipSync": boolean
    }
  ]
}';
    }

    /**
     * Check if AI Shot Intelligence is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_intelligence_enabled', true);
    }

    // =====================================
    // CONTINUITY INTEGRATION (Phase 3)
    // =====================================

    /**
     * Add continuity analysis to shot breakdown.
     *
     * @param array $analysis The parsed shot analysis
     * @param array $context Scene context
     * @return array Analysis with continuity data added
     */
    protected function addContinuityAnalysis(array $analysis, array $context): array
    {
        // Check if continuity service is available and enabled
        if (!$this->continuityService || !$this->continuityService->isContinuityEnabled()) {
            $analysis['continuity'] = [
                'enabled' => false,
                'score' => null,
                'issues' => [],
                'suggestions' => [],
            ];
            return $analysis;
        }

        // Analyze shot sequence for continuity
        $shots = $analysis['shots'] ?? [];
        $continuityResult = $this->continuityService->analyzeSequence($shots);

        // Add continuity data to analysis
        $analysis['continuity'] = $continuityResult;

        // If auto-optimize is enabled and score is low, try to optimize
        if ($this->continuityService->isAutoOptimizationEnabled() &&
            $continuityResult['score'] < 70 &&
            count($shots) > 1) {

            $sceneType = $context['sceneType'] ?? VwSetting::getValue('shot_continuity_default_scene_type', 'dialogue');
            $optimized = $this->continuityService->optimizeSequence($shots, $sceneType);

            if ($optimized['improvement'] > 10) {
                $analysis['shots'] = $optimized['optimized'];
                $analysis['shotCount'] = count($optimized['optimized']);
                $analysis['continuity']['optimized'] = true;
                $analysis['continuity']['optimization'] = [
                    'originalScore' => $optimized['originalScore'],
                    'newScore' => $optimized['optimizedScore'],
                    'changes' => $optimized['changes'],
                ];

                // Recalculate total duration
                $totalDuration = 0;
                foreach ($analysis['shots'] as $shot) {
                    $totalDuration += $shot['duration'] ?? 6;
                }
                $analysis['totalDuration'] = $totalDuration;

                Log::info('ShotIntelligenceService: Auto-optimized shot sequence', [
                    'original_score' => $optimized['originalScore'],
                    'new_score' => $optimized['optimizedScore'],
                    'changes_count' => count($optimized['changes']),
                ]);
            }
        }

        return $analysis;
    }

    /**
     * Get coverage pattern suggestions for a scene.
     *
     * @param array $scene Scene data
     * @param array $context Additional context
     * @return array Coverage pattern with suggested shots
     */
    public function getCoveragePattern(array $scene, array $context = []): array
    {
        if (!$this->continuityService) {
            return [
                'enabled' => false,
                'pattern' => [],
                'sceneType' => 'unknown',
            ];
        }

        // Detect scene type
        $sceneType = $this->detectSceneType($scene, $context);

        // Get coverage pattern
        $pattern = $this->continuityService->getCoveragePattern($sceneType);

        return [
            'enabled' => true,
            'sceneType' => $sceneType,
            'pattern' => $pattern,
        ];
    }

    /**
     * Detect scene type from content.
     *
     * @param array $scene Scene data
     * @param array $context Additional context
     * @return string Detected scene type
     */
    protected function detectSceneType(array $scene, array $context): string
    {
        // Use explicitly provided scene type if available
        if (!empty($context['sceneType'])) {
            return $context['sceneType'];
        }

        $narration = strtolower($scene['narration'] ?? '');
        $visual = strtolower($scene['visualDescription'] ?? $scene['visual'] ?? '');
        $combined = $narration . ' ' . $visual;

        // Detect dialogue scenes
        $dialogueIndicators = ['says', 'asks', 'replies', 'speaks', 'tells', 'conversation', 'dialogue'];
        foreach ($dialogueIndicators as $indicator) {
            if (strpos($combined, $indicator) !== false) {
                return 'dialogue';
            }
        }

        // Detect action scenes
        $actionIndicators = ['fight', 'chase', 'run', 'explod', 'crash', 'battle', 'attack', 'escape'];
        foreach ($actionIndicators as $indicator) {
            if (strpos($combined, $indicator) !== false) {
                return 'action';
            }
        }

        // Detect emotional scenes
        $emotionalIndicators = ['cry', 'tears', 'embrace', 'grief', 'joy', 'heartbreak', 'emotional'];
        foreach ($emotionalIndicators as $indicator) {
            if (strpos($combined, $indicator) !== false) {
                return 'emotional';
            }
        }

        // Detect establishing/montage
        $establishingIndicators = ['establishing', 'overview', 'landscape', 'cityscape', 'exterior'];
        foreach ($establishingIndicators as $indicator) {
            if (strpos($combined, $indicator) !== false) {
                return 'establishing';
            }
        }

        // Default to dialogue (most common)
        return VwSetting::getValue('shot_continuity_default_scene_type', 'dialogue');
    }

    /**
     * Validate a user-modified shot sequence.
     *
     * @param array $shots Modified shot sequence
     * @return array Validation results
     */
    public function validateShotSequence(array $shots): array
    {
        if (!$this->continuityService || !$this->continuityService->isContinuityEnabled()) {
            return [
                'valid' => true,
                'enabled' => false,
            ];
        }

        return $this->continuityService->validateSequence($shots);
    }

    /**
     * Get next shot suggestions based on current shot.
     *
     * @param array $currentShot Current shot data
     * @param string $sceneType Type of scene
     * @param array $usedShots Previously used shot types
     * @return array Suggested next shots
     */
    public function suggestNextShot(array $currentShot, string $sceneType = 'dialogue', array $usedShots = []): array
    {
        if (!$this->continuityService) {
            return [];
        }

        return $this->continuityService->suggestNextShot($currentShot, $sceneType, $usedShots);
    }

    // =====================================
    // PROGRESSION INTEGRATION (Phase 6)
    // =====================================

    /**
     * Add progression analysis to shot breakdown.
     * Phase 6: Analyzes narrative progression, story beats, and action continuity.
     *
     * @param array $analysis The parsed shot analysis
     * @param array $scene Scene data
     * @param array $context Additional context (tensionCurve, emotionalJourney, sceneIndex, totalScenes)
     * @return array Analysis with progression data added
     */
    protected function addProgressionAnalysis(array $analysis, array $scene, array $context): array
    {
        // Check if progression service is available and enabled
        if (!$this->progressionService || !$this->progressionService->isEnabled()) {
            $analysis['progression'] = [
                'enabled' => false,
                'overallScore' => null,
                'shots' => [],
                'issues' => [],
            ];
            return $analysis;
        }

        try {
            // Build progression context from scene and wizard context
            $progressionContext = [
                'tensionCurve' => $context['tensionCurve'] ?? 'balanced',
                'emotionalJourney' => $context['emotionalJourney'] ?? 'hopeful-path',
                'sceneIndex' => $context['sceneIndex'] ?? 0,
                'totalScenes' => $context['totalScenes'] ?? 1,
                'sceneType' => $context['sceneType'] ?? 'dialogue',
                'genre' => $context['genre'] ?? 'drama',
                'pacing' => $context['pacing'] ?? 'balanced',
            ];

            // Analyze progression for all shots
            $progressionResult = $this->progressionService->analyzeProgression(
                $analysis['shots'] ?? [],
                $progressionContext
            );

            // Update shots with progression data (story beats, energy, mood)
            if (!empty($progressionResult['shots'])) {
                foreach ($progressionResult['shots'] as $index => $progressionData) {
                    if (isset($analysis['shots'][$index])) {
                        // Merge progression data into shot
                        $analysis['shots'][$index]['progression'] = $progressionData;

                        // If prompt enhancement is enabled, update the video prompt
                        if ($this->progressionService->isPromptEnhancementEnabled() &&
                            !empty($progressionData['promptEnhancement'])) {
                            $existingPrompt = $analysis['shots'][$index]['videoPrompt'] ?? '';
                            $analysis['shots'][$index]['videoPrompt'] = $this->enhancePromptWithProgression(
                                $existingPrompt,
                                $progressionData['promptEnhancement']
                            );
                        }
                    }
                }
            }

            // Add overall progression analysis to result
            $analysis['progression'] = [
                'enabled' => true,
                'overallScore' => $progressionResult['overallScore'] ?? 0,
                'issues' => $progressionResult['issues'] ?? [],
                'suggestions' => $progressionResult['suggestions'] ?? [],
                'energyCurve' => $progressionResult['energyCurve'] ?? [],
                'moodArc' => $progressionResult['moodArc'] ?? [],
            ];

            // Log progression analysis results
            if (!empty($progressionResult['issues'])) {
                Log::info('ShotIntelligenceService: Progression issues detected', [
                    'issue_count' => count($progressionResult['issues']),
                    'overall_score' => $progressionResult['overallScore'] ?? 0,
                ]);
            }

        } catch (\Throwable $e) {
            Log::warning('ShotIntelligenceService: Progression analysis failed', [
                'error' => $e->getMessage(),
            ]);

            $analysis['progression'] = [
                'enabled' => true,
                'error' => $e->getMessage(),
                'overallScore' => null,
                'shots' => [],
                'issues' => [],
            ];
        }

        return $analysis;
    }

    /**
     * Enhance a video prompt with progression context.
     *
     * @param string $existingPrompt The existing video prompt
     * @param string $progressionEnhancement The progression enhancement text
     * @return string Enhanced prompt
     */
    protected function enhancePromptWithProgression(string $existingPrompt, string $progressionEnhancement): string
    {
        if (empty($existingPrompt)) {
            return $progressionEnhancement;
        }

        if (empty($progressionEnhancement)) {
            return $existingPrompt;
        }

        // Append progression context to the prompt
        // Format: "Original prompt. [Progression context]"
        return trim($existingPrompt) . ' ' . trim($progressionEnhancement);
    }

    /**
     * Get progression analysis for an existing shot sequence.
     * Useful for re-analyzing shots after user modifications.
     *
     * @param array $shots Shot sequence
     * @param array $context Progression context
     * @return array Progression analysis results
     */
    public function getProgressionAnalysis(array $shots, array $context = []): array
    {
        if (!$this->progressionService || !$this->progressionService->isEnabled()) {
            return [
                'enabled' => false,
                'overallScore' => null,
                'shots' => [],
                'issues' => [],
            ];
        }

        return $this->progressionService->analyzeProgression($shots, $context);
    }

    /**
     * Validate action progression between shots.
     * Returns issues if consecutive shots have identical/similar actions.
     *
     * @param array $shots Shot sequence with subjectAction data
     * @return array Validation results with issues
     */
    public function validateActionProgression(array $shots): array
    {
        if (!$this->progressionService) {
            return [
                'valid' => true,
                'enabled' => false,
                'issues' => [],
            ];
        }

        $issues = [];
        $previousAction = null;

        foreach ($shots as $index => $shot) {
            $currentAction = $shot['subjectAction'] ?? $shot['action'] ?? '';

            if ($index > 0 && !empty($currentAction) && !empty($previousAction)) {
                $validation = $this->progressionService->validateActionStrings(
                    $currentAction,
                    $previousAction
                );

                if (!$validation['valid']) {
                    $issues[] = [
                        'shotIndex' => $index,
                        'type' => 'action_continuity',
                        'severity' => $validation['similarity'] > 95 ? 'high' : 'medium',
                        'message' => $validation['message'] ?? 'Consecutive shots have similar actions',
                        'similarity' => $validation['similarity'],
                        'suggestion' => $validation['suggestion'] ?? 'Add unique action progression',
                    ];
                }
            }

            $previousAction = $currentAction;
        }

        return [
            'valid' => empty($issues),
            'enabled' => true,
            'issues' => $issues,
        ];
    }
}
