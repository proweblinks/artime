<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Modules\AppAIContents\Models\ContentBusinessDna;
use Modules\AppAIContents\Services\BusinessDnaService;
use Modules\AppAIContents\Services\WebScraperService;

class BusinessDna extends Component
{
    use WithFileUploads;

    public ?int $dnaId = null;
    public ?ContentBusinessDna $dna = null;
    public string $websiteUrl = '';
    public bool $isAnalyzing = false;
    public ?string $editingField = null;
    public bool $justCompleted = false;

    protected $listeners = ['switch-dna' => 'onSwitchDna'];

    // Edit form values
    public string $editBrandName = '';
    public string $editTagline = '';
    public string $editOverview = '';
    public array $editColors = [];
    public array $editFonts = [];
    public array $editBrandValues = [];
    public array $editBrandAesthetic = [];
    public array $editBrandTone = [];
    public string $newChipValue = '';
    public string $newColorHex = '#03fcf4';

    // File uploads
    public $logoUpload = null;
    public $imageUploads = [];

    public function mount(?int $dnaId = null)
    {
        $this->dnaId = $dnaId;
        $this->loadDna();
    }

    public function onSwitchDna(?int $newDnaId)
    {
        $this->dnaId = $newDnaId;
        $this->dna = null;
        $this->isAnalyzing = false;
        $this->justCompleted = false;
        $this->editingField = null;
        $this->websiteUrl = '';
        $this->logoUpload = null;
        $this->imageUploads = [];
        $this->loadDna();
    }

    protected function loadDna()
    {
        if ($this->dnaId) {
            $this->dna = ContentBusinessDna::find($this->dnaId);
        }

        // Don't auto-load first DNA — rely on the dnaId passed from ContentHub
        if ($this->dna && $this->dna->status === 'analyzing') {
            $this->isAnalyzing = true;
        }
    }

    public function analyzeSite()
    {
        if (empty($this->websiteUrl)) return;

        $url = $this->websiteUrl;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $this->isAnalyzing = true;
        $teamId = auth()->user()->current_team_id ?? auth()->id();

        // Create new DNA record (multi-business: always create, never updateOrCreate)
        $this->dna = ContentBusinessDna::create([
            'team_id' => $teamId,
            'website_url' => $url,
            'status' => 'analyzing',
        ]);
        $this->dnaId = $this->dna->id;

        // Dispatch to queue for background processing
        $dnaId = $this->dna->id;
        dispatch(function () use ($url, $teamId, $dnaId) {
            $service = new BusinessDnaService(new WebScraperService());
            $service->analyzeWebsite($url, $teamId, $dnaId);
        })->afterResponse();
    }

    public function pollAnalysis()
    {
        if (!$this->isAnalyzing || !$this->dnaId) return;

        $this->dna = ContentBusinessDna::find($this->dnaId);

        if ($this->dna && in_array($this->dna->status, ['ready', 'failed'])) {
            $this->isAnalyzing = false;
            if ($this->dna->status === 'ready') {
                $this->justCompleted = true;
            }
        }
    }

    // ─── Edit Methods ──────────────────────────────────

    public function openEdit(string $field)
    {
        $this->editingField = $field;

        match ($field) {
            'brand_name' => $this->editBrandName = $this->dna->brand_name ?? '',
            'tagline' => $this->editTagline = $this->dna->tagline ?? '',
            'business_overview' => $this->editOverview = $this->dna->business_overview ?? '',
            'colors' => $this->editColors = $this->dna->colors ?? [],
            'fonts' => $this->editFonts = $this->dna->fonts ?? [],
            'brand_values' => $this->editBrandValues = $this->dna->brand_values ?? [],
            'brand_aesthetic' => $this->editBrandAesthetic = $this->dna->brand_aesthetic ?? [],
            'brand_tone' => $this->editBrandTone = $this->dna->brand_tone ?? [],
            default => null,
        };

        $this->newChipValue = '';
    }

    public function closeEdit()
    {
        $this->editingField = null;
        $this->newChipValue = '';
    }

    public function saveField()
    {
        if (!$this->dna || !$this->editingField) return;

        $updates = match ($this->editingField) {
            'brand_name' => ['brand_name' => $this->editBrandName],
            'tagline' => ['tagline' => $this->editTagline],
            'business_overview' => ['business_overview' => $this->editOverview],
            'colors' => ['colors' => $this->editColors],
            'fonts' => ['fonts' => $this->editFonts],
            'brand_values' => ['brand_values' => $this->editBrandValues],
            'brand_aesthetic' => ['brand_aesthetic' => $this->editBrandAesthetic],
            'brand_tone' => ['brand_tone' => $this->editBrandTone],
            default => [],
        };

        if (!empty($updates)) {
            $this->dna->update($updates);
            $this->dna = $this->dna->fresh();
        }

        $this->closeEdit();
    }

    public function addColor()
    {
        if (!empty($this->newColorHex) && !in_array($this->newColorHex, $this->editColors)) {
            $this->editColors[] = $this->newColorHex;
        }
    }

    public function removeColor(int $index)
    {
        unset($this->editColors[$index]);
        $this->editColors = array_values($this->editColors);
    }

    public function addChip(string $field)
    {
        if (empty($this->newChipValue)) return;

        $prop = match ($field) {
            'brand_values' => 'editBrandValues',
            'brand_aesthetic' => 'editBrandAesthetic',
            'brand_tone' => 'editBrandTone',
            default => null,
        };

        if ($prop && !in_array($this->newChipValue, $this->$prop)) {
            $arr = $this->$prop;
            $arr[] = $this->newChipValue;
            $this->$prop = $arr;
            $this->newChipValue = '';
        }
    }

    public function removeChip(string $field, int $index)
    {
        $prop = match ($field) {
            'brand_values' => 'editBrandValues',
            'brand_aesthetic' => 'editBrandAesthetic',
            'brand_tone' => 'editBrandTone',
            default => null,
        };

        if ($prop) {
            $arr = $this->$prop;
            unset($arr[$index]);
            $this->$prop = array_values($arr);
        }
    }

    public function uploadLogo()
    {
        if (!$this->logoUpload || !$this->dna) return;

        $path = $this->logoUpload->store("content-studio/{$this->dna->team_id}/logo", 'public');
        $this->dna->update(['logo_path' => $path]);
        $this->dna = $this->dna->fresh();
        $this->logoUpload = null;
    }

    public function uploadImages()
    {
        if (empty($this->imageUploads) || !$this->dna) return;

        $images = $this->dna->images ?? [];
        foreach ($this->imageUploads as $upload) {
            $path = $upload->store("content-studio/{$this->dna->team_id}/images", 'public');
            $images[] = ['url' => url('/public/storage/' . $path), 'caption' => '', 'path' => $path];
        }

        $this->dna->update(['images' => $images]);
        $this->dna = $this->dna->fresh();
        $this->imageUploads = [];
    }

    public function resetDna()
    {
        if (!$this->dna) return;

        $this->dna->delete();
        $this->dna = null;
        $this->dnaId = null;
        $this->websiteUrl = '';
        $this->isAnalyzing = false;
    }

    public function render()
    {
        return view('appaicontents::livewire.business-dna');
    }
}
