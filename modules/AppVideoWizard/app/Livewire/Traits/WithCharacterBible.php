<?php

namespace Modules\AppVideoWizard\Livewire\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Modules\AppVideoWizard\Services\CharacterLookService;
use Modules\AppVideoWizard\Services\BibleOrderingService;
use Modules\AppVideoWizard\Services\VoiceoverService;
use Modules\AppVideoWizard\Services\SpeechSegment;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Services\CinematicIntelligenceService;

/**
 * Character Bible Trait for VideoWizard
 *
 * This trait provides all Character Bible related functionality including:
 * - Modal control (toggle open/close)
 * - CRUD operations for characters
 * - Character traits and accessories management
 * - Voice configuration and presets
 * - Portrait generation and reference images
 * - Character DNA (detailed look system)
 * - Wardrobe management across scenes
 * - Character intelligence and state tracking
 * - Detected speaker synchronization
 *
 * All methods access the parent VideoWizard component's properties:
 * - $this->sceneMemory (character bible data)
 * - $this->script (scene/script data)
 * - $this->cinematicAnalysis (cinematic intelligence data)
 * - $this->characterIntelligence (detected speakers)
 * - $this->characterPortraitsGenerated
 * - $this->showCharacterBibleModal
 * - $this->isBatchUpdating
 * - $this->editingCharacterIndex
 *
 * @package Modules\AppVideoWizard\Livewire\Traits
 */
trait WithCharacterBible
{
    // =========================================================================
    // CHARACTER BIBLE UPDATED HOOK
    // =========================================================================

    /**
     * Handle sceneMemory.characterBible changes - triggers Scene DNA rebuild.
     * Livewire calls this for any change to sceneMemory.characterBible.*
     *
     * @param mixed $value The new value
     * @param string $key The nested path after "sceneMemory.characterBible"
     */
    public function updatedSceneMemoryCharacterBible($value, $key): void
    {
        if ($this->isBatchUpdating) {
            return;
        }

        // Skip if modal is open (will rebuild on close)
        if ($this->showCharacterBibleModal) {
            return;
        }

        $this->debouncedBuildSceneDNA();
    }

    // =========================================================================
    // CHARACTER BIBLE MODAL CONTROL
    // =========================================================================

    /**
     * Toggle Character Bible.
     */
    public function toggleCharacterBible(): void
    {
        $this->sceneMemory['characterBible']['enabled'] = !$this->sceneMemory['characterBible']['enabled'];
        $this->saveProject();
    }

    // =========================================================================
    // CHARACTER CRUD OPERATIONS
    // =========================================================================

    /**
     * Add character to Character Bible.
     */
    public function addCharacter(string $name = '', string $description = ''): void
    {
        $this->sceneMemory['characterBible']['characters'][] = [
            'id' => uniqid('char_'),
            'name' => $name,
            'description' => $description,
            'role' => 'Supporting',
            'scenes' => [],
            'traits' => [],
            'defaultExpression' => '',           // Default facial expression (e.g., "confident", "thoughtful")
            'attire' => '',                      // Legacy attire field for prompt compatibility
            'referenceImage' => null,
            'referenceImageBase64' => null,      // Base64 data for API calls (face consistency)
            'referenceImageMimeType' => null,    // MIME type (e.g., 'image/png')
            'referenceImageStatus' => 'none',    // 'none' | 'generating' | 'ready' | 'error'

            // ═══════════════════════════════════════════════════════════════════
            // CHARACTER VOICE SYSTEM - For lip-sync and voiceover
            // ═══════════════════════════════════════════════════════════════════
            'voice' => [
                'id' => null,               // TTS voice ID: alloy, echo, fable, onyx, nova, shimmer
                'gender' => null,           // male, female, neutral (auto-detected from description if null)
                'style' => 'natural',       // natural, warm, authoritative, energetic, calm
                'speed' => 1.0,             // 0.5 to 2.0
                'pitch' => 'medium',        // low, medium, high
            ],
            'isNarrator' => false,          // If true, this character serves as story narrator
            'speakingRole' => 'dialogue',   // dialogue, monologue, narrator, silent

            // ═══════════════════════════════════════════════════════════════════
            // CHARACTER LOOK SYSTEM - Structured fields for Hollywood consistency
            // ═══════════════════════════════════════════════════════════════════

            // Hair details - critical for visual consistency
            'hair' => [
                'style' => '',      // e.g., "sleek bob with side part", "long flowing waves"
                'color' => '',      // e.g., "jet black", "auburn red", "platinum blonde"
                'length' => '',     // e.g., "chin-length", "shoulder-length", "waist-length"
                'texture' => '',    // e.g., "straight glossy", "curly voluminous", "wavy"
            ],

            // Wardrobe/Costume - what the character wears
            'wardrobe' => [
                'outfit' => '',     // e.g., "fitted black tactical jacket over dark gray t-shirt"
                'colors' => '',     // e.g., "black, charcoal gray, silver accents"
                'style' => '',      // e.g., "tactical-tech", "corporate professional", "casual"
                'footwear' => '',   // e.g., "black combat boots", "white sneakers"
            ],

            // Makeup/Styling - the character's look
            'makeup' => [
                'style' => '',      // e.g., "minimal natural", "glamorous", "none"
                'details' => '',    // e.g., "subtle smoky eye, nude lip", "bold red lip"
            ],

            // Accessories - jewelry, glasses, watches, etc.
            'accessories' => [],    // Array of strings: ["silver stud earrings", "tactical watch"]
        ];
        $this->saveProject();
    }

