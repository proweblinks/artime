<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\CharacterPsychologyService;

/**
 * Character Look System Service - Phase 4 of Hollywood Upgrade Plan
 *
 * Provides:
 * - 4.1: Auto-extract character details from description using AI
 * - 4.2: Character DNA Template System for common archetypes
 * - 4.3: Wardrobe Continuity Tracking per scene
 */
class CharacterLookService
{
    protected GeminiService $geminiService;

    /**
     * CharacterPsychologyService for full emotion mapping when presets aren't enough (Phase 23)
     */
    protected ?CharacterPsychologyService $psychologyService = null;

    /**
     * Character DNA Templates for common archetypes (Phase 4.2)
     */
    const CHARACTER_DNA_TEMPLATES = [
        'action_hero' => [
            'name' => 'Action Hero',
            'description' => 'Athletic action protagonist with practical tactical gear',
            'hair' => [
                'style' => 'short military cut',
                'color' => 'dark brown',
                'length' => 'short',
                'texture' => 'thick and neat',
            ],
            'wardrobe' => [
                'outfit' => 'fitted black tactical jacket over dark gray compression shirt',
                'colors' => 'black, charcoal gray, military green',
                'style' => 'tactical-athletic',
                'footwear' => 'black combat boots',
            ],
            'makeup' => [
                'style' => 'none/natural',
                'details' => 'clean-shaven, rugged complexion',
            ],
            'accessories' => ['tactical watch', 'dog tags', 'utility belt'],
            'physical' => [
                'build' => 'athletic muscular',
                'age_range' => '30-45',
                'distinctive_features' => 'strong jawline, intense gaze',
            ],
        ],
        'tech_professional' => [
            'name' => 'Tech Professional',
            'description' => 'Modern tech industry professional with smart casual attire',
            'hair' => [
                'style' => 'neat side-parted',
                'color' => 'varied',
                'length' => 'medium',
                'texture' => 'well-groomed',
            ],
            'wardrobe' => [
                'outfit' => 'slim-fit button-down shirt, chinos or dark jeans',
                'colors' => 'navy, white, gray, earth tones',
                'style' => 'smart casual',
                'footwear' => 'clean white sneakers or leather loafers',
            ],
            'makeup' => [
                'style' => 'minimal',
                'details' => 'natural, well-groomed appearance',
            ],
            'accessories' => ['smart watch', 'wireless earbuds', 'laptop bag'],
            'physical' => [
                'build' => 'average to slim',
                'age_range' => '25-40',
                'distinctive_features' => 'alert expression, focused demeanor',
            ],
        ],
        'corporate_executive' => [
            'name' => 'Corporate Executive',
            'description' => 'Polished business executive in formal professional attire',
            'hair' => [
                'style' => 'professionally styled',
                'color' => 'varied',
                'length' => 'medium to short',
                'texture' => 'sleek and controlled',
            ],
            'wardrobe' => [
                'outfit' => 'tailored business suit with crisp dress shirt',
                'colors' => 'charcoal, navy, black with white shirt',
                'style' => 'formal corporate',
                'footwear' => 'polished leather oxford shoes',
            ],
            'makeup' => [
                'style' => 'professional subtle',
                'details' => 'flawless complexion, subtle enhancements',
            ],
            'accessories' => ['luxury watch', 'cufflinks', 'leather briefcase'],
            'physical' => [
                'build' => 'average, well-maintained',
                'age_range' => '35-55',
                'distinctive_features' => 'commanding presence, confident posture',
            ],
        ],
        'mysterious_figure' => [
            'name' => 'Mysterious Figure',
            'description' => 'Enigmatic character with concealing dark attire',
            'hair' => [
                'style' => 'often hidden or shadowed',
                'color' => 'dark',
                'length' => 'varied',
                'texture' => 'unknown or partially visible',
            ],
            'wardrobe' => [
                'outfit' => 'long dark coat or hooded jacket over dark layers',
                'colors' => 'black, deep navy, charcoal',
                'style' => 'concealing, layered',
                'footwear' => 'dark boots or dress shoes',
            ],
            'makeup' => [
                'style' => 'dramatic shadows',
                'details' => 'face often partially obscured, sharp features visible',
            ],
            'accessories' => ['hood or hat', 'dark gloves', 'mysterious pendant'],
            'physical' => [
                'build' => 'lean and agile',
                'age_range' => 'indeterminate',
                'distinctive_features' => 'piercing eyes, shadowed face',
            ],
        ],
        'narrator' => [
            'name' => 'Narrator',
            'description' => 'Professional neutral presenter suitable for voiceover',
            'hair' => [
                'style' => 'neat and professional',
                'color' => 'natural',
                'length' => 'medium',
                'texture' => 'well-maintained',
            ],
            'wardrobe' => [
                'outfit' => 'neutral professional attire, solid colors',
                'colors' => 'navy, gray, white, burgundy',
                'style' => 'professional neutral',
                'footwear' => 'classic dress shoes',
            ],
            'makeup' => [
                'style' => 'broadcast-ready natural',
                'details' => 'even skin tone, camera-friendly',
            ],
            'accessories' => ['subtle jewelry if any', 'no distracting elements'],
            'physical' => [
                'build' => 'average',
                'age_range' => '30-50',
                'distinctive_features' => 'trustworthy appearance, warm expression',
            ],
        ],
        'young_professional' => [
            'name' => 'Young Professional',
            'description' => 'Modern millennial/gen-z professional',
            'hair' => [
                'style' => 'trendy modern cut',
                'color' => 'varied',
                'length' => 'medium',
                'texture' => 'styled but approachable',
            ],
            'wardrobe' => [
                'outfit' => 'blazer over casual top, fitted pants',
                'colors' => 'mix of neutrals with accent colors',
                'style' => 'business casual trendy',
                'footwear' => 'minimalist sneakers or chelsea boots',
            ],
            'makeup' => [
                'style' => 'fresh natural',
                'details' => 'dewy skin, minimal enhancement',
            ],
            'accessories' => ['smart watch', 'minimal jewelry', 'stylish tote bag'],
            'physical' => [
                'build' => 'fit and healthy',
                'age_range' => '22-32',
                'distinctive_features' => 'energetic demeanor, approachable smile',
            ],
        ],
        'scientist_researcher' => [
            'name' => 'Scientist/Researcher',
            'description' => 'Academic or research professional',
            'hair' => [
                'style' => 'practical, sometimes disheveled',
                'color' => 'varied',
                'length' => 'medium',
                'texture' => 'natural',
            ],
            'wardrobe' => [
                'outfit' => 'lab coat over casual academic attire or smart casual',
                'colors' => 'white lab coat, earth tones underneath',
                'style' => 'academic practical',
                'footwear' => 'comfortable practical shoes',
            ],
            'makeup' => [
                'style' => 'minimal to none',
                'details' => 'natural, focused on work',
            ],
            'accessories' => ['glasses', 'ID badge', 'pen in pocket'],
            'physical' => [
                'build' => 'varied',
                'age_range' => '28-60',
                'distinctive_features' => 'thoughtful expression, intelligent eyes',
            ],
        ],
        'creative_artist' => [
            'name' => 'Creative Artist',
            'description' => 'Artistic creative professional',
            'hair' => [
                'style' => 'expressive, unconventional',
                'color' => 'may include creative colors',
                'length' => 'varied',
                'texture' => 'textured, personality-driven',
            ],
            'wardrobe' => [
                'outfit' => 'eclectic mix of vintage and contemporary',
                'colors' => 'bold colors, artistic patterns',
                'style' => 'bohemian creative',
                'footwear' => 'unique statement shoes or boots',
            ],
            'makeup' => [
                'style' => 'artistic expressive',
                'details' => 'may include bold colors or artistic elements',
            ],
            'accessories' => ['unique jewelry', 'art supplies', 'vintage accessories'],
            'physical' => [
                'build' => 'varied',
                'age_range' => '20-50',
                'distinctive_features' => 'expressive face, creative aura',
            ],
        ],
    ];

