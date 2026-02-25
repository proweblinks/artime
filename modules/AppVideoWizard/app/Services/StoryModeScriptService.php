<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;

class StoryModeScriptService
{
    protected PromptService $promptService;

    public function __construct()
    {
        $this->promptService = new PromptService();
    }

    /**
     * Generate a narration script from a user prompt.
     * Returns structured transcript with scene segments.
     *
     * @param string $prompt User's video description
     * @param int $targetDuration Target video duration in seconds
     * @param int $maxWords Maximum word count for transcript
     * @return array{transcript: string, segments: array, word_count: int}
     */
    public function generateScript(string $prompt, int $targetDuration = 35, int $maxWords = 450): array
    {
        $engine = get_option('story_mode_ai_engine', 'gemini');
        $model = get_option('story_mode_ai_model', 'gemini-2.5-flash');

        // Calculate target word count based on duration (~140 WPM for narration)
        $wordsPerMinute = 140;
        $targetWords = min(
            (int) round(($targetDuration / 60) * $wordsPerMinute),
            $maxWords
        );

        // Try DB-managed prompt first, fall back to hardcoded template
        $compiledPrompt = $this->promptService->getCompiledPrompt('story_mode_script_generate', [
            'prompt' => $prompt,
            'target_duration' => $targetDuration,
            'target_words' => $targetWords,
            'max_words' => $maxWords,
        ]);

        if (empty($compiledPrompt)) {
            $compiledPrompt = $this->buildFallbackPrompt($prompt, $targetDuration, $targetWords, $maxWords);
        }

        $systemMessage = "You are a professional video narration scriptwriter. You write engaging, concise narration scripts for short-form video content. Your scripts are vivid, conversational, and designed to pair with visual imagery. Always respond with ONLY the JSON output, no markdown formatting.";

        Log::info('StoryModeScriptService: Generating script', [
            'engine' => $engine,
            'model' => $model,
            'target_duration' => $targetDuration,
            'target_words' => $targetWords,
        ]);

        try {
            // Prepend system instruction to the prompt content
            $fullPrompt = "{$systemMessage}\n\n{$compiledPrompt}";

            $response = AI::processWithOverride(
                $fullPrompt,
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ],
                auth()->user()?->team_id ?? 0
            );

            if (!empty($response['error'])) {
                throw new \Exception('AI error: ' . $response['error']);
            }

            $content = $response['data'][0] ?? '';

            // Parse the JSON response
            $parsed = $this->parseScriptResponse($content);

            if (empty($parsed['transcript'])) {
                throw new \Exception('AI returned empty transcript');
            }

            // Segment the transcript into scenes
            $segments = $parsed['segments'] ?? $this->segmentTranscript($parsed['transcript'], $targetDuration);

            $wordCount = str_word_count($parsed['transcript']);

            Log::info('StoryModeScriptService: Script generated', [
                'word_count' => $wordCount,
                'segments' => count($segments),
            ]);

            return [
                'transcript' => $parsed['transcript'],
                'segments' => $segments,
                'word_count' => $wordCount,
                'title' => $parsed['title'] ?? 'Untitled Story',
            ];
        } catch (\Exception $e) {
            Log::error('StoryModeScriptService: Script generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build the visual script from transcript segments.
     * Converts each segment's narration into a detailed image prompt.
     *
     * @param array $segments Transcript segments
     * @param string $styleInstruction Style modifier for image generation
     * @return array Visual script with image prompts per segment
     */
    public function buildVisualScript(array $segments, string $styleInstruction = ''): array
    {
        $engine = get_option('story_mode_ai_engine', 'gemini');
        $model = get_option('story_mode_ai_model', 'gemini-2.5-flash');

        $segmentTexts = [];
        foreach ($segments as $i => $segment) {
            $segmentTexts[] = "Segment " . ($i + 1) . ": \"{$segment['text']}\"";
        }
        $allSegments = implode("\n", $segmentTexts);

        $styleContext = $styleInstruction
            ? "\n\nVISUAL STYLE TO APPLY: {$styleInstruction}\nAll image prompts must incorporate this visual style consistently."
            : '';

        $prompt = <<<PROMPT
You are a visual director creating image prompts for a narrated video.

For each narration segment below, write a detailed image generation prompt that:
1. Visually illustrates the key concept of the narration
2. Is self-contained (doesn't reference other segments)
3. Includes specific visual details: subject, setting, lighting, mood, composition
4. Includes camera perspective (wide shot, close-up, overhead, etc.)
5. Is 1-3 sentences long{$styleContext}

NARRATION SEGMENTS:
{$allSegments}

Respond ONLY with a JSON array where each element has:
- "segment_index": the segment number (1-based)
- "image_prompt": the detailed image generation prompt
- "camera_motion": suggested camera movement for video animation (e.g., "slow zoom in", "pan left", "static", "dolly forward")

Output ONLY valid JSON, no markdown.
PROMPT;

        try {
            $fullPrompt = "You are a visual director. Respond only with valid JSON arrays.\n\n{$prompt}";

            $response = AI::processWithOverride(
                $fullPrompt,
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.6,
                    'max_tokens' => 4000,
                ],
                auth()->user()?->team_id ?? 0
            );

            if (!empty($response['error'])) {
                throw new \Exception('AI error: ' . $response['error']);
            }

            $content = $response['data'][0] ?? '';
            $visualScript = $this->parseJsonResponse($content);

            if (!is_array($visualScript)) {
                throw new \Exception('Failed to parse visual script response');
            }

            // Merge visual script data back into segments
            $result = [];
            foreach ($segments as $i => $segment) {
                $visual = $visualScript[$i] ?? [];
                $result[] = array_merge($segment, [
                    'image_prompt' => $visual['image_prompt'] ?? "A visual scene depicting: {$segment['text']}",
                    'camera_motion' => $visual['camera_motion'] ?? 'slow zoom in',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('StoryModeScriptService: Visual script generation failed', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: create basic image prompts from narration
            return array_map(function ($segment) use ($styleInstruction) {
                $stylePrefix = $styleInstruction ? "{$styleInstruction}. " : '';
                return array_merge($segment, [
                    'image_prompt' => "{$stylePrefix}A cinematic scene depicting: {$segment['text']}",
                    'camera_motion' => 'slow zoom in',
                ]);
            }, $segments);
        }
    }

    /**
     * Build fallback prompt when DB prompt is not available.
     */
    protected function buildFallbackPrompt(string $prompt, int $targetDuration, int $targetWords, int $maxWords): string
    {
        return <<<PROMPT
Write a narration script for a {$targetDuration}-second video about the following topic:

"{$prompt}"

REQUIREMENTS:
- Write exactly {$targetWords} words (maximum {$maxWords} words)
- Write in a conversational, engaging narration style (NOT dialogue)
- Structure with natural break points every 5-8 seconds of spoken content
- Start with a hook that grabs attention in the first sentence
- End with a memorable closing thought or call-to-action
- Each segment should paint a vivid visual scene

Respond ONLY with a JSON object containing:
{
  "title": "Short catchy title for the video",
  "transcript": "The complete narration text as one continuous string",
  "segments": [
    {
      "text": "The narration text for this segment",
      "estimated_duration": 6
    }
  ]
}

Output ONLY valid JSON, no markdown formatting.
PROMPT;
    }

    /**
     * Parse the AI response to extract transcript and segments.
     */
    protected function parseScriptResponse(string $content): array
    {
        $json = $this->parseJsonResponse($content);

        if (is_array($json)) {
            return [
                'title' => $json['title'] ?? 'Untitled Story',
                'transcript' => $json['transcript'] ?? '',
                'segments' => $json['segments'] ?? [],
            ];
        }

        // If JSON parsing fails, try to extract transcript via regex
        if (preg_match('/"transcript"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $content, $m)) {
            $transcript = stripcslashes($m[1]);
            $title = 'Untitled Story';
            if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $content, $tm)) {
                $title = stripcslashes($tm[1]);
            }
            return [
                'title' => $title,
                'transcript' => $transcript,
                'segments' => [],
            ];
        }

        // Last resort: treat the entire response as the transcript
        return [
            'title' => 'Untitled Story',
            'transcript' => trim($content),
            'segments' => [],
        ];
    }

    /**
     * Segment a transcript into time-based scenes.
     * Splits at sentence boundaries targeting 5-8 seconds per segment.
     *
     * @param string $transcript Full narration text
     * @param int $targetDuration Total video duration in seconds
     * @return array Segments with text and estimated duration
     */
    public function segmentTranscript(string $transcript, int $targetDuration = 35): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($transcript));
        $sentences = array_filter($sentences, fn ($s) => !empty(trim($s)));
        $sentences = array_values($sentences);

        if (empty($sentences)) {
            return [['text' => $transcript, 'estimated_duration' => $targetDuration]];
        }

        $wordsPerMinute = 140;
        $totalWords = str_word_count($transcript);
        $totalEstDuration = ($totalWords / $wordsPerMinute) * 60;

        // Target 5-8 seconds per segment
        $targetSegmentDuration = 6.5;
        $targetSegmentCount = max(3, min(10, (int) round($totalEstDuration / $targetSegmentDuration)));

        $segments = [];
        $currentSegment = '';
        $sentencesPerSegment = max(1, (int) ceil(count($sentences) / $targetSegmentCount));

        foreach ($sentences as $i => $sentence) {
            $currentSegment .= ($currentSegment ? ' ' : '') . trim($sentence);

            if (($i + 1) % $sentencesPerSegment === 0 || $i === count($sentences) - 1) {
                $segmentWords = str_word_count($currentSegment);
                $segmentDuration = round(($segmentWords / $wordsPerMinute) * 60, 1);

                $segments[] = [
                    'text' => $currentSegment,
                    'estimated_duration' => max(3, $segmentDuration),
                ];
                $currentSegment = '';
            }
        }

        return $segments;
    }

    /**
     * Parse JSON from AI response (handles markdown code blocks, BOM, etc.).
     */
    protected function parseJsonResponse(string $content): ?array
    {
        $content = trim($content);

        // Remove UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Remove markdown code blocks if present (various formats)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        // Try direct decode
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try extracting JSON object from mixed content
        if (preg_match('/\{[\s\S]*"transcript"[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting JSON array from mixed content
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('StoryModeScriptService: JSON parsing failed', [
            'error' => json_last_error_msg(),
            'content_preview' => substr($content, 0, 200),
        ]);

        return null;
    }
}
