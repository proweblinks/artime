# Comprehensive Plan: Assembly Studio & Export Implementation for Laravel Video Wizard

## Executive Summary

This document outlines a detailed implementation plan to upgrade Steps 6 (Assembly Studio) and 7 (Export) of the Laravel Video Creation Wizard to match the functionality of the original `video-creation-wizard.html` implementation.

**Current State:** The Laravel version has basic placeholder UI for both steps
**Target State:** Professional video editor interface with real-time preview, comprehensive controls, and full export functionality

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Phase 1: Video Preview Engine Integration](#phase-1-video-preview-engine-integration)
3. [Phase 2: Assembly Studio - Full-Screen Layout](#phase-2-assembly-studio-full-screen-layout)
4. [Phase 3: Text/Captions Panel](#phase-3-textcaptions-panel)
5. [Phase 4: Audio Panel & Music Library](#phase-4-audio-panel-music-library)
6. [Phase 5: Professional Timeline](#phase-5-professional-timeline)
7. [Phase 6: Export System](#phase-6-export-system)
8. [Phase 7: Server-Side Rendering](#phase-7-server-side-rendering)
9. [File Structure](#file-structure)
10. [Implementation Checklist](#implementation-checklist)

---

## 1. Architecture Overview

### Current Laravel Stack
- **Backend:** Laravel 10+ with Livewire 3
- **Frontend:** Alpine.js, Tailwind CSS
- **Component:** `modules/AppVideoWizard/app/Livewire/VideoWizard.php`
- **Views:** `modules/AppVideoWizard/resources/views/livewire/steps/`

### Key Files to Modify/Create

| File | Purpose |
|------|---------|
| `steps/assembly.blade.php` | Assembly Studio UI (complete rewrite) |
| `steps/export.blade.php` | Export UI (major enhancements) |
| `resources/assets/js/video-preview-engine.js` | Video preview (already exists, needs integration) |
| `resources/assets/js/assembly-studio.js` | New: Assembly Studio controller |
| `resources/assets/js/professional-timeline.js` | New: Timeline component |
| `resources/assets/js/music-browser.js` | New: Music library browser |
| `VideoWizard.php` | Backend methods for assembly/export |

---

## Phase 1: Video Preview Engine Integration

### 1.1 Current State
The `video-preview-engine.js` exists but is **NOT integrated** with the Laravel UI. The assembly step shows a static placeholder.

### 1.2 Tasks

#### 1.2.1 Create Preview Canvas Component
**File:** `steps/partials/_preview-canvas.blade.php`

```blade
{{-- Video Preview Canvas Component --}}
<div x-data="previewController()" x-init="init()" class="preview-container">
    <div class="preview-wrapper">
        <canvas
            x-ref="previewCanvas"
            :width="canvasWidth"
            :height="canvasHeight"
            class="preview-canvas"
        ></canvas>

        {{-- Play overlay --}}
        <div x-show="!isReady" class="preview-overlay">
            <button @click="loadPreview()" class="load-preview-btn">
                <span>▶</span> Load Preview
            </button>
        </div>

        {{-- Loading indicator --}}
        <div x-show="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-progress">
                <div class="progress-bar" :style="'width: ' + loadProgress + '%'"></div>
            </div>
            <span x-text="'Loading ' + loadProgress + '%'"></span>
        </div>
    </div>

    {{-- Transport Controls --}}
    <div class="transport-controls">
        <button @click="seekStart()" :disabled="!isReady">⏮</button>
        <button @click="togglePlay()" :disabled="!isReady" class="play-btn">
            <span x-text="isPlaying ? '❚❚' : '▶'"></span>
        </button>
        <button @click="seekEnd()" :disabled="!isReady">⏭</button>

        <input
            type="range"
            x-model="currentTime"
            :max="totalDuration"
            @input="seek($event.target.value)"
            class="timeline-slider"
        >

        <span class="time-display" x-text="formatTime(currentTime) + ' / ' + formatTime(totalDuration)"></span>
    </div>
</div>
```

#### 1.2.2 Alpine.js Preview Controller
**File:** `resources/assets/js/preview-controller.js`

```javascript
window.previewController = function() {
    return {
        engine: null,
        isReady: false,
        isLoading: false,
        isPlaying: false,
        currentTime: 0,
        totalDuration: 0,
        loadProgress: 0,
        canvasWidth: 1280,
        canvasHeight: 720,

        init() {
            // Get aspect ratio from Livewire
            const aspectRatio = @this.aspectRatio || '16:9';
            this.setAspectRatio(aspectRatio);
        },

        setAspectRatio(ratio) {
            const ratios = {
                '16:9': { width: 1280, height: 720 },
                '9:16': { width: 720, height: 1280 },
                '1:1': { width: 1080, height: 1080 },
                '4:5': { width: 1080, height: 1350 }
            };
            const dims = ratios[ratio] || ratios['16:9'];
            this.canvasWidth = dims.width;
            this.canvasHeight = dims.height;
        },

        async loadPreview() {
            this.isLoading = true;
            this.loadProgress = 0;

            const canvas = this.$refs.previewCanvas;

            this.engine = new VideoPreviewEngine(canvas, {
                width: this.canvasWidth,
                height: this.canvasHeight,
                onTimeUpdate: (time) => {
                    this.currentTime = time;
                    this.$wire.set('preview.currentTime', time);
                },
                onSceneChange: (index) => {
                    this.$wire.set('preview.currentSceneIndex', index);
                },
                onEnded: () => {
                    this.isPlaying = false;
                },
                onLoadProgress: (progress) => {
                    this.loadProgress = Math.round(progress * 100);
                },
                onReady: () => {
                    this.isLoading = false;
                    this.isReady = true;
                    this.totalDuration = this.engine.totalDuration;
                }
            });

            // Get scenes from Livewire
            const scenes = await this.$wire.getPreviewScenes();
            await this.engine.loadScenes(scenes);

            // Apply caption settings
            this.applyCaptionSettings();

            // Apply music if enabled
            await this.applyMusicSettings();
        },

        applyCaptionSettings() {
            const settings = this.$wire.assembly?.captions || {};
            this.engine.captionsEnabled = settings.enabled !== false;
            this.engine.captionStyle = settings.style || 'karaoke';
            this.engine.captionPosition = settings.position || 'bottom';
            this.engine.captionSize = settings.size || 1.0;
            // ... additional settings
        },

        async applyMusicSettings() {
            const music = this.$wire.assembly?.music || {};
            if (music.enabled && music.url) {
                await this.engine.setBackgroundMusic(music.url, music.volume / 100);
            }
        },

        togglePlay() {
            if (this.isPlaying) {
                this.engine.pause();
            } else {
                this.engine.play();
            }
            this.isPlaying = !this.isPlaying;
        },

        seek(time) {
            this.engine.seek(parseFloat(time));
        },

        seekStart() {
            this.seek(0);
        },

        seekEnd() {
            this.seek(this.totalDuration);
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    };
};
```

#### 1.2.3 Backend Method for Scene Data
**Add to:** `VideoWizard.php`

```php
/**
 * Get scenes data formatted for VideoPreviewEngine
 */
public function getPreviewScenes(): array
{
    $scenes = [];
    $scriptScenes = $this->script['scenes'] ?? [];
    $storyboardScenes = $this->storyboard['scenes'] ?? [];
    $animationScenes = $this->animation['scenes'] ?? [];

    foreach ($scriptScenes as $index => $scene) {
        $sceneId = $scene['id'] ?? "scene-{$index}";

        // Find corresponding storyboard and animation data
        $storyboard = collect($storyboardScenes)->firstWhere('sceneId', $sceneId);
        $animation = collect($animationScenes)->firstWhere('sceneId', $sceneId);

        $scenes[] = [
            'id' => $sceneId,
            'index' => $index,
            'duration' => $scene['visualDuration'] ?? $scene['duration'] ?? 8,
            'visualDuration' => $scene['visualDuration'] ?? $scene['duration'] ?? 8,
            'imageUrl' => $storyboard['imageUrl'] ?? null,
            'videoUrl' => $animation['videoUrl'] ?? null,
            'voiceoverUrl' => $animation['voiceoverUrl'] ?? null,
            'voiceoverDuration' => $animation['voiceoverDuration'] ?? null,
            'voiceoverOffset' => $animation['voiceoverOffset'] ?? 0,
            'caption' => $scene['narration'] ?? '',
            'wordTimings' => $animation['wordTimings'] ?? null,
            'transition' => $this->assembly['transitions'][$sceneId] ?? ['type' => 'fade'],
            'kenBurns' => [
                'startScale' => 1.0,
                'endScale' => 1.1,
                'startX' => 0.5,
                'startY' => 0.5,
                'endX' => 0.5 + (rand(-10, 10) / 100),
                'endY' => 0.5 + (rand(-10, 10) / 100),
            ]
        ];
    }

    return $scenes;
}
```

---

## Phase 2: Assembly Studio Full-Screen Layout

### 2.1 Overview
Replace the current basic grid layout with a professional full-screen video editor interface.

### 2.2 Layout Structure

```
┌────────────────────────────────────────────────────────────────────┐
│                           TOP HEADER BAR                            │
│ [🎬 Wizard] │ Project Name │ Stats │ [💾 Save] [← Back] [🚀 Export] │
├──────────────┬───────────────────────┬─────────────────────────────┤
│              │                       │                             │
│   LEFT       │    TABBED CONTROL     │      RIGHT PANEL            │
│   SIDEBAR    │       PANEL           │      (PREVIEW)              │
│              │                       │                             │
│  - Scenes    │  [TEXT][AUDIO][MEDIA] │    ┌─────────────────┐      │
│  - Captions  │  [TRANSITIONS]        │    │                 │      │
│  - Audio     │                       │    │  VIDEO CANVAS   │      │
│  - Trans.    │   Tab content area    │    │                 │      │
│              │                       │    └─────────────────┘      │
│  Quick       │                       │    [ ⏮ ▶ ⏭ ] ─────── 0:00  │
│  Actions     │                       │                             │
├──────────────┴───────────────────────┴─────────────────────────────┤
│                      PROFESSIONAL TIMELINE                          │
│  [▶][⏮][⏭] [✂ Split] [🗑 Delete]     [🔍 Zoom] [Snap: ✓]           │
│  ─────────────────────────────────────────────────────────────────  │
│  Video  │ ████████ │ ████████ │ ████████ │ ████████ │              │
│  Voice  │    ▓▓▓   │    ▓▓▓   │    ▓▓▓   │    ▓▓▓   │              │
│  Music  │ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │              │
│  Captions│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ │              │
└──────────────────────────────────────────────────────────────────────┘
```

### 2.3 New Assembly Step View
**File:** `steps/assembly.blade.php` (complete rewrite)

```blade
{{-- Step 6: Assembly Studio (Professional Video Editor) --}}

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/appvideowizard/css/assembly-studio.css') }}">
@endpush

<div
    x-data="assemblyStudio(@js($this->getAssemblyData()))"
    x-init="init()"
    class="assembly-fullscreen"
    @keydown.window="handleKeydown($event)"
>
    {{-- Top Header Bar --}}
    @include('appvideowizard::livewire.steps.partials._assembly-header')

    {{-- Main Content Area --}}
    <div class="assembly-main">
        {{-- Left Sidebar --}}
        @include('appvideowizard::livewire.steps.partials._assembly-sidebar')

        {{-- Tabbed Control Panel --}}
        @include('appvideowizard::livewire.steps.partials._assembly-tabs')

        {{-- Right Panel (Preview) --}}
        @include('appvideowizard::livewire.steps.partials._assembly-preview')
    </div>

    {{-- Bottom Timeline --}}
    @include('appvideowizard::livewire.steps.partials._assembly-timeline')

    {{-- Modals --}}
    @include('appvideowizard::livewire.steps.partials._music-browser-modal')
    @include('appvideowizard::livewire.steps.partials._export-modal')
</div>

@push('scripts')
<script src="{{ asset('modules/appvideowizard/js/video-preview-engine.js') }}"></script>
<script src="{{ asset('modules/appvideowizard/js/assembly-studio.js') }}"></script>
@endpush
```

### 2.4 Header Component
**File:** `steps/partials/_assembly-header.blade.php`

```blade
<div class="assembly-header">
    {{-- Left: Logo & Project --}}
    <div class="header-left">
        <div class="logo-section">
            <span class="logo-icon">🎬</span>
            <span class="logo-text">Video Creation Wizard</span>
        </div>
        <div class="divider"></div>
        <span class="project-name">{{ $projectName ?? 'Untitled Video' }}</span>
    </div>

    {{-- Center: Stats --}}
    <div class="header-center">
        <span class="stat">{{ count($script['scenes'] ?? []) }} scenes</span>
        <span class="stat-sep">•</span>
        <span class="stat" x-text="formatDuration(totalDuration)">0:00</span>
    </div>

    {{-- Right: Actions --}}
    <div class="header-right">
        <button wire:click="saveAssemblySettings" class="btn-secondary">
            💾 Save
        </button>
        <button wire:click="previousStep" class="btn-secondary">
            ← Back
        </button>
        <button @click="openExportModal()" class="btn-primary">
            🚀 Export
        </button>
    </div>
</div>
```

### 2.5 Assembly Sidebar
**File:** `steps/partials/_assembly-sidebar.blade.php`

```blade
<div class="assembly-sidebar">
    {{-- Project Info Card --}}
    <div class="sidebar-card project-info">
        <div class="card-label">Project</div>
        <div class="card-value">{{ $projectName ?? 'Untitled' }}</div>
    </div>

    {{-- Navigation Buttons --}}
    <button @click="setActiveTab('scenes')" :class="{ 'active': activeTab === 'scenes' }" class="sidebar-nav-btn">
        <span class="nav-icon">📹</span>
        <span class="nav-label">Scenes</span>
        <span class="nav-badge">{{ count($script['scenes'] ?? []) }}</span>
    </button>

    <button @click="setActiveTab('text')" :class="{ 'active': activeTab === 'text' }" class="sidebar-nav-btn">
        <span class="nav-icon">💬</span>
        <span class="nav-label">Captions</span>
        <span x-show="captionsEnabled" class="nav-dot green"></span>
    </button>

    <button @click="setActiveTab('audio')" :class="{ 'active': activeTab === 'audio' }" class="sidebar-nav-btn">
        <span class="nav-icon">🎵</span>
        <span class="nav-label">Audio</span>
        <span x-show="musicEnabled" class="nav-dot green"></span>
    </button>

    <button @click="setActiveTab('transitions')" :class="{ 'active': activeTab === 'transitions' }" class="sidebar-nav-btn">
        <span class="nav-icon">✨</span>
        <span class="nav-label">Transitions</span>
    </button>

    <div class="sidebar-spacer"></div>

    {{-- Quick Actions --}}
    <div class="quick-actions">
        <div class="section-label">Quick Actions</div>
        <button @click="loadPreview()" class="quick-action-btn preview">
            <span>▶</span> Preview
        </button>
        <button @click="openExportModal()" class="quick-action-btn export">
            <span>🚀</span> Export
        </button>
    </div>

    {{-- Duration Display --}}
    <div class="duration-display">
        <div class="duration-label">Total Duration</div>
        <div class="duration-value" x-text="formatDuration(totalDuration)">0:00</div>
    </div>
</div>
```

---

## Phase 3: Text/Captions Panel

### 3.1 Full Caption Controls
**File:** `steps/partials/_tab-text.blade.php`

```blade
<div x-show="activeTab === 'text'" class="tab-content text-tab">
    {{-- Section Header --}}
    <div class="section-header">
        <div class="section-label">CAPTIONS</div>
    </div>

    {{-- Show Captions Toggle --}}
    <div class="setting-row toggle-row">
        <span class="setting-label">Show Captions</span>
        <label class="toggle">
            <input
                type="checkbox"
                x-model="captionsEnabled"
                @change="updateCaptionSetting('enabled', $event.target.checked)"
            >
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div :class="{ 'disabled': !captionsEnabled }" class="caption-settings">
        {{-- Caption Mode (Word/Sentence) --}}
        <div class="setting-group">
            <label class="setting-label">Caption Style</label>
            <div class="button-group dual">
                <button
                    @click="setCaptionMode('word')"
                    :class="{ 'active': captionMode === 'word' }"
                    class="mode-btn"
                >
                    WORD LEVEL
                </button>
                <button
                    @click="setCaptionMode('sentence')"
                    :class="{ 'active': captionMode === 'sentence' }"
                    class="mode-btn"
                >
                    SENTENCE LEVEL
                </button>
            </div>
        </div>

        {{-- Font Selection --}}
        <div class="setting-group">
            <label class="setting-label">Font</label>
            <select x-model="captionFont" @change="updateCaptionSetting('fontFamily', $event.target.value)" class="select-input">
                @foreach(['Montserrat', 'Poppins', 'Roboto', 'Inter', 'Oswald', 'Bebas Neue', 'Anton', 'Playfair Display'] as $font)
                    <option value="{{ $font }}" style="font-family: {{ $font }};">{{ $font }}</option>
                @endforeach
            </select>
        </div>

        {{-- Fill Color --}}
        <div class="setting-row color-row">
            <span class="setting-label">Fill Color</span>
            <div class="color-input-wrapper">
                <input
                    type="color"
                    x-model="captionFillColor"
                    @change="updateCaptionSetting('fillColor', $event.target.value)"
                    class="color-input"
                >
                <span class="color-value" x-text="captionFillColor"></span>
            </div>
        </div>

        {{-- Stroke Color --}}
        <div class="setting-row color-row">
            <span class="setting-label">Stroke Color</span>
            <input
                type="color"
                x-model="captionStrokeColor"
                @change="updateCaptionSetting('strokeColor', $event.target.value)"
                class="color-input small"
            >
        </div>

        {{-- Stroke Width --}}
        <div class="setting-group">
            <div class="setting-row">
                <span class="setting-label">Stroke Width</span>
                <span class="setting-value" x-text="captionStrokeWidth + 'px'"></span>
            </div>
            <input
                type="range"
                min="0" max="5" step="0.5"
                x-model="captionStrokeWidth"
                @change="updateCaptionSetting('strokeWidth', parseFloat($event.target.value))"
                class="range-input"
            >
        </div>

        {{-- Effects Section --}}
        <div class="setting-group">
            <div class="section-label with-icon">
                <span>✨</span> EFFECTS
            </div>
            <div class="effect-grid">
                @php
                    $effects = [
                        ['id' => 'none', 'name' => 'None', 'icon' => '—'],
                        ['id' => 'pop', 'name' => 'Pop', 'icon' => '💥'],
                        ['id' => 'fade', 'name' => 'Fade', 'icon' => '🌫️'],
                        ['id' => 'zoom', 'name' => 'Zoom', 'icon' => '🔍'],
                        ['id' => 'bounce', 'name' => 'Bounce', 'icon' => '⚡'],
                    ];
                @endphp
                @foreach($effects as $effect)
                    <button
                        @click="setCaptionEffect('{{ $effect['id'] }}')"
                        :class="{ 'active': captionEffect === '{{ $effect['id'] }}' }"
                        class="effect-btn"
                    >
                        <div class="effect-icon">{{ $effect['icon'] }}</div>
                        <div class="effect-name">{{ $effect['name'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Text Style Presets --}}
        <div class="setting-group">
            <label class="setting-label">Text Style</label>
            <div class="preset-grid">
                <button
                    @click="setCaptionStyle('karaoke')"
                    :class="{ 'active': captionStyle === 'karaoke' }"
                    class="preset-btn"
                >
                    Karaoke
                </button>
                <button
                    @click="setCaptionStyle('beasty')"
                    :class="{ 'active': captionStyle === 'beasty' }"
                    class="preset-btn"
                >
                    Bold
                </button>
                <button
                    @click="setCaptionStyle('deepdiver')"
                    :class="{ 'active': captionStyle === 'deepdiver' }"
                    class="preset-btn"
                >
                    Minimal
                </button>
            </div>
        </div>

        {{-- Highlight Color (for Karaoke) --}}
        <div class="setting-group highlight-color">
            <div class="setting-row">
                <span class="setting-label">Highlight Color</span>
                <div class="color-input-wrapper">
                    <input
                        type="color"
                        x-model="captionHighlightColor"
                        @change="updateCaptionSetting('highlightColor', $event.target.value)"
                        class="color-input"
                    >
                    <span class="karaoke-badge">KARAOKE</span>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 3.2 Backend Caption Settings
**Add to:** `VideoWizard.php`

```php
/**
 * Caption settings with full customization
 */
public array $assembly = [
    'defaultTransition' => 'fade',
    'music' => [
        'enabled' => false,
        'volume' => 30,
        'trackId' => null,
        'url' => null,
        'fadeIn' => 2,
        'fadeOut' => 3,
    ],
    'captions' => [
        'enabled' => true,
        'mode' => 'word',           // word | sentence
        'style' => 'karaoke',       // karaoke | beasty | deepdiver | hormozi | ali | podcast | minimal
        'position' => 'bottom',     // top | middle | bottom
        'size' => 1.0,
        'fontFamily' => 'Montserrat',
        'fontWeight' => 600,
        'fillColor' => '#FFFFFF',
        'strokeColor' => '#000000',
        'strokeWidth' => 2,
        'effect' => 'none',         // none | pop | fade | zoom | bounce
        'highlightColor' => '#FBBF24',
    ],
    'audioMix' => [
        'voiceVolume' => 100,
        'musicVolume' => 30,
        'sfxVolume' => 50,
        'ducking' => true,
    ],
    'transitions' => [],
    'sceneOrder' => [],
];

/**
 * Update individual caption setting
 */
public function updateCaptionSetting(string $key, mixed $value): void
{
    $this->assembly['captions'][$key] = $value;
    $this->saveToDatabase();

    // Dispatch event to update preview
    $this->dispatch('caption-setting-updated', [
        'key' => $key,
        'value' => $value
    ]);
}
```

---

## Phase 4: Audio Panel & Music Library

### 4.1 Audio Tab with Smart Audio AI
**File:** `steps/partials/_tab-audio.blade.php`

```blade
<div x-show="activeTab === 'audio'" class="tab-content audio-tab">
    {{-- Smart Audio AI Panel --}}
    <div class="smart-audio-panel">
        <div class="panel-header">
            <div class="header-left">
                <span class="ai-icon">🤖</span>
                <span class="panel-title">Smart Audio</span>
                <span class="ai-badge">AI</span>
            </div>
            <span x-show="audioProfileReady" class="analyzed-badge">✓ Analyzed</span>
        </div>

        <p class="panel-description">
            AI analyzes your content genre, pacing, and mood to recommend perfect background music,
            sound effects, and mix settings.
        </p>

        {{-- Loading State --}}
        <div x-show="audioAnalyzing" class="analyzing-state">
            <div class="spinner"></div>
            <span>Analyzing content...</span>
        </div>

        {{-- Recommendations (when ready) --}}
        <div x-show="audioProfileReady && !audioAnalyzing" class="recommendations">
            <div class="recommendation-card">
                <div x-show="audioProfile.music?.topPick" class="top-pick">
                    <span class="music-icon">🎵</span>
                    <div class="pick-info">
                        <div class="pick-name" x-text="audioProfile.music?.topPick?.name"></div>
                        <div class="pick-meta">
                            <span x-text="audioProfile.music?.topPick?.mood"></span> •
                            <span x-text="audioProfile.music?.topPick?.bpm + ' BPM'"></span> •
                            <span x-text="audioProfile.music?.topPick?.matchScore + '% match'"></span>
                        </div>
                    </div>
                </div>

                <div class="mix-settings">
                    <span>● Voice: <span x-text="audioProfile.mix?.voiceVolume + '%'"></span></span>
                    <span>● Music: <span x-text="audioProfile.mix?.musicVolume + '%'"></span></span>
                    <span>● SFX: <span x-text="audioProfile.mix?.sfxVolume + '%'"></span></span>
                </div>
            </div>

            <div class="action-buttons">
                <button @click="applySmartAudioRecommendations()" class="btn-primary">
                    ✨ Apply All Recommendations
                </button>
                <button @click="analyzeAudio()" class="btn-icon" title="Re-analyze">
                    🔄
                </button>
            </div>
        </div>

        {{-- Analyze Button (when not analyzed) --}}
        <button
            x-show="!audioProfileReady && !audioAnalyzing"
            @click="analyzeAudio()"
            class="btn-analyze"
        >
            <span>✨</span>
            Analyze & Get AI Recommendations
        </button>
    </div>

    {{-- Audio Mix Visualization --}}
    <div class="audio-mix-viz">
        <div class="viz-label">Audio Mix</div>
        <div class="viz-bars">
            <div class="bar" style="height: 70%;"></div>
            <div class="bar" style="height: 85%;"></div>
            <div class="bar" style="height: 60%;"></div>
            <div class="bar alt" style="height: 90%;"></div>
            <div class="bar alt" style="height: 75%;"></div>
            <div class="bar" style="height: 65%;"></div>
            <div class="bar alt" style="height: 80%;"></div>
            <div class="bar" style="height: 55%;"></div>
        </div>
        <div class="viz-legend">
            <span class="legend-voice">● Voice <span x-text="voiceVolume + '%'"></span></span>
            <span class="legend-music">● Music <span x-text="musicEnabled ? musicVolume + '%' : 'Off'"></span></span>
        </div>
    </div>

    {{-- Voiceover Settings --}}
    <div class="setting-section">
        <div class="section-header">
            <span>🎙️</span> Voiceover
        </div>
        <div class="setting-row">
            <span class="setting-label">Volume</span>
            <span class="setting-value" x-text="voiceVolume + '%'"></span>
        </div>
        <input
            type="range"
            min="0" max="100"
            x-model="voiceVolume"
            @change="updateAudioMix('voiceVolume', $event.target.value)"
            class="range-input voice"
        >
    </div>

    {{-- Background Music Section --}}
    <div class="setting-section">
        <div class="section-header">
            <span>🎵</span> Background Music
        </div>

        {{-- Enable Toggle --}}
        <div class="setting-row toggle-row">
            <span class="setting-label">Enable Music</span>
            <label class="toggle">
                <input
                    type="checkbox"
                    x-model="musicEnabled"
                    @change="toggleMusic($event.target.checked)"
                >
                <span class="toggle-slider"></span>
            </label>
        </div>

        <div :class="{ 'disabled': !musicEnabled }" class="music-controls">
            {{-- Track Selection --}}
            <div class="setting-group">
                <div class="setting-row">
                    <label class="setting-label">Select Track</label>
                    <button @click="openMusicBrowser()" class="browse-btn">
                        🔍 Browse Library
                    </button>
                </div>
                <select
                    x-model="selectedTrackId"
                    @change="selectMusicTrack($event.target.value)"
                    class="select-input"
                >
                    <option value="">-- Select a track --</option>
                    <template x-for="track in musicLibrary" :key="track.id">
                        <option :value="track.id" x-text="track.name"></option>
                    </template>
                </select>
            </div>

            {{-- Volume --}}
            <div class="setting-group">
                <div class="setting-row">
                    <span class="setting-label">Volume</span>
                    <span class="setting-value" x-text="musicVolume + '%'"></span>
                </div>
                <input
                    type="range"
                    min="0" max="100" step="5"
                    x-model="musicVolume"
                    @change="updateMusicVolume($event.target.value)"
                    class="range-input music"
                >
            </div>

            {{-- Audio Ducking --}}
            <div class="setting-row toggle-row">
                <span class="setting-label">Auto-duck during voice</span>
                <label class="toggle small">
                    <input
                        type="checkbox"
                        x-model="audioDucking"
                        @change="toggleAudioDucking($event.target.checked)"
                    >
                    <span class="toggle-slider"></span>
                </label>
            </div>

            {{-- Fade Settings --}}
            <div class="fade-settings">
                <div class="fade-group">
                    <label>Fade In</label>
                    <select x-model="musicFadeIn" @change="updateMusicFade('fadeIn', $event.target.value)">
                        <option value="0">None</option>
                        <option value="1">1s</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                    </select>
                </div>
                <div class="fade-group">
                    <label>Fade Out</label>
                    <select x-model="musicFadeOut" @change="updateMusicFade('fadeOut', $event.target.value)">
                        <option value="0">None</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                        <option value="5">5s</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 4.2 Music Browser Modal
**File:** `steps/partials/_music-browser-modal.blade.php`

```blade
<div
    x-show="showMusicBrowser"
    x-cloak
    class="modal-overlay"
    @click.self="closeMusicBrowser()"
>
    <div class="music-browser-modal">
        <div class="modal-header">
            <div class="header-title">
                <span>🎵</span>
                <span>Music Library</span>
            </div>
            <button @click="closeMusicBrowser()" class="close-btn">×</button>
        </div>

        <div class="modal-content">
            {{-- Search & Filters --}}
            <div class="browser-filters">
                <input
                    type="search"
                    x-model="musicSearch"
                    placeholder="Search tracks..."
                    class="search-input"
                >

                <div class="filter-group">
                    <label>Mood</label>
                    <select x-model="musicMoodFilter">
                        <option value="">All Moods</option>
                        <option value="upbeat">Upbeat</option>
                        <option value="chill">Chill</option>
                        <option value="dramatic">Dramatic</option>
                        <option value="inspirational">Inspirational</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Genre</label>
                    <select x-model="musicGenreFilter">
                        <option value="">All Genres</option>
                        <option value="electronic">Electronic</option>
                        <option value="cinematic">Cinematic</option>
                        <option value="acoustic">Acoustic</option>
                        <option value="hip-hop">Hip-Hop</option>
                        <option value="ambient">Ambient</option>
                    </select>
                </div>
            </div>

            {{-- Track List --}}
            <div class="track-list">
                <template x-for="track in filteredMusicLibrary" :key="track.id">
                    <div
                        class="track-item"
                        :class="{ 'selected': selectedTrackId === track.id, 'playing': previewingTrackId === track.id }"
                        @click="selectTrack(track)"
                    >
                        <div class="track-preview-btn" @click.stop="toggleTrackPreview(track)">
                            <span x-text="previewingTrackId === track.id ? '⏸' : '▶'"></span>
                        </div>
                        <div class="track-info">
                            <div class="track-name" x-text="track.name"></div>
                            <div class="track-meta">
                                <span x-text="track.mood"></span> •
                                <span x-text="track.bpm + ' BPM'"></span> •
                                <span x-text="formatDuration(track.duration)"></span>
                            </div>
                        </div>
                        <div class="track-waveform">
                            {{-- Placeholder waveform --}}
                            <div class="waveform-placeholder"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="modal-footer">
            <button @click="closeMusicBrowser()" class="btn-secondary">Cancel</button>
            <button
                @click="confirmMusicSelection()"
                :disabled="!selectedTrackId"
                class="btn-primary"
            >
                Use Selected Track
            </button>
        </div>
    </div>
</div>
```

### 4.3 Music Library Backend
**Add to:** `VideoWizard.php`

```php
/**
 * Get music library from config or external source
 */
public function getMusicLibrary(): array
{
    // Can load from config, database, or external API
    return config('appvideowizard.music_library', [
        [
            'id' => 'upbeat-corporate-1',
            'name' => 'Corporate Uplift',
            'mood' => 'upbeat',
            'genre' => 'corporate',
            'bpm' => 120,
            'duration' => 180,
            'url' => '/audio/music/corporate-uplift.mp3',
        ],
        [
            'id' => 'cinematic-epic-1',
            'name' => 'Epic Cinematic',
            'mood' => 'dramatic',
            'genre' => 'cinematic',
            'bpm' => 90,
            'duration' => 240,
            'url' => '/audio/music/epic-cinematic.mp3',
        ],
        // ... more tracks
    ]);
}

/**
 * Analyze content for audio recommendations
 */
public function analyzeContentForAudio(): array
{
    // Analyze script content, genre, mood
    $genre = $this->concept['genre'] ?? 'general';
    $mood = $this->concept['suggestedMood'] ?? 'neutral';
    $pacing = $this->script['timing']['pacing'] ?? 'moderate';

    // Generate recommendations based on analysis
    return [
        'music' => [
            'topPick' => [
                'name' => 'Recommended Track',
                'mood' => $mood,
                'bpm' => $this->calculateIdealBPM($pacing),
                'matchScore' => 92,
            ],
        ],
        'mix' => [
            'voiceVolume' => 100,
            'musicVolume' => $mood === 'dramatic' ? 40 : 25,
            'sfxVolume' => 30,
        ],
        'sfx' => [
            'style' => $genre === 'tech' ? 'electronic' : 'subtle',
        ],
        'ambience' => [
            'primaryAmbience' => $this->suggestAmbience($genre),
        ],
    ];
}
```

---

## Phase 5: Professional Timeline

### 5.1 Timeline Component
**File:** `steps/partials/_assembly-timeline.blade.php`

```blade
<div class="professional-timeline" x-data="timelineController()">
    {{-- Timeline Toolbar --}}
    <div class="timeline-toolbar">
        {{-- Left: Transport & Edit Tools --}}
        <div class="toolbar-left">
            {{-- Transport Controls --}}
            <div class="transport-group">
                <button @click="seekStart()" title="Go to Start">⏮</button>
                <button @click="togglePlayback()" :class="{ 'playing': isPlaying }" class="play-btn">
                    <span x-text="isPlaying ? '⏸' : '▶'"></span>
                </button>
                <button @click="seekEnd()" title="Go to End">⏭</button>
            </div>

            <div class="divider"></div>

            {{-- Edit Tools --}}
            <div class="edit-tools">
                <button @click="undo()" :disabled="!canUndo" title="Undo (Ctrl+Z)">↶</button>
                <button @click="redo()" :disabled="!canRedo" title="Redo (Ctrl+Y)">↷</button>
                <div class="divider small"></div>
                <button @click="splitAtPlayhead()" :disabled="!isReady" title="Split at Playhead (S)">
                    <span>✂</span> Split
                </button>
                <button @click="deleteSelected()" :disabled="!selectedClipId" class="delete-btn" title="Delete Selected (Del)">
                    <span>🗑</span> Delete
                </button>
            </div>
        </div>

        {{-- Center: Timeline Title & Time --}}
        <div class="toolbar-center">
            <div class="timeline-title">
                <span>🎬</span>
                <span>Timeline</span>
            </div>
            <div class="time-display">
                <span x-text="formatTimecode(currentTime)">00:00:00</span>
                <span class="separator">/</span>
                <span x-text="formatTimecode(totalDuration)">00:00:00</span>
            </div>
        </div>

        {{-- Right: Zoom & View Controls --}}
        <div class="toolbar-right">
            <div class="zoom-controls">
                <button @click="zoomOut()" title="Zoom Out">−</button>
                <input
                    type="range"
                    min="20" max="200"
                    x-model="zoom"
                    @input="updateZoom($event.target.value)"
                    class="zoom-slider"
                >
                <button @click="zoomIn()" title="Zoom In">+</button>
                <span class="zoom-value" x-text="zoom + '%'"></span>
            </div>

            <div class="divider"></div>

            <label class="snap-toggle">
                <input type="checkbox" x-model="snapToGrid">
                <span>Snap</span>
            </label>
        </div>
    </div>

    {{-- Timeline Content --}}
    <div class="timeline-content" @scroll="handleScroll($event)">
        {{-- Time Ruler --}}
        <div class="time-ruler" :style="'width: ' + timelineWidth + 'px'">
            <template x-for="marker in timeMarkers" :key="marker.time">
                <div
                    class="time-marker"
                    :class="{ 'major': marker.major }"
                    :style="'left: ' + (marker.time * zoom) + 'px'"
                >
                    <span x-text="marker.label"></span>
                </div>
            </template>
        </div>

        {{-- Playhead --}}
        <div
            class="playhead"
            :style="'left: ' + (currentTime * zoom) + 'px'"
        >
            <div class="playhead-head"></div>
            <div class="playhead-line"></div>
        </div>

        {{-- Track Lanes --}}
        <div class="track-lanes">
            {{-- Video Track --}}
            <div class="track-lane video-track">
                <div class="track-label">
                    <span>📹</span> Video
                </div>
                <div class="track-clips" :style="'width: ' + timelineWidth + 'px'">
                    <template x-for="scene in scenes" :key="scene.id">
                        <div
                            class="clip video-clip"
                            :class="{
                                'selected': selectedClipId === scene.id,
                                'has-video': scene.videoUrl,
                                'image-only': !scene.videoUrl && scene.imageUrl
                            }"
                            :style="'left: ' + (scene.startTime * zoom) + 'px; width: ' + (scene.duration * zoom) + 'px'"
                            @click="selectClip(scene.id)"
                            @dblclick="jumpToScene(scene.index)"
                        >
                            <div class="clip-thumbnail">
                                <img :src="scene.imageUrl || scene.videoThumb" alt="">
                            </div>
                            <div class="clip-info">
                                <span class="clip-name" x-text="'Scene ' + (scene.index + 1)"></span>
                                <span class="clip-duration" x-text="formatDuration(scene.duration)"></span>
                            </div>
                            <div class="clip-handles">
                                <div class="handle left" @mousedown.stop="startTrim(scene.id, 'left', $event)"></div>
                                <div class="handle right" @mousedown.stop="startTrim(scene.id, 'right', $event)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Voice Track --}}
            <div class="track-lane voice-track">
                <div class="track-label">
                    <span>🎙️</span> Voice
                </div>
                <div class="track-clips" :style="'width: ' + timelineWidth + 'px'">
                    <template x-for="scene in scenesWithVoice" :key="scene.id + '-voice'">
                        <div
                            class="clip voice-clip"
                            :style="'left: ' + ((scene.startTime + (scene.voiceoverOffset || 0)) * zoom) + 'px; width: ' + ((scene.voiceoverDuration || scene.duration * 0.8) * zoom) + 'px'"
                        >
                            <div class="waveform-viz"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Music Track --}}
            <div class="track-lane music-track">
                <div class="track-label">
                    <span>🎵</span> Music
                </div>
                <div class="track-clips" :style="'width: ' + timelineWidth + 'px'">
                    <div
                        x-show="musicEnabled && musicUrl"
                        class="clip music-clip"
                        :style="'width: ' + (totalDuration * zoom) + 'px'"
                    >
                        <div class="music-info" x-text="selectedTrackName || 'Background Music'"></div>
                    </div>
                </div>
            </div>

            {{-- Captions Track --}}
            <div class="track-lane captions-track">
                <div class="track-label">
                    <span>💬</span> Captions
                </div>
                <div class="track-clips" :style="'width: ' + timelineWidth + 'px'">
                    <template x-for="scene in scenesWithCaptions" :key="scene.id + '-caption'">
                        <div
                            class="clip caption-clip"
                            :style="'left: ' + (scene.startTime * zoom) + 'px; width: ' + (scene.duration * zoom) + 'px'"
                        >
                            <span class="caption-preview" x-text="truncate(scene.caption, 30)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 5.2 Timeline JavaScript Controller
**File:** `resources/assets/js/timeline-controller.js`

```javascript
window.timelineController = function() {
    return {
        // State
        scenes: [],
        currentTime: 0,
        totalDuration: 0,
        zoom: 50, // pixels per second
        scrollLeft: 0,
        selectedClipId: null,
        isPlaying: false,
        isReady: false,
        snapToGrid: true,

        // Undo/Redo
        history: [],
        historyIndex: -1,
        canUndo: false,
        canRedo: false,

        // Music
        musicEnabled: false,
        musicUrl: null,
        selectedTrackName: null,

        // Computed
        get timelineWidth() {
            return this.totalDuration * this.zoom;
        },

        get timeMarkers() {
            const markers = [];
            const interval = this.zoom < 30 ? 10 : (this.zoom < 60 ? 5 : 1);

            for (let t = 0; t <= this.totalDuration; t += interval) {
                markers.push({
                    time: t,
                    label: this.formatTimecode(t),
                    major: t % (interval * 5) === 0
                });
            }

            return markers;
        },

        get scenesWithVoice() {
            return this.scenes.filter(s => s.voiceoverUrl);
        },

        get scenesWithCaptions() {
            return this.scenes.filter(s => s.caption);
        },

        // Methods
        init() {
            // Load scenes from Livewire
            this.loadScenes();

            // Listen for preview engine updates
            this.$watch('$wire.preview', (preview) => {
                this.currentTime = preview?.currentTime || 0;
                this.isPlaying = preview?.isPlaying || false;
                this.isReady = preview?.isReady || false;
            });
        },

        async loadScenes() {
            const scenesData = await this.$wire.getPreviewScenes();
            this.scenes = scenesData.map((s, i) => ({
                ...s,
                startTime: this.calculateStartTime(scenesData, i)
            }));
            this.totalDuration = this.scenes.reduce((sum, s) => sum + s.duration, 0);
        },

        calculateStartTime(scenes, index) {
            return scenes.slice(0, index).reduce((sum, s) => sum + (s.duration || 8), 0);
        },

        selectClip(clipId) {
            this.selectedClipId = clipId;
            this.$wire.set('timeline.selectedClipId', clipId);
        },

        jumpToScene(index) {
            const scene = this.scenes[index];
            if (scene) {
                this.$dispatch('seek-preview', { time: scene.startTime });
            }
        },

        togglePlayback() {
            this.$dispatch('toggle-preview-playback');
        },

        seekStart() {
            this.$dispatch('seek-preview', { time: 0 });
        },

        seekEnd() {
            this.$dispatch('seek-preview', { time: this.totalDuration });
        },

        zoomIn() {
            this.zoom = Math.min(200, this.zoom + 10);
        },

        zoomOut() {
            this.zoom = Math.max(20, this.zoom - 10);
        },

        updateZoom(value) {
            this.zoom = parseInt(value);
        },

        splitAtPlayhead() {
            // Find scene at current time
            const scene = this.scenes.find(s =>
                this.currentTime >= s.startTime &&
                this.currentTime < s.startTime + s.duration
            );

            if (scene) {
                this.$wire.splitSceneAtTime(scene.id, this.currentTime - scene.startTime);
            }
        },

        deleteSelected() {
            if (this.selectedClipId) {
                this.$wire.deleteScene(this.selectedClipId);
                this.selectedClipId = null;
            }
        },

        // Drag handling for clip trimming
        startTrim(clipId, handle, event) {
            // ... drag implementation
        },

        // Utility
        formatTimecode(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            const f = Math.floor((seconds % 1) * 30); // 30fps frames

            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m}:${s.toString().padStart(2, '0')}`;
        },

        formatDuration(seconds) {
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return `${m}:${s.toString().padStart(2, '0')}`;
        },

        truncate(text, length) {
            return text?.length > length ? text.substring(0, length) + '...' : text;
        }
    };
};
```

---

## Phase 6: Export System

### 6.1 Export Modal
**File:** `steps/partials/_export-modal.blade.php`

```blade
<div
    x-show="showExportModal"
    x-cloak
    class="modal-overlay export-modal-overlay"
    @click.self="closeExportModal()"
>
    <div class="export-modal">
        {{-- Modal Header --}}
        <div class="modal-header">
            <div class="header-title">
                <span>🚀</span>
                <span>Export Video</span>
            </div>
            <button
                x-show="!isExporting"
                @click="closeExportModal()"
                class="close-btn"
            >×</button>
        </div>

        {{-- Modal Content --}}
        <div class="modal-content">
            {{-- Config State --}}
            <div x-show="!isExporting && !exportComplete && !exportFailed">
                {{-- Video Summary --}}
                <div class="export-section summary-section">
                    <h4 class="section-title">📋 Video Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-stat">
                            <div class="stat-label">Platform</div>
                            <div class="stat-value">
                                <span x-text="platformIcon"></span>
                                <span x-text="platformName"></span>
                            </div>
                        </div>
                        <div class="summary-stat">
                            <div class="stat-label">Duration</div>
                            <div class="stat-value" x-text="formatDuration(totalDuration)"></div>
                        </div>
                        <div class="summary-stat">
                            <div class="stat-label">Images</div>
                            <div class="stat-value" :class="imageStatusClass" x-text="imageCount + '/' + sceneCount"></div>
                        </div>
                        <div class="summary-stat">
                            <div class="stat-label">Animated</div>
                            <div class="stat-value" :class="animatedStatusClass" x-text="animatedCount + '/' + sceneCount"></div>
                        </div>
                    </div>
                </div>

                {{-- Ken Burns Info --}}
                <div x-show="animatedCount < sceneCount && canExport" class="info-banner cyan">
                    <span x-text="animatedCount === 0 ? 'Using Ken Burns effect on images (animation optional)' : (sceneCount - animatedCount) + ' scene(s) will use Ken Burns effect'"></span>
                </div>

                {{-- Platform Selection --}}
                <div class="export-section">
                    <div class="section-header">
                        <h4 class="section-title">🎯 Target Platform</h4>
                        <span class="smart-badge">SMART</span>
                    </div>

                    <div class="platform-grid">
                        @php
                            $exportPlatforms = [
                                ['id' => 'youtube-standard', 'icon' => '📺', 'name' => 'YouTube', 'aspect' => '16:9'],
                                ['id' => 'youtube-shorts', 'icon' => '🎬', 'name' => 'YT Shorts', 'aspect' => '9:16'],
                                ['id' => 'tiktok-standard', 'icon' => '🎵', 'name' => 'TikTok', 'aspect' => '9:16'],
                                ['id' => 'instagram-reels', 'icon' => '📸', 'name' => 'Reels', 'aspect' => '9:16'],
                                ['id' => 'linkedin-video', 'icon' => '💼', 'name' => 'LinkedIn', 'aspect' => '16:9'],
                                ['id' => 'twitter-video', 'icon' => '🐦', 'name' => 'Twitter/X', 'aspect' => '16:9'],
                            ];
                        @endphp
                        @foreach($exportPlatforms as $ep)
                            <button
                                @click="setExportPlatform('{{ $ep['id'] }}')"
                                :class="{ 'active': exportPlatform === '{{ $ep['id'] }}' }"
                                class="platform-btn"
                            >
                                <div class="platform-icon">{{ $ep['icon'] }}</div>
                                <div class="platform-name">{{ $ep['name'] }}</div>
                                <div class="platform-aspect">{{ $ep['aspect'] }}</div>
                            </button>
                        @endforeach
                    </div>

                    {{-- Platform Tips --}}
                    <div class="platform-tips">
                        <div class="tip-row">
                            <span class="tip-label">Hook:</span>
                            <span class="tip-value" x-text="platformTips[exportPlatform]?.hook || '5s'"></span>
                        </div>
                        <div class="tip-row">
                            <span class="tip-label">Optimal:</span>
                            <span class="tip-value green" x-text="platformTips[exportPlatform]?.optimal || '1-5 min'"></span>
                        </div>
                        <div class="tip-info" x-text="'💡 ' + (platformTips[exportPlatform]?.tip || '')"></div>
                    </div>
                </div>

                {{-- Duration Warning --}}
                <div x-show="durationWarning" class="warning-banner">
                    <span>⚠</span>
                    <span x-text="durationWarning"></span>
                </div>

                {{-- Quality Selection --}}
                <div class="export-section">
                    <h4 class="section-title">⚙️ Export Quality</h4>
                    <div class="quality-grid">
                        @php
                            $qualities = [
                                ['id' => '720p', 'icon' => '📺', 'name' => '720p', 'desc' => 'HD'],
                                ['id' => '1080p', 'icon' => '🎬', 'name' => '1080p', 'desc' => 'Full HD', 'recommended' => true],
                                ['id' => '4k', 'icon' => '🌟', 'name' => '4K', 'desc' => 'Ultra HD'],
                            ];
                        @endphp
                        @foreach($qualities as $q)
                            <button
                                @click="setExportQuality('{{ $q['id'] }}')"
                                :class="{ 'active': exportQuality === '{{ $q['id'] }}' }"
                                class="quality-btn {{ $q['recommended'] ?? false ? 'recommended' : '' }}"
                            >
                                <div class="quality-icon">{{ $q['icon'] }}</div>
                                <div class="quality-name">{{ $q['name'] }}</div>
                                @if($q['recommended'] ?? false)
                                    <div class="recommended-badge">Recommended</div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Cannot Export Warning --}}
                <div x-show="!canExport" class="error-banner">
                    Generate images in Storyboard step first
                </div>

                {{-- Export Button --}}
                <button
                    @click="startExport()"
                    :disabled="!canExport"
                    class="export-btn"
                >
                    <span>🚀</span>
                    <span>Start Export</span>
                </button>
            </div>

            {{-- Exporting State --}}
            <div x-show="isExporting" class="export-progress-state">
                <div class="progress-icon animate-pulse">🎬</div>
                <h3>Rendering Your Video</h3>
                <p class="progress-stage" x-text="exportStage"></p>

                {{-- Scene Progress Indicators --}}
                <div x-show="scenesTotal > 0" class="scene-progress">
                    <div class="scene-dots">
                        <template x-for="i in scenesTotal" :key="i">
                            <div
                                class="scene-dot"
                                :class="{
                                    'complete': sceneStatuses[i-1]?.status === 'complete',
                                    'failed': sceneStatuses[i-1]?.status === 'failed',
                                    'rendering': sceneStatuses[i-1]?.status === 'rendering'
                                }"
                            >
                                <span x-text="sceneStatuses[i-1]?.status === 'complete' ? '✓' : (sceneStatuses[i-1]?.status === 'failed' ? '✗' : i)"></span>
                            </div>
                        </template>
                    </div>
                    <p class="scenes-count" x-text="scenesCompleted + ' of ' + scenesTotal + ' scenes complete'"></p>
                </div>

                {{-- Progress Bar --}}
                <div class="progress-bar-container">
                    <div class="progress-bar" :style="'width: ' + exportProgress + '%'"></div>
                </div>
                <div class="progress-info">
                    <span>Progress</span>
                    <span x-text="exportProgress + '%'"></span>
                </div>

                {{-- Cancel Button --}}
                <button @click="cancelExport()" class="cancel-btn">
                    Cancel Export
                </button>
                <p class="warning-text">Please don't close this page while exporting</p>
            </div>

            {{-- Complete State --}}
            <div x-show="exportComplete" class="export-complete-state">
                <div class="success-icon">🎉</div>
                <h3>Video Export Complete!</h3>
                <p>Your video is ready to download and share</p>

                {{-- Video Preview --}}
                <div x-show="exportedVideoUrl" class="video-preview">
                    <video :src="exportedVideoUrl" controls></video>
                </div>

                {{-- Stats --}}
                <div class="export-stats">
                    <span>📹 <span x-text="sceneCount"></span> scenes</span>
                    <span>⏱️ <span x-text="formatDuration(totalDuration)"></span></span>
                    <span>🎬 MP4 format</span>
                </div>

                {{-- Action Buttons --}}
                <div class="action-buttons">
                    <a :href="exportedVideoUrl" download class="btn-primary download-btn">
                        <span>⬇️</span> Download Video
                    </a>
                    <button @click="exportAgain()" class="btn-secondary">
                        <span>🔄</span> Export Again
                    </button>
                    <button @click="closeExportModal()" class="btn-tertiary">
                        Close
                    </button>
                </div>

                {{-- Upload Prompt --}}
                <div class="upload-prompt">
                    <div class="prompt-icon">✓</div>
                    <div class="prompt-text">
                        <strong>Ready to upload!</strong>
                        <p>Download your video and upload it directly to YouTube, TikTok, Instagram, or any other platform.</p>
                    </div>
                </div>
            </div>

            {{-- Failed State --}}
            <div x-show="exportFailed" class="export-failed-state">
                <div class="error-icon">✗</div>
                <h3>Export Failed</h3>
                <p>Something went wrong during export</p>

                <div class="error-message" x-text="exportError"></div>

                <div class="action-buttons">
                    <button @click="retryExport()" class="btn-primary">
                        <span>🔄</span> Try Again
                    </button>
                    <button @click="closeExportModal()" class="btn-secondary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 6.2 Export Controller
**Add to:** `resources/assets/js/assembly-studio.js`

```javascript
// Export-related state and methods for assemblyStudio Alpine component
{
    // Export State
    showExportModal: false,
    isExporting: false,
    exportComplete: false,
    exportFailed: false,
    exportProgress: 0,
    exportStage: '',
    exportError: '',
    exportedVideoUrl: null,

    exportPlatform: 'youtube-standard',
    exportQuality: '1080p',

    scenesTotal: 0,
    scenesCompleted: 0,
    sceneStatuses: [],

    canExport: false,
    imageCount: 0,
    animatedCount: 0,
    sceneCount: 0,

    platformTips: {
        'youtube-standard': { hook: '30s', optimal: '8-15 min', tip: 'Use chapters and end screens' },
        'youtube-shorts': { hook: '3s', optimal: '30-58s', tip: 'Loop-friendly content performs best' },
        'tiktok-standard': { hook: '2s', optimal: '21-34s', tip: 'Trending sounds boost reach 40%' },
        'instagram-reels': { hook: '2s', optimal: '15-30s', tip: 'Saves and shares boost algorithm' },
        'linkedin-video': { hook: '5s', optimal: '30-120s', tip: 'Native captions are essential' },
        'twitter-video': { hook: '3s', optimal: '15-60s', tip: 'Assume muted autoplay' }
    },

    get durationWarning() {
        const duration = this.totalDuration;
        if (this.exportPlatform === 'youtube-shorts' && duration > 60) {
            return 'Video exceeds 60s Shorts limit. Will be split or trimmed.';
        }
        if (this.exportPlatform === 'tiktok-standard' && duration > 60) {
            return 'Consider TikTok Extended for videos over 60s.';
        }
        if (this.exportPlatform === 'instagram-reels' && duration > 90) {
            return 'Exceeds 90s Reels limit. Content will be trimmed.';
        }
        return null;
    },

    // Methods
    openExportModal() {
        this.showExportModal = true;
        this.loadExportStats();
    },

    closeExportModal() {
        if (!this.isExporting) {
            this.showExportModal = false;
        }
    },

    async loadExportStats() {
        const stats = await this.$wire.getExportStats();
        this.sceneCount = stats.sceneCount;
        this.imageCount = stats.imageCount;
        this.animatedCount = stats.animatedCount;
        this.canExport = stats.canExport;
    },

    setExportPlatform(platform) {
        this.exportPlatform = platform;
    },

    setExportQuality(quality) {
        this.exportQuality = quality;
    },

    async startExport() {
        this.isExporting = true;
        this.exportProgress = 0;
        this.exportStage = 'Preparing assets...';
        this.scenesCompleted = 0;
        this.sceneStatuses = [];

        try {
            // Start export job on server
            const result = await this.$wire.startExport({
                platform: this.exportPlatform,
                quality: this.exportQuality
            });

            if (result.jobId) {
                // Poll for progress
                this.pollExportProgress(result.jobId);
            } else if (result.error) {
                throw new Error(result.error);
            }
        } catch (error) {
            this.handleExportError(error.message);
        }
    },

    async pollExportProgress(jobId) {
        const checkProgress = async () => {
            if (!this.isExporting) return;

            const status = await this.$wire.getExportProgress(jobId);

            this.exportProgress = status.progress;
            this.exportStage = status.stage;
            this.scenesTotal = status.scenesTotal || this.sceneCount;
            this.scenesCompleted = status.scenesCompleted || 0;
            this.sceneStatuses = status.sceneStatuses || [];

            if (status.status === 'completed') {
                this.handleExportComplete(status.outputUrl);
            } else if (status.status === 'failed') {
                this.handleExportError(status.error);
            } else {
                // Continue polling
                setTimeout(checkProgress, 2000);
            }
        };

        checkProgress();
    },

    handleExportComplete(videoUrl) {
        this.isExporting = false;
        this.exportComplete = true;
        this.exportedVideoUrl = videoUrl;
    },

    handleExportError(error) {
        this.isExporting = false;
        this.exportFailed = true;
        this.exportError = error || 'An unknown error occurred';
    },

    async cancelExport() {
        if (confirm('Are you sure you want to cancel the export?')) {
            await this.$wire.cancelExport();
            this.isExporting = false;
        }
    },

    exportAgain() {
        this.exportComplete = false;
        this.exportFailed = false;
        this.exportProgress = 0;
        this.exportedVideoUrl = null;
    },

    retryExport() {
        this.exportFailed = false;
        this.startExport();
    }
}
```

---

## Phase 7: Server-Side Rendering

### 7.1 Export Job Handler
**File:** `app/Jobs/ExportVideoJob.php`

```php
<?php

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AppVideoWizard\Services\VideoRenderService;

class ExportVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $projectId,
        public string $platform,
        public string $quality,
        public array $assemblySettings
    ) {}

    public function handle(VideoRenderService $renderService): void
    {
        $project = WizardProject::findOrFail($this->projectId);

        try {
            // Update status
            $this->updateProgress('preparing', 0);

            // Collect all assets
            $assets = $this->collectAssets($project);
            $this->updateProgress('collecting_assets', 10);

            // Process each scene
            $sceneCount = count($assets['scenes']);
            foreach ($assets['scenes'] as $index => $scene) {
                $this->updateSceneStatus($index, 'rendering');

                // Render scene
                $renderService->renderScene($scene, $this->assemblySettings);

                $this->updateSceneStatus($index, 'complete');
                $this->updateProgress(
                    'rendering_scenes',
                    10 + (($index + 1) / $sceneCount * 60)
                );
            }

            // Add voiceovers
            $this->updateProgress('adding_voiceovers', 75);
            $renderService->addVoiceovers($assets['voiceovers']);

            // Apply transitions
            $this->updateProgress('applying_transitions', 85);
            $renderService->applyTransitions($this->assemblySettings['transitions']);

            // Add background music
            if ($this->assemblySettings['music']['enabled']) {
                $this->updateProgress('adding_music', 90);
                $renderService->addBackgroundMusic($this->assemblySettings['music']);
            }

            // Encode final video
            $this->updateProgress('encoding', 95);
            $outputPath = $renderService->encode($this->quality);

            // Upload to storage
            $this->updateProgress('uploading', 98);
            $outputUrl = $this->uploadToStorage($outputPath);

            // Update project
            $project->update([
                'status' => 'completed',
                'output_url' => $outputUrl,
            ]);

            $this->updateProgress('completed', 100, $outputUrl);

        } catch (\Exception $e) {
            $this->updateProgress('failed', $this->getProgress(), null, $e->getMessage());
            $project->update(['status' => 'failed']);
            throw $e;
        }
    }

    private function updateProgress(string $stage, int $progress, ?string $outputUrl = null, ?string $error = null): void
    {
        cache()->put("export_progress_{$this->projectId}", [
            'status' => $stage === 'completed' ? 'completed' : ($stage === 'failed' ? 'failed' : 'processing'),
            'stage' => $this->getStageLabel($stage),
            'progress' => $progress,
            'scenesTotal' => $this->scenesTotal ?? 0,
            'scenesCompleted' => $this->scenesCompleted ?? 0,
            'sceneStatuses' => $this->sceneStatuses ?? [],
            'outputUrl' => $outputUrl,
            'error' => $error,
        ], now()->addHours(1));
    }

    private function getStageLabel(string $stage): string
    {
        return match($stage) {
            'preparing' => 'Preparing assets...',
            'collecting_assets' => 'Collecting media files...',
            'rendering_scenes' => 'Rendering scenes...',
            'adding_voiceovers' => 'Adding voiceovers...',
            'applying_transitions' => 'Applying transitions...',
            'adding_music' => 'Adding background music...',
            'encoding' => 'Encoding video...',
            'uploading' => 'Uploading to cloud...',
            'completed' => 'Export complete!',
            'failed' => 'Export failed',
            default => 'Processing...'
        };
    }
}
```

### 7.2 Livewire Export Methods
**Add to:** `VideoWizard.php`

```php
/**
 * Start video export
 */
public function startExport(array $config): array
{
    try {
        // Validate project is ready
        if (!$this->canExport()) {
            return ['error' => 'Project is not ready for export'];
        }

        // Deduct credits
        $creditCost = config('appvideowizard.credit_costs.video_export', 15);
        if (!$this->deductCredits($creditCost)) {
            return ['error' => 'Insufficient credits'];
        }

        // Create export job
        $job = ExportVideoJob::dispatch(
            $this->projectId,
            $config['platform'],
            $config['quality'],
            $this->assembly
        );

        return [
            'jobId' => $this->projectId,
            'status' => 'started'
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get export progress
 */
public function getExportProgress(int $jobId): array
{
    return cache()->get("export_progress_{$jobId}", [
        'status' => 'pending',
        'progress' => 0,
        'stage' => 'Waiting...'
    ]);
}

/**
 * Cancel export
 */
public function cancelExport(): bool
{
    // Cancel running job
    cache()->put("export_cancel_{$this->projectId}", true, now()->addMinutes(5));
    return true;
}

/**
 * Get export stats
 */
public function getExportStats(): array
{
    $scriptScenes = $this->script['scenes'] ?? [];
    $storyboardScenes = $this->storyboard['scenes'] ?? [];
    $animationScenes = $this->animation['scenes'] ?? [];

    $imageCount = collect($storyboardScenes)->filter(fn($s) => !empty($s['imageUrl']))->count();
    $animatedCount = collect($animationScenes)->filter(fn($s) => !empty($s['videoUrl']))->count();

    return [
        'sceneCount' => count($scriptScenes),
        'imageCount' => $imageCount,
        'animatedCount' => $animatedCount,
        'canExport' => $imageCount > 0 || $animatedCount > 0,
        'totalDuration' => collect($scriptScenes)->sum(fn($s) => $s['duration'] ?? 8),
    ];
}

/**
 * Check if project can be exported
 */
public function canExport(): bool
{
    $stats = $this->getExportStats();
    return $stats['canExport'];
}
```

---

## File Structure

```
modules/AppVideoWizard/
├── app/
│   ├── Livewire/
│   │   └── VideoWizard.php                    # Main component (modify)
│   ├── Jobs/
│   │   └── ExportVideoJob.php                 # NEW: Export job handler
│   └── Services/
│       ├── VideoRenderService.php             # NEW: FFmpeg rendering
│       └── MusicLibraryService.php            # NEW: Music library
├── resources/
│   ├── views/
│   │   └── livewire/
│   │       └── steps/
│   │           ├── assembly.blade.php         # REWRITE: Full-screen editor
│   │           ├── export.blade.php           # REWRITE: Enhanced export
│   │           └── partials/
│   │               ├── _assembly-header.blade.php
│   │               ├── _assembly-sidebar.blade.php
│   │               ├── _assembly-tabs.blade.php
│   │               ├── _assembly-preview.blade.php
│   │               ├── _assembly-timeline.blade.php
│   │               ├── _tab-text.blade.php
│   │               ├── _tab-audio.blade.php
│   │               ├── _tab-media.blade.php
│   │               ├── _tab-transitions.blade.php
│   │               ├── _music-browser-modal.blade.php
│   │               ├── _export-modal.blade.php
│   │               └── _preview-canvas.blade.php
│   ├── assets/
│   │   ├── js/
│   │   │   ├── video-preview-engine.js        # EXISTS: Needs integration
│   │   │   ├── assembly-studio.js             # NEW: Main controller
│   │   │   ├── timeline-controller.js         # NEW: Timeline logic
│   │   │   ├── preview-controller.js          # NEW: Preview logic
│   │   │   └── music-browser.js               # NEW: Music browser
│   │   └── css/
│   │       ├── assembly-studio.css            # NEW: Full-screen styles
│   │       └── timeline.css                   # NEW: Timeline styles
│   └── lang/
│       └── en/                                # Translations
└── config/
    └── config.php                             # Add music library, export settings
```

---

## Implementation Checklist

### Phase 1: Video Preview Engine Integration
- [ ] Create preview canvas partial
- [ ] Create Alpine.js preview controller
- [ ] Add `getPreviewScenes()` backend method
- [ ] Wire up Livewire ↔ Alpine communication
- [ ] Test basic preview playback
- [ ] Test Ken Burns effect on images
- [ ] Test video clip playback
- [ ] Test voiceover synchronization

### Phase 2: Assembly Studio Layout
- [ ] Create full-screen CSS styles
- [ ] Create header component
- [ ] Create sidebar component
- [ ] Create tabbed panel structure
- [ ] Create preview panel
- [ ] Wire up tab switching
- [ ] Implement keyboard shortcuts
- [ ] Test responsive behavior

### Phase 3: Text/Captions Panel
- [ ] Create text tab template
- [ ] Implement caption enable/disable
- [ ] Implement word/sentence mode toggle
- [ ] Implement font selection
- [ ] Implement color pickers
- [ ] Implement stroke width slider
- [ ] Implement effects grid
- [ ] Implement style presets
- [ ] Wire up real-time preview updates

### Phase 4: Audio Panel & Music Library
- [ ] Create audio tab template
- [ ] Implement Smart Audio AI panel
- [ ] Implement voiceover volume control
- [ ] Implement music enable/toggle
- [ ] Create music library backend service
- [ ] Create music browser modal
- [ ] Implement track search/filter
- [ ] Implement track preview
- [ ] Implement audio ducking toggle
- [ ] Implement fade in/out controls

### Phase 5: Professional Timeline
- [ ] Create timeline template
- [ ] Create timeline controller JS
- [ ] Implement time ruler
- [ ] Implement playhead
- [ ] Implement video track with clips
- [ ] Implement voice track
- [ ] Implement music track
- [ ] Implement captions track
- [ ] Implement clip selection
- [ ] Implement zoom controls
- [ ] Implement snap to grid
- [ ] Implement clip trimming (drag handles)
- [ ] Implement undo/redo

### Phase 6: Export System
- [ ] Create export modal template
- [ ] Implement platform selection
- [ ] Implement quality selection
- [ ] Implement duration warnings
- [ ] Implement progress UI
- [ ] Implement scene-by-scene indicators
- [ ] Implement cancel functionality
- [ ] Implement success state with preview
- [ ] Implement failed state with retry

### Phase 7: Server-Side Rendering
- [ ] Create ExportVideoJob
- [ ] Create VideoRenderService
- [ ] Implement asset collection
- [ ] Implement scene rendering
- [ ] Implement transition application
- [ ] Implement voiceover merging
- [ ] Implement music mixing
- [ ] Implement final encoding
- [ ] Implement cloud upload
- [ ] Implement progress tracking
- [ ] Test full export pipeline

### Final Testing
- [ ] Test complete workflow start to finish
- [ ] Test with standard mode (single video per scene)
- [ ] Test with multi-shot mode
- [ ] Test all transition types
- [ ] Test all caption styles
- [ ] Test music integration
- [ ] Test export at all quality levels
- [ ] Test error handling and recovery
- [ ] Performance testing with many scenes
- [ ] Mobile responsiveness testing

---

## Dependencies

### Required NPM Packages
- None new (uses native browser APIs)

### Required Composer Packages
- `pbmedia/laravel-ffmpeg` - For server-side video rendering

### External Services
- Cloud storage (S3, GCS, etc.) for exported videos
- Optional: AI service for Smart Audio recommendations

---

## Estimated Implementation Effort

| Phase | Complexity | Files | Notes |
|-------|------------|-------|-------|
| 1 | Medium | 3 | VideoPreviewEngine already exists |
| 2 | Medium | 6 | Layout restructure |
| 3 | Low | 2 | Mostly UI controls |
| 4 | Medium | 4 | Music library service needed |
| 5 | High | 3 | Most complex component |
| 6 | Medium | 3 | Modal + state management |
| 7 | High | 3 | FFmpeg integration |

---

## Notes for Implementation

1. **Start with Phase 1** - Get the preview engine working first, as all other features depend on it.

2. **Use Feature Flags** - Consider implementing phases behind feature flags so you can deploy incrementally.

3. **Test Mobile** - The full-screen layout should degrade gracefully on mobile. Consider a simplified mobile view.

4. **Performance** - The timeline can get slow with many scenes. Consider virtualization for large projects.

5. **FFmpeg** - Server-side rendering requires FFmpeg installed on the server. Consider using a dedicated rendering service or cloud functions for production.

6. **Credits** - Implement credit checking before export starts. Export is an expensive operation.

7. **Error Handling** - Export can fail for many reasons. Implement robust error handling and retry logic.

---

*Document created: January 2025*
*For: ArTime Laravel Video Wizard*
*Reference: video-creation-wizard.html (original implementation)*
