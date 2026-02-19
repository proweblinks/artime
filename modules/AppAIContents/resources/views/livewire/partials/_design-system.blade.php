<style>
/* ============================================
   CONTENT STUDIO — FROSTED GLASS DESIGN SYSTEM
   Pomelli-exact layout + ARTime frosted glass
   ============================================ */

@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap');

.cs-app {
    /* ── Backgrounds ── */
    --cs-bg-deep: #f0f4f8;
    --cs-bg-surface: rgba(255, 255, 255, 0.55);
    --cs-bg-surface-solid: #ffffff;
    --cs-bg-elevated: rgba(255, 255, 255, 0.35);
    --cs-bg-hover: rgba(255, 255, 255, 0.65);
    --cs-bg-input: rgba(255, 255, 255, 0.7);

    /* ── Borders ── */
    --cs-border: rgba(255, 255, 255, 0.35);
    --cs-border-strong: rgba(0, 0, 0, 0.08);
    --cs-border-accent: rgba(3, 252, 244, 0.2);

    /* ── Primary accent (cyan) ── */
    --cs-primary: #03fcf4;
    --cs-primary-hover: #00d4cc;
    --cs-primary-soft: rgba(3, 252, 244, 0.08);
    --cs-primary-text: #0891b2;
    --cs-text-on-primary: #0a2e2e;

    /* ── Semantic ── */
    --cs-success: #22c55e;
    --cs-success-soft: rgba(34, 197, 94, 0.1);
    --cs-warning: #f59e0b;
    --cs-warning-soft: rgba(245, 158, 11, 0.1);
    --cs-danger: #ef4444;
    --cs-danger-soft: rgba(239, 68, 68, 0.08);

    /* ── Text ── */
    --cs-text: #1a1a2e;
    --cs-text-secondary: #5a6178;
    --cs-text-muted: #94a0b8;

    /* ── Typography ── */
    --cs-font: 'Inter', system-ui, -apple-system, sans-serif;
    --cs-font-serif: 'Playfair Display', Georgia, serif;

    /* ── Radius ── */
    --cs-radius: 0.875rem;
    --cs-radius-lg: 1.25rem;
    --cs-radius-sm: 0.625rem;
    --cs-radius-pill: 50rem;

    /* ── Glass shadows ── */
    --cs-glass: 0 8px 32px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.04);
    --cs-glass-hover: 0 12px 40px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.05);
    --cs-glass-sm: 0 4px 12px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.03);

    /* ── Sidebar ── */
    --cs-sidebar-w: 240px;
    --cs-sidebar-collapsed-w: 60px;

    /* ── Transition ── */
    --cs-transition: 200ms ease;

    font-family: var(--cs-font);
    color: var(--cs-text);
    background: var(--cs-bg-deep);
    min-height: 100vh;
    position: relative;
}

/* ── Gradient Mesh Background ── */
.cs-mesh-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
}

.cs-mesh-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    will-change: transform;
}

.cs-mesh-blob--cyan {
    width: 400px;
    height: 400px;
    background: #03fcf4;
    top: 10%;
    left: 20%;
    opacity: 0.12;
    animation: cs-float-1 25s ease-in-out infinite;
}

.cs-mesh-blob--teal {
    width: 350px;
    height: 350px;
    background: #14b8a6;
    bottom: 20%;
    right: 15%;
    opacity: 0.12;
    animation: cs-float-2 22s ease-in-out infinite;
    animation-delay: -8s;
}

.cs-mesh-blob--blue {
    width: 300px;
    height: 300px;
    background: #38bdf8;
    top: 60%;
    left: 5%;
    opacity: 0.10;
    animation: cs-float-3 20s ease-in-out infinite;
    animation-delay: -5s;
}

@keyframes cs-float-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-15px, 15px) scale(0.97); }
}
@keyframes cs-float-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-25px, 20px) scale(0.95); }
    66% { transform: translate(20px, -10px) scale(1.03); }
}
@keyframes cs-float-3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(15px, 25px) scale(1.02); }
    66% { transform: translate(-20px, -15px) scale(0.98); }
}

