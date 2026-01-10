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

        {{-- Click-to-Play Overlay with YouTube-style flash animation --}}
        <div
            x-show="isReady"
            @click="flashPlayPause()"
            class="vw-play-overlay"
            :class="{ 'is-visible': !isPlaying && !showFlashIcon }"
            x-cloak
        >
            {{-- Persistent center play button (when paused) --}}
            <div class="vw-center-play-btn" :class="{ 'is-paused': !isPlaying }">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
            </div>
        </div>

        {{-- Flash Icon (YouTube-style brief animation on toggle) --}}
        <div
            x-show="showFlashIcon"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-50"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-150"
            class="vw-flash-icon-overlay"
            x-cloak
        >
            <div class="vw-flash-icon">
                {{-- Show play icon when we just started playing --}}
                <svg x-show="flashIconType === 'play'" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                {{-- Show pause icon when we just paused --}}
                <svg x-show="flashIconType === 'pause'" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16"/>
                    <rect x="14" y="4" width="4" height="16"/>
                </svg>
            </div>
        </div>

        {{-- Full-screen hint --}}
        <div class="vw-fullscreen-hint" x-show="!isFullscreen && isReady" x-cloak>
            {{ __('Double-click for fullscreen') }}
        </div>

        {{-- Exit fullscreen hint (shown briefly in fullscreen) --}}
        <div class="vw-exit-fullscreen-hint"
             x-show="isFullscreen && controlsVisible"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <span class="vw-exit-key">ESC</span>
            <span>{{ __('to exit fullscreen') }}</span>
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
                <div class="vw-volume-control" :class="{ 'is-muted': isMuted }">
                    <button @click="toggleMute()" class="vw-ctrl-btn" type="button" :title="isMuted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}' + ' (M)'">
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
                        <input type="range"
                               class="vw-volume-slider"
                               min="0"
                               max="100"
                               x-model="volume"
                               @input="setVolume($event.target.value)"
                               :style="'--volume-percent: ' + volume + '%'"
                               :title="volume + '%'">
                    </div>
                </div>

                {{-- Time Display --}}
                <div class="vw-time-display">
                    <span x-text="formatTime(currentTime)">0:00</span>
                    <span class="vw-time-sep">/</span>
                    <span x-text="formatTime(totalDuration)">0:00</span>
                </div>

                {{-- Playback Speed Control --}}
                <div class="vw-speed-control" x-data="{ showSpeedMenu: false }" @click.away="showSpeedMenu = false">
                    <button @click="showSpeedMenu = !showSpeedMenu"
                            class="vw-speed-btn"
                            type="button"
                            title="{{ __('Playback Speed') }}">
                        <span x-text="playbackSpeed + 'x'">1x</span>
                    </button>
                    <div class="vw-speed-menu" x-show="showSpeedMenu" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">
                        <template x-for="speed in [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]" :key="speed">
                            <button class="vw-speed-option"
                                    :class="{ 'is-active': playbackSpeed === speed }"
                                    @click="setPlaybackSpeed(speed); showSpeedMenu = false"
                                    type="button">
                                <span x-text="speed + 'x'"></span>
                                <svg x-show="playbackSpeed === speed" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Right Controls --}}
            <div class="vw-controls-right">
                {{-- Resolution/Quality Indicator --}}
                <div class="vw-quality-badge" x-show="isReady" title="{{ __('Video Quality') }}">
                    <span class="vw-resolution" x-text="getResolutionLabel()">720p</span>
                    <span class="vw-aspect" x-text="aspectRatio">16:9</span>
                </div>

                {{-- Scene Indicator --}}
                <div x-show="totalScenes > 1" class="vw-scene-badge">
                    <span>{{ __('Scene') }}</span>
                    <span x-text="currentSceneIndex + 1">1</span>/<span x-text="totalScenes">1</span>
                </div>

                {{-- Picture-in-Picture Toggle --}}
                <button @click="togglePictureInPicture()"
                        x-show="isPiPSupported"
                        class="vw-ctrl-btn"
                        :class="{ 'is-active': isPiPActive }"
                        type="button"
                        :title="isPiPActive ? '{{ __('Exit Picture-in-Picture') }}' : '{{ __('Picture-in-Picture') }}'">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 7h-8v6h8V7zm2-4H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/>
                    </svg>
                </button>

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

    /* ===== Flash Icon Overlay (YouTube-style) ===== */
    .vw-flash-icon-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 15;
    }

    .vw-flash-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }

    .vw-flash-icon svg {
        width: 36px;
        height: 36px;
        color: white;
    }

    .vw-flash-icon svg:first-child {
        margin-left: 4px;
    }

    /* Fullscreen: Larger flash icon */
    .is-fullscreen .vw-flash-icon {
        width: 100px;
        height: 100px;
    }

    .is-fullscreen .vw-flash-icon svg {
        width: 44px;
        height: 44px;
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
        padding: 1rem 2rem;
        border-radius: 1.5rem;
        background: rgba(10, 10, 20, 0.85);
    }

    /* Fullscreen: Larger buttons */
    .is-fullscreen .vw-ctrl-btn {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
    }

    .is-fullscreen .vw-ctrl-btn svg {
        width: 24px;
        height: 24px;
    }

    .is-fullscreen .vw-play-pause-btn {
        width: 56px;
        height: 56px;
    }

    .is-fullscreen .vw-play-pause-btn svg {
        width: 28px;
        height: 28px;
    }

    /* Fullscreen: Larger center play button */
    .is-fullscreen .vw-center-play-btn {
        width: 100px;
        height: 100px;
    }

    .is-fullscreen .vw-center-play-btn svg {
        width: 40px;
        height: 40px;
    }

    /* Fullscreen: Larger time display */
    .is-fullscreen .vw-time-display {
        font-size: 0.95rem;
        padding: 0.5rem 0.75rem;
    }

    /* Fullscreen: Larger scene badge */
    .is-fullscreen .vw-scene-badge {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    /* Fullscreen: Thicker progress bar */
    .is-fullscreen .vw-progress-track {
        height: 6px;
    }

    .is-fullscreen .vw-progress-bar:hover .vw-progress-track {
        height: 10px;
    }

    .is-fullscreen .vw-progress-thumb {
        width: 18px;
        height: 18px;
    }

    /* Fullscreen: Larger volume slider */
    .is-fullscreen .vw-volume-control:hover .vw-volume-slider-wrapper {
        width: 100px;
    }

    /* Exit fullscreen hint */
    .vw-exit-fullscreen-hint {
        position: absolute;
        top: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
        pointer-events: none;
        z-index: 100;
    }

    .vw-exit-key {
        padding: 0.2rem 0.5rem;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.25rem;
        font-family: 'SF Mono', Monaco, 'Consolas', monospace;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    /* Fullscreen: Aspect ratio preservation */
    .is-fullscreen .vw-canvas-frame {
        width: auto;
        height: calc(100vh - 140px);
        max-width: 100%;
        margin: 0 auto;
    }

    /* Fullscreen: Center the canvas frame */
    .vw-preview-container.is-fullscreen {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    /* Fullscreen: Position controls at bottom */
    .is-fullscreen .vw-controls-wrapper {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 0 3rem 2rem;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
    }

    /* Fullscreen: Time tooltip larger */
    .is-fullscreen .vw-time-tooltip {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }

    .vw-controls-left,
    .vw-controls-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .is-fullscreen .vw-controls-left,
    .is-fullscreen .vw-controls-right {
        gap: 0.75rem;
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
        position: relative;
    }

    .vw-volume-slider-wrapper {
        width: 0;
        overflow: hidden;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
    }

    .vw-volume-control:hover .vw-volume-slider-wrapper,
    .vw-volume-control:focus-within .vw-volume-slider-wrapper {
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
        transition: height 0.15s ease;
    }

    .vw-volume-slider:hover {
        height: 6px;
    }

    /* WebKit browsers - Volume fill gradient */
    .vw-volume-slider::-webkit-slider-runnable-track {
        height: 100%;
        border-radius: 2px;
        background: linear-gradient(to right,
            #8b5cf6 0%,
            #8b5cf6 var(--volume-percent, 100%),
            rgba(255, 255, 255, 0.2) var(--volume-percent, 100%),
            rgba(255, 255, 255, 0.2) 100%
        );
    }

    .vw-volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        margin-top: -5px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .vw-volume-slider::-webkit-slider-thumb:hover {
        transform: scale(1.15);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }

    /* Firefox */
    .vw-volume-slider::-moz-range-track {
        height: 4px;
        border-radius: 2px;
        background: rgba(255, 255, 255, 0.2);
    }

    .vw-volume-slider::-moz-range-progress {
        height: 4px;
        border-radius: 2px;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
    }

    .vw-volume-slider::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        transition: transform 0.15s ease;
    }

    .vw-volume-slider::-moz-range-thumb:hover {
        transform: scale(1.15);
    }

    /* Muted state styling */
    .vw-volume-control.is-muted .vw-volume-slider::-webkit-slider-runnable-track {
        background: rgba(255, 255, 255, 0.15);
    }

    .vw-volume-control.is-muted .vw-volume-slider::-moz-range-progress {
        background: rgba(255, 255, 255, 0.3);
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

    /* ===== Playback Speed Control (Phase 5) ===== */
    .vw-speed-control {
        position: relative;
    }

    .vw-speed-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.35rem 0.6rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 42px;
    }

    .vw-speed-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        color: white;
    }

    .vw-speed-menu {
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 0.5rem;
        background: rgba(15, 15, 25, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.75rem;
        padding: 0.5rem;
        min-width: 100px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        z-index: 50;
    }

    .vw-speed-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.5rem 0.75rem;
        background: transparent;
        border: none;
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-speed-option:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-speed-option.is-active {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
    }

    .vw-speed-option svg {
        width: 16px;
        height: 16px;
        color: #10b981;
    }

    /* Fullscreen: Larger speed control */
    .is-fullscreen .vw-speed-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        min-width: 50px;
    }

    .is-fullscreen .vw-speed-menu {
        min-width: 120px;
    }

    .is-fullscreen .vw-speed-option {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    /* ===== Quality/Resolution Badge (Phase 5) ===== */
    .vw-quality-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        background: rgba(6, 182, 212, 0.15);
        border: 1px solid rgba(6, 182, 212, 0.25);
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .vw-resolution {
        color: #22d3ee;
    }

    .vw-aspect {
        color: rgba(255, 255, 255, 0.5);
        padding-left: 0.5rem;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Fullscreen: Larger quality badge */
    .is-fullscreen .vw-quality-badge {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }

    /* ===== Picture-in-Picture Button (Phase 5) ===== */
    .vw-ctrl-btn.is-active {
        background: rgba(139, 92, 246, 0.3);
        border: 1px solid rgba(139, 92, 246, 0.5);
        color: #a78bfa;
    }

    .vw-ctrl-btn.is-active:hover {
        background: rgba(139, 92, 246, 0.4);
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
