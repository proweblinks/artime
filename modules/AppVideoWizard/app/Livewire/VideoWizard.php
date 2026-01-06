<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\ConceptService;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\VoiceoverService;

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
        'imageModel' => 'flux',
        'visualStyle' => [
            'mood' => '',
            'lighting' => '',
            'colorPalette' => '',
            'composition' => '',
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

    // Concept variations state
    public array $conceptVariations = [];
    public int $selectedConceptIndex = 0;

    // Script generation options
    public string $scriptTone = 'engaging';
    public string $contentDepth = 'detailed';
    public string $additionalInstructions = '';

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
    }

    /**
     * Save current state to database.
     */
    public function saveProject(): void
    {
        $this->isSaving = true;

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
            ]);

            // Update storyboard with the generated image
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }
            $this->storyboard['scenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'imageUrl' => $result['imageUrl'],
                'assetId' => $result['assetId'] ?? null,
            ];

            $this->saveProject();

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
                // Skip if already has an image
                if (!empty($this->storyboard['scenes'][$index]['imageUrl'])) {
                    continue;
                }

                try {
                    $result = $imageService->generateSceneImage($project, $scene, [
                        'sceneIndex' => $index,
                        'teamId' => session('current_team_id', 0),
                    ]);

                    $this->storyboard['scenes'][$index] = [
                        'sceneId' => $scene['id'],
                        'imageUrl' => $result['imageUrl'],
                        'assetId' => $result['assetId'] ?? null,
                    ];

                    $this->saveProject();

                } catch (\Exception $e) {
                    // Log individual scene errors but continue
                    \Log::warning("Failed to generate image for scene {$index}: " . $e->getMessage());
                }
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
