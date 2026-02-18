<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\WizardProject;
use Illuminate\Support\Facades\Log;

/**
 * Context Window Service - Phase 3: Context Window Maximization
 *
 * This service maximizes the use of AI model context windows (especially Grok 4.1's 2M tokens)
 * to generate more coherent, consistent scripts by providing full story context.
 *
 * Key Features:
 * - Full Story Bible context injection
 * - Scene accumulation for narrative continuity
 * - Context budget management per model tier
 * - Character/location consistency enforcement
 */
class ContextWindowService
{
    /**
     * Context window limits per engine (in approximate tokens).
     * Conservative estimates to leave room for output.
     */
    public const CONTEXT_LIMITS = [
        'grok' => [
            'model' => 'grok-4-fast',
            'inputLimit' => 100000,
            'outputLimit' => 8000,
            'description' => 'Fast generation with moderate context',
        ],
        'gemini' => [
            'model' => 'gemini-2.5-flash',
            'inputLimit' => 900000,  // Gemini has 1M context
            'outputLimit' => 16000,
            'description' => 'Large context, fast generation',
        ],
        'deepseek' => [
            'model' => 'deepseek-chat',
            'inputLimit' => 60000,
            'outputLimit' => 8000,
            'description' => 'Best value for moderate context',
        ],
        'claude' => [
            'model' => 'claude-sonnet-4-20250514',
            'inputLimit' => 180000,  // 200K context
            'outputLimit' => 16000,
            'description' => 'Creative writing with large context',
        ],
        'claude-haiku' => [
            'model' => 'claude-3.5-haiku-20241022',
            'inputLimit' => 180000,  // 200K context
            'outputLimit' => 8000,
            'description' => 'Fast Claude with large context',
        ],
        'openai' => [
            'model' => 'gpt-4o',
            'inputLimit' => 120000,
            'outputLimit' => 16000,
            'description' => 'Flagship model, balanced context',
        ],
        'unlimited' => [
            'model' => 'grok-4',
            'inputLimit' => 1500000,
            'outputLimit' => 32000,
            'description' => 'Maximum context for complex narratives',
        ],
        // Legacy tier names for backward compatibility
        'economy' => [
            'model' => 'grok-4-fast',
            'inputLimit' => 100000,
            'outputLimit' => 8000,
            'description' => 'Legacy: maps to grok',
        ],
        'standard' => [
            'model' => 'gpt-4o',
            'inputLimit' => 120000,
            'outputLimit' => 16000,
            'description' => 'Legacy: maps to openai',
        ],
        'premium' => [
            'model' => 'gpt-4o',
            'inputLimit' => 120000,
            'outputLimit' => 16000,
            'description' => 'Legacy: maps to openai',
        ],
    ];

    /**
     * Estimate token count from text (rough approximation: 1 token ≈ 4 characters).
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Build a full-context prompt with Story Bible and accumulated scenes.
     * This maximizes context usage for better narrative continuity.
     */
    public function buildFullContextPrompt(
        WizardProject $project,
        array $existingScenes = [],
        array $options = []
    ): array {
        $tier = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';
        $limits = self::CONTEXT_LIMITS[$tier] ?? self::CONTEXT_LIMITS['economy'];

        $contextParts = [];
        $totalTokens = 0;

        // 1. Story Bible (highest priority - always include full)
        $storyBibleContext = $this->buildStoryBibleContext($project);
        $bibleTokens = $this->estimateTokens($storyBibleContext);

        if ($bibleTokens < $limits['inputLimit'] * 0.3) { // Max 30% for Bible
            $contextParts['storyBible'] = $storyBibleContext;
            $totalTokens += $bibleTokens;
        } else {
            // Compress Bible if too large
            $contextParts['storyBible'] = $this->compressStoryBible($project);
            $totalTokens += $this->estimateTokens($contextParts['storyBible']);
        }

        // 2. Existing scenes context (for continuity)
        if (!empty($existingScenes)) {
            $scenesContext = $this->buildScenesContext($existingScenes, $limits['inputLimit'] - $totalTokens);
            $scenesTokens = $this->estimateTokens($scenesContext);

            if ($totalTokens + $scenesTokens < $limits['inputLimit'] * 0.8) {
                $contextParts['existingScenes'] = $scenesContext;
                $totalTokens += $scenesTokens;
            }
        }

        // 3. Production context
        $productionContext = $this->buildProductionContext($project);
        $contextParts['production'] = $productionContext;
        $totalTokens += $this->estimateTokens($productionContext);

        Log::info('ContextWindow: Built full context', [
            'tier' => $tier,
            'totalTokens' => $totalTokens,
            'inputLimit' => $limits['inputLimit'],
            'utilization' => round(($totalTokens / $limits['inputLimit']) * 100, 1) . '%',
            'parts' => array_keys($contextParts),
        ]);

        return [
            'context' => $contextParts,
            'totalTokens' => $totalTokens,
            'remainingTokens' => $limits['inputLimit'] - $totalTokens,
            'tier' => $tier,
            'model' => $limits['model'],
        ];
    }

