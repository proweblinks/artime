{{--
    Professional Timeline Component - Phase 5
    Multi-track, zoom, clip trimming, undo/redo
--}}

<div
    class="vw-pro-timeline"
    x-data="{
        // Synced from parent previewController
        currentTime: 0,
        totalDuration: {{ $this->getTotalDuration() ?? 0 }},

        // Timeline state
        zoom: 1,
        zoomLevels: [0.5, 0.75, 1, 1.5, 2, 3],
        scrollLeft: 0,
        isDragging: false,
        dragType: null,
        dragTarget: null,
        dragStartX: 0,
        dragStartValue: 0,

        // Track visibility
        tracks: {
            video: { visible: true, height: 60, color: '#8b5cf6' },
            voiceover: { visible: true, height: 40, color: '#06b6d4' },
            music: { visible: true, height: 40, color: '#10b981' },
            captions: { visible: true, height: 35, color: '#f59e0b' }
        },

        // Selection
        selectedClip: null,
        selectedTrack: null,

        // Undo/Redo
        history: [],
        historyIndex: -1,
        maxHistory: 50,

        // Snapping
        snapEnabled: true,
        snapThreshold: 10,

        // Format time helper (local copy since we have isolated scope)
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + secs.toString().padStart(2, '0');
        },

        // Seek helper - dispatches to parent
        seek(time) {
            window.dispatchEvent(new CustomEvent('seek-preview', { detail: { time: time } }));
        },

        // Computed values
        get pixelsPerSecond() {
            return 50 * this.zoom;
        },

        get timelineWidth() {
            return Math.max((this.totalDuration || 1) * this.pixelsPerSecond, 800);
        },

        get visibleTracks() {
            return Object.entries(this.tracks).filter(([k, v]) => v.visible);
        },

        // Methods
        zoomIn() {
            const idx = this.zoomLevels.indexOf(this.zoom);
            if (idx < this.zoomLevels.length - 1) {
                this.zoom = this.zoomLevels[idx + 1];
            }
        },

        zoomOut() {
            const idx = this.zoomLevels.indexOf(this.zoom);
            if (idx > 0) {
                this.zoom = this.zoomLevels[idx - 1];
            }
        },

        zoomFit() {
            const container = this.$refs.timelineContent;
            if (container && this.totalDuration > 0) {
                const availableWidth = container.offsetWidth - 100;
                this.zoom = Math.max(0.5, Math.min(3, availableWidth / (this.totalDuration * 50)));
            }
        },

        timeToPixels(time) {
            return time * this.pixelsPerSecond;
        },

        pixelsToTime(pixels) {
            return pixels / this.pixelsPerSecond;
        },

        seekToPosition(e) {
            const rect = this.$refs.timelineRuler.getBoundingClientRect();
            const x = e.clientX - rect.left + this.scrollLeft;
            const time = this.pixelsToTime(x);
            this.seek(Math.max(0, Math.min(this.totalDuration, time)));
        },

        // Clip dragging
        startDrag(e, type, target, startValue) {
            this.isDragging = true;
            this.dragType = type;
            this.dragTarget = target;
            this.dragStartX = e.clientX;
            this.dragStartValue = startValue;
            document.addEventListener('mousemove', this.handleDrag.bind(this));
            document.addEventListener('mouseup', this.endDrag.bind(this));
        },

        handleDrag(e) {
            if (!this.isDragging) return;

            const deltaX = e.clientX - this.dragStartX;
            const deltaTime = this.pixelsToTime(deltaX);

            if (this.dragType === 'trim-start') {
                this.handleTrimStart(deltaTime);
            } else if (this.dragType === 'trim-end') {
                this.handleTrimEnd(deltaTime);
            } else if (this.dragType === 'move') {
                this.handleMove(deltaTime);
            }
        },

        endDrag() {
            if (this.isDragging) {
                this.saveHistory();
            }
            this.isDragging = false;
            this.dragType = null;
            this.dragTarget = null;
            document.removeEventListener('mousemove', this.handleDrag.bind(this));
            document.removeEventListener('mouseup', this.endDrag.bind(this));
        },

        handleTrimStart(deltaTime) {
            // Trim clip start - will update via Livewire
            console.log('Trim start:', this.dragTarget, deltaTime);
        },

        handleTrimEnd(deltaTime) {
            // Trim clip end - will update via Livewire
            console.log('Trim end:', this.dragTarget, deltaTime);
        },

        handleMove(deltaTime) {
            // Move clip - will update via Livewire
            console.log('Move clip:', this.dragTarget, deltaTime);
        },

        // Selection
        selectClip(track, clipIndex) {
            this.selectedTrack = track;
            this.selectedClip = clipIndex;
            $dispatch('clip-selected', { track, clipIndex });
        },

        deselectAll() {
            this.selectedTrack = null;
            this.selectedClip = null;
        },

        // History management
        saveHistory() {
            // Remove any future states if we're not at the end
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            // Add current state
            this.history.push({
                timestamp: Date.now(),
                // State would be captured here
            });

            // Limit history size
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
        },

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.restoreState(this.history[this.historyIndex]);
                $wire.call('timelineUndo');
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.restoreState(this.history[this.historyIndex]);
                $wire.call('timelineRedo');
            }
        },

        restoreState(state) {
            // Restore timeline state
            console.log('Restoring state:', state);
        },

        get canUndo() {
            return this.historyIndex > 0;
        },

        get canRedo() {
            return this.historyIndex < this.history.length - 1;
        },

        // Time formatting
        formatTimeRuler(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        // Generate ruler marks
        get rulerMarks() {
            const marks = [];
            const interval = this.zoom >= 2 ? 1 : (this.zoom >= 1 ? 2 : 5);
            for (let t = 0; t <= this.totalDuration; t += interval) {
                marks.push({
                    time: t,
                    position: this.timeToPixels(t),
                    major: t % (interval * 2) === 0
                });
            }
            return marks;
        }
    }"
    x-init="
        // Listen for time updates from preview controller
        window.addEventListener('preview-time-update', (e) => {
            if (e.detail && typeof e.detail.time !== 'undefined') {
                currentTime = e.detail.time;
            }
        });

        // Listen for preview ready to get total duration
        window.addEventListener('preview-ready', (e) => {
            if (e.detail && typeof e.detail.duration !== 'undefined') {
                totalDuration = e.detail.duration;
            }
        });

        // Also listen as Alpine custom events
        $el.addEventListener('preview-time-update', (e) => {
            if (e.detail && typeof e.detail.time !== 'undefined') {
                currentTime = e.detail.time;
            }
        });
    "
    @click.away="deselectAll()"
    @keydown.ctrl.z.prevent="undo()"
    @keydown.ctrl.y.prevent="redo()"
    @keydown.ctrl.shift.z.prevent="redo()"
