<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwSetting;

class VwSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // =============================================
            // SHOT INTELLIGENCE SETTINGS
            // =============================================
            [
                'slug' => 'shot_intelligence_enabled',
                'name' => 'Enable AI Shot Intelligence',
                'category' => 'shot_intelligence',
                'description' => 'When enabled, AI will analyze each scene and dynamically determine the optimal number of shots, their types, and durations. When disabled, uses manual shot count selection.',
                'value_type' => 'boolean',
                'value' => 'true',
                'default_value' => 'true',
                'input_type' => 'checkbox',
                'input_help' => 'AI analyzes scene content (dialogue, action, emotion) to determine shot breakdown',
                'icon' => 'fa-solid fa-brain',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'shot_min_per_scene',
                'name' => 'Minimum Shots Per Scene',
                'category' => 'shot_intelligence',
                'description' => 'The minimum number of shots that can be created for any scene, regardless of AI recommendation.',
                'value_type' => 'integer',
                'value' => '1',
                'default_value' => '1',
                'min_value' => 1,
                'max_value' => 10,
                'input_type' => 'number',
                'input_help' => 'Even simple scenes need at least this many shots',
                'icon' => 'fa-solid fa-arrow-down',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'shot_max_per_scene',
                'name' => 'Maximum Shots Per Scene',
                'category' => 'shot_intelligence',
                'description' => 'The maximum number of shots allowed per scene. Set higher for complex action sequences.',
                'value_type' => 'integer',
                'value' => '20',
                'default_value' => '20',
                'min_value' => 3,
                'max_value' => 50,
                'input_type' => 'number',
                'input_help' => 'Complex scenes with lots of action or dialogue may need more shots',
                'icon' => 'fa-solid fa-arrow-up',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'shot_default_count',
                'name' => 'Default Shot Count',
                'category' => 'shot_intelligence',
                'description' => 'Default number of shots when AI intelligence is disabled or as a fallback.',
                'value_type' => 'integer',
                'value' => '3',
                'default_value' => '3',
                'min_value' => 1,
                'max_value' => 10,
                'input_type' => 'number',
                'input_help' => 'Used when manual mode is selected or AI cannot determine shot count',
                'icon' => 'fa-solid fa-hashtag',
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'shot_ai_prompt',
                'name' => 'Shot Analysis AI Prompt',
                'category' => 'shot_intelligence',
                'description' => 'The prompt template used by AI to analyze scenes and determine shot breakdown. Variables: {{scene_description}}, {{narration}}, {{duration}}, {{mood}}, {{genre}}',
                'value_type' => 'string',
                'value' => 'Analyze this scene and determine the optimal cinematic shot breakdown.

SCENE: {{scene_description}}
NARRATION: {{narration}}
DURATION: {{duration}} seconds
MOOD: {{mood}}
GENRE: {{genre}}

Determine:
1. Optimal number of shots (consider pacing, dialogue, action)
2. For each shot: type (establishing, medium, close-up, etc.), recommended duration, camera movement
3. Which shots need lip-sync (for dialogue with visible speakers)

Return JSON:
{
  "shotCount": number,
  "reasoning": "brief explanation",
  "shots": [
    {
      "type": "shot_type_slug",
      "duration": seconds,
      "purpose": "why this shot",
      "cameraMovement": "movement description",
      "needsLipSync": boolean
    }
  ]
}',
                'default_value' => null,
                'input_type' => 'textarea',
                'input_help' => 'Customize how AI analyzes scenes. Use {{variables}} for dynamic content.',
                'icon' => 'fa-solid fa-message',
                'is_system' => true,
                'sort_order' => 5,
            ],

            // =============================================
            // ANIMATION MODEL SETTINGS
            // =============================================
            [
                'slug' => 'animation_default_model',
                'name' => 'Default Animation Model',
                'category' => 'animation',
                'description' => 'The default video animation model to use for standard shots.',
                'value_type' => 'string',
                'value' => 'minimax',
                'default_value' => 'minimax',
                'allowed_values' => ['minimax', 'multitalk'],
                'input_type' => 'select',
                'input_help' => 'MiniMax for general animation, Multitalk for lip-sync dialogue',
                'icon' => 'fa-solid fa-wand-magic-sparkles',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'animation_minimax_durations',
                'name' => 'MiniMax Duration Options',
                'category' => 'animation',
                'description' => 'Available duration options for MiniMax video generation (in seconds).',
                'value_type' => 'json',
                'value' => '[5, 6, 10]',
                'default_value' => '[5, 6, 10]',
                'input_type' => 'json_editor',
                'input_help' => 'Array of durations in seconds. MiniMax supports 5s, 6s, and 10s.',
                'icon' => 'fa-solid fa-clock',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'animation_minimax_default_duration',
                'name' => 'MiniMax Default Duration',
                'category' => 'animation',
                'description' => 'Default duration for MiniMax video generation.',
                'value_type' => 'integer',
                'value' => '6',
                'default_value' => '6',
                'allowed_values' => [5, 6, 10],
                'input_type' => 'select',
                'input_help' => 'Standard duration when not specified by AI or user',
                'icon' => 'fa-solid fa-stopwatch',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'animation_multitalk_durations',
                'name' => 'Multitalk Duration Options',
                'category' => 'animation',
                'description' => 'Available duration options for Multitalk lip-sync video generation (in seconds).',
                'value_type' => 'json',
                'value' => '[5, 10, 15, 20]',
                'default_value' => '[5, 10, 15, 20]',
                'input_type' => 'json_editor',
                'input_help' => 'Array of durations in seconds. Multitalk supports longer durations for dialogue.',
                'icon' => 'fa-solid fa-clock',
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'animation_multitalk_default_duration',
                'name' => 'Multitalk Default Duration',
                'category' => 'animation',
                'description' => 'Default duration for Multitalk lip-sync video generation.',
                'value_type' => 'integer',
                'value' => '10',
                'default_value' => '10',
                'allowed_values' => [5, 10, 15, 20],
                'input_type' => 'select',
                'input_help' => 'Standard duration for dialogue scenes',
                'icon' => 'fa-solid fa-stopwatch',
                'is_system' => true,
                'sort_order' => 5,
            ],
            [
                'slug' => 'animation_auto_select_model',
                'name' => 'Auto-Select Model by Content',
                'category' => 'animation',
                'description' => 'Automatically select Multitalk for dialogue shots and MiniMax for others.',
                'value_type' => 'boolean',
                'value' => 'true',
                'default_value' => 'true',
                'input_type' => 'checkbox',
                'input_help' => 'When enabled, shots with dialogue automatically use Multitalk if available',
                'icon' => 'fa-solid fa-robot',
                'is_system' => true,
                'sort_order' => 6,
            ],

            // =============================================
            // DURATION SETTINGS
            // =============================================
            [
                'slug' => 'duration_shot_min',
                'name' => 'Minimum Shot Duration',
                'category' => 'duration',
                'description' => 'Minimum duration for any individual shot (in seconds).',
                'value_type' => 'integer',
                'value' => '3',
                'default_value' => '3',
                'min_value' => 1,
                'max_value' => 10,
                'input_type' => 'number',
                'input_help' => 'Very short shots (under 3s) may feel too rushed',
                'icon' => 'fa-solid fa-hourglass-start',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'duration_shot_max',
                'name' => 'Maximum Shot Duration',
                'category' => 'duration',
                'description' => 'Maximum duration for any individual shot (in seconds).',
                'value_type' => 'integer',
                'value' => '20',
                'default_value' => '20',
                'min_value' => 5,
                'max_value' => 60,
                'input_type' => 'number',
                'input_help' => 'Long shots are supported by Multitalk for extended dialogue',
                'icon' => 'fa-solid fa-hourglass-end',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'duration_shot_default',
                'name' => 'Default Shot Duration',
                'category' => 'duration',
                'description' => 'Default duration when not specified by AI or user.',
                'value_type' => 'integer',
                'value' => '6',
                'default_value' => '6',
                'min_value' => 3,
                'max_value' => 20,
                'input_type' => 'number',
                'input_help' => '6 seconds is optimal for most shot types',
                'icon' => 'fa-solid fa-stopwatch',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'duration_per_shot_enabled',
                'name' => 'Enable Per-Shot Duration',
                'category' => 'duration',
                'description' => 'Allow different durations for each shot within a scene (vs. uniform duration).',
                'value_type' => 'boolean',
                'value' => 'true',
                'default_value' => 'true',
                'input_type' => 'checkbox',
                'input_help' => 'When enabled, each shot can have its own duration based on content',
                'icon' => 'fa-solid fa-sliders',
                'is_system' => true,
                'sort_order' => 4,
            ],

            // =============================================
            // SCENE PROCESSING SETTINGS
            // =============================================
            [
                'slug' => 'scene_duration_min',
                'name' => 'Minimum Scene Duration',
                'category' => 'scene',
                'description' => 'Minimum duration for any scene (in seconds).',
                'value_type' => 'integer',
                'value' => '10',
                'default_value' => '10',
                'min_value' => 5,
                'max_value' => 30,
                'input_type' => 'number',
                'input_help' => 'Scenes shorter than this may not have enough content',
                'icon' => 'fa-solid fa-minimize',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'scene_duration_max',
                'name' => 'Maximum Scene Duration',
                'category' => 'scene',
                'description' => 'Maximum duration for any scene (in seconds).',
                'value_type' => 'integer',
                'value' => '60',
                'default_value' => '60',
                'min_value' => 15,
                'max_value' => 300,
                'input_type' => 'number',
                'input_help' => 'Very long scenes should be split into multiple scenes',
                'icon' => 'fa-solid fa-maximize',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'scene_duration_default',
                'name' => 'Default Scene Duration',
                'category' => 'scene',
                'description' => 'Default duration when generating new scenes.',
                'value_type' => 'integer',
                'value' => '15',
                'default_value' => '15',
                'min_value' => 10,
                'max_value' => 60,
                'input_type' => 'number',
                'input_help' => '15 seconds provides good balance of content and pacing',
                'icon' => 'fa-solid fa-clock',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'scene_auto_decompose',
                'name' => 'Auto-Decompose Scenes',
                'category' => 'scene',
                'description' => 'Automatically decompose scenes into shots when entering animation phase.',
                'value_type' => 'boolean',
                'value' => 'false',
                'default_value' => 'false',
                'input_type' => 'checkbox',
                'input_help' => 'When enabled, all scenes are automatically decomposed without manual trigger',
                'icon' => 'fa-solid fa-wand-magic',
                'is_system' => true,
                'sort_order' => 4,
            ],

            // =============================================
            // GENERAL WIZARD SETTINGS
            // =============================================
            [
                'slug' => 'frame_chaining_enabled',
                'name' => 'Enable Frame Chaining',
                'category' => 'general',
                'description' => 'Use last frame of previous shot as starting frame for next shot (ensures visual continuity).',
                'value_type' => 'boolean',
                'value' => 'true',
                'default_value' => 'true',
                'input_type' => 'checkbox',
                'input_help' => 'Recommended for professional-looking shot transitions',
                'icon' => 'fa-solid fa-link',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'style_bible_enforcement',
                'name' => 'Style Bible Enforcement Level',
                'category' => 'general',
                'description' => 'How strictly to apply Style Bible settings to generated content.',
                'value_type' => 'string',
                'value' => 'moderate',
                'default_value' => 'moderate',
                'allowed_values' => ['strict', 'moderate', 'loose'],
                'input_type' => 'select',
                'input_help' => 'Strict: exact match required, Moderate: balanced, Loose: general guidance',
                'icon' => 'fa-solid fa-palette',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'parallel_generation_limit',
                'name' => 'Parallel Generation Limit',
                'category' => 'general',
                'description' => 'Maximum number of shots/images to generate in parallel.',
                'value_type' => 'integer',
                'value' => '3',
                'default_value' => '3',
                'min_value' => 1,
                'max_value' => 10,
                'input_type' => 'number',
                'input_help' => 'Higher values speed up generation but may hit API rate limits',
                'icon' => 'fa-solid fa-layer-group',
                'is_system' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($settings as $setting) {
            VwSetting::updateOrCreate(
                ['slug' => $setting['slug']],
                $setting
            );
        }

        // Clear cache after seeding
        VwSetting::clearCache();
    }
}
