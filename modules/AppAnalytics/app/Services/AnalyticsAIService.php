<?php

namespace Modules\AppAnalytics\Services;

use Modules\AppAnalytics\Models\AnalyticsAIInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnalyticsAIService
{
    /**
     * Generate weekly insights for a specific platform account.
     */
    public function generateWeeklyInsights(int $teamId, ?int $accountId, string $platform, array $metricsData): ?string
    {
        if (!get_option('analytics_ai_insights', 1)) {
            return null;
        }

        // Check if we already generated insights this week for this account
        $weekStart = Carbon::now()->startOfWeek();
        $existing = AnalyticsAIInsight::where('team_id', $teamId)
            ->where('account_id', $accountId)
            ->where('insight_type', 'weekly_summary')
            ->where('period_start', $weekStart->toDateString())
            ->first();

        if ($existing) {
            return $existing->content;
        }

        $model = get_option('analytics_ai_model', 'gpt-4o-mini');

        $prompt = $this->buildWeeklyPrompt($platform, $metricsData);

        try {
            $response = \AI::process($prompt, 'text', [
                'model' => $model,
                'max_tokens' => 1200,
            ], $teamId);

            $content = $response['content'] ?? '';

            if (!empty($content)) {
                // Store the insight
                AnalyticsAIInsight::create([
                    'team_id' => $teamId,
                    'account_id' => $accountId,
                    'social_network' => $platform,
                    'insight_type' => 'weekly_summary',
                    'content' => $content,
                    'period_start' => $weekStart->toDateString(),
                    'period_end' => Carbon::now()->endOfWeek()->toDateString(),
                    'created' => time(),
                ]);
            }

            return $content;
        } catch (\Exception $e) {
            Log::warning('Analytics AI Insights error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate content recommendations based on post performance.
     */
    public function generateContentTips(int $teamId, ?int $accountId, string $platform, array $topPosts): ?string
    {
        if (!get_option('analytics_ai_insights', 1)) {
            return null;
        }

        $weekStart = Carbon::now()->startOfWeek();
        $existing = AnalyticsAIInsight::where('team_id', $teamId)
            ->where('account_id', $accountId)
            ->where('insight_type', 'content_tips')
            ->where('period_start', $weekStart->toDateString())
            ->first();

        if ($existing) {
            return $existing->content;
        }

        $model = get_option('analytics_ai_model', 'gpt-4o-mini');

        $prompt = "You are a social media strategist. Analyze the top performing posts on {$platform} this week and provide 3-5 actionable content recommendations.\n\n"
            . "Top posts data:\n" . json_encode($topPosts, JSON_PRETTY_PRINT) . "\n\n"
            . "Provide specific, actionable tips about:\n"
            . "- What content types perform best\n"
            . "- What topics/themes resonate with the audience\n"
            . "- Specific suggestions for upcoming content\n"
            . "Format as markdown with clear headings.";

        try {
            $response = \AI::process($prompt, 'text', [
                'model' => $model,
                'max_tokens' => 800,
            ], $teamId);

            $content = $response['content'] ?? '';

            if (!empty($content)) {
                AnalyticsAIInsight::create([
                    'team_id' => $teamId,
                    'account_id' => $accountId,
                    'social_network' => $platform,
                    'insight_type' => 'content_tips',
                    'content' => $content,
                    'period_start' => $weekStart->toDateString(),
                    'period_end' => Carbon::now()->endOfWeek()->toDateString(),
                    'created' => time(),
                ]);
            }

            return $content;
        } catch (\Exception $e) {
            Log::warning('Analytics AI Content Tips error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all existing insights for a team.
     */
    public function getInsights(int $teamId, ?int $accountId = null, ?string $type = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = AnalyticsAIInsight::where('team_id', $teamId);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }
        if ($type) {
            $query->where('insight_type', $type);
        }

        return $query->orderByDesc('created')->limit($limit)->get();
    }

    /**
     * Build the weekly insights prompt.
     */
    protected function buildWeeklyPrompt(string $platform, array $metricsData): string
    {
        return "You are a social media analytics expert. Analyze the following {$platform} metrics for the past 7 days and provide actionable insights.\n\n"
            . "Metrics data:\n" . json_encode($metricsData, JSON_PRETTY_PRINT) . "\n\n"
            . "Please provide:\n"
            . "1. **Performance Summary** — A brief narrative of how the account performed this week\n"
            . "2. **Key Highlights** — Notable achievements or concerning trends\n"
            . "3. **Recommendations** — 2-3 specific actions to improve performance\n"
            . "4. **Best Posting Times** — If daily data is available, suggest optimal posting times\n\n"
            . "Keep it concise and actionable. Format as markdown.";
    }
}