/* ── Main Layout ── */
.cs-layout {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* ── Sidebar ── */
.cs-sidebar {
    width: var(--cs-sidebar-w);
    min-height: 100vh;
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-right: 1px solid var(--cs-border);
    display: flex;
    flex-direction: column;
    transition: width 300ms ease;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 50;
    padding-top: env(safe-area-inset-top, 0px);
}

.cs-sidebar.collapsed {
    width: var(--cs-sidebar-collapsed-w);
}

.cs-sidebar-header {
    padding: 20px 16px;
    border-bottom: 1px solid var(--cs-border);
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 72px;
}

.cs-sidebar-header .cs-logo {
    width: 32px;
    height: 32px;
    min-width: 32px;
    background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-hover));
    border-radius: var(--cs-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--cs-text-on-primary);
    font-size: 16px;
}

.cs-sidebar-header .cs-brand-text {
    font-family: var(--cs-font);
    font-weight: 700;
    font-size: 15px;
    color: var(--cs-text);
    letter-spacing: -0.01em;
    white-space: nowrap;
    overflow: hidden;
    transition: opacity 200ms ease;
}

.cs-sidebar.collapsed .cs-brand-text,
.cs-sidebar.collapsed .cs-experiment-badge {
    opacity: 0;
    width: 0;
    overflow: hidden;
}

.cs-experiment-badge {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--cs-primary-text);
    background: var(--cs-primary-soft);
    border: 1px solid var(--cs-border-accent);
    padding: 2px 6px;
    border-radius: var(--cs-radius-pill);
    white-space: nowrap;
    transition: opacity 200ms ease;
}

/* Sidebar Navigation */
.cs-sidebar-nav {
    flex: 1;
    padding: 12px 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.cs-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: var(--cs-radius-sm);
    cursor: pointer;
    transition: all var(--cs-transition);
    color: var(--cs-text-secondary);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    position: relative;
    white-space: nowrap;
    overflow: hidden;
}

.cs-nav-item:hover {
    background: var(--cs-bg-hover);
    color: var(--cs-text);
}

.cs-nav-item.active {
    background: var(--cs-primary-soft);
    color: var(--cs-primary-text);
}

.cs-nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 3px;
    background: var(--cs-primary);
    border-radius: 0 3px 3px 0;
}

.cs-nav-item i {
    font-size: 18px;
    width: 24px;
    min-width: 24px;
    text-align: center;
}

.cs-nav-item .cs-nav-label {
    transition: opacity 200ms ease;
}

.cs-sidebar.collapsed .cs-nav-label {
    opacity: 0;
    width: 0;
    overflow: hidden;
}

/* Sidebar Toggle */
.cs-sidebar-toggle {
    padding: 12px;
    border-top: 1px solid var(--cs-border);
    display: flex;
    justify-content: center;
}

.cs-sidebar-toggle button {
    width: 36px;
    height: 36px;
    border-radius: var(--cs-radius-sm);
    border: 1px solid var(--cs-border);
    background: var(--cs-bg-elevated);
    color: var(--cs-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--cs-transition);
}

.cs-sidebar-toggle button:hover {
    background: var(--cs-bg-hover);
    color: var(--cs-text);
}

/* ── Main Content ── */
.cs-main {
    flex: 1;
    margin-left: var(--cs-sidebar-w);
    transition: margin-left 300ms ease;
    min-height: 100vh;
}

.cs-sidebar.collapsed ~ .cs-main,
.cs-main.sidebar-collapsed {
    margin-left: var(--cs-sidebar-collapsed-w);
}

.cs-content {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 40px;
}

/* ── Page Headers (Pomelli style: icon + italic serif title + subtitle) ── */
.cs-page-header {
    text-align: center;
    margin-bottom: 32px;
    padding-top: 8px;
}

.cs-page-header .cs-page-icon {
    font-size: 32px;
    color: var(--cs-primary-text);
    margin-bottom: 8px;
}

.cs-page-header h1 {
    font-family: var(--cs-font-serif);
    font-style: italic;
    font-weight: 400;
    font-size: 28px;
    color: var(--cs-text);
    margin: 0 0 8px 0;
    letter-spacing: -0.01em;
}

.cs-page-header p {
    font-size: 14px;
    color: var(--cs-text-muted);
    margin: 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
}

/* ── Glass Card ── */
.cs-card {
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--cs-border);
    border-radius: var(--cs-radius-lg);
    box-shadow: var(--cs-glass);
    transition: box-shadow var(--cs-transition), border-color var(--cs-transition);
}

.cs-card:hover {
    box-shadow: var(--cs-glass-hover);
    border-color: rgba(255, 255, 255, 0.45);
}

.cs-card-clickable {
    cursor: pointer;
}

.cs-card-clickable:hover {
    border-color: rgba(3, 252, 244, 0.3);
}

