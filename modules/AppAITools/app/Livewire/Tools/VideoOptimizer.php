<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class VideoOptimizer extends Component
{
    public string $url = '';
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];
    public string $activeTab = 'titles';

    public function mount()
    {
        $this->platform = config('appaitools.platforms.' . get_option('creator_hub_default_platform', 'youtube')) ? get_option('creator_hub_default_platform', 'youtube') : 'youtube';
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->history = AiToolHistory::forTeam($teamId)
                ->forTool('video_optimizer')
                ->completed()
                ->orderByDesc('created')
                ->limit(10)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'title' => $h->title,
                    'platform' => $h->platform,
                    'created' => $h->created,
                ])
                ->toArray();
        }
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
