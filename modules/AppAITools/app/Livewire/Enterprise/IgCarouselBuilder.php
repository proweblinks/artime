<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class IgCarouselBuilder extends Component
{
    use HasEnterpriseHistory;

    public string $topic = '';
    public string $niche = '';
    public string $slideCount = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'ig_carousel_builder'; }
    protected function getScoreKey(): string { return 'carousel_score'; }
    protected function getScoreLabel(): string { return 'Carousel'; }

    public function resetForm(): void
    {
        $this->topic = '';
        $this->niche = '';
        $this->slideCount = '';
        $this->result = null;
        $this->isLoading = false;
        $this->loadingStep = 0;
    }

    public function mount() { $this->loadHistory(); }

    public function analyze()
    {
        $this->validate([
            'topic' => 'required|string|min:3',
        ]);

        $this->isLoading = true;
        $this->result = null;
        $this->loadingStep = 0;

        try {
            $service = app(EnterpriseToolService::class);
            $this->result = $service->analyzeInstagramCarousel($this->topic, $this->niche, $this->slideCount);
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
        return view('appaitools::livewire.enterprise.ig-carousel-builder', [
            'loadingSteps' => config('appaitools.enterprise_tools.ig-carousel-builder.loading_steps', []),
        ]);
    }
}
