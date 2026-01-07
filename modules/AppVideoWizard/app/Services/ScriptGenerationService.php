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
        ]);

        $parsedScript = $this->parseScriptResponse($response, $duration);

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
     * Uses PromptService to load from DB, falls back to hardcoded if not available.
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

        $minutes = round($duration / 60, 1);
        $avgSceneDuration = (int) ceil($duration / $sceneCount);

        $toneGuide = $this->promptService->getToneGuide($tone);
        $depthGuide = $this->promptService->getDepthGuide($contentDepth);

        // Try to load prompt from database
        $compiledPrompt = $this->promptService->getCompiledPrompt('script_generation', [
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
            'additionalInstructions' => !empty($additionalInstructions)
                ? "\nADDITIONAL REQUIREMENTS: {$additionalInstructions}\n"
                : '',
        ]);

        if ($compiledPrompt) {
            return $compiledPrompt;
        }

        // Fallback to hardcoded prompt if DB prompt not available
        \Log::info('VideoWizard: Using fallback hardcoded prompt for script_generation');

        $prompt = <<<PROMPT
You are an expert video scriptwriter creating a {$minutes}-minute video script.

TOPIC: {$topic}
TONE: {$tone} - {$toneGuide}
CONTENT DEPTH: {$contentDepth} - {$depthGuide}
TOTAL DURATION: {$duration} seconds ({$minutes} minutes)
TARGET WORD COUNT: {$targetWords} words
NUMBER OF SCENES: {$sceneCount}
WORDS PER SCENE: ~{$wordsPerScene}
SCENE DURATION: ~{$avgSceneDuration} seconds each

PROMPT;

        if (!empty($additionalInstructions)) {
            $prompt .= "\nADDITIONAL REQUIREMENTS: {$additionalInstructions}\n\n";
        }

        $prompt .= <<<PROMPT

SCRIPT STRUCTURE:
1. HOOK (first 5 seconds) - Grab attention immediately with a bold statement or question
2. INTRODUCTION (10-15 seconds) - Set expectations for what viewers will learn
3. MAIN CONTENT ({$sceneCount} scenes) - Deliver value with retention hooks every 30-45 seconds
4. CALL TO ACTION (5-10 seconds) - Clear next step for viewer

RESPOND WITH ONLY THIS JSON (no markdown code blocks, no explanation, just pure JSON):
{
  "title": "SEO-optimized title (max 60 chars)",
  "hook": "Attention-grabbing opening (5-10 words)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title",
      "narration": "What narrator says ({$wordsPerScene} words)",
      "visualDescription": "Detailed visual for AI image generation (describe setting, mood, colors, lighting, composition)",
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

CRITICAL REQUIREMENTS:
- Generate EXACTLY {$sceneCount} scenes
- Each scene narration MUST be approximately {$wordsPerScene} words
- Total narration should equal approximately {$targetWords} words
- Visual descriptions must be detailed enough for AI image generation
- Include varied Ken Burns movements (scale, position changes)
- Scene durations should add up to approximately {$duration} seconds
- NO markdown formatting - output raw JSON only
PROMPT;

        return $prompt;
    }

    /**
     * Parse the AI response into a structured script.
     */
    protected function parseScriptResponse(string $response, int $targetDuration = 60): array
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
     * Uses regex to extract key fields and rebuild a valid structure.
     */
    protected function rebuildScriptFromResponse(string $response): ?array
    {
        $scenes = [];

        // Try to extract title
        $title = 'Generated Script';
        if (preg_match('/"title"\s*:\s*"([^"]+)"/', $response, $m)) {
            $title = $m[1];
        }

        // Try to extract hook
        $hook = '';
        if (preg_match('/"hook"\s*:\s*"([^"]+)"/', $response, $m)) {
            $hook = $m[1];
        }

        // Try to extract CTA
        $cta = '';
        if (preg_match('/"cta"\s*:\s*"([^"]+)"/', $response, $m)) {
            $cta = $m[1];
        }

        // Try to extract scenes using multiple approaches
        // Approach 1: Find scene objects
        if (preg_match_all('/"id"\s*:\s*"(scene-\d+)"[^}]*"narration"\s*:\s*"([^"]+)"[^}]*"visualDescription"\s*:\s*"([^"]+)"/s', $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $scenes[] = [
                    'id' => $match[1],
                    'title' => 'Scene ' . ($index + 1),
                    'narration' => $match[2],
                    'visualDescription' => $match[3],
                    'duration' => 15,
                ];
            }
        }

        // Approach 2: Try different field order
        if (empty($scenes) && preg_match_all('/"narration"\s*:\s*"([^"]+)"[^}]*"visualDescription"\s*:\s*"([^"]+)"/s', $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $scenes[] = [
                    'id' => 'scene-' . ($index + 1),
                    'title' => 'Scene ' . ($index + 1),
                    'narration' => $match[1],
                    'visualDescription' => $match[2],
                    'duration' => 15,
                ];
            }
        }

        // Approach 3: Just find narration fields
        if (empty($scenes) && preg_match_all('/"narration"\s*:\s*"([^"]+)"/', $response, $matches)) {
            foreach ($matches[1] as $index => $narration) {
                $scenes[] = [
                    'id' => 'scene-' . ($index + 1),
                    'title' => 'Scene ' . ($index + 1),
                    'narration' => $narration,
                    'visualDescription' => $narration,
                    'duration' => 15,
                ];
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
