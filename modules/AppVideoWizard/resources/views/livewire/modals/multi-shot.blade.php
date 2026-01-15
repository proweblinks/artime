{{-- Multi-Shot Decomposition Modal - Full Screen Layout --}}

{{-- Define Alpine component for video polling --}}
<script>
// Define globally so Alpine can find it
window.multiShotVideoPolling = function() {
    return {
        pollingInterval: null,
        isPolling: false,
        pollCount: 0,
        maxPolls: 120, // Stop after 10 minutes (120 * 5s)
        POLL_INTERVAL: 5000,
        componentDestroyed: false,

        initPolling() {
            console.log('[MultiShot] 🚀 Alpine polling component initialized');
            this.componentDestroyed = false;

            // Check initial state
            this.$nextTick(() => {
                if (this.checkForProcessingVideos()) {
                    console.log('[MultiShot] 📹 Found processing videos on init');
                    this.startPolling();
                }
            });

            // Listen for Livewire events
            this.videoStartedListener = Livewire.on('video-generation-started', (data) => {
                console.log('[MultiShot] 🎬 video-generation-started event received', data);
                if (!this.componentDestroyed) {
                    this.startPolling();
                }
            });

            this.videoCompleteListener = Livewire.on('video-generation-complete', () => {
                console.log('[MultiShot] ✅ video-generation-complete event received');
                this.stopPolling();
            });

            // Listen for modal close event to stop polling
            this.modalCloseListener = Livewire.on('multi-shot-modal-closing', () => {
                console.log('[MultiShot] 🚪 Modal closing - stopping polling');
                this.cleanup();
            });

            // Make available globally for debugging
            window.multiShotPolling = {
                start: () => this.startPolling(),
                stop: () => this.stopPolling(),
                poll: () => this.dispatchPoll(),
                status: () => {
                    console.log('[MultiShot] 📊 Status:', {
                        isPolling: this.isPolling,
                        pollCount: this.pollCount,
                        destroyed: this.componentDestroyed
                    });
                },
                check: () => this.checkForProcessingVideos()
            };

            console.log('[MultiShot] ✅ Ready. Debug: window.multiShotPolling.status()');
        },

        checkForProcessingVideos() {
            const statusTexts = document.body.innerText;
            const hasRendering = statusTexts.includes('Rendering...') || statusTexts.includes('Starting...');
            const processingShots = document.querySelectorAll('[data-video-status="processing"], [data-video-status="generating"]');

            console.log('[MultiShot] 🔍 Check:', { hasRendering, processingCount: processingShots.length });
            return hasRendering || processingShots.length > 0;
        },

        dispatchPoll() {
            if (this.componentDestroyed) {
                console.log('[MultiShot] ⚠️ Component destroyed, skipping poll');
                this.stopPolling();
                return;
            }

            if (this.pollCount >= this.maxPolls) {
                console.log('[MultiShot] ⚠️ Max polls reached (' + this.maxPolls + '), stopping');
                this.stopPolling();
                return;
            }

            this.pollCount++;
            console.log('[MultiShot] 📡 Poll #' + this.pollCount);

            try {
                if (this.$wire) {
                    this.$wire.pollVideoJobs().then((result) => {
                        console.log('[MultiShot] ✅ pollVideoJobs result:', result);
                        if (result && result.pendingJobs === 0) {
                            console.log('[MultiShot] ⚠️ No pending jobs - stopping polling');
                            this.stopPolling();
                        }
                    }).catch((e) => {
                        console.error('[MultiShot] ❌ pollVideoJobs() error:', e);
                        if (e.message && e.message.includes('Could not find')) {
                            console.log('[MultiShot] ⚠️ Component not found, stopping polling');
                            this.stopPolling();
                        }
                    });
                } else {
                    console.error('[MultiShot] ❌ $wire not available - stopping polling');
                    this.stopPolling();
                }
            } catch (e) {
                console.error('[MultiShot] ❌ Poll failed:', e);
                this.stopPolling();
            }
        },

        startPolling() {
            if (this.isPolling) {
                console.log('[MultiShot] ⚠️ Already polling');
                return;
            }

            if (this.componentDestroyed) {
                console.log('[MultiShot] ⚠️ Component destroyed, cannot start polling');
                return;
            }

            this.isPolling = true;
            this.pollCount = 0;
            console.log('[MultiShot] ✅ Starting polling (every 5s)');

            setTimeout(() => {
                if (!this.componentDestroyed) {
                    this.dispatchPoll();
                }
            }, 1000);

            this.pollingInterval = setInterval(() => {
                if (!this.componentDestroyed) {
                    this.dispatchPoll();
                } else {
                    this.stopPolling();
                }
            }, this.POLL_INTERVAL);
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
            this.isPolling = false;
            console.log('[MultiShot] ⏹️ Polling stopped after ' + this.pollCount + ' polls');
        },

        cleanup() {
            this.componentDestroyed = true;
            this.stopPolling();

            if (this.videoStartedListener) {
                this.videoStartedListener();
                this.videoStartedListener = null;
            }
            if (this.videoCompleteListener) {
                this.videoCompleteListener();
                this.videoCompleteListener = null;
            }
            if (this.modalCloseListener) {
                this.modalCloseListener();
                this.modalCloseListener = null;
            }

            console.log('[MultiShot] 🧹 Cleanup complete');
        },

        destroy() {
            this.cleanup();
        }
    };
};
</script>

