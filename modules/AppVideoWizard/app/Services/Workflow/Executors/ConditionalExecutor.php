<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;

/**
 * Evaluates a condition and returns which branch to take.
 * Used for branching logic (e.g., seedance video generation paths).
 */
class ConditionalExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'conditional';
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        $field = $config['field'] ?? 'value';
        $operator = $config['operator'] ?? 'equals';
        $compareValue = $config['compare_value'] ?? null;
        $inputValue = $inputs[$field] ?? $inputs['value'] ?? null;

        $matched = match ($operator) {
            'equals' => $inputValue === $compareValue,
            'not_equals' => $inputValue !== $compareValue,
            'contains' => is_string($inputValue) && str_contains($inputValue, $compareValue),
            'in' => is_array($compareValue) && in_array($inputValue, $compareValue),
            'truthy' => !empty($inputValue),
            'falsy' => empty($inputValue),
            default => false,
        };

        return [
            'matched' => $matched,
            'branch' => $matched ? ($config['true_branch'] ?? 'true') : ($config['false_branch'] ?? 'false'),
            'value' => $inputValue,
        ];
    }
}
