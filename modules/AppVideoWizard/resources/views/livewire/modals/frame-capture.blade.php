{{-- Frame Capture Modal --}}
@if($showFrameCaptureModal)
@php
    $decomposed = $multiShotMode['decomposedScenes'][$frameCaptureSceneIndex] ?? null;
    $shot = $decomposed['shots'][$frameCaptureShotIndex] ?? null;
    $nextShotIndex = $frameCaptureShotIndex + 1;
    $nextShot = $decomposed['shots'][$nextShotIndex] ?? null;
    $hasNextShot = $nextShot !== null;
@endphp

@if($shot && !empty($shot['videoUrl']))
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 1002; padding: 1rem;"
     wire:click.self="closeFrameCaptureModal"
     x-data="{
         capturedFrame: null,
         isCapturing: false,

         captureCurrentFrame() {
             const video = this.$refs.captureVideo;
             if (!video) return;

             this.isCapturing = true;
             try {
                 const canvas = document.createElement('canvas');
                 canvas.width = video.videoWidth || 1280;
                 canvas.height = video.videoHeight || 720;
                 const ctx = canvas.getContext('2d');
                 ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                 this.capturedFrame = canvas.toDataURL('image/png');
                 $wire.setCapturedFrame(this.capturedFrame);
             } catch (error) {
                 console.error('Frame capture failed:', error);
                 alert('Failed to capture frame. Please try again.');
             } finally {
                 this.isCapturing = false;
             }
         },

         captureLastFrame() {
             const video = this.$refs.captureVideo;
             if (!video) return;

             this.isCapturing = true;
             video.currentTime = video.duration - 0.1;
             video.onseeked = () => {
                 this.captureCurrentFrame();
                 video.onseeked = null;
             };
         }
     }">
    <div style="max-width: 800px; width: 100%; background: linear-gradient(135deg, #1a1a2e, #0f172a); border-radius: 1rem; border: 1px solid rgba(139, 92, 246, 0.3); overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 1.1rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.3rem;">🎯</span> {{ __('Frame Capture') }} - {{ __('Shot') }} {{ $frameCaptureShotIndex + 1 }}
                </div>
                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">{{ __('Scrub video to select frame for next shot') }}</div>
            </div>
            <button type="button" wire:click="closeFrameCaptureModal" style="background: none; border: none; color: rgba(255,255,255,0.5); font-size: 1.5rem; cursor: pointer; padding: 0.5rem;">&times;</button>
        </div>

        {{-- Video Player --}}
        <div style="padding: 1rem; background: rgba(0,0,0,0.3);">
            <video x-ref="captureVideo"
                   src="{{ $shot['videoUrl'] }}"
                   crossorigin="anonymous"
                   controls
                   style="width: 100%; border-radius: 0.5rem; max-height: 400px;"></video>

            {{-- Frame Preview --}}
            <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: flex-start;">
                <div style="flex: 1;">
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem;">{{ __('CAPTURED FRAME') }}</div>
                    <div style="background: rgba(0,0,0,0.5); border: 2px dashed {{ $capturedFrame ? 'rgba(16, 185, 129, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.5rem; height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <template x-if="capturedFrame">
                            <img :src="capturedFrame" style="width: 100%; height: 100%; object-fit: cover;">
                        </template>
                        <template x-if="!capturedFrame">
                            <span style="color: rgba(255,255,255,0.3); font-size: 0.85rem;">{{ __('Click "Capture Current Frame"') }}</span>
                        </template>
                    </div>
                </div>

                @if($hasNextShot)
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 1rem;">
                        <div style="font-size: 2rem; color: rgba(139, 92, 246, 0.5);">→</div>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4);">{{ __('TRANSFER') }}</div>
                    </div>

                    <div style="flex: 1;">
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem;">{{ __('SHOT') }} {{ $nextShotIndex + 1 }} {{ __('START FRAME') }}</div>
                        <div style="background: rgba(0,0,0,0.5); border: 2px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            @if(!empty($nextShot['imageUrl']))
                                <img src="{{ $nextShot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <span style="color: rgba(255,255,255,0.3); font-size: 0.85rem;">{{ __('No image yet') }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div style="flex: 1; text-align: center; padding: 2rem;">
                        <div style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">{{ __('This is the last shot') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button"
                    @click="captureCurrentFrame()"
                    :disabled="isCapturing"
                    style="flex: 1; min-width: 150px; padding: 0.75rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span x-show="!isCapturing">📸 {{ __('Capture Current Frame') }}</span>
                <span x-show="isCapturing">
                    <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Capturing...') }}
                </span>
            </button>

            <button type="button"
                    @click="captureLastFrame()"
                    :disabled="isCapturing"
                    style="flex: 1; min-width: 150px; padding: 0.75rem; background: rgba(6, 182, 212, 0.2); border: 1px solid rgba(6, 182, 212, 0.5); border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                ⏭️ {{ __('Capture Last Frame') }}
            </button>

            @if($hasNextShot)
                <button type="button"
                        wire:click="transferFrameToNextShot"
                        wire:loading.attr="disabled"
                        :disabled="!capturedFrame"
                        x-bind:style="capturedFrame ? 'background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3)); border: 1px solid rgba(16, 185, 129, 0.5); color: white; cursor: pointer;' : 'background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: rgba(255,255,255,0.4); cursor: not-allowed;'"
                        style="flex: 1; min-width: 200px; padding: 0.75rem; border-radius: 0.5rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <span wire:loading.remove wire:target="transferFrameToNextShot">➡️ {{ __('Send to Shot') }} {{ $nextShotIndex + 1 }}</span>
                    <span wire:loading wire:target="transferFrameToNextShot">
                        <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Transferring...') }}
                    </span>
                </button>
            @endif
        </div>
    </div>
</div>
@endif
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
