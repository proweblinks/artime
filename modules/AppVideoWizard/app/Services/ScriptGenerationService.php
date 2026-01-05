<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AIService;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;

class ScriptGenerationService
{
    /**
     * Generate a video script based on project configuration.
     */
    public function generateScript(WizardProject $project, array $options = []): array
    {
        $concept = $project->concept ?? [];
        $contentConfig = $project->content_config ?? [];
        $productionType = $project->getProductionTypeConfig();

        $topic = $concept['refinedConcept'] ?? $concept['rawInput'] ?? $contentConfig['topic'] ?? '';
        $tone = $contentConfig['tone'] ?? 'engaging';
        $duration = $project->target_duration;
        $style = $contentConfig['style'] ?? 'engaging';

        // Calculate target word count based on duration
        // Average speaking rate is ~150 words per minute
        $targetWords = (int) ($duration / 60 * 150);

        $prompt = $this->buildScriptPrompt([
            'topic' => $topic,
            'tone' => $tone,
            'duration' => $duration,
            'targetWords' => $targetWords,
            'style' => $style,
            'productionType' => $productionType,
            'concept' => $concept,
            'aspectRatio' => $project->aspect_ratio,
        ]);

        // Use ArTime's existing AI service
        $response = AIService::generate($prompt, [
            'model' => config('appvideowizard.ai_models.script.model', 'gpt-4'),
            'max_tokens' => 4000,
            'temperature' => 0.8,
        ]);

        // Parse the response
        $script = $this->parseScriptResponse($response);

        return $script;
    }

    /**
     * Build the script generation prompt.
     */
    protected function buildScriptPrompt(array $params): string
    {
        $topic = $params['topic'];
        $tone = $params['tone'];
        $duration = $params['duration'];
        $targetWords = $params['targetWords'];
        $concept = $params['concept'];

        $toneDescriptions = [
            'engaging' => 'conversational, energetic, keeps viewers hooked with dynamic pacing and relatable language',
            'educational' => 'informative, clear explanations, authoritative yet accessible, with structured learning points',
            'entertaining' => 'fun, humorous, uses storytelling and personality to captivate, includes jokes',
            'professional' => 'polished, business-appropriate, credible and trustworthy, with data-backed insights',
        ];

        $toneGuide = $toneDescriptions[$tone] ?? $toneDescriptions['engaging'];

        // Calculate scene count based on duration
        $sceneCount = max(3, min(10, (int) ($duration / 20)));

        $prompt = <<<PROMPT
You are an expert video script writer. Create an engaging video script.

REQUIREMENTS:
- Topic: {$topic}
- Tone: {$tone} - {$toneGuide}
- Target Duration: {$duration} seconds (~{$targetWords} words)
- Number of Scenes: {$sceneCount}

SCRIPT STRUCTURE:
1. Hook (first 3-5 seconds) - Grab attention immediately
2. Introduction - Brief overview of what viewers will learn
3. Main Content - {$sceneCount} scenes with clear value
4. Call to Action - Subscribe, like, comment
5. Outro - Wrap up

FORMAT YOUR RESPONSE AS JSON:
{
  "title": "Video title (SEO optimized, max 60 chars)",
  "hook": "Opening hook text (attention grabber)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title",
      "narration": "What the narrator says",
      "visualDescription": "What should be shown visually",
      "duration": 15,
      "kenBurns": {
        "startScale": 1.0,
        "endScale": 1.1,
        "startX": 0.5,
        "startY": 0.5,
        "endX": 0.5,
        "endY": 0.4
      }
    }
  ],
  "cta": "Call to action text",
  "totalDuration": {$duration},
  "wordCount": {$targetWords}
}

IMPORTANT:
- Each scene narration should be 10-30 words
- Visual descriptions should be detailed enough for image generation
- Include smooth Ken Burns camera movements for each scene
- Ensure total duration adds up to approximately {$duration} seconds
PROMPT;

        return $prompt;
    }

    /**
     * Parse the AI response into a structured script.
     */
    protected function parseScriptResponse(string $response): array
    {
        // Clean up response - extract JSON
        $response = trim($response);
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        // Try to parse JSON
        $script = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract JSON from the response
            preg_match('/\{[\s\S]*"scenes"[\s\S]*\}/', $response, $matches);
            if (!empty($matches[0])) {
                $script = json_decode($matches[0], true);
            }
        }

        if (!$script || !isset($script['scenes'])) {
            throw new \Exception('Failed to parse script response');
        }

        // Validate and fix scenes
        foreach ($script['scenes'] as $index => &$scene) {
            if (!isset($scene['id'])) {
                $scene['id'] = 'scene-' . ($index + 1);
            }
            if (!isset($scene['duration'])) {
                $scene['duration'] = 15;
            }
            if (!isset($scene['kenBurns'])) {
                $scene['kenBurns'] = [
                    'startScale' => 1.0,
                    'endScale' => 1.1,
                    'startX' => 0.5,
                    'startY' => 0.5,
                    'endX' => 0.5,
                    'endY' => 0.4,
                ];
            }
        }

        return $script;
    }

    /**
     * Improve/refine an existing script.
     */
    public function improveScript(array $script, string $instruction): array
    {
        $prompt = <<<PROMPT
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
```json
{$this->jsonEncode($script)}
```

INSTRUCTION: {$instruction}

Return the improved script in the same JSON format.
PROMPT;

        $response = AIService::generate($prompt, [
            'model' => config('appvideowizard.ai_models.script.model', 'gpt-4'),
            'max_tokens' => 4000,
        ]);

        return $this->parseScriptResponse($response);
    }

    /**
     * Encode array to JSON with proper formatting.
     */
    protected function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
