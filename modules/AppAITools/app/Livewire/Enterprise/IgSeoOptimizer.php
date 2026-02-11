<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class IgSeoOptimizer extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $caption = '';
    public string $youtubeChannel = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'ig_seo_optimizer'; }
    protected function getScoreKey(): string { return 'seo_score'; }
    protected function getScoreLabel(): string { return 'SEO'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->caption = '';
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
            $this->result = $service->analyzeInstagramSeo($this->profile, $this->caption, $this->youtubeChannel);
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
        return view('appaitools::livewire.enterprise.ig-seo-optimizer', [
            'loadingSteps' => config('appaitools.enterprise_tools.ig-seo-optimizer.loading_steps', []),
        ]);
    }
}
