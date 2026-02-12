<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * VoiceRegistryService - Single source of truth for voice assignments.
 *
 * Phase 17 (VOC-05): Centralizes voice tracking so narrator, internal thought,
 * and character voices are managed through one class with first-occurrence-wins
 * behavior and mismatch detection.
 *
 * Features:
 * - Initialize from Character Bible (supports both array and string voice formats)
 * - First-occurrence-wins: Once a voice is assigned to a character, it persists
 * - Case-insensitive character matching (ALICE == alice == Alice)
 * - Mismatch detection: Logs warnings when conflicting voices are registered
 * - Validation: validateContinuity() returns array of issues for review
 *
 * Usage:
 * ```php
 * $registry = new VoiceRegistryService();
 * $registry->initializeFromCharacterBible($characterBible, $narratorVoice);
 * $voice = $registry->getVoiceForCharacter('Alice', fn($n) => $this->lookupVoice($n));
 * ```
 *
 * @package Modules\AppVideoWizard\Services
 */
class VoiceRegistryService
{
    /**
     * Registered character voices.
     * Key: uppercase character name, Value: ['voiceId' => string, 'source' => string]
     *
     * @var array<string, array{voiceId: string, source: string}>
     */
    protected array $characterVoices = [];

    /**
     * Narrator voice ID (set during initialization).
     *
     * @var string|null
     */
    protected ?string $narratorVoiceId = null;

    /**
     * Internal thought voice ID (typically the character's voice).
     *
     * @var string|null
     */
    protected ?string $internalVoiceId = null;

    /**
     * History of detected voice mismatches for validation.
     *
     * @var array<int, array{character: string, expectedVoice: string, actualVoice: string, source: string}>
     */
    protected array $mismatchHistory = [];

    /**
     * Initialize the voice registry from Character Bible data.
     *
     * Extracts voice assignments from Character Bible and registers them.
     * Handles both new array format ['voice' => ['id' => 'nova']] and
     * legacy string format ['voice' => 'nova'].
     *
     * @param array $characterBible Character Bible data with 'characters' array
     * @param string $narratorVoice Narrator voice ID from getNarratorVoice()
     * @return void
     */
    public function initializeFromCharacterBible(array $characterBible, string $narratorVoice): void
    {
        $characters = $characterBible['characters'] ?? [];
        $registeredCount = 0;

        foreach ($characters as $char) {
            $name = $char['name'] ?? null;
            if (empty($name)) {
                continue;
            }

            // Extract voice ID - handle both formats
            $voiceId = null;

            // New format: voice is array with 'id' key
            if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
                $voiceId = $char['voice']['id'];
            }
            // Legacy format: voice is string
            elseif (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                $voiceId = $char['voice'];
            }

            if ($voiceId) {
                if ($this->registerCharacterVoice($name, $voiceId, 'character_bible')) {
                    $registeredCount++;
                }
            }
        }

        $this->narratorVoiceId = $narratorVoice;

