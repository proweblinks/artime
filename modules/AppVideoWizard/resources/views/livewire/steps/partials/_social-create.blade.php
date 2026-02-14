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

    @media (max-width: 768px) {
        .vw-social-create-body { flex-direction: column; }
        .vw-social-preview-panel { width: 100%; height: 40vh; }
        .vw-social-workflow-panel { width: 100%; border-left: none; border-top: 1px solid rgba(100,100,140,0.15); }
    }
</style>

<script>
window.socialContentPolling = function() {
    return {
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
        },
    };
};
</script>

<div class="vw-social-create" x-data="socialContentPolling()" x-init="initPolling()">
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
                    @if(!empty($selectedIdea['audioType']))
                        <br><span style="color: #94a3b8; font-size: 0.75rem;">
                            Audio: {{ $selectedIdea['audioType'] === 'music-lipsync' ? 'Music Lip-Sync' : 'Voiceover' }}
                        </span>
                    @endif
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

            {{-- Section 2: Audio --}}
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

            {{-- Section 3: Animate --}}
            <div class="vw-social-section {{ ($videoStatus === 'ready') ? 'completed' : '' }}">
                <div class="vw-social-section-header">
                    <div class="vw-social-section-num">
                        @if($videoStatus === 'ready') &#10003; @else 3 @endif
                    </div>
                    <div>
                        <div class="vw-social-section-title">{{ __('Animate with Lip-Sync') }}</div>
                        <div class="vw-social-section-subtitle">{{ __('InfiniteTalk brings your character to life') }}</div>
                    </div>
                    @if($videoStatus !== 'pending')
                        <span class="vw-social-status-badge {{ $videoStatus }}">{{ ucfirst($videoStatus) }}</span>
                    @endif
                </div>

                <button class="vw-social-action-btn orange"
                        wire:click="generateShotVideo(0, 0)"
                        wire:loading.attr="disabled"
                        wire:target="generateShotVideo"
                        @if($imageStatus !== 'ready' || $audioStatus !== 'ready') disabled title="{{ __('Image and audio required') }}"
                        @elseif(in_array($videoStatus, ['generating', 'processing'])) disabled
                        @endif>
                    @if(in_array($videoStatus, ['generating', 'processing']))
                        <span>
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Rendering video...') }}
                        </span>
                    @else
                        <span wire:loading.remove wire:target="generateShotVideo">
                            <i class="fa-solid fa-film"></i>
                            {{ ($videoStatus === 'ready') ? __('Re-Animate') : __('Animate with Lip-Sync') }}
                        </span>
                        <span wire:loading wire:target="generateShotVideo">
                            <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Submitting...') }}
                        </span>
                    @endif
                </button>

                @if(in_array($videoStatus, ['generating', 'processing']))
                    <div class="vw-social-progress-bar">
                        <div class="vw-social-progress-text">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            {{ __('AI is animating your character...') }}
                        </div>
                        <div class="vw-social-progress-track">
                            <div class="vw-social-progress-fill"></div>
                        </div>
                        <div class="vw-social-progress-hint">{{ __('This usually takes 2-5 minutes') }}</div>
                    </div>
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
        </div>
    </div>
</div>
