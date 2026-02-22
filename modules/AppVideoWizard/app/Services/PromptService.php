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
     * Get a prompt by slug, compile both system_message and prompt_template with variables.
     * Returns null if prompt not found. Returns array with 'system' and 'user' keys.
     */
    public function getCompiledPromptWithSystem(string $slug, array $variables = []): ?array
    {
        $prompt = $this->getPrompt($slug);

        if (!$prompt) {
            Log::warning("VideoWizard: Prompt not found: {$slug}, using fallback for system+user");
            return $this->getFallbackPromptWithSystem($slug, $variables);
        }

        return [
            'system' => $prompt->compileSystemMessage($variables),
            'user' => $prompt->compile($variables),
        ];
    }

    /**
     * Get fallback prompt with system message when DB prompt is not available.
     */
    protected function getFallbackPromptWithSystem(string $slug, array $variables = []): ?array
    {
        $defaults = $this->getDefaultPrompts();

        if (!isset($defaults[$slug])) {
            return null;
        }

        $config = $defaults[$slug];
        $userTemplate = $config['template'];
        $systemTemplate = $config['system_message'] ?? null;

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $userTemplate = str_replace(['{{' . $key . '}}', '{$' . $key . '}'], $value, $userTemplate);
                if ($systemTemplate) {
                    $systemTemplate = str_replace(['{{' . $key . '}}', '{$' . $key . '}'], $value, $systemTemplate);
                }
            }
        }

        return [
            'system' => $systemTemplate,
            'user' => $userTemplate,
        ];
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
      "narration": "Character dialogue and speech segments ({{wordsPerScene}} words)",
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
      "narration": "Character dialogue and action (25-50 words using CHARACTER: format)",
      "visualDescription": "Detailed visual description for AI image generation (describe setting, mood, colors, composition)",
      "duration": {{avgSceneDuration}}
    }
  ]
}