    /**
     * Build comprehensive Story Bible context for injection into prompts.
     */
    public function buildStoryBibleContext(WizardProject $project): string
    {
        if (!$project->hasStoryBible()) {
            return '';
        }

        $bible = $project->story_bible;
        $context = "=== STORY BIBLE (AUTHORITATIVE REFERENCE) ===\n\n";

        // Core Identity
        if (!empty($bible['title'])) {
            $context .= "TITLE: {$bible['title']}\n";
        }
        if (!empty($bible['logline'])) {
            $context .= "LOGLINE: {$bible['logline']}\n";
        }
        if (!empty($bible['theme'])) {
            $context .= "THEME: {$bible['theme']}\n";
        }
        if (!empty($bible['tone'])) {
            $context .= "TONE: {$bible['tone']}\n";
        }
        if (!empty($bible['genre'])) {
            $context .= "GENRE: {$bible['genre']}\n";
        }
        $context .= "\n";

        // Narrative Structure (Acts)
        if (!empty($bible['acts'])) {
            $context .= "=== NARRATIVE STRUCTURE ===\n";
            foreach ($bible['acts'] as $act) {
                $context .= "ACT {$act['number']}: {$act['title']}\n";
                $context .= "  Purpose: {$act['purpose']}\n";
                if (!empty($act['beats'])) {
                    $context .= "  Key Beats: " . implode(', ', $act['beats']) . "\n";
                }
                if (!empty($act['emotionalArc'])) {
                    $context .= "  Emotional Arc: {$act['emotionalArc']}\n";
                }
                if (!empty($act['duration'])) {
                    $context .= "  Duration: {$act['duration']}s\n";
                }
                $context .= "\n";
            }
        }

        // Characters (Full Detail)
        if (!empty($bible['characters'])) {
            $context .= "=== CHARACTERS ===\n";
            foreach ($bible['characters'] as $char) {
                $context .= "CHARACTER: {$char['name']}\n";
                $context .= "  Role: " . ucfirst($char['role'] ?? 'supporting') . "\n";
                if (!empty($char['visualDescription'])) {
                    $context .= "  Visual: {$char['visualDescription']}\n";
                }
                if (!empty($char['traits'])) {
                    $context .= "  Traits: " . implode(', ', $char['traits']) . "\n";
                }
                if (!empty($char['arc'])) {
                    $context .= "  Arc: {$char['arc']}\n";
                }
                if (!empty($char['voiceStyle'])) {
                    $context .= "  Voice: {$char['voiceStyle']}\n";
                }
                $context .= "\n";
            }
        }

        // Locations (Full Detail)
        if (!empty($bible['locations'])) {
            $context .= "=== LOCATIONS ===\n";
            foreach ($bible['locations'] as $loc) {
                $context .= "LOCATION: {$loc['name']}\n";
                $context .= "  Type: " . ucfirst($loc['type'] ?? 'interior') . "\n";
                if (!empty($loc['visualDescription'])) {
                    $context .= "  Visual: {$loc['visualDescription']}\n";
                }
                if (!empty($loc['timeOfDay'])) {
                    $context .= "  Time of Day: {$loc['timeOfDay']}\n";
                }
                if (!empty($loc['atmosphere'])) {
                    $context .= "  Atmosphere: {$loc['atmosphere']}\n";
                }
                if (!empty($loc['significance'])) {
                    $context .= "  Significance: {$loc['significance']}\n";
                }
                $context .= "\n";
            }
        }

        // Visual Style
        if (!empty($bible['visualStyle'])) {
            $style = $bible['visualStyle'];
            $context .= "=== VISUAL STYLE GUIDE ===\n";
            if (!empty($style['mode'])) {
                $context .= "Mode: {$style['mode']}\n";
            }
            if (!empty($style['colorPalette'])) {
                $context .= "Color Palette: " . implode(', ', $style['colorPalette']) . "\n";
            }
            if (!empty($style['colorDescription'])) {
                $context .= "Color Mood: {$style['colorDescription']}\n";
            }
            if (!empty($style['lighting'])) {
                $context .= "Lighting: {$style['lighting']}\n";
            }
            if (!empty($style['cameraLanguage'])) {
                $context .= "Camera Language: {$style['cameraLanguage']}\n";
            }
            if (!empty($style['motifs'])) {
                $context .= "Visual Motifs: " . implode(', ', $style['motifs']) . "\n";
            }
            $context .= "\n";
        }

        // Pacing
        if (!empty($bible['pacing'])) {
            $pacing = $bible['pacing'];
            $context .= "=== PACING & RHYTHM ===\n";
            if (!empty($pacing['overallPace'])) {
                $context .= "Overall Pace: {$pacing['overallPace']}\n";
            }
            if (!empty($pacing['transitionStyle'])) {
                $context .= "Transitions: {$pacing['transitionStyle']}\n";
            }
            if (!empty($pacing['musicMood'])) {
                $context .= "Music Mood: {$pacing['musicMood']}\n";
            }
            $context .= "\n";
        }

        $context .= "=== END STORY BIBLE ===\n";
        $context .= "CRITICAL: All generated content MUST adhere to this Story Bible.\n";
        $context .= "Characters must match their visual descriptions exactly.\n";
        $context .= "Locations must use the defined atmospheres and lighting.\n";
        $context .= "Narrative must follow the act structure and emotional arcs.\n\n";

        return $context;
    }

