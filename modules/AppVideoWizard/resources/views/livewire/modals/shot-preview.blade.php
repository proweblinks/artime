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
         previewTab: '{{ $hasVideo ? 'video' : 'image' }}',

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
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 2147483648; padding: 0.5rem; overflow-y: auto;"
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
            {{-- Tab Buttons (Alpine.js for instant switching, no server round-trip) --}}
            @if($hasVideo)
                <div style="display: flex; gap: 0.4rem; padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.2); flex-shrink: 0;">
                    <button type="button"
                            @click="previewTab = 'image'"
                            :style="previewTab === 'image'
                                ? 'padding: 0.35rem 0.75rem; background: rgba(139, 92, 246, 0.3); border: 1px solid rgba(139, 92, 246, 0.5); border-radius: 0.4rem; color: white; cursor: pointer; font-size: 0.8rem; font-weight: 500;'
                                : 'padding: 0.35rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.4rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.8rem; font-weight: 500;'">
                        🖼️ {{ __('Image') }}
                    </button>
                    <button type="button"
                            @click="previewTab = 'video'"
                            :style="previewTab === 'video'
                                ? 'padding: 0.35rem 0.75rem; background: rgba(139, 92, 246, 0.3); border: 1px solid rgba(139, 92, 246, 0.5); border-radius: 0.4rem; color: white; cursor: pointer; font-size: 0.8rem; font-weight: 500;'
                                : 'padding: 0.35rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.4rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.8rem; font-weight: 500;'">
                        🎬 {{ __('Video') }}
                    </button>
                </div>
            @endif

            {{-- Preview Container --}}
            <div style="display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0,0,0,0.4); min-height: 280px; height: 45vh; position: relative; flex-shrink: 0;">
                {{-- Image Preview (Alpine.js controlled visibility) --}}
                <div x-show="previewTab === 'image' || !{{ $hasVideo ? 'true' : 'false' }}"
                     style="position: absolute; inset: 1rem; display: flex; align-items: center; justify-content: center;">
                    @if($hasImage)
                        <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotPreviewShotIndex + 1 }}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.6);">
                    @else
                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                            <span style="font-size: 3rem;">🖼️</span>
                            <div style="margin-top: 0.5rem; font-size: 0.9rem;">{{ __('No image generated yet') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Video Preview (Alpine.js controlled visibility) --}}
                @if($hasVideo)
                    <div x-show="previewTab === 'video'"
                         style="position: absolute; inset: 1rem; display: flex; align-items: center; justify-content: center;">
                        <video
                            src="{{ $shot['videoUrl'] }}"
                            controls
                            autoplay
                            x-on:ended="onVideoEnded()"
                            style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.6);">
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

            {{-- Speech/Text Content Section --}}
            @php
                $hasDialogue = !empty($shot['dialogue']);
                $hasMonologue = !empty($shot['monologue']);
                $hasNarration = !empty($shot['narration']);
                $hasVisualContext = !empty($shot['visualContext']);
                $speechIndicator = $shot['speechIndicator'] ?? null;
                $speechType = $shot['speechType'] ?? 'narrator';
                $speakingCharacter = $shot['speakingCharacter'] ?? $shot['speaker'] ?? null;
                $needsLipSync = $shot['needsLipSync'] ?? false;
                $hasAudioReady = !empty($shot['audioUrl']) && ($shot['audioStatus'] ?? '') === 'ready';

                // Determine what content to show
                $showSpeechSection = $hasDialogue || $hasMonologue || $hasNarration || $hasVisualContext;
            @endphp

            @if($showSpeechSection)
            <div style="padding: 0.75rem; background: linear-gradient(135deg, rgba(236, 72, 153, 0.08), rgba(139, 92, 246, 0.08)); border-top: 1px solid rgba(236, 72, 153, 0.2); flex-shrink: 0;">
                {{-- Section Header with Speech Type Badge --}}
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                    @if($speechType === 'dialogue')
                        {{-- Dialogue: Character talking to others - needs Multitalk lip-sync --}}
                        <span style="background: linear-gradient(135deg, #ec4899, #8b5cf6); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700; color: white;">
                            💬 {{ __('DIALOGUE') }}
                        </span>
                        <span style="background: rgba(236, 72, 153, 0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #f9a8d4;">
                            🎬 {{ __('Multitalk Lip-Sync') }}
                        </span>
                    @elseif($speechType === 'monologue')
                        {{-- Character Monologue: On-screen character speaking alone - needs Multitalk lip-sync --}}
                        <span style="background: linear-gradient(135deg, #8b5cf6, #6366f1); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700; color: white;">
                            🗣️ {{ __('CHARACTER MONOLOGUE') }}
                        </span>
                        <span style="background: rgba(139, 92, 246, 0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #c4b5fd;">
                            🎬 {{ __('Multitalk Lip-Sync') }}
                        </span>
                    @elseif($speechType === 'narrator' || $hasNarration)
                        {{-- Narrator: Off-screen voice - TTS only, no lip-sync --}}
                        <span style="background: rgba(100, 116, 139, 0.4); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #e2e8f0;">
                            🎙️ {{ __('NARRATOR') }}
                        </span>
                        <span style="background: rgba(100, 116, 139, 0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #94a3b8;">
                            🔊 {{ __('Voiceover Only') }}
                        </span>
                    @elseif($speechType === 'internal')
                        {{-- Internal thoughts: Character's inner voice - TTS only, no lip-sync --}}
                        <span style="background: rgba(168, 85, 247, 0.3); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #d8b4fe;">
                            💭 {{ __('INTERNAL THOUGHTS') }}
                        </span>
                        <span style="background: rgba(168, 85, 247, 0.15); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #c4b5fd;">
                            🔊 {{ __('Voiceover Only') }}
                        </span>
                    @elseif($hasDialogue || $hasMonologue)
                        {{-- Fallback for legacy data without proper speechType --}}
                        @if($needsLipSync)
                            <span style="background: linear-gradient(135deg, #ec4899, #8b5cf6); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700; color: white;">
                                💬 {{ __('SPEECH') }}
                            </span>
                            <span style="background: rgba(236, 72, 153, 0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #f9a8d4;">
                                🎬 {{ __('Lip-Sync') }}
                            </span>
                        @else
                            <span style="background: rgba(139, 92, 246, 0.3); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #c4b5fd;">
                                🗣️ {{ __('SPEECH') }}
                            </span>
                            <span style="background: rgba(139, 92, 246, 0.15); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; color: #a78bfa;">
                                🔊 {{ __('Voiceover') }}
                            </span>
                        @endif
                    @elseif($hasVisualContext)
                        <span style="background: rgba(59, 130, 246, 0.2); padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #93c5fd;">
                            👁️ {{ __('VISUAL') }}
                        </span>
                    @endif

                    {{-- Speaker name (for character speech types) --}}
                    @if($speakingCharacter && in_array($speechType, ['dialogue', 'monologue', 'internal']))
                        <span style="color: rgba(255,255,255,0.8); font-size: 0.8rem; font-weight: 600; padding: 0.15rem 0.4rem; background: rgba(255,255,255,0.1); border-radius: 0.25rem;">
                            {{ $speakingCharacter }}
                        </span>
                    @endif

                    {{-- Audio ready indicator --}}
                    @if($hasAudioReady)
                        <span style="background: rgba(16, 185, 129, 0.3); padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem; color: #34d399;">
                            🎤 {{ __('Audio Ready') }}
                        </span>
                    @endif
                </div>

                {{-- Content Box --}}
                <div style="background: rgba(0,0,0,0.3); border-radius: 0.5rem; padding: 0.75rem; border-left: 3px solid {{ $hasDialogue || $hasMonologue ? '#ec4899' : ($hasNarration ? '#64748b' : '#3b82f6') }};">
                    @if($hasDialogue || $hasMonologue)
                        <p style="color: rgba(255,255,255,0.95); font-size: 0.95rem; line-height: 1.5; margin: 0; font-style: italic;">
                            "{{ $shot['dialogue'] ?? $shot['monologue'] }}"
                        </p>
                    @elseif($hasNarration)
                        <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                            {{ $shot['narration'] }}
                        </p>
                    @elseif($hasVisualContext)
                        <p style="color: rgba(255,255,255,0.7); font-size: 0.85rem; line-height: 1.4; margin: 0;">
                            {{ $shot['visualContext'] }}
                        </p>
                    @endif
                </div>

                {{-- Generation Method Info --}}
                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    {{-- Generation method indicator --}}
                    @if($needsLipSync || in_array($speechType, ['dialogue', 'monologue']))
                        <div style="display: flex; align-items: center; gap: 0.3rem; font-size: 0.7rem;">
                            <span style="color: rgba(236, 72, 153, 0.9);">⚡</span>
                            <span style="color: rgba(255,255,255,0.6);">{{ __('Generation') }}:</span>
                            <span style="color: #f472b6; font-weight: 600;">{{ __('Multitalk') }}</span>
                            <span style="color: rgba(255,255,255,0.4);">({{ __('lip-sync video with audio') }})</span>
                        </div>
                    @else
                        <div style="display: flex; align-items: center; gap: 0.3rem; font-size: 0.7rem;">
                            <span style="color: rgba(100, 116, 139, 0.9);">⚡</span>
                            <span style="color: rgba(255,255,255,0.6);">{{ __('Generation') }}:</span>
                            <span style="color: #94a3b8; font-weight: 600;">{{ __('TTS') }}</span>
                            <span style="color: rgba(255,255,255,0.4);">({{ __('audio track only') }})</span>
                        </div>
                    @endif

                    @if($shot['partOfVoiceover'] ?? false)
                        <span style="font-size: 0.65rem; color: #94a3b8; padding: 0.15rem 0.4rem; background: rgba(100, 116, 139, 0.2); border-radius: 0.2rem;">
                            {{ __('Part of scene voiceover') }}
                        </span>
                    @endif
                </div>
            </div>
            @else
            {{-- No speech content - show silent shot indicator --}}
            <div style="padding: 0.5rem 0.75rem; background: rgba(100, 116, 139, 0.1); border-top: 1px solid rgba(100, 116, 139, 0.2); flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: rgba(100, 116, 139, 0.2); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; color: #94a3b8;">
                        🔇 {{ __('SILENT SHOT') }}
                    </span>
                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                        {{ $shot['type'] === 'establishing' ? __('Establishing shot - sets the scene') : ($shot['type'] === 'reaction' ? __('Reaction shot - visual response') : __('No dialogue or narration')) }}
                    </span>
                </div>
            </div>
            @endif

            {{-- Prompts Section (compact) --}}
            <div style="padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    {{-- Image Prompt --}}
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.4rem; padding: 0.4rem;">
                        <div style="font-size: 0.6rem; color: rgba(16, 185, 129, 0.9); margin-bottom: 0.2rem; font-weight: 600;">🖼️ {{ __('IMAGE PROMPT') }}</div>
                        <div style="font-size: 0.7rem; color: rgba(167, 243, 208, 0.95); line-height: 1.3; max-height: 50px; overflow-y: auto;">
                            {{ $shot['prompt'] ?? $shot['imagePrompt'] ?? __('No prompt') }}
                        </div>
                    </div>
                    {{-- Video Prompt --}}
                    <div style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 0.4rem; padding: 0.4rem;">
                        <div style="font-size: 0.6rem; color: rgba(6, 182, 212, 0.9); margin-bottom: 0.2rem; font-weight: 600;">🎬 {{ __('VIDEO PROMPT') }}</div>
                        <div style="font-size: 0.7rem; color: rgba(103, 232, 249, 0.95); line-height: 1.3; max-height: 50px; overflow-y: auto;">
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
