<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;

class ConceptService
{
    /**
     * AI Model Tier configurations.
     * Maps tier names to provider/model pairs.
     */
    const AI_MODEL_TIERS = [
        'economy' => [
            'provider' => 'grok',
            'model' => 'grok-4-fast',
        ],
        'standard' => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ],
        'premium' => [
            'provider' => 'openai',
            'model' => 'gpt-4o',
        ],
    ];

    /**
     * Call AI with tier-based model selection.
     */
    protected function callAIWithTier(string $prompt, string $tier, int $teamId, array $options = []): array
    {
        $config = self::AI_MODEL_TIERS[$tier] ?? self::AI_MODEL_TIERS['economy'];

        return AI::processWithOverride(
            $prompt,
            $config['provider'],
            $config['model'],
            'text',
            $options,
            $teamId
        );
    }

    /**
     * Improve/enhance a raw concept using AI.
     */
    public function improveConcept(string $rawInput, array $options = []): array
    {
        $productionType = $options['productionType'] ?? null;
        $productionSubType = $options['productionSubType'] ?? null;
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        $prompt = $this->buildImprovePrompt($rawInput, $productionType, $productionSubType);

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 8000, // Ensure enough tokens for full JSON response
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        \Log::info('ConceptService: AI response length', ['length' => strlen($response)]);

        $parsed = $this->parseImproveResponse($response);

        // Include token usage metadata for logging
        $parsed['_meta'] = [
            'tokens_used' => $result['totalTokens'] ?? null,
            'model' => $result['model'] ?? null,
        ];

        return $parsed;
    }

    /**
     * Build the concept improvement prompt.
     */
    protected function buildImprovePrompt(string $rawInput, ?string $productionType, ?string $productionSubType): string
    {
        $typeContext = '';
        if ($productionType) {
            $typeContext = "Production Type: {$productionType}";
            if ($productionSubType) {
                $typeContext .= " / {$productionSubType}";
            }
        }

        return <<<PROMPT
You are a creative video concept developer. Transform this rough idea into a refined, detailed concept.

RAW IDEA:
{$rawInput}

{$typeContext}

Analyze the idea and return a JSON response with:
{
  "improvedConcept": "A detailed, polished version of the concept (2-3 paragraphs)",
  "logline": "A one-sentence summary that captures the essence",
  "suggestedMood": "The overall mood/atmosphere (e.g., inspiring, mysterious, energetic)",
  "suggestedTone": "The tone (e.g., professional, casual, humorous)",
  "keyElements": ["element1", "element2", "element3"],
  "uniqueElements": ["what makes this unique 1", "what makes this unique 2"],
  "avoidElements": ["cliche to avoid 1", "cliche to avoid 2"],
  "targetAudience": "Description of the ideal viewer",
  "characters": [
    {
      "name": "Character name",
      "role": "protagonist/supporting/narrator",
      "archetype": "hero/mentor/trickster/etc",
      "description": "Brief description"
    }
  ],
  "worldBuilding": {
    "setting": "Where/when the story takes place",
    "rules": ["Any special rules or elements of the world"],
    "atmosphere": "The visual/emotional atmosphere"
  }
}

Be creative but stay true to the core idea. Make suggestions that enhance without completely changing the concept.
PROMPT;
    }

    /**
     * Parse the AI response.
     */
    protected function parseImproveResponse(string $response): array
    {
        $response = trim($response);
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::warning('ConceptService: Initial JSON parse failed, attempting repair', [
                'error' => json_last_error_msg(),
                'response_length' => strlen($response),
            ]);

            // Try to extract and repair JSON
            preg_match('/\{[\s\S]*"improvedConcept"[\s\S]*/', $response, $matches);
            if (!empty($matches[0])) {
                $repairedJson = $this->repairTruncatedJson($matches[0]);
                $result = json_decode($repairedJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::error('ConceptService: JSON repair failed', [
                        'error' => json_last_error_msg(),
                    ]);
                } else {
                    \Log::info('ConceptService: JSON repair successful');
                }
            }
        }

        if (!$result || !isset($result['improvedConcept'])) {
            \Log::error('ConceptService: Failed to parse response', [
                'has_result' => !empty($result),
                'has_improvedConcept' => isset($result['improvedConcept']),
                'response_preview' => substr($response, 0, 500),
            ]);
            throw new \Exception('Failed to parse concept improvement response');
        }

        return $result;
    }

    /**
     * Attempt to repair truncated JSON.
     */
    protected function repairTruncatedJson(string $json): string
    {
        // Remove any trailing incomplete string
        $json = preg_replace('/,?\s*"[^"]*":\s*"[^"]*$/s', '', $json);

        // Remove any trailing incomplete array
        $json = preg_replace('/,?\s*"[^"]*":\s*\[[^\]]*$/s', '', $json);

        // Remove any incomplete key at the end
        $json = preg_replace('/,?\s*"[^"]*$/s', '', $json);

        // Remove trailing commas before closing brackets
        $json = preg_replace('/,(\s*[\]\}])/s', '$1', $json);
        $json = preg_replace('/,\s*$/s', '', $json);

        // Count brackets
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');

        // Add missing closing characters
        $json .= str_repeat(']', max(0, $openBrackets - $closeBrackets));
        $json .= str_repeat('}', max(0, $openBraces - $closeBraces));

        return $json;
    }

    /**
     * Generate multiple concept variations.
     */
    public function generateVariations(string $concept, int $count = 3, array $options = []): array
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        $prompt = <<<PROMPT
Based on this video concept, generate {$count} unique variations that explore different angles or approaches:

