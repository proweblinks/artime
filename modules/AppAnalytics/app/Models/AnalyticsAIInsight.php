<?php

namespace Modules\AppAnalytics\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsAIInsight extends Model
{
    public $timestamps = false;
    protected $table = 'analytics_ai_insights';
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('insight_type', $type);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created');
    }
}
