<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\GrokService;
use App\Services\AIService;

/**
 * LLMExpansionService
 *
 * AI-powered prompt expansion service using LLM with vocabulary constraints.
 * Implements a three-tier fallback cascade: Grok -> Gemini -> Template
 *
 * Uses meta-prompting with vocabulary constraints instead of few-shot examples
 * for consistent Hollywood-quality output. The system prompt includes all
 * vocabulary from existing services (lens psychology, lighting ratios,
 * emotion manifestations, spatial dynamics) to constrain LLM output.
 *
 * Three-layer prompt caching strategy:
 * - Layer 1: System prompt (stable vocabulary) - cached by LLM provider
 * - Layer 2: Session context (project settings) - semi-stable
 * - Layer 3: Shot data (dynamic) - never cached
 *
 * @see ComplexityDetectorService for routing decision logic
 * @see CinematographyVocabulary for lens/lighting vocabulary
 * @see CharacterPsychologyService for emotion manifestations
 * @see CharacterDynamicsService for spatial dynamics vocabulary
 */
class LLMExpansionService
{
    /**
     * Cache TTL for expanded prompts (24 hours).
     */
    protected const CACHE_TTL_HOURS = 24;

    /**
     * Maximum words for expanded prompts.
     */
    protected const MAX_OUTPUT_WORDS = 200;

    /**
     * Required semantic markers in output.
     */
    protected const SEMANTIC_MARKERS = [
        '[LENS:',
        '[LIGHTING:',
        '[FRAME:',
        '[SUBJECT:',
        '[DYNAMICS:',
        '[ENVIRONMENT:',
    ];

    /**
     * Minimum required markers for valid output.
     */
    protected const MIN_REQUIRED_MARKERS = 2;

    /**
     * Grok model for expansion (cost-effective).
     */
    protected const GROK_MODEL = 'grok-4-fast';

    /**
     * Grok temperature for controlled creativity.
     */
    protected const GROK_TEMPERATURE = 0.4;

    /**
     * Max tokens for LLM response.
     */
    protected const MAX_TOKENS = 400;

    protected ComplexityDetectorService $complexityDetector;
    protected CinematographyVocabulary $cinematographyVocabulary;
    protected CharacterPsychologyService $characterPsychology;
    protected CharacterDynamicsService $characterDynamics;
    protected PromptExpanderService $promptExpander;
    protected ?GrokService $grokService = null;
    protected ?AIService $aiService = null;

    public function __construct(
        ?ComplexityDetectorService $complexityDetector = null,
        ?CinematographyVocabulary $cinematographyVocabulary = null,
        ?CharacterPsychologyService $characterPsychology = null,
        ?CharacterDynamicsService $characterDynamics = null,
        ?PromptExpanderService $promptExpander = null,
        ?GrokService $grokService = null,
        ?AIService $aiService = null
    ) {
        $this->complexityDetector = $complexityDetector ?? new ComplexityDetectorService();
        $this->cinematographyVocabulary = $cinematographyVocabulary ?? new CinematographyVocabulary();
        $this->characterPsychology = $characterPsychology ?? new CharacterPsychologyService();
        $this->characterDynamics = $characterDynamics ?? new CharacterDynamicsService();
        $this->promptExpander = $promptExpander ?? new PromptExpanderService();
        $this->grokService = $grokService;
        $this->aiService = $aiService;
    }

    /**
     * Check if shot data is complex enough to warrant LLM expansion.
     *
     * This is a convenience method for external services to check complexity
     * without performing expansion.
     *
     * @param array $shotData Shot data with characters, shot_type, emotion, etc.
     * @return bool True if shot is complex
     */
    public function isComplex(array $shotData): bool
    {
        return $this->complexityDetector->isComplex($shotData);
    }

