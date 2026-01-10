{{--
    Video Preview Canvas Component - Modern Professional Design
    Integrates with VideoPreviewEngine for real-time canvas-based preview

    Features:
    - Glassmorphism controls
    - Auto-hiding controls on hover
    - Full-screen support
    - SVG icons
    - Smooth micro-interactions
--}}

<div class="vw-preview-container" x-ref="previewContainer"
     @dblclick="toggleFullscreen()"
     @mousemove="showControls()"
     @mouseleave="hideControlsDelayed()"
     @touchstart="showControls()"
     @touchmove="showControls()"
     :class="{ 'is-fullscreen': isFullscreen, 'cursor-hidden': cursorHidden }"
     x-data="{
         hoverTime: 0,
         hoverPosition: 0,
         showTimeTooltip: false,
         updateHoverTime(event) {
             const bar = event.currentTarget;
             const rect = bar.getBoundingClientRect();
             const x = event.clientX - rect.left;
             this.hoverPosition = Math.max(0, Math.min(x, rect.width));
             const percentage = x / rect.width;
             this.hoverTime = percentage * totalDuration;
             this.showTimeTooltip = true;
         },
         hideTimeTooltip() {
             this.showTimeTooltip = false;
         },
         formatHoverTime(seconds) {
             if (!seconds || isNaN(seconds)) return '0:00';
             const m = Math.floor(seconds / 60);
             const s = Math.floor(seconds % 60);
             return m + ':' + s.toString().padStart(2, '0');
         }
     }">

    {{-- Canvas Frame --}}
    <div class="vw-canvas-frame" :class="{ 'is-loading': isLoading, 'is-ready': isReady, 'is-playing': isPlaying }">
        <canvas
            x-ref="previewCanvas"
            class="vw-preview-canvas"
        ></canvas>

        {{-- Load Preview Overlay (shown when not loaded) --}}
        <div x-show="!isReady && !isLoading" x-cloak class="vw-initial-overlay">
            <button @click="loadPreview()" class="vw-load-btn" type="button">
                <svg class="vw-load-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3" fill="currentColor" stroke="none"/>
                </svg>
                <span>{{ __('Load Preview') }}</span>
            </button>
            <p class="vw-load-hint">{{ __('Click to load and preview your video') }}</p>
        </div>

        {{-- Loading Overlay --}}
        <div x-show="isLoading" x-cloak class="vw-loading-overlay">
            <div class="vw-loader">
                <div class="vw-loader-ring"></div>
                <div class="vw-loader-ring"></div>
                <div class="vw-loader-ring"></div>
            </div>
            <div class="vw-loading-progress-bar">
                <div class="vw-loading-progress-fill" :style="'width: ' + loadProgress + '%'"></div>
            </div>
            <span class="vw-loading-text">{{ __('Loading preview') }} <span x-text="loadProgress">0</span>%</span>
        </div>

        {{-- Click-to-Play Overlay (appears briefly on pause) --}}
        <div
            x-show="isReady"
            @click="togglePlay()"
            class="vw-play-overlay"
            :class="{ 'is-visible': !isPlaying || showPlayIcon }"
            x-cloak
        >
            <div class="vw-center-play-btn" :class="{ 'is-paused': !isPlaying }">
                <svg x-show="!isPlaying" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                <svg x-show="isPlaying" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16"/>
                    <rect x="14" y="4" width="4" height="16"/>
                </svg>
            </div>
        </div>

        {{-- Full-screen hint --}}
        <div class="vw-fullscreen-hint" x-show="!isFullscreen && isReady" x-cloak>
            {{ __('Double-click for fullscreen') }}
        </div>
    </div>

    {{-- Modern Floating Controls --}}
    <div class="vw-controls-wrapper"
         :class="{ 'is-visible': controlsVisible || !isPlaying || isLoading, 'is-fullscreen': isFullscreen }">

        {{-- Enhanced Progress Bar with Time Tooltip --}}
        <div class="vw-progress-container">
            <div class="vw-progress-bar"
                 @click="seekToPosition($event)"
                 @mousemove="updateHoverTime($event)"
                 @mouseleave="hideTimeTooltip()"
                 @touchmove.prevent="updateHoverTime($event.touches[0])">

                {{-- Time Tooltip --}}
                <div class="vw-time-tooltip"
                     x-show="showTimeTooltip && totalDuration > 0"
                     x-cloak
                     :style="'left: ' + hoverPosition + 'px'"
                     x-text="formatHoverTime(hoverTime)">
                </div>

                {{-- Hover Preview Line --}}
                <div class="vw-hover-line"
                     x-show="showTimeTooltip"
                     :style="'left: ' + hoverPosition + 'px'">
                </div>

                {{-- Progress Track --}}
                <div class="vw-progress-track">
                    {{-- Buffered/Loaded segment (visual placeholder) --}}
                    <div class="vw-progress-buffered" style="width: 100%;"></div>
                    {{-- Played segment --}}
                    <div class="vw-progress-played" :style="'width: ' + (totalDuration > 0 ? (currentTime / totalDuration * 100) : 0) + '%'"></div>
                </div>

                {{-- Progress Thumb --}}
                <div class="vw-progress-thumb" :style="'left: ' + (totalDuration > 0 ? (currentTime / totalDuration * 100) : 0) + '%'"></div>
            </div>
        </div>

        {{-- Controls Bar --}}
        <div class="vw-controls-bar">
            {{-- Left Controls --}}
            <div class="vw-controls-left">
                {{-- Play/Pause --}}
                <button @click="togglePlay()" :disabled="!isReady" class="vw-ctrl-btn vw-play-pause-btn" type="button" :title="isPlaying ? '{{ __('Pause') }}' : '{{ __('Play') }}'">
                    <svg x-show="!isPlaying" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <svg x-show="isPlaying" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="6" y="4" width="4" height="16"/>
                        <rect x="14" y="4" width="4" height="16"/>
                    </svg>
                </button>

                {{-- Skip Back --}}
                <button @click="seek(Math.max(0, currentTime - 5))" :disabled="!isReady" class="vw-ctrl-btn" type="button" title="{{ __('Back 5s') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                        <text x="12" y="14" font-size="6" fill="currentColor" text-anchor="middle" stroke="none">5</text>
                    </svg>
                </button>

                {{-- Skip Forward --}}
                <button @click="seek(Math.min(totalDuration, currentTime + 5))" :disabled="!isReady" class="vw-ctrl-btn" type="button" title="{{ __('Forward 5s') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8z"/>
                        <text x="12" y="14" font-size="6" fill="currentColor" text-anchor="middle" stroke="none">5</text>
                    </svg>
                </button>

                {{-- Volume --}}
                <div class="vw-volume-control">
                    <button @click="toggleMute()" class="vw-ctrl-btn" type="button" title="{{ __('Volume') }}">
                        <svg x-show="!isMuted && volume > 50" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                        <svg x-show="!isMuted && volume > 0 && volume <= 50" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/>
                        </svg>
                        <svg x-show="isMuted || volume === 0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                        </svg>
                    </button>
                    <div class="vw-volume-slider-wrapper">
                        <input type="range" class="vw-volume-slider" min="0" max="100" x-model="volume" @input="setVolume($event.target.value)">
                    </div>
                </div>

                {{-- Time Display --}}
                <div class="vw-time-display">
                    <span x-text="formatTime(currentTime)">0:00</span>
                    <span class="vw-time-sep">/</span>
                    <span x-text="formatTime(totalDuration)">0:00</span>
                </div>
            </div>

            {{-- Right Controls --}}
            <div class="vw-controls-right">
                {{-- Scene Indicator --}}
                <div x-show="totalScenes > 1" class="vw-scene-badge">
                    <span>{{ __('Scene') }}</span>
                    <span x-text="currentSceneIndex + 1">1</span>/<span x-text="totalScenes">1</span>
                </div>

                {{-- Fullscreen Toggle --}}
                <button @click="toggleFullscreen()" class="vw-ctrl-btn" type="button" title="{{ __('Fullscreen') }} (F)">
                    <svg x-show="!isFullscreen" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                    </svg>
                    <svg x-show="isFullscreen" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* =====================================================
       MODERN VIDEO PREVIEW PLAYER - Phase 1 Design
       Glassmorphism, enhanced visuals, micro-interactions
       ===================================================== */

    .vw-preview-container {
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        padding: 1rem;
        justify-content: center;
        align-items: center;
        gap: 0;
    }

    .vw-preview-container.is-fullscreen {
        padding: 0;
        background: #000;
    }

    .vw-preview-container.cursor-hidden {
        cursor: none;
    }

    .vw-preview-container.cursor-hidden * {
        cursor: none !important;
    }

    /* Canvas Frame - Modern bezel with glow effects */
    .vw-canvas-frame {
        position: relative;
        width: 100%;
        max-width: 100%;
        aspect-ratio: 16/9;
        background: linear-gradient(145deg, #0a0a12 0%, #12121f 100%);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow:
            0 4px 6px rgba(0, 0, 0, 0.3),
            0 10px 40px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: box-shadow 0.4s ease, border-color 0.4s ease;
        max-height: calc(100% - 80px);
    }

    .is-fullscreen .vw-canvas-frame {
        max-height: calc(100% - 100px);
        border-radius: 0;
        border: none;
        max-width: none;
    }

    /* Active/Playing glow effect */
    .vw-canvas-frame.is-ready {
        border-color: rgba(139, 92, 246, 0.2);
    }

    .vw-canvas-frame.is-playing {
        box-shadow:
            0 4px 6px rgba(0, 0, 0, 0.3),
            0 10px 40px rgba(0, 0, 0, 0.4),
            0 0 60px rgba(139, 92, 246, 0.1),
            0 0 100px rgba(6, 182, 212, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        border-color: rgba(139, 92, 246, 0.3);
    }

    /* Loading shimmer effect */
    .vw-canvas-frame.is-loading {
        background: linear-gradient(
            90deg,
            #0a0a12 0%,
            #14141f 25%,
            #1a1a2e 50%,
            #14141f 75%,
            #0a0a12 100%
        );
        background-size: 200% 100%;
        animation: shimmer 1.5s ease-in-out infinite;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Canvas element */
    .vw-preview-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    /* ===== Initial Load Overlay ===== */
    .vw-initial-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: radial-gradient(ellipse at center, rgba(15, 15, 30, 0.9) 0%, rgba(5, 5, 15, 0.95) 100%);
        gap: 1rem;
    }

    .vw-load-btn {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        padding: 1rem 2.5rem;
        border-radius: 3rem;
        border: none;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        color: white;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow:
            0 4px 15px rgba(139, 92, 246, 0.4),
            0 0 40px rgba(139, 92, 246, 0.2);
    }

    .vw-load-btn:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow:
            0 8px 25px rgba(139, 92, 246, 0.5),
            0 0 60px rgba(139, 92, 246, 0.3);
    }

    .vw-load-btn:active {
        transform: translateY(-1px) scale(0.98);
    }

    .vw-load-icon {
        width: 24px;
        height: 24px;
    }

    .vw-load-hint {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.4);
        margin: 0;
    }

    /* ===== Loading Overlay ===== */
    .vw-loading-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(10, 10, 20, 0.9);
        backdrop-filter: blur(8px);
        gap: 1.25rem;
    }

    /* Triple ring loader */
    .vw-loader {
        position: relative;
        width: 60px;
        height: 60px;
    }

    .vw-loader-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 3px solid transparent;
        animation: loaderSpin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    }

    .vw-loader-ring:nth-child(1) {
        border-top-color: #8b5cf6;
        animation-delay: -0.45s;
    }

    .vw-loader-ring:nth-child(2) {
        inset: 6px;
        border-right-color: #06b6d4;
        animation-delay: -0.3s;
        animation-direction: reverse;
    }

    .vw-loader-ring:nth-child(3) {
        inset: 12px;
        border-bottom-color: #10b981;
        animation-delay: -0.15s;
    }

    @keyframes loaderSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .vw-loading-progress-bar {
        width: 200px;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
    }

    .vw-loading-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4, #10b981);
        background-size: 200% 100%;
        border-radius: 2px;
        transition: width 0.3s ease;
        animation: progressGlow 2s ease infinite;
    }

    @keyframes progressGlow {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .vw-loading-text {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 500;
    }

    /* ===== Play Overlay ===== */
    .vw-play-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .vw-play-overlay.is-visible {
        opacity: 1;
    }

    .vw-play-overlay:hover {
        opacity: 1;
    }

    .vw-center-play-btn {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(139, 92, 246, 0.9);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow:
            0 8px 32px rgba(0, 0, 0, 0.4),
            0 0 40px rgba(139, 92, 246, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
    }

    .vw-center-play-btn.is-paused {
        opacity: 1;
    }

    .vw-play-overlay:hover .vw-center-play-btn {
        transform: scale(1.1);
        box-shadow:
            0 12px 40px rgba(0, 0, 0, 0.5),
            0 0 60px rgba(139, 92, 246, 0.4);
    }

    .vw-center-play-btn svg {
        width: 32px;
        height: 32px;
        color: white;
        margin-left: 4px;
    }

    .vw-center-play-btn svg:last-child {
        margin-left: 0;
    }

    /* Fullscreen hint */
    .vw-fullscreen-hint {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.3);
        background: rgba(0, 0, 0, 0.4);
        padding: 0.35rem 0.75rem;
        border-radius: 0.5rem;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }

    .vw-canvas-frame:hover .vw-fullscreen-hint {
        opacity: 1;
    }

    /* ===== Controls Wrapper with Glassmorphism ===== */
    .vw-controls-wrapper {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 0 1rem 1rem;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }

    .vw-controls-wrapper.is-visible {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .is-fullscreen .vw-controls-wrapper {
        padding: 0 2rem 2rem;
    }

    /* Progress Bar */
    .vw-progress-container {
        padding: 0.5rem 0;
        margin-bottom: 0.5rem;
    }

    .vw-progress-bar {
        position: relative;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 8px 0;
    }

    .vw-progress-track {
        position: relative;
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 3px;
        overflow: hidden;
        transition: height 0.15s ease, box-shadow 0.15s ease;
    }

    .vw-progress-bar:hover .vw-progress-track {
        height: 8px;
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.3);
    }

    .vw-progress-played {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 2px;
        transition: width 0.1s linear;
    }

    .vw-progress-thumb {
        position: absolute;
        top: 50%;
        width: 14px;
        height: 14px;
        background: white;
        border-radius: 50%;
        transform: translate(-50%, -50%) scale(0);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        transition: transform 0.2s ease;
    }

    .vw-progress-bar:hover .vw-progress-thumb {
        transform: translate(-50%, -50%) scale(1);
    }

    /* Time Tooltip */
    .vw-time-tooltip {
        position: absolute;
        bottom: 100%;
        transform: translateX(-50%);
        margin-bottom: 8px;
        padding: 0.35rem 0.6rem;
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(8px);
        border-radius: 0.375rem;
        font-family: 'SF Mono', Monaco, 'Consolas', monospace;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
        white-space: nowrap;
        pointer-events: none;
        z-index: 10;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-time-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: rgba(0, 0, 0, 0.9);
    }

    /* Hover Preview Line */
    .vw-hover-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(255, 255, 255, 0.5);
        transform: translateX(-50%);
        pointer-events: none;
        z-index: 5;
    }

    /* Buffered Segment */
    .vw-progress-buffered {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    /* Glassmorphism Controls Bar */
    .vw-controls-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 1rem;
        background: rgba(15, 15, 25, 0.75);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow:
            0 4px 30px rgba(0, 0, 0, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .is-fullscreen .vw-controls-bar {
        padding: 0.875rem 1.5rem;
        border-radius: 1.25rem;
    }

    .vw-controls-left,
    .vw-controls-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Control Buttons */
    .vw-ctrl-btn {
        width: 40px;
        height: 40px;
        border-radius: 0.625rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.85);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .vw-ctrl-btn svg {
        width: 20px;
        height: 20px;
    }

    .vw-ctrl-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: scale(1.05);
    }

    .vw-ctrl-btn:active:not(:disabled) {
        transform: scale(0.95);
    }

    .vw-ctrl-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    /* Play/Pause button highlight */
    .vw-play-pause-btn {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.2));
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .vw-play-pause-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.5), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.5);
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
    }

    .vw-play-pause-btn svg {
        width: 22px;
        height: 22px;
    }

    /* Volume Control */
    .vw-volume-control {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-volume-slider-wrapper {
        width: 0;
        overflow: hidden;
        transition: width 0.3s ease;
    }

    .vw-volume-control:hover .vw-volume-slider-wrapper {
        width: 80px;
    }

    .vw-volume-slider {
        width: 80px;
        height: 4px;
        -webkit-appearance: none;
        appearance: none;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        cursor: pointer;
    }

    .vw-volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
    }

    .vw-volume-slider::-moz-range-thumb {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        border: none;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
    }

    /* Time Display */
    .vw-time-display {
        font-family: 'SF Mono', Monaco, 'Consolas', monospace;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
        padding: 0 0.5rem;
        white-space: nowrap;
    }

    .vw-time-sep {
        color: rgba(255, 255, 255, 0.3);
        margin: 0 0.25rem;
    }

    /* Scene Badge */
    .vw-scene-badge {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.25);
        border-radius: 2rem;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-scene-badge span:first-child {
        color: rgba(255, 255, 255, 0.5);
    }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        .vw-controls-bar {
            padding: 0.5rem 0.75rem;
        }

        .vw-ctrl-btn {
            width: 36px;
            height: 36px;
        }

        .vw-play-pause-btn {
            width: 40px;
            height: 40px;
        }

        .vw-time-display {
            font-size: 0.75rem;
        }

        .vw-volume-control:hover .vw-volume-slider-wrapper {
            width: 60px;
        }

        .vw-center-play-btn {
            width: 64px;
            height: 64px;
        }

        .vw-center-play-btn svg {
            width: 26px;
            height: 26px;
        }
    }

    [x-cloak] {
        display: none !important;
    }
</style>
