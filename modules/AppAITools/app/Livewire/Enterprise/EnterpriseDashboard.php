<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;
use Carbon\Carbon;

class EnterpriseDashboard extends Component
{
    public string $activeCategory = 'all';
    public string $activePlatform = 'youtube';
    public string $viewMode = 'dashboard'; // dashboard | grid
    public array $recentActivity = [];

    public function mount()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->loadRecentActivity($teamId);
        }
    }

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
        $this->viewMode = 'grid';
    }

    public function setPlatform(string $platform): void
    {
        $this->activePlatform = $platform;
        $this->activeCategory = 'all';
        $this->viewMode = 'grid';
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function browseAll(): void
    {
        $this->viewMode = 'grid';
        $this->activeCategory = 'all';
    }

    protected function loadRecentActivity(int $teamId): void
    {
        $query = AiToolHistory::forTeam($teamId)
            ->completed()
            ->orderByDesc('created')
            ->limit(5);

        if ($this->activePlatform !== 'all') {
            $query->where('platform', $this->activePlatform);
        }

        $this->recentActivity = $query->get()
            ->map(function ($h) {
                return [
                    'tool'       => $h->tool,
                    'tool_label' => ucfirst(str_replace('_', ' ', $h->tool)),
                    'title'      => $h->title ?? '-',
                    'time_ago'   => Carbon::createFromTimestamp($h->created)->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public function render()
    {
        $tools = config('appaitools.enterprise_tools', []);
        $categories = config('appaitools.enterprise_categories', []);
        $platforms = config('appaitools.enterprise_platforms', []);

        // Filter tools by platform first
        $platformTools = array_filter($tools, fn($t) => ($t['platform'] ?? 'youtube') === $this->activePlatform);

        // Then filter by category
        $filteredTools = $platformTools;
        if ($this->activeCategory !== 'all') {
            $filteredTools = array_filter($platformTools, fn($t) => ($t['category'] ?? '') === $this->activeCategory);
        }

        return view('appaitools::livewire.enterprise.dashboard', [
            'tools' => $tools,
            'platformTools' => $platformTools,
            'filteredTools' => $filteredTools,
            'categories' => $categories,
            'platforms' => $platforms,
            'currentPlatform' => $platforms[$this->activePlatform] ?? $platforms['youtube'],
        ]);
    }
}
