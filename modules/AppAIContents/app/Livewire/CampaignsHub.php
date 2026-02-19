<?php

namespace Modules\AppAIContents\Livewire;

use Livewire\Component;
use Modules\AppAIContents\Models\ContentBusinessDna;
use Modules\AppAIContents\Models\ContentCampaign;
use Modules\AppAIContents\Models\ContentCampaignIdea;
use Modules\AppAIContents\Services\CampaignService;
use Modules\AppAIContents\Services\CreativeService;

class CampaignsHub extends Component
{
    public ?int $dnaId = null;
    public string $prompt = '';
    public string $aspectRatio = '9:16';
    public bool $isGenerating = false;
    public array $currentIdeas = [];

    protected $listeners = ['refresh-campaigns' => '$refresh'];

    public function mount(?int $dnaId = null)
    {
        $this->dnaId = $dnaId;
    }

    public function generateIdeas()
    {
        if (empty(trim($this->prompt)) || !$this->dnaId) return;

        $this->isGenerating = true;

        $dna = ContentBusinessDna::find($this->dnaId);
        if (!$dna) return;

        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $service = new CampaignService();

        try {
            $ideas = $service->generateIdeas($this->prompt, $dna, $teamId);
            $this->currentIdeas = collect($ideas)->map(fn($idea) => [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
            ])->toArray();
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->isGenerating = false;
    }

    public function useCampaignIdea(int $ideaId)
    {
        $idea = ContentCampaignIdea::find($ideaId);
        if (!$idea) return;

        $teamId = auth()->user()->current_team_id ?? auth()->id();
        $service = new CampaignService();
        $campaign = $service->createCampaignFromIdea($idea, $this->aspectRatio, $teamId);

        // Generate creatives in background
        dispatch(function () use ($campaign) {
            $creativeService = new CreativeService();
            $creativeService->generateCreatives($campaign, 4);
        })->afterResponse();

        $this->dispatch('open-campaign', campaignId: $campaign->id)->to('app-ai-contents::content-hub');
    }

    public function deleteIdea(int $ideaId)
    {
        ContentCampaignIdea::where('id', $ideaId)
            ->where('team_id', auth()->user()->current_team_id ?? auth()->id())
            ->delete();

        $this->currentIdeas = array_filter($this->currentIdeas, fn($idea) => $idea['id'] !== $ideaId);
    }

    public function deleteCampaign(int $campaignId)
    {
        ContentCampaign::where('id', $campaignId)
            ->where('team_id', auth()->user()->current_team_id ?? auth()->id())
            ->delete();
    }

    public function setAspectRatio(string $ratio)
    {
        $this->aspectRatio = $ratio;
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id ?? auth()->id();

        $campaigns = ContentCampaign::where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $dnaSuggestions = $this->dnaId
            ? ContentCampaignIdea::where('dna_id', $this->dnaId)
                ->where('is_dna_suggestion', true)
                ->where('status', 'pending')
                ->get()
            : collect();

        return view('appaicontents::livewire.campaigns-hub', [
            'campaigns' => $campaigns,
            'dnaSuggestions' => $dnaSuggestions,
        ]);
    }
}
