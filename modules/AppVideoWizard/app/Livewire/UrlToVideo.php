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
use Modules\AppVideoWizard\Services\FilmTemplateService;
use Modules\AppVideoWizard\Services\ImageSourceService;
use Modules\AppVideoWizard\Jobs\UrlToVideoGenerationJob;
use Modules\AppVideoWizard\Traits\HasImageSelection;

class UrlToVideo extends Component
{
    use WithFileUploads;
    use HasImageSelection;

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

    // Film Mode state
    public bool $filmMode = false;
    public ?string $selectedFilmTemplate = null;
    public ?array $filmTemplateConfig = null;

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

    // Recreate: stores original project's scenes for restoring clip selections
    public ?int $recreateFromProjectId = null;

    // Draft: tracks the active draft project for auto-save/resume
    public ?int $draftProjectId = null;

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

            // Film mode: build screenplay prompt from template
            if ($this->filmMode && $this->filmTemplateConfig) {
                $filmService = new FilmTemplateService();
                $userConcept = trim(preg_replace('/(https?:\/\/[^\s<>"\']+)/i', '', $this->prompt));

                // If URL provided, extract content subject for richer context
                if (!empty($this->sourceUrl)) {
                    $sourceType = $this->detectedSourceType ?: $extractor->detectSourceType($this->sourceUrl);
                    $extractedContent = $extractor->extract($this->sourceUrl, $sourceType);
                    $this->storedExtractedContent = $extractedContent;
                    $contentBrief = $extractor->analyzeContent($extractedContent, $userConcept ?: null);
                    $this->storedContentBrief = $contentBrief;
                    $userConcept = $contentBrief['subject'] ?? $extractedContent['title'] ?? $userConcept;
                } else {
                    $this->storedExtractedContent = [];
                    $this->storedContentBrief = [];
                }

                $enhancedPrompt = $filmService->buildScreenplayPrompt(
                    $this->filmTemplateConfig,
                    $userConcept ?: $this->prompt,
                    $this->videoDuration
                );

                // Generate screenplay (rawPrompt=true so it uses the prompt as-is with JSON output)
                $scriptService = new StoryModeScriptService();
                $maxWords = $this->calculateMaxWords($this->videoDuration);
                $result = $scriptService->generateScript($enhancedPrompt, $this->videoDuration, $maxWords, true);

                $this->editableTranscript = $result['transcript'];
                $this->transcriptWordCount = $result['word_count'];
                $this->generatedTitle = $result['title'] ?? 'Untitled Film';
                $this->generatedSegments = $result['segments'] ?? [];
                $this->creativeConceptTitle = $result['concept_title'] ?? null;
                $this->creativeConceptPitch = $result['concept_pitch'] ?? null;

                $this->showTranscriptModal = true;
                return;
            }

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
     * Confirm transcript and source images for media selection.
     * Always goes through the image selection modal now.
     */
    public function confirmTranscript()
    {
        if (empty($this->editableTranscript)) {
            return;
        }

        $scriptService = new StoryModeScriptService();
        $targetDuration = $this->videoDuration;
        $useScreenplay = $this->filmMode && $this->filmTemplateConfig;

        // If we already have candidates, verify scene count still matches the transcript
        if (!empty($this->sceneImageCandidates)) {
            $currentSegments = $useScreenplay
                ? $scriptService->segmentScreenplay($this->editableTranscript, $targetDuration)
                : $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
            $currentCount = count($currentSegments);
            $cachedCount = count($this->sceneImageCandidates);

            if ($currentCount === $cachedCount) {
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

        // Segment the transcript and source images
        $segments = $useScreenplay
            ? $scriptService->segmentScreenplay($this->editableTranscript, $targetDuration)
            : $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);

        // Use the trait's sourceImagesForScenes which handles sourcing + opening modal
        $this->sourceImagesForScenes($segments, $this->storedContentBrief, $this->storedExtractedContent);

        // If recreating from an existing project, prepend original clips after sourcing
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

                    $origUrl = $candidate['url'];
                    $this->sceneImageCandidates[$sceneId] = array_values(array_filter(
                        $this->sceneImageCandidates[$sceneId],
                        fn($c) => ($c['url'] ?? '') !== $origUrl
                    ));

                    array_unshift($this->sceneImageCandidates[$sceneId], $candidate);

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
            $this->recreateFromProjectId = null;
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
            $this->resetImageSelectionState();
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
        $wordCount = str_word_count($this->editableTranscript);

        // Use pre-split segments from AI Studio if available, otherwise segment fresh
        if (!empty($this->sceneVisualScript) && !empty($this->generatedSegments)) {
            $segments = $this->generatedSegments;
        } else {
            $scriptService = new StoryModeScriptService();
            $targetDuration = $this->videoDuration;
            $useScreenplay = $this->filmMode && $this->filmTemplateConfig;
            $segments = $useScreenplay
                ? $scriptService->segmentScreenplay($this->editableTranscript, $targetDuration)
                : $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
        }

        $scenes = [];
        foreach ($segments as $i => $segment) {
            $scene = [
                'id' => 'scene_' . $i,
                'index' => $i,
                'text' => $segment['text'],
                'estimated_duration' => $segment['estimated_duration'],
            ];
            // Film mode: carry screenplay-specific fields
            if (!empty($segment['direction'])) {
                $scene['direction'] = $segment['direction'];
            }
            if (!empty($segment['is_visual_only'])) {
                $scene['is_visual_only'] = true;
            }
            $scenes[] = $scene;
        }

        $sourceType = $this->detectedSourceType ?: 'prompt';
        $imageSource = !empty($this->selectedSceneImages) ? 'real_images' : 'ai';

        // Download selected images and assign URLs to scenes
        if (!empty($this->selectedSceneImages)) {
            $imageService = new ImageSourceService();

            // Create a temporary project ID for file storage
            $tempProjectId = time();

            foreach ($scenes as &$scene) {
                $sceneId = $scene['id'];
                $selection = $this->selectedSceneImages[$sceneId] ?? [];

                if ($selection === 'ai' || empty($selection)) {
                    // Scene will use AI generation — leave image_url empty
                    continue;
                }

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
                    // Backward compat: first clip's URL as primary
                    $first = $sceneClips[0];
                    $scene['video_url'] = $first['video_url'] ?? null;
                    $scene['image_url'] = $first['image_url'] ?? $first['thumbnail'] ?? null;
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
                    $edit = $this->sceneVideoEdits[$sceneId];
                    // Only attach video_edit if user made intentional edits (manual trim point or flips).
                    // Auto-trim-only edits (trimStart=0, no flips) use stale estimated_duration —
                    // the orchestrator will set correct trimEnd after voiceover generates real audio_duration.
                    $hasUserEdits = ($edit['trimStart'] ?? 0) > 0
                        || !empty($edit['flipH'])
                        || !empty($edit['flipV']);
                    if ($hasUserEdits) {
                        $scene['video_edit'] = $edit;
                    }
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

        // Attach visual script data from AI Studio (enables smart-skip in orchestrator)
        if (!empty($this->sceneVisualScript)) {
            foreach ($scenes as &$scene) {
                $sceneId = $scene['id'] ?? '';
                $visual = $this->sceneVisualScript[$sceneId] ?? [];
                if (!empty($visual)) {
                    $scene['image_prompt'] = $visual['image_prompt'] ?? '';
                    $scene['video_action'] = $visual['video_action'] ?? '';
                    $scene['camera_motion'] = $visual['camera_motion'] ?? 'slow zoom in';
                    $scene['mood'] = $visual['mood'] ?? 'professional';
                    $scene['voice_emotion'] = $visual['voice_emotion'] ?? 'neutral';
                    $scene['characters_in_scene'] = $visual['characters_in_scene'] ?? [];
                    $scene['transition_type'] = $visual['transition_type'] ?? 'fade';
                    $scene['transition_duration'] = (float) ($visual['transition_duration'] ?? 0.5);
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
            'visual_script' => !empty($this->sceneVisualScript) ? array_values($this->sceneVisualScript) : null,
            'metadata' => [
                'started_at' => now()->toIso8601String(),
                'ai_engine' => get_option('story_mode_ai_engine', 'gemini'),
                'video_resolution' => $this->videoResolution,
                'video_quality' => $this->videoQuality,
                'video_duration_target' => $this->videoDuration,
                'narrative_style' => $this->filmMode ? 'film' : ($this->creativeMode ? 'creative' : $this->narrativeStyle),
                'image_source' => $imageSource,
                'creative_mode' => $this->creativeMode,
                'creative_concept_title' => $this->creativeConceptTitle,
                'creative_concept_pitch' => $this->creativeConceptPitch,
                'character_bible' => $this->filmMode
                    ? (new FilmTemplateService())->getCharacterBibleForTemplate($this->filmTemplateConfig ?? [])
                    : $this->characterBible,
                'interactive_studio' => !empty($this->sceneVisualScript),
                'visual_style' => $this->selectedVisualStyle ?? 'cinematic',
                'visual_style_config' => $this->filmMode && $this->filmTemplateConfig
                    ? (new FilmTemplateService())->getVisualStyleConfig($this->filmTemplateConfig)
                    : (self::VISUAL_STYLE_PRESETS[$this->selectedVisualStyle ?? 'cinematic'] ?? null),
                'film_mode' => $this->filmMode,
                'film_template' => $this->selectedFilmTemplate,
                'film_template_config' => $this->filmTemplateConfig,
            ],
        ]);

        $this->activeProjectId = $project->id;
        $this->detailProjectId = $project->id;

        // Clean up draft now that the real project exists
        if ($this->draftProjectId) {
            UrlToVideoProject::where('id', $this->draftProjectId)
                ->where('status', 'draft')
                ->delete();
            $this->draftProjectId = null;
        }

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
        $this->resetImageSelectionState();
    }

    public function updatedCreativeMode()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->resetImageSelectionState();
        $this->creativeConceptTitle = null;
        $this->creativeConceptPitch = null;
        $this->alternativeConcepts = [];
        $this->showConceptCards = false;
        if ($this->creativeMode) {
            $this->filmMode = false;
            $this->selectedFilmTemplate = null;
            $this->filmTemplateConfig = null;
        }
    }

    public function updatedFilmMode()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->resetImageSelectionState();
        if ($this->filmMode) {
            $this->creativeMode = false;
            $this->creativeConceptTitle = null;
            $this->creativeConceptPitch = null;
        } else {
            $this->selectedFilmTemplate = null;
            $this->filmTemplateConfig = null;
        }
    }

    public function selectFilmTemplate(string $slug): void
    {
        $filmService = new FilmTemplateService();
        $template = $filmService->getTemplate($slug);

        if (!$template) {
            return;
        }

        $this->selectedFilmTemplate = $slug;
        $this->filmTemplateConfig = $template;
        $this->filmMode = true;
        $this->creativeMode = false;
        $this->aspectRatio = $template['aspect_ratio'] ?? '16:9';
        $this->videoDuration = $template['duration_default'] ?? 120;
        $this->selectedVisualStyle = $template['visual_style'] ?? 'cyberpunk';

        // Clear stale state
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->resetImageSelectionState();
    }

    public function clearFilmTemplate(): void
    {
        $this->filmMode = false;
        $this->creativeMode = false;
        $this->selectedFilmTemplate = null;
        $this->filmTemplateConfig = null;
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->resetImageSelectionState();
    }

    #[Computed]
    public function filmTemplates(): array
    {
        return (new FilmTemplateService())->getTemplates();
    }

    public function updatedVideoDuration()
    {
        $this->editableTranscript = null;
        $this->generatedTitle = null;
        $this->generatedSegments = [];
        $this->resetImageSelectionState();
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
     * Cancel a generating project — sets status to cancelled so the server-side job stops.
     */
    public function cancelProject(int $projectId)
    {
        $project = UrlToVideoProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if ($project && $project->isGenerating()) {
            $project->update([
                'status' => 'cancelled',
                'current_stage' => 'Cancelled by user',
            ]);

            Log::info('UrlToVideo: Project cancelled by user', ['project_id' => $projectId]);

            // Delete the cancelled project and its files
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
        $this->filmMode = $meta['film_mode'] ?? false;
        $this->selectedFilmTemplate = $meta['film_template'] ?? null;
        $this->filmTemplateConfig = $meta['film_template_config'] ?? null;

        // Store the original project ID so confirmTranscript can restore clips
        $this->recreateFromProjectId = $projectId;

        // Reset cached image selections (will be rebuilt on confirmTranscript)
        $this->resetImageSelectionState();

        // Close the detail modal and show the transcript editor
        $this->detailProjectId = null;
        $this->showTranscriptModal = true;

        Log::info('UrlToVideo: Recreating project — showing transcript editor', [
            'original_project_id' => $projectId,
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
