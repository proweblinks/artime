{{--
    Professional Timeline Component - Phase 1 & 2 Redesign
    Modern glassmorphism design with advanced interactions
--}}

<div
    class="vw-pro-timeline"
    x-data="{
        // Synced from parent previewController
        currentTime: 0,
        totalDuration: {{ $this->getTotalDuration() ?? 0 }},

        // Timeline state
        zoom: 1,
        zoomLevels: [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2, 3, 4],
        scrollLeft: 0,

        // Playhead dragging
        isPlayheadDragging: false,
        playheadTooltipTime: 0,

        // Track visibility and configuration
        tracks: {
            video: { visible: true, height: 70, color: '#8b5cf6', label: 'Video', icon: 'film', locked: false, muted: false },
            voiceover: { visible: true, height: 50, color: '#06b6d4', label: 'Voiceover', icon: 'mic', locked: false, muted: false },
            music: { visible: true, height: 50, color: '#10b981', label: 'Music', icon: 'music', locked: false, muted: false },
            captions: { visible: true, height: 40, color: '#f59e0b', label: 'Captions', icon: 'text', locked: false, muted: false }
        },

        // Selection
        selectedClip: null,
        selectedTrack: null,
        hoveredClip: null,
        hoveredTrack: null,

        // Undo/Redo
        history: [],
        historyIndex: -1,
        maxHistory: 50,

        // ===== Phase 2: Snapping System =====
        snapEnabled: true,
        snapThreshold: 10,
        snapThresholdOptions: [5, 10, 15, 20],
        showSnapIndicator: false,
        snapIndicatorPosition: 0,
        activeSnapPoints: [],
        lastSnapTime: 0,

        // ===== Phase 2: Drag & Drop System =====
        isDragging: false,
        dragType: null, // 'move', 'trim-start', 'trim-end'
        dragTarget: null,
        dragStartX: 0,
        dragStartY: 0,
        dragStartValue: 0,
        dragCurrentValue: 0,
        dragDelta: 0,

        // Ghost clip state
        showGhostClip: false,
        ghostClipLeft: 0,
        ghostClipWidth: 0,
        ghostClipTrack: null,
        ghostClipOriginalLeft: 0,

        // Drop zone state
        dropZoneTrack: null,
        dropZonePosition: null,
        showInsertIndicator: false,
        insertIndicatorPosition: 0,

        // Trim preview state
        showTrimPreview: false,
        trimPreviewTime: 0,
        trimPreviewType: null,
        trimOriginalStart: 0,
        trimOriginalDuration: 0,

        // ===== Phase 2: Ripple Edit Mode =====
        rippleMode: false,
        affectedClips: [],

        // Format time helper
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + secs.toString().padStart(2, '0');
        },

        formatTimeDetailed(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00.0';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            const ms = Math.floor((seconds % 1) * 10);
            return mins + ':' + secs.toString().padStart(2, '0') + '.' + ms;
        },

        // Seek helper - dispatches to parent
        seek(time) {
            window.dispatchEvent(new CustomEvent('seek-preview', { detail: { time: time } }));
        },

        // Computed values
        get pixelsPerSecond() {
            return 60 * this.zoom;
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
            const container = this.$refs.timelineScroll;
            if (container && this.totalDuration > 0) {
                const availableWidth = container.offsetWidth - 40;
                const idealZoom = availableWidth / (this.totalDuration * 60);
                // Find closest zoom level
                let closest = this.zoomLevels[0];
                for (const level of this.zoomLevels) {
                    if (Math.abs(level - idealZoom) < Math.abs(closest - idealZoom)) {
                        closest = level;
                    }
                }
                this.zoom = closest;
            }
        },

        timeToPixels(time) {
            return time * this.pixelsPerSecond;
        },

        pixelsToTime(pixels) {
            return pixels / this.pixelsPerSecond;
        },

        // Ruler click to seek
        seekToPosition(e) {
            const rect = this.$refs.timelineRuler.getBoundingClientRect();
            const x = e.clientX - rect.left + this.$refs.timelineScroll.scrollLeft;
            const time = this.pixelsToTime(x);
            this.seek(Math.max(0, Math.min(this.totalDuration, time)));
        },

        // Playhead dragging
        startPlayheadDrag(e) {
            this.isPlayheadDragging = true;
            this.playheadTooltipTime = this.currentTime;
            document.addEventListener('mousemove', this.handlePlayheadDrag.bind(this));
            document.addEventListener('mouseup', this.endPlayheadDrag.bind(this));
            e.preventDefault();
        },

        handlePlayheadDrag(e) {
            if (!this.isPlayheadDragging) return;
            const container = this.$refs.timelineScroll;
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left + container.scrollLeft;
            let time = this.pixelsToTime(x);
            time = Math.max(0, Math.min(this.totalDuration, time));

            // Snapping
            if (this.snapEnabled) {
                const snapPoints = this.getSnapPoints();
                for (const point of snapPoints) {
                    if (Math.abs(this.timeToPixels(time) - this.timeToPixels(point)) < this.snapThreshold) {
                        time = point;
                        this.showSnapIndicator = true;
                        this.snapIndicatorPosition = this.timeToPixels(point);
                        break;
                    } else {
                        this.showSnapIndicator = false;
                    }
                }
            }

            this.playheadTooltipTime = time;
            this.seek(time);
        },

        endPlayheadDrag() {
            this.isPlayheadDragging = false;
            this.showSnapIndicator = false;
            document.removeEventListener('mousemove', this.handlePlayheadDrag.bind(this));
            document.removeEventListener('mouseup', this.endPlayheadDrag.bind(this));
        },

        // ===== Enhanced Snap System =====
        getSnapPoints() {
            const points = [];

            // Add timeline boundaries
            points.push({ time: 0, type: 'boundary', label: 'Start' });
            points.push({ time: this.totalDuration, type: 'boundary', label: 'End' });

            // Add playhead position
            points.push({ time: this.currentTime, type: 'playhead', label: 'Playhead' });

            // Add clip edges from all tracks
            @foreach($script['scenes'] ?? [] as $index => $scene)
                @php
                    $sceneStart = 0;
                    for ($i = 0; $i < $index; $i++) {
                        $sceneStart += ($storyboard['scenes'][$i]['duration'] ?? 5);
                    }
                    $sceneDuration = $storyboard['scenes'][$index]['duration'] ?? 5;
                    $sceneEnd = $sceneStart + $sceneDuration;
                @endphp
                points.push({ time: {{ $sceneStart }}, type: 'clip-start', index: {{ $index }} });
                points.push({ time: {{ $sceneEnd }}, type: 'clip-end', index: {{ $index }} });
            @endforeach

            return points;
        },

        findSnapPoint(time, excludeIndex = null) {
            if (!this.snapEnabled) return null;

            const points = this.getSnapPoints();
            let closestSnap = null;
            let minDistance = this.snapThreshold;

            for (const point of points) {
                // Skip self when moving/trimming
                if (excludeIndex !== null && point.index === excludeIndex) continue;

                const distance = Math.abs(this.timeToPixels(time) - this.timeToPixels(point.time));
                if (distance < minDistance) {
                    minDistance = distance;
                    closestSnap = point;
                }
            }

            return closestSnap;
        },

        showSnapFeedback(snapPoint) {
            this.showSnapIndicator = true;
            this.snapIndicatorPosition = this.timeToPixels(snapPoint.time);
            this.activeSnapPoints = [snapPoint];
            this.lastSnapTime = Date.now();

            // Haptic-style pulse animation (trigger CSS animation)
            const indicator = this.$refs.snapIndicator;
            if (indicator) {
                indicator.classList.remove('snap-pulse');
                void indicator.offsetWidth; // Trigger reflow
                indicator.classList.add('snap-pulse');
            }
        },

        hideSnapFeedback() {
            this.showSnapIndicator = false;
            this.activeSnapPoints = [];
        },

        // ===== Enhanced Drag & Drop System =====
        startDrag(e, type, target, startValue, clipWidth = 0, clipStart = 0) {
            if (this.tracks[target.track]?.locked) return;

            this.isDragging = true;
            this.dragType = type;
            this.dragTarget = target;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.dragStartValue = startValue;
            this.dragCurrentValue = startValue;
            this.dragDelta = 0;

            // Store original values for trim
            if (type === 'trim-start' || type === 'trim-end') {
                this.trimOriginalStart = clipStart;
                this.trimOriginalDuration = clipWidth;
                this.showTrimPreview = true;
                this.trimPreviewType = type;
                this.trimPreviewTime = type === 'trim-start' ? clipStart : clipStart + clipWidth;
            }

            // Setup ghost clip for move operations
            if (type === 'move') {
                this.showGhostClip = true;
                this.ghostClipTrack = target.track;
                this.ghostClipWidth = clipWidth;
                this.ghostClipLeft = this.timeToPixels(clipStart);
                this.ghostClipOriginalLeft = this.ghostClipLeft;
            }

            // Add document listeners
            this._boundHandleDrag = this.handleDrag.bind(this);
            this._boundEndDrag = this.endDrag.bind(this);
            document.addEventListener('mousemove', this._boundHandleDrag);
            document.addEventListener('mouseup', this._boundEndDrag);

            // Prevent text selection during drag
            e.preventDefault();
            document.body.style.userSelect = 'none';
            document.body.style.cursor = type === 'move' ? 'grabbing' : 'ew-resize';
        },

        handleDrag(e) {
            if (!this.isDragging) return;

            const deltaX = e.clientX - this.dragStartX;
            const deltaTime = this.pixelsToTime(deltaX);
            this.dragDelta = deltaTime;

            // Calculate new time based on drag type
            let newTime;
            if (this.dragType === 'move') {
                newTime = this.dragStartValue + deltaTime;
            } else if (this.dragType === 'trim-start') {
                newTime = this.trimOriginalStart + deltaTime;
            } else if (this.dragType === 'trim-end') {
                newTime = this.trimOriginalStart + this.trimOriginalDuration + deltaTime;
            }

            // Apply snapping
            const snapPoint = this.findSnapPoint(newTime, this.dragTarget.index);
            if (snapPoint) {
                newTime = snapPoint.time;
                this.showSnapFeedback(snapPoint);
            } else {
                this.hideSnapFeedback();
            }

            // Clamp to valid range
            newTime = Math.max(0, Math.min(this.totalDuration, newTime));
            this.dragCurrentValue = newTime;

            // Update visual feedback
            if (this.dragType === 'move') {
                this.ghostClipLeft = this.timeToPixels(newTime);
                this.updateDropZone(e);
            } else if (this.dragType === 'trim-start' || this.dragType === 'trim-end') {
                this.trimPreviewTime = newTime;
                this.updateRipplePreview();
            }
        },

        endDrag() {
            if (this.isDragging) {
                // Save to history before applying changes
                this.saveHistory();

                // Dispatch event with final values
                if (this.dragType === 'move') {
                    const finalTime = this.pixelsToTime(this.ghostClipLeft);
                    this.$dispatch('clip-moved', {
                        track: this.dragTarget.track,
                        index: this.dragTarget.index,
                        newStart: finalTime,
                        ripple: this.rippleMode
                    });
                } else if (this.dragType === 'trim-start') {
                    const trimDelta = this.dragCurrentValue - this.trimOriginalStart;
                    this.$dispatch('clip-trimmed', {
                        track: this.dragTarget.track,
                        index: this.dragTarget.index,
                        edge: 'start',
                        delta: trimDelta,
                        ripple: this.rippleMode
                    });
                } else if (this.dragType === 'trim-end') {
                    const newDuration = this.dragCurrentValue - this.trimOriginalStart;
                    this.$dispatch('clip-trimmed', {
                        track: this.dragTarget.track,
                        index: this.dragTarget.index,
                        edge: 'end',
                        newDuration: newDuration,
                        ripple: this.rippleMode
                    });
                }
            }

            // Reset all drag state
            this.isDragging = false;
            this.dragType = null;
            this.dragTarget = null;
            this.showGhostClip = false;
            this.showTrimPreview = false;
            this.hideSnapFeedback();
            this.dropZoneTrack = null;
            this.showInsertIndicator = false;
            this.affectedClips = [];

            // Restore document state
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            document.removeEventListener('mousemove', this._boundHandleDrag);
            document.removeEventListener('mouseup', this._boundEndDrag);
        },

        updateDropZone(e) {
            // Determine which track is being hovered
            const container = this.$refs.tracksContainer;
            if (!container) return;

            const rect = container.getBoundingClientRect();
            const y = e.clientY - rect.top;

            let currentY = 0;
            for (const [trackId, track] of Object.entries(this.tracks)) {
                if (!track.visible) continue;
                if (y >= currentY && y < currentY + track.height) {
                    this.dropZoneTrack = trackId;
                    break;
                }
                currentY += track.height;
            }
        },

        updateRipplePreview() {
            if (!this.rippleMode) {
                this.affectedClips = [];
                return;
            }

            // Calculate which clips would be affected by ripple
            const affected = [];
            const clipIndex = this.dragTarget.index;
            const track = this.dragTarget.track;

            // In ripple mode, all clips after the edited clip are affected
            @foreach($script['scenes'] ?? [] as $index => $scene)
                if ({{ $index }} > clipIndex) {
                    affected.push({ track, index: {{ $index }} });
                }
            @endforeach

            this.affectedClips = affected;
        },

        // Toggle ripple edit mode
        toggleRippleMode() {
            this.rippleMode = !this.rippleMode;
        },

        // Toggle track lock
        toggleTrackLock(trackId) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].locked = !this.tracks[trackId].locked;
            }
        },

        // Toggle track mute
        toggleTrackMute(trackId) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].muted = !this.tracks[trackId].muted;
                this.$dispatch('track-muted', { track: trackId, muted: this.tracks[trackId].muted });
            }
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
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }
            this.history.push({ timestamp: Date.now() });
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
        },

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                $wire.call('timelineUndo');
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                $wire.call('timelineRedo');
            }
        },

        get canUndo() {
            return this.historyIndex > 0;
        },

        get canRedo() {
            return this.historyIndex < this.history.length - 1;
        },

        // Ruler marks generation
        get rulerMarks() {
            const marks = [];
            let interval;
            if (this.zoom >= 2) interval = 1;
            else if (this.zoom >= 1) interval = 2;
            else if (this.zoom >= 0.5) interval = 5;
            else interval = 10;

            for (let t = 0; t <= this.totalDuration; t += interval) {
                marks.push({
                    time: t,
                    position: this.timeToPixels(t),
                    major: t % (interval * 2) === 0 || interval >= 5
                });
            }
            // Add sub-marks
            if (this.zoom >= 1.5) {
                const subInterval = interval / 2;
                for (let t = subInterval; t < this.totalDuration; t += interval) {
                    marks.push({
                        time: t,
                        position: this.timeToPixels(t),
                        major: false,
                        sub: true
                    });
                }
            }
            return marks.sort((a, b) => a.time - b.time);
        },

        // Generate waveform path for SVG
        generateWaveformPath(width, height, seed = 0) {
            const points = Math.ceil(width / 3);
            let path = 'M 0 ' + (height / 2);
            const midY = height / 2;

            for (let i = 0; i <= points; i++) {
                const x = (i / points) * width;
                // Create more realistic waveform pattern
                const noise1 = Math.sin(i * 0.3 + seed) * 0.3;
                const noise2 = Math.sin(i * 0.7 + seed * 2) * 0.2;
                const noise3 = Math.sin(i * 0.1 + seed * 0.5) * 0.4;
                const envelope = Math.sin((i / points) * Math.PI) * 0.3 + 0.7;
                const amplitude = (0.3 + noise1 + noise2 + noise3) * envelope;
                const y = midY - (amplitude * midY * 0.8);
                path += ' L ' + x + ' ' + y;
            }

            // Mirror for bottom half
            for (let i = points; i >= 0; i--) {
                const x = (i / points) * width;
                const noise1 = Math.sin(i * 0.3 + seed) * 0.3;
                const noise2 = Math.sin(i * 0.7 + seed * 2) * 0.2;
                const noise3 = Math.sin(i * 0.1 + seed * 0.5) * 0.4;
                const envelope = Math.sin((i / points) * Math.PI) * 0.3 + 0.7;
                const amplitude = (0.3 + noise1 + noise2 + noise3) * envelope;
                const y = midY + (amplitude * midY * 0.8);
                path += ' L ' + x + ' ' + y;
            }

            path += ' Z';
            return path;
        }
    }"
    x-init="
        // Listen for time updates from preview controller
        window.addEventListener('preview-time-update', (e) => {
            if (e.detail && typeof e.detail.time !== 'undefined') {
                currentTime = e.detail.time;
            }
        });

        // Listen for preview ready
        window.addEventListener('preview-ready', (e) => {
            if (e.detail && typeof e.detail.duration !== 'undefined') {
                totalDuration = e.detail.duration;
            }
        });

        // Keyboard shortcuts
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                zoomIn();
            } else if (e.key === '-') {
                e.preventDefault();
                zoomOut();
            } else if (e.key === '0') {
                e.preventDefault();
                zoomFit();
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
        <div class="vw-toolbar-section vw-toolbar-left">
            <div class="vw-track-toggles">
                <template x-for="[trackId, track] in Object.entries(tracks)" :key="trackId">
                    <button
                        type="button"
                        @click="tracks[trackId].visible = !tracks[trackId].visible"
                        :class="{ 'is-active': track.visible }"
                        class="vw-track-toggle"
                        :title="track.label"
                    >
                        <span class="vw-toggle-dot" :style="{ background: track.visible ? track.color : 'transparent', borderColor: track.color }"></span>
                        <span class="vw-toggle-label" x-text="track.label.charAt(0)"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Center: Edit Tools --}}
        <div class="vw-toolbar-section vw-toolbar-center">
            <div class="vw-tool-group">
                <button
                    type="button"
                    @click="undo()"
                    :disabled="!canUndo"
                    class="vw-tool-btn"
                    title="{{ __('Undo') }} (Ctrl+Z)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 10h10a5 5 0 0 1 5 5v0a5 5 0 0 1-5 5H8M3 10l4-4M3 10l4 4"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="redo()"
                    :disabled="!canRedo"
                    class="vw-tool-btn"
                    title="{{ __('Redo') }} (Ctrl+Y)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10H11a5 5 0 0 0-5 5v0a5 5 0 0 0 5 5h5M21 10l-4-4M21 10l-4 4"/>
                    </svg>
                </button>
            </div>

            <div class="vw-toolbar-divider"></div>

            {{-- Snap Control with Threshold --}}
            <div class="vw-snap-control" x-data="{ showSnapMenu: false }" @click.away="showSnapMenu = false">
                <button
                    type="button"
                    @click="snapEnabled = !snapEnabled"
                    :class="{ 'is-active': snapEnabled }"
                    class="vw-tool-btn vw-snap-btn"
                    title="{{ __('Magnetic Snap') }}"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/>
                    </svg>
                    <span>{{ __('Snap') }}</span>
                </button>
                <button
                    type="button"
                    @click="showSnapMenu = !showSnapMenu"
                    class="vw-snap-dropdown-btn"
                    :class="{ 'is-active': snapEnabled }"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
                <div class="vw-snap-menu" x-show="showSnapMenu" x-cloak x-transition>
                    <div class="vw-snap-menu-label">{{ __('Snap Threshold') }}</div>
                    <template x-for="threshold in snapThresholdOptions" :key="threshold">
                        <button
                            type="button"
                            class="vw-snap-option"
                            :class="{ 'is-active': snapThreshold === threshold }"
                            @click="snapThreshold = threshold; showSnapMenu = false"
                        >
                            <span x-text="threshold + 'px'"></span>
                            <svg x-show="snapThreshold === threshold" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        </button>
                    </template>
                </div>
            </div>

            <div class="vw-toolbar-divider"></div>

            {{-- Ripple Edit Mode --}}
            <button
                type="button"
                @click="toggleRippleMode()"
                :class="{ 'is-active': rippleMode }"
                class="vw-tool-btn vw-ripple-btn"
                title="{{ __('Ripple Edit Mode') }} - Auto-shift clips"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 12h4l3-9 4 18 3-9h6"/>
                </svg>
                <span>{{ __('Ripple') }}</span>
            </button>

            <div class="vw-toolbar-divider"></div>

            <button type="button" class="vw-tool-btn" title="{{ __('Split at Playhead') }} (S)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="2" x2="12" y2="22"/>
                    <path d="M8 6l4-4 4 4M8 18l4 4 4-4"/>
                </svg>
            </button>

            <button type="button" class="vw-tool-btn" title="{{ __('Delete Selected') }} (Del)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </div>

        {{-- Right: Zoom & Time --}}
        <div class="vw-toolbar-section vw-toolbar-right">
            <div class="vw-zoom-control">
                <button
                    type="button"
                    @click="zoomOut()"
                    :disabled="zoom <= 0.25"
                    class="vw-zoom-btn"
                    title="{{ __('Zoom Out') }} (-)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </button>

                <div class="vw-zoom-slider-container">
                    <input
                        type="range"
                        class="vw-zoom-slider"
                        min="0"
                        :max="zoomLevels.length - 1"
                        :value="zoomLevels.indexOf(zoom)"
                        @input="zoom = zoomLevels[$event.target.value]"
                    >
                    <span class="vw-zoom-value" x-text="Math.round(zoom * 100) + '%'">100%</span>
                </div>

                <button
                    type="button"
                    @click="zoomIn()"
                    :disabled="zoom >= 4"
                    class="vw-zoom-btn"
                    title="{{ __('Zoom In') }} (+)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="11" y1="8" x2="11" y2="14"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </button>

                <button
                    type="button"
                    @click="zoomFit()"
                    class="vw-zoom-btn vw-zoom-fit"
                    title="{{ __('Fit to View') }} (0)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                </button>
            </div>

            <div class="vw-time-indicator">
                <span class="vw-time-current" x-text="formatTimeDetailed(currentTime)">0:00.0</span>
                <span class="vw-time-sep">/</span>
                <span class="vw-time-total" x-text="formatTime(totalDuration)">0:00</span>
            </div>
        </div>
    </div>

    {{-- Timeline Body --}}
    <div class="vw-timeline-body">
        {{-- Track Headers --}}
        <div class="vw-track-headers">
            <div class="vw-ruler-header">
                <svg viewBox="0 0 24 24" fill="currentColor" class="vw-clock-icon">
                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/>
                </svg>
            </div>
            <template x-for="[trackId, track] in visibleTracks" :key="trackId">
                <div
                    class="vw-track-header"
                    :style="{ height: track.height + 'px', '--track-color': track.color }"
                >
                    <div class="vw-header-color-bar"></div>
                    <div class="vw-header-content">
                        <span class="vw-header-icon">
                            <template x-if="trackId === 'video'">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                            </template>
                            <template x-if="trackId === 'voiceover'">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/></svg>
                            </template>
                            <template x-if="trackId === 'music'">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                            </template>
                            <template x-if="trackId === 'captions'">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4H5c-1.11 0-2 .9-2 2v12c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 7H9.5v-.5h-2v3h2V13H11v1c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1zm7 0h-1.5v-.5h-2v3h2V13H18v1c0 .55-.45 1-1 1h-3c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1z"/></svg>
                            </template>
                        </span>
                        <span class="vw-header-label" x-text="track.label"></span>
                    </div>
                    <div class="vw-header-controls">
                        <button
                            type="button"
                            class="vw-header-btn"
                            :class="{ 'is-active': track.muted }"
                            @click.stop="toggleTrackMute(trackId)"
                            :title="track.muted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}'"
                        >
                            <template x-if="!track.muted">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
                            </template>
                            <template x-if="track.muted">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
                            </template>
                        </button>
                        <button
                            type="button"
                            class="vw-header-btn"
                            :class="{ 'is-active': track.locked }"
                            @click.stop="toggleTrackLock(trackId)"
                            :title="track.locked ? '{{ __('Unlock') }}' : '{{ __('Lock') }}'"
                        >
                            <template x-if="!track.locked">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h2c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
                            </template>
                            <template x-if="track.locked">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            </template>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Scrollable Timeline Area --}}
        <div class="vw-timeline-scroll" x-ref="timelineScroll" @scroll="scrollLeft = $el.scrollLeft">
            {{-- Time Ruler --}}
            <div
                class="vw-time-ruler"
                x-ref="timelineRuler"
                :style="{ width: timelineWidth + 'px' }"
                @click="seekToPosition($event)"
            >
                {{-- Ruler Background Pattern --}}
                <div class="vw-ruler-pattern"></div>

                {{-- Ruler Marks --}}
                <template x-for="mark in rulerMarks" :key="mark.time + '-' + mark.major">
                    <div
                        class="vw-ruler-mark"
                        :class="{ 'is-major': mark.major, 'is-sub': mark.sub }"
                        :style="{ left: mark.position + 'px' }"
                    >
                        <span
                            class="vw-ruler-label"
                            x-show="mark.major && !mark.sub"
                            x-text="formatTime(mark.time)"
                        ></span>
                    </div>
                </template>

                {{-- Playhead Top Marker --}}
                <div
                    class="vw-playhead-top"
                    :style="{ left: timeToPixels(currentTime) + 'px' }"
                    :class="{ 'is-dragging': isPlayheadDragging }"
                    @mousedown="startPlayheadDrag($event)"
                >
                    <div class="vw-playhead-handle">
                        <svg viewBox="0 0 12 16" fill="currentColor">
                            <path d="M0 0h12v10l-6 6-6-6z"/>
                        </svg>
                    </div>
                    {{-- Time tooltip during drag --}}
                    <div class="vw-playhead-tooltip" x-show="isPlayheadDragging" x-cloak>
                        <span x-text="formatTimeDetailed(playheadTooltipTime)"></span>
                    </div>
                </div>
            </div>

            {{-- Tracks Container --}}
            <div class="vw-tracks-container" :style="{ width: timelineWidth + 'px' }">
                {{-- Video Track --}}
                <div
                    class="vw-track vw-track-video"
                    :class="{ 'is-locked': tracks.video.locked, 'is-muted': tracks.video.muted, 'is-drop-target': isDragging && dropZoneTrack === 'video' }"
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
                            $thumbnail = $storyboard['scenes'][$index]['image'] ?? null;
                        @endphp
                        <div
                            class="vw-clip vw-clip-video"
                            :class="{
                                'is-selected': selectedTrack === 'video' && selectedClip === {{ $index }},
                                'is-hovered': hoveredTrack === 'video' && hoveredClip === {{ $index }},
                                'is-dragging': isDragging && dragTarget?.track === 'video' && dragTarget?.index === {{ $index }},
                                'is-ripple-affected': rippleMode && affectedClips.some(c => c.track === 'video' && c.index === {{ $index }})
                            }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $sceneDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('video', {{ $index }})"
                            @mouseenter="hoveredTrack = 'video'; hoveredClip = {{ $index }}"
                            @mouseleave="hoveredTrack = null; hoveredClip = null"
                            @mousedown.stop="if (!tracks.video.locked && $event.target.closest('.vw-trim-handle') === null) startDrag($event, 'move', { track: 'video', index: {{ $index }} }, {{ $sceneStart }}, {{ $sceneDuration }}, {{ $sceneStart }})"
                        >
                            {{-- Thumbnail Filmstrip --}}
                            <div class="vw-clip-filmstrip">
                                @if($thumbnail)
                                    <div class="vw-filmstrip-thumb" style="background-image: url('{{ $thumbnail }}');"></div>
                                    <div class="vw-filmstrip-thumb" style="background-image: url('{{ $thumbnail }}');"></div>
                                    <div class="vw-filmstrip-thumb" style="background-image: url('{{ $thumbnail }}');"></div>
                                @else
                                    <div class="vw-filmstrip-placeholder">
                                        <span class="vw-scene-num">{{ $index + 1 }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Clip Info Overlay --}}
                            <div class="vw-clip-info">
                                <span class="vw-clip-badge">{{ $index + 1 }}</span>
                                <span class="vw-clip-duration">{{ number_format($sceneDuration, 1) }}s</span>
                            </div>

                            {{-- Lock Indicator --}}
                            <div class="vw-clip-lock-overlay" x-show="tracks.video.locked" x-cloak>
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            </div>

                            {{-- Enhanced Trim Handles --}}
                            <div
                                class="vw-trim-handle vw-trim-left"
                                @mousedown.stop="startDrag($event, 'trim-start', { track: 'video', index: {{ $index }} }, {{ $sceneStart }}, {{ $sceneDuration }}, {{ $sceneStart }})"
                            >
                                <div class="vw-trim-grip">
                                    <div class="vw-trim-line"></div>
                                    <div class="vw-trim-line"></div>
                                </div>
                                <div class="vw-trim-hit-area"></div>
                            </div>
                            <div
                                class="vw-trim-handle vw-trim-right"
                                @mousedown.stop="startDrag($event, 'trim-end', { track: 'video', index: {{ $index }} }, {{ $sceneStart + $sceneDuration }}, {{ $sceneDuration }}, {{ $sceneStart }})"
                            >
                                <div class="vw-trim-grip">
                                    <div class="vw-trim-line"></div>
                                    <div class="vw-trim-line"></div>
                                </div>
                                <div class="vw-trim-hit-area"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Voiceover Track --}}
                <div
                    class="vw-track vw-track-voiceover"
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
                            class="vw-clip vw-clip-audio"
                            :class="{
                                'is-selected': selectedTrack === 'voiceover' && selectedClip === {{ $index }},
                                'is-hovered': hoveredTrack === 'voiceover' && hoveredClip === {{ $index }}
                            }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $voiceoverDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('voiceover', {{ $index }})"
                            @mouseenter="hoveredTrack = 'voiceover'; hoveredClip = {{ $index }}"
                            @mouseleave="hoveredTrack = null; hoveredClip = null"
                        >
                            {{-- Waveform SVG --}}
                            <svg class="vw-waveform-svg" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="waveGradientVoice{{ $index }}" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#06b6d4;stop-opacity:0.9"/>
                                        <stop offset="50%" style="stop-color:#0891b2;stop-opacity:0.7"/>
                                        <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:0.9"/>
                                    </linearGradient>
                                </defs>
                                <path
                                    class="vw-waveform-path"
                                    fill="url(#waveGradientVoice{{ $index }})"
                                    :d="generateWaveformPath($el.parentElement.offsetWidth || 200, $el.parentElement.offsetHeight || 40, {{ $index * 7 }})"
                                ></path>
                            </svg>
                        </div>
                    @endforeach
                </div>

                {{-- Music Track --}}
                <div
                    class="vw-track vw-track-music"
                    x-show="tracks.music.visible"
                    :style="{ height: tracks.music.height + 'px' }"
                >
                    @if($assembly['music']['enabled'] ?? false)
                        <div
                            class="vw-clip vw-clip-audio vw-clip-music"
                            :class="{
                                'is-selected': selectedTrack === 'music' && selectedClip === 0,
                                'is-hovered': hoveredTrack === 'music' && hoveredClip === 0
                            }"
                            :style="{
                                left: '0px',
                                width: timeToPixels(totalDuration) + 'px'
                            }"
                            @click.stop="selectClip('music', 0)"
                            @mouseenter="hoveredTrack = 'music'; hoveredClip = 0"
                            @mouseleave="hoveredTrack = null; hoveredClip = null"
                        >
                            {{-- Waveform SVG --}}
                            <svg class="vw-waveform-svg" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="waveGradientMusic" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#10b981;stop-opacity:0.9"/>
                                        <stop offset="50%" style="stop-color:#059669;stop-opacity:0.7"/>
                                        <stop offset="100%" style="stop-color:#10b981;stop-opacity:0.9"/>
                                    </linearGradient>
                                </defs>
                                <path
                                    class="vw-waveform-path"
                                    fill="url(#waveGradientMusic)"
                                    :d="generateWaveformPath($el.parentElement.offsetWidth || 400, $el.parentElement.offsetHeight || 40, 42)"
                                ></path>
                            </svg>

                            {{-- Music Label --}}
                            <div class="vw-music-label">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                                <span>{{ __('Background Music') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="vw-track-placeholder">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                            <span>{{ __('No music added') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Captions Track --}}
                <div
                    class="vw-track vw-track-captions"
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
                                $captionText = Str::limit($scene['narration'] ?? '', 40);
                            @endphp
                            <div
                                class="vw-clip vw-clip-caption"
                                :class="{
                                    'is-selected': selectedTrack === 'captions' && selectedClip === {{ $index }},
                                    'is-hovered': hoveredTrack === 'captions' && hoveredClip === {{ $index }}
                                }"
                                :style="{
                                    left: timeToPixels({{ $sceneStart }}) + 'px',
                                    width: timeToPixels({{ $captionDuration }}) + 'px'
                                }"
                                @click.stop="selectClip('captions', {{ $index }})"
                                @mouseenter="hoveredTrack = 'captions'; hoveredClip = {{ $index }}"
                                @mouseleave="hoveredTrack = null; hoveredClip = null"
                                title="{{ $scene['narration'] ?? '' }}"
                            >
                                <span class="vw-caption-text">{{ $captionText ?: __('Caption') . ' ' . ($index + 1) }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="vw-track-placeholder">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4H5c-1.11 0-2 .9-2 2v12c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 7H9.5v-.5h-2v3h2V13H11v1c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1z"/></svg>
                            <span>{{ __('Captions disabled') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Playhead Line --}}
                <div
                    class="vw-playhead-line"
                    :class="{ 'is-dragging': isPlayheadDragging }"
                    :style="{ left: timeToPixels(currentTime) + 'px' }"
                ></div>

                {{-- Snap Indicator with Enhanced Visual --}}
                <div
                    class="vw-snap-indicator"
                    x-ref="snapIndicator"
                    x-show="showSnapIndicator"
                    x-cloak
                    :style="{ left: snapIndicatorPosition + 'px' }"
                >
                    <div class="vw-snap-line"></div>
                    <div class="vw-snap-label" x-show="activeSnapPoints.length > 0">
                        <span x-text="activeSnapPoints[0]?.type === 'playhead' ? '{{ __('Playhead') }}' : (activeSnapPoints[0]?.type === 'boundary' ? activeSnapPoints[0]?.label : '{{ __('Clip Edge') }}')"></span>
                    </div>
                </div>

                {{-- Ghost Clip (shown during drag-to-move) --}}
                <div
                    class="vw-ghost-clip"
                    x-show="showGhostClip"
                    x-cloak
                    :class="'vw-ghost-' + ghostClipTrack"
                    :style="{
                        left: ghostClipLeft + 'px',
                        width: ghostClipWidth + 'px',
                        top: ghostClipTrack === 'video' ? '4px' : (ghostClipTrack === 'voiceover' ? (tracks.video.height + 4) + 'px' : '4px'),
                        height: tracks[ghostClipTrack]?.height ? (tracks[ghostClipTrack].height - 8) + 'px' : '62px'
                    }"
                >
                    <div class="vw-ghost-content">
                        <span class="vw-ghost-time" x-text="formatTimeDetailed(pixelsToTime(ghostClipLeft))"></span>
                    </div>
                </div>

                {{-- Original Position Indicator (during move) --}}
                <div
                    class="vw-original-position"
                    x-show="showGhostClip && ghostClipOriginalLeft !== ghostClipLeft"
                    x-cloak
                    :style="{ left: ghostClipOriginalLeft + 'px' }"
                ></div>

                {{-- Trim Preview Indicator --}}
                <div
                    class="vw-trim-preview"
                    x-show="showTrimPreview"
                    x-cloak
                    :class="'vw-trim-preview-' + trimPreviewType"
                    :style="{ left: timeToPixels(trimPreviewTime) + 'px' }"
                >
                    <div class="vw-trim-preview-line"></div>
                    <div class="vw-trim-preview-tooltip">
                        <span x-text="formatTimeDetailed(trimPreviewTime)"></span>
                    </div>
                </div>

                {{-- Ripple Indicator Line --}}
                <div
                    class="vw-ripple-indicator"
                    x-show="rippleMode && isDragging && affectedClips.length > 0"
                    x-cloak
                >
                    <div class="vw-ripple-line"></div>
                    <div class="vw-ripple-label">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 12h4l3-9 4 18 3-9h6"/></svg>
                        <span x-text="affectedClips.length + ' {{ __('clips will shift') }}'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Clip Inspector Panel --}}
    <div class="vw-clip-inspector" x-show="selectedClip !== null" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="vw-inspector-header">
            <div class="vw-inspector-title">
                <span class="vw-inspector-icon" :style="{ background: selectedTrack ? tracks[selectedTrack]?.color : '#666' }"></span>
                <span x-text="selectedTrack ? tracks[selectedTrack]?.label : ''"></span>
                <span class="vw-inspector-clip-num">{{ __('Clip') }} #<span x-text="selectedClip !== null ? selectedClip + 1 : ''"></span></span>
            </div>
            <button type="button" @click="deselectAll()" class="vw-inspector-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="vw-inspector-body">
            <div class="vw-inspector-row">
                <span class="vw-inspector-label">{{ __('Start Time') }}</span>
                <span class="vw-inspector-value" x-text="formatTime(0)">0:00</span>
            </div>
            <div class="vw-inspector-row">
                <span class="vw-inspector-label">{{ __('Duration') }}</span>
                <span class="vw-inspector-value">5.0s</span>
            </div>
            <div class="vw-inspector-row">
                <span class="vw-inspector-label">{{ __('End Time') }}</span>
                <span class="vw-inspector-value">0:05</span>
            </div>
        </div>
        <div class="vw-inspector-actions">
            <button type="button" class="vw-inspector-action" title="{{ __('Split at Playhead') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="2" x2="12" y2="22"/>
                </svg>
            </button>
            <button type="button" class="vw-inspector-action" title="{{ __('Duplicate') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>
            <button type="button" class="vw-inspector-action vw-action-danger" title="{{ __('Delete') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   PROFESSIONAL TIMELINE - Phase 1 Redesign
   Modern glassmorphism design with enhanced visuals
   ========================================================================== */

