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

class ImageGenerationService
{
    protected GeminiService $geminiService;
    protected RunPodService $runPodService;

    /**
     * Model configurations with token costs matching reference implementation.
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
            'description' => 'High quality, fast generation',
            'tokenCost' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash-exp-image-generation',
            'quality' => 'hd',
            'async' => false,
        ],
        'nanobanana' => [
            'name' => 'NanoBanana',
            'description' => 'Quick drafts, lower cost',
            'tokenCost' => 1,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash-exp-image-generation',
            'quality' => 'basic',
            'async' => false,
        ],
    ];

    public function __construct(GeminiService $geminiService, RunPodService $runPodService)
    {
        $this->geminiService = $geminiService;
        $this->runPodService = $runPodService;
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
        $sceneMemory = $project->storyboard['sceneMemory'] ?? null;
        $sceneIndex = $options['sceneIndex'] ?? null;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Build the image prompt with all Bible integrations (Style, Character, Location)
        $prompt = $this->buildImagePrompt($visualDescription, $styleBible, $visualStyle, $project, $sceneMemory, $sceneIndex);

        // Get resolution based on aspect ratio
        $resolution = $this->getResolution($project->aspect_ratio);

        // Check credits
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            throw new \Exception($quota['message']);
        }

        // Get character reference for face consistency (if available)
        $characterReference = $this->getCharacterReferenceForScene($sceneMemory, $sceneIndex);

        // Get location reference for environment consistency (if available)
        $locationReference = $this->getLocationReferenceForScene($sceneMemory, $sceneIndex);

        // Get style reference for visual style consistency (if available)
        // Style Bible reference is global (applies to all scenes)
        $styleReference = $this->getStyleReference($sceneMemory);

        // Route to appropriate provider
        if ($modelConfig['provider'] === 'runpod') {
            return $this->generateWithHiDream($project, $scene, $prompt, $resolution, $options);
        } else {
            return $this->generateWithGemini($project, $scene, $prompt, $resolution, $modelId, $modelConfig, $options, $characterReference, $locationReference, $styleReference);
        }
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
                ]);

                return [
                    'base64' => $character['referenceImageBase64'],
                    'mimeType' => $character['referenceImageMimeType'] ?? 'image/png',
                    'characterName' => $character['name'] ?? 'Character',
                    'characterDescription' => $character['description'] ?? '',
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
     * @param array|null $sceneMemory The scene memory containing Style Bible
     * @return array|null Style reference data {base64, mimeType, styleDescription} or null
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
            // Build style description from Style Bible fields
            $styleDescription = [];
            if (!empty($styleBible['style'])) {
                $styleDescription[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $styleDescription[] = $styleBible['colorGrade'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $styleDescription[] = $styleBible['atmosphere'];
            }
            if (!empty($styleBible['camera'])) {
                $styleDescription[] = $styleBible['camera'];
            }

            Log::info('[getStyleReference] Using style reference', [
                'base64Length' => strlen($styleBible['referenceImageBase64']),
                'mimeType' => $styleBible['referenceImageMimeType'] ?? 'image/png',
                'hasStyleDescription' => !empty($styleDescription),
            ]);

            return [
                'base64' => $styleBible['referenceImageBase64'],
                'mimeType' => $styleBible['referenceImageMimeType'] ?? 'image/png',
                'styleDescription' => implode(', ', $styleDescription),
                'visualDNA' => $styleBible['visualDNA'] ?? '',
            ];
        }

        Log::debug('[getStyleReference] No style with base64 reference found');
        return null;
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

        Log::info('HiDream generation request', [
            'endpointId' => $endpointId,
            'prompt' => substr($prompt, 0, 100) . '...',
            'width' => $resolution['width'],
            'height' => $resolution['height'],
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
     * @param array $resolution Image resolution
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
        array $resolution,
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

        $aspectRatio = $aspectRatioMap[$project->aspect_ratio] ?? '16:9';

        // Priority: Character reference (face consistency) > Location reference (environment consistency)
        // If we have a character reference, use image-to-image generation for face consistency
        if ($characterReference && !empty($characterReference['base64'])) {
            Log::info('[generateWithGemini] Using character reference for face consistency', [
                'characterName' => $characterReference['characterName'] ?? 'Unknown',
                'mimeType' => $characterReference['mimeType'] ?? 'image/png',
                'hasLocationReference' => !empty($locationReference),
            ]);

            // Build special prompt for face/character consistency
            // Include location details in the prompt if available
            $locationContext = '';
            if ($locationReference) {
                $locationContext = "\n\nLOCATION CONTEXT: The scene takes place in \"{$locationReference['locationName']}\"";
                if (!empty($locationReference['locationDescription'])) {
                    $locationContext .= " - {$locationReference['locationDescription']}";
                }
                if (!empty($locationReference['timeOfDay']) && $locationReference['timeOfDay'] !== 'day') {
                    $locationContext .= ". Time: {$locationReference['timeOfDay']}";
                }
                if (!empty($locationReference['weather']) && $locationReference['weather'] !== 'clear') {
                    $locationContext .= ". Weather: {$locationReference['weather']}";
                }
                if (!empty($locationReference['atmosphere'])) {
                    $locationContext .= ". Atmosphere: {$locationReference['atmosphere']}";
                }
            }

            $faceConsistencyPrompt = <<<EOT
Using the provided image as a character/face reference to maintain consistency, generate a new image.

CHARACTER REFERENCE: The provided image shows the EXACT appearance of "{$characterReference['characterName']}" that MUST be preserved in the generated image. Maintain the same:
- Facial features (eyes, nose, mouth, face shape)
- Skin tone and complexion
- Hair color, style, and texture
- Overall body type and proportions{$locationContext}

SCENE TO GENERATE:
{$prompt}

CRITICAL: The character in the generated image MUST look like the SAME PERSON as in the reference image. This is essential for visual consistency across the video.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $characterReference['base64'],
                $faceConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.0-flash-exp-image-generation',
                    'mimeType' => $characterReference['mimeType'] ?? 'image/png',
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
            ]);

            // Build special prompt for location/environment consistency
            $locationConsistencyPrompt = <<<EOT
Using the provided image as an environment/location reference to maintain visual consistency, generate a new image.

LOCATION REFERENCE: The provided image shows the EXACT environment "{$locationReference['locationName']}" that MUST be used as the setting. Maintain the same:
- Architecture and structural elements
- Color palette and lighting atmosphere
- Environmental details and textures
- Overall mood and visual style

SCENE TO GENERATE:
{$prompt}

CRITICAL: The environment in the generated image MUST match the SAME LOCATION as in the reference image. This is essential for visual consistency across the video.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $locationReference['base64'],
                $locationConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.0-flash-exp-image-generation',
                    'mimeType' => $locationReference['mimeType'] ?? 'image/png',
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
            ]);

            // Build special prompt for visual style consistency
            $styleConsistencyPrompt = <<<EOT
Using the provided image as a VISUAL STYLE REFERENCE to maintain consistent visual aesthetics, generate a new image.

STYLE REFERENCE: The provided image defines the EXACT visual style that MUST be preserved:
- Color palette and color grading
- Lighting style and atmosphere
- Overall mood and tone
- Visual quality and cinematic look
EOT;

            if (!empty($styleReference['styleDescription'])) {
                $styleConsistencyPrompt .= "\n\nSTYLE DETAILS: {$styleReference['styleDescription']}";
            }

            if (!empty($styleReference['visualDNA'])) {
                $styleConsistencyPrompt .= "\n\nQUALITY: {$styleReference['visualDNA']}";
            }

            $styleConsistencyPrompt .= <<<EOT


SCENE TO GENERATE:
{$prompt}

CRITICAL: The generated image MUST match the SAME VISUAL STYLE as in the reference image. This ensures visual consistency across the video.
EOT;

            $result = $this->geminiService->generateImageFromImage(
                $styleReference['base64'],
                $styleConsistencyPrompt,
                [
                    'model' => $modelConfig['model'] ?? 'gemini-2.0-flash-exp-image-generation',
                    'mimeType' => $styleReference['mimeType'] ?? 'image/png',
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
            'model' => $modelConfig['model'] ?? 'gemini-2.0-flash-exp-image-generation',
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
                'width' => $resolution['width'],
                'height' => $resolution['height'],
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
     * - Uses narrative descriptions instead of keyword lists
     * - Includes photography-specific terminology (camera, lens, lighting)
     * - Applies cinematic quality modifiers for 8K photorealism
     *
     * Prompt Chain Architecture:
     * 1. Photography Foundation - Camera, lens, shot type
     * 2. Scene Description - Subject, action, environment (narrative style)
     * 3. Lighting & Atmosphere - Direction, quality, mood
     * 4. Style & Color Grade - Visual style, color palette
     * 5. Quality Anchors - Technical excellence modifiers
     */
    protected function buildImagePrompt(
        string $visualDescription,
        ?array $styleBible,
        ?array $visualStyle,
        WizardProject $project,
        ?array $sceneMemory = null,
        ?int $sceneIndex = null
    ): string {
        // =========================================================================
        // EXTRACT ALL BIBLE DATA FIRST
        // =========================================================================

        // Extract character info for this scene
        $characterDescription = '';
        if ($sceneMemory && $sceneIndex !== null) {
            $characterBible = $sceneMemory['characterBible'] ?? null;
            if ($characterBible && ($characterBible['enabled'] ?? false) && !empty($characterBible['characters'])) {
                $sceneCharacters = $this->getCharactersForScene($characterBible['characters'], $sceneIndex);
                if (!empty($sceneCharacters)) {
                    $charParts = [];
                    foreach ($sceneCharacters as $character) {
                        if (!empty($character['description'])) {
                            $name = $character['name'] ?? 'a person';
                            $charParts[] = "{$name}, {$character['description']}";
                        }
                    }
                    $characterDescription = implode(', and ', $charParts);
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

        // Add quality anchors at the end
        $promptParts[] = $qualityAnchors;

        // Combine with proper narrative flow
        $finalPrompt = implode('. ', array_filter($promptParts));

        // Log for debugging
        Log::debug('ImageGenerationService: Built photorealistic prompt', [
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
     * Get characters that appear in a specific scene.
     *
     * IMPORTANT: Empty appliedScenes array means "applies to ALL scenes" (per UI design).
     * This matches the behavior shown in the Character Bible modal.
     */
    protected function getCharactersForScene(array $characters, int $sceneIndex): array
    {
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
     */
    protected function getLocationForScene(array $locations, int $sceneIndex): ?array
    {
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

        // Sort by scene index to ensure proper order
        usort($stateChanges, fn($a, $b) => ($a['scene'] ?? 0) <=> ($b['scene'] ?? 0));

        // Find the most recent state change at or before this scene
        $applicableState = null;
        foreach ($stateChanges as $change) {
            $changeScene = $change['scene'] ?? -1;
            if ($changeScene <= $sceneIndex) {
                $applicableState = $change['state'] ?? null;
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
     * Note: API supports: '1024x1024', '1024x1536', '1536x1024', and 'auto'
     */
    protected function getResolution(string $aspectRatio): array
    {
        $resolutions = [
            '16:9' => ['width' => 1536, 'height' => 1024, 'size' => '1536x1024'],
            '9:16' => ['width' => 1024, 'height' => 1536, 'size' => '1024x1536'],
            '1:1' => ['width' => 1024, 'height' => 1024, 'size' => '1024x1024'],
            '4:5' => ['width' => 1024, 'height' => 1280, 'size' => '1024x1536'],
            '4:3' => ['width' => 1024, 'height' => 1024, 'size' => '1024x1024'],
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
                'model' => 'gemini-2.0-flash-exp-image-generation',
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
                'model' => 'gemini-2.0-flash-exp-image-generation',
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
                'model' => 'gemini-2.0-flash-exp-image-generation',
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
