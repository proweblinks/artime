<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * Scene Sync Service - Centralized scene assignment and Bible synchronization
 *
 * PHASE 3 OPTIMIZATION: This service ensures:
 * - One-location-per-scene rule is atomic and consistent
 * - Character Bible and Location Bible stay synchronized
 * - Scene DNA is rebuilt at appropriate times
 * - All Bible systems maintain data integrity
 *
 * Key responsibilities:
 * 1. Atomic scene ownership operations (no race conditions)
 * 2. Cross-Bible validation (location + character assignments are consistent)
 * 3. Scene DNA rebuilding coordination
 * 4. Integrity validation on modal close
 */
class SceneSyncService
{
    /**
     * Assign a scene to a location, ensuring one-location-per-scene rule.
     * This is the ONLY method that should modify location scene assignments.
     *
     * @param array &$locationBible Reference to the location Bible data
     * @param int $locationIndex The location to assign the scene to
     * @param int $sceneIndex The scene to assign
     * @return array Updated ownership map
     */
    public function assignSceneToLocation(array &$locationBible, int $locationIndex, int $sceneIndex): array
    {
        $locations = &$locationBible['locations'];

        if (!isset($locations[$locationIndex])) {
            Log::warning('SceneSyncService: Invalid location index', [
                'locationIndex' => $locationIndex,
                'totalLocations' => count($locations),
            ]);
            return $this->buildOwnershipMap($locations);
        }

        // ATOMIC OPERATION: Remove scene from ALL locations first
        foreach ($locations as $idx => &$location) {
            if (isset($location['scenes']) && is_array($location['scenes'])) {
                $location['scenes'] = array_values(array_filter(
                    $location['scenes'],
                    fn($s) => $s !== $sceneIndex
                ));
            }
        }
        unset($location);

        // Now assign to the target location
        if (!isset($locations[$locationIndex]['scenes'])) {
            $locations[$locationIndex]['scenes'] = [];
        }
        $locations[$locationIndex]['scenes'][] = $sceneIndex;
        sort($locations[$locationIndex]['scenes']);

        Log::debug('SceneSyncService: Scene assigned to location', [
            'sceneIndex' => $sceneIndex,
            'locationIndex' => $locationIndex,
            'locationName' => $locations[$locationIndex]['name'] ?? 'Unknown',
        ]);

        return $this->buildOwnershipMap($locations);
    }

    /**
     * Remove a scene from a location.
     *
     * @param array &$locationBible Reference to the location Bible data
     * @param int $locationIndex The location to remove the scene from
     * @param int $sceneIndex The scene to remove
     * @return array Updated ownership map
     */
    public function removeSceneFromLocation(array &$locationBible, int $locationIndex, int $sceneIndex): array
    {
        $locations = &$locationBible['locations'];

        if (!isset($locations[$locationIndex])) {
            return $this->buildOwnershipMap($locations);
        }

        if (isset($locations[$locationIndex]['scenes'])) {
            $locations[$locationIndex]['scenes'] = array_values(array_filter(
                $locations[$locationIndex]['scenes'],
                fn($s) => $s !== $sceneIndex
            ));
        }

        Log::debug('SceneSyncService: Scene removed from location', [
            'sceneIndex' => $sceneIndex,
            'locationIndex' => $locationIndex,
        ]);

        return $this->buildOwnershipMap($locations);
    }

    /**
     * Build ownership map from current location assignments.
     * Returns sceneIndex => locationIndex mapping.
     *
     * @param array $locations The locations array
     * @return array Ownership map
     */
    public function buildOwnershipMap(array $locations): array
    {
        $ownership = [];

        foreach ($locations as $locIdx => $location) {
            $scenes = $location['scenes'] ?? [];
            foreach ($scenes as $sceneIndex) {
                // If scene is already owned, this indicates a conflict
                if (isset($ownership[$sceneIndex])) {
                    Log::warning('SceneSyncService: Duplicate scene ownership detected', [
                        'sceneIndex' => $sceneIndex,
                        'existingOwner' => $ownership[$sceneIndex],
                        'conflictingOwner' => $locIdx,
                    ]);
                }
                $ownership[$sceneIndex] = $locIdx;
            }
        }

        return $ownership;
    }

