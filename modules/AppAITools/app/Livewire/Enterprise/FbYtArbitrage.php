<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbYtArbitrage extends Component
{
    use HasEnterpriseHistory;

    public string $youtubeChannel = '';
    public string $fbNiche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_yt_arbitrage'; }
    protected function getScoreKey(): string { return 'arbitrage_score'; }
    protected function getScoreLabel(): string { return 'Arbitrage'; }

    public function resetForm(): void
    {
        $this->youtubeChannel = '';
        $this->fbNiche = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['youtubeChannel' => 'required|url']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeYoutubeFacebookArbitrage($this->youtubeChannel, $this->fbNiche);
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
        return view('appaitools::livewire.enterprise.fb-yt-arbitrage', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-yt-arbitrage.loading_steps', []),
        ]);
    }
}
