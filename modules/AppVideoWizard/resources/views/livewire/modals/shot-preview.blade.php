{{-- Shot Preview Modal --}}
@if($showShotPreviewModal)
@php
    $decomposed = $multiShotMode['decomposedScenes'][$shotPreviewSceneIndex] ?? null;
    $shot = $decomposed['shots'][$shotPreviewShotIndex] ?? null;
    $hasImage = $shot && ($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']);
    $hasVideo = $shot && ($shot['videoStatus'] ?? '') === 'ready' && !empty($shot['videoUrl']);
    $isLastShot = $shot && $shotPreviewShotIndex === count($decomposed['shots'] ?? []) - 1;
    $wasTransferred = isset($shot['transferredFrom']);
@endphp

@if($shot)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 1001; padding: 1rem;"
     wire:click.self="closeShotPreviewModal">
    <div style="max-width: 1000px; width: 100%; background: linear-gradient(135deg, #1a1a2e, #0f172a); border-radius: 1rem; border: 1px solid rgba(139, 92, 246, 0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <div style="font-size: 1.1rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: rgba(139, 92, 246, 0.3); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.9rem;">{{ $shotPreviewShotIndex + 1 }}</span>
                    <span>{{ __('Shot Preview') }} - {{ ucfirst($shot['type'] ?? 'Shot') }}</span>
                </div>
                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-top: 0.25rem;">
                    {{ $shot['cameraMovement'] ?? 'static' }} • {{ $shot['selectedDuration'] ?? $shot['duration'] ?? 6 }}s
                    @if($wasTransferred)
                        • <span style="color: #10b981;">🔗 {{ __('Frame from Shot') }} {{ $shot['transferredFrom'] + 1 }}</span>
                    @endif
                </div>
            </div>
            <button type="button" wire:click="closeShotPreviewModal" style="background: none; border: none; color: rgba(255,255,255,0.5); font-size: 1.5rem; cursor: pointer; padding: 0.5rem; transition: color 0.2s;">&times;</button>
        </div>

        {{-- Content Area --}}
        <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
            {{-- Tab Buttons --}}
            @if($hasVideo)
                <div style="display: flex; gap: 0.5rem; padding: 1rem 1rem 0.5rem; background: rgba(0,0,0,0.2);">
                    <button type="button"
                            wire:click="switchShotPreviewTab('image')"
                            style="padding: 0.5rem 1rem; background: {{ $shotPreviewTab === 'image' ? 'rgba(139, 92, 246, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $shotPreviewTab === 'image' ? 'rgba(139, 92, 246, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.5rem; color: {{ $shotPreviewTab === 'image' ? 'white' : 'rgba(255,255,255,0.7)' }}; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                        🖼️ {{ __('Image') }}
                    </button>
                    <button type="button"
                            wire:click="switchShotPreviewTab('video')"
                            style="padding: 0.5rem 1rem; background: {{ $shotPreviewTab === 'video' ? 'rgba(139, 92, 246, 0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $shotPreviewTab === 'video' ? 'rgba(139, 92, 246, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.5rem; color: {{ $shotPreviewTab === 'video' ? 'white' : 'rgba(255,255,255,0.7)' }}; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                        🎬 {{ __('Video') }}
                    </button>
                </div>
            @endif

            {{-- Preview Container --}}
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0,0,0,0.3); min-height: 280px; max-height: 50vh; overflow: hidden;">
                {{-- Image Preview --}}
                <div style="display: {{ $shotPreviewTab === 'image' || !$hasVideo ? 'flex' : 'none' }}; align-items: center; justify-content: center; width: 100%; height: 100%;">
                    @if($hasImage)
                        <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotPreviewShotIndex + 1 }}" style="max-width: 100%; max-height: 48vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.5);">
                    @else
                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                            <span style="font-size: 3rem;">🖼️</span>
                            <div style="margin-top: 0.5rem;">{{ __('No image generated yet') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Video Preview --}}
                @if($hasVideo)
                    <div style="display: {{ $shotPreviewTab === 'video' ? 'flex' : 'none' }}; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <video src="{{ $shot['videoUrl'] }}" controls autoplay style="max-width: 100%; max-height: 48vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.5);"></video>
                    </div>
                @endif
            </div>

            {{-- Prompts Section --}}
            <div style="padding: 1rem; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.1);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    {{-- Image Prompt --}}
                    <div>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-bottom: 0.25rem;">🖼️ {{ __('IMAGE PROMPT') }}</div>
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7); line-height: 1.4; max-height: 60px; overflow-y: auto;">
                            {{ $shot['prompt'] ?? $shot['imagePrompt'] ?? __('No prompt') }}
                        </div>
                    </div>
                    {{-- Video Prompt --}}
                    <div>
                        <div style="font-size: 0.65rem; color: rgba(6, 182, 212, 0.8); margin-bottom: 0.25rem;">🎬 {{ __('VIDEO PROMPT') }}</div>
                        <div style="font-size: 0.75rem; color: rgba(103, 232, 249, 0.9); line-height: 1.4; max-height: 60px; overflow-y: auto;">
                            {{ $shot['videoPrompt'] ?? $shot['narrativeBeat']['motionDescription'] ?? __('Action prompt will be generated from scene data') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; flex-wrap: wrap; flex-shrink: 0;">
            {{-- Image Source Status --}}
            @if($shotPreviewShotIndex === 0)
                <div style="flex: 1; min-width: 120px; padding: 0.6rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; text-align: center;">
                    <span style="color: #10b981; font-size: 0.85rem;">🔗 {{ __('Uses Scene Image') }}</span>
                </div>
            @else
                <div style="flex: 1; min-width: 120px; padding: 0.6rem; background: {{ $hasImage ? 'rgba(16, 185, 129, 0.15)' : 'rgba(139, 92, 246, 0.15)' }}; border: 1px solid {{ $hasImage ? 'rgba(16, 185, 129, 0.3)' : 'rgba(139, 92, 246, 0.3)' }}; border-radius: 0.5rem; text-align: center;">
                    <span style="color: {{ $hasImage ? '#10b981' : '#a78bfa' }}; font-size: 0.85rem;">
                        {{ $hasImage ? '🔗 ' . __('Frame Chain Image') : '⏳ ' . __('Awaiting Frame Transfer') }}
                    </span>
                </div>
            @endif

            @if($hasImage)
                <button type="button"
                        wire:click="generateShotVideo({{ $shotPreviewSceneIndex }}, {{ $shotPreviewShotIndex }})"
                        wire:loading.attr="disabled"
                        style="flex: 1; min-width: 120px; padding: 0.6rem; background: {{ $hasVideo ? 'rgba(6, 182, 212, 0.15)' : 'linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(59, 130, 246, 0.3))' }}; border: 1px solid rgba(6, 182, 212, 0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                    {{ $hasVideo ? '🔄 ' . __('Re-Animate') : '🎬 ' . __('Animate Shot') }}
                </button>
            @endif

            @if($hasVideo && !$isLastShot)
                <button type="button"
                        wire:click="closeShotPreviewModal"
                        x-on:click="setTimeout(() => $wire.openFrameCaptureModal({{ $shotPreviewSceneIndex }}, {{ $shotPreviewShotIndex }}), 100)"
                        style="flex: 1; min-width: 140px; padding: 0.6rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3)); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                    🎯 {{ __('Capture Frame') }} → {{ __('Shot') }} {{ $shotPreviewShotIndex + 2 }}
                </button>
            @endif
        </div>
    </div>
</div>
@endif
@endif
