<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Services\UrlContentExtractorService;
use Modules\AppVideoWizard\Services\StoryModeScriptService;
use Modules\AppVideoWizard\Services\ImageSourceService;
use Modules\AppVideoWizard\Jobs\UrlToVideoGenerationJob;

class UrlToVideo extends Component
{
    use WithFileUploads;

    // Input state
    public string $prompt = '';
    public string $sourceUrl = '';
    public string $detectedSourceType = '';
    public array $extractedPreview = [];
    public string $aspectRatio = '9:16';
    public string $selectedVoice = 'auto';
    public string $voiceProvider = '';
    public string $videoResolution = '480p';
    public string $videoQuality = 'pro';
    public int $videoDuration = 60;
    public string $narrativeStyle = 'hook_reveal';

    // Creative Mode state
    public bool $creativeMode = false;
    public ?string $creativeConceptTitle = null;
    public ?string $creativeConceptPitch = null;
    public array $alternativeConcepts = [];
    public bool $isGeneratingConcepts = false;
    public bool $showConceptCards = false;

    // Extraction state
    public bool $isExtracting = false;

    // Transcript editing
    public ?string $editableTranscript = null;
    public int $transcriptWordCount = 0;
    public ?string $generatedTitle = null;
    public array $generatedSegments = [];

    // Stored from extraction for confirmTranscript
    public array $storedExtractedContent = [];
    public array $storedContentBrief = [];

    // UI state
    public bool $showTranscriptModal = false;
    public bool $showVoiceModal = false;
    public bool $isGeneratingScript = false;
    public bool $isGenerating = false;

    // Real Images mode (default: true — show image selection modal, user can opt-in to AI Images)
    public bool $useRealImages = true;
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

    // Active project tracking
    public ?int $activeProjectId = null;
    public ?int $detailProjectId = null;
    public ?int $sharedProjectId = null;

    // Recreate: stores original project's scenes for restoring clip selections
    public ?int $recreateFromProjectId = null;

    protected $listeners = [
        'projectCompleted' => '$refresh',
    ];

    public function mount($sharedProject = null)
    {
        $this->aspectRatio = get_option('story_mode_default_aspect', '9:16');

        if ($sharedProject) {
            $this->sharedProjectId = $sharedProject->id;
            $this->detailProjectId = $sharedProject->id;
        }

        $activeProject = UrlToVideoProject::forUser(auth()->id())
            ->inProgress()
            ->latest()
            ->first();

        if ($activeProject) {
            $this->activeProjectId = $activeProject->id;
        }
    }

    /**
     * Auto-detect URL in prompt text and show preview.
     */
    public function updatedPrompt()
    {
        // Extract first URL from prompt text
        if (preg_match('/(https?:\/\/[^\s<>"\']+)/i', $this->prompt, $matches)) {
            $url = rtrim($matches[1], '.,;:!?)');
            if ($url !== $this->sourceUrl) {
                $this->sourceUrl = $url;
                $this->editableTranscript = null;
                $this->generatedTitle = null;
                $this->generatedSegments = [];
                $this->detectAndPreview();
            }
        } else {
            $this->sourceUrl = '';
            $this->detectedSourceType = '';
            $this->extractedPreview = [];
        }
    }

    /**
     * Detect source type and extract lightweight preview (title + thumbnail).
     */
    public function detectAndPreview()
    {
        if (empty($this->sourceUrl)) {
            return;
        }

        $this->isExtracting = true;

        try {
            $extractor = new UrlContentExtractorService();
            $this->detectedSourceType = $extractor->detectSourceType($this->sourceUrl);

            $extracted = $extractor->extract($this->sourceUrl, $this->detectedSourceType);
            $this->extractedPreview = [
                'title' => $extracted['title'] ?? '',
                'thumbnail' => $extracted['images'][0]['url'] ?? null,
                'source_type' => $this->detectedSourceType,
            ];
        } catch (\Exception $e) {
            Log::warning('UrlToVideo: Preview extraction failed', ['error' => $e->getMessage()]);
            $this->extractedPreview = [
                'title' => 'Could not preview this URL',
                'thumbnail' => null,
                'source_type' => $this->detectedSourceType ?: 'article',
            ];
        } finally {
            $this->isExtracting = false;
        }
    }

