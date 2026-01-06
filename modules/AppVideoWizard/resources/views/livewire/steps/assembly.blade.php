{{-- Step 6: Assembly --}}
<style>
    .vw-assembly-step {
        width: 100%;
    }

    .vw-assembly-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-assembly-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-assembly-icon {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        background: linear-gradient(135deg, #f59e0b 0%, #ec4899 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.25rem !important;
    }

    .vw-assembly-title {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-assembly-subtitle {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.15rem !important;
    }

    /* Grid Layout */
    .vw-assembly-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .vw-assembly-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Preview Section */
    .vw-preview-section {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .vw-preview-video {
        width: 100%;
        aspect-ratio: 16/9;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-preview-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-preview-placeholder-icon {
        font-size: 2.5rem;
    }

    .vw-preview-placeholder-text {
        font-size: 0.8rem;
    }

    .vw-preview-controls {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: rgba(0, 0, 0, 0.5);
    }

    .vw-play-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .vw-play-btn:hover {
        transform: scale(1.05);
    }

    .vw-timeline-slider {
        flex: 1;
        -webkit-appearance: none;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        outline: none;
    }

    .vw-timeline-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 12px;
        height: 12px;
        background: #8b5cf6;
        border-radius: 50%;
        cursor: pointer;
    }

    .vw-time-display {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        font-family: monospace;
    }

    /* Settings Section */
    .vw-settings-section {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .vw-setting-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1rem;
        transition: all 0.2s;
    }

    .vw-setting-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-setting-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-setting-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-setting-title-icon {
        font-size: 1rem;
    }

    /* Toggle Switch */
    .vw-toggle {
        position: relative;
        width: 44px;
        height: 24px;
    }

    .vw-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .vw-toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        transition: 0.3s;
    }

    .vw-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    .vw-toggle input:checked + .vw-toggle-slider {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    }

    .vw-toggle input:checked + .vw-toggle-slider:before {
        transform: translateX(20px);
    }

    /* Select Input */
    .vw-select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.8rem;
        outline: none;
        cursor: pointer;
    }

    .vw-select:focus {
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-select option {
        background: #1a1a2e;
        color: white;
    }

    /* Subsetting Grid */
    .vw-subsetting-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-top: 0.75rem;
    }

    .vw-subsetting {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .vw-subsetting-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Range Slider */
    .vw-range-section {
        margin-top: 0.75rem;
    }

    .vw-range-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .vw-range-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-range-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #a78bfa;
    }

    .vw-range-slider {
        width: 100%;
        -webkit-appearance: none;
        height: 4px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 2px;
        outline: none;
    }

    .vw-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 50%;
        cursor: pointer;
    }

    /* Transition Options */
    .vw-transition-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    @media (max-width: 640px) {
        .vw-transition-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-transition-btn {
        padding: 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.03);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.7rem;
        text-align: center;
        transition: all 0.2s;
    }

    .vw-transition-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-transition-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        color: white;
    }

    .vw-transition-btn-icon {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    /* Caption Preview */
    .vw-caption-preview {
        margin-top: 0.75rem;
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
        text-align: center;
    }

    .vw-caption-preview-text {
        font-size: 0.85rem;
        color: white;
        font-weight: 500;
    }

    .vw-caption-preview-text.karaoke {
        background: linear-gradient(90deg, #fbbf24 30%, white 30%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .vw-caption-preview-text.typewriter {
        animation: vw-typewriter 3s steps(20) infinite;
        overflow: hidden;
        white-space: nowrap;
        border-right: 2px solid #8b5cf6;
    }

    @keyframes vw-typewriter {
        0%, 100% { width: 0; }
        50% { width: 100%; }
    }

    /* Ready Indicator */
    .vw-ready-banner {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .vw-ready-icon {
        font-size: 1.5rem;
    }

    .vw-ready-text {
        flex: 1;
    }

    .vw-ready-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #10b981;
    }

    .vw-ready-subtitle {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-continue-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s;
    }

    .vw-continue-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
</style>

<div class="vw-assembly-step">
    {{-- Main Card --}}
    <div class="vw-assembly-card">
        {{-- Header --}}
        <div class="vw-assembly-header">
            <div class="vw-assembly-icon">🎞️</div>
            <div style="flex: 1;">
                <h2 class="vw-assembly-title">{{ __('Assembly Studio') }}</h2>
                <p class="vw-assembly-subtitle">{{ __('Configure transitions, music, and captions') }}</p>
            </div>
        </div>

        {{-- Grid Layout --}}
        <div class="vw-assembly-grid">
            {{-- Preview Section --}}
            <div class="vw-preview-section">
                <div class="vw-preview-video">
                    <div class="vw-preview-placeholder">
                        <span class="vw-preview-placeholder-icon">🎬</span>
                        <span class="vw-preview-placeholder-text">{{ __('Preview will appear here') }}</span>
                    </div>
                </div>
                <div class="vw-preview-controls">
                    <button type="button" class="vw-play-btn" id="play-btn">▶</button>
                    <input type="range" class="vw-timeline-slider" id="timeline-slider" min="0" max="100" value="0">
                    <span class="vw-time-display" id="time-display">0:00 / {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>

            {{-- Settings Section --}}
            <div class="vw-settings-section">
                {{-- Transitions --}}
                <div class="vw-setting-card">
                    <div class="vw-setting-header">
                        <div class="vw-setting-title">
                            <span class="vw-setting-title-icon">🔀</span>
                            <span>{{ __('Transitions') }}</span>
                        </div>
                    </div>
                    <div class="vw-transition-grid">
                        @php
                            $transitions = [
                                'cut' => ['icon' => '✂️', 'name' => 'Cut'],
                                'fade' => ['icon' => '🌫️', 'name' => 'Fade'],
                                'slide-left' => ['icon' => '⬅️', 'name' => 'Slide L'],
                                'slide-right' => ['icon' => '➡️', 'name' => 'Slide R'],
                                'zoom-in' => ['icon' => '🔍', 'name' => 'Zoom In'],
                                'zoom-out' => ['icon' => '🔎', 'name' => 'Zoom Out'],
                            ];
                            $selectedTransition = $assembly['defaultTransition'] ?? 'fade';
                        @endphp
                        @foreach($transitions as $transId => $trans)
                            <button type="button"
                                    class="vw-transition-btn {{ $selectedTransition === $transId ? 'selected' : '' }}"
                                    wire:click="$set('assembly.defaultTransition', '{{ $transId }}')">
                                <div class="vw-transition-btn-icon">{{ $trans['icon'] }}</div>
                                {{ $trans['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Captions --}}
                <div class="vw-setting-card">
                    <div class="vw-setting-header">
                        <div class="vw-setting-title">
                            <span class="vw-setting-title-icon">📝</span>
                            <span>{{ __('Captions') }}</span>
                        </div>
                        <label class="vw-toggle">
                            <input type="checkbox" wire:model.live="assembly.captions.enabled" {{ ($assembly['captions']['enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="vw-toggle-slider"></span>
                        </label>
                    </div>

                    @if($assembly['captions']['enabled'] ?? true)
                        <div class="vw-subsetting-grid">
                            <div class="vw-subsetting">
                                <span class="vw-subsetting-label">{{ __('Style') }}</span>
                                <select class="vw-select" wire:model.live="assembly.captions.style">
                                    @foreach($captionStyles ?? [] as $styleId => $style)
                                        <option value="{{ $styleId }}">{{ $style['name'] }}</option>
                                    @endforeach
                                    @if(empty($captionStyles))
                                        <option value="karaoke">🎤 Karaoke</option>
                                        <option value="standard">📝 Standard</option>
                                        <option value="typewriter">⌨️ Typewriter</option>
                                        <option value="bold">💪 Bold</option>
                                    @endif
                                </select>
                            </div>
                            <div class="vw-subsetting">
                                <span class="vw-subsetting-label">{{ __('Position') }}</span>
                                <select class="vw-select" wire:model.live="assembly.captions.position">
                                    <option value="top">{{ __('Top') }}</option>
                                    <option value="middle">{{ __('Middle') }}</option>
                                    <option value="bottom">{{ __('Bottom') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="vw-range-section">
                            <div class="vw-range-header">
                                <span class="vw-range-label">{{ __('Size') }}</span>
                                <span class="vw-range-value">{{ number_format($assembly['captions']['size'] ?? 1, 1) }}x</span>
                            </div>
                            <input type="range"
                                   wire:model.live="assembly.captions.size"
                                   min="0.5" max="2" step="0.1"
                                   class="vw-range-slider">
                        </div>

                        <div class="vw-caption-preview">
                            <span class="vw-caption-preview-text {{ $assembly['captions']['style'] ?? 'karaoke' }}">
                                {{ __('Sample caption text preview') }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Background Music --}}
                <div class="vw-setting-card">
                    <div class="vw-setting-header">
                        <div class="vw-setting-title">
                            <span class="vw-setting-title-icon">🎵</span>
                            <span>{{ __('Background Music') }}</span>
                        </div>
                        <label class="vw-toggle">
                            <input type="checkbox" wire:model.live="assembly.music.enabled" {{ ($assembly['music']['enabled'] ?? false) ? 'checked' : '' }}>
                            <span class="vw-toggle-slider"></span>
                        </label>
                    </div>

                    @if($assembly['music']['enabled'] ?? false)
                        <div class="vw-range-section">
                            <div class="vw-range-header">
                                <span class="vw-range-label">{{ __('Volume') }}</span>
                                <span class="vw-range-value">{{ $assembly['music']['volume'] ?? 30 }}%</span>
                            </div>
                            <input type="range"
                                   wire:model.live="assembly.music.volume"
                                   min="0" max="100" step="5"
                                   class="vw-range-slider">
                        </div>

                        <div style="margin-top: 0.75rem;">
                            <button type="button"
                                    onclick="alert('Music browser coming soon!')"
                                    style="width: 100%; padding: 0.5rem; border-radius: 0.5rem; border: 1px dashed rgba(255,255,255,0.2); background: transparent; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 0.75rem;">
                                🎶 {{ __('Browse Music Library') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ready Banner --}}
        <div class="vw-ready-banner">
            <span class="vw-ready-icon">✅</span>
            <div class="vw-ready-text">
                <div class="vw-ready-title">{{ __('Ready to Export') }}</div>
                <div class="vw-ready-subtitle">{{ __('Your video is configured and ready for final export') }}</div>
            </div>
            <button type="button" class="vw-continue-btn" wire:click="nextStep">
                {{ __('Continue') }} →
            </button>
        </div>
    </div>
</div>
