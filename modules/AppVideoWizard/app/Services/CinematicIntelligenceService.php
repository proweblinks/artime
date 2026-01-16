<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\VwSetting;
use Illuminate\Support\Facades\Log;

/**
 * Cinematic Intelligence Service
 *
 * Advanced Hollywood-level production intelligence for:
 * - Character state tracking across scenes
 * - Emotional arc management
 * - Wardrobe continuity validation
 * - Location-character binding
 * - Character relationship mapping
 * - Narrative beat alignment
 * - Shot-type character rules
 * - Scene transition intelligence
 * - Reference image chaining
 * - Consistency scoring
 */
class CinematicIntelligenceService
{
    /**
     * Default emotional states for character arcs
     */
    protected const EMOTIONAL_STATES = [
        'confident', 'fearful', 'angry', 'joyful', 'sad', 'anxious',
        'determined', 'hopeful', 'desperate', 'triumphant', 'defeated',
        'curious', 'suspicious', 'loving', 'grieving', 'neutral',
    ];

    /**
     * Story beat types for narrative alignment
     */
    protected const STORY_BEATS = [
        'setup', 'inciting_incident', 'rising_action', 'midpoint',
        'complications', 'climax', 'falling_action', 'resolution',
    ];

    /**
     * Relationship types for character mapping
     */
    protected const RELATIONSHIP_TYPES = [
        'allies', 'enemies', 'romantic', 'family', 'mentor_student',
        'rivals', 'strangers', 'colleagues', 'friends',
    ];

    /**
     * Shot types and their character rules
     */
    protected const SHOT_TYPE_RULES = [
        'establishing' => [
            'maxCharacters' => 0,
            'focusOn' => null,
            'description' => 'Wide shot of location, typically no character focus',
        ],
        'wide' => [
            'maxCharacters' => 10,
            'focusOn' => null,
            'description' => 'Full scene view, can include multiple characters',
        ],
        'medium' => [
            'maxCharacters' => 3,
            'focusOn' => 'Main',
            'description' => 'Waist-up shot, typically 1-3 characters',
        ],
        'close-up' => [
            'maxCharacters' => 1,
            'focusOn' => 'Main',
            'description' => 'Face/detail shot, single character only',
        ],
        'extreme-close-up' => [
            'maxCharacters' => 1,
            'focusOn' => 'Main',
            'description' => 'Detail shot, single character feature',
        ],
        'reaction' => [
            'maxCharacters' => 1,
            'focusOn' => 'Supporting',
            'requiresSecondary' => true,
            'description' => 'Reaction shot of secondary character',
        ],
        'two-shot' => [
            'maxCharacters' => 2,
            'focusOn' => null,
            'description' => 'Two characters in frame',
        ],
        'over-shoulder' => [
            'maxCharacters' => 2,
            'focusOn' => 'Main',
            'description' => 'Over one characters shoulder to another',
        ],
        'group' => [
            'maxCharacters' => 10,
            'focusOn' => null,
            'description' => 'Multiple characters together',
        ],
        'pov' => [
            'maxCharacters' => 0,
            'focusOn' => null,
            'description' => 'Point of view shot, no character visible',
        ],
    ];

    /**
     * Scene transition types and their rules
     */
    protected const SCENE_TRANSITION_RULES = [
        'establishing' => [
            'requiresMainChar' => false,
            'suggestedShots' => ['establishing', 'wide'],
            'typicalDuration' => 'short',
        ],
        'dialogue' => [
            'requiresMainChar' => true,
            'suggestedShots' => ['medium', 'close-up', 'reaction', 'over-shoulder'],
            'typicalDuration' => 'medium',
        ],
        'action' => [
            'requiresMainChar' => true,
            'suggestedShots' => ['wide', 'medium', 'close-up'],
            'typicalDuration' => 'variable',
        ],
        'montage' => [
            'requiresMainChar' => false,
            'suggestedShots' => ['medium', 'close-up', 'wide'],
            'typicalDuration' => 'short',
        ],
        'emotional' => [
            'requiresMainChar' => true,
            'suggestedShots' => ['close-up', 'extreme-close-up', 'reaction'],
            'typicalDuration' => 'medium',
        ],
        'transition' => [
            'requiresMainChar' => false,
            'suggestedShots' => ['establishing', 'wide'],
            'typicalDuration' => 'short',
        ],
        'flashback' => [
            'requiresMainChar' => true,
            'suggestedShots' => ['medium', 'close-up'],
            'typicalDuration' => 'variable',
        ],
        'reveal' => [
            'requiresMainChar' => true,
            'suggestedShots' => ['wide', 'medium', 'close-up'],
            'typicalDuration' => 'short',
        ],
    ];

    // =========================================================================
    // IMPROVEMENT 1: CHARACTER STATE TRACKING
    // =========================================================================