.vw-pro-timeline {
    display: flex;
    flex-direction: column;
    background: linear-gradient(180deg, rgba(15, 15, 25, 0.98) 0%, rgba(10, 10, 18, 0.99) 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    min-height: 280px;
}

/* ==========================================================================
   TOOLBAR
   ========================================================================== */

.vw-timeline-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    gap: 1rem;
    flex-shrink: 0;
}

.vw-toolbar-section {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vw-toolbar-divider {
    width: 1px;
    height: 24px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0 0.25rem;
}

/* Track Toggles */
.vw-track-toggles {
    display: flex;
    gap: 0.25rem;
}

.vw-track-toggle {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.6rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.4rem;
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.vw-track-toggle:hover {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.7);
}

.vw-track-toggle.is-active {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.9);
}

.vw-toggle-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid;
    transition: all 0.2s;
}

.vw-toggle-label {
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Tool Buttons */
.vw-tool-group {
    display: flex;
    gap: 0.2rem;
}

.vw-tool-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    width: 32px;
    height: 32px;
    padding: 0;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.4rem;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.2s ease;
}

.vw-tool-btn svg {
    width: 16px;
    height: 16px;
}

.vw-tool-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
    color: white;
    border-color: rgba(255, 255, 255, 0.15);
}

.vw-tool-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.vw-tool-btn.is-active {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
    color: #a78bfa;
}

