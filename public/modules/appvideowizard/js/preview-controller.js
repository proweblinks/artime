/**
 * Preview Controller - Alpine.js component for video preview integration
 *
 * Manages the VideoPreviewEngine and provides reactive state for the UI.
 * This controller bridges the gap between Livewire (server state) and
 * the client-side VideoPreviewEngine.
 */

window.previewController = function(initialData = {}) {
    return {
        // Engine instance
        engine: null,

        // State
        isReady: false,
        isLoading: false,
        isPlaying: false,
        currentTime: 0,
        totalDuration: initialData.totalDuration || 0,
        loadProgress: 0,
        currentSceneIndex: 0,
        totalScenes: (initialData.scenes || []).length,

        // Canvas dimensions based on aspect ratio
        canvasWidth: 1280,
        canvasHeight: 720,

        // Settings from Livewire (passed via server-side rendering)
        aspectRatio: initialData.aspectRatio || '16:9',
        captionsEnabled: initialData.captionsEnabled !== false,
        captionStyle: initialData.captionStyle || 'karaoke',
        captionPosition: initialData.captionPosition || 'bottom',
        captionSize: initialData.captionSize || 1.0,
        musicEnabled: initialData.musicEnabled || false,
        musicVolume: initialData.musicVolume || 30,
        musicUrl: initialData.musicUrl || null,

        // Scenes data - passed directly from server to avoid $wire scope issues
        scenes: initialData.scenes || [],

        /**
         * Initialize the controller
         */
        init() {
            // Set canvas dimensions based on aspect ratio
            this.setAspectRatio(this.aspectRatio);

            // Listen for Livewire updates
            this.setupLivewireListeners();

            // Listen for custom events
            this.$el.addEventListener('preview:load', () => this.loadPreview());
            this.$el.addEventListener('preview:play', () => this.play());
            this.$el.addEventListener('preview:pause', () => this.pause());
            this.$el.addEventListener('preview:seek', (e) => this.seek(e.detail.time));
        },

        /**
         * Set canvas dimensions based on aspect ratio
         */
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

            // Update canvas if engine exists
            if (this.engine) {
                this.engine.resize(dims.width, dims.height);
            }

            // Update wrapper aspect ratio via CSS custom property
            if (this.$refs.previewCanvas) {
                const wrapper = this.$refs.previewCanvas.parentElement;
                if (wrapper) {
                    wrapper.style.aspectRatio = ratio.replace(':', '/');
                }
            }
        },

        /**
         * Setup Livewire event listeners
         */
        setupLivewireListeners() {
            // Check if Livewire is available
            if (typeof Livewire === 'undefined') {
                console.warn('[PreviewController] Livewire not available, skipping Livewire listeners');
                return;
            }

            // Listen for caption setting changes
            Livewire.on('caption-setting-updated', (data) => {
                this.updateCaptionSetting(data.key, data.value);
            });

            // Listen for music setting changes
            Livewire.on('music-setting-updated', (data) => {
                this.updateMusicSetting(data.key, data.value);
            });

            // Listen for transition changes
            Livewire.on('transition-updated', () => {
                if (this.engine && this.isReady) {
                    this.refreshScenes();
                }
            });

            // Listen for scenes update (when server sends new scenes data)
            Livewire.on('preview-scenes-updated', (data) => {
                if (data && data.scenes) {
                    this.updateScenes(data.scenes);
                }
            });

            // Listen for caption preset changes (Phase 3)
            Livewire.on('caption-preset-applied', (data) => {
                if (this.engine && this.isReady) {
                    // Refresh scenes to apply new preset styling
                    this.refreshScenes();
                }
            });

            // Listen for voice preset changes (Phase 4)
            Livewire.on('voice-preset-applied', (data) => {
                if (this.engine && this.isReady) {
                    // Voice presets affect audio processing, refresh if needed
                    console.log('[PreviewController] Voice preset applied:', data.preset);
                }
            });

            // Listen for seek events from timeline
            window.addEventListener('seek-preview', (e) => {
                if (e.detail && typeof e.detail.time !== 'undefined') {
                    this.seek(e.detail.time);
                }
            });

            // Listen for toggle playback events
            window.addEventListener('toggle-preview-playback', () => {
                this.togglePlay();
            });
        },

        /**
         * Load preview - initialize VideoPreviewEngine and load scenes
         *
         * Uses scenes data passed directly from server via initialData.
         * This avoids $wire scope issues in nested Alpine components.
         */
        async loadPreview() {
            if (this.isLoading || this.isReady) return;

            this.isLoading = true;
            this.loadProgress = 0;

            try {
                const canvas = this.$refs.previewCanvas;
                if (!canvas) {
                    throw new Error('Canvas element not found. Make sure x-ref="previewCanvas" exists.');
                }

                // Check if VideoPreviewEngine is available
                if (typeof VideoPreviewEngine === 'undefined') {
                    throw new Error('VideoPreviewEngine class not loaded. Check if video-preview-engine.js is included.');
                }

                // Use scenes from initialData (passed directly from server)
                const scenes = this.scenes;

                if (!scenes || scenes.length === 0) {
                    throw new Error('No scenes available for preview. Complete the previous steps first.');
                }

                console.log('[PreviewController] Loading preview with', scenes.length, 'scenes');

                // Initialize engine
                this.engine = new VideoPreviewEngine(canvas, {
                    width: this.canvasWidth,
                    height: this.canvasHeight,
                    onTimeUpdate: (time) => {
                        this.currentTime = time;
                        // Dispatch event for timeline sync
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
                            detail: {
                                duration: this.totalDuration,
                                scenes: this.totalScenes
                            }
                        }));
                        console.log('[PreviewController] Preview ready. Duration:', this.totalDuration, 'Scenes:', this.totalScenes);
                    }
                });

                // Load scenes into engine
                await this.engine.loadScenes(scenes);

                // Apply caption settings
                this.applyCaptionSettings();

                // Apply music if enabled
                if (this.musicEnabled && this.musicUrl) {
                    await this.applyMusicSettings();
                }

            } catch (error) {
                console.error('[PreviewController] Failed to load preview:', error);
                this.isLoading = false;
                window.dispatchEvent(new CustomEvent('preview-error', { detail: { message: error.message } }));
            }
        },

        /**
         * Refresh scenes - reloads scenes into the engine
         *
         * Note: Since scenes are passed via server-side rendering,
         * a full page/component refresh is needed to get updated scenes.
         * This method reloads the current scenes into the engine.
         */
        async refreshScenes() {
            if (!this.engine || !this.isReady) return;

            try {
                // Reload the existing scenes (in case engine state was corrupted)
                if (this.scenes && this.scenes.length > 0) {
                    await this.engine.loadScenes(this.scenes);
                    this.totalScenes = this.engine.scenes.length;
                    this.totalDuration = this.engine.totalDuration;
                    console.log('[PreviewController] Scenes refreshed');
                }
            } catch (error) {
                console.error('[PreviewController] Failed to refresh scenes:', error);
            }
        },

        /**
         * Update scenes data (called when new scenes are available)
         * This can be triggered by Livewire events
         */
        updateScenes(newScenes) {
            this.scenes = newScenes || [];
            this.totalScenes = this.scenes.length;

            // If engine is ready, reload scenes
            if (this.engine && this.isReady) {
                this.refreshScenes();
            }
        },

        /**
         * Apply caption settings to engine
         */
        applyCaptionSettings() {
            if (!this.engine) return;

            this.engine.captionsEnabled = this.captionsEnabled;
            this.engine.captionStyle = this.captionStyle;
            this.engine.captionPosition = this.captionPosition;
            this.engine.captionSize = this.captionSize;

            // Re-render current frame to show updated captions
            if (this.isReady && !this.isPlaying) {
                this.engine._renderFrame();
            }
        },

        /**
         * Update individual caption setting
         */
        updateCaptionSetting(key, value) {
            if (!this.engine) return;

            switch (key) {
                case 'enabled':
                    this.captionsEnabled = value;
                    this.engine.setCaptionsEnabled(value);
                    break;
                case 'style':
                    this.captionStyle = value;
                    this.engine.setCaptionStyle(value);
                    break;
                case 'position':
                    this.captionPosition = value;
                    this.engine.setCaptionPosition(value);
                    break;
                case 'size':
                    this.captionSize = value;
                    this.engine.setCaptionSize(value);
                    break;
                case 'fontFamily':
                    this.engine.captionFontFamily = value;
                    this.engine._renderFrame();
                    break;
                case 'fillColor':
                    this.engine.captionFillColor = value;
                    this.engine._renderFrame();
                    break;
                case 'strokeColor':
                    this.engine.captionStrokeColor = value;
                    this.engine._renderFrame();
                    break;
                case 'strokeWidth':
                    this.engine.captionStrokeWidth = value;
                    this.engine._renderFrame();
                    break;
                case 'highlightColor':
                    this.engine.captionHighlightColor = value;
                    this.engine._renderFrame();
                    break;
                // Phase 3: Additional caption settings
                case 'textTransform':
                    this.engine.captionTextTransform = value;
                    this.engine._renderFrame();
                    break;
                case 'letterSpacing':
                    this.engine.captionLetterSpacing = value;
                    this.engine._renderFrame();
                    break;
                case 'lineHeight':
                    this.engine.captionLineHeight = value;
                    this.engine._renderFrame();
                    break;
                case 'backgroundEnabled':
                    this.engine.captionBackgroundEnabled = value;
                    this.engine._renderFrame();
                    break;
                case 'backgroundColor':
                    this.engine.captionBackgroundColor = value;
                    this.engine._renderFrame();
                    break;
                case 'backgroundOpacity':
                    this.engine.captionBackgroundOpacity = value;
                    this.engine._renderFrame();
                    break;
                case 'shadowEnabled':
                    this.engine.captionShadowEnabled = value;
                    this.engine._renderFrame();
                    break;
                case 'shadowBlur':
                    this.engine.captionShadowBlur = value;
                    this.engine._renderFrame();
                    break;
                case 'shadowOffset':
                    this.engine.captionShadowOffset = value;
                    this.engine._renderFrame();
                    break;
                case 'glowEnabled':
                    this.engine.captionGlowEnabled = value;
                    this.engine._renderFrame();
                    break;
                case 'glowColor':
                    this.engine.captionGlowColor = value;
                    this.engine._renderFrame();
                    break;
                case 'glowIntensity':
                    this.engine.captionGlowIntensity = value;
                    this.engine._renderFrame();
                    break;
                case 'effect':
                    this.engine.captionEffect = value;
                    this.engine._renderFrame();
                    break;
                case 'wordDuration':
                    this.engine.captionWordDuration = value;
                    break;
                case 'mode':
                    this.engine.captionMode = value;
                    this.engine._renderFrame();
                    break;
            }
        },

        /**
         * Apply music settings to engine
         */
        async applyMusicSettings() {
            if (!this.engine) return;

            if (this.musicEnabled && this.musicUrl) {
                try {
                    await this.engine.setBackgroundMusic(this.musicUrl, this.musicVolume / 100);
                } catch (error) {
                    console.warn('Failed to load background music:', error);
                }
            } else {
                this.engine.stopBackgroundMusic();
            }
        },

        /**
         * Update music setting
         */
        updateMusicSetting(key, value) {
            if (!this.engine) return;

            switch (key) {
                case 'enabled':
                    this.musicEnabled = value;
                    if (!value) {
                        this.engine.stopBackgroundMusic();
                    } else if (this.musicUrl) {
                        this.applyMusicSettings();
                    }
                    break;
                case 'volume':
                    this.musicVolume = value;
                    if (this.engine.musicElement) {
                        this.engine.musicElement.volume = value / 100;
                    }
                    break;
                case 'url':
                    this.musicUrl = value;
                    if (this.musicEnabled && value) {
                        this.applyMusicSettings();
                    }
                    break;
            }
        },

        /**
         * Toggle play/pause
         */
        togglePlay() {
            if (!this.engine || !this.isReady) return;

            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        },

        /**
         * Start playback
         */
        play() {
            if (!this.engine || !this.isReady) return;

            this.engine.play();
            this.isPlaying = true;
            this.$dispatch('preview-play');
        },

        /**
         * Pause playback
         */
        pause() {
            if (!this.engine) return;

            this.engine.pause();
            this.isPlaying = false;
            this.$dispatch('preview-pause');
        },

        /**
         * Stop playback and reset to start
         */
        stop() {
            if (!this.engine) return;

            this.engine.stop();
            this.isPlaying = false;
            this.currentTime = 0;
            this.$dispatch('preview-stop');
        },

        /**
         * Seek to specific time
         */
        seek(time) {
            if (!this.engine || !this.isReady) return;

            const clampedTime = Math.max(0, Math.min(time, this.totalDuration));
            this.engine.seek(clampedTime);
            this.currentTime = clampedTime;
            this.$dispatch('preview-seek', { time: clampedTime });
        },

        /**
         * Seek to start
         */
        seekStart() {
            this.seek(0);
        },

        /**
         * Seek to end
         */
        seekEnd() {
            this.seek(this.totalDuration);
        },

        /**
         * Jump to specific scene
         */
        jumpToScene(sceneIndex) {
            if (!this.engine || !this.isReady) return;

            this.engine.jumpToScene(sceneIndex);
            this.currentSceneIndex = sceneIndex;
        },

        /**
         * Seek to start of a specific scene
         */
        seekToScene(sceneIndex) {
            if (!this.engine || !this.isReady) return;
            if (sceneIndex < 0 || sceneIndex >= this.engine.scenes.length) return;

            // Calculate time to seek to
            let time = 0;
            for (let i = 0; i < sceneIndex; i++) {
                time += this.engine.scenes[i].duration || 5;
            }

            this.seek(time);
            this.currentSceneIndex = sceneIndex;
        },

        /**
         * Format time in seconds to MM:SS
         */
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';

            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        /**
         * Format time in seconds to HH:MM:SS (for longer videos)
         */
        formatTimecode(seconds) {
            if (!seconds || isNaN(seconds)) return '00:00:00';

            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);

            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m}:${s.toString().padStart(2, '0')}`;
        },

        /**
         * Get current scene data
         */
        getCurrentScene() {
            if (!this.engine || !this.engine.scenes[this.currentSceneIndex]) {
                return null;
            }
            return this.engine.scenes[this.currentSceneIndex];
        },

        /**
         * Set volume for voice and music
         */
        setVolume(voice = 1.0, music = 0.3) {
            if (!this.engine) return;

            this.engine.setVolume(voice, music);
        },

        /**
         * Destroy the engine and cleanup
         */
        destroy() {
            if (this.engine) {
                this.engine.destroy();
                this.engine = null;
            }

            this.isReady = false;
            this.isPlaying = false;
            this.currentTime = 0;
            this.totalDuration = 0;
        }
    };
};

// Make available globally for non-module usage
if (typeof window !== 'undefined') {
    window.previewController = window.previewController;
}
