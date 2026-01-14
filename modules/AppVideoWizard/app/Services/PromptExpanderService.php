<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\AppVideoWizard\Models\VwSetting;
use App\Services\GrokService;
use App\Services\OpenAIService;

/**
 * PromptExpanderService - Hollywood-quality AI prompt enhancement.
 *
 * Implements the professional prompt formula based on industry best practices:
 * [Camera Shot + Motion] + [Subject + Detailed Action] + [Environment] + [Lighting] + [Cinematic Style]
 *
 * Sources:
 * - Runway Text to Video Prompting Guide
 * - MiniMax/Hailuo Prompt Guide
 * - Sora 2 Best Practices
 * - Higgsfield Testing Results
 */
class PromptExpanderService
{
    /**
     * Enhancement styles available for different creative needs.
     */
    public const ENHANCEMENT_STYLES = [
        'cinematic' => [
            'name' => 'Cinematic',
            'description' => 'Hollywood film-quality with professional cinematography',
            'keywords' => 'shallow depth of field, cinematic lighting, film grain, anamorphic lens',
            'focus' => 'visual storytelling, professional camera work, mood',
        ],
        'action' => [
            'name' => 'Action',
            'description' => 'Dynamic, high-energy with impactful movement',
            'keywords' => 'dynamic camera movement, fast-paced, impact frames, motion blur',
            'focus' => 'movement, energy, choreography, tension',
        ],
        'emotional' => [
            'name' => 'Emotional',
            'description' => 'Intimate, character-focused with subtle expressions',
            'keywords' => 'close-up expressions, intimate framing, soft lighting, emotional depth',
            'focus' => 'facial expressions, body language, emotional beats, intimacy',
        ],
        'atmospheric' => [
            'name' => 'Atmospheric',
            'description' => 'Environment-rich with immersive world-building',
            'keywords' => 'volumetric lighting, environmental storytelling, wide establishing shots',
            'focus' => 'environment, mood, atmosphere, world-building',
        ],
        'documentary' => [
            'name' => 'Documentary',
            'description' => 'Authentic, observational with naturalistic feel',
            'keywords' => 'natural lighting, handheld camera, observational style, authentic',
            'focus' => 'authenticity, realism, natural moments, truth',
        ],
    ];

