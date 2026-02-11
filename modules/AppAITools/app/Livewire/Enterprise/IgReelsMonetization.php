<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class IgReelsMonetization extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $avgViews = '';
    public string $followerCount = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'ig_reels_monetization'; }
    protected function getScoreKey(): string { return 'reels_score'; }
    protected function getScoreLabel(): string { return 'Reels'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->avgViews = '';
        $this->followerCount = '';
        $this->youtubeChannel = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate([
            'profile' => 'required|string|min:2',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeInstagramReelsMonetization($this->profile, $this->avgViews, $this->followerCount, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.ig-reels-monetization', [
            'loadingSteps' => config('appaitools.enterprise_tools.ig-reels-monetization.loading_steps', []),
        ]);
    }
}
