<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * VideoTemporalService
 *
 * Provides temporal beat structuring for video prompts with timing markers.
 * Ensures video generation models receive properly timed action sequences
 * that fit within the requested duration.
 *
 * Research confirmed: Simple actions need 2-3 seconds, complex motions need 4-5 seconds.
 * Overpacking a clip with too many actions results in rushed, artificial-looking motion.
 *
 * VID-02: Video prompts include temporal progression with beat-by-beat timing
 */
class VideoTemporalService
{
    /**
     * Guidelines for temporal beat durations by action type.
     *
     * Each action type has a min/max duration range that produces natural-looking motion:
     * - simple_action: Quick, single motions (turn head, blink, nod)
     * - complex_motion: Multi-step movements requiring coordination (walk, sit, gesture)
     * - emotional_beat: Internal state changes visible in expression (realization, recognition)
     * - camera_movement: Physical camera motion through space (dolly, pan, crane)
     */
    public const TEMPORAL_BEAT_GUIDELINES = [
        'simple_action' => [
            'min_duration' => 2,
            'max_duration' => 3,
            'examples' => ['turn head', 'blink', 'nod', 'glance', 'smile'],
        ],
        'complex_motion' => [
            'min_duration' => 4,
            'max_duration' => 5,
            'examples' => ['walk across', 'sit down', 'gesture', 'stand up', 'reach for object'],
        ],
        'emotional_beat' => [
            'min_duration' => 3,
            'max_duration' => 4,
            'examples' => ['realization', 'recognition', 'reaction', 'dawning horror', 'subtle smile spreading'],
        ],
        'camera_movement' => [
            'min_duration' => 3,
            'max_duration' => 8,
            'examples' => ['dolly in', 'pan across', 'crane up', 'tracking shot', 'zoom'],
        ],
    ];

    /**
     * Maximum actions allowed per clip duration.
     *
     * Prevents overpacking clips with too many actions, which results in
     * rushed, unnatural motion. Less is more for believable video.
     */
    public const MAX_ACTIONS_PER_DURATION = [
        5 => 2,   // 5 seconds: max 2 actions
        10 => 4,  // 10 seconds: max 4 actions
        15 => 5,  // 15 seconds: max 5 actions
    ];

    /**
     * Build formatted temporal beats string for video prompt.
     *
     * Takes an array of beats with actions and durations, returns formatted
     * time-range string suitable for video generation prompts.
     *
     * @param array<array{action: string, duration: int}> $beats Array of beat definitions
     * @param int $totalDuration Total clip duration in seconds
     * @return string Formatted beats string: "[00:00-00:02] action. [00:02-00:05] next action."
     */
    public function buildTemporalBeats(array $beats, int $totalDuration): string
    {
        if (empty($beats)) {
            return '';
        }

        $formatted = [];
        $currentTime = 0;

        foreach ($beats as $beat) {
            $action = $beat['action'] ?? '';
            $duration = $beat['duration'] ?? 2;

            if (empty($action)) {
                continue;
            }

            // Calculate end time, but don't exceed total duration
            $endTime = min($currentTime + $duration, $totalDuration);

            // Format the time range
            $timeRange = $this->formatTimeRange($currentTime, $endTime);
            $formatted[] = "{$timeRange} {$action}";

            $currentTime = $endTime;

            // Stop if we've reached the end
            if ($currentTime >= $totalDuration) {
                break;
            }
        }

        Log::debug('VideoTemporalService: Built temporal beats', [
            'beat_count' => count($formatted),
            'total_duration' => $totalDuration,
            'final_time' => $currentTime,
        ]);

        return implode('. ', $formatted) . '.';
    }

    /**
     * Validate beats against duration constraints.
     *
     * Checks that the beats don't exceed maximum action count for the duration
     * and that total beat time doesn't exceed clip duration.
     *
     * @param array<array{action: string, duration: int}> $beats Array of beat definitions
     * @param int $totalDuration Total clip duration in seconds
     * @return array{valid: bool, warnings: array<string>} Validation result with warnings
     */
    public function validateBeatsForDuration(array $beats, int $totalDuration): array
    {
        $warnings = [];
        $valid = true;

        // Check action count against max allowed
        $maxActions = $this->getMaxActionsForDuration($totalDuration);
        $beatCount = count($beats);

        if ($beatCount > $maxActions) {
            $warnings[] = "Too many actions ({$beatCount}) for {$totalDuration}s clip. Maximum recommended: {$maxActions}";
            $valid = false;
        }

        // Check total beat duration
        $totalBeatDuration = 0;
        foreach ($beats as $beat) {
            $totalBeatDuration += $beat['duration'] ?? 2;
        }

        if ($totalBeatDuration > $totalDuration) {
            $warnings[] = "Beat durations ({$totalBeatDuration}s) exceed clip duration ({$totalDuration}s)";
            $valid = false;
        }

        // Check for empty actions
        $emptyActions = 0;
        foreach ($beats as $beat) {
            if (empty($beat['action'])) {
                $emptyActions++;
            }
        }

        if ($emptyActions > 0) {
            $warnings[] = "{$emptyActions} beat(s) have empty actions";
            $valid = false;
        }

        Log::debug('VideoTemporalService: Validated beats', [
            'valid' => $valid,
            'beat_count' => $beatCount,
            'max_allowed' => $maxActions,
            'total_duration' => $totalDuration,
            'total_beat_duration' => $totalBeatDuration,
            'warnings' => $warnings,
        ]);

        return [
            'valid' => $valid,
            'warnings' => $warnings,
        ];
    }

