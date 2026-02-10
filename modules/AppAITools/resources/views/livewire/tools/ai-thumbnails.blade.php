<div>
@include('appaitools::livewire.partials._tool-base')

<style>
/* Thumbnail PRO - Additional Styles */
.aith-mode-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}
.aith-mode-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.75rem 0.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.aith-mode-tab:hover {
    border-color: #c4b5fd;
    background: #faf5ff;
}
.aith-mode-tab.aith-mode-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
}
.aith-mode-tab i {
    font-size: 1.25rem;
    color: #94a3b8;
    transition: color 0.2s;
}
.aith-mode-tab.aith-mode-active i {
    color: #7c3aed;
}
.aith-mode-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}
.aith-mode-tab.aith-mode-active .aith-mode-name {
    color: #7c3aed;
}
.aith-mode-desc {
    font-size: 0.6875rem;
    color: #94a3b8;
    line-height: 1.3;
}
.aith-mode-credits {
    font-size: 0.625rem;
    font-weight: 600;
    color: #7c3aed;
    background: rgba(124,58,237,0.08);
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    margin-top: 0.25rem;
}
.aith-var-group {
    display: flex;
    gap: 0.375rem;
}
.aith-var-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 700;
    color: #64748b;
    transition: all 0.2s;
}
.aith-var-btn:hover {
    border-color: #c4b5fd;
}
.aith-var-btn.aith-var-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #7c3aed, #6366f1);
    color: #fff;
}
.aith-inline-row {
    display: flex;
    gap: 0.75rem;
}
.aith-inline-row > * {
    flex: 1;
}
.aith-youtube-fetch {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}
.aith-youtube-fetch .aith-form-group {
    flex: 1;
    margin-bottom: 0;
}
.aith-fetch-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-size: 0.8125rem;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    height: 42px;
}
.aith-fetch-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}
.aith-fetch-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.aith-ref-zone {
    position: relative;
}
.aith-ref-remove {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.75rem;
    transition: background 0.2s;
    z-index: 2;
}
.aith-ref-remove:hover {
    background: rgba(220,38,38,0.8);
}
.aith-advanced-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #7c3aed;
    transition: all 0.2s;
}
.aith-advanced-toggle:hover {
    color: #6d28d9;
}
.aith-advanced-toggle .aith-adv-chevron {
    margin-left: auto;
    transition: transform 0.3s;
    font-size: 0.75rem;
}
.aith-advanced-toggle.aith-open .aith-adv-chevron {
    transform: rotate(180deg);
}
.aith-advanced-panel {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.4s ease, padding 0.3s ease;
    padding: 0;
}
.aith-advanced-panel.aith-open {
    max-height: 1000px;
    padding: 1rem 0;
}
.aith-img-grid {
    display: grid;
    gap: 1rem;
}
.aith-img-grid-1 { grid-template-columns: 1fr; }
.aith-img-grid-2 { grid-template-columns: repeat(2, 1fr); }
.aith-img-grid-3 { grid-template-columns: repeat(2, 1fr); }
.aith-img-grid-4 { grid-template-columns: repeat(2, 1fr); }
.aith-img-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    transition: all 0.2s;
}
.aith-img-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.aith-img-card img {
    width: 100%;
    display: block;
    cursor: pointer;
}
.aith-img-actions {
    display: flex;
    gap: 0.375rem;
    padding: 0.625rem;
    flex-wrap: wrap;
}
.aith-img-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.625rem;
    background: #fff;
    color: #64748b;
    font-size: 0.6875rem;
    font-weight: 500;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.aith-img-btn:hover {
    background: #f1f5f9;
    color: #334155;
    border-color: #cbd5e1;
}
.aith-img-btn-hd {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
    border-color: #e9d5ff;
}
.aith-img-btn-hd:hover {
    background: linear-gradient(135deg, #ede9fe, #e0e7ff);
}
.aith-img-btn-edit {
    background: linear-gradient(135deg, #ecfdf5, #f0fdfa);
    color: #059669;
    border-color: #a7f3d0;
}
.aith-img-btn-edit:hover {
    background: linear-gradient(135deg, #d1fae5, #ccfbf1);
}
/* Inpainting canvas styles */
.aith-inpaint-wrap {
    position: relative;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
}
.aith-inpaint-canvas {
    display: block;
    width: 100%;
    cursor: crosshair;
}
.aith-inpaint-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #1e293b;
    color: #fff;
    flex-wrap: wrap;
}
.aith-inpaint-controls label {
    font-size: 0.75rem;
    color: #94a3b8;
}
.aith-inpaint-controls input[type="range"] {
    width: 100px;
    accent-color: #7c3aed;
}
.aith-inpaint-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.aith-inpaint-btn-clear {
    background: rgba(255,255,255,0.1);
    color: #cbd5e1;
}
.aith-inpaint-btn-clear:hover { background: rgba(255,255,255,0.2); }
.aith-inpaint-btn-eraser {
    background: rgba(255,255,255,0.1);
    color: #cbd5e1;
}
.aith-inpaint-btn-eraser.active {
    background: #7c3aed;
    color: #fff;
}
/* Zoom modal */
.aith-zoom-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    cursor: pointer;
}
.aith-zoom-overlay img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
/* Bulk table */
.aith-bulk-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.5rem;
}
.aith-bulk-row {
    background: #f8fafc;
    border-radius: 10px;
}
.aith-bulk-row td {
    padding: 0.75rem;
    font-size: 0.8125rem;
    vertical-align: middle;
}
.aith-bulk-row td:first-child { border-radius: 10px 0 0 10px; }
.aith-bulk-row td:last-child { border-radius: 0 10px 10px 0; }
.aith-bulk-thumb {
    width: 80px;
    border-radius: 6px;
}
.aith-bulk-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 600;
}
.aith-bulk-pending { background: #f1f5f9; color: #64748b; }
.aith-bulk-processing { background: #eff6ff; color: #2563eb; }
.aith-bulk-done { background: #ecfdf5; color: #059669; }
.aith-bulk-error { background: #fef2f2; color: #dc2626; }
/* Face lock */
.aith-facelock-zone {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    border-radius: 10px;
}
.aith-facelock-preview {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #7c3aed;
}
/* Image model selector */
.aith-model-group {
    display: flex;
    gap: 0.5rem;
}
.aith-model-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.625rem 0.5rem;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    position: relative;
}
.aith-model-btn:hover {
    border-color: #c4b5fd;
    background: #faf5ff;
}
.aith-model-btn.aith-model-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
}
.aith-model-name {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
}
.aith-model-btn.aith-model-active .aith-model-name {
    color: #7c3aed;
}
.aith-model-detail {
    font-size: 0.625rem;
    color: #94a3b8;
    line-height: 1.3;
}
.aith-model-credits {
    font-size: 0.5625rem;
    font-weight: 600;
    color: #7c3aed;
    background: rgba(124,58,237,0.08);
    padding: 0.0625rem 0.375rem;
    border-radius: 999px;
}
.aith-model-badge-pro {
    position: absolute;
    top: -6px;
    right: -6px;
    font-size: 0.5rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #7c3aed, #6366f1);
    padding: 0.125rem 0.375rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
/* Category/Style pill buttons */
.aith-pill-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}
.aith-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.4375rem 0.75rem;
    border-radius: 999px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #475569;
    transition: all 0.2s;
    white-space: nowrap;
}
.aith-pill:hover {
    border-color: #c4b5fd;
    background: #faf5ff;
}
.aith-pill.aith-pill-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
    font-weight: 600;
}
.aith-pill .aith-pill-icon {
    font-size: 1rem;
    line-height: 1;
}
/* Style cards */
.aith-style-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}
.aith-style-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.625rem 0.375rem;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.aith-style-card:hover {
    border-color: #c4b5fd;
    background: #faf5ff;
}
.aith-style-card.aith-style-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
}
.aith-style-card .aith-style-icon {
    font-size: 1.25rem;
    line-height: 1;
}
.aith-style-card .aith-style-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}
.aith-style-card.aith-style-active .aith-style-name {
    color: #7c3aed;
}
.aith-style-card .aith-style-desc {
    font-size: 0.625rem;
    color: #94a3b8;
    line-height: 1.3;
}
/* Mode feature tags */
.aith-mode-features {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 0.125rem;
}
.aith-mode-feature-tag {
    font-size: 0.5625rem;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    padding: 0.0625rem 0.375rem;
    border-radius: 999px;
}
.aith-mode-tab.aith-mode-active .aith-mode-feature-tag {
    color: #7c3aed;
    background: rgba(124,58,237,0.08);
}
/* YouTube Preview Modal */
.aith-yt-preview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.aith-yt-preview-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 720px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.5rem;
}
.aith-yt-preview-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.aith-yt-preview-section {
    margin-bottom: 1.25rem;
}
.aith-yt-preview-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.aith-yt-search-card {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 10px;
}
.aith-yt-search-thumb {
    width: 200px;
    min-width: 200px;
    border-radius: 8px;
    aspect-ratio: 16/9;
    object-fit: cover;
}
.aith-yt-search-meta h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.aith-yt-search-meta p {
    font-size: 0.75rem;
    color: #94a3b8;
}
.aith-yt-sidebar-card {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem;
    background: #f8fafc;
    border-radius: 10px;
    max-width: 400px;
}
.aith-yt-sidebar-thumb {
    width: 120px;
    min-width: 120px;
    border-radius: 6px;
    aspect-ratio: 16/9;
    object-fit: cover;
}
.aith-yt-sidebar-meta h4 {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.aith-yt-sidebar-meta p {
    font-size: 0.6875rem;
    color: #94a3b8;
}
.aith-yt-mobile-card {
    max-width: 320px;
    background: #f8fafc;
    border-radius: 10px;
    overflow: hidden;
}
.aith-yt-mobile-thumb {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
}
.aith-yt-mobile-meta {
    padding: 0.5rem 0.75rem;
}
.aith-yt-mobile-meta h4 {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}
.aith-yt-mobile-meta p {
    font-size: 0.6875rem;
    color: #94a3b8;
    margin-top: 0.125rem;
}
/* Bulk generated thumbnail */
.aith-bulk-generated {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-top: 0.5rem;
}
.aith-bulk-gen-thumb {
    width: 120px;
    border-radius: 6px;
    aspect-ratio: 16/9;
    object-fit: cover;
    border: 2px solid #7c3aed;
}
.aith-bulk-gen-actions {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}
/* Face Lock animated faces */
.aith-facelock-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.aith-facelock-faces {
    display: inline-flex;
    align-items: center;
    position: relative;
    width: 20px;
    height: 20px;
    vertical-align: middle;
}
.aith-facelock-faces span {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    line-height: 1;
    animation: aithFaceRotate 6s infinite;
    opacity: 0;
}
.aith-facelock-faces span:nth-child(1) { animation-delay: 0s; }
.aith-facelock-faces span:nth-child(2) { animation-delay: 1.5s; }
.aith-facelock-faces span:nth-child(3) { animation-delay: 3s; }
.aith-facelock-faces span:nth-child(4) { animation-delay: 4.5s; }
@keyframes aithFaceRotate {
    0%, 20% { opacity: 1; transform: translateY(0); }
    25%, 100% { opacity: 0; transform: translateY(-6px); }
}
.aith-facelock-pro-badge {
    font-size: 0.5625rem;
    font-weight: 700;
    color: #7c3aed;
    background: rgba(124,58,237,0.1);
    padding: 0.0625rem 0.375rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
/* Bulk face lock source buttons */
.aith-source-group {
    display: flex;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}
.aith-source-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.625rem;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    transition: all 0.2s;
}
.aith-source-btn:hover {
    border-color: #c4b5fd;
}
.aith-source-btn.aith-source-active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
    font-weight: 600;
}
@media (max-width: 640px) {
    .aith-mode-tabs { flex-direction: column; }
    .aith-inline-row { flex-direction: column; }
    .aith-img-grid-2, .aith-img-grid-3, .aith-img-grid-4 { grid-template-columns: 1fr; }
    .aith-youtube-fetch { flex-direction: column; }
    .aith-inpaint-controls { gap: 0.5rem; }
    .aith-model-group { flex-direction: column; }
    .aith-style-grid { grid-template-columns: repeat(2, 1fr); }
    .aith-yt-search-card { flex-direction: column; }
    .aith-yt-search-thumb { width: 100%; min-width: unset; }
}
</style>

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Thumbnails account for 90% of a video\'s first impression',
        'Faces with emotions increase CTR by 35%',
        'High contrast colors stand out in search results',
        'Text on thumbnails should be readable at small sizes',
        'Reference images help maintain brand consistency'
    ],
    steps: [
        { label: 'Building Prompt', icon: 'fa-wand-magic-sparkles' },
        { label: 'Analyzing Style', icon: 'fa-palette' },
        { label: 'Generating Images', icon: 'fa-image' },
        { label: 'Optimizing Quality', icon: 'fa-sparkles' },
        { label: 'Finalizing', icon: 'fa-check-double' }
    ],
    interval: null, tipInterval: null,
    zoomedImage: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 20) this.progress += 1.2;
            else if (this.progress < 50) this.progress += 0.6;
            else if (this.progress < 75) this.progress += 0.3;
            else if (this.progress < 90) this.progress += 0.15;
            else if (this.progress < 95) this.progress += 0.05;
            this.step = Math.min(Math.floor(this.progress / 20), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = this.steps.length;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    }
}"
x-init="
    Livewire.hook('message.processed', (msg, comp) => {
        if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading();
    });
