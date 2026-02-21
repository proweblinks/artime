<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;
use Modules\AppVideoWizard\Services\AnimationService;

/**
 * Executes AI video generation nodes.
 * Wraps AnimationService for Seedance — async with polling.
 */
class AiVideoExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'ai_video';
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        $service = app(AnimationService::class);
        $method = $config['method'] ?? 'generateAnimation';
        $model = $config['model'] ?? 'seedance';
        $duration = (int) ($config['duration'] ?? 8);

        $imageUrl = $inputs['image_url'] ?? '';
        $prompt = $inputs['prompt'] ?? '';

        if (!method_exists($service, $method)) {
            throw new \RuntimeException("AnimationService::{$method} does not exist");
        }

        Log::info("[AiVideoExecutor] Generating video with model '{$model}', duration: {$duration}s");

        $result = $service->$method($imageUrl, $prompt, [
            'model' => $model,
            'duration' => $duration,
            'quality' => $config['quality'] ?? 'pro',
            'project_id' => $project->id,
            'async' => $config['async'] ?? true,
        ]);

        return [
            'video_url' => $result['url'] ?? $result['video_url'] ?? null,
            'job_id' => $result['job_id'] ?? $result['requestId'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'raw_response' => $result,
        ];
    }
}
