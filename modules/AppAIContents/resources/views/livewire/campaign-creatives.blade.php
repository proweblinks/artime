<div
    @if($isGenerating || $animatingId)
        wire:poll.3s="pollCreatives"
    @endif
    x-data="{ showAddSheet: @entangle('showAddSheet') }"
>
    {{-- Back Breadcrumb --}}
    <div class="cs-breadcrumb" wire:click="goBack">
        <i class="fa-light fa-arrow-left"></i>
        {{ __('Back to Campaigns') }}
    </div>

    @if($campaign)
        {{-- Campaign Brief --}}
        <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 24px;">
            <div style="font-size: 20px; color: var(--cs-primary-text);">
                <i class="fa-light fa-bullhorn"></i>
            </div>
            <div>
                <h2 style="font-family: var(--cs-font-serif); font-style: italic; font-size: 22px; margin: 0 0 4px 0;">
                    {{ $campaign->title }}
                </h2>
                <p style="color: var(--cs-text-muted); font-size: 14px; margin: 0;">
                    {{ $campaign->description }}
                </p>
            </div>
        </div>

        {{-- ━━━ Creatives Grid ━━━ --}}
        @if($isGenerating && $campaign->creatives->isEmpty())
            {{-- Skeleton loading --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                @for($i = 0; $i < 4; $i++)
                    <div class="cs-skeleton" style="aspect-ratio: 9/16; border-radius: var(--cs-radius-lg);"></div>
                @endfor
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                @foreach($campaign->creatives as $creative)
                    <div class="cs-creative-card" x-data="{ menuOpen: false }">
                        {{-- Image / Video --}}
                        @if($creative->type === 'video' && $creative->video_url)
                            <div style="position: relative;" x-data="{ playing: false }">
                                <video
                                    x-ref="video{{ $creative->id }}"
                                    src="{{ $creative->video_url }}"
                                    class="cs-creative-image"
                                    loop
                                    muted
                                    playsinline
                                    @click="playing = !playing; playing ? $refs.video{{ $creative->id }}.play() : $refs.video{{ $creative->id }}.pause()"
                                    poster="{{ $creative->image_url }}"
                                ></video>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;" x-show="!playing">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-play" style="color: white; font-size: 18px; margin-left: 3px;"></i>
                                    </div>
                                </div>
                            </div>
                        @elseif($creative->image_url)
                            <img src="{{ $creative->image_url }}" alt="" class="cs-creative-image" wire:click="openEditor({{ $creative->id }})" style="cursor: pointer;">
                        @else
                            <div class="cs-creative-image cs-skeleton" wire:click="openEditor({{ $creative->id }})" style="cursor: pointer;"></div>
                        @endif

                        {{-- More Menu (top-right) --}}
                        <div class="cs-creative-more" style="position: relative;">
                            <button class="cs-btn cs-btn-icon" style="background: rgba(0,0,0,0.4); color: white; border: none; width: 32px; height: 32px; min-width: 32px; border-radius: 50%;"
                                    @click="menuOpen = !menuOpen">
                                <i class="fa-light fa-ellipsis-vertical"></i>
                            </button>

                            <div class="cs-dropdown" x-show="menuOpen" @click.outside="menuOpen = false" x-transition>
                                {{-- Duplicate submenu --}}
                                <div class="cs-dropdown-item" wire:click="duplicateCreative({{ $creative->id }}, 'same')" @click="menuOpen = false">
                                    <i class="fa-light fa-copy"></i> {{ __('Duplicate') }}
                                </div>
                                <div class="cs-dropdown-item" wire:click="duplicateCreative({{ $creative->id }}, '4:5')" @click="menuOpen = false">
                                    <i class="fa-light fa-rectangle-vertical"></i> {{ __('Duplicate as Feed (4:5)') }}
                                </div>
                                <div class="cs-dropdown-item" wire:click="duplicateCreative({{ $creative->id }}, '1:1')" @click="menuOpen = false">
                                    <i class="fa-light fa-square"></i> {{ __('Duplicate as Square (1:1)') }}
                                </div>
                                <div class="cs-dropdown-item" wire:click="downloadCreative({{ $creative->id }})" @click="menuOpen = false">
                                    <i class="fa-light fa-download"></i> {{ __('Download') }}
                                </div>
                                <div class="cs-dropdown-item danger" wire:click="deleteCreative({{ $creative->id }})" @click="menuOpen = false"
                                     wire:confirm="{{ __('Delete this creative?') }}">
                                    <i class="fa-light fa-trash"></i> {{ __('Delete') }}
                                </div>
                            </div>
                        </div>

                        {{-- Bottom Actions --}}
                        <div class="cs-creative-actions">
                            @if($creative->type !== 'video')
                                <button class="cs-btn cs-btn-sm"
                                        style="background: rgba(0,0,0,0.5); color: white; border: none; backdrop-filter: blur(8px); flex: 1;"
                                        wire:click="animateCreative({{ $creative->id }}, true)"
                                        @if($animatingId === $creative->id) disabled @endif>
                                    <i class="fa-light fa-video"></i>
                                    @if($animatingId === $creative->id)
                                        {{ __('Animating...') }}
                                    @else
                                        {{ __('Animate') }}
                                    @endif
                                </button>
                                <button class="cs-btn cs-btn-icon"
                                        style="background: rgba(0,0,0,0.5); color: white; border: none; width: 32px; height: 32px; min-width: 32px; backdrop-filter: blur(8px);"
                                        wire:click="animateCreative({{ $creative->id }}, false)"
                                        title="{{ __('Animate without text') }}"
                                        @if($animatingId === $creative->id) disabled @endif>
                                    <i class="fa-light fa-font-case" style="text-decoration: line-through;"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Add Creative Button --}}
            <div style="text-align: center; margin-top: 24px;">
                <button class="cs-btn cs-btn-primary" @click="showAddSheet = true">
                    <i class="fa-light fa-plus"></i> {{ __('Add Creative') }}
                </button>
            </div>
        @endif
    @endif

    {{-- ━━━ Add Creative Bottom Sheet ━━━ --}}
    <div class="cs-bottom-sheet" :class="{ 'open': showAddSheet }" x-show="showAddSheet" x-transition>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 600; margin: 0;">{{ __('Add Creative') }}</h3>
            <button class="cs-modal-close" @click="showAddSheet = false">
                <i class="fa-light fa-xmark"></i>
            </button>
        </div>

        <textarea class="cs-input" wire:model="addCreativePrompt" placeholder="{{ __('Describe the creative you want to make') }}" rows="3"></textarea>

        <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
            <button class="cs-btn cs-btn-primary" wire:click="addCreative" @click="showAddSheet = false" @if(empty(trim($addCreativePrompt ?? ''))) disabled @endif>
                <i class="fa-light fa-sparkles"></i> {{ __('Suggest Creative') }}
            </button>
        </div>
    </div>

    {{-- Backdrop for bottom sheet --}}
    <div x-show="showAddSheet" @click="showAddSheet = false"
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 99;"
         x-transition.opacity></div>
</div>
