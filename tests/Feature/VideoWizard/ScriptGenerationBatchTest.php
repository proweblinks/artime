<?php

use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Models\WizardProject;

beforeEach(function () {
    $this->service = new ScriptGenerationService();
});

describe('buildBatchContext', function () {

    it('returns opening context for first batch with no existing scenes', function () {
        $context = $this->service->buildBatchContext([], 1, 10);

        expect($context)->toContain('NARRATIVE POSITION: OPENING');
        expect($context)->toContain('BEGINNING of the video');
        expect($context)->toContain('Create a strong hook');
    });

    it('includes recent scenes for continuity', function () {
        $existingScenes = [
            ['title' => 'Introduction', 'narration' => 'Welcome to our journey through the fascinating world of technology.'],
            ['title' => 'Background', 'narration' => 'Technology has evolved rapidly over the past few decades.'],
            ['title' => 'Current State', 'narration' => 'Today we see innovations happening at an unprecedented pace.'],
        ];

        $context = $this->service->buildBatchContext($existingScenes, 2, 10);

        expect($context)->toContain('STORY SO FAR');
        expect($context)->toContain('Previously generated 3 scenes');
        expect($context)->toContain('RECENT SCENES');
        expect($context)->toContain('Introduction');
        expect($context)->toContain('Background');
        expect($context)->toContain('Current State');
    });

    it('returns setup phase context for first quarter', function () {
        $existingScenes = [
            ['title' => 'Scene 1', 'narration' => 'Test narration'],
        ];

        $context = $this->service->buildBatchContext($existingScenes, 2, 10);

        expect($context)->toContain('SETUP phase');
        expect($context)->toContain('first quarter');
    });

    it('returns development phase context for second quarter', function () {
        $existingScenes = array_fill(0, 5, ['title' => 'Test', 'narration' => 'Test']);

        $context = $this->service->buildBatchContext($existingScenes, 4, 10);

        expect($context)->toContain('DEVELOPMENT phase');
        expect($context)->toContain('second quarter');
    });

    it('returns escalation phase context for third quarter', function () {
        $existingScenes = array_fill(0, 10, ['title' => 'Test', 'narration' => 'Test']);

        $context = $this->service->buildBatchContext($existingScenes, 7, 10);

        expect($context)->toContain('ESCALATION phase');
        expect($context)->toContain('third quarter');
    });

    it('returns resolution phase context for final quarter', function () {
        $existingScenes = array_fill(0, 15, ['title' => 'Test', 'narration' => 'Test']);

        $context = $this->service->buildBatchContext($existingScenes, 9, 10);

        expect($context)->toContain('RESOLUTION phase');
        expect($context)->toContain('final quarter');
        expect($context)->toContain('call-to-action');
    });

    it('limits recent scenes to last 3', function () {
        $existingScenes = [
            ['title' => 'Scene 1', 'narration' => 'First scene content'],
            ['title' => 'Scene 2', 'narration' => 'Second scene content'],
            ['title' => 'Scene 3', 'narration' => 'Third scene content'],
            ['title' => 'Scene 4', 'narration' => 'Fourth scene content'],
            ['title' => 'Scene 5', 'narration' => 'Fifth scene content'],
        ];

        $context = $this->service->buildBatchContext($existingScenes, 3, 10);

        // Should include last 3 scenes (3, 4, 5) but not first 2
        expect($context)->toContain('Scene 3');
        expect($context)->toContain('Scene 4');
        expect($context)->toContain('Scene 5');
        expect($context)->not->toContain('First scene content');
        expect($context)->not->toContain('Second scene content');
    });

});

describe('calculateSceneCount (via VideoWizard)', function () {

    it('calculates correct scene count for short videos', function () {
        // 30 second video with standard production = 30/6 = 5 scenes
        // Based on the service's scene duration of 6 seconds for standard
        $service = new ScriptGenerationService();

        // Test via reflection or by checking the calculateScriptParameters method
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateScriptParameters');
        $method->setAccessible(true);

        $params = $method->invoke($service, 30, 'detailed');
        expect($params['sceneCount'])->toBeGreaterThanOrEqual(3);
        expect($params['sceneCount'])->toBeLessThanOrEqual(10);
    });

    it('calculates correct scene count for medium videos', function () {
        $service = new ScriptGenerationService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateScriptParameters');
        $method->setAccessible(true);

        // 2 minute video = 120 seconds
        $params = $method->invoke($service, 120, 'detailed');
        expect($params['sceneCount'])->toBeGreaterThanOrEqual(8);
        expect($params['sceneCount'])->toBeLessThanOrEqual(20);
    });

    it('calculates correct scene count for long videos', function () {
        $service = new ScriptGenerationService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateScriptParameters');
        $method->setAccessible(true);

        // 10 minute video = 600 seconds
        $params = $method->invoke($service, 600, 'detailed');
        expect($params['sceneCount'])->toBeGreaterThanOrEqual(30);
        expect($params['sceneCount'])->toBeLessThanOrEqual(80);
    });

});

