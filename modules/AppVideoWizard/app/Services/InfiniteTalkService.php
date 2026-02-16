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

        // Multi-mode processes 2 faces = ~2x VRAM; scale proportionally to fit within max dimension
        if ($personCount === 'multi') {
            $maxDim = 768;
            $w = (int) $width;
            $h = (int) $height;
            if ($w > $maxDim || $h > $maxDim) {
                $scale = min($maxDim / $w, $maxDim / $h);
                $width = (int) round($w * $scale);
                $height = (int) round($h * $scale);
                // Ensure dimensions are divisible by 8 (required by video models)
                $width = (int) (floor($width / 8) * 8);
                $height = (int) (floor($height / 8) * 8);
            }
        }

        // Build InfiniteTalk input payload
        // Quality parameters — InfiniteTalk official defaults with step-distillation LoRA
        $steps = (int) ($options['steps'] ?? 40);
        $audioCfgScale = (float) ($options['audio_cfg_scale'] ?? 2.0);

        $input = [
            'input_type' => $inputType,
            'person_count' => $personCount,
            'prompt' => $prompt,
            'width' => (int) $width,
            'height' => (int) $height,
            'force_offload' => $options['force_offload'] ?? true,
            'steps' => $steps,
            'audio_cfg_scale' => $audioCfgScale,
            'seed' => -1,
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
            'steps' => $steps,
            'audio_cfg_scale' => $audioCfgScale,
        ]);

        $result = $this->runPodService->runAsync($this->endpointId, $input);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Failed to submit InfiniteTalk job');
        }

        $taskId = $result['id'];

        // Cache project info for later video storage
        $cacheData = [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'team_id' => $teamId,
        ];

        // OTS shots: cache the speaker's audio URL for post-processing overlay
        if (!empty($options['ots_overlay_audio'])) {
            $cacheData['ots_overlay_audio'] = $options['ots_overlay_audio'];
        }

        Cache::put("infinitetalk_project:{$taskId}", $cacheData, now()->addHours(3));

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

            // OTS post-processing: overlay speaker audio onto the silent video
            $otsOverlayAudio = $projectInfo['ots_overlay_audio'] ?? null;
            if (!empty($otsOverlayAudio)) {
                Log::info('InfiniteTalkService: OTS post-processing - overlaying speaker audio', [
                    'taskId' => $taskId,
                    'videoPath' => $storagePath,
                    'audioUrl' => substr($otsOverlayAudio, 0, 80),
                ]);

                $mergedUrl = $this->overlayAudioOnVideo($storagePath, $otsOverlayAudio, $projectId);
                if ($mergedUrl) {
                    $publicUrl = $mergedUrl;
                    Log::info('InfiniteTalkService: OTS audio overlay successful', [
                        'taskId' => $taskId,
                        'mergedUrl' => $mergedUrl,
                    ]);
                } else {
                    Log::warning('InfiniteTalkService: OTS audio overlay failed, returning silent video', [
                        'taskId' => $taskId,
                    ]);
                }
            }

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
     * Generate a WAV file with very subtle pink noise instead of pure silence.
     * Used in Dual Take mode to keep InfiniteTalk's animation engine active
     * during speech gaps — prevents character freezing when the speaking face
     * has a natural pause and the listening face would otherwise be pure silence.
     */
    public static function generateAmbientWavUrl(int $projectId, float $durationSeconds = 5.0): string
    {
        $ffmpeg = null;
        foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
            if (file_exists($path) && is_executable($path)) {
                $ffmpeg = $path;
                break;
            }
        }

        // Fallback: if no ffmpeg, return silent WAV (better than nothing)
        if (!$ffmpeg) {
            Log::warning('InfiniteTalk: ffmpeg not found for ambient noise, falling back to silent WAV');
            return self::generateSilentWavUrl($projectId, $durationSeconds);
        }

        $filename = 'ambient_' . md5($durationSeconds . '_' . $projectId . '_' . time()) . '.wav';
        $storagePath = "wizard-audio/{$projectId}/{$filename}";
        $diskPath = Storage::disk('public')->path($storagePath);

        // Ensure directory exists
        $dir = dirname($diskPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Generate pink noise at very low amplitude (0.008 ≈ -42dB, inaudible but keeps animation active)
        $cmd = sprintf(
            '%s -f lavfi -i anoisesrc=c=pink:r=44100:a=0.008:d=%s -c:a pcm_s16le %s -y 2>&1',
            escapeshellarg($ffmpeg),
            $durationSeconds,
            escapeshellarg($diskPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($diskPath)) {
            Log::warning('InfiniteTalk: ambient noise generation failed, falling back to silent WAV', [
                'returnCode' => $returnCode,
            ]);
            return self::generateSilentWavUrl($projectId, $durationSeconds);
        }

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
        float $pauseBetween = 0.5
    ): array {
        $endPaddingSec = 2.0; // Extra silence after last speaker to prevent cutoff
        $totalDuration = $duration1 + $pauseBetween + $duration2 + $endPaddingSec;
        $fallback = [
            'success' => false,
            'audioUrl1' => $audioUrl1,
            'audioUrl2' => $audioUrl2,
            'totalDuration' => $totalDuration,
        ];

        try {
            // Read both WAV files
            $wavData1 = self::readWavFile($audioUrl1);
            $wavData2 = self::readWavFile($audioUrl2);

            if (!$wavData1 || !$wavData2) {
                Log::warning('InfiniteTalk timeline sync: could not read WAV files', [
                    'url1' => substr($audioUrl1, 0, 80),
                    'url2' => substr($audioUrl2, 0, 80),
                ]);
                return $fallback;
            }

            // Calculate ACTUAL duration from PCM data to prevent overlap
            // TTS providers return estimated durations that may not match actual audio length
            $bytesPerSample1 = max(1, $wavData1['bitsPerSample'] / 8);
            $bytesPerSample2 = max(1, $wavData2['bitsPerSample'] / 8);
            $actualDuration1 = strlen($wavData1['pcmData']) / ($wavData1['sampleRate'] * $wavData1['channels'] * $bytesPerSample1);
            $actualDuration2 = strlen($wavData2['pcmData']) / ($wavData2['sampleRate'] * $wavData2['channels'] * $bytesPerSample2);

            // Use MAX of reported and actual duration to ensure no overlap
            $duration1 = max($duration1, $actualDuration1);
            $duration2 = max($duration2, $actualDuration2);
            $totalDuration = $duration1 + $pauseBetween + $duration2 + $endPaddingSec;

            Log::info('InfiniteTalk timeline sync: building tracks (pure PHP)', [
                'reportedDuration1' => round($duration1, 2),
                'reportedDuration2' => round($duration2, 2),
                'actualDuration1' => round($actualDuration1, 2),
                'actualDuration2' => round($actualDuration2, 2),
                'pauseBetween' => $pauseBetween,
                'totalDuration' => round($totalDuration, 2),
                'wav1_sampleRate' => $wavData1['sampleRate'],
                'wav2_sampleRate' => $wavData2['sampleRate'],
            ]);

            // Generate silence PCM bytes matching each file's format
            // End padding prevents InfiniteTalk from clipping the last speech segment
            $endPadding = 2.0;
            $silence1Dur = $pauseBetween + $duration2 + $endPadding;
            $silence1Pcm = self::generateSilencePcm($wavData1, $silence1Dur);

            $silence2Dur = $duration1 + $pauseBetween;
            $silence2Pcm = self::generateSilencePcm($wavData2, $silence2Dur);
            $endSilence2 = self::generateSilencePcm($wavData2, $endPadding);

            // Speaker 1: [speech PCM] + [silence for pause+speaker2+end]
            $synced1Pcm = $wavData1['pcmData'] . $silence1Pcm;
            $synced1Wav = self::buildWavFromPcm($wavData1, $synced1Pcm);

            // Speaker 2: [silence for speaker1+pause] + [speech PCM] + [end padding]
            $synced2Pcm = $silence2Pcm . $wavData2['pcmData'] . $endSilence2;
            $synced2Wav = self::buildWavFromPcm($wavData2, $synced2Pcm);

            // Store to public storage
            $uid = uniqid('tl_', true);
            $storagePath1 = "wizard-audio/{$projectId}/timeline_speaker1_{$uid}.wav";
            $storagePath2 = "wizard-audio/{$projectId}/timeline_speaker2_{$uid}.wav";

            Storage::disk('public')->put($storagePath1, $synced1Wav);
            Storage::disk('public')->put($storagePath2, $synced2Wav);

            $syncedUrl1 = url('/files/' . $storagePath1);
            $syncedUrl2 = url('/files/' . $storagePath2);

            Log::info('InfiniteTalk timeline sync: tracks built successfully', [
                'totalDuration' => $totalDuration,
                'synced1Size' => strlen($synced1Wav),
                'synced2Size' => strlen($synced2Wav),
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
     * Read a WAV file from URL or local path and parse its header + PCM data.
     * Automatically converts non-WAV formats (FLAC, MP3, OGG) to WAV using ffmpeg.
     *
     * @return array|null Parsed WAV info: sampleRate, bitsPerSample, channels, pcmData
     */
    protected static function readWavFile(string $audioUrl): ?array
    {
        // Resolve to local path first
        $localPath = self::resolveAudioPath($audioUrl);
        if (!$localPath) {
            return null;
        }

        $raw = file_get_contents($localPath);
        if ($raw === false || strlen($raw) < 44) {
            return null;
        }

        // If not WAV, try converting via ffmpeg (supports FLAC, MP3, OGG, etc.)
        if (substr($raw, 0, 4) !== 'RIFF' || substr($raw, 8, 4) !== 'WAVE') {
            $localPath = self::convertToWav($localPath);
            if (!$localPath) {
                Log::warning('InfiniteTalk: not a WAV file and ffmpeg conversion failed', ['url' => substr($audioUrl, 0, 80)]);
                return null;
            }
            $raw = file_get_contents($localPath);
            if ($raw === false || strlen($raw) < 44 || substr($raw, 0, 4) !== 'RIFF') {
                return null;
            }
        }

        // Parse fmt chunk — find it by scanning (it's usually at offset 12)
        $pos = 12;
        $sampleRate = 44100;
        $bitsPerSample = 16;
        $channels = 1;
        $pcmData = '';

        while ($pos < strlen($raw) - 8) {
            $chunkId = substr($raw, $pos, 4);
            $chunkSize = unpack('V', substr($raw, $pos + 4, 4))[1];

            if ($chunkId === 'fmt ') {
                $fmt = unpack('vaudioFormat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', substr($raw, $pos + 8, 16));
                $sampleRate = $fmt['sampleRate'];
                $bitsPerSample = $fmt['bitsPerSample'];
                $channels = $fmt['channels'];
            } elseif ($chunkId === 'data') {
                $pcmData = substr($raw, $pos + 8, $chunkSize);
                break;
            }

            $pos += 8 + $chunkSize;
            // Chunks must be word-aligned
            if ($chunkSize % 2 !== 0) {
                $pos++;
            }
        }

        if (empty($pcmData)) {
            Log::warning('InfiniteTalk: no data chunk in WAV', ['url' => substr($audioUrl, 0, 80)]);
            return null;
        }

        return [
            'sampleRate' => $sampleRate,
            'bitsPerSample' => $bitsPerSample,
            'channels' => $channels,
            'pcmData' => $pcmData,
        ];
    }

    /**
     * Generate silent PCM bytes matching the format of a parsed WAV file.
     */
    protected static function generateSilencePcm(array $wavInfo, float $durationSeconds): string
    {
        $bytesPerSample = $wavInfo['bitsPerSample'] / 8;
        $numSamples = max(1, (int) ($wavInfo['sampleRate'] * $durationSeconds));
        $silencePerSample = str_repeat("\x00", (int) $bytesPerSample * $wavInfo['channels']);

        return str_repeat($silencePerSample, $numSamples);
    }

    /**
     * Build a complete WAV file from header info + raw PCM data.
     */
    protected static function buildWavFromPcm(array $wavInfo, string $pcmData): string
    {
        $sampleRate = $wavInfo['sampleRate'];
        $bitsPerSample = $wavInfo['bitsPerSample'];
        $channels = $wavInfo['channels'];
        $bytesPerSample = $bitsPerSample / 8;
        $byteRate = $sampleRate * $channels * $bytesPerSample;
        $blockAlign = $channels * $bytesPerSample;
        $dataSize = strlen($pcmData);

        // RIFF header
        $header = pack('A4VA4', 'RIFF', 36 + $dataSize, 'WAVE');
        // fmt chunk
        $fmt = pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample);
        // data chunk
        $data = pack('A4V', 'data', $dataSize) . $pcmData;

        return $header . $fmt . $data;
    }

    /**
     * Resolve an audio URL to a local file path.
     * Converts same-site public URLs to local storage paths, or downloads external URLs.
     */
    protected static function resolveAudioPath(string $audioUrl): ?string
    {
        // Check if it's a local URL (same site) — handle both /files/ and /public/ paths
        $siteUrl = rtrim(url('/'), '/');
        $localPrefixes = [
            $siteUrl . '/files/' => fn($rel) => Storage::disk('public')->path($rel),
            $siteUrl . '/public/' => fn($rel) => public_path($rel),
        ];

        foreach ($localPrefixes as $prefix => $resolver) {
            if (str_starts_with($audioUrl, $prefix)) {
                $relativePath = ltrim(str_replace($prefix, '', $audioUrl), '/');
                $localPath = $resolver($relativePath);

                if (file_exists($localPath)) {
                    return $localPath;
                }
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
     * Convert a non-WAV audio file to WAV using ffmpeg.
     * Returns path to the converted WAV file, or null on failure.
     */
    protected static function convertToWav(string $inputPath): ?string
    {
        // Look for ffmpeg binary in common locations
        $ffmpeg = null;
        foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
            if (file_exists($path) && is_executable($path)) {
                $ffmpeg = $path;
                break;
            }
        }

        if (!$ffmpeg) {
            Log::warning('InfiniteTalk: ffmpeg not found, cannot convert audio');
            return null;
        }

        $tempDir = storage_path('app/temp/timeline-sync');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir . '/converted_' . md5($inputPath) . '_' . time() . '.wav';

        // Convert to 16-bit PCM WAV, mono, 24kHz (standard for speech)
        $cmd = sprintf(
            '%s -i %s -f wav -acodec pcm_s16le -ac 1 -ar 24000 %s -y 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            Log::warning('InfiniteTalk: ffmpeg conversion failed', [
                'input' => basename($inputPath),
                'returnCode' => $returnCode,
                'output' => implode("\n", array_slice($output, -3)),
            ]);
            return null;
        }

        Log::info('InfiniteTalk: converted audio to WAV', [
            'input' => basename($inputPath),
            'output' => basename($outputPath),
            'size' => filesize($outputPath),
        ]);

        return $outputPath;
    }

    /**
     * Overlay audio onto a video file using ffmpeg.
     * Used for OTS shots where InfiniteTalk generates a silent video
     * and the speaker's audio needs to be merged in post-processing.
     *
     * @param string $videoStoragePath Storage path of the silent video (relative to public disk)
     * @param string $audioUrl URL of the speaker's audio to overlay
     * @param int $projectId Project ID for storage path
     * @return string|null Public URL of the merged video, or null on failure
     */
    protected function overlayAudioOnVideo(string $videoStoragePath, string $audioUrl, int $projectId): ?string
    {
        try {
            // Find ffmpeg binary
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk OTS: ffmpeg not found, cannot overlay audio');
                return null;
            }

            // Resolve video path on disk
            $videoPath = Storage::disk('public')->path($videoStoragePath);
            if (!file_exists($videoPath)) {
                Log::error('InfiniteTalk OTS: video file not found', ['path' => $videoPath]);
                return null;
            }

            // Resolve audio to local path
            $audioPath = static::resolveAudioPath($audioUrl);
            if (!$audioPath || !file_exists($audioPath)) {
                Log::error('InfiniteTalk OTS: audio file not found', ['url' => substr($audioUrl, 0, 80)]);
                return null;
            }

            // Output merged video
            $mergedFilename = 'infinitetalk_ots_' . time() . '_' . uniqid() . '.mp4';
            $mergedStoragePath = "wizard-videos/{$projectId}/{$mergedFilename}";
            $mergedDiskPath = Storage::disk('public')->path($mergedStoragePath);

            // Ensure output directory exists
            $outputDir = dirname($mergedDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // ffmpeg: copy video stream, encode audio as AAC, merge
            $cmd = sprintf(
                '%s -i %s -i %s -c:v copy -c:a aac -b:a 128k -map 0:v:0 -map 1:a:0 -shortest %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($videoPath),
                escapeshellarg($audioPath),
                escapeshellarg($mergedDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($mergedDiskPath)) {
                Log::error('InfiniteTalk OTS: ffmpeg merge failed', [
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk OTS: audio overlay complete', [
                'mergedPath' => $mergedStoragePath,
                'mergedSize' => filesize($mergedDiskPath),
            ]);

            return url('/files/' . $mergedStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk OTS: overlay error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Concatenate two video files using ffmpeg concat demuxer (no re-encoding).
     * Used by Dual Take mode to join two separate speaker renders into one video.
     *
     * @param string $videoUrl1 Public URL of first video (Take 1)
     * @param string $videoUrl2 Public URL of second video (Take 2)
     * @param int $projectId Project ID for storage path
     * @return string|null Public URL of the concatenated video, or null on failure
     */
    public static function concatenateVideos(string $videoUrl1, string $videoUrl2, int $projectId): ?string
    {
        try {
            // Find ffmpeg binary
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk DualTake: ffmpeg not found, cannot concatenate videos');
                return null;
            }

            // Resolve video URLs to local disk paths
            $resolveVideoPath = function(string $url): ?string {
                // Extract storage path from /files/ URL
                $parsed = parse_url($url);
                $path = $parsed['path'] ?? '';
                if (str_starts_with($path, '/files/')) {
                    $storagePath = substr($path, 7); // Remove '/files/'
                    $diskPath = Storage::disk('public')->path($storagePath);
                    if (file_exists($diskPath)) {
                        return $diskPath;
                    }
                }
                return null;
            };

            $videoPath1 = $resolveVideoPath($videoUrl1);
            $videoPath2 = $resolveVideoPath($videoUrl2);

            if (!$videoPath1 || !$videoPath2) {
                Log::error('InfiniteTalk DualTake: video file(s) not found', [
                    'url1' => substr($videoUrl1, 0, 80),
                    'url2' => substr($videoUrl2, 0, 80),
                    'path1Exists' => $videoPath1 ? 'yes' : 'no',
                    'path2Exists' => $videoPath2 ? 'yes' : 'no',
                ]);
                return null;
            }

            // Output concatenated video
            $outputFilename = 'dual_take_' . time() . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            // Ensure output directory exists
            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Create concat list file
            $concatListPath = $outputDiskPath . '.concat.txt';
            $concatContent = "file " . escapeshellarg($videoPath1) . "\nfile " . escapeshellarg($videoPath2);
            file_put_contents($concatListPath, $concatContent);

            // ffmpeg: concat demuxer — no re-encoding, fast lossless join
            $cmd = sprintf(
                '%s -f concat -safe 0 -i %s -c copy %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($concatListPath),
                escapeshellarg($outputDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            // Clean up concat list file
            @unlink($concatListPath);

            if ($returnCode !== 0 || !file_exists($outputDiskPath)) {
                Log::error('InfiniteTalk DualTake: ffmpeg concat failed', [
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk DualTake: concatenation complete', [
                'outputPath' => $outputStoragePath,
                'outputSize' => filesize($outputDiskPath),
            ]);

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk DualTake: concat error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract the last frame from a video file as a PNG image.
     * Used by Dual Take sequential mode: Take 1's last frame becomes Take 2's input image,
     * ensuring perfect visual continuity at the transition point.
     *
     * @param string $videoUrl Public URL of the video
     * @param int $projectId Project ID for storage path
     * @return string|null Public URL of the extracted frame image, or null on failure
     */
    public static function extractLastFrame(string $videoUrl, int $projectId): ?string
    {
        try {
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk: ffmpeg not found for last frame extraction');
                return null;
            }

            // Resolve video URL to local disk path
            $parsed = parse_url($videoUrl);
            $urlPath = $parsed['path'] ?? '';
            if (!str_starts_with($urlPath, '/files/')) {
                Log::error('InfiniteTalk: cannot resolve video URL for frame extraction', ['url' => substr($videoUrl, 0, 80)]);
                return null;
            }
            $storagePath = substr($urlPath, 7);
            $videoDiskPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($videoDiskPath)) {
                Log::error('InfiniteTalk: video file not found for frame extraction', ['path' => $videoDiskPath]);
                return null;
            }

            // Output frame image
            $frameFilename = 'lastframe_' . time() . '_' . uniqid() . '.png';
            $frameStoragePath = "wizard-videos/{$projectId}/{$frameFilename}";
            $frameDiskPath = Storage::disk('public')->path($frameStoragePath);

            $outputDir = dirname($frameDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Extract last frame: seek to 1s before end, grab 1 frame
            // Note: -sseof -0.1 is too close to end and yields 0 frames; -1 is reliable
            $cmd = sprintf(
                '%s -sseof -1 -i %s -frames:v 1 -update 1 %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($videoDiskPath),
                escapeshellarg($frameDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($frameDiskPath)) {
                Log::error('InfiniteTalk: last frame extraction failed', [
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk: last frame extracted', [
                'framePath' => $frameStoragePath,
                'frameSize' => filesize($frameDiskPath),
            ]);

            return url('/files/' . $frameStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk: last frame extraction error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract a single frame at a specific timestamp from a video.
     * Used by the Video Extend feature to capture a frame the user paused on.
     */
    public static function extractFrameAtTimestamp(string $videoUrl, float $timestamp, int $projectId): ?string
    {
        try {
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk: ffmpeg not found for frame extraction at timestamp');
                return null;
            }

            // Resolve video URL to local disk path
            $parsed = parse_url($videoUrl);
            $urlPath = $parsed['path'] ?? '';
            if (!str_starts_with($urlPath, '/files/')) {
                Log::error('InfiniteTalk: cannot resolve video URL for timestamp frame extraction', ['url' => substr($videoUrl, 0, 80)]);
                return null;
            }
            $storagePath = substr($urlPath, 7);
            $videoDiskPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($videoDiskPath)) {
                Log::error('InfiniteTalk: video file not found for timestamp frame extraction', ['path' => $videoDiskPath]);
                return null;
            }

            // Output frame image
            $frameFilename = 'extend_frame_' . str_replace('.', '_', (string) $timestamp) . '_' . uniqid() . '.png';
            $frameStoragePath = "wizard-videos/{$projectId}/{$frameFilename}";
            $frameDiskPath = Storage::disk('public')->path($frameStoragePath);

            $outputDir = dirname($frameDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Extract frame at the specified timestamp
            $cmd = sprintf(
                '%s -ss %s -i %s -frames:v 1 -update 1 %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg(number_format($timestamp, 3, '.', '')),
                escapeshellarg($videoDiskPath),
                escapeshellarg($frameDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($frameDiskPath)) {
                Log::error('InfiniteTalk: frame extraction at timestamp failed', [
                    'timestamp' => $timestamp,
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk: frame extracted at timestamp', [
                'timestamp' => $timestamp,
                'framePath' => $frameStoragePath,
                'frameSize' => filesize($frameDiskPath),
            ]);

            return url('/files/' . $frameStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk: frame extraction at timestamp error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Trim a video to end at a specific timestamp (lossless, no re-encode).
     * Used by the Video Extend feature to keep only the portion before the cut point.
     */
    public static function trimVideoToTimestamp(string $videoUrl, float $timestamp, int $projectId): ?string
    {
        try {
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk: ffmpeg not found for video trimming');
                return null;
            }

            // Resolve video URL to local disk path
            $parsed = parse_url($videoUrl);
            $urlPath = $parsed['path'] ?? '';
            if (!str_starts_with($urlPath, '/files/')) {
                Log::error('InfiniteTalk: cannot resolve video URL for trimming', ['url' => substr($videoUrl, 0, 80)]);
                return null;
            }
            $storagePath = substr($urlPath, 7);
            $videoDiskPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($videoDiskPath)) {
                Log::error('InfiniteTalk: video file not found for trimming', ['path' => $videoDiskPath]);
                return null;
            }

            // Output trimmed video
            $outputFilename = 'trimmed_' . str_replace('.', '_', (string) $timestamp) . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Trim video: keep from start to timestamp, lossless copy
            $cmd = sprintf(
                '%s -i %s -t %s -c copy %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($videoDiskPath),
                escapeshellarg(number_format($timestamp, 3, '.', '')),
                escapeshellarg($outputDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputDiskPath)) {
                Log::error('InfiniteTalk: video trimming failed', [
                    'timestamp' => $timestamp,
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk: video trimmed', [
                'timestamp' => $timestamp,
                'outputPath' => $outputStoragePath,
                'outputSize' => filesize($outputDiskPath),
            ]);

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk: video trimming error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Concatenate multiple video URLs into a single video (lossless).
     * Used by the Video Extend feature for assembling 3+ segments.
     */
    public static function concatenateMultipleVideos(array $videoUrls, int $projectId): ?string
    {
        if (count($videoUrls) < 2) {
            return $videoUrls[0] ?? null;
        }

        // For exactly 2 videos, use the existing method
        if (count($videoUrls) === 2) {
            return self::concatenateVideos($videoUrls[0], $videoUrls[1], $projectId);
        }

        try {
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk: ffmpeg not found for multi-video concatenation');
                return null;
            }

            // Resolve all video URLs to local disk paths
            $resolveVideoPath = function(string $url): ?string {
                $parsed = parse_url($url);
                $path = $parsed['path'] ?? '';
                if (str_starts_with($path, '/files/')) {
                    $storagePath = substr($path, 7);
                    $diskPath = Storage::disk('public')->path($storagePath);
                    if (file_exists($diskPath)) {
                        return $diskPath;
                    }
                }
                return null;
            };

            $videoPaths = [];
            foreach ($videoUrls as $i => $url) {
                $path = $resolveVideoPath($url);
                if (!$path) {
                    Log::error('InfiniteTalk: multi-concat video file not found', [
                        'index' => $i,
                        'url' => substr($url, 0, 80),
                    ]);
                    return null;
                }
                $videoPaths[] = $path;
            }

            // Output concatenated video
            $outputFilename = 'extended_' . time() . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Create concat list file
            $concatListPath = $outputDiskPath . '.concat.txt';
            $concatLines = array_map(fn($p) => "file " . escapeshellarg($p), $videoPaths);
            file_put_contents($concatListPath, implode("\n", $concatLines));

            // ffmpeg: concat demuxer — no re-encoding
            $cmd = sprintf(
                '%s -f concat -safe 0 -i %s -c copy %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($concatListPath),
                escapeshellarg($outputDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            @unlink($concatListPath);

            if ($returnCode !== 0 || !file_exists($outputDiskPath)) {
                Log::error('InfiniteTalk: multi-video concatenation failed', [
                    'videoCount' => count($videoUrls),
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk: multi-video concatenation complete', [
                'videoCount' => count($videoUrls),
                'outputPath' => $outputStoragePath,
                'outputSize' => filesize($outputDiskPath),
            ]);

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk: multi-video concat error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Add a subtle noise floor to an audio file to prevent InfiniteTalk from
     * freezing character animation during natural speech pauses.
     * Mixes very low pink noise into the existing audio so that even during
     * silent gaps between sentences, there's enough signal to keep animation active.
     *
     * @param string $audioUrl Public URL of the speaker's audio WAV
     * @param int $projectId Project ID for storage path
     * @return string|null Public URL of the enhanced audio, or null on failure (caller should use original)
     */
    public static function addNoiseFloorToAudio(string $audioUrl, int $projectId): ?string
    {
        try {
            $ffmpeg = null;
            foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $ffmpeg = $path;
                    break;
                }
            }

            if (!$ffmpeg) {
                Log::warning('InfiniteTalk: ffmpeg not found for noise floor');
                return null;
            }

            // Resolve audio URL to local disk path
            $parsed = parse_url($audioUrl);
            $urlPath = $parsed['path'] ?? '';
            if (!str_starts_with($urlPath, '/files/')) {
                Log::warning('InfiniteTalk: cannot resolve audio URL for noise floor', ['url' => substr($audioUrl, 0, 80)]);
                return null;
            }
            $storagePath = substr($urlPath, 7);
            $audioDiskPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($audioDiskPath)) {
                Log::warning('InfiniteTalk: audio file not found for noise floor', ['path' => $audioDiskPath]);
                return null;
            }

            // Output enhanced audio
            $enhancedFilename = 'noisefloor_' . time() . '_' . uniqid() . '.wav';
            $enhancedStoragePath = "wizard-audio/{$projectId}/{$enhancedFilename}";
            $enhancedDiskPath = Storage::disk('public')->path($enhancedStoragePath);

            $dir = dirname($enhancedDiskPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Mix original audio with very low pink noise (amplitude 0.006 ≈ -44dB)
            // Using amix with duration=first so output matches original audio length
            // The noise is inaudible but keeps InfiniteTalk's animation engine active during speech pauses
            $cmd = sprintf(
                '%s -i %s -f lavfi -i anoisesrc=c=pink:r=44100:a=0.006:d=60 -filter_complex "[0:a][1:a]amix=inputs=2:duration=first:dropout_transition=0" -c:a pcm_s16le %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($audioDiskPath),
                escapeshellarg($enhancedDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($enhancedDiskPath)) {
                Log::warning('InfiniteTalk: noise floor mixing failed', [
                    'returnCode' => $returnCode,
                    'output' => implode("\n", array_slice($output, -5)),
                ]);
                return null;
            }

            Log::info('InfiniteTalk: noise floor added to speaker audio', [
                'originalPath' => $storagePath,
                'enhancedPath' => $enhancedStoragePath,
                'enhancedSize' => filesize($enhancedDiskPath),
            ]);

            return url('/files/' . $enhancedStoragePath);

        } catch (\Throwable $e) {
            Log::error('InfiniteTalk: noise floor error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate a WAV file with ZERO amplitude (true digital silence) and return its URL.
     * Used in Dual Take mode for the non-speaking character — ensures absolutely no audio
     * signal reaches InfiniteTalk's lip-sync detector, preventing mouth movement bleed.
     *
     * Unlike generateAmbientWavUrl() which uses pink noise (~-42dB), this produces
     * pure silence via ffmpeg's anullsrc filter.
     */
    public static function generateTrueSilentWavUrl(int $projectId, float $durationSeconds = 5.0): string
    {
        $ffmpeg = null;
        foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
            if (file_exists($path) && is_executable($path)) {
                $ffmpeg = $path;
                break;
            }
        }

        // Fallback: if no ffmpeg, use PHP-generated silent WAV
        if (!$ffmpeg) {
            Log::warning('InfiniteTalk: ffmpeg not found for true silence, falling back to PHP silent WAV');
            return self::generateSilentWavUrl($projectId, $durationSeconds);
        }

        $filename = 'truesilent_' . md5($durationSeconds . '_' . $projectId . '_' . time()) . '.wav';
        $storagePath = "wizard-audio/{$projectId}/{$filename}";
        $diskPath = Storage::disk('public')->path($storagePath);

        $dir = dirname($diskPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Pure digital silence: anullsrc generates zero-amplitude samples
        $cmd = sprintf(
            '%s -f lavfi -i anullsrc=r=44100:cl=mono -t %s -c:a pcm_s16le %s -y 2>&1',
            escapeshellarg($ffmpeg),
            $durationSeconds,
            escapeshellarg($diskPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($diskPath)) {
            Log::warning('InfiniteTalk: true silence generation failed, falling back to PHP silent WAV', [
                'returnCode' => $returnCode,
            ]);
            return self::generateSilentWavUrl($projectId, $durationSeconds);
        }

        return url('/files/' . $storagePath);
    }

    // =========================================================================
    //  Pipeline Diagnostic Logging
    // =========================================================================

    /**
     * Append a diagnostic entry to the pipeline log JSON file.
     * Each entry records a step in the Dual Take pipeline for real-time visualization.
     */
    public static function writePipelineLog(int $projectId, string $step, string $status, array $data = [], string $message = ''): void
    {
        try {
            $storagePath = "wizard-videos/{$projectId}/pipeline-log.json";
            $diskPath = Storage::disk('public')->path($storagePath);
            $dir = dirname($diskPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $entries = [];
            if (file_exists($diskPath)) {
                $raw = file_get_contents($diskPath);
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $entries = $decoded;
                }
            }

            $entries[] = [
                'step' => $step,
                'status' => $status,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'epoch' => microtime(true),
                'message' => $message,
                'data' => $data,
            ];

            file_put_contents($diskPath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Pipeline diagnostic log write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear the pipeline log for a new animation run.
     */
    public static function clearPipelineLog(int $projectId): void
    {
        try {
            $storagePath = "wizard-videos/{$projectId}/pipeline-log.json";
            $diskPath = Storage::disk('public')->path($storagePath);
            $dir = dirname($diskPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($diskPath, json_encode([], JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('Pipeline diagnostic log clear failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate a self-contained diagnostic HTML page that auto-refreshes from pipeline-log.json.
     * Returns the public URL to the diagnostic page.
     */
    public static function generateDiagnosticHtml(int $projectId, string $projectTitle = ''): string
    {
        $storagePath = "wizard-videos/{$projectId}/diagnostic.html";
        $diskPath = Storage::disk('public')->path($storagePath);
        $dir = dirname($diskPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $logUrl = url("/files/wizard-videos/{$projectId}/pipeline-log.json");
        $startTime = now()->format('Y-m-d H:i:s');
        $title = $projectTitle ?: "Project {$projectId}";

        $html = <<<'HTMLEOF'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pipeline Diagnostic — __TITLE__</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0a0a14;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
.header{position:sticky;top:0;z-index:10;background:rgba(10,10,20,0.95);border-bottom:1px solid rgba(139,92,246,0.3);padding:1rem 1.5rem;backdrop-filter:blur(10px)}
.header h1{font-size:1.2rem;font-weight:700;color:#f1f5f9;display:flex;align-items:center;gap:0.5rem}
.header h1 .icon{font-size:1.4rem}
.header-meta{display:flex;gap:1.5rem;margin-top:0.4rem;font-size:0.78rem;color:#94a3b8}
.header-meta span{display:flex;align-items:center;gap:0.3rem}
.container{max-width:900px;margin:0 auto;padding:1.5rem}
.step-card{background:rgba(25,25,45,0.6);border:1px solid rgba(100,100,140,0.2);border-radius:0.75rem;margin-bottom:0.75rem;overflow:hidden;transition:border-color 0.3s}
.step-card.active{border-color:rgba(59,130,246,0.5);box-shadow:0 0 20px rgba(59,130,246,0.1)}
.step-card.done{border-color:rgba(16,185,129,0.3)}
.step-card.error{border-color:rgba(239,68,68,0.4)}
.step-card.pending{opacity:0.5}
.step-header{display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;cursor:pointer;user-select:none}
.step-icon{width:28px;height:28px;min-width:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700}
.step-icon.pending{background:rgba(100,100,140,0.2);color:#64748b}
.step-icon.start,.step-icon.info{background:rgba(59,130,246,0.2);color:#60a5fa}
.step-icon.done{background:rgba(16,185,129,0.2);color:#6ee7b7}
.step-icon.error{background:rgba(239,68,68,0.2);color:#fca5a5}
.step-name{font-weight:600;font-size:0.88rem;flex:1}
.step-time{font-size:0.72rem;color:#64748b;font-family:'Courier New',monospace}
.step-msg{font-size:0.78rem;color:#94a3b8;padding:0 1rem 0.5rem 3.5rem}
.step-body{padding:0.5rem 1rem 1rem 3.5rem;border-top:1px solid rgba(100,100,140,0.1);display:none}
.step-card.expanded .step-body{display:block}
.data-grid{display:grid;grid-template-columns:140px 1fr;gap:0.3rem 0.75rem;font-size:0.78rem}
.data-label{color:#64748b;font-weight:600}
.data-value{color:#e2e8f0;font-family:'Courier New',monospace;word-break:break-all}
.data-value.url{color:#60a5fa;font-size:0.72rem}
pre.prompt-block{background:rgba(0,0,0,0.4);border:1px solid rgba(100,100,140,0.15);border-radius:0.5rem;padding:0.75rem;margin-top:0.5rem;font-size:0.72rem;line-height:1.6;color:#a78bfa;white-space:pre-wrap;word-break:break-word;max-height:300px;overflow-y:auto;font-family:'Courier New',monospace}
pre.json-block{background:rgba(0,0,0,0.4);border:1px solid rgba(100,100,140,0.15);border-radius:0.5rem;padding:0.75rem;margin-top:0.5rem;font-size:0.7rem;line-height:1.5;color:#fbbf24;white-space:pre-wrap;word-break:break-word;max-height:400px;overflow-y:auto;font-family:'Courier New',monospace}
audio{width:100%;height:32px;margin-top:0.25rem;border-radius:0.25rem}
video{max-width:100%;max-height:200px;margin-top:0.5rem;border-radius:0.5rem;border:1px solid rgba(100,100,140,0.2)}
.spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(59,130,246,0.3);border-top-color:#60a5fa;border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.elapsed{font-size:0.72rem;color:#fb923c;font-weight:600;margin-left:auto}
.status-bar{position:sticky;bottom:0;background:rgba(10,10,20,0.95);border-top:1px solid rgba(100,100,140,0.15);padding:0.5rem 1.5rem;font-size:0.75rem;color:#64748b;display:flex;align-items:center;gap:1rem;backdrop-filter:blur(10px)}
.status-dot{width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse-dot 2s infinite}
.status-dot.stopped{background:#ef4444;animation:none}
@keyframes pulse-dot{0%,100%{opacity:0.4}50%{opacity:1}}
</style>
</head>
<body>
<div class="header">
    <h1><span class="icon">&#128300;</span> Dual Take Pipeline Diagnostic</h1>
    <div class="header-meta">
        <span>&#128196; __TITLE__</span>
        <span>&#128337; Started: __START_TIME__</span>
        <span id="elapsed-total"></span>
    </div>
</div>
<div class="container" id="timeline"></div>
<div class="status-bar">
    <div class="status-dot" id="status-dot"></div>
    <span id="status-text">Connecting...</span>
    <span style="margin-left:auto" id="poll-count"></span>
</div>

<script>
const LOG_URL = '__LOG_URL__';
const POLL_MS = 1500;
const STEP_ORDER = ['INIT','VOICE_INFO','AUDIO_ROUTING_TAKE1','PROMPT_TAKE1','PAYLOAD_TAKE1','DISPATCH_TAKE1','POLLING_TAKE1','TAKE1_COMPLETE','AUDIO_ROUTING_TAKE2','PROMPT_TAKE2','PAYLOAD_TAKE2','DISPATCH_TAKE2','POLLING_TAKE2','TAKE2_COMPLETE','ASSEMBLY','DONE'];

let lastCount = 0;
let pollNum = 0;
let startEpoch = null;
let isDone = false;

function statusIcon(s) {
    if (s === 'done') return '&#10004;';
    if (s === 'error') return '&#10008;';
    if (s === 'start' || s === 'info') return '<div class="spinner"></div>';
    return '&#8943;';
}

function renderAudio(url) {
    if (!url) return '';
    return '<audio controls preload="none" src="' + url + '"></audio>';
}

function renderVideo(url) {
    if (!url) return '';
    return '<video controls preload="none" src="' + url + '"></video>';
}

function renderData(data) {
    if (!data || Object.keys(data).length === 0) return '';
    let html = '<div class="data-grid">';
    for (const [k, v] of Object.entries(data)) {
        if (k === '_prompt' || k === '_payload' || k === '_ffmpeg') continue;
        let val = v;
        let cls = 'data-value';
        if (typeof v === 'string' && (v.startsWith('http') || v.startsWith('/files/'))) {
            cls += ' url';
            if (v.match(/\.(wav|mp3|flac|ogg)(\?|$)/i)) {
                html += '<div class="data-label">' + k + '</div><div class="' + cls + '">' + v.split('/').pop().substring(0, 60) + renderAudio(v) + '</div>';
                continue;
            }
            if (v.match(/\.(mp4|webm)(\?|$)/i)) {
                html += '<div class="data-label">' + k + '</div><div class="' + cls + '">' + v.split('/').pop().substring(0, 60) + renderVideo(v) + '</div>';
                continue;
            }
            val = v.length > 80 ? '...' + v.slice(-70) : v;
        } else if (typeof v === 'object') {
            val = JSON.stringify(v);
            if (val.length > 120) val = val.substring(0, 120) + '...';
        } else if (typeof v === 'boolean') {
            val = v ? 'YES' : 'NO';
        }
        html += '<div class="data-label">' + k + '</div><div class="' + cls + '">' + val + '</div>';
    }
    html += '</div>';
    if (data._prompt) {
        html += '<pre class="prompt-block">' + escHtml(data._prompt) + '</pre>';
    }
    if (data._payload) {
        html += '<pre class="json-block">' + escHtml(typeof data._payload === 'string' ? data._payload : JSON.stringify(data._payload, null, 2)) + '</pre>';
    }
    if (data._ffmpeg) {
        html += '<pre class="json-block" style="color:#fb923c;">' + escHtml(data._ffmpeg) + '</pre>';
    }
    return html;
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function render(entries) {
    const timeline = document.getElementById('timeline');
    const seenSteps = new Set(entries.map(e => e.step));

    // Build map of latest entry per step
    const latestByStep = {};
    entries.forEach(e => { latestByStep[e.step] = e; });

    let html = '';
    // Show steps in order, including pending future steps
    const allSteps = [...new Set([...entries.map(e => e.step), ...STEP_ORDER])];
    const orderedSteps = STEP_ORDER.filter(s => allSteps.includes(s));
    // Add any extra steps not in STEP_ORDER
    entries.forEach(e => { if (!orderedSteps.includes(e.step)) orderedSteps.push(e.step); });

    for (const stepName of orderedSteps) {
        const entry = latestByStep[stepName];
        const status = entry ? entry.status : 'pending';
        const isActive = status === 'start' || status === 'info';
        const cardClass = status === 'done' ? 'done' : status === 'error' ? 'error' : isActive ? 'active' : seenSteps.has(stepName) ? '' : 'pending';
        const expanded = entry && (entry.data && Object.keys(entry.data).length > 0);

        html += '<div class="step-card ' + cardClass + (expanded ? ' expanded' : '') + '" onclick="this.classList.toggle(\'expanded\')">';
        html += '<div class="step-header">';
        html += '<div class="step-icon ' + status + '">' + statusIcon(status) + '</div>';
        html += '<div class="step-name">' + stepName + '</div>';
        if (entry && entry.timestamp) {
            html += '<div class="step-time">' + entry.timestamp.split(' ')[1] + '</div>';
        }
        if (isActive && entry && entry.epoch && startEpoch) {
            const elapsed = Math.round(Date.now()/1000 - entry.epoch);
            if (elapsed > 5) {
                const m = Math.floor(elapsed/60);
                const s = elapsed % 60;
                html += '<div class="elapsed">' + (m > 0 ? m + 'm ' : '') + s + 's</div>';
            }
        }
        html += '</div>';
        if (entry && entry.message) {
            html += '<div class="step-msg">' + escHtml(entry.message) + '</div>';
        }
        if (entry && entry.data && Object.keys(entry.data).length > 0) {
            html += '<div class="step-body">' + renderData(entry.data) + '</div>';
        }
        html += '</div>';
    }

    timeline.innerHTML = html;
}

async function poll() {
    try {
        const res = await fetch(LOG_URL + '?t=' + Date.now());
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const entries = await res.json();
        pollNum++;

        if (!startEpoch && entries.length > 0) {
            startEpoch = entries[0].epoch;
        }

        if (entries.length !== lastCount) {
            lastCount = entries.length;
            render(entries);
            // Auto-scroll to bottom
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        // Check if pipeline is done
        const lastEntry = entries[entries.length - 1];
        if (lastEntry && (lastEntry.step === 'DONE' || lastEntry.step === 'ERROR')) {
            isDone = true;
            document.getElementById('status-dot').classList.add('stopped');
            document.getElementById('status-text').textContent = lastEntry.step === 'DONE' ? 'Pipeline complete' : 'Pipeline error';
        }

        document.getElementById('status-text').textContent = isDone ? (lastEntry.step === 'DONE' ? 'Pipeline complete' : 'Pipeline error') : 'Live — polling every 1.5s';
        document.getElementById('poll-count').textContent = 'Poll #' + pollNum + ' | ' + entries.length + ' entries';

        // Update total elapsed
        if (startEpoch) {
            const totalSec = Math.round(Date.now()/1000 - startEpoch);
            const tm = Math.floor(totalSec/60);
            const ts = totalSec % 60;
            document.getElementById('elapsed-total').textContent = 'Elapsed: ' + (tm > 0 ? tm + 'm ' : '') + ts + 's';
        }
    } catch (e) {
        document.getElementById('status-text').textContent = 'Waiting for pipeline data...';
    }

    if (!isDone) {
        setTimeout(poll, POLL_MS);
    }
}

poll();
</script>
</body>
</html>
HTMLEOF;

        // Replace placeholders
        $html = str_replace('__LOG_URL__', $logUrl, $html);
        $html = str_replace('__TITLE__', htmlspecialchars($title), $html);
        $html = str_replace('__START_TIME__', $startTime, $html);

        file_put_contents($diskPath, $html);

        return url("/files/wizard-videos/{$projectId}/diagnostic.html");
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