    /**
     * Validate location Bible integrity - ensures no scene conflicts.
     *
     * @param array $locationBible The location Bible data
     * @return array Validation result with 'valid' boolean and 'issues' array
     */
    public function validateLocationBible(array $locationBible): array
    {
        $issues = [];
        $locations = $locationBible['locations'] ?? [];
        $sceneSeen = [];

        foreach ($locations as $locIdx => $location) {
            $locationName = $location['name'] ?? "Location {$locIdx}";
            $scenes = $location['scenes'] ?? [];

            foreach ($scenes as $sceneIndex) {
                if (isset($sceneSeen[$sceneIndex])) {
                    $issues[] = [
                        'type' => 'duplicate_scene',
                        'sceneIndex' => $sceneIndex,
                        'locations' => [$sceneSeen[$sceneIndex], $locationName],
                        'message' => "Scene " . ($sceneIndex + 1) . " is assigned to multiple locations: {$sceneSeen[$sceneIndex]} and {$locationName}",
                    ];
                } else {
                    $sceneSeen[$sceneIndex] = $locationName;
                }
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'scenesCovered' => count($sceneSeen),
            'locationsWithScenes' => count(array_filter($locations, fn($l) => !empty($l['scenes']))),
        ];
    }

    /**
     * Validate character Bible integrity.
     *
     * @param array $characterBible The character Bible data
     * @param int $totalScenes Total number of scenes
     * @return array Validation result
     */
    public function validateCharacterBible(array $characterBible, int $totalScenes): array
    {
        $issues = [];
        $characters = $characterBible['characters'] ?? [];

        foreach ($characters as $charIdx => $character) {
            $charName = $character['name'] ?? "Character {$charIdx}";
            $appliedScenes = $character['appliedScenes'] ?? [];

            // Check for out-of-range scene indices
            foreach ($appliedScenes as $sceneIndex) {
                if ($sceneIndex < 0 || $sceneIndex >= $totalScenes) {
                    $issues[] = [
                        'type' => 'invalid_scene_index',
                        'characterName' => $charName,
                        'sceneIndex' => $sceneIndex,
                        'message' => "Character '{$charName}' has invalid scene index: {$sceneIndex}",
                    ];
                }
            }

            // Check for main characters without scene assignments
            $role = strtolower($character['role'] ?? 'supporting');
            if (in_array($role, ['protagonist', 'main', 'lead']) && empty($appliedScenes)) {
                $issues[] = [
                    'type' => 'main_character_no_scenes',
                    'characterName' => $charName,
                    'message' => "Main character '{$charName}' has no scene assignments",
                ];
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'totalCharacters' => count($characters),
        ];
    }

    /**
     * Validate all Bible systems together.
     *
     * @param array $sceneMemory The complete scene memory object
     * @param int $totalScenes Total number of scenes
     * @return array Comprehensive validation result
     */
    public function validateAllBibles(array $sceneMemory, int $totalScenes): array
    {
        $locationResult = $this->validateLocationBible($sceneMemory['locationBible'] ?? []);
        $characterResult = $this->validateCharacterBible($sceneMemory['characterBible'] ?? [], $totalScenes);

        $allIssues = array_merge(
            $locationResult['issues'],
            $characterResult['issues']
        );

        // Cross-Bible validation: check scene coverage
        $crossBibleIssues = $this->validateCrossBibleConsistency($sceneMemory, $totalScenes);
        $allIssues = array_merge($allIssues, $crossBibleIssues);

        return [
            'valid' => empty($allIssues),
            'issues' => $allIssues,
            'location' => $locationResult,
            'character' => $characterResult,
            'totalScenes' => $totalScenes,
        ];
    }

    /**
     * Validate cross-Bible consistency.
     *
     * @param array $sceneMemory The complete scene memory object
     * @param int $totalScenes Total number of scenes
     * @return array Issues found
     */
    protected function validateCrossBibleConsistency(array $sceneMemory, int $totalScenes): array
    {
        $issues = [];
        $locationBible = $sceneMemory['locationBible'] ?? [];
        $characterBible = $sceneMemory['characterBible'] ?? [];

        // Check for scenes without any location
        $scenesWithLocation = [];
        foreach ($locationBible['locations'] ?? [] as $location) {
            foreach ($location['scenes'] ?? [] as $sceneIndex) {
                $scenesWithLocation[$sceneIndex] = true;
            }
        }

        for ($i = 0; $i < $totalScenes; $i++) {
            if (!isset($scenesWithLocation[$i])) {
                $issues[] = [
                    'type' => 'scene_no_location',
                    'sceneIndex' => $i,
                    'message' => "Scene " . ($i + 1) . " has no location assigned",
                ];
            }
        }

        // Check for scenes without any characters (warning only)
        $scenesWithCharacters = [];
        foreach ($characterBible['characters'] ?? [] as $character) {
            foreach ($character['appliedScenes'] ?? [] as $sceneIndex) {
                $scenesWithCharacters[$sceneIndex] = true;
            }
        }

        for ($i = 0; $i < $totalScenes; $i++) {
            if (!isset($scenesWithCharacters[$i]) && ($characterBible['enabled'] ?? false)) {
                // This is a warning, not an error - some scenes may intentionally have no characters
                Log::debug('SceneSyncService: Scene has no characters', ['sceneIndex' => $i]);
            }
        }

        return $issues;
    }

    /**
     * Fix location conflicts by keeping only the first assignment.
     *
     * @param array &$locationBible Reference to the location Bible data
     * @return int Number of conflicts fixed
     */
    public function fixLocationConflicts(array &$locationBible): int
    {
        $locations = &$locationBible['locations'];
        $ownership = [];
        $conflictsFixed = 0;

        foreach ($locations as $locIdx => &$location) {
            $scenes = $location['scenes'] ?? [];
            $filteredScenes = [];

            foreach ($scenes as $sceneIndex) {
                if (!isset($ownership[$sceneIndex])) {
                    $ownership[$sceneIndex] = $locIdx;
                    $filteredScenes[] = $sceneIndex;
                } else {
                    $conflictsFixed++;
                    Log::info('SceneSyncService: Fixed duplicate scene assignment', [
                        'sceneIndex' => $sceneIndex,
                        'keptInLocation' => $ownership[$sceneIndex],
                        'removedFromLocation' => $locIdx,
                    ]);
                }
            }

            $location['scenes'] = $filteredScenes;
            sort($location['scenes']);
        }
        unset($location);

        return $conflictsFixed;
    }

    /**
     * Ensure all scenes have a location assigned.
     * Uses intelligent distribution based on scene context.
     *
     * @param array &$locationBible Reference to the location Bible data
     * @param int $totalScenes Total number of scenes
     * @return int Number of scenes that were assigned
     */
    public function ensureAllScenesHaveLocation(array &$locationBible, int $totalScenes): int
    {
        $locations = &$locationBible['locations'];
        if (empty($locations)) {
            return 0;
        }

        $ownership = $this->buildOwnershipMap($locations);
        $assignedCount = 0;

        for ($sceneIndex = 0; $sceneIndex < $totalScenes; $sceneIndex++) {
            if (!isset($ownership[$sceneIndex])) {
                // Try to assign to adjacent location first
                $targetLocation = $this->findBestLocationForScene($locations, $ownership, $sceneIndex, $totalScenes);

                $locations[$targetLocation]['scenes'][] = $sceneIndex;
                sort($locations[$targetLocation]['scenes']);
                $ownership[$sceneIndex] = $targetLocation;
                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Find the best location for an unassigned scene.
     *
     * @param array $locations The locations array
     * @param array $ownership Current ownership map
     * @param int $sceneIndex The scene to assign
     * @param int $totalScenes Total scenes
     * @return int The best location index
     */
    protected function findBestLocationForScene(array $locations, array $ownership, int $sceneIndex, int $totalScenes): int
    {
        // Strategy 1: Assign to same location as previous scene (narrative continuity)
        if ($sceneIndex > 0 && isset($ownership[$sceneIndex - 1])) {
            return $ownership[$sceneIndex - 1];
        }

        // Strategy 2: Assign to same location as next scene
        if ($sceneIndex < $totalScenes - 1 && isset($ownership[$sceneIndex + 1])) {
            return $ownership[$sceneIndex + 1];
        }

        // Strategy 3: Assign to location with fewest scenes (balanced distribution)
        $minScenes = PHP_INT_MAX;
        $targetIdx = 0;

        foreach ($locations as $idx => $location) {
            $sceneCount = count($location['scenes'] ?? []);
            if ($sceneCount < $minScenes) {
                $minScenes = $sceneCount;
                $targetIdx = $idx;
            }
        }

        return $targetIdx;
    }
}
