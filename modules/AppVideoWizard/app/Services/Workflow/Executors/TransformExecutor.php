<?php

namespace Modules\AppVideoWizard\Services\Workflow\Executors;

use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\Workflow\NodeExecutorInterface;

/**
 * Executes synchronous data transformations.
 *
 * Supports multiple transform types:
 * - compose_string: Template-based string composition
 * - php_method: Calls a service method
 * - seedance_assemble: Assembles a Seedance-format video prompt
 * - array_pick: Picks a value from an array by key
 * - merge: Merges multiple inputs into one object
 */
class TransformExecutor implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'transform';
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function execute(array $config, array $inputs, WizardProject $project): array
    {
        $transform = $config['transform'] ?? 'passthrough';

        return match ($transform) {
            'compose_string' => $this->composeString($config, $inputs),
            'php_method' => $this->callPhpMethod($config, $inputs, $project),
            'seedance_assemble' => $this->seedanceAssemble($config, $inputs),
            'array_pick' => $this->arrayPick($config, $inputs),
            'merge' => $this->mergeInputs($inputs),
            'passthrough' => $inputs,
            default => throw new \RuntimeException("Unknown transform type: {$transform}"),
        };
    }

    /**
     * Compose a string from a template with {placeholder} replacements.
     */
    protected function composeString(array $config, array $inputs): array
    {
        $template = $config['template'] ?? '';

        $result = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($inputs) {
            $key = $matches[1];
            return $inputs[$key] ?? $matches[0];
        }, $template);

        // The first key in the output map will receive this value
        return ['result' => $result];
    }

    /**
     * Call a PHP service method.
     */
    protected function callPhpMethod(array $config, array $inputs, WizardProject $project): array
    {
        $class = $config['class'] ?? null;
        $method = $config['method'] ?? null;

        if (!$class || !$method) {
            throw new \RuntimeException("php_method transform requires 'class' and 'method' in config");
        }

        // Resolve the service class (try module namespace first)
        $fqcn = "Modules\\AppVideoWizard\\Services\\{$class}";
        if (!class_exists($fqcn)) {
            $fqcn = $class; // Try as fully-qualified class name
        }

        $service = app($fqcn);

        if (!method_exists($service, $method)) {
            throw new \RuntimeException("Method {$method} does not exist on {$fqcn}");
        }

        // Call with the first input value or all inputs
        $inputValues = array_values($inputs);
        $result = $service->$method(...$inputValues);

        // Wrap scalar results
        if (!is_array($result)) {
            return ['result' => $result];
        }

        return $result;
    }

    /**
     * Assemble a Seedance video prompt with camera instructions.
     */
    protected function seedanceAssemble(array $config, array $inputs): array
    {
        $basePrompt = $inputs['base_prompt'] ?? '';
        $cameraMove = $config['camera_move'] ?? 'none';
        $styleAnchor = $config['style_anchor'] ?? '';

        $cameraMap = [
            'none' => '',
            'push_in' => 'The camera slowly pushes in.',
            'pull_out' => 'The camera gently pulls out.',
            'pan_left' => 'The camera pans smoothly to the left.',
            'pan_right' => 'The camera pans smoothly to the right.',
            'orbit' => 'The camera orbits slowly around the subject.',
            'tracking' => 'The camera tracks alongside the subject.',
            'handheld' => 'Handheld camera with subtle natural movement.',
            'static' => 'The camera remains perfectly still.',
        ];

        $cameraInstruction = $cameraMap[$cameraMove] ?? '';

        $parts = array_filter([
            $basePrompt,
            $cameraInstruction,
            $styleAnchor,
        ]);

        return ['result' => implode(' ', $parts)];
    }

    /**
     * Pick a value from an input array by key.
     */
    protected function arrayPick(array $config, array $inputs): array
    {
        $key = $config['key'] ?? null;
        $source = $inputs['source'] ?? $inputs;

        if ($key && is_array($source)) {
            return ['result' => $source[$key] ?? null];
        }

        return ['result' => $source];
    }

    /**
     * Merge all inputs into a single result object.
     */
    protected function mergeInputs(array $inputs): array
    {
        return ['result' => $inputs];
    }
}
