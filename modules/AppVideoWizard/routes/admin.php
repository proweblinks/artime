<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\Admin\VideoWizardAdminController;
use Modules\AppVideoWizard\Http\Controllers\Admin\PromptController;
use Modules\AppVideoWizard\Http\Controllers\Admin\ProductionTypeController;
use Modules\AppVideoWizard\Http\Controllers\Admin\GenerationLogController;
use Modules\AppVideoWizard\Http\Controllers\Admin\NarrativeStructureController;
use Modules\AppVideoWizard\Http\Controllers\Admin\CinematographyController;
use Modules\AppVideoWizard\Http\Controllers\Admin\GenrePresetController;
use Modules\AppVideoWizard\Http\Controllers\Admin\CameraMovementController;
use Modules\AppVideoWizard\Http\Controllers\Admin\CoveragePatternController;
use Modules\AppVideoWizard\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Admin Routes for Video Wizard
|--------------------------------------------------------------------------
|
| Admin panel routes for managing Video Wizard settings, prompts,
| production types, cinematography settings, and viewing generation logs.
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

        // Narrative Structures Management (Hollywood-level script generation)
        Route::prefix('narrative')->group(function () {
            Route::get('/', [NarrativeStructureController::class, 'index'])
                ->name('admin.video-wizard.narrative.index');
            Route::get('/story-arcs', [NarrativeStructureController::class, 'storyArcs'])
                ->name('admin.video-wizard.narrative.story-arcs');
            Route::get('/presets', [NarrativeStructureController::class, 'presets'])
                ->name('admin.video-wizard.narrative.presets');
            Route::get('/tension-curves', [NarrativeStructureController::class, 'tensionCurves'])
                ->name('admin.video-wizard.narrative.tension-curves');
            Route::get('/emotional-journeys', [NarrativeStructureController::class, 'emotionalJourneys'])
                ->name('admin.video-wizard.narrative.emotional-journeys');
            Route::post('/update-settings', [NarrativeStructureController::class, 'updateSettings'])
                ->name('admin.video-wizard.narrative.update-settings');
            Route::post('/toggle', [NarrativeStructureController::class, 'toggle'])
                ->name('admin.video-wizard.narrative.toggle');
            Route::get('/export', [NarrativeStructureController::class, 'export'])
                ->name('admin.video-wizard.narrative.export');
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

        // =============================================
        // PROFESSIONAL CINEMATOGRAPHY SYSTEM
        // =============================================
        Route::prefix('cinematography')->group(function () {
            // Dashboard
            Route::get('/', [CinematographyController::class, 'index'])
                ->name('admin.video-wizard.cinematography.index');

            // Genre Presets (full CRUD)
            Route::prefix('genre-presets')->group(function () {
                Route::get('/', [GenrePresetController::class, 'index'])
                    ->name('admin.video-wizard.cinematography.genre-presets.index');
                Route::get('/create', [GenrePresetController::class, 'create'])
                    ->name('admin.video-wizard.cinematography.genre-presets.create');
                Route::post('/', [GenrePresetController::class, 'store'])
                    ->name('admin.video-wizard.cinematography.genre-presets.store');
                Route::get('/{genrePreset}/edit', [GenrePresetController::class, 'edit'])
                    ->name('admin.video-wizard.cinematography.genre-presets.edit');
                Route::put('/{genrePreset}', [GenrePresetController::class, 'update'])
                    ->name('admin.video-wizard.cinematography.genre-presets.update');
                Route::delete('/{genrePreset}', [GenrePresetController::class, 'destroy'])
                    ->name('admin.video-wizard.cinematography.genre-presets.destroy');
                Route::post('/{genrePreset}/toggle', [GenrePresetController::class, 'toggle'])
                    ->name('admin.video-wizard.cinematography.genre-presets.toggle');
                Route::post('/{genrePreset}/clone', [GenrePresetController::class, 'clone'])
                    ->name('admin.video-wizard.cinematography.genre-presets.clone');
                Route::post('/reorder', [GenrePresetController::class, 'reorder'])
                    ->name('admin.video-wizard.cinematography.genre-presets.reorder');
                Route::get('/export', [GenrePresetController::class, 'export'])
                    ->name('admin.video-wizard.cinematography.genre-presets.export');
                Route::post('/import', [GenrePresetController::class, 'import'])
                    ->name('admin.video-wizard.cinematography.genre-presets.import');
                Route::get('/{genrePreset}/preview', [GenrePresetController::class, 'preview'])
                    ->name('admin.video-wizard.cinematography.genre-presets.preview');
            });

            // Shot Types (50+ types)
            Route::get('/shot-types', [CinematographyController::class, 'shotTypes'])
                ->name('admin.video-wizard.cinematography.shot-types');
            Route::get('/shot-types/{shotType}/edit', [CinematographyController::class, 'editShotType'])
                ->name('admin.video-wizard.cinematography.shot-types.edit');
            Route::put('/shot-types/{shotType}', [CinematographyController::class, 'updateShotType'])
                ->name('admin.video-wizard.cinematography.shot-types.update');
            Route::post('/shot-types/{shotType}/toggle', [CinematographyController::class, 'toggleShotType'])
                ->name('admin.video-wizard.cinematography.shot-types.toggle');

            // Emotional Beats (Three-Act Structure)
            Route::get('/emotional-beats', [CinematographyController::class, 'emotionalBeats'])
                ->name('admin.video-wizard.cinematography.emotional-beats');
            Route::post('/emotional-beats/{emotionalBeat}/toggle', [CinematographyController::class, 'toggleEmotionalBeat'])
                ->name('admin.video-wizard.cinematography.emotional-beats.toggle');

            // Story Structures (Hero's Journey, etc.)
            Route::get('/story-structures', [CinematographyController::class, 'storyStructures'])
                ->name('admin.video-wizard.cinematography.story-structures');
            Route::post('/story-structures/{storyStructure}/toggle', [CinematographyController::class, 'toggleStoryStructure'])
                ->name('admin.video-wizard.cinematography.story-structures.toggle');
            Route::post('/story-structures/{storyStructure}/set-default', [CinematographyController::class, 'setDefaultStructure'])
                ->name('admin.video-wizard.cinematography.story-structures.set-default');

            // Camera Specs (Lenses, Film Stocks)
            Route::get('/camera-specs', [CinematographyController::class, 'cameraSpecs'])
                ->name('admin.video-wizard.cinematography.camera-specs');
            Route::post('/camera-specs/{cameraSpec}/toggle', [CinematographyController::class, 'toggleCameraSpec'])
                ->name('admin.video-wizard.cinematography.camera-specs.toggle');

            // Camera Movements (Motion Intelligence)
            Route::prefix('camera-movements')->group(function () {
                Route::get('/', [CameraMovementController::class, 'index'])
                    ->name('admin.video-wizard.cinematography.camera-movements');
                Route::get('/create', [CameraMovementController::class, 'create'])
                    ->name('admin.video-wizard.cinematography.camera-movements.create');
                Route::post('/', [CameraMovementController::class, 'store'])
                    ->name('admin.video-wizard.cinematography.camera-movements.store');
                Route::get('/{cameraMovement}/edit', [CameraMovementController::class, 'edit'])
                    ->name('admin.video-wizard.cinematography.camera-movements.edit');
                Route::put('/{cameraMovement}', [CameraMovementController::class, 'update'])
                    ->name('admin.video-wizard.cinematography.camera-movements.update');
                Route::delete('/{cameraMovement}', [CameraMovementController::class, 'destroy'])
                    ->name('admin.video-wizard.cinematography.camera-movements.destroy');
                Route::post('/{cameraMovement}/toggle', [CameraMovementController::class, 'toggle'])
                    ->name('admin.video-wizard.cinematography.camera-movements.toggle');
                Route::post('/{cameraMovement}/test-prompt', [CameraMovementController::class, 'testPrompt'])
                    ->name('admin.video-wizard.cinematography.camera-movements.test-prompt');
                Route::get('/{cameraMovement}/stackable', [CameraMovementController::class, 'getStackable'])
                    ->name('admin.video-wizard.cinematography.camera-movements.stackable');
                Route::post('/reorder', [CameraMovementController::class, 'reorder'])
                    ->name('admin.video-wizard.cinematography.camera-movements.reorder');
                Route::get('/export', [CameraMovementController::class, 'export'])
                    ->name('admin.video-wizard.cinematography.camera-movements.export');
                Route::post('/import', [CameraMovementController::class, 'import'])
                    ->name('admin.video-wizard.cinematography.camera-movements.import');
                Route::post('/seed-defaults', [CameraMovementController::class, 'seedDefaults'])
                    ->name('admin.video-wizard.cinematography.camera-movements.seed-defaults');
            });

            // Shot Continuity (Phase 3)
            Route::get('/continuity', [CinematographyController::class, 'continuity'])
                ->name('admin.video-wizard.cinematography.continuity');

            // Coverage Patterns (Phase 4: Scene Type Detection)
            Route::prefix('coverage-patterns')->group(function () {
                Route::get('/', [CoveragePatternController::class, 'index'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.index');
                Route::get('/create', [CoveragePatternController::class, 'create'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.create');
                Route::post('/', [CoveragePatternController::class, 'store'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.store');
                Route::get('/{coveragePattern}/edit', [CoveragePatternController::class, 'edit'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.edit');
                Route::put('/{coveragePattern}', [CoveragePatternController::class, 'update'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.update');
                Route::delete('/{coveragePattern}', [CoveragePatternController::class, 'destroy'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.destroy');
                Route::post('/{coveragePattern}/toggle', [CoveragePatternController::class, 'toggle'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.toggle');
                Route::get('/export', [CoveragePatternController::class, 'export'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.export');
                Route::post('/seed-defaults', [CoveragePatternController::class, 'seedDefaults'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.seed-defaults');
                Route::post('/test-detection', [CoveragePatternController::class, 'testDetection'])
                    ->name('admin.video-wizard.cinematography.coverage-patterns.test-detection');
            });

            // Bulk Operations
            Route::post('/clear-caches', [CinematographyController::class, 'clearCaches'])
                ->name('admin.video-wizard.cinematography.clear-caches');
            Route::get('/export-all', [CinematographyController::class, 'exportAll'])
                ->name('admin.video-wizard.cinematography.export-all');
        });

        // Settings (credit costs, AI models, etc.) - Legacy static settings
        Route::get('/settings', [VideoWizardAdminController::class, 'settings'])
            ->name('admin.video-wizard.settings');
        Route::post('/settings', [VideoWizardAdminController::class, 'updateSettings'])
            ->name('admin.video-wizard.settings.update');

        // =============================================
        // DYNAMIC SETTINGS (Shot Intelligence, Animation, etc.)
        // =============================================
        Route::prefix('dynamic-settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])
                ->name('admin.video-wizard.dynamic-settings.index');
            Route::post('/', [SettingsController::class, 'update'])
                ->name('admin.video-wizard.dynamic-settings.update');
            Route::post('/reset-category/{category}', [SettingsController::class, 'resetCategory'])
                ->name('admin.video-wizard.dynamic-settings.reset-category');
            Route::post('/reset-all', [SettingsController::class, 'resetAll'])
                ->name('admin.video-wizard.dynamic-settings.reset-all');
            Route::post('/{setting}/toggle', [SettingsController::class, 'toggle'])
                ->name('admin.video-wizard.dynamic-settings.toggle');
            Route::post('/seed-defaults', [SettingsController::class, 'seedDefaults'])
                ->name('admin.video-wizard.dynamic-settings.seed-defaults');

            // API endpoints for AJAX
            Route::get('/json', [SettingsController::class, 'getJson'])
                ->name('admin.video-wizard.dynamic-settings.json');
            Route::post('/{setting}/update-single', [SettingsController::class, 'updateSingle'])
                ->name('admin.video-wizard.dynamic-settings.update-single');
        });

        // Clear caches
        Route::post('/clear-cache', [VideoWizardAdminController::class, 'clearCache'])
            ->name('admin.video-wizard.clear-cache');
    });
});