>
    {{-- Timeline Toolbar --}}
    <div class="vw-timeline-toolbar">
        {{-- Left: Track Controls --}}
        <div class="vw-toolbar-left">
            <div class="vw-track-toggles">
                <button
                    type="button"
                    @click="tracks.video.visible = !tracks.video.visible"
                    :class="{ 'active': tracks.video.visible }"
                    class="vw-track-toggle"
                    title="{{ __('Video Track') }}"
                >
                    <span class="vw-track-dot" style="background: #8b5cf6;"></span>
                    📹
                </button>
                <button
                    type="button"
                    @click="tracks.voiceover.visible = !tracks.voiceover.visible"
                    :class="{ 'active': tracks.voiceover.visible }"
                    class="vw-track-toggle"
                    title="{{ __('Voiceover Track') }}"
                >
                    <span class="vw-track-dot" style="background: #06b6d4;"></span>
                    🎙️
                </button>
                <button
                    type="button"
                    @click="tracks.music.visible = !tracks.music.visible"
                    :class="{ 'active': tracks.music.visible }"
                    class="vw-track-toggle"
                    title="{{ __('Music Track') }}"
                >
                    <span class="vw-track-dot" style="background: #10b981;"></span>
                    🎵
                </button>
                <button
                    type="button"
                    @click="tracks.captions.visible = !tracks.captions.visible"
                    :class="{ 'active': tracks.captions.visible }"
                    class="vw-track-toggle"
                    title="{{ __('Captions Track') }}"
                >
                    <span class="vw-track-dot" style="background: #f59e0b;"></span>
                    💬
                </button>
            </div>
        </div>

        {{-- Center: Undo/Redo & Snap --}}
        <div class="vw-toolbar-center">
            <div class="vw-history-controls">
                <button
                    type="button"
                    @click="undo()"
                    :disabled="!canUndo"
                    class="vw-toolbar-btn"
                    title="{{ __('Undo') }} (Ctrl+Z)"
                >
                    ↩️
                </button>
                <button
                    type="button"
                    @click="redo()"
                    :disabled="!canRedo"
                    class="vw-toolbar-btn"
                    title="{{ __('Redo') }} (Ctrl+Y)"
                >
                    ↪️
                </button>
            </div>

            <div class="vw-toolbar-divider"></div>

            <button
                type="button"
                @click="snapEnabled = !snapEnabled"
                :class="{ 'active': snapEnabled }"
                class="vw-toolbar-btn snap"
                title="{{ __('Snap to Grid') }}"
            >
                🧲 {{ __('Snap') }}
            </button>
        </div>

        {{-- Right: Zoom Controls --}}
        <div class="vw-toolbar-right">
            <div class="vw-zoom-controls">
                <button
                    type="button"
                    @click="zoomOut()"
                    :disabled="zoom <= 0.5"
                    class="vw-zoom-btn"
                    title="{{ __('Zoom Out') }}"
                >
                    ➖
                </button>
                <div class="vw-zoom-display">
                    <span x-text="Math.round(zoom * 100) + '%'">100%</span>
                </div>
                <button
                    type="button"
                    @click="zoomIn()"
                    :disabled="zoom >= 3"
                    class="vw-zoom-btn"
                    title="{{ __('Zoom In') }}"
                >
                    ➕
                </button>
                <button
                    type="button"
                    @click="zoomFit()"
                    class="vw-zoom-btn fit"
                    title="{{ __('Fit to View') }}"
                >
                    ↔️
                </button>
            </div>

            <div class="vw-time-display">
                <span class="vw-current-time" x-text="formatTime(currentTime)">0:00</span>
                <span class="vw-time-separator">/</span>
                <span class="vw-total-time" x-text="formatTime(totalDuration)">0:00</span>
            </div>
        </div>
    </div>

    {{-- Timeline Content --}}
    <div class="vw-timeline-content" x-ref="timelineContent">
        {{-- Track Labels --}}
        <div class="vw-track-labels">
            <div class="vw-ruler-spacer"></div>
            <template x-for="[trackId, track] in visibleTracks" :key="trackId">
                <div
                    class="vw-track-label"
                    :style="{ height: track.height + 'px', borderLeftColor: track.color }"
                >
                    <span class="vw-label-icon" x-text="trackId === 'video' ? '📹' : (trackId === 'voiceover' ? '🎙️' : (trackId === 'music' ? '🎵' : '💬'))"></span>
                    <span class="vw-label-text" x-text="trackId.charAt(0).toUpperCase() + trackId.slice(1)"></span>
                </div>
            </template>
        </div>

        {{-- Scrollable Timeline Area --}}
        <div
            class="vw-timeline-scroll"
            @scroll="scrollLeft = $el.scrollLeft"
        >
            {{-- Time Ruler --}}
            <div
                class="vw-time-ruler"
                x-ref="timelineRuler"
                :style="{ width: timelineWidth + 'px' }"
                @click="seekToPosition($event)"
            >
                <template x-for="mark in rulerMarks" :key="mark.time">
                    <div
                        class="vw-ruler-mark"
                        :class="{ 'major': mark.major }"
                        :style="{ left: mark.position + 'px' }"
                    >
                        <span
                            class="vw-ruler-time"
                            x-show="mark.major"
                            x-text="formatTimeRuler(mark.time)"
                        ></span>
                    </div>
                </template>

                {{-- Playhead on Ruler --}}
                <div
                    class="vw-playhead-marker"
                    :style="{ left: timeToPixels(currentTime) + 'px' }"
                ></div>
            </div>

            {{-- Tracks Container --}}
            <div class="vw-tracks-container" :style="{ width: timelineWidth + 'px' }">
                {{-- Video Track --}}
                <div
                    class="vw-track video"
                    x-show="tracks.video.visible"
                    :style="{ height: tracks.video.height + 'px' }"
                >
                    @foreach($script['scenes'] ?? [] as $index => $scene)
                        @php
                            $sceneStart = 0;
                            for ($i = 0; $i < $index; $i++) {
                                $sceneStart += ($storyboard['scenes'][$i]['duration'] ?? 5);
                            }
                            $sceneDuration = $storyboard['scenes'][$index]['duration'] ?? 5;
                        @endphp
                        <div
                            class="vw-clip video-clip"
                            :class="{ 'selected': selectedTrack === 'video' && selectedClip === {{ $index }} }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $sceneDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('video', {{ $index }})"
                        >
                            {{-- Trim Handles --}}
                            <div
                                class="vw-trim-handle left"
                                @mousedown.stop="startDrag($event, 'trim-start', { track: 'video', index: {{ $index }} }, {{ $sceneStart }})"
                            ></div>

                            <div class="vw-clip-content">
                                <span class="vw-clip-number">{{ $index + 1 }}</span>
                                <span class="vw-clip-duration">{{ number_format($sceneDuration, 1) }}s</span>
                            </div>

                            <div
                                class="vw-trim-handle right"
                                @mousedown.stop="startDrag($event, 'trim-end', { track: 'video', index: {{ $index }} }, {{ $sceneDuration }})"
                            ></div>
                        </div>
                    @endforeach
                </div>

                {{-- Voiceover Track --}}
                <div
                    class="vw-track voiceover"
                    x-show="tracks.voiceover.visible"
                    :style="{ height: tracks.voiceover.height + 'px' }"
                >
                    @foreach($script['scenes'] ?? [] as $index => $scene)
                        @php
                            $sceneStart = 0;
                            for ($i = 0; $i < $index; $i++) {
                                $sceneStart += ($storyboard['scenes'][$i]['duration'] ?? 5);
                            }
                            $voiceoverDuration = $storyboard['scenes'][$index]['duration'] ?? 5;
                        @endphp
                        <div
                            class="vw-clip voiceover-clip"
                            :class="{ 'selected': selectedTrack === 'voiceover' && selectedClip === {{ $index }} }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $voiceoverDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('voiceover', {{ $index }})"
                        >
                            <div class="vw-waveform">
                                @for($w = 0; $w < 20; $w++)
                                    <div class="vw-wave-bar" style="height: {{ rand(20, 100) }}%;"></div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Music Track --}}
                <div
                    class="vw-track music"
                    x-show="tracks.music.visible"
                    :style="{ height: tracks.music.height + 'px' }"
                >
                    @if($assembly['music']['enabled'] ?? false)
                        <div
                            class="vw-clip music-clip"
                            :class="{ 'selected': selectedTrack === 'music' && selectedClip === 0 }"
                            :style="{
                                left: '0px',
                                width: timeToPixels(totalDuration) + 'px'
                            }"
                            @click.stop="selectClip('music', 0)"
                        >
                            <div class="vw-music-pattern">
                                @for($m = 0; $m < 30; $m++)
                                    <div class="vw-music-bar" style="height: {{ rand(30, 90) }}%;"></div>
                                @endfor
                            </div>
                            <span class="vw-music-label">🎵 {{ __('Background Music') }}</span>
                        </div>
                    @else
                        <div class="vw-track-empty">
                            <span>{{ __('No music added') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Captions Track --}}
                <div
                    class="vw-track captions"
                    x-show="tracks.captions.visible"
                    :style="{ height: tracks.captions.height + 'px' }"
                >
                    @if($assembly['captions']['enabled'] ?? true)
                        @foreach($script['scenes'] ?? [] as $index => $scene)
                            @php
                                $sceneStart = 0;
                                for ($i = 0; $i < $index; $i++) {
                                    $sceneStart += ($storyboard['scenes'][$i]['duration'] ?? 5);
                                }
                                $captionDuration = $storyboard['scenes'][$index]['duration'] ?? 5;
                                $captionText = Str::limit($scene['narration'] ?? '', 30);
                            @endphp
                            <div
                                class="vw-clip caption-clip"
                                :class="{ 'selected': selectedTrack === 'captions' && selectedClip === {{ $index }} }"
                                :style="{
                                    left: timeToPixels({{ $sceneStart }}) + 'px',
                                    width: timeToPixels({{ $captionDuration }}) + 'px'
                                }"
                                @click.stop="selectClip('captions', {{ $index }})"
                                title="{{ $scene['narration'] ?? '' }}"
                            >
                                <span class="vw-caption-text">{{ $captionText }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="vw-track-empty">
                            <span>{{ __('Captions disabled') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Playhead --}}
                <div
                    class="vw-playhead"
                    :style="{ left: timeToPixels(currentTime) + 'px' }"
                >
                    <div class="vw-playhead-line"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Clip Inspector (shown when clip selected) --}}
    <div class="vw-clip-inspector" x-show="selectedClip !== null" x-cloak>
        <div class="vw-inspector-header">
            <span class="vw-inspector-title">
                <span x-text="selectedTrack ? selectedTrack.charAt(0).toUpperCase() + selectedTrack.slice(1) : ''"></span>
                {{ __('Clip') }} #<span x-text="selectedClip !== null ? selectedClip + 1 : ''"></span>
            </span>
            <button type="button" @click="deselectAll()" class="vw-inspector-close">×</button>
        </div>
        <div class="vw-inspector-content">
            <div class="vw-inspector-row">
                <span class="vw-inspector-label">{{ __('Start') }}</span>
                <span class="vw-inspector-value" x-text="formatTime(0)">0:00</span>
            </div>
            <div class="vw-inspector-row">
                <span class="vw-inspector-label">{{ __('Duration') }}</span>
                <span class="vw-inspector-value">--</span>
            </div>
            <div class="vw-inspector-actions">
                <button type="button" class="vw-inspector-btn" title="{{ __('Split Clip') }}">✂️</button>
                <button type="button" class="vw-inspector-btn" title="{{ __('Delete Clip') }}">🗑️</button>
            </div>
        </div>
    </div>
