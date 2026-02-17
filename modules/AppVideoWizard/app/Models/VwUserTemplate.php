<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;

class VwUserTemplate extends Model
{
    protected $table = 'vw_user_templates';

    protected $fillable = [
        'user_id', 'team_id', 'name', 'description', 'icon',
        'is_shared', 'video_prompt', 'concept', 'seedance_settings',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'concept' => 'array',
        'seedance_settings' => 'array',
    ];

    /**
     * Scope: templates visible to a user (own + team shared).
     */
    public function scopeVisibleTo($query, int $userId, ?int $teamId = null)
    {
        return $query->where(function ($q) use ($userId, $teamId) {
            $q->where('user_id', $userId);
            if ($teamId) {
                $q->orWhere(function ($q2) use ($teamId) {
                    $q2->where('team_id', $teamId)->where('is_shared', true);
                });
            }
        });
    }
}
