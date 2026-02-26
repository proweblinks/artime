{{-- URL-to-Video Project Card --}}
<div wire:key="utv-project-{{ $project->id }}">
    <div class="utv-project-card" wire:click="openProject({{ $project->id }})">
        <div class="position-relative" style="background: #111;">
            @if($project->thumbnail_url)
                <img src="{{ $project->thumbnail_url }}" alt="{{ $project->title }}"
                     style="width: 100%; display: block; aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }}; object-fit: cover;">
            @else
                <div class="d-flex align-items-center justify-content-center"
                     style="aspect-ratio: {{ $project->aspect_ratio === '16:9' ? '16/9' : ($project->aspect_ratio === '1:1' ? '1/1' : '9/16') }};">
                    @if($project->isGenerating())
                        <div class="text-center">
                            <i class="fa-light fa-spinner-third fa-spin mb-2" style="font-size: 1.2rem; color: #f97316;"></i>
                            <div style="font-size: 0.65rem; color: #666;">{{ $project->progress_percent }}%</div>
                        </div>
                    @elseif($project->isFailed())
                        <i class="fa-light fa-triangle-exclamation" style="font-size: 1.2rem; color: #ef4444;"></i>
                    @else
                        <i class="fa-light fa-film" style="font-size: 1.2rem; color: #333;"></i>
                    @endif
                </div>
            @endif

            {{-- Source Type Badge --}}
            @php
                $sourceColors = [
                    'youtube_video' => '#ff0000',
                    'linkedin' => '#0a66c2',
                    'twitter' => '#1da1f2',
                    'news' => '#f59e0b',
                    'newsletter' => '#10b981',
                    'article' => '#f97316',
                    'prompt' => '#8b5cf6',
                ];
                $sourceColor = $sourceColors[$project->source_type] ?? '#f97316';
                $sourceLabel = str_replace('_', ' ', ucfirst($project->source_type ?? 'article'));
            @endphp
            <div class="position-absolute top-0 start-0 m-2">
                <span style="background: rgba(0,0,0,0.55); color: {{ $sourceColor }}; font-size: 0.58rem; padding: 3px 7px; border-radius: 4px; backdrop-filter: blur(4px); font-weight: 500;">
                    {{ $sourceLabel }} {{ __('to video') }}
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

            {{-- Title overlay --}}
            @if($project->title && $project->title !== 'Untitled Video')
                <div class="position-absolute bottom-0 start-0 w-100" style="background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px 8px 6px;">
                    <span style="font-size: 0.7rem; color: #ddd;">{{ Str::limit($project->title, 40) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