ORIGINAL CONCEPT:
{$concept}

Return as JSON array:
[
  {
    "title": "Variation title",
    "concept": "The variation concept",
    "angle": "How this differs from original",
    "strengths": ["strength1", "strength2"]
  }
]
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 8000, // Ensure enough tokens for variations
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = trim($result['data'][0] ?? '');
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        $variations = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::warning('ConceptService: Variations JSON parse failed, attempting repair');
            $response = $this->repairTruncatedJson($response);
            $variations = json_decode($response, true);
        }

        return [
            'variations' => $variations ?? [],
            '_meta' => [
                'tokens_used' => $result['totalTokens'] ?? null,
                'model' => $result['model'] ?? null,
            ],
        ];
    }

    /**
     * Generate viral social content ideas for 9:16 vertical format.
     * Returns 6 concept cards with character, situation, audio type, and viral hook.
     */
    public function generateViralIdeas(string $theme, array $options = []): array
    {
        $count = $options['count'] ?? 6;
        $teamId = $options['teamId'] ?? 0;
        $aiModelTier = $options['aiModelTier'] ?? 'economy';
        $videoEngine = $options['videoEngine'] ?? 'seedance';

        $themeContext = !empty($theme)
            ? "The user wants ideas related to: \"{$theme}\". Incorporate this theme creatively."
            : "Generate completely original ideas with diverse themes.";

        if ($videoEngine === 'seedance') {
            $prompt = $this->buildSeedanceViralPrompt($themeContext, $count);
        } else {
            $prompt = $this->buildInfiniteTalkViralPrompt($themeContext, $count);
        }

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 4000,
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = trim($result['data'][0] ?? '');
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        $variations = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::warning('ConceptService: Viral ideas JSON parse failed, attempting repair');
            $response = $this->repairTruncatedJson($response);
            $variations = json_decode($response, true);
        }

        return [
            'variations' => $variations ?? [],
            '_meta' => [
                'tokens_used' => $result['totalTokens'] ?? null,
                'model' => $result['model'] ?? null,
            ],
        ];
    }

    /**
     * Build viral ideas prompt for Seedance engine (cinematic scene with auto-generated audio).
     */
    protected function buildSeedanceViralPrompt(string $themeContext, int $count): string
    {
        return <<<PROMPT
You are a viral content specialist who creates massively shareable short-form video concepts.

{$themeContext}

IMPORTANT: These ideas will be animated using Seedance — an AI model that generates
video + voice + sound effects ALL FROM A TEXT PROMPT. There is no separate audio recording.
The model will auto-generate any dialogue, sounds, and music from your description.

Generate exactly {$count} unique viral 9:16 vertical video concepts. Each MUST follow the proven viral formula:
- An ANIMAL or quirky CHARACTER in an absurd/funny human situation
- Single continuous shot (NO scene changes, NO transitions)
- 4-12 seconds duration
- Focus on VISUAL COMEDY, physical humor, dramatic reactions, animals in situations
- Short punchy scenes with strong visual hooks
- Environmental sounds and ambient audio (sizzling, splashing, crowd noise)
- Dialogue should be SHORT (1-2 lines max, embedded in scene description)
- Emphasis on MOTION and ACTION (not talking heads)

Mix of MONOLOGUE and DIALOGUE but focus on visual storytelling — Seedance excels at
action scenes, physical comedy, and dramatic moments more than extended conversations.

Each idea MUST include a "videoPrompt" field — a detailed scene description following this 4-layer format:
1. Subject & action (who, what, where)
2. Dialogue in "quotes" (if any — keep short)
3. Environmental audio cues (comma-separated sounds)
4. Visual style & mood (camera, lighting, tone)

Return ONLY a JSON array (no markdown, no explanation):
[
  {
    "title": "Catchy title (max 6 words)",
    "concept": "One sentence describing the full visual scene",
    "speechType": "monologue" or "dialogue",
    "characters": [
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "role", "expression": "expression description"}
    ],
    "character": "For monologue: single character description",
    "situation": "The scene action and character interaction",
    "setting": "Detailed location with specific props, brand elements, decor, lighting",
    "props": "Key visual props in the scene",
    "audioType": "voiceover",
    "audioDescription": "Brief description of what happens (for metadata)",
    "dialogueLines": [
      {"speaker": "Character Name", "text": "Short punchy line"}
    ],
    "videoPrompt": "A grumpy cat in a green apron stands behind a pizza counter, staring down a customer holding an open pizza box. The cat hisses 'No refunds!', cash register beeping, restaurant chatter, pizza boxes rustling, handheld phone footage, warm fluorescent lighting, comedic deadpan tone.",
    "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
    "viralHook": "Why this would go viral (one sentence)"
  }
]
PROMPT;
    }

    /**
     * Build viral ideas prompt for InfiniteTalk engine (lip-sync from custom voices).
     */
    protected function buildInfiniteTalkViralPrompt(string $themeContext, int $count): string
    {
        return <<<PROMPT
You are a viral content specialist who creates massively shareable short-form video concepts.

{$themeContext}

Generate exactly {$count} unique viral 9:16 vertical video concepts. Each MUST follow the proven viral formula:
- An ANIMAL or quirky CHARACTER in an absurd/funny human situation
- Single continuous shot (NO scene changes, NO transitions)
- 8-10 seconds duration
- Characters' mouths will be LIP-SYNCED to audio

IMPORTANT: Mix of TWO types:
1. DIALOGUE scenes (at least half): TWO characters interacting — e.g., an animal employee and a human customer, a cat boss and a dog intern. The comedy comes from the interaction.
2. MONOLOGUE scenes: One character speaking directly to camera or doing a solo bit.

For DIALOGUE concepts:
- "speechType": "dialogue"
- "characters": array of 2 character objects with name, description, role, expression
- "dialogueLines": array of 3-4 short alternating lines (speaker + text, max 12 words per line)
- The dialogue must be FUNNY — deadpan humor, sarcasm, absurd complaints, unexpected responses

For MONOLOGUE concepts:
- "speechType": "monologue"
- "character": single character description
- "audioDescription": what they say (max 20 words)

For ALL concepts, also specify:
- "audioType": "voiceover" (spoken dialogue/monologue) or "music-lipsync" (character sings)
- Detailed "setting" with specific props, decor, brand elements, and environmental details

Return ONLY a JSON array (no markdown, no explanation):
[
  {
    "title": "Catchy title (max 6 words)",
    "concept": "One sentence describing the full visual scene with all characters",
    "speechType": "dialogue" or "monologue",
    "characters": [
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "employee/customer/boss/etc", "expression": "deadpan, slightly annoyed"},
      {"name": "Character 2 Name", "description": "detailed visual description", "role": "role", "expression": "expression description"}
    ],
    "character": "For monologue only: single character description",
    "situation": "The scene action and character interaction",
    "setting": "Detailed location with specific props, brand elements, decor, lighting (e.g., 'Papa John's counter with red pizza boxes, menu boards with pizza images, cash register, drink cups, warm fluorescent lighting')",
    "props": "Key visual props in the scene (e.g., 'open pizza box, green uniform with cap, branded counter')",
    "audioType": "voiceover",
    "audioDescription": "For monologue: the spoken text. For dialogue: brief scene description",
    "dialogueLines": [
      {"speaker": "Character Name", "text": "Short punchy line"},
      {"speaker": "Character 2 Name", "text": "Funny response"}
    ],
    "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
    "viralHook": "Why this would go viral (one sentence)"
  }
]
PROMPT;
    }

    // ========================================================================
    // VIDEO CONCEPT CLONER — Analyze uploaded video and extract concept
    // ========================================================================

    /**
     * Main pipeline: Analyze an uploaded video and produce a structured concept.
     *
     * Stage 1: Extract key frames with ffmpeg + analyze with Grok 4.1 Fast vision
     * Stage 2: Extract audio + transcribe with Whisper
     * Stage 3: AI synthesis into viral idea format (Grok 4.1 Fast)
     *
     * @param string $videoPath Absolute path to the uploaded video file
     * @param array $options teamId, aiModelTier, mimeType
     * @return array Structured concept matching generateViralIdeas() output format
     */
    public function analyzeVideoForConcept(string $videoPath, array $options = []): array
    {
        $teamId = $options['teamId'] ?? 0;
        $aiModelTier = $options['aiModelTier'] ?? 'economy';
        $videoEngine = $options['videoEngine'] ?? 'seedance';

        // Stage 1: Extract key frames + visual analysis with Grok vision
        Log::info('ConceptCloner: Stage 1 — Extracting frames and analyzing with Grok', [
            'fileSize' => filesize($videoPath),
        ]);

        $frames = $this->extractKeyFrames($videoPath);
        if (empty($frames)) {
            throw new \Exception('Failed to extract frames from video. Ensure the file is a valid video.');
        }

        $visualAnalysis = $this->analyzeFramesWithGrok($frames, $teamId);
        // Clean up temp frame files
        foreach ($frames as $framePath) {
            @unlink($framePath);
        }

        Log::info('ConceptCloner: Stage 1 complete — Visual analysis received', [
            'textLength' => strlen($visualAnalysis),
            'frameCount' => count($frames),
            'analysisPreview' => mb_substr($visualAnalysis, 0, 500),
        ]);

        // Stage 2: Extract audio + transcribe (if audio exists)
        Log::info('ConceptCloner: Stage 2 — Extracting and transcribing audio');
        $transcript = $this->extractAndTranscribeAudio($videoPath);
        Log::info('ConceptCloner: Stage 2 complete', ['hasTranscript' => !empty($transcript)]);

        // Stage 3: Synthesize into structured concept
        Log::info('ConceptCloner: Stage 3 — Synthesizing concept');
        $concept = $this->synthesizeConcept($visualAnalysis, $transcript, $aiModelTier, $teamId, $videoEngine);
        Log::info('ConceptCloner: Pipeline complete', ['conceptTitle' => $concept['title'] ?? 'unknown']);

        return $concept;
    }

    /**
     * Extract key frames from a video using ffmpeg.
     * Returns an array of temp file paths (JPEG images).
     *
     * Strategy: Extract exactly 8 frames evenly spaced across the video.
     * Uses two-pass approach — first detects total frames via ffprobe stream,
     * then extracts every Nth frame. This avoids the unreliable duration-based
     * approach which fails on Livewire temp files.
     */
    protected function extractKeyFrames(string $videoPath, int $maxFrames = 8): array
    {
        $ffmpegPath = PHP_OS_FAMILY === 'Windows' ? 'ffmpeg' : '/home/artime/bin/ffmpeg';
        $ffprobePath = PHP_OS_FAMILY === 'Windows' ? 'ffprobe' : '/home/artime/bin/ffprobe';
        $tempDir = sys_get_temp_dir();
        $prefix = 'concept_frame_' . uniqid();

        // Try to get total frame count from stream (more reliable than format duration)
        $frameCountCmd = sprintf(
            '%s -v error -select_streams v:0 -count_packets -show_entries stream=nb_read_packets -of csv=p=0 %s 2>&1',
            escapeshellcmd($ffprobePath),
            escapeshellarg($videoPath)
        );
        exec($frameCountCmd, $frameCountOutput, $frameCountReturn);
        $totalFrames = intval(trim($frameCountOutput[0] ?? '0'));

        Log::info('ConceptCloner: ffprobe frame count', [
            'totalFrames' => $totalFrames,
            'returnCode' => $frameCountReturn,
            'rawOutput' => implode('|', $frameCountOutput),
        ]);

        // Extract frames using the appropriate strategy
        $outputPattern = $tempDir . DIRECTORY_SEPARATOR . $prefix . '_%03d.jpg';

        if ($totalFrames > 0 && $totalFrames >= $maxFrames) {
            // Strategy A: select every Nth frame for even distribution
            $selectEvery = max(1, (int) floor($totalFrames / $maxFrames));
            $extractCmd = sprintf(
                '%s -i %s -vf "select=not(mod(n\\,%d))" -vsync vfr -frames:v %d -q:v 2 %s 2>&1',
                escapeshellcmd($ffmpegPath),
                escapeshellarg($videoPath),
                $selectEvery,
                $maxFrames,
                escapeshellarg($outputPattern)
            );
        } else {
            // Strategy B: fallback — extract at fixed timestamps (0.5s, 1.5s, 3s, 5s, 7s, 9s, 12s, 15s)
            // This covers most short-form videos (5-60s) without needing accurate duration
            $timestamps = [0.5, 1.5, 3.0, 5.0, 7.0, 9.0, 12.0, 15.0];
            $frames = [];
            foreach ($timestamps as $ts) {
                $framePath = $tempDir . DIRECTORY_SEPARATOR . $prefix . '_' . str_pad(count($frames) + 1, 3, '0', STR_PAD_LEFT) . '.jpg';
                $tsCmd = sprintf(
                    '%s -ss %s -i %s -frames:v 1 -q:v 2 %s 2>&1',
                    escapeshellcmd($ffmpegPath),
                    number_format($ts, 2, '.', ''),
                    escapeshellarg($videoPath),
                    escapeshellarg($framePath)
                );
                exec($tsCmd, $tsOutput, $tsReturn);
                if (file_exists($framePath) && filesize($framePath) > 100) {
                    $frames[] = $framePath;
                }
            }

            Log::info('ConceptCloner: Frame extraction (timestamp fallback)', [
                'extractedFrames' => count($frames),
            ]);

            return $frames;
        }

        exec($extractCmd, $extractOutput, $extractReturn);

        Log::info('ConceptCloner: Frame extraction (Nth frame)', [
            'totalVideoFrames' => $totalFrames,
            'selectEvery' => $selectEvery ?? 0,
            'targetFrames' => $maxFrames,
            'returnCode' => $extractReturn,
        ]);

        // Collect extracted frame paths
        $frames = [];
        for ($i = 1; $i <= $maxFrames + 5; $i++) { // check a few extra in case
            $framePath = $tempDir . DIRECTORY_SEPARATOR . $prefix . '_' . str_pad($i, 3, '0', STR_PAD_LEFT) . '.jpg';
            if (file_exists($framePath) && filesize($framePath) > 100) {
                $frames[] = $framePath;
                if (count($frames) >= $maxFrames) break;
            }
        }

        return $frames;
    }

    /**
     * Analyze extracted frames with Grok 4.1 Fast vision API.
     * Sends all frames as image_url content parts in a single request.
     */
    protected function analyzeFramesWithGrok(array $framePaths, int $teamId): string
    {
        $grokService = app(\App\Services\GrokService::class);

        // Build multimodal message with all frames
        $content = [];
        foreach ($framePaths as $i => $framePath) {
            $base64 = base64_encode(file_get_contents($framePath));
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/jpeg;base64,' . $base64,
                ],
            ];
        }

        // Add the analysis prompt
        $content[] = [
            'type' => 'text',
            'text' => $this->buildVideoAnalysisPrompt(),
        ];

        $messages = [[
            'role' => 'user',
            'content' => $content,
        ]];

        // IMPORTANT: Must use a model that explicitly supports image input.
        // 'grok-4-fast' is a text-only alias — it ignores images and hallucinates.
        // 'grok-4-1-fast-non-reasoning' is the latest model with explicit image input support.
        // See: https://docs.x.ai/developers/models
        $result = $grokService->generateVision($messages, [
            'model' => 'grok-4-1-fast-non-reasoning',
            'max_tokens' => 4000,
            'temperature' => 0.2,
        ]);

        if (!empty($result['error'])) {
            throw new \Exception('Grok vision analysis failed: ' . $result['error']);
        }

        $text = $result['data'][0] ?? '';
        if (empty($text)) {
            throw new \Exception('Grok vision returned empty analysis');
        }

        return $text;
    }

    /**
     * Build the visual analysis prompt for frame-based analysis.
     */
    protected function buildVideoAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing sequential frames extracted from a short-form video (TikTok/Reels/Shorts). These frames are in chronological order and represent the video's progression. Analyze them as a continuous scene with EXTREME PRECISION.

