<?php

namespace Modules\AppAITools\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeDataService
{
    protected const API_BASE = 'https://www.googleapis.com/youtube/v3';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the next API key using rotation.
     */
    public function getNextApiKey(): string
    {
        $keys = json_decode(get_option('creator_hub_youtube_api_keys', '[]'), true);
        $activeKeys = array_values(array_filter($keys, fn($k) => $k['active'] ?? false));

        if (empty($activeKeys)) {
            throw new \Exception('No YouTube API keys configured. Please add keys in Admin > Creator Hub Settings.');
        }

        $mode = get_option('creator_hub_youtube_rotation_mode', 'round-robin');

        if ($mode === 'random') {
            return $activeKeys[array_rand($activeKeys)]['key'];
        }

        // Round-robin
        $index = (int) get_option('creator_hub_youtube_api_key_index', 0);
        $key = $activeKeys[$index % count($activeKeys)];
        update_option('creator_hub_youtube_api_key_index', ($index + 1) % count($activeKeys));

        return $key['key'];
    }

    /**
     * Make an API call with automatic key rotation on quota errors.
     */
    protected function apiCall(string $endpoint, array $params = []): array
    {
        $keys = json_decode(get_option('creator_hub_youtube_api_keys', '[]'), true);
        $activeKeys = array_values(array_filter($keys, fn($k) => $k['active'] ?? false));
        $attempts = count($activeKeys);

        for ($i = 0; $i < $attempts; $i++) {
            $apiKey = $this->getNextApiKey();
            $params['key'] = $apiKey;

            try {
                $response = Http::timeout(15)->get(self::API_BASE . '/' . $endpoint, $params);

                if ($response->successful()) {
                    return $response->json();
                }

                $error = $response->json('error', []);
                $code = $error['code'] ?? $response->status();

                // Quota exceeded - try next key
                if ($code === 403 && str_contains($error['message'] ?? '', 'quota')) {
                    Log::warning("YouTube API quota exceeded, rotating to next key", ['attempt' => $i + 1]);
                    continue;
                }

                throw new \Exception($error['message'] ?? "YouTube API error (HTTP {$code})");
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new \Exception("YouTube API connection failed: {$e->getMessage()}");
            }
        }

        throw new \Exception('All YouTube API keys have exceeded their daily quota.');
    }

    /**
     * Extract video ID from various YouTube URL formats.
     */
    public function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/',
            '/^([a-zA-Z0-9_-]{11})$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract channel identifier from various URL formats.
     */
    public function extractChannelId(string $url): ?array
    {
        // @handle format
        if (preg_match('/youtube\.com\/@([a-zA-Z0-9_.-]+)/', $url, $matches)) {
            return ['type' => 'handle', 'value' => $matches[1]];
        }
        // /channel/ format
        if (preg_match('/youtube\.com\/channel\/(UC[a-zA-Z0-9_-]+)/', $url, $matches)) {
            return ['type' => 'id', 'value' => $matches[1]];
        }
        // /c/ custom URL
        if (preg_match('/youtube\.com\/c\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return ['type' => 'custom', 'value' => $matches[1]];
        }
        // /user/ format
        if (preg_match('/youtube\.com\/user\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return ['type' => 'user', 'value' => $matches[1]];
        }

        return null;
    }

    /**
     * Get video data by URL or ID.
     */
    public function getVideoData(string $videoIdOrUrl): ?array
    {
        $videoId = $this->extractVideoId($videoIdOrUrl) ?? $videoIdOrUrl;

        return Cache::remember("yt_video_{$videoId}", self::CACHE_TTL, function () use ($videoId) {
            $data = $this->apiCall('videos', [
                'part' => 'snippet,statistics,contentDetails',
                'id' => $videoId,
            ]);

            if (empty($data['items'])) {
                return null;
            }

            $item = $data['items'][0];
            $snippet = $item['snippet'] ?? [];
            $stats = $item['statistics'] ?? [];
            $content = $item['contentDetails'] ?? [];

            return [
                'id' => $videoId,
                'title' => $snippet['title'] ?? '',
                'description' => $snippet['description'] ?? '',
                'channel' => $snippet['channelTitle'] ?? '',
                'channel_id' => $snippet['channelId'] ?? '',
                'published_at' => $snippet['publishedAt'] ?? '',
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? '',
                'tags' => $snippet['tags'] ?? [],
                'category_id' => $snippet['categoryId'] ?? '',
                'views' => (int) ($stats['viewCount'] ?? 0),
                'likes' => (int) ($stats['likeCount'] ?? 0),
                'comments' => (int) ($stats['commentCount'] ?? 0),
                'duration' => $content['duration'] ?? '',
                'definition' => $content['definition'] ?? '',
            ];
        });
    }

    /**
     * Get channel data.
     */
    public function getChannelData(string $channelUrl): ?array
    {
        $identifier = $this->extractChannelId($channelUrl);
        if (!$identifier) {
            throw new \Exception('Could not parse YouTube channel URL.');
        }

        $cacheKey = "yt_channel_{$identifier['type']}_{$identifier['value']}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($identifier) {
            $params = ['part' => 'snippet,statistics,contentDetails'];

            switch ($identifier['type']) {
                case 'id':
                    $params['id'] = $identifier['value'];
                    break;
                case 'handle':
                    $params['forHandle'] = $identifier['value'];
                    break;
                case 'user':
                    $params['forUsername'] = $identifier['value'];
                    break;
                case 'custom':
                    // Search by custom URL name
                    $searchResult = $this->apiCall('search', [
                        'part' => 'snippet',
                        'q' => $identifier['value'],
                        'type' => 'channel',
                        'maxResults' => 1,
                    ]);
                    if (!empty($searchResult['items'])) {
                        $params['id'] = $searchResult['items'][0]['snippet']['channelId'] ?? '';
                    } else {
                        return null;
                    }
                    break;
            }

            $data = $this->apiCall('channels', $params);

            if (empty($data['items'])) {
                return null;
            }

            $item = $data['items'][0];
            $snippet = $item['snippet'] ?? [];
            $stats = $item['statistics'] ?? [];

            return [
                'id' => $item['id'] ?? '',
                'title' => $snippet['title'] ?? '',
                'description' => $snippet['description'] ?? '',
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? '',
                'country' => $snippet['country'] ?? '',
                'published_at' => $snippet['publishedAt'] ?? '',
                'subscribers' => (int) ($stats['subscriberCount'] ?? 0),
                'total_views' => (int) ($stats['viewCount'] ?? 0),
                'video_count' => (int) ($stats['videoCount'] ?? 0),
                'hidden_subscriber_count' => $stats['hiddenSubscriberCount'] ?? false,
            ];
        });
    }

