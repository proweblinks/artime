<?php

namespace Modules\AppVideoWizard\App\Services;

use Illuminate\Support\Facades\Log;

/**
 * DialogueSceneDecomposerService
 *
 * Decomposes multi-character dialogue scenes into Shot/Reverse Shot pattern.
 * Each shot features ONE character speaking their line, enabling Multitalk lip-sync.
 *
 * Hollywood Pattern:
 * - Two-shot establishing → CU Character A speaks → CU Character B responds → repeat
 * - Emotional intensity increases toward climax
 * - Reaction shots (silent) add breathing room
 *
 * @see ~/.claude/skills/hollywood-cinematography/references/dialogue-patterns.md
 */
class DialogueSceneDecomposerService
{
    /**
     * Minimum dialogue exchanges to trigger dialogue decomposition.
     */
    protected int $minDialogueExchanges = 2;

    /**
     * PHASE 4: 180-degree rule - camera stays on one side of the action line.
     * The axis is an imaginary line between the two characters.
     */
    protected string $axisLockSide = 'left';

    /**
     * Shot types for dialogue scenes, ordered by emotional intensity.
     */
    protected array $dialogueShotTypes = [
        'establishing' => ['intensity' => 0.1, 'forSpeaking' => false],
        'two-shot' => ['intensity' => 0.2, 'forSpeaking' => false],
        'wide' => ['intensity' => 0.25, 'forSpeaking' => true],
        'medium' => ['intensity' => 0.4, 'forSpeaking' => true],
        'over-the-shoulder' => ['intensity' => 0.5, 'forSpeaking' => true],
        'medium-close' => ['intensity' => 0.6, 'forSpeaking' => true],
        'close-up' => ['intensity' => 0.8, 'forSpeaking' => true],
        'extreme-close-up' => ['intensity' => 0.95, 'forSpeaking' => true],
    ];

    /**
     * Emotion keywords mapped to intensity values.
     */
    protected array $emotionIntensityMap = [
        // Low intensity (0.1-0.3)
        'calm' => 0.2,
        'neutral' => 0.25,
        'curious' => 0.3,
        'thoughtful' => 0.3,

        // Medium intensity (0.4-0.6)
        'concerned' => 0.45,
        'questioning' => 0.5,
        'determined' => 0.55,
        'serious' => 0.5,
        'hopeful' => 0.45,

        // High intensity (0.7-0.85)
        'urgent' => 0.75,
        'angry' => 0.8,
        'fearful' => 0.75,
        'desperate' => 0.8,
        'confrontational' => 0.85,

        // Peak intensity (0.9-1.0)
        'revelation' => 0.9,
        'climax' => 0.95,
        'shock' => 0.9,
        'emotional' => 0.85,
    ];

    /**
     * PHASE 4: Minimum coverage requirements for Hollywood-quality dialogue.
     */
    protected array $coverageRequirements = [
        // Required shot types
        'requiredTypes' => [
            'establishing' => 1,      // At least 1 establishing/two-shot
            'over-the-shoulder' => 2, // At least 2 OTS (one per character direction)
            'close-up' => 1,          // At least 1 close-up for emphasis
        ],

        // Per-character minimums
        'perCharacter' => [
            'speakingShots' => 1,     // Each character needs at least 1 speaking shot
            'coverage' => 0.3,        // Each character should have 30%+ of shots
        ],

        // Pattern requirements
        'patterns' => [
            'maxConsecutiveOTS' => 4, // Break up OTS with two-shot after 4
            'minVariety' => 3,        // At least 3 different shot types
        ],
    ];

    /**
     * PHASE 4: Shot type categories for coverage analysis.
     */
    protected array $shotTypeCategories = [
        'establishing' => ['establishing', 'two-shot', 'wide'],
        'ots' => ['over-the-shoulder', 'medium'],
        'closeup' => ['close-up', 'extreme-close-up', 'medium-close'],
        'reaction' => ['reaction'],
    ];

    /**
     * Detect if a scene contains multi-character dialogue.
     *
     * @param array $scene The scene data
     * @return bool True if scene has dialogue between multiple characters
     */
    public function isDialogueScene(array $scene): bool
    {
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            return false;
        }

        // Parse dialogue exchanges
        $exchanges = $this->parseDialogueExchanges($narration);

        // Check if we have multiple speakers with at least 2 exchanges
        $speakers = array_unique(array_column($exchanges, 'speaker'));

