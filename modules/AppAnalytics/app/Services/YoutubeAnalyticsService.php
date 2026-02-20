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
     * Get channel overview — uses Data API v3 primarily, Analytics API as bonus.
     */
    public function getOverview(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $client = $this->initClient($account);
            $youtube = new \Google\Service\YouTube($client);

            // Channel stats via Data API (always works)
            $channelsResponse = $youtube->channels->listChannels('statistics,snippet,contentDetails', ['mine' => true]);

            $channelStats = [];
            $channelName = '';
            $uploadsPlaylistId = '';

            if (!empty($channelsResponse->getItems())) {
                $channel = $channelsResponse->getItems()[0];
                $stats = $channel->getStatistics();
                $channelStats = [
                    'subscriber_count' => (int)$stats->getSubscriberCount(),
                    'video_count' => (int)$stats->getVideoCount(),
                    'view_count' => (int)$stats->getViewCount(),
                    'comment_count' => (int)$stats->getCommentCount(),
                ];
                $channelName = $channel->getSnippet()->getTitle();
                $uploadsPlaylistId = $channel->getContentDetails()->getRelatedPlaylists()->getUploads();
            }

            // Get video-level stats from Data API
            $videoStats = $this->getVideoStatsFromDataAPI($youtube, $uploadsPlaylistId, 50);

            // Try Analytics API for period-specific data (may fail if not enabled)
            $analyticsData = $this->fetchAnalyticsReport($client, $startDate, $endDate);

            // If Analytics API returned zeros, derive from video stats
            if (($analyticsData['views'] ?? 0) === 0 && !empty($videoStats)) {
                $totalViews = array_sum(array_column($videoStats, 'views'));
                $totalLikes = array_sum(array_column($videoStats, 'likes'));
                $totalComments = array_sum(array_column($videoStats, 'comments'));

                // Use channel lifetime view count (more accurate than summing video views)
                $analyticsData['views'] = $channelStats['view_count'] ?? $totalViews;
                $analyticsData['likes'] = $totalLikes;
                $analyticsData['comments'] = $totalComments;
                $analyticsData['source'] = 'data_api';
            }

            return [
                'success' => true,
                'channel_stats' => $channelStats,
                'channel_name' => $channelName,
                'analytics' => $analyticsData,
                'video_stats' => $videoStats,
            ];
        } catch (\Exception $e) {
            Log::warning('YouTube Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get top performing videos via Data API v3.
     */
    public function getPostPerformance(Accounts $account, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $client = $this->initClient($account);
            $youtube = new \Google\Service\YouTube($client);

            // Get uploads playlist ID
            $channelsResponse = $youtube->channels->listChannels('contentDetails', ['mine' => true]);
            $uploadsPlaylistId = '';
            if (!empty($channelsResponse->getItems())) {
                $uploadsPlaylistId = $channelsResponse->getItems()[0]
                    ->getContentDetails()->getRelatedPlaylists()->getUploads();
            }

            if (!$uploadsPlaylistId) {
                return ['success' => true, 'posts' => []];
            }

            // Get videos from uploads playlist (up to 50)
            $videos = $this->getVideoStatsFromDataAPI($youtube, $uploadsPlaylistId, 50);

            // Sort by views (highest first)
            usort($videos, fn($a, $b) => $b['views'] - $a['views']);

            return ['success' => true, 'posts' => $videos];
        } catch (\Exception $e) {
            Log::warning('YouTube Posts Analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get audience demographics via Analytics API.
     */
    public function getAudienceInsights(Accounts $account): array
    {
        try {
            $client = $this->initClient($account);
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
            // Analytics API not enabled — not critical
            return ['success' => true, 'audience' => ['demographics' => []]];
        }
    }

    /**
     * Get daily metrics — try Analytics API, fall back to empty.
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
            // Analytics API not enabled — return empty (chart won't render)
            return ['success' => true, 'daily' => []];
        }
    }

    /**
     * Get video stats from the Data API v3 via uploads playlist.
     */
    protected function getVideoStatsFromDataAPI(\Google\Service\YouTube $youtube, string $uploadsPlaylistId, int $limit = 50): array
    {
        if (!$uploadsPlaylistId) {
            return [];
        }

        // Get video IDs from uploads playlist
        $videoIds = [];
        $nextPageToken = null;
        $fetched = 0;

        do {
            $params = [
                'playlistId' => $uploadsPlaylistId,
                'maxResults' => min(50, $limit - $fetched),
            ];
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $playlistResponse = $youtube->playlistItems->listPlaylistItems('contentDetails', $params);

            foreach ($playlistResponse->getItems() as $item) {
                $videoIds[] = $item->getContentDetails()->getVideoId();
                $fetched++;
            }

            $nextPageToken = $playlistResponse->getNextPageToken();
        } while ($nextPageToken && $fetched < $limit);

        if (empty($videoIds)) {
            return [];
        }

        // Fetch full stats for all videos (batch by 50)
        $videos = [];
        foreach (array_chunk($videoIds, 50) as $chunk) {
            $videosResponse = $youtube->videos->listVideos('statistics,snippet,contentDetails', [
                'id' => implode(',', $chunk),
            ]);

            foreach ($videosResponse->getItems() as $video) {
                $stats = $video->getStatistics();
                $snippet = $video->getSnippet();
                $duration = $this->parseDuration($video->getContentDetails()->getDuration());

                $videos[] = [
                    'id' => $video->getId(),
                    'title' => mb_substr($snippet->getTitle(), 0, 120),
                    'published_at' => Carbon::parse($snippet->getPublishedAt())->format('M j, Y'),
                    'views' => (int)($stats->getViewCount() ?? 0),
                    'likes' => (int)($stats->getLikeCount() ?? 0),
                    'comments' => (int)($stats->getCommentCount() ?? 0),
                    'duration' => $duration,
                    'duration_label' => $this->formatDuration($duration),
                    'thumbnail' => $snippet->getThumbnails()?->getMedium()?->getUrl()
                        ?? $snippet->getThumbnails()?->getDefault()?->getUrl()
                        ?? '',
                ];
            }
        }

        return $videos;
    }

    /**
     * Fetch analytics report from YouTube Analytics API (may fail if not enabled).
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
                    'source' => 'analytics_api',
                ];
            }
        } catch (\Exception $e) {
            // YouTube Analytics API not enabled — this is expected
            Log::info('YouTube Analytics API not available, using Data API fallback: ' . $e->getMessage());
        }

        return [
            'views' => 0,
            'estimated_minutes_watched' => 0,
            'average_view_duration' => 0,
            'subscribers_gained' => 0,
            'subscribers_lost' => 0,
            'likes' => 0,
            'comments' => 0,
            'source' => 'none',
        ];
    }

    /**
     * Parse ISO 8601 duration to seconds.
     */
    protected function parseDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format seconds to human readable.
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds >= 3600) {
            return sprintf('%d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
        }
        return sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
    }
}
