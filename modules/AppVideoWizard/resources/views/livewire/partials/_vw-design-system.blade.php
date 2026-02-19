{{-- Video Wizard Design System --}}
{{-- Centralized tokens, component classes, and animations --}}
{{-- Include once in video-wizard.blade.php --}}

<style>
/* ============================================
   VIDEO WIZARD DESIGN SYSTEM
   ============================================ */

/* --- Design Tokens --- */
.video-wizard {
    --vw-bg-deep: #071437;
    --vw-bg-surface: #0f1d3d;
    --vw-bg-elevated: #162241;
    --vw-bg-hover: #1a2a50;
    --vw-bg-overlay: rgba(7, 20, 55, 0.92);

    --vw-border: rgba(75, 86, 117, 0.35);
    --vw-border-accent: rgba(103, 93, 255, 0.35);
    --vw-border-focus: rgba(103, 93, 255, 0.6);
    --vw-border-success: rgba(34, 197, 94, 0.35);

    --vw-primary: #675dff;
    --vw-primary-rgb: 103, 93, 255;
    --vw-primary-hover: #524acc;
    --vw-primary-soft: rgba(103, 93, 255, 0.15);
    --vw-primary-glow: 0 0 20px rgba(103, 93, 255, 0.2);

    --vw-success: #22c55e;
    --vw-success-soft: rgba(34, 197, 94, 0.15);
    --vw-warning: #f59e0b;
    --vw-warning-soft: rgba(245, 158, 11, 0.15);
    --vw-danger: #ef4444;
    --vw-danger-soft: rgba(239, 68, 68, 0.15);
    --vw-info: #22d3ee;
    --vw-info-soft: rgba(34, 211, 238, 0.15);

    --vw-text: #dbdfe9;
    --vw-text-secondary: #99a1b7;
    --vw-text-muted: #78829d;
    --vw-text-bright: #fff;

    --vw-font: 'Inter', system-ui, -apple-system, sans-serif;
    --vw-text-xs: 0.7rem;
    --vw-text-sm: 0.8rem;
    --vw-text-base: 0.875rem;
    --vw-text-md: 0.95rem;
    --vw-text-lg: 1.1rem;
    --vw-text-xl: 1.35rem;
    --vw-text-2xl: 1.5rem;

    --vw-radius-sm: 0.375rem;
    --vw-radius: 0.5rem;
    --vw-radius-md: 0.625rem;
    --vw-radius-lg: 0.75rem;
    --vw-radius-xl: 1rem;
    --vw-radius-full: 9999px;

    --vw-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.2);
    --vw-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    --vw-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.3);
    --vw-shadow-glow: 0 0 20px rgba(103, 93, 255, 0.15);

    --vw-transition: 150ms ease;
    --vw-transition-slow: 250ms ease;

    font-family: var(--vw-font);
    color: var(--vw-text);
}


/* --- Base Reset for Wizard Area --- */
.video-wizard {
    background: var(--vw-bg-deep);
}

.video-wizard .main {
    background: var(--vw-bg-deep);
}


/* ============================================
   COMPONENT: Cards
   ============================================ */
.vw-card {
    background: var(--vw-bg-surface);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius-lg);
    padding: 1.25rem;
    transition: border-color var(--vw-transition), box-shadow var(--vw-transition);
}

.vw-card:hover {
    border-color: rgba(75, 86, 117, 0.5);
}

.vw-card--selectable {
    cursor: pointer;
}

.vw-card--selectable:hover {
    border-color: var(--vw-border-accent);
    transform: translateY(-1px);
}

.vw-card--selectable.selected,
.vw-card--selectable.active {
    border-color: var(--vw-primary);
    box-shadow: var(--vw-primary-glow);
    background: linear-gradient(135deg, var(--vw-bg-surface) 0%, rgba(103, 93, 255, 0.05) 100%);
}


