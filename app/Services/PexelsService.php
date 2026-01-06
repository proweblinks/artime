<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ClientException;

class PexelsService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) get_option("media_pexels_api_key", "");
        $this->client = new Client([
            'base_uri' => 'https://api.pexels.com/',
            'headers' => [
                'Authorization' => $this->apiKey,
            ],
        ]);
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && get_option('media_pexels_status', 1) == 1;
    }

    /**
     * Search for videos.
     */
    public function searchVideos(string $query, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API not configured');
        }

        $cacheKey = 'pexels_videos_' . md5($query . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($query, $options) {
            try {
                $response = $this->client->request('GET', 'videos/search', [
                    'query' => [
                        'query' => $query,
                        'per_page' => $options['per_page'] ?? 15,
                        'page' => $options['page'] ?? 1,
                        'orientation' => $options['orientation'] ?? null, // landscape, portrait, square
                        'size' => $options['size'] ?? null, // large, medium, small
                    ],
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);

                return [
                    'success' => true,
                    'data' => $this->formatVideos($body['videos'] ?? []),
                    'total' => $body['total_results'] ?? 0,
                    'page' => $body['page'] ?? 1,
                    'per_page' => $body['per_page'] ?? 15,
                    'next_page' => $body['next_page'] ?? null,
                ];

            } catch (\Throwable $e) {
                Log::error("Pexels video search failed: " . $e->getMessage());
                return $this->errorResponse($e->getMessage());
            }
        });
    }

    /**
     * Search for photos.
     */
    public function searchPhotos(string $query, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API not configured');
        }

        $cacheKey = 'pexels_photos_' . md5($query . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($query, $options) {
            try {
                $response = $this->client->request('GET', 'v1/search', [
                    'query' => [
                        'query' => $query,
                        'per_page' => $options['per_page'] ?? 15,
                        'page' => $options['page'] ?? 1,
                        'orientation' => $options['orientation'] ?? null,
                        'size' => $options['size'] ?? null,
                        'color' => $options['color'] ?? null,
                    ],
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);

                return [
                    'success' => true,
                    'data' => $this->formatPhotos($body['photos'] ?? []),
                    'total' => $body['total_results'] ?? 0,
                    'page' => $body['page'] ?? 1,
                    'per_page' => $body['per_page'] ?? 15,
                    'next_page' => $body['next_page'] ?? null,
                ];

            } catch (\Throwable $e) {
                Log::error("Pexels photo search failed: " . $e->getMessage());
                return $this->errorResponse($e->getMessage());
            }
        });
    }

    /**
     * Get a specific video by ID.
     */
    public function getVideo(int $id): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API not configured');
        }

        try {
            $response = $this->client->request('GET', "videos/videos/{$id}", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $this->formatVideo($body),
            ];

        } catch (\Throwable $e) {
            Log::error("Pexels get video failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get a specific photo by ID.
     */
    public function getPhoto(int $id): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API not configured');
        }

        try {
            $response = $this->client->request('GET', "v1/photos/{$id}", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $this->formatPhoto($body),
            ];

        } catch (\Throwable $e) {
            Log::error("Pexels get photo failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get popular videos.
     */
    public function getPopularVideos(array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API not configured');
        }

        try {
            $response = $this->client->request('GET', 'videos/popular', [
                'query' => [
                    'per_page' => $options['per_page'] ?? 15,
                    'page' => $options['page'] ?? 1,
                ],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $this->formatVideos($body['videos'] ?? []),
                'total' => $body['total_results'] ?? 0,
                'page' => $body['page'] ?? 1,
            ];

        } catch (\Throwable $e) {
            Log::error("Pexels popular videos failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Format videos array.
     */
    protected function formatVideos(array $videos): array
    {
        return array_map([$this, 'formatVideo'], $videos);
    }

    /**
     * Format single video.
     */
    protected function formatVideo(array $video): array
    {
        // Get best quality video file
        $videoFiles = $video['video_files'] ?? [];
        $hdFile = collect($videoFiles)->firstWhere('quality', 'hd') ?? $videoFiles[0] ?? null;
        $sdFile = collect($videoFiles)->firstWhere('quality', 'sd') ?? $videoFiles[0] ?? null;

        return [
            'id' => $video['id'],
            'source' => 'pexels',
            'type' => 'video',
            'width' => $video['width'] ?? null,
            'height' => $video['height'] ?? null,
            'duration' => $video['duration'] ?? null,
            'url' => $video['url'] ?? null,
            'thumbnail' => $video['image'] ?? null,
            'video_url_hd' => $hdFile['link'] ?? null,
            'video_url_sd' => $sdFile['link'] ?? null,
            'user' => [
                'name' => $video['user']['name'] ?? 'Unknown',
                'url' => $video['user']['url'] ?? null,
            ],
        ];
    }

    /**
     * Format photos array.
     */
    protected function formatPhotos(array $photos): array
    {
        return array_map([$this, 'formatPhoto'], $photos);
    }

    /**
     * Format single photo.
     */
    protected function formatPhoto(array $photo): array
    {
        return [
            'id' => $photo['id'],
            'source' => 'pexels',
            'type' => 'photo',
            'width' => $photo['width'] ?? null,
            'height' => $photo['height'] ?? null,
            'url' => $photo['url'] ?? null,
            'src' => [
                'original' => $photo['src']['original'] ?? null,
                'large2x' => $photo['src']['large2x'] ?? null,
                'large' => $photo['src']['large'] ?? null,
                'medium' => $photo['src']['medium'] ?? null,
                'small' => $photo['src']['small'] ?? null,
                'tiny' => $photo['src']['tiny'] ?? null,
            ],
            'alt' => $photo['alt'] ?? '',
            'photographer' => $photo['photographer'] ?? 'Unknown',
            'photographer_url' => $photo['photographer_url'] ?? null,
        ];
    }

    /**
     * Error response format.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'data' => [],
            'error' => $message,
        ];
    }
}
