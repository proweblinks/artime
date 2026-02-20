<?php

namespace Modules\AppAnalytics\Services;

use Illuminate\Support\Facades\Cache;
use Modules\AppAnalytics\Models\AnalyticsSnapshot;
use Modules\AppChannels\Models\Accounts;
use Carbon\Carbon;

class AnalyticsCacheService
{
    /**
     * Get data from cache or fetch from API.
     */
    public function getOrFetch(Accounts $account, string $metricType, Carbon $start, Carbon $end, callable $fetcher): array
    {
        $cacheKey = "analytics:{$account->team_id}:{$account->id}:{$metricType}:{$start->format('Ymd')}:{$end->format('Ymd')}";
        $ttl = (int) get_option('analytics_cache_ttl', 360);

        return Cache::remember($cacheKey, $ttl * 60, $fetcher);
    }

    /**
     * Invalidate cache for a specific account.
     */
    public function invalidate(Accounts $account, ?string $metricType = null): void
    {
        // Laravel doesn't natively support tag-less prefix invalidation on all drivers.
        // For now, we rely on TTL expiration. Manual refresh clears specific keys.
        if ($metricType) {
            $pattern = "analytics:{$account->team_id}:{$account->id}:{$metricType}:*";
            // On Redis we could use keys(), but for file/database cache we just let TTL expire.
            Cache::forget($pattern);
        }
    }

    /**
     * Store a daily snapshot for historical data.
     */
    public function storeSnapshot(Accounts $account, Carbon $date, array $metrics, ?array $topPosts = null): void
    {
        AnalyticsSnapshot::updateOrCreate(
            [
                'account_id' => $account->id,
                'snapshot_date' => $date->toDateString(),
            ],
            [
                'team_id' => $account->team_id,
                'social_network' => $account->social_network,
                'metrics' => $metrics,
                'top_posts' => $topPosts,
                'created' => time(),
            ]
        );
    }

    /**
     * Get historical snapshot data for charts.
     */
    public function getSnapshots(int $accountId, Carbon $start, Carbon $end): \Illuminate\Database\Eloquent\Collection
    {
        return AnalyticsSnapshot::where('account_id', $accountId)
            ->whereBetween('snapshot_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('snapshot_date')
            ->get();
    }
}
