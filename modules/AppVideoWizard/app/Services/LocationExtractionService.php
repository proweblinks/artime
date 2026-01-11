<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered location extraction from video scripts.
 * Based on the original video-creation-wizard LOCATION_BIBLE_GENERATOR.
 */
class LocationExtractionService
{
    /**
     * Extract locations from a video script using AI analysis.
     *
     * @param array $script The script data with scenes
     * @param array $options Additional options (genre, productionType, styleBible, visualMode)
     * @return array Result with locations array and metadata
     */
    public function extractLocations(array $script, array $options = []): array
    {
        $scenes = $script['scenes'] ?? [];

        if (empty($scenes)) {
            return [
                'success' => false,
                'locations' => [],
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
                Log::error('LocationExtraction: AI error', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'locations' => [],
                    'error' => $result['error'],
                ];
            }

            $response = $result['data'][0] ?? '';

            if (empty($response)) {
                Log::warning('LocationExtraction: Empty AI response');
                return [
                    'success' => false,
                    'locations' => [],
                    'error' => 'Empty AI response',
                ];
            }

            // Parse the response
            $parsed = $this->parseResponse($response);

            Log::info('LocationExtraction: Extracted locations', [
                'count' => count($parsed['locations']),
                'durationMs' => $durationMs,
            ]);

            return [
                'success' => true,
                'locations' => $parsed['locations'],
                'suggestedStyleNote' => $parsed['suggestedStyleNote'] ?? null,
                'durationMs' => $durationMs,
            ];

        } catch (\Exception $e) {
            Log::error('LocationExtraction: Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'locations' => [],
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
            $location = $scene['location'] ?? $scene['setting'] ?? '';

            $sceneEntry = "Scene {$sceneNum}:\nVisual: {$visual}\nNarration: {$narration}";
            if (!empty($location)) {
                $sceneEntry .= "\nExplicit Location: {$location}";
            }

            $content[] = $sceneEntry;
        }

        return implode("\n\n", $content);
    }

    /**
     * Build the AI extraction prompt for locations.
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
You are an expert at analyzing video scripts and identifying locations for visual consistency.
Your task is to extract all unique locations that appear in the script and create detailed visual descriptions for AI image generation.
{$visualModeEnforcement}
CRITICAL RULES:
1. Identify DISTINCT LOCATIONS that appear visually in the video
2. Merge similar locations (e.g., "office" and "corporate office" are the same location)
3. Create SPECIFIC, CONSISTENT descriptions for each location
4. Include: setting type (interior/exterior), time of day, weather, atmosphere, architectural details, colors, textures
5. Track which scenes each location appears in
6. **EXTRACT ALL LOCATIONS** - Do NOT artificially limit. Include every distinct environment.
7. **STYLE CONSISTENCY IS PARAMOUNT** - ALL locations must match the Master Visual Style above
8. If the visual mode is "cinematic-realistic", ALL locations must be real-world, photorealistic settings - NO fantasy, cartoon, or stylized imagery

LOCATION ANALYSIS:
- Look for explicit location mentions in narration
- Infer locations from visual descriptions
- Group scenes that share the same location
- Note any time-of-day or weather variations

STYLE-APPROPRIATE LOCATION GENERATION:
For CINEMATIC-REALISTIC visual mode:
- All locations must be real-world, physically plausible environments
- Use film photography references (ARRI, RED, Panavision quality)
- Describe practical lighting, real textures, architectural authenticity
- Even if script mentions fantasy elements, interpret as "movie set" or "practical effects" or "real-world inspired location"
- Example: "Mystical forest" → "Pacific Northwest old-growth forest at dusk, fog rolling through ancient redwoods, practical lighting through canopy"

For STYLIZED-ANIMATION visual mode:
- Locations can be stylized, illustrated, animated
- Use animation studio references (Pixar, Disney, anime)
- Describe stylized colors, exaggerated features, artistic interpretation

Return valid JSON only, no markdown formatting or code blocks.
SYSTEM;

        $styleBibleContext = '';
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            $styleBibleContext = "\n=== STYLE BIBLE (match locations to this style) ===\n";
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
Analyze this script and extract location descriptions for visual consistency.

=== SCRIPT CONTENT ===
Title: {$title}
Genre: {$genre}
Production Mode: {$productionMode}

{$sceneContent}
{$styleBibleContext}
=== OUTPUT FORMAT ===
Extract ALL distinct locations that appear in the script. PRIORITIZE finding every location.

{
  "locations": [
    {
      "name": "Location Name",
      "description": "Detailed visual description: architecture, materials, colors, textures, key elements. Be specific.",
      "type": "interior/exterior/abstract",
      "timeOfDay": "day/night/dawn/dusk/golden-hour",
      "weather": "clear/cloudy/rainy/foggy/stormy/snowy",
      "atmosphere": "professional/mysterious/energetic/peaceful/tense/romantic",
      "mood": "tense/hopeful/melancholy/energetic/intimate/ominous",
      "lightingStyle": "e.g., soft daylight, harsh fluorescent, warm golden hour",
      "appearsInScenes": [1, 2, 5],
      "stateChanges": []
    }
  ],
  "hasDistinctLocations": true,
  "suggestedStyleNote": "Optional style note"
}

=== CRITICAL RULES ===
1. **EXTRACT ALL LOCATIONS** - Do NOT limit yourself. Every distinct environment should be a separate location.
2. Each scene may have its own location - extract them all
3. If the same location appears multiple times, list it once with all scene numbers
4. DNA fields (mood, lightingStyle, stateChanges) are OPTIONAL - include if inferable
5. Focus on QUANTITY first - basic descriptions for all locations is better than detailed DNA for few

=== QUICK REFERENCE ===
- name: Distinctive location name (e.g., "Corporate Boardroom", "Forest Clearing", "Space Station Bridge")
- description: Visual details for AI image generation (REQUIRED)
- type: interior/exterior/abstract
- timeOfDay: day/night/dawn/dusk/golden-hour
- weather: clear/cloudy/rainy/foggy/stormy/snowy
- atmosphere: The general feel of the location
- mood/lightingStyle: Optional DNA details
- stateChanges: Optional - only if location visually changes across scenes

Return valid JSON only. Extract EVERY distinct location.
USER;

        return "{$systemPrompt}\n\n{$userPrompt}";
    }

    /**
     * Parse the AI response into structured location data.
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
            if (preg_match('/\{[\s\S]*"locations"[\s\S]*\}/m', $response, $matches)) {
                $result = json_decode($matches[0], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
            Log::warning('LocationExtraction: Failed to parse JSON', [
                'response' => substr($response, 0, 500),
                'jsonError' => json_last_error_msg(),
            ]);
            return [
                'locations' => [],
                'hasDistinctLocations' => false,
                'suggestedStyleNote' => 'Could not analyze script for locations.',
            ];
        }

        // Normalize and validate locations with full DNA extraction
        $locations = [];
        foreach ($result['locations'] ?? [] as $idx => $loc) {
            $locations[] = [
                'id' => 'loc_' . time() . '_' . $idx,
                'name' => $loc['name'] ?? 'Location ' . ($idx + 1),
                'description' => $loc['description'] ?? '',
                'type' => $this->normalizeLocationType($loc['type'] ?? 'exterior'),
                'timeOfDay' => $this->normalizeTimeOfDay($loc['timeOfDay'] ?? 'day'),
                'weather' => $this->normalizeWeather($loc['weather'] ?? 'clear'),
                'atmosphere' => $loc['atmosphere'] ?? '',
                // Location DNA fields - auto-extracted from script by AI
                'mood' => $loc['mood'] ?? '',
                'lightingStyle' => $loc['lightingStyle'] ?? '',
                'scenes' => $this->normalizeSceneIndices($loc['appearsInScenes'] ?? []),
                'stateChanges' => $this->normalizeStateChanges($loc['stateChanges'] ?? []),
                'referenceImage' => null,
                'autoDetected' => true,
                'aiGenerated' => true,
            ];
        }

        return [
            'locations' => $locations,
            'hasDistinctLocations' => $result['hasDistinctLocations'] ?? (count($locations) > 0),
            'suggestedStyleNote' => $result['suggestedStyleNote'] ?? null,
        ];
    }

    /**
     * Normalize location type.
     */
    protected function normalizeLocationType(string $type): string
    {
        $type = strtolower(trim($type));
        $validTypes = ['interior', 'exterior', 'abstract'];
        return in_array($type, $validTypes) ? $type : 'exterior';
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
            'goldenhour' => 'golden-hour',
        ];
        return $mapping[$time] ?? 'day';
    }

