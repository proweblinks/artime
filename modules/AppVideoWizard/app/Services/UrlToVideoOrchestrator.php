<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Models\WizardProject;

/**
 * UrlToVideoOrchestrator
 *
 * Pipeline for URL-to-Video mode. Reuses the same sub-services as StoryModeOrchestrator:
 * Visual Script → Voiceover → Images → Video Clips → Assembly
 */
class UrlToVideoOrchestrator
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
     * Run the full generation pipeline for a URL-to-Video project.
     */
    public function generate(UrlToVideoProject $project): void
    {
        Log::info('UrlToVideoOrchestrator: Starting pipeline', ['project_id' => $project->id]);

        try {
            // Step 1: Build Visual Script (15-35%)
            $this->stepBuildVisualScript($project);
            if ($this->isCancelled($project)) return;

            // Step 2: Generate Voiceover (35-50%)
            $this->stepGenerateVoiceover($project);
            if ($this->isCancelled($project)) return;

            // Step 3: Generate Images (50-70%)
            $this->stepGenerateImages($project);
            if ($this->isCancelled($project)) return;

            // Step 4: Generate Video Clips (70-85%)
            $this->stepGenerateVideoClips($project);
            if ($this->isCancelled($project)) return;

            // Step 5: Assemble Final Video (85-100%)
            $this->stepAssembleFinalVideo($project);

            Log::info('UrlToVideoOrchestrator: Pipeline completed', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('UrlToVideoOrchestrator: Pipeline failed', [
                'project_id' => $project->id,
                'stage' => $project->current_stage,
                'error' => $e->getMessage(),
            ]);
            $project->markFailed($e->getMessage());
        }
    }

    /**
     * Check if the project has been cancelled or deleted (refresh from DB).
     */
    protected function isCancelled(UrlToVideoProject $project): bool
    {
        // Re-fetch from DB — project may have been force-deleted by cancel
        $fresh = UrlToVideoProject::find($project->id);

        if (!$fresh || $fresh->status === 'cancelled') {
            Log::info('UrlToVideoOrchestrator: Project cancelled/deleted, stopping pipeline', [
                'project_id' => $project->id,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Step 1: Build visual script from transcript segments.
     */
    protected function stepBuildVisualScript(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_visual_script', 15, 'Creating visual script');

        $scenes = $project->scenes ?? [];

        // Smart-skip: If all scenes already have image_prompt from interactive AI Studio, skip generation
        $allHavePrompts = !empty($scenes) && collect($scenes)->every(fn($s) => !empty($s['image_prompt']));
        if ($allHavePrompts) {
            Log::info('UrlToVideoOrchestrator: Visual script already populated (interactive mode), skipping', [
                'project_id' => $project->id,
                'scenes' => count($scenes),
            ]);

            // Still store visual_script if not already set
            if (empty($project->visual_script)) {
                $visualScript = array_map(fn($s) => [
                    'image_prompt' => $s['image_prompt'] ?? '',
                    'video_action' => $s['video_action'] ?? '',
                    'camera_motion' => $s['camera_motion'] ?? 'slow zoom in',
                    'mood' => $s['mood'] ?? 'professional',
                    'voice_emotion' => $s['voice_emotion'] ?? 'neutral',
                    'characters_in_scene' => $s['characters_in_scene'] ?? [],
                    'transition_type' => $s['transition_type'] ?? 'fade',
                    'transition_duration' => (float) ($s['transition_duration'] ?? 0.5),
                ], $scenes);

                $project->update([
                    'visual_script' => $visualScript,
                ]);
            }
            return;
        }

        $aspectRatio = $project->aspect_ratio ?? '9:16';

        // Derive style instruction from visual style config (if set) or content brief
        $brief = $project->content_brief ?? [];
        $tone = $brief['tone'] ?? 'professional';
        $metadata = $project->metadata ?? [];
        $styleConfig = $metadata['visual_style_config'] ?? null;
        if ($styleConfig && !empty($styleConfig['imagePrefix'])) {
            $styleInstruction = "{$styleConfig['imagePrefix']}. {$tone} tone. {$styleConfig['imageSuffix']}";
        } else {
            $category = $brief['content_category'] ?? 'general';
            $styleInstruction = "Cinematic, photorealistic, {$tone} tone, {$category} content";
        }

        $segments = array_map(fn ($s) => [
            'text' => $s['text'] ?? '',
            'estimated_duration' => $s['audio_duration'] ?? $s['estimated_duration'] ?? 6,
        ], $scenes);

        $visualScript = $this->scriptService->buildVisualScript($segments, $styleInstruction, $aspectRatio, $project->team_id);
        $characterBible = $this->scriptService->lastCharacterBible;

        $updatedScenes = [];
        foreach ($scenes as $i => $scene) {
            $visual = $visualScript[$i] ?? [];
            // Only overwrite if not already set (preserve interactive mode edits)
            $scene['image_prompt'] = $scene['image_prompt'] ?? $visual['image_prompt'] ?? "A cinematic scene: {$scene['text']}";
            $scene['video_action'] = $scene['video_action'] ?? $visual['video_action'] ?? '';
            $scene['characters_in_scene'] = $scene['characters_in_scene'] ?? $visual['characters_in_scene'] ?? [];
            $scene['camera_motion'] = $scene['camera_motion'] ?? $visual['camera_motion'] ?? 'slow zoom in';
            $scene['mood'] = $scene['mood'] ?? $visual['mood'] ?? 'professional';
            $scene['voice_emotion'] = $scene['voice_emotion'] ?? $visual['voice_emotion'] ?? 'neutral';
            $scene['transition_type'] = $scene['transition_type'] ?? $visual['transition_type'] ?? 'fade';
            $scene['transition_duration'] = (float) ($scene['transition_duration'] ?? $visual['transition_duration'] ?? 0.5);
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

        Log::info('UrlToVideoOrchestrator: Visual script built', [
            'project_id' => $project->id,
            'scenes' => count($updatedScenes),
        ]);
    }

    /**
     * Step 2: Generate voiceover audio for each scene.
     */
    protected function stepGenerateVoiceover(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_voiceover', 35, 'Generating voiceover');

        $scenes = $project->scenes ?? [];
        if (empty($scenes)) {
            throw new \Exception('No scenes data available for voiceover generation');
        }

        $voiceId = $project->voice_id;
        $voiceProvider = $project->voice_provider ?: null;

        if (!$voiceId) {
            // Auto-select based on mood
            $voiceId = 'bm_lewis';
            $voiceProvider = 'kokoro';
        }

        $wizardProject = $this->createTempWizardProject($project);
        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            if ($this->isCancelled($project)) return;

            $progress = 35 + (int) (($i / count($scenes)) * 15);
            $project->updateProgress('generating_voiceover', $progress, "Generating voiceover (" . ($i + 1) . "/" . count($scenes) . ")");

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

                // Fix stale video_edit.trimEnd that was set from pre-voiceover estimated_duration.
                // If trimEnd is shorter than the real audio_duration, extend it so the clip covers the full scene.
                if (!empty($scene['video_edit']) && isset($scene['video_edit']['trimEnd'])) {
                    $actualDuration = (float) $scene['audio_duration'];
                    $oldTrimEnd = (float) $scene['video_edit']['trimEnd'];
                    if ($oldTrimEnd < $actualDuration) {
                        Log::debug('UrlToVideoOrchestrator: Updated stale trimEnd', [
                            'scene_id' => $scene['id'] ?? "scene_{$i}",
                            'old_trimEnd' => $oldTrimEnd,
                            'new_trimEnd' => round($actualDuration, 2),
                        ]);
                        $scene['video_edit']['trimEnd'] = round($actualDuration, 2);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Voiceover failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
                $scene['audio_url'] = null;
                $scene['audio_duration'] = $scene['estimated_duration'] ?? 6;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);
        $wizardProject->delete();
    }

    /**
     * Step 3: Generate images for each scene.
     */
    protected function stepGenerateImages(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_images', 50, 'Generating images');

        $scenes = $project->scenes ?? [];
        $imageModel = get_option('story_mode_image_model', 'nanobanana2');
        $wizardProject = $this->createTempWizardProject($project);

        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            if ($this->isCancelled($project)) return;

            // Skip AI generation if scene already has a real image assigned
            if (!empty($scene['image_url'])) {
                Log::info('UrlToVideoOrchestrator: Using pre-assigned image for scene', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                    'image_url' => $scene['image_url'],
                ]);
                $updatedScenes[] = $scene;
                continue;
            }

            $progress = 50 + (int) (($i / count($scenes)) * 20);
            $project->updateProgress('generating_images', $progress, "Generating image (" . ($i + 1) . "/" . count($scenes) . ")");

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
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Image generation failed for segment {$i}", [
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
     * Step 4: Generate video clips from images.
     */
    protected function stepGenerateVideoClips(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_video', 70, 'Submitting video generation jobs');

        $scenes = $project->scenes ?? [];
        $wizardProject = $this->createTempWizardProject($project);
        $styleInstruction = $project->metadata['style_instruction'] ?? '';
        $styleConfig = $project->metadata['visual_style_config'] ?? null;
        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $imageUrls = array_map(fn($s) => $s['image_url'] ?? null, $scenes);

        // Phase 1: Submit all jobs
        $pendingTasks = [];
        foreach ($scenes as $i => $scene) {
            // Skip AI video generation if scene already has a pre-assigned video clip
            if (!empty($scene['video_url'])) {
                Log::info('UrlToVideoOrchestrator: Using pre-assigned video clip', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                    'video_url' => $scene['video_url'],
                ]);
                continue;
            }

            // Skip if user chose NOT to animate this scene (will use Ken Burns instead)
            if (empty($scene['animate_with_ai'])) {
                Log::info('UrlToVideoOrchestrator: Scene uses static image (no animation)', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                ]);
                continue;
            }

            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) {
                continue;
            }

            try {
                $audioDuration = isset($scene['audio_duration']) ? (float) $scene['audio_duration'] : null;
                $clipDuration = $this->calculateClipDuration($audioDuration);

                $animationOptions = [
                    'imageUrl' => $imageUrl,
                    'prompt' => $this->buildVideoPrompt($scene, $styleInstruction, $aspectRatio, $styleConfig),
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => $project->metadata['video_resolution'] ?? '480p',
                    'variant' => $project->metadata['video_quality'] ?? 'pro',
                    'generate_audio' => false,
                ];

                if ($i < count($scenes) - 1 && !empty($imageUrls[$i + 1])) {
                    $animationOptions['end_image_url'] = $imageUrls[$i + 1];
                }

                $result = $this->animationService->generateAnimation($wizardProject, $animationOptions);

                if (!empty($result['success']) && !empty($result['taskId'])) {
                    $pendingTasks[$i] = $result['taskId'];
                    $scenes[$i]['video_task_id'] = $result['taskId'];
                } elseif (!empty($result['videoUrl'])) {
                    $scenes[$i]['video_url'] = $result['videoUrl'];
                }
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Video submission failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $project->update(['scenes' => $scenes]);

        // Phase 2: Poll pending tasks
        if (!empty($pendingTasks)) {
            $project->updateProgress('generating_video', 72, 'Waiting for video clips (' . count($pendingTasks) . ' jobs)');

            $maxAttempts = 96;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                sleep(5);

                if ($this->isCancelled($project)) return;

                $stillPending = 0;
                $completedCount = 0;

                foreach ($pendingTasks as $sceneIndex => $taskId) {
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
                        } elseif ($state === 'failed') {
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
     * Step 5: Assemble the final video.
     */
    protected function stepAssembleFinalVideo(UrlToVideoProject $project): void
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

        $manifestScenes = [];
        foreach ($scenes as $i => $scene) {
            $duration = $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;

            if ($i === count($scenes) - 1) {
                $duration += 2.5;
            }

            // Use crop focal point if set, otherwise default to center
            $crop = $scene['crop'] ?? null;
            $focalX = $crop ? $crop['focalX'] : 0.5;
            $focalY = $crop ? $crop['focalY'] : 0.5;

            $manifestScenes[] = [
                'imageUrl' => $scene['image_url'] ?? null,
                'videoUrl' => $scene['video_url'] ?? null,
                'voiceoverUrl' => $scene['audio_url'] ?? null,
                'duration' => $duration,
                'narration' => $scene['text'] ?? '',
                'transition_type' => $scene['transition_type'] ?? $transitionType,
                'transition_duration' => (float) ($scene['transition_duration'] ?? $crossfadeDuration),
                'crop' => $crop,
                'video_edit' => $scene['video_edit'] ?? null,
                'clips' => $scene['clips'] ?? null,
                'kenBurns' => [
                    'startScale' => 1.0,
                    'endScale' => 1.2,
                    'startX' => $focalX,
                    'startY' => $focalY,
                    'endX' => $focalX + (($i % 2 === 0) ? 0.05 : -0.05),
                    'endY' => $focalY + (($i % 3 === 0) ? 0.05 : -0.05),
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

            Log::info('UrlToVideoOrchestrator: Starting assembly', [
                'project_id' => $project->id,
                'scene_count' => count($manifestScenes),
                'scenes_with_video' => count(array_filter($manifestScenes, fn($s) => !empty($s['videoUrl']))),
                'scenes_with_trim' => count(array_filter($manifestScenes, fn($s) => !empty($s['video_edit']))),
            ]);

            $progressCallback = function (int $progress, string $message) use ($project) {
                $mappedProgress = 88 + (int)(($progress / 100) * 11);
                $project->updateProgress('assembling', min(99, $mappedProgress), $message);
            };

            $result = $this->renderService->processStoryModeExport($manifest, $progressCallback);

            $videoUrl = $result['outputUrl'] ?? null;
            $videoPath = $result['outputPath'] ?? null;

            Log::info('UrlToVideoOrchestrator: Assembly succeeded', [
                'project_id' => $project->id,
                'output_url' => $videoUrl ? substr($videoUrl, 0, 80) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('UrlToVideoOrchestrator: Assembly FAILED — falling back to raw clip', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $videoUrl = null;
            $videoPath = null;
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
    }

    /**
     * Build a Seedance video prompt for a scene.
     */
    protected function buildVideoPrompt(array $scene, string $styleInstruction, string $aspectRatio, ?array $styleConfig = null): string
    {
        $parts = [];

        // 1. CORE: Rich video action (2-4 sentences from AI)
        $videoAction = trim($scene['video_action'] ?? '');
        if (!empty($videoAction)) {
            $parts[] = rtrim($videoAction, '.');
        } else {
            $narration = trim($scene['text'] ?? '');
            if (!empty($narration)) {
                $firstSentence = strtok($narration, '.!?');
                if (!empty($firstSentence)) {
                    $parts[] = trim($firstSentence);
                }
            }
        }

        if (empty($parts)) {
            return $scene['camera_motion'] ?? 'slow zoom in';
        }

        // 2. CAMERA: Woven naturally into the narrative
        $cameraMotion = $scene['camera_motion'] ?? 'slow zoom in';
        $parts[] = $this->mapCameraToSeedance($cameraMotion);

        // 3. STYLE: Use style config anchor if available, else raw instruction
        if ($styleConfig && !empty($styleConfig['videoAnchor'])) {
            $parts[] = $styleConfig['videoAnchor'];
        } else {
            $style = !empty($styleInstruction) ? $styleInstruction : 'Cinematic, photorealistic';
            $parts[] = rtrim($style, '.');
        }

        // 4. LIGHTING: Mood-specific + style-specific
        $mood = strtolower(trim($scene['mood'] ?? ''));
        $moodLighting = $this->mapMoodToLighting($mood);
        if ($styleConfig && !empty($styleConfig['videoLighting']) && $styleConfig['videoLighting'] !== $moodLighting) {
            $parts[] = $styleConfig['videoLighting'] . ', ' . strtolower($moodLighting);
        } else {
            $parts[] = $moodLighting;
        }

        // 5. COLOR TREATMENT from style
        if ($styleConfig && !empty($styleConfig['videoColor'])) {
            $parts[] = $styleConfig['videoColor'];
        }

        // 6. AUDIO: Context-aware from scene content
        $parts[] = $this->extractAudioFromScene($scene);

        // Assemble as flowing prose
        $prompt = implode('. ', array_filter($parts)) . '.';

        // Clean up double periods, extra spaces
        $prompt = preg_replace('/\.\s*\./', '.', $prompt);
        $prompt = preg_replace('/\s{2,}/', ' ', $prompt);

        if (class_exists(SeedancePromptService::class)) {
            $prompt = SeedancePromptService::sanitize($prompt);
        }

        return trim($prompt);
    }

    protected function mapCameraToSeedance(string $cameraMotion): string
    {
        $map = [
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

        return $map[strtolower(trim($cameraMotion))] ?? 'The camera slowly pushes in';
    }

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
            'nostalgic'    => 'Warm amber tones with soft diffusion',
            'professional' => 'Clean balanced lighting',
            'hopeful'      => 'Bright natural light breaking through',
            'horror'       => 'Dim flickering light with heavy shadows',
            'intimate'     => 'Soft warm close lighting',
        ];

        return $map[$mood] ?? 'Clean balanced lighting';
    }

    /**
     * Extract context-aware audio direction from scene content.
     */
    protected function extractAudioFromScene(array $scene): string
    {
        $text = strtolower(($scene['video_action'] ?? '') . ' ' . ($scene['image_prompt'] ?? '') . ' ' . ($scene['text'] ?? ''));
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

    protected function calculateClipDuration(?float $audioDuration): int
    {
        if ($audioDuration === null || $audioDuration <= 0) {
            return 8;
        }

        $withPadding = $audioDuration + 2.0;
        $clamped = min(10, max(5, (int) ceil($withPadding)));

        $supported = [5, 6, 8, 10];
        $snapped = 10;
        foreach ($supported as $dur) {
            if ($dur >= $clamped) {
                $snapped = $dur;
                break;
            }
        }

        return $snapped;
    }

    /**
     * Create a temporary WizardProject for sub-service compatibility.
     */
    protected function createTempWizardProject(UrlToVideoProject $project): WizardProject
    {
        return WizardProject::create([
            'user_id' => $project->user_id,
            'team_id' => $project->team_id,
            'name' => "[UrlToVideo] {$project->title}",
            'status' => 'processing',
            'aspect_ratio' => $project->aspect_ratio ?? '9:16',
            'platform' => 'multi-platform',
        ]);
    }

    protected function ensureProjectDir(int $projectId, string $subDir = ''): string
    {
        $path = public_path("url-to-video/{$projectId}");
        if ($subDir) {
            $path .= "/{$subDir}";
        }
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
        return $path;
    }
}
