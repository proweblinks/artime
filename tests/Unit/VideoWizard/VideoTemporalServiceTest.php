<?php

use Modules\AppVideoWizard\Services\VideoTemporalService;

beforeEach(function () {
    $this->service = new VideoTemporalService();
});

describe('VideoTemporalService', function () {

    describe('TEMPORAL_BEAT_GUIDELINES', function () {

        test('contains all four action types', function () {
            $types = array_keys(VideoTemporalService::TEMPORAL_BEAT_GUIDELINES);

            expect($types)->toContain('simple_action');
            expect($types)->toContain('complex_motion');
            expect($types)->toContain('emotional_beat');
            expect($types)->toContain('camera_movement');
        });

        test('each action type has min_duration, max_duration, and examples', function () {
            foreach (VideoTemporalService::TEMPORAL_BEAT_GUIDELINES as $type => $data) {
                expect($data)->toHaveKeys(['min_duration', 'max_duration', 'examples']);
                expect($data['min_duration'])->toBeInt()->toBeGreaterThan(0);
                expect($data['max_duration'])->toBeInt()->toBeGreaterThanOrEqual($data['min_duration']);
                expect($data['examples'])->toBeArray()->not->toBeEmpty();
            }
        });

        test('simple_action has 2-3 second range', function () {
            $simple = VideoTemporalService::TEMPORAL_BEAT_GUIDELINES['simple_action'];

            expect($simple['min_duration'])->toBe(2);
            expect($simple['max_duration'])->toBe(3);
        });

        test('complex_motion has 4-5 second range', function () {
            $complex = VideoTemporalService::TEMPORAL_BEAT_GUIDELINES['complex_motion'];

            expect($complex['min_duration'])->toBe(4);
            expect($complex['max_duration'])->toBe(5);
        });

    });

    describe('MAX_ACTIONS_PER_DURATION', function () {

        test('has defined thresholds at 5, 10, and 15 seconds', function () {
            $thresholds = array_keys(VideoTemporalService::MAX_ACTIONS_PER_DURATION);

            expect($thresholds)->toContain(5);
            expect($thresholds)->toContain(10);
            expect($thresholds)->toContain(15);
        });

        test('5 seconds allows max 2 actions', function () {
            expect(VideoTemporalService::MAX_ACTIONS_PER_DURATION[5])->toBe(2);
        });

        test('10 seconds allows max 4 actions', function () {
            expect(VideoTemporalService::MAX_ACTIONS_PER_DURATION[10])->toBe(4);
        });

    });

    describe('buildTemporalBeats', function () {

        test('formats beats correctly with time ranges', function () {
            $beats = [
                ['action' => 'character turns head left', 'duration' => 2],
                ['action' => 'eyes widen in recognition', 'duration' => 3],
            ];

            $result = $this->service->buildTemporalBeats($beats, 10);

            expect($result)->toContain('[00:00-00:02]');
            expect($result)->toContain('[00:02-00:05]');
            expect($result)->toContain('character turns head left');
            expect($result)->toContain('eyes widen in recognition');
        });

        test('returns empty string for empty beats array', function () {
            $result = $this->service->buildTemporalBeats([], 10);

            expect($result)->toBe('');
        });

        test('ends with period', function () {
            $beats = [
                ['action' => 'single action', 'duration' => 2],
            ];

            $result = $this->service->buildTemporalBeats($beats, 5);

            expect($result)->toEndWith('.');
        });

        test('skips beats with empty actions', function () {
            $beats = [
                ['action' => 'valid action', 'duration' => 2],
                ['action' => '', 'duration' => 2],
                ['action' => 'another valid', 'duration' => 2],
            ];

            $result = $this->service->buildTemporalBeats($beats, 10);

            expect($result)->toContain('valid action');
            expect($result)->toContain('another valid');
            // Time should be continuous (no gap)
            expect($result)->toContain('[00:00-00:02]');
            expect($result)->toContain('[00:02-00:04]');
        });

    });

    describe('validateBeatsForDuration', function () {

        test('rejects overpacked clip with too many actions', function () {
            $beats = [
                ['action' => 'action 1', 'duration' => 1],
                ['action' => 'action 2', 'duration' => 1],
                ['action' => 'action 3', 'duration' => 1],
                ['action' => 'action 4', 'duration' => 1],
                ['action' => 'action 5', 'duration' => 1],
            ];

            $result = $this->service->validateBeatsForDuration($beats, 5);

            expect($result['valid'])->toBeFalse();
            expect($result['warnings'])->not->toBeEmpty();
            expect($result['warnings'][0])->toContain('Too many actions');
        });

        test('accepts valid beat count', function () {
            $beats = [
                ['action' => 'action 1', 'duration' => 2],
                ['action' => 'action 2', 'duration' => 3],
            ];

            $result = $this->service->validateBeatsForDuration($beats, 5);

            expect($result['valid'])->toBeTrue();
            expect($result['warnings'])->toBeEmpty();
        });

        test('warns when beat durations exceed total', function () {
            $beats = [
                ['action' => 'long action', 'duration' => 10],
            ];

            $result = $this->service->validateBeatsForDuration($beats, 5);

            expect($result['valid'])->toBeFalse();
            expect(implode(' ', $result['warnings']))->toContain('exceed');
        });

    });

    describe('suggestBeatDuration', function () {

        test('returns correct value for simple_action', function () {
            $result = $this->service->suggestBeatDuration('simple_action');

            // Midpoint of 2-3 is 2.5, floor = 2
            expect($result)->toBe(2);
        });

        test('returns correct value for complex_motion', function () {
            $result = $this->service->suggestBeatDuration('complex_motion');

            // Midpoint of 4-5 is 4.5, floor = 4
            expect($result)->toBe(4);
        });

        test('returns correct value for emotional_beat', function () {
            $result = $this->service->suggestBeatDuration('emotional_beat');

            // Midpoint of 3-4 is 3.5, floor = 3
            expect($result)->toBe(3);
        });

        test('returns correct value for camera_movement', function () {
            $result = $this->service->suggestBeatDuration('camera_movement');

            // Midpoint of 3-8 is 5.5, floor = 5
            expect($result)->toBe(5);
        });

        test('returns default for unknown action type', function () {
            $result = $this->service->suggestBeatDuration('unknown_type');

            expect($result)->toBe(2);
        });

    });

    describe('formatTimeRange', function () {

        test('formats seconds under one minute correctly', function () {
            $result = $this->service->formatTimeRange(0, 3);

            expect($result)->toBe('[00:00-00:03]');
        });

        test('handles minutes correctly', function () {
            $result = $this->service->formatTimeRange(55, 65);

            expect($result)->toBe('[00:55-01:05]');
        });

        test('formats multi-minute ranges', function () {
            $result = $this->service->formatTimeRange(120, 180);

            expect($result)->toBe('[02:00-03:00]');
        });

        test('pads single digits with zeros', function () {
            $result = $this->service->formatTimeRange(5, 9);

            expect($result)->toBe('[00:05-00:09]');
        });

    });

    describe('beats do not exceed total duration', function () {

        test('beats are capped at total duration', function () {
            $beats = [
                ['action' => 'action 1', 'duration' => 5],
                ['action' => 'action 2', 'duration' => 5],
                ['action' => 'action 3', 'duration' => 5],
            ];

            $result = $this->service->buildTemporalBeats($beats, 10);

            // Should stop at 10 seconds, not continue to 15
            expect($result)->not->toContain('[00:10-00:15]');
            // First two actions should fit
            expect($result)->toContain('[00:00-00:05]');
            expect($result)->toContain('[00:05-00:10]');
        });

    });

    describe('classifyAction', function () {

        test('classifies dolly as camera_movement', function () {
            $result = $this->service->classifyAction('dolly in slowly');

            expect($result)->toBe('camera_movement');
        });

        test('classifies walk as complex_motion', function () {
            $result = $this->service->classifyAction('character walks across room');

            expect($result)->toBe('complex_motion');
        });

        test('classifies realization as emotional_beat', function () {
            $result = $this->service->classifyAction('dawning realization');

            expect($result)->toBe('emotional_beat');
        });

        test('classifies unknown as simple_action', function () {
            $result = $this->service->classifyAction('blinks twice');

            expect($result)->toBe('simple_action');
        });

    });

});
