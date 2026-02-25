<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\StoryModeProject;
use Modules\AppVideoWizard\Services\StoryModeOrchestrator;

/**
 * Story Mode Generation Job
 *
 * Runs the full Story Mode pipeline in the background:
 * Voiceover → Visual Script → Images → Video Clips → Assembly
 */
class StoryModeGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     * Pipeline takes ~8-12 minutes: voiceover ~1min, images ~3min, video ~4min, assembly ~2min
     */
    public int $timeout = 900; // 15 minutes

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $projectId,
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "story_mode_generation_{$this->projectId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = StoryModeProject::find($this->projectId);

        if (!$project) {
            Log::error('StoryModeGenerationJob: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        if ($project->status === 'ready' || $project->status === 'failed') {
            Log::info('StoryModeGenerationJob: Project already completed/failed, skipping', [
                'project_id' => $this->projectId,
                'status' => $project->status,
            ]);
            return;
        }

        Log::info('StoryModeGenerationJob: Starting pipeline', ['project_id' => $this->projectId]);

        try {
            $orchestrator = new StoryModeOrchestrator();
            $orchestrator->generate($project);
        } catch (\Exception $e) {
            Log::error('StoryModeGenerationJob: Pipeline failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            // Ensure project is marked as failed
            $project->refresh();
            if ($project->status !== 'failed') {
                $project->markFailed($e->getMessage());
            }

            throw $e; // Let the queue system handle the failure
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('StoryModeGenerationJob: Job permanently failed', [
            'project_id' => $this->projectId,
            'error' => $exception?->getMessage(),
        ]);

        $project = StoryModeProject::find($this->projectId);
        if ($project && $project->status !== 'failed' && $project->status !== 'ready') {
            $project->markFailed('Generation job failed: ' . ($exception?->getMessage() ?? 'Unknown error'));
        }
    }

    /**
     * Get the tags assigned to the job.
     */
    public function tags(): array
    {
        return ['story-mode', "project:{$this->projectId}"];
    }
}