    /**
     * Initialize character states for all scenes.
     * Creates a state map tracking each character's condition per scene.
     *
     * @param array $characters Character Bible characters
     * @param array $scenes Script scenes
     * @return array Character states per scene
     */
    public function initializeCharacterStates(array $characters, array $scenes): array
    {
        $characterStates = [];
        $totalScenes = count($scenes);

        foreach ($characters as $character) {
            $charId = $character['id'] ?? uniqid('char_');
            $charName = $character['name'] ?? 'Unknown';
            $appliedScenes = $character['appliedScenes'] ?? [];

            // Empty appliedScenes means "all scenes" - expand to full range
            if (empty($appliedScenes)) {
                $appliedScenes = range(0, $totalScenes - 1);
            }

            $characterStates[$charId] = [
                'name' => $charName,
                'role' => $character['role'] ?? 'Supporting',
                'baseWardrobe' => $character['wardrobe'] ?? [],
                'baseHair' => $character['hair'] ?? [],
                'scenes' => [],
            ];

            // Initialize state for each scene the character appears in
            foreach ($appliedScenes as $sceneIndex) {
                if ($sceneIndex < $totalScenes) {
                    $characterStates[$charId]['scenes'][$sceneIndex] = [
                        'mood' => $character['defaultExpression'] ?? 'neutral',
                        'wardrobe' => $character['wardrobe'] ?? [],
                        'hair' => $character['hair'] ?? [],
                        'injuries' => [],
                        'props' => [],
                        'wardrobeChange' => false,
                        'stateNotes' => '',
                    ];
                }
            }
        }

        Log::info('CinematicIntelligence: Character states initialized', [
            'characterCount' => count($characterStates),
            'totalScenes' => $totalScenes,
        ]);

        return $characterStates;
    }

    /**
     * Update character state for a specific scene.
     */
    public function updateCharacterState(
        array &$characterStates,
        string $charId,
        int $sceneIndex,
        array $stateChanges
    ): void {
        if (!isset($characterStates[$charId]['scenes'][$sceneIndex])) {
            $characterStates[$charId]['scenes'][$sceneIndex] = [
                'mood' => 'neutral',
                'wardrobe' => $characterStates[$charId]['baseWardrobe'] ?? [],
                'hair' => $characterStates[$charId]['baseHair'] ?? [],
                'injuries' => [],
                'props' => [],
                'wardrobeChange' => false,
                'stateNotes' => '',
            ];
        }

        $characterStates[$charId]['scenes'][$sceneIndex] = array_merge(
            $characterStates[$charId]['scenes'][$sceneIndex],
            $stateChanges
        );
    }

    /**
     * Propagate state changes forward through scenes.
     * E.g., if character gets injured in Scene 3, injury persists in Scene 4+
     */
    public function propagateStateChanges(array &$characterStates): void
    {
        foreach ($characterStates as $charId => &$charData) {
            $scenes = $charData['scenes'] ?? [];
            ksort($scenes); // Ensure scenes are in order

            $currentInjuries = [];
            $currentProps = [];
            $currentWardrobe = $charData['baseWardrobe'] ?? [];

            foreach ($scenes as $sceneIndex => &$state) {
                // Propagate injuries (they persist unless healed)
                $state['injuries'] = array_unique(array_merge($currentInjuries, $state['injuries'] ?? []));
                $currentInjuries = $state['injuries'];

                // Propagate props (they persist unless dropped)
                if (!empty($state['props'])) {
                    $currentProps = array_merge($currentProps, $state['props']);
                }
                $state['props'] = array_unique($currentProps);

                // Propagate wardrobe changes
                if ($state['wardrobeChange'] ?? false) {
                    $currentWardrobe = $state['wardrobe'];
                } else {
                    $state['wardrobe'] = $currentWardrobe;
                }
            }

            $charData['scenes'] = $scenes;
        }
    }

    // =========================================================================
    // IMPROVEMENT 2: SHOT-TYPE CHARACTER RULES
    // =========================================================================

    /**
     * Get shot type rules.
     */
    public function getShotTypeRules(): array
    {
        return self::SHOT_TYPE_RULES;
    }

    /**
     * Validate character count for a shot type.
     */
    public function validateShotCharacters(string $shotType, array $characters): array
    {
        $rules = self::SHOT_TYPE_RULES[$shotType] ?? self::SHOT_TYPE_RULES['medium'];
        $issues = [];

        $maxChars = $rules['maxCharacters'];
        $charCount = count($characters);

        if ($maxChars > 0 && $charCount > $maxChars) {
            $issues[] = [
                'type' => 'character_overflow',
                'message' => "Shot type '{$shotType}' allows max {$maxChars} characters, but {$charCount} assigned",
                'severity' => 'warning',
            ];
        }

        if (($rules['requiresSecondary'] ?? false) && $charCount < 2) {
            $issues[] = [
                'type' => 'missing_secondary',
                'message' => "Shot type '{$shotType}' requires a secondary character for reaction",
                'severity' => 'warning',
            ];
        }

        return $issues;
    }

    /**
     * Suggest optimal shot type based on characters and scene context.
     */
    public function suggestShotType(int $characterCount, string $sceneType = 'dialogue'): string
    {
        if ($characterCount === 0) {
            return 'establishing';
        }

        if ($characterCount === 1) {
            return $sceneType === 'emotional' ? 'close-up' : 'medium';
        }

        if ($characterCount === 2) {
            return $sceneType === 'dialogue' ? 'over-shoulder' : 'two-shot';
        }

        return $characterCount <= 5 ? 'medium' : 'wide';
    }

