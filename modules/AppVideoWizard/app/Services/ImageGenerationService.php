<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\GeminiService;
use App\Services\RunPodService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;
use Modules\AppVideoWizard\Models\WizardProcessingJob;
use Modules\AppVideoWizard\Services\StructuredPromptBuilderService;

class ImageGenerationService
{
    protected GeminiService $geminiService;
    protected RunPodService $runPodService;
    protected StructuredPromptBuilderService $structuredPromptBuilder;

    /**
     * VisualConsistencyService for Story Bible-enhanced prompts (Phase 4).
     */
    protected ?VisualConsistencyService $consistencyService = null;

    /**
     * Model configurations with token costs matching reference implementation.
     *
     * Correct Model IDs (as of Jan 2026):
     * - gemini-2.5-flash-image: Nano Banana - Fast, cost-effective, up to 1024px
     * - gemini-3-pro-image-preview: Nano Banana Pro - Best quality, 4K, supports up to 5 human refs
     *
     * @see https://ai.google.dev/gemini-api/docs/image-generation
     */
    public const IMAGE_MODELS = [
        'hidream' => [
            'name' => 'HiDream',
            'description' => 'Artistic & cinematic style',
            'tokenCost' => 2,
            'provider' => 'runpod',
            'async' => true,
        ],
        'nanobanana-pro' => [
            'name' => 'NanoBanana Pro',
            'description' => 'Best quality, superior face consistency (up to 5 reference faces), 4K output',
            'tokenCost' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-3-pro-image-preview', // Gemini 3 Pro Image (Nano Banana Pro)
            'resolution' => '4K', // Pro supports up to 4K
            'quality' => 'hd',
            'async' => false,
            'maxHumanRefs' => 5, // Supports up to 5 human reference images
        ],
        'nanobanana' => [
            'name' => 'NanoBanana',
            'description' => 'Quick drafts, good balance of speed and quality',
            'tokenCost' => 1,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash-image', // Gemini 2.5 Flash Image (Nano Banana)
            'resolution' => '1K', // Flash supports up to 1024px
            'quality' => 'basic',
            'async' => false,
            'maxHumanRefs' => 3, // Recommended max for flash
        ],
    ];

    public function __construct(GeminiService $geminiService, RunPodService $runPodService, StructuredPromptBuilderService $structuredPromptBuilder)
    {
        $this->geminiService = $geminiService;
        $this->runPodService = $runPodService;
        $this->structuredPromptBuilder = $structuredPromptBuilder;
        $this->consistencyService = new VisualConsistencyService();
    }

    /**
     * Generate a public URL for a storage path.
     * Uses /files/ prefix to bypass nginx interception of /storage/ paths.
     */
    protected function getPublicUrl(string $path): string
    {
        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Use /files/ prefix which routes through Laravel instead of nginx
        return url('/files/' . $path);
    }

    /**
     * Get available image models.
     */
    public function getModels(): array
    {
        return self::IMAGE_MODELS;
    }

    /**
     * Generate an image for a scene.
     */
    public function generateSceneImage(WizardProject $project, array $scene, array $options = []): array
    {
        $modelId = $options['model'] ?? $project->storyboard['imageModel'] ?? 'nanobanana';
        $modelConfig = self::IMAGE_MODELS[$modelId] ?? self::IMAGE_MODELS['nanobanana'];

        $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? '';
        $styleBible = $project->storyboard['styleBible'] ?? null;
        $visualStyle = $project->storyboard['visualStyle'] ?? null;

        // SceneMemory contains Bible data (Character, Location, Style references)
        // It can be stored in multiple locations depending on when it was saved
        $contentConfig = $project->content_config ?? [];
        $sceneMemory = $contentConfig['sceneMemory']
            ?? $project->storyboard['sceneMemory']
            ?? null;

        $sceneIndex = $options['sceneIndex'] ?? null;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $isLocationReference = $options['isLocationReference'] ?? false;
        $isCharacterPortrait = $options['isCharacterPortrait'] ?? false;
        $customNegativePrompt = $options['negativePrompt'] ?? null;

        // For location references and character portraits, use the visual description directly
        // These have specialized prompts that should not be modified by Bible integrations
        if ($isLocationReference || $isCharacterPortrait) {
            // Use prompt as-is, append negative prompt for Gemini
            $prompt = $visualDescription;
            if ($customNegativePrompt) {
                $prompt .= "\n\nCRITICAL - MUST AVOID (will ruin the image): {$customNegativePrompt}";
            }
        } else {
            // =================================================================
            // PHASE 3: Extract shot context from options for multi-shot mode
            // =================================================================
            $shotContext = [
                'shot_type' => $options['shot_type'] ?? null,
                'shot_purpose' => $options['shot_purpose'] ?? null,
                'shot_index' => $options['shot_index'] ?? null,
                'total_shots' => $options['total_shots'] ?? null,
                'story_beat' => $options['story_beat'] ?? null,
                'is_multi_shot' => $options['is_multi_shot'] ?? false,
            ];

            // Regular scene: build prompt with all Bible integrations
            $prompt = $this->buildImagePrompt($visualDescription, $styleBible, $visualStyle, $project, $sceneMemory, $sceneIndex, $shotContext);
        }

        // Get resolution based on aspect ratio
        // For collage previews, use 1:1 aspect ratio for proper 2x2 grid layout
        $isCollagePreview = $options['is_collage_preview'] ?? false;
        $aspectRatio = $isCollagePreview ? '1:1' : $project->aspect_ratio;
        $resolution = $this->getResolution($aspectRatio);

        // Check credits
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            throw new \Exception($quota['message']);
        }

        // Initialize references as null
        $characterReference = null;
        $locationReference = null;
        $styleReference = null;

        // Check for direct style reference override (used for regeneration from collage)
        $directStyleReference = $options['directStyleReference'] ?? null;
        if ($directStyleReference && !empty($directStyleReference['base64'])) {
            // Use the direct style reference instead of Bible references
            $styleReference = $directStyleReference;
            Log::info('[ImageGeneration] Using direct style reference override', [
                'hasBase64' => true,
                'mimeType' => $directStyleReference['mimeType'] ?? 'image/png',
                'hasStyleDescription' => !empty($directStyleReference['styleDescription']),
            ]);
        } elseif (!$isLocationReference && !$isCharacterPortrait) {
            // For location references: NO character references (empty environments only)
            // For character portraits: NO location references (studio backdrop)
            // For regular scenes: use all available references
            // Regular scene: get all references for consistency
            $characterReference = $this->getCharacterReferenceForScene($sceneMemory, $sceneIndex);
            $locationReference = $this->getLocationReferenceForScene($sceneMemory, $sceneIndex);
            $styleReference = $this->getStyleReference($sceneMemory);
        } elseif ($isCharacterPortrait) {
            // Character portrait: only use style reference for visual consistency
            $styleReference = $this->getStyleReference($sceneMemory);
        }
        // Location references: no references needed (pure environment)

