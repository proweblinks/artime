<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Modules\AppVideoWizard\Models\VwPrompt;
use Modules\AppVideoWizard\Services\GenerationLogService;

class GenerationLogController extends Controller
{
    protected GenerationLogService $logService;

    public function __construct(GenerationLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display list of generation logs.
     */
    public function index(Request $request)
    {
        // Validate filter inputs
        $request->validate([
            'prompt_slug' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:success,failed,partial'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        // Select only needed columns (exclude large JSON fields for performance)
        $query = VwGenerationLog::select([
            'id', 'project_id', 'user_id', 'prompt_slug', 'prompt_version',
            'tokens_used', 'duration_ms', 'status', 'error_message',
            'estimated_cost', 'created_at', 'updated_at'
        ])->with([
            'user:id,fullname,email',
            'project:id,name'
        ]);

        // Filters
        if ($request->filled('prompt_slug')) {
            $query->where('prompt_slug', $request->get('prompt_slug'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to') . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get prompts for filter dropdown (with fallback for empty table)
        try {
            $prompts = VwPrompt::orderBy('name')->pluck('name', 'slug');
        } catch (\Exception $e) {
            $prompts = collect();
        }
        $statuses = ['success' => 'Success', 'failed' => 'Failed', 'partial' => 'Partial'];

        return view('appvideowizard::admin.logs.index', compact('logs', 'prompts', 'statuses'));
    }

    /**
     * Show analytics dashboard.
     */
    public function analytics(Request $request)
    {
        $days = $request->get('days', 30);

        $stats = $this->logService->getDashboardStats($days);
        $byPrompt = $this->logService->getStatsByPrompt($days);
        $dailyStats = $this->logService->getDailyStats($days);
        $topUsers = $this->logService->getTopUsers($days, 10);
        $errors = $this->logService->getErrorAnalysis(7);
        $performance = $this->logService->getPerformanceMetrics(7);

        return view('appvideowizard::admin.logs.analytics', compact(
            'stats',
            'byPrompt',
            'dailyStats',
            'topUsers',
            'errors',
            'performance',
            'days'
        ));
    }

    /**
     * Show single log entry.
     */
    public function show(VwGenerationLog $log)
    {
        $log->load(['user', 'project']);

        return view('appvideowizard::admin.logs.show', compact('log'));
    }

    /**
     * Export logs to CSV.
     */
    public function export(Request $request)
    {
        $startDate = $request->get('date_from');
        $endDate = $request->get('date_to');

        $csv = $this->logService->exportToCsv($startDate, $endDate);

        $filename = 'video-wizard-logs-' . date('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Show performance dashboard.
     */
    public function performance(Request $request)
    {
        $days = $request->get('days', 7);

        $performance = $this->logService->getPerformanceMetrics($days);
        $stats = $this->logService->getDashboardStats($days);
        $byPrompt = $this->logService->getStatsByPrompt($days);
        $dailyStats = $this->logService->getDailyStats($days);

        return view('appvideowizard::admin.logs.performance', compact(
            'performance',
            'stats',
            'byPrompt',
            'dailyStats',
            'days'
        ));
    }
}
