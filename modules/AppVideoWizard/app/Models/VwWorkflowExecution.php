<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VwWorkflowExecution extends Model
{
    protected $table = 'vw_workflow_executions';

    protected $fillable = [
        'workflow_id',
        'project_id',
        'status',
        'current_node_id',
        'data_bus',
        'node_results',
        'workflow_snapshot',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'data_bus' => 'array',
        'node_results' => 'array',
        'workflow_snapshot' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // --- Relationships ---

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(VwWorkflow::class, 'workflow_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    // --- Scopes ---

    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['running', 'paused']);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // --- Helpers ---

    /**
     * Get the effective workflow nodes (snapshot if edited, otherwise from workflow).
     */
    public function getNodes(): array
    {
        return $this->workflow_snapshot['nodes'] ?? $this->workflow->nodes;
    }

    /**
     * Get the effective workflow edges.
     */
    public function getEdges(): array
    {
        return $this->workflow_snapshot['edges'] ?? $this->workflow->edges;
    }

    /**
     * Get a specific node from the effective workflow.
     */
    public function getNode(string $nodeId): ?array
    {
        foreach ($this->getNodes() as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Get the result for a specific node.
     */
    public function getNodeResult(string $nodeId): ?array
    {
        return $this->node_results[$nodeId] ?? null;
    }

    /**
     * Check if a node has completed successfully.
     */
    public function isNodeCompleted(string $nodeId): bool
    {
        $result = $this->getNodeResult($nodeId);
        return $result && ($result['status'] ?? '') === 'completed';
    }

    /**
     * Get a value from the data bus using dot notation.
     */
    public function getDataBusValue(string $path, $default = null)
    {
        return data_get($this->data_bus, $path, $default);
    }

    /**
     * Set a value on the data bus using dot notation.
     */
    public function setDataBusValue(string $path, $value): void
    {
        $dataBus = $this->data_bus ?? [];
        data_set($dataBus, $path, $value);
        $this->data_bus = $dataBus;
    }

    /**
     * Update a single node's result.
     */
    public function setNodeResult(string $nodeId, array $result): void
    {
        $results = $this->node_results ?? [];
        $results[$nodeId] = $result;
        $this->node_results = $results;
    }

    /**
     * Update a node in the workflow snapshot (for editing).
     */
    public function updateNodeInSnapshot(string $nodeId, array $changes): void
    {
        $snapshot = $this->workflow_snapshot ?? [
            'nodes' => $this->workflow->nodes,
            'edges' => $this->workflow->edges,
        ];

        foreach ($snapshot['nodes'] as &$node) {
            if ($node['id'] === $nodeId) {
                $node = array_merge($node, $changes);
                break;
            }
        }

        $this->workflow_snapshot = $snapshot;
    }
}
