<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Modules\AppAITools\Models\AiToolHistory;

class HookGeneratorService
{
    /**
     * Generate viral hooks with effectiveness scores.
     */
    public function generate(string $topic, string $hookStyle, int $count, string $platform): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'viral_hooks',
            'platform' => $platform,
            'title' => "Hooks: " . mb_substr($topic, 0, 100),
            'input_data' => compact('topic', 'hookStyle', 'count', 'platform'),
            'status' => 2,
        ]);

        try {
            $platformName = config("appaitools.platforms.{$platform}.name", ucfirst($platform));
            $styleName = config("appaitools.hook_styles.{$hookStyle}", 'Question');

            $prompt = "You are a viral content expert specializing in {$platformName}. "
                . "Generate {$count} attention-grabbing hooks for videos.\n\n"
                . "Topic: {$topic}\n"
                . "Hook Style: {$styleName}\n"
                . "Platform: {$platformName}\n"
                . "\nRespond with ONLY a JSON array of hook objects:\n"
                . '[{"text": "The hook text", "score": 0-100, "explanation": "Why this hook works"}]'
                . "\n\nRules:\n"
                . "- Each hook must be 1-2 sentences max\n"
                . "- Score = estimated effectiveness (considers curiosity gap, emotional impact, specificity)\n"
                . "- All hooks should use the \"{$styleName}\" style approach\n"
                . "- Optimized for {$platformName} audience behavior\n"
                . "- Order by score descending (best first)\n"
                . "\nRespond with ONLY the JSON array.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $hooks = $this->parseJsonArray($result['data'][0] ?? '[]');

            $output = [
                'hooks' => $hooks,
                'style' => $hookStyle,
                'count' => count($hooks),
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

    protected function parseJsonArray(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        if (preg_match('/\[.*\]/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) return $decoded;
        }

        $jsonStart = strpos($text, '[');
        if ($jsonStart === false) $jsonStart = strpos($text, '{');
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
