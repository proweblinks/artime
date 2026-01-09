<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminBlogTags\Http\Controllers\AdminBlogTagsController;


Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "admin"], function () {
        Route::group(["prefix" => "blogs/tags"], function () {
            Route::resource('/', AdminBlogTagsController::class)->only(['index'])->names('admin.blogs.tags');
            Route::post('update', [AdminBlogTagsController::class, 'update'])->name('admin.blogtags.update');
            Route::post('save', [AdminBlogTagsController::class, 'save'])->name('admin.blogtags.save');
            Route::post('list', [AdminBlogTagsController::class, 'list'])->name('admin.blogtags.list');
            Route::post('destroy', [AdminBlogTagsController::class, 'destroy'])->name('admin.blogtags.destroy');
            Route::post('status/{any}', [AdminBlogTagsController::class, 'status'])->name('admin.blogtags.status');
        });
    });
});
