<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbPageGrowth extends Component
{
    use HasEnterpriseHistory;

    public string $pageUrl = '';
    public string $followerCount = '';
    public string $niche = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_page_growth'; }
    protected function getScoreKey(): string { return 'growth_score'; }
    protected function getScoreLabel(): string { return 'Growth'; }

    public function resetForm(): void
    {
        $this->pageUrl = '';
        $this->followerCount = '';
        $this->niche = '';
        $this->youtubeChannel = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['pageUrl' => 'required|string|min:2']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeFbPageGrowth($this->pageUrl, $this->followerCount, $this->niche, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.fb-page-growth', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-page-growth.loading_steps', []),
        ]);
    }
}
