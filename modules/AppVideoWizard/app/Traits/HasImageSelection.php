<?php

namespace Modules\AppVideoWizard\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\AnimationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\ImageSourceService;
use Modules\AppVideoWizard\Services\SeedancePromptService;
use Modules\AppVideoWizard\Services\FilmTemplateService;
use Modules\AppVideoWizard\Services\StoryModeScriptService;
use Modules\AppVideoWizard\Services\UrlToVideoOrchestrator;
use Modules\AdminCredits\Facades\Credit;

/**
 * Shared image selection properties and methods for URL-to-Video and Story Mode.
 *
 * Provides the full image sourcing, selection, search, upload, library browsing,
 * crop/trim/animation state used by the image selection modal.
 */
trait HasImageSelection
{
    // Image selection modal state
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
    public int $libraryPage = 1;
    public bool $libraryHasMore = false;
    public string $librarySearchQuery = '';
    public string $librarySort = 'title';
    public string $libraryTypeFilter = '';

    // AI Studio: Visual Script state
    public array $sceneVisualScript = [];        // sceneId => {image_prompt, video_action, camera_motion, mood, ...}
    public bool $isGeneratingVisualScript = false;
    public ?array $characterBible = null;

    // AI Studio: Per-scene image generation
    public array $sceneGeneratedImages = [];     // sceneId => [{url, timestamp}]
    public array $sceneImageGenerating = [];     // sceneId => bool

    // AI Studio: Per-scene video prompt generation (on-demand via Gemini vision)
    public array $sceneVideoPromptGenerating = []; // sceneId => bool
    public bool $isBatchGeneratingVideoPrompts = false;
    public int $batchVideoPromptProgress = 0;
    public int $batchVideoPromptTotal = 0;

    // AI Studio: Per-scene video generation
    public array $sceneVideoTaskIds = [];        // sceneId => taskId
    public array $sceneVideoStatus = [];         // sceneId => 'idle'|'submitting'|'processing'|'completed'|'failed'
    public array $sceneGeneratedVideos = [];     // sceneId => [{url, duration, timestamp}]

    // AI Studio: Active scene for preview panel
    public string $activeStudioScene = '';

    // AI Studio: Visual style
    public string $selectedVisualStyle = 'cinematic';

    protected const VISUAL_STYLE_PRESETS = [
        'cinematic' => [
            'name' => 'Cinematic',
            'icon' => 'fa-light fa-film',
            'color' => '#f59e0b',
            'imagePrefix' => 'Cinematic shot, dramatic lighting, film grain, shallow depth of field',
            'imageSuffix' => 'anamorphic lens, professional color grading',
            'videoAnchor' => 'Cinematic, film grain, anamorphic lens flare',
            'videoLighting' => 'dramatic directional lighting with natural shadows',
            'videoColor' => 'rich color grading with warm highlights and cool shadows',
        ],
        'photorealistic' => [
            'name' => 'Photorealistic',
            'icon' => 'fa-light fa-camera',
            'color' => '#3b82f6',
            'imagePrefix' => 'Photorealistic, highly detailed, professional photography, 8K',
            'imageSuffix' => 'sharp focus, natural lighting, realistic textures',
            'videoAnchor' => 'Photorealistic, hyper-detailed, natural movement',
            'videoLighting' => 'natural lighting with realistic shadows',
            'videoColor' => 'true-to-life color reproduction',
        ],
        'pixar' => [
            'name' => 'Pixar 3D',
            'icon' => 'fa-light fa-cube',
            'color' => '#8b5cf6',
            'imagePrefix' => 'Pixar-quality 3D animation, smooth surfaces, expressive characters, vibrant',
            'imageSuffix' => 'subsurface scattering, volumetric lighting, polished render',
            'videoAnchor' => '3D animation style, smooth character movement, Pixar quality',
            'videoLighting' => 'soft volumetric studio lighting with colorful bounced light',
            'videoColor' => 'vivid saturated colors with warm tones',
        ],
        'anime' => [
            'name' => 'Anime',
            'icon' => 'fa-light fa-sparkles',
            'color' => '#ec4899',
            'imagePrefix' => 'Anime style, manga aesthetic, cel-shaded, vibrant colors, clean lines',
            'imageSuffix' => 'expressive characters, detailed backgrounds, Studio Ghibli quality',
            'videoAnchor' => 'Anime style, cel-shaded animation, vibrant',
            'videoLighting' => 'stylized anime lighting with sharp highlights',
            'videoColor' => 'vibrant anime color palette with bold highlights',
        ],
        'illustration' => [
            'name' => 'Illustration',
            'icon' => 'fa-light fa-palette',
            'color' => '#10b981',
            'imagePrefix' => 'Digital illustration, concept art, artstation trending',
            'imageSuffix' => 'highly detailed, vibrant colors, professional artwork',
            'videoAnchor' => 'Digital illustration style, painterly motion',
            'videoLighting' => 'stylized artistic lighting',
            'videoColor' => 'vibrant illustration color palette',
        ],
        'watercolor' => [
            'name' => 'Watercolor',
            'icon' => 'fa-light fa-paintbrush',
            'color' => '#06b6d4',
            'imagePrefix' => 'Watercolor painting, soft edges, flowing colors, artistic',
            'imageSuffix' => 'painterly, organic textures, paper texture visible',
            'videoAnchor' => 'Watercolor painting aesthetic, soft flowing edges',
            'videoLighting' => 'soft diffused natural light',
            'videoColor' => 'watercolor washes with bleeding color transitions',
        ],
        'noir' => [
            'name' => 'Film Noir',
            'icon' => 'fa-light fa-moon',
            'color' => '#6b7280',
            'imagePrefix' => 'Film noir, black and white, high contrast, deep shadows',
            'imageSuffix' => 'venetian blinds shadows, moody atmosphere, classic cinema',
            'videoAnchor' => 'Film noir, black and white, high contrast',
            'videoLighting' => 'harsh directional key light with deep noir shadows',
            'videoColor' => 'monochrome with rich tonal range',
        ],
        'cyberpunk' => [
            'name' => 'Cyberpunk',
            'icon' => 'fa-light fa-microchip',
            'color' => '#a855f7',
            'imagePrefix' => 'Cyberpunk, neon lights, futuristic, rain-soaked streets, holographic',
            'imageSuffix' => 'neon reflections, dystopian atmosphere, Blade Runner',
            'videoAnchor' => 'Cyberpunk neon, rain reflections, holographic displays',
            'videoLighting' => 'neon-lit with cool blue and hot pink rim lights',
            'videoColor' => 'teal-and-magenta neon color grading',
        ],
        'vintage' => [
            'name' => 'Vintage Film',
            'icon' => 'fa-light fa-cassette-tape',
            'color' => '#d97706',
            'imagePrefix' => 'Vintage photograph, retro aesthetic, 1970s film stock',
            'imageSuffix' => 'film grain, faded warm colors, nostalgic mood',
            'videoAnchor' => 'Vintage film stock, visible grain, retro',
            'videoLighting' => 'warm amber natural light with soft diffusion',
            'videoColor' => 'faded warm tones, yellow-green vintage tint',
        ],
        'minimalist' => [
            'name' => 'Minimalist',
            'icon' => 'fa-light fa-square',
            'color' => '#94a3b8',
            'imagePrefix' => 'Minimalist design, clean composition, negative space, modern',
            'imageSuffix' => 'elegant simplicity, subtle shadows',
            'videoAnchor' => 'Clean minimalist, negative space, modern',
            'videoLighting' => 'clean even studio lighting with subtle shadows',
            'videoColor' => 'muted desaturated palette with selective color accents',
        ],
    ];

    /**
     * Reset all image selection state. Useful when script changes invalidate cached data.
     */
    public function resetImageSelectionState(): void
    {
        $this->sceneImageCandidates = [];
        $this->sceneSearchSuggestions = [];
        $this->selectedSceneImages = [];
        $this->sceneAnimateWithAI = [];
        $this->sceneCropData = [];
        $this->sceneVideoEdits = [];
        $this->showImageSelectionModal = false;
        $this->showLibraryBrowser = false;
        $this->libraryPage = 1;
        $this->libraryHasMore = false;
        $this->librarySearchQuery = '';
        $this->librarySort = 'title';
        $this->libraryTypeFilter = '';

        // AI Studio cleanup
        $this->sceneVisualScript = [];
        $this->isGeneratingVisualScript = false;
        $this->characterBible = null;
        $this->sceneGeneratedImages = [];
        $this->sceneImageGenerating = [];
        $this->sceneVideoTaskIds = [];
        $this->sceneVideoStatus = [];
        $this->sceneGeneratedVideos = [];
        $this->activeStudioScene = '';
        $this->selectedVisualStyle = 'cinematic';
    }

