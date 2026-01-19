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
            $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
                'maxResult' => 1,
                'max_tokens' => 10000, // Ensure enough tokens for all locations
            ]);

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

            // Consolidate fragmented locations (merge building rooms, related areas)
            $locations = $this->consolidateLocations($parsed['locations']);

            Log::info('LocationExtraction: Extracted and consolidated locations', [
                'beforeConsolidation' => count($parsed['locations']),
                'afterConsolidation' => count($locations),
                'durationMs' => $durationMs,
            ]);

            return [
                'success' => true,
                'locations' => $locations,
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
1. **CONSOLIDATE RELATED LOCATIONS** - Different rooms/areas of the same building = ONE location
   - "Living Room", "Kitchen", "Bedroom" in same house = "The House" or "Home"
   - "Office Lobby", "Office Meeting Room", "Office Rooftop" = "Corporate Office"
2. **MERGE OUTDOOR AREAS** - Related outdoor spaces = ONE location
   - "Forest Path", "Forest Clearing", "Dense Woods" = "Forest"
   - "Beach Shore", "Beach Dunes" = "Beach"
3. **TIME IS NOT A LOCATION** - Same place at different times = ONE entry
   - "Dock at Dawn" and "Dock at Night" = "Dock" (note time variations)
4. Create SPECIFIC, CONSISTENT descriptions for each location
5. Include: setting type (interior/exterior), time of day, weather, atmosphere, architectural details
6. Track which scenes each location appears in
7. **STYLE CONSISTENCY IS PARAMOUNT** - ALL locations must match the Master Visual Style above
8. If the visual mode is "cinematic-realistic", ALL locations must be real-world, photorealistic settings - NO fantasy, cartoon, or stylized imagery
9. A 5-minute film typically has 3-6 distinct locations, not 10+

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
1. **CONSOLIDATE** - Rooms in the same building = ONE location (e.g., "John's Home" not "John's Kitchen" + "John's Bedroom")
2. **MERGE** - Related outdoor areas = ONE location (e.g., "Forest" not "Forest Path" + "Forest Clearing")
3. If the same location appears multiple times, list it once with all scene numbers
4. DNA fields (mood, lightingStyle, stateChanges) are OPTIONAL - include if inferable
5. Focus on DISTINCT VISUAL ENVIRONMENTS - a typical 5-min film has 3-6 locations, not 10+

=== CONSOLIDATION EXAMPLES ===
WRONG (fragmented):
- "John's House Exterior"
- "John's House Living Room"
- "John's House Kitchen"

CORRECT (consolidated):
- "John's House" (includes all rooms/areas)

WRONG (fragmented):
- "Forest Path"
- "Forest Clearing"
- "Dark Forest"

CORRECT (consolidated):
- "Forest" (various areas within)

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
            Log::warning('LocationExtraction: Initial JSON parse failed, attempting repair', [
                'error' => json_last_error_msg(),
            ]);

            // Try to extract and repair JSON from response
            if (preg_match('/\{[\s\S]*"locations"[\s\S]*/', $response, $matches)) {
                $repairedJson = $this->repairTruncatedJson($matches[0]);
                $result = json_decode($repairedJson, true);
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
     * Consolidate fragmented locations into unified entries.
     * Merges building rooms into single building, related outdoor areas into single area.
     *
     * @param array $locations Array of extracted locations
     * @return array Consolidated locations
     */
    protected function consolidateLocations(array $locations): array
    {
        if (empty($locations)) {
            return $locations;
        }

        $consolidated = [];
        $hierarchyMap = []; // Track "Building" -> index for merging "Building Room"

        foreach ($locations as $loc) {
            $name = $loc['name'] ?? '';
            $baseName = $this->extractLocationBaseName($name);

            // Check if this is a sub-location of an existing entry
            if (isset($hierarchyMap[$baseName])) {
                // Merge into existing location
                $existingIdx = $hierarchyMap[$baseName];
                $consolidated[$existingIdx] = $this->mergeLocations($consolidated[$existingIdx], $loc);
                Log::debug('LocationExtraction: Merged sub-location', [
                    'subLocation' => $name,
                    'mergedWith' => $consolidated[$existingIdx]['name'],
                ]);
                continue;
            }

            // Check if any existing location should be merged with this one
            $foundParent = false;
            foreach ($consolidated as $idx => $existing) {
                if ($this->shouldMergeLocations($name, $existing['name'])) {
                    // Merge with existing
                    $consolidated[$idx] = $this->mergeLocations($existing, $loc);
                    $hierarchyMap[$baseName] = $idx;
                    $foundParent = true;
                    Log::debug('LocationExtraction: Merged related location', [
                        'location' => $name,
                        'mergedWith' => $consolidated[$idx]['name'],
                    ]);
                    break;
                }
            }

            if (!$foundParent) {
                // Add as new location
                $hierarchyMap[$baseName] = count($consolidated);
                $consolidated[] = $loc;
            }
        }

        return array_values($consolidated);
    }

    /**
     * Extract base name for location hierarchy detection.
     * "Elias's Home Living Room" -> "elias's home"
     * "Corporate Office Lobby" -> "corporate office"
     *
     * @param string $name Location name
     * @return string Normalized base name
     */
    protected function extractLocationBaseName(string $name): string
    {
        $name = strtolower(trim($name));

        // Common room/area suffixes to remove
        $suffixes = [
            // Building rooms
            '/\s+(room|lobby|hallway|corridor|entrance|foyer|kitchen|bedroom|bathroom|living\s*room|dining\s*room|study|office|basement|attic|garage|garden|yard|patio|balcony|rooftop|exterior|interior)$/i',
            // Outdoor areas
            '/\s+(path|clearing|trail|meadow|shore|dunes|cliff|peak|base|edge|depths|outskirts)$/i',
            // Time of day variations
            '/\s+at\s+(dawn|dusk|night|day|noon|morning|evening|sunset|sunrise)$/i',
        ];

        foreach ($suffixes as $pattern) {
            $name = preg_replace($pattern, '', $name);
        }

        return trim($name);
    }

    /**
     * Check if two locations should be merged.
     *
     * @param string $a First location name
     * @param string $b Second location name
     * @return bool True if locations should be merged
     */
    protected function shouldMergeLocations(string $a, string $b): bool
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        // A contains B as prefix: "Elias's Home Living Room" contains "Elias's Home"
        if (strpos($a, $b) === 0 && strlen($a) > strlen($b)) {
            return true;
        }

        // B contains A as prefix: "Elias's Home" is prefix of "Elias's Home Living Room"
        if (strpos($b, $a) === 0 && strlen($b) > strlen($a)) {
            return true;
        }

        // Check for possessive variations with same base
        $aBase = $this->extractLocationBaseName($a);
        $bBase = $this->extractLocationBaseName($b);

        // Same base name but different full names = should merge
        if ($aBase === $bBase && ($aBase !== $a || $bBase !== $b)) {
            return true;
        }

        return false;
    }

    /**
     * Merge two locations that are related (same building/area).
     *
     * @param array $main The main location entry
     * @param array $sub The sub-location to merge
     * @return array Merged location data
     */
    protected function mergeLocations(array $main, array $sub): array
    {
        // Use shorter/simpler name (the parent location)
        if (strlen($sub['name'] ?? '') < strlen($main['name'] ?? '')) {
            $main['name'] = $sub['name'];
        }

        // Combine scenes
        $allScenes = array_unique(array_merge(
            $main['scenes'] ?? [],
            $sub['scenes'] ?? []
        ));
        sort($allScenes);
        $main['scenes'] = $allScenes;

        // Merge descriptions (keep the more detailed one, note variations)
        $mainDesc = $main['description'] ?? '';
        $subDesc = $sub['description'] ?? '';
        if (strlen($subDesc) > strlen($mainDesc)) {
            $main['description'] = $subDesc;
        }

        // Add note about sub-areas if not already mentioned
        $subName = $sub['name'] ?? '';
        if (!empty($subName) && $subName !== ($main['name'] ?? '') &&
            stripos($main['description'] ?? '', $subName) === false) {
            $main['description'] = ($main['description'] ?? '') . " (Includes areas: {$subName})";
        }

        // Track time of day variations
        if (!isset($main['timeVariations'])) {
            $main['timeVariations'] = [];
        }
        if (!empty($sub['timeOfDay']) && !in_array($sub['timeOfDay'], $main['timeVariations'])) {
            $main['timeVariations'][] = $sub['timeOfDay'];
        }

        // Merge state changes
        $main['stateChanges'] = array_merge(
            $main['stateChanges'] ?? [],
            $sub['stateChanges'] ?? []
        );

        return $main;
    }
}