@if($showMultiShotModal)
<div class="vw-modal-overlay"
     wire:key="multi-shot-modal-{{ $multiShotSceneIndex }}"
     x-data="multiShotVideoPolling()"
     x-init="initPolling()"
     @destroy="cleanup()"
     style="position: fixed; inset: 0; background: rgba(10, 10, 15, 0.98); display: flex; flex-direction: column; z-index: 1000;">

    @php
        $scene = $script['scenes'][$multiShotSceneIndex] ?? null;
        $decomposed = $multiShotMode['decomposedScenes'][$multiShotSceneIndex] ?? null;
        $storyboardScene = $storyboard['scenes'][$multiShotSceneIndex] ?? null;
        $collage = $sceneCollages[$multiShotSceneIndex] ?? null;
        $currentPage = $collage['currentPage'] ?? 0;
        $totalPages = $collage['totalPages'] ?? 1;
        $currentCollage = $collage['collages'][$currentPage] ?? null;
        $currentShots = $currentCollage['shots'] ?? [];
    @endphp

    {{-- Header Bar --}}
    <div style="padding: 1rem 1.5rem; background: linear-gradient(180deg, rgba(30,30,45,0.95), rgba(20,20,30,0.9)); border-bottom: 1px solid rgba(139,92,246,0.3); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                ✂️ {{ __('Multi-Shot Decomposition') }}
            </h2>
            <span style="background: rgba(139,92,246,0.3); color: #a78bfa; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 500;">
                {{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}
            </span>
            @if($decomposed)
                @php
                    $totalDuration = 0;
                    foreach ($decomposed['shots'] as $shot) {
                        $totalDuration += $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                    }
                    $imagesReady = collect($decomposed['shots'])->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->count();
                    $videosReady = collect($decomposed['shots'])->filter(fn($s) => ($s['videoStatus'] ?? '') === 'ready' && !empty($s['videoUrl']))->count();
                @endphp
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-left: 0.5rem;">
                    <span style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">📽️ {{ count($decomposed['shots']) }} {{ __('shots') }}</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">• {{ $totalDuration }}s</span>
                    <span style="color: #10b981; font-size: 0.8rem;">🖼️ {{ $imagesReady }}/{{ count($decomposed['shots']) }}</span>
                    <span style="color: #06b6d4; font-size: 0.8rem;">🎬 {{ $videosReady }}/{{ count($decomposed['shots']) }}</span>
                </div>
            @endif
        </div>
        <button type="button"
                wire:click="closeMultiShotModal"
                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; line-height: 1; border-radius: 0.5rem; transition: all 0.2s;"
                onmouseover="this.style.background='rgba(239,68,68,0.3)'; this.style.borderColor='rgba(239,68,68,0.5)';"
                onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)';">
            &times;
        </button>
    </div>

    @if($scene)
        @if(!$decomposed)
            {{-- PRE-DECOMPOSITION VIEW - Centered --}}
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem;">
                <div style="width: 100%; max-width: 600px;">
                    {{-- Scene Preview --}}
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 1rem; align-items: start;">
                            @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                                <img src="{{ $storyboardScene['imageUrl'] }}"
                                     alt="Scene {{ $multiShotSceneIndex + 1 }}"
                                     style="width: 200px; height: 112px; object-fit: cover; border-radius: 0.5rem;">
                            @else
                                <div style="width: 200px; height: 112px; background: rgba(255,255,255,0.05); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: rgba(255,255,255,0.4); font-size: 2rem;">🎬</span>
                                </div>
                            @endif
                            <div style="flex: 1;">
                                <div style="color: white; font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</div>
                                <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin: 0; line-height: 1.5;">
                                    {{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 200) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Shot Count Selector --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 500;">{{ __('Number of Shots') }}</label>
                        <div style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.5rem;">
                            <button type="button"
                                    wire:click="$set('multiShotCount', 0)"
                                    style="padding: 0.75rem; border-radius: 0.5rem; border: 2px solid {{ $multiShotCount === 0 ? 'rgba(16, 185, 129, 0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $multiShotCount === 0 ? 'linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(6, 182, 212, 0.25))' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                🤖 {{ __('AI') }}
                            </button>
                            @foreach([2, 3, 4, 5, 6, 8, 10] as $count)
                                <button type="button"
                                        wire:click="$set('multiShotCount', {{ $count }})"
                                        style="padding: 0.75rem; border-radius: 0.5rem; border: 2px solid {{ $multiShotCount === $count ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $multiShotCount === $count ? 'rgba(139,92,246,0.25)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 1rem; font-weight: 600;">
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                        @if($multiShotCount === 0)
                            <p style="color: rgba(16, 185, 129, 0.9); font-size: 0.8rem; margin-top: 0.5rem;">
                                🤖 {{ __('AI will analyze the scene and determine optimal shot count and durations') }}
                            </p>
                        @else
                            <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-top: 0.5rem;">
                                💡 {{ __('Manual: :count shots with uniform duration', ['count' => $multiShotCount]) }}
                            </p>
                        @endif
                    </div>

                    {{-- Decompose Button --}}
                    <button type="button"
                            wire:click="decomposeScene({{ $multiShotSceneIndex }})"
                            wire:loading.attr="disabled"
                            wire:target="decomposeScene"
                            style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 1.1rem;">
                        <span wire:loading.remove wire:target="decomposeScene">✂️ {{ __('Decompose Scene') }}</span>
                        <span wire:loading wire:target="decomposeScene">
                            <svg style="width: 20px; height: 20px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                            </svg>
                            {{ __('Decomposing...') }}
                        </span>
                    </button>
                </div>
            </div>
        @else
            {{-- DECOMPOSED VIEW - Split Panel Layout --}}
            <div style="flex: 1; display: flex; overflow: hidden;">

                {{-- LEFT PANEL: Collage Preview --}}
                <div style="width: 45%; min-width: 400px; max-width: 550px; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">

                    {{-- Collage Header --}}
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">🖼️ {{ __('Collage Preview') }}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.5); font-size: 0.75rem;">{{ __('Click a region, then assign to a shot') }}</p>
                        </div>
                        <button type="button"
                                wire:click="generateCollagePreview({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                wire:target="generateCollagePreview"
                                style="padding: 0.5rem 1rem; background: linear-gradient(135deg, rgba(236, 72, 153, 0.3), rgba(139, 92, 246, 0.3)); border: 1px solid rgba(236, 72, 153, 0.5); border-radius: 0.5rem; color: white; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem;">
                            <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate') }}</span>
                            <span wire:loading wire:target="generateCollagePreview">⏳</span>
                        </button>
                    </div>

                    {{-- Collage Content Area --}}
                    <div style="flex: 1; padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column;">
                        @if($collage && in_array($collage['status'], ['ready', 'generating', 'processing']))
                            @if($collage['status'] === 'generating' || $collage['status'] === 'processing')
                                <div style="flex: 1; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); border-radius: 0.5rem;">
                                    <div style="text-align: center;">
                                        <div style="width: 48px; height: 48px; border: 4px solid rgba(236, 72, 153, 0.3); border-top-color: #ec4899; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                        <span style="font-size: 0.9rem; color: rgba(255,255,255,0.7); margin-top: 1rem; display: block;">{{ __('Generating collage preview...') }}</span>
                                    </div>
                                </div>
                            @else
                                {{-- Collage Image with Overlay --}}
                                <div style="position: relative; border-radius: 0.5rem; overflow: hidden; flex: 1; display: flex; flex-direction: column;"
                                     x-data="{ selectedRegion: null, selectedPage: {{ $currentPage }} }">

                                    @if($totalPages > 1)
                                        <div style="background: rgba(236, 72, 153, 0.1); padding: 0.5rem 0.75rem; margin-bottom: 0.5rem; border-radius: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                                            <span style="color: rgba(255,255,255,0.7); font-size: 0.8rem; font-weight: 500;">
                                                {{ __('Page :current of :total', ['current' => $currentPage + 1, 'total' => $totalPages]) }}
                                                <span style="color: rgba(255,255,255,0.5); margin-left: 0.25rem;">({{ __('Shots :start-:end', ['start' => min($currentShots) + 1, 'end' => max($currentShots) + 1]) }})</span>
                                            </span>
                                            <div style="display: flex; gap: 0.35rem;">
                                                <button type="button" wire:click="prevCollagePage({{ $multiShotSceneIndex }})" {{ $currentPage <= 0 ? 'disabled' : '' }}
                                                        style="padding: 0.35rem 0.75rem; background: {{ $currentPage > 0 ? 'rgba(236, 72, 153, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $currentPage > 0 ? 'rgba(236, 72, 153, 0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.35rem; color: {{ $currentPage > 0 ? 'white' : 'rgba(255,255,255,0.3)' }}; font-size: 0.8rem; cursor: {{ $currentPage > 0 ? 'pointer' : 'not-allowed' }};">◀</button>
                                                @for($pageIdx = 0; $pageIdx < $totalPages; $pageIdx++)
                                                    <button type="button" wire:click="setCollagePage({{ $multiShotSceneIndex }}, {{ $pageIdx }})"
                                                            style="width: 32px; height: 32px; background: {{ $pageIdx === $currentPage ? 'rgba(236, 72, 153, 0.5)' : 'rgba(255,255,255,0.1)' }}; border: 1px solid {{ $pageIdx === $currentPage ? 'rgba(236, 72, 153, 0.7)' : 'rgba(255,255,255,0.2)' }}; border-radius: 50%; color: white; font-size: 0.75rem; font-weight: 600; cursor: pointer;">{{ $pageIdx + 1 }}</button>
                                                @endfor
                                                <button type="button" wire:click="nextCollagePage({{ $multiShotSceneIndex }})" {{ $currentPage >= $totalPages - 1 ? 'disabled' : '' }}
                                                        style="padding: 0.35rem 0.75rem; background: {{ $currentPage < $totalPages - 1 ? 'rgba(236, 72, 153, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $currentPage < $totalPages - 1 ? 'rgba(236, 72, 153, 0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.35rem; color: {{ $currentPage < $totalPages - 1 ? 'white' : 'rgba(255,255,255,0.3)' }}; font-size: 0.8rem; cursor: {{ $currentPage < $totalPages - 1 ? 'pointer' : 'not-allowed' }};">▶</button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- The Collage Image --}}
                                    <div style="position: relative; flex: 1; min-height: 300px;">
                                        @if(!empty($currentCollage['previewUrl']))
                                            <img src="{{ $currentCollage['previewUrl'] }}"
                                                 alt="Collage Preview"
                                                 style="width: 100%; height: 100%; object-fit: contain; border-radius: 0.5rem; background: rgba(0,0,0,0.3);">
                                        @else
                                            <div style="width: 100%; height: 100%; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; border-radius: 0.5rem;">
                                                <span style="color: rgba(255,255,255,0.4); font-size: 1rem;">{{ __('No preview available') }}</span>
                                            </div>
                                        @endif

                                        {{-- Clickable Quadrant Overlay --}}
                                        <div style="position: absolute; inset: 0; display: grid; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr);">
                                            @for($regionIdx = 0; $regionIdx < 4; $regionIdx++)
                                                @php
                                                    $region = $currentCollage['regions'][$regionIdx] ?? null;
                                                    $assignedShot = $region['assignedToShot'] ?? null;
                                                @endphp
                                                <div style="position: relative; cursor: pointer; border: 2px solid rgba(255,255,255,0.3); transition: all 0.2s; margin: 2px;"
                                                     x-on:click="selectedRegion = {{ $regionIdx }}; selectedPage = {{ $currentPage }}; $dispatch('region-selected', { regionIndex: {{ $regionIdx }}, pageIndex: {{ $currentPage }} })"
                                                     x-bind:style="selectedRegion === {{ $regionIdx }} && selectedPage === {{ $currentPage }} ? 'background: rgba(236, 72, 153, 0.4); border-color: #ec4899; box-shadow: inset 0 0 0 3px rgba(236, 72, 153, 0.6);' : ''"
                                                     onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.borderColor='rgba(255,255,255,0.6)';"
                                                     onmouseout="if (!this.classList.contains('selected')) { this.style.background='transparent'; this.style.borderColor='rgba(255,255,255,0.3)'; }">

                                                    {{-- Region Number --}}
                                                    <div style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(0,0,0,0.85); color: white; padding: 0.3rem 0.6rem; border-radius: 0.35rem; font-size: 0.85rem; font-weight: 600;">
                                                        {{ ($currentShots[$regionIdx] ?? $regionIdx) + 1 }}
                                                    </div>

                                                    {{-- Assigned Badge --}}
                                                    @if($assignedShot !== null)
                                                        <div style="position: absolute; bottom: 0.5rem; right: 0.5rem; background: rgba(16, 185, 129, 0.95); color: white; padding: 0.3rem 0.6rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 600;">
                                                            → {{ __('Shot') }} {{ $assignedShot + 1 }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    {{-- Shot Assignment Buttons --}}
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);"
                                         x-data="{ selectedRegionForAssignment: null, selectedPageForAssignment: {{ $currentPage }} }"
                                         x-on:region-selected.window="selectedRegionForAssignment = $event.detail.regionIndex; selectedPageForAssignment = $event.detail.pageIndex">
                                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-bottom: 0.5rem; font-weight: 500;">
                                            {{ __('Assign selected region to:') }}
                                        </div>
                                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                            @foreach($decomposed['shots'] as $shotIdx => $shotData)
                                                @php
                                                    $shotSource = $collage['shotSources'][$shotIdx] ?? null;
                                                    $shotHasRegion = $shotSource !== null;
                                                    $assignedPageIdx = $shotSource['pageIndex'] ?? null;
                                                    $assignedRegionIdx = $shotSource['regionIndex'] ?? null;
                                                @endphp
                                                <button type="button"
                                                        x-on:click="if (selectedRegionForAssignment !== null) { $wire.assignCollageRegionToShot({{ $multiShotSceneIndex }}, selectedPageForAssignment, selectedRegionForAssignment, {{ $shotIdx }}) }"
                                                        x-bind:disabled="selectedRegionForAssignment === null"
                                                        x-bind:style="selectedRegionForAssignment === null ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                                                        style="padding: 0.4rem 0.75rem; background: {{ $shotHasRegion ? 'rgba(16, 185, 129, 0.35)' : 'rgba(139,92,246,0.25)' }}; border: 1px solid {{ $shotHasRegion ? 'rgba(16, 185, 129, 0.6)' : 'rgba(139,92,246,0.5)' }}; border-radius: 0.35rem; color: white; font-size: 0.8rem; cursor: pointer; font-weight: 500;">
                                                    {{ __('Shot') }} {{ $shotIdx + 1 }}
                                                    @if($shotHasRegion)
                                                        <span style="font-size: 0.7rem; opacity: 0.8; margin-left: 0.2rem;">(P{{ $assignedPageIdx + 1 }}R{{ $assignedRegionIdx + 1 }})</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Clear Button --}}
                                <div style="margin-top: 1rem; text-align: center;">
                                    <button type="button"
                                            wire:click="clearCollagePreview({{ $multiShotSceneIndex }})"
                                            style="padding: 0.4rem 1rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: rgba(255,255,255,0.6); font-size: 0.75rem; cursor: pointer;">
                                        ✕ {{ __('Clear Collage') }}
                                    </button>
                                </div>
                            @endif
                        @else
                            {{-- No Collage - Empty State --}}
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 0.5rem; border: 2px dashed rgba(236, 72, 153, 0.3);">
                                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🖼️</div>
                                <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin: 0 0 1rem 0; text-align: center;">
                                    {{ __('Generate a 2x2 collage to quickly assign visual variations to shots') }}
                                </p>
                                <button type="button"
                                        wire:click="generateCollagePreview({{ $multiShotSceneIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateCollagePreview"
                                        style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, rgba(236, 72, 153, 0.4), rgba(139, 92, 246, 0.4)); border: 1px solid rgba(236, 72, 153, 0.6); border-radius: 0.5rem; color: white; font-size: 0.9rem; cursor: pointer; font-weight: 500;">
                                    <span wire:loading.remove wire:target="generateCollagePreview">🖼️ {{ __('Generate Collage Preview') }}</span>
                                    <span wire:loading wire:target="generateCollagePreview">⏳ {{ __('Generating...') }}</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RIGHT PANEL: Shot Grid --}}
                <div style="flex: 1; display: flex; flex-direction: column; background: rgba(0,0,0,0.1); overflow: hidden;">

                    {{-- Action Bar --}}
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                        <button type="button"
                                wire:click="generateAllShots({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="padding: 0.6rem 1rem; background: rgba(139,92,246,0.25); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.5rem; color: white; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; font-weight: 500;">
                            🎨 {{ __('Generate All Images') }}
                        </button>
                        <button type="button"
                                wire:click="generateAllShotVideos({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="padding: 0.6rem 1rem; background: rgba(6,182,212,0.25); border: 1px solid rgba(6,182,212,0.5); border-radius: 0.5rem; color: white; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; font-weight: 500;">
                            🎬 {{ __('Animate All Shots') }}
                        </button>
                        <div style="flex: 1;"></div>
                        <button type="button"
                                wire:click="resetDecomposition({{ $multiShotSceneIndex }})"
                                style="padding: 0.5rem 0.75rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #ef4444; font-size: 0.8rem; cursor: pointer;">
                            🗑️ {{ __('Reset') }}
                        </button>
                    </div>

                    {{-- Duration Timeline --}}
                    <div style="padding: 0.75rem 1.25rem; background: rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <div style="display: flex; height: 32px; border-radius: 0.5rem; overflow: hidden; background: rgba(0,0,0,0.4);">
                            @foreach($decomposed['shots'] as $idx => $shot)
                                @php
                                    $shotDuration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                    $percentage = $totalDuration > 0 ? ($shotDuration / $totalDuration * 100) : (100 / count($decomposed['shots']));
                                    $hasImage = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                    $hasVideo = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                                    $bgColor = $hasVideo ? 'rgba(6, 182, 212, 0.6)' : ($hasImage ? 'rgba(16, 185, 129, 0.5)' : 'rgba(139, 92, 246, 0.3)');
                                @endphp
                                <div style="width: {{ $percentage }}%; background: {{ $bgColor }}; display: flex; align-items: center; justify-content: center; border-right: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: all 0.2s;"
                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $idx }})"
                                     title="Shot {{ $idx + 1 }}: {{ $shotDuration }}s"
                                     onmouseover="this.style.filter='brightness(1.2)';"
                                     onmouseout="this.style.filter='brightness(1)';">
                                    <span style="font-size: 0.75rem; color: white; font-weight: 600;">{{ $idx + 1 }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Shot Grid --}}
                    <div style="flex: 1; padding: 1.25rem; overflow-y: auto;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                            @foreach($decomposed['shots'] as $shotIndex => $shot)
                                @php
                                    $hasImage = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                    $hasVideo = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                                    $isGeneratingImage = ($shot['status'] ?? '') === 'generating';
                                    $isGeneratingVideo = in_array($shot['videoStatus'] ?? '', ['generating', 'processing']);
                                    $wasTransferred = isset($shot['transferredFrom']);
                                    $isSelected = ($decomposed['selectedShot'] ?? 0) === $shotIndex;
                                    $shotDuration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                    $durationColor = $shotDuration <= 5 ? '#22c55e' : ($shotDuration <= 6 ? '#eab308' : '#3b82f6');
                                    $shotSource = $collage['shotSources'][$shotIndex] ?? null;
                                    $hasCollageRegion = $shotSource !== null;
                                    $assignedCollagePage = $shotSource['pageIndex'] ?? null;
                                    $assignedCollageRegion = $shotSource['regionIndex'] ?? null;
                                    $fromCollageRegion = $shot['fromCollageRegion'] ?? null;
                                    $hasDialogue = !empty($shot['dialogue']);
                                @endphp

                                <div style="background: rgba(255,255,255,0.03); border: 2px solid {{ $hasVideo ? 'rgba(6, 182, 212, 0.5)' : ($isSelected ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.1)') }}; border-radius: 0.75rem; overflow: hidden; transition: all 0.2s;"
                                     data-video-status="{{ $shot['videoStatus'] ?? 'pending' }}"
                                     data-shot-index="{{ $shotIndex }}">

                                    {{-- Shot Header --}}
                                    <div style="padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.3); display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="background: rgba(139, 92, 246, 0.5); color: white; padding: 0.2rem 0.5rem; border-radius: 0.3rem; font-size: 0.8rem; font-weight: 600;">{{ $shotIndex + 1 }}</span>
                                            <span style="color: rgba(255,255,255,0.7); font-size: 0.75rem;">{{ ucfirst(str_replace('_', ' ', $shot['type'] ?? 'shot')) }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                                            @if($hasDialogue)
                                                <span style="background: rgba(251, 191, 36, 0.4); color: #fbbf24; padding: 0.15rem 0.35rem; border-radius: 0.25rem; font-size: 0.65rem;">💬</span>
                                            @endif
                                            <span style="color: {{ $durationColor }}; font-size: 0.8rem; font-weight: 500;">{{ $shotDuration }}s</span>
                                        </div>
                                    </div>

                                    {{-- Image/Video Area --}}
                                    <div style="height: 130px; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer;"
                                         wire:click="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})">
                                        @if($hasImage)
                                            <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotIndex + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;"
                                                 onmouseover="this.style.opacity='1';" onmouseout="this.style.opacity='0';">
                                                <span style="font-size: 1.5rem;">{{ $hasVideo ? '▶️' : '🔍' }}</span>
                                            </div>
                                            {{-- Status Icons --}}
                                            <div style="position: absolute; bottom: 0.4rem; right: 0.4rem; display: flex; gap: 0.25rem;">
                                                <div style="width: 20px; height: 20px; background: rgba(16, 185, 129, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <span style="font-size: 10px;">🖼</span>
                                                </div>
                                                @if($hasVideo)
                                                    <div style="width: 20px; height: 20px; background: rgba(6, 182, 212, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <span style="font-size: 10px;">🎬</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($isGeneratingImage)
                                            <div style="text-align: center;">
                                                <div style="width: 32px; height: 32px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                                <span style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 0.5rem; display: block;">{{ __('Generating...') }}</span>
                                            </div>
                                        @elseif($isGeneratingVideo && $hasImage)
                                            <img src="{{ $shot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.4);">
                                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                                <div style="width: 40px; height: 40px; border: 3px solid rgba(6, 182, 212, 0.3); border-top-color: #06b6d4; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                                <span style="font-size: 0.8rem; color: #67e8f9; margin-top: 0.5rem; font-weight: 600;">🎬 {{ __('Animating...') }}</span>
                                            </div>
                                        @else
                                            <div style="text-align: center;">
                                                <span style="font-size: 2rem; color: rgba(255,255,255,0.2);">🖼️</span>
                                                @if($hasCollageRegion)
                                                    <button type="button"
                                                            wire:click.stop="generateShotFromCollageRegion({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                            style="display: block; margin: 0.5rem auto 0; padding: 0.35rem 0.75rem; background: linear-gradient(135deg, rgba(236, 72, 153, 0.4), rgba(139, 92, 246, 0.4)); border: 1px solid rgba(236, 72, 153, 0.5); border-radius: 0.35rem; color: white; font-size: 0.75rem; cursor: pointer;">
                                                        🖼️ P{{ $assignedCollagePage + 1 }}R{{ $assignedCollageRegion + 1 }}
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            wire:click.stop="generateShotImage({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                            style="display: block; margin: 0.5rem auto 0; padding: 0.35rem 0.75rem; background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.35rem; color: white; font-size: 0.75rem; cursor: pointer;">
                                                        {{ __('Generate') }}
                                                    </button>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Collage Region Badge --}}
                                        @if($hasCollageRegion)
                                            <div style="position: absolute; top: 0.4rem; left: 0.4rem; background: rgba(236, 72, 153, 0.9); color: white; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">
                                                🖼️ P{{ $assignedCollagePage + 1 }}R{{ $assignedCollageRegion + 1 }}
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Shot Controls --}}
                                    <div style="padding: 0.6rem 0.75rem;">
                                        {{-- Duration Selector --}}
                                        @php
                                            $selectedModel = $shot['selectedVideoModel'] ?? 'minimax';
                                            $shotAvailableDurations = $this->getAvailableDurations($selectedModel);
                                        @endphp
                                        <div style="display: flex; gap: 0.25rem; margin-bottom: 0.5rem;">
                                            @foreach($shotAvailableDurations as $dur)
                                                @php
                                                    $durColor = $dur <= 5 ? 'rgba(34, 197, 94' : ($dur <= 6 ? 'rgba(234, 179, 8' : 'rgba(59, 130, 246');
                                                    $isSelectedDur = $shotDuration === $dur;
                                                @endphp
                                                <button type="button"
                                                        wire:click.stop="setShotDuration({{ $multiShotSceneIndex }}, {{ $shotIndex }}, {{ $dur }})"
                                                        style="flex: 1; padding: 0.3rem; font-size: 0.7rem; background: {{ $isSelectedDur ? $durColor . ', 0.35)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $isSelectedDur ? $durColor . ', 0.6)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.25rem; color: white; cursor: pointer; font-weight: {{ $isSelectedDur ? '600' : '400' }};">
                                                    {{ $dur }}s
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Action Buttons --}}
                                        @if($hasVideo)
                                            <div style="display: flex; gap: 0.35rem;">
                                                <button type="button"
                                                        wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="flex: 1; padding: 0.4rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(6, 182, 212, 0.3)); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 0.35rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 500;">
                                                    ▶️ {{ __('Play') }}
                                                </button>
                                                @if($shotIndex < count($decomposed['shots']) - 1)
                                                    <button type="button"
                                                            wire:click.stop="openFrameCaptureModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                            style="padding: 0.4rem 0.6rem; background: rgba(16, 185, 129, 0.25); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.35rem; color: white; cursor: pointer; font-size: 0.7rem;">
                                                        🎯 → {{ $shotIndex + 2 }}
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($hasImage && !$isGeneratingVideo)
                                            <button type="button"
                                                    wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                    style="width: 100%; padding: 0.5rem; background: linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(59, 130, 246, 0.3)); border: 1px solid rgba(6, 182, 212, 0.5); border-radius: 0.35rem; color: white; cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                                                🎬 {{ __('Animate') }}
                                            </button>
                                        @elseif($isGeneratingVideo)
                                            <div style="text-align: center; padding: 0.25rem;">
                                                <div style="font-size: 0.75rem; color: #67e8f9;">⏳ {{ __('Rendering...') }}</div>
                                                <div style="height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-top: 0.35rem;">
                                                    <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6); animation: progress-indeterminate 1.5s infinite linear;"></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endif

