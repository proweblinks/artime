<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwShotType extends Model
{
    protected $table = 'vw_shot_types';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'camera_specs',
        'default_lens',
        'default_aperture',
        'typical_duration_min',
        'typical_duration_max',
        'emotional_beats',
        'best_for_genres',
        'prompt_template',
        'motion_description',
        'compatible_transitions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'emotional_beats' => 'array',
        'best_for_genres' => 'array',
        'compatible_transitions' => 'array',
        'typical_duration_min' => 'integer',
        'typical_duration_max' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_shot_types';
    const CACHE_TTL = 3600;

    /**
     * Get all active shot types with caching.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($shot) => $shot->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get shot types grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return self::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(fn($group) => $group->map(fn($shot) => $shot->toConfigArray())->toArray())
            ->toArray();
    }

    /**
     * Get shot types suitable for an emotional beat.
     */
    public static function getForEmotionalBeat(string $beat): array
    {
        return self::where('is_active', true)
            ->whereJsonContains('emotional_beats', $beat)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($shot) => $shot->toConfigArray())
            ->toArray();
    }

    /**
     * Get shot types suitable for a genre.
     */
    public static function getForGenre(string $genre): array
    {
        return self::where('is_active', true)
            ->whereJsonContains('best_for_genres', $genre)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($shot) => $shot->toConfigArray())
            ->toArray();
    }

    /**
     * Get compatible next shots (30-degree rule).
     */
    public function getCompatibleTransitions(): array
    {
        return $this->compatible_transitions ?? [];
    }

    /**
     * Check if this shot can transition to another.
     */
    public function canTransitionTo(string $nextShotSlug): bool
    {
        $compatible = $this->getCompatibleTransitions();
        return empty($compatible) || in_array($nextShotSlug, $compatible);
    }

    /**
     * Convert to config array format.
     */
    public function toConfigArray(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'cameraSpecs' => $this->camera_specs,
            'defaultLens' => $this->default_lens,
            'defaultAperture' => $this->default_aperture,
            'durationRange' => [
                'min' => $this->typical_duration_min,
                'max' => $this->typical_duration_max,
            ],
            'emotionalBeats' => $this->emotional_beats ?? [],
            'bestForGenres' => $this->best_for_genres ?? [],
            'promptTemplate' => $this->prompt_template,
            'motionDescription' => $this->motion_description,
            'compatibleTransitions' => $this->compatible_transitions ?? [],
        ];
    }

    /**
     * Clear cache.
     */
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
