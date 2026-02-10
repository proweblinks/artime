<?php

namespace Modules\AppAITools\Livewire\Tools\Concerns;

use Carbon\Carbon;
use Modules\AppAITools\Models\AiToolHistory;

trait HasToolHistory
{
    public array $history = [];

    abstract protected function getToolKey(): string;

    protected function mapHistoryItem($h): array
    {
        return [
            'id' => $h->id,
            'title' => $h->title,
            'platform' => $h->platform,
            'credits' => $h->credits_used,
            'time_ago' => Carbon::createFromTimestamp($h->created)->diffForHumans(),
            'result_data' => json_decode($h->result_data, true),
        ];
    }

    public function loadHistory(): void
    {
        $teamId = session('current_team_id');
        if (!$teamId) {
            $this->history = [];
            return;
        }

        $this->history = AiToolHistory::forTeam($teamId)
            ->forTool($this->getToolKey())
            ->completed()
            ->orderByDesc('created')
            ->limit(10)
            ->get()
            ->map(fn ($h) => $this->mapHistoryItem($h))
            ->toArray();
    }

    public function loadHistoryItem(int $index): void
    {
        if (isset($this->history[$index]['result_data'])) {
            $this->result = $this->history[$index]['result_data'];
        }
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
}
