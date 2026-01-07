{{-- Project Manager Modal --}}
<div x-data="{
        deleteConfirmId: null,
        deleteConfirmName: '',
        editingProjectId: null,
        editingProjectName: ''
     }"
     x-show="$wire.showProjectManager"
     x-cloak
     class="vw-modal-overlay"
     style="z-index: 1000;"
     @keydown.escape.window="$wire.showProjectManager && $wire.closeProjectManager()">

    {{-- Modal Backdrop --}}
    <div class="vw-modal-backdrop"
         @click="$wire.closeProjectManager()"
         x-show="$wire.showProjectManager"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Modal Content --}}
    <div class="vw-project-manager-modal"
         x-show="$wire.showProjectManager"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.stop>

        {{-- Modal Header --}}
        <div class="vw-pm-header">
            <div class="vw-pm-header-left">
                <h2 class="vw-pm-title">
                    <span class="vw-pm-title-icon">📁</span>
                    {{ __('My Projects') }}
                </h2>
                <span class="vw-pm-count">{{ count($projectManagerProjects ?? []) }} {{ __('projects') }}</span>
            </div>
            <div class="vw-pm-header-right">
                <button type="button"
                        class="vw-pm-new-btn"
                        wire:click="createNewProject"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createNewProject">➕ {{ __('New Project') }}</span>
                    <span wire:loading wire:target="createNewProject">{{ __('Creating...') }}</span>
                </button>
                <button type="button"
                        class="vw-pm-close-btn"
                        @click="$wire.closeProjectManager()">
                    ✕
                </button>
            </div>
        </div>

        {{-- Search and Sort --}}
        <div class="vw-pm-filters">
            <div class="vw-pm-search">
                <span class="vw-pm-search-icon">🔍</span>
                <input type="text"
                       class="vw-pm-search-input"
                       placeholder="{{ __('Search projects...') }}"
                       wire:model.live.debounce.300ms="projectManagerSearch">
            </div>
            <div class="vw-pm-sort">
                <select class="vw-pm-sort-select" wire:model.live="projectManagerSort">
                    <option value="updated_at">{{ __('Last Modified') }}</option>
                    <option value="created_at">{{ __('Date Created') }}</option>
                    <option value="name">{{ __('Name') }}</option>
                </select>
            </div>
        </div>

        {{-- Projects Grid --}}
        <div class="vw-pm-content" wire:loading.class="vw-pm-loading" wire:target="loadProjectManagerProjects">
            @if(empty($projectManagerProjects ?? []))
                {{-- Empty State --}}
                <div class="vw-pm-empty">
                    <div class="vw-pm-empty-icon">📽️</div>
                    <h3 class="vw-pm-empty-title">{{ __('No projects yet') }}</h3>
                    <p class="vw-pm-empty-text">{{ __('Create your first video project to get started!') }}</p>
                    <button type="button"
                            class="vw-pm-empty-btn"
                            wire:click="createNewProject">
                        ➕ {{ __('Create New Project') }}
                    </button>
                </div>
            @else
                <div class="vw-pm-grid">
                    @foreach($projectManagerProjects as $project)
                        @php
                            $isCurrent = $projectId === $project['id'];
                            $platformConfig = config('appvideowizard.platforms.' . ($project['platform'] ?? 'custom'), []);
                            $platformName = $platformConfig['name'] ?? ucfirst($project['platform'] ?? 'Custom');
                            $platformIcon = $platformConfig['icon'] ?? '🎬';
                            $sceneCount = count($project['script']['scenes'] ?? []);
                            $duration = $project['target_duration'] ?? 60;
                            $durationText = $duration >= 60
                                ? floor($duration / 60) . 'm ' . ($duration % 60) . 's'
                                : $duration . 's';
                            $stepsCompleted = $project['stepsCompleted'] ?? 0;
                            $stepLabels = ['Platform', 'Concept', 'Script', 'Storyboard', 'Animation', 'Assembly', 'Export'];
                        @endphp
                        <div class="vw-pm-card {{ $isCurrent ? 'vw-pm-card-current' : '' }}"
                             wire:key="project-{{ $project['id'] }}">

                            {{-- Card Header --}}
                            <div class="vw-pm-card-header">
                                <div class="vw-pm-card-platform">
                                    <span class="vw-pm-card-platform-icon">{{ $platformIcon }}</span>
                                    <span class="vw-pm-card-platform-name">{{ $platformName }}</span>
                                </div>
                                <div class="vw-pm-card-status vw-pm-status-{{ $project['status'] ?? 'draft' }}">
                                    {{ ucfirst($project['status'] ?? 'draft') }}
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="vw-pm-card-body">
                                {{-- Inline Edit Title --}}
                                <div x-show="editingProjectId === {{ $project['id'] }}" class="vw-pm-card-title-edit">
                                    <input type="text"
                                           x-model="editingProjectName"
                                           @keydown.enter="$wire.renameProject({{ $project['id'] }}, editingProjectName); editingProjectId = null"
                                           @keydown.escape="editingProjectId = null"
                                           @click.outside="editingProjectId = null"
                                           class="vw-pm-card-title-input"
                                           x-ref="renameInput{{ $project['id'] }}"
                                           @focus="$el.select()">
                                    <div class="vw-pm-card-title-actions">
                                        <button type="button"
                                                class="vw-pm-card-title-btn vw-pm-card-title-save"
                                                @click="$wire.renameProject({{ $project['id'] }}, editingProjectName); editingProjectId = null">
                                            ✓
                                        </button>
                                        <button type="button"
                                                class="vw-pm-card-title-btn vw-pm-card-title-cancel"
                                                @click="editingProjectId = null">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                                {{-- Display Title --}}
                                <h3 x-show="editingProjectId !== {{ $project['id'] }}"
                                    class="vw-pm-card-title vw-pm-card-title-editable"
                                    @click="editingProjectId = {{ $project['id'] }}; editingProjectName = '{{ addslashes($project['name'] ?? 'Untitled') }}'; $nextTick(() => $refs.renameInput{{ $project['id'] }}?.focus())"
                                    title="{{ __('Click to rename') }}">
                                    {{ $project['name'] ?? __('Untitled Project') }}
                                    <span class="vw-pm-card-title-edit-icon">✏️</span>
                                </h3>
                                @if($isCurrent)
                                    <span class="vw-pm-card-current-badge">{{ __('Currently Open') }}</span>
                                @endif
                            </div>

                            {{-- Card Meta --}}
                            <div class="vw-pm-card-meta">
                                <div class="vw-pm-card-meta-item">
                                    <span class="vw-pm-card-meta-icon">🎬</span>
                                    <span>{{ $sceneCount }} {{ __('scenes') }}</span>
                                </div>
                                <div class="vw-pm-card-meta-item">
                                    <span class="vw-pm-card-meta-icon">⏱️</span>
                                    <span>{{ $durationText }}</span>
                                </div>
                                <div class="vw-pm-card-meta-item">
                                    <span class="vw-pm-card-meta-icon">📅</span>
                                    <span>{{ \Carbon\Carbon::parse($project['updated_at'])->diffForHumans() }}</span>
                                </div>
                            </div>

                            {{-- Step Progress --}}
                            <div class="vw-pm-card-progress">
                                <div class="vw-pm-card-progress-label">
                                    <span>{{ __('Progress') }}</span>
                                    <span>{{ $stepsCompleted }}/7</span>
                                </div>
                                <div class="vw-pm-card-progress-bar">
                                    @for($i = 1; $i <= 7; $i++)
                                        <div class="vw-pm-card-progress-step {{ $i <= $stepsCompleted ? 'completed' : '' }}"
                                             title="{{ $stepLabels[$i - 1] ?? 'Step ' . $i }}">
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            {{-- Card Actions --}}
                            <div class="vw-pm-card-actions">
                                @if($isCurrent)
                                    <button type="button"
                                            class="vw-pm-card-btn vw-pm-card-btn-current"
                                            disabled>
                                        ✓ {{ __('Open') }}
                                    </button>
                                @else
                                    <button type="button"
                                            class="vw-pm-card-btn vw-pm-card-btn-load"
                                            wire:click="loadProjectFromManager({{ $project['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="loadProjectFromManager({{ $project['id'] }})">
                                        <span wire:loading.remove wire:target="loadProjectFromManager({{ $project['id'] }})">📂 {{ __('Load') }}</span>
                                        <span wire:loading wire:target="loadProjectFromManager({{ $project['id'] }})">{{ __('Loading...') }}</span>
                                    </button>
                                @endif
                                <button type="button"
                                        class="vw-pm-card-btn vw-pm-card-btn-duplicate"
                                        wire:click="duplicateProject({{ $project['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="duplicateProject({{ $project['id'] }})"
                                        title="{{ __('Duplicate project') }}">
                                    <span wire:loading.remove wire:target="duplicateProject({{ $project['id'] }})">📋</span>
                                    <span wire:loading wire:target="duplicateProject({{ $project['id'] }})">⏳</span>
                                </button>
                                <button type="button"
                                        class="vw-pm-card-btn vw-pm-card-btn-delete"
                                        @click="deleteConfirmId = {{ $project['id'] }}; deleteConfirmName = '{{ addslashes($project['name'] ?? 'Untitled') }}'"
                                        title="{{ __('Delete project') }}">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @php
                    $totalPages = ceil($projectManagerTotal / $projectManagerPerPage);
                @endphp
                @if($totalPages > 1)
                    <div class="vw-pm-pagination">
                        <div class="vw-pm-pagination-info">
                            {{ __('Showing') }} {{ (($projectManagerPage - 1) * $projectManagerPerPage) + 1 }}-{{ min($projectManagerPage * $projectManagerPerPage, $projectManagerTotal) }} {{ __('of') }} {{ $projectManagerTotal }}
                        </div>
                        <div class="vw-pm-pagination-controls">
                            <button type="button"
                                    class="vw-pm-pagination-btn"
                                    wire:click="projectManagerPrevPage"
                                    {{ $projectManagerPage <= 1 ? 'disabled' : '' }}>
                                ←
                            </button>
                            @for($p = max(1, $projectManagerPage - 2); $p <= min($totalPages, $projectManagerPage + 2); $p++)
                                <button type="button"
                                        class="vw-pm-pagination-btn {{ $p === $projectManagerPage ? 'active' : '' }}"
                                        wire:click="projectManagerGoToPage({{ $p }})">
                                    {{ $p }}
                                </button>
                            @endfor
                            <button type="button"
                                    class="vw-pm-pagination-btn"
                                    wire:click="projectManagerNextPage"
                                    {{ $projectManagerPage >= $totalPages ? 'disabled' : '' }}>
                                →
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Loading Overlay --}}
        <div class="vw-pm-loading-overlay"
             wire:loading.flex
             wire:target="loadProjectFromManager, deleteProjectFromManager, createNewProject, duplicateProject, renameProject">
            <div class="vw-pm-loading-spinner"></div>
            <span class="vw-pm-loading-text">{{ __('Please wait...') }}</span>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <template x-if="deleteConfirmId !== null">
        <div class="vw-pm-confirm-overlay" @click="deleteConfirmId = null">
            <div class="vw-pm-confirm-modal" @click.stop>
                <div class="vw-pm-confirm-icon">⚠️</div>
                <h3 class="vw-pm-confirm-title">{{ __('Delete Project?') }}</h3>
                <p class="vw-pm-confirm-text">
                    {{ __('Are you sure you want to delete') }} "<span x-text="deleteConfirmName"></span>"?
                    {{ __('This action cannot be undone.') }}
                </p>
                <div class="vw-pm-confirm-actions">
                    <button type="button"
                            class="vw-pm-confirm-btn vw-pm-confirm-btn-cancel"
                            @click="deleteConfirmId = null">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button"
                            class="vw-pm-confirm-btn vw-pm-confirm-btn-delete"
                            @click="$wire.deleteProjectFromManager(deleteConfirmId); deleteConfirmId = null"
                            wire:loading.attr="disabled">
                        🗑️ {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
/* Project Manager Modal Styles */
.vw-modal-overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.vw-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.vw-project-manager-modal {
    position: relative;
    width: 100%;
    max-width: 900px;
    max-height: 85vh;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e3a5f 100%);
    border-radius: 1rem;
    border: 1px solid rgba(139, 92, 246, 0.3);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Header */
.vw-pm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    background: rgba(0, 0, 0, 0.2);
}

