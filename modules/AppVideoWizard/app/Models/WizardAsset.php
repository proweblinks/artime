<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WizardAsset extends Model
{
    protected $table = 'wizard_assets';

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'name',
        'path',
        'url',
        'mime_type',
        'file_size',
        'scene_index',
        'scene_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'scene_index' => 'integer',
    ];

    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';
    const TYPE_VOICEOVER = 'voiceover';
    const TYPE_MUSIC = 'music';

    /**
     * Get the project that owns the asset.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    /**
     * Get the user that owns the asset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public URL for the asset.
     */
    public function getPublicUrl(): string
    {
        if ($this->url) {
            return $this->url;
        }
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Get asset duration (for audio/video).
     */
    public function getDuration(): ?float
    {
        return $this->metadata['duration'] ?? null;
    }

    /**
     * Get asset dimensions (for images/video).
     */
    public function getDimensions(): ?array
    {
        if (isset($this->metadata['width']) && isset($this->metadata['height'])) {
            return [
                'width' => $this->metadata['width'],
                'height' => $this->metadata['height'],
            ];
        }
        return null;
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->path && Storage::disk('public')->exists($this->path)) {
            return Storage::disk('public')->delete($this->path);
        }
        return false;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($asset) {
            $asset->deleteFile();
        });
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
     * Scope to filter by scene.
     */
    public function scopeForScene($query, $sceneIndex)
    {
        return $query->where('scene_index', $sceneIndex);
    }
}
