<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * StructuredPromptBuilderService
 *
 * Builds structured JSON-based prompts for photorealistic image generation.
 * This service creates prompts optimized for NanoBanana Pro and other
 * high-quality image generation models.
 *
 * The structured approach provides:
 * - Granular control over subject, attire, pose, environment
 * - Explicit negative prompts to avoid AI artifacts
 * - Authenticity markers for realistic output
 * - Technical specifications for quality control
 */
class StructuredPromptBuilderService
{
    /**
     * Visual mode configurations with structured templates.
     * These define the base settings for each visual style.
     */
    public const VISUAL_MODE_TEMPLATES = [
        'cinematic-realistic' => [
            'prompt_type' => 'photorealistic_cinematic',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'ultra_photoreal_cinematic_film_still',
                'sharpness' => 'crisp_but_natural',
                'film_grain' => 'subtle_35mm',
                'color_grade' => 'cinematic_color_science',
                'dynamic_range' => 'natural_cinematic',
                'skin_rendering' => 'real_texture_no_retouch',
            ],
            'global_rules' => [
                'camera_language' => 'shot on ARRI Alexa Mini LF with Zeiss Master Prime 50mm lens, cinematic framing, professional cinematography, natural bokeh with real optical characteristics',
                'authenticity_markers' => 'subtle 35mm film grain visible at pixel level, gentle halation bleeding on overexposed highlights, organic chromatic aberration at frame edges, realistic skin with visible pores and texture, micro-imperfections like tiny freckles or slight skin blemishes, natural hair with individual strands visible, fabric weave texture clearly rendered, environmental dust particles in light rays',
                'lighting_language' => 'motivated natural or practical lighting only, cinematographer-quality light shaping with real falloff, realistic shadows with bounce light detail, no artificial studio perfection',
            ],
            'quality_keywords' => [
                '8K UHD',
                'ultra high resolution',
                'photorealistic',
                'hyperdetailed',
                'cinematic depth of field',
                'HDR',
                'professional color grading',
                'award-winning cinematography',
                'film still quality',
                'natural skin texture with pores',
                'real fabric texture',
                'authentic lighting',
            ],
            'negative_prompt' => [
                'cartoon',
                'anime',
                'illustrated',
                'stylized',
                '3D render',
                'CGI look',
                'digital painting',
                'concept art',
                'plastic skin',
                'beauty retouch',
                'over-sharpened',
                'AI glow',
                'fake bokeh',
                'perfect symmetry',
                'watermark',
                'text',
                'logo',
                'blurry',
                'low quality',
                'oversaturated',
                'airbrushed skin',
                'waxy complexion',
                'doll-like features',
                'uncanny valley',
                'smooth plastic texture',
                'stock photo look',
                'generic lighting',
                'artificial perfection',
                'instagram filter',
                'hyperreal gloss',
                'mannequin appearance',
            ],
        ],
        'documentary-realistic' => [
            'prompt_type' => 'photorealistic_documentary',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'ultra_photoreal_candid_documentary',
                'sharpness' => 'crisp_but_natural',
                'film_grain' => 'subtle_35mm',
                'color_grade' => 'restrained_cinematic_realism',
                'dynamic_range' => 'natural_not_hdr',
                'skin_rendering' => 'real_texture_no_retouch',
            ],
            'global_rules' => [
                'camera_language' => '35mm lens equivalent shot on Leica M or Canon R5, eye-level candid framing, slightly imperfect composition suggesting real moment capture, focus on eyes when person is present',
                'authenticity_markers' => 'subtle halation on bright highlights, visible environmental texture (dust, steam, mist), realistic background clutter, slight motion blur on secondary elements, natural skin imperfections, wrinkles and expression lines, worn fabric and real material textures, environmental context (rain droplets, snow, leaves)',
                'lighting_language' => 'motivated natural light only - window light, overcast daylight, practical lamps, golden hour sun, deep shadows with visible detail, no artificial fill light',
            ],
            'quality_keywords' => [
                '4K Ultra HD',
                'high resolution',
                'photorealistic',
                'documentary photography',
                'street photography style',
                'natural lighting',
                'candid moment captured',
                'authentic real-world scene',
                'environmental portrait',
                'genuine human moment',
            ],
            'negative_prompt' => [
                'studio lighting',
                'beauty retouch',
                'plastic skin',
                'over-sharpened',
                'HDR overprocessed',
                'AI glow',
                'perfect symmetry',
                'overly clean background',
                'fake bokeh',
                'text',
                'logo',
                'watermark',
                'cartoon',
                'painterly',
                'posed model',
                'artificial',
                'fashion photography',
                'commercial look',
                'sterile environment',
                'perfect lighting',
                'flawless skin',
                'magazine cover',
            ],
        ],
        'stylized-animation' => [
            'prompt_type' => 'stylized_animation',
            'output_settings' => [
                'resolution_target' => 'high_res',
                'render_style' => '3d_animation_stylized',
                'sharpness' => 'clean_crisp',
                'film_grain' => 'none',
                'color_grade' => 'vibrant_animated',
                'dynamic_range' => 'enhanced',
                'skin_rendering' => 'stylized_clean',
            ],
            'global_rules' => [
                'camera_language' => 'animated film camera work, dynamic angles, expressive framing',
                'authenticity_markers' => 'clean lines, stylized proportions, animated film quality',
                'lighting_language' => 'stylized lighting, rim lights, dramatic color lighting',
            ],
            'quality_keywords' => [
                '4K',
                'Pixar quality',
                'DreamWorks style',
                'professional 3D animation',
                'stylized',
                'vibrant colors',
                'clean rendering',
            ],
            'negative_prompt' => [
                'photorealistic',
                'live-action',
                'real person',
                'documentary',
                'film grain',
                'natural lighting',
                'low quality',
                'blurry',
            ],
        ],
        'mixed-hybrid' => [
            'prompt_type' => 'mixed_hybrid',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'flexible_creative',
                'sharpness' => 'balanced',
                'film_grain' => 'optional_subtle',
                'color_grade' => 'creative_flexible',
                'dynamic_range' => 'balanced',
                'skin_rendering' => 'context_appropriate',
            ],
            'global_rules' => [
                'camera_language' => 'flexible camera work appropriate to scene',
                'authenticity_markers' => 'style-appropriate markers',
                'lighting_language' => 'context-appropriate lighting',
            ],
            'quality_keywords' => [
                'high quality',
                'detailed',
                'professional',
                'well-composed',
            ],
            'negative_prompt' => [
                'low quality',
                'blurry',
                'watermark',
                'text',
                'logo',
            ],
        ],
    ];

    /**
     * Preset lighting configurations.
     */
    public const LIGHTING_PRESETS = [
        'golden_hour' => [
            'lighting' => 'warm golden hour sunlight streaming at low angle, long soft shadows, golden rim lighting on subject',
            'mood' => 'warm, romantic, nostalgic',
            'color_palette' => 'warm oranges, golden yellows, soft shadows with blue fill',
        ],
        'blue_hour' => [
            'lighting' => 'soft blue hour twilight, ambient blue tones, city lights beginning to glow',
            'mood' => 'contemplative, serene, mysterious',
            'color_palette' => 'deep blues, purple undertones, warm accent lights',
        ],
        'overcast_natural' => [
            'lighting' => 'soft overcast daylight, diffused natural illumination, no harsh shadows',
            'mood' => 'natural, authentic, approachable',
            'color_palette' => 'neutral tones, soft contrast, natural colors',
        ],
        'dramatic_low_key' => [
            'lighting' => 'dramatic low-key chiaroscuro lighting, deep shadows with selective highlights, motivated single source',
            'mood' => 'dramatic, intense, cinematic',
            'color_palette' => 'high contrast, deep blacks, selective bright highlights',
        ],
        'studio_soft' => [
            'lighting' => 'professional studio soft box lighting, beauty dish key light, fill and rim lights',
            'mood' => 'polished, professional, commercial',
            'color_palette' => 'clean whites, accurate skin tones, controlled shadows',
        ],
        'neon_night' => [
            'lighting' => 'neon signs casting colored light, urban night illumination, practical light sources',
            'mood' => 'urban, energetic, modern',
            'color_palette' => 'neon pinks, cyans, purples against dark backgrounds',
        ],
        'window_light' => [
            'lighting' => 'soft natural window light, diffused daylight through curtains, gentle gradients',
            'mood' => 'intimate, peaceful, natural',
            'color_palette' => 'soft neutral tones, gentle shadows, natural warmth',
        ],
    ];

    /**
     * Camera/lens presets for different shot types.
     */
    public const CAMERA_PRESETS = [
        'portrait_85mm' => [
            'lens' => '85mm f/1.4',
            'depth_of_field' => 'shallow, creamy bokeh',
            'description' => '85mm portrait lens with beautiful bokeh and flattering compression',
        ],
        'wide_24mm' => [
            'lens' => '24mm f/2.8',
            'depth_of_field' => 'medium to deep',
            'description' => '24mm wide angle for establishing shots and environmental portraits',
        ],
        'standard_50mm' => [
            'lens' => '50mm f/1.8',
            'depth_of_field' => 'medium shallow',
            'description' => '50mm natural perspective, close to human eye',
        ],
        'telephoto_135mm' => [
            'lens' => '135mm f/2',
            'depth_of_field' => 'very shallow, strong background separation',
            'description' => '135mm telephoto for dramatic compression and isolation',
        ],
        'cinematic_anamorphic' => [
            'lens' => 'anamorphic 40mm',
            'depth_of_field' => 'oval bokeh, horizontal flares',
            'description' => 'anamorphic lens with characteristic oval bokeh and lens flares',
        ],
        'street_35mm' => [
            'lens' => '35mm f/2',
            'depth_of_field' => 'medium, environmental context',
            'description' => '35mm street photography lens, subject in context',
        ],
    ];

    /**
     * Build a complete structured prompt from scene data.
     *
     * @param array $options Configuration options
     * @return array Structured prompt data
     */
    public function build(array $options): array
    {
        $visualMode = $options['visual_mode'] ?? 'cinematic-realistic';
        $template = self::VISUAL_MODE_TEMPLATES[$visualMode] ?? self::VISUAL_MODE_TEMPLATES['cinematic-realistic'];

        // Get aspect ratio from project
        $aspectRatio = $options['aspect_ratio'] ?? '16:9';

        // Build the structured prompt
        $structuredPrompt = [
            'meta_data' => [
                'prompt_type' => $template['prompt_type'],
                'visual_mode' => $visualMode,
                'version' => 'v2.0_STRUCTURED_PROMPT',
            ],
            'output_settings' => array_merge($template['output_settings'], [
                'aspect_ratio' => $aspectRatio,
                'orientation' => $this->getOrientation($aspectRatio),
            ]),
            'global_rules' => $template['global_rules'],
            'creative_prompt' => $this->buildCreativePrompt($options, $template),
            'technical_specifications' => $this->buildTechnicalSpecs($options, $template),
            'negative_prompt' => $this->buildNegativePrompt($options, $template),
        ];

        Log::debug('StructuredPromptBuilder: Built structured prompt', [
            'visualMode' => $visualMode,
            'hasSubject' => !empty($structuredPrompt['creative_prompt']['subject']),
            'hasEnvironment' => !empty($structuredPrompt['creative_prompt']['environment']),
        ]);

        return $structuredPrompt;
    }

    /**
     * Build the creative prompt section.
     */
    protected function buildCreativePrompt(array $options, array $template): array
    {
        $sceneDescription = $options['scene_description'] ?? '';
        $characterBible = $options['character_bible'] ?? null;
        $locationBible = $options['location_bible'] ?? null;
        $styleBible = $options['style_bible'] ?? null;
        $sceneIndex = $options['scene_index'] ?? 0;

        // Check if Scene DNA is available (unified Bible data)
        $sceneDNA = $options['scene_dna'] ?? null;
        if ($sceneDNA && ($sceneDNA['enabled'] ?? false) && !empty($sceneDNA['scenes'][$sceneIndex])) {
            return $this->buildCreativePromptFromSceneDNA($sceneDNA['scenes'][$sceneIndex], $options, $template);
        }

        // Extract multi-shot context for proper framing
        $shotType = $options['shot_type'] ?? null;
        $shotIndex = $options['shot_index'] ?? null;
        $totalShots = $options['total_shots'] ?? null;
        $isMultiShot = $options['is_multi_shot'] ?? false;

        // Build subject from character bible
        $subject = $this->buildSubjectDescription($characterBible, $sceneIndex, $sceneDescription);

        // Build environment from location bible
        $environment = $this->buildEnvironmentDescription($locationBible, $sceneIndex, $sceneDescription);

        // Build lighting from style bible and location
        $lighting = $this->buildLightingDescription($styleBible, $locationBible, $sceneIndex);

        // Build scene summary
        $sceneSummary = $this->buildSceneSummary($sceneDescription, $subject, $environment);

        // Build Character DNA for Hollywood-level consistency
        $characterDNA = $this->buildCharacterDNA($characterBible, $sceneIndex);

        // Build Style DNA for visual consistency
        $styleDNA = $this->buildStyleDNA($styleBible);

        // Build Location DNA for environmental consistency
        $locationDNA = $this->buildLocationDNA($locationBible, $sceneIndex);

        // Build shot composition/framing instructions for multi-shot sequences
        $composition = $this->buildShotComposition($shotType, $shotIndex, $totalShots, $isMultiShot);

        return [
            'scene_summary' => $sceneSummary,
            'subject' => $subject,
            'environment' => $environment,
            'lighting_and_atmosphere' => $lighting,
            'authenticity_markers' => $template['global_rules']['authenticity_markers'],
            'mood' => $styleBible['atmosphere'] ?? $lighting['mood'] ?? 'cinematic, dramatic',
            'character_dna' => $characterDNA, // Array of DNA blocks for each character
            'style_dna' => $styleDNA,         // Style DNA block for visual consistency
            'location_dna' => $locationDNA,   // Location DNA block for environmental consistency
            'composition' => $composition,    // Shot-specific framing instructions
        ];
    }

    /**
     * Build creative prompt from Scene DNA (unified Bible data).
     * Scene DNA provides pre-processed, validated, single-source-of-truth data per scene.
     */
    protected function buildCreativePromptFromSceneDNA(array $sceneDNAEntry, array $options, array $template): array
    {
        $sceneDescription = $sceneDNAEntry['visualDescription'] ?? $options['scene_description'] ?? '';

        // Extract multi-shot context for proper framing
        $shotType = $options['shot_type'] ?? null;
        $shotIndex = $options['shot_index'] ?? null;
        $totalShots = $options['total_shots'] ?? null;
        $isMultiShot = $options['is_multi_shot'] ?? false;

        // Build subject from Scene DNA characters
        $subject = $this->buildSubjectFromSceneDNA($sceneDNAEntry, $sceneDescription);

        // Build environment from Scene DNA location
        $environment = $this->buildEnvironmentFromSceneDNA($sceneDNAEntry, $sceneDescription);

        // Build lighting from Scene DNA
        $lighting = $this->buildLightingFromSceneDNA($sceneDNAEntry);

        // Build scene summary
        $sceneSummary = $this->buildSceneSummary($sceneDescription, $subject, $environment);

        // Build Character DNA blocks for consistency
        $characterDNA = $this->buildCharacterDNAFromSceneDNA($sceneDNAEntry);

        // Build Style DNA for visual consistency (pass visual_mode from options as fallback)
        $styleDNA = $this->buildStyleDNAFromSceneDNA($sceneDNAEntry, $options['visual_mode'] ?? null);

        // Build Location DNA for environmental consistency
        $locationDNA = $this->buildLocationDNAFromSceneDNA($sceneDNAEntry);

        // Build shot composition/framing instructions
        $composition = $this->buildShotComposition($shotType, $shotIndex, $totalShots, $isMultiShot);

        Log::debug('StructuredPromptBuilder: Built prompt from Scene DNA', [
            'sceneIndex' => $sceneDNAEntry['sceneIndex'] ?? 'unknown',
            'characterCount' => $sceneDNAEntry['characterCount'] ?? 0,
            'hasLocation' => !empty($sceneDNAEntry['location']),
            'hasStyle' => !empty($sceneDNAEntry['style']),
            'visualMode' => $options['visual_mode'] ?? 'cinematic-realistic',
            'styleDNAVisuaMode' => $sceneDNAEntry['style']['visualMode'] ?? 'not-set',
            'hasStyleDNA' => !empty($styleDNA),
            'hasCharacterDNA' => !empty($characterDNA),
        ]);

        return [
            'scene_summary' => $sceneSummary,
            'subject' => $subject,
            'environment' => $environment,
            'lighting_and_atmosphere' => $lighting,
            'authenticity_markers' => $template['global_rules']['authenticity_markers'],
            'mood' => $sceneDNAEntry['mood'] ?? $lighting['mood'] ?? 'cinematic, dramatic',
            'character_dna' => $characterDNA,
            'style_dna' => $styleDNA,
            'location_dna' => $locationDNA,
            'composition' => $composition,
        ];
    }

    /**
     * Build subject description from Scene DNA.
     */
    protected function buildSubjectFromSceneDNA(array $sceneDNAEntry, string $sceneDescription): array
    {
        $subject = [
            'description' => '',
            'physical_details' => '',
            'expression' => 'natural, authentic expression',
            'attire' => '',
            'pose' => '',
            'micro_action' => '',
            'character_count' => 0,
            'character_names' => [],
            'story_action' => '',
            'body_language' => '',
            'character_count_instruction' => '',
        ];

        $characters = $sceneDNAEntry['characters'] ?? [];
        if (empty($characters)) {
            $subject['description'] = $this->extractSubjectFromDescription($sceneDescription);
            $subject['character_count'] = $this->detectCharacterCountFromDescription($sceneDescription);
            $subject['story_action'] = $this->extractStoryAction($sceneDescription);
            return $subject;
        }

        $subject['character_count'] = count($characters);
        $subject['character_names'] = $sceneDNAEntry['characterNames'] ?? array_map(fn($c) => $c['name'] ?? 'Unknown', $characters);

        // Build character count instruction
        $subject['character_count_instruction'] = $this->buildCharacterCountInstruction(
            count($characters),
            $subject['character_names']
        );

        // Build detailed subject from first/main character
        $mainCharacter = $characters[0];
        $subject['description'] = $mainCharacter['description'] ?? '';

        // Build physical details from Scene DNA character data
        $physicalParts = [];
        if (!empty($mainCharacter['hair'])) {
            $hair = $mainCharacter['hair'];
            if (!empty($hair['color'])) $physicalParts[] = $hair['color'] . ' hair';
            if (!empty($hair['style'])) $physicalParts[] = $hair['style'];
        }
        $subject['physical_details'] = implode(', ', $physicalParts) ?: 'with natural appearance and realistic features';

        // Build attire from wardrobe
        if (!empty($mainCharacter['wardrobe'])) {
            $wardrobe = $mainCharacter['wardrobe'];
            $attireParts = [];
            if (!empty($wardrobe['outfit'])) $attireParts[] = $wardrobe['outfit'];
            if (!empty($wardrobe['colors'])) $attireParts[] = 'in ' . $wardrobe['colors'];
            $subject['attire'] = implode(' ', $attireParts);
        }

        // Extract pose and action from scene description
        $subject['pose'] = $this->extractPoseFromDescription($sceneDescription);
        $subject['micro_action'] = $this->extractMicroAction($sceneDescription);
        $subject['story_action'] = $this->extractStoryAction($sceneDescription);
        $subject['body_language'] = $this->extractBodyLanguage($sceneDescription);

        // Secondary characters
        if (count($characters) > 1) {
            $secondaryDescriptions = [];
            for ($i = 1; $i < count($characters); $i++) {
                $secondaryDescriptions[] = $characters[$i]['description'] ?? '';
            }
            $subject['secondary_subjects'] = implode(', also featuring ', array_filter($secondaryDescriptions));
        }

        return $subject;
    }

    /**
     * Build environment description from Scene DNA.
     */
    protected function buildEnvironmentFromSceneDNA(array $sceneDNAEntry, string $sceneDescription): array
    {
        $environment = [
            'location' => '',
            'background' => '',
            'details' => '',
            'atmosphere' => '',
            'time_of_day' => 'day',
            'weather' => 'clear',
        ];

        $location = $sceneDNAEntry['location'] ?? null;
        if (!$location) {
            $environment['location'] = $this->extractLocationFromDescription($sceneDescription);
            return $environment;
        }

        $environment['location'] = $location['name'] ?? '';
        $environment['background'] = $location['description'] ?? '';
        $environment['details'] = $location['currentState'] ?? $this->extractEnvironmentDetails($sceneDescription);
        $environment['atmosphere'] = $location['atmosphere'] ?? '';
        $environment['time_of_day'] = $location['timeOfDay'] ?? 'day';
        $environment['weather'] = $location['weather'] ?? 'clear';

        return $environment;
    }

    /**
     * Build lighting description from Scene DNA.
     */
    protected function buildLightingFromSceneDNA(array $sceneDNAEntry): array
    {
        $lighting = [
            'lighting' => '',
            'mood' => '',
            'color_palette' => '',
        ];

        // Get time of day from location
        $location = $sceneDNAEntry['location'] ?? null;
        $timeOfDay = $location['timeOfDay'] ?? 'day';

        // Map time of day to lighting preset
        $lightingPreset = $this->getTimeOfDayLighting($timeOfDay);
        $lighting = array_merge($lighting, $lightingPreset);

        // Override with style if available
        $style = $sceneDNAEntry['style'] ?? null;
        if ($style) {
            if (!empty($style['mood'])) {
                $lighting['mood'] = $style['mood'];
            }
            if (!empty($style['colorPalette'])) {
                $lighting['color_palette'] = $style['colorPalette'];
            }
            if (!empty($style['lightingStyle'])) {
                $lighting['lighting'] = $style['lightingStyle'];
            }
        }

        // Location-specific lighting override
        if ($location && !empty($location['lightingStyle'])) {
            $lighting['lighting'] = $location['lightingStyle'];
        }

        return $lighting;
    }

    /**
     * Build Character DNA blocks from Scene DNA characters.
     */
    protected function buildCharacterDNAFromSceneDNA(array $sceneDNAEntry): array
    {
        $characters = $sceneDNAEntry['characters'] ?? [];
        $dnaBlocks = [];

        foreach ($characters as $character) {
            $dna = $this->buildSingleCharacterDNAFromEntry($character);
            if (!empty($dna)) {
                $dnaBlocks[] = $dna;
            }
        }

        return $dnaBlocks;
    }

    /**
     * Build DNA template for a single Scene DNA character entry.
     */
    protected function buildSingleCharacterDNAFromEntry(array $character): string
    {
        $name = $character['name'] ?? 'Character';
        $parts = [];

        // Identity/Face section with REALISM markers
        $identityParts = [];
        if (!empty($character['description'])) {
            $identityParts[] = $character['description'];
        }
        $identityParts[] = "Same skin tone, same complexion, same facial proportions";
        $identityParts[] = "Same body type and build";
        $parts[] = "IDENTITY: " . implode('. ', $identityParts);

        // CRITICAL: Add realism markers for photorealistic rendering
        $parts[] = "REALISM (MANDATORY): Real human skin with visible pores and natural texture, realistic facial features with natural imperfections, authentic skin tones with subtle color variation, genuine human proportions - NOT CGI, NOT 3D render, NOT digital art, NOT airbrushed";

        // Hair section
        $hair = $character['hair'] ?? [];
        if (!empty(array_filter($hair))) {
            $hairParts = [];
            if (!empty($hair['color'])) $hairParts[] = $hair['color'];
            if (!empty($hair['length'])) $hairParts[] = $hair['length'];
            if (!empty($hair['style'])) $hairParts[] = $hair['style'];
            if (!empty($hair['texture'])) $hairParts[] = $hair['texture'] . ' texture';
            // Add realism for hair
            $hairParts[] = 'individual strands visible, realistic hair texture';
            if (!empty($hairParts)) {
                $parts[] = "HAIR (EXACT MATCH): " . implode(', ', $hairParts);
            }
        }

        // Wardrobe section
        $wardrobe = $character['wardrobe'] ?? [];
        if (!empty(array_filter($wardrobe))) {
            $wardrobeParts = [];
            if (!empty($wardrobe['outfit'])) $wardrobeParts[] = $wardrobe['outfit'];
            if (!empty($wardrobe['colors'])) $wardrobeParts[] = "in " . $wardrobe['colors'];
            if (!empty($wardrobe['style'])) $wardrobeParts[] = $wardrobe['style'] . ' style';
            // Add realism for fabric
            $wardrobeParts[] = 'realistic fabric weave texture, natural fabric drape';
            if (!empty($wardrobeParts)) {
                $parts[] = "WARDROBE (EXACT MATCH): " . implode(', ', $wardrobeParts);
            }
        }

        // Makeup section
        $makeup = $character['makeup'] ?? [];
        if (!empty(array_filter($makeup))) {
            $makeupParts = [];
            if (!empty($makeup['style'])) $makeupParts[] = $makeup['style'];
            if (!empty($makeup['details'])) $makeupParts[] = $makeup['details'];
            if (!empty($makeupParts)) {
                $parts[] = "MAKEUP (EXACT MATCH): " . implode(', ', $makeupParts);
            }
        }

        // Accessories section
        $accessories = $character['accessories'] ?? [];
        if (!empty($accessories)) {
            $accessoryList = is_array($accessories) ? implode(', ', $accessories) : $accessories;
            if (!empty(trim($accessoryList))) {
                $parts[] = "ACCESSORIES (EXACT MATCH): " . $accessoryList;
            }
        }

        if (count($parts) <= 2) {  // Changed from 1 to 2 since we always add REALISM now
            return '';
        }

        return "CHARACTER DNA - {$name} (MUST MATCH EXACTLY):\n" . implode("\n", $parts);
    }

    /**
     * Build Style DNA from Scene DNA.
     * @param array $sceneDNAEntry The Scene DNA entry for this scene
     * @param string|null $fallbackVisualMode Visual mode from options as fallback
     */
    protected function buildStyleDNAFromSceneDNA(array $sceneDNAEntry, ?string $fallbackVisualMode = null): string
    {
        $style = $sceneDNAEntry['style'] ?? null;
        $parts = [];

        // CRITICAL: Always add realism enforcement for cinematic-realistic mode
        // This ensures photorealistic output even when style data is minimal
        // Priority: style.visualMode > fallback from options > default to cinematic-realistic
        $visualMode = $style['visualMode'] ?? $fallbackVisualMode ?? 'cinematic-realistic';
        if (in_array($visualMode, ['cinematic-realistic', 'documentary-realistic'])) {
            $parts[] = "RENDER MODE (MANDATORY): PHOTOREALISTIC - Real photograph quality, shot on professional cinema camera, natural film grain, authentic lighting with real shadows and highlights, NO CGI, NO 3D render, NO illustration, NO digital painting, NO anime/cartoon styling";
            $parts[] = "QUALITY MARKERS: 8K UHD, hyperdetailed, cinematic depth of field, professional color grading, natural bokeh with real optical characteristics";
        }

        if ($style) {
            if (!empty($style['visualStyle'])) {
                $parts[] = "VISUAL STYLE: " . $style['visualStyle'];
            }
            if (!empty($style['colorPalette'])) {
                $parts[] = "COLOR GRADE (CONSISTENT): " . $style['colorPalette'];
            }
            if (!empty($style['lightingStyle'])) {
                $parts[] = "LIGHTING (CONSISTENT): " . $style['lightingStyle'];
            }
            if (!empty($style['mood'])) {
                $parts[] = "ATMOSPHERE: " . $style['mood'];
            }
            if (!empty($style['era'])) {
                $parts[] = "ERA/PERIOD: " . $style['era'];
            }
        }

        if (empty($parts)) {
            return '';
        }

        return "STYLE DNA - VISUAL CONSISTENCY ANCHOR:\n" . implode("\n", $parts);
    }

    /**
     * Build Location DNA from Scene DNA.
     */
    protected function buildLocationDNAFromSceneDNA(array $sceneDNAEntry): string
    {
        $location = $sceneDNAEntry['location'] ?? null;
        if (!$location) {
            return '';
        }

        $name = $location['name'] ?? 'Location';
        $parts = [];

        if (!empty($location['description'])) {
            $parts[] = "ENVIRONMENT: " . $location['description'];
        }

        if (!empty($location['type'])) {
            $typeMap = [
                'interior' => 'Interior space',
                'exterior' => 'Exterior/outdoor location',
                'abstract' => 'Abstract/stylized environment',
            ];
            $parts[] = "TYPE: " . ($typeMap[$location['type']] ?? $location['type']);
        }

        // Time and Weather
        $timeWeather = [];
        if (!empty($location['timeOfDay'])) {
            $timeMap = [
                'day' => 'Daytime',
                'night' => 'Nighttime',
                'dawn' => 'Dawn/early morning',
                'dusk' => 'Dusk/twilight',
                'golden_hour' => 'Golden hour',
            ];
            $timeWeather[] = $timeMap[$location['timeOfDay']] ?? $location['timeOfDay'];
        }
        if (!empty($location['weather'])) {
            $weatherMap = [
                'clear' => 'clear sky',
                'cloudy' => 'overcast/cloudy',
                'rainy' => 'rainy conditions',
                'foggy' => 'foggy/misty atmosphere',
                'stormy' => 'stormy weather',
                'snowy' => 'snowy conditions',
            ];
            $timeWeather[] = $weatherMap[$location['weather']] ?? $location['weather'];
        }
        if (!empty($timeWeather)) {
            $parts[] = "TIME/WEATHER (CONSISTENT): " . implode(', ', $timeWeather);
        }

        if (!empty($location['atmosphere'])) {
            $parts[] = "ATMOSPHERE: " . $location['atmosphere'];
        }

        if (!empty($location['mood'])) {
            $parts[] = "MOOD: " . $location['mood'];
        }

        if (!empty($location['lightingStyle'])) {
            $parts[] = "LIGHTING: " . $location['lightingStyle'];
        }

        // Current state from Scene DNA
        if (!empty($location['currentState'])) {
            $parts[] = "CURRENT STATE: " . $location['currentState'];
        }

        if (count($parts) <= 1) {
            return '';
        }

        return "LOCATION DNA - {$name} (MAINTAIN CONSISTENCY):\n" . implode("\n", $parts);
    }

    /**
     * Build detailed subject description from character bible.
     */
    protected function buildSubjectDescription(?array $characterBible, ?int $sceneIndex, string $sceneDescription): array
    {
        $subject = [
            'description' => '',
            'physical_details' => '',
            'expression' => 'natural, authentic expression',
            'attire' => '',
            'pose' => '',
            'micro_action' => '',
            // =================================================================
            // PHASE 1: NEW FIELDS FOR CHARACTER COUNT & STORY ACTION
            // =================================================================
            'character_count' => 0,
            'character_names' => [],
            'story_action' => '',
            'body_language' => '',
            'character_count_instruction' => '',
        ];

        if (!$characterBible || !($characterBible['enabled'] ?? false)) {
            // Extract subject hints from scene description
            $subject['description'] = $this->extractSubjectFromDescription($sceneDescription);
            $subject['character_count'] = $this->detectCharacterCountFromDescription($sceneDescription);
            $subject['story_action'] = $this->extractStoryAction($sceneDescription);
            return $subject;
        }

        $characters = $characterBible['characters'] ?? [];
        $sceneCharacters = [];

        foreach ($characters as $character) {
            // Support multiple field names for scene assignment (matching ImageGenerationService)
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];

            // Include character if:
            // - sceneIndex is null (no scene context, e.g., portrait generation) - include all
            // - Empty appliedScenes array means "applies to ALL scenes" (per UI design)
            // - Character is specifically assigned to this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneCharacters[] = $character;
            }
        }

        // Debug logging for character filtering
        Log::debug('StructuredPromptBuilder: Character filtering for scene', [
            'sceneIndex' => $sceneIndex,
            'totalCharactersInBible' => count($characters),
            'filteredCharacterCount' => count($sceneCharacters),
            'filteredCharacters' => array_map(fn($c) => [
                'name' => $c['name'] ?? 'Unknown',
                'appliedScenes' => $c['appliedScenes'] ?? $c['appearsInScenes'] ?? [],
            ], $sceneCharacters),
            'excludedCharacters' => array_map(fn($c) => [
                'name' => $c['name'] ?? 'Unknown',
                'appliedScenes' => $c['appliedScenes'] ?? $c['appearsInScenes'] ?? [],
            ], array_filter($characters, fn($c) => !in_array($c, $sceneCharacters))),
        ]);

        if (empty($sceneCharacters)) {
            $subject['description'] = $this->extractSubjectFromDescription($sceneDescription);
            $subject['character_count'] = $this->detectCharacterCountFromDescription($sceneDescription);
            $subject['story_action'] = $this->extractStoryAction($sceneDescription);
            return $subject;
        }

        // =================================================================
        // PHASE 1: Track character count for duplicate prevention
        // =================================================================
        $subject['character_count'] = count($sceneCharacters);
        $subject['character_names'] = array_map(fn($c) => $c['name'] ?? 'Unknown', $sceneCharacters);

        // Build character count instruction based on how many characters are in scene
        $subject['character_count_instruction'] = $this->buildCharacterCountInstruction(
            count($sceneCharacters),
            $subject['character_names']
        );

        // Build detailed subject from first/main character
        $mainCharacter = $sceneCharacters[0];

        $subject['description'] = $mainCharacter['description'] ?? '';
        $subject['physical_details'] = $this->extractPhysicalDetails($mainCharacter);
        $subject['expression'] = $mainCharacter['defaultExpression'] ?? 'natural, authentic expression';
        $subject['attire'] = $mainCharacter['attire'] ?? '';

        // Extract pose from scene description if available
        $subject['pose'] = $this->extractPoseFromDescription($sceneDescription);
        $subject['micro_action'] = $this->extractMicroAction($sceneDescription);

        // =================================================================
        // PHASE 1: Extract story action from scene description
        // =================================================================
        $subject['story_action'] = $this->extractStoryAction($sceneDescription);
        $subject['body_language'] = $this->extractBodyLanguage($sceneDescription);

        // If multiple characters, add secondary
        if (count($sceneCharacters) > 1) {
            $secondaryDescriptions = [];
            for ($i = 1; $i < count($sceneCharacters); $i++) {
                $secondaryDescriptions[] = $sceneCharacters[$i]['description'] ?? '';
            }
            $subject['secondary_subjects'] = implode(', also featuring ', array_filter($secondaryDescriptions));
        }

        return $subject;
    }

    /**
     * Extract physical details from character data.
     */
    protected function extractPhysicalDetails(array $character): string
    {
        $details = [];

        // Try to extract structured physical details if available
        if (!empty($character['physical'])) {
            $physical = $character['physical'];
            if (!empty($physical['skin_tone'])) $details[] = $physical['skin_tone'] . ' skin tone';
            if (!empty($physical['hair'])) $details[] = $physical['hair'];
            if (!empty($physical['eye_color'])) $details[] = $physical['eye_color'] . ' eyes';
            if (!empty($physical['build'])) $details[] = $physical['build'] . ' build';
            if (!empty($physical['age_range'])) $details[] = 'approximately ' . $physical['age_range'] . ' years old';
        }

        // Fall back to description parsing
        if (empty($details) && !empty($character['description'])) {
            return 'with natural appearance and realistic features';
        }

        return implode(', ', $details);
    }

    /**
     * Build environment description from location bible.
     */
    protected function buildEnvironmentDescription(?array $locationBible, ?int $sceneIndex, string $sceneDescription): array
    {
        $environment = [
            'location' => '',
            'background' => '',
            'details' => '',
            'atmosphere' => '',
            'time_of_day' => 'day',
            'weather' => 'clear',
        ];

        if (!$locationBible || !($locationBible['enabled'] ?? false)) {
            // Extract environment hints from scene description
            $environment['location'] = $this->extractLocationFromDescription($sceneDescription);
            return $environment;
        }

        $locations = $locationBible['locations'] ?? [];
        $sceneLocation = null;

        foreach ($locations as $location) {
            // Support multiple field names for scene assignment (matching ImageGenerationService)
            $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

            // Include location if:
            // - sceneIndex is null (no scene context) - use first available
            // - Empty appliedScenes array means "applies to ALL scenes" (per UI design)
            // - Location is specifically assigned to this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneLocation = $location;
                break;
            }
        }

        // Fall back to first location if no specific assignment
        if (!$sceneLocation && !empty($locations)) {
            $sceneLocation = $locations[0];
        }

        if (!$sceneLocation) {
            $environment['location'] = $this->extractLocationFromDescription($sceneDescription);
            return $environment;
        }

        $environment['location'] = $sceneLocation['name'] ?? '';
        $environment['background'] = $sceneLocation['description'] ?? '';
        $environment['details'] = $sceneLocation['details'] ?? $this->extractEnvironmentDetails($sceneDescription);
        $environment['atmosphere'] = $sceneLocation['atmosphere'] ?? '';
        $environment['time_of_day'] = $sceneLocation['timeOfDay'] ?? 'day';
        $environment['weather'] = $sceneLocation['weather'] ?? 'clear';

        // Include location state if available
        if (!empty($sceneLocation['sceneStates'][$sceneIndex])) {
            $state = $sceneLocation['sceneStates'][$sceneIndex];
            if (!empty($state['stateDescription'])) {
                $environment['details'] .= '. ' . $state['stateDescription'];
            }
        }

        return $environment;
    }

    /**
     * Build lighting description.
     */
    protected function buildLightingDescription(?array $styleBible, ?array $locationBible, ?int $sceneIndex): array
    {
        $lighting = [
            'lighting' => '',
            'mood' => '',
            'color_palette' => '',
        ];

        // Get time of day from location
        $timeOfDay = 'day';
        if ($locationBible && ($locationBible['enabled'] ?? false)) {
            $locations = $locationBible['locations'] ?? [];
            foreach ($locations as $location) {
                $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

                // Match location if no scene context (use first), empty assignment (applies to all), or specific match
                if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                    $timeOfDay = $location['timeOfDay'] ?? 'day';
                    break;
                }
            }
        }

        // Map time of day to lighting preset
        $lightingPreset = $this->getTimeOfDayLighting($timeOfDay);
        $lighting = array_merge($lighting, $lightingPreset);

        // Override with style bible if available
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            if (!empty($styleBible['atmosphere'])) {
                $lighting['mood'] = $styleBible['atmosphere'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $lighting['color_palette'] = $styleBible['colorGrade'];
            }
        }

        return $lighting;
    }

    /**
     * Get lighting preset based on time of day.
     */
    protected function getTimeOfDayLighting(string $timeOfDay): array
    {
        $mappings = [
            'dawn' => self::LIGHTING_PRESETS['golden_hour'],
            'morning' => self::LIGHTING_PRESETS['overcast_natural'],
            'day' => self::LIGHTING_PRESETS['overcast_natural'],
            'afternoon' => self::LIGHTING_PRESETS['overcast_natural'],
            'golden_hour' => self::LIGHTING_PRESETS['golden_hour'],
            'sunset' => self::LIGHTING_PRESETS['golden_hour'],
            'dusk' => self::LIGHTING_PRESETS['blue_hour'],
            'evening' => self::LIGHTING_PRESETS['blue_hour'],
            'night' => self::LIGHTING_PRESETS['neon_night'],
        ];

        return $mappings[$timeOfDay] ?? self::LIGHTING_PRESETS['overcast_natural'];
    }

    /**
     * Build the scene summary.
     */
    protected function buildSceneSummary(string $sceneDescription, array $subject, array $environment): string
    {
        $parts = [];

        // Start with scene type
        $parts[] = 'Cinematic photorealistic scene';

        // Add location context
        if (!empty($environment['location'])) {
            $parts[] = 'set in ' . $environment['location'];
        }

        // Add subject context
        if (!empty($subject['description'])) {
            $parts[] = 'featuring ' . $subject['description'];
        }

        // Add the visual description
        if (!empty($sceneDescription)) {
            $parts[] = $sceneDescription;
        }

        // Add atmosphere
        if (!empty($environment['atmosphere'])) {
            $parts[] = $environment['atmosphere'];
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Build technical specifications.
     */
    protected function buildTechnicalSpecs(array $options, array $template): array
    {
        $styleBible = $options['style_bible'] ?? null;

        $specs = [
            'quality' => implode(', ', $template['quality_keywords']),
            'style' => $template['prompt_type'] === 'photorealistic_cinematic'
                ? 'Realistic photography, cinematic film still, Hollywood quality'
                : 'High quality professional image',
            'focus' => 'Sharp focus on subject with cinematic depth of field, natural bokeh',
            'camera' => $template['global_rules']['camera_language'],
        ];

        // Override with style bible if available
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            if (!empty($styleBible['camera'])) {
                $specs['camera'] = $styleBible['camera'];
            }
            if (!empty($styleBible['visualDNA'])) {
                $specs['quality'] = $styleBible['visualDNA'] . ', ' . $specs['quality'];
            }
        }

        return $specs;
    }

    /**
     * Build negative prompt array.
     */
    protected function buildNegativePrompt(array $options, array $template): array
    {
        $negativePrompt = $template['negative_prompt'];

        // Add custom negative prompts from style bible
        $styleBible = $options['style_bible'] ?? null;
        if ($styleBible && !empty($styleBible['negativePrompt'])) {
            $customNegatives = array_map('trim', explode(',', $styleBible['negativePrompt']));
            $negativePrompt = array_merge($negativePrompt, $customNegatives);
        }

        // =================================================================
        // PHASE 1: DUPLICATE CHARACTER PREVENTION
        // =================================================================
        // Always add duplicate prevention negatives to avoid cloned characters
        $duplicatePreventionNegatives = [
            'duplicate characters',
            'cloned figures',
            'same person appearing twice',
            'multiple copies of same character',
            'twin copies',
            'mirrored duplicate',
            'repeated identical figure',
        ];
        $negativePrompt = array_merge($negativePrompt, $duplicatePreventionNegatives);

        // Shot-type specific negatives
        $shotType = $options['shot_type'] ?? null;
        if ($shotType) {
            $singleCharacterShots = ['close-up', 'extreme-close-up', 'medium-close', 'reaction', 'pov'];
            if (in_array($shotType, $singleCharacterShots)) {
                // For close shots, emphasize single subject
                $negativePrompt[] = 'multiple people in close-up';
                $negativePrompt[] = 'crowded frame';
            }
        }

        return array_unique($negativePrompt);
    }

    /**
     * Convert structured prompt to optimized string for API.
     */
    public function toPromptString(array $structuredPrompt): string
    {
        $parts = [];

        // Get visual mode to enforce realism upfront
        $visualMode = $structuredPrompt['meta_data']['visual_mode'] ?? 'cinematic-realistic';
        $promptType = $structuredPrompt['meta_data']['prompt_type'] ?? '';

        // CRITICAL: Add strong realism enforcement at the very start of prompt
        // This ensures the AI model prioritizes photorealism above all else
        if ($promptType === 'photorealistic_cinematic' || $visualMode === 'cinematic-realistic') {
            $parts[] = 'PHOTOREALISTIC ONLY - Ultra-realistic photograph, indistinguishable from real camera footage, NOT CGI, NOT illustration, NOT 3D render, NOT digital art';
        } elseif ($promptType === 'photorealistic_documentary' || $visualMode === 'documentary-realistic') {
            $parts[] = 'DOCUMENTARY PHOTOGRAPH - Real candid photograph, authentic moment captured, NOT staged, NOT CGI, NOT illustration';
        }

        // Opening with quality and style
        $techSpecs = $structuredPrompt['technical_specifications'] ?? [];
        $parts[] = $techSpecs['quality'] ?? '8K UHD, photorealistic';

        // Camera setup
        if (!empty($techSpecs['camera'])) {
            $parts[] = $techSpecs['camera'];
        }

        // Scene summary
        $creative = $structuredPrompt['creative_prompt'] ?? [];
        if (!empty($creative['scene_summary'])) {
            $parts[] = $creative['scene_summary'];
        }

        // SHOT COMPOSITION - Critical framing instructions for multi-shot sequences
        $composition = $creative['composition'] ?? [];
        if (!empty($composition['composition_instruction'])) {
            // Add composition as HIGH PRIORITY instruction
            $parts[] = '[FRAMING: ' . $composition['composition_instruction'] . ']';
        }
        if (!empty($composition['framing'])) {
            $parts[] = $composition['framing'];
        }
        if (!empty($composition['subject_scale'])) {
            $parts[] = $composition['subject_scale'];
        }
        if (!empty($composition['depth_of_field'])) {
            $parts[] = $composition['depth_of_field'];
        }

        // Subject details
        $subject = $creative['subject'] ?? [];
        if (!empty($subject['description'])) {
            $subjectParts = ['featuring ' . $subject['description']];
            if (!empty($subject['physical_details'])) {
                $subjectParts[] = $subject['physical_details'];
            }
            if (!empty($subject['expression'])) {
                $subjectParts[] = $subject['expression'];
            }
            if (!empty($subject['attire'])) {
                $subjectParts[] = 'wearing ' . $subject['attire'];
            }
            if (!empty($subject['pose'])) {
                $subjectParts[] = $subject['pose'];
            }
            if (!empty($subject['micro_action'])) {
                $subjectParts[] = $subject['micro_action'];
            }
            // =================================================================
            // PHASE 1: Include story action and body language
            // =================================================================
            if (!empty($subject['story_action'])) {
                $subjectParts[] = $subject['story_action'];
            }
            if (!empty($subject['body_language'])) {
                $subjectParts[] = $subject['body_language'];
            }
            // Include secondary characters (if multiple characters in scene)
            if (!empty($subject['secondary_subjects'])) {
                $subjectParts[] = 'also featuring ' . $subject['secondary_subjects'];
            }
            $parts[] = implode(', ', array_filter($subjectParts));
        }

        // =================================================================
        // PHASE 1: Character count instruction (duplicate prevention)
        // =================================================================
        if (!empty($subject['character_count_instruction'])) {
            $parts[] = $subject['character_count_instruction'];
        }

        // Environment - respect shot composition for background treatment
        $environment = $creative['environment'] ?? [];
        $backgroundTreatment = $composition['background_treatment'] ?? '';
        $shotType = $composition['shot_type'] ?? 'standard';

        // For close-ups and extreme close-ups, minimize environment details
        $isCloseShot = in_array($shotType, ['close-up', 'extreme-close-up', 'reaction', 'detail', 'insert']);

        if (!empty($environment['location']) || !empty($environment['background'])) {
            $envParts = [];

            // Always include location context, but frame it appropriately
            if (!empty($environment['location'])) {
                if ($isCloseShot) {
                    // For close shots, just mention location without detail
                    $envParts[] = 'location: ' . $environment['location'] . ' (blurred background)';
                } else {
                    $envParts[] = 'set in ' . $environment['location'];
                }
            }

            // Only add detailed background for wide/medium shots
            if (!$isCloseShot) {
                if (!empty($environment['background'])) {
                    $envParts[] = $environment['background'];
                }
                if (!empty($environment['details'])) {
                    $envParts[] = $environment['details'];
                }
            }

            // Add background treatment instruction if specified
            if (!empty($backgroundTreatment)) {
                $envParts[] = 'Background: ' . $backgroundTreatment;
            }

            $parts[] = implode(', ', array_filter($envParts));
        }

        // Lighting and atmosphere
        $lighting = $creative['lighting_and_atmosphere'] ?? [];
        if (!empty($lighting['lighting'])) {
            $parts[] = $lighting['lighting'];
        }
        if (!empty($lighting['color_palette'])) {
            $parts[] = $lighting['color_palette'];
        }
        if (!empty($lighting['mood'])) {
            $parts[] = $lighting['mood'] . ' mood';
        }

        // Authenticity markers
        if (!empty($creative['authenticity_markers'])) {
            $parts[] = $creative['authenticity_markers'];
        }

        // Focus and technical
        if (!empty($techSpecs['focus'])) {
            $parts[] = $techSpecs['focus'];
        }

        // Combine base prompt
        $basePrompt = implode('. ', array_filter($parts));

        // Add Style DNA for visual consistency enforcement
        $styleDNA = $creative['style_dna'] ?? '';
        if (!empty($styleDNA)) {
            $basePrompt .= "\n\n" . $styleDNA;
        }

        // Add Location DNA for environmental consistency enforcement
        $locationDNA = $creative['location_dna'] ?? '';
        if (!empty($locationDNA)) {
            $basePrompt .= "\n\n" . $locationDNA;
        }

        // Add Character DNA blocks for consistency enforcement
        $characterDNA = $creative['character_dna'] ?? [];
        if (!empty($characterDNA)) {
            $dnaSection = "\n\n" . implode("\n\n", $characterDNA);
            $basePrompt .= $dnaSection;
        }

        // CRITICAL: Final realism enforcement for photorealistic modes
        // This ensures the AI model doesn't drift into stylized/CGI territory
        if ($promptType === 'photorealistic_cinematic' || $visualMode === 'cinematic-realistic') {
            $basePrompt .= "\n\n[MANDATORY STYLE: This must look like a real photograph taken on a professional cinema camera. Real human skin with pores, real textures, real lighting. NO CGI, NO illustration, NO digital art, NO anime, NO cartoon styling whatsoever.]";
        } elseif ($promptType === 'photorealistic_documentary' || $visualMode === 'documentary-realistic') {
            $basePrompt .= "\n\n[MANDATORY STYLE: This must look like a real documentary photograph. Authentic, candid, natural. NO stylization, NO CGI effects.]";
        }

        return $basePrompt;
    }

    /**
     * Get negative prompt as string.
     */
    public function getNegativePromptString(array $structuredPrompt): string
    {
        $negatives = $structuredPrompt['negative_prompt'] ?? [];
        return implode(', ', $negatives);
    }

    /**
     * Get orientation from aspect ratio.
     */
    protected function getOrientation(string $aspectRatio): string
    {
        $parts = explode(':', $aspectRatio);
        if (count($parts) !== 2) return 'landscape';

        $width = (int) $parts[0];
        $height = (int) $parts[1];

        if ($width > $height) return 'landscape';
        if ($height > $width) return 'portrait';
        return 'square';
    }

    // =========================================================================
    // HELPER METHODS FOR EXTRACTING INFO FROM DESCRIPTIONS
    // =========================================================================

    protected function extractSubjectFromDescription(string $description): string
    {
        // Basic extraction - can be enhanced with NLP
        if (stripos($description, 'person') !== false ||
            stripos($description, 'man') !== false ||
            stripos($description, 'woman') !== false ||
            stripos($description, 'character') !== false) {
            return 'a person in the scene';
        }
        return '';
    }

    protected function extractLocationFromDescription(string $description): string
    {
        // Basic extraction - can be enhanced
        $locationKeywords = ['in', 'at', 'inside', 'outside', 'near', 'by the'];
        foreach ($locationKeywords as $keyword) {
            if (preg_match("/{$keyword}\s+(?:the\s+)?([^,.]+)/i", $description, $matches)) {
                return trim($matches[1]);
            }
        }
        return '';
    }

    protected function extractEnvironmentDetails(string $description): string
    {
        // Extract environmental details from description
        return '';
    }

    protected function extractPoseFromDescription(string $description): string
    {
        $poseKeywords = ['standing', 'sitting', 'walking', 'running', 'lying', 'crouching', 'leaning'];
        foreach ($poseKeywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                return $keyword;
            }
        }
        return '';
    }

    protected function extractMicroAction(string $description): string
    {
        // Look for action verbs
        $actionPatterns = [
            '/looking at ([^,.]+)/i',
            '/holding ([^,.]+)/i',
            '/touching ([^,.]+)/i',
            '/reaching for ([^,.]+)/i',
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return $matches[0];
            }
        }
        return '';
    }

    // =========================================================================
    // CHARACTER DNA METHODS - Hollywood-level consistency
    // =========================================================================

    /**
     * Build Character DNA blocks for all characters in a scene.
     * This ensures Hollywood-level visual consistency across shots.
     */
    protected function buildCharacterDNA(?array $characterBible, ?int $sceneIndex): array
    {
        if (!$characterBible || !($characterBible['enabled'] ?? false)) {
            return [];
        }

        $characters = $characterBible['characters'] ?? [];
        $dnaBlocks = [];

        foreach ($characters as $character) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];

            // Include if: no scene context, applies to all scenes, or matches this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $dna = $this->buildSingleCharacterDNA($character);
                if (!empty($dna)) {
                    $dnaBlocks[] = $dna;
                }
            }
        }

        return $dnaBlocks;
    }

    /**
     * Build DNA template for a single character.
     */
    protected function buildSingleCharacterDNA(array $character): string
    {
        $name = $character['name'] ?? 'Character';
        $parts = [];

        // Identity/Face section
        $identityParts = [];
        if (!empty($character['description'])) {
            $identityParts[] = $character['description'];
        }
        $identityParts[] = "Same skin tone, same complexion, same facial proportions";
        $identityParts[] = "Same body type and build";
        $parts[] = "IDENTITY: " . implode('. ', $identityParts);

        // Hair section - critical for visual consistency
        $hair = $character['hair'] ?? [];
        if (!empty(array_filter($hair))) {
            $hairParts = [];
            if (!empty($hair['color'])) $hairParts[] = $hair['color'];
            if (!empty($hair['length'])) $hairParts[] = $hair['length'];
            if (!empty($hair['style'])) $hairParts[] = $hair['style'];
            if (!empty($hair['texture'])) $hairParts[] = $hair['texture'] . ' texture';
            if (!empty($hairParts)) {
                $parts[] = "HAIR (EXACT MATCH): " . implode(', ', $hairParts);
            }
        }

        // Wardrobe section
        $wardrobe = $character['wardrobe'] ?? [];
        if (!empty(array_filter($wardrobe))) {
            $wardrobeParts = [];
            if (!empty($wardrobe['outfit'])) $wardrobeParts[] = $wardrobe['outfit'];
            if (!empty($wardrobe['colors'])) $wardrobeParts[] = "in " . $wardrobe['colors'];
            if (!empty($wardrobe['style'])) $wardrobeParts[] = $wardrobe['style'] . ' style';
            if (!empty($wardrobe['footwear'])) $wardrobeParts[] = $wardrobe['footwear'];
            if (!empty($wardrobeParts)) {
                $parts[] = "WARDROBE (EXACT MATCH): " . implode(', ', $wardrobeParts);
            }
        }

        // Makeup section
        $makeup = $character['makeup'] ?? [];
        if (!empty(array_filter($makeup))) {
            $makeupParts = [];
            if (!empty($makeup['style'])) $makeupParts[] = $makeup['style'];
            if (!empty($makeup['details'])) $makeupParts[] = $makeup['details'];
            if (!empty($makeupParts)) {
                $parts[] = "MAKEUP (EXACT MATCH): " . implode(', ', $makeupParts);
            }
        }

        // Accessories section
        $accessories = $character['accessories'] ?? [];
        if (!empty($accessories)) {
            $accessoryList = is_array($accessories) ? implode(', ', $accessories) : $accessories;
            if (!empty(trim($accessoryList))) {
                $parts[] = "ACCESSORIES (EXACT MATCH): " . $accessoryList;
            }
        }

        // Traits section - personality and physical characteristics
        $traits = $character['traits'] ?? [];
        if (!empty($traits)) {
            $traitList = is_array($traits) ? implode(', ', $traits) : $traits;
            if (!empty(trim($traitList))) {
                $parts[] = "TRAITS/CHARACTERISTICS: " . $traitList;
            }
        }

        // Default expression if specified
        if (!empty($character['defaultExpression'])) {
            $parts[] = "DEFAULT EXPRESSION: " . $character['defaultExpression'];
        }

        if (count($parts) <= 1) {
            return ''; // Only identity, no detailed DNA
        }

        return "CHARACTER DNA - {$name} (MUST MATCH EXACTLY):\n" . implode("\n", $parts);
    }

    // =========================================================================
    // STYLE DNA METHODS - Visual consistency enforcement
    // =========================================================================

    /**
     * Build Style DNA block for visual consistency enforcement.
     * This ensures consistent visual style across all generated images.
     */
    protected function buildStyleDNA(?array $styleBible): string
    {
        if (!$styleBible || !($styleBible['enabled'] ?? false)) {
            return '';
        }

        $parts = [];

        // Visual Style section
        if (!empty($styleBible['style'])) {
            $parts[] = "VISUAL STYLE: " . $styleBible['style'];
        }

        // Color Grading section
        if (!empty($styleBible['colorGrade'])) {
            $parts[] = "COLOR GRADE (CONSISTENT): " . $styleBible['colorGrade'];
        }

        // Lighting section - structured if available
        $lighting = $styleBible['lighting'] ?? [];
        if (!empty(array_filter($lighting))) {
            $lightingParts = [];
            if (!empty($lighting['setup'])) $lightingParts[] = $lighting['setup'];
            if (!empty($lighting['intensity'])) $lightingParts[] = $lighting['intensity'] . ' intensity';
            if (!empty($lighting['type'])) $lightingParts[] = $lighting['type'] . ' lighting';
            if (!empty($lighting['mood'])) $lightingParts[] = $lighting['mood'] . ' mood';
            if (!empty($lightingParts)) {
                $parts[] = "LIGHTING (CONSISTENT): " . implode(', ', $lightingParts);
            }
        }

        // Atmosphere section
        if (!empty($styleBible['atmosphere'])) {
            $parts[] = "ATMOSPHERE: " . $styleBible['atmosphere'];
        }

        // Camera Language section
        if (!empty($styleBible['camera'])) {
            $parts[] = "CAMERA: " . $styleBible['camera'];
        }

        // Visual DNA (quality keywords)
        if (!empty($styleBible['visualDNA'])) {
            $parts[] = "QUALITY MARKERS: " . $styleBible['visualDNA'];
        }

        if (empty($parts)) {
            return '';
        }

        return "STYLE DNA - VISUAL CONSISTENCY ANCHOR:\n" . implode("\n", $parts);
    }

    // =========================================================================
    // LOCATION DNA METHODS - Environmental consistency enforcement
    // =========================================================================

    /**
     * Build Location DNA blocks for environmental consistency.
     * This ensures consistent location appearance across shots.
     */
    protected function buildLocationDNA(?array $locationBible, ?int $sceneIndex): string
    {
        if (!$locationBible || !($locationBible['enabled'] ?? false)) {
            return '';
        }

        $locations = $locationBible['locations'] ?? [];
        if (empty($locations)) {
            return '';
        }

        // Find location for this scene
        $sceneLocation = null;
        foreach ($locations as $location) {
            $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

            // Include if: no scene context, applies to all scenes, or matches this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneLocation = $location;
                break;
            }
        }

        if (!$sceneLocation) {
            return '';
        }

        return $this->buildSingleLocationDNA($sceneLocation, $sceneIndex);
    }

    /**
     * Build DNA template for a single location.
     */
    protected function buildSingleLocationDNA(array $location, ?int $sceneIndex): string
    {
        $name = $location['name'] ?? 'Location';
        $parts = [];

        // Environment section
        if (!empty($location['description'])) {
            $parts[] = "ENVIRONMENT: " . $location['description'];
        }

        // Type section
        if (!empty($location['type'])) {
            $typeMap = [
                'interior' => 'Interior space',
                'exterior' => 'Exterior/outdoor location',
                'abstract' => 'Abstract/stylized environment',
            ];
            $parts[] = "TYPE: " . ($typeMap[$location['type']] ?? $location['type']);
        }

        // Time and Weather - critical for visual consistency
        $timeWeather = [];
        if (!empty($location['timeOfDay'])) {
            $timeMap = [
                'day' => 'Daytime',
                'night' => 'Nighttime',
                'dawn' => 'Dawn/early morning',
                'dusk' => 'Dusk/twilight',
                'golden_hour' => 'Golden hour',
            ];
            $timeWeather[] = $timeMap[$location['timeOfDay']] ?? $location['timeOfDay'];
        }
        if (!empty($location['weather'])) {
            $weatherMap = [
                'clear' => 'clear sky',
                'cloudy' => 'overcast/cloudy',
                'rainy' => 'rainy conditions',
                'foggy' => 'foggy/misty atmosphere',
                'stormy' => 'stormy weather',
                'snowy' => 'snowy conditions',
            ];
            $timeWeather[] = $weatherMap[$location['weather']] ?? $location['weather'];
        }
        if (!empty($timeWeather)) {
            $parts[] = "TIME/WEATHER (CONSISTENT): " . implode(', ', $timeWeather);
        }

        // Atmosphere
        if (!empty($location['atmosphere'])) {
            $parts[] = "ATMOSPHERE: " . $location['atmosphere'];
        }

        // Mood
        if (!empty($location['mood'])) {
            $parts[] = "MOOD: " . $location['mood'];
        }

        // Lighting style if specified
        if (!empty($location['lightingStyle'])) {
            $parts[] = "LIGHTING: " . $location['lightingStyle'];
        }

        // Scene state if available (support both old and new field names for backwards compatibility)
        if ($sceneIndex !== null && !empty($location['stateChanges'])) {
            foreach ($location['stateChanges'] as $stateChange) {
                // Support both 'sceneIndex' (new) and 'scene' (old) field names
                $changeSceneIndex = $stateChange['sceneIndex'] ?? $stateChange['scene'] ?? null;
                // Support both 'stateDescription' (new) and 'state' (old) field names
                $changeDescription = $stateChange['stateDescription'] ?? $stateChange['state'] ?? '';

                if ($changeSceneIndex === $sceneIndex && !empty($changeDescription)) {
                    $parts[] = "CURRENT STATE: " . $changeDescription;
                    break;
                }
            }
        }

        if (count($parts) <= 1) {
            return '';
        }

        return "LOCATION DNA - {$name} (MAINTAIN CONSISTENCY):\n" . implode("\n", $parts);
    }

    // =========================================================================
    // SHOT COMPOSITION - Framing & Composition for Multi-Shot Sequences
    // =========================================================================

    /**
     * Build shot composition instructions based on shot type.
     * This creates visual variety between shots in a multi-shot sequence.
     */
    protected function buildShotComposition(?string $shotType, ?int $shotIndex, ?int $totalShots, bool $isMultiShot): array
    {
        // Default composition if not a multi-shot context
        if (!$isMultiShot || !$shotType) {
            return [
                'shot_type' => 'standard',
                'framing' => '',
                'depth_of_field' => '',
                'subject_scale' => '',
                'background_treatment' => '',
                'composition_instruction' => '',
            ];
        }

        // Detailed framing instructions per shot type - these define WHAT to show
        $compositions = [
            'establishing' => [
                'shot_type' => 'establishing',
                'framing' => 'ULTRA-WIDE establishing shot, environment-dominant composition',
                'depth_of_field' => 'Deep focus - everything sharp from foreground to background',
                'subject_scale' => 'Characters appear SMALL in frame (under 20% of frame height), environment takes 80%+ of composition',
                'background_treatment' => 'Background is the PRIMARY subject - show full location scope',
                'composition_instruction' => 'CRITICAL: This is an ENVIRONMENT shot. Show the ENTIRE location with tiny figures. Characters should be barely visible in the distance. Emphasize scale and atmosphere.',
            ],
            'wide' => [
                'shot_type' => 'wide',
                'framing' => 'Wide shot showing full scene context with spatial relationships',
                'depth_of_field' => 'Deep focus - environment and characters both visible',
                'subject_scale' => 'Characters appear at 30-40% of frame height, showing full bodies with significant environment',
                'background_treatment' => 'Environment visible and in focus, providing context',
                'composition_instruction' => 'Wide framing: Full body characters visible with significant environment. Show spatial relationships between characters and location.',
            ],
            'medium' => [
                'shot_type' => 'medium',
                'framing' => 'Medium shot from waist/hips up, balanced subject-environment ratio',
                'depth_of_field' => 'Moderate depth - subject sharp, background slightly soft',
                'subject_scale' => 'Characters fill 50-60% of frame height, waist-up framing',
                'background_treatment' => 'Background visible but secondary, provides context without distraction',
                'composition_instruction' => 'Medium framing: Waist-up composition showing upper body language and gestures. Subject dominates but environment remains visible.',
            ],
            'medium-close' => [
                'shot_type' => 'medium-close',
                'framing' => 'Medium close-up from chest up, intimate framing for dialogue',
                'depth_of_field' => 'Shallow depth - face sharp, background noticeably soft',
                'subject_scale' => 'Character fills 65-75% of frame height, chest-up framing',
                'background_treatment' => 'Background soft and blurred, minimal distraction',
                'composition_instruction' => 'Medium close-up: Chest-up framing focusing on facial expression and upper body. Background should be noticeably out of focus.',
            ],
            'close-up' => [
                'shot_type' => 'close-up',
                'framing' => 'Close-up of face filling the frame, emotional intensity',
                'depth_of_field' => 'Very shallow - eyes sharp, ears may be soft, background completely blurred',
                'subject_scale' => 'Face fills 80-90% of frame, head and shoulders framing',
                'background_treatment' => 'Background is COMPLETELY BLURRED bokeh, unrecognizable',
                'composition_instruction' => 'CRITICAL: CLOSE-UP means FACE FILLS THE FRAME. Show only head and shoulders. Background must be heavily blurred bokeh. Focus on eyes and emotional expression.',
            ],
            'extreme-close-up' => [
                'shot_type' => 'extreme-close-up',
                'framing' => 'Extreme close-up on specific detail - eyes, hands, or crucial object',
                'depth_of_field' => 'Extremely shallow - only focal point sharp',
                'subject_scale' => 'Detail fills 90%+ of frame - eyes only, or hands only, or single object',
                'background_treatment' => 'No recognizable background - abstract blur or single color',
                'composition_instruction' => 'EXTREME CLOSE-UP: Show ONLY the detail - eyes filling frame, OR hands/fingers, OR specific object. Nothing else visible. Macro-style isolation.',
            ],
            'reaction' => [
                'shot_type' => 'reaction',
                'framing' => 'Reaction shot focused on facial response to off-screen action',
                'depth_of_field' => 'Shallow depth - face sharp, background soft',
                'subject_scale' => 'Face fills 70-80% of frame, focused on emotional response',
                'background_treatment' => 'Background blurred, attention on expression',
                'composition_instruction' => 'Reaction shot: Focus ENTIRELY on facial expression showing emotional response. Subject reacting to something off-screen. Clear emotional read.',
            ],
            'detail' => [
                'shot_type' => 'detail',
                'framing' => 'Insert/detail shot of specific object or action',
                'depth_of_field' => 'Selective focus on the detail only',
                'subject_scale' => 'Object/detail fills most of frame',
                'background_treatment' => 'Minimal or no background, isolated subject',
                'composition_instruction' => 'DETAIL/INSERT: Show a SPECIFIC OBJECT or DETAIL - hands on keyboard, a document, a weapon, etc. NOT a face. NOT full scene. One specific element isolated.',
            ],
            'over-shoulder' => [
                'shot_type' => 'over-shoulder',
                'framing' => 'Over-the-shoulder shot showing conversation perspective',
                'depth_of_field' => 'Foreground shoulder soft, facing character sharp',
                'subject_scale' => 'Foreground character blurred shoulder/back, facing character at medium-close',
                'background_treatment' => 'Background visible through conversation space',
                'composition_instruction' => 'Over-shoulder: Show back/shoulder of one character in soft foreground, with another character in focus facing camera. Classic dialogue framing.',
            ],
            'pov' => [
                'shot_type' => 'pov',
                'framing' => 'Point-of-view shot from character\'s perspective',
                'depth_of_field' => 'Natural vision depth - focus on what character is looking at',
                'subject_scale' => 'Shows what character sees, not the character themselves',
                'background_treatment' => 'Environment as character would see it',
                'composition_instruction' => 'POV SHOT: Show what the CHARACTER SEES - their hands may be visible at bottom of frame, but NO face of the POV character. First-person perspective.',
            ],
            'insert' => [
                'shot_type' => 'insert',
                'framing' => 'Insert shot of specific narrative element',
                'depth_of_field' => 'Tight focus on the insert element',
                'subject_scale' => 'Specific element fills frame',
                'background_treatment' => 'Minimal, undistracting',
                'composition_instruction' => 'INSERT: Specific object, text, screen, hand action - NOT a character portrait. Show the THING that matters to the story.',
            ],
        ];

        // Get composition for this shot type, fallback to medium
        $comp = $compositions[$shotType] ?? $compositions['medium'];

        // Add sequence context
        if ($shotIndex !== null && $totalShots !== null) {
            $position = $shotIndex + 1;
            $comp['sequence_context'] = "Shot {$position} of {$totalShots} in sequence";
        }

        return $comp;
    }

    // =========================================================================
    // PHASE 1: CHARACTER COUNT & STORY ACTION HELPERS
    // =========================================================================

    /**
     * Build character count instruction to prevent duplicates.
     * This explicitly tells the AI how many distinct characters should appear.
     */
    protected function buildCharacterCountInstruction(int $count, array $names): string
    {
        if ($count <= 0) {
            return '';
        }

        if ($count === 1) {
            $name = $names[0] ?? 'the character';
            return "IMPORTANT: Exactly ONE person in this image - {$name}. Do NOT duplicate this character. Single figure only.";
        }

        if ($count === 2) {
            return "IMPORTANT: Exactly TWO distinct people - " . implode(' and ', $names) . ". Each person is UNIQUE - do NOT duplicate either character.";
        }

        $nameList = implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
        return "IMPORTANT: Exactly {$count} distinct people - {$nameList}. Each person is UNIQUE with different appearance - no duplicates or clones.";
    }

    /**
     * Detect character count from scene description when no Bible is available.
     */
    protected function detectCharacterCountFromDescription(string $description): int
    {
        $desc = strtolower($description);

        // Check for explicit numbers
        if (preg_match('/\b(two|2)\s+(people|persons|characters|figures|men|women)/i', $desc)) {
            return 2;
        }
        if (preg_match('/\b(three|3)\s+(people|persons|characters|figures)/i', $desc)) {
            return 3;
        }
        if (preg_match('/\b(group|crowd|army|soldiers|warriors)\b/i', $desc)) {
            return 5; // Generic "many"
        }

        // Check for singular indicators
        $singularKeywords = ['alone', 'solitary', 'single', 'one person', 'a man', 'a woman', 'the man', 'the woman'];
        foreach ($singularKeywords as $keyword) {
            if (str_contains($desc, $keyword)) {
                return 1;
            }
        }

        // Default to 1 if a person is mentioned
        if (preg_match('/\b(person|man|woman|character|figure|he|she)\b/i', $desc)) {
            return 1;
        }

        return 0; // No people detected
    }

    /**
     * Extract the main story action from scene description.
     * This captures WHAT is happening in the scene narratively.
     */
    protected function extractStoryAction(string $description): string
    {
        // Look for strong action verbs and their objects
        $actionPatterns = [
            // Dramatic actions
            '/\b(roars?|screams?|shouts?|bellows?|cries out|yells?)\b[^.]*\./i',
            '/\b(challenges?|confronts?|faces?|defies?)\b[^.]*\./i',
            '/\b(raises?|lifts?|holds up|brandishes?)\b[^.]*\./i',
            '/\b(strikes?|hits?|punches?|attacks?|fights?)\b[^.]*\./i',
            '/\b(runs?|charges?|rushes?|sprints?|flees?)\b[^.]*\./i',
            '/\b(falls?|collapses?|drops?|stumbles?)\b[^.]*\./i',
            '/\b(embraces?|hugs?|kisses?|holds?)\b[^.]*\./i',
            '/\b(speaks?|says?|tells?|announces?|declares?)\b[^.]*\./i',
            '/\b(looks?|stares?|gazes?|watches?|observes?)\b[^.]*\./i',
            '/\b(walks?|strides?|marches?|approaches?|enters?)\b[^.]*\./i',
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return trim($matches[0], '. ');
            }
        }

        // Fall back: extract any sentence with an action verb
        $sentences = preg_split('/[.!?]+/', $description);
        $actionVerbs = ['stand', 'sit', 'walk', 'run', 'look', 'hold', 'raise', 'reach', 'turn', 'move', 'speak', 'open', 'close'];

        foreach ($sentences as $sentence) {
            foreach ($actionVerbs as $verb) {
                if (preg_match('/\b' . $verb . '(s|ing|ed)?\b/i', $sentence)) {
                    return trim($sentence);
                }
            }
        }

        return '';
    }

    /**
     * Extract body language cues from scene description.
     */
    protected function extractBodyLanguage(string $description): string
    {
        $bodyLanguageParts = [];

        // Posture indicators
        $posturePatterns = [
            '/\b(arms?\s+(spread|raised|crossed|outstretched|at sides|folded))\b/i',
            '/\b(shoulders?\s+(back|slumped|tense|relaxed|squared))\b/i',
            '/\b(chin\s+(raised|lowered|tucked))\b/i',
            '/\b(head\s+(bowed|raised|tilted|turned))\b/i',
            '/\b(hands?\s+(clenched|open|raised|on hips))\b/i',
            '/\b(fists?\s+(clenched|raised|pounding))\b/i',
            '/\b(chest\s+(puffed|heaving))\b/i',
            '/\b(stance\s+\w+)\b/i',
        ];

        foreach ($posturePatterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $bodyLanguageParts[] = $matches[0];
            }
        }

        // Emotional body language
        $emotionalPatterns = [
            'aggressive' => ['towering', 'looming', 'intimidating', 'dominant'],
            'defensive' => ['cowering', 'shrinking', 'protective'],
            'confident' => ['proud', 'upright', 'commanding'],
            'fearful' => ['trembling', 'shaking', 'quivering'],
        ];

        foreach ($emotionalPatterns as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    $bodyLanguageParts[] = "{$emotion} posture";
                    break;
                }
            }
        }

        return implode(', ', array_unique($bodyLanguageParts));
    }
}
