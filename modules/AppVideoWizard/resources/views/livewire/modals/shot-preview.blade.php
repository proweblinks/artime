{{-- Shot Preview Modal with Auto-Play --}}
@if($showShotPreviewModal)
@php
    $decomposed = $multiShotMode['decomposedScenes'][$shotPreviewSceneIndex] ?? null;
    $shots = $decomposed['shots'] ?? [];
    $shot = $shots[$shotPreviewShotIndex] ?? null;
    $hasImage = $shot && ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
    $hasVideo = $shot && ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
    $isLastShot = $shot && $shotPreviewShotIndex === count($shots) - 1;
    $wasTransferred = isset($shot['transferredFrom']);

    // Calculate consecutive ready shots starting from current position
    $consecutiveReadyCount = 0;
    $consecutiveShots = [];
    for ($i = $shotPreviewShotIndex; $i < count($shots); $i++) {
        $s = $shots[$i];
        if (($s['videoStatus'] ?? '') === 'ready' && !empty($s['videoUrl'])) {
            $consecutiveReadyCount++;
            $consecutiveShots[] = [
                'index' => $i,
                'videoUrl' => $s['videoUrl'],
                'type' => $s['type'] ?? 'shot',
                'duration' => $s['selectedDuration'] ?? $s['duration'] ?? 6,
            ];
        } else {
            break;
        }
    }
    $hasMultipleReady = $consecutiveReadyCount > 1;
@endphp

