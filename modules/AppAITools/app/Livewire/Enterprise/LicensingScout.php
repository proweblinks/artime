<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;
use Modules\AppAITools\Services\EnterpriseToolService;
use Carbon\Carbon;

class LicensingScout extends Component
{
    public string $url = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];
    public int $loadingStep = 0;

    public function mount()
    {
        $this->loadHistory();
    }

    public function analyze()
    {
        $this->validate([
            'url' => 'required|url',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->scoutLicensing($this->url);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadHistoryItem(int $index): void
    {
        if (isset($this->history[$index])) {
            $this->result = $this->history[$index]['result_data'] ?? null;
        }
    }

    protected function loadHistory(): void
    {
        $teamId = session('current_team_id');
        if (!$teamId) return;

        $this->history = AiToolHistory::forTeam($teamId)
            ->forTool('licensing_scout')
            ->completed()
            ->orderByDesc('created')
            ->limit(10)
            ->get()
            ->map(fn($h) => [
                'title' => $h->title ?? '-',
                'time_ago' => Carbon::createFromTimestamp($h->created)->diffForHumans(),
                'result_data' => $h->result_data,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.licensing-scout', [
            'loadingSteps' => config('appaitools.enterprise_tools.licensing-scout.loading_steps', []),
        ]);
    }
}
