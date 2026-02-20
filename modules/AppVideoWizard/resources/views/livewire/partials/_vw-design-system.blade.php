{{-- Video Wizard Design System --}}
{{-- Centralized tokens, component classes, and animations --}}
{{-- Include once in video-wizard.blade.php --}}

<style>
/* ============================================
   VIDEO WIZARD DESIGN SYSTEM
   Frosted Glass — Cyan Accent (#03fcf4)
   ============================================ */

/* --- Design Tokens (aliased to shared at-* system) --- */
.video-wizard {
    --vw-bg-deep: var(--at-bg-deep);
    --vw-bg-surface: var(--at-bg-surface);
    --vw-bg-surface-solid: var(--at-bg-surface-solid);
    --vw-bg-elevated: var(--at-bg-elevated);
    --vw-bg-hover: var(--at-bg-hover);
    --vw-bg-overlay: var(--at-bg-overlay);

    --vw-border: var(--at-border);
    --vw-border-accent: var(--at-border-accent);
    --vw-border-focus: var(--at-border-focus);
    --vw-border-success: rgba(34, 197, 94, 0.4);

    --vw-primary: var(--at-primary);
    --vw-primary-rgb: var(--at-primary-rgb);
    --vw-primary-hover: var(--at-primary-hover);
    --vw-primary-soft: var(--at-primary-soft);
    --vw-primary-glow: var(--at-primary-glow);
    --vw-primary-text: var(--at-primary-text);
    --vw-primary-text-rgb: var(--at-primary-text-rgb);
    --vw-text-on-primary: var(--at-text-on-primary);

    --vw-success: var(--at-success);
    --vw-success-soft: var(--at-success-soft);
    --vw-warning: var(--at-warning);
    --vw-warning-soft: var(--at-warning-soft);
    --vw-danger: var(--at-danger);
    --vw-danger-soft: var(--at-danger-soft);
    --vw-info: var(--at-info);
    --vw-info-soft: var(--at-info-soft);

    --vw-text: var(--at-text);
    --vw-text-secondary: var(--at-text-secondary);
    --vw-text-muted: var(--at-text-muted);
    --vw-text-bright: var(--at-text-bright);

    --vw-font: var(--at-font);
    --vw-text-xs: 0.7rem;
    --vw-text-sm: 0.8rem;
    --vw-text-base: 0.875rem;
    --vw-text-md: 0.95rem;
    --vw-text-lg: 1.1rem;
    --vw-text-xl: 1.35rem;
    --vw-text-2xl: 1.5rem;

    --vw-radius-sm: var(--at-radius-sm);
    --vw-radius: var(--at-radius);
    --vw-radius-md: 1rem;
    --vw-radius-lg: var(--at-radius-lg);
    --vw-radius-xl: var(--at-radius-xl);
    --vw-radius-full: var(--at-radius-full);

    /* --- Glass Shadows --- */
    --vw-glass: var(--at-glass);
    --vw-glass-hover: var(--at-glass-hover);
    --vw-glass-active:
        0 8px 32px rgba(3, 252, 244, 0.1),
        0 2px 8px rgba(0, 0, 0, 0.04),
        0 0 0 2px rgba(3, 252, 244, 0.3);
    --vw-glass-btn: var(--at-glass-btn);
    --vw-glass-btn-hover: var(--at-glass-btn-hover);
    --vw-glass-inset: var(--at-glass-inset);
    --vw-glass-sm: var(--at-glass-sm);
    --vw-glass-lg: var(--at-glass-lg);

    /* Backward-compat aliases (clay → glass) */
    --vw-clay: var(--vw-glass);
    --vw-clay-hover: var(--vw-glass-hover);
    --vw-clay-active: var(--vw-glass-active);
    --vw-clay-btn: var(--vw-glass-btn);
    --vw-clay-btn-hover: var(--vw-glass-btn-hover);
    --vw-clay-inset: var(--vw-glass-inset);
    --vw-clay-sm: var(--vw-glass-sm);
    --vw-clay-lg: var(--vw-glass-lg);
    --vw-shadow-sm: var(--vw-glass-sm);
    --vw-shadow: var(--vw-glass);
    --vw-shadow-lg: var(--vw-glass-lg);
    --vw-shadow-glow: 0 0 0 3px rgba(3, 252, 244, 0.1);

    --vw-transition: var(--at-transition);
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
   COMPONENT: Cards — Frosted Glass
   ============================================ */
.vw-card {
    background: var(--vw-bg-surface);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: var(--vw-radius-lg);
    padding: 1.25rem;
    box-shadow: var(--vw-glass);
    transition: box-shadow var(--vw-transition), transform var(--vw-transition), border-color var(--vw-transition);
}

.vw-card:hover {
    box-shadow: var(--vw-glass-hover);
    border-color: rgba(255, 255, 255, 0.45);
}

.vw-card--selectable {
    cursor: pointer;
}

.vw-card--selectable:hover {
    box-shadow: var(--vw-glass-hover);
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.5);
}

