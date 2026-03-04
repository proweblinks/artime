<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController;
use Modules\AppVideoWizard\Http\Controllers\StockMediaReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public route for RunPod to upload generated videos (no auth, uses signed token)
// Using API routes to bypass CSRF protection
Route::put('/runpod/video-upload/{token}', [AppVideoWizardController::class, 'runpodVideoUpload'])
    ->name('api.runpod.video-upload');

// Public route for RunPod Kokoro TTS to upload generated audio (no auth, uses signed token)
Route::put('/runpod/audio-upload/{token}', [AppVideoWizardController::class, 'runpodAudioUpload'])
    ->name('api.runpod.audio-upload');

// Report stock media (public, one report per IP per item)
Route::post('/stock-media/{stockMedia}/report', [StockMediaReportController::class, 'report'])
    ->middleware('throttle:5,1')
    ->name('api.stock-media.report');
