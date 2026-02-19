<?php

use Illuminate\Support\Facades\Route;
use Modules\AppAIContents\Http\Controllers\AppAIContentsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "app"], function () {
        Route::group(["prefix" => "ai-contents"], function () {
            // Main Content Studio (Livewire SPA)
            Route::get('/', [AppAIContentsController::class, 'index'])->name('app.ai-contents.index');

            // Legacy popup routes (used by other modules like Captions)
            Route::post('popup-ai-content', [AppAIContentsController::class, 'popupAIContent'])->name('app.ai-contents.popupAIContent');
            Route::post('process', [AppAIContentsController::class, 'process'])->name('app.ai-contents.process');
            Route::post('process/{any}', [AppAIContentsController::class, 'process']);
            Route::post('categories', [AppAIContentsController::class, 'categories'])->name('app.ai-contents.categories');
            Route::post('templates', [AppAIContentsController::class, 'templates'])->name('app.ai-contents.templates');
            Route::post('create-content', [AppAIContentsController::class, 'createContent'])->name('app.ai-contents.create_content');
        });
    });
});
