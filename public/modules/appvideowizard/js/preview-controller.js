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
        totalDuration: 0,
        loadProgress: 0,
        currentSceneIndex: 0,
        totalScenes: 0,

        // Canvas dimensions based on aspect ratio
        canvasWidth: 1280,
        canvasHeight: 720,

        // Settings from Livewire
        aspectRatio: initialData.aspectRatio || '16:9',
        captionsEnabled: initialData.captionsEnabled !== false,
        captionStyle: initialData.captionStyle || 'karaoke',
        captionPosition: initialData.captionPosition || 'bottom',
        captionSize: initialData.captionSize || 1.0,
        musicEnabled: initialData.musicEnabled || false,
        musicVolume: initialData.musicVolume || 30,
        musicUrl: initialData.musicUrl || null,

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
         */
        async loadPreview() {
            if (this.isLoading || this.isReady) return;

            this.isLoading = true;
            this.loadProgress = 0;

            try {
                const canvas = this.$refs.previewCanvas;
                if (!canvas) {
                    throw new Error('Canvas element not found');
                }

                // Check if VideoPreviewEngine is available
                if (typeof VideoPreviewEngine === 'undefined') {
                    throw new Error('VideoPreviewEngine not loaded');
                }

                // Initialize engine
                this.engine = new VideoPreviewEngine(canvas, {
                    width: this.canvasWidth,
                    height: this.canvasHeight,
                    onTimeUpdate: (time) => {
                        this.currentTime = time;
                        // Dispatch event for timeline sync
                        this.$dispatch('preview-time-update', { time });
                    },
                    onSceneChange: (index) => {
                        this.currentSceneIndex = index;
                        this.$dispatch('preview-scene-change', { index });
                    },
                    onEnded: () => {
                        this.isPlaying = false;
                        this.$dispatch('preview-ended');
                    },
                    onLoadProgress: (progress) => {
                        this.loadProgress = Math.round(progress * 100);
                    },
                    onReady: () => {
                        this.isLoading = false;
                        this.isReady = true;
                        this.totalDuration = this.engine.totalDuration;
                        this.totalScenes = this.engine.scenes.length;
                        this.$dispatch('preview-ready', {
                            duration: this.totalDuration,
                            scenes: this.totalScenes
                        });
                    }
                });

                // Get scenes from Livewire
                const scenes = await this.$wire.getPreviewScenes();

                if (!scenes || scenes.length === 0) {
                    throw new Error('No scenes available for preview');
                }

                // Load scenes into engine
                await this.engine.loadScenes(scenes);

                // Apply caption settings
                this.applyCaptionSettings();

                // Apply music if enabled
                if (this.musicEnabled && this.musicUrl) {
                    await this.applyMusicSettings();
                }

            } catch (error) {
                console.error('Failed to load preview:', error);
                this.isLoading = false;
                this.$dispatch('preview-error', { message: error.message });
            }
        },

        /**
         * Refresh scenes (reload from Livewire)
         */
        async refreshScenes() {
            if (!this.engine || !this.isReady) return;

            try {
                const scenes = await this.$wire.getPreviewScenes();
                await this.engine.loadScenes(scenes);
                this.totalScenes = this.engine.scenes.length;
                this.totalDuration = this.engine.totalDuration;
            } catch (error) {
                console.error('Failed to refresh scenes:', error);
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
