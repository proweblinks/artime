<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\QueuedJobsManager;
use Modules\AppVideoWizard\Services\PerformanceMonitoringService;
use Exception;

/**
 * Batch Script Generation Job
 *
 * PHASE 5 OPTIMIZATION: Background job for generating scripts.
 *
 * Features:
 * - Generates full video scripts in the background
 * - Reports progress for real-time UI updates
 * - Supports cancellation
 * - Integrates with performance monitoring
 * - Updates project with generated script on completion
 */
class BatchScriptGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Project ID
     */
    protected int $projectId;

    /**
     * Job ID for tracking
     */
    protected string $jobId;

    /**
     * Generation options
     */
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $projectId,
        string $jobId,
        array $options = []
    ) {
        $this->projectId = $projectId;
        $this->jobId = $jobId;
        $this->options = $options;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "batch_script_{$this->projectId}_{$this->jobId}";
    }

    /**
     * Execute the job.
     */
    public function handle(
        ScriptGenerationService $scriptService,
        QueuedJobsManager $jobsManager,
        PerformanceMonitoringService $performanceService
    ): void {
        Log::info("[BatchScriptGenerationJob:{$this->jobId}] Starting script generation", [
            'projectId' => $this->projectId,
        ]);

        // Start performance tracking
        $timerId = $performanceService->startOperation('batch_script_generation', [
            'project_id' => $this->projectId,
            'options' => array_keys($this->options),
        ]);

        $jobsManager->markJobStarted($this->jobId);

        try {
            $project = WizardProject::findOrFail($this->projectId);

            // Check for cancellation before starting
            if ($jobsManager->isCancellationRequested($this->jobId)) {
                Log::info("[BatchScriptGenerationJob:{$this->jobId}] Cancellation requested before start");
                $jobsManager->updateJobStatus($this->jobId, 'cancelled', 0, 'Cancelled before processing');
                return;
            }

            // Update progress - starting generation
            $jobsManager->updateJobStatus(
                $this->jobId,
                'processing',
                10,
                'Analyzing project configuration...'
            );

            // Prepare options
            $generationOptions = array_merge($this->options, [
                'teamId' => $project->team_id ?? session('current_team_id', 0),
            ]);

            // Update progress - calling AI
            $jobsManager->updateJobStatus(
                $this->jobId,
                'processing',
                25,
                'Generating script with AI...'
            );

            // Track the actual AI generation
            $aiTimerId = $performanceService->startOperation('script_ai_generation', [
                'ai_tier' => $generationOptions['aiEngine'] ?? $generationOptions['aiModelTier'] ?? 'grok',
            ]);

            // Generate the script
            $result = $scriptService->generateScript($project, $generationOptions);

            // Stop AI timing
            $performanceService->stopOperation($aiTimerId, [
                'success' => isset($result['scenes']),
                'scene_count' => count($result['scenes'] ?? []),
            ]);

            // Check for cancellation after generation
            if ($jobsManager->isCancellationRequested($this->jobId)) {
                Log::info("[BatchScriptGenerationJob:{$this->jobId}] Cancellation requested after generation");
                $jobsManager->updateJobStatus(
                    $this->jobId,
                    'cancelled',
                    75,
                    'Cancelled - script was generated but not saved',
                    ['generated_script' => $result]
                );
                return;
            }

            // Update progress - saving
            $jobsManager->updateJobStatus(
                $this->jobId,
                'processing',
                85,
                'Saving generated script...'
            );

            // Update the project with the generated script
            if (isset($result['scenes']) && !empty($result['scenes'])) {
                $storyboard = $project->storyboard ?? [];
                $storyboard['scenes'] = $result['scenes'];
                $storyboard['script_generated_at'] = now()->toIso8601String();
                $storyboard['script_job_id'] = $this->jobId;

                $project->storyboard = $storyboard;
                $project->save();
            }

            // Stop performance tracking
            $performanceService->stopOperation($timerId, [
                'scene_count' => count($result['scenes'] ?? []),
            ]);

            // Persist metrics
            $performanceService->persistMetrics(
                $this->projectId,
                $project->user_id ?? null,
                'batch_script_generation'
            );

            // Mark job as completed
            $jobsManager->markJobCompleted($this->jobId, [
                'scene_count' => count($result['scenes'] ?? []),
                'total_duration' => $result['totalDuration'] ?? null,
                'word_count' => $result['wordCount'] ?? null,
            ]);

            // Remove from active tracking
            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);

            Log::info("[BatchScriptGenerationJob:{$this->jobId}] Script generation completed", [
                'sceneCount' => count($result['scenes'] ?? []),
            ]);

        } catch (Exception $e) {
            Log::error("[BatchScriptGenerationJob:{$this->jobId}] Job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $performanceService->stopOperation($timerId, ['error' => $e->getMessage()]);

            $jobsManager->markJobFailed($this->jobId, $e->getMessage(), [
                'exception_class' => get_class($e),
            ]);

            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("[BatchScriptGenerationJob:{$this->jobId}] Job failed permanently", [
            'error' => $exception?->getMessage(),
        ]);

        try {
            $jobsManager = app(QueuedJobsManager::class);
            $jobsManager->markJobFailed(
                $this->jobId,
                $exception?->getMessage() ?? 'Unknown error',
                ['permanent_failure' => true]
            );
            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);
        } catch (Exception $e) {
            Log::error("[BatchScriptGenerationJob:{$this->jobId}] Failed to update job status on failure", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'batch_script_generation',
            "project:{$this->projectId}",
            "job:{$this->jobId}",
        ];
    }
}
