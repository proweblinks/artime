<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPhotoshoot extends Model
{
    protected $table = 'content_photoshoots';

    protected $fillable = [
        'team_id',
        'dna_id',
        'type',
        'prompt',
        'product_image_path',
        'template_id',
        'aspect_ratio',
        'status',
        'results',
    ];

    protected $casts = [
        'results' => 'array',
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
