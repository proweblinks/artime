<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class VideoOptimizer extends Component
{
    use HasToolHistory;

    public string $url = '';
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public string $activeTab = 'titles';

    protected function getToolKey(): string { return 'video_optimizer'; }

    public function mount()
    {
        $this->platform = config('appaitools.platforms.' . get_option('creator_hub_default_platform', 'youtube')) ? get_option('creator_hub_default_platform', 'youtube') : 'youtube';
        $this->loadHistory();
    }

    public function optimize()
    {
        $this->validate([
            'url' => 'required|url',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\VideoOptimizerService::class);
            $this->result = $service->optimize($this->url, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('appaitools::livewire.tools.video-optimizer', [
            'platforms' => config('appaitools.platforms'),
        ]);
    }
}
