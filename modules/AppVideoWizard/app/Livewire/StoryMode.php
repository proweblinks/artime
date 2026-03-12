<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\StoryModeProject;
use Modules\AppVideoWizard\Models\StoryModeStyle;
use Modules\AppVideoWizard\Services\StoryModeScriptService;
use Modules\AppVideoWizard\Services\ImageSourceService;
use Modules\AppVideoWizard\Jobs\StoryModeGenerationJob;
use Modules\AppVideoWizard\Traits\HasImageSelection;

class StoryMode extends Component
{
    use WithFileUploads;
    use HasImageSelection;

    // Input state
    public string $prompt = '';
    public ?int $selectedStyleId = null;
    public string $customStyleInstruction = '';
    public $customStyleImage = null;
    public string $customStyleName = '';
    public string $aspectRatio = '9:16';
    public string $imageAspectRatio = '9:16';
    public string $videoAspectRatio = '9:16';
    public string $selectedVoice = 'auto';
    public string $voiceProvider = '';
    public string $videoResolution = '480p';
    public string $videoQuality = 'pro';
    public string $imageModel = 'nanobanana2';
    public bool $generateAudio = true;
    public $attachedFile = null;

    // Transcript editing
    public ?string $editableTranscript = null;
    public int $transcriptWordCount = 0;
    public ?string $generatedTitle = null;
    public array $generatedSegments = [];

    // UI state
    public bool $showTranscriptModal = false;
    public bool $showVoiceModal = false;
    public bool $showStyleModal = false;
    public bool $isGeneratingScript = false;
    public bool $isGenerating = false;

    // Active project tracking
    public ?int $activeProjectId = null;
    public ?int $detailProjectId = null;

    // Shared project (from URL)
    public ?int $sharedProjectId = null;

    protected $listeners = [
        'projectCompleted' => '$refresh',
    ];

    public function mount($sharedProject = null)
    {
        $this->aspectRatio = get_option('story_mode_default_aspect', '9:16');
        $this->imageModel = get_option('story_mode_image_model', 'nanobanana2');

        if ($sharedProject) {
            $this->sharedProjectId = $sharedProject->id;
            $this->detailProjectId = $sharedProject->id;
        }

        // Check for active in-progress project
        $activeProject = StoryModeProject::forUser(auth()->id())
            ->inProgress()
            ->latest()
            ->first();

        if ($activeProject) {
            $this->activeProjectId = $activeProject->id;
        }
    }

    /**
     * Submit prompt — generates script and shows transcript modal.
     */
    public function submitPrompt()
    {
        $this->validate([
            'prompt' => 'required|string|min:10|max:2000',
        ]);

        $this->isGeneratingScript = true;

        try {
            $scriptService = new StoryModeScriptService();
            $targetDuration = (int) get_option('story_mode_default_duration', 35);
            $maxWords = (int) get_option('story_mode_max_words', 450);

            $result = $scriptService->generateScript($this->prompt, $targetDuration, $maxWords);

            $this->editableTranscript = $result['transcript'];
            $this->transcriptWordCount = $result['word_count'];
            $this->generatedTitle = $result['title'] ?? 'Untitled Story';
            $this->generatedSegments = $result['segments'] ?? [];
            $this->showTranscriptModal = true;
        } catch (\Exception $e) {
            Log::error('StoryMode: Script generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate script: ' . $e->getMessage());
        } finally {
            $this->isGeneratingScript = false;
        }
    }

    /**
     * Confirm transcript — segment and source images for the media selection step.
     */
    public function confirmTranscript()
    {
        if (empty($this->editableTranscript)) {
            return;
        }

        // If we already have candidates, verify scene count still matches
        if (!empty($this->sceneImageCandidates)) {
            $scriptService = new StoryModeScriptService();
            $targetDuration = (int) get_option('story_mode_default_duration', 35);
            $currentSegments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
            $currentCount = count($currentSegments);
            $cachedCount = count($this->sceneImageCandidates);

            if ($currentCount === $cachedCount) {
                $this->generatedSegments = $currentSegments;
                $this->showTranscriptModal = false;
                $this->showImageSelectionModal = true;
                return;
            }

            // Scene count changed — invalidate stale candidates
            Log::info('StoryMode: Transcript changed scene count, re-sourcing images', [
                'cached' => $cachedCount, 'current' => $currentCount,
            ]);
            $this->resetImageSelectionState();
        }

        // Segment the transcript and source images
        $scriptService = new StoryModeScriptService();
        $targetDuration = (int) get_option('story_mode_default_duration', 35);
        $segments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);

