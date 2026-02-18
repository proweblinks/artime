<?php

namespace Modules\AppVideoWizard\Services\Workflow;

use Modules\AppVideoWizard\Models\VwWorkflowExecution;

/**
 * Manages data flow between workflow nodes.
 *
 * The data bus is a shared key-value store (JSON) on the execution record.
 * Node inputs reference bus paths like "data_bus.viral_ideas" which resolve
 * to the actual stored values. Node outputs write back to the bus.
 */
class WorkflowDataBus
{
    protected VwWorkflowExecution $execution;

    public function __construct(VwWorkflowExecution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * Resolve a node's input map to actual values from the data bus.
     *
     * Input map format: ["theme" => "data_bus.user_input.theme", "count" => 6]
     * - Strings starting with "data_bus." are resolved from the bus
     * - Strings starting with "from_project" are resolved from the project
     * - Other values are passed through as literals
     */
    public function resolveInputs(array $inputMap): array
    {
        $resolved = [];

        foreach ($inputMap as $key => $ref) {
            $resolved[$key] = $this->resolveValue($ref);
        }

        return $resolved;
    }

    /**
     * Resolve a single value reference.
     */
    public function resolveValue($ref)
    {
        if (!is_string($ref)) {
            return $ref;
        }

        // Resolve data_bus references
        if (str_starts_with($ref, 'data_bus.')) {
            $path = substr($ref, 9); // Remove "data_bus." prefix
            return $this->get($path);
        }

        // Resolve project references
        if ($ref === 'from_project' || str_starts_with($ref, 'from_project.')) {
            return $this->resolveProjectValue($ref);
        }

        // Literal value
        return $ref;
    }

    /**
     * Store node outputs to the data bus.
     *
     * Output map format: ["ideas" => "data_bus.viral_ideas"]
     * Result format: ["ideas" => [...array of ideas...]]
     */
    public function storeOutputs(array $outputMap, array $result): void
    {
        foreach ($outputMap as $resultKey => $busPath) {
            if (!isset($result[$resultKey])) {
                continue;
            }

            if (str_starts_with($busPath, 'data_bus.')) {
                $path = substr($busPath, 9);
                $this->set($path, $result[$resultKey]);
            }
        }

        $this->save();
    }

    /**
     * Get a value from the data bus using dot notation.
     */
    public function get(string $path, $default = null)
    {
        return data_get($this->execution->data_bus ?? [], $path, $default);
    }

    /**
     * Set a value on the data bus.
     */
    public function set(string $path, $value): void
    {
        $dataBus = $this->execution->data_bus ?? [];
        data_set($dataBus, $path, $value);
        $this->execution->data_bus = $dataBus;
    }

    /**
     * Merge an array of values into the data bus.
     */
    public function merge(array $data): void
    {
        $dataBus = $this->execution->data_bus ?? [];
        $this->execution->data_bus = array_merge($dataBus, $data);
    }

    /**
     * Save the data bus to the database.
     */
    public function save(): void
    {
        $this->execution->save();
    }

    /**
     * Get the full data bus contents.
     */
    public function all(): array
    {
        return $this->execution->data_bus ?? [];
    }

    /**
     * Resolve a "from_project" reference using the associated project.
     */
    protected function resolveProjectValue(string $ref)
    {
        $project = $this->execution->project;
        if (!$project) {
            return null;
        }

        // "from_project" alone maps to common fields based on context
        if ($ref === 'from_project') {
            return null; // Must be handled by the executor with context
        }

        // "from_project.aspect_ratio" etc.
        $field = substr($ref, 13); // Remove "from_project."

        $projectFieldMap = [
            'aspect_ratio' => $project->aspect_ratio,
            'platform' => $project->platform,
            'target_duration' => $project->target_duration,
            'format' => $project->format,
            'production_type' => $project->production_type,
            'production_subtype' => $project->production_subtype,
            'ai_tier' => $project->content_config['aiEngine'] ?? $project->content_config['aiModelTier'] ?? 'grok',
            'video_engine' => $project->content_config['videoModel']['model'] ?? 'seedance',
            'language' => $project->content_config['language'] ?? 'en',
        ];

        return $projectFieldMap[$field] ?? data_get($project->toArray(), $field);
    }
}
