<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;

class VoiceoverService
{
    /**
     * Available voices.
     */
    protected array $voices = [
        'alloy' => ['name' => 'Alloy', 'gender' => 'neutral', 'style' => 'versatile'],
        'echo' => ['name' => 'Echo', 'gender' => 'male', 'style' => 'warm'],
        'fable' => ['name' => 'Fable', 'gender' => 'neutral', 'style' => 'storytelling'],
        'onyx' => ['name' => 'Onyx', 'gender' => 'male', 'style' => 'deep'],
        'nova' => ['name' => 'Nova', 'gender' => 'female', 'style' => 'friendly'],
        'shimmer' => ['name' => 'Shimmer', 'gender' => 'female', 'style' => 'bright'],
    ];

    /**
     * Generate voiceover for a scene.
     */
    public function generateSceneVoiceover(WizardProject $project, array $scene, array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $voice = $options['voice'] ?? 'nova';
        $speed = $options['speed'] ?? 1.0;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        if (empty($narration)) {
            throw new \Exception('No narration text provided');
        }

        // Generate audio using OpenAI TTS
        $result = AI::process($narration, 'speech', [
            'voice' => $voice,
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $audioContent = $result['data'][0] ?? null;
        if (!$audioContent) {
            throw new \Exception('No audio generated');
        }

        // Store the audio file
        $filename = Str::slug($scene['id']) . '-voiceover-' . time() . '.mp3';
        $path = "wizard-projects/{$project->id}/audio/{$filename}";

        Storage::disk('public')->put($path, $audioContent);

        // Get audio duration (approximate based on word count)
        $wordCount = str_word_count($narration);
        $estimatedDuration = ($wordCount / 150) * 60 / $speed; // 150 words per minute

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id']) . ' - Voiceover',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'voice' => $voice,
                'speed' => $speed,
                'narration' => $narration,
                'wordCount' => $wordCount,
                'estimatedDuration' => $estimatedDuration,
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $estimatedDuration,
            'voice' => $voice,
        ];
    }

    /**
     * Generate voiceovers for all scenes.
     */
    public function generateAllVoiceovers(WizardProject $project, array $options = [], callable $progressCallback = null): array
    {
        $scenes = $project->getScenes();
        $results = [];
        $voice = $options['voice'] ?? 'nova';
        $speed = $options['speed'] ?? 1.0;

        foreach ($scenes as $index => $scene) {
            try {
                $result = $this->generateSceneVoiceover($project, $scene, [
                    'voice' => $voice,
                    'speed' => $speed,
                    'sceneIndex' => $index,
                ]);
                $results[$scene['id']] = $result;

                if ($progressCallback) {
                    $progressCallback($index + 1, count($scenes), $scene['id']);
                }
            } catch (\Exception $e) {
                $results[$scene['id']] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get available voices.
     */
    public function getAvailableVoices(): array
    {
        return $this->voices;
    }

    /**
     * Preview voice with sample text.
     */
    public function previewVoice(string $voice, string $sampleText = null, array $options = []): string
    {
        $text = $sampleText ?? 'This is a preview of the ' . ($this->voices[$voice]['name'] ?? $voice) . ' voice.';
        $teamId = $options['teamId'] ?? session('current_team_id', 0);

        $result = AI::process($text, 'speech', [
            'voice' => $voice,
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $audioContent = $result['data'][0] ?? '';

        // Return base64 encoded audio for preview
        return 'data:audio/mpeg;base64,' . base64_encode($audioContent);
    }
}
