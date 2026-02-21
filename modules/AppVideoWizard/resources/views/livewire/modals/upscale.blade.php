{{-- Upscale Quality Modal --}}
@if($showUpscaleModal)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000100; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(251,191,36,0.3); border-radius: 1rem; width: 100%; max-width: 500px; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">⬆️ {{ __('Upscale Image') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Enhance image resolution and quality') }}</p>
            </div>
            <button type="button" wire:click="closeUpscaleModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="padding: 1.25rem;">
            @php
                $storyboardScene = $storyboard['scenes'][$upscaleSceneIndex] ?? null;
            @endphp

            {{-- Current Image Preview --}}
            @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                <div style="margin-bottom: 1.25rem; text-align: center;">
                    <img src="{{ $storyboardScene['imageUrl'] }}"
                         alt="Scene {{ $upscaleSceneIndex + 1 }}"
                         style="max-width: 100%; max-height: 200px; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                    @if(!empty($storyboardScene['upscaled']))
                        <div style="margin-top: 0.5rem; padding: 0.35rem 0.75rem; background: rgba(16,185,129,0.15); border-radius: 0.35rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <span style="color: #10b981; font-size: 0.75rem;">✓ {{ __('Already upscaled to') }} {{ strtoupper($storyboardScene['upscaleQuality'] ?? 'HD') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quality Selection --}}
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.75rem;">{{ __('Select Output Quality') }}</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    {{-- HD Option --}}
                    <button type="button"
                            wire:click="$set('upscaleQuality', 'hd')"
                            style="padding: 1rem; border-radius: 0.75rem; border: 2px solid {{ $upscaleQuality === 'hd' ? 'rgba(6,182,212,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $upscaleQuality === 'hd' ? 'rgba(6,182,212,0.1)' : 'rgba(255,255,255,0.03)' }}; cursor: pointer; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.35rem;">🖼️</div>
                        <div style="color: white; font-weight: 600; font-size: 1rem;">HD</div>
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 0.25rem;">1920 × 1080</div>
                        <div style="color: #06b6d4; font-size: 0.7rem; margin-top: 0.35rem;">2 {{ __('tokens') }}</div>
                    </button>

                    {{-- 4K Option --}}
                    <button type="button"
                            wire:click="$set('upscaleQuality', '4k')"
                            style="padding: 1rem; border-radius: 0.75rem; border: 2px solid {{ $upscaleQuality === '4k' ? 'rgba(251,191,36,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $upscaleQuality === '4k' ? 'rgba(251,191,36,0.1)' : 'rgba(255,255,255,0.03)' }}; cursor: pointer; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.35rem;">✨</div>
                        <div style="color: white; font-weight: 600; font-size: 1rem;">4K</div>
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 0.25rem;">3840 × 2160</div>
                        <div style="color: #fbbf24; font-size: 0.7rem; margin-top: 0.35rem;">4 {{ __('tokens') }}</div>
                    </button>
                </div>
            </div>

            {{-- Info --}}
            <div style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.2); border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: start; gap: 0.5rem;">
                    <span style="font-size: 1rem;">💡</span>
                    <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; line-height: 1.4;">
                        {{ __('Upscaling uses AI to enhance image resolution while maintaining quality and adding fine details. Original image composition will be preserved.') }}
                    </div>
                </div>
            </div>

            {{-- Error --}}
            @if($error)
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #ef4444; font-size: 0.85rem;">
                        <span>❌</span>
                        <span>{{ $error }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <button type="button"
                    wire:click="closeUpscaleModal"
                    style="padding: 0.6rem 1rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                {{ __('Cancel') }}
            </button>
            <button type="button"
                    wire:click="upscaleImage"
                    wire:loading.attr="disabled"
                    wire:target="upscaleImage"
                    {{ $isUpscaling ? 'disabled' : '' }}
                    style="padding: 0.6rem 1.5rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; {{ $isUpscaling ? 'opacity: 0.5;' : '' }}">
                <span wire:loading.remove wire:target="upscaleImage">
                    ⬆️ {{ __('Upscale to') }} {{ strtoupper($upscaleQuality) }}
                </span>
                <span wire:loading wire:target="upscaleImage">
                    <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Upscaling...') }}
                </span>
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