    /**
     * Compress Story Bible for smaller context windows.
     */
    protected function compressStoryBible(WizardProject $project): string
    {
        if (!$project->hasStoryBible()) {
            return '';
        }

        $bible = $project->story_bible;
        $context = "=== STORY BIBLE (COMPRESSED) ===\n";

        // Core only
        $context .= "Title: " . ($bible['title'] ?? 'Untitled') . "\n";
        $context .= "Logline: " . ($bible['logline'] ?? '') . "\n";
        $context .= "Tone: " . ($bible['tone'] ?? 'engaging') . "\n";

        // Character names only
        if (!empty($bible['characters'])) {
            $names = array_column($bible['characters'], 'name');
            $context .= "Characters: " . implode(', ', $names) . "\n";
        }

        // Location names only
        if (!empty($bible['locations'])) {
            $names = array_column($bible['locations'], 'name');
            $context .= "Locations: " . implode(', ', $names) . "\n";
        }

        $context .= "=== END ===\n\n";

        return $context;
    }

    /**
     * Build context from existing scenes for continuity.
     */
    public function buildScenesContext(array $scenes, int $tokenBudget): string
    {
        if (empty($scenes)) {
            return '';
        }

        $context = "=== EXISTING SCENES (FOR CONTINUITY) ===\n";
        $context .= "The following scenes have already been generated. New scenes must flow naturally from these.\n\n";

        $accumulatedTokens = $this->estimateTokens($context);
        $includedScenes = [];

        // Prioritize recent scenes (they matter most for continuity)
        $reversedScenes = array_reverse($scenes, true);

        foreach ($reversedScenes as $index => $scene) {
            $sceneText = $this->formatSceneForContext($scene, $index);
            $sceneTokens = $this->estimateTokens($sceneText);

            if ($accumulatedTokens + $sceneTokens < $tokenBudget * 0.5) {
                array_unshift($includedScenes, ['index' => $index, 'text' => $sceneText]);
                $accumulatedTokens += $sceneTokens;
            } else {
                // Budget exceeded, stop adding scenes
                break;
            }
        }

        // Add scenes in chronological order
        foreach ($includedScenes as $item) {
            $context .= $item['text'] . "\n";
        }

        if (count($includedScenes) < count($scenes)) {
            $context .= "[" . (count($scenes) - count($includedScenes)) . " earlier scenes omitted for brevity]\n";
        }

        $context .= "=== END EXISTING SCENES ===\n\n";

        return $context;
    }

