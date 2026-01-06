{{-- Style Bible Modal --}}
<div x-data="{ isOpen: false }"
     @open-style-bible-modal.window="isOpen = true"
     @close-style-bible-modal.window="isOpen = false"
     x-show="isOpen"
     x-cloak
     class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="isOpen = false"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 700px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">🎨 {{ __('Style Bible') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Define your visual DNA for consistent imagery') }}</p>
            </div>
            <button type="button" @click="isOpen = false" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;">
            {{-- Quick Templates --}}
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Quick Templates') }}</label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" wire:click="applyStyleTemplate('cinematic')" style="padding: 0.4rem 0.75rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.35rem; color: #c4b5fd; font-size: 0.75rem; cursor: pointer;">🎬 {{ __('Cinematic') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('documentary')" style="padding: 0.4rem 0.75rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.35rem; color: #6ee7b7; font-size: 0.75rem; cursor: pointer;">🎥 {{ __('Documentary') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('anime')" style="padding: 0.4rem 0.75rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.35rem; color: #f9a8d4; font-size: 0.75rem; cursor: pointer;">🎌 {{ __('Anime') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('noir')" style="padding: 0.4rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.75rem; cursor: pointer;">🖤 {{ __('Film Noir') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('3d')" style="padding: 0.4rem 0.75rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.35rem; color: #fcd34d; font-size: 0.75rem; cursor: pointer;">🎮 {{ __('3D Stylized') }}</button>
                </div>
            </div>

            {{-- Visual Style --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Visual Style') }}</label>
                <textarea wire:model.live="sceneMemory.styleBible.style"
                          placeholder="{{ __('e.g., Photorealistic with cinematic framing, shallow depth of field...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 80px; resize: vertical;"></textarea>
            </div>

            {{-- Color Grade --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Color Grade') }}</label>
                <textarea wire:model.live="sceneMemory.styleBible.colorGrade"
                          placeholder="{{ __('e.g., Teal and orange color grading, lifted blacks, desaturated skin tones...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 60px; resize: vertical;"></textarea>
            </div>

            {{-- Atmosphere --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Atmosphere & Mood') }}</label>
                <textarea wire:model.live="sceneMemory.styleBible.atmosphere"
                          placeholder="{{ __('e.g., Moody, mysterious, with volumetric lighting and subtle haze...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 60px; resize: vertical;"></textarea>
            </div>

            {{-- Camera Language --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Camera Language') }}</label>
                <textarea wire:model.live="sceneMemory.styleBible.camera"
                          placeholder="{{ __('e.g., Shot on ARRI Alexa, anamorphic lenses, wide establishing shots...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 60px; resize: vertical;"></textarea>
            </div>

            {{-- Visual DNA --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Visual DNA (Additional Keywords)') }}</label>
                <textarea wire:model.live="sceneMemory.styleBible.visualDNA"
                          placeholder="{{ __('e.g., high quality, detailed, professional, 8K resolution, sharp focus...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 60px; resize: vertical;"></textarea>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.styleBible.enabled" style="accent-color: #8b5cf6;">
                {{ __('Enable Style Bible') }}
            </label>
            <button type="button"
                    @click="isOpen = false"
                    style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>
