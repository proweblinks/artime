<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Modules\AppVideoWizard\Services\SpeechSegment;
use Modules\AppVideoWizard\Services\SpeechSegmentParser;

class ScriptGenerationService
{
    /**
     * PromptService instance for loading prompts from DB.
     */
    protected PromptService $promptService;

    /**
     * ContextWindowService for maximized context generation (Phase 3).
     */
    protected ContextWindowService $contextService;

    /**
     * Constructor.
     */
    public function __construct(?PromptService $promptService = null, ?ContextWindowService $contextService = null)
    {
        $this->promptService = $promptService ?? new PromptService();
        $this->contextService = $contextService ?? new ContextWindowService();
    }

    /**
     * Speaking rate constants (words per minute)
     * Research: Normal conversational pace is 125-150 WPM
     */
    const WPM_SLOW = 120;
    const WPM_NORMAL = 140;
    const WPM_FAST = 160;

    /**
     * Scene duration constants (seconds)
     * Research: Optimal scene changes every 10-20 seconds for engagement
     */
    const SCENE_DURATION_MIN = 10;
    const SCENE_DURATION_MAX = 25;
    const SCENE_DURATION_DEFAULT = 15;

    /**
     * Maximum supported duration (20 minutes = 1200 seconds)
     */
    const MAX_DURATION_SECONDS = 1200;

    /**
     * AI Model Tier configurations.
     * Maps tier names to provider/model pairs.
     * Must match VideoWizard::AI_MODEL_TIERS
     */
    const AI_MODEL_TIERS = [
        'economy' => [
            'provider' => 'grok',
            'model' => 'grok-4-fast',
        ],
        'standard' => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ],
        'premium' => [
            'provider' => 'openai',
            'model' => 'gpt-4o',
        ],
    ];

    /**
     * Get provider and model for a given AI tier.
     *
     * @param string $tier The tier name (economy, standard, premium)
     * @return array ['provider' => string, 'model' => string]
     */
    protected function getAIConfigFromTier(string $tier): array
    {
        return self::AI_MODEL_TIERS[$tier] ?? self::AI_MODEL_TIERS['economy'];
    }

    /**
     * Call AI with tier-based model selection.
     * Uses processWithOverride to bypass global AI settings.
     *
     * @param string $prompt The prompt to send
     * @param string $tier The AI tier (economy, standard, premium)
     * @param int $teamId Team ID for quota tracking
     * @param array $options Additional options
     * @return array AI response
     */
    protected function callAIWithTier(string $prompt, string $tier, int $teamId, array $options = []): array
    {
        $config = $this->getAIConfigFromTier($tier);

        \Log::info('VideoWizard: AI call with tier', [
            'tier' => $tier,
            'provider' => $config['provider'],
            'model' => $config['model'],
        ]);

        return AI::processWithOverride(
            $prompt,
            $config['provider'],
            $config['model'],
            'text',
            $options,
            $teamId
        );
    }

    /**
     * Generate a video script based on project configuration.
     * Supports videos up to 20 minutes with intelligent scene chunking.
     */
    public function generateScript(WizardProject $project, array $options = []): array
    {
        $concept = $project->concept ?? [];
        $productionType = $project->getProductionTypeConfig();
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        $topic = $concept['refinedConcept'] ?? $concept['rawInput'] ?? '';
        $tone = $options['tone'] ?? $concept['suggestedTone'] ?? 'engaging';
        $duration = min($project->target_duration, self::MAX_DURATION_SECONDS);
        $contentDepth = $options['contentDepth'] ?? 'detailed';
        $additionalInstructions = $options['additionalInstructions'] ?? '';

        // AI Model Tier selection (economy, standard, premium)
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        // Narrative Structure Intelligence options
        $narrativePreset = $options['narrativePreset'] ?? null;
        $storyArc = $options['storyArc'] ?? null;
        $tensionCurve = $options['tensionCurve'] ?? null;
        $emotionalJourney = $options['emotionalJourney'] ?? null;

        // Dynamic cast and location guidance (based on duration and production type)
        $suggestedCharacterCount = $options['suggestedCharacterCount'] ?? null;
        $suggestedLocationCount = $options['suggestedLocationCount'] ?? null;
        $productionSubtype = $options['productionSubtype'] ?? null;

        // Story Bible constraint (Phase 1: Bible-First Architecture)
        // If the project has a Story Bible, use it to constrain script generation
        $storyBibleConstraint = $project->hasStoryBible() ? $project->getStoryBibleConstraint() : '';

        // Calculate script parameters
        $params = $this->calculateScriptParameters($duration, $contentDepth);

        \Log::info('VideoWizard: Generating script', [
            'teamId' => $teamId,
            'topic' => substr($topic, 0, 100),
            'duration' => $duration,
            'sceneCount' => $params['sceneCount'],
            'targetWords' => $params['targetWords'],
            'aiModelTier' => $aiModelTier,
        ]);

        // For videos over 5 minutes, use chunked generation
        if ($duration > 300) {
            return $this->generateLongFormScript($topic, $tone, $duration, $params, $concept, $productionType, $additionalInstructions, $teamId, $aiModelTier);
        }

        // Standard generation for shorter videos
        $promptParams = [
            'topic' => $topic,
            'tone' => $tone,
            'duration' => $duration,
            'targetWords' => $params['targetWords'],
            'sceneCount' => $params['sceneCount'],
            'wordsPerScene' => $params['wordsPerScene'],
            'productionType' => $productionType,
            'concept' => $concept,
            'contentDepth' => $contentDepth,
            'additionalInstructions' => $additionalInstructions,
            'aspectRatio' => $project->aspect_ratio,
            // Narrative Structure Intelligence
            'narrativePreset' => $narrativePreset,
            'storyArc' => $storyArc,
            'tensionCurve' => $tensionCurve,
            'emotionalJourney' => $emotionalJourney,
            // Story Bible constraint (Phase 1: Bible-First Architecture)
            'storyBibleConstraint' => $storyBibleConstraint,
            // Dynamic cast and location requirements
            'suggestedCharacterCount' => $suggestedCharacterCount,
            'suggestedLocationCount' => $suggestedLocationCount,
            'productionSubtype' => $productionSubtype,
        ];

        $prompt = $this->buildScriptPrompt($promptParams);
        $startTime = microtime(true);

        // Use tier-based AI model selection
        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 15000, // Ensure enough tokens for full script JSON
        ]);
        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        // Extract token usage from AI response
        $tokensUsed = $result['totalTokens'] ?? null;

        if (!empty($result['error'])) {
            \Log::error('VideoWizard: AI error', ['error' => $result['error']]);

            // Log the failure
            $this->logGeneration('script_generation', $promptParams, [], 'failed', $result['error'], $tokensUsed, $durationMs, $project->id);

            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        if (empty($response)) {
            \Log::error('VideoWizard: Empty AI response', ['result' => $result]);

            // Log the failure
            $this->logGeneration('script_generation', $promptParams, [], 'failed', 'Empty AI response', $tokensUsed, $durationMs, $project->id);

            throw new \Exception('AI returned an empty response. Please try again.');
        }

        \Log::info('VideoWizard: Parsing response', [
            'responseLength' => strlen($response),
            'responsePreview' => substr($response, 0, 300),
            'expectedSceneCount' => $params['sceneCount'],
        ]);

        $parsedScript = $this->parseScriptResponse($response, $duration, $params['sceneCount']);

        // Validate scene count and interpolate if needed
        $actualSceneCount = count($parsedScript['scenes'] ?? []);
        $expectedSceneCount = $params['sceneCount'];

        if ($actualSceneCount < $expectedSceneCount) {
            \Log::warning('VideoWizard: Scene count below expected', [
                'expected' => $expectedSceneCount,
                'actual' => $actualSceneCount,
                'deficit' => $expectedSceneCount - $actualSceneCount,
                'note' => 'Use progressive batch generation for exact scene counts',
            ]);
            // Note: Old interpolateScenes method removed - use progressive batch generation instead
            // The new batch system generates scenes incrementally with proper context continuity
        }

        // Log successful generation with token usage
        $this->logGeneration('script_generation', $promptParams, $parsedScript, 'success', null, $tokensUsed, $durationMs, $project->id);

        // Include metadata for external tracking
        $parsedScript['_meta'] = [
            'tokens_used' => $tokensUsed,
            'model' => $result['model'] ?? null,
        ];

        return $parsedScript;
    }

    /**
     * Log an AI generation event.
     */
    protected function logGeneration(
        string $promptSlug,
        array $inputData,
        array $outputData,
        string $status = 'success',
        ?string $errorMessage = null,
        ?int $tokensUsed = null,
        ?int $durationMs = null,
        ?int $projectId = null
    ): void {
        try {
            $this->promptService->logGeneration(
                $promptSlug,
                $inputData,
                $outputData,
                $status,
                $errorMessage,
                $tokensUsed,
                $durationMs,
                $projectId
            );
        } catch (\Exception $e) {
            // Don't let logging failures break the main flow
            \Log::warning('VideoWizard: Failed to log generation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate script parameters based on duration and content depth.
     */
    protected function calculateScriptParameters(int $duration, string $contentDepth): array
    {
        // Adjust speaking rate based on content depth
        $wpm = match ($contentDepth) {
            'quick' => self::WPM_FAST,
            'standard' => self::WPM_NORMAL,
            'detailed', 'deep' => self::WPM_SLOW,
            default => self::WPM_NORMAL,
        };

        // Calculate total words needed
        $targetWords = (int) ceil($duration / 60 * $wpm);

        // Calculate optimal scene count (10-20 seconds per scene)
        // With pause factor of 1.1 for natural breaks
        $avgSceneDuration = match ($contentDepth) {
            'quick' => 12,  // Faster pacing
            'standard' => 15,
            'detailed' => 18,
            'deep' => 20,   // More time per concept
            default => 15,
        };

        $sceneCount = max(3, (int) ceil($duration / $avgSceneDuration));

        // Words per scene
        $wordsPerScene = (int) ceil($targetWords / $sceneCount);

        \Log::info('VideoWizard: calculateScriptParameters', [
            'input_duration' => $duration,
            'input_contentDepth' => $contentDepth,
            'calculated_wpm' => $wpm,
            'calculated_targetWords' => $targetWords,
            'calculated_avgSceneDuration' => $avgSceneDuration,
            'calculated_sceneCount' => $sceneCount,
            'calculated_wordsPerScene' => $wordsPerScene,
            'duration_formula' => "max(3, ceil({$duration} / {$avgSceneDuration})) = max(3, " . ceil($duration / $avgSceneDuration) . ") = {$sceneCount}",
        ]);

        return [
            'wpm' => $wpm,
            'targetWords' => $targetWords,
            'sceneCount' => $sceneCount,
            'avgSceneDuration' => $avgSceneDuration,
            'wordsPerScene' => $wordsPerScene,
        ];
    }

    /**
     * Generate long-form script (over 5 minutes) using multi-pass approach.
     */
    protected function generateLongFormScript(
        string $topic,
        string $tone,
        int $duration,
        array $params,
        array $concept,
        ?array $productionType,
        string $additionalInstructions,
        int $teamId,
        string $aiModelTier = 'economy'
    ): array {
        \Log::info('VideoWizard: Using multi-pass generation for long-form video', [
            'duration' => $duration,
            'minutes' => round($duration / 60, 1),
            'aiModelTier' => $aiModelTier,
        ]);

        // Step 1: Generate outline/structure
        $outlinePrompt = $this->buildOutlinePrompt($topic, $tone, $duration, $params, $concept, $additionalInstructions);
        $outlineResult = $this->callAIWithTier($outlinePrompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 8000, // Ensure enough tokens for outline JSON
        ]);

        if (!empty($outlineResult['error'])) {
            throw new \Exception($outlineResult['error']);
        }

        $outline = $this->parseOutlineResponse($outlineResult['data'][0] ?? '');

        \Log::info('VideoWizard: Outline generated', [
            'sectionCount' => count($outline['sections'] ?? []),
        ]);

        // Step 2: Generate detailed scenes for each section
        $allScenes = [];
        $sections = $outline['sections'] ?? [];

        foreach ($sections as $sectionIndex => $section) {
            $sectionScenes = $this->generateSectionScenes(
                $section,
                $sectionIndex,
                $topic,
                $tone,
                $concept,
                $teamId,
                $aiModelTier
            );
            $allScenes = array_merge($allScenes, $sectionScenes);
        }

        // Re-index scene IDs
        foreach ($allScenes as $index => &$scene) {
            $scene['id'] = 'scene-' . ($index + 1);
        }

        return [
            'title' => $outline['title'] ?? 'Generated Script',
            'hook' => $outline['hook'] ?? ($allScenes[0]['narration'] ?? ''),
            'scenes' => $allScenes,
            'cta' => $outline['cta'] ?? 'Subscribe for more content!',
            'totalDuration' => array_sum(array_column($allScenes, 'duration')),
            'wordCount' => array_sum(array_map(fn($s) => str_word_count($s['narration'] ?? ''), $allScenes)),
        ];
    }

    // =========================================================================
    // PROGRESSIVE BATCH-BASED SCENE GENERATION
    // =========================================================================

    /**
     * Generate a batch of scenes with context continuity.
     *
     * @param WizardProject $project The project
     * @param int $startScene Starting scene number (1-indexed)
     * @param int $endScene Ending scene number (1-indexed)
     * @param int $totalScenes Total scenes in the full script
     * @param string $context Context from previous scenes
     * @param array $options Generation options
     * @return array ['success' => bool, 'scenes' => array, 'error' => string|null]
     */
    public function generateSceneBatch(
        WizardProject $project,
        int $startScene,
        int $endScene,
        int $totalScenes,
        string $context,
        array $options = []
    ): array {
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $topic = $options['topic'] ?? $project->concept['refinedConcept'] ?? $project->concept['rawInput'] ?? '';
        $sceneCount = $endScene - $startScene + 1;
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        \Log::info('VideoWizard: Generating scene batch', [
            'startScene' => $startScene,
            'endScene' => $endScene,
            'totalScenes' => $totalScenes,
            'sceneCount' => $sceneCount,
            'aiModelTier' => $aiModelTier,
        ]);

        // Build the batch prompt
        $prompt = $this->buildBatchPrompt(
            $topic,
            $startScene,
            $endScene,
            $totalScenes,
            $context,
            $options
        );

        // Call AI with tier-based model selection
        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 10000, // Ensure enough tokens for batch scenes
        ]);

        if (!empty($result['error'])) {
            return ['success' => false, 'error' => $result['error'], 'scenes' => []];
        }

        $response = $result['data'][0] ?? '';

        try {
            $parsed = $this->parseBatchResponse($response, $startScene, $endScene);

            if (empty($parsed['scenes'])) {
                return ['success' => false, 'error' => 'No scenes parsed from response', 'scenes' => []];
            }

            \Log::info('VideoWizard: Batch generated successfully', [
                'scenesGenerated' => count($parsed['scenes']),
            ]);

            return ['success' => true, 'scenes' => $parsed['scenes'], 'error' => null];

        } catch (\Exception $e) {
            \Log::error('VideoWizard: Batch parsing failed', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500),
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'scenes' => []];
        }
    }

    /**
     * Build context string from existing scenes for batch continuity.
     *
     * @param array $existingScenes Scenes generated so far
     * @param int $batchNumber Current batch number (1-indexed)
     * @param int $totalBatches Total number of batches
     * @return string Context for AI prompt
     */
    public function buildBatchContext(array $existingScenes, int $batchNumber, int $totalBatches): string
    {
        $context = "";

        if (empty($existingScenes)) {
            $context .= "=== NARRATIVE POSITION: OPENING ===\n";
            $context .= "This is the BEGINNING of the video.\n";
            $context .= "- Create a strong hook in the first scene to grab attention\n";
            $context .= "- Introduce the main topic/premise clearly\n";
            $context .= "- Set the tone and style for the entire video\n";
            $context .= "- Make the viewer want to continue watching\n";
            return $context;
        }

        // Summary of narrative so far
        $context .= "=== STORY SO FAR ===\n";
        $context .= "Previously generated " . count($existingScenes) . " scenes.\n\n";

        // Last 3 scenes for direct continuity
        $context .= "RECENT SCENES (maintain continuity with these):\n";
        $recentScenes = array_slice($existingScenes, -3);
        foreach ($recentScenes as $scene) {
            $title = $scene['title'] ?? 'Untitled';
            $narration = $scene['narration'] ?? '';
            $context .= "• Scene \"{$title}\": " . substr($narration, 0, 120) . "...\n";
        }
        $context .= "\n";

        // Narrative position guidance based on where we are in the story
        $position = $batchNumber / $totalBatches;
        $context .= "=== NARRATIVE POSITION ===\n";

        if ($position <= 0.25) {
            $context .= "You are in the SETUP phase (first quarter of the video).\n";
            $context .= "- Continue building the foundation established in scene 1\n";
            $context .= "- Introduce key concepts, characters, or ideas\n";
            $context .= "- Maintain viewer engagement with interesting content\n";
            $context .= "- Set up what's coming next\n";
        } elseif ($position <= 0.5) {
            $context .= "You are in the DEVELOPMENT phase (second quarter).\n";
            $context .= "- Deepen the narrative with more detail\n";
            $context .= "- Add complexity and nuance to the topic\n";
            $context .= "- Build momentum towards the midpoint\n";
            $context .= "- Keep the viewer invested in the content\n";
        } elseif ($position <= 0.75) {
            $context .= "You are in the ESCALATION phase (third quarter).\n";
            $context .= "- Increase the intensity or importance of content\n";
            $context .= "- Present key revelations or turning points\n";
            $context .= "- Build towards the climax/main point\n";
            $context .= "- Create anticipation for the conclusion\n";
        } else {
            $context .= "You are in the RESOLUTION phase (final quarter).\n";
            $context .= "- Bring the narrative to a satisfying conclusion\n";
            $context .= "- Deliver the main message or takeaway\n";
            $context .= "- Summarize key points if appropriate\n";
            $context .= "- Include a strong call-to-action in the FINAL scene\n";
        }

        return $context;
    }

    /**
     * Build the prompt for generating a batch of scenes.
     */
    protected function buildBatchPrompt(
        string $topic,
        int $startScene,
        int $endScene,
        int $totalScenes,
        string $context,
        array $options
    ): string {
        $sceneCount = $endScene - $startScene + 1;
        $tone = $options['tone'] ?? 'engaging';
        $contentDepth = $options['contentDepth'] ?? 'detailed';
        $productionType = $options['productionType'] ?? 'standard';

        // Calculate scene duration based on production type
        $sceneDurations = [
            'tiktok-viral' => 3,
            'youtube-short' => 5,
            'short-form' => 4,
            'standard' => 6,
            'cinematic' => 8,
            'documentary' => 10,
            'long-form' => 8,
        ];
        $sceneDuration = $sceneDurations[$productionType] ?? 6;

        // Words per scene (roughly 2.5 words per second for narration)
        $wordsPerScene = (int) round($sceneDuration * 2.5);

        $prompt = <<<PROMPT
You are an expert video scriptwriter. Generate scenes {$startScene} to {$endScene} of a {$totalScenes}-scene video.

TOPIC: {$topic}

TONE: {$tone}
CONTENT DEPTH: {$contentDepth}

{$context}

=== REQUIREMENTS ===
- Generate EXACTLY {$sceneCount} scenes (scenes {$startScene} through {$endScene})
- Each scene duration: approximately {$sceneDuration} seconds
- Each scene narration: approximately {$wordsPerScene} words
- Maintain narrative continuity with any previous scenes
- Each scene MUST have unique, meaningful content
- NO generic transitions or filler content
- NO placeholder text like "transition" or "more to come"
- Every scene must advance the narrative

=== VOICEOVER SPEECH TYPES (CRITICAL FOR LIP-SYNC) ===
speechType determines whether character's lips move on screen:
- "narrator": External narrator describes scene (NO lip movement)
- "internal": Character's inner thoughts heard as voiceover (NO lip movement)
- "monologue": Character speaks OUT LOUD to self (lips MOVE - set speakingCharacter)
- "dialogue": Characters talk to each other (lips MOVE - set speakingCharacter)

DEFAULT: Use "narrator" unless the script clearly shows a character speaking aloud.

=== SPEECH SEGMENT MARKERS (USE IN NARRATION FIELD) ===
Each scene's narration can mix narrator text with character dialogue using these markers:
[NARRATOR] Text here         → External narrator voiceover (no lip-sync)
[INTERNAL: CHARACTER] Text   → Character's inner thoughts (no lip-sync)
[MONOLOGUE: CHARACTER] Text  → Character speaking alone/to camera (lip-sync)
CHARACTER_NAME: Text         → Character dialogue (lip-sync required)

EXAMPLE of mixed narration:
[NARRATOR] The interrogation room falls silent.
DETECTIVE: Where were you last night?
SUSPECT: I was at home. Alone.
[NARRATOR] The detective slides a photograph across the table.

⚠️ CRITICAL: If a scene involves 2+ characters interacting (conversation, confrontation, argument),
the narration MUST include CHARACTER_NAME: dialogue lines. Do NOT reduce dialogue to narrator prose.

=== JSON FORMAT ===
Respond with ONLY valid JSON (no markdown, no explanation):
{
  "scenes": [
    {
      "id": "scene-{$startScene}",
      "title": "Descriptive scene title (2-5 words)",
      "narration": "Mixed narration using [NARRATOR] and CHARACTER: markers (~{$wordsPerScene} words)",
      "visualDescription": "Detailed visual description for AI image generation (50-100 words)",
      "mood": "Scene emotional tone (one word: inspiring, dramatic, peaceful, exciting, etc.)",
      "transition": "cut",
      "voiceover": {
        "speechType": "dialogue or narrator (use dialogue if scene has character speech)",
        "speakingCharacter": "Name of primary speaking character, or null for narrator"
      }
    }
  ]
}

Generate exactly {$sceneCount} scenes starting from scene-{$startScene} to scene-{$endScene}.
PROMPT;

        return $prompt;
    }

    /**
     * Parse batch generation response.
     */
    protected function parseBatchResponse(string $response, int $startScene, int $endScene): array
    {
        $expectedCount = $endScene - $startScene + 1;

        // Extract JSON from response
        $json = $this->extractJson($response);

        // Parse JSON
        $data = json_decode($json, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE || !isset($data['scenes'])) {
            // Try recovery methods
            $data = $this->tryAggressiveJsonRepair($json);

            if ($data === null || !isset($data['scenes'])) {
                $data = $this->rebuildScriptFromResponse($response);
            }

            if ($data === null || empty($data['scenes'])) {
                throw new \Exception('Failed to parse batch response: ' . json_last_error_msg());
            }
        }

        $scenes = [];
        foreach ($data['scenes'] as $index => $scene) {
            $sceneNumber = $startScene + $index;
            $scenes[] = $this->sanitizeScene($scene, $sceneNumber - 1);
            $scenes[count($scenes) - 1]['id'] = 'scene-' . $sceneNumber;
        }

        // Validate we got the expected number of scenes
        if (count($scenes) < $expectedCount) {
            \Log::warning('VideoWizard: Batch returned fewer scenes than expected', [
                'expected' => $expectedCount,
                'received' => count($scenes),
            ]);
        }

        return ['scenes' => $scenes];
    }

    /**
     * Build outline prompt for long-form videos.
     */
    protected function buildOutlinePrompt(
        string $topic,
        string $tone,
        int $duration,
        array $params,
        array $concept,
        string $additionalInstructions
    ): string {
        $minutes = round($duration / 60, 1);
        $sectionCount = max(3, min(8, (int) ceil($duration / 120))); // ~2 min per section

        $prompt = <<<PROMPT
You are a professional video content strategist. Create a detailed outline for a {$minutes}-minute video.

TOPIC: {$topic}
TONE: {$tone}
TOTAL DURATION: {$duration} seconds ({$minutes} minutes)
SECTIONS NEEDED: {$sectionCount}

PROMPT;

        if (!empty($additionalInstructions)) {
            $prompt .= "\nADDITIONAL REQUIREMENTS: {$additionalInstructions}\n";
        }

        $prompt .= <<<PROMPT

Create an outline that divides this video into {$sectionCount} main sections. Each section should:
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
PROMPT;

        return $prompt;
    }

    /**
     * Parse outline response.
     */
    protected function parseOutlineResponse(string $response): array
    {
        $json = $this->extractJson($response);
        $outline = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($outline['sections'])) {
            \Log::warning('VideoWizard: Outline parsing failed', [
                'response' => substr($response, 0, 500),
            ]);

            // Create default outline
            return [
                'title' => 'Video Script',
                'hook' => 'Welcome to this video!',
                'sections' => [
                    ['title' => 'Introduction', 'focus' => 'Opening', 'duration' => 60, 'keyPoints' => []],
                    ['title' => 'Main Content', 'focus' => 'Core topic', 'duration' => 120, 'keyPoints' => []],
                    ['title' => 'Conclusion', 'focus' => 'Wrap up', 'duration' => 60, 'keyPoints' => []],
                ],
                'cta' => 'Thanks for watching!',
            ];
        }

        return $outline;
    }

    /**
     * Generate scenes for a specific section.
     */
    protected function generateSectionScenes(
        array $section,
        int $sectionIndex,
        string $topic,
        string $tone,
        array $concept,
        int $teamId,
        string $aiModelTier = 'economy'
    ): array {
        $sectionDuration = $section['duration'] ?? 60;
        $sceneCount = max(2, (int) ceil($sectionDuration / 15));
        $avgSceneDuration = (int) ceil($sectionDuration / $sceneCount);

        $keyPointsText = !empty($section['keyPoints'])
            ? implode("\n- ", $section['keyPoints'])
            : '';

        $prompt = <<<PROMPT
You are an expert video script writer. Create {$sceneCount} detailed scenes for a video section.

OVERALL TOPIC: {$topic}
SECTION: {$section['title']}
SECTION FOCUS: {$section['focus']}
SECTION DURATION: {$sectionDuration} seconds
SCENES NEEDED: {$sceneCount}
AVERAGE SCENE DURATION: {$avgSceneDuration} seconds
TONE: {$tone}

KEY POINTS TO COVER:
- {$keyPointsText}

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title (descriptive)",
      "narration": "Exactly what the narrator says (25-50 words)",
      "visualDescription": "Detailed visual description for AI image generation (describe setting, mood, colors, composition)",
      "duration": {$avgSceneDuration}
    }
  ]
}

