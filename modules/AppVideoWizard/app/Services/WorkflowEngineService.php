<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwWorkflow;
use Modules\AppVideoWizard\Models\VwWorkflowExecution;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\WorkflowDataBus;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;
use Modules\AppVideoWizard\Services\Workflow\Executors\AiTextExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\AiImageExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\AiVideoExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\TransformExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\UserInputExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\PollWaitExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\ConditionalExecutor;
use Modules\AppVideoWizard\Services\Workflow\Executors\ComposeExecutor;

class WorkflowEngineService
{
    /**
     * Map of node type => executor class.
     */
    protected array $executors = [];

    public function __construct()
    {
        $this->registerExecutors();
    }

    /**
     * Register all available node executors.
     */
    protected function registerExecutors(): void
    {
        $executorClasses = [
            AiTextExecutor::class,
            TransformExecutor::class,
            UserInputExecutor::class,
            // Phase 2 executors (registered when their files exist)
            AiImageExecutor::class,
            AiVideoExecutor::class,
            PollWaitExecutor::class,
            ConditionalExecutor::class,
            ComposeExecutor::class,
        ];

        foreach ($executorClasses as $class) {
            if (class_exists($class)) {
                $executor = app($class);
                $this->executors[$executor->getType()] = $executor;
            }
        }
    }

