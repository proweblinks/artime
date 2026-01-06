<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;
use Modules\AppVideoWizard\Services\ConceptService;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\VoiceoverService;
use Modules\AppVideoWizard\Services\StockMediaService;

class VideoWizard extends Component
{
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

    // Step 3: Script
    public array $script = [
        'title' => '',
        'hook' => '',
        'scenes' => [],
        'cta' => '',
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
     */
    protected function recoverPendingJobs(WizardProject $project): void
    {
        $pendingJobs = WizardProcessingJob::where('project_id', $project->id)
            ->whereIn('status', [
                WizardProcessingJob::STATUS_PENDING,
                WizardProcessingJob::STATUS_PROCESSING
            ])
            ->get();

        if ($pendingJobs->isEmpty()) {
            return;
        }

        // Restore pending jobs to component state
        foreach ($pendingJobs as $job) {
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

        // Restore Scene Memory, Multi-Shot Mode, and Concept Variations from content_config
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
                // Save Scene Memory, Multi-Shot Mode, and Concept Variations
                'content_config' => [
                    'sceneMemory' => $this->sceneMemory,
                    'multiShotMode' => $this->multiShotMode,
                    'conceptVariations' => $this->conceptVariations,
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
        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $conceptService = app(ConceptService::class);

            $result = $conceptService->improveConcept($this->concept['rawInput'], [
                'productionType' => $this->productionType,
                'productionSubType' => $this->productionSubtype,
                'teamId' => session('current_team_id', 0),
            ]);

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

            $this->dispatch('concept-enhanced');

        } catch (\Exception $e) {
            $this->error = __('Failed to enhance concept: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate unique ideas based on concept.
     */
    public function generateIdeas(): void
    {
        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
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

            $this->conceptVariations = $variations;
            $this->selectedConceptIndex = 0;

            $this->saveProject();

            // Don't auto-advance - let user review and select concept variation

        } catch (\Exception $e) {
            $this->error = __('Failed to generate ideas: ') . $e->getMessage();
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
        if (empty($this->concept['rawInput']) && empty($this->concept['refinedConcept'])) {
            $this->error = __('Please complete the concept step first.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
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

            // Update script data
            $this->script = array_merge($this->script, $generatedScript);

            $this->saveProject();

            $this->dispatch('script-generated');

        } catch (\Exception $e) {
            $this->error = __('Failed to generate script: ') . $e->getMessage();
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