    /**
     * Hollywood prompt formula components.
     */
    public const HOLLYWOOD_FORMULA = [
        'camera_shot' => [
            'extreme-wide' => 'Extreme wide establishing shot',
            'wide' => 'Wide shot capturing full scene',
            'medium-wide' => 'Medium wide shot showing full figure',
            'medium' => 'Medium shot from waist up',
            'medium-close' => 'Medium close-up from chest up',
            'close-up' => 'Close-up on face/details',
            'extreme-close-up' => 'Extreme close-up showing fine detail',
            'over-shoulder' => 'Over-the-shoulder perspective',
            'two-shot' => 'Two-shot framing both subjects',
            'pov' => 'Point-of-view shot',
            'low-angle' => 'Low angle looking up (power/dominance)',
            'high-angle' => 'High angle looking down (vulnerability)',
            'dutch-angle' => 'Dutch angle tilted frame (tension/unease)',
            'aerial' => 'Aerial/bird\'s-eye view',
        ],
        'camera_movement' => [
            'static' => 'static locked-off frame',
            'slow-push' => 'slow push in building intensity',
            'slow-pull' => 'slow pull back revealing context',
            'dolly-forward' => 'dolly forward drawing viewer in',
            'dolly-backward' => 'dolly backward creating distance',
            'tracking-left' => 'tracking shot left following action',
            'tracking-right' => 'tracking shot right following action',
            'crane-up' => 'crane up rising to reveal',
            'crane-down' => 'crane down descending toward subject',
            'pan-left' => 'smooth pan left surveying scene',
            'pan-right' => 'smooth pan right surveying scene',
            'tilt-up' => 'tilt up from ground to sky',
            'tilt-down' => 'tilt down from sky to ground',
            'handheld' => 'handheld subtle movement with organic feel',
            'steadicam' => 'steadicam smooth floating movement',
            'orbit' => 'orbit circling around subject',
            'whip-pan' => 'whip pan rapid transition',
            'zoom-in' => 'slow zoom in intensifying focus',
            'zoom-out' => 'slow zoom out revealing scale',
        ],
        'lighting_styles' => [
            'golden-hour' => 'golden hour warm light with long shadows',
            'blue-hour' => 'blue hour twilight cool ambient light',
            'harsh-midday' => 'harsh midday sun with strong shadows',
            'soft-diffused' => 'soft diffused light flattering and even',
            'dramatic-side' => 'dramatic side lighting with deep shadows',
            'backlit' => 'backlit silhouette with rim light',
            'rim-light' => 'rim light separating subject from background',
            'chiaroscuro' => 'chiaroscuro dramatic contrast light and shadow',
            'neon' => 'neon colored light urban night aesthetic',
            'candlelight' => 'candlelight warm flickering intimate',
            'moonlight' => 'moonlight cool pale ambient glow',
            'overcast' => 'overcast soft even shadowless light',
            'low-key' => 'low-key mostly shadow dramatic and mysterious',
            'high-key' => 'high-key bright minimal shadow upbeat feel',
            'volumetric' => 'volumetric light rays visible atmosphere',
            'practical' => 'practical lights in frame motivated sources',
        ],
        'cinematic_styles' => [
            'film-noir' => 'film noir style, high contrast black and white aesthetic',
            'neo-noir' => 'neo-noir modern with saturated colors and shadow',
            'blockbuster' => 'blockbuster style polished high-budget look',
            'indie' => 'indie film aesthetic natural and intimate',
            'arthouse' => 'arthouse contemplative and visually poetic',
            'thriller' => 'thriller tension building suspenseful mood',
            'horror' => 'horror atmospheric dread and unease',
            'romance' => 'romance soft warm intimate and tender',
            'epic' => 'epic scale grandeur and spectacle',
            'gritty' => 'gritty realistic raw and unflinching',
            'dreamlike' => 'dreamlike ethereal soft and surreal',
            'retro-70s' => 'retro 70s film grain warm desaturated',
            'retro-80s' => 'retro 80s neon synth-wave aesthetic',
            'modern-clean' => 'modern clean sharp minimal contemporary',
        ],
    ];

    /**
     * Verb-based action library for different shot types.
     */
    public const ACTION_VERBS = [
        'establishing' => [
            'emerges', 'surveys', 'arrives', 'stands', 'awaits',
            'gazes across', 'overlooks', 'enters', 'approaches', 'observes',
        ],
        'wide' => [
            'strides', 'moves through', 'navigates', 'traverses', 'journeys',
            'walks purposefully', 'runs toward', 'advances', 'retreats', 'paces',
        ],
        'medium' => [
            'gestures', 'speaks', 'listens intently', 'reacts', 'considers',
            'turns to face', 'leans forward', 'steps back', 'reaches', 'holds',
        ],
        'close-up' => [
            'reveals emotion', 'shows determination', 'expresses', 'conveys',
            'furrows brow', 'narrows eyes', 'parts lips', 'swallows hard', 'blinks',
        ],
        'reaction' => [
            'realizes', 'processes', 'absorbs', 'comprehends', 'registers',
            'recoils', 'softens', 'hardens', 'transforms', 'shifts',
        ],
        'action' => [
            'strikes', 'dodges', 'lunges', 'blocks', 'parries',
            'spins', 'leaps', 'ducks', 'rolls', 'charges',
        ],
    ];

    protected ?GrokService $grokService = null;
    protected ?OpenAIService $openAIService = null;

    public function __construct()
    {
        // Services will be initialized on demand
    }

