<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class FbYtReelsConverter extends Component
{
    use HasEnterpriseHistory;

    public string $youtubeUrl = '';
    public string $reelsStyle = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'fb_yt_reels_converter'; }
    protected function getScoreKey(): string { return 'adaptation_score'; }
    protected function getScoreLabel(): string { return 'Adaptation'; }

    public function resetForm(): void
    {
        $this->youtubeUrl = '';
        $this->reelsStyle = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['youtubeUrl' => 'required|url']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->convertYoutubeToFbReels($this->youtubeUrl, $this->reelsStyle);
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
        return view('appaitools::livewire.enterprise.fb-yt-reels-converter', [
            'loadingSteps' => config('appaitools.enterprise_tools.fb-yt-reels-converter.loading_steps', []),
        ]);
    }
}
