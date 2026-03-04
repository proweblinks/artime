<?php

namespace Modules\AppVideoWizard\Traits;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\ImageSourceService;
use Modules\AppVideoWizard\Services\StoryModeScriptService;

/**
 * Shared image selection properties and methods for URL-to-Video and Story Mode.
 *
 * Provides the full image sourcing, selection, search, upload, library browsing,
 * crop/trim/animation state used by the image selection modal.
 */
trait HasImageSelection
{
    // Image selection modal state
    public bool $showImageSelectionModal = false;
    public bool $isSourcingImages = false;
    public array $sceneImageCandidates = [];
    public array $sceneSearchSuggestions = [];
    public array $selectedSceneImages = [];
    public $uploadedSceneImage;
    public string $uploadTargetScene = '';

    // Search state
    public string $searchQuery = '';
    public string $searchType = 'all'; // 'all', 'images', 'videos'

    // Per-scene AI animation toggle (opt-in to Seedance)
    public array $sceneAnimateWithAI = [];

    // Crop/position data for 9:16 framing
    public array $sceneCropData = [];

    // Video edit data (trim + flip) per scene
    public array $sceneVideoEdits = [];

    // Library browser state
    public bool $showLibraryBrowser = false;
    public string $libraryBrowseScene = '';
    public array $libraryCategories = [];
    public string $libraryActiveCategory = '';
    public array $libraryCategoryResults = [];
    public int $libraryPage = 1;
    public bool $libraryHasMore = false;
    public string $librarySearchQuery = '';

    /**
     * Reset all image selection state. Useful when script changes invalidate cached data.
     */
    public function resetImageSelectionState(): void
    {
        $this->sceneImageCandidates = [];
        $this->sceneSearchSuggestions = [];
        $this->selectedSceneImages = [];
        $this->sceneAnimateWithAI = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
        $this->showImageSelectionModal = false;
        $this->showLibraryBrowser = false;
        $this->libraryPage = 1;
        $this->libraryHasMore = false;
        $this->librarySearchQuery = '';
    }

    /**
     * Source images for scenes: search stock/article photos, populate candidates,
     * auto-select first candidate per scene, and open the image selection modal.
     *
     * @param array $segments  Segmented transcript scenes
     * @param array $contentBrief  Content brief with subject info
     * @param array $extractedContent  Raw extracted content (article images, etc.)
     */
    public function sourceImagesForScenes(array $segments, array $contentBrief, array $extractedContent): void
    {
        $this->showTranscriptModal = false;
        $this->isSourcingImages = true;

        try {
            $scenes = [];
            foreach ($segments as $i => $segment) {
                $scenes[] = [
                    'id' => 'scene_' . $i,
                    'index' => $i,
                    'text' => $segment['text'],
                    'estimated_duration' => $segment['estimated_duration'],
                ];
            }

            // Sync generatedSegments with actual scene segmentation
            $this->generatedSegments = $segments;

            $imageService = new ImageSourceService();

            // Ensure content brief has a subject for stock search
            if (empty($contentBrief['subject'])) {
                $contentBrief['subject'] = $this->prompt ?: ($this->generatedTitle ?? '');
            }

            $result = $imageService->sourceForScenes($scenes, $extractedContent, $contentBrief);

            // Split structured result into candidates and suggestions
            $this->sceneImageCandidates = [];
            $this->sceneSearchSuggestions = [];
            foreach ($result as $sceneId => $data) {
                $this->sceneImageCandidates[$sceneId] = $data['candidates'] ?? [];
                $this->sceneSearchSuggestions[$sceneId] = $data['suggestions'] ?? [];
            }

            // Auto-select first candidate per scene and auto-trim long video clips
            $this->selectedSceneImages = [];
            foreach ($this->sceneImageCandidates as $sceneId => $sceneCandidates) {
                if (!empty($sceneCandidates)) {
                    $this->selectedSceneImages[$sceneId] = [0];
                    $firstCandidate = $sceneCandidates[0];
                    if (($firstCandidate['type'] ?? 'image') === 'video') {
                        $this->sceneAnimateWithAI[$sceneId] = $this->sceneAnimateWithAI[$sceneId] ?? false;
                        if (!isset($this->sceneVideoEdits[$sceneId])) {
                            $this->autoTrimVideoClip($sceneId, $firstCandidate);
                        }
                    }
                } else {
                    $this->selectedSceneImages[$sceneId] = 'ai';
                }
            }

            $this->showImageSelectionModal = true;
        } catch (\Exception $e) {
            Log::error('HasImageSelection: Image sourcing failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to source images: ' . $e->getMessage());
        } finally {
            $this->isSourcingImages = false;
        }
    }