/* ── Buttons ── */
.cs-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: var(--cs-radius-pill);
    font-family: var(--cs-font);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--cs-transition);
    border: none;
    text-decoration: none;
    line-height: 1.4;
}

.cs-btn-primary {
    background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-hover));
    color: var(--cs-text-on-primary);
    box-shadow: 0 2px 8px rgba(3, 252, 244, 0.3);
}

.cs-btn-primary:hover {
    box-shadow: 0 4px 16px rgba(3, 252, 244, 0.4);
    transform: translateY(-1px);
}

.cs-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.cs-btn-secondary {
    background: var(--cs-bg-surface);
    backdrop-filter: blur(8px);
    border: 1px solid var(--cs-border-strong);
    color: var(--cs-text-secondary);
}

.cs-btn-secondary:hover {
    background: var(--cs-bg-hover);
    color: var(--cs-text);
    border-color: rgba(3, 252, 244, 0.2);
}

.cs-btn-ghost {
    background: transparent;
    color: var(--cs-text-secondary);
    padding: 8px 12px;
}

.cs-btn-ghost:hover {
    background: var(--cs-primary-soft);
    color: var(--cs-primary-text);
}

.cs-btn-danger {
    background: var(--cs-danger-soft);
    color: var(--cs-danger);
    border: 1px solid rgba(239, 68, 68, 0.15);
}

.cs-btn-danger:hover {
    background: rgba(239, 68, 68, 0.12);
}

.cs-btn-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    padding: 0;
    border-radius: var(--cs-radius-sm);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cs-btn-sm {
    padding: 6px 14px;
    font-size: 13px;
}

/* AI action buttons — sparkle icon prefix */
.cs-btn-ai {
    background: linear-gradient(135deg, rgba(3, 252, 244, 0.1), rgba(3, 252, 244, 0.05));
    border: 1px solid var(--cs-border-accent);
    color: var(--cs-primary-text);
}

.cs-btn-ai:hover {
    background: linear-gradient(135deg, rgba(3, 252, 244, 0.15), rgba(3, 252, 244, 0.08));
    border-color: rgba(3, 252, 244, 0.3);
}

.cs-btn-ai i.fa-sparkles,
.cs-btn-ai i.fa-wand-magic-sparkles {
    color: var(--cs-primary-text);
}

/* ── Form Inputs ── */
.cs-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--cs-border-strong);
    border-radius: var(--cs-radius);
    background: var(--cs-bg-input);
    backdrop-filter: blur(8px);
    color: var(--cs-text);
    font-family: var(--cs-font);
    font-size: 14px;
    transition: all var(--cs-transition);
    outline: none;
}

.cs-input:focus {
    border-color: rgba(3, 252, 244, 0.4);
    box-shadow: 0 0 0 3px rgba(3, 252, 244, 0.1);
}

.cs-input::placeholder {
    color: var(--cs-text-muted);
}

textarea.cs-input {
    resize: vertical;
    min-height: 100px;
}

.cs-input-lg {
    padding: 14px 18px;
    font-size: 15px;
    min-height: 120px;
}

/* ── Chips / Tags ── */
.cs-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: var(--cs-radius-pill);
    background: var(--cs-primary-soft);
    border: 1px solid var(--cs-border-accent);
    color: var(--cs-primary-text);
    font-size: 13px;
    font-weight: 500;
}

.cs-chip-remove {
    cursor: pointer;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.08);
    font-size: 10px;
    transition: background var(--cs-transition);
}

.cs-chip-remove:hover {
    background: rgba(239, 68, 68, 0.2);
    color: var(--cs-danger);
}

/* ── Color Swatches ── */
.cs-color-swatch {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid var(--cs-bg-surface-solid);
    box-shadow: var(--cs-glass-sm);
    cursor: pointer;
    transition: transform var(--cs-transition), box-shadow var(--cs-transition);
    position: relative;
}

.cs-color-swatch:hover {
    transform: scale(1.1);
    box-shadow: var(--cs-glass);
}

.cs-color-swatch .cs-swatch-remove {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--cs-danger);
    color: white;
    font-size: 10px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.cs-color-swatch:hover .cs-swatch-remove {
    display: flex;
}

/* ── Ingredient Buttons (below prompt) ── */
.cs-ingredients {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.cs-ingredient-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--cs-radius-pill);
    background: var(--cs-bg-surface);
    backdrop-filter: blur(8px);
    border: 1px solid var(--cs-border-strong);
    color: var(--cs-text-secondary);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--cs-transition);
}

