<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AppVideoWizard\Models\StoryModeProject;

class StoryModeProjects extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all, ready, in_progress, failed

    protected $listeners = [
        'projectDeleted' => '$refresh',
    ];

    /**
     * Set filter and reset pagination.
     */
    public function setFilter(string $filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    /**
     * Delete a project.
     */
    public function deleteProject(int $projectId)
    {
        $project = StoryModeProject::where('id', $projectId)
            ->where('user_id', auth()->id())
            ->first();

        if ($project) {
            $project->deleteWithFiles();
        }
    }

    public function render()
    {
        $query = StoryModeProject::forUser(auth()->id())
            ->with('style')
            ->orderBy('updated_at', 'desc');

        if ($this->filter === 'ready') {
            $query->ready();
        } elseif ($this->filter === 'in_progress') {
            $query->inProgress();
        } elseif ($this->filter === 'failed') {
            $query->where('status', 'failed');
        }

        return view('appvideowizard::livewire.story-mode-projects', [
            'projects' => $query->paginate(12),
        ]);
    }
}
