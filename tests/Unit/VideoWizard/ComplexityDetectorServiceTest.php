<?php

use Modules\AppVideoWizard\Services\ComplexityDetectorService;
use Modules\AppVideoWizard\Services\PromptTemplateLibrary;

beforeEach(function () {
    $this->service = new ComplexityDetectorService();
});

describe('ComplexityDetectorService', function () {

    describe('single character shots', function () {

        test('single_character_not_complex', function () {
            // 1 character, known shot type, single emotion = NOT complex
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeFalse();
            expect($result['scores']['multi_character'])->toBe(0.0);
        });

        test('single_character_with_known_emotion_has_low_combination_score', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // close-up:grief is in COMMON_COMBINATIONS
            expect($result['scores']['combination_novelty'])->toBe(0.0);
        });

    });

    describe('multi-character complexity', function () {

        test('two_characters_triggers_complexity', function () {
            // 2 characters = complex (multi_character >= 0.7)
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeTrue();
            expect($result['scores']['multi_character'])->toBe(0.7);
        });

        test('three_plus_characters_always_complex', function () {
            // 3+ characters = always complex, regardless of other scores
            $shotData = [
                'characters' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                    ['name' => 'C'],
                ],
                'shot_type' => 'wide',
                'emotion' => 'isolation',  // even with low other scores
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeTrue();
            expect($result['scores']['multi_character'])->toBe(1.0);
        });

        test('four_characters_triggers_complexity', function () {
            $shotData = [
                'characters' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                    ['name' => 'C'],
                    ['name' => 'D'],
                ],
                'shot_type' => 'wide',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeTrue();
            expect($result['scores']['multi_character'])->toBe(1.0);
        });

        test('character_count_field_also_works', function () {
            // Test that character_count field is respected when characters array is empty
            $shotData = [
                'character_count' => 3,
                'shot_type' => 'wide',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeTrue();
        });

    });

    describe('emotional complexity', function () {

        test('subtext_triggers_complexity', function () {
            // Non-empty subtext field adds emotional complexity
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
                'subtext' => 'She hides her fear behind forced composure',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Subtext alone adds 0.5 to emotional_complexity
            expect($result['scores']['emotional_complexity'])->toBeGreaterThanOrEqual(0.5);
        });

        test('high_tension_triggers_complexity', function () {
            // tension_level >= 8 adds to emotional complexity
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'anxiety',
                'tension_level' => 9,
                'subtext' => 'Hidden agenda',  // 0.5
                'emotions' => ['anxiety', 'hidden_joy'],  // 0.3
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Subtext (0.5) + multiple emotions (0.3) + high tension (0.2) = 1.0 (capped)
            expect($result['scores']['emotional_complexity'])->toBe(1.0);
            expect($result['is_complex'])->toBeTrue();
        });

        test('multiple_emotions_triggers_complexity', function () {
            // 2+ emotions in emotions array adds complexity
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'medium',
                'emotion' => 'tension',
                'emotions' => ['anxiety', 'hidden_joy', 'desperation'],
                'subtext' => 'Complex internal state',  // 0.5
            ];

            $result = $this->service->calculateComplexity($shotData);

            // subtext (0.5) + multiple emotions (0.3) = 0.8
            expect($result['scores']['emotional_complexity'])->toBeGreaterThanOrEqual(0.7);
            expect($result['is_complex'])->toBeTrue();
        });

        test('emotional_complexity_without_subtext_can_still_trigger', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'fear',
                'emotions' => ['fear', 'hope', 'determination'],
                'tension_level' => 10,
            ];

            $result = $this->service->calculateComplexity($shotData);

            // multiple emotions (0.3) + high tension (0.2) = 0.5
            expect($result['scores']['emotional_complexity'])->toBe(0.5);
        });

    });

    describe('environment novelty', function () {

        test('novel_environment_triggers_complexity', function () {
            // Environment not in template keywords = high novelty
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'wide',
                'emotion' => 'isolation',
                'environment' => 'bioluminescent alien landscape',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Novel environment should score high (0.9)
            expect($result['scores']['environment_novelty'])->toBeGreaterThanOrEqual(0.7);
            expect($result['is_complex'])->toBeTrue();
        });

        test('known_environment_not_complex', function () {
            // Environment matching template emphasis keywords = low novelty
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'medium',
                'emotion' => 'neutral',
                'environment' => 'office',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Known environment should score 0.0
            expect($result['scores']['environment_novelty'])->toBe(0.0);
        });

        test('partial_match_environment_scores_lower', function () {
            // Environment with partial match = moderate novelty
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'wide',
                'emotion' => 'peace',
                'environment' => 'abandoned warehouse with strange machinery',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // 'warehouse' is known, so partial match = 0.7 (1.0 - 0.3)
            expect($result['scores']['environment_novelty'])->toBe(0.7);
        });

        test('empty_environment_scores_zero', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['scores']['environment_novelty'])->toBe(0.0);
        });

    });

    describe('combination novelty', function () {

        test('common_combination_scores_zero', function () {
            // close-up:grief is a common combination
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['scores']['combination_novelty'])->toBe(0.0);
        });

        test('novel_shot_emotion_combination_triggers_complexity', function () {
            // extreme-close-up with 'whimsy' is not a common combination
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'extreme-close-up',
                'emotion' => 'whimsy',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Neither shot type nor emotion is common in combinations
            expect($result['scores']['combination_novelty'])->toBeGreaterThan(0.0);
        });

        test('no_emotion_scores_zero_combination', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['scores']['combination_novelty'])->toBe(0.0);
        });

    });

    describe('combined low scores not complex', function () {

        test('combined_low_scores_not_complex', function () {
            // Multiple low scores don't accidentally trigger complexity
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
                'environment' => 'bedroom',  // known environment
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeFalse();
            expect($result['total_score'])->toBeLessThan(0.6);
        });

        test('moderate_scores_across_dimensions_may_trigger', function () {
            // If enough moderate scores add up
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'detail',  // less common shot type
                'emotion' => 'nostalgia',  // not in common combinations
                'environment' => 'Victorian greenhouse',  // somewhat novel
                'emotions' => ['nostalgia', 'melancholy'],  // multiple emotions = 0.3
            ];

            $result = $this->service->calculateComplexity($shotData);

            // This may or may not trigger - just verifying the scoring works
            expect($result['total_score'])->toBeGreaterThan(0.0);
        });

    });

    describe('complexity reasons', function () {

        test('complexity_reasons_populated', function () {
            // complexity_reasons array contains human-readable strings
            $shotData = [
                'characters' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
                'shot_type' => 'wide',
                'emotion' => 'tension',
                'subtext' => 'Hidden agenda behind forced smiles',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['complexity_reasons'])->toBeArray();
            expect(count($result['complexity_reasons']))->toBeGreaterThan(0);

            // Should include multi-character reason
            $reasons = implode(' ', $result['complexity_reasons']);
            expect($reasons)->toContain('character');
        });

        test('reasons_include_three_plus_characters_message', function () {
            $shotData = [
                'characters' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
                'shot_type' => 'wide',
            ];

            $result = $this->service->calculateComplexity($shotData);

            $reasons = implode(' ', $result['complexity_reasons']);
            expect(strtolower($reasons))->toContain('three');
        });

        test('reasons_include_two_characters_message', function () {
            $shotData = [
                'characters' => [['name' => 'A'], ['name' => 'B']],
                'shot_type' => 'two-shot',
            ];

            $result = $this->service->calculateComplexity($shotData);

            $reasons = implode(' ', $result['complexity_reasons']);
            expect(strtolower($reasons))->toContain('two');
        });

        test('reasons_include_emotional_complexity_message', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
                'subtext' => 'Hidden pain beneath the surface',
                'emotions' => ['grief', 'guilt', 'longing'],
            ];

            $result = $this->service->calculateComplexity($shotData);

            $reasons = implode(' ', $result['complexity_reasons']);
            expect(strtolower($reasons))->toContain('emotion');
        });

        test('empty_reasons_for_simple_shot', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['complexity_reasons'])->toBeArray();
            // Simple shot should have few or no reasons
            expect(count($result['complexity_reasons']))->toBeLessThanOrEqual(1);
        });

    });

    describe('convenience methods', function () {

        test('isComplex_returns_boolean', function () {
            $simpleShot = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $complexShot = [
                'characters' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
                'shot_type' => 'wide',
            ];

            expect($this->service->isComplex($simpleShot))->toBeFalse();
            expect($this->service->isComplex($complexShot))->toBeTrue();
        });

        test('calculateComplexity_returns_proper_structure', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result)->toHaveKeys(['scores', 'total_score', 'is_complex', 'complexity_reasons']);
            expect($result['scores'])->toHaveKeys([
                'multi_character',
                'emotional_complexity',
                'environment_novelty',
                'combination_novelty',
                'token_budget_risk',
            ]);
            expect($result['total_score'])->toBeFloat();
            expect($result['is_complex'])->toBeBool();
            expect($result['complexity_reasons'])->toBeArray();
        });

    });

    describe('token budget risk', function () {

        test('simple_shot_within_budget', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Simple shot should have low token budget risk
            expect($result['scores']['token_budget_risk'])->toBeLessThan(0.7);
        });

        test('complex_shot_higher_budget_risk', function () {
            $shotData = [
                'characters' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
                'shot_type' => 'wide',
                'emotion' => 'tension',
                'emotions' => ['anxiety', 'fear', 'determination'],
                'subtext' => 'Complex layered emotions',
                'environment' => 'abandoned industrial complex',
                'action' => 'walking slowly',
            ];

            $result = $this->service->calculateComplexity($shotData);

            // Complex shot should have higher token budget risk
            expect($result['scores']['token_budget_risk'])->toBeGreaterThan(0.0);
        });

    });

    describe('edge cases', function () {

        test('empty_shot_data_handles_gracefully', function () {
            $result = $this->service->calculateComplexity([]);

            expect($result['is_complex'])->toBeFalse();
            expect($result['total_score'])->toBe(0.0);
        });

        test('null_values_in_shot_data_handled', function () {
            $shotData = [
                'characters' => null,
                'shot_type' => null,
                'emotion' => null,
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['is_complex'])->toBeFalse();
        });

        test('whitespace_only_subtext_not_counted', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'grief',
                'subtext' => '   ',  // whitespace only
            ];

            $result = $this->service->calculateComplexity($shotData);

            expect($result['scores']['emotional_complexity'])->toBe(0.0);
        });

    });

});
