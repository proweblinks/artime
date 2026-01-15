{{-- Step 1: Platform & Format Selection --}}

{{-- Embedded CSS for Platform Step (ensures styles aren't overridden) --}}
<style>
    /* Content Card Container */
    .vw-content-card {
        background: rgba(0, 0, 0, 0.03) !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 1.5rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-card-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-card-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        border-radius: 0.75rem !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(6, 182, 212, 0.15) 100%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-card-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: rgba(0, 0, 0, 0.8) !important;
        margin: 0 !important;
    }

    .vw-card-subtitle {
        font-size: 0.875rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
        margin: 0 !important;
    }

    /* Format Grid - 4 columns */
    .vw-format-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-format-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    /* Format Cards */
    .vw-format-card {
        background: rgba(0, 0, 0, 0.03) !important;
        border: 2px solid transparent !important;
        border-radius: 1rem !important;
        padding: 1.25rem 1rem !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .vw-format-card:hover {
        background: rgba(0, 0, 0, 0.06) !important;
        border-color: rgba(139, 92, 246, 0.3) !important;
    }

    .vw-format-card.selected {
        background: rgba(139, 92, 246, 0.1) !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.15) !important;
    }

    .vw-format-icon {
        font-size: 2rem !important;
        margin-bottom: 0.75rem !important;
        display: block !important;
    }

    .vw-format-name {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: rgba(0, 0, 0, 0.8) !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-format-card.selected .vw-format-name {
        color: #8b5cf6 !important;
    }

    .vw-format-ratio {
        font-size: 0.8rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-format-desc {
        font-size: 0.75rem !important;
        color: rgba(0, 0, 0, 0.4) !important;
    }

    /* Production Type Grid - 3 columns */
    .vw-production-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-production-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-production-card {
        background: rgba(0, 0, 0, 0.03) !important;
        border: 2px solid transparent !important;
        border-radius: 1rem !important;
        padding: 1.25rem !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .vw-production-card:hover {
        background: rgba(0, 0, 0, 0.06) !important;
        border-color: rgba(139, 92, 246, 0.3) !important;
    }

    .vw-production-card.selected {
        background: rgba(139, 92, 246, 0.1) !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.15) !important;
    }

    .vw-production-icon {
        font-size: 2rem !important;
        margin-bottom: 0.5rem !important;
        display: block !important;
    }

    .vw-production-name {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: rgba(0, 0, 0, 0.8) !important;
    }

    .vw-production-card.selected .vw-production-name {
        color: #8b5cf6 !important;
    }

    .vw-production-desc {
        font-size: 0.75rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
        margin-top: 0.25rem !important;
    }

    /* Subtype Grid - 4 columns */
    .vw-subtype-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.5rem !important;
    }

    @media (max-width: 1024px) {
        .vw-subtype-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }

    @media (max-width: 768px) {
        .vw-subtype-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-subtype-card {
        background: rgba(0, 0, 0, 0.03) !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .vw-subtype-card:hover {
        background: rgba(0, 0, 0, 0.06) !important;
        border-color: rgba(139, 92, 246, 0.3) !important;
    }

    .vw-subtype-card.selected {
        background: rgba(139, 92, 246, 0.1) !important;
        border-color: #8b5cf6 !important;
    }

    .vw-subtype-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    .vw-subtype-icon {
        font-size: 1.1rem !important;
    }

    .vw-subtype-name {
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        color: rgba(0, 0, 0, 0.7) !important;
    }

    .vw-subtype-card.selected .vw-subtype-name {
        color: #8b5cf6 !important;
    }

    .vw-subtype-desc {
        font-size: 0.7rem !important;
        color: rgba(0, 0, 0, 0.4) !important;
        margin-top: 0.25rem !important;
        margin-left: 1.6rem !important;
    }

    /* Duration section */
    .vw-divider {
        margin-top: 1.5rem !important;
        padding-top: 1.5rem !important;
        border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
    }

    .vw-subtype-label {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.875rem !important;
        color: rgba(0, 0, 0, 0.6) !important;
        margin-bottom: 1rem !important;
    }

    .vw-duration-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 1rem !important;
    }

    .vw-duration-label {
        font-size: 0.875rem !important;
        color: rgba(0, 0, 0, 0.6) !important;
    }

    .vw-duration-badge {
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
        color: white !important;
        padding: 0.25rem 0.75rem !important;
        border-radius: 1rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
    }

    .vw-range {
        width: 100% !important;
        height: 8px !important;
        border-radius: 4px !important;
        background: rgba(0, 0, 0, 0.1) !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        cursor: pointer !important;
    }

    .vw-range::-webkit-slider-thumb {
        appearance: none !important;
        -webkit-appearance: none !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
        cursor: pointer !important;
    }

    .vw-range-labels {
        display: flex !important;
        justify-content: space-between !important;
        font-size: 0.75rem !important;
        color: rgba(0, 0, 0, 0.4) !important;
        margin-top: 0.5rem !important;
    }

    /* ========================================
       PRODUCTION SETTINGS - Modern 3-Column Layout
       ======================================== */

    .vw-settings-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5rem !important;
    }

    @media (max-width: 992px) {
        .vw-settings-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }
    }

    .vw-setting-section {
        background: rgba(255, 255, 255, 0.6) !important;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        border-radius: 1rem !important;
        padding: 1.25rem !important;
    }

    .vw-setting-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-setting-icon {
        font-size: 1.25rem !important;
    }

    .vw-setting-title {
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        color: rgba(0, 0, 0, 0.8) !important;
    }

    /* AI Model Tier Cards */
    .vw-tier-options {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.5rem !important;
    }

    .vw-tier-card {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0.75rem !important;
        border: 2px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 0.75rem !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        background: white !important;
    }

    .vw-tier-card:hover {
        border-color: rgba(139, 92, 246, 0.3) !important;
        background: rgba(139, 92, 246, 0.02) !important;
    }

    .vw-tier-card.selected {
        border-color: #8b5cf6 !important;
        background: rgba(139, 92, 246, 0.08) !important;
    }

    .vw-tier-icon {
        font-size: 1.25rem !important;
        width: 32px !important;
        text-align: center !important;
    }

    .vw-tier-info {
        flex: 1 !important;
        min-width: 0 !important;
    }

    .vw-tier-name {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: rgba(0, 0, 0, 0.8) !important;
        display: inline !important;
    }

    .vw-tier-card.selected .vw-tier-name {
        color: #8b5cf6 !important;
    }

    .vw-tier-model {
        font-size: 0.7rem !important;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
        color: rgba(0, 0, 0, 0.4) !important;
        margin-left: 0.35rem !important;
        padding: 0.1rem 0.35rem !important;
        background: rgba(0, 0, 0, 0.05) !important;
        border-radius: 0.25rem !important;
    }

    .vw-tier-card.selected .vw-tier-model {
        color: rgba(139, 92, 246, 0.7) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-tier-price {
        font-size: 0.7rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
    }

    .vw-tier-badge {
        font-size: 0.55rem !important;
        font-weight: 700 !important;
        padding: 0.15rem 0.4rem !important;
        border-radius: 0.25rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em !important;
    }

    .vw-tier-badge.green {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #059669 !important;
    }

    .vw-tier-badge.blue {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #2563eb !important;
    }

    .vw-tier-badge.purple {
        background: rgba(139, 92, 246, 0.15) !important;
        color: #7c3aed !important;
    }

    /* Language Selector - Custom Dropdown with Flag Images */
    .vw-lang-dropdown {
        position: relative !important;
    }

    .vw-lang-trigger {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        width: 100% !important;
        padding: 0.75rem 1rem !important;
        border: 2px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 0.75rem !important;
        background: white !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .vw-lang-trigger:hover {
        border-color: rgba(139, 92, 246, 0.3) !important;
        background: rgba(139, 92, 246, 0.02) !important;
    }

    .vw-lang-trigger.open {
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-lang-trigger-flag {
        width: 28px !important;
        height: 20px !important;
        border-radius: 3px !important;
        object-fit: cover !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
    }

    .vw-lang-trigger-text {
        flex: 1 !important;
        font-size: 0.9rem !important;
        font-weight: 500 !important;
        color: rgba(0, 0, 0, 0.8) !important;
        text-align: left !important;
    }

    .vw-lang-trigger-arrow {
        width: 16px !important;
        height: 16px !important;
        color: rgba(0, 0, 0, 0.4) !important;
        transition: transform 0.2s ease !important;
    }

    .vw-lang-trigger.open .vw-lang-trigger-arrow {
        transform: rotate(180deg) !important;
    }

    .vw-lang-menu {
        position: absolute !important;
        top: calc(100% + 4px) !important;
        left: 0 !important;
        right: 0 !important;
        background: white !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
        max-height: 280px !important;
        overflow-y: auto !important;
        z-index: 100 !important;
        display: none !important;
    }

    .vw-lang-menu.open {
        display: block !important;
    }

    .vw-lang-option {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0.625rem 1rem !important;
        cursor: pointer !important;
        transition: background 0.15s ease !important;
    }

    .vw-lang-option:first-child {
        border-radius: 0.75rem 0.75rem 0 0 !important;
    }

    .vw-lang-option:last-child {
        border-radius: 0 0 0.75rem 0.75rem !important;
    }

    .vw-lang-option:hover {
        background: rgba(139, 92, 246, 0.08) !important;
    }

    .vw-lang-option.selected {
        background: rgba(139, 92, 246, 0.12) !important;
    }

    .vw-lang-option-flag {
        width: 24px !important;
        height: 16px !important;
        border-radius: 2px !important;
        object-fit: cover !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
    }

    .vw-lang-option-text {
        flex: 1 !important;
        font-size: 0.85rem !important;
        color: rgba(0, 0, 0, 0.8) !important;
    }

    .vw-lang-option-native {
        font-size: 0.75rem !important;
        color: rgba(0, 0, 0, 0.4) !important;
    }

    .vw-lang-option-check {
        width: 16px !important;
        height: 16px !important;
        color: #8b5cf6 !important;
        opacity: 0 !important;
    }

    .vw-lang-option.selected .vw-lang-option-check {
        opacity: 1 !important;
    }

    .vw-language-preview {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0.75rem 1rem !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(6, 182, 212, 0.08) 100%) !important;
        border-radius: 0.75rem !important;
        margin-top: 0.75rem !important;
        border: 1px solid rgba(139, 92, 246, 0.1) !important;
    }

    .vw-language-flag {
        width: 32px !important;
        height: 22px !important;
        border-radius: 3px !important;
        object-fit: cover !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
    }

    .vw-language-info {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.1rem !important;
    }

    .vw-language-name {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: rgba(0, 0, 0, 0.8) !important;
    }

    .vw-language-desc {
        font-size: 0.75rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
    }

    /* Duration Section Enhancement */
    .vw-duration-display {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        padding: 0.75rem !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%) !important;
        border-radius: 0.75rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-duration-value {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }

    .vw-duration-unit {
        font-size: 0.8rem !important;
        color: rgba(0, 0, 0, 0.5) !important;
    }

    .vw-duration-slider-wrap {
        padding: 0 0.25rem !important;
    }
</style>

<div class="vw-platform-step">
    {{-- Video Format Card --}}
    <div class="vw-content-card">
        <div class="vw-card-header">
            <div class="vw-card-icon">📐</div>
            <div>
                <div class="vw-card-title">{{ __('Video Format') }}</div>
                <div class="vw-card-subtitle">{{ __('Choose your aspect ratio') }}</div>
            </div>
        </div>

        <div class="vw-format-grid">
            @foreach($formats as $id => $formatConfig)
                <div wire:click="selectFormat('{{ $id }}')"
                     class="vw-format-card {{ $format === $id ? 'selected' : '' }}"
                     style="cursor: pointer;">
                    <span class="vw-format-icon" style="pointer-events: none;">
                        @switch($id)
                            @case('widescreen') 🖥️ @break
                            @case('vertical') 📱 @break
                            @case('square') ⬜ @break
                            @case('tall') 📐 @break
                            @default 🎬
                        @endswitch
                    </span>
                    <div class="vw-format-name">{{ $formatConfig['name'] }}</div>
                    <div class="vw-format-ratio">{{ $formatConfig['aspectRatio'] }}</div>
                    <div class="vw-format-desc">{{ $formatConfig['description'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Production Type Card --}}
    <div class="vw-content-card">
        <div class="vw-card-header">
            <div class="vw-card-icon">🎬</div>
            <div>
                <div class="vw-card-title">{{ __('What are you creating?') }}</div>
                <div class="vw-card-subtitle">{{ __('Select your production type') }}</div>
            </div>
        </div>

        <div class="vw-production-grid">
            @foreach($productionTypes as $typeId => $type)
                <div wire:click="selectProductionType('{{ $typeId }}')"
                     class="vw-production-card {{ $productionType === $typeId ? 'selected' : '' }}"
                     style="cursor: pointer;">
                    <span class="vw-production-icon" style="pointer-events: none;">
                        @switch($typeId)
                            @case('social') 📱 @break
                            @case('movie') 🎬 @break
                            @case('series') 📺 @break
                            @case('educational') 🎓 @break
                            @case('music') 🎵 @break
                            @case('commercial') 📢 @break
                            @default 🎯
                        @endswitch
                    </span>
                    <div class="vw-production-name">{{ $type['name'] }}</div>
                    <div class="vw-production-desc">{{ $type['description'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Sub-type Selection --}}
        @if($productionType && isset($productionTypes[$productionType]['subTypes']))
            <div class="vw-divider">
                <div class="vw-subtype-label">
                    <span>{{ $productionTypes[$productionType]['icon'] ?? '🎬' }}</span>
                    <span>{{ __('Select :name Style:', ['name' => $productionTypes[$productionType]['name']]) }}</span>
                </div>

                <div class="vw-subtype-grid">
                    @foreach($productionTypes[$productionType]['subTypes'] as $subId => $subType)
                        <div wire:click="selectProductionType('{{ $productionType }}', '{{ $subId }}')"
                             class="vw-subtype-card {{ $productionSubtype === $subId ? 'selected' : '' }}"
                             style="cursor: pointer;">
                            <div class="vw-subtype-header">
                                <span class="vw-subtype-icon">{{ $subType['icon'] ?? '🎯' }}</span>
                                <span class="vw-subtype-name">{{ $subType['name'] }}</span>
                            </div>
                            <div class="vw-subtype-desc">{{ $subType['description'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Production Settings Card - Appears after selections are made --}}
    @if($productionType && $productionSubtype)
        @php
            $aiModelTiers = \Modules\AppVideoWizard\Livewire\VideoWizard::AI_MODEL_TIERS;
            $languages = \Modules\AppVideoWizard\Livewire\VideoWizard::SUPPORTED_LANGUAGES;
            $selectedTier = $content['aiModelTier'] ?? 'economy';
            $selectedLang = $content['language'] ?? 'en';
            $durationMin = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15;
            $durationMax = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300;
        @endphp
        <div class="vw-content-card">
            <div class="vw-card-header">
                <div class="vw-card-icon">⚙️</div>
                <div>
                    <div class="vw-card-title">{{ __('Production Settings') }}</div>
                    <div class="vw-card-subtitle">
                        {{ __('Configure AI model, language, and duration') }}
                    </div>
                </div>
            </div>

            <div class="vw-settings-grid">
                {{-- AI Model Tier Selection --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon">🤖</span>
                        <span class="vw-setting-title">{{ __('AI Model') }}</span>
                    </div>
                    <div class="vw-tier-options">
                        @foreach($aiModelTiers as $tierKey => $tier)
                            <div class="vw-tier-card {{ $selectedTier === $tierKey ? 'selected' : '' }}"
                                 wire:click="$set('content.aiModelTier', '{{ $tierKey }}')">
                                <span class="vw-tier-icon">{{ $tier['icon'] }}</span>
                                <div class="vw-tier-info">
                                    <div>
                                        <span class="vw-tier-name">{{ $tier['label'] }}</span>
                                        <span class="vw-tier-model">{{ $tier['model'] }}</span>
                                    </div>
                                    <div class="vw-tier-price">{{ $tier['pricing'] }}</div>
                                </div>
                                <span class="vw-tier-badge {{ $tier['badgeColor'] }}">{{ $tier['badge'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Language Selection --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon">🌍</span>
                        <span class="vw-setting-title">{{ __('Content Language') }}</span>
                    </div>
                    <div class="vw-lang-dropdown" x-data="{ open: false }" @click.away="open = false">
                        {{-- Dropdown Trigger --}}
                        <div class="vw-lang-trigger" :class="{ 'open': open }" @click="open = !open">
                            <img src="https://flagcdn.com/w40/{{ $languages[$selectedLang]['country'] ?? 'us' }}.png"
                                 srcset="https://flagcdn.com/w80/{{ $languages[$selectedLang]['country'] ?? 'us' }}.png 2x"
                                 class="vw-lang-trigger-flag"
                                 alt="{{ $languages[$selectedLang]['name'] ?? 'English' }}">
                            <span class="vw-lang-trigger-text">{{ $languages[$selectedLang]['name'] ?? 'English' }} ({{ $languages[$selectedLang]['native'] ?? 'English' }})</span>
                            <svg class="vw-lang-trigger-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </div>

                        {{-- Dropdown Menu --}}
                        <div class="vw-lang-menu" :class="{ 'open': open }">
                            @foreach($languages as $langCode => $lang)
                                <div class="vw-lang-option {{ $selectedLang === $langCode ? 'selected' : '' }}"
                                     wire:click="$set('content.language', '{{ $langCode }}')"
                                     @click="open = false">
                                    <img src="https://flagcdn.com/w40/{{ $lang['country'] }}.png"
                                         srcset="https://flagcdn.com/w80/{{ $lang['country'] }}.png 2x"
                                         class="vw-lang-option-flag"
                                         alt="{{ $lang['name'] }}">
                                    <span class="vw-lang-option-text">{{ $lang['name'] }}</span>
                                    <span class="vw-lang-option-native">{{ $lang['native'] }}</span>
                                    <svg class="vw-lang-option-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if(isset($languages[$selectedLang]))
                        <div class="vw-language-preview">
                            <img src="https://flagcdn.com/w80/{{ $languages[$selectedLang]['country'] }}.png"
                                 srcset="https://flagcdn.com/w160/{{ $languages[$selectedLang]['country'] }}.png 2x"
                                 class="vw-language-flag"
                                 alt="{{ $languages[$selectedLang]['name'] }}">
                            <div class="vw-language-info">
                                <span class="vw-language-name">{{ $languages[$selectedLang]['name'] }}</span>
                                <span class="vw-language-desc">{{ __('Script & voiceover in :lang', ['lang' => $languages[$selectedLang]['native']]) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Video Duration --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon">⏱️</span>
                        <span class="vw-setting-title">{{ __('Video Duration') }}</span>
                    </div>
                    <div class="vw-duration-display">
                        <span class="vw-duration-value">
                            @if($targetDuration >= 60)
                                {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}
                            @else
                                {{ $targetDuration }}s
                            @endif
                        </span>
                        <span class="vw-duration-unit">
                            @if($targetDuration >= 60)
                                {{ __('minutes') }}
                            @else
                                {{ __('seconds') }}
                            @endif
                        </span>
                    </div>
                    <div class="vw-duration-slider-wrap">
                        <input type="range"
                               wire:model.live="targetDuration"
                               min="{{ $durationMin }}"
                               max="{{ $durationMax }}"
                               class="vw-range" />
                        <div class="vw-range-labels">
                            <span>{{ $durationMin }}s</span>
                            <span>{{ $durationMax }}s</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
