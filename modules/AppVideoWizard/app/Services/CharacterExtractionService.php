<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered character extraction from video scripts.
 * Based on the original video-creation-wizard creationWizardExtractCharacters function.
 */
class CharacterExtractionService
{
    /**
     * Extract characters from a video script using AI analysis.
     *
     * @param array $script The script data with scenes
     * @param array $options Additional options (genre, productionMode, styleBible)
     * @return array Result with characters array and metadata
     */
    public function extractCharacters(array $script, array $options = []): array
    {
        $scenes = $script['scenes'] ?? [];

        if (empty($scenes)) {
            return [
                'success' => false,
                'characters' => [],
                'hasHumanCharacters' => false,
                'error' => 'No scenes provided',
            ];
        }

        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $genre = $options['genre'] ?? $options['productionType'] ?? 'General';
        $productionMode = $options['productionMode'] ?? 'standard';
        $styleBible = $options['styleBible'] ?? null;
        $visualMode = $options['visualMode'] ?? null; // Master visual mode enforcement

        try {
            // Build scene content for analysis
            $sceneContent = $this->buildSceneContent($scenes);

            // Build the AI prompt with visual mode enforcement
            $prompt = $this->buildExtractionPrompt(
                $sceneContent,
                $script['title'] ?? 'Untitled',
                $genre,
                $productionMode,
                $styleBible,
                $visualMode
            );

            $startTime = microtime(true);

            // Call AI
            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            if (!empty($result['error'])) {
                Log::error('CharacterExtraction: AI error', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'characters' => [],
                    'hasHumanCharacters' => false,
                    'error' => $result['error'],
                ];
            }

            $response = $result['data'][0] ?? '';

            if (empty($response)) {
                Log::warning('CharacterExtraction: Empty AI response');
                return [
                    'success' => false,
                    'characters' => [],
                    'hasHumanCharacters' => false,
                    'error' => 'Empty AI response',
                ];
            }

            // Parse the response
            $parsed = $this->parseResponse($response);

            Log::info('CharacterExtraction: Extracted characters', [
                'count' => count($parsed['characters']),
                'hasHumanCharacters' => $parsed['hasHumanCharacters'],
                'durationMs' => $durationMs,
            ]);

            return [
                'success' => true,
                'characters' => $parsed['characters'],
                'hasHumanCharacters' => $parsed['hasHumanCharacters'],
                'suggestedStyleNote' => $parsed['suggestedStyleNote'] ?? null,
                'durationMs' => $durationMs,
            ];

        } catch (\Exception $e) {
            Log::error('CharacterExtraction: Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'characters' => [],
                'hasHumanCharacters' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build scene content string for AI analysis.
     */
    protected function buildSceneContent(array $scenes): string
    {
        $content = [];

        foreach ($scenes as $idx => $scene) {
            $sceneNum = $idx + 1;
            $narration = $scene['narration'] ?? 'No narration';
            $visual = $scene['visualDescription'] ?? $scene['visual'] ?? $scene['visualPrompt'] ?? 'No visual description';

            $content[] = "Scene {$sceneNum}:\nNarration: {$narration}\nVisual: {$visual}";
        }

        return implode("\n\n", $content);
    }

    /**
     * Build the AI extraction prompt.
     * Based on original creationWizardExtractCharacters system prompt.
     */
    protected function buildExtractionPrompt(
        string $sceneContent,
        string $title,
        string $genre,
        string $productionMode,
        ?array $styleBible,
        ?array $visualMode = null
    ): string {
        // Build visual mode enforcement (HIGHEST PRIORITY)
        $visualModeEnforcement = '';
        if ($visualMode) {
            $enforcement = $visualMode['enforcement'] ?? '';
            $keywords = $visualMode['keywords'] ?? '';
            $forbidden = $visualMode['forbidden'] ?? '';

            $visualModeEnforcement = <<<VISUAL

=== MASTER VISUAL STYLE - MANDATORY COMPLIANCE ===
{$enforcement}

REQUIRED VISUAL STYLE: {$keywords}
VISUAL;
            if (!empty($forbidden)) {
                $visualModeEnforcement .= "\nFORBIDDEN STYLES (never use these): {$forbidden}";
            }
            $visualModeEnforcement .= "\n=== END MASTER VISUAL STYLE ===\n";
        }

        $systemPrompt = <<<SYSTEM
You are an expert at analyzing video scripts and identifying characters for visual consistency.
Your task is to extract all characters that appear in the script and create detailed visual descriptions for AI image generation.
{$visualModeEnforcement}
CRITICAL RULES:
1. Focus on characters that APPEAR VISUALLY in the video (not just mentioned in narration)
2. Create SPECIFIC, CONSISTENT descriptions that can be used across all scenes
3. Include: age, gender, ethnicity, build, hair, eyes, distinctive features, clothing
4. Make descriptions CONCRETE, not vague (e.g., "short dark brown hair" not "dark hair")
5. Consider the genre and style to match character descriptions appropriately
6. If the script is abstract/conceptual with no human characters, return empty array
7. **EXTRACT ALL CHARACTERS** - Do NOT artificially limit. Include every character that appears visually.
8. **STYLE CONSISTENCY IS PARAMOUNT** - ALL character descriptions must match the Master Visual Style above
9. If visual mode is "cinematic-realistic", ALL characters must be described as real people (photorealistic, live-action actors)

**CRITICAL - INDIVIDUAL CHARACTERS ONLY:**
10. NEVER create group/collective character entries like "Warriors" or "Group of people"
11. Each entry MUST be a SINGLE, INDIVIDUAL person with their own unique description
12. If the script mentions "a group of warriors" or "diverse people", extract each INDIVIDUAL character separately
13. Give each individual a distinct name (e.g., "Warrior 1 - Ayo", "Warrior 2 - Kenji") and unique visual description
14. Each character entry represents ONE PERSON only - never multiple people in one entry

STYLE-APPROPRIATE CHARACTER GENERATION:
For CINEMATIC-REALISTIC visual mode:
- Describe characters as real people, like casting for a film
- Use realistic physical descriptions (natural skin textures, realistic features)
- Reference film/TV quality: "Like a Netflix drama lead", "Film-quality appearance"
- Even if script mentions fantasy/anime elements, describe as real actors playing roles

For STYLIZED-ANIMATION visual mode:
- Characters can have stylized, animated features
- Use animation references: "Pixar-style", "anime-inspired", "Disney-like"
- Can describe exaggerated features appropriate for animation

GENRE CONSIDERATIONS:
- Corporate/Business: Professional attire, polished appearance
- Action/Adventure: Practical clothing, battle-ready look
- Fantasy: Period-appropriate attire (but REALISTIC HUMAN if cinematic-realistic mode)
- Sci-Fi: Futuristic clothing, tech accessories
- Documentary: Natural, authentic appearance
- Lifestyle: Contemporary casual or stylish clothing
- Educational: Professional but approachable appearance
- Entertainment: Genre-appropriate styling

Return valid JSON only, no markdown formatting or code blocks.
SYSTEM;

        $styleBibleContext = '';
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            $styleBibleContext = "\n=== STYLE BIBLE (match characters to this style) ===\n";
            if (!empty($styleBible['style'])) {
                $styleBibleContext .= "Style: {$styleBible['style']}\n";
            }
            if (!empty($styleBible['colorGrade'])) {
                $styleBibleContext .= "Color Grade: {$styleBible['colorGrade']}\n";
            }
            if (!empty($styleBible['atmosphere'])) {
                $styleBibleContext .= "Atmosphere: {$styleBible['atmosphere']}\n";
            }
            if (!empty($styleBible['camera'])) {
                $styleBibleContext .= "Camera Language: {$styleBible['camera']}\n";
            }
        }

        $userPrompt = <<<USER
Analyze this script and extract character descriptions for visual consistency.

=== SCRIPT CONTENT ===
Title: {$title}
Genre: {$genre}
Production Mode: {$productionMode}

{$sceneContent}
{$styleBibleContext}
=== OUTPUT FORMAT ===
Extract ALL characters that appear visually in the script. PRIORITIZE finding every character.

{
  "characters": [
    {
      "name": "Character Name",
      "description": "Detailed visual description: age, gender, ethnicity, build, face, hair, eyes, clothing. Be specific and concrete.",
      "role": "Main/Supporting/Background",
      "appearsInScenes": [1, 2, 5],
      "traits": ["confident", "mysterious"],
      "defaultExpression": "confident and alert",
      "hair": {"color": "jet black", "style": "sleek bob", "length": "chin-length", "texture": "straight"},
      "wardrobe": {"outfit": "black tactical jacket, slim pants", "colors": "black, gray", "style": "tactical", "footwear": "combat boots"},
      "makeup": {"style": "minimal", "details": "subtle smoky eye"},
      "accessories": ["silver watch", "earrings"]
    }
  ],
  "hasHumanCharacters": true,
  "suggestedStyleNote": "Optional style note"
}

=== CRITICAL RULES ===
1. **EXTRACT ALL CHARACTERS** - Do NOT limit yourself. If script mentions 10 characters, extract 10.
2. Each character MUST be an INDIVIDUAL person (never groups like "Warriors" or "People")
3. If script mentions "a group of heroes" - extract EACH hero as separate character
4. Include both main and supporting characters
5. DNA fields (hair, wardrobe, makeup, accessories) are OPTIONAL - include if you can infer from script, otherwise leave empty objects/arrays
6. Focus on QUANTITY first - it's better to have basic descriptions for all characters than detailed DNA for few

=== QUICK REFERENCE ===
- name: Individual name (give distinct names like "Warrior 1 - Ayo", "The Hacker", "Young Woman")
- description: Core identity - age, gender, ethnicity, build, distinctive features (REQUIRED)
- role: Main/Supporting/Background
- appearsInScenes: Scene numbers where character appears (1-based)
- traits: Personality traits visible in demeanor (optional)
- hair/wardrobe/makeup/accessories: Optional DNA details if inferable from script

Return valid JSON only. Extract EVERY character that appears visually.
USER;

        return "{$systemPrompt}\n\n{$userPrompt}";
    }

    /**
     * Parse the AI response into structured character data.
     */
    protected function parseResponse(string $response): array
    {
        // Clean up response
        $response = trim($response);
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        // Try to parse JSON
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract JSON from response
            if (preg_match('/\{[\s\S]*"characters"[\s\S]*\}/m', $response, $matches)) {
                $result = json_decode($matches[0], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
            Log::warning('CharacterExtraction: Failed to parse JSON', [
                'response' => substr($response, 0, 500),
                'jsonError' => json_last_error_msg(),
            ]);
            return [
                'characters' => [],
                'hasHumanCharacters' => false,
                'suggestedStyleNote' => 'Could not analyze script for characters.',
            ];
        }

        // Normalize and validate characters with full DNA extraction
        $characters = [];
        foreach ($result['characters'] ?? [] as $idx => $char) {
            // Extract hair DNA (object with color, style, length, texture)
            $hair = $this->normalizeHairDNA($char['hair'] ?? null);

            // Extract wardrobe DNA (object with outfit, colors, style, footwear)
            $wardrobe = $this->normalizeWardrobeDNA($char['wardrobe'] ?? null);

            // Extract makeup DNA (object with style, details)
            $makeup = $this->normalizeMakeupDNA($char['makeup'] ?? null);

            // Extract accessories (array of strings)
            $accessories = $this->normalizeAccessories($char['accessories'] ?? []);

            $characters[] = [
                'id' => 'char_' . time() . '_' . $idx,
                'name' => $char['name'] ?? 'Character ' . ($idx + 1),
                'description' => $char['description'] ?? '',
                'role' => $char['role'] ?? 'Supporting',
                'appearsInScenes' => $this->normalizeSceneIndices($char['appearsInScenes'] ?? []),
                'traits' => $char['traits'] ?? [],
                'defaultExpression' => $char['defaultExpression'] ?? '',
                'referenceImage' => null,
                'autoDetected' => true,
                'aiGenerated' => true,
                // Character DNA fields - auto-extracted from script
                'hair' => $hair,
                'wardrobe' => $wardrobe,
                'makeup' => $makeup,
                'accessories' => $accessories,
            ];
        }

        return [
            'characters' => $characters,
            'hasHumanCharacters' => $result['hasHumanCharacters'] ?? (count($characters) > 0),
            'suggestedStyleNote' => $result['suggestedStyleNote'] ?? null,
        ];
    }

    /**
     * Normalize scene indices (AI might return 1-based, we need 0-based).
     */
    protected function normalizeSceneIndices(array $indices): array
    {
        // Convert to 0-based indices and ensure integers
        $normalized = [];
        foreach ($indices as $index) {
            $idx = (int) $index;
            // If AI returned 1-based (scene 1 = index 0), convert
            if ($idx > 0) {
                $normalized[] = $idx - 1;
            } else {
                $normalized[] = $idx;
            }
        }
        return array_unique($normalized);
    }

    /**
     * Normalize hair DNA from AI response.
     * Expected structure: {color, style, length, texture}
     */
    protected function normalizeHairDNA($hair): array
    {
        if (empty($hair) || !is_array($hair)) {
            return [
                'color' => '',
                'style' => '',
                'length' => '',
                'texture' => '',
            ];
        }

        return [
            'color' => $hair['color'] ?? '',
            'style' => $hair['style'] ?? '',
            'length' => $hair['length'] ?? '',
            'texture' => $hair['texture'] ?? '',
        ];
    }

    /**
     * Normalize wardrobe DNA from AI response.
     * Expected structure: {outfit, colors, style, footwear}
     */
    protected function normalizeWardrobeDNA($wardrobe): array
    {
        if (empty($wardrobe) || !is_array($wardrobe)) {
            return [
                'outfit' => '',
                'colors' => '',
                'style' => '',
                'footwear' => '',
            ];
        }

        return [
            'outfit' => $wardrobe['outfit'] ?? '',
            'colors' => $wardrobe['colors'] ?? '',
            'style' => $wardrobe['style'] ?? '',
            'footwear' => $wardrobe['footwear'] ?? '',
        ];
    }

    /**
     * Normalize makeup DNA from AI response.
     * Expected structure: {style, details}
     */
    protected function normalizeMakeupDNA($makeup): array
    {
        if (empty($makeup) || !is_array($makeup)) {
            return [
                'style' => '',
                'details' => '',
            ];
        }

        return [
            'style' => $makeup['style'] ?? '',
            'details' => $makeup['details'] ?? '',
        ];
    }

    /**
     * Normalize accessories from AI response.
     * Expected: array of strings
     */
    protected function normalizeAccessories($accessories): array
    {
        if (empty($accessories)) {
            return [];
        }

        // If it's a string, split by comma
        if (is_string($accessories)) {
            return array_map('trim', explode(',', $accessories));
        }

        // If it's already an array, ensure all items are strings
        if (is_array($accessories)) {
            return array_values(array_filter(array_map(function ($item) {
                return is_string($item) ? trim($item) : '';
            }, $accessories)));
        }

        return [];
    }

    /**
     * Auto-detect Character Intelligence settings from script.
     * Analyzes script to suggest: narration style, character count, voice assignments.
     *
     * @param array $script The script data with scenes
     * @param array $options Additional options
     * @return array Character Intelligence suggestions
     */
    public function autoDetectCharacterIntelligence(array $script, array $options = []): array
    {
        $scenes = $script['scenes'] ?? [];
        $productionType = $options['productionType'] ?? null;

        // Default result
        $result = [
            'enabled' => true,
            'narrationStyle' => 'voiceover',
            'characterCount' => 0,
            'suggestedCount' => 0,
            'hasDialogue' => false,
            'hasMultipleSpeakers' => false,
            'dialogueScenes' => [],
            'voiceoverScenes' => [],
            'detectionConfidence' => 'low',
        ];

        if (empty($scenes)) {
            return $result;
        }

        // Analyze each scene for dialogue patterns
        $dialoguePatterns = [
            '/\b(said|says|replied|asked|exclaimed|whispered|shouted|muttered)\b/i',
            '/["\'"][\w\s,!?.]+["\']/i', // Quoted speech
            '/\b[A-Z][A-Za-z]*:\s+["\']?/i', // Character name: "dialogue"
            '/--\s*[A-Z][a-z]+/i', // -- Character attribution
        ];

        $dialogueSceneCount = 0;
        $voiceoverSceneCount = 0;
        $detectedSpeakers = [];

        foreach ($scenes as $idx => $scene) {
            $narration = $scene['narration'] ?? '';
            $hasDialogue = false;

            // Check for dialogue patterns
            foreach ($dialoguePatterns as $pattern) {
                if (preg_match($pattern, $narration)) {
                    $hasDialogue = true;
                    break;
                }
            }

            // Extract potential speaker names
            if (preg_match_all('/\b([A-Z][a-z]+):\s/i', $narration, $matches)) {
                foreach ($matches[1] as $speaker) {
                    $detectedSpeakers[$speaker] = ($detectedSpeakers[$speaker] ?? 0) + 1;
                }
            }

            if ($hasDialogue) {
                $dialogueSceneCount++;
                $result['dialogueScenes'][] = $idx;
            } else {
                $voiceoverSceneCount++;
                $result['voiceoverScenes'][] = $idx;
            }
        }

        $totalScenes = count($scenes);
        $dialoguePercentage = $totalScenes > 0 ? ($dialogueSceneCount / $totalScenes) * 100 : 0;

        // Determine narration style based on analysis
        if ($dialoguePercentage > 70) {
            $result['narrationStyle'] = 'dialogue';
            $result['detectionConfidence'] = 'high';
        } elseif ($dialoguePercentage > 30) {
            $result['narrationStyle'] = 'narrator'; // Mix of narrator + dialogue
            $result['detectionConfidence'] = 'medium';
        } else {
            $result['narrationStyle'] = 'voiceover';
            $result['detectionConfidence'] = $dialoguePercentage > 10 ? 'medium' : 'high';
        }

        // Apply production type defaults if available
        if ($productionType) {
            $productionTypes = config('appvideowizard.production_types', []);
            foreach ($productionTypes as $category) {
                $subTypes = $category['subTypes'] ?? [];
                if (isset($subTypes[$productionType])) {
                    $subType = $subTypes[$productionType];
                    // Only override if detection confidence is low
                    if ($result['detectionConfidence'] === 'low' && !empty($subType['defaultNarration'])) {
                        $result['narrationStyle'] = $subType['defaultNarration'];
                    }
                    break;
                }
            }
        }

        // Calculate character count
        $uniqueSpeakers = count($detectedSpeakers);
        $result['characterCount'] = $uniqueSpeakers;
        $result['suggestedCount'] = max(1, $uniqueSpeakers);
        $result['hasDialogue'] = $dialogueSceneCount > 0;
        $result['hasMultipleSpeakers'] = $uniqueSpeakers > 1;
        $result['detectedSpeakers'] = array_keys($detectedSpeakers);

        // Log detection results
        Log::info('CharacterIntelligence: Auto-detection completed', [
            'narrationStyle' => $result['narrationStyle'],
            'dialoguePercentage' => round($dialoguePercentage, 1),
            'uniqueSpeakers' => $uniqueSpeakers,
            'confidence' => $result['detectionConfidence'],
        ]);

        return $result;
    }
}
