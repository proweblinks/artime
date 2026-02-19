<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentCreative extends Model
{
    protected $table = 'content_creatives';

    protected $fillable = [
        'campaign_id',
        'team_id',
        'type',
        'image_path',
        'image_url',
        'video_path',
        'video_url',
        'header_text',
        'header_font',
        'header_color',
        'header_size',
        'header_height',
        'header_visible',
        'description_text',
        'desc_font',
        'desc_color',
        'desc_size',
        'desc_height',
        'desc_visible',
        'cta_text',
        'cta_font',
        'cta_color',
        'cta_size',
        'cta_visible',
        'current_version',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'header_visible' => 'boolean',
        'desc_visible' => 'boolean',
        'cta_visible' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ContentCampaign::class, 'campaign_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContentCreativeVersion::class, 'creative_id')->orderBy('version_number');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
