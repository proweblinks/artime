<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WizardScene Model
 *
 * Represents a single scene in a wizard project. Replaces the nested array
 * structure in $script['scenes'], $storyboard['scenes'], and $animation['scenes']
 * with a proper Eloquent model for lazy loading and reduced Livewire payloads.
 *
 * @property int $id
 * @property int $project_id
 * @property int $order
 * @property string|null $narration
 * @property string|null $visual_prompt
 * @property int $duration
 * @property string $speech_type
 * @property string $transition
 * @property string|null $image_url
 * @property string $image_status
 * @property string|null $image_prompt
 * @property string|null $image_job_id
 * @property string|null $video_url
 * @property string $video_status
 * @property string|null $voiceover_url
 * @property array|null $scene_metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read WizardProject $project
 * @property-read \Illuminate\Database\Eloquent\Collection<WizardShot> $shots
 * @property-read \Illuminate\Database\Eloquent\Collection<WizardSpeechSegment> $speechSegments
 */
class WizardScene extends Model
{
    protected $table = 'wizard_scenes';

    protected $fillable = [
        'project_id',
        'order',
        // Script data
        'narration',
        'visual_prompt',
        'duration',
        'speech_type',
        'transition',
        // Storyboard data
        'image_url',
        'image_status',
        'image_prompt',
        'image_job_id',
        // Animation data
        'video_url',
        'video_status',
        'voiceover_url',
        // Metadata
        'scene_metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration' => 'integer',
        'scene_metadata' => 'array',
    ];

    /**
     * Get the project that owns this scene.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    /**
     * Get the shots for this scene (multi-shot decomposition).
     * Ordered by position within the scene.
     */
    public function shots(): HasMany
    {
        return $this->hasMany(WizardShot::class, 'scene_id')
            ->orderBy('order');
    }

    /**
     * Get the speech segments for this scene.
     * Ordered by position within the scene.
     */
    public function speechSegments(): HasMany
    {
        return $this->hasMany(WizardSpeechSegment::class, 'scene_id')
            ->orderBy('order');
    }

    /**
     * Check if this scene has generated storyboard image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image_url);
    }

    /**
     * Check if this scene has generated video.
     */
    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }

    /**
     * Check if this scene has shots (multi-shot mode).
     */
    public function hasShots(): bool
    {
        return $this->shots()->exists();
    }

    /**
     * Get shot count for this scene.
     */
    public function getShotCount(): int
    {
        return $this->shots()->count();
    }
}
