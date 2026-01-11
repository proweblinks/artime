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
<div class="fc-modal-overlay"
     wire:click.self="closeFrameCaptureModal"
     x-data="{
         capturedFrame: null,
         isCapturing: false,
         videoLoaded: false,
         videoError: false,
         corsBlocked: false,

         init() {
             this.$nextTick(() => {
                 const video = this.$refs.captureVideo;
                 if (video) {
                     video.addEventListener('loadedmetadata', () => {
                         this.videoLoaded = true;
                     });
                     video.addEventListener('error', () => {
                         this.videoError = true;
                     });
                     video.src = '{{ $shot['videoUrl'] }}';
                     video.load();
                 }
             });
         },

         async captureCurrentFrame() {
             const video = this.$refs.captureVideo;
             if (!video || !this.videoLoaded) {
                 console.warn('[FrameCapture] Video not ready');
                 return;
             }

             video.pause();
             this.isCapturing = true;
             this.corsBlocked = false;

             try {
                 // Try client-side canvas capture
                 const canvas = document.createElement('canvas');
                 canvas.width = video.videoWidth || 1280;
                 canvas.height = video.videoHeight || 720;
                 const ctx = canvas.getContext('2d');
                 ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                 // This will throw SecurityError if CORS blocked
                 const frameDataUrl = canvas.toDataURL('image/png');

                 console.log('[FrameCapture] Client-side capture success');
                 this.capturedFrame = frameDataUrl;
                 await $wire.setCapturedFrame(frameDataUrl);
                 this.isCapturing = false;
                 return;
             } catch (clientError) {
                 console.warn('[FrameCapture] Client-side failed (CORS):', clientError.message);
                 this.corsBlocked = true;
             }

             // Fallback to server-side capture
             try {
                 console.log('[FrameCapture] Trying server-side capture at:', video.currentTime);
                 const result = await $wire.captureFrameServerSide(video.currentTime);

                 console.log('[FrameCapture] Server result:', result);

                 if (result && result.success && result.frameUrl) {
                     this.capturedFrame = result.frameUrl;
                     await $wire.setCapturedFrame(result.frameUrl);
                     console.log('[FrameCapture] Server-side capture success');
                 } else {
                     const errorMsg = result?.error || 'Server capture failed';
                     console.error('[FrameCapture] Server error:', errorMsg);
                     alert('Frame capture failed: ' + errorMsg);
                 }
             } catch (serverError) {
                 console.error('[FrameCapture] Server-side exception:', serverError);
                 alert('Frame capture failed. FFmpeg may not be available on the server.');
             }

             this.isCapturing = false;
         },

         captureLastFrame() {
             const video = this.$refs.captureVideo;
             if (!video || !this.videoLoaded) return;
             video.currentTime = Math.max(0, video.duration - 0.1);
             video.onseeked = () => {
                 this.captureCurrentFrame();
                 video.onseeked = null;
             };
         }
     }">

    <div class="fc-modal">
        {{-- Header --}}
        <div class="fc-header">
            <div class="fc-header-left">
                <div class="fc-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <div>
                    <div class="fc-title">Frame Capture - Shot {{ $frameCaptureShotIndex + 1 }}</div>
                    <div class="fc-subtitle">Scrub video to select frame for next shot</div>
                </div>
            </div>
            <button type="button" wire:click="closeFrameCaptureModal" class="fc-close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- Video Player - Native Controls --}}
        <div class="fc-video-container">
            <video x-ref="captureVideo"
                   controls
                   playsinline
                   class="fc-video"></video>
        </div>

        {{-- Frame Preview Section --}}
        <div class="fc-frames-section">
            {{-- Captured Frame --}}
            <div class="fc-frame-box">
                <div class="fc-frame-label">CAPTURED FRAME</div>
                <div class="fc-frame-preview" :class="capturedFrame ? 'has-image' : ''">
                    <template x-if="capturedFrame">
                        <img :src="capturedFrame" class="fc-frame-image">
                    </template>
                    <template x-if="!capturedFrame">
                        <span class="fc-frame-placeholder">Click "Capture Current Frame"</span>
                    </template>
                </div>
            </div>

            {{-- Transfer Arrow --}}
            <div class="fc-transfer">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>TRANSFER</span>
            </div>

            {{-- Next Shot Frame --}}
            <div class="fc-frame-box">
                <div class="fc-frame-label">SHOT {{ $nextShotIndex + 1 }} START FRAME</div>
                <div class="fc-frame-preview next-shot">
                    @if(!empty($nextShot['imageUrl']))
                        <img src="{{ $nextShot['imageUrl'] }}" class="fc-frame-image">
                    @else
                        <span class="fc-frame-placeholder">No image yet</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- CORS Warning --}}
        <div x-show="corsBlocked" x-cloak class="fc-cors-warning">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Using server-side capture (CORS restriction detected)
        </div>

        {{-- Action Buttons --}}
        <div class="fc-actions">
            <button type="button"
                    @click="captureCurrentFrame()"
                    :disabled="isCapturing || !videoLoaded"
                    class="fc-btn fc-btn-pink">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span x-text="isCapturing ? 'Capturing...' : 'Capture Current Frame'"></span>
            </button>

            <button type="button"
                    @click="captureLastFrame()"
                    :disabled="isCapturing || !videoLoaded"
                    class="fc-btn fc-btn-purple-outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 4 15 12 5 20 5 4"/>
                    <line x1="19" y1="5" x2="19" y2="19"/>
                </svg>
                Capture Last Frame
            </button>

            @if($hasNextShot)
                <button type="button"
                        wire:click="transferFrameToNextShot"
                        wire:loading.attr="disabled"
                        :disabled="!capturedFrame"
                        :class="capturedFrame ? 'fc-btn-teal' : 'fc-btn-teal-disabled'"
                        class="fc-btn">
                    <span wire:loading.remove wire:target="transferFrameToNextShot">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                        Send to Shot {{ $nextShotIndex + 1 }}
                    </span>
                    <span wire:loading wire:target="transferFrameToNextShot">Transferring...</span>
                </button>
            @endif
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

