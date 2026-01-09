<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

/**
 * Video Render Service
 *
 * Handles video rendering with FFmpeg, including:
 * - Ken Burns effect generation from images
 * - Audio mixing (voiceovers + background music)
 * - Caption burning
 * - Cloud storage upload
 *
 * Based on the reference video-processor from video-creation-wizard.
 */
class VideoRenderService
{
    /**
     * FFmpeg and FFprobe paths
     */
    protected string $ffmpegPath;
    protected string $ffprobePath;

    /**
     * Temp directory for processing
     */
    protected string $tempDir;

    /**
     * Storage bucket name
     */
    protected string $bucketName;

    /**
     * Video processor service URL (Cloud Run)
     */
    protected string $videoProcessorUrl;

    /**
     * Whether to use parallel scene processing
     */
    protected bool $parallelProcessing;

    /**
     * Quality settings for different render modes
     */
    protected array $qualitySettings = [
        'fast' => [
            'preset' => 'ultrafast',
            'fps' => 30,
            'zoompanFps' => 60,
            'crf' => '26',
        ],
        'balanced' => [
            'preset' => 'fast',
            'fps' => 30,
            'zoompanFps' => 60,
            'crf' => '23',
        ],
        'best' => [
            'preset' => 'medium',
            'fps' => 30,
            'zoompanFps' => 60,
            'crf' => '20',
        ],
    ];

    /**
     * Resolution settings
     */
    protected array $resolutions = [
        '480p' => ['width' => 854, 'height' => 480],
        '720p' => ['width' => 1280, 'height' => 720],
        '1080p' => ['width' => 1920, 'height' => 1080],
        '1440p' => ['width' => 2560, 'height' => 1440],
        '4k' => ['width' => 3840, 'height' => 2160],
    ];

