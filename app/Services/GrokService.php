<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\AIModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * xAI Grok Service
 *
 * API Documentation: https://docs.x.ai/docs/models
 *
 * Pricing (Jan 2026):
 * - Grok 4.1 Fast: $0.20/1M input, $0.50/1M output (2M context)
 * - Grok 4: $3.00/1M input, $15.00/1M output (256K context)
 * - Grok 3 Mini: $0.30/1M input, $0.50/1M output
 *
 * The API is OpenAI-compatible.
 */
class GrokService
{
    protected Client $client;
    protected string $apiKey;
    protected array $cachedModels = [];

    protected array $fallbacks = [
        'text'   => 'grok-4-fast',  // Latest & best value: $0.20/$0.50 per 1M tokens
        'vision' => 'grok-2-vision-1212',
    ];

    public function __construct()
    {
        $this->apiKey = (string) get_option("ai_grok_api_key", "");
        $this->client = new Client([
            'base_uri' => 'https://api.x.ai/v1/',
        ]);
    }

    protected function getModel(string $category, ?string $default = null): string
    {
        $default ??= $this->fallbacks[$category] ?? 'grok-3-fast';

        if (empty($this->cachedModels)) {
            $this->cachedModels = array_keys($this->getModels());
        }

        $optionKey = "ai_grok_model_{$category}";
        $model     = get_option($optionKey, $default);

        if (!in_array($model, $this->cachedModels, true)) {
            $model = $default;
            try {
                DB::table('options')->updateOrInsert(
                    ['key' => $optionKey],
                    ['value' => $default, 'updated_at' => now()]
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to update default Grok model for {$category}: " . $e->getMessage());
            }
        }

        return $model;
    }

    public function getModels(): array
    {
        try {
            $models = AIModel::query()
                ->where('provider', 'grok')
                ->where('is_active', 1)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['model_key', 'name']);

            return $models->pluck('name', 'model_key')->toArray();
        } catch (\Throwable $e) {
            Log::error("Error fetching Grok models from DB: " . $e->getMessage());
            return [];
        }
    }

    /** ---------------- Text ---------------- */
    public function generateText(
        string|array $content,
        int $maxLength,
        ?int $maxResult = null,
        string $category = 'text',
        ?string $modelOverride = null,
        array $options = []
    ): array {
        // Use override model if provided, otherwise check options, then fall back to default
        $model = $modelOverride ?? $options['model'] ?? $this->getModel($category);

        $messages = is_array($content)
            ? $content
            : [[
                "role"    => "user",
                "content" => $content,
            ]];

        try {
            $response = $this->client->request('POST', 'chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => (int) $maxLength,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'top_p'       => $options['top_p'] ?? 0.95,
                ],
                'timeout' => 120, // Grok supports long contexts, may need more time
            ]);

            $body = json_decode($response->getBody(), true);

            $result = [];
            if (!empty($body['choices'])) {
                foreach ($body['choices'] as $choice) {
                    $result[] = $choice['message']['content'] ?? '';
                }
            }

            return $this->successResponse($model, $result, [
                'promptTokens'     => $body['usage']['prompt_tokens'] ?? 0,
                'completionTokens' => $body['usage']['completion_tokens'] ?? 0,
                'totalTokens'      => $body['usage']['total_tokens'] ?? 0,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /** ---------------- Vision (Image Understanding) ---------------- */
    public function generateVision(string|array $prompt, array $options = [], string $category = 'vision'): array
    {
        $model = $options['model'] ?? $this->getModel('vision');

        try {
            // Build messages with image content
            $messages = [];

            if (is_array($prompt) && isset($prompt[0]['role'])) {
                // Already formatted messages
                $messages = $prompt;
            } else {
                // Simple prompt with image
                $imageUrl = $options['image_url'] ?? null;
                $content = [];

                if ($imageUrl) {
                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => ['url' => $imageUrl],
                    ];
                }

                $content[] = [
                    'type' => 'text',
                    'text' => is_string($prompt) ? $prompt : ($prompt['text'] ?? 'Describe this image'),
                ];

                $messages[] = [
                    'role' => 'user',
                    'content' => $content,
                ];
            }

            $response = $this->client->request('POST', 'chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => $options['max_tokens'] ?? 1024,
                    'temperature' => $options['temperature'] ?? 0.7,
                ],
                'timeout' => 60,
            ]);

            $body = json_decode($response->getBody(), true);

            $result = [];
            if (!empty($body['choices'])) {
                foreach ($body['choices'] as $choice) {
                    $result[] = $choice['message']['content'] ?? '';
                }
            }

            return $this->successResponse($model, $result, [
                'promptTokens'     => $body['usage']['prompt_tokens'] ?? 0,
                'completionTokens' => $body['usage']['completion_tokens'] ?? 0,
                'totalTokens'      => $body['usage']['total_tokens'] ?? 0,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /** ---------------- Image Generation ---------------- */
    public function generateImage(string $prompt, array $options = [], string $category = 'image'): array
    {
        // Grok currently focuses on text/vision, not image generation
        return $this->errorResponse('grok-image', new \Exception("Grok image generation not yet available"), $category);
    }

    /** ---------------- Video ---------------- */
    public function generateVideo(string $prompt, array $options = [], string $category = 'video'): array
    {
        return $this->errorResponse('grok-video', new \Exception("Grok video generation not supported"), $category);
    }

    /** ---------------- Embedding ---------------- */
    public function generateEmbedding(string $prompt, array $options = [], string $category = 'embedding'): array
    {
        $model = $options['model'] ?? 'grok-2-1212';

        try {
            $response = $this->client->request('POST', 'embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'input' => $prompt,
                ],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            $embeddings = [];
            if (!empty($body['data'])) {
                foreach ($body['data'] as $item) {
                    $embeddings[] = $item['embedding'] ?? [];
                }
            }

            return $this->successResponse($model, $embeddings, [
                'promptTokens' => $body['usage']['prompt_tokens'] ?? 0,
                'totalTokens'  => $body['usage']['total_tokens'] ?? 0,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /** ---------------- Speech ---------------- */
    public function textToSpeech(string $text, array $options = [], string $category = 'speech'): array
    {
        return $this->errorResponse('grok-tts', new \Exception("Grok TTS not supported"), $category);
    }

    public function speechToText(string $filePath, array $options = [], string $category = 'speech_to_text'): array
    {
        return $this->errorResponse('grok-stt', new \Exception("Grok STT not supported"), $category);
    }

    public function generateAudio(string $filePath, array $options = [], string $category = 'audio'): array
    {
        return $this->speechToText($filePath, $options, $category);
    }

    /** ---------------- Helpers ---------------- */
    protected function successResponse(string $model, array $data, array $usage = [], float $minutesUsed = 0): array
    {
        return [
            'data'             => $data,
            'promptTokens'     => $usage['promptTokens'] ?? 0,
            'completionTokens' => $usage['completionTokens'] ?? 0,
            'totalTokens'      => $usage['totalTokens'] ?? 0,
            'minutesUsed'      => $minutesUsed,
            'model'            => $model,
            'error'            => null,
        ];
    }

    protected function errorResponse(string $model, \Throwable $e, string $category = ''): array
    {
        Log::error("Grok {$category} error with model {$model}: " . $e->getMessage());

        return [
            'data'             => [],
            'promptTokens'     => 0,
            'completionTokens' => 0,
            'totalTokens'      => 0,
            'minutesUsed'      => 0,
            'model'            => $model,
            'error'            => $e->getMessage(),
        ];
    }
}
