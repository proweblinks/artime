<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwGenrePreset extends Model
{
    protected $table = 'vw_genre_presets';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'camera_language',
        'color_grade',
        'lighting',
        'atmosphere',
        'style',
        'lens_preferences',
        'prompt_prefix',
        'prompt_suffix',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'lens_preferences' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_genre_presets';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all active genre presets with caching.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(function ($preset) {
                    return [
                        'id' => $preset->slug,
                        'name' => $preset->name,
                        'category' => $preset->category,
                        'camera' => $preset->camera_language,
                        'colorGrade' => $preset->color_grade,
                        'lighting' => $preset->lighting,
                        'atmosphere' => $preset->atmosphere,
                        'style' => $preset->style,
                        'lensPreferences' => $preset->lens_preferences ?? [],
                        'promptPrefix' => $preset->prompt_prefix,
                        'promptSuffix' => $preset->prompt_suffix,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get genre preset by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $presets = self::getAllActive();
        return $presets[$slug] ?? null;
    }

    /**
     * Get the default genre preset.
     */
    public static function getDefault(): ?array
    {
        $preset = self::where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$preset) {
            // Fallback to first active preset
            $preset = self::where('is_active', true)
                ->orderBy('sort_order')
                ->first();
        }

        return $preset ? self::getBySlug($preset->slug) : null;
    }

    /**
     * Get presets grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return self::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($group) {
                return $group->map(function ($preset) {
                    return [
                        'id' => $preset->slug,
                        'name' => $preset->name,
                        'style' => $preset->style,
                    ];
                })->toArray();
            })
            ->toArray();
    }

    /**
     * Get lens for a specific shot type from this preset.
     */
    public function getLensForShotType(string $shotType): ?string
    {
        return $this->lens_preferences[$shotType] ?? null;
    }

    /**
     * Clear the genre presets cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Scope to filter active presets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
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

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
