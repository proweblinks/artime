<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * CharacterPathService
 *
 * Provides character movement trajectory vocabulary for video generation.
 * AI video models need explicit movement descriptions - "walks toward camera"
 * rather than implicit direction like "approaches."
 *
 * Each path type has variants with parameter placeholders for customization,
 * and duration estimates aligned with VideoTemporalService guidelines (2-5 seconds).
 *
 * Categories:
 * - approach: Movement toward camera/subject
 * - retreat: Movement away from camera/subject
 * - stationary_motion: In-place movements (turns, rises, shifts)
 * - crossing: Lateral frame traversal
 * - gestural: Hand/arm movements and postures
 */
class CharacterPathService
{
    /**
     * Character path vocabulary organized by movement type.
     *
     * Placeholders:
     * - {degrees}: Rotation amount (e.g., 45, 90, 180)
     * - {direction}: Directional reference (left, right, up, down)
     * - {hand}: Which hand (left, right, both)
     */
    public const CHARACTER_PATH_VOCABULARY = [
        'approach' => [
            'toward_camera' => 'walks directly toward camera, growing larger in frame',
            'diagonal_entry' => 'enters frame from lower left, moving diagonally toward upper right',
            'lateral_approach' => 'approaches from the side, moving into personal space',
            'slow_advance' => 'advances slowly with measured steps, closing distance gradually',
            'purposeful_stride' => 'strides purposefully toward destination, each step deliberate',
        ],
        'retreat' => [
            'away_from_camera' => 'backs away from camera, shrinking in frame',
            'exit_frame' => 'exits frame, gaze lingering before departure',
            'turn_and_walk' => 'turns and walks away, back to camera',
            'fade_back' => 'steps backward into shadow, receding from view',
            'hesitant_retreat' => 'retreats with hesitant steps, glancing back',
        ],
        'stationary_motion' => [
            'turn' => 'turns {degrees} degrees to face {direction}',
            'rise' => 'rises from seated position to standing',
            'settle' => 'settles into position, weight shifting back',
            'shift_weight' => 'shifts weight from one foot to the other',
            'lean' => 'leans {direction}, changing center of gravity',
            'straighten' => 'straightens posture, drawing up to full height',
            'slump' => 'slumps slightly, shoulders dropping',
        ],
        'crossing' => [
            'left_to_right' => 'crosses frame from left to right, maintaining consistent distance',
            'right_to_left' => 'crosses frame from right to left, maintaining consistent distance',
            'foreground_cross' => 'crosses close to camera in foreground, momentarily filling frame',
            'background_cross' => 'crosses in background, smaller figure moving through scene',
            'diagonal_cross' => 'crosses diagonally through frame, from near to far',
        ],
        'gestural' => [
            'reach' => 'reaches {direction} with {hand}, extending into space',
            'point' => 'points {direction}, arm extending to indicate',
            'embrace_open' => 'opens arms in welcoming gesture',
            'defensive_cross' => 'crosses arms defensively, closing off body language',
            'hand_to_face' => 'brings hand to face in contemplative gesture',
            'dismissive_wave' => 'waves dismissively with {hand}, brushing off',
            'beckoning' => 'beckons with {hand}, inviting approach',
        ],
    ];

    /**
     * Duration estimates for each path type (in seconds).
     *
     * Aligned with VideoTemporalService guidelines:
     * - Most movements complete in 2-5 seconds
     * - Complex movements may take up to 6 seconds
     * - Gestures are fastest at 1-2 seconds
     */
    public const PATH_DURATION_ESTIMATES = [
        'approach' => ['min' => 3, 'max' => 5],
        'retreat' => ['min' => 2, 'max' => 4],
        'stationary_motion' => ['min' => 1, 'max' => 3],
        'crossing' => ['min' => 3, 'max' => 6],
        'gestural' => ['min' => 1, 'max' => 2],
    ];

    /**
     * Maps character intent to appropriate path type and variant.
     */
    public const INTENT_TO_PATH = [
        'enter_scene' => ['path_type' => 'approach', 'variant' => 'diagonal_entry'],
        'leave_scene' => ['path_type' => 'retreat', 'variant' => 'exit_frame'],
        'confront' => ['path_type' => 'approach', 'variant' => 'toward_camera'],
        'avoid' => ['path_type' => 'retreat', 'variant' => 'turn_and_walk'],
        'observe' => ['path_type' => 'stationary_motion', 'variant' => 'turn'],
        'react' => ['path_type' => 'stationary_motion', 'variant' => 'shift_weight'],
        'greet' => ['path_type' => 'gestural', 'variant' => 'embrace_open'],
        'reject' => ['path_type' => 'gestural', 'variant' => 'defensive_cross'],
        'summon' => ['path_type' => 'gestural', 'variant' => 'beckoning'],
        'dismiss' => ['path_type' => 'gestural', 'variant' => 'dismissive_wave'],
        'think' => ['path_type' => 'gestural', 'variant' => 'hand_to_face'],
        'stand_up' => ['path_type' => 'stationary_motion', 'variant' => 'rise'],
        'sit_down' => ['path_type' => 'stationary_motion', 'variant' => 'settle'],
        'pass_by' => ['path_type' => 'crossing', 'variant' => 'left_to_right'],
        'intercept' => ['path_type' => 'crossing', 'variant' => 'foreground_cross'],
        'indicate' => ['path_type' => 'gestural', 'variant' => 'point'],
    ];

