<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\WizardProject;
use Illuminate\Support\Facades\Log;

/**
 * Visual Consistency Service - Phase 4: Visual Consistency Engine
 *
 * This service ensures visual consistency across all generated images by:
 * - Injecting Story Bible character descriptions into visual prompts
 * - Injecting Story Bible location descriptions into visual prompts
 * - Applying consistent visual style (colors, lighting, atmosphere)
 * - Tracking which characters/locations appear in each scene
 * - Providing consistency scores and warnings
 */
class VisualConsistencyService
{
    /**
     * Consistency injection modes.
     */
    public const INJECTION_MODES = [
        'auto' => [
            'name' => 'Auto-Detect',
            'description' => 'Automatically detect and inject character/location descriptions',
        ],
        'strict' => [
            'name' => 'Strict Bible',
            'description' => 'Only use exact Story Bible descriptions, no inference',
        ],
        'enhanced' => [
            'name' => 'Enhanced',
            'description' => 'Bible descriptions + AI enhancement for better visuals',
        ],
        'disabled' => [
            'name' => 'Disabled',
            'description' => 'No consistency injection (use raw visual descriptions)',
        ],
    ];

    /**
     * Build a consistency-enhanced visual prompt for a scene.
     * Injects Story Bible character/location descriptions for visual consistency.
     */
    public function buildConsistentPrompt(
        WizardProject $project,
        array $scene,
        array $options = []
    ): array {
        $mode = $options['consistencyMode'] ?? 'auto';

        if ($mode === 'disabled' || !$project->hasStoryBible()) {
            return [
                'prompt' => $scene['visualDescription'] ?? $scene['visualPrompt'] ?? '',
                'consistencyApplied' => false,
                'detectedCharacters' => [],
                'detectedLocations' => [],
                'styleApplied' => false,
            ];
        }

        $bible = $project->story_bible;
        $basePrompt = $scene['visualDescription'] ?? $scene['visualPrompt'] ?? '';
        $narration = $scene['narration'] ?? '';

        // Detect characters mentioned in the scene
        $detectedCharacters = $this->detectCharactersInScene($bible, $narration, $basePrompt);

        // Detect locations mentioned in the scene
        $detectedLocations = $this->detectLocationsInScene($bible, $narration, $basePrompt);

        // Build the enhanced prompt
        $enhancedPrompt = $this->constructEnhancedPrompt(
            $basePrompt,
            $bible,
            $detectedCharacters,
            $detectedLocations,
            $mode
        );

        Log::info('VisualConsistency: Built consistent prompt', [
            'sceneId' => $scene['id'] ?? 'unknown',
            'mode' => $mode,
            'charactersDetected' => count($detectedCharacters),
            'locationsDetected' => count($detectedLocations),
            'promptLength' => strlen($enhancedPrompt),
        ]);

        return [
            'prompt' => $enhancedPrompt,
            'consistencyApplied' => true,
            'detectedCharacters' => $detectedCharacters,
            'detectedLocations' => $detectedLocations,
            'styleApplied' => !empty($bible['visualStyle']),
            'originalPrompt' => $basePrompt,
        ];
    }

    /**
     * Detect which characters from the Story Bible appear in a scene.
     */
    protected function detectCharactersInScene(array $bible, string $narration, string $visual): array
    {
        $detected = [];
        $searchText = strtolower($narration . ' ' . $visual);

        foreach ($bible['characters'] ?? [] as $character) {
            $name = $character['name'] ?? '';
            if (empty($name)) continue;

            // Check for exact name match or common variations
            $nameLower = strtolower($name);
            $firstName = explode(' ', $name)[0] ?? '';
            $firstNameLower = strtolower($firstName);

            if (
                strpos($searchText, $nameLower) !== false ||
                strpos($searchText, $firstNameLower) !== false
            ) {
                $detected[] = $character;
            }
        }

        return $detected;
    }

