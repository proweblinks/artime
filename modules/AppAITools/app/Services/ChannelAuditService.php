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
                . "Channel Created: {$channelData['published_at']}\n"
                . "Description: " . mb_substr($channelData['description'], 0, 300) . "\n"
                . "\nCalculated Metrics:\n"
                . "Average Views/Video: " . number_format($metrics['avg_views']) . "\n"
                . "Average Likes/Video: " . number_format($metrics['avg_likes']) . "\n"
                . "Average Comments/Video: " . number_format($metrics['avg_comments']) . "\n"
                . "Engagement Rate: " . number_format($metrics['engagement_rate'], 2) . "%\n"
                . "Posting Frequency: ~" . $metrics['posting_frequency'] . " videos/month\n"
                . "Views/Subs Ratio: " . number_format($metrics['views_to_subs_ratio'], 1) . "%\n"
                . "Like/View Ratio: " . number_format($metrics['like_to_view_ratio'], 2) . "%\n"
                . "Comment/View Ratio: " . number_format($metrics['comment_to_view_ratio'], 3) . "%\n"
                . "Est. Monthly Views: " . number_format($metrics['est_monthly_views']) . "\n"
                . "Avg View Velocity: " . number_format($metrics['avg_view_velocity'], 0) . " views/day\n"
                . "Upload Consistency (StdDev days): " . number_format($metrics['upload_consistency_days'], 1) . "\n"
                . "Avg Days Between Uploads: " . number_format($metrics['avg_days_between_uploads'], 1) . "\n"
                . "Avg Video Duration: " . number_format($metrics['avg_duration_seconds'] / 60, 1) . " minutes\n"
                . "View Trend (newest vs oldest): " . ($metrics['view_trend_pct'] >= 0 ? '+' : '') . number_format($metrics['view_trend_pct'], 1) . "%\n";

            if (!empty($recentVideos)) {
                $context .= "\nAll 20 recent videos (title, views, likes, comments, duration):\n";
                foreach ($recentVideos as $i => $v) {
                    $context .= ($i + 1) . ". \"{$v['title']}\" - " . number_format($v['views']) . " views, "
                        . number_format($v['likes']) . " likes, " . number_format($v['comments']) . " comments, "
                        . "duration: {$v['duration']}\n";
                }

                $context .= "\nTop 3 by views:\n";
                foreach ($metrics['top_videos'] as $v) {
                    $context .= "- \"{$v['title']}\" — " . number_format($v['views']) . " views\n";
                }
                $context .= "Bottom 3 by views:\n";
                foreach ($metrics['bottom_videos'] as $v) {
                    $context .= "- \"{$v['title']}\" — " . number_format($v['views']) . " views\n";
                }
            }

            $prompt = "You are a {$platform} growth expert performing a comprehensive, data-driven channel audit. "
                . "Analyze this channel data thoroughly and provide detailed, actionable insights.\n\n"
                . $context
                . "\nProvide your audit as a JSON object with this exact structure:\n"
                . <<<'JSON'
{
  "overall_score": 0-100,
  "overall_summary": "2-3 sentence summary referencing actual data",
  "grade": "A+|A|B+|B|C+|C|D|F",
  "detected_niche": "channel's primary niche",
  "categories": [
    {"name": "SEO & Discoverability", "score": 0-100, "summary": "1 sentence", "icon": "fa-magnifying-glass-chart"},
    {"name": "Content Quality", "score": 0-100, "summary": "1 sentence", "icon": "fa-gem"},
    {"name": "Engagement & Community", "score": 0-100, "summary": "1 sentence", "icon": "fa-comments"},
    {"name": "Growth & Momentum", "score": 0-100, "summary": "1 sentence", "icon": "fa-rocket"},
    {"name": "Monetization Readiness", "score": 0-100, "summary": "1 sentence", "icon": "fa-dollar-sign"},
    {"name": "Branding & Consistency", "score": 0-100, "summary": "1 sentence", "icon": "fa-palette"}
  ],
  "channel_health": {
    "upload_consistency": "Excellent|Good|Needs Improvement|Poor",
    "audience_retention_signal": "Strong|Average|Weak",
    "viral_potential": "High|Medium|Low",
    "seo_optimization": "Well Optimized|Partially Optimized|Needs Work",
    "thumbnail_quality": "Professional|Average|Needs Improvement",
    "title_effectiveness": "Strong|Average|Weak"
  },
  "quick_wins": [
    {"action": "specific immediate action", "expected_impact": "e.g. +15% CTR", "effort": "low|medium", "icon": "fa-bolt"},
    {"action": "specific immediate action", "expected_impact": "e.g. +20% engagement", "effort": "low|medium", "icon": "fa-wand-magic-sparkles"},
    {"action": "specific immediate action", "expected_impact": "e.g. +10% views", "effort": "low", "icon": "fa-bullseye"}
  ],
  "content_analysis": {
    "best_performing_topics": ["topic1","topic2","topic3"],
    "underperforming_topics": ["topic1","topic2"],
    "recommended_content_types": ["type1","type2","type3"],
    "optimal_upload_time": "e.g. Tuesday/Thursday 2-4pm EST",
    "ideal_video_length": "e.g. 12-18 minutes"
  },
  "engagement_funnel": {
    "views_per_video": "avg views number",
    "likes_per_video": "avg likes number",
    "comments_per_video": "avg comments number",
    "view_to_like_pct": 0.0,
    "view_to_comment_pct": 0.0,
    "funnel_health": "Healthy|Needs Work|Critical",
    "funnel_insight": "1 sentence interpreting the funnel shape"
  },
  "competitor_benchmarks": {
    "niche_avg_views": "estimated avg views for similar channels in this niche",
    "niche_avg_engagement": "estimated engagement rate for niche",
    "niche_avg_frequency": "estimated posting frequency for niche",
    "performance_vs_niche": "Above Average|Average|Below Average",
    "percentile_estimate": "top X% of niche",
    "comparison_summary": "1-2 sentences comparing channel to niche averages"
  },
  "growth_assessment": {
    "current_trajectory": "Accelerating|Steady|Plateauing|Declining",
    "subscriber_milestone": "Next milestone + estimated time",
    "monthly_view_potential": "estimated achievable monthly views",
    "growth_blockers": ["blocker1","blocker2"],
    "growth_accelerators": ["accelerator1","accelerator2"]
  },
  "monetization_insights": {
    "estimated_monthly_revenue": "$X - $Y",
    "estimated_cpm_range": "$X - $Y",
    "estimated_annual_revenue": "$X - $Y",
    "sponsorship_readiness": "Ready|Almost Ready|Not Yet",
    "sponsorship_rate_estimate": "$X - $Y per video",
    "top_revenue_opportunities": ["opp1","opp2","opp3"]
  },
  "swot": {
    "strengths": ["s1","s2","s3"],
    "weaknesses": ["w1","w2","w3"],
    "opportunities": ["o1","o2","o3"],
    "threats": ["t1","t2","t3"]
  },
  "action_plan": [
    {"week":"Week 1-2","title":"short title","action":"detailed action","impact":"high|medium|low"},
    {"week":"Week 3-4","title":"short title","action":"detailed action","impact":"high|medium|low"},
    {"week":"Week 5-6","title":"short title","action":"detailed action","impact":"high|medium|low"},
    {"week":"Week 7-8","title":"short title","action":"detailed action","impact":"high|medium|low"},
    {"week":"Week 9-10","title":"short title","action":"detailed action","impact":"medium|low"},
    {"week":"Week 11-12","title":"short title","action":"detailed action","impact":"medium|low"}
  ],
  "recommendations": [
    {"title":"short title","description":"detailed recommendation","priority":"high|medium|low","category":"seo|content|engagement|growth|monetization"}
  ],
  "key_takeaway": "One powerful sentence summarizing the single most important insight for this channel"
}
JSON
                . "\n\nRules:\n"
                . "- Reference actual video titles and channel data in your analysis\n"
                . "- All scores must be justified by the data provided\n"
                . "- Interpret and contextualize the computed metrics, don't just repeat them\n"
                . "- Include 6-10 prioritized recommendations with category tags\n"
                . "- Action plan should cover 90 days (6-8 items) with realistic, specific actions\n"
                . "- Quick wins must be things the channel can do THIS WEEK with minimal effort\n"
                . "- Competitor benchmarks should be realistic estimates for the detected niche\n"
                . "- Engagement funnel percentages must match the computed metrics provided\n"
                . "- Be specific: mention video titles, actual numbers, and concrete strategies\n"
                . "\nRespond with ONLY the JSON object.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1, 'maxTokens' => 5000], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $parsed = $this->parseJsonResponse($result['data'][0] ?? '{}');

            // Merge channel info
            $parsed['channel_info'] = [
                'title' => $channelData['title'],
                'thumbnail' => $channelData['thumbnail'],
                'subscribers' => $channelData['subscribers'],
            ];

            // Merge channel stats
            $parsed['channel_stats'] = [
                'total_views' => $channelData['total_views'],
                'video_count' => $channelData['video_count'],
                'country' => $channelData['country'],
                'published_at' => $channelData['published_at'],
            ];

            // Merge computed metrics
            $parsed['computed_metrics'] = $metrics;

            // Merge recent videos (full array for blade cards)
            $parsed['recent_videos'] = $recentVideos;

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

        $subscribers = max(1, $channelData['subscribers'] ?? 1);

        // Views-to-subs ratio
        $viewsToSubsRatio = ($avgViews / $subscribers) * 100;

        // Like-to-view and comment-to-view ratios
        $likeToViewRatio = $avgViews > 0 ? ($avgLikes / $avgViews) * 100 : 0;
        $commentToViewRatio = $avgViews > 0 ? ($avgComments / $avgViews) * 100 : 0;

        // Posting frequency & upload gaps
        $postingFrequency = 0;
        $avgDaysBetweenUploads = 0;
        $uploadConsistencyDays = 0;
        $dates = [];

        if ($count >= 2) {
            $dates = array_filter(array_column($recentVideos, 'published_at'));
            if (count($dates) >= 2) {
                $timestamps = array_map('strtotime', $dates);
                sort($timestamps);

                $oldest = $timestamps[0];
                $newest = end($timestamps);
                $daySpan = max(1, ($newest - $oldest) / 86400);
                $postingFrequency = round(($count / $daySpan) * 30, 1);

                // Calculate gaps between consecutive uploads
                $gaps = [];
                for ($i = 1; $i < count($timestamps); $i++) {
                    $gaps[] = ($timestamps[$i] - $timestamps[$i - 1]) / 86400;
                }

                if (!empty($gaps)) {
                    $avgDaysBetweenUploads = array_sum($gaps) / count($gaps);

                    // Standard deviation of gaps
                    $mean = $avgDaysBetweenUploads;
                    $variance = 0;
                    foreach ($gaps as $gap) {
                        $variance += pow($gap - $mean, 2);
                    }
                    $uploadConsistencyDays = sqrt($variance / count($gaps));
                }
            }
        }

        // Estimated monthly views
        $estMonthlyViews = round($avgViews * $postingFrequency);

        // Top 3 and bottom 3 videos by views
        $sortedByViews = $recentVideos;
        usort($sortedByViews, fn($a, $b) => ($b['views'] ?? 0) - ($a['views'] ?? 0));

        $topVideos = array_map(fn($v) => [
            'title' => $v['title'],
            'views' => $v['views'] ?? 0,
            'likes' => $v['likes'] ?? 0,
            'thumbnail' => $v['thumbnail'] ?? '',
            'published_at' => $v['published_at'] ?? '',
            'id' => $v['id'] ?? '',
        ], array_slice($sortedByViews, 0, 3));

        $bottomVideos = array_map(fn($v) => [
            'title' => $v['title'],
            'views' => $v['views'] ?? 0,
            'likes' => $v['likes'] ?? 0,
            'thumbnail' => $v['thumbnail'] ?? '',
            'published_at' => $v['published_at'] ?? '',
            'id' => $v['id'] ?? '',
        ], array_slice($sortedByViews, -3));

        // Average view velocity (views / days since publish)
        $avgViewVelocity = 0;
        $now = time();
        $velocities = [];
        foreach ($recentVideos as $v) {
            if (!empty($v['published_at'])) {
                $daysSince = max(1, ($now - strtotime($v['published_at'])) / 86400);
                $velocities[] = ($v['views'] ?? 0) / $daysSince;
            }
        }
        if (!empty($velocities)) {
            $avgViewVelocity = array_sum($velocities) / count($velocities);
        }

        // Average duration in seconds (parse ISO 8601)
        $avgDurationSeconds = 0;
        $durations = [];
        foreach ($recentVideos as $v) {
            if (!empty($v['duration'])) {
                $seconds = $this->parseIsoDuration($v['duration']);
                if ($seconds > 0) {
                    $durations[] = $seconds;
                }
            }
        }
        if (!empty($durations)) {
            $avgDurationSeconds = round(array_sum($durations) / count($durations));
        }

        // View trend: compare newest 10 vs oldest 10
        $viewTrendPct = 0;
        if ($count >= 4) {
            $half = (int) floor($count / 2);
            // Sort by published_at descending
            $sortedByDate = $recentVideos;
            usort($sortedByDate, fn($a, $b) => strtotime($b['published_at'] ?? '0') - strtotime($a['published_at'] ?? '0'));

            $newestHalf = array_slice($sortedByDate, 0, $half);
            $oldestHalf = array_slice($sortedByDate, -$half);

            $newestAvg = array_sum(array_column($newestHalf, 'views')) / max(1, count($newestHalf));
            $oldestAvg = array_sum(array_column($oldestHalf, 'views')) / max(1, count($oldestHalf));

            if ($oldestAvg > 0) {
                $viewTrendPct = (($newestAvg - $oldestAvg) / $oldestAvg) * 100;
            }
        }

        return [
            'avg_views' => $avgViews,
            'avg_likes' => $avgLikes,
            'avg_comments' => $avgComments,
            'engagement_rate' => $engagementRate,
            'posting_frequency' => $postingFrequency,
            'views_to_subs_ratio' => $viewsToSubsRatio,
            'like_to_view_ratio' => $likeToViewRatio,
            'comment_to_view_ratio' => $commentToViewRatio,
            'est_monthly_views' => $estMonthlyViews,
            'top_videos' => $topVideos,
            'bottom_videos' => $bottomVideos,
            'avg_view_velocity' => $avgViewVelocity,
            'upload_consistency_days' => $uploadConsistencyDays,
            'avg_days_between_uploads' => $avgDaysBetweenUploads,
            'avg_duration_seconds' => $avgDurationSeconds,
            'view_trend_pct' => $viewTrendPct,
        ];
    }

    /**
     * Parse ISO 8601 duration (PT1H2M3S) to seconds.
     */
    protected function parseIsoDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
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
