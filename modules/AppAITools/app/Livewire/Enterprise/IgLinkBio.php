<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class IgLinkBio extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $currentLinks = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'ig_link_bio'; }
    protected function getScoreKey(): string { return 'bio_score'; }
    protected function getScoreLabel(): string { return 'Bio'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->currentLinks = '';
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
            $this->result = $service->analyzeInstagramLinkBio($this->profile, $this->currentLinks);
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
        return view('appaitools::livewire.enterprise.ig-link-bio', [
            'loadingSteps' => config('appaitools.enterprise_tools.ig-link-bio.loading_steps', []),
        ]);
    }
}
