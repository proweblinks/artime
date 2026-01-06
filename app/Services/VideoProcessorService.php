<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class VideoProcessorService
{
    protected ?Client $client = null;
    protected string $baseUrl;
    protected bool $isConfigured = false;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) get_option("video_processor_url", ""), '/');

        if (!empty($this->baseUrl)) {
            $this->client = new Client([
                'base_uri' => $this->baseUrl . '/',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
            $this->isConfigured = true;
        }
    }

    /**
     * Check if the video processor is configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured && $this->client !== null;
    }

    /**
     * Concatenate multiple videos.
     */
    public function concatenate(array $videoUrls, array $options = []): array
    {
        return $this->submitJob('concatenate', [
            'videos' => $videoUrls,
            'output_format' => $options['output_format'] ?? 'mp4',
            'resolution' => $options['resolution'] ?? null,
            'fps' => $options['fps'] ?? null,
        ]);
    }

    /**
     * Add audio to a video.
     */
    public function addAudio(string $videoUrl, string $audioUrl, array $options = []): array
    {
        return $this->submitJob('add_audio', [
            'video_url' => $videoUrl,
            'audio_url' => $audioUrl,
            'audio_volume' => $options['audio_volume'] ?? 1.0,
            'video_volume' => $options['video_volume'] ?? 1.0,
            'mix_mode' => $options['mix_mode'] ?? 'replace', // replace, mix, ducking
            'fade_in' => $options['fade_in'] ?? 0,
            'fade_out' => $options['fade_out'] ?? 0,
        ]);
    }

    /**
     * Add background music to a video.
     */
    public function addBackgroundMusic(string $videoUrl, string $musicUrl, array $options = []): array
    {
        return $this->submitJob('add_background_music', [
            'video_url' => $videoUrl,
            'music_url' => $musicUrl,
            'music_volume' => $options['music_volume'] ?? 0.3,
            'loop_music' => $options['loop_music'] ?? true,
            'fade_in' => $options['fade_in'] ?? 2,
            'fade_out' => $options['fade_out'] ?? 2,
        ]);
    }

    /**
     * Add subtitles/captions to a video.
     */
    public function addSubtitles(string $videoUrl, array $subtitles, array $options = []): array
    {
        return $this->submitJob('add_subtitles', [
            'video_url' => $videoUrl,
            'subtitles' => $subtitles, // [{start: 0, end: 5, text: "Hello"}, ...]
            'style' => [
                'font' => $options['font'] ?? 'Arial',
                'font_size' => $options['font_size'] ?? 24,
                'font_color' => $options['font_color'] ?? '#FFFFFF',
                'background_color' => $options['background_color'] ?? '#000000',
                'background_opacity' => $options['background_opacity'] ?? 0.7,
                'position' => $options['position'] ?? 'bottom', // top, center, bottom
            ],
        ]);
    }

    /**
     * Trim a video.
     */
    public function trim(string $videoUrl, float $startTime, float $endTime): array
    {
        return $this->submitJob('trim', [
            'video_url' => $videoUrl,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Resize/scale a video.
     */
    public function resize(string $videoUrl, int $width, int $height, array $options = []): array
    {
        return $this->submitJob('resize', [
            'video_url' => $videoUrl,
            'width' => $width,
            'height' => $height,
            'maintain_aspect' => $options['maintain_aspect'] ?? true,
            'background_color' => $options['background_color'] ?? '#000000',
        ]);
    }

    /**
     * Add watermark to a video.
     */
    public function addWatermark(string $videoUrl, string $watermarkUrl, array $options = []): array
    {
        return $this->submitJob('add_watermark', [
            'video_url' => $videoUrl,
            'watermark_url' => $watermarkUrl,
            'position' => $options['position'] ?? 'bottom-right', // top-left, top-right, bottom-left, bottom-right, center
            'opacity' => $options['opacity'] ?? 0.7,
            'scale' => $options['scale'] ?? 0.15,
            'margin' => $options['margin'] ?? 20,
        ]);
    }

    /**
     * Add transitions between video clips.
     */
    public function addTransitions(array $videoUrls, array $options = []): array
    {
        return $this->submitJob('add_transitions', [
            'videos' => $videoUrls,
            'transition_type' => $options['transition_type'] ?? 'fade', // fade, dissolve, wipe, slide
            'transition_duration' => $options['transition_duration'] ?? 1.0,
        ]);
    }

    /**
     * Generate thumbnail from video.
     */
    public function generateThumbnail(string $videoUrl, float $timestamp = 0, array $options = []): array
    {
        return $this->submitJob('generate_thumbnail', [
            'video_url' => $videoUrl,
            'timestamp' => $timestamp,
            'width' => $options['width'] ?? 1280,
            'height' => $options['height'] ?? 720,
            'format' => $options['format'] ?? 'jpg',
        ]);
    }

    /**
     * Extract audio from video.
     */
    public function extractAudio(string $videoUrl, array $options = []): array
    {
        return $this->submitJob('extract_audio', [
            'video_url' => $videoUrl,
            'format' => $options['format'] ?? 'mp3',
            'bitrate' => $options['bitrate'] ?? '192k',
        ]);
    }

    /**
     * Apply video filters/effects.
     */
    public function applyFilters(string $videoUrl, array $filters): array
    {
        return $this->submitJob('apply_filters', [
            'video_url' => $videoUrl,
            'filters' => $filters, // ['brightness' => 1.2, 'contrast' => 1.1, 'saturation' => 1.3, etc.]
        ]);
    }

    /**
     * Convert video format.
     */
    public function convert(string $videoUrl, string $outputFormat, array $options = []): array
    {
        return $this->submitJob('convert', [
            'video_url' => $videoUrl,
            'output_format' => $outputFormat, // mp4, webm, mov, avi
            'codec' => $options['codec'] ?? null,
            'quality' => $options['quality'] ?? 'high', // low, medium, high
            'fps' => $options['fps'] ?? null,
        ]);
    }

    /**
     * Submit a job to the video processor.
     */
    protected function submitJob(string $operation, array $params): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Video Processor not configured');
        }

        try {
            $response = $this->client->request('POST', 'process', [
                'json' => [
                    'operation' => $operation,
                    'params' => $params,
                ],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['error'])) {
                return $this->errorResponse($body['error']);
            }

            return [
                'success' => true,
                'job_id' => $body['job_id'] ?? null,
                'status' => $body['status'] ?? 'queued',
                'output_url' => $body['output_url'] ?? null,
            ];

        } catch (ClientException $e) {
            Log::error("Video Processor job submission failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("Video Processor error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get job status.
     */
    public function getJobStatus(string $jobId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Video Processor not configured');
        }

        try {
            $response = $this->client->request('GET', "status/{$jobId}", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'job_id' => $body['job_id'] ?? $jobId,
                'status' => $body['status'] ?? 'unknown',
                'progress' => $body['progress'] ?? 0,
                'output_url' => $body['output_url'] ?? null,
                'error' => $body['error'] ?? null,
            ];

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Wait for job completion.
     */
    public function waitForCompletion(string $jobId, int $maxWaitSeconds = 600, int $pollInterval = 5): array
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWaitSeconds) {
            $status = $this->getJobStatus($jobId);

            if (!$status['success']) {
                return $status;
            }

            $currentStatus = $status['status'];

            if ($currentStatus === 'completed') {
                return $status;
            }

            if (in_array($currentStatus, ['failed', 'error', 'cancelled'])) {
                return [
                    'success' => false,
                    'status' => $currentStatus,
                    'error' => $status['error'] ?? "Job {$currentStatus}",
                ];
            }

            sleep($pollInterval);
        }

        return [
            'success' => false,
            'status' => 'timeout',
            'error' => "Job did not complete within {$maxWaitSeconds} seconds",
        ];
    }

    /**
     * Health check.
     */
    public function healthCheck(): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Video Processor not configured');
        }

        try {
            $response = $this->client->request('GET', 'health', [
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'status' => $body['status'] ?? 'unknown',
                'version' => $body['version'] ?? null,
            ];

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Parse error from ClientException.
     */
    protected function parseError(ClientException $e): string
    {
        $response = $e->getResponse();
        if ($response) {
            $body = json_decode($response->getBody(), true);
            if (isset($body['error'])) {
                return is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            }
        }
        return $e->getMessage();
    }

    /**
     * Error response format.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}
