<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;

/**
 * Pauses workflow execution to collect user input.
 *
 * When this node executes, it returns immediately with status 'paused'
 * and the workflow engine stops advancing. The UI shows the input form.
 * When the user provides input, WorkflowEngine::resume() is called
 * with the user's selection as the result.
 */
class UserInputExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'user_input';
    }

    public function isAsync(): bool
    {
        return false; // Not async — it pauses the pipeline
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        // Return the configuration for the UI to render the input form
        return [
            'input_type' => $config['input_type'] ?? 'text',
            'source' => $inputs['source'] ?? $config['source'] ?? null,
            'label' => $config['label'] ?? 'Please provide input',
            'options' => $config['options'] ?? [],
            'waiting_for_input' => true,
        ];
    }
}
