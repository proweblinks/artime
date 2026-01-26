<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * MiseEnSceneService
 *
 * Modifies environment descriptions to reflect character emotional states.
 * Implements the Hollywood technique where environment reflects inner psychology:
 * - Anxious scenes have cramped framing and harsh shadows
 * - Peaceful scenes have soft lighting and open space
 *
 * This is the visual storytelling principle that the environment IS the emotion,
 * not just a backdrop for it.
 */
class MiseEnSceneService
{
    /**
     * Environment-emotion mappings (mise-en-scene psychology).
     *
     * Each emotional state has corresponding lighting, colors, space, and atmosphere
     * that reinforce the psychological state visually.
     */
    public const MISE_EN_SCENE_MAPPINGS = [
        'anxiety' => [
            'lighting' => 'harsh overhead creating unflattering shadows under eyes, flickering or unstable light source',
            'colors' => 'desaturated with sickly yellow-green undertone, washed out highlights',
            'space' => 'cramped framing, cluttered background pressing in, low ceiling visible',
            'atmosphere' => 'thick air, visible dust particles, sense of walls closing in',
        ],
        'tension' => [
            'lighting' => 'dramatic side lighting, deep shadows obscuring half the scene, unstable flickering',
            'colors' => 'high contrast, deep blacks against sharp highlights, red or amber accents',
            'space' => 'diagonal compositions, tilted horizon, foreground obstructions partially blocking view',
            'atmosphere' => 'heavy stillness, pregnant pause, something about to break',
        ],
        'peace' => [
            'lighting' => 'soft diffused natural light, gentle golden hour warmth, no harsh shadows',
            'colors' => 'warm earth tones, soft pastels, harmonious palette',
            'space' => 'open airy composition, breathing room around subject, clear sightlines',
            'atmosphere' => 'gentle movement of curtains or leaves, calm settled quality',
        ],
        'isolation' => [
            'lighting' => 'single pool of light in darkness, subject illuminated but surroundings lost in shadow',
            'colors' => 'cool blues and grays, muted and distant',
            'space' => 'vast negative space around subject, distant horizon, empty surroundings',
            'atmosphere' => 'quiet emptiness, stillness without warmth',
        ],
        'danger' => [
            'lighting' => 'harsh underlit or backlit silhouette, dramatic rim light, deep impenetrable shadows',
            'colors' => 'saturated reds, deep blacks, warning colors',
            'space' => 'constricted escape routes, walls or obstacles hemming in, no clear exit',
            'atmosphere' => 'thick tension, predatory stillness, imminent threat',
        ],
        'hope' => [
            'lighting' => 'shaft of light breaking through darkness, dawn light on horizon, volumetric rays',
            'colors' => 'warm gold emerging from cool shadows, gradient from dark to light',
            'space' => 'opening vista, path forward visible, expanding horizon',
            'atmosphere' => 'sense of emergence, dawn breaking, weight lifting',
        ],
        'intimacy' => [
            'lighting' => 'warm close lighting, soft key with gentle fill, candlelight warmth',
            'colors' => 'warm amber and honey tones, skin-flattering palette',
            'space' => 'tight two-shot framing, subjects close together, background softened away',
            'atmosphere' => 'private enclosed world, outside world excluded',
        ],
        'chaos' => [
            'lighting' => 'strobing or rapidly changing, multiple conflicting sources, harsh mixed temperatures',
            'colors' => 'clashing discordant palette, oversaturated and jarring',
            'space' => 'dutch angle, motion blur, fragmented composition, multiple planes of action',
            'atmosphere' => 'sensory overload, disorientation, loss of control',
        ],
    ];

    /**
     * Tension scale for gradual environmental shift.
     *
     * Provides progressive space and lighting modifications
     * from relaxed (1) to oppressive (10).
     */
    public const TENSION_SCALE = [
        1 => ['space_modifier' => 'open and comfortable', 'light_modifier' => 'even and natural'],
        2 => ['space_modifier' => 'spacious and relaxed', 'light_modifier' => 'soft ambient glow'],
        3 => ['space_modifier' => 'slightly confined', 'light_modifier' => 'subtle shadows forming'],
        4 => ['space_modifier' => 'moderately compressed', 'light_modifier' => 'distinct shadow areas'],
        5 => ['space_modifier' => 'noticeably compressed', 'light_modifier' => 'pronounced shadow areas'],
        6 => ['space_modifier' => 'uncomfortably tight', 'light_modifier' => 'heavy shadows encroaching'],
        7 => ['space_modifier' => 'claustrophobic and pressing', 'light_modifier' => 'dramatic contrast'],
        8 => ['space_modifier' => 'suffocatingly close', 'light_modifier' => 'severe chiaroscuro'],
        9 => ['space_modifier' => 'walls closing in', 'light_modifier' => 'near-darkness with harsh accents'],
        10 => ['space_modifier' => 'oppressively tight, no escape', 'light_modifier' => 'harsh chiaroscuro'],
    ];

