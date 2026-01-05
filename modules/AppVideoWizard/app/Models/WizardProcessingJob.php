<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WizardProcessingJob extends Model
{
    protected $table = 'wizard_processing_jobs';

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'status',
        'progress',
        'current_stage',
        'input_data',
        'result_data',
        'error_message',
        'external_job_id',
        'external_provider',
        'credits_used',
    ];

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
        'progress' => 'integer',
        'credits_used' => 'integer',
    ];

    const TYPE_SCRIPT_GENERATION = 'script_generation';
    const TYPE_CONCEPT_IMPROVEMENT = 'concept_improvement';
    const TYPE_IMAGE_GENERATION = 'image_generation';
    const TYPE_VOICEOVER_GENERATION = 'voiceover_generation';
    const TYPE_VIDEO_ANIMATION = 'video_animation';
    const TYPE_VIDEO_EXPORT = 'video_export';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the project that owns the job.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    /**
     * Get the user that owns the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if job is in progress.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if job has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if job is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark job as processing.
     */
    public function markAsProcessing(string $stage = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'current_stage' => $stage,
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $progress, string $stage = null): void
    {
        $data = ['progress' => min(100, max(0, $progress))];
        if ($stage) {
            $data['current_stage'] = $stage;
        }
        $this->update($data);
    }

    /**
     * Mark job as completed.
     */
    public function markAsCompleted(array $resultData = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'result_data' => $resultData,
        ]);
    }

    /**
     * Mark job as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark job as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
