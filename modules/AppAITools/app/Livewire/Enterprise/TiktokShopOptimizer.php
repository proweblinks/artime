<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Services\EnterpriseToolService;
use Modules\AppAITools\Livewire\Enterprise\Concerns\HasEnterpriseHistory;

class TiktokShopOptimizer extends Component
{
    use HasEnterpriseHistory;

    public string $profile = '';
    public string $productType = '';
    public string $priceRange = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public int $loadingStep = 0;

    protected function getToolKey(): string { return 'tiktok_shop_optimizer'; }
    protected function getScoreKey(): string { return 'shop_score'; }
    protected function getScoreLabel(): string { return 'Shop'; }

    public function resetForm(): void
    {
        $this->profile = '';
        $this->productType = '';
        $this->priceRange = '';
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
            $this->result = $service->analyzeTiktokShop($this->profile, $this->productType, $this->priceRange);
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
        return view('appaitools::livewire.enterprise.tiktok-shop-optimizer', [
            'loadingSteps' => config('appaitools.enterprise_tools.tiktok-shop-optimizer.loading_steps', []),
        ]);
    }
}
