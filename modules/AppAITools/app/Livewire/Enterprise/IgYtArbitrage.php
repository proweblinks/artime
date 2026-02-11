<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class IgYtArbitrage extends Component
{
    use HasEnterpriseHistory;

    public string $youtubeChannel = '';
    public string $igNiche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'ig_yt_arbitrage'; }
    protected function getScoreKey(): string { return 'arbitrage_score'; }
    protected function getScoreLabel(): string { return 'Arbitrage'; }

    public function resetForm(): void
    {
        $this->youtubeChannel = '';
        $this->igNiche = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate([
            'youtubeChannel' => 'required|url',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeYoutubeInstagramArbitrage($this->youtubeChannel, $this->igNiche);
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
        return view('appaitools::livewire.enterprise.ig-yt-arbitrage', [
            'loadingSteps' => config('appaitools.enterprise_tools.ig-yt-arbitrage.loading_steps', []),
        ]);
    }
}
