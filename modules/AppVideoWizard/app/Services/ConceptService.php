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

        if ($videoEngine === 'seedance') {
            $prompt = $this->buildSeedanceViralPrompt($themeContext, $count, $styleModifier, $chaosModifier);
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
    protected function buildSeedanceViralPrompt(string $themeContext, int $count, string $styleModifier = '', string $chaosModifier = ''): string
    {
        return <<<PROMPT
You are a viral content specialist who creates massively shareable short-form video concepts.

{$themeContext}

{$styleModifier}

{$chaosModifier}

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

Each idea MUST include a "videoPrompt" field — a Seedance 1.5 Pro optimized prompt.

VIDEO PROMPT RULES — THIS IS CRITICAL:

WORD COUNT: 150-180 words. This is the proven sweet spot for Seedance 1.5 Pro.
Under 140 words loses critical intensity. Over 200 words gets redundant. Aim for 160-175.

DO NOT describe character appearances — that goes in "character" and "characters" fields.
The video prompt describes ONLY actions, reactions, sounds, and voice.

=== THE #1 RULE: ONE DIALOGUE LINE, THEN PURE UNBROKEN CHAOS ===
The human speaks ONCE at the start (1 line, max 20 words). After that, ZERO dialogue.
No "he exclaims", no "he yells", no more human speech. The rest is ALL physical action,
animal sounds, and environmental destruction. The dialogue exists ONLY to trigger the chaos.

STRUCTURE — DIALOGUE TRIGGER → UNBROKEN CHAOS:
1. TRIGGER (1 sentence): The human says ONE short angry line.
2. INSTANT REACTION: "Instantly" — the animal explodes. First sound + first physical strike.
3. CONTINUOUS ESCALATION: Rapid-fire action beats with NO PAUSES for dialogue or narrative.
4. PEAK LAUNCH: Animal launches onto human with maximum force.
5. ENVIRONMENTAL CHAIN REACTIONS: Body smashes into objects → objects topple → things crash.
6. CLOSING: "continuous crazy aggressive [animal] screaming throughout. Cinematic, photorealistic."

AGGRESSOR DOMINANCE — THE ANIMAL CONTROLS 100%:
- The animal drives ALL action. The human ONLY reacts: jerks back, gasps, stumbles, hands up.
- The human NEVER grabs, holds, or controls the animal. NEVER speaks after the trigger line.

INTENSITY QUALIFIERS — mandatory on every action:
"at high frequency", "crazy intensity", "with large amplitude", "crazy explosive force",
"fast and violently", wildly, powerfully, violently, ferociously, furiously, aggressively.

CHARACTER SOUNDS — CONTINUOUS (most important rule):
Animal sounds in EVERY beat: screeching, yowling, hissing, shrieking, screaming, growling.
Describe physically: "mouth gaping wide showing sharp fangs", "ears flattened".
End with "continuous crazy aggressive [animal] screaming throughout."

PHYSICAL ACTION — SPECIFIC BODY PARTS + AMPLITUDE:
GOOD: "front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge"
GOOD: "rigid tail whips violently, snapping against a metal utensil holder, sending spoons clattering"

ENVIRONMENTAL DESTRUCTION — CREATIVE CHAIN REACTIONS (minimum 3):
Every object HIT by a body part before it breaks. Invent specific destruction chains.

STYLE ANCHOR — end with: "Cinematic, photorealistic."

BANNED: No semicolons. No camera descriptions. No appearance descriptions. No passive voice.
No weak verbs. No multiple dialogue lines. No human grabbing/controlling animal. No narrative pauses.

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
    "videoPrompt": "The man leans forward and says 'This coffee is terrible, what did you put in this? I want my money back!' while gesturing angrily at the cup. Instantly the cat shrieks a deafening crazy yowl, ears flattened, mouth gaping wide showing sharp fangs. Its front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge at the man. Razor claws scrape wildly across the man's jacket, shredding fabric with an audible rip as the man jerks his head back gasping. Simultaneously the cat's hind legs kick at high frequency, smashing cup fragments and spraying dark liquid violently across the man's chest. The man cries out, body recoiling sharply, hands thrown up defensively as he stumbles backwards fast. The cat launches itself with crazy explosive force onto the man's torso, front claws raking downward powerfully with large amplitude while rear legs thrash wildly and relentlessly against his midsection. Its body smashes into a nearby display of packaged goods, toppling the entire stack which crashes loudly onto the floor. The cat's rigid tail whips violently, snapping against a metal utensil holder, sending spoons and forks clattering loudly across the counter. Continuous crazy aggressive cat screaming throughout. Cinematic, photorealistic.",
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
        $escalation = match (true) {
            $chaosLevel <= 20 => [
                'label' => 'CALM & GENTLE',
                'instruction' => 'Keep scenes CALM and wholesome. The main character performs gentle, deliberate actions — softly tapping, carefully adjusting, quietly reacting. No flying objects, no destruction. The humor comes from subtle expressions and small physical gestures the character does.',
                'verbs' => 'taps, nudges, adjusts, tilts, peeks, pats, straightens, polishes, arranges, sets down',
                'causality' => 'The character gently [action] which makes [small result]. Example: "the cat carefully nudges the salt shaker, which tips over and spills a tiny pile of salt"',
            ],
            $chaosLevel <= 45 => [
                'label' => 'MODERATE ENERGY',
                'instruction' => 'The main character starts getting physical — grabbing things, tossing objects, making exaggerated gestures. Every object that moves is BECAUSE the character touched it, pushed it, or knocked it. The character\'s increasingly frantic actions cause a small chain reaction.',
                'verbs' => 'grabs, tosses, shoves, swats, flicks, kicks, yanks, bumps, drops, fumbles',
                'causality' => 'The character [aggressive action] WHICH CAUSES [object] to [fly/fall/break]. Example: "the cat grabs the cutting board and flips it, sending vegetables scattering across the counter"',
            ],
            $chaosLevel <= 65 => [
                'label' => 'HIGH ENERGY',
                'instruction' => 'The main character is the ENGINE of all chaos. They slam, throw, and smash — and every impact causes objects to fly, surfaces to crack, and bystanders to duck. NOTHING moves unless the character physically causes it. Write the character\'s aggressive action FIRST, then describe what it causes.',
                'verbs' => 'SLAMS, HURLS, SMASHES, RAMS, LAUNCHES, SWEEPS, KICKS, FLIPS, POUNDS, WRECKS',
                'causality' => 'The character SLAMS [object] which SENDS [things] flying/crashing. Example: "the cat SLAMS both paws on the counter, SENDING the cash register sliding off the edge and pizza boxes tumbling from the shelf behind"',
            ],
            $chaosLevel <= 85 => [
                'label' => 'PEAK CHAOS',
                'instruction' => 'The main character is a WRECKING BALL. Every movement they make destroys something. They throw things that hit other things that break more things — but it ALL starts from the character\'s physical actions. The character is grabbing, throwing, kicking, spinning, sweeping everything off surfaces. EACH sentence must start with what the character DOES, then what it CAUSES.',
                'verbs' => 'DEMOLISHES, PILE-DRIVES, BODY-SLAMS, CATAPULTS, TORPEDOES, KARATE-CHOPS, WRECKING-BALLS, BARREL-ROLLS through, UPPERCUTS, DROPKICKS',
                'causality' => 'The character DEMOLISHES [thing], CATAPULTING [debris] into [other thing] which EXPLODES into pieces. Example: "the cat LEAPS onto the shelf and BODY-SLAMS it, CATAPULTING every pizza box into the air while the shelf CRASHES into the counter behind"',
            ],
            default => [
                'label' => 'APOCALYPTIC MELTDOWN',
                'instruction' => 'The main character becomes a FORCE OF NATURE. Every limb is destroying something simultaneously. They\'re spinning, throwing, stomping, and the CHAIN REACTION of their actions levels the entire scene. But remember: the CHARACTER is the source — they physically cause every single piece of destruction. Write it as a rapid sequence of the character\'s actions and their escalating consequences.',
                'verbs' => 'ANNIHILATES, DETONATES, VAPORIZES, SUPERNOVA-BLASTS, MEGA-LAUNCHES, NUCLEAR-KICKS, TORNADO-SPINS through, METEOR-SLAMS',
                'causality' => 'The character ANNIHILATES [thing], which TRIGGERS a chain reaction: [consequence 1], [consequence 2], [consequence 3]. Example: "the cat TORNADO-SPINS across the counter, SWEEPING every single item into the air — plates SHATTER against the walls, drinks SPLASH across the ceiling, the cash register LAUNCHES through the window"',
            ],
        };

        $parts = [];
        $parts[] = "CHAOS INTENSITY: {$escalation['label']} ({$chaosLevel}/100)";
        $parts[] = '';
        $parts[] = 'CRITICAL — CHARACTER-DRIVEN CHAOS RULE:';
        $parts[] = 'ALL chaos MUST be caused by the main character\'s PHYSICAL ACTIONS. The character does something';
        $parts[] = 'aggressive (slams, throws, kicks, sweeps) and THAT action causes objects to fly, break, and scatter.';
        $parts[] = 'NEVER write passive chaos like "spaghetti flying wildly" — ALWAYS write CAUSED chaos like';
        $parts[] = '"the cat SLAMS the table, SENDING spaghetti flying across the room."';
        $parts[] = 'Every flying object, every broken thing, every splash MUST trace back to a specific character action.';
        $parts[] = '';
        $parts[] = $escalation['instruction'];
        $parts[] = "ACTION VERBS: {$escalation['verbs']}";
        $parts[] = "CAUSALITY PATTERN: {$escalation['causality']}";

        if (!empty($chaosDescription)) {
            $parts[] = '';
            $parts[] = "USER'S CHAOS DIRECTION: \"{$chaosDescription}\"";
            $parts[] = 'Incorporate this specific chaos vision into every idea. The main character\'s physical actions';
            $parts[] = 'should create exactly this kind of chaos. Shape the scenarios around this direction while';
            $parts[] = 'maintaining the CHARACTER-DRIVEN causality rule above.';
        }

        return implode("\n", $parts);
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
   - Size relative to other objects/characters
   - SPATIAL POSITION: Where is each character relative to others? Who is in the foreground/background? Who faces whom?

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
    protected function synthesizeConcept(string $visualAnalysis, ?string $transcript, string $aiModelTier, int $teamId, string $videoEngine = 'seedance'): array
    {
        $transcriptSection = $transcript
            ? "AUDIO TRANSCRIPT:\n\"{$transcript}\"\n\nCRITICAL AUDIO ANALYSIS:\n- This transcript was captured from the video's audio track.\n- On TikTok/Reels, human dialogue over animal videos is almost ALWAYS a dubbed voiceover/narration — the animal is NOT actually speaking.\n- If the visual analysis shows an ANIMAL with mouth open, the animal is making ANIMAL SOUNDS (meowing, barking, hissing, screaming) — NOT speaking human words.\n- The transcript above is likely a VOICEOVER narration added for comedy, NOT the animal's actual voice.\n- IMPORTANT FOR VOICEOVER TEXT: Strip out ALL animal sound words (meow, woof, bark, hiss, growl, etc.) from the voiceover narration. Only include the HUMAN SPEECH parts. If the transcript is 'This is not what I ordered! Meow meow meow! I asked for chicken!' the voiceover should be 'This is not what I ordered! I asked for chicken!' — no animal sounds in the voiceover.\n- The voiceover narration must contain ONLY clean human speech. Animal sounds happen VISUALLY in the scene, not in the voiceover audio."
            : "AUDIO: No speech detected in video. Assume visual comedy / silent humor with environmental sounds only.";

        $videoPromptInstruction = $videoEngine === 'seedance'
            ? 'Also generate a "videoPrompt" field — see SEEDANCE VIDEO PROMPT RULES at the end of this prompt.'
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

Return ONLY a JSON object (no markdown, no explanation):
{
  "title": "Catchy title (max 6 words) — must reference the actual character/animal",
  "concept": "One sentence describing the EXACT visual scene as analyzed",
  "speechType": "monologue" or "dialogue",
  "characters": [
    {"name": "Fun Name", "description": "EXACT species + detailed visual description matching the analysis: fur color, clothing, accessories, size", "role": "protagonist/supporting/background", "expression": "expression from analysis", "position": "EXACT spatial position: foreground/background, left/right/center, facing direction, distance from camera"}
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
You are CLONING a reference video — but NOT recreating it literally. Your job is to capture
the ENERGY and CONCEPT of the reference, then create an INTENSIFIED Seedance-optimized version.
Do NOT try to describe every exact moment from the reference. Instead, take the core concept
and write the most CHAOTIC, ACTION-PACKED version possible.

WORD COUNT: 150-180 words. This is the proven sweet spot for Seedance 1.5 Pro.
Under 140 words loses critical intensity. Over 200 words gets redundant. Aim for 160-175.

DO NOT describe character appearances — that's in "character" and "characters" fields.
The videoPrompt describes ONLY actions, reactions, sounds, and voice.

=== THE #1 RULE: ONE DIALOGUE LINE, THEN PURE UNBROKEN CHAOS ===
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

INTENSITY QUALIFIERS — mandatory on every action:
- Frequency: "at high frequency", "in rapid succession", "nonstop"
- Intensity: "crazy intensity", "with large amplitude", "with full force", "crazy explosive force"
- Speed: "fast and violently", "quickly", "fast"
- Adverbs: wildly, powerfully, violently, ferociously, furiously, aggressively, fiercely
EVERY action verb MUST have at least one intensity qualifier. No exceptions.

CHARACTER SOUNDS — CONTINUOUS (most important rule):
Animal sounds must appear in EVERY action beat. Use varied words:
screeching, yowling, hissing, shrieking, screaming, growling, wailing.
Describe sounds physically: "mouth gaping wide showing sharp fangs", "ears flattened".
End with "continuous crazy aggressive [animal] screaming throughout."

PHYSICAL ACTION — SPECIFIC BODY PARTS + AMPLITUDE:
GOOD: "front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge"
GOOD: "rear legs kick at high frequency, smashing cup fragments and spraying dark liquid violently"
GOOD: "rigid tail whips violently, snapping against a metal utensil holder, sending spoons clattering"
BAD: "the cat attacks him aggressively" (too vague — which body part? what gets hit?)

ENVIRONMENTAL DESTRUCTION — CREATIVE CHAIN REACTIONS (minimum 3):
Don't just say "things crash." Invent specific destruction chains:
- "body smashes into a nearby display of packaged goods, toppling the entire stack which crashes loudly"
- "rigid tail whips violently, snapping against a metal utensil holder, sending spoons and forks clattering"
- "smashing cup fragments and spraying dark liquid violently across the man's chest"
Every object must be HIT by a body part before it breaks. More destruction = better video.

STYLE ANCHOR — ALWAYS end with: "Cinematic, photorealistic."

BANNED:
- No semicolons
- No camera movement descriptions (camera is controlled separately by the API)
- No appearance/clothing descriptions (what characters LOOK like — only what they DO)
- No passive voice — only active verbs with intensity qualifiers
- No weak/generic verbs: "goes", "moves", "does", "gets", "starts", "begins"
- No slow builds — chaos is INSTANT after the trigger
- No multiple dialogue lines — ONE line triggers the chaos, then ZERO speech
- No human grabbing/controlling/restraining the animal — animal dominates
- No narrative back-and-forth — pure continuous chaos, no pauses
- No recreating exact reference details — create an INTENSIFIED creative version

EXAMPLE — GOOD CLONE PROMPT (~170 words):
"The man leans forward and says 'This coffee is terrible, what did you put in this? I want my money back!' while gesturing angrily at the cup. Instantly the cat shrieks a deafening crazy yowl, ears flattened, mouth gaping wide showing sharp fangs. Its front paws slam into the counter powerfully, propelling its body forward in a fast violent lunge at the man. Razor claws scrape wildly across the man's jacket, shredding fabric with an audible rip as the man jerks his head back gasping. Simultaneously the cat's hind legs kick at high frequency, smashing cup fragments and spraying dark liquid violently across the man's chest. The man cries out, body recoiling sharply, hands thrown up defensively as he stumbles backwards fast. The cat launches itself with crazy explosive force onto the man's torso, front claws raking downward powerfully with large amplitude while rear legs thrash wildly and relentlessly against his midsection. Its body smashes into a nearby display of packaged goods, toppling the entire stack which crashes loudly onto the floor. The cat's rigid tail whips violently, snapping against a metal utensil holder, sending spoons and forks clattering loudly across the counter. Continuous crazy aggressive cat screaming throughout. Cinematic, photorealistic."

NOW generate the JSON — make the videoPrompt an INTENSIFIED creative version of the reference.
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
