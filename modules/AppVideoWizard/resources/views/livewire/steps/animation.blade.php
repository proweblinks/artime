{{-- Step 5: Animation Studio Pro --}}
<style>
    /* ========================================
       ANIMATION STUDIO PRO - Full Screen Layout
       ======================================== */

    .vw-animation-studio {
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

    /* CRITICAL: Force hide ALL app sidebars and navigation when Animation Studio is active */
    .sidebar,
    .main-sidebar,
    div.sidebar,
    aside.sidebar,
    .hide-scroll.sidebar {
        z-index: 1 !important;
    }

    /* Ensure full-screen coverage - hide main app sidebar */
    body.vw-animation-fullscreen {
        overflow: hidden !important;
    }

    body.vw-animation-fullscreen .sidebar,
    body.vw-animation-fullscreen .main-sidebar,
    body.vw-animation-fullscreen div.sidebar,
    body.vw-animation-fullscreen .sidebar.hide-scroll,
    body.vw-animation-fullscreen [class*="sidebar"]:not(.vw-scene-grid-panel),
    body.vw-animation-fullscreen aside:not(.vw-animation-studio aside),
    body.vw-animation-fullscreen nav:not(.vw-animation-studio nav) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 0 !important;
        max-width: 0 !important;
        overflow: hidden !important;
    }

    body.vw-animation-fullscreen .main-content,
    body.vw-animation-fullscreen .page-wrapper,
    body.vw-animation-fullscreen [class*="content"]:not(.vw-studio-content):not(.vw-animation-studio *) {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
    }

    /* Top Header Bar */
    .vw-studio-header {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.6rem 1.25rem;
        background: rgba(15, 15, 28, 0.98);
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        backdrop-filter: blur(10px);
    }

    .vw-studio-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-studio-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .vw-studio-title {
        font-weight: 700;
        color: white;
        font-size: 1rem;
        letter-spacing: -0.02em;
    }

    .vw-studio-subtitle {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Progress Pills */
    .vw-studio-pills {
        display: flex;
        gap: 0.5rem;
        margin-left: 1.5rem;
    }

    .vw-studio-pill {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 2rem;
        font-size: 0.7rem;
    }

    .vw-studio-pill.voiceover {
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .vw-studio-pill.voiceover.complete {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .vw-studio-pill.voiceover .pill-value {
        color: #a78bfa;
        font-weight: 600;
    }

    .vw-studio-pill.voiceover.complete .pill-value {
        color: #10b981;
    }

    .vw-studio-pill.animated {
        background: rgba(6, 182, 212, 0.15);
        border: 1px solid rgba(6, 182, 212, 0.3);
    }

    .vw-studio-pill.animated .pill-value {
        color: #06b6d4;
        font-weight: 600;
    }

    .vw-studio-pill.ready {
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .vw-studio-pill.ready .pill-value {
        color: #10b981;
        font-weight: 600;
    }

    /* Header Actions */
    .vw-studio-actions {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-studio-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-studio-btn.back {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-studio-btn.back:hover {
        border-color: rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-studio-btn.continue {
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-studio-btn.continue.enabled {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-weight: 600;
    }

    .vw-studio-btn.continue.enabled:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Main Split Panel Content */
    .vw-studio-content {
        flex: 1;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 0;
        overflow: hidden;
    }

    @media (max-width: 900px) {
        .vw-studio-content {
            grid-template-columns: 1fr;
        }
        .vw-scene-grid-panel {
            display: none;
        }
    }

    /* ========================================
       LEFT PANEL - Scene Grid
       ======================================== */

    .vw-scene-grid-panel {
        background: rgba(15, 15, 28, 0.98);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .vw-scene-grid-header {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-scene-grid-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.6rem;
    }

    .vw-scene-grid-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-scene-grid-title span {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-scene-grid-tools {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-tool-btn {
        width: 26px;
        height: 26px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .vw-tool-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
    }

    /* Quick Actions */
    .vw-quick-actions {
        display: flex;
        gap: 0.4rem;
    }

    .vw-quick-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.45rem;
        border-radius: 0.4rem;
        border: none;
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-quick-btn.voice {
        background: linear-gradient(135deg, #8b5cf6, #a855f7);
    }

    .vw-quick-btn.voice:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
    }

    .vw-quick-btn.animate {
        background: linear-gradient(135deg, #06b6d4, #10b981);
    }

    .vw-quick-btn.animate:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(6, 182, 212, 0.4);
    }

    .vw-quick-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Hint */
    .vw-scene-grid-hint {
        padding: 0.35rem 1rem;
        background: rgba(139, 92, 246, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.55rem;
        color: rgba(255, 255, 255, 0.35);
    }

    /* Scene Grid Footer with Stats */
    .vw-scene-grid-footer {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: rgba(10, 10, 20, 0.98);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-footer-stat {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-footer-stat-icon {
        font-size: 0.8rem;
    }

    .vw-footer-stat-text {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 500;
    }

    /* View Mode Toggle Buttons */
    .vw-view-modes {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-view-mode-btn {
        width: 26px;
        height: 26px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        transition: all 0.2s;
    }

    .vw-view-mode-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-view-mode-btn.active {
        border-color: rgba(139, 92, 246, 0.5);
        background: rgba(139, 92, 246, 0.15);
        color: #a78bfa;
    }

    /* Scrollable Scene List */
    .vw-scene-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem;
    }

    /* Scene Card */
    .vw-scene-card {
        position: relative;
        display: flex;
        gap: 0.6rem;
        padding: 0.5rem;
        margin-bottom: 0.4rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .vw-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-scene-card.selected {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-scene-card.processing {
        border-color: rgba(251, 191, 36, 0.4);
    }

    /* Progress Ring Container */
    .vw-progress-ring-container {
        position: relative;
        width: 60px;
        height: 60px;
        flex-shrink: 0;
    }

    .vw-progress-ring {
        transform: rotate(-90deg);
    }

    .vw-progress-ring-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.1);
        stroke-width: 3;
    }

    .vw-progress-ring-fill {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.5s ease;
    }

    .vw-scene-thumb-inner {
        position: absolute;
        top: 5px;
        left: 5px;
        width: 50px;
        height: 50px;
        border-radius: 0.35rem;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.3);
    }

    .vw-scene-thumb-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-scene-thumb-empty {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-size: 1rem;
    }

    .vw-scene-number {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.55rem;
        font-weight: 600;
        color: white;
        z-index: 1;
    }

    /* Scene Info */
    .vw-scene-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .vw-scene-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0.2rem;
    }

    .vw-scene-duration {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.35rem;
    }

    .vw-scene-progress-text {
        font-size: 0.65rem;
        font-weight: 500;
    }

    .vw-scene-status {
        display: flex;
        gap: 0.25rem;
    }

    .vw-status-badge {
        font-size: 0.55rem;
        padding: 0.1rem 0.3rem;
        border-radius: 0.2rem;
        font-weight: 500;
    }

    .vw-status-badge.voice-ready {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .vw-status-badge.voice-pending {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .vw-status-badge.animated {
        background: rgba(6, 182, 212, 0.2);
        color: #06b6d4;
    }

    .vw-status-badge.generating {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
        animation: vw-pulse-badge 1.5s infinite;
    }

    .vw-status-badge.video-pending {
        background: rgba(107, 114, 128, 0.2);
        color: #9ca3af;
    }

    .vw-status-badge.video-ready {
        background: rgba(6, 182, 212, 0.2);
        color: #06b6d4;
    }

    .vw-status-badge.error {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    .vw-status-badge.music-only {
        background: rgba(236, 72, 153, 0.2);
        color: #ec4899;
    }

    @keyframes vw-pulse-badge {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* ========================================
       RIGHT PANEL - Detail View
       ======================================== */

    .vw-detail-panel {
        flex: 1;
        background: linear-gradient(180deg, rgba(15, 15, 28, 0.95) 0%, rgba(10, 10, 20, 0.98) 100%);
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .vw-detail-content {
        width: 100%;
        max-width: 700px;
    }

    /* Scene Preview Header */
    .vw-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .vw-preview-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-preview-title-icon {
        font-size: 1rem;
    }

    .vw-preview-title-text {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
    }

    .vw-preview-badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-weight: 600;
    }

    .vw-preview-badge.ken-burns {
        background: rgba(6, 182, 212, 0.2);
        color: #06b6d4;
    }

    .vw-preview-badge.animated {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .vw-preview-badge.stock {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .vw-preview-tools {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-preview-tool-btn {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-preview-tool-btn.pip {
        border: 1px solid rgba(6, 182, 212, 0.3);
        background: rgba(6, 182, 212, 0.1);
        color: #06b6d4;
    }

    .vw-preview-tool-btn.device {
        border: 1px solid rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
    }

    .vw-preview-scene-count {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Scene Navigation */
    .vw-scene-navigation {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-nav-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .vw-nav-btn:hover:not(:disabled) {
        border-color: rgba(139, 92, 246, 0.5);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
    }

    .vw-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .vw-scene-indicator {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.6rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.4rem;
    }

    .vw-scene-indicator-current {
        font-size: 0.85rem;
        font-weight: 700;
        color: #a78bfa;
    }

    .vw-scene-indicator-total {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-duration-display {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.6rem;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 0.4rem;
    }

    .vw-duration-icon {
        font-size: 0.75rem;
    }

    .vw-duration-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #10b981;
    }

    .vw-duration-label {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Keyboard shortcuts hint */
    .vw-keyboard-hint {
        position: fixed;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        background: rgba(15, 15, 28, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.5rem;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.6);
        z-index: 1000;
        backdrop-filter: blur(10px);
    }

    .vw-keyboard-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 0.4rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.8);
    }

    /* Main Preview Container */
    .vw-preview-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(20, 20, 40, 0.6));
        border-radius: 0.75rem;
        overflow: hidden;
        border: 2px solid rgba(139, 92, 246, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        margin-bottom: 1rem;
    }

    .vw-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-preview-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Ken Burns Animation */
    @keyframes kenBurnsPreview {
        0% { transform: scale(1) translate(0, 0); }
        50% { transform: scale(1.08) translate(-1%, -1%); }
        100% { transform: scale(1) translate(0, 0); }
    }

    .vw-ken-burns-preview {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .vw-ken-burns-preview img {
        width: 115%;
        height: 115%;
        object-fit: cover;
        object-position: center;
        animation: kenBurnsPreview 8s ease-in-out infinite alternate;
        transform-origin: center center;
    }

    /* Preview Overlays */
    .vw-preview-play-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.3);
        cursor: pointer;
        transition: background 0.2s;
    }

    .vw-preview-play-overlay:hover {
        background: rgba(0, 0, 0, 0.1);
    }

    .vw-preview-play-btn {
        width: 70px;
        height: 70px;
        background: rgba(139, 92, 246, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        font-size: 1.75rem;
        padding-left: 4px;
    }

    .vw-preview-status-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        padding: 0.35rem 0.7rem;
        border-radius: 0.35rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: white;
    }

    .vw-preview-status-badge.animated {
        background: rgba(16, 185, 129, 0.9);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .vw-preview-status-badge.generating {
        background: rgba(139, 92, 246, 0.9);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    /* Scene Info Overlay */
    .vw-preview-info-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent);
        padding: 2rem 1rem 0.85rem;
    }

    .vw-preview-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-preview-scene-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.2rem;
    }

    .vw-preview-scene-duration {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-preview-quick-actions {
        display: flex;
        gap: 0.4rem;
    }

    .vw-preview-action-btn {
        padding: 0.4rem 0.7rem;
        border-radius: 0.4rem;
        border: none;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.2s;
    }

    .vw-preview-action-btn.animate {
        background: rgba(6, 182, 212, 0.9);
        color: white;
    }

    .vw-preview-action-btn.animate:hover {
        background: rgba(6, 182, 212, 1);
    }

    /* Empty State */
    .vw-preview-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
    }

    .vw-preview-empty-content {
        text-align: center;
    }

    .vw-preview-empty-icon {
        font-size: 3.5rem;
        margin-bottom: 0.75rem;
    }

    .vw-preview-empty-text {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .vw-preview-empty-hint {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.2);
        margin-top: 0.25rem;
    }

    /* Generating Overlay */
    .vw-preview-generating {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .vw-preview-generating-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid rgba(6, 182, 212, 0.3);
        border-top-color: #06b6d4;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
        margin-bottom: 1rem;
    }

    .vw-preview-generating-text {
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .vw-preview-generating-hint {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    /* Mini Timeline */
    .vw-mini-timeline {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        padding: 0.5rem 0;
        margin-bottom: 1rem;
    }

    .vw-mini-timeline-item {
        flex-shrink: 0;
        width: 60px;
        height: 34px;
        border-radius: 0.25rem;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        opacity: 0.5;
        transition: all 0.2s;
    }

    .vw-mini-timeline-item:hover {
        opacity: 0.8;
    }

    .vw-mini-timeline-item.active {
        border-color: #8b5cf6;
        opacity: 1;
    }

    .vw-mini-timeline-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-mini-timeline-empty {
        width: 100%;
        height: 100%;
        background: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-size: 0.6rem;
    }

    /* Section Cards */
    .vw-section-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-section-title-icon {
        font-size: 1rem;
    }

    .vw-section-title-text {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-section-badge {
        font-size: 0.5rem;
        padding: 0.15rem 0.35rem;
        border-radius: 0.2rem;
        font-weight: 600;
    }

    .vw-section-badge.auto {
        background: linear-gradient(135deg, #f59e0b, #ec4899);
        color: white;
    }

    /* Alert */
    .vw-studio-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem;
        background: rgba(251, 191, 36, 0.15);
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 0.75rem;
        color: #fbbf24;
        margin: 2rem auto;
        max-width: 500px;
    }

    .vw-studio-alert-icon {
        font-size: 1.5rem;
    }

    .vw-studio-alert-text {
        font-size: 1rem;
    }

    /* ========================================
       PHASE 5: VIDEO PLAYER CONTROLS
       ======================================== */

    /* Video Player Container */
    .vw-video-player {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .vw-video-player video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Video Controls Overlay */
    .vw-video-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.85), rgba(0,0,0,0.4) 60%, transparent);
        padding: 2.5rem 1rem 0.75rem;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .vw-video-player:hover .vw-video-controls,
    .vw-video-player.controls-visible .vw-video-controls {
        opacity: 1;
    }

    /* Progress Bar */
    .vw-progress-container {
        position: relative;
        height: 4px;
        background: rgba(255,255,255,0.2);
        border-radius: 2px;
        margin-bottom: 0.6rem;
        cursor: pointer;
        transition: height 0.15s;
    }

    .vw-progress-container:hover {
        height: 6px;
    }

    .vw-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 2px;
        transition: width 0.1s;
    }

    .vw-progress-handle {
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 14px;
        height: 14px;
        background: white;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        transition: transform 0.15s;
    }

    .vw-progress-container:hover .vw-progress-handle {
        transform: translate(-50%, -50%) scale(1);
    }

    /* Control Buttons Row */
    .vw-controls-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-control-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .vw-control-btn:hover {
        background: rgba(255,255,255,0.2);
        transform: scale(1.05);
    }

    .vw-control-btn.play {
        width: 44px;
        height: 44px;
        background: rgba(139,92,246,0.9);
        font-size: 1.1rem;
    }

    .vw-control-btn.play:hover {
        background: rgba(139,92,246,1);
    }

    /* Time Display */
    .vw-time-display {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-family: 'SF Mono', 'Fira Code', monospace;
        color: rgba(255,255,255,0.8);
    }

    .vw-time-current {
        color: white;
        font-weight: 600;
    }

    .vw-time-separator {
        color: rgba(255,255,255,0.4);
    }

    .vw-time-total {
        color: rgba(255,255,255,0.6);
    }

    /* Volume Control */
    .vw-volume-control {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-left: auto;
    }

    .vw-volume-slider {
        width: 0;
        height: 4px;
        background: rgba(255,255,255,0.2);
        border-radius: 2px;
        cursor: pointer;
        transition: width 0.2s;
        overflow: hidden;
    }

    .vw-volume-control:hover .vw-volume-slider {
        width: 70px;
    }

    .vw-volume-slider input[type="range"] {
        width: 100%;
        height: 100%;
        -webkit-appearance: none;
        background: transparent;
        cursor: pointer;
    }

    .vw-volume-slider input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 12px;
        height: 12px;
        background: white;
        border-radius: 50%;
        cursor: pointer;
    }

    /* Fullscreen & Loop Buttons */
    .vw-player-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: auto;
    }

    .vw-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: none;
        background: transparent;
        color: rgba(255,255,255,0.7);
        font-size: 0.9rem;
        cursor: pointer;
        border-radius: 0.25rem;
        transition: all 0.15s;
    }

    .vw-action-btn:hover {
        background: rgba(255,255,255,0.1);
        color: white;
    }

    .vw-action-btn.active {
        color: #06b6d4;
    }

    /* Center Play Button (for paused state) */
    .vw-center-play {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        background: rgba(139, 92, 246, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        cursor: pointer;
        box-shadow: 0 4px 24px rgba(139, 92, 246, 0.5);
        transition: all 0.2s;
        padding-left: 5px;
    }

    .vw-center-play:hover {
        transform: translate(-50%, -50%) scale(1.08);
        box-shadow: 0 6px 32px rgba(139, 92, 246, 0.6);
    }

    /* Fullscreen Mode */
    .vw-preview-container.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        border-radius: 0;
        aspect-ratio: unset;
        border: none;
    }

    .vw-fullscreen-exit {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 10000;
        padding: 0.5rem 0.75rem;
        background: rgba(0,0,0,0.6);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 0.4rem;
        color: white;
        font-size: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-fullscreen-exit:hover {
        background: rgba(0,0,0,0.8);
    }

    /* Progress Summary Panel */
    .vw-progress-summary {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 0.75rem;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 0.5rem;
        margin-left: 1rem;
    }

    .vw-progress-summary-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
    }

    .vw-progress-summary-bar {
        width: 48px;
        height: 4px;
        background: rgba(255,255,255,0.15);
        border-radius: 2px;
        overflow: hidden;
    }

    .vw-progress-summary-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s;
    }

    .vw-progress-summary-fill.voice {
        background: linear-gradient(90deg, #8b5cf6, #a855f7);
    }

    .vw-progress-summary-fill.video {
        background: linear-gradient(90deg, #06b6d4, #10b981);
    }

    .vw-progress-summary-fill.ready {
        background: linear-gradient(90deg, #10b981, #22c55e);
    }

    .vw-progress-summary-label {
        font-size: 0.55rem;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .vw-progress-summary-value {
        font-size: 0.65rem;
        font-weight: 600;
        color: white;
    }

    /* Quick Stats Panel */
    .vw-quick-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: rgba(139, 92, 246, 0.08);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.5rem;
        margin-left: 1rem;
    }

    .vw-stat-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-stat-icon {
        font-size: 0.85rem;
    }

    .vw-stat-value {
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
    }

    .vw-stat-label {
        font-size: 0.55rem;
        color: rgba(255,255,255,0.4);
    }
</style>

{{-- Script to ensure full-screen coverage --}}
<script>
    (function() {
        // Add body class when Animation Studio is active
        document.body.classList.add('vw-animation-fullscreen');

        // Function to aggressively hide all sidebars
        function hideAllSidebars() {
            // Target the exact sidebar class used in the theme
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.style.cssText = 'display: none !important; visibility: hidden !important; width: 0 !important; max-width: 0 !important; opacity: 0 !important;';
            }

            // Also hide other sidebar variations
            document.querySelectorAll('.sidebar, .main-sidebar, div.sidebar, aside.sidebar, .hide-scroll.sidebar, [class*="sidebar"]:not(.vw-scene-grid-panel)').forEach(function(el) {
                if (!el.closest('.vw-animation-studio')) {
                    el.style.cssText = 'display: none !important; visibility: hidden !important; width: 0 !important; max-width: 0 !important; opacity: 0 !important;';
                }
            });

            // Remove left margin from main content
            document.querySelectorAll('.main-content, .page-wrapper, .wrapper').forEach(function(el) {
                el.style.cssText = 'margin-left: 0 !important; padding-left: 0 !important;';
            });
        }

        // Run immediately
        hideAllSidebars();

        // Run again after a short delay to catch dynamically loaded content
        setTimeout(hideAllSidebars, 100);
        setTimeout(hideAllSidebars, 500);

        // Cleanup on page unload/navigation
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('element.removed', (el, component) => {
                if (el.classList && el.classList.contains('vw-animation-studio')) {
                    document.body.classList.remove('vw-animation-fullscreen');
                    document.querySelectorAll('.sidebar, .main-sidebar, [class*="sidebar"], aside').forEach(function(el) {
                        el.style.cssText = '';
                    });
                    document.querySelectorAll('.main-content, .page-wrapper, .wrapper').forEach(function(el) {
                        el.style.cssText = '';
                    });
                }
            });
        }
    })();
</script>

@if(empty($script['scenes']))
    <div class="vw-studio-alert">
        <span class="vw-studio-alert-icon">⚠️</span>
        <span class="vw-studio-alert-text">{{ __('Please generate a script first before using Animation Studio.') }}</span>
    </div>
@else
    @php
        $scriptScenes = $script['scenes'] ?? [];
        $animationScenes = $animation['scenes'] ?? [];
        $storyboardScenes = $storyboard['scenes'] ?? [];
        $totalScenes = count($scriptScenes);

        // Calculate stats
        $voiceoversReady = count(array_filter($animationScenes, fn($s) => !empty($s['voiceoverUrl'])));
        $animatedCount = count(array_filter($animationScenes, fn($s) => !empty($s['videoUrl'])));
        $stockVideoCount = count(array_filter($storyboardScenes, fn($s) => ($s['source'] ?? '') === 'stock-video' && !empty($s['videoUrl'])));

        // A scene is ready if it has a visual (image or video)
        // Voiceovers and animations are OPTIONAL - user can proceed with just images
        $readyScenes = 0;
        foreach ($scriptScenes as $idx => $scriptScene) {
            $animScene = $animationScenes[$idx] ?? [];
            $sbScene = $storyboardScenes[$idx] ?? [];

            // Scene has visual if it has: animated video, stock video, or image
            $hasVisual = !empty($animScene['videoUrl']) ||
                        (($sbScene['source'] ?? '') === 'stock-video' && !empty($sbScene['videoUrl'])) ||
                        !empty($sbScene['imageUrl']);

            // Scene is ready as long as it has ANY visual content
            // Voiceovers are optional - user can create video with just images + music
            if ($hasVisual) {
                $readyScenes++;
            }
        }

        $allScenesReady = $readyScenes >= $totalScenes;
        $selectedIndex = $animation['selectedSceneIndex'] ?? 0;
        $selectedScene = $scriptScenes[$selectedIndex] ?? null;
        $selectedAnimScene = $animationScenes[$selectedIndex] ?? [];
        $selectedStoryboardScene = $storyboardScenes[$selectedIndex] ?? [];
    @endphp

    <div class="vw-animation-studio">
        {{-- TOP HEADER BAR --}}
        <div class="vw-studio-header">
            <div class="vw-studio-brand">
                <div class="vw-studio-icon">🎬</div>
                <div>
                    <div class="vw-studio-title">{{ __('Animation Studio Pro') }}</div>
                    <div class="vw-studio-subtitle">{{ __('Generate voiceovers • Create animations') }}</div>
                </div>
            </div>

            {{-- Progress Pills --}}
            <div class="vw-studio-pills">
                <div class="vw-studio-pill voiceover {{ $voiceoversReady >= $totalScenes ? 'complete' : '' }}">
                    <span>🎙️</span>
                    <span class="pill-value">{{ $voiceoversReady }}/{{ $totalScenes }}</span>
                </div>
                <div class="vw-studio-pill animated">
                    <span>🎬</span>
                    <span class="pill-value">{{ $animatedCount }} {{ __('animated') }}</span>
                </div>
                <div class="vw-studio-pill ready">
                    <span>✓</span>
                    <span class="pill-value">{{ $readyScenes }} {{ __('ready') }}</span>
                </div>
            </div>

            {{-- Progress Summary Bars --}}
            @php
                $voicePercent = $totalScenes > 0 ? round(($voiceoversReady / $totalScenes) * 100) : 0;
                $videoPercent = $totalScenes > 0 ? round((($animatedCount + $stockVideoCount) / $totalScenes) * 100) : 0;
                $readyPercent = $totalScenes > 0 ? round(($readyScenes / $totalScenes) * 100) : 0;
            @endphp
            <div class="vw-progress-summary">
                <div class="vw-progress-summary-item">
                    <div class="vw-progress-summary-bar">
                        <div class="vw-progress-summary-fill voice" style="width: {{ $voicePercent }}%"></div>
                    </div>
                    <span class="vw-progress-summary-label">{{ __('Voice') }}</span>
                </div>
                <div class="vw-progress-summary-item">
                    <div class="vw-progress-summary-bar">
                        <div class="vw-progress-summary-fill video" style="width: {{ $videoPercent }}%"></div>
                    </div>
                    <span class="vw-progress-summary-label">{{ __('Video') }}</span>
                </div>
                <div class="vw-progress-summary-item">
                    <div class="vw-progress-summary-bar">
                        <div class="vw-progress-summary-fill ready" style="width: {{ $readyPercent }}%"></div>
                    </div>
                    <span class="vw-progress-summary-label">{{ __('Ready') }}</span>
                </div>
            </div>

            {{-- Header Actions --}}
            <div class="vw-studio-actions">
                <button type="button"
                        class="vw-studio-btn back"
                        wire:click="goToStep(4)">
                    <span>←</span> {{ __('Back') }}
                </button>
                <button type="button"
                        class="vw-studio-btn continue {{ $allScenesReady ? 'enabled' : '' }}"
                        wire:click="goToStep(6)"
                        {{ !$allScenesReady ? 'disabled' : '' }}>
                    {{ __('Continue') }} <span>→</span>
                </button>
            </div>
        </div>

        {{-- MAIN SPLIT-PANEL CONTENT --}}
        <div class="vw-studio-content">
            {{-- LEFT PANEL - Scene Grid --}}
            <div class="vw-scene-grid-panel">
                <div class="vw-scene-grid-header">
                    <div class="vw-scene-grid-title-row">
                        <div class="vw-scene-grid-title">
                            <span>{{ __('SCENES') }}</span>
                        </div>
                        {{-- View Mode Toggle Buttons --}}
                        <div class="vw-view-modes">
                            <button type="button" class="vw-view-mode-btn active" title="{{ __('Card view') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            </button>
                            <button type="button" class="vw-view-mode-btn" title="{{ __('List view') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="4" width="18" height="3" rx="1"/><rect x="3" y="10" width="18" height="3" rx="1"/><rect x="3" y="16" width="18" height="3" rx="1"/></svg>
                            </button>
                            <button type="button" class="vw-view-mode-btn" title="{{ __('Compact view') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="17" y="3" width="4" height="5" rx="1"/><rect x="3" y="10" width="5" height="5" rx="1"/><rect x="10" y="10" width="5" height="5" rx="1"/><rect x="17" y="10" width="4" height="5" rx="1"/><rect x="3" y="17" width="5" height="4" rx="1"/><rect x="10" y="17" width="5" height="4" rx="1"/><rect x="17" y="17" width="4" height="4" rx="1"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="vw-quick-actions">
                        <button type="button"
                                class="vw-quick-btn voice"
                                wire:click="$dispatch('generate-all-voiceovers')"
                                wire:loading.attr="disabled">
                            <span>🎙️</span> {{ __('All Voices') }}
                        </button>
                        <button type="button"
                                class="vw-quick-btn animate"
                                wire:click="$dispatch('animate-all-scenes')"
                                wire:loading.attr="disabled">
                            <span>🎬</span> {{ __('All Anim') }}
                        </button>
                    </div>
                </div>

                <div class="vw-scene-grid-hint">
                    💡 {{ __('Click a scene to edit • Shift+Click for multi-select') }}
                </div>

                {{-- Scrollable Scene List --}}
                <div class="vw-scene-list">
                    @foreach($scriptScenes as $index => $scene)
                        @php
                            $animScene = $animationScenes[$index] ?? [];
                            $sbScene = $storyboardScenes[$index] ?? [];
                            $isSelected = $selectedIndex === $index;
                            $hasVoiceover = !empty($animScene['voiceoverUrl']);
                            $hasAnimation = !empty($animScene['videoUrl']);
                            $hasImage = !empty($sbScene['imageUrl']);
                            $isStockVideo = ($sbScene['source'] ?? '') === 'stock-video' && !empty($sbScene['videoUrl']);
                            $isMusicOnlyScene = ($animScene['musicOnly'] ?? false) || (($scene['voiceover']['enabled'] ?? true) === false);
                            $isVoiceGenerating = ($animScene['voiceoverStatus'] ?? '') === 'generating';
                            $isAnimGenerating = ($animScene['animationStatus'] ?? '') === 'generating';
                            $hasVoiceError = ($animScene['voiceoverStatus'] ?? '') === 'error';
                            $hasAnimError = ($animScene['animationStatus'] ?? '') === 'error';
                            $isProcessing = $isVoiceGenerating || $isAnimGenerating;
                            $imageUrl = $sbScene['imageUrl'] ?? null;

                            // Calculate progress (more granular)
                            $progress = 0;
                            if ($hasImage) $progress += 25;
                            if ($hasVoiceover || $isMusicOnlyScene) $progress += 25;
                            if ($hasAnimation || $isStockVideo) $progress += 50;

                            // Ring calculation
                            $ringRadius = 26;
                            $ringCircumference = 2 * 3.14159 * $ringRadius;
                            $ringOffset = $ringCircumference - ($progress / 100) * $ringCircumference;
                            $ringColor = $progress === 100 ? '#10b981' : ($progress >= 50 ? '#06b6d4' : ($progress >= 25 ? '#fbbf24' : ($isProcessing ? '#8b5cf6' : 'rgba(255,255,255,0.2)')));
                        @endphp
                        <div class="vw-scene-card {{ $isSelected ? 'selected' : '' }} {{ $isProcessing ? 'processing' : '' }}"
                             wire:click="$set('animation.selectedSceneIndex', {{ $index }})">
                            {{-- Progress Ring with Thumbnail --}}
                            <div class="vw-progress-ring-container">
                                <svg class="vw-progress-ring" width="60" height="60">
                                    <circle class="vw-progress-ring-bg" cx="30" cy="30" r="{{ $ringRadius }}"/>
                                    <circle class="vw-progress-ring-fill"
                                            cx="30" cy="30" r="{{ $ringRadius }}"
                                            stroke="{{ $ringColor }}"
                                            stroke-dasharray="{{ $ringCircumference }}"
                                            stroke-dashoffset="{{ $ringOffset }}"/>
                                </svg>
                                <div class="vw-scene-thumb-inner">
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="Scene {{ $index + 1 }}">
                                    @else
                                        <div class="vw-scene-thumb-empty">🎬</div>
                                    @endif
                                </div>
                                <div class="vw-scene-number">{{ $index + 1 }}</div>
                            </div>

                            {{-- Scene Info (matching original wizard layout) --}}
                            <div class="vw-scene-info">
                                <div class="vw-scene-name">{{ __('Scene') }} {{ $index + 1 }}</div>
                                <div class="vw-scene-progress-text" style="color: {{ $progress === 100 ? '#10b981' : ($progress > 0 ? '#fbbf24' : 'rgba(255,255,255,0.4)') }};">{{ $progress }}% {{ __('complete') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Scene List Footer with Stats --}}
                <div class="vw-scene-grid-footer">
                    <div class="vw-footer-stat">
                        <span class="vw-footer-stat-icon">🎙️</span>
                        <span class="vw-footer-stat-text">{{ $voiceoversReady }}/{{ $totalScenes }} {{ __('voiceovers') }}</span>
                    </div>
                    <div class="vw-footer-stat">
                        <span class="vw-footer-stat-icon">🎬</span>
                        <span class="vw-footer-stat-text">{{ $animatedCount }}/{{ $totalScenes }} {{ __('animations') }}</span>
                    </div>
                </div>
            </div>

            {{-- RIGHT PANEL - Detail View --}}
            <div class="vw-detail-panel">
                <div class="vw-detail-content">
                    @if($selectedScene)
                        @php
                            $selectedImageUrl = $selectedStoryboardScene['imageUrl'] ?? null;
                            $selectedVideoUrl = $selectedAnimScene['videoUrl'] ?? null;
                            $selectedVoiceoverUrl = $selectedAnimScene['voiceoverUrl'] ?? null;
                            $selectedAnimStatus = $selectedAnimScene['animationStatus'] ?? null;
                            $selectedVoiceStatus = $selectedAnimScene['voiceoverStatus'] ?? null;
                            $isStockVideo = ($selectedStoryboardScene['source'] ?? '') === 'stock-video';
                        @endphp

                        {{-- Preview Header with Navigation --}}
                        @php
                            // Calculate total duration
                            $totalDuration = 0;
                            foreach ($scriptScenes as $s) {
                                $totalDuration += $s['duration'] ?? 8;
                            }
                            $totalMinutes = floor($totalDuration / 60);
                            $totalSeconds = $totalDuration % 60;
                            $durationFormatted = $totalMinutes > 0
                                ? sprintf('%d:%02d', $totalMinutes, $totalSeconds)
                                : sprintf('0:%02d', $totalSeconds);

                            // Current scene duration
                            $currentDuration = $selectedScene['duration'] ?? 8;
                            $hasPrevScene = $selectedIndex > 0;
                            $hasNextScene = $selectedIndex < ($totalScenes - 1);
                        @endphp
                        <div class="vw-preview-header">
                            <div class="vw-preview-title">
                                <span class="vw-preview-title-icon">🎬</span>
                                <span class="vw-preview-title-text">{{ __('SCENE PREVIEW') }}</span>
                                @if($selectedVideoUrl)
                                    <span class="vw-preview-badge animated">✓ {{ __('Animated') }}</span>
                                @elseif($isStockVideo)
                                    <span class="vw-preview-badge stock">📹 {{ __('Stock') }}</span>
                                @elseif($selectedImageUrl)
                                    <span class="vw-preview-badge ken-burns">{{ __('Ken Burns') }}</span>
                                @endif
                            </div>
                            <div class="vw-preview-tools">
                                {{-- Scene Navigation --}}
                                <div class="vw-scene-navigation">
                                    <button type="button"
                                            class="vw-nav-btn"
                                            wire:click="$set('animation.selectedSceneIndex', {{ max(0, $selectedIndex - 1) }})"
                                            {{ !$hasPrevScene ? 'disabled' : '' }}
                                            title="{{ __('Previous Scene') }} (←)">
                                        ←
                                    </button>
                                    <div class="vw-scene-indicator">
                                        <span class="vw-scene-indicator-current">{{ $selectedIndex + 1 }}</span>
                                        <span class="vw-scene-indicator-total">/ {{ $totalScenes }}</span>
                                    </div>
                                    <button type="button"
                                            class="vw-nav-btn"
                                            wire:click="$set('animation.selectedSceneIndex', {{ min($totalScenes - 1, $selectedIndex + 1) }})"
                                            {{ !$hasNextScene ? 'disabled' : '' }}
                                            title="{{ __('Next Scene') }} (→)">
                                        →
                                    </button>
                                </div>

                                {{-- Duration Display --}}
                                <div class="vw-duration-display" title="{{ __('Total video duration') }}">
                                    <span class="vw-duration-icon">⏱️</span>
                                    <span class="vw-duration-value">{{ $durationFormatted }}</span>
                                    <span class="vw-duration-label">{{ __('total') }}</span>
                                </div>

                                {{-- Scene Duration --}}
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); padding: 0.25rem 0.5rem; background: rgba(255,255,255,0.05); border-radius: 0.25rem;">
                                    {{ $currentDuration }}s
                                </div>
                            </div>
                        </div>

                        {{-- Main Preview Container --}}
                        <div class="vw-preview-container" id="preview-container-{{ $selectedIndex }}" x-data="videoPlayer{{ $selectedIndex }}()">
                            @if($selectedVideoUrl)
                                {{-- Enhanced Video Player --}}
                                <div class="vw-video-player" :class="{ 'controls-visible': showControls }"
                                     @mouseenter="showControls = true"
                                     @mouseleave="if(!isPlaying) showControls = true; else showControls = false">
                                    <video class="vw-preview-video"
                                           id="preview-video-{{ $selectedIndex }}"
                                           src="{{ $selectedVideoUrl }}"
                                           :loop="isLooping"
                                           x-ref="video"
                                           @timeupdate="updateProgress($event)"
                                           @loadedmetadata="duration = $event.target.duration"
                                           @ended="isPlaying = false; showControls = true"
                                           @play="isPlaying = true"
                                           @pause="isPlaying = false"></video>

                                    {{-- Center Play Button (when paused) --}}
                                    <div class="vw-center-play" x-show="!isPlaying" @click="togglePlay()" x-cloak>
                                        ▶
                                    </div>

                                    {{-- Fullscreen Exit Button --}}
                                    <button type="button" class="vw-fullscreen-exit" x-show="isFullscreen" @click="exitFullscreen()" x-cloak>
                                        <span>✕</span> {{ __('Exit Fullscreen') }}
                                    </button>

                                    {{-- Video Controls Overlay --}}
                                    <div class="vw-video-controls">
                                        {{-- Progress Bar --}}
                                        <div class="vw-progress-container" @click="seek($event)">
                                            <div class="vw-progress-bar" :style="'width:' + progressPercent + '%'"></div>
                                            <div class="vw-progress-handle" :style="'left:' + progressPercent + '%'"></div>
                                        </div>

                                        {{-- Controls Row --}}
                                        <div class="vw-controls-row">
                                            {{-- Play/Pause --}}
                                            <button type="button" class="vw-control-btn play" @click="togglePlay()">
                                                <span x-text="isPlaying ? '⏸' : '▶'"></span>
                                            </button>

                                            {{-- Skip Back/Forward --}}
                                            <button type="button" class="vw-control-btn" @click="skip(-5)" title="{{ __('Back 5s') }}">
                                                ⏪
                                            </button>
                                            <button type="button" class="vw-control-btn" @click="skip(5)" title="{{ __('Forward 5s') }}">
                                                ⏩
                                            </button>

                                            {{-- Time Display --}}
                                            <div class="vw-time-display">
                                                <span class="vw-time-current" x-text="formatTime(currentTime)">0:00</span>
                                                <span class="vw-time-separator">/</span>
                                                <span class="vw-time-total" x-text="formatTime(duration)">0:00</span>
                                            </div>

                                            {{-- Spacer --}}
                                            <div style="flex: 1;"></div>

                                            {{-- Volume Control --}}
                                            <div class="vw-volume-control">
                                                <button type="button" class="vw-action-btn" @click="toggleMute()" :title="isMuted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}'">
                                                    <span x-text="isMuted || volume === 0 ? '🔇' : (volume < 50 ? '🔉' : '🔊')"></span>
                                                </button>
                                                <div class="vw-volume-slider">
                                                    <input type="range" min="0" max="100" x-model="volume" @input="updateVolume()">
                                                </div>
                                            </div>

                                            {{-- Loop Toggle --}}
                                            <button type="button"
                                                    class="vw-action-btn"
                                                    :class="{ 'active': isLooping }"
                                                    @click="isLooping = !isLooping"
                                                    title="{{ __('Loop') }}">
                                                🔁
                                            </button>

                                            {{-- Playback Speed --}}
                                            <button type="button"
                                                    class="vw-action-btn"
                                                    @click="cycleSpeed()"
                                                    :title="'{{ __('Speed') }}: ' + playbackSpeed + 'x'">
                                                <span x-text="playbackSpeed + 'x'" style="font-size: 0.7rem; font-weight: 600;"></span>
                                            </button>

                                            {{-- Fullscreen --}}
                                            <button type="button"
                                                    class="vw-action-btn"
                                                    @click="toggleFullscreen()"
                                                    title="{{ __('Fullscreen') }}">
                                                ⛶
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="vw-preview-status-badge animated">✓ {{ __('ANIMATED') }}</div>
                            @elseif($selectedImageUrl)
                                {{-- Ken Burns Preview --}}
                                <div class="vw-ken-burns-preview">
                                    <img src="{{ $selectedImageUrl }}" alt="{{ $selectedScene['title'] ?? 'Scene' }}">
                                </div>
                                @if($selectedAnimStatus === 'generating')
                                    <div class="vw-preview-generating">
                                        <div class="vw-preview-generating-spinner"></div>
                                        <div class="vw-preview-generating-text">{{ __('Generating Animation...') }}</div>
                                        <div class="vw-preview-generating-hint">{{ __('This may take a moment') }}</div>
                                    </div>
                                @endif
                            @else
                                {{-- No Image --}}
                                <div class="vw-preview-empty">
                                    <div class="vw-preview-empty-content">
                                        <div class="vw-preview-empty-icon">🖼️</div>
                                        <div class="vw-preview-empty-text">{{ __('No storyboard image') }}</div>
                                        <div class="vw-preview-empty-hint">{{ __('Generate images in Step 4') }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- Scene Info Overlay --}}
                            @if($selectedImageUrl || $selectedVideoUrl)
                                <div class="vw-preview-info-overlay">
                                    <div class="vw-preview-info-row">
                                        <div>
                                            <div class="vw-preview-scene-title">{{ __('Scene') }} {{ $selectedIndex + 1 }}</div>
                                            <div class="vw-preview-scene-duration">{{ $selectedScene['duration'] ?? 8 }}s {{ __('duration') }}</div>
                                        </div>
                                        <div class="vw-preview-quick-actions">
                                            @if(!$selectedVideoUrl && $selectedImageUrl)
                                                <button type="button"
                                                        class="vw-preview-action-btn animate"
                                                        wire:click="$dispatch('animate-scene', { sceneIndex: {{ $selectedIndex }} })">
                                                    🎬 {{ __('Animate') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Mini Timeline --}}
                        <div class="vw-mini-timeline">
                            @foreach($scriptScenes as $idx => $s)
                                @php
                                    $sb = $storyboardScenes[$idx] ?? [];
                                    $imgUrl = $sb['imageUrl'] ?? null;
                                @endphp
                                <div class="vw-mini-timeline-item {{ $selectedIndex === $idx ? 'active' : '' }}"
                                     wire:click="$set('animation.selectedSceneIndex', {{ $idx }})">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="Scene {{ $idx + 1 }}">
                                    @else
                                        <div class="vw-mini-timeline-empty">{{ $idx + 1 }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Voiceover Pro Section --}}
                        @php
                            $isMusicOnly = $selectedAnimScene['musicOnly'] ?? false;
                            $selectedVoice = $animation['voiceover']['voice'] ?? 'nova';
                            $voiceSpeed = $animation['voiceover']['speed'] ?? 1.0;
                            $voices = [
                                'alloy' => ['icon' => '🎭', 'name' => 'Alloy', 'desc' => 'Neutral'],
                                'echo' => ['icon' => '🎤', 'name' => 'Echo', 'desc' => 'Male'],
                                'fable' => ['icon' => '📖', 'name' => 'Fable', 'desc' => 'Story'],
                                'onyx' => ['icon' => '🎸', 'name' => 'Onyx', 'desc' => 'Deep'],
                                'nova' => ['icon' => '✨', 'name' => 'Nova', 'desc' => 'Female'],
                                'shimmer' => ['icon' => '💫', 'name' => 'Shimmer', 'desc' => 'Bright'],
                            ];
                        @endphp
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.05)); border-color: rgba(139, 92, 246, 0.2);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎙️</span>
                                    <span class="vw-section-title-text">{{ __('Voiceover Pro') }}</span>
                                </div>
                                {{-- Music Only Toggle --}}
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.6);">🎵 {{ __('Music Only') }}</span>
                                    <div style="position: relative; width: 36px; height: 20px;">
                                        <input type="checkbox"
                                               wire:click="toggleSceneMusicOnly({{ $selectedIndex }})"
                                               {{ $isMusicOnly ? 'checked' : '' }}
                                               style="opacity: 0; width: 0; height: 0;">
                                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $isMusicOnly ? '#8b5cf6' : 'rgba(255,255,255,0.2)' }}; transition: 0.2s; border-radius: 20px;">
                                            <span style="position: absolute; height: 16px; width: 16px; left: {{ $isMusicOnly ? '18px' : '2px' }}; bottom: 2px; background-color: white; transition: 0.2s; border-radius: 50%;"></span>
                                        </span>
                                    </div>
                                </label>
                            </div>

                            @if($isMusicOnly)
                                {{-- Music Only Mode --}}
                                <div style="text-align: center; padding: 1.5rem; color: rgba(255,255,255,0.6);">
                                    <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">♫</div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: white; margin-bottom: 0.25rem;">{{ __('Cinematic Music Scene') }}</div>
                                    <div style="font-size: 0.75rem;">{{ __('No voiceover - relax and let images tell the story') }}</div>
                                </div>
                            @elseif($selectedVoiceoverUrl)
                                {{-- Has Voiceover --}}
                                <div style="padding: 0.5rem 0;">
                                    <audio controls style="width: 100%; height: 40px; margin-bottom: 0.75rem;">
                                        <source src="{{ $selectedVoiceoverUrl }}" type="audio/mpeg">
                                    </audio>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="button"
                                                style="flex: 1; padding: 0.5rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.4rem; color: #a78bfa; font-size: 0.75rem; cursor: pointer;"
                                                wire:click="$dispatch('regenerate-voiceover', { sceneIndex: {{ $selectedIndex }} })">
                                            🔄 {{ __('Regenerate') }}
                                        </button>
                                        <button type="button"
                                                style="padding: 0.5rem 0.75rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.4rem; color: #f87171; font-size: 0.75rem; cursor: pointer;"
                                                wire:click="removeVoiceover({{ $selectedIndex }})">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            @elseif($selectedVoiceStatus === 'generating')
                                {{-- Generating --}}
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 1.5rem;">
                                    <div style="width: 24px; height: 24px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                                    <span style="color: rgba(255,255,255,0.7);">{{ __('Generating voiceover...') }}</span>
                                </div>
                            @else
                                {{-- No Voiceover - Show Voice Selection --}}
                                <div style="padding: 0.5rem 0;">
                                    {{-- Voice Grid --}}
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.35rem; margin-bottom: 0.75rem;">
                                        @foreach($voices as $voiceId => $voice)
                                            <button type="button"
                                                    wire:click="$set('animation.voiceover.voice', '{{ $voiceId }}')"
                                                    style="padding: 0.4rem; border-radius: 0.35rem; border: 1px solid {{ $selectedVoice === $voiceId ? '#8b5cf6' : 'rgba(255,255,255,0.1)' }}; background: {{ $selectedVoice === $voiceId ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.03)' }}; color: {{ $selectedVoice === $voiceId ? 'white' : 'rgba(255,255,255,0.6)' }}; font-size: 0.65rem; cursor: pointer; text-align: center;">
                                                <div style="font-size: 0.9rem;">{{ $voice['icon'] }}</div>
                                                <div style="font-weight: 600;">{{ $voice['name'] }}</div>
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Speed Slider --}}
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">⚡</span>
                                        <input type="range" wire:model.live="animation.voiceover.speed" min="0.5" max="2.0" step="0.1"
                                               style="flex: 1; height: 4px; accent-color: #8b5cf6;">
                                        <span style="font-size: 0.7rem; color: #a78bfa; font-weight: 600; min-width: 35px;">{{ number_format($voiceSpeed, 1) }}x</span>
                                    </div>

                                    {{-- Generate Button --}}
                                    <button type="button"
                                            style="width: 100%; padding: 0.6rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;"
                                            wire:click="$dispatch('generate-voiceover', { sceneIndex: {{ $selectedIndex }}, sceneId: '{{ $selectedScene['id'] ?? '' }}' })">
                                        🎙️ {{ __('Generate Voiceover') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Animation Style Gallery --}}
                        @php
                            $currentAnimationType = $selectedAnimScene['animationType'] ?? 'ken_burns';
                            $animationStyles = [
                                'ken_burns' => ['icon' => '🎬', 'name' => 'Ken Burns', 'desc' => 'Smooth zoom & pan', 'color' => '#06b6d4'],
                                'talking_head' => ['icon' => '🗣️', 'name' => 'Talking Head', 'desc' => 'Subtle movement', 'color' => '#8b5cf6'],
                                'static' => ['icon' => '🖼️', 'name' => 'Static', 'desc' => 'No animation', 'color' => '#6b7280'],
                            ];
                            // AI Suggestion based on scene content
                            $suggestedStyle = null;
                            $suggestionReason = '';
                            $narration = $selectedScene['narration'] ?? '';
                            if (strlen($narration) > 100) {
                                $suggestedStyle = 'talking_head';
                                $suggestionReason = 'Long narration detected - talking head works best';
                            } elseif (str_contains(strtolower($selectedScene['visualDescription'] ?? ''), 'landscape') || str_contains(strtolower($selectedScene['visualDescription'] ?? ''), 'aerial')) {
                                $suggestedStyle = 'ken_burns';
                                $suggestionReason = 'Landscape/aerial shot - Ken Burns adds cinematic feel';
                            }
                        @endphp
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(16, 185, 129, 0.05)); border-color: rgba(6, 182, 212, 0.2);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎬</span>
                                    <span class="vw-section-title-text">{{ __('Animation Style') }}</span>
                                </div>
                            </div>

                            {{-- AI Suggestion Banner --}}
                            @if($suggestedStyle && $suggestedStyle !== $currentAnimationType)
                                <div wire:click="setSceneAnimationType({{ $selectedIndex }}, '{{ $suggestedStyle }}')"
                                     style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: linear-gradient(135deg, rgba(251,191,36,0.15), rgba(245,158,11,0.1)); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.5rem; margin-bottom: 0.75rem; cursor: pointer;">
                                    <span style="font-size: 1rem;">💡</span>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.7rem; color: #fbbf24; font-weight: 600;">{{ __('AI Suggestion') }}</div>
                                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.6);">{{ $suggestionReason }}</div>
                                    </div>
                                    <span style="font-size: 0.65rem; color: #fbbf24; background: rgba(251,191,36,0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem;">{{ __('Apply') }}</span>
                                </div>
                            @endif

                            {{-- Style Cards Grid --}}
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem;">
                                @foreach($animationStyles as $styleId => $style)
                                    @php
                                        $isSelected = $currentAnimationType === $styleId;
                                        $isSuggested = $suggestedStyle === $styleId && !$isSelected;
                                    @endphp
                                    <div wire:click="setSceneAnimationType({{ $selectedIndex }}, '{{ $styleId }}')"
                                         style="position: relative; padding: 0.6rem; background: {{ $isSelected ? 'rgba(6,182,212,0.2)' : 'rgba(255,255,255,0.03)' }}; border: 2px solid {{ $isSelected ? $style['color'] : 'rgba(255,255,255,0.08)' }}; border-radius: 0.5rem; cursor: pointer; text-align: center; transition: all 0.15s;">
                                        @if($isSuggested)
                                            <div style="position: absolute; top: -6px; right: -6px; width: 18px; height: 18px; background: #fbbf24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.55rem;">💡</div>
                                        @endif
                                        <div style="font-size: 1.25rem; margin-bottom: 0.25rem;">{{ $style['icon'] }}</div>
                                        <div style="font-size: 0.7rem; font-weight: 600; color: {{ $isSelected ? $style['color'] : 'white' }};">{{ $style['name'] }}</div>
                                        <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4);">{{ $style['desc'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Camera Movement Selection --}}
                            @php
                                $cameraMovements = $selectedAnimScene['cameraMovements'] ?? ($selectedScene['cameraMovement'] ?? []);
                                $cameraOptions = [
                                    ['id' => 'Pan left', 'icon' => '⬅️', 'label' => 'Pan L'],
                                    ['id' => 'Pan right', 'icon' => '➡️', 'label' => 'Pan R'],
                                    ['id' => 'Zoom in', 'icon' => '🔍', 'label' => 'Zoom+'],
                                    ['id' => 'Zoom out', 'icon' => '🔎', 'label' => 'Zoom-'],
                                    ['id' => 'Push in', 'icon' => '⏩', 'label' => 'Push'],
                                    ['id' => 'Pull out', 'icon' => '⏪', 'label' => 'Pull'],
                                    ['id' => 'Tilt up', 'icon' => '⬆️', 'label' => 'Tilt↑'],
                                    ['id' => 'Tilt down', 'icon' => '⬇️', 'label' => 'Tilt↓'],
                                    ['id' => 'Tracking shot', 'icon' => '🎯', 'label' => 'Track'],
                                    ['id' => 'Static shot', 'icon' => '🔲', 'label' => 'Static'],
                                ];
                                $hasScriptMovements = !empty($selectedScene['cameraMovement']) && empty($selectedAnimScene['cameraMovements']);
                            @endphp
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.08);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <span style="font-size: 0.85rem;">🎥</span>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: white;">{{ __('Camera Movement') }}</span>
                                        <span style="font-size: 0.55rem; padding: 0.15rem 0.35rem; background: rgba(6,182,212,0.2); color: #06b6d4; border-radius: 0.25rem;">{{ __('Minimax AI') }}</span>
                                        @if($hasScriptMovements)
                                            <span style="font-size: 0.55rem; padding: 0.15rem 0.35rem; background: rgba(139,92,246,0.2); color: #a78bfa; border-radius: 0.25rem;">{{ __('AI Suggested') }}</span>
                                        @endif
                                    </div>
                                    @if(count($cameraMovements) > 0)
                                        <span style="font-size: 0.6rem; color: rgba(255,255,255,0.4);">{{ count($cameraMovements) }}/3 {{ __('selected') }}</span>
                                    @endif
                                </div>

                                {{-- Camera Movement Buttons --}}
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @foreach($cameraOptions as $cam)
                                        @php
                                            $isMovementSelected = in_array($cam['id'], $cameraMovements);
                                            $canSelect = $isMovementSelected || count($cameraMovements) < 3;
                                        @endphp
                                        <button type="button"
                                                wire:click="toggleCameraMovement({{ $selectedIndex }}, '{{ $cam['id'] }}')"
                                                {{ !$canSelect ? 'disabled' : '' }}
                                                style="padding: 0.35rem 0.5rem; border-radius: 0.35rem; border: 1px solid {{ $isMovementSelected ? '#06b6d4' : 'rgba(255,255,255,0.15)' }}; background: {{ $isMovementSelected ? 'rgba(6,182,212,0.2)' : 'transparent' }}; color: {{ $isMovementSelected ? '#06b6d4' : ($canSelect ? 'rgba(255,255,255,0.6)' : 'rgba(255,255,255,0.3)') }}; font-size: 0.6rem; cursor: {{ $canSelect ? 'pointer' : 'not-allowed' }}; display: flex; align-items: center; gap: 0.2rem; transition: all 0.15s;">
                                            <span style="font-size: 0.7rem;">{{ $cam['icon'] }}</span>
                                            <span>{{ $cam['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Selected Movements Preview --}}
                                @if(count($cameraMovements) > 0)
                                    <div style="margin-top: 0.5rem; padding: 0.4rem 0.6rem; background: rgba(6,182,212,0.1); border-radius: 0.35rem; font-size: 0.65rem; color: #06b6d4;">
                                        📽️ [{{ implode(', ', $cameraMovements) }}]
                                    </div>
                                @endif
                            </div>

                            {{-- Generate AI Video Button --}}
                            @php
                                $isAnimating = $selectedAnimStatus === 'generating' || $selectedAnimStatus === 'processing';
                                $hasImage = !empty($selectedImageUrl);
                                $canAnimate = $hasImage && !$isAnimating;
                                $videoModel = $content['videoModel'] ?? ['model' => 'hailuo-2.3', 'duration' => '10s', 'resolution' => '768p'];
                                $modelName = match($videoModel['model'] ?? 'hailuo-2.3') {
                                    'hailuo-2.3-fast' => 'Hailuo 2.3 Fast',
                                    'hailuo-02' => 'Hailuo 02',
                                    default => 'Hailuo 2.3',
                                };
                            @endphp
                            <div style="margin-top: 0.75rem;">
                                <button type="button"
                                        wire:click="$dispatch('animate-scene', { sceneIndex: {{ $selectedIndex }} })"
                                        {{ !$canAnimate ? 'disabled' : '' }}
                                        style="width: 100%; padding: 0.65rem; border-radius: 0.5rem; border: none; background: {{ $canAnimate ? 'linear-gradient(135deg, #06b6d4, #10b981)' : 'rgba(255,255,255,0.1)' }}; color: {{ $canAnimate ? 'white' : 'rgba(255,255,255,0.4)' }}; font-size: 0.8rem; font-weight: 600; cursor: {{ $canAnimate ? 'pointer' : 'not-allowed' }}; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    @if($isAnimating)
                                        <span style="animation: vw-spin 1s linear infinite;">⏳</span> {{ __('Generating AI Video...') }}
                                    @elseif(!$hasImage)
                                        <span>🔒</span> {{ __('Generate image first') }}
                                    @else
                                        <span>🎬</span> {{ __('Generate') }} {{ $videoModel['duration'] ?? '10s' }} {{ __('AI Video') }}
                                    @endif
                                </button>
                                <div style="text-align: center; margin-top: 0.35rem; font-size: 0.6rem; color: rgba(255,255,255,0.4);">
                                    {{ __('Using') }} {{ $modelName }} @ {{ $videoModel['resolution'] ?? '768p' }}
                                </div>
                            </div>
                        </div>

                        {{-- Audio & Music Section --}}
                        @php
                            $musicEnabled = $assembly['music']['enabled'] ?? false;
                            $musicVolume = $assembly['music']['volume'] ?? 30;
                            $voiceVolume = $assembly['audioMix']['voiceVolume'] ?? 100;
                            $duckingEnabled = ($assembly['audioMix']['ducking'] ?? true) !== false;
                            $genreId = $content['genre'] ?? null;
                        @endphp
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.05)); border-color: rgba(245, 158, 11, 0.25);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎵</span>
                                    <span class="vw-section-title-text">{{ __('Audio & Music') }}</span>
                                    <span class="vw-section-badge auto">{{ __('AUTO') }}</span>
                                </div>
                                <button type="button"
                                        wire:click="goToStep(6)"
                                        style="font-size: 0.6rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: rgba(255,255,255,0.6); cursor: pointer;">
                                    {{ __('Full Editor') }} →
                                </button>
                            </div>

                            {{-- Genre Info --}}
                            @if($genreId)
                                <div style="background: rgba(0,0,0,0.2); border-radius: 0.4rem; padding: 0.6rem; margin-bottom: 0.75rem;">
                                    <div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                                        <span style="font-size: 0.65rem; color: #f59e0b;">✨</span>
                                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __('Based on your') }} <strong style="color: #f59e0b;">{{ str_replace('-', ' ', $genreId) }}</strong> {{ __('genre') }}:</span>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                        <span style="font-size: 0.6rem; padding: 0.2rem 0.4rem; background: rgba(245,158,11,0.2); border-radius: 0.2rem; color: #fbbf24;">🎭 {{ __('atmospheric') }}</span>
                                        <span style="font-size: 0.6rem; padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.2); border-radius: 0.2rem; color: #a78bfa;">🎵 {{ __('ambient') }}</span>
                                        <span style="font-size: 0.6rem; padding: 0.2rem 0.4rem; background: rgba(6,182,212,0.2); border-radius: 0.2rem; color: #22d3ee;">{{ __('cinematic') }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Quick Controls --}}
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                {{-- Music Toggle & Volume --}}
                                <div style="background: rgba(0,0,0,0.15); border-radius: 0.4rem; padding: 0.6rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">🎵 {{ __('Music') }}</span>
                                        <label style="position: relative; display: inline-block; width: 32px; height: 18px;">
                                            <input type="checkbox"
                                                   wire:click="$set('assembly.music.enabled', {{ $musicEnabled ? 'false' : 'true' }})"
                                                   {{ $musicEnabled ? 'checked' : '' }}
                                                   style="opacity: 0; width: 0; height: 0;">
                                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $musicEnabled ? '#8b5cf6' : 'rgba(255,255,255,0.2)' }}; transition: 0.2s; border-radius: 18px;">
                                                <span style="position: absolute; height: 14px; width: 14px; left: {{ $musicEnabled ? '15px' : '2px' }}; bottom: 2px; background-color: white; transition: 0.2s; border-radius: 50%;"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <input type="range" wire:model.live="assembly.music.volume" min="0" max="100"
                                           style="width: 100%; height: 4px; cursor: pointer; accent-color: #8b5cf6;" {{ !$musicEnabled ? 'disabled' : '' }}>
                                    <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4); text-align: right; margin-top: 0.2rem;">{{ $musicVolume }}%</div>
                                </div>

                                {{-- Voice Volume --}}
                                <div style="background: rgba(0,0,0,0.15); border-radius: 0.4rem; padding: 0.6rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">🎙️ {{ __('Voice') }}</span>
                                        <span style="font-size: 0.6rem; color: #06b6d4;">{{ $voiceVolume }}%</span>
                                    </div>
                                    <input type="range" wire:model.live="assembly.audioMix.voiceVolume" min="0" max="100"
                                           style="width: 100%; height: 4px; cursor: pointer; accent-color: #06b6d4;">
                                </div>
                            </div>

                            {{-- Auto-Ducking Toggle --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.75rem; padding: 0.5rem; background: rgba(0,0,0,0.1); border-radius: 0.35rem;">
                                <div>
                                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __('Auto-Duck') }}</span>
                                    <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4);">{{ __('Lower music during voiceover') }}</div>
                                </div>
                                <label style="position: relative; display: inline-block; width: 32px; height: 18px;">
                                    <input type="checkbox"
                                           wire:click="$set('assembly.audioMix.ducking', {{ $duckingEnabled ? 'false' : 'true' }})"
                                           {{ $duckingEnabled ? 'checked' : '' }}
                                           style="opacity: 0; width: 0; height: 0;">
                                    <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $duckingEnabled ? '#10b981' : 'rgba(255,255,255,0.2)' }}; transition: 0.2s; border-radius: 18px;">
                                        <span style="position: absolute; height: 14px; width: 14px; left: {{ $duckingEnabled ? '15px' : '2px' }}; bottom: 2px; background-color: white; transition: 0.2s; border-radius: 50%;"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Keyboard Shortcuts Hint --}}
        <div class="vw-keyboard-hint" id="keyboard-hint">
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">←</span>
                <span class="vw-keyboard-key">→</span>
                <span>{{ __('Navigate') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">V</span>
                <span>{{ __('Voice') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">A</span>
                <span>{{ __('Animate') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">Space</span>
                <span>{{ __('Play') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">F</span>
                <span>{{ __('Fullscreen') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">L</span>
                <span>{{ __('Loop') }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span class="vw-keyboard-key">M</span>
                <span>{{ __('Mute') }}</span>
            </div>
            <button type="button"
                    onclick="document.getElementById('keyboard-hint').style.display='none'"
                    style="margin-left: 0.5rem; background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 0.7rem;">
                ✕
            </button>
        </div>
    </div>

    {{-- Alpine.js Video Player Component --}}
    <script>
        function videoPlayer{{ $selectedIndex }}() {
            return {
                isPlaying: false,
                isLooping: false,
                isMuted: false,
                isFullscreen: false,
                showControls: true,
                volume: 100,
                previousVolume: 100,
                currentTime: 0,
                duration: 0,
                progressPercent: 0,
                playbackSpeed: 1,
                speeds: [0.5, 0.75, 1, 1.25, 1.5, 2],

                init() {
                    // Auto-hide controls after 3 seconds of inactivity
                    let hideTimeout;
                    const container = this.$el;
                    container.addEventListener('mousemove', () => {
                        this.showControls = true;
                        clearTimeout(hideTimeout);
                        if (this.isPlaying) {
                            hideTimeout = setTimeout(() => {
                                this.showControls = false;
                            }, 3000);
                        }
                    });
                },

                togglePlay() {
                    const video = this.$refs.video;
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                },

                skip(seconds) {
                    const video = this.$refs.video;
                    video.currentTime = Math.max(0, Math.min(video.duration, video.currentTime + seconds));
                },

                seek(event) {
                    const video = this.$refs.video;
                    const rect = event.currentTarget.getBoundingClientRect();
                    const percent = (event.clientX - rect.left) / rect.width;
                    video.currentTime = percent * video.duration;
                },

                updateProgress(event) {
                    const video = event.target;
                    this.currentTime = video.currentTime;
                    this.progressPercent = (video.currentTime / video.duration) * 100;
                },

                toggleMute() {
                    const video = this.$refs.video;
                    if (this.isMuted) {
                        this.volume = this.previousVolume;
                        video.volume = this.previousVolume / 100;
                        this.isMuted = false;
                    } else {
                        this.previousVolume = this.volume;
                        this.volume = 0;
                        video.volume = 0;
                        this.isMuted = true;
                    }
                },

                updateVolume() {
                    const video = this.$refs.video;
                    video.volume = this.volume / 100;
                    this.isMuted = this.volume === 0;
                },

                cycleSpeed() {
                    const video = this.$refs.video;
                    const currentIndex = this.speeds.indexOf(this.playbackSpeed);
                    const nextIndex = (currentIndex + 1) % this.speeds.length;
                    this.playbackSpeed = this.speeds[nextIndex];
                    video.playbackRate = this.playbackSpeed;
                },

                toggleFullscreen() {
                    const container = document.getElementById('preview-container-{{ $selectedIndex }}');
                    if (!this.isFullscreen) {
                        container.classList.add('fullscreen');
                        this.isFullscreen = true;
                        // Try native fullscreen API
                        if (container.requestFullscreen) {
                            container.requestFullscreen();
                        }
                    } else {
                        this.exitFullscreen();
                    }
                },

                exitFullscreen() {
                    const container = document.getElementById('preview-container-{{ $selectedIndex }}');
                    container.classList.remove('fullscreen');
                    this.isFullscreen = false;
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {});
                    }
                },

                formatTime(seconds) {
                    if (!seconds || isNaN(seconds)) return '0:00';
                    const mins = Math.floor(seconds / 60);
                    const secs = Math.floor(seconds % 60);
                    return mins + ':' + (secs < 10 ? '0' : '') + secs;
                }
            };
        }

        // Listen for fullscreen change events
        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement) {
                document.querySelectorAll('.vw-preview-container.fullscreen').forEach(el => {
                    el.classList.remove('fullscreen');
                });
            }
        });

        // Escape key to exit fullscreen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.vw-preview-container.fullscreen').forEach(el => {
                    el.classList.remove('fullscreen');
                });
            }
        });
    </script>

    {{-- Keyboard Shortcuts Script --}}
    <script>
        document.addEventListener('keydown', function(e) {
            // Only handle if not in an input/textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            const totalScenes = {{ $totalScenes }};
            const currentIndex = {{ $selectedIndex }};

            switch(e.key) {
                case 'ArrowLeft':
                    if (currentIndex > 0) {
                        @this.set('animation.selectedSceneIndex', currentIndex - 1);
                    }
                    e.preventDefault();
                    break;
                case 'ArrowRight':
                    if (currentIndex < totalScenes - 1) {
                        @this.set('animation.selectedSceneIndex', currentIndex + 1);
                    }
                    e.preventDefault();
                    break;
                case 'v':
                case 'V':
                    // Generate voiceover for current scene
                    @this.dispatch('generate-voiceover', {
                        sceneIndex: currentIndex,
                        sceneId: '{{ $selectedScene['id'] ?? '' }}'
                    });
                    break;
                case 'a':
                case 'A':
                    // Animate current scene
                    @this.dispatch('animate-scene', { sceneIndex: currentIndex });
                    break;
                case ' ':
                    // Play/pause video preview
                    const video = document.querySelector('.vw-preview-video');
                    if (video) {
                        if (video.paused) {
                            video.play();
                        } else {
                            video.pause();
                        }
                        e.preventDefault();
                    }
                    break;
                case 'Home':
                    @this.set('animation.selectedSceneIndex', 0);
                    e.preventDefault();
                    break;
                case 'End':
                    @this.set('animation.selectedSceneIndex', totalScenes - 1);
                    e.preventDefault();
                    break;
                case 'f':
                case 'F':
                    // Toggle fullscreen
                    const container = document.querySelector('.vw-preview-container');
                    if (container) {
                        container.classList.toggle('fullscreen');
                        if (container.classList.contains('fullscreen')) {
                            if (container.requestFullscreen) {
                                container.requestFullscreen();
                            }
                        } else {
                            if (document.exitFullscreen) {
                                document.exitFullscreen().catch(() => {});
                            }
                        }
                        e.preventDefault();
                    }
                    break;
                case 'l':
                case 'L':
                    // Toggle loop
                    const vid = document.querySelector('.vw-preview-video');
                    if (vid) {
                        vid.loop = !vid.loop;
                    }
                    break;
                case 'm':
                case 'M':
                    // Toggle mute
                    const vidMute = document.querySelector('.vw-preview-video');
                    if (vidMute) {
                        vidMute.muted = !vidMute.muted;
                    }
                    break;
            }
        });
    </script>
@endif
