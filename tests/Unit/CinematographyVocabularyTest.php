<?php

use Modules\AppVideoWizard\Services\CinematographyVocabulary;

beforeEach(function () {
    $this->vocabulary = new CinematographyVocabulary();
});

describe('CinematographyVocabulary', function () {

    describe('LENS_PSYCHOLOGY', function () {

        test('includes all standard focal lengths', function () {
            $lenses = array_keys(CinematographyVocabulary::LENS_PSYCHOLOGY);

            expect($lenses)->toContain('24mm');
            expect($lenses)->toContain('35mm');
            expect($lenses)->toContain('50mm');
            expect($lenses)->toContain('85mm');
            expect($lenses)->toContain('135mm');
        });

        test('each lens has effect, psychology, and use_for', function () {
            foreach (CinematographyVocabulary::LENS_PSYCHOLOGY as $lens => $data) {
                expect($data)->toHaveKeys(['effect', 'psychology', 'use_for']);
                expect($data['effect'])->toBeString()->not->toBeEmpty();
                expect($data['psychology'])->toBeString()->not->toBeEmpty();
                expect($data['use_for'])->toBeArray()->not->toBeEmpty();
            }
        });

    });

    describe('getLensForShotType', function () {

        test('returns 85mm with psychology for close-up', function () {
            $result = $this->vocabulary->getLensForShotType('close-up');

            expect($result['focal_length'])->toBe('85mm');
            expect($result['psychology'])->toContain('intimacy');
            expect($result['effect'])->toContain('compression');
        });

        test('returns 24mm for wide shots', function () {
            $result = $this->vocabulary->getLensForShotType('wide');

            expect($result['focal_length'])->toBe('24mm');
        });

        test('returns 50mm for unknown shot types', function () {
            $result = $this->vocabulary->getLensForShotType('unknown-shot-type');

            expect($result['focal_length'])->toBe('50mm');
        });

        test('handles case insensitivity', function () {
            $result1 = $this->vocabulary->getLensForShotType('Close-Up');
            $result2 = $this->vocabulary->getLensForShotType('CLOSE-UP');

            expect($result1['focal_length'])->toBe('85mm');
            expect($result2['focal_length'])->toBe('85mm');
        });

    });

    describe('LIGHTING_RATIOS', function () {

        test('includes standard ratios', function () {
            $ratios = array_keys(CinematographyVocabulary::LIGHTING_RATIOS);

            expect($ratios)->toContain('1:1');
            expect($ratios)->toContain('2:1');
            expect($ratios)->toContain('4:1');
            expect($ratios)->toContain('8:1');
        });

        test('each ratio has numeric stops_difference', function () {
            foreach (CinematographyVocabulary::LIGHTING_RATIOS as $ratio => $data) {
                expect($data)->toHaveKey('stops_difference');
                expect($data['stops_difference'])->toBeInt();
            }
        });

        test('stops_difference values are correct', function () {
            expect(CinematographyVocabulary::LIGHTING_RATIOS['1:1']['stops_difference'])->toBe(0);
            expect(CinematographyVocabulary::LIGHTING_RATIOS['2:1']['stops_difference'])->toBe(1);
            expect(CinematographyVocabulary::LIGHTING_RATIOS['4:1']['stops_difference'])->toBe(2);
            expect(CinematographyVocabulary::LIGHTING_RATIOS['8:1']['stops_difference'])->toBe(3);
        });

    });

    describe('getRatioForMood', function () {

        test('returns 4:1 for dramatic mood', function () {
            $result = $this->vocabulary->getRatioForMood('dramatic');

            expect($result['ratio'])->toBe('4:1');
            expect($result['stops_difference'])->toBe(2);
        });

        test('returns 8:1 for noir mood', function () {
            $result = $this->vocabulary->getRatioForMood('noir');

            expect($result['ratio'])->toBe('8:1');
        });

        test('returns 1:1 for beauty mood', function () {
            $result = $this->vocabulary->getRatioForMood('beauty');

            expect($result['ratio'])->toBe('1:1');
        });

        test('defaults to 2:1 for unknown mood', function () {
            $result = $this->vocabulary->getRatioForMood('unknown-mood');

            expect($result['ratio'])->toBe('2:1');
        });

    });

    describe('COLOR_TEMPERATURES', function () {

        test('contains expected conditions', function () {
            $conditions = array_keys(CinematographyVocabulary::COLOR_TEMPERATURES);

            expect($conditions)->toContain('candlelight');
            expect($conditions)->toContain('tungsten');
            expect($conditions)->toContain('golden_hour');
            expect($conditions)->toContain('daylight');
            expect($conditions)->toContain('overcast');
            expect($conditions)->toContain('shade');
        });

        test('all temperatures are Kelvin values', function () {
            foreach (CinematographyVocabulary::COLOR_TEMPERATURES as $condition => $data) {
                expect($data['kelvin'])->toBeInt();
                expect($data['kelvin'])->toBeGreaterThan(1000);
                expect($data['kelvin'])->toBeLessThan(10000);
            }
        });

        test('temperatures are in correct order', function () {
            $temps = CinematographyVocabulary::COLOR_TEMPERATURES;

            expect($temps['candlelight']['kelvin'])->toBeLessThan($temps['tungsten']['kelvin']);
            expect($temps['tungsten']['kelvin'])->toBeLessThan($temps['daylight']['kelvin']);
            expect($temps['daylight']['kelvin'])->toBeLessThan($temps['shade']['kelvin']);
        });

    });

    describe('getTemperatureDescription', function () {

        test('returns formatted string for golden_hour', function () {
            $result = $this->vocabulary->getTemperatureDescription('golden_hour');

            expect($result)->toContain('3500K');
            expect($result)->toContain('golden hour');
        });

        test('returns formatted string for daylight', function () {
            $result = $this->vocabulary->getTemperatureDescription('daylight');

            expect($result)->toContain('5600K');
        });

        test('defaults to daylight for unknown condition', function () {
            $result = $this->vocabulary->getTemperatureDescription('unknown');

            expect($result)->toContain('5600K');
        });

    });

    describe('FRAMING_GEOMETRY', function () {

        test('has thirds positions', function () {
            expect(CinematographyVocabulary::FRAMING_GEOMETRY)->toHaveKey('thirds');
            expect(CinematographyVocabulary::FRAMING_GEOMETRY['thirds'])->toBeArray();
            expect(CinematographyVocabulary::FRAMING_GEOMETRY['thirds'])->toContain('left third intersection');
            expect(CinematographyVocabulary::FRAMING_GEOMETRY['thirds'])->toContain('center frame');
        });

        test('has frame percentages', function () {
            expect(CinematographyVocabulary::FRAMING_GEOMETRY)->toHaveKey('frame_percentages');
            expect(CinematographyVocabulary::FRAMING_GEOMETRY['frame_percentages'])->toHaveKey(40);
        });

    });

    describe('buildFramingDescription', function () {

        test('includes percentage in output', function () {
            $result = $this->vocabulary->buildFramingDescription(40, 'center frame');

            expect($result)->toContain('40%');
            expect($result)->toContain('of frame');
        });

        test('includes position in output', function () {
            $result = $this->vocabulary->buildFramingDescription(40, 'left third intersection');

            expect($result)->toContain('left third intersection');
        });

        test('returns complete framing description', function () {
            $result = $this->vocabulary->buildFramingDescription(40, 'left third intersection');

            expect($result)->toBe('subject occupies 40% of frame, positioned at left third intersection');
        });

        test('clamps percentage to valid range', function () {
            $result1 = $this->vocabulary->buildFramingDescription(5, 'center frame');
            $result2 = $this->vocabulary->buildFramingDescription(100, 'center frame');

            expect($result1)->toContain('10%');
            expect($result2)->toContain('90%');
        });

        test('defaults to center frame for invalid position', function () {
            $result = $this->vocabulary->buildFramingDescription(50, 'invalid position');

            expect($result)->toContain('center frame');
        });

    });

    describe('buildLightingDescription', function () {

        test('combines ratio and temperature', function () {
            $result = $this->vocabulary->buildLightingDescription('4:1', 'daylight');

            expect($result)->toContain('5600K');
            expect($result)->toContain('-2 stops');
        });

    });

});
