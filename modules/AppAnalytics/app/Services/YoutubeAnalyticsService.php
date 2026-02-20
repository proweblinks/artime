<?php

namespace Modules\AppAnalytics\Services;

use Modules\AppChannels\Models\Accounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class YoutubeAnalyticsService
{
    protected ?\Google_Client $client = null;

    protected function initClient(Accounts $account): \Google_Client
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(get_option('youtube_client_id', ''));
        $this->client->setClientSecret(get_option('youtube_client_secret', ''));
        $this->client->setAccessType('offline');

        if ($account->token) {
            $token = json_decode($account->token, true);
            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                if (!empty($token['refresh_token'])) {
                    $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $newToken = $this->client->getAccessToken();
                    if (empty($newToken['refresh_token'])) {
                        $newToken['refresh_token'] = $token['refresh_token'];
                    }
                    $account->token = json_encode($newToken);
                    $account->save();
                } else {
                    Accounts::where('id', $account->id)->update(['status' => 0]);
                    throw new \Exception(__('YouTube session expired. Please reconnect.'));
                }
            }
        }

        return $this->client;
    }

    /**
     * Get channel overview metrics using YouTube Analytics API.
     */
    public function getOverview(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $client = $this->initClient($account);

            // Get current channel statistics via Data API
            $youtube = new \Google\Service\YouTube($client);
            $channelsResponse = $youtube->channels->listChannels('statistics,snippet', ['mine' => true]);

            $channelStats = [];
            $channelName = '';
            if (!empty($channelsResponse->getItems())) {
                $channel = $channelsResponse->getItems()[0];
                $stats = $channel->getStatistics();
                $channelStats = [
                    'subscriber_count' => (int)$stats->getSubscriberCount(),
                    'video_count' => (int)$stats->getVideoCount(),
                    'view_count' => (int)$stats->getViewCount(),
                ];
                $channelName = $channel->getSnippet()->getTitle();
            }

            // Get analytics data via YouTube Analytics API
            $analyticsData = $this->fetchAnalyticsReport($client, $startDate, $endDate);

            return [
                'success' => true,
                'channel_stats' => $channelStats,
                'channel_name' => $channelName,
                'analytics' => $analyticsData,
            ];
        } catch (\Exception $e) {
            Log::warning('YouTube Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get top performing videos.
     */
    public function getPostPerformance(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $client = $this->initClient($account);
            $youtube = new \Google\Service\YouTube($client);

            // Get recent uploads via search
            $searchResponse = $youtube->search->listSearch('snippet', [
                'forMine' => true,
                'type' => 'video',
                'maxResults' => 20,
                'order' => 'date',
                'publishedAfter' => $startDate->toRfc3339String(),
                'publishedBefore' => $endDate->toRfc3339String(),
            ]);

            $videoIds = [];
            foreach ($searchResponse->getItems() as $item) {
                $videoIds[] = $item->getId()->getVideoId();
            }

            if (empty($videoIds)) {
                return ['success' => true, 'posts' => []];
            }

            // Get video statistics
            $videosResponse = $youtube->videos->listVideos('statistics,snippet,contentDetails', [
                'id' => implode(',', $videoIds),
            ]);

            $posts = [];
            foreach ($videosResponse->getItems() as $video) {
                $stats = $video->getStatistics();
                $snippet = $video->getSnippet();
                $posts[] = [
                    'id' => $video->getId(),
                    'title' => mb_substr($snippet->getTitle(), 0, 120),
                    'published_at' => $snippet->getPublishedAt(),
                    'views' => (int)$stats->getViewCount(),
                    'likes' => (int)$stats->getLikeCount(),
                    'comments' => (int)$stats->getCommentCount(),
                    'thumbnail' => $snippet->getThumbnails()?->getDefault()?->getUrl() ?? '',
                ];
            }

            usort($posts, fn($a, $b) => $b['views'] - $a['views']);

            return ['success' => true, 'posts' => $posts];
        } catch (\Exception $e) {
            Log::warning('YouTube Posts Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get audience demographics.
     */
    public function getAudienceInsights(Accounts $account): array
    {
        try {
            $client = $this->initClient($account);

            // YouTube Analytics API - demographics
            $analytics = new \Google\Service\YouTubeAnalytics($client);
            $response = $analytics->reports->query([
                'ids' => 'channel==MINE',
                'startDate' => Carbon::now()->subDays(28)->format('Y-m-d'),
                'endDate' => Carbon::now()->format('Y-m-d'),
                'metrics' => 'viewerPercentage',
                'dimensions' => 'ageGroup,gender',
            ]);

            $demographics = [];
            foreach ($response->getRows() ?? [] as $row) {
                $demographics[] = [
                    'age_group' => $row[0],
                    'gender' => $row[1],
                    'percentage' => $row[2],
                ];
            }

            return ['success' => true, 'audience' => ['demographics' => $demographics]];
        } catch (\Exception $e) {
            Log::warning('YouTube Audience error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get daily metrics for chart data.
     */
    public function getDailyMetrics(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $client = $this->initClient($account);

            $analytics = new \Google\Service\YouTubeAnalytics($client);
            $response = $analytics->reports->query([
                'ids' => 'channel==MINE',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,comments',
                'dimensions' => 'day',
                'sort' => 'day',
            ]);

            $daily = [];
            foreach ($response->getRows() ?? [] as $row) {
                $daily[] = [
                    'date' => $row[0],
                    'views' => (int)$row[1],
                    'estimated_minutes_watched' => (float)$row[2],
                    'average_view_duration' => (float)$row[3],
                    'subscribers_gained' => (int)$row[4],
                    'subscribers_lost' => (int)$row[5],
                    'likes' => (int)$row[6],
                    'comments' => (int)$row[7],
                ];
            }

            return ['success' => true, 'daily' => $daily];
        } catch (\Exception $e) {
            Log::warning('YouTube Daily Metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch analytics report from YouTube Analytics API.
     */
    protected function fetchAnalyticsReport(\Google_Client $client, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $analytics = new \Google\Service\YouTubeAnalytics($client);
            $response = $analytics->reports->query([
                'ids' => 'channel==MINE',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,comments',
            ]);

            $rows = $response->getRows() ?? [];
            if (!empty($rows)) {
                $row = $rows[0];
                return [
                    'views' => (int)$row[0],
                    'estimated_minutes_watched' => (float)$row[1],
                    'average_view_duration' => (float)$row[2],
                    'subscribers_gained' => (int)$row[3],
                    'subscribers_lost' => (int)$row[4],
                    'likes' => (int)$row[5],
                    'comments' => (int)$row[6],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('YouTube Analytics Report error: ' . $e->getMessage());
        }

        return [
            'views' => 0,
            'estimated_minutes_watched' => 0,
            'average_view_duration' => 0,
            'subscribers_gained' => 0,
            'subscribers_lost' => 0,
            'likes' => 0,
            'comments' => 0,
        ];
    }
}
