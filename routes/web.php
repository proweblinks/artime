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

// Serve wizard videos from public folder (for cPanel compatibility)
// Using raw PHP streaming for maximum compatibility with nginx/cPanel
Route::get('/wizard-videos/{path}', function (string $path) {
    $fullPath = public_path('wizard-videos/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'Video not found');
    }

    $fileSize = filesize($fullPath);

    // Use streaming response for better nginx compatibility
    return response()->stream(function () use ($fullPath) {
        $stream = fopen($fullPath, 'rb');
        fpassthru($stream);
        fclose($stream);
    }, 200, [
        'Content-Type' => 'video/mp4',
        'Content-Length' => $fileSize,
        'Content-Disposition' => 'inline',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=2592000',
    ]);
})->where('path', '.*')->name('wizard-videos.serve');
