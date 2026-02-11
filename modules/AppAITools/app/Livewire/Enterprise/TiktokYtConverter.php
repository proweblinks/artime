<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokYtConverter extends Component
{
    use HasEnterpriseHistory;

    public string $youtubeUrl = '';
    public string $tiktokStyle = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_yt_converter'; }
    protected function getScoreKey(): string { return 'adaptation_score'; }
    protected function getScoreLabel(): string { return 'Adaptation'; }

    public function resetForm(): void
    {
        $this->youtubeUrl = '';
        $this->tiktokStyle = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate([
            'youtubeUrl' => 'required|url',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->convertYoutubeToTiktok($this->youtubeUrl, $this->tiktokStyle);
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
        return view('appaitools::livewire.enterprise.tiktok-yt-converter', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-yt-converter.loading_steps', []),
        ]);
    }
}
