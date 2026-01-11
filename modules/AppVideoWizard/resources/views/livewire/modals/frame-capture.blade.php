{{-- Frame Capture Modal --}}
@if($showFrameCaptureModal)
@php
    $decomposed = $multiShotMode['decomposedScenes'][$frameCaptureSceneIndex] ?? null;
    $shot = $decomposed['shots'][$frameCaptureShotIndex] ?? null;
    $nextShotIndex = $frameCaptureShotIndex + 1;
    $nextShot = $decomposed['shots'][$nextShotIndex] ?? null;
    $hasNextShot = $nextShot !== null;
    $hasCharacterBible = ($sceneMemory['characterBible']['enabled'] ?? false) && !empty($sceneMemory['characterBible']['characters'] ?? []);
    $charsWithPortraits = collect($sceneMemory['characterBible']['characters'] ?? [])->filter(fn($c) => !empty($c['referenceImage']) && ($c['referenceImageStatus'] ?? '') === 'ready')->values()->all();
@endphp

@if($shot && !empty($shot['videoUrl']))
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 1002; padding: 1rem;"
     wire:click.self="closeFrameCaptureModal"
     x-data="{
         capturedFrame: null,
         isCapturing: false,
         videoLoaded: false,
         videoError: false,
         errorMessage: '',
         corsBlocked: false,

         init() {
             console.log('[FrameCapture] Initializing...');
             this.$nextTick(() => {
                 this.setupVideo();
             });
         },

         setupVideo() {
             const video = this.$refs.captureVideo;
             if (!video) {
                 console.error('[FrameCapture] Video element not found');
                 return;
             }

             console.log('[FrameCapture] Setting up video:', '{{ $shot['videoUrl'] }}');

             // Video loaded successfully
             video.addEventListener('loadedmetadata', () => {
                 console.log('[FrameCapture] Video metadata loaded');
                 this.videoLoaded = true;
                 this.videoError = false;
             });

             video.addEventListener('canplay', () => {
                 console.log('[FrameCapture] Video can play');
                 this.videoLoaded = true;
             });

             // Video load error
             video.addEventListener('error', (e) => {
                 console.error('[FrameCapture] Video load error:', e);
                 this.videoError = true;
                 this.errorMessage = 'Video failed to load. The video URL may be invalid or inaccessible.';

                 // Check if it's a CORS error
                 if (video.error) {
                     console.error('[FrameCapture] Video error code:', video.error.code, video.error.message);
                 }
             });

             // Set video source - without crossorigin to allow playback
             // Note: crossorigin is NOT set initially so the video can play
             // Frame capture will handle CORS separately
             video.src = '{{ $shot['videoUrl'] }}';
             video.load();
         },

         async captureCurrentFrame() {
             const video = this.$refs.captureVideo;
             if (!video) {
                 console.error('[FrameCapture] No video element');
                 return;
             }

             if (!this.videoLoaded) {
                 alert('{{ __('Please wait for the video to load') }}');
                 return;
             }

             this.isCapturing = true;
             const currentTime = video.currentTime;
             console.log('[FrameCapture] Capturing frame at:', currentTime);

             try {
                 // Try client-side canvas capture first
                 const canvas = document.createElement('canvas');
                 canvas.width = video.videoWidth || 1280;
                 canvas.height = video.videoHeight || 720;
                 const ctx = canvas.getContext('2d');
                 ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                 // This will throw SecurityError if canvas is tainted by CORS
                 const frameDataUrl = canvas.toDataURL('image/png');

                 console.log('[FrameCapture] Client-side capture successful');
                 this.capturedFrame = frameDataUrl;
                 this.corsBlocked = false;
                 $wire.setCapturedFrame(frameDataUrl);

             } catch (error) {
                 console.warn('[FrameCapture] Client-side capture failed (CORS):', error.message);
                 this.corsBlocked = true;

                 // Fall back to server-side capture
                 console.log('[FrameCapture] Using server-side capture...');
                 try {
                     const result = await $wire.captureFrameServerSide(currentTime);
                     if (result && result.success && result.frameUrl) {
                         console.log('[FrameCapture] Server-side capture successful');
                         this.capturedFrame = result.frameUrl;
                         $wire.setCapturedFrame(result.frameUrl);
                     } else {
                         throw new Error(result?.error || '{{ __('Server-side capture failed') }}');
                     }
                 } catch (serverError) {
                     console.error('[FrameCapture] Server-side capture also failed:', serverError);
                     alert('{{ __('Frame capture failed. Please try again or contact support.') }}');
                 }
             } finally {
                 this.isCapturing = false;
             }
         },

         captureLastFrame() {
             const video = this.$refs.captureVideo;
             if (!video) return;

             if (!this.videoLoaded) {
                 alert('{{ __('Please wait for the video to load') }}');
                 return;
             }

             this.isCapturing = true;
             console.log('[FrameCapture] Seeking to last frame, duration:', video.duration);

             // Seek to just before the end
             video.currentTime = Math.max(0, video.duration - 0.1);

             video.onseeked = () => {
                 console.log('[FrameCapture] Seeked to:', video.currentTime);
                 this.captureCurrentFrame();
                 video.onseeked = null;
             };
         },

         retryVideoLoad() {
             this.videoError = false;
             this.videoLoaded = false;
             this.errorMessage = '';

             const video = this.$refs.captureVideo;
             if (video) {
                 video.load();
             }
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
            {{-- Video Loading State --}}
            <div x-show="!videoLoaded && !videoError" style="width: 100%; height: 300px; background: rgba(0,0,0,0.5); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
                <div style="width: 40px; height: 40px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">{{ __('Loading video...') }}</div>
            </div>

            {{-- Video Error State --}}
            <div x-show="videoError" x-cloak style="width: 100%; height: 300px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; padding: 2rem;">
                <div style="font-size: 3rem;">⚠️</div>
                <div style="color: #ef4444; font-size: 1rem; font-weight: 600;">{{ __('Video Load Error') }}</div>
                <div x-text="errorMessage" style="color: rgba(255,255,255,0.6); font-size: 0.85rem; text-align: center;"></div>
                <div style="display: flex; gap: 0.5rem;">
                    <button @click="retryVideoLoad()" style="padding: 0.5rem 1rem; background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.5rem; color: white; cursor: pointer;">
                        🔄 {{ __('Retry') }}
                    </button>
                    <button wire:click="closeFrameCaptureModal" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: white; cursor: pointer;">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>

            {{-- Video Element (no crossorigin attribute to allow playback) --}}
            <video x-ref="captureVideo"
                   x-show="videoLoaded || (!videoError && !videoLoaded)"
                   x-bind:style="videoLoaded ? '' : 'opacity: 0; height: 0; position: absolute;'"
                   controls
                   playsinline
                   style="width: 100%; border-radius: 0.5rem; max-height: 400px;"></video>

            {{-- Frame Preview --}}
            <div x-show="videoLoaded" style="margin-top: 1rem; display: flex; gap: 1rem; align-items: flex-start;">
                <div style="flex: 1;">
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem;">{{ __('CAPTURED FRAME') }}</div>
                    <div x-bind:style="capturedFrame ? 'border-color: rgba(16, 185, 129, 0.5)' : 'border-color: rgba(255,255,255,0.2)'"
                         style="background: rgba(0,0,0,0.5); border: 2px dashed; border-radius: 0.5rem; height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
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

            {{-- CORS Warning (shown if server-side capture was needed) --}}
            <div x-show="corsBlocked" x-cloak style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 0.5rem;">
                <div style="font-size: 0.75rem; color: #fbbf24; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <span>{{ __('Using server-side capture (CORS restriction detected)') }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button"
                    @click="captureCurrentFrame()"
                    :disabled="isCapturing || !videoLoaded"
                    x-bind:style="(!videoLoaded) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
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
                    :disabled="isCapturing || !videoLoaded"
                    x-bind:style="(!videoLoaded) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                    style="flex: 1; min-width: 150px; padding: 0.75rem; background: rgba(6, 182, 212, 0.2); border: 1px solid rgba(6, 182, 212, 0.5); border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                ⏭️ {{ __('Capture Last Frame') }}
            </button>

            {{-- Fix Character Faces Button (shown only if frame captured AND Character Bible enabled with portraits) --}}
            @if($hasCharacterBible && count($charsWithPortraits) > 0)
                <button type="button"
                        x-show="capturedFrame"
                        x-cloak
                        wire:click="openFaceCorrectionPanel"
                        style="flex: 1; min-width: 180px; padding: 0.75rem; background: rgba(249, 115, 22, 0.2); border: 1px solid rgba(249, 115, 22, 0.5); border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    🎭 {{ __('Fix Character Faces') }}
                </button>
            @endif

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

        {{-- Face Correction Panel (hidden by default) --}}
        @if($showFaceCorrectionPanel ?? false)
            @include('appvideowizard::livewire.modals.partials._face-correction-panel', [
                'characters' => $charsWithPortraits,
                'capturedFrame' => $capturedFrame
            ])
        @endif
    </div>
</div>
@endif
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
[x-cloak] { display: none !important; }
</style>