.vw-pm-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.vw-pm-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vw-pm-title-icon {
    font-size: 1.5rem;
}

.vw-pm-count {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    background: rgba(139, 92, 246, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
}

.vw-pm-header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vw-pm-new-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
    color: #fff;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-new-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

.vw-pm-new-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.vw-pm-close-btn {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

/* Filters */
.vw-pm-filters {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(139, 92, 246, 0.1);
}

.vw-pm-search {
    flex: 1;
    position: relative;
}

.vw-pm-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.875rem;
    opacity: 0.6;
}

.vw-pm-search-input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.25rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 0.5rem;
    color: #fff;
    font-size: 0.875rem;
}

.vw-pm-search-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.vw-pm-search-input:focus {
    outline: none;
    border-color: rgba(139, 92, 246, 0.5);
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1);
}

.vw-pm-sort-select {
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 0.5rem;
    color: #fff;
    font-size: 0.875rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1rem;
}

.vw-pm-sort-select:focus {
    outline: none;
    border-color: rgba(139, 92, 246, 0.5);
}

/* Content */
.vw-pm-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    position: relative;
}

.vw-pm-content.vw-pm-loading {
    opacity: 0.5;
    pointer-events: none;
}

/* Empty State */
.vw-pm-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
}

.vw-pm-empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.vw-pm-empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 0.5rem 0;
}

