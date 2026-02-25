<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController;
use Modules\AppVideoWizard\Http\Controllers\StoryModeController;

/*
|--------------------------------------------------------------------------
| Web Routes - Using EXACT AppProfile pattern with nested groups
|--------------------------------------------------------------------------
*/

// RunPod video upload route is defined in api.php (no CSRF protection)

// DEBUG: Test route to check video paths
Route::get('/wizard-videos-debug/{projectId}', function ($projectId) {
    $basePath = public_path("wizard-videos/{$projectId}");
    $files = is_dir($basePath) ? scandir($basePath) : [];
    $fileDetails = [];

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = "{$basePath}/{$file}";
            $fileDetails[] = [
                'name' => $file,
                'size' => filesize($fullPath),
                'path' => $fullPath,
                'readable' => is_readable($fullPath),
            ];
        }
    }

    return response()->json([
        'public_path_base' => public_path(),
        'wizard_videos_path' => $basePath,
        'dir_exists' => is_dir($basePath),
        'files' => $fileDetails,
    ]);
});

// Public route to serve wizard videos by project ID only (for Multitalk uploads)
Route::get('/wizard-videos/{projectId}/{filename}', function ($projectId, $filename) {
    \Log::info('🎥 Video serve route hit', [
        'projectId' => $projectId,
        'filename' => $filename,
        'public_path' => public_path("wizard-videos/{$projectId}/{$filename}"),
    ]);

    $path = public_path("wizard-videos/{$projectId}/{$filename}");

    if (!file_exists($path)) {
        \Log::error('🎥 Video file NOT FOUND', ['path' => $path]);
        abort(404, 'Video not found');
    }

    \Log::info('🎥 Serving video file', ['path' => $path, 'size' => filesize($path)]);

    return response()->file($path, [
        'Content-Type' => 'video/mp4',
        'Content-Disposition' => 'inline',
        'Accept-Ranges' => 'bytes',
    ]);
})->where('filename', '[A-Za-z0-9_\-\.]+\.mp4')->name('wizard-video.serve-simple');

// Public route to serve wizard videos with userId (legacy format)
// This is needed because cPanel/nginx routes everything through PHP
Route::get('/wizard-videos/{userId}/{projectId}/{filename}', function ($userId, $projectId, $filename) {
    $path = public_path("wizard-videos/{$userId}/{$projectId}/{$filename}");

    if (!file_exists($path)) {
        abort(404, 'Video not found');
    }

    return response()->file($path, [
        'Content-Type' => 'video/mp4',
        'Content-Disposition' => 'inline',
        'Accept-Ranges' => 'bytes',
    ]);
})->where('filename', '.*\.mp4$')->name('wizard-video.serve');

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "app"], function () {

        // Story Mode routes
        Route::get('/story-mode', [StoryModeController::class, 'index'])->name('app.story-mode');
        Route::get('/story-mode/{id}', [StoryModeController::class, 'show'])->name('app.story-mode.show');

        Route::group(["prefix" => "video-wizard"], function () {
            // Main wizard page - renamed from 'create' to 'studio' for testing
            Route::get('/studio', [AppVideoWizardController::class, 'index'])->name('app.video-wizard.studio');

            // Base path redirects to studio
            Route::get('/', function() {
                return redirect()->route('app.video-wizard.studio');
            })->name('app.video-wizard.index');

            // Projects page (this one works!)
            Route::get('/projects', [AppVideoWizardController::class, 'projects'])->name('app.video-wizard.projects');

            // Project management
            Route::get('/project/{id}', [AppVideoWizardController::class, 'edit'])->name('app.video-wizard.edit');
            Route::delete('/project/{id}', [AppVideoWizardController::class, 'destroy'])->name('app.video-wizard.destroy');

            // API endpoints
            Route::post('/save', [AppVideoWizardController::class, 'saveProject'])->name('app.video-wizard.save');
            Route::get('/load/{id}', [AppVideoWizardController::class, 'loadProject'])->name('app.video-wizard.load');

            // AI operations
            Route::post('/improve-concept', [AppVideoWizardController::class, 'improveConcept'])->name('app.video-wizard.improve-concept');
            Route::post('/generate-script', [AppVideoWizardController::class, 'generateScript'])->name('app.video-wizard.generate-script');
            Route::post('/generate-image', [AppVideoWizardController::class, 'generateImage'])->name('app.video-wizard.generate-image');
            Route::post('/generate-voiceover', [AppVideoWizardController::class, 'generateVoiceover'])->name('app.video-wizard.generate-voiceover');
            Route::post('/generate-animation', [AppVideoWizardController::class, 'generateAnimation'])->name('app.video-wizard.generate-animation');

            // Export operations
            Route::post('/export/start', [AppVideoWizardController::class, 'startExport'])->name('app.video-wizard.export.start');
            Route::get('/export/status/{jobId}', [AppVideoWizardController::class, 'exportStatus'])->name('app.video-wizard.export.status');
        });
    });
});