.vw-snap-btn {
    width: auto;
    padding: 0 0.6rem;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Zoom Control */
.vw-zoom-control {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.vw-zoom-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    border-radius: 0.3rem;
    transition: all 0.2s;
}

.vw-zoom-btn svg {
    width: 16px;
    height: 16px;
}

.vw-zoom-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-zoom-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.vw-zoom-fit {
    margin-left: 0.25rem;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    padding-left: 0.35rem;
    border-radius: 0;
}

.vw-zoom-slider-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vw-zoom-slider {
    width: 80px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    cursor: pointer;
}

.vw-zoom-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 12px;
    height: 12px;
    background: #8b5cf6;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.15s;
}

.vw-zoom-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

.vw-zoom-value {
    min-width: 42px;
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.5);
    text-align: center;
}

/* Time Indicator */
.vw-time-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.75rem;
    background: rgba(0, 0, 0, 0.4);
    border-radius: 0.4rem;
    font-family: 'SF Mono', Monaco, 'Consolas', monospace;
    font-size: 0.8rem;
}

.vw-time-current {
    color: #8b5cf6;
    font-weight: 700;
}

.vw-time-sep {
    color: rgba(255, 255, 255, 0.3);
}

.vw-time-total {
    color: rgba(255, 255, 255, 0.5);
}

