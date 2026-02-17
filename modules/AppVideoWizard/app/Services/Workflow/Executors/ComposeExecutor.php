<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;

/**
 * Merges multiple inputs into a single output.
 * Used for combining data from parallel branches.
 */
class ComposeExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'compose';
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        $mode = $config['mode'] ?? 'merge';

        return match ($mode) {
            'merge' => ['result' => $inputs],
            'concat_string' => ['result' => implode($config['separator'] ?? ' ', array_filter($inputs))],
            'first_non_null' => ['result' => collect($inputs)->first(fn($v) => $v !== null)],
            default => ['result' => $inputs],
        };
    }
}
