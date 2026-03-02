<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\PexelsService;
use App\Services\PixabayService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Services\ArtimeStockService;

class ImageSourceService
{
    /**
     * Find and rank real images for each scene.
     *
     * @param array $scenes       [{id, text, estimated_duration}, ...]
     * @param array $extractedContent  From UrlContentExtractorService (has 'images' key)
     * @param array $contentBrief      From analyzeContent() (has 'subject' key)
     * @return array  [scene_id => ['candidates' => [...], 'suggestions' => [...]]]
     */
    public function sourceForScenes(array $scenes, array $extractedContent, array $contentBrief): array
    {
        $articleImages = $extractedContent['images'] ?? [];
        $subject = $contentBrief['subject'] ?? '';
        $results = [];
        $usedStockIds = []; // Track IDs across scenes for deduplication

        $stockService = new ArtimeStockService();

        foreach ($scenes as $scene) {
            $sceneId = $scene['id'] ?? 'scene_0';
            $sceneText = $scene['text'] ?? '';
            $candidates = [];

            // Search Artime Stock with exclusion of already-shown clips
            $stockQuery = $this->buildStockSearchQuery($sceneText, $subject);

            Log::info('ImageSourceService: Stock search for scene', [
                'scene_id' => $sceneId,
                'query' => $stockQuery,
                'scene_text_preview' => Str::limit($sceneText, 80),
                'excluding_ids' => count($usedStockIds),
            ]);

            if (!empty($stockQuery)) {
                try {
                    $stockResults = $stockService->searchExcluding($stockQuery, 8, $usedStockIds);

                    // Fallback: if exclusion returned 0, try without exclusion
                    if (empty($stockResults)) {
                        $stockResults = $stockService->search($stockQuery, 8);
                    }

                    foreach ($stockResults as $stockItem) {
                        $candidates[] = $stockItem;
                    }
                } catch (\Exception $e) {
                    Log::warning('ImageSourceService: Artime Stock search failed', [
                        'scene_id' => $sceneId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback: try subject/title directly if scene-specific search found nothing
            if (empty($candidates) && !empty($subject)) {
                try {
                    $fallbackResults = $stockService->searchExcluding($subject, 8, $usedStockIds);
                    if (empty($fallbackResults)) {
                        $fallbackResults = $stockService->search($subject, 8);
                    }
                    foreach ($fallbackResults as $stockItem) {
                        $candidates[] = $stockItem;
                    }
                } catch (\Exception $e) {
                    // Silently fail — scenes without stock will show "no images" state
                }
            }

            // Collect stock_ids from this scene's candidates into the exclusion set
            foreach ($candidates as $c) {
                if (!empty($c['stock_id'])) {
                    $usedStockIds[] = $c['stock_id'];
                }
            }

            // Sort by score descending, best first
            usort($candidates, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            // Remove internal score from output, limit to top 8 per scene (images + videos)
            $candidates = array_slice($candidates, 0, 8);
            $candidates = array_map(function ($c) {
                unset($c['_score']);
                return $c;
            }, $candidates);

            $suggestions = $this->generateSearchSuggestions($sceneText, $subject);

            $results[$sceneId] = [
                'candidates' => array_values($candidates),
                'suggestions' => $suggestions,
            ];
        }

        return $results;
    }

    /**
     * Generate 2-4 short search suggestion terms for a scene.
     * Extracts proper noun phrases and key nouns from the subject.
     */
    protected function generateSearchSuggestions(string $sceneText, string $subject): array
    {
        $suggestions = [];

        // Reuse the same stop words logic from extractSearchTerms
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

        // Extract proper noun phrases from scene text
        $sentences = preg_split('/[.!?]+/', $sceneText);
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            $words = preg_split('/\s+/', $sentence);
            $currentPhrase = [];
            $isFirst = true;

            foreach ($words as $word) {
                $clean = preg_replace('/[^a-zA-Z\'-]/', '', $word);
                if (empty($clean)) {
                    if (!empty($currentPhrase)) {
                        $suggestions[] = implode(' ', $currentPhrase);
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
                        $suggestions[] = implode(' ', $currentPhrase);
                        $currentPhrase = [];
                    }
                }
                $isFirst = false;
            }
            if (!empty($currentPhrase)) {
                $suggestions[] = implode(' ', $currentPhrase);
            }
        }

        // Extract 2-3 key nouns from subject
        if (!empty($subject)) {
            $subjectWords = array_filter(
                preg_split('/[\s,\-:]+/', $subject),
                fn($w) => mb_strlen(trim($w)) >= 3 && !isset($stopWords[strtolower(trim($w))])
            );
            $subjectTerms = array_slice(array_values($subjectWords), 0, 3);
            // Add subject as a single term if it has 2-3 short words
            if (count($subjectTerms) >= 2 && count($subjectTerms) <= 3) {
                $suggestions[] = implode(' ', $subjectTerms);
            }
            foreach ($subjectTerms as $term) {
                $suggestions[] = trim($term);
            }
        }

        // Deduplicate (case-insensitive)
        $seen = [];
        $unique = [];
        foreach ($suggestions as $s) {
            $s = trim($s);
            if (empty($s)) continue;
            $key = strtolower($s);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = $s;
        }

        // Return 2-4 suggestions, longest/most specific first
        usort($unique, fn($a, $b) => strlen($b) <=> strlen($a));

        return array_slice($unique, 0, 4);
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
    public function searchWikimedia(string $query, int $limit = 5, bool $includeVideo = false): array
    {
        $cacheKey = 'wikimedia_search_' . md5($query . '_' . $limit . '_' . ($includeVideo ? 'v' : 'i'));

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit, $includeVideo) {
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

                // Filter by allowed MIME types
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if ($includeVideo) {
                    $allowedMimes = array_merge($allowedMimes, ['video/mp4', 'video/webm', 'video/ogg']);
                }
                if (!in_array($mime, $allowedMimes)) {
                    continue;
                }

                $isVideo = str_starts_with($mime, 'video/');

                if (!$isVideo && ($width < 400 || $height < 300)) {
                    continue;
                }

                if ($isVideo) {
                    $results[] = [
                        'type' => 'video',
                        'url' => $imageInfo['url'] ?? '',
                        'thumbnail' => $imageInfo['thumburl'] ?? '',
                        'title' => $page['title'] ?? '',
                        'width' => $width,
                        'height' => $height,
                        'duration' => 0,
                        'source' => 'wikimedia',
                        'author' => $imageInfo['user'] ?? null,
                        'license' => 'CC',
                        'score' => 1,
                    ];
                } else {
                    $results[] = [
                        'url' => $imageInfo['url'] ?? '',
                        'thumbnail' => $imageInfo['thumburl'] ?? $imageInfo['url'] ?? '',
                        'title' => $page['title'] ?? '',
                        'width' => $width,
                        'height' => $height,
                        'author' => $imageInfo['user'] ?? null,
                        'license' => 'CC',
                        'score' => 1,
                    ];
                }

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        });
    }

    /**
     * Build a search query optimized for the local stock media library.
     * Uses broader keyword extraction (not just proper nouns) since stock
     * media is tagged with category keywords like "space", "galaxy", "nature", etc.
     */
    protected function buildStockSearchQuery(string $sceneText, string $subject): string
    {
        $stopWords = array_flip([
            'the', 'this', 'that', 'these', 'those', 'when', 'where', 'which',
            'what', 'how', 'who', 'why', 'not', 'but', 'and', 'for', 'with',
            'from', 'into', 'over', 'after', 'before', 'between', 'under',
            'about', 'through', 'during', 'without', 'again', 'once', 'here',
            'there', 'some', 'such', 'very', 'just', 'also', 'than', 'other',
            'even', 'most', 'more', 'many', 'much', 'each', 'every', 'both',
            'few', 'all', 'any', 'its', 'his', 'her', 'our', 'your', 'their',
            'have', 'has', 'had', 'will', 'would', 'could', 'should', 'may',
            'might', 'must', 'shall', 'can', 'did', 'does', 'was', 'were',
            'been', 'being', 'are', 'new', 'now', 'get', 'got', 'make',
            'made', 'still', 'yet', 'already', 'since', 'while', 'then',
            'look', 'see', 'know', 'think', 'like', 'only', 'well',
            'take', 'took', 'come', 'came', 'going', 'gone', 'said', 'says',
            'tell', 'told', 'give', 'given', 'first', 'last', 'next',
            'another', 'really', 'truly', 'exactly', 'actually', 'just',
            'show', 'showing', 'shows', 'imagine', 'right', 'tonight',
            'every', 'you', 'your', 'we', 'they', 'one', 'two', 'three',
        ]);

        $subjectParts = [];
        $sceneParts = [];

        // 1. Extract key words from subject/title — cap at 2 to avoid dominating
        if (!empty($subject)) {
            $subjectWords = preg_split('/[\s,\-:]+/', strtolower($subject));
            foreach ($subjectWords as $w) {
                $clean = preg_replace('/[^a-z]/', '', $w);
                if (mb_strlen($clean) >= 3 && !isset($stopWords[$clean])) {
                    $subjectParts[] = $clean;
                }
            }
        }
        $subjectParts = array_values(array_unique($subjectParts));
        $subjectParts = array_slice($subjectParts, 0, 2); // Cap subject at 2 words

        // 2. Extract nouns/adjectives from scene text — allow up to 4 scene-specific words
        $textWords = preg_split('/[\s,\.\!\?\;\:\(\)\[\]\"\']+/', strtolower($sceneText));
        foreach ($textWords as $w) {
            $clean = preg_replace('/[^a-z]/', '', $w);
            if (mb_strlen($clean) >= 4 && !isset($stopWords[$clean]) && !in_array($clean, $subjectParts)) {
                $sceneParts[] = $clean;
            }
        }
        $sceneParts = array_values(array_unique($sceneParts));
        $sceneParts = array_slice($sceneParts, 0, 4); // Up to 4 scene-specific words

        // Combine: subject words first, then scene-specific words
        $parts = array_merge($subjectParts, $sceneParts);

        return implode(' ', $parts);
    }

    /**
     * Extract search terms from scene text.
     * Finds proper noun phrases (multi-word names) and combines with subject for context.
     */
    protected function extractSearchTerms(string $sceneText, string $subject): string
    {
        // Common words that should NOT be treated as proper nouns even when capitalized
        // Includes contractions (that's, it's, etc.) stripped to base form
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
            // Contractions
            "That's", "It's", "He's", "She's", "There's", "Here's", "What's",
            "Who's", "Where's", "How's", "Let's", "Don't", "Doesn't", "Didn't",
            "Won't", "Can't", "Couldn't", "Shouldn't", "Wouldn't", "Isn't",
            "Aren't", "Wasn't", "Weren't", "Haven't", "Hasn't", "Hadn't",
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

        // Build query: proper nouns only (short queries work best on Wikimedia Commons)
        $parts = [];
        if (!empty($properNounPhrases)) {
            // Use top 2-3 proper noun phrases — these are the most visual/searchable
            $parts = array_merge($parts, array_slice($properNounPhrases, 0, 3));
        }

        $query = implode(' ', $parts);

        // Fallback 1: if no proper nouns, extract key nouns from subject (first 3 words)
        if (empty(trim($query)) && !empty($subject)) {
            $subjectWords = array_filter(
                preg_split('/[\s,\-:]+/', $subject),
                fn($w) => mb_strlen($w) >= 3
            );
            $query = implode(' ', array_slice(array_values($subjectWords), 0, 3));
        }

        // Fallback 2: use key content words from scene text
        if (empty(trim($query))) {
            $commonVerbs = array_flip([
                'would', 'could', 'should', 'might', 'about', 'after', 'before',
                'being', 'between', 'during', 'through', 'under', 'until', 'without',
                'which', 'where', 'while', 'their', 'there', 'these', 'those', 'other',
                'still', 'already', 'really', 'never', 'always', 'often', 'every',
                'interesting', 'important', 'significant', 'crucial',
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
     * Search free video clips from Pexels and Pixabay.
     * Returns a unified array of video candidates.
     */
    public function searchVideoClips(string $query, int $limit = 5): array
    {
        $results = [];
        $seenUrls = [];

        // Search Pexels Videos
        $pexelsEnabled = get_option('file_pexels_status', 1) == 1;
        if ($pexelsEnabled) {
            try {
                $pexels = new PexelsService();
                if ($pexels->isConfigured()) {
                    $pexelsResult = $pexels->searchVideos($query, [
                        'per_page' => $limit,
                        'orientation' => 'portrait',
                    ]);

                    if (!empty($pexelsResult['success']) && !empty($pexelsResult['data'])) {
                        foreach ($pexelsResult['data'] as $video) {
                            $videoUrl = $video['video_url_hd'] ?? $video['video_url_sd'] ?? null;
                            if (empty($videoUrl) || isset($seenUrls[$videoUrl])) {
                                continue;
                            }
                            $duration = $video['duration'] ?? 0;
                            // Prefer clips 5-20 seconds
                            if ($duration > 0 && ($duration < 3 || $duration > 30)) {
                                continue;
                            }
                            $seenUrls[$videoUrl] = true;
                            $results[] = [
                                'type' => 'video',
                                'url' => $videoUrl,
                                'thumbnail' => $video['thumbnail'] ?? '',
                                'title' => 'Pexels Video #' . ($video['id'] ?? ''),
                                'duration' => $duration,
                                'width' => $video['width'] ?? 0,
                                'height' => $video['height'] ?? 0,
                                'source' => 'pexels',
                                'score' => 2, // Higher base score for stock video
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ImageSourceService: Pexels video search failed', ['error' => $e->getMessage()]);
            }
        }

        // Search Pixabay Videos
        $pixabayEnabled = get_option('file_pixabay_status', 1) == 1;
        if ($pixabayEnabled) {
            try {
                $pixabay = new PixabayService();
                if ($pixabay->isConfigured()) {
                    $pixabayResult = $pixabay->searchVideos($query, [
                        'per_page' => $limit,
                    ]);

                    if (!empty($pixabayResult['success']) && !empty($pixabayResult['data'])) {
                        foreach ($pixabayResult['data'] as $video) {
                            // Prefer medium quality for reasonable file size
                            $videoUrl = $video['videos']['medium']['url']
                                ?? $video['videos']['small']['url']
                                ?? $video['videos']['large']['url']
                                ?? null;
                            if (empty($videoUrl) || isset($seenUrls[$videoUrl])) {
                                continue;
                            }
                            $duration = $video['duration'] ?? 0;
                            if ($duration > 0 && ($duration < 3 || $duration > 30)) {
                                continue;
                            }
                            $seenUrls[$videoUrl] = true;
                            $w = $video['videos']['medium']['width'] ?? $video['videos']['small']['width'] ?? 0;
                            $h = $video['videos']['medium']['height'] ?? $video['videos']['small']['height'] ?? 0;
                            $results[] = [
                                'type' => 'video',
                                'url' => $videoUrl,
                                'thumbnail' => $video['thumbnail'] ?? '',
                                'title' => $video['tags'] ?? ('Pixabay Video #' . ($video['id'] ?? '')),
                                'duration' => $duration,
                                'width' => $w,
                                'height' => $h,
                                'source' => 'pixabay',
                                'score' => 2,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ImageSourceService: Pixabay video search failed', ['error' => $e->getMessage()]);
            }
        }

        // Sort by portrait preference, then by duration (prefer 5-15s clips)
        usort($results, function ($a, $b) {
            $aPortrait = ($a['height'] ?? 0) > ($a['width'] ?? 0) ? 1 : 0;
            $bPortrait = ($b['height'] ?? 0) > ($b['width'] ?? 0) ? 1 : 0;
            if ($aPortrait !== $bPortrait) {
                return $bPortrait - $aPortrait;
            }
            // Prefer clips 5-15s
            $aDur = abs(($a['duration'] ?? 10) - 10);
            $bDur = abs(($b['duration'] ?? 10) - 10);
            return $aDur <=> $bDur;
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Search Pexels and Pixabay for stock PHOTOS (not videos).
     * Returns a unified array of image candidates.
     */
    public function searchStockPhotos(string $query, int $limit = 5): array
    {
        $results = [];

        // Search Pexels Photos
        if (get_option('file_pexels_status', 1) == 1) {
            try {
                $pexels = new PexelsService();
                if ($pexels->isConfigured()) {
                    $response = $pexels->searchPhotos($query, [
                        'per_page' => $limit,
                        'orientation' => 'portrait',
                    ]);
                    if (!empty($response['success']) && !empty($response['data'])) {
                        foreach ($response['data'] as $photo) {
                            $results[] = [
                                'url' => $photo['src']['large'] ?? $photo['src']['original'] ?? '',
                                'thumbnail' => $photo['src']['medium'] ?? $photo['src']['small'] ?? '',
                                'title' => $photo['alt'] ?? $query,
                                'width' => $photo['width'] ?? 0,
                                'height' => $photo['height'] ?? 0,
                                'source' => 'pexels',
                                'score' => 2,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ImageSourceService: Pexels photo search failed', ['error' => $e->getMessage()]);
            }
        }

        // Search Pixabay Photos
        if (get_option('file_pixabay_status', 1) == 1) {
            try {
                $pixabay = new PixabayService();
                if ($pixabay->isConfigured()) {
                    $response = $pixabay->searchImages($query, [
                        'per_page' => $limit,
                        'image_type' => 'photo',
                    ]);
                    if (!empty($response['success']) && !empty($response['data'])) {
                        foreach ($response['data'] as $img) {
                            $results[] = [
                                'url' => $img['src']['large'] ?? $img['src']['original'] ?? '',
                                'thumbnail' => $img['src']['medium'] ?? $img['src']['small'] ?? '',
                                'title' => $img['tags'] ?? $query,
                                'width' => $img['width'] ?? 0,
                                'height' => $img['height'] ?? 0,
                                'source' => 'pixabay',
                                'score' => 2,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ImageSourceService: Pixabay photo search failed', ['error' => $e->getMessage()]);
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Download a video file and store it locally.
     *
     * @return string|null Public URL on success, null on failure
     */
    public function downloadAndStoreVideo(string $videoUrl, int $projectId, string $sceneId): ?string
    {
        try {
            $response = Http::timeout(60)->withHeaders([
                'User-Agent' => 'ArtimeVideoWizard/1.0 (https://artime.ai; contact@artime.ai)',
            ])->get($videoUrl);

            if (!$response->ok()) {
                Log::warning('ImageSourceService: Video download failed', [
                    'url' => $videoUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $contentType = $response->header('Content-Type') ?? 'video/mp4';
            $ext = match (true) {
                str_contains($contentType, 'webm') => 'webm',
                str_contains($contentType, 'ogg') || str_contains($contentType, 'ogv') => 'ogv',
                default => 'mp4',
            };

            $hash = substr(md5($videoUrl), 0, 8);
            $filename = "{$sceneId}-video-{$hash}.{$ext}";
            $path = "url-to-video/{$projectId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            // If WebM or OGV, convert to MP4 via ffmpeg
            if ($ext !== 'mp4') {
                $inputPath = Storage::disk('public')->path($path);
                $mp4Filename = "{$sceneId}-video-{$hash}.mp4";
                $mp4Path = "url-to-video/{$projectId}/{$mp4Filename}";
                $mp4FullPath = Storage::disk('public')->path($mp4Path);

                $ffmpeg = $this->findFfmpeg();
                $cmd = "{$ffmpeg} -i " . escapeshellarg($inputPath) . " -c:v libx264 -crf 23 -preset fast -an -y " . escapeshellarg($mp4FullPath) . " 2>&1";
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && file_exists($mp4FullPath)) {
                    Storage::disk('public')->delete($path);
                    $path = $mp4Path;
                } else {
                    Log::warning('ImageSourceService: FFmpeg conversion failed', [
                        'cmd' => $cmd,
                        'return_code' => $returnCode,
                    ]);
                    // Keep original file as fallback
                }
            }

            return url('/public/storage/' . $path);
        } catch (\Exception $e) {
            Log::warning('ImageSourceService: Video download exception', [
                'url' => $videoUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find ffmpeg binary path.
     */
    protected function findFfmpeg(): string
    {
        // Check common locations
        $paths = [
            '/home/artime/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/usr/bin/ffmpeg',
            'ffmpeg',
        ];

        foreach ($paths as $path) {
            if ($path === 'ffmpeg' || file_exists($path)) {
                return $path;
            }
        }

        return 'ffmpeg';
    }

    /**
     * Download an image and store it locally.
     *
     * @return string|null Public URL on success, null on failure
     */
    public function downloadAndStore(string $imageUrl, int $projectId, string $sceneId): ?string
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'User-Agent' => 'ArtimeVideoWizard/1.0 (https://artime.ai; contact@artime.ai)',
            ])->get($imageUrl);

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
