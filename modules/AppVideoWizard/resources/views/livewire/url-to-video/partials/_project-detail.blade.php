{{-- URL-to-Video Project Detail Overlay --}}
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;"
     wire:click.self="closeProject">
    <div class="d-flex gap-4 position-relative" style="max-width: 900px; width: 100%; max-height: 90vh; padding: 20px;">

        {{-- Close Button --}}
        <button wire:click="closeProject" type="button"
                class="btn-close"
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
                <div class="text-center w-100 p-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; aspect-ratio: {{ str_replace(':', '/', $project->aspect_ratio) }}; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="mb-3">
                            <i class="fa-light fa-spinner-third fa-spin" style="font-size: 2.5rem; color: #03fcf4;"></i>
                        </div>
                        <h6 style="color: var(--at-text, #1a1a2e);" class="mb-2">{{ __('Generating...') }}</h6>
                        <p class="small mb-3" style="color: var(--at-text-secondary, #5a6178);">{{ $project->current_stage ?? 'Processing' }}</p>
                        <div class="w-75">
                            <div class="progress" style="height: 4px; background: #f1f4f8; border-radius: 2px;">
                                <div class="progress-bar" style="width: {{ $project->progress_percent }}%; background: linear-gradient(90deg, #03fcf4, #00d4cc); border-radius: 2px;"></div>
                            </div>
                            <small class="mt-1 d-block" style="color: var(--at-text-muted, #94a0b8);">{{ $project->progress_percent }}%</small>
                        </div>
                    </div>
                </div>
            @elseif($project->isFailed())
                <div class="text-center w-100 p-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">
                    <i class="fa-light fa-triangle-exclamation mb-3" style="font-size: 2.5rem; color: #ef4444;"></i>
                    <h6 style="color: var(--at-text, #1a1a2e);" class="mb-2">{{ __('Generation Failed') }}</h6>
                    <p class="small" style="color: var(--at-text-secondary, #5a6178);">{{ $project->error_message }}</p>
                </div>
            @else
                <div class="text-center w-100 p-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">
                    <i class="fa-light fa-film mb-3" style="font-size: 2.5rem; color: #94a0b8;"></i>
                    <p style="color: var(--at-text-muted, #94a0b8);">{{ __('No video available yet') }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="flex-grow-1 d-flex flex-column" style="min-width: 260px; max-height: 80vh; overflow-y: auto;">
            <div class="p-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">

                {{-- Source --}}
                @if($project->source_url)
                    <div class="mb-4">
                        <label class="small d-block mb-2" style="color: var(--at-text-muted, #94a0b8);">{{ __('SOURCE') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $sourceColors = [
                                    'youtube_video' => '#ff0000',
                                    'linkedin' => '#0a66c2',
                                    'twitter' => '#1da1f2',
                                    'news' => '#f59e0b',
                                    'newsletter' => '#10b981',
                                    'article' => '#0891b2',
                                    'prompt' => '#8b5cf6',
                                ];
                                $sc = $sourceColors[$project->source_type] ?? '#0891b2';
                            @endphp
                            <span class="badge" style="background: {{ $sc }}15; color: {{ $sc }}; font-size: 0.7rem;">
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
                        <label class="small d-block mb-2" style="color: var(--at-text-muted, #94a0b8);">{{ __('SUMMARY') }}</label>
                        <p style="font-size: 0.85rem; color: var(--at-text-secondary, #5a6178); line-height: 1.6; margin: 0;">
                            {{ $project->content_brief['summary'] }}
                        </p>
                    </div>
                @endif

                {{-- Duration & Aspect Ratio --}}
                <div class="d-flex gap-2 mb-4">
                    @if($project->video_duration)
                        <span class="badge" style="background: #f5f7fa; color: #5a6178;">
                            {{ gmdate('i:s', $project->video_duration) }}
                        </span>
                    @endif
                    <span class="badge" style="background: #f5f7fa; color: #5a6178;">
                        {{ $project->aspect_ratio }}
                    </span>
                    <span class="badge" style="background: {{ $project->isReady() ? '#15803d' : ($project->isFailed() ? '#991b1b' : '#92400e') }}; color: #fff;">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>

                {{-- Transcript --}}
                @if($project->transcript)
                    <div class="mb-4">
                        <label class="small d-block mb-2" style="color: var(--at-text-muted, #94a0b8);">{{ __('TRANSCRIPT') }}</label>
                        <div style="max-height: 250px; overflow-y: auto; font-size: 0.85rem; line-height: 1.7; color: var(--at-text-secondary, #5a6178);">
                            {{ $project->transcript }}
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="d-flex flex-column gap-2">
                    @if($project->isGenerating() && $project->user_id === auth()->id())
                        <button wire:click="cancelProject({{ $project->id }})"
                                wire:confirm="{{ __('Cancel this video generation?') }}"
                                wire:loading.attr="disabled"
                                type="button"
                                class="btn w-100" style="background: #991b1b; color: #fff; border-radius: 10px;">
                            <i class="fa-light fa-xmark me-2"></i>{{ __('Cancel Generation') }}
                        </button>
                    @endif

                    @if($project->isReady() && $project->video_url)
                        <a href="{{ $project->video_url }}" download
                           class="btn w-100" style="background: #03fcf4; color: #0a2e2e; border-radius: 10px;">
                            <i class="fa-light fa-download me-2"></i>{{ __('Download') }}
                        </a>
                    @endif

                    @if($project->user_id === auth()->id() && $project->transcript)
                        <button wire:click="recreateProject({{ $project->id }})"
                                wire:loading.attr="disabled"
                                type="button"
                                class="btn w-100" style="background: transparent; color: #60a5fa; border: 1px solid #eef1f5; border-radius: 10px;">
                            <i class="fa-light fa-rotate-right me-2" wire:loading.class="fa-spinner-third fa-spin" wire:target="recreateProject({{ $project->id }})"></i>
                            <span wire:loading.remove wire:target="recreateProject({{ $project->id }})">{{ __('Create Again') }}</span>
                            <span wire:loading wire:target="recreateProject({{ $project->id }})">{{ __('Preparing...') }}</span>
                        </button>
                    @endif

                    @if($project->user_id === auth()->id())
                        <button wire:click="deleteProject({{ $project->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this project?') }}"
                                type="button"
                                class="btn w-100" style="background: transparent; color: #ef4444; border: 1px solid #eef1f5; border-radius: 10px;">
                            <i class="fa-light fa-trash me-2"></i>{{ __('Delete') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
