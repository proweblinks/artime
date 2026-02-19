<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentCampaign extends Model
{
    protected $table = 'content_campaigns';

    protected $fillable = [
        'team_id',
        'dna_id',
        'title',
        'description',
        'prompt',
        'aspect_ratio',
        'status',
        'is_suggestion',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_suggestion' => 'boolean',
    ];

    public function dna(): BelongsTo
    {
        return $this->belongsTo(ContentBusinessDna::class, 'dna_id');
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(ContentCreative::class, 'campaign_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
