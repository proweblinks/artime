<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageSourceService
{
    /**
     * Find and rank real images for each scene.
     *
     * @param array $scenes       [{id, text, estimated_duration}, ...]
     * @param array $extractedContent  From UrlContentExtractorService (has 'images' key)
     * @param array $contentBrief      From analyzeContent() (has 'subject' key)
     * @return array  [scene_id => [{url, thumbnail, source, title, width, height, license?, author?}, ...]]
     */
    public function sourceForScenes(array $scenes, array $extractedContent, array $contentBrief): array
    {
        $articleImages = $extractedContent['images'] ?? [];
        $subject = $contentBrief['subject'] ?? '';
        $results = [];

        foreach ($scenes as $scene) {
            $sceneId = $scene['id'] ?? 'scene_0';
            $sceneText = $scene['text'] ?? '';
            $candidates = [];

            // 1. Score article images against scene text
            $rankedArticle = $this->rankArticleImages($sceneText, $articleImages);
            foreach ($rankedArticle as $img) {
                $candidates[] = [
                    'url' => $img['url'],
                    'thumbnail' => $img['url'],
                    'source' => 'article',
                    'title' => basename(parse_url($img['url'], PHP_URL_PATH) ?: 'image'),
                    'width' => $img['width'] ?? 0,
                    'height' => $img['height'] ?? 0,
                    'score' => $img['_score'] ?? 0,
                ];
            }

            // 2. Search Wikimedia Commons for key entities
            $searchQuery = $this->extractSearchTerms($sceneText, $subject);
            if (!empty($searchQuery)) {
                try {
                    $wikiResults = $this->searchWikimedia($searchQuery, 5);
                    foreach ($wikiResults as $wImg) {
                        $candidates[] = array_merge($wImg, [
                            'source' => 'wikimedia',
                            'score' => $wImg['score'] ?? 0,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('ImageSourceService: Wikimedia search failed', [
                        'scene_id' => $sceneId,
                        'query' => $searchQuery,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. Sort by score descending, best first
            usort($candidates, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            // Remove internal score from output
            $candidates = array_map(function ($c) {
                unset($c['_score']);
                return $c;
            }, $candidates);

            $results[$sceneId] = array_values($candidates);
        }

        return $results;
    }

    /**
     * Score article images against scene text by keyword overlap.
     */
    protected function rankArticleImages(string $sceneText, array $articleImages): array
    {
        if (empty($articleImages) || empty($sceneText)) {
            return [];
        }

        // Extract significant words (4+ chars) from scene text
        $sceneWords = array_unique(array_filter(
            preg_split('/[\s\-_\/\.]+/', strtolower($sceneText)),
            fn($w) => mb_strlen($w) >= 4
        ));

        if (empty($sceneWords)) {
            return $articleImages;
        }

        $scored = [];
        foreach ($articleImages as $img) {
            $url = strtolower($img['url'] ?? '');
            $source = strtolower($img['source'] ?? '');
            $searchable = $url . ' ' . $source;

            $score = 0;
            foreach ($sceneWords as $word) {
                if (str_contains($searchable, $word)) {
                    $score += 2;
                }
            }

            // Bonus for larger images
            $w = $img['width'] ?? 0;
            $h = $img['height'] ?? 0;
            if ($w >= 800 && $h >= 600) {
                $score += 1;
            }

            $img['_score'] = $score;
            $scored[] = $img;
        }

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

        return $scored;
    }

    /**
     * Search Wikimedia Commons for images matching a query.
     *
     * Two-step: search files → get imageinfo for each result.
     * Results are cached for 1 hour.
     */
    public function searchWikimedia(string $query, int $limit = 5): array
    {
        $cacheKey = 'wikimedia_search_' . md5($query . '_' . $limit);

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            $http = Http::timeout(15)->withHeaders([
                'User-Agent' => 'ArtimeVideoWizard/1.0 (https://artime.ai; contact@artime.ai)',
            ]);

            // Step 1: Search for files
            $searchResponse = $http->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'search',
                'srsearch' => $query,
                'srnamespace' => 6, // File namespace
                'srlimit' => $limit * 2, // Fetch extra to filter
                'format' => 'json',
                'origin' => '*',
            ]);

            if (!$searchResponse->ok()) {
                Log::warning('ImageSourceService: Wikimedia search API error', [
                    'status' => $searchResponse->status(),
                    'query' => $query,
                ]);
                throw new \RuntimeException('Wikimedia API returned ' . $searchResponse->status());
            }

            $searchResults = $searchResponse->json('query.search', []);
            if (empty($searchResults)) {
                return [];
            }

            // Step 2: Get image info for each result
            $titles = array_column($searchResults, 'title');
            $titlesString = implode('|', array_slice($titles, 0, $limit * 2));

            $infoResponse = $http->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'titles' => $titlesString,
                'prop' => 'imageinfo',
                'iiprop' => 'url|size|mime|user',
                'iiurlwidth' => 800,
                'format' => 'json',
                'origin' => '*',
            ]);

            if (!$infoResponse->ok()) {
                return [];
            }

            $pages = $infoResponse->json('query.pages', []);
            $results = [];

            foreach ($pages as $page) {
                $imageInfo = $page['imageinfo'][0] ?? null;
                if (!$imageInfo) {
                    continue;
                }

                $mime = $imageInfo['mime'] ?? '';
                $width = $imageInfo['width'] ?? 0;
                $height = $imageInfo['height'] ?? 0;

                // Filter: only jpeg/png/webp, min 400x300
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($mime, $allowedMimes)) {
                    continue;
                }
                if ($width < 400 || $height < 300) {
                    continue;
                }

                $results[] = [
                    'url' => $imageInfo['url'] ?? '',
                    'thumbnail' => $imageInfo['thumburl'] ?? $imageInfo['url'] ?? '',
                    'title' => $page['title'] ?? '',
                    'width' => $width,
                    'height' => $height,
                    'author' => $imageInfo['user'] ?? null,
                    'license' => 'CC',
                    'score' => 1, // Base score for wiki results
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        });
    }

    /**
     * Extract search terms from scene text.
     * Finds proper nouns (capitalized word sequences) and combines with subject.
     */
    protected function extractSearchTerms(string $sceneText, string $subject): string
    {
        // Extract proper nouns (capitalized words not at sentence start)
        $properNouns = [];
        $sentences = preg_split('/[.!?]+/', $sceneText);

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) {
                continue;
            }

            $words = preg_split('/\s+/', $sentence);
            $isFirst = true;

            foreach ($words as $word) {
                $clean = preg_replace('/[^a-zA-Z\'-]/', '', $word);
                if (empty($clean)) {
                    $isFirst = false;
                    continue;
                }

                // Capitalized word that isn't the first word of the sentence
                if (!$isFirst && ctype_upper($clean[0]) && mb_strlen($clean) >= 3) {
                    $properNouns[] = $clean;
                }
                $isFirst = false;
            }
        }

        $properNouns = array_unique($properNouns);

        // Build query: proper nouns + subject
        $parts = [];
        if (!empty($properNouns)) {
            $parts[] = implode(' ', array_slice($properNouns, 0, 3));
        }
        if (!empty($subject) && empty($properNouns)) {
            $parts[] = $subject;
        }

        $query = implode(' ', $parts);

        // Fallback: use first few significant words from scene text
        if (empty(trim($query))) {
            $words = array_filter(
                preg_split('/\s+/', $sceneText),
                fn($w) => mb_strlen(preg_replace('/[^a-zA-Z]/', '', $w)) >= 4
            );
            $query = implode(' ', array_slice(array_values($words), 0, 4));
        }

        return trim($query);
    }

    /**
     * Download an image and store it locally.
     *
     * @return string|null Public URL on success, null on failure
     */
    public function downloadAndStore(string $imageUrl, int $projectId, string $sceneId): ?string
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->ok()) {
                Log::warning('ImageSourceService: Download failed', [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                default => 'jpg',
            };

            $hash = substr(md5($imageUrl), 0, 8);
            $filename = "{$sceneId}-{$hash}.{$ext}";
            $path = "url-to-video/{$projectId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            return url('/public/storage/' . $path);
        } catch (\Exception $e) {
            Log::warning('ImageSourceService: Download exception', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
