<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class CompetitorAnalysis extends Component
{
    use HasToolHistory;

    public string $competitorUrl = '';
    public string $myUrl = '';
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;

    protected function getToolKey(): string { return 'competitor_analysis'; }

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
    }

    public function analyze()
    {
        $this->validate([
            'competitorUrl' => 'required|url',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\CompetitorAnalysisService::class);
            $this->result = $service->analyze($this->competitorUrl, $this->myUrl, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.tools.competitor-analysis', [
            'platforms' => config('appaitools.platforms'),
        ]);
    }
}