    /**
     * Expression presets for common emotional states (Phase 23)
     *
     * These are SIMPLER presets for quick selection when full CharacterPsychologyService
     * granularity isn't needed. For complex emotions, subtext layers, or Bible trait
     * integration, use getExpressionFromPsychology() which bridges to the full service.
     *
     * These use physical descriptions, NOT FACS AU codes.
     * Based on research: image models respond to physical manifestations.
     */
    const EXPRESSION_PRESETS = [
        'neutral' => [
            'description' => 'relaxed neutral expression, natural resting face',
            'face' => 'relaxed jaw and brow, natural lip position',
            'eyes' => 'calm direct gaze, natural eyelid position',
        ],
        'subtle_smile' => [
            'description' => 'gentle hint of smile, warmth without full grin',
            'face' => 'slight upturn at mouth corners, softened cheeks',
            'eyes' => 'slight crinkle at corners, brightened gaze',
        ],
        'concerned' => [
            'description' => 'worried concern showing in features',
            'face' => 'slight furrow between brows, lips pressed together',
            'eyes' => 'focused with slight tension, searching gaze',
        ],
        'determined' => [
            'description' => 'resolute determination visible in set of features',
            'face' => 'firm jaw, set mouth, defined brow line',
            'eyes' => 'intense focused gaze, narrowed with purpose',
        ],
        'vulnerable' => [
            'description' => 'open emotional vulnerability in expression',
            'face' => 'softened features, slight tremor in lip',
            'eyes' => 'glistening, wide and unguarded, exposed',
        ],
        'guarded' => [
            'description' => 'closed off, protective expression',
            'face' => 'tightened jaw, flattened expression',
            'eyes' => 'narrowed, assessing, revealing nothing',
        ],
        'surprised' => [
            'description' => 'genuine surprise captured in features',
            'face' => 'raised brows, parted lips, lifted cheeks',
            'eyes' => 'wide open, pupils dilated, alert',
        ],
        'contemplative' => [
            'description' => 'deep thought visible in distant expression',
            'face' => 'slightly furrowed brow, relaxed mouth',
            'eyes' => 'unfocused mid-distance gaze, inner reflection',
        ],
    ];

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Get all available Character DNA templates (Phase 4.2)
     *
     * @return array Template list with names and descriptions
     */
    public function getTemplates(): array
    {
        $templates = [];
        foreach (self::CHARACTER_DNA_TEMPLATES as $key => $template) {
            $templates[$key] = [
                'key' => $key,
                'name' => $template['name'],
                'description' => $template['description'],
            ];
        }
        return $templates;
    }

