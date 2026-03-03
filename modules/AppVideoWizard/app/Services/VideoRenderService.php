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
                (string) ($manifest['userId'] ?? 'anonymous'),
                (string) ($manifest['projectId'] ?? $jobId)
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
     * Process a Story Mode export (video clips + voiceover assembly).
     *
     * Unlike processExport() which generates Ken Burns from images,
     * this method handles pre-rendered video clips (from Seedance/etc.)
     * and concatenates them with voiceover audio.
     *
     * @param array $manifest Story mode manifest with scenes containing videoUrl/imageUrl/voiceoverUrl
     * @param callable|null $progressCallback Callback for progress updates
     * @return array Result with outputUrl, outputPath, outputSize
     */
    public function processStoryModeExport(array $manifest, ?callable $progressCallback = null): array
    {
        $jobId = Str::uuid()->toString();
        $workDir = $this->tempDir . '/story_' . $jobId;

        try {
            mkdir($workDir, 0755, true);
            $scenes = $manifest['scenes'] ?? [];
            $output = $manifest['output'] ?? [];
            $music = $manifest['music'] ?? null;

            if (empty($scenes)) {
                throw new Exception('No scenes provided in manifest');
            }

            $sceneCount = count($scenes);
            Log::info("[StoryExport:{$jobId}] Starting with {$sceneCount} scenes");

            // Determine target resolution
            $aspectRatio = $output['aspectRatio'] ?? '9:16';
            $width = $output['width'] ?? 1080;
            $height = $output['height'] ?? 1920;

            // Step 1: Download video clips (or images as fallback)
            $this->updateProgress($progressCallback, 5, 'Downloading video clips...');
            $clipFiles = [];
            $voiceoverFiles = [];

            foreach ($scenes as $i => $scene) {
                $videoUrl = $scene['videoUrl'] ?? null;
                $imageUrl = $scene['imageUrl'] ?? null;
                $voiceoverUrl = $scene['voiceoverUrl'] ?? null;
                $duration = $scene['duration'] ?? 6;

                // Download video clip or image
                if ($videoUrl) {
                    $clipPath = "{$workDir}/clip_{$i}.mp4";
                    try {
                        $this->downloadFile($videoUrl, $clipPath, $jobId);
                        $clipFiles[$i] = ['type' => 'video', 'path' => $clipPath, 'duration' => $duration];
                        Log::debug("[StoryExport:{$jobId}] Downloaded clip {$i}");
                    } catch (Exception $e) {
                        Log::warning("[StoryExport:{$jobId}] Failed to download clip {$i}, trying image fallback");
                        $clipFiles[$i] = null;
                    }
                }

                // Fallback to image if no video clip
                if (empty($clipFiles[$i]) && $imageUrl) {
                    $ext = str_contains($imageUrl, '.png') ? 'png' : 'jpg';
                    $imgPath = "{$workDir}/img_{$i}.{$ext}";
                    try {
                        $this->downloadFile($imageUrl, $imgPath, $jobId);
                        $clipFiles[$i] = ['type' => 'image', 'path' => $imgPath, 'duration' => $duration];
                    } catch (Exception $e) {
                        Log::error("[StoryExport:{$jobId}] Failed to download image {$i}");
                        $clipFiles[$i] = null;
                    }
                }

                // Download voiceover
                if ($voiceoverUrl) {
                    $voicePath = "{$workDir}/voice_{$i}.mp3";
                    try {
                        $this->downloadFile($voiceoverUrl, $voicePath, $jobId);
                        $voiceoverFiles[$i] = $voicePath;
                    } catch (Exception $e) {
                        Log::warning("[StoryExport:{$jobId}] Failed to download voiceover {$i}");
                        $voiceoverFiles[$i] = null;
                    }
                }

                $dlProgress = 5 + (int) round(($i / $sceneCount) * 20);
                $this->updateProgress($progressCallback, $dlProgress, 'Downloading scene ' . ($i + 1) . "/{$sceneCount}...");
            }

            // Step 2: Normalize and prepare scene videos
            $this->updateProgress($progressCallback, 30, 'Processing video scenes...');
            $normalizedClips = [];
            $renderQuality = $output['renderQuality'] ?? $output['quality'] ?? 'balanced';
            $settings = $this->qualitySettings[$renderQuality] ?? $this->qualitySettings['balanced'];
            $fps = $output['fps'] ?? $settings['fps'];

            // Calculate extra duration for the last scene to compensate for
            // xfade transition overlaps + a 2s safety buffer so nothing is cut off.
            $transitions = $manifest['transitions'] ?? [];
            $crossfadeDuration = (float) ($transitions['crossfadeDuration'] ?? 0.5);
            $validClipCount = count(array_filter($clipFiles));
            $xfadeOverlap = max(0, ($validClipCount - 1)) * $crossfadeDuration;
            $lastSceneBuffer = $xfadeOverlap + 2.0;

            $clipKeys = array_keys(array_filter($clipFiles));
            $lastClipKey = !empty($clipKeys) ? end($clipKeys) : -1;

            foreach ($clipFiles as $i => $clip) {
                if (!$clip) continue;

                $normalizedPath = "{$workDir}/norm_{$i}.mp4";
                $isLastClip = ($i === $lastClipKey);

                if ($clip['type'] === 'video') {
                    // Normalize video clip: crop-aware or standard scale
                    $cropData = $scenes[$i]['crop'] ?? null;
                    $videoEdit = $scenes[$i]['video_edit'] ?? $scenes[$i]['videoEdit'] ?? null;

                    // Build filter chain
                    $filters = [];
                    if ($cropData) {
                        $fx = $cropData['focalX'] ?? 0.5;
                        $fy = $cropData['focalY'] ?? 0.5;
                        $filters[] = "crop=ih*9/16:ih:({$fx})*iw-ih*9/32:({$fy})*ih-ih/2";
                        $filters[] = "scale={$width}:{$height}";
                    } else {
                        $filters[] = "scale={$width}:{$height}:force_original_aspect_ratio=increase";
                        $filters[] = "crop={$width}:{$height}";
                    }

                    // Apply flip transforms if requested
                    if ($videoEdit) {
                        if (!empty($videoEdit['flipH'])) {
                            $filters[] = 'hflip';
                        }
                        if (!empty($videoEdit['flipV'])) {
                            $filters[] = 'vflip';
                        }
                    }

                    $filters[] = "fps={$fps}";
                    $filters[] = "setsar=1";

                    // Compute target duration, accounting for trim
                    $duration = $clip['duration'] + ($isLastClip ? $lastSceneBuffer : 0);
                    $trimStart = 0;
                    if ($videoEdit && ($videoEdit['trimStart'] ?? 0) > 0) {
                        $trimStart = (float) $videoEdit['trimStart'];
                    }
                    $availableDuration = $duration; // How much source content is available
                    if ($videoEdit && ($videoEdit['trimEnd'] ?? 0) > 0) {
                        $trimmedDuration = (float) $videoEdit['trimEnd'] - $trimStart;
                        if ($trimmedDuration > 0 && $trimmedDuration < $duration) {
                            $availableDuration = $trimmedDuration;
                            $duration = $trimmedDuration + ($isLastClip ? $lastSceneBuffer : 0);
                        }
                    }

                    // Probe actual source clip duration to detect short clips
                    $actualClipDur = $this->getVideoDuration($clip['path']);
                    $effectiveSourceDur = $actualClipDur > 0
                        ? max(0, $actualClipDur - $trimStart)
                        : $availableDuration;

                    // If source clip is shorter than needed, loop the clip instead of
                    // freezing the last frame (which may be a black fade-out frame)
                    $needsLoop = ($effectiveSourceDur > 0 && $effectiveSourceDur < $duration - 0.5);
                    if ($needsLoop) {
                        Log::info("[StoryExport:{$jobId}] Clip {$i} shorter than scene: source={$effectiveSourceDur}s, needed={$duration}s, will loop");
                    }

                    $videoFilter = implode(',', $filters);

                    $cmd = [
                        $this->ffmpegPath,
                    ];
                    // Loop short clips so they replay instead of freezing on last frame
                    if ($needsLoop) {
                        $cmd[] = '-stream_loop';
                        $cmd[] = '-1';
                    }
                    // Seek to trim start point if set
                    if ($trimStart > 0) {
                        $cmd[] = '-ss';
                        $cmd[] = (string) $trimStart;
                    }
                    $cmd = array_merge($cmd, [
                        '-i', $clip['path'],
                        '-t', (string) $duration,
                        '-vf', $videoFilter,
                        '-c:v', 'libx264',
                        '-preset', $settings['preset'],
                        '-crf', $settings['crf'],
                        '-an',  // Strip audio (we'll add voiceover separately)
                        '-pix_fmt', 'yuv420p',
                        '-y',
                        $normalizedPath,
                    ]);
                    Log::info("[StoryExport:{$jobId}] Video clip {$i}: duration={$duration}s, trimStart={$trimStart}s, clipDur={$clip['duration']}s, actualDur={$actualClipDur}s, looped=" . ($needsLoop ? 'Y' : 'N') . ", isLast=" . ($isLastClip ? 'Y' : 'N'));
                    $this->runCommand($cmd, $jobId, "Normalize clip {$i}");
                } else {
                    // Generate Ken Burns from image
                    $duration = $clip['duration'] + ($isLastClip ? $lastSceneBuffer : 0);
                    $kb = $scenes[$i]['kenBurns'] ?? [];
                    $startScale = $kb['startScale'] ?? 1.0;
                    $endScale = $kb['endScale'] ?? 1.2;
                    $startX = $kb['startX'] ?? 0.5;
                    $startY = $kb['startY'] ?? 0.5;
                    $endX = $kb['endX'] ?? 0.5;
                    $endY = $kb['endY'] ?? 0.5;

                    // Calculate optimal zoompan scale per-image:
                    // Must be large enough that after scaling, the image fills the
                    // target frame (width x height) at minimum zoom. For landscape
                    // images in portrait output, height is the bottleneck.
                    $zoompanScale = $this->calculateZoompanScale($clip['path'], $width, $height, $startScale);

                    $zoompanFps = $settings['zoompanFps'];
                    $zoompanFrames = (int) round($duration * $zoompanFps);
                    $progressExpr = "(on/" . ($zoompanFrames - 1) . ")";
                    $zoomExpr = "{$startScale}+({$endScale}-{$startScale})*{$progressExpr}";
                    $xExpr = "({$startX}+({$endX}-{$startX})*{$progressExpr})*(iw-iw/zoom)";
                    $yExpr = "({$startY}+({$endY}-{$startY})*{$progressExpr})*(ih-ih/zoom)";

                    $filter = "scale={$zoompanScale}:-1:flags=lanczos,zoompan=z='{$zoomExpr}':x='{$xExpr}':y='{$yExpr}':d={$zoompanFrames}:s={$width}x{$height}:fps={$zoompanFps},fps={$fps},setsar=1";

                    $cmd = [
                        $this->ffmpegPath,
                        '-loop', '1',
                        '-i', $clip['path'],
                        '-vf', $filter,
                        '-t', (string) $duration,
                        '-c:v', 'libx264',
                        '-preset', $settings['preset'],
                        '-crf', $settings['crf'],
                        '-pix_fmt', 'yuv420p',
                        '-y',
                        $normalizedPath,
                    ];
                    $this->runCommand($cmd, $jobId, "Ken Burns scene {$i}");
                }

                if (file_exists($normalizedPath)) {
                    $normalizedClips[] = $normalizedPath;
                }

                $normProgress = 30 + (int) round(($i / $sceneCount) * 25);
                $this->updateProgress($progressCallback, $normProgress, 'Processing scene ' . ($i + 1) . "/{$sceneCount}...");
            }

            if (empty($normalizedClips)) {
                throw new Exception('No video clips were produced');
            }

            // Step 3: Concatenate video clips with transitions
            $this->updateProgress($progressCallback, 58, 'Joining video clips with transitions...');
            $transitions = $manifest['transitions'] ?? [];
            $concatenatedVideo = $this->concatenateWithXfade(
                $jobId,
                $normalizedClips,
                $transitions,
                $workDir,
                $output,
                $scenes
            );

            if (!file_exists($concatenatedVideo)) {
                throw new Exception('Failed to concatenate video clips');
            }
            // Step 4: Audio preparation (voiceover concat handled inside combineVideoWithAudio)
            $this->updateProgress($progressCallback, 65, 'Preparing audio...');

            // Step 5: Combine video + audio (pass fade-out duration for audio fade)
            $this->updateProgress($progressCallback, 75, 'Mixing audio...');
            $outputWithFade = array_merge($output, [
                'fadeOutDuration' => (float) ($transitions['fadeOutDuration'] ?? 0),
            ]);
            $finalVideoFile = $this->combineVideoWithAudio(
                $jobId,
                $concatenatedVideo,
                $scenes,
                $voiceoverFiles,
                null, // music file - could add later
                $music['volume'] ?? 0.15,
                $workDir,
                $outputWithFade
            );

            // Step 6: Upload
            $this->updateProgress($progressCallback, 92, 'Uploading video...');
            $result = $this->uploadToStorage(
                $jobId,
                $finalVideoFile,
                (string) ($manifest['userId'] ?? 'anonymous'),
                (string) ($manifest['projectId'] ?? $jobId)
            );

            $this->cleanupWorkDir($workDir);
            $this->updateProgress($progressCallback, 100, 'Export complete!');

            Log::info("[StoryExport:{$jobId}] Export completed", $result);
            return $result;

        } catch (Exception $e) {
            Log::error("[StoryExport:{$jobId}] Export failed", [
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

        // For artime stock media URLs, try local file path first (avoids HTTP overhead entirely)
        $localPath = $this->resolveLocalPath($url);
        if ($localPath && file_exists($localPath)) {
            copy($localPath, $outputPath);
            $fileSize = filesize($outputPath);
            Log::debug("[VideoRender:{$jobId}] Copied local file " . round($fileSize / 1024, 1) . " KB");
            return $outputPath;
        }

        // Stream directly to disk to avoid loading large files into PHP memory
        $response = Http::timeout(300)
            ->withOptions([
                'sink' => $outputPath,
                'connect_timeout' => 30,
            ])
            ->get($url);

        if ($response->failed()) {
            @unlink($outputPath);
            throw new Exception("HTTP {$response->status()}: Failed to download file from " . substr($url, 0, 80));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            @unlink($outputPath);
            throw new Exception("Download produced empty file for " . substr($url, 0, 80));
        }

        $fileSize = filesize($outputPath);
        Log::debug("[VideoRender:{$jobId}] Downloaded " . round($fileSize / 1024, 1) . " KB");

        return $outputPath;
    }

    /**
     * Try to resolve a URL to a local file path (for artime stock media on same server).
     */
    protected function resolveLocalPath(string $url): ?string
    {
        $appUrl = rtrim(config('app.url', ''), '/');
        if (empty($appUrl) || !str_starts_with($url, $appUrl)) {
            return null;
        }

        // URL path /public/stock-media/... maps directly to base_path()/public/stock-media/...
        $relativePath = substr($url, strlen($appUrl));
        $localPath = rtrim(base_path(), '/') . $relativePath;

        return $localPath;
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

            // Calculate optimal zoompan scale per-image
            $zoompanScale = $this->calculateZoompanScale($imageFile, $width, $height, $startScale);
            $progressExpr = "(on/" . ($zoompanFrames - 1) . ")";
            $zoomExpr = "{$startScale}+({$endScale}-{$startScale})*{$progressExpr}";
            $xExpr = "({$startX}+({$endX}-{$startX})*{$progressExpr})*(iw-iw/zoom)";
            $yExpr = "({$startY}+({$endY}-{$startY})*{$progressExpr})*(ih-ih/zoom)";

            $filter = "scale={$zoompanScale}:-1:flags=lanczos,zoompan=z='{$zoomExpr}':x='{$xExpr}':y='{$yExpr}':d={$zoompanFrames}:s={$width}x{$height}:fps={$zoompanFps},fps={$fps},setsar=1";

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

        // Ensure video covers the full voiceover duration (prevent -shortest from truncating audio)
        if ($voiceoverConcatFile && file_exists($voiceoverConcatFile)) {
            $voiceoverTotalDuration = $this->getAudioDuration($voiceoverConcatFile);
            if ($voiceoverTotalDuration > 0) {
                $videoFile = $this->padVideoToMinDuration(
                    $jobId, $videoFile, $voiceoverTotalDuration, $workDir, $output
                );
            }
        }

        // Build FFmpeg command
        $inputArgs = ['-i', $videoFile];
        $filterParts = [];
        $audioStream = null;

        // Determine audio fade-out duration from output config
        $audioFadeOut = (float) ($output['fadeOutDuration'] ?? 0);

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

        // Apply audio fade-out at the end of the video
        if ($audioFadeOut > 0) {
            $videoDuration = $this->getVideoDuration($videoFile);
            $afadeStart = max(0, round($videoDuration - $audioFadeOut, 3));
            $currentStream = $audioStream ?? '1:a';

            if (str_starts_with($currentStream, '[')) {
                // Already a named stream from filter_complex — chain afade onto it
                $streamName = rtrim(ltrim($currentStream, '['), ']');
                // Replace the output label of the last filter to chain afade
                $lastIdx = count($filterParts) - 1;
                if ($lastIdx >= 0) {
                    $filterParts[$lastIdx] = str_replace("[{$streamName}]", "[pre_fade]", $filterParts[$lastIdx]);
                    $filterParts[] = "[pre_fade]afade=t=out:st={$afadeStart}:d={$audioFadeOut}[afaded]";
                    $audioStream = '[afaded]';
                }
            } else {
                // Raw stream reference — wrap in a filter
                $filterParts[] = "[{$currentStream}]afade=t=out:st={$afadeStart}:d={$audioFadeOut}[afaded]";
                $audioStream = '[afaded]';
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

                if ($remainingTime < -0.5) {
                    Log::info("[VideoRender:{$jobId}] Scene {$i} voiceover overflow: voice={$voiceDuration}s, scene={$sceneDuration}s, overflow=" . abs($remainingTime) . "s");
                }

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
     * Get video duration using ffprobe.
     *
     * @param string $filePath Path to video file
     * @return float Duration in seconds
     */
    public function getVideoDuration(string $filePath): float
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
     * Concatenate video clips with xfade crossfade transitions and final fade-out.
     *
     * For N clips, builds N-1 xfade operations chained together.
     * Falls back to simple concat if only 1 clip or crossfade is disabled.
     *
     * @param string $jobId Job identifier for logging
     * @param array $clipPaths Array of normalized video file paths
     * @param array $transitions Transition config: type, crossfadeDuration, fadeOutDuration
     * @param string $workDir Working directory
     * @param array $output Output settings (quality, etc.)
     * @return string Path to the concatenated video file
     */
    protected function concatenateWithXfade(
        string $jobId,
        array $clipPaths,
        array $transitions,
        string $workDir,
        array $output,
        array $scenes = []
    ): string {
        $crossfadeDuration = (float) ($transitions['crossfadeDuration'] ?? 0.5);
        $fadeOutDuration = (float) ($transitions['fadeOutDuration'] ?? 1.5);
        $transitionType = $transitions['type'] ?? 'fade';
        $outputFile = "{$workDir}/concatenated.mp4";

        // Fall back to simple concat if only 1 clip or crossfade disabled
        if (count($clipPaths) <= 1 || $crossfadeDuration <= 0 || $transitionType === 'none') {
            $concatListFile = "{$workDir}/concat_list.txt";
            $concatContent = array_map(fn($p) => "file '{$p}'", $clipPaths);
            file_put_contents($concatListFile, implode("\n", $concatContent));

            $cmd = [
                $this->ffmpegPath,
                '-f', 'concat',
                '-safe', '0',
                '-i', $concatListFile,
                '-c', 'copy',
                '-movflags', '+faststart',
                '-y',
                $outputFile,
            ];
            $this->runCommand($cmd, $jobId, 'Simple Concat');

            // Add fade-out even with simple concat if requested
            if ($fadeOutDuration > 0 && file_exists($outputFile)) {
                $outputFile = $this->applyFadeOut($jobId, $outputFile, $fadeOutDuration, $workDir, $output);
            }

            return $outputFile;
        }

        // Probe actual durations for each clip (essential for correct xfade offsets)
        $durations = [];
        foreach ($clipPaths as $i => $clipPath) {
            $durations[$i] = $this->getVideoDuration($clipPath);
            Log::debug("[StoryExport:{$jobId}] Clip {$i} actual duration: {$durations[$i]}s");
        }

        // Build xfade filter chain
        // For N clips: N-1 xfade operations
        // offset_i = sum(d0..di) - (i+1) * crossfadeDuration
        $clipCount = count($clipPaths);
        $filterParts = [];
        $runningDuration = $durations[0];

        // Build inputs
        $inputArgs = [];
        foreach ($clipPaths as $clipPath) {
            $inputArgs[] = '-i';
            $inputArgs[] = $clipPath;
        }

        for ($i = 0; $i < $clipCount - 1; $i++) {
            // Use per-scene transition if available, otherwise fall back to global
            $sceneTransType = $scenes[$i + 1]['transition_type'] ?? $transitionType;
            $sceneTransDuration = (float) ($scenes[$i + 1]['transition_duration'] ?? $crossfadeDuration);

            $offset = $runningDuration - $sceneTransDuration;
            $offset = max(0, round($offset, 3));

            $inputLabel = ($i === 0) ? '[0:v]' : "[v{$i}]";
            $nextInput = '[' . ($i + 1) . ':v]';

            $isLast = ($i === $clipCount - 2);
            $outputLabel = $isLast ? '[vout]' : "[v" . ($i + 1) . "]";

            $xfadePart = "{$inputLabel}{$nextInput}xfade=transition={$sceneTransType}:duration={$sceneTransDuration}:offset={$offset}";

            // Add final fade-out on the last xfade operation
            if ($isLast && $fadeOutDuration > 0) {
                $totalDuration = $offset + $durations[$i + 1];
                $fadeStart = max(0, round($totalDuration - $fadeOutDuration, 3));
                $xfadePart .= ",fade=t=out:st={$fadeStart}:d={$fadeOutDuration}";
            }

            $xfadePart .= $outputLabel;
            $filterParts[] = $xfadePart;

            $nextIdx = $i + 1;
            Log::debug("[StoryExport:{$jobId}] Scene {$i}→{$nextIdx}: {$sceneTransType} ({$sceneTransDuration}s)", [
                'offset' => $offset,
            ]);

            // Update running duration: after xfade, the combined duration is
            // previous_combined + next_clip_duration - crossfade_overlap
            $runningDuration = $offset + $durations[$i + 1];
        }

        $filterComplex = implode(';', $filterParts);

        $renderQuality = $output['renderQuality'] ?? $output['quality'] ?? 'balanced';
        $settings = $this->qualitySettings[$renderQuality] ?? $this->qualitySettings['balanced'];

        $cmd = array_merge(
            [$this->ffmpegPath],
            $inputArgs,
            [
                '-filter_complex', $filterComplex,
                '-map', '[vout]',
                '-c:v', 'libx264',
                '-preset', $settings['preset'],
                '-crf', $settings['crf'],
                '-pix_fmt', 'yuv420p',
                '-movflags', '+faststart',
                '-y',
                $outputFile,
            ]
        );

        Log::info("[StoryExport:{$jobId}] Building xfade filter with per-scene transitions, " .
            "fallback={$transitionType}, {$fadeOutDuration}s fade-out");

        $this->runCommand($cmd, $jobId, 'Xfade Concat');

        if (!file_exists($outputFile)) {
            throw new Exception('Failed to concatenate video clips with xfade');
        }

        return $outputFile;
    }

    /**
     * Apply fade-out effect to a video file (used when simple concat is used).
     */
    protected function applyFadeOut(
        string $jobId,
        string $inputFile,
        float $fadeOutDuration,
        string $workDir,
        array $output
    ): string {
        $outputFile = "{$workDir}/concat_faded.mp4";
        $totalDuration = $this->getVideoDuration($inputFile);
        $fadeStart = max(0, round($totalDuration - $fadeOutDuration, 3));

        $renderQuality = $output['renderQuality'] ?? $output['quality'] ?? 'balanced';
        $settings = $this->qualitySettings[$renderQuality] ?? $this->qualitySettings['balanced'];

        $cmd = [
            $this->ffmpegPath,
            '-i', $inputFile,
            '-vf', "fade=t=out:st={$fadeStart}:d={$fadeOutDuration}",
            '-c:v', 'libx264',
            '-preset', $settings['preset'],
            '-crf', $settings['crf'],
            '-pix_fmt', 'yuv420p',
            '-an',
            '-movflags', '+faststart',
            '-y',
            $outputFile,
        ];

        $this->runCommand($cmd, $jobId, 'Fade Out');

        if (file_exists($outputFile)) {
            return $outputFile;
        }

        // Fall back to original if fade-out fails
        Log::warning("[StoryExport:{$jobId}] Fade-out failed, using unfaded video");
        return $inputFile;
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
     * Ensure video is at least as long as the target duration.
     * If shorter, loops the video to fill the gap (better than freeze-frame).
     */
    protected function padVideoToMinDuration(
        string $jobId,
        string $videoFile,
        float $minDuration,
        string $workDir,
        array $output
    ): string {
        $videoDuration = $this->getVideoDuration($videoFile);

        if ($videoDuration >= $minDuration - 0.1) {
            return $videoFile; // Already long enough
        }

        $targetDuration = $minDuration + 1.0; // +1s safety
        Log::info("[VideoRender:{$jobId}] Padding video: {$videoDuration}s -> {$targetDuration}s (looping)");

        $paddedVideo = "{$workDir}/padded_video.mp4";
        $renderQuality = $output['renderQuality'] ?? $output['quality'] ?? 'balanced';
        $settings = $this->qualitySettings[$renderQuality] ?? $this->qualitySettings['balanced'];

        // Use stream_loop to loop the video, then cap at target duration
        $cmd = [
            $this->ffmpegPath,
            '-stream_loop', '-1',
            '-i', $videoFile,
            '-t', (string) round($targetDuration, 2),
            '-c:v', 'libx264',
            '-preset', $settings['preset'],
            '-crf', $settings['crf'],
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            '-an',
            '-y',
            $paddedVideo,
        ];

        $this->runCommand($cmd, $jobId, 'Video Padding');

        if (file_exists($paddedVideo) && filesize($paddedVideo) > 0) {
            return $paddedVideo;
        }

        Log::warning("[VideoRender:{$jobId}] Video padding failed, using original");
        return $videoFile;
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

        $publicUrl = null;

        // Try Google Cloud Storage first
        if (config('filesystems.disks.gcs.bucket')) {
            try {
                Storage::disk('gcs')->put($fileName, file_get_contents($filePath), 'public');
                $publicUrl = "https://storage.googleapis.com/{$this->bucketName}/{$fileName}";
            } catch (\Throwable $e) {
                Log::warning("[VideoRender:{$jobId}] GCS upload failed, falling back to public disk: " . $e->getMessage());
            }
        }

        // Fallback to public disk
        if (!$publicUrl) {
            try {
                Storage::disk('public')->put($fileName, file_get_contents($filePath));
                $publicUrl = url('/files/' . $fileName);
            } catch (Exception $e) {
                Log::error("[VideoRender:{$jobId}] Upload failed: " . $e->getMessage());
                throw $e;
            }
        }

        Log::info("[VideoRender:{$jobId}] Upload completed: {$publicUrl}");

        return [
            'outputUrl' => $publicUrl,
            'outputPath' => $fileName,
            'outputSize' => $fileSize,
        ];
    }

    /**
     * Calculate optimal zoompan pre-scale width for a source image.
     *
     * Ensures the scaled image is large enough to fill the target frame
     * (width x height) at minimum zoom. For landscape images in portrait
     * output, the height is the bottleneck — we must scale wide enough
     * that the proportional height covers the target.
     *
     * Returns just enough resolution for quality + fill, staying fast.
     */
    protected function calculateZoompanScale(string $imagePath, int $targetW, int $targetH, float $minZoom = 1.0): int
    {
        // Baseline: 2.5x output width for pan headroom + sharpness
        $baseScale = (int) max($targetW * 2.5, $targetH * 1.5);

        // Try to read source image dimensions
        $imgSize = @getimagesize($imagePath);
        if (!$imgSize || $imgSize[0] <= 0 || $imgSize[1] <= 0) {
            return $baseScale;
        }

        $srcW = $imgSize[0];
        $srcH = $imgSize[1];
        $srcAspect = $srcW / $srcH; // e.g. 1.78 for 16:9

        // After `scale=S:-1`, the image becomes S x (S / srcAspect).
        // Zoompan at zoom=Z extracts a (targetW/Z) x (targetH/Z) window.
        // For the image to fill the frame: scaledH >= targetH / minZoom
        // => (S / srcAspect) >= targetH / minZoom
        // => S >= (targetH / minZoom) * srcAspect
        $minScaleForFill = (int) ceil(($targetH / max($minZoom, 0.5)) * $srcAspect * 1.1); // 10% margin

        return max($baseScale, $minScaleForFill);
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

        // Redirect stderr to a temp file to avoid pipe deadlock.
        // Reading stdout then stderr via pipes can deadlock when ffmpeg
        // fills the 64KB stderr buffer while PHP blocks on stdout.
        $stderrFile = tempnam(sys_get_temp_dir(), 'ffmpeg_err_');
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', $stderrFile, 'w'],
        ];

        $process = proc_open($cmdString, $descriptors, $pipes);

        if (!is_resource($process)) {
            @unlink($stderrFile);
            throw new Exception("Failed to start FFmpeg process");
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $exitCode = proc_close($process);
        $stderr = file_get_contents($stderrFile);
        @unlink($stderrFile);

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