.cs-ingredient-btn:hover {
    background: var(--cs-primary-soft);
    border-color: var(--cs-border-accent);
    color: var(--cs-primary-text);
}

.cs-ingredient-btn i {
    font-size: 14px;
}

/* ── Breadcrumb / Back Navigation ── */
.cs-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: var(--cs-radius-pill);
    border: 1px solid var(--cs-border-strong);
    background: var(--cs-bg-surface);
    backdrop-filter: blur(8px);
    color: var(--cs-primary-text);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--cs-transition);
    text-decoration: none;
    margin-bottom: 20px;
}

.cs-breadcrumb:hover {
    background: var(--cs-primary-soft);
    border-color: var(--cs-border-accent);
}

/* ── Idea Cards (left green border accent) ── */
.cs-idea-card {
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    border: 1px solid var(--cs-border);
    border-left: 3px solid var(--cs-primary);
    border-radius: var(--cs-radius);
    padding: 16px 20px;
    cursor: pointer;
    transition: all var(--cs-transition);
}

.cs-idea-card:hover {
    box-shadow: var(--cs-glass);
    border-color: rgba(3, 252, 244, 0.3);
    border-left-color: var(--cs-primary);
}

.cs-idea-card h3 {
    font-size: 15px;
    font-weight: 600;
    color: var(--cs-text);
    margin: 0 0 6px 0;
}

.cs-idea-card p {
    font-size: 13px;
    color: var(--cs-text-muted);
    margin: 0;
    line-height: 1.5;
}

/* ── Skeleton Shimmer ── */
.cs-skeleton {
    background: linear-gradient(90deg, var(--cs-bg-elevated) 25%, rgba(255,255,255,0.5) 50%, var(--cs-bg-elevated) 75%);
    background-size: 200% 100%;
    animation: cs-shimmer 1.5s ease-in-out infinite;
    border-radius: var(--cs-radius);
}

@keyframes cs-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* ── Accordion ── */
.cs-accordion-header {
    display: flex;
    align-items: center;
    justify-content: between;
    padding: 14px 16px;
    cursor: pointer;
    transition: all var(--cs-transition);
    border-bottom: 1px solid transparent;
}

.cs-accordion-header:hover {
    background: var(--cs-bg-elevated);
}

.cs-accordion-header.expanded {
    border-bottom-color: var(--cs-border);
}

.cs-accordion-body {
    padding: 16px;
}

/* ── Modal / Dialog ── */
.cs-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(4px);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cs-modal {
    background: var(--cs-bg-surface-solid);
    border-radius: var(--cs-radius-lg);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.15);
    width: 90%;
    max-width: 480px;
    max-height: 85vh;
    overflow-y: auto;
    padding: 24px;
}

.cs-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.cs-modal-header h2 {
    font-family: var(--cs-font);
    font-size: 18px;
    font-weight: 600;
    color: var(--cs-text);
    margin: 0;
}

.cs-modal-close {
    width: 32px;
    height: 32px;
    border-radius: var(--cs-radius-sm);
    border: none;
    background: var(--cs-bg-elevated);
    color: var(--cs-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--cs-transition);
}

.cs-modal-close:hover {
    background: var(--cs-danger-soft);
    color: var(--cs-danger);
}

/* ── Bottom Sheet ── */
.cs-bottom-sheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: var(--cs-bg-surface-solid);
    border-top-left-radius: var(--cs-radius-lg);
    border-top-right-radius: var(--cs-radius-lg);
    box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.12);
    padding: 24px;
    transform: translateY(100%);
    transition: transform 300ms ease;
}

.cs-bottom-sheet.open {
    transform: translateY(0);
}

/* ── Creative Card ── */
.cs-creative-card {
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    border: 1px solid var(--cs-border);
    border-radius: var(--cs-radius-lg);
    overflow: hidden;
    position: relative;
    transition: all var(--cs-transition);
}

.cs-creative-card:hover {
    box-shadow: var(--cs-glass-hover);
    border-color: rgba(255, 255, 255, 0.45);
}

.cs-creative-card .cs-creative-image {
    width: 100%;
    aspect-ratio: 9 / 16;
    object-fit: cover;
    display: block;
    max-height: 480px;
}

.cs-creative-card .cs-creative-actions {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cs-creative-card .cs-creative-more {
    position: absolute;
    top: 12px;
    right: 12px;
}

/* ── Split Editor Layout ── */
.cs-editor-layout {
    display: grid;
    grid-template-columns: 55% 45%;
    min-height: calc(100vh - 80px);
    gap: 0;
}

.cs-editor-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
}