    /**
     * Get a specific Character DNA template (Phase 4.2)
     *
     * @param string $templateKey The template key
     * @return array|null Full template data or null if not found
     */
    public function getTemplate(string $templateKey): ?array
    {
        return self::CHARACTER_DNA_TEMPLATES[$templateKey] ?? null;
    }

    /**
     * Apply a DNA template to a character (Phase 4.2)
     *
     * @param array $character The character data
     * @param string $templateKey The template to apply
     * @param bool $overwrite Whether to overwrite existing DNA fields
     * @return array Updated character with template DNA applied
     */
    public function applyTemplate(array $character, string $templateKey, bool $overwrite = false): array
    {
        $template = $this->getTemplate($templateKey);
        if (!$template) {
            Log::warning('[CharacterLookService] Template not found', ['templateKey' => $templateKey]);
            return $character;
        }

        // Apply DNA fields from template
        $dnaFields = ['hair', 'wardrobe', 'makeup', 'accessories'];

        foreach ($dnaFields as $field) {
            if (!isset($template[$field])) {
                continue;
            }

            // Skip if character already has data and we're not overwriting
            if (!$overwrite) {
                if ($field === 'accessories') {
                    if (!empty($character[$field])) {
                        continue;
                    }
                } else {
                    // For array fields (hair, wardrobe, makeup), check if any sub-field has data
                    $hasData = false;
                    if (is_array($character[$field] ?? null)) {
                        foreach ($character[$field] as $value) {
                            if (!empty($value)) {
                                $hasData = true;
                                break;
                            }
                        }
                    }
                    if ($hasData) {
                        continue;
                    }
                }
            }

            $character[$field] = $template[$field];
        }

        // Log application
        Log::info('[CharacterLookService] Template applied', [
            'characterName' => $character['name'] ?? 'Unknown',
            'template' => $templateKey,
            'overwrite' => $overwrite,
        ]);

        return $character;
    }

