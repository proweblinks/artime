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
        width: 100vw;
        height: 100vh;
        background: linear-gradient(135deg, #0a0a14 0%, #141428 100%);
        z-index: 999999;  /* Above sidebar (10000) */
        display: flex;
        flex-direction: column;
        overflow: hidden;
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

    /* Main Content Area - NEW SIDEBAR LAYOUT */
    .vw-storyboard-main {
        flex: 1;
        display: flex;
        flex-direction: row;
        overflow: hidden;
    }

    /* ========================================
       NEW LAYOUT: Icon Rail + Sidebar + Workspace
       ======================================== */

    /* Icon Rail - Always visible, 48px */
    .vw-icon-rail {
        width: 48px;
        min-width: 48px;
        background: rgba(10, 10, 20, 0.98);
        border-right: 1px solid rgba(139, 92, 246, 0.15);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 0;
        gap: 0.25rem;
        z-index: 10;
    }

    .vw-icon-rail-btn {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: all 0.15s ease;
        position: relative;
    }

    .vw-icon-rail-btn:hover {
        background: rgba(139, 92, 246, 0.15);
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-icon-rail-btn.active {
        background: rgba(139, 92, 246, 0.25);
        color: #a78bfa;
    }

    .vw-icon-rail-btn.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 20px;
        background: #8b5cf6;
        border-radius: 0 2px 2px 0;
    }

    .vw-icon-rail-divider {
        width: 24px;
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0.5rem 0;
    }

    .vw-icon-rail-spacer {
        flex: 1;
    }

    /* Settings Sidebar - Collapsible & Resizable */
    .vw-settings-sidebar {
        width: 320px;
        min-width: 240px;
        max-width: 500px;
        background: rgba(15, 15, 28, 0.98);
        border-right: 1px solid rgba(139, 92, 246, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: opacity 0.2s ease;
        position: relative;
    }

    .vw-settings-sidebar:not(.resizing) {
        transition: width 0.25s ease, min-width 0.25s ease, opacity 0.2s ease;
    }

    .vw-settings-sidebar.collapsed {
        width: 0 !important;
        min-width: 0 !important;
        opacity: 0;
        pointer-events: none;
    }

    /* Resize Handle */
    .vw-sidebar-resize-handle {
        position: absolute;
        top: 0;
        right: -4px;
        width: 8px;
        height: 100%;
        cursor: col-resize;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-sidebar-resize-handle::before {
        content: '';
        width: 3px;
        height: 40px;
        background: rgba(139, 92, 246, 0.3);
        border-radius: 2px;
        opacity: 0;
        transition: opacity 0.2s ease, background 0.2s ease;
    }

    .vw-sidebar-resize-handle:hover::before,
    .vw-settings-sidebar.resizing .vw-sidebar-resize-handle::before {
        opacity: 1;
        background: rgba(139, 92, 246, 0.6);
    }

    .vw-settings-sidebar.resizing .vw-sidebar-resize-handle::before {
        background: #8b5cf6;
    }

    .vw-sidebar-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-sidebar-title {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        font-weight: 600;
    }

    .vw-sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
    }

    /* Sidebar Section */
    .vw-sidebar-section {
        margin-bottom: 0.75rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .vw-sidebar-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.6rem 0.75rem;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .vw-sidebar-section-header:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .vw-sidebar-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    .vw-sidebar-section-title .icon {
        font-size: 0.9rem;
    }

    .vw-sidebar-section-chevron {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        transition: transform 0.2s ease;
    }

    .vw-sidebar-section.open .vw-sidebar-section-chevron {
        transform: rotate(180deg);
    }

    .vw-sidebar-section-body {
        padding: 0 0.75rem 0.75rem;
        display: none;
    }

    .vw-sidebar-section.open .vw-sidebar-section-body {
        display: block;
    }

    /* Quick Stats in Sidebar */
    .vw-sidebar-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .vw-sidebar-stat {
        background: rgba(139, 92, 246, 0.08);
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 0.5rem;
        padding: 0.6rem;
        text-align: center;
    }

    .vw-sidebar-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #a78bfa;
        line-height: 1;
    }

    .vw-sidebar-stat-label {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        margin-top: 0.25rem;
    }

    /* Model Selector in Sidebar */
    .vw-sidebar-models {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .vw-sidebar-model-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-sidebar-model-btn:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-sidebar-model-btn.selected {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-sidebar-model-btn.selected::before {
        content: '✓';
        margin-right: 0.5rem;
        color: #8b5cf6;
    }

    .vw-sidebar-model-name {
        font-size: 0.75rem;
        color: white;
        font-weight: 500;
    }

    .vw-sidebar-model-cost {
        font-size: 0.65rem;
        color: #fbbf24;
        background: rgba(251, 191, 36, 0.15);
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
    }

    /* Toggle Switch in Sidebar */
    .vw-sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-sidebar-toggle-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-sidebar-toggle-switch {
        position: relative;
        width: 36px;
        height: 20px;
        cursor: pointer;
    }

    .vw-sidebar-toggle-track {
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        transition: background 0.2s ease;
    }

    .vw-sidebar-toggle-switch.active .vw-sidebar-toggle-track {
        background: rgba(139, 92, 246, 0.5);
    }

    .vw-sidebar-toggle-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: white;
        border-radius: 50%;
        transition: left 0.2s ease, background 0.2s ease;
    }

    .vw-sidebar-toggle-switch.active .vw-sidebar-toggle-thumb {
        left: 18px;
        background: #8b5cf6;
    }

    /* Visual Style in Sidebar */
    .vw-sidebar-style-preview {
        padding: 0.6rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.1));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.4rem;
        margin-bottom: 0.75rem;
    }

    .vw-sidebar-style-active {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.7rem;
        color: #c4b5fd;
        margin-bottom: 0.35rem;
    }

    .vw-sidebar-style-desc {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.4;
    }

    .vw-sidebar-style-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .vw-sidebar-style-select {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .vw-sidebar-style-select label {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        text-transform: uppercase;
    }

    .vw-sidebar-style-select select {
        padding: 0.4rem 0.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.3rem;
        color: white;
        font-size: 0.7rem;
        cursor: pointer;
    }

    /* Scene Memory in Sidebar */
    .vw-sidebar-bible-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .vw-sidebar-bible-card {
        position: relative;
        aspect-ratio: 1;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.5rem;
        cursor: pointer;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .vw-sidebar-bible-card:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
    }

    .vw-sidebar-bible-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-sidebar-bible-card-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.1));
        font-size: 2rem;
    }

    .vw-sidebar-bible-card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 0.4rem 0.5rem;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
    }

    .vw-sidebar-bible-card-name {
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vw-sidebar-bible-card-tag {
        font-size: 0.55rem;
        color: rgba(167, 139, 250, 0.9);
    }

    .vw-sidebar-bible-card-add {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-style: dashed;
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-sidebar-bible-card-add:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-sidebar-bible-card-add-icon {
        font-size: 1.5rem;
        color: rgba(139, 92, 246, 0.6);
        margin-bottom: 0.25rem;
    }

    .vw-sidebar-bible-card-add-text {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-sidebar-bible-icon {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }

    .vw-sidebar-bible-label {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-sidebar-bible-count {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Collapse Button */
    .vw-sidebar-collapse-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem;
        margin: 0.5rem 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.4rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-sidebar-collapse-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.8);
    }

    /* Main Workspace */
    .vw-workspace {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: rgba(10, 10, 18, 0.5);
    }

    .vw-workspace-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-workspace-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    /* Old Settings Panel - Hidden in new layout */
    .vw-storyboard-settings-panel {
        display: none;
    }

    /* Scene Grid Container */
    .vw-storyboard-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem;
    }

    /* Legacy support - keep old card styles for nested elements */
    .vw-storyboard-fullscreen .vw-storyboard-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .vw-storyboard-fullscreen .vw-storyboard-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
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

    /* Scene Memory - Modern Tabbed Design */
    .vw-memory-tabs {
        display: flex;
        position: relative;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.75rem;
        padding: 0.25rem;
        margin-bottom: 0.75rem;
    }

    .vw-memory-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.6rem 0.5rem;
        background: transparent;
        border: none;
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
    }

    .vw-memory-tab:hover {
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-memory-tab.active {
        color: white;
    }

    .vw-memory-tab-icon {
        font-size: 1rem;
    }

    .vw-memory-tab-label {
        font-weight: 600;
    }

    .vw-memory-tab-count {
        background: rgba(139, 92, 246, 0.3);
        padding: 0.1rem 0.4rem;
        border-radius: 0.75rem;
        font-size: 0.65rem;
        font-weight: 700;
        color: #c4b5fd;
        min-width: 1.25rem;
        text-align: center;
    }

    .vw-memory-tab.active .vw-memory-tab-count {
        background: rgba(139, 92, 246, 0.6);
        color: white;
    }

    .vw-memory-tab-indicator {
        position: absolute;
        bottom: 0.25rem;
        height: calc(100% - 0.5rem);
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.25), rgba(6, 182, 212, 0.2));
        border-radius: 0.5rem;
        border: 1px solid rgba(139, 92, 246, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 0;
    }

    .vw-memory-panel {
        min-height: 120px;
    }

    .vw-memory-panel-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 0.5rem;
    }

    .vw-memory-add-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.75rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.15));
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.5rem;
        color: #a78bfa;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-memory-add-btn:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.25));
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
    }

    .vw-memory-add-btn span {
        font-size: 0.9rem;
        font-weight: 700;
    }

    .vw-memory-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .vw-memory-card {
        position: relative;
        aspect-ratio: 3/4;
        border-radius: 0.75rem;
        overflow: hidden;
        cursor: pointer;
        background: linear-gradient(135deg, rgba(30, 30, 45, 1), rgba(20, 20, 35, 1));
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .vw-memory-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 0.75rem;
        padding: 1px;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.2));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .vw-memory-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 40px rgba(139, 92, 246, 0.25), 0 4px 20px rgba(0, 0, 0, 0.4);
    }

    .vw-memory-card:hover::before {
        opacity: 1;
    }

    .vw-memory-card-image {
        position: absolute;
        inset: 0;
        overflow: hidden;
    }

    .vw-memory-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: top center;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), filter 0.3s ease;
    }

    .vw-memory-card:hover .vw-memory-card-image img {
        transform: scale(1.08);
        filter: brightness(1.1);
    }

    .vw-memory-card-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        opacity: 0.3;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.05));
    }

    /* Floating name overlay */
    .vw-memory-card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 2.5rem 0.75rem 0.75rem;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 40%, transparent 100%);
        transform: translateY(0);
        transition: all 0.3s ease;
    }

    .vw-memory-card:hover .vw-memory-card-overlay {
        background: linear-gradient(to top, rgba(139, 92, 246, 0.9) 0%, rgba(139, 92, 246, 0.5) 30%, transparent 100%);
    }

    .vw-memory-card-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: white;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: 0.02em;
    }

    .vw-memory-card-role {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 0.15rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    /* Add card with dashed border */
    .vw-memory-card-add {
        background: transparent;
        border: 2px dashed rgba(139, 92, 246, 0.3);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: none;
    }

    .vw-memory-card-add::before {
        display: none;
    }

    .vw-memory-card-add:hover {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(139, 92, 246, 0.08);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
    }

    .vw-memory-card-add-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #a78bfa;
        transition: all 0.3s ease;
    }

    .vw-memory-card-add:hover .vw-memory-card-add-icon {
        transform: scale(1.1) rotate(90deg);
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.5), rgba(6, 182, 212, 0.4));
    }

    .vw-memory-card-add-text {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 500;
    }

    .vw-memory-empty {
        grid-column: span 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        color: rgba(255, 255, 255, 0.4);
        gap: 0.75rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(6, 182, 212, 0.03));
        border-radius: 0.75rem;
        border: 1px dashed rgba(255, 255, 255, 0.1);
    }

    .vw-memory-empty-icon {
        font-size: 2.5rem;
        opacity: 0.4;
    }

    .vw-memory-empty-text {
        font-size: 0.8rem;
        font-weight: 500;
    }

    .vw-memory-dna-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.75rem;
        padding: 0.6rem 0.75rem;
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(139, 92, 246, 0.08));
        border: 1px solid rgba(6, 182, 212, 0.2);
        border-radius: 0.5rem;
    }

    .vw-memory-dna-info {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-memory-dna-icon {
        font-size: 1rem;
    }

    .vw-memory-dna-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-memory-dna-count {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        background: rgba(6, 182, 212, 0.15);
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
    }

    .vw-memory-dna-btn {
        padding: 0.3rem 0.6rem;
        background: rgba(6, 182, 212, 0.2);
        border: 1px solid rgba(6, 182, 212, 0.4);
        border-radius: 0.35rem;
        color: white;
        font-size: 0.65rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-memory-dna-btn:hover {
        background: rgba(6, 182, 212, 0.3);
        transform: translateY(-1px);
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

    /* Storyboard Grid - 3 columns for better organization */
    .vw-storyboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        /* Anti-jitter: prevent layout recalculations during updates */
        contain: layout;
    }

    @media (max-width: 1400px) {
        .vw-storyboard-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 900px) {
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
        transition: border-color 0.2s, background-color 0.2s;
        /* Anti-jitter: fixed dimensions prevent layout shift */
        contain: layout style;
        will-change: border-color, background-color;
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
        /* Anti-jitter: prevent layout recalculations */
        contain: strict;
    }

    .vw-scene-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* ========================================
       HYBRID A+B: Compact Cinematic Empty State
       ======================================== */

    /* Empty State Container - Compact and clean */
    .vw-scene-empty {
        height: 100%;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(15, 15, 30, 0.95), rgba(25, 25, 45, 0.9));
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 0;
        margin: 0;
        position: relative;
        overflow: hidden;
    }

    /* Subtle gradient background on hover */
    .vw-scene-empty::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg,
            rgba(139, 92, 246, 0.06) 0%,
            rgba(6, 182, 212, 0.04) 50%,
            rgba(236, 72, 153, 0.06) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .vw-scene-empty:hover::before {
        opacity: 1;
    }

    /* Empty State with background image from shots */
    .vw-scene-empty.has-bg-image {
        border: none;
        background-size: cover;
        background-position: center;
    }

    .vw-scene-empty.has-bg-image::before {
        background: linear-gradient(135deg,
            rgba(0, 0, 0, 0.6),
            rgba(15, 15, 30, 0.7));
        opacity: 1;
    }

    /* Center Content Area */
    .vw-empty-center {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        width: 100%;
    }

    /* Compact floating icon */
    .vw-empty-icon-float {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.2));
        border: 1px solid rgba(139, 92, 246, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 0.75rem;
        animation: vw-float 3s ease-in-out infinite;
        box-shadow: 0 4px 16px rgba(139, 92, 246, 0.15);
    }

    @keyframes vw-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    .vw-scene-empty-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
        margin-bottom: 0.85rem;
        letter-spacing: 0.02em;
    }

    .vw-scene-empty.has-bg-image .vw-scene-empty-text {
        color: rgba(255, 255, 255, 0.95);
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }

    /* ========================================
       DYNAMIC: Preview with background image
       ======================================== */

    .vw-empty-with-preview {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .vw-preview-gradient {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent);
        pointer-events: none;
    }

    .vw-preview-toolbar {
        position: relative;
        z-index: 5;
        padding: 0.6rem 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .vw-preview-label {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.7);
        text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }

    .vw-preview-actions {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .vw-preview-btn {
        padding: 0.35rem 0.6rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        color: white;
        font-size: 0.65rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .vw-preview-btn:hover {
        background: rgba(0, 0, 0, 0.7);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-1px);
    }

    .vw-preview-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.6), rgba(6, 182, 212, 0.6));
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-preview-btn.ai:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.8), rgba(6, 182, 212, 0.8));
    }

    .vw-preview-btn.stock {
        background: rgba(16, 185, 129, 0.5);
        border-color: rgba(16, 185, 129, 0.4);
    }

    .vw-preview-btn.stock:hover {
        background: rgba(16, 185, 129, 0.7);
    }

    .vw-preview-btn.collage {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.5), rgba(139, 92, 246, 0.5));
        border-color: rgba(236, 72, 153, 0.4);
    }

    .vw-preview-btn.collage:hover {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.7), rgba(139, 92, 246, 0.7));
    }

    .vw-preview-btn.use-shot {
        background: rgba(16, 185, 129, 0.7);
        border-color: rgba(16, 185, 129, 0.6);
        font-weight: 600;
    }

    .vw-preview-btn.use-shot:hover {
        background: rgba(16, 185, 129, 0.9);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }

    /* ========================================
       EDIT SHOTS BUTTON - Prominent CTA
       ======================================== */

    .vw-edit-shots-btn {
        padding: 0.4rem 0.75rem;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border: none;
        border-radius: 0.4rem;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        display: flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    .vw-edit-shots-btn:hover {
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    }

    .vw-edit-shots-btn:active {
        transform: translateY(0);
    }

    .vw-edit-shots-btn.vw-btn-loading {
        opacity: 0.8;
        cursor: wait;
    }

    /* Action Cards Grid - Compact horizontal layout */
    .vw-scene-empty-buttons {
        display: flex;
        gap: 0.5rem;
        width: 100%;
        max-width: 100%;
        padding: 0 0.5rem;
    }

    /* Individual Action Card - Compact */
    .vw-scene-empty-btn {
        flex: 1;
        padding: 0.65rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(8px);
        color: white;
        cursor: pointer;
        font-size: 0.75rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    /* Subtle hover effect */
    .vw-scene-empty-btn:hover {
        transform: translateY(-2px);
    }

    /* AI Generate - Primary gradient card */
    .vw-scene-empty-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(6, 182, 212, 0.15));
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-scene-empty-btn.ai:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.25), rgba(6, 182, 212, 0.25));
        border-color: rgba(139, 92, 246, 0.5);
        box-shadow: 0 4px 16px rgba(139, 92, 246, 0.2);
    }

    /* Stock Media - Green accent card */
    .vw-scene-empty-btn.stock {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.25);
    }

    .vw-scene-empty-btn.stock:hover {
        background: rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.5);
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.15);
    }

    /* Collage First - Pink gradient card */
    .vw-scene-empty-btn.collage {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(139, 92, 246, 0.1));
        border-color: rgba(236, 72, 153, 0.25);
    }

    .vw-scene-empty-btn.collage:hover {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(139, 92, 246, 0.2));
        border-color: rgba(236, 72, 153, 0.5);
        box-shadow: 0 4px 16px rgba(236, 72, 153, 0.15);
    }

    /* With background image - stronger glassmorphism */
    .vw-scene-empty.has-bg-image .vw-scene-empty-btn {
        backdrop-filter: blur(12px);
        background: rgba(0, 0, 0, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .vw-scene-empty.has-bg-image .vw-scene-empty-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.4)) !important;
    }

    .vw-scene-empty.has-bg-image .vw-scene-empty-btn.stock {
        background: rgba(16, 185, 129, 0.35) !important;
    }

    .vw-scene-empty.has-bg-image .vw-scene-empty-btn.collage {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.35), rgba(139, 92, 246, 0.35)) !important;
    }

    /* Icon with glow effect */
    .vw-scene-empty-btn-icon {
        font-size: 1.6rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        transition: transform 0.3s ease;
    }

    .vw-scene-empty-btn:hover .vw-scene-empty-btn-icon {
        transform: scale(1.1);
    }

    /* Label text */
    .vw-scene-empty-btn-label {
        font-weight: 500;
        font-size: 0.8rem;
        letter-spacing: 0.01em;
    }

    /* Cost badge - pill style */
    .vw-scene-empty-btn-cost {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        background: rgba(0, 0, 0, 0.2);
        padding: 0.15rem 0.5rem;
        border-radius: 1rem;
        margin-top: 0.25rem;
    }

    .vw-scene-empty-btn.stock .vw-scene-empty-btn-cost {
        color: rgba(16, 185, 129, 0.9);
        background: rgba(16, 185, 129, 0.15);
    }

    .vw-scene-empty-btn.ai .vw-scene-empty-btn-cost {
        color: rgba(167, 139, 250, 0.9);
        background: rgba(139, 92, 246, 0.15);
    }

    .vw-scene-empty-btn.collage .vw-scene-empty-btn-cost {
        color: rgba(236, 72, 153, 0.9);
        background: rgba(236, 72, 153, 0.15);
    }

    /* ========================================
       HYBRID B: Shot Timeline Scrubber
       ======================================== */

    .vw-shot-timeline {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 460px;
        margin-top: 1rem;
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
    }

    .vw-shot-timeline-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .vw-shot-timeline-label {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    .vw-shot-timeline-count {
        font-size: 0.6rem;
        color: rgba(139, 92, 246, 0.9);
        background: rgba(139, 92, 246, 0.15);
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
    }

    /* Shot thumbnails strip */
    .vw-shot-timeline-strip {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        padding: 0.25rem 0;
        scroll-behavior: smooth;
        scrollbar-width: none;
    }

    .vw-shot-timeline-strip::-webkit-scrollbar {
        display: none;
    }

    .vw-shot-timeline-thumb {
        flex-shrink: 0;
        width: 48px;
        height: 32px;
        border-radius: 0.25rem;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .vw-shot-timeline-thumb:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: scale(1.08);
    }

    .vw-shot-timeline-thumb.active {
        border-color: #8b5cf6;
        box-shadow: 0 0 12px rgba(139, 92, 246, 0.4);
    }

    .vw-shot-timeline-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-shot-timeline-thumb-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-shot-timeline-thumb-number {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        font-size: 0.5rem;
        color: white;
        padding: 0.1rem 0.2rem;
        text-align: center;
        font-weight: 600;
    }

    /* Progress bar under timeline */
    .vw-shot-timeline-progress {
        height: 3px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .vw-shot-timeline-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    /* Quick action bar */
    .vw-quick-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        justify-content: center;
    }

    .vw-quick-action-btn {
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .vw-quick-action-btn:hover {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-quick-action-btn.primary {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-quick-action-btn.primary:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.4));
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

    .vw-btn-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: vw-spin 0.6s linear infinite;
        vertical-align: middle;
    }

    /* Voice Types Panel */
    .vw-voice-types-btn:hover {
        background: linear-gradient(135deg, rgba(139,92,246,0.35), rgba(6,182,212,0.25)) !important;
        border-color: rgba(139,92,246,0.6) !important;
        transform: translateY(-1px);
    }

    .vw-voice-panel::-webkit-scrollbar {
        width: 4px;
    }

    .vw-voice-panel::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.05);
        border-radius: 2px;
    }

    .vw-voice-panel::-webkit-scrollbar-thumb {
        background: rgba(139,92,246,0.4);
        border-radius: 2px;
    }

    .vw-voice-panel::-webkit-scrollbar-thumb:hover {
        background: rgba(139,92,246,0.6);
    }

    .rotate-180 {
        transform: rotate(180deg);
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

    /* ========================================
       PHASE 6: Shot Type Badges
       ======================================== */
    .vw-shot-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    /* Shot Type Colors - Gradient from red (XCU/tight) to blue (Wide) */
    .vw-shot-badge-xcu { background: rgba(239, 68, 68, 0.25); color: rgba(239, 68, 68, 0.95); }
    .vw-shot-badge-cu { background: rgba(249, 115, 22, 0.25); color: rgba(249, 115, 22, 0.95); }
    .vw-shot-badge-mcu { background: rgba(245, 158, 11, 0.25); color: rgba(245, 158, 11, 0.95); }
    .vw-shot-badge-med { background: rgba(34, 197, 94, 0.25); color: rgba(34, 197, 94, 0.95); }
    .vw-shot-badge-wide { background: rgba(59, 130, 246, 0.25); color: rgba(59, 130, 246, 0.95); }
    .vw-shot-badge-est { background: rgba(99, 102, 241, 0.25); color: rgba(99, 102, 241, 0.95); }

    /* Purpose Badges */
    .vw-shot-badge-ots { background: rgba(139, 92, 246, 0.25); color: rgba(139, 92, 246, 0.95); }
    .vw-shot-badge-reaction { background: rgba(236, 72, 153, 0.25); color: rgba(236, 72, 153, 0.95); }
    .vw-shot-badge-two-shot { background: rgba(20, 184, 166, 0.25); color: rgba(20, 184, 166, 0.95); }

    /* Camera Movement */
    .vw-shot-badge-movement { background: rgba(168, 162, 158, 0.2); color: rgba(168, 162, 158, 0.9); }

    /* Climax indicator - special gradient */
    .vw-shot-badge-climax {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(236, 72, 153, 0.3));
        color: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.5);
    }

    .vw-shot-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin: 0.25rem 0;
    }

    /* PHASE 6: Dialogue Display Styles */
    .vw-scene-dialogue {
        background: rgba(59, 130, 246, 0.1);
        border-left: 3px solid rgba(59, 130, 246, 0.5);
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.5rem;
        border-radius: 0 0.25rem 0.25rem 0;
        font-size: 0.75rem;
        max-height: 80px;
        overflow-y: auto;
    }

    .vw-scene-dialogue::-webkit-scrollbar {
        width: 4px;
    }

    .vw-scene-dialogue::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.3);
        border-radius: 2px;
    }

    .vw-dialogue-label {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.25rem;
        font-size: 0.65rem;
        color: rgba(59, 130, 246, 0.9);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-dialogue-speaker {
        color: rgba(139, 92, 246, 0.9);
        font-weight: 600;
        font-size: 0.7rem;
    }

    .vw-dialogue-text {
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.4;
    }

    .vw-dialogue-more {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.65rem;
        font-style: italic;
    }

    /* ========================================
       PHASE 6: Status Badges
       ======================================== */
    .vw-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.15rem 0.35rem;
        border-radius: 0.2rem;
        font-size: 0.55rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .vw-status-pending {
        background: rgba(168, 162, 158, 0.2);
        color: rgba(168, 162, 158, 0.9);
    }

    .vw-status-generating {
        background: rgba(245, 158, 11, 0.2);
        color: rgba(245, 158, 11, 0.95);
        animation: pulse 1.5s ease-in-out infinite;
    }

    .vw-status-complete,
    .vw-status-ready {
        background: rgba(34, 197, 94, 0.2);
        color: rgba(34, 197, 94, 0.95);
    }

    .vw-status-error {
        background: rgba(239, 68, 68, 0.2);
        color: rgba(239, 68, 68, 0.95);
    }

    /* Intensity Bar */
    .vw-intensity-bar {
        height: 3px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 1.5px;
        overflow: hidden;
        margin: 0.25rem 0;
    }

    .vw-intensity-fill {
        height: 100%;
        border-radius: 1.5px;
        transition: width 0.3s ease;
    }

    .vw-intensity-low { background: rgba(59, 130, 246, 0.8); }
    .vw-intensity-medium { background: rgba(245, 158, 11, 0.8); }
    .vw-intensity-high { background: rgba(239, 68, 68, 0.8); }
    .vw-intensity-climax {
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.9), rgba(236, 72, 153, 0.9));
    }

    /* Mini Progress Ring */
    .vw-mini-progress {
        width: 16px;
        height: 16px;
        position: relative;
    }

    .vw-mini-progress svg {
        transform: rotate(-90deg);
    }

    .vw-mini-progress-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.1);
        stroke-width: 2;
    }

    .vw-mini-progress-fill {
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.3s ease;
    }

    /* ========================================
       PHASE 6: Visual Consistency Improvements
       ======================================== */

    /* Consistent card shadows */
    .vw-scene-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .vw-scene-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }

    /* Badge hover effects */
    .vw-shot-badge:hover,
    .vw-status-badge:hover {
        filter: brightness(1.1);
        cursor: default;
    }

    /* Smooth transitions for status changes */
    .vw-status-badge,
    .vw-intensity-fill {
        transition: all 0.3s ease;
    }

    /* Focus states for accessibility */
    .vw-storyboard-fullscreen select:focus,
    .vw-storyboard-fullscreen button:focus {
        outline: 2px solid rgba(139, 92, 246, 0.5);
        outline-offset: 2px;
    }

    /* Consistent section spacing */
    .vw-card-section {
        padding: 0.5rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 0.5rem;
    }

    /* Scrollbar consistency */
    .vw-scene-dialogue::-webkit-scrollbar,
    .vw-modal-content::-webkit-scrollbar,
    .vw-storyboard-content::-webkit-scrollbar {
        width: 4px;
    }

    .vw-scene-dialogue::-webkit-scrollbar-thumb,
    .vw-modal-content::-webkit-scrollbar-thumb,
    .vw-storyboard-content::-webkit-scrollbar-thumb {
        background: rgba(139, 92, 246, 0.3);
        border-radius: 2px;
    }

    .vw-scene-dialogue::-webkit-scrollbar-thumb:hover,
    .vw-modal-content::-webkit-scrollbar-thumb:hover,
    .vw-storyboard-content::-webkit-scrollbar-thumb:hover {
        background: rgba(139, 92, 246, 0.5);
    }

    /* Empty state styling */
    .vw-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: rgba(255, 255, 255, 0.5);
        text-align: center;
    }

    .vw-empty-state svg {
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    /* Pulse animation for generating states */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    /* Arc template selector styling */
    .vw-arc-selector select {
        transition: border-color 0.2s ease, background 0.2s ease;
    }

    .vw-arc-selector select:hover {
        border-color: rgba(139, 92, 246, 0.5);
        background: rgba(0, 0, 0, 0.4);
    }

    /* ========================================
       PHASE 1: UI UPGRADE - Skeleton Loading
       ======================================== */

    @keyframes vw-shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    .vw-skeleton {
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.03) 0%,
            rgba(255, 255, 255, 0.08) 50%,
            rgba(255, 255, 255, 0.03) 100%
        );
        background-size: 200% 100%;
        animation: vw-shimmer 1.5s ease-in-out infinite;
        border-radius: 0.5rem;
    }

    .vw-skeleton-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
    }

    .vw-skeleton-image {
        aspect-ratio: 16/9;
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.03) 0%,
            rgba(255, 255, 255, 0.08) 50%,
            rgba(255, 255, 255, 0.03) 100%
        );
        background-size: 200% 100%;
        animation: vw-shimmer 1.5s ease-in-out infinite;
    }

    .vw-skeleton-content {
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-skeleton-line {
        height: 0.75rem;
        border-radius: 0.25rem;
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.05) 0%,
            rgba(255, 255, 255, 0.1) 50%,
            rgba(255, 255, 255, 0.05) 100%
        );
        background-size: 200% 100%;
        animation: vw-shimmer 1.5s ease-in-out infinite;
    }

    .vw-skeleton-line.short {
        width: 60%;
    }

    .vw-skeleton-line.medium {
        width: 80%;
    }

    /* ========================================
       PHASE 1: UI UPGRADE - Floating Toolbar
       ======================================== */

    .vw-scene-card {
        position: relative;
    }

    .vw-floating-toolbar {
        position: absolute;
        bottom: calc(100% + 0.5rem);
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.5rem 0.75rem;
        background: rgba(20, 20, 35, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.75rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 100;
        white-space: nowrap;
    }

    .vw-scene-card:hover .vw-floating-toolbar,
    .vw-scene-card.selected .vw-floating-toolbar {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .vw-floating-toolbar-btn {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.65rem;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.9);
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .vw-floating-toolbar-btn:hover {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-floating-toolbar-btn.primary {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-floating-toolbar-btn.primary:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.6), rgba(6, 182, 212, 0.5));
    }

    .vw-floating-toolbar-btn.danger:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.4);
        color: #f87171;
    }

    .vw-floating-toolbar-divider {
        width: 1px;
        height: 1.25rem;
        background: rgba(255, 255, 255, 0.15);
        margin: 0 0.25rem;
    }

    /* ========================================
       PHASE 1: UI UPGRADE - View Mode Toggle
       ======================================== */

    .vw-view-mode-toggle {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
    }

    .vw-view-mode-btn {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.75rem;
        border-radius: 0.35rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.15s ease;
    }

    .vw-view-mode-btn:hover {
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.08);
    }

    .vw-view-mode-btn.active {
        background: rgba(139, 92, 246, 0.25);
        color: white;
        font-weight: 500;
    }

    /* Timeline View Styles */
    .vw-timeline-view {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem 0;
    }

    .vw-timeline-header {
        display: flex;
        align-items: center;
        padding: 0 1rem;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.5rem;
    }

    .vw-timeline-ruler {
        display: flex;
        flex: 1;
        margin-left: 1rem;
    }

    .vw-timeline-ruler span {
        flex: 1;
        text-align: center;
        border-left: 1px solid rgba(255, 255, 255, 0.1);
        padding-left: 0.5rem;
    }

    .vw-timeline-row {
        display: flex;
        align-items: stretch;
        min-height: 80px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 0.5rem;
        overflow: hidden;
        transition: background 0.2s;
    }

    .vw-timeline-row:hover {
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-timeline-scene-info {
        width: 120px;
        flex-shrink: 0;
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.25rem;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-timeline-scene-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
    }

    .vw-timeline-scene-duration {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-timeline-shots {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem;
        overflow-x: auto;
    }

    .vw-timeline-shot {
        flex-shrink: 0;
        height: 60px;
        border-radius: 0.35rem;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.15s ease;
        cursor: pointer;
        position: relative;
    }

    .vw-timeline-shot:hover {
        border-color: rgba(139, 92, 246, 0.6);
        transform: scale(1.05);
        z-index: 10;
    }

    .vw-timeline-shot img {
        height: 100%;
        width: auto;
        min-width: 80px;
        max-width: 160px;
        object-fit: cover;
    }

    .vw-timeline-shot-duration {
        position: absolute;
        bottom: 0.25rem;
        right: 0.25rem;
        padding: 0.15rem 0.35rem;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 0.25rem;
        font-size: 0.6rem;
        color: white;
    }

    .vw-timeline-shot.pending {
        background: rgba(255, 255, 255, 0.05);
        border: 2px dashed rgba(255, 255, 255, 0.2);
        min-width: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-timeline-shot.generating {
        background: rgba(139, 92, 246, 0.1);
        border: 2px solid rgba(139, 92, 246, 0.4);
        min-width: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ========================================
       PHASE 1: UI UPGRADE - Enhanced Progress
       ======================================== */

    .vw-enhanced-progress {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        background: rgba(139, 92, 246, 0.08);
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    }

    .vw-progress-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-progress-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-progress-title .generating-dot {
        width: 8px;
        height: 8px;
        background: #8b5cf6;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }

    .vw-progress-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-progress-bar-container {
        position: relative;
        height: 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        overflow: hidden;
    }

    .vw-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 4px;
        transition: width 0.3s ease;
        position: relative;
    }

    .vw-progress-bar-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
            90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.3) 50%,
            transparent 100%
        );
        animation: vw-shimmer 1.5s ease-in-out infinite;
        background-size: 200% 100%;
    }

    .vw-progress-details {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-progress-step {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-progress-step .step-icon {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(139, 92, 246, 0.5);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-progress-actions {
        display: flex;
        gap: 0.5rem;
    }

    .vw-progress-action-btn {
        padding: 0.3rem 0.6rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.7rem;
        transition: all 0.15s;
    }

    .vw-progress-action-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .vw-progress-action-btn.cancel:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.4);
        color: #f87171;
    }

    /* ========================================
       PHASE 2: UI UPGRADE - Bento Grid Layout
       ======================================== */

    .vw-bento-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-bento-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 1rem;
        transition: all 0.2s ease;
    }

    .vw-bento-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(255, 255, 255, 0.04);
    }

    .vw-bento-card.span-3 { grid-column: span 3; }
    .vw-bento-card.span-4 { grid-column: span 4; }
    .vw-bento-card.span-6 { grid-column: span 6; }
    .vw-bento-card.span-8 { grid-column: span 8; }
    .vw-bento-card.span-12 { grid-column: span 12; }

    @media (max-width: 1200px) {
        .vw-bento-card.span-3 { grid-column: span 6; }
        .vw-bento-card.span-4 { grid-column: span 6; }
    }

    @media (max-width: 768px) {
        .vw-bento-grid {
            grid-template-columns: 1fr;
        }
        .vw-bento-card.span-3,
        .vw-bento-card.span-4,
        .vw-bento-card.span-6,
        .vw-bento-card.span-8 {
            grid-column: span 1;
        }
    }

    .vw-bento-stat {
        text-align: center;
    }

    .vw-bento-stat-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .vw-bento-stat-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-bento-stat.purple .vw-bento-stat-value { color: #a78bfa; }
    .vw-bento-stat.cyan .vw-bento-stat-value { color: #22d3ee; }
    .vw-bento-stat.green .vw-bento-stat-value { color: #34d399; }
    .vw-bento-stat.amber .vw-bento-stat-value { color: #fbbf24; }

    /* ========================================
       PHASE 2: UI UPGRADE - Collapsible Panels
       ======================================== */

    .vw-collapsible-section {
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        margin-bottom: 0.75rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.02);
    }

    .vw-collapsible-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background 0.15s;
        user-select: none;
    }

    .vw-collapsible-header:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .vw-collapsible-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-collapsible-title-icon {
        font-size: 1rem;
    }

    .vw-collapsible-badges {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-collapsible-chevron {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.5);
        transition: transform 0.2s ease;
    }

    .vw-collapsible-chevron.open {
        transform: rotate(180deg);
    }

    .vw-collapsible-content {
        padding: 0 1rem 1rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }

    .vw-collapsible-content.open {
        max-height: 1000px;
        padding: 0 1rem 1rem;
    }

    /* ========================================
       PHASE 2: UI UPGRADE - Bible Previews
       ======================================== */

    .vw-bible-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.75rem;
        margin-top: 0.75rem;
    }

    .vw-bible-preview-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-bible-preview-card:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .vw-bible-preview-image {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        background: rgba(0, 0, 0, 0.3);
    }

    .vw-bible-preview-placeholder {
        width: 100%;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(139, 92, 246, 0.1);
        font-size: 2rem;
    }

    .vw-bible-preview-info {
        padding: 0.5rem;
    }

    .vw-bible-preview-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vw-bible-preview-tag {
        font-size: 0.6rem;
        color: rgba(139, 92, 246, 0.8);
        margin-top: 0.15rem;
    }

    .vw-bible-add-card {
        background: rgba(139, 92, 246, 0.05);
        border: 2px dashed rgba(139, 92, 246, 0.3);
        border-radius: 0.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 120px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-bible-add-card:hover {
        border-color: rgba(139, 92, 246, 0.5);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-bible-add-icon {
        font-size: 1.5rem;
        color: rgba(139, 92, 246, 0.6);
        margin-bottom: 0.25rem;
    }

    .vw-bible-add-text {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* ========================================
       PHASE 2: UI UPGRADE - Side Panel
       ======================================== */

    .vw-layout-with-panel {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    .vw-main-content {
        flex: 1;
        overflow-y: auto;
        transition: margin-right 0.3s ease;
    }

    .vw-main-content.panel-open {
        margin-right: 320px;
    }

    .vw-side-panel {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 320px;
        background: rgba(15, 15, 28, 0.98);
        border-left: 1px solid rgba(139, 92, 246, 0.2);
        backdrop-filter: blur(12px);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .vw-side-panel.open {
        transform: translateX(0);
    }

    .vw-side-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-side-panel-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
    }

    .vw-side-panel-close {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.35rem;
        border: none;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        transition: all 0.15s;
    }

    .vw-side-panel-close:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .vw-side-panel-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .vw-side-panel-section {
        margin-bottom: 1.25rem;
    }

    .vw-side-panel-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .vw-side-panel-preview {
        width: 100%;
        aspect-ratio: 16/9;
        border-radius: 0.5rem;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.3);
        margin-bottom: 1rem;
    }

    .vw-side-panel-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ========================================
       PHASE 2: UI UPGRADE - @ Mention Hint
       ======================================== */

    .vw-mention-hint {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.35rem;
        font-size: 0.65rem;
        font-family: monospace;
        color: #a78bfa;
    }

    .vw-mention-hint-label {
        color: rgba(255, 255, 255, 0.5);
        margin-right: 0.25rem;
    }

    /* ========================================
       PHASE 3: AI-NATIVE FEATURES
       ======================================== */

    /* @ Mention Autocomplete Dropdown */
    .vw-mention-autocomplete {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 200px;
        overflow-y: auto;
        background: rgba(20, 20, 35, 0.98);
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        z-index: 100;
        backdrop-filter: blur(12px);
        margin-top: 0.25rem;
    }

    .vw-mention-group-header {
        padding: 0.5rem 0.75rem;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .vw-mention-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        cursor: pointer;
        transition: background 0.15s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }

    .vw-mention-item:hover,
    .vw-mention-item.active {
        background: rgba(139, 92, 246, 0.15);
    }

    .vw-mention-item-image {
        width: 32px;
        height: 32px;
        border-radius: 0.35rem;
        object-fit: cover;
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-mention-item-icon {
        width: 32px;
        height: 32px;
        border-radius: 0.35rem;
        background: rgba(139, 92, 246, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .vw-mention-item-info {
        flex: 1;
    }

    .vw-mention-item-name {
        font-size: 0.8rem;
        color: white;
        font-weight: 500;
    }

    .vw-mention-item-tag {
        font-size: 0.65rem;
        color: #a78bfa;
        font-family: monospace;
    }

    .vw-mention-item-type {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        background: rgba(139, 92, 246, 0.2);
        color: #c4b5fd;
    }

    /* Brainstorm Suggestions Panel */
    .vw-brainstorm-panel {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(236, 72, 153, 0.08));
        border: 1px solid rgba(139, 92, 246, 0.25);
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .vw-brainstorm-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: rgba(139, 92, 246, 0.1);
        border-bottom: 1px solid rgba(139, 92, 246, 0.15);
    }

    .vw-brainstorm-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #c4b5fd;
    }

    .vw-brainstorm-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        background: rgba(139, 92, 246, 0.3);
        border-radius: 0.25rem;
        color: #e9d5ff;
    }

    .vw-brainstorm-body {
        padding: 0.75rem 1rem;
    }

    .vw-brainstorm-suggestions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-brainstorm-suggestion {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-brainstorm-suggestion:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.3);
        transform: translateX(4px);
    }

    .vw-brainstorm-suggestion-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(236, 72, 153, 0.3));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .vw-brainstorm-suggestion-content {
        flex: 1;
    }

    .vw-brainstorm-suggestion-type {
        font-size: 0.65rem;
        color: #f472b6;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.15rem;
    }

    .vw-brainstorm-suggestion-text {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.4;
    }

    .vw-brainstorm-suggestion-apply {
        padding: 0.35rem 0.6rem;
        background: rgba(139, 92, 246, 0.2);
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.35rem;
        color: #c4b5fd;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s ease;
        opacity: 0;
    }

    .vw-brainstorm-suggestion:hover .vw-brainstorm-suggestion-apply {
        opacity: 1;
    }

    .vw-brainstorm-suggestion-apply:hover {
        background: rgba(139, 92, 246, 0.4);
    }

    .vw-brainstorm-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.5rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
    }

    .vw-brainstorm-loading-spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-brainstorm-empty {
        text-align: center;
        padding: 1.5rem;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.8rem;
    }

    .vw-brainstorm-refresh {
        padding: 0.35rem 0.7rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
    }

    .vw-brainstorm-refresh:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
    }

    /* Progressive Generation Preview */
    .vw-generation-preview {
        position: relative;
        overflow: hidden;
        border-radius: 0.5rem;
    }

    .vw-generation-preview-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.2));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .vw-generation-preview-progress {
        width: 60%;
        max-width: 200px;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .vw-generation-preview-bar {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 2px;
        transition: width 0.3s ease;
        animation: vw-generation-pulse 1.5s ease-in-out infinite;
    }

    @keyframes vw-generation-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .vw-generation-preview-status {
        font-size: 0.75rem;
        color: white;
        font-weight: 500;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    }

    .vw-generation-preview-substatus {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 0.25rem;
    }

    /* Progressive image reveal effect */
    .vw-generation-reveal {
        position: relative;
    }

    .vw-generation-reveal::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom,
            transparent 0%,
            rgba(15, 15, 28, 0.5) 50%,
            rgba(15, 15, 28, 1) 100%
        );
        animation: vw-reveal-scan 2s ease-in-out infinite;
    }

    @keyframes vw-reveal-scan {
        0% { transform: translateY(-100%); }
        100% { transform: translateY(100%); }
    }

    /* AI Confidence Indicator */
    .vw-ai-confidence {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.7rem;
    }

    .vw-ai-confidence-bar {
        width: 60px;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
    }

    .vw-ai-confidence-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    .vw-ai-confidence-fill.high {
        background: linear-gradient(90deg, #10b981, #34d399);
    }

    .vw-ai-confidence-fill.medium {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
    }

    .vw-ai-confidence-fill.low {
        background: linear-gradient(90deg, #ef4444, #f87171);
    }

    /* Prompt Input with @ Mention Support */
    .vw-prompt-input-container {
        position: relative;
    }

    .vw-prompt-textarea {
        width: 100%;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.85rem;
        min-height: 100px;
        resize: vertical;
        transition: border-color 0.2s ease;
    }

    .vw-prompt-textarea:focus {
        outline: none;
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-prompt-textarea::placeholder {
        color: rgba(255, 255, 255, 0.35);
    }

    /* @ Mention in textarea highlight */
    .vw-prompt-mention {
        color: #a78bfa;
        background: rgba(139, 92, 246, 0.15);
        padding: 0.1rem 0.25rem;
        border-radius: 0.2rem;
    }

    /* Keyboard hints */
    .vw-keyboard-hint {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.35rem;
    }

    .vw-keyboard-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.25rem;
        padding: 0.1rem 0.3rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.2rem;
        font-size: 0.6rem;
        font-family: monospace;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Phase 3: Progressive Generation Animations */
    @keyframes vw-gradient-shift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes vw-scan-line {
        0% { top: -2px; }
        100% { top: 100%; }
    }

    @keyframes vw-pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 20px 10px rgba(139, 92, 246, 0.1);
        }
    }

    /* Brainstorm panel transitions */
    .vw-brainstorm-panel[x-cloak] { display: none; }

    /* ========================================
       PHASE 4: POLISH & REFINEMENTS
       ======================================== */

    /* Enhanced Glassmorphism Effects */
    .vw-glass {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-glass-strong {
        background: rgba(15, 15, 28, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(139, 92, 246, 0.15);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .vw-glass-accent {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.1));
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    /* Micro-animations: Button hover effects (Performance optimized) */
    .vw-btn-hover {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
    }

    .vw-btn-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
    }

    .vw-btn-hover:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(139, 92, 246, 0.15);
    }

    /* Micro-animations: Card hover effects (Performance optimized) */
    .vw-card-hover {
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
    }

    .vw-card-hover:hover {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    }

    /* Micro-animations: Icon spin on hover */
    .vw-icon-spin-hover:hover svg,
    .vw-icon-spin-hover:hover .icon {
        animation: vw-icon-spin 0.5s ease;
    }

    @keyframes vw-icon-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Micro-animations: Subtle bounce */
    .vw-bounce-hover:hover {
        animation: vw-subtle-bounce 0.4s ease;
    }

    @keyframes vw-subtle-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    /* Micro-animations: Glow effect */
    .vw-glow-hover {
        transition: box-shadow 0.3s ease;
    }

    .vw-glow-hover:hover {
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.4), 0 0 40px rgba(139, 92, 246, 0.1);
    }

    /* Micro-animations: Scale on focus for inputs */
    .vw-input-focus {
        transition: all 0.2s ease;
    }

    .vw-input-focus:focus {
        transform: scale(1.01);
        border-color: rgba(139, 92, 246, 0.5);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    /* Staggered animation for lists */
    .vw-stagger-item {
        opacity: 0;
        transform: translateY(10px);
        animation: vw-stagger-in 0.3s ease forwards;
    }

    @keyframes vw-stagger-in {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Shimmer effect for loading states */
    .vw-shimmer {
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.03) 0%,
            rgba(255, 255, 255, 0.08) 50%,
            rgba(255, 255, 255, 0.03) 100%
        );
        background-size: 200% 100%;
        animation: vw-shimmer 1.5s infinite;
    }

    @keyframes vw-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Ripple effect on click */
    .vw-ripple {
        position: relative;
        overflow: hidden;
    }

    .vw-ripple::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
        opacity: 0;
    }

    .vw-ripple:active::after {
        width: 200px;
        height: 200px;
        opacity: 1;
        transition: 0s;
    }

    /* Keyboard shortcut badge */
    .vw-shortcut-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.4rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-family: monospace;
        color: rgba(255, 255, 255, 0.4);
        margin-left: 0.5rem;
    }

    /* Theme toggle styles */
    .vw-theme-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 2rem;
    }

    .vw-theme-toggle-btn {
        padding: 0.35rem 0.6rem;
        border: none;
        border-radius: 1.5rem;
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s ease;
    }

    .vw-theme-toggle-btn.active {
        background: rgba(139, 92, 246, 0.3);
        color: white;
    }

    /* Light theme variables (applied via class) */
    .vw-light-theme {
        --vw-bg-primary: #f8fafc;
        --vw-bg-secondary: #ffffff;
        --vw-bg-tertiary: #f1f5f9;
        --vw-text-primary: #1e293b;
        --vw-text-secondary: #64748b;
        --vw-text-muted: #94a3b8;
        --vw-border-color: rgba(0, 0, 0, 0.08);
        --vw-accent-primary: #8b5cf6;
        --vw-accent-secondary: #06b6d4;
        --vw-shadow-color: rgba(0, 0, 0, 0.1);
    }

    /* Dark theme variables (default) */
    .vw-dark-theme,
    .vw-storyboard-fullscreen {
        --vw-bg-primary: #0f0f1c;
        --vw-bg-secondary: #1a1a2e;
        --vw-bg-tertiary: #252542;
        --vw-text-primary: #ffffff;
        --vw-text-secondary: rgba(255, 255, 255, 0.7);
        --vw-text-muted: rgba(255, 255, 255, 0.4);
        --vw-border-color: rgba(255, 255, 255, 0.08);
        --vw-accent-primary: #8b5cf6;
        --vw-accent-secondary: #06b6d4;
        --vw-shadow-color: rgba(0, 0, 0, 0.5);
    }

    /* Apply theme variables */
    .vw-themed-bg {
        background-color: var(--vw-bg-primary);
        color: var(--vw-text-primary);
    }

    .vw-themed-card {
        background-color: var(--vw-bg-secondary);
        border-color: var(--vw-border-color);
    }

    .vw-themed-text {
        color: var(--vw-text-primary);
    }

    .vw-themed-text-secondary {
        color: var(--vw-text-secondary);
    }

    .vw-themed-text-muted {
        color: var(--vw-text-muted);
    }

    /* Keyboard shortcuts overlay */
    .vw-shortcuts-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 2rem;
    }

    .vw-shortcuts-modal {
        background: rgba(30, 30, 50, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 1rem;
        padding: 1.5rem;
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        overflow-y: auto;
    }

    .vw-shortcuts-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-shortcuts-group {
        margin-bottom: 1rem;
    }

    .vw-shortcuts-group-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-shortcut-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-shortcut-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.8rem;
    }

    .vw-shortcut-keys {
        display: flex;
        gap: 0.25rem;
    }

    .vw-shortcut-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        padding: 0.25rem 0.5rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        font-size: 0.7rem;
        font-family: monospace;
        color: rgba(255, 255, 255, 0.7);
    }

    /* Focus indicator for accessibility */
    .vw-focus-ring:focus-visible {
        outline: 2px solid rgba(139, 92, 246, 0.6);
        outline-offset: 2px;
    }

    /* Smooth scrollbar for webkit browsers */
    .vw-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .vw-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 3px;
    }

    .vw-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(139, 92, 246, 0.3);
        border-radius: 3px;
    }

    .vw-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(139, 92, 246, 0.5);
    }

    /* Toast notification styles */
    .vw-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        padding: 0.75rem 1rem;
        background: rgba(30, 30, 50, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 3000;
        animation: vw-toast-in 0.3s ease;
    }

    .vw-toast.success {
        border-color: rgba(16, 185, 129, 0.5);
    }

    .vw-toast.error {
        border-color: rgba(239, 68, 68, 0.5);
    }

    @keyframes vw-toast-in {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .vw-toast-out {
        animation: vw-toast-out 0.3s ease forwards;
    }

    @keyframes vw-toast-out {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(20px);
        }
    }

    /* ========================================
       PERFORMANCE OPTIMIZATIONS
       ======================================== */

    /* Modal performance - use GPU acceleration and containment */
    .vw-modal-overlay,
    .msm-fullscreen {
        will-change: opacity;
        contain: layout style;
    }

    /* Scene cards - isolate rendering for better scroll performance */
    .vw-scene-card {
        contain: layout style paint;
    }

    /* Side panel - GPU accelerated transforms */
    .vw-side-panel {
        will-change: transform;
        contain: layout style;
    }

    /* Reduce paint on backdrop-filter elements when not visible */
    [x-cloak] {
        display: none !important;
    }

    /* Prevent layout thrashing on hover states */
    .vw-floating-toolbar,
    .vw-brainstorm-panel {
        contain: layout;
    }

    /* Optimize scrolling containers */
    .vw-storyboard-main,
    .vw-scenes-grid {
        contain: strict;
        content-visibility: auto;
    }

    /* Reduce repaints for frequently updated elements */
    .vw-spinner,
    .vw-generation-preview-bar {
        will-change: transform;
    }
