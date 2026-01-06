<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;

class ImageGenerationService
{
    /**
     * Generate an image for a scene.
     */
    public function generateSceneImage(WizardProject $project, array $scene, array $options = []): array
    {
        $visualDescription = $scene['visualDescription'] ?? '';
        $styleBible = $project->storyboard['styleBible'] ?? null;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Build the image prompt
        $prompt = $this->buildImagePrompt($visualDescription, $styleBible, $project->aspect_ratio);

        // Get resolution based on aspect ratio
        $resolution = $this->getResolution($project->aspect_ratio);

        // Generate image using ArTime's AI service
        $result = AI::process($prompt, 'image', [
            'size' => $resolution['size'],
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        // Extract image URL from result
        $imageData = $result['data'][0] ?? null;
        if (!$imageData) {
            throw new \Exception('No image generated');
        }

        $imageUrl = is_array($imageData) ? ($imageData['url'] ?? null) : $imageData;

        // Download and store the image
        $storedPath = $this->storeImage($imageUrl, $project, $scene['id']);

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_IMAGE,
            'name' => $scene['title'] ?? $scene['id'],
            'path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'mime_type' => 'image/png',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'prompt' => $prompt,
                'width' => $resolution['width'],
                'height' => $resolution['height'],
                'aspectRatio' => $project->aspect_ratio,
            ],
        ]);

        return [
            'success' => true,
            'imageUrl' => $asset->url,
            'assetId' => $asset->id,
            'prompt' => $prompt,
        ];
    }

    /**
     * Build the image generation prompt.
     */
    protected function buildImagePrompt(string $visualDescription, ?array $styleBible, string $aspectRatio): string
    {
        $parts = [];

        // Add style bible if available
        if ($styleBible && $styleBible['enabled']) {
            if (!empty($styleBible['style'])) {
                $parts[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $parts[] = $styleBible['colorGrade'];
            }
            if (!empty($styleBible['lighting'])) {
                $parts[] = $styleBible['lighting'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $parts[] = $styleBible['atmosphere'];
            }
        }

        // Add visual description
        $parts[] = $visualDescription;

        // Add technical specs
        $parts[] = '4K, ultra detailed, cinematic, professional lighting';

        // Combine all parts
        $prompt = implode('. ', array_filter($parts));

        // Add negative prompt handling if supported
        return $prompt;
    }

    /**
     * Get resolution configuration for aspect ratio.
     */
    protected function getResolution(string $aspectRatio): array
    {
        $resolutions = [
            '16:9' => ['width' => 1920, 'height' => 1080, 'size' => '1792x1024'],
            '9:16' => ['width' => 1080, 'height' => 1920, 'size' => '1024x1792'],
            '1:1' => ['width' => 1080, 'height' => 1080, 'size' => '1024x1024'],
            '4:5' => ['width' => 1080, 'height' => 1350, 'size' => '1024x1024'],
        ];

        return $resolutions[$aspectRatio] ?? $resolutions['16:9'];
    }

    /**
     * Store image from URL to local storage.
     */
    protected function storeImage(string $imageUrl, WizardProject $project, string $sceneId): string
    {
        $contents = file_get_contents($imageUrl);

        $filename = Str::slug($sceneId) . '-' . time() . '.png';
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Regenerate an image with modifications.
     */
    public function regenerateImage(WizardProject $project, array $scene, string $modification): array
    {
        $originalPrompt = $scene['prompt'] ?? $scene['visualDescription'] ?? '';

        $modifiedPrompt = "{$originalPrompt}. {$modification}";

        return $this->generateSceneImage($project, array_merge($scene, [
            'visualDescription' => $modifiedPrompt,
        ]));
    }

    /**
     * Generate images for all scenes in batch.
     */
    public function generateAllSceneImages(WizardProject $project, callable $progressCallback = null): array
    {
        $scenes = $project->getScenes();
        $results = [];

        foreach ($scenes as $index => $scene) {
            try {
                $result = $this->generateSceneImage($project, $scene, ['sceneIndex' => $index]);
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
}
