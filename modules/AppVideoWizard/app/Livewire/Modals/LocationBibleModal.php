<?php

namespace Modules\AppVideoWizard\Livewire\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\BibleOrderingService;
use Modules\AppVideoWizard\Services\ReferenceImageStorageService;

/**
 * LocationBibleModal - Child Livewire Component for Location Bible management
 *
 * This component handles all Location Bible UI and state management:
 * - Modal visibility and control
 * - CRUD operations for locations
 * - Location state changes across scenes
 * - Scene-location mapping (one-to-one enforcement)
 * - Reference image uploads
 *
 * Reference generation is dispatched to parent VideoWizard which has access to
 * ImageGenerationService and project context.
 *
 * @package Modules\AppVideoWizard\Livewire\Modals
 */
class LocationBibleModal extends Component
{
    use WithFileUploads;

    // =========================================================================
    // PROPS FROM PARENT (via wire:model or attributes)
    // =========================================================================

    /**
     * Location Bible data - two-way bound with parent via wire:model
     */
    #[Modelable]
    public array $locationBible = [
        'enabled' => false,
        'locations' => [],
    ];

    /**
     * Scene data for scene-location mapping
     */
    public array $scenes = [];

    /**
     * Project ID for storage operations
     */
    #[Locked]
    public ?int $projectId = null;

    /**
     * Visual mode for generation prompts
     */
    public string $visualMode = 'cinematic-realistic';

    /**
     * Content language for generation prompts
     */
    public string $contentLanguage = 'en';

    /**
     * Story Bible data for sync (optional)
     */
    public array $storyBible = [];

    // =========================================================================
    // LOCAL STATE
    // =========================================================================

    /**
     * Modal visibility
     */
    public bool $show = false;

    /**
     * Currently editing location index
     */
    public int $editingLocationIndex = 0;

    /**
     * File upload for reference images
     */
    public $locationImageUpload;

    /**
     * Error message
     */
    public ?string $error = null;

    /**
     * Generation in progress flag
     */
    public bool $isGeneratingLocationRef = false;

    /**
     * Syncing from Story Bible flag
     */
    public bool $isSyncingLocationBible = false;

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function mount(
        array $locationBible = [],
        array $scenes = [],
        ?int $projectId = null,
        string $visualMode = 'cinematic-realistic',
        string $contentLanguage = 'en',
        array $storyBible = []
    ): void {
        $this->locationBible = $locationBible ?: [
            'enabled' => false,
            'locations' => [],
        ];
        $this->scenes = $scenes;
        $this->projectId = $projectId;
        $this->visualMode = $visualMode;
        $this->contentLanguage = $contentLanguage;
        $this->storyBible = $storyBible;
    }

    public function render()
    {
        return view('appvideowizard::livewire.modals.location-bible-modal');
    }

    // =========================================================================
    // MODAL CONTROL (via events from parent)
    // =========================================================================

    /**
     * Open the Location Bible modal
     * Triggered by parent dispatching 'open-location-bible' event
     */
    #[On('open-location-bible')]
    public function openModal(): void
    {
        $this->show = true;
        // Set editing index to first location if exists, otherwise 0
        $this->editingLocationIndex = !empty($this->locationBible['locations']) ? 0 : 0;

        // Auto-sync from Story Bible if it has locations
        if (!empty($this->storyBible['locations']) && ($this->storyBible['status'] ?? '') === 'ready') {
            $this->isSyncingLocationBible = true;
            $this->syncStoryBibleToLocationBible();
            $this->isSyncingLocationBible = false;
        }
    }

    /**
     * Close the modal and notify parent
     */
    public function closeModal(): void
    {
        $this->show = false;

        // Dispatch updated location bible to parent for persistence and Scene DNA rebuild
        $this->dispatch('location-bible-updated', locationBible: $this->locationBible);
        $this->dispatch('location-bible-closed');
    }

    // =========================================================================
    // LOCATION CRUD OPERATIONS
    // =========================================================================

