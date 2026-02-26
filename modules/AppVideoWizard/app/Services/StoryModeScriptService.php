<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;

class StoryModeScriptService
{
    protected PromptService $promptService;

    /**
     * Character Bible extracted from the last buildVisualScript() call.
     * Each entry: { id, name, description, appears_in }
     */
    public array $lastCharacterBible = [];

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
    public function generateScript(string $prompt, int $targetDuration = 60, int $maxWords = 700): array
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
     * Also produces a character_bible for visual consistency across scenes.
     *
     * @param array $segments Transcript segments
     * @param string $styleInstruction Style modifier for image generation
     * @param string $aspectRatio Target aspect ratio (e.g. '9:16', '16:9', '1:1')
     * @return array Visual script with image prompts per segment
     */
    public function buildVisualScript(array $segments, string $styleInstruction = '', string $aspectRatio = '9:16'): array
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

        $aspectRatioLabel = match($aspectRatio) {
            '9:16' => 'portrait 9:16 vertical',
            '1:1' => 'square 1:1',
            default => 'landscape 16:9 widescreen',
        };

        $prompt = <<<PROMPT
You are a cinematic director creating a visual and emotional script for a narrated video.

Your task has TWO phases:

## PHASE 1 — CHARACTER BIBLE
Read ALL narration segments and identify every recurring character or subject (people, animals, mascots — anything that appears in more than one scene, or is the main subject).
For each, provide a detailed visual identity sheet so the character looks IDENTICAL across all generated images.

## PHASE 2 — PER-SCENE DIRECTION
For each narration segment, output detailed creative direction:

1. **image_prompt**: Detailed image generation prompt (1-3 sentences) with subject, setting, lighting, mood, composition, camera perspective{$styleContext}
2. **characters_in_scene**: Array of character IDs (from the bible) that appear in this scene
3. **camera_motion**: Select from this list based on scene content:
   - "slow zoom in" — builds intimacy/focus (emotional moments, portraits, detail)
   - "slow zoom out" — reveals context/scale (establishing shots, conclusions, landscapes)
   - "dramatic zoom in" — intense focus (climax, shock, tension)
   - "pan left" / "pan right" — scanning/progression (landscapes, journeys, timelines)
   - "pan left slow" / "pan right slow" — gentle drift (calm, contemplative scenes)
   - "tilt up" — aspiration, height (tall subjects, sky, looking up)
   - "tilt down" — grounding, detail (settling, water, close inspection)
   - "zoom in pan right" — dynamic tracking (following subject, discovery)
   - "zoom out pan left" — revealing context (pulling away, showing bigger picture)
   - "diagonal drift" — floating feeling (ambient, dreamy, contemplative)
   - "push to subject" — emotional closeup (character focus, portraits)
   - "rise and reveal" — epic opening (establishing shots, landscapes, reveals)
   - "settle in" — subtle settling (minimal motion, text-friendly)
   - "breathe" — very subtle zoom keeping image alive (default/safe choice)
4. **mood**: The emotional tone of this scene. Pick ONE from: calm, dramatic, energetic, tense, mysterious, epic, playful, nostalgic, professional, horror, intimate, hopeful
5. **voice_emotion**: How the narrator should deliver this scene. Pick ONE from: neutral, dramatic, funny, excited, calm, mysterious, sad, confident, urgent, contemplative, storytelling, whisper
6. **transition_type**: FFmpeg xfade transition TO THE NEXT scene. Pick based on mood:
   - Calm/nostalgic: "fade", "dissolve", "smoothleft", "smoothright", "fadewhite"
   - Dramatic/epic: "fadeblack", "radial", "zoomin", "circleclose"
   - Energetic/playful: "wipeleft", "wiperight", "coverleft", "pixelize", "squeezeh"
   - Tense/horror: "fadeblack", "hblur", "distance", "circleclose"
   - Mysterious: "dissolve", "distance", "fadegrays", "hblur"
   - Professional: "fade", "wipeleft", "dissolve", "smoothleft"
   - For the LAST scene: use "fade" (will be the fade-to-black)
7. **transition_duration**: Duration in seconds (0.3 for energetic, 0.5 for normal, 0.8 for calm, 1.0 for mysterious/dramatic)
8. **video_action**: A single present-tense action sentence describing what the subject DOES in this scene.
   This is for VIDEO generation — describe physical movement, not a static image.
   Rules:
   - Use active verbs: "walks", "turns", "reaches", "opens", "runs"
   - Be specific about body parts and directions: "reaches forward with right hand", "turns head to look over shoulder"
   - One clear action per scene — do NOT combine multiple actions
   - Match the narration content — the video should visually depict what the narrator says
   - Do NOT describe appearance or clothing (the source image defines this)
   - Do NOT describe facial expressions (video model cannot control these)
   Examples:
   - "walks slowly through the dark corridor, trailing fingers along dusty bookshelves"
   - "picks up the antique bookmark and holds it toward the light"
   - "steps backward as a spectral form materializes between the stacks"
   - "opens the old tome and turns its yellowed pages"
   - For scenes without characters: "dust motes drift through shafts of warm light" or "pages flutter as a cold wind sweeps through"

IMPORTANT RULES:
- Never use the same transition_type for more than 2 consecutive scenes — variety is key
- Never use the same camera_motion for consecutive scenes — alternate between zoom/pan/tilt
- Match voice_emotion to the scene content, NOT to a single global mood
- The first scene should grab attention (energetic camera, confident voice)
- The last scene should feel conclusive (slow camera, calm/storytelling voice)
- If there are no recurring characters (e.g. landscapes, abstract content), return an empty character_bible array

NARRATION SEGMENTS:
{$allSegments}

Respond ONLY with a JSON object in this exact format:
{
  "character_bible": [
    {
      "id": "short_snake_case_id",
      "name": "Character Display Name",
      "description": "Detailed visual description: age, gender, build, height, skin tone, hair color/style/length, eye color, facial features, clothing, accessories, distinguishing marks",
      "appears_in": [1, 3, 5]
    }
  ],
  "scenes": [
    {
      "segment_index": 1,
      "image_prompt": "...",
      "video_action": "walks slowly through the dark library corridor, trailing fingers along dusty spines",
      "characters_in_scene": ["short_snake_case_id"],
      "camera_motion": "...",
      "mood": "...",
      "voice_emotion": "...",
      "transition_type": "...",
      "transition_duration": 0.5
    }
  ]
}

Output ONLY valid JSON, no markdown.
PROMPT;

