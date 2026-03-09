<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * VideoUtilService - Static utility methods for video/audio processing via ffmpeg.
 *
 * Provides reusable video manipulation utilities
 * (frame extraction, trimming, concatenation, silent WAV generation).
 */
class VideoUtilService
{
    /**
     * Generate raw bytes for a silent WAV file.
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

        $header = pack('A4VA4', 'RIFF', 36 + $dataSize, 'WAVE');
        $fmt = pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample);
        $samples = str_repeat("\x00\x00", $numSamples);
        $data = pack('A4V', 'data', $dataSize) . $samples;

        return $header . $fmt . $data;
    }

    /**
     * Generate a silent WAV file, save to public storage, and return its URL.
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
     * Find the ffmpeg binary on the system.
     */
    protected static function findFfmpeg(): ?string
    {
        foreach (['/home/artime/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Resolve a video URL to a local disk path or return the URL for remote use.
     */
    protected static function resolveVideoInput(string $videoUrl): ?string
    {
        $parsed = parse_url($videoUrl);
        $urlPath = $parsed['path'] ?? '';
        if (str_starts_with($urlPath, '/files/')) {
            $storagePath = substr($urlPath, 7);
            $diskPath = Storage::disk('public')->path($storagePath);
            if (file_exists($diskPath)) {
                return $diskPath;
            }
        }
        if (str_starts_with($videoUrl, 'http://') || str_starts_with($videoUrl, 'https://')) {
            return $videoUrl;
        }
        return null;
    }

    /**
     * Download a remote video to a local temp file for reliable FFmpeg seeking.
     * Returns the temp file path, or the original path if already local.
     * Caller must clean up temp files.
     */
    protected static function ensureLocalFile(string $videoInput): array
    {
        // Already a local file
        if (!str_starts_with($videoInput, 'http://') && !str_starts_with($videoInput, 'https://')) {
            return ['path' => $videoInput, 'temp' => false];
        }

        $tempPath = sys_get_temp_dir() . '/vutil_' . md5($videoInput) . '_' . uniqid() . '.mp4';

        try {
            $ch = curl_init($videoInput);
            $fp = fopen($tempPath, 'wb');
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if ($httpCode >= 200 && $httpCode < 300 && file_exists($tempPath) && filesize($tempPath) > 0) {
                return ['path' => $tempPath, 'temp' => true];
            }
        } catch (\Throwable $e) {
            Log::warning('VideoUtil: failed to download remote video for local seeking', ['error' => $e->getMessage()]);
        }

        @unlink($tempPath);
        // Fallback: return the URL and let FFmpeg try directly
        return ['path' => $videoInput, 'temp' => false];
    }

    /**
     * Extract a frame from a video at a specific timestamp.
     */
    public static function extractFrameAtTimestamp(string $videoUrl, float $timestamp, int $projectId): ?string
    {
        $tempInfo = null;

        try {
            $ffmpeg = self::findFfmpeg();
            if (!$ffmpeg) {
                Log::warning('VideoUtil: ffmpeg not found for frame extraction');
                return null;
            }

            $videoInput = self::resolveVideoInput($videoUrl);
            if (!$videoInput) {
                Log::error('VideoUtil: cannot resolve video for frame extraction', ['url' => substr($videoUrl, 0, 80)]);
                return null;
            }

            // Download remote videos locally for reliable seeking
            $tempInfo = self::ensureLocalFile($videoInput);
            $localInput = $tempInfo['path'];

            $frameFilename = 'extend_frame_' . str_replace('.', '_', (string) $timestamp) . '_' . uniqid() . '.png';
            $frameStoragePath = "wizard-videos/{$projectId}/{$frameFilename}";
            $frameDiskPath = Storage::disk('public')->path($frameStoragePath);

            $outputDir = dirname($frameDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Probe video duration to clamp timestamp
            $probeCmd = sprintf(
                '%s -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
                escapeshellarg(str_replace('ffmpeg', 'ffprobe', $ffmpeg)),
                escapeshellarg($localInput)
            );
            $probeOutput = [];
            exec($probeCmd, $probeOutput);
            $videoDuration = (float) trim($probeOutput[0] ?? '0');

            if ($videoDuration > 0 && $timestamp >= $videoDuration - 0.05) {
                $timestamp = max(0, $videoDuration - 0.1);
            }

            // Use output seeking (-ss after -i) for accurate frame extraction.
            // Input seeking (-ss before -i) is faster but unreliable on AI-generated
            // videos with sparse keyframes (Seedance, Kling, etc.) — often returns frame 0.
            $cmd = sprintf(
                '%s -i %s -ss %s -frames:v 1 -update 1 %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($localInput),
                escapeshellarg(number_format($timestamp, 3, '.', '')),
                escapeshellarg($frameDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if (!file_exists($frameDiskPath) || filesize($frameDiskPath) === 0) {
                @unlink($frameDiskPath);
                $retryCmd = sprintf(
                    '%s -sseof -0.1 -i %s -frames:v 1 -update 1 %s -y 2>&1',
                    escapeshellarg($ffmpeg),
                    escapeshellarg($localInput),
                    escapeshellarg($frameDiskPath)
                );
                exec($retryCmd, $output, $returnCode);
            }

            // Clean up temp file
            if ($tempInfo['temp']) {
                @unlink($tempInfo['path']);
            }

            if ($returnCode !== 0 || !file_exists($frameDiskPath) || filesize($frameDiskPath) === 0) {
                Log::error('VideoUtil: frame extraction at timestamp failed', [
                    'timestamp' => $timestamp,
                    'returnCode' => $returnCode,
                ]);
                return null;
            }

            return url('/files/' . $frameStoragePath);

        } catch (\Throwable $e) {
            // Clean up temp file on error
            if ($tempInfo && $tempInfo['temp']) {
                @unlink($tempInfo['path']);
            }
            Log::error('VideoUtil: frame extraction error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Trim a video to end at a specific timestamp (lossless copy).
     */
    public static function trimVideoToTimestamp(string $videoUrl, float $timestamp, int $projectId): ?string
    {
        try {
            $ffmpeg = self::findFfmpeg();
            if (!$ffmpeg) {
                Log::warning('VideoUtil: ffmpeg not found for video trimming');
                return null;
            }

            $videoInput = self::resolveVideoInput($videoUrl);
            if (!$videoInput) {
                Log::error('VideoUtil: cannot resolve video for trimming', ['url' => substr($videoUrl, 0, 80)]);
                return null;
            }

            $outputFilename = 'trimmed_' . str_replace('.', '_', (string) $timestamp) . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $cmd = sprintf(
                '%s -i %s -t %s -c copy %s -y 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($videoInput),
                escapeshellarg(number_format($timestamp, 3, '.', '')),
                escapeshellarg($outputDiskPath)
            );

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputDiskPath)) {
                Log::error('VideoUtil: video trimming failed', [
                    'timestamp' => $timestamp,
                    'returnCode' => $returnCode,
                ]);
                return null;
            }

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('VideoUtil: video trimming error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Concatenate two videos (lossless).
     */
    public static function concatenateVideos(string $videoUrl1, string $videoUrl2, int $projectId): ?string
    {
        try {
            $ffmpeg = self::findFfmpeg();
            if (!$ffmpeg) {
                Log::warning('VideoUtil: ffmpeg not found for video concatenation');
                return null;
            }

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

            $videoPath1 = $resolveVideoPath($videoUrl1);
            $videoPath2 = $resolveVideoPath($videoUrl2);

            if (!$videoPath1 || !$videoPath2) {
                Log::error('VideoUtil: video file(s) not found for concat');
                return null;
            }

            $outputFilename = 'concat_' . time() . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $concatListPath = $outputDiskPath . '.concat.txt';
            $concatContent = "file " . escapeshellarg($videoPath1) . "\nfile " . escapeshellarg($videoPath2);
            file_put_contents($concatListPath, $concatContent);

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
                Log::error('VideoUtil: concat failed', ['returnCode' => $returnCode]);
                return null;
            }

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('VideoUtil: concat error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Concatenate multiple video URLs into a single video (lossless).
     */
    public static function concatenateMultipleVideos(array $videoUrls, int $projectId): ?string
    {
        if (count($videoUrls) < 2) {
            return $videoUrls[0] ?? null;
        }

        if (count($videoUrls) === 2) {
            return self::concatenateVideos($videoUrls[0], $videoUrls[1], $projectId);
        }

        try {
            $ffmpeg = self::findFfmpeg();
            if (!$ffmpeg) {
                Log::warning('VideoUtil: ffmpeg not found for multi-video concatenation');
                return null;
            }

            $videoPaths = [];
            foreach ($videoUrls as $i => $url) {
                $path = self::resolveVideoInput($url);
                if (!$path) {
                    Log::error('VideoUtil: multi-concat video not resolvable', ['index' => $i]);
                    return null;
                }
                $videoPaths[] = $path;
            }

            $outputFilename = 'extended_' . time() . '_' . uniqid() . '.mp4';
            $outputStoragePath = "wizard-videos/{$projectId}/{$outputFilename}";
            $outputDiskPath = Storage::disk('public')->path($outputStoragePath);

            $outputDir = dirname($outputDiskPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $concatListPath = $outputDiskPath . '.concat.txt';
            $concatLines = array_map(fn($p) => "file " . escapeshellarg($p), $videoPaths);
            file_put_contents($concatListPath, implode("\n", $concatLines));

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
                Log::error('VideoUtil: multi-video concatenation failed', ['returnCode' => $returnCode]);
                return null;
            }

            return url('/files/' . $outputStoragePath);

        } catch (\Throwable $e) {
            Log::error('VideoUtil: multi-video concat error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