    /**
     * Add a new location to the Location Bible
     */
    public function addLocation(string $name = '', string $description = ''): void
    {
        $this->locationBible['locations'][] = [
            'id' => uniqid('loc_'),
            'name' => $name ?: __('New Location'),
            'type' => 'exterior',
            'timeOfDay' => 'day',
            'weather' => 'clear',
            'atmosphere' => '',
            'mood' => '',
            'lightingStyle' => '',
            'description' => $description,
            'scenes' => [],
            'stateChanges' => [],
            'referenceImage' => null,
            'referenceImageBase64' => null,
            'referenceImageMimeType' => null,
            'referenceImageStatus' => 'none',
            'referenceImageStorageKey' => null,
        ];

        // Auto-select the newly added location for editing
        $this->editingLocationIndex = count($this->locationBible['locations']) - 1;
    }

    /**
     * Remove a location from the Location Bible
     */
    public function removeLocation(int $index): void
    {
        if (isset($this->locationBible['locations'][$index])) {
            unset($this->locationBible['locations'][$index]);
            $this->locationBible['locations'] = array_values($this->locationBible['locations']);

            // Reset editing index if needed
            $count = count($this->locationBible['locations']);
            if ($this->editingLocationIndex >= $count) {
                $this->editingLocationIndex = max(0, $count - 1);
            }
        }
    }

    /**
     * Select a location for editing
     */
    public function editLocation(int $index): void
    {
        $this->editingLocationIndex = $index;
    }

    // =========================================================================
    // LOCATION STATE CHANGES
    // =========================================================================

    /**
     * Add a state change to a location for a specific scene
     */
    public function addLocationState(int $locationIndex, int $sceneIndex, string $state = ''): void
    {
        $state = trim($state);
        if (empty($state)) {
            return;
        }

        if (!isset($this->locationBible['locations'][$locationIndex])) {
            return;
        }

        // Initialize stateChanges array if not exists
        if (!isset($this->locationBible['locations'][$locationIndex]['stateChanges'])) {
            $this->locationBible['locations'][$locationIndex]['stateChanges'] = [];
        }

        // Check if state already exists for this scene - update it
        $found = false;
        foreach ($this->locationBible['locations'][$locationIndex]['stateChanges'] as $idx => $change) {
            $changeSceneIdx = $change['sceneIndex'] ?? $change['scene'] ?? -1;
            if ($changeSceneIdx === $sceneIndex) {
                $this->locationBible['locations'][$locationIndex]['stateChanges'][$idx] = array_merge(
                    $this->locationBible['locations'][$locationIndex]['stateChanges'][$idx],
                    ['sceneIndex' => $sceneIndex, 'stateDescription' => $state]
                );
                $found = true;
                break;
            }
        }

        // Add new state change if not found
        if (!$found) {
            $this->locationBible['locations'][$locationIndex]['stateChanges'][] = [
                'sceneIndex' => $sceneIndex,
                'stateDescription' => $state,
                'timeOfDay' => null,
                'weather' => null,
            ];

            // Sort by scene index
            usort(
                $this->locationBible['locations'][$locationIndex]['stateChanges'],
                fn($a, $b) => ($a['sceneIndex'] ?? $a['scene'] ?? 0) <=> ($b['sceneIndex'] ?? $b['scene'] ?? 0)
            );
        }
    }

    /**
     * Remove a state change from a location
     */
    public function removeLocationState(int $locationIndex, int $stateIndex): void
    {
        if (!isset($this->locationBible['locations'][$locationIndex]['stateChanges'][$stateIndex])) {
            return;
        }

        unset($this->locationBible['locations'][$locationIndex]['stateChanges'][$stateIndex]);
        $this->locationBible['locations'][$locationIndex]['stateChanges'] = array_values(
            $this->locationBible['locations'][$locationIndex]['stateChanges']
        );
    }

