{{-- Step 6: Assembly Studio - Full-Screen Professional Editor --}}
{{-- Video Preview Engine script loaded via video-wizard.blade.php @assets --}}

@php
    $assemblyStats = $this->getAssemblyStats();
    $isMultiShot = $assemblyStats['mode'] === 'multi-shot';
    $canExport = !$isMultiShot || $assemblyStats['isReady'];
    $previewData = $this->getPreviewInitData();
@endphp

<div
    class="vw-assembly-fullscreen"
    x-data="{
        // Preview Engine State
        engine: null,
        isReady: false,
        isLoading: false,
        isPlaying: false,
        currentTime: 0,
        totalDuration: {{ $previewData['totalDuration'] ?? 0 }},
        loadProgress: 0,
        currentSceneIndex: 0,
        totalScenes: {{ count($previewData['scenes'] ?? []) }},

        // Canvas dimensions
        canvasWidth: 1280,
        canvasHeight: 720,

        // Settings from server
        aspectRatio: '{{ $previewData['aspectRatio'] ?? '16:9' }}',
        captionsEnabled: {{ ($previewData['captionsEnabled'] ?? true) ? 'true' : 'false' }},
        captionStyle: '{{ $previewData['captionStyle'] ?? 'karaoke' }}',
        captionPosition: '{{ $previewData['captionPosition'] ?? 'bottom' }}',
        captionSize: {{ $previewData['captionSize'] ?? 1.0 }},
        musicEnabled: {{ ($previewData['musicEnabled'] ?? false) ? 'true' : 'false' }},
        musicVolume: {{ $previewData['musicVolume'] ?? 30 }},
        musicUrl: @js($previewData['musicUrl'] ?? null),

        // Scenes data from server
        scenes: @js($previewData['scenes'] ?? []),

        // UI State
        activeTab: 'scenes',
        showExportModal: false,
        keyboardShortcuts: true,

        // Player UI State (Phase 1-4)
        isFullscreen: false,
        controlsVisible: true,
        controlsTimeout: null,
        cursorTimeout: null,
        cursorHidden: false,
        volume: 100,
        isMuted: false,
        lastMouseMove: 0,

        // Phase 4: Flash icon animation state
        showFlashIcon: false,
        flashIconType: 'play', // 'play' or 'pause'
        flashTimeout: null,

        // Phase 5: Professional features
        playbackSpeed: 1,
        isPiPSupported: false,
        isPiPActive: false,

        // Initialize
        init() {
            this.setAspectRatio(this.aspectRatio);
            this.setupLivewireListeners();
            this.setupFullscreenListeners();
            this.loadVolumePreference();
            this.checkPiPSupport();
            this.loadPlaybackSpeedPreference();
        },

        // Check Picture-in-Picture support
        checkPiPSupport() {
            this.isPiPSupported = 'pictureInPictureEnabled' in document && document.pictureInPictureEnabled;
        },

        // Load playback speed preference
        loadPlaybackSpeedPreference() {
            try {
                const savedSpeed = localStorage.getItem('vw-playback-speed');
                if (savedSpeed !== null) {
                    this.playbackSpeed = parseFloat(savedSpeed);
                }
            } catch (e) {
                console.warn('[PreviewController] Could not load playback speed preference:', e);
            }
        },

        // Save playback speed preference
        savePlaybackSpeedPreference() {
            try {
                localStorage.setItem('vw-playback-speed', this.playbackSpeed.toString());
            } catch (e) {
                console.warn('[PreviewController] Could not save playback speed preference:', e);
            }
        },

        // Set playback speed
        setPlaybackSpeed(speed) {
            this.playbackSpeed = speed;
            if (this.engine && this.engine.setPlaybackRate) {
                this.engine.setPlaybackRate(speed);
            }
            this.savePlaybackSpeedPreference();
        },

        // Get resolution label based on canvas dimensions
        getResolutionLabel() {
            const height = this.canvasHeight;
            if (height >= 2160) return '4K';
            if (height >= 1440) return '1440p';
            if (height >= 1080) return '1080p';
            if (height >= 720) return '720p';
            if (height >= 480) return '480p';
            return '360p';
        },

        // Toggle Picture-in-Picture mode
        async togglePictureInPicture() {
            if (!this.isPiPSupported) return;

            try {
                // For canvas-based preview, we need to create a video element
                // that captures the canvas content
                if (!this.pipVideo) {
                    this.pipVideo = document.createElement('video');
                    this.pipVideo.muted = true;
                    this.pipVideo.playsInline = true;

                    // Listen for PiP events
                    this.pipVideo.addEventListener('enterpictureinpicture', () => {
                        this.isPiPActive = true;
                    });
                    this.pipVideo.addEventListener('leavepictureinpicture', () => {
                        this.isPiPActive = false;
                        this.stopCanvasCapture();
                    });
                }

                if (document.pictureInPictureElement) {
                    await document.exitPictureInPicture();
                } else {
                    // Start capturing canvas to video
                    await this.startCanvasCapture();
                    await this.pipVideo.requestPictureInPicture();
                }
            } catch (error) {
                console.error('[PreviewController] PiP error:', error);
                this.isPiPActive = false;
            }
        },

        // Start capturing canvas to video for PiP
        async startCanvasCapture() {
            const canvas = this.$refs.previewCanvas;
            if (!canvas) return;

            try {
                const stream = canvas.captureStream(30); // 30 FPS
                this.pipVideo.srcObject = stream;
                await this.pipVideo.play();
            } catch (error) {
                console.error('[PreviewController] Canvas capture error:', error);
            }
        },

        // Stop canvas capture
        stopCanvasCapture() {
            if (this.pipVideo && this.pipVideo.srcObject) {
                const tracks = this.pipVideo.srcObject.getTracks();
                tracks.forEach(track => track.stop());
                this.pipVideo.srcObject = null;
            }
        },

        // Load volume preference from localStorage
        loadVolumePreference() {
            try {
                const savedVolume = localStorage.getItem('vw-player-volume');
                const savedMuted = localStorage.getItem('vw-player-muted');
                if (savedVolume !== null) {
                    this.volume = parseInt(savedVolume, 10);
                }
                if (savedMuted !== null) {
                    this.isMuted = savedMuted === 'true';
                }
            } catch (e) {
                console.warn('[PreviewController] Could not load volume preference:', e);
            }
        },

        // Save volume preference to localStorage
        saveVolumePreference() {
            try {
                localStorage.setItem('vw-player-volume', this.volume.toString());
                localStorage.setItem('vw-player-muted', this.isMuted.toString());
            } catch (e) {
                console.warn('[PreviewController] Could not save volume preference:', e);
            }
        },

        // Fullscreen support
        setupFullscreenListeners() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
            document.addEventListener('webkitfullscreenchange', () => {
                this.isFullscreen = !!document.webkitFullscreenElement;
            });
        },

        toggleFullscreen() {
            const container = this.$refs.previewContainer;
            if (!container) return;

            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        },

        // Auto-hide controls with debounce and cursor hiding
        showControls() {
            const now = Date.now();
            // Debounce: ignore calls within 50ms
            if (now - this.lastMouseMove < 50) return;
            this.lastMouseMove = now;

            this.controlsVisible = true;
            this.cursorHidden = false;

            // Clear existing timeouts
            if (this.controlsTimeout) {
                clearTimeout(this.controlsTimeout);
            }
            if (this.cursorTimeout) {
                clearTimeout(this.cursorTimeout);
            }

            // Auto-hide after 3 seconds if playing
            if (this.isPlaying) {
                this.controlsTimeout = setTimeout(() => {
                    this.controlsVisible = false;
                    // Hide cursor in fullscreen mode
                    if (this.isFullscreen) {
                        this.cursorTimeout = setTimeout(() => {
                            this.cursorHidden = true;
                        }, 500);
                    }
                }, 3000);
            }
        },

        hideControlsDelayed() {
            if (this.isPlaying) {
                if (this.controlsTimeout) {
                    clearTimeout(this.controlsTimeout);
                }
                this.controlsTimeout = setTimeout(() => {
                    this.controlsVisible = false;
                    if (this.isFullscreen) {
                        this.cursorHidden = true;
                    }
                }, 1000);
            }
        },

        // Flash play/pause animation (YouTube-style)
        flashPlayPause() {
            // Clear any existing flash timeout
            if (this.flashTimeout) {
                clearTimeout(this.flashTimeout);
            }

            // Determine what icon to flash based on NEXT state
            this.flashIconType = this.isPlaying ? 'pause' : 'play';

            // Show flash icon
            this.showFlashIcon = true;

            // Toggle playback
            this.togglePlay();

            // Hide flash icon after animation
            this.flashTimeout = setTimeout(() => {
                this.showFlashIcon = false;
            }, 400);
        },

        // Volume controls with preference persistence
        toggleMute() {
            this.isMuted = !this.isMuted;
            if (this.engine && this.engine.setVolume) {
                this.engine.setVolume(this.isMuted ? 0 : this.volume / 100, this.musicVolume / 100);
            }
            this.saveVolumePreference();
        },

        setVolume(value) {
            this.volume = parseInt(value);
            this.isMuted = this.volume === 0;
            if (this.engine && this.engine.setVolume) {
                this.engine.setVolume(this.volume / 100, this.musicVolume / 100);
            }
            this.saveVolumePreference();
        },

        // Seek by clicking on progress bar
        seekToPosition(event) {
            if (!this.isReady || !this.totalDuration) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const percentage = x / rect.width;
            const time = percentage * this.totalDuration;
            this.seek(Math.max(0, Math.min(time, this.totalDuration)));
        },

        setAspectRatio(ratio) {
            const ratios = {
                '16:9': { width: 1280, height: 720 },
                '9:16': { width: 720, height: 1280 },
                '1:1': { width: 1080, height: 1080 },
                '4:5': { width: 1080, height: 1350 },
                '4:3': { width: 1280, height: 960 }
            };
            const dims = ratios[ratio] || ratios['16:9'];
            this.canvasWidth = dims.width;
            this.canvasHeight = dims.height;
            this.aspectRatio = ratio;
            if (this.engine) {
                this.engine.resize(dims.width, dims.height);
            }
            if (this.$refs && this.$refs.previewCanvas) {
                const wrapper = this.$refs.previewCanvas.parentElement;
                if (wrapper) {
                    wrapper.style.aspectRatio = ratio.replace(':', '/');
                }
            }
        },

        setupLivewireListeners() {
            if (typeof Livewire === 'undefined') return;
            Livewire.on('caption-setting-updated', (data) => this.updateCaptionSetting(data.key, data.value));
            Livewire.on('music-setting-updated', (data) => this.updateMusicSetting(data.key, data.value));
            Livewire.on('preview-scenes-updated', (data) => { if (data && data.scenes) this.updateScenes(data.scenes); });
            window.addEventListener('seek-preview', (e) => { if (e.detail && typeof e.detail.time !== 'undefined') this.seek(e.detail.time); });
            window.addEventListener('toggle-preview-playback', () => this.togglePlay());
        },

        async loadPreview() {
            if (this.isLoading || this.isReady) return;
            this.isLoading = true;
            this.loadProgress = 0;

            try {
                const canvas = this.$refs.previewCanvas;
                if (!canvas) throw new Error('Canvas element not found');
                if (typeof VideoPreviewEngine === 'undefined') throw new Error('VideoPreviewEngine not loaded. Please refresh the page.');
                const scenes = this.scenes;
                if (!scenes || scenes.length === 0) throw new Error('No scenes available');

                console.log('[PreviewController] Loading preview with', scenes.length, 'scenes');

                this.engine = new VideoPreviewEngine(canvas, {
                    width: this.canvasWidth,
                    height: this.canvasHeight,
                    onTimeUpdate: (time) => {
                        this.currentTime = time;
                        window.dispatchEvent(new CustomEvent('preview-time-update', { detail: { time } }));
                    },
                    onSceneChange: (index) => {
                        this.currentSceneIndex = index;
                        window.dispatchEvent(new CustomEvent('preview-scene-change', { detail: { index } }));
                    },
                    onEnded: () => {
                        this.isPlaying = false;
                        window.dispatchEvent(new CustomEvent('preview-ended'));
                    },
                    onLoadProgress: (progress) => {
                        this.loadProgress = Math.round(progress * 100);
                    },
                    onReady: () => {
                        this.isLoading = false;
                        this.isReady = true;
                        this.totalDuration = this.engine.totalDuration;
                        this.totalScenes = this.engine.scenes.length;
                        window.dispatchEvent(new CustomEvent('preview-ready', {
                            detail: { duration: this.totalDuration, scenes: this.totalScenes }
                        }));
                        console.log('[PreviewController] Preview ready. Duration:', this.totalDuration);
                    }
                });

                await this.engine.loadScenes(scenes);
                this.applyCaptionSettings();
                if (this.musicEnabled && this.musicUrl) {
                    await this.applyMusicSettings();
                }
            } catch (error) {
                console.error('[PreviewController] Failed to load preview:', error);
                this.isLoading = false;
                window.dispatchEvent(new CustomEvent('preview-error', { detail: { message: error.message } }));
                alert('Preview Error: ' + error.message);
            }
        },

        updateScenes(newScenes) {
            this.scenes = newScenes || [];
            this.totalScenes = this.scenes.length;
            if (this.engine && this.isReady) this.refreshScenes();
        },

        async refreshScenes() {
            if (!this.engine || !this.isReady) return;
            if (this.scenes && this.scenes.length > 0) {
                await this.engine.loadScenes(this.scenes);
                this.totalScenes = this.engine.scenes.length;
                this.totalDuration = this.engine.totalDuration;
            }
        },

        applyCaptionSettings() {
            if (!this.engine) return;
            this.engine.captionsEnabled = this.captionsEnabled;
            this.engine.captionStyle = this.captionStyle;
            this.engine.captionPosition = this.captionPosition;
            this.engine.captionSize = this.captionSize;
            if (this.isReady && !this.isPlaying) this.engine._renderFrame();
        },

        updateCaptionSetting(key, value) {
            if (!this.engine) return;
            switch (key) {
                case 'enabled': this.captionsEnabled = value; this.engine.setCaptionsEnabled(value); break;
                case 'style': this.captionStyle = value; this.engine.setCaptionStyle(value); break;
                case 'position': this.captionPosition = value; this.engine.setCaptionPosition(value); break;
                case 'size': this.captionSize = value; this.engine.setCaptionSize(value); break;
            }
        },

        async applyMusicSettings() {
            if (!this.engine) return;
            if (this.musicEnabled && this.musicUrl) {
                try { await this.engine.setBackgroundMusic(this.musicUrl, this.musicVolume / 100); }
                catch (e) { console.warn('Failed to load music:', e); }
            } else if (this.engine.stopBackgroundMusic) {
                this.engine.stopBackgroundMusic();
            }
        },

        updateMusicSetting(key, value) {
            if (!this.engine) return;
            switch (key) {
                case 'enabled': this.musicEnabled = value; if (!value && this.engine.stopBackgroundMusic) this.engine.stopBackgroundMusic(); else if (this.musicUrl) this.applyMusicSettings(); break;
                case 'volume': this.musicVolume = value; if (this.engine.musicElement) this.engine.musicElement.volume = value / 100; break;
                case 'url': this.musicUrl = value; if (this.musicEnabled && value) this.applyMusicSettings(); break;
            }
        },

        togglePlay() {
            if (!this.engine || !this.isReady) return;
            if (this.isPlaying) this.pause();
            else this.play();
        },

        play() {
            if (!this.engine || !this.isReady) return;
            this.engine.play();
            this.isPlaying = true;
            // Auto-hide controls after 3 seconds
            this.showControls();
        },

        pause() {
            if (!this.engine) return;
            this.engine.pause();
            this.isPlaying = false;
            // Always show controls when paused
            this.controlsVisible = true;
            if (this.controlsTimeout) {
                clearTimeout(this.controlsTimeout);
            }
        },

        stop() {
            if (!this.engine) return;
            this.engine.stop();
            this.isPlaying = false;
            this.currentTime = 0;
        },

        seek(time) {
            if (!this.engine || !this.isReady) return;
            const t = Math.max(0, Math.min(time, this.totalDuration));
            this.engine.seek(t);
            this.currentTime = t;
        },

        seekStart() { this.seek(0); },
        seekEnd() { this.seek(this.totalDuration); },

        jumpToScene(idx) {
            if (!this.engine || !this.isReady) return;
            this.engine.jumpToScene(idx);
            this.currentSceneIndex = idx;
        },

        seekToScene(idx) {
            if (!this.engine || !this.isReady || idx < 0 || idx >= this.engine.scenes.length) return;
            let t = 0;
            for (let i = 0; i < idx; i++) t += this.engine.scenes[i].duration || 5;
            this.seek(t);
            this.currentSceneIndex = idx;
        },

        formatTime(s) {
            if (!s || isNaN(s)) return '0:00';
            const m = Math.floor(s / 60);
            const sec = Math.floor(s % 60);
            return m + ':' + sec.toString().padStart(2, '0');
        },

        formatTimecode(s) {
            if (!s || isNaN(s)) return '00:00:00';
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = Math.floor(s % 60);
            if (h > 0) return h + ':' + m.toString().padStart(2, '0') + ':' + sec.toString().padStart(2, '0');
            return m + ':' + sec.toString().padStart(2, '0');
        },

        getCurrentScene() {
            if (!this.engine || !this.engine.scenes[this.currentSceneIndex]) return null;
            return this.engine.scenes[this.currentSceneIndex];
        },

        setVolume(voice, music) {
            if (this.engine) this.engine.setVolume(voice || 1.0, music || 0.3);
        },

        destroy() {
            if (this.engine) { this.engine.destroy(); this.engine = null; }
            this.isReady = false;
            this.isPlaying = false;
            this.currentTime = 0;
            this.totalDuration = 0;
        },

        handleKeyboard(e) {
            if (!this.keyboardShortcuts) return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            switch(e.key.toLowerCase()) {
                case ' ':
                    e.preventDefault();
                    this.flashPlayPause();
                    break;
                case 'f':
                    e.preventDefault();
                    this.toggleFullscreen();
                    break;
                case 'escape':
                    if (this.isFullscreen) {
                        // Fullscreen exit is handled by browser
                    } else if (this.showExportModal) {
                        this.showExportModal = false;
                    }
                    break;
                case 'm':
                    this.toggleMute();
                    break;
                case '1': this.activeTab = 'scenes'; break;
                case '2': this.activeTab = 'text'; break;
                case '3': this.activeTab = 'audio'; break;
                case '4': this.activeTab = 'transitions'; break;
                case 'arrowleft':
                    if (this.engine) this.seek(Math.max(0, this.currentTime - 5));
                    break;
                case 'arrowright':
                    if (this.engine) this.seek(Math.min(this.totalDuration, this.currentTime + 5));
                    break;
                case 'arrowup':
                    e.preventDefault();
                    this.setVolume(Math.min(100, this.volume + 10));
                    break;
                case 'arrowdown':
                    e.preventDefault();
                    this.setVolume(Math.max(0, this.volume - 10));
                    break;
                case ',':
                case '<':
                    // Decrease playback speed
                    e.preventDefault();
                    const speeds = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];
                    const currentIdx = speeds.indexOf(this.playbackSpeed);
                    if (currentIdx > 0) {
                        this.setPlaybackSpeed(speeds[currentIdx - 1]);
                    }
                    break;
                case '.':
                case '>':
                    // Increase playback speed
                    e.preventDefault();
                    const speedsUp = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];
                    const currentIdxUp = speedsUp.indexOf(this.playbackSpeed);
                    if (currentIdxUp < speedsUp.length - 1) {
                        this.setPlaybackSpeed(speedsUp[currentIdxUp + 1]);
                    }
                    break;
                case 'p':
                    // Toggle Picture-in-Picture
                    if (this.isPiPSupported) {
                        e.preventDefault();
                        this.togglePictureInPicture();
                    }
                    break;
            }
        }
    }"
    x-init="
        init();
        window.addEventListener('keydown', (e) => handleKeyboard(e));
    "
    @open-export-modal.window="showExportModal = true"
    @open-music-browser.window="activeTab = 'audio'"