/* ==========================================================================
   TIMELINE BODY
   ========================================================================== */

.vw-timeline-body {
    display: flex;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* Track Headers */
.vw-track-headers {
    width: 120px;
    min-width: 120px;
    background: rgba(0, 0, 0, 0.3);
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.vw-ruler-header {
    height: 32px;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(0, 0, 0, 0.2);
}

.vw-clock-icon {
    width: 16px;
    height: 16px;
    color: rgba(255, 255, 255, 0.3);
}

.vw-track-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    position: relative;
    background: rgba(0, 0, 0, 0.1);
}

.vw-header-color-bar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--track-color);
    opacity: 0.8;
}

.vw-header-content {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex: 1;
    padding-left: 0.25rem;
}

.vw-header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    color: var(--track-color);
    opacity: 0.8;
}

.vw-header-icon svg {
    width: 14px;
    height: 14px;
}

.vw-header-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    white-space: nowrap;
}

.vw-header-controls {
    display: flex;
    gap: 0.15rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.vw-track-header:hover .vw-header-controls {
    opacity: 1;
}

.vw-header-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.4);
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-header-btn svg {
    width: 12px;
    height: 12px;
}

.vw-header-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

/* ==========================================================================
   SCROLLABLE TIMELINE
   ========================================================================== */

