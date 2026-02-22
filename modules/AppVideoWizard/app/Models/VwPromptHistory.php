<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VwPromptHistory extends Model
{
    protected $table = 'vw_prompt_history';

    protected $fillable = [
        'prompt_id',
        'version',
        'prompt_template',
        'variables',
        'model',
        'temperature',
        'max_tokens',
        'changed_by',
        'change_notes',
    ];

    protected $casts = [
        'variables' => 'array',
        'temperature' => 'decimal:1',
        'max_tokens' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Get the prompt this history belongs to.
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(VwPrompt::class, 'prompt_id');
    }

    /**
     * Get the user who made this change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get a diff between this version and another.
     */
    public function getDiff(?VwPromptHistory $other = null): array
    {
        $otherTemplate = $other ? $other->prompt_template : '';

        return [
            'added_lines' => $this->getAddedLines($otherTemplate),
            'removed_lines' => $this->getRemovedLines($otherTemplate),
        ];
    }

    /**
     * Get lines added in this version.
     */
    protected function getAddedLines(string $oldTemplate): array
    {
        $oldLines = explode("\n", $oldTemplate);
        $newLines = explode("\n", $this->prompt_template);

        return array_values(array_diff($newLines, $oldLines));
    }

    /**
     * Get lines removed in this version.
     */
    protected function getRemovedLines(string $oldTemplate): array
    {
        $oldLines = explode("\n", $oldTemplate);
        $newLines = explode("\n", $this->prompt_template);

        return array_values(array_diff($oldLines, $newLines));
    }

    /**
     * Format the change timestamp.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M d, Y H:i');
    }

    /**
     * Get the changer's name or 'System'.
     */
    public function getChangerNameAttribute(): string
    {
        return $this->changedBy?->name ?? 'System';
    }
}
