<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\StoryModeProject;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\SmartReferenceService;
use Modules\AppVideoWizard\Services\ReferenceImageStorageService;

/**
 * StoryModeOrchestrator
 *
 * Single entry point for the Story Mode pipeline.
 * Orchestrates the full flow: Script → Voiceover → Visual Script → Images → Video → Assembly
 * Delegates to existing AppVideoWizard services.
 */
class StoryModeOrchestrator
{
    protected StoryModeScriptService $scriptService;
    protected VoiceoverService $voiceoverService;
    protected ImageGenerationService $imageService;
    protected AnimationService $animationService;
    protected VideoRenderService $renderService;

    public function __construct()
    {
        $this->scriptService = new StoryModeScriptService();
        $this->voiceoverService = app(VoiceoverService::class);
        $this->imageService = app(ImageGenerationService::class);
        $this->animationService = app(AnimationService::class);
        $this->renderService = app(VideoRenderService::class);
    }

    /**
     * Run the full generation pipeline for a Story Mode project.
     * Each step updates the project's status, progress, and current_stage.
     *
     * @param StoryModeProject $project The project to generate
     */
    public function generate(StoryModeProject $project): void
    {
        Log::info('StoryModeOrchestrator: Starting pipeline', ['project_id' => $project->id]);

        try {
            // Step 1: Build Visual Script FIRST (15-35%) — generates mood/emotion metadata
            $this->stepBuildVisualScript($project);

            // Step 2: Generate Voiceover WITH emotion data (35-50%)
            $this->stepGenerateVoiceover($project);

            // Step 3: Generate Images (50-70%)
            $this->stepGenerateImages($project);

            // Step 4: Generate Video Clips (70-85%)
            if (get_option('story_mode_frame_chaining', 0)) {
                $this->stepGenerateVideoClipsSequential($project);
            } else {
                $this->stepGenerateVideoClips($project);
            }

            // Step 5: Assemble Final Video (85-100%)
            $this->stepAssembleFinalVideo($project);

            Log::info('StoryModeOrchestrator: Pipeline completed', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('StoryModeOrchestrator: Pipeline failed', [
                'project_id' => $project->id,
                'stage' => $project->current_stage,
                'error' => $e->getMessage(),
            ]);
            $project->markFailed($e->getMessage());
        }
    }

    /**
     * Step 2: Generate voiceover audio for each transcript segment.
     * Now runs AFTER visual script so voice_emotion metadata is available.
     */
    protected function stepGenerateVoiceover(StoryModeProject $project): void
    {
        $project->updateProgress('generating_voiceover', 35, 'Generating voiceover');

        $scenes = $project->scenes ?? [];
        if (empty($scenes)) {
            throw new \Exception('No scenes data available for voiceover generation');
        }

        if ($project->voice_id) {
            // User explicitly chose a voice — respect their choice
            $voiceId = $project->voice_id;
            $voiceProvider = $project->voice_provider ?: null;
        } else {
            // Auto mode: intelligently select based on story mood + content
            $smartVoice = $this->selectSmartVoice($scenes);
            $voiceId = $smartVoice['voice_id'];
            $voiceProvider = $smartVoice['provider'];

            Log::info('StoryModeOrchestrator: Smart voice selected', [
                'project_id' => $project->id,
                'voice_id' => $voiceId,
                'provider' => $voiceProvider,
            ]);
        }

        // Create a temporary WizardProject to use with existing VoiceoverService
        $wizardProject = $this->createTempWizardProject($project);

        $projectDir = $this->ensureProjectDir($project->id, 'audio');
        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            $progress = 35 + (int) (($i / count($scenes)) * 15);
            $project->updateProgress('generating_voiceover', $progress, "Generating voiceover ({$i}/{" . count($scenes) . "})");

            try {
                $sceneData = [
                    'id' => $scene['id'] ?? "scene_{$i}",
                    'narration' => $scene['text'] ?? '',
                ];

                $result = $this->voiceoverService->generateSceneVoiceover($wizardProject, $sceneData, [
                    'voice' => $voiceId,
                    'provider' => $voiceProvider,
                    'sceneIndex' => $i,
                    'emotion' => $scene['voice_emotion'] ?? null,
                ]);

                $scene['audio_url'] = $result['audioUrl'] ?? $result['audio_url'] ?? null;
                $scene['audio_duration'] = $result['duration'] ?? $scene['estimated_duration'] ?? 6;
            } catch (\Exception $e) {
                Log::warning("StoryModeOrchestrator: Voiceover failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
                $scene['audio_url'] = null;
                $scene['audio_duration'] = $scene['estimated_duration'] ?? 6;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);

        // Clean up temp wizard project
        $wizardProject->delete();
    }

    /**
     * Step 1: Build visual script (image prompts + creative metadata) from transcript segments.
     * Runs FIRST so mood/emotion data is available for voiceover generation.
     * Also extracts a Character Bible for visual consistency across scenes.
     */
    protected function stepBuildVisualScript(StoryModeProject $project): void
    {
        $project->updateProgress('generating_visual_script', 15, 'Creating visual script');

        $scenes = $project->scenes ?? [];
        $styleInstruction = $project->getEffectiveStyleInstruction();
        $aspectRatio = $project->aspect_ratio ?? '9:16';

        // Convert scenes to segments format for script service
        $segments = array_map(fn ($s) => [
            'text' => $s['text'] ?? '',
            'estimated_duration' => $s['audio_duration'] ?? $s['estimated_duration'] ?? 6,
        ], $scenes);

        $visualScript = $this->scriptService->buildVisualScript($segments, $styleInstruction, $aspectRatio, $project->team_id);
        $characterBible = $this->scriptService->lastCharacterBible;

        // Merge visual script data back into scenes (includes mood, voice_emotion, transitions)
        $updatedScenes = [];
        foreach ($scenes as $i => $scene) {
            $visual = $visualScript[$i] ?? [];
            $scene['image_prompt'] = $visual['image_prompt'] ?? "A cinematic scene: {$scene['text']}";
            $scene['video_action'] = $visual['video_action'] ?? '';
            $scene['characters_in_scene'] = $visual['characters_in_scene'] ?? [];
            $scene['camera_motion'] = $visual['camera_motion'] ?? 'slow zoom in';
            $scene['mood'] = $visual['mood'] ?? 'professional';
            $scene['voice_emotion'] = $visual['voice_emotion'] ?? 'neutral';
            $scene['transition_type'] = $visual['transition_type'] ?? 'fade';
            $scene['transition_duration'] = (float) ($visual['transition_duration'] ?? 0.5);
            $updatedScenes[] = $scene;
        }

        $project->update([
            'scenes' => $updatedScenes,
            'visual_script' => $visualScript,
            'metadata' => array_merge($project->metadata ?? [], [
                'character_bible' => $characterBible,
                'style_instruction' => $styleInstruction,
            ]),
        ]);

        Log::info('StoryModeOrchestrator: Visual script built', [
            'project_id' => $project->id,
            'scenes' => count($updatedScenes),
            'character_bible_count' => count($characterBible),
        ]);
    }

    /**
     * Step 3: Generate images for each scene.
     *
     * Scene 0 is generated as text-to-image. After Scene 0 succeeds, character
     * references are extracted from it using SmartReferenceService. Scenes 1-N
     * then use the Reference Cascade (image-to-image with character face refs)
     * so characters maintain visual identity across the entire video.
     */
    protected function stepGenerateImages(StoryModeProject $project): void
    {
        $project->updateProgress('generating_images', 50, 'Generating images');

        $scenes = $project->scenes ?? [];
        $imageModel = get_option('story_mode_image_model', 'nanobanana2');
        $wizardProject = $this->createTempWizardProject($project);
        $characterBible = $project->metadata['character_bible'] ?? [];
        $characterRefs = []; // charId => { name, description, appears_in, storageKey, mimeType }

        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            $progress = 50 + (int) (($i / count($scenes)) * 20);
            $project->updateProgress('generating_images', $progress, "Generating image (" . ($i + 1) . "/" . count($scenes) . ")");

            // Skip AI generation if scene already has a pre-assigned image
            if (!empty($scene['image_url'])) {
                $updatedScenes[] = $scene;
                continue;
            }

            try {
                $sceneData = [
                    'id' => $scene['id'] ?? "scene_{$i}",
                    'visualDescription' => $scene['image_prompt'] ?? '',
                    'narration' => $scene['text'] ?? '',
                ];

                $result = $this->imageService->generateSceneImage($wizardProject, $sceneData, [
                    'model' => $imageModel,
                    'sceneIndex' => $i,
                ]);

                $scene['image_url'] = $result['imageUrl'] ?? $result['image_url'] ?? null;

                // After Scene 0 succeeds: extract character references for subsequent scenes
                if ($i === 0 && !empty($scene['image_url']) && !empty($characterBible)) {
                    $project->updateProgress('generating_images', 53, 'Analyzing Scene 1 for character references');

                    try {
                        $characterRefs = $this->extractCharacterReferences(
                            $scene['image_url'],
                            $characterBible,
                            $wizardProject->id,
                            $project
                        );

                        if (!empty($characterRefs)) {
                            // Populate wizardProject with sceneMemory so Reference Cascade triggers
                            $this->populateSceneMemory($wizardProject, $characterRefs, $characterBible);

                            Log::info('StoryModeOrchestrator: Character references extracted from Scene 0', [
                                'project_id' => $project->id,
                                'characters' => array_keys($characterRefs),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('StoryModeOrchestrator: Character reference extraction failed, continuing without references', [
                            'error' => $e->getMessage(),
                        ]);
                        // Graceful degradation: scenes 1-N will generate as text-to-image
                    }
                }
            } catch (\Exception $e) {
                Log::warning("StoryModeOrchestrator: Image generation failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
                $scene['image_url'] = null;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);
        $wizardProject->delete();
    }

    /**
     * Extract character references from Scene 0's generated image.
     *
     * Downloads the image, uses SmartReferenceService to detect characters,
     * extracts individual portraits, and stores them via ReferenceImageStorageService.
     *
     * @return array Map of charId => { name, description, appears_in, storageKey, mimeType }
     */
    protected function extractCharacterReferences(
        string $imageUrl,
        array $characterBible,
        int $wizardProjectId,
        StoryModeProject $project
    ): array {
        // Download Scene 0 image to base64
        $response = Http::timeout(30)->get($imageUrl);
        if (!$response->successful()) {
            Log::warning('StoryModeOrchestrator: Failed to download Scene 0 image for character analysis');
            return [];
        }

        $imageBase64 = base64_encode($response->body());
        $mimeType = $response->header('Content-Type') ?: 'image/png';

        // Build bible format expected by SmartReferenceService
        $bibleForAnalysis = [
            'characters' => array_map(fn($char) => [
                'name' => $char['name'] ?? '',
                'description' => $char['description'] ?? '',
            ], $characterBible),
        ];

        $smartRefService = app(SmartReferenceService::class);
        $storageService = app(ReferenceImageStorageService::class);

        // Analyze the scene for characters
        $analysis = $smartRefService->analyzeSceneForCharacters($imageBase64, $bibleForAnalysis);

        if (empty($analysis['success']) || empty($analysis['detectedCharacters'])) {
            Log::info('StoryModeOrchestrator: No characters detected in Scene 0 (may be landscape/abstract content)');
            return [];
        }

        $characterRefs = [];
        $extractionCount = 0;
        $maxExtractions = 3; // Limit cost

        foreach ($analysis['detectedCharacters'] as $detected) {
            if ($extractionCount >= $maxExtractions) {
                break;
            }

            $confidence = $detected['confidence'] ?? 0;
            if ($confidence < 0.7) {
                continue;
            }

            $bibleIndex = $detected['bibleIndex'] ?? null;
            if ($bibleIndex === null || !isset($characterBible[$bibleIndex])) {
                continue;
            }

            $charData = $characterBible[$bibleIndex];
            $charId = $charData['id'] ?? "char_{$bibleIndex}";

            try {
                // Extract portrait for this character
                $portrait = $smartRefService->extractCharacterPortrait($imageBase64, $detected, $charData);

                if (empty($portrait['success']) || empty($portrait['base64'])) {
                    Log::warning("StoryModeOrchestrator: Portrait extraction failed for {$charId}");
                    continue;
                }

                // Store the portrait via ReferenceImageStorageService
                $storageKey = $storageService->storeBase64(
                    $wizardProjectId,
                    'character',
                    $extractionCount,
                    $portrait['base64'],
                    $portrait['mimeType'] ?? 'image/png'
                );

                $characterRefs[$charId] = [
                    'name' => $charData['name'] ?? $detected['name'] ?? 'Unknown',
                    'description' => $charData['description'] ?? '',
                    'appears_in' => $charData['appears_in'] ?? [],
                    'storageKey' => $storageKey,
                    'mimeType' => $portrait['mimeType'] ?? 'image/png',
                    'base64' => $portrait['base64'],
                ];

                $extractionCount++;

                Log::info("StoryModeOrchestrator: Portrait extracted for {$charId}", [
                    'storageKey' => $storageKey,
                    'confidence' => $confidence,
                ]);
            } catch (\Exception $e) {
                Log::warning("StoryModeOrchestrator: Portrait extraction failed for {$charId}", [
                    'error' => $e->getMessage(),
                ]);
                // Skip this character, continue with others
            }
        }

        return $characterRefs;
    }

    /**
     * Populate the WizardProject's content_config with sceneMemory.characterBible
     * so that ImageGenerationService's Reference Cascade triggers for scenes 1-N.
     */
    protected function populateSceneMemory(WizardProject $wizardProject, array $characterRefs, array $characterBible): void
    {
        $characters = [];

        foreach ($characterRefs as $charId => $ref) {
            // Find the original bible entry for full data
            $bibleEntry = null;
            foreach ($characterBible as $entry) {
                if (($entry['id'] ?? '') === $charId) {
                    $bibleEntry = $entry;
                    break;
                }
            }

            // Map appears_in (1-based) to 0-based scene indices
            $appearsIn = $ref['appears_in'] ?? [];
            $sceneIndices = array_map(fn($s) => $s - 1, $appearsIn);

            $characters[] = [
                'name' => $ref['name'],
                'description' => $ref['description'],
                'scenes' => $sceneIndices, // Empty = all scenes
                'referenceImageStorageKey' => $ref['storageKey'],
                'referenceImageStatus' => 'ready',
                'referenceImageMimeType' => $ref['mimeType'],
                'role' => 'Protagonist',
                'hair' => [],
                'wardrobe' => [],
                'makeup' => [],
                'accessories' => [],
                'traits' => [],
                'defaultExpression' => 'neutral',
            ];
        }

        $contentConfig = $wizardProject->content_config ?? [];
        $contentConfig['sceneMemory'] = [
            'characterBible' => [
                'enabled' => true,
                'characters' => $characters,
            ],
        ];

        $wizardProject->update(['content_config' => $contentConfig]);

        Log::info('StoryModeOrchestrator: sceneMemory populated with character references', [
            'wizardProjectId' => $wizardProject->id,
            'characterCount' => count($characters),
        ]);
    }

    /**
     * Build a narrative Seedance prompt for a Story Mode scene.
     * Combines video_action, camera motion, style, mood lighting, and audio direction
     * into a flowing prompt following the Subject+Action+Camera+Style format.
     */
    protected function buildStoryVideoPrompt(array $scene, string $styleInstruction, string $aspectRatio): string
    {
        try {
            $parts = [];

            // 1. Subject + Action (from video_action, with fallbacks)
            $videoAction = trim($scene['video_action'] ?? '');
            if (!empty($videoAction)) {
                $parts[] = $videoAction;
            } else {
                // Fallback: extract action from narration text
                $narration = trim($scene['text'] ?? '');
                if (!empty($narration)) {
                    // Use first sentence of narration as a loose action hint
                    $firstSentence = strtok($narration, '.!?');
                    if (!empty($firstSentence)) {
                        $parts[] = trim($firstSentence);
                    }
                }
            }

            // If still empty, fall back to camera_motion only (current behavior safety net)
            if (empty($parts)) {
                return $scene['camera_motion'] ?? 'slow zoom in';
            }

            // 2. Camera (map story mode slugs to Seedance camera language)
            $cameraMotion = $scene['camera_motion'] ?? 'slow zoom in';
            $cameraLanguage = $this->mapStoryModeCameraToSeedance($cameraMotion);
            if (!empty($cameraLanguage)) {
                $parts[] = $cameraLanguage;
            }

            // 3. Style
            $style = !empty($styleInstruction) ? $styleInstruction : 'Cinematic, photorealistic';
            $parts[] = rtrim($style, '.') . '.';

            // 4. Mood → Lighting
            $mood = strtolower(trim($scene['mood'] ?? ''));
            $lighting = $this->mapMoodToLighting($mood);
            $parts[] = $lighting . '.';

            // 5. Audio direction (prevent Seedance from adding speech)
            $parts[] = 'Ambient sound only.';

            $prompt = implode(' ', $parts);

            // Apply SeedancePromptService sanitization pipeline
            $prompt = SeedancePromptService::sanitize($prompt);

            Log::info('StoryModeOrchestrator: Video prompt built', [
                'scene_index' => $scene['segment_index'] ?? null,
                'prompt_preview' => substr($prompt, 0, 150),
                'has_video_action' => !empty($videoAction),
            ]);

            return $prompt;
        } catch (\Exception $e) {
            Log::warning('StoryModeOrchestrator: buildStoryVideoPrompt failed, falling back to camera_motion', [
                'error' => $e->getMessage(),
            ]);
            return $scene['camera_motion'] ?? 'slow zoom in';
        }
    }

    /**
     * Map Story Mode camera motion slugs to Seedance-native camera language.
     */
    protected function mapStoryModeCameraToSeedance(string $cameraMotion): string
    {
        $map = [
            'slow zoom in'      => 'Slow push-in camera',
            'slow zoom out'     => 'Slow pull-back camera',
            'dramatic zoom in'  => 'Fast push-in camera',
            'pan left'          => 'Slow pan left',
            'pan right'         => 'Slow pan right',
            'pan left slow'     => 'Very slow pan left',
            'pan right slow'    => 'Very slow pan right',
            'tilt up'           => 'Slow tilt up',
            'tilt down'         => 'Slow tilt down',
            'zoom in pan right' => 'Push-in with pan right',
            'zoom out pan left' => 'Pull-back with pan left',
            'diagonal drift'    => 'Gentle diagonal tracking',
            'push to subject'   => 'Slow push-in to subject',
            'rise and reveal'   => 'Crane shot rising upward',
            'settle in'         => 'Subtle settle, nearly locked-off',
            'breathe'           => 'Very subtle breathing movement',
        ];

        $normalized = strtolower(trim($cameraMotion));
        return $map[$normalized] ?? 'Slow push-in camera';
    }

    /**
     * Map mood to a lighting direction hint for Seedance.
     */
    protected function mapMoodToLighting(string $mood): string
    {
        $map = [
            'calm'         => 'Soft natural lighting',
            'dramatic'     => 'High-contrast dramatic lighting',
            'energetic'    => 'Bright dynamic lighting',
            'tense'        => 'Harsh directional lighting with deep shadows',
            'mysterious'   => 'Low-key lighting with atmospheric haze',
            'epic'         => 'Golden hour cinematic lighting',
            'playful'      => 'Warm cheerful lighting',
            'nostalgic'    => 'Warm amber tones, soft diffused light',
            'professional' => 'Clean balanced lighting',
            'horror'       => 'Dark underlit with cold blue tones',
            'intimate'     => 'Soft warm close lighting',
            'hopeful'      => 'Bright natural light breaking through',
        ];

        return $map[$mood] ?? 'Clean balanced lighting';
    }

    /**
     * Calculate clip duration matched to narration audio.
     * Snaps UP to Seedance-supported durations: 5, 6, 8, 10.
     * Always rounds up to ensure narration never gets cut off.
     */
    protected function calculateClipDuration(?float $audioDuration): int
    {
        if ($audioDuration === null || $audioDuration <= 0) {
            return 8; // Default when no audio duration available
        }

        // Add 2.0s padding for breathing room (increased from 1.5)
        $withPadding = $audioDuration + 2.0;

        // Clamp to 5-10 range, ceil to always round UP
        $clamped = min(10, max(5, (int) ceil($withPadding)));

        // Snap UP to next Seedance-supported duration (not nearest)
        $supported = [5, 6, 8, 10];
        $snapped = 10; // Default to max if nothing fits
        foreach ($supported as $dur) {
            if ($dur >= $clamped) {
                $snapped = $dur;
                break;
            }
        }

        return $snapped;
    }

    /**
     * Step 4: Generate video clips from images.
     * Submits all Seedance jobs, then polls until all complete.
     */
    protected function stepGenerateVideoClips(StoryModeProject $project): void
    {
        $project->updateProgress('generating_video', 70, 'Submitting video generation jobs');

        $scenes = $project->scenes ?? [];
        $wizardProject = $this->createTempWizardProject($project);
        $styleInstruction = $project->metadata['style_instruction'] ?? '';
        $aspectRatio = $project->aspect_ratio ?? '9:16';

        // Collect image URLs for end_image_url continuity
        $imageUrls = array_map(fn($s) => $s['image_url'] ?? null, $scenes);
        $crossfade = (float) get_option('story_mode_crossfade_duration', 0.5);

        // Phase 1: Submit all video generation jobs
        $pendingTasks = []; // index => taskId
        foreach ($scenes as $i => $scene) {
            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) {
                continue;
            }

            try {
                $audioDuration = isset($scene['audio_duration']) ? (float) $scene['audio_duration'] : null;
                $clipDuration = $this->calculateClipDuration($audioDuration);

                $animationOptions = [
                    'imageUrl' => $imageUrl,
                    'prompt' => $this->buildStoryVideoPrompt($scene, $styleInstruction, $aspectRatio),
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => $project->metadata['video_resolution'] ?? '480p',
                    'variant' => $project->metadata['video_quality'] ?? 'pro',
                    'generate_audio' => false,
                ];

                // Visual continuity: end frame transitions toward next scene
                if ($i < count($scenes) - 1 && !empty($imageUrls[$i + 1])) {
                    $animationOptions['end_image_url'] = $imageUrls[$i + 1];
                }

                $result = $this->animationService->generateAnimation($wizardProject, $animationOptions);

                if (!empty($result['success']) && !empty($result['taskId'])) {
                    $pendingTasks[$i] = $result['taskId'];
                    $scenes[$i]['video_task_id'] = $result['taskId'];
                    Log::info("StoryModeOrchestrator: Video job submitted for scene {$i}", [
                        'taskId' => $result['taskId'],
                    ]);
                } elseif (!empty($result['videoUrl'])) {
                    // Immediate result (unlikely with Seedance)
                    $scenes[$i]['video_url'] = $result['videoUrl'];
                }
            } catch (\Exception $e) {
                Log::warning("StoryModeOrchestrator: Video submission failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $project->update(['scenes' => $scenes]);

        // Phase 2: Poll all pending tasks until complete (max 8 minutes)
        if (!empty($pendingTasks)) {
            $project->updateProgress('generating_video', 72, 'Waiting for video clips (' . count($pendingTasks) . ' jobs)');

            $maxAttempts = 96; // 96 * 5s = 8 minutes
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                sleep(5);

                $stillPending = 0;
                $completedCount = 0;

                foreach ($pendingTasks as $sceneIndex => $taskId) {
                    // Skip already completed
                    if (!empty($scenes[$sceneIndex]['video_url'])) {
                        $completedCount++;
                        continue;
                    }

                    try {
                        $status = $this->animationService->getTaskStatus($taskId);
                        $state = $status['status'] ?? 'unknown';

                        if ($state === 'completed' && !empty($status['videoUrl'])) {
                            $scenes[$sceneIndex]['video_url'] = $status['videoUrl'];
                            $completedCount++;
                            Log::info("StoryModeOrchestrator: Video clip completed for scene {$sceneIndex}", [
                                'videoUrl' => substr($status['videoUrl'], 0, 80),
                            ]);
                        } elseif ($state === 'failed') {
                            Log::warning("StoryModeOrchestrator: Video clip failed for scene {$sceneIndex}", [
                                'error' => $status['error'] ?? 'Unknown',
                            ]);
                            unset($pendingTasks[$sceneIndex]);
                        } else {
                            $stillPending++;
                        }
                    } catch (\Exception $e) {
                        $stillPending++;
                    }
                }

                $totalTasks = count($pendingTasks);
                $progress = 72 + (int) (($completedCount / max(1, $totalTasks)) * 13);
                $project->updateProgress('generating_video', min(85, $progress),
                    "Video clips: {$completedCount}/{$totalTasks} complete"
                );

                // Save progress
                $project->update(['scenes' => $scenes]);

                if ($stillPending === 0) {
                    break;
                }
            }
        }

        $project->update(['scenes' => $scenes]);
        $wizardProject->delete();
    }

    /**
     * Step 5: Assemble the final video from all clips + audio.
     */
    protected function stepAssembleFinalVideo(StoryModeProject $project): void
    {
        $project->updateProgress('assembling', 85, 'Assembling final video');

        $scenes = $project->scenes ?? [];
        $exportQuality = get_option('story_mode_export_quality', 'balanced');
        $exportResolution = get_option('story_mode_export_resolution', '1080p');
        $captionsEnabled = (bool) get_option('story_mode_captions_enabled', 1);
        $musicEnabled = (bool) get_option('story_mode_music_enabled', 1);
        $musicVolume = (float) get_option('story_mode_music_volume', 0.15);
        $crossfadeDuration = (float) get_option('story_mode_crossfade_duration', 0.5);
        $fadeOutDuration = (float) get_option('story_mode_fadeout_duration', 1.5);
        $transitionType = get_option('story_mode_transition_type', 'fade');

        // Increase crossfade for AI-heavy videos (smoother transitions between generated images)
        $aiSceneCount = collect($scenes)->filter(fn($s) =>
            !empty($s['animate_with_ai']) || (empty($s['video_url']) && empty($s['clips']))
        )->count();
        $aiRatio = count($scenes) > 0 ? $aiSceneCount / count($scenes) : 0;
        if ($aiRatio >= 0.5 && $crossfadeDuration < 1.0) {
            $crossfadeDuration = 1.0;
        }

        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $resMap = [
            '9:16' => ['width' => 1080, 'height' => 1920],
            '16:9' => ['width' => 1920, 'height' => 1080],
            '1:1' => ['width' => 1080, 'height' => 1080],
        ];
        $res = $resMap[$aspectRatio] ?? $resMap['9:16'];

        // Build manifest for local FFmpeg assembly (same format as Video Wizard export)
        $manifestScenes = [];
        foreach ($scenes as $i => $scene) {
            $duration = $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;

            // Add tail silence to the LAST scene so fade-out lands on silence, not narration
            if ($i === count($scenes) - 1) {
                $duration += 2.5;
            }

            $manifestScenes[] = [
                'imageUrl' => $scene['image_url'] ?? null,
                'videoUrl' => $scene['video_url'] ?? null,
                'voiceoverUrl' => $scene['audio_url'] ?? null,
                'duration' => $duration,
                'narration' => $scene['text'] ?? '',
                'transition_type' => $scene['transition_type'] ?? $transitionType,
                'transition_duration' => (float) ($scene['transition_duration'] ?? $crossfadeDuration),
                'kenBurns' => [
                    'startScale' => 1.0,
                    'endScale' => 1.2,
                    'startX' => 0.5,
                    'startY' => 0.5,
                    'endX' => 0.5 + (($i % 2 === 0) ? 0.05 : -0.05),
                    'endY' => 0.5 + (($i % 3 === 0) ? 0.05 : -0.05),
                ],
            ];
        }

        $manifest = [
            'scenes' => $manifestScenes,
            'output' => [
                'quality' => $exportQuality,
                'resolution' => $exportResolution,
                'width' => $res['width'],
                'height' => $res['height'],
                'aspectRatio' => $aspectRatio,
                'fps' => 30,
            ],
            'transitions' => [
                'type' => $transitionType,
                'crossfadeDuration' => $crossfadeDuration,
                'fadeOutDuration' => $fadeOutDuration,
            ],
            'music' => $musicEnabled ? ['volume' => $musicVolume] : null,
            'captions' => $captionsEnabled ? ['enabled' => true, 'style' => 'default'] : null,
            'userId' => $project->user_id,
            'projectId' => $project->id,
        ];

        try {
            $project->updateProgress('assembling', 88, 'Rendering video with FFmpeg');

            // Use local FFmpeg assembly (same as Video Wizard Social Content mode)
            $progressCallback = function (int $progress, string $message) use ($project) {
                $mappedProgress = 88 + (int)(($progress / 100) * 11);
                $project->updateProgress('assembling', min(99, $mappedProgress), $message);
            };

            $result = $this->renderService->processStoryModeExport($manifest, $progressCallback);

            $videoUrl = $result['outputUrl'] ?? null;
            $videoPath = $result['outputPath'] ?? null;

            Log::info('StoryModeOrchestrator: Local FFmpeg assembly completed', [
                'project_id' => $project->id,
                'outputUrl' => $videoUrl,
                'outputSize' => $result['outputSize'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('StoryModeOrchestrator: Local assembly failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback: use the first available video clip URL
            $videoUrl = null;
            foreach ($scenes as $scene) {
                if (!empty($scene['video_url'])) {
                    $videoUrl = $scene['video_url'];
                    break;
                }
            }
        }

        $totalDuration = 0;
        foreach ($scenes as $scene) {
            $totalDuration += $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;
        }
        // Subtract crossfade overlaps from total duration
        $totalDuration -= max(0, count($scenes) - 1) * $crossfadeDuration;

        $project->update([
            'status' => 'ready',
            'progress_percent' => 100,
            'current_stage' => 'Complete',
            'video_url' => $videoUrl,
            'video_path' => $videoPath ?? null,
            'video_duration' => (int) round($totalDuration),
            'metadata' => array_merge($project->metadata ?? [], [
                'completed_at' => now()->toIso8601String(),
                'export_quality' => $exportQuality,
                'export_resolution' => $exportResolution,
                'assembly_method' => !empty($videoPath) ? 'local_ffmpeg' : 'clip_preview',
            ]),
        ]);

        if ($project->style) {
            $project->style->incrementUsage();
        }
    }

    /**
     * Step 4 (Sequential): Generate video clips with frame-chaining.
     * Each clip's last frame is extracted and used as the start image for the next clip.
     * Much slower than parallel but provides perfect visual continuity.
     */
    protected function stepGenerateVideoClipsSequential(StoryModeProject $project): void
    {
        $project->updateProgress('generating_video', 70, 'Generating video clips (sequential frame-chaining)');

        $scenes = $project->scenes ?? [];
        $wizardProject = $this->createTempWizardProject($project);
        $styleInstruction = $project->metadata['style_instruction'] ?? '';
        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $crossfade = (float) get_option('story_mode_crossfade_duration', 0.5);
        $projectDir = $this->ensureProjectDir($project->id, 'frames');
        $lastFrameUrl = null;

        foreach ($scenes as $i => $scene) {
            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) {
                continue;
            }

            $progress = 70 + (int) (($i / count($scenes)) * 15);
            $project->updateProgress('generating_video', $progress, "Generating clip " . ($i + 1) . "/" . count($scenes) . " (sequential)");

            // Use last frame from previous clip if available
            $startImage = $lastFrameUrl ?: $imageUrl;

            try {
                $audioDuration = isset($scene['audio_duration']) ? (float) $scene['audio_duration'] : null;
                $clipDuration = $this->calculateClipDuration($audioDuration);

                $animationOptions = [
                    'imageUrl' => $startImage,
                    'prompt' => $this->buildStoryVideoPrompt($scene, $styleInstruction, $aspectRatio),
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => $project->metadata['video_resolution'] ?? '480p',
                    'variant' => $project->metadata['video_quality'] ?? 'pro',
                    'generate_audio' => false,
                ];

                $result = $this->animationService->generateAnimation($wizardProject, $animationOptions);

                if (!empty($result['success']) && !empty($result['taskId'])) {
                    // Poll this single task to completion before moving to next
                    $taskResult = $this->pollSingleTask($result['taskId'], $i);

                    if ($taskResult) {
                        $scenes[$i]['video_url'] = $taskResult['videoUrl'];

                        // Extract last frame for next clip's start image
                        if ($i < count($scenes) - 1) {
                            try {
                                $lastFramePath = "{$projectDir}/lastframe_{$i}.jpg";
                                $this->extractLastFrame($taskResult['videoUrl'], $lastFramePath);
                                if (file_exists($lastFramePath)) {
                                    // Upload frame and use its URL
                                    $lastFrameUrl = $this->uploadFrameToStorage($lastFramePath, $project->id, $i);
                                } else {
                                    $lastFrameUrl = null;
                                }
                            } catch (\Exception $e) {
                                Log::warning("StoryModeOrchestrator: Frame extraction failed for scene {$i}", [
                                    'error' => $e->getMessage(),
                                ]);
                                $lastFrameUrl = null;
                            }
                        }
                    }
                } elseif (!empty($result['videoUrl'])) {
                    $scenes[$i]['video_url'] = $result['videoUrl'];
                }
            } catch (\Exception $e) {
                Log::warning("StoryModeOrchestrator: Sequential video failed for scene {$i}, falling back to original image", [
                    'error' => $e->getMessage(),
                ]);
                $lastFrameUrl = null;
            }

            $project->update(['scenes' => $scenes]);
        }

        $project->update(['scenes' => $scenes]);
        $wizardProject->delete();
    }

    /**
     * Poll a single Seedance task until completion.
     *
     * @return array|null Task result with videoUrl, or null if failed
     */
    protected function pollSingleTask(string $taskId, int $sceneIndex): ?array
    {
        $maxAttempts = 96; // 8 minutes
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            sleep(5);

            try {
                $status = $this->animationService->getTaskStatus($taskId);
                $state = $status['status'] ?? 'unknown';

                if ($state === 'completed' && !empty($status['videoUrl'])) {
                    Log::info("StoryModeOrchestrator: Sequential clip completed for scene {$sceneIndex}");
                    return $status;
                }

                if ($state === 'failed') {
                    Log::warning("StoryModeOrchestrator: Sequential clip failed for scene {$sceneIndex}");
                    return null;
                }
            } catch (\Exception $e) {
                // Continue polling
            }
        }

        Log::warning("StoryModeOrchestrator: Sequential clip timed out for scene {$sceneIndex}");
        return null;
    }

    /**
     * Extract the last frame from a video file/URL.
     */
    protected function extractLastFrame(string $videoUrl, string $outputPath): void
    {
        $renderService = app(VideoRenderService::class);
        $tempDir = sys_get_temp_dir() . '/frame_extract_' . Str::random(8);
        mkdir($tempDir, 0755, true);

        $tempVideo = "{$tempDir}/video.mp4";

        // Download the video
        $response = \Illuminate\Support\Facades\Http::timeout(60)->get($videoUrl);
        if (!$response->successful()) {
            throw new \Exception('Failed to download video for frame extraction');
        }
        file_put_contents($tempVideo, $response->body());

        // Get video duration
        $duration = $renderService->getVideoDuration($tempVideo);

        // Extract last frame (0.1s before end to avoid black frames)
        $seekTo = max(0, $duration - 0.1);
        $ffmpegPath = config('services.video_processor.ffmpeg_path', 'ffmpeg');

        $cmd = implode(' ', array_map('escapeshellarg', [
            $ffmpegPath,
            '-ss', (string) $seekTo,
            '-i', $tempVideo,
            '-vframes', '1',
            '-q:v', '2',
            '-y',
            $outputPath,
        ]));

        shell_exec($cmd . ' 2>&1');

        // Cleanup temp
        @unlink($tempVideo);
        @rmdir($tempDir);
    }

    /**
     * Upload a frame image to storage and return its URL.
     */
    protected function uploadFrameToStorage(string $framePath, int $projectId, int $sceneIndex): ?string
    {
        $fileName = "story-mode/{$projectId}/frames/lastframe_{$sceneIndex}.jpg";

        try {
            if (config('filesystems.disks.gcs.bucket')) {
                \Illuminate\Support\Facades\Storage::disk('gcs')->put($fileName, file_get_contents($framePath), 'public');
                $bucket = config('filesystems.disks.gcs.bucket');
                return "https://storage.googleapis.com/{$bucket}/{$fileName}";
            }

            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, file_get_contents($framePath));
            return url('/files/' . $fileName);
        } catch (\Exception $e) {
            Log::warning("StoryModeOrchestrator: Frame upload failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Intelligently select the best narrator voice based on story mood and content.
     * Analyzes dominant mood across all scenes and narration text for content cues.
     *
     * @return array ['voice_id' => string, 'provider' => string]
     */
    protected function selectSmartVoice(array $scenes): array
    {
        // 1. Collect all moods from scenes
        $moods = [];
        $allNarration = '';
        foreach ($scenes as $scene) {
            $mood = strtolower(trim($scene['mood'] ?? ''));
            if (!empty($mood)) {
                $moods[] = $mood;
            }
            $allNarration .= ' ' . ($scene['text'] ?? '');
        }

        // 2. Find dominant mood (most frequently occurring)
        $dominantMood = 'default';
        if (!empty($moods)) {
            $moodCounts = array_count_values($moods);
            arsort($moodCounts);
            $dominantMood = array_key_first($moodCounts);
        }

        // 3. Map dominant mood to best Kokoro voice
        $moodVoiceMap = [
            'mysterious'   => ['voice_id' => 'bm_george', 'provider' => 'kokoro'],   // British sophisticated — deep, atmospheric
            'horror'       => ['voice_id' => 'bm_george', 'provider' => 'kokoro'],   // British sophisticated — dark, atmospheric
            'dramatic'     => ['voice_id' => 'am_adam', 'provider' => 'kokoro'],      // American professional — authoritative
            'epic'         => ['voice_id' => 'am_adam', 'provider' => 'kokoro'],      // American professional — powerful delivery
            'calm'         => ['voice_id' => 'af_bella', 'provider' => 'kokoro'],     // American warm — gentle storytelling
            'intimate'     => ['voice_id' => 'af_bella', 'provider' => 'kokoro'],     // American warm — soft, personal
            'nostalgic'    => ['voice_id' => 'af_bella', 'provider' => 'kokoro'],     // American warm — wistful, tender
            'tense'        => ['voice_id' => 'am_michael', 'provider' => 'kokoro'],   // American natural — dynamic pacing
            'energetic'    => ['voice_id' => 'am_michael', 'provider' => 'kokoro'],   // American natural — engaging delivery
            'playful'      => ['voice_id' => 'af_sky', 'provider' => 'kokoro'],       // American youthful — bright, energetic
            'hopeful'      => ['voice_id' => 'af_sky', 'provider' => 'kokoro'],       // American youthful — uplifting
            'professional' => ['voice_id' => 'bf_isabella', 'provider' => 'kokoro'],  // British professional — polished
        ];

        $selected = $moodVoiceMap[$dominantMood] ?? ['voice_id' => 'bm_lewis', 'provider' => 'kokoro']; // Default: British warm — versatile storyteller

        // 4. Light gender-heuristic override based on narration content
        $narrationLower = strtolower($allNarration);
        $femaleIndicators = ["i'm a woman", "i'm a girl", "she looked at her reflection", "as a mother", "her own voice", "she whispered to herself"];
        $maleIndicators = ["i'm a man", "i'm a guy", "he looked at his reflection", "as a father", "his own voice", "he whispered to himself"];

        $femaleScore = 0;
        $maleScore = 0;
        foreach ($femaleIndicators as $indicator) {
            if (str_contains($narrationLower, $indicator)) {
                $femaleScore++;
            }
        }
        foreach ($maleIndicators as $indicator) {
            if (str_contains($narrationLower, $indicator)) {
                $maleScore++;
            }
        }

        // Only override if there's a clear signal (2+ indicators)
        if ($femaleScore >= 2 && $femaleScore > $maleScore) {
            // Prefer a female voice matching the mood's energy
            $femaleAlternatives = [
                'bm_george'    => 'af_bella',
                'am_adam'      => 'af_sarah',
                'am_michael'   => 'af_sky',
                'bm_lewis'     => 'af_bella',
                'bf_isabella'  => 'bf_isabella', // Already female
                'af_bella'     => 'af_bella',     // Already female
                'af_sky'       => 'af_sky',       // Already female
                'af_sarah'     => 'af_sarah',     // Already female
            ];
            $selected['voice_id'] = $femaleAlternatives[$selected['voice_id']] ?? 'af_bella';
        } elseif ($maleScore >= 2 && $maleScore > $femaleScore) {
            // Prefer a male voice matching the mood's energy
            $maleAlternatives = [
                'af_bella'     => 'bm_lewis',
                'af_sky'       => 'am_michael',
                'af_sarah'     => 'am_adam',
                'bf_isabella'  => 'bm_george',
                'bm_george'    => 'bm_george',   // Already male
                'am_adam'      => 'am_adam',       // Already male
                'am_michael'   => 'am_michael',   // Already male
                'bm_lewis'     => 'bm_lewis',     // Already male
            ];
            $selected['voice_id'] = $maleAlternatives[$selected['voice_id']] ?? 'bm_lewis';
        }

        Log::info('StoryModeOrchestrator: Smart voice analysis', [
            'dominant_mood' => $dominantMood,
            'mood_distribution' => !empty($moods) ? array_count_values($moods) : [],
            'female_score' => $femaleScore,
            'male_score' => $maleScore,
            'selected_voice' => $selected['voice_id'],
        ]);

        return $selected;
    }

    /**
     * Create a temporary WizardProject to interface with existing services
     * that expect WizardProject as a parameter.
     */
    protected function createTempWizardProject(StoryModeProject $storyProject): WizardProject
    {
        return WizardProject::create([
            'user_id' => $storyProject->user_id,
            'team_id' => $storyProject->team_id,
            'name' => "[StoryMode] {$storyProject->title}",
            'status' => 'processing',
            'aspect_ratio' => $storyProject->aspect_ratio ?? '9:16',
            'platform' => 'multi-platform',
        ]);
    }

    /**
     * Ensure project storage directory exists.
     */
    protected function ensureProjectDir(int $projectId, string $subDir = ''): string
    {
        $path = public_path("story-mode/{$projectId}");
        if ($subDir) {
            $path .= "/{$subDir}";
        }
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
        return $path;
    }
}
