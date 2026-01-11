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
         isPlaying: false,
         currentTime: 0,
         duration: 0,
         volume: 1,
         isMuted: false,
         playbackSpeed: 1,
         isFullscreen: false,
         showSpeedMenu: false,
         progressDragging: false,

         init() {
             console.log('[FrameCapture] Initializing...');
             this.$nextTick(() => {
                 this.setupVideo();
             });

             // Close speed menu on outside click
             document.addEventListener('click', (e) => {
                 if (!e.target.closest('.speed-control')) {
                     this.showSpeedMenu = false;
                 }
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
                 this.duration = video.duration;
             });

             video.addEventListener('canplay', () => {
                 console.log('[FrameCapture] Video can play');
                 this.videoLoaded = true;
             });

             // Track playback state
             video.addEventListener('play', () => this.isPlaying = true);
             video.addEventListener('pause', () => this.isPlaying = false);
             video.addEventListener('timeupdate', () => {
                 if (!this.progressDragging) {
                     this.currentTime = video.currentTime;
                 }
             });
             video.addEventListener('volumechange', () => {
                 this.volume = video.volume;
                 this.isMuted = video.muted;
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
             video.src = '{{ $shot['videoUrl'] }}';
             video.load();
         },

         togglePlay() {
             const video = this.$refs.captureVideo;
             if (!video) return;
             if (video.paused) {
                 video.play();
             } else {
                 video.pause();
             }
         },

         seekTo(e) {
             const video = this.$refs.captureVideo;
             const progressBar = this.$refs.progressBar;
             if (!video || !progressBar) return;

             const rect = progressBar.getBoundingClientRect();
             const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
             video.currentTime = percent * this.duration;
             this.currentTime = video.currentTime;
         },

         setVolume(e) {
             const video = this.$refs.captureVideo;
             const volumeBar = e.target.closest('.volume-slider') || e.target;
             if (!video || !volumeBar) return;

             const rect = volumeBar.getBoundingClientRect();
             const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
             video.volume = percent;
             video.muted = false;
         },

         toggleMute() {
             const video = this.$refs.captureVideo;
             if (!video) return;
             video.muted = !video.muted;
         },

         setPlaybackSpeed(speed) {
             const video = this.$refs.captureVideo;
             if (!video) return;
             video.playbackRate = speed;
             this.playbackSpeed = speed;
             this.showSpeedMenu = false;
         },

         toggleFullscreen() {
             const container = this.$refs.videoContainer;
             if (!container) return;

             if (!document.fullscreenElement) {
                 container.requestFullscreen().then(() => {
                     this.isFullscreen = true;
                 }).catch(err => console.error('Fullscreen error:', err));
             } else {
                 document.exitFullscreen().then(() => {
                     this.isFullscreen = false;
                 });
             }
         },

         formatTime(seconds) {
             if (!seconds || isNaN(seconds)) return '0:00';
             const mins = Math.floor(seconds / 60);
             const secs = Math.floor(seconds % 60);
             return `${mins}:${secs.toString().padStart(2, '0')}`;
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

             // Pause video when capturing
             video.pause();

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
    <div style="max-width: 900px; width: 100%; background: linear-gradient(135deg, #1a1a2e, #0f172a); border-radius: 1rem; border: 1px solid rgba(236, 72, 153, 0.3); overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        {{-- Header --}}
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2);">
            <div>
                <div style="font-size: 1.25rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: linear-gradient(135deg, #ec4899, #db2777); border-radius: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </span>
                    {{ __('Frame Capture') }} - {{ __('Shot') }} {{ $frameCaptureShotIndex + 1 }}
                </div>
                <div style="font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-top: 0.25rem; margin-left: 2.75rem;">{{ __('Scrub video to select frame for next shot') }}</div>
            </div>
            <button type="button" wire:click="closeFrameCaptureModal"
                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white';"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.7)';">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- Video Player Section --}}
        <div style="padding: 1.25rem; background: rgba(0,0,0,0.3);">
            {{-- Video Loading State --}}
            <div x-show="!videoLoaded && !videoError" style="width: 100%; aspect-ratio: 16/9; background: rgba(0,0,0,0.5); border-radius: 0.75rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; border: 3px solid rgba(236, 72, 153, 0.3); border-top-color: #ec4899; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="color: rgba(255,255,255,0.6); font-size: 0.95rem;">{{ __('Loading video...') }}</div>
            </div>

            {{-- Video Error State --}}
            <div x-show="videoError" x-cloak style="width: 100%; aspect-ratio: 16/9; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.75rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; padding: 2rem;">
                <div style="font-size: 3rem;">⚠️</div>
                <div style="color: #ef4444; font-size: 1.1rem; font-weight: 600;">{{ __('Video Load Error') }}</div>
                <div x-text="errorMessage" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-align: center;"></div>
                <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <button @click="retryVideoLoad()" class="fc-btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        {{ __('Retry') }}
                    </button>
                    <button wire:click="closeFrameCaptureModal" class="fc-btn-dark">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>

            {{-- Custom Video Player --}}
            <div x-ref="videoContainer" x-show="videoLoaded || (!videoError && !videoLoaded)" x-bind:style="(!videoLoaded) ? 'opacity: 0; height: 0; position: absolute;' : ''"
                 style="position: relative; border-radius: 0.75rem; overflow: hidden; background: #000;">

                {{-- Video Element (hidden native controls) --}}
                <video x-ref="captureVideo"
                       @click="togglePlay()"
                       playsinline
                       style="width: 100%; display: block; max-height: 450px;"></video>

                {{-- Custom Controls Overlay --}}
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 1rem; padding-top: 3rem;">

                    {{-- Progress Bar --}}
                    <div x-ref="progressBar" @click="seekTo($event)"
                         @mousedown="progressDragging = true"
                         @mouseup="progressDragging = false"
                         @mouseleave="progressDragging = false"
                         @mousemove="if(progressDragging) seekTo($event)"
                         style="width: 100%; height: 6px; background: rgba(255,255,255,0.2); border-radius: 3px; cursor: pointer; margin-bottom: 0.75rem; position: relative;">
                        <div :style="`width: ${(currentTime / duration) * 100}%; height: 100%; background: linear-gradient(90deg, #ec4899, #f472b6); border-radius: 3px; position: relative;`">
                            <div style="position: absolute; right: -6px; top: 50%; transform: translateY(-50%); width: 12px; height: 12px; background: white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>
                        </div>
                    </div>

                    {{-- Controls Row --}}
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        {{-- Play/Pause --}}
                        <button @click="togglePlay()" style="background: none; border: none; color: white; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; justify-content: center;">
                            <template x-if="!isPlaying">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            </template>
                            <template x-if="isPlaying">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                            </template>
                        </button>

                        {{-- Volume Control --}}
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <button @click="toggleMute()" style="background: none; border: none; color: white; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; justify-content: center;">
                                <template x-if="!isMuted && volume > 0.5">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                </template>
                                <template x-if="!isMuted && volume > 0 && volume <= 0.5">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                </template>
                                <template x-if="isMuted || volume === 0">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
                                </template>
                            </button>
                            <div class="volume-slider" @click="setVolume($event)" style="width: 80px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; cursor: pointer; position: relative;">
                                <div :style="`width: ${isMuted ? 0 : volume * 100}%; height: 100%; background: white; border-radius: 2px;`"></div>
                            </div>
                        </div>

                        {{-- Time Display --}}
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.85rem; font-family: monospace;">
                            <span x-text="formatTime(currentTime)"></span> / <span x-text="formatTime(duration)"></span>
                        </div>

                        {{-- Spacer --}}
                        <div style="flex: 1;"></div>

                        {{-- Speed Control --}}
                        <div class="speed-control" style="position: relative;">
                            <button @click="showSpeedMenu = !showSpeedMenu" style="background: rgba(255,255,255,0.1); border: none; color: white; cursor: pointer; padding: 0.35rem 0.75rem; border-radius: 4px; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span x-text="playbackSpeed + 'x'"></span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <div x-show="showSpeedMenu" x-cloak style="position: absolute; bottom: 100%; right: 0; margin-bottom: 0.5rem; background: rgba(30,30,40,0.98); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; overflow: hidden; min-width: 80px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                                <template x-for="speed in [0.5, 0.75, 1, 1.25, 1.5, 2]" :key="speed">
                                    <button @click="setPlaybackSpeed(speed)"
                                            :style="playbackSpeed === speed ? 'background: rgba(236, 72, 153, 0.3); color: #f472b6;' : ''"
                                            style="display: block; width: 100%; padding: 0.5rem 1rem; background: transparent; border: none; color: white; cursor: pointer; text-align: left; font-size: 0.85rem;"
                                            onmouseover="if(!this.style.background.includes('236')) this.style.background='rgba(255,255,255,0.1)';"
                                            onmouseout="if(!this.style.background.includes('236')) this.style.background='transparent';">
                                        <span x-text="speed + 'x'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Fullscreen --}}
                        <button @click="toggleFullscreen()" style="background: none; border: none; color: white; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; justify-content: center;">
                            <template x-if="!isFullscreen">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                            </template>
                            <template x-if="isFullscreen">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg>
                            </template>
                        </button>
                    </div>
                </div>

                {{-- Play overlay (center of video) --}}
                <div x-show="!isPlaying && videoLoaded" @click="togglePlay()" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; background: rgba(0,0,0,0.2);">
                    <div style="width: 72px; height: 72px; background: rgba(236, 72, 153, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 32px rgba(236, 72, 153, 0.4);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="white" style="margin-left: 3px;"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    </div>
                </div>
            </div>

            {{-- Frame Preview Section --}}
            <div x-show="videoLoaded" style="margin-top: 1.25rem; display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: start;">
                {{-- Captured Frame --}}
                <div>
                    <div style="font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ __('Captured Frame') }}</div>
                    <div x-bind:style="capturedFrame ? 'border-color: #10b981;' : 'border-color: rgba(255,255,255,0.3);'"
                         style="background: rgba(0,0,0,0.4); border: 2px dotted; border-radius: 0.5rem; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; overflow: hidden; transition: border-color 0.3s;">
                        <template x-if="capturedFrame">
                            <img :src="capturedFrame" style="width: 100%; height: 100%; object-fit: cover;">
                        </template>
                        <template x-if="!capturedFrame">
                            <div style="text-align: center; padding: 1rem;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" style="margin: 0 auto 0.5rem;">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span style="color: rgba(255,255,255,0.3); font-size: 0.8rem;">{{ __('No frame captured') }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Transfer Arrow --}}
                @if($hasNextShot)
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding-top: 2rem;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(219, 39, 119, 0.2)); border: 1px solid rgba(236, 72, 153, 0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ec4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                    <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ __('Transfer') }}</div>
                </div>

                {{-- Next Shot Start Frame --}}
                <div>
                    <div style="font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ __('Shot') }} {{ $nextShotIndex + 1 }} {{ __('Start Frame') }}</div>
                    <div style="background: rgba(0,0,0,0.4); border: 2px dotted rgba(16, 185, 129, 0.5); border-radius: 0.5rem; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        @if(!empty($nextShot['imageUrl']))
                            <img src="{{ $nextShot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="text-align: center; padding: 1rem;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" style="margin: 0 auto 0.5rem;">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span style="color: rgba(255,255,255,0.3); font-size: 0.8rem;">{{ __('No image yet') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                @else
                {{-- Last Shot Message --}}
                <div style="grid-column: span 2; display: flex; align-items: center; justify-content: center; padding: 2rem;">
                    <div style="text-align: center; color: rgba(255,255,255,0.4);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 0.5rem; opacity: 0.5;">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <div style="font-size: 0.85rem;">{{ __('This is the last shot') }}</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- CORS Warning (shown if server-side capture was needed) --}}
            <div x-show="corsBlocked" x-cloak style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 0.5rem;">
                <div style="font-size: 0.8rem; color: #fbbf24; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>{{ __('Using server-side capture (CORS restriction detected)') }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; flex-wrap: wrap; background: rgba(0,0,0,0.2);">
            <button type="button"
                    @click="captureCurrentFrame()"
                    :disabled="isCapturing || !videoLoaded"
                    x-bind:class="(!videoLoaded) ? 'fc-btn-disabled' : ''"
                    class="fc-btn-primary">
                <span x-show="!isCapturing" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    {{ __('Capture Current Frame') }}
                </span>
                <span x-show="isCapturing" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 18px; height: 18px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Capturing...') }}
                </span>
            </button>

            <button type="button"
                    @click="captureLastFrame()"
                    :disabled="isCapturing || !videoLoaded"
                    x-bind:class="(!videoLoaded) ? 'fc-btn-disabled' : ''"
                    class="fc-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 4 15 12 5 20 5 4"></polygon>
                    <line x1="19" y1="5" x2="19" y2="19"></line>
                </svg>
                {{ __('Capture Last Frame') }}
            </button>

            {{-- Fix Character Faces Button (shown only if frame captured AND Character Bible enabled with portraits) --}}
            @if($hasCharacterBible && count($charsWithPortraits) > 0)
                <button type="button"
                        x-show="capturedFrame"
                        x-cloak
                        wire:click="openFaceCorrectionPanel"
                        class="fc-btn-orange">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="5"></circle>
                        <path d="M20 21a8 8 0 0 0-16 0"></path>
                    </svg>
                    {{ __('Fix Character Faces') }}
                </button>
            @endif

            @if($hasNextShot)
                <button type="button"
                        wire:click="transferFrameToNextShot"
                        wire:loading.attr="disabled"
                        :disabled="!capturedFrame"
                        x-bind:class="capturedFrame ? 'fc-btn-success' : 'fc-btn-success-disabled'"
                        class="fc-btn-transfer">
                    <span wire:loading.remove wire:target="transferFrameToNextShot" style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                        {{ __('Send to Shot') }} {{ $nextShotIndex + 1 }}
                    </span>
                    <span wire:loading wire:target="transferFrameToNextShot" style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 18px; height: 18px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

