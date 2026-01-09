<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwEmotionalBeat extends Model
{
    protected $table = 'vw_emotional_beats';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'story_position',
        'intensity_level',
        'pacing_suggestion',
        'color_mood',
        'recommended_shot_types',
        'recommended_camera_movements',
        'recommended_lenses',
        'atmosphere_keywords',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'recommended_shot_types' => 'array',
        'recommended_camera_movements' => 'array',
        'recommended_lenses' => 'array',
        'intensity_level' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_emotional_beats';
    const CACHE_TTL = 3600;

    /**
     * Get all active emotional beats.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($beat) => $beat->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get beats by story position (three-act structure).
     */
    public static function getByStoryPosition(string $position): array
    {
        return self::where('is_active', true)
            ->where('story_position', $position)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($beat) => $beat->toConfigArray())
            ->toArray();
    }

    /**
     * Get beats grouped by act.
     */
    public static function getGroupedByAct(): array
    {
        $acts = [
            'act1' => ['act1_setup', 'act1_catalyst'],
            'act2' => ['act2_rising', 'act2_midpoint', 'act2_crisis'],
            'act3' => ['act3_climax', 'act3_resolution'],
            'standalone' => ['standalone'],
        ];

        $result = [];
        foreach ($acts as $act => $positions) {
            $result[$act] = self::where('is_active', true)
                ->whereIn('story_position', $positions)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($beat) => $beat->toConfigArray())
                ->toArray();
        }

        return $result;
    }

    /**
     * Get recommended shot type for this beat.
     */
    public function getRecommendedShotType(): ?string
    {
        $shots = $this->recommended_shot_types ?? [];
        return $shots[0] ?? null;
    }

    /**
     * Get recommended camera movement.
     */
    public function getRecommendedCameraMovement(): ?string
    {
        $movements = $this->recommended_camera_movements ?? [];
        return $movements[0] ?? null;
    }

    /**
     * Convert to config array.
     */
    public function toConfigArray(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'storyPosition' => $this->story_position,
            'intensity' => $this->intensity_level,
            'pacing' => $this->pacing_suggestion,
            'colorMood' => $this->color_mood,
            'recommendedShots' => $this->recommended_shot_types ?? [],
            'recommendedMovements' => $this->recommended_camera_movements ?? [],
            'recommendedLenses' => $this->recommended_lenses ?? [],
            'atmosphereKeywords' => $this->atmosphere_keywords,
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

    protected static function boot()
    {
        parent::boot();
        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }
}
