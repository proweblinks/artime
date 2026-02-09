<?php

namespace Modules\AppAITools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiToolHistory extends Model
{
    public $timestamps = false;

    protected $table = 'ai_tool_history';

    protected $fillable = [
        'id_secure',
        'team_id',
        'user_id',
        'tool',
        'platform',
        'title',
        'input_data',
        'result_data',
        'status',
        'credits_used',
        'created',
        'changed',
    ];

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
        'status' => 'integer',
        'credits_used' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id_secure)) {
                $model->id_secure = Str::random(32);
            }
            if (empty($model->created)) {
                $model->created = time();
            }
            $model->changed = time();
        });

        static::updating(function ($model) {
            $model->changed = time();
        });
    }

    public function assets(): HasMany
    {
        return $this->hasMany(AiToolAsset::class, 'history_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForTool($query, string $tool)
    {
        return $query->where('tool', $tool);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 1);
    }
}
