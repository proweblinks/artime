<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokHookAnalyzer extends Component
{
    use HasEnterpriseHistory;

    public string $hookText = '';
    public string $niche = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_hook_analyzer'; }
    protected function getScoreKey(): string { return 'hook_score'; }
    protected function getScoreLabel(): string { return 'Hook'; }

    public function resetForm(): void
    {
        $this->hookText = '';
        $this->niche = '';
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
            'hookText' => 'required|string|min:5',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokHook($this->hookText, $this->niche, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.tiktok-hook-analyzer', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-hook-analyzer.loading_steps', []),
        ]);
    }
}