REQUIREMENTS:
- Each scene narration should be 25-50 words
- Visual descriptions must be detailed and specific for image generation
- Include mood, lighting, colors, and composition details in visuals
- Narration should flow naturally from scene to scene
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 8000, // Ensure enough tokens for section scenes
        ]);

        if (!empty($result['error'])) {
            \Log::warning('VideoWizard: Section generation failed', [
                'section' => $section['title'],
                'error' => $result['error'],
            ]);
            // Return fallback scene
            return [[
                'id' => 'scene-' . ($sectionIndex + 1),
                'title' => $section['title'],
                'narration' => $section['focus'] ?? 'Content for this section.',
                'visualDescription' => 'Visual representation of ' . ($section['title'] ?? 'this section'),
                'duration' => $sectionDuration,
            ]];
        }

        $response = $result['data'][0] ?? '';
        $json = $this->extractJson($response);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['scenes'])) {
            \Log::warning('VideoWizard: Section scene parsing failed', [
                'response' => substr($response, 0, 300),
            ]);
            return [[
                'id' => 'scene-' . ($sectionIndex + 1),
                'title' => $section['title'],
                'narration' => $section['focus'] ?? 'Content for this section.',
                'visualDescription' => 'Visual representation of ' . ($section['title'] ?? 'this section'),
                'duration' => $sectionDuration,
            ]];
        }

        // Add Ken Burns effect to each scene
        foreach ($data['scenes'] as &$scene) {
            $scene['kenBurns'] = $this->generateKenBurnsEffect();
        }

        return $data['scenes'];
    }

    /**
     * Build the script generation prompt for standard videos.
     * Uses multi-layer prompt assembly for Hollywood-level script generation.
     */
    protected function buildScriptPrompt(array $params): string
    {
        $topic = $params['topic'];
        $tone = $params['tone'];
        $duration = $params['duration'];
        $targetWords = $params['targetWords'];
        $sceneCount = $params['sceneCount'];
        $wordsPerScene = $params['wordsPerScene'];
        $contentDepth = $params['contentDepth'] ?? 'detailed';
        $additionalInstructions = $params['additionalInstructions'] ?? '';

        // Narrative Structure Intelligence
        $narrativePreset = $params['narrativePreset'] ?? null;
        $storyArc = $params['storyArc'] ?? null;
        $tensionCurve = $params['tensionCurve'] ?? null;
        $emotionalJourney = $params['emotionalJourney'] ?? null;

        // Story Bible constraint (Phase 1: Bible-First Architecture)
        $storyBibleConstraint = $params['storyBibleConstraint'] ?? '';

        // Dynamic cast and location requirements
        $suggestedCharacterCount = $params['suggestedCharacterCount'] ?? null;
        $suggestedLocationCount = $params['suggestedLocationCount'] ?? null;
        $productionSubtype = $params['productionSubtype'] ?? null;

        $minutes = round($duration / 60, 1);
        $avgSceneDuration = (int) ceil($duration / $sceneCount);

        $toneGuide = $this->promptService->getToneGuide($tone);
        $depthGuide = $this->promptService->getDepthGuide($contentDepth);

        // Build multi-layer prompt
        $prompt = $this->buildMultiLayerPrompt([
            'topic' => $topic,
            'tone' => $tone,
            'toneGuide' => $toneGuide,
            'contentDepth' => $contentDepth,
            'depthGuide' => $depthGuide,
            'duration' => $duration,
            'minutes' => $minutes,
            'targetWords' => $targetWords,
            'sceneCount' => $sceneCount,
            'wordsPerScene' => $wordsPerScene,
            'avgSceneDuration' => $avgSceneDuration,
            'additionalInstructions' => $additionalInstructions,
            'narrativePreset' => $narrativePreset,
            'storyArc' => $storyArc,
            'tensionCurve' => $tensionCurve,
            'emotionalJourney' => $emotionalJourney,
            // Story Bible constraint (Phase 1: Bible-First Architecture)
            'storyBibleConstraint' => $storyBibleConstraint,
            // Dynamic cast and location requirements
            'suggestedCharacterCount' => $suggestedCharacterCount,
            'suggestedLocationCount' => $suggestedLocationCount,
            'productionSubtype' => $productionSubtype,
        ]);

        return $prompt;
    }

    /**
     * Build multi-layer prompt for Hollywood-level script generation.
     * Layers: Foundation → Narrative → Content → Stylistic → Technical → Output
     */
    protected function buildMultiLayerPrompt(array $params): string
    {
        $topic = $params['topic'];
        $tone = $params['tone'];
        $toneGuide = $params['toneGuide'];
        $duration = $params['duration'];
        $minutes = $params['minutes'];
        $targetWords = $params['targetWords'];
        $sceneCount = $params['sceneCount'];
        $wordsPerScene = $params['wordsPerScene'];
        $avgSceneDuration = $params['avgSceneDuration'];
        $contentDepth = $params['contentDepth'];
        $depthGuide = $params['depthGuide'];
        $additionalInstructions = $params['additionalInstructions'];

        // Get narrative structure configs
        $narrativePreset = $params['narrativePreset'];
        $storyArc = $params['storyArc'];
        $tensionCurve = $params['tensionCurve'];
        $emotionalJourney = $params['emotionalJourney'];

        // Story Bible constraint (Phase 1: Bible-First Architecture)
        $storyBibleConstraint = $params['storyBibleConstraint'] ?? '';

        // Dynamic cast and location requirements
        $suggestedCharacterCount = $params['suggestedCharacterCount'] ?? null;
        $suggestedLocationCount = $params['suggestedLocationCount'] ?? null;
        $productionSubtype = $params['productionSubtype'] ?? null;

        // Load configs
        $narrativePresets = config('appvideowizard.narrative_presets', []);
        $storyArcs = config('appvideowizard.story_arcs', []);
        $tensionCurves = config('appvideowizard.tension_curves', []);
        $emotionalJourneys = config('appvideowizard.emotional_journeys', []);

        // Get preset config and apply defaults if not explicitly set
        $presetConfig = $narrativePresets[$narrativePreset] ?? null;
        if ($presetConfig) {
            $storyArc = $storyArc ?? ($presetConfig['defaultArc'] ?? null);
            $tensionCurve = $tensionCurve ?? ($presetConfig['defaultTension'] ?? null);
            $emotionalJourney = $emotionalJourney ?? ($presetConfig['defaultEmotion'] ?? null);
        }

        // Get specific configs
        $arcConfig = $storyArcs[$storyArc] ?? null;
        $curveConfig = $tensionCurves[$tensionCurve] ?? null;
        $journeyConfig = $emotionalJourneys[$emotionalJourney] ?? null;

        // === LAYER 1: FOUNDATION (Role & Expertise) ===
        $prompt = "You are a Hollywood-level video scriptwriter with expertise in narrative structure, emotional storytelling, and viewer engagement optimization.\n\n";

        // === LAYER 2: NARRATIVE STRUCTURE ===
        if ($presetConfig || $arcConfig || $curveConfig || $journeyConfig) {
            $prompt .= "=== NARRATIVE STRUCTURE INTELLIGENCE ===\n";

            if ($presetConfig) {
                $prompt .= "STORYTELLING FORMULA: {$presetConfig['name']}\n";
                $prompt .= "Formula Guide: {$presetConfig['description']}\n";
                if (!empty($presetConfig['tips'])) {
                    $prompt .= "Pro Tips: {$presetConfig['tips']}\n";
                }
                $prompt .= "\n";
            }

            if ($arcConfig) {
                $prompt .= "STORY ARC: {$arcConfig['name']}\n";
                $prompt .= "Arc Description: {$arcConfig['description']}\n";
                if (!empty($arcConfig['beats'])) {
                    $beats = implode(' → ', array_map(fn($b) => ucwords(str_replace('_', ' ', $b)), $arcConfig['beats']));
                    $prompt .= "Story Beats: {$beats}\n";
                }
                if (!empty($arcConfig['structure'])) {
                    $structureParts = [];
                    foreach ($arcConfig['structure'] as $part => $percentage) {
                        $structureParts[] = ucfirst($part) . " ({$percentage}%)";
                    }
                    $prompt .= "Time Distribution: " . implode(' | ', $structureParts) . "\n";
                }
                $prompt .= "\n";
            }

            if ($curveConfig) {
                $prompt .= "TENSION CURVE: {$curveConfig['name']}\n";
                $prompt .= "Pacing Style: {$curveConfig['description']}\n";
                if (!empty($curveConfig['curve'])) {
                    $curveVisual = implode('→', array_map(fn($v) => $v . '%', $curveConfig['curve']));
                    $prompt .= "Tension Levels (10-point scale): {$curveVisual}\n";
                }
                $prompt .= "\n";
            }

            if ($journeyConfig) {
                $prompt .= "EMOTIONAL JOURNEY: {$journeyConfig['name']}\n";
                $prompt .= "Viewer Journey: {$journeyConfig['description']}\n";
                if (!empty($journeyConfig['emotionArc'])) {
                    $emotionArc = implode(' → ', array_map(fn($e) => ucfirst($e), $journeyConfig['emotionArc']));
                    $prompt .= "Emotional Beats: {$emotionArc}\n";
                }
                if (!empty($journeyConfig['endFeeling'])) {
                    $prompt .= "Target End Feeling: {$journeyConfig['endFeeling']}\n";
                }
                $prompt .= "\n";
            }
        }

        // === STORY BIBLE CONSTRAINT (Phase 1: Bible-First Architecture) ===
        // If a Story Bible exists, inject it as a primary constraint
        // This ensures characters, locations, and style are consistent with the Bible
        if (!empty($storyBibleConstraint)) {
            $prompt .= $storyBibleConstraint . "\n";
        }

        // === LAYER 3: CONTENT CONTEXT ===
        $prompt .= "=== CONTENT PARAMETERS ===\n";
        $prompt .= "TOPIC: {$topic}\n";
        $prompt .= "TONE: {$tone} - {$toneGuide}\n";
        $prompt .= "CONTENT DEPTH: {$contentDepth} - {$depthGuide}\n";
        $prompt .= "TOTAL DURATION: {$duration} seconds ({$minutes} minutes)\n";
        $prompt .= "TARGET WORD COUNT: {$targetWords} words\n";
        $prompt .= "NUMBER OF SCENES: {$sceneCount}\n";
        $prompt .= "WORDS PER SCENE: ~{$wordsPerScene}\n";
        $prompt .= "SCENE DURATION: ~{$avgSceneDuration} seconds each\n\n";

        if (!empty($additionalInstructions)) {
            $prompt .= "ADDITIONAL REQUIREMENTS: {$additionalInstructions}\n\n";
        }

        // === CAST & LOCATIONS REQUIREMENTS (Dynamic based on duration) ===
        if ($suggestedCharacterCount || $suggestedLocationCount) {
            $prompt .= "=== CAST & LOCATIONS REQUIREMENTS ===\n";
            $prompt .= "⚠️ MANDATORY CHARACTER/LOCATION REQUIREMENT for a {$minutes}-minute video:\n\n";

            if ($suggestedCharacterCount) {
                $prompt .= "MINIMUM CHARACTERS: {$suggestedCharacterCount} (THIS IS MANDATORY)\n";
                $prompt .= "You MUST include AT LEAST {$suggestedCharacterCount} distinct NAMED characters in your script.\n";
                $prompt .= "CRITICAL REQUIREMENT FOR VISUAL DESCRIPTIONS:\n";
                $prompt .= "- Each character MUST be explicitly NAMED BY NAME in the visualDescription fields\n";
                $prompt .= "- Format: 'Character Name, a [description]' (e.g., 'Marcus Chen, a weathered detective in his 50s')\n";
                $prompt .= "- EVERY scene should reference at least one character BY NAME in its visualDescription\n";
                $prompt .= "- Characters must appear with CONSISTENT names across all scenes (same spelling)\n\n";
                $prompt .= "CHARACTER VARIETY REQUIRED:\n";
                $prompt .= "- Protagonist(s): The main character(s) driving the story\n";
                $prompt .= "- Antagonist/Obstacle: Opposition or conflict source\n";
                $prompt .= "- Supporting cast: Allies, mentors, friends who help the protagonist\n";
                $prompt .= "- Minor characters: Background characters who add depth\n";
                if ($productionSubtype === 'thriller' || $productionSubtype === 'mystery') {
                    $prompt .= "- THRILLER CAST: Include suspects, witnesses, authority figures, victims, and mysterious strangers\n";
                } elseif ($productionSubtype === 'action' || $productionSubtype === 'adventure') {
                    $prompt .= "- ACTION CAST: Include heroes, villains, allies, informants, and bystanders\n";
                } elseif ($productionSubtype === 'drama') {
                    $prompt .= "- DRAMA CAST: Include family members, friends, rivals, mentors, and confidants\n";
                }
                $prompt .= "\n";
            }

            if ($suggestedLocationCount) {
                $prompt .= "MINIMUM LOCATIONS: {$suggestedLocationCount} (THIS IS MANDATORY)\n";
                $prompt .= "You MUST use AT LEAST {$suggestedLocationCount} different settings/environments.\n";
                $prompt .= "- EVERY visualDescription should clearly identify the location/setting\n";
                $prompt .= "- Vary locations: indoor/outdoor, day/night, public/private spaces\n";
                $prompt .= "- Each location should serve the story's progression\n";
                $prompt .= "- Locations should feel distinct and visually interesting\n";
                if ($productionSubtype === 'thriller' || $productionSubtype === 'mystery') {
                    $prompt .= "- THRILLER LOCATIONS: crime scenes, interrogation rooms, dark alleys, offices, hideouts, surveillance spots\n";
                } elseif ($productionSubtype === 'action' || $productionSubtype === 'adventure') {
                    $prompt .= "- ACTION LOCATIONS: chase routes, confrontation sites, bases, urban and natural environments\n";
                }
                $prompt .= "\n";
            }

            $prompt .= "⛔ FAILURE CONDITION: Creating only 2-3 characters for a {$minutes}-minute video is UNACCEPTABLE.\n";
            $prompt .= "⛔ A professional production requires AT LEAST {$suggestedCharacterCount} named characters distributed across scenes.\n";
            $prompt .= "✅ SUCCESS: Name EACH character explicitly in visualDescriptions so they can be extracted.\n\n";
        }

        // === LAYER 4: STRUCTURE GUIDANCE ===
        $prompt .= "=== SCRIPT STRUCTURE ===\n";

        // Build structure based on story arc or defaults
        if ($arcConfig && !empty($arcConfig['structure'])) {
            $prompt .= "Follow the {$arcConfig['name']} structure:\n";
            $scenesAssigned = 0;
            $structureIndex = 1;
            foreach ($arcConfig['structure'] as $part => $percentage) {
                $partScenes = max(1, round($sceneCount * ($percentage / 100)));
                if ($scenesAssigned + $partScenes > $sceneCount) {
                    $partScenes = $sceneCount - $scenesAssigned;
                }
                $partDuration = round($duration * ($percentage / 100));
                $prompt .= "{$structureIndex}. " . strtoupper($part) . " (~{$percentage}% = {$partDuration}s, {$partScenes} scenes)\n";
                $scenesAssigned += $partScenes;
                $structureIndex++;
            }
        } else {
            // Default structure
            $prompt .= "1. HOOK (first 5 seconds) - Grab attention immediately\n";
            $prompt .= "2. INTRODUCTION (10-15 seconds) - Set expectations\n";
            $prompt .= "3. MAIN CONTENT ({$sceneCount} scenes) - Deliver value with retention hooks every 30-45 seconds\n";
            $prompt .= "4. CALL TO ACTION (5-10 seconds) - Clear next step for viewer\n";
        }

        $prompt .= "\n";

        // === LAYER 5: ENGAGEMENT TECHNIQUES ===
        $prompt .= "=== ENGAGEMENT TECHNIQUES ===\n";

        if ($presetConfig) {
            // Platform-specific engagement
            if ($narrativePreset === 'youtube-standard' || $narrativePreset === 'youtube-retention') {
                $prompt .= "- Insert pattern breaks every 45-60 seconds (\"But here's where it gets interesting...\")\n";
                $prompt .= "- Use open loops to maintain curiosity (\"I'll reveal the secret in a moment...\")\n";
                $prompt .= "- Include micro-CTAs throughout (\"Stay with me...\")\n";
            } elseif ($narrativePreset === 'tiktok-viral') {
                $prompt .= "- First 1-2 words must stop the scroll\n";
                $prompt .= "- Build tension rapidly to payoff at 80% mark\n";
                $prompt .= "- End with loop-worthy moment that makes viewers rewatch\n";
            } elseif ($narrativePreset === 'cinematic-short') {
                $prompt .= "- Use visual storytelling over exposition\n";
                $prompt .= "- Let emotional moments breathe\n";
                $prompt .= "- Build character investment before conflict\n";
            } elseif ($narrativePreset === 'documentary-feature') {
                $prompt .= "- Ground claims in evidence and facts\n";
                $prompt .= "- Use revelations strategically for impact\n";
                $prompt .= "- Leave viewers with thought-provoking conclusions\n";
            } elseif ($narrativePreset === 'thriller-short' || $narrativePreset === 'horror-short') {
                $prompt .= "- Plant subtle clues that pay off later\n";
                $prompt .= "- Use silence and restraint for maximum impact\n";
                $prompt .= "- Build dread through atmosphere, not just events\n";
            }
        } else {
            // Default engagement techniques
            $prompt .= "- Use retention hooks every 30-45 seconds\n";
            $prompt .= "- Ask rhetorical questions to maintain engagement\n";
            $prompt .= "- Tease upcoming content to keep viewers watching\n";
        }

        $prompt .= "\n";

        // === LAYER 6: CINEMATOGRAPHY GUIDANCE ===
        $prompt .= "=== PROFESSIONAL CINEMATOGRAPHY ===\n";
        $prompt .= $this->buildCinematographyGuidance($narrativePreset, $emotionalJourney, $sceneCount);
        $prompt .= "\n";

        // === LAYER 7: SCENE BEAT SYSTEM ===
        $prompt .= "=== SCENE MICRO-STRUCTURE ===\n";
        $prompt .= $this->buildSceneBeatGuidance($sceneCount, $narrativePreset);
        $prompt .= "\n";

        // === LAYER 8: RETENTION HOOKS ===
        if ($duration >= 30) {
            $prompt .= $this->buildRetentionHookGuidance($duration, $narrativePreset);
            $prompt .= "\n";
        }

        // === LAYER 9: TRANSITION GUIDANCE ===
        $prompt .= $this->buildTransitionGuidance($narrativePreset, $emotionalJourney);
        $prompt .= "\n";

        // === LAYER 10: VISUAL STYLE (TIER 3) ===
        $visualStyle = $params['visualStyle'] ?? null;
        $genreTemplate = $params['genreTemplate'] ?? null;
        $prompt .= $this->buildVisualStyleGuidance($visualStyle, $genreTemplate);
        $prompt .= "\n";

        // === LAYER 11: MUSIC MOOD GUIDANCE (TIER 3) ===
        $prompt .= $this->buildMusicMoodGuidance($sceneCount, $emotionalJourney, $genreTemplate);
        $prompt .= "\n";

        // === LAYER 12: PACING OPTIMIZATION (TIER 3) ===
        $pacingProfile = $params['pacingProfile'] ?? null;
        $prompt .= $this->buildPacingGuidance($duration, $sceneCount, $pacingProfile, $genreTemplate);
        $prompt .= "\n";

        // === LAYER 13: GENRE TEMPLATE TIPS (TIER 3) ===
        if ($genreTemplate) {
            $prompt .= $this->buildGenreTemplateTips($genreTemplate);
            $prompt .= "\n";
        }

        // === LAYER 14: SPEECH SEGMENTS (Dynamic Multi-Voice) ===
        $prompt .= "=== SPEECH SEGMENTS (HOLLYWOOD-STYLE MIXED NARRATION) ===\n";
        $prompt .= "Each scene can mix NARRATOR, DIALOGUE, INTERNAL THOUGHTS, and MONOLOGUE.\n";
        $prompt .= "Use these segment markers in the narration field:\n\n";
        $prompt .= "SEGMENT FORMATS:\n";
        $prompt .= "[NARRATOR] Text here         → External narrator voiceover (no lip-sync)\n";
        $prompt .= "[INTERNAL: CHARACTER] Text   → Character's inner thoughts as V.O. (no lip-sync)\n";
        $prompt .= "[MONOLOGUE: CHARACTER] Text  → Character speaking alone/to camera (lip-sync)\n";
        $prompt .= "CHARACTER: Text              → Character dialogue (lip-sync required)\n\n";
        $prompt .= "EXAMPLE MIXED NARRATION:\n";
        $prompt .= "[NARRATOR] The city never sleeps. Neither does Jack.\n";
        $prompt .= "JACK: They're everywhere...\n";
        $prompt .= "[INTERNAL: JACK] I should never have come here.\n";
        $prompt .= "THUG: End of the line.\n\n";
        $prompt .= "RULES:\n";
        $prompt .= "- Use [NARRATOR] for scene-setting, exposition, describing action\n";
        $prompt .= "- Use CHARACTER: for spoken dialogue between characters\n";
        $prompt .= "- Use [INTERNAL: CHARACTER] for thoughts/reactions (heard but lips don't move)\n";
        $prompt .= "- Character names MUST match those in visualDescription\n";
        $prompt .= "- For simple narrator-only scenes, you can omit tags (defaults to narrator)\n\n";

        // === CRITICAL REINFORCEMENT: SEGMENT MARKERS ===
        $prompt .= "⚠️ CRITICAL - EVERY LINE of narration MUST start with a segment marker prefix.\n";
        $prompt .= "CORRECT FORMAT:\n";
        $prompt .= "[NARRATOR] The city was silent.\n";
        $prompt .= "JACK: I need to find her.\n";
        $prompt .= "[INTERNAL: JACK] Something wasn't right.\n\n";
        $prompt .= "❌ WRONG - DO NOT write plain prose without markers:\n";
        $prompt .= "The city was silent. Jack walked through the alley.\n";
        $prompt .= "He needed to find her before it was too late.\n\n";
        $prompt .= "If a scene has ANY character dialogue, you MUST use CHARACTER: prefix for their lines.\n";
        $prompt .= "If a scene has narration, you MUST use [NARRATOR] prefix for those lines.\n\n";

        // === LAYER 14B: DIALOGUE ENFORCEMENT FOR MULTI-CHARACTER SCENES ===
        $prompt .= "=== DIALOGUE ENFORCEMENT (CRITICAL FOR MULTI-CHARACTER SCENES) ===\n";
        $prompt .= "⚠️ MANDATORY RULE: If a scene has 2 or more characters PRESENT and INTERACTING:\n";
        $prompt .= "- The narration MUST include actual spoken dialogue using CHARACTER_NAME: format\n";
        $prompt .= "- At MINIMUM 40% of the scene's narration should be CHARACTER dialogue lines\n";
        $prompt .= "- Do NOT describe what characters say in third person (❌ 'Elena tells Victor his alibi fails')\n";
        $prompt .= "- Instead, WRITE their actual dialogue (✅ 'ELENA REYES: Your alibi doesn't hold up, Victor.')\n\n";
        $prompt .= "EXAMPLE - INTERROGATION SCENE WITH 2 CHARACTERS:\n";
        $prompt .= "❌ WRONG (all narrator prose):\n";
        $prompt .= "Elena confronts Victor about the murder weapon. Victor denies involvement. The tension rises.\n\n";
        $prompt .= "✅ CORRECT (mixed narrator + dialogue):\n";
        $prompt .= "[NARRATOR] Elena slides a photograph across the scarred metal table.\n";
        $prompt .= "ELENA REYES: Where were you on the night of June 12th?\n";
        $prompt .= "VICTOR KANE: I was home. Alone.\n";
        $prompt .= "[NARRATOR] Elena's eyes narrow as she detects the lie.\n";
        $prompt .= "ELENA REYES: That's interesting, because we have footage of you at the warehouse.\n\n";
        $prompt .= "RULE: Scenes with confrontation, conversation, argument, interrogation, negotiation, or any\n";
        $prompt .= "direct character interaction MUST use CHARACTER_NAME: dialogue lines. Never reduce dialogue to narrator description.\n\n";

        // === LAYER 15: OUTPUT FORMAT ===
        $prompt .= "=== OUTPUT FORMAT ===\n";
        $prompt .= "RESPOND WITH ONLY THIS JSON (no markdown code blocks, no explanation, just pure JSON):\n";
        $prompt .= <<<JSON
{
  "title": "SEO-optimized title (max 60 chars)",
  "hook": "Attention-grabbing opening line (5-10 words)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Descriptive scene title",
      "narration": "Mixed speech segments using [NARRATOR], CHARACTER:, [INTERNAL: CHAR] markers (~{$wordsPerScene} words)",
      "visualDescription": "Cinematic visual: [SHOT TYPE] [SUBJECT/ACTION]. [LIGHTING]. [COLOR MOOD]. [COMPOSITION]. [ATMOSPHERE]",
      "mood": "Scene emotional tone (matches emotional journey beat)",
      "musicMood": "Soundtrack mood (epic, emotional, tense, upbeat, ambient, corporate, dramatic, playful, horror, electronic)",
      "transition": "Scene transition type (cut, fade, dissolve, wipe, zoom, slide, morph, flash)",
      "duration": {$avgSceneDuration},
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
  "totalDuration": {$duration},
  "wordCount": {$targetWords}
}
JSON;

        $prompt .= "\n\n=== CRITICAL REQUIREMENTS - MUST FOLLOW ===\n";
        $prompt .= "⚠️ MANDATORY: You MUST generate EXACTLY {$sceneCount} scenes - not more, not less\n";
        $prompt .= "⚠️ The scenes array MUST contain exactly {$sceneCount} scene objects\n";
        $prompt .= "- Each scene narration MUST be approximately {$wordsPerScene} words\n";
        $prompt .= "- Total narration should equal approximately {$targetWords} words\n";
        $prompt .= "- Video duration is {$duration} seconds, so {$sceneCount} scenes at ~{$avgSceneDuration}s each\n";

        if ($curveConfig) {
            $prompt .= "- Match scene intensity to the {$curveConfig['name']} tension curve\n";
        }

        if ($journeyConfig) {
            $prompt .= "- Progress through the {$journeyConfig['name']} emotional journey\n";
        }

        if ($arcConfig) {
            $prompt .= "- Follow the {$arcConfig['name']} story structure strictly\n";
        }

        $prompt .= "- Visual descriptions must be detailed enough for AI image generation\n";
        $prompt .= "- Include varied Ken Burns movements (scale, position changes)\n";
        $prompt .= "- Scene durations should add up to approximately {$duration} seconds\n";
        $prompt .= "- NO markdown formatting - output raw JSON only\n";

        return $prompt;
    }

    /**
     * Build professional cinematography guidance based on narrative context.
     */
    protected function buildCinematographyGuidance(?string $narrativePreset, ?string $emotionalJourney, int $sceneCount): string
    {
        $shotTypes = config('appvideowizard.shot_types', []);
        $lightingStyles = config('appvideowizard.lighting_styles', []);
        $colorGrades = config('appvideowizard.color_grades', []);
        $compositions = config('appvideowizard.compositions', []);
        $cameraMovements = config('appvideowizard.camera_movements', []);

        $guidance = "Use professional cinematography language in visualDescription:\n\n";

        // Shot type progression based on scene position
        $guidance .= "SHOT TYPE PROGRESSION (vary throughout video):\n";
        $guidance .= "- Opening: Wide or Extreme Wide (establish context)\n";
        $guidance .= "- Development: Medium, Medium-Wide (content delivery)\n";
        $guidance .= "- Emotional moments: Close-Up, Medium Close-Up (connection)\n";
        $guidance .= "- Climax/Impact: Extreme Close-Up or dramatic Wide\n";
        $guidance .= "- Ending: Wide (resolution) or Close-Up (call to action)\n\n";

        // Available shot types
        $shotList = [];
        foreach (array_slice($shotTypes, 0, 6) as $shot) {
            $shotList[] = $shot['abbrev'] ?? $shot['name'];
        }
        $guidance .= "Available shots: " . implode(', ', $shotList) . "\n\n";

        // Lighting recommendations based on preset/emotion
        $guidance .= "LIGHTING STYLE (match emotional tone):\n";
        $suggestedLighting = $this->getSuggestedLighting($narrativePreset, $emotionalJourney);
        foreach ($suggestedLighting as $lighting) {
            if (isset($lightingStyles[$lighting])) {
                $guidance .= "- {$lightingStyles[$lighting]['name']}: {$lightingStyles[$lighting]['promptHint']}\n";
            }
        }
        $guidance .= "\n";

        // Color grading suggestions
        $guidance .= "COLOR GRADING (maintain consistency):\n";
        $suggestedColors = $this->getSuggestedColorGrade($narrativePreset, $emotionalJourney);
        foreach ($suggestedColors as $color) {
            if (isset($colorGrades[$color])) {
                $guidance .= "- {$colorGrades[$color]['name']}: {$colorGrades[$color]['promptHint']}\n";
            }
        }
        $guidance .= "\n";

        // Composition guidance
        $guidance .= "COMPOSITION TECHNIQUES:\n";
        $compList = ['rule-of-thirds', 'centered', 'leading-lines', 'depth-layering'];
        foreach ($compList as $comp) {
            if (isset($compositions[$comp])) {
                $guidance .= "- {$compositions[$comp]['name']}: {$compositions[$comp]['description']}\n";
            }
        }
        $guidance .= "\n";

        // Camera movement suggestions (for Ken Burns)
        $guidance .= "CAMERA MOVEMENT (reflected in kenBurns values):\n";
        $guidance .= "- Push In (build intensity): startScale=1.0, endScale=1.2\n";
        $guidance .= "- Pull Out (reveal): startScale=1.2, endScale=1.0\n";
        $guidance .= "- Pan Left/Right: vary startX/endX (0.3 to 0.7)\n";
        $guidance .= "- Tilt Up/Down: vary startY/endY (0.3 to 0.7)\n";
        $guidance .= "- Static (emphasis): startScale=endScale=1.0\n";

        return $guidance;
    }

    /**
     * Build scene beat guidance for micro-structure within scenes.
     */
    protected function buildSceneBeatGuidance(int $sceneCount, ?string $narrativePreset): string
    {
        $sceneBeats = config('appvideowizard.scene_beats', []);

        $guidance = "Each scene should follow internal beat structure:\n\n";

        foreach ($sceneBeats as $beat) {
            $guidance .= "- {$beat['name']} ({$beat['percentage']}%): {$beat['purpose']}\n";
        }

        $guidance .= "\nApply this rhythm to each scene's narration:\n";
        $guidance .= "1. SETUP (~first quarter): Orient viewer, establish what scene is about\n";
        $guidance .= "2. DEVELOPMENT (~middle half): Build content, deliver information/emotion\n";
        $guidance .= "3. PAYOFF (~final quarter): Key insight, memorable moment, bridge to next scene\n\n";

        // Platform-specific beat adjustments
        if ($narrativePreset === 'tiktok-viral') {
            $guidance .= "For TikTok: Compress setup dramatically (1-2 words), maximize payoff impact\n";
        } elseif ($narrativePreset === 'youtube-standard' || $narrativePreset === 'youtube-retention') {
            $guidance .= "For YouTube: Each scene payoff should tease the next scene's setup\n";
        } elseif ($narrativePreset === 'cinematic-short') {
            $guidance .= "For Cinematic: Let emotional beats breathe, don't rush development\n";
        }

        return $guidance;
    }

    /**
     * Build retention hook injection guidance.
     */
    protected function buildRetentionHookGuidance(int $duration, ?string $narrativePreset): string
    {
        $retentionHooks = config('appvideowizard.retention_hooks', []);

        $guidance = "=== RETENTION HOOKS ===\n";
        $guidance .= "Inject engagement hooks throughout to maintain viewer attention:\n\n";

        // Determine which hooks apply based on duration
        $applicableHooks = [];
        foreach ($retentionHooks as $hookId => $hook) {
            if (isset($hook['insertAfter']) && $hook['insertAfter'] <= $duration) {
                $applicableHooks[$hookId] = $hook;
            }
        }

        if (empty($applicableHooks)) {
            // For very short videos, still use question hooks
            $applicableHooks['question'] = $retentionHooks['question'] ?? [];
        }

        foreach ($applicableHooks as $hookId => $hook) {
            $guidance .= "AT ~{$hook['insertAfter']}s - {$hook['name']}:\n";
            if (!empty($hook['templates'])) {
                $examples = array_slice($hook['templates'], 0, 2);
                $guidance .= "  Examples: \"" . implode('\" or \"', $examples) . "\"\n";
            }
        }

        $guidance .= "\n";

        // Platform-specific retention strategies
        if ($narrativePreset === 'youtube-retention') {
            $guidance .= "YouTube Retention Strategy:\n";
            $guidance .= "- Use open loops: Promise information, deliver later\n";
            $guidance .= "- Pattern breaks every 45-60s to reset attention\n";
            $guidance .= "- Micro-CTAs: 'Stay with me...', 'Keep watching...'\n";
        } elseif ($narrativePreset === 'tiktok-viral') {
            $guidance .= "TikTok Retention Strategy:\n";
            $guidance .= "- First 0.5 seconds must stop scroll\n";
            $guidance .= "- Build anticipation to 80% mark payoff\n";
            $guidance .= "- End with loop-worthy moment\n";
        }

        return $guidance;
    }

    /**
     * Build transition guidance for scene connections.
     */
    protected function buildTransitionGuidance(?string $narrativePreset, ?string $emotionalJourney): string
    {
        $transitions = config('appvideowizard.transitions', []);

        $guidance = "=== SCENE TRANSITIONS ===\n";
        $guidance .= "Suggest appropriate transitions between scenes in the 'transition' field:\n\n";

        // Recommend transitions based on context
        $recommended = ['cut', 'fade', 'dissolve'];

        if ($narrativePreset === 'tiktok-viral') {
            $recommended = ['cut', 'zoom', 'flash'];
            $guidance .= "For TikTok: Use dynamic transitions (cut, zoom, flash)\n";
        } elseif ($narrativePreset === 'cinematic-short') {
            $recommended = ['dissolve', 'fade', 'cut'];
            $guidance .= "For Cinematic: Use elegant transitions (dissolve, fade)\n";
        } elseif ($narrativePreset === 'documentary-feature') {
            $recommended = ['cut', 'fade', 'dissolve'];
            $guidance .= "For Documentary: Use clean transitions (cut, fade)\n";
        } elseif ($emotionalJourney === 'horror' || $emotionalJourney === 'thriller') {
            $recommended = ['cut', 'flash', 'fade'];
            $guidance .= "For Suspense: Use sharp transitions, occasional flash for scares\n";
        }

        $guidance .= "Recommended: " . implode(', ', $recommended) . "\n";
        $guidance .= "Available: " . implode(', ', array_keys($transitions)) . "\n";

        return $guidance;
    }

    /**
     * Get suggested lighting styles based on narrative context.
     */
    protected function getSuggestedLighting(?string $preset, ?string $emotion): array
    {
        // Map presets/emotions to lighting styles
        $presetLighting = [
            'youtube-standard' => ['natural', 'high-key', 'studio'],
            'tiktok-viral' => ['neon', 'high-key', 'natural'],
            'cinematic-short' => ['golden-hour', 'low-key', 'rembrandt'],
            'documentary-feature' => ['natural', 'studio', 'golden-hour'],
            'thriller-short' => ['low-key', 'silhouette', 'blue-hour'],
            'horror-short' => ['low-key', 'silhouette', 'candlelight'],
            'inspirational' => ['golden-hour', 'high-key', 'natural'],
            'commercial-spot' => ['studio', 'high-key', 'natural'],
        ];

        $emotionLighting = [
            'triumph' => ['golden-hour', 'high-key'],
            'thriller' => ['low-key', 'blue-hour'],
            'horror' => ['low-key', 'silhouette'],
            'comedy' => ['high-key', 'natural'],
            'educational' => ['natural', 'studio'],
            'meditative' => ['golden-hour', 'natural'],
        ];

        if ($preset && isset($presetLighting[$preset])) {
            return $presetLighting[$preset];
        }

        if ($emotion && isset($emotionLighting[$emotion])) {
            return $emotionLighting[$emotion];
        }

        return ['natural', 'golden-hour', 'studio'];
    }

    /**
     * Get suggested color grades based on narrative context.
     */
    protected function getSuggestedColorGrade(?string $preset, ?string $emotion): array
    {
        $presetColors = [
            'youtube-standard' => ['vibrant', 'warm', 'neutral'],
            'tiktok-viral' => ['vibrant', 'high-contrast', 'neon'],
            'cinematic-short' => ['teal-orange', 'desaturated', 'warm'],
            'documentary-feature' => ['neutral', 'desaturated', 'cool'],
            'thriller-short' => ['desaturated', 'cool', 'high-contrast'],
            'horror-short' => ['desaturated', 'cool', 'monochrome'],
            'inspirational' => ['warm', 'golden-hour', 'vibrant'],
            'commercial-spot' => ['vibrant', 'warm', 'high-contrast'],
        ];

        $emotionColors = [
            'triumph' => ['warm', 'vibrant'],
            'thriller' => ['desaturated', 'cool'],
            'horror' => ['desaturated', 'monochrome'],
            'comedy' => ['vibrant', 'warm'],
            'educational' => ['neutral', 'cool'],
            'nostalgia' => ['vintage', 'warm'],
        ];

        if ($preset && isset($presetColors[$preset])) {
            return $presetColors[$preset];
        }

        if ($emotion && isset($emotionColors[$emotion])) {
            return $emotionColors[$emotion];
        }

        return ['neutral', 'warm', 'teal-orange'];
    }

    // ======================= TIER 3 METHODS =======================

    /**
     * Build visual style guidance for consistent image generation.
     */
    protected function buildVisualStyleGuidance(?string $visualStyle, ?string $genreTemplate): string
    {
        $visualStyles = config('appvideowizard.visual_styles', []);

        // Try to get style from genre template if not specified
        if (!$visualStyle && $genreTemplate) {
            $templates = config('appvideowizard.genre_templates', []);
            if (isset($templates[$genreTemplate]['defaults']['visualStyle'])) {
                $visualStyle = $templates[$genreTemplate]['defaults']['visualStyle'];
            }
        }

        $guidance = "=== VISUAL STYLE CONSISTENCY ===\n";

        if ($visualStyle && isset($visualStyles[$visualStyle])) {
            $style = $visualStyles[$visualStyle];
            $guidance .= "Selected Style: {$style['name']}\n\n";
            $guidance .= "For ALL image descriptions, incorporate these style elements:\n";
            $guidance .= "- Style Prefix: {$style['promptPrefix']}\n";
            $guidance .= "- Style Suffix: {$style['promptSuffix']}\n";
            $guidance .= "- Avoid: {$style['negativePrompt']}\n\n";
            $guidance .= "IMPORTANT: Maintain visual consistency across ALL scenes. Each visualDescription should:\n";
            $guidance .= "1. Start with style-appropriate framing keywords\n";
            $guidance .= "2. Include consistent lighting terminology\n";
            $guidance .= "3. Reference the same artistic style throughout\n";
        } else {
            $guidance .= "Default Style: Cinematic\n";
            $guidance .= "Maintain consistent visual style across all scenes.\n";
        }

        return $guidance;
    }

    /**
     * Build music mood guidance for soundtrack suggestions.
     */
    protected function buildMusicMoodGuidance(int $sceneCount, ?string $emotionalJourney, ?string $genreTemplate): string
    {
        $musicMoods = config('appvideowizard.music_moods', []);

        // Determine appropriate moods based on emotional journey
        $suggestedMoods = $this->getSuggestedMusicMoods($emotionalJourney, $genreTemplate);

        $guidance = "=== SOUNDTRACK GUIDANCE ===\n";
        $guidance .= "Include a 'musicMood' field for each scene to guide background music selection.\n\n";
        $guidance .= "Available moods: " . implode(', ', array_keys($musicMoods)) . "\n\n";

        $guidance .= "Recommended for this content:\n";
        foreach ($suggestedMoods as $idx => $moodId) {
            if (isset($musicMoods[$moodId])) {
                $mood = $musicMoods[$moodId];
                $guidance .= "- {$mood['name']}: {$mood['description']}\n";
            }
        }

        $guidance .= "\nMusic progression tips:\n";
        $guidance .= "- Opening scenes: Lower energy, establish tone\n";
        $guidance .= "- Middle scenes: Build energy, vary dynamics\n";
        $guidance .= "- Climax scenes: Peak energy, match emotional high point\n";
        $guidance .= "- Closing scenes: Resolution, emotional landing\n";

        return $guidance;
    }

    /**
     * Get suggested music moods based on content type.
     */
    protected function getSuggestedMusicMoods(?string $emotionalJourney, ?string $genreTemplate): array
    {
        $journeyMoods = [
            'triumph' => ['emotional', 'dramatic', 'epic'],
            'thriller' => ['tense', 'dramatic', 'ambient'],
            'horror' => ['horror', 'tense', 'ambient'],
            'comedy' => ['playful', 'upbeat', 'playful'],
            'educational' => ['corporate', 'ambient', 'upbeat'],
            'meditative' => ['ambient', 'emotional', 'ambient'],
            'nostalgia' => ['emotional', 'ambient', 'emotional'],
        ];

        $templateMoods = [
            'youtube-explainer' => ['corporate', 'upbeat', 'ambient'],
            'tiktok-viral' => ['upbeat', 'electronic', 'dramatic'],
            'cinematic-drama' => ['emotional', 'dramatic', 'epic'],
            'documentary' => ['ambient', 'emotional', 'dramatic'],
            'horror-thriller' => ['horror', 'tense', 'ambient'],
            'commercial-ad' => ['corporate', 'upbeat', 'emotional'],
            'inspirational' => ['emotional', 'epic', 'upbeat'],
        ];

        if ($genreTemplate && isset($templateMoods[$genreTemplate])) {
            return $templateMoods[$genreTemplate];
        }

        if ($emotionalJourney && isset($journeyMoods[$emotionalJourney])) {
            return $journeyMoods[$emotionalJourney];
        }

        return ['ambient', 'corporate', 'emotional'];
    }

    /**
     * Build pacing guidance for scene duration and WPM.
     */
    protected function buildPacingGuidance(int $duration, int $sceneCount, ?string $pacingProfile, ?string $genreTemplate): string
    {
        $pacingProfiles = config('appvideowizard.pacing_profiles', []);

        // Get profile from genre template if not specified
        if (!$pacingProfile && $genreTemplate) {
            $templates = config('appvideowizard.genre_templates', []);
            if (isset($templates[$genreTemplate]['defaults']['pacingProfile'])) {
                $pacingProfile = $templates[$genreTemplate]['defaults']['pacingProfile'];
            }
        }

        $profile = $pacingProfiles[$pacingProfile ?? 'standard'] ?? $pacingProfiles['standard'];

        $guidance = "=== PACING OPTIMIZATION ===\n";
        $guidance .= "Target Pace: {$profile['name']} ({$profile['wpm']} WPM)\n\n";

        // Calculate target words per scene
        $totalSeconds = $duration;
        $wordsPerMinute = $profile['wpm'];
        $totalWords = ($totalSeconds / 60) * $wordsPerMinute;
        $avgWordsPerScene = round($totalWords / $sceneCount);

        $guidance .= "Target Metrics:\n";
        $guidance .= "- Total Duration: {$duration}s\n";
        $guidance .= "- Scene Count: {$sceneCount}\n";
        $guidance .= "- Target WPM: {$wordsPerMinute}\n";
        $guidance .= "- Target Words: ~" . round($totalWords) . " total\n";
        $guidance .= "- Words Per Scene: ~{$avgWordsPerScene} words\n\n";

        $guidance .= "Scene Duration Guidelines:\n";
        $guidance .= "- Minimum: {$profile['sceneDuration']['min']}s\n";
        $guidance .= "- Average: {$profile['sceneDuration']['avg']}s\n";
        $guidance .= "- Maximum: {$profile['sceneDuration']['max']}s\n\n";

        $guidance .= "IMPORTANT: Each narration should be spoken at ~{$wordsPerMinute} WPM pace.\n";
        $guidance .= "Count words carefully - keep each scene's narration to approximately {$avgWordsPerScene} words.\n";

        return $guidance;
    }

    /**
     * Build genre template tips for specialized content.
     */
    protected function buildGenreTemplateTips(?string $genreTemplate): string
    {
        if (!$genreTemplate) {
            return '';
        }

        $templates = config('appvideowizard.genre_templates', []);

        if (!isset($templates[$genreTemplate])) {
            return '';
        }

        $template = $templates[$genreTemplate];

        $guidance = "=== GENRE-SPECIFIC TIPS ===\n";
        $guidance .= "Genre: {$template['name']}\n";
        $guidance .= "Description: {$template['description']}\n\n";

        if (!empty($template['tips'])) {
            $guidance .= "Pro Tips: {$template['tips']}\n";
        }

        return $guidance;
    }

    /**
     * Get genre template defaults for pre-configuration.
     */
    public function getGenreTemplateDefaults(string $genreTemplate): array
    {
        $templates = config('appvideowizard.genre_templates', []);

        if (!isset($templates[$genreTemplate])) {
            return [];
        }

        return $templates[$genreTemplate]['defaults'] ?? [];
    }

    /**
     * Parse the AI response into a structured script.
     */
    protected function parseScriptResponse(string $response, int $targetDuration = 60, int $expectedSceneCount = 0): array
    {
        $originalResponse = $response;

        // Extract JSON from response
        $json = $this->extractJson($response);

        // Parse JSON - capture error immediately before any logging (Log uses json_encode which resets json_last_error)
        $script = json_decode($json, true);
        $jsonError = json_last_error();
        $jsonErrorMsg = json_last_error_msg();

        // Now safe to log
        \Log::debug('VideoWizard: Parsing attempt', [
            'jsonLength' => strlen($json),
            'jsonPreview' => substr($json, 0, 300),
            'jsonError' => $jsonErrorMsg,
            'scriptType' => gettype($script),
        ]);

        // If initial parse failed, try multiple repair strategies
        if ($jsonError !== JSON_ERROR_NONE) {
            \Log::warning('VideoWizard: Initial JSON parse failed, trying repair strategies', [
                'error' => $jsonErrorMsg,
            ]);

            // Strategy 1: Try aggressive JSON repair
            $script = $this->tryAggressiveJsonRepair($json);

            // Strategy 2: Try rebuilding from extracted fields
            if ($script === null) {
                \Log::info('VideoWizard: Aggressive repair failed, trying field extraction');
                $script = $this->rebuildScriptFromResponse($originalResponse);
            }

            // Strategy 3: Try extracting from raw response with different approach
            if ($script === null) {
                \Log::info('VideoWizard: Field extraction failed, trying raw response parsing');
                $script = $this->tryRawResponseParsing($originalResponse);
            }

            if ($script !== null) {
                \Log::info('VideoWizard: JSON rebuilt successfully from response');
            } else {
                \Log::error('VideoWizard: JSON parse error - all repair strategies failed', [
                    'error' => $jsonErrorMsg,
                    'json' => substr($json, 0, 500),
                    'original' => substr($originalResponse, 0, 500),
                ]);
                throw new \Exception('Failed to parse script response. JSON error: ' . $jsonErrorMsg);
            }
        }

        // Check if we got a valid result
        if ($script === null) {
            \Log::error('VideoWizard: JSON decoded to null', [
                'json' => substr($json, 0, 500),
            ]);
            throw new \Exception('Failed to parse script response. AI returned empty or invalid data.');
        }

        if (!is_array($script)) {
            \Log::error('VideoWizard: JSON decoded to non-array', [
                'type' => gettype($script),
                'json' => substr($json, 0, 500),
            ]);
            throw new \Exception('Failed to parse script response. Expected object, got ' . gettype($script));
        }

        if (!isset($script['scenes']) || !is_array($script['scenes']) || empty($script['scenes'])) {
            \Log::error('VideoWizard: No scenes in parsed response', [
                'scriptKeys' => array_keys($script),
                'script' => substr(json_encode($script), 0, 500),
            ]);
            throw new \Exception('Failed to parse script response. No scenes array found in response.');
        }

        // Validate and normalize scenes
        $script = $this->normalizeScript($script, $targetDuration);

        \Log::info('VideoWizard: Script parsed successfully', [
            'title' => $script['title'],
            'sceneCount' => count($script['scenes']),
            'totalDuration' => array_sum(array_column($script['scenes'], 'duration')),
        ]);

        return $script;
    }

    /**
     * Extract JSON from AI response, handling various formats.
     */
    protected function extractJson(string $response): string
    {
        $response = trim($response);

        // Remove various code block formats
        // Handle ```json, '''json, ~~~json, etc.
        $response = preg_replace('/^[`\'~]{3,}(?:json)?\s*/im', '', $response);
        $response = preg_replace('/[`\'~]{3,}\s*$/im', '', $response);

        // Remove any BOM or invisible characters
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

        // Find JSON object boundaries
        $firstBrace = strpos($response, '{');
        $lastBrace = strrpos($response, '}');

        if ($firstBrace === false || $lastBrace === false || $lastBrace < $firstBrace) {
            \Log::warning('VideoWizard: No valid JSON braces found', [
                'response' => substr($response, 0, 200),
            ]);
            return $response;
        }

        $json = substr($response, $firstBrace, $lastBrace - $firstBrace + 1);

        // Fix common JSON issues
        $json = $this->fixJsonIssues($json);

        return $json;
    }

    /**
     * Fix common JSON formatting issues.
     */
    protected function fixJsonIssues(string $json): string
    {
        // Remove control characters except newlines and tabs
        $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $json);

        // Normalize line endings
        $json = str_replace(["\r\n", "\r"], "\n", $json);

        // Replace smart quotes with regular quotes (using UTF-8 byte sequences)
        // " = \xe2\x80\x9c, " = \xe2\x80\x9d, „ = \xe2\x80\x9e, « = \xc2\xab, » = \xc2\xbb
        $json = str_replace(["\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x9e", "\xc2\xab", "\xc2\xbb"], '"', $json);
        // ' = \xe2\x80\x98, ' = \xe2\x80\x99, ‚ = \xe2\x80\x9a, ‹ = \xe2\x80\xb9, › = \xe2\x80\xba
        $json = str_replace(["\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9a", "\xe2\x80\xb9", "\xe2\x80\xba"], "'", $json);

        // Replace em/en dashes with regular dashes
        // — = \xe2\x80\x94, – = \xe2\x80\x93
        $json = str_replace(["\xe2\x80\x94", "\xe2\x80\x93"], '-', $json);

        // Replace ellipsis with three dots
        $json = str_replace("\xe2\x80\xa6", '...', $json);

        // Remove trailing commas before } or ]
        $json = preg_replace('/,(\s*[}\]])/', '$1', $json);

        // Fix missing commas between array elements (common AI mistake)
        // Pattern: }\s*{ should be },{ when inside array
        $json = preg_replace('/\}\s+\{/', '},{', $json);

        // Fix double commas
        $json = preg_replace('/,\s*,/', ',', $json);

        // Remove any markdown formatting that might have leaked through
        $json = preg_replace('/\*\*([^*]+)\*\*/', '$1', $json); // Bold
        $json = preg_replace('/\*([^*]+)\*/', '$1', $json);     // Italic
        $json = preg_replace('/_([^_]+)_/', '$1', $json);       // Underscore italic

        // Fix common issues with unescaped characters inside strings
        // This is a multi-pass approach

        // First pass: Find and fix obvious issues
        $json = $this->fixUnescapedQuotesInStrings($json);

        return $json;
    }

    /**
     * Attempt to rebuild script from response when JSON parsing fails.
     * Uses more robust approach to extract scenes from malformed JSON.
     */
    protected function rebuildScriptFromResponse(string $response): ?array
    {
        $scenes = [];

        // Try to extract title
        $title = 'Generated Script';
        if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $response, $m)) {
            $title = $this->unescapeJsonString($m[1]);
        }

        // Try to extract hook
        $hook = '';
        if (preg_match('/"hook"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $response, $m)) {
            $hook = $this->unescapeJsonString($m[1]);
        }

        // Try to extract CTA
        $cta = '';
        if (preg_match('/"cta"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $response, $m)) {
            $cta = $this->unescapeJsonString($m[1]);
        }

        // Try multiple approaches and use the one that finds the most scenes
        $scenesByBraces = [];
        $scenesByRegex = [];

        // Approach 1: Find scenes array and extract by balanced braces
        if (preg_match('/"scenes"\s*:\s*\[/s', $response, $match, PREG_OFFSET_CAPTURE)) {
            $arrayStart = $match[0][1] + strlen($match[0][0]);
            $scenesByBraces = $this->extractScenesFromArray($response, $arrayStart);
        }

        // Approach 2: Always try regex as well (don't skip even if braces found some)
        $scenesByRegex = $this->extractScenesWithRegex($response);

        // Use whichever approach found more scenes
        $scenes = count($scenesByRegex) > count($scenesByBraces) ? $scenesByRegex : $scenesByBraces;

        \Log::info('VideoWizard: rebuildScriptFromResponse scene extraction', [
            'byBraces' => count($scenesByBraces),
            'byRegex' => count($scenesByRegex),
            'using' => count($scenesByRegex) > count($scenesByBraces) ? 'regex' : 'braces',
            'finalCount' => count($scenes),
        ]);

        if (empty($scenes)) {
            \Log::warning('VideoWizard: rebuildScriptFromResponse found no scenes', [
                'responseLength' => strlen($response),
                'responsePreview' => substr($response, 0, 500),
            ]);
            return null;
        }

        \Log::info('VideoWizard: rebuildScriptFromResponse recovered scenes', [
            'sceneCount' => count($scenes),
        ]);

        return [
            'title' => $title,
            'hook' => $hook,
            'scenes' => $scenes,
            'cta' => $cta,
        ];
    }

    /**
     * Extract scenes from the scenes array by finding balanced braces.
     * Also handles malformed JSON where scene objects lack opening braces.
     */
    protected function extractScenesFromArray(string $response, int $startPos): array
    {
        $scenes = [];
        $len = strlen($response);
        $pos = $startPos;
        $sceneIndex = 0;

        while ($pos < $len) {
            // Skip whitespace and commas
            while ($pos < $len && (ctype_space($response[$pos]) || $response[$pos] === ',')) {
                $pos++;
            }

            // Check for end of array
            if ($pos >= $len || $response[$pos] === ']') {
                break;
            }

            // Case 1: Proper scene object with opening brace
            if ($response[$pos] === '{') {
                $sceneStart = $pos;
                $braceCount = 1;
                $pos++;

                // Find matching closing brace
                $inString = false;
                $escape = false;
                while ($pos < $len && $braceCount > 0) {
                    $char = $response[$pos];

                    if ($escape) {
                        $escape = false;
                    } elseif ($char === '\\' && $inString) {
                        $escape = true;
                    } elseif ($char === '"' && !$escape) {
                        $inString = !$inString;
                    } elseif (!$inString) {
                        if ($char === '{') $braceCount++;
                        elseif ($char === '}') $braceCount--;
                    }
                    $pos++;
                }

                if ($braceCount === 0) {
                    $sceneJson = substr($response, $sceneStart, $pos - $sceneStart);
                    $scene = $this->parseSceneObject($sceneJson, $sceneIndex);
                    if ($scene) {
                        $scenes[] = $scene;
                        $sceneIndex++;
                    }
                }
            }
            // Case 2: Malformed - scene starts with "id" or "narration" without opening brace
            elseif ($response[$pos] === '"') {
                // Look ahead to see if this looks like a scene field
                $ahead = substr($response, $pos, 20);
                if (preg_match('/^"(id|narration|title|visualDescription)"/', $ahead)) {
                    // Find the end of this malformed scene object
                    // It ends at the next scene-id pattern or end of array
                    $sceneStart = $pos;
                    $nextScenePos = $this->findNextSceneStart($response, $pos + 1);
                    $sceneEnd = $nextScenePos !== false ? $nextScenePos : strpos($response, ']', $pos);

                    if ($sceneEnd !== false) {
                        $sceneContent = substr($response, $sceneStart, $sceneEnd - $sceneStart);
                        // Wrap in braces to make valid JSON
                        $sceneJson = '{' . rtrim(trim($sceneContent), ',') . '}';
                        $scene = $this->parseSceneObject($sceneJson, $sceneIndex);
                        if ($scene) {
                            $scenes[] = $scene;
                            $sceneIndex++;
                        }
                        $pos = $sceneEnd;
                    } else {
                        $pos++;
                    }
                } else {
                    $pos++;
                }
            } else {
                // Unexpected character, move forward
                $pos++;
            }
        }

        return $scenes;
    }

    /**
     * Find the start position of the next scene in the response.
     */
    protected function findNextSceneStart(string $response, int $startPos): int|false
    {
        // Look for patterns that indicate a new scene
        $patterns = [
            '/"id"\s*:\s*"scene-\d+"/i',
            '/,\s*\{\s*"id"/i',
            '/,\s*"id"\s*:\s*"scene-/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $match, PREG_OFFSET_CAPTURE, $startPos)) {
                return $match[0][1];
            }
        }

        return false;
    }

    /**
     * Parse a single scene object JSON string.
     */
    protected function parseSceneObject(string $sceneJson, int $index): ?array
    {
        // First try standard JSON decode
        $scene = json_decode($sceneJson, true);
        if ($scene !== null && isset($scene['narration'])) {
            $scene['id'] = $scene['id'] ?? 'scene-' . ($index + 1);
            return $scene;
        }

        // If that fails, extract fields with regex
        $scene = [
            'id' => 'scene-' . ($index + 1),
            'title' => 'Scene ' . ($index + 1),
            'narration' => '',
            'visualDescription' => '',
            'duration' => 15,
        ];

        // Extract narration
        if (preg_match('/"narration"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneJson, $m)) {
            $scene['narration'] = $this->unescapeJsonString($m[1]);
        }

        // Extract visualDescription
        if (preg_match('/"visualDescription"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneJson, $m)) {
            $scene['visualDescription'] = $this->unescapeJsonString($m[1]);
        }

        // Extract title
        if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneJson, $m)) {
            $scene['title'] = $this->unescapeJsonString($m[1]);
        }

        // Extract id
        if (preg_match('/"id"\s*:\s*"(scene-\d+)"/s', $sceneJson, $m)) {
            $scene['id'] = $m[1];
        }

        // Extract duration
        if (preg_match('/"duration"\s*:\s*(\d+)/s', $sceneJson, $m)) {
            $scene['duration'] = (int)$m[1];
        }

        // Extract mood
        if (preg_match('/"mood"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneJson, $m)) {
            $scene['mood'] = $this->unescapeJsonString($m[1]);
        }

        // Extract transition
        if (preg_match('/"transition"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneJson, $m)) {
            $scene['transition'] = $this->unescapeJsonString($m[1]);
        }

        // Only return if we have meaningful content
        if (!empty($scene['narration']) || !empty($scene['visualDescription'])) {
            return $scene;
        }

        return null;
    }

    /**
     * Fallback: Extract scenes using regex patterns.
     */
    protected function extractScenesWithRegex(string $response): array
    {
        $scenes = [];

        // Pattern to match scene-like structures - find all "narration" fields
        if (preg_match_all('/"narration"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $response, $narrationMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($narrationMatches[1] as $index => $match) {
                $narration = $this->unescapeJsonString($match[0]);
                $position = $match[1];

                // Look for visualDescription near this narration (within 500 chars)
                $searchRange = substr($response, max(0, $position - 200), 700);
                $visualDescription = $narration; // Default to narration

                if (preg_match('/"visualDescription"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $searchRange, $vm)) {
                    $visualDescription = $this->unescapeJsonString($vm[1]);
                }

                // Look for duration near this narration
                $duration = 15;
                if (preg_match('/"duration"\s*:\s*(\d+)/s', $searchRange, $dm)) {
                    $duration = (int)$dm[1];
                }

                $scenes[] = [
                    'id' => 'scene-' . ($index + 1),
                    'title' => 'Scene ' . ($index + 1),
                    'narration' => $narration,
                    'visualDescription' => $visualDescription,
                    'duration' => $duration,
                ];
            }
        }

        return $scenes;
    }

    /**
     * Try aggressive JSON repair strategies.
     * Returns parsed script array or null if all attempts fail.
     */
    protected function tryAggressiveJsonRepair(string $json): ?array
    {
        // Strategy 1: Remove all control characters and retry
        $repaired = preg_replace('/[\x00-\x1F\x7F]/', ' ', $json);
        $repaired = preg_replace('/\s+/', ' ', $repaired);
        $result = json_decode($repaired, true);
        if ($result !== null && isset($result['scenes']) && is_array($result['scenes']) && !empty($result['scenes'])) {
            \Log::info('VideoWizard: Aggressive repair succeeded with strategy 1 (control chars)');
            return $result;
        }

        // Strategy 2: Fix incomplete/truncated JSON
        $result = $this->fixTruncatedJson($json);
        if ($result !== null && isset($result['scenes']) && is_array($result['scenes']) && !empty($result['scenes'])) {
            \Log::info('VideoWizard: Aggressive repair succeeded with strategy 2 (truncated)');
            return $result;
        }

        // Strategy 3: Try to parse individual scene objects
        $result = $this->parseSceneByScene($json);
        if ($result !== null && isset($result['scenes']) && is_array($result['scenes']) && !empty($result['scenes'])) {
            \Log::info('VideoWizard: Aggressive repair succeeded with strategy 3 (scene-by-scene)');
            return $result;
        }

        // Strategy 4: Strip problematic unicode and retry
        $repaired = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $json);
        $result = json_decode($repaired, true);
        if ($result !== null && isset($result['scenes']) && is_array($result['scenes']) && !empty($result['scenes'])) {
            \Log::info('VideoWizard: Aggressive repair succeeded with strategy 4 (unicode strip)');
            return $result;
        }

        // Strategy 5: Fix unbalanced braces
        $result = $this->fixUnbalancedBraces($json);
        if ($result !== null && isset($result['scenes']) && is_array($result['scenes']) && !empty($result['scenes'])) {
            \Log::info('VideoWizard: Aggressive repair succeeded with strategy 5 (unbalanced braces)');
            return $result;
        }

        return null;
    }

    /**
     * Fix truncated JSON by closing open brackets/braces.
     */
    protected function fixTruncatedJson(string $json): ?array
    {
        // Count open braces and brackets
        $braceCount = 0;
        $bracketCount = 0;
        $inString = false;
        $escape = false;

        for ($i = 0; $i < strlen($json); $i++) {
            $char = $json[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $escape = true;
                continue;
            }

            if ($char === '"' && !$escape) {
                $inString = !$inString;
                continue;
            }

            if (!$inString) {
                if ($char === '{') $braceCount++;
                elseif ($char === '}') $braceCount--;
                elseif ($char === '[') $bracketCount++;
                elseif ($char === ']') $bracketCount--;
            }
        }

        // If we're inside a string, close it
        if ($inString) {
            $json .= '"';
        }

        // Close any open brackets and braces
        while ($bracketCount > 0) {
            $json .= ']';
            $bracketCount--;
        }
        while ($braceCount > 0) {
            $json .= '}';
            $braceCount--;
        }

        // Try to parse
        $result = json_decode($json, true);
        if ($result !== null) {
            return $result;
        }

        return null;
    }

    /**
     * Parse JSON by extracting and parsing each scene object individually.
     */
    protected function parseSceneByScene(string $json): ?array
    {
        // Extract title, hook, cta first
        $title = 'Generated Script';
        $hook = '';
        $cta = '';

        if (preg_match('/"title"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $json, $m)) {
            $title = $this->unescapeJsonString($m[1]);
        }
        if (preg_match('/"hook"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $json, $m)) {
            $hook = $this->unescapeJsonString($m[1]);
        }
        if (preg_match('/"cta"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $json, $m)) {
            $cta = $this->unescapeJsonString($m[1]);
        }

        // Find scenes array start
        if (!preg_match('/"scenes"\s*:\s*\[/s', $json, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $scenesStart = $match[0][1] + strlen($match[0][0]);
        $scenes = [];
        $sceneIndex = 0;

        // Try to find each scene by looking for scene-like patterns
        $pattern = '/\{\s*"(?:id|title|narration)"[^}]*?"narration"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s';
        if (preg_match_all($pattern, $json, $matches, PREG_OFFSET_CAPTURE, $scenesStart)) {
            foreach ($matches[0] as $index => $sceneMatch) {
                $sceneText = $sceneMatch[0];
                // Try to extend to include visualDescription if present
                $pos = $sceneMatch[1];
                $endPos = $pos + strlen($sceneText);

                // Look for closing brace of this scene
                $rest = substr($json, $endPos, 500);
                if (preg_match('/^[^}]*"visualDescription"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $rest, $vm)) {
                    $sceneText .= $vm[0];
                }

                // Extract narration
                $narration = '';
                if (preg_match('/"narration"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneText, $nm)) {
                    $narration = $this->unescapeJsonString($nm[1]);
                }

                // Extract visualDescription
                $visual = $narration;
                if (preg_match('/"visualDescription"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $sceneText, $vm)) {
                    $visual = $this->unescapeJsonString($vm[1]);
                }

                // Extract duration
                $duration = 15;
                if (preg_match('/"duration"\s*:\s*(\d+)/s', $sceneText, $dm)) {
                    $duration = (int)$dm[1];
                }

                if (!empty($narration)) {
                    $scenes[] = [
                        'id' => 'scene-' . ($sceneIndex + 1),
                        'title' => 'Scene ' . ($sceneIndex + 1),
                        'narration' => $narration,
                        'visualDescription' => $visual,
                        'duration' => max(5, min(60, $duration)),
                    ];
                    $sceneIndex++;
                }
            }
        }

        if (empty($scenes)) {
            return null;
        }

        return [
            'title' => $title,
            'hook' => $hook,
            'scenes' => $scenes,
            'cta' => $cta,
        ];
    }

    /**
     * Fix unbalanced braces by finding the largest valid JSON object.
     */
    protected function fixUnbalancedBraces(string $json): ?array
    {
        // Start from the beginning and find the largest parseable object
        $firstBrace = strpos($json, '{');
        if ($firstBrace === false) {
            return null;
        }

        // Try progressively shorter substrings until we find valid JSON
        for ($end = strlen($json); $end > $firstBrace + 10; $end--) {
            $candidate = substr($json, $firstBrace, $end - $firstBrace);

            // Quick check: does it end with }?
            if (substr(rtrim($candidate), -1) !== '}') {
                // Try to close it
                $candidate = rtrim($candidate, ', \t\n\r');
                $candidate .= ']}';
            }

            $result = @json_decode($candidate, true);
            if ($result !== null && isset($result['scenes'])) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Try parsing raw response with alternative approach.
     * This is a last resort when all JSON repair attempts fail.
     */
    protected function tryRawResponseParsing(string $response): ?array
    {
        $scenes = [];

        // Try to extract any text that looks like scene content
        // Pattern 1: Look for numbered scenes
        if (preg_match_all('/(?:scene\s*(?:\d+|#\d+)|^\d+\.|^\*\*scene)/im', $response, $markers, PREG_OFFSET_CAPTURE)) {
            $positions = array_column($markers[0], 1);
            $positions[] = strlen($response);

            for ($i = 0; $i < count($positions) - 1; $i++) {
                $sceneText = substr($response, $positions[$i], $positions[$i + 1] - $positions[$i]);

                // Try to extract narration/dialogue text
                $narration = '';
                if (preg_match('/"narration"\s*:\s*"([^"]+)"/s', $sceneText, $m)) {
                    $narration = $m[1];
                } elseif (preg_match('/(?:narration|dialogue|text|script):\s*["\']?([^"\'\n]+)["\']?/i', $sceneText, $m)) {
                    $narration = $m[1];
                } elseif (preg_match('/:\s*["\']([^"\']{20,})["\']/', $sceneText, $m)) {
                    $narration = $m[1];
                }

                if (!empty($narration)) {
                    $scenes[] = [
                        'id' => 'scene-' . ($i + 1),
                        'title' => 'Scene ' . ($i + 1),
                        'narration' => trim($narration),
                        'visualDescription' => trim($narration),
                        'duration' => 15,
                    ];
                }
            }
        }

        // If no scenes found, try regex extraction as final fallback
        if (empty($scenes)) {
            $scenes = $this->extractScenesWithRegex($response);
        }

        if (empty($scenes)) {
            return null;
        }

        // Extract title if possible
        $title = 'Generated Script';
        if (preg_match('/"title"\s*:\s*"([^"]+)"/s', $response, $m)) {
            $title = $m[1];
        } elseif (preg_match('/^#\s+(.+)$/m', $response, $m)) {
            $title = $m[1];
        }

        return [
            'title' => $title,
            'hook' => '',
            'scenes' => $scenes,
            'cta' => '',
        ];
    }

    /**
     * Unescape a JSON string value.
     */
    protected function unescapeJsonString(string $str): string
    {
        // Handle common escape sequences
        $str = str_replace(['\\n', '\\r', '\\t', '\\"', '\\\\'], ["\n", "\r", "\t", '"', '\\'], $str);
        return $str;
    }

    /**
     * Fix unescaped quotes inside JSON strings.
     * This uses a state machine approach to properly handle JSON structure.
     */
    protected function fixUnescapedQuotesInStrings(string $json): string
    {
        $result = '';
        $inString = false;
        $escape = false;
        $len = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $char = $json[$i];
            $nextChar = $i < $len - 1 ? $json[$i + 1] : '';

            if ($escape) {
                $result .= $char;
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                $result .= $char;
                continue;
            }

            if ($char === '"') {
                if (!$inString) {
                    // Starting a string
                    $inString = true;
                    $result .= $char;
                } else {
                    // Potentially ending a string - check context
                    // Look ahead to see if this looks like end of string
                    $restOfLine = substr($json, $i + 1, 50);

                    // If followed by :, ,, }, ], or whitespace + these, it's probably end of string
                    if (preg_match('/^\s*[,:}\]]/', $restOfLine)) {
                        $inString = false;
                        $result .= $char;
                    } else if (preg_match('/^\s*$/', $restOfLine)) {
                        // End of JSON
                        $inString = false;
                        $result .= $char;
                    } else {
                        // Probably an unescaped quote inside string - escape it
                        $result .= '\\"';
                    }
                }
            } else if ($inString && $char === "\n") {
                // Newline inside string - escape it
                $result .= '\\n';
            } else if ($inString && $char === "\t") {
                // Tab inside string - escape it
                $result .= '\\t';
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Normalize and validate script structure.
     * Ensures all fields are properly typed to prevent htmlspecialchars errors in views.
     */
    protected function normalizeScript(array $script, int $targetDuration): array
    {
        // Ensure required top-level fields are STRINGS (not arrays)
        $script['title'] = $this->ensureString($script['title'] ?? null, 'Untitled Script');
        $script['hook'] = $this->ensureString($script['hook'] ?? null, '');
        $script['cta'] = $this->ensureString($script['cta'] ?? null, '');

        // Normalize each scene
        $sceneCount = count($script['scenes']);
        $avgDuration = (int) ceil($targetDuration / $sceneCount);

        foreach ($script['scenes'] as $index => &$scene) {
            $scene = $this->sanitizeScene($scene, $index, $avgDuration);
        }

        // Calculate totals safely
        $script['totalDuration'] = 0;
        $script['wordCount'] = 0;
        foreach ($script['scenes'] as $s) {
            $script['totalDuration'] += is_numeric($s['duration'] ?? null) ? (int)$s['duration'] : $avgDuration;
            $narration = is_string($s['narration'] ?? null) ? $s['narration'] : '';
            $script['wordCount'] += str_word_count($narration);
        }

        return $script;
    }

    /**
     * Sanitize a single scene to ensure all fields are properly typed.
     * This is the SINGLE SOURCE OF TRUTH for scene data sanitization.
     */
    public function sanitizeScene(array $scene, int $index = 0, int $defaultDuration = 15): array
    {
        // Sanitize voiceover first to determine speechType
        $voiceover = $this->sanitizeVoiceover($scene['voiceover'] ?? []);

        // Sanitize speech segments (new dynamic speech system)
        $speechSegments = $this->sanitizeSpeechSegments(
            $scene['speechSegments'] ?? [],
            $scene,
            $voiceover
        );

        // If we have segments, mark speechType as 'mixed'
        if (!empty($speechSegments) && count($speechSegments) > 1) {
            $voiceover['speechType'] = 'mixed';
        }

        return [
            // Core identifiers
            'id' => $this->ensureString($scene['id'] ?? null, 'scene-' . ($index + 1)),
            'title' => $this->ensureString($scene['title'] ?? null, 'Scene ' . ($index + 1)),

            // Text content - must be strings
            'narration' => $this->ensureString($scene['narration'] ?? null, ''),
            'visualDescription' => $this->ensureString(
                $scene['visualDescription'] ?? $scene['visual_description'] ?? $scene['visual'] ?? null,
                ''
            ),
            'visualPrompt' => $this->ensureString($scene['visualPrompt'] ?? null, ''),

            // Metadata - must be strings
            'mood' => $this->ensureString($scene['mood'] ?? null, ''),
            'transition' => $this->ensureString($scene['transition'] ?? null, 'cut'),
            'status' => $this->ensureString($scene['status'] ?? null, 'draft'),

            // Duration - must be numeric
            'duration' => $this->ensureNumeric($scene['duration'] ?? null, $defaultDuration, 5, 300),

            // Voiceover structure
            'voiceover' => $voiceover,

            // NEW: Speech segments for dynamic mixed narration/dialogue
            'speechSegments' => $speechSegments,

            // Ken Burns effect
            'kenBurns' => $this->sanitizeKenBurns($scene['kenBurns'] ?? null),

            // Image data (preserve as-is if exists)
            'image' => $scene['image'] ?? null,
            'imageUrl' => $this->ensureString($scene['imageUrl'] ?? null, ''),
        ];
    }

    /**
     * Sanitize voiceover structure.
     *
     * Speech types:
     * - 'narrator': External voiceover describing the scene (NO lip-sync)
     * - 'internal': Character's inner thoughts, heard but not spoken (NO lip-sync)
     * - 'monologue': Character speaking aloud to themselves (lip-sync REQUIRED)
     * - 'dialogue': Characters speaking to each other (lip-sync REQUIRED)
     * - 'mixed': Dynamic segments with mixed narrator/dialogue/internal (NEW)
     */
    protected function sanitizeVoiceover($voiceover): array
    {
        if (!is_array($voiceover)) {
            $voiceover = [];
        }

        // Validate speechType - now includes 'mixed' for dynamic segments
        $validSpeechTypes = ['narrator', 'internal', 'monologue', 'dialogue', 'mixed'];
        $speechType = $voiceover['speechType'] ?? 'narrator';
        if (!in_array($speechType, $validSpeechTypes)) {
            $speechType = 'narrator';
        }

        return [
            'enabled' => (bool)($voiceover['enabled'] ?? true),
            'text' => $this->ensureString($voiceover['text'] ?? null, ''),
            'voiceId' => $voiceover['voiceId'] ?? null,
            'status' => $this->ensureString($voiceover['status'] ?? null, 'pending'),
            // Speech type for determining lip-sync requirements
            'speechType' => $speechType,
            // Character who is speaking (for monologue/dialogue)
            'speakingCharacter' => $voiceover['speakingCharacter'] ?? null,
        ];
    }

    /**
     * Sanitize speech segments array.
     *
     * If no segments exist but narration text does, parse it for segment markers.
     * This enables both backwards compatibility and new AI-generated segmented scripts.
     *
     * @param array $segments Existing segments array
     * @param array $scene Full scene data for migration context
     * @param array $voiceover Sanitized voiceover data
     * @return array Sanitized segments array
     */
    protected function sanitizeSpeechSegments(array $segments, array $scene, array $voiceover): array
    {
        // If segments already exist, sanitize them
        if (!empty($segments)) {
            return array_map(function ($segment, $index) {
                return $this->sanitizeSingleSegment($segment, $index);
            }, $segments, array_keys($segments));
        }

        // No segments - get narration text (prefer narration field over voiceover.text)
        $narrationText = $scene['narration'] ?? '';
        $voiceoverText = $voiceover['text'] ?? '';
        $text = !empty(trim($narrationText)) ? $narrationText : $voiceoverText;

        if (empty(trim($text))) {
            return [];
        }

        // Check if text contains segment markers (AI-generated or manually formatted)
        $hasSegmentMarkers = preg_match('/\[(NARRATOR|INTERNAL|MONOLOGUE|DIALOGUE)[:\]]|^[A-Z][A-Za-z\s\-\']+:\s/m', $text);

        try {
            $parser = app(SpeechSegmentParser::class);

            if ($hasSegmentMarkers) {
                // Parse segmented text directly
                $parsed = $parser->parse($text, $scene['characterBible'] ?? []);
            } else {
                // No segment markers found - attempt dialogue extraction before falling back to legacy
                if ($this->looksLikeDialogue($text)) {
                    $extracted = $this->attemptDialogueExtraction($text, $scene['characterBible'] ?? []);
                    if (!empty($extracted)) {
                        $parsed = $extracted;
                    }
                }

                // Fall back to legacy migration if no dialogue was extracted
                if (empty($parsed)) {
                    $parsed = $parser->migrateFromLegacy([
                        'voiceover' => $voiceover,
                        'narration' => $narrationText,
                        'speechType' => $voiceover['speechType'] ?? 'narrator',
                        'characterBible' => $scene['characterBible'] ?? [],
                    ]);
                }
            }

            return array_map(function ($segment, $index) {
                if ($segment instanceof SpeechSegment) {
                    return $segment->toArray();
                }
                return $this->sanitizeSingleSegment($segment, $index);
            }, $parsed, array_keys($parsed));
        } catch (\Exception $e) {
            // Fallback: create single narrator segment
            return [
                $this->sanitizeSingleSegment([
                    'type' => $voiceover['speechType'] ?? 'narrator',
                    'text' => $text,
                    'speaker' => $voiceover['speakingCharacter'] ?? null,
                ], 0),
            ];
        }
    }

    /**
     * Sanitize a single speech segment.
     */
    protected function sanitizeSingleSegment(array $segment, int $index): array
    {
        $validTypes = ['narrator', 'dialogue', 'internal', 'monologue'];
        $type = $segment['type'] ?? 'narrator';
        if (!in_array($type, $validTypes)) {
            $type = 'narrator';
        }

        // Calculate needsLipSync based on type
        $needsLipSync = in_array($type, ['dialogue', 'monologue']);

        return [
            'id' => $segment['id'] ?? 'seg-' . \Illuminate\Support\Str::random(8),
            'type' => $type,
            'text' => $this->ensureString($segment['text'] ?? null, ''),
            'speaker' => $segment['speaker'] ?? null,
            'characterId' => $segment['characterId'] ?? null,
            'voiceId' => $segment['voiceId'] ?? null,
            'needsLipSync' => $segment['needsLipSync'] ?? $needsLipSync,
            'startTime' => $segment['startTime'] ?? null,
            'duration' => $segment['duration'] ?? null,
            'audioUrl' => $segment['audioUrl'] ?? null,
            'order' => $segment['order'] ?? $index,
            'emotion' => $segment['emotion'] ?? null,
        ];
    }

    /**
     * Check if unmarked text looks like it contains dialogue.
     * Looks for quotation marks, "said/asked/replied" attribution patterns, or name: patterns.
     */
    protected function looksLikeDialogue(string $text): bool
    {
        // Check for quoted speech: "Hello," she said
        if (preg_match('/"[^"]{5,}"/', $text)) {
            return true;
        }

        // Check for speech attribution verbs
        if (preg_match('/\b(said|asked|replied|whispered|shouted|exclaimed|muttered|yelled|called|answered)\b/i', $text)) {
            return true;
        }

        // Check for name-colon patterns (e.g., "Jack: Hello")
        if (preg_match('/^[A-Z][a-z]+\s*:/m', $text)) {
            return true;
        }

        return false;
    }

    /**
     * Attempt to extract dialogue segments from unmarked text.
     * Handles patterns like: "Hello," Jack said. / Jack: "Hello" / JACK: Hello
     *
     * @param string $text Raw text without segment markers
     * @param array $characterBible Character bible for name matching
     * @return array Extracted speech segments, or empty if extraction fails
     */
    protected function attemptDialogueExtraction(string $text, array $characterBible): array
    {
        $segments = [];
        $order = 0;

        // Build character name list from bible
        $characterNames = [];
        foreach ($characterBible as $char) {
            $name = $char['name'] ?? $char['characterName'] ?? null;
            if ($name) {
                $characterNames[] = preg_quote($name, '/');
            }
        }

        // Split text into lines for processing
        $lines = preg_split('/\n+/', trim($text));
        $narratorBuffer = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $matched = false;

            // Pattern 1: "NAME: text" (e.g., "JACK: I need to find her")
            if (!empty($characterNames)) {
                $namePattern = '/^(' . implode('|', $characterNames) . ')\s*:\s*(.+)$/i';
                if (preg_match($namePattern, $line, $m)) {
                    // Flush narrator buffer
                    if (!empty(trim($narratorBuffer))) {
                        $segments[] = [
                            'type' => 'narrator',
                            'text' => trim($narratorBuffer),
                            'speaker' => null,
                            'needsLipSync' => false,
                            'order' => $order++,
                        ];
                        $narratorBuffer = '';
                    }
                    $segments[] = [
                        'type' => 'dialogue',
                        'text' => trim($m[2], '" '),
                        'speaker' => ucfirst(strtolower($m[1])),
                        'needsLipSync' => true,
                        'order' => $order++,
                    ];
                    $matched = true;
                }
            }

            // Pattern 2: Quoted speech with attribution: "Hello," Jack said.
            if (!$matched && preg_match('/^"([^"]+)"\s*,?\s*([A-Z][a-z]+)\s+(said|asked|replied|whispered|shouted|exclaimed|muttered)/i', $line, $m)) {
                if (!empty(trim($narratorBuffer))) {
                    $segments[] = [
                        'type' => 'narrator',
                        'text' => trim($narratorBuffer),
                        'speaker' => null,
                        'needsLipSync' => false,
                        'order' => $order++,
                    ];
                    $narratorBuffer = '';
                }
                $segments[] = [
                    'type' => 'dialogue',
                    'text' => trim($m[1]),
                    'speaker' => $m[2],
                    'needsLipSync' => true,
                    'order' => $order++,
                ];
                $matched = true;
            }

            // Pattern 3: Attribution before quote: Jack said, "Hello"
            if (!$matched && preg_match('/^([A-Z][a-z]+)\s+(said|asked|replied|whispered|shouted),?\s*"([^"]+)"/i', $line, $m)) {
                if (!empty(trim($narratorBuffer))) {
                    $segments[] = [
                        'type' => 'narrator',
                        'text' => trim($narratorBuffer),
                        'speaker' => null,
                        'needsLipSync' => false,
                        'order' => $order++,
                    ];
                    $narratorBuffer = '';
                }
                $segments[] = [
                    'type' => 'dialogue',
                    'text' => trim($m[3]),
                    'speaker' => $m[1],
                    'needsLipSync' => true,
                    'order' => $order++,
                ];
                $matched = true;
            }

            // No dialogue pattern matched - accumulate as narrator
            if (!$matched) {
                $narratorBuffer .= ' ' . $line;
            }
        }

        // Flush remaining narrator buffer
        if (!empty(trim($narratorBuffer))) {
            $segments[] = [
                'type' => 'narrator',
                'text' => trim($narratorBuffer),
                'speaker' => null,
                'needsLipSync' => false,
                'order' => $order++,
            ];
        }

        // Only return if we found at least one dialogue segment
        $hasDialogue = !empty(array_filter($segments, fn($s) => $s['type'] === 'dialogue'));
        return $hasDialogue ? $segments : [];
    }

    /**
     * Sanitize Ken Burns effect configuration.
     */
    protected function sanitizeKenBurns($kenBurns): array
    {
        if (!is_array($kenBurns)) {
            return $this->generateKenBurnsEffect();
        }

        return [
            'startScale' => (float)($kenBurns['startScale'] ?? 1.0),
            'endScale' => (float)($kenBurns['endScale'] ?? 1.15),
            'startX' => (float)($kenBurns['startX'] ?? 0.5),
            'startY' => (float)($kenBurns['startY'] ?? 0.5),
            'endX' => (float)($kenBurns['endX'] ?? 0.5),
            'endY' => (float)($kenBurns['endY'] ?? 0.5),
        ];
    }

    /**
     * Ensure a value is a string. If it's an array or other type, return default.
     */
    protected function ensureString($value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return $default;
    }

    /**
     * Ensure a value is numeric within bounds.
     */
    protected function ensureNumeric($value, int $default, int $min = 0, int $max = PHP_INT_MAX): int
    {
        if (is_numeric($value)) {
            return max($min, min($max, (int)$value));
        }
        return $default;
    }

    /**
     * Generate a random Ken Burns effect configuration.
     */
    protected function generateKenBurnsEffect(): array
    {
        $effects = [
            // Zoom in center
            ['startScale' => 1.0, 'endScale' => 1.15, 'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.5, 'endY' => 0.5],
            // Zoom out center
            ['startScale' => 1.15, 'endScale' => 1.0, 'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.5, 'endY' => 0.5],
            // Pan left to right
            ['startScale' => 1.1, 'endScale' => 1.1, 'startX' => 0.3, 'startY' => 0.5, 'endX' => 0.7, 'endY' => 0.5],
            // Pan right to left
            ['startScale' => 1.1, 'endScale' => 1.1, 'startX' => 0.7, 'startY' => 0.5, 'endX' => 0.3, 'endY' => 0.5],
            // Zoom in top-left
            ['startScale' => 1.0, 'endScale' => 1.2, 'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.3, 'endY' => 0.3],
            // Zoom in bottom-right
            ['startScale' => 1.0, 'endScale' => 1.2, 'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.7, 'endY' => 0.7],
            // Pan up
            ['startScale' => 1.1, 'endScale' => 1.1, 'startX' => 0.5, 'startY' => 0.6, 'endX' => 0.5, 'endY' => 0.4],
            // Pan down
            ['startScale' => 1.1, 'endScale' => 1.1, 'startX' => 0.5, 'startY' => 0.4, 'endX' => 0.5, 'endY' => 0.6],
        ];

        return $effects[array_rand($effects)];
    }

    /**
     * Regenerate a single scene.
     */
    public function regenerateScene(WizardProject $project, int $sceneIndex, array $options = []): ?array
    {
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $existingScene = $options['existingScene'] ?? [];
        $tone = $options['tone'] ?? 'engaging';
        $contentDepth = $options['contentDepth'] ?? 'detailed';
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        $concept = $project->concept ?? [];
        $topic = $concept['refinedConcept'] ?? $concept['rawInput'] ?? '';

        $prompt = <<<PROMPT
You are an expert video scriptwriter. Regenerate this scene with fresh content while maintaining the video's theme.

TOPIC: {$topic}
TONE: {$tone}
SCENE NUMBER: {$sceneIndex} + 1
CURRENT SCENE TITLE: {$existingScene['title']}
CURRENT DURATION: {$existingScene['duration']} seconds

Generate a new version of this scene with:
- Fresh narration that fits the same duration
- New visual description for AI image generation
- Maintain connection to the overall topic

VOICEOVER SPEECH TYPES (IMPORTANT):
- "narrator": External narrator describing the scene (character's lips do NOT move)
- "internal": Character's inner thoughts heard as voiceover (character's lips do NOT move)
- "monologue": Character speaking ALOUD to themselves (character's lips MUST move)
- "dialogue": Characters speaking to each other (character's lips MUST move)

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "id": "{$existingScene['id']}",
  "title": "New scene title",
  "narration": "New narrator text (match duration)",
  "visualDescription": "Detailed visual for AI image generation",
  "visualPrompt": "Concise prompt for image AI (50-100 words)",
  "voiceover": {
    "enabled": true,
    "text": "",
    "voiceId": null,
    "status": "pending",
    "speechType": "narrator",
    "speakingCharacter": null
  },
  "duration": {$existingScene['duration']},
  "mood": "cinematic",
  "status": "draft"
}
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 4000, // Ensure enough tokens for scene regeneration
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';
        $json = $this->extractJson($response);
        $scene = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($scene['narration'])) {
            return null;
        }

        // Ensure Ken Burns effect
        $scene['kenBurns'] = $this->generateKenBurnsEffect();

        return $scene;
    }

    /**
     * Regenerate a scene with full context awareness (Phase 3: Context Window Maximization).
     * Uses Story Bible + surrounding scenes for perfect narrative continuity.
     */
    public function regenerateSceneWithContext(WizardProject $project, int $sceneIndex, array $allScenes, array $options = []): ?array
    {
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $existingScene = $allScenes[$sceneIndex] ?? [];
        $tone = $options['tone'] ?? 'engaging';
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        // Build full context using ContextWindowService
        $contextData = $this->contextService->buildSceneRegenerationContext(
            $project,
            $allScenes,
            $sceneIndex,
            ['aiModelTier' => $aiModelTier]
        );

        \Log::info('VideoWizard: Context-aware scene regeneration', [
            'sceneIndex' => $sceneIndex,
            'aiModelTier' => $aiModelTier,
            'contextLength' => strlen($contextData),
            'hasStoryBible' => $project->hasStoryBible(),
            'totalScenes' => count($allScenes),
        ]);

        $prompt = $contextData . <<<PROMPT

TONE: {$tone}
TARGET SCENE: {$sceneIndex} + 1
SCENE TITLE: {$existingScene['title']}
DURATION: {$existingScene['duration']} seconds

Generate a completely new version of this scene that:
1. Flows naturally from previous scenes (if any)
2. Leads smoothly into following scenes (if any)
3. Uses characters exactly as described in the Story Bible
4. Uses locations exactly as described in the Story Bible
5. Maintains the established visual style and tone
6. Keeps the same approximate duration

VOICEOVER SPEECH TYPES (CRITICAL FOR LIP-SYNC):
- "narrator": External narrator describing the scene (character's lips do NOT move)
- "internal": Character's inner thoughts heard as voiceover (character's lips do NOT move)
- "monologue": Character speaking ALOUD to themselves (character's lips MUST move)
- "dialogue": Characters speaking to each other (character's lips MUST move)

Set speechType based on WHO is speaking:
- If a narrator describes the scene → "narrator"
- If we hear a character's thoughts → "internal"
- If a character speaks OUT LOUD alone → "monologue" (include speakingCharacter)
- If characters talk to each other → "dialogue" (include speakingCharacter)

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "id": "{$existingScene['id']}",
  "title": "New scene title",
  "narration": "New narrator text matching the duration",
  "visualDescription": "Detailed visual description for AI image generation, using Story Bible character/location descriptions",
  "visualPrompt": "Concise image generation prompt (50-100 words)",
  "voiceover": {
    "enabled": true,
    "text": "",
    "voiceId": null,
    "status": "pending",
    "speechType": "narrator",
    "speakingCharacter": null
  },
  "duration": {$existingScene['duration']},
  "mood": "cinematic",
  "status": "draft",
  "characters": ["Names of characters appearing in this scene"],
  "location": "Name of location used in this scene"
}
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 4000, // Ensure enough tokens for context-aware regeneration
        ]);

        if (!empty($result['error'])) {
            \Log::error('VideoWizard: Context-aware regeneration failed', ['error' => $result['error']]);
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';
        $json = $this->extractJson($response);
        $scene = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($scene['narration'])) {
            \Log::warning('VideoWizard: Failed to parse context-aware scene', [
                'jsonError' => json_last_error_msg(),
                'responsePreview' => substr($response, 0, 200),
            ]);
            return null;
        }

        // Ensure Ken Burns effect
        $scene['kenBurns'] = $this->generateKenBurnsEffect();

        // Validate against Story Bible
        $validation = $this->contextService->validateAgainstBible($project, ['scenes' => [$scene]]);
        if (!empty($validation['warnings'])) {
            \Log::warning('VideoWizard: Scene has Bible consistency warnings', $validation['warnings']);
        }

        return $scene;
    }

    /**
     * Generate script with maximized context (Phase 3).
     * Uses full Story Bible context for better narrative coherence.
     */
    public function generateScriptWithMaxContext(WizardProject $project, array $options = []): array
    {
        $tier = $options['aiModelTier'] ?? 'economy';

        // Get context stats and recommendation
        $stats = $this->contextService->getContextStats($project, $tier);

        // If complexity suggests a higher tier, log recommendation
        if ($stats['recommendation'] !== $tier) {
            \Log::info('VideoWizard: Context service recommends different tier', [
                'current' => $tier,
                'recommended' => $stats['recommendation'],
                'utilization' => $stats['utilization'] . '%',
            ]);
        }

        // Build full context prompt parts
        $fullContext = $this->contextService->buildFullContextPrompt($project, [], ['aiModelTier' => $tier]);

        // Inject the full context into options for prompt building
        $options['fullContextData'] = $fullContext;
        $options['contextStats'] = $stats;

        // Use standard generation with enhanced context
        return $this->generateScript($project, $options);
    }

    /**
     * Get context utilization information for UI display.
     */
    public function getContextUtilization(WizardProject $project, string $tier = 'economy'): array
    {
        return $this->contextService->getContextStats($project, $tier);
    }

    /**
     * Generate a visual prompt for a scene based on its narration.
     */
    public function generateVisualPromptForScene(string $narration, array $concept, array $options = []): string
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $mood = $options['mood'] ?? 'cinematic';
        $style = $options['style'] ?? '';
        $productionType = $options['productionType'] ?? 'movie';
        $aspectRatio = $options['aspectRatio'] ?? '16:9';
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        $styleContext = !empty($style) ? "STYLE REFERENCE: {$style}" : '';
        $conceptContext = !empty($concept['refinedConcept'])
            ? "OVERALL CONCEPT: {$concept['refinedConcept']}"
            : '';

        $prompt = <<<PROMPT
You are a cinematographer creating a visual prompt for AI image generation.

NARRATION: {$narration}
{$conceptContext}
{$styleContext}
MOOD: {$mood}
PRODUCTION TYPE: {$productionType}
ASPECT RATIO: {$aspectRatio}

Create a detailed visual prompt that:
1. Captures the essence of the narration visually
2. Includes specific details: lighting, colors, composition, camera angle
3. Sets the appropriate mood and atmosphere
4. Is optimized for AI image generation (Stable Diffusion/DALL-E style)

RESPOND WITH ONLY THE PROMPT TEXT (no JSON, no explanation, just the visual description):
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, ['maxResult' => 1]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $visualPrompt = trim($result['data'][0] ?? '');

        // Clean up any markdown or extra formatting
        $visualPrompt = preg_replace('/^```.*?\n/', '', $visualPrompt);
        $visualPrompt = preg_replace('/\n```$/', '', $visualPrompt);
        $visualPrompt = trim($visualPrompt);

        return $visualPrompt;
    }

    /**
     * Generate voiceover text for a scene based on its narration.
     */
    public function generateVoiceoverForScene(string $narration, array $concept, array $options = []): string
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $narrationStyle = $options['narrationStyle'] ?? 'voiceover';
        $tone = $options['tone'] ?? 'engaging';
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        // For simple voiceover, the narration IS the voiceover text
        if ($narrationStyle === 'voiceover' || $narrationStyle === 'narrator') {
            return $narration;
        }

        // For dialogue or special cases, we might need to transform it
        if ($narrationStyle === 'dialogue') {
            $prompt = <<<PROMPT
Convert this narration into natural dialogue between characters.

NARRATION: {$narration}
TONE: {$tone}

Create dialogue that:
1. Conveys the same information as the narration
2. Sounds natural and conversational
3. Uses character names (SPEAKER: dialogue format)

RESPOND WITH ONLY THE DIALOGUE TEXT (no explanation):
PROMPT;

            $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, ['maxResult' => 1]);

            if (!empty($result['error'])) {
                return $narration; // Fallback to original
            }

            return trim($result['data'][0] ?? $narration);
        }

        // For 'none' style, return empty
        if ($narrationStyle === 'none') {
            return '';
        }

        return $narration;
    }

    /**
     * Improve/refine an existing script.
     */
    public function improveScript(array $script, string $instruction, array $options = []): array
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $aiModelTier = $options['aiModelTier'] ?? 'economy';

        $scriptJson = json_encode($script, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
{$scriptJson}

INSTRUCTION: {$instruction}

RESPOND WITH ONLY THE IMPROVED JSON (no markdown, no explanation):
PROMPT;

        $result = $this->callAIWithTier($prompt, $aiModelTier, $teamId, [
            'maxResult' => 1,
            'max_tokens' => 15000, // Ensure enough tokens for improved script
        ]);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        return $this->parseScriptResponse($response);
    }
}
