<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokSoundTrends extends Component
{
    use HasEnterpriseHistory;

    public string $niche = '';
    public string $contentStyle = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_sound_trends'; }
    protected function getScoreKey(): string { return 'sound_score'; }
    protected function getScoreLabel(): string { return 'Sound'; }

    public function resetForm(): void
    {
        $this->niche = '';
        $this->contentStyle = '';
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
            'niche' => 'required|string|min:3',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokSoundTrends($this->niche, $this->contentStyle);
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
        return view('appaitools::livewire.enterprise.tiktok-sound-trends', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-sound-trends.loading_steps', []),
        ]);
    }
}