    /**
     * Submit — extract, analyze, generate script, show transcript modal.
     * If a transcript was already generated for the same URL, re-show it.
     */
    public function submitPrompt()
    {
        if (empty($this->sourceUrl) && mb_strlen($this->prompt) < 10) {
            session()->flash('error', 'Please paste a URL or enter a prompt (at least 10 characters).');
            return;
        }

        // If we already have a transcript from a previous generation, just re-show it
        if (!empty($this->editableTranscript) && $this->showTranscriptModal === false) {
            $this->showTranscriptModal = true;
            return;
        }

        $this->isGeneratingScript = true;

        try {
            $extractor = new UrlContentExtractorService();

            if (!empty($this->sourceUrl)) {
                // URL mode: extract → analyze → build prompt → generate script
                $sourceType = $this->detectedSourceType ?: $extractor->detectSourceType($this->sourceUrl);

                $extractedContent = $extractor->extract($this->sourceUrl, $sourceType);
                $this->storedExtractedContent = $extractedContent;

                // User prompt is whatever text is NOT the URL
                $userPrompt = trim(preg_replace('/(https?:\/\/[^\s<>"\']+)/i', '', $this->prompt));
                $userPrompt = $userPrompt ?: null;

                $contentBrief = $extractor->analyzeContent($extractedContent, $userPrompt);
                $this->storedContentBrief = $contentBrief;

                if ($this->creativeMode) {
                    $enhancedPrompt = $extractor->buildCreativeRoulettePrompt($contentBrief, $userPrompt, $this->videoDuration);
                } else {
                    $enhancedPrompt = $extractor->buildEnhancedPrompt($contentBrief, $userPrompt, $this->narrativeStyle);
                }
            } else {
                // Prompt-only mode (no URL)
                if ($this->creativeMode) {
                    $enhancedPrompt = $extractor->buildCreativeRoulettePromptFromText($this->prompt, $this->videoDuration);
                } else {
                    $enhancedPrompt = $this->prompt;
                    $styleInstruction = $extractor->getNarrativeStyleInstruction($this->narrativeStyle);
                    if ($styleInstruction) {
                        $enhancedPrompt .= "\n\n" . $styleInstruction;
                    }
                }
                $this->storedExtractedContent = [];
                $this->storedContentBrief = [];
            }

            // Generate script using Story Mode's script service
            $scriptService = new StoryModeScriptService();
            $targetDuration = $this->videoDuration;
            $maxWords = $this->calculateMaxWords($this->videoDuration);

            $result = $scriptService->generateScript($enhancedPrompt, $targetDuration, $maxWords, $this->creativeMode);

            $this->editableTranscript = $result['transcript'];
            $this->transcriptWordCount = $result['word_count'];
            $this->generatedTitle = $this->storedContentBrief['suggested_title']
                ?? $this->storedExtractedContent['title']
                ?? $result['title']
                ?? 'Untitled Video';
            $this->generatedSegments = $result['segments'] ?? [];

            // Extract creative concept fields
            if ($this->creativeMode) {
                $this->creativeConceptTitle = $result['concept_title'] ?? null;
                $this->creativeConceptPitch = $result['concept_pitch'] ?? null;
                $this->alternativeConcepts = [];
                $this->showConceptCards = false;
            }

            $this->showTranscriptModal = true;
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Script generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate script: ' . $e->getMessage());
        } finally {
            $this->isGeneratingScript = false;
        }
    }

