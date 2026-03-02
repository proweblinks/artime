<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Process queued jobs (video rendering, image generation, etc.)
Schedule::command('queue:work database --queue=video-wizard-images,default --stop-when-empty --timeout=600 --memory=512')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
