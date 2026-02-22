<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\WaveSpeedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppVideoWizard\Models\WizardAsset;
use Modules\AppVideoWizard\Models\WizardProject;

/**
 * AnimationService - Bridge service for video animation generation.
 *
 * Uses Seedance v1.5 Pro (via WaveSpeed API) as the sole video generation engine.
 */
class AnimationService
{
    /**
     * Available animation models with their configurations.
     */
    public const ANIMATION_MODELS = [
        'seedance' => [
            'name' => 'Seedance v1.5 Pro',
            'description' => 'Cinematic video with auto-generated audio and lip-sync',
            'durations' => [4, 5, 6, 8, 10, 12],
            'defaultDuration' => 8,
            'supportsLipSync' => true,
            'supportsAudioGen' => true,
            'requiresAudio' => false,
            'supportsTextToVideo' => false,
            'provider' => 'wavespeed',
            'variants' => [
                'pro'  => ['name' => 'Pro', 'description' => 'Full-fidelity, production-grade quality'],
                'fast' => ['name' => 'Fast', 'description' => 'Speed-optimized, cheaper per run'],
            ],
        ],
        'seedance_v2' => [
            'name' => 'Seedance 2.0',
            'description' => 'Next-gen cinematic video with extended durations, timecoded segments, and style transitions',
            'durations' => [4, 5, 6, 8, 10, 12, 15, 20],
            'defaultDuration' => 10,
            'supportsLipSync' => true,
            'supportsAudioGen' => true,
            'requiresAudio' => false,
            'supportsTextToVideo' => true,
            'supportsMultiReference' => true,
            'supportsTimecodedSegments' => true,
            'supportsStyleTransitions' => true,
            'provider' => 'wavespeed',
            'variants' => [
                'pro'  => ['name' => 'Pro', 'description' => 'Full-fidelity v2.0 quality'],
                'fast' => ['name' => 'Fast', 'description' => 'Speed-optimized v2.0'],
            ],
            'status' => 'pending_endpoint',
        ],
    ];

    public function __construct()
    {
        //
    }

