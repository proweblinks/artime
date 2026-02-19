<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Modules\AppAIContents\Models\ContentCampaign;
use Modules\AppAIContents\Models\ContentCreative;
use Modules\AppAIContents\Services\CreativeService;

class CampaignCreatives extends Component
{
    public int $campaignId;
    public ?ContentCampaign $campaign = null;
    public bool $isGenerating = false;
    public ?int $animatingId = null;
    public bool $showAddSheet = false;
    public string $addCreativePrompt = '';
    public ?int $menuOpenId = null;

    public function mount(int $campaignId)
    {
        $this->campaignId = $campaignId;
        $this->campaign = ContentCampaign::with('creatives')->find($campaignId);

        if ($this->campaign && $this->campaign->status === 'generating') {
            $this->isGenerating = true;
        }
    }

    public function pollCreatives()
    {
        $this->campaign = ContentCampaign::with('creatives')->find($this->campaignId);

        if ($this->campaign && $this->campaign->status === 'ready') {
            $this->isGenerating = false;
        }
    }

    public function openEditor(int $creativeId)
    {
        $this->dispatch('open-editor', creativeId: $creativeId)->to('app-ai-contents::content-hub');
    }

    public function animateCreative(int $creativeId, bool $withText = true)
    {
        $this->animatingId = $creativeId;

        dispatch(function () use ($creativeId, $withText) {
            $creative = ContentCreative::find($creativeId);
            if ($creative) {
                $service = new CreativeService();
                $service->animateCreative($creative, $withText);
            }
        })->afterResponse();
    }

    public function duplicateCreative(int $creativeId, string $aspectRatio = 'same')
    {
        $creative = ContentCreative::find($creativeId);
        if (!$creative) return;

        $service = new CreativeService();
        $service->duplicateCreative($creative, $aspectRatio);
        $this->menuOpenId = null;
        $this->campaign = ContentCampaign::with('creatives')->find($this->campaignId);
    }

    public function deleteCreative(int $creativeId)
    {
        ContentCreative::where('id', $creativeId)
            ->where('team_id', auth()->user()->current_team_id ?? auth()->id())
            ->delete();

        $this->menuOpenId = null;
        $this->campaign = ContentCampaign::with('creatives')->find($this->campaignId);
    }

    public function downloadCreative(int $creativeId)
    {
        $creative = ContentCreative::find($creativeId);
        if (!$creative || !$creative->image_url) return;

        $this->dispatch('download-file', url: $creative->image_url);
    }

    public function toggleMenu(int $creativeId)
    {
        $this->menuOpenId = $this->menuOpenId === $creativeId ? null : $creativeId;
    }

    public function addCreative()
    {
        if (empty(trim($this->addCreativePrompt)) || !$this->campaign) return;

        $this->showAddSheet = false;

        dispatch(function () {
            $service = new CreativeService();
            $service->generateCreatives($this->campaign, 1);
        })->afterResponse();

        $this->addCreativePrompt = '';
    }

    public function goBack()
    {
        $this->dispatch('go-back')->to('app-ai-contents::content-hub');
    }

    public function render()
    {
        return view('appaicontents::livewire.campaign-creatives');
    }
}
