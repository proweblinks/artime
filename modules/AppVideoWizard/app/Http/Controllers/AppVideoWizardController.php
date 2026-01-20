<?php

namespace Modules\AppVideoWizard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;
use Modules\AppVideoWizard\Services\ConceptService;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\VoiceoverService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AppVideoWizardController extends Controller
{
    /**
     * Generate a signed upload URL for RunPod video uploads.
     * Used internally by AnimationService.
     */
    public static function generateVideoUploadUrl(int $projectId, string $filename): array
    {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(2); // 2 hours should be enough for video generation

        // Store token data in cache
        $tokenData = [
            'project_id' => $projectId,
            'filename' => $filename,
            'expires_at' => $expiresAt->timestamp,
        ];
        Cache::put("runpod_upload_token:{$token}", $tokenData, $expiresAt);

        // Build the upload URL - use config('app.url') to ensure correct HTTPS URL
        // The url() helper can fail on servers behind proxies/load balancers
        $baseUrl = rtrim(config('app.url'), '/');
        $uploadUrl = "{$baseUrl}/api/runpod/video-upload/{$token}";

        // Build the final video URL (where the video will be accessible after upload)
        // IMPORTANT: On cPanel hosting, web root is public_html but Laravel public is at public_html/public
        // So we need /public/ prefix for direct file access
        $videoPath = "public/wizard-videos/{$projectId}/{$filename}";
        $videoUrl = "{$baseUrl}/{$videoPath}";

        return [
            'upload_url' => $uploadUrl,
            'video_url' => $videoUrl,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Handle video upload from RunPod worker.
     * This endpoint receives the generated video via PUT request.
     */
    public function runpodVideoUpload(Request $request, string $token)
    {
        Log::info('📥 RunPod video upload request received', ['token' => substr($token, 0, 16) . '...']);

        // Validate token
        $tokenData = Cache::get("runpod_upload_token:{$token}");

        if (!$tokenData) {
            Log::warning('📥 Invalid or expired upload token', ['token' => substr($token, 0, 16)]);
            return response()->json(['error' => 'Invalid or expired upload token'], 401);
        }

        // Check expiration
        if (time() > $tokenData['expires_at']) {
            Cache::forget("runpod_upload_token:{$token}");
            Log::warning('📥 Upload token expired');
            return response()->json(['error' => 'Upload token expired'], 401);
        }

        try {
            // Get the raw video data from request body
            $videoContent = $request->getContent();

            if (empty($videoContent)) {
                Log::error('📥 No video content received');
                return response()->json(['error' => 'No video content received'], 400);
            }

            Log::info('📥 Received video content', ['size' => strlen($videoContent)]);

            // Create directory if needed
            $projectId = $tokenData['project_id'];
            $filename = $tokenData['filename'];
            $directory = public_path("wizard-videos/{$projectId}");

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Save the video file
            $filePath = "{$directory}/{$filename}";
            File::put($filePath, $videoContent);

            // Invalidate the token (one-time use)
            Cache::forget("runpod_upload_token:{$token}");

            Log::info('📥 Video saved successfully', [
                'path' => $filePath,
                'size' => strlen($videoContent),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'path' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('📥 Video upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a signed upload URL for Kokoro TTS audio uploads.
     * Used internally by KokoroTtsService.
     */
    public static function generateAudioUploadUrl(int $projectId, string $filename): array
    {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(1); // 1 hour for audio generation

        // Store token data in cache
        $tokenData = [
            'project_id' => $projectId,
            'filename' => $filename,
            'type' => 'audio',
            'expires_at' => $expiresAt->timestamp,
        ];
        Cache::put("runpod_audio_upload_token:{$token}", $tokenData, $expiresAt);

        // Build the upload URL
        $baseUrl = rtrim(config('app.url'), '/');
        $uploadUrl = "{$baseUrl}/api/runpod/audio-upload/{$token}";

        // Build the final audio URL (where the audio will be accessible after upload)
        // IMPORTANT: On cPanel hosting, web root is public_html but Laravel public is at public_html/public
        $audioPath = "public/wizard-audio/{$projectId}/{$filename}";
        $audioUrl = "{$baseUrl}/{$audioPath}";

        return [
            'upload_url' => $uploadUrl,
            'audio_url' => $audioUrl,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Handle audio upload from RunPod Kokoro TTS worker.
     * This endpoint receives the generated audio via PUT request.
     */
    public function runpodAudioUpload(Request $request, string $token)
    {
        Log::info('🎤 RunPod audio upload request received', ['token' => substr($token, 0, 16) . '...']);

        // Validate token
        $tokenData = Cache::get("runpod_audio_upload_token:{$token}");

        if (!$tokenData) {
            Log::warning('🎤 Invalid or expired audio upload token', ['token' => substr($token, 0, 16)]);
            return response()->json(['error' => 'Invalid or expired upload token'], 401);
        }

        // Check expiration
        if (time() > $tokenData['expires_at']) {
            Cache::forget("runpod_audio_upload_token:{$token}");
            Log::warning('🎤 Audio upload token expired');
            return response()->json(['error' => 'Upload token expired'], 401);
        }

        try {
            // Get the raw audio data from request body
            $audioContent = $request->getContent();

            if (empty($audioContent)) {
                Log::error('🎤 No audio content received');
                return response()->json(['error' => 'No audio content received'], 400);
            }

            Log::info('🎤 Received audio content', ['size' => strlen($audioContent)]);

            // Create directory if needed
            $projectId = $tokenData['project_id'];
            $filename = $tokenData['filename'];
            $directory = public_path("wizard-audio/{$projectId}");

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Save the audio file
            $filePath = "{$directory}/{$filename}";
            File::put($filePath, $audioContent);

            // Invalidate the token (one-time use)
            Cache::forget("runpod_audio_upload_token:{$token}");

            Log::info('🎤 Audio saved successfully', [
                'path' => $filePath,
                'size' => strlen($audioContent),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audio uploaded successfully',
                'path' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('🎤 Audio upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the video wizard.
     */
    public function index(Request $request)
    {
        $projectId = $request->get('project');
        $project = null;

        if ($projectId) {
            $project = WizardProject::where('id', $projectId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('appvideowizard::index', [
            'project' => $project,
            'platforms' => config('appvideowizard.platforms'),
            'formats' => config('appvideowizard.formats'),
            'productionTypes' => config('appvideowizard.production_types'),
            'captionStyles' => config('appvideowizard.caption_styles'),
        ]);
    }

    /**
     * Display user's projects.
     */
    public function projects(Request $request)
    {
        $projects = WizardProject::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        return view('appvideowizard::projects', [
            'projects' => $projects,
        ]);
    }

    /**
     * Edit an existing project.
     */
    public function edit(Request $request, $id)
    {
        $project = WizardProject::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return redirect()->route('app.video-wizard.studio', ['project' => $project->id]);
    }

    /**
     * Delete a project.
     */
    public function destroy(Request $request, $id)
    {
        $project = WizardProject::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Delete associated assets
        foreach ($project->assets as $asset) {
            $asset->delete();
        }

        // Delete associated jobs
        $project->processingJobs()->delete();

        // Delete the project
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('app.video-wizard.projects')
            ->with('success', 'Project deleted successfully');
    }

    /**
     * Save project data.
     */
    public function saveProject(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'current_step' => 'nullable|integer|min:1|max:7',
            'platform' => 'nullable|string',
            'aspect_ratio' => 'nullable|string',
            'target_duration' => 'nullable|integer',
            'format' => 'nullable|string',
            'production_type' => 'nullable|string',
            'production_subtype' => 'nullable|string',
            'concept' => 'nullable|array',
            'character_intelligence' => 'nullable|array',
            'content_config' => 'nullable|array',
            'script' => 'nullable|array',
            'storyboard' => 'nullable|array',
            'animation' => 'nullable|array',
            'assembly' => 'nullable|array',
            'export_config' => 'nullable|array',
        ]);

        if (!empty($data['project_id'])) {
            $project = WizardProject::where('id', $data['project_id'])
                ->where('user_id', auth()->id())
                ->firstOrFail();

            unset($data['project_id']);

            // Update max_reached_step if current step is higher
            if (isset($data['current_step']) && $data['current_step'] > $project->max_reached_step) {
                $data['max_reached_step'] = $data['current_step'];
            }

            $project->update($data);
        } else {
            $project = WizardProject::create(array_merge($data, [
                'user_id' => auth()->id(),
                'team_id' => session('current_team_id'),
            ]));
        }

        return response()->json([
            'success' => true,
            'project' => $project->fresh(),
        ]);
    }

    /**
     * Load project data.
     */
    public function loadProject(Request $request, $id): JsonResponse
    {
        $project = WizardProject::where('id', $id)
            ->where('user_id', auth()->id())
            ->with(['assets'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'project' => $project,
        ]);
    }

    /**
     * Improve concept using AI.
     */
    public function improveConcept(Request $request, ConceptService $conceptService): JsonResponse
    {
        $data = $request->validate([
            'raw_input' => 'required|string|min:10',
            'production_type' => 'nullable|string',
            'production_subtype' => 'nullable|string',
        ]);

        try {
            $result = $conceptService->improveConcept($data['raw_input'], [
                'productionType' => $data['production_type'] ?? null,
                'productionSubType' => $data['production_subtype'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate script using AI.
     */
    public function generateScript(Request $request, ScriptGenerationService $scriptService): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
        ]);

        $project = WizardProject::where('id', $data['project_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Create processing job
        $job = WizardProcessingJob::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'type' => WizardProcessingJob::TYPE_SCRIPT_GENERATION,
            'status' => WizardProcessingJob::STATUS_PROCESSING,
        ]);

        try {
            $script = $scriptService->generateScript($project);

            // Update project with script
            $project->update(['script' => $script]);

            // Mark job as completed
            $job->markAsCompleted(['script' => $script]);

            return response()->json([
                'success' => true,
                'script' => $script,
                'job_id' => $job->id,
            ]);
        } catch (\Exception $e) {
            $job->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate image for a scene.
     */
    public function generateImage(Request $request, ImageGenerationService $imageService): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'scene_id' => 'required|string',
            'scene_index' => 'required|integer',
        ]);

        $project = WizardProject::where('id', $data['project_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $scenes = $project->getScenes();
        $scene = collect($scenes)->firstWhere('id', $data['scene_id']);

        if (!$scene) {
            return response()->json([
                'success' => false,
                'error' => 'Scene not found',
            ], 404);
        }

        try {
            $result = $imageService->generateSceneImage($project, $scene, [
                'sceneIndex' => $data['scene_index'],
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate voiceover for a scene.
     */
    public function generateVoiceover(Request $request, VoiceoverService $voiceoverService): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'scene_id' => 'required|string',
            'scene_index' => 'required|integer',
            'voice' => 'nullable|string',
            'speed' => 'nullable|numeric|min:0.5|max:2.0',
        ]);

        $project = WizardProject::where('id', $data['project_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $scenes = $project->getScenes();
        $scene = collect($scenes)->firstWhere('id', $data['scene_id']);

        if (!$scene) {
            return response()->json([
                'success' => false,
                'error' => 'Scene not found',
            ], 404);
        }

        try {
            $result = $voiceoverService->generateSceneVoiceover($project, $scene, [
                'sceneIndex' => $data['scene_index'],
                'voice' => $data['voice'] ?? 'nova',
                'speed' => $data['speed'] ?? 1.0,
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate animation for a scene (placeholder for future implementation).
     */
    public function generateAnimation(Request $request): JsonResponse
    {
        // TODO: Implement video animation generation
        // This would integrate with services like Runway, Pika, etc.

        return response()->json([
            'success' => false,
            'error' => 'Animation generation not yet implemented',
        ], 501);
    }

    /**
     * Start video export.
     */
    public function startExport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'quality' => 'nullable|string|in:720p,1080p,4k',
            'format' => 'nullable|string|in:mp4,webm',
        ]);

        $project = WizardProject::where('id', $data['project_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Create export job
        $job = WizardProcessingJob::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'type' => WizardProcessingJob::TYPE_VIDEO_EXPORT,
            'status' => WizardProcessingJob::STATUS_PENDING,
            'input_data' => [
                'quality' => $data['quality'] ?? '1080p',
                'format' => $data['format'] ?? 'mp4',
            ],
        ]);

        // TODO: Dispatch export job to queue
        // For now, return the job ID for status polling

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'message' => 'Export job created',
        ]);
    }

    /**
     * Get export job status.
     */
    public function exportStatus(Request $request, $jobId): JsonResponse
    {
        $job = WizardProcessingJob::where('id', $jobId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'status' => $job->status,
            'progress' => $job->progress,
            'current_stage' => $job->current_stage,
            'result' => $job->result_data,
            'error' => $job->error_message,
        ]);
    }
}
