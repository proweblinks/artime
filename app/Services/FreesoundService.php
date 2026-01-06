<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FreesoundService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) get_option("media_freesound_api_key", "");
        $this->client = new Client([
            'base_uri' => 'https://freesound.org/apiv2/',
        ]);
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && get_option('media_freesound_status', 1) == 1;
    }

    /**
     * Search for sounds.
     */
    public function searchSounds(string $query, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Freesound API not configured');
        }

        $cacheKey = 'freesound_search_' . md5($query . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($query, $options) {
            try {
                $params = [
                    'token' => $this->apiKey,
                    'query' => $query,
                    'page_size' => $options['per_page'] ?? 15,
                    'page' => $options['page'] ?? 1,
                    'fields' => 'id,name,description,tags,duration,previews,images,username,avg_rating,num_ratings,license,download',
                    'sort' => $options['sort'] ?? 'score', // score, duration_desc, duration_asc, created_desc, created_asc, downloads_desc, downloads_asc, rating_desc, rating_asc
                ];

                // Add optional filters
                if (!empty($options['filter'])) {
                    $params['filter'] = $options['filter'];
                }
                if (!empty($options['duration_min'])) {
                    $params['filter'] = ($params['filter'] ?? '') . " duration:[{$options['duration_min']} TO *]";
                }
                if (!empty($options['duration_max'])) {
                    $params['filter'] = ($params['filter'] ?? '') . " duration:[* TO {$options['duration_max']}]";
                }

                $response = $this->client->request('GET', 'search/text/', [
                    'query' => $params,
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);

                return [
                    'success' => true,
                    'data' => $this->formatSounds($body['results'] ?? []),
                    'total' => $body['count'] ?? 0,
                    'page' => $options['page'] ?? 1,
                    'per_page' => $options['per_page'] ?? 15,
                    'next' => $body['next'] ?? null,
                    'previous' => $body['previous'] ?? null,
                ];

            } catch (\Throwable $e) {
                Log::error("Freesound search failed: " . $e->getMessage());
                return $this->errorResponse($e->getMessage());
            }
        });
    }

    /**
     * Get a specific sound by ID.
     */
    public function getSound(int $id): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Freesound API not configured');
        }

        try {
            $response = $this->client->request('GET', "sounds/{$id}/", [
                'query' => [
                    'token' => $this->apiKey,
                    'fields' => 'id,name,description,tags,duration,previews,images,username,avg_rating,num_ratings,license,download,filesize,channels,bitrate,samplerate,type',
                ],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $this->formatSound($body),
            ];

        } catch (\Throwable $e) {
            Log::error("Freesound get sound failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get download URL for a sound (requires OAuth for full download, but preview is free).
     */
    public function getDownloadUrl(int $id): ?string
    {
        $sound = $this->getSound($id);

        if (!$sound['success']) {
            return null;
        }

        // Return preview URL (MP3, OGG available without OAuth)
        return $sound['data']['previews']['preview-hq-mp3']
            ?? $sound['data']['previews']['preview-lq-mp3']
            ?? null;
    }

    /**
     * Search for sounds by tags.
     */
    public function searchByTags(array $tags, array $options = []): array
    {
        $query = implode(' ', $tags);
        return $this->searchSounds($query, $options);
    }

    /**
     * Get similar sounds.
     */
    public function getSimilarSounds(int $soundId, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Freesound API not configured');
        }

        try {
            $response = $this->client->request('GET', "sounds/{$soundId}/similar/", [
                'query' => [
                    'token' => $this->apiKey,
                    'fields' => 'id,name,description,tags,duration,previews,images,username',
                ],
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $this->formatSounds($body['results'] ?? []),
                'total' => $body['count'] ?? 0,
            ];

        } catch (\Throwable $e) {
            Log::error("Freesound similar sounds failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Format sounds array.
     */
    protected function formatSounds(array $sounds): array
    {
        return array_map([$this, 'formatSound'], $sounds);
    }

    /**
     * Format single sound.
     */
    protected function formatSound(array $sound): array
    {
        return [
            'id' => $sound['id'],
            'source' => 'freesound',
            'type' => 'audio',
            'name' => $sound['name'] ?? '',
            'description' => $sound['description'] ?? '',
            'tags' => $sound['tags'] ?? [],
            'duration' => $sound['duration'] ?? 0,
            'previews' => [
                'hq_mp3' => $sound['previews']['preview-hq-mp3'] ?? null,
                'lq_mp3' => $sound['previews']['preview-lq-mp3'] ?? null,
                'hq_ogg' => $sound['previews']['preview-hq-ogg'] ?? null,
                'lq_ogg' => $sound['previews']['preview-lq-ogg'] ?? null,
            ],
            'waveform' => $sound['images']['waveform_m'] ?? $sound['images']['waveform_l'] ?? null,
            'spectrogram' => $sound['images']['spectral_m'] ?? $sound['images']['spectral_l'] ?? null,
            'username' => $sound['username'] ?? 'Unknown',
            'rating' => $sound['avg_rating'] ?? 0,
            'num_ratings' => $sound['num_ratings'] ?? 0,
            'license' => $sound['license'] ?? null,
            'download_url' => $sound['download'] ?? null,
            'filesize' => $sound['filesize'] ?? null,
            'channels' => $sound['channels'] ?? null,
            'bitrate' => $sound['bitrate'] ?? null,
            'samplerate' => $sound['samplerate'] ?? null,
            'file_type' => $sound['type'] ?? null,
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
