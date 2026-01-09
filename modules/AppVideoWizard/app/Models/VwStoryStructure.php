<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwStoryStructure extends Model
{
    protected $table = 'vw_story_structures';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'structure_type',
        'act_distribution',
        'pacing_curve',
        'best_for',
        'min_scenes',
        'max_scenes',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'act_distribution' => 'array',
        'pacing_curve' => 'array',
        'best_for' => 'array',
        'min_scenes' => 'integer',
        'max_scenes' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_story_structures';
    const CACHE_TTL = 3600;

    /**
     * Get all active story structures.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($s) => $s->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get the default story structure.
     */
    public static function getDefault(): ?array
    {
        $structure = self::where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$structure) {
            $structure = self::where('is_active', true)
                ->orderBy('sort_order')
                ->first();
        }

        return $structure?->toConfigArray();
    }

    /**
     * Distribute scenes by this structure's acts.
     */
    public function distributeScenes(int $totalScenes): array
    {
        $distribution = $this->act_distribution ?? [];
        $result = [];
        $assigned = 0;

        $acts = array_keys($distribution);
        $lastAct = end($acts);

        foreach ($distribution as $actKey => $actConfig) {
            $percentage = $actConfig['percentage'] ?? 25;

            if ($actKey === $lastAct) {
                // Last act gets remaining scenes
                $sceneCount = $totalScenes - $assigned;
            } else {
                $sceneCount = (int) round($totalScenes * ($percentage / 100));
            }

            $result[$actKey] = [
                'scenes' => $sceneCount,
                'label' => $actConfig['label'] ?? ucfirst($actKey),
                'beats' => $actConfig['beats'] ?? [],
                'startIndex' => $assigned,
                'endIndex' => $assigned + $sceneCount - 1,
            ];

            $assigned += $sceneCount;
        }

        return $result;
    }

    /**
     * Get the act for a specific scene index.
     */
    public function getActForSceneIndex(int $index, int $totalScenes): ?array
    {
        $distribution = $this->distributeScenes($totalScenes);

        foreach ($distribution as $actKey => $actData) {
            if ($index >= $actData['startIndex'] && $index <= $actData['endIndex']) {
                return array_merge(['key' => $actKey], $actData);
            }
        }

        return null;
    }

    /**
     * Get intensity at a specific percentage point.
     */
    public function getIntensityAtPercentage(float $percentage): int
    {
        $curve = $this->pacing_curve ?? [[0, 5], [100, 5]];

        // Find surrounding points and interpolate
        $prev = null;
        $next = null;

        foreach ($curve as $point) {
            if ($point[0] <= $percentage) {
                $prev = $point;
            }
            if ($point[0] >= $percentage && $next === null) {
                $next = $point;
            }
        }

        if (!$prev) $prev = $curve[0];
        if (!$next) $next = end($curve);

        if ($prev[0] === $next[0]) {
            return (int) $prev[1];
        }

        // Linear interpolation
        $ratio = ($percentage - $prev[0]) / ($next[0] - $prev[0]);
        return (int) round($prev[1] + ($next[1] - $prev[1]) * $ratio);
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
            'type' => $this->structure_type,
            'actDistribution' => $this->act_distribution ?? [],
            'pacingCurve' => $this->pacing_curve ?? [],
            'bestFor' => $this->best_for ?? [],
            'sceneRange' => [
                'min' => $this->min_scenes,
                'max' => $this->max_scenes,
            ],
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