    /**
     * Extract Character DNA from description using AI (Phase 4.1)
     *
     * Parses the character's text description and extracts structured fields:
     * - Hair: color, style, length, texture
     * - Wardrobe: outfit, colors, style, footwear
     * - Makeup: style, details
     * - Accessories: array of items
     * - Physical: age_range, build, distinctive_features
     *
     * @param string $description The character's text description
     * @param array $options Options including existing character data
     * @return array Extracted DNA fields
     */
    public function extractDNAFromDescription(string $description, array $options = []): array
    {
        if (empty(trim($description))) {
            return $this->getEmptyDNA();
        }

        $characterName = $options['characterName'] ?? 'Character';

        $prompt = $this->buildExtractionPrompt($description, $characterName);

        try {
            $result = $this->geminiService->generate($prompt, [
                'maxResult' => 1,
                'max_tokens' => 2000,
            ]);

            if (!empty($result['error'])) {
                Log::error('[CharacterLookService] AI extraction failed', ['error' => $result['error']]);
                return $this->getEmptyDNA();
            }

            $response = $result['data'][0] ?? '';
            if (empty($response)) {
                Log::warning('[CharacterLookService] Empty AI response');
                return $this->getEmptyDNA();
            }

            $dna = $this->parseExtractionResponse($response);

            Log::info('[CharacterLookService] DNA extracted from description', [
                'characterName' => $characterName,
                'hairExtracted' => !empty($dna['hair']['color']),
                'wardrobeExtracted' => !empty($dna['wardrobe']['outfit']),
                'accessoriesCount' => count($dna['accessories']),
            ]);

            return $dna;

        } catch (\Exception $e) {
            Log::error('[CharacterLookService] Extraction exception', ['error' => $e->getMessage()]);
            return $this->getEmptyDNA();
        }
    }

    /**
     * Auto-populate DNA fields for a character (Phase 4.1)
     *
     * Combines extraction with existing data, only filling empty fields.
     *
     * @param array $character The character data
     * @param bool $overwrite Whether to overwrite existing fields
     * @return array Updated character with DNA fields populated
     */
    public function autoPopulateDNA(array $character, bool $overwrite = false): array
    {
        $description = $character['description'] ?? '';
        if (empty(trim($description))) {
            return $character;
        }

        // Extract DNA from description
        $extractedDNA = $this->extractDNAFromDescription($description, [
            'characterName' => $character['name'] ?? 'Character',
        ]);

        // Merge extracted DNA with existing character data
        return $this->mergeDNA($character, $extractedDNA, $overwrite);
    }

    /**
     * Merge extracted DNA with existing character data
     *
     * @param array $character Existing character data
     * @param array $extractedDNA Newly extracted DNA
     * @param bool $overwrite Whether to overwrite existing values
     * @return array Merged character data
     */
    protected function mergeDNA(array $character, array $extractedDNA, bool $overwrite = false): array
    {
        // Hair
        if (!isset($character['hair']) || !is_array($character['hair'])) {
            $character['hair'] = ['style' => '', 'color' => '', 'length' => '', 'texture' => ''];
        }
        foreach (['style', 'color', 'length', 'texture'] as $key) {
            if ($overwrite || empty($character['hair'][$key])) {
                $character['hair'][$key] = $extractedDNA['hair'][$key] ?? '';
            }
        }

        // Wardrobe
        if (!isset($character['wardrobe']) || !is_array($character['wardrobe'])) {
            $character['wardrobe'] = ['outfit' => '', 'colors' => '', 'style' => '', 'footwear' => ''];
        }
        foreach (['outfit', 'colors', 'style', 'footwear'] as $key) {
            if ($overwrite || empty($character['wardrobe'][$key])) {
                $character['wardrobe'][$key] = $extractedDNA['wardrobe'][$key] ?? '';
            }
        }

        // Makeup
        if (!isset($character['makeup']) || !is_array($character['makeup'])) {
            $character['makeup'] = ['style' => '', 'details' => ''];
        }
        foreach (['style', 'details'] as $key) {
            if ($overwrite || empty($character['makeup'][$key])) {
                $character['makeup'][$key] = $extractedDNA['makeup'][$key] ?? '';
            }
        }

        // Accessories
        if ($overwrite || empty($character['accessories'])) {
            $character['accessories'] = $extractedDNA['accessories'] ?? [];
        }

        // Physical (new field)
        if (!isset($character['physical']) || !is_array($character['physical'])) {
            $character['physical'] = ['age_range' => '', 'build' => '', 'distinctive_features' => ''];
        }
        foreach (['age_range', 'build', 'distinctive_features'] as $key) {
            if ($overwrite || empty($character['physical'][$key])) {
                $character['physical'][$key] = $extractedDNA['physical'][$key] ?? '';
            }
        }

        return $character;
    }

