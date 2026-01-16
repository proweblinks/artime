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
            {{-- DECOMPOSED VIEW - Split Panel with Resizable Divider --}}
            <main class="msm-split-panel"
                  x-data="{
                      collageWidth: localStorage.getItem('msm-collage-width') || 380,
                      isDragging: false,
                      startX: 0,
                      startWidth: 0,
                      minWidth: 280,
                      maxWidth: 600,
                      startDrag(e) {
                          this.isDragging = true;
                          this.startX = e.clientX || e.touches?.[0]?.clientX || 0;
                          this.startWidth = parseInt(this.collageWidth);
                          document.body.style.cursor = 'col-resize';
                          document.body.style.userSelect = 'none';
                      },
                      onDrag(e) {
                          if (!this.isDragging) return;
                          const clientX = e.clientX || e.touches?.[0]?.clientX || 0;
                          const diff = clientX - this.startX;
                          let newWidth = this.startWidth + diff;
                          newWidth = Math.max(this.minWidth, Math.min(this.maxWidth, newWidth));
                          this.collageWidth = newWidth;
                      },
                      endDrag() {
                          if (!this.isDragging) return;
                          this.isDragging = false;
                          document.body.style.cursor = '';
                          document.body.style.userSelect = '';
                          localStorage.setItem('msm-collage-width', this.collageWidth);
                      }
                  }"
                  x-on:mousemove.window="onDrag($event)"
                  x-on:mouseup.window="endDrag()"
                  x-on:touchmove.window="onDrag($event)"
                  x-on:touchend.window="endDrag()"
                  x-bind:style="'grid-template-columns: ' + collageWidth + 'px auto 1fr'">
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

                {{-- RESIZE HANDLE --}}
                <div class="msm-resize-handle"
                     x-on:mousedown.prevent="startDrag($event)"
                     x-on:touchstart.prevent="startDrag($event)"
                     x-bind:class="isDragging ? 'dragging' : ''">
                    <div class="msm-resize-grip"></div>
                </div>

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
/* Full Screen Container - Must cover entire viewport including SAAS shell */
.msm-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #0a0a12 0%, #12121f 30%, #1a1a2e 50%, #12121f 70%, #0a0a12 100%);
    display: flex !important;
    flex-direction: column;
    z-index: 2147483647 !important; /* Maximum z-index to cover everything */
    overflow: hidden !important;
    isolation: isolate;
    transform: translateZ(0); /* Force GPU layer */
    contain: layout style paint;
    box-sizing: border-box !important;
}

