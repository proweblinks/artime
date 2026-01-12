<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\MiniMaxService;
use App\Services\RunPodService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppVideoWizard\Models\WizardAsset;
use Modules\AppVideoWizard\Models\WizardProject;

/**
 * AnimationService - Bridge service for video animation generation.
 *
 * Supports multiple video animation providers:
 * - MiniMax (video-01): Standard I2V animation
 * - Multitalk (RunPod): Lip-sync animation for dialogue scenes
 */
class AnimationService
{
    protected MiniMaxService $miniMaxService;
    protected RunPodService $runPodService;

    /**
     * Available animation models with their configurations.
     */
    public const ANIMATION_MODELS = [
        'minimax' => [
            'name' => 'MiniMax',
            'description' => 'High quality I2V animation',
            'durations' => [5, 6, 10],
            'defaultDuration' => 6,
            'supportsLipSync' => false,
            'provider' => 'minimax',
        ],
        'multitalk' => [
            'name' => 'Multitalk',
            'description' => 'Lip-sync for dialogue scenes',
            'durations' => [5, 10, 15, 20],
            'defaultDuration' => 5,
            'supportsLipSync' => true,
            'provider' => 'runpod',
            'requiresAudio' => true,
        ],
    ];

    public function __construct(MiniMaxService $miniMaxService, RunPodService $runPodService)
    {
        $this->miniMaxService = $miniMaxService;
        $this->runPodService = $runPodService;
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
        $model = $options['model'] ?? 'minimax';
        $imageUrl = $options['imageUrl'] ?? null;
        $prompt = $options['prompt'] ?? '';
        $duration = $options['duration'] ?? 6;
        $audioUrl = $options['audioUrl'] ?? null;

        if (empty($imageUrl)) {
            return $this->errorResponse('Image URL is required');
        }

        $modelConfig = self::ANIMATION_MODELS[$model] ?? self::ANIMATION_MODELS['minimax'];

        // Route to appropriate provider
        try {
            if ($modelConfig['provider'] === 'runpod' && $model === 'multitalk') {
                return $this->generateWithMultitalk($project, $imageUrl, $prompt, $audioUrl, $duration);
            } else {
                return $this->generateWithMiniMax($project, $imageUrl, $prompt, $duration);
            }
        } catch (\Throwable $e) {
            Log::error("AnimationService error: " . $e->getMessage(), [
                'model' => $model,
                'project_id' => $project->id,
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Generate animation using MiniMax video-01.
     */
    protected function generateWithMiniMax(
        WizardProject $project,
        string $imageUrl,
        string $prompt,
        int $duration
    ): array {
        // Check app credits first (consistent with ImageGenerationService)
        $teamId = $project->team_id ?? session('current_team_id', 0);
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            return $this->errorResponse($quota['message']);
        }

        // Check if MiniMax API key is configured
        $apiKey = (string) get_option('ai_minimax_api_key', '');
        if (empty($apiKey)) {
            return $this->errorResponse('MiniMax API key not configured. Please add your API key in Admin Panel > AI Configuration > MiniMax.');
        }

        // Validate API key format (MiniMax keys typically start with 'ey')
        if (strlen($apiKey) < 20) {
            Log::warning("AnimationService: MiniMax API key appears invalid", [
                'key_length' => strlen($apiKey),
            ]);
            return $this->errorResponse('MiniMax API key appears to be invalid. Please verify your API key in Admin Panel > AI Configuration > MiniMax.');
        }

        Log::info("AnimationService: Generating with MiniMax", [
            'project_id' => $project->id,
            'duration' => $duration,
            'key_length' => strlen($apiKey),
            'key_prefix' => substr($apiKey, 0, 4),
        ]);

        // Create fresh MiniMaxService instance to ensure current API key is used
        // (Laravel's DI container may cache an instance with old/empty API key)
        $miniMaxService = new MiniMaxService();

        // Pass duration to MiniMax - it will automatically select the right model
        // For 10s videos, MiniMaxService uses MiniMax-Hailuo-02 model
        $result = $miniMaxService->generateVideo($prompt, [
            'first_frame_image' => $imageUrl,
            'duration' => $duration,
        ]);

        if (!empty($result['error'])) {
            // Translate MiniMax-specific errors to user-friendly messages
            $errorMsg = $result['error'];
            if (stripos($errorMsg, 'insufficient balance') !== false || stripos($errorMsg, 'insufficient_balance') !== false) {
                $errorMsg = 'MiniMax API account has insufficient balance. Please contact admin to add funds to the MiniMax account, or check API key configuration.';
            } elseif (stripos($errorMsg, 'invalid api key') !== false) {
                $errorMsg .= ' (Key length: ' . strlen($apiKey) . ', prefix: ' . substr($apiKey, 0, 4) . '...)';
            }
            return $this->errorResponse($errorMsg);
        }

        $data = $result['data'] ?? [];

        if (isset($data['task_id'])) {
            return [
                'success' => true,
                'taskId' => $data['task_id'],
                'provider' => 'minimax',
                'status' => 'processing',
            ];
        }

        return $this->errorResponse('Unexpected response from MiniMax');
    }

    /**
     * Generate lip-sync animation using Multitalk via RunPod.
     */
    protected function generateWithMultitalk(
        WizardProject $project,
        string $imageUrl,
        string $prompt,
        ?string $audioUrl,
        int $duration
    ): array {
        // Check app credits first (consistent with ImageGenerationService)
        $teamId = $project->team_id ?? session('current_team_id', 0);
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            return $this->errorResponse($quota['message']);
        }

        $endpointId = (string) get_option('runpod_multitalk_endpoint', '');

        if (empty($endpointId)) {
            return $this->errorResponse('Multitalk endpoint not configured. Please configure RunPod Multitalk endpoint in Admin Panel.');
        }

        if (empty($audioUrl)) {
            return $this->errorResponse('Audio URL is required for Multitalk lip-sync animation');
        }

        Log::info("AnimationService: Generating with Multitalk", [
            'project_id' => $project->id,
            'endpoint' => $endpointId,
            'duration' => $duration,
        ]);

        $input = [
            'image_url' => $imageUrl,
            'audio_url' => $audioUrl,
            'prompt' => $prompt,
            'duration' => $duration,
        ];

        $result = $this->runPodService->runAsync($endpointId, $input);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Failed to submit Multitalk job');
        }

        return [
            'success' => true,
            'taskId' => $result['id'],
            'provider' => 'multitalk',
            'status' => 'processing',
            'endpointId' => $endpointId,
        ];
    }

    /**
     * Check the status of a video generation task.
     *
     * @param string $taskId The task/job ID
     * @param string $provider The provider (minimax or multitalk)
     * @param string|null $endpointId RunPod endpoint ID (for multitalk)
     * @return array Status information
     */
    public function getTaskStatus(string $taskId, string $provider, ?string $endpointId = null): array
    {
        try {
            if ($provider === 'multitalk') {
                return $this->getMultitalkStatus($taskId, $endpointId);
            } else {
                return $this->getMiniMaxStatus($taskId);
            }
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
     * Get MiniMax task status.
     */
    protected function getMiniMaxStatus(string $taskId): array
    {
        Log::info("AnimationService: Checking MiniMax status", ['taskId' => $taskId]);

        // Create fresh instance to ensure current API key is used
        $miniMaxService = new MiniMaxService();
        $status = $miniMaxService->getVideoTaskStatus($taskId);

        Log::info("AnimationService: MiniMax raw status response", [
            'taskId' => $taskId,
            'status' => $status['status'] ?? 'none',
            'file_id' => $status['file_id'] ?? 'none',
            'error' => $status['error'] ?? 'none',
        ]);

        // FIX: MiniMax API returns lowercase status values, but we need to handle both cases
        // Map both capitalized and lowercase versions for robustness
        $statusMap = [
            // Capitalized (original expected format)
            'Queueing' => 'queued',
            'Processing' => 'processing',
            'Success' => 'completed',
            'Fail' => 'failed',
            // Lowercase (actual MiniMax API format)
            'queueing' => 'queued',
            'processing' => 'processing',
            'success' => 'completed',
            'fail' => 'failed',
            'failed' => 'failed',
            // Additional possible values
            'pending' => 'queued',
            'running' => 'processing',
            'completed' => 'completed',
            'error' => 'failed',
        ];

        $rawStatus = $status['status'] ?? 'unknown';
        $normalizedStatus = $statusMap[$rawStatus] ?? strtolower($rawStatus);

        Log::info("AnimationService: Status normalized", [
            'raw' => $rawStatus,
            'normalized' => $normalizedStatus,
        ]);

        $result = [
            'success' => true,
            'status' => $normalizedStatus,
            'provider' => 'minimax',
        ];

        if ($normalizedStatus === 'completed' && !empty($status['file_id'])) {
            Log::info("AnimationService: Video completed, fetching download URL", [
                'taskId' => $taskId,
                'file_id' => $status['file_id'],
            ]);
            $downloadUrl = $miniMaxService->getVideoDownloadUrl($status['file_id']);
            if ($downloadUrl) {
                $result['videoUrl'] = $downloadUrl;
                Log::info("AnimationService: Got video URL", ['url' => substr($downloadUrl, 0, 100) . '...']);
            } else {
                Log::warning("AnimationService: Failed to get download URL for file_id", ['file_id' => $status['file_id']]);
            }
        }

        if (!empty($status['error'])) {
            $result['error'] = $status['error'];
        }

        return $result;
    }

    /**
     * Get Multitalk/RunPod task status.
     */
    protected function getMultitalkStatus(string $taskId, ?string $endpointId = null): array
    {
        $endpointId = $endpointId ?: (string) get_option('runpod_multitalk_endpoint', '');

        if (empty($endpointId)) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => 'Multitalk endpoint not configured',
            ];
        }

        $status = $this->runPodService->getStatus($endpointId, $taskId);

        if (!$status['success']) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $status['error'] ?? 'Failed to get status',
            ];
        }

