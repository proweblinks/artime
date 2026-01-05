<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WizardProject extends Model
{
    protected $table = 'wizard_projects';

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'status',
        'current_step',
        'max_reached_step',
        'platform',
        'aspect_ratio',
        'target_duration',
        'format',
        'production_type',
        'production_subtype',
        'concept',
        'character_intelligence',
        'content_config',
        'script',
        'storyboard',
        'animation',
        'assembly',
        'export_config',
        'output_url',
        'thumbnail_url',
    ];

    protected $casts = [
        'concept' => 'array',
        'character_intelligence' => 'array',
        'content_config' => 'array',
        'script' => 'array',
        'storyboard' => 'array',
        'animation' => 'array',
        'assembly' => 'array',
        'export_config' => 'array',
        'current_step' => 'integer',
        'max_reached_step' => 'integer',
        'target_duration' => 'integer',
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
     * Get the processing jobs for the project.
     */
    public function processingJobs(): HasMany
    {
        return $this->hasMany(WizardProcessingJob::class, 'project_id');
    }

    /**
     * Get the assets for the project.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(WizardAsset::class, 'project_id');
    }

    /**
     * Get platform configuration.
     */
    public function getPlatformConfig(): ?array
    {
        if (!$this->platform) {
            return null;
        }
        return config("appvideowizard.platforms.{$this->platform}");
    }

    /**
     * Get format configuration.
     */
    public function getFormatConfig(): ?array
    {
        if (!$this->format) {
            return null;
        }
        return config("appvideowizard.formats.{$this->format}");
    }

    /**
     * Get production type configuration.
     */
    public function getProductionTypeConfig(): ?array
    {
        if (!$this->production_type) {
            return null;
        }
        return config("appvideowizard.production_types.{$this->production_type}");
    }

    /**
     * Get scenes from script.
     */
    public function getScenes(): array
    {
        return $this->script['scenes'] ?? [];
    }

    /**
     * Get scene count.
     */
    public function getSceneCount(): int
    {
        return count($this->getScenes());
    }

    /**
     * Check if project has completed script.
     */
    public function hasScript(): bool
    {
        return !empty($this->script['scenes']);
    }

    /**
     * Check if project has storyboard images.
     */
    public function hasStoryboard(): bool
    {
        $storyboard = $this->storyboard ?? [];
        foreach ($storyboard['scenes'] ?? [] as $scene) {
            if (!empty($scene['imageUrl'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if project is ready for export.
     */
    public function isReadyForExport(): bool
    {
        return $this->hasScript() && $this->hasStoryboard();
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