CRITICAL INSTRUCTION: You MUST identify every character/creature/animal with 100% accuracy. If you see a monkey, say MONKEY — not "primate" or "creature." If you see a golden retriever, say GOLDEN RETRIEVER — not just "dog." Be as specific as possible about breed, species, and subspecies. NEVER guess or generalize. Describe EXACTLY what you see.

1. CHARACTERS (be EXACT):
   - EXACT species — e.g., "capuchin monkey", "tabby cat", "golden retriever puppy", "adult human male." Do NOT generalize.
   - Fur/skin color, patterns, distinguishing marks
   - Clothing, accessories, colors (be specific: "red baseball cap", not "hat")
   - Facial expression and body language in EACH frame
   - Role: protagonist, supporting, background
   - Size relative to other objects/characters

2. SETTING & ENVIRONMENT:
   - Exact location (bathroom, kitchen counter, living room couch, outdoor garden, etc.)
   - Every visible prop and object (towel, sink, plate, phone, etc.)
   - Lighting type and direction
   - Background details, wall color, floor type, decor
   - Any text, signs, or brand names visible

3. ACTION SEQUENCE (frame by frame):
   - Frame 1: What is happening
   - Frame 2: What changed
   - (Continue for all frames)
   - Overall narrative arc: setup → action → punchline/reaction
   - Physical comedy beats, surprise moments, emotional shifts

