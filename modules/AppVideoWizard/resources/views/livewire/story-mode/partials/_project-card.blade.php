{{-- Project Card - Gallery item --}}
<div class="col-6 col-md-4 col-lg-3">
    <div class="project-card" wire:click="openProject({{ $project->id }})">
        {{-- Thumbnail --}}
        <div class="position-relative" style="aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }}; background: #111; overflow: hidden;">
            @if($project->thumbnail_url)
                <img src="{{ $project->thumbnail_url }}" alt="{{ $project->title }}"
                     class="w-100 h-100" style="object-fit: cover;">
            @else
                <div class="d-flex align-items-center justify-content-center h-100">
                    @if($project->isGenerating())
                        <div class="text-center">
                            <i class="fa-light fa-spinner-third fa-spin mb-2" style="font-size: 1.5rem; color: #f97316;"></i>
                            <div style="font-size: 0.7rem; color: #888;">{{ $project->progress_percent }}%</div>
                        </div>
                    @elseif($project->isFailed())
                        <i class="fa-light fa-triangle-exclamation" style="font-size: 1.5rem; color: #ef4444;"></i>
                    @else
                        <i class="fa-light fa-film" style="font-size: 1.5rem; color: #444;"></i>
                    @endif
                </div>
            @endif

            {{-- Status Badge --}}
            <div class="position-absolute top-0 start-0 m-2">
                <span class="badge" style="background: rgba(0,0,0,0.6); color: #f97316; font-size: 0.6rem; backdrop-filter: blur(4px);">
                    {{ __('Story Mode') }}
                </span>
            </div>

            {{-- Duration Badge --}}
            @if($project->video_duration)
                <div class="position-absolute bottom-0 end-0 m-2">
                    <span class="badge" style="background: rgba(0,0,0,0.7); color: #fff; font-size: 0.65rem;">
                        {{ gmdate('i:s', $project->video_duration) }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="p-2">
            <div class="text-truncate" style="font-size: 0.8rem; color: #ddd;">
                {{ $project->title }}
            </div>
            @if($project->style)
                <small class="text-muted" style="font-size: 0.7rem;">{{ $project->style->name }}</small>
            @endif
        </div>
    </div>
</div>
