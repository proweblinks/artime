<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class DigitalProductArchitect extends Component
{
    use HasEnterpriseHistory;

    public string $url = '';
    public string $expertise = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'digital_product_architect'; }
    protected function getScoreKey(): string { return 'product_readiness_score'; }
    protected function getScoreLabel(): string { return 'Readiness'; }

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
            $this->result = $service->designDigitalProducts($this->url, $this->expertise);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.digital-product-architect', [
            'loadingSteps' => config('appaitools.enterprise_tools.digital-product-architect.loading_steps', []),
        ]);
    }
}
