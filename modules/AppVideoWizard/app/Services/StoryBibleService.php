<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\VwGenerationLog;

/**
 * Story Bible Service - Generates comprehensive Story Bible BEFORE script
 *
 * The Story Bible is the "DNA" that constrains all subsequent generation:
 * - Title & Logline
 * - Theme & Tone
 * - Three-Act Structure (with turning points)
 * - Character Profiles (3-5+ characters with detailed descriptions)
 * - Location Index (all settings)
 * - Visual Style Definition
 * - Pacing & Emotional Journey
 *
 * This leverages Grok 4.1's 2M token context to generate everything in a single pass.
 */
class StoryBibleService
{
    /**
     * AI Model Tier configurations.
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
     * Structure templates for different story formats
     */
    const STRUCTURE_TEMPLATES = [
        'three-act' => [
            'name' => 'Three-Act Structure',
            'acts' => [
                ['actNumber' => 1, 'name' => 'Setup', 'percentage' => 25, 'description' => 'Introduce characters, setting, and the central conflict'],
                ['actNumber' => 2, 'name' => 'Confrontation', 'percentage' => 50, 'description' => 'Rising action, obstacles, and character development'],
                ['actNumber' => 3, 'name' => 'Resolution', 'percentage' => 25, 'description' => 'Climax and resolution of the story'],
            ],
        ],
        'five-act' => [
            'name' => 'Five-Act Structure',
            'acts' => [
                ['actNumber' => 1, 'name' => 'Exposition', 'percentage' => 15, 'description' => 'Introduction and setup'],
                ['actNumber' => 2, 'name' => 'Rising Action', 'percentage' => 25, 'description' => 'Building tension and complications'],
                ['actNumber' => 3, 'name' => 'Climax', 'percentage' => 20, 'description' => 'The turning point'],
                ['actNumber' => 4, 'name' => 'Falling Action', 'percentage' => 25, 'description' => 'Consequences unfold'],
                ['actNumber' => 5, 'name' => 'Resolution', 'percentage' => 15, 'description' => 'Final resolution'],
            ],
        ],
        'heros-journey' => [
            'name' => "Hero's Journey",
            'acts' => [
                ['actNumber' => 1, 'name' => 'Ordinary World', 'percentage' => 10, 'description' => 'The hero in their normal life'],
                ['actNumber' => 2, 'name' => 'Call to Adventure', 'percentage' => 10, 'description' => 'The challenge appears'],
                ['actNumber' => 3, 'name' => 'Crossing the Threshold', 'percentage' => 15, 'description' => 'Entering the special world'],
                ['actNumber' => 4, 'name' => 'Tests & Allies', 'percentage' => 25, 'description' => 'Challenges and meeting helpers'],
                ['actNumber' => 5, 'name' => 'Ordeal', 'percentage' => 15, 'description' => 'The central crisis'],
                ['actNumber' => 6, 'name' => 'Reward & Return', 'percentage' => 25, 'description' => 'Victory and return transformed'],
            ],
        ],
    ];

    protected PromptService $promptService;

    public function __construct(?PromptService $promptService = null)
    {
        $this->promptService = $promptService ?? new PromptService();
    }

    /**
     * Get AI config from tier.
     */
    protected function getAIConfigFromTier(string $tier): array
    {
        return self::AI_MODEL_TIERS[$tier] ?? self::AI_MODEL_TIERS['economy'];
    }

