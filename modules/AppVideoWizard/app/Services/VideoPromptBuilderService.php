<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Models\VwGenrePreset;
use Modules\AppVideoWizard\Services\VideoTemporalService;
use Modules\AppVideoWizard\Services\MicroMovementService;
use Modules\AppVideoWizard\Services\CharacterDynamicsService;
use Modules\AppVideoWizard\Services\CharacterPathService;
use Modules\AppVideoWizard\Services\TransitionVocabulary;

/**
 * VideoPromptBuilderService - Builds Hollywood-quality video animation prompts.
 *
 * Implements the professional Hollywood formula:
 * [Camera Shot + Motion] + [Subject + Detailed Action] + [Environment] + [Lighting] + [Cinematic Style]
 *
 * Sources:
 * - Runway Text to Video Prompting Guide
 * - Video Prompt Guide
 * - Sora 2 Best Practices
 * - Higgsfield Testing Results
 *
 * Integrates with:
 * - CameraMovementService for intelligent movement selection
 * - ShotIntelligenceService for shot context
 * - GenrePresets for style guidance
 * - PromptExpanderService for AI-powered enhancement
 */
class VideoPromptBuilderService
{
    protected CameraMovementService $cameraMovementService;
    protected VideoTemporalService $videoTemporalService;
    protected MicroMovementService $microMovementService;
    protected CharacterDynamicsService $characterDynamicsService;
    protected CharacterPathService $characterPathService;
    protected TransitionVocabulary $transitionVocabulary;

    /**
     * Video prompt formula components (Hollywood formula order).
     */
    public const PROMPT_COMPONENTS = [
        'camera_shot',     // Shot type and framing (FIRST - establishes visual frame)
        'camera_movement', // How the camera moves
        'subject',         // Who/what is in the frame
        'action',          // CRITICAL: What the subject is doing (verb-based)
        'environment',     // Where the action takes place
        'lighting',        // Lighting conditions and mood
        'atmosphere',      // Environmental mood/atmosphere
        'style',           // Visual style and quality markers (LAST - final polish)
    ];

    /**
     * Quality markers for different output levels (Hollywood-enhanced).
     */
    public const QUALITY_MARKERS = [
        'cinematic' => 'cinematic quality, professional cinematography, 4K resolution, shallow depth of field, film grain, anamorphic lens characteristics',
        'broadcast' => 'broadcast quality, clean footage, HD resolution, natural color grading',
        'social' => 'dynamic visuals, engaging motion, optimized for social media, punchy colors',
        'premium' => '8K cinematic, IMAX quality, masterful cinematography, film-like motion blur, reference-quality color science',
        'documentary' => 'documentary realism, naturalistic lighting, authentic feel, observational style',
        'commercial' => 'polished commercial look, vibrant colors, clean compositions, product-quality finish',
    ];

    /**
     * Motion intensity descriptors with Hollywood terminology.
     */
    public const MOTION_INTENSITY = [
        'subtle' => ['slow', 'gentle', 'smooth', 'gradual', 'soft', 'imperceptible', 'whisper'],
        'moderate' => ['steady', 'controlled', 'balanced', 'fluid', 'natural', 'measured', 'deliberate'],
        'dynamic' => ['energetic', 'active', 'lively', 'engaging', 'swift', 'purposeful', 'driven'],
        'intense' => ['dramatic', 'powerful', 'rapid', 'impactful', 'bold', 'explosive', 'visceral'],
    ];

    /**
     * Hollywood verb library for subject actions (CRITICAL for video quality).
     */
    public const ACTION_VERBS = [
        'establishing' => [
            'emerges into', 'surveys', 'arrives at', 'stands overlooking', 'awaits',
            'gazes across', 'overlooks', 'enters', 'approaches slowly', 'observes quietly',
        ],
        'wide' => [
            'strides through', 'moves purposefully', 'navigates', 'traverses', 'journeys across',
            'walks with determination', 'runs toward', 'advances steadily', 'retreats from', 'paces anxiously',
        ],
        'medium' => [
            'gestures expressively', 'speaks with conviction', 'listens intently', 'reacts visibly', 'considers deeply',
            'turns to face', 'leans forward with interest', 'steps back cautiously', 'reaches toward', 'holds firmly',
        ],
        'close-up' => [
            'reveals inner emotion', 'shows quiet determination', 'expresses wordlessly', 'conveys through eyes',
            'furrows brow in thought', 'narrows eyes suspiciously', 'parts lips to speak', 'swallows hard', 'blinks slowly',
        ],
        'reaction' => [
            'realizes suddenly', 'processes the moment', 'absorbs the impact', 'comprehends fully', 'registers shock',
            'recoils instinctively', 'softens visibly', 'hardens with resolve', 'transforms emotionally', 'shifts internally',
        ],
        'action' => [
            'strikes with precision', 'dodges swiftly', 'lunges forward', 'blocks powerfully', 'parries expertly',
            'spins gracefully', 'leaps into action', 'ducks instinctively', 'rolls to safety', 'charges ahead',
        ],
    ];

    /**
     * Cinematic color grading presets.
     */
    public const COLOR_GRADING = [
        'teal-orange' => 'teal shadows with warm orange highlights, complementary color split',
        'bleach-bypass' => 'desaturated with crushed blacks, bleach bypass look',
        'warm-vintage' => 'warm golden tones, lifted blacks, vintage film aesthetic',
        'cool-modern' => 'cool blue undertones, clean whites, modern clinical feel',
        'high-contrast' => 'deep blacks, bright highlights, dramatic contrast ratio',
        'pastel-soft' => 'soft pastel tones, reduced contrast, dreamy quality',
        'neon-noir' => 'deep shadows with neon color accents, urban night aesthetic',
        'natural' => 'true-to-life colors, balanced exposure, naturalistic grade',
    ];

    public function __construct(
        CameraMovementService $cameraMovementService,
        VideoTemporalService $videoTemporalService,
        MicroMovementService $microMovementService,
        CharacterDynamicsService $characterDynamicsService,
        CharacterPathService $characterPathService,
        TransitionVocabulary $transitionVocabulary
    ) {
        $this->cameraMovementService = $cameraMovementService;
        $this->videoTemporalService = $videoTemporalService;
        $this->microMovementService = $microMovementService;
        $this->characterDynamicsService = $characterDynamicsService;
        $this->characterPathService = $characterPathService;
        $this->transitionVocabulary = $transitionVocabulary;
    }

