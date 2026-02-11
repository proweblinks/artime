<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokDuetStitch extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $niche = '';
    public string $goal = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_duet_stitch'; }
    protected function getScoreKey(): string { return 'collaboration_score'; }
    protected function getScoreLabel(): string { return 'Collaboration'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->niche = '';
        $this->goal = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate(['profile' => 'required|string|min:3']);
        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;
        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeTiktokDuetStitch($this->profile, $this->niche, $this->goal);
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
        return view('appaitools::livewire.enterprise.tiktok-duet-stitch', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-duet-stitch.loading_steps', []),
        ]);
    }
}