    /**
     * Call AI with tier-based model selection.
     */
    protected function callAIWithTier(string $prompt, string $tier, int $teamId, array $options = []): array
    {
        $config = $this->getAIConfigFromTier($tier);

        Log::info('StoryBible: AI call with tier', [
            'tier' => $tier,
            'provider' => $config['provider'],
            'model' => $config['model'],
        ]);

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
     * Generate a comprehensive Story Bible from concept.
     *
     * This is a single-pass generation that creates the complete "DNA" for the video:
     * - Title, logline, theme, tone
     * - Three-act structure with turning points
     * - 3-5+ character profiles with detailed visual descriptions
     * - Location index with atmosphere details
     * - Visual style guide
     * - Pacing and emotional journey
     *
     * @param WizardProject $project The project to generate Bible for
     * @param array $options Generation options
     * @return array The complete Story Bible
     */
    public function generateStoryBible(WizardProject $project, array $options = []): array
    {
        $startTime = microtime(true);

        $concept = $project->concept ?? [];
        $topic = $concept['refinedConcept'] ?? $concept['rawInput'] ?? '';
        $duration = $project->target_duration ?? 60;
        $productionType = $project->production_type ?? 'standard';
        $productionSubtype = $project->production_subtype ?? null;
        $visualMode = $options['visualMode'] ?? 'cinematic-realistic';
        $structureTemplate = $options['structureTemplate'] ?? 'three-act';
        $aiModelTier = $options['aiModelTier'] ?? 'economy';
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Get structure template
        $template = self::STRUCTURE_TEMPLATES[$structureTemplate] ?? self::STRUCTURE_TEMPLATES['three-act'];

        Log::info('StoryBible: Starting generation', [
            'projectId' => $project->id,
            'topic' => substr($topic, 0, 100),
            'duration' => $duration,
            'productionType' => $productionType,
            'structureTemplate' => $structureTemplate,
            'aiModelTier' => $aiModelTier,
        ]);

        // Build the comprehensive prompt
        $prompt = $this->buildStoryBiblePrompt([
            'topic' => $topic,
            'duration' => $duration,
            'productionType' => $productionType,
            'productionSubtype' => $productionSubtype,
            'visualMode' => $visualMode,
            'structureTemplate' => $template,
            'concept' => $concept,
            'additionalInstructions' => $options['additionalInstructions'] ?? '',
        ]);

        // Call AI (single pass - leveraging large context)
        // Increase max_tokens to ensure full JSON response isn't truncated
        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 15000, // Ensure enough tokens for full Story Bible JSON
        ]);

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        if (!empty($result['error'])) {
            Log::error('StoryBible: AI error', ['error' => $result['error']]);

            // Log failure
            $this->logGeneration('story_bible_generation', [
                'topic' => substr($topic, 0, 200),
                'duration' => $duration,
            ], [], 'failed', $result['error'], null, $durationMs, $project->id);

            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        if (empty($response)) {
            Log::error('StoryBible: Empty AI response');

            $this->logGeneration('story_bible_generation', [
                'topic' => substr($topic, 0, 200),
                'duration' => $duration,
            ], [], 'failed', 'Empty AI response', null, $durationMs, $project->id);

            throw new \Exception('AI returned an empty response. Please try again.');
        }

        // Parse the response
        $storyBible = $this->parseStoryBibleResponse($response, $template, $duration);

        // Add metadata
        $storyBible['enabled'] = true;
        $storyBible['status'] = 'ready';
        $storyBible['generatedAt'] = now()->toIso8601String();
        $storyBible['structureTemplate'] = $structureTemplate;
        $storyBible['aiModelTier'] = $aiModelTier;

        // Log success
        $this->logGeneration('story_bible_generation', [
            'topic' => substr($topic, 0, 200),
            'duration' => $duration,
        ], [
            'characterCount' => count($storyBible['characters'] ?? []),
            'locationCount' => count($storyBible['locations'] ?? []),
            'actCount' => count($storyBible['acts'] ?? []),
        ], 'success', null, null, $durationMs, $project->id);

        Log::info('StoryBible: Generation completed', [
            'projectId' => $project->id,
            'durationMs' => $durationMs,
            'characterCount' => count($storyBible['characters'] ?? []),
            'locationCount' => count($storyBible['locations'] ?? []),
        ]);

        return $storyBible;
    }

