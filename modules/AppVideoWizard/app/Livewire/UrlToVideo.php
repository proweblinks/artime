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
    public string $narrativeStyle = 'hook_reveal';

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

    // Real Images mode
    public bool $useRealImages = false;
    public bool $showImageSelectionModal = false;
    public bool $isSourcingImages = false;
    public array $sceneImageCandidates = [];
    public array $selectedSceneImages = [];
    public $uploadedSceneImage;
    public string $uploadTargetScene = '';

    // Active project tracking
    public ?int $activeProjectId = null;
    public ?int $detailProjectId = null;
    public ?int $sharedProjectId = null;

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

                $enhancedPrompt = $extractor->buildEnhancedPrompt($contentBrief, $userPrompt, $this->narrativeStyle);
            } else {
                // Prompt-only mode (no URL) — still apply narrative style
                $enhancedPrompt = $this->prompt;
                $styleInstruction = $extractor->getNarrativeStyleInstruction($this->narrativeStyle);
                if ($styleInstruction) {
                    $enhancedPrompt .= "\n\n" . $styleInstruction;
                }
                $this->storedExtractedContent = [];
                $this->storedContentBrief = [];
            }

            // Generate script using Story Mode's script service
            $scriptService = new StoryModeScriptService();
            $targetDuration = (int) get_option('story_mode_default_duration', 35);
            $maxWords = (int) get_option('story_mode_max_words', 450);

            $result = $scriptService->generateScript($enhancedPrompt, $targetDuration, $maxWords);

            $this->editableTranscript = $result['transcript'];
            $this->transcriptWordCount = $result['word_count'];
            $this->generatedTitle = $this->storedContentBrief['suggested_title']
                ?? $this->storedExtractedContent['title']
                ?? $result['title']
                ?? 'Untitled Video';
            $this->generatedSegments = $result['segments'] ?? [];
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
            // If we already have candidates from a previous sourcing, just reopen the modal
            if (!empty($this->sceneImageCandidates)) {
                $this->showTranscriptModal = false;
                $this->showImageSelectionModal = true;
                return;
            }

            $this->showTranscriptModal = false;
            $this->isSourcingImages = true;

            try {
                $scriptService = new StoryModeScriptService();
                $targetDuration = (int) get_option('story_mode_default_duration', 35);
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

                $imageService = new ImageSourceService();
                $candidates = $imageService->sourceForScenes(
                    $scenes,
                    $this->storedExtractedContent,
                    $this->storedContentBrief
                );

                $this->sceneImageCandidates = $candidates;

                // Auto-select first candidate per scene
                $this->selectedSceneImages = [];
                foreach ($candidates as $sceneId => $sceneCandidates) {
                    if (!empty($sceneCandidates)) {
                        $this->selectedSceneImages[$sceneId] = 0; // Index of first candidate
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
     * Select a specific image candidate for a scene.
     */
    public function selectSceneImage(string $sceneId, int $candidateIndex)
    {
        $this->selectedSceneImages[$sceneId] = $candidateIndex;
    }

    /**
     * Mark a scene to use AI-generated image instead of a real one.
     */
    public function markSceneForAI(string $sceneId)
    {
        $this->selectedSceneImages[$sceneId] = 'ai';
    }

    /**
     * Search Wikimedia Commons for additional images for a scene.
     */
    public function searchMoreImages(string $sceneId, string $query)
    {
        try {
            $imageService = new ImageSourceService();
            $results = $imageService->searchWikimedia($query, 5);

            foreach ($results as $result) {
                $this->sceneImageCandidates[$sceneId][] = array_merge($result, [
                    'source' => 'wikimedia',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('UrlToVideo: Additional image search failed', ['error' => $e->getMessage()]);
        }
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
     * Create project and dispatch the generation job.
     * Downloads selected real images and assigns image_url to scenes.
     */
    protected function dispatchGenerationPipeline(): void
    {
        $scriptService = new StoryModeScriptService();
        $targetDuration = (int) get_option('story_mode_default_duration', 35);
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
                $candidate = is_int($selection) ? ($candidates[$selection] ?? null) : null;

                if ($candidate && !empty($candidate['url'])) {
                    // If it's an uploaded image, use URL directly
                    if (($candidate['source'] ?? '') === 'upload') {
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
                'narrative_style' => $this->narrativeStyle,
                'image_source' => $imageSource,
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
        $this->selectedSceneImages = [];
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
