<?php

namespace Modules\AppAnalytics\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    public $timestamps = false;
    protected $table = 'analytics_snapshots';
    protected $guarded = [];

    protected $casts = [
        'metrics' => 'array',
        'top_posts' => 'array',
        'snapshot_date' => 'date',
    ];

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('social_network', $platform);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }
}