{{-- Video Model Selector Popup --}}
@if($showVideoModelSelector ?? false)
<div class="vw-popup-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 2000;"
     wire:click.self="closeVideoModelSelector">
    <div style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(6, 182, 212, 0.4); border-radius: 0.75rem; width: 360px; max-width: 95vw; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">
        {{-- Popup Header --}}
        <div style="padding: 1rem 1.25rem; background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(59, 130, 246, 0.2)); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">🎬 {{ __('Animation Model') }}</h4>
                <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Shot') }} {{ ($videoModelSelectorShotIndex ?? 0) + 1 }}</p>
            </div>
            <button type="button" wire:click="closeVideoModelSelector" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Model Selection --}}
        <div style="padding: 1.25rem;">
            @php
                $selectorShot = $multiShotMode['decomposedScenes'][$videoModelSelectorSceneIndex ?? 0]['shots'][$videoModelSelectorShotIndex ?? 0] ?? [];
                $hasDialogue = !empty($selectorShot['dialogue']);
                $hasAudio = !empty($selectorShot['audioUrl']) || !empty($selectorShot['voiceoverUrl']);
                $currentModel = $selectorShot['selectedVideoModel'] ?? 'minimax';
                $currentDuration = $selectorShot['selectedDuration'] ?? $selectorShot['duration'] ?? 6;
                $multitalkAvailable = !empty(get_option('runpod_multitalk_endpoint', ''));
            @endphp

            {{-- MiniMax Option --}}
            <div style="margin-bottom: 1rem;">
                <div style="background: {{ $currentModel === 'minimax' ? 'rgba(6, 182, 212, 0.2)' : 'rgba(255,255,255,0.03)' }}; border: 2px solid {{ $currentModel === 'minimax' ? 'rgba(6, 182, 212, 0.6)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; padding: 1rem; cursor: pointer; transition: all 0.2s;"
                     wire:click="setVideoModel('minimax')">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div style="width: 22px; height: 22px; border: 2px solid {{ $currentModel === 'minimax' ? '#06b6d4' : 'rgba(255,255,255,0.3)' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                @if($currentModel === 'minimax')
                                    <div style="width: 12px; height: 12px; background: #06b6d4; border-radius: 50%;"></div>
                                @endif
                            </div>
                            <div>
                                <div style="color: white; font-weight: 600; font-size: 1rem;">MiniMax</div>
                                <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('High quality I2V animation') }}</div>
                            </div>
                        </div>
                        <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600;">{{ __('Recommended') }}</span>
                    </div>
                </div>
            </div>

            {{-- Multitalk Option --}}
            <div style="margin-bottom: 1.25rem; opacity: {{ $multitalkAvailable ? '1' : '0.5' }};">
                <div style="background: {{ $currentModel === 'multitalk' ? 'rgba(251, 191, 36, 0.2)' : 'rgba(255,255,255,0.03)' }}; border: 2px solid {{ $currentModel === 'multitalk' ? 'rgba(251, 191, 36, 0.6)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; padding: 1rem; cursor: {{ $multitalkAvailable ? 'pointer' : 'not-allowed' }}; transition: all 0.2s;"
                     @if($multitalkAvailable) wire:click="setVideoModel('multitalk')" @endif>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div style="width: 22px; height: 22px; border: 2px solid {{ $currentModel === 'multitalk' ? '#fbbf24' : 'rgba(255,255,255,0.3)' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                @if($currentModel === 'multitalk')
                                    <div style="width: 12px; height: 12px; background: #fbbf24; border-radius: 50%;"></div>
                                @endif
                            </div>
                            <div>
                                <div style="color: white; font-weight: 600; font-size: 1rem;">Multitalk</div>
                                <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Lip-sync for dialogue') }}</div>
                            </div>
                        </div>
                        @if($hasDialogue)
                            <span style="background: rgba(251, 191, 36, 0.2); color: #fbbf24; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600;">💬</span>
                        @endif
                    </div>
                    @if(!$multitalkAvailable)
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #ef4444;">⚠️ {{ __('Not configured') }}</div>
                    @endif
                </div>
            </div>

            {{-- Duration Selector --}}
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 500;">{{ __('Duration') }}</label>
                @php
                    $availableDurations = $this->getAvailableDurations($currentModel);
                @endphp
                <div style="display: flex; gap: 0.4rem;">
                    @foreach($availableDurations as $dur)
                        @php
                            $isSelected = $currentDuration == $dur;
                            $durColor = $dur <= 5 ? 'rgba(34, 197, 94' : ($dur <= 6 ? 'rgba(234, 179, 8' : 'rgba(59, 130, 246');
                        @endphp
                        <button type="button"
                                wire:click="setVideoModelDuration({{ $dur }})"
                                style="flex: 1; padding: 0.6rem; font-size: 0.9rem; font-weight: 600; background: {{ $isSelected ? $durColor . ', 0.35)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $isSelected ? $durColor . ', 0.6)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.4rem; color: white; cursor: pointer;">
                            {{ $dur }}s
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Generate Button --}}
            <button type="button"
                    wire:click="confirmVideoModelAndGenerate"
                    wire:loading.attr="disabled"
                    style="width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #06b6d4, #3b82f6); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 1rem;">
                <span wire:loading.remove wire:target="confirmVideoModelAndGenerate">🎬 {{ __('Generate Animation') }}</span>
                <span wire:loading wire:target="confirmVideoModelAndGenerate">
                    <svg style="width: 18px; height: 18px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Starting...') }}
                </span>
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
@keyframes progress-indeterminate {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>