        try {
            $fullPrompt = "You are a cinematic director. Respond only with valid JSON.\n\n{$prompt}";

            $response = AI::processWithOverride(
                $fullPrompt,
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.6,
                    'max_tokens' => 6000,
                ],
                auth()->user()?->team_id ?? 0
            );

            if (!empty($response['error'])) {
                throw new \Exception('AI error: ' . $response['error']);
            }

            $content = $response['data'][0] ?? '';
            $parsed = $this->parseJsonResponse($content);

            if (!is_array($parsed)) {
                throw new \Exception('Failed to parse visual script response');
            }

            // Handle new format (object with character_bible + scenes) vs legacy (flat array)
            $characterBible = [];
            $visualScript = [];

            if (isset($parsed['character_bible']) && isset($parsed['scenes'])) {
                // New format
                $characterBible = $parsed['character_bible'] ?? [];
                $visualScript = $parsed['scenes'] ?? [];

                Log::info('StoryModeScriptService: Character bible extracted', [
                    'characters' => count($characterBible),
                    'names' => array_column($characterBible, 'name'),
                ]);
            } else {
                // Legacy flat array format — no character bible
                $visualScript = $parsed;

                Log::info('StoryModeScriptService: Legacy flat array format (no character bible)');
            }

            $this->lastCharacterBible = $characterBible;

            // Build a lookup: charId => description from the bible
            $charLookup = [];
            foreach ($characterBible as $char) {
                $charLookup[$char['id']] = $char;
            }

            // Aspect ratio framing instruction
            $aspectFraming = $this->buildAspectRatioFraming($aspectRatio, $aspectRatioLabel);

