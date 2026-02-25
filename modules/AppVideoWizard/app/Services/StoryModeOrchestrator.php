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
            // Step 1: Generate Voiceover (15-30%)
            $this->stepGenerateVoiceover($project);

            // Step 2: Build Visual Script (30-45%)
            $this->stepBuildVisualScript($project);

            // Step 3: Generate Images (45-70%)
            $this->stepGenerateImages($project);

            // Step 4: Generate Video Clips (70-85%)
            $this->stepGenerateVideoClips($project);

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
     * Step 1: Generate voiceover audio for each transcript segment.
     */
    protected function stepGenerateVoiceover(StoryModeProject $project): void
    {
        $project->updateProgress('generating_voiceover', 15, 'Generating voiceover');

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
            $progress = 15 + (int) (($i / count($scenes)) * 15);
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
     * Step 2: Build visual script (image prompts) from transcript segments.
     */
    protected function stepBuildVisualScript(StoryModeProject $project): void
    {
        $project->updateProgress('generating_visual_script', 30, 'Creating visual script');

        $scenes = $project->scenes ?? [];
        $styleInstruction = $project->getEffectiveStyleInstruction();

        // Convert scenes to segments format for script service
        $segments = array_map(fn ($s) => [
            'text' => $s['text'] ?? '',
            'estimated_duration' => $s['audio_duration'] ?? $s['estimated_duration'] ?? 6,
        ], $scenes);

        $visualScript = $this->scriptService->buildVisualScript($segments, $styleInstruction);

        // Merge visual script data back into scenes
        $updatedScenes = [];
        foreach ($scenes as $i => $scene) {
            $visual = $visualScript[$i] ?? [];
            $scene['image_prompt'] = $visual['image_prompt'] ?? "A cinematic scene: {$scene['text']}";
            $scene['camera_motion'] = $visual['camera_motion'] ?? 'slow zoom in';
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
        $project->updateProgress('generating_images', 45, 'Generating images');

        $scenes = $project->scenes ?? [];
        $imageModel = get_option('story_mode_image_model', 'nanobanana-pro');
        $wizardProject = $this->createTempWizardProject($project);

        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            $progress = 45 + (int) (($i / count($scenes)) * 25);
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

        // Phase 1: Submit all video generation jobs
        $pendingTasks = []; // index => taskId
        foreach ($scenes as $i => $scene) {
            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) {
                continue;
            }

            try {
                $clipDuration = min(8, max(4, (int) round($scene['audio_duration'] ?? 6)));

                $result = $this->animationService->generateAnimation($wizardProject, [
                    'imageUrl' => $imageUrl,
                    'prompt' => $scene['camera_motion'] ?? 'slow zoom in',
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                ]);

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
        $captionsEnabled = (bool) get_option('story_mode_captions_enabled', 1);
        $musicEnabled = (bool) get_option('story_mode_music_enabled', 1);
        $musicVolume = (float) get_option('story_mode_music_volume', 0.15);
        $exportQuality = get_option('story_mode_export_quality', 'balanced');
        $exportResolution = get_option('story_mode_export_resolution', '1080p');

        // Build the export manifest for VideoRenderService
        $manifestScenes = [];
        foreach ($scenes as $i => $scene) {
            $manifestScenes[] = [
                'imageUrl' => $scene['image_url'] ?? null,
                'videoUrl' => $scene['video_url'] ?? null,
                'voiceoverUrl' => $scene['audio_url'] ?? null,
                'duration' => $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6,
                'narration' => $scene['text'] ?? '',
            ];
        }

        // Determine aspect ratio dimensions
        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $resMap = [
            '9:16' => ['width' => 1080, 'height' => 1920],
            '16:9' => ['width' => 1920, 'height' => 1080],
            '1:1' => ['width' => 1080, 'height' => 1080],
        ];
        $res = $resMap[$aspectRatio] ?? $resMap['9:16'];

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
            'music' => $musicEnabled ? [
                'volume' => $musicVolume,
            ] : null,
            'captions' => $captionsEnabled ? [
                'enabled' => true,
                'style' => 'default',
            ] : null,
        ];

        try {
            $project->updateProgress('assembling', 90, 'Rendering final video');

            // Submit to Cloud Run video processor
            $jobId = \Illuminate\Support\Str::uuid()->toString();
            $manifest['userId'] = $project->user_id;
            $manifest['projectId'] = $project->id;

            $submitResult = $this->renderService->processExportViaCloudRun($manifest, $jobId);
            Log::info('StoryModeOrchestrator: Cloud Run job submitted', [
                'project_id' => $project->id,
                'job_id' => $jobId,
                'result' => $submitResult,
            ]);

            // Poll for completion (max 10 minutes)
            $maxAttempts = 120; // 120 * 5s = 10 minutes
            $videoUrl = null;
            $videoPath = null;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                sleep(5);

                $status = $this->renderService->getCloudRunExportStatus($jobId);
                $state = $status['status'] ?? $status['state'] ?? 'unknown';

                $progressPct = $status['progress'] ?? 0;
                $mappedProgress = 90 + (int) (($progressPct / 100) * 9);
                $project->updateProgress('assembling', min(99, $mappedProgress), $status['stage'] ?? 'Rendering...');

                if ($state === 'completed' || $state === 'done') {
                    $videoUrl = $status['videoUrl'] ?? $status['video_url'] ?? $status['url'] ?? null;
                    $videoPath = $status['videoPath'] ?? $status['video_path'] ?? null;
                    break;
                }

                if ($state === 'failed' || $state === 'error') {
                    throw new \Exception('Cloud Run export failed: ' . ($status['error'] ?? 'Unknown error'));
                }
            }

            if (empty($videoUrl)) {
                throw new \Exception('Video render timed out or did not return a video URL');
            }

            $totalDuration = 0;
            foreach ($scenes as $scene) {
                $totalDuration += $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;
            }

            $project->update([
                'status' => 'ready',
                'progress_percent' => 100,
                'current_stage' => 'Complete',
                'video_url' => $videoUrl,
                'video_path' => $videoPath,
                'video_duration' => (int) round($totalDuration),
                'metadata' => array_merge($project->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                    'export_quality' => $exportQuality,
                    'export_resolution' => $exportResolution,
                    'cloud_run_job_id' => $jobId,
                ]),
            ]);

            // Increment style usage counter
            if ($project->style) {
                $project->style->incrementUsage();
            }
        } catch (\Exception $e) {
            Log::error('StoryModeOrchestrator: Assembly failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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
