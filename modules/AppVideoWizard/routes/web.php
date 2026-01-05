<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\VideoWizardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "app"], function () {
        Route::group(["prefix" => "video-wizard"], function () {
            // Main wizard page
            Route::get('/', [VideoWizardController::class, 'index'])->name('app.video-wizard.index');

            // Project management
            Route::get('/projects', [VideoWizardController::class, 'projects'])->name('app.video-wizard.projects');
            Route::get('/project/{id}', [VideoWizardController::class, 'edit'])->name('app.video-wizard.edit');
            Route::delete('/project/{id}', [VideoWizardController::class, 'destroy'])->name('app.video-wizard.destroy');

            // API endpoints for wizard operations
            Route::post('/save', [VideoWizardController::class, 'saveProject'])->name('app.video-wizard.save');
            Route::get('/load/{id}', [VideoWizardController::class, 'loadProject'])->name('app.video-wizard.load');

            // AI operations
            Route::post('/improve-concept', [VideoWizardController::class, 'improveConcept'])->name('app.video-wizard.improve-concept');
            Route::post('/generate-script', [VideoWizardController::class, 'generateScript'])->name('app.video-wizard.generate-script');
            Route::post('/generate-image', [VideoWizardController::class, 'generateImage'])->name('app.video-wizard.generate-image');
            Route::post('/generate-voiceover', [VideoWizardController::class, 'generateVoiceover'])->name('app.video-wizard.generate-voiceover');
            Route::post('/generate-animation', [VideoWizardController::class, 'generateAnimation'])->name('app.video-wizard.generate-animation');

            // Export operations
            Route::post('/export/start', [VideoWizardController::class, 'startExport'])->name('app.video-wizard.export.start');
            Route::get('/export/status/{jobId}', [VideoWizardController::class, 'exportStatus'])->name('app.video-wizard.export.status');
        });
    });
});
