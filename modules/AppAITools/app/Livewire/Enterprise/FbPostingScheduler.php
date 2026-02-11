<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbPostingScheduler extends Component
{
    use HasEnterpriseHistory;

    public string $pageUrl = '';
    public string $timezone = '';
    public string $contentType = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_posting_scheduler'; }
    protected function getScoreKey(): string { return 'timing_score'; }
    protected function getScoreLabel(): string { return 'Timing'; }

    public function resetForm(): void
    {
        $this->pageUrl = '';
        $this->timezone = '';
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
            $this->result = $service->analyzeFbPostingTime($this->pageUrl, $this->timezone, $this->contentType);
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
        return view('appaitools::livewire.enterprise.fb-posting-scheduler', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-posting-scheduler.loading_steps', []),
        ]);
    }
}