    /**
     * Build the AI prompt for DNA extraction
     */
    protected function buildExtractionPrompt(string $description, string $characterName): string
    {
        return <<<PROMPT
Extract detailed visual attributes from this character description for AI image generation consistency.

CHARACTER NAME: {$characterName}
CHARACTER DESCRIPTION:
{$description}

Extract and structure the following attributes. If information is not mentioned, infer reasonable defaults based on context or leave empty.

Return ONLY valid JSON (no markdown, no explanation):
{
  "hair": {
    "style": "specific hairstyle (e.g., 'sleek bob with side part', 'messy curls')",
    "color": "specific color (e.g., 'jet black', 'auburn red', 'platinum blonde')",
    "length": "length descriptor (e.g., 'chin-length', 'shoulder-length', 'cropped')",
    "texture": "texture descriptor (e.g., 'straight glossy', 'curly voluminous', 'wavy')"
  },
  "wardrobe": {
    "outfit": "detailed outfit description (e.g., 'fitted black tactical jacket over gray t-shirt')",
    "colors": "color palette (e.g., 'black, charcoal, silver accents')",
    "style": "style category (e.g., 'tactical-tech', 'corporate', 'casual bohemian')",
    "footwear": "specific footwear (e.g., 'black combat boots', 'white sneakers')"
  },
  "makeup": {
    "style": "makeup style (e.g., 'minimal natural', 'glamorous', 'dramatic', 'none')",
    "details": "specific details (e.g., 'subtle smoky eye, nude lip', 'clean-shaven')"
  },
  "accessories": ["item1", "item2"],
  "physical": {
    "age_range": "age range (e.g., 'early 30s', 'mid-40s')",
    "build": "body type (e.g., 'athletic', 'slim', 'stocky')",
    "distinctive_features": "notable features (e.g., 'strong jawline, intense blue eyes')"
  }
}

IMPORTANT:
- Extract ONLY what is mentioned or can be reasonably inferred
- Be specific and detailed for AI image generation
- Use empty strings "" for truly unknown attributes
- Accessories should be an array of specific items mentioned
PROMPT;
    }