    /**
     * Remove character from Character Bible.
     */
    public function removeCharacter(int $index): void
    {
        if (isset($this->sceneMemory['characterBible']['characters'][$index])) {
            unset($this->sceneMemory['characterBible']['characters'][$index]);
            $this->sceneMemory['characterBible']['characters'] = array_values($this->sceneMemory['characterBible']['characters']);

            // Reset editing index if needed
            $count = count($this->sceneMemory['characterBible']['characters']);
            if ($this->editingCharacterIndex >= $count) {
                $this->editingCharacterIndex = max(0, $count - 1);
            }

            $this->saveProject();
        }
    }

    /**
     * Add a character to the Story Bible (legacy support).
     */
    public function addBibleCharacter(): void
    {
        $characters = $this->storyBible['characters'] ?? [];
        $index = count($characters);

        $characters[] = [
            'id' => 'char_' . time() . '_' . $index,
            'name' => '',
            'role' => 'supporting',
            'description' => '',
            'arc' => '',
            'traits' => [],
            'appearsInActs' => [1],
            'referenceImage' => null,
        ];

        $this->storyBible['characters'] = $characters;
        $this->editingBibleCharacterIndex = $index;
        $this->saveProject();
    }

    /**
     * Edit a Story Bible character (legacy support).
     */
    public function editBibleCharacter(int $index): void
    {
        $this->editingBibleCharacterIndex = $index;
    }

    // =========================================================================
    // CHARACTER TRAITS MANAGEMENT
    // =========================================================================

    /**
     * Add a trait to a character.
     */
    public function addCharacterTrait(int $characterIndex, string $trait = ''): void
    {
        $trait = trim($trait);
        if (empty($trait)) {
            return;
        }

        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        // Initialize traits array if not exists
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'])) {
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = [];
        }

        // Avoid duplicates (case-insensitive)
        $existingTraits = array_map('strtolower', $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits']);
        if (in_array(strtolower($trait), $existingTraits)) {
            return;
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][] = $trait;
        $this->saveProject();
    }

