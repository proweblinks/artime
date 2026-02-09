<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class VideoOptimizerService
{
    protected PlatformDataService $platformService;

    public function __construct(PlatformDataService $platformService)
    {
        $this->platformService = $platformService;
    }

    /**
     * Optimize video SEO: titles, description, tags, and score.
     */
    public function optimize(string $url, string $platform): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        // Fetch video data if YouTube
        $videoInfo = null;
        if ($platform === 'youtube') {
            try {
                $videoInfo = $this->platformService->analyzePlatformUrl($url);
            } catch (\Exception $e) {
                Log::warning('VideoOptimizer: Could not fetch video data', ['error' => $e->getMessage()]);
            }
        }

        // Create history record
        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'video_optimizer',
            'platform' => $platform,
            'title' => $videoInfo['title'] ?? $url,
            'input_data' => ['url' => $url, 'platform' => $platform, 'video_info' => $videoInfo],
            'status' => 2, // processing
        ]);

        try {
            $platformConfig = config("appaitools.platforms.{$platform}", []);
            $platformName = $platformConfig['name'] ?? ucfirst($platform);

            // Build context from video data
            $videoContext = '';
            if ($videoInfo) {
                $videoContext = "Current video data:\n"
                    . "Title: {$videoInfo['title']}\n"
                    . "Description: " . mb_substr($videoInfo['description'] ?? '', 0, 500) . "\n"
                    . "Tags: " . implode(', ', array_slice($videoInfo['tags'] ?? [], 0, 20)) . "\n"
                    . "Views: " . number_format($videoInfo['views'] ?? 0) . "\n"
                    . "Likes: " . number_format($videoInfo['likes'] ?? 0) . "\n"
                    . "Comments: " . number_format($videoInfo['comments'] ?? 0) . "\n";
            } else {
                $videoContext = "Video URL: {$url}\nPlatform: {$platformName}\n";
            }

            // Generate optimized titles
            $titlePrompt = "You are an expert {$platformName} SEO specialist. Analyze this video and generate 3 optimized title variants.\n\n"
                . $videoContext
                . "\nRules:\n"
                . "- Each title must be under " . ($platformConfig['max_title'] ?? 100) . " characters\n"
                . "- Use power words that drive clicks\n"
                . "- Include relevant keywords naturally\n"
                . "- Make each title distinctly different in approach (curiosity, benefit, urgency)\n"
                . "\nRespond with ONLY a JSON array of 3 title strings, no explanation. Example: [\"Title 1\", \"Title 2\", \"Title 3\"]";

            $titleResult = AI::process($titlePrompt, 'text', ['maxResult' => 1], $teamId);
            $titles = $this->parseJsonArray($titleResult['data'][0] ?? '[]');

            // Generate optimized description
            $descPrompt = "You are an expert {$platformName} SEO specialist. Write an optimized description for this video.\n\n"
                . $videoContext
                . "\nRules:\n"
                . "- Max " . ($platformConfig['max_description'] ?? 5000) . " characters\n"
                . "- Front-load important keywords in the first 2 lines (visible before 'Show More')\n"
                . "- Include a clear call-to-action\n"
                . "- Add relevant timestamps if video is longer than 3 minutes\n"
                . "- Use natural language with keywords woven in\n"
                . "- Include 3-5 relevant hashtags at the end\n"
                . "\nRespond with ONLY the optimized description text, no explanation or quotes.";

            $descResult = AI::process($descPrompt, 'text', ['maxResult' => 1], $teamId);
            $description = trim($descResult['data'][0] ?? '');

            // Generate tags
            $tagPrompt = "You are an expert {$platformName} SEO specialist. Generate optimized tags/hashtags for this video.\n\n"
                . $videoContext
                . "\nRules:\n"
                . "- Generate 15-20 highly relevant tags\n"
                . "- Mix broad and specific (long-tail) tags\n"
                . "- Include trending and evergreen tags\n"
                . "- Order by relevance (most relevant first)\n"
                . "\nRespond with ONLY a JSON array of tag strings. Example: [\"tag1\", \"tag2\", \"tag3\"]";

            $tagResult = AI::process($tagPrompt, 'text', ['maxResult' => 1], $teamId);
            $tags = $this->parseJsonArray($tagResult['data'][0] ?? '[]');

            // Calculate SEO score
            $seoScore = $this->calculateSeoScore($videoInfo, $titles, $description, $tags, $platform);

            $result = [
                'video_info' => $videoInfo,
                'titles' => $titles,
                'description' => $description,
                'tags' => $tags,
                'seo_score' => $seoScore['score'],
                'seo_summary' => $seoScore['summary'],
            ];

            // Calculate total credits used
            $totalTokens = ($titleResult['totalTokens'] ?? 0) + ($descResult['totalTokens'] ?? 0) + ($tagResult['totalTokens'] ?? 0);

            $history->update([
                'result_data' => $result,
                'status' => 1,
                'credits_used' => $totalTokens,
            ]);

            return $result;

        } catch (\Exception $e) {
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    /**
     * Calculate SEO score based on video metadata quality.
     */
    protected function calculateSeoScore(?array $videoInfo, array $titles, string $description, array $tags, string $platform): array
    {
        $score = 0;
        $issues = [];

        // Title quality (25 points)
        $currentTitle = $videoInfo['title'] ?? '';
        if (strlen($currentTitle) > 30 && strlen($currentTitle) < 70) {
            $score += 25;
        } elseif (strlen($currentTitle) > 20) {
            $score += 15;
            $issues[] = 'Title length could be optimized';
        } else {
            $score += 5;
            $issues[] = 'Title is too short for SEO';
        }

        // Description quality (25 points)
        $currentDesc = $videoInfo['description'] ?? '';
        if (strlen($currentDesc) > 200) {
            $score += 25;
        } elseif (strlen($currentDesc) > 50) {
            $score += 15;
            $issues[] = 'Description should be longer and more detailed';
        } else {
            $score += 5;
            $issues[] = 'Description is too short';
        }

        // Tags quality (25 points)
        $currentTags = $videoInfo['tags'] ?? [];
        if (count($currentTags) >= 10) {
            $score += 25;
        } elseif (count($currentTags) >= 5) {
            $score += 15;
            $issues[] = 'Add more relevant tags';
        } else {
            $score += 5;
            $issues[] = 'Very few or no tags found';
        }

        // Engagement signals (25 points)
        $views = $videoInfo['views'] ?? 0;
        $likes = $videoInfo['likes'] ?? 0;
        if ($views > 0) {
            $engagementRate = ($likes / $views) * 100;
            if ($engagementRate > 5) $score += 25;
            elseif ($engagementRate > 2) $score += 15;
            else {
                $score += 10;
                $issues[] = 'Engagement rate is below average';
            }
        } else {
            $score += 10; // Default for no data
        }

        $summary = empty($issues) ? 'Great SEO optimization!' : implode('. ', array_slice($issues, 0, 2)) . '.';

        return ['score' => min(100, $score), 'summary' => $summary];
    }

    /**
     * Parse JSON array from AI response, handling common formatting issues.
     */
    protected function parseJsonArray(string $text): array
    {
        $text = trim($text);

        // Remove markdown code block markers
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try to extract JSON array from text
        if (preg_match('/\[.*\]/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fall back to splitting by newlines
        return array_filter(array_map('trim', explode("\n", $text)));
    }
}
