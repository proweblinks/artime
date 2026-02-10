<?php

namespace Modules\AppAITools\Livewire\SubTools;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class ThumbnailArena extends Component
{
    use WithFileUploads;
    use HasToolHistory;

    public $thumbnail1;
    public $thumbnail2;
    public bool $isLoading = false;
    public ?array $result = null;

    protected function getToolKey(): string { return 'thumbnail_arena'; }

    public function mount()
    {
        $this->loadHistory();
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
