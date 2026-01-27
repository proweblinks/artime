<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WizardSpeechSegment Model
 *
 * Represents a speech/dialogue segment within a scene. Supports narrator,
 * character dialogue, and voice-over segments with timing and audio data.
 *
 * @property int $id
 * @property int $scene_id
 * @property int $order
 * @property string $type
 * @property string $text
 * @property string|null $speaker
 * @property string|null $character_id
 * @property string|null $voice_id
 * @property float|null $start_time
 * @property float|null $duration
 * @property string|null $audio_url
 * @property string|null $emotion
 * @property bool $needs_lip_sync
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read WizardScene $scene
 */
class WizardSpeechSegment extends Model
{
    protected $table = 'wizard_speech_segments';

    protected $fillable = [
        'scene_id',
        'order',
        // Segment data
        'type',
        'text',
        'speaker',
        'character_id',
        'voice_id',
        // Timing
        'start_time',
        'duration',
        // Audio
        'audio_url',
        // Attributes
        'emotion',
        'needs_lip_sync',
    ];

    protected $casts = [
        'order' => 'integer',
        'start_time' => 'float',
        'duration' => 'float',
        'needs_lip_sync' => 'boolean',
    ];

    /**
     * Get the scene that owns this speech segment.
     */
    public function scene(): BelongsTo
    {
        return $this->belongsTo(WizardScene::class, 'scene_id');
    }

    /**
     * Check if this segment has generated audio.
     */
    public function hasAudio(): bool
    {
        return !empty($this->audio_url);
    }

    /**
     * Check if this is a narrator segment.
     */
    public function isNarrator(): bool
    {
        return $this->type === 'narrator';
    }

    /**
     * Check if this is a character dialogue segment.
     */
    public function isCharacterDialogue(): bool
    {
        return $this->type === 'character' || $this->type === 'dialogue';
    }

    /**
     * Get the display name for the speaker.
     */
    public function getDisplaySpeaker(): string
    {
        if ($this->speaker) {
            return $this->speaker;
        }
        if ($this->isNarrator()) {
            return 'Narrator';
        }
        return 'Unknown';
    }
}