.fc-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1002;
    padding: 1rem;
}

.fc-modal {
    width: 100%;
    max-width: 720px;
    background: linear-gradient(180deg, #1e2a3a 0%, #0f172a 100%);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

.fc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.fc-header-left {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.fc-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.fc-title {
    font-size: 16px;
    font-weight: 600;
    color: white;
}

.fc-subtitle {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 2px;
}

.fc-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    padding: 4px;
}
.fc-close:hover {
    color: white;
}

.fc-video-container {
    padding: 16px 20px;
    background: rgba(0, 0, 0, 0.3);
}

.fc-video {
    width: 100%;
    border-radius: 8px;
    background: #000;
    max-height: 340px;
}

.fc-frames-section {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px;
}

.fc-frame-box {
    flex: 1;
}

.fc-frame-label {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.5);
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.fc-frame-preview {
    aspect-ratio: 16/9;
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.3);
}

.fc-frame-preview.has-image {
    border-color: rgba(139, 92, 246, 0.5);
}

.fc-frame-preview.next-shot {
    border-color: rgba(20, 184, 166, 0.5);
}

.fc-frame-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.fc-frame-placeholder {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.3);
    text-align: center;
    padding: 12px;
}

.fc-transfer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding-top: 40px;
    color: rgba(255, 255, 255, 0.4);
}
.fc-transfer span {
    font-size: 10px;
    letter-spacing: 0.5px;
}

.fc-cors-warning {
    margin: 0 20px 12px;
    padding: 10px 14px;
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.3);
    border-radius: 6px;
    font-size: 13px;
    color: #fbbf24;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fc-actions {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.fc-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.fc-btn-pink {
    background: linear-gradient(135deg, #ec4899, #db2777);
    border: none;
    color: white;
}
.fc-btn-pink:hover:not(:disabled) {
    filter: brightness(1.1);
}
.fc-btn-pink:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.fc-btn-purple-outline {
    background: transparent;
    border: 2px solid #8b5cf6;
    color: #a78bfa;
}
.fc-btn-purple-outline:hover:not(:disabled) {
    background: rgba(139, 92, 246, 0.1);
}
.fc-btn-purple-outline:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.fc-btn-teal {
    background: #0d9488;
    border: none;
    color: white;
}
.fc-btn-teal:hover:not(:disabled) {
    background: #0f766e;
}

.fc-btn-teal-disabled {
    background: rgba(20, 184, 166, 0.2);
    border: none;
    color: rgba(255, 255, 255, 0.4);
    cursor: not-allowed;
}
</style>
@endif
@endif
