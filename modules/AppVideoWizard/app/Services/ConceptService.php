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
        $productionSubtype = $options['productionSubtype'] ?? 'viral';
        $chaosLevel = (int) ($options['chaosLevel'] ?? 50);
        $chaosDescription = trim($options['chaosDescription'] ?? '');

        $themeContext = !empty($theme)
            ? "The user wants ideas related to: \"{$theme}\". Incorporate this theme creatively."
            : "Generate completely original ideas with diverse themes.";

        $styleModifier = $this->getStylePromptModifier($productionSubtype);
        $chaosModifier = $this->getChaosPromptModifier($chaosLevel, $chaosDescription);
        if (!empty($options['chaosMode'])) {
            $chaosModifier .= "\n\n" . $this->getChaosModeSupercharger();
        }

        $templateId = $options['template'] ?? 'adaptive';

        if ($videoEngine === 'seedance') {
            $prompt = $this->buildSeedanceViralPrompt($themeContext, $count, $styleModifier, $chaosModifier, $templateId);
        } else {
            $prompt = $this->buildInfiniteTalkViralPrompt($themeContext, $count, $styleModifier, $chaosModifier);
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
    protected function buildSeedanceViralPrompt(string $themeContext, int $count, string $styleModifier = '', string $chaosModifier = '', string $templateId = 'adaptive'): string
    {
        $structureRules = $this->getTemplateStructureRules($templateId, 'generate');
        $technicalRules = $this->getSeedanceTechnicalRules();
        $templateExample = $this->getTemplateExample($templateId);

        return <<<PROMPT
You are a viral content specialist who creates massively shareable short-form video concepts.

{$themeContext}

{$styleModifier}

{$chaosModifier}

IMPORTANT: These ideas will be animated using Seedance — an AI model that generates
video + voice + sound effects ALL FROM A TEXT PROMPT. There is no separate audio recording.
The model auto-generates dialogue, environmental sounds, and sound effects from the prompt.
Do NOT include background music descriptions in the videoPrompt — only dialogue, sounds, and action.

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

Each idea MUST include a "videoPrompt" field — a Seedance 1.5 Pro optimized prompt.

VIDEO PROMPT RULES — THIS IS CRITICAL:

WORD COUNT: 150-180 words. This is the proven sweet spot for Seedance 1.5 Pro.
Under 140 words loses critical intensity. Over 200 words gets redundant. Aim for 160-175.

DO NOT describe character appearances (fur color, clothing, accessories) — that goes in "character" and "characters" fields.
The video prompt describes actions, reactions, sounds, voice, and SIZE/SCALE.
Convey emotion through BODY LANGUAGE, not facial expressions — see Face Consistency rule below.

{$structureRules}

{$technicalRules}

EXAMPLE — GOOD VIDEO PROMPT (~170 words):
{$templateExample}

Return ONLY a JSON array (no markdown, no explanation):
[
  {
    "title": "Catchy title (max 6 words)",
    "concept": "One sentence describing the full visual scene",
    "speechType": "monologue" or "dialogue",
    "characters": [
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "role", "expression": "expression description", "position": "spatial position: foreground/background, left/right/center, facing direction"}
    ],
    "character": "Combined description of ALL main visible characters with their spatial relationship for image generation",
    "imageStartState": "The CALM INITIAL state for the starting image — characters in positions BEFORE action begins. NO chaos, NO flying objects. Just characters in their starting poses.",
    "situation": "One concise sentence: what happens from start to finish focusing on KEY dramatic beats",
    "setting": "Detailed location with specific props, brand elements, decor, lighting",
    "props": "Key visual props in the scene",
    "audioType": "voiceover",
    "audioDescription": "Brief description of what happens (for metadata)",
    "dialogueLines": [
      {"speaker": "Character Name", "text": "Short punchy line"}
    ],
    "videoPrompt": "150-180 word Seedance-optimized prompt following the STRUCTURE and TECHNICAL RULES above. See EXAMPLE above for reference.",
    "cameraFixed": true,
    "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
    "viralHook": "Why this would go viral (one sentence)"
  }
]
PROMPT;
    }

    /**
     * Build viral ideas prompt for InfiniteTalk engine (lip-sync from custom voices).
     */
    protected function buildInfiniteTalkViralPrompt(string $themeContext, int $count, string $styleModifier = '', string $chaosModifier = ''): string
    {
        return <<<PROMPT
You are a viral content specialist who creates massively shareable short-form video concepts.

{$themeContext}

{$styleModifier}

{$chaosModifier}

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
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "employee/customer/boss/etc", "expression": "deadpan, slightly annoyed", "position": "spatial position: foreground/background, left/right/center, facing direction"},
      {"name": "Character 2 Name", "description": "detailed visual description", "role": "role", "expression": "expression description", "position": "spatial position"}
    ],
    "character": "Combined description of ALL main visible characters with their spatial relationship for image generation",
    "imageStartState": "The CALM INITIAL state for the starting image — characters in positions BEFORE action begins. NO chaos, NO flying objects. Just characters in their starting poses with neutral expressions.",
    "situation": "One concise sentence: what happens from start to finish focusing on KEY dramatic beats",
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

    /**
     * Get a style-specific prompt modifier based on the selected production subtype.
     * This shapes the AI's idea generation to match the chosen content style.
     */
    protected function getStylePromptModifier(string $subtype): string
    {
        return match ($subtype) {
            'viral' => <<<'STYLE'
CONTENT STYLE — VIRAL/TRENDING:
Focus on maximum shareability and instant visual hooks. Lean into animal chaos, physical comedy,
and visual absurdity. Single continuous shots with dramatic reactions, objects flying, and pure
slapstick energy. Every idea should make someone immediately want to tag a friend.
STYLE,
            'meme-comedy' => <<<'STYLE'
CONTENT STYLE — MEME/COMEDY:
Lean into specific meme formats and internet comedy tropes. Think "when your..." scenarios,
animals with deadpan delivery, absurd workplace humor, and quotable dialogue lines. The comedy
should come from relatable situations twisted into absurdity. Prioritize punchy one-liners,
sarcastic comebacks, and reaction-worthy moments over pure physical comedy.
STYLE,
            'educational-short' => <<<'STYLE'
CONTENT STYLE — QUICK EXPLAINER:
Frame ideas as bite-sized educational content. A character or narrator explains a fascinating
concept, science fact, or "did you know?" tidbit. Keep it visual — show don't tell. The hook
should be a surprising fact or counter-intuitive truth that makes viewers watch to the end.
STYLE,
            'story-short' => <<<'STYLE'
CONTENT STYLE — STORY/NARRATIVE:
Create mini-narratives with emotional arcs. Each idea needs a setup, tension, and payoff —
a twist ending, a heartwarming reveal, or a "wait for it" moment. Focus on character-driven
scenarios that make viewers feel something. The hook is curiosity: "what happens next?"
STYLE,
            'product' => <<<'STYLE'
CONTENT STYLE — PRODUCT SHOWCASE:
Frame ideas around satisfying product reveals, unboxing moments, or "watch this" demonstrations.
Dynamic close-ups, before/after transformations, and aspirational lifestyle context. Every concept
should make the viewer think "I need that." Emphasize visual satisfaction and desire.
STYLE,
            'lifestyle' => <<<'STYLE'
CONTENT STYLE — LIFESTYLE:
Create aspirational, aesthetically pleasing content. Morning routines, cozy moments, day-in-the-life
montages with warm color grading. Focus on visual beauty, satisfying sequences, and "living my
best life" energy. The mood should be calming, inspiring, or aesthetically satisfying.
STYLE,
            default => '',
        };
    }

    /**
     * Build a chaos/intensity modifier for the idea generation prompt.
     * Translates the user's chaos slider (0-100) + optional description into prompt instructions.
     *
     * CRITICAL RULE: All chaos must be CHARACTER-DRIVEN. The main character physically
     * performs actions (slams, throws, kicks) that CAUSE objects to fly and things to break.
     * NEVER write passive chaos ("spaghetti flying") — always write CAUSED chaos
     * ("the cat SLAMS the table, SENDING spaghetti flying").
     */
    protected function getChaosPromptModifier(int $chaosLevel, string $chaosDescription = ''): string
    {
        // Chaos levels aligned with official Seedance degree words:
        // quickly, violently, with large amplitude, at high frequency, powerfully, wildly, crazy,
        // fast, intense, strong, greatly. "crazy" is the magic word.
        $escalation = match (true) {
            $chaosLevel <= 20 => [
                'label' => 'CALM & GENTLE',
                'degreeWords' => 'Use NO degree words. Actions are slow, gentle, small.',
                'instruction' => 'Keep scenes CALM and wholesome. The character performs gentle, deliberate actions — nudging, tapping, tilting. No flying objects, no destruction. Humor from subtle expressions and small physical gestures.',
                'actionDensity' => '1-2 action beats total. Each action is slow and deliberate with a small result.',
                'chainReactions' => '0-1 chain reactions. Maybe one object tips over gently.',
                'example' => '"the cat slowly nudges the salt shaker with one paw, it tips over and spills a small pile of salt on the counter"',
            ],
            $chaosLevel <= 45 => [
                'label' => 'MODERATE ENERGY',
                'degreeWords' => 'Use 1-2 degree words per beat: "quickly", "fast", "strong".',
                'instruction' => 'The character gets physical — grabbing, tossing, shoving. Every object moves BECAUSE the character touched it. Actions cause small chain reactions.',
                'actionDensity' => '3-4 action beats. Each action has a visible consequence.',
                'chainReactions' => '1-2 chain reactions. Objects fall, liquids spill.',
                'example' => '"the cat quickly grabs the cutting board and flips it fast, sending vegetables scattering across the counter"',
            ],
            $chaosLevel <= 65 => [
                'label' => 'HIGH ENERGY',
                'degreeWords' => 'Use 2-3 degree words per beat: "powerfully", "violently", "fast", "wildly", "crazy".',
                'instruction' => 'The character is the ENGINE of chaos. They slam, throw, smash — every impact causes objects to fly and break. Write the character\'s action FIRST, then the destruction it causes.',
                'actionDensity' => '5-6 action beats. Rapid-fire, each bigger than the last.',
                'chainReactions' => '2-3 chain reactions. Objects hit other objects that break more things.',
                'example' => '"the cat\'s front paws slam into the counter powerfully, its body lunges forward fast and violently at the man"',
            ],
            $chaosLevel <= 85 => [
                'label' => 'PEAK CHAOS',
                'degreeWords' => 'Use 3+ degree words per beat. COMBINE them: "fast and violently", "powerfully with large amplitude", "wildly at high frequency". Use "crazy" liberally.',
                'instruction' => 'The character is a WRECKING BALL. Every movement destroys something. Every limb does something specific and devastating. EACH sentence starts with what the character DOES, then the chain of destruction.',
                'actionDensity' => '6-8 action beats. Nonstop, overlapping, simultaneous destruction.',
                'chainReactions' => '3-4 chain reactions. Body hits object → object hits another → both crash loudly.',
                'example' => '"the cat launches with crazy explosive force onto the man\'s torso, front claws raking downward powerfully with large amplitude while rear legs thrash wildly at high frequency against his midsection"',
            ],
            default => [
                'label' => 'MAXIMUM OVERDRIVE',
                'degreeWords' => 'EVERY action gets MULTIPLE combined degree words. "crazy" on almost everything. "wildly at high frequency", "powerfully with large amplitude", "fast and violently". Go maximum.',
                'instruction' => 'The character is a FORCE OF NATURE. Every limb destroys something simultaneously. The CHAIN REACTION of their actions levels the entire scene. Write rapid sequences of simultaneous destruction — all traced back to the character\'s body.',
                'actionDensity' => '8+ action beats. All limbs active simultaneously. No pauses between impacts.',
                'chainReactions' => '4+ chain reactions. Each destruction triggers the next. The whole environment cascades.',
                'example' => '"the cat\'s body smashes with crazy intensity into the display, toppling everything with large amplitude, rigid tail whips wildly and violently, sending items clattering fast across the counter as hind legs kick powerfully at high frequency"',
            ],
        };

        $parts = [];
        $parts[] = "CHAOS INTENSITY: {$escalation['label']} ({$chaosLevel}/100)";
        $parts[] = '';
        $parts[] = 'CHARACTER-DRIVEN CHAOS RULE:';
        $parts[] = 'ALL chaos MUST be caused by the character\'s PHYSICAL ACTIONS with specific body parts.';
        $parts[] = 'NEVER write passive chaos like "spaghetti flying" — ALWAYS write "the cat smashes the bowl powerfully, sending spaghetti flying violently."';
        $parts[] = 'Every object that moves MUST be hit by a specific body part first.';
        $parts[] = '';
        $parts[] = "DEGREE WORDS INTENSITY: {$escalation['degreeWords']}";
        $parts[] = "ACTION DENSITY: {$escalation['actionDensity']}";
        $parts[] = "CHAIN REACTIONS: {$escalation['chainReactions']}";
        $parts[] = '';
        $parts[] = $escalation['instruction'];
        $parts[] = "EXAMPLE AT THIS LEVEL: {$escalation['example']}";

        if (!empty($chaosDescription)) {
            $parts[] = '';
            $parts[] = "USER'S CHAOS DIRECTION: \"{$chaosDescription}\"";
            $parts[] = 'Incorporate this specific chaos vision. Shape scenarios around this direction.';
        }

        return implode("\n", $parts);
    }

    /**
     * Chaos Mode structural override — fewer mega-beats, front-loaded explosion.
     * Appended to chaos modifier when Chaos Mode toggle is active.
     */
    protected function getChaosModeSupercharger(): string
    {
        return <<<'CHAOS'
=== CHAOS MODE ACTIVE — OVERRIDE STRUCTURE ===

IMPORTANT: Chaos Mode overrides the normal action structure. Follow THESE rules instead:

FEWER MEGA-BEATS (3-4 maximum):
Instead of 6-8 rapid micro-actions, write ONLY 3-4 MASSIVE action beats.
Each beat gets 2-3 full sentences of detailed physical description.
More detail per beat = more motion energy Seedance allocates to each movement.

FRONT-LOAD THE EXPLOSION:
The BIGGEST, most devastating action happens IMMEDIATELY after "Instantly".
Do NOT build up gradually. The first physical strike is the most explosive one.
Structure: TRIGGER → INSTANT MEGA-STRIKE → escalation → peak destruction

EXAMPLE — CHAOS MODE PROMPT:
"The man says 'This is the worst pizza I've ever had!' Instantly the cat
launches with crazy explosive force from behind the counter directly at the
man's face, front claws extended wildly with large amplitude, slamming into
his chest powerfully, sending him staggering violently backwards. The man
crashes fast into a shelf of glass bottles, his arms flailing wildly as
bottles shatter and liquid sprays with large amplitude in every direction.
The cat's hind legs kick at high frequency against the man's torso, shredding
fabric fast while the cat screams a crazy deafening yowl, mouth gaping wide.
The man collapses powerfully onto the floor, pulling an entire display rack
down with him, plates and cups crashing violently around him as the cat
stands on his chest screaming wildly. Continuous crazy aggressive cat
screaming throughout. Cinematic, photorealistic."

KEY DIFFERENCES FROM NORMAL MODE:
- 4 mega-beats instead of 8 micro-beats
- Each beat has 2-3 sentences of detailed motion
- First action (the launch) is the BIGGEST
- "crazy" appears 3+ times
- Every action has 2+ combined degree words
CHAOS;
    }

    /**
     * Get available video prompt templates.
     * Templates define the creative structure/formula for video prompts.
     */
    public static function getVideoPromptTemplates(): array
    {
        return [
            'adaptive' => [
                'id' => 'adaptive',
                'name' => 'Adaptive',
                'description' => 'Matches the source video\'s style faithfully',
                'icon' => 'fa-solid fa-wand-magic-sparkles',
            ],
            'animal-chaos' => [
                'id' => 'animal-chaos',
                'name' => 'Animal Chaos Attack',
                'description' => 'Dialogue trigger → animal explosion → destruction',
                'icon' => 'fa-solid fa-burst',
            ],
        ];
    }

    /**
     * Get universal Seedance technical rules shared by ALL templates.
     * Covers degree words, explicit motion, character sounds, physical action, style anchor, banned items.
     */
    protected function getSeedanceTechnicalRules(): string
    {
        return <<<'RULES'
SEEDANCE OFFICIAL DEGREE WORDS — USE ONLY THESE (mandatory on every action):
Seedance responds to specific intensity words, NOT literary English adverbs.
The OFFICIAL degree words are: quickly, violently, with large amplitude, at high frequency,
powerfully, wildly, crazy, fast, intense, strong, greatly.
"crazy" is the MAGIC WORD — use it liberally: "crazy yowl", "crazy intensity", "crazy explosive force".
COMBINE degree words: "fast and violently", "powerfully with large amplitude", "wildly at high frequency".
NEVER use literary adverbs: "ferociously", "furiously", "aggressively", "frantically", "explosively",
"savagely", "relentlessly" — Seedance does NOT interpret these. Only the official list works.

EXPLICIT MOTION — Seedance CANNOT infer motion:
Every movement must be EXPLICITLY described. The model will NOT animate what you don't write.
WRONG: "the cat attacks" (model doesn't know HOW it attacks)
RIGHT: "the cat's front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge"
If a body part should move, DESCRIBE the motion. If an object should fly, DESCRIBE the trajectory.

CHARACTER SOUNDS — CONTINUOUS:
Animal/character sounds must appear in EVERY action beat. Use varied words:
screeching, yowling, hissing, shrieking, screaming, growling, wailing.
Describe sounds physically: "mouth gaping wide showing sharp fangs", "ears flattened".
End with "continuous crazy aggressive [animal/character] sounds throughout."

PHYSICAL ACTION — SPECIFIC BODY PARTS + AMPLITUDE:
GOOD: "front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge"
GOOD: "hind legs kick at high frequency, smashing cup fragments and spraying dark liquid violently"
GOOD: "rigid tail whips violently, snapping against a metal utensil holder, sending spoons clattering"
BAD: "the cat attacks him aggressively" (too vague — which body part? what motion? what gets hit?)

ENVIRONMENTAL DESTRUCTION — CREATIVE CHAIN REACTIONS:
Don't just say "things crash." Invent specific destruction chains:
Every object must be HIT by a body part before it breaks. More destruction = better video.

FACE & IDENTITY PRESERVATION — Critical for character consistency:
- Add this constraint phrase near the START of the prompt:
  "Maintain face and clothing consistency, no distortion, high detail."
- Add this constraint phrase near character introductions:
  "Character face stable without deformation, normal human structure, natural and smooth movements."
- Include 3-6 identity anchors per character: hair color/style, skin tone, distinctive accessories.
  Example: "dark curly-haired woman with warm brown skin and gold hoop earrings"
- NEVER describe face structure changes: "face shifts", "expression changes to", "features contort".
- Instead, convey emotion through BODY LANGUAGE and ACTIONS:
  WRONG: "her face shows shock, eyes widening, mouth dropping open"
  RIGHT: "she jerks back sharply, hands flying up defensively"
  WRONG: "his expression shifts to anger, brow furrowing, jaw clenching"
  RIGHT: "he leans forward aggressively, fist slamming the table"
- Seedance preserves faces best when the prompt focuses on BODY MOTION, not facial micro-expressions.
- You may mention mouth opening for SPEAKING or SOUND PRODUCTION (e.g. "mouth opens as she yells", "cat's mouth gapes in a crazy yowl") — these are actions, not appearance descriptions.
- Keep the FACE STABLE by letting the body do the emotional acting.
- AVOID: rapid lighting changes, complex multi-person fighting/hugging, exaggerated descriptions.
- PREFER: composition/framing first, then character details, then motion, then mood/style.

STYLE ANCHOR — ALWAYS end with: "Cinematic, photorealistic."

SCALE & SIZE — Seedance renders characters at DEFAULT size unless told otherwise:
- If characters are miniaturized/tiny/shrunken → you MUST say so: "tiny miniature cat barely ankle-height"
- If characters are enlarged/giant → you MUST say so: "enormous cat towering over the table"
- If there's a specific COUNT of characters → state it: "a line of twelve tiny cats" not just "cats"
- Without explicit size cues, Seedance renders normal-sized characters — losing the visual comedy.

BANNED:
- No semicolons
- No camera movement descriptions (camera is controlled separately by the API)
- No appearance/clothing descriptions (fur color, clothing, accessories — only what they DO and how BIG they are)
- No direct facial expression descriptions ("expression shifts to", "face shows", "eyes widen", "brow furrows") — convey emotion through body language instead
- No passive voice — only active verbs with intensity qualifiers
- No weak/generic verbs: "goes", "moves", "does", "gets", "starts", "begins"
- No background music, soundtrack, or score descriptions (e.g. "upbeat music plays", "dramatic orchestral score", "rhythmic beat in the background"). Seedance auto-generates audio from the prompt — any mention of music will make Seedance play background music. Only describe character sounds, dialogue, and environmental sound effects caused by physical actions
RULES;
    }

    /**
     * Get template-specific structure rules.
     *
     * @param string $templateId Template ID ('adaptive', 'animal-chaos')
     * @param string $context 'clone' for video cloning, 'generate' for AI idea generation
     */
    protected function getTemplateStructureRules(string $templateId, string $context = 'clone'): string
    {
        // User-created template — use generic "follow the example" rules
        if (str_starts_with($templateId, 'user-')) {
            return <<<'RULES'
=== USER TEMPLATE MODE — FOLLOW THE REFERENCE EXAMPLE ===

Below is a reference videoPrompt that defines the target style.
Your job is to generate a NEW videoPrompt that follows the SAME structure,
energy, pacing, and creative pattern as the reference — but adapted to
the new concept/video being analyzed.

Match the reference's: action density, comedic style, escalation pattern,
character dynamics, and emotional register.
Do NOT copy the reference literally — capture its FORMULA and apply it.

WORD COUNT: 150-180 words. Aim for 160-175.
DO NOT describe character appearances — only actions, reactions, sounds, and voice.
RULES;
        }

        $templates = [
            'adaptive' => [
                'clone' => <<<'RULES'
=== ADAPTIVE MODE — MATCH THE SOURCE VIDEO ===

Your job is to FAITHFULLY recreate the energy, pacing, and structure of the reference video.
Do NOT impose a rigid formula. Analyze what makes the reference work and replicate its structure.

STRUCTURE:
- If the reference has a trigger-reaction pattern → use trigger-reaction
- If the reference builds slowly → use a slow build
- If the reference has constant rhythmic motion → keep constant motion
- If the reference is calm/gentle → keep it calm/gentle
- If the reference has multiple characters interacting → preserve those dynamics

ENERGY MATCHING:
- Match the EXACT energy level of the reference. Don't artificially escalate.
- Gentle interaction → gentle prompt. Chaotic action → chaotic prompt.
- The intensity qualifiers (degree words) should match what was SEEN, not be maximized.

FAITHFULNESS:
- Describe actions that MATCH what was seen in the reference video.
- Same type of movements, same emotional register, same comedic style.
- You can optimize phrasing for Seedance, but stay true to the source's vibe.
- If the video shows a marching band → write a marching band prompt.
- If the video shows cooking → write a cooking prompt.
- Do NOT transform everything into attack/destruction scenes.

SCALE, QUANTITY & OBJECT INTERACTIONS — PRESERVE WHAT MAKES IT SPECIAL:
- If characters are MINIATURIZED (tiny cats, shrunken animals) → the videoPrompt MUST describe them as tiny/miniature with size reference (e.g. "barely reaching her ankle").
- If characters are ENLARGED → describe the exaggerated scale.
- If there's a LARGE GROUP (a line of 12 cats, a swarm of hamsters) → specify the exact count and formation.
- If characters are USING objects (playing instruments, cooking, wielding tools) → describe the ACTIVE USE, not just holding. "Blowing into trumpets producing brass music" not "holding trumpets."
- Scale, quantity, and character-object interactions are often the CORE of the visual comedy — losing any of them ruins the concept.

WORD COUNT: 150-180 words. Aim for 160-175.
DO NOT describe character appearances (fur color, clothing) — only actions, reactions, sounds, voice, and SIZE/SCALE.
RULES,
                'generate' => <<<'RULES'
=== ADAPTIVE MODE — CHOOSE THE BEST STRUCTURE ===

Choose the structure that best fits each concept's energy and comedy style.
Not every concept needs explosive chaos — match the structure to the content.

STRUCTURE FLEXIBILITY:
- Gentle/cute concepts → gentle, deliberate movements with small actions
- Physical comedy → exaggerated movements, slapstick reactions
- Chaotic concepts → rapid escalation, chain reactions, destruction
- Rhythmic/musical → steady motion patterns, synchronized movements
- Dramatic → slow builds, tension, then payoff moment

ENERGY MATCHING:
- The intensity qualifiers (degree words) should match the concept's natural energy.
- A calm concept uses fewer/softer degree words. A wild concept uses many combined.
- Don't force maximum chaos on every concept — let the idea guide the intensity.

DIALOGUE/ACTION BALANCE:
- Some concepts work better with a trigger line then action. Others need no dialogue.
- Match the dialogue to what makes sense for the scenario.
RULES,
            ],
            'animal-chaos' => [
                'clone' => <<<'RULES'
=== ANIMAL CHAOS ATTACK — DIALOGUE TRIGGER → UNBROKEN CHAOS ===

THE #1 RULE: ONE DIALOGUE LINE, THEN PURE UNBROKEN CHAOS
The human speaks ONCE at the start (1 line, max 20 words). After that, ZERO dialogue.
No "he exclaims", no "he yells", no more human speech. The rest is ALL physical action,
animal sounds, and environmental destruction. The dialogue exists ONLY to trigger the chaos.

STRUCTURE — DIALOGUE TRIGGER → UNBROKEN CHAOS:
1. TRIGGER (1 sentence): The human says ONE short angry line.
   'The man leans forward and says "..." while gesturing angrily.'
2. INSTANT REACTION: "Instantly" — the animal explodes. First sound + first physical strike.
3. CONTINUOUS ESCALATION: Rapid-fire action beats with NO PAUSES for dialogue or narrative.
   Every beat: physical action + animal sound + environmental consequence happening simultaneously.
4. PEAK LAUNCH: Animal launches onto human with maximum force — clawing, kicking, shredding.
5. ENVIRONMENTAL CHAIN REACTIONS: Body smashes into objects → objects topple → things crash/clatter.
6. CLOSING: "continuous crazy aggressive [animal] screaming throughout. Cinematic, photorealistic."

AGGRESSOR DOMINANCE — THE ANIMAL CONTROLS 100% OF THE ACTION:
- The animal is the ONLY one driving action. It attacks, lunges, claws, launches, smashes.
- The human ONLY reacts defensively: jerks back, gasps, stumbles, cries out, hands thrown up.
- The human NEVER grabs, holds, controls, or restrains the animal.
- The human NEVER speaks after the opening trigger line. Only gasps, cries out, recoils.

ENVIRONMENTAL DESTRUCTION — minimum 3 chain reactions.
No slow builds — chaos is INSTANT after the trigger.
No multiple dialogue lines — ONE line triggers the chaos.
No human grabbing/controlling/restraining the animal — animal dominates.
No narrative back-and-forth — pure continuous chaos, no pauses.
RULES,
                'generate' => null, // Falls back to clone rules
            ],
        ];

        $templateRules = $templates[$templateId] ?? $templates['adaptive'];
        return $templateRules[$context] ?? $templateRules['clone'];
    }

    /**
     * Get an example video prompt for a given template.
     */
    protected function getTemplateExample(string $templateId): string
    {
        // User-created template — load example from DB
        if (str_starts_with($templateId, 'user-')) {
            $userTemplateId = (int) substr($templateId, 5);
            $userTemplate = \Modules\AppVideoWizard\Models\VwUserTemplate::find($userTemplateId);
            if ($userTemplate) {
                return $userTemplate->video_prompt;
            }
            // Fallback to adaptive if template not found
        }

        $examples = [
            'adaptive' => <<<'EXAMPLE'
"The cat stands on the kitchen counter, head bobbing quickly to the rhythm, front paws stepping in precise marching formation with small amplitude. Its tail sways powerfully left and right like a metronome, whiskers twitching fast with each beat. The cat's mouth opens wide letting out a sustained crazy meow in time with the tempo, ears perked forward intensely. Its hind legs stamp the counter surface at high frequency, creating a steady rhythmic tapping. The cat's whole body rocks wildly side to side, fur rippling with the motion as nearby utensils vibrate and rattle against each other. A wooden spoon slides off the counter edge and clatters to the floor. The cat pauses, looks directly at the camera with wide intense eyes, then resumes marching with even greater amplitude, front paws lifting high and stomping down powerfully. Continuous crazy enthusiastic cat vocalizing throughout. Cinematic, photorealistic."
EXAMPLE,
            'animal-chaos' => <<<'EXAMPLE'
"The man leans forward and says 'This coffee is terrible, what did you put in this? I want my money back!' while gesturing angrily at the cup. Instantly the cat shrieks a deafening crazy yowl, ears flattened, mouth gaping wide showing sharp fangs. Its front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge at the man. Razor claws scrape wildly across the man's jacket, shredding fabric with an audible rip as the man jerks his head back gasping. Simultaneously the cat's hind legs kick at high frequency, smashing cup fragments and spraying dark liquid violently across the man's chest. The man cries out, body recoiling sharply, hands thrown up defensively as he stumbles backwards fast. The cat launches itself with crazy explosive force onto the man's torso, front claws raking downward powerfully with large amplitude while rear legs thrash wildly and relentlessly against his midsection. Its body smashes into a nearby display of packaged goods, toppling the entire stack which crashes loudly onto the floor. The cat's rigid tail whips violently, snapping against a metal utensil holder, sending spoons and forks clattering loudly across the counter. Continuous crazy aggressive cat screaming throughout. Cinematic, photorealistic."
EXAMPLE,
        ];

        return $examples[$templateId] ?? $examples['adaptive'];
    }

    // ========================================================================
    // VIDEO CONCEPT CLONER — Analyze uploaded video and extract concept
    // ========================================================================

    /**
     * Main pipeline: Analyze an uploaded video and produce a structured concept.
     *
     * Stage 1: Upload video to Gemini File API + analyze with Gemini 2.5 Flash (native video understanding)
     * Stage 2: Extract audio + transcribe with Whisper
     * Stage 3: AI synthesis into viral idea format
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
        $mimeType = $options['mimeType'] ?? 'video/mp4';
        $chaosMode = !empty($options['chaosMode']);

        $geminiService = app(\App\Services\GeminiService::class);

        // Stage 1: Upload video to Gemini File API + visual analysis
        Log::info('ConceptCloner: Stage 1 — Uploading video to Gemini File API', [
            'fileSize' => filesize($videoPath),
            'mimeType' => $mimeType,
        ]);

        $upload = $geminiService->uploadFileToGemini($videoPath, $mimeType, 'concept_clone_' . time());
        if (!$upload['success']) {
            throw new \Exception('Failed to upload video to Gemini: ' . ($upload['error'] ?? 'unknown'));
        }

        Log::info('ConceptCloner: Video uploaded, analyzing with Gemini 2.5 Flash', [
            'fileUri' => $upload['fileUri'],
        ]);

        $analysisResult = $geminiService->analyzeVideoWithPrompt(
            $upload['fileUri'],
            $this->buildVideoAnalysisPrompt(),
            ['mimeType' => $upload['mimeType'] ?? $mimeType]
        );

        if (!$analysisResult['success'] || empty($analysisResult['text'])) {
            throw new \Exception('Gemini video analysis failed: ' . ($analysisResult['error'] ?? 'empty response'));
        }

        $visualAnalysis = $analysisResult['text'];

        Log::info('ConceptCloner: Stage 1 complete — Visual analysis received', [
            'textLength' => strlen($visualAnalysis),
            'model' => $analysisResult['model'] ?? 'gemini-2.5-flash',
            'analysisPreview' => mb_substr($visualAnalysis, 0, 500),
        ]);

        // Stage 2: Extract audio + transcribe (if audio exists)
        Log::info('ConceptCloner: Stage 2 — Extracting and transcribing audio');
        $transcript = $this->extractAndTranscribeAudio($videoPath);
        Log::info('ConceptCloner: Stage 2 complete', ['hasTranscript' => !empty($transcript)]);

        // Stage 3: Synthesize into structured concept
        Log::info('ConceptCloner: Stage 3 — Synthesizing concept');
        $templateId = $options['template'] ?? 'adaptive';
        $concept = $this->synthesizeConcept($visualAnalysis, $transcript, $aiModelTier, $teamId, $videoEngine, $chaosMode, $templateId);
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
        $frameSizes = [];
        foreach ($framePaths as $i => $framePath) {
            $frameData = file_get_contents($framePath);
            $frameSizes[] = strlen($frameData);
            $base64 = base64_encode($frameData);
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/jpeg;base64,' . $base64,
                ],
            ];

            // Save first frame for debug verification
            if ($i === 0) {
                $debugDir = storage_path('app/public/debug');
                if (!is_dir($debugDir)) @mkdir($debugDir, 0755, true);
                @copy($framePath, $debugDir . '/concept_debug_frame.jpg');
            }
        }

        Log::info('ConceptCloner: Sending frames to Grok vision', [
            'frameCount' => count($framePaths),
            'frameSizes' => $frameSizes,
            'totalBase64Bytes' => array_sum(array_map(fn($s) => (int) ceil($s * 4 / 3), $frameSizes)),
        ]);

        // Add the analysis prompt
        $content[] = [
            'type' => 'text',
            'text' => $this->buildVideoAnalysisPrompt(),
        ];

        $messages = [[
            'role' => 'user',
            'content' => $content,
        ]];

        // IMPORTANT: Must use the dedicated vision model.
        // 'grok-4-fast' and 'grok-4-1-fast-non-reasoning' both hallucinate (ignore images).
        // 'grok-2-vision-1212' is xAI's dedicated vision model that actually processes images.
        $result = $grokService->generateVision($messages, [
            'model' => 'grok-2-vision-1212',
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
     * Build the visual analysis prompt for Gemini native video analysis.
     */
    protected function buildVideoAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing a short-form video (TikTok/Reels/Shorts). You can see the FULL video with all its temporal flow, motion, and audio cues. Analyze it with EXTREME PRECISION.

CRITICAL INSTRUCTION: You MUST identify every character/creature/animal with 100% accuracy. If you see a monkey, say MONKEY — not "primate" or "creature." If you see a golden retriever, say GOLDEN RETRIEVER — not just "dog." Be as specific as possible about breed, species, and subspecies. NEVER guess or generalize. Describe EXACTLY what you see.

1. CHARACTERS (be EXACT):
   - EXACT species — e.g., "capuchin monkey", "tabby cat", "golden retriever puppy", "adult human male." Do NOT generalize.
   - Fur/skin color, patterns, distinguishing marks
   - Clothing, accessories, colors (be specific: "red baseball cap", not "hat")
   - Facial expression and body language as they CHANGE throughout the video
   - Role: protagonist, supporting, background
   - SPATIAL POSITION: Where is each character relative to others? Who is in the foreground/background? Who faces whom?

   SIZE & SCALE — THIS IS CRITICAL, DO NOT SKIP:
   - What is each character's size RELATIVE to the human/environment? Compare to real-world expectations.
   - Are characters their NORMAL real-world size, or are they digitally MINIATURIZED, ENLARGED, or otherwise scaled?
   - If animals appear SMALLER or LARGER than their real species normally would be, SAY SO EXPLICITLY.
     Example: "The cats are digitally miniaturized — roughly ankle-height on the woman, about 1/5 her height. Normal adult cats would reach her knee on hind legs."
     Example: "The hamster is enlarged to the size of a dog, towering over the coffee table."
   - Compare character height to nearby objects: doorways, furniture, other characters' body parts (ankle, knee, hip, shoulder).
   - COUNT characters precisely: "exactly 3 cats" or "a line of approximately 12-15 cats" — do NOT say "some cats" or "several cats."
   - If many identical/similar characters form a GROUP, describe the group size, formation pattern, and whether they move in unison or independently.

   CHARACTER-OBJECT INTERACTIONS — WHAT ARE THEY DOING WITH WHAT THEY HOLD:
   - If a character is HOLDING an object (instrument, tool, weapon, food, phone), describe HOW they are USING it — not just that they hold it.
     WRONG: "the cat is holding a trumpet" (passive — what is the cat DOING with it?)
     RIGHT: "the cat is blowing into a miniature trumpet, cheeks puffed, producing brass music"
   - For MUSICAL INSTRUMENTS: Are characters PLAYING them? Describe the physical playing action (blowing, strumming, drumming, bowing). Is the music in the audio COMING FROM their playing?
   - For TOOLS/WEAPONS: Are they swinging, pointing, using them? Describe the action.
   - For FOOD/DRINKS: Are they eating, drinking, spilling? Describe the interaction.
   - The FUNCTIONAL USE of objects is as important as the objects themselves. A cat holding a trumpet that's actively playing it is completely different from a cat just carrying a trumpet.

2. SETTING & ENVIRONMENT:
   - Exact location (bathroom, kitchen counter, living room couch, outdoor garden, etc.)
   - Every visible prop and object (towel, sink, plate, phone, etc.)
   - Lighting type and direction
   - Background details, wall color, floor type, decor
   - Any text, signs, or brand names visible

3. ACTION TIMELINE — THIS IS THE MOST IMPORTANT SECTION:
   You can see the FULL video motion. Describe the COMPLETE temporal progression second by second.
   - 0-2 seconds: What is the initial state? What are the characters doing?
   - 2-5 seconds: What happens next? Any change in behavior?
   - 5-8 seconds: Any escalation? New actions? Turning point?
   - 8-12 seconds: Climax? Peak action?
   - 12+ seconds: Resolution or punchline?
   - CRITICAL: Most viral videos have a 2-3 phase arc:
     Phase 1 (first 3-5 seconds): Setup — calm interaction, establishing shot
     Phase 2 (seconds 5-10): Escalation — character starts doing something unexpected (attacking, throwing, running, chasing, etc.)
     Phase 3 (seconds 10-15): Climax/punchline — peak chaos, surprise reaction, or payoff
   - You MUST identify ALL phases. Do NOT flatten the video into one static description.
   - Describe EVERY physical action: pushing, throwing, knocking things over, swatting, chasing, jumping, etc.
   - Note the EXACT SECOND when each new action begins
   - Describe what EACH character does independently at each phase
   - ACTION INTENSITY RATING: Rate the peak physical intensity of the scene:
     * CALM: characters mostly standing still, gentle movements, talking
     * MODERATE: some physical movement, light gestures, minor comedy
     * INTENSE: aggressive movements, throwing, pushing, fast actions
     * EXTREME/WILD: chaotic, things breaking/flying, characters leaping, total pandemonium
   - What SPECIFIC DESTRUCTIVE or WILD actions happen? (breaking objects, knocking things over, throwing items, jumping on things, pushing things off surfaces, crashing into things)
   - Be SPECIFIC about the physical destruction: what objects get knocked over, thrown, broken, pushed off surfaces?

3b. AUDIO & SOUND ANALYSIS (you can hear the actual audio):
   - What sounds do you hear? List them: human speech, animal sounds, background noise, music
   - Is there a VOICEOVER/NARRATION? (a human voice talking OVER the video, not from a character on screen)
   - Which sounds come FROM characters on screen vs. dubbed/added audio?
   - CRITICAL: If an animal's mouth is open, what sound does it ACTUALLY make? (meowing, hissing, barking — NOT human speech)
   - Is there background music or sound effects?
   - SOUND SOURCE ATTRIBUTION: If music is playing AND characters are holding/playing instruments, is the music PRODUCED BY the characters or is it a separate soundtrack? This matters enormously — a cat PLAYING a trumpet that produces audible music is the core action, not background decoration.
   - Describe any sounds that are CAUSED BY character actions (instrument playing, object impacts, footsteps, clapping).
   - Which character is the MAIN FOCUS of the scene?
   - What is the emotional state? (angry, scared, confused, playful, aggressive)
   - Describe the timing of sounds: when does speech start/stop, when do animal sounds occur?

4. CAMERA & VISUAL STYLE:
   - Camera angle (eye-level, low-angle, high-angle, overhead)
   - Camera movement throughout the video (static, slow pan, quick zoom, handheld shake, tracking)
   - Is the camera FIXED (tripod/phone on surface) or MOVING? This is critical.
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
    protected function synthesizeConcept(string $visualAnalysis, ?string $transcript, string $aiModelTier, int $teamId, string $videoEngine = 'seedance', bool $chaosMode = false, string $templateId = 'adaptive'): array
    {
        $transcriptSection = $transcript
            ? "AUDIO TRANSCRIPT:\n\"{$transcript}\"\n\nCRITICAL AUDIO ANALYSIS:\n- This transcript was captured from the video's audio track.\n- On TikTok/Reels, human dialogue over animal videos is almost ALWAYS a dubbed voiceover/narration — the animal is NOT actually speaking.\n- If the visual analysis shows an ANIMAL with mouth open, the animal is making ANIMAL SOUNDS (meowing, barking, hissing, screaming) — NOT speaking human words.\n- The transcript above is likely a VOICEOVER narration added for comedy, NOT the animal's actual voice.\n- IMPORTANT FOR VOICEOVER TEXT: Strip out ALL animal sound words (meow, woof, bark, hiss, growl, etc.) from the voiceover narration. Only include the HUMAN SPEECH parts. If the transcript is 'This is not what I ordered! Meow meow meow! I asked for chicken!' the voiceover should be 'This is not what I ordered! I asked for chicken!' — no animal sounds in the voiceover.\n- The voiceover narration must contain ONLY clean human speech. Animal sounds happen VISUALLY in the scene, not in the voiceover audio."
            : "AUDIO: No speech detected in video. Assume visual comedy / silent humor with environmental sounds only.";

        $videoPromptInstruction = $videoEngine === 'seedance'
            ? 'Also generate a "videoPrompt" field — see SEEDANCE VIDEO PROMPT RULES at the end of this prompt.'
            : 'Do NOT generate a "videoPrompt" field.';

        // Get template rules for Seedance video prompt
        $structureRules = $this->getTemplateStructureRules($templateId, 'clone');
        $technicalRules = $this->getSeedanceTechnicalRules();
        $templateExample = $this->getTemplateExample($templateId);

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
- The "videoPrompt" must describe EXACTLY what was seen — same animal, same setting, same action.
- ANIMAL SOUNDS ARE REAL: Animals make ANIMAL SOUNDS (meowing, hissing, barking) — NEVER human words. Describe sounds as actions in the scene.
- VOICEOVER vs CHARACTER SOUNDS: The audio transcript is likely a VOICEOVER narration dubbed over the video. Animals make their natural sounds. Voiceover goes in the official Seedance voiceover format.
- The main character (camera focus) should be described FIRST in the videoPrompt.

{$videoPromptInstruction}

The "cameraFixed" field MUST ALWAYS be true for social content videos.
- Camera movement is controlled separately by the API — NEVER set this to false.
- Even if the reference video had zoom/pan/tracking, the cloned version should use a FIXED camera.
- This is a hard rule: "cameraFixed": true — no exceptions.

IMPORTANT — CHARACTER RULES:
- The "characters" array MUST include EVERY visible character/creature in the video, even for voiceover/monologue scenes.
- Each character entry must have a "position" field describing their exact spatial placement from the camera's perspective.
- The "character" (singular) field should describe ALL main characters together in one sentence for the image prompt.

CRITICAL — SIZE & SCALE IN VIDEO PROMPT:
- If the visual analysis mentions characters that are MINIATURIZED, ENLARGED, or any UNUSUAL SIZE relative to normal → this MUST appear in the videoPrompt.
- Size/scale is NOT "appearance" — it is a PHYSICAL PROPERTY that directly affects how Seedance renders the scene.
- Example: if cats are tiny/miniaturized (ankle-height on a human), the videoPrompt MUST say "tiny miniature cat" or "miniaturized cat barely reaching the woman's ankle" — otherwise Seedance will render normal-sized cats and the entire visual comedy is lost.
- If there is a GROUP of characters (e.g., a line of 12 cats), specify the COUNT and FORMATION in the videoPrompt — "a single-file line of twelve tiny cats" not just "cats."
- Scale relationships between characters define the visual comedy. NEVER omit them.

CRITICAL — CHARACTER-OBJECT INTERACTIONS IN VIDEO PROMPT:
- If characters are USING objects (playing instruments, wielding tools, eating food), the videoPrompt MUST describe the ACTION of using them, not just holding/carrying.
- WRONG: "cats holding trumpets march forward" (what are they DOING with the trumpets?)
- RIGHT: "cats blowing into miniature trumpets with cheeks puffed, producing lively brass music as they march"
- The FUNCTIONAL USE of props is often the core comedy/action. A cat playing a trumpet IS the scene — omitting the playing action makes the prompt meaningless.
- If the visual analysis describes music coming from instruments characters hold, the videoPrompt must describe the PLAYING ACTION that produces the music — not treat it as background audio.

Return ONLY a JSON object (no markdown, no explanation):
{
  "title": "Catchy title (max 6 words) — must reference the actual character/animal",
  "concept": "One sentence describing the EXACT visual scene as analyzed",
  "speechType": "monologue" or "dialogue",
  "characters": [
    {"name": "Fun Name", "description": "EXACT species + detailed visual description matching the analysis: fur color, clothing, accessories. CRITICAL: include SIZE/SCALE — e.g. 'miniaturized to ankle-height' or 'normal adult cat size' or 'enlarged to dog-sized'. If the analysis says characters are unusually sized, this MUST be reflected here.", "role": "protagonist/supporting/background", "expression": "expression from analysis", "position": "EXACT spatial position: foreground/background, left/right/center, facing direction, distance from camera"}
  ],
  "character": "Combined description of ALL main visible characters with their spatial relationship — e.g. 'A woman stands at the counter facing a cat who stands on the counter behind it, they look at each other'",
  "imageComposition": "EXACT spatial layout from the reference: describe who is in foreground vs background, left vs right, their facing directions, the camera angle, and how they relate spatially — e.g. 'Customer in left foreground facing right toward the counter. Cat on counter in center-right, facing the customer. Employee in right background behind counter.'",
  "imageStartState": "The CALM INITIAL state of the scene for the starting image — characters in their starting positions BEFORE any action begins. NO action, NO chaos, NO objects flying. Just characters standing/sitting in position with neutral-to-mild expressions. Example: 'A woman stands at the counter looking at the cat. The cat stands calmly on the counter behind the glass barrier. The employee stands in the background watching.'",
  "situation": "One concise sentence: what happens from start to finish. Focus on the KEY dramatic beats, not every detail. e.g. 'Woman complains about her order, then the cat explodes — leaping across the counter, smashing dishes and sending food flying everywhere'",
  "setting": "The EXACT location with specific props, decor, and lighting from the analysis",
  "props": "Key visual props actually seen in the video",
  "audioType": "voiceover" or "dialogue" or "sfx" or "silent",
  "audioDescription": "Brief description of what happens",
  "dialogueLines": [
    {"speaker": "Character Name", "text": "What they actually say or do (for animals: 'meows angrily', for humans: 'actual spoken words')"},
    {"speaker": "Voiceover", "text": "Narration text if applicable"}
  ],
  "videoPrompt": "SEE SEEDANCE RULES BELOW — 140-170 words, dialogue trigger → instant chaos, continuous character sounds, intensity qualifiers on every action",
  "cameraFixed": true or false,
  "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
  "viralHook": "Why this would go viral (one sentence)",
  "source": "cloned"
}

=======================================================================
SEEDANCE VIDEO PROMPT RULES — READ THIS LAST, FOLLOW EXACTLY
=======================================================================

The "videoPrompt" is THE MOST IMPORTANT FIELD. It drives the actual video generation.
You are CLONING a reference video — capture the ENERGY and CONCEPT of the reference.

WORD COUNT: 150-180 words. This is the proven sweet spot for Seedance 1.5 Pro.
Under 140 words loses critical intensity. Over 200 words gets redundant. Aim for 160-175.

DO NOT describe character appearances (fur color, clothing, accessories) — that's in "character" and "characters" fields.
The videoPrompt describes actions, reactions, sounds, voice, AND SIZE/SCALE.
EXCEPTION: If characters are UNUSUALLY SIZED (miniaturized, enlarged, tiny, giant), you MUST mention this in the videoPrompt — e.g. "tiny miniature cat barely reaching ankle height" — because Seedance needs this to render the correct scale.

{$structureRules}

{$technicalRules}

EXAMPLE — GOOD CLONE PROMPT (~170 words):
{$templateExample}

NOW generate the JSON — make the videoPrompt faithfully capture the reference video's energy.
PROMPT;

        if ($chaosMode) {
            $prompt .= "\n\n" . $this->getChaosModeSupercharger();
        }

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
