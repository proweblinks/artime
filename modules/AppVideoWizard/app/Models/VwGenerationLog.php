<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VwGenerationLog extends Model
{
    protected $table = 'vw_generation_logs';

    protected $fillable = [
        'project_id',
        'user_id',
        'team_id',
        'prompt_slug',
        'prompt_version',
        'input_data',
        'output_data',
        'tokens_used',
        'duration_ms',
        'status',
        'error_message',
        'estimated_cost',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'tokens_used' => 'integer',
        'duration_ms' => 'integer',
        'prompt_version' => 'integer',
        'estimated_cost' => 'decimal:6',
    ];

    /**
     * Cost per 1K tokens (approximate for GPT-4).
     */
    const COST_PER_1K_INPUT_TOKENS = 0.03;
    const COST_PER_1K_OUTPUT_TOKENS = 0.06;

    /**
     * Get the project this log belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    /**
     * Get the user who triggered this generation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the team context.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Modules\AppTeams\Models\Team::class, 'team_id');
    }

    /**
     * Log a successful generation.
     */
    public static function logSuccess(
        string $promptSlug,
        array $inputData,
        array $outputData,
        ?int $tokensUsed = null,
        ?int $durationMs = null,
        ?int $projectId = null,
        ?int $userId = null,
        ?int $teamId = null,
        ?int $promptVersion = null
    ): self {
        $cost = $tokensUsed ? self::estimateCost($tokensUsed) : null;

        return self::create([
            'project_id' => $projectId,
            'user_id' => $userId ?? auth()->id(),
            'team_id' => $teamId ?? session('current_team_id'),
            'prompt_slug' => $promptSlug,
            'prompt_version' => $promptVersion,
            'input_data' => $inputData,
            'output_data' => $outputData,
            'tokens_used' => $tokensUsed,
            'duration_ms' => $durationMs,
            'status' => 'success',
            'estimated_cost' => $cost,
        ]);
    }

    /**
     * Log a failed generation.
     */
    public static function logFailure(
        string $promptSlug,
        array $inputData,
        string $errorMessage,
        ?int $durationMs = null,
        ?int $projectId = null,
        ?int $userId = null,
        ?int $teamId = null,
        ?int $promptVersion = null
    ): self {
        return self::create([
            'project_id' => $projectId,
            'user_id' => $userId ?? auth()->id(),
            'team_id' => $teamId ?? session('current_team_id'),
            'prompt_slug' => $promptSlug,
            'prompt_version' => $promptVersion,
            'input_data' => $inputData,
            'duration_ms' => $durationMs,
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Estimate cost based on token usage.
     */
    public static function estimateCost(int $tokens): float
    {
        // Approximate split: 60% input, 40% output
        $inputTokens = $tokens * 0.6;
        $outputTokens = $tokens * 0.4;

        return ($inputTokens / 1000 * self::COST_PER_1K_INPUT_TOKENS) +
               ($outputTokens / 1000 * self::COST_PER_1K_OUTPUT_TOKENS);
    }

    /**
     * Get analytics for a time period.
     */
    public static function getAnalytics(string $period = 'day', ?int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $query = self::where('created_at', '>=', $startDate);

        return [
            'total_generations' => $query->count(),
            'successful' => (clone $query)->where('status', 'success')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'total_tokens' => (clone $query)->sum('tokens_used'),
            'total_cost' => (clone $query)->sum('estimated_cost'),
            'avg_duration_ms' => (clone $query)->avg('duration_ms'),
            'by_prompt' => (clone $query)
                ->selectRaw('prompt_slug, COUNT(*) as count, SUM(tokens_used) as tokens, SUM(estimated_cost) as cost')
                ->groupBy('prompt_slug')
                ->get()
                ->keyBy('prompt_slug')
                ->toArray(),
        ];
    }

    /**
     * Get success rate for a prompt.
     */
    public static function getSuccessRate(string $promptSlug, ?int $days = 7): float
    {
        $startDate = now()->subDays($days);

        $total = self::where('prompt_slug', $promptSlug)
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $successful = self::where('prompt_slug', $promptSlug)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'success')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by prompt.
     */
    public function scopeForPrompt($query, string $promptSlug)
    {
        return $query->where('prompt_slug', $promptSlug);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate = null)
    {
        $query->where('created_at', '>=', $startDate);

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_ms) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return round($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCostAttribute(): string
    {
        if (!$this->estimated_cost) {
            return 'N/A';
        }

        return '$' . number_format($this->estimated_cost, 4);
    }
}
