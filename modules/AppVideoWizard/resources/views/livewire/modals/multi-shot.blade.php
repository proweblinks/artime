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
    // PHASE 6: Shot type badge helper functions
    if (!function_exists('getShotTypeBadgeClass')) {
        function getShotTypeBadgeClass($type) {
            $type = strtolower($type ?? '');
            $map = [
                'extreme-close-up' => 'xcu',
                'close-up' => 'cu',
                'medium-close' => 'mcu',
                'medium' => 'med',
                'wide' => 'wide',
                'establishing' => 'est',
                'over-the-shoulder' => 'ots',
                'reaction' => 'reaction',
                'two-shot' => 'two-shot',
            ];
            return $map[$type] ?? 'med';
        }
    }

    if (!function_exists('getShotTypeLabel')) {
        function getShotTypeLabel($type) {
            $type = strtolower($type ?? '');
            $labels = [
                'extreme-close-up' => 'XCU',
                'close-up' => 'CU',
                'medium-close' => 'MCU',
                'medium' => 'MED',
                'wide' => 'WIDE',
                'establishing' => 'EST',
                'over-the-shoulder' => 'OTS',
                'reaction' => 'REACT',
                'two-shot' => '2-SHOT',
            ];
            return $labels[$type] ?? strtoupper(substr($type, 0, 4));
        }
    }

    if (!function_exists('getCameraMovementIcon')) {
        function getCameraMovementIcon($movement) {
            $icons = [
                'push-in' => '->',
                'pull-out' => '<-',
                'pan-left' => '<',
                'pan-right' => '>',
                'tilt-up' => '^',
                'tilt-down' => 'v',
                'static' => 'o',
                'slow-push' => '->',
                'slight-drift' => '~',
                'dolly' => '=',
            ];
            return $icons[strtolower($movement ?? '')] ?? '';
        }
    }

    // PHASE 6: Enhanced camera movement SVG paths
    if (!function_exists('getCameraMovementSvgPath')) {
        function getCameraMovementSvgPath($movement) {
            $paths = [
                'push-in' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
                'pull-out' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
                'pan-left' => '<path d="M19 12H5M5 12l7-7M5 12l7 7"/>',
                'pan-right' => '<path d="M5 12h14M19 12l-7-7M19 12l-7 7"/>',
                'tilt-up' => '<path d="M12 19V5M5 12l7-7 7 7"/>',
                'tilt-down' => '<path d="M12 5v14M19 12l-7 7-7-7"/>',
                'static' => '<circle cx="12" cy="12" r="3"/>',
                'slow-push' => '<path d="M5 12h14M12 5l7 7-7 7" stroke-dasharray="4 2"/>',
                'slight-drift' => '<path d="M5 12c4-2 10 2 14 0"/>',
                'dolly' => '<path d="M5 12h14"/><circle cx="8" cy="16" r="2"/><circle cx="16" cy="16" r="2"/>',
            ];
            return $paths[strtolower($movement ?? '')] ?? $paths['static'];
        }
    }

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
                    // AI Analysis info
                    $firstShot = $decomposed['shots'][0] ?? [];
                    $isAiGenerated = $firstShot['aiRecommended'] ?? false;
                    $aiReasoning = $firstShot['aiReasoning'] ?? '';
                    $hasVariableDurations = count(array_unique(array_column($decomposed['shots'], 'duration'))) > 1;
                    $lipSyncCount = collect($decomposed['shots'])->filter(fn($s) => $s['needsLipSync'] ?? false)->count();
                    // Speech type determines if character lips move on screen
                    $speechType = $scene['voiceover']['speechType'] ?? $scene['speechType'] ?? 'narrator';
                    $speechTypeLabels = [
                        'narrator' => ['label' => '🎙️ Narrator', 'class' => 'msm-speech-narrator', 'desc' => 'External voiceover (no lip movement)'],
                        'internal' => ['label' => '💭 Internal', 'class' => 'msm-speech-internal', 'desc' => 'Character thoughts (no lip movement)'],
                        'monologue' => ['label' => '🗣️ Monologue', 'class' => 'msm-speech-monologue', 'desc' => 'Character speaks aloud (lips move)'],
                        'dialogue' => ['label' => '💬 Dialogue', 'class' => 'msm-speech-dialogue', 'desc' => 'Characters talking (lips move)'],
                    ];
                    $speechInfo = $speechTypeLabels[$speechType] ?? $speechTypeLabels['narrator'];
                @endphp
                <div class="msm-stats">
                    <span>📽️ {{ count($decomposed['shots']) }} {{ __('shots') }}</span>
                    <span>• {{ $totalDuration }}s</span>
                    <span class="msm-stat-green">🖼️ {{ $imagesReady }}/{{ count($decomposed['shots']) }}</span>
                    <span class="msm-stat-cyan">🎬 {{ $videosReady }}/{{ count($decomposed['shots']) }}</span>
                    @if($isAiGenerated)
                        <span class="msm-ai-badge" title="{{ $aiReasoning }}">🤖 AI</span>
                    @endif
                    @if($hasVariableDurations)
                        <span class="msm-var-badge">⏱️ {{ __('Variable') }}</span>
                    @endif
                    {{-- Speech Type Badge - shows whether lips move --}}
                    <span class="msm-speech-badge {{ $speechInfo['class'] }}" title="{{ $speechInfo['desc'] }}">{{ $speechInfo['label'] }}</span>
                    @if($lipSyncCount > 0)
                        <span class="msm-lipsync-badge">👄 {{ $lipSyncCount }} {{ __('lip-sync') }}</span>
                    @endif
                </div>
                @if($isAiGenerated && $aiReasoning)
                    <div class="msm-ai-reasoning" title="{{ $aiReasoning }}">
                        🤖 {{ Str::limit($aiReasoning, 80) }}
                    </div>
                @endif
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

                    {{-- Intelligent Scene Analysis & Recommendation --}}
                    @php
                        $recommendation = $this->getSceneRecommendation($multiShotSceneIndex);
                        $sceneType = $recommendation['sceneType'] ?? 'default';
                        $confidence = $recommendation['confidence'] ?? 0;
                        $recommendedShots = $recommendation['shotCount'] ?? 5;
                        $summary = $recommendation['summary'] ?? '';
                    @endphp

                    <div class="msm-scene-analysis">
                        <div class="msm-analysis-header">
                            <span class="msm-analysis-icon">🎬</span>
                            <span class="msm-analysis-title">{{ __('Scene Analysis') }}</span>
                        </div>

                        <div class="msm-analysis-grid">
                            <div class="msm-analysis-item">
                                <span class="msm-analysis-label">{{ __('Type') }}</span>
                                <span class="msm-analysis-value msm-scene-type-{{ $sceneType }}">
                                    {{ ucfirst($sceneType) }}
                                    <small>({{ $confidence }}%)</small>
                                </span>
                            </div>
                            <div class="msm-analysis-item">
                                <span class="msm-analysis-label">{{ __('Duration') }}</span>
                                <span class="msm-analysis-value">{{ $scene['duration'] ?? 30 }}s</span>
                            </div>
                            <div class="msm-analysis-item">
                                <span class="msm-analysis-label">{{ __('Pacing') }}</span>
                                <span class="msm-analysis-value">{{ ucfirst($this->content['pacing'] ?? 'balanced') }}</span>
                            </div>
                        </div>

                        <div class="msm-recommendation-box">
                            <div class="msm-recommendation-header">
                                <span class="msm-recommendation-count">{{ $recommendedShots }}</span>
                                <span class="msm-recommendation-label">{{ __('shots recommended') }}</span>
                            </div>
                            <p class="msm-recommendation-summary">{{ $summary }}</p>
                        </div>

                        {{-- Pacing Adjustment --}}
                        <div class="msm-pacing-adjuster">
                            <label>{{ __('Adjust Pacing') }}</label>
                            <div class="msm-pacing-buttons">
                                <button type="button"
                                        wire:click="$set('content.pacing', 'fast')"
                                        class="{{ ($this->content['pacing'] ?? 'balanced') === 'fast' ? 'active' : '' }}">
                                    ⚡ {{ __('Fast') }}
                                </button>
                                <button type="button"
                                        wire:click="$set('content.pacing', 'balanced')"
                                        class="{{ ($this->content['pacing'] ?? 'balanced') === 'balanced' ? 'active' : '' }}">
                                    ⚖️ {{ __('Balanced') }}
                                </button>
                                <button type="button"
                                        wire:click="$set('content.pacing', 'contemplative')"
                                        class="{{ ($this->content['pacing'] ?? 'balanced') === 'contemplative' ? 'active' : '' }}">
                                    🌙 {{ __('Slower') }}
                                </button>
                            </div>
                            <p class="msm-pacing-hint">
                                {{ __('Pacing affects shot duration and count') }}
                            </p>
                        </div>
                    </div>

                    <button type="button" wire:click="decomposeScene({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="decomposeScene" class="msm-decompose-btn">
                        <span wire:loading.remove wire:target="decomposeScene">✂️ {{ __('Decompose Scene') }}</span>
                        <span wire:loading wire:target="decomposeScene">⏳ {{ __('Decomposing...') }}</span>
                    </button>
                </div>
            </main>
        @else
            {{-- PHASE 6: Enhanced Progress Summary --}}
            @php
                $shots = $decomposed['shots'] ?? [];
                $totalShots = count($shots);
                $imagesReadyCount = collect($shots)->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->count();
                $imagesGeneratingCount = collect($shots)->filter(fn($s) => ($s['status'] ?? '') === 'generating')->count();
                $videosReadyCount = collect($shots)->filter(fn($s) => ($s['videoStatus'] ?? '') === 'ready' && !empty($s['videoUrl']))->count();
                $videosNeeded = collect($shots)->filter(fn($s) => $s['needsLipSync'] ?? false)->count();
                $avgIntensity = $totalShots > 0 ? (collect($shots)->avg('emotionalIntensity') ?? 0.5) : 0.5;
            @endphp

            <div style="
                display: flex;
                gap: 0.75rem;
                padding: 0.5rem 1rem;
                background: rgba(255, 255, 255, 0.05);
                border-bottom: 1px solid rgba(139, 92, 246, 0.15);
                font-size: 0.7rem;
                flex-wrap: wrap;
                align-items: center;
            ">
                {{-- Image Progress --}}
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <div class="vw-mini-progress">
                        <svg width="16" height="16" viewBox="0 0 16 16">
                            <circle class="vw-mini-progress-bg" cx="8" cy="8" r="6"/>
                            <circle class="vw-mini-progress-fill" cx="8" cy="8" r="6"
                                stroke="{{ $imagesReadyCount === $totalShots ? '#22C55E' : '#3B82F6' }}"
                                stroke-dasharray="{{ 2 * 3.14159 * 6 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 6 * (1 - ($totalShots > 0 ? $imagesReadyCount / $totalShots : 0)) }}"/>
                        </svg>
                    </div>
                    <span style="color: rgba(255,255,255,0.7);">{{ __('Images') }}:</span>
                    <span style="color: {{ $imagesReadyCount === $totalShots ? '#22C55E' : '#fff' }}; font-weight: 600;">
                        {{ $imagesReadyCount }}/{{ $totalShots }}
                    </span>
                    @if($imagesGeneratingCount > 0)
                        <span class="vw-status-badge vw-status-generating" style="font-size: 0.5rem;">
                            {{ $imagesGeneratingCount }} {{ __('generating') }}
                        </span>
                    @endif
                </div>

                {{-- Video Progress (if lip-sync needed) --}}
                @if($videosNeeded > 0)
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <div class="vw-mini-progress">
                            <svg width="16" height="16" viewBox="0 0 16 16">
                                <circle class="vw-mini-progress-bg" cx="8" cy="8" r="6"/>
                                <circle class="vw-mini-progress-fill" cx="8" cy="8" r="6"
                                    stroke="{{ $videosReadyCount === $videosNeeded ? '#22C55E' : '#06B6D4' }}"
                                    stroke-dasharray="{{ 2 * 3.14159 * 6 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 6 * (1 - ($videosNeeded > 0 ? $videosReadyCount / $videosNeeded : 0)) }}"/>
                            </svg>
                        </div>
                        <span style="color: rgba(255,255,255,0.7);">{{ __('Videos') }}:</span>
                        <span style="color: {{ $videosReadyCount === $videosNeeded ? '#22C55E' : '#fff' }}; font-weight: 600;">
                            {{ $videosReadyCount }}/{{ $videosNeeded }}
                        </span>
                    </div>
                @endif

                {{-- Average Intensity --}}
                <div style="display: flex; align-items: center; gap: 0.35rem; margin-left: auto;">
                    <span style="color: rgba(255,255,255,0.5);">{{ __('Avg Intensity') }}:</span>
                    <span style="color: {{ $avgIntensity >= 0.7 ? '#EF4444' : ($avgIntensity >= 0.4 ? '#F59E0B' : '#3B82F6') }}; font-weight: 600;">
                        {{ round($avgIntensity * 100) }}%
                    </span>
                </div>
            </div>

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

                                {{-- Collage Image - supports both 4 separate images and single grid image --}}
                                <div class="msm-collage-image" x-data="{ selectedRegion: null }">
                                    @php
                                        $hasRegionImages = !empty($currentCollage['regionImages']) && count($currentCollage['regionImages']) > 0;
                                        $pageStatus = $currentCollage['status'] ?? 'ready';
                                        $isPagePending = $pageStatus === 'pending';
                                        $isPageGenerating = $pageStatus === 'generating';
                                        // Check if shots have generated images (for showing in preview when no collage exists)
                                        $shotsWithImages = collect($decomposed['shots'] ?? [])->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->values()->all();
                                        $hasShotImages = count($shotsWithImages) > 0;
                                    @endphp

                                    @if($isPagePending)
                                        {{-- Page not yet generated - show Generate button --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; aspect-ratio: 1; background: rgba(0,0,0,0.2); border-radius: 8px; gap: 12px;">
                                            <div style="text-align: center; color: #9ca3af;">
                                                <p style="font-size: 0.875rem;">{{ __('Page :page not generated yet', ['page' => $currentPage + 1]) }}</p>
                                                <p style="font-size: 0.75rem; margin-top: 4px;">{{ __('Shots') }} {{ min($currentShots) + 1 }}-{{ max($currentShots) + 1 }}</p>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="generateCollagePage({{ $multiShotSceneIndex }}, {{ $currentPage }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateCollagePage"
                                                class="msm-gen-btn"
                                                style="padding: 8px 16px; font-size: 0.875rem;">
                                                <span wire:loading.remove wire:target="generateCollagePage">🖼️ {{ __('Generate Page') }}</span>
                                                <span wire:loading wire:target="generateCollagePage">⏳ {{ __('Generating...') }}</span>
                                            </button>
                                        </div>
                                    @elseif($isPageGenerating)
                                        {{-- Page is currently generating --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; aspect-ratio: 1; background: rgba(0,0,0,0.2); border-radius: 8px; gap: 12px;">
                                            <div class="msm-spinner pink" style="width: 32px; height: 32px;"></div>
                                            <p style="color: #ec4899; font-size: 0.875rem;">{{ __('Generating page :page...', ['page' => $currentPage + 1]) }}</p>
                                        </div>
                                    @elseif($hasRegionImages)
                                        {{-- NEW: Display 4 separate images in a 2x2 grid --}}
                                        <div class="msm-region-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; width: 100%; aspect-ratio: 1;">
                                            @for($r = 0; $r < 4; $r++)
                                                @php
                                                    $regionImage = $currentCollage['regionImages'][$r] ?? null;
                                                    $assigned = ($currentCollage['regions'][$r]['assignedToShot'] ?? null);
                                                    $imageUrl = $regionImage['imageUrl'] ?? null;
                                                    $imageStatus = $regionImage['status'] ?? 'pending';
                                                @endphp
                                                <div class="msm-region-cell"
                                                     style="position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 4px; cursor: pointer; border: 2px solid transparent;"
                                                     x-on:click="selectedRegion = {{ $r }}; $dispatch('region-selected', { regionIndex: {{ $r }}, pageIndex: {{ $currentPage }} })"
                                                     x-bind:style="selectedRegion === {{ $r }} ? 'border-color: #ec4899;' : ''">
                                                    @if($imageStatus === 'generating')
                                                        <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.3);">
                                                            <div class="msm-spinner pink" style="width: 24px; height: 24px;"></div>
                                                        </div>
                                                    @elseif($imageUrl)
                                                        <img src="{{ $imageUrl }}" alt="Shot {{ ($currentShots[$r] ?? $r) + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                    @else
                                                        <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.2); color: #666;">
                                                            <span>—</span>
                                                        </div>
                                                    @endif
                                                    {{-- Overlay with shot number --}}
                                                    <div style="position: absolute; top: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                                        {{ ($currentShots[$r] ?? $r) + 1 }}
                                                    </div>
                                                    @if($assigned !== null)
                                                        <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(16, 185, 129, 0.9); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem;">
                                                            → Shot {{ $assigned + 1 }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    @elseif(!empty($currentCollage['previewUrl']))
                                        {{-- FALLBACK: Single grid image with overlay (legacy) --}}
                                        <img src="{{ $currentCollage['previewUrl'] }}" alt="Collage">
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
                                    @elseif($hasShotImages)
                                        {{-- NEW: Display generated shot images when no collage but shots have images --}}
                                        <div class="msm-region-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; width: 100%; aspect-ratio: 1;">
                                            @for($r = 0; $r < min(4, count($decomposed['shots'] ?? [])); $r++)
                                                @php
                                                    $shotData = $decomposed['shots'][$r] ?? null;
                                                    $shotImageUrl = $shotData['imageUrl'] ?? null;
                                                    $shotStatus = $shotData['status'] ?? 'pending';
                                                    $hasLipSync = $shotData['needsLipSync'] ?? false;
                                                @endphp
                                                <div class="msm-region-cell"
                                                     style="position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 4px; cursor: pointer; border: 2px solid {{ $hasLipSync ? '#ec4899' : 'transparent' }};"
                                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $r }})"
                                                     title="{{ $shotData['type'] ?? 'Shot' }} {{ $r + 1 }}{{ $hasLipSync ? ' (Lip-sync)' : '' }}">
                                                    @if($shotStatus === 'generating')
                                                        <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.3);">
                                                            <div class="msm-spinner pink" style="width: 24px; height: 24px;"></div>
                                                        </div>
                                                    @elseif($shotImageUrl)
                                                        <img src="{{ $shotImageUrl }}" alt="Shot {{ $r + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                    @else
                                                        <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.2); color: #666;">
                                                            <span>—</span>
                                                        </div>
                                                    @endif
                                                    {{-- Overlay with shot number and type --}}
                                                    <div style="position: absolute; top: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                                        {{ $r + 1 }}
                                                    </div>
                                                    {{-- Shot type badge --}}
                                                    <div style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem;">
                                                        {{ ucfirst(str_replace(['_', '-'], ' ', $shotData['type'] ?? 'shot')) }}
                                                    </div>
                                                    @if($hasLipSync)
                                                        <div style="position: absolute; top: 4px; right: 4px; background: rgba(236, 72, 153, 0.9); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem;">
                                                            👄
                                                        </div>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                        {{-- Show remaining shots indicator if more than 4 --}}
                                        @if(count($decomposed['shots'] ?? []) > 4)
                                            <p style="text-align: center; color: #9ca3af; font-size: 0.75rem; margin-top: 0.5rem;">
                                                +{{ count($decomposed['shots']) - 4 }} {{ __('more shots in panel →') }}
                                            </p>
                                        @endif
                                    @else
                                        <div class="msm-no-preview">{{ __('No preview') }}</div>
                                    @endif
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

                                {{-- Extract All & Clear buttons --}}
                                <div class="msm-collage-actions" style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <button type="button"
                                            wire:click="extractAllCollageRegions({{ $multiShotSceneIndex }})"
                                            wire:loading.attr="disabled"
                                            class="msm-extract-btn"
                                            style="flex: 1; padding: 0.5rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(6, 182, 212, 0.2)); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.4rem; color: #10b981; font-size: 0.75rem; cursor: pointer;"
                                            title="{{ __('Extract all collage regions to their respective shots') }}">
                                        <span wire:loading.remove wire:target="extractAllCollageRegions">📥 {{ __('Extract All to Shots') }}</span>
                                        <span wire:loading wire:target="extractAllCollageRegions">⏳ {{ __('Extracting...') }}</span>
                                    </button>
                                    <button type="button" wire:click="clearCollagePreview({{ $multiShotSceneIndex }})" class="msm-clear-btn">✕ {{ __('Clear') }}</button>
                                </div>
                            @endif
                        @else
                            {{-- No collage exists - check if shots have images to display --}}
                            @php
                                $shotsWithImages = collect($decomposed['shots'] ?? [])->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->values()->all();
                                $hasShotImages = count($shotsWithImages) > 0;
                            @endphp

                            @if($hasShotImages)
                                {{-- Show shot images in 2x2 grid when no collage but shots have images --}}
                                <div class="msm-region-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; width: 100%; aspect-ratio: 1;">
                                    @for($r = 0; $r < min(4, count($decomposed['shots'] ?? [])); $r++)
                                        @php
                                            $shotData = $decomposed['shots'][$r] ?? null;
                                            $shotImageUrl = $shotData['imageUrl'] ?? null;
                                            $shotStatus = $shotData['status'] ?? 'pending';
                                            $hasLipSync = $shotData['needsLipSync'] ?? false;
                                        @endphp
                                        <div class="msm-region-cell"
                                             style="position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 4px; cursor: pointer; border: 2px solid {{ $hasLipSync ? '#ec4899' : 'transparent' }};"
                                             wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $r }})"
                                             title="{{ $shotData['type'] ?? 'Shot' }} {{ $r + 1 }}{{ $hasLipSync ? ' (Lip-sync)' : '' }}">
                                            @if($shotStatus === 'generating')
                                                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.3);">
                                                    <div class="msm-spinner pink" style="width: 24px; height: 24px;"></div>
                                                </div>
                                            @elseif($shotImageUrl)
                                                <img src="{{ $shotImageUrl }}" alt="Shot {{ $r + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0,0,0,0.2); color: #666;">
                                                    <span>—</span>
                                                </div>
                                            @endif
                                            {{-- Overlay with shot number --}}
                                            <div style="position: absolute; top: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                                {{ $r + 1 }}
                                            </div>
                                            {{-- Shot type badge --}}
                                            <div style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem;">
                                                {{ ucfirst(str_replace(['_', '-'], ' ', $shotData['type'] ?? 'shot')) }}
                                            </div>
                                            @if($hasLipSync)
                                                <div style="position: absolute; top: 4px; right: 4px; background: rgba(236, 72, 153, 0.9); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem;">
                                                    👄
                                                </div>
                                            @endif
                                        </div>
                                    @endfor
                                </div>
                                @if(count($decomposed['shots'] ?? []) > 4)
                                    <p style="text-align: center; color: #9ca3af; font-size: 0.75rem; margin-top: 0.5rem;">
                                        +{{ count($decomposed['shots']) - 4 }} {{ __('more shots in panel →') }}
                                    </p>
                                @endif
                                {{-- Still show Generate Collage option --}}
                                <div style="margin-top: 0.75rem; text-align: center;">
                                    <button type="button" wire:click="generateCollagePreview({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="generateCollagePreview" class="msm-gen-btn" style="padding: 6px 12px; font-size: 0.75rem;">
                                        <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate New Collage') }}</span>
                                        <span wire:loading wire:target="generateCollagePreview">⏳</span>
                                    </button>
                                </div>
                            @else
                                {{-- Empty State - no shots have images --}}
                                <div class="msm-collage-empty">
                                    <span>🖼️</span>
                                    <p>{{ __('Generate a 2x2 collage to quickly assign visuals to shots') }}</p>
                                    <button type="button" wire:click="generateCollagePreview({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" wire:target="generateCollagePreview">
                                        <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate Collage') }}</span>
                                        <span wire:loading wire:target="generateCollagePreview">⏳</span>
                                    </button>
                                </div>
                            @endif
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
                    @php
                        // Get lip-sync shot stats
                        $lipSyncStats = $this->getLipSyncShotStats($multiShotSceneIndex);
                    @endphp
                    {{-- Action Bar --}}
                    <div class="msm-action-bar">
                        <button wire:click="generateAllShots({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" class="msm-action-btn purple">🎨 {{ __('Generate All Images') }}</button>
                        {{-- Generate Voiceovers button (shown when there are lip-sync shots needing audio) --}}
                        @if($lipSyncStats['needsAudio'] > 0)
                            <button wire:click="generateAllShotVoiceovers({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" class="msm-action-btn amber" title="{{ __('Generate voiceovers for :count dialogue shot(s)', ['count' => $lipSyncStats['needsAudio']]) }}">
                                🎤 {{ __('Gen. Voiceovers') }} ({{ $lipSyncStats['needsAudio'] }})
                            </button>
                        @endif
                        <button wire:click="generateAllShotVideos({{ $multiShotSceneIndex }})" wire:loading.attr="disabled" class="msm-action-btn cyan">🎬 {{ __('Animate All') }}</button>
                        <span class="msm-spacer"></span>
                        {{-- Lip-sync status indicator --}}
                        @if($lipSyncStats['total'] > 0)
                            <span class="msm-lipsync-status" title="{{ __('Dialogue shots: :has of :total have voiceovers', ['has' => $lipSyncStats['hasAudio'], 'total' => $lipSyncStats['total']]) }}">
                                👄 {{ $lipSyncStats['hasAudio'] }}/{{ $lipSyncStats['total'] }}
                            </span>
                        @endif
                        <button wire:click="resetDecomposition({{ $multiShotSceneIndex }})" class="msm-reset-btn">🗑️ {{ __('Reset') }}</button>
                    </div>

                    {{-- Timeline (synced with carousel) --}}
                    <div class="msm-timeline" x-data="{ activeSegment: 0 }" x-on:carousel-index-changed.window="activeSegment = $event.detail.index">
                        @foreach($decomposed['shots'] as $idx => $shot)
                            @php
                                $dur = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                $pct = $totalDuration > 0 ? ($dur / $totalDuration * 100) : (100 / count($decomposed['shots']));
                                $hasImg = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                $hasVid = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                            @endphp
                            <div class="msm-timeline-seg {{ $hasVid ? 'vid' : ($hasImg ? 'img' : '') }}"
                                 x-bind:class="{ 'active': activeSegment === {{ $idx }} }"
                                 style="width: {{ $pct }}%;"
                                 x-on:click="$dispatch('scroll-to-shot', { index: {{ $idx }} })"
                                 wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $idx }})"
                                 title="Shot {{ $idx + 1 }}: {{ $dur }}s">{{ $idx + 1 }}</div>
                        @endforeach
                    </div>

                    {{-- Shot Carousel --}}
                    @php
                        // Create a hash of all shot imageUrls to detect changes
                        $shotsHash = md5(json_encode(array_column($decomposed['shots'], 'imageUrl')));
                        $totalShotsCount = count($decomposed['shots']);
                    @endphp
                    <div class="msm-carousel-wrapper"
                         x-data="{
                             currentIndex: 0,
                             totalShots: {{ $totalShotsCount }},
                             scrollContainer: null,
                             init() {
                                 this.scrollContainer = this.$refs.shotGrid;
                                 this.scrollContainer.addEventListener('scroll', () => this.updateCurrentIndex());
                             },
                             updateCurrentIndex() {
                                 if (!this.scrollContainer) return;
                                 const cards = this.scrollContainer.querySelectorAll('.msm-shot-card');
                                 const containerRect = this.scrollContainer.getBoundingClientRect();
                                 const containerCenter = containerRect.left + containerRect.width / 2;
                                 let closestIdx = 0;
                                 let closestDist = Infinity;
                                 cards.forEach((card, idx) => {
                                     const rect = card.getBoundingClientRect();
                                     const cardCenter = rect.left + rect.width / 2;
                                     const dist = Math.abs(cardCenter - containerCenter);
                                     if (dist < closestDist) {
                                         closestDist = dist;
                                         closestIdx = idx;
                                     }
                                 });
                                 if (this.currentIndex !== closestIdx) {
                                     this.currentIndex = closestIdx;
                                     this.$dispatch('carousel-index-changed', { index: closestIdx });
                                 }
                             },
                             scrollToShot(idx) {
                                 const cards = this.scrollContainer.querySelectorAll('.msm-shot-card');
                                 if (cards[idx]) {
                                     cards[idx].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                                     this.currentIndex = idx;
                                 }
                             },
                             prev() { if (this.currentIndex > 0) this.scrollToShot(this.currentIndex - 1); },
                             next() { if (this.currentIndex < this.totalShots - 1) this.scrollToShot(this.currentIndex + 1); }
                         }"
                         x-on:keydown.left.window="prev()"
                         x-on:keydown.right.window="next()"
                         x-on:scroll-to-shot.window="scrollToShot($event.detail.index)">
                        <div class="msm-carousel-container">
                            {{-- Prev Button --}}
                            <button class="msm-carousel-nav prev"
                                    x-on:click="prev()"
                                    x-bind:disabled="currentIndex === 0"
                                    title="{{ __('Previous shot') }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>

                            {{-- Shot Grid (now horizontal carousel) --}}
                            <div class="msm-shot-grid" x-ref="shotGrid" wire:key="shots-grid-{{ $multiShotSceneIndex }}-{{ $shotsHash }}">
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
                                // Additional features from old implementation
                                $cameraMovement = $shot['cameraMovement'] ?? 'static';
                                $tokenCost = $shotDur <= 5 ? 100 : ($shotDur <= 6 ? 120 : 200);
                                $needsLipSync = $shot['needsLipSync'] ?? false;
                                $aiRecommended = $shot['aiRecommended'] ?? false;
                                $wasTransferred = isset($shot['transferredFrom']);
                                $transferredFrom = $shot['transferredFrom'] ?? null;
                                $isLastShot = $shotIndex === count($decomposed['shots']) - 1;
                                $isFirstShot = $shotIndex === 0;
                                $fromCollageRegion = $shot['fromCollageRegion'] ?? null;
                                $isSelected = ($decomposed['selectedShot'] ?? 0) === $shotIndex;
                            @endphp

                            <div class="msm-shot-card {{ $hasVideo ? 'has-video' : '' }} {{ $wasTransferred ? 'transferred' : '' }} {{ $isSelected ? 'selected' : '' }}" data-video-status="{{ $shot['videoStatus'] ?? 'pending' }}" data-shot-index="{{ $shotIndex }}" wire:key="shot-{{ $multiShotSceneIndex }}-{{ $shotIndex }}-{{ md5($shot['imageUrl'] ?? 'empty') }}" wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $shotIndex }})">
                                <div class="msm-shot-header">
                                    <span class="msm-shot-num">{{ $shotIndex + 1 }}</span>
                                    <span class="msm-shot-type">{{ ucfirst(str_replace('_', ' ', $shot['type'] ?? 'shot')) }}</span>
                                    @php
                                        $hasMonologue = !empty($shot['monologue']);
                                        $hasAudioReady = !empty($shot['audioUrl']) && ($shot['audioStatus'] ?? '') === 'ready';
                                        $audioGenerating = ($shot['audioStatus'] ?? '') === 'generating';
                                    @endphp
                                    <div class="msm-shot-meta">
                                        @if($needsLipSync)<span class="msm-badge-lipsync" title="{{ __('Lip-sync: Character speaks on screen (lips will move via MultiTalk)') }}">👄</span>@endif
                                        @if($aiRecommended)<span class="msm-badge-ai" title="{{ __('AI recommended') }}">🤖</span>@endif
                                        @if($hasDialogue && !$needsLipSync)<span class="msm-badge-voiceover" title="{{ __('Voiceover only: Text spoken off-screen (no lip movement)') }}">🎙️</span>@endif
                                        @if($hasDialogue && $needsLipSync)<span class="msm-badge-dialog" title="{{ __('Has dialogue text') }}">💬</span>@endif
                                        @if($hasAudioReady)<span class="msm-badge-audio" title="{{ __('Voiceover ready') }}">🎤</span>@endif
                                        @if($audioGenerating)<span class="msm-badge-audio-gen" title="{{ __('Generating voiceover...') }}">⏳🎤</span>@endif
                                        <span class="msm-dur {{ $durColor }}">{{ $shotDur }}s</span>
                                    </div>
                                    {{-- Monologue/Dialogue text indicator --}}
                                    @if($hasMonologue || $hasDialogue)
                                        <div class="msm-monologue-indicator" title="{{ $shot['monologue'] ?? $shot['dialogue'] ?? '' }}">
                                            <small class="msm-monologue-preview">🗣️ "{{ Str::limit($shot['monologue'] ?? $shot['dialogue'] ?? '', 30) }}"</small>
                                        </div>
                                    @endif
                                </div>

                                {{-- PHASE 6: Shot Type Badges --}}
                                <div class="vw-shot-badges" style="padding: 0.25rem 0.75rem;">
                                    {{-- Shot Type --}}
                                    @if(!empty($shot['type']))
                                        <span class="vw-shot-badge vw-shot-badge-{{ getShotTypeBadgeClass($shot['type']) }}">
                                            {{ getShotTypeLabel($shot['type']) }}
                                        </span>
                                    @endif

                                    {{-- Purpose (if different from type) --}}
                                    @if(!empty($shot['purpose']) && $shot['purpose'] !== $shot['type'])
                                        <span class="vw-shot-badge vw-shot-badge-{{ getShotTypeBadgeClass($shot['purpose']) }}">
                                            {{ strtoupper(substr($shot['purpose'], 0, 4)) }}
                                        </span>
                                    @endif

                                    {{-- PHASE 6: Camera Movement with Icons --}}
                                    @if(!empty($shot['cameraMovement']) || !empty($shot['suggestedMovement']))
                                        @php
                                            $movement = $shot['cameraMovement'] ?? $shot['suggestedMovement'] ?? 'static';
                                        @endphp
                                        @if($movement !== 'static')
                                            <div class="vw-shot-badge vw-shot-badge-movement"
                                                 title="{{ ucwords(str_replace('-', ' ', $movement)) }}"
                                                 style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    {!! getCameraMovementSvgPath($movement) !!}
                                                </svg>
                                                <span>{{ strtoupper(Str::limit(str_replace('-', '', $movement), 5, '')) }}</span>
                                            </div>
                                        @endif
                                    @endif

                                    {{-- Climax Indicator --}}
                                    @if(!empty($shot['isClimax']))
                                        <span class="vw-shot-badge vw-shot-badge-climax">
                                            CLIMAX
                                        </span>
                                    @endif
                                </div>

                                {{-- PHASE 6: Shot Speech/Text Display --}}
                                @php
                                    $shotDialogue = $shot['dialogue'] ?? null;
                                    $shotMonologue = $shot['monologue'] ?? null;
                                    $shotNarration = $shot['narration'] ?? null;
                                    $shotSpeaker = $shot['speakingCharacter'] ?? $shot['speaker'] ?? null;
                                    $shotSpeechIndicator = $shot['speechIndicator'] ?? null;
                                    $shotHasText = !empty($shotDialogue) || !empty($shotMonologue) || !empty($shotNarration);
                                @endphp

                                @if($shotHasText)
                                    {{-- Speech Type Badge --}}
                                    <div class="msm-speech-badge-row" style="display: flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.75rem;">
                                        @if($needsLipSync && ($shotDialogue || $shotMonologue))
                                            <span class="msm-speech-type-badge dialogue">💬 {{ __('Dialogue') }}</span>
                                        @elseif($shotMonologue || $shotDialogue)
                                            <span class="msm-speech-type-badge monologue">🗣️ {{ __('Monologue') }}</span>
                                        @elseif($shotNarration)
                                            <span class="msm-speech-type-badge narrator">🎙️ {{ __('Narrator') }}</span>
                                        @endif
                                        @if($shotSpeaker)
                                            <span style="color: rgba(255,255,255,0.7); font-size: 0.8rem; font-weight: 500;">{{ $shotSpeaker }}</span>
                                        @endif
                                    </div>

                                    {{-- Text Content - Compact --}}
                                    <div class="msm-shot-text-content" style="
                                        background: {{ $needsLipSync ? 'rgba(236, 72, 153, 0.1)' : ($shotNarration ? 'rgba(100, 116, 139, 0.1)' : 'rgba(139, 92, 246, 0.1)') }};
                                        border-left: 2px solid {{ $needsLipSync ? 'rgba(236, 72, 153, 0.6)' : ($shotNarration ? 'rgba(100, 116, 139, 0.5)' : 'rgba(139, 92, 246, 0.5)') }};
                                        padding: 0.25rem 0.5rem;
                                        margin: 0 0.5rem 0.35rem;
                                        border-radius: 0 4px 4px 0;
                                        font-size: 0.7rem;
                                    ">
                                        <span style="color: rgba(255,255,255,0.85); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            @if($shotDialogue || $shotMonologue)
                                                "{{ Str::limit($shotDialogue ?? $shotMonologue, 60) }}"
                                            @elseif($shotNarration)
                                                {{ Str::limit($shotNarration, 60) }}
                                            @endif
                                        </span>
                                    </div>
                                @elseif(!empty($shot['needsLipSync']))
                                    {{-- Lip-sync indicator without text - Compact --}}
                                    <div style="display: inline-flex; align-items: center; gap: 0.2rem; background: rgba(236, 72, 153, 0.2); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.65rem; color: #f472b6; margin: 0.2rem 0.5rem; font-weight: 500;">
                                        <span>👄</span>
                                        <span>{{ __('Lip Sync') }}</span>
                                    </div>
                                @else
                                    {{-- Silent shot indicator - Compact --}}
                                    <div style="display: inline-flex; align-items: center; gap: 0.2rem; background: rgba(100, 116, 139, 0.15); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.65rem; color: rgba(148, 163, 184, 0.8); margin: 0.2rem 0.5rem;">
                                        <span>🔇</span>
                                        <span>{{ __('Silent') }}</span>
                                    </div>
                                @endif

                                {{-- PHASE 6: Shot Status and Intensity - Compact --}}
                                <div style="display: flex; align-items: center; gap: 0.35rem; margin: 0.2rem 0.5rem;">
                                    {{-- Image Status --}}
                                    @php
                                        $imgStatus = $shot['status'] ?? 'pending';
                                        $vidStatus = $shot['videoStatus'] ?? 'pending';
                                    @endphp

                                    <div class="vw-status-badge vw-status-{{ $imgStatus }}">
                                        @if($imgStatus === 'pending')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        @elseif($imgStatus === 'generating')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                            </svg>
                                        @elseif($imgStatus === 'ready')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        @endif
                                        IMG
                                    </div>

                                    {{-- Video Status (if applicable) --}}
                                    @if(!empty($shot['needsLipSync']) || $vidStatus !== 'pending')
                                        <div class="vw-status-badge vw-status-{{ $vidStatus === 'processing' ? 'generating' : $vidStatus }}">
                                            @if($vidStatus === 'ready')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            @elseif(in_array($vidStatus, ['generating', 'processing']))
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                    <circle cx="12" cy="12" r="10"/>
                                                </svg>
                                            @endif
                                            VID
                                        </div>
                                    @endif
                                </div>

                                {{-- Intensity Bar --}}
                                @php
                                    $intensity = $shot['emotionalIntensity'] ?? 0.5;
                                    $isClimaxShot = $shot['isClimax'] ?? false;
                                    $intensityClass = $isClimaxShot ? 'climax' : ($intensity >= 0.7 ? 'high' : ($intensity >= 0.4 ? 'medium' : 'low'));
                                    $intensityPercent = round($intensity * 100);
                                @endphp
                                <div class="vw-intensity-bar" style="margin: 0.5rem 0.75rem;" title="Intensity: {{ $intensityPercent }}%">
                                    <div class="vw-intensity-fill vw-intensity-{{ $intensityClass }}" style="width: {{ $intensityPercent }}%;"></div>
                                </div>

                                <div class="msm-shot-preview" wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})">
                                    @if($hasImage)
                                        <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotIndex + 1 }}">
                                        <div class="msm-shot-overlay"><span>{{ $hasVideo ? '▶️' : '🔍' }}</span><small>{{ __('View Prompts') }}</small></div>
                                        <div class="msm-shot-icons">
                                            <span class="msm-icon-img">🖼</span>
                                            @if($hasVideo)<span class="msm-icon-vid">🎬</span>@endif
                                        </div>
                                        {{-- Frame transfer badge --}}
                                        @if($wasTransferred)
                                            <span class="msm-transfer-badge">🔗 {{ __('from') }} #{{ $transferredFrom + 1 }}</span>
                                        @endif
                                    @elseif($isGenImg)
                                        <div class="msm-spinner purple"></div>
                                        <span>{{ __('Generating...') }}</span>
                                        <div class="msm-hover-hint">📝 {{ __('View Prompts') }}</div>
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
                                            <div class="msm-hover-hint">📝 {{ __('View Prompts') }}</div>
                                        </div>
                                    @endif
                                    @if($hasColReg)
                                        <span class="msm-collage-badge">🖼️ P{{ $collageSrc['pageIndex'] + 1 }}R{{ $collageSrc['regionIndex'] + 1 }}</span>
                                    @endif
                                    @if($fromCollageRegion !== null)
                                        <span class="msm-from-collage">{{ __('from collage') }}</span>
                                    @endif
                                </div>

                                {{-- Regenerate at 16:9 button (only show when shot has collage image) --}}
                                @if($hasImage && ($fromCollageRegion !== null || ($shot['fromCollageQuadrant'] ?? null) !== null))
                                    <div class="msm-regen-16x9" style="margin-top: 0.25rem;">
                                        <button wire:click.stop="regenerateShotFromReference({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="regenerateShotFromReference({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                class="msm-regen-btn"
                                                style="width: 100%; padding: 0.35rem 0.5rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(6, 182, 212, 0.2)); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.4rem; color: #10b981; font-size: 0.7rem; cursor: pointer; transition: all 0.2s;"
                                                title="{{ __('Regenerate at proper 16:9 aspect ratio using this image as reference') }}">
                                            <span wire:loading.remove wire:target="regenerateShotFromReference({{ $multiShotSceneIndex }}, {{ $shotIndex }})">⬆️ {{ __('Regen 16:9') }}</span>
                                            <span wire:loading wire:target="regenerateShotFromReference({{ $multiShotSceneIndex }}, {{ $shotIndex }})">⏳ {{ __('Generating...') }}</span>
                                        </button>
                                    </div>
                                @endif

                                {{-- Shot Info Row: Camera + Token Cost --}}
                                <div class="msm-shot-info">
                                    <span class="msm-camera">🎥 {{ $cameraMovement }}</span>
                                    <span class="msm-tokens">⚡ {{ $tokenCost }}t</span>
                                </div>

                                {{-- Frame Status --}}
                                <div class="msm-frame-status">
                                    @if($isFirstShot)
                                        @if($hasImage)
                                            <span class="msm-status-ok">🔗 {{ __('Scene image') }}</span>
                                        @else
                                            <span class="msm-status-wait">⚠️ {{ __('Generate scene first') }}</span>
                                        @endif
                                    @else
                                        @if($wasTransferred)
                                            <span class="msm-status-ok">🔗 {{ __('Frame from Shot') }} {{ $transferredFrom + 1 }}</span>
                                        @elseif(!$hasImage)
                                            <span class="msm-status-wait">⏳ {{ __('Awaiting frame') }}</span>
                                        @endif
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
                                        @php
                                            $usedModel = $shot['selectedVideoModel'] ?? 'minimax';
                                            $wrongModel = $needsLipSync && $usedModel !== 'multitalk';
                                        @endphp
                                        <div class="msm-action-row">
                                            <button wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-play-btn">▶️ {{ __('Play') }}</button>
                                            @if(!$isLastShot)
                                                <button wire:click.stop="openFrameCaptureModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-capture-btn">🎯→{{ $shotIndex + 2 }}</button>
                                            @endif
                                            {{-- Re-Animate Button --}}
                                            <button wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-reanimate-btn {{ $wrongModel ? 'msm-needs-reanimate' : '' }}" title="{{ $wrongModel ? __('Needs lip-sync - should use MultiTalk') : __('Re-animate with different model') }}">
                                                🔄
                                            </button>
                                        </div>
                                        {{-- Warning if animated with wrong model --}}
                                        @if($wrongModel)
                                            <div class="msm-wrong-model-hint">
                                                ⚠️ {{ __('Needs lip-sync, used') }} {{ ucfirst($usedModel) }}
                                            </div>
                                        @endif
                                    @elseif($hasImage && !$isGenVid)
                                        <div class="msm-action-row">
                                            <button wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-animate-btn">🎬 {{ __('Animate') }}</button>
                                            <button wire:click.stop="openShotFaceCorrectionModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-face-btn" title="{{ __('Face Correction') }}">👤 {{ __('Fix Face') }}</button>
                                        </div>
                                    @elseif($isGenVid)
                                        @php
                                            $videoProvider = $shot['videoProvider'] ?? 'minimax';
                                            $jobStartedAt = $shot['videoJobStartedAt'] ?? null;
                                            $estimatedSecs = $shot['videoEstimatedSeconds'] ?? 180; // default 3 min
                                            $elapsedSecs = $jobStartedAt ? (now()->timestamp - $jobStartedAt) : 0;
                                            $remainingSecs = max(0, $estimatedSecs - $elapsedSecs);
                                            $progressPct = $estimatedSecs > 0 ? min(95, ($elapsedSecs / $estimatedSecs) * 100) : 0;
                                            $elapsedMin = floor($elapsedSecs / 60);
                                            $elapsedSecRem = $elapsedSecs % 60;
                                            $remainingMin = floor($remainingSecs / 60);
                                            $remainingSecRem = $remainingSecs % 60;
                                        @endphp
                                        <div class="msm-render-status msm-render-status-enhanced" wire:poll.5s>
                                            <div class="msm-render-header">
                                                <span class="msm-render-provider">
                                                    @if($videoProvider === 'multitalk')
                                                        🎤 Multitalk
                                                    @else
                                                        🎬 MiniMax
                                                    @endif
                                                </span>
                                                <button wire:click.stop="resetShotVideo({{ $multiShotSceneIndex }}, {{ $shotIndex }})" class="msm-reset-btn" title="{{ __('Reset stuck job') }}">🔄</button>
                                            </div>
                                            <div class="msm-render-times">
                                                <span class="msm-elapsed">{{ $elapsedMin }}:{{ str_pad($elapsedSecRem, 2, '0', STR_PAD_LEFT) }}</span>
                                                <span class="msm-remaining">~{{ $remainingMin }}:{{ str_pad($remainingSecRem, 2, '0', STR_PAD_LEFT) }} {{ __('left') }}</span>
                                            </div>
                                            <div class="msm-progress-bar"><div style="width: {{ $progressPct }}%;"></div></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                            {{-- Next Button --}}
                            <button class="msm-carousel-nav next"
                                    x-on:click="next()"
                                    x-bind:disabled="currentIndex === totalShots - 1"
                                    title="{{ __('Next shot') }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>

                        {{-- Carousel Indicators --}}
                        <div class="msm-carousel-indicators">
                            @foreach($decomposed['shots'] as $dotIdx => $dotShot)
                                <button class="msm-carousel-dot"
                                        x-bind:class="{ 'active': currentIndex === {{ $dotIdx }} }"
                                        x-on:click="scrollToShot({{ $dotIdx }})"
                                        title="{{ __('Shot') }} {{ $dotIdx + 1 }}">
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Collage Region Assignment Panel --}}
                    @if($collage && $collage['status'] === 'ready')
                        <div class="msm-assign-panel" x-data="{ selectedRegion: null, selectedPage: {{ $currentPage }} }" x-on:region-selected.window="selectedRegion = $event.detail.regionIndex; selectedPage = $event.detail.pageIndex">
                            <div class="msm-assign-header">
                                <span>{{ __('Assign selected region to shot:') }}</span>
                            </div>
                            <div class="msm-assign-btns">
                                @foreach($decomposed['shots'] as $shotIdx => $shotData)
                                    @php
                                        $shotSrc = $collage['shotSources'][$shotIdx] ?? null;
                                        $shotHasReg = $shotSrc !== null;
                                    @endphp
                                    <button type="button"
                                            x-on:click="if (selectedRegion !== null) { $wire.assignCollageRegionToShot({{ $multiShotSceneIndex }}, selectedPage, selectedRegion, {{ $shotIdx }}) }"
                                            x-bind:disabled="selectedRegion === null"
                                            class="{{ $shotHasReg ? 'assigned' : '' }}">
                                        {{ __('Shot') }} {{ $shotIdx + 1 }}
                                        @if($shotHasReg)
                                            <small>(P{{ $shotSrc['pageIndex'] + 1 }}R{{ $shotSrc['regionIndex'] + 1 }})</small>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            </main>
        @endif
    @endif