/* ============================================
   COMPONENT: Buttons
   ============================================ */
.vw-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.6rem 1.25rem;
    border: none;
    border-radius: var(--vw-radius);
    font-family: var(--vw-font);
    font-weight: 600;
    font-size: var(--vw-text-sm);
    cursor: pointer;
    transition: all var(--vw-transition);
    white-space: nowrap;
    text-decoration: none;
    line-height: 1.4;
}

.vw-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Primary */
.vw-btn--primary {
    background: var(--vw-primary);
    color: var(--vw-text-bright);
}
.vw-btn--primary:hover:not(:disabled) {
    background: var(--vw-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(var(--vw-primary-rgb), 0.3);
}

/* Primary Gradient */
.vw-btn--gradient {
    background: linear-gradient(135deg, var(--vw-primary) 0%, #7c3aed 100%);
    color: var(--vw-text-bright);
}
.vw-btn--gradient:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(var(--vw-primary-rgb), 0.35);
}

/* Secondary / Outline */
.vw-btn--outline {
    background: transparent;
    color: var(--vw-primary);
    border: 1px solid var(--vw-border-accent);
}
.vw-btn--outline:hover:not(:disabled) {
    background: var(--vw-primary-soft);
    border-color: var(--vw-primary);
}

/* Ghost */
.vw-btn--ghost {
    background: rgba(255, 255, 255, 0.05);
    color: var(--vw-text-secondary);
    border: 1px solid var(--vw-border);
}
.vw-btn--ghost:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
    color: var(--vw-text);
    border-color: rgba(75, 86, 117, 0.5);
}

/* Success */
.vw-btn--success {
    background: var(--vw-success);
    color: var(--vw-text-bright);
}
.vw-btn--success:hover:not(:disabled) {
    filter: brightness(1.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

/* Warning */
.vw-btn--warning {
    background: var(--vw-warning);
    color: #1a1a2e;
}
.vw-btn--warning:hover:not(:disabled) {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

/* Danger */
.vw-btn--danger {
    background: var(--vw-danger-soft);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.vw-btn--danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.25);
}

/* Size Modifiers */
.vw-btn--sm {
    padding: 0.35rem 0.75rem;
    font-size: var(--vw-text-xs);
}

.vw-btn--lg {
    padding: 0.75rem 1.5rem;
    font-size: var(--vw-text-md);
}

.vw-btn--full {
    width: 100%;
}

.vw-btn--icon {
    width: 36px;
    height: 36px;
    padding: 0;
    border-radius: var(--vw-radius);
}


/* ============================================
   COMPONENT: Badges
   ============================================ */
.vw-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.55rem;
    border-radius: var(--vw-radius-full);
    font-size: var(--vw-text-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    line-height: 1.5;
}

.vw-badge--primary { background: var(--vw-primary-soft); color: #a49eff; border: 1px solid var(--vw-border-accent); }
.vw-badge--success { background: var(--vw-success-soft); color: #6ee7b7; border: 1px solid var(--vw-border-success); }
.vw-badge--warning { background: var(--vw-warning-soft); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); }
.vw-badge--danger { background: var(--vw-danger-soft); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
.vw-badge--info { background: var(--vw-info-soft); color: #67e8f9; border: 1px solid rgba(34, 211, 238, 0.3); }
.vw-badge--muted { background: rgba(75, 86, 117, 0.2); color: var(--vw-text-secondary); border: 1px solid var(--vw-border); }

/* Mood badges for idea cards */
.vw-badge--funny { background: rgba(250, 204, 21, 0.12); color: #fde047; border: 1px solid rgba(250, 204, 21, 0.25); }
.vw-badge--absurd { background: rgba(249, 115, 22, 0.12); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.25); }
.vw-badge--wholesome { background: rgba(52, 211, 153, 0.12); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.25); }
.vw-badge--chaotic { background: rgba(239, 68, 68, 0.12); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.25); }
.vw-badge--cute { background: rgba(236, 72, 153, 0.12); color: #f9a8d4; border: 1px solid rgba(236, 72, 153, 0.25); }


/* ============================================
   COMPONENT: Form Inputs
   ============================================ */
.vw-input {
    width: 100%;
    padding: 0.6rem 0.85rem;
    background: var(--vw-bg-elevated);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-base);
    outline: none;
    transition: border-color var(--vw-transition);
}

.vw-input:focus {
    border-color: var(--vw-border-focus);
}

.vw-input::placeholder {
    color: var(--vw-text-muted);
}

.vw-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: var(--vw-bg-elevated);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
}

.vw-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.6rem 0.85rem;
    background: var(--vw-bg-elevated);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
    line-height: 1.5;
    resize: vertical;
    outline: none;
    transition: border-color var(--vw-transition);
}

.vw-textarea:focus {
    border-color: var(--vw-border-focus);
}


/* ============================================
   COMPONENT: Section Headers
   ============================================ */
.vw-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.vw-section-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: var(--vw-radius-lg);
    background: var(--vw-primary-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--vw-primary);
}

