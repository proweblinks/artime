{{-- Multi-Shot Decomposition Modal - Full Screen Split Panel Layout --}}

{{-- Video polling Alpine component --}}
<script>
window.multiShotVideoPolling = function() {
    return {
        pollingInterval: null,
        isPolling: false,
        pollCount: 0,
        maxPolls: 120,
        POLL_INTERVAL: 5000,
        componentDestroyed: false,

        initPolling() {
            this.componentDestroyed = false;
            this.$nextTick(() => {
                if (this.checkForProcessingVideos()) this.startPolling();
            });
            this.videoStartedListener = Livewire.on('video-generation-started', () => {
                if (!this.componentDestroyed) this.startPolling();
            });
            this.videoCompleteListener = Livewire.on('video-generation-complete', () => this.stopPolling());
            this.modalCloseListener = Livewire.on('multi-shot-modal-closing', () => this.cleanup());
        },
        checkForProcessingVideos() {
            const hasRendering = document.body.innerText.includes('Rendering...') || document.body.innerText.includes('Starting...');
            const processingShots = document.querySelectorAll('[data-video-status="processing"], [data-video-status="generating"]');
            return hasRendering || processingShots.length > 0;
        },
        dispatchPoll() {
            if (this.componentDestroyed || this.pollCount >= this.maxPolls) { this.stopPolling(); return; }
            this.pollCount++;
            if (this.$wire) {
                this.$wire.pollVideoJobs().then((r) => { if (r && r.pendingJobs === 0) this.stopPolling(); }).catch(() => this.stopPolling());
            } else { this.stopPolling(); }
        },
        startPolling() {
            if (this.isPolling || this.componentDestroyed) return;
            this.isPolling = true; this.pollCount = 0;
            setTimeout(() => { if (!this.componentDestroyed) this.dispatchPoll(); }, 1000);
            this.pollingInterval = setInterval(() => { if (!this.componentDestroyed) this.dispatchPoll(); else this.stopPolling(); }, this.POLL_INTERVAL);
        },
        stopPolling() {
            if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
            this.isPolling = false;
        },
        cleanup() {
            this.componentDestroyed = true; this.stopPolling();
            if (this.videoStartedListener) { this.videoStartedListener(); this.videoStartedListener = null; }
            if (this.videoCompleteListener) { this.videoCompleteListener(); this.videoCompleteListener = null; }
            if (this.modalCloseListener) { this.modalCloseListener(); this.modalCloseListener = null; }
        },
        destroy() { this.cleanup(); }
    };
};
</script>

@if($showMultiShotModal)
@php
    $scene = $script['scenes'][$multiShotSceneIndex] ?? null;
    $decomposed = $multiShotMode['decomposedScenes'][$multiShotSceneIndex] ?? null;
    $storyboardScene = $storyboard['scenes'][$multiShotSceneIndex] ?? null;
    $collage = $sceneCollages[$multiShotSceneIndex] ?? null;
    $currentPage = $collage['currentPage'] ?? 0;
    $totalPages = $collage['totalPages'] ?? 1;
    $currentCollage = $collage['collages'][$currentPage] ?? null;
    $currentShots = $currentCollage['shots'] ?? [];
    $totalDuration = 0;
    if ($decomposed) {
        foreach ($decomposed['shots'] as $shot) {
            $totalDuration += $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
        }
    }
@endphp

