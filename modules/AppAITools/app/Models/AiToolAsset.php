<?php

namespace Modules\AppAITools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolAsset extends Model
{
    public $timestamps = false;

    protected $table = 'ai_tool_assets';

    protected $fillable = [
        'history_id',
        'type',
        'file_path',
        'metadata',
        'created',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->created)) {
                $model->created = time();
            }
        });
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(AiToolHistory::class, 'history_id');
    }
}
