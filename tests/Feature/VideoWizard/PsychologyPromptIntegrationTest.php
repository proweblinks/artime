<?php

namespace Tests\Feature\VideoWizard;

use Tests\TestCase;
use Modules\AppVideoWizard\Services\StructuredPromptBuilderService;

/**
 * Integration tests for Phase 23: Character Psychology Bible
 *
 * Verifies that psychology layer, mise-en-scene, and continuity anchors
 * are properly integrated into the prompt generation pipeline.
 *
 * Key requirements tested:
 * - Physical manifestations (jaw, brow, posture) instead of emotion labels
 * - Bible defining_features appear in psychology expressions (INF-02)
 * - Shot type affects psychology emphasis (close-up = face, wide = body)
 * - No FACS AU codes (research showed they don't work for image models)
 */
class PsychologyPromptIntegrationTest extends TestCase
{
    protected StructuredPromptBuilderService $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new StructuredPromptBuilderService();
    }

    /** @test */
    public function test_build_includes_psychology_layer_when_emotion_specified()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'A woman sits alone at a cafe table',
            'emotion' => 'anxiety',
            'emotion_intensity' => 'moderate',
        ]);

        $creative = $result['creative_prompt'];

        $this->assertArrayHasKey('psychology_layer', $creative);
        $this->assertNotEmpty($creative['psychology_layer']);
    }

    /** @test */
    public function test_psychology_layer_contains_physical_manifestations_not_labels()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'A man receives bad news',
            'emotion' => 'suppressed_anger',
            'shot_type' => 'close-up',
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        // Should contain physical descriptions (jaw, brow, etc.)
        $expressionStr = $psychology['expression'] ?? '';
        $this->assertStringContainsString('jaw', strtolower($expressionStr),
            'Expression should describe physical manifestation like jaw tension');

        // Should NOT contain emotion labels
        $this->assertStringNotContainsString('angry', strtolower($expressionStr),
            'Expression should not use emotion label');

        // Should NOT contain FACS AU codes (research: image models don't understand them)
        $this->assertStringNotContainsString('AU', $expressionStr,
            'Expression should not contain FACS AU codes');
    }

    /** @test */
    public function test_bible_defining_features_appear_in_psychology_expression()
    {
        $characterBible = [
            'enabled' => true,
            'characters' => [
                [
                    'id' => 'char_1',
                    'name' => 'Marcus',
                    'defining_features' => ['distinctive scar above left eyebrow', 'strong jaw'],
                ],
            ],
        ];

        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Marcus reacts to the news',
            'emotion' => 'suppressed_anger',
            'shot_type' => 'close-up',
            'character_bible' => $characterBible,
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];
        $expressionStr = $psychology['expression'] ?? '';

        // INF-02: Bible defining_features should appear in expression
        $this->assertStringContainsString('scar', strtolower($expressionStr),
            'Bible defining_features (scar) should appear in close-up expression');
    }

    /** @test */
    public function test_close_up_emphasizes_face_over_body()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Character reacts to news',
            'emotion' => 'grief',
            'shot_type' => 'close-up',
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        $this->assertNotEmpty($psychology['expression'] ?? '',
            'Close-up should include facial expression');

        // Close-up should NOT include body language (emphasis on face)
        $this->assertEmpty($psychology['body_language'] ?? '',
            'Close-up should not include body language');
    }

    /** @test */
    public function test_wide_shot_emphasizes_body_over_face()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Character walks through empty room',
            'emotion' => 'grief',
            'shot_type' => 'wide',
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        $this->assertNotEmpty($psychology['body_language'] ?? '',
            'Wide shot should include body language');

        // Wide shot should NOT include face expression (emphasis on body)
        $this->assertEmpty($psychology['expression'] ?? '',
            'Wide shot should not include facial expression');
    }

    /** @test */
    public function test_mise_en_scene_overlay_modifies_environment()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Office interior during tense meeting',
            'emotion' => 'tension',
            'tension_level' => 8,
        ]);

        $creative = $result['creative_prompt'];

        $this->assertArrayHasKey('mise_en_scene_overlay', $creative);
        $this->assertNotEmpty($creative['mise_en_scene_overlay']);

        // Should contain emotional lighting/atmosphere descriptors
        $overlay = $creative['mise_en_scene_overlay'];
        $this->assertArrayHasKey('emotional_lighting', $overlay);
        $this->assertArrayHasKey('emotional_atmosphere', $overlay);
    }

    /** @test */
    public function test_subtext_layer_creates_body_betrays_face()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Character pretends to be calm',
            'emotion' => 'forced_composure',
            'subtext' => [
                'surface' => 'forced_composure',
                'true' => 'anxiety',
                'leakage' => 0.4,
            ],
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        $this->assertArrayHasKey('subtext', $psychology);
        $this->assertArrayHasKey('surface', $psychology['subtext']);
        $this->assertArrayHasKey('body', $psychology['subtext']);
        $this->assertArrayHasKey('leakage', $psychology['subtext']);

        // Verify three-layer structure (surface emotion, leakage, body)
        $this->assertNotEmpty($psychology['subtext']['surface']);
        $this->assertNotEmpty($psychology['subtext']['body']);
    }

    /** @test */
    public function test_continuity_anchors_included_with_character_bible()
    {
        $characterBible = [
            'enabled' => true,
            'characters' => [
                [
                    'id' => 'char_1',
                    'name' => 'Elena',
                    'wardrobe' => [
                        'outfit' => 'red wool scarf over charcoal peacoat',
                        'colors' => 'red, charcoal gray',
                    ],
                    'hair' => [
                        'color' => 'dark brown',
                        'style' => 'messy waves',
                        'length' => 'past shoulders',
                    ],
                ],
            ],
        ];

        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Elena walks through the park',
            'character_bible' => $characterBible,
            'shot_index' => 0,
        ]);

        $creative = $result['creative_prompt'];

        $this->assertArrayHasKey('continuity_anchors', $creative);
    }

    /** @test */
    public function test_continuity_anchors_include_bible_wardrobe_details()
    {
        $characterBible = [
            'enabled' => true,
            'characters' => [
                [
                    'id' => 'char_1',
                    'name' => 'Elena',
                    'wardrobe' => [
                        'outfit' => 'red wool scarf over charcoal peacoat',
                    ],
                ],
            ],
        ];

        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Elena at the cafe',
            'character_bible' => $characterBible,
            'shot_index' => 0,
        ]);

        $anchors = $result['creative_prompt']['continuity_anchors'] ?? '';

        // INF-02: Bible wardrobe should appear in continuity anchors
        $this->assertStringContainsString('scarf', strtolower($anchors),
            'Bible wardrobe details should appear in continuity anchors');
    }

    /** @test */
    public function test_no_psychology_layer_without_emotion()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'A peaceful landscape',
            // No emotion specified
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'] ?? [];

        $this->assertEmpty($psychology,
            'Psychology layer should be empty when no emotion is specified');
    }

    /** @test */
    public function test_medium_shot_includes_both_face_and_body()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Character standing in doorway',
            'emotion' => 'anxiety',
            'shot_type' => 'medium',
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        $this->assertNotEmpty($psychology['expression'] ?? '',
            'Medium shot should include facial expression');
        $this->assertNotEmpty($psychology['body_language'] ?? '',
            'Medium shot should include body language');
    }

    /** @test */
    public function test_extreme_close_up_includes_breath_micro()
    {
        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Character eyes in extreme detail',
            'emotion' => 'anxiety',
            'shot_type' => 'extreme-close-up',
        ]);

        $psychology = $result['creative_prompt']['psychology_layer'];

        $this->assertNotEmpty($psychology['breath_micro'] ?? '',
            'Extreme close-up should include breath micro-expressions');
    }

    /** @test */
    public function test_scene_dna_path_also_includes_psychology_layer()
    {
        // Test that buildCreativePromptFromSceneDNA also integrates psychology
        $sceneDNA = [
            'enabled' => true,
            'scenes' => [
                0 => [
                    'sceneIndex' => 0,
                    'visualDescription' => 'Character in dramatic lighting',
                    'emotion' => 'suppressed_anger',
                    'emotion_intensity' => 'intense',
                    'characters' => [
                        [
                            'id' => 'char_1',
                            'name' => 'John',
                            'defining_features' => ['scar on chin'],
                        ],
                    ],
                    'location' => [
                        'name' => 'Dark alley',
                        'timeOfDay' => 'night',
                    ],
                ],
            ],
        ];

        $result = $this->builder->build([
            'visual_mode' => 'cinematic-realistic',
            'scene_description' => 'Test scene',
            'scene_dna' => $sceneDNA,
            'scene_index' => 0,
            'shot_type' => 'close-up',
        ]);

        $creative = $result['creative_prompt'];

        $this->assertArrayHasKey('psychology_layer', $creative);
        $this->assertNotEmpty($creative['psychology_layer']['expression'] ?? '');
    }
}