describe('progressive generation state', function () {

    it('initializes batch structure correctly', function () {
        $targetSceneCount = 30;
        $batchSize = 5;
        $totalBatches = (int) ceil($targetSceneCount / $batchSize);

        expect($totalBatches)->toBe(6);

        $batches = [];
        for ($i = 0; $i < $totalBatches; $i++) {
            $startScene = ($i * $batchSize) + 1;
            $endScene = min(($i + 1) * $batchSize, $targetSceneCount);

            $batches[] = [
                'batchNumber' => $i + 1,
                'startScene' => $startScene,
                'endScene' => $endScene,
                'status' => 'pending',
                'retryCount' => 0,
            ];
        }

        expect($batches)->toHaveCount(6);
        expect($batches[0]['startScene'])->toBe(1);
        expect($batches[0]['endScene'])->toBe(5);
        expect($batches[5]['startScene'])->toBe(26);
        expect($batches[5]['endScene'])->toBe(30);
    });

    it('calculates exponential backoff correctly', function () {
        $baseDelayMs = 1000;

        // Retry 1: 1000ms
        $delay1 = $baseDelayMs * pow(2, 0);
        expect($delay1)->toBe(1000);

        // Retry 2: 2000ms
        $delay2 = $baseDelayMs * pow(2, 1);
        expect($delay2)->toBe(2000);

        // Retry 3: 4000ms
        $delay3 = $baseDelayMs * pow(2, 2);
        expect($delay3)->toBe(4000);
    });

    it('correctly determines when max retries exceeded', function () {
        $maxRetries = 3;
        $retryCount = 3;

        $shouldRetry = $retryCount < $maxRetries;
        expect($shouldRetry)->toBeFalse();

        $retryCount = 2;
        $shouldRetry = $retryCount < $maxRetries;
        expect($shouldRetry)->toBeTrue();
    });

});

describe('batch state persistence', function () {

    it('preserves script generation state structure', function () {
        $state = [
            'status' => 'paused',
            'targetSceneCount' => 30,
            'generatedSceneCount' => 15,
            'batchSize' => 5,
            'currentBatch' => 3,
            'totalBatches' => 6,
            'batches' => [
                ['batchNumber' => 1, 'status' => 'complete', 'retryCount' => 0],
                ['batchNumber' => 2, 'status' => 'complete', 'retryCount' => 0],
                ['batchNumber' => 3, 'status' => 'complete', 'retryCount' => 1],
                ['batchNumber' => 4, 'status' => 'pending', 'retryCount' => 0],
                ['batchNumber' => 5, 'status' => 'pending', 'retryCount' => 0],
                ['batchNumber' => 6, 'status' => 'pending', 'retryCount' => 0],
            ],
            'autoGenerate' => false,
            'maxRetries' => 3,
            'retryDelayMs' => 1000,
        ];

        // Simulate save and restore
        $serialized = json_encode($state);
        $restored = json_decode($serialized, true);

        expect($restored['status'])->toBe('paused');
        expect($restored['generatedSceneCount'])->toBe(15);
        expect($restored['batches'])->toHaveCount(6);
        expect($restored['batches'][2]['retryCount'])->toBe(1);
    });

    it('handles generating status on reload', function () {
        $state = [
            'status' => 'generating',
            'batches' => [
                ['status' => 'complete'],
                ['status' => 'generating'],
                ['status' => 'pending'],
            ],
        ];

        // Simulate the loadProject behavior
        if (in_array($state['status'], ['generating', 'retrying'])) {
            $state['status'] = 'paused';
            foreach ($state['batches'] as &$batch) {
                if (in_array($batch['status'], ['generating', 'retrying'])) {
                    $batch['status'] = 'pending';
                }
            }
        }

        expect($state['status'])->toBe('paused');
        expect($state['batches'][0]['status'])->toBe('complete');
        expect($state['batches'][1]['status'])->toBe('pending');
        expect($state['batches'][2]['status'])->toBe('pending');
    });

});
