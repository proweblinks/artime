<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbAudienceInsights extends Component
{
    use HasEnterpriseHistory;

    public string $pageUrl = '';
    public string $niche = '';
    public string $followerCount = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_audience_insights'; }
    protected function getScoreKey(): string { return 'audience_score'; }
    protected function getScoreLabel(): string { return 'Audience'; }

    public function resetForm(): void
    {
        $this->pageUrl = '';
        $this->niche = '';
        $this->followerCount = '';
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
            $this->result = $service->analyzeFbAudienceInsights($this->pageUrl, $this->niche, $this->followerCount, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.fb-audience-insights', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-audience-insights.loading_steps', []),
        ]);
    }
}