    /**
     * Suggest duration for an action type.
     *
     * Returns the midpoint of the recommended duration range for the action type.
     *
     * @param string $actionType One of: simple_action, complex_motion, emotional_beat, camera_movement
     * @return int Suggested duration in seconds
     */
    public function suggestBeatDuration(string $actionType): int
    {
        $actionType = strtolower(trim($actionType));

        if (!isset(self::TEMPORAL_BEAT_GUIDELINES[$actionType])) {
            Log::debug('VideoTemporalService: Unknown action type, using default', [
                'action_type' => $actionType,
                'available' => array_keys(self::TEMPORAL_BEAT_GUIDELINES),
            ]);

            // Default to simple_action midpoint
            return 2;
        }

        $guidelines = self::TEMPORAL_BEAT_GUIDELINES[$actionType];
        $min = $guidelines['min_duration'];
        $max = $guidelines['max_duration'];

        // Return midpoint rounded down
        return (int) floor(($min + $max) / 2);
    }

    /**
     * Format time range as "[MM:SS-MM:SS]" string.
     *
     * @param int $startSeconds Start time in seconds
     * @param int $endSeconds End time in seconds
     * @return string Formatted time range "[00:00-00:03]"
     */
    public function formatTimeRange(int $startSeconds, int $endSeconds): string
    {
        $startFormatted = $this->formatSeconds($startSeconds);
        $endFormatted = $this->formatSeconds($endSeconds);

        return "[{$startFormatted}-{$endFormatted}]";
    }

    /**
     * Format seconds as MM:SS string.
     *
     * @param int $seconds Total seconds
     * @return string Formatted time "MM:SS"
     */
    private function formatSeconds(int $seconds): string
    {
        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Get maximum actions allowed for a duration.
     *
     * Uses MAX_ACTIONS_PER_DURATION thresholds. For durations between
     * defined thresholds, uses the lower threshold's limit.
     *
     * @param int $duration Duration in seconds
     * @return int Maximum recommended actions
     */
    private function getMaxActionsForDuration(int $duration): int
    {
        // Find the appropriate threshold
        $thresholds = array_keys(self::MAX_ACTIONS_PER_DURATION);
        sort($thresholds);

        $maxActions = 1; // Minimum of 1 action

        foreach ($thresholds as $threshold) {
            if ($duration >= $threshold) {
                $maxActions = self::MAX_ACTIONS_PER_DURATION[$threshold];
            }
        }

        return $maxActions;
    }

    /**
     * Get all available action types.
     *
     * @return array<string>
     */
    public function getAvailableActionTypes(): array
    {
        return array_keys(self::TEMPORAL_BEAT_GUIDELINES);
    }

    /**
     * Classify an action description into an action type.
     *
     * Uses simple keyword matching to classify actions.
     *
     * @param string $actionDescription Description of the action
     * @return string Action type (simple_action, complex_motion, emotional_beat, camera_movement)
     */
    public function classifyAction(string $actionDescription): string
    {
        $action = strtolower($actionDescription);

        // Camera movement keywords
        $cameraKeywords = ['dolly', 'pan', 'crane', 'track', 'zoom', 'tilt', 'camera'];
        foreach ($cameraKeywords as $keyword) {
            if (str_contains($action, $keyword)) {
                return 'camera_movement';
            }
        }

        // Emotional beat keywords
        $emotionalKeywords = ['realize', 'recognize', 'react', 'horror', 'dawning', 'spreading', 'emotion', 'feel'];
        foreach ($emotionalKeywords as $keyword) {
            if (str_contains($action, $keyword)) {
                return 'emotional_beat';
            }
        }

        // Complex motion keywords
        $complexKeywords = ['walk', 'sit', 'stand', 'reach', 'run', 'climb', 'descend', 'gesture', 'embrace'];
        foreach ($complexKeywords as $keyword) {
            if (str_contains($action, $keyword)) {
                return 'complex_motion';
            }
        }

        // Default to simple action
        return 'simple_action';
    }
}