</div>

<style>
    .vw-pro-timeline {
        display: flex;
        flex-direction: column;
        background: rgba(15, 15, 25, 0.98);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        position: relative;
    }

    /* Toolbar */
    .vw-timeline-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        gap: 1rem;
    }

    .vw-toolbar-left,
    .vw-toolbar-center,
    .vw-toolbar-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-track-toggles {
        display: flex;
        gap: 0.25rem;
    }

    .vw-track-toggle {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-track-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .vw-track-toggle.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-track-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .vw-history-controls {
        display: flex;
        gap: 0.25rem;
    }

    .vw-toolbar-btn {
        padding: 0.35rem 0.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-toolbar-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.1);
    }

    .vw-toolbar-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .vw-toolbar-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: white;
    }

    .vw-toolbar-btn.snap {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-toolbar-divider {
        width: 1px;
        height: 20px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0 0.25rem;
    }

    .vw-zoom-controls {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.4rem;
    }

    .vw-zoom-btn {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        cursor: pointer;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }

    .vw-zoom-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.1);
    }

    .vw-zoom-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .vw-zoom-btn.fit {
        margin-left: 0.25rem;
        border-left: 1px solid rgba(255, 255, 255, 0.1);
        padding-left: 0.35rem;
    }

    .vw-zoom-display {
        min-width: 45px;
        text-align: center;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 600;
    }

    .vw-time-display {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.6rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.35rem;
        font-family: 'SF Mono', Monaco, monospace;
        font-size: 0.8rem;
    }

    .vw-current-time {
        color: white;
        font-weight: 600;
    }

    .vw-time-separator {
        color: rgba(255, 255, 255, 0.3);
    }

    .vw-total-time {
        color: rgba(255, 255, 255, 0.5);
    }

    /* Timeline Content */
    .vw-timeline-content {
        display: flex;
        flex: 1;
        min-height: 180px;
        max-height: 250px;
        overflow: hidden;
    }

    .vw-track-labels {
        width: 100px;
        min-width: 100px;
        background: rgba(0, 0, 0, 0.2);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        flex-direction: column;
    }

    .vw-ruler-spacer {
        height: 28px;
        min-height: 28px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-track-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        border-left: 3px solid transparent;
    }

    .vw-label-icon {
        font-size: 0.85rem;
    }

    .vw-label-text {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Scrollable Area */
    .vw-timeline-scroll {
        flex: 1;
        overflow-x: auto;
        overflow-y: hidden;
        position: relative;
    }

    /* Time Ruler */
    .vw-time-ruler {
        height: 28px;
        min-height: 28px;
        background: rgba(0, 0, 0, 0.3);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        cursor: pointer;
    }

    .vw-ruler-mark {
        position: absolute;
        top: 0;
        width: 1px;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
    }

    .vw-ruler-mark.major {
        background: rgba(255, 255, 255, 0.2);
    }

    .vw-ruler-time {
        position: absolute;
        top: 4px;
        left: 4px;
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
        white-space: nowrap;
    }

    .vw-playhead-marker {
        position: absolute;
        top: 0;
        width: 0;
        height: 100%;
        border-left: 2px solid #ef4444;
    }

    .vw-playhead-marker::before {
        content: '';
        position: absolute;
        top: 0;
        left: -6px;
        width: 10px;
        height: 10px;
        background: #ef4444;
        clip-path: polygon(50% 100%, 0 0, 100% 0);
    }

    /* Tracks Container */
    .vw-tracks-container {
        position: relative;
        min-height: calc(100% - 28px);
    }

    .vw-track {
        position: relative;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .vw-track.video {
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-track.voiceover {
        background: rgba(6, 182, 212, 0.05);
    }

    .vw-track.music {
        background: rgba(16, 185, 129, 0.05);
    }

    .vw-track.captions {
        background: rgba(245, 158, 11, 0.05);
    }

    .vw-track-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: rgba(255, 255, 255, 0.3);
        font-size: 0.7rem;
    }

    /* Clips */
    .vw-clip {
        position: absolute;
        top: 4px;
        height: calc(100% - 8px);
        border-radius: 0.35rem;
        overflow: hidden;
        cursor: pointer;
        transition: box-shadow 0.2s;
    }

    .vw-clip:hover {
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
    }

    .vw-clip.selected {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(139, 92, 246, 0.5);
    }

    .vw-clip.video-clip {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(139, 92, 246, 0.2));
        border: 1px solid rgba(139, 92, 246, 0.5);
    }

    .vw-clip.voiceover-clip {
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.4), rgba(6, 182, 212, 0.2));
        border: 1px solid rgba(6, 182, 212, 0.5);
    }

    .vw-clip.music-clip {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(16, 185, 129, 0.2));
        border: 1px solid rgba(16, 185, 129, 0.5);
    }

    .vw-clip.caption-clip {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.4), rgba(245, 158, 11, 0.2));
        border: 1px solid rgba(245, 158, 11, 0.5);
    }

    .vw-clip-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 0 0.5rem;
    }

    .vw-clip-number {
        font-size: 0.8rem;
        font-weight: 700;
        color: white;
    }

    .vw-clip-duration {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Trim Handles */
    .vw-trim-handle {
        position: absolute;
        top: 0;
        width: 8px;
        height: 100%;
        cursor: ew-resize;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .vw-clip:hover .vw-trim-handle,
    .vw-clip.selected .vw-trim-handle {
        opacity: 1;
    }

    .vw-trim-handle.left {
        left: 0;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.4), transparent);
    }

    .vw-trim-handle.right {
        right: 0;
        background: linear-gradient(-90deg, rgba(255, 255, 255, 0.4), transparent);
    }

    /* Waveform */
    .vw-waveform {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 2px;
        padding: 0 0.25rem;
    }

    .vw-wave-bar {
        width: 3px;
        background: rgba(6, 182, 212, 0.6);
        border-radius: 2px;
    }

    /* Music Pattern */
    .vw-music-pattern {
        display: flex;
        align-items: center;
        height: 100%;
        gap: 3px;
        padding: 0 0.5rem;
        opacity: 0.6;
    }

    .vw-music-bar {
        width: 4px;
        background: rgba(16, 185, 129, 0.6);
        border-radius: 2px;
    }

    .vw-music-label {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.8);
        white-space: nowrap;
        background: rgba(0, 0, 0, 0.3);
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
    }

    /* Caption Text */
    .vw-caption-text {
        display: block;
        padding: 0.25rem 0.4rem;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.8);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Playhead */
    .vw-playhead {
        position: absolute;
        top: 0;
        width: 2px;
        height: 100%;
        pointer-events: none;
        z-index: 100;
    }

    .vw-playhead-line {
        width: 2px;
        height: 100%;
        background: #ef4444;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
    }

    /* Clip Inspector */
    .vw-clip-inspector {
        position: absolute;
        bottom: 100%;
        right: 10px;
        margin-bottom: 5px;
        width: 200px;
        background: rgba(30, 30, 45, 0.98);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        z-index: 200;
    }

    .vw-inspector-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-inspector-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-inspector-close {
        width: 20px;
        height: 20px;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 0.25rem;
        cursor: pointer;
        font-size: 0.8rem;
    }

    .vw-inspector-content {
        padding: 0.5rem 0.6rem;
    }

    .vw-inspector-row {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0;
    }

    .vw-inspector-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-inspector-value {
        font-size: 0.7rem;
        color: white;
        font-weight: 600;
    }

    .vw-inspector-actions {
        display: flex;
        gap: 0.35rem;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-inspector-btn {
        flex: 1;
        padding: 0.35rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.3rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-inspector-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    [x-cloak] {
        display: none !important;
    }

    /* Scrollbar styling */
    .vw-timeline-scroll::-webkit-scrollbar {
        height: 8px;
    }

    .vw-timeline-scroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-timeline-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .vw-timeline-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