    /**
     * Apply a preset state progression to a location
     */
    public function applyLocationStatePreset(int $locationIndex, string $preset): void
    {
        if (!isset($this->locationBible['locations'][$locationIndex])) {
            return;
        }

        $scenes = $this->locationBible['locations'][$locationIndex]['scenes'] ?? [];
        if (count($scenes) < 2) {
            return; // Need at least 2 scenes for a state progression
        }

        // Sort scenes
        sort($scenes);
        $firstScene = $scenes[0];
        $lastScene = $scenes[count($scenes) - 1];

        $presets = [
            'destruction' => [
                ['state' => 'pristine, intact'],
                ['state' => 'damaged, destruction visible'],
            ],
            'time-of-day' => [
                ['state' => 'morning light, fresh atmosphere', 'timeOfDay' => 'dawn'],
                ['state' => 'evening, golden hour lighting', 'timeOfDay' => 'golden-hour'],
            ],
            'weather-change' => [
                ['state' => 'clear skies, bright', 'weather' => 'clear'],
                ['state' => 'stormy, dramatic clouds', 'weather' => 'stormy'],
            ],
            'abandonment' => [
                ['state' => 'inhabited, active, signs of life'],
                ['state' => 'abandoned, dusty, overgrown'],
            ],
            'transformation' => [
                ['state' => 'ordinary, mundane'],
                ['state' => 'transformed, magical, ethereal'],
            ],
            'tension' => [
                ['state' => 'calm, peaceful'],
                ['state' => 'tense, foreboding'],
            ],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        // Apply first state to first scene, second state to last scene
        // Include timeOfDay/weather overrides if preset provides them
        $this->locationBible['locations'][$locationIndex]['stateChanges'] = [
            [
                'sceneIndex' => $firstScene,
                'stateDescription' => $presets[$preset][0]['state'],
                'timeOfDay' => $presets[$preset][0]['timeOfDay'] ?? null,
                'weather' => $presets[$preset][0]['weather'] ?? null,
            ],
            [
                'sceneIndex' => $lastScene,
                'stateDescription' => $presets[$preset][1]['state'],
                'timeOfDay' => $presets[$preset][1]['timeOfDay'] ?? null,
                'weather' => $presets[$preset][1]['weather'] ?? null,
            ],
        ];
    }

    // =========================================================================
    // SCENE-LOCATION MAPPING (One-to-One Enforcement)
    // =========================================================================

    /**
     * Toggle location assignment to a scene
     * Enforces ONE location per scene - adding a scene to this location removes it from others
     */
    public function toggleLocationScene(int $locIndex, int $sceneIndex): void
    {
        if (!isset($this->locationBible['locations'][$locIndex])) {
            return;
        }

        $scenes = $this->locationBible['locations'][$locIndex]['scenes'] ?? [];

        // Check if scene is already assigned to this location
        $flipped = array_flip($scenes);
        if (isset($flipped[$sceneIndex])) {
            // Removing scene from this location
            $scenes = array_values(array_filter($scenes, fn($s) => $s !== $sceneIndex));
        } else {
            // Adding scene to this location - REMOVE from all other locations first
            // This enforces one-location-per-scene rule
            foreach ($this->locationBible['locations'] as $idx => &$otherLoc) {
                if ($idx !== $locIndex && isset($otherLoc['scenes'])) {
                    $otherLoc['scenes'] = array_values(array_filter(
                        $otherLoc['scenes'],
                        fn($s) => $s !== $sceneIndex
                    ));
                }
            }
            unset($otherLoc); // Break reference
            $scenes[] = $sceneIndex;
        }

        sort($scenes);
        $this->locationBible['locations'][$locIndex]['scenes'] = $scenes;

        // Mark as user-modified so sync won't overwrite
        $this->locationBible['locations'][$locIndex]['userModifiedScenes'] = true;
    }

    /**
     * Apply location to all scenes
     * Clears all other locations' scenes since one location now owns all scenes
     */
    public function applyLocationToAllScenes(int $locIndex): void
    {
        if (!isset($this->locationBible['locations'][$locIndex])) {
            return;
        }

        $sceneCount = count($this->scenes);

        // Clear scenes from ALL other locations
        foreach ($this->locationBible['locations'] as $idx => &$loc) {
            if ($idx !== $locIndex) {
                $loc['scenes'] = [];
            }
        }
        unset($loc);

        // Assign all scenes to this location
        $this->locationBible['locations'][$locIndex]['scenes'] = range(0, $sceneCount - 1);
        $this->locationBible['locations'][$locIndex]['userModifiedScenes'] = true;
    }

    // =========================================================================
    // REFERENCE IMAGE OPERATIONS
    // =========================================================================

    /**
     * Generate reference image - dispatch to parent
     * Parent VideoWizard has access to ImageGenerationService and project context
     */
    public function generateLocationReference(int $index): void
    {
        if (!isset($this->locationBible['locations'][$index])) {
            $this->error = __('Location not found at index: ') . $index;
            return;
        }

        // Mark as generating locally
        $this->locationBible['locations'][$index]['referenceImageStatus'] = 'generating';
        $this->isGeneratingLocationRef = true;

        // Dispatch to parent to handle actual generation
        $this->dispatch('generate-location-reference', locationIndex: $index);
    }

    /**
     * Handle reference generation result from parent
     */
    #[On('location-reference-generated')]
    public function handleReferenceGenerated(int $locationIndex, ?string $referenceImage, ?string $referenceImageStorageKey, string $status, ?string $error = null): void
    {
        if (!isset($this->locationBible['locations'][$locationIndex])) {
            return;
        }

        $this->locationBible['locations'][$locationIndex]['referenceImage'] = $referenceImage;
        $this->locationBible['locations'][$locationIndex]['referenceImageStorageKey'] = $referenceImageStorageKey;
        $this->locationBible['locations'][$locationIndex]['referenceImageStatus'] = $status;

        if ($error) {
            $this->error = $error;
        }

        $this->isGeneratingLocationRef = false;
    }

    /**
     * Upload reference image
     */
    public function uploadLocationReference(int $index): void
    {
        if (!$this->locationImageUpload) {
            return;
        }

        if (!isset($this->locationBible['locations'][$index])) {
            $this->error = __('Location not found');
            return;
        }

        try {
            $storageService = app(ReferenceImageStorageService::class);

            // Store the uploaded image
            $path = $this->locationImageUpload->store('reference-images/locations', 'public');
            $url = asset('storage/' . $path);

            // Update location
            $this->locationBible['locations'][$index]['referenceImage'] = $url;
            $this->locationBible['locations'][$index]['referenceImageStatus'] = 'ready';
            $this->locationBible['locations'][$index]['referenceImageSource'] = 'upload';

            $this->locationImageUpload = null;
        } catch (\Exception $e) {
            Log::error('LocationBibleModal: Failed to upload reference', [
                'error' => $e->getMessage(),
            ]);
            $this->error = __('Failed to upload image: ') . $e->getMessage();
        }
    }

    /**
     * Remove reference image from a location
     */
    public function removeLocationReference(int $index): void
    {
        if (!isset($this->locationBible['locations'][$index])) {
            return;
        }

        $this->locationBible['locations'][$index]['referenceImage'] = null;
        $this->locationBible['locations'][$index]['referenceImageBase64'] = null;
        $this->locationBible['locations'][$index]['referenceImageStorageKey'] = null;
        $this->locationBible['locations'][$index]['referenceImageStatus'] = 'none';
        $this->locationBible['locations'][$index]['referenceImageSource'] = null;
    }

    /**
     * Generate all missing location references
     * Dispatches to parent for batch generation
     */
    public function generateAllMissingLocationReferences(): void
    {
        $locationsNeeding = $this->getLocationsNeedingReferences();

        if (empty($locationsNeeding)) {
            return;
        }

        // Mark all as pending
        foreach ($locationsNeeding as $loc) {
            $idx = $loc['index'];
            $this->locationBible['locations'][$idx]['referenceImageStatus'] = 'pending';
        }

        // Dispatch to parent
        $this->dispatch('generate-all-location-references');
    }

    /**
     * Get locations that need reference images
     */
    public function getLocationsNeedingReferences(): array
    {
        if (!($this->locationBible['enabled'] ?? false)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getLocationsNeedingReferences($this->locationBible);
    }

    // =========================================================================
    // LOCATION TEMPLATES
    // =========================================================================

    /**
     * Apply a location template
     */
    public function applyLocationTemplate(string $template): void
    {
        $templates = [
            'urban-night' => [
                'name' => __('Urban Night Scene'),
                'type' => 'exterior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Urban cityscape at night with neon lights, wet streets reflecting colors, and dramatic shadows.',
                'lightingStyle' => 'Neon signs with wet surface reflections, dramatic rim lighting',
            ],
            'forest' => [
                'name' => __('Forest'),
                'type' => 'exterior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'peaceful',
                'description' => 'Dense forest with tall trees, dappled sunlight filtering through leaves, and lush undergrowth.',
                'lightingStyle' => 'Natural dappled sunlight through canopy',
            ],
            'tech-lab' => [
                'name' => __('Technology Lab'),
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'energetic',
                'description' => 'Modern high-tech laboratory with screens, equipment, and clean minimalist design.',
                'lightingStyle' => 'Cool blue-white LED lighting with screen glow',
            ],
            'desert' => [
                'name' => __('Desert'),
                'type' => 'exterior',
                'timeOfDay' => 'golden-hour',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'Vast desert landscape with sand dunes, dramatic shadows, and warm golden lighting.',
                'lightingStyle' => 'Warm golden hour with long dramatic shadows',
            ],
            'industrial' => [
                'name' => __('Industrial'),
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'tense',
                'description' => 'Industrial warehouse or factory with metal structures, machinery, and gritty atmosphere.',
                'lightingStyle' => 'Harsh overhead industrial lighting with deep shadows',
            ],
            'space' => [
                'name' => __('Space'),
                'type' => 'abstract',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Deep space environment with stars, nebulae, and cosmic phenomena.',
                'lightingStyle' => 'Starlight and nebula glow, rim lighting from distant stars',
            ],
        ];

        if (isset($templates[$template])) {
            // Add new location with template settings
            $this->locationBible['locations'][] = array_merge(
                [
                    'id' => uniqid('loc_'),
                    'scenes' => [],
                    'stateChanges' => [],
                    'referenceImage' => null,
                    'referenceImageBase64' => null,
                    'referenceImageMimeType' => null,
                    'referenceImageStatus' => 'none',
                    'referenceImageStorageKey' => null,
                ],
                $templates[$template]
            );
            $this->editingLocationIndex = count($this->locationBible['locations']) - 1;
        }
    }

    // =========================================================================
    // STORY BIBLE SYNC
    // =========================================================================

    /**
     * Sync locations from Story Bible
     */
    public function syncStoryBibleToLocationBible(): void
    {
        if (empty($this->storyBible['locations'])) {
            return;
        }

        $existingNames = array_map(
            fn($loc) => strtolower(trim($loc['name'] ?? '')),
            $this->locationBible['locations'] ?? []
        );

        foreach ($this->storyBible['locations'] as $storyLocation) {
            $name = $storyLocation['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            // Skip if already exists
            if (in_array(strtolower(trim($name)), $existingNames)) {
                continue;
            }

            // Add new location from Story Bible
            $this->locationBible['locations'][] = [
                'id' => uniqid('loc_'),
                'name' => $name,
                'type' => $storyLocation['type'] ?? 'exterior',
                'timeOfDay' => $storyLocation['timeOfDay'] ?? 'day',
                'weather' => $storyLocation['weather'] ?? 'clear',
                'atmosphere' => $storyLocation['atmosphere'] ?? '',
                'mood' => $storyLocation['mood'] ?? '',
                'lightingStyle' => $storyLocation['lightingStyle'] ?? '',
                'description' => $storyLocation['description'] ?? '',
                'scenes' => $storyLocation['scenes'] ?? [],
                'stateChanges' => [],
                'referenceImage' => null,
                'referenceImageBase64' => null,
                'referenceImageMimeType' => null,
                'referenceImageStatus' => 'none',
                'referenceImageStorageKey' => null,
            ];

            $existingNames[] = strtolower(trim($name));
        }
    }

    // =========================================================================
    // AUTO-DETECT LOCATIONS (dispatch to parent)
    // =========================================================================

    /**
     * Auto-detect locations from script
     * Dispatches to parent which has AI services
     */
    public function autoDetectLocations(): void
    {
        $this->dispatch('auto-detect-locations');
    }

    // =========================================================================
    // SORTED LOCATIONS FOR DISPLAY
    // =========================================================================

    /**
     * Get sorted locations with metadata for display
     */
    public function getSortedLocations(string $sortMethod = 'first_appearance'): array
    {
        $locations = $this->locationBible['locations'] ?? [];

        if (empty($locations)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getSortedLocationsWithMetadata($locations, $this->scenes, $sortMethod);
    }
}
