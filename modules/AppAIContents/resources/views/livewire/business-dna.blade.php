<div
    @if($isAnalyzing)
        wire:poll.5s="pollAnalysis"
    @endif
>
    {{-- Page Header --}}
    <div class="cs-page-header">
        <div class="cs-page-icon"><i class="fa-light fa-dna"></i></div>
        <h1>{{ __('Your Business DNA') }}</h1>
        <p>{{ __('Your brand identity powers every campaign and creative we generate.') }}</p>
    </div>

    @if(!$dna || $dna->status === 'pending' || (!$isAnalyzing && !$dna->brand_name))
        {{-- ━━━ Onboarding: Enter Website URL ━━━ --}}
        <div class="cs-card" style="max-width: 600px; margin: 40px auto; padding: 48px 40px; text-align: center;">
            <div style="font-size: 48px; color: var(--cs-primary-text); margin-bottom: 16px;">
                <i class="fa-light fa-globe"></i>
            </div>
            <h2 style="font-family: var(--cs-font-serif); font-style: italic; font-size: 22px; margin-bottom: 8px; color: var(--cs-text);">
                {{ __("Let's get to know your brand") }}
            </h2>
            <p style="color: var(--cs-text-muted); font-size: 14px; margin-bottom: 24px;">
                {{ __('Enter your website URL and we\'ll analyze your brand identity automatically.') }}
            </p>

            <div style="display: flex; gap: 8px; max-width: 500px; margin: 0 auto;">
                <input type="url"
                    class="cs-input"
                    wire:model.live="websiteUrl"
                    wire:keydown.enter="analyzeSite"
                    placeholder="https://yourwebsite.com"
                    style="flex: 1;">
                <button class="cs-btn cs-btn-primary" wire:click="analyzeSite" @if(empty($websiteUrl)) disabled @endif>
                    <i class="fa-light fa-sparkles"></i>
                    {{ __('Analyze') }}
                </button>
            </div>
        </div>

    @elseif($isAnalyzing)
        {{-- ━━━ Analyzing State ━━━ --}}
        <div class="cs-card" style="max-width: 500px; margin: 40px auto; padding: 48px 40px; text-align: center;">
            <div style="font-size: 40px; color: var(--cs-primary-text); margin-bottom: 16px;">
                <i class="fa-light fa-dna fa-spin-pulse"></i>
            </div>
            <h2 style="font-family: var(--cs-font-serif); font-style: italic; font-size: 20px; margin-bottom: 8px;">
                {{ __('Analyzing your website...') }}
            </h2>
            <p style="color: var(--cs-text-muted); font-size: 14px; margin-bottom: 24px;">
                {{ __('We\'re extracting your brand identity. This may take a few minutes.') }}
            </p>
            <div class="cs-progress" style="max-width: 300px; margin: 0 auto;">
                <div class="cs-progress-bar" style="width: 60%; animation: cs-shimmer 2s ease-in-out infinite;"></div>
            </div>
            <div style="color: var(--cs-text-muted); font-size: 12px; margin-top: 12px;">
                {{ $dna->website_url ?? '' }}
            </div>
        </div>

    @else
        {{-- ━━━ DNA Display (Pomelli-style two-column layout) ━━━ --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            {{-- LEFT COLUMN: Brand attributes --}}
            <div style="display: flex; flex-direction: column; gap: 16px;">

                {{-- Brand Name --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_name')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="cs-section-label">{{ __('Brand Name') }}</div>
                            <div style="font-size: 20px; font-weight: 600; color: var(--cs-text);">
                                {{ $dna->brand_name ?: __('Not set') }}
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="cs-card" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="cs-section-label">{{ __('Logo') }}</div>
                            @if($dna->logo_path)
                                <img src="{{ url('/public/storage/' . $dna->logo_path) }}" alt="Logo" style="max-height: 48px; border-radius: 8px;">
                            @else
                                <div style="color: var(--cs-text-muted); font-size: 14px;">
                                    <i class="fa-light fa-plus"></i> {{ __('Add a logo') }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <input type="file" wire:model="logoUpload" id="logo-upload" style="display:none;" accept="image/*">
                            <label for="logo-upload" class="cs-btn cs-btn-ghost cs-btn-sm" style="cursor: pointer;">
                                <i class="fa-light fa-upload"></i>
                            </label>
                        </div>
                    </div>
                    @if($logoUpload)
                        <div style="margin-top: 8px;">
                            <button class="cs-btn cs-btn-primary cs-btn-sm" wire:click="uploadLogo">{{ __('Save Logo') }}</button>
                        </div>
                    @endif
                </div>

                {{-- Colors --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('colors')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="cs-section-label">{{ __('Colors') }}</div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                @foreach(($dna->colors ?? []) as $color)
                                    <div class="cs-color-swatch" style="width: 36px; height: 36px; background: {{ $color }};"></div>
                                @endforeach
                                @if(empty($dna->colors))
                                    <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('No colors detected') }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Fonts --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('fonts')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="cs-section-label">{{ __('Fonts') }}</div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                @foreach(($dna->fonts ?? []) as $font)
                                    <span class="cs-chip">
                                        <span style="font-family: '{{ $font['name'] ?? $font }}', sans-serif; font-size: 16px;">Aa</span>
                                        {{ $font['name'] ?? $font }}
                                    </span>
                                @endforeach
                                @if(empty($dna->fonts))
                                    <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('No fonts detected') }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Tagline --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('tagline')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div class="cs-section-label">{{ __('Tagline') }}</div>
                            <div style="font-family: var(--cs-font-serif); font-style: italic; font-size: 16px; color: var(--cs-text);">
                                {{ $dna->tagline ?: __('No tagline set') }}
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Brand Values --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_values')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div class="cs-section-label">{{ __('Brand Values') }}</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach(($dna->brand_values ?? []) as $value)
                                    <span class="cs-chip">{{ $value }}</span>
                                @endforeach
                                @if(empty($dna->brand_values))
                                    <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('Not set') }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Brand Aesthetic --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_aesthetic')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div class="cs-section-label">{{ __('Brand Aesthetic') }}</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach(($dna->brand_aesthetic ?? []) as $value)
                                    <span class="cs-chip">{{ $value }}</span>
                                @endforeach
                                @if(empty($dna->brand_aesthetic))
                                    <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('Not set') }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Brand Tone --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_tone')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div class="cs-section-label">{{ __('Brand Tone of Voice') }}</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach(($dna->brand_tone ?? []) as $value)
                                    <span class="cs-chip">{{ $value }}</span>
                                @endforeach
                                @if(empty($dna->brand_tone))
                                    <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('Not set') }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                </div>

                {{-- Business Overview --}}
                <div class="cs-card cs-card-clickable" wire:click="openEdit('business_overview')" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div class="cs-section-label">{{ __('Business Overview') }}</div>
                            <div style="font-size: 14px; color: var(--cs-text-secondary); line-height: 1.6;">
                                {{ $dna->business_overview ?: __('No overview set') }}
                            </div>
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted); margin-left: 12px;"></i>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Images --}}
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="cs-card" style="padding: 20px;">
                    <div class="cs-section-label">{{ __('Images') }}</div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        @foreach(($dna->images ?? []) as $image)
                            <div style="aspect-ratio: 1; border-radius: var(--cs-radius-sm); overflow: hidden;">
                                <img src="{{ $image['url'] ?? '' }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top: 16px;">
                        <input type="file" wire:model="imageUploads" id="image-uploads" style="display:none;" accept="image/*" multiple>
                        <label for="image-uploads" class="cs-btn cs-btn-secondary cs-btn-sm" style="cursor: pointer;">
                            <i class="fa-light fa-upload"></i> {{ __('Upload Images') }}
                        </label>
                        @if(!empty($imageUploads))
                            <button class="cs-btn cs-btn-primary cs-btn-sm" wire:click="uploadImages" style="margin-left: 8px;">
                                {{ __('Save') }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Reset DNA --}}
                <div style="text-align: center; padding-top: 20px;">
                    <button class="cs-btn cs-btn-danger cs-btn-sm"
                            wire:click="resetDna"
                            wire:confirm="{{ __('Are you sure? This will delete your entire Business DNA and all associated campaigns.') }}">
                        <i class="fa-light fa-trash"></i> {{ __('Reset Business DNA') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ Edit Modals ━━━ --}}
        @include('appaicontents::livewire.partials._dna-edit-modal')
    @endif
</div>
