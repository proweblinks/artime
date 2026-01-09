<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Processor Service (Cloud Run)
    |--------------------------------------------------------------------------
    |
    | Configuration for the Cloud Run video processor service.
    | Handles FFmpeg processing, Ken Burns effects, and video rendering.
    |
    */
    'video_processor' => [
        'url' => env('VIDEO_PROCESSOR_URL', 'https://video-processor-xxxxx.us-central1.run.app'),
        'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'ytseo-6d1b0.firebasestorage.app'),
        'temp_dir' => env('VIDEO_PROCESSOR_TEMP_DIR', '/tmp/video-processing'),
        'timeout' => env('VIDEO_PROCESSOR_TIMEOUT', 900), // 15 minutes
        'parallel_scenes' => env('VIDEO_PROCESSOR_PARALLEL', false),
        'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Platform
    |--------------------------------------------------------------------------
    */
    'google_cloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'key_file' => env('GOOGLE_CLOUD_KEY_FILE'),
        'region' => env('GOOGLE_CLOUD_REGION', 'us-central1'),
    ],

];
