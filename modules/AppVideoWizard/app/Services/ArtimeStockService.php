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
    public function search(string $query, int $limit = 6, int $offset = 0, ?string $type = null, ?string $orientation = null): array
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

            $fetchLimit = ($limit + $offset) * 2;
            $results = $builder->limit($fetchLimit)->get();

            // Score and convert to candidates
            $candidates = [];
            foreach ($results as $media) {
                $score = $this->computeRelevanceScore($media, $query);
                $candidates[] = $media->toCandidate($score);
            }

            // Sort by score descending
            usort($candidates, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            return array_slice($candidates, $offset, $limit);
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
    public function browseCategory(string $category, int $limit = 12, int $offset = 0, ?string $type = null): array
    {
        try {
            $builder = StockMedia::active()->inCategory($category);

            if ($type && in_array($type, ['image', 'video'])) {
                $builder->ofType($type);
            }

            $results = $builder->orderBy('title')->offset($offset)->limit($limit)->get();

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
     * Find the best matching stock category for a search query.
     * Checks direct category name match, then keyword-to-category mapping.
     */
    public function findMatchingCategory(string $query): ?string
    {
        $queryWords = array_filter(
            preg_split('/[\s,\-:]+/', strtolower(trim($query))),
            fn($w) => strlen($w) >= 3
        );

        if (empty($queryWords)) {
            return null;
        }

        $categories = array_keys($this->getCategories());

        // Direct match: query word IS a category name
        foreach ($queryWords as $word) {
            foreach ($categories as $cat) {
                if ($cat === $word) {
                    return $cat;
                }
            }
        }

        // Keyword-to-category mapping for common search terms
        $keywordMap = [
            'ocean' => 'nature', 'sea' => 'nature', 'forest' => 'nature',
            'mountain' => 'nature', 'aurora' => 'nature', 'sunset' => 'nature',
            'beach' => 'nature', 'reef' => 'nature', 'wildlife' => 'nature',
            'river' => 'nature', 'waterfall' => 'nature', 'landscape' => 'nature',
            'clouds' => 'nature', 'rain' => 'nature', 'snow' => 'nature',
            'desert' => 'nature', 'jungle' => 'nature', 'flowers' => 'nature',
            'underwater' => 'nature', 'coral' => 'nature', 'volcano' => 'nature',
            'planet' => 'space', 'galaxy' => 'space', 'stars' => 'space',
            'moon' => 'space', 'astronaut' => 'space', 'rocket' => 'space',
            'nasa' => 'space', 'cosmos' => 'space', 'nebula' => 'space',
            'orbit' => 'space', 'satellite' => 'space', 'mars' => 'space',
            'trip' => 'travel', 'vacation' => 'travel', 'adventure' => 'travel',
            'tourism' => 'travel', 'destination' => 'travel', 'explore' => 'travel',
            'journey' => 'travel', 'wanderlust' => 'travel', 'backpacking' => 'travel',
            'sightseeing' => 'travel', 'airport' => 'travel', 'passport' => 'travel',
            'car' => 'cars', 'vehicle' => 'cars', 'driving' => 'cars',
            'racing' => 'cars', 'supercar' => 'cars', 'automobile' => 'cars',
            'food' => 'cooking', 'recipe' => 'cooking', 'chef' => 'cooking',
            'kitchen' => 'cooking', 'baking' => 'cooking', 'meal' => 'cooking',
            'cuisine' => 'cooking', 'restaurant' => 'cooking',
            'cat' => 'cats', 'kitten' => 'cats', 'feline' => 'cats',
            'gym' => 'fitness', 'workout' => 'fitness', 'exercise' => 'fitness',
            'muscle' => 'fitness', 'training' => 'fitness', 'yoga' => 'fitness',
            'rich' => 'luxury', 'wealth' => 'luxury', 'expensive' => 'luxury',
            'mansion' => 'luxury', 'diamond' => 'luxury', 'yacht' => 'luxury',
            'fashion' => 'luxury', 'designer' => 'luxury', 'lux' => 'luxury',
            'luxe' => 'luxury', 'opulent' => 'luxury', 'premium' => 'luxury',
            'elegant' => 'luxury', 'covetable' => 'luxury', 'glamour' => 'luxury',
            'painting' => 'art-craft', 'drawing' => 'art-craft', 'craft' => 'art-craft',
            'sculpture' => 'art-craft', 'pottery' => 'art-craft',
            'asmr' => 'satisfying', 'slime' => 'satisfying',
        ];

        foreach ($queryWords as $word) {
            if (isset($keywordMap[$word])) {
                return $keywordMap[$word];
            }
        }

        return null;
    }

    /**
     * Browse media in a category, excluding specific IDs.
     */
    public function browseCategoryExcluding(string $category, int $limit = 8, array $excludeIds = []): array
    {
        try {
            $builder = StockMedia::active()->inCategory($category);

            if (!empty($excludeIds)) {
                $builder->whereNotIn('id', $excludeIds);
            }

            $results = $builder->inRandomOrder()->limit($limit)->get();

            return $results->map(fn($media) => $media->toCandidate(5.0))->toArray();
        } catch (\Exception $e) {
            Log::warning('ArtimeStockService: browseCategoryExcluding failed', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Compute relevance score for a media item against a search query.
     *
     * Base score: 3.0 (to outrank external sources at 1-2)
     * Bonuses: category match, keyword matches in title/tags, orientation match
     */
    public function computeRelevanceScore(StockMedia $media, string $query): float
    {
        $score = 3.0;
        $queryWords = array_filter(preg_split('/\s+/', strtolower($query)), fn($w) => strlen($w) >= 2);

        if (empty($queryWords)) {
            return $score;
        }

        // Category match bonus (strongest signal for curated library)
        $categoryLower = strtolower($media->category ?? '');
        foreach ($queryWords as $word) {
            if ($categoryLower === $word) {
                $score += 3.0;
                break;
            }
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
