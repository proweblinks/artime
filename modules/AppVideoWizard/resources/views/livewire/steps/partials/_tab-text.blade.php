{{--
    Text/Captions Tab Content - Phase 3
    Full caption customization: fonts, colors, effects, presets
--}}

<div class="vw-text-tab" x-data="{
    activeSubTab: 'presets',
    previewText: 'Sample Caption',
    showFontPicker: false,
    customColors: {
        fill: '{{ $assembly['captions']['fillColor'] ?? '#FFFFFF' }}',
        stroke: '{{ $assembly['captions']['strokeColor'] ?? '#000000' }}',
        highlight: '{{ $assembly['captions']['highlightColor'] ?? '#FBBF24' }}',
        background: '{{ $assembly['captions']['backgroundColor'] ?? 'transparent' }}'
    },

    // Local copy of parent state (synced via Livewire events)
    captionsEnabled: {{ ($assembly['captions']['enabled'] ?? true) ? 'true' : 'false' }},

    // Dispatch to parent's updateCaptionSetting method via event
    updateCaptionSetting(key, value) {
        // Dispatch Livewire event for server-side update
        Livewire.dispatch('caption-setting-updated', { key: key, value: value });
    }
}">
    {{-- Enable Toggle --}}
    <div class="vw-caption-toggle">
        <div class="vw-toggle-content">
            <span class="vw-toggle-icon">💬</span>
            <div class="vw-toggle-text">
                <span class="vw-toggle-title">{{ __('Captions') }}</span>
                <span class="vw-toggle-desc">{{ __('Show text on screen') }}</span>
            </div>
        </div>
        <label class="vw-toggle-switch">
            <input
                type="checkbox"
                wire:model.live="assembly.captions.enabled"
                x-on:change="captionsEnabled = $event.target.checked; updateCaptionSetting('enabled', $event.target.checked)"
                {{ ($assembly['captions']['enabled'] ?? true) ? 'checked' : '' }}
            >
            <span class="vw-toggle-slider"></span>
        </label>
    </div>

    {{-- Sub-Navigation Tabs --}}
    <div class="vw-sub-tabs" :class="{ 'disabled': !captionsEnabled }">
        <button type="button" @click="activeSubTab = 'presets'" :class="{ 'active': activeSubTab === 'presets' }" class="vw-sub-tab">
            {{ __('Presets') }}
        </button>
        <button type="button" @click="activeSubTab = 'style'" :class="{ 'active': activeSubTab === 'style' }" class="vw-sub-tab">
            {{ __('Style') }}
        </button>
        <button type="button" @click="activeSubTab = 'colors'" :class="{ 'active': activeSubTab === 'colors' }" class="vw-sub-tab">
            {{ __('Colors') }}
        </button>
        <button type="button" @click="activeSubTab = 'effects'" :class="{ 'active': activeSubTab === 'effects' }" class="vw-sub-tab">
            {{ __('Effects') }}
        </button>
    </div>

    {{-- Caption Settings Content --}}
    <div class="vw-caption-content" :class="{ 'disabled': !captionsEnabled }">
        {{-- PRESETS TAB --}}
        <div x-show="activeSubTab === 'presets'" x-cloak class="vw-tab-content">
            {{-- Live Preview Box --}}
            <div class="vw-caption-preview-box">
                <div class="vw-preview-label">{{ __('Preview') }}</div>
                <div
                    class="vw-caption-live-preview"
                    :class="'style-' + '{{ $assembly['captions']['style'] ?? 'karaoke' }}'"
                    :style="{
                        fontFamily: '{{ $assembly['captions']['fontFamily'] ?? 'Montserrat' }}',
                        fontSize: ({{ $assembly['captions']['size'] ?? 1 }} * 1.2) + 'rem'
                    }"
                >
                    <span class="vw-preview-word highlighted">Sample</span>
                    <span class="vw-preview-word">Caption</span>
                </div>
            </div>

            {{-- Style Presets Grid --}}
            <div class="vw-presets-section">
                <div class="vw-presets-label">{{ __('Choose a Style') }}</div>
                <div class="vw-preset-cards">
                    @php
                        $presets = [
                            [
                                'id' => 'karaoke',
                                'name' => 'Karaoke',
                                'desc' => 'Word-by-word highlight',
                                'preview' => 'karaoke',
                                'icon' => '🎤'
                            ],
                            [
                                'id' => 'beasty',
                                'name' => 'Beasty',
                                'desc' => 'Bold impact text',
                                'preview' => 'beasty',
                                'icon' => '💪'
                            ],
                            [
                                'id' => 'hormozi',
                                'name' => 'Hormozi',
                                'desc' => 'Box highlight style',
                                'preview' => 'hormozi',
                                'icon' => '📦'
                            ],
                            [
                                'id' => 'ali',
                                'name' => 'Ali',
                                'desc' => 'Glow effect text',
                                'preview' => 'ali',
                                'icon' => '✨'
                            ],
                            [
                                'id' => 'minimal',
                                'name' => 'Minimal',
                                'desc' => 'Clean simple text',
                                'preview' => 'minimal',
                                'icon' => '📝'
                            ],
                            [
                                'id' => 'neon',
                                'name' => 'Neon',
                                'desc' => 'Glowing neon effect',
                                'preview' => 'neon',
                                'icon' => '💡'
                            ],
                        ];
                    @endphp

                    @foreach($presets as $preset)
                        <button
                            type="button"
                            wire:click="applyCaptionPreset('{{ $preset['id'] }}')"
                            @click="updateCaptionSetting('style', '{{ $preset['id'] }}')"
                            class="vw-preset-card {{ ($assembly['captions']['style'] ?? 'karaoke') === $preset['id'] ? 'active' : '' }}"
                        >
                            <div class="vw-preset-preview preset-{{ $preset['preview'] }}">
                                <span class="highlighted">Sample</span> Text
                            </div>
                            <div class="vw-preset-info">
                                <span class="vw-preset-icon">{{ $preset['icon'] }}</span>
                                <div class="vw-preset-text">
                                    <span class="vw-preset-name">{{ $preset['name'] }}</span>
                                    <span class="vw-preset-desc">{{ $preset['desc'] }}</span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Caption Mode Toggle --}}
            <div class="vw-mode-section">
                <div class="vw-mode-label">{{ __('Caption Level') }}</div>
                <div class="vw-mode-buttons">
                    <button
                        type="button"
                        wire:click="$set('assembly.captions.mode', 'word')"
                        @click="updateCaptionSetting('mode', 'word')"
                        class="vw-mode-btn {{ ($assembly['captions']['mode'] ?? 'word') === 'word' ? 'active' : '' }}"
                    >
                        <span class="vw-mode-icon">📝</span>
                        <span class="vw-mode-name">{{ __('Word') }}</span>
                        <span class="vw-mode-desc">{{ __('Highlight each word') }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('assembly.captions.mode', 'sentence')"
                        @click="updateCaptionSetting('mode', 'sentence')"
                        class="vw-mode-btn {{ ($assembly['captions']['mode'] ?? 'word') === 'sentence' ? 'active' : '' }}"
                    >
                        <span class="vw-mode-icon">📄</span>
                        <span class="vw-mode-name">{{ __('Sentence') }}</span>
                        <span class="vw-mode-desc">{{ __('Show full sentence') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- STYLE TAB --}}
        <div x-show="activeSubTab === 'style'" x-cloak class="vw-tab-content">
            {{-- Font Family --}}
            <div class="vw-style-section">
                <div class="vw-style-label">{{ __('Font Family') }}</div>
                <div class="vw-font-selector">
                    <select
                        wire:model.live="assembly.captions.fontFamily"
                        x-on:change="updateCaptionSetting('fontFamily', $event.target.value)"
                        class="vw-font-select"
                        :style="{ fontFamily: $el.value }"
                    >
                        <optgroup label="{{ __('Sans-Serif') }}">
                            <option value="Montserrat" style="font-family: Montserrat;">Montserrat</option>
                            <option value="Poppins" style="font-family: Poppins;">Poppins</option>
                            <option value="Inter" style="font-family: Inter;">Inter</option>
                            <option value="Roboto" style="font-family: Roboto;">Roboto</option>
                            <option value="Open Sans" style="font-family: Open Sans;">Open Sans</option>
                            <option value="Lato" style="font-family: Lato;">Lato</option>
                        </optgroup>
                        <optgroup label="{{ __('Display') }}">
                            <option value="Oswald" style="font-family: Oswald;">Oswald</option>
                            <option value="Bebas Neue" style="font-family: Bebas Neue;">Bebas Neue</option>
                            <option value="Anton" style="font-family: Anton;">Anton</option>
                            <option value="Archivo Black" style="font-family: Archivo Black;">Archivo Black</option>
                            <option value="Black Ops One" style="font-family: Black Ops One;">Black Ops One</option>
                        </optgroup>
                        <optgroup label="{{ __('Serif') }}">
                            <option value="Playfair Display" style="font-family: Playfair Display;">Playfair Display</option>
                            <option value="Merriweather" style="font-family: Merriweather;">Merriweather</option>
                            <option value="Georgia" style="font-family: Georgia;">Georgia</option>
                        </optgroup>
                        <optgroup label="{{ __('Script') }}">
                            <option value="Dancing Script" style="font-family: Dancing Script;">Dancing Script</option>
                            <option value="Pacifico" style="font-family: Pacifico;">Pacifico</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            {{-- Font Size --}}
            <div class="vw-style-section">
                <div class="vw-style-row">
                    <span class="vw-style-label">{{ __('Size') }}</span>
                    <span class="vw-style-value">{{ number_format(($assembly['captions']['size'] ?? 1) * 100, 0) }}%</span>
                </div>
                <input
                    type="range"
                    wire:model.change="assembly.captions.size"
                    x-on:input="updateCaptionSetting('size', parseFloat($event.target.value))"
                    min="0.5" max="2" step="0.1"
                    class="vw-range-slider"
                >
                <div class="vw-range-labels">
                    <span>{{ __('Small') }}</span>
                    <span>{{ __('Large') }}</span>
                </div>
            </div>

            {{-- Position --}}
            <div class="vw-style-section">
                <div class="vw-style-label">{{ __('Position') }}</div>
                <div class="vw-position-grid">
                    @foreach(['top' => '↑ Top', 'middle' => '↔ Middle', 'bottom' => '↓ Bottom'] as $pos => $label)
                        <button
                            type="button"
                            wire:click="$set('assembly.captions.position', '{{ $pos }}')"
                            @click="updateCaptionSetting('position', '{{ $pos }}')"
                            class="vw-position-btn {{ ($assembly['captions']['position'] ?? 'bottom') === $pos ? 'active' : '' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Text Transform --}}
            <div class="vw-style-section">
                <div class="vw-style-label">{{ __('Text Transform') }}</div>
                <div class="vw-transform-grid">
                    @foreach(['none' => 'Aa', 'uppercase' => 'AA', 'lowercase' => 'aa', 'capitalize' => 'Aa'] as $transform => $label)
                        <button
                            type="button"
                            wire:click="$set('assembly.captions.textTransform', '{{ $transform }}')"
                            @click="updateCaptionSetting('textTransform', '{{ $transform }}')"
                            class="vw-transform-btn {{ ($assembly['captions']['textTransform'] ?? 'none') === $transform ? 'active' : '' }}"
                            title="{{ ucfirst($transform) }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Letter Spacing --}}
            <div class="vw-style-section">
                <div class="vw-style-row">
                    <span class="vw-style-label">{{ __('Letter Spacing') }}</span>
                    <span class="vw-style-value">{{ $assembly['captions']['letterSpacing'] ?? 0 }}px</span>
                </div>
                <input
                    type="range"
                    wire:model.change="assembly.captions.letterSpacing"
                    x-on:input="updateCaptionSetting('letterSpacing', parseInt($event.target.value))"
                    min="-2" max="10" step="1"
                    class="vw-range-slider"
                >
            </div>

            {{-- Line Height --}}
            <div class="vw-style-section">
                <div class="vw-style-row">
                    <span class="vw-style-label">{{ __('Line Height') }}</span>
                    <span class="vw-style-value">{{ number_format($assembly['captions']['lineHeight'] ?? 1.4, 1) }}</span>
                </div>
                <input
                    type="range"
                    wire:model.change="assembly.captions.lineHeight"
                    x-on:input="updateCaptionSetting('lineHeight', parseFloat($event.target.value))"
                    min="1" max="2.5" step="0.1"
                    class="vw-range-slider"
                >
            </div>
        </div>

        {{-- COLORS TAB --}}
        <div x-show="activeSubTab === 'colors'" x-cloak class="vw-tab-content">
            {{-- Fill Color --}}
            <div class="vw-color-section">
                <div class="vw-color-header">
                    <span class="vw-color-label">{{ __('Text Color') }}</span>
                    <div class="vw-color-picker-wrap">
                        <input
                            type="color"
                            wire:model.live="assembly.captions.fillColor"
                            x-on:change="customColors.fill = $event.target.value; updateCaptionSetting('fillColor', $event.target.value)"
                            value="{{ $assembly['captions']['fillColor'] ?? '#FFFFFF' }}"
                            class="vw-color-picker"
                        >
                        <span class="vw-color-hex" x-text="customColors.fill">{{ $assembly['captions']['fillColor'] ?? '#FFFFFF' }}</span>
                    </div>
                </div>
                <div class="vw-color-swatches">
                    @foreach(['#FFFFFF', '#000000', '#FBBF24', '#EF4444', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'] as $color)
                        <button
                            type="button"
                            wire:click="$set('assembly.captions.fillColor', '{{ $color }}')"
                            @click="customColors.fill = '{{ $color }}'; updateCaptionSetting('fillColor', '{{ $color }}')"
                            class="vw-swatch {{ ($assembly['captions']['fillColor'] ?? '#FFFFFF') === $color ? 'active' : '' }}"
                            style="background-color: {{ $color }};"
                        ></button>
                    @endforeach
                </div>
            </div>

            {{-- Stroke/Outline Color --}}
            <div class="vw-color-section">
                <div class="vw-color-header">
                    <span class="vw-color-label">{{ __('Outline Color') }}</span>
                    <div class="vw-color-picker-wrap">
                        <input
                            type="color"
                            wire:model.live="assembly.captions.strokeColor"
                            x-on:change="customColors.stroke = $event.target.value; updateCaptionSetting('strokeColor', $event.target.value)"
                            value="{{ $assembly['captions']['strokeColor'] ?? '#000000' }}"
                            class="vw-color-picker"
                        >
                        <span class="vw-color-hex" x-text="customColors.stroke">{{ $assembly['captions']['strokeColor'] ?? '#000000' }}</span>
                    </div>
                </div>
                <div class="vw-style-row">
                    <span class="vw-style-label">{{ __('Outline Width') }}</span>
                    <span class="vw-style-value">{{ $assembly['captions']['strokeWidth'] ?? 2 }}px</span>
                </div>
                <input
                    type="range"
                    wire:model.change="assembly.captions.strokeWidth"
                    x-on:input="updateCaptionSetting('strokeWidth', parseFloat($event.target.value))"
                    min="0" max="8" step="0.5"
                    class="vw-range-slider"
                >
            </div>

            {{-- Highlight Color (Karaoke) --}}
            <div class="vw-color-section highlight">
                <div class="vw-color-header">
                    <span class="vw-color-label">
                        {{ __('Highlight Color') }}
                        <span class="vw-badge karaoke">KARAOKE</span>
                    </span>
                    <div class="vw-color-picker-wrap">
                        <input
                            type="color"
                            wire:model.live="assembly.captions.highlightColor"
                            x-on:change="customColors.highlight = $event.target.value; updateCaptionSetting('highlightColor', $event.target.value)"
                            value="{{ $assembly['captions']['highlightColor'] ?? '#FBBF24' }}"
                            class="vw-color-picker"
                        >
                        <span class="vw-color-hex" x-text="customColors.highlight">{{ $assembly['captions']['highlightColor'] ?? '#FBBF24' }}</span>
                    </div>
                </div>
                <div class="vw-color-swatches">
                    @foreach(['#FBBF24', '#F59E0B', '#EF4444', '#EC4899', '#8B5CF6', '#06B6D4', '#10B981', '#84CC16'] as $color)
                        <button
                            type="button"
                            wire:click="$set('assembly.captions.highlightColor', '{{ $color }}')"
                            @click="customColors.highlight = '{{ $color }}'; updateCaptionSetting('highlightColor', '{{ $color }}')"
                            class="vw-swatch {{ ($assembly['captions']['highlightColor'] ?? '#FBBF24') === $color ? 'active' : '' }}"
                            style="background-color: {{ $color }};"
                        ></button>
                    @endforeach
                </div>
            </div>

            {{-- Background Color --}}
            <div class="vw-color-section">
                <div class="vw-color-header">
                    <span class="vw-color-label">{{ __('Background') }}</span>
                    <label class="vw-mini-toggle">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.captions.backgroundEnabled"
                            x-on:change="updateCaptionSetting('backgroundEnabled', $event.target.checked)"
                            {{ ($assembly['captions']['backgroundEnabled'] ?? false) ? 'checked' : '' }}
                        >
                        <span class="vw-mini-slider"></span>
                    </label>
                </div>
                @if($assembly['captions']['backgroundEnabled'] ?? false)
                    <div class="vw-bg-options">
                        <div class="vw-color-picker-wrap">
                            <input
                                type="color"
                                wire:model.live="assembly.captions.backgroundColor"
                                x-on:change="customColors.background = $event.target.value; updateCaptionSetting('backgroundColor', $event.target.value)"
                                value="{{ $assembly['captions']['backgroundColor'] ?? '#000000' }}"
                                class="vw-color-picker"
                            >
                            <span class="vw-color-hex">{{ $assembly['captions']['backgroundColor'] ?? '#000000' }}</span>
                        </div>
                        <div class="vw-style-row">
                            <span class="vw-style-label">{{ __('Opacity') }}</span>
                            <span class="vw-style-value">{{ ($assembly['captions']['backgroundOpacity'] ?? 0.7) * 100 }}%</span>
                        </div>
                        <input
                            type="range"
                            wire:model.change="assembly.captions.backgroundOpacity"
                            x-on:input="updateCaptionSetting('backgroundOpacity', parseFloat($event.target.value))"
                            min="0.1" max="1" step="0.1"
                            class="vw-range-slider"
                        >
                    </div>
                @endif
            </div>
        </div>

        {{-- EFFECTS TAB --}}
        <div x-show="activeSubTab === 'effects'" x-cloak class="vw-tab-content">
            {{-- Animation Effect --}}
            <div class="vw-effects-section">
                <div class="vw-effects-label">{{ __('Animation') }}</div>
                <div class="vw-effects-grid">
                    @php
                        $effects = [
                            ['id' => 'none', 'name' => 'None', 'icon' => '—', 'desc' => 'No animation'],
                            ['id' => 'pop', 'name' => 'Pop', 'icon' => '💥', 'desc' => 'Scale in'],
                            ['id' => 'fade', 'name' => 'Fade', 'icon' => '🌫️', 'desc' => 'Fade in'],
                            ['id' => 'slide', 'name' => 'Slide', 'icon' => '➡️', 'desc' => 'Slide in'],
                            ['id' => 'bounce', 'name' => 'Bounce', 'icon' => '⚡', 'desc' => 'Bounce in'],
                            ['id' => 'typewriter', 'name' => 'Type', 'icon' => '⌨️', 'desc' => 'Type effect'],
                        ];
                    @endphp

                    @foreach($effects as $effect)
                        <button
                            type="button"
                            wire:click="$set('assembly.captions.effect', '{{ $effect['id'] }}')"
                            @click="updateCaptionSetting('effect', '{{ $effect['id'] }}')"
                            class="vw-effect-card {{ ($assembly['captions']['effect'] ?? 'none') === $effect['id'] ? 'active' : '' }}"
                        >
                            <span class="vw-effect-icon">{{ $effect['icon'] }}</span>
                            <span class="vw-effect-name">{{ $effect['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Shadow --}}
            <div class="vw-effects-section">
                <div class="vw-effects-header">
                    <span class="vw-effects-label">{{ __('Shadow') }}</span>
                    <label class="vw-mini-toggle">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.captions.shadowEnabled"
                            x-on:change="updateCaptionSetting('shadowEnabled', $event.target.checked)"
                            {{ ($assembly['captions']['shadowEnabled'] ?? true) ? 'checked' : '' }}
                        >
                        <span class="vw-mini-slider"></span>
                    </label>
                </div>
                @if($assembly['captions']['shadowEnabled'] ?? true)
                    <div class="vw-shadow-controls">
                        <div class="vw-style-row">
                            <span class="vw-style-label">{{ __('Blur') }}</span>
                            <span class="vw-style-value">{{ $assembly['captions']['shadowBlur'] ?? 4 }}px</span>
                        </div>
                        <input
                            type="range"
                            wire:model.change="assembly.captions.shadowBlur"
                            x-on:input="updateCaptionSetting('shadowBlur', parseInt($event.target.value))"
                            min="0" max="20" step="1"
                            class="vw-range-slider"
                        >
                        <div class="vw-style-row">
                            <span class="vw-style-label">{{ __('Offset') }}</span>
                            <span class="vw-style-value">{{ $assembly['captions']['shadowOffset'] ?? 2 }}px</span>
                        </div>
                        <input
                            type="range"
                            wire:model.change="assembly.captions.shadowOffset"
                            x-on:input="updateCaptionSetting('shadowOffset', parseInt($event.target.value))"
                            min="0" max="10" step="1"
                            class="vw-range-slider"
                        >
                    </div>
                @endif
            </div>

            {{-- Glow Effect --}}
            <div class="vw-effects-section">
                <div class="vw-effects-header">
                    <span class="vw-effects-label">{{ __('Glow') }}</span>
                    <label class="vw-mini-toggle">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.captions.glowEnabled"
                            x-on:change="updateCaptionSetting('glowEnabled', $event.target.checked)"
                            {{ ($assembly['captions']['glowEnabled'] ?? false) ? 'checked' : '' }}
                        >
                        <span class="vw-mini-slider"></span>
                    </label>
                </div>
                @if($assembly['captions']['glowEnabled'] ?? false)
                    <div class="vw-glow-controls">
                        <div class="vw-color-header">
                            <span class="vw-style-label">{{ __('Glow Color') }}</span>
                            <input
                                type="color"
                                wire:model.live="assembly.captions.glowColor"
                                x-on:change="updateCaptionSetting('glowColor', $event.target.value)"
                                value="{{ $assembly['captions']['glowColor'] ?? '#8B5CF6' }}"
                                class="vw-color-picker small"
                            >
                        </div>
                        <div class="vw-style-row">
                            <span class="vw-style-label">{{ __('Intensity') }}</span>
                            <span class="vw-style-value">{{ $assembly['captions']['glowIntensity'] ?? 10 }}px</span>
                        </div>
                        <input
                            type="range"
                            wire:model.change="assembly.captions.glowIntensity"
                            x-on:input="updateCaptionSetting('glowIntensity', parseInt($event.target.value))"
                            min="5" max="30" step="1"
                            class="vw-range-slider"
                        >
                    </div>
                @endif
            </div>

            {{-- Animation Timing --}}
            <div class="vw-effects-section">
                <div class="vw-effects-label">{{ __('Timing') }}</div>
                <div class="vw-timing-controls">
                    <div class="vw-style-row">
                        <span class="vw-style-label">{{ __('Word Duration') }}</span>
                        <span class="vw-style-value">{{ number_format($assembly['captions']['wordDuration'] ?? 0.3, 1) }}s</span>
                    </div>
                    <input
                        type="range"
                        wire:model.change="assembly.captions.wordDuration"
                        x-on:input="updateCaptionSetting('wordDuration', parseFloat($event.target.value))"
                        min="0.1" max="1" step="0.1"
                        class="vw-range-slider"
                    >
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bebas+Neue&family=Black+Ops+One&family=Dancing+Script&family=Inter:wght@400;600;700&family=Lato:wght@400;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600;700&family=Oswald:wght@400;600;700&family=Pacifico&family=Playfair+Display:wght@400;700&family=Poppins:wght@400;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    .vw-text-tab {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Caption Toggle */
    .vw-caption-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-toggle-content {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .vw-toggle-icon {
        font-size: 1.25rem;
    }

    .vw-toggle-text {
        display: flex;
        flex-direction: column;
    }

    .vw-toggle-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-toggle-desc {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .vw-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .vw-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: 0.3s;
        border-radius: 24px;
    }

    .vw-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider {
        background: linear-gradient(135deg, #03fcf4, #06b6d4);
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider:before {
        transform: translateX(20px);
    }

    /* Sub Tabs */
    .vw-sub-tabs {
        display: flex;
        gap: 0.25rem;
        padding: 0.25rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-sub-tabs.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .vw-sub-tab {
        flex: 1;
        padding: 0.5rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 0.35rem;
        transition: all 0.2s;
    }

    .vw-sub-tab:hover {
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-sub-tab.active {
        background: linear-gradient(135deg, rgba(3, 252, 244, 0.3), rgba(6, 182, 212, 0.2));
        color: white;
    }

    /* Caption Content */
    .vw-caption-content {
        transition: opacity 0.3s;
    }

    .vw-caption-content.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .vw-tab-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Live Preview Box */
    .vw-caption-preview-box {
        padding: 1rem;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.5), rgba(20, 20, 30, 0.5));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        text-align: center;
    }

    .vw-preview-label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.5rem;
    }

    .vw-caption-live-preview {
        font-size: 1.2rem;
        font-weight: 700;
        color: white;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
    }

    .vw-caption-live-preview .highlighted {
        color: #FBBF24;
    }

    .vw-caption-live-preview.style-beasty {
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .vw-caption-live-preview.style-hormozi .highlighted {
        background: #FBBF24;
        color: #000;
        padding: 0.1em 0.3em;
        border-radius: 0.2em;
    }

    .vw-caption-live-preview.style-ali {
        text-shadow: 0 0 10px rgba(3, 252, 244, 0.8), 0 0 20px rgba(3, 252, 244, 0.4);
    }

    .vw-caption-live-preview.style-minimal {
        font-weight: 400;
        text-shadow: none;
    }

    .vw-caption-live-preview.style-neon {
        text-shadow: 0 0 5px #fff, 0 0 10px #fff, 0 0 15px #03fcf4, 0 0 20px #03fcf4;
    }

    /* Preset Cards */
    .vw-presets-section {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-presets-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        letter-spacing: 0.05em;
    }

    .vw-preset-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .vw-preset-card {
        display: flex;
        flex-direction: column;
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
    }

    .vw-preset-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(3, 252, 244, 0.3);
    }

    .vw-preset-card.active {
        background: linear-gradient(135deg, rgba(3, 252, 244, 0.2), rgba(6, 182, 212, 0.15));
        border-color: #03fcf4;
    }

    .vw-preset-preview {
        font-size: 0.7rem;
        font-weight: 700;
        color: white;
        padding: 0.4rem;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 0.25rem;
        margin-bottom: 0.4rem;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
    }

    .vw-preset-preview.preset-karaoke .highlighted {
        color: #FBBF24;
    }

    .vw-preset-preview.preset-beasty {
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .vw-preset-preview.preset-hormozi .highlighted {
        background: #FBBF24;
        color: #000;
        padding: 0 0.2em;
        border-radius: 0.15em;
    }

    .vw-preset-preview.preset-ali {
        text-shadow: 0 0 8px rgba(3, 252, 244, 0.8);
    }

    .vw-preset-preview.preset-minimal {
        font-weight: 400;
    }

    .vw-preset-preview.preset-neon {
        text-shadow: 0 0 5px #03fcf4, 0 0 10px #03fcf4;
    }

    .vw-preset-info {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-preset-icon {
        font-size: 0.9rem;
    }

    .vw-preset-text {
        display: flex;
        flex-direction: column;
    }

    .vw-preset-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-preset-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Mode Buttons */
    .vw-mode-section {
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-mode-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    .vw-mode-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .vw-mode-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-mode-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-mode-btn.active {
        background: rgba(3, 252, 244, 0.2);
        border-color: rgba(3, 252, 244, 0.4);
    }

    .vw-mode-icon {
        font-size: 1rem;
    }

    .vw-mode-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-mode-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Style Section */
    .vw-style-section {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-style-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-style-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .vw-style-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #03fcf4;
    }

    .vw-font-select {
        width: 100%;
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.4rem;
        color: white;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .vw-range-slider {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        cursor: pointer;
    }

    .vw-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #03fcf4, #06b6d4);
        cursor: pointer;
        border: 2px solid white;
    }

    .vw-range-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-position-grid,
    .vw-transform-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.35rem;
    }

    .vw-transform-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .vw-position-btn,
    .vw-transform-btn {
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-position-btn:hover,
    .vw-transform-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-position-btn.active,
    .vw-transform-btn.active {
        background: rgba(3, 252, 244, 0.2);
        border-color: rgba(3, 252, 244, 0.4);
        color: white;
    }

    /* Color Section */
    .vw-color-section {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-color-section.highlight {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05));
        border: 1px solid rgba(251, 191, 36, 0.2);
    }

    .vw-color-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .vw-color-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-badge {
        font-size: 0.5rem;
        padding: 0.15rem 0.3rem;
        border-radius: 0.2rem;
        font-weight: 600;
    }

    .vw-badge.karaoke {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .vw-color-picker-wrap {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-color-picker {
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 0.3rem;
        cursor: pointer;
        padding: 0;
        background: transparent;
    }

    .vw-color-picker.small {
        width: 24px;
        height: 24px;
    }

    .vw-color-hex {
        font-size: 0.7rem;
        font-family: monospace;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
    }

    .vw-color-swatches {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .vw-swatch {
        width: 24px;
        height: 24px;
        border-radius: 0.25rem;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-swatch:hover {
        transform: scale(1.1);
    }

    .vw-swatch.active {
        border-color: white;
        box-shadow: 0 0 0 2px rgba(3, 252, 244, 0.5);
    }

    /* Mini Toggle */
    .vw-mini-toggle {
        position: relative;
        display: inline-block;
        width: 32px;
        height: 18px;
    }

    .vw-mini-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .vw-mini-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: 0.3s;
        border-radius: 18px;
    }

    .vw-mini-slider:before {
        position: absolute;
        content: "";
        height: 12px;
        width: 12px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .vw-mini-toggle input:checked + .vw-mini-slider {
        background: linear-gradient(135deg, #03fcf4, #06b6d4);
    }

    .vw-mini-toggle input:checked + .vw-mini-slider:before {
        transform: translateX(14px);
    }

    .vw-bg-options,
    .vw-shadow-controls,
    .vw-glow-controls,
    .vw-timing-controls {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-top: 0.4rem;
    }

    /* Effects Grid */
    .vw-effects-section {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-effects-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .vw-effects-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 0.4rem;
    }

    .vw-effects-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.4rem;
    }

    .vw-effect-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        padding: 0.5rem 0.25rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-effect-card:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-effect-card.active {
        background: rgba(3, 252, 244, 0.2);
        border-color: rgba(3, 252, 244, 0.4);
    }

    .vw-effect-icon {
        font-size: 1rem;
    }

    .vw-effect-name {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.7);
    }

    [x-cloak] {
        display: none !important;
    }
</style>