    /**
     * Get mise-en-scene environment modifiers for an emotional state.
     *
     * @param string $emotion The emotional state (anxiety, peace, tension, etc.)
     * @return array{lighting: string, colors: string, space: string, atmosphere: string}
     */
    public function getMiseEnSceneForEmotion(string $emotion): array
    {
        $emotion = strtolower(trim($emotion));

        if (isset(self::MISE_EN_SCENE_MAPPINGS[$emotion])) {
            return self::MISE_EN_SCENE_MAPPINGS[$emotion];
        }

        // Try to find a close match via aliases
        $aliases = [
            'anxious' => 'anxiety',
            'nervous' => 'anxiety',
            'worried' => 'anxiety',
            'stressed' => 'anxiety',
            'tense' => 'tension',
            'suspense' => 'tension',
            'nervous anticipation' => 'tension',
            'peaceful' => 'peace',
            'calm' => 'peace',
            'serene' => 'peace',
            'tranquil' => 'peace',
            'isolated' => 'isolation',
            'lonely' => 'isolation',
            'alone' => 'isolation',
            'abandoned' => 'isolation',
            'dangerous' => 'danger',
            'threatening' => 'danger',
            'menacing' => 'danger',
            'ominous' => 'danger',
            'hopeful' => 'hope',
            'optimistic' => 'hope',
            'uplifting' => 'hope',
            'intimate' => 'intimacy',
            'romantic' => 'intimacy',
            'close' => 'intimacy',
            'tender' => 'intimacy',
            'chaotic' => 'chaos',
            'frantic' => 'chaos',
            'manic' => 'chaos',
            'overwhelming' => 'chaos',
        ];

        $mappedEmotion = $aliases[$emotion] ?? null;

        if ($mappedEmotion && isset(self::MISE_EN_SCENE_MAPPINGS[$mappedEmotion])) {
            Log::debug('MiseEnScene: Mapped emotion alias', [
                'original' => $emotion,
                'mapped' => $mappedEmotion,
            ]);
            return self::MISE_EN_SCENE_MAPPINGS[$mappedEmotion];
        }

        // Default to neutral/balanced if unknown
        Log::info('MiseEnScene: Unknown emotion, returning neutral', ['emotion' => $emotion]);
        return [
            'lighting' => 'balanced natural lighting with soft contrast',
            'colors' => 'neutral palette with natural tones',
            'space' => 'comfortable framing with balanced composition',
            'atmosphere' => 'settled ambient quality',
        ];
    }

    /**
     * Build an environmentally-enhanced mood description by blending
     * emotional atmosphere with a base location.
     *
     * @param string $emotion The emotional state to convey
     * @param array $baseEnvironment The base location data (from Story Bible)
     * @return array Enhanced environment with emotional overlay
     */
    public function buildEnvironmentalMood(string $emotion, array $baseEnvironment): array
    {
        $emotionalMood = $this->getMiseEnSceneForEmotion($emotion);

        $result = [
            // Preserve base location identity
            'base_location' => $baseEnvironment['name'] ?? $baseEnvironment['description'] ?? 'unknown location',
            'base_type' => $baseEnvironment['type'] ?? 'interior',
            'base_description' => $baseEnvironment['description'] ?? '',

            // Emotional overlay
            'emotional_lighting' => $emotionalMood['lighting'],
            'emotional_colors' => $emotionalMood['colors'],
            'emotional_space' => $emotionalMood['space'],
            'emotional_atmosphere' => $emotionalMood['atmosphere'],

            // Combined for prompt generation
            'combined_description' => $this->combineDescriptions(
                $baseEnvironment['description'] ?? '',
                $emotionalMood
            ),
        ];

        Log::debug('MiseEnScene: Built environmental mood', [
            'emotion' => $emotion,
            'base_location' => $result['base_location'],
            'has_combined' => !empty($result['combined_description']),
        ]);

        return $result;
    }

    /**
     * Get spacial tension modifiers for a given tension level (1-10).
     *
     * @param int $tensionLevel Tension level from 1 (relaxed) to 10 (oppressive)
     * @return array{space_modifier: string, light_modifier: string}
     */
    public function getSpacialTension(int $tensionLevel): array
    {
        // Clamp to valid range
        $level = max(1, min(10, $tensionLevel));

        // Direct lookup if defined
        if (isset(self::TENSION_SCALE[$level])) {
            return self::TENSION_SCALE[$level];
        }

        // Interpolate for undefined levels (shouldn't happen with 1-10 defined)
        $lowerKey = null;
        $upperKey = null;

        foreach (array_keys(self::TENSION_SCALE) as $key) {
            if ($key <= $level) {
                $lowerKey = $key;
            }
            if ($key >= $level && $upperKey === null) {
                $upperKey = $key;
            }
        }

        // Return lower bound if can't interpolate
        return self::TENSION_SCALE[$lowerKey ?? 1];
    }