@if($shot)
<div class="vw-modal-overlay"
     x-data="{
         autoPlayEnabled: {{ $hasMultipleReady ? 'true' : 'false' }},
         currentIndex: 0,
         totalShots: {{ $consecutiveReadyCount }},
         shots: {{ json_encode($consecutiveShots) }},
         isPlaying: false,

         init() {
             if (this.autoPlayEnabled && this.totalShots > 0) {
                 this.isPlaying = true;
             }
         },

         playShot(index) {
             if (index >= 0 && index < this.totalShots) {
                 this.currentIndex = index;
                 const shot = this.shots[index];
                 $wire.navigateToShot({{ $shotPreviewSceneIndex }}, shot.index);
             }
         },

         onVideoEnded() {
             if (this.autoPlayEnabled && this.currentIndex < this.totalShots - 1) {
                 this.currentIndex++;
                 this.playShot(this.currentIndex);
             } else {
                 this.isPlaying = false;
             }
         },

         toggleAutoPlay() {
             this.autoPlayEnabled = !this.autoPlayEnabled;
         },

         nextShot() {
             if (this.currentIndex < this.totalShots - 1) {
                 this.playShot(this.currentIndex + 1);
             }
         },

         prevShot() {
             if (this.currentIndex > 0) {
                 this.playShot(this.currentIndex - 1);
             }
         }
     }"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 1001; padding: 0.5rem; overflow-y: auto;"
     wire:click.self="closeShotPreviewModal">
    <div style="max-width: 900px; width: 100%; background: linear-gradient(135deg, #1a1a2e, #0f172a); border-radius: 0.75rem; border: 1px solid rgba(139, 92, 246, 0.3); overflow: hidden; max-height: 98vh; display: flex; flex-direction: column; margin: auto;">
        {{-- Header (compact) --}}
        <div style="padding: 0.6rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <div style="font-size: 1rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                    <span style="background: rgba(139, 92, 246, 0.3); padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.85rem;">{{ $shotPreviewShotIndex + 1 }}</span>
                    <span>{{ __('Shot Preview') }} - {{ ucfirst($shot['type'] ?? 'Shot') }}</span>

                    {{-- Auto-play Badge --}}
                    @if($hasMultipleReady)
                        <button type="button"
                                x-on:click="toggleAutoPlay()"
                                :style="autoPlayEnabled
                                    ? 'background: linear-gradient(135deg, #8b5cf6, #06b6d4); color: white;'
                                    : 'background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5);'"
                                style="padding: 0.2rem 0.5rem; border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
                            <span x-show="autoPlayEnabled">▶</span>
                            <span x-show="!autoPlayEnabled">⏸</span>
                            <span>{{ __('Auto-playing') }}:</span>
                            <span x-text="(currentIndex + 1) + '{{ __('of') }}' + totalShots"></span>
                        </button>
                    @endif
                </div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 0.15rem;">
                    {{ $shot['cameraMovement'] ?? 'static' }} • {{ $shot['selectedDuration'] ?? $shot['duration'] ?? 6 }}s
                    @if($wasTransferred)
                        • <span style="color: #10b981;">🔗 {{ __('Frame from Shot') }} {{ $shot['transferredFrom'] + 1 }}</span>
                    @endif
                    @if($hasMultipleReady)
                        • <span style="color: #06b6d4;">{{ $consecutiveReadyCount }} {{ __('consecutive shots ready') }}</span>
                    @endif
                </div>
            </div>
            <button type="button" wire:click="closeShotPreviewModal" style="background: none; border: none; color: rgba(255,255,255,0.5); font-size: 1.25rem; cursor: pointer; padding: 0.25rem;">&times;</button>
        </div>

        {{-- Content Area (scrollable) --}}
        <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
            {{-- Tab Buttons --}}
            @if($hasVideo)
                <div style="display: flex; gap: 0.4rem; padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.2); flex-shrink: 0;">
                    <button type="button"
                            wire:click="switchShotPreviewTab('image')"
                            style="padding: 0.35rem 0.75rem; background: {{ $shotPreviewTab === 'image' ? 'rgba(139, 92, 246, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $shotPreviewTab === 'image' ? 'rgba(139, 92, 246, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.4rem; color: {{ $shotPreviewTab === 'image' ? 'white' : 'rgba(255,255,255,0.7)' }}; cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                        🖼️ {{ __('Image') }}
                    </button>
                    <button type="button"
                            wire:click="switchShotPreviewTab('video')"
                            style="padding: 0.35rem 0.75rem; background: {{ $shotPreviewTab === 'video' ? 'rgba(139, 92, 246, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $shotPreviewTab === 'video' ? 'rgba(139, 92, 246, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.4rem; color: {{ $shotPreviewTab === 'video' ? 'white' : 'rgba(255,255,255,0.7)' }}; cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                        🎬 {{ __('Video') }}
                    </button>
                </div>
            @endif

            {{-- Preview Container --}}
            <div style="display: flex; align-items: center; justify-content: center; padding: 0.5rem; background: rgba(0,0,0,0.3); min-height: 200px; max-height: 45vh; position: relative; flex-shrink: 0;">
                {{-- Image Preview --}}
                <div style="display: {{ $shotPreviewTab === 'image' || !$hasVideo ? 'flex' : 'none' }}; align-items: center; justify-content: center; width: 100%; height: 100%;">
                    @if($hasImage)
                        <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotPreviewShotIndex + 1 }}" style="max-width: 100%; max-height: 42vh; object-fit: contain; border-radius: 0.4rem; box-shadow: 0 4px 16px rgba(0,0,0,0.5);">
                    @else
                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                            <span style="font-size: 2.5rem;">🖼️</span>
                            <div style="margin-top: 0.4rem; font-size: 0.85rem;">{{ __('No image generated yet') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Video Preview --}}
                @if($hasVideo)
                    <div style="display: {{ $shotPreviewTab === 'video' ? 'flex' : 'none' }}; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <video
                            src="{{ $shot['videoUrl'] }}"
                            controls
                            autoplay
                            x-on:ended="onVideoEnded()"
                            style="max-width: 100%; max-height: 42vh; object-fit: contain; border-radius: 0.4rem; box-shadow: 0 4px 16px rgba(0,0,0,0.5);">
                        </video>
                    </div>
                @endif

                {{-- Navigation Arrows --}}
                @if($hasMultipleReady)
                    <button type="button"
                            x-on:click="prevShot()"
                            x-show="currentIndex > 0"
                            style="position: absolute; left: 0.25rem; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.7); border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; color: white; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">
                        ‹
                    </button>
                    <button type="button"
                            x-on:click="nextShot()"
                            x-show="currentIndex < totalShots - 1"
                            style="position: absolute; right: 0.25rem; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.7); border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; color: white; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">
                        ›
                    </button>
                @endif
            </div>

            {{-- Action Info Box (compact) --}}
            <div style="padding: 0.5rem 0.75rem; background: rgba(139, 92, 246, 0.1); border-top: 1px solid rgba(139, 92, 246, 0.2); flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-size: 0.65rem; color: #f472b6; font-weight: 600;">🎬 {{ __('ACTION (for video)') }}</span>
                    <span style="font-size: 0.8rem; color: white;">{{ __('Shot') }} {{ $shotPreviewShotIndex + 1 }}: {{ $shot['narrativeBeat']['action'] ?? $shot['action'] ?? __('establish phase') }}</span>
                    @if(!$isLastShot)
                        <span style="font-size: 0.65rem; color: rgba(255,255,255,0.4); font-style: italic;">→ {{ __('Frame chains to next shot') }}</span>
                    @endif
                </div>
            </div>

            {{-- Prompts Section (compact) --}}
            <div style="padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    {{-- Image Prompt --}}
                    <div>
                        <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-bottom: 0.15rem;">🖼️ {{ __('IMAGE PROMPT') }}</div>
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.7); line-height: 1.3; max-height: 45px; overflow-y: auto;">
                            {{ $shot['prompt'] ?? $shot['imagePrompt'] ?? __('No prompt') }}
                        </div>
                    </div>
                    {{-- Video Prompt --}}
                    <div>
                        <div style="font-size: 0.6rem; color: rgba(6, 182, 212, 0.8); margin-bottom: 0.15rem;">🎬 {{ __('VIDEO PROMPT') }}</div>
                        <div style="font-size: 0.7rem; color: rgba(103, 232, 249, 0.9); line-height: 1.3; max-height: 45px; overflow-y: auto;">
                            {{ $shot['videoPrompt'] ?? $shot['narrativeBeat']['motionDescription'] ?? __('Action prompt will be generated') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions Footer (compact) --}}
        <div style="padding: 0.5rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.5rem; flex-wrap: wrap; flex-shrink: 0;">
            {{-- Image Source Status --}}
            @if($shotPreviewShotIndex === 0)
                <button type="button" style="flex: 1; min-width: 100px; padding: 0.5rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.4rem; color: #10b981; cursor: default; font-size: 0.75rem; font-weight: 500;">
                    🔗 {{ __('Uses Scene Image') }}
                </button>
            @else
                <button type="button" style="flex: 1; min-width: 100px; padding: 0.5rem; background: {{ $hasImage ? 'rgba(16, 185, 129, 0.15)' : 'rgba(139, 92, 246, 0.15)' }}; border: 1px solid {{ $hasImage ? 'rgba(16, 185, 129, 0.3)' : 'rgba(139, 92, 246, 0.3)' }}; border-radius: 0.4rem; color: {{ $hasImage ? '#10b981' : '#a78bfa' }}; cursor: default; font-size: 0.75rem; font-weight: 500;">
                    {{ $hasImage ? '🔗 ' . __('Frame Chain Image') : '⏳ ' . __('Awaiting Frame') }}
                </button>
            @endif

            {{-- Re-Animate Button --}}
            @if($hasImage)
                <button type="button"
                        wire:click="openVideoModelSelector({{ $shotPreviewSceneIndex }}, {{ $shotPreviewShotIndex }})"
                        wire:loading.attr="disabled"
                        style="flex: 1; min-width: 100px; padding: 0.5rem; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 0.4rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 500;">
                    🎬 {{ __('Re-Animate') }}
                </button>
            @endif

            {{-- Capture Frame Button --}}
            @if($hasVideo && !$isLastShot)
                <button type="button"
                        wire:click="closeShotPreviewModal"
                        x-on:click="setTimeout(() => $wire.openFrameCaptureModal({{ $shotPreviewSceneIndex }}, {{ $shotPreviewShotIndex }}), 100)"
                        style="flex: 1; min-width: 120px; padding: 0.5rem; background: linear-gradient(135deg, rgba(236, 72, 153, 0.3), rgba(139, 92, 246, 0.3)); border: 1px solid rgba(236, 72, 153, 0.5); border-radius: 0.4rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 500;">
                    🎯 {{ __('Capture Frame') }} → {{ __('Shot') }} {{ $shotPreviewShotIndex + 2 }}
                </button>
            @endif
        </div>
    </div>
</div>
@endif
@endif
