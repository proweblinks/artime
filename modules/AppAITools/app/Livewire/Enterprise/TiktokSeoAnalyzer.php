<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokSeoAnalyzer extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $caption = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_seo_analyzer'; }
    protected function getScoreKey(): string { return 'seo_score'; }
    protected function getScoreLabel(): string { return 'SEO'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->caption = '';
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
            $this->result = $service->analyzeTiktokSeo($this->profile, $this->caption);
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
        return view('appaitools::livewire.enterprise.tiktok-seo-analyzer', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-seo-analyzer.loading_steps', []),
        ]);
    }
}