    /**
     * Expand a basic prompt to Hollywood-quality using AI.
     *
     * @param string $basicPrompt The simple/basic prompt
     * @param array $options Enhancement options
     * @return array Expanded prompt result
     */
    public function expandPrompt(string $basicPrompt, array $options = []): array
    {
        $style = $options['style'] ?? 'cinematic';
        $styleConfig = self::ENHANCEMENT_STYLES[$style] ?? self::ENHANCEMENT_STYLES['cinematic'];

        $shotType = $options['shotType'] ?? null;
        $emotion = $options['emotion'] ?? null;
        $genre = $options['genre'] ?? 'cinematic';
        $useAI = $options['useAI'] ?? true;
        $storyBibleContext = $options['storyBibleContext'] ?? null;

        try {
            if ($useAI) {
                // Use AI for intelligent expansion
                return $this->expandWithAI($basicPrompt, $styleConfig, [
                    'shotType' => $shotType,
                    'emotion' => $emotion,
                    'genre' => $genre,
                    'storyBibleContext' => $storyBibleContext,
                ]);
            } else {
                // Use rule-based expansion
                return $this->expandWithRules($basicPrompt, $styleConfig, [
                    'shotType' => $shotType,
                    'emotion' => $emotion,
                    'genre' => $genre,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('PromptExpanderService: Expansion failed', [
                'error' => $e->getMessage(),
                'basicPrompt' => substr($basicPrompt, 0, 100),
            ]);

            // Fallback to rule-based expansion
            return $this->expandWithRules($basicPrompt, $styleConfig, [
                'shotType' => $shotType,
                'emotion' => $emotion,
                'genre' => $genre,
            ]);
        }
    }

    /**
     * Expand prompt using AI for intelligent enhancement.
     */
    protected function expandWithAI(string $basicPrompt, array $styleConfig, array $context): array
    {
        $systemPrompt = $this->buildSystemPrompt($styleConfig, $context);
        $userPrompt = $this->buildUserPrompt($basicPrompt, $context);

        // Try Grok first (cost-effective), fall back to OpenAI
        $response = null;
        $provider = 'grok';

        try {
            $this->grokService = $this->grokService ?? app(GrokService::class);
            $response = $this->grokService->chat([
                'model' => 'grok-3-mini-fast',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if (!empty($response['content'])) {
                $expandedPrompt = trim($response['content']);
            }
        } catch (\Exception $e) {
            Log::warning('PromptExpanderService: Grok failed, trying OpenAI', [
                'error' => $e->getMessage(),
            ]);
            $provider = 'openai';
        }

        // Fallback to OpenAI if Grok failed
        if (empty($expandedPrompt)) {
            try {
                $this->openAIService = $this->openAIService ?? app(OpenAIService::class);
                $response = $this->openAIService->chat([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

                $expandedPrompt = trim($response['choices'][0]['message']['content'] ?? '');
            } catch (\Exception $e) {
                Log::error('PromptExpanderService: OpenAI also failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        // Clean up the response
        $expandedPrompt = $this->cleanExpandedPrompt($expandedPrompt);

        return [
            'success' => true,
            'originalPrompt' => $basicPrompt,
            'expandedPrompt' => $expandedPrompt,
            'style' => $styleConfig['name'],
            'provider' => $provider,
            'method' => 'ai',
        ];
    }

    /**
     * Build system prompt for AI expansion.
     */
    protected function buildSystemPrompt(array $styleConfig, array $context): string
    {
        $genre = $context['genre'] ?? 'cinematic';

        return <<<SYSTEM
You are a Hollywood cinematographer and video prompt engineer. Your task is to expand basic video prompts into professional, cinematic prompts that will generate stunning AI video.

## Your Role
Transform simple descriptions into detailed, visually rich prompts following the Hollywood formula:
[Camera Shot + Motion] + [Subject + Detailed Action] + [Environment] + [Lighting] + [Cinematic Style]

## Enhancement Style: {$styleConfig['name']}
{$styleConfig['description']}
Keywords: {$styleConfig['keywords']}
Focus areas: {$styleConfig['focus']}

## CRITICAL RULES
1. **Subject Action is CRITICAL**: Always describe WHAT THE SUBJECT IS DOING with specific verbs
   - BAD: "A man in a forest"
   - GOOD: "A rugged man in his 40s strides purposefully through dense forest, his weathered face showing determination"

2. **Camera Movement Syntax**: Use professional terminology
   - "slow dolly forward", "tracking shot left", "crane rising", "handheld with subtle movement"

3. **Lighting Must Be Specific**:
   - BAD: "good lighting"
   - GOOD: "golden hour side lighting casting long shadows, rim light separating subject from background"

4. **Color Grading**: Include specific color palette
   - "teal shadows with warm orange highlights", "desaturated with lifted blacks", "deep contrast with crushed blacks"

5. **Emotional Context**: Include facial expressions and body language for character shots

6. **Keep It Concise**: Optimal length is 50-100 words. Be specific, not verbose.

## Output Format
Return ONLY the expanded prompt. No explanations, no bullet points, no formatting - just the cinematic prompt text.
SYSTEM;
    }

    /**
     * Build user prompt for AI expansion.
     */
    protected function buildUserPrompt(string $basicPrompt, array $context): string
    {
        $parts = ["Expand this basic prompt into a Hollywood-quality video prompt:\n\n\"{$basicPrompt}\""];

        if (!empty($context['shotType'])) {
            $parts[] = "\nShot type: {$context['shotType']}";
        }

        if (!empty($context['emotion'])) {
            $parts[] = "Emotional tone: {$context['emotion']}";
        }

        if (!empty($context['genre'])) {
            $parts[] = "Genre: {$context['genre']}";
        }

        if (!empty($context['storyBibleContext'])) {
            $parts[] = "\nStory Bible context:\n{$context['storyBibleContext']}";
        }

        return implode("\n", $parts);
    }

    /**
     * Rule-based prompt expansion (no AI required).
     */
    protected function expandWithRules(string $basicPrompt, array $styleConfig, array $context): array
    {
        $parts = [];

        // 1. Detect and add shot type
        $shotType = $context['shotType'] ?? $this->detectShotType($basicPrompt);
        $shotDescription = self::HOLLYWOOD_FORMULA['camera_shot'][$shotType] ?? '';
        if ($shotDescription) {
            $parts[] = $shotDescription;
        }

        // 2. Infer and add camera movement
        $movement = $this->inferCameraMovement($shotType, $context['emotion'] ?? 'neutral');
        $movementDescription = self::HOLLYWOOD_FORMULA['camera_movement'][$movement] ?? '';
        if ($movementDescription) {
            $parts[] = $movementDescription;
        }

        // 3. Enhance subject action
        $enhancedAction = $this->enhanceSubjectAction($basicPrompt, $shotType);
        $parts[] = $enhancedAction;

        // 4. Add lighting based on context
        $lighting = $this->inferLighting($basicPrompt, $context['emotion'] ?? 'neutral');
        $lightingDescription = self::HOLLYWOOD_FORMULA['lighting_styles'][$lighting] ?? '';
        if ($lightingDescription) {
            $parts[] = $lightingDescription;
        }

        // 5. Add cinematic style
        $cinematicStyle = $this->inferCinematicStyle($context['genre'] ?? 'cinematic');
        $styleDescription = self::HOLLYWOOD_FORMULA['cinematic_styles'][$cinematicStyle] ?? '';
        if ($styleDescription) {
            $parts[] = $styleDescription;
        }

        // 6. Add style-specific keywords
        $parts[] = $styleConfig['keywords'];

        $expandedPrompt = implode('. ', array_filter($parts));

        return [
            'success' => true,
            'originalPrompt' => $basicPrompt,
            'expandedPrompt' => $expandedPrompt,
            'style' => $styleConfig['name'],
            'provider' => 'rules',
            'method' => 'rules',
            'components' => [
                'shotType' => $shotType,
                'movement' => $movement,
                'lighting' => $lighting,
                'cinematicStyle' => $cinematicStyle,
            ],
        ];
    }

    /**
     * Detect shot type from prompt content.
     */
    protected function detectShotType(string $prompt): string
    {
        $prompt = strtolower($prompt);

        // Check for explicit shot type mentions
        $shotMappings = [
            'extreme wide' => 'extreme-wide',
            'establishing' => 'extreme-wide',
            'wide shot' => 'wide',
            'full shot' => 'wide',
            'medium wide' => 'medium-wide',
            'medium shot' => 'medium',
            'medium close' => 'medium-close',
            'close-up' => 'close-up',
            'closeup' => 'close-up',
            'extreme close' => 'extreme-close-up',
            'over shoulder' => 'over-shoulder',
            'over-the-shoulder' => 'over-shoulder',
            'two shot' => 'two-shot',
            'pov' => 'pov',
            'point of view' => 'pov',
            'low angle' => 'low-angle',
            'high angle' => 'high-angle',
            'dutch' => 'dutch-angle',
            'aerial' => 'aerial',
            'bird' => 'aerial',
        ];

        foreach ($shotMappings as $keyword => $type) {
            if (str_contains($prompt, $keyword)) {
                return $type;
            }
        }

        // Infer from content
        if (preg_match('/\b(face|eyes|expression|lips|emotion)\b/', $prompt)) {
            return 'close-up';
        }
        if (preg_match('/\b(landscape|city|environment|scene|location)\b/', $prompt)) {
            return 'wide';
        }
        if (preg_match('/\b(conversation|talking|speaking|dialogue)\b/', $prompt)) {
            return 'medium';
        }

        return 'medium'; // Default
    }

    /**
     * Infer appropriate camera movement.
     */
    protected function inferCameraMovement(string $shotType, string $emotion): string
    {
        $emotionMovements = [
            'tense' => ['slow-push', 'handheld', 'orbit'],
            'dramatic' => ['dolly-forward', 'crane-up', 'slow-push'],
            'calm' => ['static', 'slow-pull', 'pan-left'],
            'energetic' => ['tracking-left', 'whip-pan', 'steadicam'],
            'mysterious' => ['slow-push', 'orbit', 'crane-down'],
            'romantic' => ['dolly-forward', 'steadicam', 'orbit'],
            'sad' => ['slow-pull', 'crane-down', 'static'],
            'happy' => ['steadicam', 'tracking-right', 'crane-up'],
        ];

        $movements = $emotionMovements[$emotion] ?? ['steadicam', 'slow-push', 'static'];

        // Adjust based on shot type
        $shotAdjustments = [
            'extreme-wide' => ['crane-up', 'pan-left', 'static'],
            'wide' => ['dolly-forward', 'tracking-left', 'crane-down'],
            'close-up' => ['slow-push', 'static', 'handheld'],
            'extreme-close-up' => ['static', 'slow-push'],
            'action' => ['handheld', 'tracking-left', 'whip-pan'],
        ];

        $shotMovements = $shotAdjustments[$shotType] ?? null;
        if ($shotMovements) {
            $movements = array_merge($shotMovements, $movements);
        }

        return $movements[array_rand($movements)];
    }

    /**
     * Enhance subject action with specific verbs and details.
     */
    protected function enhanceSubjectAction(string $basicPrompt, string $shotType): string
    {
        // Get action verbs for this shot type
        $shotCategory = 'medium';
        if (in_array($shotType, ['extreme-wide', 'wide'])) {
            $shotCategory = 'wide';
        } elseif (in_array($shotType, ['close-up', 'extreme-close-up'])) {
            $shotCategory = 'close-up';
        } elseif ($shotType === 'establishing') {
            $shotCategory = 'establishing';
        }

        $verbs = self::ACTION_VERBS[$shotCategory] ?? self::ACTION_VERBS['medium'];

        // Detect subject from prompt
        $subject = $this->detectSubject($basicPrompt);
        $action = $verbs[array_rand($verbs)];

        // Build enhanced action description
        $enhanced = $basicPrompt;

        // Add action verb if not already present
        $hasAction = preg_match('/\b(' . implode('|', array_merge(...array_values(self::ACTION_VERBS))) . ')\b/i', $basicPrompt);
        if (!$hasAction && !empty($subject)) {
            $enhanced = "{$subject} {$action}, {$basicPrompt}";
        }

        // Add body language/emotional cues for character shots
        if ($shotCategory === 'close-up' || $shotCategory === 'medium') {
            $emotionalCues = [
                'eyes conveying inner conflict',
                'subtle tension in their jaw',
                'a flicker of emotion crossing their face',
                'their expression shifting almost imperceptibly',
                'micro-expressions revealing their true feelings',
            ];
            $enhanced .= ', ' . $emotionalCues[array_rand($emotionalCues)];
        }

        return $enhanced;
    }

    /**
     * Detect subject from prompt.
     */
    protected function detectSubject(string $prompt): string
    {
        $prompt = strtolower($prompt);

        // Character keywords
        $characterPatterns = [
            '/\b(man|woman|person|figure|character|protagonist|hero|villain)\b/' => 'The subject',
            '/\b(warrior|soldier|knight|samurai)\b/' => 'The warrior',
            '/\b(detective|agent|spy)\b/' => 'The agent',
            '/\b(child|kid|boy|girl)\b/' => 'The young figure',
            '/\b(elderly|old man|old woman)\b/' => 'The elderly figure',
            '/\b(group|people|crowd|team)\b/' => 'The group',
        ];

        foreach ($characterPatterns as $pattern => $subject) {
            if (preg_match($pattern, $prompt)) {
                return $subject;
            }
        }

        return 'The subject';
    }

    /**
     * Infer lighting from context.
     */
    protected function inferLighting(string $prompt, string $emotion): string
    {
        $prompt = strtolower($prompt);

        // Check for explicit lighting mentions
        $lightingMappings = [
            'golden hour' => 'golden-hour',
            'sunset' => 'golden-hour',
            'sunrise' => 'golden-hour',
            'night' => 'moonlight',
            'neon' => 'neon',
            'dark' => 'low-key',
            'bright' => 'high-key',
            'shadow' => 'chiaroscuro',
            'candle' => 'candlelight',
            'overcast' => 'overcast',
            'backlit' => 'backlit',
            'fog' => 'volumetric',
            'rays' => 'volumetric',
        ];

        foreach ($lightingMappings as $keyword => $style) {
            if (str_contains($prompt, $keyword)) {
                return $style;
            }
        }

        // Infer from emotion
        $emotionLighting = [
            'tense' => 'low-key',
            'dramatic' => 'chiaroscuro',
            'romantic' => 'golden-hour',
            'mysterious' => 'low-key',
            'happy' => 'high-key',
            'sad' => 'overcast',
            'energetic' => 'high-key',
            'peaceful' => 'soft-diffused',
        ];

        return $emotionLighting[$emotion] ?? 'dramatic-side';
    }

    /**
     * Infer cinematic style from genre.
     */
    protected function inferCinematicStyle(string $genre): string
    {
        $genreStyles = [
            'thriller' => 'thriller',
            'horror' => 'horror',
            'romance' => 'romance',
            'action' => 'blockbuster',
            'drama' => 'indie',
            'documentary' => 'indie',
            'epic' => 'epic',
            'noir' => 'neo-noir',
            'sci-fi' => 'modern-clean',
            'fantasy' => 'epic',
            'comedy' => 'high-key',
        ];

        return $genreStyles[strtolower($genre)] ?? 'blockbuster';
    }

    /**
     * Clean up AI-expanded prompt.
     */
    protected function cleanExpandedPrompt(string $prompt): string
    {
        // Remove common AI response artifacts
        $prompt = preg_replace('/^(Here\'s|Here is|Expanded prompt:|Enhanced prompt:)/i', '', $prompt);
        $prompt = preg_replace('/^[\s\-\*\#]+/', '', $prompt);
        $prompt = trim($prompt, " \t\n\r\0\x0B\"'");

        return $prompt;
    }

    /**
     * Get all available enhancement styles.
     */
    public function getEnhancementStyles(): array
    {
        return self::ENHANCEMENT_STYLES;
    }

    /**
     * Expand video motion prompt (separate from image prompt).
     */
    public function expandVideoMotionPrompt(string $imagePrompt, array $options = []): array
    {
        $shotType = $options['shotType'] ?? $this->detectShotType($imagePrompt);
        $emotion = $options['emotion'] ?? 'neutral';
        $duration = $options['duration'] ?? 6;

        // Build motion-specific prompt components
        $parts = [];

        // 1. Subject Action (CRITICAL for video)
        $subjectAction = $this->generateVideoSubjectAction($imagePrompt, $shotType);
        $parts[] = $subjectAction;

        // 2. Camera Movement with timing
        $movement = $this->inferCameraMovement($shotType, $emotion);
        $movementDesc = self::HOLLYWOOD_FORMULA['camera_movement'][$movement] ?? 'smooth camera movement';
        $parts[] = "Camera: {$movementDesc}";

        // 3. Motion dynamics based on duration
        if ($duration <= 4) {
            $parts[] = 'subtle contained movement';
        } elseif ($duration <= 8) {
            $parts[] = 'natural fluid motion progression';
        } else {
            $parts[] = 'gradual evolving movement with clear beginning middle and end';
        }

        // 4. Atmospheric motion
        $atmosphericMotion = $this->getAtmosphericMotion($imagePrompt);
        if ($atmosphericMotion) {
            $parts[] = $atmosphericMotion;
        }

        $motionPrompt = implode('. ', array_filter($parts));

        return [
            'success' => true,
            'imagePrompt' => $imagePrompt,
            'motionPrompt' => $motionPrompt,
            'shotType' => $shotType,
            'movement' => $movement,
            'duration' => $duration,
        ];
    }

    /**
     * Generate video-specific subject action.
     */
    protected function generateVideoSubjectAction(string $imagePrompt, string $shotType): string
    {
        // Use "the subject" for image-to-video per MiniMax/Runway best practices
        $actions = [
            'extreme-wide' => 'The subject moves subtly within the vast environment, their presence grounded in the scene',
            'wide' => 'The subject advances through the space with purposeful movement, body language conveying intent',
            'medium' => 'The subject engages with their surroundings, subtle shifts in posture and expression',
            'medium-close' => 'The subject displays measured movement, expression evolving with inner thought',
            'close-up' => 'The subject\'s expression shifts subtly, eyes moving with life, micro-expressions revealing emotion',
            'extreme-close-up' => 'Subtle breathing movement, almost imperceptible shifts in expression, living detail',
            'reaction' => 'The subject reacts with visible emotion, expression transforming to match the moment',
            'pov' => 'The perspective shifts naturally as if through living eyes, organic head movement',
        ];

        $baseAction = $actions[$shotType] ?? $actions['medium'];

        // Detect if prompt mentions specific action and incorporate
        if (preg_match('/\b(walking|running|fighting|talking|sitting|standing)\b/i', $imagePrompt, $matches)) {
            $detectedAction = strtolower($matches[1]);
            $baseAction = str_replace('moves', $detectedAction, $baseAction);
        }

        return $baseAction;
    }

    /**
     * Get atmospheric motion elements from prompt.
     */
    protected function getAtmosphericMotion(string $prompt): ?string
    {
        $prompt = strtolower($prompt);

        $atmosphericElements = [
            'wind' => 'wind gently moving hair and fabric',
            'rain' => 'rain falling with dynamic droplets',
            'snow' => 'snow drifting softly through frame',
            'fire' => 'flames flickering with organic movement',
            'smoke' => 'smoke wisps curling through air',
            'fog' => 'fog drifting lazily through scene',
            'dust' => 'dust particles catching light',
            'water' => 'water rippling with subtle movement',
            'leaves' => 'leaves rustling in gentle breeze',
            'clouds' => 'clouds drifting slowly overhead',
        ];

        foreach ($atmosphericElements as $keyword => $motion) {
            if (str_contains($prompt, $keyword)) {
                return $motion;
            }
        }

        return null;
    }

    /**
     * Static factory for quick expansion.
     */
    public static function expand(string $prompt, array $options = []): array
    {
        return (new self())->expandPrompt($prompt, $options);
    }
}
