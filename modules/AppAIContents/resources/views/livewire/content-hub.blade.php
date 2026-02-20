<div class="cs-app" x-data @dna-ready.window="$wire.onDnaReady($event.detail.dnaId)">
    @include('appaicontents::livewire.partials._design-system')

    {{-- Gradient Mesh Background --}}
    <div class="cs-mesh-bg" aria-hidden="true">
        <div class="cs-mesh-blob cs-mesh-blob--cyan"></div>
        <div class="cs-mesh-blob cs-mesh-blob--teal"></div>
        <div class="cs-mesh-blob cs-mesh-blob--blue"></div>
    </div>

    <div class="cs-layout-vertical">
        {{-- ━━━ Top Navigation Bar ━━━ --}}
        <header class="cs-topbar" x-data="{ businessOpen: false }">
            <div class="cs-topbar-inner">
                {{-- Business Selector (left) --}}
                <div class="cs-business-selector" @click.outside="businessOpen = false">
                    @if($activeBusiness)
                        <button class="cs-business-btn" @click="businessOpen = !businessOpen">
                            @if($activeBusiness->logo_path)
                                <img src="{{ url('/public/storage/' . $activeBusiness->logo_path) }}" alt="" class="cs-business-logo">
                            @else
                                <div class="cs-business-logo-placeholder">
                                    {{ strtoupper(substr($activeBusiness->brand_name ?? 'B', 0, 1)) }}
                                </div>
                            @endif
                            <span class="cs-business-name" dir="auto">{{ $activeBusiness->brand_name ?? 'My Business' }}</span>
                            <i class="fa-light fa-chevron-down" style="font-size: 10px; color: var(--cs-text-muted);"></i>
                        </button>
                    @else
                        <button class="cs-business-btn" @click="businessOpen = !businessOpen">
                            <div class="cs-business-logo-placeholder">
                                <i class="fa-light fa-plus" style="font-size: 12px;"></i>
                            </div>
                            <span class="cs-business-name">{{ __('New Business') }}</span>
                        </button>
                    @endif

                    {{-- Dropdown --}}
                    <div class="cs-business-dropdown" x-show="businessOpen" x-transition>
                        @foreach($businesses as $biz)
                            <div class="cs-business-dropdown-item {{ $biz->id === $dnaId ? 'active' : '' }}"
                                 wire:click="switchBusiness({{ $biz->id }})" @click="businessOpen = false">
                                @if($biz->logo_path)
                                    <img src="{{ url('/public/storage/' . $biz->logo_path) }}" alt="" class="cs-business-mini-logo">
                                @else
                                    <div class="cs-business-mini-logo-placeholder">
                                        {{ strtoupper(substr($biz->brand_name ?? 'B', 0, 1)) }}
                                    </div>
                                @endif
                                <div style="flex: 1; min-width: 0;">
                                    <div dir="auto" style="font-weight: 500; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $biz->brand_name ?? 'Untitled' }}</div>
                                    <div style="font-size: 11px; color: var(--cs-text-muted);">{{ $biz->website_url }}</div>
                                </div>
                                @if($biz->id === $dnaId)
                                    <i class="fa-solid fa-check" style="font-size: 12px; color: var(--cs-primary-text);"></i>
                                @endif
                            </div>
                        @endforeach

                        @if($businesses->count() > 0)
                            <div style="border-top: 1px solid var(--cs-border-strong); margin: 4px 0;"></div>
                        @endif

                        <div class="cs-business-dropdown-item" wire:click="newBusiness" @click="businessOpen = false">
                            <div class="cs-business-mini-logo-placeholder" style="background: var(--cs-primary-soft); color: var(--cs-primary-text);">
                                <i class="fa-light fa-plus" style="font-size: 10px;"></i>
                            </div>
                            <span style="font-weight: 500; font-size: 13px; color: var(--cs-primary-text);">{{ __('New Business') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Section Tabs (center) --}}
                <nav class="cs-tabs">
                    <button class="cs-tab {{ $section === 'dna' ? 'active' : '' }}"
                            wire:click="switchSection('dna')">
                        <i class="fa-light fa-dna"></i>
                        {{ __('Business DNA') }}
                    </button>
                    <button class="cs-tab {{ $section === 'campaigns' ? 'active' : '' }}"
                            wire:click="switchSection('campaigns')"
                            @if(!$dnaId) disabled @endif>
                        <i class="fa-light fa-bullhorn"></i>
                        {{ __('Campaigns') }}
                    </button>
                    <button class="cs-tab {{ $section === 'photoshoot' ? 'active' : '' }}"
                            wire:click="switchSection('photoshoot')"
                            @if(!$dnaId) disabled @endif>
                        <i class="fa-light fa-camera-retro"></i>
                        {{ __('Photoshoot') }}
                    </button>
                </nav>

                {{-- New Business button (right) --}}
                <button class="cs-btn cs-btn-secondary cs-btn-sm" wire:click="newBusiness" title="{{ __('New Business') }}">
                    <i class="fa-light fa-plus"></i>
                    <span class="cs-topbar-btn-label">{{ __('New Business') }}</span>
                </button>
            </div>
        </header>

        {{-- ━━━ Main Content ━━━ --}}
        <main class="cs-main-full">
            <div class="cs-content">
                @if($section === 'dna')
                    @livewire('app-ai-contents::business-dna', ['dnaId' => $dnaId], key('dna-section'))

                @elseif($section === 'campaigns')
                    @if($activeCreativeId)
                        @livewire('app-ai-contents::creative-editor', ['creativeId' => $activeCreativeId], key('editor-' . $activeCreativeId))
                    @elseif($activeCampaignId)
                        @livewire('app-ai-contents::campaign-creatives', ['campaignId' => $activeCampaignId], key('creatives-' . $activeCampaignId))
                    @else
                        @livewire('app-ai-contents::campaigns-hub', ['dnaId' => $dnaId], key('campaigns-hub-' . $dnaId))
                    @endif

                @elseif($section === 'photoshoot')
                    @livewire('app-ai-contents::photoshoot-hub', ['dnaId' => $dnaId], key('photoshoot-hub-' . $dnaId))
                @endif
            </div>
        </main>
    </div>
</div>