        // Route to appropriate provider
        if ($modelConfig['provider'] === 'runpod') {
            return $this->generateWithHiDream($project, $scene, $prompt, $resolution, $options);
        } else {
            return $this->generateWithGemini($project, $scene, $prompt, $resolution, $modelId, $modelConfig, $options, $characterReference, $locationReference, $styleReference);
        }
    }

    /**
     * Generate an image with visual consistency enhancement (Phase 4).
     * Uses Story Bible to ensure character/location/style consistency.
     */
    public function generateWithConsistency(WizardProject $project, array $scene, array $options = []): array
    {
        // Build consistency-enhanced prompt using VisualConsistencyService
        $consistencyMode = $options['consistencyMode'] ?? 'auto';
        $consistencyResult = $this->consistencyService->buildConsistentPrompt($project, $scene, [
            'consistencyMode' => $consistencyMode,
        ]);

        Log::info('ImageGeneration: Using consistency-enhanced prompt', [
            'mode' => $consistencyMode,
            'consistencyApplied' => $consistencyResult['consistencyApplied'],
            'detectedCharacters' => count($consistencyResult['detectedCharacters']),
            'detectedLocations' => count($consistencyResult['detectedLocations']),
        ]);

        // Override the visual description with the enhanced prompt
        $enhancedScene = $scene;
        $enhancedScene['visualDescription'] = $consistencyResult['prompt'];

        // Generate the image with the enhanced prompt
        $result = $this->generateSceneImage($project, $enhancedScene, $options);

        // Add consistency metadata to the result
        $result['consistency'] = [
            'mode' => $consistencyMode,
            'applied' => $consistencyResult['consistencyApplied'],
            'characters' => $consistencyResult['detectedCharacters'],
            'locations' => $consistencyResult['detectedLocations'],
            'styleApplied' => $consistencyResult['styleApplied'],
        ];

        return $result;
    }

    /**
     * Generate scene image with reference to previous scene for visual continuity.
     *
     * This method ensures scene-to-scene consistency by:
     * 1. Extracting style anchors (color grading, lighting, atmosphere) from reference scene
     * 2. Injecting continuity markers into the prompt
     * 3. Maintaining visual DNA across the entire video
     *
     * @param WizardProject $project The project
     * @param array $scene The scene to generate
     * @param array $referenceStyle Style anchors extracted from reference scene
     * @param array $options Additional generation options
     * @return array Generation result with continuity metadata
     */
    public function generateSceneWithReference(
        WizardProject $project,
        array $scene,
        array $referenceStyle,
        array $options = []
    ): array {
        $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? '';

        // Build continuity-enhanced prompt
        $continuityParts = [];

        // Start with the original visual description
        $continuityParts[] = $visualDescription;

        // Add style continuity instructions
        $styleInstructions = [];

        if (!empty($referenceStyle['colorGrading'])) {
            $styleInstructions[] = "maintain exact color grading: {$referenceStyle['colorGrading']}";
        }

        if (!empty($referenceStyle['lightingStyle'])) {
            $styleInstructions[] = "match lighting style: {$referenceStyle['lightingStyle']}";
        }

        if (!empty($referenceStyle['filmLook'])) {
            $styleInstructions[] = "preserve film look: {$referenceStyle['filmLook']}";
        }

        if (!empty($referenceStyle['atmosphere'])) {
            $styleInstructions[] = "continue atmosphere: {$referenceStyle['atmosphere']}";
        }

        if (!empty($referenceStyle['palette'])) {
            $styleInstructions[] = "use color palette: {$referenceStyle['palette']}";
        }

        // Add continuity block to prompt
        if (!empty($styleInstructions)) {
            $continuityBlock = implode('. ', $styleInstructions);
            $continuityParts[] = "VISUAL CONTINUITY (CRITICAL - match previous scene exactly): {$continuityBlock}";
        }

        // Build enhanced scene with continuity prompt
        $enhancedScene = $scene;
        $enhancedScene['visualDescription'] = implode('. ', $continuityParts);

        Log::info('ImageGeneration: Generating with scene reference', [
            'hasColorGrading' => !empty($referenceStyle['colorGrading']),
            'hasLighting' => !empty($referenceStyle['lightingStyle']),
            'hasFilmLook' => !empty($referenceStyle['filmLook']),
            'hasAtmosphere' => !empty($referenceStyle['atmosphere']),
            'promptLength' => strlen($enhancedScene['visualDescription']),
        ]);

        // Generate with enhanced prompt
        $result = $this->generateSceneImage($project, $enhancedScene, $options);

        // Add continuity metadata
        $result['continuity'] = [
            'applied' => true,
            'referenceStyle' => $referenceStyle,
            'styleInstructions' => $styleInstructions,
        ];

        return $result;
    }

    /**
     * Extract style anchors from an existing scene's prompt for continuity.
     *
     * @param string $prompt The prompt used for the reference scene
     * @param array $analysisData Optional image analysis data
     * @return array Style anchors for continuity
     */
    public function extractStyleAnchorsFromPrompt(string $prompt, array $analysisData = []): array
    {
        $anchors = [
            'colorGrading' => '',
            'lightingStyle' => '',
            'filmLook' => '',
            'atmosphere' => '',
            'palette' => '',
        ];

        $prompt = strtolower($prompt);

        // Extract color grading
        $colorPatterns = [
            'teal' => 'teal and orange split toning',
            'warm' => 'warm color temperature',
            'cool' => 'cool color temperature',
            'desaturated' => 'desaturated muted palette',
            'vibrant' => 'vibrant saturated colors',
            'muted' => 'muted earth tones',
            'golden' => 'golden warm tones',
            'neon' => 'neon color accents',
            'crushed blacks' => 'crushed blacks high contrast',
            'lifted blacks' => 'lifted blacks low contrast',
        ];

        foreach ($colorPatterns as $keyword => $grade) {
            if (str_contains($prompt, $keyword)) {
                $anchors['colorGrading'] = $grade;
                break;
            }
        }

        // Extract lighting style
        $lightingPatterns = [
            'dramatic' => 'dramatic high-contrast lighting',
            'soft' => 'soft diffused lighting',
            'harsh' => 'harsh directional lighting',
            'chiaroscuro' => 'chiaroscuro contrast',
            'low-key' => 'low-key dramatic shadows',
            'high-key' => 'high-key bright even',
            'volumetric' => 'volumetric light rays',
            'backlit' => 'backlit rim lighting',
            'rim light' => 'rim lighting separation',
            'golden hour' => 'golden hour warm directional',
            'blue hour' => 'blue hour cool ambient',
            'natural' => 'natural available lighting',
        ];

        foreach ($lightingPatterns as $keyword => $style) {
            if (str_contains($prompt, $keyword)) {
                $anchors['lightingStyle'] = $style;
                break;
            }
        }

        // Extract film look
        $filmPatterns = [
            'cinematic' => 'cinematic film look',
            'film grain' => 'organic film grain texture',
            'anamorphic' => 'anamorphic lens characteristics',
            'documentary' => 'documentary realism',
            'noir' => 'film noir aesthetic',
            'vintage' => 'vintage film stock',
            'modern' => 'modern clean digital',
            'shallow depth' => 'shallow depth of field',
            'bokeh' => 'bokeh background blur',
        ];

        foreach ($filmPatterns as $keyword => $look) {
            if (str_contains($prompt, $keyword)) {
                $anchors['filmLook'] = $look;
                break;
            }
        }

        // Extract atmosphere
        $atmospherePatterns = [
            'moody' => 'moody intimate atmosphere',
            'epic' => 'epic grand atmosphere',
            'tense' => 'tense suspenseful atmosphere',
            'romantic' => 'romantic soft atmosphere',
            'mysterious' => 'mysterious enigmatic atmosphere',
            'peaceful' => 'peaceful serene atmosphere',
            'energetic' => 'energetic dynamic atmosphere',
            'dark' => 'dark brooding atmosphere',
            'bright' => 'bright uplifting atmosphere',
        ];

        foreach ($atmospherePatterns as $keyword => $atmos) {
            if (str_contains($prompt, $keyword)) {
                $anchors['atmosphere'] = $atmos;
                break;
            }
        }

        // Include analysis data if provided (from AI image analysis)
        if (!empty($analysisData)) {
            if (!empty($analysisData['dominantColors'])) {
                $anchors['palette'] = implode(', ', $analysisData['dominantColors']);
            }
            if (!empty($analysisData['lighting']) && empty($anchors['lightingStyle'])) {
                $anchors['lightingStyle'] = $analysisData['lighting'];
            }
            if (!empty($analysisData['mood']) && empty($anchors['atmosphere'])) {
                $anchors['atmosphere'] = $analysisData['mood'];
            }
        }

        return $anchors;
    }

    /**
     * Batch generate images with visual consistency for all scenes.
     * Ensures consistent character/location appearances across the storyboard.
     */
    public function batchGenerateWithConsistency(WizardProject $project, array $scenes, array $options = []): array
    {
        $consistencyMode = $options['consistencyMode'] ?? 'auto';

        // First, generate all consistency-enhanced prompts
        $batchResult = $this->consistencyService->generateBatchConsistentPrompts($project, $scenes, [
            'consistencyMode' => $consistencyMode,
        ]);

        Log::info('ImageGeneration: Starting batch generation with consistency', [
            'totalScenes' => count($scenes),
            'avgConsistencyScore' => $batchResult['statistics']['averageConsistencyScore'],
            'charactersUsed' => count($batchResult['statistics']['uniqueCharactersUsed']),
            'locationsUsed' => count($batchResult['statistics']['uniqueLocationsUsed']),
        ]);

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($batchResult['prompts'] as $promptData) {
            $sceneIndex = $promptData['sceneIndex'];
            $scene = $scenes[$sceneIndex] ?? null;

            if (!$scene) {
                continue;
            }

            try {
                // Create enhanced scene with consistency prompt
                $enhancedScene = $scene;
                $enhancedScene['visualDescription'] = $promptData['prompt'];

                // Generate image
                $imageResult = $this->generateSceneImage($project, $enhancedScene, array_merge($options, [
                    'sceneIndex' => $sceneIndex,
                ]));

                $imageResult['consistency'] = $promptData['consistency'];
                $imageResult['sceneIndex'] = $sceneIndex;

                $results[] = $imageResult;
                $successCount++;

            } catch (\Exception $e) {
                Log::error('ImageGeneration: Batch consistency generation failed for scene', [
                    'sceneIndex' => $sceneIndex,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'success' => false,
                    'sceneIndex' => $sceneIndex,
                    'error' => $e->getMessage(),
                    'consistency' => $promptData['consistency'],
                ];
                $failCount++;
            }
        }

        return [
            'results' => $results,
            'statistics' => array_merge($batchResult['statistics'], [
                'successCount' => $successCount,
                'failCount' => $failCount,
            ]),
        ];
    }

    /**
     * Analyze visual consistency for a project's storyboard.
     */
    public function analyzeStoryboardConsistency(WizardProject $project): array
    {
        $scenes = $project->script['scenes'] ?? [];

        if (empty($scenes)) {
            return [
                'overallScore' => 0,
                'status' => 'no_scenes',
                'scenes' => [],
            ];
        }

        $sceneAnalyses = [];
        $totalScore = 0;

        foreach ($scenes as $index => $scene) {
            $analysis = $this->consistencyService->analyzeSceneConsistency($project, $scene);
            $analysis['sceneIndex'] = $index;
            $analysis['sceneTitle'] = $scene['title'] ?? "Scene " . ($index + 1);

            $sceneAnalyses[] = $analysis;
            $totalScore += $analysis['score'];
        }

        $avgScore = count($sceneAnalyses) > 0 ? round($totalScore / count($sceneAnalyses), 1) : 0;

        // Determine overall status
        $status = 'excellent';
        if ($avgScore < 90) $status = 'good';
        if ($avgScore < 70) $status = 'fair';
        if ($avgScore < 50) $status = 'needs_attention';

        return [
            'overallScore' => $avgScore,
            'status' => $status,
            'scenes' => $sceneAnalyses,
            'hasStoryBible' => $project->hasStoryBible(),
            'totalScenes' => count($scenes),
        ];
    }

    /**
     * Get character reference image for a scene from Character Bible.
     * This enables face/identity consistency across scene images.
     *
     * @param array|null $sceneMemory The scene memory containing Character Bible
     * @param int|null $sceneIndex The scene index to get characters for
     * @return array|null Character reference data {base64, mimeType, characterName} or null
     */
    protected function getCharacterReferenceForScene(?array $sceneMemory, ?int $sceneIndex): ?array
    {
        if (!$sceneMemory) {
            return null;
        }

        $characterBible = $sceneMemory['characterBible'] ?? null;
        if (!$characterBible || !($characterBible['enabled'] ?? false)) {
            Log::debug('[getCharacterReferenceForScene] Character Bible disabled');
            return null;
        }

        $characters = $characterBible['characters'] ?? [];
        if (empty($characters)) {
            return null;
        }

        // Get characters applicable to this scene
        $sceneCharacters = $this->getCharactersForScene($characters, $sceneIndex);

        Log::debug('[getCharacterReferenceForScene] Scene characters', [
            'sceneIndex' => $sceneIndex,
            'totalCharacters' => count($characters),
            'sceneCharacters' => count($sceneCharacters),
        ]);

        // Find the first character with a ready reference image (base64)
        foreach ($sceneCharacters as $character) {
            $hasBase64 = !empty($character['referenceImageBase64']);
            $isReady = ($character['referenceImageStatus'] ?? '') === 'ready';

            if ($hasBase64 && $isReady) {
                Log::info('[getCharacterReferenceForScene] Using character reference', [
                    'characterName' => $character['name'] ?? 'Unknown',
                    'base64Length' => strlen($character['referenceImageBase64']),
                    'mimeType' => $character['referenceImageMimeType'] ?? 'image/png',
                    'hasLookSystem' => !empty($character['hair']) || !empty($character['wardrobe']),
                ]);

                return [
                    'base64' => $character['referenceImageBase64'],
                    'mimeType' => $character['referenceImageMimeType'] ?? 'image/png',
                    'characterName' => $character['name'] ?? 'Character',
                    'characterDescription' => $character['description'] ?? '',
                    // Character Look System fields for Hollywood consistency
                    'hair' => $character['hair'] ?? [],
                    'wardrobe' => $character['wardrobe'] ?? [],
                    'makeup' => $character['makeup'] ?? [],
                    'accessories' => $character['accessories'] ?? [],
                ];
            }
        }

        Log::debug('[getCharacterReferenceForScene] No character with base64 portrait found');
        return null;
    }

    /**
     * Get location reference image for a scene from Location Bible.
     * This enables location/environment visual consistency across scene images.
     *
     * @param array|null $sceneMemory The scene memory containing Location Bible
     * @param int|null $sceneIndex The scene index to get location for
     * @return array|null Location reference data {base64, mimeType, locationName, locationDescription} or null
     */
    protected function getLocationReferenceForScene(?array $sceneMemory, ?int $sceneIndex): ?array
    {
        if (!$sceneMemory) {
            return null;
        }

        $locationBible = $sceneMemory['locationBible'] ?? null;
        if (!$locationBible || !($locationBible['enabled'] ?? false)) {
            Log::debug('[getLocationReferenceForScene] Location Bible disabled');
            return null;
        }

        $locations = $locationBible['locations'] ?? [];
        if (empty($locations)) {
            return null;
        }

        // Get location applicable to this scene
        $sceneLocation = $this->getLocationForScene($locations, $sceneIndex);

        if (!$sceneLocation) {
            Log::debug('[getLocationReferenceForScene] No location assigned to scene', [
                'sceneIndex' => $sceneIndex,
            ]);
            return null;
        }

        // Check if location has a ready reference image (base64)
        $hasBase64 = !empty($sceneLocation['referenceImageBase64']);
        $isReady = ($sceneLocation['referenceImageStatus'] ?? '') === 'ready';

        if ($hasBase64 && $isReady) {
            Log::info('[getLocationReferenceForScene] Using location reference', [
                'locationName' => $sceneLocation['name'] ?? 'Unknown',
                'base64Length' => strlen($sceneLocation['referenceImageBase64']),
                'mimeType' => $sceneLocation['referenceImageMimeType'] ?? 'image/png',
            ]);

            return [
                'base64' => $sceneLocation['referenceImageBase64'],
                'mimeType' => $sceneLocation['referenceImageMimeType'] ?? 'image/png',
                'locationName' => $sceneLocation['name'] ?? 'Location',
                'locationDescription' => $sceneLocation['description'] ?? '',
                'type' => $sceneLocation['type'] ?? 'exterior',
                'timeOfDay' => $sceneLocation['timeOfDay'] ?? 'day',
                'weather' => $sceneLocation['weather'] ?? 'clear',
                'atmosphere' => $sceneLocation['atmosphere'] ?? '',
            ];
        }

        Log::debug('[getLocationReferenceForScene] No location with base64 reference found');
        return null;
    }

    /**
     * Get style reference image from Style Bible.
     * This enables visual style consistency (colors, lighting, mood) across all scene images.
     * Style Bible is global - applies to ALL scenes.
     *
     * IMPROVED: Now extracts detailed style attributes for 6-element style anchoring
     * based on Gemini 2.5 Flash best practices (88% style fidelity improvement).
     *
     * @param array|null $sceneMemory The scene memory containing Style Bible
     * @return array|null Style reference data with detailed attributes or null
     */
    protected function getStyleReference(?array $sceneMemory): ?array
    {
        if (!$sceneMemory) {
            return null;
        }

        $styleBible = $sceneMemory['styleBible'] ?? null;
        if (!$styleBible || !($styleBible['enabled'] ?? false)) {
            Log::debug('[getStyleReference] Style Bible disabled');
            return null;
        }

        // Check if style bible has a ready reference image (base64)
        $hasBase64 = !empty($styleBible['referenceImageBase64']);
        $isReady = ($styleBible['referenceImageStatus'] ?? '') === 'ready';

        if ($hasBase64 && $isReady) {
            // Build comprehensive style description using 6-element anchoring
            $styleElements = [];

            // 1. Visual style (medium, aesthetic)
            if (!empty($styleBible['style'])) {
                $styleElements['style'] = $styleBible['style'];
            }

            // 2. Color grading
            if (!empty($styleBible['colorGrade'])) {
                $styleElements['colorGrade'] = $styleBible['colorGrade'];
            }

            // 3. Atmosphere/mood
            if (!empty($styleBible['atmosphere'])) {
                $styleElements['atmosphere'] = $styleBible['atmosphere'];
            }

            // 4. Camera/lens characteristics
            if (!empty($styleBible['camera'])) {
                $styleElements['camera'] = $styleBible['camera'];
            }

            // Build style description string for the prompt
            $styleDescription = $this->buildComprehensiveStyleDescription($styleElements);

            Log::info('[getStyleReference] Using style reference with 6-element anchoring', [
                'base64Length' => strlen($styleBible['referenceImageBase64']),
                'mimeType' => $styleBible['referenceImageMimeType'] ?? 'image/png',
                'hasStyleDescription' => !empty($styleDescription),
                'styleElements' => array_keys($styleElements),
            ]);

            return [
                'base64' => $styleBible['referenceImageBase64'],
                'mimeType' => $styleBible['referenceImageMimeType'] ?? 'image/png',
                'styleDescription' => $styleDescription,
                'visualDNA' => $styleBible['visualDNA'] ?? '',
                // Include raw elements for potential future use
                'styleElements' => $styleElements,
            ];
        }

        Log::debug('[getStyleReference] No style with base64 reference found');
        return null;
    }

    /**
     * Build comprehensive style description for 6-element anchoring.
     *
     * @param array $styleElements Style elements from Style Bible
     * @return string Comprehensive style description
     */
    protected function buildComprehensiveStyleDescription(array $styleElements): string
    {
        $parts = [];

        // Visual style/medium
        if (!empty($styleElements['style'])) {
            $parts[] = "Visual Style: {$styleElements['style']}";
        }

        // Color grading with enhanced descriptions
        if (!empty($styleElements['colorGrade'])) {
            $colorGrade = $styleElements['colorGrade'];
            $colorLower = strtolower($colorGrade);

            // Enhance color grading description with specific details
            $colorDetails = [];
            if (str_contains($colorLower, 'teal') && str_contains($colorLower, 'orange')) {
                $colorDetails[] = 'teal-orange complementary split';
            }
            if (str_contains($colorLower, 'warm')) {
                $colorDetails[] = 'warm color temperature';
            }
            if (str_contains($colorLower, 'cool') || str_contains($colorLower, 'cold')) {
                $colorDetails[] = 'cool color temperature';
            }
            if (str_contains($colorLower, 'desaturated') || str_contains($colorLower, 'muted')) {
                $colorDetails[] = 'reduced saturation';
            }
            if (str_contains($colorLower, 'vibrant') || str_contains($colorLower, 'saturated')) {
                $colorDetails[] = 'high saturation';
            }
            if (str_contains($colorLower, 'lifted')) {
                $colorDetails[] = 'lifted blacks';
            }
            if (str_contains($colorLower, 'crushed')) {
                $colorDetails[] = 'crushed blacks';
            }

            $colorDesc = $colorGrade;
            if (!empty($colorDetails)) {
                $colorDesc .= ' (' . implode(', ', $colorDetails) . ')';
            }
            $parts[] = "Color Grading: {$colorDesc}";
        }

        // Atmosphere/mood with lighting implications
        if (!empty($styleElements['atmosphere'])) {
            $atmosphere = $styleElements['atmosphere'];
            $atmosphereLower = strtolower($atmosphere);

            // Enhance with lighting details
            $lightingDetails = [];
            if (str_contains($atmosphereLower, 'moody') || str_contains($atmosphereLower, 'dark')) {
                $lightingDetails[] = 'low-key lighting';
            }
            if (str_contains($atmosphereLower, 'bright') || str_contains($atmosphereLower, 'airy')) {
                $lightingDetails[] = 'high-key lighting';
            }
            if (str_contains($atmosphereLower, 'volumetric')) {
                $lightingDetails[] = 'volumetric light rays';
            }
            if (str_contains($atmosphereLower, 'dramatic')) {
                $lightingDetails[] = 'dramatic contrast';
            }

            $atmosphereDesc = $atmosphere;
            if (!empty($lightingDetails)) {
                $atmosphereDesc .= ' with ' . implode(', ', $lightingDetails);
            }
            $parts[] = "Atmosphere: {$atmosphereDesc}";
        }

        // Camera/lens characteristics
        if (!empty($styleElements['camera'])) {
            $parts[] = "Camera: {$styleElements['camera']}";
        }

        if (empty($parts)) {
            return 'Cinematic visual style with professional color grading';
        }

        return implode('. ', $parts);
    }

    /**
     * Generate image using HiDream via RunPod (async).
     */
    protected function generateWithHiDream(
        WizardProject $project,
        array $scene,
        string $prompt,
        array $resolution,
        array $options = []
    ): array {
        // Check API key first
        $apiKey = get_option('runpod_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('RunPod API key not configured. Go to Admin → AI Configuration → RunPod to add your API key.');
        }

        $endpointId = get_option('runpod_hidream_endpoint', '');
        if (empty($endpointId)) {
            throw new \Exception('HiDream endpoint not configured. Go to Admin → AI Configuration → RunPod to add your HiDream endpoint ID.');
        }

        // HiDream generation settings - send only essential params
        // The worker will use defaults for anything not specified
        $input = [
            'prompt' => $prompt,
            'width' => $resolution['width'],
            'height' => $resolution['height'],
            'num_inference_steps' => 35,
            'guidance_scale' => 5.0,
        ];

        // Add negative prompt if available (HiDream supports this natively)
        $negativePrompt = $project->getAttribute('_lastNegativePrompt') ?? $options['negativePrompt'] ?? null;
        if (!empty($negativePrompt)) {
            $input['negative_prompt'] = $negativePrompt;
        }

        Log::info('HiDream generation request', [
            'endpointId' => $endpointId,
            'prompt' => substr($prompt, 0, 100) . '...',
            'width' => $resolution['width'],
            'height' => $resolution['height'],
            'hasNegativePrompt' => !empty($negativePrompt),
        ]);

        // Refresh API key in case it was recently updated
        $this->runPodService->refreshApiKey();

        // Start async job
        $result = $this->runPodService->runAsync($endpointId, $input);

        Log::info('HiDream RunPod response', ['result' => $result]);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to start HiDream generation');
        }

        // Create processing job record for polling
        $job = WizardProcessingJob::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id ?? auth()->id(),
            'type' => WizardProcessingJob::TYPE_IMAGE_GENERATION,
            'external_provider' => 'runpod',
            'external_job_id' => $result['id'],
            'status' => WizardProcessingJob::STATUS_PROCESSING,
            'input_data' => [
                'sceneId' => $scene['id'],
                'sceneIndex' => $options['sceneIndex'] ?? null,
                'model' => 'hidream',
                'endpointId' => $endpointId,
                'prompt' => $prompt,
            ],
        ]);

        return [
            'success' => true,
            'status' => 'generating',
            'jobId' => $result['id'],
            'processingJobId' => $job->id,
            'imageUrl' => null,
            'prompt' => $prompt,
            'model' => 'hidream',
            'async' => true,
        ];
    }

    /**
     * Poll for HiDream job completion.
     */
    public function pollHiDreamJob(WizardProcessingJob $job): array
    {
        $inputData = $job->input_data ?? [];
        $endpointId = $inputData['endpointId'] ?? get_option('runpod_hidream_endpoint', '');

        $status = $this->runPodService->getStatus($endpointId, $job->external_job_id);

        if (!$status['success']) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $status['error'],
            ];
        }

        if ($status['status'] === 'COMPLETED') {
            $output = $status['output'] ?? [];

            // Log the output structure for debugging
            Log::info('HiDream RunPod output', [
                'jobId' => $job->external_job_id,
                'output' => $output,
                'outputType' => gettype($output),
            ]);

            // Extract image URL from various possible output formats
            $imageUrl = $this->extractImageUrlFromOutput($output);

            if ($imageUrl) {
                // Download and store the image
                $project = WizardProject::find($job->project_id);
                $sceneId = $inputData['sceneId'] ?? 'scene';

                // Handle both URL and base64 data
                $storedPath = null;
                if (str_starts_with($imageUrl, 'data:image/')) {
                    // Data URL format: data:image/png;base64,xxxxx
                    $parts = explode(',', $imageUrl, 2);
                    $base64Data = $parts[1] ?? $imageUrl;
                    $mimeType = 'image/png';
                    if (preg_match('/data:([^;]+);/', $imageUrl, $matches)) {
                        $mimeType = $matches[1];
                    }
                    $storedPath = $this->storeBase64Image($base64Data, $mimeType, $project, $sceneId);
                } elseif (preg_match('/^[a-zA-Z0-9+\/]{100,}={0,2}$/', $imageUrl)) {
                    // Raw base64 data (no data: prefix)
                    $storedPath = $this->storeBase64Image($imageUrl, 'image/png', $project, $sceneId);
                } else {
                    // Regular URL - download and store
                    $storedPath = $this->storeImage($imageUrl, $project, $sceneId);
                }
                $publicUrl = $this->getPublicUrl($storedPath);

                // Create asset record
                $asset = WizardAsset::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id,
                    'type' => WizardAsset::TYPE_IMAGE,
                    'name' => $sceneId,
                    'path' => $storedPath,
                    'url' => $publicUrl,
                    'mime_type' => 'image/png',
                    'scene_index' => $inputData['sceneIndex'] ?? null,
                    'scene_id' => $sceneId,
                    'metadata' => [
                        'prompt' => $inputData['prompt'] ?? '',
                        'model' => 'hidream',
                        'jobId' => $job->external_job_id,
                    ],
                ]);

                // Update job status
                $job->markAsCompleted([
                    'imageUrl' => $publicUrl,
                    'assetId' => $asset->id,
                ]);

                return [
                    'success' => true,
                    'status' => 'ready',
                    'imageUrl' => $publicUrl,
                    'assetId' => $asset->id,
                    'sceneIndex' => $inputData['sceneIndex'] ?? null,
                ];
            } else {
                // Job completed but no image URL found in output
                Log::error('HiDream job completed but no image URL found', [
                    'jobId' => $job->external_job_id,
                    'output' => $output,
                ]);

                $job->markAsFailed('Job completed but no image URL found in output');

                return [
                    'success' => false,
                    'status' => 'error',
                    'error' => 'Image generation completed but output format not recognized. Check logs for details.',
                ];
            }
        }

        if (in_array($status['status'], ['FAILED', 'CANCELLED', 'TIMED_OUT'])) {
            $job->markAsFailed($status['error'] ?? $status['status']);

            return [
                'success' => false,
                'status' => 'error',
                'error' => $status['error'] ?? 'Job failed: ' . $status['status'],
            ];
        }

        // Still processing
        return [
            'success' => true,
            'status' => 'generating',
            'runpodStatus' => $status['status'],
        ];
    }

    /**
     * Extract image URL from various RunPod/ComfyUI worker output formats.
     *
     * Handles multiple output formats:
     * - { "image": "url" } - direct image URL
     * - { "image_url": "url" } - alternative key
     * - { "images": ["url1", "url2"] } - ComfyUI array format
     * - { "message": "url" } - message format from some workers
     * - "url" - direct string output
     * - { "status": "success", "message": "url" } - status wrapper format
     */
    protected function extractImageUrlFromOutput($output): ?string
    {
        // If output is null or empty
        if (empty($output)) {
            Log::warning('HiDream output is empty');
            return null;
        }

        // If output is already a string URL
        if (is_string($output)) {
            if (filter_var($output, FILTER_VALIDATE_URL)) {
                return $output;
            }
            // Could be base64 data
            if (str_starts_with($output, 'data:image/') || preg_match('/^[a-zA-Z0-9+\/=]+$/', $output)) {
                Log::info('HiDream output appears to be base64 data');
                return $output;
            }
        }

        // If output is not an array, we can't extract
        if (!is_array($output)) {
            Log::warning('HiDream output is not an array', ['type' => gettype($output)]);
            return null;
        }

        // Try direct image keys first
        if (!empty($output['image'])) {
            return $output['image'];
        }

        if (!empty($output['image_url'])) {
            return $output['image_url'];
        }

        // ComfyUI worker format: { "images": ["url1", "url2", ...] }
        if (!empty($output['images']) && is_array($output['images'])) {
            // Return first image from array
            $firstImage = $output['images'][0] ?? null;
            if ($firstImage) {
                // Could be URL string or nested object
                if (is_string($firstImage)) {
                    return $firstImage;
                }
                if (is_array($firstImage) && !empty($firstImage['url'])) {
                    return $firstImage['url'];
                }
            }
        }

        // Message format from some workers
        if (!empty($output['message'])) {
            $message = $output['message'];
            if (is_string($message) && filter_var($message, FILTER_VALIDATE_URL)) {
                return $message;
            }
            // Sometimes message contains the URL as part of text
            if (is_string($message) && preg_match('/(https?:\/\/[^\s]+\.(png|jpg|jpeg|webp))/i', $message, $matches)) {
                return $matches[1];
            }
        }

        // Status wrapper format: { "status": "success", "output": { ... } }
        if (!empty($output['output']) && is_array($output['output'])) {
            return $this->extractImageUrlFromOutput($output['output']);
        }

        // RunPod storage URL format - sometimes in nested structure
        if (!empty($output['data'])) {
            if (is_string($output['data']) && filter_var($output['data'], FILTER_VALIDATE_URL)) {
                return $output['data'];
            }
            if (is_array($output['data'])) {
                return $this->extractImageUrlFromOutput($output['data']);
            }
        }

        // Look for any URL-like value in the output
        foreach ($output as $key => $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                if (preg_match('/\.(png|jpg|jpeg|webp|gif)(\?.*)?$/i', $value)) {
                    Log::info("Found image URL in key '{$key}'", ['url' => $value]);
                    return $value;
                }
            }
        }

        Log::warning('Could not extract image URL from HiDream output', [
            'outputKeys' => is_array($output) ? array_keys($output) : 'not_array',
        ]);

        return null;
    }

    /**
     * Generate image using Gemini (NanoBanana).
     *
     * @param WizardProject $project The project
     * @param array $scene The scene data
     * @param string $prompt The image prompt
     * @param array $resolutionArray Image resolution array with width/height for metadata
     * @param string $modelId Model ID
     * @param array $modelConfig Model configuration
     * @param array $options Additional options
     * @param array|null $characterReference Character reference for face consistency {base64, mimeType, characterName}
     * @param array|null $locationReference Location reference for environment consistency {base64, mimeType, locationName}
     * @param array|null $styleReference Style reference for visual style consistency {base64, mimeType, styleDescription}
     */
    protected function generateWithGemini(
        WizardProject $project,
        array $scene,
        string $prompt,
        array $resolutionArray,
        string $modelId,
        array $modelConfig,
        array $options = [],
        ?array $characterReference = null,
        ?array $locationReference = null,
        ?array $styleReference = null
    ): array {
        // Map aspect ratio for Gemini
        $aspectRatioMap = [
            '16:9' => '16:9',
            '9:16' => '9:16',
            '1:1' => '1:1',
            '4:3' => '4:3',
            '4:5' => '3:4',
        ];

        // Determine the aspect ratio to use
        // For collage previews, use 1:1 (passed via resolutionArray['size'] as '1024x1024')
        $isCollagePreview = $options['is_collage_preview'] ?? false;
        if ($isCollagePreview) {
            $aspectRatio = '1:1';
        } else {
            $aspectRatio = $aspectRatioMap[$project->aspect_ratio] ?? '16:9';
        }

        // Get model resolution string (1K, 2K, 4K) for API call
        $modelResolution = $modelConfig['resolution'] ?? '2K';

        // Priority: Character reference (face consistency) > Location reference (environment consistency)
        // If we have a character reference, use image-to-image generation for face consistency
        if ($characterReference && !empty($characterReference['base64'])) {
            Log::info('[generateWithGemini] Using character reference for face consistency', [
                'characterName' => $characterReference['characterName'] ?? 'Unknown',
                'mimeType' => $characterReference['mimeType'] ?? 'image/png',
                'hasLocationReference' => !empty($locationReference),
                'aspectRatio' => $aspectRatio,
                'resolution' => $modelResolution,
            ]);

            // Build location context for the scene
            $locationContext = '';
            if ($locationReference) {
                $locationContext = " in {$locationReference['locationName']}";
                if (!empty($locationReference['locationDescription'])) {
                    $locationContext .= " ({$locationReference['locationDescription']})";
                }
            }

            // Build time/weather context
            $environmentContext = '';
            if ($locationReference) {
                $envParts = [];
                if (!empty($locationReference['timeOfDay']) && $locationReference['timeOfDay'] !== 'day') {
                    $envParts[] = "{$locationReference['timeOfDay']} lighting";
                }
                if (!empty($locationReference['weather']) && $locationReference['weather'] !== 'clear') {
                    $envParts[] = "{$locationReference['weather']} weather";
                }
                if (!empty($locationReference['atmosphere'])) {
                    $envParts[] = "{$locationReference['atmosphere']} atmosphere";
                }
                if (!empty($envParts)) {
                    $environmentContext = "\nEnvironment: " . implode(', ', $envParts);
                }
            }

            // IMPROVED PROMPT: Use "this exact person" phrasing per Google's recommendations
            // This significantly improves face/identity consistency
            // Now includes Character Look System for Hollywood-level consistency
            $characterName = $characterReference['characterName'] ?? 'the person';

            // Build Character DNA for complete look consistency
            $characterDNA = $this->buildCharacterDNAForPrompt($characterReference);

            $faceConsistencyPrompt = <<<EOT
Generate a photorealistic cinematic image of THIS EXACT PERSON from the reference image{$locationContext}.

{$characterDNA}
{$environmentContext}
SCENE DESCRIPTION:
{$prompt}

QUALITY REQUIREMENTS:
- 8K photorealistic, cinematic film still quality
- Natural skin texture with visible pores (no airbrushing)
- Professional cinematography lighting
- Sharp focus on face, cinematic depth of field

OUTPUT: Generate a single high-quality image showing THIS EXACT SAME PERSON (not a similar person, THE SAME person) with their EXACT appearance (same hair, same clothing, same accessories) in the described scene.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $characterReference['base64'],
                $faceConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.5-flash-image',
                    'mimeType' => $characterReference['mimeType'] ?? 'image/png',
                    'aspectRatio' => $aspectRatio,
                    'resolution' => $modelResolution,
                ]
            );

            // Handle image-to-image response format
            if (!empty($result['error']) || (!$result['success'] ?? false)) {
                throw new \Exception($result['error'] ?? 'Image generation with character reference failed');
            }

            if (!empty($result['imageData'])) {
                // Store the base64 image
                $storedPath = $this->storeBase64Image(
                    $result['imageData'],
                    $result['mimeType'] ?? 'image/png',
                    $project,
                    $scene['id']
                );
                $imageUrl = $this->getPublicUrl($storedPath);

                // Create asset record
                $asset = WizardAsset::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id,
                    'type' => WizardAsset::TYPE_IMAGE,
                    'name' => $scene['id'],
                    'path' => $storedPath,
                    'url' => $imageUrl,
                    'mime_type' => $result['mimeType'] ?? 'image/png',
                    'scene_index' => $options['sceneIndex'] ?? null,
                    'scene_id' => $scene['id'],
                    'metadata' => [
                        'prompt' => $prompt,
                        'model' => $modelId,
                        'characterReference' => $characterReference['characterName'] ?? 'Unknown',
                        'locationReference' => $locationReference['locationName'] ?? null,
                        'faceConsistency' => true,
                    ],
                ]);

                return [
                    'success' => true,
                    'imageUrl' => $imageUrl,
                    'prompt' => $prompt,
                    'model' => $modelId,
                    'assetId' => $asset->id,
                    'faceConsistency' => true,
                    'characterUsed' => $characterReference['characterName'] ?? 'Unknown',
                    'locationUsed' => $locationReference['locationName'] ?? null,
                ];
            }

            throw new \Exception('No image data in character reference response');
        }

        // If we have a location reference but no character, use location for environment consistency
        if ($locationReference && !empty($locationReference['base64'])) {
            Log::info('[generateWithGemini] Using location reference for environment consistency', [
                'locationName' => $locationReference['locationName'] ?? 'Unknown',
                'mimeType' => $locationReference['mimeType'] ?? 'image/png',
                'aspectRatio' => $aspectRatio,
                'resolution' => $modelResolution,
            ]);

            // Build location details for enhanced consistency
            $locationName = $locationReference['locationName'] ?? 'this location';
            $locationType = $locationReference['type'] ?? 'environment';
            $timeOfDay = $locationReference['timeOfDay'] ?? 'day';
            $weather = $locationReference['weather'] ?? 'clear';
            $atmosphere = $locationReference['atmosphere'] ?? '';

            // Build environmental context
            $envContext = [];
            if ($timeOfDay && $timeOfDay !== 'day') {
                $envContext[] = "{$timeOfDay} lighting conditions";
            }
            if ($weather && $weather !== 'clear') {
                $envContext[] = "{$weather} weather";
            }
            if ($atmosphere) {
                $envContext[] = "{$atmosphere} atmosphere";
            }
            $environmentalContext = !empty($envContext) ? "\nEnvironmental Conditions: " . implode(', ', $envContext) : '';

            // IMPROVED PROMPT: Use "THIS EXACT LOCATION" with 5-7 specific preservation elements
            // Based on research showing 41% quality improvement with explicit element listing
            $locationConsistencyPrompt = <<<EOT
Generate a photorealistic cinematic image set in THIS EXACT LOCATION from the reference image.

LOCATION IDENTITY PRESERVATION (CRITICAL - List specific elements):
1. ARCHITECTURE: Maintain identical structural elements, building shapes, doorways, windows, columns, and spatial layout exactly as shown in "{$locationName}"
2. MATERIALS & TEXTURES: Same wall textures, floor materials, surface finishes (brick, concrete, glass, wood grain, metal patina)
3. COLOR PALETTE: Identical color scheme - wall colors, accent colors, environmental tones
4. LIGHTING DIRECTION: Same light source positions, shadow directions, highlight placements
5. SPATIAL DEPTH: Maintain same perspective, depth relationships, foreground/midground/background layering
6. ENVIRONMENTAL DETAILS: Same props, furniture, signage, architectural ornaments, background elements
7. ATMOSPHERE: Same visual mood, haze/fog levels, ambient particles{$environmentalContext}

SCENE TO GENERATE:
{$prompt}

CAMERA & QUALITY:
- Shot on ARRI Alexa with Zeiss Master Prime wide-angle lens
- 8K photorealistic, architectural photography quality
- Professional cinematography with motivated lighting
- Sharp environmental details, natural depth of field
- Authentic material textures (no smoothing or AI artifacts)

OUTPUT: Generate THIS EXACT SAME LOCATION (not similar, THE IDENTICAL environment) with the scene described above. The viewer must recognize this as the same place.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $locationReference['base64'],
                $locationConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.5-flash-image',
                    'mimeType' => $locationReference['mimeType'] ?? 'image/png',
                    'aspectRatio' => $aspectRatio,
                    'resolution' => $modelResolution,
                ]
            );

            // Handle image-to-image response format
            if (!empty($result['error']) || (!$result['success'] ?? false)) {
                throw new \Exception($result['error'] ?? 'Image generation with location reference failed');
            }

            if (!empty($result['imageData'])) {
                // Store the base64 image
                $storedPath = $this->storeBase64Image(
                    $result['imageData'],
                    $result['mimeType'] ?? 'image/png',
                    $project,
                    $scene['id']
                );
                $imageUrl = $this->getPublicUrl($storedPath);

                // Create asset record
                $asset = WizardAsset::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id,
                    'type' => WizardAsset::TYPE_IMAGE,
                    'name' => $scene['id'],
                    'path' => $storedPath,
                    'url' => $imageUrl,
                    'mime_type' => $result['mimeType'] ?? 'image/png',
                    'scene_index' => $options['sceneIndex'] ?? null,
                    'scene_id' => $scene['id'],
                    'metadata' => [
                        'prompt' => $prompt,
                        'model' => $modelId,
                        'locationReference' => $locationReference['locationName'] ?? 'Unknown',
                        'locationConsistency' => true,
                    ],
                ]);

                return [
                    'success' => true,
                    'imageUrl' => $imageUrl,
                    'prompt' => $prompt,
                    'model' => $modelId,
                    'assetId' => $asset->id,
                    'locationConsistency' => true,
                    'locationUsed' => $locationReference['locationName'] ?? 'Unknown',
                ];
            }

            throw new \Exception('No image data in location reference response');
        }

        // If we have a style reference but no character or location, use style for visual consistency
        if ($styleReference && !empty($styleReference['base64'])) {
            Log::info('[generateWithGemini] Using style reference for visual style consistency', [
                'hasStyleDescription' => !empty($styleReference['styleDescription']),
                'mimeType' => $styleReference['mimeType'] ?? 'image/png',
                'aspectRatio' => $aspectRatio,
                'resolution' => $modelResolution,
            ]);

            // IMPROVED PROMPT: Use 6-element style preservation based on Gemini 2.5 Flash best practices
            // Research shows 88% style fidelity when using specific attribute anchoring
            $styleConsistencyPrompt = <<<EOT
Generate a photorealistic cinematic image that EXACTLY MATCHES the visual style from this reference image.

VISUAL STYLE PRESERVATION (CRITICAL - 6-ELEMENT ANCHORING):

1. COLOR GRADING: Match EXACTLY the same color palette
   - Same hue shifts in shadows, midtones, and highlights
   - Identical saturation levels and color temperature
   - Same color contrast and complementary relationships
   - Match the color grading from the reference image precisely

2. LIGHTING QUALITY: Replicate the lighting characteristics
   - Same light direction, hardness/softness, and contrast ratio
   - Identical shadow density and highlight rolloff
   - Same ambient fill level and light color temperature
   - Match how light interacts with surfaces

3. ATMOSPHERE & MOOD: Preserve the emotional tone
   - Same visual mood and emotional atmosphere
   - Identical atmospheric effects (haze, fog, clarity)
   - Same sense of depth and air between elements
   - Match the overall feeling of the image

4. TEXTURE & GRAIN: Match the material rendering
   - Same film grain or digital noise characteristics
   - Identical surface texture rendering quality
   - Same level of detail and sharpness
   - Match skin texture rendering approach

5. CONTRAST & EXPOSURE: Match the tonal range
   - Same black point and white point levels
   - Identical midtone contrast and curve shape
   - Same dynamic range handling
   - Match highlight and shadow detail levels

6. CAMERA CHARACTERISTICS: Replicate the lens look
   - Same depth of field characteristics
   - Identical lens distortion and bokeh quality
   - Same perspective and focal length feel
   - Match vignetting and optical characteristics
EOT;

            if (!empty($styleReference['styleDescription'])) {
                $styleConsistencyPrompt .= "\n\nSTYLE DETAILS: {$styleReference['styleDescription']}";
            }

            if (!empty($styleReference['visualDNA'])) {
                $styleConsistencyPrompt .= "\n\nQUALITY DNA: {$styleReference['visualDNA']}";
            }

            $styleConsistencyPrompt .= <<<EOT


SCENE TO GENERATE:
{$prompt}

CAMERA & QUALITY:
- Shot on professional cinema camera matching reference style
- 8K photorealistic quality with film-like aesthetics
- Natural imperfections that match the reference (grain, texture)
- No AI artifacts, watermarks, or unnatural smoothing

CRITICAL: The generated image must be VISUALLY INDISTINGUISHABLE in style from the reference. A viewer should immediately recognize both images as having THE SAME visual treatment, color grading, and cinematic approach.

OUTPUT: Generate a single high-quality image in THIS EXACT SAME VISUAL STYLE showing the described scene.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $styleReference['base64'],
                $styleConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.5-flash-image',
                    'mimeType' => $styleReference['mimeType'] ?? 'image/png',
                    'aspectRatio' => $aspectRatio,
                    'resolution' => $modelResolution,
                ]
            );

            // Handle image-to-image response format
            if (!empty($result['error']) || (!$result['success'] ?? false)) {
                throw new \Exception($result['error'] ?? 'Image generation with style reference failed');
            }

            if (!empty($result['imageData'])) {
                // Store the base64 image
                $storedPath = $this->storeBase64Image(
                    $result['imageData'],
                    $result['mimeType'] ?? 'image/png',
                    $project,
                    $scene['id']
                );
                $imageUrl = $this->getPublicUrl($storedPath);

                // Create asset record
                $asset = WizardAsset::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id,
                    'type' => WizardAsset::TYPE_IMAGE,
                    'name' => $scene['id'],
                    'path' => $storedPath,
                    'url' => $imageUrl,
                    'mime_type' => $result['mimeType'] ?? 'image/png',
                    'scene_index' => $options['sceneIndex'] ?? null,
                    'scene_id' => $scene['id'],
                    'metadata' => [
                        'prompt' => $prompt,
                        'model' => $modelId,
                        'styleReference' => true,
                        'styleConsistency' => true,
                    ],
                ]);

                return [
                    'success' => true,
                    'imageUrl' => $imageUrl,
                    'prompt' => $prompt,
                    'model' => $modelId,
                    'assetId' => $asset->id,
                    'styleConsistency' => true,
                ];
            }

            throw new \Exception('No image data in style reference response');
        }

        // Standard generation without character, location, or style reference
        $result = $this->geminiService->generateImage($prompt, [
            'model' => $modelConfig['model'] ?? 'gemini-2.5-flash-image',
            'aspectRatio' => $aspectRatio,
            'count' => 1,
            'style' => $this->getStyleFromVisualStyle($project->storyboard['visualStyle'] ?? null),
            'tone' => $this->getToneFromVisualStyle($project->storyboard['visualStyle'] ?? null),
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        // Extract image from result
        $imageData = $result['data'][0] ?? null;
        if (!$imageData) {
            throw new \Exception('No image generated');
        }

        // Handle base64 or URL response
        $imageUrl = null;
        $storedPath = null;

        if (isset($imageData['b64_json'])) {
            // Base64 image - decode and store
            $storedPath = $this->storeBase64Image(
                $imageData['b64_json'],
                $imageData['mimeType'] ?? 'image/png',
                $project,
                $scene['id']
            );
            $imageUrl = $this->getPublicUrl($storedPath);
        } elseif (isset($imageData['url'])) {
            $imageUrl = $imageData['url'];
            $storedPath = $this->storeImage($imageUrl, $project, $scene['id']);
            $imageUrl = $this->getPublicUrl($storedPath);
        } else {
            throw new \Exception('Invalid image response format');
        }

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_IMAGE,
            'name' => $scene['title'] ?? $scene['id'],
            'path' => $storedPath,
            'url' => $imageUrl,
            'mime_type' => 'image/png',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'prompt' => $prompt,
                'model' => $modelId,
                'width' => $resolutionArray['width'],
                'height' => $resolutionArray['height'],
                'aspectRatio' => $project->aspect_ratio,
            ],
        ]);

        return [
            'success' => true,
            'status' => 'ready',
            'imageUrl' => $asset->url,
            'assetId' => $asset->id,
            'prompt' => $prompt,
            'model' => $modelId,
            'async' => false,
        ];
    }

    /**
     * Build comprehensive image prompt integrating all Bibles.
     *
     * OPTIMIZED FOR GEMINI PHOTOREALISTIC IMAGE GENERATION:
     * - Uses structured JSON-based prompts for realistic modes
     * - Includes negative prompts to avoid AI artifacts
     * - Uses narrative descriptions instead of keyword lists
     * - Includes photography-specific terminology (camera, lens, lighting)
     * - Applies cinematic quality modifiers for 8K photorealism
     *
     * Returns array with 'prompt' and 'negativePrompt' when using structured mode.
     */
    protected function buildImagePrompt(
        string $visualDescription,
        ?array $styleBible,
        ?array $visualStyle,
        WizardProject $project,
        ?array $sceneMemory = null,
        ?int $sceneIndex = null,
        array $shotContext = []  // PHASE 3: Shot context for multi-shot mode
    ): string {
        // Determine visual mode from project settings
        $visualMode = $this->getVisualMode($project, $visualStyle);

        // For realistic modes, use the structured prompt builder
        if ($this->shouldUseStructuredPrompt($visualMode)) {
            return $this->buildStructuredImagePrompt(
                $visualDescription,
                $styleBible,
                $visualStyle,
                $project,
                $sceneMemory,
                $sceneIndex,
                $visualMode,
                $shotContext  // PHASE 3: Pass shot context
            );
        }

        // Fall back to legacy prompt building for non-realistic modes
        return $this->buildLegacyImagePrompt(
            $visualDescription,
            $styleBible,
            $visualStyle,
            $project,
            $sceneMemory,
            $sceneIndex
        );
    }

    /**
     * Determine the visual mode from project settings.
     * Checks multiple locations where visualMode might be stored.
     */
    protected function getVisualMode(WizardProject $project, ?array $visualStyle): string
    {
        // Priority 1: Check concept for visual mode (this is where VideoWizard stores it)
        $concept = $project->concept ?? [];
        if (!empty($concept['visualMode'])) {
            return $concept['visualMode'];
        }

        // Priority 2: Check storyboard for visual mode (fallback location)
        $storyboard = $project->storyboard ?? [];
        if (!empty($storyboard['visualMode'])) {
            return $storyboard['visualMode'];
        }

        // Priority 3: Check content_config.content for visual mode
        $contentConfig = $project->content_config ?? [];
        $content = $contentConfig['content'] ?? [];
        if (!empty($content['visualMode'])) {
            return $content['visualMode'];
        }

        // Priority 4: Infer from visual style settings
        if ($visualStyle) {
            $style = $visualStyle['style'] ?? $visualStyle['renderStyle'] ?? '';
            if (stripos($style, 'realistic') !== false || stripos($style, 'cinematic') !== false) {
                return 'cinematic-realistic';
            }
            if (stripos($style, 'documentary') !== false) {
                return 'documentary-realistic';
            }
            if (stripos($style, 'animation') !== false || stripos($style, 'stylized') !== false) {
                return 'stylized-animation';
            }
        }

        // Default to cinematic-realistic for best quality
        return 'cinematic-realistic';
    }

    /**
     * Determine if we should use structured prompts.
     */
    protected function shouldUseStructuredPrompt(string $visualMode): bool
    {
        // Use structured prompts for realistic modes
        return in_array($visualMode, [
            'cinematic-realistic',
            'documentary-realistic',
            'mixed-hybrid',
        ]);
    }

    /**
     * Build image prompt using structured JSON schema.
     */
    protected function buildStructuredImagePrompt(
        string $visualDescription,
        ?array $styleBible,
        ?array $visualStyle,
        WizardProject $project,
        ?array $sceneMemory,
        ?int $sceneIndex,
        string $visualMode,
        array $shotContext = []  // PHASE 3: Shot context for multi-shot mode
    ): string {
        // Build options for structured prompt builder
        $options = [
            'visual_mode' => $visualMode,
            'aspect_ratio' => $project->aspect_ratio ?? '16:9',
            'scene_description' => $visualDescription,
            'scene_index' => $sceneIndex ?? 0,
            'style_bible' => $styleBible,
            'character_bible' => $sceneMemory['characterBible'] ?? null,
            'location_bible' => $sceneMemory['locationBible'] ?? null,
            // =================================================================
            // PHASE 3: Pass shot context for duplicate prevention
            // =================================================================
            'shot_type' => $shotContext['shot_type'] ?? null,
            'shot_purpose' => $shotContext['shot_purpose'] ?? null,
            'shot_index' => $shotContext['shot_index'] ?? null,
            'total_shots' => $shotContext['total_shots'] ?? null,
            'story_beat' => $shotContext['story_beat'] ?? null,
            'is_multi_shot' => $shotContext['is_multi_shot'] ?? false,
        ];

        // Build structured prompt
        $structuredPrompt = $this->structuredPromptBuilder->build($options);

        // Convert to prompt string
        $promptString = $this->structuredPromptBuilder->toPromptString($structuredPrompt);

        // Get negative prompts
        $negativePromptString = $this->structuredPromptBuilder->getNegativePromptString($structuredPrompt);

        // Append negative prompt instructions to the main prompt
        // Since Gemini doesn't support separate negative prompts, we embed them as exclusions
        if (!empty($negativePromptString)) {
            $promptString .= "\n\nCRITICAL - AVOID these elements (they will ruin the image): {$negativePromptString}";
        }

        // Store negative prompt for HiDream/RunPod which supports it natively
        $project->setAttribute('_lastNegativePrompt', $negativePromptString);

        Log::info('ImageGenerationService: Built STRUCTURED prompt', [
            'visualMode' => $visualMode,
            'sceneIndex' => $sceneIndex,
            'promptLength' => strlen($promptString),
            'negativePromptLength' => strlen($negativePromptString),
            'hasStyleBible' => !empty($styleBible['enabled']),
            'hasCharacterBible' => !empty($sceneMemory['characterBible']['enabled']),
            'hasLocationBible' => !empty($sceneMemory['locationBible']['enabled']),
            // PHASE 3: Log shot context
            'shotContext' => [
                'shot_type' => $shotContext['shot_type'] ?? null,
                'is_multi_shot' => $shotContext['is_multi_shot'] ?? false,
                'shot_index' => $shotContext['shot_index'] ?? null,
            ],
            'promptPreview' => substr($promptString, 0, 300) . '...',
        ]);

        return $promptString;
    }

    /**
     * Legacy prompt building for non-realistic modes.
     * Preserved for backward compatibility with stylized/animation modes.
     */
    protected function buildLegacyImagePrompt(
        string $visualDescription,
        ?array $styleBible,
        ?array $visualStyle,
        WizardProject $project,
        ?array $sceneMemory,
        ?int $sceneIndex
    ): string {
        // =========================================================================
        // EXTRACT ALL BIBLE DATA FIRST
        // =========================================================================

        // Extract character info for this scene - now uses full Character DNA
        $characterDescription = '';
        $characterDNABlock = '';
        if ($sceneMemory && $sceneIndex !== null) {
            $characterBible = $sceneMemory['characterBible'] ?? null;
            if ($characterBible && ($characterBible['enabled'] ?? false) && !empty($characterBible['characters'])) {
                $sceneCharacters = $this->getCharactersForScene($characterBible['characters'], $sceneIndex);
                if (!empty($sceneCharacters)) {
                    $charParts = [];
                    $dnaParts = [];
                    foreach ($sceneCharacters as $character) {
                        $name = $character['name'] ?? 'a person';

                        // Build basic description for narrative
                        if (!empty($character['description'])) {
                            $charParts[] = "{$name}, {$character['description']}";
                        }

                        // Build full Character DNA for consistency enforcement
                        $dna = $this->buildCharacterDNAForPrompt([
                            'characterName' => $name,
                            'characterDescription' => $character['description'] ?? '',
                            'hair' => $character['hair'] ?? [],
                            'wardrobe' => $character['wardrobe'] ?? [],
                            'makeup' => $character['makeup'] ?? [],
                            'accessories' => $character['accessories'] ?? [],
                        ]);
                        if (!empty($dna)) {
                            $dnaParts[] = $dna;
                        }
                    }
                    $characterDescription = implode(', and ', $charParts);
                    $characterDNABlock = implode("\n\n", $dnaParts);
                }
            }
        }

        // Extract location info for this scene
        $locationDescription = '';
        $timeOfDay = 'day';
        $weather = 'clear';
        $locationAtmosphere = '';
        if ($sceneMemory && $sceneIndex !== null) {
            $locationBible = $sceneMemory['locationBible'] ?? null;
            if ($locationBible && ($locationBible['enabled'] ?? false) && !empty($locationBible['locations'])) {
                $sceneLocation = $this->getLocationForScene($locationBible['locations'], $sceneIndex);
                if ($sceneLocation) {
                    $locParts = [];
                    if (!empty($sceneLocation['name'])) {
                        $locParts[] = $sceneLocation['name'];
                    }
                    if (!empty($sceneLocation['description'])) {
                        $locParts[] = $sceneLocation['description'];
                    }
                    $locationDescription = implode(', ', $locParts);
                    $timeOfDay = $sceneLocation['timeOfDay'] ?? 'day';
                    $weather = $sceneLocation['weather'] ?? 'clear';
                    $locationAtmosphere = $sceneLocation['atmosphere'] ?? '';

                    // Include location state if available
                    $locationState = $this->getLocationStateForScene($sceneLocation, $sceneIndex);
                    if ($locationState) {
                        $locationDescription .= ", {$locationState}";
                    }
                }
            }
        }

        // Build Style DNA for visual consistency
        $styleDNABlock = $this->buildStyleDNAForPrompt($styleBible);

        // Build Location DNA for environmental consistency (need sceneLocation from above)
        $locationDNABlock = '';
        if (isset($sceneLocation)) {
            $locationDNABlock = $this->buildLocationDNAForPrompt($sceneLocation, $sceneIndex);
        }

        // =========================================================================
        // LAYER 1: PHOTOGRAPHY FOUNDATION
        // =========================================================================
        $photographyParts = [];

        // Camera and lens - Professional photography terminology
        $cameraSetup = $styleBible['camera'] ?? null;
        if ($cameraSetup) {
            $photographyParts[] = $cameraSetup;
        } else {
            // Default cinematic camera setup for photorealism
            $photographyParts[] = 'shot on ARRI Alexa Mini with Zeiss Master Prime lenses';
        }

        // Shot type/composition
        $shotType = $this->getPhotographicShotType($visualStyle['composition'] ?? null);
        if ($shotType) {
            $photographyParts[] = $shotType;
        }

        // =========================================================================
        // LAYER 2: SCENE DESCRIPTION (NARRATIVE STYLE)
        // =========================================================================
        $sceneNarrative = $this->buildSceneNarrative(
            $visualDescription,
            $characterDescription,
            $locationDescription
        );

        // =========================================================================
        // LAYER 3: LIGHTING & ATMOSPHERE
        // =========================================================================
        $lightingDescription = $this->buildLightingDescription(
            $visualStyle['lighting'] ?? null,
            $timeOfDay,
            $weather,
            $styleBible['atmosphere'] ?? $locationAtmosphere
        );

        // =========================================================================
        // LAYER 4: STYLE & COLOR GRADE
        // =========================================================================
        $styleDescription = $this->buildStyleDescription(
            $styleBible,
            $visualStyle
        );

        // =========================================================================
        // LAYER 5: QUALITY ANCHORS (PHOTOREALISM BOOSTERS)
        // =========================================================================
        $qualityAnchors = $this->buildQualityAnchors(
            $project,
            $styleBible
        );

        // =========================================================================
        // ASSEMBLE FINAL NARRATIVE PROMPT
        // =========================================================================
        $promptParts = [];

        // Start with photography context
        if (!empty($photographyParts)) {
            $promptParts[] = 'A photorealistic image ' . implode(', ', $photographyParts);
        } else {
            $promptParts[] = 'A photorealistic cinematic photograph';
        }

        // Add the scene narrative
        $promptParts[] = $sceneNarrative;

        // Add lighting and atmosphere
        if ($lightingDescription) {
            $promptParts[] = $lightingDescription;
        }

        // Add style and color grade
        if ($styleDescription) {
            $promptParts[] = $styleDescription;
        }

        // Add Style DNA for visual consistency enforcement
        if (!empty($styleDNABlock)) {
            $promptParts[] = "\n\n" . $styleDNABlock;
        }

        // Add Location DNA for environmental consistency enforcement
        if (!empty($locationDNABlock)) {
            $promptParts[] = "\n\n" . $locationDNABlock;
        }

        // Add Character DNA for consistency enforcement (before quality anchors)
        if (!empty($characterDNABlock)) {
            $promptParts[] = "\n\n" . $characterDNABlock;
        }

        // Add quality anchors at the end
        $promptParts[] = $qualityAnchors;

        // Combine with proper narrative flow
        $finalPrompt = implode('. ', array_filter($promptParts));

        // Log for debugging
        Log::debug('ImageGenerationService: Built LEGACY photorealistic prompt', [
            'sceneIndex' => $sceneIndex,
            'promptLength' => strlen($finalPrompt),
            'hasStyleBible' => !empty($styleBible['enabled']),
            'hasCharacterBible' => !empty($characterDescription),
            'hasLocationBible' => !empty($locationDescription),
            'promptPreview' => substr($finalPrompt, 0, 200) . '...',
        ]);

        return $finalPrompt;
    }

    /**
     * Get photographic shot type description for Gemini.
     */
    protected function getPhotographicShotType(?string $composition): string
    {
        $shotTypes = [
            'wide' => 'wide establishing shot capturing the full environment',
            'medium' => 'medium shot with balanced framing of subject and surroundings',
            'close-up' => 'intimate close-up shot with shallow depth of field, bokeh background',
            'extreme-close-up' => 'extreme close-up macro shot with razor-sharp focus on fine details',
            'low-angle' => 'dramatic low-angle shot looking upward, emphasizing power and scale',
            'birds-eye' => 'overhead bird\'s eye view shot looking directly down',
            'over-shoulder' => 'over-the-shoulder perspective creating depth and connection',
            'tracking' => 'dynamic tracking shot with slight motion blur suggesting movement',
        ];

        return $shotTypes[$composition] ?? 'cinematic medium shot with natural framing';
    }

    /**
     * Build scene narrative in natural language for Gemini.
     */
    protected function buildSceneNarrative(
        string $visualDescription,
        string $characterDescription,
        string $locationDescription
    ): string {
        $narrative = [];

        // Start with subject/character if available
        if (!empty($characterDescription)) {
            $narrative[] = "featuring {$characterDescription}";
        }

        // Add the main visual description
        if (!empty($visualDescription)) {
            $narrative[] = $visualDescription;
        }

        // Add location context
        if (!empty($locationDescription)) {
            $narrative[] = "set in {$locationDescription}";
        }

        if (empty($narrative)) {
            return 'depicting a cinematic scene';
        }

        return implode(', ', $narrative);
    }

    /**
     * Build professional lighting description for Gemini.
     */
    protected function buildLightingDescription(
        ?string $lightingStyle,
        string $timeOfDay,
        string $weather,
        string $atmosphere
    ): string {
        $parts = [];

        // Time-based lighting foundation
        $timeBasedLighting = [
            'day' => 'natural daylight streaming through',
            'night' => 'ambient night lighting with subtle artificial sources',
            'dawn' => 'soft pre-dawn light with delicate pink and blue hues',
            'dusk' => 'warm twilight glow with deep purple and orange tones',
            'golden-hour' => 'magical golden hour sunlight with long warm shadows',
        ];
        $baseLighting = $timeBasedLighting[$timeOfDay] ?? 'natural lighting';

        // Lighting style modifiers
        $lightingDescriptions = [
            'natural' => 'soft natural light with gentle shadows, diffused through clouds',
            'golden-hour' => 'rich golden hour sunlight casting long warm shadows, backlit with lens flare',
            'blue-hour' => 'ethereal blue hour light with cool ambient tones and city lights emerging',
            'high-key' => 'bright high-key lighting with minimal shadows, clean and crisp',
            'low-key' => 'dramatic low-key chiaroscuro lighting with deep shadows and highlights',
            'neon' => 'vibrant neon lights reflecting off wet surfaces, cyberpunk atmosphere',
            'studio' => 'professional three-point studio lighting with soft fill and rim light',
            'dramatic' => 'dramatic directional lighting with strong contrast and volumetric rays',
            'soft' => 'soft diffused lighting with gentle gradients and no harsh shadows',
            'bright' => 'bright even lighting with clear visibility and vibrant colors',
            'golden' => 'warm golden tones with sun-kissed highlights and soft vignette',
        ];

        if ($lightingStyle && isset($lightingDescriptions[$lightingStyle])) {
            $parts[] = "illuminated by {$lightingDescriptions[$lightingStyle]}";
        } else {
            $parts[] = "illuminated by {$baseLighting}";
        }

        // Weather effects on lighting
        $weatherEffects = [
            'cloudy' => 'with overcast sky providing soft diffused lighting',
            'rainy' => 'with rain creating reflections on wet surfaces and atmospheric haze',
            'foggy' => 'with atmospheric fog adding depth and mystery, volumetric light rays',
            'stormy' => 'with dramatic storm clouds and occasional lightning illumination',
            'snowy' => 'with snow creating bright reflective surfaces and soft white ambiance',
        ];
        if ($weather && $weather !== 'clear' && isset($weatherEffects[$weather])) {
            $parts[] = $weatherEffects[$weather];
        }

        // Atmosphere mood
        if (!empty($atmosphere)) {
            $parts[] = "creating a {$atmosphere} atmosphere";
        }

        return implode(', ', $parts);
    }

    /**
     * Build style and color grade description for Gemini.
     */
    protected function buildStyleDescription(?array $styleBible, ?array $visualStyle): string
    {
        $parts = [];

        // Style Bible visual style
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            if (!empty($styleBible['style'])) {
                $parts[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $parts[] = "with {$styleBible['colorGrade']} color grading";
            }
        }

        // Visual style mood
        if ($visualStyle) {
            $moodDescriptions = [
                'epic' => 'epic and grandiose with sweeping visual scale',
                'intimate' => 'intimate and personal with emotional depth',
                'mysterious' => 'mysterious and enigmatic with hidden depths',
                'energetic' => 'dynamic and energetic with visual momentum',
                'contemplative' => 'contemplative and serene with thoughtful composition',
                'tense' => 'tense and suspenseful with psychological weight',
                'hopeful' => 'hopeful and optimistic with uplifting warmth',
                'professional' => 'professional and polished with corporate elegance',
                'inspiring' => 'inspiring and motivational with aspirational beauty',
                'dramatic' => 'intensely dramatic with emotional power',
                'playful' => 'playful and lighthearted with joyful energy',
                'nostalgic' => 'nostalgic and wistful with vintage warmth',
                'dark' => 'dark and moody with brooding intensity',
                'romantic' => 'romantic and tender with soft emotional glow',
            ];
            if (!empty($visualStyle['mood']) && isset($moodDescriptions[$visualStyle['mood']])) {
                $parts[] = $moodDescriptions[$visualStyle['mood']];
            }

            // Color palette as color grading
            $colorGrading = [
                'teal-orange' => 'cinematic teal and orange color grading like Hollywood blockbusters',
                'warm-tones' => 'warm color temperature with amber and golden hues',
                'warm' => 'inviting warm tones with cozy color palette',
                'cool-tones' => 'cool color temperature with blue and cyan tones',
                'cool' => 'crisp cool tones with professional color balance',
                'desaturated' => 'slightly desaturated colors with muted artistic palette',
                'vibrant' => 'vibrant saturated colors that pop with energy',
                'pastel' => 'soft pastel color palette with gentle tones',
                'neutral' => 'balanced neutral colors with true-to-life rendering',
                'rich' => 'rich deep colors with luxurious color depth',
                'dark' => 'dark moody color palette with shadowy tones',
            ];
            if (!empty($visualStyle['colorPalette']) && isset($colorGrading[$visualStyle['colorPalette']])) {
                $parts[] = "with {$colorGrading[$visualStyle['colorPalette']]}";
            }
        }

        if (empty($parts)) {
            return 'with cinematic color grading and professional visual style';
        }

        return implode(', ', $parts);
    }

    /**
     * Build quality anchor modifiers for photorealistic results.
     */
    protected function buildQualityAnchors(WizardProject $project, ?array $styleBible): string
    {
        $anchors = [];

        // Custom quality from Style Bible
        if ($styleBible && !empty($styleBible['visualDNA'])) {
            $anchors[] = $styleBible['visualDNA'];
        }

        // Technical specs from project
        $technicalSpecs = $project->storyboard['technicalSpecs'] ?? null;
        if ($technicalSpecs && !empty($technicalSpecs['positive'])) {
            $anchors[] = $technicalSpecs['positive'];
        }

        // Default photorealism quality anchors (essential for Gemini)
        $defaultAnchors = [
            'ultra high resolution 8K UHD',
            'photorealistic',
            'hyperdetailed',
            'sharp focus with cinematic depth of field',
            'HDR',
            'professional color grading',
            'masterful composition',
            'award-winning photography',
        ];

        // Add defaults if custom is minimal
        if (count($anchors) < 2) {
            $anchors = array_merge($anchors, $defaultAnchors);
        }

        // Aspect ratio optimization
        $aspectRatio = $project->aspect_ratio ?? '16:9';
        $anchors[] = "optimized for {$aspectRatio} aspect ratio";

        return implode(', ', array_unique($anchors));
    }

    /**
     * Build Character DNA block for prompt injection.
     * Creates a comprehensive, structured description that ensures Hollywood-level
     * consistency for face, hair, wardrobe, makeup, and accessories.
     *
     * @param array $characterReference Character reference data from getCharacterReferenceForScene
     * @return string Formatted Character DNA block for prompt
     */
    protected function buildCharacterDNAForPrompt(array $characterReference): string
    {
        $name = $characterReference['characterName'] ?? 'Character';
        $parts = [];

        // Identity/Face section (always include)
        $identityParts = [
            "This is \"{$name}\" - maintain IDENTICAL facial features: same eyes, nose, mouth, face shape, jawline",
            "Same skin tone, same complexion, same facial proportions",
            "Same body type and build",
        ];

        if (!empty($characterReference['characterDescription'])) {
            $identityParts[] = "Identity: {$characterReference['characterDescription']}";
        }

        $parts[] = "IDENTITY PRESERVATION (CRITICAL):\n- " . implode("\n- ", $identityParts);

        // Hair section - critical for visual consistency
        $hair = $characterReference['hair'] ?? [];
        $hairParts = array_filter([
            !empty($hair['color']) ? "Color: {$hair['color']}" : '',
            !empty($hair['style']) ? "Style: {$hair['style']}" : '',
            !empty($hair['length']) ? "Length: {$hair['length']}" : '',
            !empty($hair['texture']) ? "Texture: {$hair['texture']}" : '',
        ]);
        if (!empty($hairParts)) {
            $parts[] = "HAIR (MUST MATCH EXACTLY - never different):\n- " . implode("\n- ", $hairParts);
        }

        // Wardrobe section - what the character wears
        $wardrobe = $characterReference['wardrobe'] ?? [];
        $wardrobeParts = [];
        if (!empty($wardrobe['outfit'])) {
            $wardrobeParts[] = "Outfit: {$wardrobe['outfit']}";
        }
        if (!empty($wardrobe['colors'])) {
            $wardrobeParts[] = "Color palette: {$wardrobe['colors']}";
        }
        if (!empty($wardrobe['footwear'])) {
            $wardrobeParts[] = "Footwear: {$wardrobe['footwear']}";
        }
        if (!empty($wardrobe['style'])) {
            $wardrobeParts[] = "Style: {$wardrobe['style']}";
        }
        if (!empty($wardrobeParts)) {
            $parts[] = "WARDROBE (MUST wear this exact outfit):\n- " . implode("\n- ", $wardrobeParts);
        }

        // Makeup section - the character's styling
        $makeup = $characterReference['makeup'] ?? [];
        $makeupParts = array_filter([
            !empty($makeup['style']) ? "Style: {$makeup['style']}" : '',
            !empty($makeup['details']) ? "Details: {$makeup['details']}" : '',
        ]);
        if (!empty($makeupParts)) {
            $parts[] = "MAKEUP/STYLING (maintain consistent look):\n- " . implode("\n- ", $makeupParts);
        }

        // Accessories section - jewelry, glasses, watches, etc.
        $accessories = $characterReference['accessories'] ?? [];
        if (!empty($accessories)) {
            $parts[] = "ACCESSORIES (these items MUST be visible):\n- " . implode("\n- ", $accessories);
        }

        // Traits section - personality and physical characteristics
        $traits = $characterReference['traits'] ?? [];
        if (!empty($traits)) {
            $traitList = is_array($traits) ? implode(', ', $traits) : $traits;
            if (!empty(trim($traitList))) {
                $parts[] = "TRAITS/CHARACTERISTICS:\n- " . $traitList;
            }
        }

        // Default expression if specified
        if (!empty($characterReference['defaultExpression'])) {
            $parts[] = "DEFAULT EXPRESSION: {$characterReference['defaultExpression']}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Build Style DNA block for visual consistency.
     * This ensures consistent visual style across all generated images.
     */
    protected function buildStyleDNAForPrompt(?array $styleBible): string
    {
        if (!$styleBible || !($styleBible['enabled'] ?? false)) {
            return '';
        }

        $parts = [];

        // Visual Style section
        if (!empty($styleBible['style'])) {
            $parts[] = "VISUAL STYLE: {$styleBible['style']}";
        }

        // Color Grading section
        if (!empty($styleBible['colorGrade'])) {
            $parts[] = "COLOR GRADE (maintain consistency): {$styleBible['colorGrade']}";
        }

        // Lighting section - structured if available
        $lighting = $styleBible['lighting'] ?? [];
        if (!empty(array_filter($lighting))) {
            $lightingParts = [];
            if (!empty($lighting['setup'])) $lightingParts[] = $lighting['setup'];
            if (!empty($lighting['intensity'])) $lightingParts[] = $lighting['intensity'] . ' intensity';
            if (!empty($lighting['type'])) $lightingParts[] = $lighting['type'] . ' lighting';
            if (!empty($lighting['mood'])) $lightingParts[] = $lighting['mood'] . ' mood';
            if (!empty($lightingParts)) {
                $parts[] = "LIGHTING SETUP: " . implode(', ', $lightingParts);
            }
        }

        // Atmosphere section
        if (!empty($styleBible['atmosphere'])) {
            $parts[] = "ATMOSPHERE: {$styleBible['atmosphere']}";
        }

        // Camera Language section
        if (!empty($styleBible['camera'])) {
            $parts[] = "CAMERA: {$styleBible['camera']}";
        }

        if (empty($parts)) {
            return '';
        }

        return "STYLE DNA - VISUAL CONSISTENCY:\n" . implode("\n", $parts);
    }

    /**
     * Build Location DNA block for environmental consistency.
     * This ensures consistent location appearance across shots.
     */
    protected function buildLocationDNAForPrompt(?array $sceneLocation, ?int $sceneIndex): string
    {
        if (!$sceneLocation) {
            return '';
        }

        $name = $sceneLocation['name'] ?? 'Location';
        $parts = [];

        // Environment section
        if (!empty($sceneLocation['description'])) {
            $parts[] = "ENVIRONMENT: {$sceneLocation['description']}";
        }

        // Time and Weather - critical for visual consistency
        $timeWeather = [];
        if (!empty($sceneLocation['timeOfDay'])) {
            $timeMap = [
                'day' => 'Daytime',
                'night' => 'Nighttime',
                'dawn' => 'Dawn/early morning',
                'dusk' => 'Dusk/twilight',
                'golden_hour' => 'Golden hour',
            ];
            $timeWeather[] = $timeMap[$sceneLocation['timeOfDay']] ?? $sceneLocation['timeOfDay'];
        }
        if (!empty($sceneLocation['weather'])) {
            $weatherMap = [
                'clear' => 'clear sky',
                'cloudy' => 'overcast/cloudy',
                'rainy' => 'rainy conditions',
                'foggy' => 'foggy/misty atmosphere',
                'stormy' => 'stormy weather',
                'snowy' => 'snowy conditions',
            ];
            $timeWeather[] = $weatherMap[$sceneLocation['weather']] ?? $sceneLocation['weather'];
        }
        if (!empty($timeWeather)) {
            $parts[] = "TIME/WEATHER: " . implode(', ', $timeWeather);
        }

        // Atmosphere
        if (!empty($sceneLocation['atmosphere'])) {
            $parts[] = "ATMOSPHERE: {$sceneLocation['atmosphere']}";
        }

        // Mood
        if (!empty($sceneLocation['mood'])) {
            $parts[] = "MOOD: {$sceneLocation['mood']}";
        }

        // Scene state if available (support both old and new field names for backwards compatibility)
        if ($sceneIndex !== null && !empty($sceneLocation['stateChanges'])) {
            foreach ($sceneLocation['stateChanges'] as $stateChange) {
                // Support both 'sceneIndex' (new) and 'scene' (old) field names
                $changeSceneIndex = $stateChange['sceneIndex'] ?? $stateChange['scene'] ?? null;
                // Support both 'stateDescription' (new) and 'state' (old) field names
                $changeDescription = $stateChange['stateDescription'] ?? $stateChange['state'] ?? '';

                if ($changeSceneIndex === $sceneIndex && !empty($changeDescription)) {
                    $parts[] = "CURRENT STATE: {$changeDescription}";
                    break;
                }
            }
        }

        if (count($parts) <= 1) {
            return '';
        }

        return "LOCATION DNA - {$name} (MAINTAIN CONSISTENCY):\n" . implode("\n", $parts);
    }

    /**
     * Get characters that appear in a specific scene.
     *
     * IMPORTANT: Empty appliedScenes array means "applies to ALL scenes" (per UI design).
     * This matches the behavior shown in the Character Bible modal.
     *
     * @param array $characters List of characters from Character Bible
     * @param int|null $sceneIndex Scene index (null for non-scene contexts like portrait generation)
     * @return array Characters that apply to the given scene (or all if sceneIndex is null)
     */
    protected function getCharactersForScene(array $characters, ?int $sceneIndex): array
    {
        // If no scene context (e.g., generating character portrait), return all characters
        if ($sceneIndex === null) {
            return $characters;
        }

        return array_filter($characters, function ($character) use ($sceneIndex) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];

            // Empty array means character applies to ALL scenes (per UI design)
            if (empty($appliedScenes)) {
                return true;
            }

            return in_array($sceneIndex, $appliedScenes);
        });
    }

    /**
     * Get the primary location for a specific scene.
     *
     * IMPORTANT: Empty scenes array means "applies to ALL scenes" (per UI design).
     * The Location Bible modal shows "Currently applies to ALL scenes" when no specific scenes are selected.
     *
     * @param array $locations List of locations from Location Bible
     * @param int|null $sceneIndex Scene index (null for non-scene contexts)
     * @return array|null Location that applies to the given scene
     */
    protected function getLocationForScene(array $locations, ?int $sceneIndex): ?array
    {
        // If no scene context, return first location as default
        if ($sceneIndex === null && !empty($locations)) {
            return $locations[0];
        }

        foreach ($locations as $location) {
            $scenes = $location['scenes'] ?? $location['appearsInScenes'] ?? [];

            // Empty array means location applies to ALL scenes (per UI design)
            if (empty($scenes)) {
                return $location;
            }

            if (in_array($sceneIndex, $scenes)) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Get the location state for a specific scene index.
     * Returns the most recent state change at or before this scene.
     */
    protected function getLocationStateForScene(array $location, int $sceneIndex): ?string
    {
        $stateChanges = $location['stateChanges'] ?? [];
        if (empty($stateChanges)) {
            return null;
        }

        // Sort by scene index to ensure proper order (support both field names)
        usort($stateChanges, fn($a, $b) => ($a['sceneIndex'] ?? $a['scene'] ?? 0) <=> ($b['sceneIndex'] ?? $b['scene'] ?? 0));

        // Find the most recent state change at or before this scene
        // Support both new (sceneIndex/stateDescription) and old (scene/state) field names
        $applicableState = null;
        foreach ($stateChanges as $change) {
            $changeScene = $change['sceneIndex'] ?? $change['scene'] ?? -1;
            if ($changeScene <= $sceneIndex) {
                $applicableState = $change['stateDescription'] ?? $change['state'] ?? null;
            } else {
                break; // Since sorted, no need to continue
            }
        }

        return $applicableState;
    }

    /**
     * Get style string from visual style settings.
     */
    protected function getStyleFromVisualStyle(?array $visualStyle): string
    {
        if (!$visualStyle) {
            return 'photorealistic, 8k professional photograph, cinematic lighting';
        }

        $parts = ['photorealistic, cinematic'];

        if (!empty($visualStyle['lighting'])) {
            $lightingMap = [
                'golden-hour' => 'golden hour lighting',
                'blue-hour' => 'blue hour, twilight',
                'neon' => 'neon lighting',
                'low-key' => 'noir lighting',
            ];
            $parts[] = $lightingMap[$visualStyle['lighting']] ?? '';
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Get tone string from visual style settings.
     */
    protected function getToneFromVisualStyle(?array $visualStyle): string
    {
        if (!$visualStyle) {
            return 'professional, high-quality, sharp focus';
        }

        $parts = [];

        if (!empty($visualStyle['mood'])) {
            $moodMap = [
                'epic' => 'dramatic, high contrast',
                'intimate' => 'cozy, warm',
                'mysterious' => 'moody, atmospheric',
                'energetic' => 'energetic, dynamic',
                'contemplative' => 'minimalistic, clean',
            ];
            $parts[] = $moodMap[$visualStyle['mood']] ?? '';
        }

        if (!empty($visualStyle['colorPalette'])) {
            $colorMap = [
                'teal-orange' => 'teal and orange color grading',
                'warm-tones' => 'warm colors',
                'cool-tones' => 'cool colors',
                'vibrant' => 'vibrant colors',
                'pastel' => 'soft pastel palette',
            ];
            $parts[] = $colorMap[$visualStyle['colorPalette']] ?? '';
        }

        return implode(', ', array_filter($parts)) ?: 'professional, high-quality';
    }

    /**
     * Get resolution configuration for aspect ratio.
     * Note: HiDream accepts any dimensions. Gemini API supports specific sizes but
     * we map to closest values. All ratios are now mathematically correct.
     */
    protected function getResolution(string $aspectRatio): array
    {
        $resolutions = [
            // 16:9 = 1.777... ratio (1792/1008 = 1.777...)
            '16:9' => ['width' => 1792, 'height' => 1008, 'size' => '1792x1008'],
            // 9:16 = 0.5625 ratio (1008/1792 = 0.5625)
            '9:16' => ['width' => 1008, 'height' => 1792, 'size' => '1008x1792'],
            // 1:1 = 1.0 ratio
            '1:1' => ['width' => 1024, 'height' => 1024, 'size' => '1024x1024'],
            // 4:5 = 0.8 ratio (1024/1280 = 0.8)
            '4:5' => ['width' => 1024, 'height' => 1280, 'size' => '1024x1280'],
            // 4:3 = 1.333... ratio (1365/1024 ≈ 1.333)
            '4:3' => ['width' => 1365, 'height' => 1024, 'size' => '1365x1024'],
        ];

        return $resolutions[$aspectRatio] ?? $resolutions['16:9'];
    }

    /**
     * Store image from URL to local storage.
     */
    protected function storeImage(string $imageUrl, WizardProject $project, string $sceneId): string
    {
        try {
            $contents = Http::timeout(60)->get($imageUrl)->body();
        } catch (\Exception $e) {
            Log::warning('ImageGeneration: HTTP fetch failed, trying file_get_contents', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            $contents = file_get_contents($imageUrl);
        }

        if (empty($contents)) {
            Log::error('ImageGeneration: Failed to download image', ['url' => $imageUrl]);
            throw new \Exception('Failed to download image from URL');
        }

        $filename = Str::slug($sceneId) . '-' . time() . '.png';
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        $stored = Storage::disk('public')->put($path, $contents);

        if (!$stored) {
            Log::error('ImageGeneration: Failed to store image', [
                'path' => $path,
                'contentLength' => strlen($contents),
            ]);
            throw new \Exception('Failed to store image to disk');
        }

        // Verify the file was actually stored
        if (!Storage::disk('public')->exists($path)) {
            Log::error('ImageGeneration: Image file does not exist after storage', ['path' => $path]);
            throw new \Exception('Image file was not saved correctly');
        }

        $fullUrl = $this->getPublicUrl($path);
        Log::info('ImageGeneration: Image stored successfully', [
            'path' => $path,
            'url' => $fullUrl,
            'size' => strlen($contents),
        ]);

        return $path;
    }

    /**
     * Store base64 image to local storage.
     */
    protected function storeBase64Image(string $base64Data, string $mimeType, WizardProject $project, string $sceneId): string
    {
        $contents = base64_decode($base64Data);

        if (empty($contents)) {
            Log::error('ImageGeneration: Failed to decode base64 image');
            throw new \Exception('Failed to decode base64 image data');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $filename = Str::slug($sceneId) . '-' . time() . '.' . $extension;
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        $stored = Storage::disk('public')->put($path, $contents);

        if (!$stored) {
            Log::error('ImageGeneration: Failed to store base64 image', [
                'path' => $path,
                'contentLength' => strlen($contents),
            ]);
            throw new \Exception('Failed to store image to disk');
        }

        // Verify the file was actually stored
        if (!Storage::disk('public')->exists($path)) {
            Log::error('ImageGeneration: Base64 image file does not exist after storage', ['path' => $path]);
            throw new \Exception('Image file was not saved correctly');
        }

        $fullUrl = $this->getPublicUrl($path);
        Log::info('ImageGeneration: Base64 image stored successfully', [
            'path' => $path,
            'url' => $fullUrl,
            'size' => strlen($contents),
        ]);

        return $path;
    }

    /**
     * Regenerate an image with modifications.
     */
    public function regenerateImage(WizardProject $project, array $scene, string $modification, array $options = []): array
    {
        $originalPrompt = $scene['prompt'] ?? $scene['visualDescription'] ?? '';

        $modifiedPrompt = "{$originalPrompt}. {$modification}";

        return $this->generateSceneImage($project, array_merge($scene, [
            'visualDescription' => $modifiedPrompt,
        ]), $options);
    }

    /**
     * Generate images for all scenes in batch.
     */
    public function generateAllSceneImages(WizardProject $project, callable $progressCallback = null, array $options = []): array
    {
        $scenes = $project->getScenes();
        $results = [];
        $modelId = $options['model'] ?? $project->storyboard['imageModel'] ?? 'nanobanana';
        $modelConfig = self::IMAGE_MODELS[$modelId] ?? self::IMAGE_MODELS['nanobanana'];

        // For async models (HiDream), start all jobs first
        if ($modelConfig['async'] ?? false) {
            foreach ($scenes as $index => $scene) {
                try {
                    $result = $this->generateSceneImage($project, $scene, array_merge($options, [
                        'sceneIndex' => $index,
                    ]));
                    $results[$scene['id']] = $result;

                    if ($progressCallback) {
                        $progressCallback($index + 1, count($scenes), $scene['id'], 'started');
                    }
                } catch (\Exception $e) {
                    $results[$scene['id']] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            return $results;
        }

        // For sync models (NanoBanana), generate sequentially with rate limiting
        foreach ($scenes as $index => $scene) {
            try {
                $result = $this->generateSceneImage($project, $scene, array_merge($options, [
                    'sceneIndex' => $index,
                ]));
                $results[$scene['id']] = $result;

                if ($progressCallback) {
                    $progressCallback($index + 1, count($scenes), $scene['id'], 'completed');
                }

                // Rate limiting - wait between requests to avoid 429 errors
                if ($index < count($scenes) - 1) {
                    usleep(500000); // 500ms between requests
                }
            } catch (\Exception $e) {
                $results[$scene['id']] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                Log::warning("Failed to generate image for scene {$index}: " . $e->getMessage());

                // On rate limit, wait longer before retrying
                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate')) {
                    sleep(5);
                }
            }
        }

        return $results;
    }

    /**
     * Get pending/processing image jobs for a project.
     */
    public function getPendingJobs(WizardProject $project): array
    {
        return WizardProcessingJob::where('project_id', $project->id)
            ->where('type', 'image_generation')
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->toArray();
    }

    /**
     * Upscale an image to HD or 4K quality.
     */
    public function upscaleImage(string $imageUrl, string $quality = 'hd'): array
    {
        try {
            // Download the original image
            $response = Http::timeout(30)->get($imageUrl);
            if (!$response->successful()) {
                throw new \Exception('Failed to download image for upscaling');
            }

            $imageData = $response->body();
            $base64Image = base64_encode($imageData);

            // Determine target resolution based on quality
            $targetResolution = $quality === '4k' ? '3840x2160' : '1920x1080';

            // Use Gemini for upscaling with image generation
            $prompt = "Upscale this image to {$targetResolution} resolution while maintaining perfect quality, details, and sharpness. Enhance clarity and detail. Do not change the content, composition, or style - only improve resolution and quality.";

            $result = $this->geminiService->generateImageFromImage($base64Image, $prompt, [
                'model' => 'gemini-2.5-flash-image',
                'responseType' => 'image',
            ]);

            if (!empty($result['imageData'])) {
                // Save upscaled image
                $filename = 'wizard/upscaled/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, base64_decode($result['imageData']));
                $upscaledUrl = $this->getPublicUrl($filename);

                return [
                    'success' => true,
                    'imageUrl' => $upscaledUrl,
                    'quality' => $quality,
                    'resolution' => $targetResolution,
                ];
            }

            throw new \Exception('Upscaling did not return an image');

        } catch (\Exception $e) {
            Log::error('Image upscale failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Edit an image using AI with a mask.
     */
    public function editImageWithMask(string $imageUrl, string $maskData, string $editPrompt): array
    {
        try {
            // Download the original image
            $response = Http::timeout(30)->get($imageUrl);
            if (!$response->successful()) {
                throw new \Exception('Failed to download image for editing');
            }

            $imageData = $response->body();
            $base64Image = base64_encode($imageData);

            // The maskData is a base64 encoded PNG from the canvas
            // Remove data URL prefix if present
            $cleanMaskData = $maskData;
            if (str_starts_with($maskData, 'data:')) {
                $cleanMaskData = preg_replace('/^data:image\/\w+;base64,/', '', $maskData);
            }

            // Build edit prompt with mask context
            $fullPrompt = "Edit the marked/highlighted areas of this image. The white areas in the mask indicate regions to edit. Edit instruction: {$editPrompt}. Keep the unmasked areas unchanged. Maintain consistent style and lighting with the original image.";

            // Use Gemini for inpainting/editing
            $result = $this->geminiService->editImageWithMask($base64Image, $cleanMaskData, $fullPrompt, [
                'model' => 'gemini-2.5-flash-image',
            ]);

            if (!empty($result['imageData'])) {
                // Save edited image
                $filename = 'wizard/edited/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, base64_decode($result['imageData']));
                $editedUrl = $this->getPublicUrl($filename);

                return [
                    'success' => true,
                    'imageUrl' => $editedUrl,
                ];
            }

            // Fallback: If mask editing not supported, try image-to-image generation
            $fallbackResult = $this->geminiService->generateImageFromImage($base64Image, $editPrompt, [
                'model' => 'gemini-2.5-flash-image',
                'responseType' => 'image',
            ]);

            if (!empty($fallbackResult['imageData'])) {
                $filename = 'wizard/edited/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, base64_decode($fallbackResult['imageData']));
                $editedUrl = $this->getPublicUrl($filename);

                return [
                    'success' => true,
                    'imageUrl' => $editedUrl,
                ];
            }

            throw new \Exception('Image editing did not return a result');

        } catch (\Exception $e) {
            Log::error('Image edit failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
