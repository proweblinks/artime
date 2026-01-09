<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Modules\AppVideoWizard\Models\VwGenrePreset;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Models\VwEmotionalBeat;
use Modules\AppVideoWizard\Models\VwStoryStructure;
use Modules\AppVideoWizard\Models\VwCameraSpec;

/**
 * Professional Cinematography Service
 *
 * Provides database-backed cinematography data with caching.
 * Integrates genre presets, shot types, emotional beats, story structures,
 * and camera specs for Hollywood-quality video generation.
 */
class CinematographyService
{
    protected const CACHE_TTL = 3600; // 1 hour
    protected const CACHE_PREFIX = 'cinematography_';

    /**
     * Fallback genre presets if database is empty.
     * Based on Hollywood cinematography standards.
     */
    protected const FALLBACK_GENRE_PRESETS = [
        'standard' => [
            'camera' => 'balanced movements, professional framing, smooth transitions',
            'colorGrade' => 'natural, balanced, professional',
            'lighting' => 'motivated, natural-looking, balanced',
            'atmosphere' => 'clean, professional, versatile',
            'style' => 'professional standard, versatile, clean',
        ],
        'cinematic-thriller' => [
            'camera' => 'slow dolly, low angles, stabilized gimbal, anamorphic lens feel',
            'colorGrade' => 'desaturated teal shadows, amber highlights, crushed blacks',
            'lighting' => 'harsh single-source, dramatic rim lights, deep shadows',
            'atmosphere' => 'smoke, rain reflections, wet surfaces, urban grit',
            'style' => 'ultra-cinematic photoreal, noir thriller, high contrast',
        ],
        'documentary-narrative' => [
            'camera' => 'smooth tracking, wide establishing shots, intimate close-ups',
            'colorGrade' => 'natural tones, slight desaturation, documentary realism',
            'lighting' => 'natural light, available light, practical sources',
            'atmosphere' => 'authentic environments, real textures, genuine moments',
            'style' => 'documentary realism, authentic, observational',
        ],
    ];