    /**
     * Format a single scene for context inclusion.
     */
    protected function formatSceneForContext(array $scene, int $index): string
    {
        $text = "SCENE " . ($index + 1) . ": " . ($scene['title'] ?? 'Untitled') . "\n";

        if (!empty($scene['narration'])) {
            $text .= "  Narration: " . substr($scene['narration'], 0, 200);
            if (strlen($scene['narration']) > 200) {
                $text .= "...";
            }
            $text .= "\n";
        }

        if (!empty($scene['visualDescription'])) {
            $text .= "  Visual: " . substr($scene['visualDescription'], 0, 150);
            if (strlen($scene['visualDescription']) > 150) {
                $text .= "...";
            }
            $text .= "\n";
        }

        return $text;
    }

    /**
     * Build production context (platform, duration, etc.).
     */
    public function buildProductionContext(WizardProject $project): string
    {
        $context = "=== PRODUCTION CONTEXT ===\n";
        $context .= "Platform: " . ($project->platform ?? 'youtube') . "\n";
        $context .= "Target Duration: " . ($project->target_duration ?? 60) . " seconds\n";
        $context .= "Aspect Ratio: " . ($project->aspect_ratio ?? '16:9') . "\n";

        $productionType = $project->getProductionTypeConfig();
        if (!empty($productionType['name'])) {
            $context .= "Production Type: {$productionType['name']}\n";
        }

        $context .= "=== END PRODUCTION CONTEXT ===\n\n";

        return $context;
    }

    /**
     * Build a context-aware prompt for scene regeneration.
     * Includes full Story Bible + surrounding scenes for perfect continuity.
     */
    public function buildSceneRegenerationContext(
        WizardProject $project,
        array $allScenes,
        int $targetSceneIndex,
        array $options = []
    ): string {
        $tier = $options['aiEngine'] ?? $options['aiModelTier'] ?? 'grok';
        $limits = self::CONTEXT_LIMITS[$tier] ?? self::CONTEXT_LIMITS['economy'];

        $context = "";

        // 1. Story Bible (always include)
        $context .= $this->buildStoryBibleContext($project);

        // 2. Previous scenes (for what came before)
        if ($targetSceneIndex > 0) {
            $previousScenes = array_slice($allScenes, 0, $targetSceneIndex);
            $context .= "=== PREVIOUS SCENES ===\n";
            $context .= "These scenes come BEFORE the scene being regenerated:\n\n";
            foreach ($previousScenes as $idx => $scene) {
                $context .= $this->formatSceneForContext($scene, $idx) . "\n";
            }
            $context .= "\n";
        }

        // 3. Current scene (what we're replacing)
        if (isset($allScenes[$targetSceneIndex])) {
            $context .= "=== SCENE TO REGENERATE ===\n";
            $context .= "Scene " . ($targetSceneIndex + 1) . " needs to be regenerated.\n";
            $currentScene = $allScenes[$targetSceneIndex];
            $context .= "Current Title: " . ($currentScene['title'] ?? 'Untitled') . "\n";
            $context .= "Duration: " . ($currentScene['duration'] ?? 10) . " seconds\n\n";
        }

        // 4. Following scenes (for what comes after)
        if ($targetSceneIndex < count($allScenes) - 1) {
            $nextScenes = array_slice($allScenes, $targetSceneIndex + 1);
            $context .= "=== FOLLOWING SCENES ===\n";
            $context .= "These scenes come AFTER the scene being regenerated:\n\n";
            foreach ($nextScenes as $idx => $scene) {
                $actualIdx = $targetSceneIndex + 1 + $idx;
                $context .= $this->formatSceneForContext($scene, $actualIdx) . "\n";
            }
            $context .= "\n";
        }

        $context .= "=== REGENERATION INSTRUCTIONS ===\n";
        $context .= "Generate a new version of Scene " . ($targetSceneIndex + 1) . " that:\n";
        $context .= "1. Follows naturally from the previous scenes\n";
        $context .= "2. Leads smoothly into the following scenes\n";
        $context .= "3. Adheres to all Story Bible constraints\n";
        $context .= "4. Maintains consistent character appearances and location descriptions\n";
        $context .= "5. Matches the overall tone and pacing established\n\n";

        return $context;
    }

