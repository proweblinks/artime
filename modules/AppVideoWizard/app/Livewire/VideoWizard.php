<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;
use Modules\AppVideoWizard\Services\ConceptService;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\VoiceoverService;
use Modules\AppVideoWizard\Services\StockMediaService;
use Modules\AppVideoWizard\Services\CharacterExtractionService;
use Modules\AppVideoWizard\Services\LocationExtractionService;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoWizard extends Component
{
    use WithFileUploads;

    // Import file for project import
    public $importFile;

    // Reference image uploads for Character/Location/Style Bible
    public $characterImageUpload;
    public $locationImageUpload;
    public $styleImageUpload;
    public bool $isGeneratingStyleRef = false;

    // Project state
    public ?int $projectId = null;
    public string $projectName = 'Untitled Video';
    public int $currentStep = 1;
    public int $maxReachedStep = 1;

    // Step 1: Platform & Format
    public ?string $platform = null;
    public string $aspectRatio = '16:9';
    public int $targetDuration = 60;
    public ?string $format = null;
    public ?string $productionType = null;
    public ?string $productionSubtype = null;

    // Step 2: Concept
    public array $concept = [
        'rawInput' => '',
        'refinedConcept' => '',
        'keywords' => [],
        'keyElements' => [],
        'logline' => '',
        'suggestedMood' => null,
        'suggestedTone' => null,
        'styleReference' => '',
        'avoidElements' => '',
        'targetAudience' => '',
    ];

    // Step 2: Character Intelligence (affects script generation)
    public array $characterIntelligence = [
        'enabled' => true,
        'narrationStyle' => 'voiceover', // voiceover, dialogue, narrator, none
        'characterCount' => 4,
        'suggestedCount' => 4,
        'characters' => [], // Will be populated after script generation
    ];

    // Step 3: Script
    public array $script = [
        'title' => '',
        'hook' => '',
        'scenes' => [],
        'cta' => '',
        'totalDuration' => 0,
        'totalNarrationTime' => 0,
    ];

    /// Step 3: Progressive Script Generation State
    public array $scriptGeneration = [
        'status' => 'idle',              // 'idle' | 'generating' | 'paused' | 'complete'
        'targetSceneCount' => 0,         // Total scenes needed (e.g., 30)
        'generatedSceneCount' => 0,      // Scenes generated so far
        'batchSize' => 5,                // Scenes per batch
        'currentBatch' => 0,             // Current batch index (0-indexed)
        'totalBatches' => 0,             // Total batches needed
        'batches' => [],                 // Batch status tracking
        'autoGenerate' => false,         // Auto-continue to next batch
        'maxRetries' => 3,               // Max retry attempts per batch
        'retryDelayMs' => 1000,          // Base delay for exponential backoff (1s, 2s, 4s)
    ];

    // Step 3: Voice & Dialogue Status
    public array $voiceStatus = [
        'dialogueLines' => 0,
        'speakers' => 0,
        'voicesMapped' => 0,
        'scenesWithDialogue' => 0,
        'scenesWithVoiceover' => 0,
        'pendingVoices' => 0,
    ];

    // Step 4: Storyboard
    public array $storyboard = [
        'scenes' => [],
        'styleBible' => null,
        'imageModel' => 'nanobanana', // Default to NanoBanana (Gemini) - HiDream requires RunPod setup
        'visualStyle' => [
            'mood' => '',
            'lighting' => '',
            'colorPalette' => '',
            'composition' => '',
        ],
        'technicalSpecs' => [
            'enabled' => true,
            'quality' => '4k',
            'positive' => 'high quality, detailed, professional, 8K resolution, sharp focus',
            'negative' => 'blurry, low quality, ugly, distorted, watermark, nsfw, text, logo',
        ],
        'promptChain' => [
            'enabled' => true,
            'status' => 'pending',
            'processedAt' => null,
            'scenes' => [],
        ],
    ];

    // Step 5: Animation
    public array $animation = [
        'scenes' => [],
        'voiceover' => [
            'voice' => 'nova',
            'speed' => 1.0,
        ],
    ];

    // Step 6: Assembly
    public array $assembly = [
        'transitions' => [],
        'defaultTransition' => 'fade',
        'music' => ['enabled' => false, 'trackId' => null, 'volume' => 30],
        'captions' => [
            'enabled' => true,
            'style' => 'karaoke',
            'position' => 'bottom',
            'size' => 1,
        ],
    ];

    // UI state
    public bool $isLoading = false;
    public bool $isSaving = false;
    public bool $isTransitioning = false;  // Track step transitions for loading overlay
    public ?string $transitionMessage = null;  // Message to show during transition
    public ?string $error = null;

    // Stock Media Browser state
    public bool $showStockBrowser = false;
    public int $stockBrowserSceneIndex = 0;
    public string $stockSearchQuery = '';
    public string $stockMediaType = 'image';
    public string $stockOrientation = 'landscape';
    public array $stockSearchResults = [];
    public bool $stockSearching = false;

    // Edit Prompt/Scene Modal state
    public bool $showEditPromptModal = false;
    public int $editPromptSceneIndex = 0;
    public string $editPromptText = '';
    public string $editSceneNarration = '';
    public int $editSceneDuration = 8;
    public string $editSceneTransition = 'cut';

    // Project Manager Modal state
    public bool $showProjectManager = false;
    public array $projectManagerProjects = [];
    public string $projectManagerSearch = '';
    public string $projectManagerSort = 'updated_at';
    public string $projectManagerSortDirection = 'desc';
    public string $projectManagerStatusFilter = 'all';
    public int $projectManagerPage = 1;
    public int $projectManagerPerPage = 12;
    public int $projectManagerTotal = 0;
    public array $projectManagerStatusCounts = [
        'all' => 0,
        'draft' => 0,
        'in_progress' => 0,
        'complete' => 0,
    ];
    public array $projectManagerSelected = [];
    public bool $projectManagerSelectMode = false;

    // Scene Memory state (Style Bible, Character Bible, Location Bible)
    public array $sceneMemory = [
        'styleBible' => [
            'enabled' => false,
            'style' => '',
            'colorGrade' => '',
            'atmosphere' => '',
            'camera' => '',
            'visualDNA' => '',
            'referenceImage' => '',
            'referenceImageSource' => '',
        ],
        'characterBible' => [
            'enabled' => false,
            'characters' => [],
        ],
        'locationBible' => [
            'enabled' => false,
            'locations' => [],
        ],
    ];

    // RunPod job polling state
    public array $pendingJobs = [];

    // Generation progress tracking
    public int $generationProgress = 0;
    public int $generationTotal = 0;
    public ?string $generationCurrentScene = null;

    // Concept variations state
    public array $conceptVariations = [];
    public int $selectedConceptIndex = 0;

    // Script generation options
    public string $scriptTone = 'engaging';
    public string $contentDepth = 'detailed';
    public string $additionalInstructions = '';

    // Narrative Structure Intelligence (Hollywood-level script generation)
    public ?string $narrativePreset = null; // Platform-optimized storytelling formula
    public ?string $storyArc = null; // Structure like three-act, hero's journey
    public ?string $tensionCurve = null; // Pacing dynamics
    public ?string $emotionalJourney = null; // The feeling arc for viewers
    public bool $showNarrativeAdvanced = false; // Toggle for advanced options
    public ?string $contentFormatOverride = null; // Manual override: 'short' or 'feature' (null = auto from duration)

    // Multi-Shot Mode state
    public array $multiShotMode = [
        'enabled' => false,
        'defaultShotCount' => 3,
        'decomposedScenes' => [],
        'batchStatus' => null,
        'globalVisualProfile' => null,
    ];
    public bool $showMultiShotModal = false;
    public int $multiShotSceneIndex = 0;
    public int $multiShotCount = 3;

    // Shot Preview Modal state
    public bool $showShotPreviewModal = false;
    public int $shotPreviewSceneIndex = 0;
    public int $shotPreviewShotIndex = 0;
    public string $shotPreviewTab = 'image'; // 'image' or 'video'

    // Frame Capture Modal state
    public bool $showFrameCaptureModal = false;
    public int $frameCaptureSceneIndex = 0;
    public int $frameCaptureShotIndex = 0;
    public ?string $capturedFrame = null;

    // Upscale Modal state
    public bool $showUpscaleModal = false;
    public int $upscaleSceneIndex = 0;
    public string $upscaleQuality = 'hd'; // 'hd' or '4k'
    public bool $isUpscaling = false;

    // AI Edit Modal state
    public bool $showAIEditModal = false;
    public int $aiEditSceneIndex = 0;
    public string $aiEditPrompt = '';
    public int $aiEditBrushSize = 30;
    public bool $isApplyingEdit = false;

    // Character Bible Modal state
    public bool $showCharacterBibleModal = false;
    public int $editingCharacterIndex = 0;
    public bool $isGeneratingPortrait = false;

    // Location Bible Modal state
    public bool $showLocationBibleModal = false;
    public int $editingLocationIndex = 0;
    public bool $isGeneratingLocationRef = false;

    // Scene Overwrite Confirmation Modal
    public bool $showSceneOverwriteModal = false;
    public string $sceneOverwriteAction = 'replace'; // 'replace' or 'append'

    // Storyboard Pagination (Performance optimization for 45+ scenes)
    public int $storyboardPage = 1;
    public int $storyboardPerPage = 12;

    // Save debouncing
    protected int $saveDebounceMs = 500;
    protected ?string $lastSaveHash = null;

    /**
     * Get paginated scenes for storyboard display.
     * Returns only scenes for current page to optimize rendering.
     */
    public function getPaginatedScenesProperty(): array
    {
        $allScenes = $this->script['scenes'] ?? [];
        $totalScenes = count($allScenes);

        if ($totalScenes <= $this->storyboardPerPage) {
            // No pagination needed for small scene counts
            return [
                'scenes' => $allScenes,
                'indices' => range(0, max(0, $totalScenes - 1)),
                'totalPages' => 1,
                'currentPage' => 1,
                'totalScenes' => $totalScenes,
                'showingFrom' => 1,
                'showingTo' => $totalScenes,
                'hasPrevious' => false,
                'hasNext' => false,
            ];
        }

        $totalPages = (int) ceil($totalScenes / $this->storyboardPerPage);
        $currentPage = max(1, min($this->storyboardPage, $totalPages));
        $offset = ($currentPage - 1) * $this->storyboardPerPage;

        $paginatedScenes = array_slice($allScenes, $offset, $this->storyboardPerPage, true);
        $indices = array_keys($paginatedScenes);

        return [
            'scenes' => array_values($paginatedScenes),
            'indices' => $indices,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'totalScenes' => $totalScenes,
            'showingFrom' => $offset + 1,
            'showingTo' => min($offset + $this->storyboardPerPage, $totalScenes),
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages,
        ];
    }

    /**
     * Navigate to storyboard page.
     */
    public function goToStoryboardPage(int $page): void
    {
        $totalPages = (int) ceil(count($this->script['scenes'] ?? []) / $this->storyboardPerPage);
        $this->storyboardPage = max(1, min($page, $totalPages));
    }

    /**
     * Navigate to next storyboard page.
     */
    public function nextStoryboardPage(): void
    {
        $this->goToStoryboardPage($this->storyboardPage + 1);
    }

    /**
     * Navigate to previous storyboard page.
     */
    public function previousStoryboardPage(): void
    {
        $this->goToStoryboardPage($this->storyboardPage - 1);
    }

    /**
     * Jump to the page containing a specific scene.
     */
    public function goToScenePage(int $sceneIndex): void
    {
        $page = (int) floor($sceneIndex / $this->storyboardPerPage) + 1;
        $this->goToStoryboardPage($page);
    }

    /**
     * Mount the component.
     * Note: We accept mixed $project to avoid Livewire's implicit model binding
     * which fails when null is passed.
     */
    public function mount($project = null)
    {
        // Handle both WizardProject instance and null
        if ($project instanceof WizardProject && $project->exists) {
            $this->loadProject($project);
            $this->recoverPendingJobs($project);
        }
    }

    /**
     * Recover pending async jobs from database.
     * This restores job tracking after page refresh.
     * Jobs older than 10 minutes are automatically marked as timed out.
     */
    protected function recoverPendingJobs(WizardProject $project): void
    {
        $pendingJobs = WizardProcessingJob::where('project_id', $project->id)
            ->whereIn('status', [
                WizardProcessingJob::STATUS_PENDING,
                WizardProcessingJob::STATUS_PROCESSING
            ])
            ->get();

        // First, clean up any stuck "generating" scenes that don't have valid pending jobs
        $this->cleanupStuckScenes($project, $pendingJobs);

        if ($pendingJobs->isEmpty()) {
            return;
        }

        // Timeout threshold: 10 minutes
        $timeoutThreshold = now()->subMinutes(10);

        // Restore pending jobs to component state
        foreach ($pendingJobs as $job) {
            // Check if job has timed out
            if ($job->created_at < $timeoutThreshold) {
                $job->markAsFailed('Job timed out after 10 minutes');
                \Log::warning("Auto-cancelled timed out job", [
                    'jobId' => $job->id,
                    'externalJobId' => $job->external_job_id,
                    'createdAt' => $job->created_at,
                ]);
                continue;
            }

            $inputData = $job->input_data ?? [];
            $sceneIndex = $inputData['sceneIndex'] ?? null;

            if ($sceneIndex !== null && $job->type === WizardProcessingJob::TYPE_IMAGE_GENERATION) {
                // Mark scene as generating in storyboard
                if (!isset($this->storyboard['scenes'][$sceneIndex])) {
                    $this->storyboard['scenes'][$sceneIndex] = [];
                }
                $this->storyboard['scenes'][$sceneIndex]['status'] = 'generating';
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = $job->external_job_id;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = $job->id;

                // Add to pendingJobs array for polling
                $this->pendingJobs[$sceneIndex] = [
                    'jobId' => $job->external_job_id,
                    'processingJobId' => $job->id,
                    'type' => $job->type,
                    'sceneIndex' => $sceneIndex,
                ];
            }
        }

        // Dispatch event to start polling if we have pending jobs
        if (!empty($this->pendingJobs)) {
            $this->dispatch('resume-job-polling', count: count($this->pendingJobs));
        }
    }

    /**
     * Clean up scenes that are stuck in "generating" status without valid pending jobs.
     */
    protected function cleanupStuckScenes(WizardProject $project, $pendingJobs): void
    {
        // Get scene indices that have valid pending jobs
        $validPendingScenes = [];
        foreach ($pendingJobs as $job) {
            $inputData = $job->input_data ?? [];
            $sceneIndex = $inputData['sceneIndex'] ?? null;
            if ($sceneIndex !== null) {
                $validPendingScenes[] = $sceneIndex;
            }
        }

        // Check each scene in storyboard
        $needsSave = false;
        if (isset($this->storyboard['scenes']) && is_array($this->storyboard['scenes'])) {
            foreach ($this->storyboard['scenes'] as $index => $scene) {
                // If scene is "generating" but doesn't have a valid pending job, reset it
                if (($scene['status'] ?? '') === 'generating' && !in_array($index, $validPendingScenes)) {
                    // Reset to error state to allow regeneration
                    $this->storyboard['scenes'][$index]['status'] = 'error';
                    $this->storyboard['scenes'][$index]['error'] = 'Previous generation failed or was interrupted';
                    unset($this->storyboard['scenes'][$index]['jobId']);
                    unset($this->storyboard['scenes'][$index]['processingJobId']);
                    $needsSave = true;
                }
            }
        }

        // Save if we made changes
        if ($needsSave) {
            $this->saveProject();
        }
    }

    /**
     * Load project data into component state.
     */
    protected function loadProject(WizardProject $project): void
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->currentStep = $project->current_step;
        $this->maxReachedStep = $project->max_reached_step;

        $this->platform = $project->platform;
        $this->aspectRatio = $project->aspect_ratio;
        $this->targetDuration = $project->target_duration;
        $this->format = $project->format;
        $this->productionType = $project->production_type;
        $this->productionSubtype = $project->production_subtype;

        if ($project->concept) {
            $this->concept = array_merge($this->concept, $project->concept);
        }
        if ($project->script) {
            $this->script = array_merge($this->script, $project->script);
            // Sanitize loaded script data to prevent type errors in views
            $this->sanitizeScriptData();
        }
        if ($project->storyboard) {
            $this->storyboard = array_merge($this->storyboard, $project->storyboard);
        }
        if ($project->animation) {
            $this->animation = array_merge($this->animation, $project->animation);
        }
        if ($project->assembly) {
            $this->assembly = array_merge($this->assembly, $project->assembly);
        }

        // Restore Scene Memory, Multi-Shot Mode, Concept Variations, and Character Intelligence from content_config
        if ($project->content_config) {
            $config = $project->content_config;

            if (isset($config['sceneMemory'])) {
                $this->sceneMemory = array_merge($this->sceneMemory, $config['sceneMemory']);
            }
            if (isset($config['multiShotMode'])) {
                $this->multiShotMode = array_merge($this->multiShotMode, $config['multiShotMode']);
            }
            if (isset($config['conceptVariations'])) {
                $this->conceptVariations = $config['conceptVariations'];
            }
            if (isset($config['characterIntelligence'])) {
                $this->characterIntelligence = array_merge($this->characterIntelligence, $config['characterIntelligence']);
            }

            // Restore script generation state (for resuming after browser refresh)
            if (isset($config['scriptGeneration'])) {
                $this->scriptGeneration = array_merge($this->scriptGeneration, $config['scriptGeneration']);
                // If generation was in progress, set to paused so user can resume
                if (in_array($this->scriptGeneration['status'], ['generating', 'retrying'])) {
                    $this->scriptGeneration['status'] = 'paused';
                    // Also update any generating/retrying batches to pending
                    foreach ($this->scriptGeneration['batches'] as &$batch) {
                        if (in_array($batch['status'], ['generating', 'retrying'])) {
                            $batch['status'] = 'pending';
                        }
                    }
                }
            }
        }

        // Recalculate voice status if script exists
        if (!empty($this->script['scenes'])) {
            $this->recalculateVoiceStatus();
        }

        // Initialize save hash to prevent redundant save after loading
        $this->lastSaveHash = $this->computeSaveHash();
    }

    /**
     * Compute a hash of the current saveable state.
     * Used to detect changes and avoid redundant saves.
     */
    protected function computeSaveHash(): string
    {
        $data = [
            'name' => $this->projectName,
            'current_step' => $this->currentStep,
            'platform' => $this->platform,
            'aspect_ratio' => $this->aspectRatio,
            'target_duration' => $this->targetDuration,
            'format' => $this->format,
            'production_type' => $this->productionType,
            'production_subtype' => $this->productionSubtype,
            'concept' => $this->concept,
            'script' => $this->script,
            'storyboard' => $this->storyboard,
            'animation' => $this->animation,
            'assembly' => $this->assembly,
            'sceneMemory' => $this->sceneMemory,
            'multiShotMode' => $this->multiShotMode,
        ];

        return md5(json_encode($data));
    }

    /**
     * Save project with change detection to avoid redundant database writes.
     * Uses hash comparison to skip saves when nothing has changed.
     */
    public function saveProject(): void
    {
        // Skip save if nothing has changed (except for new projects)
        $currentHash = $this->computeSaveHash();
        if ($this->projectId && $this->lastSaveHash === $currentHash) {
            return; // No changes to save
        }

        $this->isSaving = true;
        $isNewProject = !$this->projectId;

        try {
            $data = [
                'name' => $this->projectName,
                'current_step' => $this->currentStep,
                'max_reached_step' => max($this->maxReachedStep, $this->currentStep),
                'platform' => $this->platform,
                'aspect_ratio' => $this->aspectRatio,
                'target_duration' => $this->targetDuration,
                'format' => $this->format,
                'production_type' => $this->productionType,
                'production_subtype' => $this->productionSubtype,
                'concept' => $this->concept,
                'script' => $this->script,
                'storyboard' => $this->storyboard,
                'animation' => $this->animation,
                'assembly' => $this->assembly,
                // Save Scene Memory, Multi-Shot Mode, Concept Variations, Character Intelligence, and Script Generation State
                'content_config' => [
                    'sceneMemory' => $this->sceneMemory,
                    'multiShotMode' => $this->multiShotMode,
                    'conceptVariations' => $this->conceptVariations,
                    'characterIntelligence' => $this->characterIntelligence,
                    'scriptGeneration' => $this->scriptGeneration,
                ],
            ];

            if ($this->projectId) {
                $project = WizardProject::findOrFail($this->projectId);
                $project->update($data);
            } else {
                $project = WizardProject::create(array_merge($data, [
                    'user_id' => auth()->id(),
                    'team_id' => session('current_team_id'),
                ]));
                $this->projectId = $project->id;
            }

            // Update hash after successful save
            $this->lastSaveHash = $currentHash;

            $this->dispatch('project-saved', projectId: $this->projectId);

            // Update browser URL with project ID for new projects
            if ($isNewProject && $this->projectId) {
                $this->dispatch('update-browser-url', projectId: $this->projectId);
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to save project: ' . $e->getMessage();
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Force save project even if no changes detected.
     * Use this when you need to ensure the save happens.
     */
    public function forceSaveProject(): void
    {
        $this->lastSaveHash = null;
        $this->saveProject();
    }

    /**
     * Go to a specific step.
     */
    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 7) {
            return;
        }

        // Can only go to steps we've reached or the next step
        if ($step <= $this->maxReachedStep + 1) {
            $previousStep = $this->currentStep;
            $this->currentStep = $step;
            $this->maxReachedStep = max($this->maxReachedStep, $step);

            // Step Transition Hook: Auto-populate Scene Memory when entering Storyboard (step 4)
            // Use deferred async call to prevent blocking the UI
            if ($step === 4 && $previousStep !== 4 && !empty($this->script['scenes'])) {
                $this->isTransitioning = true;
                $this->transitionMessage = __('Analyzing script for characters and locations...');

                // Dispatch event to trigger async population after view renders
                $this->dispatch('step-changed', step: $step, needsPopulation: true);
            }

            // Only save if user is authenticated
            if (auth()->check()) {
                $this->saveProject();
            }
        }
    }

    /**
     * Handle deferred scene memory population after step transition.
     * This is called async after the view renders to prevent blocking.
     */
    #[On('populate-scene-memory')]
    public function handleDeferredSceneMemoryPopulation(): void
    {
        if (!$this->isTransitioning) {
            return;
        }

        try {
            $this->autoPopulateSceneMemory();
        } catch (\Exception $e) {
            Log::warning('VideoWizard: Scene memory population failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isTransitioning = false;
            $this->transitionMessage = null;
        }
    }

    /**
     * Go to next step.
     */
    public function nextStep(): void
    {
        $this->goToStep($this->currentStep + 1);
    }

    /**
     * Go to previous step.
     */
    public function previousStep(): void
    {
        $this->goToStep($this->currentStep - 1);
    }

    /**
     * Update platform selection.
     */
    public function selectPlatform(string $platformId): void
    {
        $this->platform = $platformId;

        $platforms = config('appvideowizard.platforms');
        if (isset($platforms[$platformId])) {
            $platform = $platforms[$platformId];
            $this->aspectRatio = $platform['defaultFormat'];

            // Get the max duration - but respect production type's suggested range if it's higher
            $maxDuration = $platform['maxDuration'];
            $minDuration = $platform['minDuration'];

            // If production type is set and allows longer videos, use that range instead
            $productionDuration = $this->getProductionTypeDurationRange();
            if ($productionDuration) {
                // Use the higher of platform max or production type max (for movies/films)
                $maxDuration = max($maxDuration, $productionDuration['max']);
                $minDuration = $productionDuration['min'];
            }

            // Only adjust duration if it's outside the valid range
            if ($this->targetDuration < $minDuration) {
                $this->targetDuration = $minDuration;
            } elseif ($this->targetDuration > $maxDuration) {
                $this->targetDuration = $maxDuration;
            }
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Get the suggested duration range for the selected production type.
     */
    public function getProductionTypeDurationRange(): ?array
    {
        if (empty($this->productionType)) {
            return null;
        }

        $productionTypes = config('appvideowizard.production_types', []);
        $type = $productionTypes[$this->productionType] ?? null;

        if (!$type) {
            return null;
        }

        // Check if subtype has a specific duration range
        if ($this->productionSubtype && isset($type['subTypes'][$this->productionSubtype]['suggestedDuration'])) {
            return $type['subTypes'][$this->productionSubtype]['suggestedDuration'];
        }

        // Look for duration in any subtype as a fallback
        if (isset($type['subTypes']) && is_array($type['subTypes'])) {
            foreach ($type['subTypes'] as $subtype) {
                if (isset($subtype['suggestedDuration'])) {
                    return $subtype['suggestedDuration'];
                }
            }
        }

        return null;
    }

    /**
     * Update format selection.
     */
    public function selectFormat(string $formatId): void
    {
        $this->format = $formatId;

        $formats = config('appvideowizard.formats');
        if (isset($formats[$formatId])) {
            $this->aspectRatio = $formats[$formatId]['aspectRatio'];
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Update production type and auto-apply recommended narrative preset.
     * This enables cascading selection where Step 1 choices influence Step 3 defaults.
     */
    public function selectProductionType(string $type, ?string $subtype = null): void
    {
        $this->productionType = $type;
        $this->productionSubtype = $subtype;

        // Get the recommended preset mapping and auto-apply default
        $mapping = $this->getPresetMappingForProduction();
        if (!empty($mapping['default']) && empty($this->narrativePreset)) {
            $this->applyNarrativePreset($mapping['default']);
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Determine if current duration is feature-length (20+ minutes).
     */
    public function isFeatureLength(): bool
    {
        return $this->targetDuration >= 1200; // 20 minutes in seconds
    }

    /**
     * Get the content format category based on duration or manual override.
     */
    public function getContentFormat(): string
    {
        // Manual override takes precedence
        if ($this->contentFormatOverride !== null) {
            return $this->contentFormatOverride;
        }
        return $this->isFeatureLength() ? 'feature' : 'short';
    }

    /**
     * Toggle between short and feature format manually.
     */
    public function toggleContentFormat(): void
    {
        $currentFormat = $this->getContentFormat();
        $this->contentFormatOverride = ($currentFormat === 'short') ? 'feature' : 'short';

        // Clear current preset when format changes so user selects appropriate one
        $this->narrativePreset = null;

        // Auto-apply the default preset for the new format
        $mapping = $this->getPresetMappingForProduction();
        if (!empty($mapping['default'])) {
            $this->applyNarrativePreset($mapping['default']);
        }
    }

    /**
     * Set content format explicitly.
     */
    public function setContentFormat(string $format): void
    {
        if (in_array($format, ['short', 'feature'])) {
            $this->contentFormatOverride = $format;

            // Clear current preset and apply new default
            $this->narrativePreset = null;
            $mapping = $this->getPresetMappingForProduction();
            if (!empty($mapping['default'])) {
                $this->applyNarrativePreset($mapping['default']);
            }
        }
    }

    /**
     * Get the preset mapping for current production type/subtype.
     * Returns recommended, compatible presets based on content format (short/feature).
     */
    public function getPresetMappingForProduction(): array
    {
        $mappings = config('appvideowizard.production_preset_mapping', []);
        $format = $this->getContentFormat();

        $emptyMapping = [
            'default' => null,
            'recommended' => [],
            'compatible' => [],
        ];

        if (empty($this->productionType) || !isset($mappings[$this->productionType])) {
            return $emptyMapping;
        }

        $typeMapping = $mappings[$this->productionType];

        // First check for specific subtype mapping, then fall back to _default
        $subtypeMapping = null;
        if (!empty($this->productionSubtype) && isset($typeMapping[$this->productionSubtype])) {
            $subtypeMapping = $typeMapping[$this->productionSubtype];
        } else {
            $subtypeMapping = $typeMapping['_default'] ?? null;
        }

        if (!$subtypeMapping) {
            return $emptyMapping;
        }

        // Get the format-specific mapping (short or feature)
        $formatMapping = $subtypeMapping[$format] ?? null;

        // If no mapping for this format, try the other format as fallback
        if (!$formatMapping) {
            $fallbackFormat = $format === 'feature' ? 'short' : 'feature';
            $formatMapping = $subtypeMapping[$fallbackFormat] ?? null;
        }

        return $formatMapping ?? $emptyMapping;
    }

    /**
     * Get narrative presets organized by recommendation level.
     * Used by the view to display presets with proper hierarchy.
     * Filters presets by content format (short/feature) based on duration.
     */
    public function getOrganizedNarrativePresets(): array
    {
        $allPresets = config('appvideowizard.narrative_presets', []);
        $mapping = $this->getPresetMappingForProduction();
        $format = $this->getContentFormat();

        $recommended = [];
        $compatible = [];
        $other = [];

        foreach ($allPresets as $key => $preset) {
            $presetCategory = $preset['category'] ?? 'short';

            // Filter by content format - only show presets matching current format
            // unless they're in recommended/compatible lists
            $inRecommended = in_array($key, $mapping['recommended'] ?? []);
            $inCompatible = in_array($key, $mapping['compatible'] ?? []);

            if ($inRecommended) {
                $recommended[$key] = $preset;
            } elseif ($inCompatible) {
                $compatible[$key] = $preset;
            } elseif ($presetCategory === $format) {
                // Only show other presets if they match the current format
                $other[$key] = $preset;
            }
        }

        return [
            'recommended' => $recommended,
            'compatible' => $compatible,
            'other' => $other,
            'defaultPreset' => $mapping['default'] ?? null,
            'contentFormat' => $format,
        ];
    }

    /**
     * Update concept.
     */
    #[On('concept-updated')]
    public function updateConcept(array $conceptData): void
    {
        $this->concept = array_merge($this->concept, $conceptData);
        $this->saveProject();
    }

    /**
     * Enhance concept with AI.
     */
    public function enhanceConcept(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'concept-enhance';

        // Dispatch debug event to browser
        $this->dispatch('vw-debug', [
            'action' => 'enhance-concept-start',
            'message' => 'Starting AI concept enhancement',
            'data' => [
                'rawInput' => substr($this->concept['rawInput'] ?? '', 0, 100) . '...',
                'productionType' => $this->productionType,
                'productionSubtype' => $this->productionSubtype,
            ]
        ]);

        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-error',
                'message' => 'No concept input provided',
                'level' => 'warn'
            ]);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'rawInput' => $this->concept['rawInput'],
            'productionType' => $this->productionType,
            'productionSubType' => $this->productionSubtype,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting concept enhancement', $inputData);

            $conceptService = app(ConceptService::class);

            $result = $conceptService->improveConcept($this->concept['rawInput'], [
                'productionType' => $this->productionType,
                'productionSubType' => $this->productionSubtype,
                'teamId' => session('current_team_id', 0),
            ]);

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success to admin panel
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    $result,
                    null, // tokens - not available from ConceptService
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            // Update concept with AI-enhanced data
            $this->concept['refinedConcept'] = $result['improvedConcept'] ?? '';
            $this->concept['logline'] = $result['logline'] ?? '';
            $this->concept['suggestedMood'] = $result['suggestedMood'] ?? null;
            $this->concept['suggestedTone'] = $result['suggestedTone'] ?? null;
            $this->concept['keyElements'] = $result['keyElements'] ?? [];
            $this->concept['targetAudience'] = $result['targetAudience'] ?? '';

            // Also populate avoid elements if AI suggested them
            if (!empty($result['avoidElements']) && is_array($result['avoidElements'])) {
                $this->concept['avoidElements'] = implode(', ', $result['avoidElements']);
            }

            $this->saveProject();

            // Dispatch success debug event
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-success',
                'message' => 'Concept enhanced successfully',
                'data' => [
                    'duration_ms' => $durationMs,
                    'has_refined' => !empty($this->concept['refinedConcept']),
                    'has_logline' => !empty($this->concept['logline']),
                    'mood' => $this->concept['suggestedMood'],
                    'tone' => $this->concept['suggestedTone'],
                ]
            ]);

            $this->dispatch('concept-enhanced');

            Log::info('VideoWizard: Concept enhancement completed', [
                'project_id' => $this->projectId,
                'duration_ms' => $durationMs,
            ]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Log failure to admin panel
            try {
                VwGenerationLog::logFailure(
                    $promptSlug,
                    $inputData,
                    $errorMessage,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            // Dispatch error debug event
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-error',
                'message' => 'Concept enhancement failed: ' . $errorMessage,
                'level' => 'error',
                'data' => [
                    'error' => $errorMessage,
                    'duration_ms' => $durationMs,
                    'trace' => $e->getTraceAsString(),
                ]
            ]);

            Log::error('VideoWizard: Concept enhancement failed', [
                'project_id' => $this->projectId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = __('Failed to enhance concept: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Apply the enhanced concept to the main input.
     * This copies the refined concept to rawInput so the user can edit it further.
     */
    public function applyEnhancedConcept(): void
    {
        if (!empty($this->concept['refinedConcept'])) {
            // Apply the refined concept to the main input
            $this->concept['rawInput'] = $this->concept['refinedConcept'];

            // Keep the enhancement data but mark as applied
            $this->saveProject();

            $this->dispatch('vw-debug', [
                'action' => 'apply-enhancement',
                'message' => 'Enhanced concept applied to input',
            ]);
        }
    }

    /**
     * Dismiss the enhancement preview without applying.
     * This clears the refined concept but keeps the original input.
     */
    public function dismissEnhancement(): void
    {
        // Clear the refined concept to hide the preview
        $this->concept['refinedConcept'] = '';
        $this->concept['logline'] = '';
        $this->concept['suggestedMood'] = null;
        $this->concept['suggestedTone'] = null;
        $this->concept['keyElements'] = [];
        $this->concept['targetAudience'] = '';

        $this->saveProject();

        $this->dispatch('vw-debug', [
            'action' => 'dismiss-enhancement',
            'message' => 'Enhancement preview dismissed',
        ]);
    }

    /**
     * Generate unique ideas based on concept.
     */
    public function generateIdeas(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'concept-ideas';

        $this->dispatch('vw-debug', [
            'action' => 'generate-ideas-start',
            'message' => 'Starting AI idea generation',
            'data' => ['rawInput' => substr($this->concept['rawInput'] ?? '', 0, 100) . '...']
        ]);

        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            $this->dispatch('vw-debug', ['action' => 'generate-ideas-error', 'message' => 'No concept input', 'level' => 'warn']);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'rawInput' => $this->concept['rawInput'],
            'productionType' => $this->productionType,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting idea generation', $inputData);

            $conceptService = app(ConceptService::class);

            // First enhance the concept if not already done
            if (empty($this->concept['refinedConcept'])) {
                $result = $conceptService->improveConcept($this->concept['rawInput'], [
                    'productionType' => $this->productionType,
                    'productionSubType' => $this->productionSubtype,
                    'teamId' => session('current_team_id', 0),
                ]);

                $this->concept['refinedConcept'] = $result['improvedConcept'] ?? '';
                $this->concept['logline'] = $result['logline'] ?? '';
                $this->concept['suggestedMood'] = $result['suggestedMood'] ?? null;
                $this->concept['suggestedTone'] = $result['suggestedTone'] ?? null;
                $this->concept['keyElements'] = $result['keyElements'] ?? [];
                $this->concept['targetAudience'] = $result['targetAudience'] ?? '';
            }

            // Generate concept variations
            $variations = $conceptService->generateVariations(
                $this->concept['refinedConcept'] ?: $this->concept['rawInput'],
                3,
                ['teamId' => session('current_team_id', 0)]
            );

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    ['variations_count' => count($variations)],
                    null,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            $this->conceptVariations = $variations;
            $this->selectedConceptIndex = 0;

            $this->saveProject();

            $this->dispatch('vw-debug', [
                'action' => 'generate-ideas-success',
                'message' => 'Ideas generated successfully',
                'data' => ['variations_count' => count($variations), 'duration_ms' => $durationMs]
            ]);

            Log::info('VideoWizard: Idea generation completed', ['variations' => count($variations), 'duration_ms' => $durationMs]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            try {
                VwGenerationLog::logFailure($promptSlug, $inputData, $errorMessage, $durationMs, $this->projectId, auth()->id(), session('current_team_id'));
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            $this->dispatch('vw-debug', [
                'action' => 'generate-ideas-error',
                'message' => 'Idea generation failed: ' . $errorMessage,
                'level' => 'error',
                'data' => ['error' => $errorMessage, 'duration_ms' => $durationMs]
            ]);

            Log::error('VideoWizard: Idea generation failed', ['error' => $errorMessage]);
            $this->error = __('Failed to generate ideas: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Select a concept variation.
     */
    public function selectConceptVariation(int $index): void
    {
        if (isset($this->conceptVariations[$index])) {
            $this->selectedConceptIndex = $index;

            // Update the refined concept with the selected variation
            $variation = $this->conceptVariations[$index];
            $this->concept['refinedConcept'] = $variation['concept'] ?? $this->concept['refinedConcept'];

            $this->saveProject();
        }
    }

    /**
     * Generate different concepts (re-generate variations).
     */
    public function generateDifferentConcepts(): void
    {
        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $conceptService = app(ConceptService::class);

            // Generate new variations
            $variations = $conceptService->generateVariations(
                $this->concept['rawInput'], // Use original input for fresh variations
                3,
                ['teamId' => session('current_team_id', 0)]
            );

            $this->conceptVariations = $variations;
            $this->selectedConceptIndex = 0;

            // Update refined concept with first variation
            if (!empty($variations[0]['concept'])) {
                $this->concept['refinedConcept'] = $variations[0]['concept'];
            }

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to generate concepts: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate script using AI.
     */
    #[On('generate-script')]
    public function generateScript(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'script-generation';

        // Dispatch debug event to browser
        $this->dispatch('vw-debug', [
            'action' => 'generate-script-start',
            'message' => 'Starting AI script generation',
            'data' => [
                'project_id' => $this->projectId,
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'targetDuration' => $this->targetDuration,
            ]
        ]);

        if (empty($this->concept['rawInput']) && empty($this->concept['refinedConcept'])) {
            $this->error = __('Please complete the concept step first.');
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-error',
                'message' => 'No concept input provided',
                'level' => 'warn'
            ]);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'concept' => substr($this->concept['refinedConcept'] ?: $this->concept['rawInput'], 0, 200),
            'tone' => $this->scriptTone,
            'contentDepth' => $this->contentDepth,
            'targetDuration' => $this->targetDuration,
            'additionalInstructions' => $this->additionalInstructions,
            'narrativePreset' => $this->narrativePreset,
            'storyArc' => $this->storyArc,
            'tensionCurve' => $this->tensionCurve,
            'emotionalJourney' => $this->emotionalJourney,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting script generation', $inputData);

            // Always save project first to ensure database has latest settings (duration, etc.)
            $this->forceSaveProject();

            $project = WizardProject::findOrFail($this->projectId);

            // Log the actual duration that will be used
            Log::info('VideoWizard: Script generation using duration', [
                'component_targetDuration' => $this->targetDuration,
                'project_target_duration' => $project->target_duration,
            ]);
            $scriptService = app(ScriptGenerationService::class);

            $generatedScript = $scriptService->generateScript($project, [
                'teamId' => session('current_team_id', 0),
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'additionalInstructions' => $this->additionalInstructions,
                // Narrative Structure Intelligence
                'narrativePreset' => $this->narrativePreset,
                'storyArc' => $this->storyArc,
                'tensionCurve' => $this->tensionCurve,
                'emotionalJourney' => $this->emotionalJourney,
            ]);

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success to admin panel
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    ['scenes_count' => count($generatedScript['scenes'] ?? [])],
                    null,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            // Update script data
            $this->script = array_merge($this->script, $generatedScript);

            // Sanitize generated script data to prevent type errors in views
            $this->sanitizeScriptData();

            // Auto-detect Character Intelligence from generated script
            $this->autoDetectCharacterIntelligence();

            // Recalculate voice status based on new script
            $this->recalculateVoiceStatus();

            $this->saveProject();

            // Dispatch success debug event
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-success',
                'message' => 'Script generated successfully',
                'data' => [
                    'duration_ms' => $durationMs,
                    'scenes_count' => count($this->script['scenes'] ?? []),
                ]
            ]);

            $this->dispatch('script-generated');

            Log::info('VideoWizard: Script generation completed', [
                'project_id' => $this->projectId,
                'duration_ms' => $durationMs,
                'scenes_count' => count($this->script['scenes'] ?? []),
            ]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Log failure to admin panel
            try {
                VwGenerationLog::logFailure(
                    $promptSlug,
                    $inputData,
                    $errorMessage,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            // Dispatch error debug event
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-error',
                'message' => 'Script generation failed: ' . $errorMessage,
                'level' => 'error',
                'data' => [
                    'error' => $errorMessage,
                    'duration_ms' => $durationMs,
                    'trace' => $e->getTraceAsString(),
                ]
            ]);

            Log::error('VideoWizard: Script generation failed', [
                'project_id' => $this->projectId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = __('Failed to generate script: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // PROGRESSIVE SCRIPT GENERATION (Batch-Based)
    // =========================================================================

    /**
     * Calculate exact scene count based on duration and production type.
     */
    public function calculateSceneCount(): int
    {
        $targetDuration = $this->targetDuration ?? 60;
        $productionType = $this->production['type'] ?? 'standard';

        // Scene duration based on production type
        $sceneDurations = [
            'tiktok-viral' => 3,      // Fast cuts, 3s per scene
            'youtube-short' => 5,     // Medium pace, 5s per scene
            'short-form' => 4,        // Short form content
            'standard' => 6,          // Standard pace, 6s per scene
            'cinematic' => 8,         // Slower, cinematic, 8s per scene
            'documentary' => 10,      // Documentary style, 10s per scene
            'long-form' => 8,         // Long form content
        ];

        $sceneDuration = $sceneDurations[$productionType] ?? 6;

        return (int) ceil($targetDuration / $sceneDuration);
    }

    /**
     * Start progressive script generation.
     * If scenes exist, shows confirmation modal first.
     */
    #[On('start-progressive-generation')]
    public function startProgressiveGeneration(): void
    {
        if (empty($this->concept['rawInput']) && empty($this->concept['refinedConcept'])) {
            $this->error = __('Please complete the concept step first.');
            return;
        }

        // Check if scenes already exist - show confirmation modal
        $existingSceneCount = count($this->script['scenes'] ?? []);
        if ($existingSceneCount > 0) {
            $this->showSceneOverwriteModal = true;
            return;
        }

        // No existing scenes - proceed directly
        $this->executeProgressiveGeneration('replace');
    }

    /**
     * Handle scene overwrite confirmation.
     */
    public function confirmSceneOverwrite(string $action): void
    {
        $this->showSceneOverwriteModal = false;
        $this->sceneOverwriteAction = $action;

        if ($action === 'cancel') {
            return;
        }

        $this->executeProgressiveGeneration($action);
    }

    /**
     * Execute the progressive generation with specified action.
     * @param string $action 'replace' to start fresh, 'append' to add to existing scenes
     */
    protected function executeProgressiveGeneration(string $action): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $targetSceneCount = $this->calculateSceneCount();
            $batchSize = 5;

            // If appending, adjust target count
            $existingSceneCount = 0;
            if ($action === 'append') {
                $existingSceneCount = count($this->script['scenes'] ?? []);
                // Calculate how many more scenes we need
                $remainingScenes = max(0, $targetSceneCount - $existingSceneCount);
                if ($remainingScenes === 0) {
                    $this->error = __('You already have :count scenes. Target is :target scenes.', [
                        'count' => $existingSceneCount,
                        'target' => $targetSceneCount,
                    ]);
                    $this->isLoading = false;
                    return;
                }
                $targetSceneCount = $remainingScenes;
            }

            $totalBatches = (int) ceil($targetSceneCount / $batchSize);

            // Initialize batch tracking
            $batches = [];
            for ($i = 0; $i < $totalBatches; $i++) {
                $startScene = $existingSceneCount + ($i * $batchSize) + 1;
                $endScene = $existingSceneCount + min(($i + 1) * $batchSize, $targetSceneCount);

                $batches[] = [
                    'batchNumber' => $i + 1,
                    'startScene' => $startScene,
                    'endScene' => $endScene,
                    'status' => 'pending',
                    'generatedAt' => null,
                    'sceneIds' => [],
                    'retryCount' => 0,
                    'lastError' => null,
                ];
            }

            $this->scriptGeneration = [
                'status' => 'generating',
                'targetSceneCount' => $existingSceneCount + $targetSceneCount,
                'generatedSceneCount' => $existingSceneCount,
                'batchSize' => $batchSize,
                'currentBatch' => 0,
                'totalBatches' => $totalBatches,
                'batches' => $batches,
                'autoGenerate' => false,
            ];

            // Initialize or keep script structure based on action
            if ($action === 'replace') {
                $this->script = [
                    'title' => $this->concept['refinedConcept'] ?? $this->concept['rawInput'] ?? 'Untitled',
                    'hook' => '',
                    'scenes' => [],
                    'cta' => '',
                    'totalDuration' => 0,
                    'totalNarrationTime' => 0,
                ];
            }
            // If appending, keep existing script structure

            $this->saveProject();

            // Dispatch event for UI update
            $this->dispatch('progressive-generation-started', [
                'targetSceneCount' => $this->scriptGeneration['targetSceneCount'],
                'totalBatches' => $totalBatches,
                'action' => $action,
            ]);

            // Generate first batch
            $this->generateNextBatch();

        } catch (\Exception $e) {
            $this->error = __('Failed to start generation: ') . $e->getMessage();
            $this->scriptGeneration['status'] = 'idle';
            Log::error('VideoWizard: Progressive generation start failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate the next batch of scenes.
     */
    #[On('generate-next-batch')]
    public function generateNextBatch(): void
    {
        $currentBatchIndex = $this->scriptGeneration['currentBatch'];

        if ($currentBatchIndex >= $this->scriptGeneration['totalBatches']) {
            $this->scriptGeneration['status'] = 'complete';
            $this->dispatch('progressive-generation-complete');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        // Update batch status
        $this->scriptGeneration['batches'][$currentBatchIndex]['status'] = 'generating';
        $this->scriptGeneration['status'] = 'generating';

        try {
            $batch = $this->scriptGeneration['batches'][$currentBatchIndex];
            $scriptService = app(ScriptGenerationService::class);
            $project = WizardProject::findOrFail($this->projectId);

            // Build context from existing scenes
            $context = $scriptService->buildBatchContext(
                $this->script['scenes'] ?? [],
                $batch['batchNumber'],
                $this->scriptGeneration['totalBatches']
            );

            // Generate this batch
            $result = $scriptService->generateSceneBatch(
                $project,
                $batch['startScene'],
                $batch['endScene'],
                $this->scriptGeneration['targetSceneCount'],
                $context,
                [
                    'topic' => $this->concept['refinedConcept'] ?? $this->concept['rawInput'] ?? '',
                    'tone' => $this->scriptTone ?? 'engaging',
                    'contentDepth' => $this->contentDepth ?? 'detailed',
                    'productionType' => $this->production['type'] ?? 'standard',
                    'teamId' => session('current_team_id', 0),
                    'narrativePreset' => $this->narrativePreset,
                    'emotionalJourney' => $this->emotionalJourney,
                ]
            );

            if ($result['success'] && !empty($result['scenes'])) {
                // Sanitize and append new scenes
                foreach ($result['scenes'] as $index => $scene) {
                    $sceneIndex = count($this->script['scenes']);
                    $this->script['scenes'][] = $this->sanitizeScene($scene, $sceneIndex);
                }

                // Update batch status
                $this->scriptGeneration['batches'][$currentBatchIndex]['status'] = 'complete';
                $this->scriptGeneration['batches'][$currentBatchIndex]['generatedAt'] = now()->toDateTimeString();
                $this->scriptGeneration['batches'][$currentBatchIndex]['sceneIds'] = array_column($result['scenes'], 'id');

                // Update counts
                $this->scriptGeneration['generatedSceneCount'] = count($this->script['scenes']);
                $this->scriptGeneration['currentBatch']++;

                // Recalculate totals
                $this->recalculateScriptTotals();
                $this->recalculateVoiceStatus();

                // Check if complete
                if ($this->scriptGeneration['currentBatch'] >= $this->scriptGeneration['totalBatches']) {
                    $this->scriptGeneration['status'] = 'complete';
                    $this->autoDetectCharacterIntelligence();
                    $this->dispatch('progressive-generation-complete');
                    $this->dispatch('script-generated');
                } else {
                    $this->scriptGeneration['status'] = 'paused';

                    // Dispatch batch complete event
                    $this->dispatch('batch-generated', [
                        'batchNumber' => $batch['batchNumber'],
                        'scenesGenerated' => count($result['scenes']),
                        'totalGenerated' => $this->scriptGeneration['generatedSceneCount'],
                    ]);

                    // Auto-continue if enabled
                    if ($this->scriptGeneration['autoGenerate']) {
                        $this->generateNextBatch();
                        return;
                    }
                }

                $this->saveProject();

            } else {
                $errorMessage = $result['error'] ?? __('Failed to generate batch');
                $this->handleBatchError($currentBatchIndex, $errorMessage);
            }

        } catch (\Exception $e) {
            $this->handleBatchError($currentBatchIndex, $e->getMessage());
        } finally {
            $this->isLoading = false;
            $this->saveProject();
        }
    }

    /**
     * Handle batch generation error with exponential backoff retry.
     */
    protected function handleBatchError(int $batchIndex, string $errorMessage): void
    {
        $batch = &$this->scriptGeneration['batches'][$batchIndex];
        $batch['retryCount'] = ($batch['retryCount'] ?? 0) + 1;
        $batch['lastError'] = $errorMessage;
        $maxRetries = $this->scriptGeneration['maxRetries'] ?? 3;

        Log::warning('VideoWizard: Batch generation failed', [
            'batch' => $batch['batchNumber'],
            'retryCount' => $batch['retryCount'],
            'maxRetries' => $maxRetries,
            'error' => $errorMessage,
        ]);

        if ($batch['retryCount'] < $maxRetries) {
            // Calculate exponential backoff delay: 1s, 2s, 4s
            $delayMs = ($this->scriptGeneration['retryDelayMs'] ?? 1000) * pow(2, $batch['retryCount'] - 1);

            $batch['status'] = 'retrying';
            $this->error = __('Batch :num failed, retrying in :sec seconds... (Attempt :attempt/:max)', [
                'num' => $batch['batchNumber'],
                'sec' => $delayMs / 1000,
                'attempt' => $batch['retryCount'] + 1,
                'max' => $maxRetries,
            ]);

            // Dispatch delayed retry event
            $this->dispatch('retry-batch-delayed', [
                'batchIndex' => $batchIndex,
                'delayMs' => $delayMs,
            ]);

        } else {
            // Max retries exceeded - mark as failed
            $batch['status'] = 'error';
            $this->scriptGeneration['status'] = 'paused';
            $this->error = __('Batch :num failed after :max attempts: :error', [
                'num' => $batch['batchNumber'],
                'max' => $maxRetries,
                'error' => $errorMessage,
            ]);

            Log::error('VideoWizard: Batch generation failed permanently', [
                'batch' => $batch['batchNumber'],
                'error' => $errorMessage,
            ]);
        }
    }

    /**
     * Execute delayed retry for a batch (called from JS after delay).
     */
    #[On('execute-delayed-retry')]
    public function executeDelayedRetry(int $batchIndex): void
    {
        if (!isset($this->scriptGeneration['batches'][$batchIndex])) {
            return;
        }

        $batch = $this->scriptGeneration['batches'][$batchIndex];
        if ($batch['status'] !== 'retrying') {
            return;
        }

        // Reset to pending and retry
        $this->scriptGeneration['batches'][$batchIndex]['status'] = 'pending';
        $this->scriptGeneration['currentBatch'] = $batchIndex;
        $this->error = null;

        $this->generateNextBatch();
    }

    /**
     * Auto-generate all remaining batches.
     */
    #[On('generate-all-remaining')]
    public function generateAllRemaining(): void
    {
        $this->scriptGeneration['autoGenerate'] = true;
        $this->generateNextBatch();
    }

    /**
     * Retry failed batch.
     */
    public function retryBatch(int $batchIndex): void
    {
        if (!isset($this->scriptGeneration['batches'][$batchIndex])) {
            return;
        }

        // Reset batch status
        $this->scriptGeneration['batches'][$batchIndex]['status'] = 'pending';
        $this->scriptGeneration['currentBatch'] = $batchIndex;
        $this->scriptGeneration['status'] = 'paused';

        // Generate the batch
        $this->generateNextBatch();
    }

    /**
     * Reset progressive generation.
     */
    public function resetProgressiveGeneration(): void
    {
        $this->scriptGeneration = [
            'status' => 'idle',
            'targetSceneCount' => 0,
            'generatedSceneCount' => 0,
            'batchSize' => 5,
            'currentBatch' => 0,
            'totalBatches' => 0,
            'batches' => [],
            'autoGenerate' => false,
        ];

        $this->script = [
            'title' => '',
            'hook' => '',
            'scenes' => [],
            'cta' => '',
            'totalDuration' => 0,
            'totalNarrationTime' => 0,
        ];

        $this->saveProject();
    }

    /**
     * Apply narrative preset defaults.
     * When a preset is selected, auto-set story structure, tension curve, and emotional journey.
     */
    public function applyNarrativePreset(string $preset): void
    {
        $this->narrativePreset = $preset;

        $presets = config('appvideowizard.narrative_presets', []);

        if (!isset($presets[$preset])) {
            return;
        }

        $presetConfig = $presets[$preset];

        // Auto-set story arc/structure if preset defines one
        // Support both 'defaultStructure' (new) and 'defaultArc' (legacy)
        if (!empty($presetConfig['defaultStructure'])) {
            $this->storyArc = $presetConfig['defaultStructure'];
        } elseif (!empty($presetConfig['defaultArc'])) {
            $this->storyArc = $presetConfig['defaultArc'];
        }

        // Auto-set tension curve if preset defines one
        if (!empty($presetConfig['defaultTension'])) {
            $this->tensionCurve = $presetConfig['defaultTension'];
        }

        // Auto-set emotional journey if preset defines one
        if (!empty($presetConfig['defaultEmotion'])) {
            $this->emotionalJourney = $presetConfig['defaultEmotion'];
        }

        // Show advanced options when preset is selected
        $this->showNarrativeAdvanced = true;
    }

    /**
     * Clear narrative structure selections.
     */
    public function clearNarrativeSettings(): void
    {
        $this->narrativePreset = null;
        $this->storyArc = null;
        $this->tensionCurve = null;
        $this->emotionalJourney = null;
        $this->showNarrativeAdvanced = false;
    }

    /**
     * Update script.
     */
    #[On('script-updated')]
    public function updateScript(array $scriptData): void
    {
        $this->script = array_merge($this->script, $scriptData);
        $this->saveProject();
    }

    /**
     * Toggle "Music only" (no voiceover) for a scene.
     */
    public function toggleSceneMusicOnly(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $currentValue = $this->script['scenes'][$sceneIndex]['voiceover']['enabled'] ?? true;
        $this->script['scenes'][$sceneIndex]['voiceover']['enabled'] = !$currentValue;

        $this->recalculateVoiceStatus();
        $this->saveProject();
    }

    /**
     * Update scene duration.
     */
    public function updateSceneDuration(int $sceneIndex, int $duration): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['duration'] = max(1, min(300, $duration));
        $this->recalculateScriptTotals();
        $this->saveProject();
    }

    /**
     * Update scene transition.
     */
    public function updateSceneTransition(int $sceneIndex, string $transition): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $validTransitions = array_keys(config('appvideowizard.transitions', []));
        if (!in_array($transition, $validTransitions)) {
            $transition = 'cut';
        }

        $this->script['scenes'][$sceneIndex]['transition'] = $transition;
        $this->saveProject();
    }

    /**
     * Update scene visual prompt.
     */
    public function updateSceneVisualPrompt(int $sceneIndex, string $prompt): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['visualPrompt'] = $prompt;
        $this->saveProject();
    }

    /**
     * Update scene narration text.
     */
    public function updateSceneNarration(int $sceneIndex, string $narration): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['narration'] = $narration;
        $this->recalculateScriptTotals();
        $this->saveProject();
    }

    /**
     * Update scene voiceover text.
     */
    public function updateSceneVoiceover(int $sceneIndex, string $text): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        if (!isset($this->script['scenes'][$sceneIndex]['voiceover'])) {
            $this->script['scenes'][$sceneIndex]['voiceover'] = [
                'enabled' => true,
                'text' => '',
                'voiceId' => null,
                'status' => 'pending',
            ];
        }

        $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $text;
        $this->recalculateVoiceStatus();
        $this->saveProject();
    }

    /**
     * Regenerate a single scene.
     */
    public function regenerateScene(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $project = WizardProject::findOrFail($this->projectId);
            $scriptService = app(ScriptGenerationService::class);

            $regeneratedScene = $scriptService->regenerateScene($project, $sceneIndex, [
                'teamId' => session('current_team_id', 0),
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'existingScene' => $this->script['scenes'][$sceneIndex],
            ]);

            if ($regeneratedScene) {
                // Preserve certain fields from the original scene
                $regeneratedScene['id'] = $this->script['scenes'][$sceneIndex]['id'];
                $regeneratedScene['transition'] = $this->script['scenes'][$sceneIndex]['transition'] ?? 'cut';

                // Sanitize the regenerated scene to ensure proper data types
                $this->script['scenes'][$sceneIndex] = $this->sanitizeScene($regeneratedScene, $sceneIndex);
                $this->recalculateScriptTotals();
                $this->recalculateVoiceStatus();
                $this->saveProject();

                $this->dispatch('scene-regenerated', ['sceneIndex' => $sceneIndex]);
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to regenerate scene: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Reorder a scene (move up or down).
     */
    public function reorderScene(int $sceneIndex, string $direction): void
    {
        $scenes = $this->script['scenes'] ?? [];
        $sceneCount = count($scenes);

        if ($sceneIndex < 0 || $sceneIndex >= $sceneCount) {
            return;
        }

        $newIndex = $direction === 'up' ? $sceneIndex - 1 : $sceneIndex + 1;

        if ($newIndex < 0 || $newIndex >= $sceneCount) {
            return;
        }

        // Swap scenes
        $temp = $scenes[$sceneIndex];
        $scenes[$sceneIndex] = $scenes[$newIndex];
        $scenes[$newIndex] = $temp;

        // Reindex array
        $this->script['scenes'] = array_values($scenes);
        $this->saveProject();

        $this->dispatch('scenes-reordered');
    }

    /**
     * Delete a scene.
     */
    public function deleteScene(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        // Don't allow deleting the last scene
        if (count($this->script['scenes']) <= 1) {
            $this->error = __('Cannot delete the last scene.');
            return;
        }

        // Remove the scene
        array_splice($this->script['scenes'], $sceneIndex, 1);

        // Also remove corresponding storyboard scene if exists
        if (isset($this->storyboard['scenes'][$sceneIndex])) {
            array_splice($this->storyboard['scenes'], $sceneIndex, 1);
        }

        $this->recalculateScriptTotals();
        $this->recalculateVoiceStatus();
        $this->saveProject();

        $this->dispatch('scene-deleted', ['sceneIndex' => $sceneIndex]);
    }

    /**
     * Add a new scene.
     */
    public function addScene(): void
    {
        $sceneCount = count($this->script['scenes'] ?? []);
        $newSceneId = 'scene_' . ($sceneCount + 1) . '_' . time();

        // Create new scene with sanitized structure
        $newScene = $this->sanitizeScene([
            'id' => $newSceneId,
            'title' => __('Scene') . ' ' . ($sceneCount + 1),
            'narration' => '',
            'visualDescription' => '',
            'visualPrompt' => '',
            'duration' => 15,
            'transition' => 'cut',
            'mood' => 'neutral',
            'status' => 'draft',
        ], $sceneCount);

        $this->script['scenes'][] = $newScene;
        $this->recalculateScriptTotals();
        $this->recalculateVoiceStatus();
        $this->saveProject();

        $this->dispatch('scene-added', ['sceneIndex' => $sceneCount]);
    }

    /**
     * Generate visual prompt for a scene using AI.
     */
    public function generateVisualPrompt(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $scene = $this->script['scenes'][$sceneIndex];
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            $this->error = __('Scene has no narration to generate visual prompt from.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $scriptService = app(ScriptGenerationService::class);

            $visualPrompt = $scriptService->generateVisualPromptForScene(
                $narration,
                $this->concept,
                [
                    'mood' => $scene['mood'] ?? $this->concept['suggestedMood'] ?? 'cinematic',
                    'style' => $this->concept['styleReference'] ?? '',
                    'productionType' => $this->productionType,
                    'aspectRatio' => $this->aspectRatio,
                ]
            );

            $this->script['scenes'][$sceneIndex]['visualPrompt'] = $visualPrompt;
            $this->saveProject();

            $this->dispatch('visual-prompt-generated', ['sceneIndex' => $sceneIndex]);

        } catch (\Exception $e) {
            $this->error = __('Failed to generate visual prompt: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate voiceover text for a scene using AI.
     */
    public function generateVoiceoverText(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $scene = $this->script['scenes'][$sceneIndex];
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            // Use narration as voiceover text if no separate voiceover needed
            $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $narration;
            $this->saveProject();
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $scriptService = app(ScriptGenerationService::class);

            $voiceoverText = $scriptService->generateVoiceoverForScene(
                $narration,
                $this->concept,
                [
                    'narrationStyle' => $this->characterIntelligence['narrationStyle'] ?? 'voiceover',
                    'tone' => $this->scriptTone,
                ]
            );

            $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $voiceoverText;
            $this->recalculateVoiceStatus();
            $this->saveProject();

            $this->dispatch('voiceover-text-generated', ['sceneIndex' => $sceneIndex]);

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceover text: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Recalculate script totals (duration, narration time).
     */
    protected function recalculateScriptTotals(): void
    {
        $totalDuration = 0;
        $totalNarrationTime = 0;

        foreach ($this->script['scenes'] ?? [] as $scene) {
            $totalDuration += $scene['duration'] ?? 15;

            // Estimate narration time based on word count (150 words/minute)
            $narration = $scene['narration'] ?? '';
            $wordCount = str_word_count($narration);
            $totalNarrationTime += ($wordCount / 150) * 60;
        }

        $this->script['totalDuration'] = $totalDuration;
        $this->script['totalNarrationTime'] = round($totalNarrationTime, 1);
    }

    /**
     * Sanitize script data to ensure all fields are properly typed.
     * This prevents htmlspecialchars errors when rendering in Blade views.
     * Should be called after loading script data from database or generating new script.
     */
    protected function sanitizeScriptData(): void
    {
        if (empty($this->script['scenes'])) {
            return;
        }

        // Sanitize top-level script fields
        $this->script['title'] = $this->ensureString($this->script['title'] ?? null, 'Untitled Script');
        $this->script['hook'] = $this->ensureString($this->script['hook'] ?? null, '');
        $this->script['cta'] = $this->ensureString($this->script['cta'] ?? null, '');

        // Sanitize each scene
        foreach ($this->script['scenes'] as $index => &$scene) {
            $scene = $this->sanitizeScene($scene, $index);
        }
    }

    /**
     * Sanitize a single scene to ensure all fields are properly typed.
     */
    protected function sanitizeScene(array $scene, int $index = 0): array
    {
        return [
            // Core identifiers
            'id' => $this->ensureString($scene['id'] ?? null, 'scene-' . ($index + 1)),
            'title' => $this->ensureString($scene['title'] ?? null, 'Scene ' . ($index + 1)),

            // Text content - must be strings
            'narration' => $this->ensureString($scene['narration'] ?? null, ''),
            'visualDescription' => $this->ensureString(
                $scene['visualDescription'] ?? $scene['visual_description'] ?? $scene['visual'] ?? null,
                ''
            ),
            'visualPrompt' => $this->ensureString($scene['visualPrompt'] ?? null, ''),

            // Metadata - must be strings
            'mood' => $this->ensureString($scene['mood'] ?? null, ''),
            'transition' => $this->ensureString($scene['transition'] ?? null, 'cut'),
            'status' => $this->ensureString($scene['status'] ?? null, 'draft'),

            // Duration - must be numeric
            'duration' => $this->ensureNumeric($scene['duration'] ?? null, 15, 5, 300),

            // Voiceover structure
            'voiceover' => $this->sanitizeVoiceover($scene['voiceover'] ?? []),

            // Ken Burns effect (preserve if valid, otherwise generate)
            'kenBurns' => is_array($scene['kenBurns'] ?? null) ? $scene['kenBurns'] : [
                'startScale' => 1.0, 'endScale' => 1.15,
                'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.5, 'endY' => 0.5
            ],

            // Image data (preserve as-is if exists)
            'image' => $scene['image'] ?? null,
            'imageUrl' => $this->ensureString($scene['imageUrl'] ?? null, ''),
        ];
    }

    /**
     * Sanitize voiceover structure.
     */
    protected function sanitizeVoiceover($voiceover): array
    {
        if (!is_array($voiceover)) {
            $voiceover = [];
        }

        return [
            'enabled' => (bool)($voiceover['enabled'] ?? true),
            'text' => $this->ensureString($voiceover['text'] ?? null, ''),
            'voiceId' => $voiceover['voiceId'] ?? null,
            'status' => $this->ensureString($voiceover['status'] ?? null, 'pending'),
        ];
    }

    /**
     * Ensure a value is a string. If it's an array, recursively extract first string.
     * Handles nested arrays like [['value']] that AI sometimes returns.
     */
    protected function ensureString($value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        // Handle arrays - recursively extract first string value
        if (is_array($value)) {
            foreach ($value as $item) {
                $result = $this->ensureString($item, '');
                if ($result !== '') {
                    return $result;
                }
            }
        }
        return $default;
    }

    /**
     * Ensure a value is numeric within bounds.
     */
    protected function ensureNumeric($value, int $default, int $min = 0, int $max = PHP_INT_MAX): int
    {
        if (is_numeric($value)) {
            return max($min, min($max, (int)$value));
        }
        return $default;
    }

    /**
     * Recalculate voice status from script scenes.
     */
    protected function recalculateVoiceStatus(): void
    {
        $dialogueLines = 0;
        $speakers = [];
        $voicesMapped = 0;
        $scenesWithDialogue = 0;
        $scenesWithVoiceover = 0;
        $pendingVoices = 0;

        foreach ($this->script['scenes'] ?? [] as $scene) {
            $voiceover = $scene['voiceover'] ?? [];

            if ($voiceover['enabled'] ?? true) {
                $scenesWithVoiceover++;

                if (!empty($voiceover['text'])) {
                    $dialogueLines++;

                    if (!empty($voiceover['voiceId'])) {
                        $voicesMapped++;
                    } else {
                        $pendingVoices++;
                    }
                } else {
                    $pendingVoices++;
                }
            }

            // Count speakers from dialogue (if narrationStyle is dialogue)
            if ($this->characterIntelligence['narrationStyle'] === 'dialogue') {
                // Extract speaker names from narration (format: "SPEAKER: text")
                $narration = $scene['narration'] ?? '';
                if (preg_match_all('/^([A-Z][A-Z\s]+):/m', $narration, $matches)) {
                    foreach ($matches[1] as $speaker) {
                        $speakers[trim($speaker)] = true;
                        $dialogueLines++;
                    }
                    $scenesWithDialogue++;
                }
            }
        }

        $this->voiceStatus = [
            'dialogueLines' => $dialogueLines,
            'speakers' => count($speakers),
            'voicesMapped' => $voicesMapped,
            'scenesWithDialogue' => $scenesWithDialogue,
            'scenesWithVoiceover' => $scenesWithVoiceover,
            'pendingVoices' => $pendingVoices,
        ];
    }

    /**
     * Auto-detect Character Intelligence from generated script.
     * Analyzes the script to automatically set narration style, character count, etc.
     */
    protected function autoDetectCharacterIntelligence(): void
    {
        try {
            $characterService = app(CharacterExtractionService::class);

            $detection = $characterService->autoDetectCharacterIntelligence(
                $this->script,
                ['productionType' => $this->productionSubtype ?? $this->productionType]
            );

            // Update Character Intelligence with detected values
            $this->characterIntelligence['narrationStyle'] = $detection['narrationStyle'];
            $this->characterIntelligence['characterCount'] = $detection['characterCount'];
            $this->characterIntelligence['suggestedCount'] = $detection['suggestedCount'];

            // Store detection metadata for UI display
            $this->characterIntelligence['autoDetected'] = true;
            $this->characterIntelligence['detectionConfidence'] = $detection['detectionConfidence'];
            $this->characterIntelligence['hasDialogue'] = $detection['hasDialogue'];
            $this->characterIntelligence['detectedSpeakers'] = $detection['detectedSpeakers'] ?? [];

            Log::info('VideoWizard: Character Intelligence auto-detected', [
                'project_id' => $this->projectId,
                'narrationStyle' => $detection['narrationStyle'],
                'characterCount' => $detection['characterCount'],
                'confidence' => $detection['detectionConfidence'],
            ]);

            // Dispatch event for UI notification
            $this->dispatch('vw-debug', [
                'action' => 'character-intelligence-detected',
                'message' => "Auto-detected: {$detection['narrationStyle']} style ({$detection['detectionConfidence']} confidence)",
                'data' => $detection,
            ]);

        } catch (\Exception $e) {
            Log::warning('VideoWizard: Character Intelligence auto-detection failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Character Intelligence settings.
     */
    public function updateCharacterIntelligence(string $field, $value): void
    {
        if (in_array($field, ['enabled', 'narrationStyle', 'characterCount'])) {
            // Cast values to proper types
            if ($field === 'enabled') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($field === 'characterCount') {
                $value = (int) $value;
            }

            $this->characterIntelligence[$field] = $value;

            // Recalculate suggested character count based on production type
            if ($field === 'narrationStyle' || $field === 'enabled') {
                $this->characterIntelligence['suggestedCount'] = $this->calculateSuggestedCharacterCount();
            }

            $this->saveProject();
        }
    }

    /**
     * Calculate suggested character count based on production type and narration style.
     */
    protected function calculateSuggestedCharacterCount(): int
    {
        $narrationStyle = $this->characterIntelligence['narrationStyle'] ?? 'voiceover';

        // No characters needed for voiceover or none
        if (in_array($narrationStyle, ['voiceover', 'narrator', 'none'])) {
            return 0;
        }

        // For dialogue, suggest based on production type
        $productionType = $this->productionType ?? 'social';

        return match ($productionType) {
            'movie' => 4,
            'series' => 5,
            'commercial' => 2,
            'educational' => 1,
            default => 2,
        };
    }

    /**
     * Update storyboard.
     */
    #[On('storyboard-updated')]
    public function updateStoryboard(array $storyboardData): void
    {
        $this->storyboard = array_merge($this->storyboard, $storyboardData);
        $this->saveProject();
    }

    /**
     * Generate image for a single scene.
     */
    #[On('generate-image')]
    public function generateImage(int $sceneIndex, string $sceneId): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            // Initialize scenes array if needed
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }

            // Set generating status BEFORE the API call for immediate UI feedback
            $this->storyboard['scenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'imageUrl' => null,
                'assetId' => null,
                'status' => 'generating',
                'source' => 'ai',
            ];

            // Force save and UI update before the potentially slow API call
            $this->saveProject();

            $imageService = app(ImageGenerationService::class);
            $result = $imageService->generateSceneImage($project, $scene, [
                'sceneIndex' => $sceneIndex,
                'teamId' => session('current_team_id', 0),
                'model' => $this->storyboard['imageModel'] ?? 'nanobanana', // Use UI-selected model
            ]);

            if ($result['async'] ?? false) {
                // HiDream async job - update with job ID for polling
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = $result['jobId'] ?? null;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = $result['processingJobId'] ?? null;

                $this->saveProject();

                // Dispatch event to start polling
                $this->dispatch('image-generation-started', [
                    'sceneIndex' => $sceneIndex,
                    'async' => true,
                ]);
            } else {
                // Sync generation - image is ready
                $this->storyboard['scenes'][$sceneIndex] = [
                    'sceneId' => $sceneId,
                    'imageUrl' => $result['imageUrl'],
                    'assetId' => $result['assetId'] ?? null,
                    'source' => 'ai',
                    'status' => 'ready',
                ];

                $this->saveProject();
            }

        } catch (\Exception $e) {
            // Set error status on failure
            if (isset($this->storyboard['scenes'][$sceneIndex])) {
                $this->storyboard['scenes'][$sceneIndex]['status'] = 'error';
                $this->storyboard['scenes'][$sceneIndex]['error'] = $e->getMessage();
                $this->saveProject();
            }
            $this->error = __('Failed to generate image: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate images for all scenes.
     */
    #[On('generate-all-images')]
    public function generateAllImages(): void
    {
        $this->isLoading = true;
        $this->error = null;
        $hasAsyncJobs = false;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $imageService = app(ImageGenerationService::class);

            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }

            // First pass: Set all pending scenes to 'generating' status for immediate UI feedback
            $scenesToGenerate = [];
            foreach ($this->script['scenes'] as $index => $scene) {
                $existingScene = $this->storyboard['scenes'][$index] ?? null;
                if (empty($existingScene['imageUrl']) && ($existingScene['status'] ?? '') !== 'generating') {
                    $this->storyboard['scenes'][$index] = [
                        'sceneId' => $scene['id'],
                        'imageUrl' => null,
                        'assetId' => null,
                        'status' => 'generating',
                        'source' => 'ai',
                    ];
                    $scenesToGenerate[] = $index;
                }
            }

            // Save with all 'generating' statuses for immediate UI update
            if (!empty($scenesToGenerate)) {
                $this->saveProject();
            }

            // Second pass: Actually generate images
            foreach ($scenesToGenerate as $index) {
                $scene = $this->script['scenes'][$index];

                try {
                    $result = $imageService->generateSceneImage($project, $scene, [
                        'sceneIndex' => $index,
                        'teamId' => session('current_team_id', 0),
                        'model' => $this->storyboard['imageModel'] ?? 'nanobanana',
                    ]);

                    if ($result['async'] ?? false) {
                        // HiDream async job - update with job ID
                        $this->storyboard['scenes'][$index]['jobId'] = $result['jobId'] ?? null;
                        $this->storyboard['scenes'][$index]['processingJobId'] = $result['processingJobId'] ?? null;
                        $hasAsyncJobs = true;
                    } else {
                        // Sync generation - image is ready
                        $this->storyboard['scenes'][$index] = [
                            'sceneId' => $scene['id'],
                            'imageUrl' => $result['imageUrl'],
                            'assetId' => $result['assetId'] ?? null,
                            'source' => 'ai',
                            'status' => 'ready',
                        ];
                    }

                    $this->saveProject();

                } catch (\Exception $e) {
                    // Set error status on failure
                    $this->storyboard['scenes'][$index]['status'] = 'error';
                    $this->storyboard['scenes'][$index]['error'] = $e->getMessage();
                    $this->saveProject();
                    Log::warning("Failed to generate image for scene {$index}: " . $e->getMessage());
                }
            }

            // Dispatch polling start if we have async jobs
            if ($hasAsyncJobs) {
                $this->dispatch('image-generation-started', [
                    'async' => true,
                    'sceneIndex' => -1, // Indicates batch
                ]);
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to generate images: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Regenerate image for a scene.
     */
    #[On('regenerate-image')]
    public function regenerateImage(int $sceneIndex): void
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            $this->generateImage($sceneIndex, $scene['id']);
        }
    }

    /**
     * Generate AI-powered stock search suggestions based on scene content.
     */
    public function generateStockSuggestions(int $sceneIndex): array
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if (!$scene) {
            return ['primaryQuery' => '', 'alternatives' => []];
        }

        $visual = $scene['visual'] ?? '';
        $narration = $scene['narration'] ?? '';
        $combined = trim($visual . ' ' . $narration);

        if (empty($combined)) {
            return ['primaryQuery' => '', 'alternatives' => []];
        }

        // Extract keywords from scene description
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'to', 'of',
            'in', 'for', 'on', 'with', 'at', 'by', 'this', 'that', 'it', 'and', 'or',
            'but', 'if', 'then', 'else', 'when', 'where', 'why', 'how', 'all', 'each',
            'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no',
            'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just',
            'show', 'showing', 'shows', 'scene', 'shot', 'shots', 'frame', 'frames'];

        // Clean and extract meaningful words
        $words = preg_split('/\s+/', strtolower($combined));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = preg_replace('/[^a-z]/', '', $word);
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        $keywords = array_values(array_unique($keywords));
        $primaryWords = array_slice($keywords, 0, 3);
        $primaryQuery = implode(' ', $primaryWords);

        // Generate alternative queries
        $alternatives = [];
        if (count($keywords) > 3) {
            $alternatives[] = implode(' ', array_slice($keywords, 1, 3));
        }
        if (count($keywords) > 4) {
            $alternatives[] = implode(' ', array_slice($keywords, 2, 3));
        }

        // Add context-based alternatives
        $contextKeywords = [
            'office' => ['business office', 'corporate workspace', 'professional meeting'],
            'nature' => ['natural landscape', 'outdoor scenery', 'forest trees'],
            'technology' => ['tech devices', 'digital innovation', 'computer screen'],
            'people' => ['diverse team', 'professional people', 'lifestyle portrait'],
            'city' => ['urban skyline', 'city streets', 'metropolitan view'],
        ];

        foreach ($contextKeywords as $context => $suggestions) {
            if (stripos($combined, $context) !== false) {
                $alternatives = array_merge($alternatives, array_slice($suggestions, 0, 2));
                break;
            }
        }

        return [
            'primaryQuery' => $primaryQuery,
            'alternatives' => array_slice(array_unique($alternatives), 0, 4),
        ];
    }

    /**
     * Search stock media.
     */
    #[On('search-stock-media')]
    public function searchStockMedia(string $query, string $type = 'image', int $sceneIndex = 0): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $stockService = app(StockMediaService::class);

            // Get orientation based on aspect ratio
            $orientation = $stockService->getOrientation($this->aspectRatio);

            $result = $stockService->searchPexels($query, $type, [
                'orientation' => $orientation,
                'page' => 1,
                'perPage' => 20,
            ]);

            if ($result['success']) {
                $this->dispatch('stock-media-results', [
                    'results' => $result['results'],
                    'total' => $result['total'],
                    'sceneIndex' => $sceneIndex,
                ]);
            } else {
                $this->error = $result['error'] ?? __('Failed to search stock media');
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to search stock media: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Select stock media for a scene.
     */
    #[On('select-stock-media')]
    public function selectStockMedia(int $sceneIndex, string $mediaUrl, string $mediaId, string $type = 'image'): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            $stockService = app(StockMediaService::class);

            $result = $stockService->importMedia(
                $project,
                $mediaUrl,
                $mediaId,
                $type,
                $scene['id'],
                ['sceneIndex' => $sceneIndex]
            );

            if ($result['success']) {
                // Update storyboard with the stock media
                if (!isset($this->storyboard['scenes'])) {
                    $this->storyboard['scenes'] = [];
                }

                $this->storyboard['scenes'][$sceneIndex] = [
                    'sceneId' => $scene['id'],
                    'imageUrl' => $result['url'],
                    'assetId' => $result['assetId'],
                    'source' => 'stock',
                    'status' => 'ready',
                ];

                $this->saveProject();

                $this->dispatch('stock-media-selected', [
                    'sceneIndex' => $sceneIndex,
                    'imageUrl' => $result['url'],
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Import failed');
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to import stock media: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Poll for pending HiDream image generation jobs.
     */
    #[On('poll-image-jobs')]
    public function pollImageJobs(): void
    {
        if (!$this->projectId) {
            return;
        }

        try {
            $project = WizardProject::findOrFail($this->projectId);
            $imageService = app(ImageGenerationService::class);

            // Get pending/processing jobs
            $jobs = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                ->where('project_id', $project->id)
                ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                ->whereIn('status', [
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                ])
                ->get();

            foreach ($jobs as $job) {
                $result = $imageService->pollHiDreamJob($job);

                if ($result['status'] === 'ready' && $result['success']) {
                    // Image is ready - update storyboard
                    $sceneIndex = $result['sceneIndex'] ?? null;
                    if ($sceneIndex !== null) {
                        if (!isset($this->storyboard['scenes'])) {
                            $this->storyboard['scenes'] = [];
                        }
                        $this->storyboard['scenes'][$sceneIndex] = [
                            'sceneId' => $job->input_data['sceneId'] ?? null,
                            'imageUrl' => $result['imageUrl'],
                            'assetId' => $result['assetId'],
                            'source' => 'ai',
                            'status' => 'ready',
                        ];

                        $this->saveProject();

                        $this->dispatch('image-ready', [
                            'sceneIndex' => $sceneIndex,
                            'imageUrl' => $result['imageUrl'],
                        ]);
                    }
                } elseif ($result['status'] === 'error') {
                    $this->dispatch('image-error', [
                        'sceneIndex' => $job->input_data['sceneIndex'] ?? null,
                        'error' => $result['error'],
                    ]);
                }
            }

            // If there are still pending jobs, schedule another poll
            $pendingCount = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                ->where('project_id', $project->id)
                ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                ->whereIn('status', [
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                ])
                ->count();

            $this->dispatch('poll-status', [
                'pendingJobs' => $pendingCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to poll image jobs: ' . $e->getMessage());
        }
    }

    /**
     * Get pending jobs count for a project.
     */
    public function getPendingJobsCount(): int
    {
        if (!$this->projectId) {
            return 0;
        }

        return \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
            ->where('project_id', $this->projectId)
            ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
            ->whereIn('status', [
                \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
            ])
            ->count();
    }

    /**
     * Cancel a stuck image generation job.
     */
    #[On('cancel-image-generation')]
    public function cancelImageGeneration(int $sceneIndex): void
    {
        try {
            // Get the processing job for this scene
            if ($this->projectId) {
                $job = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                    ->where('project_id', $this->projectId)
                    ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                    ->whereIn('status', [
                        \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                        \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                    ])
                    ->whereJsonContains('input_data->sceneIndex', $sceneIndex)
                    ->first();

                if ($job) {
                    $job->markAsCancelled();
                    \Log::info("Cancelled stuck job for scene {$sceneIndex}", ['jobId' => $job->id]);
                }
            }

            // Reset the scene status in storyboard
            if (isset($this->storyboard['scenes'][$sceneIndex])) {
                $this->storyboard['scenes'][$sceneIndex]['status'] = null;
                $this->storyboard['scenes'][$sceneIndex]['imageUrl'] = null;
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = null;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = null;
            }

            // Remove from pendingJobs array if present
            if (isset($this->pendingJobs[$sceneIndex])) {
                unset($this->pendingJobs[$sceneIndex]);
            }

            $this->saveProject();

            $this->dispatch('generation-cancelled', [
                'sceneIndex' => $sceneIndex,
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to cancel generation for scene {$sceneIndex}: " . $e->getMessage());
            $this->error = __('Failed to cancel generation');
        }
    }

    /**
     * Generate voiceover for a single scene.
     */
    #[On('generate-voiceover')]
    public function generateVoiceover(int $sceneIndex, string $sceneId): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            $voiceoverService = app(VoiceoverService::class);
            $result = $voiceoverService->generateSceneVoiceover($project, $scene, [
                'sceneIndex' => $sceneIndex,
                'voice' => $this->animation['voiceover']['voice'] ?? 'nova',
                'speed' => $this->animation['voiceover']['speed'] ?? 1.0,
                'teamId' => session('current_team_id', 0),
            ]);

            // Update animation with the generated voiceover
            if (!isset($this->animation['scenes'])) {
                $this->animation['scenes'] = [];
            }
            $this->animation['scenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'voiceoverUrl' => $result['audioUrl'],
                'assetId' => $result['assetId'] ?? null,
                'duration' => $result['duration'] ?? null,
            ];

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceover: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate voiceovers for all scenes.
     */
    #[On('generate-all-voiceovers')]
    public function generateAllVoiceovers(): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $voiceoverService = app(VoiceoverService::class);

            if (!isset($this->animation['scenes'])) {
                $this->animation['scenes'] = [];
            }

            foreach ($this->script['scenes'] as $index => $scene) {
                // Skip if already has a voiceover
                if (!empty($this->animation['scenes'][$index]['voiceoverUrl'])) {
                    continue;
                }

                try {
                    $result = $voiceoverService->generateSceneVoiceover($project, $scene, [
                        'sceneIndex' => $index,
                        'voice' => $this->animation['voiceover']['voice'] ?? 'nova',
                        'speed' => $this->animation['voiceover']['speed'] ?? 1.0,
                        'teamId' => session('current_team_id', 0),
                    ]);

                    $this->animation['scenes'][$index] = [
                        'sceneId' => $scene['id'],
                        'voiceoverUrl' => $result['audioUrl'],
                        'assetId' => $result['assetId'] ?? null,
                        'duration' => $result['duration'] ?? null,
                    ];

                    $this->saveProject();

                } catch (\Exception $e) {
                    \Log::warning("Failed to generate voiceover for scene {$index}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceovers: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Regenerate voiceover for a scene.
     */
    #[On('regenerate-voiceover')]
    public function regenerateVoiceover(int $sceneIndex): void
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            // Clear existing voiceover first
            if (isset($this->animation['scenes'][$sceneIndex])) {
                unset($this->animation['scenes'][$sceneIndex]['voiceoverUrl']);
            }
            $this->generateVoiceover($sceneIndex, $scene['id']);
        }
    }

    /**
     * Get step titles.
     */
    public function getStepTitles(): array
    {
        return [
            1 => 'Platform & Format',
            2 => 'Concept',
            3 => 'Script',
            4 => 'Storyboard',
            5 => 'Animation',
            6 => 'Assembly',
            7 => 'Export',
        ];
    }

    /**
     * Check if step is completed.
     */
    public function isStepCompleted(int $step): bool
    {
        return match ($step) {
            1 => !empty($this->platform) || !empty($this->format),
            2 => !empty($this->concept['rawInput']) || !empty($this->concept['refinedConcept']),
            3 => !empty($this->script['scenes']),
            4 => $this->hasStoryboardImages(),
            5 => $this->hasAnimationData(),
            6 => true, // Assembly is optional
            7 => false, // Export is never "completed" in this sense
            default => false,
        };
    }

    /**
     * Check if storyboard has images.
     */
    protected function hasStoryboardImages(): bool
    {
        foreach ($this->storyboard['scenes'] ?? [] as $scene) {
            if (!empty($scene['imageUrl'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if animation data exists.
     */
    protected function hasAnimationData(): bool
    {
        foreach ($this->animation['scenes'] ?? [] as $scene) {
            if (!empty($scene['voiceoverUrl']) || !empty($scene['videoUrl'])) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // STOCK MEDIA BROWSER METHODS
    // =========================================================================

    /**
     * Open stock media browser for a scene.
     */
    #[On('open-stock-browser')]
    public function openStockBrowser(int $sceneIndex): void
    {
        $this->stockBrowserSceneIndex = $sceneIndex;
        $this->showStockBrowser = true;
        $this->stockSearchQuery = '';
        $this->stockSearchResults = [];

        // Set default search query based on scene description
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            // Extract keywords from visual description
            $description = $scene['visualDescription'] ?? $scene['title'] ?? '';
            $this->stockSearchQuery = $this->extractSearchKeywords($description);
        }

        // Set orientation based on aspect ratio
        $this->stockOrientation = match ($this->aspectRatio) {
            '9:16', '4:5' => 'portrait',
            '1:1' => 'square',
            default => 'landscape',
        };
    }

    /**
     * Extract search keywords from text.
     */
    protected function extractSearchKeywords(string $text): string
    {
        // Remove common words and keep meaningful keywords
        $stopWords = ['the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were'];
        $words = preg_split('/\s+/', strtolower($text));
        $keywords = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        return implode(' ', array_slice($keywords, 0, 4));
    }

    // =========================================================================
    // EDIT PROMPT/SCENE METHODS
    // =========================================================================

    /**
     * Open edit prompt modal for a scene (full scene editing).
     */
    #[On('open-edit-prompt')]
    public function openEditPrompt(int $sceneIndex): void
    {
        $this->editPromptSceneIndex = $sceneIndex;
        $this->showEditPromptModal = true;

        // Load existing prompt or visual description
        $storyboardScene = $this->storyboard['scenes'][$sceneIndex] ?? null;
        $scriptScene = $this->script['scenes'][$sceneIndex] ?? null;

        $this->editPromptText = $storyboardScene['prompt']
            ?? $scriptScene['visualDescription']
            ?? $scriptScene['narration']
            ?? '';

        // Load scene properties for full scene editing
        $this->editSceneNarration = $scriptScene['narration'] ?? '';
        $this->editSceneDuration = (int) ($scriptScene['duration'] ?? 8);
        $this->editSceneTransition = $scriptScene['transition'] ?? 'cut';
    }

    /**
     * Close edit prompt modal.
     */
    public function closeEditPrompt(): void
    {
        $this->showEditPromptModal = false;
        $this->editPromptText = '';
        $this->editSceneNarration = '';
        $this->editSceneDuration = 8;
        $this->editSceneTransition = 'cut';
    }

    /**
     * Save scene properties only (without regenerating image).
     */
    public function saveSceneProperties(): void
    {
        // Update script scene properties
        if (isset($this->script['scenes'][$this->editPromptSceneIndex])) {
            $this->script['scenes'][$this->editPromptSceneIndex]['narration'] = $this->editSceneNarration;
            $this->script['scenes'][$this->editPromptSceneIndex]['duration'] = $this->editSceneDuration;
            $this->script['scenes'][$this->editPromptSceneIndex]['transition'] = $this->editSceneTransition;

            // Update visual description if provided
            if (!empty($this->editPromptText)) {
                $this->script['scenes'][$this->editPromptSceneIndex]['visualDescription'] = $this->editPromptText;
            }
        }

        // Store the custom prompt in storyboard
        if (!empty($this->editPromptText)) {
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }
            if (!isset($this->storyboard['scenes'][$this->editPromptSceneIndex])) {
                $this->storyboard['scenes'][$this->editPromptSceneIndex] = [];
            }
            $this->storyboard['scenes'][$this->editPromptSceneIndex]['prompt'] = $this->editPromptText;
        }

        $this->saveProject();
        $this->closeEditPrompt();
    }

    /**
     * Save edited prompt and regenerate image.
     */
    public function saveAndRegeneratePrompt(): void
    {
        if (empty($this->editPromptText)) {
            $this->error = __('Prompt cannot be empty');
            return;
        }

        // First save all scene properties
        if (isset($this->script['scenes'][$this->editPromptSceneIndex])) {
            $this->script['scenes'][$this->editPromptSceneIndex]['visualDescription'] = $this->editPromptText;
            $this->script['scenes'][$this->editPromptSceneIndex]['narration'] = $this->editSceneNarration;
            $this->script['scenes'][$this->editPromptSceneIndex]['duration'] = $this->editSceneDuration;
            $this->script['scenes'][$this->editPromptSceneIndex]['transition'] = $this->editSceneTransition;
        }

        // Store the custom prompt in storyboard
        if (!isset($this->storyboard['scenes'])) {
            $this->storyboard['scenes'] = [];
        }
        if (!isset($this->storyboard['scenes'][$this->editPromptSceneIndex])) {
            $this->storyboard['scenes'][$this->editPromptSceneIndex] = [];
        }
        $this->storyboard['scenes'][$this->editPromptSceneIndex]['prompt'] = $this->editPromptText;

        $this->closeEditPrompt();

        // Regenerate the image with the new prompt
        $scene = $this->script['scenes'][$this->editPromptSceneIndex] ?? null;
        if ($scene) {
            $this->generateImage($this->editPromptSceneIndex, $scene['id']);
        }
    }

    // =========================================================================
    // SCENE MEMORY METHODS (Style Bible, Character Bible, Location Bible)
    // =========================================================================

    /**
     * Toggle Style Bible.
     */
    public function toggleStyleBible(): void
    {
        $this->sceneMemory['styleBible']['enabled'] = !$this->sceneMemory['styleBible']['enabled'];

        // Sync to storyboard
        $this->storyboard['styleBible'] = $this->sceneMemory['styleBible'];
        $this->saveProject();
    }

    /**
     * Update Style Bible settings.
     */
    public function updateStyleBible(string $field, string $value): void
    {
        if (isset($this->sceneMemory['styleBible'][$field])) {
            $this->sceneMemory['styleBible'][$field] = $value;
            $this->storyboard['styleBible'] = $this->sceneMemory['styleBible'];
            $this->saveProject();
        }
    }

    /**
     * Toggle Character Bible.
     */
    public function toggleCharacterBible(): void
    {
        $this->sceneMemory['characterBible']['enabled'] = !$this->sceneMemory['characterBible']['enabled'];
        $this->saveProject();
    }

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
            'appliedScenes' => [],
            'traits' => [],
            'referenceImage' => null,
        ];
        $this->saveProject();
    }

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

    /**
     * Apply a preset trait set to a character based on archetype.
     */
    public function applyTraitPreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $presets = [
            'hero' => ['confident', 'determined', 'courageous', 'charismatic'],
            'villain' => ['cunning', 'menacing', 'calculating', 'powerful'],
            'mentor' => ['wise', 'patient', 'experienced', 'supportive'],
            'comic' => ['witty', 'playful', 'energetic', 'quirky'],
            'mysterious' => ['enigmatic', 'reserved', 'observant', 'cryptic'],
            'professional' => ['competent', 'focused', 'reliable', 'articulate'],
            'creative' => ['imaginative', 'passionate', 'expressive', 'innovative'],
            'leader' => ['authoritative', 'decisive', 'inspiring', 'strategic'],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        // Merge with existing traits, avoiding duplicates
        $currentTraits = $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] ?? [];
        $currentTraitsLower = array_map('strtolower', $currentTraits);

        foreach ($presets[$preset] as $trait) {
            if (!in_array(strtolower($trait), $currentTraitsLower)) {
                $currentTraits[] = $trait;
                $currentTraitsLower[] = strtolower($trait);
            }
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = $currentTraits;
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
     * Toggle Location Bible.
     */
    public function toggleLocationBible(): void
    {
        $this->sceneMemory['locationBible']['enabled'] = !$this->sceneMemory['locationBible']['enabled'];
        $this->saveProject();
    }

    /**
     * Add location to Location Bible.
     */
    public function addLocation(string $name = '', string $description = ''): void
    {
        $this->sceneMemory['locationBible']['locations'][] = [
            'id' => uniqid('loc_'),
            'name' => $name,
            'type' => 'exterior',
            'timeOfDay' => 'day',
            'weather' => 'clear',
            'atmosphere' => '',
            'description' => $description,
            'scenes' => [],
            'stateChanges' => [],
            'referenceImage' => null,
        ];
        $this->saveProject();
    }

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
        $found = false;
        foreach ($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] as $idx => $change) {
            if (($change['scene'] ?? -1) === $sceneIndex) {
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$idx]['state'] = $state;
                $found = true;
                break;
            }
        }

        // Add new state change if not found
        if (!$found) {
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][] = [
                'scene' => $sceneIndex,
                'state' => $state,
            ];

            // Sort by scene index
            usort(
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'],
                fn($a, $b) => ($a['scene'] ?? 0) <=> ($b['scene'] ?? 0)
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

        // Apply first state to first scene, second state to last scene
        $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = [
            ['scene' => $firstScene, 'state' => $presets[$preset][0]['state']],
            ['scene' => $lastScene, 'state' => $presets[$preset][1]['state']],
        ];

        $this->saveProject();
    }

    /**
     * Get the location state for a specific scene index.
     */
    protected function getLocationStateForScene(array $location, int $sceneIndex): ?string
    {
        $stateChanges = $location['stateChanges'] ?? [];
        if (empty($stateChanges)) {
            return null;
        }

        // Find the most recent state change at or before this scene
        $applicableState = null;
        foreach ($stateChanges as $change) {
            $changeScene = $change['scene'] ?? -1;
            if ($changeScene <= $sceneIndex) {
                $applicableState = $change['state'] ?? null;
            } else {
                break; // Since sorted, no need to continue
            }
        }

        return $applicableState;
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
    // RUNPOD POLLING METHODS
    // =========================================================================

    /**
     * Check status of pending RunPod jobs.
     */
    public function pollPendingJobs(): void
    {
        if (empty($this->pendingJobs)) {
            return;
        }

        $imageService = app(ImageGenerationService::class);

        foreach ($this->pendingJobs as $sceneIndex => $job) {
            try {
                $result = $imageService->checkRunPodJobStatus($job['jobId']);

                if ($result['status'] === 'COMPLETED') {
                    // Update storyboard scene with completed image
                    if (isset($this->storyboard['scenes'][$sceneIndex])) {
                        $this->storyboard['scenes'][$sceneIndex]['status'] = 'ready';
                        // Image URL should already be set
                    }

                    // Remove from pending
                    unset($this->pendingJobs[$sceneIndex]);
                    $this->saveProject();

                } elseif ($result['status'] === 'FAILED') {
                    // Mark as error
                    if (isset($this->storyboard['scenes'][$sceneIndex])) {
                        $this->storyboard['scenes'][$sceneIndex]['status'] = 'error';
                        $this->storyboard['scenes'][$sceneIndex]['error'] = $result['error'] ?? 'Generation failed';
                    }

                    unset($this->pendingJobs[$sceneIndex]);
                    $this->saveProject();
                }
                // If IN_QUEUE or IN_PROGRESS, keep polling

            } catch (\Exception $e) {
                \Log::error("Failed to poll job status: " . $e->getMessage());
            }
        }
    }

    /**
     * Get image models for display.
     */
    public function getImageModels(): array
    {
        return [
            'hidream' => [
                'name' => 'HiDream',
                'description' => 'Artistic & cinematic style',
                'tokenCost' => 2,
            ],
            'nanobanana-pro' => [
                'name' => 'NanoBanana Pro',
                'description' => 'High quality, fast generation',
                'tokenCost' => 3,
            ],
            'nanobanana' => [
                'name' => 'NanoBanana',
                'description' => 'Quick drafts, lower cost',
                'tokenCost' => 1,
            ],
        ];
    }

    // =========================================================================
    // PROMPT CHAIN METHODS
    // =========================================================================

    /**
     * Process prompt chain for all scenes.
     */
    public function processPromptChain(): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $this->storyboard['promptChain']['status'] = 'processing';

            // Process each scene
            foreach ($this->script['scenes'] as $index => $scene) {
                $this->storyboard['promptChain']['scenes'][$index] = [
                    'sceneId' => $scene['id'],
                    'imagePrompt' => $this->buildScenePrompt($scene, $index),
                    'processed' => true,
                ];
            }

            $this->storyboard['promptChain']['status'] = 'ready';
            $this->storyboard['promptChain']['processedAt'] = now()->toIso8601String();

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to process prompt chain: ') . $e->getMessage();
            $this->storyboard['promptChain']['status'] = 'error';
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Apply a technical specs preset for quick configuration.
     */
    public function applyTechnicalSpecsPreset(string $preset): void
    {
        $presets = [
            'cinematic' => [
                'quality' => '4k',
                'positive' => 'cinematic, film grain, anamorphic lens, shallow depth of field, dramatic lighting, professional color grading, 8K resolution, HDR, masterpiece',
                'negative' => 'blurry, low quality, amateur, oversaturated, cartoon, anime, illustration, watermark, text, logo, nsfw, deformed',
            ],
            'photorealistic' => [
                'quality' => '4k',
                'positive' => 'photorealistic, ultra detailed, DSLR photo, natural lighting, sharp focus, 8K UHD, professional photography, realistic textures, high resolution',
                'negative' => 'cartoon, anime, illustration, 3D render, CGI, artificial, blurry, low quality, watermark, text, deformed, oversaturated',
            ],
            'artistic' => [
                'quality' => '2k',
                'positive' => 'artistic, stylized, vibrant colors, creative composition, expressive, beautiful artwork, detailed illustration, concept art quality',
                'negative' => 'blurry, low quality, pixelated, watermark, text, logo, amateur, poorly drawn, ugly',
            ],
            'documentary' => [
                'quality' => '4k',
                'positive' => 'documentary style, authentic, natural, candid, observational, informative, real-world, high quality footage, professional',
                'negative' => 'staged, artificial, cartoon, fantasy, blurry, low quality, watermark, text, logo, glamorous, stylized',
            ],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        $this->storyboard['technicalSpecs'] = array_merge(
            $this->storyboard['technicalSpecs'],
            $presets[$preset],
            ['enabled' => true]
        );

        $this->saveProject();
    }

    /**
     * Build comprehensive prompt for a scene integrating all Bibles.
     *
     * Prompt Chain Architecture (5 Layers):
     * 1. Style Bible - Visual DNA, style, color grade, atmosphere, camera
     * 2. Character Bible - Character descriptions for characters in this scene
     * 3. Location Bible - Location description for this scene
     * 4. Scene Content - Visual description + visual style settings
     * 5. Technical Specs - Quality and format specifications
     */
    protected function buildScenePrompt(array $scene, int $index): string
    {
        $parts = [];

        // =========================================================================
        // LAYER 1: STYLE BIBLE (Visual DNA)
        // =========================================================================
        if ($this->sceneMemory['styleBible']['enabled'] ?? false) {
            $styleBible = $this->sceneMemory['styleBible'];
            $styleParts = [];

            if (!empty($styleBible['style'])) {
                $styleParts[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $styleParts[] = $styleBible['colorGrade'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $styleParts[] = $styleBible['atmosphere'];
            }
            if (!empty($styleBible['camera'])) {
                $styleParts[] = $styleBible['camera'];
            }

            if (!empty($styleParts)) {
                $parts[] = 'STYLE: ' . implode(', ', $styleParts);
            }

            if (!empty($styleBible['visualDNA'])) {
                $parts[] = 'QUALITY: ' . $styleBible['visualDNA'];
            }
        }

        // =========================================================================
        // LAYER 2: CHARACTER BIBLE (Characters in this scene)
        // =========================================================================
        if ($this->sceneMemory['characterBible']['enabled'] ?? false) {
            $characters = $this->sceneMemory['characterBible']['characters'] ?? [];
            $sceneCharacters = $this->getCharactersForSceneIndex($characters, $index);

            if (!empty($sceneCharacters)) {
                $characterDescriptions = [];
                foreach ($sceneCharacters as $character) {
                    if (!empty($character['description'])) {
                        $name = $character['name'] ?? 'Character';
                        $charDesc = "{$name}: {$character['description']}";

                        // Include traits if available for personality/expression guidance
                        $traits = $character['traits'] ?? [];
                        if (!empty($traits)) {
                            $charDesc .= ' (personality: ' . implode(', ', array_slice($traits, 0, 4)) . ')';
                        }

                        $characterDescriptions[] = $charDesc;
                    }
                }
                if (!empty($characterDescriptions)) {
                    $parts[] = 'CHARACTERS: ' . implode('. ', $characterDescriptions);
                }
            }
        }

        // =========================================================================
        // LAYER 3: LOCATION BIBLE (Location for this scene)
        // =========================================================================
        if ($this->sceneMemory['locationBible']['enabled'] ?? false) {
            $locations = $this->sceneMemory['locationBible']['locations'] ?? [];
            $sceneLocation = $this->getLocationForSceneIndex($locations, $index);

            if ($sceneLocation) {
                $locationParts = [];

                $locName = $sceneLocation['name'] ?? '';
                $locType = $sceneLocation['type'] ?? '';
                if ($locName) {
                    $locationParts[] = $locName . ($locType ? " ({$locType})" : '');
                }

                if (!empty($sceneLocation['description'])) {
                    $locationParts[] = $sceneLocation['description'];
                }

                if (!empty($sceneLocation['timeOfDay'])) {
                    $locationParts[] = $sceneLocation['timeOfDay'];
                }

                if (!empty($sceneLocation['weather']) && $sceneLocation['weather'] !== 'clear') {
                    $locationParts[] = $sceneLocation['weather'] . ' weather';
                }

                // Include location state for this scene if available
                $locationState = $this->getLocationStateForScene($sceneLocation, $index);
                if ($locationState) {
                    $locationParts[] = 'current state: ' . $locationState;
                }

                if (!empty($locationParts)) {
                    $parts[] = 'LOCATION: ' . implode(', ', $locationParts);
                }
            }
        }

        // =========================================================================
        // LAYER 4: SCENE CONTENT (Visual description + Visual Style)
        // =========================================================================
        $visualStyle = $this->storyboard['visualStyle'] ?? [];
        $visualParts = [];

        if (!empty($visualStyle['mood'])) {
            $visualParts[] = $visualStyle['mood'] . ' mood';
        }
        if (!empty($visualStyle['lighting'])) {
            $visualParts[] = $visualStyle['lighting'] . ' lighting';
        }
        if (!empty($visualStyle['colorPalette'])) {
            $visualParts[] = $visualStyle['colorPalette'] . ' color palette';
        }
        if (!empty($visualStyle['composition'])) {
            $visualParts[] = $visualStyle['composition'] . ' shot';
        }

        if (!empty($visualParts)) {
            $parts[] = 'VISUAL: ' . implode(', ', $visualParts);
        }

        // Scene visual description
        $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';
        if (!empty($visualDescription)) {
            $parts[] = 'SCENE: ' . $visualDescription;
        }

        // =========================================================================
        // LAYER 5: TECHNICAL SPECS
        // =========================================================================
        if ($this->storyboard['technicalSpecs']['enabled'] ?? true) {
            $techSpecs = $this->storyboard['technicalSpecs']['positive'] ?? 'high quality, detailed, professional, 8K resolution';
            $parts[] = $techSpecs;
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Get characters that appear in a specific scene (for prompt building).
     */
    protected function getCharactersForSceneIndex(array $characters, int $sceneIndex): array
    {
        return array_filter($characters, function ($character) use ($sceneIndex) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];
            // Empty array means "applies to ALL scenes" (default behavior)
            // Non-empty array means "applies only to these specific scenes"
            return empty($appliedScenes) || in_array($sceneIndex, $appliedScenes);
        });
    }

    /**
     * Get the primary location for a specific scene (for prompt building).
     */
    protected function getLocationForSceneIndex(array $locations, int $sceneIndex): ?array
    {
        foreach ($locations as $location) {
            $scenes = $location['scenes'] ?? $location['appearsInScenes'] ?? [];
            // Empty array means "applies to ALL scenes" (default behavior)
            // Non-empty array means "applies only to these specific scenes"
            if (empty($scenes) || in_array($sceneIndex, $scenes)) {
                return $location;
            }
        }
        return null;
    }

    // =========================================================================
    // EDIT PROMPT MODAL METHODS
    // =========================================================================

    /**
     * Open edit prompt modal.
     */
    public function openEditPromptModal(int $sceneIndex): void
    {
        $this->editPromptSceneIndex = $sceneIndex;
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        $this->editPromptText = $scene['visualDescription'] ?? $scene['narration'] ?? '';
        $this->showEditPromptModal = true;

        $this->dispatch('open-edit-prompt-modal', ['sceneIndex' => $sceneIndex]);
    }

    /**
     * Append text to current prompt.
     */
    public function appendToPrompt(string $text): void
    {
        if (!empty($this->editPromptText)) {
            $this->editPromptText .= ', ' . $text;
        } else {
            $this->editPromptText = $text;
        }
    }

    // =========================================================================
    // PROJECT MANAGER METHODS
    // =========================================================================

    /**
     * Open project manager modal and load projects.
     */
    public function openProjectManager(): void
    {
        $this->loadProjectManagerProjects();
        $this->showProjectManager = true;
    }

    /**
     * Close project manager modal.
     */
    public function closeProjectManager(): void
    {
        $this->showProjectManager = false;
    }

    /**
     * Load projects for the project manager with pagination and filtering.
     */
    public function loadProjectManagerProjects(): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        // Base query for user's projects
        $baseQuery = WizardProject::where(function ($q) use ($userId, $teamId) {
            $q->where('user_id', $userId);
            if ($teamId) {
                $q->orWhere('team_id', $teamId);
            }
        });

        // Apply search filter to base query
        if (!empty($this->projectManagerSearch)) {
            $baseQuery->where('name', 'like', '%' . $this->projectManagerSearch . '%');
        }

        // Calculate status counts (before applying status filter)
        $this->calculateStatusCounts(clone $baseQuery);

        // Clone for filtered query
        $query = clone $baseQuery;

        // Apply status filter
        if ($this->projectManagerStatusFilter !== 'all') {
            $query->where('status', $this->projectManagerStatusFilter);
        }

        // Apply sorting with direction
        $query->orderBy($this->projectManagerSort, $this->projectManagerSortDirection);

        // Get total count for pagination (after status filter)
        $this->projectManagerTotal = $query->count();

        // Calculate offset for pagination
        $offset = ($this->projectManagerPage - 1) * $this->projectManagerPerPage;

        // Get paginated projects
        $projects = $query->skip($offset)->take($this->projectManagerPerPage)->get();

        $this->projectManagerProjects = $projects->map(function ($project) {
            // Calculate step progress (1-7 steps)
            $stepsCompleted = $this->calculateProjectStepProgress($project);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'platform' => $project->platform,
                'status' => $project->status ?? $this->detectProjectStatus($project),
                'target_duration' => $project->target_duration,
                'script' => $project->script ?? [],
                'stepsCompleted' => $stepsCompleted,
                'created_at' => $project->created_at?->toIso8601String(),
                'updated_at' => $project->updated_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Calculate status counts for filter tabs.
     */
    protected function calculateStatusCounts($query): void
    {
        // Get all projects to count statuses
        $allProjects = $query->get();

        $this->projectManagerStatusCounts = [
            'all' => $allProjects->count(),
            'draft' => $allProjects->where('status', 'draft')->count(),
            'in_progress' => $allProjects->where('status', 'in_progress')->count(),
            'complete' => $allProjects->where('status', 'complete')->count(),
        ];
    }

    /**
     * Calculate the step progress of a project.
     */
    protected function calculateProjectStepProgress($project): int
    {
        $steps = 0;

        // Step 1: Platform selected
        if (!empty($project->platform)) {
            $steps = 1;
        }

        // Step 2: Concept filled
        $concept = $project->concept ?? [];
        if (!empty($concept) && (!empty($concept['rawInput'] ?? '') || !empty($concept['refinedConcept'] ?? ''))) {
            $steps = 2;
        }

        // Step 3: Script has scenes
        $script = $project->script ?? [];
        if (!empty($script) && isset($script['scenes']) && count($script['scenes'] ?? []) > 0) {
            $steps = 3;
        }

        // Step 4: Storyboard has frames
        $storyboard = $project->storyboard ?? [];
        if (!empty($storyboard) && (isset($storyboard['frames']) || isset($storyboard['scenes']))) {
            $steps = 4;
        }

        // Step 5: Animation configured
        $animation = $project->animation ?? [];
        if (!empty($animation)) {
            $steps = 5;
        }

        // Step 6: Assembly configured
        $assembly = $project->assembly ?? [];
        if (!empty($assembly)) {
            $steps = 6;
        }

        // Step 7: Exported
        if (!empty($assembly) && isset($assembly['exported']) && $assembly['exported']) {
            $steps = 7;
        }

        return $steps;
    }

    /**
     * Detect project status based on its data.
     */
    protected function detectProjectStatus($project): string
    {
        $steps = $this->calculateProjectStepProgress($project);

        if ($steps >= 7) {
            return 'complete';
        } elseif ($steps >= 3) {
            return 'in_progress';
        }

        return 'draft';
    }

    /**
     * Go to a specific page in project manager.
     */
    public function projectManagerGoToPage(int $page): void
    {
        $totalPages = ceil($this->projectManagerTotal / $this->projectManagerPerPage);
        $this->projectManagerPage = max(1, min($page, $totalPages));
        $this->loadProjectManagerProjects();
    }

    /**
     * Go to next page in project manager.
     */
    public function projectManagerNextPage(): void
    {
        $totalPages = ceil($this->projectManagerTotal / $this->projectManagerPerPage);
        if ($this->projectManagerPage < $totalPages) {
            $this->projectManagerPage++;
            $this->loadProjectManagerProjects();
        }
    }

    /**
     * Go to previous page in project manager.
     */
    public function projectManagerPrevPage(): void
    {
        if ($this->projectManagerPage > 1) {
            $this->projectManagerPage--;
            $this->loadProjectManagerProjects();
        }
    }

    /**
     * Load a project from the project manager.
     */
    public function loadProjectFromManager(int $projectId): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        $project = WizardProject::where('id', $projectId)
            ->where(function ($q) use ($userId, $teamId) {
                $q->where('user_id', $userId);
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            })
            ->first();

        if (!$project) {
            $this->error = __('Project not found or access denied.');
            return;
        }

        // Load the project
        $this->loadProject($project);

        // Close modal and update URL
        $this->showProjectManager = false;
        $this->dispatch('update-browser-url', ['projectId' => $projectId]);
        $this->dispatch('project-loaded', ['projectId' => $projectId]);
    }

    /**
     * Delete a project from the project manager.
     */
    public function deleteProjectFromManager(int $projectId): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        $project = WizardProject::where('id', $projectId)
            ->where(function ($q) use ($userId, $teamId) {
                $q->where('user_id', $userId);
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            })
            ->first();

        if (!$project) {
            $this->error = __('Project not found or access denied.');
            return;
        }

        // Check if we're deleting the current project
        $isDeletingCurrent = $this->projectId === $projectId;

        // Delete associated assets and jobs
        $project->assets()->delete();
        $project->processingJobs()->delete();
        $project->delete();

        // If we deleted the current project, reset to new project
        if ($isDeletingCurrent) {
            $this->createNewProject();
        }

        // Refresh the projects list
        $this->loadProjectManagerProjects();

        $this->dispatch('project-deleted', ['projectId' => $projectId]);
    }

    /**
     * Create a new project (reset wizard state).
     */
    public function createNewProject(): void
    {
        // Reset all state to defaults
        $this->projectId = null;
        $this->projectName = 'Untitled Video';
        $this->currentStep = 1;
        $this->maxReachedStep = 1;

        $this->platform = null;
        $this->aspectRatio = '16:9';
        $this->targetDuration = 60;
        $this->format = null;
        $this->productionType = null;
        $this->productionSubtype = null;

        $this->concept = [
            'rawInput' => '',
            'refinedConcept' => '',
            'keywords' => [],
            'keyElements' => [],
            'logline' => '',
            'suggestedMood' => null,
            'suggestedTone' => null,
            'styleReference' => '',
            'avoidElements' => '',
            'targetAudience' => '',
        ];

        $this->characterIntelligence = [
            'enabled' => true,
            'narrationStyle' => 'voiceover',
            'characterCount' => 4,
            'suggestedCount' => 4,
            'characters' => [],
        ];

        $this->script = [
            'title' => '',
            'hook' => '',
            'scenes' => [],
            'cta' => '',
            'totalDuration' => 0,
            'totalNarrationTime' => 0,
        ];

        $this->voiceStatus = [
            'dialogueLines' => 0,
            'speakers' => 0,
            'voicesMapped' => 0,
            'scenesWithDialogue' => 0,
            'scenesWithVoiceover' => 0,
            'pendingVoices' => 0,
        ];

        $this->storyboard = [
            'scenes' => [],
            'styleBible' => null,
            'imageModel' => 'nanobanana',
            'visualStyle' => [
                'mood' => '',
                'lighting' => '',
                'colorPalette' => '',
                'composition' => '',
            ],
            'technicalSpecs' => [
                'enabled' => true,
                'quality' => '4k',
                'positive' => 'high quality, detailed, professional, 8K resolution, sharp focus',
                'negative' => 'blurry, low quality, ugly, distorted, watermark, nsfw, text, logo',
            ],
            'promptChain' => [
                'enabled' => true,
                'status' => 'pending',
                'processedAt' => null,
                'scenes' => [],
            ],
        ];

        $this->animation = [
            'scenes' => [],
            'voiceover' => [
                'voice' => 'nova',
                'speed' => 1.0,
            ],
        ];

        $this->assembly = [
            'transitions' => [],
            'defaultTransition' => 'fade',
            'music' => ['enabled' => false, 'trackId' => null, 'volume' => 30],
            'captions' => [
                'enabled' => true,
                'style' => 'karaoke',
                'position' => 'bottom',
                'size' => 1,
            ],
        ];

        $this->sceneMemory = [
            'styleBible' => [
                'enabled' => false,
                'style' => '',
                'colorGrade' => '',
                'atmosphere' => '',
                'visualDNA' => '',
            ],
            'characterBible' => [
                'enabled' => false,
                'characters' => [],
            ],
            'locationBible' => [
                'enabled' => false,
                'locations' => [],
            ],
        ];

        $this->multiShotMode = [
            'enabled' => false,
            'defaultShotCount' => 3,
        ];

        $this->conceptVariations = [];
        $this->selectedConceptIndex = 0;
        $this->pendingJobs = [];
        $this->error = null;

        // Close the modal
        $this->showProjectManager = false;

        // Update browser URL
        $this->dispatch('update-browser-url', ['projectId' => null]);
        $this->dispatch('project-created');
    }

    /**
     * React to search changes in project manager.
     */
    public function updatedProjectManagerSearch(): void
    {
        $this->projectManagerPage = 1; // Reset to first page when searching
        $this->loadProjectManagerProjects();
    }

    /**
     * React to sort changes in project manager.
     */
    public function updatedProjectManagerSort(): void
    {
        $this->projectManagerPage = 1; // Reset to first page when changing sort
        $this->loadProjectManagerProjects();
    }

    /**
     * Set status filter in project manager.
     */
    public function setProjectManagerStatusFilter(string $status): void
    {
        $this->projectManagerStatusFilter = $status;
        $this->projectManagerPage = 1; // Reset to first page when changing filter
        $this->loadProjectManagerProjects();
    }

    /**
     * Toggle sort direction in project manager.
     */
    public function toggleProjectManagerSortDirection(): void
    {
        $this->projectManagerSortDirection = $this->projectManagerSortDirection === 'asc' ? 'desc' : 'asc';
        $this->loadProjectManagerProjects();
    }

    /**
     * Toggle select mode in project manager.
     */
    public function toggleProjectManagerSelectMode(): void
    {
        $this->projectManagerSelectMode = !$this->projectManagerSelectMode;
        if (!$this->projectManagerSelectMode) {
            $this->projectManagerSelected = [];
        }
    }

    /**
     * Toggle selection of a project.
     */
    public function toggleProjectSelection(int $projectId): void
    {
        if (in_array($projectId, $this->projectManagerSelected)) {
            $this->projectManagerSelected = array_values(array_diff($this->projectManagerSelected, [$projectId]));
        } else {
            $this->projectManagerSelected[] = $projectId;
        }
    }

    /**
     * Select all visible projects.
     */
    public function selectAllProjects(): void
    {
        $this->projectManagerSelected = array_column($this->projectManagerProjects, 'id');
    }

    /**
     * Deselect all projects.
     */
    public function deselectAllProjects(): void
    {
        $this->projectManagerSelected = [];
    }

    /**
     * Delete selected projects.
     */
    public function deleteSelectedProjects(): void
    {
        try {
            if (empty($this->projectManagerSelected)) {
                return;
            }

            // Don't delete the currently loaded project
            $toDelete = array_filter($this->projectManagerSelected, fn($id) => $id !== $this->projectId);

            WizardProject::whereIn('id', $toDelete)->delete();

            // Reset selection
            $this->projectManagerSelected = [];
            $this->projectManagerSelectMode = false;

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('projects-deleted', ['count' => count($toDelete)]);
        } catch (\Exception $e) {
            Log::error('Failed to delete selected projects: ' . $e->getMessage());
            $this->error = __('Failed to delete selected projects');
        }
    }

    /**
     * Export a project to JSON.
     */
    public function exportProject(int $projectId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $project = WizardProject::findOrFail($projectId);

        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'platform' => $project->platform,
                'aspect_ratio' => $project->aspect_ratio,
                'target_duration' => $project->target_duration,
                'format' => $project->format,
                'production_type' => $project->production_type,
                'production_subtype' => $project->production_subtype,
                'status' => $project->status,
                'concept' => $project->concept,
                'script' => $project->script,
                'storyboard' => $project->storyboard,
                'animation' => $project->animation,
                'assembly' => $project->assembly,
            ],
        ];

        $filename = \Illuminate\Support\Str::slug($project->name) . '-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Get comprehensive debug snapshot of all wizard state.
     * Used for troubleshooting issues by capturing every setting and selection.
     */
    public function getDebugSnapshot(): array
    {
        return [
            '_meta' => [
                'version' => '2.0',
                'generated_at' => now()->toIso8601String(),
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
                'user_id' => auth()->id(),
                'team_id' => session('current_team_id'),
            ],
            'wizard_state' => [
                'project_id' => $this->projectId,
                'project_name' => $this->projectName,
                'current_step' => $this->currentStep,
                'max_reached_step' => $this->maxReachedStep,
                'is_loading' => $this->isLoading,
                'is_saving' => $this->isSaving,
                'error' => $this->error,
            ],
            'platform_settings' => [
                'platform' => $this->platform,
                'aspect_ratio' => $this->aspectRatio,
                'target_duration' => $this->targetDuration,
                'format' => $this->format,
                'production_type' => $this->productionType,
                'production_subtype' => $this->productionSubtype,
                'content_format_override' => $this->contentFormatOverride ?? null,
            ],
            'script_settings' => [
                'script_tone' => $this->scriptTone,
                'content_depth' => $this->contentDepth,
                'additional_instructions' => $this->additionalInstructions,
                'narrative_preset' => $this->narrativePreset,
                'story_arc' => $this->storyArc,
                'tension_curve' => $this->tensionCurve,
                'emotional_journey' => $this->emotionalJourney,
            ],
            'concept' => $this->concept,
            'script' => [
                'title' => $this->script['title'] ?? null,
                'hook' => $this->script['hook'] ?? null,
                'cta' => $this->script['cta'] ?? null,
                'scene_count' => count($this->script['scenes'] ?? []),
                'total_duration' => collect($this->script['scenes'] ?? [])->sum('duration'),
                'scenes_summary' => collect($this->script['scenes'] ?? [])->map(fn($s, $i) => [
                    'index' => $i,
                    'id' => $s['id'] ?? null,
                    'duration' => $s['duration'] ?? null,
                    'has_narration' => !empty($s['narration']),
                    'has_visual' => !empty($s['visualDescription']),
                ])->toArray(),
            ],
            'storyboard' => [
                'visual_style' => $this->storyboard['visualStyle'] ?? null,
                'image_model' => $this->storyboard['imageModel'] ?? null,
                'style_bible_enabled' => $this->storyboard['styleBible']['enabled'] ?? false,
                'prompt_chain_status' => $this->storyboard['promptChain']['status'] ?? null,
            ],
            'scene_memory' => [
                'style_bible_enabled' => $this->sceneMemory['styleBible']['enabled'] ?? false,
                'character_bible_enabled' => $this->sceneMemory['characterBible']['enabled'] ?? false,
                'character_count' => count($this->sceneMemory['characterBible']['characters'] ?? []),
                'location_bible_enabled' => $this->sceneMemory['locationBible']['enabled'] ?? false,
                'location_count' => count($this->sceneMemory['locationBible']['locations'] ?? []),
            ],
            'pending_jobs' => array_keys($this->pendingJobs ?? []),
            'config_snapshot' => [
                'platform_config' => config('appvideowizard.platforms.' . $this->platform) ?? null,
                'production_type_config' => $this->productionType
                    ? config('appvideowizard.production_types.' . $this->productionType)
                    : null,
            ],
        ];
    }

    /**
     * Export debug snapshot as downloadable JSON file.
     */
    public function exportDebugSnapshot(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $snapshot = $this->getDebugSnapshot();
        $filename = 'wizard-debug-' . ($this->projectId ?? 'new') . '-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($snapshot) {
            echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Dispatch debug snapshot to browser console (for development).
     */
    public function logDebugSnapshot(): void
    {
        $snapshot = $this->getDebugSnapshot();
        $this->dispatch('vw-debug', [
            'action' => 'debug-snapshot',
            'message' => 'Full wizard state snapshot',
            'data' => $snapshot,
        ]);
    }

    /**
     * Import a project from JSON file.
     */
    public function importProject($file): void
    {
        try {
            if (!$file) {
                $this->error = __('No file selected');
                return;
            }

            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error = __('Invalid JSON file');
                return;
            }

            if (!isset($data['project'])) {
                $this->error = __('Invalid project file format');
                return;
            }

            $projectData = $data['project'];

            // Create new project from imported data
            $project = new WizardProject();
            $project->user_id = auth()->id();
            $project->team_id = session('current_team_id', 0);
            $project->name = ($projectData['name'] ?? 'Imported Project') . ' (Imported)';
            $project->platform = $projectData['platform'] ?? null;
            $project->aspect_ratio = $projectData['aspect_ratio'] ?? '16:9';
            $project->target_duration = $projectData['target_duration'] ?? 60;
            $project->format = $projectData['format'] ?? null;
            $project->production_type = $projectData['production_type'] ?? null;
            $project->production_subtype = $projectData['production_subtype'] ?? null;
            $project->status = 'draft'; // Always start as draft
            $project->concept = $projectData['concept'] ?? [];
            $project->script = $projectData['script'] ?? [];
            $project->storyboard = $projectData['storyboard'] ?? [];
            $project->animation = $projectData['animation'] ?? [];
            $project->assembly = $projectData['assembly'] ?? [];
            $project->save();

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-imported', ['projectId' => $project->id]);
        } catch (\Exception $e) {
            Log::error('Failed to import project: ' . $e->getMessage());
            $this->error = __('Failed to import project: ') . $e->getMessage();
        }
    }

    /**
     * Duplicate a project.
     */
    public function duplicateProject(int $projectId): void
    {
        try {
            $original = WizardProject::findOrFail($projectId);

            // Create a copy with a new name
            $copy = $original->replicate();
            $copy->name = $original->name . ' (Copy)';
            $copy->created_at = now();
            $copy->updated_at = now();
            $copy->save();

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-duplicated', ['projectId' => $copy->id]);
        } catch (\Exception $e) {
            Log::error('Failed to duplicate project: ' . $e->getMessage());
            $this->error = __('Failed to duplicate project');
        }
    }

    /**
     * Rename a project.
     */
    public function renameProject(int $projectId, string $newName): void
    {
        try {
            $newName = trim($newName);
            if (empty($newName)) {
                $this->error = __('Project name cannot be empty');
                return;
            }

            $project = WizardProject::findOrFail($projectId);
            $project->name = $newName;
            $project->save();

            // If this is the currently loaded project, update local state
            if ($this->projectId === $projectId) {
                $this->projectName = $newName;
            }

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-renamed', ['projectId' => $projectId, 'newName' => $newName]);
        } catch (\Exception $e) {
            Log::error('Failed to rename project: ' . $e->getMessage());
            $this->error = __('Failed to rename project');
        }
    }

    /**
     * Get the status of a project based on its data.
     */
    public function getProjectStatus(array $projectData): string
    {
        $concept = $projectData['concept'] ?? [];
        $script = $projectData['script'] ?? [];
        $storyboard = $projectData['storyboard'] ?? [];
        $animation = $projectData['animation'] ?? [];
        $assembly = $projectData['assembly'] ?? [];

        // Check if export/assembly is complete
        if (!empty($assembly) && isset($assembly['exported']) && $assembly['exported']) {
            return 'complete';
        }

        // Check if any work has been done beyond concept
        if (!empty($storyboard) || !empty($animation) || !empty($assembly)) {
            return 'in_progress';
        }

        // Check if script has scenes
        if (!empty($script) && isset($script['scenes']) && count($script['scenes'] ?? []) > 0) {
            return 'in_progress';
        }

        // Check if concept is filled
        if (!empty($concept) && !empty($concept['topic'] ?? '')) {
            return 'draft';
        }

        return 'draft';
    }

    // =========================================================================
    // STYLE TEMPLATE METHODS
    // =========================================================================

    /**
     * Apply a style template.
     */
    public function applyStyleTemplate(string $template): void
    {
        $templates = [
            'cinematic' => [
                'style' => 'Cinematic photorealistic photography, Hollywood blockbuster look, shot on ARRI Alexa',
                'colorGrade' => 'Teal and orange color grading, lifted blacks, cinematic LUT',
                'atmosphere' => 'Dramatic atmosphere, volumetric lighting, lens flares',
                'camera' => 'Anamorphic lenses, shallow depth of field, wide establishing shots',
                'visualDNA' => 'Epic scale, professional cinematography, Marvel quality visuals',
            ],
            'documentary' => [
                'style' => 'Documentary photography, authentic realism, natural lighting',
                'colorGrade' => 'Natural colors, slight desaturation, documentary grade',
                'atmosphere' => 'Authentic atmosphere, real-world environments',
                'camera' => 'Handheld camera feel, natural framing, observational style',
                'visualDNA' => 'Authentic, journalistic, National Geographic quality',
            ],
            'anime' => [
                'style' => 'Anime art style, cel-shaded, Japanese animation aesthetic',
                'colorGrade' => 'Vibrant saturated colors, anime color palette',
                'atmosphere' => 'Stylized atmosphere, dramatic lighting, expressive',
                'camera' => 'Dynamic angles, action lines, anime cinematography',
                'visualDNA' => 'Studio Ghibli quality, detailed backgrounds, expressive characters',
            ],
            'noir' => [
                'style' => 'Film noir style, black and white, high contrast',
                'colorGrade' => 'Monochrome, deep blacks, high contrast',
                'atmosphere' => 'Moody, mysterious, shadowy atmosphere',
                'camera' => 'Low-key lighting, dramatic shadows, Dutch angles',
                'visualDNA' => 'Classic film noir, 1940s aesthetic, detective movie quality',
            ],
            '3d' => [
                'style' => 'Pixar-style 3D animation, stylized 3D rendering',
                'colorGrade' => 'Vibrant colors, soft gradients, 3D render quality',
                'atmosphere' => 'Whimsical atmosphere, clean environments',
                'camera' => 'Smooth camera movements, 3D depth, cinematic framing',
                'visualDNA' => 'Pixar quality, Disney animation, high-end 3D render',
            ],
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['styleBible'] = array_merge(
                $this->sceneMemory['styleBible'],
                $templates[$template],
                ['enabled' => true]
            );
            $this->saveProject();
        }
    }

    // =========================================================================
    // STEP TRANSITION HOOK - AUTO-POPULATE SCENE MEMORY
    // =========================================================================

    /**
     * Auto-populate Scene Memory when entering Storyboard step.
     * This applies information from the Script step to Character Bible, Location Bible, and Style Bible.
     */
    protected function autoPopulateSceneMemory(): void
    {
        // Skip if already has characters or locations (don't override user edits)
        $hasExistingCharacters = !empty($this->sceneMemory['characterBible']['characters']);
        $hasExistingLocations = !empty($this->sceneMemory['locationBible']['locations']);
        $hasExistingStyle = !empty($this->sceneMemory['styleBible']['style']);

        // 1. Auto-populate Style Bible based on production type (if not already set)
        if (!$hasExistingStyle) {
            $this->transitionMessage = __('Setting up visual style...');
            $this->autoPopulateStyleBible();
        }

        // 2. Auto-detect characters from script (if none exist)
        if (!$hasExistingCharacters) {
            $this->transitionMessage = __('Detecting characters from script...');
            $this->autoDetectCharactersFromScript();
        }

        // 3. Auto-detect locations from script (if none exist)
        if (!$hasExistingLocations) {
            $this->transitionMessage = __('Identifying locations...');
            $this->autoDetectLocationsFromScript();
        }

        // Dispatch event to notify UI
        $this->dispatch('scene-memory-populated', [
            'characters' => count($this->sceneMemory['characterBible']['characters']),
            'locations' => count($this->sceneMemory['locationBible']['locations']),
            'styleBibleEnabled' => $this->sceneMemory['styleBible']['enabled'],
        ]);
    }

    /**
     * Auto-populate Style Bible based on production type, concept, and platform.
     * Creates comprehensive visual consistency settings for the entire video.
     */
    protected function autoPopulateStyleBible(): void
    {
        // Get base defaults from production type
        $styleDefaults = $this->getStyleBibleDefaultsForProductionType();

        // Enhance with concept data (mood, tone from AI concept refinement)
        $styleDefaults = $this->enhanceStyleWithConceptData($styleDefaults);

        // Add platform-specific optimizations
        $styleDefaults = $this->addPlatformOptimizations($styleDefaults);

        if (!empty($styleDefaults)) {
            $this->sceneMemory['styleBible'] = array_merge(
                $this->sceneMemory['styleBible'],
                $styleDefaults,
                ['enabled' => true]
            );

            // Also populate storyboard visualStyle for UI dropdowns
            $this->populateStoryboardVisualStyle($styleDefaults);
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'style_bible_populated',
            'productionType' => $this->productionType,
            'productionSubtype' => $this->productionSubtype,
            'platform' => $this->platform,
            'hasConcept' => !empty($this->concept['suggestedMood']),
        ]);
    }

    /**
     * Enhance style defaults with data from concept refinement.
     */
    protected function enhanceStyleWithConceptData(array $styleDefaults): array
    {
        $concept = $this->concept ?? [];

        // Apply suggested mood from concept
        if (!empty($concept['suggestedMood'])) {
            $moodStyles = $this->getMoodStyleEnhancements($concept['suggestedMood']);
            if (!empty($moodStyles)) {
                // Append mood-specific enhancements to atmosphere
                if (!empty($styleDefaults['atmosphere'])) {
                    $styleDefaults['atmosphere'] .= ', ' . $moodStyles['atmosphere'];
                } else {
                    $styleDefaults['atmosphere'] = $moodStyles['atmosphere'];
                }

                // Add mood-specific color adjustments
                if (!empty($moodStyles['colorAdjustment']) && !empty($styleDefaults['colorGrade'])) {
                    $styleDefaults['colorGrade'] .= ', ' . $moodStyles['colorAdjustment'];
                }
            }
        }

        // Apply suggested tone from concept
        if (!empty($concept['suggestedTone'])) {
            $toneStyles = $this->getToneStyleEnhancements($concept['suggestedTone']);
            if (!empty($toneStyles)) {
                // Enhance visual style with tone
                if (!empty($styleDefaults['style'])) {
                    $styleDefaults['style'] .= ', ' . $toneStyles['style'];
                }
            }
        }

        // Apply style reference from concept if available
        if (!empty($concept['styleReference'])) {
            $styleDefaults['visualDNA'] = ($styleDefaults['visualDNA'] ?? '') .
                ', inspired by: ' . $concept['styleReference'];
        }

        return $styleDefaults;
    }

    /**
     * Get mood-specific style enhancements.
     */
    protected function getMoodStyleEnhancements(string $mood): array
    {
        $mood = strtolower(trim($mood));

        $moodMap = [
            'inspiring' => [
                'atmosphere' => 'uplifting, hopeful, motivational lighting',
                'colorAdjustment' => 'warm golden tones, bright highlights',
                'lighting' => 'bright',
                'colorPalette' => 'warm',
            ],
            'mysterious' => [
                'atmosphere' => 'enigmatic, shadowy, intriguing',
                'colorAdjustment' => 'deep shadows, selective lighting',
                'lighting' => 'dramatic',
                'colorPalette' => 'cool',
            ],
            'energetic' => [
                'atmosphere' => 'dynamic, vibrant, high-energy',
                'colorAdjustment' => 'saturated colors, punchy contrast',
                'lighting' => 'bright',
                'colorPalette' => 'vibrant',
            ],
            'calm' => [
                'atmosphere' => 'peaceful, serene, meditative',
                'colorAdjustment' => 'soft pastels, gentle gradients',
                'lighting' => 'soft',
                'colorPalette' => 'pastel',
            ],
            'dramatic' => [
                'atmosphere' => 'intense, powerful, emotionally charged',
                'colorAdjustment' => 'high contrast, deep blacks',
                'lighting' => 'dramatic',
                'colorPalette' => 'rich',
            ],
            'playful' => [
                'atmosphere' => 'fun, whimsical, lighthearted',
                'colorAdjustment' => 'bright, cheerful colors',
                'lighting' => 'bright',
                'colorPalette' => 'vibrant',
            ],
            'nostalgic' => [
                'atmosphere' => 'warm memories, vintage feel, wistful',
                'colorAdjustment' => 'warm sepia tones, film grain effect',
                'lighting' => 'golden',
                'colorPalette' => 'warm',
            ],
            'professional' => [
                'atmosphere' => 'polished, confident, authoritative',
                'colorAdjustment' => 'clean, balanced colors',
                'lighting' => 'studio',
                'colorPalette' => 'neutral',
            ],
            'dark' => [
                'atmosphere' => 'moody, intense, brooding',
                'colorAdjustment' => 'desaturated, heavy shadows',
                'lighting' => 'low-key',
                'colorPalette' => 'dark',
            ],
            'romantic' => [
                'atmosphere' => 'intimate, warm, emotionally tender',
                'colorAdjustment' => 'soft warm tones, dreamy highlights',
                'lighting' => 'golden',
                'colorPalette' => 'warm',
            ],
        ];

        return $moodMap[$mood] ?? [];
    }

    /**
     * Get tone-specific style enhancements.
     */
    protected function getToneStyleEnhancements(string $tone): array
    {
        $tone = strtolower(trim($tone));

        $toneMap = [
            'professional' => ['style' => 'polished corporate aesthetic'],
            'casual' => ['style' => 'relaxed approachable visuals'],
            'humorous' => ['style' => 'playful bright comedic framing'],
            'serious' => ['style' => 'formal authoritative composition'],
            'engaging' => ['style' => 'dynamic attention-grabbing visuals'],
            'informative' => ['style' => 'clear educational presentation'],
            'conversational' => ['style' => 'friendly intimate framing'],
            'authoritative' => ['style' => 'commanding powerful presence'],
            'inspirational' => ['style' => 'uplifting heroic imagery'],
            'emotional' => ['style' => 'expressive intimate cinematography'],
        ];

        return $toneMap[$tone] ?? [];
    }

    /**
     * Add platform-specific optimizations to style.
     */
    protected function addPlatformOptimizations(array $styleDefaults): array
    {
        $platform = $this->platform ?? '';

        $platformOptimizations = [
            'youtube' => [
                'technicalNote' => 'optimized for YouTube, thumbnail-friendly compositions',
                'composition' => 'wide establishing shots, clear focal points',
            ],
            'instagram' => [
                'technicalNote' => 'Instagram-optimized, mobile-first visuals',
                'composition' => 'vertical-friendly framing, bold visuals',
                'colorAdjustment' => 'Instagram-aesthetic colors',
            ],
            'tiktok' => [
                'technicalNote' => 'TikTok-optimized, fast-paced visuals',
                'composition' => 'vertical format, dynamic movement',
                'colorAdjustment' => 'high contrast, trend-aware palette',
            ],
            'facebook' => [
                'technicalNote' => 'Facebook-optimized, feed-friendly',
                'composition' => 'clear focal points, text-safe zones',
            ],
            'linkedin' => [
                'technicalNote' => 'LinkedIn professional standards',
                'composition' => 'professional framing, business-appropriate',
                'colorAdjustment' => 'corporate color palette',
            ],
            'twitter' => [
                'technicalNote' => 'Twitter/X optimized, scroll-stopping',
                'composition' => 'impactful opening frames, clear messaging',
            ],
        ];

        if (isset($platformOptimizations[$platform])) {
            $opts = $platformOptimizations[$platform];

            // Add technical note to visualDNA
            if (!empty($opts['technicalNote'])) {
                $styleDefaults['visualDNA'] = ($styleDefaults['visualDNA'] ?? '') .
                    ', ' . $opts['technicalNote'];
            }

            // Store composition preference
            if (!empty($opts['composition'])) {
                $styleDefaults['platformComposition'] = $opts['composition'];
            }
        }

        return $styleDefaults;
    }

    /**
     * Populate storyboard visualStyle settings from Style Bible.
     */
    protected function populateStoryboardVisualStyle(array $styleDefaults): void
    {
        // Map Style Bible data to storyboard visualStyle dropdowns
        $concept = $this->concept ?? [];
        $suggestedMood = strtolower($concept['suggestedMood'] ?? '');

        // Get mood enhancements for dropdown values
        $moodEnhancements = $this->getMoodStyleEnhancements($suggestedMood);

        // Set mood dropdown
        if (!empty($moodEnhancements['lighting'])) {
            $this->storyboard['visualStyle']['lighting'] = $moodEnhancements['lighting'];
        }

        if (!empty($moodEnhancements['colorPalette'])) {
            $this->storyboard['visualStyle']['colorPalette'] = $moodEnhancements['colorPalette'];
        }

        // Set mood based on concept
        if (!empty($suggestedMood)) {
            $this->storyboard['visualStyle']['mood'] = $suggestedMood;
        }

        // Set composition from platform optimization
        if (!empty($styleDefaults['platformComposition'])) {
            $this->storyboard['visualStyle']['composition'] = $styleDefaults['platformComposition'];
        }
    }

    /**
     * Get Style Bible defaults based on production type.
     * Includes camera language and comprehensive visual settings.
     */
    protected function getStyleBibleDefaultsForProductionType(): array
    {
        $productionType = $this->productionType ?? '';
        $productionSubtype = $this->productionSubtype ?? '';

        $defaults = [
            'commercial' => [
                'style' => 'Professional commercial style, clean visuals, product-focused, high production value',
                'colorGrade' => 'Bright, vibrant colors, commercial quality, balanced exposure',
                'atmosphere' => 'Upbeat, modern, engaging atmosphere, aspirational',
                'camera' => 'Smooth dolly shots, product close-ups, clean compositions, studio lighting',
                'visualDNA' => 'High-end commercial production, Madison Avenue quality, broadcast-ready',
            ],
            'social_media' => [
                'style' => 'Dynamic social media style, eye-catching, trend-focused, thumb-stopping',
                'colorGrade' => 'High contrast, saturated colors, mobile-optimized, bold palette',
                'atmosphere' => 'Energetic, engaging, scroll-stopping, relatable',
                'camera' => 'Dynamic angles, quick cuts, selfie-style, handheld energy',
                'visualDNA' => 'Viral content quality, platform-native aesthetic, share-worthy',
            ],
            'educational' => [
                'style' => 'Clear educational style, informative visuals, well-organized, accessible',
                'colorGrade' => 'Neutral colors, good contrast for readability, balanced',
                'atmosphere' => 'Professional, trustworthy, accessible, approachable',
                'camera' => 'Steady shots, clear framing, presenter-focused, diagram-friendly',
                'visualDNA' => 'Documentary quality, educational content standard, TED-talk aesthetic',
            ],
            'entertainment' => [
                'style' => 'Cinematic entertainment style, dramatic visuals, theatrical quality',
                'colorGrade' => 'Film-quality color grading, Hollywood look, rich tones',
                'atmosphere' => 'Immersive, engaging, theatrical, emotionally resonant',
                'camera' => 'Cinematic movements, dramatic angles, depth of field, ARRI-style',
                'visualDNA' => 'Netflix quality, premium streaming standard, binge-worthy',
            ],
            'corporate' => [
                'style' => 'Professional corporate style, polished visuals, brand-aligned',
                'colorGrade' => 'Clean, professional color palette, brand-consistent',
                'atmosphere' => 'Trustworthy, sophisticated, business-appropriate, confident',
                'camera' => 'Steady corporate shots, executive framing, office environments',
                'visualDNA' => 'Fortune 500 quality, executive presentation standard, investor-ready',
            ],
            'music_video' => [
                'style' => 'Creative music video style, artistic visuals, rhythm-driven',
                'colorGrade' => 'Bold color choices, artistic grading, mood-driven palette',
                'atmosphere' => 'Rhythmic, expressive, genre-appropriate, visceral',
                'camera' => 'Creative movements, beat-synced, performance shots, artistic angles',
                'visualDNA' => 'MTV quality, artistic music visual standard, chart-topping aesthetic',
            ],
            'documentary' => [
                'style' => 'Documentary style, authentic visuals, journalistic integrity',
                'colorGrade' => 'Natural color grading, realistic tones, authentic look',
                'atmosphere' => 'Authentic, immersive, story-driven, truthful',
                'camera' => 'Handheld authenticity, interview setups, b-roll rich, observational',
                'visualDNA' => 'HBO Documentary quality, cinéma vérité standard, award-worthy',
            ],
            'animation' => [
                'style' => 'Animated style, stylized visuals, character-driven, expressive',
                'colorGrade' => 'Vibrant animation colors, stylized palette, bold choices',
                'atmosphere' => 'Whimsical, expressive, visually dynamic, imaginative',
                'camera' => 'Virtual camera movements, impossible angles, smooth transitions',
                'visualDNA' => 'Pixar quality, premium animation standard, family-friendly',
            ],
            'lifestyle' => [
                'style' => 'Lifestyle aesthetic, aspirational visuals, authentic moments',
                'colorGrade' => 'Warm, inviting colors, Instagram-worthy palette',
                'atmosphere' => 'Relatable, aspirational, warm, inviting',
                'camera' => 'Natural light preference, candid moments, lifestyle b-roll',
                'visualDNA' => 'Influencer quality, lifestyle brand aesthetic, Pinterest-worthy',
            ],
            'product' => [
                'style' => 'Product showcase style, detail-focused, premium presentation',
                'colorGrade' => 'Clean whites, accurate colors, studio quality',
                'atmosphere' => 'Premium, desirable, detailed, luxurious',
                'camera' => 'Macro details, 360 rotations, studio lighting, product hero shots',
                'visualDNA' => 'Apple-quality product visuals, e-commerce premium, catalog-ready',
            ],
            'testimonial' => [
                'style' => 'Authentic testimonial style, trustworthy, personal connection',
                'colorGrade' => 'Natural skin tones, warm and inviting, professional',
                'atmosphere' => 'Genuine, trustworthy, relatable, convincing',
                'camera' => 'Interview framing, eye-level connection, comfortable distance',
                'visualDNA' => 'Customer story quality, social proof aesthetic, trust-building',
            ],
        ];

        // Comprehensive subtype-specific overrides with camera
        $subtypeDefaults = [
            'action' => [
                'style' => 'High-energy action style, dynamic camera work, intense visuals',
                'colorGrade' => 'Desaturated with punchy highlights, action movie look',
                'atmosphere' => 'Intense, adrenaline-pumping, explosive',
                'camera' => 'Fast tracking shots, crash zooms, impact angles, shaky-cam energy',
            ],
            'comedy' => [
                'style' => 'Bright comedy style, well-lit, inviting, comedic timing',
                'colorGrade' => 'Warm, friendly colors, sitcom aesthetic',
                'atmosphere' => 'Light-hearted, fun, accessible',
                'camera' => 'Wide comedy frames, reaction shots, timing-focused cuts',
            ],
            'drama' => [
                'style' => 'Dramatic cinematic style, emotional lighting, character-focused',
                'colorGrade' => 'Rich, moody color grading, prestige TV look',
                'atmosphere' => 'Emotional, immersive, character-focused',
                'camera' => 'Intimate close-ups, slow reveals, emotional beats, shallow DOF',
            ],
            'horror' => [
                'style' => 'Dark horror style, unsettling visuals, tension-building',
                'colorGrade' => 'Desaturated, cold tones, high contrast shadows',
                'atmosphere' => 'Tense, unsettling, atmospheric dread',
                'camera' => 'Creeping movements, POV horror, jump scare setups, off-kilter angles',
            ],
            'sci-fi' => [
                'style' => 'Futuristic sci-fi style, high-tech visuals, otherworldly',
                'colorGrade' => 'Cool blues and teals, neon accents, tech aesthetic',
                'atmosphere' => 'Futuristic, immersive, technologically advanced',
                'camera' => 'Smooth glides, HUD overlays, vast establishing shots, tech details',
            ],
            'fantasy' => [
                'style' => 'Epic fantasy style, magical visuals, mythical grandeur',
                'colorGrade' => 'Rich saturated colors, ethereal tones, golden magic',
                'atmosphere' => 'Magical, epic, otherworldly',
                'camera' => 'Sweeping vistas, hero shots, magical reveals, epic scale',
            ],
            'thriller' => [
                'style' => 'Suspenseful thriller style, tension-building visuals',
                'colorGrade' => 'Cold, clinical tones with warm accent pops',
                'atmosphere' => 'Suspenseful, paranoid, edge-of-seat tension',
                'camera' => 'Slow push-ins, surveillance angles, claustrophobic framing',
            ],
            'romance' => [
                'style' => 'Romantic visual style, soft and dreamy, intimate',
                'colorGrade' => 'Warm, soft focus, romantic glow, skin-flattering',
                'atmosphere' => 'Intimate, warm, emotionally tender',
                'camera' => 'Soft focus close-ups, two-shots, golden hour preference',
            ],
            'sports' => [
                'style' => 'Dynamic sports style, high-energy, athletic',
                'colorGrade' => 'High contrast, energetic colors, broadcast quality',
                'atmosphere' => 'Competitive, exciting, triumphant',
                'camera' => 'Super slow-mo, tracking athletes, victory moments, wide action',
            ],
            'travel' => [
                'style' => 'Travel documentary style, wanderlust-inducing, exploration',
                'colorGrade' => 'Natural vibrant colors, location-authentic palette',
                'atmosphere' => 'Adventurous, inspiring, culturally rich',
                'camera' => 'Drone aerials, ground-level exploration, local details, golden hour',
            ],
        ];

        // Start with production type defaults
        $result = $defaults[$productionType] ?? $defaults['entertainment'];

        // Merge subtype overrides if available
        if ($productionSubtype && isset($subtypeDefaults[$productionSubtype])) {
            $result = array_merge($result, $subtypeDefaults[$productionSubtype]);
        }

        return $result;
    }

    /**
     * Auto-detect characters from script content using AI extraction.
     * Falls back to pattern matching if AI fails.
     */
    protected function autoDetectCharactersFromScript(): void
    {
        // Try AI-powered extraction first
        try {
            $service = app(CharacterExtractionService::class);

            $result = $service->extractCharacters($this->script, [
                'teamId' => session('current_team_id', 0),
                'genre' => $this->productionType ?? 'General',
                'productionType' => $this->productionType,
                'productionMode' => 'standard',
                'styleBible' => $this->sceneMemory['styleBible'] ?? null,
            ]);

            if ($result['success'] && !empty($result['characters'])) {
                Log::info('CharacterExtraction: AI extraction successful', [
                    'count' => count($result['characters']),
                ]);

                // Add AI-extracted characters to Character Bible
                foreach ($result['characters'] as $character) {
                    // Check if already exists
                    $exists = collect($this->sceneMemory['characterBible']['characters'])
                        ->where('name', $character['name'])
                        ->isNotEmpty();

                    if (!$exists) {
                        $this->sceneMemory['characterBible']['characters'][] = [
                            'id' => $character['id'] ?? uniqid('char_'),
                            'name' => $character['name'],
                            'description' => $character['description'] ?? '',
                            'role' => $character['role'] ?? 'Supporting',
                            'appliedScenes' => $character['appearsInScenes'] ?? [],
                            'traits' => $character['traits'] ?? [],
                            'referenceImage' => null,
                            'autoDetected' => true,
                            'aiGenerated' => true,
                        ];
                    }
                }

                // Enable Character Bible if we detected any characters
                if (!empty($result['characters'])) {
                    $this->sceneMemory['characterBible']['enabled'] = true;
                }

                // Dispatch event for debugging
                $this->dispatch('vw-debug', [
                    'type' => 'character_extraction',
                    'method' => 'ai',
                    'count' => count($result['characters']),
                    'hasHumanCharacters' => $result['hasHumanCharacters'],
                ]);

                return; // AI extraction successful, no need for fallback
            }

            // If AI returned no characters but was successful, check if video has no human characters
            if ($result['success'] && empty($result['characters']) && !$result['hasHumanCharacters']) {
                Log::info('CharacterExtraction: AI determined no human characters in video');
                $this->dispatch('vw-debug', [
                    'type' => 'character_extraction',
                    'method' => 'ai',
                    'count' => 0,
                    'message' => 'No human characters detected',
                ]);
                return;
            }

        } catch (\Exception $e) {
            Log::warning('CharacterExtraction: AI extraction failed, falling back to pattern matching', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to pattern-based detection
        $this->autoDetectCharactersWithPatterns();
    }

    /**
     * Pattern-based character detection (fallback method).
     */
    protected function autoDetectCharactersWithPatterns(): void
    {
        $detectedCharacters = [];
        $characterScenes = []; // Track which scenes each character appears in

        // Common character indicators
        $characterPatterns = [
            // Named roles
            '/\b(the\s+)?(protagonist|hero|heroine|narrator|speaker|presenter|host|expert|customer|client|user|employee|manager|CEO|founder|leader|teacher|student|doctor|nurse|chef|artist)\b/i',
            // Personal pronouns with context (he, she, they followed by verbs)
            '/\b(he|she|they)\s+(is|are|was|were|walks?|runs?|speaks?|says?|looks?|stands?|sits?)\b/i',
            // A/The person descriptions
            '/\b(a|the)\s+(young|old|middle-aged|professional|business|confident|mysterious|elegant)\s+(man|woman|person|figure|individual)\b/i',
            // Proper names (capitalized words that could be names)
            '/\b([A-Z][a-z]+)\s+(says?|speaks?|walks?|looks?|appears?|enters?|exits?|stands?)\b/',
        ];

        foreach ($this->script['scenes'] as $sceneIndex => $scene) {
            $sceneText = '';

            // Combine all text sources
            if (!empty($scene['narration'])) {
                $sceneText .= ' ' . $scene['narration'];
            }
            if (!empty($scene['visualDescription'])) {
                $sceneText .= ' ' . $scene['visualDescription'];
            }
            if (!empty($scene['visual'])) {
                $sceneText .= ' ' . $scene['visual'];
            }

            // Check for dialogue speakers
            if (isset($scene['dialogue']) && is_array($scene['dialogue'])) {
                foreach ($scene['dialogue'] as $dialogue) {
                    $speaker = $dialogue['speaker'] ?? null;
                    if ($speaker) {
                        $normalizedName = ucfirst(strtolower(trim($speaker)));
                        if (!isset($detectedCharacters[$normalizedName])) {
                            $detectedCharacters[$normalizedName] = [
                                'name' => $normalizedName,
                                'description' => '',
                                'source' => 'dialogue',
                            ];
                            $characterScenes[$normalizedName] = [];
                        }
                        if (!in_array($sceneIndex, $characterScenes[$normalizedName])) {
                            $characterScenes[$normalizedName][] = $sceneIndex;
                        }
                    }
                }
            }

            // Detect characters from text patterns
            foreach ($characterPatterns as $pattern) {
                if (preg_match_all($pattern, $sceneText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $characterName = $this->normalizeCharacterName($match[0]);
                        if ($characterName && strlen($characterName) > 2) {
                            if (!isset($detectedCharacters[$characterName])) {
                                $detectedCharacters[$characterName] = [
                                    'name' => $characterName,
                                    'description' => $this->inferCharacterDescription($sceneText, $characterName),
                                    'source' => 'pattern',
                                ];
                                $characterScenes[$characterName] = [];
                            }
                            if (!in_array($sceneIndex, $characterScenes[$characterName])) {
                                $characterScenes[$characterName][] = $sceneIndex;
                            }
                        }
                    }
                }
            }
        }

        // Add detected characters to Character Bible
        foreach ($detectedCharacters as $name => $data) {
            // Check if already exists
            $exists = collect($this->sceneMemory['characterBible']['characters'])
                ->where('name', $name)
                ->isNotEmpty();

            if (!$exists) {
                $this->sceneMemory['characterBible']['characters'][] = [
                    'id' => uniqid('char_'),
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'appliedScenes' => $characterScenes[$name] ?? [],
                    'referenceImage' => null,
                    'autoDetected' => true,
                    'patternMatched' => true,
                ];
            }
        }

        // Enable Character Bible if we detected any characters
        if (!empty($detectedCharacters)) {
            $this->sceneMemory['characterBible']['enabled'] = true;
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'character_extraction',
            'method' => 'pattern',
            'count' => count($detectedCharacters),
        ]);
    }

    /**
     * Normalize character name from pattern match.
     */
    protected function normalizeCharacterName(string $match): ?string
    {
        // Remove articles and clean up
        $name = preg_replace('/^(the|a|an)\s+/i', '', trim($match));
        $name = preg_replace('/\s+(says?|speaks?|walks?|looks?|appears?|enters?|exits?|stands?|sits?|is|are|was|were|runs?).*$/i', '', $name);
        $name = trim($name);

        // Capitalize properly
        $name = ucwords(strtolower($name));

        // Skip if too short or just a pronoun
        $skipWords = ['he', 'she', 'they', 'it', 'we', 'you', 'i'];
        if (strlen($name) < 3 || in_array(strtolower($name), $skipWords)) {
            return null;
        }

        return $name;
    }

    /**
     * Infer character description from context.
     */
    protected function inferCharacterDescription(string $text, string $characterName): string
    {
        // Look for descriptive phrases near the character name
        $patterns = [
            '/\b' . preg_quote($characterName, '/') . '\s*,?\s*(a\s+)?([\w\s]+(?:man|woman|person|figure))/i',
            '/\b([\w\s]+(?:man|woman|person))\s+(?:named|called)\s+' . preg_quote($characterName, '/') . '/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return ucfirst(trim($match[0]));
            }
        }

        return ''; // Return empty if no description found
    }

    /**
     * Auto-detect locations from script content using AI extraction.
     * Falls back to pattern matching if AI fails.
     */
    protected function autoDetectLocationsFromScript(): void
    {
        // Try AI-powered extraction first
        try {
            $service = app(LocationExtractionService::class);

            $result = $service->extractLocations($this->script, [
                'teamId' => session('current_team_id', 0),
                'genre' => $this->productionType ?? 'General',
                'productionType' => $this->productionType,
                'productionMode' => 'standard',
                'styleBible' => $this->sceneMemory['styleBible'] ?? null,
            ]);

            if ($result['success'] && !empty($result['locations'])) {
                Log::info('LocationExtraction: AI extraction successful', [
                    'count' => count($result['locations']),
                ]);

                // Add AI-extracted locations to Location Bible
                foreach ($result['locations'] as $location) {
                    // Check if already exists
                    $exists = collect($this->sceneMemory['locationBible']['locations'])
                        ->filter(fn($loc) => strtolower($loc['name'] ?? '') === strtolower($location['name']))
                        ->isNotEmpty();

                    if (!$exists) {
                        $this->sceneMemory['locationBible']['locations'][] = [
                            'id' => $location['id'] ?? uniqid('loc_'),
                            'name' => $location['name'],
                            'description' => $location['description'] ?? '',
                            'type' => $location['type'] ?? 'exterior',
                            'timeOfDay' => $location['timeOfDay'] ?? 'day',
                            'weather' => $location['weather'] ?? 'clear',
                            'atmosphere' => $location['atmosphere'] ?? '',
                            'scenes' => $location['scenes'] ?? [],
                            'stateChanges' => $location['stateChanges'] ?? [],
                            'referenceImage' => null,
                            'autoDetected' => true,
                            'aiGenerated' => true,
                        ];
                    }
                }

                // Enable Location Bible if we detected any locations
                if (!empty($result['locations'])) {
                    $this->sceneMemory['locationBible']['enabled'] = true;
                }

                // Dispatch event for debugging
                $this->dispatch('vw-debug', [
                    'type' => 'location_extraction',
                    'method' => 'ai',
                    'count' => count($result['locations']),
                ]);

                return; // AI extraction successful, no need for fallback
            }

            // If AI returned no locations but was successful, video might be abstract
            if ($result['success'] && empty($result['locations'])) {
                Log::info('LocationExtraction: AI determined no distinct locations in video');
                $this->dispatch('vw-debug', [
                    'type' => 'location_extraction',
                    'method' => 'ai',
                    'count' => 0,
                    'message' => 'No distinct locations detected',
                ]);
                return;
            }

        } catch (\Exception $e) {
            Log::warning('LocationExtraction: AI extraction failed, falling back to pattern matching', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to pattern-based detection
        $this->autoDetectLocationsWithPatterns();
    }

    /**
     * Pattern-based location detection (fallback method).
     */
    protected function autoDetectLocationsWithPatterns(): void
    {
        $locationMap = [];

        foreach ($this->script['scenes'] as $sceneIndex => $scene) {
            $visual = $scene['visualDescription'] ?? $scene['visual'] ?? '';
            $narration = $scene['narration'] ?? '';
            $fullText = $visual . ' ' . $narration;

            if (empty(trim($fullText))) {
                continue;
            }

            // Infer location from visual description
            $locationName = $this->inferLocationFromVisual($fullText);
            $normalizedName = strtolower(trim($locationName));

            if ($locationName && $locationName !== 'Unknown') {
                if (!isset($locationMap[$normalizedName])) {
                    $locationMap[$normalizedName] = [
                        'name' => $locationName,
                        'type' => $this->inferLocationType($fullText),
                        'timeOfDay' => $this->inferTimeOfDay($fullText),
                        'weather' => $this->inferWeather($fullText),
                        'description' => $this->extractLocationDescription($visual),
                        'scenes' => [],
                    ];
                }
                $locationMap[$normalizedName]['scenes'][] = $sceneIndex;
            }
        }

        // Add detected locations to Location Bible
        foreach ($locationMap as $normalizedName => $data) {
            // Check if already exists
            $exists = collect($this->sceneMemory['locationBible']['locations'])
                ->filter(fn($loc) => strtolower($loc['name'] ?? '') === $normalizedName)
                ->isNotEmpty();

            if (!$exists) {
                $this->sceneMemory['locationBible']['locations'][] = [
                    'id' => uniqid('loc_'),
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'timeOfDay' => $data['timeOfDay'],
                    'weather' => $data['weather'],
                    'description' => $data['description'],
                    'scenes' => $data['scenes'],
                    'referenceImage' => null,
                    'autoDetected' => true,
                    'patternMatched' => true,
                ];
            }
        }

        // Enable Location Bible if we detected any locations
        if (!empty($locationMap)) {
            $this->sceneMemory['locationBible']['enabled'] = true;
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'location_extraction',
            'method' => 'pattern',
            'count' => count($locationMap),
        ]);
    }

    /**
     * Infer location name from visual description using pattern matching.
     * Based on the original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferLocationFromVisual
     */
    protected function inferLocationFromVisual(string $visual): string
    {
        if (empty($visual)) {
            return 'Unknown';
        }

        $v = strtolower($visual);

        // Location patterns (most specific first)
        $locationPatterns = [
            ['pattern' => '/\b(dojo|training hall|martial arts)\b/i', 'name' => 'The Dojo'],
            ['pattern' => '/\b(boardroom|conference room|meeting room)\b/i', 'name' => 'Boardroom'],
            ['pattern' => '/\b(office|corporate|workspace|desk)\b/i', 'name' => 'Office'],
            ['pattern' => '/\b(warehouse|factory|industrial|abandoned building)\b/i', 'name' => 'Warehouse'],
            ['pattern' => '/\b(forest|woods|trees|jungle|nature trail)\b/i', 'name' => 'Forest'],
            ['pattern' => '/\b(street|alley|urban|city|downtown|sidewalk)\b/i', 'name' => 'City Streets'],
            ['pattern' => '/\b(rooftop|roof|skyline|terrace)\b/i', 'name' => 'Rooftop'],
            ['pattern' => '/\b(beach|shore|coast|ocean|sea|waves)\b/i', 'name' => 'Beach'],
            ['pattern' => '/\b(lab|laboratory|research|science|experiment)\b/i', 'name' => 'Laboratory'],
            ['pattern' => '/\b(home|house|apartment|living room|bedroom|kitchen)\b/i', 'name' => 'Home Interior'],
            ['pattern' => '/\b(hospital|medical|clinic|emergency room)\b/i', 'name' => 'Hospital'],
            ['pattern' => '/\b(bar|pub|club|restaurant|cafe|coffee shop)\b/i', 'name' => 'Restaurant/Bar'],
            ['pattern' => '/\b(castle|fortress|palace|throne|medieval)\b/i', 'name' => 'Castle'],
            ['pattern' => '/\b(cave|cavern|underground|tunnel)\b/i', 'name' => 'Cave'],
            ['pattern' => '/\b(ship|boat|vessel|deck|yacht|cruise)\b/i', 'name' => 'Ship/Boat'],
            ['pattern' => '/\b(mountain|peak|summit|cliff|hiking)\b/i', 'name' => 'Mountain'],
            ['pattern' => '/\b(park|garden|lawn|outdoor|backyard)\b/i', 'name' => 'Park/Garden'],
            ['pattern' => '/\b(studio|stage|set|backdrop|production)\b/i', 'name' => 'Studio'],
            ['pattern' => '/\b(gym|fitness|workout|training|exercise)\b/i', 'name' => 'Gym'],
            ['pattern' => '/\b(school|classroom|university|campus|lecture)\b/i', 'name' => 'School/University'],
            ['pattern' => '/\b(airport|terminal|airplane|flight|gate)\b/i', 'name' => 'Airport'],
            ['pattern' => '/\b(hotel|lobby|reception|suite|resort)\b/i', 'name' => 'Hotel'],
            ['pattern' => '/\b(store|shop|retail|mall|shopping)\b/i', 'name' => 'Retail Store'],
            ['pattern' => '/\b(highway|road|driving|car|vehicle)\b/i', 'name' => 'Highway/Road'],
            ['pattern' => '/\b(desert|sand|dunes|arid|dry)\b/i', 'name' => 'Desert'],
            ['pattern' => '/\b(space|spacecraft|spaceship|stars|galaxy|cosmos)\b/i', 'name' => 'Space'],
            ['pattern' => '/\b(farm|barn|rural|countryside|fields|crops)\b/i', 'name' => 'Farm/Rural'],
        ];

        foreach ($locationPatterns as $item) {
            if (preg_match($item['pattern'], $v)) {
                return $item['name'];
            }
        }

        return 'General Location';
    }

    /**
     * Infer location type (interior/exterior) from text.
     */
    protected function inferLocationType(string $text): string
    {
        $t = strtolower($text);

        $interiorKeywords = ['inside', 'interior', 'indoor', 'room', 'office', 'home', 'building', 'house', 'apartment', 'studio', 'lab', 'hospital', 'hotel', 'restaurant', 'bar', 'store', 'mall'];
        $exteriorKeywords = ['outside', 'exterior', 'outdoor', 'street', 'park', 'beach', 'mountain', 'forest', 'ocean', 'sky', 'rooftop', 'desert', 'highway', 'road'];

        foreach ($interiorKeywords as $keyword) {
            if (strpos($t, $keyword) !== false) {
                return 'interior';
            }
        }

        foreach ($exteriorKeywords as $keyword) {
            if (strpos($t, $keyword) !== false) {
                return 'exterior';
            }
        }

        return 'exterior'; // Default to exterior
    }

    /**
     * Infer time of day from text context.
     * Based on original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferTimeOfDay
     */
    protected function inferTimeOfDay(string $text): string
    {
        $t = strtolower($text);

        if (preg_match('/\b(dawn|sunrise|first light|early morning)\b/', $t)) {
            return 'dawn';
        }
        if (preg_match('/\b(morning|bright day|fresh day)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(noon|midday|harsh sun|overhead sun)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(afternoon|warm light|late day)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(golden hour|sunset|orange light|evening sun)\b/', $t)) {
            return 'golden-hour';
        }
        if (preg_match('/\b(dusk|twilight|fading light)\b/', $t)) {
            return 'dusk';
        }
        if (preg_match('/\b(night|darkness|moonlight|stars|neon|midnight|deep night)\b/', $t)) {
            return 'night';
        }

        return 'day'; // Default
    }

    /**
     * Infer weather from text context.
     * Based on original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferWeather
     */
    protected function inferWeather(string $text): string
    {
        $t = strtolower($text);

        if (preg_match('/\b(storm|thunder|lightning)\b/', $t)) {
            return 'stormy';
        }
        if (preg_match('/\b(heavy rain|downpour|torrential)\b/', $t)) {
            return 'rainy';
        }
        if (preg_match('/\b(rain|drizzle|wet|raining)\b/', $t)) {
            return 'rainy';
        }
        if (preg_match('/\b(fog|mist|haze|foggy)\b/', $t)) {
            return 'foggy';
        }
        if (preg_match('/\b(snow|blizzard|frost|winter|cold)\b/', $t)) {
            return 'snowy';
        }
        if (preg_match('/\b(cloudy|overcast|grey sky)\b/', $t)) {
            return 'cloudy';
        }
        if (preg_match('/\b(sunny|bright|clear sky|blue sky)\b/', $t)) {
            return 'clear';
        }

        return 'clear'; // Default
    }

    /**
     * Extract location description from visual text.
     */
    protected function extractLocationDescription(string $visual): string
    {
        // Clean up and truncate for description
        $description = trim($visual);

        if (strlen($description) > 200) {
            $description = substr($description, 0, 197) . '...';
        }

        return $description;
    }

    // =========================================================================
    // CHARACTER BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect characters from script.
     */
    public function autoDetectCharacters(): void
    {
        // Use AI-powered extraction (with pattern fallback)
        $this->autoDetectCharactersFromScript();
        $this->saveProject();
    }

    /**
     * Edit a character.
     */
    public function editCharacter(int $index): void
    {
        $this->editingCharacterIndex = $index;
    }

    /**
     * Open Character Bible modal.
     */
    public function openCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = true;
        $this->editingCharacterIndex = 0;
    }

    /**
     * Close Character Bible modal.
     */
    public function closeCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = false;
    }

    /**
     * Toggle character scene assignment.
     */
    public function toggleCharacterScene(int $charIndex, int $sceneIndex): void
    {
        $appliedScenes = $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] ?? [];

        if (in_array($sceneIndex, $appliedScenes)) {
            $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] = array_values(
                array_diff($appliedScenes, [$sceneIndex])
            );
        } else {
            $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'][] = $sceneIndex;
        }

        $this->saveProject();
    }

    /**
     * Apply character to all scenes.
     */
    public function applyCharacterToAllScenes(int $charIndex): void
    {
        $sceneCount = count($this->script['scenes'] ?? []);
        $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] = range(0, $sceneCount - 1);
        $this->saveProject();
    }

    /**
     * Remove character portrait.
     */
    public function removeCharacterPortrait(int $index): void
    {
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = null;
        $this->saveProject();
    }

    /**
     * Upload a reference image for a character.
     */
    public function uploadCharacterPortrait(int $index): void
    {
        if (!$this->characterImageUpload) {
            return;
        }

        $this->validate([
            'characterImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'character_' . uniqid() . '_' . time() . '.' . $this->characterImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->characterImageUpload->storeAs('wizard-assets/characters', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Update character with the uploaded image
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = $url;
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageSource'] = 'upload';

            // Clear the upload
            $this->characterImageUpload = null;

            $this->saveProject();

            // Dispatch debug event
            $this->dispatch('vw-debug', [
                'type' => 'character_image_upload',
                'characterIndex' => $index,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error('Character image upload failed', ['error' => $e->getMessage()]);
            $this->error = __('Failed to upload image: ') . $e->getMessage();
        }
    }

    /**
     * Apply character template.
     */
    public function applyCharacterTemplate(int $index, string $template): void
    {
        $templates = [
            'action-hero' => 'Athletic build, confident stance, determined expression, wearing practical tactical clothing, strong jawline, focused eyes',
            'tech-pro' => 'Smart casual attire, clean-cut appearance, glasses optional, modern hairstyle, professional demeanor, laptop or tablet nearby',
            'mysterious' => 'Dark clothing, partially obscured face, enigmatic expression, shadows across features, subtle accessories, intriguing presence',
            'narrator' => 'Friendly approachable appearance, warm smile, neutral professional clothing, trustworthy expression, good lighting on face',
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['characterBible']['characters'][$index]['description'] = $templates[$template];
            $this->saveProject();
        }
    }

    /**
     * Generate character portrait.
     */
    public function generateCharacterPortrait(int $index): void
    {
        $character = $this->sceneMemory['characterBible']['characters'][$index] ?? null;
        if (!$character) return;

        $this->isLoading = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            // Build portrait-optimized prompt (matching reference implementation)
            $promptParts = [
                'Professional studio portrait photograph',
                $character['description'],
                'Standing pose facing camera',
                'Clean pure white background',
                'Professional studio lighting with soft shadows',
                'High quality, detailed, sharp focus',
                'Full body visible from head to feet',
                'Neutral expression, confident pose',
                'Fashion photography style, catalog quality',
            ];
            $prompt = implode('. ', $promptParts);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $character['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Portraits don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = $result['imageUrl'];
                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate portrait: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // STYLE BIBLE REFERENCE IMAGE METHODS
    // =========================================================================

    /**
     * Upload a reference image for Style Bible.
     */
    public function uploadStyleReference(): void
    {
        if (!$this->styleImageUpload) {
            return;
        }

        $this->validate([
            'styleImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'style_ref_' . uniqid() . '_' . time() . '.' . $this->styleImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->styleImageUpload->storeAs('wizard-assets/styles', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Update Style Bible with the uploaded image
            $this->sceneMemory['styleBible']['referenceImage'] = $url;
            $this->sceneMemory['styleBible']['referenceImageSource'] = 'upload';

            // Clear the upload
            $this->styleImageUpload = null;

            $this->saveProject();
        } catch (\Exception $e) {
            $this->error = __('Failed to upload style reference: ') . $e->getMessage();
        }
    }

    /**
     * Generate style reference image based on Style Bible settings.
     */
    public function generateStyleReference(): void
    {
        $styleBible = $this->sceneMemory['styleBible'] ?? [];

        // Build prompt from style bible fields
        $parts = [];
        if (!empty($styleBible['style'])) {
            $parts[] = $styleBible['style'];
        }
        if (!empty($styleBible['colorGrade'])) {
            $parts[] = $styleBible['colorGrade'];
        }
        if (!empty($styleBible['atmosphere'])) {
            $parts[] = $styleBible['atmosphere'];
        }
        if (!empty($styleBible['camera'])) {
            $parts[] = $styleBible['camera'];
        }
        if (!empty($styleBible['visualDNA'])) {
            $parts[] = $styleBible['visualDNA'];
        }

        if (empty($parts)) {
            $this->error = __('Please fill in some style settings before generating a reference.');
            return;
        }

        $this->isGeneratingStyleRef = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            // Build style reference prompt
            $prompt = "Style reference, visual mood board, " . implode(', ', $parts);
            $prompt .= ", cinematic, artistic composition, reference image";

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => 'style_ref_' . uniqid(),
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Style references don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $this->sceneMemory['styleBible']['referenceImage'] = $result['imageUrl'];
                        $this->sceneMemory['styleBible']['referenceImageSource'] = 'ai';
                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate style reference: ') . $e->getMessage();
        } finally {
            $this->isGeneratingStyleRef = false;
        }
    }

    /**
     * Remove style reference image.
     */
    public function removeStyleReference(): void
    {
        $this->sceneMemory['styleBible']['referenceImage'] = '';
        $this->sceneMemory['styleBible']['referenceImageSource'] = '';
        $this->saveProject();
    }

    // =========================================================================
    // LOCATION BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect locations from script.
     */
    public function autoDetectLocations(): void
    {
        // Use AI-powered extraction (with pattern fallback)
        $this->autoDetectLocationsFromScript();
        $this->saveProject();
    }

    /**
     * Apply location template.
     */
    public function applyLocationTemplate(string $template): void
    {
        $templates = [
            'urban' => [
                'name' => 'Urban City',
                'type' => 'exterior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'energetic',
                'description' => 'Modern cityscape, tall buildings, neon lights, busy streets, urban environment',
                'lightingStyle' => 'Neon signs with wet surface reflections',
            ],
            'urban-night' => [
                'name' => 'Urban Night',
                'type' => 'exterior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Dark city streets at night, neon signs, rain-slicked pavement, atmospheric fog, cyberpunk aesthetic',
                'lightingStyle' => 'Neon signs reflecting on wet surfaces, dramatic shadows, colorful lighting',
            ],
            'forest' => [
                'name' => 'Forest',
                'type' => 'exterior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'peaceful',
                'description' => 'Dense forest, tall trees, dappled sunlight, natural environment, lush vegetation',
                'lightingStyle' => 'Dappled sunlight through leaves, natural golden light',
            ],
            'tech-lab' => [
                'name' => 'Tech Lab',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'High-tech laboratory, advanced equipment, holographic displays, sterile white surfaces, futuristic design',
                'lightingStyle' => 'Clean LED lighting, blue accent lights, holographic glow',
            ],
            'desert' => [
                'name' => 'Desert Sunset',
                'type' => 'exterior',
                'timeOfDay' => 'golden-hour',
                'weather' => 'clear',
                'mood' => 'peaceful',
                'description' => 'Vast desert landscape, sand dunes, dramatic sunset sky, warm orange and red colors, endless horizon',
                'lightingStyle' => 'Golden hour warmth, long shadows, dramatic sky colors',
            ],
            'industrial' => [
                'name' => 'Industrial',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'tense',
                'description' => 'Industrial factory interior, metal structures, pipes, machinery, warehouse atmosphere, gritty textures',
                'lightingStyle' => 'Harsh overhead lighting, dramatic shadows, dust particles in light',
            ],
            'space' => [
                'name' => 'Space Station',
                'type' => 'interior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Futuristic space station interior, curved corridors, control panels, view of stars through windows, zero-gravity elements',
                'lightingStyle' => 'Ambient blue-white lighting, starlight through windows, holographic displays',
            ],
            'office' => [
                'name' => 'Office',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'Modern office interior, clean design, glass walls, professional workspace',
                'lightingStyle' => 'Soft diffused lighting, natural light from windows',
            ],
            'studio' => [
                'name' => 'Studio',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'Professional studio setup, controlled lighting, clean backdrop, production environment',
                'lightingStyle' => 'Three-point lighting setup, controlled studio lights',
            ],
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['locationBible']['locations'][] = array_merge(
                ['id' => uniqid('loc_'), 'referenceImage' => null, 'referenceImageStatus' => null],
                $templates[$template]
            );
            $this->editingLocationIndex = count($this->sceneMemory['locationBible']['locations']) - 1;
            $this->saveProject();
        }
    }

    /**
     * Edit a location.
     */
    public function editLocation(int $index): void
    {
        $this->editingLocationIndex = $index;
    }

    /**
     * Open Location Bible modal.
     */
    public function openLocationBibleModal(): void
    {
        $this->showLocationBibleModal = true;
        if (empty($this->sceneMemory['locationBible']['locations'])) {
            $this->addLocation('New Location', '');
        }
        $this->editingLocationIndex = 0;
    }

    /**
     * Close Location Bible modal.
     */
    public function closeLocationBibleModal(): void
    {
        $this->showLocationBibleModal = false;
        $this->saveProject();
    }

    /**
     * Toggle location assignment to a scene.
     */
    public function toggleLocationScene(int $locIndex, int $sceneIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locIndex])) {
            return;
        }

        $scenes = $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] ?? [];

        if (in_array($sceneIndex, $scenes)) {
            $scenes = array_values(array_filter($scenes, fn($s) => $s !== $sceneIndex));
        } else {
            $scenes[] = $sceneIndex;
        }

        $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] = $scenes;
        $this->saveProject();
    }

    /**
     * Apply location to all scenes.
     */
    public function applyLocationToAllScenes(int $locIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locIndex])) {
            return;
        }

        $sceneCount = count($this->storyboard['scenes'] ?? []);
        $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] = range(0, $sceneCount - 1);
        $this->saveProject();
    }

    /**
     * Remove location reference image.
     */
    public function removeLocationReference(int $index): void
    {
        if (isset($this->sceneMemory['locationBible']['locations'][$index])) {
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = null;
            $this->saveProject();
        }
    }

    /**
     * Upload a reference image for a location.
     */
    public function uploadLocationReference(int $index): void
    {
        if (!$this->locationImageUpload) {
            return;
        }

        $this->validate([
            'locationImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'location_' . uniqid() . '_' . time() . '.' . $this->locationImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->locationImageUpload->storeAs('wizard-assets/locations', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Update location with the uploaded image
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = $url;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageSource'] = 'upload';

            // Clear the upload
            $this->locationImageUpload = null;

            $this->saveProject();

            // Dispatch debug event
            $this->dispatch('vw-debug', [
                'type' => 'location_image_upload',
                'locationIndex' => $index,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error('Location image upload failed', ['error' => $e->getMessage()]);
            $this->error = __('Failed to upload image: ') . $e->getMessage();
        }
    }

    /**
     * Generate location reference.
     */
    public function generateLocationReference(int $index): void
    {
        $location = $this->sceneMemory['locationBible']['locations'][$index] ?? null;
        if (!$location) return;

        $this->isLoading = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            // Build location-optimized prompt
            $promptParts = [
                'Cinematic establishing shot',
                $location['description'],
                ucfirst($location['type']) . ' setting',
                ucfirst($location['timeOfDay']) . ' lighting',
            ];

            if (!empty($location['weather']) && $location['weather'] !== 'clear') {
                $promptParts[] = ucfirst($location['weather']) . ' weather conditions';
            }

            if (!empty($location['mood'])) {
                $promptParts[] = ucfirst($location['mood']) . ' atmosphere';
            }

            $promptParts = array_merge($promptParts, [
                'Wide angle composition',
                'High production value',
                'Professional cinematography',
                'No people or characters',
                'Empty environment reference shot',
            ]);
            $prompt = implode('. ', $promptParts);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $location['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Location references don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = $result['imageUrl'];
                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate location reference: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // MULTI-SHOT MODE METHODS
    // =========================================================================

    /**
     * Toggle multi-shot mode.
     */
    public function toggleMultiShotMode(): void
    {
        $this->multiShotMode['enabled'] = !$this->multiShotMode['enabled'];
        $this->saveProject();
    }

    /**
     * Set default shot count for multi-shot mode.
     */
    public function setMultiShotCount(int $count): void
    {
        $this->multiShotMode['defaultShotCount'] = max(2, min(6, $count));
        $this->multiShotCount = $this->multiShotMode['defaultShotCount'];
        $this->saveProject();
    }

    /**
     * Open multi-shot decomposition modal.
     */
    public function openMultiShotModal(int $sceneIndex): void
    {
        $this->multiShotSceneIndex = $sceneIndex;
        $this->multiShotCount = $this->multiShotMode['defaultShotCount'];
        $this->showMultiShotModal = true;
    }

    /**
     * Close multi-shot modal.
     */
    public function closeMultiShotModal(): void
    {
        $this->showMultiShotModal = false;
    }

    /**
     * Decompose scene into multiple shots.
     */
    public function decomposeScene(int $sceneIndex): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $scene = $this->script['scenes'][$sceneIndex] ?? null;
            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            // Get visual description for decomposition
            $visualDescription = $scene['visualDescription'] ?? $scene['narration'] ?? '';

            // Use Gemini to decompose scene into shots
            $imageService = app(ImageGenerationService::class);

            // Calculate duration per shot (default 6s each, adjustable)
            $sceneDuration = $scene['duration'] ?? 30;
            $baseShotDuration = max(5, min(10, intval($sceneDuration / $this->multiShotCount)));

            $shots = [];
            for ($i = 0; $i < $this->multiShotCount; $i++) {
                $shotType = $this->getShotTypeForIndex($i, $this->multiShotCount);
                $cameraMovement = $this->getCameraMovementForShot($shotType['type'], $i);

                $shots[] = [
                    'id' => uniqid('shot_'),
                    'index' => $i,
                    'type' => $shotType['type'],
                    'shotType' => $shotType['type'],
                    'description' => $shotType['description'],
                    'prompt' => $this->buildShotPrompt($visualDescription, $shotType, $i),
                    'imageUrl' => null,
                    'videoUrl' => null,
                    'status' => 'pending',
                    'videoStatus' => 'pending',
                    'fromSceneImage' => $i === 0, // First shot can use scene image
                    'duration' => $baseShotDuration,
                    'selectedDuration' => $baseShotDuration,
                    'durationClass' => $baseShotDuration <= 5 ? 'quick' : ($baseShotDuration <= 6 ? 'short' : 'standard'),
                    'cameraMovement' => $cameraMovement,
                    'narrativeBeat' => [
                        'motionDescription' => $this->getMotionDescriptionForShot($shotType['type'], $cameraMovement, $visualDescription),
                    ],
                ];
            }

            // Store decomposed scene
            $this->multiShotMode['decomposedScenes'][$sceneIndex] = [
                'sceneId' => $scene['id'] ?? $sceneIndex,
                'shots' => $shots,
                'selectedShot' => 0,
                'status' => 'ready',
                'consistencyAnchors' => [
                    'style' => $this->sceneMemory['styleBible']['style'] ?? '',
                    'characters' => $this->getCharactersForScene($sceneIndex),
                    'location' => $this->getLocationForScene($sceneIndex),
                ],
            ];

            // If scene already has an image, use it for first shot
            $storyboardScene = $this->storyboard['scenes'][$sceneIndex] ?? null;
            if ($storyboardScene && !empty($storyboardScene['imageUrl'])) {
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['imageUrl'] = $storyboardScene['imageUrl'];
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['status'] = 'ready';
            }

            $this->saveProject();
            $this->showMultiShotModal = false;

        } catch (\Exception $e) {
            $this->error = __('Failed to decompose scene: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get shot type configuration based on index and total count.
     */
    protected function getShotTypeForIndex(int $index, int $total): array
    {
        $shotTypes = [
            ['type' => 'establishing', 'description' => 'Wide establishing shot showing the environment'],
            ['type' => 'medium', 'description' => 'Medium shot focusing on the main subject'],
            ['type' => 'close-up', 'description' => 'Close-up shot emphasizing details'],
            ['type' => 'reaction', 'description' => 'Reaction shot or cutaway'],
            ['type' => 'detail', 'description' => 'Detail shot of important elements'],
            ['type' => 'wide', 'description' => 'Wide shot showing full context'],
        ];

        return $shotTypes[$index % count($shotTypes)];
    }

    /**
     * Build prompt for a specific shot.
     */
    protected function buildShotPrompt(string $baseDescription, array $shotType, int $index): string
    {
        $prompt = $baseDescription;
        $prompt .= ", " . $shotType['type'] . " shot";
        $prompt .= ", " . $shotType['description'];

        // Add style from Style Bible if enabled
        if ($this->sceneMemory['styleBible']['enabled'] && !empty($this->sceneMemory['styleBible']['style'])) {
            $prompt .= ", " . $this->sceneMemory['styleBible']['style'];
        }

        // Add technical specs
        if ($this->storyboard['technicalSpecs']['enabled']) {
            $prompt .= ", " . $this->storyboard['technicalSpecs']['positive'];
        }

        return $prompt;
    }

    /**
     * Get camera movement for a shot based on its type.
     */
    protected function getCameraMovementForShot(string $shotType, int $index): string
    {
        $movements = [
            'establishing' => 'slow pan',
            'medium' => 'static',
            'close-up' => 'push in',
            'reaction' => 'quick cut',
            'detail' => 'slow zoom',
            'wide' => 'drift',
        ];

        return $movements[$shotType] ?? 'static';
    }

    /**
     * Get motion description for video generation based on shot type.
     */
    protected function getMotionDescriptionForShot(string $shotType, string $cameraMovement, string $visualDescription): string
    {
        $baseDescriptions = [
            'establishing' => 'Slow pan across the scene, establishing the environment and atmosphere',
            'medium' => 'Subtle movement focusing on the main subject with gentle camera motion',
            'close-up' => 'Slight push in emphasizing details and expressions',
            'reaction' => 'Quick responsive movement capturing emotional response',
            'detail' => 'Slow zoom highlighting specific important elements',
            'wide' => 'Expansive view with subtle camera drift revealing the full scene',
        ];

        $base = $baseDescriptions[$shotType] ?? 'Natural subtle movement maintaining visual interest';

        // Add context from visual description if available
        if (strlen($visualDescription) > 20) {
            $contextSnippet = substr($visualDescription, 0, 100);
            return "{$base}. Context: {$contextSnippet}";
        }

        return $base;
    }

    /**
     * Get characters applied to a scene.
     */
    protected function getCharactersForScene(int $sceneIndex): array
    {
        $characters = [];
        foreach ($this->sceneMemory['characterBible']['characters'] as $character) {
            if (in_array($sceneIndex, $character['appliedScenes'] ?? [])) {
                $characters[] = $character;
            }
        }
        return $characters;
    }

    /**
     * Get location for a scene.
     */
    protected function getLocationForScene(int $sceneIndex): ?array
    {
        foreach ($this->sceneMemory['locationBible']['locations'] as $location) {
            if (in_array($sceneIndex, $location['appliedScenes'] ?? [])) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Generate image for a specific shot.
     */
    public function generateShotImage(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'generating';

        $this->isLoading = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $shot['id'],
                        'visualDescription' => $shot['prompt'],
                    ], [
                        'model' => $this->storyboard['imageModel'] ?? 'hidream',
                        'sceneIndex' => $sceneIndex, // Pass actual scene index for character/location context
                    ]);

                    if ($result['success']) {
                        if (isset($result['imageUrl'])) {
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageUrl'] = $result['imageUrl'];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'ready';
                        } elseif (isset($result['jobId'])) {
                            // Async job - store for polling
                            $this->pendingJobs["shot_{$sceneIndex}_{$shotIndex}"] = [
                                'jobId' => $result['jobId'],
                                'type' => 'shot',
                                'sceneIndex' => $sceneIndex,
                                'shotIndex' => $shotIndex,
                            ];
                        }
                        $this->saveProject();
                    } else {
                        throw new \Exception($result['error'] ?? __('Generation failed'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'error';
            $this->error = __('Failed to generate shot image: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate all shots for a scene.
     */
    public function generateAllShots(int $sceneIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed) {
            $this->error = __('Scene not decomposed');
            return;
        }

        foreach ($decomposed['shots'] as $shotIndex => $shot) {
            if ($shot['status'] !== 'ready') {
                $this->generateShotImage($sceneIndex, $shotIndex);
            }
        }
    }

    /**
     * Select a shot for the scene.
     */
    public function selectShot(int $sceneIndex, int $shotIndex): void
    {
        if (isset($this->multiShotMode['decomposedScenes'][$sceneIndex])) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['selectedShot'] = $shotIndex;
            $this->saveProject();
        }
    }

    /**
     * Open shot preview modal.
     */
    public function openShotPreviewModal(int $sceneIndex, int $shotIndex): void
    {
        $this->shotPreviewSceneIndex = $sceneIndex;
        $this->shotPreviewShotIndex = $shotIndex;
        $this->shotPreviewTab = 'image';
        $this->showShotPreviewModal = true;
    }

    /**
     * Close shot preview modal.
     */
    public function closeShotPreviewModal(): void
    {
        $this->showShotPreviewModal = false;
    }

    /**
     * Switch tab in shot preview modal.
     */
    public function switchShotPreviewTab(string $tab): void
    {
        $this->shotPreviewTab = $tab;
    }

    /**
     * Open frame capture modal.
     */
    public function openFrameCaptureModal(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];
        if (empty($shot['videoUrl'])) {
            $this->error = __('Generate video first to capture frames');
            return;
        }

        $this->frameCaptureSceneIndex = $sceneIndex;
        $this->frameCaptureShotIndex = $shotIndex;
        $this->capturedFrame = null;
        $this->showFrameCaptureModal = true;
    }

    /**
     * Close frame capture modal.
     */
    public function closeFrameCaptureModal(): void
    {
        $this->showFrameCaptureModal = false;
        $this->capturedFrame = null;
    }

    /**
     * Set captured frame from JavaScript.
     */
    public function setCapturedFrame(string $frameDataUrl): void
    {
        $this->capturedFrame = $frameDataUrl;
    }

    /**
     * Transfer captured frame to next shot.
     */
    public function transferFrameToNextShot(): void
    {
        if (!$this->capturedFrame) {
            $this->error = __('No frame captured');
            return;
        }

        $sceneIndex = $this->frameCaptureSceneIndex;
        $shotIndex = $this->frameCaptureShotIndex;
        $nextShotIndex = $shotIndex + 1;

        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$nextShotIndex])) {
            $this->error = __('Next shot not found');
            return;
        }

        try {
            // Save the captured frame to storage
            $imageService = app(ImageGenerationService::class);
            $filename = "frame_capture_{$sceneIndex}_{$shotIndex}_" . time() . '.png';

            // Decode base64 and save
            $frameData = preg_replace('/^data:image\/\w+;base64,/', '', $this->capturedFrame);
            $frameData = base64_decode($frameData);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $path = "wizard-projects/{$project->id}/frames/{$filename}";
                    Storage::disk('public')->put($path, $frameData);
                    $imageUrl = Storage::disk('public')->url($path);

                    // Update next shot with transferred frame
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['imageUrl'] = $imageUrl;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['status'] = 'ready';
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['transferredFrom'] = $shotIndex;

                    $this->saveProject();
                    $this->closeFrameCaptureModal();
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to transfer frame: ') . $e->getMessage();
        }
    }

    /**
     * Set shot duration.
     */
    public function setShotDuration(int $sceneIndex, int $shotIndex, int $duration): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return;
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['selectedDuration'] = $duration;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['duration'] = $duration;

        // Update duration class
        $durationClass = $duration <= 5 ? 'quick' : ($duration <= 6 ? 'short' : 'standard');
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['durationClass'] = $durationClass;

        $this->saveProject();
    }

    /**
     * Calculate total duration of all shots in a decomposed scene.
     */
    public function calculateSceneTotalDuration(int $sceneIndex): int
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            return 0;
        }

        $total = 0;
        foreach ($decomposed['shots'] as $shot) {
            $total += $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
        }
        return $total;
    }

    /**
     * Reset decomposition for a scene.
     */
    public function resetDecomposition(int $sceneIndex): void
    {
        if (isset($this->multiShotMode['decomposedScenes'][$sceneIndex])) {
            unset($this->multiShotMode['decomposedScenes'][$sceneIndex]);
            $this->saveProject();
        }
    }

    /**
     * Generate video for a specific shot.
     */
    public function generateShotVideo(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];
        if (empty($shot['imageUrl'])) {
            $this->error = __('Generate image first');
            return;
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'generating';
        $this->isLoading = true;
        $this->error = null;

        try {
            $animationService = app(\Starter\AppVideoWizard\Services\AnimationService::class);
            $duration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    // Build motion description for the shot
                    $motionPrompt = $this->buildShotMotionPrompt($shot);

                    $result = $animationService->generateAnimation($project, [
                        'imageUrl' => $shot['imageUrl'],
                        'prompt' => $motionPrompt,
                        'model' => $this->animation['model'] ?? 'minimax',
                        'duration' => $duration,
                    ]);

                    if ($result['success']) {
                        if (isset($result['videoUrl'])) {
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoUrl'] = $result['videoUrl'];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'ready';
                        } elseif (isset($result['taskId'])) {
                            // Async job - store for polling
                            $this->pendingJobs["shot_video_{$sceneIndex}_{$shotIndex}"] = [
                                'taskId' => $result['taskId'],
                                'type' => 'shot_video',
                                'sceneIndex' => $sceneIndex,
                                'shotIndex' => $shotIndex,
                            ];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'processing';
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoTaskId'] = $result['taskId'];
                        }
                        $this->saveProject();
                    } else {
                        throw new \Exception($result['error'] ?? __('Animation failed'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'error';
            $this->error = __('Failed to generate shot video: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Build motion prompt for a shot.
     */
    protected function buildShotMotionPrompt(array $shot): string
    {
        $prompt = '';

        // Use narrative beat motion description if available
        if (!empty($shot['narrativeBeat']['motionDescription'])) {
            $prompt = $shot['narrativeBeat']['motionDescription'];
        } elseif (!empty($shot['description'])) {
            $prompt = $shot['description'];
        } else {
            // Build from shot type
            $shotType = $shot['type'] ?? 'medium';
            $cameraMovement = $shot['cameraMovement'] ?? 'static';

            $movements = [
                'establishing' => 'slow pan across the scene, establishing the environment',
                'medium' => 'subtle movement focusing on the subject',
                'close-up' => 'slight push in, emphasizing details',
                'reaction' => 'quick cut, capturing the reaction',
                'detail' => 'slow zoom on key details',
                'wide' => 'expansive view with subtle camera drift',
            ];

            $prompt = $movements[$shotType] ?? 'natural subtle movement';
        }

        return $prompt;
    }

    /**
     * Generate all shot videos for a decomposed scene.
     */
    public function generateAllShotVideos(int $sceneIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed) {
            $this->error = __('Scene not decomposed');
            return;
        }

        foreach ($decomposed['shots'] as $shotIndex => $shot) {
            if (!empty($shot['imageUrl']) && ($shot['videoStatus'] ?? 'pending') !== 'ready') {
                $this->generateShotVideo($sceneIndex, $shotIndex);
            }
        }
    }

    // =========================================================================
    // UPSCALE METHODS
    // =========================================================================

    /**
     * Open upscale quality modal.
     */
    public function openUpscaleModal(int $sceneIndex): void
    {
        $this->upscaleSceneIndex = $sceneIndex;
        $this->upscaleQuality = 'hd';
        $this->showUpscaleModal = true;
    }

    /**
     * Close upscale modal.
     */
    public function closeUpscaleModal(): void
    {
        $this->showUpscaleModal = false;
    }

    /**
     * Upscale scene image.
     */
    public function upscaleImage(): void
    {
        $storyboardScene = $this->storyboard['scenes'][$this->upscaleSceneIndex] ?? null;
        if (!$storyboardScene || empty($storyboardScene['imageUrl'])) {
            $this->error = __('No image to upscale');
            return;
        }

        $this->isUpscaling = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            $result = $imageService->upscaleImage(
                $storyboardScene['imageUrl'],
                $this->upscaleQuality
            );

            if ($result['success'] && isset($result['imageUrl'])) {
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['imageUrl'] = $result['imageUrl'];
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['upscaled'] = true;
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['upscaleQuality'] = $this->upscaleQuality;
                $this->saveProject();
                $this->showUpscaleModal = false;
            } else {
                throw new \Exception($result['error'] ?? __('Upscale failed'));
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to upscale image: ') . $e->getMessage();
        } finally {
            $this->isUpscaling = false;
        }
    }

    // =========================================================================
    // AI EDIT WITH MASK METHODS
    // =========================================================================

    /**
     * Open AI edit modal.
     */
    public function openAIEditModal(int $sceneIndex): void
    {
        $this->aiEditSceneIndex = $sceneIndex;
        $this->aiEditPrompt = '';
        $this->aiEditBrushSize = 30;
        $this->showAIEditModal = true;
    }

    /**
     * Close AI edit modal.
     */
    public function closeAIEditModal(): void
    {
        $this->showAIEditModal = false;
    }

    /**
     * Set AI edit brush size.
     */
    public function setAIEditBrushSize(int $size): void
    {
        $this->aiEditBrushSize = max(10, min(100, $size));
    }

    /**
     * Apply AI edit with mask.
     */
    public function applyAIEdit(string $maskData): void
    {
        $storyboardScene = $this->storyboard['scenes'][$this->aiEditSceneIndex] ?? null;
        if (!$storyboardScene || empty($storyboardScene['imageUrl'])) {
            $this->error = __('No image to edit');
            return;
        }

        if (empty($this->aiEditPrompt)) {
            $this->error = __('Please describe what you want to change');
            return;
        }

        $this->isApplyingEdit = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            $result = $imageService->editImageWithMask(
                $storyboardScene['imageUrl'],
                $maskData,
                $this->aiEditPrompt
            );

            if ($result['success'] && isset($result['imageUrl'])) {
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['imageUrl'] = $result['imageUrl'];
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['edited'] = true;
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['editHistory'][] = [
                    'prompt' => $this->aiEditPrompt,
                    'timestamp' => now()->toIso8601String(),
                ];
                $this->saveProject();
                $this->showAIEditModal = false;
            } else {
                throw new \Exception($result['error'] ?? __('Edit failed'));
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to apply AI edit: ') . $e->getMessage();
        } finally {
            $this->isApplyingEdit = false;
        }
    }

    /**
     * Check for pending jobs on page load.
     */
    #[On('check-pending-jobs')]
    public function checkPendingJobs(): void
    {
        if (!empty($this->pendingJobs)) {
            $this->dispatch('poll-status', ['pendingJobs' => count($this->pendingJobs)]);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('appvideowizard::livewire.video-wizard', [
            'platforms' => config('appvideowizard.platforms'),
            'formats' => config('appvideowizard.formats'),
            'productionTypes' => config('appvideowizard.production_types'),
            'captionStyles' => config('appvideowizard.caption_styles'),
            'stepTitles' => $this->getStepTitles(),
        ]);
    }
}
