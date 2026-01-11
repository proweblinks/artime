<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Models\VwShotType;

/**
 * ShotIntelligenceService - AI-driven shot decomposition for scenes.
 *
 * Analyzes scene content (narration, visual description, mood) and determines:
 * - Optimal number of shots based on pacing and content
 * - Per-shot duration based on action/dialogue density
 * - Shot type sequence for professional cinematography
 * - Which shots need lip-sync (Multitalk) vs standard animation (MiniMax)
 */
class ShotIntelligenceService
{
    /**
     * Available shot types for AI to choose from.
     */
    protected array $shotTypes;

    /**
     * AI provider service for making LLM calls.
     */
    protected $aiService;

    public function __construct()
    {
        $this->shotTypes = VwShotType::getAllActive();
    }

    /**
     * Analyze a scene and determine optimal shot breakdown.
     *
     * @param array $scene Scene data with narration, visualDescription, duration, etc.
     * @param array $context Additional context (genre, pacing, characters, etc.)
     * @return array Shot breakdown with shots array and metadata
     */
    public function analyzeScene(array $scene, array $context = []): array
    {
        try {
            // Get settings
            $minShots = (int) VwSetting::getValue('shot_min_per_scene', 1);
            $maxShots = (int) VwSetting::getValue('shot_max_per_scene', 20);
            $aiPromptTemplate = VwSetting::getValue('shot_ai_prompt', $this->getDefaultPrompt());

            // Build the analysis prompt
            $prompt = $this->buildAnalysisPrompt($scene, $context, $aiPromptTemplate);

            // Call AI service
            $aiResponse = $this->callAI($prompt, $context);

            if (!$aiResponse['success']) {
                Log::warning('ShotIntelligenceService: AI analysis failed, using fallback', [
                    'error' => $aiResponse['error'] ?? 'Unknown error',
                ]);
                return $this->getFallbackAnalysis($scene, $context, $minShots, $maxShots);
            }

            // Parse and validate AI response
            $analysis = $this->parseAIResponse($aiResponse['response'], $scene, $minShots, $maxShots);

            Log::info('ShotIntelligenceService: Scene analyzed successfully', [
                'scene_id' => $scene['id'] ?? 'unknown',
                'shot_count' => $analysis['shotCount'],
                'total_duration' => $analysis['totalDuration'],
            ]);

            return $analysis;

        } catch (\Throwable $e) {
            Log::error('ShotIntelligenceService: Exception during analysis', [
                'error' => $e->getMessage(),
                'scene_id' => $scene['id'] ?? 'unknown',
            ]);

            return $this->getFallbackAnalysis($scene, $context,
                (int) VwSetting::getValue('shot_min_per_scene', 1),
                (int) VwSetting::getValue('shot_max_per_scene', 20)
            );
        }
    }