        return count($speakers) >= 2 && count($exchanges) >= $this->minDialogueExchanges;
    }

    /**
     * Parse narration text to extract dialogue exchanges.
     *
     * Supports formats:
     * - "SPEAKER: dialogue text"
     * - "SPEAKER NAME: dialogue text"
     * - [NARRATOR] narrator text (captured separately)
     *
     * @param string $narration The scene narration
     * @return array Array of ['exchanges' => array, 'narratorSegments' => array]
     */
    public function parseDialogueExchanges(string $narration): array
    {
        $exchanges = [];
        $narratorSegments = [];
        $lines = preg_split('/\n+/', trim($narration));

        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Capture narrator tags - don't skip them anymore
            if (preg_match('/^\[NARRATOR\]\s*(.*)$/i', $line, $narratorMatch)) {
                $narratorText = trim($narratorMatch[1]);
                if (!empty($narratorText)) {
                    $narratorSegments[] = [
                        'text' => $narratorText,
                        'position' => count($exchanges), // Position relative to dialogue
                        'lineIndex' => $lineIndex,
                        'type' => 'narrator',
                    ];
                }
                continue;
            }

            // Skip parenthetical directions
            if (preg_match('/^\(.+\)$/', $line)) {
                continue;
            }

            // Match "SPEAKER: text" or "Speaker Name: text"
            if (preg_match('/^([A-Z][A-Za-z\s\-\']+):\s*(.+)$/u', $line, $matches)) {
                $speaker = trim($matches[1]);
                $text = trim($matches[2]);

                // Capture narrator as speaker too
                if (strtoupper($speaker) === 'NARRATOR') {
                    if (!empty($text)) {
                        $narratorSegments[] = [
                            'text' => $text,
                            'position' => count($exchanges),
                            'lineIndex' => $lineIndex,
                            'type' => 'narrator',
                        ];
                    }
                    continue;
                }

                if (!empty($text)) {
                    $exchanges[] = [
                        'speaker' => $speaker,
                        'text' => $text,
                        'isNarrator' => false,
                        'wordCount' => str_word_count($text),
                    ];
                }
            }
        }

        return [
            'exchanges' => $exchanges,
            'narratorSegments' => $narratorSegments,
        ];
    }

    /**
     * Extract scene-level voiceover/narration text.
     * This captures the overall scene narration that should be distributed across shots.
     *
     * @param array $scene The scene data
     * @return array Narrator info with text and speechType
     */
    public function extractSceneNarration(array $scene): array
    {
        // Check for voiceover configuration
        $voiceover = $scene['voiceover'] ?? [];
        $speechType = $voiceover['speechType'] ?? $scene['speechType'] ?? 'narrator';

        // Get voiceover text if available
        $voiceoverText = $voiceover['text'] ?? null;

        // Get visual description as fallback
        $visualDescription = $scene['visualDescription'] ?? '';

        // Get scene narration field
        $sceneNarration = $scene['narration'] ?? '';

        return [
            'speechType' => $speechType,
            'voiceoverText' => $voiceoverText,
            'visualDescription' => $visualDescription,
            'narration' => $sceneNarration,
            'hasVoiceover' => !empty($voiceoverText),
        ];
    }

    /**
     * Decompose a dialogue scene into Shot/Reverse Shot pattern.
     *
     * @param array $scene The scene data
     * @param array $characterBible The Character Bible data
     * @param array $options Additional options
     * @return array Array of shots following dialogue pattern
     */
    public function decomposeDialogueScene(array $scene, array $characterBible = [], array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $parseResult = $this->parseDialogueExchanges($narration);

        // Handle both old and new return format for backwards compatibility
        if (isset($parseResult['exchanges'])) {
            $exchanges = $parseResult['exchanges'];
            $narratorSegments = $parseResult['narratorSegments'] ?? [];
        } else {
            // Old format (just exchanges array)
            $exchanges = $parseResult;
            $narratorSegments = [];
        }

        // Extract scene-level narration info
        $sceneNarrationInfo = $this->extractSceneNarration($scene);

        if (empty($exchanges)) {
            Log::warning('DialogueDecomposer: No dialogue exchanges found', [
                'sceneId' => $scene['id'] ?? 'unknown',
            ]);
            return [];
        }

        $shots = [];
        $speakers = array_unique(array_column($exchanges, 'speaker'));
        $totalExchanges = count($exchanges);

        // Build character lookup from Character Bible
        $characterLookup = $this->buildCharacterLookup($characterBible, $speakers);

        Log::info('DialogueDecomposer: Decomposing dialogue scene', [
            'sceneId' => $scene['id'] ?? 'unknown',
            'exchangeCount' => $totalExchanges,
            'speakers' => $speakers,
        ]);

        // SHOT 1: Establishing two-shot (if more than 2 exchanges)
        if ($totalExchanges > 2 && ($options['includeEstablishing'] ?? true)) {
            $shots[] = $this->createEstablishingShot($scene, $speakers, $characterLookup);
        }

        // Generate shots for each dialogue exchange
        foreach ($exchanges as $index => $exchange) {
            $position = $this->calculateDialoguePosition($index, $totalExchanges);
            $emotionalIntensity = $this->calculateEmotionalIntensity($exchange, $position, $scene);

            // Create the speaking shot with spatial data
            $shot = $this->createDialogueShot(
                $exchange,
                $index,
                $emotionalIntensity,
                $position,
                $characterLookup,
                $scene,
                $speakers // PHASE 4: Pass speakers for spatial calculation
            );

            $shots[] = $shot;

            // PHASE 4: Analyze listener emotion and determine if reaction needed
            $listener = $this->getOtherCharacter($exchange['speaker'], $characterLookup);
            $listenerData = $characterLookup[$listener] ?? [];

            $listenerEmotion = $this->analyzeListenerEmotion(
                $exchange['text'],
                $shot['expression'] ?? 'neutral',
                ['mood' => $scene['mood'] ?? 'neutral']
            );

            // Determine if this exchange warrants a reaction shot
            if ($this->shouldInsertReaction($exchange, $index, $totalExchanges, $listenerEmotion)) {
                if ($listener) {
                    $reactionShot = $this->buildReactionShot(
                        $listener,
                        $listenerData,
                        $listenerEmotion,
                        $shot, // Previous shot (what they're reacting to)
                        $shot['spatial'] ?? []
                    );

                    $shots[] = $reactionShot;

                    Log::debug('DialogueSceneDecomposer: Added strategic reaction shot', [
                        'listener' => $listener,
                        'emotion' => $listenerEmotion['emotion'],
                        'intensity' => $listenerEmotion['intensity'],
                        'exchange_index' => $index,
                    ]);
                }
            }
        }

        // Calculate durations based on dialogue length
        $shots = $this->calculateShotDurations($shots);

        // PHASE 4: Pair reverse shots for continuity validation
        $shots = $this->pairReverseShots($shots);

        // Assign shot indices
        foreach ($shots as $idx => &$shot) {
            $shot['shotIndex'] = $idx;
            $shot['totalShots'] = count($shots);
        }

        // PHASE 4: Validate and fix coverage
        $characters = array_keys($characterLookup);
        $coverageAnalysis = $this->analyzeCoverage($shots, $characters);

        if (!empty($coverageAnalysis['issues'])) {
            Log::info('DialogueSceneDecomposer: Coverage issues detected, applying fixes', [
                'issue_count' => count($coverageAnalysis['issues']),
                'issues' => array_column($coverageAnalysis['issues'], 'type'),
            ]);

            $shots = $this->fixCoverageIssues($shots, $coverageAnalysis, $characters, $characterLookup);

            // Re-analyze to confirm fixes
            $finalAnalysis = $this->analyzeCoverage($shots, $characters);

            if (!empty($finalAnalysis['issues'])) {
                Log::warning('DialogueSceneDecomposer: Some coverage issues remain after fixes', [
                    'remaining_issues' => count($finalAnalysis['issues']),
                ]);
            }
        }

        // Log final coverage summary
        Log::info('DialogueSceneDecomposer: Final coverage summary', [
            'total_shots' => count($shots),
            'by_category' => $coverageAnalysis['typeCategories'],
            'by_character' => $coverageAnalysis['byCharacter'],
            'unique_types' => count($coverageAnalysis['patterns']['uniqueTypes']),
        ]);

        // PHASE 4: Enhanced logging with spatial continuity info
        Log::info('DialogueDecomposer: Generated shots with spatial continuity', [
            'sceneId' => $scene['id'] ?? 'unknown',
            'shotCount' => count($shots),
            'speakingShots' => count(array_filter($shots, fn($s) => $s['useMultitalk'] ?? false)),
            'pairCount' => count(array_filter($shots, fn($s) => !empty($s['spatial']['pairId'] ?? null))),
        ]);

        // Distribute narrator segments and scene narration to shots
        $shots = $this->distributeNarrationToShots($shots, $narratorSegments, $sceneNarrationInfo);

        return $shots;
    }

    /**
     * Distribute narrator segments and scene-level narration to shots.
     * This ensures every shot has proper speech/text indication.
     *
     * @param array $shots The shots array
     * @param array $narratorSegments Parsed narrator segments
     * @param array $sceneNarrationInfo Scene-level narration info
     * @return array Shots with narration distributed
     */
    protected function distributeNarrationToShots(array $shots, array $narratorSegments, array $sceneNarrationInfo): array
    {
        $totalShots = count($shots);
        if ($totalShots === 0) {
            return $shots;
        }

        $speechType = $sceneNarrationInfo['speechType'] ?? 'narrator';
        $voiceoverText = $sceneNarrationInfo['voiceoverText'] ?? null;
        $visualDescription = $sceneNarrationInfo['visualDescription'] ?? '';

        // Distribute narrator segments to their corresponding positions
        foreach ($narratorSegments as $segment) {
            $position = $segment['position'] ?? 0;
            // Find the closest shot to this position
            $targetShotIndex = min($position, $totalShots - 1);

            if (isset($shots[$targetShotIndex])) {
                // Append to existing narration or create new
                $existingNarration = $shots[$targetShotIndex]['narration'] ?? '';
                $shots[$targetShotIndex]['narration'] = trim($existingNarration . ' ' . $segment['text']);
                $shots[$targetShotIndex]['hasNarratorText'] = true;
            }
        }

        // For shots without dialogue, monologue, or narration, add scene context
        foreach ($shots as $idx => &$shot) {
            // Add speech type info to all shots
            $shot['speechType'] = $speechType;

            // Determine what text type this shot has
            $hasDialogue = !empty($shot['dialogue']);
            $hasMonologue = !empty($shot['monologue']);
            $hasNarration = !empty($shot['narration']);
            $hasNarratorText = $shot['hasNarratorText'] ?? false;

            // Calculate speech indicator
            if ($hasDialogue || $hasMonologue) {
                $shot['speechIndicator'] = $shot['needsLipSync'] ?? false ? 'dialogue' : 'monologue';
            } elseif ($hasNarration || $hasNarratorText) {
                $shot['speechIndicator'] = 'narrator';
            } elseif ($speechType === 'narrator' && $voiceoverText) {
                // This shot is part of a narrator scene - mark it
                $shot['speechIndicator'] = 'narrator';
                $shot['partOfVoiceover'] = true;
            } else {
                // Silent shot (establishing, reaction, etc.)
                $shot['speechIndicator'] = 'silent';
            }

            // For first shot, add voiceover text if scene has it and shot has no dialogue
            if ($idx === 0 && $voiceoverText && !$hasDialogue && !$hasMonologue && !$hasNarration) {
                $shot['narration'] = $voiceoverText;
                $shot['hasNarratorText'] = true;
                $shot['speechIndicator'] = 'narrator';
            }

            // Add visual description snippet to shots without any text
            if (!$hasDialogue && !$hasMonologue && !$hasNarration && !empty($visualDescription)) {
                // Only add a portion of visual description based on shot position
                $descWords = explode(' ', $visualDescription);
                $wordsPerShot = max(5, intval(count($descWords) / max(1, $totalShots)));
                $startWord = $idx * $wordsPerShot;
                $shotDescWords = array_slice($descWords, $startWord, $wordsPerShot);
                if (!empty($shotDescWords)) {
                    $shot['visualContext'] = implode(' ', $shotDescWords);
                }
            }
        }

        Log::debug('DialogueDecomposer: Distributed narration to shots', [
            'totalShots' => $totalShots,
            'narratorSegments' => count($narratorSegments),
            'speechType' => $speechType,
            'hasVoiceover' => !empty($voiceoverText),
        ]);

        return $shots;
    }

    /**
     * Build character lookup from Character Bible.
     */
    protected function buildCharacterLookup(array $characterBible, array $speakers): array
    {
        $lookup = [];
        $characters = $characterBible['characters'] ?? [];

        foreach ($speakers as $speaker) {
            $speakerUpper = strtoupper(trim($speaker));
            $found = false;

            foreach ($characters as $index => $char) {
                $charName = strtoupper(trim($char['name'] ?? ''));
                if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                    $lookup[$speaker] = [
                        'characterIndex' => $index,
                        'name' => $char['name'] ?? $speaker,
                        'voiceId' => $this->getCharacterVoiceId($char),
                        'gender' => $char['gender'] ?? $char['voice']['gender'] ?? 'unknown',
                        'hasReference' => !empty($char['referenceImageBase64']),
                        'characterData' => $char,
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Create default entry for unknown speaker
                $lookup[$speaker] = [
                    'characterIndex' => null,
                    'name' => $speaker,
                    'voiceId' => $this->inferVoiceFromName($speaker),
                    'gender' => 'unknown',
                    'hasReference' => false,
                    'characterData' => null,
                ];
            }
        }

        return $lookup;
    }

    /**
     * Get voice ID for a character.
     */
    protected function getCharacterVoiceId(array $character): string
    {
        // Check for configured voice
        if (is_array($character['voice'] ?? null) && !empty($character['voice']['id'])) {
            return $character['voice']['id'];
        }
        if (is_string($character['voice'] ?? null) && !empty($character['voice'])) {
            return $character['voice'];
        }

        // Infer from gender
        $gender = strtolower($character['gender'] ?? $character['voice']['gender'] ?? '');
        if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
            return 'nova';
        } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
            return 'onyx';
        }

        // Default
        return 'echo';
    }

    /**
     * Infer voice from speaker name.
     */
    protected function inferVoiceFromName(string $name): string
    {
        // Common female names
        $femalePatterns = ['sarah', 'emma', 'anna', 'maria', 'lisa', 'nina', 'julia', 'sophia', 'elena', 'maya'];
        $nameLower = strtolower($name);

        foreach ($femalePatterns as $pattern) {
            if (str_contains($nameLower, $pattern)) {
                return 'nova';
            }
        }

        // Use hash for consistent assignment
        $hash = crc32(strtoupper($name));
        $voices = ['echo', 'onyx', 'nova', 'alloy'];
        return $voices[$hash % count($voices)];
    }

    /**
     * Calculate dialogue position (beginning, middle, climax, resolution).
     */
    protected function calculateDialoguePosition(int $index, int $total): string
    {
        $progress = $total > 1 ? $index / ($total - 1) : 0;

        if ($progress < 0.2) {
            return 'opening';
        } elseif ($progress < 0.5) {
            return 'building';
        } elseif ($progress < 0.8) {
            return 'climax';
        } else {
            return 'resolution';
        }
    }

    /**
     * Calculate emotional intensity for a dialogue exchange.
     */
    protected function calculateEmotionalIntensity(array $exchange, string $position, array $scene): float
    {
        // Base intensity from position
        $positionIntensity = match($position) {
            'opening' => 0.3,
            'building' => 0.5,
            'climax' => 0.85,
            'resolution' => 0.5,
            default => 0.5,
        };

        // Adjust based on scene mood
        $mood = strtolower($scene['mood'] ?? 'neutral');
        $moodIntensity = $this->emotionIntensityMap[$mood] ?? 0.5;

        // Adjust based on dialogue content (exclamations, questions increase intensity)
        $text = $exchange['text'] ?? '';
        $textModifier = 0;

        if (str_contains($text, '!')) {
            $textModifier += 0.15;
        }
        if (str_contains($text, '?')) {
            $textModifier += 0.05;
        }
        if (str_contains($text, '...')) {
            $textModifier -= 0.1; // Trailing off = lower intensity
        }

        // Combine factors
        $intensity = ($positionIntensity * 0.5) + ($moodIntensity * 0.3) + ($textModifier);

        return max(0.1, min(1.0, $intensity));
    }

    /**
     * PHASE 13 CAM-03: Analyze speaker's emotional state from dialogue text.
     * Returns emotion and intensity for per-speaker shot selection.
     *
     * Emotion detection priority:
     * 1. High-intensity (0.75+): angry, fearful, loving
     * 2. Medium-intensity (0.5-0.7): remorseful, excited, pleading
     * 3. Low-intensity (0.3-0.5): contemplative, sad
     * 4. Default: neutral (0.5)
     *
     * @param string $dialogueText The speaker's dialogue text
     * @return array ['emotion' => string, 'intensity' => float]
     */
    protected function analyzeSpeakerEmotion(string $dialogueText): array
    {
        $text = strtolower($dialogueText);

        // High-intensity emotions (0.75+)
        // Angry: yelling, screaming, hate, rage
        if (preg_match('/\b(yell|scream|hate|kill|furious|rage)\b/', $text) || substr_count($text, '!') >= 2) {
            return ['emotion' => 'angry', 'intensity' => 0.8];
        }

        // Fearful: scared, terrified, please don't
        if (preg_match('/\b(afraid|scared|terrified|help me)\b/', $text) || preg_match("/please don'?t/", $text)) {
            return ['emotion' => 'fearful', 'intensity' => 0.75];
        }

        // Loving: declarations of love
        if (preg_match('/\b(love|adore|marry|forever)\b/', $text)) {
            return ['emotion' => 'loving', 'intensity' => 0.75];
        }

        // Medium-intensity emotions (0.5-0.7)
        // Remorseful: apologies
        if (preg_match('/\b(sorry|regret|forgive|apologize)\b/', $text)) {
            return ['emotion' => 'remorseful', 'intensity' => 0.6];
        }

        // Excited: positive exclamations
        if (preg_match('/\b(amazing|incredible|finally)\b/', $text) || preg_match('/yes!/i', $text)) {
            return ['emotion' => 'excited', 'intensity' => 0.65];
        }

        // Pleading: begging, urgency
        if (preg_match('/\b(please|beg|need|must)\b/', $text)) {
            return ['emotion' => 'pleading', 'intensity' => 0.55];
        }

        // Low-intensity emotions (0.3-0.5)
        // Contemplative: thinking
        if (preg_match('/\b(think|consider|wonder|perhaps)\b/', $text)) {
            return ['emotion' => 'contemplative', 'intensity' => 0.4];
        }

        // Sad: trailing off
        if (str_contains($text, '...')) {
            return ['emotion' => 'sad', 'intensity' => 0.35];
        }

        // Default: neutral
        return ['emotion' => 'neutral', 'intensity' => 0.5];
    }

    /**
     * PHASE 4: Calculate camera position for dialogue shot.
     * Maintains 180-degree rule - camera stays on same side of axis.
     *
     * @param string $speakerName Current speaker
     * @param array $characters Array of [characterA, characterB]
     * @param string $shotType Type of shot (establishing, ots, close-up, etc.)
     * @return array Spatial data for the shot
     */
    protected function calculateSpatialData(string $speakerName, array $characters, string $shotType): array
    {
        // Determine which character is speaking (A or B)
        $isCharacterA = count($characters) >= 1 && strcasecmp($speakerName, $characters[0]) === 0;

        // Camera position follows 180-degree rule
        // When shooting Character A: camera is on left, A is screen-right, looks screen-left
        // When shooting Character B: camera is on left, B is screen-left, looks screen-right

        $spatial = [
            'cameraPosition' => $this->axisLockSide, // Always same side (180-degree rule)
            'cameraAngle' => $this->determineCameraAngle($shotType),
            'subjectPosition' => $isCharacterA ? 'right' : 'left', // A=right, B=left
            'eyeLineDirection' => $isCharacterA ? 'screen-left' : 'screen-right',
            'lookingAt' => $isCharacterA && count($characters) >= 2 ? $characters[1] : ($characters[0] ?? null),
            'reverseOf' => null, // Set later when pairing
            'pairId' => null,    // Set later when pairing
        ];

        return $spatial;
    }

    /**
     * PHASE 4: Determine camera angle based on shot type.
     *
     * @param string $shotType The shot type
     * @return string Camera angle description
     */
    protected function determineCameraAngle(string $shotType): string
    {
        return match($shotType) {
            'establishing', 'two-shot', 'wide' => 'frontal',
            'over-the-shoulder' => 'three-quarter',
            'medium', 'medium-close' => 'three-quarter',
            'close-up', 'extreme-close-up' => 'profile',
            default => 'three-quarter',
        };
    }

    /**
     * PHASE 4: Pair reverse shots in the dialogue sequence.
     * Links shots that form shot/reverse-shot pairs.
     *
     * @param array $shots Array of shots to process
     * @return array Shots with reverse pairing data
     */
    protected function pairReverseShots(array $shots): array
    {
        $pairCounter = 0;
        $lastSpeakerShot = [];

        foreach ($shots as $index => &$shot) {
            // Skip non-dialogue shots (establishing, reaction without speaker)
            if (empty($shot['speakingCharacter'])) {
                continue;
            }

            $speaker = $shot['speakingCharacter'];

            // Check if there's a previous shot from different speaker (reverse candidate)
            foreach ($lastSpeakerShot as $prevSpeaker => $prevIndex) {
                if ($prevSpeaker !== $speaker && $prevIndex !== null) {
                    // This is a reverse of the previous speaker's shot
                    $pairId = 'pair_' . $pairCounter;

                    // Ensure spatial array exists
                    if (!isset($shot['spatial'])) {
                        $shot['spatial'] = [];
                    }
                    if (!isset($shots[$prevIndex]['spatial'])) {
                        $shots[$prevIndex]['spatial'] = [];
                    }

                    // Link current shot to previous
                    $shot['spatial']['reverseOf'] = $prevIndex;
                    $shot['spatial']['pairId'] = $pairId;

                    // Link previous shot to current
                    $shots[$prevIndex]['spatial']['pairId'] = $pairId;

                    $pairCounter++;
                    break;
                }
            }

            // Track this speaker's latest shot
            $lastSpeakerShot[$speaker] = $index;
        }

        return $shots;
    }

    /**
     * PHASE 12: Validate 180-degree rule compliance across dialogue sequence.
     * Camera must stay on same side of axis; eyelines must oppose between speakers.
     *
     * @param array $shots Shots with spatial data
     * @return array Array of violations (empty if compliant)
     */
    protected function validate180DegreeRule(array $shots): array
    {
        $violations = [];
        $lastSpeaker = null;
        $lastEyeLine = null;

        foreach ($shots as $index => $shot) {
            // Skip non-dialogue shots (no speakingCharacter)
            if (empty($shot['speakingCharacter'])) {
                continue;
            }

            $spatial = $shot['spatial'] ?? [];
            $speaker = $shot['speakingCharacter'];
            $cameraPosition = $spatial['cameraPosition'] ?? null;
            $eyeLineDirection = $spatial['eyeLineDirection'] ?? null;

            // Check 1: Camera position must be consistent (axis lock)
            if ($cameraPosition !== null && $cameraPosition !== $this->axisLockSide) {
                $violations[] = [
                    'type' => 'axis_jump',
                    'shotIndex' => $index,
                    'speaker' => $speaker,
                    'expected' => $this->axisLockSide,
                    'actual' => $cameraPosition,
                    'message' => "Camera jumped axis: expected '{$this->axisLockSide}', got '{$cameraPosition}'",
                ];

                Log::warning('DialogueSceneDecomposer: 180-degree rule violation - axis jump', [
                    'shot_index' => $index,
                    'speaker' => $speaker,
                    'expected_camera' => $this->axisLockSide,
                    'actual_camera' => $cameraPosition,
                ]);
            }

            // Check 2: When speaker changes, eyeline should differ (opposite directions)
            if ($lastSpeaker !== null && $lastSpeaker !== $speaker) {
                if ($eyeLineDirection !== null && $lastEyeLine !== null) {
                    if ($eyeLineDirection === $lastEyeLine) {
                        $violations[] = [
                            'type' => 'eyeline_match',
                            'shotIndex' => $index,
                            'speaker' => $speaker,
                            'previousSpeaker' => $lastSpeaker,
                            'eyeLineDirection' => $eyeLineDirection,
                            'message' => "Both speakers look same direction ({$eyeLineDirection}) - breaks shot/reverse pattern",
                        ];

                        Log::warning('DialogueSceneDecomposer: 180-degree rule violation - eyeline match', [
                            'shot_index' => $index,
                            'speaker' => $speaker,
                            'previous_speaker' => $lastSpeaker,
                            'eyeline' => $eyeLineDirection,
                        ]);
                    }
                }
            }

            // Track for next iteration
            $lastSpeaker = $speaker;
            $lastEyeLine = $eyeLineDirection;
        }

        if (empty($violations)) {
            Log::debug('DialogueSceneDecomposer: 180-degree rule validation passed', [
                'shot_count' => count($shots),
            ]);
        }

        return $violations;
    }

    /**
     * PHASE 12: Enforce single character per shot constraint (FLOW-02).
     * Image generation model cannot reliably render multiple characters.
     * Converts two-shots to wide shots; ensures OTS shows only focus character.
     *
     * @param array $shots Shots to enforce constraint on
     * @return array Modified shots with single-character constraint applied
     */
    protected function enforceSingleCharacterConstraint(array $shots): array
    {
        $conversions = [];

        foreach ($shots as $index => &$shot) {
            $type = $shot['type'] ?? '';
            $charactersInShot = $shot['charactersInShot'] ?? [];
            $characterCount = count($charactersInShot);

            // Skip shots that already have single character
            if ($characterCount <= 1) {
                continue;
            }

            // Case 1: Two-shot or establishing with multiple characters
            if ($type === 'two-shot' || $type === 'establishing') {
                // Convert to wide shot with only the primary character
                $primaryCharacter = $charactersInShot[0] ?? null;

                $shot['type'] = 'wide';
                $shot['originalType'] = $type;
                $shot['charactersInShot'] = $primaryCharacter ? [$primaryCharacter] : [];
                $shot['singleCharacterEnforced'] = true;

                // Update visual description
                if (!empty($shot['visualDescription'])) {
                    $shot['visualDescription'] = preg_replace(
                        '/two[- ]?shot|establishing shot/i',
                        'wide shot',
                        $shot['visualDescription']
                    );
                }

                $conversions[] = [
                    'shotIndex' => $index,
                    'from' => $type,
                    'to' => 'wide',
                    'reason' => 'two-shot converted to single-character wide',
                    'keptCharacter' => $primaryCharacter,
                ];

                Log::debug('DialogueSceneDecomposer: Converted multi-character shot to wide', [
                    'shot_index' => $index,
                    'original_type' => $type,
                    'kept_character' => $primaryCharacter,
                ]);
            }

            // Case 2: Over-the-shoulder with multiple characters visible
            if ($type === 'over-the-shoulder') {
                // Keep only the focus character (the one speaking/being focused on)
                $focusCharacter = $shot['speakingCharacter'] ?? $charactersInShot[0] ?? null;

                // Ensure charactersInShot contains ONLY the focus character
                $shot['charactersInShot'] = $focusCharacter ? [$focusCharacter] : [];
                $shot['singleCharacterEnforced'] = true;

                // Enhance OTS data to clarify foreground is partial/blurred
                if (!isset($shot['otsData'])) {
                    $shot['otsData'] = [];
                }
                $shot['otsData']['foregroundVisible'] = 'shoulder and partial head';
                $shot['otsData']['foregroundBlur'] = true;
                $shot['otsData']['focusOn'] = $focusCharacter;

                $conversions[] = [
                    'shotIndex' => $index,
                    'from' => 'over-the-shoulder (multi)',
                    'to' => 'over-the-shoulder (single focus)',
                    'reason' => 'OTS enforced single focus character',
                    'focusCharacter' => $focusCharacter,
                ];

                Log::debug('DialogueSceneDecomposer: Enforced OTS single focus', [
                    'shot_index' => $index,
                    'focus_character' => $focusCharacter,
                ]);
            }
        }

        if (!empty($conversions)) {
            Log::info('DialogueSceneDecomposer: Enforced single-character constraint (FLOW-02)', [
                'conversions_count' => count($conversions),
                'details' => $conversions,
            ]);
        } else {
            Log::debug('DialogueSceneDecomposer: Single-character constraint already satisfied');
        }

        return $shots;
    }

    /**
     * PHASE 12: Validate character alternation in dialogue sequences (FLOW-04).
     * Flags consecutive shots from same speaker as potential coverage issue.
     *
     * @param array $shots Shots to validate
     * @return array Array of alternation issues
     */
    protected function validateCharacterAlternation(array $shots): array
    {
        $issues = [];
        $consecutiveCount = 1;
        $lastSpeaker = null;
        $consecutiveStartIndex = 0;

        foreach ($shots as $index => $shot) {
            // Get speaker (dialogue shots only)
            $speaker = $shot['speakingCharacter'] ?? null;
            $purpose = $shot['purpose'] ?? '';

            // Non-speaking shots (reaction, narrator overlay) reset the counter
            if ($speaker === null || $purpose === 'reaction' || $purpose === 'narrator') {
                // Check if previous streak was problematic before resetting
                if ($consecutiveCount >= 3 && $lastSpeaker !== null) {
                    $issues[] = [
                        'type' => 'consecutive_speaker',
                        'speaker' => $lastSpeaker,
                        'count' => $consecutiveCount,
                        'startIndex' => $consecutiveStartIndex,
                        'endIndex' => $index - 1,
                        'suggestion' => 'Consider inserting reaction shot for visual variety',
                    ];
                }
                $consecutiveCount = 0;
                $lastSpeaker = null;
                continue;
            }

            // Same speaker as previous shot
            if ($speaker === $lastSpeaker) {
                $consecutiveCount++;
            } else {
                // Different speaker - check previous streak
                if ($consecutiveCount >= 3 && $lastSpeaker !== null) {
                    $issues[] = [
                        'type' => 'consecutive_speaker',
                        'speaker' => $lastSpeaker,
                        'count' => $consecutiveCount,
                        'startIndex' => $consecutiveStartIndex,
                        'endIndex' => $index - 1,
                        'suggestion' => 'Consider inserting reaction shot for visual variety',
                    ];
                }

                // Reset for new speaker
                $consecutiveCount = 1;
                $consecutiveStartIndex = $index;
            }

            $lastSpeaker = $speaker;
        }

        // Check final streak at end of shots
        if ($consecutiveCount >= 3 && $lastSpeaker !== null) {
            $issues[] = [
                'type' => 'consecutive_speaker',
                'speaker' => $lastSpeaker,
                'count' => $consecutiveCount,
                'startIndex' => $consecutiveStartIndex,
                'endIndex' => count($shots) - 1,
                'suggestion' => 'Consider inserting reaction shot for visual variety',
            ];
        }

        if (!empty($issues)) {
            Log::info('DialogueSceneDecomposer: Character alternation issues found (FLOW-04)', [
                'issue_count' => count($issues),
                'details' => $issues,
            ]);
        } else {
            Log::debug('DialogueSceneDecomposer: Character alternation validation passed');
        }

        return $issues;
    }

    /**
     * PHASE 4: Analyze coverage of generated shots.
     *
     * @param array $shots Array of generated shots
     * @param array $characters Characters in the dialogue
     * @return array Coverage analysis results
     */
    protected function analyzeCoverage(array $shots, array $characters): array
    {
        $analysis = [
            'total' => count($shots),
            'byType' => [],
            'byCharacter' => [],
            'typeCategories' => [
                'establishing' => 0,
                'ots' => 0,
                'closeup' => 0,
                'reaction' => 0,
            ],
            'patterns' => [
                'consecutiveOTS' => 0,
                'maxConsecutiveOTS' => 0,
                'uniqueTypes' => [],
            ],
            'issues' => [],
        ];

        $currentOTSStreak = 0;

        foreach ($shots as $shot) {
            $type = $shot['type'] ?? 'unknown';
            $speaker = $shot['speakingCharacter'] ?? $shot['reactionCharacter'] ?? null;

            // Count by type
            $analysis['byType'][$type] = ($analysis['byType'][$type] ?? 0) + 1;
            $analysis['patterns']['uniqueTypes'][$type] = true;

            // Count by category
            foreach ($this->shotTypeCategories as $category => $types) {
                if (in_array($type, $types)) {
                    $analysis['typeCategories'][$category]++;
                    break;
                }
            }

            // Count by character
            if ($speaker) {
                $analysis['byCharacter'][$speaker] = ($analysis['byCharacter'][$speaker] ?? 0) + 1;
            }

            // Track OTS streaks
            $isOTS = in_array($type, $this->shotTypeCategories['ots']);
            if ($isOTS) {
                $currentOTSStreak++;
                $analysis['patterns']['maxConsecutiveOTS'] = max(
                    $analysis['patterns']['maxConsecutiveOTS'],
                    $currentOTSStreak
                );
            } else {
                $currentOTSStreak = 0;
            }
        }

        // Check requirements
        foreach ($this->coverageRequirements['requiredTypes'] as $category => $minimum) {
            $count = $analysis['typeCategories'][$category] ?? 0;
            if ($count < $minimum) {
                $analysis['issues'][] = [
                    'type' => 'missing_type',
                    'category' => $category,
                    'required' => $minimum,
                    'actual' => $count,
                ];
            }
        }

        // Check per-character coverage
        foreach ($characters as $character) {
            $charShots = $analysis['byCharacter'][$character] ?? 0;
            $charCoverage = $analysis['total'] > 0 ? $charShots / $analysis['total'] : 0;

            if ($charShots < $this->coverageRequirements['perCharacter']['speakingShots']) {
                $analysis['issues'][] = [
                    'type' => 'insufficient_character_coverage',
                    'character' => $character,
                    'shots' => $charShots,
                ];
            }

            if ($charCoverage < $this->coverageRequirements['perCharacter']['coverage']) {
                $analysis['issues'][] = [
                    'type' => 'unbalanced_coverage',
                    'character' => $character,
                    'coverage' => round($charCoverage * 100) . '%',
                ];
            }
        }

        // Check OTS pattern
        if ($analysis['patterns']['maxConsecutiveOTS'] > $this->coverageRequirements['patterns']['maxConsecutiveOTS']) {
            $analysis['issues'][] = [
                'type' => 'ots_monotony',
                'consecutive' => $analysis['patterns']['maxConsecutiveOTS'],
                'max_allowed' => $this->coverageRequirements['patterns']['maxConsecutiveOTS'],
            ];
        }

        // Check variety
        $uniqueTypes = count($analysis['patterns']['uniqueTypes']);
        if ($uniqueTypes < $this->coverageRequirements['patterns']['minVariety']) {
            $analysis['issues'][] = [
                'type' => 'insufficient_variety',
                'unique_types' => $uniqueTypes,
                'required' => $this->coverageRequirements['patterns']['minVariety'],
            ];
        }

        return $analysis;
    }

    /**
     * PHASE 4: Fix coverage issues by inserting missing shots.
     *
     * @param array $shots Current shots array
     * @param array $analysis Coverage analysis
     * @param array $characters Characters in dialogue
     * @param array $characterLookup Character data
     * @return array Corrected shots array
     */
    protected function fixCoverageIssues(
        array $shots,
        array $analysis,
        array $characters,
        array $characterLookup
    ): array {
        foreach ($analysis['issues'] as $issue) {
            switch ($issue['type']) {
                case 'missing_type':
                    $shots = $this->insertMissingShotType($shots, $issue, $characters, $characterLookup);
                    break;

                case 'ots_monotony':
                    $shots = $this->insertTwoShotBreaks($shots);
                    break;

                // Other issues logged but not auto-fixed
                default:
                    Log::warning('DialogueSceneDecomposer: Coverage issue detected', $issue);
            }
        }

        return $shots;
    }

    /**
     * Insert a missing shot type at appropriate position.
     */
    protected function insertMissingShotType(
        array $shots,
        array $issue,
        array $characters,
        array $characterLookup
    ): array {
        $category = $issue['category'];

        switch ($category) {
            case 'establishing':
                // Insert establishing shot at the beginning
                $establishingShot = $this->buildEstablishingShot($characters, $characterLookup);
                array_unshift($shots, $establishingShot);
                Log::info('DialogueSceneDecomposer: Inserted missing establishing shot');
                break;

            case 'closeup':
                // Insert close-up near the climax (60-70% through)
                $insertPos = (int)(count($shots) * 0.65);
                $closeupShot = $this->buildEmphasisCloseup($shots[$insertPos] ?? $shots[0], $characterLookup);
                array_splice($shots, $insertPos, 0, [$closeupShot]);
                Log::info('DialogueSceneDecomposer: Inserted missing close-up at position ' . $insertPos);
                break;
        }

        return $shots;
    }

    /**
     * Insert two-shot breaks to reduce OTS monotony.
     */
    protected function insertTwoShotBreaks(array $shots): array
    {
        $maxOTS = $this->coverageRequirements['patterns']['maxConsecutiveOTS'];
        $result = [];
        $otsCount = 0;

        foreach ($shots as $shot) {
            $isOTS = in_array($shot['type'] ?? '', $this->shotTypeCategories['ots']);

            if ($isOTS) {
                $otsCount++;

                // Insert two-shot break after max consecutive OTS
                if ($otsCount >= $maxOTS) {
                    $result[] = $this->buildTwoShotBreak($shot);
                    $otsCount = 0;
                    Log::debug('DialogueSceneDecomposer: Inserted two-shot break for visual variety');
                }
            } else {
                $otsCount = 0;
            }

            $result[] = $shot;
        }

        return $result;
    }

    /**
     * Build an establishing two-shot.
     */
    protected function buildEstablishingShot(array $characters, array $characterLookup): array
    {
        $charA = $characters[0] ?? 'Character A';
        $charB = $characters[1] ?? 'Character B';

        return [
            'type' => 'two-shot',
            'purpose' => 'establishing',
            'speakingCharacter' => null,
            'dialogue' => null,
            'useMultitalk' => false,
            'needsLipSync' => false,
            'duration' => 3,
            'emotionalIntensity' => 0.2,
            'visualDescription' => "Wide two-shot establishing {$charA} and {$charB} in conversation, " .
                "both characters visible, neutral staging, setting the scene for dialogue.",
            'spatial' => [
                'cameraPosition' => $this->axisLockSide,
                'cameraAngle' => 'frontal',
                'subjectPosition' => 'center',
                'eyeLineDirection' => 'towards each other',
            ],
        ];
    }

    /**
     * Build a brief two-shot break for visual variety.
     */
    protected function buildTwoShotBreak(array $contextShot): array
    {
        return [
            'type' => 'two-shot',
            'purpose' => 'breathing_room',
            'speakingCharacter' => null,
            'dialogue' => null,
            'useMultitalk' => false,
            'needsLipSync' => false,
            'duration' => 2,
            'emotionalIntensity' => $contextShot['emotionalIntensity'] ?? 0.5,
            'visualDescription' => "Brief two-shot showing both characters, visual breathing room in the dialogue.",
            'spatial' => [
                'cameraPosition' => $this->axisLockSide,
                'cameraAngle' => 'frontal',
                'subjectPosition' => 'center',
            ],
        ];
    }

    /**
     * Build an emphasis close-up for dramatic moment.
     */
    protected function buildEmphasisCloseup(array $referenceShot, array $characterLookup): array
    {
        $speaker = $referenceShot['speakingCharacter'] ?? array_keys($characterLookup)[0] ?? 'character';
        $characterData = $characterLookup[$speaker] ?? [];

        return [
            'type' => 'close-up',
            'purpose' => 'emphasis',
            'speakingCharacter' => $speaker,
            'dialogue' => null,
            'useMultitalk' => false,
            'needsLipSync' => false,
            'duration' => 3,
            'emotionalIntensity' => 0.8,
            'visualDescription' => "Close-up of {$speaker} for emotional emphasis, " .
                "intense expression, dramatic moment in conversation.",
            'spatial' => $referenceShot['spatial'] ?? [],
        ];
    }

    /**
     * Create establishing two-shot.
     */
    protected function createEstablishingShot(array $scene, array $speakers, array $characterLookup): array
    {
        $characterNames = array_map(fn($s) => $characterLookup[$s]['name'] ?? $s, $speakers);

        return [
            'type' => 'two-shot',
            'purpose' => 'establishing',
            'speakingCharacter' => null,
            'characterIndex' => null,
            'dialogue' => null,
            'monologue' => null,
            'voiceId' => null,
            'useMultitalk' => false,
            'emotionalIntensity' => 0.2,
            'position' => 'opening',
            'description' => 'Two-shot establishing ' . implode(' and ', array_slice($characterNames, 0, 2)),
            'visualPromptAddition' => 'Two characters visible in frame, establishing their spatial relationship',
            'charactersInShot' => array_slice($speakers, 0, 2),
            'duration' => 3, // Establishing shots are typically 2-4 seconds
            'needsLipSync' => false,
        ];
    }

    /**
     * Create a dialogue shot for a speaking character.
     *
     * @param array $exchange The dialogue exchange data
     * @param int $index Exchange index
     * @param float $emotionalIntensity Emotional intensity value
     * @param string $position Position in dialogue (opening, building, climax, resolution)
     * @param array $characterLookup Character lookup data
     * @param array $scene Scene data
     * @param array $speakers Array of speaker names for spatial calculation
     */
    protected function createDialogueShot(
        array $exchange,
        int $index,
        float $emotionalIntensity,
        string $position,
        array $characterLookup,
        array $scene,
        array $speakers = []
    ): array {
        $speaker = $exchange['speaker'];
        $charData = $characterLookup[$speaker] ?? [];

        // PHASE 13 CAM-03: Analyze speaker emotion from dialogue
        $speakerEmotion = $this->analyzeSpeakerEmotion($exchange['text'] ?? '');

        // Select shot type based on emotional intensity and speaker emotion
        $shotType = $this->selectShotTypeForIntensity($emotionalIntensity, $position, $speakerEmotion['emotion'] ?? null);

        // PHASE 4: Calculate spatial data for this shot
        $characters = array_slice($speakers, 0, 2); // First two characters
        $spatial = $this->calculateSpatialData(
            $speaker,
            $characters,
            $shotType
        );

        // Build visual description for the speaking character
        $visualPromptAddition = $this->buildSpeakingVisualPrompt($exchange, $shotType, $charData, $position);

        // Build shot data
        $shot = [
            'type' => $shotType,
            'purpose' => 'dialogue',
            'speakingCharacter' => $speaker,
            'characterIndex' => $charData['characterIndex'] ?? null,
            'dialogue' => $exchange['text'],
            'monologue' => $exchange['text'], // Same as dialogue for Multitalk
            'voiceId' => $charData['voiceId'] ?? 'echo',
            'useMultitalk' => true,
            'emotionalIntensity' => $emotionalIntensity,
            'position' => $position,
            'dialogueIndex' => $index,
            'description' => "{$shotType} of {$speaker} speaking",
            'visualPromptAddition' => $visualPromptAddition,
            'charactersInShot' => [$speaker],
            'wordCount' => $exchange['wordCount'] ?? str_word_count($exchange['text']),
            'needsLipSync' => true,
            'characterData' => $charData['characterData'] ?? null,
            'spatial' => $spatial, // PHASE 4: Spatial continuity data
        ];

        // Use spatial-aware prompt for enhanced visual description
        $shot['spatialAwarePrompt'] = $this->buildSpatialAwarePrompt($shot, $charData['characterData'] ?? []);

        // PHASE 4: Detect if this should be an OTS shot and enhance with OTS data
        $isOTSShot = $this->shouldUseOTS($shotType, $emotionalIntensity, $index);

        if ($isOTSShot) {
            // Get the other character (listener) for OTS foreground
            $listener = null;
            foreach (array_keys($characterLookup) as $char) {
                if (strcasecmp($char, $speaker) !== 0) {
                    $listener = $char;
                    break;
                }
            }

            if ($listener) {
                // Build OTS-specific data
                $otsData = $this->buildOTSData($speaker, $listener, $spatial);
                $shot = array_merge($shot, $otsData);

                // Update shot type to OTS
                $shot['type'] = 'over-the-shoulder';

                // Use OTS-specific prompt
                $listenerData = $characterLookup[$listener] ?? [];
                $shot['visualPromptAddition'] = $this->buildOTSPrompt($shot, $charData, $listenerData);

                // Include listener in shot
                $shot['charactersInShot'] = [$speaker, $listener];
                $shot['description'] = "Over-the-shoulder shot of {$speaker} speaking, {$listener}'s shoulder in foreground";

                Log::debug('DialogueSceneDecomposer: Generated OTS shot', [
                    'speaker' => $speaker,
                    'listener' => $listener,
                    'shoulder' => $otsData['otsData']['foregroundShoulder'],
                    'profileAngle' => $otsData['otsData']['profileAngle'],
                ]);
            }
        }

        return $shot;
    }

    /**
     * PHASE 4: Determine if shot should be OTS based on context.
     *
     * OTS shots work well for medium-intensity dialogue, creating
     * depth and visual connection between characters.
     *
     * @param string $shotType Current shot type
     * @param float $intensity Emotional intensity
     * @param int $exchangeIndex Position in dialogue
     * @return bool True if should use OTS framing
     */
    protected function shouldUseOTS(string $shotType, float $intensity, int $exchangeIndex): bool
    {
        // Explicit OTS shot type
        if ($shotType === 'over-the-shoulder') {
            return true;
        }

        // Medium shots in dialogue often work well as OTS
        // OTS creates depth and shows spatial relationship
        if (in_array($shotType, ['medium', 'medium-close']) && $intensity >= 0.3 && $intensity <= 0.7) {
            // Use OTS for alternating shots (creates shot/reverse-shot rhythm)
            return $exchangeIndex % 2 === 1;
        }

        return false;
    }

    /**
     * Create a reaction shot (silent, no dialogue).
     */
    protected function createReactionShot(
        string $speaker,
        array $characterLookup,
        array $scene,
        float $precedingIntensity
    ): array {
        $charData = $characterLookup[$speaker] ?? [];

        return [
            'type' => 'close-up',
            'purpose' => 'reaction',
            'speakingCharacter' => $speaker, // Character shown, but not speaking
            'characterIndex' => $charData['characterIndex'] ?? null,
            'dialogue' => null,
            'monologue' => null,
            'voiceId' => null,
            'useMultitalk' => false, // No lip-sync for reaction shots
            'emotionalIntensity' => $precedingIntensity * 0.9, // Slightly lower
            'position' => 'reaction',
            'description' => "Reaction shot of {$speaker}",
            'visualPromptAddition' => "{$speaker} reacting to what was just said, processing the information, subtle facial expression showing their emotional response",
            'charactersInShot' => [$speaker],
            'duration' => 2, // Reaction shots are typically 1-3 seconds
            'needsLipSync' => false,
            'characterData' => $charData['characterData'] ?? null,
        ];
    }

    /**
     * Select shot type based on emotional intensity and position.
     *
     * PHASE 13 Requirements:
     * - CAM-01: Dynamic CU/MS/OTS selection based on emotional intensity
     * - CAM-02: Camera variety based on position in conversation
     * - CAM-04: Establishing shot at start, tight framing at climax
     *
     * Position-enforced rules take priority over intensity alone.
     * Third parameter allows speaker emotion to adjust intensity thresholds.
     *
     * @param float $intensity Emotional intensity (0.0-1.0)
     * @param string $position Position in dialogue (opening, building, climax, resolution)
     * @param string|null $speakerEmotion Optional speaker emotion from analyzeSpeakerEmotion
     * @return string Shot type (establishing, wide, medium, over-the-shoulder, medium-close, close-up, extreme-close-up)
     */
    protected function selectShotTypeForIntensity(float $intensity, string $position, ?string $speakerEmotion = null): string
    {
        // PHASE 13 CAM-04: Position-enforced rules take priority
        switch ($position) {
            case 'opening':
                // Opening ALWAYS uses wide framing (CAM-04)
                // Never close-up at conversation start
                if ($intensity >= 0.5) return 'medium';
                if ($intensity >= 0.3) return 'wide';
                return 'establishing';

            case 'climax':
                // Climax ALWAYS uses tight framing (CAM-04)
                if ($intensity >= 0.8 || $speakerEmotion === 'angry' || $speakerEmotion === 'fearful') {
                    return 'extreme-close-up';
                }
                return 'close-up';

            case 'resolution':
                // Resolution eases back to medium framing
                if ($intensity >= 0.65) return 'medium-close';
                if ($intensity >= 0.4) return 'medium';
                return 'wide';
        }

        // 'building' phase uses full intensity range (CAM-01)
        // Speaker emotion adjusts threshold (CAM-03)
        $adjustedIntensity = $intensity;
        if ($speakerEmotion === 'angry' || $speakerEmotion === 'fearful') {
            $adjustedIntensity = min(1.0, $intensity + 0.15);
        }

        if ($adjustedIntensity >= 0.75) {
            return 'close-up';
        } elseif ($adjustedIntensity >= 0.55) {
            return 'medium-close';
        } elseif ($adjustedIntensity >= 0.4) {
            return 'over-the-shoulder';
        } elseif ($adjustedIntensity >= 0.25) {
            return 'medium';
        }

        return 'wide';
    }

    /**
     * Build visual prompt addition for speaking character.
     */
    protected function buildSpeakingVisualPrompt(
        array $exchange,
        string $shotType,
        array $charData,
        string $position
    ): string {
        $speaker = $exchange['speaker'];
        $text = $exchange['text'];

        // Determine expression based on dialogue content
        $expression = 'speaking';
        if (str_contains($text, '!')) {
            $expression = 'speaking with intensity';
        } elseif (str_contains($text, '?')) {
            $expression = 'speaking questioningly';
        } elseif (str_contains($text, '...')) {
            $expression = 'speaking thoughtfully';
        }

        // Build prompt based on shot type
        $prompt = match($shotType) {
            'extreme-close-up' => "{$speaker}'s face in extreme close-up, {$expression}, eyes conveying deep emotion, every facial detail visible",
            'close-up' => "{$speaker} in close-up, {$expression}, clear view of facial expression and mouth movement",
            'medium-close' => "{$speaker} from chest up, {$expression}, gesturing naturally while talking",
            'over-the-shoulder' => "Over-the-shoulder shot focused on {$speaker}, {$expression}, slight blur on foreground shoulder",
            'medium' => "{$speaker} from waist up, {$expression}, body language visible during conversation",
            default => "{$speaker} {$expression}, engaged in dialogue",
        };

        // Add position context
        if ($position === 'climax') {
            $prompt .= ', dramatic lighting emphasizing the crucial moment';
        }

        return $prompt;
    }

    /**
     * PHASE 4: Build visual prompt with spatial continuity information.
     *
     * @param array $shot Shot data including spatial info
     * @param array $characterData Character appearance data
     * @return string Enhanced visual prompt
     */
    protected function buildSpatialAwarePrompt(array $shot, array $characterData): string
    {
        $prompt = [];

        // Base character description
        $characterName = $shot['speakingCharacter'] ?? 'character';
        $appearance = $characterData['appearance'] ?? '';

        // Shot type and framing
        $shotType = $shot['type'] ?? 'medium';
        $prompt[] = ucfirst($shotType) . ' shot of ' . $characterName;

        if (!empty($appearance)) {
            $prompt[] = "({$appearance})";
        }

        // PHASE 4: Add spatial positioning
        $spatial = $shot['spatial'] ?? [];

        if (!empty($spatial['subjectPosition'])) {
            $prompt[] = "positioned {$spatial['subjectPosition']} of frame";
        }

        if (!empty($spatial['eyeLineDirection'])) {
            $prompt[] = "looking {$spatial['eyeLineDirection']}";
        }

        if (!empty($spatial['cameraAngle'])) {
            $angleDesc = match($spatial['cameraAngle']) {
                'profile' => 'in profile view',
                'three-quarter' => 'at three-quarter angle',
                'frontal' => 'facing camera',
                default => '',
            };
            if ($angleDesc) {
                $prompt[] = $angleDesc;
            }
        }

        // Add expression based on dialogue
        if (!empty($shot['expression'])) {
            $prompt[] = "with {$shot['expression']} expression";
        }

        // Add dialogue context
        if (!empty($shot['dialogue'])) {
            $dialogueHint = $this->getDialogueVisualHint($shot['dialogue']);
            if ($dialogueHint) {
                $prompt[] = $dialogueHint;
            }
        }

        return implode(', ', array_filter($prompt)) . '.';
    }

    /**
     * PHASE 4: Get visual hint from dialogue content.
     *
     * @param string $dialogue The dialogue text
     * @return string Visual hint for the prompt
     */
    protected function getDialogueVisualHint(string $dialogue): string
    {
        if (str_ends_with(trim($dialogue), '?')) {
            return 'questioning expression';
        }
        if (str_ends_with(trim($dialogue), '!')) {
            return 'emphatic delivery';
        }
        if (stripos($dialogue, 'sorry') !== false || stripos($dialogue, 'apologize') !== false) {
            return 'apologetic demeanor';
        }
        if (stripos($dialogue, 'love') !== false || stripos($dialogue, 'care') !== false) {
            return 'warm expression';
        }
        return '';
    }

    /**
     * Determine if a reaction shot should be added.
     */
    protected function shouldAddReactionShot(string $position, float $intensity, array $options): bool
    {
        // Don't add reaction shots if disabled
        if (!($options['includeReactionShots'] ?? true)) {
            return false;
        }

        // Add reaction before climax for dramatic pause
        if ($position === 'building' && $intensity >= 0.6) {
            return true;
        }

        // Randomly add reactions (30% chance) for natural pacing
        if ($position === 'building' && mt_rand(1, 100) <= 30) {
            return true;
        }

        return false;
    }

    /**
     * Get the next speaker in the dialogue sequence.
     */
    protected function getNextSpeaker(array $exchanges, int $currentIndex): ?string
    {
        $nextIndex = $currentIndex + 1;
        return $exchanges[$nextIndex]['speaker'] ?? null;
    }

    /**
     * Calculate shot durations based on dialogue length.
     * Speaking rate: ~150 words per minute = 2.5 words per second
     */
    protected function calculateShotDurations(array $shots): array
    {
        foreach ($shots as &$shot) {
            if ($shot['useMultitalk'] ?? false) {
                // Calculate based on word count (150 wpm = 2.5 words/second)
                $wordCount = $shot['wordCount'] ?? str_word_count($shot['dialogue'] ?? '');
                $speakingDuration = $wordCount / 2.5;

                // Add buffer for natural pacing (0.5s before + 0.5s after)
                $shot['duration'] = max(3, ceil($speakingDuration + 1));
                $shot['calculatedFromWords'] = true;
            } else {
                // Non-speaking shots get default duration
                $shot['duration'] = $shot['duration'] ?? 3;
            }
        }

        return $shots;
    }

    /**
     * Get summary of dialogue decomposition.
     */
    public function getDecompositionSummary(array $shots): array
    {
        $speakingShots = array_filter($shots, fn($s) => $s['useMultitalk'] ?? false);
        $reactionShots = array_filter($shots, fn($s) => ($s['purpose'] ?? '') === 'reaction');
        $speakers = array_unique(array_filter(array_column($shots, 'speakingCharacter')));

        $totalDuration = array_sum(array_column($shots, 'duration'));
        $dialogueDuration = array_sum(array_column($speakingShots, 'duration'));

        return [
            'totalShots' => count($shots),
            'speakingShots' => count($speakingShots),
            'reactionShots' => count($reactionShots),
            'speakers' => $speakers,
            'speakerCount' => count($speakers),
            'totalDuration' => $totalDuration,
            'dialogueDuration' => $dialogueDuration,
            'hasEstablishing' => !empty(array_filter($shots, fn($s) => ($s['purpose'] ?? '') === 'establishing')),
        ];
    }

    /**
     * PHASE 4: Build OTS-specific shot data.
     *
     * Over-the-shoulder shots need explicit foreground/background specification
     * to generate proper Hollywood-style framing with depth separation.
     *
     * @param string $speaker The speaking character (in focus)
     * @param string $listener The listening character (foreground)
     * @param array $spatial Base spatial data
     * @return array Enhanced OTS shot data
     */
    protected function buildOTSData(string $speaker, string $listener, array $spatial): array
    {
        // Determine which shoulder based on spatial positioning
        // If speaker is screen-right, we see over listener's left shoulder
        // If speaker is screen-left, we see over listener's right shoulder
        $speakerScreenPosition = $spatial['subjectPosition'] ?? 'right';
        $foregroundShoulder = $speakerScreenPosition === 'right' ? 'left' : 'right';

        return [
            'otsData' => [
                'foregroundCharacter' => $listener,
                'foregroundShoulder' => $foregroundShoulder,
                'foregroundBlur' => true,
                'foregroundVisible' => 'shoulder and partial head',
                'backgroundCharacter' => $speaker, // In focus
                'backgroundPosition' => $speakerScreenPosition,
                'depthOfField' => 'shallow', // Blur foreground, focus background
                'focusOn' => $speaker,
                'profileAngle' => $speakerScreenPosition === 'right' ? 'left-three-quarter' : 'right-three-quarter',
            ],
        ];
    }

    /**
     * PHASE 4: Build visual prompt specifically for OTS shots.
     *
     * OTS shots require specific prompting to achieve proper Hollywood framing:
     * - Foreground character blurred (shoulder + partial head)
     * - Background character in sharp focus
     * - Explicit shoulder side and profile angle
     *
     * @param array $shot Shot data with OTS info
     * @param array $speakerData Speaker character data
     * @param array $listenerData Listener character data
     * @return string OTS-specific visual prompt
     */
    protected function buildOTSPrompt(array $shot, array $speakerData, array $listenerData): string
    {
        $otsData = $shot['otsData'] ?? [];
        $prompt = [];

        // Shot type declaration
        $prompt[] = 'Over-the-shoulder shot';

        // Foreground description (blurred)
        $listenerName = $otsData['foregroundCharacter'] ?? 'listener';
        $shoulder = $otsData['foregroundShoulder'] ?? 'left';
        $prompt[] = "with {$listenerName}'s {$shoulder} shoulder and partial head in soft-focus foreground";

        // Background (in focus) - the speaker
        $speakerName = $otsData['backgroundCharacter'] ?? $shot['speakingCharacter'];
        $speakerAppearance = $speakerData['appearance'] ?? '';
        $profileAngle = $otsData['profileAngle'] ?? 'three-quarter';

        $prompt[] = "{$speakerName} in sharp focus";

        if (!empty($speakerAppearance)) {
            $prompt[] = "({$speakerAppearance})";
        }

        $prompt[] = "at {$profileAngle} angle";

        // Position in frame
        $position = $otsData['backgroundPosition'] ?? 'right';
        $prompt[] = "positioned {$position} of frame";

        // Expression
        if (!empty($shot['expression'])) {
            $prompt[] = "with {$shot['expression']} expression";
        }

        // Depth of field (moderate to keep background speaker visible)
        $prompt[] = 'moderate depth of field with visible background figures';
        $prompt[] = 'cinematic lighting';

        // Dialogue context
        if (!empty($shot['dialogue'])) {
            $emotion = $this->detectDialogueEmotion($shot['dialogue']);
            if ($emotion) {
                $prompt[] = $emotion;
            }
        }

        return implode(', ', array_filter($prompt)) . '.';
    }

    /**
     * PHASE 4: Analyze dialogue to determine listener's emotional response.
     *
     * @param string $dialogue What was said TO the listener
     * @param string $speakerEmotion The speaker's emotional state
     * @param array $context Additional context (relationship, scene mood)
     * @return array Listener emotion data
     */
    protected function analyzeListenerEmotion(string $dialogue, string $speakerEmotion, array $context = []): array
    {
        $text = strtolower($dialogue);
        $emotion = 'attentive'; // Default neutral listening
        $intensity = 0.5;
        $visualCues = [];

        // Questions directed at listener - they need to think
        if (str_ends_with(trim($dialogue), '?')) {
            $emotion = 'contemplative';
            $visualCues[] = 'thoughtful expression';
            $visualCues[] = 'slight head tilt';
            $intensity = 0.5;
        }

        // Accusations or blame
        if (preg_match('/\b(you|your)\b.*\b(fault|blame|wrong|lied|betrayed)\b/', $text)) {
            $emotion = 'defensive';
            $visualCues[] = 'tense posture';
            $visualCues[] = 'guarded expression';
            $intensity = 0.7;
        }

        // Declarations of love or care
        if (preg_match('/\b(love|care about|need|miss)\s+(you|him|her)\b/', $text)) {
            $emotion = 'moved';
            $visualCues[] = 'softening expression';
            $visualCues[] = 'emotional eyes';
            $intensity = 0.75;
        }

        // Bad news or revelations
        if (preg_match('/\b(dead|died|cancer|leaving|divorce|fired|over)\b/', $text)) {
            $emotion = 'shocked';
            $visualCues[] = 'widening eyes';
            $visualCues[] = 'stunned silence';
            $intensity = 0.85;
        }

        // Good news or positive revelations
        if (preg_match('/\b(pregnant|engaged|won|accepted|promoted|alive)\b/', $text)) {
            $emotion = 'overjoyed';
            $visualCues[] = 'breaking into smile';
            $visualCues[] = 'relief flooding face';
            $intensity = 0.8;
        }

        // Threats or intimidation
        if (preg_match('/\b(kill|hurt|destroy|regret|warn)\b/', $text)) {
            $emotion = 'fearful';
            $visualCues[] = 'nervous swallow';
            $visualCues[] = 'fear in eyes';
            $intensity = 0.8;
        }

        // Apologies
        if (preg_match('/\b(sorry|apologize|forgive|my fault)\b/', $text)) {
            $emotion = 'considering';
            $visualCues[] = 'weighing response';
            $visualCues[] = 'guarded but listening';
            $intensity = 0.6;
        }

        // Mirror high-intensity speaker emotions
        if ($speakerEmotion === 'angry' && $emotion === 'attentive') {
            $emotion = 'wary';
            $visualCues[] = 'cautious expression';
            $intensity = 0.6;
        }

        return [
            'emotion' => $emotion,
            'intensity' => $intensity,
            'visualCues' => $visualCues,
            'silentBeat' => $intensity >= 0.7, // High intensity = hold on reaction
        ];
    }

    /**
     * PHASE 4: Determine if a reaction shot should be inserted after this exchange.
     *
     * @param array $exchange Current dialogue exchange
     * @param int $exchangeIndex Position in dialogue
     * @param int $totalExchanges Total number of exchanges
     * @param array $listenerEmotion Analyzed listener emotion
     * @return bool True if reaction shot should be added
     */
    protected function shouldInsertReaction(
        array $exchange,
        int $exchangeIndex,
        int $totalExchanges,
        array $listenerEmotion
    ): bool {
        // Always add reaction for high-intensity moments
        if ($listenerEmotion['silentBeat']) {
            return true;
        }

        // Add reaction at narrative turning points
        $progress = $exchangeIndex / max(1, $totalExchanges - 1);

        // Before the midpoint climax (around 40-50% through)
        if ($progress >= 0.35 && $progress <= 0.5 && $listenerEmotion['intensity'] >= 0.6) {
            return true;
        }

        // After major revelations (high intensity exchanges)
        if ($listenerEmotion['intensity'] >= 0.75) {
            return true;
        }

        // At the end of significant exchanges (every 3-4 exchanges for rhythm)
        if ($exchangeIndex > 0 && $exchangeIndex % 3 === 2) {
            return true;
        }

        // After questions that deserve visual consideration
        if (str_ends_with(trim($exchange['text'] ?? ''), '?') && $listenerEmotion['intensity'] >= 0.5) {
            return true;
        }

        return false;
    }

    /**
     * PHASE 4: Build a detailed reaction shot.
     *
     * @param string $listener The character reacting
     * @param array $listenerData Character data
     * @param array $listenerEmotion Analyzed emotion
     * @param array $previousShot The shot being reacted to
     * @param array $spatial Spatial continuity data
     * @return array Complete reaction shot data
     */
    protected function buildReactionShot(
        string $listener,
        array $listenerData,
        array $listenerEmotion,
        array $previousShot,
        array $spatial
    ): array {
        // Calculate duration based on intensity (longer for bigger reactions)
        $baseDuration = 2;
        $duration = $listenerEmotion['intensity'] >= 0.7
            ? $baseDuration + 1.5  // Hold on big reactions
            : $baseDuration;

        // Build visual description
        $appearance = $listenerData['appearance'] ?? '';
        $visualCues = implode(', ', $listenerEmotion['visualCues']);
        $emotion = $listenerEmotion['emotion'];

        // Determine shot type based on intensity
        $shotType = $listenerEmotion['intensity'] >= 0.75
            ? 'close-up'  // Tight for big emotions
            : 'medium-close';  // Standard for reactions

        // Build the prompt
        $promptParts = [
            ucfirst($shotType) . " shot of {$listener}",
        ];

        if (!empty($appearance)) {
            $promptParts[] = "({$appearance})";
        }

        $promptParts[] = "listening intently";
        $promptParts[] = "{$emotion} expression";

        if (!empty($visualCues)) {
            $promptParts[] = $visualCues;
        }

        // Add spatial positioning (reverse of speaker)
        $promptParts[] = "positioned " . ($spatial['subjectPosition'] === 'right' ? 'left' : 'right') . " of frame";
        $promptParts[] = "looking " . ($spatial['eyeLineDirection'] === 'screen-left' ? 'screen-right' : 'screen-left');

        // Add context from what was said
        if (!empty($previousShot['dialogue'])) {
            $context = $this->getReactionContext($previousShot['dialogue']);
            if ($context) {
                $promptParts[] = $context;
            }
        }

        $promptParts[] = 'silent moment';
        $promptParts[] = 'cinematic lighting';

        return [
            'type' => $shotType,
            'purpose' => 'reaction',
            'speakingCharacter' => null,
            'reactionCharacter' => $listener,
            'dialogue' => null,
            'useMultitalk' => false,
            'needsLipSync' => false,
            'duration' => $duration,
            'emotionalIntensity' => $listenerEmotion['intensity'],
            'expression' => $emotion,
            'visualDescription' => implode(', ', $promptParts) . '.',
            'spatial' => [
                'cameraPosition' => $spatial['cameraPosition'] ?? 'left',
                'cameraAngle' => $shotType === 'close-up' ? 'three-quarter' : 'frontal',
                'subjectPosition' => $spatial['subjectPosition'] === 'right' ? 'left' : 'right',
                'eyeLineDirection' => $spatial['eyeLineDirection'] === 'screen-left' ? 'screen-right' : 'screen-left',
                'reverseOf' => null,
                'pairId' => null,
            ],
            'reactionData' => [
                'reactingTo' => $previousShot['dialogue'] ?? '',
                'reactingToSpeaker' => $previousShot['speakingCharacter'] ?? '',
                'emotion' => $emotion,
                'visualCues' => $listenerEmotion['visualCues'],
                'silentBeat' => $listenerEmotion['silentBeat'],
            ],
        ];
    }

    /**
     * PHASE 4: Get contextual description based on dialogue content.
     *
     * @param string $dialogue The dialogue text
     * @return string Contextual description for reaction
     */
    protected function getReactionContext(string $dialogue): string
    {
        $text = strtolower($dialogue);

        if (preg_match('/\b(dead|died|gone|over)\b/', $text)) {
            return 'processing devastating news';
        }
        if (preg_match('/\b(love|marry|together)\b/', $text)) {
            return 'absorbing heartfelt words';
        }
        if (preg_match('/\b(sorry|apologize)\b/', $text)) {
            return 'considering the apology';
        }
        if (str_ends_with(trim($dialogue), '?')) {
            return 'formulating response';
        }
        if (str_ends_with(trim($dialogue), '!')) {
            return 'reacting to emphatic statement';
        }

        return 'taking in the words';
    }

    /**
     * PHASE 4: Get the other character in a two-person dialogue.
     *
     * @param string $currentSpeaker Current speaker name
     * @param array $characterLookup Character lookup array
     * @return string|null Other character name or null
     */
    protected function getOtherCharacter(string $currentSpeaker, array $characterLookup): ?string
    {
        foreach (array_keys($characterLookup) as $character) {
            if (strcasecmp($character, $currentSpeaker) !== 0) {
                return $character;
            }
        }
        return null;
    }

    /**
     * PHASE 4: Detect emotional tone from dialogue text.
     *
     * @param string $dialogue The dialogue text
     * @return string Emotional atmosphere description or empty string
     */
    protected function detectDialogueEmotion(string $dialogue): string
    {
        $text = strtolower($dialogue);

        // Check for emotional keywords
        if (preg_match('/\b(angry|furious|rage|hate)\b/', $text)) {
            return 'intense confrontational mood';
        }
        if (preg_match('/\b(love|adore|care|tender)\b/', $text)) {
            return 'warm intimate atmosphere';
        }
        if (preg_match('/\b(scared|afraid|terrified|fear)\b/', $text)) {
            return 'tense fearful atmosphere';
        }
        if (preg_match('/\b(sad|cry|tears|grief)\b/', $text)) {
            return 'somber emotional moment';
        }
        if (preg_match('/\b(happy|joy|excited|thrilled)\b/', $text)) {
            return 'uplifting joyful energy';
        }

        // Check punctuation
        if (str_ends_with(trim($dialogue), '!')) {
            return 'emphatic delivery';
        }
        if (str_ends_with(trim($dialogue), '?')) {
            return 'questioning tone';
        }

        return '';
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // PHASE 11: SPEECH-DRIVEN SHOT CREATION
    // Methods for creating/enhancing shots from speech segments (1:1 mapping)
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Enhance pre-created shots with dialogue patterns.
     * Called when shots are created from speech segments (1:1 mapping).
     * Applies shot/reverse-shot patterns, camera positions, and emotional intensity.
     *
     * @param array $shots Array of shots created from speech segments
     * @param array $scene Scene data for context
     * @param array $characterBible Character Bible for character lookup
     * @return array Enhanced shots with dialogue patterns applied
     */
    public function enhanceShotsWithDialoguePatterns(array $shots, array $scene, array $characterBible = []): array
    {
        if (empty($shots)) {
            return [];
        }

        $totalShots = count($shots);
        $speakers = $this->extractSpeakersFromShots($shots);
        $characterLookup = $this->buildCharacterLookup($characterBible, $speakers);

        Log::info('DialogueSceneDecomposer: Enhancing speech-driven shots', [
            'shot_count' => $totalShots,
            'speakers' => $speakers,
            'ratio' => '1:1',
        ]);

        // Enhance each shot with dialogue patterns
        foreach ($shots as $index => &$shot) {
            $position = $this->calculateDialoguePosition($index, $totalShots);
            $emotionalIntensity = $this->calculateEmotionalIntensityFromShot($shot, $position, $scene);

            // Get speaker from shot
            $speaker = $shot['speakingCharacter'] ?? $shot['speaker'] ?? null;
            $charData = $characterLookup[$speaker] ?? [];

            // PHASE 13 CAM-03: Analyze speaker's emotion from their dialogue
            $speakerEmotion = $this->analyzeSpeakerEmotion($shot['dialogue'] ?? $shot['monologue'] ?? '');

            // Store the speaker emotion in the shot data
            $shot['speakerEmotion'] = $speakerEmotion;

            // Select shot type based on emotional intensity and speaker emotion
            $shotType = $this->selectShotTypeForIntensity($emotionalIntensity, $position, $speakerEmotion['emotion'] ?? null);

            // Calculate spatial data for 180-degree rule
            $characters = array_slice($speakers, 0, 2);
            $spatial = $this->calculateSpatialData($speaker ?? '', $characters, $shotType);

            // PHASE 13 debug logging
            Log::debug('DialogueSceneDecomposer: PHASE 13 camera intelligence applied', [
                'shot_index' => $index,
                'position' => $position,
                'speaker_emotion' => $speakerEmotion['emotion'] ?? 'neutral',
                'intensity' => $emotionalIntensity,
                'shot_type' => $shotType,
            ]);

            // Enhance shot with dialogue pattern data
            $shot['type'] = $shotType;
            $shot['purpose'] = 'dialogue';
            $shot['emotionalIntensity'] = $emotionalIntensity;
            $shot['position'] = $position;
            $shot['dialogueIndex'] = $index;
            $shot['shotIndex'] = $index;
            $shot['totalShots'] = $totalShots;
            $shot['spatial'] = $spatial;

            // Add visual prompt based on shot type
            $shot['visualPromptAddition'] = $this->buildSpeakingVisualPrompt(
                ['speaker' => $speaker, 'text' => $shot['dialogue'] ?? ''],
                $shotType,
                $charData,
                $position
            );

            // Mark for lip-sync
            $shot['useMultitalk'] = true;
            $shot['needsLipSync'] = true;

            // Add character data
            if (!empty($charData)) {
                $shot['characterIndex'] = $charData['characterIndex'] ?? null;
                $shot['characterData'] = $charData['characterData'] ?? null;
                if (empty($shot['voiceId']) && !empty($charData['voiceId'])) {
                    $shot['voiceId'] = $charData['voiceId'];
                }
            }

            // Build spatial-aware prompt
            $shot['spatialAwarePrompt'] = $this->buildSpatialAwarePrompt($shot, $charData['characterData'] ?? []);
        }

        // Apply shot/reverse-shot pairing
        $shots = $this->pairReverseShots($shots);

        // Phase 12: Enforce single-character constraint (FLOW-02)
        $shots = $this->enforceSingleCharacterConstraint($shots);

        // Phase 12: Validate 180-degree rule (SCNE-04)
        $axisViolations = $this->validate180DegreeRule($shots);
        if (!empty($axisViolations)) {
            Log::warning('DialogueSceneDecomposer: 180-degree rule violations', [
                'violations' => $axisViolations,
            ]);
        }

        // Phase 12: Validate character alternation (FLOW-04)
        $alternationIssues = $this->validateCharacterAlternation($shots);
        if (!empty($alternationIssues)) {
            Log::info('DialogueSceneDecomposer: Character alternation notes', [
                'issues' => $alternationIssues,
            ]);
        }

        // Calculate durations
        $shots = $this->calculateShotDurations($shots);

        Log::info('DialogueSceneDecomposer: Enhanced shots with dialogue patterns', [
            'total_shots' => count($shots),
            'speakers' => $speakers,
            'unique_types' => count(array_unique(array_column($shots, 'type'))),
        ]);

        return $shots;
    }

    /**
     * Extract unique speakers from shots array.
     *
     * @param array $shots Array of shots
     * @return array Array of speaker names
     */
    protected function extractSpeakersFromShots(array $shots): array
    {
        $speakers = [];
        foreach ($shots as $shot) {
            $speaker = $shot['speakingCharacter'] ?? $shot['speaker'] ?? null;
            if ($speaker && !in_array($speaker, $speakers)) {
                $speakers[] = $speaker;
            }
        }
        return $speakers;
    }

    /**
     * Calculate emotional intensity from shot data.
     *
     * @param array $shot Shot data with dialogue
     * @param string $position Position in dialogue sequence
     * @param array $scene Scene context
     * @return float Intensity value 0.0-1.0
     */
    protected function calculateEmotionalIntensityFromShot(array $shot, string $position, array $scene): float
    {
        $exchange = [
            'speaker' => $shot['speakingCharacter'] ?? $shot['speaker'] ?? 'Unknown',
            'text' => $shot['dialogue'] ?? $shot['monologue'] ?? '',
        ];

        return $this->calculateEmotionalIntensity($exchange, $position, $scene);
    }

    /**
     * Create a single shot from a speech segment.
     * Used for 1:1 mapping where each segment becomes one shot.
     *
     * @param array $segment Speech segment data
     * @param array $scene Scene data for context
     * @param array $characterBible Character Bible for character lookup
     * @param int $index Segment index in sequence
     * @param int $total Total number of segments
     * @return array Shot data
     */
    public function createShotFromSegment(
        array $segment,
        array $scene,
        array $characterBible,
        int $index = 0,
        int $total = 1
    ): array {
        $speaker = $segment['speaker'] ?? 'Unknown';
        $text = $segment['text'] ?? '';

        // Build character lookup
        $characterLookup = $this->buildCharacterLookup($characterBible, [$speaker]);
        $charData = $characterLookup[$speaker] ?? [];

        // Calculate position and intensity
        $position = $this->calculateDialoguePosition($index, $total);
        $emotionalIntensity = $this->calculateEmotionalIntensity(
            ['speaker' => $speaker, 'text' => $text],
            $position,
            $scene
        );

        // Select shot type
        $shotType = $this->selectShotTypeForIntensity($emotionalIntensity, $position);

        // Base shot structure
        $shot = [
            'id' => uniqid('shot_'),
            'type' => $shotType,
            'purpose' => 'dialogue',
            'duration' => $this->calculateDurationFromTextLength($text),
            'dialogue' => $text,
            'monologue' => $text,
            'speaker' => $speaker,
            'speakingCharacter' => $speaker,
            'speakingCharacters' => [$speaker],
            'needsLipSync' => true,
            'useMultitalk' => true,
            'speechSegments' => [$segment],
            'emotionalIntensity' => $emotionalIntensity,
            'position' => $position,
            'dialogueIndex' => $index,
            'shotIndex' => $index,
            'totalShots' => $total,
        ];

        // Add character context from Character Bible
        if (!empty($charData)) {
            $shot['characterIndex'] = $charData['characterIndex'] ?? null;
            $shot['characterName'] = $charData['name'] ?? $speaker;
            $shot['voiceId'] = $charData['voiceId'] ?? null;
            $shot['characterData'] = $charData['characterData'] ?? null;
        }

        // Build visual prompt
        $shot['visualPromptAddition'] = $this->buildSpeakingVisualPrompt(
            ['speaker' => $speaker, 'text' => $text],
            $shotType,
            $charData,
            $position
        );

        return $shot;
    }

    /**
     * Calculate shot duration based on text length.
     * Speaking rate: ~150 words per minute = ~2.5 words per second.
     *
     * @param string $text Dialogue text
     * @return int Duration in seconds (minimum 3s)
     */
    protected function calculateDurationFromTextLength(string $text): int
    {
        $wordCount = str_word_count($text);
        // 2.5 words per second + 1 second buffer for natural pacing
        $duration = ceil($wordCount / 2.5) + 1;
        return max(3, (int) $duration); // Minimum 3 seconds
    }

    /**
     * Validate dialogue scene decomposition.
     */
    public function validateDecomposition(array $shots): array
    {
        $errors = [];
        $warnings = [];

        // Check for empty shots
        foreach ($shots as $idx => $shot) {
            if (($shot['useMultitalk'] ?? false) && empty($shot['dialogue'])) {
                $errors[] = "Shot {$idx} marked for Multitalk but has no dialogue";
            }

            if (($shot['useMultitalk'] ?? false) && empty($shot['voiceId'])) {
                $warnings[] = "Shot {$idx} has no voice ID assigned, will use default";
            }
        }

        // Check for speaker coverage
        $speakingShots = array_filter($shots, fn($s) => $s['useMultitalk'] ?? false);
        if (count($speakingShots) < 2) {
            $warnings[] = "Dialogue scene has fewer than 2 speaking shots";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
