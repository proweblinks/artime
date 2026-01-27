<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WizardShot Model
 *
 * Represents a single shot within a scene for Hollywood-style multi-shot
 * decomposition workflows. Each shot has individual prompts, camera movements,
 * and generated assets.
 *
 * @property int $id
 * @property int $scene_id
 * @property int $order
 * @property string|null $image_prompt
 * @property string|null $video_prompt
 * @property string|null $camera_movement
 * @property int $duration
 * @property string $duration_class
 * @property string|null $image_url
 * @property string $image_status
 * @property string|null $video_url
 * @property string $video_status
 * @property string|null $dialogue
 * @property array|null $shot_metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read WizardScene $scene
 */
class WizardShot extends Model
{
    protected $table = 'wizard_shots';

    protected $fillable = [
        'scene_id',
        'order',
        // Prompts
        'image_prompt',
        'video_prompt',
        // Technical specs
        'camera_movement',
        'duration',
        'duration_class',
        // Generated assets
        'image_url',
        'image_status',
        'video_url',
        'video_status',
        // Dialogue
        'dialogue',
        // Metadata
        'shot_metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration' => 'integer',
        'shot_metadata' => 'array',
    ];

    /**
     * Get the scene that owns this shot.
     */
    public function scene(): BelongsTo
    {
        return $this->belongsTo(WizardScene::class, 'scene_id');
    }

    /**
     * Check if this shot has a generated image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image_url);
    }

    /**
     * Check if this shot has a generated video.
     */
    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }

    /**
     * Get speaking characters from metadata.
     */
    public function getSpeakingCharacters(): array
    {
        return $this->shot_metadata['speaking_characters'] ?? [];
    }
}
