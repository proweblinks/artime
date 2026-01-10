<?php

namespace Starter\AppVideoWizard\Services;

use App\Services\MiniMaxService;
use App\Services\RunPodService;
use Illuminate\Support\Facades\Log;
use Starter\AppVideoWizard\Models\WizardProject;

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
            'description' => 'High quality I2V, recommended for most scenes',
            'durations' => [5, 6],
            'defaultDuration' => 6,
            'supportsLipSync' => false,
            'provider' => 'minimax',
        ],
        'minimax-10s' => [
            'name' => 'MiniMax (10s)',
            'description' => 'Extended duration for longer scenes',
            'durations' => [10],
            'defaultDuration' => 10,
            'supportsLipSync' => false,
            'provider' => 'minimax',
        ],
        'multitalk' => [
            'name' => 'Multitalk',
            'description' => 'Lip-sync animation for dialogue scenes',
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
        Log::info("AnimationService: Generating with MiniMax", [
            'project_id' => $project->id,
            'duration' => $duration,
        ]);

        $result = $this->miniMaxService->generateVideo($prompt, [
            'first_frame_image' => $imageUrl,
            'model' => 'video-01',
        ]);

        if (!empty($result['error'])) {
            return $this->errorResponse($result['error']);
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
        $status = $this->miniMaxService->getVideoTaskStatus($taskId);

        $statusMap = [
            'Queueing' => 'queued',
            'Processing' => 'processing',
            'Success' => 'completed',
            'Fail' => 'failed',
        ];

        $normalizedStatus = $statusMap[$status['status']] ?? $status['status'];

        $result = [
            'success' => true,
            'status' => $normalizedStatus,
            'provider' => 'minimax',
        ];

        if ($normalizedStatus === 'completed' && !empty($status['file_id'])) {
            $downloadUrl = $this->miniMaxService->getVideoDownloadUrl($status['file_id']);
            if ($downloadUrl) {
                $result['videoUrl'] = $downloadUrl;
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
}
