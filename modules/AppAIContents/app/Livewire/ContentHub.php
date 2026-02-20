<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Modules\AppAIContents\Models\ContentBusinessDna;

class ContentHub extends Component
{
    public string $section = 'dna';
    public ?int $dnaId = null;
    public ?int $activeCampaignId = null;
    public ?int $activeCreativeId = null;

    protected $listeners = [
        'open-campaign' => 'openCampaign',
        'open-editor' => 'openEditor',
        'go-back' => 'goBack',
        'switch-section' => 'switchSection',
    ];

    public function mount()
    {
        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $dna = ContentBusinessDna::where('team_id', $teamId)
            ->where('status', 'ready')
            ->orderByDesc('updated_at')
            ->first();
        $this->dnaId = $dna?->id;

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

    public function switchBusiness(int $dnaId)
    {
        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $dna = ContentBusinessDna::where('id', $dnaId)->where('team_id', $teamId)->first();
        if ($dna) {
            $this->dnaId = $dna->id;
            $this->section = 'dna';
            $this->activeCampaignId = null;
            $this->activeCreativeId = null;
            $this->dispatch('switch-dna', newDnaId: $dna->id)->to('app-ai-contents::business-dna');
        }
    }

    public function newBusiness()
    {
        $this->dnaId = null;
        $this->section = 'dna';
        $this->activeCampaignId = null;
        $this->activeCreativeId = null;
        $this->dispatch('switch-dna', newDnaId: null)->to('app-ai-contents::business-dna');
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
        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $businesses = ContentBusinessDna::where('team_id', $teamId)
            ->whereIn('status', ['ready', 'analyzing'])
            ->orderByDesc('updated_at')
            ->get();

        $activeBusiness = $this->dnaId
            ? $businesses->firstWhere('id', $this->dnaId)
            : null;

        return view('appaicontents::livewire.content-hub', [
            'businesses' => $businesses,
            'activeBusiness' => $activeBusiness,
        ]);
    }
}
