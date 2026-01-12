<?php
/**
 * Direct video serving script for cPanel
 * Place this file in public_html (document root)
 * URL: /video.php?path=1/112/scene_0_shot_0_1768230184.mp4
 */

$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    die('Missing path parameter');
}

// Sanitize path to prevent directory traversal
$path = str_replace(['..', "\0"], '', $path);

// On cPanel, videos are stored in public_html/public/wizard-videos/
$fullPath = __DIR__ . '/public/wizard-videos/' . $path;

if (!file_exists($fullPath)) {
    http_response_code(404);
    die('Video not found: ' . $path);
}

if (!is_file($fullPath)) {
    http_response_code(400);
    die('Invalid file');
}

$fileSize = filesize($fullPath);

// Clear any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Send headers
header('Content-Type: video/mp4');
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=2592000');

// Stream the file
readfile($fullPath);
exit;
