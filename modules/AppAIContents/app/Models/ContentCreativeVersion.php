<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCreativeVersion extends Model
{
    protected $table = 'content_creative_versions';

    public $timestamps = false;

    protected $fillable = [
        'creative_id',
        'version_number',
        'image_path',
        'image_url',
        'header_text',
        'description_text',
        'cta_text',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function creative(): BelongsTo
    {
        return $this->belongsTo(ContentCreative::class, 'creative_id');
    }
}
