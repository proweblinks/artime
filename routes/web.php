<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Storage File Serving Route
|--------------------------------------------------------------------------
| This route serves files from storage when nginx doesn't handle the symlink.
| This is a fallback - ideally nginx should serve /storage directly.
*/
// Primary route using /files/ path (bypasses nginx /storage handling)
Route::get('/files/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'File not found');
    }

    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=2592000',
    ]);
})->where('path', '.*')->name('files.serve');

// Fallback: also handle /storage/ in case nginx passes it through
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'File not found');
    }

    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=2592000',
    ]);
})->where('path', '.*')->name('storage.serve');
