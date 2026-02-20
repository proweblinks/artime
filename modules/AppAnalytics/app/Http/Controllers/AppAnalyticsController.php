<?php

namespace Modules\AppAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;

class AppAnalyticsController extends Controller
{
    public function index()
    {
        return view('appanalytics::index');
    }

    public function settings()
    {
        return view('appanalytics::settings');
    }
}
