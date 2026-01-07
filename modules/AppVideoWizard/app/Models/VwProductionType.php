<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class VwProductionType extends Model
{
    protected $table = 'vw_production_types';

    protected $fillable = [
        'parent_id',
        'slug',
        'name',
        'icon',
        'description',
        'characteristics',
        'default_narration',
        'suggested_duration_min',
        'suggested_duration_max',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'characteristics' => 'array',
        'suggested_duration_min' => 'integer',
        'suggested_duration_max' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    const CACHE_KEY = 'vw_production_types_tree';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the parent production type.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child production types (subtypes).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all active subtypes.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Check if this is a parent (main) type.
     */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this is a subtype.
     */
    public function isSubtype(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the full tree of production types with caching.
     */
    public static function getTree(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with(['activeChildren'])
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->slug,
                        'name' => $type->name,
                        'icon' => $type->icon,
                        'description' => $type->description,
                        'subTypes' => $type->activeChildren->map(function ($subtype) {
                            return [
                                'id' => $subtype->slug,
                                'name' => $subtype->name,
                                'icon' => $subtype->icon,
                                'description' => $subtype->description,
                                'characteristics' => $subtype->characteristics ?? [],
                                'defaultNarration' => $subtype->default_narration,
                                'suggestedDuration' => [
                                    'min' => $subtype->suggested_duration_min,
                                    'max' => $subtype->suggested_duration_max,
                                ],
                            ];
                        })->keyBy('id')->toArray(),
                    ];
                })
                ->keyBy('id')
                ->toArray();
        });
    }

    /**
     * Get production types as config format (for backwards compatibility).
     */
    public static function getAsConfig(): array
    {
        return self::getTree();
    }

    /**
     * Find a production type by slug (including subtypes).
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Get suggested duration range.
     */
    public function getSuggestedDurationAttribute(): ?array
    {
        if ($this->suggested_duration_min === null && $this->suggested_duration_max === null) {
            return null;
        }

        return [
            'min' => $this->suggested_duration_min,
            'max' => $this->suggested_duration_max,
        ];
    }

    /**
     * Clear the production types cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Scope to filter active types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only parent types.
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only subtypes.
     */
    public function scopeSubtypes($query)
    {
        return $query->whereNotNull('parent_id');
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
