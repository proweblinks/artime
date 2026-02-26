<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\StoryModeProject;
use Modules\AppVideoWizard\Models\WizardProject;

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

        $voiceId = $project->voice_id ?: get_option('story_mode_default_voice', 'nova');
        $voiceProvider = $project->voice_provider ?: null;

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
     */
    protected function stepBuildVisualScript(StoryModeProject $project): void
    {
        $project->updateProgress('generating_visual_script', 15, 'Creating visual script');

        $scenes = $project->scenes ?? [];
        $styleInstruction = $project->getEffectiveStyleInstruction();

        // Convert scenes to segments format for script service
        $segments = array_map(fn ($s) => [
            'text' => $s['text'] ?? '',
            'estimated_duration' => $s['audio_duration'] ?? $s['estimated_duration'] ?? 6,
        ], $scenes);

        $visualScript = $this->scriptService->buildVisualScript($segments, $styleInstruction);

        // Merge visual script data back into scenes (includes mood, voice_emotion, transitions)
        $updatedScenes = [];
        foreach ($scenes as $i => $scene) {
            $visual = $visualScript[$i] ?? [];
            $scene['image_prompt'] = $visual['image_prompt'] ?? "A cinematic scene: {$scene['text']}";
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
        ]);
    }

    /**
     * Step 3: Generate images for each scene.
     */
    protected function stepGenerateImages(StoryModeProject $project): void
    {
        $project->updateProgress('generating_images', 50, 'Generating images');

        $scenes = $project->scenes ?? [];
        $imageModel = get_option('story_mode_image_model', 'nanobanana-pro');
        $wizardProject = $this->createTempWizardProject($project);

        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            $progress = 50 + (int) (($i / count($scenes)) * 20);
            $project->updateProgress('generating_images', $progress, "Generating image ({$i}/{" . count($scenes) . "})");

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
     * Step 4: Generate video clips from images.
     * Submits all Seedance jobs, then polls until all complete.
     */
    protected function stepGenerateVideoClips(StoryModeProject $project): void
    {
        $project->updateProgress('generating_video', 70, 'Submitting video generation jobs');

        $scenes = $project->scenes ?? [];
        $wizardProject = $this->createTempWizardProject($project);

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
                $clipDuration = 10;

                $animationOptions = [
                    'imageUrl' => $imageUrl,
                    'prompt' => $scene['camera_motion'] ?? 'slow zoom in',
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => '480p',
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
            $manifestScenes[] = [
                'imageUrl' => $scene['image_url'] ?? null,
                'videoUrl' => $scene['video_url'] ?? null,
                'voiceoverUrl' => $scene['audio_url'] ?? null,
                'duration' => $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6,
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
                $clipDuration = 10;

                $animationOptions = [
                    'imageUrl' => $startImage,
                    'prompt' => $scene['camera_motion'] ?? 'slow zoom in',
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => '480p',
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
