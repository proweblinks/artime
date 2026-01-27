<?php

namespace Modules\AppVideoWizard\Livewire\Traits;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\BibleOrderingService;

/**
 * Location Bible Trait for VideoWizard
 *
 * This trait provides all Location Bible related functionality including:
 * - Modal control (toggle open/close)
 * - CRUD operations for locations
 * - Location state changes across scenes
 * - State preset application
 * - Reference image generation
 * - Location sorting
 *
 * All methods access the parent VideoWizard component's properties:
 * - $this->sceneMemory (location bible data)
 * - $this->script (scene/script data)
 * - $this->showLocationBibleModal
 * - $this->isBatchUpdating
 * - $this->editingLocationIndex
 *
 * @package Modules\AppVideoWizard\Livewire\Traits
 */
trait WithLocationBible
{
    // =========================================================================
    // LOCATION BIBLE UPDATED HOOK
    // =========================================================================

    /**
     * Handle sceneMemory.locationBible changes - triggers Scene DNA rebuild.
     * Livewire calls this for any change to sceneMemory.locationBible.*
     *
     * @param mixed $value The new value
     * @param string $key The nested path after "sceneMemory.locationBible"
     */
    public function updatedSceneMemoryLocationBible($value, $key): void
    {
        if ($this->isBatchUpdating) {
            return;
        }

        // Skip if modal is open (will rebuild on close)
        if ($this->showLocationBibleModal) {
            return;
        }

        $this->debouncedBuildSceneDNA();
    }

    // =========================================================================
    // LOCATION BIBLE MODAL CONTROL
    // =========================================================================

    /**
     * Toggle Location Bible.
     */
    public function toggleLocationBible(): void
    {
        $this->sceneMemory['locationBible']['enabled'] = !$this->sceneMemory['locationBible']['enabled'];
        $this->saveProject();
    }

    // =========================================================================
    // LOCATION CRUD OPERATIONS
    // =========================================================================

    /**
     * Add location to Location Bible.
     */
    public function addLocation(string $name = '', string $description = ''): void
    {
        $this->sceneMemory['locationBible']['locations'][] = [
            'id' => uniqid('loc_'),
            'name' => $name ?: __('New Location'),
            'type' => 'exterior',
            'timeOfDay' => 'day',
            'weather' => 'clear',
            'atmosphere' => '',
            'mood' => '',                        // Location mood (e.g., "tense", "peaceful", "mysterious")
            'lightingStyle' => '',               // Specific lighting for this location
            'description' => $description,
            'scenes' => [],
            'stateChanges' => [],
            'referenceImage' => null,
            'referenceImageBase64' => null,      // Base64 data for API calls (location consistency)
            'referenceImageMimeType' => null,    // MIME type (e.g., 'image/png')
            'referenceImageStatus' => 'none',    // 'none' | 'generating' | 'ready' | 'error'
        ];
        // Auto-select the newly added location for editing
        $this->editingLocationIndex = count($this->sceneMemory['locationBible']['locations']) - 1;
        $this->saveProject();
    }

    /**
     * Remove location from Location Bible.
     */
    public function removeLocation(int $index): void
    {
        if (isset($this->sceneMemory['locationBible']['locations'][$index])) {
            unset($this->sceneMemory['locationBible']['locations'][$index]);
            $this->sceneMemory['locationBible']['locations'] = array_values($this->sceneMemory['locationBible']['locations']);

            // Reset editing index if needed
            $count = count($this->sceneMemory['locationBible']['locations']);
            if ($this->editingLocationIndex >= $count) {
                $this->editingLocationIndex = max(0, $count - 1);
            }

            $this->saveProject();
        }
    }

    // =========================================================================
    // LOCATION STATE CHANGES
    // =========================================================================

