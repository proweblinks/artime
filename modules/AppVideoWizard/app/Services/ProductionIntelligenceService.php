<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\VwSetting;
use Illuminate\Support\Facades\Log;

/**
 * Production Intelligence Service
 *
 * Bridges the gap between production type selection and feature activation.
 * Reads production type configuration and VwSettings to determine:
 * - Which features should auto-enable for a given production type
 * - Character scene percentages for narrative continuity
 * - Whether to use literal or narrative character tracking
 */
class ProductionIntelligenceService
{
    /**
     * Cache for production type config
     */
    protected ?array $productionTypesConfig = null;

    /**
     * Get the features that should auto-enable for a production type.
     *
     * @param string $productionType The main production type (e.g., 'movie', 'social')
     * @param string|null $subType The sub-type (e.g., 'action', 'drama')
     * @return array Features with their activation mode
     */
    public function getAutoFeatures(string $productionType, ?string $subType = null): array
    {
        // First try to get from config.php (production type specific)
        $configFeatures = $this->getFeaturesFromConfig($productionType);

        // If no config features, fall back to VwSettings
        if (empty($configFeatures)) {
            $configFeatures = $this->getFeaturesFromSettings($productionType);
        }

        Log::debug('ProductionIntelligence: Features for type', [
            'productionType' => $productionType,
            'subType' => $subType,
            'features' => $configFeatures,
        ]);

        return $configFeatures;
    }

    /**
     * Get features from config.php production type definition.
     */
    protected function getFeaturesFromConfig(string $productionType): array
    {
        $config = $this->getProductionTypesConfig();

        if (!isset($config[$productionType]['features'])) {
            return [];
        }

        return $config[$productionType]['features'];
    }

    /**
     * Get features from VwSettings (fallback/override).
     */
    protected function getFeaturesFromSettings(string $productionType): array
    {
        $features = [];

        // Check each feature type against the settings
        $multiShotTypes = $this->getSettingAsArray('production_auto_multishot_types');
        $characterBibleTypes = $this->getSettingAsArray('production_auto_character_bible_types');
        $locationBibleTypes = $this->getSettingAsArray('production_auto_location_bible_types');
        $styleBibleTypes = $this->getSettingAsArray('production_auto_style_bible_types');

        $features['multiShotMode'] = in_array($productionType, $multiShotTypes) ? 'auto' : 'manual';
        $features['characterBible'] = in_array($productionType, $characterBibleTypes) ? 'auto' : 'manual';
        $features['locationBible'] = in_array($productionType, $locationBibleTypes) ? 'auto' : 'manual';
        $features['styleBible'] = in_array($productionType, $styleBibleTypes) ? 'auto' : 'manual';

        return $features;
    }

    /**
     * Get intelligence rules for a production type.
     *
     * @param string $productionType The main production type
     * @return array Intelligence rules (scene percentages, tracking mode, etc.)
     */
    public function getIntelligenceRules(string $productionType): array
    {
        // First try config.php
        $config = $this->getProductionTypesConfig();

        if (isset($config[$productionType]['intelligence'])) {
            return $config[$productionType]['intelligence'];
        }

        // Fall back to VwSettings
        return $this->getIntelligenceFromSettings($productionType);
    }

    /**
     * Get intelligence rules from VwSettings.
     */
    protected function getIntelligenceFromSettings(string $productionType): array
    {
        $narrativeTypes = $this->getSettingAsArray('production_narrative_tracking_types');

        return [
            'mainCharScenePercent' => (int) VwSetting::getValue('production_main_char_scene_percent', 70),
            'supportingCharScenePercent' => (int) VwSetting::getValue('production_supporting_char_scene_percent', 40),
            'characterTracking' => in_array($productionType, $narrativeTypes) ? 'narrative' : 'literal',
            'transitionScenes' => true,
            'shotDecomposition' => in_array($productionType, $this->getSettingAsArray('production_auto_multishot_types')),
        ];
    }

    /**
     * Check if a feature should auto-enable for a production type.
     *
     * @param string $productionType The production type
     * @param string $feature The feature to check ('multiShotMode', 'characterBible', etc.)
     * @return bool Whether the feature should auto-enable
     */
    public function shouldAutoEnable(string $productionType, string $feature): bool
    {
        $features = $this->getAutoFeatures($productionType);
        return ($features[$feature] ?? 'manual') === 'auto';
    }

    /**
     * Get the main character scene percentage for a production type.
     */
    public function getMainCharScenePercent(string $productionType): int
    {
        $rules = $this->getIntelligenceRules($productionType);
        return $rules['mainCharScenePercent'] ?? 70;
    }

