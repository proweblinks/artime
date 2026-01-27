<?php

namespace Tests\Feature\VideoWizard;

use Tests\TestCase;
use Modules\AppVideoWizard\Services\VideoPromptBuilderService;
use Modules\AppVideoWizard\Services\CameraMovementService;
use Modules\AppVideoWizard\Services\VideoTemporalService;
use Modules\AppVideoWizard\Services\MicroMovementService;
use Modules\AppVideoWizard\Services\CharacterDynamicsService;
use Modules\AppVideoWizard\Services\CharacterPathService;
use Modules\AppVideoWizard\Services\TransitionVocabulary;

/**
 * Integration tests for Phase 24: Video Temporal Expansion
 *
 * Verifies that all temporal services are properly integrated into the
 * video prompt generation pipeline via buildTemporalVideoPrompt.
 *
 * Key requirements tested:
 * - VID-01: Video prompts contain all image prompt features (camera, lighting, psychology)
 * - VID-02: Video prompts contain temporal beat structure with timing
 * - VID-03: Video prompts contain camera movement with duration and psychology
 * - VID-04: Character movement paths included when movement_intent provided
 * - VID-05: Multi-character video prompts contain spatial dynamics
 * - VID-06: Close-up video shots include micro-movements
 * - VID-07: Video prompts include transition setup information
 */
