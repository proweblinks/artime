<?php

namespace Modules\AppAITools\Livewire;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class ToolsHub extends Component
{
    public array $recentActivity = [];

    public function mount()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->recentActivity = AiToolHistory::forTeam($teamId)
                ->completed()
                ->orderByDesc('created')
                ->limit(5)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'tool' => $h->tool,
                    'title' => $h->title,
                    'platform' => $h->platform,
                    'created' => $h->created,
                ])
                ->toArray();
        }
    }

    public function render()
    {
        return view('appaitools::livewire.tools-hub', [
            'tools' => config('appaitools.tools'),
        ]);
    }
}