    /**
     * Build a complete video animation prompt.
     *
     * @param array $shot Shot data with type, duration, subjectAction, etc.
     * @param array $context Scene context (genre, mood, lighting, characters, etc.)
     * @return array Built prompt with components and final string
     */
    public function buildPrompt(array $shot, array $context = []): array
    {
        try {
            // Extract shot data
            $shotType = $shot['type'] ?? 'medium-shot';
            $duration = $shot['duration'] ?? 6;
            $subjectAction = $shot['subjectAction'] ?? $shot['action'] ?? '';
            $emotion = $shot['emotion'] ?? $context['mood'] ?? 'neutral';
            $needsLipSync = $shot['needsLipSync'] ?? false;

            // Get genre preset for style guidance
            $genrePreset = $this->getGenrePreset($context['genre'] ?? 'cinematic');

            // Build components
            $components = [
                'style' => $this->buildStyleComponent($context, $genrePreset),
                'subject' => $this->buildSubjectComponent($shot, $context),
                'action' => $this->buildActionComponent($subjectAction, $emotion, $needsLipSync),
                'camera_movement' => $this->buildCameraMovementComponent($shot, $context),
                'lighting' => $this->buildLightingComponent($context, $genrePreset),
                'atmosphere' => $this->buildAtmosphereComponent($context, $genrePreset),
            ];

            // Combine into final prompt string
            $promptString = $this->combineComponents($components, $context);

            // Add quality markers based on output level
            $qualityLevel = $context['qualityLevel'] ?? 'cinematic';
            $promptString = $this->addQualityMarkers($promptString, $qualityLevel);

            // Apply negative guidance for common video issues
            $negativeGuidance = $this->getNegativeGuidance($context);

            Log::info('VideoPromptBuilderService: Built video prompt', [
                'shot_type' => $shotType,
                'duration' => $duration,
                'components_count' => count(array_filter($components)),
                'prompt_length' => strlen($promptString),
            ]);

            return [
                'success' => true,
                'prompt' => $promptString,
                'components' => $components,
                'negativeGuidance' => $negativeGuidance,
                'metadata' => [
                    'shotType' => $shotType,
                    'duration' => $duration,
                    'emotion' => $emotion,
                    'qualityLevel' => $qualityLevel,
                    'needsLipSync' => $needsLipSync,
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('VideoPromptBuilderService: Error building prompt', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'prompt' => $this->getFallbackPrompt($shot, $context),
            ];
        }
    }

    /**
     * Build style component based on genre and quality preferences.
     */
    protected function buildStyleComponent(array $context, ?object $genrePreset): string
    {
        $parts = [];

        // Base visual style
        if ($genrePreset && !empty($genrePreset->style)) {
            $parts[] = $genrePreset->style;
        } else {
            $parts[] = 'cinematic photorealistic';
        }

        // Color grading from genre
        if ($genrePreset && !empty($genrePreset->color_grade)) {
            $parts[] = $genrePreset->color_grade;
        }

        // Era/period styling if specified
        if (!empty($context['era'])) {
            $parts[] = "{$context['era']} period aesthetic";
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Build subject component describing who/what is in frame.
     */
    protected function buildSubjectComponent(array $shot, array $context): string
    {
        $parts = [];

        // Character information
        if (!empty($shot['characters'])) {
            $characters = is_array($shot['characters']) ? $shot['characters'] : [$shot['characters']];
            $count = count($characters);

            if ($count === 1) {
                $parts[] = "single person, {$characters[0]}";
            } elseif ($count === 2) {
                $parts[] = "two people, " . implode(' and ', $characters);
            } else {
                $parts[] = "group of {$count} people";
            }
        }

        // Shot framing description
        $shotType = $shot['type'] ?? 'medium-shot';
        $framingDesc = $this->getShotFramingDescription($shotType);
        if ($framingDesc) {
            $parts[] = $framingDesc;
        }

        // Subject state/emotion
        if (!empty($shot['subjectState'])) {
            $parts[] = $shot['subjectState'];
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Build action component describing movement and activity.
     * CRITICAL: This is the most important component for video quality.
     * AI video models NEED specific verb-based action descriptions.
     */
    protected function buildActionComponent(string $subjectAction, string $emotion, bool $needsLipSync): string
    {
        $parts = [];

        // Main subject action - enhance with Hollywood verbs if too basic
        if (!empty($subjectAction)) {
            $enhancedAction = $this->enhanceWithHollywoodVerbs($subjectAction);
            $parts[] = $enhancedAction;
        }

        // Emotional context for action with body language
        $emotionDescriptor = $this->getEmotionDescriptor($emotion);
        $bodyLanguage = $this->getBodyLanguageForEmotion($emotion);
        if ($emotionDescriptor && strpos($subjectAction, $emotionDescriptor) === false) {
            $parts[] = "with {$emotionDescriptor} demeanor";
        }
        if ($bodyLanguage && !str_contains(strtolower($subjectAction), $bodyLanguage)) {
            $parts[] = $bodyLanguage;
        }

        // Lip-sync specific guidance with realistic detail
        if ($needsLipSync) {
            $parts[] = 'speaking with natural lip movement and micro-expressions';
            $parts[] = 'eyes alive with thought between words';
        }

        // Add subtle life motion (breathing, blinking) for close shots
        if (str_contains(strtolower($subjectAction), 'close') || str_contains(strtolower($subjectAction), 'face')) {
            $parts[] = 'subtle breathing movement, natural eye motion';
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Enhance basic action with Hollywood-quality verbs.
     */
    protected function enhanceWithHollywoodVerbs(string $action): string
    {
        $action = trim($action);

        // Skip if already has strong verbs
        $strongVerbs = ['strides', 'emerges', 'surveys', 'gestures', 'reveals', 'strikes', 'lunges', 'dodges'];
        foreach ($strongVerbs as $verb) {
            if (str_contains(strtolower($action), $verb)) {
                return $action;
            }
        }

        // Replace weak verbs with strong Hollywood verbs
        $weakToStrong = [
            '/\bis\s+walking\b/i' => 'strides purposefully',
            '/\bwalks\b/i' => 'moves with deliberate purpose',
            '/\bis\s+standing\b/i' => 'stands with commanding presence',
            '/\bstands\b/i' => 'holds position with quiet intensity',
            '/\bis\s+looking\b/i' => 'gazes with focused attention',
            '/\blooks\b/i' => 'surveys the scene',
            '/\bis\s+running\b/i' => 'charges forward with urgency',
            '/\bruns\b/i' => 'moves swiftly',
            '/\bis\s+sitting\b/i' => 'sits with composed stillness',
            '/\bsits\b/i' => 'settles into position',
            '/\bis\s+talking\b/i' => 'speaks with conviction',
            '/\btalks\b/i' => 'communicates intently',
        ];

        foreach ($weakToStrong as $pattern => $replacement) {
            $action = preg_replace($pattern, $replacement, $action);
        }

        return $action;
    }

    /**
     * Get body language description for emotion.
     */
    protected function getBodyLanguageForEmotion(string $emotion): string
    {
        $bodyLanguage = [
            'happy' => 'open posture, relaxed shoulders',
            'sad' => 'shoulders slightly hunched, gaze downward',
            'angry' => 'tense muscles, squared shoulders',
            'fearful' => 'body slightly contracted, alert stance',
            'surprised' => 'body pulling back slightly, widened stance',
            'neutral' => 'balanced centered posture',
            'tense' => 'coiled tension in every muscle',
            'romantic' => 'body angled toward the other, soft openness',
            'mysterious' => 'guarded posture, controlled movements',
            'determined' => 'forward lean, locked jaw',
            'contemplative' => 'stillness with subtle weight shifts',
        ];

        return $bodyLanguage[$emotion] ?? '';
    }

    /**
     * Build camera movement component using CameraMovementService.
     */
    protected function buildCameraMovementComponent(array $shot, array $context): string
    {
        // Check if motion intelligence is enabled
        $motionEnabled = VwSetting::getValue('motion_intelligence_enabled', true);

        if (!$motionEnabled) {
            // Use simple movement description from shot data
            return $shot['cameraMovement'] ?? 'static camera';
        }

        // Use CameraMovementService for intelligent movement selection
        $shotType = $shot['type'] ?? 'medium-shot';
        $emotion = $shot['emotion'] ?? $context['mood'] ?? 'neutral';
        $intensity = $context['intensity'] ?? VwSetting::getValue('default_movement_intensity', 'moderate');

        // Get recommended movement
        $movement = $this->cameraMovementService->getRecommendedMovement($shotType, $emotion, $intensity);

        if (!$movement) {
            return $shot['cameraMovement'] ?? 'smooth camera movement';
        }

        // Check for movement stacking
        $stackingEnabled = VwSetting::getValue('movement_stacking_enabled', true);
        $secondaryMovement = null;

        if ($stackingEnabled && !empty($shot['secondaryMovement'])) {
            $secondaryMovement = $shot['secondaryMovement'];
        }

        // Build the movement prompt
        return $this->cameraMovementService->buildMovementPrompt(
            $movement['slug'],
            $secondaryMovement,
            $intensity
        );
    }

    /**
     * Build lighting component from context and genre preset.
     */
    protected function buildLightingComponent(array $context, ?object $genrePreset): string
    {
        $parts = [];

        // Genre-specific lighting
        if ($genrePreset && !empty($genrePreset->lighting)) {
            $parts[] = $genrePreset->lighting;
        }

        // Time of day lighting
        if (!empty($context['timeOfDay'])) {
            $timeDescriptor = $this->getTimeOfDayLighting($context['timeOfDay']);
            if ($timeDescriptor) {
                $parts[] = $timeDescriptor;
            }
        }

        // Location-based lighting
        if (!empty($context['locationType'])) {
            $locationLighting = $this->getLocationLighting($context['locationType']);
            if ($locationLighting) {
                $parts[] = $locationLighting;
            }
        }

        // Mood-based lighting adjustment
        if (!empty($context['mood'])) {
            $moodLighting = $this->getMoodLighting($context['mood']);
            if ($moodLighting && empty($parts)) {
                $parts[] = $moodLighting;
            }
        }

        return implode(', ', array_filter($parts)) ?: 'natural lighting';
    }

    /**
     * Build atmosphere component for environmental mood.
     */
    protected function buildAtmosphereComponent(array $context, ?object $genrePreset): string
    {
        $parts = [];

        // Genre atmosphere
        if ($genrePreset && !empty($genrePreset->atmosphere)) {
            $parts[] = $genrePreset->atmosphere;
        }

        // Weather effects if applicable
        if (!empty($context['weather'])) {
            $parts[] = $context['weather'];
        }

        // Environmental particles/effects
        if (!empty($context['effects'])) {
            $parts[] = $context['effects'];
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Combine all components into a single prompt string.
     */
    protected function combineComponents(array $components, array $context): string
    {
        // Filter out empty components
        $validComponents = array_filter($components);

        // Build prompt following Higgsfield formula order
        $orderedParts = [];

        // 1. Style first (sets visual foundation)
        if (!empty($components['style'])) {
            $orderedParts[] = $components['style'];
        }

        // 2. Subject (who/what)
        if (!empty($components['subject'])) {
            $orderedParts[] = $components['subject'];
        }

        // 3. Action (what they're doing)
        if (!empty($components['action'])) {
            $orderedParts[] = $components['action'];
        }

        // 4. Camera movement (how we see it)
        if (!empty($components['camera_movement'])) {
            $orderedParts[] = $components['camera_movement'];
        }

        // 5. Lighting (how it's lit)
        if (!empty($components['lighting'])) {
            $orderedParts[] = $components['lighting'];
        }

        // 6. Atmosphere (environmental feel)
        if (!empty($components['atmosphere'])) {
            $orderedParts[] = $components['atmosphere'];
        }

        return implode('. ', $orderedParts);
    }

    /**
     * Add quality markers to the prompt.
     */
    protected function addQualityMarkers(string $prompt, string $qualityLevel): string
    {
        $markers = self::QUALITY_MARKERS[$qualityLevel] ?? self::QUALITY_MARKERS['cinematic'];
        return $prompt . '. ' . $markers;
    }

    /**
     * Get negative guidance to avoid common video generation issues.
     */
    protected function getNegativeGuidance(array $context): string
    {
        $negatives = [
            'morphing faces',
            'distorted limbs',
            'unnatural motion',
            'flickering',
            'temporal inconsistency',
            'blurry faces',
            'extra fingers',
            'missing limbs',
        ];

        // Add genre-specific negatives
        $genre = $context['genre'] ?? 'general';
        if ($genre === 'horror') {
            // Horror is allowed to be more unsettling
            $negatives = array_filter($negatives, fn($n) => !in_array($n, ['morphing faces']));
        }

        return implode(', ', $negatives);
    }

    /**
     * Get fallback prompt when building fails.
     */
    protected function getFallbackPrompt(array $shot, array $context): string
    {
        $action = $shot['subjectAction'] ?? $shot['action'] ?? 'subject in frame';
        $movement = $shot['cameraMovement'] ?? 'smooth camera movement';

        return "{$action}, {$movement}, cinematic quality, natural lighting, professional cinematography";
    }

    /**
     * Get genre preset by slug or name.
     */
    protected function getGenrePreset(string $genre): ?object
    {
        return VwGenrePreset::where('slug', $genre)
            ->orWhere('name', 'like', "%{$genre}%")
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get shot framing description based on shot type.
     */
    protected function getShotFramingDescription(string $shotType): string
    {
        $descriptions = [
            'extreme-close-up' => 'extreme close-up showing fine detail',
            'close-up' => 'close-up framing on the face',
            'medium-close-up' => 'medium close-up from chest up',
            'medium-shot' => 'medium shot from waist up',
            'medium-wide-shot' => 'medium wide shot showing full figure with environment',
            'wide-shot' => 'wide shot showing full scene',
            'extreme-wide-shot' => 'extreme wide shot establishing location',
            'establishing-shot' => 'establishing shot of the location',
            'over-the-shoulder' => 'over-the-shoulder perspective',
            'two-shot' => 'two-shot framing both subjects',
            'pov' => 'point-of-view perspective',
            'aerial' => 'aerial view from above',
            'low-angle' => 'low angle looking up',
            'high-angle' => 'high angle looking down',
            'dutch-angle' => 'tilted dutch angle',
        ];

        return $descriptions[$shotType] ?? '';
    }

    /**
     * Get emotion descriptor for action context.
     */
    protected function getEmotionDescriptor(string $emotion): string
    {
        $descriptors = [
            'happy' => 'joyful',
            'sad' => 'melancholic',
            'angry' => 'intense',
            'fearful' => 'anxious',
            'surprised' => 'astonished',
            'neutral' => 'composed',
            'tense' => 'alert',
            'romantic' => 'tender',
            'mysterious' => 'enigmatic',
            'determined' => 'resolute',
        ];

        return $descriptors[$emotion] ?? '';
    }

    /**
     * Get time of day lighting description.
     */
    protected function getTimeOfDayLighting(string $timeOfDay): string
    {
        $lighting = [
            'dawn' => 'soft pre-dawn light, cool tones transitioning to warm',
            'morning' => 'bright morning light, clear and fresh',
            'golden_hour' => 'warm golden hour light, long shadows',
            'midday' => 'harsh midday sun, strong shadows',
            'afternoon' => 'warm afternoon light, soft shadows',
            'sunset' => 'rich sunset light, orange and purple hues',
            'dusk' => 'soft twilight, blue hour lighting',
            'night' => 'nighttime lighting, artificial sources',
        ];

        return $lighting[$timeOfDay] ?? '';
    }

    /**
     * Get location-based lighting.
     */
    protected function getLocationLighting(string $locationType): string
    {
        $lighting = [
            'interior' => 'interior lighting, mixed sources',
            'exterior' => 'natural outdoor lighting',
            'studio' => 'controlled studio lighting',
            'urban' => 'city lighting, mixed artificial and natural',
            'nature' => 'natural environment lighting',
            'underwater' => 'underwater caustic lighting',
        ];

        return $lighting[$locationType] ?? '';
    }

    /**
     * Get mood-based lighting adjustment.
     */
    protected function getMoodLighting(string $mood): string
    {
        $lighting = [
            'dramatic' => 'dramatic chiaroscuro lighting',
            'romantic' => 'soft diffused romantic lighting',
            'mysterious' => 'low-key mysterious lighting',
            'cheerful' => 'bright high-key lighting',
            'melancholic' => 'muted desaturated lighting',
            'tense' => 'harsh contrasting lighting',
        ];

        return $lighting[$mood] ?? '';
    }

    /**
     * Build a batch of prompts for multiple shots efficiently.
     *
     * @param array $shots Array of shot data
     * @param array $context Shared scene context
     * @return array Array of built prompts indexed by shot index
     */
    public function buildBatchPrompts(array $shots, array $context = []): array
    {
        $results = [];

        foreach ($shots as $index => $shot) {
            $results[$index] = $this->buildPrompt($shot, $context);
        }

        return $results;
    }

    /**
     * Get a simple video prompt without full processing.
     * Useful for quick previews or testing.
     *
     * @param string $action Subject action
     * @param string $movement Camera movement slug
     * @param string $style Style preset
     * @return string Simple combined prompt
     */
    public function getSimplePrompt(string $action, string $movement = 'static', string $style = 'cinematic'): string
    {
        $movementPrompt = $this->cameraMovementService->buildMovementPrompt($movement);

        $styleMarker = self::QUALITY_MARKERS[$style] ?? self::QUALITY_MARKERS['cinematic'];

        return "{$action}. {$movementPrompt}. {$styleMarker}";
    }

    /**
     * Enhance an existing prompt with camera movement.
     * Useful for upgrading basic prompts with motion intelligence.
     *
     * @param string $existingPrompt The base prompt
     * @param string $shotType Shot type for movement selection
     * @param string $emotion Emotional context
     * @return string Enhanced prompt with camera movement
     */
    public function enhanceWithMovement(string $existingPrompt, string $shotType, string $emotion = 'neutral'): string
    {
        $intensity = VwSetting::getValue('default_movement_intensity', 'moderate');
        $movement = $this->cameraMovementService->getRecommendedMovement($shotType, $emotion, $intensity);

        if (!$movement) {
            return $existingPrompt;
        }

        $movementPrompt = $this->cameraMovementService->buildMovementPrompt($movement['slug'], null, $intensity);

        // Insert camera movement before quality markers if they exist
        if (strpos($existingPrompt, 'cinematic') !== false) {
            return preg_replace('/cinematic/', "{$movementPrompt}, cinematic", $existingPrompt, 1);
        }

        return "{$existingPrompt}. {$movementPrompt}";
    }

    /**
     * Build a Hollywood-quality video prompt using the professional formula.
     * [Camera Shot + Motion] + [Subject + Detailed Action] + [Environment] + [Lighting] + [Cinematic Style]
     *
     * @param array $shot Shot data
     * @param array $context Scene/project context
     * @param array $storyBibleContext Optional Story Bible data for consistency
     * @return array Complete Hollywood prompt with components
     */
    public function buildHollywoodPrompt(array $shot, array $context = [], array $storyBibleContext = []): array
    {
        try {
            $shotType = $shot['type'] ?? 'medium-shot';
            $duration = $shot['duration'] ?? 6;
            $emotion = $shot['emotion'] ?? $context['mood'] ?? 'neutral';
            $genre = $context['genre'] ?? 'cinematic';

            // Build components following Hollywood formula order
            $components = [];

            // 1. CAMERA SHOT (establishes visual frame)
            $components['camera_shot'] = $this->buildCameraShotDescription($shotType);

            // 2. CAMERA MOVEMENT (how we see it)
            $components['camera_movement'] = $this->buildCameraMovementComponent($shot, $context);

            // 3. SUBJECT (who/what)
            $components['subject'] = $this->buildSubjectComponent($shot, $context);

            // 4. ACTION (CRITICAL - what subject is doing)
            $subjectAction = $shot['subjectAction'] ?? $shot['action'] ?? '';
            $needsLipSync = $shot['needsLipSync'] ?? false;
            $components['action'] = $this->buildActionComponent($subjectAction, $emotion, $needsLipSync);

            // 5. ENVIRONMENT (where)
            $components['environment'] = $this->buildEnvironmentComponent($context, $storyBibleContext);

            // 6. LIGHTING (mood lighting)
            $genrePreset = $this->getGenrePreset($genre);
            $components['lighting'] = $this->buildLightingComponent($context, $genrePreset);

            // 7. ATMOSPHERE (environmental feel)
            $components['atmosphere'] = $this->buildAtmosphereComponent($context, $genrePreset);

            // 8. STYLE (final polish)
            $components['style'] = $this->buildHollywoodStyleComponent($context, $genrePreset, $storyBibleContext);

            // Combine following Hollywood formula
            $promptString = $this->combineHollywoodComponents($components);

            // Add quality markers
            $qualityLevel = $context['qualityLevel'] ?? 'cinematic';
            $promptString = $this->addQualityMarkers($promptString, $qualityLevel);

            // Get negative guidance
            $negativeGuidance = $this->getNegativeGuidance($context);

            Log::info('VideoPromptBuilderService: Built Hollywood prompt', [
                'shot_type' => $shotType,
                'components_count' => count(array_filter($components)),
                'prompt_length' => strlen($promptString),
            ]);

            return [
                'success' => true,
                'prompt' => $promptString,
                'components' => $components,
                'negativeGuidance' => $negativeGuidance,
                'formula' => 'hollywood',
                'metadata' => [
                    'shotType' => $shotType,
                    'duration' => $duration,
                    'emotion' => $emotion,
                    'qualityLevel' => $qualityLevel,
                    'genre' => $genre,
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('VideoPromptBuilderService: Hollywood prompt build failed', [
                'error' => $e->getMessage(),
            ]);

            // Fallback to standard method
            return $this->buildPrompt($shot, $context);
        }
    }

    /**
     * Build camera shot description with professional terminology.
     */
    protected function buildCameraShotDescription(string $shotType): string
    {
        $descriptions = [
            'extreme-close-up' => 'Extreme close-up revealing intimate detail',
            'close-up' => 'Close-up framing the face, capturing every nuance of expression',
            'medium-close-up' => 'Medium close-up from chest up, balancing emotion and context',
            'medium-shot' => 'Medium shot from waist up, allowing gesture and expression',
            'medium-wide-shot' => 'Medium wide shot showing full figure with environment context',
            'wide-shot' => 'Wide shot capturing the complete scene and spatial relationships',
            'extreme-wide-shot' => 'Extreme wide establishing shot revealing the full scope',
            'establishing-shot' => 'Establishing shot setting the scene geography',
            'over-the-shoulder' => 'Over-the-shoulder shot creating intimate conversation perspective',
            'two-shot' => 'Two-shot framing both subjects in balanced composition',
            'pov' => 'Point-of-view shot immersing viewer in character perspective',
            'aerial' => 'Aerial shot providing god\'s-eye overview',
            'low-angle' => 'Low angle shot conveying power and dominance',
            'high-angle' => 'High angle shot suggesting vulnerability or overview',
            'dutch-angle' => 'Dutch angle creating visual tension and unease',
        ];

        return $descriptions[$shotType] ?? "Professional {$shotType} framing";
    }

    /**
     * Build environment component from context and Story Bible.
     */
    protected function buildEnvironmentComponent(array $context, array $storyBibleContext = []): string
    {
        $parts = [];

        // Location from Story Bible
        if (!empty($storyBibleContext['location'])) {
            $location = $storyBibleContext['location'];
            if (is_array($location)) {
                $parts[] = $location['description'] ?? $location['name'] ?? '';
            } else {
                $parts[] = $location;
            }
        }

        // Time of day
        if (!empty($context['timeOfDay'])) {
            $timeDescriptions = [
                'dawn' => 'at dawn with first light breaking',
                'morning' => 'in bright morning light',
                'golden_hour' => 'during golden hour magic',
                'midday' => 'under harsh midday sun',
                'afternoon' => 'in warm afternoon glow',
                'sunset' => 'as sunset paints the sky',
                'dusk' => 'in blue hour twilight',
                'night' => 'under night\'s cover',
            ];
            $parts[] = $timeDescriptions[$context['timeOfDay']] ?? $context['timeOfDay'];
        }

        // Weather/atmosphere
        if (!empty($context['weather'])) {
            $parts[] = $context['weather'];
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Build Hollywood-enhanced style component.
     */
    protected function buildHollywoodStyleComponent(array $context, ?object $genrePreset, array $storyBibleContext = []): string
    {
        $parts = [];

        // Style Bible override (highest priority)
        if (!empty($storyBibleContext['styleBible']['style'])) {
            $parts[] = $storyBibleContext['styleBible']['style'];
        } elseif ($genrePreset && !empty($genrePreset->style)) {
            $parts[] = $genrePreset->style;
        }

        // Color grading
        if (!empty($storyBibleContext['styleBible']['colorGrade'])) {
            $parts[] = $storyBibleContext['styleBible']['colorGrade'];
        } elseif ($genrePreset && !empty($genrePreset->color_grade)) {
            $parts[] = $genrePreset->color_grade;
        }

        // Depth of field (moderate keeps background visible while maintaining focus)
        if (empty($context['depthOfField']) || $context['depthOfField'] !== 'deep') {
            $parts[] = 'moderate depth of field with visible environment';
        }

        // Film characteristics
        $parts[] = 'subtle film grain, anamorphic lens characteristics';

        return implode(', ', array_filter($parts));
    }

    /**
     * Combine components following Hollywood formula order.
     * Produces natural-reading prompt with proper flow.
     */
    protected function combineHollywoodComponents(array $components): string
    {
        $sentences = [];

        // Camera and movement combined
        $cameraLine = [];
        if (!empty($components['camera_shot'])) {
            $cameraLine[] = $components['camera_shot'];
        }
        if (!empty($components['camera_movement'])) {
            $cameraLine[] = $components['camera_movement'];
        }
        if (!empty($cameraLine)) {
            $sentences[] = implode(', ', $cameraLine);
        }

        // Subject and action combined (most important)
        $subjectLine = [];
        if (!empty($components['subject'])) {
            $subjectLine[] = $components['subject'];
        }
        if (!empty($components['action'])) {
            $subjectLine[] = $components['action'];
        }
        if (!empty($subjectLine)) {
            $sentences[] = implode(', ', $subjectLine);
        }

        // Environment
        if (!empty($components['environment'])) {
            $sentences[] = $components['environment'];
        }

        // Lighting
        if (!empty($components['lighting'])) {
            $sentences[] = $components['lighting'];
        }

        // Atmosphere
        if (!empty($components['atmosphere'])) {
            $sentences[] = $components['atmosphere'];
        }

        // Style (final polish)
        if (!empty($components['style'])) {
            $sentences[] = $components['style'];
        }

        return implode('. ', array_filter($sentences));
    }

    /**
     * Build consistent video prompt maintaining visual continuity from previous scene.
     *
     * @param array $shot Current shot data
     * @param array $context Scene context
     * @param array $referenceStyle Style anchors from reference scene
     * @return array Prompt with continuity markers
     */
    public function buildConsistentPrompt(array $shot, array $context, array $referenceStyle): array
    {
        // Build base prompt
        $baseResult = $this->buildHollywoodPrompt($shot, $context);

        if (!$baseResult['success']) {
            return $baseResult;
        }

        // Extract style anchors from reference
        $continuityMarkers = [];

        if (!empty($referenceStyle['colorGrading'])) {
            $continuityMarkers[] = "maintaining {$referenceStyle['colorGrading']} color grading";
        }
        if (!empty($referenceStyle['lightingStyle'])) {
            $continuityMarkers[] = "consistent {$referenceStyle['lightingStyle']} lighting";
        }
        if (!empty($referenceStyle['filmLook'])) {
            $continuityMarkers[] = "matching {$referenceStyle['filmLook']} aesthetic";
        }
        if (!empty($referenceStyle['atmosphere'])) {
            $continuityMarkers[] = "preserving {$referenceStyle['atmosphere']} atmosphere";
        }

        // Inject continuity markers into prompt
        if (!empty($continuityMarkers)) {
            $continuityString = implode(', ', $continuityMarkers);
            $baseResult['prompt'] = "{$baseResult['prompt']}. VISUAL CONTINUITY: {$continuityString}";
            $baseResult['continuityApplied'] = true;
            $baseResult['continuityMarkers'] = $continuityMarkers;
        }

        return $baseResult;
    }

    /**
     * Extract style anchors from an existing prompt or image analysis.
     *
     * @param string $prompt The existing prompt
     * @param array $analysisData Optional image analysis data
     * @return array Style anchors for continuity
     */
    public function extractStyleAnchors(string $prompt, array $analysisData = []): array
    {
        $anchors = [
            'colorGrading' => '',
            'lightingStyle' => '',
            'filmLook' => '',
            'atmosphere' => '',
            'palette' => '',
        ];

        $prompt = strtolower($prompt);

        // Extract color grading
        $colorPatterns = [
            'teal' => 'teal and orange',
            'warm' => 'warm tones',
            'cool' => 'cool tones',
            'desaturated' => 'desaturated palette',
            'vibrant' => 'vibrant saturated',
            'muted' => 'muted earth tones',
            'golden' => 'golden warm',
            'neon' => 'neon accents',
        ];

        foreach ($colorPatterns as $keyword => $grade) {
            if (str_contains($prompt, $keyword)) {
                $anchors['colorGrading'] = $grade;
                break;
            }
        }

        // Extract lighting style
        $lightingPatterns = [
            'dramatic' => 'dramatic side lighting',
            'soft' => 'soft diffused lighting',
            'harsh' => 'harsh contrast lighting',
            'chiaroscuro' => 'chiaroscuro',
            'low-key' => 'low-key dramatic',
            'high-key' => 'high-key bright',
            'volumetric' => 'volumetric light rays',
            'backlit' => 'backlighting',
            'rim light' => 'rim lighting',
            'golden hour' => 'golden hour',
        ];

        foreach ($lightingPatterns as $keyword => $style) {
            if (str_contains($prompt, $keyword)) {
                $anchors['lightingStyle'] = $style;
                break;
            }
        }

        // Extract film look
        $filmPatterns = [
            'cinematic' => 'cinematic film look',
            'film grain' => 'organic film grain',
            'anamorphic' => 'anamorphic characteristics',
            'documentary' => 'documentary realism',
            'noir' => 'noir aesthetic',
            'vintage' => 'vintage film',
            'modern' => 'modern clean',
        ];

        foreach ($filmPatterns as $keyword => $look) {
            if (str_contains($prompt, $keyword)) {
                $anchors['filmLook'] = $look;
                break;
            }
        }

        // Extract atmosphere
        $atmospherePatterns = [
            'moody' => 'moody intimate',
            'epic' => 'epic grandeur',
            'tense' => 'tense suspenseful',
            'romantic' => 'romantic soft',
            'mysterious' => 'mysterious enigmatic',
            'peaceful' => 'peaceful serene',
            'energetic' => 'energetic dynamic',
        ];

        foreach ($atmospherePatterns as $keyword => $atmos) {
            if (str_contains($prompt, $keyword)) {
                $anchors['atmosphere'] = $atmos;
                break;
            }
        }

        // Include analysis data if provided (from AI image analysis)
        if (!empty($analysisData)) {
            if (!empty($analysisData['dominantColors'])) {
                $anchors['palette'] = implode(', ', $analysisData['dominantColors']);
            }
            if (!empty($analysisData['lighting'])) {
                $anchors['lightingStyle'] = $analysisData['lighting'];
            }
        }

        return $anchors;
    }

    /**
     * Build complete Hollywood-quality video prompt with temporal structure.
     * Implements VID-01 through VID-07 requirements.
     *
     * @param array $shot Shot data (type, duration, characters, action, emotion, etc.)
     * @param array $context Scene context (genre, mood, lighting, timeOfDay, etc.)
     * @param array $storyBibleContext Optional Story Bible data
     * @param array $temporalBeats Optional pre-defined temporal beats
     * @return array Complete prompt with components and metadata
     */
    public function buildTemporalVideoPrompt(
        array $shot,
        array $context = [],
        array $storyBibleContext = [],
        array $temporalBeats = []
    ): array {
        try {
            // Extract shot parameters
            $shotType = $shot['type'] ?? 'medium-shot';
            $duration = $shot['duration'] ?? 6;
            $emotion = $shot['emotion'] ?? $context['mood'] ?? 'neutral';
            $characters = $shot['characters'] ?? [];

            // Normalize characters to array of names
            if (!empty($characters) && !is_array($characters)) {
                $characters = [$characters];
            }

            // 1. START WITH BASE HOLLYWOOD PROMPT (VID-01: inherits all image features)
            $baseResult = $this->buildHollywoodPrompt($shot, $context, $storyBibleContext);

            if (!$baseResult['success']) {
                return $baseResult;
            }

            // Initialize temporal components
            $temporalComponents = [];
            $transitionSetup = [
                'ending_state' => '',
                'next_shot_suggestion' => '',
            ];

            // 2. ADD TEMPORAL BEAT STRUCTURE (VID-02)
            $temporalBeatsString = '';
            if (!empty($temporalBeats)) {
                // Validate beats first
                $validation = $this->videoTemporalService->validateBeatsForDuration($temporalBeats, $duration);
                if (!$validation['valid']) {
                    Log::warning('VideoPromptBuilderService: Temporal beats validation warnings', [
                        'warnings' => $validation['warnings'],
                    ]);
                }
                $temporalBeatsString = $this->videoTemporalService->buildTemporalBeats($temporalBeats, $duration);
            } else {
                // Auto-generate beats from shot action if no beats provided
                $temporalBeatsString = $this->autoGenerateTemporalBeats($shot, $duration);
            }
            $temporalComponents['temporal_beats'] = $temporalBeatsString;

            // 3. ENHANCE CAMERA MOVEMENT WITH DURATION AND PSYCHOLOGY (VID-03)
            $cameraMovement = $shot['cameraMovement'] ?? $this->inferCameraMovement($shotType, $emotion);
            $emotionalPurpose = $this->mapEmotionToPsychology($emotion);
            $movementDuration = $this->cameraMovementService->getRecommendedDuration($cameraMovement, $duration);

            $cameraWithPsychology = $this->cameraMovementService->buildTemporalMovementPrompt(
                $cameraMovement,
                $movementDuration,
                $emotionalPurpose,
                $context['intensity'] ?? 'moderate'
            );
            $temporalComponents['camera_psychology'] = $cameraWithPsychology;

            // 4. ADD CHARACTER PATH DESCRIPTION (VID-04)
            $characterPathString = '';
            $movementIntent = $shot['movement_intent'] ?? $shot['characterPath'] ?? null;
            if ($movementIntent) {
                $pathSuggestion = $this->characterPathService->suggestPathForIntent($movementIntent);
                $characterPathString = $this->characterPathService->buildCharacterPath(
                    $pathSuggestion['path_type'],
                    $pathSuggestion['variant'],
                    $shot['path_parameters'] ?? []
                );
            }
            $temporalComponents['character_path'] = $characterPathString;

            // 5. ADD MULTI-CHARACTER DYNAMICS (VID-05)
            $dynamicsString = '';
            $isMultiCharacter = count($characters) > 1;
            if ($isMultiCharacter) {
                $relationship = $shot['relationship'] ?? $context['relationship'] ?? 'colleagues';
                $proximity = $shot['proximity'] ?? $this->characterDynamicsService->getProximityForRelationship($relationship);
                $dynamicsString = $this->characterDynamicsService->buildSpatialDynamics(
                    $relationship,
                    $proximity,
                    $characters
                );
            }
            $temporalComponents['character_dynamics'] = $dynamicsString;

            // 6. ADD MICRO-MOVEMENTS (VID-06)
            $microMovementsString = $this->microMovementService->buildMicroMovementLayer(
                $shotType,
                $emotion,
                []
            );
            $temporalComponents['micro_movements'] = $microMovementsString;

            // 7. ADD TRANSITION SETUP (VID-07)
            $transitionType = $this->transitionVocabulary->suggestTransitionForMood($emotion);
            $transitionVariants = $this->transitionVocabulary->getVariantsForType($transitionType);
            $defaultVariant = !empty($transitionVariants) ? $transitionVariants[0] : 'reaction_held';

            $endingState = $this->transitionVocabulary->buildTransitionSetup(
                $transitionType,
                $shot['transition_variant'] ?? $defaultVariant,
                []
            );

            // Map transition type to ending state for next shot suggestion
            $endingStateKey = $this->mapTransitionToEndingState($transitionType, $shot);
            $nextShotSuggestion = $this->transitionVocabulary->getNextShotSuggestion($endingStateKey);

            $transitionSetup = [
                'ending_state' => $endingState,
                'next_shot_suggestion' => $nextShotSuggestion,
                'transition_type' => $transitionType,
            ];

            // ASSEMBLE FULL PROMPT
            // Order: Camera (with psychology) -> Subject & Dynamics -> Temporal Beats -> Micro-movements -> Base components
            $promptParts = [];

            // Camera with psychology and duration
            if (!empty($cameraWithPsychology)) {
                $promptParts[] = $cameraWithPsychology;
            }

            // Subject and character dynamics (for multi-character)
            if (!empty($baseResult['components']['subject'])) {
                $subjectLine = $baseResult['components']['subject'];
                if (!empty($dynamicsString)) {
                    $subjectLine .= '. ' . $dynamicsString;
                }
                $promptParts[] = $subjectLine;
            }

            // Temporal beats with timing
            if (!empty($temporalBeatsString)) {
                $promptParts[] = $temporalBeatsString;
            }

            // Character path (if provided)
            if (!empty($characterPathString)) {
                $promptParts[] = $characterPathString;
            }

            // Micro-movements (for close-ups/medium shots)
            if (!empty($microMovementsString)) {
                $promptParts[] = $microMovementsString;
            }

            // Action from base
            if (!empty($baseResult['components']['action'])) {
                $promptParts[] = $baseResult['components']['action'];
            }

            // Environment and lighting from base
            if (!empty($baseResult['components']['environment'])) {
                $promptParts[] = $baseResult['components']['environment'];
            }
            if (!empty($baseResult['components']['lighting'])) {
                $promptParts[] = $baseResult['components']['lighting'];
            }

            // Style from base
            if (!empty($baseResult['components']['style'])) {
                $promptParts[] = $baseResult['components']['style'];
            }

            $fullPrompt = implode('. ', array_filter($promptParts));

            // Add quality markers
            $qualityLevel = $context['qualityLevel'] ?? 'cinematic';
            $fullPrompt = $this->addQualityMarkers($fullPrompt, $qualityLevel);

            Log::info('VideoPromptBuilderService: Built temporal video prompt', [
                'shot_type' => $shotType,
                'duration' => $duration,
                'has_temporal_beats' => !empty($temporalBeatsString),
                'has_multi_character' => $isMultiCharacter,
                'has_micro_movements' => !empty($microMovementsString),
                'prompt_length' => strlen($fullPrompt),
            ]);

            return [
                'success' => true,
                'prompt' => $fullPrompt,
                'components' => array_merge(
                    $baseResult['components'],
                    $temporalComponents
                ),
                'transition_setup' => $transitionSetup,
                'negativeGuidance' => $baseResult['negativeGuidance'] ?? $this->getNegativeGuidance($context),
                'formula' => 'temporal_hollywood',
                'metadata' => [
                    'shotType' => $shotType,
                    'duration' => $duration,
                    'emotion' => $emotion,
                    'qualityLevel' => $qualityLevel,
                    'temporal_structure' => true,
                    'beat_count' => !empty($temporalBeats) ? count($temporalBeats) : $this->countAutoGeneratedBeats($shot, $duration),
                    'has_multi_character' => $isMultiCharacter,
                    'has_micro_movements' => !empty($microMovementsString),
                    'character_count' => count($characters),
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('VideoPromptBuilderService: Temporal prompt build failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to standard Hollywood method
            return $this->buildHollywoodPrompt($shot, $context, $storyBibleContext);
        }
    }

    /**
     * Auto-generate temporal beats from shot action and duration.
     *
     * @param array $shot Shot data
     * @param int $duration Clip duration in seconds
     * @return string Formatted temporal beats
     */
    protected function autoGenerateTemporalBeats(array $shot, int $duration): string
    {
        $subjectAction = $shot['subjectAction'] ?? $shot['action'] ?? '';

        if (empty($subjectAction)) {
            return '';
        }

        // Classify the action to get appropriate duration
        $actionType = $this->videoTemporalService->classifyAction($subjectAction);
        $suggestedDuration = $this->videoTemporalService->suggestBeatDuration($actionType);

        // Create single beat from the action
        $beats = [
            [
                'action' => $subjectAction,
                'duration' => min($suggestedDuration, $duration),
            ],
        ];

        return $this->videoTemporalService->buildTemporalBeats($beats, $duration);
    }

    /**
     * Count auto-generated beats for metadata.
     *
     * @param array $shot Shot data
     * @param int $duration Clip duration
     * @return int Beat count
     */
    protected function countAutoGeneratedBeats(array $shot, int $duration): int
    {
        $subjectAction = $shot['subjectAction'] ?? $shot['action'] ?? '';
        return !empty($subjectAction) ? 1 : 0;
    }

    /**
     * Infer camera movement from shot type and emotion.
     *
     * @param string $shotType Shot type
     * @param string $emotion Emotional context
     * @return string Movement slug
     */
    protected function inferCameraMovement(string $shotType, string $emotion): string
    {
        // Map shot types to default movements
        $shotDefaults = [
            'close-up' => 'dolly-in',
            'extreme-close-up' => 'push-in',
            'medium-shot' => 'static',
            'wide-shot' => 'pan',
            'establishing-shot' => 'crane-up',
            'two-shot' => 'static',
            'over-the-shoulder' => 'static',
        ];

        // Emotion overrides
        $emotionOverrides = [
            'tense' => 'push-in',
            'dramatic' => 'dolly-in',
            'peaceful' => 'static',
            'energetic' => 'tracking',
            'romantic' => 'dolly-in',
        ];

        // Check emotion first, then shot type
        if (isset($emotionOverrides[$emotion])) {
            return $emotionOverrides[$emotion];
        }

        return $shotDefaults[$shotType] ?? 'static';
    }

    /**
     * Map emotion to psychological purpose for camera movement.
     *
     * @param string $emotion Emotion
     * @return string Psychology key
     */
    protected function mapEmotionToPsychology(string $emotion): string
    {
        $mapping = [
            'tense' => 'tension',
            'dramatic' => 'tension',
            'romantic' => 'intimacy',
            'tender' => 'intimacy',
            'peaceful' => 'contemplation',
            'contemplative' => 'contemplation',
            'sad' => 'isolation',
            'melancholic' => 'isolation',
            'hopeful' => 'reveal',
            'curious' => 'discovery',
            'powerful' => 'power',
            'vulnerable' => 'vulnerability',
            'anxious' => 'urgency',
            'fearful' => 'urgency',
            'farewell' => 'departure',
            'neutral' => 'contemplation',
        ];

        return $mapping[strtolower($emotion)] ?? 'contemplation';
    }

    /**
     * Map transition type to ending state key for next shot suggestions.
     *
     * @param string $transitionType Transition type
     * @param array $shot Shot data
     * @return string Ending state key
     */
    protected function mapTransitionToEndingState(string $transitionType, array $shot): string
    {
        $mapping = [
            'match_cut_setup' => 'mid_motion',
            'hard_cut_setup' => 'emotional_peak',
            'soft_transition_setup' => 'settling',
        ];

        return $mapping[$transitionType] ?? 'settling';
    }
}
