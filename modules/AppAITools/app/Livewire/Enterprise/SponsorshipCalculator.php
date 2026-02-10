<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class SponsorshipCalculator extends Component
{
    use HasEnterpriseHistory;

    public string $url = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'sponsorship_calculator'; }
    protected function getScoreKey(): string { return 'sponsorship_score'; }
    protected function getScoreLabel(): string { return 'Sponsorship'; }

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
            $this->result = $service->calculateSponsorship($this->url);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->dispatch('loadingComplete');
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.sponsorship-calculator', [
            'loadingSteps' => config('appaitools.enterprise_tools.sponsorship-calculator.loading_steps', []),
        ]);
    }
}
