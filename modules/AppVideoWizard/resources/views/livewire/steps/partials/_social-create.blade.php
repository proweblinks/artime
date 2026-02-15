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
        background: linear-gradient(135deg, #0a0a14 0%, #141428 100%);
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
        background: rgba(10, 10, 20, 0.95);
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        flex-shrink: 0;
    }
    .vw-social-create-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .vw-social-create-header .vw-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        background: rgba(100, 100, 140, 0.15);
        border: 1px solid rgba(100, 100, 140, 0.3);
        border-radius: 0.5rem;
        color: #94a3b8;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-create-header .vw-back-btn:hover {
        background: rgba(100, 100, 140, 0.25);
        color: #e2e8f0;
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
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(5, 5, 15, 0.5);
    }
    .vw-social-preview-frame {
        width: 100%;
        max-width: 360px;
        aspect-ratio: 9/16;
        background: rgba(20, 20, 35, 0.8);
        border: 2px solid rgba(139, 92, 246, 0.3);
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
        color: #64748b;
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
        border-left: 1px solid rgba(100, 100, 140, 0.15);
    }
    .vw-social-section {
        background: rgba(25, 25, 45, 0.6);
        border: 1px solid rgba(100, 100, 140, 0.2);
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }
    .vw-social-section.completed {
        border-color: rgba(16, 185, 129, 0.3);
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
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.3);
    }
    .vw-social-section.completed .vw-social-section-num {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .vw-social-section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #e2e8f0;
    }
    .vw-social-section-subtitle {
        font-size: 0.75rem;
        color: #64748b;
    }
    .vw-social-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    }
    .vw-social-action-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .vw-social-action-btn.success {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }
    .vw-social-action-btn.orange {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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
    .vw-social-status-badge.pending { background: rgba(100,100,140,0.2); color: #94a3b8; }
    .vw-social-status-badge.generating { background: rgba(139,92,246,0.2); color: #a78bfa; animation: vw-pulse-badge 1.5s infinite; }
    .vw-social-status-badge.ready { background: rgba(16,185,129,0.2); color: #6ee7b7; }
    .vw-social-status-badge.processing { background: rgba(249,115,22,0.2); color: #fb923c; animation: vw-pulse-badge 1.5s infinite; }
    .vw-social-status-badge.error { background: rgba(239,68,68,0.2); color: #fca5a5; }
    @keyframes vw-pulse-badge { 0%,100%{opacity:0.6} 50%{opacity:1} }
    .vw-social-swap-btn {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.5rem; border-radius: 0.3rem;
        background: rgba(139,92,246,0.15); color: #a78bfa;
        border: 1px solid rgba(139,92,246,0.3);
        font-size: 0.7rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-swap-btn:hover { background: rgba(139,92,246,0.3); color: #c4b5fd; }

    .vw-mode-btn { padding: 0.35rem 0.75rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: #94a3b8; cursor: pointer; transition: all 0.2s; font-size: 0.75rem; }
    .vw-mode-btn.active { background: rgba(139,92,246,0.2); border-color: rgba(139,92,246,0.4); color: #a78bfa; }
    .vw-mode-btn:hover { border-color: rgba(139,92,246,0.3); color: #a78bfa; }
    .vw-mode-hint { font-size: 0.65rem; color: #64748b; margin-top: 0.25rem; }

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
        color: #94a3b8;
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

    .vw-social-audio-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid rgba(100,100,140,0.25);
    }
    .vw-social-audio-tab {
        flex: 1;
        padding: 0.5rem;
        text-align: center;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        background: rgba(25,25,45,0.8);
        color: #94a3b8;
        border: none;
        transition: all 0.2s;
    }
    .vw-social-audio-tab.active {
        background: rgba(139,92,246,0.2);
        color: #a78bfa;
    }
    .vw-social-model-select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        background: rgba(20,20,40,0.8);
        border: 1px solid rgba(100,100,140,0.25);
        border-radius: 0.5rem;
        color: #e2e8f0;
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
    }
    .vw-social-file-upload {
        width: 100%;
        padding: 0.5rem;
        background: rgba(20,20,40,0.5);
        border: 1px dashed rgba(100,100,140,0.3);
        border-radius: 0.5rem;
        color: #94a3b8;
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
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
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
        box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
    }
    .vw-social-idea-summary {
        background: rgba(139,92,246,0.08);
        border: 1px solid rgba(139,92,246,0.2);
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-bottom: 1rem;
        font-size: 0.8rem;
        color: #cbd5e1;
    }
    .vw-social-idea-summary strong { color: #a78bfa; }

    /* Creation Details Debug Panel */
    .vw-social-debug-panel { margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 0.75rem; }
    .vw-social-debug-toggle { width: 100%; display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.15); border-radius: 0.5rem; color: #a78bfa; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .vw-social-debug-toggle:hover { background: rgba(139,92,246,0.15); }
    .vw-social-debug-toggle span { flex: 1; text-align: left; }
    .vw-debug-section { margin-top: 0.5rem; background: rgba(15,23,42,0.5); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.5rem; overflow: hidden; }
    .vw-debug-section summary { padding: 0.5rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #94a3b8; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
    .vw-debug-section summary:hover { color: #e2e8f0; }
    .vw-debug-section summary::-webkit-details-marker { display: none; }
    .vw-debug-section summary::after { content: '+'; margin-left: auto; font-size: 0.9rem; color: #64748b; }
    .vw-debug-section[open] summary::after { content: '\2212'; }
    .vw-debug-content { padding: 0.5rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.04); }
    .vw-debug-field { display: flex; gap: 0.5rem; margin-bottom: 0.35rem; font-size: 0.7rem; }
    .vw-debug-label { color: #64748b; min-width: 80px; flex-shrink: 0; }
    .vw-debug-value { color: #e2e8f0; font-family: 'JetBrains Mono', monospace; }
    .vw-debug-prompt { color: #a78bfa; font-size: 0.65rem; line-height: 1.5; background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 0.375rem; margin-top: 0.25rem; font-family: 'JetBrains Mono', monospace; word-break: break-word; max-height: 120px; overflow-y: auto; }
    .vw-debug-speaker { padding: 0.35rem 0.5rem; background: rgba(255,255,255,0.03); border-radius: 0.375rem; margin-bottom: 0.35rem; }
    .vw-debug-speaker-name { font-size: 0.7rem; font-weight: 700; color: #fbbf24; margin-bottom: 0.25rem; }
    .vw-debug-badge { font-size: 0.65rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; display: inline-block; margin: 0.25rem 0; }
    .vw-debug-badge.swap { background: rgba(249,115,22,0.15); color: #fb923c; }

    .vw-social-prompt-editor { margin-bottom: 0.75rem; }
    .vw-social-prompt-editor label { display: block; font-size: 0.8rem; font-weight: 600; color: #a78bfa; margin-bottom: 0.35rem; }
    .vw-social-prompt-editor textarea {
        width: 100%; min-height: 100px; padding: 0.6rem 0.75rem; background: rgba(20,20,40,0.8);
        border: 1px solid rgba(100,100,140,0.25); border-radius: 0.5rem; color: #e2e8f0;
        font-size: 0.8rem; line-height: 1.5; resize: vertical; outline: none; transition: border-color 0.2s;
    }
    .vw-social-prompt-editor textarea:focus { border-color: rgba(139,92,246,0.5); }
    .vw-social-prompt-editor small { display: block; font-size: 0.7rem; color: #64748b; margin-top: 0.25rem; }
    .vw-social-duration-select, .vw-social-resolution-select {
        width: 100%; padding: 0.5rem 0.75rem; background: rgba(20,20,40,0.8);
        border: 1px solid rgba(100,100,140,0.25); border-radius: 0.5rem; color: #e2e8f0;
        font-size: 0.8rem; margin-bottom: 0.75rem;
    }
    .vw-seedance-options-row {
        display: flex; gap: 0.75rem; margin-bottom: 0.75rem;
    }
    .vw-seedance-options-row > div { flex: 1; }
    .vw-social-engine-badge {
        display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.5rem;
        border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600;
        background: rgba(139,92,246,0.12); color: #a78bfa; border: 1px solid rgba(139,92,246,0.2);
    }

    @media (max-width: 768px) {
        .vw-social-create-body { flex-direction: column; }
        .vw-social-preview-panel { width: 100%; height: 40vh; }
        .vw-social-workflow-panel { width: 100%; border-left: none; border-top: 1px solid rgba(100,100,140,0.15); }
    }
</style>

<div class="vw-social-create"
     x-data="{
        audioTab: '{{ ($audioSource === "music_upload") ? "music" : "voice" }}',
        pollingInterval: null,
        isPolling: false,
        pollCount: 0,
        maxPolls: 120,
        POLL_INTERVAL: 5000,
        initPolling() {
            const status = '{{ $videoStatus }}';
            if (status === 'generating' || status === 'processing') {
                this.startPolling();
            }
            Livewire.on('video-generation-started', () => this.startPolling());
            Livewire.on('video-generation-complete', () => this.stopPolling());
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
            <div class="vw-social-preview-frame">
                @if($videoUrl && $videoStatus === 'ready')
                    <video src="{{ $videoUrl }}" controls loop playsinline></video>
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
                    <img src="{{ $imageUrl }}" alt="Generated image" />
                @else
                    <div class="vw-social-preview-placeholder">
                        <i class="fa-solid fa-image"></i>
                        <div>{{ __('Generate an image to preview') }}</div>
                        <div style="font-size: 0.75rem; margin-top: 0.25rem;">9:16 Vertical</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Workflow Steps --}}
        <div class="vw-social-workflow-panel">
            {{-- Idea Summary --}}
            @if(!empty($selectedIdea))
                <div class="vw-social-idea-summary">
                    <strong>{{ $selectedIdea['character'] ?? '' }}</strong> &mdash;
                    {{ $selectedIdea['situation'] ?? '' }}
                    <br><span style="color: #94a3b8; font-size: 0.75rem;">
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
                    <button class="vw-social-action-btn"
                            wire:click="generateShotImage(0, 0)"
                            wire:loading.attr="disabled"
                            wire:target="generateShotImage">
                        <span wire:loading.remove wire:target="generateShotImage">
                            <i class="fa-solid fa-arrows-rotate"></i> {{ __('Regenerate Image') }}
                        </span>
                        <span wire:loading wire:target="generateShotImage">
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Generating...') }}
                        </span>
                    </button>
                @else
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
                    <small>{{ __('4-layer format: Subject & action, dialogue in "quotes", environmental sounds, visual style & mood.') }}</small>
                </div>

                <div class="vw-seedance-options-row">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.35rem;">{{ __('Duration') }}</label>
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
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.35rem;">{{ __('Resolution') }}</label>
                        <select class="vw-social-resolution-select"
                                wire:model.live="multiShotMode.decomposedScenes.0.shots.0.selectedResolution">
                            <option value="480p">480p ({{ __('Faster / Cheaper') }})</option>
                            <option value="720p" selected>720p ({{ __('Recommended') }})</option>
                        </select>
                    </div>
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
                        <div style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-comments" style="color: #a78bfa;"></i>
                            {{ __('Generates separate voices for') }}
                            <strong style="color: #a78bfa;">{{ $shot['charactersInShot'][0] ?? 'Speaker 1' }}</strong>
                            {{ __('and') }}
                            <strong style="color: #67e8f9;">{{ $shot['charactersInShot'][1] ?? 'Speaker 2' }}</strong>
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
                        <div style="font-size: 0.75rem; color: #a78bfa; margin-bottom: 0.25rem; font-weight: 600;">
                            <i class="fa-solid fa-comments"></i> {{ __('Dialogue Mode') }} &mdash; {{ $shot['charactersInShot'][0] ?? 'Speaker 1' }}
                        </div>
                    @endif
                    <audio src="{{ $audioUrl }}" controls class="vw-social-audio-player"></audio>
                    @if($isDialogueShot && $audioUrl2)
                        <div style="font-size: 0.75rem; color: #67e8f9; margin-top: 0.5rem; margin-bottom: 0.25rem; font-weight: 600;">
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

                {{-- Swap Speaker Faces button for dialogue shots (InfiniteTalk only) --}}
                @if(!$isSeedance && $isDialogueShot && count($charactersInShot) >= 2 && $audioStatus === 'ready')
                    <div class="vw-social-face-order" style="margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #94a3b8;">
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
                            @if($shot['dualTakeMode'] ?? false)
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
                            {{ ($shot['dualTakeMode'] ?? false) ? __('Sequential rendering — Take 2 uses Take 1\'s last frame for smooth transition') : __('This usually takes 2-5 minutes') }}
                        </div>
                    </div>
                @endif

                {{-- View Diagnostic button (dual take mode) --}}
                @if(($shot['dualTakeMode'] ?? false) && !empty($shot['diagnosticUrl']))
                    <a href="{{ $shot['diagnosticUrl'] }}" target="_blank" rel="noopener"
                       style="display:flex;align-items:center;gap:0.4rem;margin-top:0.5rem;padding:0.4rem 0.75rem;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);border-radius:0.5rem;color:#60a5fa;font-size:0.75rem;font-weight:600;text-decoration:none;transition:all 0.2s;width:fit-content;"
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
                                    <span class="vw-debug-value" style="color: #64748b;">{{ __('Generate image to see prompt') }}</span>
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
