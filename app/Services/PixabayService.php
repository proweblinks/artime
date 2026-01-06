<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PixabayService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) get_option("media_pixabay_api_key", "");
        $this->client = new Client([
            'base_uri' => 'https://pixabay.com/api/',
        ]);
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && get_option('media_pixabay_status', 1) == 1;
    }

    /**
     * Search for videos.
     */
    public function searchVideos(string $query, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pixabay API not configured');
        }

        $cacheKey = 'pixabay_videos_' . md5($query . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($query, $options) {
            try {
                $response = $this->client->request('GET', 'videos/', [
                    'query' => [
                        'key' => $this->apiKey,
                        'q' => $query,
                        'per_page' => $options['per_page'] ?? 20,
                        'page' => $options['page'] ?? 1,
                        'video_type' => $options['video_type'] ?? 'all', // all, film, animation
                        'category' => $options['category'] ?? null,
                        'min_width' => $options['min_width'] ?? null,
                        'min_height' => $options['min_height'] ?? null,
                        'safesearch' => $options['safesearch'] ?? 'true',
                        'order' => $options['order'] ?? 'popular', // popular, latest
                    ],
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);

                return [
                    'success' => true,
                    'data' => $this->formatVideos($body['hits'] ?? []),
                    'total' => $body['totalHits'] ?? 0,
                    'page' => $options['page'] ?? 1,
                    'per_page' => $options['per_page'] ?? 20,
                ];

            } catch (\Throwable $e) {
                Log::error("Pixabay video search failed: " . $e->getMessage());
                return $this->errorResponse($e->getMessage());
            }
        });
    }

    /**
     * Search for images.
     */
    public function searchImages(string $query, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pixabay API not configured');
        }

        $cacheKey = 'pixabay_images_' . md5($query . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($query, $options) {
            try {
                $response = $this->client->request('GET', '', [
                    'query' => [
                        'key' => $this->apiKey,
                        'q' => $query,
                        'per_page' => $options['per_page'] ?? 20,
                        'page' => $options['page'] ?? 1,
                        'image_type' => $options['image_type'] ?? 'all', // all, photo, illustration, vector
                        'orientation' => $options['orientation'] ?? 'all', // all, horizontal, vertical
                        'category' => $options['category'] ?? null,
                        'colors' => $options['colors'] ?? null,
                        'safesearch' => $options['safesearch'] ?? 'true',
                        'order' => $options['order'] ?? 'popular',
                    ],
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);

                return [
                    'success' => true,
                    'data' => $this->formatImages($body['hits'] ?? []),
                    'total' => $body['totalHits'] ?? 0,
                    'page' => $options['page'] ?? 1,
                    'per_page' => $options['per_page'] ?? 20,
                ];

            } catch (\Throwable $e) {
                Log::error("Pixabay image search failed: " . $e->getMessage());
                return $this->errorResponse($e->getMessage());
            }
        });
    }

    /**
     * Search for music/audio.
     * Note: Pixabay doesn't have a direct music API, but has audio in videos.
     * For music, consider using the video API with music category.
     */
    public function searchMusic(string $query, array $options = []): array
    {
        // Pixabay doesn't have a separate music API
        // Redirect to video search with music category
        $options['category'] = 'music';
        return $this->searchVideos($query, $options);
    }

    /**
     * Get available categories.
     */
    public function getCategories(): array
    {
        return [
            'backgrounds',
            'fashion',
            'nature',
            'science',
            'education',
            'feelings',
            'health',
            'people',
            'religion',
            'places',
            'animals',
            'industry',
            'computer',
            'food',
            'sports',
            'transportation',
            'travel',
            'buildings',
            'business',
            'music',
        ];
    }

    /**
     * Format videos array.
     */
    protected function formatVideos(array $videos): array
    {
        return array_map(function ($video) {
            $videos = $video['videos'] ?? [];

            return [
                'id' => $video['id'],
                'source' => 'pixabay',
                'type' => 'video',
                'pageURL' => $video['pageURL'] ?? null,
                'tags' => $video['tags'] ?? '',
                'duration' => $video['duration'] ?? 0,
                'thumbnail' => $video['picture_id']
                    ? "https://i.vimeocdn.com/video/{$video['picture_id']}_640x360.jpg"
                    : null,
                'videos' => [
                    'large' => $videos['large'] ?? null,
                    'medium' => $videos['medium'] ?? null,
                    'small' => $videos['small'] ?? null,
                    'tiny' => $videos['tiny'] ?? null,
                ],
                'views' => $video['views'] ?? 0,
                'downloads' => $video['downloads'] ?? 0,
                'likes' => $video['likes'] ?? 0,
                'user' => $video['user'] ?? 'Unknown',
                'userImageURL' => $video['userImageURL'] ?? null,
            ];
        }, $videos);
    }

    /**
     * Format images array.
     */
    protected function formatImages(array $images): array
    {
        return array_map(function ($image) {
            return [
                'id' => $image['id'],
                'source' => 'pixabay',
                'type' => 'image',
                'pageURL' => $image['pageURL'] ?? null,
                'tags' => $image['tags'] ?? '',
                'width' => $image['imageWidth'] ?? null,
                'height' => $image['imageHeight'] ?? null,
                'src' => [
                    'original' => $image['largeImageURL'] ?? null,
                    'large' => $image['largeImageURL'] ?? null,
                    'medium' => $image['webformatURL'] ?? null,
                    'small' => $image['previewURL'] ?? null,
                ],
                'views' => $image['views'] ?? 0,
                'downloads' => $image['downloads'] ?? 0,
                'likes' => $image['likes'] ?? 0,
                'user' => $image['user'] ?? 'Unknown',
                'userImageURL' => $image['userImageURL'] ?? null,
            ];
        }, $images);
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
