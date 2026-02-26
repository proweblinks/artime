<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Services\UrlContentExtractorService;
use Modules\AppVideoWizard\Services\StoryModeScriptService;
use Modules\AppVideoWizard\Jobs\UrlToVideoGenerationJob;

class UrlToVideo extends Component
{
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
     */
    public function submitPrompt()
    {
        if (empty($this->sourceUrl) && mb_strlen($this->prompt) < 10) {
            session()->flash('error', 'Please paste a URL or enter a prompt (at least 10 characters).');
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

                $enhancedPrompt = $extractor->buildEnhancedPrompt($contentBrief, $userPrompt);
            } else {
                // Prompt-only mode (no URL)
                $enhancedPrompt = $this->prompt;
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
     */
    public function confirmTranscript()
    {
        if (empty($this->editableTranscript)) {
            return;
        }

        $this->showTranscriptModal = false;
        $this->isGenerating = true;

        try {
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
                ],
            ]);

            $this->activeProjectId = $project->id;
            $this->detailProjectId = $project->id;
            Cache::forget('url-to-video-projects-' . auth()->id());

            UrlToVideoGenerationJob::dispatch($project->id)
                ->onQueue('video-wizard-images');

            Log::info('UrlToVideo: Generation job dispatched', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('UrlToVideo: Pipeline dispatch failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Video generation failed: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
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
