<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\StockMedia;

class ArtimeStockService
{
    /**
     * Search stock media by keyword.
     *
     * @return array Array of candidate arrays (same format as Pexels/Pixabay)
     */
    public function search(string $query, int $limit = 6, ?string $type = null, ?string $orientation = null): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        try {
            $builder = StockMedia::active()->search($query);

            if ($type && in_array($type, ['image', 'video'])) {
                $builder->ofType($type);
            }

            if ($orientation && in_array($orientation, ['landscape', 'portrait', 'square'])) {
                $builder->orientation($orientation);
            }

            $results = $builder->limit($limit * 2)->get();

            // Score and convert to candidates
            $candidates = [];
            foreach ($results as $media) {
                $score = $this->computeRelevanceScore($media, $query);
                $candidates[] = $media->toCandidate($score);
            }

            // Sort by score descending
            usort($candidates, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            return array_slice($candidates, 0, $limit);
        } catch (\Exception $e) {
            Log::warning('ArtimeStockService: Search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Search stock media excluding specific IDs (for per-scene deduplication).
     *
     * @return array Array of candidate arrays
     */
    public function searchExcluding(string $query, int $limit = 6, array $excludeIds = [], ?string $type = null, ?string $orientation = null): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        try {
            $builder = StockMedia::active()->search($query);

            if (!empty($excludeIds)) {
                $builder->whereNotIn('id', $excludeIds);
            }

            if ($type && in_array($type, ['image', 'video'])) {
                $builder->ofType($type);
            }

            if ($orientation && in_array($orientation, ['landscape', 'portrait', 'square'])) {
                $builder->orientation($orientation);
            }

            $results = $builder->limit($limit * 2)->get();

            $candidates = [];
            foreach ($results as $media) {
                $score = $this->computeRelevanceScore($media, $query);
                $candidates[] = $media->toCandidate($score);
            }

            usort($candidates, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            return array_slice($candidates, 0, $limit);
        } catch (\Exception $e) {
            Log::warning('ArtimeStockService: searchExcluding failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Browse media by category.
     *
     * @return array Array of candidate arrays
     */
    public function browseCategory(string $category, int $limit = 12, ?string $type = null): array
    {
        try {
            $builder = StockMedia::active()->inCategory($category);

            if ($type && in_array($type, ['image', 'video'])) {
                $builder->ofType($type);
            }

            $results = $builder->orderBy('title')->limit($limit)->get();

            return $results->map(fn($media) => $media->toCandidate(3.0))->toArray();
        } catch (\Exception $e) {
            Log::warning('ArtimeStockService: Browse failed', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get all categories with counts.
     *
     * @return array ['category_name' => count, ...]
     */
    public function getCategories(): array
    {
        try {
            return StockMedia::active()
                ->selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->orderBy('category')
                ->pluck('total', 'category')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get library statistics.
     */
    public function getStats(): array
    {
        try {
            $total = StockMedia::active()->count();
            $images = StockMedia::active()->ofType('image')->count();
            $videos = StockMedia::active()->ofType('video')->count();
            $categories = StockMedia::active()->distinct('category')->count('category');

            return compact('total', 'images', 'videos', 'categories');
        } catch (\Exception $e) {
            return ['total' => 0, 'images' => 0, 'videos' => 0, 'categories' => 0];
        }
    }

    /**
     * Compute relevance score for a media item against a search query.
     *
     * Base score: 3.0 (to outrank external sources at 1-2)
     * Bonuses: keyword matches in title/tags, orientation match
     */
    public function computeRelevanceScore(StockMedia $media, string $query): float
    {
        $score = 3.0;
        $queryWords = array_filter(preg_split('/\s+/', strtolower($query)), fn($w) => strlen($w) >= 2);

        if (empty($queryWords)) {
            return $score;
        }

        $titleLower = strtolower($media->title ?? '');
        $tagsLower = strtolower($media->tags ?? '');
        $descLower = strtolower($media->description ?? '');

        foreach ($queryWords as $word) {
            // Title match (strongest signal)
            if (str_contains($titleLower, $word)) {
                $score += 1.0;
            }
            // Tags match
            if (str_contains($tagsLower, $word)) {
                $score += 0.5;
            }
            // Description match
            if (str_contains($descLower, $word)) {
                $score += 0.25;
            }
        }

        // Exact title match bonus
        if (str_contains($titleLower, strtolower($query))) {
            $score += 1.5;
        }

        // Portrait orientation bonus (preferred for social video)
        if ($media->orientation === 'portrait') {
            $score += 0.5;
        }

        return round($score, 2);
    }
}
