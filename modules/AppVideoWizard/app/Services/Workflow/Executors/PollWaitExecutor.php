<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;

/**
 * Waits for an async job to complete by polling.
 * The actual polling is handled by the Livewire component;
 * this executor stores the job reference and poll config.
 */
class PollWaitExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'poll_wait';
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        return [
            'job_id' => $inputs['job_id'] ?? null,
            'poll_interval' => $config['poll_interval'] ?? 5,
            'max_wait' => $config['max_wait'] ?? 300,
            'status_method' => $config['status_method'] ?? null,
            'waiting' => true,
        ];
    }
}