    /**
     * Remove a trait from a character.
     */
    public function removeCharacterTrait(int $characterIndex, int $traitIndex): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][$traitIndex])) {
            return;
        }

        unset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][$traitIndex]);
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = array_values(
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits']
        );
        $this->saveProject();
    }

    // =========================================================================
    // CHARACTER ACCESSORIES MANAGEMENT
    // =========================================================================

    /**
     * Add an accessory to a character.
     */
    public function addCharacterAccessory(int $characterIndex, string $accessory = ''): void
    {
        $accessory = trim($accessory);
        if (empty($accessory)) {
            return;
        }

        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        // Initialize accessories array if not exists
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'])) {
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'] = [];
        }

        // Avoid duplicates (case-insensitive)
        $existingAccessories = array_map('strtolower', $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories']);
        if (in_array(strtolower($accessory), $existingAccessories)) {
            return;
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'][] = $accessory;
        $this->saveProject();
    }

    /**
     * Remove an accessory from a character.
     */
    public function removeCharacterAccessory(int $characterIndex, int $accessoryIndex): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'][$accessoryIndex])) {
            return;
        }

        unset($this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'][$accessoryIndex]);
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'] = array_values(
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories']
        );
        $this->saveProject();
    }

    // =========================================================================
    // CHARACTER VOICE MANAGEMENT
    // =========================================================================

    /**
     * Update character voice settings.
     * Used for lip-sync and character-specific voiceover.
     *
     * @param int $characterIndex Character index in Character Bible
     * @param string $field Voice field to update: id, gender, style, speed, pitch
     * @param mixed $value New value
     */
    public function updateCharacterVoice(int $characterIndex, string $field, $value): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        // Initialize voice array if not exists (for backwards compatibility)
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['voice'])) {
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['voice'] = [
                'id' => null,
                'gender' => null,
                'style' => 'natural',
                'speed' => 1.0,
                'pitch' => 'medium',
            ];
        }

        // Validate field
        $validFields = ['id', 'gender', 'style', 'speed', 'pitch'];
        if (!in_array($field, $validFields)) {
            return;
        }

        // Validate voice ID
        if ($field === 'id') {
            $validVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
            if ($value !== null && !in_array($value, $validVoices)) {
                return;
            }
        }

        // Validate speed range
        if ($field === 'speed') {
            $value = max(0.5, min(2.0, (float)$value));
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['voice'][$field] = $value;
        $this->saveProject();
    }

    /**
     * Set character as narrator (or not).
     * A narrator character tells the story in off-screen voiceover.
     *
     * @param int $characterIndex Character index
     * @param bool $isNarrator Whether this character is the narrator
     */
    public function setCharacterAsNarrator(int $characterIndex, bool $isNarrator): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        // If setting as narrator, unset any other narrator first
        if ($isNarrator) {
            foreach ($this->sceneMemory['characterBible']['characters'] as $idx => &$char) {
                $char['isNarrator'] = ($idx === $characterIndex);
            }
        } else {
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['isNarrator'] = false;
        }

        $this->saveProject();
    }

    /**
     * Update character speaking role.
     *
     * @param int $characterIndex Character index
     * @param string $role Speaking role: dialogue, monologue, narrator, silent
     */
    public function updateCharacterSpeakingRole(int $characterIndex, string $role): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $validRoles = ['dialogue', 'monologue', 'narrator', 'silent'];
        if (!in_array($role, $validRoles)) {
            return;
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['speakingRole'] = $role;
        $this->saveProject();
    }

    /**
     * Get the narrator character (if any).
     * Returns null if no character is designated as narrator.
     *
     * @return array|null Narrator character data or null
     */
    public function getNarratorCharacter(): ?array
    {
        foreach ($this->sceneMemory['characterBible']['characters'] ?? [] as $char) {
            if (!empty($char['isNarrator'])) {
                return $char;
            }
        }
        return null;
    }

    /**
     * Apply a voice preset to a character based on archetype.
     */
    public function applyCharacterVoicePreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $voicePresets = [
            'hero-male' => ['id' => 'onyx', 'gender' => 'male', 'style' => 'confident', 'speed' => 1.0, 'pitch' => 'medium'],
            'hero-female' => ['id' => 'nova', 'gender' => 'female', 'style' => 'confident', 'speed' => 1.0, 'pitch' => 'medium'],
            'villain-male' => ['id' => 'echo', 'gender' => 'male', 'style' => 'intense', 'speed' => 0.9, 'pitch' => 'low'],
            'villain-female' => ['id' => 'shimmer', 'gender' => 'female', 'style' => 'intense', 'speed' => 0.9, 'pitch' => 'medium'],
            'mentor' => ['id' => 'fable', 'gender' => 'neutral', 'style' => 'warm', 'speed' => 0.95, 'pitch' => 'medium'],
            'narrator' => ['id' => 'fable', 'gender' => 'neutral', 'style' => 'storytelling', 'speed' => 1.0, 'pitch' => 'medium'],
            'young-male' => ['id' => 'alloy', 'gender' => 'male', 'style' => 'energetic', 'speed' => 1.1, 'pitch' => 'medium'],
            'young-female' => ['id' => 'nova', 'gender' => 'female', 'style' => 'energetic', 'speed' => 1.1, 'pitch' => 'high'],
            'professional' => ['id' => 'alloy', 'gender' => 'neutral', 'style' => 'authoritative', 'speed' => 1.0, 'pitch' => 'medium'],
            'documentary' => ['id' => 'onyx', 'gender' => 'male', 'style' => 'authoritative', 'speed' => 0.95, 'pitch' => 'low'],
        ];

        if (!isset($voicePresets[$preset])) {
            return;
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['voice'] = $voicePresets[$preset];
        $this->saveProject();
    }

    // =========================================================================
    // CHARACTER PORTRAIT GENERATION
    // =========================================================================

    /**
     * PHASE 3: Generate character reference portraits.
     * Creates consistent reference images for each character in the Character Bible.
     * Called when entering storyboard step if portraits haven't been generated.
     */
    public function generateCharacterPortraits(): void
    {
        $characters = $this->sceneMemory['characterBible']['characters'] ?? [];

        if (empty($characters)) {
            Log::info('VideoWizard: No characters in Bible, skipping portrait generation');
            return;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($characters as $index => $character) {
            // Skip if portrait already exists (referenceImage or referenceImageBase64)
            if (!empty($character['referenceImage']) || !empty($character['referenceImageBase64'])) {
                $skipped++;
                continue;
            }

            // Build portrait prompt from character description
            $name = $character['name'] ?? 'Character';
            $appearance = $character['appearance'] ?? $character['description'] ?? '';

            if (empty($appearance)) {
                Log::warning('VideoWizard: Character has no appearance description', ['name' => $name]);
                continue;
            }

            try {
                // Use existing generateCharacterPortrait method
                $this->generateCharacterPortrait($index);
                $generated++;

                Log::info('VideoWizard: Generated character portrait', [
                    'character' => $name,
                    'index' => $index,
                ]);

                // Small delay to avoid rate limiting
                usleep(500000); // 500ms

            } catch (\Exception $e) {
                Log::error('VideoWizard: Failed to generate character portrait', [
                    'character' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->characterPortraitsGenerated = true;
        $this->saveProject();

        Log::info('VideoWizard: Character portrait generation complete', [
            'generated' => $generated,
            'skipped' => $skipped,
            'total' => count($characters),
        ]);
    }

    /**
     * PHASE 3: Handle character portrait generation event.
     * Triggered when entering storyboard if portraits need generation.
     */
    #[On('generate-character-portraits')]
    public function handleGenerateCharacterPortraits(): void
    {
        if ($this->characterPortraitsGenerated) {
            Log::debug('VideoWizard: Character portraits already generated, skipping');
            return;
        }

        Log::info('VideoWizard: Starting character portrait generation');
        $this->generateCharacterPortraits();
    }

    /**
     * Queue auto-generation of character portraits.
     * Directly generates portraits for all characters that need them.
     */
    public function queueAutoCharacterPortraits(): void
    {
        $characters = $this->sceneMemory['characterBible']['characters'] ?? [];

        if (empty($characters)) {
            Log::info('CharacterAutoGen: No characters to generate portraits for');
            return;
        }

        $toGenerate = [];
        foreach ($characters as $index => $char) {
            // Skip if already has portrait (check both new storage key and legacy base64)
            $hasPortrait = !empty($char['referenceImageStorageKey']) || !empty($char['referenceImageBase64']);
            if ($hasPortrait && ($char['referenceImageStatus'] ?? '') === 'ready') {
                continue;
            }

            // Skip if already generating or pending (prevent duplicate queuing)
            if (in_array($char['referenceImageStatus'] ?? '', ['generating', 'pending'])) {
                continue;
            }

            // Mark as pending
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'pending';
            $toGenerate[] = ['index' => $index, 'name' => $char['name'] ?? 'Unknown'];
        }

        if (empty($toGenerate)) {
            Log::info('CharacterAutoGen: All characters already have portraits');
            return;
        }

        $this->saveProject();

        Log::info('CharacterAutoGen: Marked ' . count($toGenerate) . ' characters as pending', [
            'characters' => array_column($toGenerate, 'name'),
        ]);

        // Dispatch event to start polling - generation will happen via polling to avoid HTTP timeout
        // This is fully async: no generation during the initial request
        Log::info('CharacterAutoGen: Dispatching continue-character-reference-generation event', [
            'type' => 'character',
            'remaining' => count($toGenerate),
        ]);
        $this->dispatch('continue-character-reference-generation', [
            'type' => 'character',
            'remaining' => count($toGenerate),
        ]);

        // Also dispatch a browser-visible debug event
        $this->dispatch('vw-debug', [
            'source' => 'queueAutoCharacterPortraits',
            'toGenerate' => count($toGenerate),
            'message' => 'Character portrait generation queued for polling',
        ]);
    }

    /**
     * Generate the next pending character portrait.
     * Called by polling to continue auto-generation without timeout.
     */
    public function generateNextPendingCharacterPortrait(): ?array
    {
        $characters = $this->sceneMemory['characterBible']['characters'] ?? [];

        foreach ($characters as $index => $char) {
            if (($char['referenceImageStatus'] ?? '') === 'pending') {
                try {
                    Log::info('CharacterAutoGen: Generating next pending portrait', [
                        'index' => $index,
                        'name' => $char['name'] ?? 'Unknown',
                    ]);
                    $this->generateCharacterPortrait($index);
                    return [
                        'success' => true,
                        'name' => $char['name'] ?? 'Unknown',
                        'remaining' => $this->countPendingCharacterPortraits(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('CharacterAutoGen: Failed to generate pending portrait', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    // Mark as failed so we don't loop forever
                    $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'failed';
                    $this->saveProject();
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'remaining' => $this->countPendingCharacterPortraits(),
                    ];
                }
            }
        }

        return ['success' => true, 'remaining' => 0, 'message' => 'No pending characters'];
    }

    /**
     * Count how many character portraits are still pending.
     */
    public function countPendingCharacterPortraits(): int
    {
        $count = 0;
        foreach ($this->sceneMemory['characterBible']['characters'] ?? [] as $char) {
            if (($char['referenceImageStatus'] ?? '') === 'pending') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Generate reference images for all characters that need them (one-click)
     * Dispatches generation jobs and updates progress
     */
    public function generateAllMissingCharacterReferences(): void
    {
        $charactersNeeding = $this->getCharactersNeedingReferences();

        if (empty($charactersNeeding)) {
            $this->dispatch('notification', [
                'type' => 'info',
                'message' => 'All characters already have reference images.',
            ]);
            return;
        }

        Log::info('[VideoWizard] One-click character reference generation', [
            'totalCharacters' => count($charactersNeeding),
            'characterNames' => array_column($charactersNeeding, 'name'),
        ]);

        $this->dispatch('generation-status', [
            'message' => 'Queuing ' . count($charactersNeeding) . ' character portrait(s) for generation...',
        ]);

        // Use the async polling approach to avoid HTTP timeout
        $this->queueAutoCharacterPortraits();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Character portrait generation started for ' . count($charactersNeeding) . ' character(s). They will be generated automatically.',
        ]);
    }

    /**
     * Get characters that need reference images (for one-click generation)
     *
     * @return array Characters missing reference images with indices
     */
    public function getCharactersNeedingReferences(): array
    {
        $characterBible = $this->sceneMemory['characterBible'] ?? [];

        if (!($characterBible['enabled'] ?? false)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getCharactersNeedingReferences($characterBible);
    }

    // =========================================================================
    // CHARACTER DNA (DETAILED LOOK SYSTEM)
    // =========================================================================

    /**
     * Build Character DNA template for prompt injection.
     * This creates a comprehensive, structured description that ensures
     * Hollywood-level consistency across all scene generations.
     */
    public function buildCharacterDNA(array $character): string
    {
        $name = $character['name'] ?? 'Character';
        $parts = [];

        // Identity/Face section
        if (!empty($character['description'])) {
            $parts[] = "IDENTITY: {$character['description']}";
        }

        // Hair section
        $hair = $character['hair'] ?? [];
        $hairParts = array_filter([
            $hair['color'] ?? '',
            $hair['style'] ?? '',
            $hair['length'] ?? '',
            $hair['texture'] ?? '',
        ]);
        if (!empty($hairParts)) {
            $parts[] = "HAIR: " . implode(', ', $hairParts) . ". MUST remain consistent - never different style/color/length.";
        }

        // Wardrobe section
        $wardrobe = $character['wardrobe'] ?? [];
        $wardrobeParts = [];
        if (!empty($wardrobe['outfit'])) {
            $wardrobeParts[] = $wardrobe['outfit'];
        }
        if (!empty($wardrobe['colors'])) {
            $wardrobeParts[] = "Color palette: {$wardrobe['colors']}";
        }
        if (!empty($wardrobe['footwear'])) {
            $wardrobeParts[] = "Footwear: {$wardrobe['footwear']}";
        }
        if (!empty($wardrobeParts)) {
            $parts[] = "WARDROBE: " . implode('. ', $wardrobeParts) . ". MUST wear this exact outfit unless scene specifies otherwise.";
        }

        // Makeup section
        $makeup = $character['makeup'] ?? [];
        $makeupParts = array_filter([
            $makeup['style'] ?? '',
            $makeup['details'] ?? '',
        ]);
        if (!empty($makeupParts)) {
            $parts[] = "MAKEUP/STYLING: " . implode(', ', $makeupParts) . ". Maintain consistent look.";
        }

        // Accessories section
        $accessories = $character['accessories'] ?? [];
        if (!empty($accessories)) {
            $parts[] = "ACCESSORIES: " . implode(', ', $accessories) . ". These items should be visible and consistent.";
        }

        if (empty($parts)) {
            return '';
        }

        return "CHARACTER DNA - {$name} (MUST MATCH EXACTLY):\n" . implode("\n", $parts);
    }

    /**
     * Get available Character DNA templates (Phase 4.2)
     *
     * @return array List of templates with key, name, description
     */
    public function getCharacterDNATemplates(): array
    {
        $lookService = app(CharacterLookService::class);
        return $lookService->getTemplates();
    }

    /**
     * Apply a Character DNA template to a character (Phase 4.2)
     *
     * @param int $characterIndex The character index in Character Bible
     * @param string $templateKey The template key to apply
     * @param bool $overwrite Whether to overwrite existing DNA fields
     */
    public function applyCharacterDNATemplate(int $characterIndex, string $templateKey, bool $overwrite = false): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $lookService = app(CharacterLookService::class);
        $character = $this->sceneMemory['characterBible']['characters'][$characterIndex];

        $updatedCharacter = $lookService->applyTemplate($character, $templateKey, $overwrite);

        $this->sceneMemory['characterBible']['characters'][$characterIndex] = $updatedCharacter;
        $this->saveProject();

        Log::info('[VideoWizard] DNA template applied', [
            'characterIndex' => $characterIndex,
            'characterName' => $character['name'] ?? 'Unknown',
            'template' => $templateKey,
            'overwrite' => $overwrite,
        ]);
    }

    /**
     * Auto-populate DNA fields from character description using AI (Phase 4.1)
     *
     * @param int $characterIndex The character index in Character Bible
     * @param bool $overwrite Whether to overwrite existing DNA fields
     */
    public function autoPopulateCharacterDNA(int $characterIndex, bool $overwrite = false): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $lookService = app(CharacterLookService::class);
        $character = $this->sceneMemory['characterBible']['characters'][$characterIndex];

        // Extract and merge DNA from description
        $updatedCharacter = $lookService->autoPopulateDNA($character, $overwrite);

        $this->sceneMemory['characterBible']['characters'][$characterIndex] = $updatedCharacter;
        $this->saveProject();

        Log::info('[VideoWizard] Character DNA auto-populated', [
            'characterIndex' => $characterIndex,
            'characterName' => $character['name'] ?? 'Unknown',
            'overwrite' => $overwrite,
        ]);

        $this->dispatch('character-dna-populated', [
            'characterIndex' => $characterIndex,
            'characterName' => $character['name'] ?? 'Unknown',
        ]);
    }

    /**
     * Batch auto-populate DNA for all characters in Character Bible (Phase 4.1)
     *
     * @param bool $overwrite Whether to overwrite existing DNA fields
     */
    public function batchAutoPopulateAllCharactersDNA(bool $overwrite = false): void
    {
        $characters = $this->sceneMemory['characterBible']['characters'] ?? [];

        if (empty($characters)) {
            return;
        }

        $lookService = app(CharacterLookService::class);
        $updatedCharacters = $lookService->batchAutoPopulateDNA($characters, $overwrite);

        $this->sceneMemory['characterBible']['characters'] = $updatedCharacters;
        $this->saveProject();

        Log::info('[VideoWizard] Batch DNA auto-population completed', [
            'totalCharacters' => count($characters),
            'overwrite' => $overwrite,
        ]);

        $this->dispatch('batch-dna-populated', [
            'totalCharacters' => count($characters),
        ]);
    }

    // =========================================================================
    // CHARACTER LOOK PRESETS
    // =========================================================================

    /**
     * Apply a complete look preset to a character (hair, wardrobe, makeup, accessories).
     * These presets ensure Hollywood-level visual consistency.
     */
    public function applyCharacterLookPreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $lookPresets = [
            'corporate-female' => [
                'hair' => ['style' => 'sleek professional blowout', 'color' => 'dark brown', 'length' => 'shoulder-length', 'texture' => 'straight polished'],
                'wardrobe' => ['outfit' => 'tailored charcoal blazer over white silk blouse, fitted dark trousers', 'colors' => 'charcoal, white, navy accents', 'style' => 'corporate professional', 'footwear' => 'black pointed-toe heels'],
                'makeup' => ['style' => 'polished professional', 'details' => 'neutral eyeshadow, defined brows, nude-pink lip, subtle contour'],
                'accessories' => ['pearl stud earrings', 'silver wristwatch', 'thin gold necklace'],
            ],
            'corporate-male' => [
                'hair' => ['style' => 'short tapered business cut', 'color' => 'dark brown', 'length' => 'short', 'texture' => 'neat styled'],
                'wardrobe' => ['outfit' => 'navy blue tailored suit, white dress shirt, dark tie', 'colors' => 'navy, white, silver accents', 'style' => 'corporate professional', 'footwear' => 'polished black oxford shoes'],
                'makeup' => ['style' => 'none', 'details' => 'clean groomed appearance'],
                'accessories' => ['silver wristwatch', 'wedding band', 'subtle cufflinks'],
            ],
            'tech-female' => [
                'hair' => ['style' => 'modern asymmetric bob', 'color' => 'black with subtle highlights', 'length' => 'chin-length', 'texture' => 'straight sleek'],
                'wardrobe' => ['outfit' => 'fitted black jacket over dark tech t-shirt, slim dark jeans', 'colors' => 'black, charcoal, electric blue accents', 'style' => 'tech-casual', 'footwear' => 'white minimalist sneakers'],
                'makeup' => ['style' => 'minimal modern', 'details' => 'subtle wing eyeliner, natural lip, dewy skin'],
                'accessories' => ['smart watch with black band', 'small geometric earrings', 'thin-framed glasses'],
            ],
            'tech-male' => [
                'hair' => ['style' => 'textured modern cut', 'color' => 'dark brown', 'length' => 'medium-short', 'texture' => 'slightly tousled'],
                'wardrobe' => ['outfit' => 'gray zip-up hoodie over dark t-shirt, dark slim jeans', 'colors' => 'gray, black, subtle blue', 'style' => 'tech-casual', 'footwear' => 'clean white sneakers'],
                'makeup' => ['style' => 'none', 'details' => 'natural groomed'],
                'accessories' => ['smart watch', 'wireless earbuds case clipped to belt'],
            ],
            'action-hero-female' => [
                'hair' => ['style' => 'practical ponytail or braided', 'color' => 'dark', 'length' => 'long pulled back', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'fitted tactical vest over dark compression top, cargo pants with utility belt', 'colors' => 'black, olive, tactical tan', 'style' => 'tactical combat', 'footwear' => 'black tactical boots'],
                'makeup' => ['style' => 'minimal combat-ready', 'details' => 'smudge-proof subtle eye, natural lip, matte skin'],
                'accessories' => ['tactical watch', 'dog tags', 'utility belt pouches'],
            ],
            'action-hero-male' => [
                'hair' => ['style' => 'short military-style or rugged', 'color' => 'dark', 'length' => 'short', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'fitted tactical jacket, dark henley shirt, military cargo pants', 'colors' => 'black, olive drab, tactical gray', 'style' => 'tactical combat', 'footwear' => 'worn combat boots'],
                'makeup' => ['style' => 'none', 'details' => 'weathered rugged appearance, possible stubble'],
                'accessories' => ['tactical watch', 'dog tags', 'weapon holster'],
            ],
            'scientist-female' => [
                'hair' => ['style' => 'practical bun or neat ponytail', 'color' => 'natural brown', 'length' => 'medium-long tied back', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'white lab coat over smart casual blouse, dark trousers', 'colors' => 'white, navy, muted tones', 'style' => 'academic professional', 'footwear' => 'sensible closed-toe flats'],
                'makeup' => ['style' => 'natural minimal', 'details' => 'light natural makeup, clear lip balm'],
                'accessories' => ['reading glasses', 'ID badge on lanyard', 'simple stud earrings'],
            ],
            'scientist-male' => [
                'hair' => ['style' => 'neat professional cut', 'color' => 'graying at temples', 'length' => 'short', 'texture' => 'neat'],
                'wardrobe' => ['outfit' => 'white lab coat over button-down shirt, khaki trousers', 'colors' => 'white, light blue, khaki', 'style' => 'academic professional', 'footwear' => 'brown leather shoes'],
                'makeup' => ['style' => 'none', 'details' => 'clean professional appearance'],
                'accessories' => ['wire-framed glasses', 'ID badge', 'pen in lab coat pocket'],
            ],
            'cyberpunk' => [
                'hair' => ['style' => 'edgy undercut or neon-streaked', 'color' => 'black with neon highlights', 'length' => 'asymmetric', 'texture' => 'styled spiky or sleek'],
                'wardrobe' => ['outfit' => 'leather jacket with LED accents, tech-wear bodysuit, tactical pants', 'colors' => 'black, neon cyan, magenta accents', 'style' => 'cyberpunk streetwear', 'footwear' => 'platform tech boots'],
                'makeup' => ['style' => 'cyber-glam', 'details' => 'neon eyeliner, holographic highlights, dark lip'],
                'accessories' => ['cyber-implant earpiece', 'LED wrist display', 'holographic jewelry'],
            ],
            'fantasy-warrior' => [
                'hair' => ['style' => 'long braided warrior style', 'color' => 'natural or silver', 'length' => 'long', 'texture' => 'thick braided'],
                'wardrobe' => ['outfit' => 'leather armor with metal pauldrons, worn tunic, belted', 'colors' => 'brown leather, silver metal, earth tones', 'style' => 'medieval warrior', 'footwear' => 'worn leather boots'],
                'makeup' => ['style' => 'battle-worn', 'details' => 'natural weathered look, possible war paint'],
                'accessories' => ['sword sheath on back', 'leather bracers', 'tribal pendant'],
            ],
        ];

        if (!isset($lookPresets[$preset])) {
            return;
        }

        $presetData = $lookPresets[$preset];

        // Apply the preset to character
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['hair'] = $presetData['hair'];
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['wardrobe'] = $presetData['wardrobe'];
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['makeup'] = $presetData['makeup'];
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['accessories'] = $presetData['accessories'];

        $this->saveProject();
    }

    // =========================================================================
    // CHARACTER WARDROBE MANAGEMENT
    // =========================================================================

    /**
     * Set intentional wardrobe change for a character at a specific scene (Phase 4.3)
     *
     * @param int $characterIndex The character index
     * @param int $sceneIndex The scene index where wardrobe changes
     * @param array $newWardrobe The new wardrobe for this scene
     * @param string|null $reason Optional reason for the change (e.g., "character changes after battle")
     */
    public function setCharacterWardrobeChange(
        int $characterIndex,
        int $sceneIndex,
        array $newWardrobe,
        ?string $reason = null
    ): void {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $lookService = app(CharacterLookService::class);
        $character = $this->sceneMemory['characterBible']['characters'][$characterIndex];

        $updatedCharacter = $lookService->setIntentionalWardrobeChange(
            $character,
            $sceneIndex,
            $newWardrobe,
            $reason
        );

        $this->sceneMemory['characterBible']['characters'][$characterIndex] = $updatedCharacter;
        $this->saveProject();

        Log::info('[VideoWizard] Wardrobe change set', [
            'characterIndex' => $characterIndex,
            'characterName' => $character['name'] ?? 'Unknown',
            'sceneIndex' => $sceneIndex,
            'reason' => $reason,
        ]);
    }

    /**
     * Get wardrobe for a character at a specific scene (Phase 4.3)
     *
     * @param int $characterIndex The character index
     * @param int $sceneIndex The scene index
     * @return array|null Wardrobe data or null
     */
    public function getCharacterWardrobeForScene(int $characterIndex, int $sceneIndex): ?array
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return null;
        }

        $lookService = app(CharacterLookService::class);
        $character = $this->sceneMemory['characterBible']['characters'][$characterIndex];

        return $lookService->getWardrobeForScene($character, $sceneIndex);
    }

    // =========================================================================
    // CHARACTER INTELLIGENCE & STATE TRACKING
    // =========================================================================

    /**
     * Update Character Intelligence settings.
     *
     * @deprecated Phase 1.5 - Character Intelligence UI removed.
     * Settings are now auto-detected from speech segments.
     * This method kept for backward compatibility but has no effect.
     */
    public function updateCharacterIntelligence(string $field, $value): void
    {
        // Log deprecation warning (development only)
        if (config('app.debug')) {
            Log::warning('VideoWizard: updateCharacterIntelligence is deprecated (Phase 1.5)', [
                'field' => $field,
                'value' => $value,
            ]);
        }
    }

    /**
     * Get character state for a specific character in a specific scene.
     */
    public function getCharacterStateForScene(string $charId, int $sceneIndex): ?array
    {
        return $this->cinematicAnalysis['characterStates'][$charId]['scenes'][$sceneIndex] ?? null;
    }

    /**
     * Get reference image chain for a character.
     */
    public function getCharacterImageChain(string $charId): ?array
    {
        return $this->cinematicAnalysis['imageChain'][$charId] ?? null;
    }

    /**
     * Validate shot characters against shot type rules.
     */
    public function validateShotCharacters(string $shotType, array $characterIds): array
    {
        if (!VwSetting::getValue('shot_type_rules_enabled', true)) {
            return [];
        }

        try {
            $cinematicService = app(CinematicIntelligenceService::class);
            return $cinematicService->validateShotCharacters($shotType, $characterIds);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * PHASE 3: Get character references with consistency options for image generation.
     * Combines reference images and consistency prompt into options array.
     *
     * @param int $sceneIndex Scene index
     * @return array Options to merge with image generation call
     */
    public function getCharacterConsistencyOptions(int $sceneIndex): array
    {
        $characterReferences = $this->getCharacterReferenceImages($sceneIndex);

        if (empty($characterReferences)) {
            return [];
        }

        $options = [
            'characterReferences' => $characterReferences,
        ];

        // Build character consistency prompt addition
        $consistencyPrompt = $this->buildCharacterConsistencyPrompt($characterReferences);
        if (!empty($consistencyPrompt)) {
            $options['characterConsistencyPrompt'] = $consistencyPrompt;
        }

        return $options;
    }

    // =========================================================================
    // DETECTED SPEAKERS SYNCHRONIZATION
    // =========================================================================

    /**
     * Sync detected speakers from script analysis to Character Bible.
     * Creates new character entries for detected speakers not already in Bible,
     * and auto-assigns voices based on detected gender/role.
     *
     * @param bool $overwrite If true, updates existing characters with detected info
     * @return array Summary of what was synced
     */
    public function syncDetectedSpeakersToCharacterBible(bool $overwrite = false): array
    {
        $detectedSpeakers = $this->characterIntelligence['detectedSpeakers'] ?? [];
        $existingCharacters = $this->sceneMemory['characterBible']['characters'] ?? [];

        $added = 0;
        $updated = 0;
        $skipped = 0;

        // Build map of existing character names (case-insensitive)
        $existingNames = [];
        foreach ($existingCharacters as $idx => $char) {
            $existingNames[strtoupper(trim($char['name'] ?? ''))] = $idx;
        }

        foreach ($detectedSpeakers as $speaker) {
            $speakerName = trim($speaker['name'] ?? $speaker);
            $speakerUpper = strtoupper($speakerName);

            // Skip narrator
            if ($speakerUpper === 'NARRATOR') {
                continue;
            }

            // Check if character already exists
            if (isset($existingNames[$speakerUpper])) {
                if ($overwrite) {
                    // Update existing character with detected info
                    $idx = $existingNames[$speakerUpper];
                    $this->updateCharacterFromDetection($idx, $speaker);
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }

            // Create new character entry
            $this->addCharacterFromDetection($speaker);
            $added++;
        }

        if ($added > 0 || $updated > 0) {
            $this->saveProject();

            Log::info('Synced detected speakers to Character Bible', [
                'added' => $added,
                'updated' => $updated,
                'skipped' => $skipped,
            ]);
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($this->sceneMemory['characterBible']['characters'] ?? []),
        ];
    }

    // =========================================================================
    // CHARACTER SORTING
    // =========================================================================

    /**
     * Get sorted characters with metadata for display (Phase 5.1)
     *
     * @param string $sortMethod Sorting method: 'smart', 'alphabetical', 'manual'
     * @return array Sorted characters with metadata
     */
    public function getSortedCharacters(string $sortMethod = 'smart'): array
    {
        $characters = $this->sceneMemory['characterBible']['characters'] ?? [];

        if (empty($characters)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getSortedCharactersWithMetadata($characters, $sortMethod);
    }
}