.vw-pm-empty-text {
    color: rgba(255, 255, 255, 0.6);
    margin: 0 0 1.5rem 0;
}

.vw-pm-empty-btn {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
    color: #fff;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

/* Grid */
.vw-pm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

/* Card */
.vw-pm-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.2s;
}

.vw-pm-card:hover {
    border-color: rgba(139, 92, 246, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.vw-pm-card-current {
    border-color: rgba(16, 185, 129, 0.5);
    background: rgba(16, 185, 129, 0.1);
}

.vw-pm-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.2);
}

.vw-pm-card-platform {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
}

.vw-pm-card-platform-icon {
    font-size: 1rem;
}

.vw-pm-card-status {
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.vw-pm-status-draft {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.vw-pm-status-in_progress {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.vw-pm-status-processing {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.vw-pm-status-complete {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.vw-pm-status-completed {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.vw-pm-status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.vw-pm-card-body {
    padding: 1rem;
}

.vw-pm-card-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 0.5rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vw-pm-card-title-editable {
    cursor: pointer;
    position: relative;
    padding-right: 1.5rem;
}

.vw-pm-card-title-editable:hover {
    color: #a78bfa;
}

.vw-pm-card-title-edit-icon {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.vw-pm-card-title-editable:hover .vw-pm-card-title-edit-icon {
    opacity: 0.7;
}

.vw-pm-card-title-edit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.vw-pm-card-title-input {
    flex: 1;
    padding: 0.375rem 0.5rem;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(139, 92, 246, 0.4);
    border-radius: 0.375rem;
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 600;
}

.vw-pm-card-title-input:focus {
    outline: none;
    border-color: rgba(139, 92, 246, 0.7);
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
}

.vw-pm-card-title-actions {
    display: flex;
    gap: 0.25rem;
}

.vw-pm-card-title-btn {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-card-title-save {
    background: rgba(16, 185, 129, 0.3);
    color: #10b981;
}

.vw-pm-card-title-save:hover {
    background: rgba(16, 185, 129, 0.5);
}

.vw-pm-card-title-cancel {
    background: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.vw-pm-card-title-cancel:hover {
    background: rgba(239, 68, 68, 0.5);
}

.vw-pm-card-current-badge {
    display: inline-block;
    font-size: 0.6875rem;
    color: #10b981;
    background: rgba(16, 185, 129, 0.2);
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
}

.vw-pm-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.1);
    border-top: 1px solid rgba(139, 92, 246, 0.1);
}

.vw-pm-card-meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.vw-pm-card-meta-icon {
    font-size: 0.75rem;
}

/* Step Progress */
.vw-pm-card-progress {
    padding: 0.75rem 1rem;
    border-top: 1px solid rgba(139, 92, 246, 0.1);
}

.vw-pm-card-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.6875rem;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 0.375rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vw-pm-card-progress-bar {
    display: flex;
    gap: 3px;
}

.vw-pm-card-progress-step {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    transition: all 0.2s;
}

.vw-pm-card-progress-step.completed {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
}

/* Pagination */
.vw-pm-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    margin-top: 1rem;
    border-top: 1px solid rgba(139, 92, 246, 0.1);
}

.vw-pm-pagination-info {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.6);
}

.vw-pm-pagination-controls {
    display: flex;
    gap: 0.375rem;
}

.vw-pm-pagination-btn {
    min-width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 0.375rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8125rem;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-pagination-btn:hover:not(:disabled) {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
    color: #fff;
}

.vw-pm-pagination-btn.active {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
    border-color: transparent;
    color: #fff;
}

.vw-pm-pagination-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.vw-pm-card-actions {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid rgba(139, 92, 246, 0.1);
}

.vw-pm-card-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-card-btn-load {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.vw-pm-card-btn-load:hover {
    background: rgba(139, 92, 246, 0.3);
}

.vw-pm-card-btn-load:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.vw-pm-card-btn-current {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    cursor: default;
}

.vw-pm-card-btn-duplicate {
    flex: 0;
    width: 2.25rem;
    background: rgba(6, 182, 212, 0.1);
    color: rgba(6, 182, 212, 0.7);
}

.vw-pm-card-btn-duplicate:hover {
    background: rgba(6, 182, 212, 0.2);
    color: #06b6d4;
}

.vw-pm-card-btn-duplicate:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.vw-pm-card-btn-delete {
    flex: 0;
    width: 2.25rem;
    background: rgba(239, 68, 68, 0.1);
    color: rgba(239, 68, 68, 0.7);
}

.vw-pm-card-btn-delete:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

/* Loading Overlay */
.vw-pm-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    z-index: 10;
}

.vw-pm-loading-spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 3px solid rgba(139, 92, 246, 0.2);
    border-top-color: #8b5cf6;
    border-radius: 50%;
    animation: vw-pm-spin 0.8s linear infinite;
}

@keyframes vw-pm-spin {
    to { transform: rotate(360deg); }
}

.vw-pm-loading-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.875rem;
}

/* Delete Confirmation */
.vw-pm-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1100;
}

.vw-pm-confirm-modal {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.75rem;
    padding: 1.5rem;
    max-width: 400px;
    text-align: center;
}

.vw-pm-confirm-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.vw-pm-confirm-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 0.75rem 0;
}

.vw-pm-confirm-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    margin: 0 0 1.5rem 0;
    line-height: 1.5;
}

.vw-pm-confirm-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.vw-pm-confirm-btn {
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.vw-pm-confirm-btn-cancel {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
}

.vw-pm-confirm-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.2);
}

.vw-pm-confirm-btn-delete {
    background: rgba(239, 68, 68, 0.8);
    color: #fff;
}

.vw-pm-confirm-btn-delete:hover {
    background: #ef4444;
}

/* Responsive */
@media (max-width: 640px) {
    .vw-project-manager-modal {
        max-height: 90vh;
    }

    .vw-pm-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .vw-pm-header-right {
        width: 100%;
        justify-content: space-between;
    }

    .vw-pm-filters {
        flex-direction: column;
    }

    .vw-pm-grid {
        grid-template-columns: 1fr;
    }
}
</style>
