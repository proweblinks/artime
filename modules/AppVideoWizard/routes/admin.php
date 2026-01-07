<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\Admin\VideoWizardAdminController;
use Modules\AppVideoWizard\Http\Controllers\Admin\PromptController;
use Modules\AppVideoWizard\Http\Controllers\Admin\ProductionTypeController;
use Modules\AppVideoWizard\Http\Controllers\Admin\GenerationLogController;

/*
|--------------------------------------------------------------------------
| Admin Routes for Video Wizard
|--------------------------------------------------------------------------
|
| Admin panel routes for managing Video Wizard settings, prompts,
| production types, and viewing generation logs.
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'admin/video-wizard'], function () {

        // Dashboard
        Route::get('/', [VideoWizardAdminController::class, 'index'])
            ->name('admin.video-wizard.index');

        // Prompts Management
        Route::prefix('prompts')->group(function () {
            Route::get('/', [PromptController::class, 'index'])
                ->name('admin.video-wizard.prompts.index');
            Route::get('/create', [PromptController::class, 'create'])
                ->name('admin.video-wizard.prompts.create');
            Route::post('/', [PromptController::class, 'store'])
                ->name('admin.video-wizard.prompts.store');
            Route::get('/{prompt}/edit', [PromptController::class, 'edit'])
                ->name('admin.video-wizard.prompts.edit');
            Route::put('/{prompt}', [PromptController::class, 'update'])
                ->name('admin.video-wizard.prompts.update');
            Route::delete('/{prompt}', [PromptController::class, 'destroy'])
                ->name('admin.video-wizard.prompts.destroy');
            Route::post('/{prompt}/toggle', [PromptController::class, 'toggle'])
                ->name('admin.video-wizard.prompts.toggle');
            Route::get('/{prompt}/history', [PromptController::class, 'history'])
                ->name('admin.video-wizard.prompts.history');
            Route::post('/{prompt}/rollback/{version}', [PromptController::class, 'rollback'])
                ->name('admin.video-wizard.prompts.rollback');
            Route::post('/{prompt}/test', [PromptController::class, 'test'])
                ->name('admin.video-wizard.prompts.test');
            Route::post('/seed-defaults', [PromptController::class, 'seedDefaults'])
                ->name('admin.video-wizard.prompts.seed-defaults');
        });

        // Production Types Management
        Route::prefix('production-types')->group(function () {
            Route::get('/', [ProductionTypeController::class, 'index'])
                ->name('admin.video-wizard.production-types.index');
            Route::get('/create', [ProductionTypeController::class, 'create'])
                ->name('admin.video-wizard.production-types.create');
            Route::post('/', [ProductionTypeController::class, 'store'])
                ->name('admin.video-wizard.production-types.store');
            Route::get('/{productionType}/edit', [ProductionTypeController::class, 'edit'])
                ->name('admin.video-wizard.production-types.edit');
            Route::put('/{productionType}', [ProductionTypeController::class, 'update'])
                ->name('admin.video-wizard.production-types.update');
            Route::delete('/{productionType}', [ProductionTypeController::class, 'destroy'])
                ->name('admin.video-wizard.production-types.destroy');
            Route::post('/{productionType}/toggle', [ProductionTypeController::class, 'toggle'])
                ->name('admin.video-wizard.production-types.toggle');
            Route::post('/reorder', [ProductionTypeController::class, 'reorder'])
                ->name('admin.video-wizard.production-types.reorder');
            Route::post('/seed-defaults', [ProductionTypeController::class, 'seedDefaults'])
                ->name('admin.video-wizard.production-types.seed-defaults');
        });

        // Generation Logs
        Route::prefix('logs')->group(function () {
            Route::get('/', [GenerationLogController::class, 'index'])
                ->name('admin.video-wizard.logs.index');
            Route::get('/analytics', [GenerationLogController::class, 'analytics'])
                ->name('admin.video-wizard.logs.analytics');
            Route::get('/export', [GenerationLogController::class, 'export'])
                ->name('admin.video-wizard.logs.export');
            Route::get('/{log}', [GenerationLogController::class, 'show'])
                ->name('admin.video-wizard.logs.show');
        });

        // Settings (credit costs, AI models, etc.)
        Route::get('/settings', [VideoWizardAdminController::class, 'settings'])
            ->name('admin.video-wizard.settings');
        Route::post('/settings', [VideoWizardAdminController::class, 'updateSettings'])
            ->name('admin.video-wizard.settings.update');

        // Clear caches
        Route::post('/clear-cache', [VideoWizardAdminController::class, 'clearCache'])
            ->name('admin.video-wizard.clear-cache');
    });
});
