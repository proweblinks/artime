{{-- Video Wizard Design System --}}
{{-- Centralized tokens, component classes, and animations --}}
{{-- Include once in video-wizard.blade.php --}}

<style>
/* ============================================
   VIDEO WIZARD DESIGN SYSTEM
   Claymorphism — Warm Neutrals + Warm Indigo
   ============================================ */

/* --- Design Tokens --- */
.video-wizard {
    --vw-bg-deep: #f2f0ed;
    --vw-bg-surface: #ffffff;
    --vw-bg-elevated: #f7f5f2;
    --vw-bg-hover: #edeae6;
    --vw-bg-overlay: rgba(255, 255, 255, 0.92);

    --vw-border: #e5e1db;
    --vw-border-accent: rgba(99, 102, 241, 0.2);
    --vw-border-focus: #6366f1;
    --vw-border-success: rgba(34, 197, 94, 0.4);

    --vw-primary: #6366f1;
    --vw-primary-rgb: 99, 102, 241;
    --vw-primary-hover: #5558e3;
    --vw-primary-soft: rgba(99, 102, 241, 0.08);
    --vw-primary-glow: 0 0 0 3px rgba(99, 102, 241, 0.12);

    --vw-success: #22c55e;
    --vw-success-soft: rgba(34, 197, 94, 0.1);
    --vw-warning: #f59e0b;
    --vw-warning-soft: rgba(245, 158, 11, 0.1);
    --vw-danger: #ef4444;
    --vw-danger-soft: rgba(239, 68, 68, 0.08);
    --vw-info: #0ea5e9;
    --vw-info-soft: rgba(14, 165, 233, 0.08);

    --vw-text: #2d2a33;
    --vw-text-secondary: #6b6580;
    --vw-text-muted: #a09aad;
    --vw-text-bright: #ffffff;

    --vw-font: 'Inter', system-ui, -apple-system, sans-serif;
    --vw-text-xs: 0.7rem;
    --vw-text-sm: 0.8rem;
    --vw-text-base: 0.875rem;
    --vw-text-md: 0.95rem;
    --vw-text-lg: 1.1rem;
    --vw-text-xl: 1.35rem;
    --vw-text-2xl: 1.5rem;

    --vw-radius-sm: 0.625rem;
    --vw-radius: 0.875rem;
    --vw-radius-md: 1rem;
    --vw-radius-lg: 1.25rem;
    --vw-radius-xl: 1.5rem;
    --vw-radius-full: 9999px;

    /* --- Clay Shadows (core of claymorphism) --- */
    --vw-clay:
        6px 6px 14px rgba(0, 0, 0, 0.07),
        inset -2px -2px 5px rgba(0, 0, 0, 0.04),
        inset 2px 2px 5px rgba(255, 255, 255, 0.7);
    --vw-clay-hover:
        8px 8px 20px rgba(0, 0, 0, 0.1),
        inset -3px -3px 6px rgba(0, 0, 0, 0.05),
        inset 3px 3px 6px rgba(255, 255, 255, 0.8);
    --vw-clay-active:
        6px 6px 14px rgba(99, 102, 241, 0.12),
        inset -2px -2px 5px rgba(99, 102, 241, 0.06),
        inset 2px 2px 5px rgba(255, 255, 255, 0.7),
        0 0 0 2px rgba(99, 102, 241, 0.3);
    --vw-clay-btn:
        4px 4px 10px rgba(0, 0, 0, 0.1),
        inset -2px -2px 4px rgba(0, 0, 0, 0.08),
        inset 2px 2px 4px rgba(255, 255, 255, 0.5);
    --vw-clay-btn-hover:
        6px 6px 14px rgba(0, 0, 0, 0.14),
        inset -2px -2px 5px rgba(0, 0, 0, 0.1),
        inset 3px 3px 5px rgba(255, 255, 255, 0.6);
    --vw-clay-inset:
        inset 2px 2px 6px rgba(0, 0, 0, 0.06),
        inset -1px -1px 4px rgba(255, 255, 255, 0.5);
    --vw-clay-sm:
        3px 3px 6px rgba(0, 0, 0, 0.07),
        inset -1px -1px 3px rgba(0, 0, 0, 0.05),
        inset 1px 1px 3px rgba(255, 255, 255, 0.6);
    --vw-clay-lg:
        10px 10px 24px rgba(0, 0, 0, 0.09),
        inset -3px -3px 8px rgba(0, 0, 0, 0.04),
        inset 4px 4px 8px rgba(255, 255, 255, 0.7);

    /* Legacy shadow tokens (kept for compatibility) */
    --vw-shadow-sm: var(--vw-clay-sm);
    --vw-shadow: var(--vw-clay);
    --vw-shadow-lg: var(--vw-clay-lg);
    --vw-shadow-glow: 0 0 0 3px rgba(99, 102, 241, 0.1);

    --vw-transition: 200ms ease;
    --vw-transition-slow: 300ms ease;

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
   COMPONENT: Cards — Clay 3D
   ============================================ */
.vw-card {
    background: var(--vw-bg-surface);
    border: none;
    border-radius: var(--vw-radius-lg);
    padding: 1.25rem;
    box-shadow: var(--vw-clay);
    transition: box-shadow var(--vw-transition), transform var(--vw-transition);
}

.vw-card:hover {
    box-shadow: var(--vw-clay-hover);
}

.vw-card--selectable {
    cursor: pointer;
}

.vw-card--selectable:hover {
    box-shadow: var(--vw-clay-hover);
    transform: translateY(-2px);
}

.vw-card--selectable.selected,
.vw-card--selectable.active {
    box-shadow: var(--vw-clay-active);
    background: var(--vw-bg-surface);
}


/* ============================================
   COMPONENT: Buttons — Clay 3D
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
    opacity: 0.4;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Primary — Warm Indigo Clay */
.vw-btn--primary {
    background: var(--vw-primary);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-clay-btn);
}
.vw-btn--primary:hover:not(:disabled) {
    background: var(--vw-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--vw-clay-btn-hover);
}

