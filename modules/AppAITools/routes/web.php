<?php

use Illuminate\Support\Facades\Route;
use Modules\AppAITools\Http\Controllers\AppAIToolsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/ai-tools'], function () {
        // Main tools hub
        Route::get('/', [AppAIToolsController::class, 'index'])->name('app.ai-tools.index');

        // Individual tools
        Route::get('/video-optimizer', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.video-optimizer')->defaults('tool', 'video-optimizer');
        Route::get('/competitor-analysis', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.competitor-analysis')->defaults('tool', 'competitor-analysis');
        Route::get('/trend-predictor', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.trend-predictor')->defaults('tool', 'trend-predictor');
        Route::get('/ai-thumbnails', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.ai-thumbnails')->defaults('tool', 'ai-thumbnails');
        Route::get('/channel-audit', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.channel-audit')->defaults('tool', 'channel-audit');

        // More tools sub-hub
        Route::get('/more-tools', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.more-tools')->defaults('tool', 'more-tools');
        Route::get('/more-tools/script-studio', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.script-studio')->defaults('tool', 'script-studio');
        Route::get('/more-tools/viral-hooks', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.viral-hooks')->defaults('tool', 'viral-hook-lab');
        Route::get('/more-tools/content-multiplier', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.content-multiplier')->defaults('tool', 'content-multiplier');
        Route::get('/more-tools/thumbnail-arena', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.thumbnail-arena')->defaults('tool', 'thumbnail-arena');
    });
});
