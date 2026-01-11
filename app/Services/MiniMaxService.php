<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\AIModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class MiniMaxService
{
    protected Client $client;
    protected string $apiKey;
    protected array $cachedModels = [];

    protected array $fallbacks = [
        'text' => 'abab6.5s-chat',
        'video' => 'video-01',
        'speech' => 'speech-01-turbo',
    ];

    public function __construct()
    {
        $this->apiKey = (string) get_option("ai_minimax_api_key", "");

        // MiniMax API base URL - configurable for regional variants
        // Primary: api.minimax.io, Regional: api.minimaxi.com
        $baseUrl = (string) get_option("ai_minimax_api_url", "https://api.minimax.io/");

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Gets the configured or default model key for a specific category.
     */
    protected function getModel(string $category, ?string $default = null): string
    {
        $default ??= $this->fallbacks[$category] ?? 'abab6.5s-chat';

        if (empty($this->cachedModels)) {
            $this->cachedModels = array_keys($this->getModels());
        }

        $optionKey = "ai_minimax_model_{$category}";
        $model = get_option($optionKey, $default);

        if (!empty($this->cachedModels) && !in_array($model, $this->cachedModels, true)) {
            $model = $default;
            try {
                DB::table('options')->updateOrInsert(
                    ['name' => $optionKey],
                    ['value' => $default, 'updated_at' => now()]
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to update default MiniMax model for {$category}: " . $e->getMessage());
            }
        }

        return $model;
    }

    /**
     * Retrieves a list of active models from the database.
     */
    public function getModels(): array
    {
        try {
            $models = AIModel::query()
                ->where('provider', 'minimax')
                ->where('is_active', 1)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['model_key', 'name']);

            return $models->pluck('name', 'model_key')->toArray();
        } catch (\Throwable $e) {
            Log::error("Error fetching MiniMax models from DB: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Make an API call to MiniMax.
     */
    protected function makeAPICall(string $endpoint, array $payload, string $method = 'POST'): array
    {
        try {
            $options = [
                'json' => $payload,
                'timeout' => 120,
            ];

            $response = $this->client->request($method, $endpoint, $options);
            $body = json_decode($response->getBody(), true);

            // Check for API-level errors
            if (isset($body['base_resp']['status_code']) && $body['base_resp']['status_code'] !== 0) {
                throw new \Exception($body['base_resp']['status_msg'] ?? 'Unknown MiniMax API error');
            }

            return $body;

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? (string)$response->getBody() : null;
            $message = $e->getMessage();

            if ($errorBody) {
                $decoded = json_decode($errorBody, true);
                if (isset($decoded['base_resp']['status_msg'])) {
                    $message = $decoded['base_resp']['status_msg'];
                }
            }

            Log::error("MiniMax API Client Error", [
                'endpoint' => $endpoint,
                'status' => $response?->getStatusCode(),
                'body' => $errorBody,
            ]);

            throw new \Exception($message, $e->getCode(), $e);

        } catch (\Throwable $e) {
            Log::error("MiniMax API Fatal Error", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // --- Text Generation ---

    /**
     * Generates text using MiniMax chat models.
     */
    public function generateText(
        string|array $content,
        int $maxLength,
        ?int $maxResult = null,
        string $category = 'text',
        array $options = []
    ): array {
        $model = $this->getModel($category);

        $messages = is_array($content)
            ? $content
            : [['role' => 'user', 'content' => $content]];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxLength,
            'temperature' => $options['temperature'] ?? 0.7,
            'top_p' => $options['top_p'] ?? 0.95,
        ];

        try {
            $body = $this->makeAPICall('v1/text/chatcompletion_v2', $payload);

            $result = [];
            foreach ($body['choices'] ?? [] as $choice) {
                $result[] = $choice['message']['content'] ?? '';
            }

            $usage = $body['usage'] ?? [];
            return $this->successResponse(
                $model,
                $result,
                [
                    'promptTokens' => $usage['prompt_tokens'] ?? 0,
                    'completionTokens' => $usage['completion_tokens'] ?? 0,
                    'totalTokens' => $usage['total_tokens'] ?? 0,
                ]
            );

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Video Generation ---

    /**
     * Generates video using MiniMax video models.
     *
     * Supported models and durations:
     * - video-01, I2V-01: 6s only (720P)
     * - MiniMax-Hailuo-02: 6s or 10s (all resolutions)
     * - MiniMax-Hailuo-2.3: 6s or 10s (768P), 6s only (1080P)
     */
    public function generateVideo(string $prompt, array $options = [], string $category = 'video'): array
    {
        $duration = $options['duration'] ?? 6;
        $model = $options['model'] ?? $this->getModel($category);

        // For 10s videos, use Hailuo-02 model which supports all durations
        if ($duration == 10 && !str_contains($model, 'Hailuo')) {
            $model = 'MiniMax-Hailuo-02';
        }

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
        ];

        // Add duration parameter (supported by Hailuo models)
        if ($duration && in_array($duration, [6, 10])) {
            $payload['duration'] = $duration;
        }

        // Optional: Image-to-video
        if (!empty($options['first_frame_image'])) {
            $payload['first_frame_image'] = $options['first_frame_image'];
        }

        Log::info('MiniMax video generation request', [
            'model' => $model,
            'duration' => $duration,
            'hasImage' => !empty($options['first_frame_image']),
        ]);

        try {
            // Submit video generation task
            $body = $this->makeAPICall('v1/video_generation', $payload);

            $taskId = $body['task_id'] ?? null;

            if ($taskId) {
                return [
                    'data' => [
                        'task_id' => $taskId,
                        'status' => 'processing',
                    ],
                    'model' => $model,
                    'error' => null,
                    'totalTokens' => 0,
                ];
            }

            // If direct result (unlikely for video)
            return $this->successResponse($model, $body);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /**
     * Check video generation task status.
     */
    public function getVideoTaskStatus(string $taskId): array
    {
        try {
            $response = $this->client->request('GET', 'v1/query/video_generation', [
                'query' => ['task_id' => $taskId],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            // Log full response for debugging
            \Log::info("MiniMaxService: Video task status raw response", [
                'taskId' => $taskId,
                'body_keys' => is_array($body) ? array_keys($body) : 'not_array',
                'status' => $body['status'] ?? 'not_found',
                'file_id' => $body['file_id'] ?? 'not_found',
                'base_resp' => $body['base_resp'] ?? 'not_found',
            ]);

            // Check for API error first
            if (isset($body['base_resp']['status_code']) && $body['base_resp']['status_code'] !== 0) {
                \Log::warning("MiniMaxService: API returned error", [
                    'status_code' => $body['base_resp']['status_code'],
                    'status_msg' => $body['base_resp']['status_msg'] ?? 'unknown',
                ]);
                return [
                    'status' => 'error',
                    'file_id' => null,
                    'error' => $body['base_resp']['status_msg'] ?? 'Unknown API error',
                ];
            }

            return [
                'status' => $body['status'] ?? 'unknown',
                'file_id' => $body['file_id'] ?? null,
                'error' => $body['base_resp']['status_msg'] ?? null,
            ];

        } catch (\Throwable $e) {
            \Log::error("MiniMaxService: getVideoTaskStatus exception", [
                'taskId' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get video download URL from file_id.
     */
    public function getVideoDownloadUrl(string $fileId): ?string
    {
        try {
            $response = $this->client->request('GET', 'v1/files/retrieve', [
                'query' => ['file_id' => $fileId],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['file']['download_url'] ?? null;

        } catch (\Throwable $e) {
            Log::error("Failed to get MiniMax video download URL: " . $e->getMessage());
            return null;
        }
    }

    // --- Text-to-Speech ---

    /**
     * Generates speech from text using MiniMax TTS.
     */
    public function textToSpeech(string $text, array $options = [], string $category = 'speech'): array
    {
        $model = $options['model'] ?? $this->getModel($category);

        $payload = [
            'model' => $model,
            'text' => $text,
            'voice_setting' => [
                'voice_id' => $options['voice_id'] ?? 'male-qn-qingse',
                'speed' => $options['speed'] ?? 1.0,
                'vol' => $options['volume'] ?? 1.0,
                'pitch' => $options['pitch'] ?? 0,
            ],
            'audio_setting' => [
                'sample_rate' => $options['sample_rate'] ?? 32000,
                'bitrate' => $options['bitrate'] ?? 128000,
                'format' => $options['format'] ?? 'mp3',
            ],
        ];

        try {
            $body = $this->makeAPICall('v1/t2a_v2', $payload);

            $audioData = $body['data']['audio'] ?? null;

            if ($audioData) {
                return $this->successResponse($model, [
                    'audio_base64' => $audioData,
                    'format' => $options['format'] ?? 'mp3',
                ]);
            }

            throw new \Exception("No audio data returned from MiniMax TTS");

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Image Generation (not the primary use case for MiniMax) ---

    public function generateImage(string $prompt, array $options = [], string $category = 'image'): array
    {
        return $this->errorResponse('minimax', new \Exception("MiniMax does not support image generation directly"), $category);
    }

    // --- Vision (not supported) ---

    public function generateVision(string|array $prompt, array $options = [], string $category = 'vision'): array
    {
        return $this->errorResponse('minimax', new \Exception("MiniMax does not support vision"), $category);
    }

    // --- Speech-to-Text (not supported) ---

    public function speechToText(string $filePath, array $options = [], string $category = 'speech_to_text'): array
    {
        return $this->errorResponse('minimax', new \Exception("MiniMax does not support speech-to-text"), $category);
    }

    public function generateAudio(string $filePath, array $options = [], string $category = 'audio'): array
    {
        return $this->errorResponse('minimax', new \Exception("Use textToSpeech for audio generation"), $category);
    }

    public function generateEmbedding(string $text, array $options = [], string $category = 'embedding'): array
    {
        return $this->errorResponse('minimax', new \Exception("MiniMax does not support embeddings"), $category);
    }

    // --- Response Helpers ---

    protected function errorResponse(string $model, \Throwable $e, string $category = ''): array
    {
        Log::error("MiniMax {$category} error with model {$model}: " . $e->getMessage());

        return [
            'data' => [],
            'promptTokens' => 0,
            'completionTokens' => 0,
            'totalTokens' => 0,
            'minutesUsed' => 0,
            'model' => $model,
            'error' => $e->getMessage(),
        ];
    }

    protected function successResponse(string $model, array $data, array $usage = [], float $minutesUsed = 0): array
    {
        return [
            'data' => $data,
            'promptTokens' => $usage['promptTokens'] ?? 0,
            'completionTokens' => $usage['completionTokens'] ?? 0,
            'totalTokens' => $usage['totalTokens'] ?? 0,
            'minutesUsed' => $minutesUsed,
            'model' => $model,
            'error' => null,
        ];
    }
}
