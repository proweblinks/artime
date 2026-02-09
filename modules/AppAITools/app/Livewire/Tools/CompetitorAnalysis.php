<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class CompetitorAnalysis extends Component
{
    public string $competitorUrl = '';
    public string $myUrl = '';
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->history = AiToolHistory::forTeam($teamId)
                ->forTool('competitor_analysis')
                ->completed()
                ->orderByDesc('created')
                ->limit(10)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'title' => $h->title,
                    'platform' => $h->platform,
                    'created' => $h->created,
                ])
                ->toArray();
        }
    }

    public function analyze()
    {
        $this->validate([
            'competitorUrl' => 'required|url',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\CompetitorAnalysisService::class);
            $this->result = $service->analyze($this->competitorUrl, $this->myUrl, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.tools.competitor-analysis', [
            'platforms' => config('appaitools.platforms'),
        ]);
    }
}
