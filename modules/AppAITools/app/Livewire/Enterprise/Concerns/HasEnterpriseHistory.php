<?php

namespace Modules\AppAITools\Livewire\Enterprise\Concerns;

use Modules\AppAITools\Models\AiToolHistory;
use Carbon\Carbon;

trait HasEnterpriseHistory
{
    public array $history = [];

    /**
     * Score key name in result_data for this tool.
     * Override in component if different from default.
     */
    protected function getScoreKey(): string
    {
        return 'score';
    }

    /**
     * Human-readable score label.
     */
    protected function getScoreLabel(): string
    {
        return 'Score';
    }

    /**
     * Tool key used in ai_tool_history.tool column.
     */
    abstract protected function getToolKey(): string;

    /**
     * Extract a subtitle from result_data for history display.
     * Override in component for custom subtitle.
     */
    protected function getHistorySubtitle(array $resultData): string
    {
        // Try common patterns
        foreach (['channel_analysis.niche', 'channel_overview.niche', 'channel_profile.niche', 'current_analysis.niche', 'creator_profile.niche', 'content_analysis.niche', 'video_analysis.topic'] as $path) {
            $parts = explode('.', $path);
            $value = $resultData;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    $value = null;
                    break;
                }
                $value = $value[$part];
            }
            if ($value && is_string($value)) {
                return $value;
            }
        }
        return '';
    }

    public function loadHistoryItem(int $index): void
    {
        if (isset($this->history[$index])) {
            $this->result = $this->history[$index]['result_data'] ?? null;
        }
    }

    public function resetForm(): void
    {
        $this->url = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function deleteHistoryItem(int $index): void
    {
        $teamId = session('current_team_id');
        if (!$teamId) return;

        if (isset($this->history[$index]['id'])) {
            AiToolHistory::where('id', $this->history[$index]['id'])
                ->where('team_id', $teamId)
                ->delete();
            $this->loadHistory();
        }
    }

    protected function loadHistory(): void
    {
        $teamId = session('current_team_id');
        if (!$teamId) return;

        $scoreKey = $this->getScoreKey();
        $scoreLabel = $this->getScoreLabel();

        $this->history = AiToolHistory::forTeam($teamId)
            ->forTool($this->getToolKey())
            ->completed()
            ->orderByDesc('created')
            ->limit(10)
            ->get()
            ->map(function ($h) use ($scoreKey, $scoreLabel) {
                $resultData = $h->result_data ?? [];
                $score = $resultData[$scoreKey] ?? null;

                return [
                    'id' => $h->id,
                    'title' => $h->title ?? '-',
                    'subtitle' => $this->getHistorySubtitle($resultData),
                    'score' => $score,
                    'score_label' => $scoreLabel,
                    'credits' => $h->credits_used ?? 0,
                    'time_ago' => Carbon::createFromTimestamp($h->created)->diffForHumans(),
                    'result_data' => $resultData,
                ];
            })
            ->toArray();
    }
}