.vw-timeline-scroll {
    flex: 1;
    overflow-x: auto;
    overflow-y: hidden;
    position: relative;
}

/* Custom Scrollbar */
.vw-timeline-scroll::-webkit-scrollbar {
    height: 10px;
}

.vw-timeline-scroll::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.3);
}

.vw-timeline-scroll::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 5px;
    border: 2px solid rgba(0, 0, 0, 0.3);
}

.vw-timeline-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}

/* ==========================================================================
   TIME RULER
   ========================================================================== */

.vw-time-ruler {
    height: 32px;
    min-height: 32px;
    background: linear-gradient(180deg, rgba(30, 30, 50, 0.8) 0%, rgba(20, 20, 35, 0.9) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    cursor: pointer;
}

.vw-ruler-pattern {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.03) 0px,
        rgba(255, 255, 255, 0.03) 1px,
        transparent 1px,
        transparent 60px
    );
}

.vw-ruler-mark {
    position: absolute;
    bottom: 0;
    width: 1px;
    height: 8px;
    background: rgba(255, 255, 255, 0.15);
}

.vw-ruler-mark.is-major {
    height: 14px;
    background: rgba(255, 255, 255, 0.3);
}

.vw-ruler-mark.is-sub {
    height: 5px;
    background: rgba(255, 255, 255, 0.08);
}

