<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbAdBreaks extends Component
{
    use HasEnterpriseHistory;

    public string $pageUrl = '';
    public string $contentType = '';
    public string $avgVideoLength = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_ad_breaks'; }
    protected function getScoreKey(): string { return 'ad_break_score'; }
    protected function getScoreLabel(): string { return 'Ad Breaks'; }

    public function resetForm(): void
    {
        $this->pageUrl = '';
        $this->contentType = '';
        $this->avgVideoLength = '';
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
            $this->result = $service->analyzeFbAdBreaks($this->pageUrl, $this->contentType, $this->avgVideoLength);
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
        return view('appaitools::livewire.enterprise.fb-ad-breaks', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-ad-breaks.loading_steps', []),
        ]);
    }
}