/* Primary Gradient — Indigo Clay */
.vw-btn--gradient {
    background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-clay-btn);
}
.vw-btn--gradient:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--vw-clay-btn-hover);
}

/* Secondary / Outline — Clay surface */
.vw-btn--outline {
    background: var(--vw-bg-surface);
    color: var(--vw-primary);
    box-shadow: var(--vw-clay-sm);
}
.vw-btn--outline:hover:not(:disabled) {
    background: var(--vw-primary-soft);
    box-shadow: var(--vw-clay-btn);
    transform: translateY(-1px);
}

/* Ghost — Subtle clay */
.vw-btn--ghost {
    background: var(--vw-bg-elevated);
    color: var(--vw-text-secondary);
    border: none;
    box-shadow: var(--vw-clay-sm);
}
.vw-btn--ghost:hover:not(:disabled) {
    background: var(--vw-bg-surface);
    color: var(--vw-text);
    box-shadow: var(--vw-clay-btn);
    transform: translateY(-1px);
}

/* Success */
.vw-btn--success {
    background: var(--vw-success);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-clay-btn);
}
.vw-btn--success:hover:not(:disabled) {
    filter: brightness(1.05);
    transform: translateY(-1px);
    box-shadow: var(--vw-clay-btn-hover);
}

/* Warning */
.vw-btn--warning {
    background: var(--vw-warning);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-clay-btn);
}
.vw-btn--warning:hover:not(:disabled) {
    filter: brightness(1.05);
    transform: translateY(-1px);
    box-shadow: var(--vw-clay-btn-hover);
}

/* Danger */
.vw-btn--danger {
    background: var(--vw-danger-soft);
    color: var(--vw-danger);
    box-shadow: var(--vw-clay-sm);
}
.vw-btn--danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.12);
    box-shadow: var(--vw-clay-btn);
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
   COMPONENT: Badges — Tiny Clay
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
    border: none;
    box-shadow: var(--vw-clay-sm);
}