    /**
     * Main entry point - expand shot data based on complexity.
     *
     * Routes complex shots to LLM expansion, simple shots to template expansion.
     *
     * @param array $shotData Shot data with characters, shot_type, emotion, etc.
     * @return array{expanded_prompt: string, method: string, provider: string, complexity: array}
     */
    public function expand(array $shotData): array
    {
        // Calculate complexity to determine routing
        $complexity = $this->complexityDetector->calculateComplexity($shotData);

        Log::debug('LLMExpansionService: Complexity calculated', [
            'is_complex' => $complexity['is_complex'],
            'total_score' => $complexity['total_score'],
            'reasons' => $complexity['complexity_reasons'],
        ]);

        // Route based on complexity
        if (!$complexity['is_complex']) {
            // Simple shot - use template expansion
            return $this->fallbackToTemplate($shotData, $complexity);
        }

        // Complex shot - attempt LLM expansion with fallback cascade
        return $this->expandComplex($shotData, $complexity);
    }

    /**
     * Expand with caching support.
     *
     * @param array $shotData Shot data
     * @return array Expansion result (from cache or fresh)
     */
    public function expandWithCache(array $shotData): array
    {
        // Generate cache key from shot data
        $cacheKey = 'llm_expansion:' . md5(json_encode($shotData));

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($shotData) {
            return $this->expand($shotData);
        });
    }

    /**
     * Expand complex shot using LLM fallback cascade.
     *
     * @param array $shotData Shot data
     * @param array $complexity Complexity analysis result
     * @return array Expansion result
     */
    protected function expandComplex(array $shotData, array $complexity): array
    {
        // Try Grok first (primary provider)
        $result = $this->expandWithGrok($shotData);
        if ($result !== null) {
            return $this->postProcess($result, $shotData, $complexity);
        }

        // Fallback to Gemini
        $result = $this->expandWithGemini($shotData);
        if ($result !== null) {
            return $this->postProcess($result, $shotData, $complexity);
        }

        // Final fallback to template
        Log::warning('LLMExpansionService: All LLM providers failed, falling back to template');
        return $this->fallbackToTemplate($shotData, $complexity);
    }

    /**
     * Attempt expansion with Grok.
     *
     * @param array $shotData Shot data
     * @return array|null Result or null on failure
     */
    protected function expandWithGrok(array $shotData): ?array
    {
        try {
            $this->grokService = $this->grokService ?? app(GrokService::class);

            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($shotData);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];

            $response = $this->grokService->generateText(
                $messages,
                self::MAX_TOKENS,
                null,
                'text',
                self::GROK_MODEL,
                ['temperature' => self::GROK_TEMPERATURE]
            );

            if (!empty($response['error'])) {
                Log::warning('LLMExpansionService: Grok returned error', [
                    'error' => $response['error'],
                ]);
                return null;
            }

            $content = $response['data'][0] ?? '';
            if (empty(trim($content))) {
                Log::warning('LLMExpansionService: Grok returned empty content');
                return null;
            }

            Log::debug('LLMExpansionService: Grok expansion successful', [
                'tokens_used' => $response['totalTokens'] ?? 0,
            ]);

            return [
                'expanded_prompt' => trim($content),
                'method' => 'llm',
                'provider' => 'grok',
            ];

        } catch (\Throwable $e) {
            Log::error('LLMExpansionService: Grok expansion failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Attempt expansion with Gemini via AIService.
     *
     * @param array $shotData Shot data
     * @return array|null Result or null on failure
     */
    protected function expandWithGemini(array $shotData): ?array
    {
        try {
            $this->aiService = $this->aiService ?? app(AIService::class);

            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($shotData);

            // Format as messages for Gemini
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];

            $response = $this->aiService->processWithOverride(
                $messages,
                'gemini',
                'gemini-2.5-flash',
                'text',
                [
                    'temperature' => self::GROK_TEMPERATURE,
                    'max_tokens' => self::MAX_TOKENS,
                ]
            );

            if (!empty($response['error'])) {
                Log::warning('LLMExpansionService: Gemini returned error', [
                    'error' => $response['error'],
                ]);
                return null;
            }

            $content = $response['data'][0] ?? '';
            if (empty(trim($content))) {
                Log::warning('LLMExpansionService: Gemini returned empty content');
                return null;
            }

            Log::debug('LLMExpansionService: Gemini expansion successful', [
                'tokens_used' => $response['totalTokens'] ?? 0,
            ]);

            return [
                'expanded_prompt' => trim($content),
                'method' => 'llm',
                'provider' => 'gemini',
            ];

        } catch (\Throwable $e) {
            Log::error('LLMExpansionService: Gemini expansion failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fallback to template-based expansion.
     *
     * @param array $shotData Shot data
     * @param array|null $complexity Complexity analysis (optional)
     * @return array Expansion result
     */
    protected function fallbackToTemplate(array $shotData, ?array $complexity = null): array
    {
        // Build basic prompt from shot data
        $basicPrompt = $this->buildBasicPrompt($shotData);

        // Use PromptExpanderService rules-based expansion
        $result = $this->promptExpander->expandPrompt($basicPrompt, [
            'useAI' => false,
            'shotType' => $shotData['shot_type'] ?? 'medium',
            'emotion' => $shotData['emotion'] ?? null,
            'style' => 'cinematic',
        ]);

        $expandedPrompt = $result['expandedPrompt'] ?? $basicPrompt;

        // Wrap in semantic markers for consistency
        $expandedPrompt = $this->wrapWithSemanticMarkers($expandedPrompt, $shotData);

        return [
            'expanded_prompt' => $expandedPrompt,
            'method' => 'template',
            'provider' => 'rules',
            'complexity' => $complexity ?? $this->complexityDetector->calculateComplexity($shotData),
        ];
    }

    /**
     * Build the vocabulary-constrained system prompt.
     *
     * This prompt is STABLE and can be cached by LLM providers.
     *
     * @return string System prompt with vocabulary constraints
     */
    protected function buildSystemPrompt(): string
    {
        $lensVocabulary = $this->formatVocabulary(CinematographyVocabulary::LENS_PSYCHOLOGY);
        $lightingVocabulary = $this->formatVocabulary(CinematographyVocabulary::LIGHTING_RATIOS);
        $emotionVocabulary = $this->formatVocabulary(CharacterPsychologyService::EMOTION_MANIFESTATIONS);
        $proxemicVocabulary = $this->formatVocabulary(CharacterDynamicsService::PROXEMIC_ZONES);
        $powerVocabulary = $this->formatVocabulary(CharacterDynamicsService::POWER_POSITIONING);

        return <<<SYSTEM
You are a Hollywood cinematographer expanding video prompts. You MUST use ONLY the vocabulary provided below.

## VOCABULARY CONSTRAINTS (Use these exact terms)

### Lens Psychology
{$lensVocabulary}

### Lighting Ratios
{$lightingVocabulary}

### Emotion Physical Manifestations (NOT emotion labels)
{$emotionVocabulary}

### Multi-Character Spatial Dynamics
Proxemic Zones:
{$proxemicVocabulary}

Power Positioning:
{$powerVocabulary}

## OUTPUT FORMAT
Use semantic markers for structure:
- [LENS:] for lens/camera description
- [LIGHTING:] for lighting ratios and mood
- [FRAME:] for framing and composition
- [SUBJECT:] for character description with physical manifestations
- [DYNAMICS:] for multi-character spatial relationships
- [ENVIRONMENT:] for mise-en-scene

## CRITICAL RULES
1. NEVER use emotion labels ("angry", "sad"). Use ONLY physical manifestations.
2. NEVER invent technical terms. Use ONLY the vocabulary provided.
3. Keep expanded prompt under 200 words.
4. Multi-character scenes MUST include explicit spatial relationships from DYNAMICS vocabulary.
5. Output ONLY the expanded prompt. No explanations.
SYSTEM;
    }

    /**
     * Build dynamic per-shot user prompt.
     *
     * @param array $shotData Shot data
     * @return string User prompt
     */
    protected function buildUserPrompt(array $shotData): string
    {
        $parts = ['Expand this shot into a Hollywood-quality video prompt:'];

        // Shot type
        $shotType = $shotData['shot_type'] ?? 'medium';
        $parts[] = "Shot type: {$shotType}";

        // Characters
        $characters = $shotData['characters'] ?? [];
        if (!empty($characters)) {
            $characterNames = [];
            foreach ($characters as $char) {
                if (is_array($char) && isset($char['name'])) {
                    $characterNames[] = $char['name'];
                } elseif (is_string($char)) {
                    $characterNames[] = $char;
                }
            }
            $parts[] = 'Characters: ' . implode(', ', $characterNames);
        }

        // Emotion/mood
        if (!empty($shotData['emotion'])) {
            $parts[] = "Emotional state: {$shotData['emotion']}";
        }

        // Subtext
        if (!empty($shotData['subtext'])) {
            $parts[] = "Subtext: {$shotData['subtext']}";
        }

        // Environment
        if (!empty($shotData['environment'])) {
            $parts[] = "Environment: {$shotData['environment']}";
        }

        // Action/movement
        if (!empty($shotData['action'])) {
            $parts[] = "Action: {$shotData['action']}";
        }

        // Relationship (for multi-character)
        if (!empty($shotData['relationship'])) {
            $parts[] = "Character relationship: {$shotData['relationship']}";
        }

        return implode("\n", $parts);
    }

    /**
     * Format vocabulary arrays for system prompt.
     *
     * @param array $vocabulary Vocabulary array
     * @return string Formatted vocabulary string
     */
    protected function formatVocabulary(array $vocabulary): string
    {
        $lines = [];

        foreach ($vocabulary as $key => $value) {
            if (is_array($value)) {
                // Extract key descriptive parts
                $description = '';
                if (isset($value['effect'])) {
                    $description = $value['effect'];
                } elseif (isset($value['description'])) {
                    $description = $value['description'];
                } elseif (isset($value['prompt'])) {
                    $description = $value['prompt'];
                } elseif (isset($value['face'])) {
                    // Emotion manifestations - show physical components
                    $description = "face: {$value['face']}, eyes: {$value['eyes']}";
                } elseif (isset($value['dominant'])) {
                    // Power positioning
                    $description = "dominant: {$value['dominant']}";
                }

                if (!empty($description)) {
                    $lines[] = "- {$key}: {$description}";
                }
            } else {
                $lines[] = "- {$key}: {$value}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Post-process LLM output to validate and clean.
     *
     * @param array $result Raw LLM result
     * @param array $shotData Original shot data
     * @param array $complexity Complexity analysis
     * @return array Processed result
     */
    protected function postProcess(array $result, array $shotData, array $complexity): array
    {
        $expandedPrompt = $result['expanded_prompt'];

        // Validate semantic markers
        $markerCount = 0;
        foreach (self::SEMANTIC_MARKERS as $marker) {
            if (str_contains($expandedPrompt, $marker)) {
                $markerCount++;
            }
        }

        if ($markerCount < self::MIN_REQUIRED_MARKERS) {
            Log::warning('LLMExpansionService: Output missing semantic markers', [
                'marker_count' => $markerCount,
                'required' => self::MIN_REQUIRED_MARKERS,
                'provider' => $result['provider'],
            ]);
            // Still use the output but flag it
            $result['markers_valid'] = false;
        } else {
            $result['markers_valid'] = true;
        }

        // Check and trim word count
        $wordCount = str_word_count($expandedPrompt);
        if ($wordCount > self::MAX_OUTPUT_WORDS) {
            Log::info('LLMExpansionService: Trimming output to word limit', [
                'original_words' => $wordCount,
                'limit' => self::MAX_OUTPUT_WORDS,
            ]);
            $expandedPrompt = $this->trimToWordLimit($expandedPrompt, self::MAX_OUTPUT_WORDS);
            $result['trimmed'] = true;
        }

        // Check for multi-character scenes requiring dynamics
        $characters = $shotData['characters'] ?? [];
        $characterCount = is_array($characters) ? count($characters) : 0;

        if ($characterCount >= 2 && !str_contains($expandedPrompt, '[DYNAMICS:')) {
            Log::warning('LLMExpansionService: Multi-character scene missing DYNAMICS marker', [
                'character_count' => $characterCount,
            ]);
            $result['dynamics_missing'] = true;
        }

        $result['expanded_prompt'] = $expandedPrompt;
        $result['complexity'] = $complexity;
        $result['word_count'] = str_word_count($expandedPrompt);

        return $result;
    }

    /**
     * Trim prompt to word limit while preserving semantic markers.
     *
     * @param string $prompt The prompt to trim
     * @param int $maxWords Maximum word count
     * @return string Trimmed prompt
     */
    protected function trimToWordLimit(string $prompt, int $maxWords): string
    {
        $words = preg_split('/\s+/', $prompt);

        if (count($words) <= $maxWords) {
            return $prompt;
        }

        // Take first N words
        $trimmedWords = array_slice($words, 0, $maxWords);
        $trimmed = implode(' ', $trimmedWords);

        // Try to end at a natural break (period or closing bracket)
        $lastPeriod = strrpos($trimmed, '.');
        $lastBracket = strrpos($trimmed, ']');

        $breakPoint = max($lastPeriod, $lastBracket);
        if ($breakPoint !== false && $breakPoint > strlen($trimmed) * 0.7) {
            $trimmed = substr($trimmed, 0, $breakPoint + 1);
        }

        return $trimmed;
    }

    /**
     * Build basic prompt from shot data for template expansion.
     *
     * @param array $shotData Shot data
     * @return string Basic prompt
     */
    protected function buildBasicPrompt(array $shotData): string
    {
        $parts = [];

        // Characters
        $characters = $shotData['characters'] ?? [];
        if (!empty($characters)) {
            $characterNames = [];
            foreach ($characters as $char) {
                if (is_array($char) && isset($char['name'])) {
                    $characterNames[] = $char['name'];
                } elseif (is_string($char)) {
                    $characterNames[] = $char;
                }
            }
            if (count($characterNames) === 1) {
                $parts[] = $characterNames[0];
            } else {
                $parts[] = implode(' and ', $characterNames);
            }
        } else {
            $parts[] = 'A person';
        }

        // Action
        if (!empty($shotData['action'])) {
            $parts[] = $shotData['action'];
        }

        // Environment
        if (!empty($shotData['environment'])) {
            $parts[] = 'in ' . $shotData['environment'];
        }

        // Emotion context
        if (!empty($shotData['emotion'])) {
            $parts[] = 'with ' . $shotData['emotion'] . ' expression';
        }

        return implode(' ', $parts);
    }

    /**
     * Wrap template-expanded prompt with semantic markers.
     *
     * @param string $prompt Expanded prompt
     * @param array $shotData Original shot data
     * @return string Prompt with semantic markers
     */
    protected function wrapWithSemanticMarkers(string $prompt, array $shotData): string
    {
        $parts = [];

        // Add lens marker based on shot type
        $shotType = $shotData['shot_type'] ?? 'medium';
        $lens = $this->cinematographyVocabulary->getLensForShotType($shotType);
        $parts[] = "[LENS: {$lens['focal_length']}, {$lens['effect']}]";

        // Add subject marker
        $parts[] = "[SUBJECT: {$prompt}]";

        // Add lighting marker if we have emotion
        if (!empty($shotData['emotion'])) {
            $lighting = $this->cinematographyVocabulary->getRatioForMood($shotData['emotion']);
            $parts[] = "[LIGHTING: {$lighting['ratio']} ratio, {$lighting['description']}]";
        }

        // Add dynamics marker for multi-character
        $characters = $shotData['characters'] ?? [];
        $characterCount = is_array($characters) ? count($characters) : 0;

        if ($characterCount >= 2) {
            $relationship = $shotData['relationship'] ?? 'equals';
            $proximity = $this->characterDynamics->getProximityForRelationship($relationship);
            $dynamics = $this->characterDynamics->buildSpatialDynamics($relationship, $proximity, $characters);
            $parts[] = "[DYNAMICS: {$dynamics}]";
        }

        // Add environment if present
        if (!empty($shotData['environment'])) {
            $parts[] = "[ENVIRONMENT: {$shotData['environment']}]";
        }

        return implode(' ', $parts);
    }
}