        $statusMap = [
            'IN_QUEUE' => 'queued',
            'IN_PROGRESS' => 'processing',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'TIMED_OUT' => 'timeout',
        ];

        $normalizedStatus = $statusMap[$status['status']] ?? strtolower($status['status']);

        $result = [
            'success' => true,
            'status' => $normalizedStatus,
            'provider' => 'multitalk',
        ];

        if ($normalizedStatus === 'completed' && isset($status['output'])) {
            // RunPod output typically contains the video URL
            if (is_array($status['output']) && isset($status['output']['video_url'])) {
                $result['videoUrl'] = $status['output']['video_url'];
            } elseif (is_string($status['output'])) {
                $result['videoUrl'] = $status['output'];
            }
        }

        if (!empty($status['error'])) {
            $result['error'] = $status['error'];
        }

        return $result;
    }

    /**
     * Get available animation models with their configurations.
     */
    public function getAvailableModels(): array
    {
        $models = [];

        foreach (self::ANIMATION_MODELS as $key => $config) {
            $available = true;

            // Check provider availability
            if ($config['provider'] === 'runpod') {
                $endpointId = (string) get_option('runpod_multitalk_endpoint', '');
                $available = !empty($endpointId) && $this->runPodService->isConfigured();
            }

            $models[$key] = array_merge($config, [
                'available' => $available,
            ]);
        }

        return $models;
    }

    /**
     * Determine recommended model based on shot type.
     */
    public function getRecommendedModel(array $shot): string
    {
        // If shot has dialogue/audio, recommend Multitalk
        if (!empty($shot['audioUrl']) || !empty($shot['dialogueAudio']) || !empty($shot['voiceoverUrl'])) {
            $multitalkAvailable = !empty(get_option('runpod_multitalk_endpoint', ''));
            if ($multitalkAvailable) {
                return 'multitalk';
            }
        }

        // Default to MiniMax for standard I2V
        return 'minimax';
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
     * This prevents video URL expiration issues by downloading videos
     * from provider's temporary signed URLs and storing them in our
     * permanent storage (local or cloud).
     *
     * @param string $temporaryUrl The temporary signed URL from video provider
     * @param WizardProject $project The project context
     * @param int $sceneIndex Scene index for organization
     * @param int $shotIndex Shot index for organization
     * @param string $provider The video provider (minimax, multitalk)
     * @return array Result with permanent URL or error
     */
    public function downloadAndStoreVideo(
        string $temporaryUrl,
        WizardProject $project,
        int $sceneIndex,
        int $shotIndex,
        string $provider = 'minimax'
    ): array {
        try {
            Log::info('AnimationService: Downloading video for permanent storage', [
                'project_id' => $project->id,
                'sceneIndex' => $sceneIndex,
                'shotIndex' => $shotIndex,
                'provider' => $provider,
                'tempUrl' => substr($temporaryUrl, 0, 100) . '...',
            ]);

            // Download the video with longer timeout for large files
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
                    'fallbackUrl' => $temporaryUrl, // Return temp URL as fallback
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

            // Generate permanent storage path
            $userId = $project->user_id ?? 0;
            $projectId = $project->id;
            $timestamp = time();
            $filename = "wizard-videos/{$userId}/{$projectId}/scene_{$sceneIndex}_shot_{$shotIndex}_{$timestamp}.mp4";

            // Determine storage disk (prefer GCS if configured, else public)
            $disk = 'public';
            $permanentUrl = null;

            if (config('filesystems.disks.gcs.bucket')) {
                $disk = 'gcs';
                Storage::disk($disk)->put($filename, $videoContent, 'public');
                $bucket = config('filesystems.disks.gcs.bucket');
                $permanentUrl = "https://storage.googleapis.com/{$bucket}/{$filename}";
            } else {
                Storage::disk($disk)->put($filename, $videoContent);
                $permanentUrl = Storage::disk($disk)->url($filename);
            }

            Log::info('AnimationService: Video stored permanently', [
                'disk' => $disk,
                'path' => $filename,
                'size' => $fileSize,
                'permanentUrl' => substr($permanentUrl, 0, 100) . '...',
            ]);

            // Create WizardAsset record to track the video
            $asset = WizardAsset::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'type' => WizardAsset::TYPE_VIDEO,
                'name' => "Scene {$sceneIndex} Shot {$shotIndex} Video",
                'path' => $filename,
                'url' => $permanentUrl,
                'mime_type' => 'video/mp4',
                'file_size' => $fileSize,
                'scene_index' => $sceneIndex,
                'metadata' => [
                    'provider' => $provider,
                    'shot_index' => $shotIndex,
                    'original_url' => $temporaryUrl,
                    'stored_at' => now()->toIso8601String(),
                ],
            ]);

            return [
                'success' => true,
                'permanentUrl' => $permanentUrl,
                'assetId' => $asset->id,
                'path' => $filename,
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
                'fallbackUrl' => $temporaryUrl, // Return temp URL as fallback
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
        // Common patterns for signed/temporary URLs
        $temporaryPatterns = [
            'aliyuncs.com',      // Alibaba Cloud OSS
            'amazonaws.com',     // AWS S3 signed URLs
            'blob.core.windows', // Azure Blob
            'Expires=',          // URL contains expiration parameter
            'X-Amz-Expires',     // AWS signature expiration
            'se=',               // Azure SAS token expiration
            'oss-cn-',           // Alibaba OSS region
            'minimax',           // MiniMax API URLs
            'runpod',            // RunPod URLs
        ];

        foreach ($temporaryPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