    /**
     * Detect which locations from the Story Bible appear in a scene.
     */
    protected function detectLocationsInScene(array $bible, string $narration, string $visual): array
    {
        $detected = [];
        $searchText = strtolower($narration . ' ' . $visual);

        foreach ($bible['locations'] ?? [] as $location) {
            $name = $location['name'] ?? '';
            if (empty($name)) continue;

            $nameLower = strtolower($name);

            // Also check for location type keywords
            $type = strtolower($location['type'] ?? '');
            $atmosphere = strtolower($location['atmosphere'] ?? '');

            if (
                strpos($searchText, $nameLower) !== false ||
                ($type && strpos($searchText, $type) !== false)
            ) {
                $detected[] = $location;
            }
        }

        return $detected;
    }

    /**
     * Construct the enhanced visual prompt with Bible constraints.
     */
    protected function constructEnhancedPrompt(
        string $basePrompt,
        array $bible,
        array $characters,
        array $locations,
        string $mode
    ): string {
        $parts = [];

        // 1. Visual Style Prefix (from Bible)
        $stylePrefix = $this->buildStylePrefix($bible);
        if (!empty($stylePrefix)) {
            $parts[] = $stylePrefix;
        }

        // 2. Character Descriptions
        if (!empty($characters)) {
            $charDesc = $this->buildCharacterDescriptions($characters, $mode);
            if (!empty($charDesc)) {
                $parts[] = $charDesc;
            }
        }

        // 3. Location Description
        if (!empty($locations)) {
            $locDesc = $this->buildLocationDescription($locations[0], $mode); // Primary location
            if (!empty($locDesc)) {
                $parts[] = $locDesc;
            }
        }

        // 4. Original Visual Description (enhanced or as-is based on mode)
        if ($mode === 'enhanced') {
            $parts[] = "Scene: " . $basePrompt;
        } else {
            $parts[] = $basePrompt;
        }

        // 5. Style Suffix (technical quality)
        $styleSuffix = $this->buildStyleSuffix($bible);
        if (!empty($styleSuffix)) {
            $parts[] = $styleSuffix;
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Build a visual style prefix from the Story Bible.
     */
    protected function buildStylePrefix(array $bible): string
    {
        $style = $bible['visualStyle'] ?? [];
        $parts = [];

        if (!empty($style['mode'])) {
            $modeDescriptions = [
                'cinematic' => 'Cinematic film quality',
                'documentary' => 'Documentary style, authentic',
                'animated' => 'Stylized animation style',
                'stock' => 'Professional stock photo quality',
            ];
            $parts[] = $modeDescriptions[$style['mode']] ?? ucfirst($style['mode']);
        }

        if (!empty($style['lighting'])) {
            $parts[] = $style['lighting'] . ' lighting';
        }

        if (!empty($style['colorDescription'])) {
            $parts[] = $style['colorDescription'];
        } elseif (!empty($style['colorPalette'])) {
            // Convert hex colors to descriptive terms
            $parts[] = 'Color palette: ' . implode(', ', array_slice($style['colorPalette'], 0, 3));
        }

        return implode(', ', $parts);
    }

    /**
     * Build character descriptions for the visual prompt.
     */
    protected function buildCharacterDescriptions(array $characters, string $mode): string
    {
        $descriptions = [];

        foreach ($characters as $char) {
            $name = $char['name'] ?? 'Character';
            $visual = $char['visualDescription'] ?? '';

            if ($mode === 'strict' && !empty($visual)) {
                // Use exact Bible description
                $descriptions[] = "{$name}: {$visual}";
            } elseif ($mode === 'enhanced' && !empty($visual)) {
                // Enhanced with role context
                $role = $char['role'] ?? '';
                $roleContext = $role ? " ({$role})" : '';
                $descriptions[] = "{$name}{$roleContext}: {$visual}";
            } elseif (!empty($visual)) {
                // Auto mode - concise
                $descriptions[] = "{$name} - {$visual}";
            }
        }

        if (empty($descriptions)) {
            return '';
        }

        return 'Characters: ' . implode('; ', $descriptions);
    }

    /**
     * Build location description for the visual prompt.
     */
    protected function buildLocationDescription(array $location, string $mode): string
    {
        $name = $location['name'] ?? '';
        $visual = $location['visualDescription'] ?? '';
        $atmosphere = $location['atmosphere'] ?? '';
        $timeOfDay = $location['timeOfDay'] ?? '';

        if (empty($visual) && empty($atmosphere)) {
            return '';
        }

        $parts = [];

        if ($mode === 'strict') {
            if (!empty($visual)) $parts[] = $visual;
        } else {
            if (!empty($name)) $parts[] = "Location: {$name}";
            if (!empty($visual)) $parts[] = $visual;
            if (!empty($atmosphere)) $parts[] = "Atmosphere: {$atmosphere}";
            if (!empty($timeOfDay)) $parts[] = ucfirst($timeOfDay);
        }

        return implode(', ', $parts);
    }

    /**
     * Build style suffix for technical quality.
     */
    protected function buildStyleSuffix(array $bible): string
    {
        $style = $bible['visualStyle'] ?? [];
        $parts = [];

        if (!empty($style['cameraLanguage'])) {
            $parts[] = $style['cameraLanguage'];
        }

        // Add quality keywords
        $parts[] = 'high quality';
        $parts[] = 'detailed';

        return implode(', ', $parts);
    }

    /**
     * Analyze a scene for visual consistency with the Story Bible.
     * Returns a consistency score and recommendations.
     */
    public function analyzeSceneConsistency(WizardProject $project, array $scene): array
    {
        if (!$project->hasStoryBible()) {
            return [
                'score' => 100,
                'status' => 'no_bible',
                'warnings' => [],
                'suggestions' => [],
            ];
        }

        $bible = $project->story_bible;
        $visual = $scene['visualDescription'] ?? '';
        $narration = $scene['narration'] ?? '';

        $warnings = [];
        $suggestions = [];
        $score = 100;

        // Check for character consistency
        $detectedChars = $this->detectCharactersInScene($bible, $narration, $visual);
        $bibleChars = $bible['characters'] ?? [];

        foreach ($detectedChars as $char) {
            $charVisual = $char['visualDescription'] ?? '';
            $charName = $char['name'] ?? '';

            // Check if character visual description is used in scene
            if (!empty($charVisual) && strpos(strtolower($visual), strtolower($charName)) !== false) {
                // Character is mentioned but check if visual matches
                $keyFeatures = $this->extractKeyFeatures($charVisual);
                $missingFeatures = [];

                foreach ($keyFeatures as $feature) {
                    if (strpos(strtolower($visual), strtolower($feature)) === false) {
                        $missingFeatures[] = $feature;
                    }
                }

                if (!empty($missingFeatures)) {
                    $warnings[] = "Character '{$charName}' appears but missing visual details: " . implode(', ', array_slice($missingFeatures, 0, 3));
                    $score -= 10;
                }
            }
        }

        // Check for location consistency
        $detectedLocs = $this->detectLocationsInScene($bible, $narration, $visual);

        foreach ($detectedLocs as $loc) {
            $locVisual = $loc['visualDescription'] ?? '';
            $locName = $loc['name'] ?? '';

            if (!empty($locVisual)) {
                $keyFeatures = $this->extractKeyFeatures($locVisual);
                $matchCount = 0;

                foreach ($keyFeatures as $feature) {
                    if (strpos(strtolower($visual), strtolower($feature)) !== false) {
                        $matchCount++;
                    }
                }

                if ($matchCount < count($keyFeatures) / 2) {
                    $suggestions[] = "Location '{$locName}' could use more Bible-defined visual elements";
                    $score -= 5;
                }
            }
        }

        // Check visual style consistency
        $style = $bible['visualStyle'] ?? [];
        if (!empty($style['lighting']) && strpos(strtolower($visual), strtolower($style['lighting'])) === false) {
            $suggestions[] = "Consider adding '{$style['lighting']}' lighting as defined in Bible";
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'status' => $score >= 80 ? 'good' : ($score >= 50 ? 'fair' : 'poor'),
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'detectedCharacters' => array_column($detectedChars, 'name'),
            'detectedLocations' => array_column($detectedLocs, 'name'),
        ];
    }

    /**
     * Extract key visual features from a description.
     */
    protected function extractKeyFeatures(string $description): array
    {
        // Common visual feature keywords
        $featurePatterns = [
            '/\b(hair|eyes|skin|wearing|dressed|tall|short|young|old|beard|glasses)\b/i',
            '/\b(wooden|stone|glass|metal|modern|ancient|bright|dark|warm|cool)\b/i',
            '/\b(red|blue|green|yellow|black|white|brown|gray|golden|silver)\b/i',
        ];

        $features = [];

        foreach ($featurePatterns as $pattern) {
            if (preg_match_all($pattern, $description, $matches)) {
                $features = array_merge($features, $matches[0]);
            }
        }

        return array_unique($features);
    }

    /**
     * Generate a batch of consistent visual prompts for all scenes.
     */
    public function generateBatchConsistentPrompts(WizardProject $project, array $scenes, array $options = []): array
    {
        $results = [];
        $mode = $options['consistencyMode'] ?? 'auto';

        foreach ($scenes as $index => $scene) {
            $result = $this->buildConsistentPrompt($project, $scene, ['consistencyMode' => $mode]);
            $result['sceneIndex'] = $index;
            $result['sceneId'] = $scene['id'] ?? "scene-{$index}";

            // Add consistency analysis
            $analysis = $this->analyzeSceneConsistency($project, $scene);
            $result['consistency'] = $analysis;

            $results[] = $result;
        }

        // Calculate overall batch statistics
        $totalScore = array_sum(array_column(array_column($results, 'consistency'), 'score'));
        $avgScore = count($results) > 0 ? round($totalScore / count($results), 1) : 0;

        $allCharacters = [];
        $allLocations = [];
        foreach ($results as $r) {
            $allCharacters = array_merge($allCharacters, $r['detectedCharacters'] ?? []);
            $allLocations = array_merge($allLocations, $r['detectedLocations'] ?? []);
        }

        Log::info('VisualConsistency: Generated batch prompts', [
            'sceneCount' => count($scenes),
            'avgConsistencyScore' => $avgScore,
            'uniqueCharacters' => count(array_unique($allCharacters)),
            'uniqueLocations' => count(array_unique($allLocations)),
        ]);

        return [
            'prompts' => $results,
            'statistics' => [
                'totalScenes' => count($scenes),
                'averageConsistencyScore' => $avgScore,
                'uniqueCharactersUsed' => array_values(array_unique($allCharacters)),
                'uniqueLocationsUsed' => array_values(array_unique($allLocations)),
                'consistencyMode' => $mode,
            ],
        ];
    }

    /**
     * Get the character reference image requirements for a scene.
     * Used for models that support face consistency (like NanoBanana Pro).
     */
    public function getCharacterReferences(WizardProject $project, array $scene): array
    {
        if (!$project->hasStoryBible()) {
            return [];
        }

        $bible = $project->story_bible;
        $narration = $scene['narration'] ?? '';
        $visual = $scene['visualDescription'] ?? '';

        $detectedChars = $this->detectCharactersInScene($bible, $narration, $visual);
        $references = [];

        foreach ($detectedChars as $char) {
            $ref = [
                'name' => $char['name'] ?? 'Unknown',
                'role' => $char['role'] ?? 'character',
                'visualDescription' => $char['visualDescription'] ?? '',
                'referenceImage' => $char['referenceImage'] ?? null,
            ];

            // If character has a reference image, include it
            if (!empty($ref['referenceImage'])) {
                $ref['hasReference'] = true;
            } else {
                $ref['hasReference'] = false;
                $ref['suggestion'] = "Upload a reference image for '{$ref['name']}' to improve face consistency";
            }

            $references[] = $ref;
        }

        return $references;
    }
}