    /**
     * Search videos.
     */
    public function searchVideos(string $query, array $options = []): array
    {
        $params = [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $options['maxResults'] ?? 10,
            'order' => $options['order'] ?? 'relevance',
            'regionCode' => $options['regionCode'] ?? 'US',
        ];

        if (isset($options['publishedAfter'])) {
            $params['publishedAfter'] = $options['publishedAfter'];
        }

        $data = $this->apiCall('search', $params);

        return array_map(function ($item) {
            $snippet = $item['snippet'] ?? [];
            return [
                'id' => $item['id']['videoId'] ?? '',
                'title' => $snippet['title'] ?? '',
                'description' => $snippet['description'] ?? '',
                'channel' => $snippet['channelTitle'] ?? '',
                'published_at' => $snippet['publishedAt'] ?? '',
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? '',
            ];
        }, $data['items'] ?? []);
    }

    /**
     * Extract playlist ID from a YouTube playlist URL.
     */
    public function extractPlaylistId(string $url): ?string
    {
        if (preg_match('/[?&]list=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get videos from a playlist.
     */
    public function getPlaylistVideos(string $playlistUrl, int $limit = 10): array
    {
        $playlistId = $this->extractPlaylistId($playlistUrl);
        if (!$playlistId) {
            throw new \Exception('Could not parse playlist URL. Please provide a valid YouTube playlist URL.');
        }

        $cacheKey = "yt_playlist_{$playlistId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($playlistId, $limit) {
            $data = $this->apiCall('playlistItems', [
                'part' => 'snippet',
                'playlistId' => $playlistId,
                'maxResults' => min($limit, 50),
            ]);

            $videoIds = array_filter(array_map(
                fn($item) => $item['snippet']['resourceId']['videoId'] ?? null,
                $data['items'] ?? []
            ));

            if (empty($videoIds)) {
                return [];
            }

            $videoData = $this->apiCall('videos', [
                'part' => 'snippet,statistics,contentDetails',
                'id' => implode(',', $videoIds),
            ]);

            return array_map(function ($item) {
                $snippet = $item['snippet'] ?? [];
                $stats = $item['statistics'] ?? [];
                return [
                    'id' => $item['id'] ?? '',
                    'title' => $snippet['title'] ?? '',
                    'description' => $snippet['description'] ?? '',
                    'channel' => $snippet['channelTitle'] ?? '',
                    'channel_id' => $snippet['channelId'] ?? '',
                    'published_at' => $snippet['publishedAt'] ?? '',
                    'thumbnail' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? '',
                    'tags' => $snippet['tags'] ?? [],
                    'views' => (int) ($stats['viewCount'] ?? 0),
                    'likes' => (int) ($stats['likeCount'] ?? 0),
                    'comments' => (int) ($stats['commentCount'] ?? 0),
                    'duration' => $item['contentDetails']['duration'] ?? '',
                ];
            }, $videoData['items'] ?? []);
        });
    }

    /**
     * Get recent videos from a channel.
     */
    public function getChannelVideos(string $channelId, int $limit = 20): array
    {
        $cacheKey = "yt_channel_videos_{$channelId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($channelId, $limit) {
            $searchData = $this->apiCall('search', [
                'part' => 'snippet',
                'channelId' => $channelId,
                'type' => 'video',
                'order' => 'date',
                'maxResults' => min($limit, 50),
            ]);

            $videoIds = array_filter(array_map(
                fn($item) => $item['id']['videoId'] ?? null,
                $searchData['items'] ?? []
            ));

            if (empty($videoIds)) {
                return [];
            }

            $videoData = $this->apiCall('videos', [
                'part' => 'snippet,statistics,contentDetails',
                'id' => implode(',', $videoIds),
            ]);

            return array_map(function ($item) {
                $snippet = $item['snippet'] ?? [];
                $stats = $item['statistics'] ?? [];
                return [
                    'id' => $item['id'] ?? '',
                    'title' => $snippet['title'] ?? '',
                    'views' => (int) ($stats['viewCount'] ?? 0),
                    'likes' => (int) ($stats['likeCount'] ?? 0),
                    'comments' => (int) ($stats['commentCount'] ?? 0),
                    'duration' => $item['contentDetails']['duration'] ?? '',
                    'published_at' => $snippet['publishedAt'] ?? '',
                    'thumbnail' => $snippet['thumbnails']['high']['url'] ?? '',
                ];
            }, $videoData['items'] ?? []);
        });
    }
}
