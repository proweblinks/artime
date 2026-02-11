<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokYtArbitrage extends Component
{
    use HasEnterpriseHistory;

    public string $youtubeChannel = '';
    public string $tiktokNiche = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_yt_arbitrage'; }
    protected function getScoreKey(): string { return 'arbitrage_score'; }
    protected function getScoreLabel(): string { return 'Arbitrage'; }

    public function resetForm(): void
    {
        $this->youtubeChannel = '';
        $this->tiktokNiche = '';
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
            $this->result = $service->analyzeYoutubeTiktokArbitrage($this->youtubeChannel, $this->tiktokNiche);
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
        return view('appaitools::livewire.enterprise.tiktok-yt-arbitrage', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-yt-arbitrage.loading_steps', []),
        ]);
    }
}
