{{-- Step 6: Assembly Studio - Full-Screen Professional Editor --}}
{{-- Note: Video Preview Engine scripts are loaded in video-wizard.blade.php --}}

@php
    $assemblyStats = $this->getAssemblyStats();
    $isMultiShot = $assemblyStats['mode'] === 'multi-shot';
    $canExport = !$isMultiShot || $assemblyStats['isReady'];
@endphp

<div
    class="vw-assembly-fullscreen"
    x-data="{
        ...(typeof previewController === 'function' ? previewController(@js($this->getPreviewInitData())) : {
            // Fallback properties if previewController isn't loaded yet
            engine: null,
            isReady: false,
            isLoading: false,
            isPlaying: false,
            currentTime: 0,
            totalDuration: {{ $this->getTotalDuration() ?? 0 }},
            loadProgress: 0,
            currentSceneIndex: 0,
            totalScenes: {{ count($script['scenes'] ?? []) }},
            canvasWidth: 1280,
            canvasHeight: 720,
            aspectRatio: '{{ $aspectRatio ?? '16:9' }}',
            captionsEnabled: {{ ($assembly['captions']['enabled'] ?? true) ? 'true' : 'false' }},
            musicEnabled: {{ ($assembly['music']['enabled'] ?? false) ? 'true' : 'false' }},
            scenes: @js($this->getPreviewScenes()),
            init() {
                console.warn('[Assembly] previewController not loaded, using fallback. Check if scripts are loaded.');
            },
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '0:00';
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return mins + ':' + secs.toString().padStart(2, '0');
            },
            togglePlay() { console.warn('[Assembly] togglePlay called but no engine'); },
            play() { console.warn('[Assembly] play called but no engine'); },
            pause() { console.warn('[Assembly] pause called but no engine'); },
            seek(time) { console.warn('[Assembly] seek called but no engine'); },
            seekStart() { this.seek(0); },
            seekEnd() { this.seek(this.totalDuration); },
            jumpToScene(index) { this.currentSceneIndex = index; },
            seekToScene(index) { this.currentSceneIndex = index; },
            loadPreview() {
                console.error('[Assembly] loadPreview failed - previewController not loaded. Ensure video-preview-engine.js and preview-controller.js are included.');
                alert('Preview could not load. Please refresh the page and try again.');
            }
        }),
        activeTab: 'scenes',
        musicEnabled: @js($assembly['music']['enabled'] ?? false),
        captionsEnabled: @js($assembly['captions']['enabled'] ?? true),
        showExportModal: false,
        keyboardShortcuts: true,

        handleKeyboard(e) {
            if (!this.keyboardShortcuts) return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            switch(e.key.toLowerCase()) {
                case ' ':
                    e.preventDefault();
                    this.togglePlay();
                    break;
                case 'escape':
                    if (this.showExportModal) {
                        this.showExportModal = false;
                    }
                    break;
                case '1':
                    this.activeTab = 'scenes';
                    break;
                case '2':
                    this.activeTab = 'text';
                    break;
                case '3':
                    this.activeTab = 'audio';
                    break;
                case '4':
                    this.activeTab = 'transitions';
                    break;
                case 'arrowleft':
                    if (this.engine) {
                        this.seek(Math.max(0, this.currentTime - 5));
                    }
                    break;
                case 'arrowright':
                    if (this.engine) {
                        this.seek(Math.min(this.totalDuration, this.currentTime + 5));
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
                    <div class="vw-shortcut"><kbd>1-4</kbd> {{ __('Switch tabs') }}</div>
                    <div class="vw-shortcut"><kbd>Ctrl+Z</kbd> {{ __('Undo') }}</div>
                    <div class="vw-shortcut"><kbd>Ctrl+Y</kbd> {{ __('Redo') }}</div>
                    <div class="vw-shortcut"><kbd>Esc</kbd> {{ __('Close modal') }}</div>
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