    /**
     * Build a character path description with parameters filled in.
     *
     * @param string $pathType The path category (approach, retreat, etc.)
     * @param string $variant The specific variant within the category
     * @param array $parameters Parameters to substitute: degrees, direction, hand
     * @return string Completed path description
     */
    public function buildCharacterPath(string $pathType, string $variant, array $parameters = []): string
    {
        $pathType = strtolower(trim($pathType));
        $variant = strtolower(trim($variant));

        // Get the path template
        $template = self::CHARACTER_PATH_VOCABULARY[$pathType][$variant] ?? null;

        if (!$template) {
            // Try to find by variant alone across all types
            foreach (self::CHARACTER_PATH_VOCABULARY as $type => $variants) {
                if (isset($variants[$variant])) {
                    $template = $variants[$variant];
                    $pathType = $type;
                    break;
                }
            }
        }

        if (!$template) {
            Log::warning('CharacterPathService: Unknown path type/variant', [
                'path_type' => $pathType,
                'variant' => $variant,
            ]);
            return "performs movement";
        }

        // Substitute parameters with defaults
        $result = $template;

        // Substitute {degrees} with default of 90
        $degrees = $parameters['degrees'] ?? '90';
        $result = str_replace('{degrees}', $degrees, $result);

        // Substitute {direction} with default of 'left'
        $direction = $parameters['direction'] ?? 'left';
        $result = str_replace('{direction}', $direction, $result);

        // Substitute {hand} with default of 'right hand'
        $hand = $parameters['hand'] ?? 'right hand';
        $result = str_replace('{hand}', $hand, $result);

        Log::debug('CharacterPathService: Built character path', [
            'path_type' => $pathType,
            'variant' => $variant,
            'has_parameters' => !empty($parameters),
            'result_length' => strlen($result),
        ]);

        return $result;
    }

    /**
     * Suggest a path based on character intent.
     *
     * @param string $intent The intent (enter_scene, leave_scene, confront, etc.)
     * @return array{path_type: string, variant: string}
     */
    public function suggestPathForIntent(string $intent): array
    {
        $intent = strtolower(trim($intent));

        if (isset(self::INTENT_TO_PATH[$intent])) {
            return self::INTENT_TO_PATH[$intent];
        }

        // Fuzzy matching for common variations
        $aliases = [
            'enter' => 'enter_scene',
            'leave' => 'leave_scene',
            'exit' => 'leave_scene',
            'approach' => 'confront',
            'retreat' => 'avoid',
            'look' => 'observe',
            'watch' => 'observe',
            'welcome' => 'greet',
            'refuse' => 'reject',
            'call' => 'summon',
            'wave_off' => 'dismiss',
            'ponder' => 'think',
            'contemplate' => 'think',
            'rise' => 'stand_up',
            'stand' => 'stand_up',
            'sit' => 'sit_down',
            'walk_past' => 'pass_by',
            'block' => 'intercept',
            'show' => 'indicate',
        ];

        $mappedIntent = $aliases[$intent] ?? null;

        if ($mappedIntent && isset(self::INTENT_TO_PATH[$mappedIntent])) {
            return self::INTENT_TO_PATH[$mappedIntent];
        }

        // Default to subtle reaction
        Log::info('CharacterPathService: Unknown intent, defaulting to react', [
            'intent' => $intent,
        ]);

        return ['path_type' => 'stationary_motion', 'variant' => 'shift_weight'];
    }

    /**
     * Get duration estimate for a path type.
     *
     * @param string $pathType The path category
     * @return array{min: int, max: int}
     */
    public function estimatePathDuration(string $pathType): array
    {
        $pathType = strtolower(trim($pathType));

        if (isset(self::PATH_DURATION_ESTIMATES[$pathType])) {
            return self::PATH_DURATION_ESTIMATES[$pathType];
        }

        // Default to moderate duration
        return ['min' => 2, 'max' => 4];
    }

