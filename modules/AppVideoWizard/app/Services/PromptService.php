<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\VwPrompt;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PromptService
{
    /**
     * Default prompt configurations (fallback when DB is empty).
     */
    protected static array $defaultPrompts = [];

    /**
     * Get a prompt by slug, compile it with variables, and return the result.
     */
    public function getCompiledPrompt(string $slug, array $variables = []): ?string
    {
        $prompt = $this->getPrompt($slug);

        if (!$prompt) {
            Log::warning("VideoWizard: Prompt not found: {$slug}, using fallback");
            return $this->getFallbackPrompt($slug, $variables);
        }

        return $prompt->compile($variables);
    }

    /**
     * Get a prompt model by slug.
     */
    public function getPrompt(string $slug): ?VwPrompt
    {
        return VwPrompt::getBySlug($slug);
    }

    /**
     * Get prompt settings (model, temperature, max_tokens).
     */
    public function getPromptSettings(string $slug): array
    {
        $prompt = $this->getPrompt($slug);

        if (!$prompt) {
            return [
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ];
        }

        return [
            'model' => $prompt->model,
            'temperature' => $prompt->temperature,
            'max_tokens' => $prompt->max_tokens,
            'version' => $prompt->version,
        ];
    }

    /**
     * Get all available prompts.
     */
    public function getAllPrompts(): array
    {
        return VwPrompt::orderBy('name')->get()->toArray();
    }

    /**
     * Get fallback prompt when DB prompt is not available.
     */
    protected function getFallbackPrompt(string $slug, array $variables = []): ?string
    {
        $defaults = $this->getDefaultPrompts();

        if (!isset($defaults[$slug])) {
            Log::error("VideoWizard: No fallback prompt found for: {$slug}");
            return null;
        }

        $template = $defaults[$slug]['template'];

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace(['{{' . $key . '}}', '{$' . $key . '}'], $value, $template);
            }
        }

        return $template;
    }

    /**
     * Get default prompt configurations.
     */
    public function getDefaultPrompts(): array
    {
        return [
            'script_generation' => [
                'name' => 'Script Generation',
                'description' => 'Main prompt for generating video scripts with scenes, narration, and visual descriptions.',
                'variables' => [
                    'topic', 'tone', 'toneGuide', 'contentDepth', 'depthGuide',
                    'duration', 'minutes', 'targetWords', 'sceneCount',
                    'wordsPerScene', 'avgSceneDuration', 'additionalInstructions'
                ],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'template' => <<<'PROMPT'
You are an expert video scriptwriter creating a {{minutes}}-minute video script.

TOPIC: {{topic}}
TONE: {{tone}} - {{toneGuide}}
CONTENT DEPTH: {{contentDepth}} - {{depthGuide}}
TOTAL DURATION: {{duration}} seconds ({{minutes}} minutes)
TARGET WORD COUNT: {{targetWords}} words
NUMBER OF SCENES: {{sceneCount}}
WORDS PER SCENE: ~{{wordsPerScene}}
SCENE DURATION: ~{{avgSceneDuration}} seconds each

{{additionalInstructions}}

SCRIPT STRUCTURE:
1. HOOK (first 5 seconds) - Grab attention immediately with a bold statement or question
2. INTRODUCTION (10-15 seconds) - Set expectations for what viewers will learn
3. MAIN CONTENT ({{sceneCount}} scenes) - Deliver value with retention hooks every 30-45 seconds
4. CALL TO ACTION (5-10 seconds) - Clear next step for viewer

RESPOND WITH ONLY THIS JSON (no markdown code blocks, no explanation, just pure JSON):
{
  "title": "SEO-optimized title (max 60 chars)",
  "hook": "Attention-grabbing opening (5-10 words)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title",
      "narration": "What narrator says ({{wordsPerScene}} words)",
      "visualDescription": "Detailed visual for AI image generation (describe setting, mood, colors, lighting, composition)",
      "duration": {{avgSceneDuration}},
      "kenBurns": {
        "startScale": 1.0,
        "endScale": 1.15,
        "startX": 0.5,
        "startY": 0.5,
        "endX": 0.5,
        "endY": 0.4
      }
    }
  ],
  "cta": "Clear call to action",
  "totalDuration": {{duration}},
  "wordCount": {{targetWords}}
}

CRITICAL REQUIREMENTS:
- Generate EXACTLY {{sceneCount}} scenes
- Each scene narration MUST be approximately {{wordsPerScene}} words
- Total narration should equal approximately {{targetWords}} words
- Visual descriptions must be detailed enough for AI image generation
- Include varied Ken Burns movements (scale, position changes)
- Scene durations should add up to approximately {{duration}} seconds
- NO markdown formatting - output raw JSON only
PROMPT
            ],

            'script_outline' => [
                'name' => 'Script Outline',
                'description' => 'Generates an outline/structure for long-form videos (over 5 minutes).',
                'variables' => [
                    'topic', 'tone', 'duration', 'minutes', 'sectionCount', 'additionalInstructions'
                ],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'template' => <<<'PROMPT'
You are a professional video content strategist. Create a detailed outline for a {{minutes}}-minute video.

TOPIC: {{topic}}
TONE: {{tone}}
TOTAL DURATION: {{duration}} seconds ({{minutes}} minutes)
SECTIONS NEEDED: {{sectionCount}}

{{additionalInstructions}}

Create an outline that divides this video into {{sectionCount}} main sections. Each section should:
- Have a clear focus and purpose
- Build on the previous section
- Include engagement hooks every 30-45 seconds

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "title": "SEO-optimized video title (max 60 chars)",
  "hook": "Attention-grabbing opening line (5-10 words)",
  "sections": [
    {
      "title": "Section title",
      "focus": "What this section covers",
      "duration": 90,
      "keyPoints": ["point 1", "point 2", "point 3"],
      "engagementHook": "Question or statement to keep viewers watching"
    }
  ],
  "cta": "Call to action text"
}
PROMPT
            ],

            'section_scenes' => [
                'name' => 'Section Scenes',
                'description' => 'Generates detailed scenes for a specific section of a long-form video.',
                'variables' => [
                    'topic', 'sectionTitle', 'sectionFocus', 'sectionDuration',
                    'sceneCount', 'avgSceneDuration', 'tone', 'keyPointsText'
                ],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 3000,
                'template' => <<<'PROMPT'
You are an expert video script writer. Create {{sceneCount}} detailed scenes for a video section.

OVERALL TOPIC: {{topic}}
SECTION: {{sectionTitle}}
SECTION FOCUS: {{sectionFocus}}
SECTION DURATION: {{sectionDuration}} seconds
SCENES NEEDED: {{sceneCount}}
AVERAGE SCENE DURATION: {{avgSceneDuration}} seconds
TONE: {{tone}}

KEY POINTS TO COVER:
{{keyPointsText}}

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title (descriptive)",
      "narration": "Exactly what the narrator says (25-50 words)",
      "visualDescription": "Detailed visual description for AI image generation (describe setting, mood, colors, composition)",
      "duration": {{avgSceneDuration}}
    }
  ]
}

