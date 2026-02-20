<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Modules\AppAIContents\Models\ContentPhotoshoot;
use Modules\AppAIContents\Services\PhotoshootService;

class PhotoshootHub extends Component
{
    use WithFileUploads;

    public ?int $dnaId = null;
    public string $mode = 'menu'; // menu, template, freeform
    public string $prompt = '';
    public string $aspectRatio = '9:16';
    public bool $isGenerating = false;
    public array $results = [];

    // Template mode
    public $productImage = null;
    public ?string $productImagePath = null;
    public ?string $selectedTemplate = null;

    // Freeform mode
    public $referenceImages = [];
    public array $referenceImagePaths = [];

    public function mount(?int $dnaId = null)
    {
        $this->dnaId = $dnaId;
    }

    public function selectMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function updatedProductImage()
    {
        if ($this->productImage) {
            $teamId = auth()->user()->current_team_id ?? auth()->id();
            $this->productImagePath = $this->productImage->store("content-studio/{$teamId}/photoshoot", 'public');
        }
    }

    public function selectTemplate(string $templateId)
    {
        $this->selectedTemplate = $this->selectedTemplate === $templateId ? null : $templateId;
    }

    public function uploadReferenceImages()
    {
        if (empty($this->referenceImages)) return;

        $teamId = auth()->user()->current_team_id ?? auth()->id();
        foreach ($this->referenceImages as $img) {
            if (count($this->referenceImagePaths) >= 3) break;
            $path = $img->store("content-studio/{$teamId}/photoshoot/ref", 'public');
            $this->referenceImagePaths[] = $path;
        }
        $this->referenceImages = [];
    }

    public function removeReferenceImage(int $index)
    {
        unset($this->referenceImagePaths[$index]);
        $this->referenceImagePaths = array_values($this->referenceImagePaths);
    }

    public function generate()
    {
        $this->isGenerating = true;
        $teamId = auth()->user()->current_team_id ?? auth()->id();

        if ($this->mode === 'template') {
            if (!$this->productImagePath || !$this->selectedTemplate) return;

            dispatch(function () use ($teamId) {
                $service = new PhotoshootService();
                $photoshoot = $service->generateFromTemplate(
                    $this->productImagePath,
                    $this->selectedTemplate,
                    $this->aspectRatio,
                    $teamId,
                    $this->dnaId
                );
            })->afterResponse();
        } else {
            if (empty(trim($this->prompt))) return;

            dispatch(function () use ($teamId) {
                $service = new PhotoshootService();
                $photoshoot = $service->generateFreeform(
                    $this->prompt,
                    $this->referenceImagePaths,
                    $this->aspectRatio,
                    $teamId,
                    $this->dnaId
                );
            })->afterResponse();
        }
    }

    public function pollResults()
    {
        if (!$this->isGenerating) return;

        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $latest = ContentPhotoshoot::where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->first();

        if ($latest && in_array($latest->status, ['ready', 'failed'])) {
            $this->isGenerating = false;
            $this->results = $latest->results ?? [];
        }
    }

    public function goBack()
    {
        if ($this->mode !== 'menu') {
            $this->mode = 'menu';
            $this->results = [];
            $this->isGenerating = false;
        }
    }

    public function setAspectRatio(string $ratio)
    {
        $this->aspectRatio = $ratio;
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $recentPhotoshoots = ContentPhotoshoot::where('team_id', $teamId)
            ->when($this->dnaId, fn($q) => $q->where('dna_id', $this->dnaId))
            ->where('status', 'ready')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $templates = [
            ['id' => 'studio-white', 'name' => 'Studio White', 'icon' => 'fa-light fa-lightbulb'],
            ['id' => 'lifestyle', 'name' => 'Lifestyle', 'icon' => 'fa-light fa-couch'],
            ['id' => 'outdoor', 'name' => 'Outdoor', 'icon' => 'fa-light fa-tree'],
            ['id' => 'flat-lay', 'name' => 'Flat Lay', 'icon' => 'fa-light fa-layer-group'],
            ['id' => 'dramatic', 'name' => 'Dramatic', 'icon' => 'fa-light fa-bolt'],
            ['id' => 'seasonal', 'name' => 'Seasonal', 'icon' => 'fa-light fa-snowflake'],
            ['id' => 'minimalist', 'name' => 'Minimalist', 'icon' => 'fa-light fa-minus'],
            ['id' => 'luxury', 'name' => 'Luxury', 'icon' => 'fa-light fa-gem'],
            ['id' => 'tech', 'name' => 'Tech', 'icon' => 'fa-light fa-microchip'],
        ];

        return view('appaicontents::livewire.photoshoot-hub', [
            'templates' => $templates,
            'recentPhotoshoots' => $recentPhotoshoots,
        ]);
    }
}
