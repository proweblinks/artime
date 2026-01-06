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
</style>

<div class="vw-platform-step" x-data="{
    selectedFormat: '{{ $format }}',
    selectedType: '{{ $productionType }}',
    selectedSubtype: '{{ $productionSubtype }}'
}">
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
                <div @click="selectedFormat = '{{ $id }}'; $wire.selectFormat('{{ $id }}')"
                     :class="{ 'selected': selectedFormat === '{{ $id }}' }"
                     class="vw-format-card"
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
                <div @click="selectedType = '{{ $typeId }}'; selectedSubtype = ''; $wire.selectProductionType('{{ $typeId }}')"
                     :class="{ 'selected': selectedType === '{{ $typeId }}' }"
                     class="vw-production-card"
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
                        <div @click="selectedSubtype = '{{ $subId }}'; $wire.selectProductionType('{{ $productionType }}', '{{ $subId }}')"
                             :class="{ 'selected': selectedSubtype === '{{ $subId }}' }"
                             class="vw-subtype-card"
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

    {{-- Target Duration Card --}}
    @if($productionType && $productionSubtype)
        <div class="vw-content-card">
            <div class="vw-card-header">
                <div class="vw-card-icon">⏱️</div>
                <div>
                    <div class="vw-card-title">{{ __('Target Duration') }}</div>
                    <div class="vw-card-subtitle">
                        {{ __('Recommended for :type', ['type' => $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'] ?? $productionTypes[$productionType]['name']]) }}
                    </div>
                </div>
            </div>

            <div>
                <div class="vw-duration-header">
                    <span class="vw-duration-label">{{ __('Video Length') }}</span>
                    <span class="vw-duration-badge">
                        @if($targetDuration >= 60)
                            {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
                            {{ $targetDuration }}s
                        @endif
                    </span>
                </div>

                <input type="range"
                       wire:model.live="targetDuration"
                       min="{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15 }}"
                       max="{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300 }}"
                       class="vw-range" />

                <div class="vw-range-labels">
                    <span>{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15 }}s</span>
                    <span>{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300 }}s</span>
                </div>
            </div>
        </div>
    @endif
</div>
