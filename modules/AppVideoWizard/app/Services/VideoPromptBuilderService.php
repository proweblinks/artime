<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Models\VwGenrePreset;

/**
 * VideoPromptBuilderService - Builds professional video animation prompts.
 *
 * Implements the Higgsfield methodology for video prompts:
 * Style + Subject + Action + Camera Movement + Lighting
 *
 * Integrates with:
 * - CameraMovementService for intelligent movement selection
 * - ShotIntelligenceService for shot context
 * - GenrePresets for style guidance
 */
class VideoPromptBuilderService
{
    protected CameraMovementService $cameraMovementService;

    /**
     * Video prompt formula components.
     */
    public const PROMPT_COMPONENTS = [
        'style',           // Visual style and quality markers
        'subject',         // Who/what is in the frame
        'action',          // What the subject is doing
        'camera_movement', // How the camera moves
        'lighting',        // Lighting conditions
        'atmosphere',      // Environmental mood/atmosphere
    ];

    /**
     * Quality markers for different output levels.
     */
    public const QUALITY_MARKERS = [
        'cinematic' => 'cinematic quality, professional cinematography, 4K resolution, film grain',
        'broadcast' => 'broadcast quality, clean footage, HD resolution',
        'social' => 'dynamic visuals, engaging motion, optimized for social media',
        'premium' => '8K cinematic, IMAX quality, masterful cinematography, film-like motion blur',
    ];

    /**
     * Motion intensity descriptors.
     */
    public const MOTION_INTENSITY = [
        'subtle' => ['slow', 'gentle', 'smooth', 'gradual', 'soft'],
        'moderate' => ['steady', 'controlled', 'balanced', 'fluid', 'natural'],
        'dynamic' => ['energetic', 'active', 'lively', 'engaging', 'swift'],
        'intense' => ['dramatic', 'powerful', 'rapid', 'impactful', 'bold'],
    ];

    public function __construct(CameraMovementService $cameraMovementService)
    {
        $this->cameraMovementService = $cameraMovementService;
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
     */
    protected function buildActionComponent(string $subjectAction, string $emotion, bool $needsLipSync): string
    {
        $parts = [];

        // Main subject action
        if (!empty($subjectAction)) {
            $parts[] = $subjectAction;
        }

        // Emotional context for action
        $emotionDescriptor = $this->getEmotionDescriptor($emotion);
        if ($emotionDescriptor && strpos($subjectAction, $emotionDescriptor) === false) {
            $parts[] = "with {$emotionDescriptor} demeanor";
        }

        // Lip-sync specific guidance
        if ($needsLipSync) {
            $parts[] = 'speaking with natural lip movement';
            $parts[] = 'realistic facial expressions while talking';
        }

        return implode(', ', array_filter($parts));
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
}
