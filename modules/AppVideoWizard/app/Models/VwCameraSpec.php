<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwCameraSpec extends Model
{
    protected $table = 'vw_camera_specs';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'focal_length',
        'aperture',
        'characteristics',
        'look_description',
        'prompt_text',
        'best_for_shots',
        'best_for_genres',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'best_for_shots' => 'array',
        'best_for_genres' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_camera_specs';
    const CACHE_TTL = 3600;

    /**
     * Get all active camera specs.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($spec) => $spec->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get camera specs grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return self::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(fn($group) => $group->map(fn($spec) => $spec->toConfigArray())->toArray())
            ->toArray();
    }

    /**
     * Get lenses only.
     */
    public static function getLenses(): array
    {
        return self::where('is_active', true)
            ->where('category', 'lens')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($spec) => $spec->toConfigArray())
            ->toArray();
    }

    /**
     * Get film stocks/looks.
     */
    public static function getFilmStocks(): array
    {
        return self::where('is_active', true)
            ->where('category', 'film_stock')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($spec) => $spec->toConfigArray())
            ->toArray();
    }

    /**
     * Get best lens for a shot type.
     */
    public static function getBestLensForShot(string $shotType): ?array
    {
        $lens = self::where('is_active', true)
            ->where('category', 'lens')
            ->whereJsonContains('best_for_shots', $shotType)
            ->orderBy('sort_order')
            ->first();

        return $lens?->toConfigArray();
    }

    /**
     * Get best specs for a genre.
     */
    public static function getForGenre(string $genre): array
    {
        return self::where('is_active', true)
            ->whereJsonContains('best_for_genres', $genre)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($spec) => $spec->toConfigArray())
            ->toArray();
    }

    /**
     * Convert to config array.
     */
    public function toConfigArray(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'focalLength' => $this->focal_length,
            'aperture' => $this->aperture,
            'characteristics' => $this->characteristics,
            'lookDescription' => $this->look_description,
            'promptText' => $this->prompt_text,
            'bestForShots' => $this->best_for_shots ?? [],
            'bestForGenres' => $this->best_for_genres ?? [],
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }
}
