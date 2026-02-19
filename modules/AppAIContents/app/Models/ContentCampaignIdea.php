<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCampaignIdea extends Model
{
    protected $table = 'content_campaign_ideas';

    protected $fillable = [
        'team_id',
        'dna_id',
        'title',
        'description',
        'prompt',
        'is_dna_suggestion',
        'status',
    ];

    protected $casts = [
        'is_dna_suggestion' => 'boolean',
    ];

    public function dna(): BelongsTo
    {
        return $this->belongsTo(ContentBusinessDna::class, 'dna_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
