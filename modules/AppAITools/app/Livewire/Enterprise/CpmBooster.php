<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class CpmBooster extends Component
{
    use HasEnterpriseHistory;

    public string $url = '';
    public string $niche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'cpm_booster'; }
    protected function getScoreKey(): string { return 'cpm_score'; }
    protected function getScoreLabel(): string { return 'CPM'; }

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
            $this->result = $service->analyzeCpmBoost($this->url, $this->niche);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.cpm-booster', [
            'loadingSteps' => config('appaitools.enterprise_tools.cpm-booster.loading_steps', []),
        ]);
    }
}