    /**
     * Generate animation from an image.
     *
     * @param WizardProject $project The project context
     * @param array $options Animation options
     * @return array Result with success, videoUrl/taskId, or error
     */
    public function generateAnimation(WizardProject $project, array $options): array
    {
        $imageUrl = $options['imageUrl'] ?? null;
        $prompt = $options['prompt'] ?? '';
        $duration = $options['duration'] ?? 8;

        if (empty($imageUrl)) {
            return $this->errorResponse('Image URL is required');
        }

        try {
            return $this->generateWithSeedance($project, $imageUrl, $prompt, $duration, $options);
        } catch (\Throwable $e) {
            Log::error("AnimationService error: " . $e->getMessage(), [
                'model' => 'seedance',
                'project_id' => $project->id,
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Generate video using Seedance v1.5 Pro via WaveSpeed API.
     */
    protected function generateWithSeedance(
        WizardProject $project,
        string $imageUrl,
        string $prompt,
        int $duration,
        array $options = []
    ): array {
        $teamId = $project->team_id ?? session('current_team_id', 0);
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            return $this->errorResponse($quota['message']);
        }

        $waveSpeedService = new WaveSpeedService();

        if (!$waveSpeedService->isConfigured()) {
            return $this->errorResponse('WaveSpeed API key not configured. Please add your API key in Admin Panel > AI Configuration > WaveSpeed AI.');
        }

        Log::info('AnimationService: Generating with Seedance v1.5 Pro', [
            'project_id' => $project->id,
            'duration' => $duration,
            'aspect_ratio' => $options['aspect_ratio'] ?? '9:16',
            'prompt_length' => strlen($prompt),
        ]);

        $result = $waveSpeedService->generateVideo($imageUrl, $prompt, [
            'aspect_ratio' => $options['aspect_ratio'] ?? '9:16',
            'duration' => $duration,
            'resolution' => $options['resolution'] ?? '1080p',
            'generate_audio' => $options['generate_audio'] ?? true,
            'anti_speech' => $options['anti_speech'] ?? true,
            'camera_fixed' => $options['camera_fixed'] ?? false,
            'variant' => $options['variant'] ?? 'pro',
            'end_image_url' => $options['end_image_url'] ?? null,
            'seedance_version' => $options['seedance_version'] ?? '1.5',
        ]);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Failed to submit Seedance job');
        }

        return [
            'success' => true,
            'taskId' => $result['taskId'],
            'provider' => 'wavespeed',
            'status' => 'processing',
        ];
    }

    /**
     * Check the status of a video generation task.
     *
     * @param string $taskId The task/job ID
     * @param string $provider The provider (wavespeed)
     * @param string|null $endpointId Unused, kept for API compatibility
     * @return array Status information
     */
    public function getTaskStatus(string $taskId, string $provider = 'wavespeed', ?string $endpointId = null): array
    {
        try {
            return $this->getWaveSpeedStatus($taskId);
        } catch (\Throwable $e) {
            Log::error("AnimationService status check error: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get WaveSpeed/Seedance task status.
     */
    protected function getWaveSpeedStatus(string $taskId): array
    {
        $waveSpeedService = new WaveSpeedService();
        return $waveSpeedService->getTaskStatus($taskId);
    }

    /**
     * Get available animation models with their configurations.
     */
    public function getAvailableModels(): array
    {
        $waveSpeedService = new WaveSpeedService();
        $available = $waveSpeedService->isConfigured();

        $models = [
            'seedance' => array_merge(self::ANIMATION_MODELS['seedance'], [
                'available' => $available,
            ]),
        ];

        // Include v2 only if endpoint is configured
        $v2Config = self::ANIMATION_MODELS['seedance_v2'];
        $v2Endpoint = \Modules\AppVideoWizard\Models\VwSetting::getValue('seedance_v2_endpoint', '');
        $models['seedance_v2'] = array_merge($v2Config, [
            'available' => $available && !empty($v2Endpoint),
            'status' => !empty($v2Endpoint) ? 'active' : 'pending_endpoint',
        ]);

        return $models;
    }

    /**
     * Determine recommended model based on shot type.
     */
    public function getRecommendedModel(array $shot): string
    {
        return 'seedance';
    }

    /**
     * Error response helper.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }

    /**
     * Download video from temporary URL and store permanently.
     *
     * @param string $temporaryUrl The temporary signed URL from video provider
     * @param WizardProject $project The project context
     * @param int $sceneIndex Scene index for organization
     * @param int $shotIndex Shot index for organization
     * @param string $provider The video provider
     * @return array Result with permanent URL or error
     */
    public function downloadAndStoreVideo(
        string $temporaryUrl,
        WizardProject $project,
        int $sceneIndex,
        int $shotIndex,
        string $provider = 'wavespeed'
    ): array {
        try {
            Log::info('AnimationService: Downloading video for permanent storage', [
                'project_id' => $project->id,
                'sceneIndex' => $sceneIndex,
                'shotIndex' => $shotIndex,
                'provider' => $provider,
                'tempUrl' => substr($temporaryUrl, 0, 100) . '...',
            ]);

            $response = Http::timeout(120)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; VideoWizard/1.0)',
            ])->get($temporaryUrl);

            if (!$response->successful()) {
                Log::error('AnimationService: Failed to download video', [
                    'status' => $response->status(),
                    'project_id' => $project->id,
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to download video: HTTP ' . $response->status(),
                    'fallbackUrl' => $temporaryUrl,
                ];
            }

            $videoContent = $response->body();
            $fileSize = strlen($videoContent);

            if ($fileSize < 1000) {
                Log::error('AnimationService: Downloaded file too small, likely an error', [
                    'size' => $fileSize,
                    'content_preview' => substr($videoContent, 0, 200),
                ]);
                return [
                    'success' => false,
                    'error' => 'Downloaded file appears invalid (too small)',
                    'fallbackUrl' => $temporaryUrl,
                ];
            }

            $userId = $project->user_id ?? 0;
            $projectId = $project->id;
            $timestamp = time();
            $filename = "scene_{$sceneIndex}_shot_{$shotIndex}_{$timestamp}.mp4";
            $relativePath = "wizard-videos/{$userId}/{$projectId}/{$filename}";
            $publicPath = public_path($relativePath);

            $directory = dirname($publicPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($publicPath, $videoContent);

            $videoPath = "{$userId}/{$projectId}/{$filename}";
            $permanentUrl = rtrim(config('app.url'), '/') . '/video.php?path=' . urlencode($videoPath);

            Log::info('AnimationService: Video stored permanently in public folder', [
                'path' => $relativePath,
                'fullPath' => $publicPath,
                'size' => $fileSize,
                'permanentUrl' => $permanentUrl,
            ]);

            $asset = WizardAsset::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'type' => WizardAsset::TYPE_VIDEO,
                'name' => "Scene {$sceneIndex} Shot {$shotIndex} Video",
                'path' => $relativePath,
                'url' => $permanentUrl,
                'mime_type' => 'video/mp4',
                'file_size' => $fileSize,
                'scene_index' => $sceneIndex,
                'metadata' => [
                    'provider' => $provider,
                    'shot_index' => $shotIndex,
                    'original_url' => $temporaryUrl,
                    'stored_at' => now()->toIso8601String(),
                    'storage_location' => 'public_folder',
                ],
            ]);

            return [
                'success' => true,
                'permanentUrl' => $permanentUrl,
                'assetId' => $asset->id,
                'path' => $relativePath,
                'fileSize' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error('AnimationService: Exception storing video', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallbackUrl' => $temporaryUrl,
            ];
        }
    }

    /**
     * Check if a video URL is a temporary signed URL that will expire.
     *
     * @param string $url The video URL to check
     * @return bool True if URL appears to be temporary/signed
     */
    public function isTemporaryUrl(string $url): bool
    {
        $temporaryPatterns = [
            'wavespeed',         // WaveSpeed AI signed URLs
            'aliyuncs.com',      // Alibaba Cloud OSS
            'amazonaws.com',     // AWS S3 signed URLs
            'blob.core.windows', // Azure Blob
            'Expires=',          // URL contains expiration parameter
            'X-Amz-Expires',     // AWS signature expiration
            'se=',               // Azure SAS token expiration
            'oss-cn-',           // Alibaba OSS region
        ];

        foreach ($temporaryPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
