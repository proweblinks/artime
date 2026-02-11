<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokPostingTime extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $timezone = '';
    public string $contentType = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_posting_time'; }
    protected function getScoreKey(): string { return 'timing_score'; }
    protected function getScoreLabel(): string { return 'Timing'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->timezone = '';
        $this->contentType = '';
        $this->youtubeChannel = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount()
    {
        $this->loadHistory();
    }

    public function analyze()
    {
        $this->validate([
            'profile' => 'required|string|min:3',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokPostingTime($this->profile, $this->timezone, $this->contentType, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.tiktok-posting-time', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-posting-time.loading_steps', []),
        ]);
    }
}
