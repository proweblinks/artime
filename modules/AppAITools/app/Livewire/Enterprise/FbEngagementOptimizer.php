<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbEngagementOptimizer extends Component
{
    use HasEnterpriseHistory;

    public string $pageUrl = '';
    public string $avgEngagement = '';
    public string $contentType = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_engagement_optimizer'; }
    protected function getScoreKey(): string { return 'engagement_score'; }
    protected function getScoreLabel(): string { return 'Engagement'; }

    public function resetForm(): void
    {
        $this->pageUrl = '';
        $this->avgEngagement = '';
        $this->contentType = '';
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
            $this->result = $service->analyzeFbEngagement($this->pageUrl, $this->avgEngagement, $this->contentType);
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
        return view('appaitools::livewire.enterprise.fb-engagement-optimizer', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-engagement-optimizer.loading_steps', []),
        ]);
    }
}