    /**
     * Source images for scenes: search stock/article photos, populate candidates,
     * auto-select first candidate per scene, and open the image selection modal.
     *
     * @param array $segments  Segmented transcript scenes
     * @param array $contentBrief  Content brief with subject info
     * @param array $extractedContent  Raw extracted content (article images, etc.)
     */
    public function sourceImagesForScenes(array $segments, array $contentBrief, array $extractedContent): void
    {
        $this->showTranscriptModal = false;
        $this->isSourcingImages = true;

        try {
            // Film mode: skip stock search, ALL scenes use AI generation
            if ($this->filmMode ?? false) {
                $this->sceneImageCandidates = [];
                $this->sceneSearchSuggestions = [];
                $this->selectedSceneImages = [];

                foreach ($segments as $i => $segment) {
                    $sceneId = 'scene_' . $i;
                    $this->sceneImageCandidates[$sceneId] = [];
                    $this->selectedSceneImages[$sceneId] = 'ai';
                    // Force AI animation for all film scenes
                    $this->sceneAnimateWithAI[$sceneId] = true;
                }

                $this->generatedSegments = $segments;
                $this->showImageSelectionModal = true;
                $this->isSourcingImages = false;
                return;
            }

            $scenes = [];
            foreach ($segments as $i => $segment) {
                $scenes[] = [
                    'id' => 'scene_' . $i,
                    'index' => $i,
                    'text' => $segment['text'],
                    'estimated_duration' => $segment['estimated_duration'],
                ];
            }

            // Sync generatedSegments with actual scene segmentation
            $this->generatedSegments = $segments;

            $imageService = new ImageSourceService();

            // Ensure content brief has a subject for stock search
            if (empty($contentBrief['subject'])) {
                $contentBrief['subject'] = $this->prompt ?: ($this->generatedTitle ?? '');
            }

            $result = $imageService->sourceForScenes($scenes, $extractedContent, $contentBrief);

            // Split structured result into candidates and suggestions
            $this->sceneImageCandidates = [];
            $this->sceneSearchSuggestions = [];
            foreach ($result as $sceneId => $data) {
                $this->sceneImageCandidates[$sceneId] = $data['candidates'] ?? [];
                $this->sceneSearchSuggestions[$sceneId] = $data['suggestions'] ?? [];
            }

            // Auto-select first candidate per scene and auto-trim long video clips
            $this->selectedSceneImages = [];
            foreach ($this->sceneImageCandidates as $sceneId => $sceneCandidates) {
                if (!empty($sceneCandidates)) {
                    $this->selectedSceneImages[$sceneId] = [0];
                    $firstCandidate = $sceneCandidates[0];
                    if (($firstCandidate['type'] ?? 'image') === 'video') {
                        $this->sceneAnimateWithAI[$sceneId] = $this->sceneAnimateWithAI[$sceneId] ?? false;
                        if (!isset($this->sceneVideoEdits[$sceneId])) {
                            $this->autoTrimVideoClip($sceneId, $firstCandidate);
                        }
                    }
                } else {
                    $this->selectedSceneImages[$sceneId] = 'ai';
                }
            }

            $this->showImageSelectionModal = true;
        } catch (\Exception $e) {
            Log::error('HasImageSelection: Image sourcing failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to source images: ' . $e->getMessage());
        } finally {
            $this->isSourcingImages = false;
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
     * Toggle a clip selection for a scene (multi-clip: add/remove from array).
     */
    public function selectSceneImage(string $sceneId, int $candidateIndex)
    {
        if (!isset($this->selectedSceneImages[$sceneId]) || !is_array($this->selectedSceneImages[$sceneId])) {
            $this->selectedSceneImages[$sceneId] = [];
        }

        $clips = $this->selectedSceneImages[$sceneId];
        $pos = array_search($candidateIndex, $clips);
        if ($pos !== false) {
            array_splice($clips, $pos, 1);
        } else {
            $clips[] = $candidateIndex;
        }

        $this->selectedSceneImages[$sceneId] = array_values($clips);

        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
        $candidate = $candidates[$candidateIndex] ?? null;
        if ($candidate && ($candidate['type'] ?? 'image') === 'video') {
            $this->sceneAnimateWithAI[$sceneId] = false;
        }
    }

    /**
     * Remove a clip from a scene's selection by position.
     */
    public function removeSceneClip(string $sceneId, int $clipPosition)
    {
        if (isset($this->selectedSceneImages[$sceneId]) && is_array($this->selectedSceneImages[$sceneId])) {
            array_splice($this->selectedSceneImages[$sceneId], $clipPosition, 1);
            $this->selectedSceneImages[$sceneId] = array_values($this->selectedSceneImages[$sceneId]);
        }
    }

    /**
     * Reorder a clip within a scene's selection.
     */
    public function reorderSceneClip(string $sceneId, int $fromPos, int $toPos)
    {
        $clips = $this->selectedSceneImages[$sceneId] ?? [];
        if (!is_array($clips) || !isset($clips[$fromPos]) || $toPos < 0 || $toPos >= count($clips)) return;
        $item = array_splice($clips, $fromPos, 1)[0];
        array_splice($clips, $toPos, 0, [$item]);
        $this->selectedSceneImages[$sceneId] = $clips;
    }

    /**
     * Toggle AI-generated image for a scene.
     */
    public function markSceneForAI(string $sceneId)
    {
        $current = $this->selectedSceneImages[$sceneId] ?? [];
        if ($current === 'ai') {
            $this->selectedSceneImages[$sceneId] = [];
            $this->sceneAnimateWithAI[$sceneId] = false;
        } else {
            $this->selectedSceneImages[$sceneId] = 'ai';
            $this->sceneAnimateWithAI[$sceneId] = true;
        }
    }

    /**
     * Toggle all scenes between AI-generated and stock images.
     * If all are already AI, revert to stock; otherwise set all to AI.
     */
    public function toggleAllScenesAI(): void
    {
        if ($this->areAllScenesAI()) {
            // If all scenes are already AI but visual script hasn't been generated yet,
            // generate it now (Film mode sets all scenes to AI automatically, so the
            // "set all to AI" path in setAllScenesAI() is never reached).
            if (empty($this->sceneVisualScript)) {
                $this->generateVisualScript();
            } else {
                $this->clearAllScenesAI();
            }
        } else {
            $this->setAllScenesAI();
        }
    }

    /**
     * Set all scenes to use AI-generated images.
     * Preserves selection for scenes that already have an AI-generated image selected.
     */
    public function setAllScenesAI(): void
    {
        foreach ($this->sceneImageCandidates as $sceneId => $candidates) {
            // If scene already has an AI-generated image selected, keep it
            $currentSelection = $this->selectedSceneImages[$sceneId] ?? null;
            if (is_array($currentSelection) && !empty($currentSelection)) {
                $lastIdx = end($currentSelection);
                $candidate = $candidates[(int) $lastIdx] ?? null;
                if ($candidate && ($candidate['source'] ?? '') === 'ai_generated') {
                    // Already has an AI-generated image — keep the selection
                    $this->sceneAnimateWithAI[$sceneId] = true;
                    continue;
                }
            }
            $this->selectedSceneImages[$sceneId] = 'ai';
            $this->sceneAnimateWithAI[$sceneId] = true;
        }

        // Auto-trigger visual script generation if not already done
        if (empty($this->sceneVisualScript)) {
            $this->generateVisualScript();
        }
    }

    /**
     * Revert scenes from AI back to best available candidate.
     * Prefers AI-generated images, then falls back to first stock candidate.
     * Scenes with no candidates stay on AI.
     */
    public function clearAllScenesAI(): void
    {
        foreach ($this->sceneImageCandidates as $sceneId => $candidates) {
            if (!empty($candidates)) {
                // Prefer the latest AI-generated image if one exists
                $aiIndex = null;
                foreach ($candidates as $idx => $c) {
                    if (($c['source'] ?? '') === 'ai_generated') {
                        $aiIndex = $idx; // Keep updating to get the LAST (newest) AI image
                    }
                }

                if ($aiIndex !== null) {
                    // Use the AI-generated image
                    $this->selectedSceneImages[$sceneId] = [$aiIndex];
                    $this->sceneAnimateWithAI[$sceneId] = false;
                } else {
                    // Fall back to first stock candidate
                    $this->selectedSceneImages[$sceneId] = [0];
                    $this->sceneAnimateWithAI[$sceneId] = false;

                    $firstCandidate = $candidates[0];
                    if (($firstCandidate['type'] ?? 'image') === 'video') {
                        $this->autoTrimVideoClip($sceneId, $firstCandidate);
                    }
                }
            }
            // Scenes with no candidates keep AI selection
        }
    }

    /**
     * Check if every scene is set to AI mode (either pending 'ai' or has an AI-generated image).
     */
    public function areAllScenesAI(): bool
    {
        if (empty($this->sceneImageCandidates)) {
            return false;
        }

        foreach ($this->sceneImageCandidates as $sceneId => $candidates) {
            $selection = $this->selectedSceneImages[$sceneId] ?? null;

            // Scene marked as 'ai' (pending generation)
            if ($selection === 'ai') {
                continue;
            }

            // Scene has an AI-generated image selected
            if (is_array($selection) && !empty($selection)) {
                $lastIdx = end($selection);
                $candidate = $candidates[(int) $lastIdx] ?? null;
                if ($candidate && ($candidate['source'] ?? '') === 'ai_generated') {
                    continue;
                }
            }

            // Scene has a stock image — not all AI
            return false;
        }

        return true;
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
     */
    protected function autoTrimVideoClip(string $sceneId, array $candidate): void
    {
        $clipDuration = (float) ($candidate['duration'] ?? 0);
        $sceneDuration = $this->getSceneDuration($sceneId);

        if ($clipDuration > 0 && $sceneDuration > 0 && $clipDuration > $sceneDuration) {
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            $this->sceneVideoEdits[$sceneId] = [
                'trimStart' => 0,
                'trimEnd' => round($sceneDuration, 1),
                'flipH' => $existing['flipH'] ?? false,
                'flipV' => $existing['flipV'] ?? false,
            ];
        } else {
            $existing = $this->sceneVideoEdits[$sceneId] ?? null;
            if ($existing) {
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

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Visual Script Generation
    // ────────────────────────────────────────────────────────────────────

    /**
     * Generate visual script (image_prompt + video_action) for all scenes.
     * Called automatically when AI Mode is toggled ON.
     */
    public function generateVisualScript(): void
    {
        if ($this->isGeneratingVisualScript) return;
        $this->isGeneratingVisualScript = true;

        try {
            // Phase 4: Split long scenes first
            $this->splitLongScenes();

            $segments = $this->generatedSegments ?? [];
            if (empty($segments)) {
                $this->isGeneratingVisualScript = false;
                return;
            }

            $aspectRatio = $this->aspectRatio ?? '9:16';
            $teamId = session('current_team_id');

            // Film mode: use FilmTemplateService for flowing image prompts with visual variety
            $isFilmMode = !empty($this->filmMode) && !empty($this->filmTemplateConfig);
            if ($isFilmMode) {
                $filmService = new FilmTemplateService();
                $visualScript = $filmService->buildFilmVisualScript($segments, $this->filmTemplateConfig);
                $this->characterBible = [];
            } else {
                // Standard/Creative mode: generic visual script
                $brief = property_exists($this, 'storedContentBrief') ? ($this->storedContentBrief ?? []) : [];
                $tone = $brief['tone'] ?? 'professional';
                $style = self::VISUAL_STYLE_PRESETS[$this->selectedVisualStyle] ?? self::VISUAL_STYLE_PRESETS['cinematic'];
                $styleInstruction = "{$style['imagePrefix']}. {$tone} tone. {$style['imageSuffix']}";

                $scriptService = new StoryModeScriptService();
                $visualScript = $scriptService->buildVisualScript($segments, $styleInstruction, $aspectRatio, $teamId);
                $this->characterBible = $scriptService->lastCharacterBible;
            }

            // Map results to sceneId keys
            $this->sceneVisualScript = [];
            foreach ($segments as $i => $segment) {
                $sceneId = 'scene_' . $i;
                $visual = $visualScript[$i] ?? [];
                $this->sceneVisualScript[$sceneId] = [
                    'image_prompt' => $visual['image_prompt'] ?? "A cinematic scene: {$segment['text']}",
                    'video_action' => $visual['video_action'] ?? '',
                    'camera_motion' => $visual['camera_motion'] ?? 'slow zoom in',
                    'mood' => $visual['mood'] ?? 'professional',
                    'voice_emotion' => $visual['voice_emotion'] ?? 'neutral',
                    'characters_in_scene' => $visual['characters_in_scene'] ?? [],
                    'transition_type' => $visual['transition_type'] ?? 'fade',
                    'transition_duration' => (float) ($visual['transition_duration'] ?? 0.5),
                    'location_hint' => $visual['location_hint'] ?? '',
                    'scene_type' => $visual['scene_type'] ?? '',
                ];
            }

            if ($isFilmMode) {
                // Film mode: skip enrichment — video prompts generated on-demand in AI Studio via Gemini vision
                foreach ($this->sceneVisualScript as $sceneId => &$visual) {
                    $visual['direction_context'] = $visual['video_action'] ?? '';
                    $visual['video_action'] = '';  // Empty until user generates on-demand
                }
                unset($visual);
            } else {
                // Standard/Creative mode: enrich video_action with 100+ word Seedance narrative prompts
                $orchestrator = new UrlToVideoOrchestrator();
                $style = self::VISUAL_STYLE_PRESETS[$this->selectedVisualStyle] ?? self::VISUAL_STYLE_PRESETS['cinematic'];
                foreach ($this->sceneVisualScript as $sceneId => &$visual) {
                    $sceneIndex = (int) str_replace('scene_', '', $sceneId);
                    $sceneData = [
                        'video_action' => $visual['video_action'] ?? '',
                        'direction' => $visual['video_action'] ?? '',
                        'text' => $segments[$sceneIndex]['text'] ?? '',
                        'camera_motion' => $visual['camera_motion'] ?? 'slow zoom in',
                        'mood' => $visual['mood'] ?? 'dramatic',
                        'has_dialogue' => (bool) preg_match('/^[A-Z][A-Z0-9_\s]+:\s*.+$/m', $segments[$sceneIndex]['text'] ?? ''),
                        'location_hint' => $visual['location_hint'] ?? '',
                    ];

                    $styleInstr = $styleInstruction ?? "Cinematic, photorealistic";
                    $richPrompt = $orchestrator->buildVideoPrompt($sceneData, $styleInstr, $aspectRatio, $style, null);

                    if (!empty($richPrompt) && str_word_count($richPrompt) > str_word_count($visual['video_action'] ?? '')) {
                        $visual['video_action'] = $richPrompt;
                    }
                }
                unset($visual);
            }

            // Set first scene as active in studio
            if (!empty($this->sceneVisualScript) && empty($this->activeStudioScene)) {
                $this->activeStudioScene = array_key_first($this->sceneVisualScript);
            }

            Log::info('HasImageSelection: Visual script generated for AI Studio with rich video prompts', [
                'scenes' => count($this->sceneVisualScript),
                'characters' => count($this->characterBible ?? []),
            ]);
        } catch (\Exception $e) {
            Log::error('HasImageSelection: Visual script generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate visual script: ' . $e->getMessage());
        } finally {
            $this->isGeneratingVisualScript = false;
            $this->saveDraftState();
        }
    }

    /**
     * Update a scene's image prompt from the editable textarea.
     */
    public function updateSceneImagePrompt(string $sceneId, string $prompt): void
    {
        if (isset($this->sceneVisualScript[$sceneId])) {
            $this->sceneVisualScript[$sceneId]['image_prompt'] = trim($prompt);
        }
    }

    /**
     * Update a scene's video action prompt from the editable textarea.
     */
    public function updateSceneVideoPrompt(string $sceneId, string $prompt): void
    {
        if (isset($this->sceneVisualScript[$sceneId])) {
            $this->sceneVisualScript[$sceneId]['video_action'] = trim($prompt);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: On-Demand Video Prompt Generation (Film Mode)
    // ────────────────────────────────────────────────────────────────────

    /**
     * Generate a Seedance video prompt for a specific scene using Gemini vision.
     * Analyzes the scene's generated image + script context to write a focused
     * motion-first prompt: subject + actions + camera + dialogue/sound cues.
     */
    public function generateSceneVideoPrompt(string $sceneId): void
    {
        $visual = $this->sceneVisualScript[$sceneId] ?? null;
        if (!$visual) return;

        $this->sceneVideoPromptGenerating[$sceneId] = true;

        try {
            $sceneIndex = (int) str_replace('scene_', '', $sceneId);
            $segment = $this->generatedSegments[$sceneIndex] ?? [];
            $teamId = session('current_team_id');

            // Gather scene context
            $direction = $segment['text'] ?? '';
            $directionContext = $visual['direction_context'] ?? '';
            $cameraMotion = $visual['camera_motion'] ?? 'slow zoom in';
            $mood = $visual['mood'] ?? 'dramatic';
            $charactersInScene = $visual['characters_in_scene'] ?? [];
            $locationHint = $visual['location_hint'] ?? '';
            $sceneType = $visual['scene_type'] ?? '';

            // Duration and word target
            $sceneDuration = (float) ($segment['estimated_duration'] ?? 6);
            $clipDuration = $this->calculateAIClipDuration($sceneDuration);
            $targetWords = $this->getTargetWordCount($clipDuration);

            // Get character bible from film template config
            $characters = $this->filmTemplateConfig['characters'] ?? [];

            // Natural camera direction
            $cameraDirection = $this->mapCameraMotionToNatural($cameraMotion);

            // Build dialogue text from direction (extract CHARACTER: lines)
            $dialogueText = '';
            if (preg_match_all('/^([A-Z][A-Z0-9_\s]+):\s*(.+)$/m', $direction, $matches, PREG_SET_ORDER)) {
                $dialogueParts = [];
                foreach ($matches as $m) {
                    $dialogueParts[] = trim($m[1]) . ' says: "' . trim($m[2]) . '"';
                }
                $dialogueText = implode('. ', $dialogueParts);
            }

            // Try to get scene image as base64 for vision analysis
            $imageBase64 = null;
            $mimeType = 'image/png';
            $imageUrl = $this->getSceneImageUrl($sceneId);
            if ($imageUrl) {
                try {
                    $imageContent = @file_get_contents($imageUrl);
                    if ($imageContent === false) {
                        $ch = curl_init($imageUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_TIMEOUT => 15,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ]);
                        $imageContent = curl_exec($ch);
                        curl_close($ch);
                    }
                    if ($imageContent !== false && strlen($imageContent) > 100) {
                        $imageBase64 = base64_encode($imageContent);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageContent) ?: 'image/png';
                    }
                } catch (\Exception $e) {
                    Log::warning('[VideoPrompt] Failed to fetch scene image', [
                        'scene' => $sceneId, 'url' => $imageUrl, 'error' => $e->getMessage(),
                    ]);
                }
            }

            // Build the prompt — Seedance 2.0 formula: Subject → Action → Camera → Style
            $atmosphere = $this->filmTemplateConfig['atmosphere'] ?? $mood;
            $systemInstruction = $this->buildVideoPromptSystemInstruction($characters, $atmosphere, $clipDuration, $targetWords);

            $userPrompt = $systemInstruction . "\n\n---\n\n";
            $userPrompt .= "Write a Seedance 2.0 video prompt for this scene. Output ONLY the prompt paragraph — no labels, no explanation.\n\n";
            $userPrompt .= "SCENE CONTEXT:\n";
            if (!empty($locationHint)) $userPrompt .= "- Location: {$locationHint}\n";
            if (!empty($sceneType)) $userPrompt .= "- Scene type: {$sceneType}\n";
            $userPrompt .= "- Mood/atmosphere: {$mood}\n";
            $userPrompt .= "- Camera movement: {$cameraDirection}\n";
            $userPrompt .= "- Clip duration: {$clipDuration} seconds (target {$targetWords} words)\n";
            if (!empty($charactersInScene)) {
                $userPrompt .= "- Characters in scene: " . implode(', ', $charactersInScene) . "\n";
            }
            if (!empty($directionContext)) {
                $userPrompt .= "\nSCENE DIRECTION (what happens):\n{$directionContext}\n";
            }
            if (!empty($dialogueText)) {
                $userPrompt .= "\nDIALOGUE TO INCLUDE:\n{$dialogueText}\n";
            }
            if ($imageBase64) {
                $userPrompt .= "\nThe attached image is the STARTING FRAME. Do NOT describe what the image looks like — Seedance already sees it. Instead, describe what should START MOVING from this frame: body motions, gestures, facial changes, object interactions, environmental physics (wind, rain, light flicker), and camera movement. Every sentence must describe visible MOTION.\n";
            } else {
                $userPrompt .= "\nNo image is available. Write the prompt based on the scene direction above, focusing entirely on MOTION: what the subject does, how they move, gestures, facial expressions, and camera movement. Do NOT describe static environment or appearance.\n";
            }
            $userPrompt .= "\nOutput exactly one paragraph of {$targetWords} words. Present tense. No headers. No markdown. Start directly with the subject's action.";

            // Call Gemini 2.5 Flash — always use analyzeImageWithPrompt for both vision and text
            $generatedPrompt = '';
            $gemini = app(\App\Services\GeminiService::class);

            if ($imageBase64) {
                // Vision mode: image + prompt
                $visionResult = $gemini->analyzeImageWithPrompt($imageBase64, $userPrompt, [
                    'model' => 'gemini-2.5-flash',
                    'mimeType' => $mimeType,
                    'temperature' => 0.8,
                    'maxOutputTokens' => 16384,
                ]);
                Log::debug('[VideoPrompt] Raw vision response', [
                    'scene' => $sceneId,
                    'success' => $visionResult['success'] ?? false,
                    'text_length' => strlen($visionResult['text'] ?? ''),
                    'text_words' => str_word_count($visionResult['text'] ?? ''),
                    'raw_text' => $visionResult['text'] ?? '(empty)',
                    'error' => $visionResult['error'] ?? null,
                    'finishReason' => $visionResult['finishReason'] ?? 'unknown',
                    'thinkingLength' => $visionResult['thinkingLength'] ?? 0,
                    'partsCount' => $visionResult['partsCount'] ?? 0,
                ]);
                if (!empty($visionResult['success']) && !empty($visionResult['text'])) {
                    $generatedPrompt = trim($visionResult['text']);
                } else {
                    Log::warning('[VideoPrompt] Vision call failed', [
                        'scene' => $sceneId,
                        'error' => $visionResult['error'] ?? 'unknown',
                    ]);
                }
            }

            // Text-only fallback (no image or vision failed)
            if (empty($generatedPrompt)) {
                $textResult = $gemini->generateText($userPrompt, 16384, 1, 'text', [
                    'model' => 'gemini-2.5-flash',
                    'temperature' => 0.8,
                ]);
                Log::debug('[VideoPrompt] Raw text response', [
                    'scene' => $sceneId,
                    'data_type' => gettype($textResult['data'] ?? null),
                    'data_count' => is_array($textResult['data'] ?? null) ? count($textResult['data']) : 'n/a',
                    'first_item_length' => is_array($textResult['data'] ?? null) ? strlen($textResult['data'][0] ?? '') : strlen((string)($textResult['data'] ?? '')),
                    'first_item_words' => is_array($textResult['data'] ?? null) ? str_word_count($textResult['data'][0] ?? '') : str_word_count((string)($textResult['data'] ?? '')),
                    'raw_preview' => substr(is_array($textResult['data'] ?? null) ? ($textResult['data'][0] ?? '') : (string)($textResult['data'] ?? ''), 0, 500),
                ]);
                if (!empty($textResult['data'])) {
                    $generatedPrompt = is_array($textResult['data'])
                        ? trim($textResult['data'][0] ?? '')
                        : trim((string) $textResult['data']);
                }
            }

            // Clean up AI artifacts: remove markdown, labels, thinking blocks
            if (!empty($generatedPrompt)) {
                // Remove ```...``` code blocks
                $generatedPrompt = preg_replace('/```[\s\S]*?```/', '', $generatedPrompt);
                // Remove **bold** markers
                $generatedPrompt = preg_replace('/\*\*([^*]+)\*\*/', '$1', $generatedPrompt);
                // Remove leading labels like "Prompt:" or "Video Prompt:"
                $generatedPrompt = preg_replace('/^(?:video\s+)?prompt\s*:\s*/i', '', trim($generatedPrompt));
                // Remove thinking blocks <think>...</think>
                $generatedPrompt = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $generatedPrompt);
                $generatedPrompt = trim($generatedPrompt);
            }

            // Sanitize via SeedancePromptService
            if (!empty($generatedPrompt) && class_exists(SeedancePromptService::class)) {
                $generatedPrompt = SeedancePromptService::sanitize($generatedPrompt);
            }

            $wordCount = str_word_count($generatedPrompt);
            if (!empty($generatedPrompt) && $wordCount >= 15) {
                $this->sceneVisualScript[$sceneId]['video_action'] = $generatedPrompt;
                Log::info('[VideoPrompt] Generated', [
                    'scene' => $sceneId,
                    'words' => $wordCount,
                    'mode' => $imageBase64 ? 'vision' : 'text',
                    'preview' => substr($generatedPrompt, 0, 120),
                ]);
            } else {
                Log::warning('[VideoPrompt] Output too short or empty', [
                    'scene' => $sceneId,
                    'words' => $wordCount,
                    'raw' => substr($generatedPrompt, 0, 200),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[VideoPrompt] Generation failed', [
                'scene' => $sceneId, 'error' => $e->getMessage(),
            ]);
        } finally {
            $this->sceneVideoPromptGenerating[$sceneId] = false;
        }
    }

    /**
     * Generate video prompts for ALL scenes sequentially (batch mode).
     * Processes one scene at a time for quality, with progress tracking.
     */
    public function generateAllSceneVideoPrompts(): void
    {
        if ($this->isBatchGeneratingVideoPrompts) return;

        $sceneIds = array_keys($this->sceneVisualScript);
        if (empty($sceneIds)) return;

        $this->isBatchGeneratingVideoPrompts = true;
        $this->batchVideoPromptTotal = count($sceneIds);
        $this->batchVideoPromptProgress = 0;

        try {
            foreach ($sceneIds as $sceneId) {
                $this->batchVideoPromptProgress++;

                // Skip scenes that already have a good prompt (30+ words)
                $existing = trim($this->sceneVisualScript[$sceneId]['video_action'] ?? '');
                if (str_word_count($existing) >= 30) {
                    continue;
                }

                $this->generateSceneVideoPrompt($sceneId);

                // Save draft periodically (every 5 scenes)
                if ($this->batchVideoPromptProgress % 5 === 0) {
                    $this->saveDraftState();
                }
            }

            $this->saveDraftState();

            // Count results
            $filled = 0;
            foreach ($this->sceneVisualScript as $v) {
                if (str_word_count($v['video_action'] ?? '') >= 15) $filled++;
            }
            Log::info('[VideoPrompt] Batch generation complete', [
                'total' => $this->batchVideoPromptTotal,
                'filled' => $filled,
            ]);
        } catch (\Exception $e) {
            Log::error('[VideoPrompt] Batch generation failed', [
                'progress' => $this->batchVideoPromptProgress,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isBatchGeneratingVideoPrompts = false;
            $this->batchVideoPromptProgress = 0;
            $this->batchVideoPromptTotal = 0;
        }
    }

    /**
     * Get the current image URL for a scene (from selected candidates or generated images).
     */
    protected function getSceneImageUrl(string $sceneId): ?string
    {
        $selection = $this->selectedSceneImages[$sceneId] ?? [];
        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];

        if (is_array($selection) && !empty($selection)) {
            $lastIdx = end($selection);
            $candidate = $candidates[(int) $lastIdx] ?? null;
            if ($candidate && !empty($candidate['url']) && ($candidate['type'] ?? 'image') !== 'video') {
                return $candidate['url'];
            }
        }

        return null;
    }

    /**
     * Calculate target word count based on clip duration.
     * ~11 words per second for Seedance prompts.
     */
    protected function getTargetWordCount(int $clipDuration): int
    {
        return (int) round($clipDuration * 11);
    }

    /**
     * Map camera motion shorthand to natural prose description.
     */
    protected function mapCameraMotionToNatural(string $cameraMotion): string
    {
        $cameraMap = [
            'slow zoom in'      => 'The camera slowly pushes in closer',
            'slow zoom out'     => 'The camera gradually pulls back to reveal more',
            'dramatic zoom in'  => 'The camera rapidly pushes in',
            'pan left'          => 'The camera pans slowly to the left',
            'pan right'         => 'The camera pans slowly to the right',
            'pan left slow'     => 'The camera drifts gently to the left',
            'pan right slow'    => 'The camera drifts gently to the right',
            'tilt up'           => 'The camera tilts slowly upward',
            'tilt down'         => 'The camera tilts slowly downward',
            'push to subject'   => 'The camera pushes steadily toward the subject',
            'rise and reveal'   => 'The camera rises upward in a crane shot, revealing the scene',
            'settle in'         => 'The camera settles with a subtle, nearly locked-off motion',
            'breathe'           => 'The camera holds with a very subtle breathing motion',
            'zoom in pan right' => 'The camera pushes in while panning right',
            'zoom out pan left' => 'The camera pulls back while panning left',
            'diagonal drift'    => 'The camera drifts diagonally in a floating motion',
        ];

        return $cameraMap[strtolower(trim($cameraMotion))] ?? 'The camera slowly pushes in';
    }

    /**
     * Build the system instruction for Gemini video prompt generation.
     */
    protected function buildVideoPromptSystemInstruction(array $characters, string $atmosphere, int $clipDuration, int $targetWords): string
    {
        $characterBible = '';
        if (!empty($characters)) {
            $charLines = [];
            foreach ($characters as $char) {
                $name = strtoupper($char['name'] ?? 'CHARACTER');
                $desc = $char['description'] ?? '';
                $charLines[] = "- {$name}: {$desc}";
            }
            $characterBible = "CHARACTERS:\n" . implode("\n", $charLines) . "\n\n";
        }

        return <<<SYSTEM
You write Seedance 2.0 video prompts. Seedance prompts follow: Subject → Action → Camera → Style.

{$characterBible}CLIP: {$clipDuration} seconds. Write exactly {$targetWords} words. Atmosphere: {$atmosphere}.

SEEDANCE FORMULA:
- SUBJECT: Name the character (use names from CHARACTERS above, never "the man/woman/figure"). State what they do with ONE clear present-tense verb per action beat.
- ACTION: Describe visible physical motion — hands, face, body, objects they interact with. Include micro-details: fingers tightening, breath visible, fabric shifting, hair moving. For dialogue: write the quoted line AND the physical delivery (jaw movement, breath pattern, gesture while speaking).
- CAMERA: Weave camera direction as a sentence in the prose. Use cinematic terms: dolly, push-in, handheld sway, locked-off, crane rise, rack focus. One movement per shot.
- STYLE: End with one environmental motion detail that grounds the scene (light flicker, rain streaking, dust motes, smoke drift).

CRITICAL RULES:
1. NEVER describe what the image looks like — no colors, no lighting, no environment description, no "neon-lit street", no "dim room". Seedance SEES the image already.
2. NEVER describe appearance or clothing — no "wearing a leather jacket", no "her silver hair". The image shows this already.
3. Every single sentence MUST describe MOTION — something physically moving or changing.
4. Start immediately with the subject's name and action verb. No scene-setting.
5. Single flowing paragraph. Present tense. No headers, labels, markdown, or quotation formatting.

BAD (describes environment + appearance — Seedance already sees this):
"A dark cyberpunk cityscape with neon-lit streets glistening in the rain. A man in a dark leather jacket with cybernetic implants stands at a holographic console, blue light illuminating his face. The atmosphere is tense and mysterious with electronic hums filling the air."

GOOD (describes only MOTION — what Seedance should animate):
"Ren leans forward, his fingers dancing across the holographic keys as data cascades faster across every screen. His eyes widen and his lips part, recognition flickering across his face. He traces a fractal pattern with his fingertip, pulling it closer as the camera pushes in steadily. He whispers 'There it is' through clenched teeth, his breath visible in the cold air. Behind him, a monitor flickers and the overhead light sways from a distant vibration."
SYSTEM;
    }

    /**
     * Set the active scene for the AI Studio preview panel.
     */
    public function setActiveStudioScene(string $sceneId): void
    {
        $this->activeStudioScene = $sceneId;
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Visual Style System
    // ────────────────────────────────────────────────────────────────────

    /**
     * Get the visual style presets for the frontend.
     */
    public function getVisualStylePresets(): array
    {
        return self::VISUAL_STYLE_PRESETS;
    }

    /**
     * Set the active visual style.
     */
    public function setVisualStyle(string $styleId): void
    {
        if (!isset(self::VISUAL_STYLE_PRESETS[$styleId])) {
            return;
        }

        $this->selectedVisualStyle = $styleId;

        if (!empty($this->sceneVisualScript)) {
            $this->dispatch('notify', type: 'info', message: __('Style updated. Click "Regenerate Prompts" to apply to existing scenes.'));
        }
    }

    /**
     * Get the active style's config array.
     */
    public function getActiveStyleConfig(): array
    {
        return self::VISUAL_STYLE_PRESETS[$this->selectedVisualStyle] ?? self::VISUAL_STYLE_PRESETS['cinematic'];
    }

    /**
     * Regenerate all prompts with the currently selected style.
     */
    public function regenerateAllPrompts(): void
    {
        if (empty($this->sceneVisualScript)) return;

        $this->generateVisualScript();
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Per-Scene Image Generation
    // ────────────────────────────────────────────────────────────────────

    /**
     * Generate an AI image for a specific scene using its image_prompt.
     */
    public function generateSceneAIImage(string $sceneId): void
    {
        $visual = $this->sceneVisualScript[$sceneId] ?? null;
        if (!$visual || empty($visual['image_prompt'])) {
            session()->flash('error', 'No image prompt available for this scene.');
            return;
        }

        $sceneIndex = (int) str_replace('scene_', '', $sceneId);

        Log::info('HasImageSelection: generateSceneAIImage called', [
            'sceneId' => $sceneId,
            'sceneIndex' => $sceneIndex,
            'prompt_preview' => substr($visual['image_prompt'], 0, 100),
            'visualScriptKeys' => array_keys($this->sceneVisualScript),
        ]);

        $this->sceneImageGenerating[$sceneId] = true;
        $this->activeStudioScene = $sceneId;

        try {
            $teamId = session('current_team_id');
            Credit::checkQuota($teamId);

            // Create temp WizardProject for service compatibility
            $wizardProject = WizardProject::create([
                'user_id' => auth()->id(),
                'team_id' => $teamId,
                'name' => '[AI Studio] Image Generation',
                'status' => 'processing',
                'aspect_ratio' => $this->aspectRatio ?? '9:16',
                'platform' => 'multi-platform',
            ]);

            $sceneIndex = (int) str_replace('scene_', '', $sceneId);
            $imageModel = $this->imageModel ?? get_option('story_mode_image_model', 'nanobanana2');

            $sceneData = [
                'id' => $sceneId,
                'visualDescription' => $visual['image_prompt'],
                'narration' => $this->generatedSegments[$sceneIndex]['text'] ?? '',
            ];

            // AI Studio: Use scene_0's image as CHARACTER identity reference
            //
            // CRITICAL FIX: Previously Scene 0's image was passed via storyboard['scenes'][0]
            // which was picked up as "continuity" reference (weak: "maintain lighting/color").
            // The strong IDENTITY ANCHOR + FACE CONSISTENCY instructions only fire for
            // CHARACTER references (with base64 images). By fetching Scene 0's image as base64
            // and injecting it as a character reference, the cascade now generates:
            //   "IDENTITY ANCHOR: Generate THIS EXACT SAME PERSON..."
            //   "CRITICAL FACE CONSISTENCY: Same facial structure, eyes, nose..."
            //
            // Additionally, the old storyboard index was wrong — scene_0 was always at index [0],
            // so getContinuityReference() only worked for scene_1 (looks at $sceneIndex - 1 = 0).
            // Scenes 2+ got NO references at all — pure text-to-image generation!
            $sceneMemory = null;
            $hasCharacterAnchor = false;

            // Find the first generated image (scene_0) to use as character anchor
            $firstImageUrl = null;
            $firstGenImages = $this->sceneGeneratedImages['scene_0'] ?? [];
            if (!empty($firstGenImages)) {
                $firstImageUrl = end($firstGenImages)['url'] ?? null;
            }

            // Build CHARACTER reference from scene_0's image for all subsequent scenes
            if ($firstImageUrl && $sceneIndex > 0) {
                // Fetch Scene 0's image as base64 for character identity reference
                $anchorBase64 = null;
                try {
                    $imageContent = @file_get_contents($firstImageUrl);
                    if ($imageContent) {
                        $anchorBase64 = base64_encode($imageContent);
                    }
                } catch (\Exception $e) {
                    Log::warning('HasImageSelection: Failed to fetch anchor image for cascade', [
                        'url' => $firstImageUrl,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Inject as CHARACTER reference — triggers IDENTITY ANCHOR + FACE CONSISTENCY
                // instructions in ImageGenerationService::buildCascadeEnhancedPrompt()
                if ($anchorBase64) {
                    $sceneMemory = [
                        'characterBible' => [
                            'enabled' => true,
                            'characters' => [[
                                'name' => 'Visual Anchor',
                                'description' => 'All characters exactly as shown in the reference image — maintain identical appearance, face, hair, clothing, and body type for every character visible',
                                'scenes' => [], // empty = applies to all scenes
                                'referenceImageStatus' => 'ready',
                                'referenceImageBase64' => $anchorBase64,
                                'referenceImageMimeType' => 'image/png',
                            ]],
                        ],
                        // Scene progression context for cascade prompt differentiation
                        'sceneContext' => [
                            'sceneIndex' => $sceneIndex,
                            'totalScenes' => count($this->sceneImageCandidates),
                            'narration' => $this->generatedSegments[$sceneIndex]['text'] ?? '',
                            'anchorSceneNarration' => $this->generatedSegments[0]['text'] ?? '',
                        ],
                    ];
                    $hasCharacterAnchor = true;

                    Log::info('HasImageSelection: Character anchor injected for cascade', [
                        'sceneId' => $sceneId,
                        'sceneIndex' => $sceneIndex,
                        'anchorImageUrl' => $firstImageUrl,
                        'anchorBase64Length' => strlen($anchorBase64),
                    ]);
                }
            }

            $imageService = app(ImageGenerationService::class);
            $result = $imageService->generateSceneImage($wizardProject, $sceneData, [
                'model' => $imageModel,
                'sceneIndex' => $sceneIndex,
                'teamId' => $teamId,
                'sceneMemory' => $sceneMemory,
                'useCascade' => $hasCharacterAnchor,
            ]);

            $imageUrl = $result['imageUrl'] ?? $result['image_url'] ?? null;

            if ($imageUrl) {
                // Track generated images
                if (!isset($this->sceneGeneratedImages[$sceneId])) {
                    $this->sceneGeneratedImages[$sceneId] = [];
                }
                $this->sceneGeneratedImages[$sceneId][] = [
                    'url' => $imageUrl,
                    'timestamp' => now()->toIso8601String(),
                ];

                // Add as candidate and auto-select
                $newCandidate = [
                    'url' => $imageUrl,
                    'thumbnail' => $imageUrl,
                    'source' => 'ai_generated',
                    'title' => 'AI Generated',
                    'type' => 'image',
                    'width' => 0,
                    'height' => 0,
                ];
                $this->sceneImageCandidates[$sceneId][] = $newCandidate;
                $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
                $this->selectedSceneImages[$sceneId] = [$newIndex];

                Log::info('HasImageSelection: AI image generated for scene', [
                    'scene' => $sceneId, 'url' => substr($imageUrl, 0, 80),
                ]);

                $this->saveDraftState();
            }

            $wizardProject->delete();
        } catch (\Exception $e) {
            Log::error('HasImageSelection: AI image generation failed', [
                'scene' => $sceneId, 'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Image generation failed: ' . $e->getMessage());
        } finally {
            $this->sceneImageGenerating[$sceneId] = false;
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Generation History — Select & Delete
    // ────────────────────────────────────────────────────────────────────

    /**
     * Select a previously generated image from history as the active image for a scene.
     */
    public function selectHistoryImage(string $sceneId, int $historyIndex): void
    {
        $history = $this->sceneGeneratedImages[$sceneId] ?? [];
        $entry = $history[$historyIndex] ?? null;
        if (!$entry || empty($entry['url'])) return;

        $targetUrl = $entry['url'];
        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];

        // Find the candidate with the matching URL
        $candidateIndex = null;
        foreach ($candidates as $idx => $candidate) {
            if (($candidate['url'] ?? '') === $targetUrl) {
                $candidateIndex = $idx;
                break;
            }
        }

        if ($candidateIndex !== null) {
            $this->selectedSceneImages[$sceneId] = [$candidateIndex];
        } else {
            // Candidate was removed or missing — re-add it
            $this->sceneImageCandidates[$sceneId][] = [
                'url' => $targetUrl,
                'thumbnail' => $targetUrl,
                'source' => 'ai_generated',
                'title' => 'AI Generated',
                'type' => 'image',
                'width' => 0,
                'height' => 0,
            ];
            $newIdx = count($this->sceneImageCandidates[$sceneId]) - 1;
            $this->selectedSceneImages[$sceneId] = [$newIdx];
        }

        $this->saveDraftState();
    }

    /**
     * Delete a generated image from history and its candidate entry.
     */
    public function deleteHistoryImage(string $sceneId, int $historyIndex): void
    {
        $history = $this->sceneGeneratedImages[$sceneId] ?? [];
        $entry = $history[$historyIndex] ?? null;
        if (!$entry) return;

        $deletedUrl = $entry['url'] ?? '';

        // Remove from generation history
        array_splice($this->sceneGeneratedImages[$sceneId], $historyIndex, 1);

        // Remove matching candidate
        if ($deletedUrl) {
            $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
            $wasSelected = false;
            foreach ($candidates as $idx => $candidate) {
                if (($candidate['url'] ?? '') === $deletedUrl) {
                    // Check if this candidate was selected
                    $selection = $this->selectedSceneImages[$sceneId] ?? [];
                    if (is_array($selection) && in_array($idx, $selection)) {
                        $wasSelected = true;
                    }
                    unset($this->sceneImageCandidates[$sceneId][$idx]);
                    break;
                }
            }
            // Re-index candidates array after removal
            $this->sceneImageCandidates[$sceneId] = array_values($this->sceneImageCandidates[$sceneId]);

            // If deleted image was selected, auto-select the latest remaining
            if ($wasSelected) {
                $remaining = $this->sceneGeneratedImages[$sceneId] ?? [];
                if (!empty($remaining)) {
                    $lastEntry = end($remaining);
                    $lastUrl = $lastEntry['url'] ?? '';
                    foreach ($this->sceneImageCandidates[$sceneId] as $idx => $c) {
                        if (($c['url'] ?? '') === $lastUrl) {
                            $this->selectedSceneImages[$sceneId] = [$idx];
                            break;
                        }
                    }
                } else {
                    $this->selectedSceneImages[$sceneId] = [];
                }
            } else {
                // Re-map selection indices after re-indexing
                $selection = $this->selectedSceneImages[$sceneId] ?? [];
                if (is_array($selection) && !empty($selection)) {
                    $selectedUrl = null;
                    // Try to find the currently selected URL in the old candidates
                    foreach ($candidates as $idx => $c) {
                        if (in_array($idx, $selection)) {
                            $selectedUrl = $c['url'] ?? null;
                            break;
                        }
                    }
                    if ($selectedUrl) {
                        foreach ($this->sceneImageCandidates[$sceneId] as $idx => $c) {
                            if (($c['url'] ?? '') === $selectedUrl) {
                                $this->selectedSceneImages[$sceneId] = [$idx];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $this->saveDraftState();

        Log::info('HasImageSelection: History image deleted', [
            'scene' => $sceneId,
            'historyIndex' => $historyIndex,
            'remainingHistory' => count($this->sceneGeneratedImages[$sceneId] ?? []),
        ]);
    }

    /**
     * Delete a generated video from history.
     */
    public function deleteHistoryVideo(string $sceneId, int $historyIndex): void
    {
        $history = $this->sceneGeneratedVideos[$sceneId] ?? [];
        $entry = $history[$historyIndex] ?? null;
        if (!$entry) return;

        $deletedUrl = $entry['url'] ?? '';

        // Remove from video history
        array_splice($this->sceneGeneratedVideos[$sceneId], $historyIndex, 1);

        // Remove matching candidate
        if ($deletedUrl) {
            $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
            foreach ($candidates as $idx => $candidate) {
                if (($candidate['url'] ?? '') === $deletedUrl && ($candidate['type'] ?? 'image') === 'video') {
                    unset($this->sceneImageCandidates[$sceneId][$idx]);
                    break;
                }
            }
            $this->sceneImageCandidates[$sceneId] = array_values($this->sceneImageCandidates[$sceneId]);
        }

        $this->saveDraftState();
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Per-Scene Video Generation
    // ────────────────────────────────────────────────────────────────────

    /**
     * Generate a video clip for a specific scene (requires image to exist).
     */
    public function generateSceneAIVideo(string $sceneId): void
    {
        // Require an image first
        $selection = $this->selectedSceneImages[$sceneId] ?? [];
        $candidates = $this->sceneImageCandidates[$sceneId] ?? [];
        $imageUrl = null;

        if (is_array($selection) && !empty($selection)) {
            $lastIdx = end($selection);
            $candidate = $candidates[(int) $lastIdx] ?? null;
            if ($candidate) {
                $imageUrl = $candidate['url'] ?? null;
            }
        }

        if (!$imageUrl) {
            session()->flash('error', 'Generate an image first before creating a video clip.');
            return;
        }

        $this->sceneVideoStatus[$sceneId] = 'submitting';
        $this->activeStudioScene = $sceneId;

        try {
            $teamId = session('current_team_id');
            Credit::checkQuota($teamId);

            $wizardProject = WizardProject::create([
                'user_id' => auth()->id(),
                'team_id' => $teamId,
                'name' => '[AI Studio] Video Generation',
                'status' => 'processing',
                'aspect_ratio' => $this->aspectRatio ?? '9:16',
                'platform' => 'multi-platform',
            ]);

            $sceneIndex = (int) str_replace('scene_', '', $sceneId);
            $visual = $this->sceneVisualScript[$sceneId] ?? [];
            $sceneDuration = $this->generatedSegments[$sceneIndex]['estimated_duration'] ?? 6;
            $clipDuration = $this->calculateAIClipDuration($sceneDuration);

            // Film mode: use on-demand video_action directly (Seedance prompt from Gemini vision)
            // No fallback to buildInteractiveVideoPrompt — that produces static environment descriptions
            $isFilmMode = !empty($this->filmMode) && !empty($this->filmTemplateConfig);
            if ($isFilmMode) {
                $prompt = trim($visual['video_action'] ?? '');
                if (empty($prompt) || str_word_count($prompt) < 20) {
                    // Auto-generate via Gemini if prompt is empty or too short
                    $this->generateSceneVideoPrompt($sceneId);
                    $visual = $this->sceneVisualScript[$sceneId] ?? [];
                    $prompt = trim($visual['video_action'] ?? '');
                }
                if (empty($prompt)) {
                    // Absolute last resort: use direction_context as-is (still better than buildInteractiveVideoPrompt)
                    $prompt = trim($visual['direction_context'] ?? $visual['image_prompt'] ?? '');
                    Log::warning('[VideoPrompt] Using raw direction_context as fallback', ['scene' => $sceneId]);
                }
            } else {
                $prompt = $this->buildInteractiveVideoPrompt($visual, $sceneIndex);
            }

            $animationService = app(AnimationService::class);
            $result = $animationService->generateAnimation($wizardProject, [
                'imageUrl' => $imageUrl,
                'prompt' => $prompt,
                'duration' => $clipDuration,
                'sceneIndex' => $sceneIndex,
                'resolution' => property_exists($this, 'videoResolution') ? $this->videoResolution : '480p',
                'variant' => property_exists($this, 'videoQuality') ? $this->videoQuality : 'pro',
                'generate_audio' => false,
            ]);

            if (!empty($result['success']) && !empty($result['taskId'])) {
                $this->sceneVideoTaskIds[$sceneId] = $result['taskId'];
                $this->sceneVideoStatus[$sceneId] = 'processing';
            } else {
                $this->sceneVideoStatus[$sceneId] = 'failed';
            }

            $wizardProject->delete();
        } catch (\Exception $e) {
            Log::error('HasImageSelection: AI video generation failed', [
                'scene' => $sceneId, 'error' => $e->getMessage(),
            ]);
            $this->sceneVideoStatus[$sceneId] = 'failed';
            session()->flash('error', 'Video generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Poll all scenes that have video generation in progress.
     * Called via wire:poll from the frontend when any scene is processing.
     */
    public function pollAllVideoStatuses(): void
    {
        $animationService = app(AnimationService::class);
        $hasProcessing = false;

        foreach ($this->sceneVideoTaskIds as $sceneId => $taskId) {
            if (($this->sceneVideoStatus[$sceneId] ?? '') !== 'processing') continue;

            try {
                $status = $animationService->getTaskStatus($taskId);
                $state = $status['status'] ?? 'unknown';

                if ($state === 'completed' && !empty($status['videoUrl'])) {
                    $videoUrl = $status['videoUrl'];
                    $this->sceneVideoStatus[$sceneId] = 'completed';

                    // Track generated video
                    if (!isset($this->sceneGeneratedVideos[$sceneId])) {
                        $this->sceneGeneratedVideos[$sceneId] = [];
                    }
                    $this->sceneGeneratedVideos[$sceneId][] = [
                        'url' => $videoUrl,
                        'timestamp' => now()->toIso8601String(),
                    ];

                    // Add video as selectable candidate
                    $sceneIndex = (int) str_replace('scene_', '', $sceneId);
                    $sceneDuration = $this->generatedSegments[$sceneIndex]['estimated_duration'] ?? 6;

                    $videoCandidate = [
                        'url' => $videoUrl,
                        'thumbnail' => $videoUrl,
                        'source' => 'ai_video',
                        'title' => 'AI Video',
                        'type' => 'video',
                        'duration' => $this->calculateAIClipDuration($sceneDuration),
                        'width' => 0,
                        'height' => 0,
                    ];
                    $this->sceneImageCandidates[$sceneId][] = $videoCandidate;
                    $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
                    $this->selectedSceneImages[$sceneId] = [$newIndex];
                    $this->sceneAnimateWithAI[$sceneId] = false; // We have a real clip now

                    Log::info('HasImageSelection: AI video completed for scene', [
                        'scene' => $sceneId, 'url' => substr($videoUrl, 0, 80),
                    ]);

                    $this->saveDraftState();
                } elseif ($state === 'failed') {
                    $this->sceneVideoStatus[$sceneId] = 'failed';
                    Log::warning('HasImageSelection: AI video failed for scene', ['scene' => $sceneId]);
                } else {
                    $hasProcessing = true;
                }
            } catch (\Exception $e) {
                Log::warning('HasImageSelection: Video poll error', [
                    'scene' => $sceneId, 'error' => $e->getMessage(),
                ]);
                $hasProcessing = true;
            }
        }
    }

    /**
     * Check if any scene has video generation in progress (for polling trigger).
     */
    public function hasProcessingVideos(): bool
    {
        foreach ($this->sceneVideoStatus as $status) {
            if ($status === 'processing' || $status === 'submitting') return true;
        }
        return false;
    }

    /**
     * Build a Seedance 2.0 quality video prompt for interactive mode.
     * Produces flowing prose with style, lighting, color, and audio cues.
     */
    protected function buildInteractiveVideoPrompt(array $visual, int $sceneIndex): string
    {
        $style = self::VISUAL_STYLE_PRESETS[$this->selectedVisualStyle]
            ?? self::VISUAL_STYLE_PRESETS['cinematic'];

        $parts = [];

        // 1. CORE: Rich video action (2-4 sentences from AI)
        $videoAction = trim($visual['video_action'] ?? '');
        if (!empty($videoAction)) {
            $parts[] = rtrim($videoAction, '.');
        }
        if (empty($parts)) {
            // Fallback: extract setting from image prompt
            $imgPrompt = $visual['image_prompt'] ?? '';
            $firstSentence = strtok($imgPrompt, '.');
            if (!empty($firstSentence)) {
                $parts[] = trim($firstSentence);
            }
        }

        if (empty($parts)) {
            return $visual['camera_motion'] ?? 'slow zoom in';
        }

        // 2. CAMERA: Woven naturally into the narrative
        $cameraMotion = $visual['camera_motion'] ?? 'slow zoom in';
        $cameraMap = [
            'slow zoom in'      => 'The camera slowly pushes in closer',
            'slow zoom out'     => 'The camera gradually pulls back to reveal more',
            'dramatic zoom in'  => 'The camera rapidly pushes in',
            'pan left'          => 'The camera pans slowly to the left',
            'pan right'         => 'The camera pans slowly to the right',
            'pan left slow'     => 'The camera drifts gently to the left',
            'pan right slow'    => 'The camera drifts gently to the right',
            'tilt up'           => 'The camera tilts slowly upward',
            'tilt down'         => 'The camera tilts slowly downward',
            'push to subject'   => 'The camera pushes steadily toward the subject',
            'rise and reveal'   => 'The camera rises upward in a crane shot, revealing the scene',
            'settle in'         => 'The camera settles with a subtle, nearly locked-off motion',
            'breathe'           => 'The camera holds with a very subtle breathing motion',
            'zoom in pan right' => 'The camera pushes in while panning right',
            'zoom out pan left' => 'The camera pulls back while panning left',
            'diagonal drift'    => 'The camera drifts diagonally in a floating motion',
        ];
        $parts[] = $cameraMap[strtolower(trim($cameraMotion))] ?? 'The camera slowly pushes in';

        // 3. STYLE: Visual anchor from selected style
        $parts[] = $style['videoAnchor'];

        // 4. LIGHTING: Mood-specific + style-specific
        $mood = strtolower(trim($visual['mood'] ?? ''));
        $moodLightingMap = [
            'calm'         => 'soft natural lighting',
            'dramatic'     => 'high-contrast dramatic lighting',
            'energetic'    => 'bright dynamic lighting',
            'tense'        => 'harsh directional lighting with deep shadows',
            'mysterious'   => 'low-key lighting with atmospheric haze',
            'epic'         => 'golden hour cinematic lighting',
            'playful'      => 'warm cheerful lighting',
            'nostalgic'    => 'warm amber tones with soft diffusion',
            'professional' => 'clean balanced lighting',
            'hopeful'      => 'bright natural light breaking through',
            'horror'       => 'dim flickering light with heavy shadows',
            'intimate'     => 'soft warm close lighting',
        ];
        $moodLighting = $moodLightingMap[$mood] ?? 'clean balanced lighting';
        $lightingText = $moodLighting;
        if (!empty($style['videoLighting']) && $style['videoLighting'] !== $moodLighting) {
            $lightingText = $style['videoLighting'] . ', ' . $moodLighting;
        }
        $parts[] = $lightingText;

        // 5. COLOR TREATMENT from style
        if (!empty($style['videoColor'])) {
            $parts[] = $style['videoColor'];
        }

        // 6. AUDIO: Context-aware from video_action setting description
        $parts[] = $this->extractAudioFromScene($visual);

        // Assemble as flowing prose
        $prompt = implode('. ', array_filter($parts)) . '.';

        // Clean up double periods, extra spaces
        $prompt = preg_replace('/\.\s*\./', '.', $prompt);
        $prompt = preg_replace('/\s{2,}/', ' ', $prompt);

        // Sanitize via SeedancePromptService
        if (class_exists(SeedancePromptService::class)) {
            $prompt = SeedancePromptService::sanitize($prompt);
        }

        return trim($prompt);
    }

    /**
     * Extract context-aware audio direction from scene content.
     */
    protected function extractAudioFromScene(array $visual): string
    {
        $text = strtolower(($visual['video_action'] ?? '') . ' ' . ($visual['image_prompt'] ?? ''));
        $cueMap = [
            'rain' => 'rain and distant thunder', 'storm' => 'thunder and heavy rainfall',
            'ocean' => 'ocean waves', 'forest' => 'birds and rustling leaves',
            'city' => 'distant traffic and urban hum', 'street' => 'footsteps and city noise',
            'office' => 'keyboard clicks and air conditioning', 'kitchen' => 'sizzling',
            'fire' => 'crackling fire', 'night' => 'crickets and nighttime ambiance',
            'snow' => 'crunching snow underfoot', 'water' => 'flowing water',
            'crowd' => 'murmuring crowd', 'piano' => 'piano resonance',
            'server' => 'quiet electronic hum', 'studio' => 'quiet ambient hum',
            'concert' => 'hall reverb', 'library' => 'quiet reverberant space',
            'garden' => 'birds chirping', 'wind' => 'wind and rustling',
        ];
        foreach ($cueMap as $keyword => $sound) {
            if (str_contains($text, $keyword)) {
                return "Only {$sound}";
            }
        }
        return 'Ambient sound only';
    }

    /**
     * Calculate clip duration snapped to Seedance-supported values.
     */
    protected function calculateAIClipDuration(float $audioDuration): int
    {
        $withPadding = $audioDuration + 2.0;
        $clamped = min(10, max(5, (int) ceil($withPadding)));
        $supported = [5, 6, 8, 10];
        foreach ($supported as $dur) {
            if ($dur >= $clamped) return $dur;
        }
        return 10;
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Long Scene Auto-Splitting (Phase 4)
    // ────────────────────────────────────────────────────────────────────

    /**
     * Auto-split scenes exceeding 10s into sub-scenes at sentence boundaries.
     * Called at the start of generateVisualScript().
     */
    protected function splitLongScenes(): void
    {
        $segments = $this->generatedSegments ?? [];
        if (empty($segments)) return;

        $newSegments = [];
        $didSplit = false;

        foreach ($segments as $i => $segment) {
            $duration = (float) ($segment['estimated_duration'] ?? 6);

            if ($duration <= 10.0) {
                $newSegments[] = $segment;
                continue;
            }

            // Split at nearest sentence boundary to midpoint
            $text = $segment['text'] ?? '';
            $midpoint = mb_strlen($text) / 2;

            // Find sentence breaks (. ! ?)
            preg_match_all('/[.!?]\s+/', $text, $matches, PREG_OFFSET_CAPTURE);
            $breaks = $matches[0] ?? [];

            $bestBreak = null;
            $bestDistance = PHP_INT_MAX;
            foreach ($breaks as $match) {
                $pos = $match[1] + mb_strlen($match[0]);
                $distance = abs($pos - $midpoint);
                if ($distance < $bestDistance && $pos > 10 && $pos < mb_strlen($text) - 10) {
                    $bestBreak = $pos;
                    $bestDistance = $distance;
                }
            }

            if ($bestBreak === null) {
                // No good sentence boundary, keep as-is
                $newSegments[] = $segment;
                continue;
            }

            $part1Text = trim(mb_substr($text, 0, $bestBreak));
            $part2Text = trim(mb_substr($text, $bestBreak));

            if (empty($part1Text) || empty($part2Text)) {
                $newSegments[] = $segment;
                continue;
            }

            // Proportional durations
            $ratio = mb_strlen($part1Text) / mb_strlen($text);
            $dur1 = round($duration * $ratio, 1);
            $dur2 = round($duration * (1 - $ratio), 1);

            $newSegments[] = array_merge($segment, [
                'text' => $part1Text,
                'estimated_duration' => $dur1,
                'split_from' => $i,
                'split_part' => 1,
            ]);
            $newSegments[] = array_merge($segment, [
                'text' => $part2Text,
                'estimated_duration' => $dur2,
                'split_from' => $i,
                'split_part' => 2,
            ]);
            $didSplit = true;

            Log::info('HasImageSelection: Split long scene', [
                'original_index' => $i,
                'original_duration' => $duration,
                'part1_duration' => $dur1,
                'part2_duration' => $dur2,
            ]);
        }

        if ($didSplit) {
            $this->generatedSegments = $newSegments;

            // Re-source stock candidates for the new scene count
            $imageService = new ImageSourceService();
            $scenes = [];
            foreach ($newSegments as $i => $seg) {
                $scenes[] = [
                    'id' => 'scene_' . $i,
                    'index' => $i,
                    'text' => $seg['text'],
                    'estimated_duration' => $seg['estimated_duration'],
                ];
            }

            $brief = property_exists($this, 'storedContentBrief') ? ($this->storedContentBrief ?? []) : [];
            if (empty($brief['subject'])) {
                $brief['subject'] = $this->prompt ?? '';
            }
            $extracted = property_exists($this, 'storedExtractedContent') ? ($this->storedExtractedContent ?? []) : [];

            $result = $imageService->sourceForScenes($scenes, $extracted, $brief);

            // Rebuild candidates
            $this->sceneImageCandidates = [];
            $this->sceneSearchSuggestions = [];
            foreach ($result as $sceneId => $data) {
                $this->sceneImageCandidates[$sceneId] = $data['candidates'] ?? [];
                $this->sceneSearchSuggestions[$sceneId] = $data['suggestions'] ?? [];
            }

            // Set all new scenes to AI
            $this->selectedSceneImages = [];
            foreach ($this->sceneImageCandidates as $sceneId => $candidates) {
                $this->selectedSceneImages[$sceneId] = 'ai';
                $this->sceneAnimateWithAI[$sceneId] = true;
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // AI Studio: Draft Auto-Save & Resume
    // ────────────────────────────────────────────────────────────────────

    /**
     * Save the current AI Studio state as a draft project.
     * Creates a new draft or updates an existing one.
     */
    public function saveDraftState(): void
    {
        try {
            // Only save if we have meaningful state (visual script or generated images)
            if (empty($this->sceneVisualScript) && empty($this->sceneGeneratedImages)) {
                return;
            }

            $studioState = [
                'sceneVisualScript' => $this->sceneVisualScript,
                'characterBible' => $this->characterBible,
                'sceneGeneratedImages' => $this->sceneGeneratedImages,
                'sceneGeneratedVideos' => $this->sceneGeneratedVideos,
                'selectedSceneImages' => $this->selectedSceneImages,
                'sceneImageCandidates' => $this->sceneImageCandidates,
                'selectedVisualStyle' => $this->selectedVisualStyle,
                'sceneAnimateWithAI' => $this->sceneAnimateWithAI,
                'activeStudioScene' => $this->activeStudioScene,
                'sceneVideoStatus' => $this->sceneVideoStatus,
                'sceneVideoTaskIds' => $this->sceneVideoTaskIds,
            ];

            $draftData = [
                'user_id' => auth()->id(),
                'team_id' => session('current_team_id'),
                'title' => (property_exists($this, 'generatedTitle') ? $this->generatedTitle : null) ?: 'Untitled Draft',
                'prompt' => property_exists($this, 'prompt') ? ($this->prompt ?: null) : null,
                'source_url' => property_exists($this, 'sourceUrl') ? ($this->sourceUrl ?: '') : '',
                'source_type' => property_exists($this, 'detectedSourceType') ? ($this->detectedSourceType ?: 'prompt') : 'prompt',
                'transcript' => property_exists($this, 'editableTranscript') ? $this->editableTranscript : null,
                'transcript_word_count' => property_exists($this, 'transcriptWordCount') ? $this->transcriptWordCount : 0,
                'aspect_ratio' => property_exists($this, 'aspectRatio') ? ($this->aspectRatio ?? '9:16') : '9:16',
                'voice_id' => property_exists($this, 'selectedVoice') ? ($this->selectedVoice !== 'auto' ? $this->selectedVoice : null) : null,
                'voice_provider' => property_exists($this, 'voiceProvider') ? ($this->voiceProvider ?: null) : null,
                'visual_script' => !empty($this->sceneVisualScript) ? array_values($this->sceneVisualScript) : null,
                'status' => 'draft',
                'progress_percent' => 0,
                'metadata' => [
                    'video_resolution' => property_exists($this, 'videoResolution') ? $this->videoResolution : '480p',
                    'video_quality' => property_exists($this, 'videoQuality') ? $this->videoQuality : 'pro',
                    'video_duration_target' => property_exists($this, 'videoDuration') ? $this->videoDuration : 60,
                    'narrative_style' => property_exists($this, 'narrativeStyle') ? $this->narrativeStyle : 'hook_reveal',
                    'creative_mode' => property_exists($this, 'creativeMode') ? $this->creativeMode : false,
                    'creative_concept_title' => property_exists($this, 'creativeConceptTitle') ? $this->creativeConceptTitle : null,
                    'creative_concept_pitch' => property_exists($this, 'creativeConceptPitch') ? $this->creativeConceptPitch : null,
                    'studio_state' => $studioState,
                ],
                'scenes' => null,
                'extracted_content' => property_exists($this, 'storedExtractedContent') ? ($this->storedExtractedContent ?: null) : null,
                'content_brief' => property_exists($this, 'storedContentBrief') ? ($this->storedContentBrief ?: null) : null,
            ];

            $draftProjectId = property_exists($this, 'draftProjectId') ? $this->draftProjectId : null;

            if ($draftProjectId) {
                // Update existing draft
                UrlToVideoProject::where('id', $draftProjectId)
                    ->where('status', 'draft')
                    ->update($draftData);
            } else {
                // Create new draft
                $draft = UrlToVideoProject::create($draftData);
                if (property_exists($this, 'draftProjectId')) {
                    $this->draftProjectId = $draft->id;
                }
            }

            Cache::forget('url-to-video-projects-' . auth()->id());

            Log::info('HasImageSelection: Draft saved', [
                'draft_id' => property_exists($this, 'draftProjectId') ? $this->draftProjectId : 'unknown',
                'images' => count($this->sceneGeneratedImages),
                'videos' => count($this->sceneGeneratedVideos),
            ]);
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: Draft save failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Resume an AI Studio session from a saved draft.
     */
    public function resumeDraft(int $projectId): void
    {
        $draft = UrlToVideoProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->first();

        if (!$draft) {
            session()->flash('error', 'Draft not found or already used.');
            return;
        }

        // Restore basic fields
        if (property_exists($this, 'prompt')) $this->prompt = $draft->prompt ?? '';
        if (property_exists($this, 'sourceUrl')) $this->sourceUrl = $draft->source_url ?? '';
        if (property_exists($this, 'detectedSourceType')) $this->detectedSourceType = $draft->source_type ?? 'prompt';
        if (property_exists($this, 'editableTranscript')) $this->editableTranscript = $draft->transcript;
        if (property_exists($this, 'transcriptWordCount')) $this->transcriptWordCount = $draft->transcript_word_count ?? 0;
        if (property_exists($this, 'generatedTitle')) $this->generatedTitle = $draft->title;
        if (property_exists($this, 'aspectRatio')) $this->aspectRatio = $draft->aspect_ratio ?? '9:16';
        if (property_exists($this, 'selectedVoice')) $this->selectedVoice = $draft->voice_id ?? 'auto';
        if (property_exists($this, 'voiceProvider')) $this->voiceProvider = $draft->voice_provider ?? '';
        if (property_exists($this, 'storedExtractedContent')) $this->storedExtractedContent = $draft->extracted_content ?? [];
        if (property_exists($this, 'storedContentBrief')) $this->storedContentBrief = $draft->content_brief ?? [];

        // Restore metadata config
        $meta = $draft->metadata ?? [];
        if (property_exists($this, 'videoResolution')) $this->videoResolution = $meta['video_resolution'] ?? '480p';
        if (property_exists($this, 'videoQuality')) $this->videoQuality = $meta['video_quality'] ?? 'pro';
        if (property_exists($this, 'videoDuration')) $this->videoDuration = $meta['video_duration_target'] ?? 60;
        if (property_exists($this, 'narrativeStyle')) $this->narrativeStyle = $meta['narrative_style'] ?? 'hook_reveal';
        if (property_exists($this, 'creativeMode')) $this->creativeMode = $meta['creative_mode'] ?? false;
        if (property_exists($this, 'creativeConceptTitle')) $this->creativeConceptTitle = $meta['creative_concept_title'] ?? null;
        if (property_exists($this, 'creativeConceptPitch')) $this->creativeConceptPitch = $meta['creative_concept_pitch'] ?? null;

        // Restore AI Studio state
        $studio = $meta['studio_state'] ?? [];
        if (!empty($studio)) {
            $this->sceneVisualScript = $studio['sceneVisualScript'] ?? [];
            $this->characterBible = $studio['characterBible'] ?? null;
            $this->sceneGeneratedImages = $studio['sceneGeneratedImages'] ?? [];
            $this->sceneGeneratedVideos = $studio['sceneGeneratedVideos'] ?? [];
            $this->selectedSceneImages = $studio['selectedSceneImages'] ?? [];
            $this->sceneImageCandidates = $studio['sceneImageCandidates'] ?? [];
            $this->selectedVisualStyle = $studio['selectedVisualStyle'] ?? 'cinematic';
            $this->sceneAnimateWithAI = $studio['sceneAnimateWithAI'] ?? [];
            $this->activeStudioScene = $studio['activeStudioScene'] ?? '';
            $this->sceneVideoTaskIds = $studio['sceneVideoTaskIds'] ?? [];

            // Reset stale video statuses (processing/submitting → idle)
            $this->sceneVideoStatus = [];
            foreach (($studio['sceneVideoStatus'] ?? []) as $sceneId => $status) {
                if ($status === 'processing' || $status === 'submitting') {
                    $this->sceneVideoStatus[$sceneId] = 'idle';
                } else {
                    $this->sceneVideoStatus[$sceneId] = $status;
                }
            }
        }

        // Re-segment transcript to restore generatedSegments
        if (!empty($this->editableTranscript ?? null)) {
            $scriptService = new StoryModeScriptService();
            $targetDuration = property_exists($this, 'videoDuration') ? $this->videoDuration : 60;
            if (property_exists($this, 'generatedSegments')) {
                $this->generatedSegments = $scriptService->segmentTranscript($this->editableTranscript, $targetDuration);
            }
        }

        // Track draft ID and open the modal
        if (property_exists($this, 'draftProjectId')) $this->draftProjectId = $draft->id;
        if (property_exists($this, 'detailProjectId')) $this->detailProjectId = null;

        // Open AI Studio if visual script exists, otherwise show transcript editor
        if (!empty($this->sceneVisualScript)) {
            $this->showTranscriptModal = false;
            $this->showImageSelectionModal = true;
        } else {
            $this->showTranscriptModal = true;
            $this->showImageSelectionModal = false;
        }

        Log::info('HasImageSelection: Draft resumed', [
            'draft_id' => $draft->id,
            'scenes' => count($this->sceneVisualScript),
            'images' => count($this->sceneGeneratedImages),
        ]);
    }

    /**
     * Open the full library browser for a scene.
     */
    public function openLibraryBrowser(string $sceneId)
    {
        $this->libraryBrowseScene = $sceneId;
        $this->libraryActiveCategory = '';
        $this->libraryCategoryResults = [];
        $this->libraryPage = 1;
        $this->libraryHasMore = false;
        $this->librarySearchQuery = '';
        $this->librarySort = 'title';
        $this->libraryTypeFilter = '';

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
        $this->librarySearchQuery = '';
        $this->libraryPage = 1;
        $perPage = 24;
        $type = in_array($this->libraryTypeFilter, ['image', 'video']) ? $this->libraryTypeFilter : null;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $results = $stockService->browseCategory($category, $perPage + 1, 0, $type, $this->librarySort);
        $this->libraryHasMore = count($results) > $perPage;
        $this->libraryCategoryResults = array_slice($results, 0, $perPage);
    }

    /**
     * Search the library browser by text query.
     */
    public function searchLibrary(string $query)
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) return;

        $this->libraryActiveCategory = '';
        $this->librarySearchQuery = $query;
        $this->libraryPage = 1;
        $perPage = 24;
        $type = in_array($this->libraryTypeFilter, ['image', 'video']) ? $this->libraryTypeFilter : null;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $results = $stockService->search($query, $perPage + 1, 0, $type);
        $this->libraryHasMore = count($results) > $perPage;
        $this->libraryCategoryResults = array_slice($results, 0, $perPage);
    }

    /**
     * Load more items in the library browser (next page).
     */
    public function loadMoreLibrary()
    {
        if (!$this->libraryHasMore) return;

        $this->libraryPage++;
        $perPage = 24;
        $offset = ($this->libraryPage - 1) * $perPage;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $type = in_array($this->libraryTypeFilter, ['image', 'video']) ? $this->libraryTypeFilter : null;

        if (!empty($this->librarySearchQuery)) {
            $results = $stockService->search($this->librarySearchQuery, $perPage + 1, $offset, $type);
        } elseif (!empty($this->libraryActiveCategory)) {
            $results = $stockService->browseCategory($this->libraryActiveCategory, $perPage + 1, $offset, $type, $this->librarySort);
        } else {
            return;
        }

        $this->libraryHasMore = count($results) > $perPage;
        $newItems = array_slice($results, 0, $perPage);
        $this->libraryCategoryResults = array_merge($this->libraryCategoryResults, $newItems);
    }

    /**
     * Change sort order and re-fetch library results.
     */
    public function updateLibrarySort(string $sort)
    {
        if (!in_array($sort, ['title', 'shortest', 'longest', 'newest'])) return;
        $this->librarySort = $sort;
        $this->reloadLibraryResults();
    }

    /**
     * Change type filter and re-fetch library results.
     */
    public function updateLibraryTypeFilter(string $type)
    {
        $this->libraryTypeFilter = $type === $this->libraryTypeFilter ? '' : $type;
        $this->reloadLibraryResults();
    }

    /**
     * Re-fetch library results with current sort/filter settings.
     */
    protected function reloadLibraryResults(): void
    {
        $this->libraryPage = 1;
        $perPage = 24;
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $type = in_array($this->libraryTypeFilter, ['image', 'video']) ? $this->libraryTypeFilter : null;

        if (!empty($this->librarySearchQuery)) {
            $results = $stockService->search($this->librarySearchQuery, $perPage + 1, 0, $type);
        } elseif (!empty($this->libraryActiveCategory)) {
            $results = $stockService->browseCategory($this->libraryActiveCategory, $perPage + 1, 0, $type, $this->librarySort);
        } else {
            return;
        }

        $this->libraryHasMore = count($results) > $perPage;
        $this->libraryCategoryResults = array_slice($results, 0, $perPage);
    }

    /**
     * Select a clip from the library browser and add it to the scene.
     */
    public function selectFromLibrary(int $index)
    {
        $sceneId = $this->libraryBrowseScene;
        if (empty($sceneId) || !isset($this->libraryCategoryResults[$index])) return;

        $candidate = $this->libraryCategoryResults[$index];
        $this->sceneImageCandidates[$sceneId][] = $candidate;
        $newIndex = count($this->sceneImageCandidates[$sceneId]) - 1;
        $this->selectedSceneImages[$sceneId] = [$newIndex];

        if (($candidate['type'] ?? 'image') === 'video') {
            $this->sceneAnimateWithAI[$sceneId] = false;
            $this->autoTrimVideoClip($sceneId, $candidate);
        }

        $this->showLibraryBrowser = false;
    }

    /**
     * Execute a scene search with media type filtering.
     */
    public function executeSceneSearch(string $sceneId, string $query = '', string $type = '')
    {
        $query = trim($query ?: $this->searchQuery);
        $type = $type ?: $this->searchType;

        if (mb_strlen($query) < 2) return;

        Log::info('HasImageSelection: executeSceneSearch', [
            'scene' => $sceneId, 'query' => $query, 'type' => $type,
        ]);

        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $added = 0;

        try {
            $stockType = ($type === 'videos') ? 'video' : (($type === 'images') ? 'image' : null);
            $stockResults = $stockService->search($query, 12, $stockType);

            $uploads = array_filter($this->sceneImageCandidates[$sceneId] ?? [], function ($c) {
                return ($c['source'] ?? '') === 'upload';
            });
            $this->sceneImageCandidates[$sceneId] = array_values($uploads);

            foreach ($stockResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: Scene search failed', [
                'scene' => $sceneId, 'query' => $query, 'error' => $e->getMessage(),
            ]);
            session()->flash('searchError', 'Search failed: ' . $e->getMessage());
        }

        if ($added > 0) {
            session()->flash('searchSuccess', "Found {$added} results for \"{$query}\"");
        } else {
            session()->flash('searchError', "No results found for \"{$query}\"");
        }

        $this->searchQuery = '';
    }

    /**
     * Load more stock candidates for a scene.
     */
    public function loadMoreCandidates(string $sceneId)
    {
        $stockService = new \Modules\AppVideoWizard\Services\ArtimeStockService();
        $existingIds = array_filter(array_column($this->sceneImageCandidates[$sceneId] ?? [], 'stock_id'));

        // Use scene narration text for search instead of global subject
        $sceneText = $this->sceneSearchSuggestions[$sceneId]['scene_text'] ?? '';
        $subject = (property_exists($this, 'storedContentBrief') ? ($this->storedContentBrief['subject'] ?? null) : null)
            ?? $this->prompt ?? '';

        // Try scene-specific category first, fall back to subject-based
        $searchTerm = !empty($sceneText) ? $sceneText : $subject;
        $matchedCategory = !empty($searchTerm) ? $stockService->findMatchingCategory($searchTerm) : null;
        $added = 0;

        try {
            if ($matchedCategory) {
                $results = $stockService->browseCategoryExcluding($matchedCategory, 8, $existingIds);
            } else {
                $results = $stockService->searchExcluding($searchTerm, 8, $existingIds);
            }

            foreach ($results as $item) {
                $this->sceneImageCandidates[$sceneId][] = $item;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: loadMoreCandidates failed', [
                'scene' => $sceneId, 'error' => $e->getMessage(),
            ]);
        }

        if ($added === 0) {
            session()->flash('searchError', 'No more clips available');
        }
    }

    /**
     * Search external stock sources (Pexels, Pixabay, Wikimedia) for a scene.
     */
    public function searchExternalStock(string $sceneId, string $query = '')
    {
        $query = trim($query ?: $this->searchQuery);
        if (mb_strlen($query) < 2) return;

        Log::info('HasImageSelection: searchExternalStock', [
            'scene' => $sceneId, 'query' => $query,
        ]);

        $imageService = new ImageSourceService();
        $added = 0;

        try {
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

            $videoResults = $imageService->searchVideoClips($query, 5);
            foreach ($videoResults as $r) {
                $this->sceneImageCandidates[$sceneId][] = $r;
                $added++;
            }
        } catch (\Exception $e) {
            Log::warning('HasImageSelection: External stock search failed', [
                'scene' => $sceneId, 'query' => $query, 'error' => $e->getMessage(),
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
        if (empty($sceneId) || !$this->uploadedSceneImage) return;

        try {
            $path = $this->uploadedSceneImage->store('url-to-video/uploads', 'public');
            $publicUrl = url('/public/storage/' . $path);

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
            Log::warning('HasImageSelection: Image upload failed', ['error' => $e->getMessage()]);
        }
    }
}
