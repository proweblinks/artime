<?php

namespace Modules\AppAITools\Livewire\Enterprise;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;
use Carbon\Carbon;

class EnterpriseDashboard extends Component
{
    public string $activeCategory = 'all';
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
        $this->recentActivity = AiToolHistory::forTeam($teamId)
            ->completed()
            ->orderByDesc('created')
            ->limit(5)
            ->get()
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

        // Filter tools by category
        $filteredTools = $tools;
        if ($this->activeCategory !== 'all') {
            $filteredTools = array_filter($tools, fn($t) => ($t['category'] ?? '') === $this->activeCategory);
        }

        return view('appaitools::livewire.enterprise.dashboard', [
            'tools' => $tools,
            'filteredTools' => $filteredTools,
            'categories' => $categories,
        ]);
    }
}
