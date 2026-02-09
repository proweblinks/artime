<?php

namespace Modules\AppAITools\Livewire\SubTools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class ViralHookLab extends Component
{
    public string $topic = '';
    public string $hookStyle = 'question';
    public int $count = 5;
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->history = AiToolHistory::forTeam($teamId)
                ->forTool('viral_hooks')
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

    public function generate()
    {
        $this->validate([
            'topic' => 'required|string|min:3|max:500',
            'hookStyle' => 'required|in:' . implode(',', array_keys(config('appaitools.hook_styles'))),
            'count' => 'required|integer|min:3|max:10',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\HookGeneratorService::class);
            $this->result = $service->generate($this->topic, $this->hookStyle, $this->count, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.sub-tools.viral-hook-lab', [
            'platforms' => config('appaitools.platforms'),
            'hookStyles' => config('appaitools.hook_styles'),
        ]);
    }
}