.vw-badge--primary { background: var(--vw-primary-soft); color: var(--vw-primary); }
.vw-badge--success { background: var(--vw-success-soft); color: #16a34a; }
.vw-badge--warning { background: var(--vw-warning-soft); color: #d97706; }
.vw-badge--danger { background: var(--vw-danger-soft); color: #dc2626; }
.vw-badge--info { background: var(--vw-info-soft); color: #0284c7; }
.vw-badge--muted { background: var(--vw-bg-elevated); color: var(--vw-text-secondary); }

/* Mood badges for idea cards */
.vw-badge--funny { background: rgba(250, 204, 21, 0.1); color: #a16207; }
.vw-badge--absurd { background: rgba(249, 115, 22, 0.1); color: #c2410c; }
.vw-badge--wholesome { background: rgba(52, 211, 153, 0.1); color: #059669; }
.vw-badge--chaotic { background: rgba(239, 68, 68, 0.08); color: #dc2626; }
.vw-badge--cute { background: rgba(236, 72, 153, 0.08); color: #be185d; }


/* ============================================
   COMPONENT: Form Inputs — Recessed Clay
   ============================================ */
.vw-input {
    width: 100%;
    padding: 0.6rem 0.85rem;
    background: var(--vw-bg-elevated);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-base);
    outline: none;
    box-shadow: var(--vw-clay-inset);
    transition: box-shadow var(--vw-transition);
}

.vw-input:focus {
    border-color: transparent;
    box-shadow: var(--vw-clay-inset), 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.vw-input::placeholder {
    color: var(--vw-text-muted);
}

.vw-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: var(--vw-bg-elevated);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
    box-shadow: var(--vw-clay-inset);
}

.vw-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.6rem 0.85rem;
    background: var(--vw-bg-elevated);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
    line-height: 1.5;
    resize: vertical;
    outline: none;
    box-shadow: var(--vw-clay-inset);
    transition: box-shadow var(--vw-transition);
}

.vw-textarea:focus {
    border-color: transparent;
    box-shadow: var(--vw-clay-inset), 0 0 0 3px rgba(99, 102, 241, 0.15);
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
    border: none;
    box-shadow: var(--vw-clay-sm);
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
   COMPONENT: Step Number Circles — Clay
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
    background: var(--vw-bg-surface);
    color: var(--vw-text-secondary);
    border: none;
    box-shadow: var(--vw-clay-sm);
    transition: all var(--vw-transition);
}

.vw-step-circle.completed {
    background: #dcfce7;
    color: #16a34a;
    box-shadow: var(--vw-clay-sm);
}


/* ============================================
   COMPONENT: Pills / Chips — Clay
   ============================================ */
.vw-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.85rem;
    background: var(--vw-bg-surface);
    border: none;
    border-radius: var(--vw-radius-full);
    color: var(--vw-text-secondary);
    font-size: var(--vw-text-sm);
    font-weight: 500;
    cursor: pointer;
    box-shadow: var(--vw-clay-sm);
    transition: all var(--vw-transition);
}

.vw-pill:hover {
    color: var(--vw-text);
    background: var(--vw-bg-surface);
    box-shadow: var(--vw-clay-btn);
    transform: translateY(-1px);
}

.vw-pill.active {
    background: var(--vw-primary);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-clay-btn);
}


/* ============================================
   COMPONENT: Segmented Control / Tabs — Clay
   ============================================ */
.vw-tabs {
    display: flex;
    gap: 0;
    background: var(--vw-bg-elevated);
    border-radius: var(--vw-radius-lg);
    padding: 0.25rem;
    border: none;
    box-shadow: var(--vw-clay-inset);
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
    background: rgba(255, 255, 255, 0.6);
}

.vw-tab.active {
    background: var(--vw-bg-surface);
    color: var(--vw-text);
    box-shadow: var(--vw-clay-sm);
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
    box-shadow: var(--vw-clay-sm);
}

.vw-status--pending { background: var(--vw-bg-elevated); color: var(--vw-text-secondary); }
.vw-status--generating { background: var(--vw-primary-soft); color: var(--vw-primary); animation: vw-pulse 1.5s infinite; }
.vw-status--ready { background: var(--vw-success-soft); color: #16a34a; }
.vw-status--processing { background: var(--vw-warning-soft); color: #d97706; animation: vw-pulse 1.5s infinite; }
.vw-status--error { background: var(--vw-danger-soft); color: #dc2626; }


/* ============================================
   COMPONENT: Toggle Switch — Clay
   ============================================ */
.vw-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: var(--vw-bg-surface);
    border: none;
    border-radius: var(--vw-radius);
    box-shadow: var(--vw-clay-sm);
}

.vw-toggle-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.vw-toggle-checkbox { display: none; }

.vw-toggle-switch {
    position: relative;
    width: 34px;
    height: 18px;
    background: #d4d0cc;
    border-radius: 9px;
    transition: background var(--vw-transition);
    flex-shrink: 0;
    box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.1);
}
.vw-toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: white;
    box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
    transition: all var(--vw-transition);
}
.vw-toggle-checkbox:checked + .vw-toggle-switch {
    background: var(--vw-primary);
}
.vw-toggle-checkbox:checked + .vw-toggle-switch::after {
    left: 18px;
    background: white;
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
   COMPONENT: Progress Bar — Clay
   ============================================ */
.vw-progress {
    padding: 0.75rem;
    background: var(--vw-warning-soft);
    border: none;
    border-radius: var(--vw-radius);
    box-shadow: var(--vw-clay-sm);
}

.vw-progress-text {
    font-size: var(--vw-text-sm);
    color: #92400e;
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
    color: var(--vw-text);
    margin: 0 0 0.25rem;
}

.vw-page-subtitle {
    font-size: var(--vw-text-base);
    color: var(--vw-text-secondary);
    margin: 0;
}


/* ============================================
   FOOTER NAV BUTTONS — Clay
   ============================================ */
.vw-footer-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--vw-border);
    background: var(--vw-bg-surface);
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
