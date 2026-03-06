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
    public function generateScript(string $prompt, int $targetDuration = 60, int $maxWords = 700, bool $rawPrompt = false): array
    {
        $engine = get_option('story_mode_ai_engine', 'gemini');
        $model = get_option('story_mode_ai_model', 'gemini-2.5-flash');

        // Calculate target word count based on duration (~140 WPM for narration)
        $wordsPerMinute = 140;
        $targetWords = min(
            (int) round(($targetDuration / 60) * $wordsPerMinute),
            $maxWords
        );

        if ($rawPrompt) {
            // Raw mode: use the prompt as-is (caller provides full JSON format instructions)
            $compiledPrompt = $prompt;
        } else {
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
        }

        $systemMessage = "You are a professional video narration scriptwriter. You write engaging, concise narration scripts for short-form video content. Your scripts are vivid, conversational, and designed to pair with visual imagery. Always respond with ONLY the JSON output, no markdown formatting.";

        Log::info('StoryModeScriptService: Generating script', [
            'engine' => $engine,
            'model' => $model,
            'target_duration' => $targetDuration,
            'target_words' => $targetWords,
        ]);

        // Always allow retries — Gemini often truncates JSON on first attempt
        $maxAttempts = $rawPrompt ? 2 : 2;
        $wordCount = 0;
        $lastParsed = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Attempt 1: JSON format. Attempt 2: plain text (or re-try JSON for rawPrompt)
                if ($attempt === 1) {
                    $fullPrompt = "{$systemMessage}\n\n{$compiledPrompt}";
                } elseif ($rawPrompt) {
                    // Raw prompt mode (creative): retry the same prompt with more tokens
                    $fullPrompt = "{$systemMessage}\n\n{$compiledPrompt}\n\nIMPORTANT: Your previous response was truncated. You MUST output the COMPLETE JSON with ALL fields including the full transcript. Do not stop mid-sentence.";
                } else {
                    $plainTextPrompt = $this->buildPlainTextRetryPrompt($prompt, $targetDuration, $targetWords, $maxWords);
                    $plainTextSystem = "You are a professional video narration scriptwriter. Write engaging, vivid narration scripts for video content. Output ONLY the narration text — no JSON, no formatting, no headers, no markdown.";
                    $fullPrompt = "{$plainTextSystem}\n\n{$plainTextPrompt}";
                }

                $response = AI::processWithOverride(
                    $fullPrompt,
                    $engine,
                    $model,
                    'text',
                    [
                        'temperature' => 0.7,
                        'max_tokens' => $rawPrompt ? max(8000, (int) ($maxWords * 10)) : max(4000, (int) ($maxWords * 6)),
                    ],
                    auth()->user()?->team_id ?? 0
                );

                if (!empty($response['error'])) {
                    throw new \Exception('AI error: ' . $response['error']);
                }

                $content = $response['data'][0] ?? '';

                if ($attempt === 1 || $rawPrompt) {
                    // Parse the JSON response (rawPrompt retries also use JSON format)
                    $parsed = $this->parseScriptResponse($content);
                } else {
                    // Plain text retry — use raw content as transcript
                    $plainText = trim($content);
                    // Strip any accidental markdown/JSON wrapping
                    $plainText = preg_replace('/^```[\w]*\s*/s', '', $plainText);
                    $plainText = preg_replace('/\s*```$/s', '', $plainText);
                    // Remove any "Title:" or "Narration:" headers the AI might add
                    $plainText = preg_replace('/^(Title|Narration|Script|Video Script|Narration Script)\s*:\s*/im', '', $plainText);
                    $plainText = trim($plainText);

                    Log::info('StoryModeScriptService: Plain text retry response', [
                        'content_length' => strlen($plainText),
                        'word_count' => str_word_count($plainText),
                    ]);

                    $parsed = [
                        'title' => $lastParsed['title'] ?? 'Untitled Story',
                        'transcript' => $plainText,
                        'segments' => [],
                        'concept_title' => $lastParsed['concept_title'] ?? null,
                        'concept_pitch' => $lastParsed['concept_pitch'] ?? null,
                    ];
                }

                if (empty($parsed['transcript'])) {
                    throw new \Exception('AI returned empty transcript');
                }

                $wordCount = str_word_count($parsed['transcript']);

                // If output is too short for the target, retry with plain text
                // Also retry if transcript ends mid-sentence (no ending punctuation)
                $endsAbruptly = !preg_match('/[.!?…\]][\s"\']*$/', trim($parsed['transcript']));
                if ($attempt < $maxAttempts && ($wordCount < $targetWords * 0.85 || $endsAbruptly)) {
                    Log::warning('StoryModeScriptService: Script too short or truncated, retrying', [
                        'attempt' => $attempt,
                        'word_count' => $wordCount,
                        'target_words' => $targetWords,
                        'ends_abruptly' => $endsAbruptly,
                    ]);
                    $lastParsed = $parsed;
                    continue;
                }

                // Segment the transcript into scenes
                $segments = $parsed['segments'] ?? $this->segmentTranscript($parsed['transcript'], $targetDuration);

                Log::info('StoryModeScriptService: Script generated', [
                    'word_count' => $wordCount,
                    'segments' => count($segments),
                    'attempt' => $attempt,
                    'format' => ($attempt === 1 || $rawPrompt) ? 'json' : 'plain_text',
                ]);

                return [
                    'transcript' => $parsed['transcript'],
                    'segments' => $segments,
                    'word_count' => $wordCount,
                    'title' => $parsed['title'] ?? 'Untitled Story',
                    'concept_title' => $parsed['concept_title'] ?? null,
                    'concept_pitch' => $parsed['concept_pitch'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::error('StoryModeScriptService: Script generation failed', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            }
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
    public function buildVisualScript(array $segments, string $styleInstruction = '', string $aspectRatio = '9:16', ?int $teamId = null): array
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
For each narration segment, output detailed creative direction. EVERY field is MANDATORY — do NOT skip any field:

1. **image_prompt**: Detailed image generation prompt (1-3 sentences) with subject, setting, lighting, mood, composition, camera perspective. CRITICAL: All images will be generated in {$aspectRatioLabel} format — compose every scene accordingly. For portrait (9:16): use vertical compositions, tall framing, subjects centered vertically, close-up or medium shots, avoid wide panoramas. For landscape (16:9): use wide horizontal compositions, cinematic widescreen framing. For square (1:1): use centered balanced compositions.{$styleContext} Never describe text, subtitles, captions, title cards, or written words — the image must be purely visual with zero text elements.
2. **video_action** (MANDATORY — DO NOT SKIP): A RICH scene description for Seedance 2.0 AI video generation (2-4 sentences of flowing prose). This is the MOST IMPORTANT field — video quality depends entirely on this text.

   Structure as natural flowing narrative following Seedance 2.0 format (Subject → Action → Camera → Style):
   - SENTENCE 1: Open with the SETTING — where is this? What does the environment look like?
     Include atmospheric details: particles, light behavior, reflections, textures.
   - SENTENCE 2-3: Describe the PRIMARY ACTION with explicit physical motion.
     Specify body parts and directions: "extends right hand", "turns head to look over left shoulder".
     Include interaction effects: what happens to objects touched, light changes, particle reactions.
   - SENTENCE 4 (optional): Environmental response — how the setting reacts to the action.
     Dust shifts, light changes, reflections move, fabric billows, etc.

   CRITICAL RULES:
   - Use active present-tense verbs ONLY. BANNED: "goes", "moves", "starts", "begins", "is"
   - Describe EXPLICIT motion — Seedance cannot infer movement from static descriptions
   - Include physical details: "fingers trail along dusty spines" NOT "touches books"
   - Include atmospheric texture: dust motes, light shafts, reflections, smoke, particles
   - Do NOT describe clothing/appearance (the source image defines this)
   - Do NOT describe facial expressions (video model cannot control these)
   - Do NOT include camera directions (camera_motion field handles this separately)
   - Do NOT include style/aesthetic directions (applied separately during prompt assembly)

   GOOD: "Inside a vast server room bathed in cool blue-purple neon, intricate data streams flow as visible threads of light, converging from multiple terminals onto a central glowing AI core. The core pulses steadily brighter as each data thread arrives, casting shifting geometric shadows across the glass floor panels."
   BAD: "The data flows to the AI core" — too vague, no setting, no atmosphere

3. **characters_in_scene**: Array of character IDs (from the bible) that appear in this scene
4. **camera_motion**: Select from this list based on scene content:
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
5. **mood**: Pick ONE from: calm, dramatic, energetic, tense, mysterious, epic, playful, nostalgic, professional, horror, intimate, hopeful
6. **voice_emotion**: Pick ONE from: neutral, dramatic, funny, excited, calm, mysterious, sad, confident, urgent, contemplative, storytelling, whisper
7. **transition_type**: FFmpeg xfade transition TO THE NEXT scene. Pick based on mood:
   - Calm/nostalgic: "fade", "dissolve", "smoothleft", "smoothright", "fadewhite"
   - Dramatic/epic: "fadeblack", "radial", "zoomin", "circleclose"
   - Energetic/playful: "wipeleft", "wiperight", "coverleft", "pixelize", "squeezeh"
   - Tense/horror: "fadeblack", "hblur", "distance", "circleclose"
   - Mysterious: "dissolve", "distance", "fadegrays", "hblur"
   - Professional: "fade", "wipeleft", "dissolve", "smoothleft"
   - For the LAST scene: use "fade" (will be the fade-to-black)
8. **transition_duration**: Duration in seconds (0.3 for energetic, 0.5 for normal, 0.8 for calm, 1.0 for mysterious/dramatic)

IMPORTANT RULES:
- Never use the same transition_type for more than 2 consecutive scenes — variety is key
- Never use the same camera_motion for consecutive scenes — alternate between zoom/pan/tilt
- Match voice_emotion to the scene content, NOT to a single global mood
- The first scene should grab attention (energetic camera, confident voice)
- The last scene should feel conclusive (slow camera, calm/storytelling voice)
- If there are no recurring characters (e.g. landscapes, abstract content), return an empty character_bible array

NARRATION SEGMENTS:
{$allSegments}

Respond ONLY with a JSON object in this exact format (EVERY field is required — do NOT omit video_action):
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
      "image_prompt": "Detailed image generation prompt with subject, setting, lighting...",
      "video_action": "Inside a dimly lit library corridor, dust motes drift through amber shafts of light filtering through tall stained-glass windows. A solitary figure trails fingertips along rows of leather-bound spines, each touch releasing tiny spirals of golden dust that swirl upward. The ancient shelves creak softly as the figure pauses, pulling one weathered tome halfway out, its pages catching the warm light.",
      "characters_in_scene": ["short_snake_case_id"],
      "camera_motion": "slow zoom in",
      "mood": "mysterious",
      "voice_emotion": "contemplative",
      "transition_type": "dissolve",
      "transition_duration": 0.5
    }
  ]
}