</div>
@endif

{{-- Video Model Selector --}}
@if($showVideoModelSelector ?? false)
<div class="msm-popup-overlay" wire:click.self="closeVideoModelSelector">
    <div class="msm-popup msm-popup-lg">
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
                $hasAudio = !empty($selShot['audioUrl']) && ($selShot['audioStatus'] ?? '') === 'ready';
                $audioGenerating = ($selShot['audioStatus'] ?? '') === 'generating';
                $needsLipSync = $selShot['needsLipSync'] ?? false;
            @endphp

            {{-- MiniMax Option --}}
            <div class="msm-model-opt {{ $curModel === 'minimax' ? 'active' : '' }}" wire:click="setVideoModel('minimax')">
                <div class="msm-radio {{ $curModel === 'minimax' ? 'checked' : '' }}"></div>
                <div><strong>MiniMax</strong><span>{{ __('High quality I2V') }}</span></div>
                <span class="msm-rec">{{ __('Recommended') }}</span>
            </div>

            {{-- Multitalk Option with Audio Controls --}}
            <div class="msm-model-opt msm-model-multitalk {{ $curModel === 'multitalk' ? 'active' : '' }} {{ !$mtAvail ? 'disabled' : '' }}">
                <div class="msm-model-header-row" @if($mtAvail) wire:click="setVideoModel('multitalk')" @endif>
                    <div class="msm-radio {{ $curModel === 'multitalk' ? 'checked' : '' }}"></div>
                    <div>
                        <strong>Multitalk</strong>
                        <span class="msm-badge-lip">Lip-Sync</span>
                    </div>
                    @if(!$mtAvail)<span class="msm-warn">⚠️ {{ __('Not configured') }}</span>@endif
                </div>

                @if($mtAvail)
                    @if($hasAudio && !$showVoiceRegenerateOptions)
                        {{-- Audio Ready - Show status with regenerate option --}}
                        <div class="msm-audio-ready">
                            <span class="msm-audio-status msm-status-ready">✓ {{ __('Audio ready') }} ({{ $selShot['voiceId'] ?? 'voice' }})</span>
                            @if($selShot['audioDuration'] ?? null)
                                <span class="msm-audio-duration">{{ number_format($selShot['audioDuration'], 1) }}s</span>
                            @endif
                            <button type="button" wire:click.stop.prevent="toggleVoiceRegenerateOptions" class="msm-btn-regen-small" title="{{ __('Regenerate with different voice') }}">
                                🔄 {{ __('Regenerate') }}
                            </button>
                        </div>
                    @elseif($hasAudio && $showVoiceRegenerateOptions)
                        {{-- Audio Regenerate Mode - Show voice options --}}
                        <div class="msm-audio-setup msm-audio-regenerate">
                            <div class="msm-regen-header">
                                <span class="msm-regen-title">🔄 {{ __('Regenerate Voiceover') }}</span>
                                <button type="button" wire:click.stop.prevent="toggleVoiceRegenerateOptions" class="msm-btn-cancel-small">✕</button>
                            </div>

                            {{-- Voice Selection --}}
                            <div class="msm-voice-select">
                                <label>
                                    {{ __('Voice') }}
                                    @if($activeTtsProvider === 'kokoro')
                                        <span class="msm-provider-badge msm-provider-kokoro">Kokoro</span>
                                    @else
                                        <span class="msm-provider-badge msm-provider-openai">OpenAI</span>
                                    @endif
                                </label>
                                <select wire:model.live="shotVoiceSelection" class="msm-voice-dropdown">
                                    @foreach($availableTtsVoices as $voiceId => $voiceConfig)
                                        <option value="{{ $voiceId }}">
                                            {{ $voiceConfig['name'] ?? ucfirst($voiceId) }}
                                            ({{ $voiceConfig['accent'] ?? ucfirst($voiceConfig['gender'] ?? 'neutral') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Monologue Preview/Edit --}}
                            <div class="msm-monologue-edit">
                                <label>{{ __('Dialogue/Monologue') }}:</label>
                                <textarea wire:model.blur="shotMonologueEdit"
                                          class="msm-monologue-textarea"
                                          rows="2"
                                          placeholder="{{ __('Leave empty to keep existing text') }}"></textarea>
                            </div>

                            {{-- Regenerate Voice Button --}}
                            <button type="button"
                                    wire:click.stop.prevent="generateShotVoiceover({{ $videoModelSelectorSceneIndex }}, {{ $videoModelSelectorShotIndex }})"
                                    wire:loading.attr="disabled"
                                    wire:target="generateShotVoiceover"
                                    class="msm-btn msm-btn-voice msm-btn-regen">
                                <span wire:loading.remove wire:target="generateShotVoiceover">
                                    🎤 {{ __('Regenerate Voice') }}
                                </span>
                                <span wire:loading wire:target="generateShotVoiceover">
                                    ⏳ {{ __('Generating...') }}
                                </span>
                            </button>
                        </div>
                    @elseif($audioGenerating)
                        {{-- Audio Generating --}}
                        <div class="msm-audio-generating">
                            <div class="msm-spinner-small"></div>
                            <span>{{ __('Generating voiceover...') }}</span>
                        </div>
                    @else
                        {{-- No Audio - Show Generation Options --}}
                        <div class="msm-audio-setup">
                            <span class="msm-audio-hint">{{ __('Lip-sync requires voiceover audio') }}</span>

                            {{-- Voice Selection --}}
                            <div class="msm-voice-select">
                                <label>
                                    {{ __('Voice') }}
                                    @if($activeTtsProvider === 'kokoro')
                                        <span class="msm-provider-badge msm-provider-kokoro">Kokoro</span>
                                    @else
                                        <span class="msm-provider-badge msm-provider-openai">OpenAI</span>
                                    @endif
                                </label>
                                <select wire:model.live="shotVoiceSelection" class="msm-voice-dropdown">
                                    @foreach($availableTtsVoices as $voiceId => $voiceConfig)
                                        <option value="{{ $voiceId }}">
                                            {{ $voiceConfig['name'] ?? ucfirst($voiceId) }}
                                            ({{ $voiceConfig['accent'] ?? ucfirst($voiceConfig['gender'] ?? 'neutral') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Monologue Preview/Edit --}}
                            <div class="msm-monologue-edit">
                                <label>{{ __('Dialogue/Monologue') }}:</label>
                                <textarea wire:model.blur="shotMonologueEdit"
                                          class="msm-monologue-textarea"
                                          rows="2"
                                          placeholder="{{ __('Leave empty to auto-generate from scene context') }}"></textarea>
                            </div>

                            {{-- Generate Voice Button --}}
                            <button type="button"
                                    wire:click.stop.prevent="generateShotVoiceover({{ $videoModelSelectorSceneIndex }}, {{ $videoModelSelectorShotIndex }})"
                                    wire:loading.attr="disabled"
                                    wire:target="generateShotVoiceover"
                                    class="msm-btn msm-btn-voice">
                                <span wire:loading.remove wire:target="generateShotVoiceover">
                                    🎤 {{ __('Generate Voice') }}
                                </span>
                                <span wire:loading wire:target="generateShotVoiceover">
                                    ⏳ {{ __('Generating...') }}
                                </span>
                            </button>
                        </div>
                    @endif

                    @if($needsLipSync && !$hasAudio)
                        <div class="msm-lipsync-hint">
                            💡 {{ __('Character speaks on-screen - generate voiceover for MultiTalk lip-sync') }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Duration Selector --}}
            <div class="msm-dur-selector">
                <label>{{ __('Duration') }}</label>
                <div class="msm-dur-opts">
                    @foreach($this->getAvailableDurations($curModel) as $d)
                        <button wire:click="setVideoModelDuration({{ $d }})" class="{{ $curDur == $d ? 'active' : '' }}">{{ $d }}s</button>
                    @endforeach
                </div>
            </div>

            {{-- Generate Animation Button --}}
            @if($curModel === 'multitalk' && !$hasAudio)
                <button type="button" disabled class="msm-gen-anim-btn msm-btn-disabled">
                    🎬 {{ __('Generate voice first to use Multitalk') }}
                </button>
            @else
                <button type="button" wire:click.stop.prevent="confirmVideoModelAndGenerate" wire:loading.attr="disabled" wire:target="confirmVideoModelAndGenerate" class="msm-gen-anim-btn">
                    <span wire:loading.remove wire:target="confirmVideoModelAndGenerate">🎬 {{ __('Generate Animation') }}</span>
                    <span wire:loading wire:target="confirmVideoModelAndGenerate">⏳</span>
                </button>
            @endif
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

/* Scene Analysis - New Intelligent UI */
.msm-scene-analysis { background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border: 1px solid rgba(139,92,246,0.2); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; }
.msm-analysis-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
.msm-analysis-icon { font-size: 1.5rem; }
.msm-analysis-title { color: #fff; font-size: 1.1rem; font-weight: 600; }
.msm-analysis-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
.msm-analysis-item { display: flex; flex-direction: column; gap: 0.25rem; }
.msm-analysis-label { color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.msm-analysis-value { color: #fff; font-size: 1rem; font-weight: 600; }
.msm-analysis-value small { color: rgba(255,255,255,0.5); font-weight: 400; margin-left: 0.25rem; }
.msm-scene-type-action { color: #ef4444; }
.msm-scene-type-dialogue { color: #3b82f6; }
.msm-scene-type-emotional { color: #ec4899; }
.msm-scene-type-establishing { color: #10b981; }
.msm-scene-type-default { color: #8b5cf6; }
.msm-recommendation-box { background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(6,182,212,0.1)); border: 1px solid rgba(139,92,246,0.3); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; text-align: center; }
.msm-recommendation-header { display: flex; align-items: baseline; justify-content: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.msm-recommendation-count { font-size: 2.5rem; font-weight: 800; color: #fff; text-shadow: 0 0 20px rgba(139,92,246,0.5); }
.msm-recommendation-label { color: rgba(255,255,255,0.7); font-size: 1rem; font-weight: 500; }
.msm-recommendation-summary { color: rgba(255,255,255,0.6); font-size: 0.85rem; line-height: 1.5; margin: 0; }
.msm-pacing-adjuster { margin-top: 1rem; }
.msm-pacing-adjuster label { display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.75rem; }
.msm-pacing-buttons { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.msm-pacing-buttons button { padding: 0.75rem 0.5rem; border-radius: 10px; border: 2px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.8); cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease; }
.msm-pacing-buttons button:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
.msm-pacing-buttons button.active { border-color: rgba(139,92,246,0.6); background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(168,85,247,0.2)); color: #fff; box-shadow: 0 4px 15px rgba(139,92,246,0.25); }
.msm-pacing-hint { color: rgba(255,255,255,0.4); font-size: 0.75rem; margin-top: 0.5rem; text-align: center; }

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
.msm-action-btn.amber { background: linear-gradient(135deg, rgba(245,158,11,0.3), rgba(251,191,36,0.2)); border: 1px solid rgba(245,158,11,0.5); box-shadow: 0 2px 12px rgba(245,158,11,0.2); }
.msm-action-btn.amber:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(245,158,11,0.35); }
.msm-lipsync-status { background: rgba(251,191,36,0.2); color: #fbbf24; padding: 0.35rem 0.65rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; border: 1px solid rgba(251,191,36,0.3); cursor: help; }
.msm-spacer { flex: 1; }
.msm-reset-btn { padding: 0.5rem 0.8rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.35); border-radius: 8px; color: #ef4444; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; }
.msm-reset-btn:hover { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.5); }

.msm-timeline { display: flex; height: 48px; margin: 1rem 1.5rem; background: linear-gradient(135deg, rgba(0,0,0,0.55), rgba(10,10,20,0.45)); border-radius: 12px; overflow: hidden; box-shadow: inset 0 3px 10px rgba(0,0,0,0.35), 0 3px 12px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); }
.msm-timeline-seg { display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.95rem; font-weight: 700; border-right: 1px solid rgba(255,255,255,0.12); background: linear-gradient(180deg, rgba(139,92,246,0.45), rgba(139,92,246,0.3)); cursor: pointer; transition: all 0.25s ease; position: relative; }
.msm-timeline-seg.img { background: linear-gradient(180deg, rgba(16,185,129,0.6), rgba(16,185,129,0.4)); }
.msm-timeline-seg.vid { background: linear-gradient(180deg, rgba(6,182,212,0.7), rgba(59,130,246,0.5)); }
.msm-timeline-seg:hover { filter: brightness(1.3); transform: scaleY(1.1); box-shadow: 0 0 20px rgba(139,92,246,0.4); z-index: 5; }
.msm-timeline-seg.active {
    filter: brightness(1.4);
    transform: scaleY(1.15);
    box-shadow: 0 4px 20px rgba(139,92,246,0.5), inset 0 -3px 0 rgba(255,255,255,0.3);
    z-index: 10;
}
.msm-timeline-seg.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid rgba(139,92,246,0.8);
}

.msm-shot-grid {
    flex: 1;
    padding: 1rem 0;
    overflow-x: auto;
    overflow-y: hidden;
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    scroll-padding: 0 1.5rem;
    -webkit-overflow-scrolling: touch;
}

/* Hide scrollbar but keep functionality */
.msm-shot-grid::-webkit-scrollbar { height: 0; width: 0; }
.msm-shot-grid { scrollbar-width: none; -ms-overflow-style: none; }

/* Carousel navigation wrapper */
.msm-carousel-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    min-height: 0;
}

.msm-carousel-container {
    flex: 1;
    display: flex;
    align-items: flex-start;
    position: relative;
    min-height: 0;
    padding: 0 3.5rem;
}

/* Navigation arrows */
.msm-carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 20;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.9), rgba(168, 85, 247, 0.8));
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
}
.msm-carousel-nav:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 30px rgba(139, 92, 246, 0.6);
}
.msm-carousel-nav:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    transform: translateY(-50%);
}
.msm-carousel-nav.prev { left: 0.5rem; }
.msm-carousel-nav.next { right: 0.5rem; }

/* Carousel indicators */
.msm-carousel-indicators {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 0;
    background: linear-gradient(180deg, transparent, rgba(0,0,0,0.2));
}
.msm-carousel-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
}
.msm-carousel-dot:hover { background: rgba(255, 255, 255, 0.5); transform: scale(1.2); }
.msm-carousel-dot.active {
    background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    width: 28px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(139, 92, 246, 0.5);
}