        // Source images (opens image selection modal via trait)
        $this->sourceImagesForScenes($segments, ['subject' => $this->prompt], []);
    }

    /**
     * Confirm image selection and dispatch the generation pipeline.
     * Downloads selected images/videos and creates the project with pre-assigned scene media.
     */
    public function confirmImageSelection()
    {
        $this->showImageSelectionModal = false;
        $this->isGenerating = true;

        try {
            $scriptService = new StoryModeScriptService();
            $targetDuration = (int) get_option('story_mode_default_duration', 35);
            $segments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
            $wordCount = str_word_count($this->editableTranscript);

            // Build scenes array with pre-assigned images from selection
            $scenes = [];
            $imageService = new ImageSourceService();
            $tempProjectId = time();

            foreach ($segments as $i => $segment) {
                $scene = [
                    'id' => 'scene_' . $i,
                    'index' => $i,
                    'text' => $segment['text'],
                    'estimated_duration' => $segment['estimated_duration'],
                ];

                $sceneId = $scene['id'];
                $selection = $this->selectedSceneImages[$sceneId] ?? [];

                if ($selection !== 'ai' && !empty($selection)) {
                    $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
                    $sceneClips = [];

                    foreach ((array) $selection as $selIdx) {
                        $candidate = (is_int($selIdx) || ctype_digit((string) $selIdx))
                            ? ($candidates[(int) $selIdx] ?? null) : null;

                        if (!$candidate || empty($candidate['url'])) continue;

                        $clipData = ['type' => $candidate['type'] ?? 'image'];
                        $isVideo = $clipData['type'] === 'video';

                        if ($isVideo) {
                            if (($candidate['source'] ?? '') === 'artime_stock') {
                                $clipData['video_url'] = $candidate['url'];
                            } else {
                                $localUrl = $imageService->downloadAndStoreVideo(
                                    $candidate['url'], $tempProjectId, $sceneId . '_' . $selIdx
                                );
                                if ($localUrl) $clipData['video_url'] = $localUrl;
                            }
                            $clipData['duration'] = $candidate['duration'] ?? 0;
                            if (!empty($candidate['thumbnail'])) {
                                $clipData['thumbnail'] = $candidate['thumbnail'];
                            }
                        } else {
                            if (in_array($candidate['source'] ?? '', ['upload', 'artime_stock'])) {
                                $clipData['image_url'] = $candidate['url'];
                            } else {
                                $localUrl = $imageService->downloadAndStore(
                                    $candidate['url'], $tempProjectId, $sceneId . '_' . $selIdx
                                );
                                if ($localUrl) $clipData['image_url'] = $localUrl;
                            }
                        }

                        $sceneClips[] = $clipData;
                    }

                    if (!empty($sceneClips)) {
                        $scene['clips'] = $sceneClips;
                        $first = $sceneClips[0];
                        $scene['video_url'] = $first['video_url'] ?? null;
                        $scene['image_url'] = $first['image_url'] ?? $first['thumbnail'] ?? null;
                    }
                }

                // Attach crop/position data, animation flag, and video edits
                if (!empty($this->sceneCropData[$sceneId])) {
                    $scene['crop'] = $this->sceneCropData[$sceneId];
                }
                if (!empty($this->sceneVideoEdits[$sceneId])) {
                    $edit = $this->sceneVideoEdits[$sceneId];
                    $hasUserEdits = ($edit['trimStart'] ?? 0) > 0
                        || !empty($edit['flipH'])
                        || !empty($edit['flipV']);
                    if ($hasUserEdits) {
                        $scene['video_edit'] = $edit;
                    }
                }
                $scene['animate_with_ai'] = $this->sceneAnimateWithAI[$sceneId] ?? false;

                $scenes[] = $scene;
            }

            // Create the project
            $project = StoryModeProject::create([
                'user_id' => auth()->id(),
                'team_id' => session('current_team_id'),
                'title' => $this->generatedTitle ?: 'Untitled Story',
                'prompt' => $this->prompt,
                'style_id' => $this->selectedStyleId,
                'custom_style_instruction' => $this->customStyleInstruction ?: null,
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
                    'image_model' => $this->imageModel,
                    'image_source' => !empty($this->selectedSceneImages) ? 'real_images' : 'ai',
                    'attached_file' => $this->attachedFile
                        ? $this->attachedFile->store('story-mode/attachments', 'public')
                        : null,
                ],
            ]);

            $this->activeProjectId = $project->id;
            $this->detailProjectId = $project->id;
            Cache::forget('story-mode-projects-' . auth()->id());

            // Dispatch the pipeline as a background job
            StoryModeGenerationJob::dispatch($project->id)
                ->onQueue('video-wizard-images');

            Log::info('StoryMode: Generation job dispatched', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('StoryMode: Pipeline dispatch failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Video generation failed: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Update transcript word count on edit.
     */
    public function updatedEditableTranscript()
    {
        $this->transcriptWordCount = str_word_count($this->editableTranscript ?? '');
    }

    /**
     * Validate attached file on upload.
     */
    public function updatedAttachedFile()
    {
        $this->validate([
            'attachedFile' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mp3,wav,pdf,doc,docx,txt',
        ]);
    }

    /**
     * Remove the attached file.
     */
    public function removeAttachedFile()
    {
        $this->attachedFile = null;
    }

    /**
     * Select a style.
     */
    public function selectStyle(int $styleId)
    {
        $this->selectedStyleId = $this->selectedStyleId === $styleId ? null : $styleId;
        $this->customStyleInstruction = '';
    }

    /**
     * Open custom style modal.
     */
    public function openStyleModal()
    {
        $this->showStyleModal = true;
    }

    /**
     * Save custom style.
     */
    public function saveCustomStyle()
    {
        $this->validate([
            'customStyleInstruction' => 'required|string|min:10',
        ]);

        $this->selectedStyleId = null;
        $this->showStyleModal = false;
    }

    /**
     * Open voice selection modal.
     */
    public function openVoiceModal()
    {
        $this->showVoiceModal = true;
    }

    /**
     * Select a voice.
     */
    public function selectVoice(string $voiceId, string $provider = '')
    {
        $this->selectedVoice = $voiceId;
        $this->voiceProvider = $provider;
        $this->showVoiceModal = false;
    }

    /**
     * Open project detail overlay.
     */
    public function openProject(int $projectId)
    {
        $this->detailProjectId = $projectId;
    }

    /**
     * Close project detail overlay.
     */
    public function closeProject()
    {
        $this->detailProjectId = null;
    }

    /**
     * Use the style from a project.
     */
    public function useThisStyle(int $projectId)
    {
        $project = StoryModeProject::find($projectId);
        if ($project && $project->style_id) {
            $this->selectedStyleId = $project->style_id;
        } elseif ($project && $project->custom_style_instruction) {
            $this->customStyleInstruction = $project->custom_style_instruction;
            $this->selectedStyleId = null;
        }
        $this->detailProjectId = null;
    }

    /**
     * Delete a project.
     */
    public function deleteProject(int $projectId)
    {
        $project = StoryModeProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if ($project) {
            $project->deleteWithFiles();
            Cache::forget('story-mode-projects-' . auth()->id());
            if ($this->detailProjectId === $projectId) {
                $this->detailProjectId = null;
            }
            if ($this->activeProjectId === $projectId) {
                $this->activeProjectId = null;
            }
        }
    }

    /**
     * Get the active project for polling.
     * #[Computed] memoizes within a single request — no duplicate queries per render cycle.
     */
    #[Computed]
    public function activeProject()
    {
        if ($this->activeProjectId) {
            return StoryModeProject::with('style')->find($this->activeProjectId);
        }
        return null;
    }

    /**
     * Get the detail project for overlay.
     */
    #[Computed]
    public function detailProject()
    {
        if ($this->detailProjectId) {
            return StoryModeProject::with('style')->find($this->detailProjectId);
        }
        return null;
    }

    /**
     * Get user's projects — cached for 60 seconds to avoid re-querying on every poll.
     */
    #[Computed]
    public function userProjects()
    {
        return Cache::remember(
            'story-mode-projects-' . auth()->id(),
            60,
            fn () => StoryModeProject::forUser(auth()->id())
                ->with('style')
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get()
        );
    }

    /**
     * Get available voices.
     */
    public function getVoicesProperty(): array
    {
        return [
            ['id' => 'auto', 'name' => 'Auto Select', 'gender' => 'auto', 'provider' => '', 'description' => 'AI picks the best narrator for your story'],
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
        $styles = Cache::remember('story-mode-styles', 3600, function () {
            return StoryModeStyle::active()
                ->orderBy('sort_order')
                ->get();
        });

        return view('appvideowizard::livewire.story-mode', [
            'styles' => $styles,
        ]);
    }
}
