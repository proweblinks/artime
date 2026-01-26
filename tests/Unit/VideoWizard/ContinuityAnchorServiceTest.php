<?php

use Modules\AppVideoWizard\Services\ContinuityAnchorService;

beforeEach(function () {
    $this->service = new ContinuityAnchorService();
});

describe('ContinuityAnchorService', function () {

    describe('ANCHOR_PRIORITY', function () {

        test('has primary, secondary, and tertiary levels', function () {
            $priorities = array_keys(ContinuityAnchorService::ANCHOR_PRIORITY);

            expect($priorities)->toContain('primary');
            expect($priorities)->toContain('secondary');
            expect($priorities)->toContain('tertiary');
        });

        test('primary level contains face and hair', function () {
            $primary = ContinuityAnchorService::ANCHOR_PRIORITY['primary'];

            expect($primary)->toHaveKey('face');
            expect($primary)->toHaveKey('hair');
        });

        test('secondary level contains wardrobe, accessories, makeup', function () {
            $secondary = ContinuityAnchorService::ANCHOR_PRIORITY['secondary'];

            expect($secondary)->toHaveKey('wardrobe');
            expect($secondary)->toHaveKey('accessories');
            expect($secondary)->toHaveKey('makeup');
        });

        test('tertiary level contains posture, props, lighting_position', function () {
            $tertiary = ContinuityAnchorService::ANCHOR_PRIORITY['tertiary'];

            expect($tertiary)->toHaveKey('posture');
            expect($tertiary)->toHaveKey('props');
            expect($tertiary)->toHaveKey('lighting_position');
        });

    });

    describe('extractAnchorsFromPrompt', function () {

        test('finds wardrobe details with color and material', function () {
            $prompt = 'A woman wearing a red wool scarf draped over her left shoulder, standing in a cafe.';

            $anchors = $this->service->extractAnchorsFromPrompt($prompt, 'char_1');

            expect($anchors)->toHaveKey('wardrobe');
            expect(strtolower($anchors['wardrobe']))->toContain('red');
            expect(strtolower($anchors['wardrobe']))->toContain('wool');
            expect(strtolower($anchors['wardrobe']))->toContain('scarf');
        });

        test('finds hair description with color and style', function () {
            $prompt = 'A woman with dark brown hair in messy waves, looking thoughtful.';

            $anchors = $this->service->extractAnchorsFromPrompt($prompt, 'char_1');

            expect($anchors)->toHaveKey('hair');
            expect(strtolower($anchors['hair']))->toContain('dark');
            expect(strtolower($anchors['hair']))->toContain('hair');
        });

        test('finds accessories with material and item', function () {
            $prompt = 'She is wearing silver hoop earrings and a leather bag strap across her chest.';

            $anchors = $this->service->extractAnchorsFromPrompt($prompt, 'char_1');

            expect($anchors)->toHaveKey('accessories');
            expect(strtolower($anchors['accessories']))->toContain('silver');
            expect(strtolower($anchors['accessories']))->toContain('earrings');
        });

        test('returns empty anchors for prompt without anchor-like details', function () {
            $prompt = 'A person standing in a park on a sunny day.';

            $anchors = $this->service->extractAnchorsFromPrompt($prompt, 'char_1');

            expect($anchors)->toBeEmpty();
        });

    });

    describe('applyAnchorsToPrompt', function () {

        test('adds CONTINUITY ANCHORS block to prompt', function () {
            $basePrompt = 'A woman walking down the street.';
            $anchors = [
                'wardrobe' => 'red wool scarf loosely draped over left shoulder',
                'hair' => 'dark brown waves falling past shoulders',
            ];

            $result = $this->service->applyAnchorsToPrompt($basePrompt, $anchors, 'secondary');

            expect($result)->toContain('CONTINUITY ANCHORS (MUST MATCH previous shots)');
            expect($result)->toContain('WARDROBE:');
            expect($result)->toContain('HAIR:');
        });

        test('respects priority levels - primary always included', function () {
            $basePrompt = 'A woman in a cafe.';
            $anchors = [
                'hair' => 'dark brown waves',           // primary
                'face' => 'angular jawline',           // primary
                'wardrobe' => 'red scarf',             // secondary
                'posture' => 'leaning forward',        // tertiary
            ];

            // Primary priority should only include primary anchors
            $result = $this->service->applyAnchorsToPrompt($basePrompt, $anchors, 'primary');

            expect($result)->toContain('HAIR:');
            expect($result)->toContain('FACE:');
            expect($result)->not->toContain('WARDROBE:');
            expect($result)->not->toContain('POSTURE:');
        });

        test('secondary priority includes primary and secondary anchors', function () {
            $basePrompt = 'A woman in a cafe.';
            $anchors = [
                'hair' => 'dark brown waves',           // primary
                'wardrobe' => 'red scarf',             // secondary
                'posture' => 'leaning forward',        // tertiary
            ];

            $result = $this->service->applyAnchorsToPrompt($basePrompt, $anchors, 'secondary');

            expect($result)->toContain('HAIR:');
            expect($result)->toContain('WARDROBE:');
            expect($result)->not->toContain('POSTURE:');
        });

        test('returns original prompt when no anchors provided', function () {
            $basePrompt = 'A woman walking down the street.';

            $result = $this->service->applyAnchorsToPrompt($basePrompt, [], 'secondary');

            expect($result)->toBe($basePrompt);
        });

    });

    describe('buildAnchorDescription', function () {

        test('builds anchor string from character Bible data', function () {
            $character = [
                'id' => 'char_123',
                'name' => 'Sarah',
                'wardrobe' => [
                    'outfit' => 'red wool scarf over charcoal peacoat',
                    'colors' => 'red, charcoal',
                    'style' => 'casual elegant',
                ],
                'hair' => [
                    'color' => 'dark brown',
                    'texture' => 'wavy',
                    'length' => 'shoulder-length',
                    'style' => 'loose waves',
                ],
                'accessories' => ['silver hoop earrings', 'leather messenger bag'],
            ];

            $result = $this->service->buildAnchorDescription($character, 0);

            expect($result)->toBeString()->not->toBeEmpty();
            expect($result)->toContain('WARDROBE:');
            expect($result)->toContain('HAIR:');
            expect($result)->toContain('ACCESSORIES:');
        });

        test('includes physical distinctive features in face anchor', function () {
            $character = [
                'id' => 'char_123',
                'physical' => [
                    'distinctive_features' => 'strong jawline, intense blue eyes',
                ],
            ];

            $result = $this->service->buildAnchorDescription($character, 0);

            expect($result)->toContain('FACE:');
            expect(strtolower($result))->toContain('jawline');
        });

        test('returns empty string for character with no anchor data', function () {
            $character = [
                'id' => 'char_123',
                'name' => 'Unknown',
            ];

            $result = $this->service->buildAnchorDescription($character, 0);

            expect($result)->toBe('');
        });

    });

    describe('detectAnchorConflicts', function () {

        test('identifies wardrobe change as conflict', function () {
            $existingAnchors = [
                'wardrobe' => 'red wool scarf over charcoal peacoat',
            ];

            $newAnchors = [
                'wardrobe' => 'blue denim jacket with white t-shirt',
            ];

            $conflicts = $this->service->detectAnchorConflicts($newAnchors, $existingAnchors);

            expect($conflicts)->not->toBeEmpty();
            expect($conflicts[0]['category'])->toBe('wardrobe');
            expect($conflicts[0])->toHaveKey('existing');
            expect($conflicts[0])->toHaveKey('new');
            expect($conflicts[0])->toHaveKey('severity');
        });

        test('identifies hair color change as critical conflict', function () {
            $existingAnchors = [
                'hair' => 'dark brown wavy hair',
            ];

            $newAnchors = [
                'hair' => 'bright blonde straight hair',
            ];

            $conflicts = $this->service->detectAnchorConflicts($newAnchors, $existingAnchors);

            expect($conflicts)->not->toBeEmpty();
            expect($conflicts[0]['category'])->toBe('hair');
            // Hair is primary anchor, should be high or critical severity
            expect(in_array($conflicts[0]['severity'], ['critical', 'high']))->toBeTrue();
        });

        test('returns empty array when anchors match', function () {
            $anchors = [
                'wardrobe' => 'red wool scarf',
                'hair' => 'dark brown waves',
            ];

            $conflicts = $this->service->detectAnchorConflicts($anchors, $anchors);

            expect($conflicts)->toBeEmpty();
        });

        test('ignores categories not present in both anchor sets', function () {
            $existingAnchors = [
                'wardrobe' => 'red scarf',
            ];

            $newAnchors = [
                'accessories' => 'silver earrings',
            ];

            $conflicts = $this->service->detectAnchorConflicts($newAnchors, $existingAnchors);

            expect($conflicts)->toBeEmpty();
        });

    });

    describe('getAnchorsForCharacter', function () {

        test('returns stored anchors for character', function () {
            $character = [
                'id' => 'char_123',
                'wardrobe' => ['outfit' => 'red scarf'],
                'hair' => ['color' => 'brown'],
            ];

            // Build and store anchors
            $this->service->buildAnchorDescription($character, 0);

            // Retrieve anchors
            $anchors = $this->service->getAnchorsForCharacter('char_123', 1, []);

            expect($anchors)->not->toBeEmpty();
        });

        test('returns anchors from provided storage', function () {
            $storedAnchors = [
                'char_123' => [
                    'anchors' => [
                        'wardrobe' => 'red scarf',
                        'hair' => 'brown waves',
                    ],
                    'extracted_from_shot' => 0,
                ],
            ];

            $anchors = $this->service->getAnchorsForCharacter('char_123', 1, $storedAnchors);

            expect($anchors)->toHaveKey('wardrobe');
            expect($anchors)->toHaveKey('hair');
        });

        test('returns empty array for unknown character', function () {
            $anchors = $this->service->getAnchorsForCharacter('unknown_char', 0, []);

            expect($anchors)->toBeEmpty();
        });

    });

    describe('storeAnchors and getAllStoredAnchors', function () {

        test('stores and retrieves anchors correctly', function () {
            $anchors = [
                'wardrobe' => 'red scarf',
                'hair' => 'brown waves',
            ];

            $this->service->storeAnchors('char_1', 0, $anchors);

            $stored = $this->service->getAllStoredAnchors();

            expect($stored)->toHaveKey('char_1');
            expect($stored['char_1']['anchors'])->toBe($anchors);
            expect($stored['char_1']['scene_index'])->toBe(0);
        });

        test('clearStoredAnchors removes all stored data', function () {
            $this->service->storeAnchors('char_1', 0, ['wardrobe' => 'scarf']);
            $this->service->storeAnchors('char_2', 0, ['hair' => 'brown']);

            $this->service->clearStoredAnchors();

            $stored = $this->service->getAllStoredAnchors();
            expect($stored)->toBeEmpty();
        });

    });

});
