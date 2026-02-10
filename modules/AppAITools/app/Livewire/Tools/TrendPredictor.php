<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class TrendPredictor extends Component
{
    use HasToolHistory;

    public string $niche = '';
    public string $platform = '';
    public string $region = 'US';
    public bool $isLoading = false;
    public ?array $result = null;

    protected function getToolKey(): string { return 'trend_predictor'; }

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
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
