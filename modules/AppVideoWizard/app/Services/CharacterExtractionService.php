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
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

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

            // Call AI with tier-based model selection
            $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, ['maxResult' => 1]);

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
Your task is to extract ALL characters that appear in the script and create detailed visual descriptions for AI image generation.
{$visualModeEnforcement}
CORE TASK:
1. Read the script carefully and identify EVERY person who appears visually
2. Create a DETAILED visual description for each character (age, gender, ethnicity, build, hair, eyes, clothing)
3. Track which scenes each character appears in

CRITICAL REQUIREMENTS:
- Extract ALL characters - do not limit or combine them
- Every character MUST have a detailed description (never empty)
- Each entry is ONE individual person (never groups)
- Descriptions must be specific: "brown shoulder-length wavy hair" not just "brown hair"
- If script mentions "a group of people", extract each individual separately with unique descriptions

STYLE MATCHING:
For CINEMATIC-REALISTIC mode: Describe as real people, like casting for a film
For STYLIZED-ANIMATION mode: Can include stylized/animated features

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
      "description": "Full visual description: age, gender, ethnicity, build, face shape, hair color and style, eye color, skin tone, distinctive features, typical clothing. Example: A confident woman in her early 30s with warm brown skin, long dark curly hair, and striking amber eyes. Athletic build, wearing a tailored navy blazer over a white silk blouse.",
      "role": "Main/Supporting/Background",
      "appearsInScenes": [1, 2, 5],
      "traits": ["confident", "mysterious"],
      "defaultExpression": "confident and alert"
    }
  ],
  "hasHumanCharacters": true,
  "suggestedStyleNote": "Optional style note"
}

=== CRITICAL RULES ===
1. **EXTRACT ALL CHARACTERS** - If the script shows 5 people, extract 5 separate characters
2. Each character MUST have a DETAILED description field - this is REQUIRED, never leave it empty
3. Each entry MUST be a SINGLE individual person (never groups)
4. Description must include: age, gender, ethnicity/skin tone, build, hair, eyes, clothing
5. If script mentions "a group" - extract EACH individual as their own character entry

=== EXAMPLE DESCRIPTIONS ===
Good: "A tall African American man in his late 40s with a shaved head, warm brown skin, and deep-set dark eyes. Broad-shouldered with an authoritative presence. Wears a charcoal business suit with a burgundy tie."
Good: "A young East Asian woman, early 20s, with shoulder-length black hair and almond-shaped brown eyes. Petite build, casual style with an oversized denim jacket and vintage band t-shirt."
Bad: "A mysterious figure" (too vague)
Bad: "" (empty description is NOT allowed)

