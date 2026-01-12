<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Camera Movement Model
 *
 * Represents camera movement presets for video animation prompts.
 * Based on Higgsfield's 50+ cinematic motion presets.
 */
class VwCameraMovement extends Model
{
    protected $table = 'vw_camera_movements';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'prompt_syntax',
        'intensity',
        'typical_duration_min',
        'typical_duration_max',
        'stackable_with',
        'best_for_shot_types',
        'best_for_emotions',
        'natural_continuation',
        'ending_state',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'stackable_with' => 'array',
        'best_for_shot_types' => 'array',
        'best_for_emotions' => 'array',
        'typical_duration_min' => 'integer',
        'typical_duration_max' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_camera_movements';
    const CACHE_TTL = 3600;

    // Category constants
    const CATEGORY_ZOOM = 'zoom';
    const CATEGORY_DOLLY = 'dolly';
    const CATEGORY_CRANE = 'crane';
    const CATEGORY_PAN_TILT = 'pan_tilt';
    const CATEGORY_ARC = 'arc';
    const CATEGORY_SPECIALTY = 'specialty';

    // Intensity constants
    const INTENSITY_SUBTLE = 'subtle';
    const INTENSITY_MODERATE = 'moderate';
    const INTENSITY_DYNAMIC = 'dynamic';
    const INTENSITY_INTENSE = 'intense';

    /**
     * Get all active movements with caching.
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($movement) => $movement->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get movements grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        $cacheKey = self::CACHE_KEY . '_by_category';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category')
                ->map(fn($group) => $group->map(fn($m) => $m->toConfigArray())->toArray())
                ->toArray();
        });
    }

    /**
     * Get a movement by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $all = self::getAllActive();
        return $all[$slug] ?? null;
    }

    /**
     * Get movements suitable for a shot type.
     */
    public static function getForShotType(string $shotType): array
    {
        return self::where('is_active', true)
            ->where(function ($query) use ($shotType) {
                $query->whereJsonContains('best_for_shot_types', $shotType)
                      ->orWhereNull('best_for_shot_types');
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => $m->toConfigArray())
            ->toArray();
    }

    /**
     * Get movements suitable for an emotional purpose.
     */
    public static function getForEmotion(string $emotion): array
    {
        return self::where('is_active', true)
            ->whereJsonContains('best_for_emotions', $emotion)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => $m->toConfigArray())
            ->toArray();
    }

    /**
     * Get movements by intensity level.
     */
    public static function getByIntensity(string $intensity): array
    {
        return self::where('is_active', true)
            ->where('intensity', $intensity)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => $m->toConfigArray())
            ->toArray();
    }

    /**
     * Check if this movement can stack with another.
     */
    public function canStackWith(string $otherSlug): bool
    {
        $stackable = $this->stackable_with ?? [];
        return in_array($otherSlug, $stackable);
    }

    /**
     * Get compatible movements for stacking.
     */
    public function getStackableMovements(): array
    {
        $stackable = $this->stackable_with ?? [];
        if (empty($stackable)) {
            return [];
        }

        return self::where('is_active', true)
            ->whereIn('slug', $stackable)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => $m->toConfigArray())
            ->toArray();
    }

    /**
     * Build stacked movement prompt syntax.
     *
     * @param string|null $secondarySlug Secondary movement slug
     * @return string Combined prompt syntax
     */
    public function buildStackedPrompt(?string $secondarySlug = null): string
    {
        $prompt = $this->prompt_syntax;

        if ($secondarySlug && $this->canStackWith($secondarySlug)) {
            $secondary = self::where('slug', $secondarySlug)->first();
            if ($secondary) {
                $prompt .= ' while ' . $secondary->prompt_syntax;
            }
        }

        return $prompt;
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
            'intensity' => $this->intensity,
            'intensityLabel' => $this->getIntensityLabel(),
            'durationRange' => [
                'min' => $this->typical_duration_min,
                'max' => $this->typical_duration_max,
            ],
            'stackableWith' => $this->stackable_with ?? [],
            'bestForShotTypes' => $this->best_for_shot_types ?? [],
            'bestForEmotions' => $this->best_for_emotions ?? [],
            'naturalContinuation' => $this->natural_continuation,
            'endingState' => $this->ending_state,
        ];
    }

    /**
     * Get human-readable category label.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_ZOOM => 'Zoom',
            self::CATEGORY_DOLLY => 'Dolly',
            self::CATEGORY_CRANE => 'Crane',
            self::CATEGORY_PAN_TILT => 'Pan & Tilt',
            self::CATEGORY_ARC => 'Arc & Orbit',
            self::CATEGORY_SPECIALTY => 'Specialty',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get human-readable intensity label.
     */
    public function getIntensityLabel(): string
    {
        return match ($this->intensity) {
            self::INTENSITY_SUBTLE => 'Subtle',
            self::INTENSITY_MODERATE => 'Moderate',
            self::INTENSITY_DYNAMIC => 'Dynamic',
            self::INTENSITY_INTENSE => 'Intense',
            default => ucfirst($this->intensity),
        };
    }

    /**
     * Get category options for forms.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_ZOOM => 'Zoom',
            self::CATEGORY_DOLLY => 'Dolly',
            self::CATEGORY_CRANE => 'Crane',
            self::CATEGORY_PAN_TILT => 'Pan & Tilt',
            self::CATEGORY_ARC => 'Arc & Orbit',
            self::CATEGORY_SPECIALTY => 'Specialty',
        ];
    }

    /**
     * Get intensity options for forms.
     */
    public static function getIntensityOptions(): array
    {
        return [
            self::INTENSITY_SUBTLE => 'Subtle',
            self::INTENSITY_MODERATE => 'Moderate',
            self::INTENSITY_DYNAMIC => 'Dynamic',
            self::INTENSITY_INTENSE => 'Intense',
        ];
    }

    /**
     * Clear all caches.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_by_category');
    }

    /**
     * Scope: active movements only.
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
     * Scope: filter by intensity.
     */
    public function scopeIntensity($query, string $intensity)
    {
        return $query->where('intensity', $intensity);
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