/* Header - Glassmorphism Style */
.msm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.75rem;
    background: linear-gradient(180deg, rgba(30,30,50,0.85), rgba(20,20,35,0.75));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(139,92,246,0.25);
    box-shadow: 0 4px 30px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
    flex-shrink: 0;
}
.msm-header-left { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
.msm-header h2 { margin: 0; color: #fff; font-size: 1.3rem; font-weight: 700; letter-spacing: -0.02em; text-shadow: 0 2px 10px rgba(139,92,246,0.3); }
.msm-scene-badge { background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(168,85,247,0.3)); color: #c4b5fd; padding: 0.35rem 0.9rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(139,92,246,0.4); box-shadow: 0 2px 10px rgba(139,92,246,0.2); }
.msm-stats { display: flex; gap: 0.8rem; color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 500; }
.msm-stat-green { color: #34d399; text-shadow: 0 0 10px rgba(16,185,129,0.4); }
.msm-stat-cyan { color: #22d3ee; text-shadow: 0 0 10px rgba(6,182,212,0.4); }
.msm-close-btn { background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05)); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 1.6rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 12px; cursor: pointer; transition: all 0.2s ease; }
.msm-close-btn:hover { background: linear-gradient(135deg, rgba(239,68,68,0.4), rgba(220,38,38,0.3)); border-color: rgba(239,68,68,0.5); transform: scale(1.05); box-shadow: 0 4px 20px rgba(239,68,68,0.3); }

/* Pre-Decompose - Modern Centered Card */
.msm-pre-decompose { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2.5rem; background: radial-gradient(ellipse at center, rgba(139,92,246,0.08) 0%, transparent 70%); }
.msm-pre-decompose-content { width: 100%; max-width: 580px; }
.msm-scene-preview { display: flex; gap: 1.25rem; background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 1.25rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.msm-scene-preview img { width: 200px; height: 112px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
.msm-scene-placeholder { width: 200px; height: 112px; background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; border: 1px dashed rgba(255,255,255,0.15); }
.msm-scene-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
.msm-scene-info h3 { margin: 0 0 0.5rem; color: #fff; font-size: 1.1rem; font-weight: 600; }
.msm-scene-info p { margin: 0; color: rgba(255,255,255,0.6); font-size: 0.9rem; line-height: 1.5; }
.msm-shot-selector { margin-bottom: 2rem; }
.msm-shot-selector label { display: block; color: rgba(255,255,255,0.8); font-size: 0.95rem; font-weight: 500; margin-bottom: 0.75rem; }
.msm-shot-buttons { display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.5rem; }
.msm-shot-buttons button { padding: 0.75rem 0.5rem; border-radius: 10px; border: 2px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.8); cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s ease; }
.msm-shot-buttons button:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
.msm-shot-buttons button.active { border-color: rgba(139,92,246,0.6); background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(168,85,247,0.2)); color: #fff; box-shadow: 0 4px 15px rgba(139,92,246,0.25); }
.msm-shot-buttons button.ai.active { border-color: rgba(16,185,129,0.6); background: linear-gradient(135deg, rgba(16,185,129,0.3), rgba(6,182,212,0.25)); box-shadow: 0 4px 15px rgba(16,185,129,0.25); }
.msm-hint { color: rgba(255,255,255,0.55); font-size: 0.85rem; margin-top: 0.75rem; font-weight: 500; }
.msm-decompose-btn { width: 100%; padding: 1.1rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 12px; color: #fff; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 6px 25px rgba(139,92,246,0.35); }
.msm-decompose-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(139,92,246,0.45); }
.msm-decompose-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

/* Split Panel Layout */
.msm-split-panel {
    flex: 1;
    display: grid;
    grid-template-columns: 380px auto 1fr; /* Default: collage panel, resize handle, shots panel */
    overflow: hidden;
    min-height: 0;
    gap: 0;
}

/* Resize Handle */
.msm-resize-handle {
    width: 8px;
    background: linear-gradient(180deg, rgba(139,92,246,0.15), rgba(6,182,212,0.15));
    cursor: col-resize;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
    position: relative;
    z-index: 10;
}
.msm-resize-handle:hover,
.msm-resize-handle.dragging {
    background: linear-gradient(180deg, rgba(139,92,246,0.4), rgba(6,182,212,0.4));
}
.msm-resize-grip {
    width: 4px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
    transition: all 0.2s ease;
}
.msm-resize-handle:hover .msm-resize-grip,
.msm-resize-handle.dragging .msm-resize-grip {
    background: rgba(255,255,255,0.5);
    height: 60px;
}

/* Collage Panel - Left Sidebar */
.msm-collage-panel {
    display: flex;
    flex-direction: column;
    background: linear-gradient(180deg, rgba(15,15,25,0.95), rgba(10,10,18,0.98));
    border-right: none;
    overflow: hidden;
    box-shadow: 4px 0 30px rgba(0,0,0,0.4);
    min-width: 0; /* Allow shrinking */
}
.msm-panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent); }
.msm-panel-header h3 { margin: 0; color: #fff; font-size: 1rem; font-weight: 600; }
.msm-panel-header p { margin: 0.2rem 0 0; color: rgba(255,255,255,0.45); font-size: 0.75rem; }
.msm-gen-btn { padding: 0.5rem 1rem; background: linear-gradient(135deg, rgba(236,72,153,0.35), rgba(139,92,246,0.35)); border: 1px solid rgba(236,72,153,0.5); border-radius: 8px; color: #fff; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 10px rgba(236,72,153,0.2); }
.msm-gen-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(236,72,153,0.35); }

.msm-collage-content { flex: 1; padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
.msm-collage-loading { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(236,72,153,0.05), rgba(139,92,246,0.05)); border: 2px dashed rgba(236,72,153,0.3); border-radius: 12px; }
.msm-collage-loading span { color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-top: 1rem; font-weight: 500; }

.msm-pagination { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.8rem; background: linear-gradient(135deg, rgba(236,72,153,0.12), rgba(139,92,246,0.08)); border: 1px solid rgba(236,72,153,0.25); border-radius: 10px; }
.msm-pagination > span { color: rgba(255,255,255,0.75); font-size: 0.8rem; font-weight: 500; }
.msm-page-btns { display: flex; gap: 0.35rem; }
.msm-page-btns button { width: 30px; height: 30px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.08); color: #fff; font-size: 0.75rem; cursor: pointer; transition: all 0.2s ease; }
.msm-page-btns button.active { background: linear-gradient(135deg, rgba(236,72,153,0.6), rgba(168,85,247,0.5)); border-color: rgba(236,72,153,0.7); box-shadow: 0 2px 10px rgba(236,72,153,0.3); }
.msm-page-btns button:hover:not(:disabled) { background: rgba(255,255,255,0.15); transform: translateY(-1px); }
.msm-page-btns button:disabled { opacity: 0.35; cursor: not-allowed; }

.msm-collage-image { position: relative; aspect-ratio: 1; background: linear-gradient(135deg, rgba(0,0,0,0.4), rgba(10,10,20,0.5)); border-radius: 12px; overflow: hidden; border: 2px solid rgba(255,255,255,0.08); box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05); }
.msm-collage-image img { width: 100%; height: 100%; object-fit: contain; }
.msm-no-preview { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.35); font-size: 0.9rem; }
.msm-quadrant-overlay { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: 2px; }
.msm-quadrant { position: relative; border: 2px solid rgba(255,255,255,0.2); cursor: pointer; transition: all 0.25s ease; backdrop-filter: blur(0px); }
.msm-quadrant:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.5); backdrop-filter: blur(2px); }
.msm-quadrant.selected { background: linear-gradient(135deg, rgba(236,72,153,0.45), rgba(168,85,247,0.35)); border-color: #ec4899; box-shadow: inset 0 0 0 3px rgba(236,72,153,0.4), 0 0 20px rgba(236,72,153,0.3); }
.msm-q-num { position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(0,0,0,0.9); color: #fff; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.4); }
.msm-q-assigned { position: absolute; bottom: 0.5rem; right: 0.5rem; background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; box-shadow: 0 2px 8px rgba(16,185,129,0.4); }

.msm-assign-section { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.75rem; }
.msm-assign-section label { display: block; color: rgba(255,255,255,0.65); font-size: 0.8rem; font-weight: 500; margin-bottom: 0.5rem; }
.msm-assign-btns { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.msm-assign-btns button { padding: 0.45rem 0.75rem; background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(168,85,247,0.15)); border: 1px solid rgba(139,92,246,0.45); border-radius: 8px; color: #fff; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; }
.msm-assign-btns button:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 12px rgba(139,92,246,0.25); }
.msm-assign-btns button.assigned { background: linear-gradient(135deg, rgba(16,185,129,0.35), rgba(5,150,105,0.25)); border-color: rgba(16,185,129,0.6); box-shadow: 0 2px 8px rgba(16,185,129,0.2); }
.msm-assign-btns button:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
.msm-assign-btns button small { font-size: 0.65rem; opacity: 0.75; margin-left: 0.2rem; }

.msm-clear-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.6); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.75rem; cursor: pointer; align-self: center; transition: all 0.2s ease; margin-top: 0.5rem; }
.msm-clear-btn:hover { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.4); color: #ef4444; }