    /**
     * Confirm transcript and dispatch generation pipeline.
     * If Real Images mode is on, fork to image sourcing flow instead.
     */
    public function confirmTranscript()
    {
        if (empty($this->editableTranscript)) {
            return;
        }

        // If Real Images mode is enabled, source images first (or reuse if already sourced)
        if ($this->useRealImages) {
            // If we already have candidates, verify scene count still matches the transcript
            if (!empty($this->sceneImageCandidates)) {
                $scriptService = new StoryModeScriptService();
                $targetDuration = $this->videoDuration;
                $currentSegments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
                $currentCount = count($currentSegments);
                $cachedCount = count($this->sceneImageCandidates);

                if ($currentCount === $cachedCount) {
                    // Scene count matches — safe to reuse cached candidates
                    $this->generatedSegments = $currentSegments;
                    $this->showTranscriptModal = false;
                    $this->showImageSelectionModal = true;
                    return;
                }

                // Scene count changed — invalidate stale candidates
                Log::info('UrlToVideo: Transcript changed scene count, re-sourcing images', [
                    'cached' => $cachedCount, 'current' => $currentCount,
                ]);
                $this->sceneImageCandidates = [];
                $this->sceneSearchSuggestions = [];
                $this->selectedSceneImages = [];
            }

            $this->showTranscriptModal = false;
            $this->isSourcingImages = true;

            try {
                $scriptService = new StoryModeScriptService();
                $targetDuration = $this->videoDuration;
                $segments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);

                $scenes = [];
                foreach ($segments as $i => $segment) {
                    $scenes[] = [
                        'id' => 'scene_' . $i,
                        'index' => $i,
                        'text' => $segment['text'],
                        'estimated_duration' => $segment['estimated_duration'],
                    ];
                }

                // Sync generatedSegments with actual scene segmentation so modal shows text for all scenes
                $this->generatedSegments = $segments;

                $imageService = new ImageSourceService();
                // Ensure content brief has a subject for stock search
                // Prefer the raw user prompt over AI-generated title — "funny cats"
                // is far more search-relevant than "The Secret Life of 'Funny' Cats"
                $contentBrief = $this->storedContentBrief;
                if (empty($contentBrief['subject'])) {
                    $contentBrief['subject'] = $this->prompt ?: $this->generatedTitle;
                }
                $result = $imageService->sourceForScenes(
                    $scenes,
                    $this->storedExtractedContent,
                    $contentBrief
                );

                // Split structured result into candidates and suggestions
                $this->sceneImageCandidates = [];
                $this->sceneSearchSuggestions = [];
                foreach ($result as $sceneId => $data) {
                    $this->sceneImageCandidates[$sceneId] = $data['candidates'] ?? [];
                    $this->sceneSearchSuggestions[$sceneId] = $data['suggestions'] ?? [];
                }

                // If recreating from an existing project, prepend original clips
                if ($this->recreateFromProjectId) {
                    $origProject = UrlToVideoProject::find($this->recreateFromProjectId);
                    if ($origProject) {
                        $originalScenes = $origProject->scenes ?? [];
                        foreach ($originalScenes as $origScene) {
                            $sceneId = $origScene['id'] ?? null;
                            if (!$sceneId || !isset($this->sceneImageCandidates[$sceneId])) continue;

                            $hasVideo = !empty($origScene['video_url']);
                            $hasImage = !empty($origScene['image_url']);
                            if (!$hasVideo && !$hasImage) continue;

                            $candidate = [
                                'url' => $hasVideo ? $origScene['video_url'] : $origScene['image_url'],
                                'thumbnail' => $origScene['image_url'] ?? ($hasVideo ? $origScene['video_url'] : ''),
                                'title' => 'Previous selection',
                                'width' => 0,
                                'height' => 0,
                                'source' => 'previous_selection',
                                'score' => 10.0,
                            ];
                            if ($hasVideo) {
                                $candidate['type'] = 'video';
                                $candidate['duration'] = $origScene['estimated_duration'] ?? 0;
                            }

                            // Remove duplicate URLs from fresh candidates
                            $origUrl = $candidate['url'];
                            $this->sceneImageCandidates[$sceneId] = array_values(array_filter(
                                $this->sceneImageCandidates[$sceneId],
                                fn($c) => ($c['url'] ?? '') !== $origUrl
                            ));

                            // Prepend original as first candidate
                            array_unshift($this->sceneImageCandidates[$sceneId], $candidate);

                            // Restore crop/video-edit/animation from original
                            if (!empty($origScene['crop'])) {
                                $this->sceneCropData[$sceneId] = $origScene['crop'];
                            }
                            if (!empty($origScene['video_edit'])) {
                                $this->sceneVideoEdits[$sceneId] = $origScene['video_edit'];
                            }
                            if (isset($origScene['animate_with_ai'])) {
                                $this->sceneAnimateWithAI[$sceneId] = $origScene['animate_with_ai'];
                            }
                        }
                    }
                    $this->recreateFromProjectId = null; // Consumed
                }

                // Auto-select first candidate per scene and auto-trim long video clips
                $this->selectedSceneImages = [];
                foreach ($this->sceneImageCandidates as $sceneId => $sceneCandidates) {
                    if (!empty($sceneCandidates)) {
                        $this->selectedSceneImages[$sceneId] = 0; // Index of first candidate
                        $firstCandidate = $sceneCandidates[0];
                        if (($firstCandidate['type'] ?? 'image') === 'video') {
                            $this->sceneAnimateWithAI[$sceneId] = $this->sceneAnimateWithAI[$sceneId] ?? false;
                            if (!isset($this->sceneVideoEdits[$sceneId])) {
                                $this->autoTrimVideoClip($sceneId, $firstCandidate);
                            }
                        }
                    } else {
                        $this->selectedSceneImages[$sceneId] = 'ai'; // No candidates → AI fallback
                    }
                }

                $this->showImageSelectionModal = true;
            } catch (\Exception $e) {
                Log::error('UrlToVideo: Image sourcing failed', ['error' => $e->getMessage()]);
                session()->flash('error', 'Failed to source images: ' . $e->getMessage());
            } finally {
                $this->isSourcingImages = false;
            }

            return;
        }

        // Standard flow: create project and dispatch immediately
        $this->showTranscriptModal = false;
        $this->isGenerating = true;

