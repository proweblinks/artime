<?php

namespace Modules\AppAnalytics\Livewire;

use Livewire\Component;
use Modules\AppChannels\Models\Accounts;
use Modules\AppAnalytics\Services\FacebookAnalyticsService;
use Modules\AppAnalytics\Services\InstagramAnalyticsService;
use Modules\AppAnalytics\Services\YoutubeAnalyticsService;
use Modules\AppAnalytics\Services\LinkedinAnalyticsService;
use Modules\AppAnalytics\Services\AnalyticsCacheService;
use Modules\AppAnalytics\Services\AnalyticsAIService;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    public string $activeTab = 'overview';
    public string $dateRange = '30';
    public ?int $selectedAccountId = null;
    public bool $loading = false;
    public bool $showUpgradeModal = false;
    public string $upgradePlatform = '';

    // Data properties
    public array $overviewData = [];
    public array $platformData = [];
    public array $postsData = [];
    public array $audienceData = [];
    public array $dailyData = [];
    public array $aiInsights = [];
    public string $errorMessage = '';

    protected $listeners = [
        'refreshAnalytics' => 'refreshData',
    ];

    /**
     * Platform configuration.
     */
    protected array $platformConfig = [
        'facebook' => [
            'name' => 'Facebook',
            'icon' => 'fa-brands fa-facebook',
            'color' => '#1877F2',
            'social_network' => 'facebook',
        ],
        'instagram' => [
            'name' => 'Instagram',
            'icon' => 'fa-brands fa-instagram',
            'color' => '#E4405F',
            'social_network' => 'instagram',
        ],
        'youtube' => [
            'name' => 'YouTube',
            'icon' => 'fa-brands fa-youtube',
            'color' => '#FF0000',
            'social_network' => 'youtube',
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'icon' => 'fa-brands fa-linkedin',
            'color' => '#0A66C2',
            'social_network' => 'linkedin',
        ],
    ];

    public function mount()
    {
        $this->loadData();
    }

    /**
     * Switch to a different tab.
     */
    public function switchTab(string $tab)
    {
        if ($tab !== 'overview' && $tab !== 'ai_insights' && !$this->canAccessPlatform($tab)) {
            $this->showUpgradeModal = true;
            $this->upgradePlatform = $tab;
            return;
        }

        $this->activeTab = $tab;
        $this->errorMessage = '';
        $this->loadData();
    }

    /**
     * Set the date range filter.
     */
    public function setDateRange(string $range)
    {
        $this->dateRange = $range;
        $this->loadData();
    }

    /**
     * Select a specific account to analyze.
     */
    public function selectAccount(?int $accountId)
    {
        $this->selectedAccountId = $accountId;
        $this->loadData();
    }

    /**
     * Force refresh data (bypass cache).
     */
    public function refreshData()
    {
        // Clear relevant cache keys
        if ($this->selectedAccountId) {
            $account = $this->getAccount($this->selectedAccountId);
            if ($account) {
                $cacheService = new AnalyticsCacheService();
                $cacheService->invalidate($account);
            }
        }
        $this->loadData();
    }

    /**
     * Show upgrade modal for locked platforms.
     */
    public function showUpgradePrompt(string $platform)
    {
        $this->showUpgradeModal = true;
        $this->upgradePlatform = $platform;
    }

    /**
     * Close upgrade modal.
     */
    public function closeUpgradeModal()
    {
        $this->showUpgradeModal = false;
        $this->upgradePlatform = '';
    }

    /**
     * Generate AI insights for current data.
     */
    public function generateAIInsights()
    {
        if (!$this->canAccessAIInsights()) {
            return;
        }

        $teamId = $this->getTeamId();
        $aiService = new AnalyticsAIService();

        $account = $this->selectedAccountId
            ? $this->getAccount($this->selectedAccountId)
            : null;

        $platform = $account ? $account->social_network : 'cross-platform';

        // Use overview data for AI analysis
        $metricsForAI = $this->overviewData;

        $insight = $aiService->generateWeeklyInsights(
            $teamId,
            $account?->id,
            $platform,
            $metricsForAI
        );

        if ($insight) {
            $this->aiInsights = $aiService->getInsights($teamId, $account?->id)->toArray();
        }
    }

    /**
     * Check if user's plan allows access to a platform tab.
     */
    public function canAccessPlatform(string $platform): bool
    {
        return \Access::canAccess('appanalytics.' . $platform);
    }

    /**
     * Check if user can access AI insights.
     */
    public function canAccessAIInsights(): bool
    {
        return \Access::canAccess('appanalytics.ai_insights');
    }

    /**
     * Load analytics data based on current tab and filters.
     */
    protected function loadData(): void
    {
        $this->loading = true;
        $this->errorMessage = '';

        // Reset data between tab switches to prevent stale data from showing
        $this->platformData = [];
        $this->postsData = [];
        $this->dailyData = [];
        $this->audienceData = [];

        $teamId = $this->getTeamId();
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays((int) $this->dateRange);
        $cacheService = new AnalyticsCacheService();

        try {
            if ($this->activeTab === 'overview') {
                $this->loadOverviewData($teamId, $startDate, $endDate, $cacheService);
            } elseif ($this->activeTab === 'ai_insights') {
                $this->loadAIInsightsData($teamId);
            } else {
                $this->loadPlatformData($this->activeTab, $startDate, $endDate, $cacheService);
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }

        $this->loading = false;
    }

    /**
     * Load cross-platform overview data.
     */
    protected function loadOverviewData(int $teamId, Carbon $startDate, Carbon $endDate, AnalyticsCacheService $cacheService): void
    {
        $accounts = $this->getConnectedAccounts($teamId);
        $overview = [
            'total_followers' => 0,
            'total_reach' => 0,
            'total_engagement' => 0,
            'total_posts' => 0,
            'platforms' => [],
        ];

        foreach ($accounts as $account) {
            $platform = $account->social_network;
            if (!$this->canAccessPlatform($platform)) {
                continue;
            }

            $service = $this->getServiceForPlatform($platform);
            if (!$service) {
                continue;
            }

            $data = $cacheService->getOrFetch($account, 'overview', $startDate, $endDate, function () use ($service, $account, $startDate, $endDate) {
                return $service->getOverview($account, $startDate, $endDate);
            });

            if ($data['success'] ?? false) {
                $platformSummary = $this->normalizePlatformOverview($platform, $data);

                // Aggregate multiple accounts per platform instead of overwriting
                if (isset($overview['platforms'][$platform])) {
                    $existing = $overview['platforms'][$platform];
                    $existing['followers'] = ($existing['followers'] ?? 0) + ($platformSummary['followers'] ?? 0);
                    $existing['reach'] = ($existing['reach'] ?? 0) + ($platformSummary['reach'] ?? 0);
                    $existing['engagement'] = ($existing['engagement'] ?? 0) + ($platformSummary['engagement'] ?? 0);
                    $existing['accounts'] = ($existing['accounts'] ?? 1) + 1;
                    $overview['platforms'][$platform] = $existing;
                } else {
                    $platformSummary['accounts'] = 1;
                    $overview['platforms'][$platform] = $platformSummary;
                }

                $overview['total_followers'] += $platformSummary['followers'] ?? 0;
                $overview['total_reach'] += $platformSummary['reach'] ?? 0;
                $overview['total_engagement'] += $platformSummary['engagement'] ?? 0;
            }
        }

        $this->overviewData = $overview;
    }

    /**
     * Load data for a specific platform tab.
     */
    protected function loadPlatformData(string $platform, Carbon $startDate, Carbon $endDate, AnalyticsCacheService $cacheService): void
    {
        $account = $this->getAccountForPlatform($platform);
        if (!$account) {
            $this->errorMessage = __('No :platform account connected. Connect one in Channels to see analytics.', ['platform' => ucfirst($platform)]);
            return;
        }

        $service = $this->getServiceForPlatform($platform);
        if (!$service) {
            $this->errorMessage = __(':platform analytics are not yet available.', ['platform' => ucfirst($platform)]);
            return;
        }

        // Fetch overview
        $this->platformData = $cacheService->getOrFetch($account, 'overview', $startDate, $endDate, function () use ($service, $account, $startDate, $endDate) {
            return $service->getOverview($account, $startDate, $endDate);
        });

        // Fetch posts
        $this->postsData = $cacheService->getOrFetch($account, 'posts', $startDate, $endDate, function () use ($service, $account, $startDate, $endDate) {
            return $service->getPostPerformance($account, $startDate, $endDate);
        });

        // Fetch daily metrics for charts
        $this->dailyData = $cacheService->getOrFetch($account, 'daily', $startDate, $endDate, function () use ($service, $account, $startDate, $endDate) {
            return $service->getDailyMetrics($account, $startDate, $endDate);
        });

        // Fetch audience
        $this->audienceData = $cacheService->getOrFetch($account, 'audience', $startDate, $endDate, function () use ($service, $account) {
            return $service->getAudienceInsights($account);
        });
    }

    /**
     * Load AI insights data.
     */
    protected function loadAIInsightsData(int $teamId): void
    {
        $aiService = new AnalyticsAIService();
        $account = $this->selectedAccountId
            ? $this->getAccount($this->selectedAccountId)
            : null;

        $this->aiInsights = $aiService->getInsights($teamId, $account?->id)->toArray();
    }

    /**
     * Normalize different platform metrics into a common format for overview.
     */
    protected function normalizePlatformOverview(string $platform, array $data): array
    {
        return match ($platform) {
            'facebook' => [
                'followers' => $data['page_fans'] ?? 0,
                'reach' => $data['metrics']['page_impressions'] ?? 0,
                'engagement' => $data['metrics']['page_post_engagements'] ?? 0,
                'name' => $data['page_name'] ?? '',
            ],
            'instagram' => [
                'followers' => $data['follower_count'] ?? 0,
                'reach' => $data['metrics']['reach'] ?? 0,
                'engagement' => $data['metrics']['impressions'] ?? 0,
                'name' => $data['username'] ?? '',
            ],
            'youtube' => [
                'followers' => $data['channel_stats']['subscriber_count'] ?? 0,
                'reach' => $data['analytics']['views'] ?? 0,
                'engagement' => $data['analytics']['likes'] ?? 0,
                'name' => $data['channel_name'] ?? '',
            ],
            'linkedin' => [
                'followers' => $data['metrics']['follower_count'] ?? 0,
                'reach' => $data['metrics']['impressions'] ?? 0,
                'engagement' => $data['metrics']['clicks'] ?? 0,
                'name' => '',
            ],
            default => ['followers' => 0, 'reach' => 0, 'engagement' => 0, 'name' => ''],
        };
    }

    /**
     * Get service instance for a platform.
     */
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

    /**
     * Get connected accounts for the team.
     */
    protected function getConnectedAccounts(int $teamId): \Illuminate\Database\Eloquent\Collection
    {
        return Accounts::where('team_id', $teamId)
            ->where('status', 1)
            ->whereIn('social_network', array_keys($this->platformConfig))
            ->get();
    }

    /**
     * Get an account for a specific platform.
     */
    protected function getAccountForPlatform(string $platform): ?Accounts
    {
        $teamId = $this->getTeamId();

        if ($this->selectedAccountId) {
            return Accounts::where('id', $this->selectedAccountId)
                ->where('team_id', $teamId)
                ->where('social_network', $platform)
                ->where('status', 1)
                ->first();
        }

        return Accounts::where('team_id', $teamId)
            ->where('social_network', $platform)
            ->where('status', 1)
            ->first();
    }

    /**
     * Get a specific account by ID.
     */
    protected function getAccount(int $accountId): ?Accounts
    {
        return Accounts::where('id', $accountId)
            ->where('team_id', $this->getTeamId())
            ->where('status', 1)
            ->first();
    }

    /**
     * Get current team ID.
     */
    protected function getTeamId(): int
    {
        return auth()->user()->current_team_id ?? auth()->id();
    }

    /**
     * Get available platforms with their connection status.
     */
    public function getAvailablePlatformsProperty(): array
    {
        $teamId = $this->getTeamId();
        $accounts = $this->getConnectedAccounts($teamId);
        $connectedPlatforms = $accounts->pluck('social_network')->unique()->toArray();

        $platforms = [];
        foreach ($this->platformConfig as $key => $config) {
            $platforms[$key] = array_merge($config, [
                'connected' => in_array($key, $connectedPlatforms),
                'accessible' => $this->canAccessPlatform($key),
                'account_count' => $accounts->where('social_network', $key)->count(),
            ]);
        }

        return $platforms;
    }

    /**
     * Get accounts for the active platform (for the account dropdown).
     */
    public function getPlatformAccountsProperty(): array
    {
        if ($this->activeTab === 'overview' || $this->activeTab === 'ai_insights') {
            return [];
        }

        $teamId = $this->getTeamId();
        $platform = $this->activeTab;

        return Accounts::where('team_id', $teamId)
            ->where('social_network', $platform)
            ->where('status', 1)
            ->get()
            ->map(fn($a) => ['id' => $a->id, 'name' => $a->name ?? $a->username ?? "Account #{$a->id}"])
            ->toArray();
    }

    public function render()
    {
        return view('appanalytics::livewire.analytics-dashboard');
    }
}