    /**
     * Parse the AI extraction response
     */
    protected function parseExtractionResponse(string $response): array
    {
        // Clean JSON
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $parsed = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('[CharacterLookService] JSON parse failed', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500),
            ]);
            return $this->getEmptyDNA();
        }

        // Normalize the response
        return [
            'hair' => [
                'style' => $parsed['hair']['style'] ?? '',
                'color' => $parsed['hair']['color'] ?? '',
                'length' => $parsed['hair']['length'] ?? '',
                'texture' => $parsed['hair']['texture'] ?? '',
            ],
            'wardrobe' => [
                'outfit' => $parsed['wardrobe']['outfit'] ?? '',
                'colors' => $parsed['wardrobe']['colors'] ?? '',
                'style' => $parsed['wardrobe']['style'] ?? '',
                'footwear' => $parsed['wardrobe']['footwear'] ?? '',
            ],
            'makeup' => [
                'style' => $parsed['makeup']['style'] ?? '',
                'details' => $parsed['makeup']['details'] ?? '',
            ],
            'accessories' => is_array($parsed['accessories'] ?? null) ? $parsed['accessories'] : [],
            'physical' => [
                'age_range' => $parsed['physical']['age_range'] ?? '',
                'build' => $parsed['physical']['build'] ?? '',
                'distinctive_features' => $parsed['physical']['distinctive_features'] ?? '',
            ],
        ];
    }

    /**
     * Get empty DNA structure
     */
    protected function getEmptyDNA(): array
    {
        return [
            'hair' => ['style' => '', 'color' => '', 'length' => '', 'texture' => ''],
            'wardrobe' => ['outfit' => '', 'colors' => '', 'style' => '', 'footwear' => ''],
            'makeup' => ['style' => '', 'details' => ''],
            'accessories' => [],
            'physical' => ['age_range' => '', 'build' => '', 'distinctive_features' => ''],
        ];
    }

    // =========================================================================
    // PHASE 23: EXPRESSION PRESETS
    // =========================================================================

    /**
     * Get expression preset by name (Phase 23)
     *
     * Use for quick expression selection. For full emotion mapping with
     * intensity, subtext, and Bible traits, use getExpressionFromPsychology().
     *
     * @param string $preset Preset name from EXPRESSION_PRESETS
     * @return array|null Preset data or null if not found
     */
    public function getExpressionPreset(string $preset): ?array
    {
        $preset = strtolower(trim($preset));

        return self::EXPRESSION_PRESETS[$preset] ?? null;
    }

    /**
     * Build expression description for prompt (Phase 23)
     *
     * Combines preset with optional intensity modifier.
     *
     * @param string $preset Preset name from EXPRESSION_PRESETS
     * @param string $intensity Intensity level: subtle, moderate, intense
     * @return string Formatted expression description for prompts
     */
    public function buildExpressionDescription(string $preset, string $intensity = 'moderate'): string
    {
        $presetData = $this->getExpressionPreset($preset);

        if ($presetData === null) {
            Log::debug('[CharacterLookService] Unknown expression preset requested', [
                'preset' => $preset,
                'available' => array_keys(self::EXPRESSION_PRESETS),
            ]);

            return '';
        }

        // Map intensity to modifier word
        $intensityModifiers = [
            'subtle' => 'subtly',
            'moderate' => '',
            'intense' => 'intensely',
        ];

        $modifier = $intensityModifiers[$intensity] ?? '';
        $description = $presetData['description'];

        // Build formatted output
        $parts = [];

        if (!empty($modifier)) {
            $parts[] = "Expression: {$modifier} {$description}";
        } else {
            $parts[] = "Expression: {$description}";
        }

        $parts[] = $presetData['face'];
        $parts[] = $presetData['eyes'];

        return implode('. ', $parts) . '.';
    }

    /**
     * Get all available expression presets (Phase 23)
     *
     * @return array Array of preset names and their descriptions
     */
    public function getExpressionPresets(): array
    {
        $presets = [];

        foreach (self::EXPRESSION_PRESETS as $key => $data) {
            $presets[$key] = [
                'key' => $key,
                'description' => $data['description'],
            ];
        }

        return $presets;
    }

    /**
     * Bridge to CharacterPsychologyService for full emotion mapping (Phase 23)
     *
     * Use when you need:
     * - Intensity modifiers (subtle/moderate/intense)
     * - Subtext layers (body betrays face)
     * - Character Bible trait integration (defining_features)
     *
     * @param string $emotion Emotion from CharacterPsychologyService::EMOTION_MANIFESTATIONS
     * @param string $intensity One of: subtle, moderate, intense
     * @param array $characterTraits Optional Bible traits for enhanced description
     * @return string Full physical description
     */
    public function getExpressionFromPsychology(string $emotion, string $intensity = 'moderate', array $characterTraits = []): string
    {
        if ($this->psychologyService === null) {
            $this->psychologyService = new CharacterPsychologyService();
        }

        return $this->psychologyService->buildEnhancedEmotionDescription($emotion, $intensity, $characterTraits);
    }

    // =========================================================================
    // PHASE 4.3: WARDROBE CONTINUITY TRACKING
    // =========================================================================

    /**
     * Track wardrobe for a character across scenes (Phase 4.3)
     *
     * This maintains a per-scene wardrobe record to detect changes
     * and ensure continuity or flag intentional costume changes.
     *
     * @param array $character The character data
     * @param int $sceneIndex The scene index
     * @param array|null $wardrobeOverride Optional wardrobe override for this scene
     * @return array Updated character with wardrobe tracking
     */
    public function trackWardrobe(array $character, int $sceneIndex, ?array $wardrobeOverride = null): array
    {
        // Initialize wardrobe tracking array if not exists
        if (!isset($character['wardrobePerScene'])) {
            $character['wardrobePerScene'] = [];
        }

        // Determine wardrobe for this scene
        $sceneWardrobe = $wardrobeOverride ?? $character['wardrobe'] ?? [];

        // Store wardrobe for this scene
        $character['wardrobePerScene'][$sceneIndex] = [
            'outfit' => $sceneWardrobe['outfit'] ?? '',
            'colors' => $sceneWardrobe['colors'] ?? '',
            'style' => $sceneWardrobe['style'] ?? '',
            'footwear' => $sceneWardrobe['footwear'] ?? '',
            'isOverride' => $wardrobeOverride !== null,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::debug('[CharacterLookService] Wardrobe tracked', [
            'characterName' => $character['name'] ?? 'Unknown',
            'sceneIndex' => $sceneIndex,
            'isOverride' => $wardrobeOverride !== null,
        ]);

        return $character;
    }

    /**
     * Get wardrobe for a specific scene (Phase 4.3)
     *
     * @param array $character The character data
     * @param int $sceneIndex The scene index
     * @return array|null Wardrobe data for the scene or null
     */
    public function getWardrobeForScene(array $character, int $sceneIndex): ?array
    {
        // Check for scene-specific override first
        if (isset($character['wardrobePerScene'][$sceneIndex])) {
            return $character['wardrobePerScene'][$sceneIndex];
        }

        // Fall back to default wardrobe
        return $character['wardrobe'] ?? null;
    }

    /**
     * Detect wardrobe changes across scenes (Phase 4.3)
     *
     * @param array $character The character data
     * @return array List of detected changes with scene indices
     */
    public function detectWardrobeChanges(array $character): array
    {
        $changes = [];
        $wardrobePerScene = $character['wardrobePerScene'] ?? [];

        if (count($wardrobePerScene) < 2) {
            return $changes;
        }

        // Sort by scene index
        ksort($wardrobePerScene);

        $prevSceneIndex = null;
        $prevWardrobe = null;

        foreach ($wardrobePerScene as $sceneIndex => $wardrobe) {
            if ($prevWardrobe !== null) {
                $differences = $this->compareWardrobes($prevWardrobe, $wardrobe);

                if (!empty($differences)) {
                    $changes[] = [
                        'fromScene' => $prevSceneIndex,
                        'toScene' => $sceneIndex,
                        'differences' => $differences,
                        'isIntentional' => $wardrobe['isOverride'] ?? false,
                    ];
                }
            }

            $prevSceneIndex = $sceneIndex;
            $prevWardrobe = $wardrobe;
        }

        Log::info('[CharacterLookService] Wardrobe changes detected', [
            'characterName' => $character['name'] ?? 'Unknown',
            'totalChanges' => count($changes),
        ]);

        return $changes;
    }

    /**
     * Compare two wardrobes and return differences
     */
    protected function compareWardrobes(array $wardrobe1, array $wardrobe2): array
    {
        $differences = [];
        $fields = ['outfit', 'colors', 'style', 'footwear'];

        foreach ($fields as $field) {
            $val1 = strtolower(trim($wardrobe1[$field] ?? ''));
            $val2 = strtolower(trim($wardrobe2[$field] ?? ''));

            if ($val1 !== $val2 && !empty($val1) && !empty($val2)) {
                $differences[$field] = [
                    'from' => $wardrobe1[$field] ?? '',
                    'to' => $wardrobe2[$field] ?? '',
                ];
            }
        }

        return $differences;
    }

    /**
     * Validate wardrobe continuity for a character (Phase 4.3)
     *
     * Returns warnings if there are unexpected wardrobe changes.
     *
     * @param array $character The character data
     * @param array $scenes The script scenes
     * @return array Validation result with warnings
     */
    public function validateWardrobeContinuity(array $character, array $scenes): array
    {
        $result = [
            'valid' => true,
            'warnings' => [],
            'changes' => [],
        ];

        $changes = $this->detectWardrobeChanges($character);

        foreach ($changes as $change) {
            // Check if scenes are adjacent (might be continuity error)
            $fromScene = $change['fromScene'];
            $toScene = $change['toScene'];
            $isAdjacent = ($toScene - $fromScene) === 1;

            // Check if both scenes are at the same location (stronger continuity expectation)
            $fromLocation = $scenes[$fromScene]['locationRef'] ?? null;
            $toLocation = $scenes[$toScene]['locationRef'] ?? null;
            $sameLocation = $fromLocation !== null && $fromLocation === $toLocation;

            if (!$change['isIntentional']) {
                // Unintentional change detected
                $severity = 'info';
                $message = "Wardrobe change from scene {$fromScene} to scene {$toScene}";

                if ($isAdjacent && $sameLocation) {
                    // Adjacent scenes at same location = likely continuity error
                    $severity = 'warning';
                    $message = "Possible continuity error: Wardrobe changed between adjacent scenes ({$fromScene} to {$toScene}) at the same location";
                    $result['valid'] = false;
                } elseif ($isAdjacent) {
                    $severity = 'info';
                    $message = "Wardrobe changed between adjacent scenes ({$fromScene} to {$toScene})";
                }

                $result['warnings'][] = [
                    'severity' => $severity,
                    'message' => $message,
                    'fromScene' => $fromScene,
                    'toScene' => $toScene,
                    'differences' => $change['differences'],
                ];
            }

            $result['changes'][] = $change;
        }

        return $result;
    }

    /**
     * Set intentional wardrobe change for a scene (Phase 4.3)
     *
     * Marks a wardrobe override as intentional to suppress continuity warnings.
     *
     * @param array $character The character data
     * @param int $sceneIndex The scene index
     * @param array $newWardrobe The new wardrobe for this scene
     * @param string|null $reason Optional reason for the change
     * @return array Updated character
     */
    public function setIntentionalWardrobeChange(
        array $character,
        int $sceneIndex,
        array $newWardrobe,
        ?string $reason = null
    ): array {
        // Initialize wardrobe tracking if not exists
        if (!isset($character['wardrobePerScene'])) {
            $character['wardrobePerScene'] = [];
        }

        // Store the intentional change
        $character['wardrobePerScene'][$sceneIndex] = [
            'outfit' => $newWardrobe['outfit'] ?? '',
            'colors' => $newWardrobe['colors'] ?? '',
            'style' => $newWardrobe['style'] ?? '',
            'footwear' => $newWardrobe['footwear'] ?? '',
            'isOverride' => true,
            'isIntentional' => true,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('[CharacterLookService] Intentional wardrobe change set', [
            'characterName' => $character['name'] ?? 'Unknown',
            'sceneIndex' => $sceneIndex,
            'reason' => $reason,
        ]);

        return $character;
    }

    /**
     * Batch auto-populate DNA for multiple characters (Phase 4.1)
     *
     * @param array $characters Array of character data
     * @param bool $overwrite Whether to overwrite existing fields
     * @return array Updated characters array
     */
    public function batchAutoPopulateDNA(array $characters, bool $overwrite = false): array
    {
        $updated = [];
        $processed = 0;
        $skipped = 0;

        foreach ($characters as $idx => $character) {
            // Skip if character already has DNA and we're not overwriting
            if (!$overwrite && $this->characterHasDNA($character)) {
                $updated[$idx] = $character;
                $skipped++;
                continue;
            }

            $updated[$idx] = $this->autoPopulateDNA($character, $overwrite);
            $processed++;

            // Small delay to avoid rate limits
            if ($processed % 3 === 0) {
                usleep(500000); // 0.5 second delay every 3 characters
            }
        }

        Log::info('[CharacterLookService] Batch DNA population completed', [
            'total' => count($characters),
            'processed' => $processed,
            'skipped' => $skipped,
        ]);

        return $updated;
    }

    /**
     * Check if character has meaningful DNA data
     */
    protected function characterHasDNA(array $character): bool
    {
        // Check hair
        $hair = $character['hair'] ?? [];
        if (!empty($hair['color']) || !empty($hair['style'])) {
            return true;
        }

        // Check wardrobe
        $wardrobe = $character['wardrobe'] ?? [];
        if (!empty($wardrobe['outfit'])) {
            return true;
        }

        // Check accessories
        if (!empty($character['accessories'])) {
            return true;
        }

        return false;
    }
}
