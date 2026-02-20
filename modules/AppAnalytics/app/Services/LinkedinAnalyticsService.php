<?php

namespace Modules\AppAnalytics\Services;

use Modules\AppChannels\Models\Accounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedinAnalyticsService
{
    protected string $apiBase = 'https://api.linkedin.com/v2';

    /**
     * Get LinkedIn page overview metrics.
     */
    public function getOverview(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $token = $account->token;
            $orgId = $account->pid;

            // Get follower statistics
            $followerStats = $this->request($token, '/organizationalEntityFollowerStatistics', [
                'q' => 'organizationalEntity',
                'organizationalEntity' => "urn:li:organization:{$orgId}",
            ]);

            $followerCount = 0;
            foreach ($followerStats['elements'] ?? [] as $el) {
                $followerCount = $el['followerCounts']['organicFollowerCount'] ?? 0;
                $followerCount += $el['followerCounts']['paidFollowerCount'] ?? 0;
            }

            // Get page statistics
            $pageStats = $this->request($token, '/organizationPageStatistics', [
                'q' => 'organization',
                'organization' => "urn:li:organization:{$orgId}",
            ]);

            $pageViews = 0;
            $uniqueVisitors = 0;
            foreach ($pageStats['elements'] ?? [] as $el) {
                $views = $el['views'] ?? [];
                $pageViews = $views['allPageViews']['pageViews'] ?? 0;
                $uniqueVisitors = $views['allPageViews']['uniquePageViews'] ?? 0;
            }

            // Get share statistics for engagement
            $startMs = $startDate->startOfDay()->getTimestampMs();
            $endMs = $endDate->endOfDay()->getTimestampMs();

            $shareStats = $this->request($token, '/organizationalEntityShareStatistics', [
                'q' => 'organizationalEntity',
                'organizationalEntity' => "urn:li:organization:{$orgId}",
                'timeIntervals.timeGranularityType' => 'DAY',
                'timeIntervals.timeRange.start' => $startMs,
                'timeIntervals.timeRange.end' => $endMs,
            ]);

            $totalImpressions = 0;
            $totalEngagement = 0;
            $totalClicks = 0;
            $totalShares = 0;
            foreach ($shareStats['elements'] ?? [] as $el) {
                foreach ($el['totalShareStatistics'] ?? [] as $stat) {
                    $totalImpressions += $stat['impressionCount'] ?? 0;
                    $totalEngagement += $stat['engagement'] ?? 0;
                    $totalClicks += $stat['clickCount'] ?? 0;
                    $totalShares += $stat['shareCount'] ?? 0;
                }
            }

            return [
                'success' => true,
                'metrics' => [
                    'follower_count' => $followerCount,
                    'page_views' => $pageViews,
                    'unique_visitors' => $uniqueVisitors,
                    'impressions' => $totalImpressions,
                    'engagement' => $totalEngagement,
                    'clicks' => $totalClicks,
                    'shares' => $totalShares,
                ],
            ];
        } catch (\Exception $e) {
            Log::warning('LinkedIn Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get post performance data.
     */
    public function getPostPerformance(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $token = $account->token;
            $orgId = $account->pid;

            $response = $this->request($token, '/shares', [
                'q' => 'owners',
                'owners' => "urn:li:organization:{$orgId}",
                'count' => 50,
                'sortBy' => 'LAST_MODIFIED',
            ]);

            $posts = [];
            foreach ($response['elements'] ?? [] as $share) {
                $created = ($share['created']['time'] ?? 0) / 1000;
                $postDate = Carbon::createFromTimestamp($created);

                if ($postDate->lt($startDate) || $postDate->gt($endDate)) {
                    continue;
                }

                $text = $share['text']['text'] ?? '(No text)';
                $posts[] = [
                    'id' => $share['id'] ?? '',
                    'text' => mb_substr($text, 0, 120),
                    'created_time' => $postDate->toDateTimeString(),
                    'likes' => $share['totalShareStatistics']['likeCount'] ?? 0,
                    'comments' => $share['totalShareStatistics']['commentCount'] ?? 0,
                    'shares' => $share['totalShareStatistics']['shareCount'] ?? 0,
                    'impressions' => $share['totalShareStatistics']['impressionCount'] ?? 0,
                    'clicks' => $share['totalShareStatistics']['clickCount'] ?? 0,
                ];
            }

            usort($posts, fn($a, $b) => ($b['impressions'] + $b['likes']) - ($a['impressions'] + $a['likes']));

            return ['success' => true, 'posts' => $posts];
        } catch (\Exception $e) {
            Log::warning('LinkedIn Posts Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get audience demographics.
     */
    public function getAudienceInsights(Accounts $account): array
    {
        try {
            $token = $account->token;
            $orgId = $account->pid;

            $followerStats = $this->request($token, '/organizationalEntityFollowerStatistics', [
                'q' => 'organizationalEntity',
                'organizationalEntity' => "urn:li:organization:{$orgId}",
            ]);

            $audience = [
                'industries' => [],
                'seniorities' => [],
                'functions' => [],
            ];

            foreach ($followerStats['elements'] ?? [] as $el) {
                foreach ($el['followerCountsByIndustry'] ?? [] as $item) {
                    $audience['industries'][] = [
                        'name' => $item['industry'] ?? 'Unknown',
                        'count' => ($item['followerCounts']['organicFollowerCount'] ?? 0) + ($item['followerCounts']['paidFollowerCount'] ?? 0),
                    ];
                }
                foreach ($el['followerCountsBySeniority'] ?? [] as $item) {
                    $audience['seniorities'][] = [
                        'name' => $item['seniority'] ?? 'Unknown',
                        'count' => ($item['followerCounts']['organicFollowerCount'] ?? 0) + ($item['followerCounts']['paidFollowerCount'] ?? 0),
                    ];
                }
                foreach ($el['followerCountsByFunction'] ?? [] as $item) {
                    $audience['functions'][] = [
                        'name' => $item['function'] ?? 'Unknown',
                        'count' => ($item['followerCounts']['organicFollowerCount'] ?? 0) + ($item['followerCounts']['paidFollowerCount'] ?? 0),
                    ];
                }
            }

            return ['success' => true, 'audience' => $audience];
        } catch (\Exception $e) {
            Log::warning('LinkedIn Audience error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get daily metrics for chart data.
     */
    public function getDailyMetrics(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $token = $account->token;
            $orgId = $account->pid;

            $startMs = $startDate->startOfDay()->getTimestampMs();
            $endMs = $endDate->endOfDay()->getTimestampMs();

            $response = $this->request($token, '/organizationalEntityShareStatistics', [
                'q' => 'organizationalEntity',
                'organizationalEntity' => "urn:li:organization:{$orgId}",
                'timeIntervals.timeGranularityType' => 'DAY',
                'timeIntervals.timeRange.start' => $startMs,
                'timeIntervals.timeRange.end' => $endMs,
            ]);

            $daily = [];
            foreach ($response['elements'] ?? [] as $el) {
                $timeRange = $el['timeRange'] ?? [];
                $start = ($timeRange['start'] ?? 0) / 1000;
                $date = Carbon::createFromTimestamp($start)->format('Y-m-d');

                $stats = $el['totalShareStatistics'] ?? [];
                $daily[] = [
                    'date' => $date,
                    'impressions' => $stats['impressionCount'] ?? 0,
                    'clicks' => $stats['clickCount'] ?? 0,
                    'likes' => $stats['likeCount'] ?? 0,
                    'comments' => $stats['commentCount'] ?? 0,
                    'shares' => $stats['shareCount'] ?? 0,
                    'engagement' => $stats['engagement'] ?? 0,
                ];
            }

            return ['success' => true, 'daily' => $daily];
        } catch (\Exception $e) {
            Log::warning('LinkedIn Daily Metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Make a LinkedIn API request.
     */
    protected function request(string $token, string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Restli-Protocol-Version' => '2.0.0',
        ])->get($this->apiBase . $endpoint, $params);

        if ($response->failed()) {
            throw new \Exception('LinkedIn API error: ' . $response->body());
        }

        return $response->json() ?? [];
    }
}
