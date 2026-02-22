<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwSeedanceStyle extends Model
{
    protected $table = 'vw_seedance_styles';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'prompt_syntax',
        'compatible_genres',
        'compatible_moods',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'compatible_genres' => 'array',
        'compatible_moods' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_seedance_styles';
    const CACHE_TTL = 3600;

    const CATEGORY_VISUAL = 'visual';
    const CATEGORY_LIGHTING = 'lighting';
    const CATEGORY_COLOR = 'color';

    /**
     * Get all active styles with caching, keyed by slug.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($style) => $style->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get a style by slug from cache.
     */
    public static function getBySlug(string $slug): ?array
    {
        $all = self::getAllActive();
        return $all[$slug] ?? null;
    }

    /**
     * Get styles by category.
     */
    public static function getByCategory(string $category): array
    {
        $cacheKey = self::CACHE_KEY . '_' . $category;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            return self::where('is_active', true)
                ->where('category', $category)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($style) => $style->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get the default style for a category (or first active).
     */
    public static function getDefault(string $category): ?array
    {
        $styles = self::getByCategory($category);
        foreach ($styles as $style) {
            if ($style['isDefault'] ?? false) {
                return $style;
            }
        }
        return $styles[0] ?? null;
    }

    /**
     * Convert to config array format.
     */
    public function toConfigArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'categoryLabel' => $this->getCategoryLabel(),
            'description' => $this->description,
            'promptSyntax' => $this->prompt_syntax,
            'compatibleGenres' => $this->compatible_genres ?? [],
            'compatibleMoods' => $this->compatible_moods ?? [],
            'isDefault' => $this->is_default,
        ];
    }

    /**
     * Get human-readable category label.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_VISUAL => 'Visual Style',
            self::CATEGORY_LIGHTING => 'Lighting',
            self::CATEGORY_COLOR => 'Color Treatment',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get category options for forms.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_VISUAL => 'Visual Style',
            self::CATEGORY_LIGHTING => 'Lighting',
            self::CATEGORY_COLOR => 'Color Treatment',
        ];
    }

    /**
     * Clear all caches.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_' . self::CATEGORY_VISUAL);
        Cache::forget(self::CACHE_KEY . '_' . self::CATEGORY_LIGHTING);
        Cache::forget(self::CACHE_KEY . '_' . self::CATEGORY_COLOR);
    }

    /**
     * Scope: active styles only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }
}
