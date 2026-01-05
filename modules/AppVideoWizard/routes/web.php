<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController;

/*
|--------------------------------------------------------------------------
| Web Routes - All routes OUTSIDE nested groups (workaround)
|--------------------------------------------------------------------------
*/

// Main wizard page - OUTSIDE nested groups with full explicit path
Route::middleware(['web', 'auth'])->get('app/video-wizard/create', [AppVideoWizardController::class, 'index'])->name('app.video-wizard.create');

// Redirect base path
Route::middleware(['web', 'auth'])->get('app/video-wizard', function() {
    return redirect()->route('app.video-wizard.create');
})->name('app.video-wizard.index');

// Project management - explicit paths
Route::middleware(['web', 'auth'])->get('app/video-wizard/projects', [AppVideoWizardController::class, 'projects'])->name('app.video-wizard.projects');
Route::middleware(['web', 'auth'])->get('app/video-wizard/project/{id}', [AppVideoWizardController::class, 'edit'])->name('app.video-wizard.edit');
Route::middleware(['web', 'auth'])->delete('app/video-wizard/project/{id}', [AppVideoWizardController::class, 'destroy'])->name('app.video-wizard.destroy');

// API endpoints
Route::middleware(['web', 'auth'])->post('app/video-wizard/save', [AppVideoWizardController::class, 'saveProject'])->name('app.video-wizard.save');
Route::middleware(['web', 'auth'])->get('app/video-wizard/load/{id}', [AppVideoWizardController::class, 'loadProject'])->name('app.video-wizard.load');

// AI operations
Route::middleware(['web', 'auth'])->post('app/video-wizard/improve-concept', [AppVideoWizardController::class, 'improveConcept'])->name('app.video-wizard.improve-concept');
Route::middleware(['web', 'auth'])->post('app/video-wizard/generate-script', [AppVideoWizardController::class, 'generateScript'])->name('app.video-wizard.generate-script');
Route::middleware(['web', 'auth'])->post('app/video-wizard/generate-image', [AppVideoWizardController::class, 'generateImage'])->name('app.video-wizard.generate-image');
Route::middleware(['web', 'auth'])->post('app/video-wizard/generate-voiceover', [AppVideoWizardController::class, 'generateVoiceover'])->name('app.video-wizard.generate-voiceover');
Route::middleware(['web', 'auth'])->post('app/video-wizard/generate-animation', [AppVideoWizardController::class, 'generateAnimation'])->name('app.video-wizard.generate-animation');

// Export operations
Route::middleware(['web', 'auth'])->post('app/video-wizard/export/start', [AppVideoWizardController::class, 'startExport'])->name('app.video-wizard.export.start');
Route::middleware(['web', 'auth'])->get('app/video-wizard/export/status/{jobId}', [AppVideoWizardController::class, 'exportStatus'])->name('app.video-wizard.export.status');
