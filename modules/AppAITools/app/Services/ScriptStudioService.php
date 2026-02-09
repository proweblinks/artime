<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Modules\AppAITools\Models\AiToolHistory;

class ScriptStudioService
{
    /**
     * Generate a complete video script.
     */
    public function generate(string $topic, string $duration, string $style, string $platform): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'script_studio',
            'platform' => $platform,
            'title' => "Script: " . mb_substr($topic, 0, 100),
            'input_data' => compact('topic', 'duration', 'style', 'platform'),
            'status' => 2,
        ]);

        try {
            $platformName = config("appaitools.platforms.{$platform}.name", ucfirst($platform));
            $durationConfig = config("appaitools.script_durations.{$duration}", ['label' => 'Medium', 'words' => '300-750']);
            $styleName = config("appaitools.script_styles.{$style}", 'Engaging');

            $prompt = "You are an expert video scriptwriter specializing in {$platformName} content. "
                . "Write a complete, ready-to-read video script.\n\n"
                . "Topic: {$topic}\n"
                . "Duration: {$durationConfig['label']} ({$durationConfig['words']} words)\n"
                . "Style: {$styleName}\n"
                . "Platform: {$platformName}\n"
                . "\nScript Structure:\n"
                . "1. HOOK (first 5-10 seconds) - Grab attention immediately\n"
                . "2. INTRO - Brief context setting\n"
                . "3. MAIN CONTENT - 3-5 key sections with clear transitions\n"
                . "4. CALL TO ACTION - What should viewers do next?\n"
                . "5. OUTRO - Brief sign-off\n"
                . "\nRules:\n"
                . "- Write in conversational, spoken-word style\n"
                . "- Include [PAUSE], [EMPHASIS], and [B-ROLL] cues where appropriate\n"
                . "- Target word count: {$durationConfig['words']} words\n"
                . "- Optimize for {$platformName} audience retention patterns\n"
                . "- Make each section clearly labeled with a header\n"
                . "\nWrite the complete script now. Do NOT wrap in JSON or code blocks.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $script = trim($result['data'][0] ?? '');
            $wordCount = str_word_count($script);
            $estimatedDuration = $this->estimateDuration($wordCount);

            $output = [
                'script' => $script,
                'word_count' => $wordCount,
                'estimated_duration' => $estimatedDuration,
                'style' => $style,
                'duration_target' => $duration,
            ];

            $history->update([
                'result_data' => $output,
                'status' => 1,
                'credits_used' => $result['totalTokens'] ?? 0,
            ]);

            return $output;

        } catch (\Exception $e) {
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    protected function estimateDuration(int $wordCount): string
    {
        // Average speaking rate: ~150 words per minute
        $minutes = $wordCount / 150;

        if ($minutes < 1) {
            return round($minutes * 60) . ' seconds';
        }
        if ($minutes < 2) {
            return '~1 minute';
        }

        return '~' . round($minutes) . ' minutes';
    }
}
