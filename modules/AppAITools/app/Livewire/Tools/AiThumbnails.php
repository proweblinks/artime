<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;

class AiThumbnails extends Component
{
    public string $title = '';
    public string $style = 'bold_text';
    public string $customPrompt = '';
    public string $aspectRatio = '16:9';
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
                ->forTool('ai_thumbnails')
                ->completed()
                ->orderByDesc('created')
                ->limit(10)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'title' => $h->title,
                    'created' => $h->created,
                    'assets' => $h->assets->map(fn ($a) => ['path' => $a->file_path, 'metadata' => $a->metadata])->toArray(),
                ])
                ->toArray();
        }
    }

    public function generate()
    {
        $this->validate([
            'title' => 'required|string|min:3|max:200',
            'style' => 'required|in:' . implode(',', array_keys(config('appaitools.thumbnail_styles'))),
            'aspectRatio' => 'required|in:16:9,9:16,1:1',
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\ThumbnailService::class);
            $this->result = $service->generate($this->title, $this->style, $this->customPrompt, $this->aspectRatio);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('appaitools::livewire.tools.ai-thumbnails', [
            'styles' => config('appaitools.thumbnail_styles'),
        ]);
    }
}
