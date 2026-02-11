<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbGroupMonetization extends Component
{
    use HasEnterpriseHistory;

    public string $groupUrl = '';
    public string $memberCount = '';
    public string $niche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_group_monetization'; }
    protected function getScoreKey(): string { return 'group_score'; }
    protected function getScoreLabel(): string { return 'Group'; }

    public function resetForm(): void
    {
        $this->groupUrl = '';
        $this->memberCount = '';
        $this->niche = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['groupUrl' => 'required|string|min:2']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeFbGroupMonetization($this->groupUrl, $this->memberCount, $this->niche);
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
        return view('appaitools::livewire.enterprise.fb-group-monetization', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-group-monetization.loading_steps', []),
        ]);
    }
}
