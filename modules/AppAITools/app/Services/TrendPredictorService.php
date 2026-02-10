<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class TrendPredictorService
{
    protected YouTubeDataService $youtube;

    public function __construct(YouTubeDataService $youtube)
    {
        $this->youtube = $youtube;
    }

    /**
     * Predict trends for a niche and platform.
     */
    public function predict(string $niche, string $platform, string $region = 'US'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'trend_predictor',
            'platform' => $platform,
            'title' => "Trends: {$niche}",
            'input_data' => ['niche' => $niche, 'platform' => $platform, 'region' => $region],
            'status' => 2,
        ]);

        try {
            $platformName = config("appaitools.platforms.{$platform}.name", ucfirst($platform));

            // For YouTube, try to fetch trending videos in the niche
            $trendingContext = '';
            if ($platform === 'youtube') {
                try {
                    $recentVideos = $this->youtube->searchVideos($niche, [
                        'maxResults' => 10,
                        'order' => 'viewCount',
                        'regionCode' => $region,
                        'publishedAfter' => now()->subDays(7)->toIso8601String(),
                    ]);

                    if (!empty($recentVideos)) {
                        $trendingContext = "\nRecent high-performing videos in this niche (last 7 days):\n";
                        foreach (array_slice($recentVideos, 0, 5) as $v) {
                            $trendingContext .= "- \"{$v['title']}\" by {$v['channel']}\n";
                        }
                    }
                } catch (\Exception $e) {
                    Log::info('TrendPredictor: YouTube search failed, using AI-only prediction');
                }
            }

            $prompt = "You are an expert content trend analyst and viral content predictor specializing in {$platformName}. "
                . "Analyze trends for the niche: \"{$niche}\" in region: {$region}.\n"
                . $trendingContext
                . "\nProvide your analysis as a JSON object with this exact structure:\n"
                . '{"current_trends": [{"topic": "...", "description": "...", "status": "rising|stable|declining", "confidence": 0-100}], '
                . '"predicted_trends": [{"topic": "...", "reasoning": "why this will trend"}], '
                . '"content_ideas": [{"title": "video title idea", "description": "brief description", "estimated_performance": "high|medium|low"}], '
                . '"best_posting_times": ["Mon 2pm EST", "..."], '
                . '"hashtags": ["#tag1", "#tag2"]}'
                . "\n\nRules:\n"
                . "- 5-8 current trends with status indicators\n"
                . "- 3-5 predicted upcoming trends with reasoning\n"
                . "- 5-8 specific content ideas with titles\n"
                . "- 3-5 optimal posting times for the region\n"
                . "- 10-15 relevant trending hashtags\n"
                . "- Be specific to the niche, not generic\n"
                . "\nRespond with ONLY the JSON object.";

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