    /**
     * Build the comprehensive Story Bible generation prompt.
     */
    protected function buildStoryBiblePrompt(array $params): string
    {
        $topic = $params['topic'];
        $duration = $params['duration'];
        $productionType = $params['productionType'];
        $productionSubtype = $params['productionSubtype'] ?? '';
        $visualMode = $params['visualMode'];
        $template = $params['structureTemplate'];
        $concept = $params['concept'] ?? [];
        $additionalInstructions = $params['additionalInstructions'] ?? '';

        $minutes = round($duration / 60, 1);
        $templateName = $template['name'] ?? 'Three-Act Structure';

        // Build act structure guidance
        $actsGuidance = '';
        foreach ($template['acts'] ?? [] as $act) {
            $actNum = $act['actNumber'];
            $actName = $act['name'];
            $actPercent = $act['percentage'];
            $actDuration = round($duration * ($actPercent / 100));
            $actsGuidance .= "  Act {$actNum}: {$actName} (~{$actPercent}% = {$actDuration}s)\n";
        }

        // Visual mode guidance
        $visualModeGuide = match ($visualMode) {
            'cinematic-realistic' => 'PHOTOREALISTIC. All characters and locations must be described as real-world, film-quality visuals. Think: Netflix original, HBO series.',
            'stylized-animation' => 'ANIMATED/STYLIZED. Characters and locations can be stylized, animated, or illustrated. Think: Pixar, anime, motion graphics.',
            'mixed-hybrid' => 'MIXED STYLE. Can combine realistic and stylized elements as appropriate.',
            default => 'PHOTOREALISTIC by default.',
        };

        // Extract any existing character/concept hints
        $conceptHints = '';
        if (!empty($concept['keyElements'])) {
            $elements = is_array($concept['keyElements']) ? implode(', ', $concept['keyElements']) : $concept['keyElements'];
            $conceptHints .= "Key Elements: {$elements}\n";
        }
        if (!empty($concept['targetAudience'])) {
            $conceptHints .= "Target Audience: {$concept['targetAudience']}\n";
        }
        if (!empty($concept['suggestedTone'])) {
            $conceptHints .= "Suggested Tone: {$concept['suggestedTone']}\n";
        }
        if (!empty($concept['suggestedMood'])) {
            $conceptHints .= "Suggested Mood: {$concept['suggestedMood']}\n";
        }

        $prompt = <<<PROMPT
You are a Hollywood screenwriter and story architect. Your task is to create a comprehensive STORY BIBLE for a video project.

The Story Bible will serve as the "DNA" that constrains ALL subsequent generation - script, images, and video.
Every character, location, and visual element in the final video MUST match this Bible exactly.

=== PROJECT DETAILS ===
CONCEPT: {$topic}

DURATION: {$duration} seconds ({$minutes} minutes)
PRODUCTION TYPE: {$productionType}
PRODUCTION SUBTYPE: {$productionSubtype}
STRUCTURE: {$templateName}

{$actsGuidance}

=== VISUAL MODE (MANDATORY) ===
{$visualModeGuide}

{$conceptHints}

PROMPT;

        if (!empty($additionalInstructions)) {
            $prompt .= "ADDITIONAL REQUIREMENTS: {$additionalInstructions}\n\n";
        }

        $prompt .= <<<'PROMPT'

=== STORY BIBLE REQUIREMENTS ===

Generate a COMPLETE Story Bible with ALL of the following sections:

1. TITLE & LOGLINE
   - Title: Compelling, SEO-friendly video title
   - Logline: One-sentence story summary (25-40 words)

2. THEME & TONE
   - Theme: The core message or idea
   - Tone: Overall emotional feel (dramatic, inspiring, educational, etc.)
   - Genre: Specific genre classification

3. ACT STRUCTURE
   - For each act: description and turning point
   - Specific story beats for each act
   - How tension builds and resolves

4. CHARACTER PROFILES (3-5 characters minimum)
   - Name: Full name or title
   - Role: protagonist, antagonist, supporting, narrator
   - Description: DETAILED visual description for AI image generation:
     * Age, gender, ethnicity/skin tone
     * Build (athletic, slim, stocky, etc.)
     * Hair (color, length, style)
     * Eyes (color, shape)
     * Distinctive features
     * Typical clothing/wardrobe
   - Arc: Character's journey/transformation
   - Traits: Key personality traits (array)

5. LOCATION INDEX (2-5 locations minimum)
   - Name: Distinctive location name
   - Type: interior/exterior
   - Description: DETAILED visual description for AI image generation:
     * Architecture, materials, colors
     * Key objects and furniture
     * Textures and atmosphere
   - TimeOfDay: day/night/dawn/dusk/golden-hour
   - Atmosphere: tense/peaceful/energetic/mysterious/etc.

6. VISUAL STYLE
   - Mode: cinematic-realistic/stylized-animation/mixed-hybrid
   - ColorPalette: Primary colors and mood
   - Lighting: Lighting style (natural, dramatic, soft, etc.)
   - CameraLanguage: Preferred camera movements and angles
   - References: Visual references (e.g., "Blade Runner meets The Social Network")

7. PACING
   - Overall: fast/balanced/contemplative
   - EmotionalBeats: Array of emotional states the viewer experiences

=== OUTPUT FORMAT ===
Return ONLY valid JSON (no markdown, no explanation):

{
  "title": "The Video Title",
  "logline": "One-sentence compelling summary of the story",
  "theme": "The core message or theme",
  "tone": "dramatic/inspiring/educational/humorous/etc",
  "genre": "thriller/documentary/comedy/drama/etc",

  "acts": [
    {
      "actNumber": 1,
      "name": "Act Name",
      "description": "What happens in this act",
      "turningPoint": "The key moment that transitions to next act",
      "percentage": 25,
      "beats": ["beat 1", "beat 2", "beat 3"]
    }
  ],

  "characters": [
    {
      "id": "char_1",
      "name": "Full Character Name",
      "role": "protagonist",
      "description": "Detailed visual description: age, gender, ethnicity, build, hair color and style, eye color, skin tone, distinctive features, typical clothing. Be VERY specific for AI image generation.",
      "arc": "Character's journey and transformation",
      "traits": ["trait1", "trait2", "trait3"],
      "appearsInActs": [1, 2, 3]
    }
  ],

  "locations": [
    {
      "id": "loc_1",
      "name": "Location Name",
      "type": "interior",
      "description": "Detailed visual description: architecture, materials, colors, key objects, textures, atmosphere. Be VERY specific for AI image generation.",
      "timeOfDay": "day",
      "atmosphere": "tense",
      "appearsInActs": [1, 3]
    }
  ],

  "visualStyle": {
    "mode": "cinematic-realistic",
    "colorPalette": "Color scheme description",
    "lighting": "Lighting style description",
    "cameraLanguage": "Camera movement preferences",
    "references": "Visual references"
  },

  "pacing": {
    "overall": "balanced",
    "tensionCurve": [10, 30, 50, 80, 100, 70, 90],
    "emotionalBeats": ["curiosity", "tension", "revelation", "satisfaction"]
  }
}

=== CRITICAL REQUIREMENTS ===
1. Character descriptions MUST be detailed enough for AI image generation (50-100 words each)
2. Location descriptions MUST be detailed enough for AI image generation (50-100 words each)
3. ALL characters must have visual descriptions - NEVER leave description empty
4. Create AT LEAST 3 characters and 2 locations
5. Ensure acts add up to 100% total
6. Match the visual mode strictly (realistic vs stylized)
7. Return ONLY valid JSON - no markdown code blocks, no explanation text
8. KEEP RESPONSE CONCISE - limit act beats to 3-4 items, traits to 3-4 items
9. PRIORITIZE: characters and locations are ESSENTIAL - include them before adding extra detail elsewhere

PROMPT;

        return $prompt;
    }