.msm-collage-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(236,72,153,0.03), rgba(139,92,246,0.03)); border: 2px dashed rgba(236,72,153,0.35); border-radius: 16px; padding: 2rem; }
.msm-collage-empty > span { font-size: 3.5rem; opacity: 0.4; margin-bottom: 1rem; filter: grayscale(0.3); }
.msm-collage-empty p { color: rgba(255,255,255,0.55); font-size: 0.9rem; margin: 0 0 1.25rem; text-align: center; padding: 0 1rem; line-height: 1.5; max-width: 280px; }
.msm-collage-empty button { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, rgba(236,72,153,0.45), rgba(139,92,246,0.4)); border: 1px solid rgba(236,72,153,0.6); border-radius: 10px; color: #fff; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(236,72,153,0.25); }
.msm-collage-empty button:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(236,72,153,0.35); }

/* Shots Panel - Right Main Area */
.msm-shots-panel { display: flex; flex-direction: column; background: linear-gradient(135deg, rgba(10,10,18,0.6), rgba(15,15,25,0.4)); overflow: hidden; }

.msm-action-bar { display: flex; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; align-items: center; background: linear-gradient(180deg, rgba(255,255,255,0.02), transparent); }
.msm-action-btn { padding: 0.6rem 1.1rem; border-radius: 10px; color: #fff; font-size: 0.85rem; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
.msm-action-btn.purple { background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(168,85,247,0.2)); border: 1px solid rgba(139,92,246,0.5); box-shadow: 0 2px 12px rgba(139,92,246,0.2); }
.msm-action-btn.purple:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(139,92,246,0.35); }
.msm-action-btn.cyan { background: linear-gradient(135deg, rgba(6,182,212,0.3), rgba(59,130,246,0.2)); border: 1px solid rgba(6,182,212,0.5); box-shadow: 0 2px 12px rgba(6,182,212,0.2); }
.msm-action-btn.cyan:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(6,182,212,0.35); }
.msm-spacer { flex: 1; }
.msm-reset-btn { padding: 0.5rem 0.8rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.35); border-radius: 8px; color: #ef4444; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; }
.msm-reset-btn:hover { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.5); }

