<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class WaveSpeedService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) get_option('ai_wavespeed_api_key', '');
        $this->client = new Client([
            'base_uri' => 'https://api.wavespeed.ai/api/v3/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Check if WaveSpeed API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && strlen($this->apiKey) > 10;
    }

    /**
     * Generate video from image + text prompt using Seedance v1.5 Pro.
     *
     * @param string $imageUrl Public URL of the source image
     * @param string $prompt 4-layer video prompt (subject, dialogue, audio cues, visual style)
     * @param array $options Additional options (aspect_ratio, duration, resolution, etc.)
     * @return array Result with taskId or error
     */
    public function generateVideo(string $imageUrl, string $prompt, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WaveSpeed API key not configured. Add it in Admin Panel > AI Configuration.'];
        }

        // Anti-speech suffix: prevents Seedance from generating unwanted spoken dialogue.
        // Disabled when the shot has dialogue/lip-sync so characters can speak naturally.
        $antiSpeech = $options['anti_speech'] ?? true;
        if ($antiSpeech) {
            $antiSpeechSuffix = ' No speech, no dialogue, no voiceover, no dubbing, no singing, no spoken words. Sound effects and ambient audio only.';
            if (!str_contains($prompt, 'No speech, no dialogue')) {
                $prompt = rtrim($prompt) . $antiSpeechSuffix;
            }
        }

        $payload = [
            'image' => $imageUrl,
            'prompt' => $prompt,
            'aspect_ratio' => $options['aspect_ratio'] ?? '9:16',
            'duration' => $options['duration'] ?? 8,
            'resolution' => $options['resolution'] ?? '1080p',
            'generate_audio' => $options['generate_audio'] ?? true,
            'camera_fixed' => $options['camera_fixed'] ?? false,
            'seed' => $options['seed'] ?? -1,
        ];

        // Bookend face identity: end on the same image to constrain face drift
        if (!empty($options['end_image_url'])) {
            $payload['end_image_url'] = $options['end_image_url'];
        }

        // Determine endpoint variant: 'pro' (quality) or 'fast' (speed/cost)
        $variant = $options['variant'] ?? 'pro';
        $endpoint = $variant === 'fast'
            ? 'bytedance/seedance-v1.5-pro/image-to-video-fast'
            : 'bytedance/seedance-v1.5-pro/image-to-video';

        Log::info('WaveSpeedService: Submitting Seedance video generation', [
            'image_url' => substr($imageUrl, 0, 80) . '...',
            'prompt_length' => strlen($prompt),
            'duration' => $payload['duration'],
            'aspect_ratio' => $payload['aspect_ratio'],
            'variant' => $variant,
            'endpoint' => $endpoint,
        ]);

        try {
            $response = $this->client->request('POST', $endpoint, [
                'json' => $payload,
                'timeout' => 60,
            ]);

            $body = json_decode($response->getBody(), true);

            Log::info('WaveSpeedService: Submission response (full)', [
                'body_keys' => array_keys($body ?? []),
                'data_keys' => isset($body['data']) ? array_keys($body['data']) : 'no data key',
                'full_body' => json_encode($body),
            ]);

            // Extract task ID from response — check multiple possible locations
            $taskId = $body['data']['id'] ?? ($body['id'] ?? ($body['data']['request_id'] ?? ($body['request_id'] ?? null)));
            if (empty($taskId)) {
                return ['success' => false, 'error' => 'No task ID returned from WaveSpeed API'];
            }

            return [
                'success' => true,
                'taskId' => $taskId,
                'provider' => 'wavespeed',
                'status' => 'processing',
            ];

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? (string) $response->getBody() : null;
            $errorData = $errorBody ? json_decode($errorBody, true) : null;
            $errorMsg = $errorData['message'] ?? $errorData['error'] ?? $e->getMessage();

            Log::error('WaveSpeedService: API error', [
                'status_code' => $response ? $response->getStatusCode() : null,
                'error' => $errorMsg,
            ]);

            return ['success' => false, 'error' => 'WaveSpeed API error: ' . $errorMsg];
        } catch (\Throwable $e) {
            Log::error('WaveSpeedService: Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'WaveSpeed error: ' . $e->getMessage()];
        }
    }

    /**
     * Check the status of a video generation task.
     *
     * @param string $taskId The prediction/task ID
     * @return array Status information with videoUrl on completion
     */
    public function getTaskStatus(string $taskId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'status' => 'error', 'error' => 'WaveSpeed API key not configured'];
        }

        try {
            $response = $this->client->request('GET', "predictions/{$taskId}/result", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);
            $data = $body['data'] ?? $body;
            $rawStatus = $data['status'] ?? 'unknown';

            // Map WaveSpeed statuses to canonical
            $statusMap = [
                'created' => 'queued',
                'processing' => 'processing',
                'completed' => 'completed',
                'failed' => 'failed',
                'canceled' => 'cancelled',
            ];

            $normalizedStatus = $statusMap[$rawStatus] ?? strtolower($rawStatus);

            $result = [
                'success' => true,
                'status' => $normalizedStatus,
                'provider' => 'wavespeed',
            ];

            if ($normalizedStatus === 'completed') {
                // Extract video URL from outputs array
                $outputs = $data['outputs'] ?? [];
                $videoUrl = is_array($outputs) && !empty($outputs) ? $outputs[0] : null;

                if (empty($videoUrl) && !empty($data['output'])) {
                    // Fallback: check 'output' key
                    $videoUrl = is_array($data['output']) ? ($data['output'][0] ?? null) : $data['output'];
                }

                if ($videoUrl) {
                    $result['videoUrl'] = $videoUrl;
                    Log::info('WaveSpeedService: Video completed', [
                        'taskId' => $taskId,
                        'videoUrl' => substr($videoUrl, 0, 100),
                    ]);
                } else {
                    Log::warning('WaveSpeedService: Completed but no video URL found', [
                        'taskId' => $taskId,
                        'outputs' => $outputs,
                    ]);
                    $result['error'] = 'Video generation completed but no output URL found';
                }
            }

            if ($normalizedStatus === 'failed') {
                $result['error'] = $data['error'] ?? $data['message'] ?? 'Video generation failed';
                Log::error('WaveSpeedService: Task failed', [
                    'taskId' => $taskId,
                    'error' => $result['error'],
                ]);
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('WaveSpeedService: Status check failed', [
                'taskId' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'status' => 'error',
                'error' => 'Failed to check task status: ' . $e->getMessage(),
            ];
        }
    }
}
