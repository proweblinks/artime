{{-- Multi-Shot Decomposition Modal --}}

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
            // Safety check: don't poll if component was destroyed
            if (this.componentDestroyed) {
                console.log('[MultiShot] ⚠️ Component destroyed, skipping poll');
                this.stopPolling();
                return;
            }

            // Safety check: stop after max polls to prevent infinite polling
            if (this.pollCount >= this.maxPolls) {
                console.log('[MultiShot] ⚠️ Max polls reached (' + this.maxPolls + '), stopping');
                this.stopPolling();
                return;
            }

            this.pollCount++;
            console.log('[MultiShot] 📡 Poll #' + this.pollCount);

            try {
                // Call Livewire method directly via $wire (more reliable than dispatch)
                if (this.$wire) {
                    this.$wire.pollVideoJobs().then((result) => {
                        console.log('[MultiShot] ✅ pollVideoJobs result:', result);
                        // Stop polling if no jobs
                        if (result && result.pendingJobs === 0) {
                            console.log('[MultiShot] ⚠️ No pending jobs - stopping polling');
                            this.stopPolling();
                        }
                    }).catch((e) => {
                        console.error('[MultiShot] ❌ pollVideoJobs() error:', e);
                        // If we get a component not found error, stop polling
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

            // First poll after 1s
            setTimeout(() => {
                if (!this.componentDestroyed) {
                    this.dispatchPoll();
                }
            }, 1000);

            // Then every 5 seconds
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

        // Cleanup listeners and polling
        cleanup() {
            this.componentDestroyed = true;
            this.stopPolling();

            // Remove event listeners to prevent memory leaks
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

        // Called by Alpine when component is destroyed (x-on:destroy)
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
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 900px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 0.6rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">✂️ {{ __('Multi-Shot Decomposition') }}</h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ __('Split scene into multiple camera shots for dynamic storytelling') }}</p>
            </div>
            <button type="button" wire:click="closeMultiShotModal" style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 0.75rem 1rem;">
            @php
                $scene = $script['scenes'][$multiShotSceneIndex] ?? null;
                $decomposed = $multiShotMode['decomposedScenes'][$multiShotSceneIndex] ?? null;
            @endphp

            @if($scene)
                {{-- Scene Preview --}}
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; padding: 0.6rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: start;">
                        @php
                            $storyboardScene = $storyboard['scenes'][$multiShotSceneIndex] ?? null;
                        @endphp
                        @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                            <img src="{{ $storyboardScene['imageUrl'] }}"
                                 alt="Scene {{ $multiShotSceneIndex + 1 }}"
                                 style="width: 140px; height: 78px; object-fit: cover; border-radius: 0.375rem;">
                        @else
                            <div style="width: 140px; height: 78px; background: rgba(255,255,255,0.05); border-radius: 0.375rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: rgba(255,255,255,0.4);">🎬</span>
                            </div>
                        @endif
                        <div style="flex: 1;">
                            <div style="color: white; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.2rem;">{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</div>
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.75rem; margin: 0; line-height: 1.3;">
                                {{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 120) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if(!$decomposed)
                    {{-- Shot Count Selector --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Number of Shots') }}</label>
                        <div style="display: flex; gap: 0.35rem;">
                            @foreach([2, 3, 4, 5, 6] as $count)
                                <button type="button"
                                        wire:click="$set('multiShotCount', {{ $count }})"
                                        style="flex: 1; padding: 0.5rem; border-radius: 0.375rem; border: 1px solid {{ $multiShotCount === $count ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $multiShotCount === $count ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-top: 0.35rem;">
                            💡 {{ __('More shots = more dynamic scene, but requires more generation') }}
                        </p>
                    </div>

                    {{-- Shot Types Preview --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Shot Sequence Preview') }}</label>
                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            @php
                                $shotTypes = [
                                    ['type' => 'establishing', 'icon' => '🏔️', 'label' => 'Establishing'],
                                    ['type' => 'medium', 'icon' => '👤', 'label' => 'Medium'],
                                    ['type' => 'close-up', 'icon' => '🔍', 'label' => 'Close-up'],
                                    ['type' => 'reaction', 'icon' => '😮', 'label' => 'Reaction'],
                                    ['type' => 'detail', 'icon' => '✨', 'label' => 'Detail'],
                                    ['type' => 'wide', 'icon' => '🌄', 'label' => 'Wide'],
                                ];
                            @endphp
                            @for($i = 0; $i < $multiShotCount; $i++)
                                @php $shot = $shotTypes[$i % count($shotTypes)]; @endphp
                                <div style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.375rem; padding: 0.35rem 0.5rem; text-align: center;">
                                    <div style="font-size: 1rem;">{{ $shot['icon'] }}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.7);">{{ __($shot['label']) }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Decompose Button --}}
                    <button type="button"
                            wire:click="decomposeScene({{ $multiShotSceneIndex }})"
                            wire:loading.attr="disabled"
                            wire:target="decomposeScene"
                            style="width: 100%; padding: 0.65rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.375rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem;">
                        <span wire:loading.remove wire:target="decomposeScene">✂️ {{ __('Decompose Scene') }}</span>
                        <span wire:loading wire:target="decomposeScene">
                            <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                            </svg>
                            {{ __('Decomposing...') }}
                        </span>
                    </button>
                @else
                    {{-- DECOMPOSED VIEW --}}

                    {{-- Duration Timeline Bar --}}
                    @php
                        $totalDuration = 0;
                        foreach ($decomposed['shots'] as $shot) {
                            $totalDuration += $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                        }
                        $imagesReady = collect($decomposed['shots'])->filter(fn($s) => ($s['status'] ?? '') === 'ready' && !empty($s['imageUrl']))->count();
                        $videosReady = collect($decomposed['shots'])->filter(fn($s) => ($s['videoStatus'] ?? '') === 'ready' && !empty($s['videoUrl']))->count();
                    @endphp

                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; padding: 0.6rem; margin-bottom: 0.6rem;">
                        {{-- Header with stats --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span style="color: white; font-weight: 600; font-size: 0.85rem;">📽️ {{ count($decomposed['shots']) }} SHOTS</span>
                                <span style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">• {{ $totalDuration }}s</span>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <span style="font-size: 0.65rem; color: rgba(16, 185, 129, 0.9);">
                                    🖼️ {{ $imagesReady }}/{{ count($decomposed['shots']) }}
                                </span>
                                <span style="font-size: 0.65rem; color: rgba(6, 182, 212, 0.8);">
                                    🎬 {{ $videosReady }}/{{ count($decomposed['shots']) }}
                                </span>
                            </div>
                        </div>

                        {{-- Duration Timeline Visual --}}
                        <div style="display: flex; height: 24px; border-radius: 0.375rem; overflow: hidden; background: rgba(0,0,0,0.4);">
                            @foreach($decomposed['shots'] as $idx => $shot)
                                @php
                                    $shotDuration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                    $percentage = $totalDuration > 0 ? ($shotDuration / $totalDuration * 100) : (100 / count($decomposed['shots']));
                                    $hasImage = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                    $hasVideo = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                                    $bgColor = $hasVideo ? 'rgba(6, 182, 212, 0.6)' : ($hasImage ? 'rgba(16, 185, 129, 0.5)' : 'rgba(139, 92, 246, 0.3)');
                                @endphp
                                <div style="width: {{ $percentage }}%; background: {{ $bgColor }}; display: flex; align-items: center; justify-content: center; border-right: 1px solid rgba(255,255,255,0.1); position: relative; cursor: pointer;"
                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $idx }})"
                                     title="Shot {{ $idx + 1 }}: {{ $shotDuration }}s">
                                    <span style="font-size: 0.6rem; color: white; font-weight: 600;">{{ $idx + 1 }}</span>
                                    @if(($decomposed['selectedShot'] ?? 0) === $idx)
                                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: white;"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div style="display: flex; gap: 0.4rem; margin-bottom: 0.6rem; flex-wrap: wrap;">
                        <button type="button"
                                wire:click="generateAllShots({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="flex: 1; min-width: 120px; padding: 0.4rem 0.6rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.375rem; color: white; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.3rem;">
                            🎨 {{ __('Generate All Images') }}
                        </button>
                        <button type="button"
                                wire:click="generateAllShotVideos({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="flex: 1; min-width: 120px; padding: 0.4rem 0.6rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.375rem; color: white; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.3rem;">
                            🎬 {{ __('Animate All Shots') }}
                        </button>
                    </div>

                    {{-- Shot Timeline with Frame Chain --}}
                    <div style="position: relative; display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.25rem 0; overflow-x: auto;">
                        @foreach($decomposed['shots'] as $shotIndex => $shot)
                            @php
                                $hasImage = ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
                                $hasVideo = ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
                                $isGeneratingImage = ($shot['status'] ?? '') === 'generating';
                                $isGeneratingVideo = in_array($shot['videoStatus'] ?? '', ['generating', 'processing']);
                                $wasTransferred = isset($shot['transferredFrom']);
                                $isLastShot = $shotIndex === count($decomposed['shots']) - 1;
                                $nextShot = $decomposed['shots'][$shotIndex + 1] ?? null;
                                $isSelected = ($decomposed['selectedShot'] ?? 0) === $shotIndex;
                                $shotDuration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
                                $durationClass = $shotDuration <= 5 ? 'quick' : ($shotDuration <= 6 ? 'short' : 'standard');
                                $durationColor = $durationClass === 'quick' ? '#22c55e' : ($durationClass === 'short' ? '#eab308' : '#3b82f6');
                            @endphp

                            <div style="flex: 1; min-width: 160px; max-width: 200px; position: relative;">
                                {{-- Frame Chain Connector --}}
                                @if(!$isLastShot)
                                    <div style="position: absolute; top: 55px; right: -0.75rem; width: 1.5rem; height: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 3;">
                                        @if($hasVideo)
                                            <div style="font-size: 1rem; color: {{ ($nextShot['transferredFrom'] ?? -1) === $shotIndex ? '#10b981' : 'rgba(139, 92, 246, 0.6)' }};">
                                                {{ ($nextShot['transferredFrom'] ?? -1) === $shotIndex ? '🔗' : '→' }}
                                            </div>
                                        @else
                                            <div style="width: 100%; height: 2px; background: rgba(255,255,255,0.15);"></div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Shot Card --}}
                                <div style="background: rgba(255,255,255,0.05); border: 1px solid {{ $wasTransferred ? 'rgba(16, 185, 129, 0.4)' : ($hasVideo ? 'rgba(6, 182, 212, 0.4)' : ($isSelected ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.15)')) }}; border-radius: 0.5rem; overflow: hidden; position: relative; z-index: 1; cursor: pointer;"
                                     data-video-status="{{ $shot['videoStatus'] ?? 'pending' }}"
                                     data-shot-index="{{ $shotIndex }}"
                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $shotIndex }})">

                                    {{-- Shot Number Badge --}}
                                    <div style="position: absolute; top: 0.25rem; left: 0.25rem; background: rgba(0,0,0,0.7); color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600; z-index: 2;">
                                        {{ $shotIndex + 1 }}
                                    </div>

                                    {{-- Shot Type Badge --}}
                                    <div style="position: absolute; top: 0.25rem; right: 0.25rem; background: rgba(139, 92, 246, 0.8); color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.55rem; z-index: 2;">
                                        {{ ucfirst(str_replace('_', ' ', $shot['type'] ?? 'shot')) }}
                                    </div>

                                    {{-- Audio Type Badge --}}
                                    @php
                                        // dialogue can be a string or array, so just check if not empty
                                        $hasDialogue = !empty($shot['dialogue']);
                                        $audioType = $hasDialogue ? 'dialogue' : 'music';
                                        $audioConfig = [
                                            'dialogue' => ['icon' => '💬', 'label' => __('Dialogue'), 'bg' => 'rgba(251, 191, 36, 0.9)'],
                                            'music' => ['icon' => '🎵', 'label' => __('Music'), 'bg' => 'rgba(59, 130, 246, 0.7)'],
                                        ];
                                        $audio = $audioConfig[$audioType];
                                    @endphp
                                    <div style="position: absolute; top: 1.5rem; right: 0.25rem; background: {{ $audio['bg'] }}; color: white; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.45rem; z-index: 2; display: flex; align-items: center; gap: 0.1rem;">
                                        {{ $audio['icon'] }} {{ $audio['label'] }}
                                    </div>

                                    {{-- Transferred Badge (positioned below audio badge) --}}
                                    @if($wasTransferred)
                                        <div style="position: absolute; top: 2.6rem; right: 0.25rem; background: rgba(16, 185, 129, 0.9); color: white; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.5rem; z-index: 2;">
                                            🔗 from {{ $shot['transferredFrom'] + 1 }}
                                        </div>
                                    @endif

                                    {{-- Selected Badge --}}
                                    @if($isSelected)
                                        <div style="position: absolute; top: 0.25rem; left: 50%; transform: translateX(-50%); background: #10b981; color: white; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.5rem; z-index: 2;">
                                            ✓ Selected
                                        </div>
                                    @endif

                                    {{-- Image/Video Area --}}
                                    <div style="height: 90px; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; position: relative;"
                                         @if($hasImage) wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})" @endif>
                                        @if($hasImage)
                                            <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotIndex + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            {{-- Hover overlay --}}
                                            <div class="shot-hover-overlay" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                                                <span style="font-size: 1.2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">{{ $hasVideo ? '▶️' : '🔍' }}</span>
                                            </div>
                                            {{-- Status indicators --}}
                                            <div style="position: absolute; bottom: 0.25rem; right: 0.25rem; display: flex; gap: 0.15rem;">
                                                <div style="width: 14px; height: 14px; background: rgba(16, 185, 129, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <span style="color: white; font-size: 7px;">🖼</span>
                                                </div>
                                                @if($hasVideo)
                                                    <div style="width: 14px; height: 14px; background: rgba(6, 182, 212, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <span style="color: white; font-size: 7px;">🎬</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($isGeneratingImage)
                                            <div style="display: flex; flex-direction: column; align-items: center;">
                                                <div style="width: 24px; height: 24px; border: 2px solid rgba(139, 92, 246, 0.2); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                                <span style="font-size: 0.6rem; color: rgba(255,255,255,0.5); margin-top: 0.25rem;">Generating...</span>
                                            </div>
                                        @elseif($isGeneratingVideo && $hasImage)
                                            <img src="{{ $shot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.5);">
                                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
                                                <div style="width: 36px; height: 36px; border: 3px solid rgba(6, 182, 212, 0.3); border-top-color: #06b6d4; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                                <span style="font-size: 0.65rem; color: #67e8f9; margin-top: 0.5rem; font-weight: 600;">🎬 Animating...</span>
                                            </div>
                                        @elseif(($shot['status'] ?? '') === 'error')
                                            <div style="text-align: center;">
                                                <span style="font-size: 1.25rem;">⚠️</span>
                                                <div style="font-size: 0.6rem; color: #ef4444; margin-top: 0.25rem;">Error</div>
                                            </div>
                                        @else
                                            <div style="text-align: center;">
                                                <span style="font-size: 1.5rem; color: rgba(255,255,255,0.3);">🖼️</span>
                                                <button type="button"
                                                        wire:click.stop="generateShotImage({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="display: block; margin: 0.5rem auto 0; padding: 0.25rem 0.5rem; background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.25rem; color: white; font-size: 0.6rem; cursor: pointer;">
                                                    Generate
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Shot Info & Controls --}}
                                    <div style="padding: 0.4rem;">
                                        {{-- Camera & Duration --}}
                                        <div style="font-size: 0.6rem; color: rgba(255,255,255,0.6); margin-bottom: 0.3rem; display: flex; justify-content: space-between; align-items: center;">
                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $shot['cameraMovement'] ?? 'static' }} •
                                                <span style="color: {{ $durationColor }};">{{ $shotDuration }}s</span>
                                            </span>
                                            {{-- Token Cost --}}
                                            <span style="font-size: 0.5rem; color: #fbbf24;">⚡ {{ ($shotDuration <= 5 ? 100 : ($shotDuration <= 6 ? 120 : 200)) }}t</span>
                                        </div>

                                        {{-- Shot 1: Scene Image Status --}}
                                        @if($shotIndex === 0)
                                            @if($hasImage)
                                                <div style="text-align: center; padding: 0.2rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.25rem; margin-bottom: 0.3rem;">
                                                    <div style="font-size: 0.5rem; color: #10b981;">🔗 {{ __('Scene image') }}</div>
                                                </div>
                                            @else
                                                <div style="text-align: center; padding: 0.2rem; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 0.25rem; margin-bottom: 0.3rem;">
                                                    <div style="font-size: 0.5rem; color: #f59e0b;">⚠ {{ __('Generate scene image first') }}</div>
                                                </div>
                                            @endif
                                        @else
                                            {{-- Shots 2+: Frame Transfer Status --}}
                                            @if(!$hasImage && $wasTransferred === false)
                                                <div style="text-align: center; padding: 0.2rem; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.25rem; margin-bottom: 0.3rem;">
                                                    <div style="font-size: 0.5rem; color: #a78bfa;">⏳ {{ __('Waiting for frame from Shot :num', ['num' => $shotIndex]) }}</div>
                                                </div>
                                            @elseif($wasTransferred)
                                                <div style="text-align: center; padding: 0.2rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.25rem; margin-bottom: 0.3rem;">
                                                    <div style="font-size: 0.5rem; color: #10b981;">🔗 {{ __('Frame from Shot :num', ['num' => $shot['transferredFrom'] + 1]) }}</div>
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Duration Control --}}
                                        <div style="font-size: 0.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 0.25rem; padding: 0.25rem; margin-bottom: 0.3rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.15rem;">
                                                <span style="color: #3b82f6; font-weight: 600;">⏱️ {{ __('Duration') }}</span>
                                                <span style="color: {{ $durationColor }}; font-weight: 500;">{{ $shotDuration }}s</span>
                                            </div>
                                            <div style="display: flex; gap: 0.2rem; margin-bottom: 0.15rem;">
                                                @foreach([5, 6, 10] as $dur)
                                                    @php
                                                        $durColor = $dur === 5 ? 'rgba(34, 197, 94' : ($dur === 6 ? 'rgba(234, 179, 8' : 'rgba(59, 130, 246');
                                                        $isSelected = $shotDuration === $dur;
                                                    @endphp
                                                    <button type="button"
                                                            wire:click.stop="setShotDuration({{ $multiShotSceneIndex }}, {{ $shotIndex }}, {{ $dur }})"
                                                            style="flex: 1; padding: 0.15rem 0.25rem; font-size: 0.5rem; background: {{ $isSelected ? $durColor . ', 0.3)' : 'rgba(255,255,255,0.1)' }}; border: 1px solid {{ $isSelected ? $durColor . ', 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.2rem; color: white; cursor: pointer;">
                                                        {{ $dur }}s
                                                    </button>
                                                @endforeach
                                            </div>
                                            @php
                                                $shotType = $shot['type'] ?? 'medium';
                                                $recommendedDuration = match($shotType) {
                                                    'establishing', 'establishing_wide', 'wide' => 6,
                                                    'close-up', 'detail', 'reaction' => 5,
                                                    default => 6
                                                };
                                            @endphp
                                            <div style="font-size: 0.4rem; color: rgba(255,255,255,0.5); line-height: 1.2;">
                                                💡 {{ $recommendedDuration }}s {{ __('optimal for :type shot', ['type' => str_replace('_', ' ', $shotType)]) }}
                                            </div>
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            @if($hasVideo)
                                                {{-- Play Video --}}
                                                <button type="button"
                                                        wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        x-on:click="setTimeout(() => $wire.set('shotPreviewTab', 'video'), 100)"
                                                        style="width: 100%; padding: 0.3rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(6, 182, 212, 0.3)); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 0.3rem; color: white; cursor: pointer; font-size: 0.6rem; font-weight: 500;">
                                                    ▶️ {{ __('Play Video') }}
                                                </button>
                                            @elseif($hasImage && !$isGeneratingVideo)
                                                {{-- Animate Button - Opens Video Model Selector --}}
                                                <button type="button"
                                                        wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="width: 100%; padding: 0.3rem; background: linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(59, 130, 246, 0.3)); border: 1px solid rgba(6, 182, 212, 0.4); border-radius: 0.3rem; color: white; cursor: pointer; font-size: 0.6rem; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                                    🎬 {{ __('Animate') }}
                                                    @php
                                                        $selectedModel = $shot['selectedVideoModel'] ?? 'minimax';
                                                        $modelLabel = $selectedModel === 'multitalk' ? 'Lip-Sync' : 'Standard';
                                                        $modelBg = $selectedModel === 'multitalk' ? 'rgba(251, 191, 36, 0.5)' : 'rgba(59, 130, 246, 0.5)';
                                                    @endphp
                                                    <span style="font-size: 0.45rem; background: {{ $modelBg }}; padding: 0.1rem 0.2rem; border-radius: 0.15rem;">{{ __($modelLabel) }}</span>
                                                </button>
                                            @elseif($isGeneratingVideo)
                                                {{-- Video Generation Progress - matches original wizard --}}
                                                @php
                                                    $videoStatus = $shot['videoStatus'] ?? 'generating';
                                                    $selectedModel = $shot['selectedVideoModel'] ?? 'minimax';
                                                    $modelLabel = $selectedModel === 'multitalk' ? 'Lip-Sync' : 'Standard';
                                                @endphp
                                                <div
                                                    x-data="{
                                                        elapsedSeconds: 0,
                                                        checkCount: 0,
                                                        init() {
                                                            this.startTimer();
                                                            // Listen for poll events to update check count
                                                            this.$watch('$wire.pendingJobs', () => this.checkCount++);
                                                        },
                                                        startTimer() {
                                                            setInterval(() => {
                                                                this.elapsedSeconds++;
                                                            }, 1000);
                                                        },
                                                        formatTime(seconds) {
                                                            const mins = Math.floor(seconds / 60);
                                                            const secs = seconds % 60;
                                                            return mins + ':' + (secs < 10 ? '0' : '') + secs;
                                                        }
                                                    }"
                                                    style="text-align: center; padding: 0.5rem;">
                                                    {{-- Rendering Status --}}
                                                    <div style="font-size: 0.7rem; color: #67e8f9; font-weight: 600; margin-bottom: 0.3rem;">
                                                        ⏳ {{ __('Rendering...') }} <span x-text="formatTime(elapsedSeconds)"></span>
                                                    </div>

                                                    {{-- Check count and expected time --}}
                                                    <div style="font-size: 0.55rem; color: rgba(255,255,255,0.5); margin-bottom: 0.4rem;">
                                                        {{ __('Check') }} #<span x-text="Math.max(1, checkCount)"></span> • {{ __('Usually 1-3 min') }}
                                                    </div>

                                                    {{-- Progress Bar --}}
                                                    <div style="height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-bottom: 0.4rem;">
                                                        <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6); animation: progress-indeterminate 1.5s infinite linear;"></div>
                                                    </div>

                                                    {{-- Model Badge --}}
                                                    <span style="display: inline-block; font-size: 0.55rem; background: rgba(59, 130, 246, 0.3); color: #60a5fa; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-weight: 500;">
                                                        🎬 {{ __($modelLabel) }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if($hasVideo && !$isLastShot)
                                                {{-- Capture Frame to Next Shot --}}
                                                <button type="button"
                                                        wire:click.stop="openFrameCaptureModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="width: 100%; padding: 0.3rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3)); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 0.3rem; color: white; cursor: pointer; font-size: 0.6rem; font-weight: 500;">
                                                    🎯 {{ __('Capture') }} → {{ __('Shot') }} {{ $shotIndex + 2 }}
                                                </button>
                                            @endif

                                            @if($hasVideo)
                                                {{-- Re-Animate - Opens Video Model Selector --}}
                                                <button type="button"
                                                        wire:click.stop="openVideoModelSelector({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="width: 100%; padding: 0.25rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.3rem; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 0.55rem;">
                                                    🔄 {{ __('Re-Animate') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reset Button --}}
                    <div style="text-align: center; padding-top: 0.6rem; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 0.6rem;">
                        <button type="button"
                                wire:click="resetDecomposition({{ $multiShotSceneIndex }})"
                                style="padding: 0.35rem 0.75rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.25rem; color: #ef4444; font-size: 0.7rem; cursor: pointer;">
                            🗑️ {{ __('Reset Decomposition') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; flex-shrink: 0;">
            <button type="button"
                    wire:click="closeMultiShotModal"
                    style="padding: 0.4rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.375rem; color: white; cursor: pointer; font-size: 0.85rem;">
                {{ __('Close') }}
            </button>
        </div>
    </div>
</div>
@endif

{{-- Video Model Selector Popup --}}
@if($showVideoModelSelector ?? false)
<div class="vw-popup-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 2000;"
     wire:click.self="closeVideoModelSelector">
    <div style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(6, 182, 212, 0.4); border-radius: 0.75rem; width: 320px; max-width: 95vw; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        {{-- Popup Header --}}
        <div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(59, 130, 246, 0.2)); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0; color: white; font-size: 0.95rem; font-weight: 600;">🎬 {{ __('Animation Model') }}</h4>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Shot') }} {{ ($videoModelSelectorShotIndex ?? 0) + 1 }}</p>
            </div>
            <button type="button" wire:click="closeVideoModelSelector" style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Model Selection --}}
        <div style="padding: 1rem;">
            @php
                $selectorShot = $multiShotMode['decomposedScenes'][$videoModelSelectorSceneIndex ?? 0]['shots'][$videoModelSelectorShotIndex ?? 0] ?? [];
                $hasDialogue = !empty($selectorShot['dialogue']);
                $hasAudio = !empty($selectorShot['audioUrl']) || !empty($selectorShot['voiceoverUrl']);
                $currentModel = $selectorShot['selectedVideoModel'] ?? 'minimax';
                $currentDuration = $selectorShot['selectedDuration'] ?? $selectorShot['duration'] ?? 6;
                $multitalkAvailable = !empty(get_option('runpod_multitalk_endpoint', ''));
            @endphp

            {{-- MiniMax Option --}}
            <label style="display: block; cursor: pointer; margin-bottom: 0.75rem;">
                <div style="background: {{ $currentModel === 'minimax' ? 'rgba(6, 182, 212, 0.2)' : 'rgba(255,255,255,0.05)' }}; border: 2px solid {{ $currentModel === 'minimax' ? 'rgba(6, 182, 212, 0.6)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.5rem; padding: 0.75rem; transition: all 0.2s;"
                     wire:click="setVideoModel('minimax')">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 18px; height: 18px; border: 2px solid {{ $currentModel === 'minimax' ? '#06b6d4' : 'rgba(255,255,255,0.3)' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                @if($currentModel === 'minimax')
                                    <div style="width: 10px; height: 10px; background: #06b6d4; border-radius: 50%;"></div>
                                @endif
                            </div>
                            <div>
                                <div style="color: white; font-weight: 600; font-size: 0.9rem;">MiniMax</div>
                                <div style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('High quality I2V animation') }}</div>
                            </div>
                        </div>
                        <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600;">{{ __('Recommended') }}</span>
                    </div>
                    <div style="margin-top: 0.5rem; padding-left: 1.75rem;">
                        <div style="display: flex; gap: 0.25rem; font-size: 0.65rem; color: rgba(255,255,255,0.5);">
                            <span style="background: rgba(59,130,246,0.2); padding: 0.1rem 0.3rem; border-radius: 0.2rem;">5-10s</span>
                            <span style="background: rgba(139,92,246,0.2); padding: 0.1rem 0.3rem; border-radius: 0.2rem;">{{ __('Most scenes') }}</span>
                        </div>
                    </div>
                </div>
            </label>

            {{-- Multitalk Option --}}
            <label style="display: block; cursor: {{ $multitalkAvailable ? 'pointer' : 'not-allowed' }}; opacity: {{ $multitalkAvailable ? '1' : '0.5' }}; margin-bottom: 1rem;">
                <div style="background: {{ $currentModel === 'multitalk' ? 'rgba(251, 191, 36, 0.2)' : 'rgba(255,255,255,0.05)' }}; border: 2px solid {{ $currentModel === 'multitalk' ? 'rgba(251, 191, 36, 0.6)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.5rem; padding: 0.75rem; transition: all 0.2s;"
                     @if($multitalkAvailable) wire:click="setVideoModel('multitalk')" @endif>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 18px; height: 18px; border: 2px solid {{ $currentModel === 'multitalk' ? '#fbbf24' : 'rgba(255,255,255,0.3)' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                @if($currentModel === 'multitalk')
                                    <div style="width: 10px; height: 10px; background: #fbbf24; border-radius: 50%;"></div>
                                @endif
                            </div>
                            <div>
                                <div style="color: white; font-weight: 600; font-size: 0.9rem;">Multitalk</div>
                                <div style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Lip-sync for dialogue scenes') }}</div>
                            </div>
                        </div>
                        @if($hasDialogue)
                            <span style="background: rgba(251, 191, 36, 0.2); color: #fbbf24; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600;">💬 {{ __('Dialogue') }}</span>
                        @endif
                    </div>
                    <div style="margin-top: 0.5rem; padding-left: 1.75rem;">
                        <div style="display: flex; gap: 0.25rem; font-size: 0.65rem; color: rgba(255,255,255,0.5);">
                            <span style="background: rgba(251,191,36,0.2); padding: 0.1rem 0.3rem; border-radius: 0.2rem;">5-20s</span>
                            <span style="background: rgba(251,191,36,0.2); padding: 0.1rem 0.3rem; border-radius: 0.2rem;">{{ __('Requires audio') }}</span>
                        </div>
                        @if(!$multitalkAvailable)
                            <div style="margin-top: 0.35rem; font-size: 0.6rem; color: #ef4444;">⚠️ {{ __('RunPod Multitalk endpoint not configured') }}</div>
                        @elseif(!$hasAudio && $currentModel === 'multitalk')
                            <div style="margin-top: 0.35rem; font-size: 0.6rem; color: #f59e0b;">⚠️ {{ __('Generate voiceover first for lip-sync') }}</div>
                        @endif
                    </div>
                </div>
            </label>

            {{-- Duration Selector --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.4rem;">{{ __('Duration') }}</label>
                @php
                    // MiniMax supports 5s, 6s, 10s durations
                    // Multitalk supports variable durations up to 20s
                    $availableDurations = $currentModel === 'multitalk' ? [5, 10, 15, 20] : [5, 6, 10];
                @endphp
                <div style="display: flex; gap: 0.35rem;">
                    @foreach($availableDurations as $dur)
                        @php
                            $isSelected = $currentDuration == $dur;
                            $durColor = $dur <= 5 ? 'rgba(34, 197, 94' : ($dur <= 6 ? 'rgba(234, 179, 8' : 'rgba(59, 130, 246');
                        @endphp
                        <button type="button"
                                wire:click="setVideoModelDuration({{ $dur }})"
                                style="flex: 1; padding: 0.5rem; font-size: 0.8rem; font-weight: 600; background: {{ $isSelected ? $durColor . ', 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $isSelected ? $durColor . ', 0.5)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.35rem; color: white; cursor: pointer;">
                            {{ $dur }}s
                        </button>
                    @endforeach
                </div>
                @if($currentModel === 'minimax')
                    <div style="margin-top: 0.35rem; font-size: 0.6rem; color: rgba(255,255,255,0.5);">💡 {{ __('6s recommended for most shots') }}</div>
                @endif
            </div>

            {{-- Generate Button --}}
            <button type="button"
                    wire:click="confirmVideoModelAndGenerate"
                    wire:loading.attr="disabled"
                    style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #06b6d4, #3b82f6); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span wire:loading.remove wire:target="confirmVideoModelAndGenerate">
                    🎬 {{ __('Generate Animation') }}
                </span>
                <span wire:loading wire:target="confirmVideoModelAndGenerate">
                    <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Starting...') }}
                </span>
            </button>

            {{-- Pre-configure for waiting shots --}}
            @php
                $waitingShots = collect($multiShotMode['decomposedScenes'][$videoModelSelectorSceneIndex ?? 0]['shots'] ?? [])
                    ->filter(fn($s, $i) => $i > ($videoModelSelectorShotIndex ?? 0) && empty($s['videoUrl']) && !empty($s['imageUrl']))
                    ->count();
            @endphp
            @if($waitingShots > 0)
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.75rem; color: rgba(255,255,255,0.7);">
                        <input type="checkbox" wire:model.live="preConfigureWaitingShots" style="accent-color: #06b6d4;">
                        {{ __('Apply to :count waiting shots', ['count' => $waitingShots]) }}
                    </label>
                </div>
            @endif
        </div>
    </div>
</div>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
@keyframes progress-indeterminate {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.shot-hover-overlay:hover {
    opacity: 1 !important;
}
</style>

{{-- Video polling is now handled by Alpine component at the top --}}
