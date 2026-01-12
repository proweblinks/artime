<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController;

/*
|--------------------------------------------------------------------------
| Web Routes - Using EXACT AppProfile pattern with nested groups
|--------------------------------------------------------------------------
*/

// Public route to serve wizard videos (no auth required)
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