REQUIREMENTS:
- Each scene narration should be 25-50 words
- Visual descriptions must be detailed and specific for image generation
- Include mood, lighting, colors, and composition details in visuals
- Narration should flow naturally from scene to scene
PROMPT
            ],

            'scene_regenerate' => [
                'name' => 'Scene Regeneration',
                'description' => 'Regenerates a single scene with fresh content while maintaining the video theme.',
                'variables' => [
                    'topic', 'tone', 'sceneIndex', 'existingTitle', 'existingDuration', 'existingId'
                ],
                'model' => 'gpt-4',
                'temperature' => 0.8,
                'max_tokens' => 1000,
                'template' => <<<'PROMPT'
You are an expert video scriptwriter. Regenerate this scene with fresh content while maintaining the video's theme.

TOPIC: {{topic}}
TONE: {{tone}}
SCENE NUMBER: {{sceneIndex}}
CURRENT SCENE TITLE: {{existingTitle}}
CURRENT DURATION: {{existingDuration}} seconds

Generate a new version of this scene with:
- Fresh narration that fits the same duration
- New visual description for AI image generation
- Maintain connection to the overall topic

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "id": "{{existingId}}",
  "title": "New scene title",
  "narration": "New narrator text (match duration)",
  "visualDescription": "Detailed visual for AI image generation",
  "visualPrompt": "Concise prompt for image AI (50-100 words)",
  "voiceover": {
    "enabled": true,
    "text": "",
    "voiceId": null,
    "status": "pending"
  },
  "duration": {{existingDuration}},
  "mood": "cinematic",
  "status": "draft"
}
PROMPT
            ],

            'visual_prompt' => [
                'name' => 'Visual Prompt Generation',
                'description' => 'Generates detailed visual prompts for AI image generation based on scene narration.',
                'variables' => [
                    'narration', 'conceptContext', 'styleContext', 'mood', 'productionType', 'aspectRatio'
                ],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'template' => <<<'PROMPT'
You are a cinematographer creating a visual prompt for AI image generation.

NARRATION: {{narration}}
{{conceptContext}}
{{styleContext}}
MOOD: {{mood}}
PRODUCTION TYPE: {{productionType}}
ASPECT RATIO: {{aspectRatio}}

Create a detailed visual prompt that:
1. Captures the essence of the narration visually
2. Includes specific details: lighting, colors, composition, camera angle
3. Sets the appropriate mood and atmosphere
4. Is optimized for AI image generation (Stable Diffusion/DALL-E style)

RESPOND WITH ONLY THE PROMPT TEXT (no JSON, no explanation, just the visual description):
PROMPT
            ],

            'voiceover_dialogue' => [
                'name' => 'Voiceover Dialogue Conversion',
                'description' => 'Converts narration into natural dialogue between characters.',
                'variables' => ['narration', 'tone'],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'template' => <<<'PROMPT'
Convert this narration into natural dialogue between characters.

NARRATION: {{narration}}
TONE: {{tone}}

Create dialogue that:
1. Conveys the same information as the narration
2. Sounds natural and conversational
3. Uses character names (SPEAKER: dialogue format)

RESPOND WITH ONLY THE DIALOGUE TEXT (no explanation):
PROMPT
            ],

            'script_improve' => [
                'name' => 'Script Improvement',
                'description' => 'Improves/refines an existing script based on specific instructions.',
                'variables' => ['scriptJson', 'instruction'],
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'template' => <<<'PROMPT'
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
{{scriptJson}}

INSTRUCTION: {{instruction}}

RESPOND WITH ONLY THE IMPROVED JSON (no markdown, no explanation):
PROMPT
            ],
        ];
    }

    /**
     * Log a generation event.
     */
    public function logGeneration(
        string $promptSlug,
        array $inputData,
        array $outputData,
        string $status = 'success',
        ?string $errorMessage = null,
        ?int $tokensUsed = null,
        ?int $durationMs = null,
        ?int $projectId = null
    ): VwGenerationLog {
        $prompt = $this->getPrompt($promptSlug);

        if ($status === 'success') {
            return VwGenerationLog::logSuccess(
                $promptSlug,
                $inputData,
                $outputData,
                $tokensUsed,
                $durationMs,
                $projectId,
                auth()->id(),
                session('current_team_id'),
                $prompt?->version
            );
        }

        return VwGenerationLog::logFailure(
            $promptSlug,
            $inputData,
            $errorMessage ?? 'Unknown error',
            $durationMs,
            $projectId,
            auth()->id(),
            session('current_team_id'),
            $prompt?->version
        );
    }

    /**
     * Seed the default prompts into the database.
     */
    public function seedDefaultPrompts(): void
    {
        $defaults = $this->getDefaultPrompts();

        foreach ($defaults as $slug => $config) {
            VwPrompt::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'prompt_template' => $config['template'],
                    'variables' => $config['variables'],
                    'model' => $config['model'],
                    'temperature' => $config['temperature'],
                    'max_tokens' => $config['max_tokens'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Get tone guide text.
     */
    public function getToneGuide(string $tone): string
    {
        $guides = [
            'engaging' => 'conversational, energetic, keeps viewers hooked with dynamic pacing',
            'professional' => 'polished, authoritative, business-appropriate with credibility',
            'casual' => 'friendly, relaxed, like talking to a friend',
            'inspirational' => 'uplifting, motivational, emotionally resonant',
            'educational' => 'clear, structured, informative with examples',
        ];

        return $guides[$tone] ?? $guides['engaging'];
    }

    /**
     * Get content depth guide text.
     */
    public function getDepthGuide(string $depth): string
    {
        $guides = [
            'quick' => 'Focus on key points only, minimal detail',
            'standard' => 'Balanced coverage with some examples',
            'detailed' => 'Include examples, statistics, and supporting details',
            'deep' => 'Comprehensive analysis with multiple perspectives',
        ];

        return $guides[$depth] ?? $guides['detailed'];
    }
}