    /**
     * Validate generated content against Story Bible.
     * Returns array of consistency warnings/errors.
     */
    public function validateAgainstBible(WizardProject $project, array $generatedContent): array
    {
        if (!$project->hasStoryBible()) {
            return ['valid' => true, 'warnings' => [], 'errors' => []];
        }

        $bible = $project->story_bible;
        $warnings = [];
        $errors = [];

        // Check character references
        $bibleCharacterNames = array_map(
            fn($c) => strtolower($c['name'] ?? ''),
            $bible['characters'] ?? []
        );

        // Check location references
        $bibleLocationNames = array_map(
            fn($l) => strtolower($l['name'] ?? ''),
            $bible['locations'] ?? []
        );

        // Analyze generated scenes
        foreach ($generatedContent['scenes'] ?? [] as $index => $scene) {
            $narration = strtolower($scene['narration'] ?? '');
            $visual = strtolower($scene['visualDescription'] ?? '');

            // Look for potential unnamed characters
            $characterPatterns = ['he ', 'she ', 'they ', 'the man', 'the woman', 'the person'];
            foreach ($characterPatterns as $pattern) {
                if (strpos($narration, $pattern) !== false && empty($bibleCharacterNames)) {
                    $warnings[] = "Scene " . ($index + 1) . ": Contains character reference but no characters defined in Bible";
                }
            }

            // Check if mentioned locations exist in Bible
            foreach ($bibleLocationNames as $locationName) {
                if (!empty($locationName) && strpos($visual, $locationName) === false) {
                    // Location not mentioned - not necessarily an error
                }
            }
        }

        return [
            'valid' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Get the optimal AI model tier based on content complexity.
     */
    public function recommendTier(WizardProject $project): string
    {
        $bible = $project->story_bible ?? [];
        $duration = $project->target_duration ?? 60;

        // Calculate complexity score
        $complexity = 0;

        // Duration complexity
        if ($duration > 600) $complexity += 3; // 10+ minutes
        elseif ($duration > 300) $complexity += 2; // 5+ minutes
        elseif ($duration > 120) $complexity += 1; // 2+ minutes

        // Character complexity
        $characterCount = count($bible['characters'] ?? []);
        if ($characterCount > 5) $complexity += 2;
        elseif ($characterCount > 2) $complexity += 1;

        // Location complexity
        $locationCount = count($bible['locations'] ?? []);
        if ($locationCount > 5) $complexity += 2;
        elseif ($locationCount > 2) $complexity += 1;

        // Act complexity
        $actCount = count($bible['acts'] ?? []);
        if ($actCount > 3) $complexity += 1;

        // Recommend tier based on complexity
        if ($complexity >= 6) return 'unlimited'; // Use Grok 4.1's full context
        if ($complexity >= 4) return 'premium';
        if ($complexity >= 2) return 'standard';
        return 'economy';
    }

    /**
     * Get context utilization stats for debugging/display.
     */
    public function getContextStats(WizardProject $project, string $tier = 'economy'): array
    {
        $limits = self::CONTEXT_LIMITS[$tier] ?? self::CONTEXT_LIMITS['economy'];

        $bibleContext = $this->buildStoryBibleContext($project);
        $bibleTokens = $this->estimateTokens($bibleContext);

        $productionContext = $this->buildProductionContext($project);
        $productionTokens = $this->estimateTokens($productionContext);

        $sceneCount = count($project->script['scenes'] ?? []);
        $estimatedSceneTokens = $sceneCount * 150; // ~150 tokens per scene summary

        $totalUsed = $bibleTokens + $productionTokens + $estimatedSceneTokens;

        return [
            'tier' => $tier,
            'model' => $limits['model'],
            'inputLimit' => $limits['inputLimit'],
            'outputLimit' => $limits['outputLimit'],
            'used' => [
                'storyBible' => $bibleTokens,
                'production' => $productionTokens,
                'scenes' => $estimatedSceneTokens,
                'total' => $totalUsed,
            ],
            'remaining' => $limits['inputLimit'] - $totalUsed,
            'utilization' => round(($totalUsed / $limits['inputLimit']) * 100, 2),
            'recommendation' => $this->recommendTier($project),
        ];
    }
}
