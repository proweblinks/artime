<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class CompetitorAnalysisService
{
    protected YouTubeDataService $youtube;

    public function __construct(YouTubeDataService $youtube)
    {
        $this->youtube = $youtube;
    }

    /**
     * Analyze competitor channel with competitive intelligence.
     */
    public function analyze(string $competitorUrl, string $myUrl = '', string $platform = 'youtube'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'competitor_analysis',
            'platform' => $platform,
            'title' => "Competitor: {$competitorUrl}",
            'input_data' => [
                'competitor_url' => $competitorUrl,
                'my_url' => $myUrl,
                'platform' => $platform,
            ],
            'status' => 2,
        ]);

        try {
            if ($platform !== 'youtube') {
                throw new \Exception('Competitor analysis currently supports YouTube only.');
            }

            // Resolve competitor channel data
            $competitorChannel = $this->resolveChannel($competitorUrl);
            if (!$competitorChannel) {
                throw new \Exception('Could not fetch competitor channel data. Please check the URL format.');
            }

            // Fetch 20 recent videos
            $competitorVideos = [];
            try {
                $competitorVideos = $this->youtube->getChannelVideos($competitorChannel['id'], 20);
            } catch (\Exception $e) {
                Log::info('CompetitorAnalysis: Could not fetch competitor videos', ['error' => $e->getMessage()]);
            }

            // Calculate competitor metrics
            $competitorMetrics = $this->calculateMetrics($competitorChannel, $competitorVideos);

            // Optionally fetch user's channel for head-to-head
            $myChannel = null;
            $myVideos = [];
            $myMetrics = [];
            if (!empty($myUrl)) {
                try {
                    $myChannel = $this->resolveChannel($myUrl);
                    if ($myChannel) {
                        $myVideos = $this->youtube->getChannelVideos($myChannel['id'], 20);
                        $myMetrics = $this->calculateMetrics($myChannel, $myVideos);
                    }
                } catch (\Exception $e) {
                    Log::info('CompetitorAnalysis: Could not fetch user channel', ['error' => $e->getMessage()]);
                }
            }

            // Build AI context
            $context = $this->buildContext($competitorChannel, $competitorMetrics, $competitorVideos, $myChannel, $myMetrics, $myVideos);

            // Build prompt
            $prompt = $this->buildPrompt($context, !empty($myChannel));

            $aiPlatform = get_option('ai_platform', 'openai');
            $result = AI::processWithOverride($prompt, $aiPlatform, null, 'text', [
                'maxResult' => 1,
                'max_tokens' => 5000,
            ], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $parsed = $this->parseJsonResponse($result['data'][0] ?? '{}');

            // Merge extra data
            $parsed['competitor_info'] = [
                'title' => $competitorChannel['title'],
                'thumbnail' => $competitorChannel['thumbnail'],
                'subscribers' => $competitorChannel['subscribers'],
                'total_views' => $competitorChannel['total_views'],
                'video_count' => $competitorChannel['video_count'],
                'country' => $competitorChannel['country'],
                'published_at' => $competitorChannel['published_at'],
                'description' => mb_substr($competitorChannel['description'] ?? '', 0, 300),
            ];

            $parsed['computed_metrics'] = $competitorMetrics;
            $parsed['recent_videos'] = $competitorVideos;

            if ($myChannel) {
                $parsed['my_info'] = [
                    'title' => $myChannel['title'],
                    'thumbnail' => $myChannel['thumbnail'],
                    'subscribers' => $myChannel['subscribers'],
                    'total_views' => $myChannel['total_views'],
                    'video_count' => $myChannel['video_count'],
                    'country' => $myChannel['country'],
                    'published_at' => $myChannel['published_at'],
                ];
                $parsed['my_metrics'] = $myMetrics;
            }

            $history->update([
                'title' => "Competitor: {$competitorChannel['title']}",
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
     * Resolve a URL (channel or video) to channel data.
     */
    protected function resolveChannel(string $url): ?array
    {
        // Try as channel URL first
        $channelId = $this->youtube->extractChannelId($url);
        if ($channelId) {
            return $this->youtube->getChannelData($url);
        }

        // Try as video URL — extract channel_id from video data
        $videoId = $this->youtube->extractVideoId($url);
        if ($videoId) {
            $videoData = $this->youtube->getVideoData($videoId);
            if ($videoData && !empty($videoData['channel_id'])) {
                $channelUrl = 'https://youtube.com/channel/' . $videoData['channel_id'];
                return $this->youtube->getChannelData($channelUrl);
            }
        }

        throw new \Exception('Could not parse URL. Please provide a YouTube channel URL (@handle, /channel/ID) or a video URL.');
    }

    /**
     * Calculate channel metrics from video data.
     * Reuses the same pattern as ChannelAuditService.
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
        $viewsToSubsRatio = ($avgViews / $subscribers) * 100;
        $likeToViewRatio = $avgViews > 0 ? ($avgLikes / $avgViews) * 100 : 0;
        $commentToViewRatio = $avgViews > 0 ? ($avgComments / $avgViews) * 100 : 0;

        // Posting frequency & upload gaps
        $postingFrequency = 0;
        $avgDaysBetweenUploads = 0;
        $uploadConsistencyDays = 0;

        if ($count >= 2) {
            $dates = array_filter(array_column($recentVideos, 'published_at'));
            if (count($dates) >= 2) {
                $timestamps = array_map('strtotime', $dates);
                sort($timestamps);

                $oldest = $timestamps[0];
                $newest = end($timestamps);
                $daySpan = max(1, ($newest - $oldest) / 86400);
                $postingFrequency = round(($count / $daySpan) * 30, 1);

                $gaps = [];
                for ($i = 1; $i < count($timestamps); $i++) {
                    $gaps[] = ($timestamps[$i] - $timestamps[$i - 1]) / 86400;
                }

                if (!empty($gaps)) {
                    $avgDaysBetweenUploads = array_sum($gaps) / count($gaps);
                    $mean = $avgDaysBetweenUploads;
                    $variance = 0;
                    foreach ($gaps as $gap) {
                        $variance += pow($gap - $mean, 2);
                    }
                    $uploadConsistencyDays = sqrt($variance / count($gaps));
                }
            }
        }

        $estMonthlyViews = round($avgViews * $postingFrequency);

        // Top 5 and bottom 5 videos by views
        $sortedByViews = $recentVideos;
        usort($sortedByViews, fn($a, $b) => ($b['views'] ?? 0) - ($a['views'] ?? 0));

        $topVideos = array_map(fn($v) => [
            'title' => $v['title'],
            'views' => $v['views'] ?? 0,
            'likes' => $v['likes'] ?? 0,
            'thumbnail' => $v['thumbnail'] ?? '',
            'published_at' => $v['published_at'] ?? '',
            'id' => $v['id'] ?? '',
        ], array_slice($sortedByViews, 0, 5));

        $bottomVideos = array_map(fn($v) => [
            'title' => $v['title'],
            'views' => $v['views'] ?? 0,
            'likes' => $v['likes'] ?? 0,
            'thumbnail' => $v['thumbnail'] ?? '',
            'published_at' => $v['published_at'] ?? '',
            'id' => $v['id'] ?? '',
        ], array_slice($sortedByViews, -5));

        // Average view velocity
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

        // Average duration in seconds
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

        // View trend: newest half vs oldest half
        $viewTrendPct = 0;
        if ($count >= 4) {
            $half = (int) floor($count / 2);
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
     * Build AI context string from channel data.
     */
    protected function buildContext(array $channel, array $metrics, array $videos, ?array $myChannel, array $myMetrics, array $myVideos): string
    {
        $context = "COMPETITOR CHANNEL DATA:\n"
            . "Channel: {$channel['title']}\n"
            . "Subscribers: " . number_format($channel['subscribers']) . "\n"
            . "Total Views: " . number_format($channel['total_views']) . "\n"
            . "Total Videos: " . number_format($channel['video_count']) . "\n"
            . "Country: {$channel['country']}\n"
            . "Channel Created: {$channel['published_at']}\n"
            . "Description: " . mb_substr($channel['description'] ?? '', 0, 300) . "\n"
            . "\nCOMPUTED METRICS:\n"
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

        if (!empty($videos)) {
            $context .= "\nALL 20 RECENT VIDEOS (title, views, likes, comments, duration):\n";
            foreach ($videos as $i => $v) {
                $context .= ($i + 1) . ". \"{$v['title']}\" - " . number_format($v['views']) . " views, "
                    . number_format($v['likes']) . " likes, " . number_format($v['comments']) . " comments, "
                    . "duration: {$v['duration']}\n";
            }

            $context .= "\nTOP 5 BY VIEWS:\n";
            foreach ($metrics['top_videos'] as $v) {
                $context .= "- \"{$v['title']}\" — " . number_format($v['views']) . " views\n";
            }
            $context .= "BOTTOM 5 BY VIEWS:\n";
            foreach ($metrics['bottom_videos'] as $v) {
                $context .= "- \"{$v['title']}\" — " . number_format($v['views']) . " views\n";
            }
        }

        if ($myChannel) {
            $context .= "\n\n--- YOUR CHANNEL DATA (for head-to-head comparison) ---\n"
                . "Channel: {$myChannel['title']}\n"
                . "Subscribers: " . number_format($myChannel['subscribers']) . "\n"
                . "Total Views: " . number_format($myChannel['total_views']) . "\n"
                . "Total Videos: " . number_format($myChannel['video_count']) . "\n"
                . "Country: {$myChannel['country']}\n"
                . "\nYOUR COMPUTED METRICS:\n"
                . "Average Views/Video: " . number_format($myMetrics['avg_views'] ?? 0) . "\n"
                . "Average Likes/Video: " . number_format($myMetrics['avg_likes'] ?? 0) . "\n"
                . "Engagement Rate: " . number_format($myMetrics['engagement_rate'] ?? 0, 2) . "%\n"
                . "Posting Frequency: ~" . ($myMetrics['posting_frequency'] ?? 0) . " videos/month\n"
                . "Views/Subs Ratio: " . number_format($myMetrics['views_to_subs_ratio'] ?? 0, 1) . "%\n"
                . "View Trend: " . (($myMetrics['view_trend_pct'] ?? 0) >= 0 ? '+' : '') . number_format($myMetrics['view_trend_pct'] ?? 0, 1) . "%\n";

            if (!empty($myVideos)) {
                $context .= "\nYOUR RECENT VIDEO TITLES:\n";
                foreach (array_slice($myVideos, 0, 10) as $i => $v) {
                    $context .= ($i + 1) . ". \"{$v['title']}\" - " . number_format($v['views']) . " views\n";
                }
            }
        }

        return $context;
    }

    /**
     * Build AI prompt for competitive intelligence.
     */
    protected function buildPrompt(string $context, bool $hasHeadToHead): string
    {
        $h2hBlock = $hasHeadToHead ? <<<'JSON'
  "head_to_head": {
    "verdict": "You're ahead|Neck and neck|They're ahead|Different leagues",
    "your_advantages": ["adv1", "adv2", "adv3"],
    "their_advantages": ["adv1", "adv2", "adv3"],
    "key_battleground": "the one metric/area where the competition is tightest",
    "win_probability": "percentage estimate with reasoning"
  },
JSON
        : '';

        $h2hRule = $hasHeadToHead
            ? "- head_to_head: provide honest comparison based on actual metrics provided\n"
            : "- Omit the head_to_head section entirely since no user channel data was provided\n";

        return "You are a competitive intelligence analyst — not a friendly coach. You analyze YouTube channels like a spy analyzing an adversary. Be sharp, specific, and brutally honest.\n\n"
            . $context
            . "\n\nProvide your competitive intelligence report as a JSON object with this exact structure:\n"
            . <<<JSON
{
  "threat_level": 1-10,
  "threat_label": "Dominant|Formidable|Moderate|Beatable|Vulnerable",
  "competitor_score": 0-100,
  "competitor_grade": "A+|A|B+|B|C+|C|D|F",
  "detected_niche": "their primary niche",
  "executive_summary": "3-4 sentence competitive intelligence brief",
  "categories": [
    {"name": "Content Quality", "score": 0-100, "summary": "1 sentence", "icon": "fa-gem"},
    {"name": "SEO & Discoverability", "score": 0-100, "summary": "1 sentence", "icon": "fa-magnifying-glass-chart"},
    {"name": "Audience Engagement", "score": 0-100, "summary": "1 sentence", "icon": "fa-comments"},
    {"name": "Growth Momentum", "score": 0-100, "summary": "1 sentence", "icon": "fa-rocket"},
    {"name": "Brand Authority", "score": 0-100, "summary": "1 sentence", "icon": "fa-crown"},
    {"name": "Monetization Power", "score": 0-100, "summary": "1 sentence", "icon": "fa-dollar-sign"}
  ],
  "steal_their_strategy": {
    "content_formula": "describe their repeatable content formula",
    "title_patterns": ["pattern 1 with example", "pattern 2 with example", "pattern 3"],
    "upload_strategy": "their upload timing/frequency strategy",
    "engagement_hooks": ["hook technique 1", "hook technique 2", "hook technique 3"],
    "thumbnail_style": "describe their thumbnail approach",
    "what_to_copy": ["specific tactic 1", "specific tactic 2", "specific tactic 3"],
    "what_to_avoid": ["thing they do poorly 1", "thing they do poorly 2"]
  },
  "content_gap_radar": [
    {"gap": "specific topic/format gap", "opportunity_size": "high|medium|low", "difficulty": "easy|medium|hard", "example_title": "suggested video title"}
  ],
  "weakness_exploits": [
    {"weakness": "specific weakness", "how_to_exploit": "concrete action", "potential_impact": "high|medium"}
  ],
  {$h2hBlock}
  "content_analysis": {
    "best_performing_topics": ["topic1","topic2","topic3"],
    "content_pillars": ["pillar1","pillar2","pillar3"],
    "format_breakdown": [
      {"format": "format name", "percentage": 40, "avg_performance": "above average|average|below average"}
    ],
    "optimal_length": "e.g. 12-18 minutes",
    "posting_schedule": "e.g. Mon/Wed/Fri at 2pm EST"
  },
  "monetization_intel": {
    "estimated_monthly_revenue": "\$X - \$Y",
    "estimated_cpm_range": "\$X - \$Y",
    "sponsorship_likelihood": "High|Medium|Low",
    "estimated_sponsorship_rate": "\$X - \$Y per video",
    "revenue_streams": ["stream1","stream2","stream3"]
  },
  "swot": {
    "strengths": ["s1","s2","s3","s4"],
    "weaknesses": ["w1","w2","w3","w4"],
    "opportunities": ["o1","o2","o3"],
    "threats": ["t1","t2","t3"]
  },
  "battle_plan": [
    {"phase": "Phase 1: Quick Wins (Week 1-2)", "actions": ["action1","action2","action3"], "goal": "what this achieves"},
    {"phase": "Phase 2: Content Attack (Week 3-6)", "actions": ["action1","action2","action3"], "goal": "what this achieves"},
    {"phase": "Phase 3: Authority Build (Week 7-10)", "actions": ["action1","action2"], "goal": "what this achieves"},
    {"phase": "Phase 4: Domination (Week 11-12)", "actions": ["action1","action2"], "goal": "what this achieves"}
  ],
  "recommendations": [
    {"title":"short title","description":"detailed rec","priority":"high|medium|low","category":"content|seo|engagement|growth|monetization"}
  ]
}
JSON
            . "\n\nRules:\n"
            . "- You are a competitive intelligence analyst, not a friendly coach\n"
            . "- Reference actual video titles and real data from the channel\n"
            . "- 'steal_their_strategy' must reference their actual content patterns from the video list\n"
            . "- 'content_gap_radar': 5-8 specific gaps, not generic advice. Include example_title for each\n"
            . "- 'weakness_exploits': 4-6 actionable weaknesses, not obvious platitudes\n"
            . $h2hRule
            . "- Be brutally honest about threat level based on subscriber count, engagement, and growth\n"
            . "- 'battle_plan' must be a 4-phase tactical plan, not generic advice\n"
            . "- Recommendations: 6-10 items with priority and category tags\n"
            . "- All scores must be justified by the data\n"
            . "\nRespond with ONLY the JSON object.";
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
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) return $decoded;
        }

        $jsonStart = strpos($text, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($text, $jsonStart);
            $len = strlen($jsonStr);
            $repaired = '';
            $stack = [];
            $inString = false;
            $escape = false;

            for ($i = 0; $i < $len; $i++) {
                $ch = $jsonStr[$i];
                if ($escape) { $escape = false; $repaired .= $ch; continue; }
                if ($ch === '\\' && $inString) { $escape = true; $repaired .= $ch; continue; }
                if ($ch === '"') { $inString = !$inString; $repaired .= $ch; continue; }
                if ($inString) { $repaired .= $ch; continue; }
                if ($ch === '{' || $ch === '[') { $stack[] = $ch; $repaired .= $ch; continue; }
                if ($ch === '}') {
                    if (!empty($stack) && end($stack) === '{') { array_pop($stack); $repaired .= $ch; }
                    continue;
                }
                if ($ch === ']') {
                    if (!empty($stack) && end($stack) === '[') { array_pop($stack); $repaired .= $ch; }
                    continue;
                }
                $repaired .= $ch;
            }

            $repaired = rtrim($repaired, " \t\n\r,:");
            $repaired = preg_replace('/"[^"]*$/', '""', $repaired);
            while (!empty($stack)) {
                $opener = array_pop($stack);
                $repaired .= ($opener === '{') ? '}' : ']';
            }

            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) return $decoded;
        }

        return [];
    }
}