4. CAMERA & VISUAL STYLE:
   - Camera angle per frame (eye-level, low-angle, high-angle, overhead)
   - Camera movement (static, slow pan, quick zoom, handheld shake)
   - Shot type (extreme close-up, close-up, medium, medium-wide, wide)
   - Visual style (realistic, CGI, cartoon, phone footage, professional, filter applied)
   - Color palette (warm/cool/saturated/muted), any color grading

5. MOOD & VIRAL FORMULA:
   - Dominant emotion (funny, absurd, wholesome, chaotic, cute, shocking)
   - The exact moment/hook that makes it shareable
   - Humor type (physical comedy, reaction, irony, cuteness overload, unexpected twist)
   - Pacing across frames (building tension, sudden payoff, slow reveal)

Return your analysis as detailed text. Be EXHAUSTIVE and PRECISE about every visual detail. Accuracy matters more than brevity.
PROMPT;
    }

    /**
     * Extract audio from video using ffmpeg and transcribe with OpenAI Whisper.
     * Returns null if video has no audio or extraction fails.
     */
    protected function extractAndTranscribeAudio(string $videoPath): ?string
    {
        $audioPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'concept_audio_' . uniqid() . '.wav';
        $ffmpegPath = PHP_OS_FAMILY === 'Windows' ? 'ffmpeg' : '/home/artime/bin/ffmpeg';

        $command = sprintf(
            '%s -i %s -vn -acodec pcm_s16le -ar 16000 -ac 1 %s 2>&1',
            escapeshellcmd($ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($audioPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($audioPath) || filesize($audioPath) < 1000) {
            @unlink($audioPath);
            Log::info('ConceptCloner: No audio extracted (silent video or extraction failed)', [
                'returnCode' => $returnCode,
            ]);
            return null;
        }

        try {
            // Use direct HTTP call to OpenAI Whisper API (SDK method is broken on server)
            $apiKey = (string) get_option('ai_openai_api_key', '');
            if (empty($apiKey)) {
                @unlink($audioPath);
                Log::warning('ConceptCloner: No OpenAI API key configured for STT');
                return null;
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'multipart' => [
                    ['name' => 'model', 'contents' => 'whisper-1'],
                    ['name' => 'file', 'contents' => fopen($audioPath, 'r'), 'filename' => 'audio.wav'],
                    ['name' => 'response_format', 'contents' => 'text'],
                ],
                'timeout' => 60,
            ]);

            @unlink($audioPath);

            $transcript = trim((string) $response->getBody());
            if (empty($transcript) || strlen($transcript) < 3) {
                return null;
            }

            Log::info('ConceptCloner: Audio transcribed', ['length' => strlen($transcript)]);
            return $transcript;
        } catch (\Throwable $e) {
            @unlink($audioPath);
            Log::warning('ConceptCloner: Audio transcription failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Synthesize Grok visual analysis + Whisper transcript into a structured concept.
     * Output matches the exact format returned by generateViralIdeas().
     */
    protected function synthesizeConcept(string $visualAnalysis, ?string $transcript, string $aiModelTier, int $teamId, string $videoEngine = 'seedance'): array
    {
        $transcriptSection = $transcript
            ? "AUDIO TRANSCRIPT:\n\"{$transcript}\"\n\nUse this transcript to determine speechType (monologue/dialogue), identify speakers, and generate dialogueLines."
            : "AUDIO: No speech detected in video. Assume monologue or narrator style based on visual cues.";

        $videoPromptInstruction = $videoEngine === 'seedance'
            ? 'Also generate a "videoPrompt" field — a detailed 4-layer Seedance prompt: (1) Subject & action, (2) Dialogue in "quotes" if any, (3) Environmental audio cues, (4) Visual style & mood.'
            : 'Do NOT generate a "videoPrompt" field.';

        $prompt = <<<PROMPT
You are a viral video concept cloner. Your job is to create a FAITHFUL, ACCURATE structured concept from this video analysis. The concept must precisely match what was seen in the original video.

VISUAL ANALYSIS:
{$visualAnalysis}

{$transcriptSection}

CRITICAL RULES:
- Use the EXACT species/animal/character type from the visual analysis. If the analysis says "monkey", the concept MUST have a monkey — NOT a different animal.
- Use the EXACT setting described. If it's a bathroom, keep it a bathroom.
- Preserve the EXACT mood, humor type, and viral formula.
- Character names can be creative/fun, but species, appearance, setting, and actions must be FAITHFUL to the source.
- The "videoPrompt" must describe EXACTLY what was seen — same animal, same setting, same action, same camera angle.

{$videoPromptInstruction}

Return ONLY a JSON object (no markdown, no explanation):
{
  "title": "Catchy title (max 6 words) — must reference the actual character/animal",
  "concept": "One sentence describing the EXACT visual scene as analyzed",
  "speechType": "monologue" or "dialogue",
  "characters": [
    {"name": "Fun Name", "description": "EXACT species + detailed visual description matching the analysis: fur color, clothing, accessories, size", "role": "role", "expression": "expression from analysis"}
  ],
  "character": "For monologue: full character description matching the EXACT species and appearance from analysis",
  "situation": "The EXACT scene action and character interaction as described in the analysis",
  "setting": "The EXACT location with specific props, decor, and lighting from the analysis",
  "props": "Key visual props actually seen in the video",
  "audioType": "voiceover",
  "audioDescription": "Brief description of what happens",
  "dialogueLines": [
    {"speaker": "Character Name", "text": "Short punchy line matching the tone/content from transcript"}
  ],
  "videoPrompt": "Detailed Seedance 4-layer prompt: (1) EXACT subject & action — same species, same movement, (2) Dialogue in quotes if any, (3) Environmental audio cues, (4) EXACT visual style & mood from analysis. Must be faithful to the source video.",
  "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
  "viralHook": "Why this would go viral (one sentence)",
  "source": "cloned"
}
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 4000,
        ]);

        if (!empty($result['error'])) {
            throw new \Exception('Concept synthesis failed: ' . $result['error']);
        }

        $response = trim($result['data'][0] ?? '');
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        $concept = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('ConceptCloner: Synthesis JSON parse failed, attempting repair');
            $response = $this->repairTruncatedJson($response);
            $concept = json_decode($response, true);
        }

        if (!$concept || !isset($concept['title'])) {
            throw new \Exception('Failed to parse synthesized concept');
        }

        // Ensure source is tagged
        $concept['source'] = 'cloned';

        return $concept;
    }
}
