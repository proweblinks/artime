{{-- Social Content: Viral Idea Generator + Video Concept Cloner --}}
<style>
    .vw-social-concept .vw-viral-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .vw-social-concept .vw-viral-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        background: linear-gradient(135deg, #06e3f7 0%, #03fcf4 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #0a2e2e;
    }
    .vw-social-concept .vw-viral-title {
        font-size: var(--vw-text-2xl);
        font-weight: 700;
        color: var(--vw-text);
    }
    .vw-social-concept .vw-viral-subtitle {
        font-size: var(--vw-text-base);
        color: var(--vw-text-secondary);
        margin-top: 0.25rem;
    }
    .vw-social-concept .vw-theme-input-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        align-items: stretch;
    }
    .vw-social-concept .vw-theme-input {
        flex: 1;
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border-accent);
        border-radius: var(--vw-radius-lg);
        padding: 0.75rem 1rem;
        color: var(--vw-text);
        font-size: var(--vw-text-md);
        font-family: var(--vw-font);
        outline: none;
        transition: border-color var(--vw-transition);
    }
    .vw-social-concept .vw-theme-input:focus {
        border-color: var(--vw-border-focus);
    }
    .vw-social-concept .vw-theme-input::placeholder {
        color: var(--vw-text-muted);
    }
    .vw-social-concept .vw-generate-viral-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #06e3f7 0%, #03fcf4 100%);
        color: #0a2e2e;
        border: none;
        border-radius: var(--vw-radius-lg);
        font-weight: 600;
        font-size: var(--vw-text-md);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        white-space: nowrap;
        box-shadow: var(--vw-clay-btn);
    }
    .vw-social-concept .vw-generate-viral-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: var(--vw-clay-btn-hover);
    }
    .vw-social-concept .vw-generate-viral-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .vw-social-concept .vw-ideas-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
        .vw-social-concept .vw-ideas-grid {
            grid-template-columns: 1fr;
        }
    }
    .vw-social-concept .vw-idea-card {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
        cursor: pointer;
        transition: all var(--vw-transition);
        position: relative;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-idea-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--vw-clay-hover);
    }
    .vw-social-concept .vw-idea-card.selected {
        box-shadow: var(--vw-clay-active);
    }
    .vw-social-concept .vw-idea-card.selected::after {
        content: '\2713';
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        width: 24px;
        height: 24px;
        background: var(--vw-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .vw-social-concept .vw-idea-title {
        font-size: var(--vw-text-lg);
        font-weight: 700;
        color: var(--vw-text);
        margin-bottom: 0.5rem;
        padding-right: 2rem;
    }
    .vw-social-concept .vw-idea-desc {
        font-size: var(--vw-text-sm);
        color: var(--vw-text-secondary);
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    .vw-social-concept .vw-idea-character {
        font-size: var(--vw-text-sm);
        color: var(--vw-text-secondary);
        margin-bottom: 0.5rem;
    }
    .vw-social-concept .vw-idea-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.75rem;
    }
    .vw-social-concept .vw-idea-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: var(--vw-radius-full);
        font-size: var(--vw-text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .vw-social-concept .vw-idea-badge.audio-music {
        background: rgba(168, 85, 247, 0.1);
        color: #00d4cc;
        border: 1px solid rgba(168, 85, 247, 0.2);
    }
    .vw-social-concept .vw-idea-badge.audio-voice {
        background: var(--vw-info-soft);
        color: #0284c7;
        border: 1px solid rgba(14, 165, 233, 0.2);
    }
    .vw-social-concept .vw-idea-badge.mood-funny { background: rgba(250, 204, 21, 0.1); color: #a16207; border: 1px solid rgba(250, 204, 21, 0.2); }
    .vw-social-concept .vw-idea-badge.mood-absurd { background: rgba(249, 115, 22, 0.1); color: #c2410c; border: 1px solid rgba(249, 115, 22, 0.2); }
    .vw-social-concept .vw-idea-badge.mood-wholesome { background: rgba(52, 211, 153, 0.1); color: #059669; border: 1px solid rgba(52, 211, 153, 0.2); }
    .vw-social-concept .vw-idea-badge.mood-chaotic { background: rgba(239, 68, 68, 0.08); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }
    .vw-social-concept .vw-idea-badge.mood-cute { background: rgba(236, 72, 153, 0.08); color: #be185d; border: 1px solid rgba(236, 72, 153, 0.2); }
    .vw-social-concept .vw-idea-badge.source-cloned {
        background: rgba(20, 184, 166, 0.1);
        color: #0d9488;
        border: 1px solid rgba(20, 184, 166, 0.2);
    }
    .vw-social-concept .vw-idea-hook {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-secondary);
        font-style: italic;
        line-height: 1.3;
    }
    .vw-social-concept .vw-generate-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: transparent;
        color: var(--vw-text-secondary);
        border: 1px solid var(--vw-border-accent);
        border-radius: var(--vw-radius-lg);
        font-weight: 600;
        font-size: var(--vw-text-base);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
    }
    .vw-social-concept .vw-generate-more-btn:hover:not(:disabled) {
        background: var(--vw-primary-soft);
        border-color: rgba(var(--vw-primary-rgb), 0.6);
    }
    .vw-social-concept .vw-generate-more-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .vw-social-concept .vw-skeleton-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
        .vw-social-concept .vw-skeleton-grid { grid-template-columns: 1fr; }
    }
    .vw-social-concept .vw-skeleton-card {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
        animation: vw-skeleton-pulse 1.5s ease-in-out infinite;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-skeleton-line {
        height: 0.75rem;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
    .vw-social-concept .vw-skeleton-line.short { width: 60%; }
    .vw-social-concept .vw-skeleton-line.medium { width: 80%; }
    .vw-social-concept .vw-skeleton-line.title { height: 1rem; width: 70%; margin-bottom: 0.75rem; }
    @keyframes vw-skeleton-pulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }
    .vw-social-concept .vw-engine-selector { margin-bottom: 1.5rem; }
    .vw-social-concept .vw-engine-selector h3 {
        font-size: var(--vw-text-sm); font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .vw-social-concept .vw-engine-cards {
        display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
    }
    @media (max-width: 640px) { .vw-social-concept .vw-engine-cards { grid-template-columns: 1fr; } }
    .vw-social-concept .vw-engine-card {
        background: var(--vw-bg-elevated); border: none; border-radius: var(--vw-radius-lg);
        padding: 1rem; cursor: pointer; transition: all var(--vw-transition); position: relative;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-engine-card:hover { box-shadow: var(--vw-clay-hover); transform: translateY(-2px); }
    .vw-social-concept .vw-engine-card.active { box-shadow: var(--vw-clay-active); }
    .vw-social-concept .vw-engine-card .vw-engine-icon { font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--vw-text-secondary); }
    .vw-social-concept .vw-engine-card.active .vw-engine-icon { color: var(--vw-primary); }
    .vw-social-concept .vw-engine-card h4 { font-size: var(--vw-text-md); font-weight: 700; color: var(--vw-text); margin-bottom: 0.35rem; }
    .vw-social-concept .vw-engine-card p { font-size: var(--vw-text-xs); color: var(--vw-text-secondary); line-height: 1.4; margin-bottom: 0.5rem; }
    .vw-social-concept .vw-engine-card .vw-engine-badge {
        display: inline-block; padding: 0.15rem 0.5rem; border-radius: var(--vw-radius-full); font-size: 0.65rem;
        font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
        background: var(--vw-primary-soft); color: var(--vw-text-secondary); border: 1px solid var(--vw-border-accent);
    }

    /* Source Tabs */
    .vw-social-concept .vw-source-tabs { margin-bottom: 1.5rem; }
    .vw-social-concept .vw-tab-row {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 1.25rem;
        background: var(--vw-bg-elevated);
        border-radius: var(--vw-radius-lg);
        padding: 0.25rem;
        border: none;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-tab-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        background: transparent;
        color: var(--vw-text-secondary);
        border: none;
        border-radius: var(--vw-radius-md);
        font-weight: 600;
        font-size: var(--vw-text-base);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .vw-social-concept .vw-tab-btn:hover { color: var(--vw-text); background: rgba(var(--vw-primary-rgb), 0.08); }
    .vw-social-concept .vw-tab-btn.active {
        background: var(--vw-primary-soft);
        color: var(--vw-text);
        border: 1px solid var(--vw-border-accent);
    }

    /* Clone Video UI */
    .vw-social-concept .vw-clone-toggle {
        display: flex;
        gap: 0;
        margin-bottom: 1rem;
        background: var(--vw-bg-elevated);
        border-radius: var(--vw-radius-md);
        border: none;
        overflow: hidden;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-clone-toggle-btn {
        flex: 1;
        padding: 0.55rem 0.75rem;
        background: transparent;
        border: none;
        color: var(--vw-text-secondary);
        font-size: var(--vw-text-sm);
        font-weight: 500;
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }
    .vw-social-concept .vw-clone-toggle-btn:hover { color: var(--vw-text); background: rgba(var(--vw-primary-rgb), 0.06); }
    .vw-social-concept .vw-clone-toggle-btn.active {
        background: var(--vw-primary-soft);
        color: var(--vw-text);
    }
    .vw-social-concept .vw-template-picker {
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-template-label {
        font-size: var(--vw-text-xs); color: var(--vw-text-secondary); margin-bottom: 0.5rem;
        display: flex; align-items: center; gap: 0.4rem;
    }
    .vw-social-concept .vw-template-pills {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    .vw-social-concept .vw-template-pill {
        display: flex; align-items: center; gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-full); color: var(--vw-text-secondary);
        font-size: var(--vw-text-sm); cursor: pointer;
        transition: all var(--vw-transition);
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-template-pill:hover {
        color: var(--vw-text);
        box-shadow: var(--vw-clay-hover);
        transform: translateY(-2px);
    }
    .vw-social-concept .vw-template-pill.active {
        background: var(--vw-primary-soft);
        color: var(--vw-text);
        box-shadow: var(--vw-clay-active);
    }
    .vw-social-concept .vw-template-desc {
        font-size: var(--vw-text-xs); color: var(--vw-text-muted); margin-top: 0.4rem;
        font-style: italic;
    }
    .vw-social-concept .vw-url-import-box {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
        margin-bottom: 1rem;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-url-input-row {
        display: flex;
        gap: 0.5rem;
    }
    .vw-social-concept .vw-url-input-wrap {
        flex: 1;
        position: relative;
    }
    .vw-social-concept .vw-url-input-icon {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--vw-text-muted);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .vw-social-concept .vw-url-input {
        width: 100%;
        padding: 0.65rem 0.85rem 0.65rem 2.3rem;
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: var(--vw-radius);
        color: var(--vw-text);
        font-size: var(--vw-text-base);
        font-family: var(--vw-font);
        outline: none;
        transition: box-shadow var(--vw-transition);
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-url-input:focus { box-shadow: var(--vw-clay-active); }
    .vw-social-concept .vw-url-input::placeholder { color: var(--vw-text-muted); }
    .vw-social-concept .vw-url-analyze-btn {
        padding: 0.65rem 1.2rem;
        background: linear-gradient(135deg, #06e3f7 0%, #03fcf4 100%);
        color: #0a2e2e;
        border: none;
        border-radius: var(--vw-radius);
        font-weight: 600;
        font-size: var(--vw-text-base);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: var(--vw-clay-btn);
    }
    .vw-social-concept .vw-url-analyze-btn:hover { transform: translateY(-1px); box-shadow: var(--vw-clay-btn-hover); }
    .vw-social-concept .vw-url-analyze-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .vw-social-concept .vw-url-platforms {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-top: 0.75rem;
        color: var(--vw-text-muted);
        font-size: var(--vw-text-xs);
    }
    .vw-social-concept .vw-url-platforms i { font-size: 0.95rem; opacity: 0.7; }
    .vw-social-concept .vw-url-platforms span { margin-left: 0.1rem; }
    .vw-social-concept .vw-upload-dropzone {
        border: 2px dashed var(--vw-border-accent);
        border-radius: var(--vw-radius-lg);
        padding: 2rem;
        text-align: center;
        transition: all var(--vw-transition);
        background: var(--vw-bg-surface);
        cursor: pointer;
        position: relative;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-upload-dropzone:hover,
    .vw-social-concept .vw-upload-dropzone.dragging {
        border-color: rgba(var(--vw-primary-rgb), 0.6);
        background: rgba(var(--vw-primary-rgb), 0.05);
    }
    .vw-social-concept .vw-dropzone-content { color: var(--vw-text-secondary); }
    .vw-social-concept .vw-dropzone-icon { font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--vw-text-muted); }
    .vw-social-concept .vw-dropzone-content p { font-size: var(--vw-text-md); color: var(--vw-text-secondary); margin-bottom: 0.25rem; }
    .vw-social-concept .vw-dropzone-content small { font-size: var(--vw-text-xs); color: var(--vw-text-muted); }
    .vw-social-concept .vw-video-preview {
        max-height: 280px;
        width: 100%;
        border-radius: var(--vw-radius-lg);
        object-fit: contain;
        background: #000;
    }
    .vw-social-concept .vw-remove-video {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.75rem;
        padding: 0.4rem 0.8rem;
        background: var(--vw-danger-soft);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--vw-radius);
        font-size: var(--vw-text-sm);
        cursor: pointer;
        transition: all var(--vw-transition);
    }
    .vw-social-concept .vw-remove-video:hover { background: rgba(239, 68, 68, 0.25); }
    .vw-social-concept .vw-analyze-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #06e3f7 0%, #03fcf4 100%);
        color: #0a2e2e;
        border: none;
        border-radius: var(--vw-radius-lg);
        font-weight: 600;
        font-size: var(--vw-text-md);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        width: 100%;
        justify-content: center;
        margin-bottom: 1rem;
        box-shadow: var(--vw-clay-btn);
    }
    .vw-social-concept .vw-analyze-btn:hover { transform: translateY(-1px); box-shadow: var(--vw-clay-btn-hover); }
    .vw-social-concept .vw-analysis-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1.25rem;
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: var(--vw-radius-lg);
        margin-bottom: 1rem;
        color: var(--vw-text-secondary);
        font-size: var(--vw-text-base);
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-progress-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(var(--vw-primary-rgb), 0.3);
        border-top-color: var(--vw-primary);
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }
    .vw-social-concept .vw-analysis-error {
        padding: 0.75rem 1rem;
        background: var(--vw-danger-soft);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--vw-radius-lg);
        color: #dc2626;
        font-size: var(--vw-text-sm);
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-cloned-idea-card {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
        position: relative;
        margin-bottom: 1rem;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-cloned-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: var(--vw-radius-full);
        font-size: var(--vw-text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        background: rgba(20, 184, 166, 0.15);
        color: #0d9488;
        border: 1px solid rgba(20, 184, 166, 0.3);
        margin-bottom: 0.75rem;
    }
    .vw-social-concept .vw-cloned-prompt-preview {
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: var(--vw-radius);
        padding: 0.75rem;
        margin: 0.75rem 0;
        font-size: var(--vw-text-sm);
        color: var(--vw-text-secondary);
        line-height: 1.4;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-cloned-prompt-preview strong { color: var(--vw-text); font-size: var(--vw-text-xs); text-transform: uppercase; letter-spacing: 0.03em; }
    .vw-social-concept .vw-cloned-prompt-preview p { margin-top: 0.35rem; }
    .vw-social-concept .vw-use-concept-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: var(--vw-success);
        color: var(--vw-text-bright);
        border: none;
        border-radius: var(--vw-radius-lg);
        font-weight: 600;
        font-size: var(--vw-text-base);
        font-family: var(--vw-font);
        cursor: pointer;
        transition: all var(--vw-transition);
        width: 100%;
        justify-content: center;
        margin-top: 0.5rem;
    }
    .vw-social-concept .vw-use-concept-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4); }

    /* Chaos Controls */
    .vw-social-concept .vw-chaos-controls {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: var(--vw-radius-lg);
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-chaos-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .vw-social-concept .vw-chaos-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: var(--vw-text-sm);
        font-weight: 600;
        color: var(--vw-text-secondary);
        white-space: nowrap;
        min-width: fit-content;
    }
    .vw-social-concept .vw-chaos-slider {
        flex: 1;
        height: 6px;
        border-radius: 3px;
        appearance: none;
        -webkit-appearance: none;
        background: linear-gradient(90deg, rgba(var(--vw-primary-rgb), 0.5) 0%, rgba(249,115,22,0.5) 50%, rgba(239,68,68,0.6) 100%);
        cursor: pointer;
        min-width: 120px;
    }
    .vw-social-concept .vw-chaos-slider::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-danger) 100%);
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .vw-social-concept .vw-chaos-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-danger) 100%);
        cursor: pointer;
        border: none;
    }
    .vw-social-concept .vw-chaos-badge {
        font-weight: 600;
        font-size: var(--vw-text-xs);
        padding: 0.15rem 0.5rem;
        border-radius: 1rem;
        transition: all var(--vw-transition);
        white-space: nowrap;
    }
    .vw-social-concept .vw-chaos-badge.calm { color: #0891b2; background: rgba(3,252,244,0.15); }
    .vw-social-concept .vw-chaos-badge.rising { color: #fbbf24; background: rgba(251,191,36,0.15); }
    .vw-social-concept .vw-chaos-badge.intense { color: #fb923c; background: rgba(249,115,22,0.15); }
    .vw-social-concept .vw-chaos-badge.wild { color: #f87171; background: rgba(248,113,113,0.15); }
    .vw-social-concept .vw-chaos-badge.chaos { color: #ff4444; background: rgba(239,68,68,0.2); text-shadow: 0 0 6px rgba(239,68,68,0.5); }
    .vw-social-concept .vw-chaos-desc-input {
        width: 100%;
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: var(--vw-radius);
        padding: 0.5rem 0.75rem;
        color: var(--vw-text);
        font-size: var(--vw-text-sm);
        font-family: var(--vw-font);
        outline: none;
        margin-top: 0.6rem;
        transition: box-shadow var(--vw-transition);
        box-shadow: var(--vw-clay);
    }
    .vw-social-concept .vw-chaos-desc-input:focus {
        box-shadow: var(--vw-clay-active);
    }
    .vw-social-concept .vw-chaos-desc-input::placeholder {
        color: var(--vw-text-muted);
    }

    /* User template additions */
    .vw-social-concept .vw-template-divider {
        font-size: var(--vw-text-xs); color: var(--vw-text-muted); margin: 0.6rem 0 0.3rem;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .vw-social-concept .vw-template-pill-wrap {
        position: relative; display: inline-flex;
    }
    .vw-social-concept .vw-template-delete {
        position: absolute; top: -4px; right: -4px;
        width: 16px; height: 16px; border-radius: 50%;
        background: rgba(239, 68, 68, 0.8); border: none;
        color: white; font-size: 0.55rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity var(--vw-transition);
    }
    .vw-social-concept .vw-template-pill-wrap:hover .vw-template-delete { opacity: 1; }

    /* Save as Template */
    .vw-save-template-wrap { margin-top: 0.75rem; }
    .vw-save-template-btn {
        font-size: var(--vw-text-xs); color: var(--vw-text-secondary); background: none;
        border: 1px dashed var(--vw-border-accent); border-radius: var(--vw-radius);
        padding: 0.4rem 0.8rem; cursor: pointer; transition: all var(--vw-transition);
        display: inline-flex; align-items: center; gap: 0.35rem;
        font-family: var(--vw-font);
    }
    .vw-save-template-btn:hover {
        border-color: rgba(var(--vw-primary-rgb), 0.6); color: var(--vw-text);
    }
    .vw-save-template-form {
        margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem;
        padding: 0.75rem; background: var(--vw-bg-elevated);
        border: none; border-radius: var(--vw-radius);
        box-shadow: var(--vw-clay);
    }
    .vw-save-template-input {
        background: var(--vw-bg-deep); border: none;
        border-radius: var(--vw-radius-sm); padding: 0.4rem 0.6rem; color: var(--vw-text);
        font-size: var(--vw-text-sm); outline: none; font-family: var(--vw-font);
        box-shadow: var(--vw-clay);
    }
    .vw-save-template-input:focus { box-shadow: var(--vw-clay-active); }
    .vw-save-template-toggle {
        display: flex; align-items: center; gap: 0.4rem;
        font-size: var(--vw-text-xs); color: var(--vw-text-secondary); cursor: pointer;
    }
    .vw-save-template-confirm {
        align-self: flex-start; padding: 0.35rem 0.7rem;
        background: var(--vw-primary-soft); border: 1px solid var(--vw-border-accent);
        border-radius: var(--vw-radius-sm); color: var(--vw-text); font-size: var(--vw-text-xs);
        cursor: pointer; transition: all var(--vw-transition);
        display: inline-flex; align-items: center; gap: 0.3rem;
        font-family: var(--vw-font);
    }
    .vw-save-template-confirm:hover { background: rgba(var(--vw-primary-rgb), 0.3); }
</style>

<div class="vw-social-concept" x-data="{ viralTheme: '' }">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert">
                <i class="fas fa-triangle-exclamation" style="margin-right: 0.5rem;"></i>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-viral-header">
            <div class="vw-viral-icon"><i class="fas fa-fire"></i></div>
            <div>
                <h2 class="vw-viral-title">{{ __('Create Viral Content') }}</h2>
                <p class="vw-viral-subtitle">{{ __('AI generates trending video ideas — pick one and bring it to life') }}</p>
            </div>
        </div>

        {{-- Video Engine Selector --}}
        <div class="vw-engine-selector">
            <h3>{{ __('Choose Your Video Style') }}</h3>
            <div class="vw-engine-cards">
                <div class="vw-engine-card {{ $videoEngine === 'seedance' ? 'active' : '' }}"
                     wire:click="setVideoEngine('seedance')">
                    <div class="vw-engine-icon"><i class="fas fa-film"></i></div>
                    <h4>{{ __('Cinematic Scene') }}</h4>
                    <p>{{ __('AI generates video + voice + sound effects from a single prompt. Perfect for visual gags, animals in situations, dramatic scenes.') }}</p>
                    <span class="vw-engine-badge">{{ __('Auto Audio') }}</span>
                </div>
                <div class="vw-engine-card {{ $videoEngine === 'infinitetalk' ? 'active' : '' }}"
                     wire:click="setVideoEngine('infinitetalk')">
                    <div class="vw-engine-icon"><i class="fas fa-comments"></i></div>
                    <h4>{{ __('Lip-Sync Talking') }}</h4>
                    <p>{{ __('Characters speak with precise lip-sync from custom voices. Perfect for dialogue, narration, character conversations.') }}</p>
                    <span class="vw-engine-badge">{{ __('Custom Voices') }}</span>
                </div>
            </div>
        </div>

        {{-- ========================== Workflow Selector (Primary Control) ========================== --}}
        @php
            $availableWorkflows = $this->getAvailableWorkflows();
        @endphp
        <div style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: var(--vw-bg-surface); border: none; border-radius: var(--vw-radius-lg); box-shadow: var(--vw-clay);">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-diagram-project" style="color: var(--vw-primary); font-size: 0.85rem;"></i>
                <span style="font-size: var(--vw-text-sm); font-weight: 600; color: var(--vw-text);">{{ __('Workflow') }}</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @foreach($availableWorkflows as $wf)
                    <button type="button"
                        style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: var(--vw-radius-full); font-size: var(--vw-text-sm); font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1.5px solid {{ $activeWorkflowId == $wf['id'] ? 'var(--vw-primary)' : 'var(--vw-border)' }}; background: {{ $activeWorkflowId == $wf['id'] ? 'var(--vw-primary-soft)' : 'rgba(255,255,255,0.03)' }}; color: {{ $activeWorkflowId == $wf['id'] ? 'var(--vw-text)' : 'var(--vw-text-secondary)' }}; font-family: var(--vw-font);"
                        wire:click="selectWorkflow({{ $wf['id'] }})">
                        @if(($wf['mode'] ?? 'generate') === 'clone')
                            <i class="fa-solid fa-clone"></i>
                        @else
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        @endif
                        {{ $wf['name'] }}
                    </button>
                @endforeach
            </div>
            @if($activeWorkflowName)
                <div style="margin-top: 0.5rem; font-size: var(--vw-text-xs); color: var(--vw-text-muted); font-style: italic;">
                    @foreach($availableWorkflows as $wf)
                        @if($activeWorkflowId == $wf['id'])
                            {{ $wf['description'] }}
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ========================== Generate Mode ========================== --}}
        @if($activeWorkflowMode !== 'clone')
            {{-- Theme Input + Generate Button --}}
            <div class="vw-theme-input-row">
                <input type="text"
                       class="vw-theme-input"
                       x-model="viralTheme"
                       placeholder="{{ __('Describe a theme (e.g., cats, cooking, gym life) or leave blank for random ideas...') }}"
                       @keydown.enter="$wire.generateViralIdeas(viralTheme)" />
                <button class="vw-generate-viral-btn"
                        wire:click="generateViralIdeas(viralTheme)"
                        x-on:click="$wire.generateViralIdeas(viralTheme)"
                        wire:loading.attr="disabled"
                        @if($isLoading) disabled @endif>
                    <span wire:loading.remove wire:target="generateViralIdeas">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        {{ __('Generate Viral Ideas') }}
                    </span>
                    <span wire:loading wire:target="generateViralIdeas">
                        <span class="vw-loading-inner"></span>
                        {{ __('Generating...') }}
                    </span>
                </button>
            </div>

            {{-- Loading Skeleton --}}
            @if($isLoading && empty($conceptVariations))
                <div class="vw-skeleton-grid">
                    @for($i = 0; $i < 6; $i++)
                        <div class="vw-skeleton-card">
                            <div class="vw-skeleton-line title"></div>
                            <div class="vw-skeleton-line medium"></div>
                            <div class="vw-skeleton-line"></div>
                            <div class="vw-skeleton-line short"></div>
                        </div>
                    @endfor
                </div>
            @endif

            {{-- Idea Cards Grid --}}
            @if(!empty($conceptVariations))
                <div class="vw-ideas-grid">
                    @foreach($conceptVariations as $index => $idea)
                        <div class="vw-idea-card {{ $selectedConceptIndex === $index ? 'selected' : '' }}"
                             wire:click="selectViralIdea({{ $index }})">
                            <div class="vw-idea-title">
                                {{ $idea['title'] ?? 'Untitled' }}
                            </div>
                            <div class="vw-idea-character">
                                @if(($idea['speechType'] ?? '') === 'dialogue' && !empty($idea['characters']))
                                    @foreach($idea['characters'] as $ci => $char)
                                        {{ $char['name'] ?? '' }}{{ $ci < count($idea['characters']) - 1 ? ' vs ' : '' }}
                                    @endforeach
                                    &mdash; {{ $idea['situation'] ?? '' }}
                                @else
                                    {{ $idea['character'] ?? '' }} &mdash; {{ $idea['situation'] ?? '' }}
                                @endif
                            </div>
                            <div class="vw-idea-badges">
                                @if(($idea['source'] ?? '') === 'cloned')
                                    <span class="vw-idea-badge source-cloned">
                                        <i class="fa-solid fa-clone"></i> Cloned
                                    </span>
                                @endif
                                @if(($idea['speechType'] ?? '') === 'dialogue')
                                    <span class="vw-idea-badge audio-voice">
                                        <i class="fa-solid fa-comments"></i> Dialogue
                                    </span>
                                @elseif(($idea['audioType'] ?? '') === 'music-lipsync')
                                    <span class="vw-idea-badge audio-music">
                                        <i class="fa-solid fa-music"></i> Music Lip-Sync
                                    </span>
                                @else
                                    <span class="vw-idea-badge audio-voice">
                                        <i class="fa-solid fa-microphone"></i> Monologue
                                    </span>
                                @endif
                                @php $mood = strtolower($idea['mood'] ?? 'funny'); @endphp
                                <span class="vw-idea-badge mood-{{ $mood }}">
                                    {{ ucfirst($mood) }}
                                </span>
                            </div>
                            @if(($idea['speechType'] ?? '') === 'dialogue' && !empty($idea['dialogueLines']))
                                <div class="vw-idea-desc" style="font-size: var(--vw-text-sm);">
                                    @foreach(array_slice($idea['dialogueLines'], 0, 3) as $line)
                                        <div style="margin-bottom: 0.2rem;"><strong>{{ $line['speaker'] ?? '' }}:</strong> "{{ $line['text'] ?? '' }}"</div>
                                    @endforeach
                                    @if(count($idea['dialogueLines']) > 3)
                                        <div style="color: var(--vw-text-muted);">+ {{ count($idea['dialogueLines']) - 3 }} more...</div>
                                    @endif
                                </div>
                            @else
                                <div class="vw-idea-desc">{{ $idea['audioDescription'] ?? '' }}</div>
                            @endif
                            <div class="vw-idea-hook">{{ $idea['viralHook'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Regenerate Ideas Button --}}
                <div style="display: flex; justify-content: center;">
                    <button class="vw-generate-more-btn"
                            wire:click="generateViralIdeas(viralTheme)"
                            x-on:click="$wire.generateViralIdeas(viralTheme)"
                            wire:loading.attr="disabled"
                            @if($isLoading) disabled @endif>
                        <span wire:loading.remove wire:target="generateViralIdeas">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            {{ __('Regenerate Ideas') }}
                        </span>
                        <span wire:loading wire:target="generateViralIdeas">
                            <span class="vw-loading-inner"></span>
                            {{ __('Generating...') }}
                        </span>
                    </button>
                </div>

                {{-- Save as Template --}}
                @php
                    $selectedIdea = $conceptVariations[$selectedConceptIndex ?? 0] ?? [];
                @endphp
                @if(!empty($selectedIdea['videoPrompt']))
                    <div x-data="{ showSaveForm: false, tplName: '', tplDesc: '', tplShared: false }" class="vw-save-template-wrap">
                        <button @click="showSaveForm = !showSaveForm" class="vw-save-template-btn" type="button">
                            <i class="fa-solid fa-bookmark"></i> {{ __('Save as Template') }}
                        </button>

                        <div x-show="showSaveForm" x-cloak class="vw-save-template-form">
                            <input type="text" x-model="tplName" placeholder="{{ __('Template name...') }}"
                                   class="vw-save-template-input" maxlength="100" />
                            <input type="text" x-model="tplDesc" placeholder="{{ __('Description (optional)...') }}"
                                   class="vw-save-template-input" maxlength="255" />
                            <label class="vw-save-template-toggle">
                                <input type="checkbox" x-model="tplShared" />
                                <span>{{ __('Share with team') }}</span>
                            </label>
                            <button @click="if(tplName.trim()) { $wire.saveAsTemplate(tplName.trim(), tplDesc.trim(), tplShared); showSaveForm = false; tplName = ''; tplDesc = ''; tplShared = false; }"
                                    class="vw-save-template-confirm" type="button">
                                <i class="fa-solid fa-check"></i> {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        @endif

        {{-- ========================== Clone Mode ========================== --}}
        @if($activeWorkflowMode === 'clone')
            <div class="vw-clone-zone"
                 x-data="{ isDragging: false, cloneMode: 'url' }">

                {{-- Clone Mode Toggle: Upload / Paste URL --}}
                <div class="vw-clone-toggle">
                    <button class="vw-clone-toggle-btn" :class="{ 'active': cloneMode === 'url' }"
                            @click="cloneMode = 'url'; $wire.set('videoAnalysisError', null)">
                        <i class="fa-solid fa-link"></i> {{ __('Paste URL') }}
                    </button>
                    <button class="vw-clone-toggle-btn" :class="{ 'active': cloneMode === 'upload' }"
                            @click="cloneMode = 'upload'; $wire.set('videoAnalysisError', null)">
                        <i class="fa-solid fa-cloud-arrow-up"></i> {{ __('Upload File') }}
                    </button>
                </div>

                {{-- ========= URL Mode ========= --}}
                <div x-show="cloneMode === 'url'" x-cloak>
                    <div class="vw-url-import-box">
                        <div class="vw-url-input-row">
                            <div class="vw-url-input-wrap">
                                <i class="fa-brands fa-youtube vw-url-input-icon" style="color: #ff0000;"></i>
                                <input type="url"
                                       wire:model.defer="conceptVideoUrl"
                                       class="vw-url-input"
                                       placeholder="{{ __('Paste a YouTube video URL...') }}"
                                       @keydown.enter="$wire.analyzeVideoFromUrl()" />
                            </div>
                            <button class="vw-url-analyze-btn"
                                    wire:click="analyzeVideoFromUrl"
                                    wire:loading.attr="disabled"
                                    wire:target="analyzeVideoFromUrl"
                                    @if($urlDownloadStage || $videoAnalysisStage) disabled @endif>
                                <span wire:loading.remove wire:target="analyzeVideoFromUrl">
                                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                                    {{ __('Analyze') }}
                                </span>
                                <span wire:loading wire:target="analyzeVideoFromUrl" style="display: none;">
                                    <span class="vw-loading-inner"></span>
                                </span>
                            </button>
                        </div>
                        <div class="vw-url-platforms">
                            <i class="fa-brands fa-youtube" style="color: #ff0000; opacity: 1;"></i>
                            <span>{{ __('YouTube videos only') }}</span>
                        </div>
                    </div>
                </div>

                {{-- ========= Upload Mode ========= --}}
                <div x-show="cloneMode === 'upload'" x-cloak>
                    <div class="vw-upload-dropzone"
                         :class="{ 'dragging': isDragging }"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; let fi = $el.querySelector('input[type=file]'); fi.files = $event.dataTransfer.files; fi.dispatchEvent(new Event('change'))"
                         @click="$el.querySelector('input[type=file]').click()">

                        @if($conceptVideoUpload)
                            <video class="vw-video-preview" controls onclick="event.stopPropagation()">
                                <source src="{{ $conceptVideoUpload->temporaryUrl() }}" type="{{ $conceptVideoUpload->getMimeType() }}">
                            </video>
                            <div>
                                <button class="vw-remove-video"
                                        wire:click="$set('conceptVideoUpload', null)"
                                        onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-trash-can"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        @else
                            <div class="vw-dropzone-content">
                                <div class="vw-dropzone-icon"><i class="fa-solid fa-video"></i></div>
                                <p>{{ __('Drop a short video here or click to upload') }}</p>
                                <small>{{ __('MP4, MOV, WebM up to 100MB') }}</small>
                            </div>
                        @endif

                        <input type="file"
                               x-ref="videoInput"
                               wire:model="conceptVideoUpload"
                               accept="video/mp4,video/quicktime,video/webm,video/x-msvideo"
                               style="display: none;" />
                    </div>

                    @if(!$conceptVideoUpload && !$videoAnalysisResult)
                        <div wire:loading wire:target="conceptVideoUpload" class="vw-analysis-progress" style="display: none;">
                            <div class="vw-progress-spinner"></div>
                            <span>{{ __('Uploading video...') }}</span>
                        </div>
                    @endif

                    @if($conceptVideoUpload && !$videoAnalysisStage)
                        <button class="vw-analyze-btn"
                                wire:click="analyzeUploadedVideo"
                                wire:loading.attr="disabled"
                                wire:target="analyzeUploadedVideo">
                            <span wire:loading.remove wire:target="analyzeUploadedVideo">
                                <i class="fa-solid fa-magnifying-glass-chart"></i>
                                {{ __('Analyze & Clone Concept') }}
                            </span>
                            <span wire:loading wire:target="analyzeUploadedVideo" style="display: none;">
                                <div class="vw-progress-spinner" style="display:inline-block;width:18px;height:18px;vertical-align:middle;margin-right:8px;"></div>
                                {{ __('Analyzing video...') }}
                            </span>
                        </button>
                    @endif
                </div>

                {{-- ========= Shared: Progress / Error / Result ========= --}}

                {{-- Download Progress (URL mode) --}}
                @if($urlDownloadStage === 'downloading')
                    <div class="vw-analysis-progress">
                        <div class="vw-progress-spinner"></div>
                        <span><i class="fa-solid fa-download" style="margin-right: 0.3rem;"></i> {{ __('Downloading video from URL...') }}</span>
                    </div>
                @endif

                {{-- Analysis Progress --}}
                @if($videoAnalysisStage)
                    <div class="vw-analysis-progress">
                        <div class="vw-progress-spinner"></div>
                        <span>
                            @if($videoAnalysisStage === 'analyzing')
                                {{ __('Analyzing video with AI vision...') }}
                            @elseif($videoAnalysisStage === 'transcribing')
                                {{ __('Transcribing audio...') }}
                            @elseif($videoAnalysisStage === 'synthesizing')
                                {{ __('Building concept...') }}
                            @else
                                {{ __('Processing...') }}
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Error --}}
                @if($videoAnalysisError)
                    <div class="vw-analysis-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ $videoAnalysisError }}
                    </div>
                @endif

                {{-- Analysis Result Card --}}
                @if($videoAnalysisResult)
                    <div class="vw-cloned-idea-card">
                        <div class="vw-cloned-badge">
                            <i class="fa-solid fa-clone"></i> {{ __('Cloned Concept') }}
                        </div>
                        @if(!empty($videoAnalysisResult['firstFrameUrl']))
                            <div style="margin: 0.75rem 0; border-radius: var(--vw-radius-lg); overflow: hidden; border: 2px solid var(--vw-border-accent); position: relative;">
                                <img src="{{ $videoAnalysisResult['firstFrameUrl'] }}" alt="{{ __('First frame') }}"
                                     style="width: 100%; max-height: 280px; object-fit: contain; background: #000;">
                                {{-- AI Image Studio floating button --}}
                                <button type="button"
                                        wire:click="openImageStudio('clone')"
                                        title="{{ __('AI Image Studio — Edit or Reimagine') }}"
                                        style="position: absolute; bottom: 2.5rem; right: 0.5rem; width: 36px; height: 36px; border-radius: 50%; border: none; background: var(--vw-primary); color: white; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(var(--vw-primary-rgb), 0.4); transition: transform 0.2s, box-shadow 0.2s; z-index: 5;"
                                        onmouseover="this.style.transform='scale(1.15)'; this.style.boxShadow='0 4px 16px rgba(3, 252, 244, 0.3)';"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(3, 252, 244, 0.2)';">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                </button>
                                <div style="padding: 0.4rem 0.6rem; background: var(--vw-primary-soft); font-size: var(--vw-text-xs); color: var(--vw-text-secondary); display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-solid fa-image"></i> {{ __('First frame — will be used as base image') }}
                                    @if($originalFirstFrameUrl && $originalFirstFrameUrl !== ($videoAnalysisResult['firstFrameUrl'] ?? ''))
                                        <span style="margin-left: auto; padding: 0.15rem 0.4rem; background: rgba(var(--vw-primary-rgb),0.1); border-radius: 0.25rem; font-size: 0.65rem; color: var(--vw-text-secondary); font-weight: 500;">
                                            <i class="fa-solid fa-pen-fancy" style="font-size: 0.55rem;"></i> {{ __('Edited') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="vw-idea-title">{{ $videoAnalysisResult['title'] ?? 'Cloned Concept' }}</div>
                        <div class="vw-idea-character">
                            @if(($videoAnalysisResult['speechType'] ?? '') === 'dialogue' && !empty($videoAnalysisResult['characters']))
                                @foreach($videoAnalysisResult['characters'] as $c)
                                    {{ $c['name'] ?? '' }}@if(!$loop->last) vs @endif
                                @endforeach
                                &mdash; {{ $videoAnalysisResult['situation'] ?? '' }}
                            @else
                                {{ $videoAnalysisResult['character'] ?? '' }} &mdash; {{ $videoAnalysisResult['situation'] ?? '' }}
                            @endif
                        </div>
                        <div class="vw-idea-desc">{{ $videoAnalysisResult['concept'] ?? '' }}</div>
                        @if(!empty($videoAnalysisResult['videoPrompt']))
                            <div class="vw-cloned-prompt-preview" x-data="{ expanded: false }">
                                <strong>{{ __('Video Prompt') }}</strong>
                                <p x-show="!expanded">{{ Str::limit($videoAnalysisResult['videoPrompt'], 200) }}
                                    @if(strlen($videoAnalysisResult['videoPrompt']) > 200)
                                        <a href="#" @click.prevent="expanded = true" style="color: var(--vw-primary); cursor: pointer;">Show more</a>
                                    @endif
                                </p>
                                <p x-show="expanded" x-cloak>{{ $videoAnalysisResult['videoPrompt'] }}
                                    <a href="#" @click.prevent="expanded = false" style="color: var(--vw-primary); cursor: pointer;">Show less</a>
                                </p>
                            </div>
                        @endif
                        <div class="vw-idea-hook">{{ $videoAnalysisResult['viralHook'] ?? '' }}</div>

                        {{-- Visual Analysis (transparency panel) --}}
                        @if(!empty($videoAnalysisResult['_visualAnalysis']))
                            <div class="vw-cloned-prompt-preview" x-data="{ showAnalysis: false }" style="margin-top: 0.5rem;">
                                <a href="#" @click.prevent="showAnalysis = !showAnalysis" style="color: var(--vw-primary); cursor: pointer; font-size: var(--vw-text-sm); display: flex; align-items: center; gap: 0.3rem;">
                                    <i class="fa-solid fa-microscope"></i>
                                    <span x-text="showAnalysis ? 'Hide Visual Analysis' : 'Show Visual Analysis (Gemini)'"></span>
                                </a>
                                <div x-show="showAnalysis" x-cloak style="margin-top: 0.5rem; max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-size: var(--vw-text-xs); line-height: 1.5; color: var(--vw-text-secondary); padding: 0.5rem; background: var(--vw-bg-elevated); border-radius: var(--vw-radius);">{{ $videoAnalysisResult['_visualAnalysis'] }}</div>
                            </div>
                        @endif

                        <button class="vw-use-concept-btn" wire:click="useAnalyzedConcept">
                            <i class="fa-solid fa-check"></i>
                            {{ __('Use This Concept') }}
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
