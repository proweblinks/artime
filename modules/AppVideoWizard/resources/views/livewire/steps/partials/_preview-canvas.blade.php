{{--
    Video Preview Canvas Component
    Integrates with VideoPreviewEngine for real-time canvas-based preview

    Usage: @include('appvideowizard::livewire.steps.partials._preview-canvas')

    Expects parent to have Alpine x-data with previewController
--}}

<div class="vw-preview-canvas-container">
    {{-- Canvas Wrapper --}}
    <div class="vw-canvas-wrapper" :class="{ 'loading': isLoading, 'ready': isReady }">
        <canvas
            x-ref="previewCanvas"
            class="vw-preview-canvas"
        ></canvas>

        {{-- Load Preview Overlay (shown when not loaded) --}}
        <div x-show="!isReady && !isLoading" x-cloak class="vw-preview-overlay">
            <button @click="loadPreview()" class="vw-load-preview-btn" type="button">
                <span class="vw-play-icon">&#9658;</span>
                <span>{{ __('Load Preview') }}</span>
            </button>
        </div>

        {{-- Loading Overlay --}}
        <div x-show="isLoading" x-cloak class="vw-preview-overlay loading">
            <div class="vw-loading-spinner"></div>
            <div class="vw-loading-progress">
                <div class="vw-loading-bar" :style="'width: ' + loadProgress + '%'"></div>
            </div>
            <span class="vw-loading-text">{{ __('Loading') }} <span x-text="loadProgress"></span>%</span>
        </div>

        {{-- Play/Pause Overlay (shown when ready but paused) --}}
        <div
            x-show="isReady && !isPlaying"
            x-cloak
            @click="togglePlay()"
            class="vw-preview-overlay play-hover"
        >
            <div class="vw-play-button-large">
                <span>&#9658;</span>
            </div>
        </div>
    </div>

    {{-- Transport Controls --}}
    <div class="vw-transport-controls" :class="{ 'disabled': !isReady }">
        {{-- Playback Buttons --}}
        <div class="vw-transport-buttons">
            <button @click="seekStart()" :disabled="!isReady" class="vw-transport-btn" type="button" title="{{ __('Go to start') }}">
                <span>&#9198;</span>
            </button>
            <button @click="togglePlay()" :disabled="!isReady" class="vw-transport-btn play" type="button">
                <span x-text="isPlaying ? '&#9208;' : '&#9658;'"></span>
            </button>
            <button @click="seekEnd()" :disabled="!isReady" class="vw-transport-btn" type="button" title="{{ __('Go to end') }}">
                <span>&#9197;</span>
            </button>
        </div>

        {{-- Timeline Slider --}}
        <div class="vw-timeline-wrapper">
            <input
                type="range"
                class="vw-timeline-input"
                min="0"
                :max="totalDuration"
                step="0.1"
                x-model="currentTime"
                @input="seek(parseFloat($event.target.value))"
                :disabled="!isReady"
            >
        </div>

        {{-- Time Display --}}
        <div class="vw-time-display">
            <span x-text="formatTime(currentTime)">0:00</span>
            <span class="vw-time-separator">/</span>
            <span x-text="formatTime(totalDuration)">0:00</span>
        </div>
    </div>

    {{-- Scene Indicator --}}
    <div x-show="isReady && totalScenes > 1" x-cloak class="vw-scene-indicator">
        <span class="vw-scene-label">{{ __('Scene') }}</span>
        <span class="vw-scene-current" x-text="currentSceneIndex + 1">1</span>
        <span class="vw-scene-separator">/</span>
        <span class="vw-scene-total" x-text="totalScenes">1</span>
    </div>
</div>

<style>
    .vw-preview-canvas-container {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .vw-canvas-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 16/9;
        background: #000;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .vw-canvas-wrapper.loading {
        background: linear-gradient(45deg, #0a0a0f 25%, #14141f 50%, #0a0a0f 75%);
        background-size: 400% 400%;
        animation: shimmer 2s ease infinite;
    }

    @keyframes shimmer {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .vw-preview-canvas {
        width: 100%;
        height: 100%;
        display: block;
    }

    .vw-preview-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        transition: opacity 0.3s ease;
    }

    .vw-preview-overlay.play-hover {
        background: rgba(0,0,0,0.2);
        opacity: 0;
        cursor: pointer;
    }

    .vw-preview-overlay.play-hover:hover {
        opacity: 1;
    }

    .vw-load-preview-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        border-radius: 0.75rem;
        border: none;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    }

    .vw-load-preview-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }

    .vw-play-icon {
        font-size: 1.25rem;
    }

    .vw-loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .vw-loading-progress {
        width: 200px;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .vw-loading-bar {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .vw-loading-text {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.7);
    }

    .vw-play-button-large {
        width: 80px;
        height: 80px;
        background: rgba(139, 92, 246, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        transition: transform 0.2s ease;
    }

    .vw-play-button-large span {
        margin-left: 5px;
    }

    .vw-preview-overlay.play-hover:hover .vw-play-button-large {
        transform: scale(1.1);
    }

    /* Transport Controls */
    .vw-transport-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(0,0,0,0.4);
        border-radius: 0.75rem;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .vw-transport-controls.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .vw-transport-buttons {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-transport-btn {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        border: 1px solid rgba(255,255,255,0.2);
        background: transparent;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-transport-btn:hover:not(:disabled) {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.3);
    }

    .vw-transport-btn.play {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        font-size: 1.1rem;
    }

    .vw-transport-btn.play:hover:not(:disabled) {
        box-shadow: 0 2px 10px rgba(139, 92, 246, 0.4);
    }

    .vw-transport-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .vw-timeline-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
    }

    .vw-timeline-input {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        appearance: none;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        cursor: pointer;
    }

    .vw-timeline-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }

    .vw-timeline-input::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }

    .vw-timeline-input:disabled {
        cursor: not-allowed;
    }

    .vw-time-display {
        font-family: 'SF Mono', Monaco, monospace;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.8);
        background: rgba(0,0,0,0.3);
        padding: 0.35rem 0.75rem;
        border-radius: 0.4rem;
        white-space: nowrap;
    }

    .vw-time-separator {
        color: rgba(255,255,255,0.4);
        margin: 0 0.25rem;
    }

    /* Scene Indicator */
    .vw-scene-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        width: fit-content;
    }

    .vw-scene-label {
        color: rgba(255,255,255,0.5);
    }

    .vw-scene-current {
        color: #8b5cf6;
        font-weight: 600;
    }

    .vw-scene-separator {
        color: rgba(255,255,255,0.3);
    }

    .vw-scene-total {
        color: rgba(255,255,255,0.6);
    }

    [x-cloak] {
        display: none !important;
    }
</style>
