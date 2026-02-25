<div x-data="{ showTemplatePicker: @entangle('showTemplatePicker') }">
    @if($isFixingLayout)
        <div wire:poll.3s="pollFixLayout" style="display:none;"></div>
    @endif
    @if($isCompositing)
        <div wire:poll.3s="pollComposite" style="display:none;"></div>
    @endif

    {{-- Back Breadcrumb --}}
    <div class="cs-breadcrumb" wire:click="goBack">
        <i class="fa-light fa-arrow-left"></i>
        {{ __('Back to') }} {{ $creative?->campaign?->title ?? __('Campaign') }}
    </div>

    @if($creative)
    <div class="cs-editor-layout" style="margin: 0 -40px;">

        {{-- ━━━ LEFT: Creative Preview ━━━ --}}
        <div class="cs-editor-preview">
            {{-- Version History Bar --}}
            <div class="cs-version-bar" style="margin-bottom: 16px; width: 100%; max-width: 400px;">
                <i class="fa-light fa-clock-rotate-left"></i>
                <span>{{ __('Version History') }}</span>
                <div style="flex: 1;"></div>
                <button wire:click="navigateVersion('prev')" @if($currentVersion <= 1) disabled @endif>
                    <i class="fa-light fa-chevron-left"></i>
                </button>
                <span style="font-weight: 600; min-width: 40px; text-align: center;">{{ $currentVersion }} / {{ $totalVersions }}</span>
                <button wire:click="navigateVersion('next')" @if($currentVersion >= $totalVersions) disabled @endif>
                    <i class="fa-light fa-chevron-right"></i>
                </button>
            </div>

            {{-- Preview Image --}}
            <div style="width: 100%; max-width: 360px; position: relative;">
                @if($isFixingLayout)
                    <div style="aspect-ratio: 9/16; border-radius: var(--cs-radius-lg); overflow: hidden; position: relative;">
                        <div class="cs-skeleton" style="width: 100%; height: 100%;"></div>
                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;">
                            <div class="cs-progress" style="width: 60%;">
                                <div class="cs-progress-bar" style="width: 50%; animation: cs-shimmer 2s ease-in-out infinite;"></div>
                            </div>
                            <div class="cs-progress" style="width: 40%; margin-top: 4px;">
                                <div class="cs-progress-bar" style="width: 70%; animation: cs-shimmer 2.5s ease-in-out infinite; animation-delay: 0.5s;"></div>
                            </div>
                            <span style="font-size: 13px; color: var(--cs-text-muted); margin-top: 8px;">{{ __('About 2 minutes left') }}</span>
                        </div>
                    </div>
                @elseif($compositeImage)
                    {{-- Show composite image (text baked in server-side) --}}
                    <div style="aspect-ratio: 9/16; border-radius: var(--cs-radius-lg); overflow: hidden; background: #111;">
                        <img src="{{ $compositeImage }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @else
                    {{-- Fallback: raw image + CSS text overlays --}}
                    <div style="aspect-ratio: 9/16; border-radius: var(--cs-radius-lg); overflow: hidden; position: relative; background: #111;">
                        @if($versionImage)
                            <img src="{{ $versionImage }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-light fa-image" style="font-size: 48px; color: rgba(255,255,255,0.2);"></i>
                            </div>
                        @endif

                        {{-- Text Overlays Preview --}}
                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 24px;">
                            @if($headerVisible && $headerText)
                                <div dir="auto" style="font-family: '{{ $headerFont }}', sans-serif; color: {{ $headerColor }}; font-size: {{ min($headerSize * 0.6, 28) }}px; line-height: {{ $headerHeight * 0.6 }}px; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.5); margin-bottom: 8px;">
                                    {{ $headerText }}
                                </div>
                            @endif
                            @if($descVisible && $descriptionText)
                                <div dir="auto" style="font-family: '{{ $descFont }}', sans-serif; color: {{ $descColor }}; font-size: {{ min($descSize * 0.6, 14) }}px; line-height: {{ $descHeight * 0.6 }}px; text-shadow: 0 1px 4px rgba(0,0,0,0.5); margin-bottom: 12px;">
                                    {{ $descriptionText }}
                                </div>
                            @endif
                            @if($ctaVisible && $ctaText)
                                <div dir="auto">
                                    <span style="display: inline-block; padding: 6px 16px; background: rgba(255,255,255,0.9); color: #111; border-radius: 20px; font-size: {{ min($ctaSize * 0.6, 12) }}px; font-weight: 600;">
                                        {{ $ctaText }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Compositing Indicator --}}
            @if($isCompositing)
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 12px; width: 100%; max-width: 400px; padding: 8px 12px; background: rgba(139,92,246,0.1); border-radius: var(--cs-radius-md); font-size: 12px; color: rgb(139,92,246);">
                    <i class="fa-light fa-spinner-third fa-spin"></i>
                    {{ __('Rendering composite image...') }}
                </div>
            @endif

            {{-- Bottom Buttons --}}
            <div style="display: flex; gap: 8px; margin-top: 16px; width: 100%; max-width: 400px;">
                <button class="cs-btn cs-btn-ai" style="flex: 1;"
                        wire:click="fixLayout"
                        @if($isFixingLayout) disabled @endif>
                    <i class="fa-light fa-sparkles"></i>
                    @if($isFixingLayout)
                        {{ __('Updating...') }}
                    @else
                        {{ __('Fix Layout') }}
                    @endif
                </button>
                <button class="cs-btn cs-btn-secondary cs-btn-icon" @click="showTemplatePicker = true" title="{{ __('Change Template') }}">
                    <i class="fa-light fa-grid-2"></i>
                </button>
                <button class="cs-btn cs-btn-secondary cs-btn-icon" wire:click="download" title="{{ __('Download') }}">
                    <i class="fa-light fa-download"></i>
                </button>
            </div>
        </div>

        {{-- ━━━ RIGHT: Properties Inspector ━━━ --}}
        <div class="cs-editor-panel">
            {{-- Image Section --}}
            <div style="border-bottom: 1px solid var(--cs-border);">
                <div class="cs-accordion-header {{ $expandedSection === 'image' ? 'expanded' : '' }}"
                     wire:click="toggleSection('image')">
                    <i class="fa-light fa-image" style="margin-right: 8px; color: var(--cs-primary-text);"></i>
                    <span style="font-weight: 600; font-size: 14px; flex: 1;">{{ __('Image') }}</span>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'image' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted);"></i>
                </div>
                @if($expandedSection === 'image')
                <div class="cs-accordion-body">
                    <div style="font-size: 11px; color: var(--cs-text-muted); margin-bottom: 6px;">{{ __('Image preview') }}</div>
                    <div style="width: 80px; height: 100px; border: 2px dashed var(--cs-border-strong); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                        @if($creative->image_url)
                            <img src="{{ $creative->image_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <i class="fa-light fa-image" style="color: var(--cs-text-muted);"></i>
                        @endif
                        <i class="fa-light fa-pen" style="position: absolute; top: 4px; right: 4px; font-size: 10px; color: var(--cs-primary-text); background: var(--cs-bg-surface); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;"></i>
                    </div>
                    <div style="font-size: 12px; color: var(--cs-text-muted); margin-top: 4px;">
                        @if(($creative->source_type ?? 'ai') === 'ai')
                            <span style="color: rgb(139,92,246);"><i class="fa-light fa-sparkles" style="font-size: 10px;"></i> {{ __('AI Generated') }}</span>
                        @else
                            <span style="color: rgb(16,185,129);"><i class="fa-light fa-camera" style="font-size: 10px;"></i> {{ __('Brand Image') }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Header Section --}}
            <div style="border-bottom: 1px solid var(--cs-border);">
                <div class="cs-accordion-header {{ $expandedSection === 'header' ? 'expanded' : '' }}"
                     wire:click="toggleSection('header')">
                    <i class="fa-light fa-heading" style="margin-right: 8px; color: var(--cs-primary-text);"></i>
                    <span style="font-weight: 600; font-size: 14px; flex: 1;">{{ __('Header') }}</span>
                    <button style="background: none; border: none; cursor: pointer; padding: 4px; color: var(--cs-primary-text);" wire:click.stop="toggleVisibility('header')" title="{{ $headerVisible ? __('Hide') : __('Show') }}"><i class="fa-light {{ $headerVisible ? 'fa-eye' : 'fa-eye-slash' }}" style="font-size: 14px;"></i></button>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'header' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'header')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <textarea class="cs-input" wire:model.blur="headerText" wire:change="updateText('header')" dir="auto" rows="2" placeholder="{{ __('Header text') }}"></textarea>
                        <button class="cs-btn cs-btn-ai cs-btn-sm" style="margin-top: 8px;" wire:click="helpMeWrite('header')">
                            <i class="fa-light fa-wand-magic-sparkles"></i> {{ __('Help me write') }}
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Font') }}</label>
                            <select class="cs-input" style="padding: 6px 10px;" wire:model="headerFont" wire:change="updateStyle('header', 'font', $event.target.value)">
                                <option value="Roboto">Roboto</option>
                                <option value="Inter">Inter</option>
                                <option value="Playfair Display">Playfair Display</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Lato">Lato</option>
                                <option value="Poppins">Poppins</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Color') }}</label>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: {{ $headerColor ?? '#ffffff' }}; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="headerColor" wire:change="updateStyle('header', 'color', $event.target.value)">
                                </div>
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: #000; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" value="#000000" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="headerColor" wire:change="updateStyle('header', 'color', $event.target.value)">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Size') }}</label>
                            <input type="number" class="cs-input" style="padding: 6px 10px;" wire:model="headerSize" wire:change="updateStyle('header', 'size', $event.target.value)">
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Height') }}</label>
                            <input type="number" class="cs-input" style="padding: 6px 10px;" wire:model="headerHeight" wire:change="updateStyle('header', 'height', $event.target.value)">
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Description Section --}}
            <div style="border-bottom: 1px solid var(--cs-border);">
                <div class="cs-accordion-header {{ $expandedSection === 'description' ? 'expanded' : '' }}"
                     wire:click="toggleSection('description')">
                    <i class="fa-light fa-align-left" style="margin-right: 8px; color: var(--cs-primary-text);"></i>
                    <span style="font-weight: 600; font-size: 14px; flex: 1;">{{ __('Description') }}</span>
                    <button style="background: none; border: none; cursor: pointer; padding: 4px; color: var(--cs-primary-text);" wire:click.stop="toggleVisibility('description')" title="{{ $descVisible ? __('Hide') : __('Show') }}"><i class="fa-light {{ $descVisible ? 'fa-eye' : 'fa-eye-slash' }}" style="font-size: 14px;"></i></button>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'description' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'description')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <textarea class="cs-input" wire:model.blur="descriptionText" wire:change="updateText('description')" dir="auto" rows="2" placeholder="{{ __('Description text') }}"></textarea>
                        <button class="cs-btn cs-btn-ai cs-btn-sm" style="margin-top: 8px;" wire:click="helpMeWrite('description')">
                            <i class="fa-light fa-wand-magic-sparkles"></i> {{ __('Help me write') }}
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Font') }}</label>
                            <select class="cs-input" style="padding: 6px 10px;" wire:model="descFont" wire:change="updateStyle('description', 'font', $event.target.value)">
                                <option value="Roboto">Roboto</option>
                                <option value="Inter">Inter</option>
                                <option value="Playfair Display">Playfair Display</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Lato">Lato</option>
                                <option value="Poppins">Poppins</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Color') }}</label>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: {{ $descColor ?? '#ffffff' }}; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="descColor" wire:change="updateStyle('description', 'color', $event.target.value)">
                                </div>
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: #000; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" value="#000000" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="descColor" wire:change="updateStyle('description', 'color', $event.target.value)">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Size') }}</label>
                            <input type="number" class="cs-input" style="padding: 6px 10px;" wire:model="descSize" wire:change="updateStyle('description', 'size', $event.target.value)">
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Height') }}</label>
                            <input type="number" class="cs-input" style="padding: 6px 10px;" wire:model="descHeight" wire:change="updateStyle('description', 'height', $event.target.value)">
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- CTA Section --}}
            <div>
                <div class="cs-accordion-header {{ $expandedSection === 'cta' ? 'expanded' : '' }}"
                     wire:click="toggleSection('cta')">
                    <i class="fa-light fa-hand-pointer" style="margin-right: 8px; color: var(--cs-primary-text);"></i>
                    <span style="font-weight: 600; font-size: 14px; flex: 1;">{{ __('Call To Action') }}</span>
                    <button style="background: none; border: none; cursor: pointer; padding: 4px; color: var(--cs-primary-text);" wire:click.stop="toggleVisibility('cta')" title="{{ $ctaVisible ? __('Hide') : __('Show') }}"><i class="fa-light {{ $ctaVisible ? 'fa-eye' : 'fa-eye-slash' }}" style="font-size: 14px;"></i></button>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'cta' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'cta')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <input type="text" class="cs-input" wire:model.blur="ctaText" wire:change="updateText('cta')" dir="auto" placeholder="{{ __('Call to action text') }}">
                        <button class="cs-btn cs-btn-ai cs-btn-sm" style="margin-top: 8px;" wire:click="helpMeWrite('cta')">
                            <i class="fa-light fa-sparkles"></i> {{ __('Generate') }}
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Font') }}</label>
                            <select class="cs-input" style="padding: 6px 10px;" wire:model="ctaFont" wire:change="updateStyle('cta', 'font', $event.target.value)">
                                <option value="Roboto">Roboto</option>
                                <option value="Inter">Inter</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Poppins">Poppins</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Color') }}</label>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: {{ $ctaColor ?? '#ffffff' }}; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="ctaColor" wire:change="updateStyle('cta', 'color', $event.target.value)">
                                </div>
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: #000; border: 2px solid rgba(0,0,0,0.1); cursor: pointer; position: relative; overflow: hidden;">
                                    <input type="color" value="#000000" style="position: absolute; inset: -4px; width: calc(100% + 8px); height: calc(100% + 8px); cursor: pointer; opacity: 0;" wire:model="ctaColor" wire:change="updateStyle('cta', 'color', $event.target.value)">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--cs-text-muted); display: block; margin-bottom: 4px;">{{ __('Size') }}</label>
                            <input type="number" class="cs-input" style="padding: 6px 10px;" wire:model="ctaSize" wire:change="updateStyle('cta', 'size', $event.target.value)">
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ━━━ Template Picker Modal ━━━ --}}
    @php
        $layoutTemplates = \Modules\AppAIContents\Models\CreativeLayoutTemplate::active()
            ->orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
    @endphp

    <div x-show="showTemplatePicker" x-cloak>
        {{-- Backdrop --}}
        <div @click="showTemplatePicker = false"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 100; backdrop-filter: blur(4px);"
             x-transition.opacity></div>

        {{-- Modal --}}
        <div x-transition.scale.95
             style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 101;
                    width: 520px; max-width: 90vw; max-height: 75vh;
                    background: var(--cs-bg-card); border-radius: 16px;
                    box-shadow: 0 25px 60px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05);
                    display: flex; flex-direction: column; overflow: hidden;">

            {{-- Header --}}
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid var(--cs-border); flex-shrink: 0;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; margin: 0; letter-spacing: -0.01em;">{{ __('Choose a Template') }}</h3>
                    <div style="font-size: 12px; color: var(--cs-text-muted); margin-top: 2px;">{{ $layoutTemplates->flatten()->count() }} {{ __('templates available') }}</div>
                </div>
                <button @click="showTemplatePicker = false"
                        style="width: 32px; height: 32px; border-radius: 8px; border: none; background: var(--cs-bg-muted);
                               display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--cs-text-muted);
                               transition: all 0.15s;"
                        onmouseover="this.style.background='var(--cs-border)'"
                        onmouseout="this.style.background='var(--cs-bg-muted)'">
                    <i class="fa-light fa-xmark" style="font-size: 14px;"></i>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div style="overflow-y: auto; padding: 16px 24px 24px; flex: 1;">
                @foreach($layoutTemplates as $category => $categoryTemplates)
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--cs-text-muted); margin-bottom: 10px;">
                            {{ ucfirst($category) }}
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                            @foreach($categoryTemplates as $tpl)
                                @php $isActive = ($creative->layout_template_id ?? 0) === $tpl->id; @endphp
                                <button wire:click="changeTemplate({{ $tpl->id }})" @click="showTemplatePicker = false"
                                        style="padding: 12px 10px; text-align: center; border-radius: 10px; cursor: pointer;
                                               border: {{ $isActive ? '2px solid var(--cs-primary-text)' : '1px solid var(--cs-border)' }};
                                               background: {{ $isActive ? 'var(--cs-primary-soft, rgba(0,200,180,0.08))' : 'var(--cs-bg-card)' }};
                                               transition: all 0.15s; font-size: 12px; line-height: 1.3; position: relative;"
                                        onmouseover="if(!{{ $isActive ? 'true' : 'false' }})this.style.borderColor='var(--cs-primary-text)';this.style.background='var(--cs-primary-soft, rgba(0,200,180,0.06))'"
                                        onmouseout="if(!{{ $isActive ? 'true' : 'false' }}){this.style.borderColor='var(--cs-border)';this.style.background='var(--cs-bg-card)'}">
                                    @if($isActive)
                                        <div style="position: absolute; top: 6px; right: 6px; width: 18px; height: 18px; border-radius: 50%;
                                                    background: var(--cs-primary-text); display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-check" style="font-size: 9px; color: #fff;"></i>
                                        </div>
                                    @endif
                                    <div style="font-weight: 600; color: {{ $isActive ? 'var(--cs-primary-text)' : 'var(--cs-text)' }};">{{ $tpl->name }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
