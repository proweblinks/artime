{{-- Project Card - Masonry item --}}
<div class="project-card-wrap" wire:key="project-{{ $project->id }}">
    <div class="project-card" wire:click="openProject({{ $project->id }})">
        {{-- Thumbnail with overlays --}}
        <div class="position-relative" style="background: #f5f7fa;">
            @if($project->thumbnail_url)
                <img src="{{ $project->thumbnail_url }}" alt="{{ $project->title }}"
                     style="width: 100%; display: block; aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }}; object-fit: cover;">
            @elseif($project->isReady() && $project->video_url)
                <video muted preload="metadata"
                       style="width: 100%; display: block; aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }}; object-fit: cover; pointer-events: none;">
                    <source src="{{ $project->video_url }}#t=0.5" type="video/mp4">
                </video>
            @else
                <div class="d-flex align-items-center justify-content-center"
                     style="aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }};">
                    @if($project->isGenerating())
                        <div class="text-center">
                            <i class="fa-light fa-spinner-third fa-spin mb-2" style="font-size: 1.2rem; color: #03fcf4;"></i>
                            <div style="font-size: 0.65rem; color: var(--at-text-muted, #94a0b8);">{{ $project->progress_percent }}%</div>
                        </div>
                    @elseif($project->isFailed())
                        <i class="fa-light fa-triangle-exclamation" style="font-size: 1.2rem; color: #ef4444;"></i>
                    @else
                        <i class="fa-light fa-film" style="font-size: 1.2rem; color: #94a0b8;"></i>
                    @endif
                </div>
            @endif

            {{-- Story Mode Badge --}}
            <div class="position-absolute top-0 start-0 m-2">
                <span style="background: rgba(0,0,0,0.55); color: #03fcf4; font-size: 0.58rem; padding: 3px 7px; border-radius: 4px; backdrop-filter: blur(4px); font-weight: 500;">
                    {{ __('Story Mode') }}
                </span>
            </div>

            {{-- Duration Badge --}}
            @if($project->video_duration)
                <div class="position-absolute bottom-0 end-0 m-2">
                    <span style="background: rgba(0,0,0,0.6); color: #fff; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px;">
                        {{ gmdate('i:s', $project->video_duration) }}
                    </span>
                </div>
            @endif

            {{-- Style name overlay --}}
            @if($project->style)
                <div class="position-absolute bottom-0 start-0 w-100" style="background: linear-gradient(transparent, rgba(0,0,0,0.6)); padding: 20px 8px 6px;">
                    <span style="font-size: 0.7rem; color: #fff;">{{ $project->style->name }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
