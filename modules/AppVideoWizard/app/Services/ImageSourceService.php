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
        $usedWikiUrls = []; // Track Wikimedia URLs across scenes to prevent duplicates

        foreach ($scenes as $scene) {
            $sceneId = $scene['id'] ?? 'scene_0';
            $sceneText = $scene['text'] ?? '';
            $candidates = [];

            // 1. Score article images against scene text (only include relevant ones)
            $rankedArticle = $this->rankArticleImages($sceneText, $articleImages);
            foreach ($rankedArticle as $img) {
                if (($img['_score'] ?? 0) <= 0) {
                    continue; // Skip article images with no relevance to this scene
                }
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
            Log::info('ImageSourceService: Search query for scene', [
                'scene_id' => $sceneId,
                'query' => $searchQuery,
                'scene_text_preview' => Str::limit($sceneText, 80),
            ]);

            if (!empty($searchQuery)) {
                try {
                    $wikiResults = $this->searchWikimedia($searchQuery, 8); // Fetch extra to allow for dedup filtering
                    foreach ($wikiResults as $wImg) {
                        $url = $wImg['url'] ?? '';
                        // Skip images already used in previous scenes
                        if (in_array($url, $usedWikiUrls)) {
                            continue;
                        }
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

            // Remove internal score from output, limit to top 5 per scene
            $candidates = array_slice($candidates, 0, 5);
            $candidates = array_map(function ($c) {
                unset($c['_score']);
                return $c;
            }, $candidates);

            $results[$sceneId] = array_values($candidates);

            // Track all Wiki URLs from this scene to avoid cross-scene duplicates
            foreach ($candidates as $c) {
                if (($c['source'] ?? '') === 'wikimedia' && !empty($c['url'])) {
                    $usedWikiUrls[] = $c['url'];
                }
            }
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
     * Finds proper noun phrases (multi-word names) and combines with subject for context.
     */
    protected function extractSearchTerms(string $sceneText, string $subject): string
    {
        // Common words that should NOT be treated as proper nouns even when capitalized
        $stopWords = array_flip(array_map('strtolower', [
            'The', 'This', 'That', 'These', 'Those', 'When', 'Where', 'Which',
            'What', 'How', 'Who', 'Why', 'Not', 'But', 'And', 'For', 'With',
            'From', 'Into', 'Over', 'After', 'Before', 'Between', 'Under',
            'About', 'Through', 'During', 'Without', 'Again', 'Once', 'Here',
            'There', 'Some', 'Such', 'Very', 'Just', 'Also', 'Than', 'Other',
            'Even', 'Most', 'More', 'Many', 'Much', 'Each', 'Every', 'Both',
            'Few', 'All', 'Any', 'Its', 'His', 'Her', 'Our', 'Your', 'Their',
            'Have', 'Has', 'Had', 'Will', 'Would', 'Could', 'Should', 'May',
            'Might', 'Must', 'Shall', 'Can', 'Did', 'Does', 'Was', 'Were',
            'Been', 'Being', 'Are', 'New', 'Now', 'Get', 'Got', 'Make',
            'Made', 'Still', 'Yet', 'Already', 'Since', 'While', 'Then',
            'Found', 'Error', 'However', 'Although', 'Despite', 'According',
            'Meanwhile', 'Furthermore', 'Moreover', 'Like', 'Only', 'Well',
            'Take', 'Took', 'Come', 'Came', 'Going', 'Gone', 'Said', 'Says',
            'Tell', 'Told', 'Know', 'Known', 'Think', 'Thought', 'Give',
            'Given', 'First', 'Last', 'Next', 'Another', 'Perhaps', 'Nearly',
            'Almost', 'Along', 'Already', 'Across', 'Around', 'Away', 'Back',
            'Down', 'Enough', 'Else', 'Instead', 'Often', 'Rather', 'Soon',
            'Whether', 'Whose', 'Whom',
        ]));

        // Extract multi-word proper noun phrases (consecutive capitalized words)
        $properNounPhrases = [];
        $sentences = preg_split('/[.!?]+/', $sceneText);

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) {
                continue;
            }

            $words = preg_split('/\s+/', $sentence);
            $currentPhrase = [];
            $isFirst = true;

            foreach ($words as $word) {
                $clean = preg_replace('/[^a-zA-Z\'-]/', '', $word);
                if (empty($clean)) {
                    if (!empty($currentPhrase)) {
                        $properNounPhrases[] = implode(' ', $currentPhrase);
                        $currentPhrase = [];
                    }
                    $isFirst = false;
                    continue;
                }

                $isCapitalized = ctype_upper($clean[0]) && mb_strlen($clean) >= 3;
                $isStop = isset($stopWords[strtolower($clean)]);

                if (!$isFirst && $isCapitalized && !$isStop) {
                    $currentPhrase[] = $clean;
                } else {
                    if (!empty($currentPhrase)) {
                        $properNounPhrases[] = implode(' ', $currentPhrase);
                        $currentPhrase = [];
                    }
                }
                $isFirst = false;
            }
            if (!empty($currentPhrase)) {
                $properNounPhrases[] = implode(' ', $currentPhrase);
            }
        }

        $properNounPhrases = array_unique($properNounPhrases);

        // Sort by length desc (longer phrases = more specific = better search results)
        usort($properNounPhrases, fn($a, $b) => strlen($b) <=> strlen($a));

        // Build query: top proper noun phrases + subject for context
        $parts = [];
        if (!empty($properNounPhrases)) {
            $parts = array_merge($parts, array_slice($properNounPhrases, 0, 2));
        }

        // Always include subject for Wikimedia search context
        if (!empty($subject)) {
            $parts[] = $subject;
        }

        $query = implode(' ', $parts);

        // Fallback: use key content words (5+ chars, not common verbs)
        if (empty(trim($query))) {
            $commonVerbs = array_flip([
                'would', 'could', 'should', 'might', 'about', 'after', 'before',
                'being', 'between', 'during', 'through', 'under', 'until', 'without',
                'which', 'where', 'while', 'their', 'there', 'these', 'those', 'other',
                'still', 'already', 'really', 'never', 'always', 'often', 'every',
            ]);
            $words = array_filter(
                preg_split('/\s+/', $sceneText),
                function ($w) use ($commonVerbs) {
                    $clean = strtolower(preg_replace('/[^a-zA-Z]/', '', $w));
                    return mb_strlen($clean) >= 5 && !isset($commonVerbs[$clean]);
                }
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
