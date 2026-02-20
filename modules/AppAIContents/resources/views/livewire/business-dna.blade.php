<div>
    {{-- Polling element (nested, not on root — reliable across Livewire morphs) --}}
    @if($isAnalyzing)
        <div wire:poll.3s="pollAnalysis" style="display:none;"></div>
    @endif

    {{-- Notify parent when DNA analysis just completed --}}
    @if($justCompleted && $dna)
        <div x-data x-init="
            $nextTick(() => {
                $dispatch('dna-ready', { dnaId: {{ $dna->id }} });
                $wire.set('justCompleted', false);
            });
        " style="display:none;"></div>
    @endif

    {{-- Page Header --}}
    <div class="cs-page-header">
        <div class="cs-page-icon"><i class="fa-light fa-dna"></i></div>
        <h1>{{ __('Your Business DNA') }}</h1>
        <p>{{ __('Here is a snapshot of your business that we\'ll use to create social media campaigns.') }}<br>{{ __('Feel free to edit this at anytime.') }}</p>
    </div>

    @if(!$dna || $dna->status === 'pending' || (!$isAnalyzing && !$dna->brand_name))
        {{-- ━━━ Onboarding: Enter Website URL ━━━ --}}
        <div wire:key="dna-state-onboarding">
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
        </div>

    @elseif($isAnalyzing)
        {{-- ━━━ Analyzing State with Step Progress ━━━ --}}
        <div wire:key="dna-state-analyzing">
            <div class="cs-card" style="max-width: 540px; margin: 40px auto; padding: 48px 40px; text-align: center;">
                <div style="font-size: 40px; color: var(--cs-primary-text); margin-bottom: 16px;">
                    <i class="fa-light fa-dna fa-spin-pulse"></i>
                </div>
                <h2 style="font-family: var(--cs-font-serif); font-style: italic; font-size: 20px; margin-bottom: 8px;">
                    {{ __('Analyzing your website...') }}
                </h2>
                <p style="color: var(--cs-text-muted); font-size: 14px; margin-bottom: 32px;">
                    {{ __('We\'re extracting your brand identity. This may take a few minutes.') }}
                </p>

                {{-- Step Progress Indicator --}}
                @php
                    $steps = [
                        1 => __('Scraping website'),
                        2 => __('Analyzing brand'),
                        3 => __('Validating images'),
                        4 => __('Generating ideas'),
                        5 => __('Complete'),
                    ];
                    $currentStep = $progressStep ?: 0;
                @endphp

                <div style="display: flex; align-items: center; justify-content: center; gap: 0; max-width: 400px; margin: 0 auto 20px;">
                    @foreach($steps as $stepNum => $stepLabel)
                        {{-- Dot --}}
                        <div style="
                            width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
                            transition: all 0.4s ease;
                            {{ $stepNum <= $currentStep
                                ? 'background: var(--cs-primary-text); box-shadow: 0 0 8px rgba(3,252,244,0.4);'
                                : 'background: var(--cs-border-strong);'
                            }}
                            {{ $stepNum == $currentStep ? 'transform: scale(1.3);' : '' }}
                        "></div>

                        {{-- Connecting line (not after last dot) --}}
                        @if($stepNum < 5)
                            <div style="
                                flex: 1; height: 2px;
                                transition: background 0.4s ease;
                                {{ $stepNum < $currentStep
                                    ? 'background: var(--cs-primary-text);'
                                    : 'background: var(--cs-border-strong);'
                                }}
                            "></div>
                        @endif
                    @endforeach
                </div>

                {{-- Current Step Label --}}
                <div style="color: var(--cs-primary-text); font-size: 13px; font-weight: 500; min-height: 20px;">
                    {{ $progressMessage ?: ($currentStep > 0 ? ($steps[$currentStep] ?? '') : __('Starting...')) }}
                </div>

                {{-- Step counter --}}
                <div style="color: var(--cs-text-muted); font-size: 11px; margin-top: 8px;">
                    {{ $currentStep > 0 ? "Step {$currentStep} of 5" : '' }}
                </div>

                <div style="color: var(--cs-text-muted); font-size: 12px; margin-top: 16px;">
                    {{ $dna->website_url ?? '' }}
                </div>
            </div>
        </div>

    @else
        {{-- ━━━ DNA Display — Pomelli-style 2-column layout ━━━ --}}
        <div wire:key="dna-state-complete-{{ $dna->id }}">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">

                {{-- ═══════ LEFT COLUMN: Brand attributes ═══════ --}}
                <div style="display: flex; flex-direction: column; gap: 16px;">

                    {{-- Row 1: Brand Name (full-width) --}}
                    <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_name')" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div style="font-size: 24px; font-weight: 600; color: var(--cs-text); margin-bottom: 4px;">
                                    {{ $dna->brand_name ?: __('Not set') }}
                                </div>
                                @if($dna->website_url)
                                    <div style="display: flex; align-items: center; gap: 6px; color: var(--cs-text-muted); font-size: 13px;">
                                        <i class="fa-light fa-link" style="font-size: 11px;"></i>
                                        <a href="{{ $dna->website_url }}" target="_blank" rel="noopener" style="color: var(--cs-text-muted); text-decoration: none;"
                                           onclick="event.stopPropagation();">
                                            {{ $dna->website_url }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <i class="fa-light fa-pen" style="color: var(--cs-text-muted); margin-top: 4px;"></i>
                        </div>
                    </div>

                    {{-- Row 2: Language (compact) --}}
                    @if($dna->language)
                    <div class="cs-card cs-card-clickable" wire:click="openEdit('language')" style="padding: 16px 24px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="cs-section-label" style="margin: 0;">{{ __('Language') }}</div>
                            <span style="font-size: 14px; color: var(--cs-text);">{{ $dna->language }}</span>
                            @if($dna->language_code)
                                <span style="font-size: 11px; color: var(--cs-text-muted); background: var(--cs-glass-bg); padding: 2px 8px; border-radius: 10px; text-transform: uppercase; font-weight: 500;">{{ $dna->language_code }}</span>
                            @endif
                        </div>
                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                    </div>
                    @endif

                    {{-- Row 3: Logo + Fonts (side-by-side) --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        {{-- Logo --}}
                        <div class="cs-card" style="padding: 20px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    @if($dna->logo_path)
                                        <img src="{{ url('/public/storage/' . $dna->logo_path) }}" alt="Logo" style="max-height: 60px; border-radius: 8px;">
                                    @else
                                        <div style="text-align: center; color: var(--cs-text-muted);">
                                            <div style="font-size: 24px; margin-bottom: 4px;"><i class="fa-light fa-plus"></i></div>
                                            <div style="font-size: 13px;">{{ __('Add a logo') }}</div>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <input type="file" wire:model="logoUpload" id="logo-upload" style="display:none;" accept="image/*">
                                    <label for="logo-upload" class="cs-btn cs-btn-ghost cs-btn-sm" style="cursor: pointer;">
                                        <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                                    </label>
                                </div>
                            </div>
                            @if($logoUpload)
                                <div style="margin-top: 8px; text-align: center;">
                                    <button class="cs-btn cs-btn-primary cs-btn-sm" wire:click="uploadLogo">{{ __('Save Logo') }}</button>
                                </div>
                            @endif
                        </div>

                        {{-- Fonts --}}
                        <div class="cs-card cs-card-clickable" wire:click="openEdit('fonts')" style="padding: 20px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Fonts') }}</div>
                                    @foreach(($dna->fonts ?? []) as $font)
                                        <div style="display: flex; align-items: baseline; gap: 8px;">
                                            <span style="font-family: '{{ $font['name'] ?? $font }}', sans-serif; font-size: 32px; font-weight: 400; color: var(--cs-text);">Aa</span>
                                            <span style="font-size: 14px; color: var(--cs-text-secondary);">{{ $font['name'] ?? $font }}</span>
                                        </div>
                                    @endforeach
                                    @if(empty($dna->fonts))
                                        <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('No fonts detected') }}</span>
                                    @endif
                                </div>
                                <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Row 3: Colors (full-width with large circles + hex) --}}
                    <div class="cs-card cs-card-clickable" wire:click="openEdit('colors')" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div class="cs-section-label" style="margin-bottom: 12px;">{{ __('Colors') }}</div>
                                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                    @foreach(($dna->colors ?? []) as $color)
                                        <div style="text-align: center;">
                                            <div style="width: 56px; height: 56px; border-radius: 50%; background: {{ $color }}; border: 2px solid rgba(0,0,0,0.08); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                            <div style="font-size: 11px; color: var(--cs-text-muted); margin-top: 6px; font-family: monospace;">{{ $color }}</div>
                                        </div>
                                    @endforeach
                                    @if(empty($dna->colors))
                                        <span style="color: var(--cs-text-muted); font-size: 14px;">{{ __('No colors detected') }}</span>
                                    @endif
                                </div>
                            </div>
                            <i class="fa-light fa-pen" style="color: var(--cs-text-muted);"></i>
                        </div>
                    </div>

                    {{-- Row 4: Tagline + Brand Values (side-by-side) --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        {{-- Tagline --}}
                        <div class="cs-card cs-card-clickable" wire:click="openEdit('tagline')" style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Tagline') }}</div>
                                    <div style="font-family: var(--cs-font-serif); font-style: italic; font-size: 16px; color: var(--cs-text); line-height: 1.4;">
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
                                    <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Brand Values') }}</div>
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
                    </div>

                    {{-- Row 5: Brand Aesthetic + Brand Tone (side-by-side) --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        {{-- Brand Aesthetic --}}
                        <div class="cs-card cs-card-clickable" wire:click="openEdit('brand_aesthetic')" style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Brand Aesthetic') }}</div>
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
                                    <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Brand Tone of Voice') }}</div>
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
                    </div>

                    {{-- Row 6: Business Overview (full-width) --}}
                    <div class="cs-card cs-card-clickable" wire:click="openEdit('business_overview')" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div class="cs-section-label" style="margin-bottom: 8px;">{{ __('Business Overview') }}</div>
                                <div style="font-size: 14px; color: var(--cs-text-secondary); line-height: 1.6;">
                                    {{ $dna->business_overview ?: __('No overview set') }}
                                </div>
                            </div>
                            <i class="fa-light fa-pen" style="color: var(--cs-text-muted); margin-left: 12px;"></i>
                        </div>
                    </div>
                </div>

                {{-- ═══════ RIGHT COLUMN: Images (sticky) ═══════ --}}
                <div style="position: sticky; top: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div class="cs-card" style="padding: 20px;">
                        <div class="cs-section-label" style="margin-bottom: 12px;">{{ __('Images') }}</div>

                        {{-- Photoshoot Promo Card --}}
                        <div style="background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: var(--cs-radius); padding: 16px; margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
                            <div style="flex-shrink: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 4px; width: 80px;">
                                @foreach(array_slice(($dna->images ?? []), 0, 4) as $img)
                                    <div style="width: 36px; height: 36px; border-radius: 4px; overflow: hidden;">
                                        <img src="{{ !empty($img['path']) ? url('/public/storage/' . $img['path']) : ($img['url'] ?? '') }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @endforeach
                                @for($i = count(($dna->images ?? [])); $i < 4; $i++)
                                    <div style="width: 36px; height: 36px; border-radius: 4px; background: rgba(255,255,255,0.1);"></div>
                                @endfor
                            </div>
                            <div style="flex: 1;">
                                <div style="color: #fff; font-size: 13px; font-weight: 600; margin-bottom: 4px;">{{ __('Endless creatives, ready in minutes') }}</div>
                                <div style="color: rgba(255,255,255,0.6); font-size: 11px; line-height: 1.4; margin-bottom: 8px;">{{ __('Skip the cost and complexity of traditional photoshoots and generate compelling, on-brand images.') }}</div>
                                <button class="cs-btn cs-btn-sm" style="background: rgba(3,252,244,0.15); color: #03fcf4; border: 1px solid rgba(3,252,244,0.3); font-size: 11px; padding: 4px 12px;"
                                        wire:click="$dispatch('switch-section', { section: 'photoshoot' })">
                                    <i class="fa-light fa-camera-retro" style="font-size: 10px;"></i> {{ __('Try Photoshoot') }}
                                </button>
                            </div>
                        </div>

                        {{-- Upload Images + Image Grid --}}
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            {{-- Upload Button Cell --}}
                            <div style="aspect-ratio: 1; border-radius: var(--cs-radius-sm); border: 2px dashed var(--cs-border-strong); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative;">
                                <input type="file" wire:model="imageUploads" id="image-uploads" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;" accept="image/*" multiple>
                                <i class="fa-light fa-upload" style="font-size: 16px; color: var(--cs-primary-text); margin-bottom: 4px;"></i>
                                <span style="font-size: 9px; color: var(--cs-text-muted); text-align: center;">{{ __('Upload') }}</span>
                            </div>

                            {{-- Images (use local path if available, fall back to URL) --}}
                            @foreach(($dna->images ?? []) as $image)
                                <div style="aspect-ratio: 1; border-radius: var(--cs-radius-sm); overflow: hidden;">
                                    <img src="{{ !empty($image['path']) ? url('/public/storage/' . $image['path']) : ($image['url'] ?? '') }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($imageUploads))
                            <div style="margin-top: 12px; text-align: center;">
                                <button class="cs-btn cs-btn-primary cs-btn-sm" wire:click="uploadImages">
                                    {{ __('Save') }}
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Reset DNA --}}
                    <div style="text-align: center; padding-top: 12px;">
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
        </div>
    @endif
</div>
