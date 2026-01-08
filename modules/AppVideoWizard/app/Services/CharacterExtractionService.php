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

        try {
            // Build scene content for analysis
            $sceneContent = $this->buildSceneContent($scenes);

            // Build the AI prompt
            $prompt = $this->buildExtractionPrompt(
                $sceneContent,
                $script['title'] ?? 'Untitled',
                $genre,
                $productionMode,
                $styleBible
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
        ?array $styleBible
    ): string {
        $systemPrompt = <<<SYSTEM
You are an expert at analyzing video scripts and identifying characters for visual consistency.
Your task is to extract all characters that appear in the script and create detailed visual descriptions for AI image generation.

CRITICAL RULES:
1. Focus on characters that APPEAR VISUALLY in the video (not just mentioned in narration)
2. Create SPECIFIC, CONSISTENT descriptions that can be used across all scenes
3. Include: age, gender, ethnicity, build, hair, eyes, distinctive features, clothing
4. Make descriptions CONCRETE, not vague (e.g., "short dark brown hair" not "dark hair")
5. Consider the genre and style to match character descriptions appropriately
6. If the script is abstract/conceptual with no human characters, return empty array
7. Maximum 5 characters (focus on main/recurring characters)

GENRE CONSIDERATIONS:
- Corporate/Business: Professional attire, polished appearance
- Action/Adventure: Practical clothing, battle-ready look
- Fantasy: Period-appropriate or magical attire
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
        }

        $userPrompt = <<<USER
Analyze this script and extract character descriptions for visual consistency.

=== SCRIPT CONTENT ===
Title: {$title}
Genre: {$genre}
Production Mode: {$productionMode}

{$sceneContent}
{$styleBibleContext}
=== REQUIRED OUTPUT FORMAT ===
{
  "characters": [
    {
      "name": "Character Name or Role (e.g., 'The Protagonist', 'Sarah', 'The CEO')",
      "description": "Detailed physical description for AI image generation: age, gender, ethnicity, build, hair color and style, eye color, distinctive features, specific clothing and accessories. Be very specific and concrete.",
      "role": "Main/Supporting/Background",
      "appearsInScenes": [1, 2, 5],
      "traits": ["confident", "mysterious", "professional"]
    }
  ],
  "hasHumanCharacters": true,
  "suggestedStyleNote": "Optional note about character style recommendations"
}

If there are no human characters or the video is purely abstract/conceptual/nature footage, return:
{
  "characters": [],
  "hasHumanCharacters": false,
  "suggestedStyleNote": "This script focuses on [type of content] without human characters."
}
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

        // Normalize and validate characters
        $characters = [];
        foreach ($result['characters'] ?? [] as $idx => $char) {
            $characters[] = [
                'id' => 'char_' . time() . '_' . $idx,
                'name' => $char['name'] ?? 'Character ' . ($idx + 1),
                'description' => $char['description'] ?? '',
                'role' => $char['role'] ?? 'Supporting',
                'appearsInScenes' => $this->normalizeSceneIndices($char['appearsInScenes'] ?? []),
                'traits' => $char['traits'] ?? [],
                'referenceImage' => null,
                'autoDetected' => true,
                'aiGenerated' => true,
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
