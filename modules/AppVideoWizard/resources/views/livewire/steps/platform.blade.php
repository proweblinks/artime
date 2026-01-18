{{-- Step 1: Platform & Format Selection --}}

{{-- Scoped CSS for Platform Step - uses parent selector for specificity instead of !important --}}
<style>
    /* Content Card Container */
    .vw-platform-step .vw-content-card {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .vw-platform-step .vw-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .vw-platform-step .vw-card-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(6, 182, 212, 0.15) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .vw-platform-step .vw-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.8);
        margin: 0;
    }

    .vw-platform-step .vw-card-subtitle {
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.5);
        margin: 0;
    }

    /* Format Grid - 4 columns */
    .vw-platform-step .vw-format-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-format-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Format Cards */
    .vw-platform-step .vw-format-card {
        background: rgba(0, 0, 0, 0.03);
        border: 2px solid transparent;
        border-radius: 1rem;
        padding: 1.25rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .vw-platform-step .vw-format-card:hover {
        background: rgba(0, 0, 0, 0.06);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-platform-step .vw-format-card.selected {
        background: rgba(139, 92, 246, 0.1);
        border-color: #8b5cf6;
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.15);
    }

    .vw-platform-step .vw-format-card.recommended::after {
        content: '★';
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        font-size: 0.75rem;
        color: #f59e0b;
    }

    .vw-platform-step .vw-format-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        display: block;
    }

    .vw-platform-step .vw-format-name {
        font-size: 0.95rem;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.8);
        margin-bottom: 0.25rem;
    }

    .vw-platform-step .vw-format-card.selected .vw-format-name {
        color: #8b5cf6;
    }

    .vw-platform-step .vw-format-ratio {
        font-size: 0.8rem;
        color: rgba(0, 0, 0, 0.5);
        margin-bottom: 0.25rem;
    }

    .vw-platform-step .vw-format-desc {
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.4);
    }

    .vw-platform-step .vw-format-recommendation {
        font-size: 0.7rem;
        color: #f59e0b;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    /* Production Type Grid - 3 columns */
    .vw-platform-step .vw-production-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-production-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-platform-step .vw-production-card {
        background: rgba(0, 0, 0, 0.03);
        border: 2px solid transparent;
        border-radius: 1rem;
        padding: 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-platform-step .vw-production-card:hover {
        background: rgba(0, 0, 0, 0.06);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-platform-step .vw-production-card.selected {
        background: rgba(139, 92, 246, 0.1);
        border-color: #8b5cf6;
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.15);
    }

    .vw-platform-step .vw-production-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .vw-platform-step .vw-production-name {
        font-size: 0.95rem;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.8);
    }

    .vw-platform-step .vw-production-card.selected .vw-production-name {
        color: #8b5cf6;
    }

    .vw-platform-step .vw-production-desc {
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.5);
        margin-top: 0.25rem;
    }

    /* Subtype Grid - 4 columns */
    .vw-platform-step .vw-subtype-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    @media (max-width: 1024px) {
        .vw-platform-step .vw-subtype-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-subtype-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-platform-step .vw-subtype-card {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.5rem;
        padding: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-platform-step .vw-subtype-card:hover {
        background: rgba(0, 0, 0, 0.06);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-platform-step .vw-subtype-card.selected {
        background: rgba(139, 92, 246, 0.1);
        border-color: #8b5cf6;
    }

    .vw-platform-step .vw-subtype-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-platform-step .vw-subtype-icon {
        font-size: 1.1rem;
    }

    .vw-platform-step .vw-subtype-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.7);
    }

    .vw-platform-step .vw-subtype-card.selected .vw-subtype-name {
        color: #8b5cf6;
    }

    .vw-platform-step .vw-subtype-desc {
        font-size: 0.7rem;
        color: rgba(0, 0, 0, 0.4);
        margin-top: 0.25rem;
        margin-left: 1.6rem;
    }

    /* Divider */
    .vw-platform-step .vw-divider {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    .vw-platform-step .vw-subtype-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.6);
        margin-bottom: 1rem;
    }

    /* Production Settings - Modern 3-Column Layout */
    .vw-platform-step .vw-settings-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 992px) {
        .vw-platform-step .vw-settings-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    .vw-platform-step .vw-setting-section {
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 1rem;
        padding: 1.25rem;
    }

    .vw-platform-step .vw-setting-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-platform-step .vw-setting-icon {
        font-size: 1.25rem;
    }

    .vw-platform-step .vw-setting-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.8);
    }

    /* AI Model Tier Cards */
    .vw-platform-step .vw-tier-options {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-platform-step .vw-tier-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 2px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
    }

    .vw-platform-step .vw-tier-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.02);
    }

    .vw-platform-step .vw-tier-card.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.08);
    }

    .vw-platform-step .vw-tier-icon {
        font-size: 1.25rem;
        width: 32px;
        text-align: center;
    }

    .vw-platform-step .vw-tier-info {
        flex: 1;
        min-width: 0;
    }

    .vw-platform-step .vw-tier-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.8);
        display: inline;
    }

    .vw-platform-step .vw-tier-card.selected .vw-tier-name {
        color: #8b5cf6;
    }

    .vw-platform-step .vw-tier-model {
        font-size: 0.7rem;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        color: rgba(0, 0, 0, 0.4);
        margin-left: 0.35rem;
        padding: 0.1rem 0.35rem;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 0.25rem;
    }

    .vw-platform-step .vw-tier-card.selected .vw-tier-model {
        color: rgba(139, 92, 246, 0.7);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-platform-step .vw-tier-price {
        font-size: 0.7rem;
        color: rgba(0, 0, 0, 0.5);
    }

    .vw-platform-step .vw-tier-badge {
        font-size: 0.55rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .vw-platform-step .vw-tier-badge.green {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
    }

    .vw-platform-step .vw-tier-badge.blue {
        background: rgba(59, 130, 246, 0.15);
        color: #2563eb;
    }

    .vw-platform-step .vw-tier-badge.purple {
        background: rgba(139, 92, 246, 0.15);
        color: #7c3aed;
    }

    /* Language Selector - Custom Dropdown */
    .vw-platform-step .vw-lang-dropdown {
        position: relative;
    }

    .vw-platform-step .vw-lang-trigger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-platform-step .vw-lang-trigger:hover {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.02);
    }

    .vw-platform-step .vw-lang-trigger.open {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .vw-platform-step .vw-lang-trigger-flag {
        width: 28px;
        height: 20px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .vw-platform-step .vw-lang-trigger-text {
        flex: 1;
        font-size: 0.9rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.8);
        text-align: left;
    }

    .vw-platform-step .vw-lang-trigger-arrow {
        width: 16px;
        height: 16px;
        color: rgba(0, 0, 0, 0.4);
        transition: transform 0.2s ease;
    }

    .vw-platform-step .vw-lang-trigger.open .vw-lang-trigger-arrow {
        transform: rotate(180deg);
    }

    .vw-platform-step .vw-lang-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        max-height: 280px;
        overflow-y: auto;
        z-index: 100;
        display: none;
    }

    .vw-platform-step .vw-lang-menu.open {
        display: block;
    }

    .vw-platform-step .vw-lang-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 1rem;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .vw-platform-step .vw-lang-option:first-child {
        border-radius: 0.75rem 0.75rem 0 0;
    }

    .vw-platform-step .vw-lang-option:last-child {
        border-radius: 0 0 0.75rem 0.75rem;
    }

    .vw-platform-step .vw-lang-option:hover {
        background: rgba(139, 92, 246, 0.08);
    }

    .vw-platform-step .vw-lang-option.selected {
        background: rgba(139, 92, 246, 0.12);
    }

    .vw-platform-step .vw-lang-option-flag {
        width: 24px;
        height: 16px;
        border-radius: 2px;
        object-fit: cover;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .vw-platform-step .vw-lang-option-text {
        flex: 1;
        font-size: 0.85rem;
        color: rgba(0, 0, 0, 0.8);
    }

    .vw-platform-step .vw-lang-option-native {
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.4);
    }

    .vw-platform-step .vw-lang-option-check {
        width: 16px;
        height: 16px;
        color: #8b5cf6;
        opacity: 0;
    }

    .vw-platform-step .vw-lang-option.selected .vw-lang-option-check {
        opacity: 1;
    }

    .vw-platform-step .vw-language-preview {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(6, 182, 212, 0.08) 100%);
        border-radius: 0.75rem;
        margin-top: 0.75rem;
        border: 1px solid rgba(139, 92, 246, 0.1);
    }

    .vw-platform-step .vw-language-flag {
        width: 32px;
        height: 22px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }

    .vw-platform-step .vw-language-info {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    .vw-platform-step .vw-language-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.8);
    }

    .vw-platform-step .vw-language-desc {
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.5);
    }

    /* Duration Section */
    .vw-platform-step .vw-duration-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
    }

    .vw-platform-step .vw-duration-value {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .vw-platform-step .vw-duration-unit {
        font-size: 0.8rem;
        color: rgba(0, 0, 0, 0.5);
    }

    .vw-platform-step .vw-duration-slider-wrap {
        padding: 0 0.25rem;
    }

    .vw-platform-step .vw-range {
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: rgba(0, 0, 0, 0.1);
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    .vw-platform-step .vw-range::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        cursor: pointer;
    }

    .vw-platform-step .vw-range::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        cursor: pointer;
        border: none;
    }

    .vw-platform-step .vw-range-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: rgba(0, 0, 0, 0.4);
        margin-top: 0.5rem;
    }

    /* Format Guidance Alert */
    .vw-platform-step .vw-format-guidance {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: rgba(0, 0, 0, 0.7);
    }

    .vw-platform-step .vw-format-guidance-icon {
        font-size: 1rem;
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

        {{-- Format guidance based on production type --}}
        @if($productionType)
            @php
                $formatGuidance = match($productionType) {
                    'movie', 'series' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for cinematic content')],
                    'social' => ['format' => 'vertical', 'text' => __('Recommended: Vertical (9:16) for social media')],
                    'music' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) or Square (1:1) for music videos')],
                    'commercial' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for commercials')],
                    'educational' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for educational content')],
                    default => null
                };
            @endphp
            @if($formatGuidance)
                <div class="vw-format-guidance">
                    <span class="vw-format-guidance-icon">💡</span>
                    <span>{{ $formatGuidance['text'] }}</span>
                </div>
            @endif
        @endif

        <div class="vw-format-grid">
            @foreach($formats as $id => $formatConfig)
                @php
                    $isRecommended = isset($formatGuidance) && $formatGuidance && $formatGuidance['format'] === $id;
                @endphp
                <div wire:click="selectFormat('{{ $id }}')"
                     class="vw-format-card {{ $format === $id ? 'selected' : '' }} {{ $isRecommended ? 'recommended' : '' }}"
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
                    @if($isRecommended)
                        <div class="vw-format-recommendation">{{ __('Recommended') }}</div>
                    @endif
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

    {{-- Production Settings Card - Now appears after just productionType is selected --}}
    @if($productionType)
        @php
            $aiModelTiers = \Modules\AppVideoWizard\Livewire\VideoWizard::AI_MODEL_TIERS;
            $languages = \Modules\AppVideoWizard\Livewire\VideoWizard::SUPPORTED_LANGUAGES;
            $selectedTier = $content['aiModelTier'] ?? 'economy';
            $selectedLang = $content['language'] ?? 'en';

            // Get duration range - use subtype if available, otherwise use type defaults
            if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration'])) {
                $durationMin = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15;
                $durationMax = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300;
            } else {
                // Default duration range when subtype not yet selected
                $durationMin = 15;
                $durationMax = 300;
            }
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
