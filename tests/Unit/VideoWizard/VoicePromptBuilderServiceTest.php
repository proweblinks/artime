<?php

use Modules\AppVideoWizard\Services\VoicePromptBuilderService;
use Modules\AppVideoWizard\Services\VoiceDirectionVocabulary;
use Modules\AppVideoWizard\Services\VoicePacingService;
use Modules\AppVideoWizard\Services\SpeechSegment;

beforeEach(function () {
    $this->voiceDirection = new VoiceDirectionVocabulary();
    $this->pacingService = new VoicePacingService();
    $this->service = new VoicePromptBuilderService($this->voiceDirection, $this->pacingService);
});

describe('VoicePromptBuilderService', function () {

    describe('AMBIENT_AUDIO_CUES constant', function () {

        test('contains all 8 expected scene types', function () {
            $sceneTypes = array_keys(VoicePromptBuilderService::AMBIENT_AUDIO_CUES);

            expect($sceneTypes)->toContain('intimate');
            expect($sceneTypes)->toContain('outdoor');
            expect($sceneTypes)->toContain('crowded');
            expect($sceneTypes)->toContain('tense');
            expect($sceneTypes)->toContain('storm');
            expect($sceneTypes)->toContain('night');
            expect($sceneTypes)->toContain('office');
            expect($sceneTypes)->toContain('vehicle');
            expect(count($sceneTypes))->toBe(8);
        });

        test('each cue has descriptive content', function () {
            foreach (VoicePromptBuilderService::AMBIENT_AUDIO_CUES as $type => $description) {
                expect($description)->toBeString();
                expect(strlen($description))->toBeGreaterThan(20);
            }
        });

    });

    describe('EMOTIONAL_ARC_PATTERNS constant', function () {

        test('contains all 6 expected arc types', function () {
            $arcTypes = array_keys(VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS);

            expect($arcTypes)->toContain('building');
            expect($arcTypes)->toContain('crashing');
            expect($arcTypes)->toContain('recovering');
            expect($arcTypes)->toContain('masking');
            expect($arcTypes)->toContain('revealing');
            expect($arcTypes)->toContain('confronting');
            expect(count($arcTypes))->toBe(6);
        });

        test('each arc has exactly 4 stages', function () {
            foreach (VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS as $type => $stages) {
                expect($stages)->toBeArray();
                expect(count($stages))->toBe(4);
            }
        });

        test('building arc has quiet to peak progression', function () {
            $building = VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS['building'];

            expect($building[0])->toBe('quiet');
            expect($building[1])->toBe('rising');
            expect($building[2])->toBe('intense');
            expect($building[3])->toBe('peak');
        });

        test('crashing arc has confident to collapsed progression', function () {
            $crashing = VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS['crashing'];

            expect($crashing[0])->toBe('confident');
            expect($crashing[1])->toBe('wavering');
            expect($crashing[2])->toBe('breaking');
            expect($crashing[3])->toBe('collapsed');
        });

    });

    describe('buildEnhancedVoicePrompt', function () {

        test('adds emotional direction for elevenlabs provider', function () {
            $segment = SpeechSegment::dialogue('ALICE', 'I thought you were gone forever.');
            $segment->emotion = 'grief';

            $result = $this->service->buildEnhancedVoicePrompt($segment, ['provider' => 'elevenlabs']);

            expect($result['text'])->toContain('[crying]');
            expect($result['text'])->toContain('I thought you were gone forever.');
        });

        test('returns unchanged text without emotion', function () {
            $segment = SpeechSegment::dialogue('BOB', 'The meeting starts at noon.');
            // No emotion set

            $result = $this->service->buildEnhancedVoicePrompt($segment, ['provider' => 'elevenlabs']);

            expect($result['text'])->toBe('The meeting starts at noon.');
            expect($result['instructions'])->toBe('');
        });

        test('uses provider specific tags for elevenlabs', function () {
            $segment = SpeechSegment::dialogue('ALICE', 'This is scary.');
            $segment->emotion = 'fear';

            $result = $this->service->buildEnhancedVoicePrompt($segment, ['provider' => 'elevenlabs']);

            // ElevenLabs uses [nervous] for fear
            expect($result['text'])->toContain('[nervous]');
        });

        test('openai provider returns instructions separately', function () {
            $segment = SpeechSegment::dialogue('ALICE', 'I miss you.');
            $segment->emotion = 'grief';

            $result = $this->service->buildEnhancedVoicePrompt($segment, ['provider' => 'openai']);

            // Text should NOT contain bracketed tags
            expect($result['text'])->toBe('I miss you.');
            expect($result['text'])->not->toContain('[');
            // Instructions should contain direction
            expect($result['instructions'])->toContain('sorrow');
        });

        test('includes ambient when requested', function () {
            $segment = SpeechSegment::dialogue('ALICE', 'Hello.');
            $segment->emotion = 'anxiety';

            $result = $this->service->buildEnhancedVoicePrompt($segment, [
                'provider' => 'elevenlabs',
                'includeAmbient' => true,
                'sceneType' => 'tense',
            ]);

            expect($result['ambient'])->toContain('silence');
            expect($result['ambient'])->toContain('anticipation');
        });

        test('includes arc position in instructions when provided', function () {
            $segment = SpeechSegment::dialogue('ALICE', 'Getting worried now.');
            $segment->emotion = 'anxiety';

            $result = $this->service->buildEnhancedVoicePrompt($segment, [
                'provider' => 'openai',
                'arcPosition' => 'rising',
            ]);

            expect($result['instructions'])->toContain('rising');
        });

    });

    describe('buildEmotionalArc', function () {

        test('assigns arc notes to segments', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Line one'),
                SpeechSegment::dialogue('ALICE', 'Line two'),
                SpeechSegment::dialogue('ALICE', 'Line three'),
                SpeechSegment::dialogue('ALICE', 'Line four'),
            ];

            $result = $this->service->buildEmotionalArc($segments, 'building');

            expect($result[0]->emotionalArcNote)->toBe('quiet');
            expect($result[3]->emotionalArcNote)->toBe('peak');
        });

        test('handles single segment', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Only line'),
            ];

            $result = $this->service->buildEmotionalArc($segments, 'building');

            expect($result[0]->emotionalArcNote)->toBe('quiet');
        });

        test('handles many segments with distribution', function () {
            // 8 segments should distribute across 4 arc stages
            $segments = [];
            for ($i = 0; $i < 8; $i++) {
                $segments[] = SpeechSegment::dialogue('ALICE', "Line {$i}");
            }

            $result = $this->service->buildEmotionalArc($segments, 'building');

            // First 2 segments: quiet (positions 0-1)
            expect($result[0]->emotionalArcNote)->toBe('quiet');
            expect($result[1]->emotionalArcNote)->toBe('quiet');
            // Next 2: rising (positions 2-3)
            expect($result[2]->emotionalArcNote)->toBe('rising');
            expect($result[3]->emotionalArcNote)->toBe('rising');
            // Next 2: intense (positions 4-5)
            expect($result[4]->emotionalArcNote)->toBe('intense');
            expect($result[5]->emotionalArcNote)->toBe('intense');
            // Last 2: peak (positions 6-7)
            expect($result[6]->emotionalArcNote)->toBe('peak');
            expect($result[7]->emotionalArcNote)->toBe('peak');
        });

        test('uses correct pattern for crashing arc', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Line one'),
                SpeechSegment::dialogue('ALICE', 'Line two'),
                SpeechSegment::dialogue('ALICE', 'Line three'),
                SpeechSegment::dialogue('ALICE', 'Line four'),
            ];

            $result = $this->service->buildEmotionalArc($segments, 'crashing');

            expect($result[0]->emotionalArcNote)->toBe('confident');
            expect($result[1]->emotionalArcNote)->toBe('wavering');
            expect($result[2]->emotionalArcNote)->toBe('breaking');
            expect($result[3]->emotionalArcNote)->toBe('collapsed');
        });

        test('handles empty segments array', function () {
            $result = $this->service->buildEmotionalArc([], 'building');

            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

    });

    describe('buildAmbientCue', function () {

        test('returns cue for known scene type', function () {
            $result = $this->service->buildAmbientCue('tense');

            expect($result)->toContain('silence');
            expect($result)->toContain('anticipation');
        });

        test('returns fallback for unknown scene type', function () {
            $result = $this->service->buildAmbientCue('unknown_type');

            // Should fall back to 'intimate'
            expect($result)->toBe(VoicePromptBuilderService::AMBIENT_AUDIO_CUES['intimate']);
            expect($result)->toContain('quiet room tone');
        });

        test('handles case insensitivity', function () {
            $result = $this->service->buildAmbientCue('OUTDOOR');

            expect($result)->toContain('wind');
            expect($result)->toContain('nature');
        });

    });

    describe('buildDialogueDirectionPrompt', function () {

        test('returns complete package', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Line one'),
                SpeechSegment::dialogue('BOB', 'Line two'),
            ];
            $segments[0]->emotion = 'anxiety';
            $segments[1]->emotion = 'fear';

            $result = $this->service->buildDialogueDirectionPrompt($segments, 'building', 'tense', 'elevenlabs');

            expect($result)->toHaveKey('segments');
            expect($result)->toHaveKey('arcSummary');
            expect($result)->toHaveKey('ambient');
            expect(count($result['segments']))->toBe(2);
            expect($result['arcSummary'])->toBeString();
            expect($result['ambient'])->toContain('silence');
        });

        test('applies emotional arc to all segments', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Start'),
                SpeechSegment::dialogue('ALICE', 'Middle'),
                SpeechSegment::dialogue('ALICE', 'End'),
            ];

            $result = $this->service->buildDialogueDirectionPrompt($segments, 'building', 'intimate', 'elevenlabs');

            // Segments should have arc notes applied
            expect($result['segments'][0]['segment']->emotionalArcNote)->toBe('quiet');
        });

        test('includes arc summary describing progression', function () {
            $segments = [
                SpeechSegment::dialogue('ALICE', 'Line one'),
                SpeechSegment::dialogue('ALICE', 'Line two'),
                SpeechSegment::dialogue('ALICE', 'Line three'),
            ];

            $result = $this->service->buildDialogueDirectionPrompt($segments, 'building', 'tense', 'elevenlabs');

            expect($result['arcSummary'])->toContain('quiet');
            expect($result['arcSummary'])->toContain('peak');
        });

    });

    describe('buildArcSummary', function () {

        test('describes progression for building arc', function () {
            $result = $this->service->buildArcSummary('building', 4);

            expect($result)->toContain('quiet');
            expect($result)->toContain('peak');
        });

        test('adapts for single segment', function () {
            $result = $this->service->buildArcSummary('building', 1);

            expect($result)->toContain('quiet');
            expect($result)->toContain('Deliver');
        });

        test('adapts for two segments', function () {
            $result = $this->service->buildArcSummary('building', 2);

            expect($result)->toContain('Start');
            expect($result)->toContain('end');
        });

        test('handles crashing arc', function () {
            $result = $this->service->buildArcSummary('crashing', 4);

            expect($result)->toContain('confident');
            expect($result)->toContain('collapsed');
        });

    });

    describe('helper methods', function () {

        test('getAvailableArcTypes returns all arc keys', function () {
            $arcTypes = $this->service->getAvailableArcTypes();

            expect($arcTypes)->toContain('building');
            expect($arcTypes)->toContain('crashing');
            expect($arcTypes)->toContain('recovering');
            expect(count($arcTypes))->toBe(6);
        });

        test('getAvailableSceneTypes returns all scene keys', function () {
            $sceneTypes = $this->service->getAvailableSceneTypes();

            expect($sceneTypes)->toContain('intimate');
            expect($sceneTypes)->toContain('outdoor');
            expect($sceneTypes)->toContain('tense');
            expect(count($sceneTypes))->toBe(8);
        });

        test('hasArcType returns true for valid arc', function () {
            expect($this->service->hasArcType('building'))->toBeTrue();
            expect($this->service->hasArcType('crashing'))->toBeTrue();
        });

        test('hasArcType returns false for invalid arc', function () {
            expect($this->service->hasArcType('unknown'))->toBeFalse();
        });

        test('hasSceneType returns true for valid scene', function () {
            expect($this->service->hasSceneType('tense'))->toBeTrue();
            expect($this->service->hasSceneType('outdoor'))->toBeTrue();
        });

        test('hasSceneType returns false for invalid scene', function () {
            expect($this->service->hasSceneType('unknown'))->toBeFalse();
        });

        test('getArcPattern returns pattern for valid arc', function () {
            $pattern = $this->service->getArcPattern('revealing');

            expect($pattern)->toBeArray();
            expect(count($pattern))->toBe(4);
            expect($pattern[0])->toBe('guarded');
            expect($pattern[3])->toBe('vulnerable');
        });

        test('getArcPattern returns building pattern for unknown arc', function () {
            $pattern = $this->service->getArcPattern('unknown');

            expect($pattern)->toBe(VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS['building']);
        });

        test('getVoiceDirection returns injected service', function () {
            $voiceDirection = $this->service->getVoiceDirection();

            expect($voiceDirection)->toBeInstanceOf(VoiceDirectionVocabulary::class);
        });

        test('getPacingService returns injected service', function () {
            $pacingService = $this->service->getPacingService();

            expect($pacingService)->toBeInstanceOf(VoicePacingService::class);
        });

    });

});
