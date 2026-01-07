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

        // Route to appropriate provider
        if ($modelConfig['provider'] === 'runpod') {
            return $this->generateWithHiDream($project, $scene, $prompt, $resolution, $options);
        } else {
            return $this->generateWithGemini($project, $scene, $prompt, $resolution, $modelId, $modelConfig, $options);
        }
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
                $publicUrl = Storage::disk('public')->url($storedPath);

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
     */
    protected function generateWithGemini(
        WizardProject $project,
        array $scene,
        string $prompt,
        array $resolution,
        string $modelId,
        array $modelConfig,
        array $options = []
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

        // Generate using Gemini service
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
            $imageUrl = Storage::disk('public')->url($storedPath);
        } elseif (isset($imageData['url'])) {
            $imageUrl = $imageData['url'];
            $storedPath = $this->storeImage($imageUrl, $project, $scene['id']);
            $imageUrl = Storage::disk('public')->url($storedPath);
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
     * Prompt Chain Architecture (4 Layers):
     * 1. Style Bible - Visual DNA, style, color grade, atmosphere, camera
     * 2. Character Bible - Character descriptions for this scene
     * 3. Location Bible - Location descriptions for this scene
     * 4. Scene Content - Visual description + visual style + technical specs
     */
    protected function buildImagePrompt(
        string $visualDescription,
        ?array $styleBible,
        ?array $visualStyle,
        WizardProject $project,
        ?array $sceneMemory = null,
        ?int $sceneIndex = null
    ): string {
        $parts = [];

        // =========================================================================
        // LAYER 1: STYLE BIBLE (Visual DNA)
        // =========================================================================
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            $styleParts = [];

            // Core visual style
            if (!empty($styleBible['style'])) {
                $styleParts[] = $styleBible['style'];
            }

            // Color grading
            if (!empty($styleBible['colorGrade'])) {
                $styleParts[] = $styleBible['colorGrade'];
            }

            // Atmosphere/mood
            if (!empty($styleBible['atmosphere'])) {
                $styleParts[] = $styleBible['atmosphere'];
            }

            // Camera language (new field)
            if (!empty($styleBible['camera'])) {
                $styleParts[] = $styleBible['camera'];
            }

            if (!empty($styleParts)) {
                $parts[] = 'STYLE: ' . implode(', ', $styleParts);
            }

            // Visual DNA as quality anchor
            if (!empty($styleBible['visualDNA'])) {
                $parts[] = 'QUALITY: ' . $styleBible['visualDNA'];
            }
        }

        // =========================================================================
        // LAYER 2: CHARACTER BIBLE (Characters in this scene)
        // =========================================================================
        if ($sceneMemory && $sceneIndex !== null) {
            $characterBible = $sceneMemory['characterBible'] ?? null;
            if ($characterBible && ($characterBible['enabled'] ?? false) && !empty($characterBible['characters'])) {
                $sceneCharacters = $this->getCharactersForScene($characterBible['characters'], $sceneIndex);
                if (!empty($sceneCharacters)) {
                    $characterDescriptions = [];
                    foreach ($sceneCharacters as $character) {
                        if (!empty($character['description'])) {
                            $name = $character['name'] ?? 'Character';
                            $charDesc = "{$name}: {$character['description']}";

                            // Include traits if available for personality/expression guidance
                            $traits = $character['traits'] ?? [];
                            if (!empty($traits)) {
                                $charDesc .= ' (personality: ' . implode(', ', array_slice($traits, 0, 4)) . ')';
                            }

                            $characterDescriptions[] = $charDesc;
                        }
                    }
                    if (!empty($characterDescriptions)) {
                        $parts[] = 'CHARACTERS: ' . implode('. ', $characterDescriptions);
                    }
                }
            }
        }

        // =========================================================================
        // LAYER 3: LOCATION BIBLE (Location for this scene)
        // =========================================================================
        if ($sceneMemory && $sceneIndex !== null) {
            $locationBible = $sceneMemory['locationBible'] ?? null;
            if ($locationBible && ($locationBible['enabled'] ?? false) && !empty($locationBible['locations'])) {
                $sceneLocation = $this->getLocationForScene($locationBible['locations'], $sceneIndex);
                if ($sceneLocation) {
                    $locationParts = [];

                    // Location name and type
                    $locName = $sceneLocation['name'] ?? '';
                    $locType = $sceneLocation['type'] ?? '';
                    if ($locName) {
                        $locationParts[] = $locName . ($locType ? " ({$locType})" : '');
                    }

                    // Location description
                    if (!empty($sceneLocation['description'])) {
                        $locationParts[] = $sceneLocation['description'];
                    }

                    // Time of day
                    if (!empty($sceneLocation['timeOfDay'])) {
                        $timeDescriptions = [
                            'day' => 'daytime, natural daylight',
                            'night' => 'nighttime, dark with artificial or moonlight',
                            'dawn' => 'dawn, early morning light, soft colors',
                            'dusk' => 'dusk, twilight, fading light',
                            'golden-hour' => 'golden hour, warm sunset lighting',
                        ];
                        $locationParts[] = $timeDescriptions[$sceneLocation['timeOfDay']] ?? $sceneLocation['timeOfDay'];
                    }

                    // Weather
                    if (!empty($sceneLocation['weather']) && $sceneLocation['weather'] !== 'clear') {
                        $weatherDescriptions = [
                            'cloudy' => 'overcast sky, diffused light',
                            'rainy' => 'rain, wet surfaces, reflections',
                            'foggy' => 'fog, mist, atmospheric haze',
                            'stormy' => 'storm, dramatic clouds, lightning',
                            'snowy' => 'snow, winter, frost',
                        ];
                        $locationParts[] = $weatherDescriptions[$sceneLocation['weather']] ?? $sceneLocation['weather'];
                    }

                    // Atmosphere if available
                    if (!empty($sceneLocation['atmosphere'])) {
                        $locationParts[] = $sceneLocation['atmosphere'] . ' atmosphere';
                    }

                    // Location state for this scene if available
                    $locationState = $this->getLocationStateForScene($sceneLocation, $sceneIndex);
                    if ($locationState) {
                        $locationParts[] = 'current state: ' . $locationState;
                    }

                    if (!empty($locationParts)) {
                        $parts[] = 'LOCATION: ' . implode(', ', $locationParts);
                    }
                }
            }
        }

        // =========================================================================
        // LAYER 4: SCENE CONTENT (Visual description + Visual Style)
        // =========================================================================

        // Visual Style parameters from storyboard UI
        if ($visualStyle) {
            $visualParts = [];

            // Mood
            if (!empty($visualStyle['mood'])) {
                $moodDescriptions = [
                    'epic' => 'epic, grand scale, dramatic atmosphere',
                    'intimate' => 'intimate, personal, close emotional connection',
                    'mysterious' => 'mysterious, atmospheric, enigmatic',
                    'energetic' => 'energetic, dynamic, high energy',
                    'contemplative' => 'contemplative, reflective, calm',
                    'tense' => 'tense, suspenseful, high stakes',
                    'hopeful' => 'hopeful, optimistic, warm',
                    'professional' => 'professional, polished, business-like',
                    'inspiring' => 'inspiring, uplifting, motivational',
                    'dramatic' => 'dramatic, intense, emotionally charged',
                    'playful' => 'playful, fun, lighthearted',
                    'nostalgic' => 'nostalgic, warm memories, vintage feel',
                    'dark' => 'dark, moody, brooding',
                    'romantic' => 'romantic, intimate, tender',
                ];
                $visualParts[] = $moodDescriptions[$visualStyle['mood']] ?? $visualStyle['mood'];
            }

            // Lighting
            if (!empty($visualStyle['lighting'])) {
                $lightingDescriptions = [
                    'natural' => 'natural daylight, soft shadows',
                    'golden-hour' => 'golden hour lighting, warm sunset tones',
                    'blue-hour' => 'blue hour, twilight, cool ambient light',
                    'high-key' => 'high-key lighting, bright and minimal shadows',
                    'low-key' => 'low-key lighting, dramatic shadows, noir style',
                    'neon' => 'neon lighting, cyberpunk, vibrant colored lights',
                    'studio' => 'studio lighting, controlled, professional',
                    'dramatic' => 'dramatic lighting, strong contrast, shadows',
                    'soft' => 'soft diffused lighting, gentle shadows',
                    'bright' => 'bright, well-lit, clear visibility',
                    'golden' => 'golden warm lighting, sun-kissed',
                ];
                $visualParts[] = $lightingDescriptions[$visualStyle['lighting']] ?? $visualStyle['lighting'];
            }

            // Color Palette
            if (!empty($visualStyle['colorPalette'])) {
                $colorDescriptions = [
                    'teal-orange' => 'cinematic teal and orange color grading',
                    'warm-tones' => 'warm color palette, reds and oranges',
                    'warm' => 'warm color palette, inviting tones',
                    'cool-tones' => 'cool color palette, blues and greens',
                    'cool' => 'cool color palette, blues and teals',
                    'desaturated' => 'desaturated colors, muted tones',
                    'vibrant' => 'vibrant, saturated, bold colors',
                    'pastel' => 'pastel colors, soft and gentle tones',
                    'neutral' => 'neutral color palette, balanced tones',
                    'rich' => 'rich, deep colors, luxurious palette',
                    'dark' => 'dark color palette, shadowy tones',
                ];
                $visualParts[] = $colorDescriptions[$visualStyle['colorPalette']] ?? $visualStyle['colorPalette'];
            }

            // Composition/Shot
            if (!empty($visualStyle['composition'])) {
                $shotDescriptions = [
                    'wide' => 'wide shot, establishing shot',
                    'medium' => 'medium shot, character framing',
                    'close-up' => 'close-up shot, facial detail',
                    'extreme-close-up' => 'extreme close-up, detail focus',
                    'low-angle' => 'low angle shot, powerful perspective',
                    'birds-eye' => 'bird\'s eye view, overhead perspective',
                    'over-shoulder' => 'over the shoulder shot',
                    'tracking' => 'tracking shot perspective',
                ];
                $visualParts[] = $shotDescriptions[$visualStyle['composition']] ?? $visualStyle['composition'];
            }

            if (!empty($visualParts)) {
                $parts[] = 'VISUAL: ' . implode(', ', $visualParts);
            }
        }

        // Main visual description (the scene content)
        if (!empty($visualDescription)) {
            $parts[] = 'SCENE: ' . $visualDescription;
        }

        // =========================================================================
        // LAYER 5: TECHNICAL SPECS
        // =========================================================================
        $technicalSpecs = $project->storyboard['technicalSpecs'] ?? null;
        if ($technicalSpecs && ($technicalSpecs['enabled'] ?? true)) {
            $techParts = [];

            // Positive prompts
            if (!empty($technicalSpecs['positive'])) {
                $techParts[] = $technicalSpecs['positive'];
            } else {
                $techParts[] = 'high quality, detailed, professional, 8K resolution, sharp focus';
            }

            // Quality based on aspect ratio
            $aspectRatio = $project->aspect_ratio ?? '16:9';
            $techParts[] = "optimized for {$aspectRatio} aspect ratio";

            $parts[] = implode(', ', $techParts);
        } else {
            // Default technical specs
            $parts[] = '4K, ultra detailed, cinematic, professional composition';
        }

        // Combine all parts with proper separation
        $finalPrompt = implode('. ', array_filter($parts));

        // Log for debugging
        Log::debug('ImageGenerationService: Built prompt', [
            'sceneIndex' => $sceneIndex,
            'promptLength' => strlen($finalPrompt),
            'hasStyleBible' => !empty($styleBible['enabled']),
            'hasCharacterBible' => !empty($sceneMemory['characterBible']['enabled']),
            'hasLocationBible' => !empty($sceneMemory['locationBible']['enabled']),
        ]);

        return $finalPrompt;
    }

    /**
     * Get characters that appear in a specific scene.
     */
    protected function getCharactersForScene(array $characters, int $sceneIndex): array
    {
        return array_filter($characters, function ($character) use ($sceneIndex) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];
            return in_array($sceneIndex, $appliedScenes);
        });
    }

    /**
     * Get the primary location for a specific scene.
     */
    protected function getLocationForScene(array $locations, int $sceneIndex): ?array
    {
        foreach ($locations as $location) {
            $scenes = $location['scenes'] ?? $location['appearsInScenes'] ?? [];
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
            $contents = file_get_contents($imageUrl);
        }

        $filename = Str::slug($sceneId) . '-' . time() . '.png';
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Store base64 image to local storage.
     */
    protected function storeBase64Image(string $base64Data, string $mimeType, WizardProject $project, string $sceneId): string
    {
        $contents = base64_decode($base64Data);

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $filename = Str::slug($sceneId) . '-' . time() . '.' . $extension;
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        Storage::disk('public')->put($path, $contents);

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
                $upscaledUrl = Storage::disk('public')->url($filename);

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
                $editedUrl = Storage::disk('public')->url($filename);

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
                $editedUrl = Storage::disk('public')->url($filename);

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