.vw-section-title {
    font-size: var(--vw-text-lg);
    font-weight: 700;
    color: var(--vw-text);
    margin: 0;
    line-height: 1.3;
}

.vw-section-subtitle {
    font-size: var(--vw-text-sm);
    color: var(--vw-text-muted);
    margin: 0;
}


/* ============================================
   COMPONENT: Step Number Circles
   ============================================ */
.vw-step-circle {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--vw-text-sm);
    font-weight: 700;
    background: var(--vw-primary-soft);
    color: var(--vw-primary);
    border: 1px solid var(--vw-border-accent);
    transition: all var(--vw-transition);
}

.vw-step-circle.completed {
    background: var(--vw-success-soft);
    color: #6ee7b7;
    border-color: var(--vw-border-success);
}


/* ============================================
   COMPONENT: Pills / Chips
   ============================================ */
.vw-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.85rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius-full);
    color: var(--vw-text-secondary);
    font-size: var(--vw-text-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all var(--vw-transition);
}

.vw-pill:hover {
    border-color: var(--vw-border-accent);
    color: var(--vw-text);
    background: rgba(103, 93, 255, 0.06);
}

.vw-pill.active {
    background: var(--vw-primary-soft);
    border-color: var(--vw-primary);
    color: var(--vw-text);
}


/* ============================================
   COMPONENT: Segmented Control / Tabs
   ============================================ */
.vw-tabs {
    display: flex;
    gap: 0;
    background: var(--vw-bg-elevated);
    border-radius: var(--vw-radius-lg);
    padding: 0.2rem;
    border: 1px solid var(--vw-border);
}

