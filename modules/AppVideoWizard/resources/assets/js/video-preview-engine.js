/**
 * VideoPreviewEngine - Client-side video preview compositor
 * Adapted for ArTime Laravel application
 *
 * Renders scenes to a canvas with synchronized audio, transitions,
 * Ken Burns effects, and caption overlays.
 */
class VideoPreviewEngine {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');

        // Dimensions
        this.width = options.width || 1280;
        this.height = options.height || 720;
        canvas.width = this.width;
        canvas.height = this.height;

        // State
        this.scenes = [];
        this.currentTime = 0;
        this.isPlaying = false;
        this.isSeeking = false;
        this.totalDuration = 0;
        this.currentSceneIndex = -1;

        // Audio
        this.audioElements = new Map();
        this.musicElement = null;
        this.musicVolume = 0.3;
        this.voiceVolume = 1.0;

        // Media cache
        this.imageCache = new Map();
        this.videoCache = new Map();

        // Animation
        this.animationFrameId = null;
        this.lastFrameTime = 0;

        // Callbacks
        this.onTimeUpdate = options.onTimeUpdate || (() => {});
        this.onSceneChange = options.onSceneChange || (() => {});
        this.onEnded = options.onEnded || (() => {});
        this.onLoadProgress = options.onLoadProgress || (() => {});
        this.onReady = options.onReady || (() => {});

        // Captions
        this.captionsEnabled = true;
        this.captionStyle = 'karaoke';
        this.captionPosition = 'bottom';
        this.captionSize = 1.0;

        // Enhanced caption properties
        this.captionMode = 'word';
        this.captionFontFamily = 'Montserrat';
        this.captionFontWeight = 600;
        this.captionFillColor = '#FFFFFF';
        this.captionStrokeColor = '#000000';
        this.captionStrokeWidth = 2;
        this.captionEffect = 'none';
        this.captionHighlightColor = '#FBBF24';