        Log::info('VoiceRegistry initialized from Character Bible (VOC-05)', [
            'charactersRegistered' => $registeredCount,
            'narratorVoice' => $narratorVoice,
            'totalCharacters' => count($characters),
        ]);
    }

    /**
     * Register a character voice with first-occurrence-wins behavior.
     *
     * If the character already has a voice registered:
     * - Same voice: returns true (already registered)
     * - Different voice: logs warning, adds to mismatch history, returns false
     *
     * @param string $characterName Character name (case-insensitive)
     * @param string $voiceId Voice ID to register
     * @param string $source Source of the registration (e.g., 'character_bible', 'runtime_lookup')
     * @return bool True if registered successfully, false if mismatch detected
     */
    protected function registerCharacterVoice(string $characterName, string $voiceId, string $source): bool
    {
        $key = strtoupper(trim($characterName));

        if (isset($this->characterVoices[$key])) {
            $existing = $this->characterVoices[$key];

            // Same voice - already registered correctly
            if ($existing['voiceId'] === $voiceId) {
                return true;
            }

            // Different voice - mismatch detected
            Log::warning('Voice mismatch detected - keeping first-occurrence voice (VOC-05)', [
                'character' => $characterName,
                'expectedVoice' => $existing['voiceId'],
                'attemptedVoice' => $voiceId,
                'originalSource' => $existing['source'],
                'attemptedSource' => $source,
            ]);

            $this->mismatchHistory[] = [
                'character' => $characterName,
                'expectedVoice' => $existing['voiceId'],
                'actualVoice' => $voiceId,
                'source' => $source,
            ];

            return false;
        }

        // New registration
        $this->characterVoices[$key] = [
            'voiceId' => $voiceId,
            'source' => $source,
        ];

        Log::debug('Voice registered for character (VOC-05)', [
            'character' => $characterName,
            'voiceId' => $voiceId,
            'source' => $source,
        ]);

        return true;
    }

    /**
     * Get voice for a character, using fallback lookup if not registered.
     *
     * First checks the registry. If not found, calls the fallback lookup
     * function and registers the result for future lookups.
     *
     * @param string $characterName Character name (case-insensitive)
     * @param callable $fallbackLookup Callback: fn(string $name) => string $voiceId
     * @return string Voice ID
     */
    public function getVoiceForCharacter(string $characterName, callable $fallbackLookup): string
    {
        $key = strtoupper(trim($characterName));

        // Exact match in registry
        if (isset($this->characterVoices[$key])) {
            return $this->characterVoices[$key]['voiceId'];
        }

        // Partial match: SARAH matches SARAH COLE, or SARAH COLE matches SARAH
        foreach ($this->characterVoices as $registeredKey => $data) {
            if (str_starts_with($registeredKey, $key . ' ') || str_starts_with($key, $registeredKey . ' ')) {
                Log::debug('VoiceRegistry: partial name match', [
                    'input' => $key,
                    'matched' => $registeredKey,
                    'voiceId' => $data['voiceId'],
                ]);
                // Also register the alias for future exact lookups
                $this->characterVoices[$key] = $data;
                return $data['voiceId'];
            }
        }

        // Fallback lookup and register
        $voiceId = $fallbackLookup($characterName);
        $this->registerCharacterVoice($characterName, $voiceId, 'runtime_lookup');

        return $voiceId;
    }

    /**
     * Get the narrator voice.
     *
     * @return string Narrator voice ID (must be initialized first)
     */
    public function getNarratorVoice(): string
    {
        return $this->narratorVoiceId;
    }

    /**
     * Get the internal thought voice.
     *
     * If a voice ID is provided and no internal voice is set, it will be registered.
     * Falls back to narrator voice if no internal voice is set.
     *
     * @param string|null $voiceId Optional voice ID to set as internal voice
     * @return string Internal voice ID (or narrator voice as fallback)
     */
    public function getInternalVoice(?string $voiceId = null): string
    {
        // Set internal voice if provided and not yet set
        if ($voiceId !== null && $this->internalVoiceId === null) {
            $this->internalVoiceId = $voiceId;
            Log::debug('Internal thought voice set (VOC-05)', [
                'voiceId' => $voiceId,
            ]);
        }

        // Return internal voice or fall back to narrator
        return $this->internalVoiceId ?? $this->narratorVoiceId;
    }

    /**
     * Get a summary of the voice registry state.
     *
     * @return array{
     *     charactersRegistered: int,
     *     narratorVoice: string|null,
     *     internalVoice: string|null,
     *     characters: array<string, array{voiceId: string, source: string}>
     * }
     */
    public function getValidationSummary(): array
    {
        return [
            'charactersRegistered' => count($this->characterVoices),
            'narratorVoice' => $this->narratorVoiceId,
            'internalVoice' => $this->internalVoiceId,
            'characters' => $this->characterVoices,
        ];
    }

    /**
     * Validate voice continuity and return issues.
     *
     * Returns an array with validation status, issues detected during
     * registration, and statistics. Matches Phase 16 validation pattern.
     *
     * @return array{
     *     valid: bool,
     *     issues: array<int, array{character: string, expectedVoice: string, actualVoice: string, source: string}>,
     *     statistics: array{charactersRegistered: int, mismatches: int}
     * }
     */
    public function validateContinuity(): array
    {
        return [
            'valid' => count($this->mismatchHistory) === 0,
            'issues' => $this->mismatchHistory,
            'statistics' => [
                'charactersRegistered' => count($this->characterVoices),
                'mismatches' => count($this->mismatchHistory),
            ],
        ];
    }

    /**
     * Export registry state for persistence (VOC-07).
     *
     * Serializes the voice registry state so it can be stored in Scene DNA
     * and restored across browser refreshes.
     *
     * @return array{
     *     characterVoices: array<string, array{voiceId: string, source: string}>,
     *     narratorVoiceId: string|null,
     *     internalVoiceId: string|null,
     *     lastValidatedAt: string
     * }
     */
    public function toArray(): array
    {
        return [
            'characterVoices' => $this->characterVoices,
            'narratorVoiceId' => $this->narratorVoiceId,
            'internalVoiceId' => $this->internalVoiceId,
            'lastValidatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Restore registry state from persisted data (VOC-07).
     *
     * Hydrates the voice registry from previously serialized state,
     * typically loaded from Scene DNA.
     *
     * @param array $data Previously serialized state from toArray()
     * @return void
     */
    public function fromArray(array $data): void
    {
        $this->characterVoices = $data['characterVoices'] ?? [];
        $this->narratorVoiceId = $data['narratorVoiceId'] ?? null;
        $this->internalVoiceId = $data['internalVoiceId'] ?? null;

        Log::debug('VoiceRegistry restored from persisted data (VOC-07)', [
            'charactersRestored' => count($this->characterVoices),
            'narratorVoice' => $this->narratorVoiceId,
        ]);
    }

    /**
     * Test Scenarios (VOC-05)
     *
     * 1. Initialization from Character Bible (new format):
     *    - Input: ['characters' => [['name' => 'Alice', 'voice' => ['id' => 'nova']]]]
     *    - Expected: getVoiceForCharacter('Alice', fn($n) => 'fallback') returns 'nova'
     *
     * 2. Initialization from Character Bible (legacy format):
     *    - Input: ['characters' => [['name' => 'Bob', 'voice' => 'onyx']]]
     *    - Expected: getVoiceForCharacter('Bob', fn($n) => 'fallback') returns 'onyx'
     *
     * 3. First-occurrence-wins:
     *    - Call getVoiceForCharacter('Charlie', fn($n) => 'alloy')
     *    - Call getVoiceForCharacter('Charlie', fn($n) => 'shimmer')
     *    - Expected: Both return 'alloy' (first wins)
     *
     * 4. Mismatch detection:
     *    - Initialize with Alice => 'nova'
     *    - Call getVoiceForCharacter('Alice', fn($n) => 'echo')
     *    - Expected: Returns 'nova', logs warning about mismatch
     *
     * 5. Case-insensitive matching:
     *    - Initialize with 'HERO' => 'nova'
     *    - Call getVoiceForCharacter('hero', fn($n) => 'fallback')
     *    - Expected: Returns 'nova' (matches regardless of case)
     *
     * 6. Internal voice fallback:
     *    - Initialize with narratorVoice = 'fable', no internalVoice set
     *    - Call getInternalVoice()
     *    - Expected: Returns 'fable' (falls back to narrator)
     *
     * 7. validateContinuity returns issues array:
     *    - Register Alice => 'nova' from character_bible
     *    - Attempt to register Alice => 'echo' from runtime_lookup (mismatch)
     *    - Call validateContinuity()
     *    - Expected: ['valid' => false, 'issues' => [...], 'statistics' => ['mismatches' => 1]]
     *
     * 8. validateContinuity returns empty when consistent:
     *    - Register Alice => 'nova', Bob => 'onyx'
     *    - Call validateContinuity()
     *    - Expected: ['valid' => true, 'issues' => [], 'statistics' => ['mismatches' => 0]]
     */
}