    /**
     * Build the AI analysis prompt from template and scene data.
     */
    protected function buildAnalysisPrompt(array $scene, array $context, string $template): string
    {
        $variables = [
            'scene_description' => $scene['visualDescription'] ?? $scene['visual'] ?? '',
            'narration' => $scene['narration'] ?? '',
            'duration' => $scene['duration'] ?? 30,
            'mood' => $scene['mood'] ?? $context['mood'] ?? 'neutral',
            'genre' => $context['genre'] ?? 'general',
            'pacing' => $context['pacing'] ?? 'balanced',
            'has_dialogue' => !empty($scene['dialogue']) || $this->detectDialogue($scene['narration'] ?? '') ? 'yes' : 'no',
            'characters' => implode(', ', $context['characters'] ?? []),
            'available_shot_types' => $this->getAvailableShotTypesForPrompt(),
        ];

        // Replace template variables
        $prompt = $template;
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Call AI service to analyze the scene.
     */
    protected function callAI(string $prompt, array $context): array
    {
        try {
            // Use the AI model tier from context or default to economy
            $modelTier = $context['aiModelTier'] ?? 'economy';

            // Get AI service based on tier
            $aiConfig = $this->getAIConfig($modelTier);

            // Make the API call
            $response = $this->makeAIRequest($prompt, $aiConfig);

            return [
                'success' => true,
                'response' => $response,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AI configuration based on model tier.
     */
    protected function getAIConfig(string $tier): array
    {
        $configs = [
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

        return $configs[$tier] ?? $configs['economy'];
    }

    /**
     * Make the actual AI API request.
     */
    protected function makeAIRequest(string $prompt, array $config): string
    {
        $provider = $config['provider'];
        $model = $config['model'];

        // Use existing AI infrastructure
        if ($provider === 'openai') {
            return $this->callOpenAI($prompt, $model);
        } elseif ($provider === 'grok') {
            return $this->callGrok($prompt, $model);
        } elseif ($provider === 'gemini') {
            return $this->callGemini($prompt, $model);
        }

        throw new \Exception("Unsupported AI provider: {$provider}");
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAI(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_openai_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional film director and cinematographer. Analyze scenes and provide optimal shot breakdowns in JSON format only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Call Grok API (xAI).
     */
    protected function callGrok(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_grok_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('Grok API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.x.ai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional film director and cinematographer. Analyze scenes and provide optimal shot breakdowns in JSON format only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Grok API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Call Gemini API.
     */
    protected function callGemini(string $prompt, string $model): string
    {
        $apiKey = get_option('ai_gemini_api_key', '');
        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured');
        }

        $response = \Illuminate\Support\Facades\Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2000,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    /**
     * Parse AI response and validate shot breakdown.
     */
    protected function parseAIResponse(string $response, array $scene, int $minShots, int $maxShots): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $jsonStr = $response;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $jsonStr = trim($matches[1]);
        }

        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::warning('ShotIntelligenceService: Failed to parse AI response as JSON', [
                'response' => substr($response, 0, 500),
            ]);
            return $this->getFallbackAnalysis($scene, [], $minShots, $maxShots);
        }

        // Validate and normalize the response
        $shotCount = isset($data['shotCount']) ? (int) $data['shotCount'] : count($data['shots'] ?? []);
        $shotCount = max($minShots, min($maxShots, $shotCount));

        $shots = [];
        $totalDuration = 0;

        foreach (($data['shots'] ?? []) as $index => $shotData) {
            if ($index >= $shotCount) break;

            $shot = $this->normalizeShot($shotData, $index, $scene);
            $shots[] = $shot;
            $totalDuration += $shot['duration'];
        }

        // If AI returned fewer shots than expected, fill with defaults
        while (count($shots) < $shotCount) {
            $index = count($shots);
            $shots[] = $this->createDefaultShot($index, $shotCount, $scene);
            $totalDuration += $shots[count($shots) - 1]['duration'];
        }

        return [
            'success' => true,
            'shotCount' => count($shots),
            'shots' => $shots,
            'totalDuration' => $totalDuration,
            'reasoning' => $data['reasoning'] ?? 'AI-optimized shot breakdown',
            'source' => 'ai',
        ];
    }

    /**
     * Normalize a shot from AI response.
     */
    protected function normalizeShot(array $shotData, int $index, array $scene): array
    {
        // Get available durations
        $needsLipSync = $shotData['needsLipSync'] ?? $shotData['needs_lip_sync'] ?? false;
        $model = $needsLipSync ? 'multitalk' : 'minimax';

        $availableDurations = $this->getAvailableDurations($model);
        $duration = $shotData['duration'] ?? 6;

        // Snap to nearest available duration
        $duration = $this->snapToAvailableDuration($duration, $availableDurations);

        // Get shot type
        $type = $shotData['type'] ?? $shotData['shot_type'] ?? 'medium';
        $type = $this->normalizeTypeName($type);

        // Get shot type info if available
        $shotTypeInfo = $this->shotTypes[$type] ?? null;

        return [
            'type' => $type,
            'duration' => $duration,
            'purpose' => $shotData['purpose'] ?? 'narrative',
            'cameraMovement' => $shotData['cameraMovement'] ?? $shotData['camera_movement'] ?? $this->getDefaultCameraMovement($type),
            'needsLipSync' => $needsLipSync,
            'recommendedModel' => $model,
            'description' => $shotData['description'] ?? $shotTypeInfo['description'] ?? '',
            'lens' => $shotTypeInfo['defaultLens'] ?? 'standard 50mm',
            'aiRecommended' => true,
        ];
    }

    /**
     * Snap duration to nearest available value.
     */
    protected function snapToAvailableDuration(int $duration, array $available): int
    {
        if (in_array($duration, $available)) {
            return $duration;
        }

        // Find nearest available duration
        $nearest = $available[0];
        $minDiff = abs($duration - $nearest);

        foreach ($available as $avail) {
            $diff = abs($duration - $avail);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $avail;
            }
        }

        return $nearest;
    }

    /**
     * Get available durations for a model.
     */
    protected function getAvailableDurations(string $model): array
    {
        $settingSlug = $model === 'multitalk'
            ? 'animation_multitalk_durations'
            : 'animation_minimax_durations';

        $defaults = $model === 'multitalk' ? [5, 10, 15, 20] : [5, 6, 10];
        $durations = VwSetting::getValue($settingSlug, $defaults);

        if (is_string($durations)) {
            $durations = json_decode($durations, true) ?? $defaults;
        }

        return array_map('intval', (array) $durations);
    }

    /**
     * Normalize shot type name to slug format.
     */
    protected function normalizeTypeName(string $type): string
    {
        // Convert "Close Up" to "close-up", "Medium Shot" to "medium", etc.
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+shot$/i', '', $type);
        $type = str_replace(' ', '-', $type);

        // Map common variations
        $mappings = [
            'closeup' => 'close-up',
            'close' => 'close-up',
            'wide' => 'wide',
            'medium' => 'medium',
            'establishing' => 'establishing',
            'extreme-wide' => 'extreme-wide',
            'extreme-close-up' => 'extreme-close-up',
            'over-the-shoulder' => 'over-shoulder',
            'over-shoulder' => 'over-shoulder',
            'two-shot' => 'two-shot',
            'reaction' => 'reaction',
            'insert' => 'insert',
            'pov' => 'pov',
            'dutch' => 'dutch-angle',
            'dutch-angle' => 'dutch-angle',
            'low-angle' => 'low-angle',
            'high-angle' => 'high-angle',
            'birds-eye' => 'birds-eye',
            'worms-eye' => 'worms-eye',
        ];

        return $mappings[$type] ?? $type;
    }

    /**
     * Get default camera movement for shot type.
     */
    protected function getDefaultCameraMovement(string $type): string
    {
        $movements = [
            'establishing' => 'slow pan',
            'wide' => 'static or slow pan',
            'medium' => 'subtle movement',
            'close-up' => 'slight push in',
            'extreme-close-up' => 'static',
            'reaction' => 'static',
            'over-shoulder' => 'subtle drift',
            'two-shot' => 'gentle track',
            'pov' => 'handheld movement',
            'dutch-angle' => 'slow rotation',
        ];

        return $movements[$type] ?? 'subtle movement';
    }

    /**
     * Create a default shot for filling gaps.
     */
    protected function createDefaultShot(int $index, int $totalShots, array $scene): array
    {
        // Determine shot type based on position in sequence
        $position = $index / max(1, $totalShots - 1);

        if ($index === 0) {
            $type = 'establishing';
        } elseif ($position < 0.3) {
            $type = 'wide';
        } elseif ($position < 0.7) {
            $type = 'medium';
        } else {
            $type = 'close-up';
        }

        $defaultDuration = (int) VwSetting::getValue('duration_shot_default', 6);

        return [
            'type' => $type,
            'duration' => $defaultDuration,
            'purpose' => 'narrative',
            'cameraMovement' => $this->getDefaultCameraMovement($type),
            'needsLipSync' => false,
            'recommendedModel' => 'minimax',
            'description' => '',
            'lens' => 'standard 50mm',
            'aiRecommended' => false,
        ];
    }

    /**
     * Get fallback analysis when AI fails.
     */
    protected function getFallbackAnalysis(array $scene, array $context, int $minShots, int $maxShots): array
    {
        $sceneDuration = $scene['duration'] ?? 30;
        $clipDuration = (int) VwSetting::getValue('duration_shot_default', 6);

        // Calculate shot count based on scene duration
        $shotCount = max($minShots, min($maxShots, (int) ceil($sceneDuration / $clipDuration)));

        $shots = [];
        $totalDuration = 0;

        for ($i = 0; $i < $shotCount; $i++) {
            $shot = $this->createDefaultShot($i, $shotCount, $scene);
            $shots[] = $shot;
            $totalDuration += $shot['duration'];
        }

        return [
            'success' => true,
            'shotCount' => $shotCount,
            'shots' => $shots,
            'totalDuration' => $totalDuration,
            'reasoning' => 'Standard shot breakdown (AI unavailable)',
            'source' => 'fallback',
        ];
    }

    /**
     * Detect if narration contains dialogue (speaking characters).
     */
    protected function detectDialogue(string $narration): bool
    {
        // Check for dialogue indicators
        $dialoguePatterns = [
            '/["\'](.*?)["\']/', // Quoted text
            '/\bsays?\b/i',
            '/\btells?\b/i',
            '/\basks?\b/i',
            '/\breplies?\b/i',
            '/\bexclaims?\b/i',
            '/\bwhispers?\b/i',
            '/\bshouts?\b/i',
            '/\bspeaks?\b/i',
            '/\bdialogue\b/i',
            '/\bconversation\b/i',
        ];

        foreach ($dialoguePatterns as $pattern) {
            if (preg_match($pattern, $narration)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available shot types formatted for the AI prompt.
     */
    protected function getAvailableShotTypesForPrompt(): string
    {
        $types = [];
        foreach ($this->shotTypes as $slug => $info) {
            $types[] = $slug . ' (' . ($info['description'] ?? $info['name'] ?? $slug) . ')';
        }

        return implode(', ', array_slice($types, 0, 20)); // Limit to prevent prompt bloat
    }

    /**
     * Get the default AI prompt template.
     */
    protected function getDefaultPrompt(): string
    {
        return 'Analyze this scene and determine the optimal cinematic shot breakdown.

SCENE: {{scene_description}}
NARRATION: {{narration}}
DURATION: {{duration}} seconds
MOOD: {{mood}}
GENRE: {{genre}}
PACING: {{pacing}}
HAS DIALOGUE: {{has_dialogue}}

Consider:
1. Pacing - faster cuts for action, longer shots for emotional moments
2. Dialogue - shots with speaking characters may need lip-sync (needsLipSync: true)
3. Visual variety - mix shot types for professional look
4. Story beats - establish, develop, climax within the scene

Available shot types: {{available_shot_types}}

Return ONLY valid JSON (no markdown, no explanation):
{
  "shotCount": number,
  "reasoning": "brief explanation of shot choices",
  "shots": [
    {
      "type": "shot_type_slug",
      "duration": seconds (5, 6, 10 for standard, 5-20 for dialogue),
      "purpose": "why this shot",
      "cameraMovement": "movement description",
      "needsLipSync": boolean
    }
  ]
}';
    }

    /**
     * Check if AI Shot Intelligence is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) VwSetting::getValue('shot_intelligence_enabled', true);
    }
}