    /**
     * Go back from image selection modal to transcript modal.
     */
    public function backToTranscript()
    {
        $this->showImageSelectionModal = false;
        $this->showTranscriptModal = true;
    }

    /**
     * Directly open image selection modal (when candidates already exist).
     */
    public function openImageSelection()
    {
        if (!empty($this->sceneImageCandidates)) {
            $this->showTranscriptModal = false;
            $this->showImageSelectionModal = true;
        }
    }

    /**
     * Toggle a clip selection for a scene (multi-clip: add/remove from array).
     */
    public function selectSceneImage(string $sceneId, int $candidateIndex)
    {
        if (!isset($this->selectedSceneImages[$sceneId]) || !is_array($this->selectedSceneImages[$sceneId])) {
            $this->selectedSceneImages[$sceneId] = [];
        }

        $clips = $this->selectedSceneImages[$sceneId];
        $pos = array_search($candidateIndex, $clips);
        if ($pos !== false) {
            array_splice($clips, $pos, 1);
        } else {
            $clips[] = $candidateIndex;
        }

        $this->selectedSceneImages[$sceneId] = array_values($clips);

        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
        $candidate = $candidates[$candidateIndex] ?? null;
        if ($candidate && ($candidate['type'] ?? 'image') === 'video') {
            $this->sceneAnimateWithAI[$sceneId] = false;
        }
    }

    /**
     * Remove a clip from a scene's selection by position.
     */
    public function removeSceneClip(string $sceneId, int $clipPosition)
    {
        if (isset($this->selectedSceneImages[$sceneId]) && is_array($this->selectedSceneImages[$sceneId])) {
            array_splice($this->selectedSceneImages[$sceneId], $clipPosition, 1);
            $this->selectedSceneImages[$sceneId] = array_values($this->selectedSceneImages[$sceneId]);
        }
    }

    /**
     * Reorder a clip within a scene's selection.
     */
    public function reorderSceneClip(string $sceneId, int $fromPos, int $toPos)
    {
        $clips = $this->selectedSceneImages[$sceneId] ?? [];
        if (!is_array($clips) || !isset($clips[$fromPos]) || $toPos < 0 || $toPos >= count($clips)) return;
        $item = array_splice($clips, $fromPos, 1)[0];
        array_splice($clips, $toPos, 0, [$item]);
        $this->selectedSceneImages[$sceneId] = $clips;
    }

    /**
     * Toggle AI-generated image for a scene.
     */
    public function markSceneForAI(string $sceneId)
    {
        $current = $this->selectedSceneImages[$sceneId] ?? [];
        if ($current === 'ai') {
            $this->selectedSceneImages[$sceneId] = [];
            $this->sceneAnimateWithAI[$sceneId] = false;
        } else {
            $this->selectedSceneImages[$sceneId] = 'ai';
            $this->sceneAnimateWithAI[$sceneId] = true;
        }
    }

    /**
     * Toggle per-scene AI animation (Seedance) on/off.
     */
    public function toggleSceneAnimation(string $sceneId)
    {
        $this->sceneAnimateWithAI[$sceneId] = !($this->sceneAnimateWithAI[$sceneId] ?? false);
    }

    /**
     * Update crop/position focal point for a scene image.
     */
    public function updateSceneCrop(string $sceneId, float $focalX, float $focalY)
    {
        $this->sceneCropData[$sceneId] = [
            'focalX' => max(0, min(1, $focalX)),
            'focalY' => max(0, min(1, $focalY)),
        ];
    }

    /**
     * Update video edit parameters (trim start/end, flip H/V) for a scene.
     */
    public function updateSceneVideoEdit(string $sceneId, float $trimStart, float $trimEnd, bool $flipH, bool $flipV)
    {
        $this->sceneVideoEdits[$sceneId] = [
            'trimStart' => max(0, $trimStart),
            'trimEnd' => max(0, $trimEnd),
            'flipH' => $flipH,
            'flipV' => $flipV,
        ];
    }