<div class="msm-fullscreen"
     wire:key="multi-shot-modal-{{ $multiShotSceneIndex }}"
     x-data="multiShotVideoPolling()"
     x-init="initPolling()"
     @destroy="cleanup()">

    {{-- Header --}}
    <header class="msm-header">
        <div class="msm-header-left">
            <h2>✂️ {{ __('Multi-Shot Decomposition') }}</h2>
            <span class="msm-scene-badge">{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</span>
            @if($decomposed)
                @php
                    $imagesReady = collect($decomposed['shots'])->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->count();
                    $videosReady = collect($decomposed['shots'])->filter(fn($s) => ($s['videoStatus'] ?? '') === 'ready' && !empty($s['videoUrl']))->count();
                @endphp
                <div class="msm-stats">
                    <span>📽️ {{ count($decomposed['shots']) }} {{ __('shots') }}</span>
                    <span>• {{ $totalDuration }}s</span>
                    <span class="msm-stat-green">🖼️ {{ $imagesReady }}/{{ count($decomposed['shots']) }}</span>
                    <span class="msm-stat-cyan">🎬 {{ $videosReady }}/{{ count($decomposed['shots']) }}</span>
                </div>
            @endif
        </div>
        <button type="button" wire:click="closeMultiShotModal" class="msm-close-btn">&times;</button>
    </header>

    @if($scene)
        @if(!$decomposed)
            {{-- PRE-DECOMPOSITION VIEW --}}
            <main class="msm-pre-decompose">
                <div class="msm-pre-decompose-content">
                    <div class="msm-scene-preview">
                        @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                            <img src="{{ $storyboardScene['imageUrl'] }}" alt="Scene">
                        @else
                            <div class="msm-scene-placeholder">🎬</div>
                        @endif
                        <div class="msm-scene-info">
                            <h3>{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</h3>
                            <p>{{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 200) }}</p>
                        </div>
                    </div>

                    <div class="msm-shot-selector">
                        <label>{{ __('Number of Shots') }}</label>
                        <div class="msm-shot-buttons">
                            <button type="button" wire:click="$set('multiShotCount', 0)" class="{{ $multiShotCount === 0 ? 'active ai' : '' }}">🤖 {{ __('AI') }}</button>
                            @foreach([2, 3, 4, 5, 6, 8, 10] as $count)
                                <button type="button" wire:click="$set('multiShotCount', {{ $count }})" class="{{ $multiShotCount === $count ? 'active' : '' }}">{{ $count }}</button>
                            @endforeach
                        </div>
                        <p class="msm-hint">
                            @if($multiShotCount === 0)
                                🤖 {{ __('AI will analyze the scene and determine optimal shot count') }}
                            @else
                                💡 {{ __('Manual: :count shots', ['count' => $multiShotCount]) }}
                            @endif
                        </p>
                    </div>

                    <button type="button" wire:click="decomposeScene({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="decomposeScene" class="msm-decompose-btn">
                        <span wire:loading.remove wire:target="decomposeScene">✂️ {{ __('Decompose Scene') }}</span>
                        <span wire:loading wire:target="decomposeScene">⏳ {{ __('Decomposing...') }}</span>
                    </button>
                </div>
            </main>
        @else
            {{-- DECOMPOSED VIEW - Split Panel --}}
            <main class="msm-split-panel">
                {{-- LEFT: Collage Panel --}}
                <aside class="msm-collage-panel">
                    <div class="msm-panel-header">
                        <div>
                            <h3>🖼️ {{ __('Collage Preview') }}</h3>
                            <p>{{ __('Click region, then assign to shot') }}</p>
                        </div>
                        <button type="button" wire:click="generateCollagePreview({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="generateCollagePreview" class="msm-gen-btn">
                            <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate') }}</span>
                            <span wire:loading wire:target="generateCollagePreview">⏳</span>
                        </button>
                    </div>

                    <div class="msm-collage-content">
                        @if($collage && in_array($collage['status'], ['ready', 'generating', 'processing']))
                            @if($collage['status'] !== 'ready')
                                <div class="msm-collage-loading">
                                    <div class="msm-spinner pink"></div>
                                    <span>{{ __('Generating collage...') }}</span>
                                </div>
                            @else
                                {{-- Pagination --}}
                                @if($totalPages > 1)
                                    <div class="msm-pagination">
                                        <span>{{ __('Page :current/:total', ['current' => $currentPage + 1, 'total' => $totalPages]) }} ({{ __('Shots') }} {{ min($currentShots) + 1 }}-{{ max($currentShots) + 1 }})</span>
                                        <div class="msm-page-btns">
                                            <button wire:click="prevCollagePage({{ $multiShotSceneIndex }})" {{ $currentPage <= 0 ? 'disabled' : '' }}>◀</button>
                                            @for($p = 0; $p < $totalPages; $p++)
                                                <button wire:click="setCollagePage({{ $multiShotSceneIndex }}, {{ $p }})" class="{{ $p === $currentPage ? 'active' : '' }}">{{ $p + 1 }}</button>
                                            @endfor
                                            <button wire:click="nextCollagePage({{ $multiShotSceneIndex }})" {{ $currentPage >= $totalPages - 1 ? 'disabled' : '' }}>▶</button>
                                        </div>
                                    </div>
                                @endif

                                {{-- Collage Image --}}
                                <div class="msm-collage-image" x-data="{ selectedRegion: null }">
                                    @if(!empty($currentCollage['previewUrl']))
                                        <img src="{{ $currentCollage['previewUrl'] }}" alt="Collage">
                                    @else
                                        <div class="msm-no-preview">{{ __('No preview') }}</div>
                                    @endif
                                    <div class="msm-quadrant-overlay">
                                        @for($r = 0; $r < 4; $r++)
                                            @php $assigned = ($currentCollage['regions'][$r]['assignedToShot'] ?? null); @endphp
                                            <div class="msm-quadrant"
                                                 x-on:click="selectedRegion = {{ $r }}; $dispatch('region-selected', { regionIndex: {{ $r }}, pageIndex: {{ $currentPage }} })"
                                                 x-bind:class="selectedRegion === {{ $r }} ? 'selected' : ''">
                                                <span class="msm-q-num">{{ ($currentShots[$r] ?? $r) + 1 }}</span>
                                                @if($assigned !== null)
                                                    <span class="msm-q-assigned">→ {{ __('Shot') }} {{ $assigned + 1 }}</span>
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                {{-- Assignment Buttons --}}
                                <div class="msm-assign-section" x-data="{ selReg: null, selPage: {{ $currentPage }} }" x-on:region-selected.window="selReg = $event.detail.regionIndex; selPage = $event.detail.pageIndex">
                                    <label>{{ __('Assign region to:') }}</label>
                                    <div class="msm-assign-btns">
                                        @foreach($decomposed['shots'] as $sIdx => $sData)
                                            @php
                                                $src = $collage['shotSources'][$sIdx] ?? null;
                                                $hasReg = $src !== null;
                                            @endphp
                                            <button x-on:click="if (selReg !== null) $wire.assignCollageRegionToShot({{ $multiShotSceneIndex }}, selPage, selReg, {{ $sIdx }})"
                                                    x-bind:disabled="selReg === null"
                                                    class="{{ $hasReg ? 'assigned' : '' }}">
                                                {{ __('Shot') }} {{ $sIdx + 1 }}
                                                @if($hasReg)<small>(P{{ $src['pageIndex'] + 1 }}R{{ $src['regionIndex'] + 1 }})</small>@endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <button type="button" wire:click="clearCollagePreview({{ $multiShotSceneIndex }})" class="msm-clear-btn">✕ {{ __('Clear') }}</button>
                            @endif
                        @else
                            {{-- Empty State --}}
                            <div class="msm-collage-empty">
                                <span>🖼️</span>
                                <p>{{ __('Generate a 2x2 collage to quickly assign visuals to shots') }}</p>
                                <button type="button" wire:click="generateCollagePreview({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="generateCollagePreview">
                                    <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate Collage') }}</span>
                                    <span wire:loading wire:target="generateCollagePreview">⏳</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </aside>

                {{-- RIGHT: Shots Panel --}}
                <section class="msm-shots-panel">
                    {{-- Action Bar --}}
                    <div class="msm-action-bar">
                        <button wire:click="generateAllShots({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" class="msm-action-btn purple">🎨 {{ __('Generate All Images') }}</button>
                        <button wire:click="generateAllShotVideos({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" class="msm-action-btn cyan">🎬 {{ __('Animate All Shots') }}</button>
                        <span class="msm-spacer"></span>
                        <button wire:click="resetDecomposition({{ $multiShotSceneIndex }})" class="msm-reset-btn">🗑️ {{ __('Reset') }}</button>
                    </div>

                    {{-- Timeline --}}
                    <div class="msm-timeline">
                        @foreach($decomposed['shots'] as $idx => $shot)
                            @php
                                $dur = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                $pct = $totalDuration > 0 ? ($dur / $totalDuration * 100) : (100 / count($decomposed['shots']));
                                $hasImg = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                $hasVid = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                            @endphp
                            <div class="msm-timeline-seg {{ $hasVid ? 'vid' : ($hasImg ? 'img' : '') }}" style="width: {{ $pct }}%;" wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $idx }})" title="Shot {{ $idx + 1 }}: {{ $dur }}s">{{ $idx + 1 }}</div>
                        @endforeach
                    </div>

                    {{-- Shot Grid --}}
                    <div class="msm-shot-grid">
                        @foreach($decomposed['shots'] as $shotIndex => $shot)
                            @php
                                $hasImage = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                $hasVideo = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                                $isGenImg = ($shot['status'] ?? '') === 'generating';
                                $isGenVid = in_array($shot['videoStatus'] ?? '', ['generating', 'processing']);
                                $shotDur = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                $collageSrc = $collage['shotSources'][$shotIndex] ?? null;
                                $hasColReg = $collageSrc !== null;
                                $hasDialogue = !empty($shot['dialogue']);
                                $durColor = $shotDur <= 5 ? 'green' : ($shotDur <= 6 ? 'yellow' : 'blue');
                            @endphp

                            <div class="msm-shot-card {{ $hasVideo ? 'has-video' : '' }}" data-video-status="{{ $shot['videoStatus'] ?? 'pending' }}" data-shot-index="{{ $shotIndex }}">
                                <div class="msm-shot-header">
                                    <span class="msm-shot-num">{{ $shotIndex + 1 }}</span>
                                    <span class="msm-shot-type">{{ ucfirst(str_replace('_', ' ', $shot['type'] ?? 'shot')) }}</span>
                                    <div class="msm-shot-meta">
                                        @if($hasDialogue)<span class="msm-badge-dialog">💬</span>@endif
                                        <span class="msm-dur {{ $durColor }}">{{ $shotDur }}s</span>
                                    </div>
                                </div>

                                <div class="msm-shot-preview" wire:click="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})">
                                    @if($hasImage)
                                        <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotIndex + 1 }}">
                                        <div class="msm-shot-overlay"><span>{{ $hasVideo ? '▶️' : '🔍' }}</span></div>
                                        <div class="msm-shot-icons">
                                            <span class="msm-icon-img">🖼</span>
                                            @if($hasVideo)<span class="msm-icon-vid">🎬</span>@endif
                                        </div>
                                    @elseif($isGenImg)
                                        <div class="msm-spinner purple"></div>
                                        <span>{{ __('Generating...') }}</span>
                                    @elseif($isGenVid && $hasImage)
                                        <img src="{{ $shot['imageUrl'] }}" alt="" class="dimmed">
                                        <div class="msm-vid-progress">
                                            <div class="msm-spinner cyan"></div>
                                            <span>🎬 {{ __('Animating...') }}</span>
                                        </div>
                                    @else
                                        <div class="msm-shot-empty">
                                            <span>🖼️</span>
                                            @if($hasColReg)
                                                <button wire:click.stop="generateShotFromCollageRegion({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-collage-btn">🖼️ P{{ $collageSrc['pageIndex'] + 1 }}R{{ $collageSrc['regionIndex'] + 1 }}</button>
                                            @else
                                                <button wire:click.stop="generateShotImage({{ $multiShotSceneIndex }}, {{ $shotIndex }})">{{ __('Generate') }}</button>
                                            @endif
                                        </div>
                                    @endif
                                    @if($hasColReg)
                                        <span class="msm-collage-badge">🖼️ P{{ $collageSrc['pageIndex'] + 1 }}R{{ $collageSrc['regionIndex'] + 1 }}</span>
                                    @endif
                                </div>

                                <div class="msm-shot-controls">
                                    @php $durations = $this->getAvailableDurations($shot['selectedVideoModel'] ?? 'minimax'); @endphp
                                    <div class="msm-dur-btns">
                                        @foreach($durations as $d)
                                            <button wire:click.stop="setShotDuration({{ $multiShotSceneIndex }}, {{ $shotIndex }}, {{ $d }})" class="{{ $shotDur === $d ? 'active ' . ($d <= 5 ? 'green' : ($d <= 6 ? 'yellow' : 'blue')) : '' }}">{{ $d }}s</button>
                                        @endforeach
                                    </div>

                                    @if($hasVideo)
                                        <div class="msm-action-row">
                                            <button wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-play-btn">▶️ {{ __('Play') }}</button>
                                            @if($shotIndex < count($decomposed['shots']) - 1)
                                                <button wire:click.stop="openFrameCaptureModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-capture-btn">🎯→{{ $shotIndex + 2 }}</button>
                                            @endif
                                        </div>
                                    @elseif($hasImage && !$isGenVid)
                                        <button wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-animate-btn">🎬 {{ __('Animate') }}</button>
                                    @elseif($isGenVid)
                                        <div class="msm-render-status">
                                            <span>⏳ {{ __('Rendering...') }}</span>
                                            <div class="msm-progress-bar"><div></div></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </main>
        @endif
    @endif
