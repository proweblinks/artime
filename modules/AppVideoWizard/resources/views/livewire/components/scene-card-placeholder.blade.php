{{--
    Scene Card Placeholder - Loading skeleton for lazy-loaded scenes

    This placeholder is displayed while the SceneCard component is loading
    (before it enters the viewport). Uses CSS animations for visual feedback.
--}}

<div class="vw-scene-card animate-pulse" wire:key="scene-card-placeholder-{{ $sceneIndex }}">
    {{-- Skeleton image container --}}
    <div style="position: relative;">
        {{-- Scene Number Badge Skeleton --}}
        <div style="position: absolute; top: 0.75rem; left: 0.75rem; z-index: 10;">
            <div style="background: rgba(255,255,255,0.1); width: 70px; height: 24px; border-radius: 0.35rem;"></div>
        </div>

        {{-- Image Skeleton --}}
        <div class="vw-scene-image-container">
            <div style="height: 220px; background: linear-gradient(135deg, rgba(3,252,244,0.05), rgba(6,182,212,0.05)); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                {{-- Animated shimmer effect --}}
                <div style="position: absolute; inset: 0; background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.05) 50%, transparent 100%); animation: shimmer 1.5s infinite; background-size: 200% 100%;"></div>

                {{-- Loading icon --}}
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; z-index: 2;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(3,252,244,0.15); display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 20px; height: 20px; animation: spin 1s linear infinite; color: rgba(3,252,244,0.5);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                    </div>
                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.4);">{{ __('Loading scene') }} {{ $sceneIndex + 1 }}...</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Skeleton --}}
    <div style="padding: 0.5rem 0.75rem;">
        {{-- Narration skeleton --}}
        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
            <div style="height: 12px; background: rgba(255,255,255,0.08); border-radius: 0.25rem; width: 100%;"></div>
            <div style="height: 12px; background: rgba(255,255,255,0.06); border-radius: 0.25rem; width: 75%;"></div>
        </div>

        {{-- Button skeleton --}}
        <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="height: 24px; width: 80px; background: rgba(3,252,244,0.1); border-radius: 0.3rem;"></div>
            <div style="height: 20px; width: 50px; background: rgba(3,252,244,0.08); border-radius: 0.2rem;"></div>
        </div>
    </div>
</div>

<style>
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
