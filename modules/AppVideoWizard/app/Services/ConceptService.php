<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
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
}