    /**
     * Auto-trim a video clip to fit the scene duration.
     */
    protected function autoTrimVideoClip(string $sceneId, array $candidate): void
    {
        $clipDuration = (float) ($candidate['duration'] ?? 0);
        $sceneDuration = $this->getSceneDuration($sceneId);

        if ($clipDuration > 0 && $sceneDuration > 0 && $clipDuration > $sceneDuration) {
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            $this->sceneVideoEdits[$sceneId] = [
                'trimStart' => 0,
                'trimEnd' => round($sceneDuration, 1),
                'flipH' => $existing['flipH'] ?? false,
                'flipV' => $existing['flipV'] ?? false,
            ];
        } else {
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            if ($existing) {
                $this->sceneVideoEdits[$sceneId] = [
                    'trimStart' => 0,
                    'trimEnd' => $clipDuration > 0 ? $clipDuration : $sceneDuration,
                    'flipH' => $existing['flipH'] ?? false,
                    'flipV' => $existing['flipV'] ?? false,
                ];
            } else {
                unset($this->sceneVideoEdits[$sceneId]);
            }
        }
    }

    /**
     * Get the estimated duration for a scene from generated segments.
     */
    protected function getSceneDuration(string $sceneId): float
    {
        $sceneIndex = (int) str_replace('scene_', '', $sceneId);
        return (float) ($this->generatedSegments[$sceneIndex]['estimated_duration'] ?? 6.0);
    }

    /**
     * Open the full library browser for a scene.
     */
    public function openLibraryBrowser(string $sceneId)
    {
        $this->libraryBrowseScene = $sceneId;
        $this->libraryActiveCategory = '';
        $this->libraryCategoryResults = [];
        $this->libraryPage = 1;
        $this->libraryHasMore = false;
        $this->librarySearchQuery = '';

        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $this->libraryCategories = $stockService->getCategories();
        $this->showLibraryBrowser = true;
    }

    /**
     * Load clips from a specific category in the library browser.
     */
    public function loadLibraryCategory(string $category)
    {
        $this->libraryActiveCategory = $category;
        $this->librarySearchQuery = '';
        $this->libraryPage = 1;
        $perPage = 24;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $results = $stockService->browseCategory($category, $perPage + 1);
        $this->libraryHasMore = count($results) > $perPage;
        $this->libraryCategoryResults = array_slice($results, 0, $perPage);
    }

