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
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .vw-social-concept .vw-viral-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #f1f5f9;
    }
    .vw-social-concept .vw-viral-subtitle {
        font-size: 0.875rem;
        color: #94a3b8;
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
        background: rgba(30, 30, 50, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .vw-social-concept .vw-theme-input:focus {
        border-color: rgba(139, 92, 246, 0.6);
    }
    .vw-social-concept .vw-theme-input::placeholder {
        color: #64748b;
    }
    .vw-social-concept .vw-generate-viral-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .vw-social-concept .vw-generate-viral-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
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
        background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 40, 0.98) 100%);
        border: 2px solid rgba(100, 100, 140, 0.2);
        border-radius: 1rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .vw-social-concept .vw-idea-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
    }
    .vw-social-concept .vw-idea-card.selected {
        border-color: #8b5cf6;
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
    }
    .vw-social-concept .vw-idea-card.selected::after {
        content: '\2713';
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        width: 24px;
        height: 24px;
        background: #8b5cf6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .vw-social-concept .vw-idea-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 0.5rem;
        padding-right: 2rem;
    }
    .vw-social-concept .vw-idea-desc {
        font-size: 0.85rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    .vw-social-concept .vw-idea-character {
        font-size: 0.8rem;
        color: #a78bfa;
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
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .vw-social-concept .vw-idea-badge.audio-music {
        background: rgba(168, 85, 247, 0.2);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.3);
    }
    .vw-social-concept .vw-idea-badge.audio-voice {
        background: rgba(34, 211, 238, 0.15);
        color: #67e8f9;
        border: 1px solid rgba(34, 211, 238, 0.3);
    }
    .vw-social-concept .vw-idea-badge.mood-funny { background: rgba(250, 204, 21, 0.15); color: #fde047; border: 1px solid rgba(250, 204, 21, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-absurd { background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-wholesome { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-chaotic { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-cute { background: rgba(236, 72, 153, 0.15); color: #f9a8d4; border: 1px solid rgba(236, 72, 153, 0.25); }
    .vw-social-concept .vw-idea-badge.source-cloned {
        background: rgba(20, 184, 166, 0.15);
        color: #5eead4;
        border: 1px solid rgba(20, 184, 166, 0.3);
    }
    .vw-social-concept .vw-idea-hook {
        font-size: 0.78rem;
        color: #94a3b8;
        font-style: italic;
        line-height: 1.3;
    }
    .vw-social-concept .vw-generate-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: transparent;
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-concept .vw-generate-more-btn:hover:not(:disabled) {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.6);
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
        background: rgba(30, 30, 50, 0.6);
        border: 1px solid rgba(100, 100, 140, 0.15);
        border-radius: 1rem;
        padding: 1.25rem;
        animation: vw-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .vw-social-concept .vw-skeleton-line {
        height: 0.75rem;
        background: rgba(100, 100, 140, 0.2);
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
        font-size: 0.85rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .vw-social-concept .vw-engine-cards {
        display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
    }
    @media (max-width: 640px) { .vw-social-concept .vw-engine-cards { grid-template-columns: 1fr; } }
    .vw-social-concept .vw-engine-card {
        background: rgba(30, 30, 50, 0.8); border: 2px solid rgba(100, 100, 140, 0.2); border-radius: 0.75rem;
        padding: 1rem; cursor: pointer; transition: all 0.2s; position: relative;
    }
    .vw-social-concept .vw-engine-card:hover { border-color: rgba(139, 92, 246, 0.4); transform: translateY(-1px); }
    .vw-social-concept .vw-engine-card.active { border-color: #8b5cf6; box-shadow: 0 0 15px rgba(139, 92, 246, 0.2); }
    .vw-social-concept .vw-engine-card .vw-engine-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
    .vw-social-concept .vw-engine-card h4 { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.35rem; }
    .vw-social-concept .vw-engine-card p { font-size: 0.78rem; color: #94a3b8; line-height: 1.4; margin-bottom: 0.5rem; }
    .vw-social-concept .vw-engine-card .vw-engine-badge {
        display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.65rem;
        font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
        background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3);
    }

    /* Source Tabs */
    .vw-social-concept .vw-source-tabs { margin-bottom: 1.5rem; }
    .vw-social-concept .vw-tab-row {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 1.25rem;
        background: rgba(20, 20, 40, 0.6);
        border-radius: 0.75rem;
        padding: 0.25rem;
    }
    .vw-social-concept .vw-tab-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        background: transparent;
        color: #94a3b8;
        border: none;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .vw-social-concept .vw-tab-btn:hover { color: #e2e8f0; background: rgba(139, 92, 246, 0.1); }
    .vw-social-concept .vw-tab-btn.active {
        background: rgba(139, 92, 246, 0.2);
        color: #e2e8f0;
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    /* Clone Video UI */
    .vw-social-concept .vw-clone-toggle {
        display: flex;
        gap: 0;
        margin-bottom: 1rem;
        background: rgba(15, 15, 30, 0.6);
        border-radius: 0.6rem;
        border: 1px solid rgba(139, 92, 246, 0.15);
        overflow: hidden;
    }
    .vw-social-concept .vw-clone-toggle-btn {
        flex: 1;
        padding: 0.55rem 0.75rem;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }
    .vw-social-concept .vw-clone-toggle-btn:hover { color: #e2e8f0; background: rgba(139, 92, 246, 0.05); }
    .vw-social-concept .vw-clone-toggle-btn.active {
        background: rgba(139, 92, 246, 0.2);
        color: #e2e8f0;
    }
    .vw-social-concept .vw-template-picker {
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-template-label {
        font-size: 0.78rem; color: #94a3b8; margin-bottom: 0.5rem;
        display: flex; align-items: center; gap: 0.4rem;
    }
    .vw-social-concept .vw-template-pills {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    .vw-social-concept .vw-template-pill {
        display: flex; align-items: center; gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        background: rgba(15, 15, 30, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 2rem; color: #94a3b8;
        font-size: 0.8rem; cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-concept .vw-template-pill:hover {
        border-color: rgba(139, 92, 246, 0.4); color: #e2e8f0;
    }
    .vw-social-concept .vw-template-pill.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.5);
        color: #e2e8f0;
    }
    .vw-social-concept .vw-template-desc {
        font-size: 0.72rem; color: #64748b; margin-top: 0.4rem;
        font-style: italic;
    }
    .vw-social-concept .vw-url-import-box {
        background: rgba(20, 20, 40, 0.5);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
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
        color: #64748b;
        font-size: 0.85rem;
        pointer-events: none;
    }
    .vw-social-concept .vw-url-input {
        width: 100%;
        padding: 0.65rem 0.85rem 0.65rem 2.3rem;
        background: rgba(10, 10, 25, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.65rem;
        color: #e2e8f0;
        font-size: 0.88rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .vw-social-concept .vw-url-input:focus { border-color: rgba(139, 92, 246, 0.5); }
    .vw-social-concept .vw-url-input::placeholder { color: #64748b; }
    .vw-social-concept .vw-url-analyze-btn {
        padding: 0.65rem 1.2rem;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        color: white;
        border: none;
        border-radius: 0.65rem;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .vw-social-concept .vw-url-analyze-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35); }
    .vw-social-concept .vw-url-analyze-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .vw-social-concept .vw-url-platforms {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-top: 0.75rem;
        color: #64748b;
        font-size: 0.75rem;
    }
    .vw-social-concept .vw-url-platforms i { font-size: 0.95rem; opacity: 0.7; }
    .vw-social-concept .vw-url-platforms span { margin-left: 0.1rem; }
    .vw-social-concept .vw-upload-dropzone {
        border: 2px dashed rgba(139, 92, 246, 0.3);
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.2s;
        background: rgba(20, 20, 40, 0.5);
        cursor: pointer;
        position: relative;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-upload-dropzone:hover,
    .vw-social-concept .vw-upload-dropzone.dragging {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(139, 92, 246, 0.05);
    }
    .vw-social-concept .vw-dropzone-content { color: #94a3b8; }
    .vw-social-concept .vw-dropzone-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .vw-social-concept .vw-dropzone-content p { font-size: 0.95rem; color: #cbd5e1; margin-bottom: 0.25rem; }
    .vw-social-concept .vw-dropzone-content small { font-size: 0.78rem; color: #64748b; }
    .vw-social-concept .vw-video-preview {
        max-height: 280px;
        width: 100%;
        border-radius: 0.75rem;
        object-fit: contain;
        background: #000;
    }
    .vw-social-concept .vw-remove-video {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.75rem;
        padding: 0.4rem 0.8rem;
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-concept .vw-remove-video:hover { background: rgba(239, 68, 68, 0.25); }
    .vw-social-concept .vw-analyze-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-analyze-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4); }
    .vw-social-concept .vw-analysis-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1.25rem;
        background: rgba(30, 30, 50, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        color: #cbd5e1;
        font-size: 0.9rem;
    }
    .vw-social-concept .vw-progress-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }
    @keyframes vw-spin { to { transform: rotate(360deg); } }
    .vw-social-concept .vw-analysis-error {
        padding: 0.75rem 1rem;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.75rem;
        color: #fca5a5;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-cloned-idea-card {
        background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 40, 0.98) 100%);
        border: 2px solid rgba(20, 184, 166, 0.3);
        border-radius: 1rem;
        padding: 1.25rem;
        position: relative;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-cloned-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        background: rgba(20, 184, 166, 0.15);
        color: #5eead4;
        border: 1px solid rgba(20, 184, 166, 0.3);
        margin-bottom: 0.75rem;
    }
    .vw-social-concept .vw-cloned-prompt-preview {
        background: rgba(15, 15, 30, 0.6);
        border: 1px solid rgba(100, 100, 140, 0.15);
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin: 0.75rem 0;
        font-size: 0.8rem;
        color: #94a3b8;
        line-height: 1.4;
    }
    .vw-social-concept .vw-cloned-prompt-preview strong { color: #cbd5e1; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .vw-social-concept .vw-cloned-prompt-preview p { margin-top: 0.35rem; }
    .vw-social-concept .vw-use-concept-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
        margin-top: 0.5rem;
    }
    .vw-social-concept .vw-use-concept-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(20, 184, 166, 0.4); }

    /* Chaos Controls */
    .vw-social-concept .vw-chaos-controls {
        background: rgba(30, 30, 50, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
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
        font-size: 0.8rem;
        font-weight: 600;
        color: #cbd5e1;
        white-space: nowrap;
        min-width: fit-content;
    }
    .vw-social-concept .vw-chaos-slider {
        flex: 1;
        height: 6px;
        border-radius: 3px;
        appearance: none;
        -webkit-appearance: none;
        background: linear-gradient(90deg, rgba(99,102,241,0.5) 0%, rgba(249,115,22,0.5) 50%, rgba(239,68,68,0.6) 100%);
        cursor: pointer;
        min-width: 120px;
    }
    .vw-social-concept .vw-chaos-slider::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #ef4444 100%);
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .vw-social-concept .vw-chaos-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #ef4444 100%);
        cursor: pointer;
        border: none;
    }
    .vw-social-concept .vw-chaos-badge {
        font-weight: 600;
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        border-radius: 1rem;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .vw-social-concept .vw-chaos-badge.calm { color: #818cf8; background: rgba(99,102,241,0.15); }
    .vw-social-concept .vw-chaos-badge.rising { color: #fbbf24; background: rgba(251,191,36,0.15); }
    .vw-social-concept .vw-chaos-badge.intense { color: #fb923c; background: rgba(249,115,22,0.15); }
    .vw-social-concept .vw-chaos-badge.wild { color: #f87171; background: rgba(248,113,113,0.15); }
    .vw-social-concept .vw-chaos-badge.chaos { color: #ff4444; background: rgba(239,68,68,0.2); text-shadow: 0 0 6px rgba(239,68,68,0.5); }
    .vw-social-concept .vw-chaos-desc-input {
        width: 100%;
        background: rgba(20, 20, 40, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        color: #e2e8f0;
        font-size: 0.82rem;
        outline: none;
        margin-top: 0.6rem;
        transition: border-color 0.2s;
    }
    .vw-social-concept .vw-chaos-desc-input:focus {
        border-color: rgba(139, 92, 246, 0.5);
    }
    .vw-social-concept .vw-chaos-desc-input::placeholder {
        color: #4b5563;
    }

    /* User template additions */
    .vw-social-concept .vw-template-divider {
        font-size: 0.7rem; color: #64748b; margin: 0.6rem 0 0.3rem;
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
        opacity: 0; transition: opacity 0.2s;
    }
    .vw-social-concept .vw-template-pill-wrap:hover .vw-template-delete { opacity: 1; }

    /* Save as Template */
    .vw-save-template-wrap { margin-top: 0.75rem; }
    .vw-save-template-btn {
        font-size: 0.78rem; color: #94a3b8; background: none;
        border: 1px dashed rgba(139, 92, 246, 0.3); border-radius: 0.5rem;
        padding: 0.4rem 0.8rem; cursor: pointer; transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 0.35rem;
    }
    .vw-save-template-btn:hover {
        border-color: rgba(139, 92, 246, 0.6); color: #e2e8f0;
    }
    .vw-save-template-form {
        margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem;
        padding: 0.75rem; background: rgba(15, 15, 30, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 0.5rem;
    }
    .vw-save-template-input {
        background: rgba(0,0,0,0.3); border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 0.35rem; padding: 0.4rem 0.6rem; color: #e2e8f0;
        font-size: 0.8rem; outline: none;
    }
    .vw-save-template-input:focus { border-color: rgba(139, 92, 246, 0.5); }
    .vw-save-template-toggle {
        display: flex; align-items: center; gap: 0.4rem;
        font-size: 0.75rem; color: #94a3b8; cursor: pointer;
    }
    .vw-save-template-confirm {
        align-self: flex-start; padding: 0.35rem 0.7rem;
        background: rgba(139, 92, 246, 0.3); border: 1px solid rgba(139, 92, 246, 0.5);
        border-radius: 0.35rem; color: #e2e8f0; font-size: 0.78rem;
        cursor: pointer; transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 0.3rem;
    }
    .vw-save-template-confirm:hover { background: rgba(139, 92, 246, 0.5); }
</style>

<div class="vw-social-concept" x-data="{ viralTheme: '' }">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert">
                <span style="margin-right: 0.5rem;">&#9888;&#65039;</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-viral-header">
            <div class="vw-viral-icon">&#128293;</div>
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
                    <div class="vw-engine-icon">&#127916;</div>
                    <h4>{{ __('Cinematic Scene') }}</h4>
                    <p>{{ __('AI generates video + voice + sound effects from a single prompt. Perfect for visual gags, animals in situations, dramatic scenes.') }}</p>
                    <span class="vw-engine-badge">{{ __('Auto Audio') }}</span>
                </div>
                <div class="vw-engine-card {{ $videoEngine === 'infinitetalk' ? 'active' : '' }}"
                     wire:click="setVideoEngine('infinitetalk')">
                    <div class="vw-engine-icon">&#128483;&#65039;</div>
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
        <div style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: rgba(30, 30, 50, 0.6); border: 1px solid rgba(139, 92, 246, 0.25); border-radius: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-diagram-project" style="color: #a78bfa; font-size: 0.85rem;"></i>
                <span style="font-size: 0.85rem; font-weight: 600; color: #cbd5e1;">{{ __('Workflow') }}</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @foreach($availableWorkflows as $wf)
                    <button type="button"
                        style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1.5px solid {{ $activeWorkflowId == $wf['id'] ? 'rgba(139, 92, 246, 0.6)' : 'rgba(100, 100, 140, 0.25)' }}; background: {{ $activeWorkflowId == $wf['id'] ? 'rgba(139, 92, 246, 0.2)' : 'rgba(15, 15, 30, 0.6)' }}; color: {{ $activeWorkflowId == $wf['id'] ? '#e2e8f0' : '#94a3b8' }};"
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
                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b; font-style: italic;">
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
                                <div class="vw-idea-desc" style="font-size: 0.8rem;">
                                    @foreach(array_slice($idea['dialogueLines'], 0, 3) as $line)
                                        <div style="margin-bottom: 0.2rem;"><strong>{{ $line['speaker'] ?? '' }}:</strong> "{{ $line['text'] ?? '' }}"</div>
                                    @endforeach
                                    @if(count($idea['dialogueLines']) > 3)
                                        <div style="color: #64748b;">+ {{ count($idea['dialogueLines']) - 3 }} more...</div>
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
                                <i class="fa-solid fa-link vw-url-input-icon"></i>
                                <input type="url"
                                       wire:model.defer="conceptVideoUrl"
                                       class="vw-url-input"
                                       placeholder="{{ __('Paste video URL here...') }}"
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
                            <i class="fa-brands fa-youtube"></i>
                            <i class="fa-brands fa-instagram"></i>
                            <i class="fa-brands fa-tiktok"></i>
                            <i class="fa-brands fa-x-twitter"></i>
                            <i class="fa-brands fa-facebook"></i>
                            <span>{{ __('& 1000+ platforms') }}</span>
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
                            <div style="margin: 0.75rem 0; border-radius: 8px; overflow: hidden; border: 2px solid rgba(139, 92, 246, 0.3);">
                                <img src="{{ $videoAnalysisResult['firstFrameUrl'] }}" alt="{{ __('First frame') }}"
                                     style="width: 100%; max-height: 280px; object-fit: contain; background: #000;">
                                <div style="padding: 0.4rem 0.6rem; background: rgba(139, 92, 246, 0.1); font-size: 0.75rem; color: rgba(255,255,255,0.7);">
                                    <i class="fa-solid fa-image"></i> {{ __('First frame — will be used as base image') }}
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
                                        <a href="#" @click.prevent="expanded = true" style="color: var(--vw-accent); cursor: pointer;">Show more</a>
                                    @endif
                                </p>
                                <p x-show="expanded" x-cloak>{{ $videoAnalysisResult['videoPrompt'] }}
                                    <a href="#" @click.prevent="expanded = false" style="color: var(--vw-accent); cursor: pointer;">Show less</a>
                                </p>
                            </div>
                        @endif
                        <div class="vw-idea-hook">{{ $videoAnalysisResult['viralHook'] ?? '' }}</div>

                        {{-- Visual Analysis (transparency panel) --}}
                        @if(!empty($videoAnalysisResult['_visualAnalysis']))
                            <div class="vw-cloned-prompt-preview" x-data="{ showAnalysis: false }" style="margin-top: 0.5rem;">
                                <a href="#" @click.prevent="showAnalysis = !showAnalysis" style="color: var(--vw-accent); cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem;">
                                    <i class="fa-solid fa-microscope"></i>
                                    <span x-text="showAnalysis ? 'Hide Visual Analysis' : 'Show Visual Analysis (Gemini)'"></span>
                                </a>
                                <div x-show="showAnalysis" x-cloak style="margin-top: 0.5rem; max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-size: 0.75rem; line-height: 1.5; color: rgba(255,255,255,0.7); padding: 0.5rem; background: rgba(0,0,0,0.3); border-radius: 6px;">{{ $videoAnalysisResult['_visualAnalysis'] }}</div>
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
