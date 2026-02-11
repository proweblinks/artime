<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokBrandPartnership extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $niche = '';
    public string $followerCount = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_brand_partnership'; }
    protected function getScoreKey(): string { return 'partnership_score'; }
    protected function getScoreLabel(): string { return 'Partnership'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->niche = '';
        $this->followerCount = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['profile' => 'required|string|min:3']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokBrandPartnership($this->profile, $this->niche, $this->followerCount);
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
        return view('appaitools::livewire.enterprise.tiktok-brand-partnership', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-brand-partnership.loading_steps', []),
        ]);
    }
}