>
    {{-- Full-Screen Layout Container --}}
    <div class="vw-studio-layout">
        {{-- Header --}}
        @include('appvideowizard::livewire.steps.partials._assembly-header')

        {{-- Main Content Area --}}
        <div class="vw-studio-main">
            {{-- Left Sidebar --}}
            @include('appvideowizard::livewire.steps.partials._assembly-sidebar')

            {{-- Tabbed Panel --}}
            @include('appvideowizard::livewire.steps.partials._assembly-tabs')

            {{-- Center Preview Area --}}
            <div class="vw-preview-area">
                {{-- Preview Canvas --}}
                @include('appvideowizard::livewire.steps.partials._preview-canvas')

                {{-- Multi-Shot Status Bar (if applicable) --}}
                @if($isMultiShot || ($multiShotMode['enabled'] ?? false))
                    <div class="vw-multishot-bar">
                        <div class="vw-multishot-info">
                            <span class="vw-multishot-badge">🎬 {{ __('Multi-Shot') }}</span>
                            <span class="vw-multishot-stats">
                                {{ $assemblyStats['sceneCount'] }} {{ __('scenes') }} •
                                {{ $assemblyStats['videoCount'] }} {{ __('clips') }} •
                                {{ $assemblyStats['formattedDuration'] }}
                            </span>
                        </div>
                        <div class="vw-multishot-progress">
                            <div class="vw-progress-bar">
                                <div class="vw-progress-fill" style="width: {{ $assemblyStats['progress'] }}%;"></div>
                            </div>
                            <span class="vw-progress-text">{{ $assemblyStats['progress'] }}%</span>
                        </div>
                        @if($assemblyStats['pendingShots'] > 0)
                            <span class="vw-pending-badge">{{ $assemblyStats['pendingShots'] }} {{ __('pending') }}</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right Properties Panel (compact) --}}
            <div class="vw-properties-panel">
                <div class="vw-properties-header">
                    <span>⚙️</span> {{ __('Quick Settings') }}
                </div>

                {{-- Aspect Ratio Display --}}
                <div class="vw-prop-item">
                    <span class="vw-prop-label">{{ __('Format') }}</span>
                    <span class="vw-prop-value">{{ $aspectRatio }}</span>
                </div>

                {{-- Transition Display --}}
                <div class="vw-prop-item">
                    <span class="vw-prop-label">{{ __('Transition') }}</span>
                    <span class="vw-prop-value">{{ ucfirst($assembly['defaultTransition'] ?? 'fade') }}</span>
                </div>

                {{-- Captions Status --}}
                <div class="vw-prop-item">
                    <span class="vw-prop-label">{{ __('Captions') }}</span>
                    <span class="vw-prop-value {{ ($assembly['captions']['enabled'] ?? true) ? 'active' : '' }}">
                        {{ ($assembly['captions']['enabled'] ?? true) ? ucfirst($assembly['captions']['style'] ?? 'karaoke') : 'Off' }}
                    </span>
                </div>

                {{-- Music Status --}}
                <div class="vw-prop-item">
                    <span class="vw-prop-label">{{ __('Music') }}</span>
                    <span class="vw-prop-value {{ ($assembly['music']['enabled'] ?? false) ? 'active' : '' }}">
                        {{ ($assembly['music']['enabled'] ?? false) ? ($assembly['music']['volume'] ?? 30) . '%' : 'Off' }}
                    </span>
                </div>

                <div class="vw-prop-divider"></div>

                {{-- Keyboard Shortcuts Toggle --}}
                <div class="vw-prop-item toggle">
                    <span class="vw-prop-label">{{ __('Shortcuts') }}</span>
                    <label class="vw-mini-toggle">
                        <input type="checkbox" x-model="keyboardShortcuts">
                        <span class="vw-mini-slider"></span>
                    </label>
                </div>

                {{-- Shortcuts Reference --}}
                <div class="vw-shortcuts-ref" x-show="keyboardShortcuts" x-collapse>
                    <div class="vw-shortcut"><kbd>Space</kbd> {{ __('Play/Pause') }}</div>
                    <div class="vw-shortcut"><kbd>←</kbd><kbd>→</kbd> {{ __('Seek 5s') }}</div>
                    <div class="vw-shortcut"><kbd>↑</kbd><kbd>↓</kbd> {{ __('Volume') }}</div>
                    <div class="vw-shortcut"><kbd>&lt;</kbd><kbd>&gt;</kbd> {{ __('Speed') }}</div>
                    <div class="vw-shortcut"><kbd>M</kbd> {{ __('Mute') }}</div>
                    <div class="vw-shortcut"><kbd>F</kbd> {{ __('Fullscreen') }}</div>
                    <div class="vw-shortcut"><kbd>P</kbd> {{ __('PiP') }}</div>
                    <div class="vw-shortcut"><kbd>Esc</kbd> {{ __('Exit/Close') }}</div>
                </div>
            </div>
        </div>

        {{-- Professional Timeline - Phase 5 --}}
        @include('appvideowizard::livewire.steps.partials._timeline')
    </div>

    {{-- Export Modal - Phase 6 --}}
    @include('appvideowizard::livewire.steps.partials._export-modal')
