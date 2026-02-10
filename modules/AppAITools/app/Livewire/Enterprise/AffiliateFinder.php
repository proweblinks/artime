<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class AffiliateFinder extends Component
{
    use HasEnterpriseHistory;

    public string $url = '';
    public string $niche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'affiliate_finder'; }
    protected function getScoreKey(): string { return 'affiliate_score'; }
    protected function getScoreLabel(): string { return 'Affiliate'; }

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
            $this->result = $service->findAffiliates($this->url, $this->niche);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.affiliate-finder', [
            'loadingSteps' => config('appaitools.enterprise_tools.affiliate-finder.loading_steps', []),
        ]);
    }
}
