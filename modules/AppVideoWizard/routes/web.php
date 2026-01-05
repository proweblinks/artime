<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\VideoWizardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->prefix('app/video-wizard')->name('app.video-wizard.')->group(function () {
    // Main wizard page
    Route::get('/', [VideoWizardController::class, 'index'])->name('index');

    // Project management
    Route::get('/projects', [VideoWizardController::class, 'projects'])->name('projects');
    Route::get('/project/{id}', [VideoWizardController::class, 'edit'])->name('edit');
    Route::delete('/project/{id}', [VideoWizardController::class, 'destroy'])->name('destroy');

    // API endpoints for wizard operations
    Route::prefix('api')->name('api.')->group(function () {
        // Project operations
        Route::post('/project/save', [VideoWizardController::class, 'saveProject'])->name('project.save');
        Route::get('/project/{id}', [VideoWizardController::class, 'loadProject'])->name('project.load');

        // AI operations
        Route::post('/improve-concept', [VideoWizardController::class, 'improveConcept'])->name('improve-concept');
        Route::post('/generate-script', [VideoWizardController::class, 'generateScript'])->name('generate-script');
        Route::post('/generate-image', [VideoWizardController::class, 'generateImage'])->name('generate-image');
        Route::post('/generate-voiceover', [VideoWizardController::class, 'generateVoiceover'])->name('generate-voiceover');
        Route::post('/generate-animation', [VideoWizardController::class, 'generateAnimation'])->name('generate-animation');

        // Export operations
        Route::post('/export/start', [VideoWizardController::class, 'startExport'])->name('export.start');
        Route::get('/export/status/{jobId}', [VideoWizardController::class, 'exportStatus'])->name('export.status');
    });
});