.vw-card--selectable.selected,
.vw-card--selectable.active {
    box-shadow: var(--vw-glass-active);
    background: rgba(255, 255, 255, 0.65);
    border-color: rgba(3, 252, 244, 0.35);
}


/* ============================================
   COMPONENT: Buttons — Glass
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

/* Primary — Cyan Glass */
.vw-btn--primary {
    background: var(--vw-primary);
    color: var(--vw-text-on-primary);
    box-shadow: var(--vw-glass-btn);
}
.vw-btn--primary:hover:not(:disabled) {
    background: var(--vw-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--vw-glass-btn-hover);
}

/* Primary Gradient — Cyan Glass */
.vw-btn--gradient {
    background: linear-gradient(135deg, #06e3f7 0%, #03fcf4 100%);
    color: var(--vw-text-on-primary);
    box-shadow: var(--vw-glass-btn);
}
.vw-btn--gradient:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--vw-glass-btn-hover);
}

/* Outline — Glass surface */
.vw-btn--outline {
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: var(--vw-primary-text);
    border: 1px solid rgba(3, 252, 244, 0.2);
    box-shadow: var(--vw-glass-sm);
}
.vw-btn--outline:hover:not(:disabled) {
    background: rgba(3, 252, 244, 0.08);
    box-shadow: var(--vw-glass-btn);
    transform: translateY(-1px);
}

/* Ghost — Glass */
.vw-btn--ghost {
    background: rgba(255, 255, 255, 0.35);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--vw-text-secondary);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: var(--vw-glass-sm);
}
.vw-btn--ghost:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.55);
    color: var(--vw-text);
    box-shadow: var(--vw-glass-btn);
    transform: translateY(-1px);
}

/* Success */
.vw-btn--success {
    background: var(--vw-success);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-glass-btn);
}
.vw-btn--success:hover:not(:disabled) {
    filter: brightness(1.05);
    transform: translateY(-1px);
    box-shadow: var(--vw-glass-btn-hover);
}

/* Warning */
.vw-btn--warning {
    background: var(--vw-warning);
    color: var(--vw-text-bright);
    box-shadow: var(--vw-glass-btn);
}
.vw-btn--warning:hover:not(:disabled) {
    filter: brightness(1.05);
    transform: translateY(-1px);
    box-shadow: var(--vw-glass-btn-hover);
}