    /**
     * Get a genre preset by slug.
     * Falls back to database default, then 'standard', then fallback constants.
     */
    public function getGenrePreset(string $slug): array
    {
        $cacheKey = self::CACHE_PREFIX . 'preset_' . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            // Try to get from database
            $preset = VwGenrePreset::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($preset) {
                return $this->formatPresetForUse($preset);
            }

            // Try standard preset from database
            $standard = VwGenrePreset::where('slug', 'standard')
                ->where('is_active', true)
                ->first();

            if ($standard) {
                return $this->formatPresetForUse($standard);
            }

            // Try default preset from database
            $default = VwGenrePreset::where('is_default', true)
                ->where('is_active', true)
                ->first();

            if ($default) {
                return $this->formatPresetForUse($default);
            }

            // Fall back to constants
            return self::FALLBACK_GENRE_PRESETS[$slug]
                ?? self::FALLBACK_GENRE_PRESETS['standard'];
        });
    }

    /**
     * Format a database preset for use in VideoWizard.
     */
    protected function formatPresetForUse(VwGenrePreset $preset): array
    {
        return [
            'camera' => $preset->camera_language ?? '',
            'colorGrade' => $preset->color_grade ?? '',
            'lighting' => $preset->lighting ?? '',
            'atmosphere' => $preset->atmosphere ?? '',
            'style' => $preset->style ?? '',
            'promptPrefix' => $preset->prompt_prefix ?? '',
            'promptSuffix' => $preset->prompt_suffix ?? '',
            'lensPreferences' => is_string($preset->lens_preferences)
                ? json_decode($preset->lens_preferences, true)
                : ($preset->lens_preferences ?? []),
        ];
    }

    /**
     * Get all active genre presets for UI display.
     */
    public function getAllGenrePresets(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_presets';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $presets = VwGenrePreset::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            if ($presets->isEmpty()) {
                // Return formatted fallback presets
                return collect(self::FALLBACK_GENRE_PRESETS)->map(function ($preset, $key) {
                    return [
                        'id' => $key,
                        'slug' => $key,
                        'name' => ucwords(str_replace('-', ' ', $key)),
                        'category' => 'standard',
                        'camera' => $preset['camera'] ?? '',
                        'style' => $preset['style'] ?? '',
                    ];
                })->values()->all();
            }

            return $presets->map(function ($preset) {
                return [
                    'id' => $preset->slug,
                    'slug' => $preset->slug,
                    'name' => $preset->name,
                    'category' => $preset->category,
                    'camera' => $preset->camera_language ?? '',
                    'style' => $preset->style ?? '',
                    'description' => $preset->description ?? '',
                ];
            })->all();
        });
    }

    /**
     * Get presets grouped by category for UI display.
     */
    public function getPresetsGroupedByCategory(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'presets_by_category';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $presets = VwGenrePreset::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            if ($presets->isEmpty()) {
                return ['standard' => [
                    [
                        'slug' => 'standard',
                        'name' => 'Standard',
                        'style' => self::FALLBACK_GENRE_PRESETS['standard']['style'],
                    ]
                ]];
            }

            return $presets->groupBy('category')->map(function ($group) {
                return $group->map(function ($preset) {
                    return [
                        'slug' => $preset->slug,
                        'name' => $preset->name,
                        'style' => $preset->style ?? '',
                        'description' => $preset->description ?? '',
                    ];
                })->all();
            })->all();
        });
    }

    /**
     * Get shot types for a specific emotional beat.
     */
    public function getShotTypesForBeat(string $beatSlug): array
    {
        $cacheKey = self::CACHE_PREFIX . 'shots_for_beat_' . $beatSlug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($beatSlug) {
            $beat = VwEmotionalBeat::where('slug', $beatSlug)
                ->where('is_active', true)
                ->first();

            if (!$beat) {
                return $this->getDefaultShotTypes();
            }

            $recommendedSlugs = is_string($beat->recommended_shot_types)
                ? json_decode($beat->recommended_shot_types, true)
                : ($beat->recommended_shot_types ?? []);

            if (empty($recommendedSlugs)) {
                return $this->getDefaultShotTypes();
            }

            $shots = VwShotType::whereIn('slug', $recommendedSlugs)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $shots->map(function ($shot) {
                return $this->formatShotTypeForUse($shot);
            })->all();
        });
    }

    /**
     * Get all active shot types.
     */
    public function getAllShotTypes(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_shot_types';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $shots = VwShotType::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            if ($shots->isEmpty()) {
                return $this->getDefaultShotTypes();
            }

            return $shots->map(function ($shot) {
                return $this->formatShotTypeForUse($shot);
            })->all();
        });
    }

    /**
     * Get shot types by category.
     */
    public function getShotTypesByCategory(string $category): array
    {
        $cacheKey = self::CACHE_PREFIX . 'shots_by_category_' . $category;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            $shots = VwShotType::where('category', $category)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $shots->map(function ($shot) {
                return $this->formatShotTypeForUse($shot);
            })->all();
        });
    }

    /**
     * Get a specific shot type by slug.
     */
    public function getShotType(string $slug): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'shot_type_' . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            $shot = VwShotType::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$shot) {
                return null;
            }

            return $this->formatShotTypeForUse($shot);
        });
    }

    /**
     * Format shot type for use in VideoWizard.
     */
    protected function formatShotTypeForUse(VwShotType $shot): array
    {
        return [
            'slug' => $shot->slug,
            'name' => $shot->name,
            'category' => $shot->category,
            'description' => $shot->description ?? '',
            'cameraSpecs' => $shot->camera_specs ?? '',
            'lens' => $shot->default_lens ?? '',
            'aperture' => $shot->default_aperture ?? '',
            'durationMin' => $shot->typical_duration_min ?? 3,
            'durationMax' => $shot->typical_duration_max ?? 8,
            'motionDescription' => $shot->motion_description ?? '',
            'promptTemplate' => $shot->prompt_template ?? '',
            'emotionalBeats' => is_string($shot->emotional_beats)
                ? json_decode($shot->emotional_beats, true)
                : ($shot->emotional_beats ?? []),
            'bestForGenres' => is_string($shot->best_for_genres)
                ? json_decode($shot->best_for_genres, true)
                : ($shot->best_for_genres ?? []),
        ];
    }

    /**
     * Get default shot types as fallback.
     */
    protected function getDefaultShotTypes(): array
    {
        return [
            ['slug' => 'establishing', 'name' => 'Establishing Shot', 'category' => 'framing', 'lens' => '24mm', 'durationMin' => 4, 'durationMax' => 8],
            ['slug' => 'wide', 'name' => 'Wide Shot', 'category' => 'framing', 'lens' => '35mm', 'durationMin' => 3, 'durationMax' => 6],
            ['slug' => 'medium', 'name' => 'Medium Shot', 'category' => 'framing', 'lens' => '50mm', 'durationMin' => 3, 'durationMax' => 6],
            ['slug' => 'close-up', 'name' => 'Close-Up', 'category' => 'framing', 'lens' => '85mm', 'durationMin' => 2, 'durationMax' => 5],
            ['slug' => 'extreme-close-up', 'name' => 'Extreme Close-Up', 'category' => 'framing', 'lens' => '100mm macro', 'durationMin' => 2, 'durationMax' => 4],
        ];
    }

    /**
     * Get active story structure.
     */
    public function getDefaultStoryStructure(): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'default_story_structure';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $structure = VwStoryStructure::where('is_default', true)
                ->where('is_active', true)
                ->first();

            if (!$structure) {
                $structure = VwStoryStructure::where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();
            }

            if (!$structure) {
                // Return fallback structure
                return [
                    'slug' => 'classic-three-act',
                    'name' => 'Classic Three-Act Structure',
                    'actDistribution' => ['act1' => 25, 'act2' => 50, 'act3' => 25],
                    'minScenes' => 3,
                    'maxScenes' => 12,
                    'pacingCurve' => [0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 0.8, 0.6],
                ];
            }

            return [
                'slug' => $structure->slug,
                'name' => $structure->name,
                'description' => $structure->description ?? '',
                'actDistribution' => is_string($structure->act_distribution)
                    ? json_decode($structure->act_distribution, true)
                    : ($structure->act_distribution ?? ['act1' => 25, 'act2' => 50, 'act3' => 25]),
                'minScenes' => $structure->min_scenes ?? 3,
                'maxScenes' => $structure->max_scenes ?? 12,
                'pacingCurve' => is_string($structure->pacing_curve)
                    ? json_decode($structure->pacing_curve, true)
                    : ($structure->pacing_curve ?? []),
            ];
        });
    }

    /**
     * Get all active story structures.
     */
    public function getAllStoryStructures(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_story_structures';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $structures = VwStoryStructure::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $structures->map(function ($structure) {
                return [
                    'slug' => $structure->slug,
                    'name' => $structure->name,
                    'description' => $structure->description ?? '',
                    'isDefault' => $structure->is_default,
                    'actDistribution' => is_string($structure->act_distribution)
                        ? json_decode($structure->act_distribution, true)
                        : ($structure->act_distribution ?? []),
                    'minScenes' => $structure->min_scenes ?? 3,
                    'maxScenes' => $structure->max_scenes ?? 12,
                ];
            })->all();
        });
    }

    /**
     * Get emotional beats for a story position.
     */
    public function getBeatsForPosition(string $position): array
    {
        $cacheKey = self::CACHE_PREFIX . 'beats_for_position_' . $position;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($position) {
            $beats = VwEmotionalBeat::where('story_position', $position)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $beats->map(function ($beat) {
                return [
                    'slug' => $beat->slug,
                    'name' => $beat->name,
                    'description' => $beat->description ?? '',
                    'intensity' => $beat->intensity_level ?? 5,
                    'storyPosition' => $beat->story_position,
                    'recommendedShots' => is_string($beat->recommended_shot_types)
                        ? json_decode($beat->recommended_shot_types, true)
                        : ($beat->recommended_shot_types ?? []),
                ];
            })->all();
        });
    }

    /**
     * Get camera specs (lenses or film stocks).
     */
    public function getCameraSpecs(string $category = null): array
    {
        $cacheKey = self::CACHE_PREFIX . 'camera_specs_' . ($category ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            $query = VwCameraSpec::where('is_active', true);

            if ($category) {
                $query->where('category', $category);
            }

            $specs = $query->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            return $specs->map(function ($spec) {
                return [
                    'slug' => $spec->slug,
                    'name' => $spec->name,
                    'category' => $spec->category,
                    'description' => $spec->description ?? '',
                    'promptText' => $spec->prompt_text ?? '',
                ];
            })->all();
        });
    }

    /**
     * Get recommended lens for a shot type.
     */
    public function getRecommendedLens(string $shotTypeSlug): ?array
    {
        $shotType = $this->getShotType($shotTypeSlug);

        if (!$shotType || empty($shotType['lens'])) {
            return null;
        }

        // Try to find matching lens in camera specs
        $lenses = $this->getCameraSpecs('lens');

        foreach ($lenses as $lens) {
            if (str_contains(strtolower($lens['name']), strtolower($shotType['lens']))) {
                return $lens;
            }
        }

        // Return basic lens info from shot type
        return [
            'slug' => 'custom',
            'name' => $shotType['lens'],
            'promptText' => $shotType['lens'],
        ];
    }

    /**
     * Build a professional cinematography prompt.
     * Combines genre preset, shot type, and camera specs.
     */
    public function buildCinematographyPrompt(
        string $subject,
        string $genreSlug,
        string $shotTypeSlug,
        ?string $emotionalBeat = null
    ): string {
        $parts = [];

        // 1. Subject description
        $parts[] = $subject;

        // 2. Shot type camera specs
        $shotType = $this->getShotType($shotTypeSlug);
        if ($shotType && !empty($shotType['cameraSpecs'])) {
            $parts[] = $shotType['cameraSpecs'];
        }

        // 3. Lens
        if ($shotType && !empty($shotType['lens'])) {
            $parts[] = $shotType['lens'];
        }

        // 4. Genre styling
        $preset = $this->getGenrePreset($genreSlug);

        if (!empty($preset['colorGrade'])) {
            $parts[] = $preset['colorGrade'];
        }

        if (!empty($preset['lighting'])) {
            // Take first lighting element
            $lightingElements = explode(',', $preset['lighting']);
            $parts[] = trim($lightingElements[0]);
        }

        if (!empty($preset['atmosphere'])) {
            $parts[] = $preset['atmosphere'];
        }

        if (!empty($preset['style'])) {
            $parts[] = $preset['style'];
        }

        // 5. Technical quality
        $parts[] = '4K, cinematic, professional cinematography';

        // Apply prefix/suffix if available
        $prompt = implode('. ', $parts);

        if (!empty($preset['promptPrefix'])) {
            $prompt = $preset['promptPrefix'] . ' ' . $prompt;
        }

        if (!empty($preset['promptSuffix'])) {
            $prompt .= ' ' . $preset['promptSuffix'];
        }

        return $prompt;
    }

    /**
     * Clear all cinematography caches.
     */
    public function clearCache(): void
    {
        $patterns = [
            'all_presets',
            'presets_by_category',
            'all_shot_types',
            'default_story_structure',
            'all_story_structures',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget(self::CACHE_PREFIX . $pattern);
        }

        // Clear model caches if they have clearCache methods
        if (method_exists(VwGenrePreset::class, 'clearCache')) {
            VwGenrePreset::clearCache();
        }
        if (method_exists(VwShotType::class, 'clearCache')) {
            VwShotType::clearCache();
        }
        if (method_exists(VwEmotionalBeat::class, 'clearCache')) {
            VwEmotionalBeat::clearCache();
        }
        if (method_exists(VwStoryStructure::class, 'clearCache')) {
            VwStoryStructure::clearCache();
        }
        if (method_exists(VwCameraSpec::class, 'clearCache')) {
            VwCameraSpec::clearCache();
        }
    }
}