.msm-timeline { display: flex; height: 36px; margin: 0.75rem 1.25rem; background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(10,10,20,0.4)); border-radius: 10px; overflow: hidden; box-shadow: inset 0 2px 8px rgba(0,0,0,0.3), 0 2px 8px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); }
.msm-timeline-seg { display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.75rem; font-weight: 700; border-right: 1px solid rgba(255,255,255,0.1); background: linear-gradient(180deg, rgba(139,92,246,0.4), rgba(139,92,246,0.25)); cursor: pointer; transition: all 0.2s ease; }
.msm-timeline-seg.img { background: linear-gradient(180deg, rgba(16,185,129,0.55), rgba(16,185,129,0.35)); }
.msm-timeline-seg.vid { background: linear-gradient(180deg, rgba(6,182,212,0.65), rgba(59,130,246,0.45)); }
.msm-timeline-seg:hover { filter: brightness(1.25); transform: scaleY(1.05); }

.msm-shot-grid { flex: 1; padding: 1.25rem; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; align-content: start; }

/* Shot Card - Modern Glass Card */
.msm-shot-card { background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.msm-shot-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.15); }
.msm-shot-card.has-video { border-color: rgba(6,182,212,0.45); box-shadow: 0 4px 20px rgba(6,182,212,0.15); }
.msm-shot-card.has-video:hover { box-shadow: 0 8px 35px rgba(6,182,212,0.25); }

