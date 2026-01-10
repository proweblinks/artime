{{--
    Professional Timeline Component - Phase 1 & 2 Redesign
    Modern glassmorphism design with advanced interactions

    ========================================
    EVENT CONTRACTS (Phase B Documentation)
    ========================================

    This component dispatches the following Alpine.js events.
    Parent components should listen for these to handle timeline actions.

    CLIP OPERATIONS:
    ----------------
    @event clip-moved
    @param {string} track - Track ID ('video', 'voiceover', 'music', 'captions')
    @param {number} index - Clip index within the track
    @param {number} newTime - New start time in seconds
    @param {boolean} ripple - Whether ripple mode was active

    @event clip-trimmed
    @param {string} track - Track ID
    @param {number} index - Clip index
    @param {number} startDelta - (trim-start) Change in start position
    @param {number} newDuration - (trim-end) New clip duration in seconds

    @event clip-selected
    @param {string} track - Track ID
    @param {number} clipIndex - Selected clip index
    @param {boolean} multi - Whether multiple clips are selected

    @event split-clip
    @param {number} time - Split point in seconds
    @param {string} track - Track to split on
    @param {number} clipIndex - (optional) Specific clip to split

    @event delete-clips
    @param {Array<{track: string, index: number}>} clips - Clips to delete
    @param {boolean} ripple - Whether to use ripple delete

    @event duplicate-clips
    @param {Array<{track: string, index: number}>} clips - Clips to duplicate

    @event paste-clips
    @param {Array} clips - Clipboard contents
    @param {string} operation - 'copy' or 'cut'
    @param {string} targetTrack - Destination track
    @param {number} targetTime - Paste position in seconds

    TRACK OPERATIONS:
    -----------------
    @event track-muted
    @param {string} track - Track ID
    @param {boolean} muted - New mute state

    @event track-solo-changed
    @param {string} track - Track ID
    @param {boolean} solo - New solo state

    @event track-volume-changed
    @param {string} track - Track ID
    @param {number} volume - Volume level (0.0 to 1.0)

    PLAYBACK CONTROL:
    -----------------
    @event pause-preview
    (no parameters) - Pause video preview

    @event jkl-playback
    @param {number} speed - Playback speed multiplier (-8 to 8, 0 = pause)

    @event scrub-preview-start
    (no parameters) - Audio scrubbing started

    @event scrub-preview-update
    @param {number} time - Current scrub position in seconds

    @event scrub-preview-end
    (no parameters) - Audio scrubbing ended

    MARKERS:
    --------
    @event marker-added
    @param {Object} marker - { id, time, label, color, type }

    @event marker-deleted
    @param {string} markerId - ID of deleted marker

    @event marker-updated
    @param {Object} marker - Updated marker object

    TRANSITIONS:
    ------------
    @event transition-applied
    @param {string} track - Track ID
    @param {number} clipIndex - Clip index
    @param {string} type - Transition type ('fade', 'dissolve', etc.)
    @param {Object} config - Transition configuration

    REGIONS & EXPORT:
    -----------------
    @event loop-region
    @param {number} start - Loop start time in seconds
    @param {number} end - Loop end time in seconds

    @event export-region
    @param {number} start - Export start time in seconds
    @param {number} end - Export end time in seconds

    UTILITY:
    --------
    @event show-notification
    @param {string} message - Notification text
    @param {string} type - (optional) 'error', 'success', 'warning', 'info'

    @event waveform-generated
    @param {Object} data - Waveform data from web worker

    LIVEWIRE METHODS CALLED:
    ------------------------
    - $wire.call('timelineUndo') - Undo last timeline action
    - $wire.call('timelineRedo') - Redo last undone action

    ========================================
    SECURITY MEASURES (Phase C Hardening)
    ========================================

    XSS Prevention:
    - All user content rendered via x-text (not x-html) for automatic escaping
    - No innerHTML, insertAdjacentHTML, or document.write usage
    - No eval() or new Function() dynamic code execution
    - All Blade output uses {{ }} (escaped), no {!! !!} (unescaped)

    Input Validation:
    - Time values clamped to 0-totalDuration range
    - Numeric inputs validated with isNaN checks
    - Marker names limited to 100 characters
    - Volume values clamped to 0-100 range

    Prototype Pollution Prevention:
    - updateMarker() uses allowlist for object keys
    - Only 'time', 'name', 'color', 'notes', 'type' keys accepted

    Error Handling:
    - Livewire calls wrapped with .catch() for graceful degradation
    - Invalid inputs silently corrected rather than throwing errors

    ========================================
    ACCESSIBILITY (Phase D Compliance)
    ========================================

    ARIA Support:
    - role="application" on main container with aria-label
    - role="slider" on playhead with aria-valuenow/min/max/text
    - role="radiogroup" on tool selector with aria-checked
    - aria-pressed on toggle buttons (Snap, Ripple)
    - aria-hidden on decorative SVG icons

    Screen Reader Support:
    - Live region (aria-live="polite") for dynamic announcements
    - Hidden instructions describing keyboard navigation
    - announceToScreenReader() method for action feedback
    - Announcements for: selection, deletion, undo/redo, markers

    Keyboard Navigation:
    - Full keyboard support for all timeline operations
    - Arrow keys for playhead navigation (when focused)
    - Home/End to jump to start/end
    - Tab navigation through interactive elements

    Focus Management:
    - High-contrast focus-visible rings (purple #8b5cf6)
    - Different focus colors for different element types
    - Focus rings respect OS-level preferences

    Motion Preferences:
    - @media (prefers-reduced-motion) disables animations

    ========================================
    TESTING INFRASTRUCTURE (Phase E)
    ========================================

    E2E Testing Support:
    - data-testid attributes on key elements:
      - timeline-editor: Main container
      - timeline-toolbar: Central toolbar
      - playhead: Timeline playhead/scrubber
      - btn-undo, btn-redo: Undo/Redo buttons
      - btn-delete, btn-copy, btn-paste: Clipboard actions
      - btn-ripple: Ripple mode toggle
      - btn-tool-select, btn-tool-split: Tool selection
      - tool-selector: Tool radiogroup

    JavaScript Testing API:
    - window.__timelineTestAPI available in test environments
    - Activated when: window.Cypress, window.__TESTING__, or [data-test-mode] present
    - API methods:
      - getState(): Returns current component state
      - actions.seek(time): Seek to time
      - actions.selectClip(track, index): Select clip
      - actions.deleteSelected(): Delete selection
      - actions.undo() / actions.redo(): History navigation
      - actions.setTool(tool): Set active tool
      - actions.addMarker(time, color, name): Add marker
      - getElements(): Get DOM element references

    Test Framework Compatibility:
    - Cypress: Automatic detection via window.Cypress
    - Playwright: Set window.__TESTING__ = true before navigation
    - Laravel Dusk: Add data-test-mode attribute to body

    Example Test (Cypress):
    ```javascript
    cy.window().then(win => {
        const api = win.__timelineTestAPI;
        const state = api.getState();
        expect(state.currentTime).to.equal(0);
        api.actions.seek(5);
        expect(api.getState().currentTime).to.equal(5);
    });
    ```

--}}

<div
    class="vw-pro-timeline"
    role="application"
    aria-label="{{ __('Video Timeline Editor') }}"
    aria-describedby="timeline-instructions"
    data-testid="timeline-editor"
    x-data="{
        // Synced from parent previewController
        currentTime: 0,
        totalDuration: {{ $this->getTotalDuration() ?? 0 }},
        frameRate: 30, // Default frame rate (can be 24, 25, 30, 60, etc.)

        // ===== Reactive Scene Data (Phase A Fix) =====
        // Pre-computed scene metadata for reactive JS access
        // Must match getPreviewScenes() logic: visualDuration -> duration -> default 8
        scenesData: @js(collect($script['scenes'] ?? [])->map(function($scene, $index) use ($storyboard, $script) {
            $start = 0;
            for ($i = 0; $i < $index; $i++) {
                $start += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
            }
            $duration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
            return [
                'index' => $index,
                'start' => $start,
                'duration' => $duration,
                'end' => $start + $duration,
                'thumbnail' => $storyboard['scenes'][$index]['image'] ?? null,
                'narration' => $scene['narration'] ?? '',
            ];
        })->values()->toArray()),

        // Helper to get scene count
        get sceneCount() {
            return this.scenesData.length;
        },

        // Timeline state
        zoom: 1,
        zoomLevels: [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2, 3, 4],
        scrollLeft: 0,

        // Playhead dragging
        isPlayheadDragging: false,
        playheadTooltipTime: 0,

        // ===== Phase 4: Enhanced Track Configuration =====
        tracks: {
            video: {
                id: 'video',
                type: 'video',
                visible: true,
                height: 70,
                defaultHeight: 70,
                minHeight: 40,
                maxHeight: 150,
                color: '#8b5cf6',
                label: '{{ __("Video") }}',
                shortLabel: 'V1',
                icon: 'film',
                locked: false,
                muted: false,
                solo: false,
                collapsed: false,
                volume: 100,
                order: 0
            },
            voiceover: {
                id: 'voiceover',
                type: 'audio',
                visible: true,
                height: 50,
                defaultHeight: 50,
                minHeight: 30,
                maxHeight: 120,
                color: '#06b6d4',
                label: '{{ __("Voiceover") }}',
                shortLabel: 'A1',
                icon: 'mic',
                locked: false,
                muted: false,
                solo: false,
                collapsed: false,
                volume: 100,
                order: 1
            },
            music: {
                id: 'music',
                type: 'audio',
                visible: true,
                height: 50,
                defaultHeight: 50,
                minHeight: 30,
                maxHeight: 120,
                color: '#10b981',
                label: '{{ __("Music") }}',
                shortLabel: 'A2',
                icon: 'music',
                locked: false,
                muted: false,
                solo: false,
                collapsed: false,
                volume: 30,
                order: 2
            },
            captions: {
                id: 'captions',
                type: 'text',
                visible: true,
                height: 40,
                defaultHeight: 40,
                minHeight: 25,
                maxHeight: 80,
                color: '#f59e0b',
                label: '{{ __("Captions") }}',
                shortLabel: 'T1',
                icon: 'text',
                locked: false,
                muted: false,
                solo: false,
                collapsed: false,
                volume: 100,
                order: 3
            }
        },

        // ===== Phase 4: Track Management State =====
        trackOrder: ['video', 'voiceover', 'music', 'captions'],
        isResizingTrack: false,
        resizeTrackId: null,
        resizeStartY: 0,
        resizeStartHeight: 0,
        isDraggingTrack: false,
        dragTrackId: null,
        dragTrackStartY: 0,
        dragTrackTargetIndex: null,
        showTrackMenu: null,
        expandedHeaders: true,

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

        // ===== Phase 3: Split/Cut Tool =====
        currentTool: 'select', // 'select', 'split', 'trim'
        splitCursorPosition: null,
        showSplitCursor: false,

        // ===== Phase 3: Multi-Selection =====
        selectedClips: [], // Array of {track, index} objects
        isMultiSelecting: false,
        marqueeStart: null,
        marqueeEnd: null,
        showMarquee: false,
        lastSelectedIndex: null,

        // ===== Phase 3: Clipboard =====
        clipboard: [],
        clipboardOperation: null, // 'cut' or 'copy'

        // ===== Phase 3: Context Menu =====
        showContextMenu: false,
        contextMenuX: 0,
        contextMenuY: 0,
        contextMenuTarget: null,

        // ===== Phase 3: In/Out Points & JKL =====
        inPoint: null,
        outPoint: null,
        jklSpeed: 0, // -4, -2, -1, 0, 1, 2, 4 for JKL playback
        jklInterval: null,

        // ===== Phase 3: Keyboard Shortcuts Modal =====
        showShortcutsModal: false,

        // ===== Phase 5: Navigation & Zoom =====
        // Enhanced Zoom
        zoomPresets: [
            { label: '25%', value: 0.25 },
            { label: '50%', value: 0.5 },
            { label: '75%', value: 0.75 },
            { label: '100%', value: 1 },
            { label: '150%', value: 1.5 },
            { label: '200%', value: 2 },
            { label: '400%', value: 4 }
        ],
        showZoomMenu: false,
        isPinchZooming: false,
        pinchStartZoom: 1,
        pinchStartDistance: 0,
        zoomFocusPoint: null,

        // Timeline Minimap
        showMinimap: true,
        minimapHeight: 40,
        minimapDragging: false,
        minimapDragStartX: 0,
        minimapDragStartScroll: 0,
        minimapViewportWidth: 0,
        minimapViewportLeft: 0,

        // Scrubbing
        isScrubbing: false,
        scrubStartX: 0,
        scrubStartTime: 0,
        audioScrubEnabled: false,
        scrubPreviewActive: false,

        // Shuttle Control (enhanced)
        shuttleSpeed: 0,
        shuttleRateDisplay: '',

        // ===== Phase 6: Markers & Chapters =====
        markers: [],
        markerColors: [
            { name: 'Red', value: '#ef4444' },
            { name: 'Orange', value: '#f97316' },
            { name: 'Yellow', value: '#eab308' },
            { name: 'Green', value: '#22c55e' },
            { name: 'Blue', value: '#3b82f6' },
            { name: 'Purple', value: '#8b5cf6' },
            { name: 'Pink', value: '#ec4899' }
        ],
        selectedMarker: null,
        showMarkerPanel: false,
        editingMarker: null,
        showMarkerMenu: false,
        markerMenuX: 0,
        markerMenuY: 0,

        // ===== Phase 6: Keyframes =====
        showKeyframes: true,
        selectedKeyframe: null,
        keyframePreviewClip: null,
        easingTypes: [
            { name: 'Linear', value: 'linear', icon: '/' },
            { name: 'Ease In', value: 'ease-in', icon: '⌒' },
            { name: 'Ease Out', value: 'ease-out', icon: '⌒' },
            { name: 'Ease In Out', value: 'ease-in-out', icon: '~' }
        ],

        // ===== Phase 6: Transitions Library =====
        transitions: [
            { id: 'fade', name: '{{ __("Fade") }}', icon: 'fade', duration: 0.5, category: 'basic' },
            { id: 'dissolve', name: '{{ __("Dissolve") }}', icon: 'dissolve', duration: 0.5, category: 'basic' },
            { id: 'wipe-left', name: '{{ __("Wipe Left") }}', icon: 'wipe', duration: 0.5, category: 'wipe' },
            { id: 'wipe-right', name: '{{ __("Wipe Right") }}', icon: 'wipe', duration: 0.5, category: 'wipe' },
            { id: 'wipe-up', name: '{{ __("Wipe Up") }}', icon: 'wipe', duration: 0.5, category: 'wipe' },
            { id: 'wipe-down', name: '{{ __("Wipe Down") }}', icon: 'wipe', duration: 0.5, category: 'wipe' },
            { id: 'slide-left', name: '{{ __("Slide Left") }}', icon: 'slide', duration: 0.5, category: 'slide' },
            { id: 'slide-right', name: '{{ __("Slide Right") }}', icon: 'slide', duration: 0.5, category: 'slide' },
            { id: 'zoom-in', name: '{{ __("Zoom In") }}', icon: 'zoom', duration: 0.5, category: 'zoom' },
            { id: 'zoom-out', name: '{{ __("Zoom Out") }}', icon: 'zoom', duration: 0.5, category: 'zoom' },
            { id: 'blur', name: '{{ __("Blur") }}', icon: 'blur', duration: 0.5, category: 'effect' },
            { id: 'flash', name: '{{ __("Flash") }}', icon: 'flash', duration: 0.3, category: 'effect' }
        ],
        transitionCategories: ['basic', 'wipe', 'slide', 'zoom', 'effect'],
        showTransitionLibrary: false,
        selectedTransitionCategory: 'all',
        draggingTransition: null,
        transitionDropTarget: null,
        previewingTransition: null,
        clipTransitions: {}, // { clipKey: { in: transitionId, out: transitionId, inDuration: 0.5, outDuration: 0.5 } }

        // ===== Phase 6: Enhanced In/Out Points =====
        showIORegion: true,
        ioRegionMode: 'highlight', // 'highlight', 'loop', 'export'

        // ===== Phase 7: Performance & Polish =====
        // Virtual Scrolling
        virtualScrollEnabled: true,
        visibleClipBuffer: 2, // Number of extra clips to render outside viewport
        visibleClipRange: { start: 0, end: 100 },
        lastScrollUpdate: 0,
        scrollDebounceMs: 16, // ~60fps

        // Web Workers
        waveformWorker: null,
        thumbnailWorker: null,
        workerQueue: [],
        isProcessingQueue: false,

        // GPU Acceleration
        useGPUAcceleration: true,
        reducedMotion: false,

        // Touch/Mobile Support
        isTouchDevice: false,
        touchStartX: 0,
        touchStartY: 0,
        touchStartTime: 0,
        touchMoved: false,
        longPressTimer: null,
        longPressDuration: 500,
        swipeThreshold: 50,
        swipeVelocityThreshold: 0.3,
        lastTouchX: 0,
        lastTouchY: 0,
        touchVelocity: 0,
        isSwiping: false,
        swipeDirection: null,
        pinchDistance: 0,
        isLongPressing: false,

        // Performance Metrics (debug)
        fpsCounter: 0,
        lastFpsUpdate: 0,
        currentFps: 60,

        // Cleanup references
        _resizeObserver: null,
        _waveformWorkerUrl: null,

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
            return this.trackOrder
                .filter(id => this.tracks[id]?.visible)
                .map(id => [id, this.tracks[id]]);
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
            // Store bound references for proper cleanup
            this._boundHandlePlayheadDrag = this.handlePlayheadDrag.bind(this);
            this._boundEndPlayheadDrag = this.endPlayheadDrag.bind(this);
            document.addEventListener('mousemove', this._boundHandlePlayheadDrag);
            document.addEventListener('mouseup', this._boundEndPlayheadDrag);
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
            document.removeEventListener('mousemove', this._boundHandlePlayheadDrag);
            document.removeEventListener('mouseup', this._boundEndPlayheadDrag);
        },

        // ===== Enhanced Snap System =====
        getSnapPoints() {
            const points = [];

            // Add timeline boundaries
            points.push({ time: 0, type: 'boundary', label: 'Start' });
            points.push({ time: this.totalDuration, type: 'boundary', label: 'End' });

            // Add playhead position
            points.push({ time: this.currentTime, type: 'playhead', label: 'Playhead' });

            // Add clip edges from all tracks (using reactive scenesData)
            this.scenesData.forEach(scene => {
                points.push({ time: scene.start, type: 'clip-start', index: scene.index });
                points.push({ time: scene.end, type: 'clip-end', index: scene.index });
            });

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
            const clipIndex = this.dragTarget.index;
            const track = this.dragTarget.track;

            // In ripple mode, all clips after the edited clip are affected (using reactive scenesData)
            this.affectedClips = this.scenesData
                .filter(scene => scene.index > clipIndex)
                .map(scene => ({ track, index: scene.index }));
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

        // ===== Phase 4: Enhanced Track Management =====
        toggleTrackSolo(trackId) {
            if (this.tracks[trackId]) {
                const wasSolo = this.tracks[trackId].solo;
                // Turn off solo on all tracks first
                Object.keys(this.tracks).forEach(id => {
                    this.tracks[id].solo = false;
                });
                // Toggle solo on selected track
                if (!wasSolo) {
                    this.tracks[trackId].solo = true;
                }
                this.$dispatch('track-solo-changed', { track: trackId, solo: this.tracks[trackId].solo });
            }
        },

        toggleTrackCollapse(trackId) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].collapsed = !this.tracks[trackId].collapsed;
                if (this.tracks[trackId].collapsed) {
                    this.tracks[trackId].height = this.tracks[trackId].minHeight;
                } else {
                    this.tracks[trackId].height = this.tracks[trackId].defaultHeight;
                }
            }
        },

        toggleTrackVisibility(trackId) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].visible = !this.tracks[trackId].visible;
            }
        },

        setTrackVolume(trackId, volume) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].volume = Math.max(0, Math.min(100, volume));
                this.$dispatch('track-volume-changed', {
                    track: trackId,
                    volume: this.tracks[trackId].volume / 100
                });
            }
        },

        // Track height resizing
        startTrackResize(e, trackId) {
            this.isResizingTrack = true;
            this.resizeTrackId = trackId;
            this.resizeStartY = e.clientY;
            this.resizeStartHeight = this.tracks[trackId].height;

            this._boundHandleTrackResize = this.handleTrackResize.bind(this);
            this._boundEndTrackResize = this.endTrackResize.bind(this);
            document.addEventListener('mousemove', this._boundHandleTrackResize);
            document.addEventListener('mouseup', this._boundEndTrackResize);
            document.body.style.cursor = 'ns-resize';
            e.preventDefault();
        },

        handleTrackResize(e) {
            if (!this.isResizingTrack || !this.resizeTrackId) return;

            const delta = e.clientY - this.resizeStartY;
            const track = this.tracks[this.resizeTrackId];
            const newHeight = Math.max(
                track.minHeight,
                Math.min(track.maxHeight, this.resizeStartHeight + delta)
            );

            this.tracks[this.resizeTrackId].height = newHeight;
            this.tracks[this.resizeTrackId].collapsed = newHeight <= track.minHeight;
        },

        endTrackResize() {
            this.isResizingTrack = false;
            this.resizeTrackId = null;
            document.removeEventListener('mousemove', this._boundHandleTrackResize);
            document.removeEventListener('mouseup', this._boundEndTrackResize);
            document.body.style.cursor = '';
        },

        resetTrackHeight(trackId) {
            if (this.tracks[trackId]) {
                this.tracks[trackId].height = this.tracks[trackId].defaultHeight;
                this.tracks[trackId].collapsed = false;
            }
        },

        // Track reordering
        startTrackDrag(e, trackId) {
            this.isDraggingTrack = true;
            this.dragTrackId = trackId;
            this.dragTrackStartY = e.clientY;
            this.dragTrackTargetIndex = this.trackOrder.indexOf(trackId);

            this._boundHandleTrackDrag = this.handleTrackDrag.bind(this);
            this._boundEndTrackDrag = this.endTrackDrag.bind(this);
            document.addEventListener('mousemove', this._boundHandleTrackDrag);
            document.addEventListener('mouseup', this._boundEndTrackDrag);
            document.body.style.cursor = 'grabbing';
            e.preventDefault();
        },

        handleTrackDrag(e) {
            if (!this.isDraggingTrack || !this.dragTrackId) return;

            const delta = e.clientY - this.dragTrackStartY;
            const currentIndex = this.trackOrder.indexOf(this.dragTrackId);
            let newIndex = currentIndex;

            // Calculate which track position we're over
            let accumulatedHeight = 0;
            for (let i = 0; i < this.trackOrder.length; i++) {
                const track = this.tracks[this.trackOrder[i]];
                if (!track.visible) continue;
                accumulatedHeight += track.height;
                if (delta > 0 && i > currentIndex && delta > accumulatedHeight / 2) {
                    newIndex = i;
                } else if (delta < 0 && i < currentIndex && Math.abs(delta) > accumulatedHeight / 2) {
                    newIndex = i;
                }
            }

            this.dragTrackTargetIndex = newIndex;
        },

        endTrackDrag() {
            if (this.isDraggingTrack && this.dragTrackId && this.dragTrackTargetIndex !== null) {
                const currentIndex = this.trackOrder.indexOf(this.dragTrackId);
                if (currentIndex !== this.dragTrackTargetIndex) {
                    // Reorder array
                    const [removed] = this.trackOrder.splice(currentIndex, 1);
                    this.trackOrder.splice(this.dragTrackTargetIndex, 0, removed);
                    // Update order values
                    this.trackOrder.forEach((id, idx) => {
                        this.tracks[id].order = idx;
                    });
                }
            }

            this.isDraggingTrack = false;
            this.dragTrackId = null;
            this.dragTrackTargetIndex = null;
            document.removeEventListener('mousemove', this._boundHandleTrackDrag);
            document.removeEventListener('mouseup', this._boundEndTrackDrag);
            document.body.style.cursor = '';
        },

        // Get tracks in display order
        get orderedTracks() {
            return this.trackOrder
                .filter(id => this.tracks[id]?.visible)
                .map(id => [id, this.tracks[id]]);
        },

        // Track menu
        openTrackMenu(trackId) {
            this.showTrackMenu = this.showTrackMenu === trackId ? null : trackId;
        },

        closeTrackMenu() {
            this.showTrackMenu = null;
        },

        // Calculate total tracks height
        get totalTracksHeight() {
            return this.trackOrder.reduce((sum, id) => {
                const track = this.tracks[id];
                return sum + (track?.visible ? track.height : 0);
            }, 0);
        },

        // ===== Phase 3: Tool Management =====
        setTool(tool) {
            this.currentTool = tool;
            this.showSplitCursor = tool === 'split';
            if (tool !== 'split') {
                this.splitCursorPosition = null;
            }
        },

        // ===== Phase 3: Split Tool =====
        updateSplitCursor(e) {
            if (this.currentTool !== 'split') return;
            const container = this.$refs.timelineScroll;
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left + container.scrollLeft;
            this.splitCursorPosition = x;
        },

        splitAtPosition(time, track = null) {
            if (time <= 0 || time >= this.totalDuration) return;

            this.saveHistory();
            this.$dispatch('split-clip', {
                time: time,
                track: track || this.selectedTrack || 'video',
                ripple: this.rippleMode
            });
        },

        splitAtPlayhead() {
            if (this.currentTime > 0 && this.currentTime < this.totalDuration) {
                this.splitAtPosition(this.currentTime);
            }
        },

        splitSelectedClip() {
            if (this.selectedClips.length > 0) {
                // Split all selected clips at playhead
                this.saveHistory();
                this.selectedClips.forEach(clip => {
                    this.$dispatch('split-clip', {
                        time: this.currentTime,
                        track: clip.track,
                        index: clip.index,
                        ripple: this.rippleMode
                    });
                });
            } else if (this.selectedClip !== null) {
                this.splitAtPlayhead();
            }
        },

        // ===== Phase 3: Enhanced Selection =====
        selectClip(track, clipIndex, event = null) {
            const clip = { track, index: clipIndex };

            if (event && (event.ctrlKey || event.metaKey)) {
                // Ctrl/Cmd+click: toggle individual selection
                const existingIndex = this.selectedClips.findIndex(
                    c => c.track === track && c.index === clipIndex
                );
                if (existingIndex >= 0) {
                    this.selectedClips.splice(existingIndex, 1);
                } else {
                    this.selectedClips.push(clip);
                }
            } else if (event && event.shiftKey && this.lastSelectedIndex !== null) {
                // Shift+click: range selection on same track
                if (this.selectedTrack === track) {
                    const start = Math.min(this.lastSelectedIndex, clipIndex);
                    const end = Math.max(this.lastSelectedIndex, clipIndex);
                    this.selectedClips = [];
                    for (let i = start; i <= end; i++) {
                        this.selectedClips.push({ track, index: i });
                    }
                }
            } else {
                // Normal click: single selection
                this.selectedClips = [clip];
            }

            this.selectedTrack = track;
            this.selectedClip = clipIndex;
            this.lastSelectedIndex = clipIndex;
            this.$dispatch('clip-selected', { track, clipIndex, multi: this.selectedClips.length > 1 });

            // Screen reader announcement
            const count = this.selectedClips.length;
            if (count > 1) {
                this.announceToScreenReader(`${count} clips selected`);
            } else {
                this.announceToScreenReader(`Clip ${clipIndex + 1} on ${track} track selected`);
            }
        },

        isClipSelected(track, index) {
            return this.selectedClips.some(c => c.track === track && c.index === index);
        },

        selectAllClipsOnTrack(track) {
            // Using reactive scenesData for clip selection
            this.selectedClips = this.scenesData.map(scene => ({ track, index: scene.index }));
            this.selectedTrack = track;
        },

        selectAllClips() {
            // Using reactive scenesData for clip selection across all tracks
            this.selectedClips = this.scenesData.flatMap(scene => [
                { track: 'video', index: scene.index },
                { track: 'voiceover', index: scene.index },
                { track: 'captions', index: scene.index }
            ]);
        },

        deselectAll() {
            this.selectedTrack = null;
            this.selectedClip = null;
            this.selectedClips = [];
            this.lastSelectedIndex = null;
            this.hideContextMenu();
        },

        // ===== Phase 3: Marquee Selection =====
        startMarquee(e) {
            if (this.currentTool !== 'select') return;
            if (e.target.closest('.vw-clip')) return;

            const container = this.$refs.timelineScroll;
            const rect = container.getBoundingClientRect();

            this.isMultiSelecting = true;
            this.showMarquee = true;
            this.marqueeStart = {
                x: e.clientX - rect.left + container.scrollLeft,
                y: e.clientY - rect.top + container.scrollTop
            };
            this.marqueeEnd = { ...this.marqueeStart };

            document.addEventListener('mousemove', this._boundUpdateMarquee = this.updateMarquee.bind(this));
            document.addEventListener('mouseup', this._boundEndMarquee = this.endMarquee.bind(this));
            e.preventDefault();
        },

        updateMarquee(e) {
            if (!this.isMultiSelecting) return;

            const container = this.$refs.timelineScroll;
            const rect = container.getBoundingClientRect();

            this.marqueeEnd = {
                x: e.clientX - rect.left + container.scrollLeft,
                y: e.clientY - rect.top + container.scrollTop
            };

            // Select clips within marquee
            this.selectClipsInMarquee();
        },

        endMarquee() {
            this.isMultiSelecting = false;
            this.showMarquee = false;
            document.removeEventListener('mousemove', this._boundUpdateMarquee);
            document.removeEventListener('mouseup', this._boundEndMarquee);
        },

        selectClipsInMarquee() {
            const left = Math.min(this.marqueeStart.x, this.marqueeEnd.x);
            const right = Math.max(this.marqueeStart.x, this.marqueeEnd.x);
            const top = Math.min(this.marqueeStart.y, this.marqueeEnd.y);
            const bottom = Math.max(this.marqueeStart.y, this.marqueeEnd.y);

            this.selectedClips = [];

            // Check each clip for intersection with marquee
            const clips = this.$refs.tracksContainer?.querySelectorAll('.vw-clip');
            clips?.forEach(clipEl => {
                const clipRect = clipEl.getBoundingClientRect();
                const container = this.$refs.timelineScroll;
                const containerRect = container.getBoundingClientRect();

                const clipLeft = clipRect.left - containerRect.left + container.scrollLeft;
                const clipRight = clipLeft + clipRect.width;
                const clipTop = clipRect.top - containerRect.top + container.scrollTop;
                const clipBottom = clipTop + clipRect.height;

                // Check intersection
                if (clipLeft < right && clipRight > left && clipTop < bottom && clipBottom > top) {
                    const track = clipEl.closest('.vw-track')?.classList.contains('vw-track-video') ? 'video' :
                                  clipEl.closest('.vw-track')?.classList.contains('vw-track-voiceover') ? 'voiceover' :
                                  clipEl.closest('.vw-track')?.classList.contains('vw-track-music') ? 'music' : 'captions';
                    const index = parseInt(clipEl.dataset.clipIndex || '0');
                    this.selectedClips.push({ track, index });
                }
            });
        },

        get marqueeStyle() {
            if (!this.marqueeStart || !this.marqueeEnd) return {};
            return {
                left: Math.min(this.marqueeStart.x, this.marqueeEnd.x) + 'px',
                top: Math.min(this.marqueeStart.y, this.marqueeEnd.y) + 'px',
                width: Math.abs(this.marqueeEnd.x - this.marqueeStart.x) + 'px',
                height: Math.abs(this.marqueeEnd.y - this.marqueeStart.y) + 'px'
            };
        },

        // ===== Phase 3: Clipboard Operations =====
        copySelectedClips() {
            if (this.selectedClips.length === 0) return;
            this.clipboard = [...this.selectedClips];
            this.clipboardOperation = 'copy';
            this.showNotification('{{ __('Copied') }} ' + this.selectedClips.length + ' {{ __('clip(s)') }}');
        },

        cutSelectedClips() {
            if (this.selectedClips.length === 0) return;
            this.clipboard = [...this.selectedClips];
            this.clipboardOperation = 'cut';
            this.showNotification('{{ __('Cut') }} ' + this.selectedClips.length + ' {{ __('clip(s)') }}');
        },

        pasteClips() {
            if (this.clipboard.length === 0) return;

            this.saveHistory();
            this.$dispatch('paste-clips', {
                clips: this.clipboard,
                operation: this.clipboardOperation,
                targetTime: this.currentTime,
                ripple: this.rippleMode
            });

            if (this.clipboardOperation === 'cut') {
                this.clipboard = [];
                this.clipboardOperation = null;
            }
        },

        deleteSelectedClips() {
            if (this.selectedClips.length === 0 && this.selectedClip === null) return;

            this.saveHistory();
            const toDelete = this.selectedClips.length > 0 ? this.selectedClips : [{ track: this.selectedTrack, index: this.selectedClip }];
            const deleteCount = toDelete.length;

            this.$dispatch('delete-clips', {
                clips: toDelete,
                ripple: this.rippleMode
            });

            this.deselectAll();

            // Screen reader announcement
            this.announceToScreenReader(`${deleteCount} clip${deleteCount > 1 ? 's' : ''} deleted`, 'assertive');
        },

        showNotification(message) {
            // Simple notification using custom event
            this.$dispatch('show-notification', { message });
        },

        // ===== Phase D: Accessibility - Screen Reader Announcements =====
        announceToScreenReader(message, priority = 'polite') {
            // Update the live region for screen reader announcement
            const liveRegion = this.$refs.srAnnouncer;
            if (liveRegion) {
                liveRegion.setAttribute('aria-live', priority);
                liveRegion.textContent = message;
                // Clear after announcement to allow repeated messages
                setTimeout(() => { liveRegion.textContent = ''; }, 1000);
            }
        },

        // ===== Phase 3: Context Menu =====
        openContextMenu(e, track, index) {
            e.preventDefault();
            this.showContextMenu = true;
            this.contextMenuX = e.clientX;
            this.contextMenuY = e.clientY;
            this.contextMenuTarget = { track, index };

            // Select the clip if not already selected
            if (!this.isClipSelected(track, index)) {
                this.selectClip(track, index);
            }

            // Add click-away listener
            setTimeout(() => {
                document.addEventListener('click', this._boundHideContextMenu = this.hideContextMenu.bind(this), { once: true });
            }, 10);
        },

        hideContextMenu() {
            this.showContextMenu = false;
            this.contextMenuTarget = null;
        },

        contextMenuAction(action) {
            switch (action) {
                case 'cut':
                    this.cutSelectedClips();
                    break;
                case 'copy':
                    this.copySelectedClips();
                    break;
                case 'paste':
                    this.pasteClips();
                    break;
                case 'delete':
                    this.deleteSelectedClips();
                    break;
                case 'split':
                    this.splitAtPlayhead();
                    break;
                case 'duplicate':
                    this.duplicateSelected();
                    break;
                case 'properties':
                    // Show in inspector panel
                    break;
            }
            this.hideContextMenu();
        },

        duplicateSelected() {
            if (this.selectedClips.length === 0 && this.selectedClip === null) return;
            this.saveHistory();
            const toDuplicate = this.selectedClips.length > 0 ? this.selectedClips : [{ track: this.selectedTrack, index: this.selectedClip }];
            this.$dispatch('duplicate-clips', { clips: toDuplicate });
        },

        // ===== Phase 3: In/Out Points =====
        setInPoint() {
            this.inPoint = this.currentTime;
            this.showNotification('{{ __('In point set at') }} ' + this.formatTimeDetailed(this.currentTime));
        },

        setOutPoint() {
            this.outPoint = this.currentTime;
            this.showNotification('{{ __('Out point set at') }} ' + this.formatTimeDetailed(this.currentTime));
        },

        clearInOutPoints() {
            this.inPoint = null;
            this.outPoint = null;
        },

        goToInPoint() {
            if (this.inPoint !== null) {
                this.seek(this.inPoint);
            }
        },

        goToOutPoint() {
            if (this.outPoint !== null) {
                this.seek(this.outPoint);
            }
        },

        // ===== Phase 3: JKL Playback =====
        jklControl(key) {
            const speeds = [-4, -2, -1, 0, 1, 2, 4];

            if (key === 'j') {
                // Reverse/slower
                const currentIdx = speeds.indexOf(this.jklSpeed);
                if (currentIdx > 0) {
                    this.jklSpeed = speeds[currentIdx - 1];
                }
            } else if (key === 'k') {
                // Stop
                this.jklSpeed = 0;
                this.$dispatch('pause-preview');
            } else if (key === 'l') {
                // Forward/faster
                const currentIdx = speeds.indexOf(this.jklSpeed);
                if (currentIdx < speeds.length - 1) {
                    this.jklSpeed = speeds[currentIdx + 1];
                }
            }

            if (this.jklSpeed !== 0) {
                this.$dispatch('jkl-playback', { speed: this.jklSpeed });
            }
        },

        // ===== Phase 3: Frame Stepping =====
        stepFrames(frames) {
            const frameTime = 1 / this.frameRate;
            const newTime = Math.max(0, Math.min(this.totalDuration, this.currentTime + (frames * frameTime)));
            this.seek(newTime);
        },

        // ===== Phase 3: Navigation =====
        goToStart() {
            this.seek(0);
        },

        goToEnd() {
            this.seek(this.totalDuration);
        },

        // Selection (legacy)
        // selectClip function has been replaced above with enhanced version

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
                const previousIndex = this.historyIndex;
                this.historyIndex--;
                $wire.call('timelineUndo').then(() => {
                    this.announceToScreenReader('Undo');
                }).catch(error => {
                    console.error('Timeline undo failed:', error);
                    this.historyIndex = previousIndex; // Revert on failure
                    this.$dispatch('show-notification', {
                        message: 'Undo failed. Please try again.',
                        type: 'error'
                    });
                    this.announceToScreenReader('Undo failed', 'assertive');
                });
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                const previousIndex = this.historyIndex;
                this.historyIndex++;
                $wire.call('timelineRedo').then(() => {
                    this.announceToScreenReader('Redo');
                }).catch(error => {
                    console.error('Timeline redo failed:', error);
                    this.historyIndex = previousIndex; // Revert on failure
                    this.$dispatch('show-notification', {
                        message: 'Redo failed. Please try again.',
                        type: 'error'
                    });
                    this.announceToScreenReader('Redo failed', 'assertive');
                });
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
        },

        // ===== Phase 5: Enhanced Zoom Controls =====
        setZoomLevel(level) {
            const container = this.$refs.timelineScroll;
            if (!container) return;

            // Store scroll center point for zoom focus
            const scrollCenter = container.scrollLeft + container.offsetWidth / 2;
            const timeAtCenter = this.pixelsToTime(scrollCenter);

            // Apply new zoom
            this.zoom = Math.max(0.25, Math.min(4, level));

            // Recenter on the same time position
            this.$nextTick(() => {
                const newPixelPosition = this.timeToPixels(timeAtCenter);
                container.scrollLeft = newPixelPosition - container.offsetWidth / 2;
                this.updateMinimapViewport();
            });
        },

        zoomToSelection() {
            if (this.selectedClips.length === 0) return;

            // Find bounds of selection using reactive scenesData
            let minTime = Infinity;
            let maxTime = 0;

            // Get selected scene indices
            const selectedIndices = new Set(this.selectedClips.map(c => c.index));

            // Get time bounds from selected clips
            this.scenesData.forEach(scene => {
                if (selectedIndices.has(scene.index)) {
                    minTime = Math.min(minTime, scene.start);
                    maxTime = Math.max(maxTime, scene.end);
                }
            });

            if (minTime === Infinity || maxTime === 0) return;

            const container = this.$refs.timelineScroll;
            if (!container) return;

            const selectionDuration = maxTime - minTime;
            const availableWidth = container.offsetWidth - 100; // Padding
            const idealZoom = availableWidth / (selectionDuration * 60);

            // Find closest zoom level
            let closest = this.zoomLevels[0];
            for (const level of this.zoomLevels) {
                if (Math.abs(level - idealZoom) < Math.abs(closest - idealZoom)) {
                    closest = level;
                }
            }

            this.zoom = closest;

            // Center on selection
            this.$nextTick(() => {
                const centerTime = minTime + selectionDuration / 2;
                const centerPixel = this.timeToPixels(centerTime);
                container.scrollLeft = centerPixel - container.offsetWidth / 2;
                this.updateMinimapViewport();
            });
        },

        handleWheelZoom(e) {
            if (!e.ctrlKey && !e.metaKey) return;

            e.preventDefault();
            const container = this.$refs.timelineScroll;
            if (!container) return;

            // Get cursor position relative to timeline
            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left + container.scrollLeft;
            const timeAtMouse = this.pixelsToTime(mouseX);

            // Calculate new zoom
            const delta = e.deltaY > 0 ? -1 : 1;
            const currentIdx = this.zoomLevels.indexOf(this.zoom);
            const newIdx = Math.max(0, Math.min(this.zoomLevels.length - 1, currentIdx + delta));
            const newZoom = this.zoomLevels[newIdx];

            if (newZoom === this.zoom) return;

            this.zoom = newZoom;

            // Maintain mouse position after zoom
            this.$nextTick(() => {
                const newPixelPosition = this.timeToPixels(timeAtMouse);
                const mouseOffsetFromLeft = e.clientX - rect.left;
                container.scrollLeft = newPixelPosition - mouseOffsetFromLeft;
                this.updateMinimapViewport();
            });
        },

        startPinchZoom(e) {
            if (e.touches.length !== 2) return;

            this.isPinchZooming = true;
            this.pinchStartZoom = this.zoom;
            this.pinchStartDistance = Math.hypot(
                e.touches[1].clientX - e.touches[0].clientX,
                e.touches[1].clientY - e.touches[0].clientY
            );

            // Store focus point (midpoint between touches)
            const container = this.$refs.timelineScroll;
            if (container) {
                const rect = container.getBoundingClientRect();
                const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left + container.scrollLeft;
                this.zoomFocusPoint = this.pixelsToTime(midX);
            }
        },

        handlePinchZoom(e) {
            if (!this.isPinchZooming || e.touches.length !== 2) return;

            e.preventDefault();
            const currentDistance = Math.hypot(
                e.touches[1].clientX - e.touches[0].clientX,
                e.touches[1].clientY - e.touches[0].clientY
            );

            const scale = currentDistance / this.pinchStartDistance;
            const newZoom = Math.max(0.25, Math.min(4, this.pinchStartZoom * scale));

            // Find closest zoom level
            let closest = this.zoomLevels[0];
            for (const level of this.zoomLevels) {
                if (Math.abs(level - newZoom) < Math.abs(closest - newZoom)) {
                    closest = level;
                }
            }

            if (closest !== this.zoom) {
                this.zoom = closest;

                // Maintain focus point
                if (this.zoomFocusPoint !== null) {
                    const container = this.$refs.timelineScroll;
                    if (container) {
                        const newPixelPosition = this.timeToPixels(this.zoomFocusPoint);
                        container.scrollLeft = newPixelPosition - container.offsetWidth / 2;
                    }
                }
            }
        },

        endPinchZoom() {
            this.isPinchZooming = false;
            this.zoomFocusPoint = null;
            this.updateMinimapViewport();
        },

        // ===== Phase 5: Timeline Minimap =====
        get minimapScale() {
            const container = this.$refs.timelineScroll;
            if (!container) return 0.1;
            return (container.offsetWidth - 20) / this.timelineWidth;
        },

        get minimapClips() {
            // Using reactive scenesData for minimap clip rendering
            return this.scenesData.map(scene => ({
                track: 'video',
                left: scene.start * this.minimapScale * this.pixelsPerSecond,
                width: scene.duration * this.minimapScale * this.pixelsPerSecond
            }));
        },

        updateMinimapViewport() {
            const container = this.$refs.timelineScroll;
            if (!container) return;

            const totalWidth = this.timelineWidth;
            const minimapWidth = container.offsetWidth - 20; // Padding

            this.minimapViewportWidth = (container.offsetWidth / totalWidth) * minimapWidth;
            this.minimapViewportLeft = (container.scrollLeft / totalWidth) * minimapWidth;
        },

        startMinimapDrag(e) {
            this.minimapDragging = true;
            this.minimapDragStartX = e.clientX;
            this.minimapDragStartScroll = this.$refs.timelineScroll?.scrollLeft || 0;

            document.addEventListener('mousemove', this._boundHandleMinimapDrag = this.handleMinimapDrag.bind(this));
            document.addEventListener('mouseup', this._boundEndMinimapDrag = this.endMinimapDrag.bind(this));
            e.preventDefault();
        },

        handleMinimapDrag(e) {
            if (!this.minimapDragging) return;

            const container = this.$refs.timelineScroll;
            if (!container) return;

            const minimapWidth = container.offsetWidth - 20;
            const deltaX = e.clientX - this.minimapDragStartX;
            const scrollDelta = (deltaX / minimapWidth) * this.timelineWidth;

            container.scrollLeft = Math.max(0, Math.min(
                this.timelineWidth - container.offsetWidth,
                this.minimapDragStartScroll + scrollDelta
            ));

            this.updateMinimapViewport();
        },

        endMinimapDrag() {
            this.minimapDragging = false;
            document.removeEventListener('mousemove', this._boundHandleMinimapDrag);
            document.removeEventListener('mouseup', this._boundEndMinimapDrag);
        },

        minimapNavigate(e) {
            if (this.minimapDragging) return;

            const container = this.$refs.timelineScroll;
            const minimap = this.$refs.minimap;
            if (!container || !minimap) return;

            const rect = minimap.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const minimapWidth = container.offsetWidth - 20;

            // Convert click position to scroll position
            const scrollRatio = clickX / minimapWidth;
            const targetScroll = scrollRatio * this.timelineWidth - container.offsetWidth / 2;

            container.scrollLeft = Math.max(0, Math.min(
                this.timelineWidth - container.offsetWidth,
                targetScroll
            ));

            this.updateMinimapViewport();
        },

        // ===== Phase 5: Enhanced Scrubbing =====
        startScrub(e) {
            this.isScrubbing = true;
            this.scrubStartX = e.clientX;
            this.scrubStartTime = this.currentTime;

            if (this.audioScrubEnabled) {
                this.scrubPreviewActive = true;
                this.$dispatch('scrub-preview-start');
            }

            document.addEventListener('mousemove', this._boundHandleScrub = this.handleScrub.bind(this));
            document.addEventListener('mouseup', this._boundEndScrub = this.endScrub.bind(this));
            e.preventDefault();
        },

        handleScrub(e) {
            if (!this.isScrubbing) return;

            const container = this.$refs.timelineScroll;
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left + container.scrollLeft;
            let time = this.pixelsToTime(x);
            time = Math.max(0, Math.min(this.totalDuration, time));

            this.seek(time);

            if (this.scrubPreviewActive) {
                this.$dispatch('scrub-preview-update', { time });
            }
        },

        endScrub() {
            this.isScrubbing = false;
            if (this.scrubPreviewActive) {
                this.scrubPreviewActive = false;
                this.$dispatch('scrub-preview-end');
            }
            document.removeEventListener('mousemove', this._boundHandleScrub);
            document.removeEventListener('mouseup', this._boundEndScrub);
        },

        toggleAudioScrub() {
            this.audioScrubEnabled = !this.audioScrubEnabled;
        },

        // ===== Phase 5: Enhanced Shuttle Control =====
        updateShuttleDisplay() {
            const rates = { '-4': '◀◀ 4x', '-2': '◀◀ 2x', '-1': '◀ 1x', '0': '▶ ||', '1': '▶ 1x', '2': '▶▶ 2x', '4': '▶▶ 4x' };
            this.shuttleRateDisplay = rates[this.jklSpeed.toString()] || '▶ ||';
        },

        shuttleStop() {
            this.jklSpeed = 0;
            this.$dispatch('pause-preview');
            this.updateShuttleDisplay();
        },

        shuttleForward() {
            const speeds = [0, 1, 2, 4];
            const currentIdx = speeds.indexOf(Math.max(0, this.jklSpeed));
            if (currentIdx < speeds.length - 1) {
                this.jklSpeed = speeds[currentIdx + 1];
                this.$dispatch('jkl-playback', { speed: this.jklSpeed });
            }
            this.updateShuttleDisplay();
        },

        shuttleReverse() {
            const speeds = [0, -1, -2, -4];
            const currentIdx = speeds.indexOf(Math.min(0, this.jklSpeed));
            if (currentIdx < speeds.length - 1) {
                this.jklSpeed = speeds[currentIdx + 1];
                this.$dispatch('jkl-playback', { speed: this.jklSpeed });
            }
            this.updateShuttleDisplay();
        },

        // ===== Phase 6: Markers & Chapters =====
        addMarker(time = null, color = null, name = '') {
            // Validate and clamp marker time to valid range
            let markerTime = time !== null ? time : this.currentTime;
            if (typeof markerTime !== 'number' || isNaN(markerTime)) {
                markerTime = this.currentTime;
            }
            markerTime = Math.max(0, Math.min(this.totalDuration, markerTime));

            // Validate and sanitize name
            let markerName = name;
            if (typeof markerName !== 'string' || !markerName.trim()) {
                markerName = '{{ __("Marker") }} ' + (this.markers.length + 1);
            } else {
                markerName = markerName.substring(0, 100); // Limit length
            }

            const marker = {
                id: Date.now(),
                time: markerTime,
                color: color || this.markerColors[0].value,
                name: markerName,
                notes: ''
            };
            this.markers.push(marker);
            this.markers.sort((a, b) => a.time - b.time);
            this.saveHistory();
            this.$dispatch('marker-added', { marker });

            // Screen reader announcement
            this.announceToScreenReader(`Marker added at ${this.formatTime(markerTime)}`);
            return marker;
        },

        addMarkerAtPlayhead() {
            const marker = this.addMarker();
            this.editingMarker = marker.id;
            this.showMarkerPanel = true;
        },

        deleteMarker(markerId) {
            const index = this.markers.findIndex(m => m.id === markerId);
            if (index >= 0) {
                this.markers.splice(index, 1);
                this.saveHistory();
                this.$dispatch('marker-deleted', { markerId });
            }
            if (this.selectedMarker === markerId) {
                this.selectedMarker = null;
            }
            this.hideMarkerMenu();
        },

        updateMarker(markerId, updates) {
            const marker = this.markers.find(m => m.id === markerId);
            if (marker) {
                // Sanitize updates to prevent prototype pollution
                const safeUpdates = {};
                const allowedKeys = ['time', 'name', 'color', 'notes', 'type'];
                for (const key of allowedKeys) {
                    if (updates.hasOwnProperty(key)) {
                        safeUpdates[key] = updates[key];
                    }
                }

                // Validate time if provided in updates
                if (safeUpdates.time !== undefined) {
                    let newTime = safeUpdates.time;
                    if (typeof newTime !== 'number' || isNaN(newTime)) {
                        delete safeUpdates.time; // Remove invalid time update
                    } else {
                        safeUpdates.time = Math.max(0, Math.min(this.totalDuration, newTime));
                    }
                }

                // Validate name to prevent XSS via object property
                if (safeUpdates.name !== undefined && typeof safeUpdates.name === 'string') {
                    safeUpdates.name = safeUpdates.name.substring(0, 100); // Limit length
                }

                Object.assign(marker, safeUpdates);
                if (safeUpdates.time !== undefined) {
                    this.markers.sort((a, b) => a.time - b.time);
                }
                this.$dispatch('marker-updated', { marker });
            }
        },

        selectMarker(markerId) {
            this.selectedMarker = markerId;
            const marker = this.markers.find(m => m.id === markerId);
            if (marker) {
                this.seek(marker.time);
            }
        },

        goToNextMarker() {
            if (this.markers.length === 0) return;
            const nextMarker = this.markers.find(m => m.time > this.currentTime + 0.1);
            if (nextMarker) {
                this.seek(nextMarker.time);
                this.selectedMarker = nextMarker.id;
            } else {
                // Loop to first marker
                this.seek(this.markers[0].time);
                this.selectedMarker = this.markers[0].id;
            }
        },

        goToPrevMarker() {
            if (this.markers.length === 0) return;
            const prevMarkers = this.markers.filter(m => m.time < this.currentTime - 0.1);
            if (prevMarkers.length > 0) {
                const prevMarker = prevMarkers[prevMarkers.length - 1];
                this.seek(prevMarker.time);
                this.selectedMarker = prevMarker.id;
            } else {
                // Loop to last marker
                const lastMarker = this.markers[this.markers.length - 1];
                this.seek(lastMarker.time);
                this.selectedMarker = lastMarker.id;
            }
        },

        openMarkerMenu(e, markerId) {
            e.preventDefault();
            e.stopPropagation();
            this.showMarkerMenu = true;
            this.markerMenuX = e.clientX;
            this.markerMenuY = e.clientY;
            this.selectedMarker = markerId;
            setTimeout(() => {
                document.addEventListener('click', this._boundHideMarkerMenu = this.hideMarkerMenu.bind(this), { once: true });
            }, 10);
        },

        hideMarkerMenu() {
            this.showMarkerMenu = false;
        },

        exportYouTubeChapters() {
            if (this.markers.length === 0) return '';

            let chapters = '';
            const sortedMarkers = [...this.markers].sort((a, b) => a.time - b.time);

            // Ensure first chapter starts at 0:00
            if (sortedMarkers[0].time > 0) {
                chapters += '0:00 {{ __("Intro") }}\n';
            }

            sortedMarkers.forEach(marker => {
                const mins = Math.floor(marker.time / 60);
                const secs = Math.floor(marker.time % 60);
                const timestamp = mins + ':' + secs.toString().padStart(2, '0');
                chapters += timestamp + ' ' + marker.name + '\n';
            });

            // Copy to clipboard
            navigator.clipboard?.writeText(chapters.trim());
            this.showNotification('{{ __("Chapters copied to clipboard!") }}');
            return chapters;
        },

        // ===== Phase 6: Keyframe Methods =====
        getClipKeyframes(track, clipIndex) {
            // Mock keyframes for demo - in production these would come from clip data
            const key = track + '-' + clipIndex;
            // Return sample keyframes for visualization
            return [
                { time: 0, property: 'opacity', value: 1, easing: 'linear' },
                { time: 0.5, property: 'scale', value: 1.1, easing: 'ease-in-out' },
                { time: 1, property: 'opacity', value: 1, easing: 'ease-out' }
            ];
        },

        selectKeyframe(track, clipIndex, keyframeIndex) {
            this.selectedKeyframe = { track, clipIndex, keyframeIndex };
            this.keyframePreviewClip = { track, clipIndex };
        },

        deselectKeyframe() {
            this.selectedKeyframe = null;
        },

        // ===== Phase 6: Transitions Library =====
        get filteredTransitions() {
            if (this.selectedTransitionCategory === 'all') {
                return this.transitions;
            }
            return this.transitions.filter(t => t.category === this.selectedTransitionCategory);
        },

        startTransitionDrag(e, transition) {
            this.draggingTransition = transition;
            document.addEventListener('mousemove', this._boundHandleTransitionDrag = this.handleTransitionDrag.bind(this));
            document.addEventListener('mouseup', this._boundEndTransitionDrag = this.endTransitionDrag.bind(this));
            e.preventDefault();
        },

        handleTransitionDrag(e) {
            if (!this.draggingTransition) return;

            // Find if we're over a clip edge
            const clips = document.querySelectorAll('.vw-clip');
            let nearestEdge = null;
            let minDistance = 30; // Threshold in pixels

            clips.forEach(clip => {
                const rect = clip.getBoundingClientRect();
                const leftEdgeDist = Math.abs(e.clientX - rect.left);
                const rightEdgeDist = Math.abs(e.clientX - rect.right);

                if (leftEdgeDist < minDistance) {
                    minDistance = leftEdgeDist;
                    nearestEdge = { clip, edge: 'in', x: rect.left };
                }
                if (rightEdgeDist < minDistance) {
                    minDistance = rightEdgeDist;
                    nearestEdge = { clip, edge: 'out', x: rect.right };
                }
            });

            this.transitionDropTarget = nearestEdge;
        },

        endTransitionDrag() {
            if (this.draggingTransition && this.transitionDropTarget) {
                const clip = this.transitionDropTarget.clip;
                const edge = this.transitionDropTarget.edge;
                const track = clip.closest('.vw-track')?.dataset.track || 'video';
                const clipIndex = parseInt(clip.dataset.clipIndex || '0');
                const clipKey = track + '-' + clipIndex;

                if (!this.clipTransitions[clipKey]) {
                    this.clipTransitions[clipKey] = {};
                }

                if (edge === 'in') {
                    this.clipTransitions[clipKey].in = this.draggingTransition.id;
                    this.clipTransitions[clipKey].inDuration = this.draggingTransition.duration;
                } else {
                    this.clipTransitions[clipKey].out = this.draggingTransition.id;
                    this.clipTransitions[clipKey].outDuration = this.draggingTransition.duration;
                }

                this.saveHistory();
                this.$dispatch('transition-applied', {
                    track,
                    clipIndex,
                    edge,
                    transition: this.draggingTransition
                });
            }

            this.draggingTransition = null;
            this.transitionDropTarget = null;
            document.removeEventListener('mousemove', this._boundHandleTransitionDrag);
            document.removeEventListener('mouseup', this._boundEndTransitionDrag);
        },

        applyTransition(track, clipIndex, edge, transitionId) {
            const clipKey = track + '-' + clipIndex;
            const transition = this.transitions.find(t => t.id === transitionId);
            if (!transition) return;

            if (!this.clipTransitions[clipKey]) {
                this.clipTransitions[clipKey] = {};
            }

            if (edge === 'in') {
                this.clipTransitions[clipKey].in = transitionId;
                this.clipTransitions[clipKey].inDuration = transition.duration;
            } else {
                this.clipTransitions[clipKey].out = transitionId;
                this.clipTransitions[clipKey].outDuration = transition.duration;
            }

            this.saveHistory();
        },

        removeTransition(track, clipIndex, edge) {
            const clipKey = track + '-' + clipIndex;
            if (this.clipTransitions[clipKey]) {
                if (edge === 'in') {
                    delete this.clipTransitions[clipKey].in;
                    delete this.clipTransitions[clipKey].inDuration;
                } else {
                    delete this.clipTransitions[clipKey].out;
                    delete this.clipTransitions[clipKey].outDuration;
                }
            }
            this.saveHistory();
        },

        getClipTransition(track, clipIndex, edge) {
            const clipKey = track + '-' + clipIndex;
            const transitions = this.clipTransitions[clipKey];
            if (!transitions) return null;

            const transitionId = edge === 'in' ? transitions.in : transitions.out;
            if (!transitionId) return null;

            return this.transitions.find(t => t.id === transitionId);
        },

        previewTransition(transition) {
            this.previewingTransition = transition;
            // Preview logic would trigger animation
        },

        stopTransitionPreview() {
            this.previewingTransition = null;
        },

        // ===== Phase 6: Enhanced In/Out Points =====
        toggleIORegion() {
            this.showIORegion = !this.showIORegion;
        },

        setIORegionMode(mode) {
            this.ioRegionMode = mode;
            if (mode === 'loop' && this.inPoint !== null && this.outPoint !== null) {
                this.$dispatch('loop-region', { start: this.inPoint, end: this.outPoint });
            }
        },

        getIORegionDuration() {
            if (this.inPoint === null || this.outPoint === null) return 0;
            return Math.abs(this.outPoint - this.inPoint);
        },

        exportIORegion() {
            if (this.inPoint === null || this.outPoint === null) {
                this.showNotification('{{ __("Set In and Out points first") }}');
                return;
            }

            const start = Math.min(this.inPoint, this.outPoint);
            const end = Math.max(this.inPoint, this.outPoint);

            this.$dispatch('export-region', { start, end });
            this.showNotification('{{ __("Exporting selected region...") }}');
        },

        // ===== Phase 7: Virtual Scrolling =====
        updateVisibleClipRange() {
            if (!this.virtualScrollEnabled) return;

            const now = performance.now();
            if (now - this.lastScrollUpdate < this.scrollDebounceMs) return;
            this.lastScrollUpdate = now;

            const container = this.$refs.timelineScroll;
            if (!container) return;

            const scrollLeft = container.scrollLeft;
            const viewportWidth = container.offsetWidth;

            // Calculate visible time range with buffer
            const bufferPixels = viewportWidth * 0.5;
            const startTime = Math.max(0, this.pixelsToTime(scrollLeft - bufferPixels));
            const endTime = this.pixelsToTime(scrollLeft + viewportWidth + bufferPixels);

            this.visibleClipRange = { start: startTime, end: endTime };
        },

        isClipVisible(startTime, duration) {
            if (!this.virtualScrollEnabled) return true;

            const clipEnd = startTime + duration;
            return !(clipEnd < this.visibleClipRange.start || startTime > this.visibleClipRange.end);
        },

        // ===== Phase 7: Web Workers =====
        initWebWorkers() {
            // Create waveform worker using inline blob
            const waveformWorkerCode = `
                self.onmessage = function(e) {
                    const { id, audioData, width, height } = e.data;

                    // Generate waveform path
                    const points = Math.ceil(width / 3);
                    let path = 'M 0 ' + (height / 2);
                    const midY = height / 2;

                    for (let i = 0; i <= points; i++) {
                        const x = (i / points) * width;
                        // Simulate audio amplitude with pseudo-random variation
                        const seed = (id || 0) + i;
                        const noise1 = Math.sin(seed * 0.3) * 0.3;
                        const noise2 = Math.sin(seed * 0.7) * 0.2;
                        const noise3 = Math.sin(seed * 0.1) * 0.4;
                        const envelope = Math.sin((i / points) * Math.PI) * 0.3 + 0.7;
                        const amplitude = (0.3 + noise1 + noise2 + noise3) * envelope;
                        const y = midY - (amplitude * midY * 0.8);
                        path += ' L ' + x.toFixed(2) + ' ' + y.toFixed(2);
                    }

                    // Mirror for bottom half
                    for (let i = points; i >= 0; i--) {
                        const x = (i / points) * width;
                        const seed = (id || 0) + i;
                        const noise1 = Math.sin(seed * 0.3) * 0.3;
                        const noise2 = Math.sin(seed * 0.7) * 0.2;
                        const noise3 = Math.sin(seed * 0.1) * 0.4;
                        const envelope = Math.sin((i / points) * Math.PI) * 0.3 + 0.7;
                        const amplitude = (0.3 + noise1 + noise2 + noise3) * envelope;
                        const y = midY + (amplitude * midY * 0.8);
                        path += ' L ' + x.toFixed(2) + ' ' + y.toFixed(2);
                    }

                    path += ' Z';
                    self.postMessage({ id, path });
                };
            `;

            try {
                const waveformBlob = new Blob([waveformWorkerCode], { type: 'application/javascript' });
                this._waveformWorkerUrl = URL.createObjectURL(waveformBlob);
                this.waveformWorker = new Worker(this._waveformWorkerUrl);
                this.waveformWorker.onmessage = (e) => {
                    this.$dispatch('waveform-generated', e.data);
                };
            } catch (err) {
                console.warn('Web Workers not supported, using main thread');
            }
        },

        generateWaveformAsync(id, width, height) {
            if (this.waveformWorker) {
                this.waveformWorker.postMessage({ id, width, height });
            }
        },

        terminateWorkers() {
            if (this.waveformWorker) {
                this.waveformWorker.terminate();
                this.waveformWorker = null;
            }
            if (this._waveformWorkerUrl) {
                URL.revokeObjectURL(this._waveformWorkerUrl);
                this._waveformWorkerUrl = null;
            }
            if (this.thumbnailWorker) {
                this.thumbnailWorker.terminate();
                this.thumbnailWorker = null;
            }
        },

        // ===== Phase 7: GPU Acceleration =====
        getGPUTransform(x, y = 0) {
            if (this.useGPUAcceleration) {
                return `translate3d(${x}px, ${y}px, 0)`;
            }
            return `translate(${x}px, ${y}px)`;
        },

        requestAnimationFrameThrottled(callback) {
            if (this._rafId) return;
            this._rafId = requestAnimationFrame(() => {
                callback();
                this._rafId = null;
            });
        },

        // ===== Phase 7: Touch/Mobile Support =====
        initTouchSupport() {
            this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

            // Check for reduced motion preference
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Listen for motion preference changes
            window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
                this.reducedMotion = e.matches;
            });
        },

        handleTouchStart(e, trackId = null, clipIndex = null) {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                this.touchStartX = touch.clientX;
                this.touchStartY = touch.clientY;
                this.touchStartTime = performance.now();
                this.touchMoved = false;
                this.lastTouchX = touch.clientX;
                this.lastTouchY = touch.clientY;

                // Start long press timer for context menu
                if (clipIndex !== null) {
                    this.longPressTimer = setTimeout(() => {
                        if (!this.touchMoved) {
                            this.isLongPressing = true;
                            // Trigger haptic feedback if available
                            if (navigator.vibrate) {
                                navigator.vibrate(50);
                            }
                            // Open context menu at touch position
                            this.openContextMenu({
                                clientX: touch.clientX,
                                clientY: touch.clientY,
                                preventDefault: () => {}
                            }, trackId, clipIndex);
                        }
                    }, this.longPressDuration);
                }
            } else if (e.touches.length === 2) {
                // Pinch zoom start
                this.pinchDistance = Math.hypot(
                    e.touches[1].clientX - e.touches[0].clientX,
                    e.touches[1].clientY - e.touches[0].clientY
                );
            }
        },

        handleTouchMove(e) {
            if (this.isLongPressing) {
                e.preventDefault();
                return;
            }

            if (e.touches.length === 1) {
                const touch = e.touches[0];
                const deltaX = touch.clientX - this.touchStartX;
                const deltaY = touch.clientY - this.touchStartY;

                // Calculate velocity
                const now = performance.now();
                const dt = now - this.touchStartTime;
                if (dt > 0) {
                    this.touchVelocity = Math.abs(deltaX) / dt;
                }

                // Check if moved enough to cancel long press
                if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
                    this.touchMoved = true;
                    if (this.longPressTimer) {
                        clearTimeout(this.longPressTimer);
                        this.longPressTimer = null;
                    }
                }

                // Detect swipe direction
                if (!this.swipeDirection && this.touchMoved) {
                    this.swipeDirection = Math.abs(deltaX) > Math.abs(deltaY) ? 'horizontal' : 'vertical';
                }

                // Handle horizontal swipe for timeline scrolling
                if (this.swipeDirection === 'horizontal') {
                    const container = this.$refs.timelineScroll;
                    if (container) {
                        const scrollDelta = this.lastTouchX - touch.clientX;
                        container.scrollLeft += scrollDelta;
                        this.updateVisibleClipRange();
                    }
                }

                this.lastTouchX = touch.clientX;
                this.lastTouchY = touch.clientY;
            } else if (e.touches.length === 2) {
                // Pinch zoom
                const newDistance = Math.hypot(
                    e.touches[1].clientX - e.touches[0].clientX,
                    e.touches[1].clientY - e.touches[0].clientY
                );

                if (this.pinchDistance > 0) {
                    const scale = newDistance / this.pinchDistance;
                    if (scale > 1.1) {
                        this.zoomIn();
                        this.pinchDistance = newDistance;
                    } else if (scale < 0.9) {
                        this.zoomOut();
                        this.pinchDistance = newDistance;
                    }
                }
            }
        },

        handleTouchEnd(e) {
            // Clear long press timer
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }

            if (this.isLongPressing) {
                this.isLongPressing = false;
                return;
            }

            // Check for swipe gesture
            if (this.touchMoved && this.swipeDirection === 'horizontal') {
                const deltaX = this.lastTouchX - this.touchStartX;

                // Fast swipe navigation
                if (this.touchVelocity > this.swipeVelocityThreshold && Math.abs(deltaX) > this.swipeThreshold) {
                    const container = this.$refs.timelineScroll;
                    if (container) {
                        // Add momentum scrolling
                        const momentum = deltaX * this.touchVelocity * 100;
                        container.scrollBy({
                            left: -momentum,
                            behavior: this.reducedMotion ? 'auto' : 'smooth'
                        });
                    }
                }
            }

            // Reset touch state
            this.touchMoved = false;
            this.swipeDirection = null;
            this.touchVelocity = 0;
            this.pinchDistance = 0;
        },

        handleTouchCancel() {
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            this.touchMoved = false;
            this.swipeDirection = null;
            this.isLongPressing = false;
            this.pinchDistance = 0;
        },

        // Double tap to zoom
        handleDoubleTap(e) {
            if (this.zoom === 1) {
                this.zoomIn();
                this.zoomIn();
            } else {
                this.zoom = 1;
            }
        },

        // ===== Phase 7: Performance Monitoring =====
        updateFPS() {
            const now = performance.now();
            this.fpsCounter++;

            if (now - this.lastFpsUpdate >= 1000) {
                this.currentFps = this.fpsCounter;
                this.fpsCounter = 0;
                this.lastFpsUpdate = now;
            }
        },

        // Debounced scroll handler
        debouncedScroll: null,
        setupScrollHandler() {
            this.debouncedScroll = this.debounce(() => {
                this.updateVisibleClipRange();
                this.updateMinimapViewport();
            }, 16);
        },

        debounce(func, wait) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        throttle(func, limit) {
            let inThrottle;
            return (...args) => {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
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

        // ===== Phase 3: Comprehensive Keyboard Shortcuts =====
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            const key = e.key.toLowerCase();

            // Zoom controls
            if (key === '+' || key === '=') {
                e.preventDefault();
                zoomIn();
            } else if (key === '-') {
                e.preventDefault();
                zoomOut();
            } else if (key === '0' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                zoomFit();
            }

            // Split/Cut tool
            else if ((key === 's' || key === 'b') && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                if (currentTool === 'split') {
                    setTool('select');
                } else if (selectedClip !== null || selectedClips.length > 0) {
                    splitAtPlayhead();
                } else {
                    setTool('split');
                }
            }

            // Delete selected
            else if (key === 'delete' || key === 'backspace') {
                e.preventDefault();
                deleteSelectedClips();
            }

            // Copy/Cut/Paste
            else if (key === 'c' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                copySelectedClips();
            } else if (key === 'x' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                cutSelectedClips();
            } else if (key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                pasteClips();
            }

            // Select all
            else if (key === 'a' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                selectAllClips();
            }

            // Duplicate
            else if (key === 'd' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                duplicateSelected();
            }

            // In/Out points
            else if (key === 'i' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                setInPoint();
            } else if (key === 'o' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                setOutPoint();
            }

            // JKL playback
            else if (key === 'j') {
                e.preventDefault();
                jklControl('j');
            } else if (key === 'k') {
                e.preventDefault();
                jklControl('k');
            } else if (key === 'l' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                jklControl('l');
            }

            // Navigation
            else if (key === 'home') {
                e.preventDefault();
                goToStart();
            } else if (key === 'end') {
                e.preventDefault();
                goToEnd();
            }

            // Frame stepping (exclude Alt for marker navigation)
            else if (key === 'arrowleft' && !e.altKey) {
                e.preventDefault();
                if (e.shiftKey) {
                    stepFrames(-5); // 5 frames back
                } else {
                    stepFrames(-1); // 1 frame back
                }
            } else if (key === 'arrowright' && !e.altKey) {
                e.preventDefault();
                if (e.shiftKey) {
                    stepFrames(5); // 5 frames forward
                } else {
                    stepFrames(1); // 1 frame forward
                }
            }

            // Escape to deselect/cancel
            else if (key === 'escape') {
                e.preventDefault();
                if (showContextMenu) {
                    hideContextMenu();
                } else if (showShortcutsModal) {
                    showShortcutsModal = false;
                } else if (currentTool !== 'select') {
                    setTool('select');
                } else {
                    deselectAll();
                }
            }

            // Keyboard shortcuts modal
            else if (key === '?' || (key === '/' && e.shiftKey)) {
                e.preventDefault();
                showShortcutsModal = !showShortcutsModal;
            }

            // Toggle snap
            else if (key === 'n' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                snapEnabled = !snapEnabled;
            }

            // Toggle ripple mode
            else if (key === 'r' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                toggleRippleMode();
            }

            // ===== Phase 6: Marker shortcuts =====
            // Add marker at playhead (Shift+M) - must come before plain 'm'
            else if (key === 'm' && e.shiftKey && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                addMarkerAtPlayhead();
            }

            // Toggle minimap (plain M only)
            else if (key === 'm' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                e.preventDefault();
                showMinimap = !showMinimap;
            }

            // Zoom to selection
            else if (key === 'f' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                if (selectedClips.length > 0) {
                    zoomToSelection();
                } else {
                    zoomFit();
                }
            }

            // Navigate markers (Shift+Left/Right)
            else if (key === 'arrowleft' && e.shiftKey && e.altKey) {
                e.preventDefault();
                goToPrevMarker();
            } else if (key === 'arrowright' && e.shiftKey && e.altKey) {
                e.preventDefault();
                goToNextMarker();
            }

            // Toggle transitions panel (T)
            else if (key === 't' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                e.preventDefault();
                showTransitionLibrary = !showTransitionLibrary;
            }
        });

        // ===== Phase 5: Wheel Zoom Handler =====
        const timelineScroll = $refs.timelineScroll;
        if (timelineScroll) {
            timelineScroll.addEventListener('wheel', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    handleWheelZoom(e);
                }
            }, { passive: false });

            // Initialize minimap viewport on resize
            // Note: scroll handler is set up in Phase 7 initialization below
            _resizeObserver = new ResizeObserver(() => {
                updateMinimapViewport();
            });
            _resizeObserver.observe(timelineScroll);
        }

        // ===== Phase 5: Touch/Pinch Zoom Handlers =====
        const tracksContainer = $refs.tracksContainer;
        if (tracksContainer) {
            tracksContainer.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    startPinchZoom(e);
                }
            }, { passive: true });

            tracksContainer.addEventListener('touchmove', (e) => {
                if (isPinchZooming) {
                    handlePinchZoom(e);
                }
            }, { passive: false });

            tracksContainer.addEventListener('touchend', () => {
                if (isPinchZooming) {
                    endPinchZoom();
                }
            });
        }

        // Initialize minimap viewport
        $nextTick(() => {
            updateMinimapViewport();
            updateShuttleDisplay();
        });

        // ===== Phase 7: Performance Initialization =====
        // Initialize touch support
        initTouchSupport();

        // Initialize web workers
        initWebWorkers();

        // Initialize virtual scrolling
        updateVisibleClipRange();
        setupScrollHandler();

        // Add optimized scroll handler
        if (timelineScroll) {
            timelineScroll.addEventListener('scroll', () => {
                if (debouncedScroll) debouncedScroll();
            }, { passive: true });
        }

        // ===== Phase E: Testing Hooks =====
        // Expose component state for E2E testing (only in test environments)
        if (window.Cypress || window.__TESTING__ || document.querySelector('[data-test-mode]')) {
            window.__timelineTestAPI = {
                // State getters
                getState: () => ({
                    currentTime,
                    totalDuration,
                    zoom,
                    selectedClips: [...selectedClips],
                    selectedClip,
                    selectedTrack,
                    currentTool,
                    rippleMode,
                    snapEnabled,
                    markers: [...markers],
                    canUndo,
                    canRedo,
                    historyIndex,
                    historyLength: history.length
                }),

                // Actions for testing
                actions: {
                    seek: (time) => seek(time),
                    selectClip: (track, index) => selectClip(track, index),
                    deleteSelected: () => deleteSelectedClips(),
                    undo: () => undo(),
                    redo: () => redo(),
                    setTool: (tool) => setTool(tool),
                    toggleRipple: () => toggleRippleMode(),
                    toggleSnap: () => { snapEnabled = !snapEnabled; },
                    addMarker: (time, color, name) => addMarker(time, color, name),
                    splitAtPlayhead: () => splitAtPlayhead(),
                    zoomIn: () => zoomIn(),
                    zoomOut: () => zoomOut(),
                    zoomFit: () => zoomFit()
                },

                // DOM queries
                getElements: () => ({
                    toolbar: document.querySelector('[data-testid=timeline-toolbar]'),
                    playhead: document.querySelector('[data-testid=playhead]'),
                    clips: document.querySelectorAll('.vw-clip'),
                    markers: document.querySelectorAll('.vw-timeline-marker')
                })
            };
        }

        // Cleanup on unmount
        window.addEventListener('beforeunload', () => {
            terminateWorkers();
            if (_resizeObserver) {
                _resizeObserver.disconnect();
            }
            // Clean up test API
            if (window.__timelineTestAPI) {
                delete window.__timelineTestAPI;
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
        <div class="vw-toolbar-section vw-toolbar-center" data-testid="timeline-toolbar">
            <div class="vw-tool-group">
                <button
                    type="button"
                    @click="undo()"
                    :disabled="!canUndo"
                    class="vw-tool-btn"
                    title="{{ __('Undo') }} (Ctrl+Z)"
                    data-testid="btn-undo"
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
                    data-testid="btn-redo"
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
                    :aria-pressed="snapEnabled"
                    class="vw-tool-btn vw-snap-btn"
                    title="{{ __('Magnetic Snap') }}"
                    aria-label="{{ __('Toggle Magnetic Snap') }}"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
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
                :aria-pressed="rippleMode"
                class="vw-tool-btn vw-ripple-btn"
                title="{{ __('Ripple Edit Mode') }} - Auto-shift clips"
                aria-label="{{ __('Toggle Ripple Edit Mode') }}"
                data-testid="btn-ripple"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M2 12h4l3-9 4 18 3-9h6"/>
                </svg>
                <span>{{ __('Ripple') }}</span>
            </button>

            <div class="vw-toolbar-divider"></div>

            {{-- Phase 3: Tool Selection --}}
            <div class="vw-tool-group vw-tool-selector" role="radiogroup" aria-label="{{ __('Timeline Tools') }}" data-testid="tool-selector">
                <button
                    type="button"
                    @click="setTool('select')"
                    :class="{ 'is-active': currentTool === 'select' }"
                    :aria-pressed="currentTool === 'select'"
                    class="vw-tool-btn"
                    title="{{ __('Selection Tool') }} (V)"
                    aria-label="{{ __('Selection Tool') }}"
                    role="radio"
                    :aria-checked="currentTool === 'select'"
                    data-testid="btn-tool-select"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="setTool('split')"
                    :class="{ 'is-active': currentTool === 'split' }"
                    :aria-pressed="currentTool === 'split'"
                    class="vw-tool-btn"
                    title="{{ __('Split Tool') }} (S)"
                    aria-label="{{ __('Split Tool') }}"
                    role="radio"
                    :aria-checked="currentTool === 'split'"
                    data-testid="btn-tool-split"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="2" x2="12" y2="22"/>
                        <path d="M8 6l4-4 4 4M8 18l4 4 4-4"/>
                    </svg>
                </button>
            </div>

            <div class="vw-toolbar-divider"></div>

            {{-- Split at Playhead --}}
            <button
                type="button"
                @click="splitAtPlayhead()"
                :disabled="currentTime <= 0 || currentTime >= totalDuration"
                class="vw-tool-btn"
                title="{{ __('Split at Playhead') }} (S)"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <polyline points="8 9 12 5 16 9"/>
                    <polyline points="8 15 12 19 16 15"/>
                </svg>
            </button>

            {{-- Delete Selected --}}
            <button
                type="button"
                @click="deleteSelectedClips()"
                :disabled="selectedClips.length === 0 && selectedClip === null"
                class="vw-tool-btn vw-tool-danger"
                title="{{ __('Delete Selected') }} (Del)"
                data-testid="btn-delete"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>

            <div class="vw-toolbar-divider"></div>

            {{-- Clipboard Actions --}}
            <div class="vw-tool-group">
                <button
                    type="button"
                    @click="copySelectedClips()"
                    :disabled="selectedClips.length === 0"
                    class="vw-tool-btn"
                    title="{{ __('Copy') }} (Ctrl+C)"
                    data-testid="btn-copy"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="pasteClips()"
                    :disabled="clipboard.length === 0"
                    class="vw-tool-btn"
                    title="{{ __('Paste') }} (Ctrl+V)"
                    data-testid="btn-paste"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                    </svg>
                </button>
            </div>

            <div class="vw-toolbar-divider"></div>

            {{-- ===== Phase 6: Markers Button ===== --}}
            <div class="vw-marker-controls">
                <button
                    type="button"
                    @click="addMarkerAtPlayhead()"
                    class="vw-tool-btn vw-marker-btn"
                    title="{{ __('Add Marker') }} (Shift+M)"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="showMarkerPanel = !showMarkerPanel"
                    :class="{ 'is-active': showMarkerPanel || markers.length > 0 }"
                    class="vw-tool-btn vw-markers-list-btn"
                    title="{{ __('Markers Panel') }}"
                >
                    <span class="vw-marker-count" x-show="markers.length > 0" x-text="markers.length"></span>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
            </div>

            {{-- ===== Phase 6: Transitions Library Button ===== --}}
            <button
                type="button"
                @click="showTransitionLibrary = !showTransitionLibrary"
                :class="{ 'is-active': showTransitionLibrary }"
                class="vw-tool-btn vw-transitions-btn"
                title="{{ __('Transitions Library') }} (T)"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                    <path d="M10 7h4M14 7l-2 2-2-2M14 17h-4M10 17l2-2 2 2"/>
                </svg>
            </button>

            <div class="vw-toolbar-divider"></div>

            {{-- Keyboard Shortcuts Help --}}
            <button
                type="button"
                @click="showShortcutsModal = true"
                class="vw-tool-btn"
                title="{{ __('Keyboard Shortcuts') }} (?)"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2"/>
                    <path d="M6 8h.001M10 8h.001M14 8h.001M18 8h.001M8 12h.001M12 12h.001M16 12h.001M6 16h12"/>
                </svg>
            </button>
        </div>

        {{-- Right: Zoom & Time --}}
        <div class="vw-toolbar-section vw-toolbar-right">
            {{-- Phase 5: Minimap Toggle --}}
            <button
                type="button"
                @click="showMinimap = !showMinimap"
                :class="{ 'is-active': showMinimap }"
                class="vw-tool-btn vw-minimap-toggle"
                title="{{ __('Toggle Minimap') }} (M)"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 15h18"/>
                    <rect x="5" y="5" width="4" height="8" rx="1" fill="currentColor" opacity="0.3"/>
                    <rect x="10" y="5" width="4" height="8" rx="1" fill="currentColor" opacity="0.3"/>
                    <rect x="15" y="5" width="4" height="8" rx="1" fill="currentColor" opacity="0.3"/>
                </svg>
            </button>

            <div class="vw-toolbar-divider"></div>

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

                {{-- Phase 5: Zoom Presets Dropdown --}}
                <div class="vw-zoom-presets" x-data="{ open: false }" @click.away="open = false">
                    <button
                        type="button"
                        @click="open = !open"
                        class="vw-zoom-preset-btn"
                        title="{{ __('Zoom Presets') }}"
                    >
                        <span x-text="Math.round(zoom * 100) + '%'">100%</span>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
                    </button>
                    <div class="vw-zoom-preset-menu" x-show="open" x-cloak x-transition>
                        <template x-for="preset in zoomPresets" :key="preset.value">
                            <button
                                type="button"
                                class="vw-zoom-preset-item"
                                :class="{ 'is-active': zoom === preset.value }"
                                @click="setZoomLevel(preset.value); open = false"
                            >
                                <span x-text="preset.label"></span>
                                <svg x-show="zoom === preset.value" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            </button>
                        </template>
                        <div class="vw-zoom-preset-divider"></div>
                        <button
                            type="button"
                            class="vw-zoom-preset-item"
                            @click="zoomFit(); open = false"
                        >
                            <span>{{ __('Fit Project') }}</span>
                            <span class="vw-shortcut-hint">0</span>
                        </button>
                        <button
                            type="button"
                            class="vw-zoom-preset-item"
                            :disabled="selectedClips.length === 0"
                            @click="zoomToSelection(); open = false"
                        >
                            <span>{{ __('Zoom to Selection') }}</span>
                            <span class="vw-shortcut-hint">F</span>
                        </button>
                    </div>
                </div>

                <div class="vw-zoom-slider-container">
                    <input
                        type="range"
                        class="vw-zoom-slider"
                        min="0"
                        :max="zoomLevels.length - 1"
                        :value="zoomLevels.indexOf(zoom)"
                        @input="setZoomLevel(zoomLevels[$event.target.value])"
                    >
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
        {{-- ===== Phase 4: Enhanced Track Headers ===== --}}
        <div class="vw-track-headers" :class="{ 'vw-headers-expanded': expandedHeaders }">
            {{-- Ruler Header --}}
            <div class="vw-ruler-header">
                <button
                    type="button"
                    class="vw-expand-headers-btn"
                    @click="expandedHeaders = !expandedHeaders"
                    :title="expandedHeaders ? '{{ __('Compact headers') }}' : '{{ __('Expand headers') }}'"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" :class="{ 'is-rotated': !expandedHeaders }">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                </button>
            </div>

            {{-- Track Headers (in order) --}}
            <template x-for="[trackId, track] in visibleTracks" :key="trackId">
                <div
                    class="vw-track-header"
                    :class="{
                        'is-collapsed': track.collapsed,
                        'is-muted': track.muted,
                        'is-solo': track.solo,
                        'is-locked': track.locked,
                        'is-dragging': isDraggingTrack && dragTrackId === trackId
                    }"
                    :style="{ height: track.height + 'px', '--track-color': track.color }"
                    @click.away="closeTrackMenu()"
                >
                    {{-- Color bar / Drag handle --}}
                    <div
                        class="vw-header-color-bar"
                        @mousedown.stop="startTrackDrag($event, trackId)"
                        :title="'{{ __('Drag to reorder') }}'"
                    >
                        <div class="vw-drag-grip">
                            <span></span><span></span><span></span>
                        </div>
                    </div>

                    {{-- Main Header Content --}}
                    <div class="vw-header-main">
                        {{-- Top Row: Icon, Label, Short Label --}}
                        <div class="vw-header-top">
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
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 4H5c-1.11 0-2 .9-2 2v12c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 7H9.5v-.5h-2v3h2V13H11v1c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1h3c.55 0 1 .45 1 1v1z"/></svg>
                                </template>
                            </span>
                            <span class="vw-header-label" x-text="expandedHeaders ? track.label : track.shortLabel"></span>
                        </div>

                        {{-- Control Buttons Row --}}
                        <div class="vw-header-controls" x-show="!track.collapsed">
                            {{-- Mute Button --}}
                            <button
                                type="button"
                                class="vw-header-btn vw-btn-mute"
                                :class="{ 'is-active': track.muted }"
                                @click.stop="toggleTrackMute(trackId)"
                                :title="track.muted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}'"
                            >
                                <span class="vw-btn-label">M</span>
                            </button>

                            {{-- Solo Button --}}
                            <button
                                type="button"
                                class="vw-header-btn vw-btn-solo"
                                :class="{ 'is-active': track.solo }"
                                @click.stop="toggleTrackSolo(trackId)"
                                title="{{ __('Solo') }}"
                            >
                                <span class="vw-btn-label">S</span>
                            </button>

                            {{-- Lock Button --}}
                            <button
                                type="button"
                                class="vw-header-btn vw-btn-lock"
                                :class="{ 'is-active': track.locked }"
                                @click.stop="toggleTrackLock(trackId)"
                                :title="track.locked ? '{{ __('Unlock') }}' : '{{ __('Lock') }}'"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <template x-if="!track.locked">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h2c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                    </template>
                                    <template x-if="track.locked">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                    </template>
                                </svg>
                            </button>

                            {{-- Visibility Button --}}
                            <button
                                type="button"
                                class="vw-header-btn vw-btn-visibility"
                                @click.stop="toggleTrackVisibility(trackId)"
                                title="{{ __('Hide Track') }}"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            </button>
                        </div>

                        {{-- Volume Slider (for audio tracks, when expanded) --}}
                        <div class="vw-header-volume" x-show="expandedHeaders && !track.collapsed && (track.type === 'audio' || trackId === 'voiceover' || trackId === 'music')">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="vw-volume-icon"><path d="M3 9v6h4l5 5V4L7 9H3z"/></svg>
                            <input
                                type="range"
                                class="vw-volume-slider"
                                min="0"
                                max="100"
                                :value="track.volume"
                                @input="setTrackVolume(trackId, parseInt($event.target.value))"
                            >
                            <span class="vw-volume-value" x-text="track.volume + '%'"></span>
                        </div>
                    </div>

                    {{-- Collapse/Expand Button --}}
                    <button
                        type="button"
                        class="vw-header-collapse"
                        @click.stop="toggleTrackCollapse(trackId)"
                        :title="track.collapsed ? '{{ __('Expand') }}' : '{{ __('Collapse') }}'"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" :class="{ 'is-collapsed': track.collapsed }">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>

                    {{-- Resize Handle (between tracks) --}}
                    <div
                        class="vw-track-resize-handle"
                        @mousedown.stop="startTrackResize($event, trackId)"
                        @dblclick="resetTrackHeight(trackId)"
                        title="{{ __('Drag to resize, double-click to reset') }}"
                    ></div>
                </div>
            </template>
        </div>

        {{-- Scrollable Timeline Area --}}
        <div class="vw-timeline-scroll" x-ref="timelineScroll" @scroll="scrollLeft = $el.scrollLeft">
            {{-- Time Ruler - Phase 5: Enhanced with Scrubbing --}}
            <div
                class="vw-time-ruler"
                x-ref="timelineRuler"
                :style="{ width: timelineWidth + 'px' }"
                :class="{ 'is-scrubbing': isScrubbing }"
                @click="seekToPosition($event)"
                @mousedown="startScrub($event)"
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

                {{-- ===== Phase 6: In/Out Point Visual Markers on Ruler ===== --}}
                <div
                    class="vw-io-marker vw-in-marker"
                    x-show="inPoint !== null && showIORegion"
                    x-cloak
                    :style="{ left: timeToPixels(inPoint) + 'px' }"
                    @click.stop="seek(inPoint)"
                    title="{{ __('In Point') }}"
                >
                    <svg viewBox="0 0 8 16" fill="currentColor">
                        <path d="M0 0v16l8-8z"/>
                    </svg>
                </div>
                <div
                    class="vw-io-marker vw-out-marker"
                    x-show="outPoint !== null && showIORegion"
                    x-cloak
                    :style="{ left: timeToPixels(outPoint) + 'px' }"
                    @click.stop="seek(outPoint)"
                    title="{{ __('Out Point') }}"
                >
                    <svg viewBox="0 0 8 16" fill="currentColor">
                        <path d="M8 0v16l-8-8z"/>
                    </svg>
                </div>

                {{-- I/O Region Highlight on Ruler --}}
                <div
                    class="vw-io-region-ruler"
                    x-show="inPoint !== null && outPoint !== null && showIORegion"
                    x-cloak
                    :style="{
                        left: timeToPixels(Math.min(inPoint, outPoint)) + 'px',
                        width: (timeToPixels(Math.max(inPoint, outPoint)) - timeToPixels(Math.min(inPoint, outPoint))) + 'px'
                    }"
                ></div>

                {{-- ===== Phase 6: Markers on Ruler ===== --}}
                <template x-for="marker in markers" :key="marker.id">
                    <div
                        class="vw-ruler-marker"
                        :class="{ 'is-selected': selectedMarker === marker.id }"
                        :style="{ left: timeToPixels(marker.time) + 'px', '--marker-color': marker.color }"
                        @click.stop="selectMarker(marker.id)"
                        @dblclick.stop="editingMarker = marker.id; showMarkerPanel = true"
                        @contextmenu="openMarkerMenu($event, marker.id)"
                        :title="marker.name"
                    >
                        <div class="vw-marker-flag">
                            <svg viewBox="0 0 10 14" fill="currentColor">
                                <path d="M0 0h10l-3 5 3 5H0z"/>
                            </svg>
                        </div>
                        <div class="vw-marker-line"></div>
                    </div>
                </template>

                {{-- Playhead Top Marker --}}
                <div
                    class="vw-playhead-top"
                    :style="{ left: timeToPixels(currentTime) + 'px' }"
                    :class="{ 'is-dragging': isPlayheadDragging }"
                    @mousedown="startPlayheadDrag($event)"
                    role="slider"
                    tabindex="0"
                    aria-label="{{ __('Timeline playhead') }}"
                    :aria-valuenow="Math.round(currentTime * 100) / 100"
                    aria-valuemin="0"
                    :aria-valuemax="totalDuration"
                    :aria-valuetext="formatTime(currentTime) + ' {{ __('of') }} ' + formatTime(totalDuration)"
                    @keydown.left.prevent="seek(Math.max(0, currentTime - 1))"
                    @keydown.right.prevent="seek(Math.min(totalDuration, currentTime + 1))"
                    @keydown.home.prevent="seek(0)"
                    @keydown.end.prevent="seek(totalDuration)"
                    data-testid="playhead"
                >
                    <div class="vw-playhead-handle">
                        <svg viewBox="0 0 12 16" fill="currentColor" aria-hidden="true">
                            <path d="M0 0h12v10l-6 6-6-6z"/>
                        </svg>
                    </div>
                    {{-- Time tooltip during drag --}}
                    <div class="vw-playhead-tooltip" x-show="isPlayheadDragging" x-cloak role="tooltip">
                        <span x-text="formatTimeDetailed(playheadTooltipTime)"></span>
                    </div>
                </div>
            </div>

            {{-- Tracks Container --}}
            <div class="vw-tracks-container"
                 x-ref="tracksContainer"
                 :style="{ width: timelineWidth + 'px' }"
                 :class="{ 'vw-split-cursor': currentTool === 'split' }"
                 @mousemove="updateSplitCursor($event)"
                 @mousedown="if (currentTool === 'select' && !$event.target.closest('.vw-clip')) startMarquee($event)"
                 @click="if (currentTool === 'split' && splitCursorPosition) splitAtPosition(pixelsToTime(splitCursorPosition))"
                 @touchstart.passive="handleTouchStart($event, null, null)"
                 @touchmove.passive="handleTouchMove($event)"
                 @touchend="handleTouchEnd($event)"
                 @touchcancel="handleTouchCancel()"
            >
                {{-- Video Track --}}
                <div
                    class="vw-track vw-track-video"
                    data-track="video"
                    :class="{ 'is-locked': tracks.video.locked, 'is-muted': tracks.video.muted, 'is-drop-target': isDragging && dropZoneTrack === 'video' }"
                    x-show="tracks.video.visible"
                    :style="{ height: tracks.video.height + 'px' }"
                >
                    @foreach($script['scenes'] ?? [] as $index => $scene)
                        @php
                            // Match getPreviewScenes() logic: visualDuration -> duration -> default 8
                            $sceneStart = 0;
                            for ($i = 0; $i < $index; $i++) {
                                $sceneStart += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
                            }
                            $sceneDuration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
                            $thumbnail = $storyboard['scenes'][$index]['image'] ?? null;
                        @endphp
                        <div
                            class="vw-clip vw-clip-video"
                            data-clip-index="{{ $index }}"
                            :class="{
                                'is-selected': isClipSelected('video', {{ $index }}),
                                'is-hovered': hoveredTrack === 'video' && hoveredClip === {{ $index }},
                                'is-dragging': isDragging && dragTarget?.track === 'video' && dragTarget?.index === {{ $index }},
                                'is-ripple-affected': rippleMode && affectedClips.some(c => c.track === 'video' && c.index === {{ $index }}),
                                'is-cut': clipboardOperation === 'cut' && clipboard.some(c => c.track === 'video' && c.index === {{ $index }}),
                                'is-long-pressing': isLongPressing && hoveredTrack === 'video' && hoveredClip === {{ $index }}
                            }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $sceneDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('video', {{ $index }}, $event)"
                            @contextmenu.prevent="openContextMenu($event, 'video', {{ $index }})"
                            @mouseenter="hoveredTrack = 'video'; hoveredClip = {{ $index }}"
                            @mouseleave="hoveredTrack = null; hoveredClip = null"
                            @mousedown.stop="if (!tracks.video.locked && $event.target.closest('.vw-trim-handle') === null && $event.button === 0) startDrag($event, 'move', { track: 'video', index: {{ $index }} }, {{ $sceneStart }}, {{ $sceneDuration }}, {{ $sceneStart }})"
                            @touchstart.stop="hoveredTrack = 'video'; hoveredClip = {{ $index }}; handleTouchStart($event, 'video', {{ $index }})"
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
                    data-track="voiceover"
                    x-show="tracks.voiceover.visible"
                    :style="{ height: tracks.voiceover.height + 'px' }"
                >
                    @foreach($script['scenes'] ?? [] as $index => $scene)
                        @php
                            // Match getPreviewScenes() logic: visualDuration -> duration -> default 8
                            $sceneStart = 0;
                            for ($i = 0; $i < $index; $i++) {
                                $sceneStart += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
                            }
                            $voiceoverDuration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
                        @endphp
                        <div
                            class="vw-clip vw-clip-audio"
                            data-clip-index="{{ $index }}"
                            :class="{
                                'is-selected': isClipSelected('voiceover', {{ $index }}),
                                'is-hovered': hoveredTrack === 'voiceover' && hoveredClip === {{ $index }},
                                'is-cut': clipboardOperation === 'cut' && clipboard.some(c => c.track === 'voiceover' && c.index === {{ $index }}),
                                'is-long-pressing': isLongPressing && hoveredTrack === 'voiceover' && hoveredClip === {{ $index }}
                            }"
                            :style="{
                                left: timeToPixels({{ $sceneStart }}) + 'px',
                                width: timeToPixels({{ $voiceoverDuration }}) + 'px'
                            }"
                            @click.stop="selectClip('voiceover', {{ $index }}, $event)"
                            @contextmenu.prevent="openContextMenu($event, 'voiceover', {{ $index }})"
                            @mouseenter="hoveredTrack = 'voiceover'; hoveredClip = {{ $index }}"
                            @mouseleave="hoveredTrack = null; hoveredClip = null"
                            @touchstart.stop="hoveredTrack = 'voiceover'; hoveredClip = {{ $index }}; handleTouchStart($event, 'voiceover', {{ $index }})"
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
                    data-track="music"
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
                    data-track="captions"
                    x-show="tracks.captions.visible"
                    :style="{ height: tracks.captions.height + 'px' }"
                >
                    @if($assembly['captions']['enabled'] ?? true)
                        @foreach($script['scenes'] ?? [] as $index => $scene)
                            @php
                                // Match getPreviewScenes() logic: visualDuration -> duration -> default 8
                                $sceneStart = 0;
                                for ($i = 0; $i < $index; $i++) {
                                    $sceneStart += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
                                }
                                $captionDuration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
                                $captionText = Str::limit($scene['narration'] ?? '', 40);
                            @endphp
                            <div
                                class="vw-clip vw-clip-caption"
                                data-clip-index="{{ $index }}"
                                :class="{
                                    'is-selected': isClipSelected('captions', {{ $index }}),
                                    'is-hovered': hoveredTrack === 'captions' && hoveredClip === {{ $index }},
                                    'is-cut': clipboardOperation === 'cut' && clipboard.some(c => c.track === 'captions' && c.index === {{ $index }}),
                                    'is-long-pressing': isLongPressing && hoveredTrack === 'captions' && hoveredClip === {{ $index }}
                                }"
                                :style="{
                                    left: timeToPixels({{ $sceneStart }}) + 'px',
                                    width: timeToPixels({{ $captionDuration }}) + 'px'
                                }"
                                @click.stop="selectClip('captions', {{ $index }}, $event)"
                                @contextmenu.prevent="openContextMenu($event, 'captions', {{ $index }})"
                                @mouseenter="hoveredTrack = 'captions'; hoveredClip = {{ $index }}"
                                @mouseleave="hoveredTrack = null; hoveredClip = null"
                                @touchstart.stop="hoveredTrack = 'captions'; hoveredClip = {{ $index }}; handleTouchStart($event, 'captions', {{ $index }})"
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

    {{-- ===== Phase 5: Timeline Minimap ===== --}}
    <div
        class="vw-timeline-minimap"
        x-ref="minimap"
        x-show="showMinimap"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        :style="{ height: minimapHeight + 'px' }"
        @click="minimapNavigate($event)"
    >
        {{-- Minimap Header --}}
        <div class="vw-minimap-header">
            <span class="vw-minimap-label">{{ __('Overview') }}</span>
            <span class="vw-minimap-time" x-text="formatTime(0) + ' - ' + formatTime(totalDuration)"></span>
        </div>

        {{-- Minimap Track Area --}}
        <div class="vw-minimap-tracks">
            {{-- Video Track Mini Clips --}}
            <div class="vw-minimap-track vw-minimap-video">
                @foreach($script['scenes'] ?? [] as $index => $scene)
                    @php
                        // Match getPreviewScenes() logic: visualDuration -> duration -> default 8
                        $sceneStart = 0;
                        for ($i = 0; $i < $index; $i++) {
                            $sceneStart += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
                        }
                        $sceneDuration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
                    @endphp
                    <div
                        class="vw-minimap-clip"
                        :style="{
                            left: ({{ $sceneStart }} / totalDuration * 100) + '%',
                            width: ({{ $sceneDuration }} / totalDuration * 100) + '%'
                        }"
                    ></div>
                @endforeach
            </div>

            {{-- Audio Track Mini Clips --}}
            <div class="vw-minimap-track vw-minimap-audio">
                @foreach($script['scenes'] ?? [] as $index => $scene)
                    @php
                        // Match getPreviewScenes() logic: visualDuration -> duration -> default 8
                        $sceneStart = 0;
                        for ($i = 0; $i < $index; $i++) {
                            $sceneStart += ($script['scenes'][$i]['visualDuration'] ?? $script['scenes'][$i]['duration'] ?? 8);
                        }
                        $sceneDuration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;
                    @endphp
                    <div
                        class="vw-minimap-clip"
                        :style="{
                            left: ({{ $sceneStart }} / totalDuration * 100) + '%',
                            width: ({{ $sceneDuration }} / totalDuration * 100) + '%'
                        }"
                    ></div>
                @endforeach
            </div>

            {{-- Viewport Indicator --}}
            <div
                class="vw-minimap-viewport"
                :class="{ 'is-dragging': minimapDragging }"
                :style="{
                    left: minimapViewportLeft + 'px',
                    width: Math.max(20, minimapViewportWidth) + 'px'
                }"
                @mousedown.stop="startMinimapDrag($event)"
            >
                <div class="vw-viewport-handle vw-viewport-left"></div>
                <div class="vw-viewport-handle vw-viewport-right"></div>
            </div>

            {{-- Playhead Position in Minimap --}}
            <div
                class="vw-minimap-playhead"
                :style="{ left: (currentTime / totalDuration * 100) + '%' }"
            ></div>

            {{-- In/Out Points in Minimap --}}
            <div
                class="vw-minimap-in-point"
                x-show="inPoint !== null"
                x-cloak
                :style="{ left: (inPoint / totalDuration * 100) + '%' }"
            ></div>
            <div
                class="vw-minimap-out-point"
                x-show="outPoint !== null"
                x-cloak
                :style="{ left: (outPoint / totalDuration * 100) + '%' }"
            ></div>
            <div
                class="vw-minimap-range"
                x-show="inPoint !== null && outPoint !== null"
                x-cloak
                :style="{
                    left: (Math.min(inPoint, outPoint) / totalDuration * 100) + '%',
                    width: (Math.abs(outPoint - inPoint) / totalDuration * 100) + '%'
                }"
            ></div>
        </div>
    </div>

    {{-- ===== Phase 5: Shuttle & Transport Controls ===== --}}
    <div class="vw-transport-bar">
        {{-- Audio Scrub Toggle --}}
        <button
            type="button"
            @click="toggleAudioScrub()"
            :class="{ 'is-active': audioScrubEnabled }"
            class="vw-transport-btn vw-audio-scrub-btn"
            title="{{ __('Audio Scrub') }}"
        >
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
            </svg>
        </button>

        <div class="vw-transport-divider"></div>

        {{-- Shuttle Controls --}}
        <div class="vw-shuttle-controls">
            {{-- Reverse --}}
            <button
                type="button"
                @click="shuttleReverse()"
                class="vw-transport-btn vw-shuttle-btn"
                :class="{ 'is-active': jklSpeed < 0 }"
                title="{{ __('Reverse') }} (J)"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z"/>
                </svg>
            </button>

            {{-- Stop/Pause --}}
            <button
                type="button"
                @click="shuttleStop()"
                class="vw-transport-btn vw-shuttle-stop"
                :class="{ 'is-active': jklSpeed === 0 }"
                title="{{ __('Stop') }} (K)"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                </svg>
            </button>

            {{-- Forward --}}
            <button
                type="button"
                @click="shuttleForward()"
                class="vw-transport-btn vw-shuttle-btn"
                :class="{ 'is-active': jklSpeed > 0 }"
                title="{{ __('Forward') }} (L)"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4 18l8.5-6L4 6v12zm9-12v12l8.5-6L13 6z"/>
                </svg>
            </button>

            {{-- Shuttle Speed Display --}}
            <div class="vw-shuttle-display" :class="{ 'is-active': jklSpeed !== 0 }">
                <span x-text="shuttleRateDisplay">▶ ||</span>
            </div>
        </div>

        <div class="vw-transport-divider"></div>

        {{-- Frame Step Controls --}}
        <div class="vw-frame-controls">
            <button
                type="button"
                @click="stepFrames(-1)"
                class="vw-transport-btn"
                title="{{ __('Previous Frame') }} (←)"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="stepFrames(1)"
                class="vw-transport-btn"
                title="{{ __('Next Frame') }} (→)"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                </svg>
            </button>
        </div>

        <div class="vw-transport-divider"></div>

        {{-- In/Out Points --}}
        <div class="vw-io-controls">
            <button
                type="button"
                @click="setInPoint()"
                :class="{ 'is-set': inPoint !== null }"
                class="vw-transport-btn vw-in-btn"
                title="{{ __('Set In Point') }} (I)"
            >
                <span>I</span>
            </button>
            <button
                type="button"
                @click="setOutPoint()"
                :class="{ 'is-set': outPoint !== null }"
                class="vw-transport-btn vw-out-btn"
                title="{{ __('Set Out Point') }} (O)"
            >
                <span>O</span>
            </button>
            <button
                type="button"
                @click="clearInOutPoints()"
                :disabled="inPoint === null && outPoint === null"
                class="vw-transport-btn vw-clear-io"
                title="{{ __('Clear In/Out') }}"
            >
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        {{-- Spacer --}}
        <div class="vw-transport-spacer"></div>

        {{-- Current Position --}}
        <div class="vw-transport-time">
            <span class="vw-current-time" x-text="formatTimeDetailed(currentTime)">0:00.0</span>
            <span class="vw-time-separator">/</span>
            <span class="vw-total-time" x-text="formatTime(totalDuration)">0:00</span>
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
            <button type="button" class="vw-inspector-action vw-action-danger" title="{{ __('Delete') }}" @click="deleteSelectedClips()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ===== Phase 3: Context Menu ===== --}}
    <div
        class="vw-context-menu"
        x-show="showContextMenu"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="{ left: contextMenuX + 'px', top: contextMenuY + 'px' }"
        @click.stop
    >
        <button type="button" class="vw-context-item" @click="contextMenuAction('cut')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                <line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/>
                <line x1="8.12" y1="8.12" x2="12" y2="12"/>
            </svg>
            <span>{{ __('Cut') }}</span>
            <kbd>Ctrl+X</kbd>
        </button>
        <button type="button" class="vw-context-item" @click="contextMenuAction('copy')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            <span>{{ __('Copy') }}</span>
            <kbd>Ctrl+C</kbd>
        </button>
        <button type="button" class="vw-context-item" @click="contextMenuAction('paste')" :disabled="clipboard.length === 0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
            </svg>
            <span>{{ __('Paste') }}</span>
            <kbd>Ctrl+V</kbd>
        </button>
        <div class="vw-context-divider"></div>
        <button type="button" class="vw-context-item" @click="contextMenuAction('split')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <polyline points="8 9 12 5 16 9"/><polyline points="8 15 12 19 16 15"/>
            </svg>
            <span>{{ __('Split at Playhead') }}</span>
            <kbd>S</kbd>
        </button>
        <button type="button" class="vw-context-item" @click="contextMenuAction('duplicate')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            <span>{{ __('Duplicate') }}</span>
            <kbd>Ctrl+D</kbd>
        </button>
        <div class="vw-context-divider"></div>
        <button type="button" class="vw-context-item vw-context-danger" @click="contextMenuAction('delete')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            <span>{{ __('Delete') }}</span>
            <kbd>Del</kbd>
        </button>
    </div>

    {{-- ===== Phase 3: Split Cursor Line ===== --}}
    <div
        class="vw-split-cursor-line"
        x-show="currentTool === 'split' && splitCursorPosition !== null"
        x-cloak
        :style="{ left: splitCursorPosition + 'px' }"
    >
        <div class="vw-split-cursor-head">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L8 6h3v5H8l4 4 4-4h-3V6h3l-4-4zM12 22l4-4h-3v-5h3l-4-4-4 4h3v5H8l4 4z"/>
            </svg>
        </div>
    </div>

    {{-- ===== Phase 3: Marquee Selection Box ===== --}}
    <div
        class="vw-marquee-box"
        x-show="showMarquee"
        x-cloak
        :style="marqueeStyle"
    ></div>

    {{-- ===== Phase 3: In/Out Point Markers ===== --}}
    <div
        class="vw-in-point-marker"
        x-show="inPoint !== null"
        x-cloak
        :style="{ left: timeToPixels(inPoint) + 'px' }"
        title="{{ __('In Point') }}"
    >
        <span class="vw-point-label">I</span>
    </div>
    <div
        class="vw-out-point-marker"
        x-show="outPoint !== null"
        x-cloak
        :style="{ left: timeToPixels(outPoint) + 'px' }"
        title="{{ __('Out Point') }}"
    >
        <span class="vw-point-label">O</span>
    </div>

    {{-- ===== Phase 3: Selection Count Badge ===== --}}
    <div
        class="vw-selection-badge"
        x-show="selectedClips.length > 1"
        x-cloak
        x-transition
    >
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l4.59-4.58L18 11l-6 6z"/></svg>
        <span x-text="selectedClips.length + ' {{ __('clips selected') }}'"></span>
    </div>

    {{-- ===== Phase 6: Markers Panel ===== --}}
    <div
        class="vw-markers-panel"
        x-show="showMarkerPanel"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-x-4"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-4"
    >
        <div class="vw-markers-header">
            <h3>{{ __('Markers & Chapters') }}</h3>
            <div class="vw-markers-actions">
                <button type="button" @click="addMarkerAtPlayhead()" class="vw-markers-add-btn" title="{{ __('Add Marker') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                </button>
                <button type="button" @click="exportYouTubeChapters()" class="vw-markers-export-btn" :disabled="markers.length === 0" title="{{ __('Export YouTube Chapters') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                    <span>{{ __('YT Chapters') }}</span>
                </button>
                <button type="button" @click="showMarkerPanel = false" class="vw-panel-close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <div class="vw-markers-list" x-show="markers.length > 0">
            <template x-for="marker in markers" :key="marker.id">
                <div
                    class="vw-marker-item"
                    :class="{ 'is-selected': selectedMarker === marker.id, 'is-editing': editingMarker === marker.id }"
                    @click="selectMarker(marker.id)"
                >
                    <div class="vw-marker-color" :style="{ background: marker.color }"></div>
                    <div class="vw-marker-info">
                        <template x-if="editingMarker !== marker.id">
                            <span class="vw-marker-name" x-text="marker.name"></span>
                        </template>
                        <template x-if="editingMarker === marker.id">
                            <input
                                type="text"
                                class="vw-marker-name-input"
                                :value="marker.name"
                                @input="updateMarker(marker.id, { name: $event.target.value })"
                                @keydown.enter="editingMarker = null"
                                @keydown.escape="editingMarker = null"
                                @blur="editingMarker = null"
                                x-ref="markerNameInput"
                                x-init="$nextTick(() => { if (editingMarker === marker.id) $el.focus() })"
                            >
                        </template>
                        <span class="vw-marker-time" x-text="formatTime(marker.time)"></span>
                    </div>
                    <div class="vw-marker-item-actions">
                        <div class="vw-color-picker">
                            <template x-for="color in markerColors" :key="color.value">
                                <button
                                    type="button"
                                    class="vw-color-option"
                                    :class="{ 'is-selected': marker.color === color.value }"
                                    :style="{ background: color.value }"
                                    @click.stop="updateMarker(marker.id, { color: color.value })"
                                    :title="color.name"
                                ></button>
                            </template>
                        </div>
                        <button type="button" @click.stop="editingMarker = marker.id" class="vw-marker-edit-btn" title="{{ __('Edit') }}">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </button>
                        <button type="button" @click.stop="deleteMarker(marker.id)" class="vw-marker-delete-btn" title="{{ __('Delete') }}">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="vw-markers-empty" x-show="markers.length === 0">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            <p>{{ __('No markers yet') }}</p>
            <span>{{ __('Press Shift+M to add a marker at the playhead') }}</span>
        </div>

        <div class="vw-markers-nav" x-show="markers.length > 1">
            <button type="button" @click="goToPrevMarker()" title="{{ __('Previous Marker') }} (Shift+Alt+←)">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            </button>
            <span x-text="(markers.findIndex(m => m.id === selectedMarker) + 1) + ' / ' + markers.length"></span>
            <button type="button" @click="goToNextMarker()" title="{{ __('Next Marker') }} (Shift+Alt+→)">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
        </div>
    </div>

    {{-- ===== Phase 6: Marker Context Menu ===== --}}
    <div
        class="vw-marker-menu"
        x-show="showMarkerMenu"
        x-cloak
        x-transition
        :style="{ left: markerMenuX + 'px', top: markerMenuY + 'px' }"
    >
        <button type="button" @click="editingMarker = selectedMarker; showMarkerPanel = true; hideMarkerMenu()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <span>{{ __('Edit Marker') }}</span>
        </button>
        <button type="button" @click="const m = markers.find(m => m.id === selectedMarker); if(m) seek(m.time); hideMarkerMenu()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            <span>{{ __('Go to Marker') }}</span>
        </button>
        <div class="vw-marker-menu-divider"></div>
        <button type="button" class="vw-danger" @click="deleteMarker(selectedMarker)">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            <span>{{ __('Delete Marker') }}</span>
        </button>
    </div>

    {{-- ===== Phase 6: Transitions Library Panel ===== --}}
    <div
        class="vw-transitions-panel"
        x-show="showTransitionLibrary"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-x-4"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform -translate-x-4"
    >
        <div class="vw-transitions-header">
            <h3>{{ __('Transitions') }}</h3>
            <button type="button" @click="showTransitionLibrary = false" class="vw-panel-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Category Filter --}}
        <div class="vw-transitions-categories">
            <button
                type="button"
                @click="selectedTransitionCategory = 'all'"
                :class="{ 'is-active': selectedTransitionCategory === 'all' }"
            >{{ __('All') }}</button>
            <template x-for="cat in transitionCategories" :key="cat">
                <button
                    type="button"
                    @click="selectedTransitionCategory = cat"
                    :class="{ 'is-active': selectedTransitionCategory === cat }"
                    x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"
                ></button>
            </template>
        </div>

        {{-- Transitions Grid --}}
        <div class="vw-transitions-grid">
            <template x-for="transition in filteredTransitions" :key="transition.id">
                <div
                    class="vw-transition-item"
                    :class="{ 'is-previewing': previewingTransition?.id === transition.id }"
                    @mousedown="startTransitionDrag($event, transition)"
                    @mouseenter="previewTransition(transition)"
                    @mouseleave="stopTransitionPreview()"
                    :title="transition.name"
                >
                    <div class="vw-transition-preview">
                        {{-- Transition icon based on type --}}
                        <div class="vw-transition-icon" :class="'vw-trans-' + transition.icon">
                            <div class="vw-trans-from"></div>
                            <div class="vw-trans-to"></div>
                        </div>
                    </div>
                    <span class="vw-transition-name" x-text="transition.name"></span>
                    <span class="vw-transition-duration" x-text="transition.duration + 's'"></span>
                </div>
            </template>
        </div>

        <div class="vw-transitions-help">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/></svg>
            <span>{{ __('Drag a transition to a clip edge') }}</span>
        </div>
    </div>

    {{-- ===== Phase 3: Keyboard Shortcuts Modal ===== --}}
    <div
        class="vw-shortcuts-modal-backdrop"
        x-show="showShortcutsModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showShortcutsModal = false"
    >
        <div class="vw-shortcuts-modal" @click.stop>
            <div class="vw-shortcuts-header">
                <h3>{{ __('Keyboard Shortcuts') }}</h3>
                <button type="button" @click="showShortcutsModal = false" class="vw-shortcuts-close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="vw-shortcuts-body">
                <div class="vw-shortcuts-section">
                    <h4>{{ __('Playback') }}</h4>
                    <div class="vw-shortcut-row"><kbd>Space</kbd><span>{{ __('Play / Pause') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>J</kbd><span>{{ __('Reverse playback') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>K</kbd><span>{{ __('Stop playback') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>L</kbd><span>{{ __('Forward playback') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Home</kbd><span>{{ __('Go to start') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>End</kbd><span>{{ __('Go to end') }}</span></div>
                </div>
                <div class="vw-shortcuts-section">
                    <h4>{{ __('Navigation') }}</h4>
                    <div class="vw-shortcut-row"><kbd>&larr;</kbd><span>{{ __('Step back 1 frame') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>&rarr;</kbd><span>{{ __('Step forward 1 frame') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Shift + &larr;</kbd><span>{{ __('Step back 5 frames') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Shift + &rarr;</kbd><span>{{ __('Step forward 5 frames') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>I</kbd><span>{{ __('Set In point') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>O</kbd><span>{{ __('Set Out point') }}</span></div>
                </div>
                <div class="vw-shortcuts-section">
                    <h4>{{ __('Editing') }}</h4>
                    <div class="vw-shortcut-row"><kbd>S</kbd> / <kbd>B</kbd><span>{{ __('Split at playhead') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Delete</kbd><span>{{ __('Delete selected') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + C</kbd><span>{{ __('Copy') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + X</kbd><span>{{ __('Cut') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + V</kbd><span>{{ __('Paste') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + D</kbd><span>{{ __('Duplicate') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + A</kbd><span>{{ __('Select all') }}</span></div>
                </div>
                <div class="vw-shortcuts-section">
                    <h4>{{ __('Tools & View') }}</h4>
                    <div class="vw-shortcut-row"><kbd>N</kbd><span>{{ __('Toggle snap') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>R</kbd><span>{{ __('Toggle ripple mode') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>+</kbd> / <kbd>=</kbd><span>{{ __('Zoom in') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>-</kbd><span>{{ __('Zoom out') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>0</kbd><span>{{ __('Fit to view') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + Z</kbd><span>{{ __('Undo') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>Ctrl + Y</kbd><span>{{ __('Redo') }}</span></div>
                    <div class="vw-shortcut-row"><kbd>?</kbd><span>{{ __('Show this help') }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Phase D: Accessibility - Screen Reader Support ===== --}}
    {{-- Hidden instructions for screen readers --}}
    <div id="timeline-instructions" class="sr-only">
        {{ __('Use arrow keys to navigate the timeline. Press Space to play/pause. Press S to split clips. Press Delete to remove selected clips. Press ? for full keyboard shortcuts.') }}
    </div>

    {{-- Live region for dynamic announcements --}}
    <div
        x-ref="srAnnouncer"
        class="sr-only"
        aria-live="polite"
        aria-atomic="true"
        role="status"
    ></div>
</div>

<style>
/* Screen reader only utility */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ===== Phase D: Accessibility - Focus Visibility ===== */
/* High-contrast focus rings for keyboard navigation */
.vw-pro-timeline *:focus {
    outline: none;
}

.vw-pro-timeline *:focus-visible {
    outline: 2px solid #8b5cf6;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.25);
}

.vw-tool-btn:focus-visible,
.vw-track-toggle:focus-visible {
    outline: 2px solid #8b5cf6;
    outline-offset: 1px;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3);
}

.vw-playhead-top:focus-visible {
    outline: 2px solid #f59e0b;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25);
}

.vw-clip:focus-visible {
    outline: 2px solid #10b981;
    outline-offset: 1px;
    z-index: 10;
}

/* Skip link for keyboard users */
.vw-skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: #8b5cf6;
    color: white;
    padding: 8px 16px;
    z-index: 1000;
    transition: top 0.2s;
}

.vw-skip-link:focus {
    top: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .vw-pro-timeline *,
    .vw-pro-timeline *::before,
    .vw-pro-timeline *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

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

/* ==========================================================================
   PHASE 3: TOOL SELECTOR
   ========================================================================== */

.vw-tool-selector {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.5rem;
    padding: 0.15rem;
}

.vw-tool-selector .vw-tool-btn.is-active {
    background: rgba(139, 92, 246, 0.3);
    border-color: rgba(139, 92, 246, 0.5);
    color: #a78bfa;
}

.vw-tool-danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.2) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    color: #f87171 !important;
}

/* ==========================================================================
   PHASE 3: CONTEXT MENU
   ========================================================================== */

.vw-context-menu {
    position: fixed;
    min-width: 200px;
    background: rgba(20, 20, 35, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.6rem;
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
    z-index: 1000;
    overflow: hidden;
    padding: 0.35rem;
}

.vw-context-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.5rem 0.6rem;
    background: transparent;
    border: none;
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.8rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-context-item svg {
    width: 16px;
    height: 16px;
    opacity: 0.7;
}

.vw-context-item span {
    flex: 1;
}

.vw-context-item kbd {
    padding: 0.15rem 0.4rem;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-family: 'SF Mono', Monaco, monospace;
    color: rgba(255, 255, 255, 0.5);
}

.vw-context-item:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-context-item:hover:not(:disabled) svg {
    opacity: 1;
}

.vw-context-item:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.vw-context-danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.vw-context-danger:hover:not(:disabled) svg {
    color: #f87171;
}

.vw-context-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.08);
    margin: 0.3rem 0;
}

/* ==========================================================================
   PHASE 3: SPLIT CURSOR
   ========================================================================== */

.vw-tracks-container.vw-split-cursor {
    cursor: crosshair;
}

.vw-split-cursor-line {
    position: absolute;
    top: 0;
    height: 100%;
    pointer-events: none;
    z-index: 80;
}

.vw-split-cursor-line::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 2px;
    height: 100%;
    background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.6);
}

.vw-split-cursor-head {
    position: absolute;
    top: -24px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    color: #22c55e;
}

.vw-split-cursor-head svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 0 4px rgba(34, 197, 94, 0.6));
}

/* ==========================================================================
   PHASE 3: MARQUEE SELECTION
   ========================================================================== */

.vw-marquee-box {
    position: absolute;
    border: 1px dashed rgba(139, 92, 246, 0.8);
    background: rgba(139, 92, 246, 0.15);
    pointer-events: none;
    z-index: 100;
}

/* ==========================================================================
   PHASE 3: IN/OUT POINT MARKERS
   ========================================================================== */

.vw-in-point-marker,
.vw-out-point-marker {
    position: absolute;
    top: 0;
    height: 100%;
    pointer-events: none;
    z-index: 55;
}

.vw-in-point-marker::before,
.vw-out-point-marker::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 2px;
    height: 100%;
}

.vw-in-point-marker::before {
    background: #facc15;
    box-shadow: 0 0 8px rgba(250, 204, 21, 0.5);
}

.vw-out-point-marker::before {
    background: #fb923c;
    box-shadow: 0 0 8px rgba(251, 146, 60, 0.5);
}

.vw-point-label {
    position: absolute;
    top: -22px;
    left: 50%;
    transform: translateX(-50%);
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 0.6rem;
    font-weight: 700;
    color: white;
}

.vw-in-point-marker .vw-point-label {
    background: #facc15;
    color: #1f2937;
}

.vw-out-point-marker .vw-point-label {
    background: #fb923c;
}

/* Region between in/out points */
.vw-in-out-region {
    position: absolute;
    top: 0;
    height: 100%;
    background: rgba(250, 204, 21, 0.1);
    border-left: 2px solid #facc15;
    border-right: 2px solid #fb923c;
    pointer-events: none;
    z-index: 40;
}

/* ==========================================================================
   PHASE 3: SELECTION BADGE
   ========================================================================== */

.vw-selection-badge {
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    background: rgba(139, 92, 246, 0.9);
    border-radius: 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    z-index: 200;
}

.vw-selection-badge svg {
    width: 14px;
    height: 14px;
}

/* ==========================================================================
   PHASE 3: KEYBOARD SHORTCUTS MODAL
   ========================================================================== */

.vw-shortcuts-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.vw-shortcuts-modal {
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    background: rgba(20, 20, 35, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1rem;
    box-shadow:
        0 25px 80px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
    overflow: hidden;
}

.vw-shortcuts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.vw-shortcuts-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: white;
}

.vw-shortcuts-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-radius: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-shortcuts-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-shortcuts-close svg {
    width: 18px;
    height: 18px;
}

.vw-shortcuts-body {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    padding: 1.25rem;
    max-height: calc(80vh - 60px);
    overflow-y: auto;
}

.vw-shortcuts-section h4 {
    margin: 0 0 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(139, 92, 246, 0.9);
}

.vw-shortcut-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.4rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.vw-shortcut-row:last-child {
    border-bottom: none;
}

.vw-shortcut-row kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    padding: 0.25rem 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.3rem;
    font-size: 0.7rem;
    font-family: 'SF Mono', Monaco, monospace;
    color: rgba(255, 255, 255, 0.8);
    white-space: nowrap;
}

.vw-shortcut-row span {
    flex: 1;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

/* ==========================================================================
   PHASE 3: CUT INDICATOR ON CLIPS
   ========================================================================== */

.vw-clip.is-cut {
    opacity: 0.5;
    filter: grayscale(0.3);
}

.vw-clip.is-cut::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        -45deg,
        transparent,
        transparent 4px,
        rgba(239, 68, 68, 0.15) 4px,
        rgba(239, 68, 68, 0.15) 8px
    );
    border-radius: inherit;
    pointer-events: none;
}

/* ==========================================================================
   PHASE 3: RESPONSIVE ADJUSTMENTS
   ========================================================================== */

@media (max-width: 768px) {
    .vw-shortcuts-body {
        grid-template-columns: 1fr;
    }

    .vw-toolbar-center {
        flex-wrap: wrap;
        justify-content: center;
    }

    .vw-tool-btn span {
        display: none;
    }

    .vw-context-menu {
        min-width: 180px;
    }
}

/* ==========================================================================
   PHASE 4: ENHANCED TRACK HEADERS
   ========================================================================== */

.vw-track-headers {
    width: 140px;
    min-width: 140px;
    flex-shrink: 0;
    transition: width 0.2s ease;
}

.vw-track-headers.vw-headers-expanded {
    width: 180px;
    min-width: 180px;
}

.vw-ruler-header {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 32px;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.vw-expand-headers-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-radius: 0.3rem;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-expand-headers-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-expand-headers-btn svg {
    width: 14px;
    height: 14px;
    transition: transform 0.2s;
}

.vw-expand-headers-btn svg.is-rotated {
    transform: rotate(45deg);
}

/* Track Header */
.vw-track-header {
    position: relative;
    display: flex;
    background: rgba(20, 20, 30, 0.8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    transition: opacity 0.2s, background 0.2s;
}

.vw-track-header.is-collapsed {
    background: rgba(15, 15, 25, 0.9);
}

.vw-track-header.is-muted {
    opacity: 0.6;
}

.vw-track-header.is-solo {
    background: rgba(34, 197, 94, 0.1);
}

.vw-track-header.is-locked {
    background: rgba(0, 0, 0, 0.3);
}

.vw-track-header.is-dragging {
    opacity: 0.8;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    z-index: 100;
}

/* Color Bar / Drag Handle */
.vw-header-color-bar {
    width: 6px;
    min-width: 6px;
    background: var(--track-color);
    cursor: grab;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: width 0.15s;
}

.vw-header-color-bar:hover {
    width: 10px;
}

.vw-header-color-bar:active {
    cursor: grabbing;
}

.vw-drag-grip {
    display: flex;
    flex-direction: column;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.15s;
}

.vw-header-color-bar:hover .vw-drag-grip {
    opacity: 1;
}

.vw-drag-grip span {
    width: 4px;
    height: 1px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 1px;
}

/* Header Main Content */
.vw-header-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 0.4rem 0.5rem;
    overflow: hidden;
    min-width: 0;
}

.vw-header-top {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.3rem;
}

.vw-header-icon {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--track-color);
}

.vw-header-icon svg {
    width: 14px;
    height: 14px;
}

.vw-header-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Control Buttons Row */
.vw-header-controls {
    display: flex;
    gap: 0.25rem;
    margin-top: auto;
}

.vw-header-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.25rem;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-header-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.85);
}

.vw-header-btn svg {
    width: 12px;
    height: 12px;
}

.vw-btn-label {
    font-size: 0.6rem;
    font-weight: 700;
}

/* Mute Button Active */
.vw-btn-mute.is-active {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

/* Solo Button Active */
.vw-btn-solo.is-active {
    background: rgba(34, 197, 94, 0.2);
    border-color: rgba(34, 197, 94, 0.4);
    color: #4ade80;
}

/* Lock Button Active */
.vw-btn-lock.is-active {
    background: rgba(251, 146, 60, 0.2);
    border-color: rgba(251, 146, 60, 0.4);
    color: #fb923c;
}

/* ==========================================================================
   PHASE 4: VOLUME SLIDER
   ========================================================================== */

.vw-header-volume {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.35rem;
    padding-top: 0.35rem;
    border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.vw-volume-icon {
    width: 12px;
    height: 12px;
    color: rgba(255, 255, 255, 0.4);
}

.vw-volume-slider {
    flex: 1;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    cursor: pointer;
}

.vw-volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 10px;
    height: 10px;
    background: var(--track-color, #8b5cf6);
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.1s;
}

.vw-volume-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

.vw-volume-slider::-moz-range-thumb {
    width: 10px;
    height: 10px;
    background: var(--track-color, #8b5cf6);
    border: none;
    border-radius: 50%;
    cursor: pointer;
}

.vw-volume-value {
    font-size: 0.6rem;
    color: rgba(255, 255, 255, 0.5);
    min-width: 28px;
    text-align: right;
}

/* ==========================================================================
   PHASE 4: COLLAPSE BUTTON
   ========================================================================== */

.vw-header-collapse {
    position: absolute;
    right: 4px;
    top: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-radius: 0.2rem;
    color: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-header-collapse:hover {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
}

.vw-header-collapse svg {
    width: 12px;
    height: 12px;
    transition: transform 0.2s;
}

.vw-header-collapse svg.is-collapsed {
    transform: rotate(-90deg);
}

/* ==========================================================================
   PHASE 4: TRACK RESIZE HANDLE
   ========================================================================== */

.vw-track-resize-handle {
    position: absolute;
    left: 0;
    right: 0;
    bottom: -3px;
    height: 6px;
    cursor: ns-resize;
    z-index: 10;
    background: transparent;
}

.vw-track-resize-handle::after {
    content: '';
    position: absolute;
    left: 10%;
    right: 10%;
    top: 50%;
    transform: translateY(-50%);
    height: 2px;
    background: transparent;
    border-radius: 1px;
    transition: background 0.15s;
}

.vw-track-resize-handle:hover::after {
    background: var(--track-color, rgba(139, 92, 246, 0.5));
}

/* ==========================================================================
   PHASE 4: TRACK STATES FOR TRACKS AREA
   ========================================================================== */

.vw-track {
    transition: opacity 0.2s, filter 0.2s;
}

.vw-track.is-solo-others {
    opacity: 0.3;
    filter: grayscale(0.5);
}

/* Track drop indicator during reorder */
.vw-track-drop-indicator {
    position: absolute;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, #8b5cf6 50%, transparent 100%);
    pointer-events: none;
    z-index: 100;
}

/* ==========================================================================
   PHASE 4: RESPONSIVE
   ========================================================================== */

@media (max-width: 768px) {
    .vw-track-headers {
        width: 100px !important;
        min-width: 100px !important;
    }

    .vw-track-headers.vw-headers-expanded {
        width: 140px !important;
        min-width: 140px !important;
    }

    .vw-header-volume {
        display: none;
    }

    .vw-header-controls {
        flex-wrap: wrap;
    }

    .vw-header-btn {
        width: 18px;
        height: 18px;
    }
}

/* ==========================================================================
   PHASE 5: ENHANCED ZOOM CONTROLS
   ========================================================================== */

.vw-minimap-toggle {
    padding: 0.35rem;
}

.vw-minimap-toggle svg {
    width: 18px;
    height: 18px;
}

.vw-minimap-toggle.is-active {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.3);
    color: #a78bfa;
}

/* Zoom Presets Dropdown */
.vw-zoom-presets {
    position: relative;
}

.vw-zoom-preset-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.5rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-zoom-preset-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.15);
}

.vw-zoom-preset-btn svg {
    width: 14px;
    height: 14px;
    opacity: 0.6;
}

.vw-zoom-preset-menu {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 0.5rem;
    min-width: 140px;
    background: rgba(20, 20, 30, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    z-index: 100;
}

.vw-zoom-preset-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.1s;
}

.vw-zoom-preset-item:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
    color: white;
}

.vw-zoom-preset-item.is-active {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
}

.vw-zoom-preset-item svg {
    width: 14px;
    height: 14px;
}

.vw-zoom-preset-item:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.vw-zoom-preset-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.08);
    margin: 0.25rem 0;
}

.vw-shortcut-hint {
    font-size: 0.65rem;
    padding: 0.15rem 0.35rem;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 0.2rem;
    color: rgba(255, 255, 255, 0.5);
    font-family: 'SF Mono', Monaco, monospace;
}

/* ==========================================================================
   PHASE 5: TIMELINE MINIMAP
   ========================================================================== */

.vw-timeline-minimap {
    position: relative;
    background: rgba(15, 15, 25, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding: 0.5rem 0.75rem;
    overflow: hidden;
}

.vw-minimap-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.35rem;
}

.vw-minimap-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.4);
}

.vw-minimap-time {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.35);
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-minimap-tracks {
    position: relative;
    height: 24px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.25rem;
    overflow: hidden;
}

.vw-minimap-track {
    position: absolute;
    left: 0;
    right: 0;
    height: 10px;
}

.vw-minimap-video {
    top: 2px;
}

.vw-minimap-audio {
    bottom: 2px;
}

.vw-minimap-clip {
    position: absolute;
    height: 100%;
    background: rgba(139, 92, 246, 0.5);
    border-radius: 2px;
    transition: background 0.15s;
}

.vw-minimap-video .vw-minimap-clip {
    background: rgba(139, 92, 246, 0.5);
}

.vw-minimap-audio .vw-minimap-clip {
    background: rgba(6, 182, 212, 0.5);
}

/* Viewport Indicator */
.vw-minimap-viewport {
    position: absolute;
    top: 0;
    bottom: 0;
    background: rgba(139, 92, 246, 0.15);
    border: 2px solid rgba(139, 92, 246, 0.6);
    border-radius: 0.25rem;
    cursor: grab;
    transition: background 0.15s, border-color 0.15s;
}

.vw-minimap-viewport:hover {
    background: rgba(139, 92, 246, 0.25);
    border-color: rgba(139, 92, 246, 0.8);
}

.vw-minimap-viewport.is-dragging {
    cursor: grabbing;
    background: rgba(139, 92, 246, 0.3);
    border-color: #a78bfa;
}

.vw-viewport-handle {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 12px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    opacity: 0;
    transition: opacity 0.15s;
}

.vw-minimap-viewport:hover .vw-viewport-handle {
    opacity: 1;
}

.vw-viewport-left {
    left: 4px;
}

.vw-viewport-right {
    right: 4px;
}

/* Minimap Playhead */
.vw-minimap-playhead {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ef4444;
    box-shadow: 0 0 6px rgba(239, 68, 68, 0.5);
    z-index: 10;
    pointer-events: none;
}

/* In/Out Points in Minimap */
.vw-minimap-in-point,
.vw-minimap-out-point {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 3px;
    z-index: 5;
}

.vw-minimap-in-point {
    background: #10b981;
    border-radius: 0 0 0 2px;
}

.vw-minimap-out-point {
    background: #f59e0b;
    border-radius: 0 0 2px 0;
}

.vw-minimap-range {
    position: absolute;
    top: 0;
    bottom: 0;
    background: rgba(16, 185, 129, 0.15);
    z-index: 4;
    pointer-events: none;
}

/* ==========================================================================
   PHASE 5: TRANSPORT BAR
   ========================================================================== */

.vw-transport-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: rgba(10, 10, 20, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.vw-transport-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-transport-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
}

.vw-transport-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.vw-transport-btn svg {
    width: 14px;
    height: 14px;
}

.vw-transport-btn.is-active {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
    color: #a78bfa;
}

.vw-transport-divider {
    width: 1px;
    height: 20px;
    background: rgba(255, 255, 255, 0.1);
}

/* Audio Scrub Button */
.vw-audio-scrub-btn.is-active {
    background: rgba(6, 182, 212, 0.2);
    border-color: rgba(6, 182, 212, 0.4);
    color: #22d3ee;
}

/* Shuttle Controls */
.vw-shuttle-controls {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.vw-shuttle-btn.is-active {
    background: rgba(34, 197, 94, 0.2);
    border-color: rgba(34, 197, 94, 0.4);
    color: #4ade80;
}

.vw-shuttle-stop.is-active {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
}

.vw-shuttle-display {
    min-width: 50px;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.25rem;
    font-size: 0.7rem;
    font-family: 'SF Mono', Monaco, monospace;
    color: rgba(255, 255, 255, 0.5);
    text-align: center;
}

.vw-shuttle-display.is-active {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
}

/* Frame Controls */
.vw-frame-controls {
    display: flex;
    gap: 0.15rem;
}

/* In/Out Points */
.vw-io-controls {
    display: flex;
    gap: 0.25rem;
}

.vw-in-btn,
.vw-out-btn {
    font-size: 0.75rem;
    font-weight: 700;
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-in-btn.is-set {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.4);
    color: #34d399;
}

.vw-out-btn.is-set {
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.4);
    color: #fbbf24;
}

.vw-transport-spacer {
    flex: 1;
}

.vw-transport-time {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.35rem;
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-current-time {
    font-size: 0.85rem;
    font-weight: 500;
    color: white;
}

.vw-time-separator {
    color: rgba(255, 255, 255, 0.3);
}

.vw-total-time {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
}

/* ==========================================================================
   PHASE 5: SCRUBBING CURSOR
   ========================================================================== */

.vw-time-ruler.is-scrubbing {
    cursor: ew-resize;
}

.vw-time-ruler.is-scrubbing::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(139, 92, 246, 0.05);
    pointer-events: none;
}

/* ==========================================================================
   PHASE 5: RESPONSIVE
   ========================================================================== */

@media (max-width: 768px) {
    .vw-zoom-presets {
        display: none;
    }

    .vw-minimap-toggle {
        display: none;
    }

    .vw-transport-bar {
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.35rem 0.5rem;
    }

    .vw-transport-btn {
        width: 24px;
        height: 24px;
    }

    .vw-transport-btn svg {
        width: 12px;
        height: 12px;
    }

    .vw-shuttle-display {
        display: none;
    }

    .vw-transport-time {
        order: -1;
        width: 100%;
        justify-content: center;
        margin-bottom: 0.25rem;
    }

    .vw-timeline-minimap {
        display: none;
    }
}

@media (max-width: 480px) {
    .vw-io-controls {
        display: none;
    }

    .vw-frame-controls {
        display: none;
    }
}

/* ==========================================================================
   PHASE 6: MARKERS & CHAPTERS
   ========================================================================== */

/* Toolbar Marker Controls */
.vw-marker-controls {
    display: flex;
    align-items: center;
}

.vw-marker-btn svg {
    width: 16px;
    height: 16px;
}

.vw-markers-list-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.3rem;
}

.vw-marker-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 16px;
    height: 16px;
    padding: 0 0.3rem;
    background: rgba(139, 92, 246, 0.3);
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 600;
    color: #a78bfa;
}

.vw-markers-list-btn svg {
    width: 12px;
    height: 12px;
}

/* Ruler Markers */
.vw-ruler-marker {
    position: absolute;
    top: 0;
    height: 100%;
    cursor: pointer;
    z-index: 15;
    transform: translateX(-5px);
}

.vw-marker-flag {
    position: relative;
    color: var(--marker-color, #ef4444);
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
    transition: transform 0.15s;
}

.vw-marker-flag svg {
    width: 10px;
    height: 14px;
}

.vw-ruler-marker:hover .vw-marker-flag {
    transform: scale(1.2);
}

.vw-ruler-marker.is-selected .vw-marker-flag {
    transform: scale(1.3);
    filter: drop-shadow(0 0 4px var(--marker-color));
}

.vw-marker-line {
    position: absolute;
    top: 14px;
    left: 50%;
    width: 2px;
    height: calc(100% + 200px);
    background: var(--marker-color, #ef4444);
    opacity: 0.3;
    transform: translateX(-50%);
    pointer-events: none;
}

.vw-ruler-marker:hover .vw-marker-line,
.vw-ruler-marker.is-selected .vw-marker-line {
    opacity: 0.6;
}

/* I/O Point Visual Markers */
.vw-io-marker {
    position: absolute;
    top: 0;
    height: 100%;
    cursor: pointer;
    z-index: 12;
}

.vw-io-marker svg {
    width: 8px;
    height: 16px;
}

.vw-in-marker {
    color: #10b981;
    transform: translateX(-8px);
}

.vw-out-marker {
    color: #f59e0b;
}

.vw-io-region-ruler {
    position: absolute;
    top: 0;
    height: 100%;
    background: rgba(16, 185, 129, 0.1);
    border-top: 2px solid rgba(16, 185, 129, 0.4);
    z-index: 11;
    pointer-events: none;
}

/* Markers Panel */
.vw-markers-panel {
    position: absolute;
    top: 60px;
    right: 10px;
    width: 320px;
    max-height: 400px;
    background: rgba(20, 20, 30, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    z-index: 200;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.vw-markers-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.vw-markers-header h3 {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
}

.vw-markers-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.vw-markers-add-btn,
.vw-markers-export-btn {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.35rem 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-markers-add-btn:hover,
.vw-markers-export-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.12);
    color: white;
}

.vw-markers-add-btn svg,
.vw-markers-export-btn svg {
    width: 14px;
    height: 14px;
}

.vw-markers-export-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.vw-panel-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-panel-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-panel-close svg {
    width: 16px;
    height: 16px;
}

/* Markers List */
.vw-markers-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.vw-marker-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.4rem;
    cursor: pointer;
    transition: background 0.15s;
}

.vw-marker-item:hover {
    background: rgba(255, 255, 255, 0.06);
}

.vw-marker-item.is-selected {
    background: rgba(139, 92, 246, 0.15);
}

.vw-marker-color {
    width: 8px;
    height: 24px;
    border-radius: 4px;
    flex-shrink: 0;
}

.vw-marker-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.vw-marker-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vw-marker-name-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 92, 246, 0.4);
    border-radius: 0.25rem;
    color: white;
    font-size: 0.8rem;
    outline: none;
}

.vw-marker-time {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.4);
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-marker-item-actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.15s;
}

.vw-marker-item:hover .vw-marker-item-actions {
    opacity: 1;
}

.vw-color-picker {
    display: flex;
    gap: 0.15rem;
}

.vw-color-option {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.15s, border-color 0.15s;
}

.vw-color-option:hover {
    transform: scale(1.2);
}

.vw-color-option.is-selected {
    border-color: white;
}

.vw-marker-edit-btn,
.vw-marker-delete-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    border-radius: 0.25rem;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-marker-edit-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-marker-delete-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.vw-marker-edit-btn svg,
.vw-marker-delete-btn svg {
    width: 12px;
    height: 12px;
}

/* Empty State */
.vw-markers-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    text-align: center;
    color: rgba(255, 255, 255, 0.4);
}

.vw-markers-empty svg {
    width: 40px;
    height: 40px;
    margin-bottom: 0.75rem;
    opacity: 0.3;
}

.vw-markers-empty p {
    margin: 0 0 0.35rem;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
}

.vw-markers-empty span {
    font-size: 0.75rem;
}

/* Navigation */
.vw-markers-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.vw-markers-nav button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.15s;
}

.vw-markers-nav button:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.vw-markers-nav button svg {
    width: 16px;
    height: 16px;
}

.vw-markers-nav span {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
}

/* Marker Context Menu */
.vw-marker-menu {
    position: fixed;
    min-width: 160px;
    background: rgba(20, 20, 30, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    z-index: 300;
    padding: 0.35rem;
}

.vw-marker-menu button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 0.6rem;
    background: transparent;
    border: none;
    border-radius: 0.35rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.1s;
}

.vw-marker-menu button:hover {
    background: rgba(255, 255, 255, 0.08);
}

.vw-marker-menu button.vw-danger:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
}

.vw-marker-menu button svg {
    width: 14px;
    height: 14px;
}

.vw-marker-menu-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.08);
    margin: 0.25rem 0;
}

/* ==========================================================================
   PHASE 6: TRANSITIONS LIBRARY
   ========================================================================== */

.vw-transitions-btn svg {
    width: 16px;
    height: 16px;
}

.vw-transitions-panel {
    position: absolute;
    top: 60px;
    left: 10px;
    width: 280px;
    max-height: 450px;
    background: rgba(20, 20, 30, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    z-index: 200;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.vw-transitions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.vw-transitions-header h3 {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
}

.vw-transitions-categories {
    display: flex;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    overflow-x: auto;
}

.vw-transitions-categories button {
    padding: 0.3rem 0.6rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.7rem;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s;
}

.vw-transitions-categories button:hover {
    background: rgba(255, 255, 255, 0.1);
}

.vw-transitions-categories button.is-active {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
    color: #a78bfa;
}

.vw-transitions-grid {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    padding: 0.75rem;
    overflow-y: auto;
}

.vw-transition-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.5rem;
    cursor: grab;
    transition: all 0.15s;
}

.vw-transition-item:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.15);
}

.vw-transition-item.is-previewing {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.4);
}

.vw-transition-item:active {
    cursor: grabbing;
}

.vw-transition-preview {
    width: 50px;
    height: 35px;
    margin-bottom: 0.35rem;
    overflow: hidden;
    border-radius: 0.25rem;
}

.vw-transition-icon {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
}

.vw-trans-from,
.vw-trans-to {
    flex: 1;
    transition: all 0.3s;
}

.vw-trans-from {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
}

.vw-trans-to {
    background: linear-gradient(135deg, #06b6d4 0%, #14b8a6 100%);
}

/* Transition Animation Previews */
.vw-trans-fade .vw-trans-from {
    opacity: 1;
}

.vw-transition-item.is-previewing .vw-trans-fade .vw-trans-from {
    opacity: 0;
}

.vw-trans-wipe .vw-trans-from {
    transform: translateX(0);
}

.vw-transition-item.is-previewing .vw-trans-wipe .vw-trans-from {
    transform: translateX(-100%);
}

.vw-trans-slide .vw-trans-to {
    transform: translateX(100%);
}

.vw-transition-item.is-previewing .vw-trans-slide .vw-trans-to {
    transform: translateX(0);
}

.vw-trans-zoom .vw-trans-from {
    transform: scale(1);
}

.vw-transition-item.is-previewing .vw-trans-zoom .vw-trans-from {
    transform: scale(0.5);
    opacity: 0;
}

.vw-trans-dissolve {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
}

.vw-trans-blur .vw-trans-from {
    filter: blur(0);
}

.vw-transition-item.is-previewing .vw-trans-blur .vw-trans-from {
    filter: blur(5px);
    opacity: 0;
}

.vw-trans-flash {
    background: white;
    opacity: 0;
}

.vw-transition-item.is-previewing .vw-trans-flash {
    animation: flash-preview 0.3s ease-out;
}

@keyframes flash-preview {
    0% { opacity: 0; }
    50% { opacity: 1; }
    100% { opacity: 0; }
}

.vw-transition-name {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    text-align: center;
}

.vw-transition-duration {
    font-size: 0.6rem;
    color: rgba(255, 255, 255, 0.4);
    font-family: 'SF Mono', Monaco, monospace;
}

.vw-transitions-help {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(0, 0, 0, 0.2);
}

.vw-transitions-help svg {
    width: 14px;
    height: 14px;
    color: rgba(255, 255, 255, 0.4);
    flex-shrink: 0;
}

.vw-transitions-help span {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.4);
}

/* ==========================================================================
   PHASE 6: KEYFRAMES (on clips)
   ========================================================================== */

.vw-clip-keyframes {
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
    height: 10px;
    display: flex;
    align-items: center;
    pointer-events: none;
}

.vw-keyframe {
    position: absolute;
    width: 8px;
    height: 8px;
    background: #fbbf24;
    transform: rotate(45deg) translateX(-50%);
    border: 1px solid rgba(0, 0, 0, 0.3);
    cursor: pointer;
    pointer-events: auto;
    transition: transform 0.15s, background 0.15s;
}

.vw-keyframe:hover {
    transform: rotate(45deg) translateX(-50%) scale(1.3);
    background: #f59e0b;
}

.vw-keyframe.is-selected {
    background: #ef4444;
    box-shadow: 0 0 6px rgba(239, 68, 68, 0.5);
}

/* Transition indicators on clips */
.vw-clip-transition {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.vw-clip-transition-in {
    left: 0;
    background: linear-gradient(90deg, rgba(139, 92, 246, 0.3) 0%, transparent 100%);
    border-left: 2px solid rgba(139, 92, 246, 0.6);
}

.vw-clip-transition-out {
    right: 0;
    background: linear-gradient(270deg, rgba(139, 92, 246, 0.3) 0%, transparent 100%);
    border-right: 2px solid rgba(139, 92, 246, 0.6);
}

.vw-clip-transition svg {
    width: 10px;
    height: 10px;
    color: rgba(255, 255, 255, 0.6);
}

/* ==========================================================================
   PHASE 6: RESPONSIVE
   ========================================================================== */

@media (max-width: 768px) {
    .vw-markers-panel,
    .vw-transitions-panel {
        position: fixed;
        top: auto;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        max-height: 50vh;
        border-radius: 1rem 1rem 0 0;
    }

    .vw-marker-controls {
        display: none;
    }

    .vw-transitions-btn {
        display: none;
    }

    .vw-transitions-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .vw-transitions-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .vw-color-picker {
        display: none;
    }
}

/* ===== Phase 7: Performance & Polish CSS ===== */

/* GPU Acceleration for smooth animations */
.vw-clip,
.vw-playhead,
.vw-playhead-line,
.vw-marker,
.vw-io-marker,
.vw-transition-indicator {
    will-change: transform;
    transform: translateZ(0);
    backface-visibility: hidden;
}

/* GPU compositing for scrollable areas */
.vw-timeline-scroll {
    will-change: scroll-position;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Reduce repaints on hover states */
.vw-clip:hover,
.vw-track-header:hover,
.vw-toolbar-btn:hover {
    will-change: transform, box-shadow;
}

/* Touch-friendly targets - minimum 44px */
@media (pointer: coarse) {
    .vw-toolbar-btn,
    .vw-track-control-btn,
    .vw-zoom-btn,
    .vw-nav-btn {
        min-width: 44px;
        min-height: 44px;
        padding: 0.75rem;
    }

    .vw-track-header {
        min-height: 48px;
        padding: 0.75rem;
    }

    .vw-clip {
        min-height: 44px;
    }

    .vw-resize-handle {
        width: 16px;
        touch-action: pan-y;
    }

    .vw-resize-handle-left {
        left: -8px;
    }

    .vw-resize-handle-right {
        right: -8px;
    }

    /* Larger touch targets for markers */
    .vw-marker {
        min-width: 20px;
        min-height: 20px;
    }

    .vw-marker-flag {
        width: 20px;
        height: 20px;
    }

    /* Larger drag handles for transitions */
    .vw-transition-handle {
        width: 16px;
        height: 100%;
    }

    /* Increase spacing for touch */
    .vw-track {
        margin-bottom: 4px;
    }

    .vw-clips-row {
        gap: 4px;
    }

    /* Prevent text selection on touch */
    .vw-timeline-container {
        -webkit-user-select: none;
        user-select: none;
        -webkit-touch-callout: none;
    }

    /* Hide scrollbars on touch devices */
    .vw-timeline-scroll::-webkit-scrollbar,
    .vw-tracks-scroll::-webkit-scrollbar {
        display: none;
    }

    .vw-timeline-scroll,
    .vw-tracks-scroll {
        scrollbar-width: none;
    }
}

/* Touch action for panning/zooming */
.vw-tracks-container {
    touch-action: pan-x pan-y pinch-zoom;
}

.vw-clip {
    touch-action: manipulation;
}

.vw-resize-handle {
    touch-action: pan-y;
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .vw-clip,
    .vw-playhead,
    .vw-marker,
    .vw-transition-indicator,
    .vw-zoom-slider,
    .vw-progress-bar {
        transition: none !important;
        animation: none !important;
    }

    .vw-timeline-scroll {
        scroll-behavior: auto;
    }

    .vw-waveform-line,
    .vw-beat-pulse {
        animation: none !important;
    }
}

/* Virtual scrolling placeholder styles */
.vw-clip.vw-clip-placeholder {
    opacity: 0.3;
    pointer-events: none;
}

.vw-clip.vw-clip-loading {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.05) 0%,
        rgba(255, 255, 255, 0.1) 50%,
        rgba(255, 255, 255, 0.05) 100%
    );
    background-size: 200% 100%;
    animation: vw-shimmer 1.5s infinite;
}

@keyframes vw-shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Long-press visual feedback */
.vw-clip.is-long-pressing {
    transform: scale(1.02);
    box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Momentum scrolling indicator */
.vw-timeline-scroll.is-momentum-scrolling {
    scroll-snap-type: none;
}

/* Pinch zoom visual feedback */
.vw-timeline-container.is-pinch-zooming {
    outline: 2px solid rgba(139, 92, 246, 0.3);
    outline-offset: -2px;
}

/* Swipe gesture hints */
.vw-swipe-hint {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.8);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.vw-swipe-hint-left {
    left: 8px;
}

.vw-swipe-hint-right {
    right: 8px;
}

.vw-timeline-container.show-swipe-hints .vw-swipe-hint {
    opacity: 1;
}

.vw-swipe-hint svg {
    width: 20px;
    height: 20px;
    color: white;
}

/* Tablet responsive improvements */
@media (min-width: 768px) and (max-width: 1024px) and (pointer: coarse) {
    .vw-timeline-container {
        padding: 0.5rem;
    }

    .vw-toolbar {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .vw-toolbar-group {
        gap: 0.375rem;
    }

    .vw-track-headers {
        width: 100px;
    }

    .vw-track-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .vw-track-controls {
        width: 100%;
        justify-content: flex-start;
    }

    /* Stack panels on tablets */
    .vw-markers-panel,
    .vw-transitions-panel {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        max-width: 100%;
        border-radius: 1rem 1rem 0 0;
        max-height: 60vh;
    }
}

/* Performance: content-visibility for off-screen elements */
.vw-track:not(:focus-within) {
    content-visibility: auto;
    contain-intrinsic-height: 60px;
}

/* Layer promotion for animated elements */
.vw-playhead-line {
    transform: translateZ(0);
    will-change: left;
}

.vw-clip.is-dragging {
    transform: translate3d(var(--drag-x, 0), var(--drag-y, 0), 0);
    will-change: transform;
    z-index: 100;
}

/* Contain paint for track containers */
.vw-tracks-wrapper {
    contain: layout style;
}

.vw-track {
    contain: layout;
}

/* Optimize clip rendering */
.vw-clip-content {
    contain: strict;
}

.vw-clip-thumbnail {
    contain: layout paint;
    content-visibility: auto;
}

.vw-waveform-canvas {
    contain: strict;
}

/* Async image loading */
.vw-clip-thumbnail img {
    decoding: async;
    loading: lazy;
}

/* Prevent layout thrashing */
.vw-ruler-container,
.vw-tracks-container {
    contain: layout style;
}

/* FPS counter (debug mode) */
.vw-fps-counter {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 2px 6px;
    background: rgba(0, 0, 0, 0.7);
    color: #10b981;
    font-size: 10px;
    font-family: monospace;
    border-radius: 4px;
    z-index: 1000;
    pointer-events: none;
}

.vw-fps-counter.fps-low {
    color: #ef4444;
}

.vw-fps-counter.fps-medium {
    color: #f59e0b;
}
</style>
