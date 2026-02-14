<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\AIModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class FalService
{
    protected Client $client;
    protected string $apiKey;
    protected array $cachedModels = [];

    protected array $fallbacks = [
        'image' => 'fal-ai/flux-pro/v1.1',
        'video' => 'fal-ai/kling-video/v1/standard/text-to-video',
        'speech' => 'fal-ai/qwen-3-tts/text-to-speech/1.7b',
    ];

    public function __construct()
    {
        $this->apiKey = (string) get_option("ai_fal_api_key", "");
        $this->client = new Client([
            'base_uri' => 'https://fal.run/',
            'headers' => [
                'Authorization' => 'Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Gets the configured or default model key for a specific category.
     */
    protected function getModel(string $category, ?string $default = null): string
    {
        $default ??= $this->fallbacks[$category] ?? 'fal-ai/flux-pro/v1.1';

        if (empty($this->cachedModels)) {
            $this->cachedModels = array_keys($this->getModels());
        }

        $optionKey = "ai_fal_model_{$category}";
        $model = get_option($optionKey, $default);

        if (!empty($this->cachedModels) && !in_array($model, $this->cachedModels, true)) {
            $model = $default;
            try {
                DB::table('options')->updateOrInsert(
                    ['name' => $optionKey],
                    ['value' => $default, 'updated_at' => now()]
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to update default FAL model for {$category}: " . $e->getMessage());
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
                ->where('provider', 'fal')
                ->where('is_active', 1)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['model_key', 'name']);

            return $models->pluck('name', 'model_key')->toArray();
        } catch (\Throwable $e) {
            Log::error("Error fetching FAL models from DB: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Make an API call to FAL.
     */
    protected function makeAPICall(string $model, array $payload): array
    {
        try {
            $response = $this->client->request('POST', $model, [
                'json' => $payload,
                'timeout' => 120,
            ]);

            $body = json_decode($response->getBody(), true);
            return $body;

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? (string)$response->getBody() : null;
            $message = $e->getMessage();

            if ($errorBody) {
                $decoded = json_decode($errorBody, true);
                if (isset($decoded['detail'])) {
                    $message = is_string($decoded['detail']) ? $decoded['detail'] : json_encode($decoded['detail']);
                }
            }

            Log::error("FAL API Client Error", [
                'model' => $model,
                'status' => $response?->getStatusCode(),
                'body' => $errorBody,
            ]);

            throw new \Exception($message, $e->getCode(), $e);

        } catch (\Throwable $e) {
            Log::error("FAL API Fatal Error", [
                'model' => $model,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit an async job to FAL and get the request ID.
     */
    protected function submitAsyncJob(string $model, array $payload): string
    {
        try {
            $response = $this->client->request('POST', $model, [
                'json' => $payload,
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['request_id'] ?? '';

        } catch (\Throwable $e) {
            Log::error("FAL async job submission failed", [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check the status of an async job.
     */
    public function getJobStatus(string $model, string $requestId): array
    {
        try {
            $response = $this->client->request('GET', "{$model}/requests/{$requestId}/status", [
                'timeout' => 30,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the result of a completed async job.
     */
    public function getJobResult(string $model, string $requestId): array
    {
        try {
            $response = $this->client->request('GET', "{$model}/requests/{$requestId}", [
                'timeout' => 30,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, 'async');
        }
    }

    // --- Image Generation ---

    /**
     * Generates images using FAL's Flux models.
     */
    public function generateImage(string $prompt, array $options = [], string $category = 'image'): array
    {
        $model = $options['model'] ?? $this->getModel($category);

        $payload = [
            'prompt' => $prompt,
            'image_size' => $options['size'] ?? 'landscape_16_9',
            'num_images' => $options['count'] ?? 1,
            'enable_safety_checker' => $options['safety'] ?? true,
        ];

        // Add optional parameters based on model
        if (!empty($options['num_inference_steps'])) {
            $payload['num_inference_steps'] = $options['num_inference_steps'];
        }
        if (!empty($options['seed'])) {
            $payload['seed'] = $options['seed'];
        }
        if (!empty($options['guidance_scale'])) {
            $payload['guidance_scale'] = $options['guidance_scale'];
        }

        try {
            $body = $this->makeAPICall($model, $payload);

            $images = [];
            foreach ($body['images'] ?? [] as $img) {
                $images[] = [
                    'url' => $img['url'] ?? '',
                    'width' => $img['width'] ?? null,
                    'height' => $img['height'] ?? null,
                    'content_type' => $img['content_type'] ?? 'image/jpeg',
                ];
            }

            return $this->successResponse($model, $images);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Video Generation ---

    /**
     * Generates video using FAL's video models (Kling, etc.).
     */
    public function generateVideo(string $prompt, array $options = [], string $category = 'video'): array
    {
        $model = $options['model'] ?? $this->getModel($category);

        $payload = [
            'prompt' => $prompt,
        ];

        // Image-to-video options
        if (!empty($options['image_url'])) {
            $payload['image_url'] = $options['image_url'];
        }

        // Video parameters
        if (!empty($options['duration'])) {
            $payload['duration'] = $options['duration']; // e.g., "5" for 5 seconds
        }
        if (!empty($options['aspect_ratio'])) {
            $payload['aspect_ratio'] = $options['aspect_ratio']; // e.g., "16:9"
        }

        try {
            // Video generation is usually async
            $body = $this->makeAPICall($model, $payload);

            // Check if we got a request_id (async) or direct result
            if (isset($body['request_id'])) {
                return [
                    'data' => [
                        'request_id' => $body['request_id'],
                        'status' => 'processing',
                    ],
                    'model' => $model,
                    'error' => null,
                    'totalTokens' => 0,
                ];
            }

            // Direct result
            $videos = [];
            if (!empty($body['video'])) {
                $videos[] = [
                    'url' => $body['video']['url'] ?? '',
                    'content_type' => $body['video']['content_type'] ?? 'video/mp4',
                ];
            }

            return $this->successResponse($model, $videos);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Text Generation (not supported by FAL) ---

    public function generateText(string|array $content, int $maxLength, ?int $maxResult = null, string $category = 'text', array $options = []): array
    {
        return $this->errorResponse('fal', new \Exception("FAL does not support text generation"), $category);
    }

    // --- Vision (not supported by FAL) ---

    public function generateVision(string|array $prompt, array $options = [], string $category = 'vision'): array
    {
        return $this->errorResponse('fal', new \Exception("FAL does not support vision"), $category);
    }

    // --- Speech/Audio (Qwen 3 TTS via FAL) ---

    /**
     * Generate speech using Qwen 3 TTS.
     *
     * @param string $text Text to speak
     * @param array $options {
     *     model: string,         // FAL model path (default: qwen-3-tts/text-to-speech/1.7b)
     *     prompt: string,        // Natural language style guide (KEY differentiator)
     *     voice: string,         // Built-in voice: Vivian, Serena, Uncle_Fu, Dylan, Eric, Ryan, Aiden, Ono_Anna, Sohee
     *     speaker_voice_embedding_file_url: string,  // Cloned voice embedding URL
     *     reference_text: string, // Reference text for cloned voice
     *     language: string,      // Auto, English, Chinese, etc.
     *     temperature: float,    // 0-1 (default 0.9)
     *     max_new_tokens: int,   // 1-8192 (default 2048)
     * }
     * @param string $category
     * @return array
     */
    public function textToSpeech(string $text, array $options = [], string $category = 'speech'): array
    {
        $model = $options['model'] ?? $this->getModel($category);

        $payload = [
            'text' => $text,
            'language' => $options['language'] ?? 'Auto',
            'max_new_tokens' => $options['max_new_tokens'] ?? 2048,
        ];

        // Style prompt — the key differentiator for expressive speech
        if (!empty($options['prompt'])) {
            $payload['prompt'] = $options['prompt'];
        }

        // Voice selection: cloned embedding OR built-in voice
        if (!empty($options['speaker_voice_embedding_file_url'])) {
            $payload['speaker_voice_embedding_file_url'] = $options['speaker_voice_embedding_file_url'];
            if (!empty($options['reference_text'])) {
                $payload['reference_text'] = $options['reference_text'];
            }
        } elseif (!empty($options['voice'])) {
            $payload['voice'] = $options['voice'];
        }

        // Sampling parameters
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        try {
            $body = $this->makeAPICall($model, $payload);

            $audio = $body['audio'] ?? [];
            return $this->successResponse($model, [$audio], [], 0);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /**
     * Clone a voice by extracting speaker embedding from audio.
     *
     * @param string $audioUrl URL of the audio sample to clone
     * @param string|null $referenceText Transcript of the audio sample
     * @return array Speaker embedding data with url, file_size, content_type
     */
    public function cloneVoice(string $audioUrl, ?string $referenceText = null): array
    {
        $payload = ['audio_url' => $audioUrl];
        if ($referenceText) {
            $payload['reference_text'] = $referenceText;
        }

        $body = $this->makeAPICall('fal-ai/qwen-3-tts/clone-voice/1.7b', $payload);
        return $body; // Returns speaker_embedding with url, file_size, content_type
    }

    public function speechToText(string $filePath, array $options = [], string $category = 'speech_to_text'): array
    {
        return $this->errorResponse('fal', new \Exception("FAL does not support speech-to-text"), $category);
    }

    public function generateAudio(string $filePath, array $options = [], string $category = 'audio'): array
    {
        return $this->errorResponse('fal', new \Exception("FAL does not support audio generation"), $category);
    }

    public function generateEmbedding(string $text, array $options = [], string $category = 'embedding'): array
    {
        return $this->errorResponse('fal', new \Exception("FAL does not support embeddings"), $category);
    }

    // --- Response Helpers ---

    protected function errorResponse(string $model, \Throwable $e, string $category = ''): array
    {
        Log::error("FAL {$category} error with model {$model}: " . $e->getMessage());

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
