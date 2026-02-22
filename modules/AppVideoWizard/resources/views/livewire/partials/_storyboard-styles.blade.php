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
        background: var(--at-bg-deep);
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
        background: var(--at-bg-surface-solid);
        border-bottom: 1px solid var(--at-border-strong);
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
        color: var(--vw-text);
        font-size: 1rem;
        letter-spacing: -0.02em;
    }

    .vw-storyboard-subtitle {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
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
        background: rgba(var(--vw-primary-rgb), 0.06);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
    }

    .vw-storyboard-pill .pill-value {
        font-weight: 600;
        color: var(--vw-primary);
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
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s;
    }

    .vw-settings-toggle:hover {
        background: var(--vw-border);
        border-color: var(--vw-border);
    }

    .vw-settings-toggle.active {
        background: rgba(var(--vw-primary-rgb), 0.08);
        border-color: var(--vw-border-accent);
        color: var(--vw-primary);
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

    /* Icon Rail removed — all settings in single scrollable sidebar */

    /* Settings Sidebar - Collapsible & Resizable */
    .vw-settings-sidebar {
        width: 320px;
        min-width: 240px;
        max-width: 500px;
        background: var(--at-bg-surface-solid);
        border-right: 1px solid var(--at-border-strong);
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
        background: rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 2px;
        opacity: 0;
        transition: opacity 0.2s ease, background 0.2s ease;
    }

    .vw-sidebar-resize-handle:hover::before,
    .vw-settings-sidebar.resizing .vw-sidebar-resize-handle::before {
        opacity: 1;
        background: var(--vw-border-focus);
    }

    .vw-settings-sidebar.resizing .vw-sidebar-resize-handle::before {
        background: var(--vw-primary);
    }

    .vw-sidebar-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-sidebar-title {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--vw-text-secondary);
        font-weight: 600;
    }

    .vw-sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
    }

    /* Sidebar Accordion */
    .vw-sidebar-accordion {
        border: 1px solid var(--at-border);
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        overflow: hidden;
        background: var(--at-bg-surface-solid);
    }

    .vw-sidebar-accordion-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.55rem 0.75rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--at-text);
        letter-spacing: 0.01em;
        transition: background 0.15s;
    }

    .vw-sidebar-accordion-header:hover {
        background: var(--at-bg-hover);
    }

    .vw-accordion-chevron {
        transition: transform 0.2s ease;
        color: var(--at-text-muted);
        flex-shrink: 0;
    }

    .vw-accordion-chevron--open {
        transform: rotate(180deg);
    }

    /* Topbar Multi-Shot Toggle */
    .vw-topbar-mode-btn {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.75rem;
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--at-border);
        border-radius: 0.5rem;
        color: var(--at-text);
        cursor: pointer;
        font-size: 0.72rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .vw-topbar-mode-btn:hover {
        background: var(--at-bg-hover);
    }

    .vw-topbar-mode-btn.active {
        background: var(--at-primary);
        color: white;
        border-color: var(--at-primary);
    }

    .vw-topbar-mode-badge {
        font-size: 0.6rem;
        padding: 0.1rem 0.3rem;
        background: rgba(255,255,255,0.25);
        border-radius: 0.2rem;
        font-weight: 700;
    }

    /* Sidebar Section */
    .vw-sidebar-section {
        margin-bottom: 0.75rem;
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.03);
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
        background: rgba(0,0,0,0.02);
    }

    .vw-sidebar-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-sidebar-section-title .icon {
        font-size: 0.9rem;
    }

    .vw-sidebar-section-chevron {
        font-size: 0.6rem;
        color: var(--vw-text-secondary);
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
        background: rgba(var(--vw-primary-rgb), 0.04);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.06);
        border-radius: 0.5rem;
        padding: 0.6rem;
        text-align: center;
    }

    .vw-sidebar-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--vw-primary);
        line-height: 1;
    }

    .vw-sidebar-stat-label {
        font-size: 0.6rem;
        color: var(--vw-text-secondary);
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
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-sidebar-model-btn:hover {
        background: rgba(var(--vw-primary-rgb), 0.04);
        border-color: rgba(var(--vw-primary-rgb), 0.12);
    }

    .vw-sidebar-model-btn.selected {
        background: rgba(var(--vw-primary-rgb), 0.06);
        border-color: var(--vw-border-accent);
    }

    .vw-sidebar-model-btn.selected::before {
        content: '✓';
        margin-right: 0.5rem;
        color: var(--vw-primary);
    }

    .vw-sidebar-model-name {
        font-size: 0.75rem;
        color: var(--vw-text);
        font-weight: 500;
    }

    .vw-sidebar-model-cost {
        font-size: 0.65rem;
        color: #d97706;
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
        color: var(--vw-text);
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
        background: var(--vw-border);
        border-radius: 10px;
        transition: background 0.2s ease;
    }

    .vw-sidebar-toggle-switch.active .vw-sidebar-toggle-track {
        background: var(--vw-border-focus);
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
        background: var(--vw-primary);
    }

    /* Visual Style in Sidebar */
    .vw-sidebar-style-preview {
        padding: 0.6rem;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(236, 72, 153, 0.1));
        border: 1px solid rgba(var(--vw-primary-rgb), 0.08);
        border-radius: 0.4rem;
        margin-bottom: 0.75rem;
    }

    .vw-sidebar-style-active {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
        margin-bottom: 0.35rem;
    }

    .vw-sidebar-style-desc {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
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
        color: var(--vw-text-secondary);
        text-transform: uppercase;
    }

    .vw-sidebar-style-select select {
        padding: 0.4rem 0.5rem;
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--vw-border);
        border-radius: 0.3rem;
        color: var(--vw-text);
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
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 0.5rem;
        cursor: pointer;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .vw-sidebar-bible-card:hover {
        border-color: var(--vw-border-focus);
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(var(--vw-primary-rgb), 0.08);
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
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6, 182, 212, 0.1));
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
        color: var(--vw-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vw-sidebar-bible-card-tag {
        font-size: 0.55rem;
        color: rgba(var(--vw-primary-rgb), 0.9);
    }

    .vw-sidebar-bible-card-add {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-style: dashed;
        border-color: rgba(var(--vw-primary-rgb), 0.12);
        background: rgba(var(--vw-primary-rgb), 0.02);
    }

    .vw-sidebar-bible-card-add:hover {
        background: rgba(var(--vw-primary-rgb), 0.06);
        border-color: var(--vw-border-focus);
    }

    .vw-sidebar-bible-card-add-icon {
        font-size: 1.5rem;
        color: var(--vw-border-focus);
        margin-bottom: 0.25rem;
    }

    .vw-sidebar-bible-card-add-text {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
    }

    .vw-sidebar-bible-icon {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }

    .vw-sidebar-bible-label {
        font-size: 0.65rem;
        color: var(--vw-text);
    }

    .vw-sidebar-bible-count {
        font-size: 0.6rem;
        color: var(--vw-text-secondary);
    }

    /* Collapse Button */
    .vw-sidebar-collapse-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem;
        margin: 0.5rem 0.75rem;
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 0.4rem;
        color: var(--vw-text-secondary);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-sidebar-collapse-btn:hover {
        background: rgba(0,0,0,0.04);
        color: var(--vw-text);
    }

    /* Main Workspace */
    .vw-workspace {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--at-bg-deep);
    }

    .vw-workspace-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--at-border-strong);
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
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--at-glass);
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
        border-top: 1px solid rgba(0,0,0,0.04);
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
        color: var(--vw-text);
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
        color: var(--vw-text);
    }

    .vw-badge-new {
        background: linear-gradient(135deg, #10b981, #06b6d4);
        color: var(--vw-text);
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
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.03);
        color: var(--vw-text);
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
        border-color: var(--vw-border-accent);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-model-btn.selected {
        border-color: var(--vw-primary);
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text);
    }

    .vw-model-btn-name {
        font-weight: 600;
    }

    .vw-model-btn-cost {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
    }

    .vw-model-btn.selected .vw-model-btn-cost {
        color: var(--vw-text);
    }

    .vw-model-description {
        color: var(--vw-text-secondary);
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
        color: var(--vw-text-secondary);
    }

    .vw-style-select {
        width: 100%;
        padding: 0.6rem 0.75rem;
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        color: var(--vw-text);
        font-size: 0.8rem;
        cursor: pointer;
    }

    .vw-style-select:focus {
        border-color: var(--vw-border-focus);
        outline: none;
    }

    .vw-style-hint {
        color: var(--vw-text-secondary);
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
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
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
        color: var(--vw-text);
        font-size: 0.85rem;
    }

    .vw-memory-desc {
        color: var(--vw-text-secondary);
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
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.35rem;
        color: var(--vw-text);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-edit-btn:hover {
        background: var(--vw-border);
        border-color: var(--vw-border);
    }

    .vw-memory-checkbox {
        width: 18px;
        height: 18px;
        accent-color: var(--vw-primary);
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
        color: var(--vw-text-secondary);
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
    }

    .vw-memory-tab:hover {
        color: var(--vw-text);
    }

    .vw-memory-tab.active {
        color: var(--vw-text);
    }

    .vw-memory-tab-icon {
        font-size: 1rem;
    }

    .vw-memory-tab-label {
        font-weight: 600;
    }

    .vw-memory-tab-count {
        background: rgba(var(--vw-primary-rgb), 0.12);
        padding: 0.1rem 0.4rem;
        border-radius: 0.75rem;
        font-size: 0.65rem;
        font-weight: 700;
        color: var(--vw-text-secondary);
        min-width: 1.25rem;
        text-align: center;
    }

    .vw-memory-tab.active .vw-memory-tab-count {
        background: var(--vw-border-focus);
        color: var(--vw-text);
    }

    .vw-memory-tab-indicator {
        position: absolute;
        bottom: 0.25rem;
        height: calc(100% - 0.5rem);
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6, 182, 212, 0.2));
        border-radius: 0.5rem;
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
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
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6, 182, 212, 0.15));
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 0.5rem;
        color: var(--vw-primary);
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-memory-add-btn:hover {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6, 182, 212, 0.25));
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--vw-primary-rgb), 0.08);
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
        background: var(--at-bg-surface-solid);
        box-shadow: var(--at-glass);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .vw-memory-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 0.75rem;
        padding: 1px;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6, 182, 212, 0.2));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .vw-memory-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 40px rgba(var(--vw-primary-rgb), 0.08), 0 4px 20px rgba(0, 0, 0, 0.4);
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
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6, 182, 212, 0.05));
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
        background: linear-gradient(to top, rgba(var(--vw-primary-rgb), 0.4) 0%, var(--vw-border-focus) 30%, transparent 100%);
    }

    .vw-memory-card-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--vw-text);
        text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: 0.02em;
    }

    .vw-memory-card-role {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
        margin-top: 0.15rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    /* Add card with dashed border */
    .vw-memory-card-add {
        background: transparent;
        border: 2px dashed rgba(var(--vw-primary-rgb), 0.12);
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
        border-color: var(--vw-border-focus);
        background: rgba(var(--vw-primary-rgb), 0.04);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(var(--vw-primary-rgb), 0.06);
    }

    .vw-memory-card-add-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6, 182, 212, 0.2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--vw-primary);
        transition: all 0.3s ease;
    }

    .vw-memory-card-add:hover .vw-memory-card-add-icon {
        transform: scale(1.1) rotate(90deg);
        background: linear-gradient(135deg, var(--vw-border-focus), rgba(6, 182, 212, 0.4));
    }

    .vw-memory-card-add-text {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
        font-weight: 500;
    }

    .vw-memory-empty {
        grid-column: span 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        color: var(--vw-text-secondary);
        gap: 0.75rem;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.02), rgba(6, 182, 212, 0.03));
        border-radius: 0.75rem;
        border: 1px dashed var(--vw-border);
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
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(var(--vw-primary-rgb), 0.04));
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
        color: var(--vw-text);
    }

    .vw-memory-dna-count {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
        background: rgba(6, 182, 212, 0.15);
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
    }

    .vw-memory-dna-btn {
        padding: 0.3rem 0.6rem;
        background: rgba(6, 182, 212, 0.2);
        border: 1px solid rgba(6, 182, 212, 0.4);
        border-radius: 0.35rem;
        color: var(--vw-text);
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
        color: var(--vw-text);
        font-size: 0.8rem;
    }

    .vw-specs-value {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--vw-text);
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
        color: var(--vw-text);
        font-size: 0.9rem;
    }

    .vw-chain-desc {
        color: var(--vw-text-secondary);
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
        color: #16a34a;
        border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .vw-chain-badge-processing {
        background: rgba(251, 191, 36, 0.2);
        color: #fcd34d;
        border: 1px solid rgba(251, 191, 36, 0.4);
        animation: vw-pulse 1.5s infinite;
    }

    .vw-chain-badge-idle {
        background: var(--vw-border);
        color: var(--vw-text-secondary);
        border: 1px solid var(--vw-border);
    }

    .vw-chain-stats {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.04);
    }

    .vw-chain-stat {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        background: rgba(var(--vw-primary-rgb), 0.06);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 0.25rem;
        color: var(--vw-text);
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
        color: var(--vw-text);
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
        color: var(--vw-text-secondary);
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
        background: linear-gradient(135deg, var(--vw-primary), #06b6d4);
        border: none;
        border-radius: 0.5rem;
        color: var(--vw-text);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-generate-all-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px var(--vw-border-accent);
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
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
        border-radius: 1rem;
        overflow: hidden;
        transition: border-color 0.2s, box-shadow 0.2s;
        /* Anti-jitter: fixed dimensions prevent layout shift */
        contain: layout style;
        will-change: border-color, box-shadow;
    }

    .vw-scene-card:hover {
        border-color: var(--vw-border-accent);
        box-shadow: var(--at-glass-hover);
    }

    /* Scene Card State Indicators */
    .vw-scene-card--empty { border-left: 3px solid var(--at-border-strong); opacity: 0.85; }
    .vw-scene-card--generating { border-left: 3px solid var(--at-primary); }
    @keyframes vw-pulse-border { 0%, 100% { border-left-color: var(--at-primary); } 50% { border-left-color: rgba(3, 252, 244, 0.3); } }
    .vw-scene-card--generating { animation: vw-pulse-border 1.5s ease-in-out infinite; }
    .vw-scene-card--ready { border-left: 3px solid var(--at-success); }
    .vw-scene-card--decomposed { border-left: 3px solid var(--at-primary); }
    .vw-scene-card--animated { border-left: 3px solid #0891b2; }
    .vw-scene-card--error { border-left: 3px solid var(--at-danger); }

    /* State badge pills */
    .vw-card-state-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.45rem;
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .vw-card-state-badge--generating {
        background: rgba(3, 252, 244, 0.1);
        color: var(--at-primary-text);
        animation: vw-pulse 1.5s infinite;
    }
    .vw-card-state-badge--ai { background: rgba(3, 252, 244, 0.1); color: var(--at-primary-text); }
    .vw-card-state-badge--stock { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .vw-card-state-badge--upload { background: rgba(139, 92, 246, 0.1); color: #7c3aed; }
    .vw-card-state-badge--shots { background: rgba(3, 252, 244, 0.08); color: var(--at-primary-text); }
    .vw-card-state-badge--animated { background: rgba(8, 145, 178, 0.1); color: #0891b2; }
    .vw-card-state-badge--error { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

    /* Scene Image Container - Larger */
    .vw-scene-image-container {
        position: relative;
        aspect-ratio: 16/9;
        background: #f1f5f9;
        overflow: hidden;
        /* Anti-jitter: prevent layout recalculations */
        contain: strict;
    }

    /* Card Action Bar — below image */
    .vw-card-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.5rem 0.75rem;
        border-top: 1px solid var(--at-border-strong);
        background: var(--at-bg-surface-solid);
    }
    .vw-card-actions-spacer { flex: 1; }
    .vw-card-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.6rem;
        border-radius: 0.4rem;
        border: 1px solid transparent;
        background: transparent;
        color: var(--at-text-secondary);
        cursor: pointer;
        font-size: 0.72rem;
        font-weight: 500;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .vw-card-action-btn:hover {
        background: rgba(var(--at-primary-rgb), 0.06);
        color: var(--at-text);
        border-color: var(--at-border-accent);
    }
    .vw-card-action-btn--primary {
        background: rgba(var(--at-primary-rgb), 0.08);
        color: var(--at-primary-text);
        border-color: var(--at-border-accent);
    }
    .vw-card-action-btn--primary:hover {
        background: rgba(var(--at-primary-rgb), 0.15);
    }
    .vw-card-action-btn--ghost {
        color: var(--at-text-muted);
    }
    .vw-card-action-btn--ghost:hover {
        color: var(--at-text);
        background: rgba(0,0,0,0.04);
    }

    /* Overflow dropdown */
    .vw-card-overflow {
        position: relative;
    }
    .vw-card-overflow-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 0.4rem;
        border: 1px solid transparent;
        background: transparent;
        color: var(--at-text-muted);
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.15s ease;
    }
    .vw-card-overflow-trigger:hover {
        background: rgba(0,0,0,0.04);
        color: var(--at-text);
    }
    .vw-card-overflow-menu {
        position: absolute;
        bottom: 100%;
        right: 0;
        min-width: 180px;
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
        border-radius: 0.5rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        padding: 0.25rem;
        z-index: 1000050;
        margin-bottom: 0.35rem;
    }
    .vw-card-overflow-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.45rem 0.65rem;
        border: none;
        background: transparent;
        color: var(--at-text);
        cursor: pointer;
        font-size: 0.72rem;
        border-radius: 0.35rem;
        transition: background 0.1s;
    }
    .vw-card-overflow-item:hover {
        background: rgba(var(--at-primary-rgb), 0.06);
    }
    .vw-card-overflow-divider {
        height: 1px;
        background: var(--at-border-strong);
        margin: 0.25rem 0.5rem;
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
        background: var(--at-bg-surface);
        border: 1px solid var(--at-border-strong);
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
            rgba(var(--vw-primary-rgb), 0.03) 0%,
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
            rgba(255, 255, 255, 0.6),
            rgba(255, 255, 255, 0.7));
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
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6, 182, 212, 0.2));
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 0.75rem;
        animation: vw-float 3s ease-in-out infinite;
        box-shadow: 0 4px 16px rgba(var(--vw-primary-rgb), 0.06);
    }

    @keyframes vw-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    .vw-scene-empty-text {
        color: var(--vw-text-secondary);
        font-size: 0.8rem;
        margin-bottom: 0.85rem;
        letter-spacing: 0.02em;
    }

    .vw-scene-empty.has-bg-image .vw-scene-empty-text {
        color: var(--vw-text);
        text-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
        color: var(--vw-text);
        text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .vw-preview-actions {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .vw-preview-btn {
        padding: 0.35rem 0.6rem;
        border-radius: 0.35rem;
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.3);
        backdrop-filter: blur(8px);
        color: var(--vw-text);
        font-size: 0.65rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .vw-preview-btn:hover {
        background: rgba(0, 0, 0, 0.7);
        border-color: var(--vw-text-secondary);
        transform: translateY(-1px);
    }

    .vw-preview-btn.collage {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.5), var(--vw-border-focus));
        border-color: rgba(236, 72, 153, 0.4);
    }

    .vw-preview-btn.collage:hover {
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.7), rgba(var(--vw-primary-rgb), 0.25));
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
        background: var(--vw-primary);
        border: none;
        border-radius: 0.4rem;
        color: var(--vw-text);
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(var(--vw-primary-rgb), 0.12);
        display: flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    .vw-edit-shots-btn:hover {
        background: linear-gradient(135deg, var(--vw-primary), var(--vw-primary));
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--vw-border-accent);
    }

    .vw-edit-shots-btn:active {
        transform: translateY(0);
    }

    .vw-edit-shots-btn.vw-btn-loading {
        opacity: 0.8;
        cursor: wait;
    }

    /* Single Generate Button - Full-width prominent CTA */
    .vw-scene-generate-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        max-width: 280px;
        padding: 0.7rem 1.25rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(var(--vw-primary-rgb), 0.25);
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.1), rgba(236, 72, 153, 0.08));
        backdrop-filter: blur(8px);
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
        position: relative;
    }

    .vw-scene-generate-btn:hover {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.18), rgba(236, 72, 153, 0.15));
        border-color: var(--vw-border-focus);
        box-shadow: 0 4px 16px rgba(var(--vw-primary-rgb), 0.12);
        transform: translateY(-1px);
    }

    .vw-scene-generate-btn:disabled {
        opacity: 0.7;
        cursor: wait;
        transform: none;
    }

    .vw-scene-generate-cost {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
        background: rgba(0, 0, 0, 0.15);
        padding: 0.15rem 0.5rem;
        border-radius: 1rem;
        margin-left: 0.25rem;
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
        border: 1px solid var(--vw-border);
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
        color: var(--vw-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    .vw-shot-timeline-count {
        font-size: 0.6rem;
        color: rgba(var(--vw-primary-rgb), 0.4);
        background: rgba(var(--vw-primary-rgb), 0.06);
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
        border-color: var(--vw-border-focus);
        transform: scale(1.08);
    }

    .vw-shot-timeline-thumb.active {
        border-color: var(--vw-primary);
        box-shadow: 0 0 12px var(--vw-border-accent);
    }

    .vw-shot-timeline-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-shot-timeline-thumb-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6, 182, 212, 0.2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: var(--vw-text-secondary);
    }

    .vw-shot-timeline-thumb-number {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        font-size: 0.5rem;
        color: var(--vw-text);
        padding: 0.1rem 0.2rem;
        text-align: center;
        font-weight: 600;
    }

    /* Progress bar under timeline */
    .vw-shot-timeline-progress {
        height: 3px;
        background: var(--vw-border);
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .vw-shot-timeline-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--vw-primary), #06b6d4);
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
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.03);
        color: var(--vw-text);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .vw-quick-action-btn:hover {
        background: rgba(var(--vw-primary-rgb), 0.08);
        border-color: var(--vw-border-accent);
    }

    .vw-quick-action-btn.primary {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6, 182, 212, 0.3));
        border-color: var(--vw-border-accent);
    }

    .vw-quick-action-btn.primary:hover {
        background: linear-gradient(135deg, var(--vw-border-accent), rgba(6, 182, 212, 0.4));
    }

    /* Generating State */
    .vw-scene-generating {
        height: 100%;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(var(--vw-primary-rgb), 0.04);
        gap: 1rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-spinner {
        width: 2.5rem;
        height: 2.5rem;
        border: 3px solid rgba(var(--vw-primary-rgb), 0.12);
        border-top-color: var(--vw-primary);
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-btn-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid var(--vw-text-secondary);
        border-top-color: white;
        border-radius: 50%;
        animation: vw-spin 0.6s linear infinite;
        vertical-align: middle;
    }

    /* Voice Types Button */
    .vw-voice-types-btn:hover {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(6,182,212,0.25)) !important;
        border-color: var(--vw-border-focus) !important;
        transform: translateY(-1px);
    }

    .vw-generating-text {
        color: var(--vw-text-secondary);
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
        color: #d97706;
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
    .vw-shot-badge-est { background: rgba(3, 252, 244, 0.25); color: rgba(3, 252, 244, 0.95); }

    /* Purpose Badges */
    .vw-shot-badge-ots { background: rgba(var(--vw-primary-rgb), 0.08); color: rgba(var(--vw-primary-rgb), 0.4); }
    .vw-shot-badge-reaction { background: rgba(236, 72, 153, 0.25); color: rgba(236, 72, 153, 0.95); }
    .vw-shot-badge-two-shot { background: rgba(20, 184, 166, 0.25); color: rgba(20, 184, 166, 0.95); }

    /* Camera Movement */
    .vw-shot-badge-movement { background: rgba(168, 162, 158, 0.2); color: rgba(168, 162, 158, 0.9); }

    /* Climax indicator - special gradient */
    .vw-shot-badge-climax {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(236, 72, 153, 0.3));
        color: var(--vw-text);
        border: 1px solid var(--vw-border-focus);
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
        color: rgba(var(--vw-primary-rgb), 0.4);
        font-weight: 600;
        font-size: 0.7rem;
    }

    .vw-dialogue-text {
        color: var(--vw-text);
        line-height: 1.4;
    }

    .vw-dialogue-more {
        color: var(--vw-text-secondary);
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
        background: var(--vw-border);
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
        background: linear-gradient(90deg, rgba(var(--vw-primary-rgb), 0.4), rgba(236, 72, 153, 0.9));
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
        stroke: var(--vw-border);
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
        outline: 2px solid var(--vw-border-focus);
        outline-offset: 2px;
    }

    /* Consistent section spacing */
    .vw-card-section {
        padding: 0.5rem 0;
        border-top: 1px solid var(--vw-border);
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
        background: rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 2px;
    }

    .vw-scene-dialogue::-webkit-scrollbar-thumb:hover,
    .vw-modal-content::-webkit-scrollbar-thumb:hover,
    .vw-storyboard-content::-webkit-scrollbar-thumb:hover {
        background: var(--vw-border-focus);
    }

    /* Empty state styling */
    .vw-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: var(--vw-text-secondary);
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
        border-color: var(--vw-border-focus);
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
            rgba(0,0,0,0.02) 0%,
            rgba(0,0,0,0.04) 50%,
            rgba(0,0,0,0.02) 100%
        );
        background-size: 200% 100%;
        animation: vw-shimmer 1.5s ease-in-out infinite;
        border-radius: 0.5rem;
    }

    .vw-skeleton-card {
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 1rem;
        overflow: hidden;
    }

    .vw-skeleton-image {
        aspect-ratio: 16/9;
        background: linear-gradient(
            90deg,
            rgba(0,0,0,0.02) 0%,
            rgba(0,0,0,0.04) 50%,
            rgba(0,0,0,0.02) 100%
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
            rgba(0,0,0,0.03) 0%,
            var(--vw-border) 50%,
            rgba(0,0,0,0.03) 100%
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
        background: var(--at-bg-surface-solid);
        backdrop-filter: blur(12px);
        border: 1px solid var(--at-border-strong);
        border-radius: 0.75rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0,0,0,0.03);
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
        background: rgba(0,0,0,0.04);
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .vw-floating-toolbar-btn:hover {
        background: rgba(var(--vw-primary-rgb), 0.08);
        border-color: var(--vw-border-accent);
    }

    .vw-floating-toolbar-btn.primary {
        background: linear-gradient(135deg, var(--vw-border-accent), rgba(6, 182, 212, 0.3));
        border-color: var(--vw-border-focus);
    }

    .vw-floating-toolbar-btn.primary:hover {
        background: linear-gradient(135deg, var(--vw-border-focus), rgba(6, 182, 212, 0.5));
    }

    .vw-floating-toolbar-btn.danger:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.4);
        color: #f87171;
    }

    .vw-floating-toolbar-divider {
        width: 1px;
        height: 1.25rem;
        background: var(--vw-border);
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
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--vw-border);
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
        color: var(--vw-text-secondary);
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.15s ease;
    }

    .vw-view-mode-btn:hover {
        color: var(--vw-text);
        background: rgba(0,0,0,0.04);
    }

    .vw-view-mode-btn.active {
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text);
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
        color: var(--vw-text-secondary);
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
        border-left: 1px solid var(--vw-border);
        padding-left: 0.5rem;
    }

    .vw-timeline-row {
        display: flex;
        align-items: stretch;
        min-height: 80px;
        background: rgba(0,0,0,0.02);
        border-radius: 0.5rem;
        overflow: hidden;
        transition: background 0.2s;
    }

    .vw-timeline-row:hover {
        background: rgba(var(--vw-primary-rgb), 0.02);
    }

    .vw-timeline-scene-info {
        width: 120px;
        flex-shrink: 0;
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.25rem;
        border-right: 1px solid rgba(0,0,0,0.04);
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-timeline-scene-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-timeline-scene-duration {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
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
        border-color: var(--vw-border-focus);
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
        color: var(--vw-text);
    }

    .vw-timeline-shot.pending {
        background: rgba(0,0,0,0.03);
        border: 2px dashed var(--vw-border);
        min-width: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-timeline-shot.generating {
        background: rgba(var(--vw-primary-rgb), 0.04);
        border: 2px solid var(--vw-border-accent);
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
        background: rgba(var(--vw-primary-rgb), 0.04);
        border-bottom: 1px solid rgba(var(--vw-primary-rgb), 0.08);
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
        color: var(--vw-text);
    }

    .vw-progress-title .generating-dot {
        width: 8px;
        height: 8px;
        background: var(--vw-primary);
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }

    .vw-progress-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.75rem;
        color: var(--vw-text-secondary);
    }

    .vw-progress-bar-container {
        position: relative;
        height: 8px;
        background: var(--vw-border);
        border-radius: 4px;
        overflow: hidden;
    }

    .vw-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--vw-primary), #06b6d4);
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
            var(--vw-text-secondary) 50%,
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
        color: var(--vw-text-secondary);
    }

    .vw-progress-step {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-progress-step .step-icon {
        width: 16px;
        height: 16px;
        border: 2px solid var(--vw-border-focus);
        border-top-color: var(--vw-primary);
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
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.03);
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.7rem;
        transition: all 0.15s;
    }

    .vw-progress-action-btn:hover {
        background: var(--vw-border);
        border-color: var(--vw-text-secondary);
    }

    .vw-progress-action-btn.cancel:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.4);
        color: #f87171;
    }

    /* ========================================
       PHASE 2: UI UPGRADE - Bento Grid Layout
       ======================================== */

    .vw-stats-strip {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: rgba(0,0,0,0.02);
        border: 1px solid var(--at-border);
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }

    .vw-stats-strip-item {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .vw-stats-strip-value {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1;
    }

    .vw-stats-strip-label {
        font-size: 0.65rem;
        color: var(--at-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .vw-stats-strip-divider {
        width: 1px;
        height: 20px;
        background: var(--at-border);
    }

    /* ========================================
       PHASE 2: UI UPGRADE - Collapsible Panels
       ======================================== */

    .vw-collapsible-section {
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 0.75rem;
        margin-bottom: 0.75rem;
        overflow: hidden;
        background: rgba(0,0,0,0.02);
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
        background: rgba(0,0,0,0.02);
    }

    .vw-collapsible-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--vw-text);
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
        color: var(--vw-text-secondary);
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
        background: rgba(0,0,0,0.02);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-bible-preview-card:hover {
        border-color: var(--vw-border-focus);
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
        background: rgba(var(--vw-primary-rgb), 0.04);
        font-size: 2rem;
    }

    .vw-bible-preview-info {
        padding: 0.5rem;
    }

    .vw-bible-preview-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--vw-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vw-bible-preview-tag {
        font-size: 0.6rem;
        color: rgba(var(--vw-primary-rgb), 0.3);
        margin-top: 0.15rem;
    }

    .vw-bible-add-card {
        background: rgba(var(--vw-primary-rgb), 0.02);
        border: 2px dashed rgba(var(--vw-primary-rgb), 0.12);
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
        border-color: var(--vw-border-focus);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-bible-add-icon {
        font-size: 1.5rem;
        color: var(--vw-border-focus);
        margin-bottom: 0.25rem;
    }

    .vw-bible-add-text {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
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
        background: var(--at-bg-surface-solid);
        border-left: 1px solid var(--at-border-strong);
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
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }

    .vw-side-panel-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-side-panel-close {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.35rem;
        border: none;
        background: rgba(0,0,0,0.04);
        color: var(--vw-text);
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
        color: var(--vw-text-secondary);
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
        background: rgba(var(--vw-primary-rgb), 0.06);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 0.35rem;
        font-size: 0.65rem;
        font-family: monospace;
        color: var(--vw-primary);
    }

    .vw-mention-hint-label {
        color: var(--vw-text-secondary);
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
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        z-index: 100;
        backdrop-filter: blur(12px);
        margin-top: 0.25rem;
    }

    .vw-mention-group-header {
        padding: 0.5rem 0.75rem;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--vw-text-secondary);
        background: rgba(0,0,0,0.02);
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }

    .vw-mention-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        cursor: pointer;
        transition: background 0.15s ease;
        border-bottom: 1px solid rgba(0,0,0,0.02);
    }

    .vw-mention-item:hover,
    .vw-mention-item.active {
        background: rgba(var(--vw-primary-rgb), 0.06);
    }

    .vw-mention-item-image {
        width: 32px;
        height: 32px;
        border-radius: 0.35rem;
        object-fit: cover;
        background: rgba(0,0,0,0.03);
    }

    .vw-mention-item-icon {
        width: 32px;
        height: 32px;
        border-radius: 0.35rem;
        background: rgba(var(--vw-primary-rgb), 0.08);
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
        color: var(--vw-text);
        font-weight: 500;
    }

    .vw-mention-item-tag {
        font-size: 0.65rem;
        color: var(--vw-primary);
        font-family: monospace;
    }

    .vw-mention-item-type {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text-secondary);
    }

    /* Brainstorm Suggestions Panel */
    .vw-brainstorm-panel {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(236, 72, 153, 0.08));
        border: 1px solid rgba(var(--vw-primary-rgb), 0.08);
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .vw-brainstorm-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: rgba(var(--vw-primary-rgb), 0.04);
        border-bottom: 1px solid rgba(var(--vw-primary-rgb), 0.06);
    }

    .vw-brainstorm-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--vw-text-secondary);
    }

    .vw-brainstorm-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        background: rgba(var(--vw-primary-rgb), 0.12);
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
        background: rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-brainstorm-suggestion:hover {
        background: rgba(var(--vw-primary-rgb), 0.04);
        border-color: rgba(var(--vw-primary-rgb), 0.12);
        transform: translateX(4px);
    }

    .vw-brainstorm-suggestion-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12), rgba(236, 72, 153, 0.3));
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
        color: var(--vw-text);
        line-height: 1.4;
    }

    .vw-brainstorm-suggestion-apply {
        padding: 0.35rem 0.6rem;
        background: rgba(var(--vw-primary-rgb), 0.08);
        border: 1px solid var(--vw-border-accent);
        border-radius: 0.35rem;
        color: var(--vw-text-secondary);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s ease;
        opacity: 0;
    }

    .vw-brainstorm-suggestion:hover .vw-brainstorm-suggestion-apply {
        opacity: 1;
    }

    .vw-brainstorm-suggestion-apply:hover {
        background: var(--vw-border-accent);
    }

    .vw-brainstorm-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.5rem;
        color: var(--vw-text-secondary);
        font-size: 0.8rem;
    }

    .vw-brainstorm-loading-spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(var(--vw-primary-rgb), 0.12);
        border-top-color: var(--vw-primary);
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-brainstorm-empty {
        text-align: center;
        padding: 1.5rem;
        color: var(--vw-text-secondary);
        font-size: 0.8rem;
    }

    .vw-brainstorm-refresh {
        padding: 0.35rem 0.7rem;
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--vw-border);
        border-radius: 0.35rem;
        color: var(--vw-text);
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
    }

    .vw-brainstorm-refresh:hover {
        background: rgba(var(--vw-primary-rgb), 0.06);
        border-color: var(--vw-border-accent);
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
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.08), rgba(6, 182, 212, 0.2));
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
        background: var(--vw-border);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .vw-generation-preview-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--vw-primary), #06b6d4);
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
        color: var(--vw-text);
        font-weight: 500;
        text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .vw-generation-preview-substatus {
        font-size: 0.65rem;
        color: var(--vw-text);
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
            rgba(255, 255, 255, 0.5) 50%,
            rgba(255, 255, 255, 1) 100%
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
        background: var(--vw-border);
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
        background: linear-gradient(90deg, #f59e0b, #d97706);
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
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        color: var(--vw-text);
        font-size: 0.85rem;
        min-height: 100px;
        resize: vertical;
        transition: border-color 0.2s ease;
    }

    .vw-prompt-textarea:focus {
        outline: none;
        border-color: var(--vw-border-focus);
    }

    .vw-prompt-textarea::placeholder {
        color: var(--vw-text-secondary);
    }

    /* @ Mention in textarea highlight */
    .vw-prompt-mention {
        color: var(--vw-primary);
        background: rgba(var(--vw-primary-rgb), 0.06);
        padding: 0.1rem 0.25rem;
        border-radius: 0.2rem;
    }

    /* Keyboard hints */
    .vw-keyboard-hint {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
        margin-top: 0.35rem;
    }

    .vw-keyboard-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.25rem;
        padding: 0.1rem 0.3rem;
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.2rem;
        font-size: 0.6rem;
        font-family: monospace;
        color: var(--vw-text-secondary);
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
            box-shadow: 0 0 0 0 var(--vw-border-accent);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 20px 10px rgba(var(--vw-primary-rgb), 0.04);
        }
    }

    /* Brainstorm panel transitions */
    .vw-brainstorm-panel[x-cloak] { display: none; }

    /* ========================================
       PHASE 4: POLISH & REFINEMENTS
       ======================================== */

    /* Enhanced Glassmorphism Effects */
    .vw-glass {
        background: rgba(0,0,0,0.02);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(0,0,0,0.04);
    }

    .vw-glass-strong {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--at-border-strong);
        box-shadow: var(--at-glass);
    }

    .vw-glass-accent {
        background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6, 182, 212, 0.1));
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.08);
    }

    /* Micro-animations: Button hover effects (Performance optimized) */
    .vw-btn-hover {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
    }

    .vw-btn-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--vw-primary-rgb), 0.08);
    }

    .vw-btn-hover:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(var(--vw-primary-rgb), 0.06);
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
        box-shadow: 0 0 20px var(--vw-border-accent), 0 0 40px rgba(var(--vw-primary-rgb), 0.04);
    }

    /* Micro-animations: Scale on focus for inputs */
    .vw-input-focus {
        transition: all 0.2s ease;
    }

    .vw-input-focus:focus {
        transform: scale(1.01);
        border-color: var(--vw-border-focus);
        box-shadow: 0 0 0 3px rgba(var(--vw-primary-rgb), 0.04);
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
            rgba(0,0,0,0.02) 0%,
            rgba(0,0,0,0.04) 50%,
            rgba(0,0,0,0.02) 100%
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
        background: var(--vw-text-secondary);
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
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--vw-border);
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-family: monospace;
        color: var(--vw-text-secondary);
        margin-left: 0.5rem;
    }

    /* Theme variables mapped to platform tokens */
    .vw-themed-bg {
        background-color: var(--at-bg-deep);
        color: var(--at-text);
    }

    .vw-themed-card {
        background-color: var(--at-bg-surface-solid);
        border-color: var(--at-border-strong);
    }

    .vw-themed-text {
        color: var(--at-text);
    }

    .vw-themed-text-secondary {
        color: var(--at-text-secondary);
    }

    .vw-themed-text-muted {
        color: var(--at-text-muted);
    }

    /* Keyboard shortcuts overlay */
    .vw-shortcuts-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000100;
        padding: 2rem;
    }

    .vw-shortcuts-modal {
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
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
        color: var(--vw-text);
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
        color: var(--vw-text-secondary);
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }

    .vw-shortcut-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-shortcut-label {
        color: var(--vw-text);
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
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--vw-border);
        border-radius: 0.35rem;
        font-size: 0.7rem;
        font-family: monospace;
        color: var(--vw-text);
    }

    /* Focus indicator for accessibility */
    .vw-focus-ring:focus-visible {
        outline: 2px solid var(--vw-border-focus);
        outline-offset: 2px;
    }

    /* Smooth scrollbar for webkit browsers */
    .vw-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .vw-scrollbar::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.02);
        border-radius: 3px;
    }

    .vw-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 3px;
    }

    .vw-scrollbar::-webkit-scrollbar-thumb:hover {
        background: var(--vw-border-focus);
    }

    /* Toast notification styles */
    .vw-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        padding: 0.75rem 1rem;
        background: var(--at-bg-surface-solid);
        border: 1px solid var(--at-border-strong);
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
