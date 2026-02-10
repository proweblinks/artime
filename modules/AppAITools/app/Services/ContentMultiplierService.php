<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Modules\AppAITools\Models\AiToolHistory;

class ContentMultiplierService
{
    /**
     * Repurpose content into multiple formats.
     */
    public function multiply(string $originalContent, array $selectedFormats, string $platform = 'general'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'content_multiplier',
            'platform' => $platform,
            'title' => 'Content Multiplier: ' . mb_substr($originalContent, 0, 80),
            'input_data' => [
                'original_length' => strlen($originalContent),
                'formats' => $selectedFormats,
                'platform' => $platform,
            ],
            'status' => 2,
        ]);

        try {
            $formatLabels = config('appaitools.multiplier_formats', []);
            $formatDescriptions = [
                'shorts_script' => 'A 30-60 second Shorts/Reels script with a strong hook, quick value delivery, and CTA. Include [VISUAL CUE] markers.',
                'twitter_thread' => 'A 5-8 tweet thread that tells the story. Each tweet under 280 chars. Include numbering (1/N). Start with a hook tweet.',
                'blog_post' => 'A 500-800 word blog post with H2 headers, introduction, main points, and conclusion. SEO-friendly structure.',
                'quotes' => '5-8 standalone quotable statements that can be used for social media graphics. Each should be impactful and shareable.',
                'email_newsletter' => 'An email newsletter section with subject line, preview text, greeting, main content (3-4 paragraphs), and CTA button text.',
                'linkedin_post' => 'A LinkedIn post (1000-1300 chars) with a professional hook, value points using line breaks, and a conversation-starting question at the end.',
                'instagram_caption' => 'An Instagram caption (under 2200 chars) with a hook first line, value content, CTA, and 20-30 relevant hashtags at the end.',
            ];

            $formatInstructions = '';
            foreach ($selectedFormats as $format) {
                $label = $formatLabels[$format] ?? ucfirst(str_replace('_', ' ', $format));
                $desc = $formatDescriptions[$format] ?? "Repurpose for {$label} format.";
                $formatInstructions .= "\n### {$format}\nFormat: {$label}\nInstructions: {$desc}\n";
            }

            $prompt = "You are an expert content repurposing specialist. Transform this original content into multiple formats.\n\n"
                . "ORIGINAL CONTENT:\n---\n" . mb_substr($originalContent, 0, 5000) . "\n---\n"
                . "\nGenerate content for these formats:{$formatInstructions}"
                . "\nRespond with ONLY a JSON object where keys are format identifiers and values are the generated content strings:\n"
                . '{"format_key": "generated content...", ...}'
                . "\n\nRules:\n"
                . "- Each format must be tailored to its specific platform/medium\n"
                . "- Maintain the core message and value of the original\n"
                . "- Adapt tone and style for each platform\n"
                . "- Include all requested formats\n"
                . "\nRespond with ONLY the JSON object.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            $parsed = $this->parseJsonResponse($result['data'][0] ?? '{}');

            $output = [
                'outputs' => $parsed,
                'format_count' => count($parsed),
                'formats_requested' => $selectedFormats,
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