class VideoTemporalIntegrationTest extends TestCase
{
    protected VideoPromptBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VideoPromptBuilderService::class);
    }

    /**
     * Get a close-up shot fixture for testing.
     */
    protected function getCloseUpShot(): array
    {
        return [
            'type' => 'close-up',
            'duration' => 6,
            'characters' => ['Elena'],
            'emotion' => 'tense',
            'subjectAction' => 'realizes the truth',
            'cameraMovement' => 'dolly-in',
        ];
    }

    /**
     * Get a multi-character shot fixture for testing.
     */
    protected function getMultiCharacterShot(): array
    {
        return [
            'type' => 'two-shot',
            'duration' => 8,
            'characters' => ['Marcus', 'Elena'],
            'emotion' => 'conflict',
            'subjectAction' => 'confrontation',
            'relationship' => 'conflict',
            'proximity' => 'personal',
        ];
    }

    /**
     * Get a wide shot fixture for testing.
     */
    protected function getWideShot(): array
    {
        return [
            'type' => 'wide-shot',
            'duration' => 5,
            'characters' => ['Marcus'],
            'emotion' => 'peaceful',
            'subjectAction' => 'surveys the landscape',
            'cameraMovement' => 'pan',
        ];
    }

    /**
     * Get a shot with explicit temporal beats for testing.
     */
    protected function getShotWithTemporalBeats(): array
    {
        return [
            'type' => 'medium-shot',
            'duration' => 10,
            'characters' => ['Marcus'],
            'emotion' => 'contemplative',
            'subjectAction' => 'considers his options',
        ];
    }

    /**
     * Get temporal beats fixture.
     */
    protected function getTemporalBeats(): array
    {
        return [
            ['action' => 'turns to face the window', 'duration' => 3],
            ['action' => 'takes a deep breath', 'duration' => 2],
            ['action' => 'nods with decision', 'duration' => 3],
        ];
    }

    /**
     * Get a shot with movement intent for testing.
     */
    protected function getShotWithMovementIntent(): array
    {
        return [
            'type' => 'medium-shot',
            'duration' => 6,
            'characters' => ['Elena'],
            'emotion' => 'determined',
            'subjectAction' => 'approaches the door',
            'movement_intent' => 'enter_scene',
        ];
    }

    // =========================================================================
    // VID-01: All image features present
    // =========================================================================

    /** @test */
    public function testTemporalVideoPromptIncludesCameraSpecs()
    {
        $shot = $this->getCloseUpShot();
        $context = [
            'genre' => 'drama',
            'mood' => 'tense',
        ];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['prompt']);

        // Should include camera framing from base Hollywood prompt
        $prompt = strtolower($result['prompt']);
        $this->assertTrue(
            str_contains($prompt, 'close-up') ||
            str_contains($prompt, 'close up') ||
            str_contains($prompt, 'framing'),
            'Temporal prompt should include camera framing from base prompt'
        );

        // Should have components from base
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('camera_shot', $result['components']);
    }

    /** @test */
    public function testTemporalVideoPromptIncludesLighting()
    {
        $shot = $this->getCloseUpShot();
        $context = [
            'genre' => 'drama',
            'timeOfDay' => 'golden_hour',
        ];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should include lighting from base Hollywood prompt
        $this->assertArrayHasKey('lighting', $result['components']);
        $this->assertNotEmpty($result['components']['lighting']);
    }

    // =========================================================================
    // VID-02: Temporal progression with timing
    // =========================================================================

    /** @test */
    public function testTemporalVideoPromptIncludesTimingBeats()
    {
        $shot = $this->getShotWithTemporalBeats();
        $beats = $this->getTemporalBeats();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context, [], $beats);

        $this->assertTrue($result['success']);

        // Should have temporal_beats component
        $this->assertArrayHasKey('temporal_beats', $result['components']);
        $temporalBeats = $result['components']['temporal_beats'];

        // Should contain timing format [00:00-00:03]
        $this->assertMatchesRegularExpression(
            '/\[\d{2}:\d{2}-\d{2}:\d{2}\]/',
            $temporalBeats,
            'Temporal beats should contain timing markers in [MM:SS-MM:SS] format'
        );

        // Should contain the actions from beats
        $this->assertStringContainsString('turns', strtolower($temporalBeats));
    }

    /** @test */
    public function testTemporalBeatsRespectDuration()
    {
        $shot = [
            'type' => 'medium-shot',
            'duration' => 5, // Short duration
            'characters' => ['Marcus'],
            'emotion' => 'neutral',
            'subjectAction' => 'waits patiently',
        ];

        // Beats that would exceed duration
        $beats = [
            ['action' => 'first action', 'duration' => 3],
            ['action' => 'second action', 'duration' => 3],
            ['action' => 'third action', 'duration' => 3],
        ];

        $result = $this->service->buildTemporalVideoPrompt($shot, [], [], $beats);

        $this->assertTrue($result['success']);

        // Metadata should reflect the beat count
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('beat_count', $result['metadata']);
    }

    // =========================================================================
    // VID-03: Camera movement with duration and psychology
    // =========================================================================

    /** @test */
    public function testCameraMovementIncludesDuration()
    {
        $shot = $this->getCloseUpShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should have camera_psychology component
        $this->assertArrayHasKey('camera_psychology', $result['components']);
        $cameraComponent = $result['components']['camera_psychology'];

        // Should contain duration phrasing like "over X seconds"
        $this->assertMatchesRegularExpression(
            '/over \d+ seconds/i',
            $cameraComponent,
            'Camera movement should include duration in "over X seconds" format'
        );
    }

    /** @test */
    public function testCameraMovementIncludesPsychology()
    {
        $shot = $this->getCloseUpShot();
        $shot['emotion'] = 'romantic';
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Camera psychology component should include emotional purpose
        $cameraComponent = $result['components']['camera_psychology'];

        // Should contain psychology phrase from MOVEMENT_PSYCHOLOGY
        $psychologyPhrases = [
            'intimacy', 'tension', 'reveal', 'isolation', 'power',
            'vulnerability', 'urgency', 'contemplation', 'discovery', 'departure',
            'connection', 'approach', 'suspense', 'scope',
        ];

        $containsPsychology = false;
        foreach ($psychologyPhrases as $phrase) {
            if (stripos($cameraComponent, $phrase) !== false) {
                $containsPsychology = true;
                break;
            }
        }

        $this->assertTrue(
            $containsPsychology,
            "Camera movement should include psychological purpose phrase. Got: {$cameraComponent}"
        );
    }

    // =========================================================================
    // VID-04: Character movement paths
    // =========================================================================

    /** @test */
    public function testCharacterPathDescriptionIncluded()
    {
        $shot = $this->getShotWithMovementIntent();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should have character_path component
        $this->assertArrayHasKey('character_path', $result['components']);
        $characterPath = $result['components']['character_path'];

        // When movement_intent is 'enter_scene', should have path description
        $this->assertNotEmpty(
            $characterPath,
            'Character path should be populated when movement_intent is provided'
        );

        // Should contain movement vocabulary
        $prompt = strtolower($result['prompt']);
        $movementVocab = ['enter', 'frame', 'moving', 'walk', 'approach', 'diagonal'];

        $containsMovement = false;
        foreach ($movementVocab as $word) {
            if (str_contains($prompt, $word)) {
                $containsMovement = true;
                break;
            }
        }

        $this->assertTrue(
            $containsMovement,
            'Prompt should contain character movement vocabulary'
        );
    }

    // =========================================================================
    // VID-05: Multi-character dynamics
    // =========================================================================

    /** @test */
    public function testMultiCharacterShotIncludesProximity()
    {
        $shot = $this->getMultiCharacterShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should have character_dynamics component
        $this->assertArrayHasKey('character_dynamics', $result['components']);
        $dynamics = $result['components']['character_dynamics'];

        $this->assertNotEmpty(
            $dynamics,
            'Multi-character shot should have spatial dynamics description'
        );

        // Should contain proxemic zone vocabulary
        $proxemicVocab = [
            'breath', 'touching', 'arm', 'length', 'distance',
            'personal', 'intimate', 'social', 'positioned',
        ];

        $containsProxemic = false;
        foreach ($proxemicVocab as $word) {
            if (stripos($dynamics, $word) !== false) {
                $containsProxemic = true;
                break;
            }
        }

        $this->assertTrue(
            $containsProxemic,
            "Dynamics should contain proxemic zone vocabulary. Got: {$dynamics}"
        );

        // Metadata should indicate multi-character
        $this->assertTrue($result['metadata']['has_multi_character']);
    }

    /** @test */
    public function testMultiCharacterShotIncludesPowerDynamics()
    {
        $shot = $this->getMultiCharacterShot();
        $shot['relationship'] = 'boss_employee';
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        $dynamics = $result['components']['character_dynamics'];

        // Should contain power positioning vocabulary
        $powerVocab = [
            'positioned', 'frame', 'higher', 'lower', 'dominant',
            'subordinate', 'angled', 'facing', 'equal', 'space',
        ];

        $containsPower = false;
        foreach ($powerVocab as $word) {
            if (stripos($dynamics, $word) !== false) {
                $containsPower = true;
                break;
            }
        }

        $this->assertTrue(
            $containsPower,
            "Dynamics should contain power positioning vocabulary. Got: {$dynamics}"
        );
    }

    // =========================================================================
    // VID-06: Micro-movements
    // =========================================================================

    /** @test */
    public function testCloseUpIncludesMicroMovements()
    {
        $shot = $this->getCloseUpShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should have micro_movements component
        $this->assertArrayHasKey('micro_movements', $result['components']);
        $microMovements = $result['components']['micro_movements'];

        // Close-up should have visible micro-movements
        $this->assertNotEmpty(
            $microMovements,
            'Close-up shot should include micro-movements'
        );

        // Should contain micro-movement vocabulary (breathing, eyes, etc.)
        $microVocab = [
            'breath', 'blink', 'eye', 'chest', 'natural',
            'subtle', 'gaze', 'shoulders', 'movement',
        ];

        $containsMicro = false;
        foreach ($microVocab as $word) {
            if (stripos($microMovements, $word) !== false) {
                $containsMicro = true;
                break;
            }
        }

        $this->assertTrue(
            $containsMicro,
            "Micro-movements should contain life motion vocabulary. Got: {$microMovements}"
        );

        // Metadata should indicate micro-movements present
        $this->assertTrue($result['metadata']['has_micro_movements']);
    }

    /** @test */
    public function testWideShortOmitsMicroMovements()
    {
        $shot = $this->getWideShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Wide shot should have empty micro_movements (too far to see details)
        $microMovements = $result['components']['micro_movements'];

        $this->assertEmpty(
            $microMovements,
            'Wide shot should not include micro-movements (invisible at this scale)'
        );

        // Metadata should indicate no micro-movements
        $this->assertFalse($result['metadata']['has_micro_movements']);
    }

    // =========================================================================
    // VID-07: Transition suggestions
    // =========================================================================

    /** @test */
    public function testTransitionSetupInMetadata()
    {
        $shot = $this->getCloseUpShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);

        // Should have transition_setup key
        $this->assertArrayHasKey('transition_setup', $result);
        $transitionSetup = $result['transition_setup'];

        // Should have ending_state
        $this->assertArrayHasKey('ending_state', $transitionSetup);

        // Should have next_shot_suggestion
        $this->assertArrayHasKey('next_shot_suggestion', $transitionSetup);

        // Should have transition_type
        $this->assertArrayHasKey('transition_type', $transitionSetup);

        // Transition type should be valid
        $validTypes = ['match_cut_setup', 'hard_cut_setup', 'soft_transition_setup'];
        $this->assertContains(
            $transitionSetup['transition_type'],
            $validTypes,
            'Transition type should be one of the valid types'
        );
    }

    // =========================================================================
    // Additional integration tests
    // =========================================================================

    /** @test */
    public function testBuildTemporalVideoPromptReturnsSuccessStructure()
    {
        $shot = $this->getCloseUpShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        // Should have all required top-level keys
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('transition_setup', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('formula', $result);

        $this->assertTrue($result['success']);
        $this->assertEquals('temporal_hollywood', $result['formula']);
    }

    /** @test */
    public function testMetadataIncludesTemporalStructureFlag()
    {
        $shot = $this->getCloseUpShot();
        $context = [];

        $result = $this->service->buildTemporalVideoPrompt($shot, $context);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('temporal_structure', $result['metadata']);
        $this->assertTrue($result['metadata']['temporal_structure']);
    }

    /** @test */
    public function testAutoGeneratedBeatsFromSubjectAction()
    {
        $shot = [
            'type' => 'medium-shot',
            'duration' => 6,
            'characters' => ['Marcus'],
            'emotion' => 'neutral',
            'subjectAction' => 'walks across the room slowly',
        ];

        $result = $this->service->buildTemporalVideoPrompt($shot, []);

        $this->assertTrue($result['success']);

        // Should have temporal_beats even without explicit beats
        $this->assertArrayHasKey('temporal_beats', $result['components']);

        // If action was provided, should have at least 1 beat
        $this->assertGreaterThanOrEqual(
            1,
            $result['metadata']['beat_count'],
            'Should auto-generate at least 1 beat from subject action'
        );
    }
}
