<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Collection;

class GenerationLogService
{
    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $logs = VwGenerationLog::where('created_at', '>=', $startDate);

        return [
            'total_generations' => (clone $logs)->count(),
            'successful' => (clone $logs)->where('status', 'success')->count(),
            'failed' => (clone $logs)->where('status', 'failed')->count(),
            'success_rate' => $this->calculateSuccessRate($logs->get()),
            'total_tokens' => (clone $logs)->sum('tokens_used') ?? 0,
            'total_cost' => (clone $logs)->sum('estimated_cost') ?? 0,
            'avg_duration_ms' => round((clone $logs)->avg('duration_ms') ?? 0),
        ];
    }

    /**
     * Get generation statistics by prompt.
     */
    public function getStatsByPrompt(int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return VwGenerationLog::where('created_at', '>=', $startDate)
            ->selectRaw('prompt_slug,
                COUNT(*) as total_count,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
                SUM(tokens_used) as total_tokens,
                SUM(estimated_cost) as total_cost,
                AVG(duration_ms) as avg_duration')
            ->groupBy('prompt_slug')
            ->orderBy('total_count', 'desc')
            ->get()
            ->map(function ($stat) {
                $stat->success_rate = $stat->total_count > 0
                    ? round(($stat->success_count / $stat->total_count) * 100, 1)
                    : 0;
                return $stat;
            });
    }

    /**
     * Get daily generation counts for chart.
     */
    public function getDailyStats(int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return VwGenerationLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(tokens_used) as tokens,
                SUM(estimated_cost) as cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get recent logs with pagination.
     */
    public function getRecentLogs(
        int $perPage = 20,
        ?string $promptSlug = null,
        ?string $status = null,
        ?int $userId = null
    ) {
        $query = VwGenerationLog::with(['user', 'project'])
            ->orderBy('created_at', 'desc');

        if ($promptSlug) {
            $query->where('prompt_slug', $promptSlug);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get cost breakdown by period.
     */
    public function getCostBreakdown(string $period = 'day', int $limit = 30): Collection
    {
        $groupBy = match ($period) {
            'hour' => 'DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00")',
            'day' => 'DATE(created_at)',
            'week' => 'YEARWEEK(created_at)',
            'month' => 'DATE_FORMAT(created_at, "%Y-%m")',
            default => 'DATE(created_at)',
        };

        return VwGenerationLog::selectRaw("{$groupBy} as period,
                SUM(estimated_cost) as cost,
                SUM(tokens_used) as tokens,
                COUNT(*) as generations")
            ->groupByRaw($groupBy)
            ->orderByRaw("{$groupBy} DESC")
            ->limit($limit)
            ->get();
    }

    /**
     * Get top users by generation count.
     */
    public function getTopUsers(int $days = 30, int $limit = 10): Collection
    {
        $startDate = now()->subDays($days);

        return VwGenerationLog::where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->with('user:id,fullname,email')
            ->selectRaw('user_id,
                COUNT(*) as generation_count,
                SUM(tokens_used) as total_tokens,
                SUM(estimated_cost) as total_cost')
            ->groupBy('user_id')
            ->orderBy('generation_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error analysis.
     */
    public function getErrorAnalysis(int $days = 7): Collection
    {
        $startDate = now()->subDays($days);

        return VwGenerationLog::where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->selectRaw('prompt_slug, error_message, COUNT(*) as count')
            ->groupBy('prompt_slug', 'error_message')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Calculate success rate from a collection of logs.
     */
    protected function calculateSuccessRate(Collection $logs): float
    {
        $total = $logs->count();

        if ($total === 0) {
            return 100.0;
        }

        $successful = $logs->where('status', 'success')->count();

        return round(($successful / $total) * 100, 1);
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $logs = VwGenerationLog::where('created_at', '>=', $startDate)->get();

        $durations = $logs->pluck('duration_ms')->filter();

        return [
            'avg_duration' => round($durations->avg() ?? 0),
            'min_duration' => $durations->min() ?? 0,
            'max_duration' => $durations->max() ?? 0,
            'p50_duration' => $this->percentile($durations, 50),
            'p95_duration' => $this->percentile($durations, 95),
            'p99_duration' => $this->percentile($durations, 99),
        ];
    }

    /**
     * Calculate percentile value.
     */
    protected function percentile(Collection $values, int $percentile): int
    {
        if ($values->isEmpty()) {
            return 0;
        }

        $sorted = $values->sort()->values();
        $index = (int) ceil(($percentile / 100) * $sorted->count()) - 1;
        $index = max(0, min($index, $sorted->count() - 1));

        return (int) $sorted[$index];
    }

    /**
     * Export logs to CSV format.
     */
    public function exportToCsv(?string $startDate = null, ?string $endDate = null): string
    {
        $query = VwGenerationLog::with(['user:id,fullname', 'project:id,name']);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $csv = "ID,Date,User,Project,Prompt,Version,Status,Tokens,Duration (ms),Cost,Error\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",%d,\"%s\",%d,%d,%.6f,\"%s\"\n",
                $log->id,
                $log->created_at->toIso8601String(),
                $log->user?->fullname ?? 'N/A',
                $log->project?->name ?? 'N/A',
                $log->prompt_slug,
                $log->prompt_version ?? 0,
                $log->status,
                $log->tokens_used ?? 0,
                $log->duration_ms ?? 0,
                $log->estimated_cost ?? 0,
                str_replace('"', '""', $log->error_message ?? '')
            );
        }

        return $csv;
    }
}