    /**
     * Normalize weather.
     */
    protected function normalizeWeather(string $weather): string
    {
        $weather = strtolower(trim($weather));
        $mapping = [
            'clear' => 'clear',
            'sunny' => 'clear',
            'bright' => 'clear',
            'cloudy' => 'cloudy',
            'overcast' => 'cloudy',
            'rainy' => 'rainy',
            'rain' => 'rainy',
            'raining' => 'rainy',
            'foggy' => 'foggy',
            'fog' => 'foggy',
            'misty' => 'foggy',
            'stormy' => 'stormy',
            'storm' => 'stormy',
            'thunder' => 'stormy',
            'snowy' => 'snowy',
            'snow' => 'snowy',
            'winter' => 'snowy',
        ];
        return $mapping[$weather] ?? 'clear';
    }

    /**
     * Normalize scene indices (AI might return 1-based, we need 0-based).
     */
    protected function normalizeSceneIndices(array $indices): array
    {
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
     * Normalize state changes to use consistent field names.
     * Supports both new format (sceneIndex/stateDescription) and legacy format (scene/state).
     */
    protected function normalizeStateChanges(array $stateChanges): array
    {
        $normalized = [];
        foreach ($stateChanges as $change) {
            // Support both field name formats
            $sceneIndex = $change['sceneIndex'] ?? $change['scene'] ?? null;
            $stateDescription = $change['stateDescription'] ?? $change['state'] ?? '';

            if ($sceneIndex !== null) {
                $normalized[] = [
                    'sceneIndex' => (int) $sceneIndex,
                    'stateDescription' => $stateDescription,
                ];
            }
        }
        return $normalized;
    }
}