        try {
            $this->dispatchGenerationPipeline();
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Pipeline dispatch failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Video generation failed: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
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
     * Select a specific image candidate for a scene.
     */
    public function selectSceneImage(string $sceneId, int $candidateIndex)
    {
        $this->selectedSceneImages[$sceneId] = $candidateIndex;

        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
        $candidate = $candidates[$candidateIndex] ?? null;

        if ($candidate && ($candidate['type'] ?? 'image') === 'video') {
            // Disable animation (already animated content)
            $this->sceneAnimateWithAI[$sceneId] = false;
            // Auto-trim if clip is longer than scene duration
            $this->autoTrimVideoClip($sceneId, $candidate);
        } else {
            // Not a video — clear any stale video edits
            unset($this->sceneVideoEdits[$sceneId]);
        }
    }

    /**
     * Toggle AI-generated image for a scene. Click again to revert to first candidate.
     */
    public function markSceneForAI(string $sceneId)
    {
        if (($this->selectedSceneImages[$sceneId] ?? null) === 'ai') {
            // Toggle off: revert to first candidate or null
            $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
            $this->selectedSceneImages[$sceneId] = !empty($candidates) ? 0 : null;
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
     * If clip is longer than scene, set trimEnd to scene duration.
     * Preserves existing flip settings if user already edited.
     */
    protected function autoTrimVideoClip(string $sceneId, array $candidate): void
    {
        $clipDuration = (float) ($candidate['duration'] ?? 0);
        $sceneDuration = $this->getSceneDuration($sceneId);

        if ($clipDuration > 0 && $sceneDuration > 0 && $clipDuration > $sceneDuration) {
            // Preserve existing flip if user already set it
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            $this->sceneVideoEdits[$sceneId] = [
                'trimStart' => 0,
                'trimEnd' => round($sceneDuration, 1),
                'flipH' => $existing['flipH'] ?? false,
                'flipV' => $existing['flipV'] ?? false,
            ];
        } else {
            // Clip fits within scene — clear trim (keep flip if set)
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            if ($existing) {
                // Only keep flip settings, remove trim
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

        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $this->libraryCategoryResults = $stockService->browseCategory($category, 24);
    }

    /**
     * Search the library browser by text query.
     */
    public function searchLibrary(string $query)
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return;
        }

        $this->libraryActiveCategory = '';
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $this->libraryCategoryResults = $stockService->search($query, 24);
    }

    /**
     * Select a clip from the library browser and add it to the scene.
     */
    public function selectFromLibrary(int $index)
    {
        $sceneId = $this->libraryBrowseScene;
        if (empty($sceneId) || !isset($this->libraryCategoryResults[$index])) {
            return;
        }

        $candidate = $this->libraryCategoryResults[$index];

        // Add to scene candidates and auto-select
        $this->sceneImageCandidates[$sceneId][] = $candidate;
        $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
        $this->selectedSceneImages[$sceneId] = $newIndex;

        // If video, disable animation and auto-trim
        if (($candidate['type'] ?? 'image') === 'video') {
            $this->sceneAnimateWithAI[$sceneId] = false;
            $this->autoTrimVideoClip($sceneId, $candidate);
        }

        $this->showLibraryBrowser = false;
    }

    /**
     * Execute a scene search with media type filtering.
     * Called via wire:click from the search UI.
     */
    public function executeSceneSearch(string $sceneId, string $query = '', string $type = '')
    {
        // Use passed params or fall back to component properties
        $query = trim($query ?: $this->searchQuery);
        $type = $type ?: $this->searchType;

        if (mb_strlen($query) < 2) {
            return;
        }

        Log::info('UrlToVideo: executeSceneSearch called', [
            'scene' => $sceneId,
            'query' => $query,
            'type' => $type,
        ]);

        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $added = 0;

        try {
            // Search Artime Stock only (local curated media)
            $stockType = ($type === 'videos') ? 'video' : (($type === 'images') ? 'image' : null);
            $stockResults = $stockService->search($query, 12, $stockType);

            // Replace existing candidates with fresh search results (keep uploads)
            $uploads = array_filter($this->sceneImageCandidates[$sceneId] ?? [], function ($c) {
                return ($c['source'] ?? '') === 'upload';
            });
            $this->sceneImageCandidates[$sceneId] = array_values($uploads);

            foreach ($stockResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('UrlToVideo: Scene search failed', [
                'scene' => $sceneId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            session()->flash('searchError', 'Search failed: ' . $e->getMessage());
        }

        if ($added > 0) {
            session()->flash('searchSuccess', "Found {$added} results for \"{$query}\"");
        } else {
            session()->flash('searchError', "No results found for \"{$query}\"");
        }

        // Reset search input
        $this->searchQuery = '';
    }

    /**
     * Load more stock candidates for a scene.
     * Fetches additional results from the matched category or subject, excluding already-shown items.
     */
    public function loadMoreCandidates(string $sceneId)
    {
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();

        // Collect IDs already shown in this scene
        $existingIds = array_filter(array_column($this->sceneImageCandidates[$sceneId] ?? [], 'stock_id'));

        $subject = $this->storedContentBrief['subject'] ?? $this->prompt ?? '';
        $matchedCategory = !empty($subject) ? $stockService->findMatchingCategory($subject) : null;
        $added = 0;

        try {
            if ($matchedCategory) {
                // Load more from the matched category
                $results = $stockService->browseCategoryExcluding($matchedCategory, 8, $existingIds);
            } else {
                // Fallback to FULLTEXT search with exclusion
                $results = $stockService->searchExcluding($subject, 8, $existingIds);
            }

            foreach ($results as $item) {
                $this->sceneImageCandidates[$sceneId][] = $item;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('UrlToVideo: loadMoreCandidates failed', [
                'scene' => $sceneId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($added === 0) {
            session()->flash('searchError', 'No more clips available');
        }
    }

    /**
     * Search external stock sources (Pexels, Pixabay, Wikimedia) for a scene.
     * Only called when user explicitly clicks "Browse External".
     */
    public function searchExternalStock(string $sceneId, string $query = '')
    {
        $query = trim($query ?: $this->searchQuery);
        if (mb_strlen($query) < 2) {
            return;
        }

        Log::info('UrlToVideo: searchExternalStock called', [
            'scene' => $sceneId,
            'query' => $query,
        ]);

        $imageService = new ImageSourceService();
        $added = 0;

        try {
            // Search images (Wikimedia + Pexels/Pixabay photos)
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

            // Search video clips (Pexels + Pixabay videos)
            $videoResults = $imageService->searchVideoClips($query, 5);
            foreach ($videoResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('UrlToVideo: External stock search failed', [
                'scene' => $sceneId,
                'query' => $query,
                'error' => $e->getMessage(),
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
        if (empty($sceneId) || !$this->uploadedSceneImage) {
            return;
        }

        try {
            $path = $this->uploadedSceneImage->store('url-to-video/uploads', 'public');
            $publicUrl = url('/public/storage/' . $path);

            // Add as new candidate and auto-select it
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
            Log::warning('UrlToVideo: Image upload failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm image selection and dispatch generation pipeline with pre-assigned images.
     */
    public function confirmImageSelection()
    {
        $this->showImageSelectionModal = false;
        $this->isGenerating = true;

        try {
            $this->dispatchGenerationPipeline();
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Pipeline dispatch failed after image selection', ['error' => $e->getMessage()]);
            session()->flash('error', 'Video generation failed: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Shuffle creative concept — re-run submitPrompt for a new random angle.
     */
    public function shuffleCreativeConcept()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->creativeConceptTitle = null;
        $this->creativeConceptPitch = null;
        $this->alternativeConcepts = [];
        $this->showConceptCards = false;
        $this->showTranscriptModal = false;

        $this->submitPrompt();
    }

    /**
     * Generate 4 alternative creative concept cards.
     */
    public function generateMoreIdeas()
    {
        $this->isGeneratingConcepts = true;

        try {
            $extractor = new UrlContentExtractorService();
            $subject = $this->storedContentBrief['subject']
                ?? $this->generatedTitle
                ?? $this->prompt;

            $this->alternativeConcepts = $extractor->generateCreativeConcepts(
                $subject,
                $this->videoDuration,
                $this->creativeConceptTitle
            );
            $this->showConceptCards = !empty($this->alternativeConcepts);
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Creative concept generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate ideas: ' . $e->getMessage());
        } finally {
            $this->isGeneratingConcepts = false;
        }
    }

    /**
     * Select a specific concept card and generate a full script for it.
     */
    public function selectCreativeConcept(int $index)
    {
        if (!isset($this->alternativeConcepts[$index])) {
            return;
        }

        $concept = $this->alternativeConcepts[$index];
        $this->isGeneratingScript = true;
        $this->showConceptCards = false;

        try {
            $extractor = new UrlContentExtractorService();

            if (!empty($this->storedContentBrief)) {
                $enhancedPrompt = $extractor->buildCreativeConceptPrompt(
                    $this->storedContentBrief,
                    $concept,
                    $this->videoDuration
                );
            } else {
                $enhancedPrompt = $extractor->buildCreativeConceptPromptFromText(
                    $this->prompt,
                    $concept,
                    $this->videoDuration
                );
            }

            $scriptService = new StoryModeScriptService();
            $maxWords = $this->calculateMaxWords($this->videoDuration);
            $result = $scriptService->generateScript($enhancedPrompt, $this->videoDuration, $maxWords, true);

            $this->editableTranscript = $result['transcript'];
            $this->transcriptWordCount = $result['word_count'];
            $this->generatedTitle = $result['title'] ?? $this->generatedTitle;
            $this->generatedSegments = $result['segments'] ?? [];
            $this->creativeConceptTitle = $result['concept_title'] ?? $concept['title'] ?? null;
            $this->creativeConceptPitch = $result['concept_pitch'] ?? $concept['pitch'] ?? null;

            // Clear image candidates since script changed
            $this->sceneImageCandidates = [];
            $this->sceneSearchSuggestions = [];
            $this->selectedSceneImages = [];
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Creative concept script generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate script: ' . $e->getMessage());
        } finally {
            $this->isGeneratingScript = false;
        }
    }

    /**
     * Create project and dispatch the generation job.
     * Downloads selected real images and assigns image_url to scenes.
     */
    protected function dispatchGenerationPipeline(): void
    {
        $scriptService = new StoryModeScriptService();
        $targetDuration = $this->videoDuration;
        $segments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
        $wordCount = str_word_count($this->editableTranscript);

        $scenes = [];
        foreach ($segments as $i => $segment) {
            $scenes[] = [
                'id' => 'scene_' . $i,
                'index' => $i,
                'text' => $segment['text'],
                'estimated_duration' => $segment['estimated_duration'],
            ];
        }

        $sourceType = $this->detectedSourceType ?: 'prompt';
        $imageSource = $this->useRealImages ? 'real_images' : 'ai';

        // If real images mode, download selected images and assign URLs to scenes
        if ($this->useRealImages && !empty($this->selectedSceneImages)) {
            $imageService = new ImageSourceService();

            // Create a temporary project ID for file storage
            $tempProjectId = time();

            foreach ($scenes as &$scene) {
                $sceneId = $scene['id'];
                $selection = $this->selectedSceneImages[$sceneId] ?? null;

                if ($selection === 'ai' || $selection === null) {
                    // Scene will use AI generation — leave image_url empty
                    continue;
                }

                $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
                $candidate = (is_int($selection) || (is_string($selection) && ctype_digit($selection)))
                    ? ($candidates[(int) $selection] ?? null)
                    : null;

                if ($candidate && !empty($candidate['url'])) {
                    $isVideo = ($candidate['type'] ?? 'image') === 'video';

                    if ($isVideo) {
                        if (($candidate['source'] ?? '') === 'artime_stock') {
                            // Local stock video — use URL directly
                            $scene['video_url'] = $candidate['url'];
                            if (!empty($candidate['thumbnail'])) {
                                $scene['image_url'] = $candidate['thumbnail'];
                            }
                        } else {
                            // Download external video clip
                            $localUrl = $imageService->downloadAndStoreVideo(
                                $candidate['url'],
                                $tempProjectId,
                                $sceneId
                            );
                            if ($localUrl) {
                                $scene['video_url'] = $localUrl;
                                // Use thumbnail as fallback image for assembly
                                if (!empty($candidate['thumbnail'])) {
                                    $thumbUrl = $imageService->downloadAndStore(
                                        $candidate['thumbnail'],
                                        $tempProjectId,
                                        $sceneId
                                    );
                                    $scene['image_url'] = $thumbUrl;
                                }
                            }
                        }
                    } elseif (in_array($candidate['source'] ?? '', ['upload', 'artime_stock'])) {
                        // Local file (upload or Artime Stock) — use URL directly
                        $scene['image_url'] = $candidate['url'];
                    } else {
                        // Download external image
                        $localUrl = $imageService->downloadAndStore(
                            $candidate['url'],
                            $tempProjectId,
                            $sceneId
                        );
                        if ($localUrl) {
                            $scene['image_url'] = $localUrl;
                        }
                    }
                }
            }
            unset($scene);

            // Attach crop/position data, animation flag, and video edits to scenes
            foreach ($scenes as &$scene) {
                $sceneId = $scene['id'] ?? '';
                if (!empty($this->sceneCropData[$sceneId])) {
                    $scene['crop'] = $this->sceneCropData[$sceneId];
                }
                if (!empty($this->sceneVideoEdits[$sceneId])) {
                    $scene['video_edit'] = $this->sceneVideoEdits[$sceneId];
                }
                $scene['animate_with_ai'] = $this->sceneAnimateWithAI[$sceneId] ?? false;
            }
            unset($scene);

            // Log how many scenes got real images
            $realCount = collect($scenes)->filter(fn($s) => !empty($s['image_url']))->count();
            \Log::info('UrlToVideo: Real images assigned', [
                'total_scenes' => count($scenes),
                'real_images' => $realCount,
                'scene_urls' => collect($scenes)->pluck('image_url', 'id')->toArray(),
            ]);
        }

        $project = UrlToVideoProject::create([
            'user_id' => auth()->id(),
            'team_id' => session('current_team_id'),
            'title' => $this->generatedTitle ?: 'Untitled Video',
            'prompt' => $this->prompt ?: null,
            'source_url' => $this->sourceUrl ?: '',
            'source_type' => $sourceType,
            'extracted_content' => $this->storedExtractedContent ?: null,
            'content_brief' => $this->storedContentBrief ?: null,
            'aspect_ratio' => $this->aspectRatio,
            'voice_id' => $this->selectedVoice !== 'auto' ? $this->selectedVoice : null,
            'voice_provider' => $this->voiceProvider ?: null,
            'transcript' => $this->editableTranscript,
            'transcript_word_count' => $wordCount,
            'scenes' => $scenes,
            'status' => 'generating_voiceover',
            'progress_percent' => 15,
            'current_stage' => 'Starting pipeline',
            'metadata' => [
                'started_at' => now()->toIso8601String(),
                'ai_engine' => get_option('story_mode_ai_engine', 'gemini'),
                'video_resolution' => $this->videoResolution,
                'video_quality' => $this->videoQuality,
                'video_duration_target' => $this->videoDuration,
                'narrative_style' => $this->creativeMode ? 'creative' : $this->narrativeStyle,
                'image_source' => $imageSource,
                'creative_mode' => $this->creativeMode,
                'creative_concept_title' => $this->creativeConceptTitle,
                'creative_concept_pitch' => $this->creativeConceptPitch,
            ],
        ]);

        $this->activeProjectId = $project->id;
        $this->detailProjectId = $project->id;
        Cache::forget('url-to-video-projects-' . auth()->id());

        UrlToVideoGenerationJob::dispatch($project->id)
            ->onQueue('video-wizard-images');

        Log::info('UrlToVideo: Generation job dispatched', [
            'project_id' => $project->id,
            'image_source' => $imageSource,
        ]);
    }

    public function updatedNarrativeStyle()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->sceneImageCandidates = [];
        $this->sceneSearchSuggestions = [];
        $this->selectedSceneImages = [];
        $this->sceneAnimateWithAI = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
    }

    public function updatedCreativeMode()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->sceneImageCandidates = [];
        $this->sceneSearchSuggestions = [];
        $this->selectedSceneImages = [];
        $this->sceneAnimateWithAI = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
        $this->creativeConceptTitle = null;
        $this->creativeConceptPitch = null;
        $this->alternativeConcepts = [];
        $this->showConceptCards = false;
    }

    public function updatedVideoDuration()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->sceneImageCandidates = [];
        $this->sceneSearchSuggestions = [];
        $this->selectedSceneImages = [];
        $this->sceneAnimateWithAI = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
    }

    public function updatedEditableTranscript()
    {
        $this->transcriptWordCount = str_word_count($this->editableTranscript ?? '');
    }

    public function openVoiceModal()
    {
        $this->showVoiceModal = true;
    }

    public function selectVoice(string $voiceId, string $provider = '')
    {
        $this->selectedVoice = $voiceId;
        $this->voiceProvider = $provider;
        $this->showVoiceModal = false;
    }

    public function openProject(int $projectId)
    {
        $this->detailProjectId = $projectId;
    }

    public function closeProject()
    {
        $this->detailProjectId = null;
    }

    public function deleteProject(int $projectId)
    {
        $project = UrlToVideoProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if ($project) {
            $project->deleteWithFiles();
            Cache::forget('url-to-video-projects-' . auth()->id());
            if ($this->detailProjectId === $projectId) {
                $this->detailProjectId = null;
            }
            if ($this->activeProjectId === $projectId) {
                $this->activeProjectId = null;
            }
        }
    }

    /**
     * Re-create a project from an existing one.
     * Shows the transcript modal so the user can review/edit, then on confirm
     * restores original per-scene media selections + fresh alternatives.
     */
    public function recreateProject(int $projectId)
    {
        $project = UrlToVideoProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$project) {
            return;
        }

        // Load project data back into component state
        $this->prompt = $project->prompt ?? '';
        $this->sourceUrl = $project->source_url ?? '';
        $this->detectedSourceType = $project->source_type ?? 'prompt';
        $this->editableTranscript = $project->transcript;
        $this->transcriptWordCount = $project->transcript_word_count ?? str_word_count($project->transcript ?? '');
        $this->generatedTitle = $project->title;
        $this->aspectRatio = $project->aspect_ratio ?? '9:16';
        $this->selectedVoice = $project->voice_id ?? 'auto';
        $this->voiceProvider = $project->voice_provider ?? '';
        $this->storedExtractedContent = $project->extracted_content ?? [];
        $this->storedContentBrief = $project->content_brief ?? [];

        // Restore metadata settings
        $meta = $project->metadata ?? [];
        $this->videoResolution = $meta['video_resolution'] ?? '480p';
        $this->videoQuality = $meta['video_quality'] ?? 'pro';
        $this->videoDuration = $meta['video_duration_target'] ?? 60;
        $this->narrativeStyle = $meta['narrative_style'] ?? 'hook_reveal';
        $this->creativeMode = $meta['creative_mode'] ?? false;
        $this->creativeConceptTitle = $meta['creative_concept_title'] ?? null;
        $this->creativeConceptPitch = $meta['creative_concept_pitch'] ?? null;

        // Determine image source mode
        $imageSource = $meta['image_source'] ?? 'ai';
        $this->useRealImages = ($imageSource === 'real_images');

        // Store the original project ID so confirmTranscript can restore clips
        $this->recreateFromProjectId = $projectId;

        // Reset cached image selections (will be rebuilt on confirmTranscript)
        $this->sceneImageCandidates = [];
        $this->selectedSceneImages = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
        $this->sceneAnimateWithAI = [];

        // Close the detail modal and show the transcript editor
        $this->detailProjectId = null;
        $this->showTranscriptModal = true;

        Log::info('UrlToVideo: Recreating project — showing transcript editor', [
            'original_project_id' => $projectId,
            'image_source' => $imageSource,
            'transcript_words' => $this->transcriptWordCount,
        ]);
    }

    #[Computed]
    public function activeProject()
    {
        if ($this->activeProjectId) {
            return UrlToVideoProject::find($this->activeProjectId);
        }
        return null;
    }

    #[Computed]
    public function detailProject()
    {
        if ($this->detailProjectId) {
            return UrlToVideoProject::find($this->detailProjectId);
        }
        return null;
    }

    #[Computed]
    public function userProjects()
    {
        return Cache::remember(
            'url-to-video-projects-' . auth()->id(),
            60,
            fn () => UrlToVideoProject::forUser(auth()->id())
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get()
        );
    }

    public function getDurationPresetsProperty(): array
    {
        return [
            ['value' => 60,  'label' => '1 min'],
            ['value' => 90,  'label' => '1.5 min'],
            ['value' => 120, 'label' => '2 min'],
            ['value' => 180, 'label' => '3 min'],
            ['value' => 300, 'label' => '5 min'],
        ];
    }

    protected function calculateMaxWords(int $duration): int
    {
        return (int) round(($duration / 60) * 140 * 1.3);
    }

    public function getNarrativePresetsProperty(): array
    {
        return [
            ['key' => 'hook_reveal', 'name' => 'Hook & Reveal', 'icon' => 'fa-light fa-bolt'],
            ['key' => 'narrator', 'name' => 'Narrator', 'icon' => 'fa-light fa-microphone'],
            ['key' => 'storytime', 'name' => 'Storytime', 'icon' => 'fa-light fa-book-open'],
            ['key' => 'did_you_know', 'name' => 'Did You Know', 'icon' => 'fa-light fa-lightbulb'],
            ['key' => 'hot_take', 'name' => 'Hot Take', 'icon' => 'fa-light fa-fire'],
            ['key' => 'breaking', 'name' => 'Breaking News', 'icon' => 'fa-light fa-signal-stream'],
            ['key' => 'top_facts', 'name' => 'Top Facts', 'icon' => 'fa-light fa-list-ol'],
            ['key' => 'cinematic', 'name' => 'Cinematic', 'icon' => 'fa-light fa-clapperboard'],
            ['key' => 'comedy', 'name' => 'Comedy Roast', 'icon' => 'fa-light fa-face-laugh'],
            ['key' => 'motivational', 'name' => 'Motivational', 'icon' => 'fa-light fa-rocket'],
            ['key' => 'debate', 'name' => 'Debate', 'icon' => 'fa-light fa-scale-balanced'],
            ['key' => 'mystery', 'name' => 'Mystery', 'icon' => 'fa-light fa-mask'],
        ];
    }

    public function getVoicesProperty(): array
    {
        return [
            ['id' => 'auto', 'name' => 'Auto Select', 'gender' => 'auto', 'provider' => '', 'description' => 'AI picks the best narrator'],
            ['id' => 'nova', 'name' => 'Nova', 'gender' => 'female', 'provider' => 'openai', 'description' => 'Friendly & warm'],
            ['id' => 'alloy', 'name' => 'Alloy', 'gender' => 'neutral', 'provider' => 'openai', 'description' => 'Versatile & balanced'],
            ['id' => 'echo', 'name' => 'Echo', 'gender' => 'male', 'provider' => 'openai', 'description' => 'Warm & conversational'],
            ['id' => 'fable', 'name' => 'Fable', 'gender' => 'neutral', 'provider' => 'openai', 'description' => 'Storytelling narrator'],
            ['id' => 'onyx', 'name' => 'Onyx', 'gender' => 'male', 'provider' => 'openai', 'description' => 'Deep & authoritative'],
            ['id' => 'shimmer', 'name' => 'Shimmer', 'gender' => 'female', 'provider' => 'openai', 'description' => 'Bright & energetic'],
            ['id' => 'af_bella', 'name' => 'Bella', 'gender' => 'female', 'provider' => 'kokoro', 'description' => 'Natural American female'],
            ['id' => 'af_sarah', 'name' => 'Sarah', 'gender' => 'female', 'provider' => 'kokoro', 'description' => 'Clear American female'],
            ['id' => 'am_adam', 'name' => 'Adam', 'gender' => 'male', 'provider' => 'kokoro', 'description' => 'Natural American male'],
            ['id' => 'am_michael', 'name' => 'Michael', 'gender' => 'male', 'provider' => 'kokoro', 'description' => 'Professional American male'],
        ];
    }

    public function render()
    {
        return view('appvideowizard::livewire.url-to-video');
    }
}