    /**
     * Search the library browser by text query.
     */
    public function searchLibrary(string $query)
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) return;

        $this->libraryActiveCategory = '';
        $this->librarySearchQuery = $query;
        $this->libraryPage = 1;
        $perPage = 24;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $results = $stockService->search($query, $perPage + 1);
        $this->libraryHasMore = count($results) > $perPage;
        $this->libraryCategoryResults = array_slice($results, 0, $perPage);
    }

    /**
     * Load more items in the library browser (next page).
     */
    public function loadMoreLibrary()
    {
        if (!$this->libraryHasMore) return;

        $this->libraryPage++;
        $perPage = 24;
        $offset = ($this->libraryPage - 1) * $perPage;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();

        if (!empty($this->librarySearchQuery)) {
            $results = $stockService->search($this->librarySearchQuery, $perPage + 1, $offset);
        } elseif (!empty($this->libraryActiveCategory)) {
            $results = $stockService->browseCategory($this->libraryActiveCategory, $perPage + 1, $offset);
        } else {
            return;
        }

        $this->libraryHasMore = count($results) > $perPage;
        $newItems = array_slice($results, 0, $perPage);
        $this->libraryCategoryResults = array_merge($this->libraryCategoryResults, $newItems);
    }

    /**
     * Select a clip from the library browser and add it to the scene.
     */
    public function selectFromLibrary(int $index)
    {
        $sceneId = $this->libraryBrowseScene;
        if (empty($sceneId) || !isset($this->libraryCategoryResults[$index])) return;

        $candidate = $this->libraryCategoryResults[$index];
        $this->sceneImageCandidates[$sceneId][] = $candidate;
        $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
        $this->selectedSceneImages[$sceneId] = [$newIndex];

        if (($candidate['type'] ?? 'image') === 'video') {
            $this->sceneAnimateWithAI[$sceneId] = false;
            $this->autoTrimVideoClip($sceneId, $candidate);
        }

        $this->showLibraryBrowser = false;
    }

    /**
     * Execute a scene search with media type filtering.
     */
    public function executeSceneSearch(string $sceneId, string $query = '', string $type = '')
    {
        $query = trim($query ?: $this->searchQuery);
        $type = $type ?: $this->searchType;

        if (mb_strlen($query) < 2) return;

        Log::info('HasImageSelection: executeSceneSearch', [
            'scene' => $sceneId, 'query' => $query, 'type' => $type,
        ]);

        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $added = 0;

        try {
            $stockType = ($type === 'videos') ? 'video' : (($type === 'images') ? 'image' : null);
            $stockResults = $stockService->search($query, 12, $stockType);

            $uploads = array_filter($this->sceneImageCandidates[$sceneId] ?? [], function ($c) {
                return ($c['source'] ?? '') === 'upload';
            });
            $this->sceneImageCandidates[$sceneId] = array_values($uploads);

            foreach ($stockResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: Scene search failed', [
                'scene' => $sceneId, 'query' => $query, 'error' => $e->getMessage(),
            ]);
            session()->flash('searchError', 'Search failed: ' . $e->getMessage());
        }

        if ($added > 0) {
            session()->flash('searchSuccess', "Found {$added} results for \"{$query}\"");
        } else {
            session()->flash('searchError', "No results found for \"{$query}\"");
        }

        $this->searchQuery = '';
    }

    /**
     * Load more stock candidates for a scene.
     */
    public function loadMoreCandidates(string $sceneId)
    {
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $existingIds = array_filter(array_column($this->sceneImageCandidates[$sceneId] ?? [], 'stock_id'));

        $subject = (property_exists($this, 'storedContentBrief') ? ($this->storedContentBrief['subject'] ?? null) : null)
            ?? $this->prompt ?? '';
        $matchedCategory = !empty($subject) ? $stockService->findMatchingCategory($subject) : null;
        $added = 0;

        try {
            if ($matchedCategory) {
                $results = $stockService->browseCategoryExcluding($matchedCategory, 8, $existingIds);
            } else {
                $results = $stockService->searchExcluding($subject, 8, $existingIds);
            }

            foreach ($results as $item) {
                $this->sceneImageCandidates[$sceneId][] = $item;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: loadMoreCandidates failed', [
                'scene' => $sceneId, 'error' => $e->getMessage(),
            ]);
        }

        if ($added === 0) {
            session()->flash('searchError', 'No more clips available');
        }
    }

    /**
     * Search external stock sources (Pexels, Pixabay, Wikimedia) for a scene.
     */
    public function searchExternalStock(string $sceneId, string $query = '')
    {
        $query = trim($query ?: $this->searchQuery);
        if (mb_strlen($query) < 2) return;

        Log::info('HasImageSelection: searchExternalStock', [
            'scene' => $sceneId, 'query' => $query,
        ]);

        $imageService = new ImageSourceService();
        $added = 0;

        try {
            $wikiResults = $imageService->searchWikimedia($query, 5);
            foreach ($wikiResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = array_merge($r, [
                    'source' => $r['source'] ?? 'wikimedia',
                ]);
                $added++;
            }

            $photoResults = $imageService->searchStockPhotos($query, 5);
            foreach ($photoResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }

            $videoResults = $imageService->searchVideoClips($query, 5);
            foreach ($videoResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: External stock search failed', [
                'scene' => $sceneId, 'query' => $query, 'error' => $e->getMessage(),
            ]);
        }

        if ($added > 0) {
            session()->flash('searchSuccess', "Found {$added} external results for \"{$query}\"");
        } else {
            session()->flash('searchError', "No external results found for \"{$query}\"");
        }

        $this->searchQuery = '';
    }

    /**
     * Backward-compatible alias for suggestion chips.
     */
    public function searchMoreImages(string $sceneId, string $query)
    {
        $this->executeSceneSearch($sceneId, $query, 'all');
    }

    /**
     * Handle uploaded scene image.
     */
    public function updatedUploadedSceneImage()
    {
        $sceneId = $this->uploadTargetScene;
        if (empty($sceneId) || !$this->uploadedSceneImage) return;

        try {
            $path = $this->uploadedSceneImage->store('url-to-video/uploads', 'public');
            $publicUrl = url('/public/storage/' . $path);

            $newCandidate = [
                'url' => $publicUrl,
                'thumbnail' => $publicUrl,
                'source' => 'upload',
                'title' => $this->uploadedSceneImage->getClientOriginalName(),
                'width' => 0,
                'height' => 0,
            ];

            $this->sceneImageCandidates[$sceneId][] = $newCandidate;
            $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
            $this->selectedSceneImages[$sceneId] = $newIndex;

            $this->uploadedSceneImage = null;
            $this->uploadTargetScene = '';
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: Image upload failed', ['error' => $e->getMessage()]);
        }
    }
}
