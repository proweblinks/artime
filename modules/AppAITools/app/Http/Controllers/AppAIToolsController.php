<?php

namespace Modules\AppAITools\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppAIToolsController extends Controller
{
    /**
     * Main tools hub page.
     */
    public function index()
    {
        return view('appaitools::index');
    }

    /**
     * Individual tool page.
     */
    public function tool(Request $request, string $tool = '')
    {
        $tool = $request->route()->defaults['tool'] ?? $tool;

        $toolMap = [
            'video-optimizer' => ['component' => 'video-optimizer', 'title' => 'Video Optimizer'],
            'competitor-analysis' => ['component' => 'competitor-analysis', 'title' => 'Competitor Analysis'],
            'trend-predictor' => ['component' => 'trend-predictor', 'title' => 'Trend Predictor'],
            'ai-thumbnails' => ['component' => 'ai-thumbnails', 'title' => 'AI Thumbnails'],
            'channel-audit' => ['component' => 'channel-audit', 'title' => 'Channel Audit Pro'],
            'more-tools' => ['component' => 'more-tools', 'title' => 'More AI Tools'],
        ];

        if (!isset($toolMap[$tool])) {
            abort(404);
        }

        return view('appaitools::tool', [
            'component' => $toolMap[$tool]['component'],
            'title' => $toolMap[$tool]['title'],
        ]);
    }

    /**
     * Sub-tool page (under More Tools).
     */
    public function subTool(Request $request, string $tool = '')
    {
        $tool = $request->route()->defaults['tool'] ?? $tool;

        $subToolMap = [
            'script-studio' => ['component' => 'script-studio', 'title' => 'Script Studio'],
            'viral-hook-lab' => ['component' => 'viral-hook-lab', 'title' => 'Viral Hook Lab'],
            'content-multiplier' => ['component' => 'content-multiplier', 'title' => 'Content Multiplier'],
            'thumbnail-arena' => ['component' => 'thumbnail-arena', 'title' => 'Thumbnail Arena'],
        ];

        if (!isset($subToolMap[$tool])) {
            abort(404);
        }

        return view('appaitools::tool', [
            'component' => $subToolMap[$tool]['component'],
            'title' => $subToolMap[$tool]['title'],
        ]);
    }
}
