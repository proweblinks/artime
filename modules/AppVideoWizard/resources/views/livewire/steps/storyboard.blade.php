{{-- Step 4: Storyboard - Full Screen Layout --}}
@include("appvideowizard::livewire.partials._storyboard-styles")

@php
// PHASE 6: Shot type badge helper functions
function getShotTypeBadgeClass($type) {
    $type = strtolower($type ?? '');
    $map = [
        'extreme-close-up' => 'xcu',
        'close-up' => 'cu',
        'medium-close' => 'mcu',
        'medium' => 'med',
        'wide' => 'wide',
        'establishing' => 'est',
        'over-the-shoulder' => 'ots',
        'reaction' => 'reaction',
        'two-shot' => 'two-shot',
    ];
    return $map[$type] ?? 'med';
}

function getShotTypeLabel($type) {
    $type = strtolower($type ?? '');
    $labels = [
        'extreme-close-up' => 'XCU',
        'close-up' => 'CU',
        'medium-close' => 'MCU',
        'medium' => 'MED',
        'wide' => 'WIDE',
        'establishing' => 'EST',
        'over-the-shoulder' => 'OTS',
        'reaction' => 'REACT',
        'two-shot' => '2-SHOT',
    ];
    return $labels[$type] ?? strtoupper(substr($type, 0, 4));
}

function getCameraMovementIcon($movement) {
    $icons = [
        'push-in' => '→●',
        'pull-out' => '●→',
        'pan-left' => '←',
        'pan-right' => '→',
        'tilt-up' => '↑',
        'tilt-down' => '↓',
        'static' => '●',
        'slow-push' => '→',
        'slight-drift' => '~',
    ];
    return $icons[strtolower($movement ?? '')] ?? '';
}
@endphp

@if($isSocialContent ?? false)
    @include('appvideowizard::livewire.steps.partials._social-create')

    {{-- Universal AI Image Studio Modal (for social content) --}}
    @include('appvideowizard::livewire.modals.image-studio')

    {{-- Asset History Panel (for social content) --}}
    @include('appvideowizard::livewire.modals.asset-history-panel')