    // =========================================================================
    // IMPROVEMENT 3: LOCATION-CHARACTER AUTO-BINDING
    // =========================================================================

    /**
     * Infer character presence based on location continuity.
     * If character is in Location X in Scene N, and Scene N+1 is also Location X,
     * character is likely present.
     */
    public function inferCharacterPresenceFromLocations(
        array $characters,
        array $scenes,
        array $locationBible
    ): array {
        $inferences = [];
        $locationCharMap = []; // Track which characters were last seen at each location
        $totalScenes = count($scenes);

        // Build initial map from existing character-scene assignments
        foreach ($characters as $char) {
            $charId = $char['id'] ?? '';
            $appliedScenes = $char['appliedScenes'] ?? [];

            // Empty appliedScenes means "all scenes" - expand to full range
            if (empty($appliedScenes)) {
                $appliedScenes = range(0, $totalScenes - 1);
            }

            foreach ($appliedScenes as $sceneIndex) {
                if (isset($scenes[$sceneIndex])) {
                    $locationId = $this->getSceneLocationId($scenes[$sceneIndex], $locationBible);
                    if ($locationId) {
                        $locationCharMap[$locationId][$charId] = true;
                    }
                }
            }
        }

        // Now check for scenes where characters might be inferred
        foreach ($scenes as $sceneIndex => $scene) {
            $locationId = $this->getSceneLocationId($scene, $locationBible);
            if (!$locationId) {
                continue;
            }

            // Get characters who were at this location
            $locationChars = array_keys($locationCharMap[$locationId] ?? []);

            foreach ($characters as $char) {
                $charId = $char['id'] ?? '';
                $appliedScenes = $char['appliedScenes'] ?? [];

                // Skip if character is already assigned to this scene
                // Empty array means "applies to ALL scenes" (default behavior)
                if (empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                    continue;
                }

                // Skip if character was never at this location
                if (!in_array($charId, $locationChars)) {
                    continue;
                }

                // Check if character was in adjacent scenes (only if they have explicit scene assignments)
                $inPrevScene = in_array($sceneIndex - 1, $appliedScenes);
                $inNextScene = in_array($sceneIndex + 1, $appliedScenes);

                // If character was in prev OR next scene at same location, suggest presence
                if ($inPrevScene || $inNextScene) {
                    $inferences[] = [
                        'characterId' => $charId,
                        'characterName' => $char['name'] ?? 'Unknown',
                        'sceneIndex' => $sceneIndex,
                        'locationId' => $locationId,
                        'reason' => $inPrevScene
                            ? "Character was at this location in previous scene"
                            : "Character will be at this location in next scene",
                        'confidence' => 0.8,
                    ];
                }
            }
        }

        Log::info('CinematicIntelligence: Location-character inferences', [
            'inferenceCount' => count($inferences),
        ]);

        return $inferences;
    }

    /**
     * Get location ID from scene data.
     */
    protected function getSceneLocationId(array $scene, array $locationBible): ?string
    {
        // Try to match scene location to location bible
        $sceneLocation = $scene['location'] ?? $scene['visualDescription'] ?? '';

        foreach ($locationBible['locations'] ?? [] as $location) {
            $locationName = $location['name'] ?? '';
            if (stripos($sceneLocation, $locationName) !== false) {
                return $location['id'] ?? $locationName;
            }
        }

        return null;
    }

    // =========================================================================
    // IMPROVEMENT 4: EMOTIONAL ARC ENGINE
    // =========================================================================

    /**
     * Generate emotional arc for a character across scenes.
     */
    public function generateEmotionalArc(
        string $charRole,
        int $totalScenes,
        string $genre = 'drama'
    ): array {
        $arc = [];

        // Define arc patterns based on role and genre
        $arcPattern = $this->getArcPattern($charRole, $genre);

        foreach (range(0, $totalScenes - 1) as $sceneIndex) {
            $progress = $totalScenes > 1 ? $sceneIndex / ($totalScenes - 1) : 0;
            $arc[$sceneIndex] = $this->interpolateEmotion($arcPattern, $progress);
        }

        return $arc;
    }

    /**
     * Get arc pattern based on character role and genre.
     */
    protected function getArcPattern(string $role, string $genre): array
    {
        // Default hero's journey emotional arc
        $patterns = [
            'Main' => [
                'drama' => ['start' => 'neutral', 'q1' => 'hopeful', 'mid' => 'anxious', 'q3' => 'desperate', 'end' => 'triumphant'],
                'comedy' => ['start' => 'joyful', 'q1' => 'curious', 'mid' => 'anxious', 'q3' => 'hopeful', 'end' => 'joyful'],
                'thriller' => ['start' => 'suspicious', 'q1' => 'anxious', 'mid' => 'fearful', 'q3' => 'desperate', 'end' => 'determined'],
                'action' => ['start' => 'confident', 'q1' => 'determined', 'mid' => 'angry', 'q3' => 'desperate', 'end' => 'triumphant'],
                'horror' => ['start' => 'curious', 'q1' => 'suspicious', 'mid' => 'fearful', 'q3' => 'desperate', 'end' => 'fearful'],
            ],
            'Supporting' => [
                'drama' => ['start' => 'neutral', 'q1' => 'supportive', 'mid' => 'concerned', 'q3' => 'hopeful', 'end' => 'joyful'],
                'default' => ['start' => 'neutral', 'q1' => 'curious', 'mid' => 'concerned', 'q3' => 'hopeful', 'end' => 'neutral'],
            ],
            'Background' => [
                'default' => ['start' => 'neutral', 'q1' => 'neutral', 'mid' => 'neutral', 'q3' => 'neutral', 'end' => 'neutral'],
            ],
        ];

        $rolePatterns = $patterns[$role] ?? $patterns['Supporting'];
        return $rolePatterns[$genre] ?? $rolePatterns['default'] ?? $patterns['Supporting']['default'];
    }