Return valid JSON only. Extract EVERY character with FULL descriptions.
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

    /**
     * Enrich characters that have missing or incomplete descriptions.
     * Makes targeted AI calls to generate descriptions for characters that were
     * truncated due to response length limits during initial extraction.
     *
     * @param array $characters Array of character data
     * @param array $script The script data for context
     * @param array $options Options including teamId, visualMode, batchSize
     * @return array Enriched characters array
     */
    public function enrichIncompleteCharacters(array $characters, array $script, array $options = []): array
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $visualMode = $options['visualMode'] ?? null;
        $batchSize = $options['batchSize'] ?? 3;
        $minDescriptionLength = $options['minDescriptionLength'] ?? 30;
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        // Identify characters needing enrichment
        $needsEnrichment = [];
        $complete = [];

        foreach ($characters as $idx => $char) {
            $description = trim($char['description'] ?? '');
            if (empty($description) || strlen($description) < $minDescriptionLength) {
                $needsEnrichment[$idx] = $char;
            } else {
                $complete[$idx] = $char;
            }
        }

        if (empty($needsEnrichment)) {
            Log::info('CharacterEnrichment: All characters have descriptions', [
                'totalCharacters' => count($characters),
            ]);
            return $characters;
        }

        Log::info('CharacterEnrichment: Starting enrichment', [
            'totalCharacters' => count($characters),
            'needsEnrichment' => count($needsEnrichment),
            'batchSize' => $batchSize,
        ]);

        // Build scene context for enrichment (condensed)
        $sceneContext = $this->buildCondensedSceneContext($script['scenes'] ?? []);

        // Process in batches to avoid token limits
        $batches = array_chunk($needsEnrichment, $batchSize, true);
        $enriched = [];

        foreach ($batches as $batchIdx => $batch) {
            try {
                $batchEnriched = $this->enrichBatch($batch, $sceneContext, $visualMode, $teamId, $aiModelTier);
                foreach ($batchEnriched as $charIdx => $enrichedChar) {
                    $enriched[$charIdx] = $enrichedChar;
                }

                Log::info('CharacterEnrichment: Batch completed', [
                    'batchIndex' => $batchIdx + 1,
                    'batchSize' => count($batch),
                    'enrichedCount' => count($batchEnriched),
                ]);
            } catch (\Exception $e) {
                Log::warning('CharacterEnrichment: Batch failed, keeping originals', [
                    'batchIndex' => $batchIdx + 1,
                    'error' => $e->getMessage(),
                ]);
                // Keep original characters on failure
                foreach ($batch as $charIdx => $char) {
                    $enriched[$charIdx] = $char;
                }
            }
        }

        // Merge enriched characters back with complete ones
        $result = $complete + $enriched;
        ksort($result); // Restore original order

        Log::info('CharacterEnrichment: Completed', [
            'totalEnriched' => count($enriched),
            'totalFinal' => count($result),
        ]);

        return array_values($result);
    }

    /**
     * Enrich a batch of characters with AI-generated descriptions.
     */
    protected function enrichBatch(array $characters, string $sceneContext, ?array $visualMode, int $teamId, string $aiModelTier = 'economy'): array
    {
        // Build character list for prompt
        $charList = [];
        $charMap = []; // Map names to original indices
        foreach ($characters as $idx => $char) {
            $name = $char['name'] ?? 'Unknown';
            $role = $char['role'] ?? 'Supporting';
            $scenes = $char['appliedScenes'] ?? $char['appearsInScenes'] ?? [];
            $sceneStr = !empty($scenes) ? implode(', ', array_map(fn($s) => $s + 1, $scenes)) : 'various';

            $charList[] = "- {$name} ({$role}): appears in scenes {$sceneStr}";
            $charMap[$name] = $idx;
        }

        $charListStr = implode("\n", $charList);

        // Build visual mode context
        $visualContext = '';
        if ($visualMode) {
            $enforcement = $visualMode['enforcement'] ?? '';
            $keywords = $visualMode['keywords'] ?? '';
            $visualContext = "\nVISUAL STYLE: {$enforcement}\nRequired keywords: {$keywords}\n";
        }

        $prompt = <<<PROMPT
Generate detailed visual descriptions for these characters. Each description must be specific enough for AI image generation.
{$visualContext}
SCENE CONTEXT (condensed):
{$sceneContext}

CHARACTERS NEEDING DESCRIPTIONS:
{$charListStr}

For EACH character, provide a detailed visual description including:
- Age (specific range like "early 30s")
- Gender
- Ethnicity/skin tone
- Build (athletic, slim, stocky, etc.)
- Hair (color, length, style)
- Eyes (color, shape)
- Distinctive features
- Typical clothing/wardrobe

Return ONLY valid JSON in this exact format:
{
  "characters": [
    {
      "name": "Exact Character Name",
      "description": "Full detailed visual description..."
    }
  ]
}

CRITICAL: Use the EXACT character names provided. Generate unique, detailed descriptions for each.
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, ['maxResult' => 1]);

        if (!empty($result['error'])) {
            throw new \Exception('AI error: ' . $result['error']);
        }

        $response = $result['data'][0] ?? '';
        if (empty($response)) {
            throw new \Exception('Empty AI response');
        }

        // Parse response
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $parsed = json_decode(trim($response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract JSON
            if (preg_match('/\{[\s\S]*"characters"[\s\S]*\}/m', $response, $matches)) {
                $parsed = json_decode($matches[0], true);
            }
        }

        if (!is_array($parsed) || empty($parsed['characters'])) {
            throw new \Exception('Could not parse character descriptions');
        }

        // Map descriptions back to original characters
        $enriched = [];
        foreach ($parsed['characters'] as $enrichedChar) {
            $name = $enrichedChar['name'] ?? '';
            $description = $enrichedChar['description'] ?? '';

            // Find matching character by name (fuzzy match)
            $matchedIdx = null;
            foreach ($charMap as $origName => $idx) {
                if (strcasecmp($name, $origName) === 0 ||
                    stripos($origName, $name) !== false ||
                    stripos($name, $origName) !== false) {
                    $matchedIdx = $idx;
                    break;
                }
            }

            if ($matchedIdx !== null && !empty($description)) {
                $enriched[$matchedIdx] = $characters[$matchedIdx];
                $enriched[$matchedIdx]['description'] = $description;
                $enriched[$matchedIdx]['enriched'] = true; // Flag for debugging
            }
        }

        // Ensure all characters in batch are returned (even if not enriched)
        foreach ($characters as $idx => $char) {
            if (!isset($enriched[$idx])) {
                $enriched[$idx] = $char;
            }
        }

        return $enriched;
    }

    /**
     * Build condensed scene context for enrichment prompts.
     * Shorter than full extraction to save tokens.
     */
    protected function buildCondensedSceneContext(array $scenes): string
    {
        $context = [];
        foreach ($scenes as $idx => $scene) {
            $sceneNum = $idx + 1;
            $visual = $scene['visualDescription'] ?? $scene['visual'] ?? '';
            // Truncate to first 100 chars
            if (strlen($visual) > 100) {
                $visual = substr($visual, 0, 100) . '...';
            }
            $context[] = "Scene {$sceneNum}: {$visual}";
        }
        return implode("\n", $context);
    }

    /**
     * Sort characters by importance (role priority, then scene count).
     *
     * @param array $characters Array of character data
     * @param string $method Sort method: 'role_then_scenes', 'scenes_only', 'alphabetical'
     * @return array Sorted characters array
     */
    public function sortCharactersByImportance(array $characters, string $method = 'role_then_scenes'): array
    {
        if (empty($characters)) {
            return $characters;
        }

        // Define role priority (lower = more important)
        $rolePriority = [
            'Main' => 1,
            'main' => 1,
            'Lead' => 1,
            'lead' => 1,
            'Primary' => 1,
            'primary' => 1,
            'Supporting' => 2,
            'supporting' => 2,
            'Secondary' => 2,
            'secondary' => 2,
            'Background' => 3,
            'background' => 3,
            'Extra' => 3,
            'extra' => 3,
            'Minor' => 3,
            'minor' => 3,
        ];

        usort($characters, function ($a, $b) use ($method, $rolePriority) {
            if ($method === 'alphabetical') {
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            }

            $aScenes = count($a['appliedScenes'] ?? $a['appearsInScenes'] ?? []);
            $bScenes = count($b['appliedScenes'] ?? $b['appearsInScenes'] ?? []);

            if ($method === 'scenes_only') {
                return $bScenes - $aScenes; // Descending by scene count
            }

            // role_then_scenes (default)
            $aRole = $a['role'] ?? 'Supporting';
            $bRole = $b['role'] ?? 'Supporting';
            $aRolePriority = $rolePriority[$aRole] ?? 2;
            $bRolePriority = $rolePriority[$bRole] ?? 2;

            // First compare by role
            if ($aRolePriority !== $bRolePriority) {
                return $aRolePriority - $bRolePriority;
            }

            // Then by scene count (descending)
            return $bScenes - $aScenes;
        });

        Log::info('CharacterSorting: Characters sorted', [
            'method' => $method,
            'count' => count($characters),
            'topCharacter' => $characters[0]['name'] ?? 'N/A',
        ]);

        return $characters;
    }
}