    /**
     * Get the supporting character scene percentage for a production type.
     */
    public function getSupportingCharScenePercent(string $productionType): int
    {
        $rules = $this->getIntelligenceRules($productionType);
        return $rules['supportingCharScenePercent'] ?? 40;
    }

    /**
     * Check if narrative tracking should be used for a production type.
     */
    public function usesNarrativeTracking(string $productionType): bool
    {
        $rules = $this->getIntelligenceRules($productionType);
        return ($rules['characterTracking'] ?? 'literal') === 'narrative';
    }

    /**
     * Check if single-person portrait enforcement is enabled.
     */
    public function enforcesSinglePersonPortrait(): bool
    {
        return VwSetting::getValue('production_portrait_single_person', true) === true
            || VwSetting::getValue('production_portrait_single_person', 'true') === 'true';
    }

    /**
     * Get all settings needed for VideoWizard initialization.
     * Call this when production type is selected to get all auto-activation settings.
     *
     * @param string $productionType The selected production type
     * @param string|null $subType The selected sub-type
     * @return array Complete settings for VideoWizard
     */
    public function getWizardSettings(string $productionType, ?string $subType = null): array
    {
        $features = $this->getAutoFeatures($productionType, $subType);
        $intelligence = $this->getIntelligenceRules($productionType);

        return [
            'features' => $features,
            'intelligence' => $intelligence,
            'autoActivate' => [
                'multiShotMode' => ($features['multiShotMode'] ?? 'manual') === 'auto',
                'characterBible' => ($features['characterBible'] ?? 'manual') === 'auto',
                'locationBible' => ($features['locationBible'] ?? 'manual') === 'auto',
                'styleBible' => ($features['styleBible'] ?? 'manual') === 'auto',
            ],
            'scenePercentages' => [
                'main' => $intelligence['mainCharScenePercent'] ?? 70,
                'supporting' => $intelligence['supportingCharScenePercent'] ?? 40,
            ],
            'characterTracking' => $intelligence['characterTracking'] ?? 'literal',
            'singlePersonPortrait' => $this->enforcesSinglePersonPortrait(),
        ];
    }

    /**
     * Apply auto-activation rules to VideoWizard state.
     * Returns the modifications that should be made to the wizard state.
     *
     * @param string $productionType The selected production type
     * @param array $currentState The current wizard state
     * @return array Modifications to apply to wizard state
     */
    public function getStateModifications(string $productionType, array $currentState = []): array
    {
        $settings = $this->getWizardSettings($productionType);
        $modifications = [];

        // Multi-Shot Mode
        if ($settings['autoActivate']['multiShotMode']) {
            $modifications['multiShotMode'] = [
                'enabled' => true,
                'reason' => "Auto-enabled for {$productionType} production type",
            ];
        }

        // Character Bible
        if ($settings['autoActivate']['characterBible']) {
            $modifications['characterBible'] = [
                'enabled' => true,
                'autoDetect' => true,
                'reason' => "Auto-enabled for {$productionType} production type",
            ];
        }

        // Location Bible
        if ($settings['autoActivate']['locationBible']) {
            $modifications['locationBible'] = [
                'enabled' => true,
                'autoDetect' => true,
                'reason' => "Auto-enabled for {$productionType} production type",
            ];
        }

        // Style Bible
        if ($settings['autoActivate']['styleBible']) {
            $modifications['styleBible'] = [
                'enabled' => true,
                'reason' => "Auto-enabled for {$productionType} production type",
            ];
        }

        // Intelligence settings
        $modifications['intelligence'] = [
            'mainCharScenePercent' => $settings['scenePercentages']['main'],
            'supportingCharScenePercent' => $settings['scenePercentages']['supporting'],
            'characterTracking' => $settings['characterTracking'],
            'singlePersonPortrait' => $settings['singlePersonPortrait'],
        ];

        Log::info('ProductionIntelligence: State modifications calculated', [
            'productionType' => $productionType,
            'modifications' => array_keys($modifications),
        ]);

        return $modifications;
    }

    /**
     * Get production types config from config.php.
     */
    protected function getProductionTypesConfig(): array
    {
        if ($this->productionTypesConfig === null) {
            $this->productionTypesConfig = config('appvideowizard.production_types', []);
        }
        return $this->productionTypesConfig;
    }

    /**
     * Get a setting value as an array (for comma-separated lists).
     */
    protected function getSettingAsArray(string $slug): array
    {
        $value = VwSetting::getValue($slug, '');
        if (empty($value)) {
            return [];
        }
        return array_map('trim', explode(',', $value));
    }

    /**
     * Clear cached config (useful for testing or when config changes).
     */
    public function clearCache(): void
    {
        $this->productionTypesConfig = null;
    }
}
