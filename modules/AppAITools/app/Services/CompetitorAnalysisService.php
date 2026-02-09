<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class CompetitorAnalysisService
{
    protected PlatformDataService $platformService;

    public function __construct(PlatformDataService $platformService)
    {
        $this->platformService = $platformService;
    }

    /**
     * Analyze competitor video with SWOT, scoring, and recommendations.
     */
    public function analyze(string $competitorUrl, string $myUrl = '', string $platform = 'youtube'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        // Fetch competitor data
        $competitorData = null;
        if ($platform === 'youtube') {
            try {
                $competitorData = $this->platformService->analyzePlatformUrl($competitorUrl);
            } catch (\Exception $e) {
                Log::warning('CompetitorAnalysis: Could not fetch competitor data', ['error' => $e->getMessage()]);
            }
        }

        // Fetch user's video data if provided
        $myData = null;
        if (!empty($myUrl) && $platform === 'youtube') {
            try {
                $myData = $this->platformService->analyzePlatformUrl($myUrl);
            } catch (\Exception $e) {
                Log::info('CompetitorAnalysis: Could not fetch user video data');
            }
        }

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'competitor_analysis',
            'platform' => $platform,
            'title' => $competitorData['title'] ?? $competitorUrl,
            'input_data' => [
                'competitor_url' => $competitorUrl,
                'my_url' => $myUrl,
                'platform' => $platform,
                'competitor_data' => $competitorData,
                'my_data' => $myData,
            ],
            'status' => 2,
        ]);

        try {
            $platformName = config("appaitools.platforms.{$platform}.name", ucfirst($platform));

            $context = "Competitor video data:\n";
            if ($competitorData) {
                $context .= "Title: {$competitorData['title']}\n"
                    . "Description: " . mb_substr($competitorData['description'] ?? '', 0, 500) . "\n"
                    . "Tags: " . implode(', ', array_slice($competitorData['tags'] ?? [], 0, 15)) . "\n"
                    . "Views: " . number_format($competitorData['views'] ?? 0) . "\n"
                    . "Likes: " . number_format($competitorData['likes'] ?? 0) . "\n"
                    . "Comments: " . number_format($competitorData['comments'] ?? 0) . "\n"
                    . "Channel: {$competitorData['channel']}\n";
            } else {
                $context .= "URL: {$competitorUrl}\nPlatform: {$platformName}\n";
            }

            if ($myData) {
                $context .= "\nUser's video data (for comparison):\n"
                    . "Title: {$myData['title']}\n"
                    . "Views: " . number_format($myData['views'] ?? 0) . "\n"
                    . "Likes: " . number_format($myData['likes'] ?? 0) . "\n";
            }

            $prompt = "You are an expert content strategy analyst specializing in {$platformName}. "
                . "Perform a deep competitor analysis.\n\n"
                . $context
                . "\nProvide your analysis as a JSON object with this exact structure:\n"
                . '{"score": 0-100 integer, "summary": "one sentence summary", '
                . '"swot": {"strengths": ["..."], "weaknesses": ["..."], "opportunities": ["..."], "threats": ["..."]}, '
                . '"better_titles": ["3 improved title suggestions"], '
                . '"content_gaps": ["areas the competitor is missing"], '
                . '"recommendations": [{"text": "recommendation text", "priority": "high|medium|low"}]}'
                . "\n\nRules:\n"
                . "- Score reflects overall content quality and optimization (0-100)\n"
                . "- SWOT: 3-5 items per category\n"
                . "- Recommendations: 5-8 actionable items with priority levels\n"
                . "- Focus on actionable, specific insights\n"
                . "\nRespond with ONLY the JSON object, no markdown or explanation.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $parsed = $this->parseJsonResponse($result['data'][0] ?? '{}');

            $history->update([
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

    protected function parseJsonResponse(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try extracting JSON object
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['score' => 0, 'summary' => 'Analysis failed to parse.', 'recommendations' => []];
    }
}
