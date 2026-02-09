<?php

use Illuminate\Support\Facades\Route;
use Modules\AppAITools\Http\Controllers\Admin\CreatorHubSettingsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'admin/creator-hub'], function () {
        // Settings page
        Route::get('/settings', [CreatorHubSettingsController::class, 'index'])->name('admin.creator-hub.settings');

        // YouTube API keys
        Route::post('/settings/youtube-keys', [CreatorHubSettingsController::class, 'saveYouTubeKeys'])->name('admin.creator-hub.youtube-keys.save');
        Route::post('/settings/youtube-keys/test', [CreatorHubSettingsController::class, 'testYouTubeKey'])->name('admin.creator-hub.youtube-keys.test');

        // General settings
        Route::post('/settings/general', [CreatorHubSettingsController::class, 'saveGeneral'])->name('admin.creator-hub.settings.general');
    });
});
