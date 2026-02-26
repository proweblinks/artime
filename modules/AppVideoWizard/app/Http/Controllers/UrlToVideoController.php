<?php

namespace Modules\AppVideoWizard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\UrlToVideoProject;

class UrlToVideoController extends Controller
{
    public function index(Request $request)
    {
        if (!get_option('url_to_video_enabled', 1)) {
            abort(404);
        }

        return view('appvideowizard::url-to-video.index');
    }

    public function show(Request $request, $id)
    {
        $project = UrlToVideoProject::with('user')->findOrFail($id);

        return view('appvideowizard::url-to-video.index', [
            'sharedProject' => $project,
        ]);
    }
}
