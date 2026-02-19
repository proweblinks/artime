<div
    @if($isFixingLayout)
        wire:poll.3s="pollFixLayout"
    @endif
>
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
                @else
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
                                <div style="font-family: '{{ $headerFont }}', sans-serif; color: {{ $headerColor }}; font-size: {{ min($headerSize * 0.6, 28) }}px; line-height: {{ $headerHeight * 0.6 }}px; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.5); margin-bottom: 8px;">
                                    {{ $headerText }}
                                </div>
                            @endif
                            @if($descVisible && $descriptionText)
                                <div style="font-family: '{{ $descFont }}', sans-serif; color: {{ $descColor }}; font-size: {{ min($descSize * 0.6, 14) }}px; line-height: {{ $descHeight * 0.6 }}px; text-shadow: 0 1px 4px rgba(0,0,0,0.5); margin-bottom: 12px;">
                                    {{ $descriptionText }}
                                </div>
                            @endif
                            @if($ctaVisible && $ctaText)
                                <div>
                                    <span style="display: inline-block; padding: 6px 16px; background: rgba(255,255,255,0.9); color: #111; border-radius: 20px; font-size: {{ min($ctaSize * 0.6, 12) }}px; font-weight: 600;">
                                        {{ $ctaText }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

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
                    <div style="width: 80px; height: 100px; border: 2px dashed var(--cs-border-strong); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        @if($creative->image_url)
                            <img src="{{ $creative->image_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <i class="fa-light fa-image" style="color: var(--cs-text-muted);"></i>
                        @endif
                    </div>
                    <div style="font-size: 12px; color: var(--cs-text-muted); margin-top: 4px;">{{ __('Generated Image') }}</div>
                </div>
                @endif
            </div>

            {{-- Header Section --}}
            <div style="border-bottom: 1px solid var(--cs-border);">
                <div class="cs-accordion-header {{ $expandedSection === 'header' ? 'expanded' : '' }}"
                     wire:click="toggleSection('header')">
                    <i class="fa-light fa-heading" style="margin-right: 8px; color: var(--cs-primary-text);"></i>
                    <span style="font-weight: 600; font-size: 14px; flex: 1;">{{ __('Header') }}</span>
                    <div class="cs-toggle {{ $headerVisible ? 'active' : '' }}" wire:click.stop="toggleVisibility('header')"></div>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'header' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'header')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <textarea class="cs-input" wire:model.blur="headerText" wire:change="updateText('header')" rows="2" placeholder="{{ __('Header text') }}"></textarea>
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
                            <input type="color" class="cs-input" style="padding: 4px; height: 36px;" wire:model="headerColor" wire:change="updateStyle('header', 'color', $event.target.value)">
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
                    <div class="cs-toggle {{ $descVisible ? 'active' : '' }}" wire:click.stop="toggleVisibility('description')"></div>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'description' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'description')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <textarea class="cs-input" wire:model.blur="descriptionText" wire:change="updateText('description')" rows="2" placeholder="{{ __('Description text') }}"></textarea>
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
                            <input type="color" class="cs-input" style="padding: 4px; height: 36px;" wire:model="descColor" wire:change="updateStyle('description', 'color', $event.target.value)">
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
                    <div class="cs-toggle {{ $ctaVisible ? 'active' : '' }}" wire:click.stop="toggleVisibility('cta')"></div>
                    <i class="fa-light fa-chevron-{{ $expandedSection === 'cta' ? 'up' : 'down' }}" style="font-size: 12px; color: var(--cs-text-muted); margin-left: 8px;"></i>
                </div>
                @if($expandedSection === 'cta')
                <div class="cs-accordion-body">
                    <div style="margin-bottom: 12px;">
                        <input type="text" class="cs-input" wire:model.blur="ctaText" wire:change="updateText('cta')" placeholder="{{ __('Call to action text') }}">
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
                            <input type="color" class="cs-input" style="padding: 4px; height: 36px;" wire:model="ctaColor" wire:change="updateStyle('cta', 'color', $event.target.value)">
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
</div>
