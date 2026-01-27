<?php

use Modules\AppVideoWizard\Services\CharacterPathService;

beforeEach(function () {
    $this->service = new CharacterPathService();
});

describe('CharacterPathService', function () {

    describe('CHARACTER_PATH_VOCABULARY', function () {

        test('contains all five movement categories', function () {
            $types = array_keys(CharacterPathService::CHARACTER_PATH_VOCABULARY);

            expect($types)->toContain('approach');
            expect($types)->toContain('retreat');
            expect($types)->toContain('stationary_motion');
            expect($types)->toContain('crossing');
            expect($types)->toContain('gestural');
        });

        test('each category has at least 3 variants', function () {
            foreach (CharacterPathService::CHARACTER_PATH_VOCABULARY as $type => $variants) {
                expect(count($variants))->toBeGreaterThanOrEqual(3, "Type '{$type}' needs at least 3 variants");
            }
        });

        test('approach includes toward_camera and diagonal_entry', function () {
            $approach = CharacterPathService::CHARACTER_PATH_VOCABULARY['approach'];

            expect($approach)->toHaveKeys(['toward_camera', 'diagonal_entry', 'lateral_approach']);
        });

        test('gestural includes parameter placeholders', function () {
            $gestural = CharacterPathService::CHARACTER_PATH_VOCABULARY['gestural'];

            // At least one gestural should have {direction} or {hand} placeholder
            $hasPlaceholder = false;
            foreach ($gestural as $description) {
                if (str_contains($description, '{') && str_contains($description, '}')) {
                    $hasPlaceholder = true;
                    break;
                }
            }

            expect($hasPlaceholder)->toBeTrue();
        });

    });

    describe('PATH_DURATION_ESTIMATES', function () {

        test('has estimates for all path types', function () {
            $pathTypes = array_keys(CharacterPathService::CHARACTER_PATH_VOCABULARY);

            foreach ($pathTypes as $type) {
                expect(CharacterPathService::PATH_DURATION_ESTIMATES)->toHaveKey($type);
            }
        });

        test('each estimate has min and max', function () {
            foreach (CharacterPathService::PATH_DURATION_ESTIMATES as $type => $estimate) {
                expect($estimate)->toHaveKeys(['min', 'max']);
                expect($estimate['min'])->toBeInt()->toBeGreaterThan(0);
                expect($estimate['max'])->toBeInt()->toBeGreaterThan($estimate['min']);
            }
        });

        test('gestural is fastest at 1-2 seconds', function () {
            $gestural = CharacterPathService::PATH_DURATION_ESTIMATES['gestural'];

            expect($gestural['min'])->toBe(1);
            expect($gestural['max'])->toBe(2);
        });

        test('crossing is longest at up to 6 seconds', function () {
            $crossing = CharacterPathService::PATH_DURATION_ESTIMATES['crossing'];

            expect($crossing['max'])->toBe(6);
        });

    });

    describe('buildCharacterPath', function () {

        test('returns path description for valid type and variant', function () {
            $result = $this->service->buildCharacterPath('approach', 'toward_camera');

            expect($result)->toContain('walks directly toward camera');
            expect($result)->toContain('growing larger in frame');
        });

        test('substitutes degrees parameter', function () {
            $result = $this->service->buildCharacterPath('stationary_motion', 'turn', [
                'degrees' => '45',
                'direction' => 'right',
            ]);

            expect($result)->toContain('45 degrees');
            expect($result)->toContain('right');
        });

        test('substitutes direction parameter', function () {
            $result = $this->service->buildCharacterPath('stationary_motion', 'lean', [
                'direction' => 'forward',
            ]);

            expect($result)->toContain('forward');
        });

        test('substitutes hand parameter', function () {
            $result = $this->service->buildCharacterPath('gestural', 'reach', [
                'direction' => 'upward',
                'hand' => 'left hand',
            ]);

            expect($result)->toContain('left hand');
            expect($result)->toContain('upward');
        });

        test('uses default parameters when not specified', function () {
            $result = $this->service->buildCharacterPath('stationary_motion', 'turn');

            // Should have defaults substituted (90 degrees, left)
            expect($result)->toContain('90 degrees');
            expect($result)->toContain('left');
        });

        test('returns fallback for unknown variant', function () {
            $result = $this->service->buildCharacterPath('approach', 'unknown_variant');

            expect($result)->toBe('performs movement');
        });

        test('handles case insensitivity', function () {
            $result = $this->service->buildCharacterPath('APPROACH', 'TOWARD_CAMERA');

            expect($result)->toContain('walks directly toward camera');
        });

    });

    describe('suggestPathForIntent', function () {

        test('enter_scene returns approach/diagonal_entry', function () {
            $result = $this->service->suggestPathForIntent('enter_scene');

            expect($result['path_type'])->toBe('approach');
            expect($result['variant'])->toBe('diagonal_entry');
        });

        test('leave_scene returns retreat/exit_frame', function () {
            $result = $this->service->suggestPathForIntent('leave_scene');

            expect($result['path_type'])->toBe('retreat');
            expect($result['variant'])->toBe('exit_frame');
        });

        test('confront returns approach/toward_camera', function () {
            $result = $this->service->suggestPathForIntent('confront');

            expect($result['path_type'])->toBe('approach');
            expect($result['variant'])->toBe('toward_camera');
        });

        test('greet returns gestural/embrace_open', function () {
            $result = $this->service->suggestPathForIntent('greet');

            expect($result['path_type'])->toBe('gestural');
            expect($result['variant'])->toBe('embrace_open');
        });

        test('handles intent aliases', function () {
            $enter = $this->service->suggestPathForIntent('enter');
            expect($enter['path_type'])->toBe('approach');

            $exit = $this->service->suggestPathForIntent('exit');
            expect($exit['path_type'])->toBe('retreat');
        });

        test('returns default for unknown intent', function () {
            $result = $this->service->suggestPathForIntent('unknown_intent_xyz');

            expect($result['path_type'])->toBe('stationary_motion');
            expect($result['variant'])->toBe('shift_weight');
        });

    });

    describe('estimatePathDuration', function () {

        test('returns min and max for approach', function () {
            $result = $this->service->estimatePathDuration('approach');

            expect($result)->toHaveKeys(['min', 'max']);
            expect($result['min'])->toBe(3);
            expect($result['max'])->toBe(5);
        });

        test('returns min and max for gestural', function () {
            $result = $this->service->estimatePathDuration('gestural');

            expect($result['min'])->toBe(1);
            expect($result['max'])->toBe(2);
        });

        test('returns default for unknown path type', function () {
            $result = $this->service->estimatePathDuration('unknown_type');

            expect($result['min'])->toBe(2);
            expect($result['max'])->toBe(4);
        });

    });

    describe('combinePathWithCamera', function () {

        test('produces coherent description when camera follows', function () {
            $result = $this->service->combinePathWithCamera(
                'walks directly toward camera',
                'push in slowly'
            );

            expect($result)->toContain('walks directly toward camera');
            expect($result)->toContain('camera');
            expect($result)->toContain('follow');
        });

        test('produces coherent description when camera contrasts', function () {
            $result = $this->service->combinePathWithCamera(
                'crosses frame from left to right',
                'static hold'
            );

            expect($result)->toContain('crosses frame');
            expect($result)->toContain('camera');
        });

        test('handles empty path description', function () {
            $result = $this->service->combinePathWithCamera('', 'slow pan right');

            expect($result)->toContain('subject visible');
            expect($result)->toContain('pan right');
        });

        test('handles empty camera movement', function () {
            $result = $this->service->combinePathWithCamera('walks toward camera', '');

            expect($result)->toBe('walks toward camera');
        });

        test('handles both empty', function () {
            $result = $this->service->combinePathWithCamera('', '');

            expect($result)->toContain('static camera');
        });

        test('detects tracking movement', function () {
            $result = $this->service->combinePathWithCamera(
                'strides purposefully forward',
                'tracking shot follows movement'
            );

            expect($result)->toContain('to follow');
        });

    });

    describe('getAvailablePathTypes', function () {

        test('returns all five path types', function () {
            $types = $this->service->getAvailablePathTypes();

            expect($types)->toHaveCount(5);
            expect($types)->toContain('approach');
            expect($types)->toContain('retreat');
            expect($types)->toContain('stationary_motion');
            expect($types)->toContain('crossing');
            expect($types)->toContain('gestural');
        });

    });

    describe('getVariantsForPathType', function () {

        test('returns variants for approach', function () {
            $variants = $this->service->getVariantsForPathType('approach');

            expect($variants)->toContain('toward_camera');
            expect($variants)->toContain('diagonal_entry');
        });

        test('returns empty array for unknown type', function () {
            $variants = $this->service->getVariantsForPathType('unknown_type');

            expect($variants)->toBeArray();
            expect($variants)->toBeEmpty();
        });

    });

    describe('buildPathFromIntent', function () {

        test('builds complete path from intent', function () {
            $result = $this->service->buildPathFromIntent('enter_scene');

            expect($result)->toContain('enters frame');
            expect($result)->toContain('diagonally');
        });

        test('accepts parameters for parameterized paths', function () {
            $result = $this->service->buildPathFromIntent('observe', [
                'degrees' => '180',
                'direction' => 'behind',
            ]);

            expect($result)->toContain('180 degrees');
            expect($result)->toContain('behind');
        });

    });

    describe('buildPromptBlock', function () {

        test('returns formatted path block with duration', function () {
            $result = $this->service->buildPromptBlock('approach', 'toward_camera');

            expect($result)->toContain('[CHARACTER-PATH:');
            expect($result)->toContain('approach/toward_camera');
            expect($result)->toContain('walks directly toward camera');
            expect($result)->toContain('3-5s');
        });

    });

    describe('validateVocabulary', function () {

        test('vocabulary structure is valid', function () {
            $result = $this->service->validateVocabulary();

            expect($result['valid'])->toBeTrue();
            expect($result['issues'])->toBeEmpty();
        });

    });

});