/* Danger */
.vw-btn--danger {
    background: var(--vw-danger-soft);
    color: var(--vw-danger);
    box-shadow: var(--vw-glass-sm);
}
.vw-btn--danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.12);
    box-shadow: var(--vw-glass-btn);
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
   COMPONENT: Badges — Glass
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
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: var(--vw-glass-sm);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.vw-badge--primary { background: rgba(3, 252, 244, 0.1); color: var(--vw-primary-text); border-color: rgba(3, 252, 244, 0.2); }
.vw-badge--success { background: var(--vw-success-soft); color: #16a34a; }
.vw-badge--warning { background: var(--vw-warning-soft); color: #d97706; }
.vw-badge--danger { background: var(--vw-danger-soft); color: #dc2626; }
.vw-badge--info { background: var(--vw-info-soft); color: #0284c7; }
.vw-badge--muted { background: rgba(255, 255, 255, 0.35); color: var(--vw-text-secondary); }

/* Mood badges for idea cards */
.vw-badge--funny { background: rgba(250, 204, 21, 0.1); color: #a16207; }
.vw-badge--absurd { background: rgba(249, 115, 22, 0.1); color: #c2410c; }
.vw-badge--wholesome { background: rgba(52, 211, 153, 0.1); color: #059669; }
.vw-badge--chaotic { background: rgba(239, 68, 68, 0.08); color: #dc2626; }
.vw-badge--cute { background: rgba(236, 72, 153, 0.08); color: #be185d; }


/* ============================================
   COMPONENT: Form Inputs — Glass Inset
   ============================================ */
.vw-input {
    width: 100%;
    padding: 0.6rem 0.85rem;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-base);
    outline: none;
    box-shadow: var(--vw-glass-inset);
    transition: box-shadow var(--vw-transition), border-color var(--vw-transition);
}

.vw-input:focus {
    border-color: rgba(3, 252, 244, 0.4);
    box-shadow: var(--vw-glass-inset), 0 0 0 3px rgba(3, 252, 244, 0.12);
}

.vw-input::placeholder {
    color: var(--vw-text-muted);
}

.vw-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
    box-shadow: var(--vw-glass-inset);
}

.vw-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.6rem 0.85rem;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: var(--vw-radius);
    color: var(--vw-text);
    font-family: var(--vw-font);
    font-size: var(--vw-text-sm);
    line-height: 1.5;
    resize: vertical;
    outline: none;
    box-shadow: var(--vw-glass-inset);
    transition: box-shadow var(--vw-transition), border-color var(--vw-transition);
}

.vw-textarea:focus {
    border-color: rgba(3, 252, 244, 0.4);
    box-shadow: var(--vw-glass-inset), 0 0 0 3px rgba(3, 252, 244, 0.12);
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
    background: rgba(3, 252, 244, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--vw-primary-text);
    border: 1px solid rgba(3, 252, 244, 0.15);
    box-shadow: var(--vw-glass-sm);
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
   COMPONENT: Step Number Circles — Glass
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
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--vw-text-secondary);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: var(--vw-glass-sm);
    transition: all var(--vw-transition);
}

.vw-step-circle.completed {
    background: #dcfce7;
    color: #16a34a;
    border-color: rgba(34, 197, 94, 0.2);
    box-shadow: var(--vw-glass-sm);
}


/* ============================================
   COMPONENT: Pills / Chips — Glass
   ============================================ */
.vw-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.85rem;
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--vw-radius-full);
    color: var(--vw-text-secondary);
    font-size: var(--vw-text-sm);
    font-weight: 500;
    cursor: pointer;
    box-shadow: var(--vw-glass-sm);
    transition: all var(--vw-transition);
}

.vw-pill:hover {
    color: var(--vw-text);
    background: rgba(255, 255, 255, 0.65);
    border-color: rgba(255, 255, 255, 0.45);
    box-shadow: var(--vw-glass-btn);
    transform: translateY(-1px);
}

.vw-pill.active {
    background: var(--vw-primary);
    color: var(--vw-text-on-primary);
    border-color: rgba(3, 252, 244, 0.5);
    box-shadow: 0 0 16px rgba(3, 252, 244, 0.2), var(--vw-glass-btn);
}


/* ============================================
   COMPONENT: Segmented Control / Tabs — Glass
   ============================================ */
.vw-tabs {
    display: flex;
    gap: 0;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: var(--vw-radius-lg);
    padding: 0.25rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: var(--vw-glass-inset);
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
    background: rgba(255, 255, 255, 0.5);
}

.vw-tab.active {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--vw-text);
    box-shadow: var(--vw-glass-sm);
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
    box-shadow: var(--vw-glass-sm);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.vw-status--pending { background: rgba(255, 255, 255, 0.35); color: var(--vw-text-secondary); }
.vw-status--generating { background: rgba(3, 252, 244, 0.1); color: var(--vw-primary-text); animation: vw-pulse 1.5s infinite; }
.vw-status--ready { background: var(--vw-success-soft); color: #16a34a; }
.vw-status--processing { background: var(--vw-warning-soft); color: #d97706; animation: vw-pulse 1.5s infinite; }
.vw-status--error { background: var(--vw-danger-soft); color: #dc2626; }


/* ============================================
   COMPONENT: Toggle Switch — Glass
   ============================================ */
.vw-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--vw-radius);
    box-shadow: var(--vw-glass-sm);
}

.vw-toggle-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.vw-toggle-checkbox { display: none; }

.vw-toggle-switch {
    position: relative;
    width: 34px;
    height: 18px;
    background: #c1c8d4;
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
   COMPONENT: Progress Bar — Glass
   ============================================ */
.vw-progress {
    padding: 0.75rem;
    background: var(--vw-warning-soft);
    border: none;
    border-radius: var(--vw-radius);
    box-shadow: var(--vw-glass-sm);
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

/* Gradient Mesh Float Animations */
@keyframes vw-mesh-float-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-15px, 15px) scale(0.97); }
}
@keyframes vw-mesh-float-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-25px, 20px) scale(0.95); }
    66% { transform: translate(20px, -10px) scale(1.03); }
}
@keyframes vw-mesh-float-3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(15px, 25px) scale(1.02); }
    66% { transform: translate(-20px, -15px) scale(0.98); }
}

.vw-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
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
   FOOTER NAV BUTTONS — Glass
   ============================================ */
.vw-footer-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
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
