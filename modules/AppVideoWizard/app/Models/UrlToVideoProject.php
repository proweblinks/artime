<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UrlToVideoProject extends Model
{
    use SoftDeletes;

    protected $table = 'url_to_video_projects';

    protected $fillable = [
        'user_id',
        'team_id',
        'title',
        'prompt',
        'source_url',
        'source_type',
        'extracted_content',
        'content_brief',
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
        'extracted_content' => 'array',
        'content_brief' => 'array',
        'visual_script' => 'array',
        'scenes' => 'array',
        'metadata' => 'array',
        'transcript_word_count' => 'integer',
        'progress_percent' => 'integer',
        'video_duration' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Modules\AppTeams\Models\Team::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNotIn('status', ['draft', 'script_ready', 'ready', 'failed', 'cancelled']);
    }

    public function isGenerating(): bool
    {
        return !in_array($this->status, ['draft', 'script_ready', 'ready', 'failed', 'cancelled']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function updateProgress(string $status, int $percent, ?string $stage = null): void
    {
        $this->update([
            'status' => $status,
            'progress_percent' => $percent,
            'current_stage' => $stage,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function deleteWithFiles(): void
    {
        $projectId = $this->id;

        $dir = public_path("url-to-video/{$projectId}");
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }

        $this->forceDelete();

        Log::info("UrlToVideoProject::deleteWithFiles - Cleaned up project {$projectId}");
    }
}
