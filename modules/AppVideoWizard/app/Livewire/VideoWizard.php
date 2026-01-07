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
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Facades\Log;

class VideoWizard extends Component
{
    use WithFileUploads;

    // Import file for project import
    public $importFile;

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
    public ?string $error = null;

    // Stock Media Browser state
    public bool $showStockBrowser = false;
    public int $stockBrowserSceneIndex = 0;
    public string $stockSearchQuery = '';
    public string $stockMediaType = 'image';
    public string $stockOrientation = 'landscape';
    public array $stockSearchResults = [];
    public bool $stockSearching = false;

    // Edit Prompt Modal state
    public bool $showEditPromptModal = false;
    public int $editPromptSceneIndex = 0;
    public string $editPromptText = '';

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
        }

        // Recalculate voice status if script exists
        if (!empty($this->script['scenes'])) {
            $this->recalculateVoiceStatus();
        }
    }

    /**
     * Save current state to database.
     */
    public function saveProject(): void
    {
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
                // Save Scene Memory, Multi-Shot Mode, Concept Variations, and Character Intelligence
                'content_config' => [
                    'sceneMemory' => $this->sceneMemory,
                    'multiShotMode' => $this->multiShotMode,
                    'conceptVariations' => $this->conceptVariations,
                    'characterIntelligence' => $this->characterIntelligence,
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
     * Go to a specific step.
     */
    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 7) {
            return;
        }

        // Can only go to steps we've reached or the next step
        if ($step <= $this->maxReachedStep + 1) {
            $this->currentStep = $step;
            $this->maxReachedStep = max($this->maxReachedStep, $step);

            // Only save if user is authenticated
            if (auth()->check()) {
                $this->saveProject();
            }
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
            $this->targetDuration = min(
                $platform['maxDuration'],
                max($platform['minDuration'], $this->targetDuration)
            );
        }
        // Note: Don't auto-save on selection - will save on step navigation
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
     * Update production type.
     */
    public function selectProductionType(string $type, ?string $subtype = null): void
    {
        $this->productionType = $type;
        $this->productionSubtype = $subtype;
        // Note: Don't auto-save on selection - will save on step navigation
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
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting script generation', $inputData);

            // Create or update the project first
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scriptService = app(ScriptGenerationService::class);

            $generatedScript = $scriptService->generateScript($project, [
                'teamId' => session('current_team_id', 0),
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'additionalInstructions' => $this->additionalInstructions,
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

            $imageService = app(ImageGenerationService::class);
            $result = $imageService->generateSceneImage($project, $scene, [
                'sceneIndex' => $sceneIndex,
                'teamId' => session('current_team_id', 0),
                'model' => $this->storyboard['imageModel'] ?? 'nanobanana', // Use UI-selected model
            ]);

            // Update storyboard with the generated image or set generating state
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }

            if ($result['async'] ?? false) {
                // HiDream async job - set generating state and start polling
                $this->storyboard['scenes'][$sceneIndex] = [
                    'sceneId' => $sceneId,
                    'imageUrl' => null,
                    'assetId' => null,
                    'status' => 'generating',
                    'jobId' => $result['jobId'] ?? null,
                    'processingJobId' => $result['processingJobId'] ?? null,
                ];

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

            foreach ($this->script['scenes'] as $index => $scene) {
                // Skip if already has an image or is generating
                $existingScene = $this->storyboard['scenes'][$index] ?? null;
                if (!empty($existingScene['imageUrl']) || ($existingScene['status'] ?? '') === 'generating') {
                    continue;
                }

                try {
                    $result = $imageService->generateSceneImage($project, $scene, [
                        'sceneIndex' => $index,
                        'teamId' => session('current_team_id', 0),
                        'model' => $this->storyboard['imageModel'] ?? 'nanobanana', // Use UI-selected model
                    ]);

                    if ($result['async'] ?? false) {
                        // HiDream async job
                        $this->storyboard['scenes'][$index] = [
                            'sceneId' => $scene['id'],
                            'imageUrl' => null,
                            'assetId' => null,
                            'status' => 'generating',
                            'jobId' => $result['jobId'] ?? null,
                            'processingJobId' => $result['processingJobId'] ?? null,
                        ];
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
                    // Log individual scene errors but continue
                    \Log::warning("Failed to generate image for scene {$index}: " . $e->getMessage());
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
    // EDIT PROMPT METHODS
    // =========================================================================

    /**
     * Open edit prompt modal for a scene.
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
    }

    /**
     * Close edit prompt modal.
     */
    public function closeEditPrompt(): void
    {
        $this->showEditPromptModal = false;
        $this->editPromptText = '';
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

        // Update the script scene's visual description
        if (isset($this->script['scenes'][$this->editPromptSceneIndex])) {
            $this->script['scenes'][$this->editPromptSceneIndex]['visualDescription'] = $this->editPromptText;
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
            'appliedScenes' => [],
            'referenceImage' => null,
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
            'description' => $description,
            'referenceImage' => null,
        ];
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
     * Build prompt for a scene.
     */
    protected function buildScenePrompt(array $scene, int $index): string
    {
        $prompt = $scene['visualDescription'] ?? $scene['narration'] ?? '';

        // Add style bible
        if ($this->sceneMemory['styleBible']['enabled']) {
            $styleBible = $this->sceneMemory['styleBible'];
            if (!empty($styleBible['style'])) {
                $prompt = $styleBible['style'] . ', ' . $prompt;
            }
            if (!empty($styleBible['colorGrade'])) {
                $prompt .= ', ' . $styleBible['colorGrade'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $prompt .= ', ' . $styleBible['atmosphere'];
            }
        }

        // Add visual style
        $visualStyle = $this->storyboard['visualStyle'] ?? [];
        if (!empty($visualStyle['mood'])) {
            $prompt .= ', ' . $visualStyle['mood'] . ' mood';
        }
        if (!empty($visualStyle['lighting'])) {
            $prompt .= ', ' . $visualStyle['lighting'] . ' lighting';
        }
        if (!empty($visualStyle['colorPalette'])) {
            $prompt .= ', ' . $visualStyle['colorPalette'] . ' color palette';
        }

        // Add technical specs
        if ($this->storyboard['technicalSpecs']['enabled'] ?? true) {
            $prompt .= ', ' . ($this->storyboard['technicalSpecs']['positive'] ?? 'high quality, detailed, professional');
        }

        return $prompt;
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
    // CHARACTER BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect characters from script.
     */
    public function autoDetectCharacters(): void
    {
        // Simple detection - look for names in dialogue
        $characters = [];
        foreach ($this->script['scenes'] as $scene) {
            if (isset($scene['dialogue']) && is_array($scene['dialogue'])) {
                foreach ($scene['dialogue'] as $dialogue) {
                    $speaker = $dialogue['speaker'] ?? null;
                    if ($speaker && !in_array($speaker, $characters)) {
                        $characters[] = $speaker;
                    }
                }
            }
        }

        foreach ($characters as $name) {
            $exists = collect($this->sceneMemory['characterBible']['characters'])
                ->where('name', $name)
                ->isNotEmpty();

            if (!$exists) {
                $this->sceneMemory['characterBible']['characters'][] = [
                    'id' => uniqid('char_'),
                    'name' => $name,
                    'description' => '',
                    'appliedScenes' => [],
                    'referenceImage' => null,
                ];
            }
        }

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

            // Build portrait prompt
            $prompt = "Character portrait, " . $character['description'];
            $prompt .= ", professional photography, studio lighting, headshot";

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $character['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => 'char_' . $index,
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
    // LOCATION BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect locations from script.
     */
    public function autoDetectLocations(): void
    {
        // Simple detection from visual descriptions
        foreach ($this->script['scenes'] as $index => $scene) {
            $visual = $scene['visualDescription'] ?? '';
            if (empty($visual)) continue;

            // Check if location already exists for this scene
            $exists = collect($this->sceneMemory['locationBible']['locations'])
                ->where('name', 'Scene ' . ($index + 1) . ' Location')
                ->isNotEmpty();

            if (!$exists && !empty($visual)) {
                $this->sceneMemory['locationBible']['locations'][] = [
                    'id' => uniqid('loc_'),
                    'name' => 'Scene ' . ($index + 1) . ' Location',
                    'type' => 'exterior',
                    'timeOfDay' => 'day',
                    'weather' => 'clear',
                    'description' => $visual,
                    'referenceImage' => null,
                ];
            }
        }

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
                'description' => 'Modern cityscape, tall buildings, neon lights, busy streets, urban environment',
            ],
            'forest' => [
                'name' => 'Forest',
                'type' => 'exterior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'description' => 'Dense forest, tall trees, dappled sunlight, natural environment, lush vegetation',
            ],
            'office' => [
                'name' => 'Office',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'description' => 'Modern office interior, clean design, glass walls, professional workspace',
            ],
            'studio' => [
                'name' => 'Studio',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'description' => 'Professional studio setup, controlled lighting, clean backdrop, production environment',
            ],
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['locationBible']['locations'][] = array_merge(
                ['id' => uniqid('loc_'), 'referenceImage' => null],
                $templates[$template]
            );
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

            // Build location prompt
            $prompt = $location['description'];
            $prompt .= ", " . $location['type'] . ", " . $location['timeOfDay'];
            if ($location['weather'] !== 'clear') {
                $prompt .= ", " . $location['weather'] . " weather";
            }
            $prompt .= ", establishing shot, wide angle, professional photography";

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $location['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => 'loc_' . $index,
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

            $shots = [];
            for ($i = 0; $i < $this->multiShotCount; $i++) {
                $shotType = $this->getShotTypeForIndex($i, $this->multiShotCount);
                $shots[] = [
                    'id' => uniqid('shot_'),
                    'index' => $i,
                    'type' => $shotType['type'],
                    'description' => $shotType['description'],
                    'prompt' => $this->buildShotPrompt($visualDescription, $shotType, $i),
                    'imageUrl' => null,
                    'status' => 'pending',
                    'fromSceneImage' => $i === 0, // First shot can use scene image
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
                        'sceneIndex' => "shot_{$sceneIndex}_{$shotIndex}",
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