.vw-ruler-label {
    position: absolute;
    bottom: 16px;
    left: 4px;
    font-size: 0.65rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.5);
    white-space: nowrap;
    font-family: 'SF Mono', Monaco, monospace;
}

/* Playhead Top Handle */
.vw-playhead-top {
    position: absolute;
    top: 0;
    transform: translateX(-6px);
    cursor: grab;
    z-index: 100;
}

.vw-playhead-top:active {
    cursor: grabbing;
}

.vw-playhead-handle {
    width: 12px;
    height: 16px;
    color: #ef4444;
    filter: drop-shadow(0 2px 4px rgba(239, 68, 68, 0.5));
    transition: transform 0.15s, filter 0.15s;
}

.vw-playhead-top:hover .vw-playhead-handle,
.vw-playhead-top.is-dragging .vw-playhead-handle {
    transform: scale(1.15);
    filter: drop-shadow(0 2px 8px rgba(239, 68, 68, 0.8));
}

.vw-playhead-handle svg {
    width: 12px;
    height: 16px;
}

.vw-playhead-tooltip {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 4px;
    padding: 0.3rem 0.5rem;
    background: rgba(0, 0, 0, 0.9);
    border: 1px solid rgba(239, 68, 68, 0.5);
    border-radius: 0.3rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    font-family: 'SF Mono', Monaco, monospace;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

/* ==========================================================================
   TRACKS CONTAINER
   ========================================================================== */

.vw-tracks-container {
    position: relative;
    min-height: 100%;
}

.vw-track {
    position: relative;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    background-size: 60px 100%;
    background-image: repeating-linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.02) 0px,
        rgba(255, 255, 255, 0.02) 1px,
        transparent 1px,
        transparent 60px
    );
}

.vw-track-video {
    background-color: rgba(139, 92, 246, 0.03);
}

.vw-track-voiceover {
    background-color: rgba(6, 182, 212, 0.03);
}

.vw-track-music {
    background-color: rgba(16, 185, 129, 0.03);
}

.vw-track-captions {
    background-color: rgba(245, 158, 11, 0.03);
}

.vw-track-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 100%;
    color: rgba(255, 255, 255, 0.25);
    font-size: 0.75rem;
}

.vw-track-placeholder svg {
    width: 16px;
    height: 16px;
    opacity: 0.5;
}