</div>

<style>
    /* ========================================
       ASSEMBLY STUDIO - Full Screen Layout
       ======================================== */

    /* Full-Screen Layout */
    .vw-assembly-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw !important;
        height: 100vh !important;
        background: #0a0a12;
        z-index: 999999;
        overflow: hidden;
    }

    /* CRITICAL: Force hide ALL app sidebars and navigation when Assembly Studio is active */
    .sidebar,
    .main-sidebar,
    div.sidebar,
    aside.sidebar,
    .hide-scroll.sidebar {
        z-index: 1 !important;
    }

    /* Ensure full-screen coverage - hide main app sidebar */
    body.vw-assembly-active {
        overflow: hidden !important;
    }

    body.vw-assembly-active .sidebar,
    body.vw-assembly-active .main-sidebar,
    body.vw-assembly-active div.sidebar,
    body.vw-assembly-active .sidebar.hide-scroll,
    body.vw-assembly-active [class*="sidebar"]:not(.vw-assembly-sidebar),
    body.vw-assembly-active aside:not(.vw-assembly-fullscreen aside),
    body.vw-assembly-active nav:not(.vw-assembly-fullscreen nav) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 0 !important;
        max-width: 0 !important;
        overflow: hidden !important;
    }

    body.vw-assembly-active .main-content,
    body.vw-assembly-active .page-wrapper,
    body.vw-assembly-active [class*="content"]:not(.vw-assembly-fullscreen *) {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
    }

    .vw-studio-layout {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
    }

    .vw-studio-main {
        flex: 1;
        display: flex;
        overflow: hidden;
    }

    /* Preview Area */
    .vw-preview-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #000;
        position: relative;
    }

    /* Multi-Shot Status Bar */
    .vw-multishot-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.1));
        border-top: 1px solid rgba(139, 92, 246, 0.2);
    }

    .vw-multishot-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-multishot-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        background: rgba(139, 92, 246, 0.3);
        border-radius: 0.25rem;
        color: #a78bfa;
    }

    .vw-multishot-stats {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-multishot-progress {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }

    .vw-progress-bar {
        flex: 1;
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        overflow: hidden;
    }

    .vw-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 3px;
        transition: width 0.3s;
    }

    .vw-progress-text {
        font-size: 0.7rem;
        color: #a78bfa;
        font-weight: 600;
        min-width: 35px;
    }

    .vw-pending-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
        background: rgba(245, 158, 11, 0.2);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 0.25rem;
        color: #f59e0b;
    }

    /* Properties Panel */
    .vw-properties-panel {
        width: 180px;
        min-width: 180px;
        background: rgba(15, 15, 25, 0.98);
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-properties-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 0.25rem;
    }

    .vw-prop-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.4rem 0;
    }

    .vw-prop-item.toggle {
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.4rem;
    }

    .vw-prop-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-prop-value {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
    }

    .vw-prop-value.active {
        color: #10b981;
    }

    .vw-prop-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.08);
        margin: 0.5rem 0;
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
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    }

    .vw-mini-toggle input:checked + .vw-mini-slider:before {
        transform: translateX(14px);
    }

    /* Shortcuts Reference */
    .vw-shortcuts-ref {
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.4rem;
        margin-top: 0.25rem;
    }

    .vw-shortcut {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
        padding: 0.2rem 0;
    }

    .vw-shortcut kbd {
        padding: 0.1rem 0.3rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 0.2rem;
        font-family: inherit;
        font-size: 0.6rem;
    }

    /* Timeline Bar */
    .vw-timeline-bar {
        height: 50px;
        min-height: 50px;
        background: rgba(15, 15, 25, 0.98);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        padding: 0 1rem;
        gap: 1rem;
    }

    .vw-timeline-scenes {
        flex: 1;
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.25rem 0;
    }

    .vw-timeline-scene {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.35rem 0.75rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 50px;
    }

    .vw-timeline-scene:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-timeline-scene.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.15));
        border-color: #8b5cf6;
    }

    .vw-scene-thumb {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-scene-duration {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-timeline-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-timeline-total {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        font-family: 'SF Mono', Monaco, monospace;
        padding: 0.35rem 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.4rem;
    }

    /* Modal Styles */
    .vw-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(4px);
    }

    .vw-export-modal {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.98), rgba(20, 20, 35, 0.98));
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 1rem;
        width: 90%;
        max-width: 480px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    .vw-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-modal-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: white;
        margin: 0;
    }

    .vw-modal-close {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 1.25rem;
        cursor: pointer;
        transition: background 0.2s;
    }

    .vw-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .vw-modal-body {
        padding: 1.25rem;
    }

    .vw-modal-text {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
        margin: 0 0 1rem 0;
        line-height: 1.5;
    }

    .vw-export-summary {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
    }

    .vw-summary-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: white;
    }

    .vw-summary-icon {
        font-size: 1rem;
    }

    .vw-export-warning {
        display: flex;
        gap: 0.75rem;
        padding: 1rem;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 0.5rem;
    }

    .vw-warning-icon {
        font-size: 1.5rem;
    }

    .vw-warning-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #f59e0b;
        margin: 0 0 0.25rem 0;
    }

    .vw-warning-text {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        margin: 0;
    }

    .vw-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-modal-btn {
        padding: 0.6rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .vw-modal-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-modal-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .vw-modal-btn.primary {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        color: white;
    }

    .vw-modal-btn.primary:hover {
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-modal-btn.warning {
        background: rgba(245, 158, 11, 0.2);
        border: 1px solid rgba(245, 158, 11, 0.4);
        color: #f59e0b;
    }

    .vw-modal-btn.warning:hover {
        background: rgba(245, 158, 11, 0.3);
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .vw-properties-panel {
            width: 160px;
            min-width: 160px;
        }
    }

    @media (max-width: 992px) {
        .vw-properties-panel {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .vw-studio-main {
            flex-direction: column;
        }

        .vw-timeline-bar {
            flex-wrap: wrap;
            height: auto;
            padding: 0.5rem;
        }

        .vw-timeline-scenes {
            order: 2;
            width: 100%;
        }
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    (function() {
        // Add body class when Assembly Studio is active
        document.body.classList.add('vw-assembly-active');

        // Function to aggressively hide all sidebars
        function hideAllSidebars() {
            const sidebars = document.querySelectorAll('.sidebar, .main-sidebar, [class*="sidebar"]:not(.vw-assembly-sidebar), aside:not(.vw-assembly-fullscreen aside)');
            sidebars.forEach(function(el) {
                if (!el.closest('.vw-assembly-fullscreen')) {
                    el.style.cssText = 'display: none !important; width: 0 !important; visibility: hidden !important;';
                }
            });
        }

        // Run immediately and after a short delay (for lazy-loaded elements)
        hideAllSidebars();
        setTimeout(hideAllSidebars, 100);
        setTimeout(hideAllSidebars, 500);

        // Cleanup when component is removed
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('element.removed', (el, component) => {
                if (el.classList && el.classList.contains('vw-assembly-fullscreen')) {
                    document.body.classList.remove('vw-assembly-active');
                    document.querySelectorAll('.sidebar, .main-sidebar, [class*="sidebar"], aside').forEach(function(el) {
                        el.style.cssText = '';
                    });
                }
            });
        }

        // Also cleanup on page unload (for SPA navigation)
        window.addEventListener('beforeunload', function() {
            document.body.classList.remove('vw-assembly-active');
        });
    })();
</script>
