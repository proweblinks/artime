<?php

namespace Modules\AppAITools\Livewire\SubTools;

use Livewire\Component;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class ContentMultiplier extends Component
{
    use HasToolHistory;

    public string $originalContent = '';
    public array $selectedFormats = [];
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;

    protected function getToolKey(): string { return 'content_multiplier'; }

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
    }

    public function multiply()
    {
        $this->validate([
            'originalContent' => 'required|string|min:50|max:10000',
            'selectedFormats' => 'required|array|min:1',
            'selectedFormats.*' => 'in:' . implode(',', array_keys(config('appaitools.multiplier_formats'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\ContentMultiplierService::class);
            $this->result = $service->multiply($this->originalContent, $this->selectedFormats, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.sub-tools.content-multiplier', [
            'formats' => config('appaitools.multiplier_formats'),
        ]);
    }
}
