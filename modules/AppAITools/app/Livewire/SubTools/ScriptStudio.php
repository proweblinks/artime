<?php

namespace Modules\AppAITools\Livewire\SubTools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class ScriptStudio extends Component
{
    public string $topic = '';
    public string $duration = 'medium';
    public string $style = 'engaging';
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
                ->forTool('script_studio')
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
            'duration' => 'required|in:short,medium,long',
            'style' => 'required|in:' . implode(',', array_keys(config('appaitools.script_styles'))),
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\ScriptStudioService::class);
            $this->result = $service->generate($this->topic, $this->duration, $this->style, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.sub-tools.script-studio', [
            'platforms' => config('appaitools.platforms'),
            'styles' => config('appaitools.script_styles'),
            'durations' => config('appaitools.script_durations'),
        ]);
    }
}
