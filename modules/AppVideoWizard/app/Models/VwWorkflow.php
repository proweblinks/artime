<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VwWorkflow extends Model
{
    protected $table = 'vw_workflows';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'video_engine',
        'nodes',
        'edges',
        'defaults',
        'user_id',
        'team_id',
        'is_active',
        'version',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'defaults' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(VwWorkflowExecution::class, 'workflow_id');
    }

    // --- Scopes ---

    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEngine($query, string $engine)
    {
        return $query->where(function ($q) use ($engine) {
            $q->where('video_engine', $engine)
              ->orWhere('video_engine', 'any');
        });
    }

    public function scopeVisibleTo($query, ?int $userId, ?int $teamId)
    {
        return $query->where(function ($q) use ($userId, $teamId) {
            $q->where('category', 'system')
              ->orWhere('user_id', $userId)
              ->orWhere(function ($q2) use ($teamId) {
                  $q2->where('team_id', $teamId)
                     ->where('category', 'shared');
              });
        });
    }

    // --- Helpers ---

    /**
     * Get a node definition by its ID.
     */
    public function getNode(string $nodeId): ?array
    {
        foreach ($this->nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Get all nodes that have no incoming edges (entry points).
     */
    public function getEntryNodes(): array
    {
        $targetIds = array_column($this->edges, 'to');
        return array_filter($this->nodes, fn($node) => !in_array($node['id'], $targetIds));
    }

    /**
     * Get downstream node IDs from a given node.
     */
    public function getDownstreamNodeIds(string $nodeId): array
    {
        return array_values(
            array_map(
                fn($edge) => $edge['to'],
                array_filter($this->edges, fn($edge) => $edge['from'] === $nodeId)
            )
        );
    }

    /**
     * Get upstream node IDs that feed into a given node.
     */
    public function getUpstreamNodeIds(string $nodeId): array
    {
        return array_values(
            array_map(
                fn($edge) => $edge['from'],
                array_filter($this->edges, fn($edge) => $edge['to'] === $nodeId)
            )
        );
    }
}
