<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Modules\AppAIContents\Models\ContentBusinessDna;

class ContentHub extends Component
{
    public string $section = 'dna';
    public bool $sidebarCollapsed = false;
    public ?int $dnaId = null;
    public ?int $activeCampaignId = null;
    public ?int $activeCreativeId = null;

    protected $listeners = [
        'open-campaign' => 'openCampaign',
        'open-editor' => 'openEditor',
        'go-back' => 'goBack',
        'switch-section' => 'switchSection',
        'dna-ready' => 'onDnaReady',
    ];

    public function mount()
    {
        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $dna = ContentBusinessDna::where('team_id', $teamId)->first();
        $this->dnaId = $dna?->id;

        // If no DNA exists, always show DNA section first
        if (!$this->dnaId) {
            $this->section = 'dna';
        }
    }

    public function switchSection(string $section)
    {
        $this->section = $section;
        $this->activeCampaignId = null;
        $this->activeCreativeId = null;
    }

    public function toggleSidebar()
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
    }

    public function openCampaign(int $campaignId)
    {
        $this->activeCampaignId = $campaignId;
        $this->activeCreativeId = null;
        $this->section = 'campaigns';
    }

    public function openEditor(int $creativeId)
    {
        $this->activeCreativeId = $creativeId;
        $this->section = 'campaigns';
    }

    public function goBack()
    {
        if ($this->activeCreativeId) {
            $this->activeCreativeId = null;
        } elseif ($this->activeCampaignId) {
            $this->activeCampaignId = null;
        }
    }

    public function onDnaReady(int $dnaId)
    {
        $this->dnaId = $dnaId;
    }

    public function render()
    {
        return view('appaicontents::livewire.content-hub');
    }
}
