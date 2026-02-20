<?php

use Illuminate\Support\Facades\Route;
use Modules\AppAnalytics\Http\Controllers\AppAnalyticsController;

Route::middleware(['web', 'auth'])->group(function () {
    // Client analytics dashboard
    Route::group(["prefix" => "app"], function () {
        Route::get('analytics', [AppAnalyticsController::class, 'index'])->name('app.analytics');
    });

    // Admin settings
    Route::group(["prefix" => "admin/api-integration"], function () {
        Route::get('analytics', [AppAnalyticsController::class, 'settings'])->name('admin.analytics.settings');
    });
});