/* Shot Card - Modern Glass Card - COMPACT NO-SCROLL */
.msm-shot-card {
    flex: 0 0 340px;
    min-width: 300px;
    max-width: 380px;
    height: fit-content;
    background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 2px solid rgba(255,255,255,0.12);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    scroll-snap-align: center;
    display: flex;
    flex-direction: column;
}
.msm-shot-card:first-child { margin-left: 1.5rem; }
.msm-shot-card:last-child { margin-right: 1.5rem; }

.msm-shot-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 16px 50px rgba(0,0,0,0.4);
    border-color: rgba(139, 92, 246, 0.5);
}
.msm-shot-card.has-video {
    border-color: rgba(6,182,212,0.6);
    box-shadow: 0 8px 32px rgba(6,182,212,0.25);
}
.msm-shot-card.has-video:hover {
    box-shadow: 0 16px 50px rgba(6,182,212,0.35);
    border-color: rgba(6,182,212,0.8);
}
.msm-shot-card.selected {
    border-color: rgba(139,92,246,0.8) !important;
    box-shadow: 0 0 0 4px rgba(139,92,246,0.3), 0 16px 50px rgba(139,92,246,0.3);
    transform: scale(1.02);
}

.msm-shot-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.65rem; background: linear-gradient(180deg, rgba(0,0,0,0.4), rgba(0,0,0,0.25)); border-bottom: 1px solid rgba(255,255,255,0.08); }
.msm-shot-num { background: linear-gradient(135deg, rgba(139,92,246,0.7), rgba(168,85,247,0.6)); color: #fff; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; box-shadow: 0 2px 8px rgba(139,92,246,0.4); min-width: 24px; text-align: center; }
.msm-shot-type { color: rgba(255,255,255,0.85); font-size: 0.8rem; font-weight: 600; }
.msm-shot-meta { margin-left: auto; display: flex; align-items: center; gap: 0.35rem; }
.msm-badge-dialog { background: linear-gradient(135deg, rgba(251,191,36,0.5), rgba(245,158,11,0.4)); color: #fcd34d; padding: 0.15rem 0.35rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
.msm-badge-voiceover { background: linear-gradient(135deg, rgba(100,116,139,0.5), rgba(71,85,105,0.4)); color: #cbd5e1; padding: 0.15rem 0.35rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
.msm-dur { font-size: 0.8rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 4px; }
.msm-dur.green { color: #4ade80; background: rgba(34,197,94,0.2); }
.msm-dur.yellow { color: #fde047; background: rgba(234,179,8,0.2); }
.msm-dur.blue { color: #60a5fa; background: rgba(59,130,246,0.2); }
.msm-monologue-indicator { display: none; } /* Hide long monologue text in header */

.msm-shot-preview {
    position: relative;
    min-height: 120px;
    max-height: 160px;
    background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(10,10,20,0.4));
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-direction: column;
    overflow: hidden;
}
.msm-shot-preview img { width: 100%; height: 100%; object-fit: cover; }
.msm-shot-preview img.dimmed { filter: brightness(0.35); }
.msm-shot-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(20,20,40,0.5)); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.25s ease; backdrop-filter: blur(3px); flex-direction: column; gap: 0.3rem; }
.msm-shot-preview:hover .msm-shot-overlay { opacity: 1; }
.msm-shot-overlay span { font-size: 1.8rem; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.6)); }
.msm-shot-overlay small { font-size: 0.75rem; color: rgba(255,255,255,0.9); font-weight: 500; }
.msm-shot-icons { position: absolute; bottom: 0.4rem; right: 0.4rem; display: flex; gap: 0.3rem; }
.msm-icon-img, .msm-icon-vid { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.4); }
.msm-icon-img { background: linear-gradient(135deg, #10b981, #059669); }
.msm-icon-vid { background: linear-gradient(135deg, #06b6d4, #0284c7); }

.msm-shot-empty { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.msm-shot-empty > span { font-size: 3rem; color: rgba(255,255,255,0.25); }
.msm-shot-empty button { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(168,85,247,0.3)); border: 1px solid rgba(139,92,246,0.6); border-radius: 10px; color: #fff; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-shot-empty button:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(139,92,246,0.35); }
.msm-collage-btn { background: linear-gradient(135deg, rgba(236,72,153,0.5), rgba(139,92,246,0.45)) !important; border-color: rgba(236,72,153,0.6) !important; box-shadow: 0 3px 12px rgba(236,72,153,0.3); }
.msm-collage-badge { position: absolute; top: 0.6rem; left: 0.6rem; background: linear-gradient(135deg, #ec4899, #db2777); color: #fff; padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; box-shadow: 0 3px 12px rgba(236,72,153,0.45); }

.msm-vid-progress { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); }
.msm-vid-progress span { color: #67e8f9; font-size: 1rem; font-weight: 600; margin-top: 0.75rem; text-shadow: 0 2px 10px rgba(0,0,0,0.6); }

.msm-shot-controls { padding: 0.5rem 0.65rem; display: flex; flex-direction: column; gap: 0.4rem; background: linear-gradient(180deg, transparent, rgba(0,0,0,0.2)); }
.msm-dur-btns { display: flex; gap: 0.25rem; }
.msm-dur-btns button { flex: 1; padding: 0.35rem 0.3rem; font-size: 0.75rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: rgba(255,255,255,0.85); cursor: pointer; transition: all 0.2s ease; }
.msm-dur-btns button:hover { background: rgba(255,255,255,0.12); }
.msm-dur-btns button.active { font-weight: 700; }
.msm-dur-btns button.active.green { background: linear-gradient(135deg, rgba(34,197,94,0.45), rgba(22,163,74,0.35)); border-color: rgba(34,197,94,0.7); color: #4ade80; }
.msm-dur-btns button.active.yellow { background: linear-gradient(135deg, rgba(234,179,8,0.45), rgba(202,138,4,0.35)); border-color: rgba(234,179,8,0.7); color: #fde047; }
.msm-dur-btns button.active.blue { background: linear-gradient(135deg, rgba(59,130,246,0.45), rgba(37,99,235,0.35)); border-color: rgba(59,130,246,0.7); color: #60a5fa; }

.msm-action-row { display: flex; gap: 0.25rem; }
.msm-play-btn { flex: 1; padding: 0.4rem; background: linear-gradient(135deg, rgba(16,185,129,0.4), rgba(6,182,212,0.35)); border: 1px solid rgba(16,185,129,0.6); border-radius: 6px; color: #fff; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-play-btn:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
.msm-capture-btn { padding: 0.4rem 0.5rem; background: rgba(16,185,129,0.3); border: 1px solid rgba(16,185,129,0.5); border-radius: 6px; color: #fff; font-size: 0.7rem; cursor: pointer; transition: all 0.2s ease; }
.msm-capture-btn:hover { background: rgba(16,185,129,0.4); }
.msm-animate-btn { flex: 1; padding: 0.4rem; background: linear-gradient(135deg, rgba(6,182,212,0.4), rgba(59,130,246,0.35)); border: 1px solid rgba(6,182,212,0.6); border-radius: 6px; color: #fff; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-animate-btn:hover { box-shadow: 0 4px 12px rgba(6,182,212,0.35); }
.msm-face-btn { flex: 1; padding: 0.4rem; background: linear-gradient(135deg, rgba(245,158,11,0.35), rgba(251,191,36,0.3)); border: 1px solid rgba(245,158,11,0.6); border-radius: 6px; color: #fbbf24; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-face-btn:hover { box-shadow: 0 4px 12px rgba(245,158,11,0.35); background: linear-gradient(135deg, rgba(245,158,11,0.45), rgba(251,191,36,0.4)); }

.msm-render-status { text-align: center; padding: 0.65rem; background: rgba(6,182,212,0.12); border-radius: 8px; position: relative; }
.msm-render-status span { font-size: 0.85rem; color: #67e8f9; font-weight: 600; }
.msm-reset-btn { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: rgba(239,68,68,0.25); border: 1px solid rgba(239,68,68,0.45); border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; cursor: pointer; color: #fca5a5; transition: all 0.2s ease; }
.msm-reset-btn:hover { background: rgba(239,68,68,0.45); border-color: rgba(239,68,68,0.65); }
.msm-progress-bar { height: 6px; background: rgba(255,255,255,0.12); border-radius: 6px; overflow: hidden; margin-top: 0.5rem; }
.msm-progress-bar div { height: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6, #8b5cf6); animation: msm-progress 1.5s infinite linear; }

/* Enhanced Render Status */
.msm-render-status-enhanced { padding: 0.75rem; }
.msm-render-status-enhanced .msm-render-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.msm-render-status-enhanced .msm-render-provider { font-size: 0.8rem; font-weight: 600; color: #a5b4fc; background: rgba(99,102,241,0.25); padding: 4px 10px; border-radius: 6px; }
.msm-render-status-enhanced .msm-reset-btn { position: static; transform: none; padding: 4px 8px; font-size: 0.75rem; }
.msm-render-status-enhanced .msm-render-times { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.msm-render-status-enhanced .msm-elapsed { font-size: 0.95rem; font-weight: 700; color: #fcd34d; font-family: monospace; }
.msm-render-status-enhanced .msm-remaining { font-size: 0.8rem; color: #9ca3af; font-family: monospace; }
.msm-render-status-enhanced .msm-progress-bar { margin-top: 0; }
.msm-render-status-enhanced .msm-progress-bar div { animation: none; transition: width 0.5s ease; }

/* Spinner - Modern Loading */
.msm-spinner { width: 44px; height: 44px; border: 3px solid rgba(255,255,255,0.18); border-radius: 50%; animation: msm-spin 0.9s cubic-bezier(0.5, 0, 0.5, 1) infinite; }
.msm-spinner.pink { border-top-color: #ec4899; border-right-color: rgba(236,72,153,0.4); }
.msm-spinner.purple { border-top-color: #8b5cf6; border-right-color: rgba(139,92,246,0.4); width: 40px; height: 40px; }
.msm-spinner.cyan { border-top-color: #06b6d4; border-right-color: rgba(6,182,212,0.4); width: 48px; height: 48px; }

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
.msm-gen-anim-btn.msm-btn-disabled { opacity: 0.5; cursor: not-allowed; background: rgba(100,100,120,0.5); box-shadow: none; }
.msm-gen-anim-btn.msm-btn-disabled:hover { transform: none; }

/* Multitalk Audio Controls */
.msm-popup-lg { width: 440px; }
.msm-model-multitalk { flex-direction: column; align-items: stretch; }
.msm-model-header-row { display: flex; align-items: center; gap: 0.75rem; cursor: pointer; width: 100%; }
.msm-badge-lip { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600; margin-left: 0.5rem; }
.msm-audio-ready { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.75rem; padding: 0.6rem 0.8rem; background: rgba(16,185,129,0.12); border-radius: 8px; border: 1px solid rgba(16,185,129,0.3); flex-wrap: wrap; }
.msm-audio-status { font-size: 0.85rem; font-weight: 500; }
.msm-status-ready { color: #10b981; }
.msm-audio-duration { color: rgba(255,255,255,0.6); font-size: 0.8rem; }
.msm-btn-regen-small { margin-left: auto; padding: 0.35rem 0.6rem; background: rgba(251,191,36,0.2); border: 1px solid rgba(251,191,36,0.4); border-radius: 6px; color: #fbbf24; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; }
.msm-btn-regen-small:hover { background: rgba(251,191,36,0.3); transform: translateY(-1px); }
.msm-audio-regenerate { background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.25); border-radius: 8px; padding: 0.75rem; }
.msm-regen-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
.msm-regen-title { color: #fbbf24; font-size: 0.9rem; font-weight: 600; }
.msm-btn-cancel-small { padding: 0.25rem 0.5rem; background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.3); border-radius: 4px; color: #ef4444; font-size: 0.75rem; cursor: pointer; }
.msm-btn-cancel-small:hover { background: rgba(239,68,68,0.3); }
.msm-btn-regen { background: linear-gradient(135deg, rgba(251,191,36,0.3), rgba(245,158,11,0.25)) !important; border-color: rgba(251,191,36,0.5) !important; }
.msm-btn-regen:hover { background: linear-gradient(135deg, rgba(251,191,36,0.4), rgba(245,158,11,0.35)) !important; box-shadow: 0 4px 15px rgba(251,191,36,0.25) !important; }
.msm-audio-generating { display: flex; align-items: center; gap: 0.6rem; margin-top: 0.75rem; padding: 0.6rem 0.8rem; background: rgba(139,92,246,0.12); border-radius: 8px; border: 1px solid rgba(139,92,246,0.3); color: #a78bfa; font-size: 0.85rem; }
.msm-spinner-small { width: 16px; height: 16px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: msm-spin 0.8s linear infinite; }
.msm-audio-setup { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.08); }
.msm-audio-hint { display: block; color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-bottom: 0.75rem; }
.msm-voice-select { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem; }
.msm-voice-select label { color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 500; white-space: nowrap; display: flex; align-items: center; gap: 0.5rem; }
.msm-provider-badge { font-size: 0.65rem; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.msm-provider-kokoro { background: linear-gradient(135deg, rgba(236,72,153,0.3), rgba(168,85,247,0.3)); border: 1px solid rgba(236,72,153,0.5); color: #f472b6; }
.msm-provider-openai { background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); color: #34d399; }
.msm-voice-dropdown { flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 0.5rem 0.75rem; color: white; font-size: 0.85rem; cursor: pointer; }
.msm-voice-dropdown:focus { border-color: rgba(139,92,246,0.5); outline: none; }
.msm-monologue-edit { margin-bottom: 0.75rem; }
.msm-monologue-edit label { display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.35rem; }
.msm-monologue-textarea { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 0.5rem 0.75rem; color: white; font-size: 0.8rem; resize: vertical; min-height: 50px; }
.msm-monologue-textarea:focus { border-color: rgba(139,92,246,0.5); outline: none; }
.msm-monologue-textarea::placeholder { color: rgba(255,255,255,0.35); }
.msm-btn-voice { width: 100%; padding: 0.65rem; background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(168,85,247,0.25)); border: 1px solid rgba(139,92,246,0.5); border-radius: 8px; color: white; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.msm-btn-voice:hover { background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(168,85,247,0.35)); transform: translateY(-1px); box-shadow: 0 4px 15px rgba(139,92,246,0.25); }
.msm-btn-voice:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.msm-lipsync-hint { margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: rgba(251,191,36,0.1); border-radius: 6px; border: 1px solid rgba(251,191,36,0.2); color: #fbbf24; font-size: 0.8rem; }

/* Animations */
@keyframes msm-spin { to { transform: rotate(360deg); } }
@keyframes msm-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

/* Scrollbar Styling (Collage content only - carousel has hidden scrollbar) */
.msm-collage-content::-webkit-scrollbar { width: 8px; }
.msm-collage-content::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 4px; }
.msm-collage-content::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.3); border-radius: 4px; }
.msm-collage-content::-webkit-scrollbar-thumb:hover { background: rgba(139,92,246,0.5); }

/* AI Analysis Badges in Header */
.msm-ai-badge { background: linear-gradient(135deg, rgba(16,185,129,0.35), rgba(6,182,212,0.3)); color: #10b981; padding: 0.15rem 0.45rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600; cursor: help; }
.msm-var-badge { background: rgba(139,92,246,0.25); color: #a78bfa; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem; }
.msm-lipsync-badge { background: rgba(251,191,36,0.25); color: #fbbf24; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem; }
/* Speech Type Badges - indicates whether character lips move on screen */
.msm-speech-badge { padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600; cursor: help; }
.msm-speech-narrator { background: rgba(100,116,139,0.3); color: #94a3b8; } /* Slate - external voiceover, no lip-sync */
.msm-speech-internal { background: rgba(168,85,247,0.25); color: #c084fc; } /* Purple - thoughts, no lip-sync */
.msm-speech-monologue { background: rgba(236,72,153,0.25); color: #f472b6; } /* Pink - speaking aloud, NEEDS lip-sync */
.msm-speech-dialogue { background: rgba(59,130,246,0.25); color: #60a5fa; } /* Blue - conversation, NEEDS lip-sync */
.msm-ai-reasoning { font-size: 0.7rem; color: rgba(255,255,255,0.6); background: rgba(16,185,129,0.1); padding: 0.35rem 0.6rem; border-radius: 0.35rem; margin-top: 0.5rem; border-left: 3px solid rgba(16,185,129,0.5); cursor: help; }

/* Shot Card Enhanced Badges */
.msm-badge-lipsync { font-size: 0.85rem; cursor: help; }
.msm-badge-ai { background: rgba(16,185,129,0.35); padding: 0.2rem 0.4rem; border-radius: 5px; font-size: 0.75rem; }
.msm-shot-card.transferred { border-color: rgba(16,185,129,0.5) !important; }
.msm-shot-card.selected { border-color: rgba(139,92,246,0.7) !important; box-shadow: 0 0 0 3px rgba(139,92,246,0.3); }
.msm-transfer-badge { position: absolute; top: 0.6rem; right: 0.6rem; background: rgba(16,185,129,0.95); color: #fff; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
.msm-from-collage { position: absolute; bottom: 2rem; left: 0.6rem; background: rgba(236,72,153,0.85); color: #fff; padding: 0.25rem 0.5rem; border-radius: 5px; font-size: 0.7rem; font-weight: 500; }
.msm-hover-hint { position: absolute; bottom: 0.75rem; left: 50%; transform: translateX(-50%); background: rgba(139,92,246,0.9); color: #fff; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; opacity: 0; transition: opacity 0.2s ease; white-space: nowrap; font-weight: 500; }
.msm-shot-preview:hover .msm-hover-hint { opacity: 1; }

/* Shot Info Row */
.msm-shot-info { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.85rem; background: rgba(0,0,0,0.25); border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.8rem; }
.msm-camera { color: rgba(255,255,255,0.75); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%; font-weight: 500; }
.msm-tokens { color: #fbbf24; font-weight: 700; font-size: 0.85rem; background: rgba(251,191,36,0.15); padding: 0.25rem 0.5rem; border-radius: 6px; }

/* Frame Status */
.msm-frame-status { padding: 0.5rem 0.85rem; text-align: center; min-height: 2rem; display: flex; align-items: center; justify-content: center; }
.msm-status-ok { font-size: 0.8rem; color: #10b981; background: rgba(16,185,129,0.2); padding: 0.35rem 0.65rem; border-radius: 6px; font-weight: 500; }
.msm-status-wait { font-size: 0.8rem; color: #f59e0b; background: rgba(245,158,11,0.2); padding: 0.35rem 0.65rem; border-radius: 6px; font-weight: 500; }

/* Shot Overlay Enhanced */
.msm-shot-overlay { flex-direction: column; gap: 0.25rem; }
.msm-shot-overlay small { font-size: 0.6rem; opacity: 0.8; }

/* Collage Region Assignment Panel */
.msm-assign-panel { background: linear-gradient(135deg, rgba(236,72,153,0.08), rgba(139,92,246,0.05)); border: 1px solid rgba(236,72,153,0.25); border-radius: 0.75rem; padding: 0.75rem; margin-top: 0.75rem; }
.msm-assign-header { font-size: 0.7rem; color: rgba(255,255,255,0.6); margin-bottom: 0.5rem; }
.msm-assign-btns { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.msm-assign-btns button { padding: 0.35rem 0.6rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.35rem; color: #fff; font-size: 0.7rem; cursor: pointer; transition: all 0.2s ease; }
.msm-assign-btns button:hover:not(:disabled) { background: rgba(139,92,246,0.35); }
.msm-assign-btns button:disabled { opacity: 0.4; cursor: not-allowed; }
.msm-assign-btns button.assigned { background: rgba(16,185,129,0.3); border-color: rgba(16,185,129,0.5); }
.msm-assign-btns button small { font-size: 0.55rem; opacity: 0.7; margin-left: 0.2rem; }

/* Monologue/Dialogue Indicators */
.msm-badge-audio { background: linear-gradient(135deg, rgba(16,185,129,0.45), rgba(6,182,212,0.4)); color: #10b981; padding: 0.2rem 0.4rem; border-radius: 5px; font-size: 0.8rem; }
.msm-badge-audio-gen { background: rgba(139,92,246,0.35); color: #a78bfa; padding: 0.2rem 0.4rem; border-radius: 5px; font-size: 0.75rem; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.msm-monologue-indicator { padding: 0.35rem 0.75rem; background: rgba(236,72,153,0.12); border-top: 1px solid rgba(236,72,153,0.2); }

/* Speech Type Badges for Shot Cards */
.msm-speech-badge-row { padding: 0.15rem 0.5rem !important; }
.msm-speech-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.12rem 0.35rem;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.msm-speech-type-badge.dialogue {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.35), rgba(139, 92, 246, 0.3));
    color: #f472b6;
    border: 1px solid rgba(236, 72, 153, 0.4);
}
.msm-speech-type-badge.monologue {
    background: rgba(139, 92, 246, 0.25);
    color: #c4b5fd;
    border: 1px solid rgba(139, 92, 246, 0.4);
}
.msm-speech-type-badge.narrator {
    background: rgba(100, 116, 139, 0.25);
    color: #94a3b8;
    border: 1px solid rgba(100, 116, 139, 0.4);
}
.msm-speech-type-badge.silent {
    background: rgba(100, 116, 139, 0.15);
    color: rgba(148, 163, 184, 0.7);
    border: 1px solid rgba(100, 116, 139, 0.3);
}
.msm-monologue-preview { color: rgba(236,72,153,0.9); font-size: 0.75rem; font-style: italic; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Re-Animate Button */
.msm-reanimate-btn { background: rgba(139,92,246,0.25) !important; border: 1px solid rgba(139,92,246,0.5) !important; padding: 0.5rem 0.65rem !important; font-size: 0.85rem !important; border-radius: 8px !important; cursor: pointer; transition: all 0.2s ease; }
.msm-reanimate-btn:hover { background: rgba(139,92,246,0.4) !important; transform: translateY(-1px); }
.msm-reanimate-btn.msm-needs-reanimate { background: rgba(245,158,11,0.3) !important; border-color: rgba(245,158,11,0.6) !important; animation: pulse 2s infinite; }

/* Wrong Model Warning */
.msm-wrong-model-hint { background: rgba(245,158,11,0.18); color: #fbbf24; font-size: 0.75rem; padding: 0.4rem 0.65rem; text-align: center; border-top: 1px solid rgba(245,158,11,0.3); font-weight: 500; }

/* Responsive Adjustments - Carousel Mode */
@media (max-width: 1400px) {
    .msm-shot-card { flex: 0 0 320px; min-width: 280px; max-width: 360px; }
}

@media (max-width: 1100px) {
    .msm-shot-card { flex: 0 0 300px; min-width: 260px; max-width: 340px; }
    .msm-shot-preview { min-height: 100px; max-height: 140px; }
    .msm-carousel-container { padding: 0 3rem; }
}

@media (max-width: 900px) {
    .msm-split-panel { grid-template-columns: 1fr !important; grid-template-rows: auto auto 1fr; }
    .msm-collage-panel { max-height: 40vh; border-bottom: 1px solid rgba(139,92,246,0.25); }
    .msm-resize-handle { display: none; }
    .msm-shot-card { flex: 0 0 280px; min-width: 250px; max-width: 320px; }
    .msm-shot-preview { min-height: 90px; max-height: 120px; }
    .msm-carousel-container { padding: 0 2.5rem; }
    .msm-carousel-nav { width: 36px; height: 36px; font-size: 1rem; }
    .msm-carousel-nav.prev { left: 0.25rem; }
    .msm-carousel-nav.next { right: 0.25rem; }
    .msm-shot-info { flex-direction: column; gap: 0.25rem; align-items: flex-start; }
    .msm-camera { max-width: 100%; }
    .msm-action-bar { padding: 0.5rem 0.65rem; gap: 0.35rem; flex-wrap: wrap; }
    .msm-action-btn { padding: 0.35rem 0.5rem; font-size: 0.7rem; }
}

@media (max-width: 600px) {
    .msm-shot-card { flex: 0 0 calc(100vw - 4rem); min-width: 240px; max-width: 320px; }
    .msm-shot-card:first-child { margin-left: 0.75rem; }
    .msm-shot-card:last-child { margin-right: 0.75rem; }
    .msm-carousel-container { padding: 0 1.5rem; }
    .msm-carousel-nav { width: 32px; height: 32px; }
    .msm-carousel-nav.prev { left: 0.1rem; }
    .msm-carousel-nav.next { right: 0.1rem; }
    .msm-shot-header { padding: 0.4rem 0.5rem; }
    .msm-shot-num { font-size: 0.75rem; padding: 0.15rem 0.4rem; }
    .msm-shot-type { font-size: 0.7rem; }
    .msm-shot-controls { padding: 0.4rem 0.5rem; }
    .msm-timeline { height: 36px; margin: 0.5rem 0.75rem; }
    .msm-timeline-seg { font-size: 0.7rem; }
    .msm-carousel-indicators { gap: 0.25rem; padding: 0.4rem 0; }
    .msm-carousel-dot { width: 6px; height: 6px; }
    .msm-carousel-dot.active { width: 18px; }
}

/* PHASE 6: Shot Type Badges - ENHANCED */
.vw-shot-badge { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.15rem 0.35rem; border-radius: 4px; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; }
.vw-shot-badges { display: flex; flex-wrap: wrap; gap: 0.2rem; padding: 0.2rem 0.5rem !important; }
.vw-shot-badge-xcu { background: rgba(239, 68, 68, 0.3); color: #f87171; }
.vw-shot-badge-cu { background: rgba(249, 115, 22, 0.3); color: #fb923c; }
.vw-shot-badge-mcu { background: rgba(245, 158, 11, 0.3); color: #fbbf24; }
.vw-shot-badge-med { background: rgba(34, 197, 94, 0.3); color: #4ade80; }
.vw-shot-badge-wide { background: rgba(59, 130, 246, 0.3); color: #60a5fa; }
.vw-shot-badge-est { background: rgba(99, 102, 241, 0.3); color: #818cf8; }
.vw-shot-badge-ots { background: rgba(139, 92, 246, 0.3); color: #a78bfa; }
.vw-shot-badge-reaction { background: rgba(236, 72, 153, 0.3); color: #f472b6; }
.vw-shot-badge-two-shot { background: rgba(20, 184, 166, 0.3); color: #2dd4bf; }
.vw-shot-badge-movement { background: rgba(168, 162, 158, 0.25); color: #d6d3d1; }
.vw-shot-badge-climax { background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(236, 72, 153, 0.4)); color: #fff; border: 1px solid rgba(139, 92, 246, 0.6); font-weight: 800; }
.vw-shot-badges { display: flex; flex-wrap: wrap; gap: 0.4rem; margin: 0.4rem 0; padding: 0 0.75rem; }

/* Enhanced Status Badges for Multi-Shot Modal */
.vw-status-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
.vw-status-pending { background: rgba(168, 162, 158, 0.25); color: #d6d3d1; }
.vw-status-generating { background: rgba(245, 158, 11, 0.25); color: #fbbf24; animation: pulse 1.5s ease-in-out infinite; }
.vw-status-complete, .vw-status-ready { background: rgba(34, 197, 94, 0.25); color: #4ade80; }
.vw-status-error { background: rgba(239, 68, 68, 0.25); color: #f87171; }
.vw-status-badge svg { width: 12px; height: 12px; }

/* Enhanced Intensity Bar */
.vw-intensity-bar { height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; overflow: hidden; margin: 0.5rem 0.75rem; }
.vw-intensity-fill { height: 100%; border-radius: 3px; transition: width 0.3s ease; }
.vw-intensity-low { background: linear-gradient(90deg, rgba(59, 130, 246, 0.9), rgba(96, 165, 250, 0.8)); }
.vw-intensity-medium { background: linear-gradient(90deg, rgba(245, 158, 11, 0.9), rgba(251, 191, 36, 0.8)); }
.vw-intensity-high { background: linear-gradient(90deg, rgba(239, 68, 68, 0.9), rgba(248, 113, 113, 0.8)); }
.vw-intensity-climax { background: linear-gradient(90deg, rgba(139, 92, 246, 0.95), rgba(236, 72, 153, 0.95)); }

/* Mini Progress Ring */
.vw-mini-progress { width: 20px; height: 20px; position: relative; }
.vw-mini-progress svg { transform: rotate(-90deg); width: 20px; height: 20px; }
.vw-mini-progress-bg { fill: none; stroke: rgba(255, 255, 255, 0.15); stroke-width: 2; }
.vw-mini-progress-fill { fill: none; stroke-width: 2; stroke-linecap: round; transition: stroke-dashoffset 0.3s ease; }
</style>
