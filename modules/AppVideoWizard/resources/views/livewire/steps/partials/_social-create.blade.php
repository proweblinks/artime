{{-- Social Content: Simplified Single-Shot Creation Studio --}}
@php
    $shot = $multiShotMode['decomposedScenes'][0]['shots'][0] ?? [];
    $sceneData = $multiShotMode['decomposedScenes'][0] ?? [];
    $imageUrl = $shot['imageUrl'] ?? null;
    $imageStatus = $shot['imageStatus'] ?? 'pending';
    $videoUrl = $shot['videoUrl'] ?? null;
    $videoStatus = $shot['videoStatus'] ?? 'pending';
    $audioUrl = $shot['audioUrl'] ?? null;
    $audioUrl2 = $shot['audioUrl2'] ?? null;
    $audioStatus = $shot['audioStatus'] ?? 'pending';
    $audioSource = $shot['audioSource'] ?? null;
    $isDialogueShot = ($shot['speechType'] ?? '') === 'dialogue' && count($shot['charactersInShot'] ?? []) >= 2;
    $selectedIdea = $concept['socialContent'] ?? ($conceptVariations[$selectedConceptIndex ?? 0] ?? []);
    $charactersInShot = $shot['charactersInShot'] ?? [];
    $faceOrder = $shot['faceOrder'] ?? $charactersInShot;
    $creationDetails = $this->getCreationDetails();
    $isSeedance = ($videoEngine ?? 'seedance') === 'seedance';
@endphp

