<?php

namespace Modules\AppAITools\Services;

class PlatformDataService
{
    protected YouTubeDataService $youtube;

    public function __construct(YouTubeDataService $youtube)
    {
        $this->youtube = $youtube;
    }

    /**
     * Detect platform from URL and return normalized video data.
     */
    public function analyzePlatformUrl(string $url): array
    {
        $platform = $this->detectPlatform($url);

        switch ($platform) {
            case 'youtube':
                $data = $this->youtube->getVideoData($url);
                if (!$data) {
                    throw new \Exception('Could not fetch video data from YouTube.');
                }
                return array_merge($data, ['platform' => 'youtube']);

            case 'tiktok':
            case 'instagram':
            case 'linkedin':
                // For non-YouTube platforms, return URL info only (API not implemented yet)
                return [
                    'platform' => $platform,
                    'url' => $url,
                    'title' => '',
                    'description' => '',
                    'tags' => [],
                    'views' => 0,
                    'likes' => 0,
                    'comments' => 0,
                ];

            default:
                throw new \Exception("Unsupported platform. Please enter a YouTube, TikTok, Instagram, or LinkedIn URL.");
        }
    }

    /**
     * Detect platform from URL.
     */
    public function detectPlatform(string $url): string
    {
        $url = strtolower($url);

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'tiktok.com')) {
            return 'tiktok';
        }
        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }
        if (str_contains($url, 'linkedin.com')) {
            return 'linkedin';
        }

        return 'general';
    }

    /**
     * Get channel data for the detected platform.
     */
    public function getChannelData(string $url, string $platform = ''): ?array
    {
        $platform = $platform ?: $this->detectPlatform($url);

        if ($platform === 'youtube') {
            return $this->youtube->getChannelData($url);
        }

        // Other platforms not yet supported
        return null;
    }
}