Output ONLY valid JSON, no markdown.
PROMPT;

        try {
            $fullPrompt = "You are a cinematic director. Respond only with valid JSON.\n\n{$prompt}";

            $effectiveTeamId = $teamId ?? auth()->user()?->team_id ?? 0;

            $response = AI::processWithOverride(
                $fullPrompt,
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.6,
                    'max_tokens' => 8000,
                ],
                $effectiveTeamId
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

            // Log field presence for debugging
            $fieldsPresent = [];
            foreach ($visualScript as $idx => $vs) {
                $fieldsPresent[$idx] = [
                    'has_image_prompt' => !empty($vs['image_prompt']),
                    'has_video_action' => !empty($vs['video_action']),
                    'video_action_length' => strlen($vs['video_action'] ?? ''),
                ];
            }
            Log::info('StoryModeScriptService: AI response field analysis', [
                'total_scenes' => count($visualScript),
                'fields' => $fieldsPresent,
            ]);

            // Merge visual script data back into segments
            $result = [];
            foreach ($segments as $i => $segment) {
                $visual = $visualScript[$i] ?? [];
                $fallbackText = $this->stripDialogueFromText($segment['text']);
                $imagePrompt = $visual['image_prompt'] ?? "A visual scene depicting: {$fallbackText}. No text or subtitles.";
                $charsInScene = $visual['characters_in_scene'] ?? [];

                // Inject character descriptions from bible into the image prompt
                $imagePrompt = $this->injectCharacterDescriptions($imagePrompt, $charsInScene, $charLookup);

                // Append aspect ratio framing
                $imagePrompt .= "\n\n" . $aspectFraming;

                // Fallback: generate video_action from narration if AI omitted it
                $videoAction = $visual['video_action'] ?? '';
                if (empty(trim($videoAction))) {
                    $videoAction = $this->generateFallbackVideoAction($segment['text'], $visual['image_prompt'] ?? '', $visual['mood'] ?? 'professional');
                    Log::info("StoryModeScriptService: Generated fallback video_action for scene {$i}");
                }

                $result[] = array_merge($segment, [
                    'image_prompt' => $imagePrompt,
                    'video_action' => $videoAction,
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
                $fallbackText = $this->stripDialogueFromText($segment['text']);
                $imagePrompt = "{$stylePrefix}A cinematic scene depicting: {$fallbackText}. No text or subtitles.";
                return array_merge($segment, [
                    'image_prompt' => "{$imagePrompt}\n\n{$aspectFraming}",
                    'video_action' => $this->generateFallbackVideoAction($segment['text'], $imagePrompt, 'professional'),
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
     * Generate a fallback Seedance-compliant video_action from narration and image_prompt.
     * Used when the AI omits video_action from its response.
     */
    protected function generateFallbackVideoAction(string $narration, string $imagePrompt, string $mood): string
    {
        // Extract setting cues from image_prompt (first sentence usually describes the setting)
        $settingSentence = '';
        if (!empty($imagePrompt)) {
            $sentences = preg_split('/(?<=[.!?])\s+/', trim($imagePrompt), 3);
            $settingSentence = $sentences[0] ?? '';
        }

        // Build atmospheric details based on mood
        $atmosphere = match($mood) {
            'calm', 'nostalgic' => 'Soft golden light filters through the scene, dust motes drifting lazily in the warm ambient glow.',
            'dramatic', 'epic' => 'Dramatic shafts of light cut through the scene, shadows shifting with each movement as the atmosphere pulses with energy.',
            'mysterious' => 'Dim ethereal light bathes the scene in blue-silver tones, faint particles swirling in the still air.',
            'tense', 'horror' => 'Harsh contrasting light carves deep shadows across the scene, the air thick with suspended particles.',
            'energetic', 'playful' => 'Vibrant light dances across the scene, colors shifting dynamically as energy ripples through the environment.',
            'intimate' => 'Warm intimate lighting wraps the scene in a soft glow, every detail rendered with delicate clarity.',
            'hopeful' => 'Bright ascending light fills the scene from below, particles rising gently upward through the luminous air.',
            default => 'Cinematic light sweeps across the scene, atmospheric particles drifting gently through the ambient glow.',
        };

        // Transform narration into active Seedance-style action
        $narrationClean = trim($narration);
        // Truncate very long narration
        if (strlen($narrationClean) > 200) {
            $narrationClean = substr($narrationClean, 0, 197) . '...';
        }

        // Combine setting + atmosphere + narration-derived action
        if (!empty($settingSentence)) {
            return "{$settingSentence} {$atmosphere} {$narrationClean}";
        }

        return "{$atmosphere} {$narrationClean}";
    }

    /**
     * Build aspect ratio framing instruction for image prompts.
     */
    protected function buildAspectRatioFraming(string $aspectRatio, string $aspectRatioLabel): string
    {
        return match($aspectRatio) {
            '9:16' => "MANDATORY: Compose in {$aspectRatioLabel} format. Use TALL vertical composition — subjects must fill the frame vertically. Use close-up or medium shots, NOT wide shots. Frame subjects centered with headroom at top. Never compose as if this were a landscape or square image.",
            '1:1' => "MANDATORY: Compose in {$aspectRatioLabel} format. Center subjects with balanced framing in a square composition.",
            default => "MANDATORY: Compose in {$aspectRatioLabel} format. Use wide horizontal cinematic composition with subjects spread across the frame.",
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
        $minWords = (int) round($targetWords * 0.9);
        return <<<PROMPT
Write a narration script for a {$targetDuration}-second video about the following topic:

"{$prompt}"

REQUIREMENTS:
- WORD COUNT IS MANDATORY: Write between {$minWords} and {$maxWords} words. The video is {$targetDuration} seconds long at 140 words per minute. Count your words. If you write fewer than {$minWords} words, the video will have dead silence and black screen. This is the #1 priority.
- The transcript MUST be a COMPLETE text that ends with a proper closing sentence. Never stop mid-sentence or mid-thought.
- Write in a conversational, engaging narration style (NOT dialogue)
- Structure with natural break points every 6-10 seconds of spoken content
- Start with a hook that grabs attention in the first sentence
- End with a memorable closing thought or call-to-action
- Each segment should paint a vivid visual scene

Respond ONLY with a JSON object containing:
{
  "title": "Short catchy title for the video",
  "transcript": "The complete narration text as one continuous string — must be {$minWords}-{$maxWords} words",
  "segments": [
    {
      "text": "The narration text for this segment",
      "estimated_duration": 6
    }
  ]
}

Output ONLY valid JSON, no markdown formatting. Do NOT truncate the transcript.
PROMPT;
    }

    /**
     * Build a plain-text retry prompt (no JSON) for when the first attempt returns too few words.
     * Gemini 2.5 Flash often truncates JSON responses; plain text avoids that issue.
     */
    protected function buildPlainTextRetryPrompt(string $prompt, int $targetDuration, int $targetWords, int $maxWords): string
    {
        $minWords = (int) round($targetWords * 0.9);
        return <<<PROMPT
Write a COMPLETE narration script for a {$targetDuration}-second video about:

"{$prompt}"

CRITICAL REQUIREMENTS:
- You MUST write between {$minWords} and {$maxWords} words. This is NON-NEGOTIABLE. Count your words carefully.
- This is a {$targetDuration}-second video at 140 words per minute. Every missing word = dead silence in the video.
- The script MUST end with a proper closing sentence. NEVER stop mid-sentence.
- Write in a conversational, engaging narration style
- Start with an attention-grabbing hook
- End with a memorable closing thought
- Each paragraph should paint a vivid visual scene
- Write ONLY the narration text — no titles, no labels, no formatting, no JSON, no markdown
- Previous attempt was too short. You MUST write the full {$minWords}+ words this time.
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
                'concept_title' => $json['concept_title'] ?? null,
                'concept_pitch' => $json['concept_pitch'] ?? null,
            ];
        }

        // If JSON parsing fails, try to extract transcript via regex
        // Pre-sanitize: collapse control chars so regex can match across newlines in values
        $sanitized = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $content) ?? $content;
        if (preg_match('/"transcript"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $m)) {
            $transcript = stripcslashes($m[1]);
            $title = 'Untitled Story';
            if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $tm)) {
                $title = stripcslashes($tm[1]);
            }
            $conceptTitle = null;
            $conceptPitch = null;
            if (preg_match('/"concept_title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $cm)) {
                $conceptTitle = stripcslashes($cm[1]);
            }
            if (preg_match('/"concept_pitch"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sanitized, $cp)) {
                $conceptPitch = stripcslashes($cp[1]);
            }
            return [
                'title' => $title,
                'transcript' => $transcript,
                'segments' => [],
                'concept_title' => $conceptTitle,
                'concept_pitch' => $conceptPitch,
            ];
        }

        // Aggressive manual extraction: find "transcript" key and extract value char-by-char
        $transcript = $this->manualExtractJsonStringValue($sanitized, 'transcript');
        if ($transcript) {
            $title = $this->manualExtractJsonStringValue($sanitized, 'title') ?: 'Untitled Story';
            $conceptTitle = $this->manualExtractJsonStringValue($sanitized, 'concept_title');
            $conceptPitch = $this->manualExtractJsonStringValue($sanitized, 'concept_pitch');
            Log::info('StoryModeScriptService: Transcript extracted via manual char-by-char parsing');
            return [
                'title' => $title,
                'transcript' => $transcript,
                'segments' => [],
                'concept_title' => $conceptTitle,
                'concept_pitch' => $conceptPitch,
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

        // Scale per-scene duration and max segments based on total video length
        $targetSegmentDuration = min(12, max(6.5, $targetDuration / 25));
        $maxSegments = min(30, max(6, (int) round($targetDuration / 10)));
        $targetSegmentCount = max(3, min($maxSegments, (int) round($totalEstDuration / $targetSegmentDuration)));

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
     * Segment a screenplay into time-based scenes.
     * Splits at [Scene: ...] markers, preserving dialogue speaker markers and direction text.
     *
     * @param string $transcript Full screenplay text with [Scene: ...] markers
     * @param int $targetDuration Total video duration in seconds
     * @return array Segments with text, direction, and estimated duration
     */
    public function segmentScreenplay(string $transcript, int $targetDuration = 120): array
    {
        // Split at [Scene: ...] markers
        $parts = preg_split('/\[Scene:\s*/i', $transcript);

        $segments = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Extract scene direction (everything up to the closing ])
            $direction = '';
            $body = $part;
            if (($closeBracket = strpos($part, ']')) !== false) {
                $direction = trim(substr($part, 0, $closeBracket));
                $body = trim(substr($part, $closeBracket + 1));
            }

            // The body contains dialogue lines (CHARACTER: "text") and possibly more directions
            // Estimate duration from dialogue word count only (directions are visual-only)
            $dialogueText = '';
            $lines = preg_split('/\r?\n/', $body);
            foreach ($lines as $line) {
                $line = trim($line);
                // Match CHARACTER: "dialogue" or CHARACTER: dialogue
                if (preg_match('/^[A-Z][A-Z0-9_\s]+:\s*(.+)$/u', $line, $m)) {
                    $dialogueText .= ' ' . trim($m[1], '" ');
                }
            }

            $dialogueWords = str_word_count(trim($dialogueText));
            $wordsPerMinute = 140;
            $segmentDuration = $dialogueWords > 0
                ? round(($dialogueWords / $wordsPerMinute) * 60, 1)
                : 4.0; // Visual-only scenes get a base duration

            $isVisualOnly = $dialogueWords === 0;

            $segments[] = [
                'text' => $body ?: "[Scene: {$direction}]",
                'direction' => $direction,
                'estimated_duration' => max(3, $segmentDuration),
                'is_visual_only' => $isVisualOnly,
            ];
        }

        // If no [Scene:] markers found, fall back to regular sentence segmentation
        if (empty($segments)) {
            return $this->segmentTranscript($transcript, $targetDuration);
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

        // Try smart sanitizer first — properly escapes newlines/tabs inside string values
        // (preserves line breaks in transcripts unlike aggressive removal)
        $sanitized = $this->sanitizeJsonControlChars($content);
        $decoded = json_decode($sanitized, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('StoryModeScriptService: JSON parsed via smart sanitizer');
            return $decoded;
        }

        // Fallback: aggressive control char removal — collapse ALL control chars to spaces.
        $aggressive = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $content) ?? $content;
        // Ensure valid UTF-8 (replace invalid sequences)
        $aggressive = mb_convert_encoding($aggressive, 'UTF-8', 'UTF-8');
        $decoded = json_decode($aggressive, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('StoryModeScriptService: JSON parsed via aggressive control char removal');
            return $decoded;
        }
        Log::debug('StoryModeScriptService: Aggressive removal still failed', [
            'error' => json_last_error_msg(),
            'error_code' => json_last_error(),
            'content_length' => strlen($aggressive),
            'preview' => substr($aggressive, 0, 300),
        ]);

        // Try double-pass: also replace non-breaking spaces, zero-width chars, etc.
        $doublePass = preg_replace('/[\x00-\x1F\x7F\xC2\x80-\xC2\x9F]+/u', ' ', $content) ?? $content;
        $doublePass = mb_convert_encoding($doublePass, 'UTF-8', 'UTF-8');
        $decoded = json_decode($doublePass, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('StoryModeScriptService: JSON parsed via double-pass control char removal');
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
     * Manually extract a JSON string value by key using char-by-char scanning.
     * Handles unescaped quotes, control chars, and other malformed JSON.
     */
    protected function manualExtractJsonStringValue(string $content, string $key): ?string
    {
        // Find the key position
        $keyPattern = '"' . $key . '"';
        $keyPos = strpos($content, $keyPattern);
        if ($keyPos === false) {
            return null;
        }

        // Skip past key, colon, and opening quote
        $pos = $keyPos + strlen($keyPattern);
        $len = strlen($content);

        // Skip whitespace and colon
        while ($pos < $len && ($content[$pos] === ' ' || $content[$pos] === ':')) {
            $pos++;
        }

        // Expect opening quote
        if ($pos >= $len || $content[$pos] !== '"') {
            return null;
        }
        $pos++; // skip opening quote

        // Extract value: scan until we find closing quote followed by , or } or ]
        $value = '';
        $escaped = false;
        while ($pos < $len) {
            $char = $content[$pos];

            if ($escaped) {
                // Convert JSON escape sequences to actual characters
                $value .= match($char) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    default => $char,
                };
                $escaped = false;
                $pos++;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $pos++;
                continue;
            }

            // Check if this quote is the closing one
            // Must distinguish dialogue quotes (mid-value) from JSON structural quotes
            if ($char === '"') {
                $ahead = ltrim(substr($content, $pos + 1, 40));

                // End of content
                if ($ahead === '') {
                    break;
                }

                // Closing quote followed by } or ]
                if ($ahead[0] === '}' || $ahead[0] === ']') {
                    break;
                }

                // Closing quote followed by , — only break if what follows the comma
                // looks like a JSON key (e.g., , "concept_title": ) not regular text
                if ($ahead[0] === ',') {
                    if (preg_match('/^,\s*"[a-z_]+"/', $ahead)) {
                        break; // real JSON field boundary
                    }
                    // Otherwise it's dialogue like "hello", she said — keep going
                    $value .= $char;
                    $pos++;
                    continue;
                }

                // Unescaped quote mid-value — include it and keep going
                $value .= $char;
                $pos++;
                continue;
            }

            $value .= $char;
            $pos++;
        }

        $value = trim($value);
        return !empty($value) ? $value : null;
    }

    /**
     * Sanitize control characters that break json_decode.
     * Inside JSON string values: escapes newlines/tabs as \\n/\\t (preserving content),
     * replaces other control chars with spaces.
     * Outside strings: preserves structural whitespace.
     */
    protected function sanitizeJsonControlChars(string $json): string
    {
        // Replace literal \r\n and \r with \n first
        $json = str_replace(["\r\n", "\r"], "\n", $json);

        // Walk through JSON, tracking string boundaries.
        // Handles: literal control chars → proper escapes, unescaped quotes → \"
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
                if ($inString) {
                    // Check if this is the REAL closing quote (JSON boundary)
                    // or an unescaped dialogue quote mid-value
                    $ahead = ltrim(substr($json, $i + 1, 40));
                    if ($ahead === '' || $ahead[0] === '}' || $ahead[0] === ']'
                        || ($ahead[0] === ',' && preg_match('/^,\s*"[a-z_]+"\s*:/', $ahead))
                        || ($ahead[0] === ':') // this quote opens a key
                    ) {
                        // Real JSON structural quote — close the string
                        $inString = false;
                        $result .= $char;
                    } else {
                        // Unescaped quote inside string value — escape it
                        $result .= '\\"';
                    }
                } else {
                    $inString = true;
                    $result .= $char;
                }
                continue;
            }

            // Inside a string value, properly escape control chars
            if ($inString && $ord < 0x20) {
                if ($ord === 0x0A) {
                    $result .= '\\n';  // newline → \n
                } elseif ($ord === 0x09) {
                    $result .= '\\t';  // tab → \t
                } else {
                    $result .= ' ';    // other control chars → space
                }
                continue;
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * Strip dialogue patterns from text so fallback image prompts don't contain spoken words.
     * Removes "CHARACTER: dialogue" patterns and quoted dialogue.
     */
    protected function stripDialogueFromText(string $text): string
    {
        // Remove "CHARACTER: dialogue" patterns
        $cleaned = preg_replace('/[A-Z][A-Z\s]+:\s*[^.!?\n]+[.!?\n]?/', '', $text);
        // Remove quoted dialogue
        $cleaned = preg_replace('/["\'].*?["\']/', '', $cleaned);
        // Clean up whitespace
        $cleaned = preg_replace('/\s{2,}/', ' ', trim($cleaned));
        return !empty($cleaned) ? $cleaned : $text;
    }
}