.vw-tab {
    flex: 1;
    padding: 0.55rem 0.75rem;
    text-align: center;
    font-size: var(--vw-text-sm);
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: var(--vw-text-secondary);
    border: none;
    border-radius: var(--vw-radius-md);
    transition: all var(--vw-transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.vw-tab:hover {
    color: var(--vw-text);
    background: rgba(255, 255, 255, 0.04);
}

.vw-tab.active {
    background: var(--vw-primary-soft);
    color: var(--vw-text);
    border: 1px solid var(--vw-border-accent);
}


/* ============================================
   COMPONENT: Status Badges
   ============================================ */
.vw-status {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.6rem;
    border-radius: var(--vw-radius-full);
    font-size: var(--vw-text-xs);
    font-weight: 600;
}

.vw-status--pending { background: rgba(75, 86, 117, 0.2); color: var(--vw-text-secondary); }
.vw-status--generating { background: var(--vw-primary-soft); color: #a49eff; animation: vw-pulse 1.5s infinite; }
.vw-status--ready { background: var(--vw-success-soft); color: #6ee7b7; }
.vw-status--processing { background: var(--vw-warning-soft); color: #fde68a; animation: vw-pulse 1.5s infinite; }
.vw-status--error { background: var(--vw-danger-soft); color: #fca5a5; }


/* ============================================
   COMPONENT: Toggle Switch
   ============================================ */
.vw-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: var(--vw-bg-elevated);
    border: 1px solid var(--vw-border);
    border-radius: var(--vw-radius);
}

.vw-toggle-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.vw-toggle-checkbox { display: none; }

.vw-toggle-switch {
    position: relative;
    width: 34px;
    height: 18px;
    background: rgba(75, 86, 117, 0.4);
    border-radius: 9px;
    transition: background var(--vw-transition);
    flex-shrink: 0;
}
.vw-toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--vw-text-secondary);
    transition: all var(--vw-transition);
}
.vw-toggle-checkbox:checked + .vw-toggle-switch {
    background: rgba(var(--vw-primary-rgb), 0.5);
}
.vw-toggle-checkbox:checked + .vw-toggle-switch::after {
    left: 18px;
    background: #c4b5fd;
}

.vw-toggle-text {
    font-size: var(--vw-text-sm);
    font-weight: 600;
    color: var(--vw-text);
}

.vw-toggle-hint {
    font-size: var(--vw-text-xs);
    color: var(--vw-text-muted);
    margin-left: auto;
}


/* ============================================
   COMPONENT: Progress Bar
   ============================================ */
.vw-progress {
    padding: 0.75rem;
    background: var(--vw-warning-soft);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: var(--vw-radius);
}

.vw-progress-text {
    font-size: var(--vw-text-sm);
    color: #fde68a;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.vw-progress-track {
    height: 3px;
    background: rgba(245, 158, 11, 0.15);
    border-radius: 2px;
    overflow: hidden;
}

.vw-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--vw-warning), #fbbf24);
    border-radius: 2px;
    animation: vw-progress-indeterminate 2s ease-in-out infinite;
}

.vw-progress-hint {
    font-size: var(--vw-text-xs);
    color: var(--vw-text-secondary);
    margin-top: 0.4rem;
}


/* ============================================
   ANIMATIONS
   ============================================ */
@keyframes vw-pulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

@keyframes vw-spin {
    to { transform: rotate(360deg); }
}

@keyframes vw-progress-indeterminate {
    0% { width: 0%; margin-left: 0%; }
    50% { width: 40%; margin-left: 30%; }
    100% { width: 0%; margin-left: 100%; }
}

@keyframes vw-fade-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.vw-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid var(--vw-border);
    border-top-color: var(--vw-primary);
    border-radius: 50%;
    animation: vw-spin 0.8s linear infinite;
}


/* ============================================
   LAYOUT HELPERS
   ============================================ */
.vw-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
.vw-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
.vw-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }

@media (max-width: 768px) {
    .vw-grid-2 { grid-template-columns: 1fr; }
    .vw-grid-3 { grid-template-columns: repeat(2, 1fr); }
    .vw-grid-4 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
    .vw-grid-3 { grid-template-columns: 1fr; }
}


/* ============================================
   PAGE HEADER
   ============================================ */
.vw-page-header {
    text-align: center;
    padding: 1.5rem 1rem 0;
}

.vw-page-title {
    font-size: var(--vw-text-2xl);
    font-weight: 700;
    color: var(--vw-primary);
    margin: 0 0 0.25rem;
}

.vw-page-subtitle {
    font-size: var(--vw-text-base);
    color: var(--vw-text-secondary);
    margin: 0;
}


/* ============================================
   FOOTER NAV BUTTONS
   ============================================ */
.vw-footer-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--vw-border);
    background: var(--vw-bg-deep);
    margin-top: 2rem;
}

.vw-footer-nav .vw-footer-center {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vw-project-id {
    font-size: var(--vw-text-xs);
    color: var(--vw-text-muted);
}
</style>
