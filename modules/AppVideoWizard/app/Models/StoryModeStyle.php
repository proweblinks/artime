<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryModeStyle extends Model
{
    protected $table = 'story_mode_styles';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'thumbnail_path',
        'thumbnail_url',
        'style_instruction',
        'style_reference_image',
        'config',
        'is_active',
        'is_system',
        'sort_order',
        'usage_count',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Get the projects using this style.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(StoryModeProject::class, 'style_id');
    }

    /**
     * Scope: only active styles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only system (built-in) styles.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Increment usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