</div>
@endif

{{-- Video Model Selector --}}
@if($showVideoModelSelector ?? false)
<div class="msm-popup-overlay" wire:click.self="closeVideoModelSelector">
    <div class="msm-popup">
        <div class="msm-popup-header">
            <div>
                <h4>🎬 {{ __('Animation Model') }}</h4>
                <p>{{ __('Shot') }} {{ ($videoModelSelectorShotIndex ?? 0) + 1 }}</p>
            </div>
            <button wire:click="closeVideoModelSelector">&times;</button>
        </div>
        <div class="msm-popup-body">
            @php
                $selShot = $multiShotMode['decomposedScenes'][$videoModelSelectorSceneIndex ?? 0]['shots'][$videoModelSelectorShotIndex ?? 0] ?? [];
                $curModel = $selShot['selectedVideoModel'] ?? 'minimax';
                $curDur = $selShot['selectedDuration'] ?? $selShot['duration'] ?? 6;
                $mtAvail = !empty(get_option('runpod_multitalk_endpoint', ''));
            @endphp

            <div class="msm-model-opt {{ $curModel === 'minimax' ? 'active' : '' }}" wire:click="setVideoModel('minimax')">
                <div class="msm-radio {{ $curModel === 'minimax' ? 'checked' : '' }}"></div>
                <div><strong>MiniMax</strong><span>{{ __('High quality I2V') }}</span></div>
                <span class="msm-rec">{{ __('Recommended') }}</span>
            </div>

            <div class="msm-model-opt {{ $curModel === 'multitalk' ? 'active' : '' }} {{ !$mtAvail ? 'disabled' : '' }}" @if($mtAvail) wire:click="setVideoModel('multitalk')" @endif>
                <div class="msm-radio {{ $curModel === 'multitalk' ? 'checked' : '' }}"></div>
                <div><strong>Multitalk</strong><span>{{ __('Lip-sync for dialogue') }}</span></div>
                @if(!$mtAvail)<span class="msm-warn">⚠️</span>@endif
            </div>

            <div class="msm-dur-selector">
                <label>{{ __('Duration') }}</label>
                <div class="msm-dur-opts">
                    @foreach($this->getAvailableDurations($curModel) as $d)
                        <button wire:click="setVideoModelDuration({{ $d }})" class="{{ $curDur == $d ? 'active' : '' }}">{{ $d }}s</button>
                    @endforeach
                </div>
            </div>

            <button wire:click="confirmVideoModelAndGenerate" wire:loading.attr="disabled" class="msm-gen-anim-btn">
                <span wire:loading.remove>🎬 {{ __('Generate Animation') }}</span>
                <span wire:loading>⏳</span>
            </button>
        </div>
    </div>
