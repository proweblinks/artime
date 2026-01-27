<?php

namespace Modules\AppVideoWizard\Services\Voice;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\VoiceRegistryService;

/**
 * VoiceContinuityValidator - Detects voice drift between scenes (VOC-08).
 *
 * Validates that character voice assignments remain consistent across scenes.
 * Reports issues when a character's voice changes unexpectedly.
 *
 * Usage:
 * ```php
 * $validator = app(VoiceContinuityValidator::class);
 * $issues = $validator->validateSceneTransition($previousScene, $currentScene);
 * if (!$issues['valid']) {
 *     // Display warnings to user
 * }
 * ```
 */
class VoiceContinuityValidator
{
    /**
     * Issue types for categorization.
     */
    public const ISSUE_VOICE_DRIFT = 'voice_drift';
    public const ISSUE_VOICE_MISSING = 'voice_missing';
    public const ISSUE_VOICE_ADDED = 'voice_added';

    /**
     * Validate voice continuity between two consecutive scenes.
     *
     * @param array $previousScene Scene data with voiceRegistry or shots with voice assignments
     * @param array $currentScene Scene data to validate against previous
     * @return array{valid: bool, issues: array, statistics: array}
     */
    public function validateSceneTransition(array $previousScene, array $currentScene): array
    {
        $issues = [];
        $previousVoices = $this->extractVoiceAssignments($previousScene);
        $currentVoices = $this->extractVoiceAssignments($currentScene);

        // Check for voice drift (same character, different voice)
        foreach ($currentVoices as $character => $voiceId) {
            if (isset($previousVoices[$character])) {
                if ($previousVoices[$character] !== $voiceId) {
                    $issues[] = [
                        'type' => self::ISSUE_VOICE_DRIFT,
                        'character' => $character,
                        'expected' => $previousVoices[$character],
                        'actual' => $voiceId,
                        'severity' => 'warning',
                        'message' => "Character '{$character}' voice changed from '{$previousVoices[$character]}' to '{$voiceId}'",
                    ];
                }
            } else {
                // New character in this scene (not necessarily an issue)
                $issues[] = [
                    'type' => self::ISSUE_VOICE_ADDED,
                    'character' => $character,
                    'voiceId' => $voiceId,
                    'severity' => 'info',
                    'message' => "Character '{$character}' first appears with voice '{$voiceId}'",
                ];
            }
        }

        // Check for missing characters (were in previous, not in current)
        foreach ($previousVoices as $character => $voiceId) {
            if (!isset($currentVoices[$character])) {
                $issues[] = [
                    'type' => self::ISSUE_VOICE_MISSING,
                    'character' => $character,
                    'voiceId' => $voiceId,
                    'severity' => 'info',
                    'message' => "Character '{$character}' not present in this scene",
                ];
            }
        }

        $hasErrors = count(array_filter($issues, fn($i) => $i['severity'] === 'warning' || $i['severity'] === 'error')) > 0;

        Log::debug('VoiceContinuityValidator: Scene transition validated (VOC-08)', [
            'previousSceneId' => $previousScene['id'] ?? 'unknown',
            'currentSceneId' => $currentScene['id'] ?? 'unknown',
            'issueCount' => count($issues),
            'valid' => !$hasErrors,
        ]);

        return [
            'valid' => !$hasErrors,
            'issues' => $issues,
            'statistics' => [
                'previousCharacters' => count($previousVoices),
                'currentCharacters' => count($currentVoices),
                'driftCount' => count(array_filter($issues, fn($i) => $i['type'] === self::ISSUE_VOICE_DRIFT)),
            ],
        ];
    }