/* ==========================================================================
   CLIPS - GENERAL
   ========================================================================== */

.vw-clip {
    position: absolute;
    top: 4px;
    height: calc(100% - 8px);
    border-radius: 0.4rem;
    overflow: hidden;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.15s;
}

.vw-clip:hover {
    z-index: 10;
}

.vw-clip.is-hovered {
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.vw-clip.is-selected {
    box-shadow:
        0 0 0 2px #fff,
        0 0 0 4px rgba(139, 92, 246, 0.6),
        0 4px 20px rgba(139, 92, 246, 0.3);
    z-index: 20;
}

/* ==========================================================================
   VIDEO CLIPS - Filmstrip Style
   ========================================================================== */

.vw-clip-video {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(139, 92, 246, 0.05) 100%);
    border: 1px solid rgba(139, 92, 246, 0.4);
}

.vw-clip-video.is-selected {
    border-color: rgba(139, 92, 246, 0.8);
}

.vw-clip-filmstrip {
    display: flex;
    height: 100%;
    gap: 1px;
    background: rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.vw-filmstrip-thumb {
    flex: 1;
    min-width: 40px;
    background-size: cover;
    background-position: center;
    border-right: 1px solid rgba(0, 0, 0, 0.3);
}

.vw-filmstrip-thumb:last-child {
    border-right: none;
}

.vw-filmstrip-placeholder {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
}

.vw-scene-num {
    font-size: 1.5rem;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.3);
}

.vw-clip-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.25rem 0.4rem;
    background: linear-gradient(0deg, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
}

.vw-clip-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: rgba(139, 92, 246, 0.9);
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-weight: 700;
    color: white;
}

.vw-clip-duration {
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    font-family: 'SF Mono', Monaco, monospace;
}

/* ==========================================================================
   AUDIO CLIPS - Waveform Style
   ========================================================================== */

.vw-clip-audio {
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(6, 182, 212, 0.05) 100%);
    border: 1px solid rgba(6, 182, 212, 0.4);
}

.vw-clip-audio.is-selected {
    border-color: rgba(6, 182, 212, 0.8);
}

.vw-clip-music {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
    border: 1px solid rgba(16, 185, 129, 0.4);
}

.vw-clip-music.is-selected {
    border-color: rgba(16, 185, 129, 0.8);
}

.vw-waveform-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.vw-waveform-path {
    transition: opacity 0.2s;
}

.vw-clip-audio:hover .vw-waveform-path {
    opacity: 0.9;
}

.vw-music-label {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.6rem;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    border-radius: 1rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    white-space: nowrap;
}

.vw-music-label svg {
    width: 12px;
    height: 12px;
    color: #10b981;
}

/* ==========================================================================
   CAPTION CLIPS
   ========================================================================== */

.vw-clip-caption {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
    border: 1px solid rgba(245, 158, 11, 0.4);
    display: flex;
    align-items: center;
}

.vw-clip-caption.is-selected {
    border-color: rgba(245, 158, 11, 0.8);
}

.vw-caption-text {
    padding: 0 0.5rem;
    font-size: 0.65rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================================
   TRIM HANDLES
   ========================================================================== */

.vw-trim-handle {
    position: absolute;
    top: 0;
    width: 12px;
    height: 100%;
    cursor: ew-resize;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 5;
}

.vw-clip:hover .vw-trim-handle,
.vw-clip.is-selected .vw-trim-handle {
    opacity: 1;
}

.vw-trim-left {
    left: 0;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
    border-radius: 0.4rem 0 0 0.4rem;
}

.vw-trim-right {
    right: 0;
    background: linear-gradient(-90deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
    border-radius: 0 0.4rem 0.4rem 0;
}

.vw-trim-grip {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 4px;
    height: 20px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 2px;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.3);
}

.vw-trim-handle:hover .vw-trim-grip {
    background: white;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
}

/* ==========================================================================
   PLAYHEAD LINE
   ========================================================================== */

.vw-playhead-line {
    position: absolute;
    top: 0;
    width: 2px;
    height: 100%;
    background: #ef4444;
    box-shadow:
        0 0 10px rgba(239, 68, 68, 0.5),
        0 0 20px rgba(239, 68, 68, 0.3);
    pointer-events: none;
    z-index: 50;
}

.vw-playhead-line.is-dragging {
    box-shadow:
        0 0 15px rgba(239, 68, 68, 0.7),
        0 0 30px rgba(239, 68, 68, 0.5);
}

/* ==========================================================================
   SNAP INDICATOR
   ========================================================================== */

.vw-snap-indicator {
    position: absolute;
    top: 0;
    width: 2px;
    height: 100%;
    background: #8b5cf6;
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.8);
    pointer-events: none;
    z-index: 45;
    animation: snapPulse 0.3s ease-out;
}

@keyframes snapPulse {
    0% { opacity: 0; transform: scaleY(0.5); }
    50% { opacity: 1; transform: scaleY(1.1); }
    100% { opacity: 1; transform: scaleY(1); }
}

/* ==========================================================================
   CLIP INSPECTOR
   ========================================================================== */

.vw-clip-inspector {
    position: absolute;
    bottom: calc(100% + 8px);
    right: 12px;
    width: 220px;
    background: rgba(20, 20, 35, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
    z-index: 200;
    overflow: hidden;
}

.vw-inspector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.75rem;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.vw-inspector-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.vw-inspector-icon {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.vw-inspector-clip-num {
    color: rgba(255, 255, 255, 0.5);
    font-weight: 400;
}

.vw-inspector-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-radius: 0.3rem;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-inspector-close svg {
    width: 14px;
    height: 14px;
}

.vw-inspector-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-inspector-body {
    padding: 0.6rem 0.75rem;
}

.vw-inspector-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.3rem 0;
}

.vw-inspector-label {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.5);
}

.vw-inspector-value {
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-inspector-actions {
    display: flex;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.vw-inspector-action {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.4rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-inspector-action svg {
    width: 14px;
    height: 14px;
}

.vw-inspector-action:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-inspector-action.vw-action-danger:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.4);
    color: #ef4444;
}

/* ==========================================================================
   UTILITIES
   ========================================================================== */

[x-cloak] {
    display: none !important;
}

/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media (max-width: 768px) {
    .vw-timeline-toolbar {
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.5rem;
    }

    .vw-toolbar-section {
        flex: 1 1 auto;
        justify-content: center;
    }

    .vw-toolbar-center {
        order: 3;
        width: 100%;
    }

    .vw-track-headers {
        width: 80px;
        min-width: 80px;
    }

    .vw-header-label {
        display: none;
    }

    .vw-zoom-slider {
        width: 60px;
    }

    .vw-clip-inspector {
        width: 180px;
        right: 8px;
    }
}

/* ==========================================================================
   PHASE 2: SNAP CONTROL DROPDOWN
   ========================================================================== */

.vw-snap-control {
    display: flex;
    position: relative;
}

.vw-snap-dropdown-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 32px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-left: none;
    border-radius: 0 0.4rem 0.4rem 0;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s;
}

.vw-snap-dropdown-btn svg {
    width: 14px;
    height: 14px;
}

.vw-snap-dropdown-btn:hover,
.vw-snap-dropdown-btn.is-active {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
}

.vw-snap-btn {
    border-radius: 0.4rem 0 0 0.4rem !important;
}

