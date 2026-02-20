<?php

namespace Modules\AppAnalytics\Services;

use JanuSoftware\Facebook\Facebook;
use Modules\AppChannels\Models\Accounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FacebookAnalyticsService
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
     * Get page overview metrics for a date range.
     */
    public function getOverview(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $fb = $this->initFacebook();
            $pageId = $account->pid;
            $token = $account->token;

            // Fetch page insights
            $metrics = 'page_impressions,page_engaged_users,page_post_engagements,page_fan_adds,page_views_total';
            $response = $fb->get(
                "/{$pageId}/insights?metric={$metrics}&period=day&since={$startDate->timestamp}&until={$endDate->timestamp}",
                $token
            )->getDecodedBody();

            $parsed = $this->parseInsightsResponse($response);

            // Fetch current page fan count
            $pageInfo = $fb->get("/{$pageId}?fields=fan_count,name", $token)->getDecodedBody();

            return [
                'success' => true,
                'metrics' => $parsed,
                'page_fans' => $pageInfo['fan_count'] ?? 0,
                'page_name' => $pageInfo['name'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::warning('Facebook Analytics error: ' . $e->getMessage());
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
            $pageId = $account->pid;
            $token = $account->token;

            $fields = 'message,created_time,shares,likes.summary(true),comments.summary(true),insights.metric(post_impressions,post_engaged_users)';
            $response = $fb->get(
                "/{$pageId}/posts?fields={$fields}&since={$startDate->timestamp}&until={$endDate->timestamp}&limit=50",
                $token
            )->getDecodedBody();

            $posts = [];
            foreach ($response['data'] ?? [] as $post) {
                $posts[] = [
                    'id' => $post['id'] ?? '',
                    'message' => mb_substr($post['message'] ?? '(No text)', 0, 120),
                    'created_time' => $post['created_time'] ?? '',
                    'likes' => $post['likes']['summary']['total_count'] ?? 0,
                    'comments' => $post['comments']['summary']['total_count'] ?? 0,
                    'shares' => $post['shares']['count'] ?? 0,
                    'impressions' => $this->extractInsightValue($post, 'post_impressions'),
                    'engaged_users' => $this->extractInsightValue($post, 'post_engaged_users'),
                ];
            }

            // Sort by engagement
            usort($posts, fn($a, $b) => ($b['likes'] + $b['comments'] + $b['shares']) - ($a['likes'] + $a['comments'] + $a['shares']));

            return ['success' => true, 'posts' => $posts];
        } catch (\Exception $e) {
            Log::warning('Facebook Posts Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get audience demographics/insights.
     */
    public function getAudienceInsights(Accounts $account): array
    {
        try {
            $fb = $this->initFacebook();
            $pageId = $account->pid;
            $token = $account->token;

            $response = $fb->get(
                "/{$pageId}/insights?metric=page_fans_city,page_fans_country,page_fans_gender_age&period=lifetime",
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
            Log::warning('Facebook Audience error: ' . $e->getMessage());
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
            $pageId = $account->pid;
            $token = $account->token;

            $metrics = 'page_impressions,page_engaged_users,page_post_engagements,page_fan_adds,page_views_total';
            $response = $fb->get(
                "/{$pageId}/insights?metric={$metrics}&period=day&since={$startDate->timestamp}&until={$endDate->timestamp}",
                $token
            )->getDecodedBody();

            return ['success' => true, 'daily' => $this->parseDailyData($response)];
        } catch (\Exception $e) {
            Log::warning('Facebook Daily Metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse insights API response into aggregated totals.
     */
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

    /**
     * Parse insights into daily time-series data for charts.
     */
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

    /**
     * Extract insight value from a post's embedded insights.
     */
    protected function extractInsightValue(array $post, string $metricName): int
    {
        foreach ($post['insights']['data'] ?? [] as $insight) {
            if (($insight['name'] ?? '') === $metricName) {
                return (int)($insight['values'][0]['value'] ?? 0);
            }
        }
        return 0;
    }
}
