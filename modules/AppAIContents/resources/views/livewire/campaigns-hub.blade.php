<div>
    {{-- Page Header --}}
    <div class="cs-page-header">
        <div class="cs-page-icon"><i class="fa-light fa-bullhorn"></i></div>
        <h1>{{ __('Campaigns') }}</h1>
        <p>{{ __('Create marketing campaigns powered by your brand identity.') }}</p>
    </div>

    @if(!$dnaId)
        {{-- No DNA yet --}}
        <div class="cs-card" style="max-width: 500px; margin: 40px auto; padding: 40px; text-align: center;">
            <div style="font-size: 36px; color: var(--cs-text-muted); margin-bottom: 12px;">
                <i class="fa-light fa-dna"></i>
            </div>
            <p style="color: var(--cs-text-secondary); margin-bottom: 16px;">
                {{ __('Set up your Business DNA first to start creating campaigns.') }}
            </p>
            <button class="cs-btn cs-btn-primary" wire:click="$dispatch('switch-section', { section: 'dna' })">
                <i class="fa-light fa-arrow-right"></i> {{ __('Set up Business DNA') }}
            </button>
        </div>
    @else
        {{-- ━━━ Prompt Input ━━━ --}}
        <div class="cs-card" style="padding: 24px; margin-bottom: 24px;">
            <textarea class="cs-input cs-input-lg"
                      wire:model.live="prompt"
                      dir="auto"
                      placeholder="{{ __('Describe the campaign you want to create') }}"
                      rows="3"></textarea>

            {{-- Ingredient Buttons --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <div class="cs-ingredients">
                    <div class="cs-ingredient-btn" style="cursor: default; opacity: 0.6;">
                        <i class="fa-light fa-bag-shopping"></i>
                        <span>{{ __('Product') }}</span>
                    </div>
                    <div class="cs-ingredient-btn" style="cursor: default; opacity: 0.6;">
                        <i class="fa-light fa-image"></i>
                        <span>{{ __('Images') }}</span>
                    </div>
                    <div class="cs-ingredient-btn" x-data="{ open: false }" @click="open = !open" style="position: relative;">
                        <i class="fa-light fa-crop-simple"></i>
                        <span>{{ __('Aspect Ratio') }}</span>
                        <i class="fa-light fa-chevron-down" style="font-size: 10px;"></i>

                        <div class="cs-dropdown" x-show="open" @click.outside="open = false" x-transition style="left: 0; right: auto; min-width: 150px;">
                            <div class="cs-dropdown-item {{ $aspectRatio === '9:16' ? 'active' : '' }}" wire:click="setAspectRatio('9:16')" @click="open = false">
                                <i class="fa-light fa-mobile"></i> Story (9:16)
                            </div>
                            <div class="cs-dropdown-item {{ $aspectRatio === '1:1' ? 'active' : '' }}" wire:click="setAspectRatio('1:1')" @click="open = false">
                                <i class="fa-light fa-square"></i> Square (1:1)
                            </div>
                            <div class="cs-dropdown-item {{ $aspectRatio === '4:5' ? 'active' : '' }}" wire:click="setAspectRatio('4:5')" @click="open = false">
                                <i class="fa-light fa-rectangle-vertical"></i> Feed (4:5)
                            </div>
                        </div>
                    </div>
                </div>

                <button class="cs-btn cs-btn-primary"
                        wire:click="generateIdeas"
                        @if(empty(trim($prompt))) disabled @endif
                        wire:loading.attr="disabled">
                    <i class="fa-light fa-sparkles"></i>
                    <span wire:loading.remove wire:target="generateIdeas">
                        {{ empty(trim($prompt)) ? __('Suggest Ideas') : __('Generate Ideas') }}
                    </span>
                    <span wire:loading wire:target="generateIdeas">{{ __('Generating...') }}</span>
                </button>
            </div>

            <div class="cs-disclaimer">{{ __('Content Studio can make mistakes, so double-check it.') }}</div>
        </div>

        {{-- ━━━ Generated Ideas ━━━ --}}
        @if($isGenerating)
            <div class="cs-section-label" style="margin-top: 24px;">{{ __('Generating Ideas...') }}</div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @for($i = 0; $i < 3; $i++)
                    <div class="cs-skeleton" style="height: 80px;"></div>
                @endfor
            </div>
        @elseif(!empty($currentIdeas))
            <div class="cs-section-label" style="margin-top: 24px;">{{ __('Generated Ideas') }}</div>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px;">
                @foreach($currentIdeas as $idea)
                    <div class="cs-idea-card" style="display: flex; justify-content: space-between; align-items: start;">
                        <div wire:click="useCampaignIdea({{ $idea['id'] }})" style="flex: 1; cursor: pointer;">
                            <h3 dir="auto">{{ $idea['title'] }}</h3>
                            <p dir="auto">{{ $idea['description'] }}</p>
                        </div>
                        <button class="cs-btn cs-btn-icon cs-btn-ghost" wire:click="deleteIdea({{ $idea['id'] }})" title="{{ __('Delete') }}">
                            <i class="fa-light fa-trash"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ━━━ Recent Campaigns ━━━ --}}
        @if($campaigns->isNotEmpty())
            <div class="cs-section-label" style="margin-top: 32px;">{{ __('Recent Campaigns') }}</div>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px;">
                @foreach($campaigns as $campaign)
                    <div class="cs-card cs-card-clickable" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px; position: relative;"
                         x-data="{ menuOpen: false }"
                         wire:click="$dispatch('open-campaign', { campaignId: {{ $campaign->id }} })">
                        {{-- Thumbnail --}}
                        @php $firstCreative = $campaign->creatives->first(); @endphp
                        @if($firstCreative && $firstCreative->image_url)
                            <div style="width: 48px; height: 64px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                                <img src="{{ $firstCreative->image_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @else
                            <div style="width: 48px; height: 64px; border-radius: 8px; background: var(--cs-bg-elevated); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-light fa-image" style="color: var(--cs-text-muted);"></i>
                            </div>
                        @endif

                        <div style="flex: 1; min-width: 0;">
                            <div dir="auto" style="font-size: 15px; font-weight: 600; color: var(--cs-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $campaign->title }}
                            </div>
                            <div dir="auto" style="font-size: 13px; color: var(--cs-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $campaign->description }}
                            </div>
                        </div>

                        <div style="flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 12px; color: var(--cs-text-muted);">
                                {{ $campaign->creatives->count() }} {{ __('creatives') }}
                            </span>
                            @if($campaign->status === 'generating')
                                <span class="cs-chip" style="font-size: 11px; padding: 2px 8px;">
                                    <i class="fa-light fa-spinner fa-spin"></i> {{ __('Generating') }}
                                </span>
                            @endif

                            {{-- More options menu --}}
                            <div style="position: relative;">
                                <button class="cs-btn cs-btn-icon cs-btn-ghost"
                                        @click.stop="menuOpen = !menuOpen"
                                        style="width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-light fa-ellipsis-vertical"></i>
                                </button>
                                <div class="cs-dropdown"
                                     x-show="menuOpen"
                                     @click.outside="menuOpen = false"
                                     x-transition
                                     style="right: 0; left: auto; min-width: 120px;">
                                    <div class="cs-dropdown-item"
                                         @click.stop="menuOpen = false; $wire.deleteCampaign({{ $campaign->id }})"
                                         style="color: #ef4444;">
                                        <i class="fa-light fa-trash"></i> {{ __('Delete') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ━━━ DNA Suggestions ━━━ --}}
        @if($dnaSuggestions->isNotEmpty())
            <div class="cs-section-label" style="margin-top: 32px;">{{ __('Suggestions based on Business DNA') }}</div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                @foreach($dnaSuggestions as $suggestion)
                    <div class="cs-idea-card" style="cursor: pointer; display: flex; flex-direction: column; padding: 0; overflow: hidden; position: relative;"
                         x-data="{ dnaMenu: false }">
                        {{-- Title --}}
                        <div wire:click="useCampaignIdea({{ $suggestion->id }})" style="padding: 14px 14px 8px 14px;">
                            <h3 dir="auto" style="margin: 0; font-size: 14px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $suggestion->title }}</h3>
                        </div>

                        {{-- Thumbnail placeholder --}}
                        <div wire:click="useCampaignIdea({{ $suggestion->id }})" style="margin: 0 14px; aspect-ratio: 4/3; border-radius: var(--cs-radius); background: linear-gradient(135deg, #1a1a2e, #0891b2); display: flex; align-items: center; justify-content: center;">
                            <i class="fa-light fa-sparkles" style="font-size: 28px; color: #fff; opacity: 0.7;"></i>
                        </div>

                        {{-- Description --}}
                        <div wire:click="useCampaignIdea({{ $suggestion->id }})" style="padding: 8px 14px 6px 14px; flex: 1;">
                            <p dir="auto" style="margin: 0; font-size: 12px; color: var(--cs-text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">{{ $suggestion->description }}</p>
                        </div>

                        {{-- More menu --}}
                        <div style="padding: 6px 14px 12px 14px; display: flex; justify-content: flex-end;">
                            <div style="position: relative;">
                                <button class="cs-btn cs-btn-icon cs-btn-ghost"
                                        @click.stop="dnaMenu = !dnaMenu"
                                        style="width: 26px; height: 26px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 13px;">
                                    <i class="fa-light fa-ellipsis-vertical"></i>
                                </button>
                                <div class="cs-dropdown"
                                     x-show="dnaMenu"
                                     @click.outside="dnaMenu = false"
                                     x-transition
                                     style="right: 0; left: auto; min-width: 120px; bottom: 100%; top: auto;">
                                    <div class="cs-dropdown-item"
                                         @click.stop="dnaMenu = false; $wire.deleteSuggestion({{ $suggestion->id }})"
                                         style="color: #ef4444;">
                                        <i class="fa-light fa-trash"></i> {{ __('Delete') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
