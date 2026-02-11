<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokViralPredictor extends Component
{
    use HasEnterpriseHistory;

    public string $contentDescription = '';
    public string $niche = '';
    public string $followerCount = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_viral_predictor'; }
    protected function getScoreKey(): string { return 'viral_score'; }
    protected function getScoreLabel(): string { return 'Viral'; }

    public function resetForm(): void
    {
        $this->contentDescription = '';
        $this->niche = '';
        $this->followerCount = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['contentDescription' => 'required|string|min:10']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokViralPotential($this->contentDescription, $this->niche, $this->followerCount);
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
        return view('appaitools::livewire.enterprise.tiktok-viral-predictor', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-viral-predictor.loading_steps', []),
        ]);
    }
}
