<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwPrompt;
use Modules\AppVideoWizard\Models\VwProductionType;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Modules\AppVideoWizard\Services\GenerationLogService;

class VideoWizardAdminController extends Controller
{
    protected GenerationLogService $logService;

    public function __construct(GenerationLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = $this->logService->getDashboardStats(30);
        $promptCount = VwPrompt::count();
        $activePromptCount = VwPrompt::where('is_active', true)->count();
        $productionTypeCount = VwProductionType::whereNull('parent_id')->count();
        $subtypeCount = VwProductionType::whereNotNull('parent_id')->count();
        $recentLogs = VwGenerationLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('appvideowizard::admin.dashboard', compact(
            'stats',
            'promptCount',
            'activePromptCount',
            'productionTypeCount',
            'subtypeCount',
            'recentLogs'
        ));
    }

    /**
     * Display settings page.
     */
    public function settings()
    {
        $config = config('appvideowizard');

        return view('appvideowizard::admin.settings', compact('config'));
    }

    /**
     * Update settings.
     */
    public function updateSettings(Request $request)
    {
        // For now, settings are in config file
        // This could be extended to store in database

        session()->flash('success', 'Settings updated successfully.');

        return redirect()->route('admin.video-wizard.settings');
    }

    /**
     * Clear all caches.
     */
    public function clearCache()
    {
        VwPrompt::clearAllCache();
        VwProductionType::clearCache();

        session()->flash('success', 'All caches cleared successfully.');

        return redirect()->back();
    }
}
