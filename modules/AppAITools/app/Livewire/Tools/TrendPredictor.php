<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class TrendPredictor extends Component
{
    public string $niche = '';
    public string $platform = '';
    public string $region = 'US';
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
                ->forTool('trend_predictor')
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

    public function predict()
    {
        $this->validate([
            'niche' => 'required|string|min:2|max:200',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
            'region' => 'required|string|size:2',
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\TrendPredictorService::class);
            $this->result = $service->predict($this->niche, $this->platform, $this->region);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.tools.trend-predictor', [
            'platforms' => config('appaitools.platforms'),
        ]);
    }
}