    /**
     * Validate voice continuity across all scenes.
     *
     * @param array $scenes Array of scenes in order
     * @param array $characterBible Character Bible data for reference voice lookup
     * @return array{valid: bool, sceneIssues: array, summary: array}
     */
    public function validateAllScenes(array $scenes, array $characterBible = []): array
    {
        if (count($scenes) < 2) {
            return [
                'valid' => true,
                'sceneIssues' => [],
                'summary' => [
                    'totalScenes' => count($scenes),
                    'totalIssues' => 0,
                    'driftCount' => 0,
                ],
            ];
        }

        $sceneIssues = [];
        $totalDriftCount = 0;

        // Build reference registry from Character Bible
        $registry = app(VoiceRegistryService::class);
        if (!empty($characterBible)) {
            $narratorVoice = $this->findNarratorVoice($characterBible);
            $registry->initializeFromCharacterBible($characterBible, $narratorVoice);
        }

        // Validate each scene transition
        for ($i = 1; $i < count($scenes); $i++) {
            $result = $this->validateSceneTransition($scenes[$i - 1], $scenes[$i]);

            if (!$result['valid'] || !empty($result['issues'])) {
                $sceneIssues[] = [
                    'fromSceneIndex' => $i - 1,
                    'toSceneIndex' => $i,
                    'fromSceneId' => $scenes[$i - 1]['id'] ?? "scene-{$i}",
                    'toSceneId' => $scenes[$i]['id'] ?? "scene-" . ($i + 1),
                    'issues' => $result['issues'],
                ];

                $totalDriftCount += $result['statistics']['driftCount'];
            }
        }

        $hasErrors = $totalDriftCount > 0;

        Log::info('VoiceContinuityValidator: All scenes validated (VOC-08)', [
            'totalScenes' => count($scenes),
            'transitionsWithIssues' => count($sceneIssues),
            'totalDriftCount' => $totalDriftCount,
            'valid' => !$hasErrors,
        ]);

        return [
            'valid' => !$hasErrors,
            'sceneIssues' => $sceneIssues,
            'summary' => [
                'totalScenes' => count($scenes),
                'totalIssues' => array_sum(array_map(fn($s) => count($s['issues']), $sceneIssues)),
                'driftCount' => $totalDriftCount,
            ],
        ];
    }

    /**
     * Extract voice assignments from scene data.
     *
     * Handles multiple formats:
     * - sceneDNA.voiceRegistry.characterVoices (preferred)
     * - shots[].speakers[].voiceId
     * - shots[].voiceId (legacy single speaker)
     *
     * @param array $scene Scene data
     * @return array<string, string> Map of uppercase character name => voiceId
     */
    protected function extractVoiceAssignments(array $scene): array
    {
        $voices = [];

        // Try voiceRegistry first (from Plan 01)
        $registry = $scene['sceneDNA']['voiceRegistry']['characterVoices'] ??
                    $scene['voiceRegistry']['characterVoices'] ?? [];

        foreach ($registry as $character => $data) {
            $voiceId = is_array($data) ? ($data['voiceId'] ?? null) : $data;
            if ($voiceId) {
                $voices[strtoupper($character)] = $voiceId;
            }
        }

        // If no registry, extract from shots
        if (empty($voices)) {
            foreach ($scene['shots'] ?? [] as $shot) {
                // Multi-speaker format
                foreach ($shot['speakers'] ?? [] as $speaker) {
                    $name = strtoupper(trim($speaker['name'] ?? ''));
                    $voiceId = $speaker['voiceId'] ?? null;
                    if ($name && $voiceId && !isset($voices[$name])) {
                        $voices[$name] = $voiceId;
                    }
                }

                // Legacy single speaker format
                $character = strtoupper(trim($shot['speakingCharacter'] ?? ''));
                $voiceId = $shot['voiceId'] ?? null;
                if ($character && $voiceId && !isset($voices[$character])) {
                    $voices[$character] = $voiceId;
                }
            }
        }

        return $voices;
    }

    /**
     * Find narrator voice from Character Bible.
     *
     * @param array $characterBible Character Bible data
     * @return string Default narrator voice
     */
    protected function findNarratorVoice(array $characterBible): string
    {
        foreach ($characterBible['characters'] ?? [] as $char) {
            if ($char['isNarrator'] ?? false) {
                return $char['voice']['id'] ?? 'fable';
            }
        }
        return 'fable'; // Default narrator voice
    }
}
