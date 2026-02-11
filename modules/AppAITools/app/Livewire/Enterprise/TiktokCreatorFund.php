<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokCreatorFund extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $avgViews = '';
    public string $followerCount = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_creator_fund'; }
    protected function getScoreKey(): string { return 'fund_score'; }
    protected function getScoreLabel(): string { return 'Fund'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->avgViews = '';
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
            $this->result = $service->analyzeTiktokCreatorFund($this->profile, $this->avgViews, $this->followerCount);
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
        return view('appaitools::livewire.enterprise.tiktok-creator-fund', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-creator-fund.loading_steps', []),
        ]);
    }
}
