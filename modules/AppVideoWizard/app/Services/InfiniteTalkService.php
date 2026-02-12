<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\RunPodService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppVideoWizard\Models\WizardProject;

class InfiniteTalkService
{
    protected RunPodService $runPodService;
    protected ?string $endpointId = null;

    public function __construct()
    {
        $this->runPodService = app(RunPodService::class);
        $this->loadEndpoint();
    }

    /**
     * Load InfiniteTalk endpoint from admin settings.
     */
    protected function loadEndpoint(): void
    {
        $endpointUrl = get_option('runpod_infinitetalk_endpoint', '');

        if (!empty($endpointUrl)) {
            if (preg_match('/\/v2\/([a-z0-9]+)/i', $endpointUrl, $matches)) {
                $this->endpointId = $matches[1];
            } else {
                $this->endpointId = trim($endpointUrl);
            }
        }
    }

    /**
     * Check if InfiniteTalk is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->endpointId) && $this->runPodService->isConfigured();
    }

    /**
     * Get the endpoint ID.
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * Map aspect ratio string to pixel resolution.
     */
    public static function getResolutionForAspectRatio(string $aspectRatio): array
    {
        return match ($aspectRatio) {
            '16:9'  => ['width' => 1280, 'height' => 720],
            '9:16'  => ['width' => 720, 'height' => 1280],
            '1:1'   => ['width' => 1024, 'height' => 1024],
            '4:5'   => ['width' => 720, 'height' => 900],
            default => ['width' => 1280, 'height' => 720],
        };
    }

    /**
     * Generate a talking video using InfiniteTalk.
     *
     * @param WizardProject $project The wizard project
     * @param string $imageUrl URL to the source portrait image
     * @param string $audioUrl URL to the WAV audio file
     * @param array $options Additional options (prompt, width, height, max_frame, person_count, input_type, aspect_ratio)
     * @return array Result with taskId, provider, status, endpointId
     */
    public function generate(
        WizardProject $project,
        string $imageUrl,
        string $audioUrl,
        array $options = []
    ): array {
        if (!$this->isConfigured()) {
            return $this->errorResponse('InfiniteTalk endpoint not configured. Please configure it in Admin Panel → AI Configuration.');
        }

        // Check credits
        $teamId = $project->team_id ?? session('current_team_id', 0);
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            return $this->errorResponse($quota['message']);
        }

        $inputType = $options['input_type'] ?? 'image';
        $personCount = $options['person_count'] ?? 'single';
        $prompt = $options['prompt'] ?? 'A person is talking in a natural way with smooth lip movements.';
        $aspectRatio = $options['aspect_ratio'] ?? '16:9';
        $resolution = self::getResolutionForAspectRatio($aspectRatio);
        $width = $options['width'] ?? $resolution['width'];
        $height = $options['height'] ?? $resolution['height'];

        // Multi-mode processes 2 faces = ~2x VRAM; RunPod docs default to 512x512
        if ($personCount === 'multi') {
            $width = min((int) $width, 512);
            $height = min((int) $height, 512);
        }

        // Build InfiniteTalk input payload
        $input = [
            'input_type' => $inputType,
            'person_count' => $personCount,
            'prompt' => $prompt,
            'width' => (int) $width,
            'height' => (int) $height,
            'force_offload' => $options['force_offload'] ?? true,
        ];

        // Set source media based on input type
        if ($inputType === 'image') {
            $input['image_url'] = $imageUrl;
        } else {
            $input['video_url'] = $imageUrl; // For V2V mode, imageUrl carries the video URL
        }

        // Audio (primary person)
        $input['wav_url'] = $audioUrl;

        // Second audio for multi-person mode (URL or inline base64)
        if ($personCount === 'multi') {
            if (!empty($options['audio_url_2'])) {
                $input['wav_url_2'] = $options['audio_url_2'];
            } elseif (!empty($options['wav_base64_2'])) {
                $input['wav_base64_2'] = $options['wav_base64_2'];
            }
        }

        // Optional max_frame to cap video length
        if (!empty($options['max_frame'])) {
            $input['max_frame'] = (int) $options['max_frame'];
        }

        Log::info('InfiniteTalkService: Submitting job', [
            'project_id' => $project->id,
            'endpoint' => $this->endpointId,
            'input_type' => $inputType,
            'person_count' => $personCount,
            'has_wav_url_2' => isset($input['wav_url_2']),
            'has_wav_base64_2' => isset($input['wav_base64_2']),
            'width' => $width,
            'height' => $height,
        ]);

