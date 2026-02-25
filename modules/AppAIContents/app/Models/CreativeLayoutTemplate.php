<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreativeLayoutTemplate extends Model
{
    protected $table = 'creative_layout_templates';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'config',
        'preview_thumbnail',
        'is_active',
        'sort_order',
        'supported_aspects',
    ];

    protected $casts = [
        'config' => 'array',
        'supported_aspects' => 'array',
        'is_active' => 'boolean',
    ];

    public function creatives(): HasMany
    {
        return $this->hasMany(ContentCreative::class, 'layout_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAspect($query, string $ratio)
    {
        return $query->where(function ($q) use ($ratio) {
            $q->whereNull('supported_aspects')
              ->orWhereJsonContains('supported_aspects', $ratio);
        });
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
