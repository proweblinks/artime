<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\MiniMaxService;
use App\Services\RunPodService;
use App\Services\WaveSpeedService;
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
 * - InfiniteTalk (RunPod): Unlimited-length talking video with lip-sync
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
        'infinitetalk' => [
            'name' => 'InfiniteTalk',
            'description' => 'Unlimited-length talking video with lip-sync',
            'durations' => [5, 10, 15, 30, 60],
            'defaultDuration' => 10,
            'supportsLipSync' => true,
            'supportsMultiPerson' => true,
            'provider' => 'runpod',
            'requiresAudio' => true,
        ],
        'seedance' => [
            'name' => 'Seedance v1.5 Pro',
            'description' => 'Cinematic video with auto-generated audio and lip-sync',
            'durations' => [4, 5, 6, 8, 10, 12],
            'defaultDuration' => 8,
            'supportsLipSync' => true,
            'supportsAudioGen' => true,
            'requiresAudio' => false,
            'provider' => 'wavespeed',
            'variants' => [
                'pro'  => ['name' => 'Pro', 'description' => 'Full-fidelity, production-grade quality'],
                'fast' => ['name' => 'Fast', 'description' => 'Speed-optimized, cheaper per run'],
            ],
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
            if ($model === 'seedance') {
                return $this->generateWithSeedance($project, $imageUrl, $prompt, $duration, $options);
            } elseif ($model === 'infinitetalk') {
                return $this->generateWithInfiniteTalk($project, $imageUrl, $prompt, $audioUrl, $duration, $options);
            } elseif ($modelConfig['provider'] === 'runpod' && $model === 'multitalk') {
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
        int $duration,
        array $options = []
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

        // Generate signed upload URL for the video
        $filename = 'multitalk_' . time() . '_' . uniqid() . '.mp4';
        $uploadData = \Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController::generateVideoUploadUrl(
            $project->id,
            $filename
        );

        // Calculate actual audio duration if provided (duration may include padding)
        $audioDuration = $options['audioDuration'] ?? $duration;
        $endPadding = max(0, $duration - $audioDuration);

        Log::info("AnimationService: Generating with Multitalk", [
            'project_id' => $project->id,
            'endpoint' => $endpointId,
            'duration' => $duration,
            'audioDuration' => $audioDuration,
            'endPadding' => $endPadding,
            'upload_url' => substr($uploadData['upload_url'], 0, 80) . '...',
            'video_url' => $uploadData['video_url'],
        ]);

        // Calculate frame count based on total duration (audio + padding)
        $fps = $options['fps'] ?? 25.0;
        $numFrames = (int) ($fps * $duration);

        // Build input with ALL required Multitalk parameters
        $input = [
            // Required file URLs
            'image_url' => $imageUrl,
            'audio_url' => $audioUrl,
            'video_upload_url' => $uploadData['upload_url'],

            // Audio cropping - use audioDuration so audio ends naturally while video continues with padding
            'audio_crop_start_time' => $options['audio_crop_start_time'] ?? 0,
            'audio_crop_end_time' => $options['audio_crop_end_time'] ?? $audioDuration,

            // Prompts
            'positive_prompt' => $prompt ?: 'natural talking head animation, smooth lip sync, realistic facial expressions',
            'negative_prompt' => $options['negative_prompt'] ?? 'blurry, distorted, unnatural movement, artifacts',

            // Video dimensions
            'aspect_ratio' => $options['aspect_ratio'] ?? '16:9',
            'scale_to_length' => $options['scale_to_length'] ?? 1280,
            'scale_to_side' => $options['scale_to_side'] ?? 'width',

            // Animation parameters
            'fps' => $fps,
            'num_frames' => $numFrames,

            // Audio embedding settings
            'embeds_audio_scale' => $options['embeds_audio_scale'] ?? 1.0,
            'embeds_cfg_audio_scale' => $options['embeds_cfg_audio_scale'] ?? 2.0,
            'embeds_multi_audio_type' => $options['embeds_multi_audio_type'] ?? 'add',
            'embeds_normalize_loudness' => $options['embeds_normalize_loudness'] ?? true,

            // Generation settings
            'steps' => $options['steps'] ?? 4,
            'seed' => $options['seed'] ?? -1,
            'scheduler' => $options['scheduler'] ?? 'euler',
        ];

        $result = $this->runPodService->runAsync($endpointId, $input);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Failed to submit Multitalk job');
        }

        // Store the expected video URL in cache so we can retrieve it when job completes
        $taskId = $result['id'];
        \Illuminate\Support\Facades\Cache::put(
            "multitalk_video_url:{$taskId}",
            $uploadData['video_url'],
            now()->addHours(3)
        );

        return [
            'success' => true,
            'taskId' => $taskId,
            'provider' => 'multitalk',
            'status' => 'processing',
            'endpointId' => $endpointId,
            'expectedVideoUrl' => $uploadData['video_url'],
        ];
    }

    /**
     * Generate lip-sync animation using InfiniteTalk via RunPod.
     */
    protected function generateWithInfiniteTalk(
        WizardProject $project,
        string $imageUrl,
        string $prompt,
        ?string $audioUrl,
        int $duration,
        array $options = []
    ): array {
        if (empty($audioUrl)) {
            return $this->errorResponse('Audio URL is required for InfiniteTalk lip-sync animation');
        }

        $infiniteTalkService = new InfiniteTalkService();

        return $infiniteTalkService->generate($project, $imageUrl, $audioUrl, [
            'prompt' => $prompt ?: 'A person is talking in a natural way with smooth lip movements.',
            'input_type' => $options['input_type'] ?? 'image',
            'person_count' => $options['person_count'] ?? 'single',
            'aspect_ratio' => $options['aspect_ratio'] ?? '16:9',
            'max_frame' => $options['max_frame'] ?? null,
            'audio_url_2' => $options['audio_url_2'] ?? null,
            'wav_base64_2' => $options['wav_base64_2'] ?? null,
            'ots_overlay_audio' => $options['ots_overlay_audio'] ?? null,
        ]);
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
            'generate_audio' => false,
            'camera_fixed' => $options['camera_fixed'] ?? false,
            'variant' => $options['variant'] ?? 'pro',
            'end_image_url' => $options['end_image_url'] ?? null,
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
     * Get WaveSpeed/Seedance task status.
     */
    protected function getWaveSpeedStatus(string $taskId): array
    {
        $waveSpeedService = new WaveSpeedService();
        return $waveSpeedService->getTaskStatus($taskId);
    }

    /**
     * Get InfiniteTalk/RunPod task status.
     */
    protected function getInfiniteTalkStatus(string $taskId, ?string $endpointId = null): array
    {
        $infiniteTalkService = new InfiniteTalkService();
        return $infiniteTalkService->getJobStatus($taskId, $endpointId);
    }

    /**
     * Check the status of a video generation task.
     *
     * @param string $taskId The task/job ID
     * @param string $provider The provider (minimax, multitalk, or infinitetalk)
     * @param string|null $endpointId RunPod endpoint ID (for multitalk/infinitetalk)
     * @return array Status information
     */
    public function getTaskStatus(string $taskId, string $provider, ?string $endpointId = null): array
    {
        try {
            if ($provider === 'wavespeed') {
                return $this->getWaveSpeedStatus($taskId);
            } elseif ($provider === 'infinitetalk') {
                return $this->getInfiniteTalkStatus($taskId, $endpointId);
            } elseif ($provider === 'multitalk') {
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

        if ($normalizedStatus === 'completed') {
            // First, check our cached video URL (from when we submitted the job)
            $cacheKey = "multitalk_video_url:{$taskId}";
            $cachedVideoUrl = \Illuminate\Support\Facades\Cache::get($cacheKey);

            \Log::info('🎬 Multitalk completed, checking for video URL', [
                'taskId' => $taskId,
                'cacheKey' => $cacheKey,
                'hasCachedUrl' => !empty($cachedVideoUrl),
                'cachedUrl' => $cachedVideoUrl ? substr($cachedVideoUrl, 0, 100) : null,
            ]);

            if ($cachedVideoUrl) {
                \Log::info('🎬 Multitalk: Found cached video URL, verifying file exists', [
                    'taskId' => $taskId,
                    'expectedUrl' => $cachedVideoUrl,
                ]);

                // Extract path from URL to check if file exists
                // Note: URL may have /public/ prefix for cPanel hosting, but public_path() already points to public dir
                $urlPath = parse_url($cachedVideoUrl, PHP_URL_PATH);
                $urlPath = ltrim($urlPath, '/');
                // Remove 'public/' prefix if present (cPanel hosting adds this to URLs but not to file paths)
                if (str_starts_with($urlPath, 'public/')) {
                    $urlPath = substr($urlPath, 7); // Remove 'public/' prefix
                }
                $filePath = public_path($urlPath);

                if (file_exists($filePath)) {
                    $result['videoUrl'] = $cachedVideoUrl;
                    \Log::info('🎬 Multitalk video file found', [
                        'taskId' => $taskId,
                        'videoUrl' => $cachedVideoUrl,
                        'fileSize' => filesize($filePath),
                    ]);

                    // Clear the cache entry
                    \Illuminate\Support\Facades\Cache::forget("multitalk_video_url:{$taskId}");
                } else {
                    \Log::warning('🎬 Multitalk job completed but video file not found', [
                        'taskId' => $taskId,
                        'expectedPath' => $filePath,
                    ]);
                }
            }

            // Also check output from RunPod as fallback
            if (!isset($result['videoUrl']) && isset($status['output'])) {
                $output = $status['output'];

                // Log raw output for debugging
                \Log::info('🎬 Multitalk raw output', [
                    'taskId' => $taskId,
                    'outputType' => gettype($output),
                    'output' => is_array($output) ? $output : substr((string)$output, 0, 500),
                ]);

                // Try multiple possible output formats
                if (is_array($output)) {
                    // Check common URL keys
                    $urlKeys = ['video_url', 'videoUrl', 'url', 'video', 'result'];
                    foreach ($urlKeys as $key) {
                        if (isset($output[$key]) && is_string($output[$key]) && filter_var($output[$key], FILTER_VALIDATE_URL)) {
                            $result['videoUrl'] = $output[$key];
                            break;
                        }
                    }

                    // Check for error in output
                    if (!isset($result['videoUrl']) && isset($output['error'])) {
                        $result['error'] = is_string($output['error']) ? $output['error'] : json_encode($output['error']);
                        $result['status'] = 'failed';
                        $result['success'] = false;
                    }

                    // Check for message that might indicate failure
                    if (!isset($result['videoUrl']) && isset($output['message'])) {
                        $result['error'] = $output['message'];
                    }
                } elseif (is_string($output) && filter_var($output, FILTER_VALIDATE_URL)) {
                    $result['videoUrl'] = $output;
                }
            }

            // If completed but no video URL found, log warning
            if ($normalizedStatus === 'completed' && !isset($result['videoUrl'])) {
                \Log::warning('🎬 Multitalk completed but no video URL found', [
                    'taskId' => $taskId,
                    'hadCachedUrl' => !empty($cachedVideoUrl),
                    'fullOutput' => $status['output'] ?? null,
                ]);
                $result['error'] = 'Video generation completed but video file not found. Upload may have failed.';
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
            if ($key === 'seedance') {
                $waveSpeedService = new WaveSpeedService();
                $available = $waveSpeedService->isConfigured();
            } elseif ($key === 'infinitetalk') {
                $endpointId = (string) get_option('runpod_infinitetalk_endpoint', '');
                $available = !empty($endpointId) && $this->runPodService->isConfigured();
            } elseif ($config['provider'] === 'runpod') {
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
        // If shot has dialogue/audio, recommend lip-sync models
        if (!empty($shot['audioUrl']) || !empty($shot['dialogueAudio']) || !empty($shot['voiceoverUrl'])) {
            // Prefer InfiniteTalk for longer audio, Multitalk for shorter
            $infinitetalkAvailable = !empty(get_option('runpod_infinitetalk_endpoint', ''));
            $multitalkAvailable = !empty(get_option('runpod_multitalk_endpoint', ''));

            if ($infinitetalkAvailable) {
                return 'infinitetalk';
            }
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
     * permanent storage (local public disk by default).
     *
     * NOTE: Videos are stored on local server (public disk) by default.
     * Firebase Storage (GCS) is NOT used for wizard videos because it
     * requires special security rules for public access.
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
            // Store directly in public folder (not storage) for cPanel/nginx compatibility
            $userId = $project->user_id ?? 0;
            $projectId = $project->id;
            $timestamp = time();
            $filename = "scene_{$sceneIndex}_shot_{$shotIndex}_{$timestamp}.mp4";
            $relativePath = "wizard-videos/{$userId}/{$projectId}/{$filename}";
            $publicPath = public_path($relativePath);

            // Ensure directory exists
            $directory = dirname($publicPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Store the video file directly in public folder
            file_put_contents($publicPath, $videoContent);

            // Generate the public URL using video.php for cPanel compatibility
            // video.php serves files from public/wizard-videos/ on cPanel where document root differs
            $videoPath = "{$userId}/{$projectId}/{$filename}";
            $permanentUrl = rtrim(config('app.url'), '/') . '/video.php?path=' . urlencode($videoPath);

            Log::info('AnimationService: Video stored permanently in public folder', [
                'path' => $relativePath,
                'fullPath' => $publicPath,
                'size' => $fileSize,
                'permanentUrl' => $permanentUrl,
            ]);

            // Create WizardAsset record to track the video
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
            'wavespeed',         // WaveSpeed AI signed URLs
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
