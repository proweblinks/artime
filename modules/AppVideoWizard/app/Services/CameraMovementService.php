<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * Camera Movement Service
 *
 * Handles camera movement selection, stacking, and prompt generation
 * for AI video animation. Based on Higgsfield's methodology.
 */
class CameraMovementService
{
    protected const CACHE_TTL = 3600;
    protected const CACHE_PREFIX = 'camera_movement_';

    /**
     * Get a movement by slug.
     */
    public function getMovement(string $slug): ?array
    {
        return VwCameraMovement::getBySlug($slug);
    }

    /**
     * Get all active movements.
     */
    public function getAllMovements(): array
    {
        return VwCameraMovement::getAllActive();
    }

    /**
     * Get movements grouped by category.
     */
    public function getMovementsByCategory(): array
    {
        return VwCameraMovement::getGroupedByCategory();
    }

    /**
     * Get recommended movement for a shot type.
     *
     * @param string $shotType Shot type slug
     * @param string|null $emotion Optional emotional context
     * @param string|null $intensity Optional intensity preference
     * @return array|null Recommended movement or null
     */
    public function getRecommendedMovement(
        string $shotType,
        ?string $emotion = null,
        ?string $intensity = null
    ): ?array {
        // Check if motion intelligence is enabled
        if (!$this->isMotionIntelligenceEnabled()) {
            return $this->getDefaultMovement();
        }

        $cacheKey = self::CACHE_PREFIX . "recommended_{$shotType}_{$emotion}_{$intensity}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shotType, $emotion, $intensity) {
            // First, try to match by shot type
            $movements = VwCameraMovement::getForShotType($shotType);

            if (empty($movements)) {
                $movements = $this->getAllMovements();
            }

            // Filter by emotion if provided
            if ($emotion && !empty($movements)) {
                $emotionFiltered = array_filter($movements, function ($m) use ($emotion) {
                    $emotions = $m['bestForEmotions'] ?? [];
                    return in_array($emotion, $emotions);
                });
                if (!empty($emotionFiltered)) {
                    $movements = $emotionFiltered;
                }
            }

            // Filter by intensity if provided
            if ($intensity && !empty($movements)) {
                $intensityFiltered = array_filter($movements, function ($m) use ($intensity) {
                    return ($m['intensity'] ?? 'moderate') === $intensity;
                });
                if (!empty($intensityFiltered)) {
                    $movements = $intensityFiltered;
                }
            }

            // Return first matching movement
            return !empty($movements) ? reset($movements) : $this->getDefaultMovement();
        });
    }

    /**
     * Get recommended movements for a shot type (multiple options).
     */
    public function getRecommendedMovements(string $shotType, int $limit = 5): array
    {
        $movements = VwCameraMovement::getForShotType($shotType);

        if (empty($movements)) {
            // Return generic movements
            $all = $this->getAllMovements();
            return array_slice($all, 0, $limit);
        }

        return array_slice($movements, 0, $limit);
    }

    /**
     * Check if two movements can be stacked together.
     */
    public function canStack(string $primarySlug, string $secondarySlug): bool
    {
        if (!$this->isMovementStackingEnabled()) {
            return false;
        }

        $primary = VwCameraMovement::where('slug', $primarySlug)->first();
        if (!$primary) {
            return false;
        }

        return $primary->canStackWith($secondarySlug);
    }

    /**
     * Get compatible secondary movements for stacking.
     */
    public function getStackableMovements(string $primarySlug): array
    {
        if (!$this->isMovementStackingEnabled()) {
            return [];
        }

        $primary = VwCameraMovement::where('slug', $primarySlug)->first();
        if (!$primary) {
            return [];
        }

        return $primary->getStackableMovements();
    }

    /**
     * Build camera movement prompt for video generation.
     *
     * @param string $primaryMovement Primary movement slug
     * @param string|null $secondaryMovement Optional secondary movement slug
     * @param string $intensity Movement intensity (subtle, moderate, dynamic, intense)
     * @return array Prompt data with syntax and metadata
     */
    public function buildMovementPrompt(
        string $primaryMovement,
        ?string $secondaryMovement = null,
        string $intensity = 'moderate'
    ): array {
        $primary = VwCameraMovement::where('slug', $primaryMovement)->first();

        if (!$primary) {
            return [
                'prompt' => 'camera remains static',
                'movements' => [],
                'stacked' => false,
            ];
        }

        // Build base prompt
        $prompt = $primary->prompt_syntax;
        $movements = [$primary->toConfigArray()];
        $stacked = false;

        // Add intensity modifier
        $prompt = $this->addIntensityModifier($prompt, $intensity);

        // Try to stack secondary movement
        if ($secondaryMovement && $this->canStack($primaryMovement, $secondaryMovement)) {
            $secondary = VwCameraMovement::where('slug', $secondaryMovement)->first();
            if ($secondary) {
                $prompt .= ' while ' . $secondary->prompt_syntax;
                $movements[] = $secondary->toConfigArray();
                $stacked = true;
            }
        }

        return [
            'prompt' => $prompt,
            'movements' => $movements,
            'stacked' => $stacked,
            'intensity' => $intensity,
            'endingState' => $primary->ending_state,
            'naturalContinuation' => $primary->natural_continuation,
        ];
    }

    /**
     * Suggest movement based on shot context.
     *
     * @param array $context Shot context with type, emotion, previous shot, etc.
     * @return array Suggested movement with reasoning
     */
    public function suggestMovement(array $context): array
    {
        $shotType = $context['shotType'] ?? 'medium';
        $emotion = $context['emotion'] ?? null;
        $previousMovement = $context['previousMovement'] ?? null;
        $isFirstShot = $context['isFirstShot'] ?? false;
        $sceneType = $context['sceneType'] ?? 'dialogue';

        // Get base recommendation
        $recommended = $this->getRecommendedMovement($shotType, $emotion);
        $reasoning = [];

        if (!$recommended) {
            return [
                'movement' => $this->getDefaultMovement(),
                'reasoning' => ['No specific movement found, using default'],
            ];
        }

        // Consider continuity with previous shot
        if ($previousMovement && !$isFirstShot) {
            $prev = $this->getMovement($previousMovement);
            if ($prev && isset($prev['naturalContinuation'])) {
                $continuation = $prev['naturalContinuation'];
                if ($continuation && $continuation !== $recommended['slug']) {
                    // Check if continuation is compatible with shot type
                    $contMovement = $this->getMovement($continuation);
                    if ($contMovement) {
                        $shotTypes = $contMovement['bestForShotTypes'] ?? [];
                        if (empty($shotTypes) || in_array($shotType, $shotTypes) || in_array('all', $shotTypes)) {
                            $recommended = $contMovement;
                            $reasoning[] = "Selected based on continuity from previous {$previousMovement}";
                        }
                    }
                }
            }
        }

        // Adjust for scene type
        $intensityBySceneType = [
            'dialogue' => 'subtle',
            'action' => 'dynamic',
            'montage' => 'moderate',
            'emotional' => 'subtle',
            'establishing' => 'moderate',
        ];

        $suggestedIntensity = $intensityBySceneType[$sceneType] ?? 'moderate';
        if ($recommended['intensity'] !== $suggestedIntensity) {
            $reasoning[] = "Scene type '{$sceneType}' suggests {$suggestedIntensity} intensity";
        }

        // First shot often benefits from establishing movement
        if ($isFirstShot && !in_array($shotType, ['close-up', 'extreme-close-up'])) {
            $reasoning[] = 'First shot - movement helps establish scene';
        }

        if (empty($reasoning)) {
            $reasoning[] = "Selected '{$recommended['name']}' based on shot type '{$shotType}'";
        }

        return [
            'movement' => $recommended,
            'suggestedIntensity' => $suggestedIntensity,
            'reasoning' => $reasoning,
        ];
    }

    /**
     * Get movement suggestions for stacking.
     */
    public function getStackingSuggestions(string $primarySlug, string $shotType): array
    {
        if (!$this->isMovementStackingEnabled()) {
            return [];
        }

        $maxStacked = $this->getMaxStackedMovements();
        if ($maxStacked < 2) {
            return [];
        }

        $stackable = $this->getStackableMovements($primarySlug);

        // Filter by shot type compatibility
        $filtered = array_filter($stackable, function ($m) use ($shotType) {
            $shotTypes = $m['bestForShotTypes'] ?? [];
            return empty($shotTypes) || in_array($shotType, $shotTypes) || in_array('all', $shotTypes);
        });

        return array_values($filtered);
    }

    /**
     * Add intensity modifier to prompt.
     */
    protected function addIntensityModifier(string $prompt, string $intensity): string
    {
        $modifiers = [
            'subtle' => 'very slowly and gently',
            'moderate' => 'smoothly',
            'dynamic' => 'dynamically',
            'intense' => 'rapidly and dramatically',
        ];

        $modifier = $modifiers[$intensity] ?? 'smoothly';

        // Insert modifier after "camera" if present
        if (stripos($prompt, 'camera ') === 0) {
            return preg_replace('/^camera /i', "camera {$modifier} ", $prompt);
        }

        return $prompt;
    }

    /**
     * Get default movement for fallback.
     */
    protected function getDefaultMovement(): array
    {
        $defaultSlug = VwSetting::getValue('default_camera_movement', 'static');
        $movement = $this->getMovement($defaultSlug);

        return $movement ?? [
            'slug' => 'static',
            'name' => 'Static',
            'category' => 'specialty',
            'promptSyntax' => 'camera remains static',
            'intensity' => 'subtle',
        ];
    }

    /**
     * Check if motion intelligence is enabled.
     */
    public function isMotionIntelligenceEnabled(): bool
    {
        return (bool) VwSetting::getValue('motion_intelligence_enabled', true);
    }

    /**
     * Check if movement stacking is enabled.
     */
    public function isMovementStackingEnabled(): bool
    {
        return (bool) VwSetting::getValue('movement_stacking_enabled', true);
    }

    /**
     * Get max stacked movements.
     */
    public function getMaxStackedMovements(): int
    {
        return (int) VwSetting::getValue('max_stacked_movements', 2);
    }

    /**
     * Get default movement intensity.
     */
    public function getDefaultIntensity(): string
    {
        return VwSetting::getValue('default_movement_intensity', 'moderate');
    }

    /**
     * Check if auto-select movement is enabled.
     */
    public function isAutoSelectEnabled(): bool
    {
        return (bool) VwSetting::getValue('auto_select_movement', true);
    }

    /**
     * Clear service cache.
     */
    public function clearCache(): void
    {
        VwCameraMovement::clearCache();
    }
}