    /**
     * Interpolate emotion based on progress through arc.
     */
    protected function interpolateEmotion(array $arcPattern, float $progress): string
    {
        if ($progress <= 0.25) {
            return $arcPattern['start'] ?? 'neutral';
        } elseif ($progress <= 0.5) {
            return $arcPattern['q1'] ?? $arcPattern['start'] ?? 'neutral';
        } elseif ($progress <= 0.75) {
            return $arcPattern['mid'] ?? 'neutral';
        } elseif ($progress <= 0.9) {
            return $arcPattern['q3'] ?? $arcPattern['mid'] ?? 'neutral';
        } else {
            return $arcPattern['end'] ?? 'neutral';
        }
    }

    /**
     * Apply emotional arc to character states.
     */
    public function applyEmotionalArc(
        array &$characterStates,
        string $charId,
        array $arc
    ): void {
        if (!isset($characterStates[$charId])) {
            return;
        }

        foreach ($arc as $sceneIndex => $emotion) {
            if (isset($characterStates[$charId]['scenes'][$sceneIndex])) {
                $characterStates[$charId]['scenes'][$sceneIndex]['mood'] = $emotion;
                $characterStates[$charId]['scenes'][$sceneIndex]['arcGenerated'] = true;
            }
        }
    }

    // =========================================================================
    // IMPROVEMENT 5: WARDROBE CONTINUITY VALIDATOR
    // =========================================================================

    /**
     * Validate wardrobe continuity across scenes.
     */
    public function validateWardrobeContinuity(array $characterStates): array
    {
        $issues = [];

        foreach ($characterStates as $charId => $charData) {
            $charName = $charData['name'] ?? 'Unknown';
            $scenes = $charData['scenes'] ?? [];
            ksort($scenes);

            $previousWardrobe = null;
            $previousScene = null;

            foreach ($scenes as $sceneIndex => $state) {
                $currentWardrobe = $state['wardrobe'] ?? [];
                $wardrobeChange = $state['wardrobeChange'] ?? false;

                if ($previousWardrobe !== null && !$wardrobeChange) {
                    // Check for unexpected wardrobe changes
                    $wardrobeDiff = $this->compareWardrobe($previousWardrobe, $currentWardrobe);

                    if (!empty($wardrobeDiff)) {
                        $issues[] = [
                            'type' => 'wardrobe_inconsistency',
                            'characterId' => $charId,
                            'characterName' => $charName,
                            'sceneIndex' => $sceneIndex,
                            'previousScene' => $previousScene,
                            'differences' => $wardrobeDiff,
                            'message' => "'{$charName}' has wardrobe change between scenes {$previousScene} and {$sceneIndex} without wardrobeChange flag",
                            'severity' => 'warning',
                            'suggestion' => "Either set wardrobeChange=true for scene {$sceneIndex} or ensure wardrobe matches",
                        ];
                    }
                }

                $previousWardrobe = $currentWardrobe;
                $previousScene = $sceneIndex;
            }
        }

        Log::info('CinematicIntelligence: Wardrobe validation complete', [
            'issueCount' => count($issues),
        ]);

        return $issues;
    }

    /**
     * Compare two wardrobe arrays and return differences.
     */
    protected function compareWardrobe(array $prev, array $curr): array
    {
        $diff = [];

        $keys = ['outfit', 'colors', 'style', 'footwear'];
        foreach ($keys as $key) {
            $prevVal = $prev[$key] ?? '';
            $currVal = $curr[$key] ?? '';

            if (!empty($prevVal) && !empty($currVal) && $prevVal !== $currVal) {
                $diff[$key] = [
                    'from' => $prevVal,
                    'to' => $currVal,
                ];
            }
        }

        return $diff;
    }

    // =========================================================================
    // IMPROVEMENT 6: SCENE TRANSITION INTELLIGENCE
    // =========================================================================

    /**
     * Get scene transition rules.
     */
    public function getSceneTransitionRules(): array
    {
        return self::SCENE_TRANSITION_RULES;
    }

