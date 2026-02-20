<style>
/* ============================================
   CONTENT STUDIO — FROSTED GLASS DESIGN SYSTEM
   Pomelli-exact layout + ARTime frosted glass
   ============================================ */

@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap');

.cs-app {
    /* ── Alias cs-* to shared at-* tokens ── */
    --cs-bg-deep: var(--at-bg-deep);
    --cs-bg-surface: var(--at-bg-surface);
    --cs-bg-surface-solid: var(--at-bg-surface-solid);
    --cs-bg-elevated: var(--at-bg-elevated);
    --cs-bg-hover: var(--at-bg-hover);
    --cs-bg-input: var(--at-bg-input);
    --cs-border: var(--at-border);
    --cs-border-strong: var(--at-border-strong);
    --cs-border-accent: var(--at-border-accent);
    --cs-primary: var(--at-primary);
    --cs-primary-hover: var(--at-primary-hover);
    --cs-primary-soft: var(--at-primary-soft);
    --cs-primary-text: var(--at-primary-text);
    --cs-text-on-primary: var(--at-text-on-primary);
    --cs-success: var(--at-success);
    --cs-success-soft: var(--at-success-soft);
    --cs-warning: var(--at-warning);
    --cs-warning-soft: var(--at-warning-soft);
    --cs-danger: var(--at-danger);
    --cs-danger-soft: var(--at-danger-soft);
    --cs-text: var(--at-text);
    --cs-text-secondary: var(--at-text-secondary);
    --cs-text-muted: var(--at-text-muted);
    --cs-font: var(--at-font);
    --cs-font-serif: 'Playfair Display', Georgia, serif;
    --cs-radius: var(--at-radius);
    --cs-radius-lg: var(--at-radius-lg);
    --cs-radius-sm: var(--at-radius-sm);
    --cs-radius-pill: 50rem;
    --cs-glass: var(--at-glass);
    --cs-glass-hover: var(--at-glass-hover);
    --cs-glass-sm: var(--at-glass-sm);
    --cs-transition: var(--at-transition);

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

/* ── Vertical Layout (top tabs, no sidebar) ── */
.cs-layout-vertical {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* ── Top Navigation Bar ── */
.cs-topbar {
    background: var(--cs-bg-surface);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--cs-border);
    position: sticky;
    top: 0;
    z-index: 50;
}

.cs-topbar-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    height: 56px;
}

/* ── Business Selector ── */
.cs-business-selector {
    position: relative;
    flex-shrink: 0;
}

.cs-business-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 12px 6px 6px;
    border-radius: var(--cs-radius);
    border: 1px solid var(--cs-border-strong);
    background: var(--cs-bg-elevated);
    cursor: pointer;
    transition: all var(--cs-transition);
    max-width: 220px;
}

.cs-business-btn:hover {
    background: var(--cs-bg-hover);
    border-color: var(--cs-border-accent);
}

.cs-business-logo {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    object-fit: cover;
}

.cs-business-logo-placeholder {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 6px;
    background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-hover));
    color: var(--cs-text-on-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
}

.cs-business-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--cs-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cs-business-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    min-width: 280px;
    background: var(--cs-bg-surface-solid);
    border: 1px solid var(--cs-border-strong);
    border-radius: var(--cs-radius);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    z-index: 60;
    padding: 6px;
}

.cs-business-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--cs-radius-sm);
    cursor: pointer;
    transition: all var(--cs-transition);
}

.cs-business-dropdown-item:hover {
    background: var(--cs-primary-soft);
}

.cs-business-dropdown-item.active {
    background: var(--cs-primary-soft);
}

.cs-business-mini-logo {
    width: 24px;
    height: 24px;
    min-width: 24px;
    border-radius: 5px;
    object-fit: cover;
}

.cs-business-mini-logo-placeholder {
    width: 24px;
    height: 24px;
    min-width: 24px;
    border-radius: 5px;
    background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-hover));
    color: var(--cs-text-on-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}

/* ── Section Tabs ── */
.cs-tabs {
    display: flex;
    align-items: center;
    gap: 2px;
    flex: 1;
    justify-content: center;
}

.cs-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: var(--cs-radius-pill);
    border: none;
    background: transparent;
    color: var(--cs-text-secondary);
    font-family: var(--cs-font);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--cs-transition);
    white-space: nowrap;
}

.cs-tab:hover:not(:disabled) {
    background: var(--cs-bg-hover);
    color: var(--cs-text);
}

.cs-tab.active {
    background: var(--cs-primary-soft);
    color: var(--cs-primary-text);
}

.cs-tab:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.cs-tab i {
    font-size: 15px;
}

.cs-topbar-btn-label {
    /* Hidden on small screens */
}

/* ── Main Content (full width, no sidebar offset) ── */
.cs-main-full {
    flex: 1;
    min-height: calc(100vh - 56px);
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

/* ── RTL Support (auto-detected via dir="auto") ── */
[dir="rtl"] .cs-idea-card,
.cs-idea-card:dir(rtl) {
    border-left: 1px solid var(--cs-border);
    border-right: 3px solid var(--cs-primary);
}

[dir="rtl"] .cs-editor-panel,
.cs-editor-panel:dir(rtl) {
    border-left: none;
    border-right: 1px solid var(--cs-border);
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .cs-topbar-inner {
        padding: 0 12px;
        gap: 8px;
    }
    .cs-topbar-btn-label {
        display: none;
    }
    .cs-business-name {
        max-width: 80px;
    }
    .cs-tab span { display: none; }
    .cs-content {
        padding: 20px 16px;
    }
    .cs-editor-layout {
        grid-template-columns: 1fr;
    }
}
</style>
