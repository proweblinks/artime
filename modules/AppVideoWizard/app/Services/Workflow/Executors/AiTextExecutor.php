<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;
use Modules\AppVideoWizard\Services\ConceptService;
use AI;

/**
 * Executes AI text generation nodes.
 *
 * Supports two modes:
 * 1. Service method: Delegates to a service class method (e.g., ConceptService::generateViralIdeas)
 * 2. Direct AI call: Builds prompt from template and calls AI::process directly
 */
class AiTextExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'ai_text';
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        // Mode 1: Delegate to a service method
        if (!empty($config['service']) && !empty($config['method'])) {
            return $this->executeServiceMethod($config, $inputs, $project);
        }

        // Mode 2: Direct AI call with prompt template
        return $this->executeDirectAI($config, $inputs, $project);
    }

    /**
     * Call a service method (e.g., ConceptService::generateViralIdeas).
     */
    protected function executeServiceMethod(array $config, array $inputs, WizardProject $project): array
    {
        $serviceClass = $config['service'];
        $method = $config['method'];

        // Resolve service class from module namespace
        $fqcn = "Modules\\AppVideoWizard\\Services\\{$serviceClass}";
        if (!class_exists($fqcn)) {
            $fqcn = $serviceClass;
        }

        $service = app($fqcn);

        if (!method_exists($service, $method)) {
            throw new \RuntimeException("Method {$method} does not exist on {$fqcn}");
        }

        // Build options array from config + inputs
        $options = array_merge($config, $inputs);

        // Map common input keys to method parameters
        $theme = $inputs['theme'] ?? $inputs['input'] ?? '';
        $result = $service->$method($theme, $options);

        // Wrap result for output mapping
        if (isset($result['result']) && is_array($result['result'])) {
            return ['ideas' => $result['result'], 'raw_response' => $result];
        }

        return $result;
    }

    /**
     * Direct AI generation using a prompt template.
     */
    protected function executeDirectAI(array $config, array $inputs, WizardProject $project): array
    {
        $promptTemplate = $config['prompt_template'] ?? '';
        $rules = $config['rules'] ?? '';
        $example = $config['example'] ?? '';
        $aiTier = $config['ai_tier'] ?? 'economy';
        $maxTokens = (int) ($config['max_tokens'] ?? 4000);
        $temperature = (float) ($config['temperature'] ?? 0.85);

        // Replace {placeholders} in template with input values
        $prompt = $this->buildPrompt($promptTemplate, $rules, $example, $inputs);

        // Resolve AI tier config
        $tierConfig = ConceptService::AI_MODEL_TIERS[$aiTier] ?? ConceptService::AI_MODEL_TIERS['economy'];

        $teamId = $project->team_id ?? session('current_team_id', 0);

        Log::info("[AiTextExecutor] Calling AI with tier '{$aiTier}', model '{$tierConfig['model']}'");

        $result = AI::processWithOverride(
            $prompt,
            $tierConfig['provider'],
            $tierConfig['model'],
            'text',
            [
                'maxResult' => 1,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ],
            $teamId
        );

        $text = $result['result'] ?? ($result['text'] ?? '');

        return [
            'text' => $text,
            'raw_response' => $result,
            'prompt_used' => $prompt,
        ];
    }

    /**
     * Build the full prompt from template, rules, and example.
     */
    protected function buildPrompt(string $template, string $rules, string $example, array $inputs): string
    {
        // Replace {placeholder} tokens with input values
        $prompt = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($inputs) {
            $key = $matches[1];
            $value = $inputs[$key] ?? null;

            if ($value === null) {
                return $matches[0]; // Keep placeholder if no value
            }

            if (is_array($value)) {
                return json_encode($value);
            }

            return (string) $value;
        }, $template);

        // Append rules and example if provided
        if (!empty($rules)) {
            $prompt .= "\n\n--- RULES ---\n" . $rules;
        }

        if (!empty($example)) {
            $prompt .= "\n\n--- EXAMPLE ---\n" . $example;
        }

        return $prompt;
    }
}