.cs-editor-panel {
    border-left: 1px solid var(--cs-border);
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    overflow-y: auto;
    max-height: calc(100vh - 80px);
}

/* ── Version History Bar ── */
.cs-version-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: var(--cs-bg-surface);
    backdrop-filter: blur(8px);
    border: 1px solid var(--cs-border);
    border-radius: var(--cs-radius);
    font-size: 13px;
    color: var(--cs-text-secondary);
}

.cs-version-bar button {
    width: 28px;
    height: 28px;
    border-radius: var(--cs-radius-sm);
    border: 1px solid var(--cs-border-strong);
    background: var(--cs-bg-elevated);
    color: var(--cs-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--cs-transition);
}

.cs-version-bar button:hover:not(:disabled) {
    background: var(--cs-primary-soft);
    color: var(--cs-primary-text);
    border-color: var(--cs-border-accent);
}

.cs-version-bar button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ── Disclaimer Text ── */
.cs-disclaimer {
    font-size: 12px;
    color: var(--cs-text-muted);
    text-align: center;
    margin-top: 12px;
}

/* ── Section Labels ── */
.cs-section-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--cs-text-muted);
    margin-bottom: 12px;
}

/* ── Progress Bars ── */
.cs-progress {
    height: 4px;
    background: var(--cs-bg-elevated);
    border-radius: 2px;
    overflow: hidden;
}

.cs-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--cs-primary), var(--cs-primary-hover));
    border-radius: 2px;
    transition: width 500ms ease;
}

/* ── Dropdown Menu ── */
.cs-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    min-width: 180px;
    background: var(--cs-bg-surface-solid);
    border: 1px solid var(--cs-border-strong);
    border-radius: var(--cs-radius);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    z-index: 50;
    padding: 4px;
}

.cs-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: var(--cs-radius-sm);
    font-size: 13px;
    color: var(--cs-text-secondary);
    cursor: pointer;
    transition: all var(--cs-transition);
}

.cs-dropdown-item:hover {
    background: var(--cs-primary-soft);
    color: var(--cs-primary-text);
}

.cs-dropdown-item.danger:hover {
    background: var(--cs-danger-soft);
    color: var(--cs-danger);
}

/* ── Toggle (eye visibility) ── */
.cs-toggle {
    width: 40px;
    height: 22px;
    border-radius: 11px;
    background: var(--cs-bg-elevated);
    border: 1px solid var(--cs-border-strong);
    cursor: pointer;
    position: relative;
    transition: all var(--cs-transition);
}

.cs-toggle.active {
    background: var(--cs-primary-soft);
    border-color: var(--cs-border-accent);
}

.cs-toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--cs-text-muted);
    transition: all var(--cs-transition);
}

.cs-toggle.active::after {
    left: 20px;
    background: var(--cs-primary-text);
}

/* ── Upload Area ── */
.cs-upload-area {
    border: 2px dashed var(--cs-border-accent);
    border-radius: var(--cs-radius-lg);
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all var(--cs-transition);
    background: var(--cs-primary-soft);
}

.cs-upload-area:hover {
    border-color: var(--cs-primary);
    background: rgba(3, 252, 244, 0.06);
}

/* ── Photoshoot Template Grid ── */
.cs-template-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.cs-template-thumb {
    aspect-ratio: 3 / 4;
    border-radius: var(--cs-radius);
    border: 2px solid transparent;
    overflow: hidden;
    cursor: pointer;
    transition: all var(--cs-transition);
}

.cs-template-thumb.selected {
    border-color: var(--cs-primary);
    box-shadow: 0 0 0 3px rgba(3, 252, 244, 0.2);
}

.cs-template-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── Aspect Ratio Select ── */
.cs-aspect-select {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--cs-radius-pill);
    background: var(--cs-bg-surface);
    border: 1px solid var(--cs-border-strong);
    color: var(--cs-text-secondary);
    font-size: 13px;
    cursor: pointer;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .cs-sidebar {
        width: var(--cs-sidebar-collapsed-w);
    }
    .cs-main {
        margin-left: var(--cs-sidebar-collapsed-w);
    }
    .cs-sidebar .cs-nav-label,
    .cs-sidebar .cs-brand-text,
    .cs-sidebar .cs-experiment-badge {
        opacity: 0;
        width: 0;
        overflow: hidden;
    }
    .cs-content {
        padding: 20px 16px;
    }
    .cs-editor-layout {
        grid-template-columns: 1fr;
    }
}
</style>