    /**
     * Blend two environment descriptions with a given intensity.
     *
     * @param array $base The base environment (location)
     * @param array $emotional The emotional environment overlay
     * @param float $intensity Blend intensity (0.0 = pure base, 1.0 = pure emotional)
     * @return array Blended environment description
     */
    public function blendEnvironments(array $base, array $emotional, float $intensity = 0.5): array
    {
        // Clamp intensity
        $intensity = max(0.0, min(1.0, $intensity));

        // At 0.0 intensity, return pure base
        if ($intensity <= 0.0) {
            return [
                'lighting' => $base['lighting'] ?? '',
                'colors' => $base['colors'] ?? '',
                'space' => $base['space'] ?? '',
                'atmosphere' => $base['atmosphere'] ?? $base['description'] ?? '',
                'blended' => false,
                'intensity' => 0.0,
            ];
        }

        // At 1.0 intensity, return pure emotional
        if ($intensity >= 1.0) {
            return [
                'lighting' => $emotional['lighting'] ?? '',
                'colors' => $emotional['colors'] ?? '',
                'space' => $emotional['space'] ?? '',
                'atmosphere' => $emotional['atmosphere'] ?? '',
                'blended' => false,
                'intensity' => 1.0,
            ];
        }

        // Blend: prioritize emotional but keep base context
        $result = [
            'lighting' => $this->blendText(
                $base['lighting'] ?? '',
                $emotional['lighting'] ?? '',
                $intensity
            ),
            'colors' => $this->blendText(
                $base['colors'] ?? '',
                $emotional['colors'] ?? '',
                $intensity
            ),
            'space' => $this->blendText(
                $base['space'] ?? '',
                $emotional['space'] ?? '',
                $intensity
            ),
            'atmosphere' => $this->blendText(
                $base['atmosphere'] ?? $base['description'] ?? '',
                $emotional['atmosphere'] ?? '',
                $intensity
            ),
            'blended' => true,
            'intensity' => $intensity,
        ];

        return $result;
    }

    /**
     * Combine base location description with emotional modifiers.
     *
     * @param string $baseDescription Original location description
     * @param array $emotionalMood Emotional mise-en-scene
     * @return string Combined description for prompt generation
     */
    protected function combineDescriptions(string $baseDescription, array $emotionalMood): string
    {
        $parts = [];

        // Start with base if provided
        if (!empty($baseDescription)) {
            $parts[] = trim($baseDescription);
        }

        // Add emotional overlay
        $parts[] = "Lighting: {$emotionalMood['lighting']}";
        $parts[] = "Colors: {$emotionalMood['colors']}";
        $parts[] = "Space: {$emotionalMood['space']}";
        $parts[] = "Atmosphere: {$emotionalMood['atmosphere']}";

        return implode('. ', $parts);
    }

    /**
     * Blend two text descriptions based on intensity.
     *
     * @param string $base Base text
     * @param string $emotional Emotional text
     * @param float $intensity How much emotional to include (0-1)
     * @return string Blended description
     */
    protected function blendText(string $base, string $emotional, float $intensity): string
    {
        $base = trim($base);
        $emotional = trim($emotional);

        // Empty cases
        if (empty($base) && empty($emotional)) {
            return '';
        }
        if (empty($base)) {
            return $emotional;
        }
        if (empty($emotional)) {
            return $base;
        }

        // At low intensity, prefer base with emotional accent
        if ($intensity < 0.3) {
            return $base . ', with hints of ' . $this->extractKeyPhrase($emotional);
        }

        // At medium intensity, combine both
        if ($intensity < 0.7) {
            return $base . ', shifting toward ' . $emotional;
        }

        // At high intensity, prefer emotional with base grounding
        return $emotional . ', grounded in ' . $this->extractKeyPhrase($base);
    }

    /**
     * Extract a key phrase from a description for blending.
     *
     * @param string $text Full description
     * @return string Key phrase
     */
    protected function extractKeyPhrase(string $text): string
    {
        // Take first clause up to comma, period, or reasonable length
        $text = trim($text);

        // Split on comma or period
        $parts = preg_split('/[,.]/', $text, 2);
        $firstPart = trim($parts[0] ?? $text);

        // Truncate if too long
        if (strlen($firstPart) > 50) {
            $words = explode(' ', $firstPart);
            $firstPart = implode(' ', array_slice($words, 0, 6));
        }

        return strtolower($firstPart);
    }

    /**
     * Get all available emotion types.
     *
     * @return array<string>
     */
    public function getAvailableEmotions(): array
    {
        return array_keys(self::MISE_EN_SCENE_MAPPINGS);
    }

    /**
     * Build a prompt-ready mise-en-scene block for an emotion.
     *
     * @param string $emotion The emotional state
     * @return string Formatted block for image generation prompts
     */
    public function buildPromptBlock(string $emotion): string
    {
        $mise = $this->getMiseEnSceneForEmotion($emotion);

        return sprintf(
            "[MISE-EN-SCENE: %s] Lighting: %s. Colors: %s. Space: %s. Atmosphere: %s.",
            $emotion,
            $mise['lighting'],
            $mise['colors'],
            $mise['space'],
            $mise['atmosphere']
        );
    }
}