    /**
     * Combine character path with camera movement for coherent description.
     *
     * Creates a unified movement description that describes both
     * what the character does and how the camera follows.
     *
     * @param string $pathDescription The character's movement description
     * @param string $cameraMovement The camera movement description
     * @return string Combined coherent description
     */
    public function combinePathWithCamera(string $pathDescription, string $cameraMovement): string
    {
        $pathDescription = trim($pathDescription);
        $cameraMovement = trim($cameraMovement);

        if (empty($pathDescription) && empty($cameraMovement)) {
            return 'subject in frame with static camera';
        }

        if (empty($pathDescription)) {
            return "subject visible as camera {$cameraMovement}";
        }

        if (empty($cameraMovement)) {
            return $pathDescription;
        }

        // Detect if camera follows or contrasts with movement
        $followsMovement = $this->doesCameraFollowMovement($pathDescription, $cameraMovement);

        if ($followsMovement) {
            return "{$pathDescription}, camera {$cameraMovement} to follow";
        }

        // Camera provides contrast or independent movement
        return "{$pathDescription} as camera {$cameraMovement}";
    }

    /**
     * Get all available path types.
     *
     * @return array<string>
     */
    public function getAvailablePathTypes(): array
    {
        return array_keys(self::CHARACTER_PATH_VOCABULARY);
    }

    /**
     * Get all variants for a path type.
     *
     * @param string $pathType The path category
     * @return array<string>
     */
    public function getVariantsForPathType(string $pathType): array
    {
        $pathType = strtolower(trim($pathType));

        if (isset(self::CHARACTER_PATH_VOCABULARY[$pathType])) {
            return array_keys(self::CHARACTER_PATH_VOCABULARY[$pathType]);
        }

        return [];
    }

    /**
     * Get all available intents.
     *
     * @return array<string>
     */
    public function getAvailableIntents(): array
    {
        return array_keys(self::INTENT_TO_PATH);
    }

    /**
     * Build a complete path from intent with default parameters.
     *
     * @param string $intent The character's intent
     * @param array $parameters Optional parameters for the path
     * @return string Complete path description
     */
    public function buildPathFromIntent(string $intent, array $parameters = []): string
    {
        $pathSuggestion = $this->suggestPathForIntent($intent);

        return $this->buildCharacterPath(
            $pathSuggestion['path_type'],
            $pathSuggestion['variant'],
            $parameters
        );
    }

    /**
     * Build a prompt-ready path block.
     *
     * @param string $pathType Path category
     * @param string $variant Path variant
     * @param array $parameters Optional parameters
     * @return string Formatted block for video generation prompts
     */
    public function buildPromptBlock(string $pathType, string $variant, array $parameters = []): string
    {
        $pathDescription = $this->buildCharacterPath($pathType, $variant, $parameters);
        $duration = $this->estimatePathDuration($pathType);

        return "[CHARACTER-PATH: {$pathType}/{$variant}] {$pathDescription} (estimated {$duration['min']}-{$duration['max']}s)";
    }

    /**
     * Check if the camera movement follows the character movement.
     *
     * @param string $pathDescription Character movement
     * @param string $cameraMovement Camera movement
     * @return bool True if camera follows character
     */
    protected function doesCameraFollowMovement(string $pathDescription, string $cameraMovement): bool
    {
        $pathLower = strtolower($pathDescription);
        $cameraLower = strtolower($cameraMovement);

        // Tracking/following keywords
        $followKeywords = ['track', 'follow', 'dolly', 'move with', 'push in', 'pull back'];

        foreach ($followKeywords as $keyword) {
            if (str_contains($cameraLower, $keyword)) {
                return true;
            }
        }

        // Directional alignment
        if (str_contains($pathLower, 'toward camera') && str_contains($cameraLower, 'push')) {
            return true;
        }

        if (str_contains($pathLower, 'away from camera') && str_contains($cameraLower, 'pull')) {
            return true;
        }

        if (str_contains($pathLower, 'left to right') && str_contains($cameraLower, 'pan right')) {
            return true;
        }

        if (str_contains($pathLower, 'right to left') && str_contains($cameraLower, 'pan left')) {
            return true;
        }

        return false;
    }

    /**
     * Verify all vocabulary entries have proper structure.
     *
     * @return array{valid: bool, issues: array}
     */
    public function validateVocabulary(): array
    {
        $issues = [];

        foreach (self::CHARACTER_PATH_VOCABULARY as $pathType => $variants) {
            if (!is_array($variants)) {
                $issues[] = "Path type '{$pathType}' must have variant array";
                continue;
            }

            foreach ($variants as $variant => $description) {
                if (!is_string($description)) {
                    $issues[] = "Variant '{$pathType}/{$variant}' must have string description";
                }

                if (strlen($description) < 10) {
                    $issues[] = "Variant '{$pathType}/{$variant}' description too short";
                }
            }

            // Check duration estimate exists
            if (!isset(self::PATH_DURATION_ESTIMATES[$pathType])) {
                $issues[] = "Path type '{$pathType}' missing duration estimate";
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }
}