REQUIREMENTS:
- Each scene should contain character dialogue (CHARACTER: text format) or monologue (25-50 words)
- Visual descriptions must be detailed and specific for image generation
- Include mood, lighting, colors, and composition details in visuals
- Dialogue should flow naturally from scene to scene
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
- Fresh character dialogue/monologue that fits the same duration
- New visual description for AI image generation
- Maintain connection to the overall topic

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "id": "{{existingId}}",
  "title": "New scene title",
  "narration": "Character dialogue and speech (match duration, use CHARACTER: format)",
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

            // =================================================================
            // Seedance Prompt Templates (structural, not AI prompts)
            // =================================================================

            'seedance_assembly' => [
                'name' => 'Seedance: Assembly',
                'description' => 'Master assembly template — controls part ordering and separators for the final Seedance prompt.',
                'variables' => ['subject_action', 'scene_context', 'continuity', 'camera', 'style', 'audio'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '{{subject_action}}. {{scene_context}}. {{continuity}}. {{camera}}. {{style}}. {{audio}}',
            ],

            'seedance_subject' => [
                'name' => 'Seedance: Subject',
                'description' => 'Per-character identity format — how each character is introduced in the prompt.',
                'variables' => ['character_name', 'role', 'brief_description'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '{{character_name}} ({{role}}, {{brief_description}})',
            ],

            'seedance_action_dialogue' => [
                'name' => 'Seedance: Action (Dialogue)',
                'description' => 'Action format when the character has dialogue — speech attribution + physical action.',
                'variables' => ['dialogue_text', 'physical_action'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => 'says "{{dialogue_text}}", {{physical_action}}',
            ],

            'seedance_action_no_dialogue' => [
                'name' => 'Seedance: Action (No Dialogue)',
                'description' => 'Action format when the character has no dialogue — physical action only.',
                'variables' => ['physical_action'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '{{physical_action}}',
            ],

            'seedance_camera' => [
                'name' => 'Seedance: Camera',
                'description' => 'Camera specification format — shot size + movement syntax.',
                'variables' => ['shot_size', 'movement_syntax'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '{{shot_size}}, {{movement_syntax}}',
            ],

            'seedance_style' => [
                'name' => 'Seedance: Style',
                'description' => 'Visual style direction — visual style + lighting + color treatment.',
                'variables' => ['visual_style', 'lighting', 'color_treatment'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '{{visual_style}}, {{lighting}}, {{color_treatment}}',
            ],

            'seedance_audio' => [
                'name' => 'Seedance: Audio',
                'description' => 'Audio/ambient direction — environmental sound cues.',
                'variables' => ['ambient_cues'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => 'No music. Only {{ambient_cues}}.',
            ],

            'seedance_continuity' => [
                'name' => 'Seedance: Continuity',
                'description' => 'Previous shot reference for visual continuity between shots.',
                'variables' => ['prev_shot_type', 'prev_character', 'prev_action'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => '[Previous: {{prev_shot_type}} shot, of {{prev_character}}, {{prev_action}}]',
            ],

            'seedance_technical_rules' => [
                'name' => 'Seedance: Technical Rules',
                'description' => 'Full Seedance 2.0 prompt engineering rules — injected as system context for prompt building.',
                'variables' => ['style_anchor'],
                'model' => 'n/a',
                'temperature' => 0,
                'max_tokens' => 0,
                'template' => <<<'PROMPT'
SEEDANCE 2.0 VIDEO PROMPT RULES:

FIVE-PART STRUCTURE — Every prompt follows: Subject → Action → Camera → Style → Audio
Each part is a single sentence separated by periods. Keep total prompt under 200 words.

SUBJECT — Name or describe each character clearly:
- Use uppercase names: "SARAH (detective, auburn hair)" not "the woman"
- For multiple subjects, describe each separately: "SARAH and MIKE"
- Do NOT describe face structure — the source IMAGE defines the face

ACTION — EXPLICIT MOTION is mandatory:
- Seedance CANNOT infer motion. Every movement must be explicitly described.
- WRONG: "the cat attacks" (too vague)
- RIGHT: "the cat slaps the man's face with its right paw"
- Specify body parts: which hand, which direction, what gets hit
- Use active verbs only. NO passive voice.
- BANNED weak verbs: "goes", "moves", "does", "gets", "starts", "begins"
- Include dialogue in quotes: says "Get off me!" while pushing back
- Include character sounds: meows, yells, screams, growls
- Include impact sounds: crashes, clattering, shattering
- For action scenes: emphasize "realistic physics" and "accurate body proportions"
- Add sensory details: textures, temperature cues, light quality on surfaces

CAMERA — One movement per shot:
- Wide shots: slow dolly or locked-off only, NO fast pans
- Medium shots: handheld = personal feel, gimbal = polished feel
- Close-ups: tiny push-ins only, AVOID pans
- SINGLE movement per shot — never combine two camera motions
- Describe camera style when relevant: "Smooth tracking shot" or "Static wide shot"

STYLE — Visual direction:
- Always end with style anchor: "{{style_anchor}}"
- Include lighting quality and color treatment
- Add atmospheric details: haze, dust, rain, fog

AUDIO — Sound direction:
- "No music. Only [ambient sounds]." for ambient-only shots
- Mention music style only if background music is needed
- Include environmental sounds caused by actions

ADVERBS — Use descriptive adverbs freely:
- High intensity: rapidly, violently, crazily, intensely, fiercely, powerfully
- Medium intensity: slowly, gently, steadily, smoothly, carefully
- Temporal: suddenly, immediately, then, finally, instantly

MULTI-REFERENCE — For character consistency across shots:
- Use @Image1, @Image2 notation when referencing multiple character images

BANNED:
- No semicolons in prompts
- No appearance/clothing descriptions (image defines this)
- No facial micro-expression descriptions
- No passive voice — only active verbs
- No background music descriptions (unless explicitly enabled)
- No conflicting camera directions in the same prompt
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