    /**
     * Parse the Story Bible response.
     */
    protected function parseStoryBibleResponse(string $response, array $template, int $duration): array
    {
        // Clean up response
        $response = trim($response);
        $originalResponse = $response;
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        // Log raw response for debugging
        Log::info('StoryBible: Parsing response', [
            'responseLength' => strlen($response),
            'hasCharactersKey' => str_contains($response, '"characters"'),
            'hasLocationsKey' => str_contains($response, '"locations"'),
            'firstChars' => substr($response, 0, 200),
        ]);

        // Try to parse JSON
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('StoryBible: Initial JSON parse failed, trying repair', [
                'jsonError' => json_last_error_msg(),
            ]);

            // Try to repair truncated JSON (common when AI hits token limit)
            $repairedJson = $this->repairTruncatedJson($response);
            if ($repairedJson !== $response) {
                Log::info('StoryBible: Attempting parse with repaired JSON');
                $result = json_decode($repairedJson, true);
            }

            // If still failing, try extraction
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to extract JSON from response - more robust pattern
                if (preg_match('/\{[\s\S]*"title"[\s\S]*"characters"[\s\S]*\}/m', $response, $matches)) {
                    $result = json_decode($matches[0], true);
                } elseif (preg_match('/\{[\s\S]*"title"[\s\S]*\}/m', $response, $matches)) {
                    $repaired = $this->repairTruncatedJson($matches[0]);
                    $result = json_decode($repaired, true);
                }
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
            Log::error('StoryBible: Failed to parse JSON response after all attempts', [
                'response' => substr($response, 0, 1000),
                'jsonError' => json_last_error_msg(),
            ]);

            // Return a minimal default structure
            return $this->buildDefaultStoryBible($template, $duration);
        }

        // Log what was parsed
        Log::info('StoryBible: JSON parsed successfully', [
            'hasTitle' => !empty($result['title']),
            'characterCount' => count($result['characters'] ?? []),
            'locationCount' => count($result['locations'] ?? []),
            'actCount' => count($result['acts'] ?? []),
            'keys' => array_keys($result),
        ]);

