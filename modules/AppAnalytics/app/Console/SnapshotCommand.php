<?php

namespace Modules\AppAnalytics\Console;

use Illuminate\Console\Command;
use Modules\AppChannels\Models\Accounts;
use Modules\AppAnalytics\Services\AnalyticsCacheService;
use Modules\AppAnalytics\Services\FacebookAnalyticsService;
use Modules\AppAnalytics\Services\InstagramAnalyticsService;
use Modules\AppAnalytics\Services\YoutubeAnalyticsService;
use Modules\AppAnalytics\Services\LinkedinAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SnapshotCommand extends Command
{
    protected $signature = 'appanalytics:snapshots';
    protected $description = 'Capture daily analytics snapshots for all active accounts';

    public function handle(): int
    {
        if (!get_option('analytics_daily_snapshots', 1)) {
            $this->info('Daily snapshots are disabled.');
            return 0;
        }

        $yesterday = Carbon::yesterday();
        $cacheService = new AnalyticsCacheService();

        $accounts = Accounts::where('status', 1)
            ->whereIn('social_network', ['facebook', 'instagram', 'youtube', 'linkedin'])
            ->get();

        $this->info("Processing {$accounts->count()} accounts...");

        foreach ($accounts as $account) {
            try {
                $service = $this->getServiceForPlatform($account->social_network);
                if (!$service) {
                    continue;
                }

                $data = $service->getOverview($account, $yesterday, $yesterday);

                if ($data['success'] ?? false) {
                    $metrics = $this->extractMetrics($account->social_network, $data);
                    $cacheService->storeSnapshot($account, $yesterday, $metrics);
                    $this->info("Snapshot saved for {$account->social_network} account #{$account->id}");
                }
            } catch (\Exception $e) {
                Log::warning("Analytics snapshot failed for account #{$account->id}: " . $e->getMessage());
                $this->warn("Failed for account #{$account->id}: " . $e->getMessage());
            }
        }

        $this->info('Daily snapshots complete.');
        return 0;
    }

    protected function getServiceForPlatform(string $platform): ?object
    {
        return match ($platform) {
            'facebook' => new FacebookAnalyticsService(),
            'instagram' => new InstagramAnalyticsService(),
            'youtube' => new YoutubeAnalyticsService(),
            'linkedin' => new LinkedinAnalyticsService(),
            default => null,
        };
    }

    protected function extractMetrics(string $platform, array $data): array
    {
        return match ($platform) {
            'facebook' => array_merge(
                $data['metrics'] ?? [],
                ['page_fans' => $data['page_fans'] ?? 0]
            ),
            'instagram' => array_merge(
                $data['metrics'] ?? [],
                ['follower_count' => $data['follower_count'] ?? 0]
            ),
            'youtube' => array_merge(
                $data['channel_stats'] ?? [],
                $data['analytics'] ?? []
            ),
            'linkedin' => $data['metrics'] ?? [],
            default => [],
        };
    }
}