    public function __construct()
    {
        $config = config('services.video_processor');

        $this->ffmpegPath = $config['ffmpeg_path'] ?? 'ffmpeg';
        $this->ffprobePath = $config['ffprobe_path'] ?? 'ffprobe';
        $this->tempDir = $config['temp_dir'] ?? '/tmp/video-processing';
        $this->bucketName = $config['bucket'] ?? 'ytseo-6d1b0.firebasestorage.app';
        $this->videoProcessorUrl = $config['url'] ?? '';
        $this->parallelProcessing = (bool) ($config['parallel_scenes'] ?? false);

        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Process a complete video export
     *
     * @param array $manifest Export manifest containing scenes, output settings, music, captions
     * @param callable|null $progressCallback Callback for progress updates
     * @return array Result with outputUrl, outputPath, outputSize
     */
    public function processExport(array $manifest, ?callable $progressCallback = null): array
    {
        $jobId = Str::uuid()->toString();
        $workDir = $this->tempDir . '/export_' . $jobId;

        try {
            mkdir($workDir, 0755, true);
            Log::info("[VideoRender:{$jobId}] Starting export", ['scenes' => count($manifest['scenes'] ?? [])]);

            $scenes = $manifest['scenes'] ?? [];
            $output = $manifest['output'] ?? [];
            $music = $manifest['music'] ?? null;
            $captions = $manifest['captions'] ?? null;

            if (empty($scenes)) {
                throw new Exception('No scenes provided in manifest');
            }

            // Step 1: Download all images
            $this->updateProgress($progressCallback, 5, 'Preparing images...');
            $imageFiles = $this->downloadAllImages($jobId, $scenes, $workDir);

            // Step 2: Download all voiceovers
            $this->updateProgress($progressCallback, 15, 'Loading voiceovers...');
            $voiceoverFiles = $this->downloadAllVoiceovers($jobId, $scenes, $workDir);

            // Step 3: Download background music (if any)
            $musicFile = null;
            if (!empty($music['url'])) {
                $this->updateProgress($progressCallback, 20, 'Loading background music...');
                $musicFile = $this->downloadFile($music['url'], $workDir . '/music.mp3', $jobId);
            }

            // Step 4: Generate Ken Burns video from images
            $this->updateProgress($progressCallback, 25, 'Creating video scenes...');
            $videoOnlyFile = $this->generateKenBurnsVideo(
                $jobId,
                $scenes,
                $imageFiles,
                $workDir,
                $output,
                $progressCallback
            );

            // Step 5: Combine video with audio
            $this->updateProgress($progressCallback, 70, 'Adding voiceovers and music...');
            $finalVideoFile = $this->combineVideoWithAudio(
                $jobId,
                $videoOnlyFile,
                $scenes,
                $voiceoverFiles,
                $musicFile,
                $music['volume'] ?? 0.3,
                $workDir,
                $output
            );

            // Step 6: Burn captions if enabled
            if (!empty($captions['enabled']) && !empty($captions['style']) && $captions['style'] !== 'none') {
                $this->updateProgress($progressCallback, 85, 'Adding captions...');
                $captionedFile = $this->burnCaptions(
                    $jobId,
                    $finalVideoFile,
                    $scenes,
                    $captions,
                    $workDir
                );
                if ($captionedFile) {
                    $finalVideoFile = $captionedFile;
                }
            }

            // Step 7: Upload to cloud storage
            $this->updateProgress($progressCallback, 92, 'Uploading video...');
            $result = $this->uploadToStorage(
                $jobId,
                $finalVideoFile,
                $manifest['userId'] ?? 'anonymous',
                $manifest['projectId'] ?? $jobId
            );

            // Cleanup
            $this->cleanupWorkDir($workDir);

            $this->updateProgress($progressCallback, 100, 'Export complete!');

            Log::info("[VideoRender:{$jobId}] Export completed", $result);

            return $result;

        } catch (Exception $e) {
            Log::error("[VideoRender:{$jobId}] Export failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->cleanupWorkDir($workDir);
            throw $e;
        }
    }

    /**
     * Process export via Cloud Run service
     *
     * @param array $manifest Export manifest
     * @param string $jobId Job ID for tracking
     * @return array Initial response with job status
     */
    public function processExportViaCloudRun(array $manifest, string $jobId): array
    {
        if (empty($this->videoProcessorUrl)) {
            throw new Exception('VIDEO_PROCESSOR_URL not configured');
        }

        $response = Http::timeout(30)
            ->post("{$this->videoProcessorUrl}/creation-export", [
                'jobId' => $jobId,
                'manifest' => $manifest,
            ]);

        if (!$response->successful()) {
            throw new Exception('Cloud Run request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get export status from Cloud Run
     */
    public function getCloudRunExportStatus(string $jobId): array
    {
        if (empty($this->videoProcessorUrl)) {
            throw new Exception('VIDEO_PROCESSOR_URL not configured');
        }

        $response = Http::timeout(10)
            ->get("{$this->videoProcessorUrl}/creation-status/{$jobId}");

        if (!$response->successful()) {
            throw new Exception('Status check failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Download all scene images
     */
    protected function downloadAllImages(string $jobId, array $scenes, string $workDir): array
    {
        $imageFiles = [];

        foreach ($scenes as $i => $scene) {
            $imageUrl = $scene['imageUrl'] ?? null;

            if (!$imageUrl) {
                Log::warning("[VideoRender:{$jobId}] Scene {$i} has no image URL");
                $imageFiles[] = null;
                continue;
            }

            $ext = str_contains($imageUrl, '.png') ? 'png' : 'jpg';
            $outputPath = "{$workDir}/scene_{$i}.{$ext}";

            try {
                $this->downloadFile($imageUrl, $outputPath, $jobId);
                $imageFiles[] = $outputPath;
                Log::debug("[VideoRender:{$jobId}] Downloaded image " . ($i + 1) . "/" . count($scenes));
            } catch (Exception $e) {
                Log::error("[VideoRender:{$jobId}] Failed to download image {$i}: " . $e->getMessage());
                $imageFiles[] = null;
            }
        }

        return $imageFiles;
    }

    /**
     * Download all scene voiceovers
     */
    protected function downloadAllVoiceovers(string $jobId, array $scenes, string $workDir): array
    {
        $voiceoverFiles = [];

        foreach ($scenes as $i => $scene) {
            $voiceoverUrl = $scene['voiceoverUrl'] ?? null;

            if (!$voiceoverUrl) {
                $voiceoverFiles[] = null;
                continue;
            }

            $ext = str_contains($voiceoverUrl, '.wav') ? 'wav' : 'mp3';
            $outputPath = "{$workDir}/voice_{$i}.{$ext}";

            try {
                $this->downloadFile($voiceoverUrl, $outputPath, $jobId);
                $voiceoverFiles[] = $outputPath;
            } catch (Exception $e) {
                Log::error("[VideoRender:{$jobId}] Failed to download voiceover {$i}: " . $e->getMessage());
                $voiceoverFiles[] = null;
            }
        }

        return $voiceoverFiles;
    }

    /**
     * Download a file from URL
     */
    protected function downloadFile(string $url, string $outputPath, string $jobId): string
    {
        Log::debug("[VideoRender:{$jobId}] Downloading: " . substr($url, 0, 80) . "...");

        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            throw new Exception("HTTP {$response->status()}: Failed to download file");
        }

        file_put_contents($outputPath, $response->body());

        $fileSize = filesize($outputPath);
        Log::debug("[VideoRender:{$jobId}] Downloaded " . round($fileSize / 1024, 1) . " KB");

        return $outputPath;
    }

    /**
     * Generate Ken Burns video from images
     */
    protected function generateKenBurnsVideo(
        string $jobId,
        array $scenes,
        array $imageFiles,
        string $workDir,
        array $output,
        ?callable $progressCallback = null
    ): string {
        $outputFile = "{$workDir}/video_only.mp4";

        // Get resolution
        $quality = $output['quality'] ?? '1080p';
        $resolution = $this->resolutions[$quality] ?? $this->resolutions['1080p'];
        $width = $resolution['width'];
        $height = $resolution['height'];

        // Adjust for aspect ratio
        $aspectRatio = $output['aspectRatio'] ?? '16:9';
        if ($aspectRatio === '9:16') {
            $width = (int) round($height * 9 / 16);
        } elseif ($aspectRatio === '1:1') {
            $width = $height = min($width, $height);
        } elseif ($aspectRatio === '4:5') {
            $width = (int) round($height * 4 / 5);
        }

        // Get quality settings
        $renderQuality = $output['renderQuality'] ?? 'balanced';
        $settings = $this->qualitySettings[$renderQuality] ?? $this->qualitySettings['balanced'];
        $fps = $output['fps'] ?? $settings['fps'];

        Log::info("[VideoRender:{$jobId}] Generating video: {$width}x{$height} @ {$fps}fps, quality: {$renderQuality}");

        // Process each scene
        $sceneVideos = [];
        $sceneCount = count($scenes);

        foreach ($scenes as $i => $scene) {
            $imageFile = $imageFiles[$i] ?? null;

            if (!$imageFile || !file_exists($imageFile)) {
                Log::warning("[VideoRender:{$jobId}] Skipping scene {$i} - no image");
                continue;
            }

            $duration = $scene['duration'] ?? 8;
            $sceneOutput = "{$workDir}/scene_video_{$i}.mp4";

            // Get Ken Burns parameters
            $kb = $scene['kenBurns'] ?? [];
            $startScale = $kb['startScale'] ?? 1.0;
            $endScale = $kb['endScale'] ?? 1.2;
            $startX = $kb['startX'] ?? 0.5;
            $startY = $kb['startY'] ?? 0.5;
            $endX = $kb['endX'] ?? 0.5;
            $endY = $kb['endY'] ?? 0.5;

            // Calculate zoompan parameters
            $zoompanFps = $settings['zoompanFps'];
            $zoompanFrames = (int) round($duration * $zoompanFps);

            // Build Ken Burns filter (8000px pre-scaling for smooth zoompan)
            $progressExpr = "(on/" . ($zoompanFrames - 1) . ")";
            $zoomExpr = "{$startScale}+({$endScale}-{$startScale})*{$progressExpr}";
            $xExpr = "({$startX}+({$endX}-{$startX})*{$progressExpr})*(iw-iw/zoom)";
            $yExpr = "({$startY}+({$endY}-{$startY})*{$progressExpr})*(ih-ih/zoom)";

            $filter = "scale=8000:-1:flags=lanczos,zoompan=z='{$zoomExpr}':x='{$xExpr}':y='{$yExpr}':d={$zoompanFrames}:s={$width}x{$height}:fps={$zoompanFps},fps={$fps},setsar=1";

            // Build FFmpeg command
            $cmd = [
                $this->ffmpegPath,
                '-loop', '1',
                '-i', $imageFile,
                '-vf', $filter,
                '-t', (string) $duration,
                '-c:v', 'libx264',
                '-preset', $settings['preset'],
                '-crf', $settings['crf'],
                '-pix_fmt', 'yuv420p',
                '-y',
                $sceneOutput,
            ];

            Log::debug("[VideoRender:{$jobId}] Processing scene " . ($i + 1) . "/{$sceneCount}...");

            // Update progress
            $sceneProgress = 25 + (int) round(($i / $sceneCount) * 40);
            $this->updateProgress($progressCallback, $sceneProgress, "Creating scene " . ($i + 1) . " of {$sceneCount}...");

            // Execute FFmpeg
            $this->runCommand($cmd, $jobId, "Scene " . ($i + 1));

            if (file_exists($sceneOutput)) {
                $sceneVideos[] = $sceneOutput;
                Log::debug("[VideoRender:{$jobId}] Scene " . ($i + 1) . " complete");
            }
        }

        if (empty($sceneVideos)) {
            throw new Exception('No scenes were successfully rendered');
        }

        // Concatenate all scene videos
        Log::info("[VideoRender:{$jobId}] Concatenating {$sceneCount} scenes...");
        $this->updateProgress($progressCallback, 65, 'Assembling video...');

        $concatFile = "{$workDir}/concat.txt";
        $concatContent = implode("\n", array_map(fn($f) => "file '{$f}'", $sceneVideos));
        file_put_contents($concatFile, $concatContent);

        $concatCmd = [
            $this->ffmpegPath,
            '-f', 'concat',
            '-safe', '0',
            '-i', $concatFile,
            '-c', 'copy',
            '-movflags', '+faststart',
            '-y',
            $outputFile,
        ];

        $this->runCommand($concatCmd, $jobId, 'Concat');

        if (!file_exists($outputFile)) {
            throw new Exception('Failed to create concatenated video');
        }

        $fileSize = filesize($outputFile);
        Log::info("[VideoRender:{$jobId}] Ken Burns video created: " . round($fileSize / 1024 / 1024, 2) . " MB");

        return $outputFile;
    }

    /**
     * Combine video with voiceovers and background music
     */
    protected function combineVideoWithAudio(
        string $jobId,
        string $videoFile,
        array $scenes,
        array $voiceoverFiles,
        ?string $musicFile,
        float $musicVolume,
        string $workDir,
        array $output
    ): string {
        $outputFile = "{$workDir}/final_output.mp4";

        $hasVoiceovers = !empty(array_filter($voiceoverFiles));
        $hasMusic = $musicFile && file_exists($musicFile);

        if (!$hasVoiceovers && !$hasMusic) {
            Log::info("[VideoRender:{$jobId}] No audio to add, copying video as-is");
            copy($videoFile, $outputFile);
            return $outputFile;
        }

        // First concatenate voiceovers with proper timing
        $voiceoverConcatFile = null;
        if ($hasVoiceovers) {
            $voiceoverConcatFile = $this->concatenateVoiceovers($jobId, $scenes, $voiceoverFiles, $workDir);
        }

        // Build FFmpeg command
        $inputArgs = ['-i', $videoFile];
        $filterParts = [];
        $audioStream = null;

        if ($voiceoverConcatFile && file_exists($voiceoverConcatFile)) {
            $inputArgs[] = '-i';
            $inputArgs[] = $voiceoverConcatFile;
            $audioStream = '1:a';
        }

        if ($hasMusic) {
            $inputArgs[] = '-i';
            $inputArgs[] = $musicFile;
            $musicIdx = count(array_filter($inputArgs, fn($a) => $a === '-i'));

            if ($audioStream) {
                // Mix voiceover with music
                $filterParts[] = "[1:a]volume=1.0[voice]";
                $filterParts[] = "[{$musicIdx}:a]volume={$musicVolume},aloop=loop=-1:size=2e+09[music]";
                $filterParts[] = "[voice][music]amix=inputs=2:duration=first:dropout_transition=2[aout]";
                $audioStream = '[aout]';
            } else {
                $filterParts[] = "[{$musicIdx}:a]volume={$musicVolume},aloop=loop=-1:size=2e+09[aout]";
                $audioStream = '[aout]';
            }
        }

        $cmd = array_merge([$this->ffmpegPath], $inputArgs);

        if (!empty($filterParts)) {
            $cmd[] = '-filter_complex';
            $cmd[] = implode(';', $filterParts);
        }

        $cmd = array_merge($cmd, [
            '-map', '0:v',
            '-map', $audioStream ?? '1:a',
            '-c:v', 'copy',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            '-movflags', '+faststart',
            '-y',
            $outputFile,
        ]);

        Log::info("[VideoRender:{$jobId}] Combining video with audio...");
        $this->runCommand($cmd, $jobId, 'Audio Mix');

        if (!file_exists($outputFile)) {
            throw new Exception('Failed to create final video with audio');
        }

        $fileSize = filesize($outputFile);
        Log::info("[VideoRender:{$jobId}] Final video created: " . round($fileSize / 1024 / 1024, 2) . " MB");

        return $outputFile;
    }

    /**
     * Concatenate voiceovers with proper timing
     */
    protected function concatenateVoiceovers(
        string $jobId,
        array $scenes,
        array $voiceoverFiles,
        string $workDir
    ): ?string {
        $outputFile = "{$workDir}/voiceovers_concat.mp3";
        $listFile = "{$workDir}/voice_list.txt";
        $listContent = [];

        foreach ($scenes as $i => $scene) {
            $voiceFile = $voiceoverFiles[$i] ?? null;
            $sceneDuration = $scene['duration'] ?? 8;
            $voiceoverOffset = $scene['voiceoverOffset'] ?? 0;

            if ($voiceFile && file_exists($voiceFile)) {
                // Add offset silence if needed
                if ($voiceoverOffset > 0.05) {
                    $offsetSilence = "{$workDir}/offset_silence_{$i}.mp3";
                    $this->generateSilence($offsetSilence, $voiceoverOffset, $jobId);
                    $listContent[] = "file '{$offsetSilence}'";
                }

                // Add voiceover
                $listContent[] = "file '{$voiceFile}'";

                // Get voiceover duration and add trailing silence
                $voiceDuration = $this->getAudioDuration($voiceFile);
                $remainingTime = $sceneDuration - $voiceoverOffset - $voiceDuration;

                if ($remainingTime > 0.1) {
                    $sceneSilence = "{$workDir}/silence_{$i}.mp3";
                    $this->generateSilence($sceneSilence, $remainingTime, $jobId);
                    $listContent[] = "file '{$sceneSilence}'";
                }
            } else {
                // No voiceover - add silence for entire scene
                $sceneSilence = "{$workDir}/silence_full_{$i}.mp3";
                $this->generateSilence($sceneSilence, $sceneDuration, $jobId);
                $listContent[] = "file '{$sceneSilence}'";
            }
        }

        file_put_contents($listFile, implode("\n", $listContent));

        // Concatenate all audio
        $cmd = [
            $this->ffmpegPath,
            '-f', 'concat',
            '-safe', '0',
            '-i', $listFile,
            '-c:a', 'libmp3lame',
            '-q:a', '2',
            '-y',
            $outputFile,
        ];

        $this->runCommand($cmd, $jobId, 'Voice Concat');

        if (!file_exists($outputFile)) {
            Log::warning("[VideoRender:{$jobId}] Failed to concatenate voiceovers");
            return null;
        }

        return $outputFile;
    }

    /**
     * Generate silence audio file
     */
    protected function generateSilence(string $outputPath, float $duration, string $jobId): void
    {
        $cmd = [
            $this->ffmpegPath,
            '-f', 'lavfi',
            '-i', 'anullsrc=r=44100:cl=stereo',
            '-t', (string) $duration,
            '-q:a', '9',
            '-y',
            $outputPath,
        ];

        $this->runCommand($cmd, $jobId, 'Silence', false);
    }

    /**
     * Get audio duration using ffprobe
     */
    protected function getAudioDuration(string $filePath): float
    {
        $cmd = [
            $this->ffprobePath,
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $filePath,
        ];

        $output = shell_exec(implode(' ', array_map('escapeshellarg', $cmd)));
        return (float) trim($output ?: '0');
    }

    /**
     * Burn captions into video
     */
    protected function burnCaptions(
        string $jobId,
        string $videoFile,
        array $scenes,
        array $captionsConfig,
        string $workDir
    ): ?string {
        $captionFile = "{$workDir}/captions.ass";
        $outputFile = "{$workDir}/final_with_captions.mp4";

        // Generate ASS caption file
        $assContent = $this->generateAssFile($scenes, $captionsConfig);
        file_put_contents($captionFile, $assContent);

        // Escape path for FFmpeg
        $escapedPath = str_replace(['\\', ':', "'"], ['/', '\\:', "'\\''"], $captionFile);

        $cmd = [
            $this->ffmpegPath,
            '-i', $videoFile,
            '-vf', "ass='{$escapedPath}'",
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '23',
            '-c:a', 'copy',
            '-movflags', '+faststart',
            '-y',
            $outputFile,
        ];

        Log::info("[VideoRender:{$jobId}] Burning captions...");

        try {
            $this->runCommand($cmd, $jobId, 'Captions');

            if (file_exists($outputFile)) {
                return $outputFile;
            }
        } catch (Exception $e) {
            Log::error("[VideoRender:{$jobId}] Caption burning failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate ASS subtitle file
     */
    protected function generateAssFile(array $scenes, array $captionsConfig): string
    {
        $style = $captionsConfig['style'] ?? 'karaoke';
        $position = $captionsConfig['position'] ?? 'bottom';
        $size = $captionsConfig['size'] ?? 1.0;
        $fontFamily = $captionsConfig['fontFamily'] ?? 'Arial';
        $fillColor = $captionsConfig['fillColor'] ?? '#FFFFFF';

        // ASS header
        $ass = "[Script Info]\n";
        $ass .= "ScriptType: v4.00+\n";
        $ass .= "PlayResX: 1920\n";
        $ass .= "PlayResY: 1080\n\n";

        // Calculate alignment based on position
        $alignment = match ($position) {
            'top' => 8,
            'middle' => 5,
            default => 2, // bottom
        };

        // Style definition
        $fontSize = (int) (72 * $size);
        $hexColor = str_replace('#', '', $fillColor);
        $assColor = '&H' . substr($hexColor, 4, 2) . substr($hexColor, 2, 2) . substr($hexColor, 0, 2) . '&';

        $ass .= "[V4+ Styles]\n";
        $ass .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
        $ass .= "Style: Default,{$fontFamily},{$fontSize},{$assColor},&H000000FF,&H00000000,&H80000000,1,0,0,0,100,100,0,0,1,3,2,{$alignment},50,50,50,1\n\n";

        // Events
        $ass .= "[Events]\n";
        $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";

        $currentTime = 0;
        foreach ($scenes as $scene) {
            $duration = $scene['duration'] ?? 8;
            $narration = $scene['narration'] ?? '';

            if (!empty($narration)) {
                $startTime = $this->formatAssTime($currentTime);
                $endTime = $this->formatAssTime($currentTime + $duration);

                // Clean text for ASS
                $text = str_replace(["\n", "\r"], "\\N", $narration);

                $ass .= "Dialogue: 0,{$startTime},{$endTime},Default,,0,0,0,,{$text}\n";
            }

            $currentTime += $duration;
        }

        return $ass;
    }

    /**
     * Format time for ASS subtitle format
     */
    protected function formatAssTime(float $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        $centiseconds = (int) (($secs - floor($secs)) * 100);

        return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, (int) $secs, $centiseconds);
    }

    /**
     * Upload to cloud storage
     */
    protected function uploadToStorage(
        string $jobId,
        string $filePath,
        string $userId,
        string $projectId
    ): array {
        $fileName = "creation-exports/{$userId}/{$projectId}-" . time() . ".mp4";
        $fileSize = filesize($filePath);

        Log::info("[VideoRender:{$jobId}] Uploading to: {$fileName}");

        try {
            // Try Google Cloud Storage
            if (config('filesystems.disks.gcs.bucket')) {
                Storage::disk('gcs')->put($fileName, file_get_contents($filePath), 'public');
                $publicUrl = "https://storage.googleapis.com/{$this->bucketName}/{$fileName}";
            } else {
                // Fallback to public disk
                Storage::disk('public')->put($fileName, file_get_contents($filePath));
                $publicUrl = Storage::disk('public')->url($fileName);
            }

            Log::info("[VideoRender:{$jobId}] Upload completed: {$publicUrl}");

            return [
                'outputUrl' => $publicUrl,
                'outputPath' => $fileName,
                'outputSize' => $fileSize,
            ];

        } catch (Exception $e) {
            Log::error("[VideoRender:{$jobId}] Upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run an FFmpeg command
     */
    protected function runCommand(array $cmd, string $jobId, string $label, bool $log = true): void
    {
        $cmdString = implode(' ', array_map('escapeshellarg', $cmd));

        if ($log) {
            Log::debug("[VideoRender:{$jobId}] [{$label}] Executing: " . substr($cmdString, 0, 200) . "...");
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmdString, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new Exception("Failed to start FFmpeg process");
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $errorMsg = trim($stderr ?: $stdout);
            Log::error("[VideoRender:{$jobId}] [{$label}] FFmpeg failed with code {$exitCode}: " . substr($errorMsg, -500));
            throw new Exception("FFmpeg [{$label}] failed with code {$exitCode}");
        }
    }

    /**
     * Update progress via callback
     */
    protected function updateProgress(?callable $callback, int $progress, string $message): void
    {
        if ($callback) {
            $callback($progress, $message);
        }
    }

    /**
     * Cleanup working directory
     */
    protected function cleanupWorkDir(string $workDir): void
    {
        if (is_dir($workDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($workDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            rmdir($workDir);
            Log::debug("Cleaned up: {$workDir}");
        }
    }

    /**
     * Check if FFmpeg is available
     */
    public function checkFfmpeg(): bool
    {
        $output = shell_exec("{$this->ffmpegPath} -version 2>&1");
        return $output && str_contains($output, 'ffmpeg version');
    }

    /**
     * Get FFmpeg version info
     */
    public function getFfmpegVersion(): ?string
    {
        $output = shell_exec("{$this->ffmpegPath} -version 2>&1");
        if ($output && preg_match('/ffmpeg version (\S+)/', $output, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
