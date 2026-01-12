<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * VwCoveragePattern - Coverage patterns for scene type detection.
 *
 * Defines professional shot sequences for different scene types:
 * - Dialogue: Master → Two-Shot → OTS A → OTS B → CU A → CU B → Reaction
 * - Action: Establishing → Wide → Tracking → Medium → Close-up → Insert
 * - Emotional: Wide → Medium → Close-up → Extreme Close-up
 * - Montage: Mix of establishing, medium, close-up, detail shots
 * - Establishing: Extreme Wide → Wide → Medium Wide
 */
class VwCoveragePattern extends Model
{
    protected $table = 'vw_coverage_patterns';

    protected $fillable = [
        'slug',
        'name',
        'scene_type',
        'description',
        'shot_sequence',
        'detection_keywords',
        'negative_keywords',
        'visual_cues',
        'recommended_pacing',
        'min_shots',
        'max_shots',
        'typical_shot_duration',
        'transition_rules',
        'default_movement_intensity',
        'preferred_movements',
        'usage_count',
        'success_rate',
        'is_active',
        'is_system',
        'sort_order',
        'priority',
    ];

    protected $casts = [
        'shot_sequence' => 'array',
        'detection_keywords' => 'array',
        'negative_keywords' => 'array',
        'visual_cues' => 'array',
        'transition_rules' => 'array',
        'preferred_movements' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'min_shots' => 'integer',
        'max_shots' => 'integer',
        'typical_shot_duration' => 'integer',
        'usage_count' => 'integer',
        'success_rate' => 'float',
        'sort_order' => 'integer',
        'priority' => 'integer',
    ];

    const CACHE_KEY = 'vw_coverage_patterns';
    const CACHE_TTL = 3600;

    /**
     * Scene type constants.
     */
    const SCENE_TYPE_DIALOGUE = 'dialogue';
    const SCENE_TYPE_ACTION = 'action';
    const SCENE_TYPE_EMOTIONAL = 'emotional';
    const SCENE_TYPE_MONTAGE = 'montage';
    const SCENE_TYPE_ESTABLISHING = 'establishing';
    const SCENE_TYPE_TRANSITION = 'transition';
    const SCENE_TYPE_FLASHBACK = 'flashback';
    const SCENE_TYPE_DREAM = 'dream';
    const SCENE_TYPE_INTERVIEW = 'interview';
    const SCENE_TYPE_DOCUMENTARY = 'documentary';

    /**
     * Pacing constants.
     */
    const PACING_SLOW = 'slow';
    const PACING_BALANCED = 'balanced';
    const PACING_FAST = 'fast';
    const PACING_DYNAMIC = 'dynamic';

    /**
     * Get all active patterns with caching.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY . '_active', self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->toArray();
        });
    }

    /**
     * Get patterns by scene type.
     */
    public static function getBySceneType(string $sceneType): array
    {
        return Cache::remember(self::CACHE_KEY . '_type_' . $sceneType, self::CACHE_TTL, function () use ($sceneType) {
            return self::where('scene_type', $sceneType)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get pattern by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $patterns = self::getAllActive();
        return $patterns[$slug] ?? null;
    }

    /**
     * Get available scene types.
     */
    public static function getSceneTypes(): array
    {
        return [
            self::SCENE_TYPE_DIALOGUE => 'Dialogue',
            self::SCENE_TYPE_ACTION => 'Action',
            self::SCENE_TYPE_EMOTIONAL => 'Emotional',
            self::SCENE_TYPE_MONTAGE => 'Montage',
            self::SCENE_TYPE_ESTABLISHING => 'Establishing',
            self::SCENE_TYPE_TRANSITION => 'Transition',
            self::SCENE_TYPE_FLASHBACK => 'Flashback',
            self::SCENE_TYPE_DREAM => 'Dream',
            self::SCENE_TYPE_INTERVIEW => 'Interview',
            self::SCENE_TYPE_DOCUMENTARY => 'Documentary',
        ];
    }

    /**
     * Get pacing options.
     */
    public static function getPacingOptions(): array
    {
        return [
            self::PACING_SLOW => 'Slow (contemplative)',
            self::PACING_BALANCED => 'Balanced (standard)',
            self::PACING_FAST => 'Fast (energetic)',
            self::PACING_DYNAMIC => 'Dynamic (variable)',
        ];
    }

    /**
     * Get shot sequence as display-friendly array.
     */
    public function getFormattedSequence(): array
    {
        $sequence = $this->shot_sequence ?? [];
        $result = [];

        foreach ($sequence as $index => $shotType) {
            $result[] = [
                'order' => $index + 1,
                'type' => $shotType,
                'name' => ucwords(str_replace('-', ' ', $shotType)),
            ];
        }

        return $result;
    }

    /**
     * Match text against detection keywords.
     * Returns match score (0-100).
     */
    public function matchKeywords(string $text): int
    {
        $text = strtolower($text);
        $score = 0;
        $maxScore = 100;

        // Check negative keywords first
        $negativeKeywords = $this->negative_keywords ?? [];
        foreach ($negativeKeywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return 0; // Negative match excludes this pattern
            }
        }

        // Check positive keywords
        $keywords = $this->detection_keywords ?? [];
        if (empty($keywords)) {
            return 50; // No keywords = neutral match
        }

        $matchCount = 0;
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $matchCount++;
            }
        }

        // Calculate score based on match ratio
        $score = (int) (($matchCount / count($keywords)) * $maxScore);

        // Boost score based on priority
        $priorityBoost = ($this->priority - 50) / 5; // -10 to +10
        $score = min(100, max(0, $score + $priorityBoost));

        return $score;
    }

    /**
     * Increment usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Update success rate based on feedback.
     */
    public function updateSuccessRate(bool $wasSuccessful): void
    {
        $currentRate = $this->success_rate ?? 50.0;
        $usageCount = $this->usage_count;

        // Weighted average: new rate = (old_rate * old_count + new_value) / new_count
        $newValue = $wasSuccessful ? 100 : 0;
        $newRate = (($currentRate * ($usageCount - 1)) + $newValue) / $usageCount;

        $this->update(['success_rate' => round($newRate, 2)]);
    }

    /**
     * Scope for active patterns.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for scene type.
     */
    public function scopeForSceneType($query, string $sceneType)
    {
        return $query->where('scene_type', $sceneType);
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('sort_order');
    }

    /**
     * Clear cache.
     */
    public static function clearCache(): void
    {
        $sceneTypes = array_keys(self::getSceneTypes());

        Cache::forget(self::CACHE_KEY . '_active');
        foreach ($sceneTypes as $type) {
            Cache::forget(self::CACHE_KEY . '_type_' . $type);
        }
    }

    /**
     * Boot model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }
}
