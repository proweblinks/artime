<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StoryModeProject extends Model
{
    use SoftDeletes;

    protected $table = 'story_mode_projects';

    protected $fillable = [
        'user_id',
        'team_id',
        'title',
        'prompt',
        'style_id',
        'custom_style_instruction',
        'custom_style_image',
        'aspect_ratio',
        'voice_id',
        'voice_provider',
        'transcript',
        'transcript_word_count',
        'visual_script',
        'scenes',
        'status',
        'progress_percent',
        'current_stage',
        'video_path',
        'video_url',
        'video_duration',
        'thumbnail_path',
        'thumbnail_url',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'visual_script' => 'array',
        'scenes' => 'array',
        'metadata' => 'array',
        'transcript_word_count' => 'integer',
        'progress_percent' => 'integer',
        'video_duration' => 'integer',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the project.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Modules\AppTeams\Models\Team::class);
    }

    /**
     * Get the style used for this project.
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(StoryModeStyle::class, 'style_id');
    }

    /**
     * Scope: filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: completed/ready projects.
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope: projects currently in progress (any generating status).
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotIn('status', ['draft', 'ready', 'failed']);
    }

    /**
     * Check if the project is currently generating.
     */
    public function isGenerating(): bool
    {
        return !in_array($this->status, ['draft', 'script_ready', 'ready', 'failed']);
    }

    /**
     * Check if the project has a completed video.
     */
    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Check if the project failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the effective style instruction (custom or from style).
     */
    public function getEffectiveStyleInstruction(): string
    {
        if (!empty($this->custom_style_instruction)) {
            return $this->custom_style_instruction;
        }

        if ($this->style) {
            return $this->style->style_instruction;
        }

        return '';
    }

    /**
     * Update pipeline progress.
     */
    public function updateProgress(string $status, int $percent, ?string $stage = null): void
    {
        $this->update([
            'status' => $status,
            'progress_percent' => $percent,
            'current_stage' => $stage,
        ]);
    }

    /**
     * Mark pipeline as failed.
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Delete project with all associated files on disk.
     */
    public function deleteWithFiles(): void
    {
        $projectId = $this->id;

        // Delete story-mode files directory
        $storyDir = public_path("story-mode/{$projectId}");
        if (File::isDirectory($storyDir)) {
            File::deleteDirectory($storyDir);
        }

        // Force delete (bypasses soft delete)
        $this->forceDelete();

        Log::info("StoryModeProject::deleteWithFiles - Cleaned up project {$projectId}");
    }
}
