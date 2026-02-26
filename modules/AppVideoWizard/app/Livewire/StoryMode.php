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
use Modules\AppVideoWizard\Jobs\StoryModeGenerationJob;

class StoryMode extends Component
{
    use WithFileUploads;

    // Input state
    public string $prompt = '';
    public ?int $selectedStyleId = null;
    public string $customStyleInstruction = '';
    public $customStyleImage = null;
    public string $customStyleName = '';
    public string $aspectRatio = '9:16';
    public string $selectedVoice = 'auto';
    public string $voiceProvider = '';
    public string $videoResolution = '480p';
    public string $videoQuality = 'pro';
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
     * Confirm transcript and kick off the full pipeline.
     */
    public function confirmTranscript()
    {
        if (empty($this->editableTranscript)) {
            return;
        }

        $this->showTranscriptModal = false;
        $this->isGenerating = true;

        try {
            // Re-segment if transcript was edited
            $scriptService = new StoryModeScriptService();
            $targetDuration = (int) get_option('story_mode_default_duration', 35);
            $segments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
            $wordCount = str_word_count($this->editableTranscript);

            // Build initial scenes array from segments
            $scenes = [];
            foreach ($segments as $i => $segment) {
                $scenes[] = [
                    'id' => 'scene_' . $i,
                    'index' => $i,
                    'text' => $segment['text'],
                    'estimated_duration' => $segment['estimated_duration'],
                ];
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