        this._renderLoop = this._renderLoop.bind(this);
    }

    async loadScenes(scenes) {
        this.scenes = scenes.map((scene, index) => ({
            ...scene,
            index,
            startTime: 0,
            endTime: 0
        }));

        this._calculateTiming();
        await this._preloadMedia();
        this._renderFrame();
        this.onReady();
    }

    _calculateTiming() {
        let currentTime = 0;
        for (const scene of this.scenes) {
            scene.startTime = currentTime;
            scene.endTime = currentTime + (scene.visualDuration || scene.duration || 5);
            currentTime = scene.endTime;
        }
        this.totalDuration = currentTime;
    }

    async _preloadMedia() {
        const loadPromises = [];
        let loaded = 0;
        let total = 0;

        for (const scene of this.scenes) {
            if (scene.imageUrl && !scene.videoUrl) total++;
            if (scene.videoUrl) total++;
            if (scene.voiceoverUrl) total++;
        }

        if (total === 0) {
            this.onLoadProgress(1);
            return;
        }

        const updateProgress = () => {
            loaded++;
            this.onLoadProgress(Math.min(loaded / total, 1));
        };

        for (const scene of this.scenes) {
            if (scene.imageUrl && !scene.videoUrl) {
                loadPromises.push(
                    this._loadImage(scene.imageUrl)
                        .then(updateProgress)
                        .catch(updateProgress)
                );
            }

            if (scene.videoUrl) {
                loadPromises.push(
                    this._loadVideo(scene.videoUrl)
                        .then(updateProgress)
                        .catch(updateProgress)
                );
            }

            if (scene.voiceoverUrl) {
                loadPromises.push(
                    this._loadAudio(scene.id, scene.voiceoverUrl)
                        .then(updateProgress)
                        .catch(updateProgress)
                );
            }
        }

        await Promise.allSettled(loadPromises);
    }

    _loadImage(url) {
        return new Promise((resolve) => {
            if (this.imageCache.has(url)) {
                resolve(this.imageCache.get(url));
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.imageCache.set(url, img);
                resolve(img);
            };
            img.onerror = () => {
                console.warn('Failed to load image:', url);
                resolve(null);
            };
            img.src = url;
        });
    }

    _loadVideo(url) {
        return new Promise((resolve) => {
            if (this.videoCache.has(url)) {
                resolve(this.videoCache.get(url));
                return;
            }

            const video = document.createElement('video');
            video.crossOrigin = 'anonymous';
            video.muted = true;
            video.playsInline = true;
            video.preload = 'auto';

            video.onloadeddata = () => {
                this.videoCache.set(url, video);
                resolve(video);
            };
            video.onerror = () => {
                console.warn('Failed to load video:', url);
                resolve(null);
            };
            video.src = url;
            video.load();
        });
    }

    _loadAudio(sceneId, url) {
        return new Promise((resolve) => {
            if (this.audioElements.has(sceneId)) {
                resolve(this.audioElements.get(sceneId));
                return;
            }

            const audio = new Audio();
            audio.crossOrigin = 'anonymous';
            audio.preload = 'auto';

            audio.oncanplaythrough = () => {
                this.audioElements.set(sceneId, audio);
                resolve(audio);
            };
            audio.onerror = () => {
                console.warn('Failed to load audio:', url);
                resolve(null);
            };
            audio.src = url;
            audio.load();
        });
    }

    async setBackgroundMusic(url, volume = 0.3) {
        if (this.musicElement) {
            this.musicElement.pause();
            this.musicElement = null;
        }

        if (url) {
            return new Promise((resolve, reject) => {
                this.musicElement = new Audio(url);
                this.musicElement.loop = true;
                this.musicElement.volume = volume;
                this.musicVolume = volume;

                this.musicElement.addEventListener('canplaythrough', () => {
                    if (this.isPlaying) {
                        this.musicElement.currentTime = this.currentTime;
                        this.musicElement.play().catch(() => {});
                    }
                    resolve();
                }, { once: true });

                this.musicElement.addEventListener('error', (e) => {
                    reject(new Error('Failed to load music'));
                }, { once: true });

                this.musicElement.load();
            });
        }
    }

    stopBackgroundMusic() {
        if (this.musicElement) {
            this.musicElement.pause();
            this.musicElement = null;
        }
    }

    play() {
        if (this.isPlaying) return;

        this.isPlaying = true;
        this.lastFrameTime = performance.now();

        if (this.musicElement) {
            this.musicElement.currentTime = this.currentTime;
            this.musicElement.play().catch(() => {});
        }

        this._syncAudio();
        this.animationFrameId = requestAnimationFrame(this._renderLoop);
    }

    pause() {
        this.isPlaying = false;

        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }

        this.audioElements.forEach(audio => audio.pause());
        if (this.musicElement) this.musicElement.pause();
    }

    stop() {
        this.pause();
        this.seek(0);
    }

    seek(time) {
        this.isSeeking = true;
        this.currentTime = Math.max(0, Math.min(time, this.totalDuration));

        this._syncAudio();

        if (this.musicElement) {
            this.musicElement.currentTime = this.currentTime;
        }

        this._renderFrame();
        this.onTimeUpdate(this.currentTime);
        this.isSeeking = false;
    }

    jumpToScene(sceneIndex) {
        const scene = this.scenes[sceneIndex];
        if (scene) {
            this.seek(scene.startTime);
        }
    }

    _syncAudio() {
        const currentScene = this._getSceneAtTime(this.currentTime);

        this.audioElements.forEach((audio, sceneId) => {
            if (!currentScene || sceneId !== currentScene.id) {
                if (!audio.paused) {
                    audio.pause();
                }
            }
        });

        if (currentScene) {
            const audio = this.audioElements.get(currentScene.id);
            if (audio) {
                const sceneLocalTime = this.currentTime - currentScene.startTime;
                const voiceoverOffset = currentScene.voiceoverOffset || 0;
                const audioTime = sceneLocalTime - voiceoverOffset;

                if (audioTime >= 0 && audioTime < audio.duration) {
                    const drift = Math.abs(audio.currentTime - audioTime);
                    if (drift > 0.3) {
                        audio.currentTime = audioTime;
                    }

                    audio.volume = this.voiceVolume;

                    if (this.isPlaying && audio.paused) {
                        audio.play().catch(() => {});
                    }
                } else {
                    if (!audio.paused) {
                        audio.pause();
                    }
                }
            }
        }
    }

    _renderLoop(timestamp) {
        if (!this.isPlaying) return;

        const deltaTime = (timestamp - this.lastFrameTime) / 1000;
        this.lastFrameTime = timestamp;

        this.currentTime += deltaTime;

        if (this.currentTime >= this.totalDuration) {
            this.currentTime = this.totalDuration;
            this.pause();
            this.onEnded();
            return;
        }

        this._renderFrame();

        if (Math.floor(this.currentTime * 2) !== Math.floor((this.currentTime - deltaTime) * 2)) {
            this._syncAudio();
        }

        this.onTimeUpdate(this.currentTime);
        this.animationFrameId = requestAnimationFrame(this._renderLoop);
    }

    _renderFrame() {
        this.ctx.fillStyle = '#000';
        this.ctx.fillRect(0, 0, this.width, this.height);

        const currentScene = this._getSceneAtTime(this.currentTime);
        const prevScene = this._getPreviousScene(currentScene);

        if (!currentScene) return;

        const transitionProgress = this._getTransitionProgress(currentScene);

        if (transitionProgress !== null && prevScene) {
            this._renderTransition(prevScene, currentScene, transitionProgress);
        } else {
            this._renderScene(currentScene);
        }

        // Render captions
        if (this.captionsEnabled && currentScene.caption) {
            this._renderCaption(currentScene);
        }

        // Scene change callback
        if (currentScene.index !== this.currentSceneIndex) {
            this.currentSceneIndex = currentScene.index;
            this.onSceneChange(currentScene.index);
        }
    }

    _getSceneAtTime(time) {
        return this.scenes.find(s => time >= s.startTime && time < s.endTime);
    }

    _getPreviousScene(currentScene) {
        if (!currentScene || currentScene.index === 0) return null;
        return this.scenes[currentScene.index - 1];
    }

    _getTransitionProgress(scene) {
        if (!scene || !scene.transition) return null;
        const transitionDuration = scene.transition.duration || 0.5;
        const sceneProgress = this.currentTime - scene.startTime;

        if (sceneProgress < transitionDuration) {
            return sceneProgress / transitionDuration;
        }
        return null;
    }

    _renderScene(scene) {
        const sceneLocalTime = this.currentTime - scene.startTime;
        const sceneDuration = scene.endTime - scene.startTime;
        const progress = sceneLocalTime / sceneDuration;

        if (scene.videoUrl && this.videoCache.has(scene.videoUrl)) {
            this._renderVideoScene(scene, progress);
        } else if (scene.imageUrl && this.imageCache.has(scene.imageUrl)) {
            this._renderImageScene(scene, progress);
        }
    }

    _renderImageScene(scene, progress) {
        const img = this.imageCache.get(scene.imageUrl);
        if (!img) return;

        // Ken Burns effect
        const kb = scene.kenBurns || {
            startScale: 1.0,
            endScale: 1.1,
            startX: 0.5,
            startY: 0.5,
            endX: 0.5,
            endY: 0.4
        };

        const scale = kb.startScale + (kb.endScale - kb.startScale) * progress;
        const centerX = kb.startX + (kb.endX - kb.startX) * progress;
        const centerY = kb.startY + (kb.endY - kb.startY) * progress;

        this._drawImageWithKenBurns(img, scale, centerX, centerY);
    }

    _renderVideoScene(scene, progress) {
        const video = this.videoCache.get(scene.videoUrl);
        if (!video) return;

        const targetTime = progress * video.duration;
        if (Math.abs(video.currentTime - targetTime) > 0.1) {
            video.currentTime = targetTime;
        }

        this.ctx.drawImage(video, 0, 0, this.width, this.height);
    }

    _drawImageWithKenBurns(img, scale, centerX, centerY) {
        const canvasAspect = this.width / this.height;
        const imgAspect = img.width / img.height;

        let drawWidth, drawHeight;

        if (imgAspect > canvasAspect) {
            drawHeight = this.height * scale;
            drawWidth = drawHeight * imgAspect;
        } else {
            drawWidth = this.width * scale;
            drawHeight = drawWidth / imgAspect;
        }

        const drawX = (this.width - drawWidth) * centerX;
        const drawY = (this.height - drawHeight) * centerY;

        this.ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
    }

    _renderTransition(prevScene, nextScene, progress) {
        const transitionType = nextScene.transition?.type || 'fade';

        switch (transitionType) {
            case 'cut':
                this._renderScene(nextScene);
                break;
            case 'slide-left':
                this._renderSlideTransition(prevScene, nextScene, progress, 'left');
                break;
            case 'slide-right':
                this._renderSlideTransition(prevScene, nextScene, progress, 'right');
                break;
            case 'zoom-in':
                this._renderZoomTransition(prevScene, nextScene, progress, 'in');
                break;
            case 'zoom-out':
                this._renderZoomTransition(prevScene, nextScene, progress, 'out');
                break;
            case 'fade':
            default:
                this._renderFadeTransition(prevScene, nextScene, progress);
                break;
        }
    }

    _renderFadeTransition(prevScene, nextScene, progress) {
        this._renderScene(prevScene);
        this.ctx.globalAlpha = progress;
        this._renderScene(nextScene);
        this.ctx.globalAlpha = 1;
    }

    _renderSlideTransition(prevScene, nextScene, progress, direction) {
        const offset = direction === 'left' ? -this.width * progress : this.width * progress;

        this.ctx.save();
        this.ctx.translate(offset, 0);
        this._renderScene(prevScene);
        this.ctx.restore();

        this.ctx.save();
        this.ctx.translate(offset + (direction === 'left' ? this.width : -this.width), 0);
        this._renderScene(nextScene);
        this.ctx.restore();
    }

    _renderZoomTransition(prevScene, nextScene, progress, direction) {
        const scale = direction === 'in' ? 1 + progress * 0.5 : 1 - progress * 0.3;
        const alpha = 1 - progress;

        this.ctx.save();
        this.ctx.translate(this.width / 2, this.height / 2);
        this.ctx.scale(scale, scale);
        this.ctx.translate(-this.width / 2, -this.height / 2);
        this.ctx.globalAlpha = alpha;
        this._renderScene(prevScene);
        this.ctx.restore();

        this.ctx.globalAlpha = progress;
        this._renderScene(nextScene);
        this.ctx.globalAlpha = 1;
    }

    _renderCaption(scene) {
        const caption = scene.caption;
        if (!caption || !caption.text) return;

        const sceneLocalTime = this.currentTime - scene.startTime;

        // Position calculation
        let y;
        const padding = this.height * 0.08;
        switch (this.captionPosition) {
            case 'top':
                y = padding + this.height * 0.05;
                break;
            case 'middle':
                y = this.height / 2;
                break;
            case 'bottom':
            default:
                y = this.height - padding;
                break;
        }

        // Style-based rendering
        switch (this.captionStyle) {
            case 'beasty':
                this._renderBeastyCaption(caption, y, sceneLocalTime);
                break;
            case 'hormozi':
                this._renderHormoziCaption(caption, y, sceneLocalTime);
                break;
            case 'ali':
                this._renderAliCaption(caption, y, sceneLocalTime);
                break;
            case 'podcast':
                this._renderPodcastCaption(caption, y, sceneLocalTime);
                break;
            case 'minimal':
                this._renderMinimalCaption(caption, y, sceneLocalTime);
                break;
            case 'karaoke':
            default:
                this._renderKaraokeCaption(caption, y, sceneLocalTime);
                break;
        }
    }

    _renderKaraokeCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.045 * this.captionSize);
        this.ctx.font = `${this.captionFontWeight} ${fontSize}px ${this.captionFontFamily}, Arial, sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text;
        const x = this.width / 2;

        // Draw stroke
        if (this.captionStrokeWidth > 0) {
            this.ctx.strokeStyle = this.captionStrokeColor;
            this.ctx.lineWidth = this.captionStrokeWidth * 2;
            this.ctx.strokeText(text, x, y);
        }

        // Draw text
        this.ctx.fillStyle = this.captionFillColor;
        this.ctx.fillText(text, x, y);
    }

    _renderBeastyCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.06 * this.captionSize);
        this.ctx.font = `bold ${fontSize}px Impact, 'Arial Black', sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text.toUpperCase();
        const x = this.width / 2;

        // Stroke
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 4;
        this.ctx.strokeText(text, x, y);

        // Fill with yellow
        this.ctx.fillStyle = '#FBBF24';
        this.ctx.fillText(text, x, y);
    }

    _renderHormoziCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.05 * this.captionSize);
        this.ctx.font = `600 ${fontSize}px Inter, Arial, sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text;
        const x = this.width / 2;

        // Background box
        const metrics = this.ctx.measureText(text);
        const boxPadding = 15;
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        this.ctx.fillRect(
            x - metrics.width / 2 - boxPadding,
            y - fontSize / 2 - boxPadding / 2,
            metrics.width + boxPadding * 2,
            fontSize + boxPadding
        );

        // Text
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.fillText(text, x, y);
    }

    _renderAliCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.045 * this.captionSize);
        this.ctx.font = `500 ${fontSize}px Inter, Arial, sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text;
        const x = this.width / 2;

        // Glow effect
        this.ctx.shadowColor = '#10B981';
        this.ctx.shadowBlur = 20;
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.fillText(text, x, y);
        this.ctx.shadowBlur = 0;
    }

    _renderPodcastCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.04 * this.captionSize);
        this.ctx.font = `500 ${fontSize}px Roboto, Arial, sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text;
        const x = this.width / 2;

        // Simple stroke
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 2;
        this.ctx.strokeText(text, x, y);

        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.fillText(text, x, y);
    }

    _renderMinimalCaption(caption, y, localTime) {
        const fontSize = Math.round(this.height * 0.035 * this.captionSize);
        this.ctx.font = `400 ${fontSize}px Inter, Arial, sans-serif`;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        const text = caption.text;
        const x = this.width / 2;

        this.ctx.globalAlpha = 0.9;
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.fillText(text, x, y);
        this.ctx.globalAlpha = 1;
    }

    // Caption settings methods
    setCaptionStyle(style) {
        this.captionStyle = style;
        this._renderFrame();
    }

    setCaptionPosition(position) {
        this.captionPosition = position;
        this._renderFrame();
    }

    setCaptionSize(size) {
        this.captionSize = size;
        this._renderFrame();
    }

    setCaptionsEnabled(enabled) {
        this.captionsEnabled = enabled;
        this._renderFrame();
    }

    setVolume(voice = 1.0, music = 0.3) {
        this.voiceVolume = voice;
        this.musicVolume = music;
        if (this.musicElement) {
            this.musicElement.volume = music;
        }
    }

    // Resize canvas
    resize(width, height) {
        this.width = width;
        this.height = height;
        this.canvas.width = width;
        this.canvas.height = height;
        this._renderFrame();
    }

    // Cleanup
    destroy() {
        this.pause();
        this.audioElements.forEach(audio => {
            audio.pause();
            audio.src = '';
        });
        this.audioElements.clear();

        if (this.musicElement) {
            this.musicElement.pause();
            this.musicElement.src = '';
            this.musicElement = null;
        }

        this.imageCache.clear();
        this.videoCache.clear();
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VideoPreviewEngine;
}

// Make available globally
window.VideoPreviewEngine = VideoPreviewEngine;
