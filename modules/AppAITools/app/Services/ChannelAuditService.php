<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class ChannelAuditService
{
    protected YouTubeDataService $youtube;
    protected PlatformDataService $platformService;

    public function __construct(YouTubeDataService $youtube, PlatformDataService $platformService)
    {
        $this->youtube = $youtube;
        $this->platformService = $platformService;
    }

    /**
     * Run comprehensive channel audit.
     */
    public function audit(string $channelUrl, string $platform = 'youtube'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'channel_audit',
            'platform' => $platform,
            'title' => "Channel Audit: {$channelUrl}",
            'input_data' => ['channel_url' => $channelUrl, 'platform' => $platform],
            'status' => 2,
        ]);

        try {
            // Fetch channel data
            $channelData = null;
            $recentVideos = [];

            if ($platform === 'youtube') {
                $channelData = $this->youtube->getChannelData($channelUrl);
                if (!$channelData) {
                    throw new \Exception('Could not fetch channel data. Please check the URL format.');
                }

                // Get recent videos for analysis
                try {
                    $recentVideos = $this->youtube->getChannelVideos($channelData['id'], 20);
                } catch (\Exception $e) {
                    Log::info('ChannelAudit: Could not fetch channel videos');
                }
            } else {
                throw new \Exception("Channel audit currently supports YouTube only. Other platforms coming soon.");
            }

            // Calculate metrics
            $metrics = $this->calculateMetrics($channelData, $recentVideos);

            // Build context for AI analysis
            $context = "Channel: {$channelData['title']}\n"
                . "Subscribers: " . number_format($channelData['subscribers']) . "\n"
                . "Total Views: " . number_format($channelData['total_views']) . "\n"
                . "Total Videos: " . number_format($channelData['video_count']) . "\n"
                . "Country: {$channelData['country']}\n"
                . "Description: " . mb_substr($channelData['description'], 0, 300) . "\n"
                . "\nCalculated Metrics:\n"
                . "Average Views/Video: " . number_format($metrics['avg_views']) . "\n"
                . "Average Likes/Video: " . number_format($metrics['avg_likes']) . "\n"
                . "Average Comments/Video: " . number_format($metrics['avg_comments']) . "\n"
                . "Engagement Rate: " . number_format($metrics['engagement_rate'], 2) . "%\n"
                . "Posting Frequency: ~" . $metrics['posting_frequency'] . " videos/month\n";

            if (!empty($recentVideos)) {
                $context .= "\nRecent videos (last 20):\n";
                foreach (array_slice($recentVideos, 0, 10) as $v) {
                    $context .= "- \"{$v['title']}\" - " . number_format($v['views']) . " views, " . number_format($v['likes']) . " likes\n";
                }
            }

            $prompt = "You are a {$platform} growth expert performing a comprehensive channel audit. "
                . "Analyze this channel data and provide detailed, actionable insights.\n\n"
                . $context
                . "\nProvide your audit as a JSON object with this exact structure:\n"
                . '{"overall_score": 0-100, "overall_summary": "one sentence summary", '
                . '"categories": [{"name": "SEO & Discoverability", "score": 0-100}, {"name": "Content Quality", "score": 0-100}, {"name": "Engagement", "score": 0-100}, {"name": "Growth Potential", "score": 0-100}], '
                . '"metrics": [{"label": "metric name", "value": "formatted value"}], '
                . '"recommendations": [{"title": "short title", "description": "detailed actionable recommendation", "priority": "high|medium|low"}]}'
                . "\n\nRules:\n"
                . "- Overall score is a weighted average of category scores\n"
                . "- Include 4-6 key metrics\n"
                . "- 6-10 prioritized recommendations\n"
                . "- Be specific and actionable, reference actual data\n"
                . "\nRespond with ONLY the JSON object.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $parsed = $this->parseJsonResponse($result['data'][0] ?? '{}');

            // Merge computed metrics into result
            $parsed['channel_info'] = [
                'title' => $channelData['title'],
                'thumbnail' => $channelData['thumbnail'],
                'subscribers' => $channelData['subscribers'],
            ];

            $history->update([
                'title' => "Audit: {$channelData['title']}",
                'result_data' => $parsed,
                'status' => 1,
                'credits_used' => $result['totalTokens'] ?? 0,
            ]);

            return $parsed;

        } catch (\Exception $e) {
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    /**
     * Calculate channel metrics from video data.
     */
    protected function calculateMetrics(array $channelData, array $recentVideos): array
    {
        $totalViews = 0;
        $totalLikes = 0;
        $totalComments = 0;
        $count = count($recentVideos);

        foreach ($recentVideos as $v) {
            $totalViews += $v['views'] ?? 0;
            $totalLikes += $v['likes'] ?? 0;
            $totalComments += $v['comments'] ?? 0;
        }

        $avgViews = $count > 0 ? round($totalViews / $count) : 0;
        $avgLikes = $count > 0 ? round($totalLikes / $count) : 0;
        $avgComments = $count > 0 ? round($totalComments / $count) : 0;

        $engagementRate = $avgViews > 0 ? (($avgLikes + $avgComments) / $avgViews) * 100 : 0;

        // Estimate posting frequency
        $postingFrequency = 0;
        if ($count >= 2) {
            $dates = array_filter(array_column($recentVideos, 'published_at'));
            if (count($dates) >= 2) {
                sort($dates);
                $oldest = strtotime($dates[0]);
                $newest = strtotime(end($dates));
                $daySpan = max(1, ($newest - $oldest) / 86400);
                $postingFrequency = round(($count / $daySpan) * 30, 1);
            }
        }

        return [
            'avg_views' => $avgViews,
            'avg_likes' => $avgLikes,
            'avg_comments' => $avgComments,
            'engagement_rate' => $engagementRate,
            'posting_frequency' => $postingFrequency,
        ];
    }

    protected function parseJsonResponse(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['overall_score' => 0, 'overall_summary' => 'Audit failed to parse.', 'recommendations' => []];
    }
}
