{{-- Multi-Shot Decomposition Modal --}}
@if($showMultiShotModal)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">✂️ {{ __('Multi-Shot Decomposition') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Split scene into multiple camera shots for dynamic storytelling') }}</p>
            </div>
            <button type="button" wire:click="closeMultiShotModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;">
            @php
                $scene = $script['scenes'][$multiShotSceneIndex] ?? null;
                $decomposed = $multiShotMode['decomposedScenes'][$multiShotSceneIndex] ?? null;
            @endphp

            @if($scene)
                {{-- Scene Preview --}}
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        @php
                            $storyboardScene = $storyboard['scenes'][$multiShotSceneIndex] ?? null;
                        @endphp
                        @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                            <img src="{{ $storyboardScene['imageUrl'] }}"
                                 alt="Scene {{ $multiShotSceneIndex + 1 }}"
                                 style="width: 160px; height: 90px; object-fit: cover; border-radius: 0.5rem;">
                        @else
                            <div style="width: 160px; height: 90px; background: rgba(255,255,255,0.05); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: rgba(255,255,255,0.4);">🎬</span>
                            </div>
                        @endif
                        <div style="flex: 1;">
                            <div style="color: white; font-weight: 600; margin-bottom: 0.35rem;">{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</div>
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0; line-height: 1.4;">
                                {{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 150) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if(!$decomposed)
                    {{-- Shot Count Selector --}}
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ __('Number of Shots') }}</label>
                        <div style="display: flex; gap: 0.5rem;">
                            @foreach([2, 3, 4, 5, 6] as $count)
                                <button type="button"
                                        wire:click="$set('multiShotCount', {{ $count }})"
                                        style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid {{ $multiShotCount === $count ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $multiShotCount === $count ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 1rem; font-weight: 600;">
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 0.5rem;">
                            💡 {{ __('More shots = more dynamic scene, but requires more generation') }}
                        </p>
                    </div>

                    {{-- Shot Types Preview --}}
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ __('Shot Sequence Preview') }}</label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
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
                                <div style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; padding: 0.5rem 0.75rem; text-align: center;">
                                    <div style="font-size: 1.25rem;">{{ $shot['icon'] }}</div>
                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __($shot['label']) }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Decompose Button --}}
                    <button type="button"
                            wire:click="decomposeScene({{ $multiShotSceneIndex }})"
                            wire:loading.attr="disabled"
                            wire:target="decomposeScene"
                            style="width: 100%; padding: 0.85rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
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

                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        {{-- Header with stats --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: white; font-weight: 600;">📽️ {{ count($decomposed['shots']) }} SHOTS</span>
                                <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">• {{ $totalDuration }}s total</span>
                            </div>
                            <div style="display: flex; gap: 0.75rem;">
                                <span style="font-size: 0.75rem; color: rgba(16, 185, 129, 0.9);">
                                    🖼️ {{ $imagesReady }}/{{ count($decomposed['shots']) }}
                                </span>
                                <span style="font-size: 0.75rem; color: rgba(6, 182, 212, 0.8);">
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
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                        <button type="button"
                                wire:click="generateAllShots({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="flex: 1; min-width: 140px; padding: 0.5rem 0.75rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.5rem; color: white; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            🎨 {{ __('Generate All Images') }}
                        </button>
                        <button type="button"
                                wire:click="generateAllShotVideos({{ $multiShotSceneIndex }})"
                                wire:loading.attr="disabled"
                                style="flex: 1; min-width: 140px; padding: 0.5rem 0.75rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.5rem; color: white; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            🎬 {{ __('Animate All Shots') }}
                        </button>
                    </div>

                    {{-- Shot Timeline with Frame Chain --}}
                    <div style="position: relative; display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.5rem 0; overflow-x: auto;">
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
                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $shotIndex }})">

                                    {{-- Shot Number Badge --}}
                                    <div style="position: absolute; top: 0.25rem; left: 0.25rem; background: rgba(0,0,0,0.7); color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600; z-index: 2;">
                                        {{ $shotIndex + 1 }}
                                    </div>

                                    {{-- Shot Type Badge --}}
                                    <div style="position: absolute; top: 0.25rem; right: 0.25rem; background: rgba(139, 92, 246, 0.8); color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.55rem; z-index: 2;">
                                        {{ ucfirst($shot['type'] ?? 'shot') }}
                                    </div>

                                    {{-- Transferred Badge --}}
                                    @if($wasTransferred)
                                        <div style="position: absolute; top: 1.5rem; right: 0.25rem; background: rgba(16, 185, 129, 0.9); color: white; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.5rem; z-index: 2;">
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
                                        <div style="font-size: 0.6rem; color: rgba(255,255,255,0.6); margin-bottom: 0.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $shot['cameraMovement'] ?? 'static' }} •
                                            <span style="color: {{ $durationColor }};">{{ $shotDuration }}s</span>
                                        </div>

                                        {{-- Duration Control --}}
                                        <div style="display: flex; gap: 0.2rem; margin-bottom: 0.3rem;">
                                            @foreach([5, 6, 10] as $dur)
                                                <button type="button"
                                                        wire:click.stop="setShotDuration({{ $multiShotSceneIndex }}, {{ $shotIndex }}, {{ $dur }})"
                                                        style="flex: 1; padding: 0.2rem; font-size: 0.55rem; background: {{ $shotDuration === $dur ? 'rgba(139,92,246,0.4)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $shotDuration === $dur ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.2rem; color: white; cursor: pointer;">
                                                    {{ $dur }}s
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div style="display: flex; gap: 0.2rem;">
                                            @if($hasImage && !$hasVideo)
                                                <button type="button"
                                                        wire:click.stop="generateShotVideo({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="flex: 1; padding: 0.25rem; font-size: 0.55rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.2rem; color: #67e8f9; cursor: pointer;">
                                                    🎬 Animate
                                                </button>
                                            @endif
                                            @if($hasVideo && !$isLastShot)
                                                <button type="button"
                                                        wire:click.stop="openFrameCaptureModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="flex: 1; padding: 0.25rem; font-size: 0.55rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.2rem; color: #10b981; cursor: pointer;">
                                                    🎯 Capture
                                                </button>
                                            @endif
                                            @if($hasImage)
                                                <button type="button"
                                                        wire:click.stop="openShotPreviewModal({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="flex: 1; padding: 0.25rem; font-size: 0.55rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                                                    👁️ View
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reset Button --}}
                    <div style="text-align: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 1rem;">
                        <button type="button"
                                wire:click="resetDecomposition({{ $multiShotSceneIndex }})"
                                style="padding: 0.5rem 1rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #ef4444; font-size: 0.8rem; cursor: pointer;">
                            🗑️ {{ __('Reset Decomposition') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end;">
            <button type="button"
                    wire:click="closeMultiShotModal"
                    style="padding: 0.6rem 1.25rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: white; cursor: pointer;">
                {{ __('Close') }}
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
.shot-hover-overlay:hover {
    opacity: 1 !important;
}
</style>
