<?php

use Modules\AppVideoWizard\Services\MiseEnSceneService;

beforeEach(function () {
    $this->service = new MiseEnSceneService();
});

describe('MiseEnSceneService', function () {

    describe('MISE_EN_SCENE_MAPPINGS', function () {

        test('contains at least 8 emotional states', function () {
            $emotions = array_keys(MiseEnSceneService::MISE_EN_SCENE_MAPPINGS);

            expect($emotions)->toHaveCount(8);
            expect($emotions)->toContain('anxiety');
            expect($emotions)->toContain('tension');
            expect($emotions)->toContain('peace');
            expect($emotions)->toContain('isolation');
            expect($emotions)->toContain('danger');
            expect($emotions)->toContain('hope');
            expect($emotions)->toContain('intimacy');
            expect($emotions)->toContain('chaos');
        });

        test('each emotion has lighting, colors, space, and atmosphere', function () {
            foreach (MiseEnSceneService::MISE_EN_SCENE_MAPPINGS as $emotion => $data) {
                expect($data)->toHaveKeys(['lighting', 'colors', 'space', 'atmosphere']);
                expect($data['lighting'])->toBeString()->not->toBeEmpty();
                expect($data['colors'])->toBeString()->not->toBeEmpty();
                expect($data['space'])->toBeString()->not->toBeEmpty();
                expect($data['atmosphere'])->toBeString()->not->toBeEmpty();
            }
        });

    });

    describe('getMiseEnSceneForEmotion', function () {

        test('returns all components for valid emotion', function () {
            $result = $this->service->getMiseEnSceneForEmotion('anxiety');

            expect($result)->toHaveKeys(['lighting', 'colors', 'space', 'atmosphere']);
        });

        test('anxiety produces cramped and harsh shadow descriptors', function () {
            $result = $this->service->getMiseEnSceneForEmotion('anxiety');

            // User-observable: generated prompt will contain these descriptors
            expect(strtolower($result['space']))->toContain('cramped');
            expect(strtolower($result['lighting']))->toContain('shadow');
        });

        test('peace produces soft lighting and open space descriptors', function () {
            $result = $this->service->getMiseEnSceneForEmotion('peace');

            // User-observable: generated prompt will contain these descriptors
            expect(strtolower($result['lighting']))->toContain('soft');
            expect(strtolower($result['space']))->toContain('open');
        });

        test('tension produces dramatic lighting descriptors', function () {
            $result = $this->service->getMiseEnSceneForEmotion('tension');

            expect(strtolower($result['lighting']))->toContain('shadow');
            expect(strtolower($result['atmosphere']))->toContain('stillness');
        });

        test('returns neutral environment for unknown emotion', function () {
            $result = $this->service->getMiseEnSceneForEmotion('unknown-emotion-xyz');

            expect($result)->toHaveKeys(['lighting', 'colors', 'space', 'atmosphere']);
            expect(strtolower($result['lighting']))->toContain('balanced');
        });

        test('handles emotion aliases', function () {
            // "peaceful" should map to "peace"
            $peaceful = $this->service->getMiseEnSceneForEmotion('peaceful');
            $peace = $this->service->getMiseEnSceneForEmotion('peace');

            expect($peaceful)->toBe($peace);
        });

        test('handles case insensitivity', function () {
            $lower = $this->service->getMiseEnSceneForEmotion('anxiety');
            $upper = $this->service->getMiseEnSceneForEmotion('ANXIETY');
            $mixed = $this->service->getMiseEnSceneForEmotion('Anxiety');

            expect($lower)->toBe($upper);
            expect($lower)->toBe($mixed);
        });

    });

    describe('TENSION_SCALE', function () {

        test('contains levels 1 through 10', function () {
            $levels = array_keys(MiseEnSceneService::TENSION_SCALE);

            expect($levels)->toContain(1);
            expect($levels)->toContain(5);
            expect($levels)->toContain(10);
        });

        test('each level has space_modifier and light_modifier', function () {
            foreach (MiseEnSceneService::TENSION_SCALE as $level => $data) {
                expect($data)->toHaveKeys(['space_modifier', 'light_modifier']);
                expect($data['space_modifier'])->toBeString()->not->toBeEmpty();
                expect($data['light_modifier'])->toBeString()->not->toBeEmpty();
            }
        });

    });

    describe('getSpacialTension', function () {

        test('tension level 1 describes comfortable space', function () {
            $result = $this->service->getSpacialTension(1);

            expect(strtolower($result['space_modifier']))->toContain('comfortable');
        });

        test('tension level 10 describes claustrophobic or oppressive space', function () {
            $result = $this->service->getSpacialTension(10);

            $spaceModifier = strtolower($result['space_modifier']);
            $isOppressive = str_contains($spaceModifier, 'oppressive') ||
                            str_contains($spaceModifier, 'claustrophobic');

            expect($isOppressive)->toBeTrue();
        });

        test('tension level 7 describes claustrophobic space', function () {
            $result = $this->service->getSpacialTension(7);

            expect(strtolower($result['space_modifier']))->toContain('claustrophobic');
        });

        test('clamps values below 1 to level 1', function () {
            $result = $this->service->getSpacialTension(-5);

            expect(strtolower($result['space_modifier']))->toContain('comfortable');
        });

        test('clamps values above 10 to level 10', function () {
            $result = $this->service->getSpacialTension(15);

            expect(strtolower($result['space_modifier']))->toContain('oppressive');
        });

    });

    describe('buildEnvironmentalMood', function () {

        test('preserves base location identity', function () {
            $baseEnvironment = [
                'name' => 'Corporate Office',
                'description' => 'Modern glass tower interior with minimalist design',
                'type' => 'interior',
            ];

            $result = $this->service->buildEnvironmentalMood('anxiety', $baseEnvironment);

            expect($result['base_location'])->toBe('Corporate Office');
            expect($result['base_type'])->toBe('interior');
            expect($result['base_description'])->toContain('Modern glass tower');
        });

        test('adds emotional overlay elements', function () {
            $baseEnvironment = [
                'name' => 'Beach',
                'description' => 'Sandy coastline at sunset',
                'type' => 'exterior',
            ];

            $result = $this->service->buildEnvironmentalMood('peace', $baseEnvironment);

            expect($result['emotional_lighting'])->toContain('soft');
            expect($result['emotional_space'])->toContain('open');
        });

        test('creates combined description', function () {
            $baseEnvironment = [
                'name' => 'Forest',
                'description' => 'Dense woodland with tall pines',
            ];

            $result = $this->service->buildEnvironmentalMood('tension', $baseEnvironment);

            expect($result['combined_description'])->toContain('Dense woodland');
            expect($result['combined_description'])->toContain('Lighting:');
            expect($result['combined_description'])->toContain('Colors:');
        });

    });

    describe('blendEnvironments', function () {

        test('intensity 0.0 returns pure base environment', function () {
            $base = [
                'lighting' => 'bright daylight',
                'colors' => 'vibrant colors',
                'space' => 'wide open field',
                'atmosphere' => 'cheerful',
            ];
            $emotional = [
                'lighting' => 'dark shadows',
                'colors' => 'muted tones',
                'space' => 'cramped corner',
                'atmosphere' => 'tense',
            ];

            $result = $this->service->blendEnvironments($base, $emotional, 0.0);

            expect($result['lighting'])->toBe('bright daylight');
            expect($result['space'])->toBe('wide open field');
            expect($result['intensity'])->toBe(0.0);
        });

        test('intensity 1.0 returns pure emotional environment', function () {
            $base = [
                'lighting' => 'bright daylight',
                'colors' => 'vibrant colors',
                'space' => 'wide open field',
                'atmosphere' => 'cheerful',
            ];
            $emotional = [
                'lighting' => 'dark shadows',
                'colors' => 'muted tones',
                'space' => 'cramped corner',
                'atmosphere' => 'tense',
            ];

            $result = $this->service->blendEnvironments($base, $emotional, 1.0);

            expect($result['lighting'])->toBe('dark shadows');
            expect($result['space'])->toBe('cramped corner');
            expect($result['intensity'])->toBe(1.0);
        });

        test('intensity 0.5 blends both environments', function () {
            $base = [
                'lighting' => 'bright daylight',
                'colors' => 'vibrant colors',
            ];
            $emotional = [
                'lighting' => 'dark shadows',
                'colors' => 'muted tones',
            ];

            $result = $this->service->blendEnvironments($base, $emotional, 0.5);

            expect($result['blended'])->toBeTrue();
            expect($result['intensity'])->toBe(0.5);
            // Blended should reference both
            expect($result['lighting'])->toContain('daylight');
        });

        test('clamps intensity to valid range', function () {
            $base = ['lighting' => 'base'];
            $emotional = ['lighting' => 'emotional'];

            $lowResult = $this->service->blendEnvironments($base, $emotional, -0.5);
            $highResult = $this->service->blendEnvironments($base, $emotional, 1.5);

            expect($lowResult['intensity'])->toBe(0.0);
            expect($highResult['intensity'])->toBe(1.0);
        });

    });

    describe('getAvailableEmotions', function () {

        test('returns all emotion types', function () {
            $emotions = $this->service->getAvailableEmotions();

            expect($emotions)->toBeArray();
            expect(count($emotions))->toBeGreaterThanOrEqual(8);
            expect($emotions)->toContain('anxiety');
            expect($emotions)->toContain('peace');
        });

    });

    describe('buildPromptBlock', function () {

        test('returns formatted mise-en-scene block', function () {
            $result = $this->service->buildPromptBlock('anxiety');

            expect($result)->toContain('[MISE-EN-SCENE:');
            expect($result)->toContain('Lighting:');
            expect($result)->toContain('Colors:');
            expect($result)->toContain('Space:');
            expect($result)->toContain('Atmosphere:');
        });

    });

});