.msm-shot-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; background: linear-gradient(180deg, rgba(0,0,0,0.35), rgba(0,0,0,0.2)); border-bottom: 1px solid rgba(255,255,255,0.05); }
.msm-shot-num { background: linear-gradient(135deg, rgba(139,92,246,0.6), rgba(168,85,247,0.5)); color: #fff; padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 6px rgba(139,92,246,0.3); }
.msm-shot-type { color: rgba(255,255,255,0.65); font-size: 0.75rem; font-weight: 500; }
.msm-shot-meta { margin-left: auto; display: flex; align-items: center; gap: 0.4rem; }
.msm-badge-dialog { background: linear-gradient(135deg, rgba(251,191,36,0.45), rgba(245,158,11,0.35)); color: #fcd34d; padding: 0.15rem 0.35rem; border-radius: 5px; font-size: 0.65rem; font-weight: 600; }
.msm-dur { font-size: 0.8rem; font-weight: 600; padding: 0.15rem 0.4rem; border-radius: 5px; }
.msm-dur.green { color: #4ade80; background: rgba(34,197,94,0.15); }
.msm-dur.yellow { color: #fde047; background: rgba(234,179,8,0.15); }
.msm-dur.blue { color: #60a5fa; background: rgba(59,130,246,0.15); }

.msm-shot-preview { position: relative; height: 120px; background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(10,10,20,0.4)); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-direction: column; }
.msm-shot-preview img { width: 100%; height: 100%; object-fit: cover; }
.msm-shot-preview img.dimmed { filter: brightness(0.35); }
.msm-shot-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(20,20,40,0.4)); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.25s ease; backdrop-filter: blur(2px); }
.msm-shot-preview:hover .msm-shot-overlay { opacity: 1; }
.msm-shot-overlay span { font-size: 2rem; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5)); }
.msm-shot-icons { position: absolute; bottom: 0.4rem; right: 0.4rem; display: flex; gap: 0.3rem; }
.msm-icon-img, .msm-icon-vid { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
.msm-icon-img { background: linear-gradient(135deg, #10b981, #059669); }
.msm-icon-vid { background: linear-gradient(135deg, #06b6d4, #0284c7); }

.msm-shot-empty { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.msm-shot-empty > span { font-size: 2rem; color: rgba(255,255,255,0.2); }
.msm-shot-empty button { padding: 0.4rem 0.8rem; background: linear-gradient(135deg, rgba(139,92,246,0.35), rgba(168,85,247,0.25)); border: 1px solid rgba(139,92,246,0.5); border-radius: 8px; color: #fff; font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; }
.msm-shot-empty button:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }
.msm-collage-btn { background: linear-gradient(135deg, rgba(236,72,153,0.45), rgba(139,92,246,0.4)) !important; border-color: rgba(236,72,153,0.55) !important; box-shadow: 0 2px 8px rgba(236,72,153,0.25); }
.msm-collage-badge { position: absolute; top: 0.4rem; left: 0.4rem; background: linear-gradient(135deg, #ec4899, #db2777); color: #fff; padding: 0.2rem 0.45rem; border-radius: 6px; font-size: 0.65rem; font-weight: 700; box-shadow: 0 2px 8px rgba(236,72,153,0.4); }

.msm-vid-progress { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); backdrop-filter: blur(3px); }
.msm-vid-progress span { color: #67e8f9; font-size: 0.8rem; font-weight: 600; margin-top: 0.6rem; text-shadow: 0 2px 8px rgba(0,0,0,0.5); }

.msm-shot-controls { padding: 0.65rem; display: flex; flex-direction: column; gap: 0.5rem; background: linear-gradient(180deg, transparent, rgba(0,0,0,0.15)); }
.msm-dur-btns { display: flex; gap: 0.25rem; }
.msm-dur-btns button { flex: 1; padding: 0.35rem; font-size: 0.7rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 6px; color: rgba(255,255,255,0.8); cursor: pointer; transition: all 0.2s ease; }
.msm-dur-btns button:hover { background: rgba(255,255,255,0.1); }
.msm-dur-btns button.active { font-weight: 700; }
.msm-dur-btns button.active.green { background: linear-gradient(135deg, rgba(34,197,94,0.4), rgba(22,163,74,0.3)); border-color: rgba(34,197,94,0.6); color: #4ade80; }
.msm-dur-btns button.active.yellow { background: linear-gradient(135deg, rgba(234,179,8,0.4), rgba(202,138,4,0.3)); border-color: rgba(234,179,8,0.6); color: #fde047; }
.msm-dur-btns button.active.blue { background: linear-gradient(135deg, rgba(59,130,246,0.4), rgba(37,99,235,0.3)); border-color: rgba(59,130,246,0.6); color: #60a5fa; }

.msm-action-row { display: flex; gap: 0.35rem; }
.msm-play-btn { flex: 1; padding: 0.45rem; background: linear-gradient(135deg, rgba(16,185,129,0.35), rgba(6,182,212,0.3)); border: 1px solid rgba(16,185,129,0.5); border-radius: 8px; color: #fff; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-play-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.25); }
.msm-capture-btn { padding: 0.45rem 0.6rem; background: rgba(16,185,129,0.25); border: 1px solid rgba(16,185,129,0.4); border-radius: 8px; color: #fff; font-size: 0.7rem; cursor: pointer; transition: all 0.2s ease; }
.msm-capture-btn:hover { background: rgba(16,185,129,0.35); }
.msm-animate-btn { width: 100%; padding: 0.5rem; background: linear-gradient(135deg, rgba(6,182,212,0.35), rgba(59,130,246,0.3)); border: 1px solid rgba(6,182,212,0.5); border-radius: 8px; color: #fff; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-animate-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(6,182,212,0.3); }

.msm-render-status { text-align: center; padding: 0.4rem; background: rgba(6,182,212,0.1); border-radius: 6px; }
.msm-render-status span { font-size: 0.75rem; color: #67e8f9; font-weight: 500; }
.msm-progress-bar { height: 4px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; margin-top: 0.4rem; }
.msm-progress-bar div { height: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6, #8b5cf6); animation: msm-progress 1.5s infinite linear; }

/* Spinner - Modern Loading */
.msm-spinner { width: 36px; height: 36px; border: 3px solid rgba(255,255,255,0.15); border-radius: 50%; animation: msm-spin 0.9s cubic-bezier(0.5, 0, 0.5, 1) infinite; }
.msm-spinner.pink { border-top-color: #ec4899; border-right-color: rgba(236,72,153,0.4); }
.msm-spinner.purple { border-top-color: #8b5cf6; border-right-color: rgba(139,92,246,0.4); width: 32px; height: 32px; }
.msm-spinner.cyan { border-top-color: #06b6d4; border-right-color: rgba(6,182,212,0.4); width: 40px; height: 40px; }

/* Popup Modal - Glass Style */
.msm-popup-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 2147483648; }
.msm-popup { background: linear-gradient(135deg, rgba(25,25,40,0.98), rgba(15,15,28,0.99)); border: 1px solid rgba(6,182,212,0.35); border-radius: 16px; width: 380px; max-width: 95vw; box-shadow: 0 25px 80px rgba(0,0,0,0.5), 0 0 60px rgba(6,182,212,0.15); }
.msm-popup-header { display: flex; justify-content: space-between; align-items: center; padding: 1.1rem 1.25rem; background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(59,130,246,0.1)); border-bottom: 1px solid rgba(255,255,255,0.08); border-radius: 16px 16px 0 0; }
.msm-popup-header h4 { margin: 0; color: #fff; font-size: 1.1rem; font-weight: 600; }
.msm-popup-header p { margin: 0.2rem 0 0; color: rgba(255,255,255,0.55); font-size: 0.8rem; }
.msm-popup-header button { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #fff; width: 32px; height: 32px; border-radius: 8px; font-size: 1.3rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.msm-popup-header button:hover { background: rgba(239,68,68,0.25); border-color: rgba(239,68,68,0.4); }
.msm-popup-body { padding: 1.25rem; }

.msm-model-opt { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 0.75rem; cursor: pointer; transition: all 0.2s ease; }
.msm-model-opt:hover:not(.disabled) { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); }
.msm-model-opt.active { background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(59,130,246,0.1)); border-color: rgba(6,182,212,0.5); box-shadow: 0 4px 20px rgba(6,182,212,0.15); }
.msm-model-opt.disabled { opacity: 0.45; cursor: not-allowed; }
.msm-radio { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; transition: all 0.2s ease; flex-shrink: 0; }
.msm-radio.checked { border-color: #06b6d4; background: radial-gradient(#06b6d4 42%, transparent 46%); box-shadow: 0 0 12px rgba(6,182,212,0.4); }
.msm-model-opt div strong { display: block; color: #fff; font-size: 0.95rem; font-weight: 600; }
.msm-model-opt div span { color: rgba(255,255,255,0.55); font-size: 0.8rem; }
.msm-rec { margin-left: auto; background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(5,150,105,0.2)); color: #34d399; padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
.msm-warn { color: #f87171; }

.msm-dur-selector { margin: 1.25rem 0; }
.msm-dur-selector label { display: block; color: rgba(255,255,255,0.75); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.6rem; }
.msm-dur-opts { display: flex; gap: 0.4rem; }
.msm-dur-opts button { flex: 1; padding: 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: rgba(255,255,255,0.8); font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-dur-opts button:hover { background: rgba(255,255,255,0.1); }
.msm-dur-opts button.active { background: linear-gradient(135deg, rgba(59,130,246,0.4), rgba(37,99,235,0.3)); border-color: rgba(59,130,246,0.6); color: #fff; box-shadow: 0 2px 10px rgba(59,130,246,0.25); }

.msm-gen-anim-btn { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #06b6d4, #3b82f6); border: none; border-radius: 10px; color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 20px rgba(6,182,212,0.3); }
.msm-gen-anim-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(6,182,212,0.4); }

/* Animations */
@keyframes msm-spin { to { transform: rotate(360deg); } }
@keyframes msm-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

/* Scrollbar Styling */
.msm-collage-content::-webkit-scrollbar,
.msm-shot-grid::-webkit-scrollbar { width: 8px; }
.msm-collage-content::-webkit-scrollbar-track,
.msm-shot-grid::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 4px; }
.msm-collage-content::-webkit-scrollbar-thumb,
.msm-shot-grid::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.3); border-radius: 4px; }
.msm-collage-content::-webkit-scrollbar-thumb:hover,
.msm-shot-grid::-webkit-scrollbar-thumb:hover { background: rgba(139,92,246,0.5); }

/* Responsive Adjustments */
@media (max-width: 900px) {
    .msm-split-panel { grid-template-columns: 1fr !important; grid-template-rows: auto auto 1fr; }
    .msm-collage-panel { max-height: 45vh; border-bottom: 1px solid rgba(139,92,246,0.2); }
    .msm-resize-handle { display: none; }
}
</style>
