<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Modules\AppAIContents\Models\ContentCreative;
use Modules\AppAIContents\Models\ContentCreativeVersion;
use Modules\AppAIContents\Services\CreativeService;

class CreativeEditor extends Component
{
    public int $creativeId;
    public ?ContentCreative $creative = null;
    public int $currentVersion = 1;
    public int $totalVersions = 1;
    public bool $isFixingLayout = false;
    public ?string $expandedSection = 'header';

    // Editable fields
    public string $headerText = '';
    public string $headerFont = 'Roboto';
    public string $headerColor = '#ffffff';
    public int $headerSize = 40;
    public int $headerHeight = 42;
    public bool $headerVisible = true;

    public string $descriptionText = '';
    public string $descFont = 'Roboto';
    public string $descColor = '#ffffff';
    public int $descSize = 16;
    public int $descHeight = 19;
    public bool $descVisible = true;

    public string $ctaText = '';
    public string $ctaFont = 'Roboto';
    public string $ctaColor = '#ffffff';
    public int $ctaSize = 14;
    public bool $ctaVisible = false;

    public function mount(int $creativeId)
    {
        $this->creativeId = $creativeId;
        $this->loadCreative();
    }

    protected function loadCreative()
    {
        $this->creative = ContentCreative::with('versions')->find($this->creativeId);
        if (!$this->creative) return;

        $this->currentVersion = $this->creative->current_version;
        $this->totalVersions = $this->creative->versions->count();

        // Load fields
        $this->headerText = $this->creative->header_text ?? '';
        $this->headerFont = $this->creative->header_font;
        $this->headerColor = $this->creative->header_color;
        $this->headerSize = $this->creative->header_size;
        $this->headerHeight = $this->creative->header_height;
        $this->headerVisible = $this->creative->header_visible;

        $this->descriptionText = $this->creative->description_text ?? '';
        $this->descFont = $this->creative->desc_font;
        $this->descColor = $this->creative->desc_color;
        $this->descSize = $this->creative->desc_size;
        $this->descHeight = $this->creative->desc_height;
        $this->descVisible = $this->creative->desc_visible;

        $this->ctaText = $this->creative->cta_text ?? '';
        $this->ctaFont = $this->creative->cta_font;
        $this->ctaColor = $this->creative->cta_color;
        $this->ctaSize = $this->creative->cta_size;
        $this->ctaVisible = $this->creative->cta_visible;
    }

    public function updateText(string $field)
    {
        if (!$this->creative) return;

        $updates = match ($field) {
            'header' => ['header_text' => $this->headerText],
            'description' => ['description_text' => $this->descriptionText],
            'cta' => ['cta_text' => $this->ctaText],
            default => [],
        };

        if (!empty($updates)) {
            $this->creative->update($updates);
        }
    }

    public function updateStyle(string $field, string $prop, $value)
    {
        if (!$this->creative) return;

        $column = match (true) {
            $field === 'header' && $prop === 'font' => 'header_font',
            $field === 'header' && $prop === 'color' => 'header_color',
            $field === 'header' && $prop === 'size' => 'header_size',
            $field === 'header' && $prop === 'height' => 'header_height',
            $field === 'description' && $prop === 'font' => 'desc_font',
            $field === 'description' && $prop === 'color' => 'desc_color',
            $field === 'description' && $prop === 'size' => 'desc_size',
            $field === 'description' && $prop === 'height' => 'desc_height',
            $field === 'cta' && $prop === 'font' => 'cta_font',
            $field === 'cta' && $prop === 'color' => 'cta_color',
            $field === 'cta' && $prop === 'size' => 'cta_size',
            default => null,
        };

        if ($column) {
            $this->creative->update([$column => $value]);
        }
    }

    public function toggleVisibility(string $field)
    {
        if (!$this->creative) return;

        match ($field) {
            'header' => $this->headerVisible = !$this->headerVisible,
            'description' => $this->descVisible = !$this->descVisible,
            'cta' => $this->ctaVisible = !$this->ctaVisible,
            default => null,
        };

        $column = match ($field) {
            'header' => 'header_visible',
            'description' => 'desc_visible',
            'cta' => 'cta_visible',
            default => null,
        };

        if ($column) {
            $this->creative->update([$column => match ($field) {
                'header' => $this->headerVisible,
                'description' => $this->descVisible,
                'cta' => $this->ctaVisible,
            }]);
        }
    }

    public function fixLayout()
    {
        if (!$this->creative || $this->isFixingLayout) return;

        $this->isFixingLayout = true;
        $this->totalVersions++;
        $this->currentVersion = $this->totalVersions;

        dispatch(function () {
            $creative = ContentCreative::find($this->creativeId);
            if ($creative) {
                $service = new CreativeService();
                $service->fixLayout($creative);
            }
        })->afterResponse();
    }

    public function pollFixLayout()
    {
        if (!$this->isFixingLayout) return;

        $this->creative = ContentCreative::with('versions')->find($this->creativeId);
        if ($this->creative && $this->creative->versions->count() >= $this->totalVersions) {
            $this->isFixingLayout = false;
            $this->loadCreative();
        }
    }

    public function navigateVersion(string $direction)
    {
        if (!$this->creative) return;

        if ($direction === 'prev' && $this->currentVersion > 1) {
            $this->currentVersion--;
        } elseif ($direction === 'next' && $this->currentVersion < $this->totalVersions) {
            $this->currentVersion++;
        }

        // Load version data
        $version = ContentCreativeVersion::where('creative_id', $this->creativeId)
            ->where('version_number', $this->currentVersion)
            ->first();

        if ($version) {
            // Show version's image in preview (handled by blade)
        }
    }

    public function helpMeWrite(string $field)
    {
        if (!$this->creative) return;

        $service = new CreativeService();
        $text = $service->helpMeWrite($field, $this->creative);

        if ($text) {
            match ($field) {
                'header' => $this->headerText = $text,
                'description' => $this->descriptionText = $text,
                'cta' => $this->ctaText = $text,
                default => null,
            };

            $this->updateText($field);
        }
    }

    public function toggleSection(string $section)
    {
        $this->expandedSection = $this->expandedSection === $section ? null : $section;
    }

    public function download()
    {
        if (!$this->creative || !$this->creative->image_url) return;
        $this->dispatch('download-file', url: $this->creative->image_url);
    }

    public function goBack()
    {
        $this->dispatch('go-back')->to('app-ai-contents::content-hub');
    }

    public function render()
    {
        $versionImage = null;
        if ($this->creative) {
            $version = ContentCreativeVersion::where('creative_id', $this->creativeId)
                ->where('version_number', $this->currentVersion)
                ->first();
            $versionImage = $version?->image_url ?? $this->creative->image_url;
        }

        return view('appaicontents::livewire.creative-editor', [
            'versionImage' => $versionImage,
        ]);
    }
}
