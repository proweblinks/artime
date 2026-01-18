{{-- Step 4: Storyboard - Full Screen Layout --}}
<style>
    /* ========================================
       STORYBOARD STUDIO - Full Screen Layout
       ======================================== */

    .vw-storyboard-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw !important;
        height: 100vh !important;
        background: linear-gradient(135deg, #0a0a14 0%, #141428 100%);
        z-index: 999999;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* CRITICAL: Force hide ALL app sidebars when Storyboard is active */
    .sidebar,
    .main-sidebar,
    div.sidebar,
    aside.sidebar,
    .hide-scroll.sidebar {
        z-index: 1 !important;
    }

    body.vw-storyboard-fullscreen-active {
        overflow: hidden !important;
    }

    body.vw-storyboard-fullscreen-active .sidebar,
    body.vw-storyboard-fullscreen-active .main-sidebar,
    body.vw-storyboard-fullscreen-active div.sidebar,
    body.vw-storyboard-fullscreen-active .sidebar.hide-scroll,
    body.vw-storyboard-fullscreen-active [class*="sidebar"]:not(.vw-storyboard-sidebar),
    body.vw-storyboard-fullscreen-active aside:not(.vw-storyboard-fullscreen aside),
    body.vw-storyboard-fullscreen-active nav:not(.vw-storyboard-fullscreen nav) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 0 !important;
        max-width: 0 !important;
        overflow: hidden !important;
    }

    body.vw-storyboard-fullscreen-active .main-content,
    body.vw-storyboard-fullscreen-active .page-wrapper,
    body.vw-storyboard-fullscreen-active [class*="content"]:not(.vw-storyboard-content):not(.vw-storyboard-fullscreen *) {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
    }

    /* Top Header Bar */
    .vw-storyboard-topbar {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.6rem 1.25rem;
        background: rgba(15, 15, 28, 0.98);
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        backdrop-filter: blur(10px);
    }

    .vw-storyboard-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-storyboard-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #ec4899, #f97316);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .vw-storyboard-title {
        font-weight: 700;
        color: white;
        font-size: 1rem;
        letter-spacing: -0.02em;
    }

    .vw-storyboard-subtitle {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Progress Pills in Header */
    .vw-storyboard-pills {
        display: flex;
        gap: 0.5rem;
        margin-left: 1.5rem;
    }

    .vw-storyboard-pill {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .vw-storyboard-pill .pill-value {
        font-weight: 600;
        color: #a78bfa;
    }

    .vw-storyboard-pill.complete {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .vw-storyboard-pill.complete .pill-value {
        color: #10b981;
    }

    /* Header Actions */
    .vw-storyboard-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-left: auto;
    }

    /* Settings Toggle Button */
    .vw-settings-toggle {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.85rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s;
    }

    .vw-settings-toggle:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .vw-settings-toggle.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: #a78bfa;
    }

    /* Main Content Area */
    .vw-storyboard-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Settings Panel (collapsible) */
    .vw-storyboard-settings-panel {
        flex-shrink: 0;
        background: rgba(20, 20, 35, 0.95);
        border-bottom: 1px solid rgba(139, 92, 246, 0.15);
        padding: 1rem 1.25rem;
        display: none;
    }

    .vw-storyboard-settings-panel.open {
        display: block;
    }

    .vw-settings-row {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .vw-settings-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-settings-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Scene Grid Container */
    .vw-storyboard-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem;
    }

    /* Legacy support - keep old card styles for nested elements */
    .vw-storyboard-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-storyboard-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
    }

    /* Section dividers */
    .vw-section {
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-section:first-of-type {
        border-top: none;
        padding-top: 0;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-section-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-badge {
        font-size: 0.55rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-badge-pro {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
    }

    .vw-badge-new {
        background: linear-gradient(135deg, #10b981, #06b6d4);
        color: white;
    }

    /* AI Model Selector */
    .vw-model-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-model-btn {
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        transition: all 0.2s;
        min-width: 110px;
    }

    .vw-model-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-model-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.25);
        color: white;
    }

    .vw-model-btn-name {
        font-weight: 600;
    }

    .vw-model-btn-cost {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-model-btn.selected .vw-model-btn-cost {
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-model-description {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        margin-top: 0.5rem;
        font-style: italic;
    }

    /* Visual Style Grid */
    .vw-style-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-style-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-style-select-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .vw-style-select-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-style-select {
        width: 100%;
        padding: 0.6rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .vw-style-select:focus {
        border-color: rgba(139, 92, 246, 0.5);
        outline: none;
    }

    .vw-style-hint {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.7rem;
        margin-top: 0.75rem;
    }

    /* Scene Memory Cards */
    .vw-memory-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-memory-grid {
            grid-template-columns: 1fr;
        }
    }

    .vw-memory-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-memory-icon {
        font-size: 1.5rem;
        width: 36px;
        text-align: center;
    }

    .vw-memory-content {
        flex: 1;
        min-width: 0;
    }

    .vw-memory-title {
        font-weight: 600;
        color: white;
        font-size: 0.85rem;
    }

    .vw-memory-desc {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.7rem;
        margin-top: 0.15rem;
    }

    .vw-memory-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-edit-btn {
        padding: 0.35rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-edit-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .vw-memory-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #8b5cf6;
        cursor: pointer;
    }

    /* Technical Specs */
    .vw-specs-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-specs-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
    }

    .vw-specs-value {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
        font-size: 0.8rem;
    }

    .vw-quality-badge {
        padding: 0.25rem 0.5rem;
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
    }

    /* Prompt Chain */
    .vw-chain-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-chain-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .vw-chain-title {
        font-weight: 600;
        color: white;
        font-size: 0.9rem;
    }

    .vw-chain-desc {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }

    .vw-chain-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.35rem;
    }

    .vw-chain-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .vw-chain-badge-ready {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
        border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .vw-chain-badge-processing {
        background: rgba(251, 191, 36, 0.2);
        color: #fcd34d;
        border: 1px solid rgba(251, 191, 36, 0.4);
        animation: vw-pulse 1.5s infinite;
    }

    .vw-chain-badge-idle {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .vw-chain-stats {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-chain-stat {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.25rem;
        color: rgba(255, 255, 255, 0.7);
    }

    @keyframes vw-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    .vw-chain-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-process-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #f59e0b, #f97316);
        border: none;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-process-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
    }

    .vw-process-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Progress Stats - Now inside the card */
    .vw-progress-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 0.75rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .vw-progress-stat {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-progress-stat-icon {
        font-size: 0.9rem;
    }

    .vw-progress-stat-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #10b981;
    }

    .vw-progress-stat-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-bulk-actions {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }

    .vw-generate-all-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 1.25rem;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-generate-all-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-generate-all-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Storyboard Grid - Larger cards for full-screen */
    .vw-storyboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .vw-storyboard-grid {
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1rem;
        }
    }

    @media (max-width: 768px) {
        .vw-storyboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Scene Card - Larger dark theme card */
    .vw-scene-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.2s;
    }

    .vw-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.05);
    }

    /* Scene Image Container - Larger */
    .vw-scene-image-container {
        position: relative;
        aspect-ratio: 16/9;
        background: rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }

    .vw-scene-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Empty State - Larger for full-screen layout */
    .vw-scene-empty {
        height: 100%;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.03);
        border: 2px dashed rgba(255, 255, 255, 0.15);
        border-radius: 0;
        margin: 0;
    }

    .vw-scene-empty-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 1rem;
        margin-bottom: 1.25rem;
    }

    .vw-scene-empty-buttons {
        display: flex;
        gap: 1rem;
        width: 100%;
        max-width: 380px;
    }

    .vw-scene-empty-btn {
        flex: 1;
        padding: 1rem 0.85rem;
        border-radius: 0.6rem;
        border: 1px solid;
        color: white;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s;
    }

    .vw-scene-empty-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-scene-empty-btn.ai:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.4));
        transform: translateY(-1px);
    }

    .vw-scene-empty-btn.stock {
        background: rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.4);
    }

    .vw-scene-empty-btn.stock:hover {
        background: rgba(16, 185, 129, 0.3);
        transform: translateY(-1px);
    }

    .vw-scene-empty-btn-icon {
        font-size: 1.5rem;
    }

    .vw-scene-empty-btn-cost {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-scene-empty-btn.stock .vw-scene-empty-btn-cost {
        color: rgba(16, 185, 129, 0.8);
    }

    /* Generating State */
    .vw-scene-generating {
        height: 100%;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(139, 92, 246, 0.08);
        gap: 1rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-spinner {
        width: 2.5rem;
        height: 2.5rem;
        border: 3px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-generating-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.95rem;
    }

    /* Alert */
    .vw-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-alert.warning {
        background: rgba(251, 191, 36, 0.12);
        border: 1px solid rgba(251, 191, 36, 0.25);
        color: #fbbf24;
    }

    .vw-alert.error {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #ef4444;
    }

    .vw-alert-icon {
        font-size: 1.25rem;
    }

    .vw-alert-text {
        font-size: 0.9rem;
    }

    .vw-alert-close {
        margin-left: auto;
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 1.25rem;
        opacity: 0.7;
    }

    .vw-alert-close:hover {
        opacity: 1;
    }
</style>

<div class="vw-storyboard-fullscreen" x-data="{ showSettings: false }">
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
                <span style="color: rgba(255,255,255,0.5);">{{ __('images') }}</span>
            </div>
            @if($multiShotMode['enabled'])
                @php $shotStats = $this->getShotStatistics(); @endphp
                <div class="vw-storyboard-pill">
                    <span>🎬</span>
                    <span class="pill-value">{{ $shotStats['totalShots'] }}</span>
                    <span style="color: rgba(255,255,255,0.5);">{{ __('shots') }}</span>
                </div>
            @endif
        </div>

        {{-- Header Actions --}}
        <div class="vw-storyboard-actions">
            {{-- Settings Toggle --}}
            <button type="button"
                    class="vw-settings-toggle"
                    :class="{ 'active': showSettings }"
                    @click="showSettings = !showSettings">
                <span>⚙️</span>
                <span>{{ __('Settings') }}</span>
            </button>

            {{-- Generate All Button --}}
            @if(!empty($script['scenes']))
                <button type="button"
                        class="vw-generate-all-btn"
                        wire:click="generateAllImages"
                        wire:loading.attr="disabled"
                        wire:target="generateAllImages">
                    <span wire:loading.remove wire:target="generateAllImages">🎨</span>
                    <span wire:loading wire:target="generateAllImages" style="display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.6s linear infinite;"></span>
                    {{ __('Generate All Images') }}
                </button>
            @endif

            {{-- Navigation Buttons --}}
            <button type="button"
                    wire:click="goToStep(3)"
                    style="padding: 0.45rem 0.85rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.8); cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem;">
                <span>←</span>
                <span>{{ __('Script') }}</span>
            </button>

            <button type="button"
                    wire:click="goToStep(5)"
                    style="padding: 0.45rem 0.85rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
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
        {{-- Main Content Area --}}
        <div class="vw-storyboard-main">
            {{-- Collapsible Settings Panel --}}
            <div class="vw-storyboard-settings-panel" :class="{ 'open': showSettings }">
                <div class="vw-settings-row">
                    {{-- AI Model Selector --}}
                    <div class="vw-settings-group">
                        <span class="vw-settings-label"><span>🤖</span> {{ __('AI Model:') }}</span>
                        @php
                            $imageModels = [
                                'hidream' => ['name' => 'HiDream', 'cost' => 2, 'desc' => 'Artistic & cinematic style'],
                                'nanobanana-pro' => ['name' => 'NanoBanana Pro', 'cost' => 3, 'desc' => 'High quality, fast generation'],
                                'nanobanana' => ['name' => 'NanoBanana', 'cost' => 1, 'desc' => 'Quick drafts, lower cost'],
                            ];
                            $selectedModel = $storyboard['imageModel'] ?? 'nanobanana';
                        @endphp
                        <div class="vw-model-buttons">
                            @foreach($imageModels as $modelId => $model)
                                <button type="button"
                                        class="vw-model-btn {{ $selectedModel === $modelId ? 'selected' : '' }}"
                                        wire:click="$set('storyboard.imageModel', '{{ $modelId }}')"
                                        title="{{ $model['desc'] }}">
                                    <span class="vw-model-btn-name">{{ $model['name'] }}</span>
                                    <span class="vw-model-btn-cost">{{ $model['cost'] }}t</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Multi-Shot Mode Toggle --}}
                    <div class="vw-settings-group">
                        <span class="vw-settings-label">
                            <span>🎬</span>
                            {{ __('Multi-Shot Mode') }}
                            <span class="vw-badge vw-badge-pro">PRO</span>
                        </span>
                        <label style="display: flex; align-items: center; cursor: pointer; gap: 0.5rem;">
                            <span style="font-size: 0.75rem; color: {{ $multiShotMode['enabled'] ? '#a78bfa' : 'rgba(255,255,255,0.5)' }};">
                                {{ $multiShotMode['enabled'] ? __('On') : __('Off') }}
                            </span>
                            <div style="position: relative; width: 40px; height: 20px;"
                                 wire:click="toggleMultiShotMode">
                                <div style="width: 100%; height: 100%; background: {{ $multiShotMode['enabled'] ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 10px; transition: all 0.2s;"></div>
                                <div style="position: absolute; top: 2px; left: {{ $multiShotMode['enabled'] ? '22px' : '2px' }}; width: 16px; height: 16px; background: {{ $multiShotMode['enabled'] ? '#8b5cf6' : 'white' }}; border-radius: 50%; transition: all 0.2s;"></div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Scrollable Content --}}
            <div class="vw-storyboard-content">
                {{-- Multi-Shot Stats (only when enabled) --}}
                @if($multiShotMode['enabled'])
                    @php
                        $shotStats = $this->getShotStatistics();
                        $clipDuration = $this->getClipDuration();
                        $sceneTiming = $script['timing'] ?? ['sceneDuration' => 35, 'pacing' => 'balanced'];
                    @endphp
                    <div style="background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                            {{-- Stats --}}
                            <div style="display: flex; gap: 1.5rem; align-items: center;">
                                <div style="text-align: center;">
                                    <div style="font-size: 1.25rem; font-weight: 700; color: #8b5cf6;">{{ $shotStats['totalScenes'] }}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.5);">{{ __('Scenes') }}</div>
                                </div>
                                <span style="color: rgba(255,255,255,0.3);">→</span>
                                <div style="text-align: center;">
                                    <div style="font-size: 1.25rem; font-weight: 700; color: #06b6d4;">{{ $shotStats['decomposedScenes'] }}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.5);">{{ __('Decomposed') }}</div>
                                </div>
                                <span style="color: rgba(255,255,255,0.3);">→</span>
                                <div style="text-align: center;">
                                    <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;">{{ $shotStats['totalShots'] }}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.5);">{{ __('Shots') }}</div>
                                </div>
                            </div>

                            {{-- Progress Bars --}}
                            <div style="display: flex; gap: 1.5rem;">
                                <div style="min-width: 120px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                        <span style="font-size: 0.65rem; color: rgba(255,255,255,0.6);">🖼️ {{ __('Images') }}</span>
                                        <span style="font-size: 0.65rem; color: #10b981;">{{ $shotStats['shotsWithImages'] }}/{{ $shotStats['totalShots'] }}</span>
                                    </div>
                                    <div style="height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $shotStats['imageProgress'] }}%; background: linear-gradient(90deg, #10b981, #22c55e);"></div>
                                    </div>
                                </div>
                                <div style="min-width: 120px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                        <span style="font-size: 0.65rem; color: rgba(255,255,255,0.6);">🎬 {{ __('Videos') }}</span>
                                        <span style="font-size: 0.65rem; color: #06b6d4;">{{ $shotStats['shotsWithVideos'] }}/{{ $shotStats['totalShots'] }}</span>
                                    </div>
                                    <div style="height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $shotStats['videoProgress'] }}%; background: linear-gradient(90deg, #06b6d4, #22d3ee);"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Timing Info --}}
                            <div style="display: flex; gap: 0.75rem; padding: 0.4rem 0.75rem; background: rgba(0,0,0,0.2); border-radius: 0.5rem;">
                                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.7);">⏱️ {{ $sceneTiming['sceneDuration'] }}s/scene</span>
                                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.7);">🎞️ {{ $clipDuration }}s/clip</span>
                            </div>
                        </div>
                    </div>
                @endif

            {{-- Video Model Selector (Coming Soon) --}}
            <div class="vw-section" style="opacity: 0.6;">
                <div class="vw-section-header">
                    <div class="vw-section-label">
                        <span>🎬</span>
                        <span>{{ __('Video Model') }}</span>
                    </div>
                    <span style="font-size: 0.55rem; padding: 0.15rem 0.5rem; background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.4); border-radius: 0.5rem; color: #a5b4fc;">
                        {{ __('COMING SOON') }}
                    </span>
                </div>
                <div class="vw-model-buttons" style="pointer-events: none;">
                    @php
                        $videoModels = [
                            'minimax' => ['name' => 'Minimax', 'cost' => 5, 'desc' => 'Fast video generation, 5-10s clips'],
                            'multitalk' => ['name' => 'Multitalk', 'cost' => 8, 'desc' => 'Lip-sync & dialogue, character animation'],
                            'kling' => ['name' => 'Kling AI', 'cost' => 10, 'desc' => 'High-quality cinematic motion'],
                        ];
                        $selectedVideoModel = $storyboard['videoModel'] ?? 'minimax';
                    @endphp
                    @foreach($videoModels as $modelId => $model)
                        <button type="button"
                                class="vw-model-btn {{ $selectedVideoModel === $modelId ? 'selected' : '' }}"
                                style="opacity: 0.5; cursor: not-allowed;"
                                disabled
                                title="{{ $model['desc'] }}">
                            <span class="vw-model-btn-name">{{ $model['name'] }}</span>
                            <span class="vw-model-btn-cost">{{ $model['cost'] }} {{ __('tokens') }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="vw-model-description" style="color: rgba(255,255,255,0.4);">
                    💡 {{ __('Video generation will animate your scenes with motion and optional lip-sync.') }}
                </p>
            </div>

            {{-- Visual Style Controls --}}
            @php
                $hasActiveStyles = !empty($storyboard['visualStyle']['mood'] ?? '') ||
                                   !empty($storyboard['visualStyle']['lighting'] ?? '') ||
                                   !empty($storyboard['visualStyle']['colorPalette'] ?? '') ||
                                   !empty($storyboard['visualStyle']['composition'] ?? '');
                $activeStyleParts = [];
                if (!empty($storyboard['visualStyle']['mood'])) {
                    $activeStyleParts[] = ucfirst($storyboard['visualStyle']['mood']) . ' mood';
                }
                if (!empty($storyboard['visualStyle']['lighting'])) {
                    $activeStyleParts[] = ucfirst(str_replace('-', ' ', $storyboard['visualStyle']['lighting'])) . ' lighting';
                }
                if (!empty($storyboard['visualStyle']['colorPalette'])) {
                    $activeStyleParts[] = ucfirst(str_replace('-', ' ', $storyboard['visualStyle']['colorPalette']));
                }
                if (!empty($storyboard['visualStyle']['composition'])) {
                    $activeStyleParts[] = ucfirst(str_replace('-', ' ', $storyboard['visualStyle']['composition'])) . ' shot';
                }
            @endphp
            <div class="vw-section">
                <div class="vw-section-header">
                    <div class="vw-section-label">
                        <span>🎬</span>
                        <span>{{ __('Visual Style') }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        @if($hasActiveStyles)
                            <span style="font-size: 0.55rem; padding: 0.15rem 0.4rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.5rem; color: #6ee7b7;">
                                ✓ {{ __('Connected') }}
                            </span>
                        @endif
                        <span class="vw-badge vw-badge-pro">PRO</span>
                    </div>
                </div>
                {{-- Active Style Preview --}}
                @if($hasActiveStyles)
                    <div style="margin-bottom: 0.75rem; padding: 0.5rem 0.75rem; background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.1)); border: 1px solid rgba(16,185,129,0.2); border-radius: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
                            <span style="font-size: 0.65rem; color: #6ee7b7; font-weight: 600;">🔗 {{ __('Active Style Profile') }}</span>
                        </div>
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.8); line-height: 1.4;">
                            {{ implode(' • ', $activeStyleParts) }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.55rem; color: rgba(255,255,255,0.4);">
                            → {{ __('Applied to all AI-generated images via Layer 4 in Prompt Chain') }}
                        </div>
                    </div>
                @endif
                <div class="vw-style-grid">
                    {{-- Mood --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Mood') }}</span>
                        <select class="vw-style-select" wire:model.change="storyboard.visualStyle.mood">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="epic">{{ __('Epic') }}</option>
                            <option value="intimate">{{ __('Intimate') }}</option>
                            <option value="mysterious">{{ __('Mysterious') }}</option>
                            <option value="energetic">{{ __('Energetic') }}</option>
                            <option value="contemplative">{{ __('Contemplative') }}</option>
                            <option value="tense">{{ __('Tense') }}</option>
                            <option value="hopeful">{{ __('Hopeful') }}</option>
                            <option value="professional">{{ __('Professional') }}</option>
                        </select>
                    </div>
                    {{-- Lighting --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Lighting') }}</span>
                        <select class="vw-style-select" wire:model.change="storyboard.visualStyle.lighting">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="natural">{{ __('Natural') }}</option>
                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                            <option value="blue-hour">{{ __('Blue Hour') }}</option>
                            <option value="high-key">{{ __('High Key') }}</option>
                            <option value="low-key">{{ __('Low Key/Noir') }}</option>
                            <option value="neon">{{ __('Neon') }}</option>
                        </select>
                    </div>
                    {{-- Colors --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Colors') }}</span>
                        <select class="vw-style-select" wire:model.change="storyboard.visualStyle.colorPalette">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="teal-orange">{{ __('Teal/Orange') }}</option>
                            <option value="warm-tones">{{ __('Warm Tones') }}</option>
                            <option value="cool-tones">{{ __('Cool Tones') }}</option>
                            <option value="desaturated">{{ __('Desaturated') }}</option>
                            <option value="vibrant">{{ __('Vibrant') }}</option>
                            <option value="pastel">{{ __('Pastel') }}</option>
                        </select>
                    </div>
                    {{-- Shot --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Shot') }}</span>
                        <select class="vw-style-select" wire:model.change="storyboard.visualStyle.composition">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="wide">{{ __('Wide') }}</option>
                            <option value="medium">{{ __('Medium') }}</option>
                            <option value="close-up">{{ __('Close-up') }}</option>
                            <option value="extreme-close-up">{{ __('Extreme CU') }}</option>
                            <option value="low-angle">{{ __('Low Angle') }}</option>
                            <option value="birds-eye">{{ __("Bird's Eye") }}</option>
                        </select>
                    </div>
                </div>
                <p class="vw-style-hint">
                    💡 {{ __('"Auto" uses genre-appropriate defaults based on your content settings') }}
                </p>
            </div>

            {{-- Scene Memory Section --}}
            <div class="vw-section">
                <div class="vw-section-header">
                    <div class="vw-section-label">
                        <span>🧠</span>
                        <span>{{ __('Scene Memory') }}</span>
                    </div>
                    <span class="vw-badge vw-badge-new">NEW</span>
                </div>
                <p class="vw-style-hint" style="margin-top: 0; margin-bottom: 0.75rem;">
                    {{ __('Visual consistency with Style, Character & Location Bibles') }}
                </p>
                <div class="vw-memory-grid">
                    {{-- Style Bible - Managed via Scene DNA --}}
                    <div class="vw-memory-card" style="opacity: 0.6;">
                        <div class="vw-memory-icon">🎨</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Style Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Managed via Scene DNA') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openSceneDNAModal('style')" wire:loading.attr="disabled" wire:target="openSceneDNAModal" style="font-size: 0.6rem;">
                                <span wire:loading.remove wire:target="openSceneDNAModal">{{ __('Scene DNA') }} →</span>
                                <span wire:loading wire:target="openSceneDNAModal">...</span>
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.styleBible.enabled"
                                   title="{{ __('Enable Style Bible') }}">
                        </div>
                    </div>

                    {{-- Character Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-icon">👤</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Character Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Consistent faces') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openCharacterBibleModal" wire:loading.attr="disabled" wire:target="openCharacterBibleModal">
                                <span wire:loading.remove wire:target="openCharacterBibleModal">{{ __('Edit') }}</span>
                                <span wire:loading wire:target="openCharacterBibleModal">...</span>
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.characterBible.enabled"
                                   title="{{ __('Enable Character Bible') }}">
                        </div>
                    </div>

                    {{-- Location Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-icon">📍</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Location Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Consistent environments') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openLocationBibleModal" wire:loading.attr="disabled" wire:target="openLocationBibleModal">
                                <span wire:loading.remove wire:target="openLocationBibleModal">{{ __('Edit') }}</span>
                                <span wire:loading wire:target="openLocationBibleModal">...</span>
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.locationBible.enabled"
                                   title="{{ __('Enable Location Bible') }}">
                        </div>
                    </div>

                    {{-- Scene DNA - Unified Bible View (Always Enabled) --}}
                    @php
                        $continuityIssueCount = count($sceneMemory['sceneDNA']['continuityIssues'] ?? []);
                    @endphp
                    <div class="vw-memory-card" style="background: linear-gradient(135deg, rgba(6,182,212,0.08), rgba(139,92,246,0.08)); border-color: rgba(6,182,212,0.3);">
                        <div class="vw-memory-icon" style="background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(139,92,246,0.2));">&#x1F9EC;</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title" style="display: flex; align-items: center; gap: 0.35rem;">
                                {{ __('Scene DNA') }}
                                <span style="font-size: 0.55rem; color: #10b981;">&#x2713;</span>
                            </div>
                            <div class="vw-memory-desc">
                                {{ __('Unified Bible data') }}
                                @if($continuityIssueCount > 0)
                                    <span style="color: #f97316; margin-left: 0.25rem;">{{ $continuityIssueCount }} {{ __('issues') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openSceneDNAModal" wire:loading.attr="disabled" wire:target="openSceneDNAModal" style="background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(139,92,246,0.2)); border-color: rgba(6,182,212,0.4);">
                                <span wire:loading.remove wire:target="openSceneDNAModal">{{ __('View') }}</span>
                                <span wire:loading wire:target="openSceneDNAModal">...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Technical Specs Section (Expandable) --}}
            <div class="vw-section" x-data="{ specsOpen: false }">
                <div class="vw-section-header" style="cursor: pointer;" @click="specsOpen = !specsOpen">
                    <div class="vw-section-label">
                        <span style="transition: transform 0.2s;" :style="specsOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                        <span>⚙️</span>
                        <span>{{ __('Technical Specs') }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span class="vw-quality-badge">{{ $storyboard['technicalSpecs']['quality'] ?? '4K' }} {{ __('quality') }}</span>
                        <input type="checkbox"
                               class="vw-memory-checkbox"
                               wire:model.live="storyboard.technicalSpecs.enabled"
                               title="{{ __('Enable Technical Specs') }}"
                               @click.stop>
                    </div>
                </div>

                {{-- Expandable Content --}}
                <div x-show="specsOpen" x-collapse style="margin-top: 0.75rem;">
                    {{-- Output Quality --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">{{ __('Output Quality') }}</label>
                        <select class="vw-style-select" wire:model.change="storyboard.technicalSpecs.quality" style="max-width: 200px;">
                            <option value="4k">{{ __('4K (3840×2160)') }}</option>
                            <option value="2k">{{ __('2K (2560×1440)') }}</option>
                            <option value="1080p">{{ __('1080p (1920×1080)') }}</option>
                            <option value="720p">{{ __('720p (1280×720)') }}</option>
                        </select>
                    </div>

                    {{-- Positive Prompts --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">
                            ✅ {{ __('Positive Prompts') }}
                            <span style="color: rgba(255,255,255,0.3); font-weight: normal;">({{ __('enhance quality') }})</span>
                        </label>
                        <textarea wire:model.blur="storyboard.technicalSpecs.positive"
                                  placeholder="{{ __('high quality, detailed, professional, 8K resolution, sharp focus, cinematic...') }}"
                                  style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.5rem; color: white; font-size: 0.8rem; min-height: 60px; resize: vertical;"></textarea>
                    </div>

                    {{-- Negative Prompts --}}
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">
                            ❌ {{ __('Negative Prompts') }}
                            <span style="color: rgba(255,255,255,0.3); font-weight: normal;">({{ __('avoid these') }})</span>
                        </label>
                        <textarea wire:model.blur="storyboard.technicalSpecs.negative"
                                  placeholder="{{ __('blurry, low quality, ugly, distorted, watermark, text, logo, nsfw...') }}"
                                  style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; color: white; font-size: 0.8rem; min-height: 60px; resize: vertical;"></textarea>
                    </div>

                    {{-- Quick Presets --}}
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem;">
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.65rem; margin-right: 0.25rem;">{{ __('Presets:') }}</span>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('cinematic')" style="padding: 0.2rem 0.5rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.6rem; cursor: pointer;">🎬 {{ __('Cinematic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('photorealistic')" style="padding: 0.2rem 0.5rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.25rem; color: #67e8f9; font-size: 0.6rem; cursor: pointer;">📷 {{ __('Photorealistic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('artistic')" style="padding: 0.2rem 0.5rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.25rem; color: #f472b6; font-size: 0.6rem; cursor: pointer;">🎨 {{ __('Artistic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('documentary')" style="padding: 0.2rem 0.5rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.25rem; color: #6ee7b7; font-size: 0.6rem; cursor: pointer;">📹 {{ __('Documentary') }}</button>
                    </div>
                </div>
            </div>

            {{-- Prompt Chain Section --}}
            @php
                $chainStatus = $storyboard['promptChain']['status'] ?? 'idle';
                $chainEnabled = $storyboard['promptChain']['enabled'] ?? true;
                $chainScenes = $storyboard['promptChain']['scenes'] ?? [];
                $chainProcessedAt = $storyboard['promptChain']['processedAt'] ?? null;
                $processedCount = count($chainScenes);
                $totalScenes = count($script['scenes'] ?? []);
            @endphp
            <div class="vw-section" style="background: {{ $chainStatus === 'ready' ? 'rgba(251, 191, 36, 0.08)' : 'transparent' }}; border-radius: 0.5rem; margin: 0 -0.5rem; padding: 0.75rem 0.5rem;">
                <div class="vw-chain-row">
                    <div class="vw-chain-info" style="flex: 1;">
                        <div class="vw-section-label" style="margin-bottom: 0.25rem;">
                            <span>⛓️</span>
                            <span>{{ __('Prompt Chain') }}</span>
                        </div>
                        <div class="vw-chain-desc">
                            @if($chainStatus === 'ready')
                                {{ __('Optimized prompts ready for generation') }}
                            @elseif($chainStatus === 'processing')
                                {{ __('Processing scene blueprints...') }}
                            @else
                                {{ __('Hollywood-grade scene blueprints') }}
                            @endif
                        </div>
                        {{-- Status Badge Row --}}
                        <div class="vw-chain-status">
                            @if($chainStatus === 'ready')
                                <span class="vw-chain-badge vw-chain-badge-ready">✓ {{ __('Ready') }}</span>
                            @elseif($chainStatus === 'processing')
                                <span class="vw-chain-badge vw-chain-badge-processing">⏳ {{ __('Processing') }}</span>
                            @else
                                <span class="vw-chain-badge vw-chain-badge-idle">○ {{ __('Not Processed') }}</span>
                            @endif
                            @if($chainProcessedAt && $chainStatus === 'ready')
                                <span style="font-size: 0.6rem; color: rgba(255,255,255,0.4);">
                                    {{ \Carbon\Carbon::parse($chainProcessedAt)->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        {{-- Stats when ready --}}
                        @if($chainStatus === 'ready' && $processedCount > 0)
                            <div class="vw-chain-stats">
                                <span class="vw-chain-stat">🖼️ {{ $processedCount }} {{ __('Image Prompts') }}</span>
                                <span class="vw-chain-stat">🎬 {{ $processedCount }} {{ __('Video Templates') }}</span>
                                <span class="vw-chain-stat">↔️ {{ $processedCount }} {{ __('Transitions') }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="vw-chain-actions">
                        @if($chainStatus === 'ready')
                            <button type="button"
                                    class="vw-process-btn"
                                    wire:click="processPromptChain"
                                    wire:loading.attr="disabled"
                                    wire:target="processPromptChain"
                                    style="background: rgba(251, 191, 36, 0.3); border: 1px solid rgba(251, 191, 36, 0.5);">
                                <span wire:loading.remove wire:target="processPromptChain">
                                    🔄 {{ __('Refresh') }}
                                </span>
                                <span wire:loading wire:target="processPromptChain">
                                    <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                    </svg>
                                </span>
                            </button>
                        @else
                            <button type="button"
                                    class="vw-process-btn"
                                    wire:click="processPromptChain"
                                    wire:loading.attr="disabled"
                                    wire:target="processPromptChain">
                                <span wire:loading.remove wire:target="processPromptChain">
                                    ⚡ {{ __('Process Chain') }}
                                </span>
                                <span wire:loading wire:target="processPromptChain">
                                    <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                    </svg>
                                    {{ __('Processing...') }}
                                </span>
                            </button>
                        @endif
                        <input type="checkbox"
                               class="vw-memory-checkbox"
                               wire:model.live="storyboard.promptChain.enabled"
                               title="{{ __('Enable Prompt Chain') }}"
                               {{ $chainEnabled ? 'checked' : '' }}>
                    </div>
                </div>
            </div>

            {{-- Scene Stats Bar --}}
            @php
                $paginatedData = $this->paginatedScenes;
                $showPagination = $paginatedData['totalPages'] > 1;
            @endphp
            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.15); border-radius: 0.5rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span>🖼️</span>
                    <span style="font-weight: 600; color: #10b981;">{{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.75rem;">{{ __('images') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span>🎬</span>
                    <span style="font-weight: 600; color: #8b5cf6;">{{ $paginatedData['totalScenes'] }}</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.75rem;">{{ __('scenes') }}</span>
                </div>
                @if($showPagination)
                    <span style="color: rgba(255,255,255,0.4); font-size: 0.75rem;">
                        {{ __('Showing') }} {{ $paginatedData['showingFrom'] }}-{{ $paginatedData['showingTo'] }}
                    </span>
                @endif
            </div>

            {{-- Pagination Controls (Top) --}}
            @if($showPagination)
                <div class="vw-pagination-controls" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem;">
                    <button type="button"
                            wire:click="previousStoryboardPage"
                            @disabled(!$paginatedData['hasPrevious'])
                            style="padding: 0.4rem 0.75rem; border-radius: 0.35rem; border: 1px solid {{ $paginatedData['hasPrevious'] ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; background: {{ $paginatedData['hasPrevious'] ? 'rgba(139,92,246,0.15)' : 'rgba(255,255,255,0.03)' }}; color: {{ $paginatedData['hasPrevious'] ? 'white' : 'rgba(255,255,255,0.3)' }}; cursor: {{ $paginatedData['hasPrevious'] ? 'pointer' : 'not-allowed' }}; font-size: 0.75rem; font-weight: 600;">
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
                                        style="width: 32px; height: 32px; border-radius: 0.35rem; border: 1px solid {{ $p === $paginatedData['currentPage'] ? '#8b5cf6' : 'rgba(255,255,255,0.15)' }}; background: {{ $p === $paginatedData['currentPage'] ? 'rgba(139,92,246,0.3)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 0.75rem; font-weight: {{ $p === $paginatedData['currentPage'] ? '700' : '500' }};">
                                    {{ $p }}
                                </button>
                            @elseif($showEllipsis)
                                <span style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4);">…</span>
                            @endif
                        @endfor
                    </div>

                    <button type="button"
                            wire:click="nextStoryboardPage"
                            @disabled(!$paginatedData['hasNext'])
                            style="padding: 0.4rem 0.75rem; border-radius: 0.35rem; border: 1px solid {{ $paginatedData['hasNext'] ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; background: {{ $paginatedData['hasNext'] ? 'rgba(139,92,246,0.15)' : 'rgba(255,255,255,0.03)' }}; color: {{ $paginatedData['hasNext'] ? 'white' : 'rgba(255,255,255,0.3)' }}; cursor: {{ $paginatedData['hasNext'] ? 'pointer' : 'not-allowed' }}; font-size: 0.75rem; font-weight: 600;">
                        {{ __('Next') }} →
                    </button>

                    {{-- Jump to page dropdown --}}
                    <select wire:model.live="storyboardPage"
                            style="padding: 0.4rem 0.5rem; border-radius: 0.35rem; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: white; font-size: 0.7rem; cursor: pointer;">
                        @for($p = 1; $p <= $paginatedData['totalPages']; $p++)
                            <option value="{{ $p }}">{{ __('Page') }} {{ $p }}</option>
                        @endfor
                    </select>
                </div>
            @endif

            {{-- Storyboard Grid - Using Paginated Scenes --}}
            <div class="vw-storyboard-grid">
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
                @endphp
                <div class="vw-scene-card">
                    {{-- Image Container with Overlays --}}
                    <div style="position: relative;">
                        {{-- Scene Number Badge - Always visible, top-left --}}
                        <div style="position: absolute; top: 0.75rem; left: 0.75rem; background: rgba(0,0,0,0.8); color: white; padding: 0.35rem 0.75rem; border-radius: 0.35rem; font-size: 0.9rem; font-weight: 600; z-index: 10;">
                            {{ __('Scene') }} {{ $index + 1 }}
                        </div>

                        {{-- Multi-Shot Badge - Top right if decomposed --}}
                        @if($hasMultiShot && !empty($decomposed['shots']))
                            <div style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10;">
                                <span style="background: linear-gradient(135deg, #8b5cf6, #06b6d4); color: white; padding: 0.3rem 0.65rem; border-radius: 0.35rem; font-size: 0.8rem; font-weight: 600;">
                                    📽️ {{ count($decomposed['shots']) }} {{ __('shots') }}
                                </span>
                            </div>
                        @endif

                        {{-- Chain Processed Indicator - Below scene number if chain is ready --}}
                        @if($hasChainData && ($storyboard['promptChain']['enabled'] ?? true))
                            <div style="position: absolute; top: 3rem; left: 0.75rem; background: rgba(251,191,36,0.9); color: #1a1a1a; padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700; z-index: 10; letter-spacing: 0.3px;">
                                ⛓️ {{ __('CHAIN') }}
                            </div>
                        @endif

                        {{-- Main Image Content Area --}}
                        <div class="vw-scene-image-container">
                            @if($status === 'generating')
                                {{-- Generating State --}}
                                <div class="vw-scene-generating">
                                    <div class="vw-spinner"></div>
                                    <span class="vw-generating-text">{{ __('Generating...') }}</span>
                                    <button type="button"
                                            wire:click="cancelImageGeneration({{ $index }})"
                                            wire:confirm="{{ __('Cancel this generation? You can retry afterwards.') }}"
                                            style="margin-top: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 0.25rem; border: 1px solid rgba(239,68,68,0.5); background: rgba(239,68,68,0.2); color: #f87171; cursor: pointer; font-size: 0.7rem; transition: all 0.2s;"
                                            onmouseover="this.style.background='rgba(239,68,68,0.4)'"
                                            onmouseout="this.style.background='rgba(239,68,68,0.2)'"
                                            title="{{ __('Cancel and retry') }}">
                                        ✕ {{ __('Cancel') }}
                                    </button>
                                </div>
                            @elseif($imageUrl)
                                {{-- Image Ready --}}
                                <img src="{{ $imageUrl }}"
                                     alt="Scene {{ $index + 1 }}"
                                     class="vw-scene-image"
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
                                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __('Image not available') }}</span>
                                    <button type="button"
                                            wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                            style="padding: 0.3rem 0.6rem; border-radius: 0.3rem; border: 1px solid rgba(139,92,246,0.5); background: rgba(139,92,246,0.3); color: white; cursor: pointer; font-size: 0.65rem;">
                                        🔄 {{ __('Regenerate') }}
                                    </button>
                                </div>

                                @php
                                    $isVideo = $source === 'stock-video';
                                    $sourceBgColor = $source === 'stock' ? 'rgba(16,185,129,0.9)' : ($isVideo ? 'rgba(6,182,212,0.9)' : 'rgba(139,92,246,0.9)');
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

                                {{-- Action Buttons Overlay - Bottom of image --}}
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 1rem; display: flex; gap: 0.6rem; z-index: 10;">
                                    <button type="button"
                                            wire:click="openAIEditModal({{ $index }})"
                                            style="flex: 1; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(236,72,153,0.5); background: linear-gradient(135deg, rgba(236,72,153,0.3), rgba(139,92,246,0.3)); color: white; cursor: pointer; font-size: 0.85rem;"
                                            title="{{ __('Edit with AI') }}">
                                        ✨ {{ __('Edit') }}
                                    </button>
                                    <button type="button"
                                            wire:click="openEditPromptModal({{ $index }})"
                                            style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.85rem;"
                                            title="{{ __('Modify prompt') }}">
                                        ✏️
                                    </button>
                                    <button type="button"
                                            wire:click="openStockBrowser({{ $index }})"
                                            style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(16,185,129,0.5); background: rgba(16,185,129,0.2); color: white; cursor: pointer; font-size: 0.85rem;"
                                            title="{{ __('Browse stock media') }}">
                                        📷
                                    </button>
                                    <button type="button"
                                            wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                            wire:loading.attr="disabled"
                                            style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.85rem;"
                                            title="{{ __('Regenerate with AI') }}">
                                        🔄
                                    </button>
                                    <button type="button"
                                            wire:click="openUpscaleModal({{ $index }})"
                                            style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(251,191,36,0.5); background: rgba(251,191,36,0.2); color: white; cursor: pointer; font-size: 0.85rem;"
                                            title="{{ __('Upscale to HD/4K') }}">
                                        ⬆️
                                    </button>
                                    <button type="button"
                                            wire:click="openMultiShotModal({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openMultiShotModal"
                                            style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(139,92,246,0.6); background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(6,182,212,0.3)); color: white; cursor: pointer; font-size: 0.85rem; font-weight: 600;"
                                            title="{{ __('Multi-shot decomposition') }}">
                                        <span wire:loading.remove wire:target="openMultiShotModal({{ $index }})">✂️</span>
                                        <span wire:loading wire:target="openMultiShotModal({{ $index }})" style="display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.6s linear infinite;"></span>
                                    </button>
                                </div>
                            @elseif($status === 'error')
                                {{-- Error State --}}
                                <div style="height: 220px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                        <span style="font-size: 1.25rem;">⚠️</span>
                                        <span style="color: #ef4444; font-size: 0.9rem;">{{ Str::limit($storyboardScene['error'] ?? __('Generation failed'), 50) }}</span>
                                    </div>
                                    <div style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 0.75rem;">{{ __('Choose to retry:') }}</div>
                                    <div style="display: flex; gap: 0.75rem; width: 100%; max-width: 320px;">
                                        <button type="button"
                                                wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                wire:loading.attr="disabled"
                                                style="flex: 1; padding: 0.75rem 0.5rem; background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(6,182,212,0.3)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
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
                                {{-- Show loading spinner while generating (wire:loading targets this specific scene) --}}
                                <div class="vw-scene-generating"
                                     wire:loading
                                     wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                     style="display: none;">
                                    <div class="vw-spinner"></div>
                                    <span class="vw-generating-text">{{ __('Generating...') }}</span>
                                </div>
                                <div class="vw-scene-empty"
                                     wire:loading.remove
                                     wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                    <div class="vw-scene-empty-text">{{ __('Choose image source:') }}</div>
                                    <div class="vw-scene-empty-buttons">
                                        <button type="button"
                                                class="vw-scene-empty-btn ai"
                                                wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                            <span class="vw-scene-empty-btn-icon" wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">🎨</span>
                                            <span wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">{{ __('AI Generate') }}</span>
                                            <span wire:loading wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                                <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                </svg>
                                            </span>
                                            <span class="vw-scene-empty-btn-cost" wire:loading.remove wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                        </button>
                                        <button type="button"
                                                class="vw-scene-empty-btn stock"
                                                wire:click="openStockBrowser({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateImage({{ $index }}, '{{ $scene['id'] }}')">
                                            <span class="vw-scene-empty-btn-icon">📷</span>
                                            <span>{{ __('Stock Media') }}</span>
                                            <span class="vw-scene-empty-btn-cost">{{ __('FREE') }}</span>
                                        </button>
                                        <button type="button"
                                                class="vw-scene-empty-btn collage"
                                                wire:click="generateCollagePreview({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateCollagePreview({{ $index }})"
                                                style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.15), rgba(139, 92, 246, 0.15)); border-color: rgba(236, 72, 153, 0.4);">
                                            <span class="vw-scene-empty-btn-icon" wire:loading.remove wire:target="generateCollagePreview({{ $index }})">🖼️</span>
                                            <span wire:loading.remove wire:target="generateCollagePreview({{ $index }})">{{ __('Collage First') }}</span>
                                            <span wire:loading wire:target="generateCollagePreview({{ $index }})">
                                                <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                </svg>
                                            </span>
                                            <span class="vw-scene-empty-btn-cost" wire:loading.remove wire:target="generateCollagePreview({{ $index }})">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                        </button>
                                    </div>

                                    {{-- Collage Preview (when generated) - Multi-Page Support --}}
                                    @php
                                        $sceneCollage = $sceneCollages[$index] ?? null;
                                        $collageCurrentPage = $sceneCollage['currentPage'] ?? 0;
                                        $collageTotalPages = $sceneCollage['totalPages'] ?? 1;
                                        $currentCollageData = $sceneCollage['collages'][$collageCurrentPage] ?? null;
                                        $currentCollageShots = $currentCollageData['shots'] ?? [];
                                        $collageRangeStart = !empty($currentCollageShots) ? min($currentCollageShots) + 1 : 1;
                                        $collageRangeEnd = !empty($currentCollageShots) ? max($currentCollageShots) + 1 : 4;
                                    @endphp
                                    @if($sceneCollage && in_array($sceneCollage['status'], ['ready', 'generating', 'processing']) && empty($decomposed['shots']))
                                        <div style="margin-top: 0.75rem; padding: 0.5rem; background: rgba(236, 72, 153, 0.08); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 0.5rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                                <span style="font-size: 0.7rem; color: rgba(255,255,255,0.8); font-weight: 600;">
                                                    🖼️ {{ __('Collage Preview') }}
                                                    @if($sceneCollage['status'] === 'ready' && $collageTotalPages > 1)
                                                        <span style="font-size: 0.55rem; color: rgba(255,255,255,0.6); margin-left: 0.25rem;">({{ __('Shots :start-:end', ['start' => $collageRangeStart, 'end' => $collageRangeEnd]) }})</span>
                                                    @endif
                                                </span>
                                                <button type="button"
                                                        wire:click="clearCollagePreview({{ $index }})"
                                                        style="font-size: 0.6rem; color: rgba(255,255,255,0.5); background: none; border: none; cursor: pointer;">
                                                    ✕
                                                </button>
                                            </div>

                                            @if($sceneCollage['status'] === 'generating' || $sceneCollage['status'] === 'processing')
                                                <div style="height: 100px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 0.375rem;">
                                                    <div style="text-align: center;">
                                                        <div style="width: 24px; height: 24px; border: 2px solid rgba(236, 72, 153, 0.3); border-top-color: #ec4899; border-radius: 50%; animation: vw-spin 1s linear infinite; margin: 0 auto;"></div>
                                                        <span style="font-size: 0.6rem; color: rgba(255,255,255,0.5); margin-top: 0.35rem; display: block;">{{ __('Generating...') }}</span>
                                                    </div>
                                                </div>
                                            @elseif($sceneCollage['status'] === 'ready' && !empty($currentCollageData['previewUrl']))
                                                <div style="font-size: 0.55rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">
                                                    {{ __('Click a quadrant to use as scene image:') }}
                                                </div>
                                                {{-- Single collage image with clickable quadrant overlay --}}
                                                <div style="position: relative; border-radius: 0.25rem; overflow: hidden;">
                                                    {{-- The single collage image --}}
                                                    <img src="{{ $currentCollageData['previewUrl'] }}"
                                                         alt="Collage Preview"
                                                         style="width: 100%; display: block; border-radius: 0.25rem;">

                                                    {{-- Clickable quadrant overlay grid (2x2) --}}
                                                    <div style="position: absolute; inset: 0; display: grid; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr);">
                                                        @for($regionIdx = 0; $regionIdx < 4; $regionIdx++)
                                                            <div wire:click="setSceneImageFromCollageRegion({{ $index }}, {{ $collageCurrentPage }}, {{ $regionIdx }})"
                                                                 style="position: relative; cursor: pointer; border: 1px solid rgba(255,255,255,0.2); transition: all 0.2s;"
                                                                 onmouseover="this.style.background='rgba(236, 72, 153, 0.3)'; this.style.borderColor='rgba(236, 72, 153, 0.8)';"
                                                                 onmouseout="this.style.background='transparent'; this.style.borderColor='rgba(255,255,255,0.2)';">

                                                                {{-- Region number badge --}}
                                                                <div style="position: absolute; top: 0.2rem; left: 0.2rem; background: rgba(0,0,0,0.8); color: white; padding: 0.1rem 0.25rem; border-radius: 0.15rem; font-size: 0.5rem; font-weight: 600; z-index: 2;">
                                                                    {{ ($currentCollageShots[$regionIdx] ?? $regionIdx) + 1 }}
                                                                </div>

                                                                {{-- Use This hover text --}}
                                                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; pointer-events: none;"
                                                                     class="use-this-overlay">
                                                                    <span style="font-size: 0.55rem; color: white; font-weight: 600; text-shadow: 0 1px 3px rgba(0,0,0,0.5); background: rgba(236, 72, 153, 0.8); padding: 0.15rem 0.4rem; border-radius: 0.2rem;">{{ __('Use This') }}</span>
                                                                </div>
                                                            </div>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <style>
                                                    .use-this-overlay { opacity: 0; }
                                                    div:hover > .use-this-overlay { opacity: 1 !important; }
                                                </style>

                                                {{-- Pagination Controls --}}
                                                @if($collageTotalPages > 1)
                                                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                                        {{-- Page indicator --}}
                                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                                            <span style="font-size: 0.6rem; color: rgba(255,255,255,0.7); font-weight: 500;">
                                                                {{ __('Page') }} {{ $collageCurrentPage + 1 }}/{{ $collageTotalPages }}
                                                            </span>
                                                            <span style="font-size: 0.55rem; color: rgba(236, 72, 153, 0.9); font-weight: 500;">
                                                                {{ __('Shots') }} {{ $collageRangeStart }}-{{ $collageRangeEnd }}
                                                            </span>
                                                        </div>
                                                        {{-- Navigation buttons --}}
                                                        <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                                                            <button type="button"
                                                                    wire:click="prevCollagePage({{ $index }})"
                                                                    {{ $collageCurrentPage <= 0 ? 'disabled' : '' }}
                                                                    style="padding: 0.2rem 0.45rem; background: {{ $collageCurrentPage > 0 ? 'rgba(236, 72, 153, 0.2)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $collageCurrentPage > 0 ? 'rgba(236, 72, 153, 0.4)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.25rem; color: {{ $collageCurrentPage > 0 ? 'white' : 'rgba(255,255,255,0.3)' }}; font-size: 0.6rem; cursor: {{ $collageCurrentPage > 0 ? 'pointer' : 'not-allowed' }};">
                                                                ◀ {{ __('Prev') }}
                                                            </button>
                                                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                                                @for($pageIdx = 0; $pageIdx < $collageTotalPages; $pageIdx++)
                                                                    <button type="button"
                                                                            wire:click="setCollagePage({{ $index }}, {{ $pageIdx }})"
                                                                            style="width: 20px; height: 20px; background: {{ $pageIdx === $collageCurrentPage ? 'linear-gradient(135deg, rgba(236, 72, 153, 0.5), rgba(168, 85, 247, 0.4))' : 'rgba(255,255,255,0.1)' }}; border: 1px solid {{ $pageIdx === $collageCurrentPage ? 'rgba(236, 72, 153, 0.7)' : 'rgba(255,255,255,0.2)' }}; border-radius: 50%; color: white; font-size: 0.55rem; font-weight: 600; cursor: pointer; box-shadow: {{ $pageIdx === $collageCurrentPage ? '0 2px 8px rgba(236, 72, 153, 0.3)' : 'none' }};">
                                                                        {{ $pageIdx + 1 }}
                                                                    </button>
                                                                @endfor
                                                            </div>
                                                            <button type="button"
                                                                    wire:click="nextCollagePage({{ $index }})"
                                                                    {{ $collageCurrentPage >= $collageTotalPages - 1 ? 'disabled' : '' }}
                                                                    style="padding: 0.2rem 0.45rem; background: {{ $collageCurrentPage < $collageTotalPages - 1 ? 'rgba(236, 72, 153, 0.2)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $collageCurrentPage < $collageTotalPages - 1 ? 'rgba(236, 72, 153, 0.4)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.25rem; color: {{ $collageCurrentPage < $collageTotalPages - 1 ? 'white' : 'rgba(255,255,255,0.3)' }}; font-size: 0.6rem; cursor: {{ $collageCurrentPage < $collageTotalPages - 1 ? 'pointer' : 'not-allowed' }};">
                                                                {{ __('Next') }} ▶
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Multi-Shot Timeline (if decomposed) --}}
                    @if($hasMultiShot && !empty($decomposed['shots']))
                        @php
                            $shotChainStatus = $this->getShotChainStatus($index);
                            $totalShotDuration = $decomposed['totalDuration'] ?? array_sum(array_column($decomposed['shots'], 'duration'));
                        @endphp
                        <div style="padding: 0.6rem 0.75rem; border-top: 1px solid rgba(139,92,246,0.2); background: linear-gradient(180deg, rgba(139,92,246,0.08), rgba(139,92,246,0.03));">
                            {{-- Header row with stats --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 0.6rem; color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                        📽️ {{ count($decomposed['shots']) }} {{ __('SHOTS') }}
                                    </span>
                                    <span style="font-size: 0.55rem; color: rgba(255,255,255,0.4);">• {{ $totalShotDuration }}s</span>
                                    {{-- Mini progress indicators --}}
                                    <div style="display: flex; gap: 0.25rem;">
                                        <span style="font-size: 0.5rem; padding: 0.1rem 0.25rem; background: rgba(16,185,129,0.2); border-radius: 0.15rem; color: #10b981;">
                                            🖼️ {{ $shotChainStatus['imagesReady'] }}/{{ $shotChainStatus['totalShots'] }}
                                        </span>
                                        <span style="font-size: 0.5rem; padding: 0.1rem 0.25rem; background: rgba(6,182,212,0.2); border-radius: 0.15rem; color: #06b6d4;">
                                            🎬 {{ $shotChainStatus['videosReady'] }}/{{ $shotChainStatus['totalShots'] }}
                                        </span>
                                    </div>
                                </div>
                                <button type="button"
                                        wire:click="openMultiShotModal({{ $index }})"
                                        wire:loading.attr="disabled"
                                        style="font-size: 0.55rem; padding: 0.15rem 0.4rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.25rem; color: #a78bfa; cursor: pointer;">
                                    <span wire:loading.remove wire:target="openMultiShotModal({{ $index }})">✂️ {{ __('Edit') }}</span>
                                    <span wire:loading wire:target="openMultiShotModal({{ $index }})">⏳</span>
                                </button>
                            </div>
                            {{-- Shots Grid with Frame Chain --}}
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.35rem;">
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
                                        $shotTypeIcons = [
                                            'establishing' => '🏔️',
                                            'medium' => '👤',
                                            'close-up' => '🔍',
                                            'reaction' => '😮',
                                            'detail' => '✨',
                                            'wide' => '🌄',
                                        ];
                                        $shotIcon = $shotTypeIcons[strtolower($shot['type'] ?? '')] ?? '🎬';
                                        $borderColor = $hasVideo ? 'rgba(6,182,212,0.6)' : ($hasImage ? 'rgba(16,185,129,0.5)' : ($isSelected ? '#8b5cf6' : 'rgba(255,255,255,0.1)'));
                                    @endphp
                                    <div style="cursor: pointer; position: relative; border-radius: 0.35rem; overflow: hidden; border: 2px solid {{ $borderColor }}; background: {{ $isSelected ? 'rgba(139,92,246,0.15)' : 'rgba(0,0,0,0.2)' }}; transition: all 0.2s;"
                                         wire:click="openMultiShotModal({{ $index }})"
                                         title="{{ $shot['description'] ?? 'Shot ' . ($shotIdx + 1) }} ({{ $shotDuration }}s)">
                                        {{-- Thumbnail --}}
                                        <div style="aspect-ratio: 16/10; position: relative;">
                                            @if($hasImage)
                                                <img src="{{ $shot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                {{-- Video play indicator --}}
                                                @if($hasVideo)
                                                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                                                        <div style="width: 20px; height: 20px; background: rgba(6,182,212,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                            <span style="font-size: 0.5rem; color: white; margin-left: 1px;">▶</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            @elseif($shotStatus === 'generating')
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(139,92,246,0.1);">
                                                    <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite; color: #8b5cf6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(255,255,255,0.03);">
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
                                                <div style="position: absolute; top: 2px; right: 2px; background: rgba(139,92,246,0.9); color: white; padding: 0.05rem 0.15rem; border-radius: 0.1rem; font-size: 0.4rem;">
                                                    📸
                                                </div>
                                            @endif

                                            {{-- Duration Badge --}}
                                            <div style="position: absolute; bottom: 2px; right: 2px; background: rgba(0,0,0,0.8); color: white; padding: 0.05rem 0.2rem; border-radius: 0.1rem; font-size: 0.45rem;">
                                                {{ $shotDuration }}s
                                            </div>
                                        </div>

                                        {{-- Shot Status Bar --}}
                                        <div style="height: 3px; background: rgba(255,255,255,0.1);">
                                            @if($hasVideo)
                                                <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #06b6d4, #22d3ee);"></div>
                                            @elseif($hasImage)
                                                <div style="height: 100%; width: 50%; background: linear-gradient(90deg, #10b981, #22c55e);"></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Quick Actions --}}
                            @if($shotChainStatus['imagesReady'] < $shotChainStatus['totalShots'] || $shotChainStatus['videosReady'] < $shotChainStatus['totalShots'])
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.5rem;">
                                    @if($shotChainStatus['imagesReady'] < $shotChainStatus['totalShots'])
                                        <button type="button"
                                                wire:click="generateAllShots({{ $index }})"
                                                wire:loading.attr="disabled"
                                                style="flex: 1; padding: 0.3rem 0.5rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.25rem; color: #10b981; cursor: pointer; font-size: 0.55rem;">
                                            🖼️ {{ __('Generate Images') }}
                                        </button>
                                    @endif
                                    @if($shotChainStatus['imagesReady'] > 0 && $shotChainStatus['videosReady'] < $shotChainStatus['totalShots'])
                                        <button type="button"
                                                wire:click="generateAllShotVideos({{ $index }})"
                                                wire:loading.attr="disabled"
                                                style="flex: 1; padding: 0.3rem 0.5rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.25rem; color: #06b6d4; cursor: pointer; font-size: 0.55rem;">
                                            🎬 {{ __('Animate All') }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Prompt Section --}}
                    <div style="padding: 1rem;">
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            {{ __('PROMPT') }}
                        </div>
                        <div style="font-size: 0.95rem; color: rgba(255,255,255,0.75); line-height: 1.5; max-height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                            {{ Str::limit($prompt, 180) }}
                        </div>
                        {{-- Duration & Transition --}}
                        <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255,255,255,0.08); display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: rgba(6,182,212,0.15); color: #67e8f9; border-radius: 0.25rem;">
                                ⏱️ {{ $scene['duration'] ?? 8 }}s
                            </span>
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.4);">
                                ↔️ {{ $scene['transition'] ?? 'cut' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
            </div> {{-- Close vw-storyboard-content --}}
        </div> {{-- Close vw-storyboard-main --}}
    @endif

    {{-- Stock Media Browser Modal --}}
    @include('appvideowizard::livewire.modals.stock-browser')

    {{-- Style Bible Modal --}}
    @include('appvideowizard::livewire.modals.style-bible')

    {{-- Character Bible Modal --}}
    @include('appvideowizard::livewire.modals.character-bible')

    {{-- Location Bible Modal --}}
    @include('appvideowizard::livewire.modals.location-bible')

    {{-- Scene DNA Overview Modal --}}
    @include('appvideowizard::livewire.modals.scene-dna')

    {{-- Edit Prompt Modal --}}
    @include('appvideowizard::livewire.modals.edit-prompt')

    {{-- Multi-Shot Decomposition Modal --}}
    @include('appvideowizard::livewire.modals.multi-shot')

    {{-- Upscale Modal --}}
    @include('appvideowizard::livewire.modals.upscale')

    {{-- AI Edit Modal --}}
    @include('appvideowizard::livewire.modals.ai-edit')
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
