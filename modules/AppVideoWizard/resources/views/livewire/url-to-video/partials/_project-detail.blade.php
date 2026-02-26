{{-- URL-to-Video Project Detail Overlay --}}
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 10100;"
     wire:click.self="closeProject">
    <div class="d-flex gap-4 position-relative" style="max-width: 900px; width: 100%; max-height: 90vh; padding: 20px;">

        {{-- Close Button --}}
        <button wire:click="closeProject" type="button"
                class="btn-close btn-close-white"
                style="position: absolute; top: 8px; right: 8px; z-index: 10110;"></button>

        {{-- Video Player Area --}}
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center"
             style="width: {{ $project->aspect_ratio === '16:9' ? '560px' : ($project->aspect_ratio === '1:1' ? '400px' : '280px') }};">

            @if($project->isReady() && $project->video_url)
                <video controls class="w-100" style="border-radius: 12px; background: #000; max-height: 80vh;"
                       poster="{{ $project->thumbnail_url }}">
                    <source src="{{ $project->video_url }}" type="video/mp4">
                    {{ __('Your browser does not support the video tag.') }}
                </video>
            @elseif($project->isGenerating())
                <div class="text-center w-100 p-4" style="background: #1a1a1a; border-radius: 12px; aspect-ratio: {{ str_replace(':', '/', $project->aspect_ratio) }};">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="mb-3">
                            <i class="fa-light fa-spinner-third fa-spin" style="font-size: 2.5rem; color: #f97316;"></i>
                        </div>
                        <h6 class="text-white mb-2">{{ __('Generating...') }}</h6>
                        <p class="text-muted small mb-3">{{ $project->current_stage ?? 'Processing' }}</p>
                        <div class="w-75">
                            <div class="progress" style="height: 4px; background: #333; border-radius: 2px;">
                                <div class="progress-bar" style="width: {{ $project->progress_percent }}%; background: #f97316; border-radius: 2px;"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ $project->progress_percent }}%</small>
                        </div>
                    </div>
                </div>
            @elseif($project->isFailed())
                <div class="text-center w-100 p-4" style="background: #1a1a1a; border-radius: 12px;">
                    <i class="fa-light fa-triangle-exclamation mb-3" style="font-size: 2.5rem; color: #ef4444;"></i>
                    <h6 class="text-white mb-2">{{ __('Generation Failed') }}</h6>
                    <p class="text-muted small">{{ $project->error_message }}</p>
                </div>
            @else
                <div class="text-center w-100 p-4" style="background: #1a1a1a; border-radius: 12px;">
                    <i class="fa-light fa-film mb-3" style="font-size: 2.5rem; color: #666;"></i>
                    <p class="text-muted">{{ __('No video available yet') }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="flex-grow-1 d-flex flex-column" style="min-width: 260px; max-height: 80vh; overflow-y: auto;">
            <div class="p-4" style="background: #1a1a1a; border-radius: 12px;">

                {{-- Source --}}
                @if($project->source_url)
                    <div class="mb-4">
                        <label class="text-muted small d-block mb-2">{{ __('SOURCE') }}</label>
                        <div class="d-flex align-items-center gap-2">
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
                                $sc = $sourceColors[$project->source_type] ?? '#f97316';
                            @endphp
                            <span class="badge" style="background: {{ $sc }}20; color: {{ $sc }}; font-size: 0.7rem;">
                                {{ str_replace('_', ' ', ucfirst($project->source_type)) }}
                            </span>
                            <a href="{{ $project->source_url }}" target="_blank" rel="noopener"
                               style="font-size: 0.8rem; color: #60a5fa; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 180px;"
                               title="{{ $project->source_url }}">
                                {{ parse_url($project->source_url, PHP_URL_HOST) }}
                                <i class="fa-light fa-arrow-up-right-from-square ms-1" style="font-size: 0.65rem;"></i>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Content Brief Summary --}}
                @if(!empty($project->content_brief['summary']))
                    <div class="mb-4">
                        <label class="text-muted small d-block mb-2">{{ __('SUMMARY') }}</label>
                        <p style="font-size: 0.85rem; color: #ccc; line-height: 1.6; margin: 0;">
                            {{ $project->content_brief['summary'] }}
                        </p>
                    </div>
                @endif

                {{-- Duration & Aspect Ratio --}}
                <div class="d-flex gap-2 mb-4">
                    @if($project->video_duration)
                        <span class="badge" style="background: #2a2a2a; color: #ccc;">
                            {{ gmdate('i:s', $project->video_duration) }}
                        </span>
                    @endif
                    <span class="badge" style="background: #2a2a2a; color: #ccc;">
                        {{ $project->aspect_ratio }}
                    </span>
                    <span class="badge" style="background: {{ $project->isReady() ? '#15803d' : ($project->isFailed() ? '#991b1b' : '#92400e') }}; color: #fff;">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>

                {{-- Transcript --}}
                @if($project->transcript)
                    <div class="mb-4">
                        <label class="text-muted small d-block mb-2">{{ __('TRANSCRIPT') }}</label>
                        <div style="max-height: 250px; overflow-y: auto; font-size: 0.85rem; line-height: 1.7; color: #ccc;">
                            {{ $project->transcript }}
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="d-flex flex-column gap-2">
                    @if($project->isReady() && $project->video_url)
                        <a href="{{ $project->video_url }}" download
                           class="btn w-100" style="background: #f97316; color: #fff; border-radius: 10px;">
                            <i class="fa-light fa-download me-2"></i>{{ __('Download') }}
                        </a>
                    @endif

                    @if($project->user_id === auth()->id())
                        <button wire:click="deleteProject({{ $project->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this project?') }}"
                                type="button"
                                class="btn w-100" style="background: transparent; color: #ef4444; border: 1px solid #333; border-radius: 10px;">
                            <i class="fa-light fa-trash me-2"></i>{{ __('Delete') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
