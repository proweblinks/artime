<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Services\UrlToVideoOrchestrator;

class UrlToVideoGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900; // 15 minutes
    public int $maxExceptions = 1;

    public function __construct(
        protected int $projectId,
    ) {}

    public function uniqueId(): string
    {
        return "url_to_video_generation_{$this->projectId}";
    }

    public function handle(): void
    {
        $project = UrlToVideoProject::find($this->projectId);

        if (!$project) {
            Log::error('UrlToVideoGenerationJob: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        if ($project->status === 'ready' || $project->status === 'failed') {
            Log::info('UrlToVideoGenerationJob: Project already completed/failed, skipping', [
                'project_id' => $this->projectId,
                'status' => $project->status,
            ]);
            return;
        }

        Log::info('UrlToVideoGenerationJob: Starting pipeline', ['project_id' => $this->projectId]);

        try {
            $orchestrator = new UrlToVideoOrchestrator();
            $orchestrator->generate($project);
        } catch (\Exception $e) {
            Log::error('UrlToVideoGenerationJob: Pipeline failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            $project->refresh();
            if ($project->status !== 'failed') {
                $project->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('UrlToVideoGenerationJob: Job permanently failed', [
            'project_id' => $this->projectId,
            'error' => $exception?->getMessage(),
        ]);

        $project = UrlToVideoProject::find($this->projectId);
        if ($project && $project->status !== 'failed' && $project->status !== 'ready') {
            $project->markFailed('Generation job failed: ' . ($exception?->getMessage() ?? 'Unknown error'));
        }
    }

    public function tags(): array
    {
        return ['url-to-video', "project:{$this->projectId}"];
    }
}
