<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\AppVideoWizard\Services\VideoRenderService;
use Modules\AppVideoWizard\Models\VideoWizardProject;
use Exception;

/**
 * Video Export Job
 *
 * Background job for rendering and exporting videos.
 * Supports both local FFmpeg processing and Cloud Run delegation.
 */
class VideoExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Project ID
     */
    protected int $projectId;

    /**
     * Export job ID (for tracking)
     */
    protected string $jobId;

    /**
     * Export manifest data
     */
    protected array $manifest;

    /**
     * Export settings
     */
    protected array $exportSettings;

    /**
     * User ID
     */
    protected ?int $userId;

    /**
     * Whether to use Cloud Run for processing
     */
    protected bool $useCloudRun;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $projectId,
        string $jobId,
        array $manifest,
        array $exportSettings,
        ?int $userId = null,
        bool $useCloudRun = false
    ) {
        $this->projectId = $projectId;
        $this->jobId = $jobId;
        $this->manifest = $manifest;
        $this->exportSettings = $exportSettings;
        $this->userId = $userId;
        $this->useCloudRun = $useCloudRun;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "video_export_{$this->projectId}_{$this->jobId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("[VideoExportJob:{$this->jobId}] Starting export for project {$this->projectId}");

        try {
            // Update status to processing
            $this->updateExportStatus('processing', 0, 'Starting export...');

            $renderService = new VideoRenderService();

            if ($this->useCloudRun && !empty(config('services.video_processor.url'))) {
                // Process via Cloud Run
                $this->processViaCloudRun($renderService);
            } else {
                // Process locally with FFmpeg
                $this->processLocally($renderService);
            }

        } catch (Exception $e) {
            Log::error("[VideoExportJob:{$this->jobId}] Export failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateExportStatus('failed', 0, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Process the export locally using FFmpeg
     */
    protected function processLocally(VideoRenderService $renderService): void
    {
        Log::info("[VideoExportJob:{$this->jobId}] Processing locally with FFmpeg");

        // Check FFmpeg availability
        if (!$renderService->checkFfmpeg()) {
            throw new Exception('FFmpeg is not available on this server');
        }

        // Prepare manifest with additional data
        $manifest = array_merge($this->manifest, [
            'userId' => (string) $this->userId,
            'projectId' => (string) $this->projectId,
            'output' => $this->exportSettings,
        ]);

        // Progress callback
        $progressCallback = function (int $progress, string $message) {
            $this->updateExportStatus('processing', $progress, $message);
        };

        // Process the export
        $result = $renderService->processExport($manifest, $progressCallback);

        // Update project with result
        $this->completeExport($result);
    }

    /**
     * Process the export via Cloud Run
     */
    protected function processViaCloudRun(VideoRenderService $renderService): void
    {
        Log::info("[VideoExportJob:{$this->jobId}] Processing via Cloud Run");

        // Prepare manifest
        $manifest = array_merge($this->manifest, [
            'userId' => (string) $this->userId,
            'projectId' => (string) $this->projectId,
            'output' => $this->exportSettings,
        ]);

        // Start the Cloud Run job
        $renderService->processExportViaCloudRun($manifest, $this->jobId);

        // Poll for completion
        $maxAttempts = 180; // 30 minutes at 10-second intervals
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(10);
            $attempt++;

            try {
                $status = $renderService->getCloudRunExportStatus($this->jobId);

                // Update local status
                $this->updateExportStatus(
                    $status['status'] ?? 'processing',
                    $status['progress'] ?? 0,
                    $status['statusMessage'] ?? 'Processing...'
                );

                if ($status['status'] === 'completed') {
                    $this->completeExport([
                        'outputUrl' => $status['outputUrl'],
                        'outputPath' => '',
                        'outputSize' => 0,
                    ]);
                    return;
                }

                if ($status['status'] === 'failed') {
                    throw new Exception($status['error'] ?? 'Cloud Run export failed');
                }

            } catch (Exception $e) {
                Log::warning("[VideoExportJob:{$this->jobId}] Status check failed: " . $e->getMessage());
            }
        }

        throw new Exception('Export timed out after 30 minutes');
    }

    /**
     * Update export status in cache (for real-time polling)
     */
    protected function updateExportStatus(string $status, int $progress, string $message): void
    {
        $statusData = [
            'jobId' => $this->jobId,
            'projectId' => $this->projectId,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updatedAt' => now()->toIso8601String(),
        ];

        // Store in cache for polling
        Cache::put("video_export_status_{$this->jobId}", $statusData, 3600);

        // Also update project record
        try {
            $project = VideoWizardProject::find($this->projectId);
            if ($project) {
                $project->update([
                    'export_status' => $status,
                    'export_progress' => $progress,
                    'export_message' => $message,
                ]);
            }
        } catch (Exception $e) {
            Log::warning("[VideoExportJob:{$this->jobId}] Failed to update project: " . $e->getMessage());
        }

        Log::debug("[VideoExportJob:{$this->jobId}] Status: {$status} ({$progress}%) - {$message}");
    }

    /**
     * Complete the export and update project
     */
    protected function completeExport(array $result): void
    {
        Log::info("[VideoExportJob:{$this->jobId}] Export completed", $result);

        $statusData = [
            'jobId' => $this->jobId,
            'projectId' => $this->projectId,
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Export complete!',
            'outputUrl' => $result['outputUrl'] ?? null,
            'outputSize' => $result['outputSize'] ?? 0,
            'completedAt' => now()->toIso8601String(),
        ];

        // Update cache
        Cache::put("video_export_status_{$this->jobId}", $statusData, 86400); // 24 hours

        // Update project record
        try {
            $project = VideoWizardProject::find($this->projectId);
            if ($project) {
                $project->update([
                    'export_status' => 'completed',
                    'export_progress' => 100,
                    'export_message' => 'Export complete!',
                    'export_url' => $result['outputUrl'] ?? null,
                    'exported_at' => now(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning("[VideoExportJob:{$this->jobId}] Failed to update project: " . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("[VideoExportJob:{$this->jobId}] Job failed permanently", [
            'error' => $exception?->getMessage(),
        ]);

        $this->updateExportStatus('failed', 0, $exception?->getMessage() ?? 'Export failed');
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'video_export',
            "project:{$this->projectId}",
            "job:{$this->jobId}",
        ];
    }
}
