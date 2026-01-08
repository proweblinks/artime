<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\VwGenerationLog;

class ScriptGenerationService
{
    /**
     * PromptService instance for loading prompts from DB.
     */
    protected PromptService $promptService;

    /**
     * Constructor.
     */
    public function __construct(?PromptService $promptService = null)
    {
        $this->promptService = $promptService ?? new PromptService();
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

        // Narrative Structure Intelligence options
        $narrativePreset = $options['narrativePreset'] ?? null;
        $storyArc = $options['storyArc'] ?? null;
        $tensionCurve = $options['tensionCurve'] ?? null;
        $emotionalJourney = $options['emotionalJourney'] ?? null;

        // Calculate script parameters
        $params = $this->calculateScriptParameters($duration, $contentDepth);

        \Log::info('VideoWizard: Generating script', [
            'teamId' => $teamId,
            'topic' => substr($topic, 0, 100),
            'duration' => $duration,
            'sceneCount' => $params['sceneCount'],
            'targetWords' => $params['targetWords'],
        ]);

        // For videos over 5 minutes, use chunked generation
        if ($duration > 300) {
            return $this->generateLongFormScript($topic, $tone, $duration, $params, $concept, $productionType, $additionalInstructions, $teamId);
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
        ];

        $prompt = $this->buildScriptPrompt($promptParams);
        $startTime = microtime(true);

        $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);
        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        if (!empty($result['error'])) {
            \Log::error('VideoWizard: AI error', ['error' => $result['error']]);

            // Log the failure
            $this->logGeneration('script_generation', $promptParams, [], 'failed', $result['error'], null, $durationMs, $project->id);

            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        if (empty($response)) {
            \Log::error('VideoWizard: Empty AI response', ['result' => $result]);

            // Log the failure
            $this->logGeneration('script_generation', $promptParams, [], 'failed', 'Empty AI response', null, $durationMs, $project->id);

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
            \Log::warning('VideoWizard: Scene count below expected, interpolating', [
                'expected' => $expectedSceneCount,
                'actual' => $actualSceneCount,
                'deficit' => $expectedSceneCount - $actualSceneCount,
            ]);

            // Interpolate additional scenes if we're significantly below target
            $parsedScript = $this->interpolateScenes($parsedScript, $expectedSceneCount, $duration);
        }

        // Log successful generation
        $this->logGeneration('script_generation', $promptParams, $parsedScript, 'success', null, null, $durationMs, $project->id);

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
        int $teamId
    ): array {
        \Log::info('VideoWizard: Using multi-pass generation for long-form video', [
            'duration' => $duration,
            'minutes' => round($duration / 60, 1),
        ]);

        // Step 1: Generate outline/structure
        $outlinePrompt = $this->buildOutlinePrompt($topic, $tone, $duration, $params, $concept, $additionalInstructions);
        $outlineResult = AI::process($outlinePrompt, 'text', ['maxResult' => 1], $teamId);

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
                $teamId
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
        int $teamId
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

        $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

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

        // === LAYER 14: OUTPUT FORMAT ===
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
      "narration": "Narrator text (~{$wordsPerScene} words) - emotionally resonant, matches tension curve",
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

        // If initial parse failed, try more aggressive repair
        if ($jsonError !== JSON_ERROR_NONE) {
            \Log::warning('VideoWizard: Initial JSON parse failed, trying repair', [
                'error' => $jsonErrorMsg,
            ]);

            // Try rebuilding JSON from extracted fields
            $script = $this->rebuildScriptFromResponse($originalResponse);

            if ($script !== null) {
                \Log::info('VideoWizard: JSON rebuilt successfully from response');
            } else {
                \Log::error('VideoWizard: JSON parse error - could not repair', [
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

        // Remove trailing commas before } or ]
        $json = preg_replace('/,(\s*[}\]])/', '$1', $json);

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
            'voiceover' => $this->sanitizeVoiceover($scene['voiceover'] ?? []),

            // Ken Burns effect
            'kenBurns' => $this->sanitizeKenBurns($scene['kenBurns'] ?? null),

            // Image data (preserve as-is if exists)
            'image' => $scene['image'] ?? null,
            'imageUrl' => $this->ensureString($scene['imageUrl'] ?? null, ''),
        ];
    }

    /**
     * Sanitize voiceover structure.
     */
    protected function sanitizeVoiceover($voiceover): array
    {
        if (!is_array($voiceover)) {
            $voiceover = [];
        }

        return [
            'enabled' => (bool)($voiceover['enabled'] ?? true),
            'text' => $this->ensureString($voiceover['text'] ?? null, ''),
            'voiceId' => $voiceover['voiceId'] ?? null,
            'status' => $this->ensureString($voiceover['status'] ?? null, 'pending'),
        ];
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
     * Interpolate scenes to reach target scene count.
     * When AI returns fewer scenes than expected, this method adds scenes
     * by splitting longer scenes or creating transitional scenes.
     */
    protected function interpolateScenes(array $script, int $targetSceneCount, int $targetDuration): array
    {
        $scenes = $script['scenes'] ?? [];
        $currentCount = count($scenes);

        if ($currentCount >= $targetSceneCount || $currentCount === 0) {
            return $script;
        }

        $scenesToAdd = $targetSceneCount - $currentCount;
        $avgSceneDuration = (int) ceil($targetDuration / $targetSceneCount);

        \Log::info('VideoWizard: Interpolating scenes', [
            'currentCount' => $currentCount,
            'targetCount' => $targetSceneCount,
            'scenesToAdd' => $scenesToAdd,
            'avgDuration' => $avgSceneDuration,
        ]);

        // Strategy: Split the longest scenes to create more
        // Sort scenes by duration (descending) to find candidates for splitting
        $scenesWithIndex = [];
        foreach ($scenes as $index => $scene) {
            $scenesWithIndex[] = [
                'index' => $index,
                'scene' => $scene,
                'duration' => $scene['duration'] ?? $avgSceneDuration,
            ];
        }

        // Sort by duration descending
        usort($scenesWithIndex, fn($a, $b) => $b['duration'] <=> $a['duration']);

        // Split scenes that are longer than average
        $newScenes = [];
        $splitCount = 0;

        foreach ($scenes as $index => $scene) {
            $sceneDuration = $scene['duration'] ?? $avgSceneDuration;

            // Check if this scene should be split (longer than 1.5x average and we still need more scenes)
            if ($splitCount < $scenesToAdd && $sceneDuration > $avgSceneDuration * 1.5) {
                // Split this scene into two
                $halfDuration = (int) ceil($sceneDuration / 2);
                $narration = $scene['narration'] ?? '';
                $words = explode(' ', $narration);
                $midPoint = (int) ceil(count($words) / 2);

                // First half
                $scene1 = $scene;
                $scene1['id'] = 'scene-' . (count($newScenes) + 1);
                $scene1['narration'] = implode(' ', array_slice($words, 0, $midPoint));
                $scene1['duration'] = $halfDuration;
                $scene1['kenBurns'] = $this->generateKenBurnsEffect();
                $newScenes[] = $scene1;

                // Second half - create as continuation
                $scene2 = $scene;
                $scene2['id'] = 'scene-' . (count($newScenes) + 1);
                $scene2['title'] = ($scene['title'] ?? 'Scene') . ' (continued)';
                $scene2['narration'] = implode(' ', array_slice($words, $midPoint));
                $scene2['duration'] = $sceneDuration - $halfDuration;
                $scene2['kenBurns'] = $this->generateKenBurnsEffect();
                $newScenes[] = $scene2;

                $splitCount++;

                \Log::debug('VideoWizard: Split scene', [
                    'originalIndex' => $index,
                    'originalDuration' => $sceneDuration,
                    'newDurations' => [$halfDuration, $sceneDuration - $halfDuration],
                ]);
            } else {
                // Keep scene as-is but update ID
                $scene['id'] = 'scene-' . (count($newScenes) + 1);
                $newScenes[] = $scene;
            }
        }

        // If we still need more scenes after splitting, add transition scenes
        while (count($newScenes) < $targetSceneCount) {
            $insertIndex = count($newScenes);
            $prevScene = $newScenes[$insertIndex - 1] ?? null;

            $transitionScene = [
                'id' => 'scene-' . ($insertIndex + 1),
                'title' => 'Transition',
                'narration' => $this->generateTransitionNarration($prevScene),
                'visualDescription' => $this->generateTransitionVisual($prevScene),
                'duration' => $avgSceneDuration,
                'mood' => $prevScene['mood'] ?? 'contemplative',
                'transition' => 'dissolve',
                'kenBurns' => $this->generateKenBurnsEffect(),
            ];

            $newScenes[] = $transitionScene;

            \Log::debug('VideoWizard: Added transition scene', [
                'index' => $insertIndex,
            ]);
        }

        // Re-index all scenes
        foreach ($newScenes as $index => &$scene) {
            $scene['id'] = 'scene-' . ($index + 1);
        }

        $script['scenes'] = $newScenes;

        \Log::info('VideoWizard: Interpolation complete', [
            'finalSceneCount' => count($newScenes),
            'targetCount' => $targetSceneCount,
        ]);

        return $script;
    }

    /**
     * Generate transition narration based on previous scene.
     */
    protected function generateTransitionNarration(?array $prevScene): string
    {
        if (!$prevScene) {
            return 'Let us explore this further...';
        }

        $transitions = [
            'But there is more to this story...',
            'And the journey continues...',
            'This brings us to an important point...',
            'Now, let us consider another aspect...',
            'Taking a moment to reflect on this...',
            'The significance of this cannot be understated...',
            'Building on what we have learned...',
            'This naturally leads us to...',
        ];

        return $transitions[array_rand($transitions)];
    }

    /**
     * Generate transition visual description based on previous scene.
     */
    protected function generateTransitionVisual(?array $prevScene): string
    {
        if (!$prevScene) {
            return 'Wide establishing shot, cinematic atmosphere, soft lighting';
        }

        $baseVisual = $prevScene['visualDescription'] ?? '';

        $transitions = [
            'Soft focus transition, dreamy atmosphere, ' . substr($baseVisual, 0, 50),
            'Wide angle perspective, establishing context, cinematic lighting',
            'Medium shot with gentle camera movement, contemplative mood',
            'Atmospheric wide shot, soft bokeh in background, warm tones',
        ];

        return $transitions[array_rand($transitions)];
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
    "status": "pending"
  },
  "duration": {$existingScene['duration']},
  "mood": "cinematic",
  "status": "draft"
}
PROMPT;

        $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

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
     * Generate a visual prompt for a scene based on its narration.
     */
    public function generateVisualPromptForScene(string $narration, array $concept, array $options = []): string
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);
        $mood = $options['mood'] ?? 'cinematic';
        $style = $options['style'] ?? '';
        $productionType = $options['productionType'] ?? 'movie';
        $aspectRatio = $options['aspectRatio'] ?? '16:9';

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

        $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

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

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

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

        $scriptJson = json_encode($script, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
{$scriptJson}

INSTRUCTION: {$instruction}

RESPOND WITH ONLY THE IMPROVED JSON (no markdown, no explanation):
PROMPT;

        $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        return $this->parseScriptResponse($response);
    }
}
