<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;
use Modules\AppVideoWizard\Services\ImageGenerationService;

/**
 * Executes AI image generation nodes.
 * Wraps ImageGenerationService — async with polling.
 */
class AiImageExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'ai_image';
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        $service = app(ImageGenerationService::class);
        $method = $config['method'] ?? 'generateSceneImage';
        $model = $config['model'] ?? 'nanobanana-pro';
        $aspectRatio = $config['aspect_ratio'] ?? $project->aspect_ratio ?? '9:16';

        $prompt = $inputs['prompt'] ?? '';

        if (!method_exists($service, $method)) {
            throw new \RuntimeException("ImageGenerationService::{$method} does not exist");
        }

        Log::info("[AiImageExecutor] Generating image with model '{$model}', prompt length: " . strlen($prompt));

        $result = $service->$method($prompt, [
            'model' => $model,
            'aspect_ratio' => $aspectRatio,
            'project_id' => $project->id,
        ]);

        return [
            'image_url' => $result['url'] ?? $result['image_url'] ?? null,
            'job_id' => $result['job_id'] ?? $result['requestId'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'raw_response' => $result,
        ];
    }
}
