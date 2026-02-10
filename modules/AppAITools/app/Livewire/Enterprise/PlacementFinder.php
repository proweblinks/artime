<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class PlacementFinder extends Component
{
    use HasEnterpriseHistory;

    public string $url = '';
    public string $niche = '';
    public bool $isLoading = false;
    public bool $isFindingMore = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'placement_finder'; }
    protected function getScoreKey(): string { return 'placement_score'; }
    protected function getScoreLabel(): string { return 'Placement'; }

    public function resetForm(): void
    {
        $this->url = '';
        $this->niche = '';
        $this->result = null;
        $this->isLoading = false;
        $this->isFindingMore = false;
        $this->loadingStep = 0;
    }

    public function mount()
    {
        $this->loadHistory();
    }

    public function analyze()
    {
        $this->validate([
            'url' => 'required|url',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzePlacement($this->url, $this->niche);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        } finally {
            $this->dispatch('loadingComplete');
            $this->isLoading = false;
        }
    }

    public function findMore()
    {
        if (!$this->result || !$this->url) return;

        $this->isFindingMore = true;

        try {
            // Collect existing handles to exclude
            $existingHandles = collect($this->result['placements'] ?? [])
                ->pluck('handle')
                ->filter()
                ->values()
                ->toArray();

            $service = app(EnterpriseToolService::class);
            $moreResults = $service->findMorePlacements($this->url, $this->niche, $existingHandles);

            // Merge new placements into existing result
            if (!empty($moreResults['placements'])) {
                $this->result['placements'] = array_merge(
                    $this->result['placements'] ?? [],
                    $moreResults['placements']
                );
            }

            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to find more: ' . $e->getMessage());
        } finally {
            $this->dispatch('findMoreComplete');
            $this->isFindingMore = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.enterprise.placement-finder', [
            'loadingSteps' => config('appaitools.enterprise_tools.placement-finder.loading_steps', []),
        ]);
    }
}
