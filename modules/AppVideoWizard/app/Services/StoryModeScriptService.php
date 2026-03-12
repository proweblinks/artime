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

        // Allow retries — Gemini often truncates JSON or writes too few words
        $maxAttempts = $rawPrompt ? 2 : 3;
        $wordCount = 0;
        $lastParsed = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Attempt 1: JSON format. Attempt 2+: plain text (or re-try JSON for rawPrompt)
                if ($attempt === 1) {
                    $fullPrompt = "{$systemMessage}\n\n{$compiledPrompt}";
                } elseif ($rawPrompt) {
                    // Raw prompt mode (creative): retry the same prompt with more tokens
                    $fullPrompt = "{$systemMessage}\n\n{$compiledPrompt}\n\nIMPORTANT: Your previous response was truncated. You MUST output the COMPLETE JSON with ALL fields including the full transcript. Do not stop mid-sentence.";
                } else {
                    $prevTranscript = $lastParsed['transcript'] ?? '';
                    $prevWordCount = str_word_count($prevTranscript);
                    $plainTextSystem = "You are a professional video narration scriptwriter. Write engaging, vivid narration scripts for video content. Output ONLY the narration text — no JSON, no formatting, no headers, no markdown.";

                    if ($attempt >= 3 && $prevWordCount > 0 && $prevWordCount < $targetWords * 0.85) {
                        // Attempt 3: expand the previous short script instead of generating from scratch
                        $wordsNeeded = $targetWords - $prevWordCount;
                        $plainTextPrompt = <<<PROMPT
Below is an INCOMPLETE narration script ({$prevWordCount} words) for a {$targetDuration}-second video. It needs to be at least {$targetWords} words total.

EXISTING SCRIPT:
{$prevTranscript}

YOUR TASK: Rewrite and EXPAND this into a COMPLETE {$targetWords}-word narration. Keep the same story and tone, but add much more detail, description, and new scenes. You need roughly {$wordsNeeded} more words. Add vivid sensory details, expand each moment, and ensure the story has a proper beginning, middle, and end with a satisfying conclusion.

Output ONLY the complete expanded narration text — no titles, no labels, no formatting.
PROMPT;
                    } else {
                        $plainTextPrompt = $this->buildPlainTextRetryPrompt($prompt, $targetDuration, $targetWords, $maxWords);
                    }
                    $fullPrompt = "{$plainTextSystem}\n\n{$plainTextPrompt}";
                }

                $response = AI::processWithOverride(
                    $fullPrompt,
                    $engine,
                    $model,
                    'text',
                    [
                        'temperature' => $attempt >= 3 ? 0.9 : 0.7,
                        'max_tokens' => $rawPrompt ? max(8000, (int) ($maxWords * 10)) : max(4000, (int) ($maxWords * 8)),
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
        $effectiveTeamId = $teamId ?? auth()->user()?->team_id ?? 0;

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

        // ── Phase 1: Director's Treatment ──
        $treatment = $this->phase1DirectorsTreatment(
            $allSegments, count($segments), $engine, $model, $effectiveTeamId
        );

        if ($treatment === null) {
            Log::warning('StoryModeScriptService: Phase 1 failed, falling back to legacy single-batch');
            return $this->legacySingleBatchVisualScript(
                $segments, $allSegments, $styleInstruction, $styleContext,
                $aspectRatio, $aspectRatioLabel, $engine, $model, $effectiveTeamId
            );
        }

        // Store character bible (same contract as legacy path)
        $characterBible = $treatment['character_bible'] ?? [];
        $this->lastCharacterBible = $characterBible;

        $charLookup = [];
        foreach ($characterBible as $char) {
            $charLookup[$char['id']] = $char;
        }

        // Build location lookup from Phase 1
        $locationLookup = [];
        if (!empty($treatment['location_bible'])) {
            foreach ($treatment['location_bible'] as $loc) {
                $locationLookup[$loc['id']] = $loc;
            }
        }

        // Build scene-to-location map from Phase 1 beats
        $sceneLocationMap = [];
        if (!empty($treatment['scene_beats'])) {
            foreach ($treatment['scene_beats'] as $beat) {
                $idx = ($beat['segment_index'] ?? 0) - 1;
                $sceneLocationMap[$idx] = $beat['location_id'] ?? '';
            }
        }

        Log::info('StoryModeScriptService: Phase 1 Director\'s Treatment completed', [
            'characters' => count($characterBible),
            'locations' => count($treatment['location_bible'] ?? []),
            'scene_beats' => count($treatment['scene_beats'] ?? []),
        ]);

        // ── Phase 2: Windowed Visual Direction ──
        $windowSize = 4;
        $allVisuals = [];       // indexed by segment position
        $previousScenes = [];   // last 2 completed scenes for continuity

        $aspectFraming = $this->buildAspectRatioFraming($aspectRatio, $aspectRatioLabel);

        for ($windowStart = 0; $windowStart < count($segments); $windowStart += $windowSize) {
            $windowSegments = array_slice($segments, $windowStart, $windowSize);
            $windowBeats = array_slice($treatment['scene_beats'] ?? [], $windowStart, $windowSize);
            $isLastWindow = ($windowStart + $windowSize) >= count($segments);

            $windowResult = $this->phase2WindowVisualDirection(
                $treatment, $windowSegments, $windowBeats,
                $windowStart, $previousScenes, $styleContext,
                $aspectRatioLabel, $isLastWindow,
                $engine, $model, $effectiveTeamId
            );

            if ($windowResult !== null) {
                foreach ($windowResult as $idx => $visual) {
                    $allVisuals[$windowStart + $idx] = $visual;
                }

                // Update continuity context: keep last 2 completed scenes
                $windowCount = count($windowResult);
                if ($windowCount >= 2) {
                    $previousScenes = array_slice($windowResult, -2);
                } elseif ($windowCount === 1) {
                    $previousScenes = array_merge(
                        count($previousScenes) > 0 ? [end($previousScenes)] : [],
                        $windowResult
                    );
                }

                Log::info('StoryModeScriptService: Phase 2 window completed', [
                    'window_start' => $windowStart + 1,
                    'scenes_in_window' => $windowCount,
                ]);
            } else {
                // Window failed — use Phase 1 beats for enhanced fallback
                Log::warning('StoryModeScriptService: Phase 2 window failed, using enhanced fallback', [
                    'window_start' => $windowStart + 1,
                    'window_size' => count($windowSegments),
                ]);

                foreach ($windowSegments as $idx => $seg) {
                    $beat = $windowBeats[$idx] ?? [];
                    $fallbackText = $this->stripDialogueFromText($seg['text']);
                    $allVisuals[$windowStart + $idx] = [
                        'image_prompt' => "A cinematic scene depicting: {$fallbackText}. No text or subtitles.",
                        'video_action' => '',
                        'characters_in_scene' => $beat['characters'] ?? [],
                        'camera_motion' => $beat['camera_hint'] ?? 'slow zoom in',
                        'mood' => $beat['mood_hint'] ?? 'professional',
                        'voice_emotion' => 'neutral',
                        'transition_type' => 'fade',
                        'transition_duration' => 0.5,
                    ];
                }
            }
        }

        // ── Post-processing: same as legacy path ──
        $result = [];
        foreach ($segments as $i => $segment) {
            $visual = $allVisuals[$i] ?? [];
            $fallbackText = $this->stripDialogueFromText($segment['text']);
            $imagePrompt = $visual['image_prompt'] ?? "A visual scene depicting: {$fallbackText}. No text or subtitles.";
            $charsInScene = $visual['characters_in_scene'] ?? [];

            // Inject character descriptions from bible into the image prompt
            $imagePrompt = $this->injectCharacterDescriptions($imagePrompt, $charsInScene, $charLookup);

            // Prefer Phase 2 location_id over Phase 1 beat mapping
            if (!empty($visual['location_id'])) {
                $sceneLocationMap[$i] = $visual['location_id'];
            }

            // Inject location context from Phase 1
            $imagePrompt = $this->injectLocationContext($imagePrompt, $i, $sceneLocationMap, $locationLookup);

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

        Log::info('StoryModeScriptService: Two-phase visual script completed', [
            'total_scenes' => count($result),
            'phase1' => 'success',
            'phase2_windows' => (int) ceil(count($segments) / $windowSize),
        ]);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Phase 1: Director's Treatment
    //  One AI call to plan the visual story arc across ALL segments.
    // ─────────────────────────────────────────────────────────────────────

    protected function phase1DirectorsTreatment(
        string $allSegments, int $segmentCount,
        string $engine, string $model, int $teamId
    ): ?array {
        $prompt = <<<PROMPT
You are a cinematic director planning a visual story. Read ALL narration segments below and create a comprehensive Director's Treatment that will guide per-scene image/video generation.

NARRATION SEGMENTS:
{$allSegments}

Your treatment must include:

1. **visual_tone**: The overall visual identity for this video — describe the color palette (warm/cool/muted/saturated), lighting style (natural/dramatic/neon/soft), film reference if applicable (e.g. "Blade Runner neon noir", "Wes Anderson pastel symmetry"), recurring visual motifs, and era/texture (modern clean, vintage grain, futuristic sleek).

2. **character_bible**: Array of every recurring character or named subject. For each:
   - "id": short_snake_case identifier
   - "name": display name
   - "description": detailed visual identity — age, gender, build, skin tone, hair color/style, eye color, facial features, clothing, accessories, distinguishing marks
   - "appears_in": array of segment numbers (1-indexed) where they appear

3. **location_bible**: Array of every distinct location. For each:
   - "id": short_snake_case identifier
   - "name": location name
   - "type": "INTERIOR" or "EXTERIOR"
   - "description": detailed visual description — MUST start with INTERIOR or EXTERIOR, then architecture, walls/ceiling/floor or terrain/sky, lighting source (natural/artificial/neon), color temperature, textures, atmosphere, time of day, weather, key props or furniture
   - "appears_in": array of segment numbers where this location is used

4. **scene_beats**: One entry per segment (exactly {$segmentCount} entries). For each:
   - "segment_index": 1-indexed segment number
   - "visual_summary": What the CAMERA SEES during this segment (1-2 sentences). CRITICAL: For dialogue-only segments like spoken lines, describe the VISUAL ACTION — body language, spatial relationships, environment details, character positions — NOT the dialogue itself. The camera cannot record words, only images.
   - "location_id": which location from location_bible
   - "characters": array of character IDs present
   - "mood_hint": suggested mood (calm, dramatic, energetic, tense, mysterious, epic, playful, nostalgic, professional, horror, intimate, hopeful)
   - "camera_hint": suggested camera motion (slow zoom in, slow zoom out, dramatic zoom in, pan left, pan right, tilt up, tilt down, push to subject, rise and reveal, diagonal drift, breathe)
   - "continuity_note": what connects this scene to the next (shared character, location transition, emotional shift, visual motif)

CRITICAL RULES:
- The visual_summary must describe what is VISUALLY HAPPENING, never repeat dialogue
- If a segment is pure dialogue like "It's a cage.", describe the speaker's body language, the environment they're in, their spatial relationship to others
- Ensure continuity_notes create a coherent visual flow from scene to scene
- If there are no recurring characters (e.g. landscapes, abstract), return empty character_bible
- Every segment must have a scene_beat — no gaps

Respond ONLY with valid JSON:
{
  "visual_tone": "...",
  "character_bible": [...],
  "location_bible": [...],
  "scene_beats": [...]
}

Output ONLY valid JSON, no markdown.
PROMPT;

        try {
            $response = AI::processWithOverride(
                "You are a cinematic director. Respond only with valid JSON.\n\n{$prompt}",
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.5,
                    'max_tokens' => 4000,
                ],
                $teamId
            );

            if (!empty($response['error'])) {
                Log::error('StoryModeScriptService: Phase 1 AI error', ['error' => $response['error']]);
                return null;
            }

            $content = $response['data'][0] ?? '';
            $parsed = $this->parseJsonResponse($content);

            if (!is_array($parsed) || empty($parsed['scene_beats'])) {
                Log::error('StoryModeScriptService: Phase 1 parse failed or missing scene_beats');
                return null;
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error('StoryModeScriptService: Phase 1 exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Phase 2: Windowed Visual Direction
    //  One AI call per window of ~4 scenes, with continuity from previous.
    // ─────────────────────────────────────────────────────────────────────

    protected function phase2WindowVisualDirection(
        array $treatment, array $windowSegments, array $windowBeats,
        int $windowStart, array $previousScenes, string $styleContext,
        string $aspectRatioLabel, bool $isLastWindow,
        string $engine, string $model, int $teamId
    ): ?array {
        // Build treatment context header
        $visualTone = $this->safeStr($treatment['visual_tone'] ?? 'cinematic', 'cinematic');
        $locationDescs = '';
        foreach ($treatment['location_bible'] ?? [] as $loc) {
            $name = $this->safeStr($loc['name'] ?? '');
            $id = $this->safeStr($loc['id'] ?? '');
            $desc = $this->safeStr($loc['description'] ?? '');
            $locationDescs .= "- {$name} ({$id}): {$desc}\n";
        }
        $charDescs = '';
        foreach ($treatment['character_bible'] ?? [] as $char) {
            $name = $this->safeStr($char['name'] ?? '');
            $id = $this->safeStr($char['id'] ?? '');
            $desc = $this->safeStr($char['description'] ?? '');
            $charDescs .= "- {$name} ({$id}): {$desc}\n";
        }

        // Build continuity context from previous 2 scenes
        $continuityBlock = '';
        if (!empty($previousScenes)) {
            $continuityBlock = "\n## PREVIOUS SCENES (for continuity — read-only, do NOT regenerate):\n";
            foreach ($previousScenes as $idx => $prev) {
                $prevNum = $windowStart - count($previousScenes) + $idx + 1;
                $continuityBlock .= "Scene {$prevNum}: image_prompt=\"" . ($prev['image_prompt'] ?? '') . "\", "
                    . "camera=\"" . ($prev['camera_motion'] ?? '') . "\", "
                    . "mood=\"" . ($prev['mood'] ?? '') . "\", "
                    . "transition=\"" . ($prev['transition_type'] ?? '') . "\"\n";
            }
        }

        // Build current window segments with their Phase 1 beats
        $windowBlock = '';
        foreach ($windowSegments as $idx => $seg) {
            $segNum = $windowStart + $idx + 1;
            $beat = $windowBeats[$idx] ?? [];
            $windowBlock .= "Segment {$segNum}: \"{$seg['text']}\"\n";
            if (!empty($beat)) {
                $windowBlock .= "  Director's beat: visual_summary=\"" . $this->safeStr($beat['visual_summary'] ?? '') . "\", "
                    . "location=\"" . $this->safeStr($beat['location_id'] ?? '') . "\", "
                    . "mood_hint=\"" . $this->safeStr($beat['mood_hint'] ?? '') . "\", "
                    . "camera_hint=\"" . $this->safeStr($beat['camera_hint'] ?? '') . "\", "
                    . "continuity=\"" . $this->safeStr($beat['continuity_note'] ?? '') . "\"\n";
            }
        }

        $lastSceneNote = $isLastWindow
            ? "\n- The LAST scene in this batch is the FINAL scene of the entire video. Use \"fade\" transition and a conclusive mood."
            : '';

        $prompt = <<<PROMPT
You are a cinematic director generating detailed per-scene visual direction for AI image and video generation.

## DIRECTOR'S TREATMENT (overall vision for the video):
Visual tone: {$visualTone}

Locations:
{$locationDescs}
Characters:
{$charDescs}{$continuityBlock}

## SCENES TO DIRECT (generate full direction for each):
{$windowBlock}
{$styleContext}

For EACH scene above, output ALL 9 mandatory fields:

1. **image_prompt**: Detailed image generation prompt (1-3 sentences) — subject, setting, lighting, mood, composition, camera perspective. CRITICAL: Compose for {$aspectRatioLabel} format. Never describe text, subtitles, or written words — purely visual.
2. **video_action** (MANDATORY): Rich Seedance 2.0 scene description (2-4 sentences of flowing prose).
   Structure: Setting with atmosphere → Primary action with explicit physical motion → Environmental response.
   RULES: Active present-tense verbs ONLY. BANNED: "goes", "moves", "starts", "begins", "is".
   Include physical details and atmospheric texture. Do NOT describe clothing, facial expressions, camera, or style.
   GOOD: "Inside a vast server room bathed in cool blue-purple neon, data streams flow as visible threads of light converging onto a central glowing AI core. The core pulses steadily brighter as each data thread arrives, casting shifting geometric shadows across glass floor panels."
   BAD: "The data flows to the AI core" — too vague, no setting, no atmosphere.
3. **characters_in_scene**: Array of character IDs from the bible
4. **camera_motion**: Pick ONE: "slow zoom in", "slow zoom out", "dramatic zoom in", "pan left", "pan right", "pan left slow", "pan right slow", "tilt up", "tilt down", "zoom in pan right", "zoom out pan left", "diagonal drift", "push to subject", "rise and reveal", "settle in", "breathe"
5. **mood**: Pick ONE: calm, dramatic, energetic, tense, mysterious, epic, playful, nostalgic, professional, horror, intimate, hopeful
6. **voice_emotion**: Pick ONE: neutral, dramatic, funny, excited, calm, mysterious, sad, confident, urgent, contemplative, storytelling, whisper
7. **transition_type**: FFmpeg xfade transition. Match mood: calm→"fade"/"dissolve", dramatic→"fadeblack"/"radial", energetic→"wipeleft"/"pixelize", tense→"hblur"/"distance", mysterious→"dissolve"/"fadegrays".
8. **transition_duration**: 0.3 (energetic), 0.5 (normal), 0.8 (calm), 1.0 (dramatic)
9. **location_id**: The location_id from the Director's beat for this scene. MUST match the beat's location.{$lastSceneNote}

LOCATION ACCURACY (CRITICAL — most common failure):
- Every image_prompt MUST begin with the location setting. Use the location_id from the Director's beat and reference that location's description.
- INTERIOR scenes: Describe walls, ceiling, floor, furniture, artificial lighting. Do NOT show outdoor sky, streets, or neon signs visible from outside.
- EXTERIOR scenes: Describe sky, buildings, streets, weather.
- If a scene takes place in "underground_data_hub", the image_prompt must show an underground room with servers/terminals — NEVER a street.
- The style instruction (e.g., "blade runner aesthetic") applies to the MOOD of the image, NOT the location. An interior server room should look like a blade runner SERVER ROOM, not a blade runner STREET.

CONTINUITY RULES:
- Never use the same camera_motion as the immediately previous scene
- Never use the same transition_type for more than 2 consecutive scenes
- Match voice_emotion to each scene's content, NOT a single global mood
- Use the Director's beats as creative seeds but add richer visual detail

Respond ONLY with a JSON object:
{
  "scenes": [
    {
      "segment_index": <number>,
      "image_prompt": "...",
      "video_action": "...",
      "characters_in_scene": [],
      "camera_motion": "...",
      "mood": "...",
      "voice_emotion": "...",
      "transition_type": "...",
      "transition_duration": 0.5,
      "location_id": "..."
    }
  ]
}

Output ONLY valid JSON, no markdown.
PROMPT;

        try {
            $response = AI::processWithOverride(
                "You are a cinematic director. Respond only with valid JSON.\n\n{$prompt}",
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.6,
                    'max_tokens' => 4000,
                ],
                $teamId
            );

            if (!empty($response['error'])) {
                Log::error('StoryModeScriptService: Phase 2 window AI error', [
                    'window_start' => $windowStart + 1,
                    'error' => $response['error'],
                ]);
                return null;
            }

            $content = $response['data'][0] ?? '';
            $parsed = $this->parseJsonResponse($content);

            if (!is_array($parsed)) {
                Log::error('StoryModeScriptService: Phase 2 window parse failed', [
                    'window_start' => $windowStart + 1,
                ]);
                return null;
            }

            // Extract scenes array (handle both {scenes:[...]} and flat [...] formats)
            $scenes = $parsed['scenes'] ?? $parsed;
            if (!is_array($scenes) || empty($scenes)) {
                Log::error('StoryModeScriptService: Phase 2 window returned no scenes', [
                    'window_start' => $windowStart + 1,
                ]);
                return null;
            }

            return array_values($scenes);
        } catch (\Exception $e) {
            Log::error('StoryModeScriptService: Phase 2 window exception', [
                'window_start' => $windowStart + 1,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Legacy single-batch visual script (fallback if Phase 1 fails)
    //  Exact behavior of the original buildVisualScript() — zero changes.
    // ─────────────────────────────────────────────────────────────────────

    protected function legacySingleBatchVisualScript(
        array $segments, string $allSegments, string $styleInstruction,
        string $styleContext, string $aspectRatio, string $aspectRatioLabel,
        string $engine, string $model, int $teamId
    ): array {
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

LOCATION ACCURACY: Every image_prompt must explicitly describe the physical setting.
For INTERIOR scenes: describe the room, walls, ceiling, lighting source, furniture.
For EXTERIOR scenes: describe the sky, buildings, weather, terrain.
Never default to outdoor neon streets — match the actual scene location.

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

            $response = AI::processWithOverride(
                $fullPrompt,
                $engine,
                $model,
                'text',
                [
                    'temperature' => 0.6,
                    'max_tokens' => 8000,
                ],
                $teamId
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
                $characterBible = $parsed['character_bible'] ?? [];
                $visualScript = $parsed['scenes'] ?? [];

                Log::info('StoryModeScriptService: [Legacy] Character bible extracted', [
                    'characters' => count($characterBible),
                    'names' => array_column($characterBible, 'name'),
                ]);
            } else {
                $visualScript = $parsed;
                Log::info('StoryModeScriptService: [Legacy] Flat array format (no character bible)');
            }

            $this->lastCharacterBible = $characterBible;

            $charLookup = [];
            foreach ($characterBible as $char) {
                $charLookup[$char['id']] = $char;
            }

            $aspectFraming = $this->buildAspectRatioFraming($aspectRatio, $aspectRatioLabel);

            $fieldsPresent = [];
            foreach ($visualScript as $idx => $vs) {
                $fieldsPresent[$idx] = [
                    'has_image_prompt' => !empty($vs['image_prompt']),
                    'has_video_action' => !empty($vs['video_action']),
                    'video_action_length' => strlen($vs['video_action'] ?? ''),
                ];
            }
            Log::info('StoryModeScriptService: [Legacy] AI response field analysis', [
                'total_scenes' => count($visualScript),
                'fields' => $fieldsPresent,
            ]);

            $result = [];
            foreach ($segments as $i => $segment) {
                $visual = $visualScript[$i] ?? [];
                $fallbackText = $this->stripDialogueFromText($segment['text']);
                $imagePrompt = $visual['image_prompt'] ?? "A visual scene depicting: {$fallbackText}. No text or subtitles.";
                $charsInScene = $visual['characters_in_scene'] ?? [];

                $imagePrompt = $this->injectCharacterDescriptions($imagePrompt, $charsInScene, $charLookup);
                $imagePrompt .= "\n\n" . $aspectFraming;

                $videoAction = $visual['video_action'] ?? '';
                if (empty(trim($videoAction))) {
                    $videoAction = $this->generateFallbackVideoAction($segment['text'], $visual['image_prompt'] ?? '', $visual['mood'] ?? 'professional');
                    Log::info("StoryModeScriptService: [Legacy] Generated fallback video_action for scene {$i}");
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
            Log::error('StoryModeScriptService: [Legacy] Visual script generation failed', [
                'error' => $e->getMessage(),
            ]);

            $this->lastCharacterBible = [];
            $aspectFraming = $this->buildAspectRatioFraming($aspectRatio, $aspectRatioLabel ?? 'portrait 9:16 vertical');

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
            '9:16' => "MANDATORY: Compose in {$aspectRatioLabel} format. Use TALL vertical composition — subjects must fill the frame vertically. Use close-up or medium shots, NOT wide shots. Frame subjects centered with headroom at top. Never compose as if this were a landscape or square image. Fill the ENTIRE frame edge-to-edge — no black bars, no letterboxing, no borders.",
            '1:1' => "MANDATORY: Compose in {$aspectRatioLabel} format. Center subjects with balanced framing in a square composition. Fill the ENTIRE frame edge-to-edge — no black bars, no letterboxing, no borders.",
            default => "MANDATORY: Compose in {$aspectRatioLabel} format. Use wide horizontal cinematic composition with subjects spread across the frame. Fill the ENTIRE frame edge-to-edge — no black bars, no letterboxing, no borders.",
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
     * Inject location context from Phase 1 location_bible into the image prompt.
     * Prepends a SETTING prefix and appends a LOCATION SETTING constraint block.
     */
    protected function injectLocationContext(
        string $imagePrompt,
        int $sceneIndex,
        array $sceneLocationMap,
        array $locationLookup
    ): string {
        $locationId = $sceneLocationMap[$sceneIndex] ?? '';
        if (empty($locationId) || !isset($locationLookup[$locationId])) {
            return $imagePrompt;
        }

        $loc = $locationLookup[$locationId];
        $locType = strtoupper($loc['type'] ?? 'INTERIOR');
        $locName = $loc['name'] ?? $locationId;
        $locDesc = $loc['description'] ?? '';

        // Build location prefix
        $prefix = "SETTING: {$locType} — {$locName}.";
        if (!empty($locDesc)) {
            // Take first 1-2 key sentences from the location description
            $sentences = preg_split('/(?<=[.!?])\s+/', trim($locDesc), 3);
            $shortDesc = implode(' ', array_slice($sentences, 0, 2));
            $prefix .= " {$shortDesc}";
        }

        // Also append as a constraint block (like character identity)
        $constraint = "\n\nLOCATION SETTING (maintain consistent environment):\n"
            . "{$locType}: {$locName} — {$locDesc}\n"
            . "Do NOT show elements from other locations.";

        return $prefix . "\n" . $imagePrompt . $constraint;
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
                $rawDuration = round(($segmentWords / $wordsPerMinute) * 60, 1);
                // Snap to Seedance native durations: 5s or 10s
                $segmentDuration = $rawDuration <= 7.5 ? 5.0 : 10.0;

                $segments[] = [
                    'text' => $currentSegment,
                    'estimated_duration' => $segmentDuration,
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
            $isVisualOnly = $dialogueWords === 0;

            if ($isVisualOnly) {
                // Visual-only scenes: 10s to maximize Seedance capacity
                $segmentDuration = 10.0;
            } else {
                // Dialogue scenes: calculate from word count, snap to Seedance native [5, 10]
                $rawDuration = round(($dialogueWords / $wordsPerMinute) * 60, 1);
                $segmentDuration = $rawDuration <= 7.5 ? 5.0 : 10.0;
            }

            $segments[] = [
                'text' => $body ?: "[Scene: {$direction}]",
                'direction' => $direction,
                'estimated_duration' => $segmentDuration,
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

    /**
     * Safely convert any value to a string. Handles arrays/objects from AI responses.
     */
    protected function safeStr(mixed $value, string $default = ''): string
    {
        if (is_string($value)) return $value;
        if (is_null($value)) return $default;
        if (is_scalar($value)) return (string) $value;
        if (is_array($value)) return implode(', ', array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
        return json_encode($value) ?: $default;
    }
}