">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.index') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(!$bulkMode && !$result)
        <button class="aith-nav-btn" wire:click="$toggle('bulkMode')">
            <i class="fa-light fa-layer-group"></i> {{ __('Bulk Mode') }}
        </button>
        @endif
        @if($bulkMode)
        <button class="aith-nav-btn" wire:click="$toggle('bulkMode')">
            <i class="fa-light fa-arrow-left"></i> {{ __('Single Mode') }}
        </button>
        @endif
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-th').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    {{-- ==================== BULK MODE ==================== --}}
    @if($bulkMode && !$result)
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🎨</span> {{ __('Bulk Thumbnail Generator') }}</h2>

        <div class="aith-tabs">
            <button class="aith-tab aith-tab-active" data-aith-tab-group="bulk" data-aith-tab="urls"
                onclick="aithSetTab('bulk','urls')">{{ __('Paste URLs') }}</button>
            <button class="aith-tab" data-aith-tab-group="bulk" data-aith-tab="playlist"
                onclick="aithSetTab('bulk','playlist')">{{ __('Playlist') }}</button>
        </div>

        <div data-aith-tab-content="bulk" data-aith-pane="urls" class="aith-tab-content aith-tab-active">
            <div class="aith-form-group">
                <label class="aith-label">{{ __('YouTube URLs') }} <span class="aith-label-hint">({{ __('One per line, max 10') }})</span></label>
                <textarea wire:model="bulkUrls" class="aith-textarea" rows="5" placeholder="https://youtube.com/watch?v=...&#10;https://youtube.com/watch?v=..."></textarea>
            </div>
            <button class="aith-btn-secondary" wire:click="fetchBulkUrls" style="margin-bottom:1rem;">
                <i class="fa-light fa-download"></i> {{ __('Fetch All') }}
            </button>
        </div>

        <div data-aith-tab-content="bulk" data-aith-pane="playlist" class="aith-tab-content">
            <div class="aith-youtube-fetch" style="margin-bottom:1rem;">
                <div class="aith-form-group">
                    <label class="aith-label">{{ __('Playlist URL') }}</label>
                    <input type="text" wire:model="playlistUrl" class="aith-input" placeholder="https://youtube.com/playlist?list=...">
                </div>
                <button class="aith-fetch-btn" wire:click="fetchPlaylistVideos">
                    <i class="fa-light fa-download"></i> {{ __('Fetch') }}
                </button>
            </div>
        </div>

        {{-- Shared settings --}}
        <div class="aith-form-group" style="margin-bottom:1rem;">
            <label class="aith-label">{{ __('Image Engine') }}</label>
            <div class="aith-model-group">
                @foreach($imageModels as $key => $m)
                <button class="aith-model-btn {{ $imageModel === $key ? 'aith-model-active' : '' }}"
                    wire:click="$set('imageModel', '{{ $key }}')">
                    @if($key === 'nanobanana-pro')
                    <span class="aith-model-badge-pro">PRO</span>
                    @endif
                    <span class="aith-model-name">{{ $m['name'] }}</span>
                    <span class="aith-model-credits">{{ $m['credits'] }} {{ __('cr/img') }}</span>
                </button>
                @endforeach
            </div>
        </div>
        {{-- Category pills --}}
        <div class="aith-form-group" style="margin-bottom:1rem;">
            <label class="aith-label">{{ __('Category') }}</label>
            <div class="aith-pill-group">
                @foreach($categories as $key => $cat)
                <button class="aith-pill {{ $category === $key ? 'aith-pill-active' : '' }}"
                    wire:click="$set('category', '{{ $key }}')">
                    <span class="aith-pill-icon">{{ $cat['icon'] }}</span>
                    {{ $cat['name'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Style cards --}}
        <div class="aith-form-group" style="margin-bottom:1rem;">
            <label class="aith-label">{{ __('Style') }}</label>
            <div class="aith-style-grid">
                @foreach($styles as $key => $st)
                <button class="aith-style-card {{ $style === $key ? 'aith-style-active' : '' }}"
                    wire:click="$set('style', '{{ $key }}')">
                    <span class="aith-style-icon">{{ $st['icon'] }}</span>
                    <span class="aith-style-name">{{ $st['name'] }}</span>
                    <span class="aith-style-desc">{{ $st['description'] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Variations --}}
        <div class="aith-form-group" style="margin-bottom:1rem;">
            <label class="aith-label">{{ __('Variations per video') }}</label>
            <div class="aith-var-group">
                @for($i = 1; $i <= 4; $i++)
                <button class="aith-var-btn {{ $variations === $i ? 'aith-var-active' : '' }}"
                    wire:click="$set('variations', {{ $i }})">{{ $i }}</button>
                @endfor
            </div>
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Custom Prompt') }} <span class="aith-label-hint">({{ __('Optional, applied to all') }})</span></label>
            <textarea wire:model="customPrompt" class="aith-textarea" rows="2" placeholder="{{ __('Additional creative direction...') }}"></textarea>
        </div>

        {{-- Bulk Face Lock (NanoBanana Pro only) --}}
        @if($imageModel === 'nanobanana-pro')
        <div class="aith-form-group" style="margin-top:0.5rem;">
            <label class="aith-checkbox-item">
                <input type="checkbox" wire:model.live="bulkFaceLockEnabled">
                <span class="aith-facelock-label">
                    <span class="aith-facelock-faces"><span>🧑</span><span>👩</span><span>👨</span><span>🧔</span></span>
                    {{ __('Face Lock') }}
                    <span class="aith-facelock-pro-badge">PRO</span>
                </span>
                <span class="aith-label-hint" style="display:block; margin-top:0.125rem;">{{ __('Keep the same face consistent across all bulk thumbnails') }}</span>
            </label>
            @if($bulkFaceLockEnabled)
            <div style="margin-top:0.5rem;">
                <div class="aith-source-group">
                    <button class="aith-source-btn {{ $bulkFaceLockSource === 'first' ? 'aith-source-active' : '' }}"
                        wire:click="$set('bulkFaceLockSource', 'first')">
                        <i class="fa-light fa-play"></i> {{ __('First Video') }}
                    </button>
                    <button class="aith-source-btn {{ $bulkFaceLockSource === 'upload' ? 'aith-source-active' : '' }}"
                        wire:click="$set('bulkFaceLockSource', 'upload')">
                        <i class="fa-light fa-cloud-arrow-up"></i> {{ __('Upload') }}
                    </button>
                </div>

                @if($bulkFaceLockSource === 'first')
                <div style="font-size:0.75rem; color:#64748b; padding:0.5rem; background:#f8fafc; border-radius:8px;">
                    <i class="fa-light fa-info-circle"></i> {{ __('Face will be extracted from the first video\'s thumbnail automatically.') }}
                </div>
                @elseif($bulkFaceLockSource === 'upload')
                    @if($bulkFaceLockPreview)
                    <div class="aith-facelock-zone">
                        <img src="{{ $bulkFaceLockPreview }}" class="aith-facelock-preview" alt="Face">
                        <div>
                            <div style="font-size:0.8125rem; font-weight:600; color:#1e293b;">{{ __('Face locked') }}</div>
                            <button class="aith-img-btn" wire:click="removeBulkFaceLock" style="margin-top:0.25rem;">
                                <i class="fa-light fa-xmark"></i> {{ __('Remove') }}
                            </button>
                        </div>
                    </div>
                    @else
                    <label class="aith-upload-zone" for="bulk-facelock-upload" style="padding:1rem;">
                        <div style="font-size:0.8125rem; color:#475569;"><i class="fa-light fa-user-circle"></i> {{ __('Upload face reference') }}</div>
                        <input type="file" id="bulk-facelock-upload" wire:model="bulkFaceLockImage" accept="image/*" style="display:none;">
                    </label>
                    <div wire:loading wire:target="bulkFaceLockImage" style="font-size:0.75rem; color:#7c3aed; margin-top:0.25rem;">
                        <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Uploading...') }}
                    </div>
                    @endif
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Bulk items table --}}
        @if(count($bulkItems) > 0)
        <div style="margin-top:1rem;">
            <h3 class="aith-section-title">{{ __('Videos') }} ({{ count($bulkItems) }})</h3>
            <table class="aith-bulk-table">
                @foreach($bulkItems as $idx => $item)
                <tr class="aith-bulk-row">
                    <td>
                        @if(!empty($item['data']['thumbnail']))
                        <img src="{{ $item['data']['thumbnail'] }}" class="aith-bulk-thumb" alt="">
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:600; color:#1e293b; font-size:0.8125rem;">
                            {{ Str::limit($item['data']['title'] ?? $item['url'], 60) }}
                        </div>
                        @if(!empty($item['data']['channel']))
                        <div style="font-size:0.6875rem; color:#94a3b8; margin-top:0.125rem;">{{ $item['data']['channel'] }}</div>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <span class="aith-bulk-status aith-bulk-{{ $item['status'] }}">
                            @if($item['status'] === 'processing')
                                <i class="fa-light fa-spinner-third fa-spin"></i>
                            @elseif($item['status'] === 'done')
                                <i class="fa-light fa-check"></i>
                            @elseif($item['status'] === 'error')
                                <i class="fa-light fa-exclamation"></i>
                            @endif
                            {{ ucfirst($item['status']) }}
                        </span>
                        @if($item['status'] === 'error' && $item['error'])
                        <div style="font-size:0.625rem; color:#dc2626; margin-top:0.25rem;">{{ Str::limit($item['error'], 50) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($item['status'] === 'done' && !empty($item['result']['images'][0]))
                        <div class="aith-bulk-generated">
                            <img src="{{ $item['result']['images'][0]['url'] ?? asset($item['result']['images'][0]['path'] ?? '') }}" class="aith-bulk-gen-thumb" alt="">
                            <div class="aith-bulk-gen-actions">
                                <a href="{{ $item['result']['images'][0]['url'] ?? asset($item['result']['images'][0]['path'] ?? '') }}" download class="aith-img-btn">
                                    <i class="fa-light fa-download"></i>
                                </a>
                                <button class="aith-img-btn aith-img-btn-hd" wire:click="upscaleImage(0)" title="{{ __('HD Upscale') }}">
                                    <i class="fa-light fa-arrow-up-right-dots"></i>
                                </button>
                                <button class="aith-img-btn" wire:click="regenerateBulkItem({{ $idx }})" title="{{ __('Regenerate') }}">
                                    <i class="fa-light fa-arrow-rotate-left"></i>
                                </button>
                            </div>
                        </div>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($item['status'] === 'error')
                        <button class="aith-img-btn" wire:click="regenerateBulkItem({{ $idx }})">
                            <i class="fa-light fa-arrow-rotate-left"></i> {{ __('Retry') }}
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>

            @if(!$isBulkProcessing)
            <button class="aith-btn-primary" wire:click="processBulk" style="margin-top:1rem;">
                <i class="fa-light fa-bolt"></i> {{ __('Process All') }}
            </button>
            @else
            <div class="aith-loading" style="margin-top:1rem;">
                <div class="aith-loading-title"><i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Processing bulk thumbnails...') }}</div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- ==================== SINGLE MODE FORM ==================== --}}
    @if(!$result && !$bulkMode)
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🎨</span> {{ __('Thumbnail Maker PRO') }}</h2>

        <div class="aith-feature-box aith-feat-pink">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('NanoBanana Pro: 4K output, 5 face references') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('NanoBanana: Fast quality with face consistency') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('3 modes: Quick, Reference & Upgrade') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('YouTube URL auto-fetch & thumbnail upgrade') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('HD upscaling & AI inpainting editor') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Face lock, batch processing & more') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        {{-- Mode Tabs --}}
        <div class="aith-mode-tabs">
            @foreach($modes as $key => $m)
            <button class="aith-mode-tab {{ $mode === $key ? 'aith-mode-active' : '' }}"
                wire:click="$set('mode', '{{ $key }}')">
                <i class="fa-light {{ $m['icon'] }}"></i>
                <span class="aith-mode-name">{{ $m['name'] }}</span>
                <span class="aith-mode-desc">{{ $m['description'] }}</span>
                @if(!empty($m['features']))
                <div class="aith-mode-features">
                    @foreach($m['features'] as $feat)
                    <span class="aith-mode-feature-tag">{{ $feat }}</span>
                    @endforeach
                </div>
                @endif
                <span class="aith-mode-credits">{{ $m['credits'] }} {{ __('credits') }}</span>
            </button>
            @endforeach
        </div>

        {{-- Title --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Video Title') }}</label>
            <input type="text" wire:model="title" class="aith-input" placeholder="{{ __('Enter your video title or topic') }}">
            @error('title') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        {{-- Category --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Category') }}</label>
            <div class="aith-pill-group">
                @foreach($categories as $key => $cat)
                <button class="aith-pill {{ $category === $key ? 'aith-pill-active' : '' }}"
                    wire:click="$set('category', '{{ $key }}')">
                    <span class="aith-pill-icon">{{ $cat['icon'] }}</span>
                    {{ $cat['name'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Style --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Style') }}</label>
            <div class="aith-style-grid">
                @foreach($styles as $key => $st)
                <button class="aith-style-card {{ $style === $key ? 'aith-style-active' : '' }}"
                    wire:click="$set('style', '{{ $key }}')">
                    <span class="aith-style-icon">{{ $st['icon'] }}</span>
                    <span class="aith-style-name">{{ $st['name'] }}</span>
                    <span class="aith-style-desc">{{ $st['description'] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Variations --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Variations') }}</label>
            <div class="aith-var-group">
                @for($i = 1; $i <= 4; $i++)
                <button class="aith-var-btn {{ $variations === $i ? 'aith-var-active' : '' }}"
                    wire:click="$set('variations', {{ $i }})">{{ $i }}</button>
                @endfor
            </div>
        </div>

        {{-- Image Model --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Image Engine') }}</label>
            <div class="aith-model-group">
                @foreach($imageModels as $key => $m)
                <button class="aith-model-btn {{ $imageModel === $key ? 'aith-model-active' : '' }}"
                    wire:click="$set('imageModel', '{{ $key }}')">
                    @if($key === 'nanobanana-pro')
                    <span class="aith-model-badge-pro">PRO</span>
                    @endif
                    <span class="aith-model-name">{{ $m['name'] }}</span>
                    <span class="aith-model-detail">{{ $m['description'] }}</span>
                    <span class="aith-model-credits">{{ $m['credits'] }} {{ __('cr/img') }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Reference Mode: Upload zone --}}
        @if($mode === 'reference')
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Reference Image') }}</label>
            @if($referenceImagePreview)
            <div class="aith-upload-zone aith-has-file aith-ref-zone">
                <button class="aith-ref-remove" wire:click="removeReferenceImage">
                    <i class="fa-light fa-xmark"></i>
                </button>
                <img src="{{ $referenceImagePreview }}" class="aith-upload-preview" alt="Reference">
            </div>
            @else
            <label class="aith-upload-zone" for="ref-upload">
                <div style="margin-bottom: 0.5rem;"><i class="fa-light fa-cloud-arrow-up" style="font-size:2rem; color:#94a3b8;"></i></div>
                <div style="font-size:0.875rem; color:#475569; font-weight:500;">{{ __('Click to upload reference image') }}</div>
                <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">{{ __('PNG, JPG up to 10MB') }}</div>
                <input type="file" id="ref-upload" wire:model="referenceImage" accept="image/*" style="display:none;">
            </label>
            @endif
            @error('referenceImage') <div class="aith-field-error">{{ $message }}</div> @enderror
            @error('referenceStorageKey') <div class="aith-field-error">{{ $message }}</div> @enderror
            <div wire:loading wire:target="referenceImage" style="font-size:0.75rem; color:#7c3aed; margin-top:0.375rem;">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Uploading...') }}
            </div>
        </div>
        @endif

        {{-- Upgrade Mode: YouTube URL --}}
        @if($mode === 'upgrade')
        <div class="aith-form-group">
            <label class="aith-label">{{ __('YouTube URL') }}</label>
            <div class="aith-youtube-fetch">
                <div class="aith-form-group">
                    <input type="text" wire:model="youtubeUrl" class="aith-input" placeholder="https://youtube.com/watch?v=...">
                </div>
                <button class="aith-fetch-btn" wire:click="fetchYouTubeData" {{ $isFetchingYouTube ? 'disabled' : '' }}>
                    @if($isFetchingYouTube)
                        <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Fetching...') }}
                    @else
                        <i class="fa-brands fa-youtube"></i> {{ __('Fetch') }}
                    @endif
                </button>
            </div>
            @error('youtubeUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        {{-- YouTube Video Info Card --}}
        @if($youtubeData)
        <div class="aith-video-info">
            @if(!empty($youtubeData['thumbnail']))
            <img src="{{ $youtubeData['thumbnail'] }}" class="aith-video-thumb" alt="">
            @endif
            <div class="aith-video-meta">
                <h4>{{ $youtubeData['title'] ?? '' }}</h4>
                <p>{{ $youtubeData['channel'] ?? '' }}</p>
                <div class="aith-video-stats">
                    @if(!empty($youtubeData['views']))
                    <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-eye"></i> {{ number_format($youtubeData['views']) }}</span>
                    @endif
                    @if(!empty($youtubeData['likes']))
                    <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-thumbs-up"></i> {{ number_format($youtubeData['likes']) }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- Advanced Options (Phase 2 - pre-wired) --}}
        @if($mode !== 'quick')
        <div class="aith-form-group" x-data="{ open: @entangle('showAdvanced') }">
            <button class="aith-advanced-toggle" :class="{ 'aith-open': open }" @click="open = !open">
                <i class="fa-light fa-sliders"></i> {{ __('Advanced Options') }}
                <i class="fa-light fa-chevron-down aith-adv-chevron"></i>
            </button>
            <div class="aith-advanced-panel" :class="{ 'aith-open': open }">
                <div class="aith-inline-row">
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Reference Type') }}</label>
                        <select wire:model="referenceType" class="aith-select">
                            <option value="auto">{{ __('Auto Detect') }}</option>
                            <option value="face">{{ __('Face Preserve') }}</option>
                            <option value="product">{{ __('Product Showcase') }}</option>
                            <option value="style">{{ __('Style Transfer') }}</option>
                            <option value="background">{{ __('Background Only') }}</option>
                        </select>
                    </div>
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Composition') }}</label>
                        <select wire:model="compositionTemplate" class="aith-select">
                            <option value="auto">{{ __('Auto') }}</option>
                            <option value="face-right">{{ __('Face Right') }}</option>
                            <option value="face-center">{{ __('Face Center') }}</option>
                            <option value="split-screen">{{ __('Split Screen') }}</option>
                            <option value="product-hero">{{ __('Product Hero') }}</option>
                            <option value="action-shot">{{ __('Action Shot') }}</option>
                            <option value="collage">{{ __('Collage') }}</option>
                        </select>
                    </div>
                </div>
                <div class="aith-inline-row">
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Expression') }}</label>
                        <select wire:model="expressionModifier" class="aith-select">
                            <option value="keep">{{ __('Keep Original') }}</option>
                            <option value="excited">{{ __('Excited') }}</option>
                            <option value="serious">{{ __('Serious') }}</option>
                            <option value="surprised">{{ __('Surprised') }}</option>
                            <option value="curious">{{ __('Curious') }}</option>
                            <option value="confident">{{ __('Confident') }}</option>
                        </select>
                    </div>
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Background') }}</label>
                        <select wire:model="backgroundStyle" class="aith-select">
                            <option value="auto">{{ __('Auto') }}</option>
                            <option value="studio">{{ __('Studio') }}</option>
                            <option value="blur">{{ __('Blurred Bokeh') }}</option>
                            <option value="gradient">{{ __('Gradient') }}</option>
                            <option value="contextual">{{ __('Contextual') }}</option>
                            <option value="dark">{{ __('Dark') }}</option>
                            <option value="vibrant">{{ __('Vibrant') }}</option>
                        </select>
                    </div>
                </div>
                <div class="aith-inline-row">
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Face Strength') }}: {{ $faceStrength }}</label>
                        <div class="aith-range-wrap">
                            <input type="range" wire:model.live="faceStrength" class="aith-range" min="0.5" max="1" step="0.1">
                        </div>
                    </div>
                    <div class="aith-form-group">
                        <label class="aith-label">{{ __('Style Strength') }}: {{ $styleStrength }}</label>
                        <div class="aith-range-wrap">
                            <input type="range" wire:model.live="styleStrength" class="aith-range" min="0.3" max="1" step="0.1">
                        </div>
                    </div>
                </div>

                {{-- Face Lock (NanoBanana Pro only) --}}
                @if($imageModel === 'nanobanana-pro')
                <div class="aith-form-group" style="margin-top:0.5rem;">
                    <label class="aith-checkbox-item">
                        <input type="checkbox" wire:model.live="faceLockEnabled">
                        <span class="aith-facelock-label">
                            <span class="aith-facelock-faces"><span>🧑</span><span>👩</span><span>👨</span><span>🧔</span></span>
                            {{ __('Face Lock') }}
                            <span class="aith-facelock-pro-badge">PRO</span>
                        </span>
                        <span class="aith-label-hint" style="display:block; margin-top:0.125rem;">{{ __('Keep the same face consistent across all variations') }}</span>
                    </label>
                    @if($faceLockEnabled)
                    <div style="margin-top:0.5rem;">
                        @if($faceLockPreview)
                        <div class="aith-facelock-zone">
                            <img src="{{ $faceLockPreview }}" class="aith-facelock-preview" alt="Face">
                            <div>
                                <div style="font-size:0.8125rem; font-weight:600; color:#1e293b;">{{ __('Face locked') }}</div>
                                <button class="aith-img-btn" wire:click="removeFaceLock" style="margin-top:0.25rem;">
                                    <i class="fa-light fa-xmark"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                        @else
                        <label class="aith-upload-zone" for="facelock-upload" style="padding:1rem;">
                            <div style="font-size:0.8125rem; color:#475569;"><i class="fa-light fa-user-circle"></i> {{ __('Upload face reference') }}</div>
                            <input type="file" id="facelock-upload" wire:model="faceLockImage" accept="image/*" style="display:none;">
                        </label>
                        <div wire:loading wire:target="faceLockImage" style="font-size:0.75rem; color:#7c3aed; margin-top:0.25rem;">
                            <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Uploading...') }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Custom Prompt --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Custom Prompt') }} <span class="aith-label-hint">({{ __('Optional') }})</span></label>
            <textarea wire:model="customPrompt" class="aith-textarea" rows="3" placeholder="{{ __('Additional creative direction for the thumbnail...') }}"></textarea>
        </div>

        {{-- Generate Button --}}
        <button wire:click="generate" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="generate">
                <i class="fa-light fa-wand-magic-sparkles"></i>
                {{ __('Generate Thumbnail') }}
                <span style="opacity:0.7; font-size:0.8125rem;">
                    ({{ $imageModels[$imageModel]['credits'] ?? 2 }} {{ __('credits') }} x {{ $variations }})
                </span>
            </span>
            <span wire:loading wire:target="generate">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Generating...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🎨</span> {{ __('Creating thumbnails...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar">
                <div class="aith-progress-fill" :style="'width:' + progress + '%'"></div>
            </div>
            <div class="aith-steps-grid" style="grid-template-columns: repeat(3, 1fr);">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon">
                            <i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i>
                        </span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    {{-- ==================== RESULTS ==================== --}}
    @if($result && isset($result['images']))
    <div class="aith-card" x-data="{ showYtPreview: false }">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">✨</span> {{ __('Generated Thumbnails') }}</h2>
            <div style="display:flex; gap:0.375rem; flex-wrap:wrap;">
                <button class="aith-btn-secondary" @click="showYtPreview = true">
                    <i class="fa-brands fa-youtube"></i> {{ __('YouTube Preview') }}
                </button>
                @if(count($result['images']) > 1)
                <button class="aith-btn-secondary" x-data x-on:click="
                    (async () => {
                        const btn = $el;
                        btn.disabled = true;
                        btn.innerHTML = '<i class=\'fa-light fa-spinner-third fa-spin\'></i> Zipping...';
                        try {
                            const { default: JSZip } = await import('https://cdn.jsdelivr.net/npm/jszip@3.10.1/+esm');
                            const zip = new JSZip();
                            const imgs = @js($result['images']);
                            for (let i = 0; i < imgs.length; i++) {
                                const url = imgs[i].url || ('/' + imgs[i].path);
                                const resp = await fetch(url);
                                const blob = await resp.blob();
                                zip.file('thumbnail_' + (i+1) + '.png', blob);
                            }
                            const content = await zip.generateAsync({type:'blob'});
                            const a = document.createElement('a');
                            a.href = URL.createObjectURL(content);
                            a.download = 'thumbnails.zip';
                            a.click();
                            URL.revokeObjectURL(a.href);
                        } catch(e) { console.error(e); }
                        btn.disabled = false;
                        btn.innerHTML = '<i class=\'fa-light fa-file-zipper\'></i> {{ __('Download ZIP') }}';
                    })()
                ">
                    <i class="fa-light fa-file-zipper"></i> {{ __('Download ZIP') }}
                </button>
                @endif
                <button class="aith-btn-secondary" wire:click="resetForm">
                    <i class="fa-light fa-plus"></i> {{ __('Create New') }}
                </button>
            </div>
        </div>

        {{-- YouTube Preview Modal --}}
        <div x-show="showYtPreview" x-cloak x-transition.opacity class="aith-yt-preview-overlay" @click.self="showYtPreview = false">
            <div class="aith-yt-preview-modal" @click.stop>
                <div class="aith-yt-preview-title">
                    <span><i class="fa-brands fa-youtube" style="color:#FF0000;"></i> {{ __('YouTube Preview') }}</span>
                    <button style="background:none;border:none;cursor:pointer;font-size:1.25rem;color:#94a3b8;" @click="showYtPreview = false">
                        <i class="fa-light fa-xmark"></i>
                    </button>
                </div>

                {{-- Search Results Preview --}}
                <div class="aith-yt-preview-section">
                    <div class="aith-yt-preview-label">{{ __('Search Results') }}</div>
                    <div class="aith-yt-search-card">
                        <img src="{{ $result['images'][0]['url'] ?? asset($result['images'][0]['path'] ?? '') }}" class="aith-yt-search-thumb" alt="">
                        <div class="aith-yt-search-meta">
                            <h4>{{ $title ?: 'Your Video Title' }}</h4>
                            <p>{{ __('Your Channel') }} &middot; {{ number_format(rand(1000, 500000)) }} {{ __('views') }} &middot; {{ rand(1,12) }} {{ __('hours ago') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sidebar Preview --}}
                <div class="aith-yt-preview-section">
                    <div class="aith-yt-preview-label">{{ __('Suggested Sidebar') }}</div>
                    <div class="aith-yt-sidebar-card">
                        <img src="{{ $result['images'][0]['url'] ?? asset($result['images'][0]['path'] ?? '') }}" class="aith-yt-sidebar-thumb" alt="">
                        <div class="aith-yt-sidebar-meta">
                            <h4>{{ $title ?: 'Your Video Title' }}</h4>
                            <p>{{ __('Your Channel') }}</p>
                            <p>{{ number_format(rand(1000, 500000)) }} {{ __('views') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Mobile Feed Preview --}}
                <div class="aith-yt-preview-section">
                    <div class="aith-yt-preview-label">{{ __('Mobile Feed') }}</div>
                    <div class="aith-yt-mobile-card">
                        <img src="{{ $result['images'][0]['url'] ?? asset($result['images'][0]['path'] ?? '') }}" class="aith-yt-mobile-thumb" alt="">
                        <div class="aith-yt-mobile-meta">
                            <h4>{{ $title ?: 'Your Video Title' }}</h4>
                            <p>{{ __('Your Channel') }} &middot; {{ number_format(rand(1000, 500000)) }} {{ __('views') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inpainting Editor --}}
        @if($editingImageIndex !== null && isset($result['images'][$editingImageIndex]))
        @php $editImg = $result['images'][$editingImageIndex]; @endphp
        <div class="aith-card" style="background:#f8fafc; margin-bottom:1rem;"
            x-data="{
                brushSize: 20,
                isEraser: false,
                isDrawing: false,
                ctx: null,
                canvas: null,
                init() {
                    this.canvas = this.$refs.maskCanvas;
                    this.ctx = this.canvas.getContext('2d');
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        this.canvas.width = img.naturalWidth;
                        this.canvas.height = img.naturalHeight;
                        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                    };
                    img.src = '{{ $editImg['url'] ?? asset($editImg['path'] ?? '') }}';
                },
                getPos(e) {
                    const rect = this.canvas.getBoundingClientRect();
                    const scaleX = this.canvas.width / rect.width;
                    const scaleY = this.canvas.height / rect.height;
                    return {
                        x: (e.clientX - rect.left) * scaleX,
                        y: (e.clientY - rect.top) * scaleY
                    };
                },
                startDraw(e) {
                    this.isDrawing = true;
                    const pos = this.getPos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                },
                draw(e) {
                    if (!this.isDrawing) return;
                    const pos = this.getPos(e);
                    this.ctx.lineWidth = this.brushSize;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    if (this.isEraser) {
                        this.ctx.globalCompositeOperation = 'destination-out';
                        this.ctx.strokeStyle = 'rgba(0,0,0,1)';
                    } else {
                        this.ctx.globalCompositeOperation = 'source-over';
                        this.ctx.strokeStyle = 'rgba(255,255,255,0.7)';
                    }
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                },
                stopDraw() { this.isDrawing = false; },
                clearMask() {
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                },
                applyEdit() {
                    const maskData = this.canvas.toDataURL('image/png').replace('data:image/png;base64,', '');
                    $wire.applyInpaintEdit(maskData);
                }
            }">
            <h3 class="aith-section-title"><i class="fa-light fa-pen-paintbrush"></i> {{ __('Inpaint Editor') }}</h3>
            <p style="font-size:0.75rem; color:#64748b; margin-bottom:0.75rem;">{{ __('Paint over areas you want to change, then describe the edit.') }}</p>

            <div class="aith-inpaint-wrap">
                <div style="position:relative;">
                    <img src="{{ $editImg['url'] ?? asset($editImg['path'] ?? '') }}" style="width:100%; display:block; border-radius:12px 12px 0 0;" alt="">
                    <canvas x-ref="maskCanvas" class="aith-inpaint-canvas"
                        style="position:absolute; top:0; left:0; width:100%; height:100%;"
                        @mousedown="startDraw($event)" @mousemove="draw($event)"
                        @mouseup="stopDraw()" @mouseleave="stopDraw()"></canvas>
                </div>
                <div class="aith-inpaint-controls">
                    <label>{{ __('Brush') }}: <span x-text="brushSize + 'px'"></span></label>
                    <input type="range" x-model="brushSize" min="5" max="50" step="1">
                    <button class="aith-inpaint-btn aith-inpaint-btn-eraser" :class="{ 'active': isEraser }" @click="isEraser = !isEraser">
                        <i class="fa-light fa-eraser"></i> {{ __('Eraser') }}
                    </button>
                    <button class="aith-inpaint-btn aith-inpaint-btn-clear" @click="clearMask()">
                        <i class="fa-light fa-trash"></i> {{ __('Clear') }}
                    </button>
                </div>
            </div>

            <div class="aith-form-group">
                <label class="aith-label">{{ __('Edit Prompt') }}</label>
                <input type="text" wire:model="editPrompt" class="aith-input" placeholder="{{ __('e.g. Add dramatic lighting, Change background to sunset...') }}">
                @error('editPrompt') <div class="aith-field-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button class="aith-btn-primary" style="flex:1;" @click="applyEdit()"
                    wire:loading.attr="disabled" wire:target="applyInpaintEdit">
                    <span wire:loading.remove wire:target="applyInpaintEdit">
                        <i class="fa-light fa-wand-magic-sparkles"></i> {{ __('Apply Edit') }}
                    </span>
                    <span wire:loading wire:target="applyInpaintEdit">
                        <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Applying...') }}
                    </span>
                </button>
                <button class="aith-btn-secondary" wire:click="cancelInpaintEdit">{{ __('Cancel') }}</button>
            </div>
        </div>
        @endif

        {{-- Image Grid --}}
        <div class="aith-img-grid aith-img-grid-{{ count($result['images']) }}">
            @foreach($result['images'] as $i => $image)
            <div class="aith-img-card">
                <img src="{{ $image['url'] ?? asset($image['path'] ?? '') }}" alt="Thumbnail {{ $i + 1 }}"
                    @click="zoomedImage = '{{ $image['url'] ?? asset($image['path'] ?? '') }}'">
                <div class="aith-img-actions">
                    <a href="{{ $image['url'] ?? asset($image['path'] ?? '') }}" download="thumbnail_{{ $i + 1 }}.png" class="aith-img-btn">
                        <i class="fa-light fa-download"></i> {{ __('Download') }}
                    </a>
                    @if(!empty($image['hd_path']))
                    <a href="{{ $image['hd_url'] ?? asset($image['hd_path']) }}" download="thumbnail_{{ $i + 1 }}_hd.png" class="aith-img-btn aith-img-btn-hd">
                        <i class="fa-light fa-arrow-up-right-dots"></i> {{ __('Download HD') }}
                    </a>
                    @else
                    <button class="aith-img-btn aith-img-btn-hd" wire:click="upscaleImage({{ $i }})"
                        wire:loading.attr="disabled" wire:target="upscaleImage({{ $i }})">
                        <span wire:loading.remove wire:target="upscaleImage({{ $i }})">
                            <i class="fa-light fa-arrow-up-right-dots"></i> {{ __('HD Upscale') }}
                        </span>
                        <span wire:loading wire:target="upscaleImage({{ $i }})">
                            <i class="fa-light fa-spinner-third fa-spin"></i>
                        </span>
                    </button>
                    @endif
                    <button class="aith-img-btn aith-img-btn-edit" wire:click="startInpaintEdit({{ $i }})">
                        <i class="fa-light fa-pen-paintbrush"></i> {{ __('Edit') }}
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Upgrade Mode: Before/After --}}
        @if($mode === 'upgrade' && $referenceImagePreview && count($result['images']) > 0)
        <div style="margin-top:1.25rem;">
            <h3 class="aith-section-title"><i class="fa-light fa-arrows-left-right"></i> {{ __('Before / After') }}</h3>
            <div class="aith-grid-2">
                <div style="text-align:center;">
                    <div style="font-size:0.6875rem; font-weight:600; color:#94a3b8; text-transform:uppercase; margin-bottom:0.5rem;">{{ __('Original') }}</div>
                    <img src="{{ $referenceImagePreview }}" style="width:100%; border-radius:10px; border:1px solid #e2e8f0;" alt="Original">
                </div>
                <div style="text-align:center;">
                    <div style="font-size:0.6875rem; font-weight:600; color:#7c3aed; text-transform:uppercase; margin-bottom:0.5rem;">{{ __('Upgraded') }}</div>
                    <img src="{{ $result['images'][0]['url'] ?? asset($result['images'][0]['path'] ?? '') }}" style="width:100%; border-radius:10px; border:1px solid #e2e8f0;" alt="Upgraded">
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Zoom Modal --}}
    <div x-show="zoomedImage" x-cloak class="aith-zoom-overlay" @click="zoomedImage = null" x-transition.opacity>
        <img :src="zoomedImage" alt="Zoomed thumbnail" @click.stop>
    </div>
    @endif

    {{-- ==================== HISTORY ==================== --}}
    @if(count($history) > 0)
    <div id="aith-history-th" class="aith-card" style="display:none; margin-top: 1rem;">
        <h3 class="aith-section-title"><i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent Thumbnails') }}</h3>
        @foreach($history as $item)
        <div class="aith-result-item" style="cursor:pointer;" wire:click="loadHistoryItem('{{ $item['id'] }}')">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                @if(!empty($item['assets'][0]['path']))
                <img src="{{ asset($item['assets'][0]['path']) }}" style="width:60px; border-radius:6px;" alt="">
                @endif
                <div>
                    <div class="aith-result-text">{{ $item['title'] ?? 'Untitled' }}</div>
                    <div style="display:flex; gap:0.5rem; align-items:center; margin-top:0.25rem;">
                        @if(!empty($item['input_data']['mode']))
                        <span class="aith-badge aith-badge-purple">{{ ucfirst($item['input_data']['mode']) }}</span>
                        @endif
                        @if(!empty($item['input_data']['image_model']))
                        <span class="aith-badge aith-badge-ghost">{{ $imageModels[$item['input_data']['image_model']]['name'] ?? $item['input_data']['image_model'] }}</span>
                        @endif
                        <span style="font-size:0.6875rem; color:#94a3b8;">{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <style>#aith-history-th.aith-open { display: block !important; }</style>
    @endif

</div>
</div>