        // Normalize and validate the Story Bible
        $normalized = $this->normalizeStoryBible($result, $template, $duration);

        // Warn if characters or locations are empty
        if (empty($normalized['characters'])) {
            Log::warning('StoryBible: No characters after normalization', [
                'rawCharacters' => $result['characters'] ?? 'not present',
            ]);
        }

        if (empty($normalized['locations'])) {
            Log::warning('StoryBible: No locations after normalization', [
                'rawLocations' => $result['locations'] ?? 'not present',
            ]);
        }

        return $normalized;
    }

    /**
     * Attempt to repair truncated JSON from AI responses.
     * Closes unclosed brackets, braces, and strings.
     */
    protected function repairTruncatedJson(string $json): string
    {
        // Remove any trailing incomplete string (cut off mid-value)
        // Look for patterns like "key": "incomplete value... (no closing quote)
        $json = preg_replace('/,?\s*"[^"]*":\s*"[^"]*$/s', '', $json);
        $json = preg_replace('/,?\s*"[^"]*":\s*\[[^\]]*$/s', '', $json); // Incomplete array
        $json = preg_replace('/,?\s*"[^"]*$/s', '', $json); // Incomplete key

        // Remove trailing commas
        $json = preg_replace('/,(\s*[\]\}])/s', '$1', $json);
        $json = preg_replace('/,\s*$/s', '', $json);

        // Count open/close brackets and braces
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');

        // Add missing closing characters
        $json .= str_repeat(']', max(0, $openBrackets - $closeBrackets));
        $json .= str_repeat('}', max(0, $openBraces - $closeBraces));

        Log::info('StoryBible: JSON repair attempted', [
            'addedBrackets' => max(0, $openBrackets - $closeBrackets),
            'addedBraces' => max(0, $openBraces - $closeBraces),
        ]);

        return $json;
    }

    /**
     * Normalize and validate the Story Bible structure.
     */
    protected function normalizeStoryBible(array $bible, array $template, int $duration): array
    {
        $normalized = [
            'title' => $bible['title'] ?? 'Untitled Video',
            'logline' => $bible['logline'] ?? '',
            'theme' => $bible['theme'] ?? '',
            'tone' => $bible['tone'] ?? 'engaging',
            'genre' => $bible['genre'] ?? 'general',
        ];

        // Normalize acts
        $acts = $bible['acts'] ?? $template['acts'] ?? [];
        $normalized['acts'] = [];
        foreach ($acts as $idx => $act) {
            $actDuration = round($duration * (($act['percentage'] ?? 25) / 100));
            $normalized['acts'][] = [
                'actNumber' => $act['actNumber'] ?? ($idx + 1),
                'name' => $act['name'] ?? 'Act ' . ($idx + 1),
                'description' => $act['description'] ?? '',
                'turningPoint' => $act['turningPoint'] ?? '',
                'percentage' => $act['percentage'] ?? 25,
                'duration' => $actDuration,
                'beats' => $act['beats'] ?? [],
            ];
        }

        // Normalize characters
        $characters = $bible['characters'] ?? [];
        $normalized['characters'] = [];
        foreach ($characters as $idx => $char) {
            $normalized['characters'][] = [
                'id' => $char['id'] ?? 'char_' . time() . '_' . $idx,
                'name' => $char['name'] ?? 'Character ' . ($idx + 1),
                'role' => $this->normalizeRole($char['role'] ?? 'supporting'),
                'description' => $char['description'] ?? '',
                'arc' => $char['arc'] ?? '',
                'traits' => is_array($char['traits'] ?? null) ? $char['traits'] : [],
                'appearsInActs' => is_array($char['appearsInActs'] ?? null) ? $char['appearsInActs'] : [1, 2, 3],
                'referenceImage' => null,
            ];
        }

        // Normalize locations
        $locations = $bible['locations'] ?? [];
        $normalized['locations'] = [];
        foreach ($locations as $idx => $loc) {
            $normalized['locations'][] = [
                'id' => $loc['id'] ?? 'loc_' . time() . '_' . $idx,
                'name' => $loc['name'] ?? 'Location ' . ($idx + 1),
                'type' => $this->normalizeLocationType($loc['type'] ?? 'interior'),
                'description' => $loc['description'] ?? '',
                'timeOfDay' => $this->normalizeTimeOfDay($loc['timeOfDay'] ?? 'day'),
                'atmosphere' => $loc['atmosphere'] ?? '',
                'appearsInActs' => is_array($loc['appearsInActs'] ?? null) ? $loc['appearsInActs'] : [1],
                'referenceImage' => null,
            ];
        }

        // Normalize visual style with Master Style Guide (Hollywood-quality)
        $style = $bible['visualStyle'] ?? [];
        $normalized['visualStyle'] = [
            'mode' => $style['mode'] ?? 'cinematic-realistic',
            'colorPalette' => $style['colorPalette'] ?? '',
            'lighting' => $style['lighting'] ?? '',
            'cameraLanguage' => $style['cameraLanguage'] ?? '',
            'references' => $style['references'] ?? '',

            // Master Style Guide - Applied to ALL scenes for visual continuity
            'masterStyleGuide' => [
                // Color grading profile (e.g., "teal shadows, warm orange highlights")
                'colorGrading' => $style['masterStyleGuide']['colorGrading']
                    ?? $style['colorGrading']
                    ?? $this->inferColorGradingFromPalette($style['colorPalette'] ?? ''),

                // Lighting style (e.g., "dramatic side lighting with soft fill")
                'lightingStyle' => $style['masterStyleGuide']['lightingStyle']
                    ?? $style['lightingStyle']
                    ?? $this->inferLightingStyle($style['lighting'] ?? ''),

                // Film look (e.g., "cinematic grain, shallow depth of field")
                'filmLook' => $style['masterStyleGuide']['filmLook']
                    ?? $style['filmLook']
                    ?? 'cinematic film look, subtle grain, shallow depth of field',

                // Atmosphere (e.g., "moody, intimate")
                'atmosphere' => $style['masterStyleGuide']['atmosphere']
                    ?? $style['atmosphere']
                    ?? '',

                // Dominant color palette (e.g., "muted earth tones with accent colors")
                'palette' => $style['masterStyleGuide']['palette']
                    ?? $style['palette']
                    ?? '',

                // Lens characteristics (e.g., "anamorphic lens, bokeh")
                'lensCharacteristics' => $style['masterStyleGuide']['lensCharacteristics']
                    ?? 'anamorphic lens characteristics, natural bokeh',

                // Contrast/exposure style
                'contrastStyle' => $style['masterStyleGuide']['contrastStyle']
                    ?? 'balanced contrast with preserved shadow detail',
            ],
        ];

        // Normalize pacing
        $pacing = $bible['pacing'] ?? [];
        $normalized['pacing'] = [
            'overall' => $pacing['overall'] ?? 'balanced',
            'tensionCurve' => is_array($pacing['tensionCurve'] ?? null) ? $pacing['tensionCurve'] : [],
            'emotionalBeats' => is_array($pacing['emotionalBeats'] ?? null) ? $pacing['emotionalBeats'] : [],
        ];

        return $normalized;
    }

    /**
     * Build a default Story Bible if parsing fails.
     */
    protected function buildDefaultStoryBible(array $template, int $duration): array
    {
        return [
            'title' => 'Untitled Video',
            'logline' => '',
            'theme' => '',
            'tone' => 'engaging',
            'genre' => 'general',
            'acts' => $template['acts'] ?? [],
            'characters' => [],
            'locations' => [],
            'visualStyle' => [
                'mode' => 'cinematic-realistic',
                'colorPalette' => '',
                'lighting' => '',
                'cameraLanguage' => '',
                'references' => '',
            ],
            'pacing' => [
                'overall' => 'balanced',
                'tensionCurve' => [],
                'emotionalBeats' => [],
            ],
        ];
    }

    /**
     * Normalize character role.
     */
    protected function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        $validRoles = ['protagonist', 'antagonist', 'supporting', 'narrator', 'background'];
        return in_array($role, $validRoles) ? $role : 'supporting';
    }

    /**
     * Normalize location type.
     */
    protected function normalizeLocationType(string $type): string
    {
        $type = strtolower(trim($type));
        $validTypes = ['interior', 'exterior', 'abstract'];
        return in_array($type, $validTypes) ? $type : 'interior';
    }

    /**
     * Normalize time of day.
     */
    protected function normalizeTimeOfDay(string $time): string
    {
        $time = strtolower(trim($time));
        $mapping = [
            'day' => 'day',
            'daytime' => 'day',
            'morning' => 'day',
            'afternoon' => 'day',
            'night' => 'night',
            'nighttime' => 'night',
            'evening' => 'night',
            'dawn' => 'dawn',
            'sunrise' => 'dawn',
            'dusk' => 'dusk',
            'sunset' => 'dusk',
            'twilight' => 'dusk',
            'golden-hour' => 'golden-hour',
            'golden hour' => 'golden-hour',
        ];
        return $mapping[$time] ?? 'day';
    }

    /**
     * Log a generation event.
     */
    protected function logGeneration(
        string $promptSlug,
        array $inputData,
        array $outputData,
        string $status = 'success',
        ?string $errorMessage = null,
        ?int $tokensUsed = null,
        ?int $durationMs = null,
        ?int $projectId = null
    ): void {
        try {
            $this->promptService->logGeneration(
                $promptSlug,
                $inputData,
                $outputData,
                $status,
                $errorMessage,
                $tokensUsed,
                $durationMs,
                $projectId
            );
        } catch (\Exception $e) {
            Log::warning('StoryBible: Failed to log generation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing Story Bible with user edits.
     * Validates the structure and ensures consistency.
     */
    public function updateStoryBible(array $existingBible, array $updates): array
    {
        $bible = array_merge($existingBible, $updates);

        // Re-normalize to ensure consistency
        $template = self::STRUCTURE_TEMPLATES[$bible['structureTemplate'] ?? 'three-act'];
        $duration = 60; // Default, actual duration handled elsewhere

        return $this->normalizeStoryBible($bible, $template, $duration);
    }

    /**
     * Add a character to the Story Bible.
     */
    public function addCharacter(array $bible, array $character): array
    {
        $characters = $bible['characters'] ?? [];

        $newChar = [
            'id' => $character['id'] ?? 'char_' . time() . '_' . count($characters),
            'name' => $character['name'] ?? 'New Character',
            'role' => $this->normalizeRole($character['role'] ?? 'supporting'),
            'description' => $character['description'] ?? '',
            'arc' => $character['arc'] ?? '',
            'traits' => $character['traits'] ?? [],
            'appearsInActs' => $character['appearsInActs'] ?? [1],
            'referenceImage' => $character['referenceImage'] ?? null,
        ];

        $characters[] = $newChar;
        $bible['characters'] = $characters;

        return $bible;
    }

    /**
     * Add a location to the Story Bible.
     */
    public function addLocation(array $bible, array $location): array
    {
        $locations = $bible['locations'] ?? [];

        $newLoc = [
            'id' => $location['id'] ?? 'loc_' . time() . '_' . count($locations),
            'name' => $location['name'] ?? 'New Location',
            'type' => $this->normalizeLocationType($location['type'] ?? 'interior'),
            'description' => $location['description'] ?? '',
            'timeOfDay' => $this->normalizeTimeOfDay($location['timeOfDay'] ?? 'day'),
            'atmosphere' => $location['atmosphere'] ?? '',
            'appearsInActs' => $location['appearsInActs'] ?? [1],
            'referenceImage' => $location['referenceImage'] ?? null,
        ];

        $locations[] = $newLoc;
        $bible['locations'] = $locations;

        return $bible;
    }

    /**
     * Get available structure templates.
     */
    public static function getStructureTemplates(): array
    {
        return self::STRUCTURE_TEMPLATES;
    }

    /**
     * Infer color grading from color palette description.
     * Translates user-friendly palette descriptions to professional color grading terms.
     */
    protected function inferColorGradingFromPalette(string $palette): string
    {
        if (empty($palette)) {
            return 'balanced neutral color grading';
        }

        $palette = strtolower($palette);

        // Match common palette descriptions to professional grading
        $gradingMap = [
            'warm' => 'warm golden tones, lifted shadows',
            'cool' => 'cool blue undertones, clean highlights',
            'muted' => 'desaturated muted palette, crushed blacks',
            'vibrant' => 'vibrant saturated colors, punchy contrast',
            'earthy' => 'muted earth tones, warm shadows',
            'teal' => 'teal shadows with warm orange highlights',
            'orange' => 'warm orange highlights, teal shadows',
            'golden' => 'golden hour warmth, long shadows',
            'blue' => 'cool blue hour tones, ambient glow',
            'cinematic' => 'cinema-grade color science, teal and orange split',
            'noir' => 'high contrast blacks, desaturated with selective color',
            'vintage' => 'faded film stock look, lifted blacks, warm cast',
            'modern' => 'clean contemporary grade, natural colors',
            'moody' => 'dark moody grade, deep shadows, selective highlights',
            'bright' => 'bright airy grade, lifted exposure, soft contrast',
            'dramatic' => 'dramatic contrast, deep blacks, specular highlights',
            'natural' => 'true-to-life color reproduction, balanced exposure',
            'pastel' => 'soft pastel tones, reduced contrast, dreamy feel',
            'neon' => 'neon accents, deep shadows, cyberpunk aesthetic',
        ];

        foreach ($gradingMap as $keyword => $grading) {
            if (str_contains($palette, $keyword)) {
                return $grading;
            }
        }

        // Default: use the palette description directly
        return "color grading based on {$palette}";
    }

    /**
     * Infer lighting style from lighting description.
     * Translates basic lighting descriptions to professional cinematography terms.
     */
    protected function inferLightingStyle(string $lighting): string
    {
        if (empty($lighting)) {
            return 'cinematic three-point lighting with soft fill';
        }

        $lighting = strtolower($lighting);

        // Match common lighting descriptions to professional terms
        $lightingMap = [
            'dramatic' => 'dramatic chiaroscuro lighting with deep shadows',
            'soft' => 'soft diffused lighting with gentle fill',
            'harsh' => 'harsh directional light with strong contrast',
            'natural' => 'naturalistic lighting with motivated sources',
            'moody' => 'moody low-key lighting with atmospheric depth',
            'bright' => 'bright high-key lighting with minimal shadows',
            'golden' => 'golden hour side lighting with long warm shadows',
            'blue' => 'blue hour ambient lighting with cool tones',
            'cinematic' => 'cinematic three-point lighting setup',
            'noir' => 'film noir high-contrast side lighting',
            'practical' => 'practical motivated lighting from in-frame sources',
            'volumetric' => 'volumetric light rays with atmospheric haze',
            'backlit' => 'backlighting with rim separation',
            'silhouette' => 'silhouette lighting against bright background',
            'rim' => 'rim lighting separating subject from background',
            'ambient' => 'ambient environmental lighting',
            'studio' => 'controlled studio lighting setup',
            'mixed' => 'mixed lighting sources for dynamic feel',
        ];

        foreach ($lightingMap as $keyword => $style) {
            if (str_contains($lighting, $keyword)) {
                return $style;
            }
        }

        // Default: use the lighting description directly with enhancement
        return "{$lighting} with cinematic depth";
    }

    /**
     * Get Master Style Guide from Story Bible.
     * Returns the style anchors that should be applied to ALL scenes.
     */
    public function getMasterStyleGuide(array $storyBible): array
    {
        $visualStyle = $storyBible['visualStyle'] ?? [];
        return $visualStyle['masterStyleGuide'] ?? [
            'colorGrading' => 'balanced neutral color grading',
            'lightingStyle' => 'cinematic three-point lighting with soft fill',
            'filmLook' => 'cinematic film look, subtle grain, shallow depth of field',
            'atmosphere' => '',
            'palette' => '',
            'lensCharacteristics' => 'anamorphic lens characteristics, natural bokeh',
            'contrastStyle' => 'balanced contrast with preserved shadow detail',
        ];
    }

    /**
     * Build continuity prompt block from Master Style Guide.
     * This should be appended to every image generation prompt.
     */
    public function buildContinuityPromptBlock(array $storyBible): string
    {
        $guide = $this->getMasterStyleGuide($storyBible);

        $parts = [];

        if (!empty($guide['colorGrading'])) {
            $parts[] = "Color Grading: {$guide['colorGrading']}";
        }

        if (!empty($guide['lightingStyle'])) {
            $parts[] = "Lighting: {$guide['lightingStyle']}";
        }

        if (!empty($guide['filmLook'])) {
            $parts[] = "Film Look: {$guide['filmLook']}";
        }

        if (!empty($guide['atmosphere'])) {
            $parts[] = "Atmosphere: {$guide['atmosphere']}";
        }

        if (!empty($guide['lensCharacteristics'])) {
            $parts[] = "Lens: {$guide['lensCharacteristics']}";
        }

        if (empty($parts)) {
            return '';
        }

        return "MASTER STYLE GUIDE (Apply to ALL scenes for visual continuity):\n" . implode("\n", $parts);
    }
}
