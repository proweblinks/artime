<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;

class ConceptService
{
    /**
     * Call AI with engine-based model selection.
     */
    protected function callAIWithEngine(string|array $prompt, string $engine, int $teamId, array $options = []): array
    {
        $config = \Modules\AppVideoWizard\Livewire\VideoWizard::resolveEngine($engine);

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
        $aiEngine = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';

        $prompt = $this->buildImprovePrompt($rawInput, $productionType, $productionSubType);

        $result = $this->callAIWithEngine($prompt, $aiEngine, $teamId, [
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
        $aiEngine = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';

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

        $result = $this->callAIWithEngine($prompt, $aiEngine, $teamId, [
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
        $aiEngine = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';
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

        $result = $this->callAIWithEngine($prompt, $aiEngine, $teamId, [
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

        // Sanitize videoPrompt in each variation to fix banned words
        if (is_array($variations)) {
            foreach ($variations as &$variation) {
                if (!empty($variation['videoPrompt'])) {
                    $variation['videoPrompt'] = self::sanitizeSeedancePrompt($variation['videoPrompt']);
                }
            }
            unset($variation);
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
CRITICAL: ABSOLUTELY NO background music in the videoPrompt. NEVER write "music plays", "upbeat music", "beat drops", "soundtrack", or any music reference. Seedance auto-generates audio — any music text causes unwanted background music. Only write dialogue, character sounds (meowing, screaming, yowling), and physical sound effects (crashing, shattering, splashing).

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
    public function getChaosPromptModifier(int $chaosLevel, string $chaosDescription = ''): string
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
                'example' => '"the cat launches with crazy intensity fast onto the man\'s torso, front claws raking downward powerfully with large amplitude while rear legs thrash wildly at high frequency against his midsection"',
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
    public function getChaosModeSupercharger(): string
    {
        return <<<'CHAOS'
=== CHAOS MODE ACTIVE — OVERRIDE STRUCTURE ===

IMPORTANT: Chaos Mode overrides the normal action structure. Follow THESE rules instead:

FEWER MEGA-BEATS (3-4 maximum):
Instead of 6-8 rapid micro-actions, write ONLY 3-4 MASSIVE action beats.
Each beat gets 2-3 full sentences of detailed physical description.
More detail per beat = more motion energy Seedance allocates to each movement.

FRONT-LOAD THE IMPACT:
The BIGGEST, most intense action happens IMMEDIATELY after "Instantly".
Do NOT build up gradually. The first physical strike is the most intense one.
Structure: TRIGGER → INSTANT MEGA-STRIKE → escalation → peak destruction

EXAMPLE — CHAOS MODE PROMPT:
"The man says 'This is the worst pizza I've ever had!' Instantly the cat
reacts at high frequency and crazy intensity, lunging forward fast and
violently from behind the counter directly at the man's face, front claws
swiping wildly with large amplitude, slamming into his chest powerfully,
sending him staggering violently backwards. The man crashes fast into a shelf
of glass bottles, his arms flailing wildly as bottles shatter and liquid
sprays with large amplitude in every direction. The cat's hind legs kick at
high frequency against the man's torso, shredding fabric fast while the cat
screams a crazy intense yowl, mouth gaping wide. The man collapses
powerfully onto the floor, pulling an entire display rack down with him,
plates and cups crashing violently around him as the cat stands on his chest
screaming wildly. Continuous crazy aggressive cat screaming throughout.
Cinematic, photorealistic."

KEY DIFFERENCES FROM NORMAL MODE:
- 4 mega-beats instead of 8 micro-beats
- Each beat has 2-3 sentences of detailed motion
- First action (the launch) is the BIGGEST
- "crazy" appears 3+ times
- Every action has 2+ combined degree words
CHAOS;
    }

    /**
     * Get chaos-aware degree word instruction that overrides skeleton defaults.
     * Scales the skeleton's fixed degree word targets based on the user's chaos slider.
     */
    public function getChaosDegreeInstruction(int $chaosLevel, string $energyType): string
    {
        // Base degree word counts per energy type from getSkeletonTemplates()
        $baseCounts = [
            'GENTLE' => [2, 4],
            'PHYSICAL COMEDY' => [6, 10],
            'RHYTHMIC' => [5, 8],
            'DRAMATIC' => [6, 12],
            'CHAOTIC' => [12, 18],
        ];

        $base = $baseCounts[$energyType] ?? $baseCounts['PHYSICAL COMEDY'];

        return match (true) {
            $chaosLevel <= 20 => sprintf(
                'DEGREE WORD OVERRIDE (CALM — %d/100): Reduce degree words to %d-%d total. Actions are slow, gentle, small. Use only "quickly" and "fast".',
                $chaosLevel, max(1, (int)($base[0] * 0.5)), max(2, (int)($base[1] * 0.5))
            ),
            $chaosLevel <= 45 => sprintf(
                'DEGREE WORD SCALING (MODERATE — %d/100): Use skeleton defaults: %d-%d degree words. Standard energy.',
                $chaosLevel, $base[0], $base[1]
            ),
            $chaosLevel <= 65 => sprintf(
                'DEGREE WORD OVERRIDE (HIGH — %d/100): Increase to %d-%d degree words. Stack 2 per action. Use "powerfully", "wildly", "fast", "violently".',
                $chaosLevel, (int)($base[0] * 1.3), (int)($base[1] * 1.3)
            ),
            $chaosLevel <= 85 => sprintf(
                'DEGREE WORD OVERRIDE (PEAK — %d/100): Increase to %d-%d degree words. Combine words: "fast and violently", "powerfully with large amplitude". Use "crazy" on peak actions.',
                $chaosLevel, (int)($base[0] * 1.6), (int)($base[1] * 1.6)
            ),
            default => sprintf(
                'DEGREE WORD OVERRIDE (MAXIMUM OVERDRIVE — %d/100): Increase to %d-%d degree words. "crazy" on every action. Stack 2-3 combined degree words per beat. Go maximum.',
                $chaosLevel, $base[0] * 2, $base[1] * 2
            ),
        };
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
        // Delegate to SeedancePromptService (canonical location)
        try {
            return app(SeedancePromptService::class)->getTechnicalRules();
        } catch (\Throwable $e) {
            // Fallback to inline rules if service unavailable
        }

        return <<<'RULES'
SEEDANCE VIDEO PROMPT RULES:

ADVERBS — Use natural, descriptive adverbs freely:
- High intensity: rapidly, violently, crazily, intensely, aggressively, wildly, fiercely, powerfully
- Medium intensity: slowly, gently, steadily, smoothly, carefully, cautiously
- Temporal: suddenly, immediately, then, finally, instantly
- Place adverbs BEFORE or AFTER verbs naturally. Write as you would narrate the scene.

EXPLICIT MOTION — Seedance CANNOT infer motion:
Every movement must be EXPLICITLY described. The model will NOT animate what you don't write.
WRONG: "the cat attacks" (too vague — HOW does it attack?)
RIGHT: "the cat slaps the man's face with its right paw"
If a body part should move, DESCRIBE the motion. If an object should fly, DESCRIBE the trajectory.

DIALOGUE & SOUNDS — INCLUDE THEM:
- Include character dialogue in quotes: yells "Get off me!"
- Include character sounds: meows, yells, screams, growls, hisses
- Include environmental sounds caused by actions: crashes, clattering, shattering
- These help Seedance generate accurate audio and mouth movements.

CAMERA STYLE — Describe when relevant:
- "A chaotic, shaking handheld camera follows the action"
- "Smooth tracking shot" or "Static wide shot"
- Camera style helps set the visual tone.

PHYSICAL ACTION — SPECIFIC BODY PARTS:
GOOD: "slaps the man's face with its right paw"
GOOD: "lands violently on the man's left shoulder, its claws gripping wildly"
GOOD: "jumps onto the counter and violently knocks over the iced coffee cup and other items"
BAD: "the cat attacks him" (which body part? what motion? what gets hit?)

OBJECT DISPLACEMENT — ALWAYS INCLUDE:
When characters interact with objects during action, describe what happens:
"jumps onto the counter and violently knocks over the iced coffee cup"
"slams his hand down, sending napkins flying off the counter"
Objects flying, falling, scattering = essential visual chaos.

FACE & IDENTITY PRESERVATION:
- Do NOT add face/identity prefix text like "Maintain face consistency" — the source IMAGE defines the face.
- Do NOT include identity anchors (hair color, skin tone) — the image shows them.
- NEVER describe face structure changes: "face shifts", "expression changes to", "features contort".
- You may mention mouth opening for SPEAKING or SOUND PRODUCTION (e.g. "yells", "mouth opens wide") — these are actions.

STYLE ANCHOR — ALWAYS end with: "Cinematic, photorealistic."

SCALE & SIZE — Seedance renders characters at DEFAULT size unless told otherwise:
- If characters are miniaturized/tiny → you MUST say so: "tiny miniature cat barely ankle-height"
- If characters are enlarged/giant → you MUST say so: "enormous cat towering over the table"
- Without explicit size cues, Seedance renders normal-sized characters.

BANNED:
- No semicolons
- No appearance/clothing descriptions (fur color, clothing details — only what they DO)
- No facial micro-expression descriptions ("eyes widen", "brow furrows", "expression shifts") — convey emotion through body language
- No passive voice — only active verbs
- No weak/generic verbs: "goes", "moves", "does", "gets", "starts", "begins"
- ABSOLUTELY NO background music, soundtrack, score, beat, rhythm, or melody descriptions. NEVER write "music plays", "upbeat music", "dramatic score", "beat drops", "soundtrack". Seedance auto-generates audio from prompt text — any music mention creates unwanted background music.
RULES;
    }

    /**
     * Technical rules for CLONE videoPrompts — prioritizes ACTION TIMELINE faithfulness
     * over the aggressive Seedance formatting rules used for generated content.
     */
    protected function getCloneTechnicalRules(): string
    {
        return <<<'CLONE_RULES'
=== CLONE VIDEO PROMPT FORMAT ===

Write a natural, vivid narrative that describes the complete action of the source video.
Use clear, descriptive language. Include dialogue, sounds, and camera style.

PROMPT STRUCTURE — FOLLOW THE COMPLETE ACTION TIMELINE:
Write flowing prose that covers EVERY action phase from the analysis. Do NOT skip any phase.
Connect actions with natural transitions: "then", "suddenly", "in a quick motion".
End with: "Cinematic, photorealistic."
If the analysis describes 8 action phases → cover ALL 8 in your narrative.

INCLUDE:
- Dialogue in quotes from the audio transcript: yells "How can you ruin this?"
- Character sounds: meows, yells, screams — these drive accurate audio generation
- Camera style when notable: "A chaotic, shaking handheld camera follows the action"
- Emotional states as part of actions: "leans aggressively", "angrily points"
- Specific body parts: "slaps the man's face with its right paw", "lands on the man's left shoulder"
- Object displacement as cause-and-effect: "jumps onto the counter and violently knocks over the iced coffee cup"

DO NOT INCLUDE:
- NO scene/setting/environment descriptions — the source image provides the scene.
- NO appearance/clothing/color descriptions — the source image provides appearance.
- NO background music mentions — Seedance generates audio from text, any music reference creates unwanted audio.
- NO facial micro-expressions ("eyes widen", "brow furrows") — convey emotion through body language.
- NO semicolons, NO passive voice.
- Do NOT fabricate actions not in the analysis. Faithful to what actually happened.
- Match intensity to the analysis: calm scene = gently/steadily, intense scene = crazily/violently.
- If characters are miniaturized/enlarged, MENTION the size — it affects rendering.
- Target: 100-150 words (8+ action phases → aim for 120-150 words). Under 90 = missing key actions. Completeness over brevity — include every phase from FIRST to LAST.

GOLD-STANDARD EXAMPLE (~75 words — this prompt produced excellent Seedance results):
"The bear steadily walks forward. The bike's front wheel bumps into the bear's butt. The bear rapidly glances back over his shoulder, flashing a big funny smile, then turns his head forward. Instantly the bear starts a rhythmic hip-swaying butt dance, hips crazily bobbing side to side with large amplitude at high frequency, its whole body bouncing in a funny groove. The bear's arms swing loosely with large amplitude matching the hip rhythm. Continuous comedic energy throughout. Cinematic, photorealistic."

WHY IT WORKS — Follow this pattern for ANY video:
- Setup: First action — what starts the scene (1 sentence)
- Trigger: What causes the chain reaction (1 sentence)
- Reaction: Immediate response (1-2 sentences)
- Main action: The core — most detail here (1-3 sentences)
- Aftermath/Resolution: What happens AFTER the climax — pulling off, throwing, walking away, giving up. DO NOT SKIP THIS. (1-3 sentences)
- Style: "Cinematic, photorealistic." (always last)
The bear example is SHORT because the bear has no resolution phase. For videos with more phases, write MORE sentences.
CRITICAL: Your LAST action sentence should describe the FINAL thing that happens in the video, NOT the climax.
CLONE_RULES;
    }

    /**
     * Post-process AI-generated Seedance prompts to fix banned words.
     * Gemini Flash frequently ignores the banned word list, so this PHP function
     * programmatically catches and replaces non-compliant words.
     *
     * Call this on any AI-generated videoPrompt/continuation prompt BEFORE saving or sending to Seedance.
     */
    public static function sanitizeSeedancePrompt(string $text): string
    {
        // Phase 1: Fix compound phrases
        $compounds = [
            '/\brazor[\s-]*sharp\b/i' => 'sharp',
            '/\brazor\s+claws\b/i' => 'sharp claws',
        ];

        foreach ($compounds as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 2b: Replace passive/weak verbs
        $passiveReplacements = [
            '/\bnestled\b/i' => 'pressing',
            '/\bnestling\b/i' => 'pressing',
            '/\bbegins to\b/i' => '',
            '/\bstarts to\b/i' => '',
        ];

        foreach ($passiveReplacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 3b: Remove/fix banned facial expression descriptions
        // Seedance preserves faces best when prompt focuses on BODY MOTION, not facial micro-expressions
        $facialPatterns = [
            // "eyes crinkling/widening/narrowing/crinkle/widen [words]" — facial micro-expression (all verb forms)
            '/,?\s*\beyes?\s+(?:crinkling|crinkel|crinkle|crinkled|widening|widen|widened|narrowing|narrow|narrowed|squinting|squint|twinkling|twinkle|sparkling|sparkle|gleaming|gleam|glinting|glint)\s*\w*/i' => '',
            // "eyes wide [adj]" — eyes wide open/serious/etc
            '/,?\s*\beyes?\s+wide\s*\w*/i' => '',
            // "eyes stare/gaze/peer/glare/look [at/toward something]" — direct eye action
            '/,?\s*\beyes?\s+(?:stare|stares?|staring|gaze|gazes?|gazing|peer|peers?|peering|glare|glares?|glaring|look|looks?|looking)\b[^,.]*[.,]?/i' => '',
            // "crinkled/squinted/narrowed/widened eyes" — inverted adjective+noun form
            '/,?\s*(?:with\s+)?(?:crinkled|squinted|narrowed|widened|teary|watery|half-closed|droopy)\s+eyes?\b[^,.]*[.,]?/i' => '',
            // "mouth curves/twists/forms into/in [adj] smile/grin/frown"
            '/,?\s*\bmouth\s+(?:curves?|twists?|forms?|breaks?|turns?)\s+(?:wide\s+)?(?:into|in)\s+(?:\w+\s+){0,2}(?:smile|grin|frown|smirk)/i' => '',
            // "mouth curves wide in smile as [anything]" — catch the compound form
            '/,?\s*\bmouth\s+curves?\s+wide\s+in\s+\w+[^.]*(?=\.)/i' => '',
            // "eyes lock on [target] with [emotional] glint/gleam/look"
            '/\beyes\s+lock\s+on\s+[^,.]*(?:glint|gleam|look|gaze|stare)\b/i' => '',
            // "brow furrowing/furrowed"
            '/\bbrows?\s+(?:furrowing|furrowed|knitting|knitted|raised|raising)\b/i' => '',
            // "jaw clenching/clenched/dropping"
            '/\bjaws?\s+(?:clenching|clenched|dropping|dropped|setting|set)\b/i' => '',
            // "in amusement/delight/horror/disgust"
            '/\bin\s+(?:amusement|delight|horror|disgust|surprise|wonder|disbelief|shock)\b/i' => '',
            // "with a [adj] glint/grin/smirk"
            '/\bwith\s+(?:a\s+)?(?:\w+\s+)?(?:glint|grin|smirk|sneer)\b/i' => '',
            // "expression shifts/changes to [adj] smile/grin"
            '/\bexpression\s+(?:shifts?|changes?)\s+to\s+[^,.]*(?:smile|grin|frown|smirk)/i' => '',
            // "wide/toothless/bright/warm smile" — any smile description
            '/\b(?:\w+\s+)?(?:toothless|wide|bright|warm|soft|gentle|sly|knowing|wicked)\s+smile\b/i' => '',
            // Standalone "smile/grin/frown/smirk" as action result
            '/\bbreaks?\s+into\s+(?:a\s+)?(?:smile|grin|laugh)\b/i' => '',
            // "eyes looking/locked/fixed at/on camera"
            '/,?\s*eyes?\s+(?:looking|gazing|staring|glancing|locked|fixed|focused|trained)\s+(?:\w+\s+)?(?:at|on|toward|towards)\s+(?:the\s+)?camera\b[^,.]*[.,]?/i' => '',
            // "toward/at the camera" anywhere
            '/,?\s*(?:looking|facing|turning|glancing|locked|fixed|focused)\s+(?:at|on|toward|towards)\s+(?:the\s+)?camera\b/i' => '',
            // standalone "at camera" / "at the camera" / "to camera"
            '/\s+(?:at|to|toward|towards)\s+(?:the\s+)?camera\b/i' => '',
            // "eye contact" — facial/camera reference
            '/,?\s*(?:maintaining|making|holding|with)\s+(?:direct\s+)?eye\s+contact\b[^,.]*[.,]?/i' => '',
            // "cheeks puffing/puffed/bulging" — facial micro-expression
            '/,?\s*(?:with\s+)?cheeks?\s+(?:puffing|puffed|bulging|inflating|inflated)\b[^,.]*[.,]?/i' => '',
            // "smiles/smiled/smiling [adverb] with [emotion]" — facial expression + emotional
            '/,?\s*\b(?:smiles?|smiled|smiling)\s+\w*\s*(?:with\s+\w+)?/i' => '',
            // "face transforms/changes/shifts [anything]" — facial structure changes
            '/,?\s*\bface\s+(?:transforms?|changes?|shifts?|contorts?|morphs?|lights?\s+up)\s*\w*[^,.]*[.,]?/i' => '',
            // "mouth forms/makes shape/shape" — facial description
            '/\bmouth\s+(?:forms?|makes?|creates?)\s+(?:a?\s*)?(?:shape|circle|oval|o\b)[^,.]*[.,]?/i' => 'mouth opens',
            // "with joy/delight/glee/satisfaction" — emotional phrase
            '/\bwith\s+(?:joy|delight|glee|satisfaction|pleasure|excitement|enthusiasm|pride|happiness)\b/i' => 'powerfully',
            // "in laugh/laughter/giggle with [anything]" — facial description compound
            '/\bin\s+(?:laugh|laughter|giggle|giggling)\s+with\s+[^,.]+/i' => 'producing crazy giggle',
            // "face brightens/glows/lights" — additional facial expression verbs
            '/,?\s*\bface\s+(?:brightens?|glows?|beams?|softens?|hardens?|relaxes?|tenses?|scrunches?|crumples?|falls?)\b[^,.]*[.,]?/i' => '',
            // "looks satisfied/happy/guilty" — emotional appearance description
            '/,?\s*\blooks?\s+(?:satisfied|happy|pleased|guilty|innocent|content|proud|sad|angry|worried|confused|surprised|shocked|terrified|bored|amused|annoyed|disgusted|excited)\b[^,.]*[.,]?/i' => '',
            // "Sleeping/resting [noun]" as appearance descriptor
            '/\b(?:sleeping|resting|dozing|napping)\s+(?=(?:mother|father|woman|man|person|baby|infant|child))/i' => '',
        ];

        foreach ($facialPatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 3c: Remove appearance/clothing descriptions
        $appearancePatterns = [
            // "wrapped from waist down", "wrapped in [cloth]"
            '/,?\s*wrapped\s+(?:from|around)\s+[^,.]+/i' => '',
            // "food/sauce smudged/splattered/visible on/around [body part]"
            '/,?\s*(?:with\s+)?(?:food|sauce|liquid|cream|crumbs?)\s+(?:residue\s+)?(?:smudged|splattered|dripping|stuck|remaining|visible|smeared|caked)\s+(?:on|around|over)\s+[^,.]+/i' => '',
            // "food residue [anything]" — ANY food residue mention is appearance description
            '/,?\s*(?:with\s+)?(?:food|sauce|liquid|cream|crumbs?)\s+residue\b[^,.]*[.,]?/i' => '',
            // "wearing/dressed in [clothing]"
            '/,?\s*(?:wearing|dressed\s+in|clad\s+in)\s+[^,.]+/i' => '',
            // Specific clothing items
            '/,?\s*(?:in\s+)?(?:a\s+)?(?:white|blue|red|black|green|pink|yellow|brown)\s+(?:shirt|jacket|hoodie|polo|sweater|dress|gown|towel|blanket)\b/i' => '',
            // Lighting descriptors: "brightly lit", "dimly lit", "well-lit", "lit [room/space]", "bright [any word]"
            '/\b(?:brightly|dimly|softly|warmly|harshly)\s+lit\b/i' => '',
            '/\blit\s+(?=(?:hospital|room|space|corridor|hallway|ward|chamber|studio|kitchen|office|area))/i' => '',
            '/\bbright\s+(?=\w)/i' => '',
            // "clear" as non-official descriptor (clear plastic, clear shhh, clear gesture)
            '/\bclear\s+(?=(?:plastic|glass|shhh|shush|gesture|chewing|slapping|tapping|sound))/i' => '',
        ];

        foreach ($appearancePatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 3f: Fix dangling "wrapped" not part of "unwrapped"
        $text = preg_replace('/\b(?<!un)wrapped(?!\s+(?:around|in|from|shawarma|food|burger|wrap))\s*,/i', ',', $text);

        // Phase 5: Clean up artifacts from removals
        // Remove empty comma-separated clauses: ", ," or ", , ," etc.
        $text = preg_replace('/,\s*,\s*,/i', ',', $text);
        $text = preg_replace('/,\s*,/', ',', $text);
        // Remove space before punctuation: "word ." → "word."
        $text = preg_replace('/\s+([.,!])/', '$1', $text);
        // Remove comma-space-period: ",." → "."
        $text = preg_replace('/,\./', '.', $text);
        // Remove leading comma after period: ". ," → "."
        $text = preg_replace('/\.\s*,/', '.', $text);
        // Remove double/triple periods
        $text = preg_replace('/\.{2,}/', '.', $text);
        // Fix "word, ." at end of sentence
        $text = preg_replace('/,\s*\./', '.', $text);
        // Phase 6: Deduplicate overused Seedance adverbs — use count-aware replacement
        $text = self::deduplicateSeedanceAdverbs($text);

        // Clean up double spaces
        $text = preg_replace('/\s{2,}/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Lightweight post-compliance pass: only deduplicate overused adverbs.
     * Use this AFTER the AI compliance validator instead of the full sanitizer,
     * which is too destructive on rewritten text.
     */
    public static function deduplicateSeedanceAdverbs(string $text): string
    {
        $allAdverbs = ['crazily', 'violently', 'rapidly', 'intensely', 'slowly', 'gently', 'steadily', 'smoothly'];

        // Count all adverbs first to pick alternatives that aren't already overused
        $counts = [];
        foreach ($allAdverbs as $adv) {
            $counts[$adv] = preg_match_all('/\b' . $adv . '\b/i', $text);
        }

        foreach ($allAdverbs as $adverb) {
            if ($counts[$adverb] <= 2) continue;

            // Find alternatives that have room (under 2 uses)
            $available = array_filter($allAdverbs, fn($a) => $a !== $adverb && $counts[$a] < 2);
            if (empty($available)) continue;

            $available = array_values($available);
            $n = 0;
            $altIdx = 0;
            $text = preg_replace_callback('/\b' . $adverb . '\b/i', function ($match) use (&$n, &$altIdx, $available, &$counts, $adverb) {
                $n++;
                if ($n > 2) {
                    $alt = $available[$altIdx % count($available)];
                    $altIdx++;
                    $counts[$alt]++;
                    $counts[$adverb]--;
                    return $alt;
                }
                return $match[0];
            }, $text);
        }
        return $text;
    }

    /**
     * AI-powered Seedance 1.5 compliance validator.
     * Sends the prompt to AI with ALL Seedance rules and gets back violations + fixed prompt + score.
     * This is the second pass after the regex sanitizer — catches everything regex misses.
     *
     * @param string $prompt The videoPrompt to validate (should already be regex-sanitized)
     * @param int $teamId Team ID for AI quota tracking
     * @param string $aiEngine AI model tier (default: economy for speed)
     * @return array {success, score, violations[], fixedPrompt, summary, originalPrompt}
     */
    public function validateSeedanceCompliance(string $prompt, int $teamId, string $aiEngine = 'economy', string $context = 'generate'): array
    {
        $rules = $this->getSeedanceTechnicalRules();
        $wordCount = str_word_count($prompt);

        // Clone context: preserve faithfulness
        if ($context === 'clone') {
            $wordCountSection = "=== WORD COUNT RULE ===\nThe TOTAL prompt should be 80-150 words. Preserve ALL action sentences, dialogue, and sounds.\nUnder 80 words = missing key actions. Over 155 = trim redundant descriptions only.\nDo NOT remove dialogue or sound descriptions — they drive audio generation.\nCurrent word count: {$wordCount} words.";
            $cloneOverride = "\n=== CLONE FAITHFULNESS (HIGHEST PRIORITY) ===\nThis is a CLONED video prompt. The #1 rule is FAITHFULNESS to the source video.\n- NEVER escalate action intensity. If the source shows calm walking, keep it calm.\n- NEVER remove dialogue, sounds, or camera style descriptions — these are intentional.\n- NEVER remove action beats that describe distinct parts of the video.\n- Preserve the COMPLETE timeline.\n- Only fix genuine technical violations (appearance descriptions, background music mentions, scene descriptions).\n- When in doubt, keep the original wording.";
        } else {
            $wordCountSection = "=== WORD COUNT RULE (CRITICAL) ===\nThe TOTAL prompt must be 80-150 words.\nIf over 155 words, TRIM by removing redundant modifiers — do NOT remove dialogue, sounds, or object displacement.\nCurrent word count: {$wordCount} words.";
            $cloneOverride = '';
        }

        $validationPrompt = <<<PROMPT
You are a Seedance video prompt compliance validator. Scan the prompt below and fix violations.

=== RULES ===
{$rules}

=== ADDITIONAL RULES ===
- Dialogue in quotes is ALLOWED and should be PRESERVED — it drives audio generation
- Character sounds (meowing, yelling, screaming) are ALLOWED and should be PRESERVED
- Camera style descriptions are ALLOWED (e.g., "chaotic handheld camera")
- Natural adverbs are ALLOWED — do NOT restrict to a fixed set
- Emotional states as part of actions are ALLOWED (e.g., "leans aggressively", "angrily points")
- NO facial micro-expression descriptions (eyes widening, brow furrowing, mouth curving into smile)
- EXCEPTION: "glance", "look", "stare", "gaze" are HEAD/EYE ACTIONS — keep them.
- NO "toward camera", "at the camera", "eyes locked on camera" — rewrite direction without camera mention
- If the prompt is truncated (ends mid-sentence), fix it by completing or trimming to last complete sentence
- Must NOT contain face/identity prefix text like "Maintain face consistency"
- Must NOT contain scene/setting descriptions — the source image already shows the scene
- Must start directly with the first action
- Must end with "Cinematic, photorealistic."
- ABSOLUTELY NO background music mentions (soundtrack, score, beat, rhythm, melody)
{$cloneOverride}

{$wordCountSection}

=== PROMPT TO VALIDATE ===
{$prompt}

=== INSTRUCTIONS ===
1. Scan for violations of the rules above
2. List ALL violations found
3. Provide the COMPLETE fixed prompt with violations corrected
4. Rate compliance 0-100

Return ONLY valid JSON (no markdown, no explanation):
{"score":85,"violations":[{"word":"the violating text","rule":"rule broken","fix":"correction"}],"fixedPrompt":"entire corrected prompt","summary":"one sentence summary"}

CRITICAL: Preserve ALL original actions, dialogue, sounds, and camera descriptions. Only fix genuine violations.
PROMPT;

        try {
            $result = $this->callAIWithEngine($validationPrompt, $aiEngine, $teamId, [
                'maxResult' => 1,
                'max_tokens' => 4000,
            ]);

            if (!empty($result['error'])) {
                \Log::warning('SeedanceCompliance: AI call failed', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'score' => 0,
                    'violations' => [],
                    'fixedPrompt' => $prompt,
                    'summary' => 'Validation failed: ' . $result['error'],
                    'originalPrompt' => $prompt,
                ];
            }

            $text = $result['data'][0] ?? '';

            // Extract JSON from response (handle potential markdown wrapping)
            $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
            $text = preg_replace('/```\s*$/m', '', $text);

            if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
                $parsed = json_decode($matches[0], true);
                if ($parsed && isset($parsed['score'])) {
                    $fixedPrompt = $parsed['fixedPrompt'] ?? $prompt;

                    // Safety: ensure fixedPrompt ends with style anchor
                    if (!str_contains($fixedPrompt, 'Cinematic, photorealistic')) {
                        $fixedPrompt = rtrim($fixedPrompt, '. ') . '. Cinematic, photorealistic.';
                    }

                    // Strip any face prefix the AI may have added (wastes word budget — image defines the face)
                    $fixedPrompt = preg_replace('/Maintain face[^.]*\.(\s*Character face[^.]*\.)?\s*/i', '', $fixedPrompt);
                    // Strip scene/setting descriptions
                    $fixedPrompt = preg_replace('/^(?:In\s+(?:a|an|the)\s+[^.]+\.\s*)+/i', '', $fixedPrompt);
                    $fixedPrompt = preg_replace('/^(?:(?:Inside|Within|At|On)\s+(?:a|an|the)\s+[^.]+\.\s*)+/i', '', $fixedPrompt);

                    // Hard word count enforcement — clone prompts need room for all action beats
                    $hardLimit = ($context === 'clone') ? 155 : 155;
                    $targetLimit = ($context === 'clone') ? 140 : 140;
                    $fixedWordCount = str_word_count($fixedPrompt);
                    if ($fixedWordCount > $hardLimit) {
                        \Log::warning("SeedanceCompliance: AI fixedPrompt over {$hardLimit} words, trimming", [
                            'wordCount' => $fixedWordCount,
                            'context' => $context,
                        ]);
                        // Trim by removing middle sentences, keeping opening and closing
                        $sentences = preg_split('/(?<=\.)\s+(?=[A-Z"])/', $fixedPrompt);
                        if (count($sentences) > 3) {
                            $opening = array_slice($sentences, 0, 2);
                            $closing = [array_pop($sentences)];
                            $middle = array_slice($sentences, 2);
                            $result = $opening;
                            $currentWords = str_word_count(implode(' ', $opening)) + str_word_count(implode(' ', $closing));
                            foreach ($middle as $sentence) {
                                $sentenceWords = str_word_count($sentence);
                                if ($currentWords + $sentenceWords <= $targetLimit) {
                                    $result[] = $sentence;
                                    $currentWords += $sentenceWords;
                                }
                            }
                            $fixedPrompt = implode(' ', array_merge($result, $closing));
                            if (!str_contains($fixedPrompt, 'Cinematic, photorealistic.')) {
                                $fixedPrompt = rtrim($fixedPrompt, '. ') . '. Cinematic, photorealistic.';
                            }
                        }
                    }

                    \Log::info('SeedanceCompliance: Validation complete', [
                        'score' => $parsed['score'],
                        'violationCount' => count($parsed['violations'] ?? []),
                        'originalWords' => str_word_count($prompt),
                        'fixedWords' => str_word_count($fixedPrompt),
                    ]);

                    return [
                        'success' => true,
                        'score' => (int) $parsed['score'],
                        'violations' => $parsed['violations'] ?? [],
                        'fixedPrompt' => $fixedPrompt,
                        'summary' => $parsed['summary'] ?? 'Validation complete',
                        'originalPrompt' => $prompt,
                    ];
                }
            }

            \Log::warning('SeedanceCompliance: Failed to parse AI response', ['response' => mb_substr($text, 0, 500)]);
            return [
                'success' => false,
                'score' => 0,
                'violations' => [],
                'fixedPrompt' => $prompt,
                'summary' => 'Failed to parse validation response',
                'originalPrompt' => $prompt,
            ];
        } catch (\Exception $e) {
            \Log::error('SeedanceCompliance: Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'score' => 0,
                'violations' => [],
                'fixedPrompt' => $prompt,
                'summary' => 'Validation error: ' . $e->getMessage(),
                'originalPrompt' => $prompt,
            ];
        }
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

STRUCTURE — MATCH THE REFERENCE:
- Trigger-reaction pattern (human says something → animal reacts) → use trigger-reaction
- Slow build (calm start → gradual escalation → payoff) → use slow build
- Constant rhythmic motion (marching, dancing, bobbing) → keep steady rhythmic motion
- Calm/gentle interaction (petting, sitting, small gestures) → stay calm and gentle
- Multiple characters interacting → preserve character dynamics and spatial relationships

DEGREE WORD SCALING — MATCH THE SOURCE ENERGY (use ONLY official words):
Official words: quickly, violently, with large amplitude, at high frequency, powerfully, wildly, crazy, fast, intense, strong, greatly.

CALM videos (gentle, cute, wholesome): 2-4 degree words total.
  One per action, Tier 1 only: "quickly", "fast".
  Example: "paws tapping quickly on the surface" — do NOT escalate.

MODERATE videos (physical comedy, light slapstick): 5-8 degree words total.
  One per action, Tier 1-2: "quickly", "fast", "powerfully", "strong".
  Example: "paws slam the table powerfully, sending the plate sliding fast"

HIGH ENERGY videos (aggressive, intense, chaotic): 9-14 degree words total.
  Stack 2 per action, Tier 2-3: "powerfully", "wildly", "violently", "crazy", "with large amplitude".
  Example: "paws slam powerfully with large amplitude, body lunging forward fast and violently"

MAXIMUM CHAOS videos (total destruction): 14-18 degree words total.
  Stack 2-3 per action, "crazy" on most: "crazy wildly", "powerfully with large amplitude", "fast and violently".
  Example: "launches with crazy intensity, claws raking wildly at high frequency"

BODY PARTS — ONLY DESCRIBE WHAT THE ANALYSIS MENTIONS:
If the analysis describes specific body parts moving (head turns, hips sway, paws step), include those.
Do NOT decompose into 4-7 body parts if the analysis doesn't mention them.
If the analysis says "the bear walks" → write "the bear walks", do NOT invent "front paws extend powerfully, hind legs push off greatly".
Only add body part detail that IS in the ACTION TIMELINE.

SOUND DESCRIPTIONS — ONLY FROM THE ANALYSIS:
Include character vocalizations ONLY if the visual analysis confirms they were actually HEARD in the audio.
- If the analysis says "No animal vocalizations detected" → do NOT fabricate character sounds
- Environmental sounds from VISIBLE ACTIONS are valid: footsteps, tire crunching, impacts, splashing
- Do NOT invent sounds to fill a quota — fewer accurate sounds beat fabricated ones

CHAIN REACTIONS — ONLY IF DESCRIBED IN ANALYSIS:
If the analysis describes cause-and-effect (object falls, liquid spills, items scatter), include those.
Do NOT fabricate chain reactions not in the analysis (no "stones dislodging", "clouds scattering" unless described).

FAITHFULNESS — MATCH WHAT WAS SEEN:
- Same type of movements, same emotional register, same comedic style as the reference.
- You can optimize phrasing for Seedance, but stay true to the source's vibe.
- If the video shows marching → write marching. If cooking → write cooking.
- Do NOT transform everything into attack/destruction scenes.

SCALE, QUANTITY & OBJECT INTERACTIONS — PRESERVE WHAT MAKES IT SPECIAL:
- MINIATURIZED characters → videoPrompt MUST say "tiny miniature cat barely reaching ankle height"
- ENLARGED characters → describe exaggerated scale
- LARGE GROUP → specify exact count and formation: "a single-file line of twelve tiny cats"
- USING objects → describe ACTIVE USE: "blowing into trumpets producing brass music" not "holding trumpets"

NO SCENE SETUP — IMAGE-TO-VIDEO:
Do NOT describe the scene, setting, or starting positions. The source IMAGE already shows all of this.
Start DIRECTLY with the first action beat. Name objects only AS PART OF actions (e.g. "grips the shawarma powerfully").

ANTI-PATTERNS (Seedance ignores or misinterprets these — NEVER use):
- Abstract descriptions: "chaos ensues", "mayhem unfolds", "things go wrong"
- Standalone emotional states without action: "feeling excited", "happily" — instead attach emotion TO action: "leans aggressively", "angrily points"
- Temporal jumps: "moments later", "suddenly", "after a while"
- Off-screen references: "someone throws", "a noise from another room"
- Vague quantities: "several", "many", "a bunch of" → use exact numbers

SEEDANCE 1.5 PRO FORMAT: Natural vivid narrative. 80-150 words total.
Build as action beats: Setup → Trigger → Reaction → Main action (body parts + modifiers) → Mood + Style.
Use natural adverbs freely: rapidly, violently, aggressively, wildly, fiercely, crazily, intensely, gently, steadily.
Temporal words: then, instantly, after, finally. Include dialogue in quotes and character sounds. NO scene descriptions. NO appearance.
Main action gets 50-60% of words with specific body part motions and intensity modifiers.
RULES,
                'generate' => <<<'RULES'
=== ADAPTIVE MODE — CHOOSE THE BEST STRUCTURE ===

Choose the structure that best fits each concept's energy and comedy style.
Not every concept needs explosive chaos — match the structure to the content.

STRUCTURAL TEMPLATES BY ENERGY TYPE:

GENTLE/CUTE concepts (calm, wholesome):
  Sentence 1-2: Establish setting + character in calm starting position with 2-3 named objects nearby.
  Sentence 3-4: Character performs small deliberate actions — tapping, nudging, tilting. One degree word each (quickly, fast).
  Sentence 5-6: Gentle continuation with a small environmental reaction (object tips, item slides).
  Sentence 7: Warm resolution. "Continuous [gentle character sounds] throughout. Cinematic, photorealistic."
  Degree words: 2-4 total. Tier 1 only (quickly, fast).

PHYSICAL COMEDY concepts (slapstick, exaggerated):
  Sentence 1: Setting + character + trigger moment (a line of dialogue or situation).
  Sentence 2-3: Exaggerated physical reaction with body part decomposition. 1-2 degree words per action.
  Sentence 4-5: Chain reaction — action causes objects to move/fall/break. Stack degree words.
  Sentence 6-7: Peak moment + aftermath. "Continuous [character sounds] throughout. Cinematic, photorealistic."
  Degree words: 6-10 total. Tier 1-2 (quickly, fast, powerfully, strong, intense).

CHAOTIC concepts (destruction, aggressive, attack):
  Sentence 1: Setup + trigger (one short dialogue line).
  Sentence 2: "Instantly" — first strike with stacked degree words (2-3 per action).
  Sentence 3-4: Escalation — rapid body part actions + chain reactions. "crazy" on most actions.
  Sentence 5-6: Peak chaos — maximum destruction, all body parts active simultaneously.
  Sentence 7: "Continuous crazy aggressive [character] screaming throughout. Cinematic, photorealistic."
  Degree words: 12-18 total. Tier 3 dominant (crazy, wildly, violently, with large amplitude, at high frequency).

RHYTHMIC/MUSICAL concepts (dancing, marching, synchronized):
  Sentence 1-2: Establish setting + character begins rhythmic action pattern.
  Sentence 3-4: Layer additional body parts joining the rhythm (head bobs, tail sways, paws tap).
  Sentence 5-6: Full-body synchronization — all parts moving in coordinated pattern. Nearby objects vibrate/rattle.
  Sentence 7: Camera-break moment or flourish. "Continuous crazy [character vocalizing] throughout. Cinematic, photorealistic."
  Degree words: 5-8 total. Mix of Tier 1-2 (quickly, fast, powerfully, at high frequency).

DRAMATIC concepts (slow build, tension, payoff):
  Sentence 1-2: Establish atmosphere + character in a still, tense starting position.
  Sentence 3: Small tell — one body part moves (ear twitches, finger taps).
  Sentence 4-5: Build — more body parts engage, degree words increase. Objects begin to react.
  Sentence 6-7: Payoff explosion — sudden burst of stacked degree words + chain reactions.
  Sentence 8: "Continuous [sounds] throughout. Cinematic, photorealistic."
  Degree words: 6-12 total. Start with 0, escalate to Tier 3 at climax.

BODY PART DECOMPOSITION — APPLIES TO ALL TYPES:
Every character: 4-7 body parts with distinct simultaneous actions.
Animals: head, ears, mouth/jaw, front paws, hind legs, tail, body/torso.
Humans: head, arms/hands, legs/feet, torso.

SOUND DESCRIPTIONS — APPLIES TO ALL TYPES (3-5 per prompt):
Apply degree words to sounds. "crazy loud meow" > "meow". "glass shattering" > "sound effects".

CHAIN REACTIONS — MINIMUM 2 PER PROMPT:
[Character body part + action + degree word] → [object reacts] → [secondary consequence]

ANTI-PATTERNS (NEVER use):
- Abstract: "chaos ensues", "mayhem unfolds"
- Emotional states: "feeling excited", "nervously"
- Temporal jumps: "moments later", "suddenly"
- Vague quantities: "several", "many" → exact numbers

DIALOGUE/ACTION BALANCE:
- Some concepts need a trigger line then action. Others need no dialogue.
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
"The cat stands on the kitchen counter and starts bobbing its head quickly to the rhythm, front paws marching in quick small steps. Its tail sways powerfully left and right like a metronome as it lets out a sustained crazy meow in time with the tempo. The cat's hind legs stamp the counter at high frequency, rattling the nearby utensils wildly against each other. A wooden spoon slides off the rack and clatters to the floor. The cat pauses, looks directly at the camera with wide intense eyes, then resumes marching with large amplitude, stomping down powerfully and sending a glass jar tipping over the edge and shattering on the floor. Continuous crazy cat vocalizing throughout. Cinematic, photorealistic."
EXAMPLE,
            'animal-chaos' => <<<'EXAMPLE'
"The man leans forward and says 'This coffee is terrible, what did you put in this? I want my money back!' while gesturing angrily at the cup. Instantly the cat reacts at high frequency and crazy intensity, screeching a loud piercing furious meow and lunging forward fast and violently, both paws swiping wildly and powerfully. The cat violently smashes the iced coffee cup, liquid splashing powerfully across the counter. The cat screams another fierce yowl and launches itself fast onto the man's chest, and then jumps back and goes wild and smashes everything in the store as he runs away. Loud crash of falling items, continuous crazy aggressive cat screaming throughout. Camera shakes with chaotic handheld energy. Cinematic, photorealistic."
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
     * Stage 1: Upload video to Gemini File API + analyze with Gemini 2.5 Pro (native video understanding)
     * Stage 2: Extract audio + transcribe with Whisper
     * Stage 3: AI synthesis into viral idea format
     *
     * @param string $videoPath Absolute path to the uploaded video file
     * @param array $options teamId, aiEngine, mimeType
     * @return array Structured concept matching generateViralIdeas() output format
     */
    public function analyzeVideoForConcept(string $videoPath, array $options = []): array
    {
        $teamId = $options['teamId'] ?? 0;
        $aiEngine = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';
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

        Log::info('ConceptCloner: Video uploaded, analyzing with Gemini 2.5 Pro', [
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
            'model' => $analysisResult['model'] ?? 'gemini-2.5-pro',
            'analysisPreview' => mb_substr($visualAnalysis, 0, 500),
        ]);

        // Stage 1b: Post-analysis validation — object displacement consistency check
        $visualAnalysis = $this->validateObjectDisplacement($visualAnalysis, $upload['fileUri'], $geminiService, $mimeType);

        // Stage 2: Extract audio + transcribe (if audio exists)
        Log::info('ConceptCloner: Stage 2 — Extracting and transcribing audio');
        $transcript = $this->extractAndTranscribeAudio($videoPath);
        Log::info('ConceptCloner: Stage 2 complete', ['hasTranscript' => !empty($transcript)]);

        // Stage 3: Synthesize into structured concept using user's chosen engine
        Log::info('ConceptCloner: Stage 3 — Synthesizing concept', [
            'aiEngine' => $aiEngine,
        ]);
        $templateId = $options['template'] ?? 'adaptive';
        $concept = $this->synthesizeConcept($visualAnalysis, $transcript, $aiEngine, $teamId, $videoEngine, $chaosMode, $templateId);
        Log::info('ConceptCloner: Pipeline complete', ['conceptTitle' => $concept['title'] ?? 'unknown']);

        // Store full analysis for debugging/inspection
        $concept['_visualAnalysis'] = $visualAnalysis;
        $concept['_audioTranscript'] = $transcript;

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
     * Post-analysis validation: detect contradictions in object displacement.
     *
     * Gemini sometimes describes objects on surfaces (cups, dispensers) but then
     * claims "all objects remain undisturbed" in the action timeline — which contradicts
     * what the video actually shows. This method detects that pattern and re-queries
     * Gemini with a focused displacement-only question.
     */
    protected function validateObjectDisplacement(string $analysis, string $fileUri, $geminiService, string $mimeType): string
    {
        $hasIntenseAction = preg_match('/(?:INTENSE|EXTREME|WILD|lunges|jumps onto|leaps|attacks|swat|knock|chaotic|pandemonium|violent)/i', $analysis);

        if (!$hasIntenseAction) {
            Log::info('ConceptCloner: Object displacement validation skipped — no intense action detected');
            return $analysis;
        }

        // Extract ALL objects mentioned on surfaces in the analysis
        $surfaceObjects = [];
        // Match patterns like "iced coffee cup on the counter", "stack of white cups on a shelf"
        if (preg_match_all('/\b((?:iced |plastic |white |orange |red |large |small |stack of |pile of )?(?:coffee |caramel )?(?:cup|cups|dispenser|straw dispenser|bottle|glass|container|mug|can|lid|tray|plate|bowl|napkin|menu|box|bag)s?)\b/i', $analysis, $objectMatches)) {
            $surfaceObjects = array_unique(array_map('strtolower', $objectMatches[1]));
        }

        // Also extract specific named objects mentioned as "undisturbed" or "remained in place"
        $undisturbedClaims = [];
        if (preg_match_all('/\b([\w\s]+?)\s*(?:remain(?:s|ed)?|stay(?:s|ed)?)\s*(?:undisturbed|in place|stationary|unmoved|on the|intact)/i', $analysis, $undisturbedMatches)) {
            $undisturbedClaims = array_map('trim', $undisturbedMatches[1]);
        }

        // Check for blanket "undisturbed" claims
        $blanketUndisturbed = preg_match('/(?:all (?:other )?objects (?:on|remain)|remain(?:s|ed)? (?:completely |totally )?undisturbed|no (?:significant |notable )?displacement|no objects (?:were |are )?displaced)/i', $analysis);

        $needsRecheck = !empty($surfaceObjects) || $blanketUndisturbed || !empty($undisturbedClaims);

        if (!$needsRecheck) {
            Log::info('ConceptCloner: Object displacement validation passed — no surface objects found to verify');
            return $analysis;
        }

        $objectList = !empty($surfaceObjects) ? implode(', ', array_slice($surfaceObjects, 0, 15)) : 'various objects on surfaces';
        $undisturbedList = !empty($undisturbedClaims) ? implode(', ', $undisturbedClaims) : '';

        Log::warning('ConceptCloner: Object displacement verification needed — re-querying Gemini', [
            'surfaceObjects' => $surfaceObjects,
            'undisturbedClaims' => $undisturbedClaims,
            'blanketUndisturbed' => (bool) $blanketUndisturbed,
        ]);

        // Build a targeted re-query that names specific objects
        $specificChallenge = '';
        if ($blanketUndisturbed || !empty($undisturbedClaims)) {
            $specificChallenge = "\n\nIMPORTANT: A previous analysis claimed that some objects were \"undisturbed\" during the action. This may be WRONG. Do NOT assume objects stayed in place. Watch the video frame by frame and verify EACH object independently.";
            if (!empty($undisturbedList)) {
                $specificChallenge .= "\nSpecifically claimed as undisturbed: {$undisturbedList}. Verify each of these carefully.";
            }
        }

        $recheckPrompt = <<<PROMPT
Watch this video frame by frame. The video contains INTENSE physical action.

The following objects were identified on surfaces (counters, tables, shelves, etc.): {$objectList}
{$specificChallenge}

YOUR TASK: For EACH of these objects, watch what happens to it during the video. Track it from start to end.

For EACH object, report ONE of:
- DISPLACED: "[object name]" — knocked off/fell/slid/scattered at approximately [timestamp] because [cause]. Landed [where].
- STAYED: "[object name]" — verified it remained in place throughout the video.
- UNCLEAR: "[object name]" — object went out of frame / could not verify.

RULES:
- Check EVERY object listed above, do not skip any.
- When characters move aggressively on or near a surface, objects almost always get displaced. Look carefully.
- Pay special attention to the moments when characters jump, lunge, swat, or land on surfaces.
- If an object disappears from view during action, it was likely displaced — report it as DISPLACED unless you can see it still in place in a later frame.
- Do NOT assume objects stayed in place. VERIFY by looking at the frames AFTER the action.
PROMPT;

        try {
            $recheckResult = $geminiService->analyzeVideoWithPrompt(
                $fileUri,
                $recheckPrompt,
                ['mimeType' => $mimeType, 'temperature' => 0.05]
            );

            if ($recheckResult['success'] && !empty($recheckResult['text'])) {
                $recheckText = $recheckResult['text'];
                Log::info('ConceptCloner: Object displacement verification result', [
                    'preview' => mb_substr($recheckText, 0, 500),
                ]);

                // Check if any displacement was found
                $hasDisplacement = preg_match('/DISPLACED/i', $recheckText);
                if ($hasDisplacement) {
                    // Extract displaced object names from re-query results
                    $displacedObjects = [];
                    // Match: DISPLACED (with optional markdown **) ... "object name" —
                    // Handles: **DISPLACED:** "iced coffee cup" —  and  DISPLACED: "cup" —
                    if (preg_match_all('/DISPLACED[^"]*"([^"]+)"\s*(?:—|--)/i', $recheckText, $dMatches)) {
                        $displacedObjects = array_map(function($name) {
                            return trim(strtolower($name));
                        }, $dMatches[1]);
                    }

                    // Replace individual per-object "undisturbed/remained" claims for displaced objects
                    foreach ($displacedObjects as $objName) {
                        // Escape for regex, match patterns like "Iced Coffee Cup: Remains undisturbed" or "**Iced Coffee Cup:** Remained in place"
                        $escaped = preg_quote($objName, '/');
                        // Make flexible: allow partial matches (e.g. "coffee cup" matches "Iced Coffee Cup")
                        $words = explode(' ', $escaped);
                        $keyWord = count($words) > 1 ? $words[count($words) - 1] : $escaped; // use last word as anchor
                        $analysis = preg_replace(
                            '/\*{0,2}[^*\n]*' . $keyWord . '[^*\n]*\*{0,2}\s*[:—-]\s*(?:Remain(?:s|ed)?|Stay(?:s|ed)?)[^\n]*(?:undisturbed|in place|stationary|unmoved|intact)[^\n]*/i',
                            '**' . ucwords($objName) . ':** DISPLACED (corrected by frame-by-frame verification)',
                            $analysis
                        );
                    }

                    // Remove blanket "undisturbed" claims in various formats:
                    // "all objects remained undisturbed", "remain on the counter, undisturbed"
                    // "OBJECTS THAT STAYED: The iced coffee cup... remain... undisturbed"
                    $analysis = preg_replace(
                        '/(?:all (?:other )?objects (?:on the |on a )?(?:counter|table|surface|desk|shelf)?.*?(?:remain(?:s|ed)?|stay(?:s|ed)?).*?(?:undisturbed|in place|stationary|unmoved|intact))[.\s]*/i',
                        '',
                        $analysis
                    );
                    // Also catch: "The iced coffee cup, the stack of lids, and the straw dispenser remain on the counter, undisturbed."
                    $analysis = preg_replace(
                        '/(?:The [\w\s,]+(?:and the [\w\s]+)?remain(?:s|ed)?\s+(?:on the |on a )?[\w\s]*,?\s*undisturbed)[.\s]*/i',
                        '',
                        $analysis
                    );
                    // Remove "OBJECTS THAT STAYED:" lines that list items as undisturbed
                    $analysis = preg_replace(
                        '/\*{0,2}OBJECTS THAT STAYED:?\*{0,2}[^\n]*(?:undisturbed|in place|remained)[^\n]*/i',
                        '',
                        $analysis
                    );

                    // Replace the entire Object Displacement section if it exists
                    // Handles: "OBJECT DISPLACEMENT TRACKING", "Object Displacement Summary:", etc.
                    $sectionPattern = '/(?:\*{1,2}\s*)?(?:####?\s*)?(?:OBJECT DISPLACEMENT(?:\s+TRACKING|\s+SUMMARY)?):?[^\n]*\n/i';
                    if (preg_match($sectionPattern, $analysis)) {
                        $analysis = preg_replace(
                            '/((?:\*{1,2}\s*)?(?:####?\s*)?(?:OBJECT DISPLACEMENT(?:\s+TRACKING|\s+SUMMARY)?):?[^\n]*\n)(.+?)(?=\n(?:###|\*\*\*|---|\d+\.\s|$))/is',
                            "$1" . $recheckText . "\n",
                            $analysis
                        );
                        Log::info('ConceptCloner: Replaced Object Displacement section with corrected version');
                    } else {
                        $analysis .= "\n\n--- OBJECT DISPLACEMENT (verified frame-by-frame) ---\n" . $recheckText;
                    }

                    // CRITICAL: Inject displacement summary at TOP of analysis so synthesis AI sees it first.
                    // When Gemini omits displacement from the action timeline entirely (no false claims to replace),
                    // the correction at the bottom gets ignored by synthesis AI. Putting it at the top forces attention.
                    if (!empty($displacedObjects)) {
                        $displacementLines = [];
                        foreach ($displacedObjects as $objName) {
                            $escaped = preg_quote($objName, '/');
                            // Extract timestamp and cause from re-query for this object
                            if (preg_match('/DISPLACED[^"]*"' . $escaped . '"[^—]*(?:—|--)\s*([^\n]+)/i', $recheckText, $causeMatch)) {
                                $displacementLines[] = '- "' . ucwords($objName) . '": ' . trim($causeMatch[1]);
                            } else {
                                $displacementLines[] = '- "' . ucwords($objName) . '": DISPLACED during the action';
                            }
                        }
                        $alert = "⚠️ FRAME-BY-FRAME VERIFIED OBJECT DISPLACEMENT (MUST appear in videoPrompt):\n"
                            . implode("\n", $displacementLines)
                            . "\n---\n\n";
                        $analysis = $alert . $analysis;
                    }

                    Log::info('ConceptCloner: Object displacement correction applied', [
                        'displacedObjects' => $displacedObjects,
                    ]);
                } else {
                    Log::info('ConceptCloner: Verification confirmed objects stayed in place');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ConceptCloner: Object displacement verification failed, continuing with original analysis', [
                'error' => $e->getMessage(),
            ]);
        }

        return $analysis;
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
   - Describe EVERY physical action: pushing, throwing, knocking things over, swatting, chasing, jumping, dancing, swaying, bouncing, bobbing, shuffling, performing, etc.
   - Note the EXACT SECOND when each new action begins
   - Describe what EACH character does independently at each phase

   CRITICAL — OBJECT CONSEQUENCES WITHIN EACH ACTION BEAT:
   When describing each action beat, you MUST also describe what happens to NEARBY OBJECTS as a DIRECT RESULT of that action. Do NOT separate object effects from the action that caused them.
   - When a character swats, lunges, jumps on, or moves aggressively near a surface with objects — LOOK AT THE OBJECTS in the next frames. Did they move? Fall? Fly off? Get knocked over?
   - Include object displacement AS PART OF the action sentence, not as a separate section.
   WRONG: "The cat swats at the man's hand." (what happened to the cup RIGHT NEXT to the cat's paw?)
   RIGHT: "The cat swats at the man's hand, its paw striking the iced coffee cup and sending it flying off the counter to the floor."
   WRONG: "The cat leaps from behind the counter onto the man's back." (did ANYTHING on the counter move when the cat pushed off?)
   RIGHT: "The cat pushes off the counter and leaps onto the man's back, the force sending the straw dispenser and stack of cups scattering across the counter."
   - EVERY aggressive action near objects has CONSEQUENCES. A cat leaping off a counter displaces items. A person slamming a table knocks things over. A struggle near a shelf sends items falling. If you describe an aggressive action but claim nearby objects "remained undisturbed" — you are almost certainly WRONG. Watch the frames AFTER the action and report what moved.
   - At the END of the timeline, provide a summary: "OBJECTS DISPLACED: [list each object and what happened]" and "OBJECTS THAT STAYED: [list]"

   - MOVEMENT QUALITY CHANGES — CRITICAL, DO NOT SKIP:
     * Does the character's movement TYPE change at any point? (walking → dancing, standing → swaying, sitting → bouncing)
     * Does movement become RHYTHMIC, SYNCHRONIZED, or DANCE-LIKE at any point? If so, at what second?
     * Does the body sway, bounce, bob, shuffle, or groove in a pattern? Describe the specific dance/rhythmic movement.
     * If music starts playing, does the character's movement CHANGE or appear to synchronize with the beat?
     * Even subtle changes matter: a bear walking normally then starting to sway its hips counts as a movement change.
     * IMPORTANT: Do not flatten a multi-phase video into one description. If the bear walks for 5 seconds then dances for 5 seconds, both phases MUST be described separately.
   - ACTION INTENSITY RATING: Rate the peak physical intensity of the scene:
     * CALM: characters mostly standing still, gentle movements, talking
     * MODERATE: some physical movement, light gestures, minor comedy
     * INTENSE: aggressive movements, throwing, pushing, fast actions
     * EXTREME/WILD: chaotic, things breaking/flying, characters leaping, total pandemonium
   - What NON-DESTRUCTIVE notable actions happen? (dancing, singing, performing, gesturing, playing, mimicking, posing, flexing)

3b. AUDIO & SOUND ANALYSIS (you can hear the actual audio):
   - Report ONLY sounds you can actually HEAR in the audio track. Do NOT infer sounds from visual cues.
   - What sounds do you hear? List them: human speech, animal sounds, background noise, music
   - Is there a VOICEOVER/NARRATION? (a human voice talking OVER the video, not from a character on screen)
   - Which sounds come FROM characters on screen vs. dubbed/added audio?
   - CRITICAL FOR ANIMALS: Do NOT infer sounds from visual cues — an open mouth could be panting, breathing, or yawning. Report ONLY vocalizations you can actually HEAR.
     * If mouth is open but no vocalization heard → report "mouth open, no vocalization heard"
     * If no animal vocalization detected → explicitly state "No animal vocalizations detected in audio"
     * Only report specific sounds (meowing, barking, hissing, growling) if you can genuinely HEAR them
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
    protected function synthesizeConcept(string $visualAnalysis, ?string $transcript, string $aiEngine, int $teamId, string $videoEngine = 'seedance', bool $chaosMode = false, string $templateId = 'adaptive'): array
    {
        $transcriptSection = $transcript
            ? "AUDIO TRANSCRIPT:\n\"{$transcript}\"\n\nCRITICAL AUDIO ANALYSIS:\n- This transcript was captured from the video's audio track.\n- On TikTok/Reels, human dialogue over animal videos is almost ALWAYS a dubbed voiceover/narration — the animal is NOT actually speaking.\n- If the visual analysis shows an ANIMAL with mouth open, the animal is making ANIMAL SOUNDS (meowing, barking, hissing, screaming) — NOT speaking human words.\n- The transcript above is likely a VOICEOVER narration added for comedy, NOT the animal's actual voice.\n- IMPORTANT FOR VOICEOVER TEXT: Strip out ALL animal sound words (meow, woof, bark, hiss, growl, etc.) from the voiceover narration. Only include the HUMAN SPEECH parts. If the transcript is 'This is not what I ordered! Meow meow meow! I asked for chicken!' the voiceover should be 'This is not what I ordered! I asked for chicken!' — no animal sounds in the voiceover.\n- The voiceover narration must contain ONLY clean human speech. Animal sounds happen VISUALLY in the scene, not in the voiceover audio."
            : "AUDIO: No speech detected in video. Assume visual comedy / silent humor with environmental sounds only.";

        $videoPromptInstruction = $videoEngine === 'seedance'
            ? 'Also generate a "videoPrompt" field — see SEEDANCE VIDEO PROMPT RULES at the end of this prompt.'
            : 'Do NOT generate a "videoPrompt" field.';

        // Get template rules for Seedance video prompt
        $structureRules = $this->getTemplateStructureRules($templateId, 'clone');
        $technicalRules = $this->getCloneTechnicalRules();
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
- ANIMAL SOUNDS — ONLY IF HEARD: If the visual analysis reports animal vocalizations were HEARD in the audio (meowing, hissing, barking, growling), include them as actions in the scene. If the analysis says "No animal vocalizations detected" or only mentions panting/breathing, do NOT add animal sounds. Animals NEVER speak human words.
- VOICEOVER vs CHARACTER SOUNDS: The audio transcript is likely a VOICEOVER narration dubbed over the video. Animals make their natural sounds. Voiceover goes in the official Seedance voiceover format.
- The main character (camera focus) should be described FIRST in the videoPrompt.
- ABSOLUTELY NO background music in the videoPrompt. NEVER write "music plays", "upbeat music", "beat drops", "soundtrack", or any music mention. Seedance generates audio from the prompt text — any music reference causes unwanted background music. Only character sounds, dialogue, and physical sound effects.

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
  "videoPrompt": "SEE SYSTEM RULES + SEEDANCE RULES BELOW — 120-200 words. Natural vivid narrative: one sentence per phase with SPECIFIC body parts, motions, directions, dialogue in quotes, character sounds, and camera style.",
  "cameraFixed": true or false,
  "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
  "viralHook": "Why this would go viral (one sentence)",
  "source": "cloned"
}

=======================================================================
SEEDANCE VIDEO PROMPT RULES — READ THIS LAST, FOLLOW EXACTLY
=======================================================================

The "videoPrompt" is THE MOST IMPORTANT FIELD. It drives the actual video generation.
You are CLONING a reference video — capture the ENERGY and CONCEPT of the reference FAITHFULLY.

WORD COUNT: 120-200 words. Write a vivid natural narrative with specific motions, body parts, directions, dialogue in quotes, character sounds, and camera style.
Build as ordered action beats following the COMPLETE action timeline from the analysis. End with "Cinematic, photorealistic."
CRITICAL — EVERY ACTION PHASE MUST BE REPRESENTED:
- The analysis describes a second-by-second action timeline. Each distinct action phase MUST appear as at least one sentence in the videoPrompt.
- Do NOT skip, merge, or compress any action phase. If the analysis describes 8 phases, the prompt needs ~8 action sentences.
- A cat leaping, a man flinching, a struggle, a throw — these are SEPARATE actions requiring SEPARATE sentences.
- Do NOT jump from "cat clings to back" to "man throws cat" — the STRUGGLE phase between them is critical.
Use natural adverbs freely: rapidly, violently, aggressively, wildly, fiercely, crazily, intensely, gently, steadily, desperately.
Connect actions with temporal words: then, instantly, after, finally.
NO scene descriptions, NO appearance/clothing — only actions, motions, dialogue, sounds, and camera style.
EMOTIONAL STATE MUST BE PART OF EACH ACTION — not separate, not optional:
- WRONG: "The cat opens its mouth wide then lunges forward." (mechanical, emotionless — Seedance renders a yawn, not an attack)
- RIGHT: "The cat violently opens its mouth wide in aggressive fury then crazily lunges forward." (anger is VISIBLE, Seedance renders aggression)
- WRONG: "The man recoils, turning away." (no emotion — could be casual)
- RIGHT: "The man crazily recoils in surprise, turning away." (shock is VISIBLE)
- If the analysis says a character is angry, aggressive, surprised, distressed, defiant — that emotion MUST appear as an adjective/phrase IN the action sentence.
- Ears flattening, body tensing aggressively, teeth baring = PHYSICAL details that define HOW the action looks.
- DO NOT write onomatopoeia (SMACK, THUD, crash, bang) but DO include character dialogue in quotes and character sounds (meows, yells, screams) — these drive Seedance's audio generation.

CRITICAL — IMAGE-TO-VIDEO (NO SCENE DESCRIPTION IN videoPrompt):
The videoPrompt is sent to Seedance together with the SOURCE IMAGE. The image already shows the scene, setting, characters, props, and starting positions.
Therefore the videoPrompt must contain ONLY actions, movements, dialogue, sounds, and camera style — things that CHANGE from the starting image.
- DO NOT describe the scene, setting, location, lighting, or background — the image shows it.
- DO NOT describe character appearances, clothing, postures, or starting positions — the image shows it.
- DO NOT describe what objects are on tables/counters/floors in their RESTING state — the image shows them.
- BUT DO describe objects being DISPLACED, KNOCKED OFF, SCATTERED, or SENT FLYING during character actions — that is an ACTION, not a scene description. Example: "cups and items violently scatter off counter" is an action beat, not a prop description.
- DO NOT include face/identity preservation instructions — the image defines the face.
- Start DIRECTLY with the first physical action beat.
- You may name objects AS PART OF actions (e.g. "grips the shawarma powerfully", "cups fly off counter") but do NOT list/describe them in their resting state.
EXCEPTION: If characters are UNUSUALLY SIZED (miniaturized, enlarged, tiny, giant), you MUST mention this — e.g. "tiny miniature cat barely reaching ankle height" — because Seedance needs this to render the correct scale.

{$structureRules}

{$technicalRules}

=== CLONE FAITHFULNESS OVERRIDES ===
1. DIALOGUE & SOUNDS ARE ESSENTIAL: Include character dialogue in quotes from the audio transcript. Include character sounds (meows, yells, screams, growls) — these drive Seedance's audio generation. Do NOT write onomatopoeia (SMACK, THUD, crash, bang) or music references.
2. ENERGY MATCHING: Match the ACTUAL energy level. Calm scene = gently/steadily. Intense scene = violently/aggressively/wildly.
3. Do NOT fabricate actions. Only describe what the analysis confirms happened.
4. CAMERA STYLE: If the analysis describes the camera movement (handheld, tracking, static), include it in the videoPrompt.
5. OBJECT DISPLACEMENT IS AN ACTION: If the analysis describes objects being knocked off surfaces, scattered, or sent flying during character actions, this MUST appear in the videoPrompt as cause-and-effect within the action sentence.

NOW generate the JSON. For videoPrompt, follow these steps IN ORDER:

STEP 1: List every action phase from the analysis timeline (e.g., 0:00-0:02 = setup, 0:02-0:03 = escalation, ... 0:08-0:09 = resolution).
STEP 2: For EACH phase, write ONE sentence capturing the SPECIFIC physical action:
   - WHO does it (the customer, the cat, both)
   - WHAT body part (hand, paw, mouth, legs, arms, chest)
   - WHAT specific motion (leans forward, opens mouth wide, swats with right paw, jumps off counter, scoops up from floor, flails front and hind legs)
   - WHAT direction/result (toward the cat, to the floor, around the counter, against his chest)
   - Use adverbs: rapidly, violently, crazily, intensely, swiftly, gently, steadily, smoothly
   WRONG: "The cat attacks the man." (too vague)
   RIGHT: "The cat violently swats at his outstretched hand with right paw in aggressive fury."
   WRONG: "The man grabs the cat." (no detail on HOW)
   RIGHT: "The customer rapidly bends down and scoops up the struggling cat from the floor."
STEP 3: CHECK FOR OBJECT DISPLACEMENT. Re-read the analysis — did ANY objects get knocked off, scattered, displaced, or sent flying? If yes, that MUST appear in your videoPrompt. Objects falling off counters/tables during chaos are ESSENTIAL visual elements. If the analysis says "cup falls off counter" or "items scatter" and your videoPrompt doesn't mention it, you FAILED.
STEP 4: LAST sentence MUST be the FINAL phase (resolution/departure/exit). If the subject walks away, holds something up, or gives up — that is the ending. Do NOT stop at the climax and skip the resolution.
STEP 5: ADD DIALOGUE & SOUNDS. Extract key dialogue from the audio transcript and include it in quotes. Include character sounds (meows, yells, screams). These drive Seedance's audio generation.
STEP 6: ADD CAMERA STYLE if notable (e.g., "A chaotic, shaking handheld camera follows the action").
STEP 7: End with "Cinematic, photorealistic."

The videoPrompt MUST be 120-200 words. Count your words before outputting. Use the FULL budget — do NOT stop early when there are more action phases to cover.
PROMPT;

        if ($chaosMode) {
            $prompt .= "\n\n" . $this->getChaosModeSupercharger();
        }

        // Count action phases from the visual analysis to set exact expectations
        preg_match_all('/\d+:\d+[-–]\d+:\d+/', $visualAnalysis, $phaseMatches);
        $phaseCount = count($phaseMatches[0]);
        if ($phaseCount < 3) $phaseCount = 7; // fallback if parsing fails
        $targetWords = $phaseCount * 15; // 15 words per sentence average

        // Use system/user message split for better instruction following.
        // System message contains the critical videoPrompt rules that must always be followed.
        // User message contains the analysis data and JSON template.
        $systemMessage = <<<SYSTEM
You are a Seedance 1.5 Pro video prompt specialist. Your #1 job is generating the "videoPrompt" field — a vivid, natural narrative describing ALL actions in the video.

The analysis contains {$phaseCount} action phases. Your videoPrompt MUST cover ALL {$phaseCount} phases — especially the FINAL resolution/departure beat.

WRITING STYLE — NATURAL NARRATIVE:
Write as if you're vividly narrating the scene to someone who can't see it. Use natural, descriptive language.
- INCLUDE dialogue in quotes: yells "How can you ruin this?" or screams "Get off me!"
- INCLUDE character sounds: meows, yells, screams, growls, hisses — these drive accurate audio generation
- INCLUDE camera style when notable: "A chaotic, shaking handheld camera follows the action"
- INCLUDE emotional states as part of actions: "leans aggressively", "angrily points", "desperately struggles"
- INCLUDE specific body parts: "slaps the man's face with its right paw", "claws gripping wildly"
- INCLUDE object displacement as cause-and-effect: "jumps onto the counter and violently knocks over the iced coffee cup"

EXAMPLE — Good videoPrompt narrative:
"The man leans aggressively over the coffee shop counter and yells 'How can you ruin such a bad iced coffee? There's no caramel in my caramel swirl!' Angrily points at the cat in the orange hat. The cat meows repeatedly and slaps the man's face with its right paw. Then, in a quick, powerful motion, the cat jumps onto the counter and violently knocks over the iced coffee cup and other items, then the cat jumps again and lands violently on the man's left shoulder, its claws gripping wildly at high frequency and speed. A chaotic, shaking handheld camera follows the action. Cinematic, photorealistic."

VIDEOPROMPT RULES:
1. ONE sentence per action phase. {$phaseCount} phases = {$phaseCount} sentences. Do NOT skip or merge any phase.
2. Use natural adverbs freely: rapidly, violently, aggressively, wildly, fiercely, powerfully, crazily, intensely, slowly, gently, steadily, desperately, furiously — whatever fits the action.
3. SPECIFICITY IS CRITICAL: Say "swats at his hand with right paw" not "attacks him". Say "claws gripping wildly at high frequency" not "struggles". The SPECIFIC body part and direction make the video accurate.
4. DIALOGUE: Extract key dialogue from the audio transcript and include it in quotes within the action. This drives Seedance's audio generation.
5. SOUNDS: Include character vocalizations (meows, yells, screams, growls) — Seedance uses these for audio.
6. WORD COUNT: 120-200 words. Use the full budget — do NOT stop early.
7. LAST SENTENCE = the FINAL action (resolution/departure/exit), NOT the climax. If the video ends with someone leaving, holding something, or giving up — that MUST be the last sentence.
8. End with "Cinematic, photorealistic."
9. Every sentence describes a DIFFERENT action — no repetition.
10. BANNED: NO appearance/clothing descriptions (Seedance uses the reference image). NO background music references. NO facial micro-expressions (pupils dilating, brows furrowing).

CRITICAL — OBJECT DISPLACEMENT:
If objects are knocked off, scattered, displaced, or sent flying, this MUST appear in the videoPrompt as cause-and-effect within the action sentence.
CORRECT: "The cat crazily jumps onto the counter, violently knocking the iced coffee cup and straw dispenser off the counter to the floor."
WRONG: "The cat crazily jumps onto the counter." (MISSING the objects — the generated video will look flat and wrong)
SYSTEM;

        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $prompt],
        ];

        $result = $this->callAIWithEngine($messages, $aiEngine, $teamId, [
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
            Log::warning('ConceptCloner: Synthesis JSON parse failed, attempting repair', [
                'error' => json_last_error_msg(),
                'responsePreview' => substr($response, 0, 500),
                'responseTail' => substr($response, -200),
                'responseLength' => strlen($response),
            ]);
            $response = $this->repairTruncatedJson($response);
            $concept = json_decode($response, true);
        }

        if (!$concept || !isset($concept['title'])) {
            Log::error('ConceptCloner: Failed to parse synthesized concept after repair', [
                'hasData' => !empty($concept),
                'keys' => $concept ? array_keys($concept) : [],
                'responsePreview' => substr($response, 0, 800),
            ]);
            throw new \Exception('Failed to parse synthesized concept');
        }

        // Ensure source is tagged
        $concept['source'] = 'cloned';

        // Expand videoPrompt if under word target — GPT-4o tends to be too concise
        if (!empty($concept['videoPrompt'])) {
            $wordCount = str_word_count($concept['videoPrompt']);
            if ($wordCount < 100) {
                Log::info("ConceptCloner: videoPrompt only {$wordCount} words, expanding to 120-150");
                $expandPrompt = [
                    ['role' => 'system', 'content' => "You rewrite Seedance 1.5 Pro video prompts to hit 120-150 words. Keep the SAME actions in the SAME order. Expand short sentences by adding: body parts (arms, paws, chest, fingers), directions (forward, backward, upward), emotional states as part of actions (leans aggressively, angrily points), dialogue in quotes from the scene, character sounds (meows, yells, screams). Use natural adverbs freely. PRESERVE existing dialogue and sounds — never remove them. Return ONLY the rewritten prompt, nothing else."],
                    ['role' => 'user', 'content' => "This prompt is only {$wordCount} words. Expand each sentence to reach 120-150 total:\n\n{$concept['videoPrompt']}"],
                ];
                $expandResult = $this->callAIWithEngine($expandPrompt, $aiEngine, $teamId, [
                    'maxResult' => 1,
                    'max_tokens' => 500,
                ]);
                if (empty($expandResult['error']) && !empty($expandResult['data'][0])) {
                    $expanded = trim($expandResult['data'][0]);
                    $expandedWordCount = str_word_count($expanded);
                    // Only use expansion if it's actually longer and not too long
                    if ($expandedWordCount >= 110 && $expandedWordCount <= 200) {
                        $concept['videoPrompt'] = $expanded;
                        Log::info("ConceptCloner: Expanded to {$expandedWordCount} words");
                    } else {
                        Log::info("ConceptCloner: Expansion returned {$expandedWordCount} words, keeping original");
                    }
                }
            }
        }

        // Sanitize videoPrompt to fix banned words that the AI keeps using
        if (!empty($concept['videoPrompt'])) {
            $concept['videoPrompt'] = self::sanitizeSeedancePrompt($concept['videoPrompt']);
        }

        return $concept;
    }

    // ========================================================================
    // FIT TO SKELETON — Rewrite raw videoPrompt to match proven structure
    // ========================================================================

    /**
     * Rewrite a raw videoPrompt to follow the proven Seedance skeleton structure.
     * Detects energy type from mood and applies the matching skeleton template.
     *
     * This is critical for clone mode where the AI generates accurate but unstructured
     * prompts that don't follow the sentence-by-sentence skeleton pattern.
     *
     * @param string $rawPrompt The raw videoPrompt from synthesis
     * @param array $concept Full concept array (needs 'mood', 'characters', 'setting', 'situation')
     * @param string $aiEngine AI model tier to use
     * @param int $teamId Team ID for credit tracking
     * @param string $templateId Template to use ('adaptive', 'animal-chaos')
     * @return array ['skeletonType' => string, 'originalPrompt' => string, 'fittedPrompt' => string]
     */
    public function fitPromptToSkeleton(string $rawPrompt, array $concept, string $aiEngine, int $teamId, string $templateId = 'adaptive', array $chaosParams = []): array
    {
        $mood = strtolower($concept['mood'] ?? 'funny');

        // Extract chaos values
        $chaosLevel = (int) ($chaosParams['chaosLevel'] ?? 0);
        $chaosMode = (bool) ($chaosParams['chaosMode'] ?? false);
        $chaosDescription = $chaosParams['chaosDescription'] ?? '';

        // Detect energy type from mood
        $energyType = $this->detectEnergyType($mood, $templateId);

        // MAXIMUM OVERDRIVE (86+) forces CHAOTIC energy regardless of detected mood
        if ($chaosLevel >= 86) {
            $energyType = 'CHAOTIC';
        }

        // Get the specific skeleton for this energy type
        $skeletons = $this->getSkeletonTemplates($templateId);
        $skeleton = $skeletons[$energyType] ?? $skeletons['PHYSICAL COMEDY'] ?? reset($skeletons);

        $example = $this->getSkeletonExample($templateId, $energyType);

        // Build character context for the AI
        $characterContext = '';
        if (!empty($concept['characters'])) {
            $chars = [];
            foreach ($concept['characters'] as $char) {
                $name = $char['name'] ?? 'Character';
                $desc = $char['description'] ?? '';
                $role = $char['role'] ?? '';
                $chars[] = "- {$name} ({$role}): {$desc}";
            }
            $characterContext = "CHARACTERS:\n" . implode("\n", $chars);
        }

        // Build the dialogue trigger from concept if available
        $dialogueLine = '';
        if (!empty($concept['dialogueLines'])) {
            foreach ($concept['dialogueLines'] as $line) {
                if (!empty($line['text']) && ($line['speaker'] ?? '') !== 'Voiceover') {
                    $dialogueLine = $line['text'];
                    break;
                }
            }
        }
        $dialogueContext = $dialogueLine ? "\nDIALOGUE FROM SOURCE: \"{$dialogueLine}\"" : '';

        // Build chaos scaling block for the skeleton prompt
        $chaosScalingBlock = '';
        if ($chaosLevel > 0) {
            $chaosLabel = match (true) {
                $chaosLevel <= 20 => 'CALM',
                $chaosLevel <= 45 => 'MODERATE',
                $chaosLevel <= 65 => 'HIGH',
                $chaosLevel <= 85 => 'PEAK',
                default => 'MAXIMUM OVERDRIVE',
            };
            $degreeOverride = $this->getChaosDegreeInstruction($chaosLevel, $energyType);
            $supercharger = $chaosMode ? "\n" . $this->getChaosModeSupercharger() : '';

            $chaosScalingBlock = "\n\nCHAOS SCALING ({$chaosLabel} — {$chaosLevel}/100):\n{$degreeOverride}{$supercharger}\n";
        }

        $prompt = <<<PROMPT
Rewrite the source material below following the MANDATORY STRUCTURE. Match the energy and beat pattern EXACTLY.

REFERENCE (match this flow and energy):
{$example}

MANDATORY STRUCTURE — follow these beats EXACTLY:
{$skeleton}

SOURCE MATERIAL (use characters, dialogue, objects — NOT its sentence structure):
Situation: {$concept['situation']}{$dialogueContext}
{$characterContext}
Raw: "{$rawPrompt}"

SEEDANCE TECHNICAL RULES — apply to ALL content:
- NEVER use emotional adjectives: frustrated, angry, feisty, furious, terrified, desperate, pained, mischievous, satisfied, playful, joyful, content, smug.
- NEVER use banned adverbs: tightly, briefly, crazily, precariously, fiercely, loudly, sharply, aggressively.
- ONLY use these degree words: quickly, violently, with large amplitude, at high frequency, powerfully, wildly, crazy, fast, intense, strong, greatly.
- Use "crazy" (adjective) NOT "crazily". Use "strong" NOT "strongly". Use "intense" NOT "intensely".
- Use "at high frequency" NOT "high-frequency". Use "crazy loud" NOT "high-pitched".
- NO clothing/appearance descriptions (jacket, shirt, hoodie, fur color). Identify characters by type/body only.
- NO facial expression descriptions (eyes widening, brow furrowing, mouth curving). Convey emotion through BODY ACTIONS.
- NO camera references (toward camera, camera shakes). Describe character direction only.
- NO weak verbs: walks, goes, moves, does, gets, starts, begins, tries.
- MUST end with "Cinematic, photorealistic." — this is NOT optional.
- MUST NOT include face/identity prefix text — the source image defines the face.
- MUST NOT include scene/setting descriptions — the source image shows the scene.
- Start directly with the first physical action beat.
{$chaosScalingBlock}
Output ONLY the rewritten prompt. Nothing else.
PROMPT;

        $result = $this->callAIWithEngine($prompt, $aiEngine, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 500, // Hard cap — 180 words ≈ 250 tokens, leave margin for formatting
        ]);

        if (!empty($result['error'])) {
            Log::warning('ConceptService: fitPromptToSkeleton AI call failed, using original', [
                'error' => $result['error'],
                'energyType' => $energyType,
            ]);
            return [
                'skeletonType' => $energyType,
                'originalPrompt' => $rawPrompt,
                'fittedPrompt' => $rawPrompt,
            ];
        }

        $fittedPrompt = trim($result['data'][0] ?? '');

        // Remove any wrapping quotes the AI might add
        if (preg_match('/^"(.*)"$/s', $fittedPrompt, $m)) {
            $fittedPrompt = $m[1];
        }
        $fittedPrompt = trim($fittedPrompt, "'");

        // Sanitize the fitted prompt (AI may reintroduce banned words)
        $fittedPrompt = self::sanitizeSeedancePrompt($fittedPrompt);

        // Strip any face prefix the AI may have added (wastes word budget — image defines the face)
        $fittedPrompt = preg_replace('/Maintain face[^.]*\.(\s*Character face[^.]*\.)?\s*/i', '', $fittedPrompt);
        // Strip scene/setting descriptions the AI may have added
        $fittedPrompt = preg_replace('/^(?:In\s+(?:a|an|the)\s+[^.]+\.\s*)+/i', '', $fittedPrompt);
        $fittedPrompt = preg_replace('/^(?:(?:Inside|Within|At|On)\s+(?:a|an|the)\s+[^.]+\.\s*)+/i', '', $fittedPrompt);

        // Fix truncation — if prompt ends mid-sentence, trim to last complete sentence
        $fittedPrompt = rtrim($fittedPrompt);
        if (!preg_match('/[.!"]$/', $fittedPrompt)) {
            $lastPeriod = strrpos($fittedPrompt, '.');
            $lastExclamation = strrpos($fittedPrompt, '!');
            $lastQuote = strrpos($fittedPrompt, '"');
            $cutPoint = max($lastPeriod ?: 0, $lastExclamation ?: 0, $lastQuote ?: 0);
            if ($cutPoint > 50) {
                $fittedPrompt = substr($fittedPrompt, 0, $cutPoint + 1);
            }
        }

        // Ensure it ends with the style anchor (template-aware)
        if (!str_contains($fittedPrompt, 'Cinematic, photorealistic.')) {
            $fittedPrompt = rtrim($fittedPrompt, '. ');
            if (($energyType === 'CHAOTIC' || $templateId === 'animal-chaos') && !str_contains($fittedPrompt, 'screaming throughout')) {
                $fittedPrompt .= '. Continuous crazy aggressive screaming throughout. Cinematic, photorealistic.';
            } else {
                $fittedPrompt .= '. Cinematic, photorealistic.';
            }
        }

        // Word count enforcement — template-aware limits
        $wordCount = str_word_count($fittedPrompt);
        $maxWords = match ($energyType) {
            'GENTLE' => 120,       // No face prefix — pure action prompts are shorter
            'PHYSICAL COMEDY' => 140,
            'CHAOTIC' => 160,
            'RHYTHMIC' => 130,
            'DRAMATIC' => 140,
            default => 140,
        };
        $trimTarget = match ($energyType) {
            'GENTLE' => 100,
            'PHYSICAL COMEDY' => 120,
            'CHAOTIC' => 140,
            'RHYTHMIC' => 110,
            'DRAMATIC' => 120,
            default => 120,
        };
        if ($wordCount > $maxWords) {
            Log::warning('ConceptService: fitPromptToSkeleton exceeded word limit, trimming', [
                'wordCount' => $wordCount,
                'maxWords' => $maxWords,
                'trimTarget' => $trimTarget,
                'energyType' => $energyType,
            ]);
            $fittedPrompt = $this->trimPromptToWordCount($fittedPrompt, $trimTarget);
        }

        Log::info('ConceptService: fitPromptToSkeleton completed', [
            'energyType' => $energyType,
            'originalWords' => str_word_count($rawPrompt),
            'fittedWords' => str_word_count($fittedPrompt),
            'chaosLevel' => $chaosLevel,
            'chaosMode' => $chaosMode,
        ]);

        return [
            'skeletonType' => $energyType,
            'originalPrompt' => $rawPrompt,
            'fittedPrompt' => $fittedPrompt,
            'chaosLevel' => $chaosLevel,
            'chaosMode' => $chaosMode,
        ];
    }

    /**
     * Detect the energy type from mood and template.
     */
    protected function detectEnergyType(string $mood, string $templateId = 'adaptive'): string
    {
        // Animal Chaos is always CHAOTIC
        if ($templateId === 'animal-chaos') {
            return 'CHAOTIC';
        }

        return match ($mood) {
            'chaotic' => 'CHAOTIC',
            'absurd' => 'PHYSICAL COMEDY',
            'funny' => 'PHYSICAL COMEDY',
            'wholesome' => 'GENTLE',
            'cute' => 'GENTLE',
            default => 'PHYSICAL COMEDY',
        };
    }

    /**
     * Get individual skeleton templates by energy type.
     * These define the cinematic narrative formula for Seedance prompts.
     */
    protected function getSkeletonTemplates(string $templateId = 'adaptive'): array
    {
        // Animal Chaos has a single specialized skeleton
        if ($templateId === 'animal-chaos') {
            return [
                'CHAOTIC' => <<<'SKELETON'
BEAT 1 — TRIGGER (1 sentence): The human does something + says ONE punchy angry line in quotes, while gesturing. This is the spark. Keep it under 20 words.
BEAT 2 — INSTANT REACTION (1 sentence): Start with "Instantly" — the animal EXPLODES. One flowing sentence: animal sound (screeching/hissing) + first physical strike (lunging, swiping). Stack 2-3 degree words (wildly, violently, powerfully, fast).
BEAT 3 — CHAIN DESTRUCTION (1-2 sentences): The animal smashes a specific object. Describe the visual consequence (liquid splashing, items flying). Then escalate — another fierce sound + bigger physical action (launches onto human, tackles, knocks down).
BEAT 4 — PEAK CHAOS (1 sentence): The animal goes absolutely wild — smashing everything, running amok. The human retreats/runs. Write it as one flowing action, not a body-part list.
CLOSING (1 sentence): "Loud crash of falling items, continuous crazy aggressive [animal] screaming throughout. Camera shakes with chaotic handheld energy. Cinematic, photorealistic."

RULES:
- The animal drives ALL the action. Human only reacts (gasps, staggers, runs away).
- No human dialogue after the trigger. No narration. No setting inventory.
- Use degree words naturally in actions: "swiping wildly and powerfully", "lunging forward fast and violently"
- Write CINEMATICALLY — "both paws swiping wildly" NOT "front right paw at 45 degrees with large amplitude"
- 100-170 words total. Every sentence is action, not description.
SKELETON,
            ];
        }

        return [
            'GENTLE' => <<<'SKELETON'
BEAT 1 — SETUP (1 sentence): The character is doing something calm and ordinary. Start with the action, not a setting description.
BEAT 2 — SMALL MOMENT (1-2 sentences): Something small happens — a gentle nudge, a curious look, a small discovery. One degree word (quickly, fast). The character reacts with a small deliberate action.
BEAT 3 — GENTLE CHAIN (1-2 sentences): The small action causes a gentle chain reaction — something tips, slides, or rolls. Another small reaction follows naturally. Keep it flowing and warm.
CLOSING (1 sentence): "Soft [character sounds] throughout. Cinematic, photorealistic."

RULES:
- Start with action, not "In a cozy room..." setting descriptions
- 2-4 degree words total, all gentle (quickly, fast)
- Write cinematically — flowing actions, not technical descriptions
- 80-130 words total. Keep it simple and warm.
SKELETON,

            'PHYSICAL COMEDY' => <<<'SKELETON'
BEAT 1 — TRIGGER (1 sentence): Character does something + says a line or encounters a comedic situation. This is the setup for the physical comedy.
BEAT 2 — EXAGGERATED REACTION (1-2 sentences): Big physical reaction — starts with one action that snowballs. Stack 1-2 degree words per action (fast, powerfully, wildly). Write as flowing motion, not a body-part inventory.
BEAT 3 — CHAIN REACTION (1-2 sentences): The physical action causes objects to move, fall, or break. Describe the visible consequences. The comedy escalates through cause-and-effect.
CLOSING (1 sentence): "[Character sounds] throughout. Cinematic, photorealistic."

RULES:
- 6-10 degree words total (quickly, fast, powerfully, strong, wildly, intensely)
- 2+ chain reactions where one action causes the next disaster
- Write like a slapstick scene — flowing, visual, funny
- 100-150 words total.
SKELETON,

            'CHAOTIC' => <<<'SKELETON'
BEAT 1 — TRIGGER (1 sentence): Character says ONE punchy line in quotes while doing something. This spark ignites everything.
BEAT 2 — INSTANT REACTION (1 sentence): "Instantly" — explosive first strike. One flowing sentence with sound + physical action + 2-3 stacked degree words (wildly, violently, powerfully, fast).
BEAT 3 — CHAIN DESTRUCTION (1-2 sentences): Smash a specific object with visible consequences (splashing, crashing, flying). Escalate with another fierce action — launch, tackle, knock over. Write as flowing cinema, not a list.
BEAT 4 — PEAK CHAOS (1 sentence): Maximum destruction — goes wild, smashes everything, total mayhem. The other character retreats or gets overwhelmed.
CLOSING (1 sentence): "Loud crash of falling items, continuous crazy aggressive screaming throughout. Camera shakes with chaotic handheld energy. Cinematic, photorealistic."

RULES:
- 12-18 degree words total. Stack them naturally: "swiping wildly and powerfully", "lunging forward fast and violently"
- 3+ chain reactions where one action causes the next
- Write CINEMATICALLY — flowing sentences of action, NOT body-part inventories
- 100-170 words total. Every sentence is pure action.
SKELETON,

            'RHYTHMIC' => <<<'SKELETON'
BEAT 1 — ESTABLISH (1 sentence): Character starts a rhythmic action — tapping, bobbing, swaying. Begin with the motion, not a setting description.
BEAT 2 — LAYER (1-2 sentences): More of the character joins the rhythm. Head bobs, body sways, hands tap. Each new element adds to the groove. Write it as coordinated motion flowing naturally.
BEAT 3 — FULL SYNC (1-2 sentences): Everything is in sync — character, movement, and nearby objects start vibrating or rattling from the energy. The rhythm hits its peak groove.
CLOSING (1 sentence): "Continuous [character vocalizing] throughout. Cinematic, photorealistic."

RULES:
- 5-8 degree words total (quickly, fast, powerfully, at high frequency)
- Write as flowing rhythm, not step-by-step instructions
- 80-140 words total.
SKELETON,

            'DRAMATIC' => <<<'SKELETON'
BEAT 1 — STILLNESS (1 sentence): Character in a tense, still moment. One small detail hints at what's coming.
BEAT 2 — SMALL TELL (1 sentence): One tiny movement breaks the stillness — a twitch, a shift, a breath. Almost nothing, but loaded with tension.
BEAT 3 — BUILD (1-2 sentences): More movement, building intensity. Degree words start appearing. Objects around begin to react. The energy is rising.
BEAT 4 — EXPLOSION (1-2 sentences): Sudden burst — everything erupts at once. Maximum degree words stacked. Chain reactions everywhere. The contrast with the stillness makes it hit harder.
CLOSING (1 sentence): "Continuous [sounds] throughout. Cinematic, photorealistic."

RULES:
- 6-12 degree words total. Start with 0 in stillness, escalate to maximum at explosion.
- The power comes from CONTRAST — quiet tension then violent release.
- 100-150 words total.
SKELETON,
        ];
    }

    /**
     * Get a reference example that matches the energy type.
     * For chaotic energy, always use the animal-chaos example (proven viral formula).
     */
    protected function getSkeletonExample(string $templateId, string $energyType): string
    {
        // Animal-chaos template always uses its own example
        if ($templateId === 'animal-chaos') {
            return $this->getTemplateExample('animal-chaos');
        }

        // For adaptive template, match example to energy type
        $chaosExample = $this->getTemplateExample('animal-chaos');
        $adaptiveExample = $this->getTemplateExample('adaptive');

        return match ($energyType) {
            'CHAOTIC' => $chaosExample,
            'PHYSICAL COMEDY' => $chaosExample,  // Close enough in energy
            default => $adaptiveExample,
        };
    }

    /**
     * Trim a video prompt to a target word count by removing sentences from the middle.
     * Preserves the opening setup (first 2 sentences) and closing (last sentence with "Cinematic, photorealistic.").
     */
    protected function trimPromptToWordCount(string $prompt, int $targetWords = 175): string
    {
        // Split into sentences (period followed by space and uppercase letter, or period at end)
        $sentences = preg_split('/(?<=\.)\s+(?=[A-Z"])/', $prompt);

        if (count($sentences) <= 3) {
            return $prompt; // Too few sentences to trim
        }

        // Always keep first 2 sentences (setup) and last sentence (closing with "Cinematic, photorealistic.")
        $opening = array_slice($sentences, 0, 2);
        $closing = [array_pop($sentences)];
        $middle = array_slice($sentences, 2);

        // Build from opening + middle sentences until we approach target
        $result = $opening;
        $currentWords = str_word_count(implode(' ', $opening)) + str_word_count(implode(' ', $closing));

        foreach ($middle as $sentence) {
            $sentenceWords = str_word_count($sentence);
            if ($currentWords + $sentenceWords <= $targetWords - 5) { // Leave 5-word buffer for closing
                $result[] = $sentence;
                $currentWords += $sentenceWords;
            }
        }

        $result = array_merge($result, $closing);
        $trimmed = implode(' ', $result);

        // Ensure it ends correctly
        if (!str_contains($trimmed, 'Cinematic, photorealistic.')) {
            $trimmed = rtrim($trimmed, '. ') . '. Cinematic, photorealistic.';
        }

        Log::info('ConceptService: trimPromptToWordCount', [
            'beforeWords' => str_word_count($prompt),
            'afterWords' => str_word_count($trimmed),
            'sentencesKept' => count($result),
            'sentencesTotal' => count($sentences) + 1,
        ]);

        return $trimmed;
    }
}