    /**
     * Start a new workflow execution.
     */
    public function start(VwWorkflow $workflow, WizardProject $project, array $userInputs = []): VwWorkflowExecution
    {
        $execution = VwWorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'project_id' => $project->id,
            'status' => 'running',
            'data_bus' => [
                'user_input' => $userInputs,
                'project' => [
                    'id' => $project->id,
                    'platform' => $project->platform,
                    'aspect_ratio' => $project->aspect_ratio,
                    'target_duration' => $project->target_duration,
                ],
            ],
            'node_results' => [],
            'started_at' => now(),
        ]);

        Log::info("[WorkflowEngine] Started execution #{$execution->id} for workflow '{$workflow->slug}' on project #{$project->id}");

        // Execute all ready entry nodes
        $this->executeReadyNodes($execution);

        return $execution;
    }

    /**
     * Resume execution after a user_input or async callback.
     */
    public function resume(VwWorkflowExecution $execution, ?string $nodeId = null, ?array $result = null): void
    {
        if ($execution->status === 'completed' || $execution->status === 'failed') {
            Log::warning("[WorkflowEngine] Cannot resume execution #{$execution->id} — status is {$execution->status}");
            return;
        }

        $execution->status = 'running';
        $execution->save();

        // If a specific node result is provided (async completion or user input)
        if ($nodeId && $result) {
            $this->completeNode($execution, $nodeId, $result);
        }

        // Execute any newly-ready nodes
        $this->executeReadyNodes($execution);
    }

    /**
     * Execute a single node by its config.
     *
     * Returns: ['status' => 'completed'|'waiting'|'paused', 'output' => [...], 'error' => null|string]
     */
    public function executeNode(VwWorkflowExecution $execution, array $nodeConfig): array
    {
        $nodeId = $nodeConfig['id'];
        $nodeType = $nodeConfig['type'];

        $executor = $this->executors[$nodeType] ?? null;
        if (!$executor) {
            return [
                'status' => 'failed',
                'output' => [],
                'error' => "No executor registered for node type: {$nodeType}",
            ];
        }

        $dataBus = new WorkflowDataBus($execution);

        // Resolve input references to actual values
        $inputs = $dataBus->resolveInputs($nodeConfig['inputs'] ?? []);

        // Also pass config values through resolution (for "from_project" references)
        $config = $this->resolveConfig($nodeConfig['config'] ?? [], $dataBus);

        Log::info("[WorkflowEngine] Executing node '{$nodeId}' (type: {$nodeType})");

        $startTime = microtime(true);

        try {
            $result = $executor->execute($config, $inputs, $execution->project);

            $elapsed = round(microtime(true) - $startTime, 2);
            $status = $executor->isAsync() ? 'waiting' : 'completed';

            // For user_input type, status is 'paused'
            if ($nodeType === 'user_input') {
                $status = 'paused';
            }

            // Store timing info
            $result['_timing'] = $elapsed;

            return [
                'status' => $status,
                'output' => $result,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $elapsed = round(microtime(true) - $startTime, 2);
            Log::error("[WorkflowEngine] Node '{$nodeId}' failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'failed',
                'output' => [],
                'error' => $e->getMessage(),
                '_timing' => $elapsed,
            ];
        }
    }

    /**
     * Advance the workflow after a node completes.
     * Checks downstream nodes and executes any that are ready.
     */
    public function advance(VwWorkflowExecution $execution, string $completedNodeId): void
    {
        $edges = $execution->getEdges();
        $downstreamIds = [];

        foreach ($edges as $edge) {
            if ($edge['from'] === $completedNodeId) {
                $downstreamIds[] = $edge['to'];
            }
        }

        if (empty($downstreamIds)) {
            // No downstream — check if workflow is complete
            $this->checkCompletion($execution);
            return;
        }

        // Execute any downstream nodes that are ready
        $this->executeReadyNodes($execution);
    }

    /**
     * Get all nodes that are ready to execute (all upstream dependencies completed).
     */
    public function getReadyNodes(VwWorkflowExecution $execution): array
    {
        $nodes = $execution->getNodes();
        $edges = $execution->getEdges();
        $nodeResults = $execution->node_results ?? [];
        $ready = [];

        foreach ($nodes as $node) {
            $nodeId = $node['id'];

            // Skip already completed, running, or waiting nodes
            $result = $nodeResults[$nodeId] ?? null;
            if ($result && in_array($result['status'] ?? '', ['completed', 'running', 'waiting', 'paused'])) {
                continue;
            }

            // Check all upstream nodes are completed
            $upstreamIds = [];
            foreach ($edges as $edge) {
                if ($edge['to'] === $nodeId) {
                    $upstreamIds[] = $edge['from'];
                }
            }

            $allUpstreamDone = true;
            foreach ($upstreamIds as $upId) {
                $upResult = $nodeResults[$upId] ?? null;
                if (!$upResult || ($upResult['status'] ?? '') !== 'completed') {
                    $allUpstreamDone = false;
                    break;
                }
            }

            if ($allUpstreamDone) {
                $ready[] = $node;
            }
        }

        return $ready;
    }

    /**
     * Find and execute all ready nodes.
     */
    protected function executeReadyNodes(VwWorkflowExecution $execution): void
    {
        $readyNodes = $this->getReadyNodes($execution);

        foreach ($readyNodes as $node) {
            $nodeId = $node['id'];

            // Mark as running
            $execution->setNodeResult($nodeId, [
                'status' => 'running',
                'started_at' => now()->toISOString(),
            ]);
            $execution->current_node_id = $nodeId;
            $execution->save();

            // Execute
            $result = $this->executeNode($execution, $node);

            if ($result['status'] === 'completed') {
                $this->completeNode($execution, $nodeId, $result['output']);
            } elseif ($result['status'] === 'waiting') {
                // Async node — mark as waiting, don't advance
                $execution->setNodeResult($nodeId, [
                    'status' => 'waiting',
                    'started_at' => now()->toISOString(),
                    'output' => $result['output'],
                ]);
                $execution->save();
            } elseif ($result['status'] === 'paused') {
                // User input needed — pause the whole execution
                $execution->status = 'paused';
                $execution->setNodeResult($nodeId, [
                    'status' => 'paused',
                    'started_at' => now()->toISOString(),
                    'output' => $result['output'],
                ]);
                $execution->save();
                return; // Stop processing until user provides input
            } elseif ($result['status'] === 'failed') {
                $execution->setNodeResult($nodeId, [
                    'status' => 'failed',
                    'started_at' => now()->toISOString(),
                    'error' => $result['error'],
                    'timing' => $result['_timing'] ?? 0,
                ]);
                $execution->status = 'failed';
                $execution->save();
                return; // Stop on failure
            }
        }
    }

    /**
     * Mark a node as completed, store outputs, and advance.
     */
    protected function completeNode(VwWorkflowExecution $execution, string $nodeId, array $output): void
    {
        $node = $execution->getNode($nodeId);
        $dataBus = new WorkflowDataBus($execution);

        // Store outputs to data bus
        if (!empty($node['outputs'])) {
            $dataBus->storeOutputs($node['outputs'], $output);
        }

        // Update node result
        $existing = $execution->getNodeResult($nodeId) ?? [];
        $execution->setNodeResult($nodeId, array_merge($existing, [
            'status' => 'completed',
            'completed_at' => now()->toISOString(),
            'output' => $output,
            'timing' => $output['_timing'] ?? ($existing['timing'] ?? 0),
        ]));
        $execution->save();

        Log::info("[WorkflowEngine] Node '{$nodeId}' completed");

        // Advance to next nodes
        $this->advance($execution, $nodeId);
    }

    /**
     * Check if the workflow is complete (all terminal nodes done).
     */
    protected function checkCompletion(VwWorkflowExecution $execution): void
    {
        $nodes = $execution->getNodes();
        $nodeResults = $execution->node_results ?? [];

        $allCompleted = true;
        foreach ($nodes as $node) {
            $result = $nodeResults[$node['id']] ?? null;
            if (!$result || ($result['status'] ?? '') !== 'completed') {
                $allCompleted = false;
                break;
            }
        }

        if ($allCompleted) {
            $execution->status = 'completed';
            $execution->completed_at = now();
            $execution->save();

            Log::info("[WorkflowEngine] Execution #{$execution->id} completed successfully");
        }
    }

    /**
     * Resolve config values that contain data_bus or project references.
     */
    protected function resolveConfig(array $config, WorkflowDataBus $dataBus): array
    {
        $resolved = [];

        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $resolved[$key] = $dataBus->resolveValue($value);
            } elseif (is_array($value)) {
                $resolved[$key] = $this->resolveConfig($value, $dataBus);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Get the execution status summary for the UI.
     */
    public function getExecutionSummary(VwWorkflowExecution $execution): array
    {
        $nodes = $execution->getNodes();
        $edges = $execution->getEdges();
        $nodeResults = $execution->node_results ?? [];

        $summary = [
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'current_node_id' => $execution->current_node_id,
            'nodes' => [],
            'edges' => $edges,
        ];

        foreach ($nodes as $node) {
            $result = $nodeResults[$node['id']] ?? null;
            $summary['nodes'][] = [
                'id' => $node['id'],
                'type' => $node['type'],
                'name' => $node['name'],
                'description' => $node['description'] ?? '',
                'status' => $result['status'] ?? 'pending',
                'timing' => $result['timing'] ?? null,
                'error' => $result['error'] ?? null,
                'has_output' => !empty($result['output']),
                'config' => $node['config'] ?? [],
            ];
        }

        return $summary;
    }
}