    /**
     * Add a state change to a location for a specific scene.
     */
    public function addLocationState(int $locationIndex, int $sceneIndex, string $state = ''): void
    {
        $state = trim($state);
        if (empty($state)) {
            return;
        }

        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex])) {
            return;
        }

        // Initialize stateChanges array if not exists
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'])) {
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = [];
        }

        // Check if state already exists for this scene - update it
        // Support both new (sceneIndex) and old (scene) field names when reading
        $found = false;
        foreach ($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] as $idx => $change) {
            $changeSceneIdx = $change['sceneIndex'] ?? $change['scene'] ?? -1;
            if ($changeSceneIdx === $sceneIndex) {
                // Update using new field names
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$idx] = [
                    'sceneIndex' => $sceneIndex,
                    'stateDescription' => $state,
                ];
                $found = true;
                break;
            }
        }

        // Add new state change if not found (using new field names)
        if (!$found) {
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][] = [
                'sceneIndex' => $sceneIndex,
                'stateDescription' => $state,
            ];

            // Sort by scene index (support both field names)
            usort(
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'],
                fn($a, $b) => ($a['sceneIndex'] ?? $a['scene'] ?? 0) <=> ($b['sceneIndex'] ?? $b['scene'] ?? 0)
            );
        }

        $this->saveProject();
    }

    /**
     * Remove a state change from a location.
     */
    public function removeLocationState(int $locationIndex, int $stateIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$stateIndex])) {
            return;
        }

        unset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$stateIndex]);
        $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = array_values(
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges']
        );
        $this->saveProject();
    }

    /**
     * Apply a preset state progression to a location.
     */
    public function applyLocationStatePreset(int $locationIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex])) {
            return;
        }

        $scenes = $this->sceneMemory['locationBible']['locations'][$locationIndex]['scenes'] ?? [];
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
                ['state' => 'morning light, fresh atmosphere'],
                ['state' => 'evening, golden hour lighting'],
            ],
            'weather-change' => [
                ['state' => 'clear skies, bright'],
                ['state' => 'stormy, dramatic clouds'],
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

        // Apply first state to first scene, second state to last scene (using new field names)
        $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = [
            ['sceneIndex' => $firstScene, 'stateDescription' => $presets[$preset][0]['state']],
            ['sceneIndex' => $lastScene, 'stateDescription' => $presets[$preset][1]['state']],
        ];

        $this->saveProject();
    }

    // =========================================================================
    // LOCATION REFERENCE IMAGE GENERATION
    // =========================================================================

    /**
     * Queue auto-generation of location references.
     * Directly generates references for all locations that need them.
     */
    public function queueAutoLocationReferences(): void
    {
        $locations = $this->sceneMemory['locationBible']['locations'] ?? [];

        if (empty($locations)) {
            Log::info('LocationAutoGen: No locations to generate references for');
            return;
        }

        $toGenerate = [];
        foreach ($locations as $index => $loc) {
            // Skip if already has reference (check both new storage key and legacy base64)
            $hasReference = !empty($loc['referenceImageStorageKey']) || !empty($loc['referenceImageBase64']);
            if ($hasReference && ($loc['referenceImageStatus'] ?? '') === 'ready') {
                continue;
            }

            // Skip if already generating or pending (prevent duplicate queuing)
            if (in_array($loc['referenceImageStatus'] ?? '', ['generating', 'pending'])) {
                continue;
            }

            // Mark as pending
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'pending';
            $toGenerate[] = ['index' => $index, 'name' => $loc['name'] ?? 'Unknown'];
        }

        if (empty($toGenerate)) {
            Log::info('LocationAutoGen: All locations already have references');
            return;
        }

        $this->saveProject();

        Log::info('LocationAutoGen: Marked ' . count($toGenerate) . ' locations as pending', [
            'locations' => array_column($toGenerate, 'name'),
        ]);

        // Dispatch event to start polling - generation will happen via polling to avoid HTTP timeout
        // This is fully async: no generation during the initial request
        Log::info('LocationAutoGen: Dispatching continue-location-reference-generation event', [
            'type' => 'location',
            'remaining' => count($toGenerate),
        ]);
        $this->dispatch('continue-location-reference-generation', [
            'type' => 'location',
            'remaining' => count($toGenerate),
        ]);

        // Also dispatch a browser-visible debug event
        $this->dispatch('vw-debug', [
            'source' => 'queueAutoLocationReferences',
            'toGenerate' => count($toGenerate),
            'message' => 'Location auto-generation queued for polling',
        ]);
    }

    /**
     * Generate the next pending location reference.
     * Called by polling to continue auto-generation without timeout.
     */
    public function generateNextPendingLocationReference(): ?array
    {
        $locations = $this->sceneMemory['locationBible']['locations'] ?? [];

        foreach ($locations as $index => $loc) {
            if (($loc['referenceImageStatus'] ?? '') === 'pending') {
                try {
                    Log::info('LocationAutoGen: Generating next pending reference', [
                        'index' => $index,
                        'name' => $loc['name'] ?? 'Unknown',
                    ]);
                    $this->generateLocationReference($index);
                    return [
                        'success' => true,
                        'name' => $loc['name'] ?? 'Unknown',
                        'remaining' => $this->countPendingLocationReferences(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('LocationAutoGen: Failed to generate pending reference', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    // Mark as failed so we don't loop forever
                    $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'failed';
                    $this->saveProject();
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'remaining' => $this->countPendingLocationReferences(),
                    ];
                }
            }
        }

        return ['success' => true, 'remaining' => 0, 'message' => 'No pending locations'];
    }

    /**
     * Count how many location references are still pending.
     */
    public function countPendingLocationReferences(): int
    {
        $count = 0;
        foreach ($this->sceneMemory['locationBible']['locations'] ?? [] as $loc) {
            if (($loc['referenceImageStatus'] ?? '') === 'pending') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Generate reference images for all locations that need them (one-click)
     * Uses async polling approach to avoid HTTP timeout
     */
    public function generateAllMissingLocationReferences(): void
    {
        $locationsNeeding = $this->getLocationsNeedingReferences();

        if (empty($locationsNeeding)) {
            $this->dispatch('notification', [
                'type' => 'info',
                'message' => 'All locations already have reference images.',
            ]);
            return;
        }

        Log::info('[VideoWizard] One-click location reference generation', [
            'totalLocations' => count($locationsNeeding),
            'locationNames' => array_column($locationsNeeding, 'name'),
        ]);

        $this->dispatch('generation-status', [
            'message' => 'Queuing ' . count($locationsNeeding) . ' location reference(s) for generation...',
        ]);

        // Use the async polling approach to avoid HTTP timeout
        $this->queueAutoLocationReferences();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Location reference generation started for ' . count($locationsNeeding) . ' location(s). They will be generated automatically.',
        ]);
    }

    /**
     * Get locations that need reference images (for one-click generation)
     *
     * @return array Locations missing reference images with indices
     */
    public function getLocationsNeedingReferences(): array
    {
        $locationBible = $this->sceneMemory['locationBible'] ?? [];

        if (!($locationBible['enabled'] ?? false)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getLocationsNeedingReferences($locationBible);
    }

    // =========================================================================
    // LOCATION SORTING
    // =========================================================================

    /**
     * Get sorted locations with metadata for display (Phase 5.2)
     *
     * @param string $sortMethod Sorting method: 'first_appearance', 'frequency', 'alphabetical'
     * @return array Sorted locations with metadata
     */
    public function getSortedLocations(string $sortMethod = 'first_appearance'): array
    {
        $locations = $this->sceneMemory['locationBible']['locations'] ?? [];
        $scenes = $this->script['scenes'] ?? [];

        if (empty($locations)) {
            return [];
        }

        $orderingService = app(BibleOrderingService::class);
        return $orderingService->getSortedLocationsWithMetadata($locations, $scenes, $sortMethod);
    }
}