@else
<div class="vw-storyboard-fullscreen" x-data="{
    showSettings: true,
    selectedModel: '{{ $storyboard['imageModel'] ?? 'nanobanana' }}',
    viewMode: 'grid',
    selectedCard: null,
    isGenerating: false,
    sidebarCollapsed: false,
    sidebarWidth: parseInt(localStorage.getItem('storyboard-sidebar-width')) || 320,
    isResizing: false,
    resizeStartX: 0,
    resizeStartWidth: 0,
    toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
    },
    startResize(e) {
        this.isResizing = true;
        this.resizeStartX = e.clientX;
        this.resizeStartWidth = this.sidebarWidth;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    },
    onResize(e) {
        if (!this.isResizing) return;
        const delta = e.clientX - this.resizeStartX;
        const newWidth = Math.min(500, Math.max(240, this.resizeStartWidth + delta));
        this.sidebarWidth = newWidth;
    },
    stopResize() {
        if (this.isResizing) {
            this.isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            localStorage.setItem('storyboard-sidebar-width', this.sidebarWidth);
        }
    },
    // Phase 2: Collapsible sections
    sections: {
        videoModel: false,
        visualStyle: true,
        sceneMemory: true
    },
    // Phase 2: Side panel
    sidePanel: {
        open: false,
        type: null,
        sceneIndex: null
    },
    openSidePanel(type, sceneIndex = null) {
        this.sidePanel.open = true;
        this.sidePanel.type = type;
        this.sidePanel.sceneIndex = sceneIndex;
    },
    closeSidePanel() {
        this.sidePanel.open = false;
        this.sidePanel.type = null;
        this.sidePanel.sceneIndex = null;
    },
    // Phase 3: @ Mention System (Performance optimized - cached items)
    mention: {
        active: false,
        query: '',
        selectedIndex: 0,
        inputEl: null,
        cursorPos: 0
    },
    // Cached bible items - initialized once, not on every access
    _mentionItemsCache: null,
    getMentionItemsBase() {
        // Return cached if available
        if (this._mentionItemsCache) return this._mentionItemsCache;
        // Build and cache on first access
        const characters = @js($sceneMemory['characterBible']['characters'] ?? []).map(c => ({
            type: 'character',
            icon: '👤',
            name: c.name || 'Character',
            tag: '@' + (c.name || 'character').toLowerCase().replace(/\s+/g, '-'),
            image: c.referenceImage || null
        }));
        const locations = @js($sceneMemory['locationBible']['locations'] ?? []).map(l => ({
            type: 'location',
            icon: '📍',
            name: l.name || 'Location',
            tag: '@' + (l.name || 'location').toLowerCase().replace(/\s+/g, '-'),
            image: l.referenceImage || null
        }));
        this._mentionItemsCache = [...characters, ...locations];
        return this._mentionItemsCache;
    },
    // Filtered items based on query - uses cached base
    getFilteredMentionItems() {
        const allItems = this.getMentionItemsBase();
        if (!this.mention.query) return allItems;
        const q = this.mention.query.toLowerCase();
        return allItems.filter(item =>
            item.name.toLowerCase().includes(q) ||
            item.tag.toLowerCase().includes(q)
        );
    },
    handleMentionInput(e) {
        const textarea = e.target;
        const value = textarea.value;
        const cursorPos = textarea.selectionStart;

        // Find @ before cursor
        let atPos = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (value[i] === '@') {
                atPos = i;
                break;
            } else if (value[i] === ' ' || value[i] === '\n') {
                break;
            }
        }

        if (atPos >= 0) {
            this.mention.active = true;
            this.mention.query = value.substring(atPos + 1, cursorPos);
            this.mention.inputEl = textarea;
            this.mention.cursorPos = cursorPos;
            this.mention.selectedIndex = 0;
        } else {
            this.mention.active = false;
            this.mention.query = '';
        }
    },
    handleMentionKeydown(e) {
        if (!this.mention.active) return;

        const items = this.getFilteredMentionItems();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.mention.selectedIndex = Math.min(this.mention.selectedIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.mention.selectedIndex = Math.max(this.mention.selectedIndex - 1, 0);
        } else if (e.key === 'Enter' && items.length > 0) {
            e.preventDefault();
            this.insertMention(items[this.mention.selectedIndex]);
        } else if (e.key === 'Escape') {
            this.mention.active = false;
        }
    },
    insertMention(item) {
        if (!this.mention.inputEl) return;
        const textarea = this.mention.inputEl;
        const value = textarea.value;
        const cursorPos = this.mention.cursorPos;

        // Find @ position
        let atPos = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (value[i] === '@') { atPos = i; break; }
        }
        if (atPos < 0) return;

        const before = value.substring(0, atPos);
        const after = value.substring(cursorPos);
        const newValue = before + item.tag + ' ' + after;

        textarea.value = newValue;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));

        const newCursorPos = atPos + item.tag.length + 1;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();

        this.mention.active = false;
        this.mention.query = '';
    },
    // Phase 3: Brainstorm Suggestions
    brainstorm: {
        open: false,
        loading: false,
        suggestions: [],
        sceneIndex: null
    },
    async fetchBrainstormSuggestions(sceneIndex) {
        this.brainstorm.sceneIndex = sceneIndex;
        this.brainstorm.open = true;
        this.brainstorm.loading = true;
        this.brainstorm.suggestions = [];

        // Simulate AI suggestions (in production, this would call a backend endpoint)
        await new Promise(resolve => setTimeout(resolve, 1200));

        this.brainstorm.suggestions = [
            { type: 'angle', icon: '📐', text: 'Try a low-angle shot to emphasize power and dominance' },
            { type: 'lighting', icon: '💡', text: 'Add golden hour rim lighting for dramatic silhouette' },
            { type: 'mood', icon: '🎭', text: 'Increase contrast and add fog for mysterious atmosphere' },
            { type: 'composition', icon: '📷', text: 'Use rule of thirds with subject off-center for visual tension' }
        ];
        this.brainstorm.loading = false;
    },
    closeBrainstorm() {
        this.brainstorm.open = false;
        this.brainstorm.suggestions = [];
    },
    // Phase 3: Progressive Generation
    generation: {
        active: false,
        sceneIndex: null,
        progress: 0,
        status: 'Initializing...',
        substatus: ''
    },
    startProgressiveGeneration(sceneIndex) {
        this.generation.active = true;
        this.generation.sceneIndex = sceneIndex;
        this.generation.progress = 0;
        this.generation.status = 'Preparing scene...';
        this.generation.substatus = 'Analyzing prompt';
        this.simulateProgress();
    },
    async simulateProgress() {
        const stages = [
            { progress: 15, status: 'Processing prompt...', substatus: 'Applying style tokens' },
            { progress: 35, status: 'Generating base...', substatus: 'Creating composition' },
            { progress: 55, status: 'Adding details...', substatus: 'Rendering textures' },
            { progress: 75, status: 'Refining image...', substatus: 'Enhancing lighting' },
            { progress: 90, status: 'Final touches...', substatus: 'Applying color grading' },
            { progress: 100, status: 'Complete!', substatus: '' }
        ];

        for (const stage of stages) {
            await new Promise(resolve => setTimeout(resolve, 600 + Math.random() * 400));
            if (!this.generation.active) break;
            this.generation.progress = stage.progress;
            this.generation.status = stage.status;
            this.generation.substatus = stage.substatus;
        }

        if (this.generation.progress >= 100) {
            await new Promise(resolve => setTimeout(resolve, 500));
            this.generation.active = false;
        }
    },
    cancelGeneration() {
        this.generation.active = false;
        this.generation.progress = 0;
    },
    // Phase 4: Keyboard Shortcuts (Performance optimized - proper cleanup)
    shortcuts: {
        showHelp: false
    },
    _keyboardHandler: null,
    initKeyboardShortcuts() {
        // Remove any existing handler first (prevents duplicates on Livewire updates)
        if (this._keyboardHandler) {
            document.removeEventListener('keydown', this._keyboardHandler);
        }
        // Create bound handler for proper cleanup
        this._keyboardHandler = (e) => {
            // Ignore if typing in input/textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            // ? or / + Shift = Show shortcuts help
            if (e.key === '?' || (e.key === '/' && e.shiftKey)) {
                e.preventDefault();
                this.shortcuts.showHelp = !this.shortcuts.showHelp;
            }
            // G = Toggle grid/timeline view
            else if (e.key === 'g' || e.key === 'G') {
                e.preventDefault();
                this.viewMode = this.viewMode === 'grid' ? 'timeline' : 'grid';
            }
            // S = Toggle settings panel
            else if (e.key === 's' || e.key === 'S') {
                if (!e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    this.showSettings = !this.showSettings;
                }
            }
            // Escape = Close panels/modals
            else if (e.key === 'Escape') {
                if (this.shortcuts.showHelp) {
                    this.shortcuts.showHelp = false;
                } else if (this.sidePanel.open) {
                    this.closeSidePanel();
                } else if (this.brainstorm.open) {
                    this.closeBrainstorm();
                }
            }
            // 1-9 = Quick select scene
            else if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey) {
                const sceneIndex = parseInt(e.key) - 1;
                const sceneCards = document.querySelectorAll('.vw-scene-card');
                if (sceneCards[sceneIndex]) {
                    sceneCards[sceneIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    sceneCards[sceneIndex].classList.add('selected');
                    setTimeout(() => sceneCards[sceneIndex].classList.remove('selected'), 1000);
                }
            }
        };
        document.addEventListener('keydown', this._keyboardHandler);
    },
    // Cleanup method for proper resource management
    destroy() {
        if (this._keyboardHandler) {
            document.removeEventListener('keydown', this._keyboardHandler);
            this._keyboardHandler = null;
        }
        if (this.toast.timeout) {
            clearTimeout(this.toast.timeout);
        }
    },
    // Phase 4: Toast Notifications
    toast: {
        show: false,
        message: '',
        type: 'success',
        timeout: null
    },
    showToast(message, type = 'success', duration = 3000) {
        if (this.toast.timeout) clearTimeout(this.toast.timeout);
        this.toast.show = true;
        this.toast.message = message;
        this.toast.type = type;
        this.toast.timeout = setTimeout(() => {
            this.toast.show = false;
        }, duration);
    },
    // Initialize on mount
    init() {
        this.initKeyboardShortcuts();
        // Pre-cache mention items for better performance
        this.getMentionItemsBase();
    }
}"
@destroy="destroy()">
    {{-- Top Header Bar --}}
    <div class="vw-storyboard-topbar">
        {{-- Brand --}}
        <div class="vw-storyboard-brand">
            <div class="vw-storyboard-icon">🎨</div>
            <div>
                <div class="vw-storyboard-title">{{ __('Storyboard Studio') }}</div>
                <div class="vw-storyboard-subtitle">{{ __('Step 4 of 7') }}</div>
            </div>
        </div>

        {{-- Progress Pills --}}
        @php
            $imagesReady = count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl'])));
            $totalScenes = count($script['scenes'] ?? []);
            $allImagesReady = $imagesReady === $totalScenes && $totalScenes > 0;
        @endphp
        <div class="vw-storyboard-pills">
            <div class="vw-storyboard-pill {{ $allImagesReady ? 'complete' : '' }}">
                <span>🖼️</span>
                <span class="pill-value">{{ $imagesReady }}/{{ $totalScenes }}</span>
                <span style="color: var(--vw-text-secondary);">{{ __('images') }}</span>
            </div>
            @if($multiShotMode['enabled'])
                @php $shotStats = $this->getShotStatistics(); @endphp
                <div class="vw-storyboard-pill">
                    <span>🎬</span>
                    <span class="pill-value">{{ $shotStats['totalShots'] }}</span>
                    <span style="color: var(--vw-text-secondary);">{{ __('shots') }}</span>
                </div>
            @endif
        </div>

        {{-- Header Actions --}}
        <div class="vw-storyboard-actions">
            {{-- View Mode Toggle --}}
            <div class="vw-view-mode-toggle">
                <button type="button"
                        class="vw-view-mode-btn"
                        :class="{ 'active': viewMode === 'grid' }"
                        @click="viewMode = 'grid'"
                        title="{{ __('Grid View') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>{{ __('Grid') }}</span>
                </button>
                <button type="button"
                        class="vw-view-mode-btn"
                        :class="{ 'active': viewMode === 'timeline' }"
                        @click="viewMode = 'timeline'"
                        title="{{ __('Timeline View') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="6" x2="20" y2="6"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="18" x2="20" y2="18"></line>
                    </svg>
                    <span>{{ __('Timeline') }}</span>
                </button>
            </div>

            {{-- Phase 4: Keyboard Shortcuts Help --}}
            <button type="button"
                    @click="shortcuts.showHelp = true"
                    style="padding: 0.4rem 0.6rem; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.35rem; color: var(--vw-text-secondary); cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem;"
                    title="{{ __('Keyboard Shortcuts') }} (?)">
                ⌨️
                <span class="vw-shortcut-badge">?</span>
            </button>

            {{-- Settings Toggle --}}
            <button type="button"
                    class="vw-settings-toggle"
                    :class="{ 'active': showSettings }"
                    @click="showSettings = !showSettings">
                <span>⚙️</span>
                <span>{{ __('Settings') }}</span>
            </button>

            {{-- Multi-Shot Toggle (promoted from sidebar) --}}
            <button type="button"
                    class="vw-topbar-mode-btn {{ $multiShotMode['enabled'] ? 'active' : '' }}"
                    wire:click="toggleMultiShotMode"
                    title="{{ __('Toggle multi-shot decomposition') }}">
                ✂️ {{ __('Multi-Shot') }}
                @if($multiShotMode['enabled'])
                    <span class="vw-topbar-mode-badge">{{ __('ON') }}</span>
                @endif
            </button>

            {{-- Generate All Button --}}
            @if(!empty($script['scenes']))
                <button type="button"
                        class="vw-generate-all-btn"
                        wire:click="generateAllImages"
                        wire:loading.attr="disabled"
                        wire:target="generateAllImages">
                    <span wire:loading.remove wire:target="generateAllImages">🎨</span>
                    <span wire:loading wire:target="generateAllImages" class="vw-btn-spinner"></span>
                    {{ __('Generate All Images') }}
                </button>
            @endif

            {{-- Navigation Buttons --}}
            <button type="button"
                    wire:click="goToStep(3)"
                    style="padding: 0.45rem 0.85rem; background: rgba(0,0,0,0.04); border: 1px solid var(--vw-border); border-radius: 0.5rem; color: var(--vw-text); cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem;">
                <span>←</span>
                <span>{{ __('Script') }}</span>
            </button>

            <button type="button"
                    wire:click="goToStep(5)"
                    style="padding: 0.45rem 0.85rem; background: linear-gradient(135deg, var(--vw-primary), #06b6d4); border: none; border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                <span>{{ __('Animation') }}</span>
                <span>→</span>
            </button>
        </div>
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="vw-alert error" style="margin: 0.5rem 1.25rem;">
            <span class="vw-alert-icon">❌</span>
            <span class="vw-alert-text">{{ $error }}</span>
            <button type="button" class="vw-alert-close" wire:click="$set('error', null)">&times;</button>
        </div>
    @endif

    @if(empty($script['scenes']))
        <div class="vw-alert warning" style="margin: 1.25rem;">
            <span class="vw-alert-icon">⚠️</span>
            <span class="vw-alert-text">{{ __('Please generate a script first before creating the storyboard.') }}</span>
        </div>
    @else
        {{-- Main Content Area - NEW SIDEBAR LAYOUT --}}
        <div class="vw-storyboard-main">

            {{-- ========================================
                 ICON RAIL - Always visible, 48px
                 ======================================== --}}
            {{-- Icon rail removed — all settings in single scrollable sidebar --}}

            {{-- ========================================
                 SETTINGS SIDEBAR - Collapsible & Resizable
                 ======================================== --}}
            <div class="vw-settings-sidebar"
                 :class="{ 'collapsed': sidebarCollapsed, 'resizing': isResizing }"
                 :style="!sidebarCollapsed ? 'width: ' + sidebarWidth + 'px' : ''"
                 @mousemove.window="onResize($event)"
                 @mouseup.window="stopResize()">
                {{-- Resize Handle --}}
                <div class="vw-sidebar-resize-handle"
                     @mousedown.prevent="startResize($event)"></div>

                {{-- Sidebar Header --}}
                <div class="vw-sidebar-header">
                    <span class="vw-sidebar-title">{{ __('Settings') }}</span>
                    <button type="button" @click="toggleSidebar()" style="background: none; border: none; cursor: pointer; color: var(--at-text-muted); font-size: 0.8rem; padding: 0.25rem;" title="{{ __('Collapse') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>
                    </button>
                </div>

                {{-- Single scrollable sidebar with all sections --}}
                <div class="vw-sidebar-content">

                    {{-- ======== SETTINGS SECTION (Accordion) ======== --}}
                    <div x-data="{ open: true }" class="vw-sidebar-accordion">
                        <button type="button" @click="open = !open" class="vw-sidebar-accordion-header">
                            <span>⚙️ {{ __('Settings') }}</span>
                            <svg :class="{ 'vw-accordion-chevron--open': open }" class="vw-accordion-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    <div x-show="open" x-collapse>
                        {{-- Quick Stats --}}
                        @php
                            $imagesReady = count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl'])));
                            $totalScenes = count($script['scenes'] ?? []);
                        @endphp
                        <div class="vw-sidebar-stats">
                            <div class="vw-sidebar-stat">
                                <div class="vw-sidebar-stat-value">{{ $totalScenes }}</div>
                                <div class="vw-sidebar-stat-label">{{ __('Scenes') }}</div>
                            </div>
                            <div class="vw-sidebar-stat">
                                <div class="vw-sidebar-stat-value">{{ $imagesReady }}</div>
                                <div class="vw-sidebar-stat-label">{{ __('Images') }}</div>
                            </div>
                            <div class="vw-sidebar-stat">
                                <div class="vw-sidebar-stat-value">{{ $multiShotMode['enabled'] ? ($this->getShotStatistics()['totalShots'] ?? 0) : '-' }}</div>
                                <div class="vw-sidebar-stat-label">{{ __('Shots') }}</div>
                            </div>
                        </div>

                        {{-- AI Model Section --}}
                        <div class="vw-sidebar-section open">
                            <div class="vw-sidebar-section-header" @click="sections.aiModel = !sections.aiModel">
                                <div class="vw-sidebar-section-title">
                                    <span class="icon">🤖</span>
                                    <span>{{ __('AI Model') }}</span>
                                </div>
                            </div>
                            <div class="vw-sidebar-section-body">
                                @php
                                    $imageModels = [
                                        'hidream' => ['name' => 'HiDream', 'cost' => 2, 'desc' => 'Artistic & cinematic'],
                                        'nanobanana-pro' => ['name' => 'NanoBanana Pro', 'cost' => 3, 'desc' => 'High quality'],
                                        'nanobanana' => ['name' => 'NanoBanana', 'cost' => 1, 'desc' => 'Quick drafts'],
                                    ];
                                @endphp
                                <div class="vw-sidebar-models">
                                    @foreach($imageModels as $modelId => $model)
                                        <button type="button"
                                                class="vw-sidebar-model-btn"
                                                :class="{ 'selected': selectedModel === '{{ $modelId }}' }"
                                                @click="selectedModel = '{{ $modelId }}'; $wire.set('storyboard.imageModel', '{{ $modelId }}')">
                                            <span class="vw-sidebar-model-name">{{ $model['name'] }}</span>
                                            <span class="vw-sidebar-model-cost">{{ $model['cost'] }}t</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Hollywood Expansion Toggle --}}
                        <div class="vw-sidebar-section open">
                            <div class="vw-sidebar-section-body" style="padding-top: 0.5rem;">
                                <div class="vw-sidebar-toggle" style="margin-bottom: 0.75rem;">
                                    <span class="vw-sidebar-toggle-label">
                                        <span>✨</span>
                                        {{ __('Hollywood Expansion') }}
                                        <span class="vw-badge vw-badge-new" style="font-size: 0.5rem; padding: 0.1rem 0.3rem;">AI</span>
                                    </span>
                                    <div class="vw-sidebar-toggle-switch {{ $hollywoodExpansionEnabled ? 'active' : '' }}"
                                         wire:click="toggleHollywoodExpansion"
                                         title="{{ $hollywoodExpansionEnabled ? __('Click to disable AI-enhanced prompts') : __('Click to enable AI-enhanced prompts') }}">
                                        <div class="vw-sidebar-toggle-track"></div>
                                        <div class="vw-sidebar-toggle-thumb"></div>
                                    </div>
                                </div>
                                <p style="font-size: 0.65rem; color: var(--vw-text-secondary); margin-bottom: 0.75rem; line-height: 1.4;">
                                    {{ $hollywoodExpansionEnabled
                                        ? __('Complex shots get AI-enhanced Hollywood-quality prompts')
                                        : __('All shots use fast template-only prompts')
                                    }}
                                </p>
                            </div>
                        </div>

                        {{-- Multi-Shot Toggle --}}
                        <div class="vw-sidebar-section open">
                            <div class="vw-sidebar-section-body" style="padding-top: 0.5rem;">
                                <div class="vw-sidebar-toggle">
                                    <span class="vw-sidebar-toggle-label">
                                        <span>🎬</span>
                                        {{ __('Multi-Shot Mode') }}
                                        <span class="vw-badge vw-badge-pro" style="font-size: 0.5rem; padding: 0.1rem 0.3rem;">PRO</span>
                                    </span>
                                    <div class="vw-sidebar-toggle-switch {{ $multiShotMode['enabled'] ? 'active' : '' }}"
                                         wire:click="toggleMultiShotMode">
                                        <div class="vw-sidebar-toggle-track"></div>
                                        <div class="vw-sidebar-toggle-thumb"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Technical Specs Section --}}
                        <div class="vw-sidebar-section" x-data="{ open: false }">
                            <div class="vw-sidebar-section-header" @click="open = !open">
                                <div class="vw-sidebar-section-title">
                                    <span class="icon">⚙️</span>
                                    <span>{{ __('Technical Specs') }}</span>
                                </div>
                                <div class="vw-sidebar-section-chevron" :style="open ? 'transform: rotate(180deg)' : ''">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="vw-sidebar-section-body" x-show="open" x-collapse>
                                <div class="vw-sidebar-style-grid" style="grid-template-columns: 1fr;">
                                    <div class="vw-sidebar-style-select">
                                        <label>{{ __('Quality') }}</label>
                                        <select wire:model.change="storyboard.technicalSpecs.quality">
                                            <option value="4k">{{ __('4K') }}</option>
                                            <option value="2k">{{ __('2K') }}</option>
                                            <option value="1080p">{{ __('1080p') }}</option>
                                            <option value="720p">{{ __('720p') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    <label style="display: block; font-size: 0.6rem; color: var(--vw-text-secondary); margin-bottom: 0.25rem;">{{ __('Positive Prompts') }}</label>
                                    <textarea wire:model.blur="storyboard.technicalSpecs.positive"
                                              placeholder="{{ __('high quality, cinematic...') }}"
                                              style="width: 100%; padding: 0.4rem; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.25rem; color: var(--at-text); font-size: 0.7rem; min-height: 50px; resize: vertical;"></textarea>
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    <label style="display: block; font-size: 0.6rem; color: var(--vw-text-secondary); margin-bottom: 0.25rem;">{{ __('Negative Prompts') }}</label>
                                    <textarea wire:model.blur="storyboard.technicalSpecs.negative"
                                              placeholder="{{ __('blurry, low quality...') }}"
                                              style="width: 100%; padding: 0.4rem; background: rgba(0,0,0,0.03); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.25rem; color: var(--at-text); font-size: 0.7rem; min-height: 50px; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>{{-- /x-collapse --}}
                    </div>{{-- /vw-sidebar-accordion (settings) --}}

                    {{-- ======== STYLE SECTION (Accordion) ======== --}}
                    <div x-data="{ open: true }" class="vw-sidebar-accordion">
                        <button type="button" @click="open = !open" class="vw-sidebar-accordion-header">
                            <span>🎨 {{ __('Visual Style') }}</span>
                            <svg :class="{ 'vw-accordion-chevron--open': open }" class="vw-accordion-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    <div x-show="open" x-collapse>
                        @php
                            $hasActiveStyles = !empty($storyboard['visualStyle']['mood'] ?? '') ||
                                               !empty($storyboard['visualStyle']['lighting'] ?? '') ||
                                               !empty($storyboard['visualStyle']['colorPalette'] ?? '') ||
                                               !empty($storyboard['visualStyle']['composition'] ?? '');
                        @endphp

                        {{-- Active Style Preview --}}
                        @if($hasActiveStyles)
                            <div class="vw-sidebar-style-preview">
                                <div class="vw-sidebar-style-active">
                                    <span>🔗</span>
                                    <span>{{ __('Active Style') }}</span>
                                </div>
                                <div class="vw-sidebar-style-desc">
                                    @if(!empty($storyboard['visualStyle']['mood'])){{ ucfirst($storyboard['visualStyle']['mood']) }}@endif
                                    @if(!empty($storyboard['visualStyle']['lighting']))• {{ ucfirst(str_replace('-', ' ', $storyboard['visualStyle']['lighting'])) }}@endif
                                </div>
                            </div>
                        @endif

                        {{-- Style Controls --}}
                        <div class="vw-sidebar-style-grid">
                            <div class="vw-sidebar-style-select">
                                <label>{{ __('Mood') }}</label>
                                <select wire:model.change="storyboard.visualStyle.mood">
                                    <option value="">{{ __('Auto') }}</option>
                                    <option value="epic">{{ __('Epic') }}</option>
                                    <option value="intimate">{{ __('Intimate') }}</option>
                                    <option value="mysterious">{{ __('Mysterious') }}</option>
                                    <option value="energetic">{{ __('Energetic') }}</option>
                                    <option value="contemplative">{{ __('Contemplative') }}</option>
                                    <option value="tense">{{ __('Tense') }}</option>
                                </select>
                            </div>
                            <div class="vw-sidebar-style-select">
                                <label>{{ __('Lighting') }}</label>
                                <select wire:model.change="storyboard.visualStyle.lighting">
                                    <option value="">{{ __('Auto') }}</option>
                                    <option value="natural">{{ __('Natural') }}</option>
                                    <option value="golden-hour">{{ __('Golden Hour') }}</option>
                                    <option value="blue-hour">{{ __('Blue Hour') }}</option>
                                    <option value="high-key">{{ __('High Key') }}</option>
                                    <option value="low-key">{{ __('Low Key') }}</option>
                                    <option value="neon">{{ __('Neon') }}</option>
                                </select>
                            </div>
                            <div class="vw-sidebar-style-select">
                                <label>{{ __('Colors') }}</label>
                                <select wire:model.change="storyboard.visualStyle.colorPalette">
                                    <option value="">{{ __('Auto') }}</option>
                                    <option value="teal-orange">{{ __('Teal/Orange') }}</option>
                                    <option value="warm-tones">{{ __('Warm') }}</option>
                                    <option value="cool-tones">{{ __('Cool') }}</option>
                                    <option value="desaturated">{{ __('Desaturated') }}</option>
                                    <option value="vibrant">{{ __('Vibrant') }}</option>
                                </select>
                            </div>
                            <div class="vw-sidebar-style-select">
                                <label>{{ __('Shot Type') }}</label>
                                <select wire:model.change="storyboard.visualStyle.composition">
                                    <option value="">{{ __('Auto') }}</option>
                                    <option value="wide">{{ __('Wide') }}</option>
                                    <option value="medium">{{ __('Medium') }}</option>
                                    <option value="close-up">{{ __('Close-up') }}</option>
                                    <option value="low-angle">{{ __('Low Angle') }}</option>
                                    <option value="birds-eye">{{ __("Bird's Eye") }}</option>
                                </select>
                            </div>
                        </div>

                        <p style="font-size: 0.65rem; color: var(--vw-text-secondary); margin-top: 0.75rem; line-height: 1.4;">
                            💡 {{ __('"Auto" uses genre-appropriate defaults') }}
                        </p>
                    </div>{{-- /x-collapse --}}
                    </div>{{-- /vw-sidebar-accordion (style) --}}

                    {{-- ======== MEMORY SECTION (Accordion) ======== --}}
                    <div x-data="{ open: true, memoryTab: 'characters' }" class="vw-sidebar-accordion">
                        <button type="button" @click="open = !open" class="vw-sidebar-accordion-header">
                            <span>🧠 {{ __('Scene Memory') }}</span>
                            <svg :class="{ 'vw-accordion-chevron--open': open }" class="vw-accordion-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    <div x-show="open" x-collapse>
                        @php
                            $characters = $sceneMemory['characterBible']['characters'] ?? [];
                            $locations = $sceneMemory['locationBible']['locations'] ?? [];
                        @endphp

                        {{-- Modern Tab Navigation --}}
                        <div class="vw-memory-tabs">
                            <button type="button"
                                    @click="memoryTab = 'characters'"
                                    :class="{ 'active': memoryTab === 'characters' }"
                                    class="vw-memory-tab">
                                <span class="vw-memory-tab-icon">👤</span>
                                <span class="vw-memory-tab-label">{{ __('Characters') }}</span>
                                <span class="vw-memory-tab-count">{{ count($characters) }}</span>
                            </button>
                            <button type="button"
                                    @click="memoryTab = 'locations'"
                                    :class="{ 'active': memoryTab === 'locations' }"
                                    class="vw-memory-tab">
                                <span class="vw-memory-tab-icon">📍</span>
                                <span class="vw-memory-tab-label">{{ __('Locations') }}</span>
                                <span class="vw-memory-tab-count">{{ count($locations) }}</span>
                            </button>
                            {{-- Tab Indicator Line --}}
                            <div class="vw-memory-tab-indicator" :style="memoryTab === 'characters' ? 'left: 0; width: 50%;' : 'left: 50%; width: 50%;'"></div>
                        </div>

                        {{-- Characters Panel --}}
                        <div x-show="memoryTab === 'characters'"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform translate-x-2"
                             x-transition:enter-end="opacity-100 transform translate-x-0"
                             class="vw-memory-panel">
                            {{-- Add Button --}}
                            <div class="vw-memory-panel-header">
                                <button type="button" wire:click="openCharacterBibleModal" class="vw-memory-add-btn">
                                    <span>+</span> {{ __('Add Character') }}
                                </button>
                            </div>
                            {{-- Character Cards Grid --}}
                            <div class="vw-memory-cards-grid">
                                @forelse($characters as $charIndex => $char)
                                    <div class="vw-memory-card" wire:click="openCharacterBibleModal" title="{{ $char['name'] ?? __('Character') }}">
                                        <div class="vw-memory-card-image">
                                            @if(!empty($char['referenceImage']))
                                                <img src="{{ $char['referenceImage'] }}" alt="{{ $char['name'] ?? '' }}" loading="lazy">
                                            @else
                                                <div class="vw-memory-card-placeholder">👤</div>
                                            @endif
                                        </div>
                                        <div class="vw-memory-card-overlay">
                                            <div class="vw-memory-card-name">{{ $char['name'] ?? __('Character') }}</div>
                                            @if(!empty($char['role']))
                                                <div class="vw-memory-card-role">{{ $char['role'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="vw-memory-empty">
                                        <span class="vw-memory-empty-icon">👤</span>
                                        <span class="vw-memory-empty-text">{{ __('No characters yet') }}</span>
                                        <button type="button" wire:click="openCharacterBibleModal" class="vw-memory-add-btn" style="margin-top: 0.5rem;">
                                            <span>+</span> {{ __('Add First Character') }}
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Locations Panel --}}
                        <div x-show="memoryTab === 'locations'"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-x-2"
                             x-transition:enter-end="opacity-100 transform translate-x-0"
                             class="vw-memory-panel">
                            {{-- Add Button --}}
                            <div class="vw-memory-panel-header">
                                <button type="button" wire:click="openLocationBibleModal" class="vw-memory-add-btn">
                                    <span>+</span> {{ __('Add Location') }}
                                </button>
                            </div>
                            {{-- Location Cards Grid --}}
                            <div class="vw-memory-cards-grid">
                                @forelse($locations as $locIndex => $loc)
                                    <div class="vw-memory-card" wire:click="openLocationBibleModal" title="{{ $loc['name'] ?? __('Location') }}">
                                        <div class="vw-memory-card-image">
                                            @if(!empty($loc['referenceImage']))
                                                <img src="{{ $loc['referenceImage'] }}" alt="{{ $loc['name'] ?? '' }}" loading="lazy">
                                            @else
                                                <div class="vw-memory-card-placeholder">📍</div>
                                            @endif
                                        </div>
                                        <div class="vw-memory-card-overlay">
                                            <div class="vw-memory-card-name">{{ $loc['name'] ?? __('Location') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="vw-memory-empty">
                                        <span class="vw-memory-empty-icon">📍</span>
                                        <span class="vw-memory-empty-text">{{ __('No locations yet') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Scene DNA Footer --}}
                        <div class="vw-memory-dna-footer">
                            <div class="vw-memory-dna-info">
                                <span class="vw-memory-dna-icon">🧬</span>
                                <span class="vw-memory-dna-label">{{ __('Scene DNA') }}</span>
                                <span class="vw-memory-dna-count">{{ count($characters) + count($locations) }} {{ __('synced') }}</span>
                            </div>
                            <button type="button" wire:click="openSceneDNAModal" class="vw-memory-dna-btn">
                                {{ __('View') }}
                            </button>
                        </div>
                    </div>{{-- /x-collapse --}}
                    </div>{{-- /vw-sidebar-accordion (memory) --}}

                </div>

                {{-- Collapse Button at bottom --}}
                <button type="button"
                        class="vw-sidebar-collapse-btn"
                        @click="toggleSidebar()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="11 17 6 12 11 7"></polyline>
                        <polyline points="18 17 13 12 18 7"></polyline>
                    </svg>
                    <span>{{ __('Collapse') }}</span>
                </button>
            </div>

            {{-- ========================================
                 MAIN WORKSPACE
                 ======================================== --}}
            <div class="vw-workspace">
                {{-- Enhanced Progress Indicator (shows during batch generation) --}}
                @php
                    $generatingScenes = collect($storyboard['scenes'] ?? [])->filter(fn($s) => ($s['status'] ?? '') === 'generating');
                    $isGeneratingBatch = $generatingScenes->count() > 0;
                    $totalToGenerate = count($script['scenes'] ?? []);
                    $completedGeneration = collect($storyboard['scenes'] ?? [])->filter(fn($s) => !empty($s['imageUrl']))->count();
                    $progressPercent = $totalToGenerate > 0 ? round(($completedGeneration / $totalToGenerate) * 100) : 0;
                @endphp
                <div class="vw-enhanced-progress"
                     x-show="{{ $isGeneratingBatch ? 'true' : 'false' }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     style="{{ $isGeneratingBatch ? '' : 'display: none;' }} margin: 0.75rem;">
                    <div class="vw-progress-header">
                        <div class="vw-progress-title">
                            <span class="generating-dot"></span>
                            <span>{{ __('Generating Images') }}</span>
                        </div>
                        <div class="vw-progress-stats">
                            <span>{{ $completedGeneration }}/{{ $totalToGenerate }} {{ __('complete') }}</span>
                            <span>•</span>
                            <span>~{{ max(1, ($totalToGenerate - $completedGeneration) * 8) }}s {{ __('remaining') }}</span>
                        </div>
                    </div>
                    <div class="vw-progress-bar-container">
                        <div class="vw-progress-bar-fill" style="width: {{ $progressPercent }}%;"></div>
                    </div>
                    <div class="vw-progress-details">
                        <div class="vw-progress-step">
                            @if($generatingScenes->count() > 0)
                                <span class="step-icon"></span>
                                <span>{{ __('Generating Scene') }} {{ $generatingScenes->keys()->first() + 1 }}...</span>
                            @else
                                <span>{{ __('Waiting to start...') }}</span>
                            @endif
                        </div>
                        <div class="vw-progress-actions">
                            <button type="button"
                                    class="vw-progress-action-btn cancel"
                                    wire:click="cancelAllGenerations"
                                    title="{{ __('Cancel all') }}">
                                ✕ {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Workspace Content --}}
                <div class="vw-workspace-content">
                    {{-- Compact Stats Bar --}}
                    @php
                        $shotStats = $multiShotMode['enabled'] ? $this->getShotStatistics() : null;
                        $clipDuration = $multiShotMode['enabled'] ? $this->getClipDuration() : 0;
                        $sceneTiming = $script['timing'] ?? ['sceneDuration' => 35, 'pacing' => 'balanced'];
                        $imagesReadyCount = count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl'])));
                        $totalScenesCount = count($script['scenes'] ?? []);
                        // Image models for cost display
                        $imageModels = [
                            'hidream' => ['name' => 'HiDream', 'cost' => 2],
                            'nanobanana-pro' => ['name' => 'NanoBanana Pro', 'cost' => 3],
                            'nanobanana' => ['name' => 'NanoBanana', 'cost' => 1],
                        ];
                        $selectedModel = $storyboard['imageModel'] ?? 'nanobanana';
                    @endphp
                <div class="vw-bento-grid">
                    {{-- Stats Cards --}}
                    <div class="vw-bento-card span-3">
                        <div class="vw-bento-stat purple">
                            <div class="vw-bento-stat-value">{{ $totalScenesCount }}</div>
                            <div class="vw-bento-stat-label">{{ __('Scenes') }}</div>
                        </div>
                    </div>
                    @if($multiShotMode['enabled'] && $shotStats)
                    <div class="vw-bento-card span-3">
                        <div class="vw-bento-stat cyan">
                            <div class="vw-bento-stat-value">{{ $shotStats['decomposedScenes'] }}</div>
                            <div class="vw-bento-stat-label">{{ __('Decomposed') }}</div>
                        </div>
                    </div>
                    <div class="vw-bento-card span-3">
                        <div class="vw-bento-stat green">
                            <div class="vw-bento-stat-value">{{ $shotStats['totalShots'] }}</div>
                            <div class="vw-bento-stat-label">{{ __('Total Shots') }}</div>
                        </div>
                    </div>
                    @else
                    <div class="vw-bento-card span-3">
                        <div class="vw-bento-stat green">
                            <div class="vw-bento-stat-value">{{ $imagesReadyCount }}/{{ $totalScenesCount }}</div>
                            <div class="vw-bento-stat-label">{{ __('Images') }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="vw-bento-card span-3">
                        <div class="vw-bento-stat amber">
                            <div class="vw-bento-stat-value">{{ $sceneTiming['sceneDuration'] }}s</div>
                            <div class="vw-bento-stat-label">{{ __('Per Scene') }}</div>
                        </div>
                    </div>

                    @if($multiShotMode['enabled'] && $shotStats)
                    {{-- Progress Cards (Multi-shot mode) --}}
                    <div class="vw-bento-card span-6">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.75rem; color: var(--vw-text); display: flex; align-items: center; gap: 0.35rem;">
                                🖼️ {{ __('Images Generated') }}
                            </span>
                            <span style="font-size: 0.8rem; font-weight: 600; color: #10b981;">{{ $shotStats['shotsWithImages'] }}/{{ $shotStats['totalShots'] }}</span>
                        </div>
                        <div style="height: 8px; background: var(--vw-border); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $shotStats['imageProgress'] }}%; background: linear-gradient(90deg, #10b981, #22c55e); border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <div class="vw-bento-card span-6">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.75rem; color: var(--vw-text); display: flex; align-items: center; gap: 0.35rem;">
                                🎬 {{ __('Videos Generated') }}
                            </span>
                            <span style="font-size: 0.8rem; font-weight: 600; color: #06b6d4;">{{ $shotStats['shotsWithVideos'] }}/{{ $shotStats['totalShots'] }}</span>
                        </div>
                        <div style="height: 8px; background: var(--vw-border); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $shotStats['videoProgress'] }}%; background: linear-gradient(90deg, #06b6d4, #22d3ee); border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    @endif
                </div>

            {{-- PHASE 6: Arc Template Selector --}}
            @if(!empty($emotionalArcData['values']))
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.5rem 0.75rem;
                    background: rgba(var(--vw-primary-rgb), 0.04);
                    border-radius: 0.5rem;
                    margin-bottom: 0.75rem;
                    border: 1px solid rgba(var(--vw-primary-rgb), 0.08);
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 0.25rem;
                        color: rgba(var(--vw-primary-rgb), 0.4);
                        font-size: 0.7rem;
                    ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        <span style="font-weight: 600;">{{ __('Emotional Arc') }}:</span>
                    </div>

                    <select
                        wire:model.live="arcTemplate"
                        wire:change="setArcTemplate($event.target.value)"
                        style="
                            background: rgba(0, 0, 0, 0.3);
                            border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        color: var(--vw-text);
                            padding: 0.25rem 0.5rem;
                            border-radius: 0.25rem;
                            font-size: 0.7rem;
                            cursor: pointer;
                        "
                    >
                        @foreach($arcTemplates as $key => $label)
                            <option value="{{ $key }}" {{ $arcTemplate === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Arc Summary --}}
                    @php
                        $arcSummary = $this->getArcSummary();
                    @endphp
                    @if($arcSummary['hasData'] ?? false)
                        <div style="
                            display: flex;
                            gap: 0.75rem;
                            margin-left: auto;
                            font-size: 0.65rem;
                            color: var(--vw-text-secondary);
                        ">
                            <span>{{ __('Shots') }}: {{ $arcSummary['shotCount'] ?? 0 }}</span>
                            <span>{{ __('Peak') }}: {{ $arcSummary['peakIntensity'] ?? '0%' }}</span>
                            <span>{{ __('Climax') }}: {{ $arcSummary['climaxScene'] ?? 'N/A' }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Scene Stats Bar --}}
            @php
                $paginatedData = $this->paginatedScenes;
                $showPagination = $paginatedData['totalPages'] > 1;
            @endphp
            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; background: rgba(var(--vw-primary-rgb), 0.04); border: 1px solid rgba(var(--vw-primary-rgb), 0.06); border-radius: 0.5rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span>🖼️</span>
                    <span style="font-weight: 600; color: #10b981;">{{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}</span>
                    <span style="color: var(--vw-text-secondary); font-size: 0.75rem;">{{ __('images') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span>🎬</span>
                    <span style="font-weight: 600; color: var(--vw-primary);">{{ $paginatedData['totalScenes'] }}</span>
                    <span style="color: var(--vw-text-secondary); font-size: 0.75rem;">{{ __('scenes') }}</span>
                </div>
                @if($showPagination)
                    <span style="color: var(--vw-text-secondary); font-size: 0.75rem;">
                        {{ __('Showing') }} {{ $paginatedData['showingFrom'] }}-{{ $paginatedData['showingTo'] }}
                    </span>
                @endif
            </div>

            {{-- Pagination Controls (Top) --}}
            @if($showPagination)
                <div class="vw-pagination-controls" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 0.5rem;">
                    <button type="button"
                            wire:click="previousStoryboardPage"
                            @disabled(!$paginatedData['hasPrevious'])
                            style="padding: 0.4rem 0.75rem; border-radius: 0.35rem; border: 1px solid {{ $paginatedData['hasPrevious'] ? 'var(--vw-border-focus)' : 'var(--vw-border)' }}; background: {{ $paginatedData['hasPrevious'] ? 'rgba(var(--vw-primary-rgb), 0.06)' : 'rgba(0,0,0,0.02)' }}; color: {{ $paginatedData['hasPrevious'] ? 'white' : 'var(--vw-text-secondary)' }}; cursor: {{ $paginatedData['hasPrevious'] ? 'pointer' : 'not-allowed' }}; font-size: 0.75rem; font-weight: 600;">
                        ← {{ __('Previous') }}
                    </button>

                    <div style="display: flex; gap: 0.25rem;">
                        @for($p = 1; $p <= min($paginatedData['totalPages'], 7); $p++)
                            @php
                                // Show first, last, current, and adjacent pages
                                $showPage = $p <= 2 ||
                                           $p > $paginatedData['totalPages'] - 2 ||
                                           abs($p - $paginatedData['currentPage']) <= 1;
                                $showEllipsis = !$showPage && (
                                    ($p == 3 && $paginatedData['currentPage'] > 4) ||
                                    ($p == $paginatedData['totalPages'] - 2 && $paginatedData['currentPage'] < $paginatedData['totalPages'] - 3)
                                );
                            @endphp
                            @if($showPage)
                                <button type="button"
                                        wire:click="goToStoryboardPage({{ $p }})"
                                        style="width: 32px; height: 32px; border-radius: 0.35rem; border: 1px solid {{ $p === $paginatedData['currentPage'] ? 'var(--vw-primary)' : 'var(--vw-border)' }}; background: {{ $p === $paginatedData['currentPage'] ? 'rgba(var(--vw-primary-rgb), 0.12)' : 'rgba(0,0,0,0.03)' }}; color: var(--at-text); cursor: pointer; font-size: 0.75rem; font-weight: {{ $p === $paginatedData['currentPage'] ? '700' : '500' }};">
                                    {{ $p }}
                                </button>
                            @elseif($showEllipsis)
                                <span style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: var(--vw-text-secondary);">…</span>
                            @endif
                        @endfor
                    </div>

                    <button type="button"
                            wire:click="nextStoryboardPage"
                            @disabled(!$paginatedData['hasNext'])
                            style="padding: 0.4rem 0.75rem; border-radius: 0.35rem; border: 1px solid {{ $paginatedData['hasNext'] ? 'var(--vw-border-focus)' : 'var(--vw-border)' }}; background: {{ $paginatedData['hasNext'] ? 'rgba(var(--vw-primary-rgb), 0.06)' : 'rgba(0,0,0,0.02)' }}; color: {{ $paginatedData['hasNext'] ? 'white' : 'var(--vw-text-secondary)' }}; cursor: {{ $paginatedData['hasNext'] ? 'pointer' : 'not-allowed' }}; font-size: 0.75rem; font-weight: 600;">
                        {{ __('Next') }} →
                    </button>

                    {{-- Jump to page dropdown --}}
                    <select wire:model.live="storyboardPage"
                            style="padding: 0.4rem 0.5rem; border-radius: 0.35rem; border: 1px solid var(--vw-border); background: rgba(0,0,0,0.03); color: var(--at-text); font-size: 0.7rem; cursor: pointer;">
                        @for($p = 1; $p <= $paginatedData['totalPages']; $p++)
                            <option value="{{ $p }}">{{ __('Page') }} {{ $p }}</option>
                        @endfor
                    </select>
                </div>
            @endif

            {{-- Skeleton Loading Grid (shows during initial load) --}}
            <div class="vw-storyboard-grid"
                 wire:loading.flex
                 wire:target="goToStoryboardPage,previousStoryboardPage,nextStoryboardPage"
                 style="display: none;">
                @for($i = 0; $i < 6; $i++)
                <div class="vw-skeleton-card">
                    <div class="vw-skeleton-image"></div>
                    <div class="vw-skeleton-content">
                        <div class="vw-skeleton-line medium"></div>
                        <div class="vw-skeleton-line short"></div>
                    </div>
                </div>
                @endfor
            </div>

            {{-- Storyboard Grid - Using Paginated Scenes (Grid View) --}}
            {{-- PERF-07: Lazy-loaded SceneCard components for normalized projects --}}
            @php
                $isNormalized = $this->usesNormalizedData();
            @endphp

            <div class="vw-storyboard-grid"
                 x-show="viewMode === 'grid'"
                 x-transition
                 wire:loading.remove
                 wire:target="goToStoryboardPage,previousStoryboardPage,nextStoryboardPage">

            @if($isNormalized)
                {{-- Normalized data: Use lazy-loaded SceneCard components --}}
                {{-- Scene data loads on-demand when card enters viewport --}}
                @php
                    // Apply pagination to sceneIds
                    $sceneIdsOffset = ($paginatedData['currentPage'] - 1) * $storyboardPerPage;
                    $paginatedSceneIds = array_slice($this->sceneIds, $sceneIdsOffset, $storyboardPerPage);
                @endphp
                @foreach($paginatedSceneIds as $localIndex => $sceneId)
                    @php
                        $sceneIndex = $sceneIdsOffset + $localIndex;
                    @endphp
                    <livewire:app-video-wizard::components.scene-card
                        :scene-id="$sceneId"
                        :project-id="$projectId"
                        :scene-index="$sceneIndex"
                        :is-normalized="true"
                        :json-scene-data="null"
                        :storyboard-data="null"
                        :multi-shot-data="$multiShotMode['decomposedScenes'][$sceneIndex] ?? null"
                        lazy
                        wire:key="scene-card-normalized-{{ $sceneId }}"
                    />
                @endforeach
            @else
                {{-- JSON fallback: Inline rendering with data passed directly --}}
                {{-- This preserves all existing functionality for non-migrated projects --}}
            @foreach($paginatedData['scenes'] as $localIndex => $scene)
                @php
                    // Get the actual index in the full scenes array
                    $index = $paginatedData['indices'][$localIndex] ?? $localIndex;
                @endphp
                @php
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                    $status = $storyboardScene['status'] ?? 'pending';
                    $source = $storyboardScene['source'] ?? 'ai';
                    $prompt = $storyboardScene['prompt'] ?? $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';
                    $hasMultiShot = isset($multiShotMode['decomposedScenes'][$index]);
                    $decomposed = $hasMultiShot ? $multiShotMode['decomposedScenes'][$index] : null;
                    $hasChainData = isset($storyboard['promptChain']['scenes'][$index]) && ($storyboard['promptChain']['status'] ?? '') === 'ready';

                    // Scene state detection for visual indicators
                    $cardState = match(true) {
                        $status === 'error' => 'error',
                        $status === 'generating' => 'generating',
                        $hasMultiShot && !empty($decomposed['shots']) => 'decomposed',
                        !empty($imageUrl) => 'ready',
                        default => 'empty',
                    };
                @endphp
                <div class="vw-scene-card vw-scene-card--{{ $cardState }}" wire:key="scene-card-{{ $index }}">

                    {{-- Phase 3: Brainstorm Suggestions Panel --}}
                    <div class="vw-brainstorm-panel"
                         x-show="brainstorm.open && brainstorm.sceneIndex === {{ $index }}"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-cloak>
                        <div class="vw-brainstorm-header">
                            <div class="vw-brainstorm-title">
                                <span>💡</span>
                                <span>{{ __('AI Suggestions') }}</span>
                                <span class="vw-brainstorm-badge">{{ __('Scene') }} {{ $index + 1 }}</span>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button"
                                        class="vw-brainstorm-refresh"
                                        @click="fetchBrainstormSuggestions({{ $index }})"
                                        :disabled="brainstorm.loading">
                                    <span x-show="!brainstorm.loading">🔄</span>
                                    <span x-show="brainstorm.loading" class="vw-brainstorm-loading-spinner"></span>
                                    <span>{{ __('Refresh') }}</span>
                                </button>
                                <button type="button"
                                        @click="closeBrainstorm()"
                                        style="padding: 0.25rem 0.5rem; background: none; border: none; color: var(--vw-text-secondary); cursor: pointer; font-size: 1rem;">
                                    ×
                                </button>
                            </div>
                        </div>
                        <div class="vw-brainstorm-body">
                            <template x-if="brainstorm.loading">
                                <div class="vw-brainstorm-loading">
                                    <span class="vw-brainstorm-loading-spinner"></span>
                                    <span>{{ __('Generating creative suggestions...') }}</span>
                                </div>
                            </template>
                            <template x-if="!brainstorm.loading && brainstorm.suggestions.length === 0">
                                <div class="vw-brainstorm-empty">
                                    {{ __('No suggestions available. Click refresh to generate new ideas.') }}
                                </div>
                            </template>
                            <template x-if="!brainstorm.loading && brainstorm.suggestions.length > 0">
                                <div class="vw-brainstorm-suggestions">
                                    <template x-for="(suggestion, idx) in brainstorm.suggestions" :key="idx">
                                        <div class="vw-brainstorm-suggestion"
                                             @click="$wire.appendToScenePrompt({{ $index }}, suggestion.text)">
                                            <div class="vw-brainstorm-suggestion-icon" x-text="suggestion.icon"></div>
                                            <div class="vw-brainstorm-suggestion-content">
                                                <div class="vw-brainstorm-suggestion-type" x-text="suggestion.type"></div>
                                                <div class="vw-brainstorm-suggestion-text" x-text="suggestion.text"></div>
                                            </div>
                                            <button type="button" class="vw-brainstorm-suggestion-apply">
                                                {{ __('Apply') }} →
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Image Container with Overlays --}}
                    <div style="position: relative;">
                        {{-- Scene Number Badge - Always visible, top-left --}}
                        <div style="position: absolute; top: 0.75rem; left: 0.75rem; background: rgba(0,0,0,0.8); color: white; padding: 0.35rem 0.75rem; border-radius: 0.35rem; font-size: 0.9rem; font-weight: 600; z-index: 10;">
                            {{ __('Scene') }} {{ $index + 1 }}
                        </div>

                        {{-- Multi-Shot Badge - Compact, top right --}}
                        @if($hasMultiShot && !empty($decomposed['shots']))
                            @php
                                $shotChainStatusBadge = $this->getShotChainStatus($index);
                            @endphp
                            <div style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10; display: flex; align-items: center; gap: 0.35rem;">
                                <span style="background: linear-gradient(135deg, var(--vw-primary), #06b6d4); color: white; padding: 0.25rem 0.5rem; border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                                    📽️ {{ count($decomposed['shots']) }}
                                </span>
                                @if($shotChainStatusBadge['imagesReady'] > 0)
                                    <span style="background: rgba(16,185,129,0.9); color: white; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600;">
                                        🖼️ {{ $shotChainStatusBadge['imagesReady'] }}/{{ $shotChainStatusBadge['totalShots'] }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- MAIN Badge - Below scene number when chain data is ready --}}
                        @if($hasChainData && ($storyboard['promptChain']['enabled'] ?? true))
                            <div style="position: absolute; top: 3rem; left: {{ $imageUrl ? '5rem' : '0.75rem' }}; background: rgba(236,72,153,0.9); color: white; padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700; z-index: 10; letter-spacing: 0.3px;">
                                {{ __('MAIN') }}
                            </div>
                        @endif

                        {{-- Main Image Content Area --}}
                        <div class="vw-scene-image-container">
                            @if($status === 'generating')
                                {{-- Phase 3: Progressive Generation Preview --}}
                                <div class="vw-generation-preview"
                                     x-data="{
                                         progress: 0,
                                         status: '{{ __('Initializing...') }}',
                                         substatus: '{{ __('Connecting to AI') }}',
                                         stages: [
                                             { p: 10, s: '{{ __('Processing prompt...') }}', sub: '{{ __('Analyzing scene') }}' },
                                             { p: 25, s: '{{ __('Generating base...') }}', sub: '{{ __('Creating composition') }}' },
                                             { p: 45, s: '{{ __('Adding details...') }}', sub: '{{ __('Rendering textures') }}' },
                                             { p: 65, s: '{{ __('Refining image...') }}', sub: '{{ __('Enhancing lighting') }}' },
                                             { p: 85, s: '{{ __('Final touches...') }}', sub: '{{ __('Color grading') }}' },
                                             { p: 95, s: '{{ __('Almost ready...') }}', sub: '{{ __('Optimizing output') }}' }
                                         ],
                                         stageIdx: 0,
                                         init() {
                                             this.runProgress();
                                         },
                                         async runProgress() {
                                             for (let i = 0; i < this.stages.length; i++) {
                                                 await new Promise(r => setTimeout(r, 2000 + Math.random() * 1500));
                                                 this.progress = this.stages[i].p;
                                                 this.status = this.stages[i].s;
                                                 this.substatus = this.stages[i].sub;
                                             }
                                         }
                                     }"
                                     style="height: 220px; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6,182,212,0.1)); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden;">

                                    {{-- Animated background gradient --}}
                                    <div style="position: absolute; inset: 0; background: linear-gradient(45deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6,182,212,0.1), rgba(236,72,153,0.1)); background-size: 400% 400%; animation: vw-gradient-shift 4s ease infinite;"></div>

                                    {{-- Scan line effect --}}
                                    <div style="position: absolute; inset: 0; overflow: hidden; pointer-events: none;">
                                        <div style="position: absolute; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--vw-border-focus), transparent); animation: vw-scan-line 2s linear infinite;"></div>
                                    </div>

                                    {{-- Progress content --}}
                                    <div style="position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                                        {{-- AI Icon with pulse --}}
                                        <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6,182,212,0.3)); display: flex; align-items: center; justify-content: center; animation: vw-pulse 1.5s ease-in-out infinite;">
                                            <span style="font-size: 1.5rem;">🎨</span>
                                        </div>

                                        {{-- Progress bar --}}
                                        <div class="vw-generation-preview-progress" style="width: 180px; height: 6px; background: var(--vw-border); border-radius: 3px; overflow: hidden;">
                                            <div class="vw-generation-preview-bar"
                                                 :style="'width: ' + progress + '%'"
                                                 style="height: 100%; background: linear-gradient(90deg, var(--vw-primary), #06b6d4); border-radius: 3px; transition: width 0.5s ease;"></div>
                                        </div>

                                        {{-- Status text --}}
                                        <div style="text-align: center;">
                                            <div class="vw-generation-preview-status" x-text="status" style="font-size: 0.85rem; color: white; font-weight: 500;"></div>
                                            <div class="vw-generation-preview-substatus" x-text="substatus" style="font-size: 0.7rem; color: var(--vw-text-secondary); margin-top: 0.25rem;"></div>
                                        </div>

                                        {{-- AI Confidence indicator --}}
                                        <div class="vw-ai-confidence" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.65rem; color: var(--vw-text-secondary);">
                                            <span>{{ __('AI Confidence:') }}</span>
                                            <div class="vw-ai-confidence-bar" style="width: 50px; height: 3px; background: var(--vw-border); border-radius: 2px; overflow: hidden;">
                                                <div class="vw-ai-confidence-fill high" style="width: 85%; height: 100%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                                            </div>
                                            <span>{{ __('High') }}</span>
                                        </div>
                                    </div>

                                    {{-- Cancel button --}}
                                    <button type="button"
                                            wire:click="cancelImageGeneration({{ $index }})"
                                            wire:confirm="{{ __('Cancel this generation? You can retry afterwards.') }}"
                                            style="position: absolute; bottom: 0.75rem; right: 0.75rem; padding: 0.35rem 0.75rem; border-radius: 0.35rem; border: 1px solid rgba(239,68,68,0.4); background: rgba(239,68,68,0.15); color: #f87171; cursor: pointer; font-size: 0.7rem; transition: all 0.2s; z-index: 10;"
                                            onmouseover="this.style.background='rgba(239,68,68,0.3)'"
                                            onmouseout="this.style.background='rgba(239,68,68,0.15)'"
                                            title="{{ __('Cancel and retry') }}">
                                        ✕ {{ __('Cancel') }}
                                    </button>
                                </div>
                            @elseif($imageUrl)
                                {{-- Image Ready --}}
                                <img src="{{ $imageUrl }}"
                                     alt="Scene {{ $index + 1 }}"
                                     class="vw-scene-image"
                                     loading="lazy"
                                     data-scene-id="{{ $scene['id'] }}"
                                     data-retry-count="0"
                                     onload="this.dataset.loaded='true'; this.parentElement.querySelector('.vw-image-placeholder')?.style && (this.parentElement.querySelector('.vw-image-placeholder').style.display='none');"
                                     onerror="
                                        this.onerror=null;
                                        const retryCount = parseInt(this.dataset.retryCount || '0');
                                        if (retryCount < 3) {
                                            this.dataset.retryCount = retryCount + 1;
                                            setTimeout(() => {
                                                const url = this.src.split('&t=')[0].split('?t=')[0];
                                                this.src = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
                                                this.onerror = function() {
                                                    this.style.display='none';
                                                    this.parentElement.querySelector('.vw-image-placeholder').style.display='flex';
                                                };
                                            }, 2000);
                                        } else {
                                            this.style.display='none';
                                            this.parentElement.querySelector('.vw-image-placeholder').style.display='flex';
                                        }
                                     ">
                                {{-- Placeholder with retry option if image fails after retries --}}
                                <div class="vw-image-placeholder" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); gap: 0.5rem;">
                                    <span style="font-size: 1.5rem;">🖼️</span>
                                    <span style="font-size: 0.7rem; color: var(--vw-text);">{{ __('Image not available') }}</span>
                                    <button type="button"
                                            wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                            style="padding: 0.3rem 0.6rem; border-radius: 0.3rem; border: 1px solid var(--vw-border-focus); background: rgba(var(--vw-primary-rgb), 0.12); color: white; cursor: pointer; font-size: 0.65rem;">
                                        🔄 {{ __('Regenerate') }}
                                    </button>
                                </div>

                                @php
                                    $isVideo = $source === 'stock-video';
                                    $sourceBgColor = $source === 'stock' ? 'rgba(16,185,129,0.9)' : ($isVideo ? 'rgba(6,182,212,0.9)' : 'rgba(var(--vw-primary-rgb), 0.4)');
                                    $sourceLabel = $source === 'stock' ? '📷 ' . __('Stock') : ($isVideo ? '🎬 ' . __('Video') : '🎨 ' . __('AI'));
                                    $clipDuration = $storyboardScene['stockInfo']['clipDuration'] ?? $storyboardScene['stockInfo']['duration'] ?? null;
                                @endphp

                                {{-- Video Play Icon Overlay --}}
                                @if($isVideo)
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 48px; height: 48px; background: rgba(0,0,0,0.6); border-radius: 50%; display: flex; align-items: center; justify-content: center; pointer-events: none; z-index: 5;">
                                        <div style="width: 0; height: 0; border-left: 14px solid white; border-top: 8px solid transparent; border-bottom: 8px solid transparent; margin-left: 3px;"></div>
                                    </div>
                                    @if($clipDuration)
                                        <div style="position: absolute; bottom: 3rem; right: 0.5rem; background: rgba(0,0,0,0.8); color: white; padding: 0.2rem 0.45rem; border-radius: 0.25rem; font-size: 0.75rem; z-index: 10;">
                                            {{ gmdate($clipDuration >= 3600 ? 'H:i:s' : 'i:s', (int)$clipDuration) }}
                                        </div>
                                    @endif
                                @endif

                                {{-- Source Badge - Below scene number --}}
                                <div style="position: absolute; top: 3rem; left: 0.75rem; background: {{ $sourceBgColor }}; color: white; padding: 0.3rem 0.6rem; border-radius: 0.3rem; font-size: 0.8rem; z-index: 10;">
                                    {!! $sourceLabel !!}
                                </div>

                                {{-- Bottom overlay removed — actions moved to card action bar below --}}
                            @elseif($status === 'error')
                                {{-- Error State --}}
                                <div style="height: 220px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                        <span style="font-size: 1.25rem;">⚠️</span>
                                        <span style="color: #ef4444; font-size: 0.9rem;">{{ Str::limit($storyboardScene['error'] ?? __('Generation failed'), 50) }}</span>
                                    </div>
                                    <div style="color: var(--vw-text-secondary); font-size: 0.85rem; margin-bottom: 0.75rem;">{{ __('Choose to retry:') }}</div>
                                    <div style="display: flex; gap: 0.75rem; width: 100%; max-width: 320px;">
                                        <button type="button"
                                                wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                wire:loading.attr="disabled"
                                                style="flex: 1; padding: 0.75rem 0.5rem; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6,182,212,0.3)); border: 1px solid var(--vw-border-accent); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                            <span style="font-size: 1.25rem;">🎨</span>
                                            <span>{{ __('Retry AI') }}</span>
                                        </button>
                                        <button type="button"
                                                wire:click="openStockBrowser({{ $index }})"
                                                style="flex: 1; padding: 0.75rem 0.5rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                            <span style="font-size: 1.25rem;">📷</span>
                                            <span>{{ __('Use Stock') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Empty/Pending State --}}
                                @php
                                    // Find background image from decomposed shots if available
                                    $emptyStateBgImage = null;
                                    if ($hasMultiShot && !empty($decomposed['shots'])) {
                                        foreach ($decomposed['shots'] as $bgShot) {
                                            if (!empty($bgShot['imageUrl']) && ($bgShot['imageStatus'] ?? $bgShot['status'] ?? '') === 'ready') {
                                                $emptyStateBgImage = $bgShot['imageUrl'];
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                {{-- Show loading spinner while generating (wire:loading targets this specific scene) --}}
                                <div class="vw-scene-generating"
                                     wire:loading
                                     wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                     style="display: none;">
                                    <div class="vw-spinner"></div>
                                    <span class="vw-generating-text">{{ __('Generating...') }}</span>
                                </div>
                                <div class="vw-scene-empty {{ $emptyStateBgImage ? 'has-bg-image' : '' }}"
                                     wire:loading.remove
                                     wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                     @if($emptyStateBgImage) style="background: url('{{ $emptyStateBgImage }}'); background-size: cover; background-position: center; border: none;" @endif>

                                    @if($emptyStateBgImage)
                                        {{-- DYNAMIC: When has background image - Show image clearly with bottom toolbar --}}
                                        <div class="vw-empty-with-preview">
                                            {{-- Light gradient overlay at bottom only --}}
                                            <div class="vw-preview-gradient"></div>

                                            {{-- Compact bottom toolbar --}}
                                            <div class="vw-preview-toolbar">
                                                <span class="vw-preview-label">{{ __('Select main image:') }}</span>
                                                <div class="vw-preview-actions">
                                                    <button type="button"
                                                            class="vw-preview-btn ai"
                                                            wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                            title="{{ __('AI Generate') }}">
                                                        <span wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">🎨 {{ __('Generate') }}</span>
                                                        <span wire:loading wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">⏳</span>
                                                    </button>
                                                    <button type="button"
                                                            class="vw-preview-btn stock"
                                                            wire:click="openStockBrowser({{ $index }})"
                                                            title="{{ __('Stock Media') }}">
                                                        📷 {{ __('Stock') }}
                                                    </button>
                                                    <button type="button"
                                                            class="vw-preview-btn collage"
                                                            wire:click="generateCollagePreview({{ $index }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="generateCollagePreview({{ $index }})"
                                                            title="{{ __('Collage First') }}">
                                                        <span wire:loading.remove wire:target="generateCollagePreview({{ $index }})">🖼️ {{ __('Collage') }}</span>
                                                        <span wire:loading wire:target="generateCollagePreview({{ $index }})">⏳</span>
                                                    </button>
                                                    <button type="button"
                                                            class="vw-preview-btn use-shot"
                                                            wire:click="useFirstReadyShot({{ $index }})"
                                                            title="{{ __('Use this shot as main image') }}">
                                                        ✓ {{ __('Use This') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{-- DYNAMIC: No background - Centered layout with cards --}}
                                        <div class="vw-empty-center">
                                            <div class="vw-empty-icon-float">🎬</div>
                                            <div class="vw-scene-empty-text">{{ __('Choose image source') }}</div>
                                            <div class="vw-scene-empty-buttons">
                                                <button type="button"
                                                        class="vw-scene-empty-btn ai"
                                                        wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                                    <span class="vw-scene-empty-btn-icon" wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">🎨</span>
                                                    <span wire:loading wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                                        <svg style="width: 20px; height: 20px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="vw-scene-empty-btn-label" wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">{{ __('AI Generate') }}</span>
                                                    <span class="vw-scene-empty-btn-cost" wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                                </button>
                                                <button type="button"
                                                        class="vw-scene-empty-btn stock"
                                                        wire:click="openStockBrowser({{ $index }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                                    <span class="vw-scene-empty-btn-icon">📷</span>
                                                    <span class="vw-scene-empty-btn-label">{{ __('Stock Media') }}</span>
                                                    <span class="vw-scene-empty-btn-cost">{{ __('FREE') }}</span>
                                                </button>
                                                <button type="button"
                                                        class="vw-scene-empty-btn collage"
                                                        wire:click="generateCollagePreview({{ $index }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="generateCollagePreview({{ $index }})">
                                                    <span class="vw-scene-empty-btn-icon" wire:loading.remove wire:target="generateCollagePreview({{ $index }})">🖼️</span>
                                                    <span wire:loading wire:target="generateCollagePreview({{ $index }})">
                                                        <svg style="width: 20px; height: 20px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="vw-scene-empty-btn-label" wire:loading.remove wire:target="generateCollagePreview({{ $index }})">{{ __('Collage First') }}</span>
                                                    <span class="vw-scene-empty-btn-cost" wire:loading.remove wire:target="generateCollagePreview({{ $index }})">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Legacy Collage Preview removed - now handled exclusively in multi-shot modal --}}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Card Action Bar — Progressive disclosure --}}
                    @if($imageUrl)
                        <div class="vw-card-actions" x-data="{ overflow: false }" @click.outside="overflow = false">
                            <button type="button" class="vw-card-action-btn vw-card-action-btn--primary" wire:click="openImageStudio('scene', {{ $index }})" title="{{ __('AI Image Studio') }}">
                                ✨ {{ __('Edit') }}
                            </button>
                            @if($hasMultiShot && !empty($decomposed['shots']))
                                <button type="button" class="vw-card-action-btn" wire:click="openMultiShotModal({{ $index }})" wire:loading.attr="disabled" title="{{ __('Multi-shot') }}">
                                    ✂️ {{ count($decomposed['shots']) }} {{ __('shots') }}
                                </button>
                            @elseif($multiShotMode['enabled'] ?? false)
                                <button type="button" class="vw-card-action-btn" wire:click="openMultiShotModal({{ $index }})" wire:loading.attr="disabled" title="{{ __('Decompose into shots') }}">
                                    ✂️ {{ __('Shots') }}
                                </button>
                            @endif
                            <div class="vw-card-actions-spacer"></div>
                            <button type="button" class="vw-card-action-btn vw-card-action-btn--ghost" wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')" wire:loading.attr="disabled" title="{{ __('Regenerate') }}">
                                🔄 {{ __('Regen') }}
                            </button>
                            <div class="vw-card-overflow">
                                <button type="button" class="vw-card-overflow-trigger" @click="overflow = !overflow" title="{{ __('More actions') }}">⋮</button>
                                <div class="vw-card-overflow-menu" x-show="overflow" x-transition x-cloak>
                                    <button type="button" class="vw-card-overflow-item" wire:click="openAssetHistory('scene', {{ $index }})" @click="overflow = false">🕐 {{ __('History') }}</button>
                                    <button type="button" class="vw-card-overflow-item" wire:click="openUpscaleModal({{ $index }})" @click="overflow = false">⬆️ {{ __('Upscale') }}</button>
                                    <button type="button" class="vw-card-overflow-item" wire:click="openStockBrowser({{ $index }})" @click="overflow = false">📷 {{ __('Stock') }}</button>
                                    <button type="button" class="vw-card-overflow-item" wire:click="openEditPromptModal({{ $index }})" @click="overflow = false">✏️ {{ __('Edit Prompt') }}</button>
                                    <div class="vw-card-overflow-divider"></div>
                                    <button type="button" class="vw-card-overflow-item" @click="fetchBrainstormSuggestions({{ $index }}); overflow = false">💡 {{ __('Brainstorm') }}</button>
                                    <button type="button" class="vw-card-overflow-item" wire:click="openSceneTextInspector({{ $index }})" @click="overflow = false">🔍 {{ __('Inspect') }}</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Multi-Shot Timeline (if decomposed) - Compact Horizontal Strip --}}
                    @if($hasMultiShot && !empty($decomposed['shots']))
                        @php
                            $shotChainStatus = $this->getShotChainStatus($index);
                            $totalShotDuration = $decomposed['totalDuration'] ?? array_sum(array_column($decomposed['shots'], 'duration'));
                        @endphp
                        <div wire:key="multi-shot-timeline-{{ $index }}"
                             x-data="{ expanded: false }"
                             style="padding: 0.4rem 0.5rem; border-top: 1px solid rgba(var(--vw-primary-rgb), 0.06); background: rgba(var(--vw-primary-rgb), 0.02); contain: layout;">
                            {{-- Compact Header row --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <button type="button" @click="expanded = !expanded" style="background: none; border: none; cursor: pointer; color: var(--vw-text-secondary); font-size: 0.6rem; padding: 0;">
                                        <span x-text="expanded ? '▼' : '▶'"></span>
                                    </button>
                                    <span style="font-size: 0.55rem; color: var(--vw-text-secondary); font-weight: 600;">
                                        📽️ {{ count($decomposed['shots']) }} {{ __('shots') }} • {{ $totalShotDuration }}s
                                    </span>
                                    <span style="font-size: 0.45rem; padding: 0.08rem 0.2rem; background: rgba(16,185,129,0.2); border-radius: 0.15rem; color: #10b981;">
                                        🖼️ {{ $shotChainStatus['imagesReady'] }}/{{ $shotChainStatus['totalShots'] }}
                                    </span>
                                </div>
                                <button type="button"
                                        wire:click="openMultiShotModal({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="vw-btn-loading"
                                        wire:target="openMultiShotModal({{ $index }})"
                                        class="vw-edit-shots-btn">
                                    <span wire:loading.remove wire:target="openMultiShotModal({{ $index }})">✂️</span>
                                    <span wire:loading wire:target="openMultiShotModal({{ $index }})" class="vw-btn-spinner"></span>
                                    {{ __('Edit Shots') }}
                                </button>
                            </div>
                            {{-- Horizontal Scrollable Shot Strip - Wrapped for proper collapse --}}
                            <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-40" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 max-h-40" x-transition:leave-end="opacity-0 max-h-0" style="overflow: hidden;">
                                <div wire:key="shots-grid-{{ $index }}" style="display: flex; flex-direction: row; gap: 0.25rem; overflow-x: auto; padding: 0.25rem 0; scrollbar-width: none; -webkit-overflow-scrolling: touch;">
                                @foreach($decomposed['shots'] as $shotIdx => $shot)
                                    @php
                                        $isSelected = ($decomposed['selectedShot'] ?? 0) === $shotIdx;
                                        $shotStatus = $shot['imageStatus'] ?? $shot['status'] ?? 'pending';
                                        $videoStatus = $shot['videoStatus'] ?? 'pending';
                                        $hasImage = $shotStatus === 'ready' && !empty($shot['imageUrl']);
                                        $hasVideo = $videoStatus === 'ready' && !empty($shot['videoUrl']);
                                        $isFromFrame = $shot['fromFrameCapture'] ?? false;
                                        $isFromScene = $shot['fromSceneImage'] ?? false;
                                        $shotType = ucfirst($shot['type'] ?? 'shot');
                                        $shotDuration = $shot['duration'] ?? 10;
                                        $shotNeedsLipSync = $shot['needsLipSync'] ?? false;
                                        $shotSpeechSegments = $shot['speechSegments'] ?? [];
                                        $shotTypeIcons = [
                                            'establishing' => '🏔️',
                                            'medium' => '👤',
                                            'close-up' => '🔍',
                                            'reaction' => '😮',
                                            'detail' => '✨',
                                            'wide' => '🌄',
                                        ];
                                        $shotIcon = $shotTypeIcons[strtolower($shot['type'] ?? '')] ?? '🎬';
                                        $borderColor = $hasVideo ? 'rgba(6,182,212,0.6)' : ($hasImage ? 'rgba(16,185,129,0.5)' : ($isSelected ? 'var(--vw-primary)' : 'var(--vw-border)'));
                                    @endphp
                                    <div wire:key="shot-thumb-{{ $index }}-{{ $shotIdx }}"
                                         style="cursor: pointer; position: relative; border-radius: 0.35rem; overflow: hidden; border: 2px solid {{ $borderColor }}; background: {{ $isSelected ? 'rgba(var(--vw-primary-rgb), 0.06)' : 'rgba(0,0,0,0.2)' }}; flex-shrink: 0; width: 90px;"
                                         wire:click="openMultiShotModal({{ $index }})"
                                         title="{{ $shot['description'] ?? 'Shot ' . ($shotIdx + 1) }} ({{ $shotDuration }}s)">
                                        {{-- Larger Thumbnail --}}
                                        <div style="aspect-ratio: 16/10; position: relative; contain: strict;">
                                            @if($hasImage)
                                                <img src="{{ $shot['imageUrl'] }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                                {{-- Video play indicator --}}
                                                @if($hasVideo)
                                                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                                                        <div style="width: 20px; height: 20px; background: rgba(6,182,212,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                            <span style="font-size: 0.5rem; color: white; margin-left: 1px;">▶</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            @elseif($shotStatus === 'generating')
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(var(--vw-primary-rgb), 0.04);">
                                                    <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite; color: var(--vw-primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.02);">
                                                    <span style="font-size: 1rem;">{{ $shotIcon }}</span>
                                                </div>
                                            @endif

                                            {{-- Shot Number Badge --}}
                                            <div style="position: absolute; top: 2px; left: 2px; background: rgba(0,0,0,0.75); color: white; padding: 0.1rem 0.25rem; border-radius: 0.15rem; font-size: 0.5rem; font-weight: 600;">
                                                #{{ $shotIdx + 1 }}
                                            </div>

                                            {{-- Frame Chain Indicator --}}
                                            @if($isFromFrame)
                                                <div style="position: absolute; top: 2px; right: 2px; background: rgba(16,185,129,0.9); color: white; padding: 0.05rem 0.15rem; border-radius: 0.1rem; font-size: 0.4rem;">
                                                    🔗
                                                </div>
                                            @elseif($isFromScene && $shotIdx === 0)
                                                <div style="position: absolute; top: 2px; right: 2px; background: rgba(var(--vw-primary-rgb), 0.4); color: white; padding: 0.05rem 0.15rem; border-radius: 0.1rem; font-size: 0.4rem;">
                                                    📸
                                                </div>
                                            @endif

                                            {{-- Duration Badge --}}
                                            <div style="position: absolute; bottom: 2px; right: 2px; background: rgba(0,0,0,0.8); color: white; padding: 0.05rem 0.2rem; border-radius: 0.1rem; font-size: 0.45rem;">
                                                {{ $shotDuration }}s
                                            </div>

                                            {{-- Lip-Sync Indicator (bottom-left) --}}
                                            @if($shotNeedsLipSync)
                                                <div style="position: absolute; bottom: 2px; left: 2px; background: rgba(251,191,36,0.9); color: white; padding: 0.05rem 0.15rem; border-radius: 0.1rem; font-size: 0.4rem;" title="{{ __('Lip-sync required') }}">
                                                    👄
                                                </div>
                                            @elseif(!empty($shotSpeechSegments))
                                                <div style="position: absolute; bottom: 2px; left: 2px; background: rgba(100,116,139,0.8); color: white; padding: 0.05rem 0.15rem; border-radius: 0.1rem; font-size: 0.4rem;" title="{{ __('Has speech (no lip-sync)') }}">
                                                    🎙️
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Shot Status Bar --}}
                                        <div style="height: 3px; background: var(--vw-border);">
                                            @if($hasVideo)
                                                <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #06b6d4, #22d3ee);"></div>
                                            @elseif($hasImage)
                                                <div style="height: 100%; width: 50%; background: linear-gradient(90deg, #10b981, #22c55e);"></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>

                            {{-- Quick Actions (also in collapsible area) --}}
                            <div x-show="expanded" style="display: flex; gap: 0.25rem; margin-top: 0.3rem;">
                                @if($shotChainStatus['imagesReady'] < $shotChainStatus['totalShots'])
                                    <button type="button"
                                            wire:click="generateAllShots({{ $index }})"
                                            wire:loading.attr="disabled"
                                            style="flex: 1; padding: 0.2rem 0.35rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.2rem; color: #10b981; cursor: pointer; font-size: 0.5rem;">
                                        🖼️ {{ __('Generate All') }}
                                    </button>
                                @endif
                                @if($shotChainStatus['imagesReady'] > 0 && $shotChainStatus['videosReady'] < $shotChainStatus['totalShots'])
                                    <button type="button"
                                            wire:click="generateAllShotVideos({{ $index }})"
                                            wire:loading.attr="disabled"
                                            style="flex: 1; padding: 0.2rem 0.35rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.2rem; color: #06b6d4; cursor: pointer; font-size: 0.5rem;">
                                        🎬 {{ __('Animate All') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- PHASE 6: Dialogue/Narration Text --}}
                    @php
                        $scriptScene = $script['scenes'][$index] ?? null;
                        $speechSegments = $scriptScene['speechSegments'] ?? [];
                        $narration = $scriptScene['narration'] ?? '';

                        // Count segment types (not just boolean presence) for accurate representation
                        $typeCounts = collect($speechSegments)->groupBy('type')->map->count();
                        $totalSegments = count($speechSegments);
                        $hasMultipleTypes = $typeCounts->count() > 1;

                        // Determine dominant type (>80% threshold) or use "Mixed"
                        $dominantType = null;
                        $speechLabel = 'NARRATION'; // Default
                        $speechIcon = '🎙️';
                        $speechDetailLabel = '';

                        if ($totalSegments > 0) {
                            foreach ($typeCounts as $type => $count) {
                                $percentage = ($count / $totalSegments) * 100;
                                if ($percentage > 80) {
                                    $dominantType = $type;
                                    break;
                                }
                            }

                            if ($dominantType) {
                                // Single dominant type
                                $speechLabel = strtoupper($dominantType === 'narrator' ? 'NARRATION' : $dominantType);
                                $speechIcon = [
                                    'narrator' => '🎙️',
                                    'dialogue' => '💬',
                                    'internal' => '💭',
                                    'monologue' => '🗣️',
                                ][$dominantType] ?? '🎙️';
                                $speechDetailLabel = "({$totalSegments} segment" . ($totalSegments > 1 ? 's' : '') . ')';
                            } else {
                                // Mixed types
                                $speechLabel = 'MIXED';
                                $speechIcon = '🎭'; // Mixed icon

                                // Build detailed breakdown: "5 segments: 3 dialogue, 2 narration"
                                $typeBreakdown = [];
                                foreach ($typeCounts->sortDesc() as $type => $count) {
                                    $typeName = $type === 'narrator' ? 'narration' : $type;
                                    $typeBreakdown[] = "{$count} {$typeName}";
                                }
                                $speechDetailLabel = "({$totalSegments} segments: " . implode(', ', $typeBreakdown) . ')';
                            }
                        }

                        // Type icons mapping for segments with accessibility labels
                        $typeIcons = [
                            'narrator' => ['icon' => '🎙️', 'color' => 'rgba(14, 165, 233, 0.4)', 'border' => 'rgba(14, 165, 233, 0.6)', 'label' => 'NARRATOR', 'lipSync' => false],
                            'dialogue' => ['icon' => '💬', 'color' => 'rgba(34, 197, 94, 0.4)', 'border' => 'rgba(34, 197, 94, 0.6)', 'label' => 'DIALOGUE', 'lipSync' => true],
                            'internal' => ['icon' => '💭', 'color' => 'rgba(var(--vw-primary-rgb), 0.4)', 'border' => 'rgba(var(--vw-primary-rgb), 0.6)', 'label' => 'INTERNAL', 'lipSync' => false],
                            'monologue' => ['icon' => '🗣️', 'color' => 'rgba(251, 191, 36, 0.4)', 'border' => 'rgba(251, 191, 36, 0.6)', 'label' => 'MONOLOGUE', 'lipSync' => true],
                        ];
                    @endphp

                    @if(!empty($speechSegments) || !empty($narration))
                        <div style="padding: 0.3rem 0.75rem;" x-data="{ voiceModalOpen: false }">
                            <div class="vw-scene-dialogue" style="display: flex; justify-content: space-between; align-items: center;">
                                {{-- Voice Types clickable badge --}}
                                <button
                                    type="button"
                                    @click.stop.prevent="voiceModalOpen = true"
                                    class="vw-voice-types-btn"
                                    style="display: flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.5rem; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6,182,212,0.15)); border: 1px solid var(--vw-border-accent); border-radius: 0.3rem; cursor: pointer; transition: all 0.2s;"
                                >
                                    <span style="font-size: 0.75rem;">🎙️</span>
                                    <span style="font-weight: 600; font-size: 0.7rem; color: var(--at-text);">{{ __('Voice Types') }}</span>
                                    <span style="opacity: 0.6; font-size: 0.6rem; color: var(--vw-text);">({{ $totalSegments }})</span>
                                </button>

                                {{-- Inspect button --}}
                                <button
                                    type="button"
                                    wire:click.stop="openSceneTextInspector({{ $index }})"
                                    class="vw-inspect-btn"
                                    title="{{ __('Full scene details') }}"
                                    style="background: rgba(var(--vw-primary-rgb), 0.06); border: 1px solid rgba(var(--vw-primary-rgb), 0.12); color: var(--vw-primary); padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem; cursor: pointer; transition: all 0.2s;"
                                >
                                    🔍 {{ __('Inspect') }}
                                </button>
                            </div>

                            {{-- Voice Types Modal Overlay --}}
                            <template x-teleport="body">
                                <div
                                    x-show="voiceModalOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    @keydown.escape.window="voiceModalOpen = false"
                                    style="position: fixed; inset: 0; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; z-index: 1000100; padding: 1rem;"
                                    x-cloak
                                >
                                    {{-- Modal Content --}}
                                    <div
                                        x-show="voiceModalOpen"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 transform scale-95"
                                        x-transition:enter-end="opacity-100 transform scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 transform scale-100"
                                        x-transition:leave-end="opacity-0 transform scale-95"
                                        @click.outside="voiceModalOpen = false"
                                        style="background: linear-gradient(135deg, rgba(30,30,50,0.99), rgba(20,20,40,1)); border: 1px solid var(--vw-border-focus); border-radius: 0.75rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3); width: 100%; max-width: 480px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden;"
                                    >
                                        {{-- Modal Header --}}
                                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--vw-border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                                            <div>
                                                <h3 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--vw-text); display: flex; align-items: center; gap: 0.5rem;">
                                                    <span>🎙️</span> {{ __('Voice Types') }}
                                                </h3>
                                                <p style="margin: 0.2rem 0 0 0; font-size: 0.65rem; color: var(--vw-text-secondary);">
                                                    {{ __('Scene') }} {{ $index + 1 }} · {{ $totalSegments }} {{ __('segments') }}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="voiceModalOpen = false"
                                                style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: var(--vw-border); border: none; border-radius: 0.3rem; color: var(--vw-text); cursor: pointer; font-size: 1rem;"
                                            >×</button>
                                        </div>

                                        {{-- Summary Bar --}}
                                        <div style="padding: 0.5rem 1rem; background: rgba(0,0,0,0.2); display: flex; gap: 1rem; flex-shrink: 0;">
                                            @php
                                                $lipSyncCount = collect($speechSegments)->filter(fn($s) => ($typeIcons[$s['type'] ?? 'narrator']['lipSync'] ?? false))->count();
                                                $voiceoverCount = $totalSegments - $lipSyncCount;
                                            @endphp
                                            @if($lipSyncCount > 0)
                                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                    <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                                    <span style="font-size: 0.7rem; color: #16a34a;">{{ $lipSyncCount }} {{ __('Multitalk (Lip-sync)') }}</span>
                                                </div>
                                            @endif
                                            @if($voiceoverCount > 0)
                                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                    <span style="width: 8px; height: 8px; background: #0ea5e9; border-radius: 50%;"></span>
                                                    <span style="font-size: 0.7rem; color: #0891b2;">{{ $voiceoverCount }} {{ __('TTS (Voiceover)') }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Segments List --}}
                                        <div style="overflow-y: auto; flex: 1; padding: 0.75rem 1rem;">
                                            @forelse($speechSegments as $segIdx => $segment)
                                                @php
                                                    $segType = $segment['type'] ?? 'narrator';
                                                    $segConfig = $typeIcons[$segType] ?? $typeIcons['narrator'];
                                                    $segSpeaker = $segment['speaker'] ?? null;
                                                    $segText = $segment['text'] ?? '';
                                                    $segAudioUrl = $segment['audioUrl'] ?? null;
                                                    $needsLipSync = $segConfig['lipSync'] ?? false;
                                                    $wordCount = str_word_count($segText);
                                                    $estDuration = round(($wordCount / 150) * 60, 1);
                                                @endphp
                                                <div style="padding: 0.75rem; margin-bottom: 0.5rem; background: rgba(0,0,0,0.02); border-left: 3px solid {{ $segConfig['border'] }}; border-radius: 0 0.5rem 0.5rem 0;">
                                                    {{-- Segment Header --}}
                                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                                        <span style="font-size: 1.1rem;">{{ $segConfig['icon'] }}</span>
                                                        <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: {{ $segConfig['color'] }}; border-radius: 0.25rem;">
                                                            {{ $segConfig['label'] }}
                                                        </span>
                                                        @if($segSpeaker)
                                                            <span style="color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 600;">{{ $segSpeaker }}</span>
                                                        @endif
                                                        <span style="flex: 1;"></span>
                                                        @if($needsLipSync)
                                                            <span style="font-size: 0.6rem; padding: 0.15rem 0.35rem; background: rgba(16,185,129,0.2); color: #16a34a; border-radius: 0.2rem; border: 1px solid rgba(16,185,129,0.3);">MULTITALK</span>
                                                        @else
                                                            <span style="font-size: 0.6rem; padding: 0.15rem 0.35rem; background: rgba(14,165,233,0.2); color: #0891b2; border-radius: 0.2rem; border: 1px solid rgba(14,165,233,0.3);">TTS</span>
                                                        @endif
                                                        <span style="font-size: 0.6rem; color: var(--vw-text-secondary);">~{{ $estDuration }}s</span>
                                                    </div>

                                                    {{-- Segment Text --}}
                                                    <div style="font-size: 0.8rem; color: var(--vw-text); line-height: 1.5; margin-bottom: 0.5rem;">
                                                        {{ $segText }}
                                                    </div>

                                                    {{-- Audio Player --}}
                                                    @if($segAudioUrl)
                                                        <div x-data="{ playing: false, audioEl: null }">
                                                            <button
                                                                type="button"
                                                                @click="
                                                                    if (!audioEl) {
                                                                        audioEl = new Audio('{{ $segAudioUrl }}');
                                                                        audioEl.onended = () => playing = false;
                                                                    }
                                                                    if (playing) {
                                                                        audioEl.pause();
                                                                        audioEl.currentTime = 0;
                                                                        playing = false;
                                                                    } else {
                                                                        audioEl.play();
                                                                        playing = true;
                                                                    }
                                                                "
                                                                style="display: flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.6rem; background: rgba(var(--vw-primary-rgb), 0.08); border: 1px solid var(--vw-border-accent); border-radius: 0.3rem; color: var(--vw-primary); font-size: 0.7rem; cursor: pointer; transition: all 0.15s;"
                                                            >
                                                                <span x-text="playing ? '⏹️' : '▶️'"></span>
                                                                <span x-text="playing ? '{{ __('Stop') }}' : '{{ __('Play Audio') }}'"></span>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                @if(!empty($narration))
                                                    {{-- Legacy narration fallback --}}
                                                    <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-left: 3px solid rgba(14, 165, 233, 0.6); border-radius: 0 0.5rem 0.5rem 0;">
                                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                            <span style="font-size: 1.1rem;">🎙️</span>
                                                            <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: rgba(14, 165, 233, 0.4); border-radius: 0.25rem;">NARRATOR</span>
                                                            <span style="flex: 1;"></span>
                                                            <span style="font-size: 0.6rem; padding: 0.15rem 0.35rem; background: rgba(14,165,233,0.2); color: #0891b2; border-radius: 0.2rem;">TTS</span>
                                                        </div>
                                                        <div style="font-size: 0.8rem; color: var(--vw-text); line-height: 1.5;">
                                                            {{ $narration }}
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforelse
                                        </div>

                                        {{-- Modal Footer --}}
                                        <div style="padding: 0.75rem 1rem; border-top: 1px solid var(--vw-border); display: flex; gap: 0.5rem; flex-shrink: 0;">
                                            <button
                                                type="button"
                                                wire:click="openSceneTextInspector({{ $index }})"
                                                @click="voiceModalOpen = false"
                                                style="flex: 1; padding: 0.5rem 0.75rem; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6,182,212,0.25)); border: 1px solid var(--vw-border-focus); border-radius: 0.4rem; color: white; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;"
                                            >
                                                <span>🔍</span>
                                                <span>{{ __('Full Inspector') }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="voiceModalOpen = false"
                                                style="padding: 0.5rem 1rem; background: var(--vw-border); border: 1px solid var(--vw-border); border-radius: 0.4rem; color: var(--vw-text); font-size: 0.75rem; cursor: pointer;"
                                            >{{ __('Close') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    @endif

                    {{-- Prompt Comparison Section --}}
                    @php
                        // Get prompt data for comparison
                        // Original comes from scene's visual description (brief)
                        $originalPrompt = $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';
                        // Expanded comes from storyboard scene data (Hollywood-quality)
                        $expandedPrompt = $storyboardScene['expandedPrompt'] ?? $storyboardScene['prompt'] ?? $prompt ?? '';
                        // Expansion method: 'llm' for AI-expanded, 'template' for rule-based
                        $expansionMethod = $storyboardScene['expansionMethod'] ?? 'template';
                    @endphp
                    @include('appvideowizard::livewire.partials.prompt-comparison', [
                        'originalPrompt' => $originalPrompt,
                        'expandedPrompt' => $expandedPrompt,
                        'expansionMethod' => $expansionMethod,
                    ])
                </div>
            @endforeach
            @endif
            {{-- End PERF-07 normalized/JSON conditional --}}
            </div>

            {{-- Timeline View --}}
            <div class="vw-timeline-view" x-show="viewMode === 'timeline'" x-transition x-cloak>
                {{-- Timeline Header with Ruler --}}
                <div class="vw-timeline-header">
                    <span style="width: 120px;">{{ __('Scene') }}</span>
                    <div class="vw-timeline-ruler">
                        <span>0s</span>
                        <span>5s</span>
                        <span>10s</span>
                        <span>15s</span>
                        <span>20s</span>
                    </div>
                </div>

                {{-- Timeline Rows --}}
                @foreach($paginatedData['scenes'] as $localIndex => $scene)
                    @php
                        $index = $paginatedData['indices'][$localIndex] ?? $localIndex;
                        $storyboardScene = $storyboard['scenes'][$index] ?? null;
                        $imageUrl = $storyboardScene['imageUrl'] ?? null;
                        $status = $storyboardScene['status'] ?? 'pending';
                        $hasMultiShot = isset($multiShotMode['decomposedScenes'][$index]);
                        $decomposed = $hasMultiShot ? $multiShotMode['decomposedScenes'][$index] : null;
                        $sceneDuration = $scene['duration'] ?? 8;
                    @endphp
                    <div class="vw-timeline-row" wire:key="timeline-row-{{ $index }}">
                        <div class="vw-timeline-scene-info">
                            <div class="vw-timeline-scene-label">{{ __('Scene') }} {{ $index + 1 }}</div>
                            <div class="vw-timeline-scene-duration">{{ $sceneDuration }}s</div>
                        </div>
                        <div class="vw-timeline-shots">
                            @if($hasMultiShot && !empty($decomposed['shots']))
                                @foreach($decomposed['shots'] as $shotIndex => $shot)
                                    @php
                                        $shotImageUrl = $shot['imageUrl'] ?? null;
                                        $shotStatus = $shot['imageStatus'] ?? $shot['status'] ?? 'pending';
                                        $shotDuration = $shot['duration'] ?? 3;
                                    @endphp
                                    @if($shotStatus === 'generating')
                                        <div class="vw-timeline-shot generating" style="width: {{ max(80, $shotDuration * 20) }}px;" title="{{ __('Generating...') }}">
                                            <div class="vw-spinner" style="width: 1.5rem; height: 1.5rem; border-width: 2px;"></div>
                                        </div>
                                    @elseif($shotImageUrl)
                                        <div class="vw-timeline-shot"
                                             style="width: {{ max(80, $shotDuration * 20) }}px;"
                                             wire:click="$dispatch('open-multi-shot-modal', { sceneIndex: {{ $index }} })"
                                             title="{{ __('Shot') }} {{ $shotIndex + 1 }}: {{ $shot['type'] ?? 'medium' }}">
                                            <img src="{{ $shotImageUrl }}" alt="Shot {{ $shotIndex + 1 }}" loading="lazy">
                                            <span class="vw-timeline-shot-duration">{{ $shotDuration }}s</span>
                                        </div>
                                    @else
                                        <div class="vw-timeline-shot pending" style="width: {{ max(80, $shotDuration * 20) }}px;" title="{{ __('Pending') }}">
                                            <span style="font-size: 0.7rem; color: var(--vw-text-secondary);">{{ $shotIndex + 1 }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                {{-- Single shot for scene --}}
                                @if($status === 'generating')
                                    <div class="vw-timeline-shot generating" style="width: {{ max(80, $sceneDuration * 10) }}px;" title="{{ __('Generating...') }}">
                                        <div class="vw-spinner" style="width: 1.5rem; height: 1.5rem; border-width: 2px;"></div>
                                    </div>
                                @elseif($imageUrl)
                                    <div class="vw-timeline-shot"
                                         style="width: {{ max(80, $sceneDuration * 10) }}px;"
                                         wire:click="openImageStudio('scene', {{ $index }})"
                                         title="{{ __('Scene') }} {{ $index + 1 }}">
                                        <img src="{{ $imageUrl }}" alt="Scene {{ $index + 1 }}" loading="lazy">
                                        <span class="vw-timeline-shot-duration">{{ $sceneDuration }}s</span>
                                    </div>
                                @else
                                    <div class="vw-timeline-shot pending" style="width: {{ max(80, $sceneDuration * 10) }}px;"
                                         wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                         title="{{ __('Click to generate') }}">
                                        <span style="font-size: 1.2rem;">🎨</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

                </div> {{-- Close vw-workspace-content --}}
            </div> {{-- Close vw-workspace --}}
        </div> {{-- Close vw-storyboard-main --}}
    @endif

    {{-- Phase 2: Contextual Side Panel --}}
    <div class="vw-side-panel"
         :class="{ 'open': sidePanel.open }"
         x-show="sidePanel.open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="transform translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform translate-x-full"
         @keydown.escape.window="closeSidePanel()"
         x-cloak>
        <div class="vw-side-panel-header">
            <span class="vw-side-panel-title" x-text="sidePanel.type === 'scene' ? '{{ __('Scene Properties') }}' : '{{ __('Properties') }}'"></span>
            <button type="button" class="vw-side-panel-close" @click="closeSidePanel()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="vw-side-panel-content">
            {{-- Scene Properties Panel --}}
            <template x-if="sidePanel.type === 'scene' && sidePanel.sceneIndex !== null">
                <div>
                    @php
                        // Get scene data for side panel (will be updated via Alpine)
                        $panelSceneIndex = 0;
                        $panelScene = $script['scenes'][$panelSceneIndex] ?? null;
                        $panelStoryboardScene = $storyboard['scenes'][$panelSceneIndex] ?? null;
                    @endphp
                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Preview') }}</div>
                        <div class="vw-side-panel-preview">
                            <template x-if="$wire.storyboard?.scenes?.[sidePanel.sceneIndex]?.imageUrl">
                                <img :src="$wire.storyboard?.scenes?.[sidePanel.sceneIndex]?.imageUrl" alt="Scene preview">
                            </template>
                            <template x-if="!$wire.storyboard?.scenes?.[sidePanel.sceneIndex]?.imageUrl">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--vw-text-secondary);">
                                    {{ __('No image yet') }}
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Scene Info') }}</div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <span style="font-size: 1.25rem; font-weight: 700; color: var(--vw-primary);" x-text="'#' + (sidePanel.sceneIndex + 1)"></span>
                            <span style="font-size: 0.8rem; color: var(--vw-text);" x-text="$wire.script?.scenes?.[sidePanel.sceneIndex]?.title || '{{ __('Scene') }}'"></span>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Duration') }}</div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="number"
                                   min="1"
                                   max="60"
                                   style="width: 80px; padding: 0.5rem; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.35rem; color: var(--at-text); font-size: 0.85rem;"
                                   :value="$wire.script?.scenes?.[sidePanel.sceneIndex]?.duration || 8"
                                   @change="$wire.set(`script.scenes.${sidePanel.sceneIndex}.duration`, $event.target.value)">
                            <span style="font-size: 0.75rem; color: var(--vw-text-secondary);">{{ __('seconds') }}</span>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Quick Actions') }}</div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button type="button"
                                    @click="$wire.openImageStudio('scene', sidePanel.sceneIndex)"
                                    style="width: 100%; padding: 0.6rem; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(var(--vw-primary-rgb), 0.08)); border: 1px solid rgba(236,72,153,0.4); border-radius: 0.5rem; color: var(--at-text); cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                ✨ {{ __('AI Image Studio') }}
                            </button>
                            <button type="button"
                                    @click="$wire.generateImage(sidePanel.sceneIndex, $wire.script?.scenes?.[sidePanel.sceneIndex]?.id)"
                                    style="width: 100%; padding: 0.6rem; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.5rem; color: var(--at-text); cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🔄 {{ __('Regenerate') }}
                            </button>
                            <button type="button"
                                    @click="$wire.openMultiShotModal(sidePanel.sceneIndex)"
                                    style="width: 100%; padding: 0.6rem; background: rgba(var(--vw-primary-rgb), 0.04); border: 1px solid rgba(var(--vw-primary-rgb), 0.12); border-radius: 0.5rem; color: var(--at-text); cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                ✂️ {{ __('Multi-shot Decompose') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Phase 4: Keyboard Shortcuts Help Modal --}}
    <div class="vw-shortcuts-overlay"
         x-show="shortcuts.showHelp"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="shortcuts.showHelp = false"
         @keydown.escape.window="shortcuts.showHelp = false"
         x-cloak>
        <div class="vw-shortcuts-modal">
            <div class="vw-shortcuts-title">
                <span>⌨️</span>
                <span>{{ __('Keyboard Shortcuts') }}</span>
                <button type="button"
                        @click="shortcuts.showHelp = false"
                        style="margin-left: auto; background: none; border: none; color: var(--vw-text-secondary); cursor: pointer; font-size: 1.25rem; line-height: 1;">
                    ×
                </button>
            </div>

            <div class="vw-shortcuts-group">
                <div class="vw-shortcuts-group-title">{{ __('Navigation') }}</div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Toggle Grid/Timeline View') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">G</span>
                    </div>
                </div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Toggle Settings Panel') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">S</span>
                    </div>
                </div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Go to Scene 1-9') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">1</span>
                        <span style="color: var(--vw-text-secondary);">-</span>
                        <span class="vw-shortcut-key">9</span>
                    </div>
                </div>
            </div>

            <div class="vw-shortcuts-group">
                <div class="vw-shortcuts-group-title">{{ __('Appearance') }}</div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Toggle Light/Dark Theme') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">T</span>
                    </div>
                </div>
            </div>

            <div class="vw-shortcuts-group">
                <div class="vw-shortcuts-group-title">{{ __('Actions') }}</div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Show Keyboard Shortcuts') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">?</span>
                    </div>
                </div>
                <div class="vw-shortcut-row">
                    <span class="vw-shortcut-label">{{ __('Close Panel/Modal') }}</span>
                    <div class="vw-shortcut-keys">
                        <span class="vw-shortcut-key">Esc</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.04); text-align: center;">
                <span style="font-size: 0.7rem; color: var(--vw-text-secondary);">
                    {{ __('Press') }} <span class="vw-shortcut-key">?</span> {{ __('anytime to show this help') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Phase 4: Toast Notifications --}}
    <div class="vw-toast"
         :class="toast.type"
         x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-4"
         x-cloak>
        <span x-show="toast.type === 'success'" style="font-size: 1rem;">✅</span>
        <span x-show="toast.type === 'error'" style="font-size: 1rem;">❌</span>
        <span x-show="toast.type === 'info'" style="font-size: 1rem;">ℹ️</span>
        <span x-text="toast.message" style="color: var(--at-text); font-size: 0.85rem;"></span>
        <button type="button"
                @click="toast.show = false"
                style="background: none; border: none; color: var(--vw-text-secondary); cursor: pointer; font-size: 1rem; padding: 0 0.25rem;">
            ×
        </button>
    </div>

    {{-- Stock Media Browser Modal --}}
    @include('appvideowizard::livewire.modals.stock-browser')

    {{-- Style Bible Modal --}}
    @include('appvideowizard::livewire.modals.style-bible')

    {{-- Character Bible Modal (Child Component) --}}
    <livewire:app-video-wizard::modals.character-bible-modal
        wire:model="sceneMemory.characterBible"
        :project-id="$projectId"
        :visual-mode="$content['visualMode'] ?? 'cinematic-realistic'"
        :content-language="$content['language'] ?? 'en'"
        :script-scenes="$script['scenes'] ?? []"
        :story-bible-characters="$storyBible['characters'] ?? []"
        :story-bible-status="$storyBible['status'] ?? ''"
        wire:key="character-bible-modal-{{ $projectId }}"
    />

    {{-- Location Bible Modal (Child Component) --}}
    <livewire:app-video-wizard::modals.location-bible-modal
        wire:model="sceneMemory.locationBible"
        :scenes="$script['scenes'] ?? []"
        :project-id="$projectId"
        :visual-mode="$content['visualMode'] ?? 'cinematic-realistic'"
        :content-language="$content['language'] ?? 'en'"
        :story-bible="$storyBible ?? []"
        wire:key="location-bible-modal-{{ $projectId }}"
    />

    {{-- Scene DNA Overview Modal --}}
    @include('appvideowizard::livewire.modals.scene-dna')

    {{-- Phase 3: Initialize Bible Items for @ Mention System --}}
    <script>
        window.bibleItems = [
            @foreach($sceneMemory['characterBible']['characters'] ?? [] as $char)
            {
                type: 'character',
                icon: '👤',
                name: @js($char['name'] ?? 'Character'),
                tag: '@' + @js(Str::slug($char['name'] ?? 'character')),
                image: @js($char['referenceImage'] ?? null)
            },
            @endforeach
            @foreach($sceneMemory['locationBible']['locations'] ?? [] as $loc)
            {
                type: 'location',
                icon: '📍',
                name: @js($loc['name'] ?? 'Location'),
                tag: '@' + @js(Str::slug($loc['name'] ?? 'location')),
                image: @js($loc['referenceImage'] ?? null)
            },
            @endforeach
        ];
    </script>

    {{-- Edit Prompt Modal --}}
    @include('appvideowizard::livewire.modals.edit-prompt')

    {{-- Multi-Shot Decomposition Modal --}}
    @include('appvideowizard::livewire.modals.multi-shot')

    {{-- Upscale Modal --}}
    @include('appvideowizard::livewire.modals.upscale')

    {{-- AI Edit Modal --}}
    @include('appvideowizard::livewire.modals.ai-edit')

    {{-- Shot Face Correction Modal --}}
    @include('appvideowizard::livewire.modals.shot-face-correction')

    {{-- Universal AI Image Studio Modal --}}
    @include('appvideowizard::livewire.modals.image-studio')

    {{-- Asset History Panel --}}
    @include('appvideowizard::livewire.modals.asset-history-panel')
</div>

<script>
    // Add body class for fullscreen mode
    (function() {
        document.body.classList.add('vw-storyboard-fullscreen-active');

        // Function to aggressively hide all sidebars
        function hideAllSidebars() {
            document.querySelectorAll('.sidebar, .main-sidebar, [class*="sidebar"], aside, nav').forEach(function(el) {
                if (!el.closest('.vw-storyboard-fullscreen')) {
                    el.style.setProperty('display', 'none', 'important');
                    el.style.setProperty('visibility', 'hidden', 'important');
                    el.style.setProperty('width', '0', 'important');
                    el.style.setProperty('opacity', '0', 'important');
                }
            });
        }

        // Hide sidebars immediately and after delays
        hideAllSidebars();
        setTimeout(hideAllSidebars, 100);
        setTimeout(hideAllSidebars, 500);

        // Cleanup when component is removed
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('element.removed', (el, component) => {
                if (el.classList && el.classList.contains('vw-storyboard-fullscreen')) {
                    document.body.classList.remove('vw-storyboard-fullscreen-active');
                    document.querySelectorAll('.sidebar, .main-sidebar, [class*="sidebar"], aside').forEach(function(el) {
                        el.style.cssText = '';
                    });
                }
            });
        }
    })();

    document.addEventListener('livewire:init', () => {
        let pollInterval = null;
        let pendingJobs = 0;
        let isPageVisible = !document.hidden;
        let pollBackoff = 3000; // Start with 3 seconds
        const MAX_POLL_INTERVAL = 10000; // Max 10 seconds between polls
        const MIN_POLL_INTERVAL = 2000; // Min 2 seconds
        let consecutiveEmptyPolls = 0;

        // Visibility API - pause polling when tab is not visible
        document.addEventListener('visibilitychange', () => {
            isPageVisible = !document.hidden;
            if (isPageVisible && pendingJobs > 0) {
                // Resume polling immediately when tab becomes visible
                pollBackoff = MIN_POLL_INTERVAL;
                consecutiveEmptyPolls = 0;
                stopPolling();
                startPolling();
                console.log('Tab visible, resuming polling');
            } else if (!isPageVisible) {
                // Pause polling when tab is hidden (saves resources)
                console.log('Tab hidden, pausing polling');
                stopPolling();
            }
        });

        // Listen for image generation started
        Livewire.on('image-generation-started', (data) => {
            if (data.async) {
                pendingJobs++;
                pollBackoff = MIN_POLL_INTERVAL; // Reset to fast polling
                consecutiveEmptyPolls = 0;
                startPolling();
            }
        });

        // Listen for resume polling (after page refresh with pending jobs)
        Livewire.on('resume-job-polling', (data) => {
            pendingJobs = data.count || 0;
            if (pendingJobs > 0) {
                console.log('Resuming polling for', pendingJobs, 'pending jobs');
                pollBackoff = MIN_POLL_INTERVAL;
                consecutiveEmptyPolls = 0;
                startPolling();
            }
        });

        // Listen for poll status updates
        Livewire.on('poll-status', (data) => {
            const newPendingJobs = data.pendingJobs || 0;
            const completedJobs = data.completedJobs || 0;

            // If jobs completed, reset backoff for faster updates
            if (completedJobs > 0) {
                pollBackoff = MIN_POLL_INTERVAL;
                consecutiveEmptyPolls = 0;
            } else {
                // No jobs completed this poll - increase backoff
                consecutiveEmptyPolls++;
                if (consecutiveEmptyPolls > 3) {
                    pollBackoff = Math.min(pollBackoff * 1.5, MAX_POLL_INTERVAL);
                }
            }

            pendingJobs = newPendingJobs;
            if (pendingJobs === 0) {
                stopPolling();
            } else {
                // Restart with new interval
                stopPolling();
                startPolling();
            }
        });

        // Listen for image ready events
        Livewire.on('image-ready', (data) => {
            console.log('Image ready for scene:', data.sceneIndex);
            // Navigate to the page containing the completed scene
            const sceneIndex = data.sceneIndex;
            if (typeof sceneIndex === 'number') {
                // Let the component know to jump to this scene's page
                Livewire.dispatch('scene-completed', { sceneIndex });
            }
        });

        // Listen for image errors
        Livewire.on('image-error', (data) => {
            console.error('Image generation error:', data.error);
        });

        // Listen for continue-reference-generation event (auto-generation of portraits/references)
        let refGenInterval = null;
        let pendingRefType = null;
        let pendingRefCount = 0;

        let isGenerating = false; // Semaphore to prevent overlapping generations

        Livewire.on('continue-reference-generation', (params) => {
            // Livewire 3 passes params as array or object depending on dispatch format
            // Handle both cases: [{ type, remaining }] or { type, remaining }
            const data = Array.isArray(params) ? params[0] : params;
            console.log('Continue reference generation event received:', data);
            if (data && data.type) {
                pendingRefType = data.type;
                pendingRefCount = data.remaining || 0;
                // Start generating immediately (don't wait for first interval)
                generateNextReference();
                startRefGenPolling();
            } else {
                console.warn('Invalid reference generation data:', params);
            }
        });

        function generateNextReference() {
            if (isGenerating) {
                console.log('Already generating, skipping this tick');
                return;
            }
            if (pendingRefCount <= 0) {
                console.log('No pending references, stopping');
                stopRefGenPolling();
                return;
            }

            isGenerating = true;
            console.log('Generating next ' + pendingRefType + ' reference, ' + pendingRefCount + ' remaining');

            if (pendingRefType === 'character') {
                @this.generateNextPendingCharacterPortrait().then((result) => {
                    console.log('Character portrait result:', result);
                    pendingRefCount = result?.remaining || 0;
                    isGenerating = false;
                    if (pendingRefCount === 0) {
                        stopRefGenPolling();
                    }
                }).catch((err) => {
                    console.error('Error generating character portrait:', err);
                    isGenerating = false;
                    // Don't stop on error - try the next one
                    pendingRefCount = Math.max(0, pendingRefCount - 1);
                    if (pendingRefCount === 0) {
                        stopRefGenPolling();
                    }
                });
            } else if (pendingRefType === 'location') {
                @this.generateNextPendingLocationReference().then((result) => {
                    console.log('Location reference result:', result);
                    pendingRefCount = result?.remaining || 0;
                    isGenerating = false;
                    if (pendingRefCount === 0) {
                        stopRefGenPolling();
                    }
                }).catch((err) => {
                    console.error('Error generating location reference:', err);
                    isGenerating = false;
                    // Don't stop on error - try the next one
                    pendingRefCount = Math.max(0, pendingRefCount - 1);
                    if (pendingRefCount === 0) {
                        stopRefGenPolling();
                    }
                });
            } else {
                isGenerating = false;
                console.warn('Unknown reference type:', pendingRefType);
            }
        }

        function startRefGenPolling() {
            if (refGenInterval) return;

            // Check every 2 seconds if we should generate the next one
            // (but only if not already generating)
            refGenInterval = setInterval(() => {
                if (!isGenerating && pendingRefCount > 0) {
                    generateNextReference();
                }
            }, 2000);

            console.log('Reference generation polling started');
        }

        function stopRefGenPolling() {
            if (refGenInterval) {
                clearInterval(refGenInterval);
                refGenInterval = null;
            }
            pendingRefType = null;
            pendingRefCount = 0;
            isGenerating = false;
            console.log('Reference generation polling stopped');
        }

        function startPolling() {
            if (pollInterval || !isPageVisible) return;

            pollInterval = setInterval(() => {
                if (pendingJobs > 0 && isPageVisible) {
                    Livewire.dispatch('poll-image-jobs');
                } else if (pendingJobs === 0) {
                    stopPolling();
                }
            }, pollBackoff);

            console.log('Polling started with interval:', pollBackoff + 'ms');
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                console.log('Polling stopped');
            }
        }

        // Check for pending jobs on page load (delayed to let Livewire hydrate)
        setTimeout(() => {
            if (isPageVisible) {
                Livewire.dispatch('check-pending-jobs');
            }
        }, 1000);
    });
</script>
@endif {{-- end @else (non-social storyboard) --}}