        $result = $this->runPodService->runAsync($this->endpointId, $input);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Failed to submit InfiniteTalk job');
        }

        $taskId = $result['id'];

        // Cache project info for later video storage
        Cache::put("infinitetalk_project:{$taskId}", [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'team_id' => $teamId,
        ], now()->addHours(3));

        return [
            'success' => true,
            'taskId' => $taskId,
            'provider' => 'infinitetalk',
            'status' => 'processing',
            'endpointId' => $this->endpointId,
        ];
    }

    /**
     * Check job status and process result if completed.
     *
     * @param string $taskId RunPod job ID
     * @param string|null $endpointId Override endpoint ID
     * @return array Status with videoUrl if completed
     */
    public function getJobStatus(string $taskId, ?string $endpointId = null): array
    {
        $endpointId = $endpointId ?: $this->endpointId;

        if (empty($endpointId)) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => 'InfiniteTalk endpoint not configured',
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
            'provider' => 'infinitetalk',
            'executionTime' => $status['executionTime'] ?? null,
            'delayTime' => $status['delayTime'] ?? null,
        ];

        if ($normalizedStatus === 'completed' && isset($status['output'])) {
            $videoUrl = $this->processCompletedJob($taskId, $status['output']);
            if ($videoUrl) {
                $result['videoUrl'] = $videoUrl;
            } else {
                $result['status'] = 'failed';
                $result['error'] = 'Failed to process video output from InfiniteTalk';
            }
        }

        if ($normalizedStatus === 'failed') {
            $result['error'] = $status['error'] ?? 'InfiniteTalk job failed';
        }

        return $result;
    }

    /**
     * Process completed job output - decode base64 video and store to server.
     *
     * @param string $taskId RunPod job ID
     * @param mixed $output RunPod output data
     * @return string|null Public URL to stored video, or null on failure
     */
    protected function processCompletedJob(string $taskId, $output): ?string
    {
        try {
            // Get project info from cache
            $projectInfo = Cache::get("infinitetalk_project:{$taskId}");
            $projectId = $projectInfo['project_id'] ?? 0;

            if (!$projectId) {
                Log::warning('InfiniteTalkService: No project info cached for task', ['taskId' => $taskId]);
                $projectId = 0; // Fallback - store in a generic location
            }

            // Extract video data from output
            $videoData = null;

            if (is_array($output)) {
                // Standard output: {"video": "data:video/mp4;base64,..."}
                $videoData = $output['video'] ?? $output['video_base64'] ?? null;
            } elseif (is_string($output)) {
                $videoData = $output;
            }

            if (empty($videoData)) {
                Log::error('InfiniteTalkService: No video data in output', [
                    'taskId' => $taskId,
                    'output_keys' => is_array($output) ? array_keys($output) : 'string',
                ]);
                return null;
            }

            // Strip data URL prefix if present
            if (str_starts_with($videoData, 'data:video/')) {
                $videoData = preg_replace('/^data:video\/[^;]+;base64,/', '', $videoData);
            }

            // Decode base64
            $videoBytes = base64_decode($videoData, true);
            if ($videoBytes === false) {
                Log::error('InfiniteTalkService: Failed to decode base64 video', ['taskId' => $taskId]);
                return null;
            }

            // Store to server
            $filename = 'infinitetalk_' . time() . '_' . uniqid() . '.mp4';
            $storagePath = "wizard-videos/{$projectId}/{$filename}";

            Storage::disk('public')->put($storagePath, $videoBytes);

            // Build public URL (cPanel compatible)
            $publicUrl = url('/files/' . $storagePath);

            Log::info('InfiniteTalkService: Video stored successfully', [
                'taskId' => $taskId,
                'projectId' => $projectId,
                'path' => $storagePath,
                'size' => strlen($videoBytes),
                'url' => $publicUrl,
            ]);

            // Clean up cache
            Cache::forget("infinitetalk_project:{$taskId}");

            return $publicUrl;

        } catch (\Throwable $e) {
            Log::error('InfiniteTalkService: Error processing completed job', [
                'taskId' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Submit job and wait for completion (blocking).
     * Use for shorter clips. For longer videos, use generate() + poll with getJobStatus().
     *
     * @param WizardProject $project
     * @param string $imageUrl
     * @param string $audioUrl
     * @param array $options
     * @param int $maxWaitSeconds Maximum wait time (default 600s = 10 min)
     * @return array Result with videoUrl on success
     */
    public function generateAndWait(
        WizardProject $project,
        string $imageUrl,
        string $audioUrl,
        array $options = [],
        int $maxWaitSeconds = 600
    ): array {
        $submitResult = $this->generate($project, $imageUrl, $audioUrl, $options);

        if (!$submitResult['success']) {
            return $submitResult;
        }

        $taskId = $submitResult['taskId'];
        $endpointId = $submitResult['endpointId'];

        Log::info('InfiniteTalkService: Waiting for completion', [
            'taskId' => $taskId,
            'maxWait' => $maxWaitSeconds,
        ]);

        $status = $this->runPodService->waitForCompletion(
            $endpointId,
            $taskId,
            $maxWaitSeconds,
            5 // Poll every 5 seconds (video gen is slow)
        );

        if (!$status['success']) {
            return $this->errorResponse($status['error'] ?? 'Job did not complete');
        }

        if ($status['status'] === 'COMPLETED' && isset($status['output'])) {
            $videoUrl = $this->processCompletedJob($taskId, $status['output']);
            if ($videoUrl) {
                return [
                    'success' => true,
                    'videoUrl' => $videoUrl,
                    'provider' => 'infinitetalk',
                    'executionTime' => $status['executionTime'] ?? null,
                ];
            }
        }

        return $this->errorResponse('Failed to process InfiniteTalk video output');
    }

    /**
     * Generate a minimal silent WAV file as base64.
     * Used to mute the second face in multi mode when only one character speaks.
     */
    public static function generateSilentWavBase64(float $durationSeconds = 0.1): string
    {
        return base64_encode(self::generateSilentWavBytes($durationSeconds));
    }

    /**
     * Generate raw bytes for a truly silent WAV file.
     * All samples are zero-amplitude (digital silence) to prevent any lip movement
     * on the non-speaking face in multi-person InfiniteTalk mode.
     */
    public static function generateSilentWavBytes(float $durationSeconds = 0.1): string
    {
        $sampleRate = 44100;
        $bitsPerSample = 16;
        $channels = 1;
        $numSamples = max(1, (int)($sampleRate * $durationSeconds));
        $bytesPerSample = $bitsPerSample / 8;
        $dataSize = $numSamples * $bytesPerSample * $channels;
        $byteRate = $sampleRate * $channels * $bytesPerSample;
        $blockAlign = $channels * $bytesPerSample;

        // RIFF header + fmt chunk
        $header = pack('A4VA4', 'RIFF', 36 + $dataSize, 'WAVE');
        $fmt = pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample);

        // True silence: all zero samples (no lip movement trigger)
        $samples = str_repeat("\x00\x00", $numSamples);

        $data = pack('A4V', 'data', $dataSize) . $samples;

        return $header . $fmt . $data;
    }

    /**
     * Generate a silent WAV file, save to public storage, and return its URL.
     * More reliable than base64 inline for multi-person mode.
     */
    public static function generateSilentWavUrl(int $projectId, float $durationSeconds = 0.1): string
    {
        $wavBytes = self::generateSilentWavBytes($durationSeconds);
        $filename = 'silent_' . md5($durationSeconds . '_' . $projectId) . '.wav';
        $storagePath = "wizard-audio/{$projectId}/{$filename}";

        Storage::disk('public')->put($storagePath, $wavBytes);

        return url('/files/' . $storagePath);
    }

    /**
     * Build timeline-synchronized audio tracks for multi-person dialogue.
     *
     * Instead of playing both speakers simultaneously, this creates tracks where:
     * - Speaker 1's track: [speech][silence during speaker 2's turn]
     * - Speaker 2's track: [silence during speaker 1's turn][speech]
     *
     * This ensures proper turn-taking in the generated video.
     *
     * @param int $projectId Project ID for file storage
     * @param string $audioUrl1 URL to speaker 1's audio
     * @param float $duration1 Duration of speaker 1's audio in seconds
     * @param string $audioUrl2 URL to speaker 2's audio
     * @param float $duration2 Duration of speaker 2's audio in seconds
     * @param float $pauseBetween Pause between turns in seconds
     * @return array ['success' => bool, 'audioUrl1' => string, 'audioUrl2' => string, 'totalDuration' => float]
     */
    public static function buildTimelineSyncedAudio(
        int $projectId,
        string $audioUrl1,
        float $duration1,
        string $audioUrl2,
        float $duration2,
        float $pauseBetween = 0.3
    ): array {
        $totalDuration = $duration1 + $pauseBetween + $duration2;
        $fallback = [
            'success' => false,
            'audioUrl1' => $audioUrl1,
            'audioUrl2' => $audioUrl2,
            'totalDuration' => max($duration1, $duration2),
        ];

        try {
            $tempDir = storage_path('app/temp/timeline-sync');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $uid = uniqid('tl_', true);

            // Resolve audio file paths (convert URLs to local paths)
            $localPath1 = self::resolveAudioPath($audioUrl1);
            $localPath2 = self::resolveAudioPath($audioUrl2);

            if (!$localPath1 || !$localPath2) {
                Log::warning('InfiniteTalk timeline sync: could not resolve audio paths', [
                    'url1' => substr($audioUrl1, 0, 80),
                    'url2' => substr($audioUrl2, 0, 80),
                ]);
                return $fallback;
            }

            $outputPath1 = $tempDir . "/synced1_{$uid}.wav";
            $outputPath2 = $tempDir . "/synced2_{$uid}.wav";

            // Speaker 1: [speech] + [silence for pause + duration2]
            $silenceDur1 = number_format($pauseBetween + $duration2, 3, '.', '');
            $cmd1 = sprintf(
                'ffmpeg -y -i %s -f lavfi -t %s -i anullsrc=r=44100:cl=mono '
                . '-filter_complex "[0:a]aresample=44100,aformat=sample_fmts=s16:channel_layouts=mono[a];'
                . '[1:a]aformat=sample_fmts=s16:channel_layouts=mono[s];'
                . '[a][s]concat=n=2:v=0:a=1[out]" '
                . '-map "[out]" %s 2>&1',
                escapeshellarg($localPath1),
                $silenceDur1,
                escapeshellarg($outputPath1)
            );

            // Speaker 2: [silence for duration1 + pause] + [speech]
            $silenceDur2 = number_format($duration1 + $pauseBetween, 3, '.', '');
            $cmd2 = sprintf(
                'ffmpeg -y -f lavfi -t %s -i anullsrc=r=44100:cl=mono -i %s '
                . '-filter_complex "[0:a]aformat=sample_fmts=s16:channel_layouts=mono[s];'
                . '[1:a]aresample=44100,aformat=sample_fmts=s16:channel_layouts=mono[a];'
                . '[s][a]concat=n=2:v=0:a=1[out]" '
                . '-map "[out]" %s 2>&1',
                $silenceDur2,
                escapeshellarg($localPath2),
                escapeshellarg($outputPath2)
            );

            Log::info('InfiniteTalk timeline sync: building tracks', [
                'duration1' => $duration1,
                'duration2' => $duration2,
                'pauseBetween' => $pauseBetween,
                'totalDuration' => $totalDuration,
            ]);

            exec($cmd1, $output1, $ret1);
            exec($cmd2, $output2, $ret2);

            if ($ret1 !== 0 || $ret2 !== 0 || !file_exists($outputPath1) || !file_exists($outputPath2)) {
                Log::error('InfiniteTalk timeline sync: FFmpeg failed', [
                    'ret1' => $ret1,
                    'ret2' => $ret2,
                    'output1' => implode("\n", array_slice($output1 ?? [], -5)),
                    'output2' => implode("\n", array_slice($output2 ?? [], -5)),
                ]);
                return $fallback;
            }

            // Store synced audio to public storage
            $storagePath1 = "wizard-audio/{$projectId}/timeline_speaker1_{$uid}.wav";
            $storagePath2 = "wizard-audio/{$projectId}/timeline_speaker2_{$uid}.wav";

            Storage::disk('public')->put($storagePath1, file_get_contents($outputPath1));
            Storage::disk('public')->put($storagePath2, file_get_contents($outputPath2));

            $syncedUrl1 = url('/files/' . $storagePath1);
            $syncedUrl2 = url('/files/' . $storagePath2);

            // Cleanup temp files
            @unlink($outputPath1);
            @unlink($outputPath2);

            Log::info('InfiniteTalk timeline sync: tracks built successfully', [
                'totalDuration' => $totalDuration,
                'syncedUrl1' => substr($syncedUrl1, 0, 80),
                'syncedUrl2' => substr($syncedUrl2, 0, 80),
            ]);

            return [
                'success' => true,
                'audioUrl1' => $syncedUrl1,
                'audioUrl2' => $syncedUrl2,
                'totalDuration' => $totalDuration,
            ];

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk timeline sync: exception', ['error' => $e->getMessage()]);
            return $fallback;
        }
    }

    /**
     * Resolve an audio URL to a local file path.
     * Converts same-site public URLs to local storage paths, or downloads external URLs.
     */
    protected static function resolveAudioPath(string $audioUrl): ?string
    {
        // Check if it's a local URL (same site)
        $baseUrl = rtrim(url('/files'), '/') . '/';
        if (str_starts_with($audioUrl, $baseUrl)) {
            $relativePath = ltrim(str_replace($baseUrl, '', $audioUrl), '/');
            $localPath = Storage::disk('public')->path($relativePath);

            if (file_exists($localPath)) {
                return $localPath;
            }
        }

        // Try downloading external URL to temp file
        try {
            $tempDir = storage_path('app/temp/timeline-sync');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $ext = pathinfo(parse_url($audioUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'wav';
            $tempPath = $tempDir . '/download_' . md5($audioUrl) . '.' . $ext;

            $contents = file_get_contents($audioUrl);
            if ($contents !== false && strlen($contents) > 0) {
                file_put_contents($tempPath, $contents);
                return $tempPath;
            }
        } catch (\Throwable $e) {
            Log::warning('InfiniteTalk: failed to download audio for timeline sync', [
                'url' => substr($audioUrl, 0, 80),
                'error' => $e->getMessage(),
            ]);
        }

        return null;
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