.vw-snap-menu {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 4px;
    min-width: 140px;
    background: rgba(20, 20, 35, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    padding: 0.35rem;
    z-index: 100;
}

.vw-snap-menu-label {
    padding: 0.4rem 0.6rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vw-snap-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.5rem 0.6rem;
    background: transparent;
    border: none;
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-snap-option:hover {
    background: rgba(255, 255, 255, 0.1);
}

.vw-snap-option.is-active {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.vw-snap-option svg {
    width: 14px;
    height: 14px;
}

/* ==========================================================================
   PHASE 2: RIPPLE MODE BUTTON
   ========================================================================== */

.vw-ripple-btn {
    width: auto !important;
    padding: 0 0.6rem !important;
    font-size: 0.7rem;
    font-weight: 600;
}

.vw-ripple-btn.is-active {
    background: rgba(236, 72, 153, 0.2);
    border-color: rgba(236, 72, 153, 0.4);
    color: #f472b6;
}

/* ==========================================================================
   PHASE 2: ENHANCED TRIM HANDLES
   ========================================================================== */

.vw-trim-handle {
    position: absolute;
    top: 0;
    width: 16px;
    height: 100%;
    cursor: ew-resize;
    z-index: 15;
    opacity: 0;
    transition: opacity 0.2s;
}

.vw-clip:hover .vw-trim-handle,
.vw-clip.is-selected .vw-trim-handle {
    opacity: 1;
}

.vw-trim-left {
    left: -4px;
}

.vw-trim-right {
    right: -4px;
}

.vw-trim-grip {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    background: linear-gradient(180deg, rgba(139, 92, 246, 0.9) 0%, rgba(139, 92, 246, 0.7) 100%);
    border-radius: 2px;
    transition: all 0.15s;
}

.vw-trim-left .vw-trim-grip {
    left: 4px;
    border-radius: 4px 0 0 4px;
}

.vw-trim-right .vw-trim-grip {
    right: 4px;
    border-radius: 0 4px 4px 0;
}

.vw-trim-line {
    width: 2px;
    height: 12px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 1px;
}

.vw-trim-handle:hover .vw-trim-grip {
    background: linear-gradient(180deg, #a78bfa 0%, #8b5cf6 100%);
    box-shadow: 0 0 12px rgba(139, 92, 246, 0.6);
}

.vw-trim-handle:hover .vw-trim-line {
    background: white;
}

/* Extended hit area for easier touch targeting */
.vw-trim-hit-area {
    position: absolute;
    top: -10px;
    bottom: -10px;
    left: -8px;
    right: -8px;
}

/* ==========================================================================
   PHASE 2: GHOST CLIP
   ========================================================================== */

.vw-ghost-clip {
    position: absolute;
    border-radius: 0.4rem;
    background: rgba(139, 92, 246, 0.3);
    border: 2px dashed rgba(139, 92, 246, 0.8);
    pointer-events: none;
    z-index: 100;
    animation: ghostPulse 0.8s ease-in-out infinite;
}

@keyframes ghostPulse {
    0%, 100% { opacity: 0.8; }
    50% { opacity: 0.5; }
}

.vw-ghost-video { background: rgba(139, 92, 246, 0.3); border-color: rgba(139, 92, 246, 0.8); }
.vw-ghost-voiceover { background: rgba(6, 182, 212, 0.3); border-color: rgba(6, 182, 212, 0.8); }
.vw-ghost-music { background: rgba(16, 185, 129, 0.3); border-color: rgba(16, 185, 129, 0.8); }
.vw-ghost-captions { background: rgba(245, 158, 11, 0.3); border-color: rgba(245, 158, 11, 0.8); }

.vw-ghost-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: white;
    font-family: 'SF Mono', Monaco, monospace;
}

/* Original position indicator */
.vw-original-position {
    position: absolute;
    top: 0;
    width: 2px;
    height: 100%;
    background: rgba(255, 255, 255, 0.3);
    pointer-events: none;
    z-index: 95;
}

.vw-original-position::before {
    content: '';
    position: absolute;
    top: 0;
    left: -4px;
    width: 10px;
    height: 10px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
}

/* ==========================================================================
   PHASE 2: TRIM PREVIEW
   ========================================================================== */

.vw-trim-preview {
    position: absolute;
    top: 0;
    height: 100%;
    pointer-events: none;
    z-index: 90;
}

.vw-trim-preview-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 2px;
    height: 100%;
    background: #22c55e;
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
}

.vw-trim-preview-trim-start .vw-trim-preview-line {
    background: #f59e0b;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.6);
}

.vw-trim-preview-trim-end .vw-trim-preview-line {
    background: #22c55e;
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
}

.vw-trim-preview-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 4px;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.9);
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: white;
    font-family: 'SF Mono', Monaco, monospace;
    white-space: nowrap;
}

/* ==========================================================================
   PHASE 2: SNAP INDICATOR ENHANCED
   ========================================================================== */

.vw-snap-indicator {
    position: absolute;
    top: 0;
    height: 100%;
    pointer-events: none;
    z-index: 85;
}

.vw-snap-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 2px;
    height: 100%;
    background: #8b5cf6;
    box-shadow: 0 0 15px rgba(139, 92, 246, 0.8);
}

.vw-snap-indicator.snap-pulse .vw-snap-line {
    animation: snapLinePulse 0.3s ease-out;
}

@keyframes snapLinePulse {
    0% { transform: scaleY(0.5); opacity: 0; box-shadow: 0 0 30px rgba(139, 92, 246, 1); }
    50% { transform: scaleY(1.1); opacity: 1; }
    100% { transform: scaleY(1); opacity: 1; box-shadow: 0 0 15px rgba(139, 92, 246, 0.8); }
}

.vw-snap-label {
    position: absolute;
    top: -24px;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.2rem 0.4rem;
    background: #8b5cf6;
    border-radius: 0.25rem;
    font-size: 0.6rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.5);
}

/* ==========================================================================
   PHASE 2: RIPPLE INDICATOR
   ========================================================================== */

.vw-ripple-indicator {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: none;
    z-index: 200;
}

.vw-ripple-label {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.6rem;
    background: rgba(236, 72, 153, 0.9);
    border-radius: 1rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: white;
    box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
    animation: rippleLabelPulse 1s ease-in-out infinite;
}

.vw-ripple-label svg {
    width: 14px;
    height: 14px;
}

@keyframes rippleLabelPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* ==========================================================================
   PHASE 2: TRACK STATES
   ========================================================================== */

.vw-track.is-locked {
    opacity: 0.6;
}

.vw-track.is-locked::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(255, 255, 255, 0.03) 10px,
        rgba(255, 255, 255, 0.03) 20px
    );
    pointer-events: none;
}

.vw-track.is-muted {
    filter: grayscale(0.5);
}

.vw-track.is-drop-target {
    background-color: rgba(139, 92, 246, 0.1) !important;
    box-shadow: inset 0 0 20px rgba(139, 92, 246, 0.2);
}

/* ==========================================================================
   PHASE 2: CLIP STATES
   ========================================================================== */

.vw-clip.is-dragging {
    opacity: 0.5;
    transform: scale(0.98);
}

.vw-clip.is-ripple-affected {
    box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.6);
    animation: rippleAffectedPulse 0.5s ease-in-out infinite;
}

@keyframes rippleAffectedPulse {
    0%, 100% { box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.6); }
    50% { box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.3); }
}

.vw-clip-lock-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 0.4rem;
    pointer-events: none;
}

.vw-clip-lock-overlay svg {
    width: 20px;
    height: 20px;
    color: rgba(255, 255, 255, 0.5);
}

/* ==========================================================================
   PHASE 2: HEADER BUTTON STATES
   ========================================================================== */

.vw-header-btn.is-active {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

/* Track header locked indicator */
.vw-track-header[style*="locked"] .vw-header-color-bar {
    background: repeating-linear-gradient(
        45deg,
        var(--track-color),
        var(--track-color) 4px,
        rgba(0, 0, 0, 0.3) 4px,
        rgba(0, 0, 0, 0.3) 8px
    );
}
</style>