            // Merge visual script data back into segments
            $result = [];
            foreach ($segments as $i => $segment) {
                $visual = $visualScript[$i] ?? [];
                $imagePrompt = $visual['image_prompt'] ?? "A visual scene depicting: {$segment['text']}";
                $charsInScene = $visual['characters_in_scene'] ?? [];

                // Inject character descriptions from bible into the image prompt
                $imagePrompt = $this->injectCharacterDescriptions($imagePrompt, $charsInScene, $charLookup);

                // Append aspect ratio framing
                $imagePrompt .= "\n\n" . $aspectFraming;

                $result[] = array_merge($segment, [
                    'image_prompt' => $imagePrompt,
                    'video_action' => $visual['video_action'] ?? '',
                    'characters_in_scene' => $charsInScene,
                    'camera_motion' => $visual['camera_motion'] ?? 'slow zoom in',
                    'mood' => $visual['mood'] ?? 'professional',
                    'voice_emotion' => $visual['voice_emotion'] ?? 'neutral',
                    'transition_type' => $visual['transition_type'] ?? 'fade',
                    'transition_duration' => (float) ($visual['transition_duration'] ?? 0.5),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('StoryModeScriptService: Visual script generation failed', [
                'error' => $e->getMessage(),
            ]);

            $this->lastCharacterBible = [];
            $aspectFraming = $this->buildAspectRatioFraming($aspectRatio, $aspectRatioLabel ?? 'portrait 9:16 vertical');

            // Fallback: create basic image prompts from narration with default creative metadata
            return array_map(function ($segment) use ($styleInstruction, $aspectFraming) {
                $stylePrefix = $styleInstruction ? "{$styleInstruction}. " : '';
                return array_merge($segment, [
                    'image_prompt' => "{$stylePrefix}A cinematic scene depicting: {$segment['text']}\n\n{$aspectFraming}",
                    'video_action' => '',
                    'characters_in_scene' => [],
                    'camera_motion' => 'slow zoom in',
                    'mood' => 'professional',
                    'voice_emotion' => 'neutral',
                    'transition_type' => 'fade',
                    'transition_duration' => 0.5,
                ]);
            }, $segments);
        }
    }

    /**
     * Build aspect ratio framing instruction for image prompts.
     */
    protected function buildAspectRatioFraming(string $aspectRatio, string $aspectRatioLabel): string
    {
        return match($aspectRatio) {
            '9:16' => "Compose in {$aspectRatioLabel} format. Frame subjects vertically with headroom at top.",
            '1:1' => "Compose in {$aspectRatioLabel} format. Center subjects with balanced framing.",
            default => "Compose in {$aspectRatioLabel} format. Use wide horizontal framing with cinematic composition.",
        };
    }

    /**
     * Inject character visual descriptions from the Bible into an image prompt.
     */
    protected function injectCharacterDescriptions(string $imagePrompt, array $characterIds, array $charLookup): string
    {
        if (empty($characterIds) || empty($charLookup)) {
            return $imagePrompt;
        }

        $descriptions = [];
        foreach ($characterIds as $charId) {
            if (isset($charLookup[$charId])) {
                $char = $charLookup[$charId];
                $descriptions[] = "{$char['name']}: {$char['description']}";
            }
        }

        if (empty($descriptions)) {
            return $imagePrompt;
        }

        return $imagePrompt . "\n\nCHARACTER VISUAL IDENTITY (maintain exact appearance):\n" . implode("\n", $descriptions);
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
- Structure with natural break points every 6-10 seconds of spoken content
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
        // Pre-sanitize: collapse control chars so regex can match across newlines in values
        $sanitized = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $content);
        if (preg_match('/"transcript"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $m)) {
            $transcript = stripcslashes($m[1]);
            $title = 'Untitled Story';
            if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $tm)) {
                $title = stripcslashes($tm[1]);
            }
            return [
                'title' => $title,
                'transcript' => $transcript,
                'segments' => [],
            ];
        }

        // Last resort: if the content looks like JSON, refuse to use it as transcript
        // (prevents narrator from reading raw JSON aloud)
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            Log::error('StoryModeScriptService: Raw JSON would have been used as transcript — rejecting', [
                'content_preview' => substr($trimmed, 0, 200),
            ]);
            throw new \Exception('Script generation returned unparseable JSON — cannot extract transcript');
        }

        // Only use raw content as transcript if it's actual plain text
        return [
            'title' => 'Untitled Story',
            'transcript' => $trimmed,
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

        // Always try aggressive control char removal — collapse ALL control chars to spaces.
        // AI responses often contain literal newlines/tabs inside JSON string values.
        // This turns pretty-printed JSON into a single line, which is still valid JSON.
        $aggressive = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $content);
        $decoded = json_decode($aggressive, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('StoryModeScriptService: JSON parsed via aggressive control char removal');
            return $decoded;
        }

        // Try smart sanitizer (replaces control chars only inside string values)
        $sanitized = $this->sanitizeJsonControlChars($content);
        $decoded = json_decode($sanitized, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try extracting JSON object with character_bible (visual script format)
        if (preg_match('/\{[\s\S]*"character_bible"[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $matches[0]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting JSON object from mixed content
        if (preg_match('/\{[\s\S]*"transcript"[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $matches[0]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting JSON array from mixed content
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $decoded = json_decode(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $matches[0]), true);
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

    /**
     * Sanitize control characters that break json_decode.
     * Replaces literal newlines/tabs/etc. inside JSON string values with spaces,
     * while preserving structural whitespace between JSON keys.
     */
    protected function sanitizeJsonControlChars(string $json): string
    {
        // Replace literal \r\n and \r with \n first
        $json = str_replace(["\r\n", "\r"], "\n", $json);

        // Replace control characters inside string values:
        // Walk through the string, tracking whether we're inside a JSON string
        $result = '';
        $inString = false;
        $escaped = false;
        $len = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $char = $json[$i];
            $ord = ord($char);

            if ($escaped) {
                $result .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $result .= $char;
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                $result .= $char;
                continue;
            }

            // Inside a string value, replace control chars with space
            if ($inString && $ord < 0x20) {
                $result .= ' ';
                continue;
            }

            $result .= $char;
        }

        return $result;
    }
}
