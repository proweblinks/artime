<?php

namespace Modules\AppVideoWizard\Services\Workflow;

use Modules\AppVideoWizard\Models\WizardProject;

interface NodeExecutorInterface
{
    /**
     * Execute the node with resolved config and inputs.
     *
     * @param array $config The node's config block (already resolved)
     * @param array $inputs The resolved input values from the data bus
     * @param WizardProject $project The associated project
     * @return array Result array — keys match the node's output map keys
     */
    public function execute(array $config, array $inputs, WizardProject $project): array;

    /**
     * Get the node type this executor handles.
     */
    public function getType(): string;

    /**
     * Whether this executor runs asynchronously (polling required).
     */
    public function isAsync(): bool;
}