    /**
     * Classify scene type based on content.
     */
    public function classifySceneType(array $scene): string
    {
        $narration = strtolower($scene['narration'] ?? '');
        $visual = strtolower($scene['visualDescription'] ?? $scene['visual'] ?? '');
        $hasDialogue = !empty($scene['dialogue']);
        $combined = $narration . ' ' . $visual;

        // Check for establishing shots
        if (preg_match('/(establishing|exterior|wide shot of|overview|skyline|building)/i', $combined)) {
            return 'establishing';
        }

        // Check for action sequences
        if (preg_match('/(fight|chase|run|explod|crash|action|battle|combat)/i', $combined)) {
            return 'action';
        }

        // Check for emotional moments
        if (preg_match('/(cry|tear|emotional|hug|kiss|grief|joy|sad|happy|love)/i', $combined)) {
            return 'emotional';
        }

        // Check for flashbacks
        if (preg_match('/(flashback|memory|remember|past|years ago)/i', $combined)) {
            return 'flashback';
        }

        // Check for reveals
        if (preg_match('/(reveal|discover|realize|find out|truth|secret)/i', $combined)) {
            return 'reveal';
        }

        // Check for montage
        if (preg_match('/(montage|series of|time pass|later)/i', $combined)) {
            return 'montage';
        }

        // Check for transitions
        if (preg_match('/(meanwhile|elsewhere|later|next day|transition)/i', $combined)) {
            return 'transition';
        }

        // Default to dialogue if there's dialogue, otherwise establishing
        return $hasDialogue ? 'dialogue' : 'establishing';
    }

    /**
     * Get suggested shots for a scene type.
     */
    public function getSuggestedShots(string $sceneType): array
    {
        $rules = self::SCENE_TRANSITION_RULES[$sceneType] ?? self::SCENE_TRANSITION_RULES['dialogue'];
        return $rules['suggestedShots'] ?? ['medium'];
    }

    // =========================================================================
    // IMPROVEMENT 7: REFERENCE IMAGE CHAIN
    // =========================================================================

    /**
     * Build reference image chain for character consistency.
     * Returns which previous scene images should be referenced for each scene.
     */
    public function buildReferenceImageChain(
        array $characters,
        array $scenes,
        array $generatedImages = []
    ): array {
        $chain = [];
        $totalScenes = count($generatedImages);

        foreach ($characters as $char) {
            $charId = $char['id'] ?? '';
            $charName = $char['name'] ?? 'Unknown';
            $appliedScenes = $char['appliedScenes'] ?? [];

            // Empty appliedScenes means "all scenes" - expand to full range
            if (empty($appliedScenes)) {
                $appliedScenes = range(0, max(0, $totalScenes - 1));
            }
            sort($appliedScenes);

            if (empty($appliedScenes)) {
                continue;
            }

            $chain[$charId] = [
                'name' => $charName,
                'firstAppearance' => $appliedScenes[0],
                'references' => [],
            ];

            // For each scene after the first, reference the first appearance
            foreach ($appliedScenes as $index => $sceneIndex) {
                if ($index === 0) {
                    // First appearance - use portrait if available
                    $chain[$charId]['references'][$sceneIndex] = [
                        'type' => 'portrait',
                        'source' => $char['referenceImage'] ?? null,
                    ];
                } else {
                    // Subsequent appearances - reference first scene image
                    $firstScene = $appliedScenes[0];
                    $chain[$charId]['references'][$sceneIndex] = [
                        'type' => 'scene_image',
                        'sourceScene' => $firstScene,
                        'source' => $generatedImages[$firstScene][$charId] ?? null,
                        'fallback' => $char['referenceImage'] ?? null,
                    ];
                }
            }
        }

        return $chain;
    }

    /**
     * Get reference images for a specific scene generation.
     */
    public function getReferenceImagesForScene(
        int $sceneIndex,
        array $imageChain,
        array $charactersInScene
    ): array {
        $references = [];

        foreach ($charactersInScene as $charId) {
            if (isset($imageChain[$charId]['references'][$sceneIndex])) {
                $ref = $imageChain[$charId]['references'][$sceneIndex];
                if (!empty($ref['source'])) {
                    $references[$charId] = $ref['source'];
                } elseif (!empty($ref['fallback'])) {
                    $references[$charId] = $ref['fallback'];
                }
            }
        }

        return $references;
    }

    // =========================================================================
    // IMPROVEMENT 8: CHARACTER RELATIONSHIP MAPPING
    // =========================================================================

