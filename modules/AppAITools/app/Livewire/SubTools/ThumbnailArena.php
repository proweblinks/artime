<?php

namespace Modules\AppAITools\Livewire\SubTools;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AppAITools\Models\AiToolHistory;

class ThumbnailArena extends Component
{
    use WithFileUploads;

    public $thumbnail1;
    public $thumbnail2;
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];

    public function mount()
    {
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->history = AiToolHistory::forTeam($teamId)
                ->forTool('thumbnail_arena')
                ->completed()
                ->orderByDesc('created')
                ->limit(10)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'title' => $h->title,
                    'created' => $h->created,
                ])
                ->toArray();
        }
    }

    public function compare()
    {
        $this->validate([
            'thumbnail1' => 'required|image|max:5120',
            'thumbnail2' => 'required|image|max:5120',
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\ThumbnailService::class);
            $this->result = $service->compare(
                $this->thumbnail1->getRealPath(),
                $this->thumbnail2->getRealPath()
            );
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.sub-tools.thumbnail-arena');
    }
}