/* Button Styles */
.fc-btn-primary {
    flex: 1;
    min-width: 180px;
    padding: 0.85rem 1.25rem;
    background: linear-gradient(135deg, #ec4899, #db2777);
    border: none;
    border-radius: 0.5rem;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(236, 72, 153, 0.35);
}
.fc-btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(236, 72, 153, 0.45);
}
.fc-btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

.fc-btn-secondary {
    flex: 1;
    min-width: 160px;
    padding: 0.85rem 1.25rem;
    background: rgba(30, 30, 45, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.fc-btn-secondary:hover:not(:disabled) {
    background: rgba(40, 40, 60, 0.9);
    border-color: rgba(255, 255, 255, 0.25);
}

.fc-btn-dark {
    padding: 0.6rem 1.25rem;
    background: rgba(30, 30, 45, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}
.fc-btn-dark:hover {
    background: rgba(40, 40, 60, 0.9);
}

.fc-btn-orange {
    flex: 1;
    min-width: 180px;
    padding: 0.85rem 1.25rem;
    background: rgba(249, 115, 22, 0.2);
    border: 1px solid rgba(249, 115, 22, 0.5);
    border-radius: 0.5rem;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.fc-btn-orange:hover {
    background: rgba(249, 115, 22, 0.3);
}

.fc-btn-transfer {
    flex: 1;
    min-width: 160px;
    padding: 0.85rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.fc-btn-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3));
    border: 1px solid rgba(16, 185, 129, 0.5);
    color: white;
    cursor: pointer;
}
.fc-btn-success:hover {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(5, 150, 105, 0.4));
}

.fc-btn-success-disabled {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: rgba(255, 255, 255, 0.4);
    cursor: not-allowed;
}

.fc-btn-disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
}
</style>