</style>

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

<div class="vw-storyboard-fullscreen" x-data="{
    showSettings: true,
    selectedModel: '{{ $storyboard['imageModel'] ?? 'nanobanana' }}',
    viewMode: 'grid',
    selectedCard: null,
    isGenerating: false,
    // NEW: Sidebar layout state
    sidebarCollapsed: false,
    activeSection: 'settings',
    // Resizable sidebar
    sidebarWidth: parseInt(localStorage.getItem('storyboard-sidebar-width')) || 320,
    isResizing: false,
    resizeStartX: 0,
    resizeStartWidth: 0,
    toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
    },
    setActiveSection(section) {
        this.activeSection = section;
        if (this.sidebarCollapsed) {
            this.sidebarCollapsed = false;
        }
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
    // Phase 4: Theme Support
    theme: localStorage.getItem('vw-theme') || 'dark',
    setTheme(newTheme) {
        this.theme = newTheme;
        localStorage.setItem('vw-theme', newTheme);
        document.documentElement.classList.remove('vw-light-theme', 'vw-dark-theme');
        document.documentElement.classList.add('vw-' + newTheme + '-theme');
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
            // T = Toggle theme
            else if (e.key === 't' || e.key === 'T') {
                e.preventDefault();
                this.setTheme(this.theme === 'dark' ? 'light' : 'dark');
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
        // Apply saved theme
        if (this.theme === 'light') {
            document.documentElement.classList.add('vw-light-theme');
        }
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

            {{-- Phase 4: Theme Toggle --}}
            <div class="vw-theme-toggle">
                <button type="button"
                        class="vw-theme-toggle-btn"
                        :class="{ 'active': theme === 'dark' }"
                        @click="setTheme('dark')"
                        title="{{ __('Dark Theme') }}">
                    🌙
                </button>
                <button type="button"
                        class="vw-theme-toggle-btn"
                        :class="{ 'active': theme === 'light' }"
                        @click="setTheme('light')"
                        title="{{ __('Light Theme') }}">
                    ☀️
                </button>
            </div>

            {{-- Phase 4: Keyboard Shortcuts Help --}}
            <button type="button"
                    @click="shortcuts.showHelp = true"
                    style="padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.35rem; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem;"
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
        {{-- Main Content Area - NEW SIDEBAR LAYOUT --}}
        <div class="vw-storyboard-main">

            {{-- ========================================
                 ICON RAIL - Always visible, 48px
                 ======================================== --}}
            <div class="vw-icon-rail">
                {{-- Settings Section --}}
                <button type="button"
                        class="vw-icon-rail-btn"
                        :class="{ 'active': activeSection === 'settings' }"
                        @click="setActiveSection('settings')"
                        title="{{ __('Settings') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </button>

                {{-- Style Section --}}
                <button type="button"
                        class="vw-icon-rail-btn"
                        :class="{ 'active': activeSection === 'style' }"
                        @click="setActiveSection('style')"
                        title="{{ __('Visual Style') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="13.5" cy="6.5" r="2.5"></circle>
                        <circle cx="17.5" cy="10.5" r="2.5"></circle>
                        <circle cx="8.5" cy="7.5" r="2.5"></circle>
                        <circle cx="6.5" cy="12.5" r="2.5"></circle>
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </button>

                {{-- Memory Section --}}
                <button type="button"
                        class="vw-icon-rail-btn"
                        :class="{ 'active': activeSection === 'memory' }"
                        @click="setActiveSection('memory')"
                        title="{{ __('Scene Memory') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </button>

                <div class="vw-icon-rail-divider"></div>

                {{-- Collapse Toggle --}}
                <button type="button"
                        class="vw-icon-rail-btn"
                        @click="toggleSidebar()"
                        :title="sidebarCollapsed ? '{{ __('Expand Sidebar') }}' : '{{ __('Collapse Sidebar') }}'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         :style="sidebarCollapsed ? 'transform: rotate(180deg)' : ''">
                        <polyline points="11 17 6 12 11 7"></polyline>
                        <polyline points="18 17 13 12 18 7"></polyline>
                    </svg>
                </button>

                <div class="vw-icon-rail-spacer"></div>

                {{-- Help --}}
                <button type="button"
                        class="vw-icon-rail-btn"
                        @click="shortcuts.showHelp = true"
                        title="{{ __('Keyboard Shortcuts') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </button>
            </div>

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
                    <span class="vw-sidebar-title" x-text="activeSection === 'settings' ? '{{ __('Settings') }}' : activeSection === 'style' ? '{{ __('Visual Style') }}' : '{{ __('Scene Memory') }}'"></span>
                </div>

                {{-- Sidebar Content --}}
                <div class="vw-sidebar-content">

                    {{-- ======== SETTINGS SECTION ======== --}}
                    <div x-show="activeSection === 'settings'" x-transition.opacity>
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
                                    <label style="display: block; font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-bottom: 0.25rem;">{{ __('Positive Prompts') }}</label>
                                    <textarea wire:model.blur="storyboard.technicalSpecs.positive"
                                              placeholder="{{ __('high quality, cinematic...') }}"
                                              style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: white; font-size: 0.7rem; min-height: 50px; resize: vertical;"></textarea>
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    <label style="display: block; font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-bottom: 0.25rem;">{{ __('Negative Prompts') }}</label>
                                    <textarea wire:model.blur="storyboard.technicalSpecs.negative"
                                              placeholder="{{ __('blurry, low quality...') }}"
                                              style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.25rem; color: white; font-size: 0.7rem; min-height: 50px; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ======== STYLE SECTION ======== --}}
                    <div x-show="activeSection === 'style'" x-transition.opacity>
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

                        <p style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 0.75rem; line-height: 1.4;">
                            💡 {{ __('"Auto" uses genre-appropriate defaults') }}
                        </p>
                    </div>

                    {{-- ======== MEMORY SECTION - Modern Tabbed Design ======== --}}
                    <div x-show="activeSection === 'memory'" x-transition.opacity x-data="{ memoryTab: 'characters' }">
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
                    </div>

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
                     x-show="$wire.isGenerating || {{ $isGeneratingBatch ? 'true' : 'false' }}"
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
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 0.35rem;">
                                🖼️ {{ __('Images Generated') }}
                            </span>
                            <span style="font-size: 0.8rem; font-weight: 600; color: #10b981;">{{ $shotStats['shotsWithImages'] }}/{{ $shotStats['totalShots'] }}</span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $shotStats['imageProgress'] }}%; background: linear-gradient(90deg, #10b981, #22c55e); border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <div class="vw-bento-card span-6">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 0.35rem;">
                                🎬 {{ __('Videos Generated') }}
                            </span>
                            <span style="font-size: 0.8rem; font-weight: 600; color: #06b6d4;">{{ $shotStats['shotsWithVideos'] }}/{{ $shotStats['totalShots'] }}</span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
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
                    background: rgba(139, 92, 246, 0.1);
                    border-radius: 0.5rem;
                    margin-bottom: 0.75rem;
                    border: 1px solid rgba(139, 92, 246, 0.2);
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 0.25rem;
                        color: rgba(139, 92, 246, 0.9);
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
                            border: 1px solid rgba(139, 92, 246, 0.3);
                            color: #fff;
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
                            color: rgba(255,255,255,0.6);
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
            <div class="vw-storyboard-grid"
                 x-show="viewMode === 'grid'"
                 x-transition
                 wire:loading.remove
                 wire:target="goToStoryboardPage,previousStoryboardPage,nextStoryboardPage">
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
                <div class="vw-scene-card" wire:key="scene-card-{{ $index }}">
                    {{-- Floating Toolbar (appears on hover) --}}
                    <div class="vw-floating-toolbar">
                        @if($imageUrl)
                            <button type="button"
                                    class="vw-floating-toolbar-btn primary"
                                    wire:click="openAIEditModal({{ $index }})"
                                    title="{{ __('Edit with AI') }}">
                                ✨ {{ __('Edit') }}
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                    wire:loading.attr="disabled"
                                    title="{{ __('Regenerate') }}">
                                🔄 {{ __('Regen') }}
                            </button>
                            <div class="vw-floating-toolbar-divider"></div>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    wire:click="openUpscaleModal({{ $index }})"
                                    title="{{ __('Upscale') }}">
                                ⬆️
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    wire:click="openMultiShotModal({{ $index }})"
                                    title="{{ __('Multi-shot') }}">
                                ✂️
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    wire:click="openStockBrowser({{ $index }})"
                                    title="{{ __('Stock media') }}">
                                📷
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    @click="fetchBrainstormSuggestions({{ $index }})"
                                    title="{{ __('AI Brainstorm') }}">
                                💡
                            </button>
                        @else
                            <button type="button"
                                    class="vw-floating-toolbar-btn primary"
                                    wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                    wire:loading.attr="disabled"
                                    title="{{ __('Generate with AI') }}">
                                🎨 {{ __('Generate') }}
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    wire:click="openStockBrowser({{ $index }})"
                                    title="{{ __('Browse stock') }}">
                                📷 {{ __('Stock') }}
                            </button>
                            <button type="button"
                                    class="vw-floating-toolbar-btn"
                                    @click="fetchBrainstormSuggestions({{ $index }})"
                                    title="{{ __('AI Brainstorm') }}">
                                💡 {{ __('Ideas') }}
                            </button>
                        @endif
                    </div>

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
                                        style="padding: 0.25rem 0.5rem; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 1rem;">
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
                                <span style="background: linear-gradient(135deg, #8b5cf6, #06b6d4); color: white; padding: 0.25rem 0.5rem; border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
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
                                     style="height: 220px; background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(6,182,212,0.1)); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden;">

                                    {{-- Animated background gradient --}}
                                    <div style="position: absolute; inset: 0; background: linear-gradient(45deg, rgba(139,92,246,0.1), rgba(6,182,212,0.1), rgba(236,72,153,0.1)); background-size: 400% 400%; animation: vw-gradient-shift 4s ease infinite;"></div>

                                    {{-- Scan line effect --}}
                                    <div style="position: absolute; inset: 0; overflow: hidden; pointer-events: none;">
                                        <div style="position: absolute; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, rgba(139,92,246,0.6), transparent); animation: vw-scan-line 2s linear infinite;"></div>
                                    </div>

                                    {{-- Progress content --}}
                                    <div style="position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                                        {{-- AI Icon with pulse --}}
                                        <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(6,182,212,0.3)); display: flex; align-items: center; justify-content: center; animation: vw-pulse 1.5s ease-in-out infinite;">
                                            <span style="font-size: 1.5rem;">🎨</span>
                                        </div>

                                        {{-- Progress bar --}}
                                        <div class="vw-generation-preview-progress" style="width: 180px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                            <div class="vw-generation-preview-bar"
                                                 :style="'width: ' + progress + '%'"
                                                 style="height: 100%; background: linear-gradient(90deg, #8b5cf6, #06b6d4); border-radius: 3px; transition: width 0.5s ease;"></div>
                                        </div>

                                        {{-- Status text --}}
                                        <div style="text-align: center;">
                                            <div class="vw-generation-preview-status" x-text="status" style="font-size: 0.85rem; color: white; font-weight: 500;"></div>
                                            <div class="vw-generation-preview-substatus" x-text="substatus" style="font-size: 0.7rem; color: rgba(255,255,255,0.6); margin-top: 0.25rem;"></div>
                                        </div>

                                        {{-- AI Confidence indicator --}}
                                        <div class="vw-ai-confidence" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.65rem; color: rgba(255,255,255,0.5);">
                                            <span>{{ __('AI Confidence:') }}</span>
                                            <div class="vw-ai-confidence-bar" style="width: 50px; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden;">
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
                                        <span wire:loading wire:target="openMultiShotModal({{ $index }})" class="vw-btn-spinner"></span>
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
                                                <div style="position: relative; border-radius: 0.25rem; overflow: hidden; contain: layout;">
                                                    {{-- The single collage image --}}
                                                    <img src="{{ $currentCollageData['previewUrl'] }}"
                                                         alt="Collage Preview"
                                                         loading="lazy"
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
                                                    div:hover > .use-this-overlay { opacity: 1; }
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

                    {{-- Multi-Shot Timeline (if decomposed) - Compact Horizontal Strip --}}
                    @if($hasMultiShot && !empty($decomposed['shots']))
                        @php
                            $shotChainStatus = $this->getShotChainStatus($index);
                            $totalShotDuration = $decomposed['totalDuration'] ?? array_sum(array_column($decomposed['shots'], 'duration'));
                        @endphp
                        <div wire:key="multi-shot-timeline-{{ $index }}"
                             x-data="{ expanded: false }"
                             style="padding: 0.4rem 0.5rem; border-top: 1px solid rgba(139,92,246,0.15); background: rgba(139,92,246,0.04); contain: layout;">
                            {{-- Compact Header row --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <button type="button" @click="expanded = !expanded" style="background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.6); font-size: 0.6rem; padding: 0;">
                                        <span x-text="expanded ? '▼' : '▶'"></span>
                                    </button>
                                    <span style="font-size: 0.55rem; color: rgba(255,255,255,0.5); font-weight: 600;">
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
                                        $borderColor = $hasVideo ? 'rgba(6,182,212,0.6)' : ($hasImage ? 'rgba(16,185,129,0.5)' : ($isSelected ? '#8b5cf6' : 'rgba(255,255,255,0.1)'));
                                    @endphp
                                    <div wire:key="shot-thumb-{{ $index }}-{{ $shotIdx }}"
                                         style="cursor: pointer; position: relative; border-radius: 0.35rem; overflow: hidden; border: 2px solid {{ $borderColor }}; background: {{ $isSelected ? 'rgba(139,92,246,0.15)' : 'rgba(0,0,0,0.2)' }}; flex-shrink: 0; width: 90px;"
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
                            'internal' => ['icon' => '💭', 'color' => 'rgba(168, 85, 247, 0.4)', 'border' => 'rgba(168, 85, 247, 0.6)', 'label' => 'INTERNAL', 'lipSync' => false],
                            'monologue' => ['icon' => '🗣️', 'color' => 'rgba(251, 191, 36, 0.4)', 'border' => 'rgba(251, 191, 36, 0.6)', 'label' => 'MONOLOGUE', 'lipSync' => true],
                        ];
                    @endphp

                    @if(!empty($speechSegments) || !empty($narration))
                        <div style="padding: 0.3rem 0.75rem;" x-data="{ voicePanelOpen: false }">
                            <div class="vw-scene-dialogue" style="display: flex; justify-content: space-between; align-items: center; position: relative;">
                                {{-- Voice Types clickable badge --}}
                                <button
                                    type="button"
                                    @click="voicePanelOpen = !voicePanelOpen"
                                    class="vw-voice-types-btn"
                                    style="display: flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.5rem; background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(6,182,212,0.15)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.3rem; cursor: pointer; transition: all 0.2s;"
                                    :style="voicePanelOpen ? 'background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(6,182,212,0.3)); border-color: rgba(139,92,246,0.6);' : ''"
                                >
                                    <span style="font-size: 0.75rem;">🎙️</span>
                                    <span style="font-weight: 600; font-size: 0.7rem; color: white;">{{ __('Voice Types') }}</span>
                                    <span style="opacity: 0.6; font-size: 0.6rem; color: rgba(255,255,255,0.7);">({{ $totalSegments }})</span>
                                    <svg :class="voicePanelOpen ? 'rotate-180' : ''" style="width: 10px; height: 10px; transition: transform 0.2s; color: rgba(255,255,255,0.6);" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                {{-- Inspect button --}}
                                <button
                                    wire:click="openSceneTextInspector({{ $index }})"
                                    class="vw-inspect-btn"
                                    title="{{ __('Full scene details') }}"
                                    style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); color: #a78bfa; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem; cursor: pointer; transition: all 0.2s;"
                                >
                                    🔍 {{ __('Inspect') }}
                                </button>

                                {{-- Voice Types Dropdown Panel --}}
                                <div
                                    x-show="voicePanelOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    @click.outside="voicePanelOpen = false"
                                    class="vw-voice-panel"
                                    style="position: absolute; top: 100%; left: 0; right: 0; margin-top: 0.35rem; background: linear-gradient(135deg, rgba(30,30,50,0.98), rgba(20,20,40,0.99)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.5); z-index: 50; max-height: 320px; overflow: hidden; display: flex; flex-direction: column;"
                                >
                                    {{-- Panel Header --}}
                                    <div style="padding: 0.5rem 0.65rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                                        <span style="font-size: 0.7rem; font-weight: 600; color: white;">{{ __('Speech Segments') }}</span>
                                        <div style="display: flex; gap: 0.5rem; font-size: 0.55rem;">
                                            @if(collect($speechSegments)->where('needsLipSync', true)->count() > 0)
                                                <span style="color: #6ee7b7;">{{ collect($speechSegments)->where('needsLipSync', true)->count() }} {{ __('lip-sync') }}</span>
                                            @endif
                                            @if(collect($speechSegments)->where('needsLipSync', false)->count() > 0)
                                                <span style="color: #67e8f9;">{{ collect($speechSegments)->where('needsLipSync', false)->count() }} {{ __('voiceover') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Segments List --}}
                                    <div style="overflow-y: auto; flex: 1; padding: 0.5rem;">
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
                                            <div style="padding: 0.5rem; margin-bottom: 0.4rem; background: rgba(255,255,255,0.03); border-left: 3px solid {{ $segConfig['border'] }}; border-radius: 0 0.3rem 0.3rem 0;">
                                                {{-- Segment Header --}}
                                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                                    <span style="font-size: 0.85rem;">{{ $segConfig['icon'] }}</span>
                                                    <span style="font-size: 0.55rem; font-weight: 600; color: white; padding: 0.1rem 0.3rem; background: {{ $segConfig['color'] }}; border-radius: 0.2rem;">
                                                        {{ $segConfig['label'] }}
                                                    </span>
                                                    @if($segSpeaker)
                                                        <span style="color: #c4b5fd; font-size: 0.65rem; font-weight: 600;">{{ $segSpeaker }}</span>
                                                    @endif
                                                    <span style="flex: 1;"></span>
                                                    @if($needsLipSync)
                                                        <span style="font-size: 0.5rem; padding: 0.1rem 0.25rem; background: rgba(16,185,129,0.2); color: #6ee7b7; border-radius: 0.15rem;">MULTITALK</span>
                                                    @else
                                                        <span style="font-size: 0.5rem; padding: 0.1rem 0.25rem; background: rgba(14,165,233,0.2); color: #67e8f9; border-radius: 0.15rem;">TTS</span>
                                                    @endif
                                                    <span style="font-size: 0.5rem; color: rgba(255,255,255,0.5);">~{{ $estDuration }}s</span>
                                                </div>

                                                {{-- Segment Text --}}
                                                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.8); line-height: 1.4; margin-bottom: 0.35rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                    {{ $segText }}
                                                </div>

                                                {{-- Audio Player (if audio exists) --}}
                                                @if($segAudioUrl)
                                                    <div x-data="{ playing: false, audioEl: null }" style="margin-top: 0.25rem;">
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
                                                            style="display: flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.2rem; color: #a78bfa; font-size: 0.55rem; cursor: pointer;"
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
                                                <div style="padding: 0.5rem; background: rgba(255,255,255,0.03); border-left: 3px solid rgba(14, 165, 233, 0.6); border-radius: 0 0.3rem 0.3rem 0;">
                                                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem;">
                                                        <span style="font-size: 0.85rem;">🎙️</span>
                                                        <span style="font-size: 0.55rem; font-weight: 600; color: white; padding: 0.1rem 0.3rem; background: rgba(14, 165, 233, 0.4); border-radius: 0.2rem;">NARRATOR</span>
                                                        <span style="flex: 1;"></span>
                                                        <span style="font-size: 0.5rem; padding: 0.1rem 0.25rem; background: rgba(14,165,233,0.2); color: #67e8f9; border-radius: 0.15rem;">TTS</span>
                                                    </div>
                                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.8); line-height: 1.4;">
                                                        {{ Str::limit($narration, 150) }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endforelse
                                    </div>

                                    {{-- Panel Footer with Full Inspect Link --}}
                                    <div style="padding: 0.5rem 0.65rem; border-top: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                        <button
                                            type="button"
                                            wire:click="openSceneTextInspector({{ $index }})"
                                            @click="voicePanelOpen = false"
                                            style="width: 100%; padding: 0.35rem 0.5rem; background: linear-gradient(135deg, rgba(139,92,246,0.25), rgba(6,182,212,0.2)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.3rem; color: white; font-size: 0.65rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem; transition: all 0.2s;"
                                        >
                                            <span>🔍</span>
                                            <span>{{ __('Open Full Inspector') }}</span>
                                            <span style="opacity: 0.6;">({{ __('prompts, metadata') }})</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Prompt Section - Compact --}}
                    <div style="padding: 0.5rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('PROMPT') }}</span>
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span style="font-size: 0.65rem; padding: 0.15rem 0.35rem; background: rgba(6,182,212,0.15); color: #67e8f9; border-radius: 0.2rem;">
                                    ⏱️ {{ $scene['duration'] ?? 8 }}s
                                </span>
                                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.4);">
                                    {{ $scene['transition'] ?? 'cut' }}
                                </span>
                            </div>
                        </div>
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.65); line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            {{ Str::limit($prompt, 120) }}
                        </div>
                    </div>
                </div>
            @endforeach
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
                                            <span style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">{{ $shotIndex + 1 }}</span>
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
                                         wire:click="openAIEditModal({{ $index }})"
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
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                                    {{ __('No image yet') }}
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Scene Info') }}</div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <span style="font-size: 1.25rem; font-weight: 700; color: #a78bfa;" x-text="'#' + (sidePanel.sceneIndex + 1)"></span>
                            <span style="font-size: 0.8rem; color: rgba(255,255,255,0.7);" x-text="$wire.script?.scenes?.[sidePanel.sceneIndex]?.title || '{{ __('Scene') }}'"></span>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Duration') }}</div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="number"
                                   min="1"
                                   max="60"
                                   style="width: 80px; padding: 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.85rem;"
                                   :value="$wire.script?.scenes?.[sidePanel.sceneIndex]?.duration || 8"
                                   @change="$wire.set(`script.scenes.${sidePanel.sceneIndex}.duration`, $event.target.value)">
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">{{ __('seconds') }}</span>
                        </div>
                    </div>

                    <div class="vw-side-panel-section">
                        <div class="vw-side-panel-label">{{ __('Quick Actions') }}</div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button type="button"
                                    @click="$wire.openAIEditModal(sidePanel.sceneIndex)"
                                    style="width: 100%; padding: 0.6rem; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(139,92,246,0.2)); border: 1px solid rgba(236,72,153,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                ✨ {{ __('Edit with AI') }}
                            </button>
                            <button type="button"
                                    @click="$wire.generateImage(sidePanel.sceneIndex, $wire.script?.scenes?.[sidePanel.sceneIndex]?.id)"
                                    style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🔄 {{ __('Regenerate') }}
                            </button>
                            <button type="button"
                                    @click="$wire.openMultiShotModal(sidePanel.sceneIndex)"
                                    style="width: 100%; padding: 0.6rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
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
                        style="margin-left: auto; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 1.25rem; line-height: 1;">
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
                        <span style="color: rgba(255,255,255,0.3);">-</span>
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

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); text-align: center;">
                <span style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">
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
        <span x-text="toast.message" style="color: white; font-size: 0.85rem;"></span>
        <button type="button"
                @click="toast.show = false"
                style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 1rem; padding: 0 0.25rem;">
            ×
        </button>
    </div>

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
