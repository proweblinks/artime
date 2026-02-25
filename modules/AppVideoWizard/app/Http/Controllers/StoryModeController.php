<?php

namespace Modules\AppVideoWizard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\StoryModeProject;
use Modules\AppVideoWizard\Models\StoryModeStyle;

class StoryModeController extends Controller
{
    /**
     * Display the Story Mode main page.
     */
    public function index(Request $request)
    {
        // Check if Story Mode is enabled
        if (!get_option('story_mode_enabled', 1)) {
            abort(404);
        }

        $styles = StoryModeStyle::active()
            ->orderBy('sort_order')
            ->get();

        $recentProjects = StoryModeProject::forUser(auth()->id())
            ->with('style')
            ->orderBy('updated_at', 'desc')
            ->limit(12)
            ->get();

        $galleryProjects = StoryModeProject::ready()
            ->with('style')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return view('appvideowizard::story-mode.index', [
            'styles' => $styles,
            'recentProjects' => $recentProjects,
            'galleryProjects' => $galleryProjects,
        ]);
    }

    /**
     * Display a specific Story Mode project (for shared links).
     */
    public function show(Request $request, $id)
    {
        $project = StoryModeProject::with('style', 'user')
            ->findOrFail($id);

        return view('appvideowizard::story-mode.index', [
            'styles' => StoryModeStyle::active()->orderBy('sort_order')->get(),
            'recentProjects' => collect(),
            'galleryProjects' => collect(),
            'sharedProject' => $project,
        ]);
    }
}
