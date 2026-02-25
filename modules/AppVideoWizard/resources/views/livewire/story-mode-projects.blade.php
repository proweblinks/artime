<div>
    {{-- Filter Tabs --}}
    <div class="d-flex gap-2 mb-4">
        @foreach(['all' => 'All', 'ready' => 'Completed', 'in_progress' => 'In Progress', 'failed' => 'Failed'] as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" type="button"
                    class="btn btn-sm"
                    style="background: {{ $filter === $key ? '#f97316' : '#2a2a2a' }}; color: {{ $filter === $key ? '#fff' : '#ccc' }}; border-radius: 8px; border: none; font-size: 0.8rem;">
                {{ __($label) }}
            </button>
        @endforeach
    </div>

    {{-- Projects Grid --}}
    @if($projects->isNotEmpty())
        <div class="row g-3">
            @foreach($projects as $project)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="project-card" style="background: #1a1a1a; border-radius: 12px; overflow: hidden;">
                        {{-- Thumbnail --}}
                        <div class="position-relative" style="aspect-ratio: 9/16; background: #111;">
                            @if($project->thumbnail_url)
                                <img src="{{ $project->thumbnail_url }}" alt="{{ $project->title }}"
                                     class="w-100 h-100" style="object-fit: cover;">
                            @else
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <i class="fa-light fa-film" style="font-size: 1.5rem; color: #444;"></i>
                                </div>
                            @endif

                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge" style="background: rgba(0,0,0,0.6); color: #f97316; font-size: 0.6rem;">
                                    {{ __('Story Mode') }}
                                </span>
                            </div>

                            <span class="position-absolute bottom-0 end-0 m-2 badge"
                                  style="background: {{ $project->isReady() ? '#15803d' : ($project->isFailed() ? '#991b1b' : '#92400e') }}; font-size: 0.6rem;">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </div>

                        <div class="p-2 d-flex align-items-center justify-content-between">
                            <div class="text-truncate" style="font-size: 0.8rem; color: #ddd;">{{ $project->title }}</div>
                            <button wire:click="deleteProject({{ $project->id }})"
                                    wire:confirm="{{ __('Delete this project?') }}"
                                    type="button" class="btn btn-sm border-0 p-0 ms-2" style="color: #666;">
                                <i class="fa-light fa-trash" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $projects->links() }}
        </div>
    @else
        <div class="text-center py-5">
            <i class="fa-light fa-film mb-3" style="font-size: 3rem; color: #333;"></i>
            <p class="text-muted">{{ __('No projects yet. Create your first story!') }}</p>
        </div>
    @endif
</div>