<style>
    .vw-social-create {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh;
        background: var(--vw-bg-deep);
        z-index: 999999;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .vw-social-create-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.5rem;
        background: var(--vw-bg-surface);
        border-bottom: 1px solid var(--vw-border);
        flex-shrink: 0;
    }
    .vw-social-create-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--vw-text);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .vw-social-create-header .vw-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid var(--vw-border);
        border-radius: var(--vw-radius);
        color: var(--vw-text-secondary);
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-create-header .vw-back-btn:hover {
        background: rgba(0, 0, 0, 0.04);
        color: var(--vw-text);
    }
    .vw-social-create-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    /* Left Panel: Preview */
    .vw-social-preview-panel {
        width: 55%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: var(--vw-bg-elevated);
        overflow-y: auto;
    }
    .vw-social-preview-inner {
        width: 100%;
        max-width: 360px;
    }
    .vw-social-preview-frame {
        width: 100%;
        aspect-ratio: 9/16;
        background: var(--vw-bg-surface);
        border: 2px solid var(--vw-border-accent);
        border-radius: 1rem;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .vw-social-preview-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .vw-social-preview-frame video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .vw-social-preview-placeholder {
        text-align: center;
        color: var(--vw-text-muted);
    }
    .vw-social-preview-placeholder i {
        font-size: 3rem;
        margin-bottom: 0.75rem;
        display: block;
        color: #4b5563;
    }
    /* Right Panel: Workflow */
    .vw-social-workflow-panel {
        width: 45%;
        overflow-y: auto;
        padding: 1.5rem;
        border-left: 1px solid var(--vw-border);
    }
    .vw-social-section {
        background: var(--vw-bg-surface);
        border: 1px solid var(--vw-border);
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }
    .vw-social-section.completed {
        border-color: var(--vw-border-success);
    }
    .vw-social-section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .vw-social-section-num {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        background: var(--vw-primary-soft);
        color: var(--vw-text-secondary);
        border: 1px solid var(--vw-border-accent);
    }
    .vw-social-section.completed .vw-social-section-num {
        background: var(--vw-success-soft);
        color: #16a34a;
        border-color: var(--vw-border-success);
    }
    .vw-social-section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--vw-text);
    }
    .vw-social-section-subtitle {
        font-size: 0.75rem;
        color: var(--vw-text-muted);
    }
    .vw-social-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
        color: white;
        border: none;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
    }
    .vw-social-action-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(var(--vw-primary-rgb), 0.3);
    }
    .vw-social-action-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .vw-social-action-btn.success {
        background: var(--vw-success);
    }
    .vw-social-action-btn.orange {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    }
    .vw-social-action-btn.upscale {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    }
    .vw-social-action-btn.upscale:hover:not(:disabled) {
        box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
    }
    .vw-social-btn-row {
        display: flex;
        gap: 0.5rem;
    }
    .vw-social-btn-row .vw-social-action-btn {
        flex: 1;
        width: auto;
    }
    .vw-social-upscaled-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        flex: 1;
        padding: 0.6rem 1rem;
        background: rgba(6, 182, 212, 0.15);
        border: 1px solid rgba(6, 182, 212, 0.3);
        border-radius: 0.6rem;
        color: #06b6d4;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .vw-social-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .vw-social-status-badge.pending { background: var(--vw-border); color: var(--vw-text-secondary); }
    .vw-social-status-badge.generating { background: var(--vw-primary-soft); color: var(--vw-text-secondary); animation: vw-pulse-badge 1.5s infinite; }
    .vw-social-status-badge.ready { background: var(--vw-success-soft); color: #16a34a; }
    .vw-social-status-badge.processing { background: rgba(249,115,22,0.2); color: #fb923c; animation: vw-pulse-badge 1.5s infinite; }
    .vw-social-status-badge.error { background: rgba(239,68,68,0.2); color: #dc2626; }
    @keyframes vw-pulse-badge { 0%,100%{opacity:0.6} 50%{opacity:1} }
    .vw-social-swap-btn {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.5rem; border-radius: 0.3rem;
        background: var(--vw-primary-soft); color: var(--vw-text-secondary);
        border: 1px solid var(--vw-border-accent);
        font-size: 0.7rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-swap-btn:hover { background: var(--vw-border-accent); color: var(--vw-text); }

    .vw-mode-btn { padding: 0.35rem 0.75rem; border-radius: 0.375rem; border: 1px solid var(--vw-border); background: transparent; color: var(--vw-text-secondary); cursor: pointer; transition: all 0.2s; font-size: 0.75rem; }
    .vw-mode-btn.active { background: var(--vw-primary-soft); border-color: rgba(var(--vw-primary-rgb), 0.4); color: var(--vw-text-secondary); }
    .vw-mode-btn:hover { border-color: var(--vw-border-accent); color: var(--vw-text-secondary); }
    .vw-mode-hint { font-size: 0.65rem; color: var(--vw-text-muted); margin-top: 0.25rem; }

    .vw-social-progress-bar {
        margin-top: 0.75rem;
        padding: 0.75rem;
        background: rgba(249,115,22,0.08);
        border: 1px solid rgba(249,115,22,0.2);
        border-radius: 0.5rem;
    }
    .vw-social-progress-text {
        font-size: 0.8rem;
        color: #fb923c;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .vw-social-progress-track {
        height: 3px;
        background: rgba(249,115,22,0.15);
        border-radius: 2px;
        overflow: hidden;
    }
    .vw-social-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #f97316, #fb923c);
        border-radius: 2px;
        animation: vw-progress-indeterminate 2s ease-in-out infinite;
    }
    @keyframes vw-progress-indeterminate {
        0% { width: 0%; margin-left: 0%; }
        50% { width: 40%; margin-left: 30%; }
        100% { width: 0%; margin-left: 100%; }
    }
    .vw-social-progress-hint {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
        margin-top: 0.4rem;
    }

    .vw-social-preview-generating {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .vw-social-preview-generating img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .vw-social-generating-overlay {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        color: #fb923c;
        font-weight: 700;
        font-size: 1rem;
    }
    .vw-social-upscaling-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #06b6d4;
        font-weight: 700;
        font-size: 1rem;
        background: var(--vw-bg-elevated);
        border-radius: 0.5rem;
    }

    .vw-social-audio-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid var(--vw-border);
    }
    .vw-social-audio-tab {
        flex: 1;
        padding: 0.5rem;
        text-align: center;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        background: var(--vw-bg-elevated);
        color: var(--vw-text-secondary);
        border: none;
        transition: all 0.2s;
    }
    .vw-social-audio-tab.active {
        background: var(--vw-primary-soft);
        color: var(--vw-text-secondary);
    }
    .vw-social-model-select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        color: var(--vw-text);
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
    }
    .vw-social-file-upload {
        width: 100%;
        padding: 0.5rem;
        background: var(--vw-bg-surface);
        border: 1px dashed var(--vw-border);
        border-radius: 0.5rem;
        color: var(--vw-text-secondary);
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
    }
    .vw-social-audio-player {
        width: 100%;
        height: 36px;
        margin-top: 0.5rem;
        border-radius: 0.5rem;
    }
    .vw-social-next-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
        color: white;
        border: none;
        border-radius: 0.6rem;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-next-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 20px rgba(var(--vw-primary-rgb), 0.4);
    }
    .vw-social-idea-summary {
        background: rgba(var(--vw-primary-rgb), 0.08);
        border: 1px solid var(--vw-primary-soft);
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-bottom: 1rem;
        font-size: 0.8rem;
        color: var(--vw-text-secondary);
    }
    .vw-social-idea-summary strong { color: var(--vw-text-secondary); }

    /* Creation Details Debug Panel */
    .vw-social-debug-panel { margin-top: 1rem; border-top: 1px solid var(--vw-border); padding-top: 0.75rem; }
    .vw-social-debug-toggle { width: 100%; display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: rgba(var(--vw-primary-rgb), 0.08); border: 1px solid var(--vw-primary-soft); border-radius: 0.5rem; color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .vw-social-debug-toggle:hover { background: var(--vw-primary-soft); }
    .vw-social-debug-toggle span { flex: 1; text-align: left; }
    .vw-debug-section { margin-top: 0.5rem; background: var(--vw-bg-elevated); border: 1px solid var(--vw-border); border-radius: 0.5rem; overflow: hidden; }
    .vw-debug-section summary { padding: 0.5rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: var(--vw-text-secondary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
    .vw-debug-section summary:hover { color: var(--vw-text); }
    .vw-debug-section summary::-webkit-details-marker { display: none; }
    .vw-debug-section summary::after { content: '+'; margin-left: auto; font-size: 0.9rem; color: var(--vw-text-muted); }
    .vw-debug-section[open] summary::after { content: '\2212'; }
    .vw-debug-content { padding: 0.5rem 0.75rem; border-top: 1px solid var(--vw-border); }
    .vw-debug-field { display: flex; gap: 0.5rem; margin-bottom: 0.35rem; font-size: 0.7rem; }
    .vw-debug-label { color: var(--vw-text-muted); min-width: 80px; flex-shrink: 0; }
    .vw-debug-value { color: var(--vw-text); font-family: 'JetBrains Mono', monospace; }
    .vw-debug-prompt { color: var(--vw-text-secondary); font-size: 0.65rem; line-height: 1.5; background: var(--vw-bg-elevated); padding: 0.5rem; border-radius: 0.375rem; margin-top: 0.25rem; font-family: 'JetBrains Mono', monospace; word-break: break-word; max-height: 120px; overflow-y: auto; }
    .vw-debug-speaker { padding: 0.35rem 0.5rem; background: var(--vw-bg-elevated); border-radius: 0.375rem; margin-bottom: 0.35rem; }
    .vw-debug-speaker-name { font-size: 0.7rem; font-weight: 700; color: #fbbf24; margin-bottom: 0.25rem; }
    .vw-debug-badge { font-size: 0.65rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; display: inline-block; margin: 0.25rem 0; }
    .vw-debug-badge.swap { background: rgba(249,115,22,0.15); color: #fb923c; }

    .vw-social-prompt-editor { margin-bottom: 0.75rem; }
    .vw-social-prompt-editor label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.35rem; }
    .vw-social-prompt-editor textarea {
        width: 100%; min-height: 100px; padding: 0.6rem 0.75rem; background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border); border-radius: 0.5rem; color: var(--vw-text);
        font-size: 0.8rem; line-height: 1.5; resize: vertical; outline: none; transition: border-color 0.2s;
    }
    .vw-social-prompt-editor textarea:focus { border-color: var(--vw-border-focus); }
    .vw-social-prompt-editor small { display: block; font-size: 0.7rem; color: var(--vw-text-muted); margin-top: 0.25rem; }
    .vw-social-duration-select, .vw-social-resolution-select, .vw-social-quality-select {
        width: 100%; padding: 0.5rem 0.75rem; background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border); border-radius: 0.5rem; color: var(--vw-text);
        font-size: 0.8rem; margin-bottom: 0.75rem;
    }
    .vw-seedance-options-row {
        display: flex; gap: 0.75rem; margin-bottom: 0.75rem;
    }
    .vw-seedance-options-row > div { flex: 1; }

    /* Background Music Toggle */
    .vw-music-toggle-row {
        display: flex; align-items: center; gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: var(--vw-bg-surface);
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
    }
    .vw-music-toggle-label {
        display: flex; align-items: center; gap: 0.5rem; cursor: pointer;
    }
    .vw-music-toggle-checkbox {
        display: none;
    }
    .vw-music-toggle-switch {
        position: relative; width: 34px; height: 18px;
        background: var(--vw-border);
        border-radius: 9px; transition: background 0.2s;
        flex-shrink: 0;
    }
    .vw-music-toggle-switch::after {
        content: ''; position: absolute; top: 2px; left: 2px;
        width: 14px; height: 14px; border-radius: 50%;
        background: #d4d4d8; transition: all 0.2s;
    }
    .vw-music-toggle-checkbox:checked + .vw-music-toggle-switch {
        background: rgba(var(--vw-primary-rgb), 0.5);
    }
    .vw-music-toggle-checkbox:checked + .vw-music-toggle-switch::after {
        left: 18px; background: var(--vw-text);
    }
    .vw-music-toggle-text {
        font-size: 0.8rem; font-weight: 600; color: var(--vw-text);
    }
    .vw-music-toggle-text i { color: var(--vw-primary); margin-right: 0.15rem; }
    .vw-music-toggle-hint {
        font-size: 0.7rem; color: var(--vw-text-muted); margin-left: auto;
    }

    /* Chaos Mode Toggle */
    .vw-chaos-mode-row {
        display: flex; align-items: center; gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .vw-chaos-toggle {
        display: flex; align-items: center; gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        background: rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 2rem;
        color: var(--vw-text-secondary); font-size: 0.8rem; font-weight: 700;
        cursor: pointer; transition: all 0.2s;
        letter-spacing: 0.05em;
    }
    .vw-chaos-toggle:hover {
        border-color: rgba(239, 68, 68, 0.5); color: #dc2626;
    }
    .vw-chaos-toggle.active {
        border-color: rgba(239, 68, 68, 0.7);
        background: rgba(239, 68, 68, 0.15);
        color: var(--vw-text);
        box-shadow: 0 0 16px rgba(239, 68, 68, 0.25), 0 0 4px rgba(239, 68, 68, 0.1);
    }
    .vw-chaos-toggle.active i { color: #ef4444; }
    .vw-chaos-toggle i { font-size: 0.8rem; transition: color 0.2s; }
    .vw-chaos-hint {
        font-size: 0.7rem; color: #f97316;
    }
    .vw-chaos-hint i { margin-right: 0.2rem; }
    .vw-camera-section.chaos-disabled {
        opacity: 0.35; pointer-events: none;
    }

    /* Camera Movement Picker */
    .vw-camera-section { margin-bottom: 0.75rem; }
    .vw-camera-section > label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.4rem; }
    .vw-camera-pills { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .vw-camera-pill {
        display: flex; align-items: center; gap: 0.35rem;
        padding: 0.4rem 0.7rem;
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border);
        border-radius: 2rem;
        color: var(--vw-text-secondary); font-size: 0.75rem;
        cursor: pointer; transition: all 0.15s;
    }
    .vw-camera-pill:hover { border-color: rgba(var(--vw-primary-rgb), 0.4); color: var(--vw-text); }
    .vw-camera-pill.active {
        border-color: rgba(var(--vw-primary-rgb), 0.6);
        background: var(--vw-primary-soft);
        color: var(--vw-text);
        box-shadow: 0 0 12px var(--vw-primary-soft);
    }
    .vw-camera-pill i { font-size: 0.7rem; }
    .vw-camera-intensity { margin-top: 0.5rem; }
    .vw-camera-intensity > label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--vw-text-muted); margin-bottom: 0.3rem; }
    .vw-intensity-pills { display: flex; gap: 0.35rem; }
    .vw-intensity-pill {
        display: flex; align-items: center; gap: 0.3rem;
        padding: 0.3rem 0.6rem;
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border);
        border-radius: 2rem;
        color: var(--vw-text-secondary); font-size: 0.7rem;
        cursor: pointer; transition: all 0.15s;
    }
    .vw-intensity-pill:hover { border-color: rgba(var(--vw-primary-rgb), 0.4); color: var(--vw-text); }
    .vw-intensity-pill.active {
        border-color: rgba(var(--vw-primary-rgb), 0.6);
        background: var(--vw-primary-soft);
        color: var(--vw-text);
    }
    .vw-social-engine-badge {
        display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.5rem;
        border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600;
        background: rgba(var(--vw-primary-rgb), 0.12); color: var(--vw-text-secondary); border: 1px solid var(--vw-primary-soft);
    }

    /* Video Extend — player overlay */
    .vw-extend-player-wrap { position: relative; width: 100%; height: 100%; }
    .vw-extend-player-wrap video { width: 100%; height: 100%; object-fit: contain; }
    .vw-extract-frame-btn {
        position: absolute; bottom: 4.5rem; left: 50%; transform: translateX(-50%);
        background: rgba(0,0,0,0.6); color: #fff; border: 1px solid var(--vw-border-accent);
        padding: 0.4rem 1rem; border-radius: 2rem; font-size: 0.82rem;
        cursor: pointer; backdrop-filter: blur(8px); transition: all 0.2s;
        white-space: nowrap; z-index: 5;
    }
    .vw-extract-frame-btn:hover { background: rgba(249,115,22,0.8); border-color: #f97316; }

    /* Video Extend — Timeline */
    .vw-timeline { margin-top: 0.5rem; width: 100%; }
    .vw-timeline-bar {
        display: flex; height: 2rem; border-radius: 0.5rem; overflow: hidden;
        border: 1px solid var(--vw-border); background: var(--vw-bg-elevated);
    }
    .vw-timeline-segment {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 0.5rem; cursor: pointer; transition: filter 0.15s;
        font-size: 0.7rem; color: var(--vw-text); min-width: 0;
    }
    .vw-timeline-segment:hover { filter: brightness(1.3); }
    .vw-timeline-segment.original { background: rgba(var(--vw-primary-rgb), 0.4); }
    .vw-timeline-segment.extension { background: rgba(249,115,22,0.4); }
    .vw-timeline-segment + .vw-timeline-segment { border-left: 1px solid var(--vw-border); }
    .vw-timeline-segment.selected { outline: 2px solid #f97316; outline-offset: -2px; filter: brightness(1.4); }
    .vw-seg-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: flex; align-items: center; gap: 0.2rem; }
    .vw-seg-fire { font-size: 0.6rem; opacity: 0.7; }
    .vw-seg-duration { flex-shrink: 0; margin-left: 0.25rem; opacity: 0.7; }
    .vw-timeline-segment[data-tooltip]:not([data-tooltip=""]) { position: relative; }
    .vw-timeline-segment[data-tooltip]:not([data-tooltip=""]):hover::after {
        content: attr(data-tooltip);
        position: absolute; top: 100%; left: 0; z-index: 10;
        background: var(--vw-bg-surface); border: 1px solid var(--vw-border);
        border-radius: 0.4rem; padding: 0.4rem 0.6rem; margin-top: 0.3rem;
        font-size: 0.7rem; color: var(--vw-text-secondary); pointer-events: none;
        white-space: normal; width: max-content; max-width: 300px;
    }
    .vw-timeline-total {
        font-size: 0.72rem; color: var(--vw-text-secondary); margin-top: 0.25rem; text-align: right;
        display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;
    }

    /* Video Extend — Extend Panel */
    .vw-extend-panel {
        background: var(--vw-bg-surface); border: 1px solid var(--vw-border);
        border-radius: 0.75rem; padding: 1rem; margin-top: 0.75rem; width: 100%;
    }
    .vw-extend-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 0.75rem; font-size: 0.85rem; color: #f97316; font-weight: 600;
    }
    .vw-extend-body {
        display: flex; gap: 0.75rem; margin-bottom: 0.6rem; align-items: flex-start;
    }
    .vw-extend-frame-col {
        flex: 0 0 auto;
    }
    .vw-extend-frame-preview {
        max-height: 120px; border-radius: 0.5rem; display: block;
    }
    .vw-extend-controls-col {
        flex: 1; display: flex; flex-direction: column; justify-content: flex-start; gap: 0.3rem;
    }
    .vw-extend-controls-col .vw-extend-duration-row { margin-bottom: 0; }
    .vw-extend-controls-col .vw-extend-intensity-row { margin-bottom: 0; padding: 0.4rem 0.5rem; }
    @media (max-width: 500px) {
        .vw-extend-body { flex-direction: column; }
    }

    /* Segment Edit Panel — compact layout */
    .vw-seg-edit {
        background: var(--vw-bg-surface); border: 1px solid rgba(0, 0, 0, 0.04);
        border-radius: 0.6rem; padding: 0.6rem; margin-top: 0.5rem; width: 100%;
    }
    .vw-seg-edit-top {
        display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;
    }
    .vw-seg-edit-title {
        font-size: 0.78rem; font-weight: 600; color: #f97316;
        white-space: nowrap; display: flex; align-items: center; gap: 0.3rem;
    }
    .vw-seg-edit-dur { display: flex; gap: 0.3rem; margin-left: auto; }
    .vw-seg-edit-body {
        display: flex; gap: 0.6rem; margin-bottom: 0.5rem;
    }
    .vw-seg-edit-thumb {
        flex-shrink: 0; width: 100px;
    }
    .vw-seg-edit-thumb img {
        width: 100%; border-radius: 0.4rem; object-fit: cover;
        border: 1px solid var(--vw-border);
    }
    .vw-seg-edit-prompt-col {
        flex: 1; min-width: 0; display: flex; flex-direction: column;
    }
    .vw-seg-edit-prompt-col .vw-extend-prompt {
        margin-bottom: 0; flex: 1; min-height: 80px; font-size: 0.75rem;
    }
    .vw-seg-edit-warning {
        font-size: 0.7rem; color: #f97316; margin-bottom: 0.3rem;
        display: flex; align-items: center; gap: 0.3rem;
    }
    .vw-seg-edit-actions {
        display: flex; gap: 0.4rem; justify-content: flex-end;
    }
    .vw-seg-edit-btn {
        padding: 0.35rem 0.75rem; border-radius: 0.4rem; font-size: 0.75rem;
        font-weight: 600; cursor: pointer; border: none; transition: all 0.15s;
        display: inline-flex; align-items: center; gap: 0.3rem;
    }
    .vw-seg-edit-btn.auto {
        background: rgba(var(--vw-primary-rgb), 0.08); color: var(--vw-text);
        border: 1px solid var(--vw-border-accent);
    }
    .vw-seg-edit-btn.auto:hover { background: rgba(var(--vw-primary-rgb), 0.4); }
    .vw-seg-edit-btn.regen {
        background: rgba(249,115,22,0.3); color: #c2410c;
        border: 1px solid rgba(249,115,22,0.4);
    }
    .vw-seg-edit-btn.regen:hover { background: rgba(249,115,22,0.5); color: #fff; }
    .vw-extend-duration-row { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem; font-size: 0.8rem; color: var(--vw-text-secondary); }
    .vw-dur-btn {
        padding: 0.3rem 0.8rem; border-radius: 1rem; border: 1px solid var(--vw-border);
        background: transparent; color: var(--vw-text-secondary); cursor: pointer; font-size: 0.78rem; transition: all 0.15s;
    }
    .vw-dur-btn.active { background: rgba(249,115,22,0.5); border-color: #f97316; color: #fff; }
    .vw-dur-btn:hover { border-color: rgba(249,115,22,0.5); }
    .vw-extend-prompt {
        width: 100%; background: var(--vw-bg-elevated); border: 1px solid var(--vw-border);
        color: var(--vw-text); border-radius: 0.5rem; padding: 0.5rem; margin-bottom: 0.75rem;
        resize: vertical; font-size: 0.8rem; font-family: inherit;
    }
    .vw-extend-direction {
        width: 100%; background: rgba(249,115,22,0.08); border: 1px solid rgba(249,115,22,0.3);
        color: var(--vw-text); border-radius: 0.5rem; padding: 0.5rem 0.6rem; margin-bottom: 0.5rem;
        font-size: 0.78rem; font-family: inherit; outline: none; transition: border-color 0.15s;
    }
    .vw-extend-direction:focus { border-color: #f97316; background: rgba(249,115,22,0.12); }
    .vw-extend-direction::placeholder { color: rgba(249,115,22,0.45); font-style: italic; }
    /* Intensity/Chaos slider */
    .vw-extend-intensity-row {
        margin-bottom: 0.75rem; padding: 0.6rem 0.7rem;
        background: var(--vw-bg-elevated); border-radius: 0.5rem;
        border: 1px solid var(--vw-border);
    }
    .vw-intensity-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 0.4rem; font-size: 0.8rem; color: var(--vw-text-secondary);
    }
    .vw-intensity-label {
        font-weight: 600; font-size: 0.75rem; padding: 0.1rem 0.5rem;
        border-radius: 1rem; transition: all 0.2s;
    }
    .vw-intensity-label.calm { color: #818cf8; background: rgba(99,102,241,0.15); }
    .vw-intensity-label.rising { color: #fbbf24; background: rgba(251,191,36,0.15); }
    .vw-intensity-label.intense { color: #fb923c; background: rgba(249,115,22,0.15); }
    .vw-intensity-label.wild { color: #f87171; background: rgba(248,113,113,0.15); }
    .vw-intensity-label.chaos { color: #ff4444; background: rgba(239,68,68,0.2); text-shadow: 0 0 6px rgba(239,68,68,0.5); }
    .vw-intensity-slider-wrap { position: relative; }
    .vw-intensity-slider {
        -webkit-appearance: none; appearance: none;
        width: 100%; height: 6px; border-radius: 3px; outline: none;
        cursor: pointer;
    }
    .vw-intensity-slider::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 16px; height: 16px; border-radius: 50%;
        background: #fff; border: 2px solid #f97316;
        cursor: pointer; box-shadow: 0 0 4px rgba(249,115,22,0.4);
    }
    .vw-intensity-slider::-moz-range-thumb {
        width: 16px; height: 16px; border-radius: 50%;
        background: #fff; border: 2px solid #f97316;
        cursor: pointer;
    }
    .vw-intensity-ticks {
        display: flex; justify-content: space-between;
        font-size: 0.6rem; color: var(--vw-text-muted); margin-top: 0.15rem; padding: 0 2px;
    }
    .vw-intensity-regen-btn {
        margin-top: 0.5rem; font-size: 0.72rem; color: var(--vw-text-secondary);
        background: none; border: 1px solid var(--vw-border);
        border-radius: 1rem; padding: 0.25rem 0.7rem; cursor: pointer;
        transition: all 0.15s;
    }
    .vw-intensity-regen-btn:hover { color: #f97316; border-color: rgba(249,115,22,0.4); }

    .vw-extend-undo-btn, .vw-extend-redo-btn {
        font-size: 0.72rem; color: var(--vw-text-secondary); background: none; border: none; cursor: pointer;
        padding: 0; margin-left: 0.5rem; transition: color 0.15s;
    }
    .vw-extend-undo-btn:hover { color: #dc2626; }
    .vw-extend-redo-btn:hover { color: #86efac; }
    .vw-extend-cancel-btn {
        background: none; border: none; color: var(--vw-text-secondary); font-size: 1.2rem; cursor: pointer;
        line-height: 1; padding: 0;
    }
    .vw-extend-cancel-btn:hover { color: #f87171; }
    .vw-social-action-btn.orange {
        background: linear-gradient(135deg, #f97316, #ea580c);
        border: 1px solid rgba(249,115,22,0.3);
    }
    .vw-social-action-btn.orange:hover { filter: brightness(1.15); }

    /* Inline Asset Gallery */
    .vw-asset-gallery {
        width: 100%;
        max-width: 380px;
        margin-top: 0.6rem;
    }
    .vw-asset-gallery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.35rem;
        padding: 0 0.15rem;
    }
    .vw-asset-gallery-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--vw-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .vw-asset-gallery-label i { font-size: 0.6rem; color: rgba(var(--vw-primary-rgb), 0.6); }
    .vw-asset-gallery-count {
        background: var(--vw-primary-soft);
        color: var(--vw-text);
        font-size: 0.6rem;
        padding: 0.05rem 0.35rem;
        border-radius: 1rem;
        font-weight: 700;
    }
    .vw-asset-gallery-nav { display: flex; gap: 0.2rem; }
    .vw-gallery-arrow {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.04);
        color: var(--vw-text-muted);
        width: 22px; height: 22px;
        border-radius: 0.3rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.55rem;
        transition: all 0.15s;
    }
    .vw-gallery-arrow:hover {
        background: var(--vw-primary-soft);
        color: var(--vw-text);
        border-color: var(--vw-border-accent);
    }
    .vw-asset-gallery-strip {
        display: flex;
        gap: 0.45rem;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 0.2rem 0.1rem 0.3rem;
        scrollbar-width: none;
    }
    .vw-asset-gallery-strip::-webkit-scrollbar { display: none; }
    .vw-asset-thumb {
        flex-shrink: 0;
        width: 68px;
        cursor: pointer;
        background: var(--vw-bg-elevated);
        border: 2px solid var(--vw-border);
        border-radius: 0.5rem;
        overflow: hidden;
        transition: all 0.2s;
        position: relative;
        padding: 0;
    }
    .vw-asset-thumb:hover {
        border-color: rgba(var(--vw-primary-rgb), 0.4);
        background: rgba(var(--vw-primary-rgb), 0.05);
        transform: translateY(-1px);
    }
    .vw-asset-thumb.active {
        border-color: rgba(var(--vw-primary-rgb), 0.7);
        background: rgba(var(--vw-primary-rgb), 0.08);
        box-shadow: 0 0 12px var(--vw-primary-soft);
        cursor: default;
    }
    .vw-asset-thumb-img {
        width: 100%;
        height: 80px;
        overflow: hidden;
        position: relative;
        background: var(--vw-bg-elevated);
    }
    .vw-asset-thumb-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .vw-asset-thumb-video-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 2;
        width: 22px; height: 22px;
        background: rgba(0,0,0,0.6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.5rem;
    }
    .vw-asset-thumb-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        padding: 0.2rem 0;
        font-size: 0.55rem;
        font-weight: 600;
        color: var(--vw-text-muted);
        background: var(--vw-bg-elevated);
    }
    .vw-asset-thumb-badge i { font-size: 0.5rem; }
    .vw-asset-thumb-badge.image i { color: #34d399; }
    .vw-asset-thumb-badge.video i { color: #06b6d4; }
    .vw-asset-thumb-active-dot {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 7px; height: 7px;
        background: #a78bfa;
        border-radius: 50%;
        box-shadow: 0 0 6px rgba(var(--vw-primary-rgb), 0.6);
    }

    @media (max-width: 768px) {
        .vw-social-create-body { flex-direction: column; }
        .vw-social-preview-panel { width: 100%; height: auto; max-height: 50vh; }
        .vw-social-workflow-panel { width: 100%; border-left: none; border-top: 1px solid var(--vw-border); }
        .vw-asset-gallery { max-width: 100%; }
    }
</style>

<div class="vw-social-create"
     x-data="{
        audioTab: '{{ ($audioSource === "music_upload") ? "music" : "voice" }}',
        pollingInterval: null,
        isPolling: false,
        pollCount: 0,
        maxPolls: 600,
        POLL_INTERVAL: 5000,
        initPolling() {
            const status = '{{ $videoStatus }}';
            if (status === 'generating' || status === 'processing') {
                this.startPolling();
            }
            Livewire.on('video-generation-started', () => this.startPolling());
            Livewire.on('video-generation-complete', () => this.stopPolling());
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !this.isPolling) {
                    const el = document.querySelector('.vw-social-status-badge.processing, .vw-social-status-badge.generating');
                    if (el) this.startPolling();
                }
            });
        },
        startPolling() {
            if (this.isPolling) return;
            this.isPolling = true;
            this.pollCount = 0;
            this.pollingInterval = setInterval(() => {
                if (this.pollCount >= this.maxPolls) { this.stopPolling(); return; }
                this.pollCount++;
                if (this.$wire) {
                    this.$wire.pollVideoJobs().then((r) => {
                        if (r && r.pendingJobs === 0) this.stopPolling();
                    }).catch(() => {});
                }
            }, this.POLL_INTERVAL);
        },
        stopPolling() {
            if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
            this.isPolling = false;
        },
        manualCheck() {
            if (this.$wire) {
                this.$wire.pollVideoJobs().then((r) => {
                    if (r && r.pendingJobs > 0 && !this.isPolling) this.startPolling();
                });
            }
        }
     }"
     x-init="initPolling()">
    {{-- Header Bar --}}
    <div class="vw-social-create-header">
        <h2>
            <span>&#128293;</span>
            {{ $selectedIdea['title'] ?? __('Create Viral Content') }}
        </h2>
        <button class="vw-back-btn" wire:click="previousStep">
            <i class="fa-solid fa-arrow-left"></i> {{ __('Back to Ideas') }}
        </button>
    </div>

    {{-- Body: Two Panels --}}
    <div class="vw-social-create-body">
        {{-- Left: Preview --}}
        <div class="vw-social-preview-panel">
            <div class="vw-social-preview-inner">
                <div class="vw-social-preview-frame">
                    @if($videoUrl && $videoStatus === 'ready')
                        <div x-data="{ currentTime: 0, videoDuration: 0, paused: true }" class="vw-extend-player-wrap">
                            <video wire:ignore src="{{ $videoUrl }}" controls loop playsinline
                                   @timeupdate="currentTime = $event.target.currentTime; videoDuration = $event.target.duration"
                                   @pause="paused = true" @play="paused = false"
                                   x-ref="mainPlayer"></video>

                            {{-- Extract Frame button — appears when paused --}}
                            @if($isSeedance)
                            <template x-if="paused && currentTime > 0 && !{{ json_encode((bool) $extendMode) }}">
                                <button class="vw-extract-frame-btn"
                                        @click="$wire.initVideoExtend(0, 0, parseFloat(currentTime.toFixed(2)))"
                                        wire:loading.attr="disabled"
                                        wire:target="initVideoExtend">
                                    <span wire:loading.remove wire:target="initVideoExtend">
                                        <i class="fa-solid fa-camera"></i>
                                        Extract Frame at <span x-text="currentTime.toFixed(1)"></span>s
                                    </span>
                                    <span wire:loading wire:target="initVideoExtend">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        Extracting frame & generating prompt...
                                    </span>
                                </button>
                            </template>
                            @endif
                        </div>
                    @elseif(in_array($videoStatus, ['generating', 'processing']))
                        <div class="vw-social-preview-generating">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="Base image" style="opacity: 0.4;" />
                            @endif
                            <div class="vw-social-generating-overlay">
                                <i class="fa-solid fa-wand-magic-sparkles fa-2x" style="animation: vw-pulse-badge 1.5s infinite;"></i>
                                <div>{{ __('Animating...') }}</div>
                            </div>
                        </div>
                    @elseif($imageUrl && $imageStatus === 'ready')
                        <div style="position: relative;">
                            <img src="{{ $imageUrl }}" alt="Generated image"
                                 wire:loading.attr="style"
                                 wire:target="upscaleShotImage"
                                 wire:loading.value="opacity: 0.35; filter: blur(2px);" />
                            <div wire:loading wire:target="upscaleShotImage" class="vw-social-upscaling-overlay">
                                <i class="fa-solid fa-up-right-and-down-left-from-center fa-2x" style="animation: vw-pulse-badge 1.5s infinite;"></i>
                                <div>{{ __('Upscaling...') }}</div>
                                <div style="font-size: 0.7rem; opacity: 0.7;">{{ __('Enhancing resolution while preserving faces') }}</div>
                            </div>
                        </div>
                    @else
                        <div class="vw-social-preview-placeholder">
                            <i class="fa-solid fa-image"></i>
                            <div>{{ __('Generate an image to preview') }}</div>
                            <div style="font-size: 0.75rem; margin-top: 0.25rem;">9:16 Vertical</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Inline Asset Gallery — all generated images & videos --}}
            @php
                $assetHistory = $shot['assetHistory'] ?? [];
            @endphp
            @if(count($assetHistory) > 1)
            <div class="vw-asset-gallery" x-data="{ scrollEl: null }" x-init="scrollEl = $refs.galleryScroll">
                <div class="vw-asset-gallery-header">
                    <span class="vw-asset-gallery-label">
                        <i class="fa-solid fa-layer-group"></i>
                        {{ __('Versions') }}
                        <span class="vw-asset-gallery-count">{{ count($assetHistory) }}</span>
                    </span>
                    <div class="vw-asset-gallery-nav">
                        <button type="button" @click="scrollEl.scrollBy({ left: -160, behavior: 'smooth' })" class="vw-gallery-arrow"><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" @click="scrollEl.scrollBy({ left: 160, behavior: 'smooth' })" class="vw-gallery-arrow"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="vw-asset-gallery-strip" x-ref="galleryScroll">
                    @foreach(array_reverse($assetHistory) as $hIdx => $histEntry)
                        @php
                            $isActive = $histEntry['isActive'] ?? false;
                            $hType = $histEntry['type'] ?? 'image';
                            $hAction = $histEntry['action'] ?? 'generated';
                            $hUrl = $histEntry['url'] ?? '';
                            $hId = $histEntry['id'] ?? '';
                            $hTimestamp = $histEntry['timestamp'] ?? null;
                            $hTimeAgo = $hTimestamp ? \Carbon\Carbon::parse($hTimestamp)->diffForHumans(null, true, true) : '';
                            $hModel = $histEntry['model'] ?? '';
                            $hSegments = $histEntry['segments'] ?? [];
                            $isExtendedVideo = ($hType === 'video' && count($hSegments) > 1);
                            $actionIcon = match($hAction) {
                                'generated', 'regenerated' => 'fa-wand-magic-sparkles',
                                'edited' => 'fa-pen-to-square',
                                'reimagined' => 'fa-palette',
                                'uploaded' => 'fa-cloud-arrow-up',
                                'animated' => 'fa-film',
                                'stock' => 'fa-image',
                                'restored' => 'fa-rotate-left',
                                default => 'fa-circle',
                            };
                            $actionLabel = match(true) {
                                $isExtendedVideo => count($hSegments) . 'x',
                                $hAction === 'generated' => __('Gen'),
                                $hAction === 'regenerated' => __('Regen'),
                                $hAction === 'edited' => __('Edit'),
                                $hAction === 'reimagined' => __('Style'),
                                $hAction === 'uploaded' => __('Upload'),
                                $hAction === 'animated' => __('Video'),
                                $hAction === 'stock' => __('Stock'),
                                $hAction === 'restored' => __('Restored'),
                                default => ucfirst($hAction),
                            };
                        @endphp
                        <button type="button"
                                class="vw-asset-thumb {{ $isActive ? 'active' : '' }} {{ $hType }}"
                                @if(!$isActive)
                                    wire:click="activateAssetFromGallery('{{ $hId }}')"
                                @endif
                                title="{{ ucfirst($hAction) }} — {{ $hTimeAgo }}">
                            <div class="vw-asset-thumb-img">
                                @if($hType === 'video')
                                    <div class="vw-asset-thumb-video-icon"><i class="fa-solid fa-play"></i></div>
                                    <div style="width:100%;height:100%;background:var(--vw-bg-elevated);"></div>
                                @else
                                    <img src="{{ $hUrl }}" alt="{{ $actionLabel }}" loading="lazy" />
                                @endif
                            </div>
                            <div class="vw-asset-thumb-badge {{ $hType }}">
                                <i class="fa-solid {{ $actionIcon }}"></i>
                                <span>{{ $actionLabel }}</span>
                            </div>
                            @if($isActive)
                                <div class="vw-asset-thumb-active-dot"></div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Visual Timeline Bar — below the video, full panel width --}}
            @if(!empty($shot['segments']) && count($shot['segments']) > 0 && ($videoStatus === 'ready' || ($shot['videoJobType'] ?? '') === 'segment_regen'))
            @php $selectedSegIdx = $segmentEditMode['segmentIndex'] ?? -1; @endphp
            <div class="vw-timeline">
                <div class="vw-timeline-bar">
                    @php
                        $totalDuration = array_sum(array_column($shot['segments'], 'duration'));
                        $cumulative = 0;
                    @endphp
                    @foreach($shot['segments'] as $segIdx => $segment)
                        @php
                            $widthPct = ($segment['duration'] / max($totalDuration, 0.1)) * 100;
                            $startTime = $cumulative;
                            $cumulative += $segment['duration'];
                            $segPromptPreview = mb_substr($segment['prompt'] ?? '', 0, 100);
                            if (mb_strlen($segment['prompt'] ?? '') > 100) $segPromptPreview .= '...';
                        @endphp
                        @php $segIntensityVal = $segment['intensity'] ?? 0; @endphp
                        <div class="vw-timeline-segment {{ $segment['type'] ?? 'original' }} {{ $selectedSegIdx === $segIdx ? 'selected' : '' }}"
                             style="width: {{ number_format($widthPct, 1) }}%"
                             @click="let v = document.querySelector('.vw-extend-player-wrap video'); if (v) v.currentTime = {{ $startTime }}; $wire.selectSegment(0, 0, {{ $segIdx }})"
                             data-tooltip="{{ $segPromptPreview ? 'Prompt: ' . $segPromptPreview : '' }}">
                            <span class="vw-seg-label">
                                {{ ($segment['type'] ?? 'original') === 'original' ? 'Original' : 'Ext ' . $segIdx }}
                                @if($segIntensityVal > 0)<span class="vw-seg-fire" title="Chaos: {{ $segIntensityVal }}%"><i class="fa-solid fa-fire"></i>{{ $segIntensityVal }}</span>@endif
                            </span>
                            <span class="vw-seg-duration">{{ number_format($segment['duration'], 1) }}s</span>
                        </div>
                    @endforeach
                </div>

                {{-- Total duration + undo --}}
                <div class="vw-timeline-total">
                    Total: {{ number_format($totalDuration, 1) }}s
                    ({{ count($shot['segments']) }} {{ count($shot['segments']) === 1 ? 'segment' : 'segments' }})
                    @if(count($shot['segments']) > 1)
                        <button wire:click="undoLastExtend(0, 0)" class="vw-extend-undo-btn"
                                wire:loading.attr="disabled"
                                wire:target="undoLastExtend, redoLastExtend">
                            <i class="fa-solid fa-rotate-left"></i> Undo
                        </button>
                    @endif
                    @if(!empty($shot['undoneSegments']))
                        <button wire:click="redoLastExtend(0, 0)" class="vw-extend-redo-btn"
                                wire:loading.attr="disabled"
                                wire:target="undoLastExtend, redoLastExtend">
                            <i class="fa-solid fa-rotate-right"></i> Redo
                        </button>
                    @endif
                </div>
            </div>

            {{-- Segment Edit Panel — appears when a segment is selected --}}
            @if($segmentEditMode && ($segmentEditMode['status'] ?? '') === 'editing')
            <div class="vw-seg-edit">
                <div class="vw-seg-edit-top">
                    <span class="vw-seg-edit-title">
                        <i class="fa-solid fa-pen"></i>
                        Edit {{ ($segmentEditMode['type'] ?? 'original') === 'original' ? 'Original' : 'Ext ' . $segmentEditMode['segmentIndex'] }}
                    </span>
                    <div class="vw-seg-edit-dur">
                        @foreach([5, 8, 10] as $dur)
                            <button wire:click="$set('segmentEditMode.duration', {{ $dur }})"
                                    class="vw-dur-btn {{ ($segmentEditMode['duration'] ?? 8) == $dur ? 'active' : '' }}">
                                {{ $dur }}s
                            </button>
                        @endforeach
                    </div>
                    <button wire:click="cancelSegmentEdit" class="vw-extend-cancel-btn">&times;</button>
                </div>

                <div class="vw-seg-edit-body">
                    @if($segmentEditMode['thumbnailUrl'] ?? null)
                        <div class="vw-seg-edit-thumb">
                            <img src="{{ $segmentEditMode['thumbnailUrl'] }}" alt="Frame" />
                        </div>
                    @endif
                    <div class="vw-seg-edit-prompt-col">
                        @if(empty($segmentEditMode['prompt']))
                            <div class="vw-seg-edit-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i> No prompt saved
                            </div>
                        @endif
                        <textarea wire:model.blur="segmentEditMode.prompt" rows="4"
                                  class="vw-extend-prompt" placeholder="Describe what happens in this segment..."></textarea>
                    </div>
                </div>

                {{-- Chaos/Intensity slider --}}
                @php $segIntensity = $segmentEditMode['intensity'] ?? 50; @endphp
                <div class="vw-extend-intensity-row" x-data="{ intensity: {{ $segIntensity }} }" style="margin-bottom: 0.5rem;">
                    <div class="vw-intensity-header">
                        <label><i class="fa-solid fa-fire"></i> Chaos</label>
                        <span class="vw-intensity-label"
                              :class="{
                                  'calm': intensity <= 20,
                                  'rising': intensity > 20 && intensity <= 45,
                                  'intense': intensity > 45 && intensity <= 65,
                                  'wild': intensity > 65 && intensity <= 85,
                                  'chaos': intensity > 85
                              }"
                              x-text="intensity <= 20 ? 'Calm' : intensity <= 45 ? 'Rising' : intensity <= 65 ? 'Intense' : intensity <= 85 ? 'Wild' : 'Chaos'">
                        </span>
                    </div>
                    <div class="vw-intensity-slider-wrap">
                        <input type="range" min="0" max="100" step="5"
                               x-model="intensity"
                               @change="$wire.set('segmentEditMode.intensity', parseInt(intensity))"
                               class="vw-intensity-slider"
                               :style="'background: linear-gradient(90deg, rgba(99,102,241,0.6) 0%, rgba(249,115,22,0.6) 50%, rgba(239,68,68,0.7) 100%)'">
                        <div class="vw-intensity-ticks">
                            <span>0</span><span>25</span><span>50</span><span>75</span><span>100</span>
                        </div>
                    </div>
                </div>

                <div class="vw-seg-edit-actions">
                    @if(empty($segmentEditMode['prompt']))
                        <button wire:click="autoGenerateSegmentPrompt" class="vw-seg-edit-btn auto"
                                wire:loading.attr="disabled" wire:target="autoGenerateSegmentPrompt">
                            <span wire:loading.remove wire:target="autoGenerateSegmentPrompt">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Generate
                            </span>
                            <span wire:loading wire:target="autoGenerateSegmentPrompt">
                                <i class="fa-solid fa-spinner fa-spin"></i> Analyzing...
                            </span>
                        </button>
                    @endif
                    <button wire:click="regenerateSegment" class="vw-seg-edit-btn regen"
                            wire:loading.attr="disabled" wire:target="regenerateSegment">
                        <span wire:loading.remove wire:target="regenerateSegment">
                            <i class="fa-solid fa-arrows-rotate"></i> Regenerate
                        </span>
                        <span wire:loading wire:target="regenerateSegment">
                            <i class="fa-solid fa-spinner fa-spin"></i> Submitting...
                        </span>
                    </button>
                </div>
            </div>
            @endif
            @endif

                {{-- Extend Panel --}}
                @if($extendMode)
                <div class="vw-extend-panel">
                    <div class="vw-extend-header">
                        <span><i class="fa-solid fa-forward"></i> Extend from {{ number_format($extendMode['timestamp'] ?? 0, 1) }}s</span>
                        <button wire:click="cancelVideoExtend" class="vw-extend-cancel-btn">&times;</button>
                    </div>

                    @if(($extendMode['status'] ?? '') === 'extracting')
                        <div style="text-align: center; padding: 1rem; color: var(--vw-text-secondary);">
                            <i class="fa-solid fa-spinner fa-spin"></i> Extracting frame...
                        </div>
                    @else
                        {{-- Two-column layout: frame left, controls right --}}
                        <div class="vw-extend-body">
                            {{-- Left: Frame preview --}}
                            @if($extendMode['frameUrl'] ?? null)
                            <div class="vw-extend-frame-col">
                                <img src="{{ $extendMode['frameUrl'] }}" class="vw-extend-frame-preview" alt="Frame" />
                                @if(!empty($extendMode['frameAnalysis']))
                                <details style="margin-top: 0.4rem; max-width: 180px;">
                                    <summary style="font-size: 0.65rem; color: var(--vw-text-muted); cursor: pointer; user-select: none;">
                                        <i class="fa-solid fa-eye" style="margin-right: 0.2rem;"></i> What AI sees
                                    </summary>
                                    <p style="font-size: 0.65rem; color: var(--vw-text-secondary); margin: 0.25rem 0 0 0; line-height: 1.35; padding: 0.3rem; background: var(--vw-bg-elevated); border-radius: 0.25rem;">
                                        {{ $extendMode['frameAnalysis'] }}
                                    </p>
                                </details>
                                @endif
                            </div>
                            @endif

                            {{-- Right: Duration + Chaos controls --}}
                            <div class="vw-extend-controls-col">
                                {{-- Duration selector --}}
                                <div class="vw-extend-duration-row">
                                    <label>Duration:</label>
                                    @foreach([5, 8, 10] as $dur)
                                        <button wire:click="$set('extendMode.duration', {{ $dur }})"
                                                class="vw-dur-btn {{ ($extendMode['duration'] ?? 8) == $dur ? 'active' : '' }}">
                                            {{ $dur }}s
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Chaos/Intensity slider --}}
                                @php $intensityVal = $extendMode['intensity'] ?? 50; @endphp
                                <div class="vw-extend-intensity-row" x-data="{ intensity: {{ $intensityVal }} }">
                                    <div class="vw-intensity-header">
                                        <label><i class="fa-solid fa-fire"></i> Chaos</label>
                                        <span class="vw-intensity-label"
                                              :class="{
                                                  'calm': intensity <= 20,
                                                  'rising': intensity > 20 && intensity <= 45,
                                                  'intense': intensity > 45 && intensity <= 65,
                                                  'wild': intensity > 65 && intensity <= 85,
                                                  'chaos': intensity > 85
                                              }"
                                              x-text="intensity <= 20 ? 'Calm' : intensity <= 45 ? 'Rising' : intensity <= 65 ? 'Intense' : intensity <= 85 ? 'Wild' : 'Chaos'">
                                        </span>
                                    </div>
                                    <div class="vw-intensity-slider-wrap">
                                        <input type="range" min="0" max="100" step="5"
                                               x-model="intensity"
                                               @change="$wire.set('extendMode.intensity', parseInt(intensity))"
                                               class="vw-intensity-slider"
                                               :style="'background: linear-gradient(90deg, rgba(99,102,241,0.6) 0%, rgba(249,115,22,0.6) 50%, rgba(239,68,68,0.7) 100%)'">
                                        <div class="vw-intensity-ticks">
                                            <span>0</span><span>25</span><span>50</span><span>75</span><span>100</span>
                                        </div>
                                    </div>
                                    <button class="vw-intensity-regen-btn"
                                            wire:click="regenerateExtendPrompt"
                                            wire:loading.attr="disabled"
                                            wire:target="regenerateExtendPrompt"
                                            title="Regenerate prompt with current chaos level and direction">
                                        <span wire:loading.remove wire:target="regenerateExtendPrompt">
                                            <i class="fa-solid fa-arrows-rotate"></i> Regenerate Prompt
                                        </span>
                                        <span wire:loading wire:target="regenerateExtendPrompt">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Generating...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Full width: Chaos direction --}}
                        <input type="text"
                               wire:model.blur="extendMode.chaosDirection"
                               class="vw-extend-direction"
                               placeholder="{{ __('Direct the chaos: e.g., cat jumps on espresso machine and destroys it...') }}" />

                        {{-- Full width: AI-generated continuation prompt (editable) --}}
                        <textarea wire:model.blur="extendMode.continuationPrompt" rows="4"
                                  class="vw-extend-prompt" placeholder="What happens next..."></textarea>

                        {{-- Generate button --}}
                        <button wire:click="executeVideoExtend" class="vw-social-action-btn orange"
                                wire:loading.attr="disabled"
                                wire:target="executeVideoExtend">
                            <span wire:loading.remove wire:target="executeVideoExtend">
                                <i class="fa-solid fa-film"></i> Generate Continuation
                            </span>
                            <span wire:loading wire:target="executeVideoExtend">
                                <i class="fa-solid fa-spinner fa-spin"></i> Submitting...
                            </span>
                        </button>
                    @endif
            </div>
            @endif
        </div>

        {{-- Right: Workflow Steps --}}
        <div class="vw-social-workflow-panel">
            {{-- Tab Switcher: Create vs Workflow Pipeline --}}
            <div class="vw-social-tab-bar" style="display: flex; gap: 0; border-bottom: 1px solid var(--vw-border); margin-bottom: 0.5rem; padding: 0 0.5rem;">
                <button class="vw-social-tab-btn {{ $socialCreateTab === 'create' ? 'active' : '' }}"
                        wire:click="setSocialCreateTab('create')"
                        style="flex: 1; padding: 0.5rem; font-size: 0.75rem; font-weight: 600; border: none; background: none; color: {{ $socialCreateTab === 'create' ? 'var(--vw-text)' : 'var(--vw-text-muted)' }}; border-bottom: 2px solid {{ $socialCreateTab === 'create' ? 'var(--vw-primary)' : 'transparent' }}; cursor: pointer; transition: all 0.15s;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="margin-right: 0.3rem;"></i> {{ __('Create') }}
                </button>
                <button class="vw-social-tab-btn {{ $socialCreateTab === 'workflow' ? 'active' : '' }}"
                        wire:click="setSocialCreateTab('workflow')"
                        style="flex: 1; padding: 0.5rem; font-size: 0.75rem; font-weight: 600; border: none; background: none; color: {{ $socialCreateTab === 'workflow' ? 'var(--vw-text)' : 'var(--vw-text-muted)' }}; border-bottom: 2px solid {{ $socialCreateTab === 'workflow' ? 'var(--vw-primary)' : 'transparent' }}; cursor: pointer; transition: all 0.15s;">
                    <i class="fa-solid fa-diagram-project" style="margin-right: 0.3rem;"></i> {{ __('Workflow') }}
                    @if($workflowExecutionSummary)
                        @php
                            $completedCount = collect($workflowExecutionSummary['nodes'] ?? [])->where('status', 'completed')->count();
                            $totalCount = count($workflowExecutionSummary['nodes'] ?? []);
                        @endphp
                        <span style="font-size: 0.6rem; background: var(--vw-bg-elevated); color: var(--vw-text-secondary); padding: 0.1rem 0.35rem; border-radius: 0.5rem; margin-left: 0.3rem;">{{ $completedCount }}/{{ $totalCount }}</span>
                    @endif
                </button>
            </div>

            @if($socialCreateTab === 'workflow')
                {{-- Workflow Pipeline Tab --}}
                @include('appvideowizard::livewire.steps.partials._workflow-pipeline')
            @else
            {{-- Create Tab (original content) --}}

            {{-- Idea Summary --}}
            @if(!empty($selectedIdea))
                <div class="vw-social-idea-summary">
                    <strong>{{ $selectedIdea['character'] ?? '' }}</strong> &mdash;
                    {{ $selectedIdea['situation'] ?? '' }}
                    <br><span style="color: var(--vw-text-secondary); font-size: 0.75rem;">
                        @if($isSeedance)
                            <i class="fa-solid fa-bolt"></i> Seedance &mdash; {{ __('Auto-generated audio') }}
                        @elseif(!empty($selectedIdea['audioType']))
                            <i class="fa-solid fa-microphone"></i> InfiniteTalk &mdash; {{ $selectedIdea['audioType'] === 'music-lipsync' ? 'Music Lip-Sync' : 'Voiceover' }}
                        @endif
                    </span>
                </div>
            @endif

            {{-- Section 1: Image --}}
            <div class="vw-social-section {{ ($imageStatus === 'ready') ? 'completed' : '' }}">
                <div class="vw-social-section-header">
                    <div class="vw-social-section-num">
                        @if($imageStatus === 'ready') &#10003; @else 1 @endif
                    </div>
                    <div>
                        <div class="vw-social-section-title">{{ __('Generate Image') }}</div>
                        <div class="vw-social-section-subtitle">{{ __('AI creates your character scene') }}</div>
                    </div>
                    @if($imageStatus !== 'pending')
                        <span class="vw-social-status-badge {{ $imageStatus }}">{{ ucfirst($imageStatus) }}</span>
                    @endif
                </div>

                {{-- Image Model Selector --}}
                <select class="vw-social-model-select" wire:model.live="storyboard.imageModel">
                    <option value="nanobanana">NanoBanana (Fast)</option>
                    <option value="nanobanana_pro">NanoBanana Pro (Quality)</option>
                    <option value="hidream">HiDream (Premium)</option>
                </select>

                @if($imageStatus === 'ready')
                    <div class="vw-social-btn-row">
                        <button class="vw-social-action-btn"
                                wire:click="generateShotImage(0, 0)"
                                wire:loading.attr="disabled"
                                wire:target="generateShotImage">
                            <span wire:loading.remove wire:target="generateShotImage">
                                <i class="fa-solid fa-arrows-rotate"></i> {{ __('Regenerate') }}
                            </span>
                            <span wire:loading wire:target="generateShotImage">
                                <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Generating...') }}
                            </span>
                        </button>
                        @php
                            $shotUpscaled = $this->multiShotMode['decomposedScenes'][0]['shots'][0]['upscaled'] ?? false;
                        @endphp
                        @if(!$shotUpscaled)
                            <button class="vw-social-action-btn upscale"
                                    wire:click="upscaleShotImage(0, 0)"
                                    wire:loading.attr="disabled"
                                    wire:target="upscaleShotImage"
                                    title="{{ __('Upscale image 4x using Aura SR — preserves faces exactly') }}">
                                <span wire:loading.remove wire:target="upscaleShotImage">
                                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i> {{ __('Upscale 4x') }}
                                </span>
                                <span wire:loading wire:target="upscaleShotImage">
                                    <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Upscaling...') }}
                                </span>
                            </button>
                        @else
                            <button class="vw-social-action-btn upscale"
                                    wire:click="upscaleShotImage(0, 0)"
                                    wire:loading.attr="disabled"
                                    wire:target="upscaleShotImage"
                                    title="{{ __('Re-upscale image with Clarity Upscaler') }}">
                                <span wire:loading.remove wire:target="upscaleShotImage">
                                    <i class="fa-solid fa-check"></i> {{ __('Upscaled') }} — <i class="fa-solid fa-arrow-rotate-right"></i> {{ __('Redo') }}
                                </span>
                                <span wire:loading wire:target="upscaleShotImage">
                                    <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Upscaling...') }}
                                </span>
                            </button>
                        @endif

                        {{-- AI Image Studio button --}}
                        <button class="vw-social-action-btn"
                                wire:click="openImageStudio('shot', 0, 0)"
                                title="{{ __('Edit image with AI') }}"
                                style="background: var(--vw-primary);">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Edit') }}
                        </button>

                        {{-- Upload Image button --}}
                        <label class="vw-social-action-btn"
                               title="{{ __('Upload your own image') }}"
                               style="background: rgba(56,189,248,0.15); border: 1px solid rgba(56,189,248,0.3); cursor: pointer; position: relative;">
                            <input type="file" accept="image/*" wire:model="uploadedSceneImage"
                                   style="position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;" />
                            <span wire:loading.remove wire:target="uploadedSceneImage">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </span>
                            <span wire:loading wire:target="uploadedSceneImage">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            </span>
                        </label>

                        {{-- Asset History button --}}
                        <button class="vw-social-action-btn"
                                wire:click="openAssetHistory('scene', 0)"
                                title="{{ __('View image history') }}"
                                style="background: rgba(0, 0, 0, 0.04); min-width: auto; padding: 0.5rem 0.75rem;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>
                    </div>
                @else
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button class="vw-social-action-btn orange"
                                wire:click="generateShotImage(0, 0)"
                                wire:loading.attr="disabled"
                                wire:target="generateShotImage">
                            <span wire:loading.remove wire:target="generateShotImage">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Generate Image') }}
                            </span>
                            <span wire:loading wire:target="generateShotImage">
                                <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Generating...') }}
                            </span>
                        </button>

                        <span style="color: var(--vw-border-accent); font-size: 0.75rem;">{{ __('or') }}</span>

                        {{-- Upload instead of generating --}}
                        <label class="vw-social-action-btn"
                               title="{{ __('Upload your own image') }}"
                               style="background: rgba(56,189,248,0.15); border: 1px solid rgba(56,189,248,0.3); cursor: pointer; position: relative;">
                            <input type="file" accept="image/*" wire:model="uploadedSceneImage"
                                   style="position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;" />
                            <span wire:loading.remove wire:target="uploadedSceneImage">
                                <i class="fa-solid fa-cloud-arrow-up"></i> {{ __('Upload') }}
                            </span>
                            <span wire:loading wire:target="uploadedSceneImage">
                                <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Uploading...') }}
                            </span>
                        </label>
                    </div>
                @endif
            </div>

            @if($isSeedance)
            {{-- Section 2: Video Prompt (Seedance mode) --}}
            <div class="vw-social-section">
                <div class="vw-social-section-header">
                    <div class="vw-social-section-num">2</div>
                    <div>
                        <div class="vw-social-section-title">{{ __('Video Prompt & Duration') }}</div>
                        <div class="vw-social-section-subtitle">{{ __('Describe the scene — AI generates video + audio') }}</div>
                    </div>
                    <span class="vw-social-engine-badge"><i class="fa-solid fa-bolt"></i> Seedance</span>
                </div>

                <div class="vw-social-prompt-editor">
                    <label>{{ __('Video Prompt') }}</label>
                    <textarea wire:model.blur="multiShotMode.decomposedScenes.0.shots.0.videoPrompt"
                              placeholder="{{ __('Describe the scene, action, dialogue (in "quotes"), and sounds...') }}">{{ $shot['videoPrompt'] ?? '' }}</textarea>
                    <small>{{ __('140-170 words ideal. Dialogue trigger → instant chaos. Continuous animal sounds + intensity qualifiers on every action. End with "Cinematic, photorealistic."') }}</small>
                </div>

                {{-- Chaos Mode Toggle --}}
                <div class="vw-chaos-mode-row" x-data="{
                    chaosMode: $wire.entangle('multiShotMode.decomposedScenes.0.shots.0.seedanceChaosMode'),
                }">
                    <button type="button"
                        class="vw-chaos-toggle"
                        :class="{ 'active': chaosMode }"
                        @click="chaosMode = !chaosMode">
                        <i class="fa-solid fa-fire"></i>
                        <span>CHAOS</span>
                    </button>
                    <span class="vw-chaos-hint" x-show="chaosMode" x-transition x-cloak>
                        <i class="fa-solid fa-camera-rotate"></i> Auto: Handheld + Dynamic camera
                    </span>
                </div>

                {{-- Camera Movement Picker --}}
                @php
                $cameraMoves = [
                    ['id' => 'none', 'label' => 'None', 'icon' => 'fa-thumbtack'],
                    ['id' => 'push-in', 'label' => 'Push In', 'icon' => 'fa-compress'],
                    ['id' => 'pull-out', 'label' => 'Pull Out', 'icon' => 'fa-expand'],
                    ['id' => 'pan-left', 'label' => 'Pan Left', 'icon' => 'fa-arrow-left'],
                    ['id' => 'pan-right', 'label' => 'Pan Right', 'icon' => 'fa-arrow-right'],
                    ['id' => 'orbit', 'label' => 'Orbit', 'icon' => 'fa-rotate'],
                    ['id' => 'tracking', 'label' => 'Tracking', 'icon' => 'fa-route'],
                    ['id' => 'handheld', 'label' => 'Handheld', 'icon' => 'fa-hand'],
                ];
                $currentCameraMove = $shot['seedanceCameraMove'] ?? 'none';
                $currentCameraIntensity = $shot['seedanceCameraMoveIntensity'] ?? 'moderate';
                @endphp
                <div class="vw-camera-section"
                     :class="{ 'chaos-disabled': $wire.$get('multiShotMode.decomposedScenes.0.shots.0.seedanceChaosMode') }"
                     x-data="{ showIntensity: {{ $currentCameraMove !== 'none' ? 'true' : 'false' }} }">
                    <label><i class="fa-solid fa-video"></i> {{ __('Camera Movement') }}</label>
                    <div class="vw-camera-pills">
                        @foreach($cameraMoves as $move)
                        <button type="button"
                            class="vw-camera-pill {{ $currentCameraMove === $move['id'] ? 'active' : '' }}"
                            wire:click="$set('multiShotMode.decomposedScenes.0.shots.0.seedanceCameraMove', '{{ $move['id'] }}')"
                            @click="showIntensity = '{{ $move['id'] }}' !== 'none'">
                            <i class="fa-solid {{ $move['icon'] }}"></i>
                            <span>{{ $move['label'] }}</span>
                        </button>
                        @endforeach
                    </div>

                    <div class="vw-camera-intensity" x-show="showIntensity" x-transition x-cloak>
                        <label>{{ __('Intensity') }}</label>
                        <div class="vw-intensity-pills">
                            @foreach(['subtle' => 'Subtle', 'moderate' => 'Moderate', 'dynamic' => 'Dynamic'] as $intId => $intLabel)
                            <button type="button"
                                class="vw-intensity-pill {{ $currentCameraIntensity === $intId ? 'active' : '' }}"
                                wire:click="$set('multiShotMode.decomposedScenes.0.shots.0.seedanceCameraMoveIntensity', '{{ $intId }}')">
                                {{ $intLabel }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="vw-seedance-options-row">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.35rem;">{{ __('Duration') }}</label>
                        <select class="vw-social-duration-select"
                                wire:model.live="multiShotMode.decomposedScenes.0.shots.0.selectedDuration">
                            <option value="4">4 {{ __('seconds') }}</option>
                            <option value="5">5 {{ __('seconds') }}</option>
                            <option value="6">6 {{ __('seconds') }}</option>
                            <option value="8" selected>8 {{ __('seconds') }} ({{ __('Recommended') }})</option>
                            <option value="10">10 {{ __('seconds') }}</option>
                            <option value="12">12 {{ __('seconds') }}</option>
                        </select>
                    </div>
                    <div x-data="{
                        quality: $wire.entangle('multiShotMode.decomposedScenes.0.shots.0.seedanceQuality'),
                        resolution: $wire.entangle('multiShotMode.decomposedScenes.0.shots.0.selectedResolution'),
                        init() {
                            this.$watch('quality', (val) => {
                                if (val === 'fast' && this.resolution === '480p') {
                                    this.resolution = '720p';
                                }
                            });
                        }
                    }">
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.35rem;">{{ __('Resolution') }}</label>
                        <select class="vw-social-resolution-select" x-model="resolution">
                            <option value="480p" x-show="quality !== 'fast'">480p ({{ __('Faster / Cheaper') }})</option>
                            <option value="720p">720p ({{ __('Recommended') }})</option>
                            <option value="1080p">1080p ({{ __('Higher Quality') }})</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--vw-text-secondary); margin-bottom: 0.35rem;">{{ __('Quality') }}</label>
                        <select class="vw-social-quality-select"
                                wire:model.live="multiShotMode.decomposedScenes.0.shots.0.seedanceQuality">
                            <option value="pro">{{ __('Pro') }} ({{ __('Best Quality') }})</option>
                            <option value="fast">{{ __('Fast') }} ({{ __('Cheaper / Quicker') }})</option>
                        </select>
                    </div>
                </div>

                {{-- Background Music Toggle --}}
                <div class="vw-music-toggle-row" x-data="{ bgMusic: $wire.entangle('multiShotMode.decomposedScenes.0.shots.0.seedanceBackgroundMusic') }">
                    <label class="vw-music-toggle-label">
                        <input type="checkbox" x-model="bgMusic" class="vw-music-toggle-checkbox">
                        <span class="vw-music-toggle-switch"></span>
                        <span class="vw-music-toggle-text">
                            <i class="fa-solid fa-music"></i> {{ __('Background Music') }}
                        </span>
                    </label>
                    <span class="vw-music-toggle-hint">{{ __('Adds energetic music to the generated video') }}</span>
                </div>
            </div>
            @else
            {{-- Section 2: Audio (InfiniteTalk mode) --}}
            <div class="vw-social-section {{ ($audioStatus === 'ready') ? 'completed' : '' }}">
                <div class="vw-social-section-header">
                    <div class="vw-social-section-num">
                        @if($audioStatus === 'ready') &#10003; @else 2 @endif
                    </div>
                    <div>
                        <div class="vw-social-section-title">{{ __('Add Audio') }}</div>
                        <div class="vw-social-section-subtitle">{{ __('Voice or music for lip-sync') }}</div>
                    </div>
                    @if($audioStatus !== 'pending')
                        <span class="vw-social-status-badge {{ $audioStatus }}">{{ ucfirst($audioStatus) }}</span>
                    @endif
                </div>

                {{-- Audio Type Tabs --}}
                <div class="vw-social-audio-tabs">
                    <button class="vw-social-audio-tab" :class="{ 'active': audioTab === 'voice' }" @click="audioTab = 'voice'">
                        <i class="fa-solid fa-microphone"></i> {{ __('Voice') }}
                    </button>
                    <button class="vw-social-audio-tab" :class="{ 'active': audioTab === 'music' }" @click="audioTab = 'music'">
                        <i class="fa-solid fa-music"></i> {{ __('Music') }}
                    </button>
                </div>

                {{-- Voice Tab --}}
                <div x-show="audioTab === 'voice'" x-cloak>
                    @if($isDialogueShot)
                        <div style="font-size: 0.78rem; color: var(--vw-text-secondary); margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-comments" style="color: var(--vw-text-secondary);"></i>
                            {{ __('Generates separate voices for') }}
                            <strong style="color: var(--vw-text-secondary);">{{ $shot['charactersInShot'][0] ?? 'Speaker 1' }}</strong>
                            {{ __('and') }}
                            <strong style="color: #0891b2;">{{ $shot['charactersInShot'][1] ?? 'Speaker 2' }}</strong>
                        </div>
                    @endif
                    <button class="vw-social-action-btn"
                            wire:click="generateShotVoiceover(0, 0)"
                            wire:loading.attr="disabled"
                            wire:target="generateShotVoiceover"
                            @if($imageStatus !== 'ready') disabled title="{{ __('Generate image first') }}" @endif>
                        <span wire:loading.remove wire:target="generateShotVoiceover">
                            <i class="fa-solid fa-volume-high"></i>
                            @if($isDialogueShot)
                                {{ ($audioStatus === 'ready' && $audioSource !== 'music_upload') ? __('Regenerate Dialogue') : __('Generate Dialogue Voices') }}
                            @else
                                {{ ($audioStatus === 'ready' && $audioSource !== 'music_upload') ? __('Regenerate Voice') : __('Generate Voice') }}
                            @endif
                        </span>
                        <span wire:loading wire:target="generateShotVoiceover">
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Generating...') }}
                        </span>
                    </button>
                </div>

                {{-- Music Tab --}}
                <div x-show="audioTab === 'music'" x-cloak>
                    <input type="file" class="vw-social-file-upload" wire:model="musicUpload" accept=".mp3,.wav,.flac,.m4a,.ogg" />
                    <button class="vw-social-action-btn"
                            wire:click="uploadMusicForShot(0, 0)"
                            wire:loading.attr="disabled"
                            wire:target="uploadMusicForShot"
                            @if(!$musicUpload) disabled @endif>
                        <span wire:loading.remove wire:target="uploadMusicForShot">
                            <i class="fa-solid fa-upload"></i> {{ __('Upload & Apply') }}
                        </span>
                        <span wire:loading wire:target="uploadMusicForShot">
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Uploading...') }}
                        </span>
                    </button>
                </div>

                {{-- Audio Player --}}
                @if($audioUrl && $audioStatus === 'ready')
                    @if($isDialogueShot && $audioUrl2)
                        <div style="font-size: 0.75rem; color: var(--vw-text-secondary); margin-bottom: 0.25rem; font-weight: 600;">
                            <i class="fa-solid fa-comments"></i> {{ __('Dialogue Mode') }} &mdash; {{ $shot['charactersInShot'][0] ?? 'Speaker 1' }}
                        </div>
                    @endif
                    <audio src="{{ $audioUrl }}" controls class="vw-social-audio-player"></audio>
                    @if($isDialogueShot && $audioUrl2)
                        <div style="font-size: 0.75rem; color: #0891b2; margin-top: 0.5rem; margin-bottom: 0.25rem; font-weight: 600;">
                            <i class="fa-solid fa-comments"></i> {{ $shot['charactersInShot'][1] ?? 'Speaker 2' }}
                        </div>
                        <audio src="{{ $audioUrl2 }}" controls class="vw-social-audio-player"></audio>
                    @endif
                @endif
            </div>
            @endif {{-- end @if($isSeedance) / @else --}}

            {{-- Section 3: Animate --}}
            <div class="vw-social-section {{ ($videoStatus === 'ready') ? 'completed' : '' }}">
                <div class="vw-social-section-header">
                    <div class="vw-social-section-num">
                        @if($videoStatus === 'ready') &#10003; @else 3 @endif
                    </div>
                    <div>
                        <div class="vw-social-section-title">{{ $isSeedance ? __('Generate Video') : __('Animate with Lip-Sync') }}</div>
                        <div class="vw-social-section-subtitle">{{ $isSeedance ? __('Seedance creates video + audio from your prompt') : __('InfiniteTalk brings your character to life') }}</div>
                    </div>
                    @if($videoStatus !== 'pending')
                        <span class="vw-social-status-badge {{ $videoStatus }}">{{ ucfirst($videoStatus) }}</span>
                    @endif
                </div>

                <button class="vw-social-action-btn orange"
                        wire:click="generateShotVideo(0, 0)"
                        wire:loading.attr="disabled"
                        wire:target="generateShotVideo"
                        @if($isSeedance)
                            @if($imageStatus !== 'ready') disabled title="{{ __('Generate image first') }}"
                            @elseif(in_array($videoStatus, ['generating', 'processing'])) disabled
                            @endif
                        @else
                            @if($imageStatus !== 'ready' || $audioStatus !== 'ready') disabled title="{{ __('Image and audio required') }}"
                            @elseif(in_array($videoStatus, ['generating', 'processing'])) disabled
                            @endif
                        @endif>
                    @if(in_array($videoStatus, ['generating', 'processing']))
                        <span>
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Rendering video...') }}
                        </span>
                    @else
                        <span wire:loading.remove wire:target="generateShotVideo">
                            <i class="fa-solid fa-film"></i>
                            @if($isSeedance)
                                {{ ($videoStatus === 'ready') ? __('Regenerate Video') : __('Generate Video') }}
                            @else
                                {{ ($videoStatus === 'ready') ? __('Re-Animate') : __('Animate with Lip-Sync') }}
                            @endif
                        </span>
                        <span wire:loading wire:target="generateShotVideo">
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Submitting...') }}
                        </span>
                    @endif
                </button>
                @if(in_array($videoStatus, ['generating', 'processing']))
                    <button class="vw-social-action-btn" style="margin-top: 0.35rem; font-size: 0.78rem; opacity: 0.7;"
                            @click="manualCheck()">
                        <i class="fa-solid fa-arrows-rotate"></i> {{ __('Check Status') }}
                    </button>
                @endif

                {{-- Swap Speaker Faces button for dialogue shots (InfiniteTalk only) --}}
                @if(!$isSeedance && $isDialogueShot && count($charactersInShot) >= 2 && $audioStatus === 'ready')
                    <div class="vw-social-face-order" style="margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: var(--vw-text-secondary);">
                            <span><i class="fa-solid fa-arrow-left"></i> {{ $faceOrder[0] ?? '?' }}</span>
                            <span style="color: #475569;">|</span>
                            <span>{{ $faceOrder[1] ?? '?' }} <i class="fa-solid fa-arrow-right"></i></span>
                            <button class="vw-social-swap-btn"
                                    wire:click="swapSpeakerFaces(0, 0)"
                                    wire:loading.attr="disabled"
                                    title="{{ __('Swap which voice plays on which face') }}">
                                <i class="fa-solid fa-right-left"></i> {{ __('Swap') }}
                            </button>
                        </div>
                    </div>

                    {{-- Dialogue Animation Mode Toggle --}}
                    <div class="vw-social-anim-mode" style="margin-top: 0.5rem;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.75rem;">
                            <button wire:click="$set('dialogueAnimMode', 'single_take')"
                                    class="vw-mode-btn {{ $dialogueAnimMode === 'single_take' ? 'active' : '' }}">
                                Single Take
                            </button>
                            <button wire:click="$set('dialogueAnimMode', 'dual_take')"
                                    class="vw-mode-btn {{ $dialogueAnimMode === 'dual_take' ? 'active' : '' }}">
                                Dual Take
                            </button>
                        </div>
                        <div class="vw-mode-hint">
                            {{ $dialogueAnimMode === 'dual_take'
                                ? __('Sequential renders per speaker with smooth transition. Better body movement control.')
                                : __('One continuous render for both speakers.') }}
                        </div>
                    </div>
                @endif

                @if(in_array($videoStatus, ['generating', 'processing']))
                    <div class="vw-social-progress-bar">
                        <div class="vw-social-progress-text">
                            @if($segmentEditMode && ($segmentEditMode['status'] ?? '') === 'generating')
                                <i class="fa-solid fa-arrows-rotate"></i>
                                {{ __('Regenerating segment...') }}
                            @elseif($extendMode && ($extendMode['status'] ?? '') === 'generating')
                                <i class="fa-solid fa-forward"></i>
                                {{ __('Generating continuation from frame...') }}
                            @elseif($shot['dualTakeMode'] ?? false)
                                @php
                                    $t1Done = !empty($shot['dualTake1VideoUrl']);
                                    $t2Done = !empty($shot['dualTake2VideoUrl']);
                                @endphp
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                @if(!$t1Done && !$t2Done)
                                    {{ __('Rendering Take 1...') }}
                                @elseif($t1Done && !$t2Done)
                                    {{ __('Take 1 done! Rendering Take 2...') }}
                                @else
                                    {{ __('Joining takes...') }}
                                @endif
                            @else
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                {{ __('AI is animating your character...') }}
                            @endif
                        </div>
                        <div class="vw-social-progress-track">
                            <div class="vw-social-progress-fill"></div>
                        </div>
                        <div class="vw-social-progress-hint">
                            @if($segmentEditMode && ($segmentEditMode['status'] ?? '') === 'generating')
                                {{ __('Regenerating segment — this usually takes 2-5 minutes') }}
                            @elseif($extendMode && ($extendMode['status'] ?? '') === 'generating')
                                {{ __('Extending your video — this usually takes 2-5 minutes') }}
                            @else
                                {{ ($shot['dualTakeMode'] ?? false) ? __('Sequential rendering — Take 2 uses Take 1\'s last frame for smooth transition') : __('This usually takes 2-5 minutes') }}
                            @endif
                        </div>
                    </div>
                @endif

                {{-- View Diagnostic button (dual take mode) --}}
                @if(($shot['dualTakeMode'] ?? false) && !empty($shot['diagnosticUrl']))
                    <a href="{{ $shot['diagnosticUrl'] }}" target="_blank" rel="noopener"
                       style="display:flex;align-items:center;gap:0.4rem;margin-top:0.5rem;padding:0.4rem 0.75rem;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);border-radius:0.5rem;color:var(--vw-text-secondary);font-size:0.75rem;font-weight:600;text-decoration:none;transition:all 0.2s;width:fit-content;"
                       onmouseover="this.style.background='rgba(59,130,246,0.2)'" onmouseout="this.style.background='rgba(59,130,246,0.12)'">
                        <i class="fa-solid fa-microscope"></i>
                        {{ __('View Pipeline Diagnostic') }}
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.65rem;opacity:0.7;"></i>
                    </a>
                @endif
            </div>

            {{-- Section 4: Export --}}
            @if($videoStatus === 'ready')
                <div class="vw-social-section" style="border-color: rgba(249,115,22,0.3); background: rgba(249,115,22,0.05);">
                    <button class="vw-social-next-btn" wire:click="nextStep">
                        <i class="fa-solid fa-arrow-right"></i>
                        {{ __('Next: Export') }}
                    </button>
                </div>
            @endif

            {{-- Creation Details Debug Panel --}}
            <div class="vw-social-debug-panel" x-data="{ open: false }">
                <button class="vw-social-debug-toggle" @click="open = !open">
                    <i class="fa-solid fa-code"></i>
                    <span>{{ __('Creation Details') }}</span>
                    <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <div x-show="open" x-collapse x-cloak>
                    {{-- Image Section --}}
                    <details class="vw-debug-section" open>
                        <summary><i class="fa-solid fa-image"></i> {{ __('Image Generation') }}</summary>
                        <div class="vw-debug-content">
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Model') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['image']['model'] }}</span>
                            </div>
                            @if($creationDetails['image']['prompt'])
                                <div class="vw-debug-field">
                                    <span class="vw-debug-label">{{ __('Prompt') }}</span>
                                </div>
                                <div class="vw-debug-prompt">{{ $creationDetails['image']['prompt'] }}</div>
                            @else
                                <div class="vw-debug-field">
                                    <span class="vw-debug-label">{{ __('Prompt') }}</span>
                                    <span class="vw-debug-value" style="color: var(--vw-text-muted);">{{ __('Generate image to see prompt') }}</span>
                                </div>
                            @endif
                        </div>
                    </details>

                    {{-- Voice Section --}}
                    <details class="vw-debug-section">
                        <summary><i class="fa-solid fa-microphone"></i> {{ __('Voice Generation') }}</summary>
                        <div class="vw-debug-content">
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Provider') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['voice']['provider'] }}</span>
                            </div>
                            @foreach(['speaker1', 'speaker2'] as $speaker)
                                @if($creationDetails['voice'][$speaker])
                                    <div class="vw-debug-speaker">
                                        <div class="vw-debug-speaker-name">{{ $creationDetails['voice'][$speaker]['name'] }}</div>
                                        <div class="vw-debug-field">
                                            <span class="vw-debug-label">{{ __('Voice ID') }}</span>
                                            <span class="vw-debug-value">{{ $creationDetails['voice'][$speaker]['voiceId'] ?? 'auto' }}</span>
                                        </div>
                                        <div class="vw-debug-field">
                                            <span class="vw-debug-label">{{ __('Species') }}</span>
                                            <span class="vw-debug-value">{{ $creationDetails['voice'][$speaker]['species'] }}</span>
                                        </div>
                                        <div class="vw-debug-field">
                                            <span class="vw-debug-label">{{ __('Duration') }}</span>
                                            <span class="vw-debug-value">{{ $creationDetails['voice'][$speaker]['duration'] ? number_format($creationDetails['voice'][$speaker]['duration'], 1) . 's' : __('pending') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            @if($creationDetails['voice']['stylePrompt1'])
                                <div class="vw-debug-field">
                                    <span class="vw-debug-label">{{ __('Style (Speaker 1)') }}</span>
                                </div>
                                <div class="vw-debug-prompt">{{ $creationDetails['voice']['stylePrompt1'] }}</div>
                            @endif
                            @if($creationDetails['voice']['stylePrompt2'] ?? null)
                                <div class="vw-debug-field">
                                    <span class="vw-debug-label">{{ __('Style (Speaker 2)') }}</span>
                                </div>
                                <div class="vw-debug-prompt">{{ $creationDetails['voice']['stylePrompt2'] }}</div>
                            @endif
                        </div>
                    </details>

                    {{-- Animation Section --}}
                    <details class="vw-debug-section">
                        <summary><i class="fa-solid fa-film"></i> {{ __('Animation') }}</summary>
                        <div class="vw-debug-content">
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Model') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['animation']['model'] }}</span>
                            </div>
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Mode') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['animation']['personCount'] }} / {{ $creationDetails['animation']['speechType'] }}</span>
                            </div>
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Duration') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['animation']['duration'] }}s</span>
                            </div>
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Emotion') }}</span>
                                <span class="vw-debug-value">{{ $creationDetails['animation']['emotion'] }}</span>
                            </div>
                            <div class="vw-debug-field">
                                <span class="vw-debug-label">{{ __('Face Order') }}</span>
                                <span class="vw-debug-value">{{ implode(' → ', $creationDetails['animation']['faceOrder']) }}</span>
                            </div>
                            @if($creationDetails['animation']['swapped'])
                                <div class="vw-debug-badge swap">{{ __('Audio tracks swapped to match face positions') }}</div>
                            @endif
                            @if($creationDetails['animation']['prompt'])
                                <div class="vw-debug-field">
                                    <span class="vw-debug-label">{{ __('Prompt') }}</span>
                                </div>
                                <div class="vw-debug-prompt">{{ $creationDetails['animation']['prompt'] }}</div>
                            @endif
                        </div>
                    </details>
                </div>
            </div>
            @endif {{-- End of socialCreateTab === 'create' --}}
        </div>
    </div>

</div>

@script
<script>
$wire.on('open-diagnostic', ({ url }) => {
    if (url) {
        window.open(url, '_blank');
    }
});
</script>
@endscript