</div>
@endif

<style>
/* Full Screen Container */
.msm-fullscreen {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    width: 100vw;
    height: 100vh;
    background: #0a0a0f;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
}

/* Header */
.msm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(180deg, rgba(30,30,45,0.95), rgba(20,20,30,0.9));
    border-bottom: 1px solid rgba(139,92,246,0.3);
    flex-shrink: 0;
}
.msm-header-left { display: flex; align-items: center; gap: 1rem; }
.msm-header h2 { margin: 0; color: #fff; font-size: 1.2rem; font-weight: 600; }
.msm-scene-badge { background: rgba(139,92,246,0.3); color: #a78bfa; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem; }
.msm-stats { display: flex; gap: 0.6rem; color: rgba(255,255,255,0.5); font-size: 0.8rem; }
.msm-stat-green { color: #10b981; }
.msm-stat-cyan { color: #06b6d4; }
.msm-close-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-size: 1.5rem; padding: 0.4rem 0.8rem; border-radius: 0.4rem; cursor: pointer; }
.msm-close-btn:hover { background: rgba(239,68,68,0.3); border-color: rgba(239,68,68,0.5); }

/* Pre-Decompose */
.msm-pre-decompose { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; }
.msm-pre-decompose-content { width: 100%; max-width: 550px; }
.msm-scene-preview { display: flex; gap: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; }
.msm-scene-preview img { width: 180px; height: 100px; object-fit: cover; border-radius: 0.5rem; }
.msm-scene-placeholder { width: 180px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
.msm-scene-info { flex: 1; }
.msm-scene-info h3 { margin: 0 0 0.4rem; color: #fff; font-size: 1rem; }
.msm-scene-info p { margin: 0; color: rgba(255,255,255,0.6); font-size: 0.85rem; line-height: 1.4; }
.msm-shot-selector { margin-bottom: 1.5rem; }
.msm-shot-selector label { display: block; color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 0.5rem; }
.msm-shot-buttons { display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.4rem; }
.msm-shot-buttons button { padding: 0.6rem; border-radius: 0.4rem; border: 2px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; font-weight: 600; }
.msm-shot-buttons button.active { border-color: rgba(139,92,246,0.6); background: rgba(139,92,246,0.25); }
.msm-shot-buttons button.ai.active { border-color: rgba(16,185,129,0.6); background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(6,182,212,0.25)); }
.msm-hint { color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-top: 0.5rem; }
.msm-decompose-btn { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }

/* Split Panel Layout */
.msm-split-panel {
    flex: 1;
    display: grid;
    grid-template-columns: minmax(320px, 420px) 1fr;
    overflow: hidden;
    min-height: 0;
}

/* Collage Panel */
.msm-collage-panel {
    display: flex;
    flex-direction: column;
    background: rgba(0,0,0,0.3);
    border-right: 1px solid rgba(255,255,255,0.1);
    overflow: hidden;
}
.msm-panel-header { padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
.msm-panel-header h3 { margin: 0; color: #fff; font-size: 0.95rem; }
.msm-panel-header p { margin: 0.15rem 0 0; color: rgba(255,255,255,0.5); font-size: 0.7rem; }
.msm-gen-btn { padding: 0.4rem 0.8rem; background: linear-gradient(135deg, rgba(236,72,153,0.3), rgba(139,92,246,0.3)); border: 1px solid rgba(236,72,153,0.5); border-radius: 0.4rem; color: #fff; font-size: 0.75rem; cursor: pointer; }

.msm-collage-content { flex: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; }
.msm-collage-loading { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 0.5rem; }
.msm-collage-loading span { color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-top: 0.75rem; }

.msm-pagination { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; background: rgba(236,72,153,0.1); border-radius: 0.35rem; }
.msm-pagination > span { color: rgba(255,255,255,0.7); font-size: 0.75rem; }
.msm-page-btns { display: flex; gap: 0.25rem; }
.msm-page-btns button { width: 26px; height: 26px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: #fff; font-size: 0.7rem; cursor: pointer; }
.msm-page-btns button.active { background: rgba(236,72,153,0.5); border-color: rgba(236,72,153,0.7); }
.msm-page-btns button:disabled { opacity: 0.4; cursor: not-allowed; }

.msm-collage-image { position: relative; aspect-ratio: 1; background: rgba(0,0,0,0.3); border-radius: 0.5rem; overflow: hidden; }
.msm-collage-image img { width: 100%; height: 100%; object-fit: contain; }
.msm-no-preview { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); }
.msm-quadrant-overlay { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
.msm-quadrant { position: relative; border: 2px solid rgba(255,255,255,0.25); cursor: pointer; transition: all 0.2s; }
.msm-quadrant:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.5); }
.msm-quadrant.selected { background: rgba(236,72,153,0.4); border-color: #ec4899; box-shadow: inset 0 0 0 2px rgba(236,72,153,0.5); }
.msm-q-num { position: absolute; top: 0.4rem; left: 0.4rem; background: rgba(0,0,0,0.85); color: #fff; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600; }
.msm-q-assigned { position: absolute; bottom: 0.4rem; right: 0.4rem; background: rgba(16,185,129,0.95); color: #fff; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600; }

.msm-assign-section label { display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; margin-bottom: 0.35rem; }
.msm-assign-btns { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.msm-assign-btns button { padding: 0.35rem 0.6rem; background: rgba(139,92,246,0.25); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.3rem; color: #fff; font-size: 0.75rem; cursor: pointer; }
.msm-assign-btns button.assigned { background: rgba(16,185,129,0.35); border-color: rgba(16,185,129,0.6); }
.msm-assign-btns button:disabled { opacity: 0.5; cursor: not-allowed; }
.msm-assign-btns button small { font-size: 0.6rem; opacity: 0.7; margin-left: 0.15rem; }

.msm-clear-btn { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.6); padding: 0.35rem 0.8rem; border-radius: 0.3rem; font-size: 0.7rem; cursor: pointer; align-self: center; }

.msm-collage-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border: 2px dashed rgba(236,72,153,0.3); border-radius: 0.5rem; }
.msm-collage-empty > span { font-size: 2.5rem; opacity: 0.5; margin-bottom: 0.5rem; }
.msm-collage-empty p { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin: 0 0 0.75rem; text-align: center; padding: 0 1rem; }
.msm-collage-empty button { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, rgba(236,72,153,0.4), rgba(139,92,246,0.4)); border: 1px solid rgba(236,72,153,0.6); border-radius: 0.4rem; color: #fff; font-size: 0.85rem; cursor: pointer; }

/* Shots Panel */
.msm-shots-panel { display: flex; flex-direction: column; background: rgba(0,0,0,0.15); overflow: hidden; }

.msm-action-bar { display: flex; gap: 0.6rem; padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); flex-wrap: wrap; align-items: center; }
.msm-action-btn { padding: 0.5rem 0.9rem; border-radius: 0.4rem; color: #fff; font-size: 0.8rem; cursor: pointer; font-weight: 500; }
.msm-action-btn.purple { background: rgba(139,92,246,0.25); border: 1px solid rgba(139,92,246,0.5); }
.msm-action-btn.cyan { background: rgba(6,182,212,0.25); border: 1px solid rgba(6,182,212,0.5); }
.msm-spacer { flex: 1; }
.msm-reset-btn { padding: 0.4rem 0.6rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.3rem; color: #ef4444; font-size: 0.75rem; cursor: pointer; }

.msm-timeline { display: flex; height: 28px; margin: 0 1rem; background: rgba(0,0,0,0.4); border-radius: 0.4rem; overflow: hidden; }
.msm-timeline-seg { display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.7rem; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); background: rgba(139,92,246,0.3); cursor: pointer; }
.msm-timeline-seg.img { background: rgba(16,185,129,0.5); }
.msm-timeline-seg.vid { background: rgba(6,182,212,0.6); }
.msm-timeline-seg:hover { filter: brightness(1.2); }

.msm-shot-grid { flex: 1; padding: 1rem; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; align-content: start; }

/* Shot Card */
.msm-shot-card { background: rgba(255,255,255,0.03); border: 2px solid rgba(255,255,255,0.1); border-radius: 0.6rem; overflow: hidden; }
.msm-shot-card.has-video { border-color: rgba(6,182,212,0.5); }

.msm-shot-header { display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.6rem; background: rgba(0,0,0,0.3); }
.msm-shot-num { background: rgba(139,92,246,0.5); color: #fff; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
.msm-shot-type { color: rgba(255,255,255,0.7); font-size: 0.7rem; }
.msm-shot-meta { margin-left: auto; display: flex; align-items: center; gap: 0.3rem; }
.msm-badge-dialog { background: rgba(251,191,36,0.4); color: #fbbf24; padding: 0.1rem 0.25rem; border-radius: 0.2rem; font-size: 0.6rem; }
.msm-dur { font-size: 0.75rem; font-weight: 500; }
.msm-dur.green { color: #22c55e; }
.msm-dur.yellow { color: #eab308; }
.msm-dur.blue { color: #3b82f6; }

.msm-shot-preview { position: relative; height: 110px; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-direction: column; }
.msm-shot-preview img { width: 100%; height: 100%; object-fit: cover; }
.msm-shot-preview img.dimmed { filter: brightness(0.4); }
.msm-shot-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
.msm-shot-preview:hover .msm-shot-overlay { opacity: 1; }
.msm-shot-overlay span { font-size: 1.5rem; }
.msm-shot-icons { position: absolute; bottom: 0.3rem; right: 0.3rem; display: flex; gap: 0.2rem; }
.msm-icon-img, .msm-icon-vid { width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; }
.msm-icon-img { background: rgba(16,185,129,0.9); }
.msm-icon-vid { background: rgba(6,182,212,0.9); }

.msm-shot-empty { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; }
.msm-shot-empty > span { font-size: 1.8rem; color: rgba(255,255,255,0.2); }
.msm-shot-empty button { padding: 0.3rem 0.6rem; background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.3rem; color: #fff; font-size: 0.7rem; cursor: pointer; }
.msm-collage-btn { background: linear-gradient(135deg, rgba(236,72,153,0.4), rgba(139,92,246,0.4)) !important; border-color: rgba(236,72,153,0.5) !important; }
.msm-collage-badge { position: absolute; top: 0.3rem; left: 0.3rem; background: rgba(236,72,153,0.9); color: #fff; padding: 0.15rem 0.35rem; border-radius: 0.2rem; font-size: 0.6rem; font-weight: 600; }

.msm-vid-progress { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.msm-vid-progress span { color: #67e8f9; font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem; }

.msm-shot-controls { padding: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; }
.msm-dur-btns { display: flex; gap: 0.2rem; }
.msm-dur-btns button { flex: 1; padding: 0.25rem; font-size: 0.65rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: #fff; cursor: pointer; }
.msm-dur-btns button.active { font-weight: 600; }
.msm-dur-btns button.active.green { background: rgba(34,197,94,0.35); border-color: rgba(34,197,94,0.6); }
.msm-dur-btns button.active.yellow { background: rgba(234,179,8,0.35); border-color: rgba(234,179,8,0.6); }
.msm-dur-btns button.active.blue { background: rgba(59,130,246,0.35); border-color: rgba(59,130,246,0.6); }

.msm-action-row { display: flex; gap: 0.3rem; }
.msm-play-btn { flex: 1; padding: 0.35rem; background: linear-gradient(135deg, rgba(16,185,129,0.3), rgba(6,182,212,0.3)); border: 1px solid rgba(16,185,129,0.5); border-radius: 0.3rem; color: #fff; font-size: 0.7rem; cursor: pointer; }
.msm-capture-btn { padding: 0.35rem 0.5rem; background: rgba(16,185,129,0.25); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.3rem; color: #fff; font-size: 0.65rem; cursor: pointer; }
.msm-animate-btn { width: 100%; padding: 0.4rem; background: linear-gradient(135deg, rgba(6,182,212,0.3), rgba(59,130,246,0.3)); border: 1px solid rgba(6,182,212,0.5); border-radius: 0.3rem; color: #fff; font-size: 0.75rem; cursor: pointer; }

.msm-render-status { text-align: center; padding: 0.2rem; }
.msm-render-status span { font-size: 0.7rem; color: #67e8f9; }
.msm-progress-bar { height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-top: 0.3rem; }
.msm-progress-bar div { height: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6); animation: msm-progress 1.5s infinite linear; }

/* Spinner */
.msm-spinner { width: 32px; height: 32px; border: 3px solid rgba(255,255,255,0.2); border-radius: 50%; animation: msm-spin 1s linear infinite; }
.msm-spinner.pink { border-top-color: #ec4899; }
.msm-spinner.purple { border-top-color: #8b5cf6; width: 28px; height: 28px; }
.msm-spinner.cyan { border-top-color: #06b6d4; width: 36px; height: 36px; }

/* Popup */
.msm-popup-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.msm-popup { background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.6rem; width: 340px; max-width: 95vw; }
.msm-popup-header { display: flex; justify-content: space-between; align-items: center; padding: 0.9rem 1rem; background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(59,130,246,0.2)); border-bottom: 1px solid rgba(255,255,255,0.1); }
.msm-popup-header h4 { margin: 0; color: #fff; font-size: 1rem; }
.msm-popup-header p { margin: 0.1rem 0 0; color: rgba(255,255,255,0.6); font-size: 0.75rem; }
.msm-popup-header button { background: none; border: none; color: #fff; font-size: 1.4rem; cursor: pointer; }
.msm-popup-body { padding: 1rem; }

.msm-model-opt { display: flex; align-items: center; gap: 0.6rem; padding: 0.8rem; background: rgba(255,255,255,0.03); border: 2px solid rgba(255,255,255,0.1); border-radius: 0.4rem; margin-bottom: 0.6rem; cursor: pointer; }
.msm-model-opt.active { background: rgba(6,182,212,0.15); border-color: rgba(6,182,212,0.5); }
.msm-model-opt.disabled { opacity: 0.5; cursor: not-allowed; }
.msm-radio { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; }
.msm-radio.checked { border-color: #06b6d4; background: radial-gradient(#06b6d4 40%, transparent 45%); }
.msm-model-opt div strong { display: block; color: #fff; font-size: 0.9rem; }
.msm-model-opt div span { color: rgba(255,255,255,0.6); font-size: 0.75rem; }
.msm-rec { margin-left: auto; background: rgba(16,185,129,0.2); color: #10b981; padding: 0.15rem 0.4rem; border-radius: 0.2rem; font-size: 0.65rem; }
.msm-warn { color: #ef4444; }

.msm-dur-selector { margin: 1rem 0; }
.msm-dur-selector label { display: block; color: rgba(255,255,255,0.8); font-size: 0.8rem; margin-bottom: 0.4rem; }
.msm-dur-opts { display: flex; gap: 0.35rem; }
.msm-dur-opts button { flex: 1; padding: 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: #fff; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
.msm-dur-opts button.active { background: rgba(59,130,246,0.35); border-color: rgba(59,130,246,0.6); }

.msm-gen-anim-btn { width: 100%; padding: 0.8rem; background: linear-gradient(135deg, #06b6d4, #3b82f6); border: none; border-radius: 0.4rem; color: #fff; font-size: 0.95rem; font-weight: 600; cursor: pointer; }

@keyframes msm-spin { to { transform: rotate(360deg); } }
@keyframes msm-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
</style>