    /**
     * Initialize character relationships.
     */
    public function initializeRelationships(array $characters): array
    {
        $relationships = [];

        // Auto-detect some relationships based on roles and naming
        foreach ($characters as $i => $char1) {
            foreach ($characters as $j => $char2) {
                if ($i >= $j) continue; // Skip self and duplicates

                $char1Id = $char1['id'] ?? "char_{$i}";
                $char2Id = $char2['id'] ?? "char_{$j}";

                // Default to colleagues/strangers
                $type = 'strangers';

                // Infer from names (family names, etc.)
                $name1 = strtolower($char1['name'] ?? '');
                $name2 = strtolower($char2['name'] ?? '');

                // Check for obvious relationships in names
                if (preg_match('/(father|mother|son|daughter|brother|sister)/i', $name1 . ' ' . $name2)) {
                    $type = 'family';
                }

                $relationships[] = [
                    'character1' => $char1Id,
                    'character2' => $char2Id,
                    'type' => $type,
                    'autoDetected' => true,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Get relationship type names.
     */
    public function getRelationshipTypes(): array
    {
        return self::RELATIONSHIP_TYPES;
    }

    /**
     * Get framing suggestion based on relationship.
     */
    public function getRelationshipFraming(string $relationshipType): array
    {
        $framingGuides = [
            'allies' => [
                'proximity' => 'close',
                'suggestedShots' => ['two-shot', 'medium'],
                'bodyLanguage' => 'open, facing each other',
            ],
            'enemies' => [
                'proximity' => 'distant',
                'suggestedShots' => ['over-shoulder', 'wide'],
                'bodyLanguage' => 'tense, guarded',
            ],
            'romantic' => [
                'proximity' => 'intimate',
                'suggestedShots' => ['two-shot', 'close-up'],
                'bodyLanguage' => 'close, touching',
            ],
            'mentor_student' => [
                'proximity' => 'respectful',
                'suggestedShots' => ['two-shot', 'over-shoulder'],
                'bodyLanguage' => 'attentive, teaching',
            ],
            'rivals' => [
                'proximity' => 'medium',
                'suggestedShots' => ['two-shot', 'medium'],
                'bodyLanguage' => 'competitive, watchful',
            ],
            'family' => [
                'proximity' => 'close',
                'suggestedShots' => ['group', 'two-shot'],
                'bodyLanguage' => 'comfortable, familiar',
            ],
            'strangers' => [
                'proximity' => 'distant',
                'suggestedShots' => ['wide', 'medium'],
                'bodyLanguage' => 'neutral, reserved',
            ],
        ];

        return $framingGuides[$relationshipType] ?? $framingGuides['strangers'];
    }

    // =========================================================================
    // IMPROVEMENT 9: NARRATIVE BEAT ALIGNMENT
    // =========================================================================

    /**
     * Assign story beats to scenes.
     */
    public function assignStoryBeats(array $scenes): array
    {
        $totalScenes = count($scenes);
        if ($totalScenes === 0) {
            return [];
        }

        $beats = [];

        foreach ($scenes as $index => $scene) {
            $progress = $totalScenes > 1 ? $index / ($totalScenes - 1) : 0;
            $beats[$index] = $this->determineStoryBeat($progress, $scene);
        }

        return $beats;
    }

    /**
     * Determine story beat based on scene position and content.
     */
    protected function determineStoryBeat(float $progress, array $scene): string
    {
        // First check content for explicit beat indicators
        $content = strtolower(($scene['narration'] ?? '') . ' ' . ($scene['visualDescription'] ?? ''));

        if (preg_match('/(introduce|meet|begin|start)/i', $content) && $progress < 0.2) {
            return 'setup';
        }
        if (preg_match('/(inciting|catalyst|change|discover)/i', $content) && $progress < 0.3) {
            return 'inciting_incident';
        }
        if (preg_match('/(climax|final|confronta|showdown)/i', $content) && $progress > 0.7) {
            return 'climax';
        }
        if (preg_match('/(resolve|end|conclude|finally)/i', $content) && $progress > 0.9) {
            return 'resolution';
        }

        // Fall back to position-based assignment
        if ($progress <= 0.15) {
            return 'setup';
        } elseif ($progress <= 0.25) {
            return 'inciting_incident';
        } elseif ($progress <= 0.45) {
            return 'rising_action';
        } elseif ($progress <= 0.55) {
            return 'midpoint';
        } elseif ($progress <= 0.75) {
            return 'complications';
        } elseif ($progress <= 0.85) {
            return 'climax';
        } elseif ($progress <= 0.95) {
            return 'falling_action';
        } else {
            return 'resolution';
        }
    }

    /**
     * Get story beat types.
     */
    public function getStoryBeats(): array
    {
        return self::STORY_BEATS;
    }

    /**
     * Get shot coverage requirements for a story beat.
     */
    public function getBeatCoverageRequirements(string $beat): array
    {
        $requirements = [
            'setup' => [
                'minShots' => 2,
                'requiredTypes' => ['establishing', 'medium'],
                'mainCharRequired' => true,
            ],
            'inciting_incident' => [
                'minShots' => 3,
                'requiredTypes' => ['medium', 'close-up'],
                'mainCharRequired' => true,
            ],
            'rising_action' => [
                'minShots' => 2,
                'requiredTypes' => ['medium'],
                'mainCharRequired' => true,
            ],
            'midpoint' => [
                'minShots' => 3,
                'requiredTypes' => ['medium', 'close-up', 'reaction'],
                'mainCharRequired' => true,
            ],
            'complications' => [
                'minShots' => 2,
                'requiredTypes' => ['medium'],
                'mainCharRequired' => true,
            ],
            'climax' => [
                'minShots' => 4,
                'requiredTypes' => ['wide', 'medium', 'close-up'],
                'mainCharRequired' => true,
            ],
            'falling_action' => [
                'minShots' => 2,
                'requiredTypes' => ['medium', 'close-up'],
                'mainCharRequired' => true,
            ],
            'resolution' => [
                'minShots' => 2,
                'requiredTypes' => ['medium', 'wide'],
                'mainCharRequired' => true,
            ],
        ];

        return $requirements[$beat] ?? $requirements['rising_action'];
    }

    // =========================================================================
    // IMPROVEMENT 10: CONSISTENCY SCORING SYSTEM
    // =========================================================================

    /**
     * Calculate comprehensive consistency score.
     */
    public function calculateConsistencyScore(
        array $characters,
        array $scenes,
        array $characterStates,
        array $storyBeats,
        array $locationBible = [],
        array $generatedImages = []
    ): array {
        $scores = [
            'characterPresence' => $this->scoreCharacterPresence($characters, $scenes),
            'wardrobeContinuity' => $this->scoreWardrobeContinuity($characterStates),
            'emotionalArcAlignment' => $this->scoreEmotionalArcAlignment($characterStates),
            'locationCharacterBinding' => $this->scoreLocationBinding($characters, $scenes, $locationBible),
            'storyBeatCoverage' => $this->scoreStoryBeatCoverage($storyBeats, $scenes, $characters),
        ];

        // Calculate overall score (weighted average)
        $weights = [
            'characterPresence' => 0.25,
            'wardrobeContinuity' => 0.20,
            'emotionalArcAlignment' => 0.15,
            'locationCharacterBinding' => 0.15,
            'storyBeatCoverage' => 0.25,
        ];

        $weightedSum = 0;
        foreach ($scores as $key => $score) {
            $weightedSum += $score['score'] * ($weights[$key] ?? 0.2);
        }

        $scores['overallScore'] = round($weightedSum, 2);
        $scores['grade'] = $this->getGrade($scores['overallScore']);

        // Collect all issues
        $allIssues = [];
        foreach ($scores as $key => $data) {
            if (is_array($data) && isset($data['issues'])) {
                $allIssues = array_merge($allIssues, $data['issues']);
            }
        }
        $scores['allIssues'] = $allIssues;
        $scores['issueCount'] = count($allIssues);

        Log::info('CinematicIntelligence: Consistency score calculated', [
            'overallScore' => $scores['overallScore'],
            'grade' => $scores['grade'],
            'issueCount' => $scores['issueCount'],
        ]);

        return $scores;
    }

    /**
     * Score character presence.
     */
    protected function scoreCharacterPresence(array $characters, array $scenes): array
    {
        $totalScenes = count($scenes);
        $issues = [];
        $scores = [];

        foreach ($characters as $char) {
            $role = $char['role'] ?? 'Supporting';
            $appliedScenes = $char['appliedScenes'] ?? [];
            $charName = $char['name'] ?? 'Unknown';

            // Empty appliedScenes means "all scenes" - count as total scenes
            $sceneCount = empty($appliedScenes) ? $totalScenes : count($appliedScenes);

            $targetPercent = match ($role) {
                'Main' => 0.70,
                'Supporting' => 0.40,
                default => 0.10,
            };

            $actualPercent = $totalScenes > 0 ? $sceneCount / $totalScenes : 0;
            $charScore = min(1.0, $actualPercent / $targetPercent);
            $scores[] = $charScore;

            if ($actualPercent < $targetPercent && $role !== 'Background') {
                $issues[] = [
                    'type' => 'character_presence_low',
                    'character' => $charName,
                    'role' => $role,
                    'actual' => round($actualPercent * 100) . '%',
                    'target' => round($targetPercent * 100) . '%',
                    'message' => "{$charName} ({$role}) appears in {$sceneCount}/{$totalScenes} scenes (" .
                        round($actualPercent * 100) . "%), target is " . round($targetPercent * 100) . "%",
                ];
            }
        }

        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 1.0;

        return [
            'score' => round($avgScore, 2),
            'issues' => $issues,
        ];
    }

    /**
     * Score wardrobe continuity.
     */
    protected function scoreWardrobeContinuity(array $characterStates): array
    {
        $issues = $this->validateWardrobeContinuity($characterStates);
        $score = max(0, 1.0 - (count($issues) * 0.1)); // -10% per issue

        return [
            'score' => round($score, 2),
            'issues' => $issues,
        ];
    }

    /**
     * Score emotional arc alignment.
     */
    protected function scoreEmotionalArcAlignment(array $characterStates): array
    {
        $issues = [];
        $alignedCount = 0;
        $totalCount = 0;

        foreach ($characterStates as $charId => $charData) {
            $scenes = $charData['scenes'] ?? [];
            foreach ($scenes as $sceneIndex => $state) {
                $totalCount++;
                if (!empty($state['mood']) && $state['mood'] !== 'neutral') {
                    $alignedCount++;
                } else {
                    $issues[] = [
                        'type' => 'missing_emotional_state',
                        'character' => $charData['name'] ?? 'Unknown',
                        'sceneIndex' => $sceneIndex,
                        'message' => "Scene {$sceneIndex} has no specific emotional state for {$charData['name']}",
                    ];
                }
            }
        }

        $score = $totalCount > 0 ? $alignedCount / $totalCount : 1.0;

        return [
            'score' => round($score, 2),
            'issues' => array_slice($issues, 0, 5), // Limit to 5 issues
        ];
    }

    /**
     * Score location-character binding.
     */
    protected function scoreLocationBinding(array $characters, array $scenes, array $locationBible): array
    {
        if (empty($locationBible['locations'] ?? [])) {
            return ['score' => 1.0, 'issues' => []];
        }

        $inferences = $this->inferCharacterPresenceFromLocations($characters, $scenes, $locationBible);
        $score = max(0, 1.0 - (count($inferences) * 0.05)); // -5% per missing inference

        $issues = array_map(function ($inf) {
            return [
                'type' => 'location_character_gap',
                'character' => $inf['characterName'],
                'sceneIndex' => $inf['sceneIndex'],
                'message' => $inf['reason'],
            ];
        }, array_slice($inferences, 0, 5));

        return [
            'score' => round($score, 2),
            'issues' => $issues,
        ];
    }

    /**
     * Score story beat coverage.
     */
    protected function scoreStoryBeatCoverage(array $storyBeats, array $scenes, array $characters): array
    {
        $issues = [];
        $coveredBeats = 0;
        $totalBeats = count(self::STORY_BEATS);

        // Check which beats are covered
        $beatsCovered = array_unique(array_values($storyBeats));

        foreach (self::STORY_BEATS as $beat) {
            if (in_array($beat, $beatsCovered)) {
                $coveredBeats++;
            } else {
                $issues[] = [
                    'type' => 'missing_story_beat',
                    'beat' => $beat,
                    'message' => "Story beat '{$beat}' is not covered in any scene",
                ];
            }
        }

        // Check main character presence in key beats
        $keyBeats = ['inciting_incident', 'midpoint', 'climax', 'resolution'];
        $mainChars = array_filter($characters, fn($c) => ($c['role'] ?? '') === 'Main');

        foreach ($keyBeats as $keyBeat) {
            $beatScenes = array_keys(array_filter($storyBeats, fn($b) => $b === $keyBeat));
            foreach ($mainChars as $char) {
                $charScenes = $char['appliedScenes'] ?? [];
                // Empty appliedScenes means "all scenes" - character is present in all beats
                $present = empty($charScenes) || !empty(array_intersect($beatScenes, $charScenes));
                if (!$present && !empty($beatScenes)) {
                    $issues[] = [
                        'type' => 'main_char_missing_key_beat',
                        'character' => $char['name'] ?? 'Unknown',
                        'beat' => $keyBeat,
                        'message' => "Main character '{$char['name']}' missing from key beat '{$keyBeat}'",
                    ];
                }
            }
        }

        $score = $totalBeats > 0 ? $coveredBeats / $totalBeats : 1.0;

        return [
            'score' => round($score, 2),
            'issues' => $issues,
        ];
    }

    /**
     * Convert score to letter grade.
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 0.95) return 'A+';
        if ($score >= 0.90) return 'A';
        if ($score >= 0.85) return 'A-';
        if ($score >= 0.80) return 'B+';
        if ($score >= 0.75) return 'B';
        if ($score >= 0.70) return 'B-';
        if ($score >= 0.65) return 'C+';
        if ($score >= 0.60) return 'C';
        if ($score >= 0.55) return 'C-';
        if ($score >= 0.50) return 'D';
        return 'F';
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Get available emotional states.
     */
    public function getEmotionalStates(): array
    {
        return self::EMOTIONAL_STATES;
    }

    /**
     * Check if cinematic intelligence is enabled.
     */
    public function isEnabled(): bool
    {
        return VwSetting::getValue('cinematic_intelligence_enabled', true) === true
            || VwSetting::getValue('cinematic_intelligence_enabled', 'true') === 'true';
    }

    /**
     * Run full cinematic analysis on a project.
     */
    public function analyzeProject(
        array $script,
        array $characterBible,
        array $locationBible = [],
        array $styleBible = [],
        string $genre = 'drama'
    ): array {
        $scenes = $script['scenes'] ?? [];
        $characters = $characterBible['characters'] ?? [];

        if (empty($scenes) || empty($characters)) {
            return [
                'success' => false,
                'error' => 'No scenes or characters to analyze',
            ];
        }

        // Initialize all components
        $characterStates = $this->initializeCharacterStates($characters, $scenes);
        $storyBeats = $this->assignStoryBeats($scenes);
        $relationships = $this->initializeRelationships($characters);
        $imageChain = $this->buildReferenceImageChain($characters, $scenes);

        // Apply emotional arcs
        foreach ($characters as $char) {
            $charId = $char['id'] ?? '';
            $role = $char['role'] ?? 'Supporting';
            $appliedScenes = $char['appliedScenes'] ?? [];

            // Empty appliedScenes means "all scenes" - character should get emotional arc
            // Only skip if charId is missing
            if (!empty($charId)) {
                $arc = $this->generateEmotionalArc($role, count($scenes), $genre);
                $this->applyEmotionalArc($characterStates, $charId, $arc);
            }
        }

        // Propagate state changes
        $this->propagateStateChanges($characterStates);

        // Get location inferences
        $locationInferences = $this->inferCharacterPresenceFromLocations(
            $characters,
            $scenes,
            $locationBible
        );

        // Classify scenes
        $sceneTypes = [];
        foreach ($scenes as $index => $scene) {
            $sceneTypes[$index] = $this->classifySceneType($scene);
        }

        // Calculate consistency score
        $consistencyScore = $this->calculateConsistencyScore(
            $characters,
            $scenes,
            $characterStates,
            $storyBeats,
            $locationBible
        );

        return [
            'success' => true,
            'characterStates' => $characterStates,
            'storyBeats' => $storyBeats,
            'sceneTypes' => $sceneTypes,
            'relationships' => $relationships,
            'imageChain' => $imageChain,
            'locationInferences' => $locationInferences,
            'consistencyScore' => $consistencyScore,
        ];
    }
}
