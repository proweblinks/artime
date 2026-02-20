<?php

namespace Modules\AppAnalytics\Services;

use JanuSoftware\Facebook\Facebook;
use Modules\AppChannels\Models\Accounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstagramAnalyticsService
{
    protected ?Facebook $fb = null;

    protected function initFacebook(): Facebook
    {
        if (!$this->fb) {
            $this->fb = new Facebook([
                'app_id' => get_option('facebook_app_id', ''),
                'app_secret' => get_option('facebook_app_secret', ''),
                'default_graph_version' => get_option('facebook_graph_version', 'v22.0'),
            ]);
        }
        return $this->fb;
    }

    /**
     * Get Instagram account overview metrics.
     */
    public function getOverview(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $fb = $this->initFacebook();
            $igUserId = $account->pid;
            $token = $account->token;

            // Fetch account-level insights
            $metrics = 'impressions,reach,profile_views,website_clicks';
            $response = $fb->get(
                "/{$igUserId}/insights?metric={$metrics}&period=day&since={$startDate->timestamp}&until={$endDate->timestamp}",
                $token
            )->getDecodedBody();

            $parsed = $this->parseInsightsResponse($response);

            // Fetch current follower count
            $profileInfo = $fb->get(
                "/{$igUserId}?fields=followers_count,media_count,username,name",
                $token
            )->getDecodedBody();

            return [
                'success' => true,
                'metrics' => $parsed,
                'follower_count' => $profileInfo['followers_count'] ?? 0,
                'media_count' => $profileInfo['media_count'] ?? 0,
                'username' => $profileInfo['username'] ?? '',
                'name' => $profileInfo['name'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::warning('Instagram Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get post-level performance data.
     */
    public function getPostPerformance(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $fb = $this->initFacebook();
            $igUserId = $account->pid;
            $token = $account->token;

            $fields = 'id,caption,timestamp,media_type,like_count,comments_count,insights.metric(impressions,reach,engagement,saved)';
            $response = $fb->get(
                "/{$igUserId}/media?fields={$fields}&limit=50",
                $token
            )->getDecodedBody();

            $posts = [];
            foreach ($response['data'] ?? [] as $media) {
                $timestamp = $media['timestamp'] ?? '';
                // Filter by date range
                if ($timestamp) {
                    $postDate = Carbon::parse($timestamp);
                    if ($postDate->lt($startDate) || $postDate->gt($endDate)) {
                        continue;
                    }
                }

                $posts[] = [
                    'id' => $media['id'] ?? '',
                    'caption' => mb_substr($media['caption'] ?? '(No caption)', 0, 120),
                    'timestamp' => $timestamp,
                    'media_type' => $media['media_type'] ?? '',
                    'likes' => $media['like_count'] ?? 0,
                    'comments' => $media['comments_count'] ?? 0,
                    'impressions' => $this->extractInsightValue($media, 'impressions'),
                    'reach' => $this->extractInsightValue($media, 'reach'),
                    'engagement' => $this->extractInsightValue($media, 'engagement'),
                    'saved' => $this->extractInsightValue($media, 'saved'),
                ];
            }

            usort($posts, fn($a, $b) => ($b['likes'] + $b['comments']) - ($a['likes'] + $a['comments']));

            return ['success' => true, 'posts' => $posts];
        } catch (\Exception $e) {
            Log::warning('Instagram Posts Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get audience demographics.
     */
    public function getAudienceInsights(Accounts $account): array
    {
        try {
            $fb = $this->initFacebook();
            $igUserId = $account->pid;
            $token = $account->token;

            $response = $fb->get(
                "/{$igUserId}/insights?metric=audience_city,audience_country,audience_gender_age&period=lifetime",
                $token
            )->getDecodedBody();

            $audience = [];
            foreach ($response['data'] ?? [] as $metric) {
                $name = $metric['name'] ?? '';
                $values = $metric['values'][0]['value'] ?? [];
                $audience[$name] = $values;
            }

            return ['success' => true, 'audience' => $audience];
        } catch (\Exception $e) {
            Log::warning('Instagram Audience error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get daily metrics for chart data.
     */
    public function getDailyMetrics(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $fb = $this->initFacebook();
            $igUserId = $account->pid;
            $token = $account->token;

            $metrics = 'impressions,reach,profile_views,website_clicks';
            $response = $fb->get(
                "/{$igUserId}/insights?metric={$metrics}&period=day&since={$startDate->timestamp}&until={$endDate->timestamp}",
                $token
            )->getDecodedBody();

            return ['success' => true, 'daily' => $this->parseDailyData($response)];
        } catch (\Exception $e) {
            Log::warning('Instagram Daily Metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function parseInsightsResponse(array $response): array
    {
        $totals = [];
        foreach ($response['data'] ?? [] as $metric) {
            $name = $metric['name'] ?? '';
            $total = 0;
            foreach ($metric['values'] ?? [] as $val) {
                $total += (int)($val['value'] ?? 0);
            }
            $totals[$name] = $total;
        }
        return $totals;
    }

    protected function parseDailyData(array $response): array
    {
        $daily = [];
        foreach ($response['data'] ?? [] as $metric) {
            $name = $metric['name'] ?? '';
            foreach ($metric['values'] ?? [] as $val) {
                $date = substr($val['end_time'] ?? '', 0, 10);
                if (!isset($daily[$date])) {
                    $daily[$date] = ['date' => $date];
                }
                $daily[$date][$name] = (int)($val['value'] ?? 0);
            }
        }
        return array_values($daily);
    }

    protected function extractInsightValue(array $media, string $metricName): int
    {
        foreach ($media['insights']['data'] ?? [] as $insight) {
            if (($insight['name'] ?? '') === $metricName) {
                return (int)($insight['values'][0]['value'] ?? 0);
            }
        }
        return 0;
    }
}
