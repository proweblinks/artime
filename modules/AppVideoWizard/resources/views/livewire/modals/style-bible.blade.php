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
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
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
            {{-- Reference Image Section --}}
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Reference Image') }}</label>
                <div style="display: flex; gap: 1rem;">
                    {{-- Image Preview --}}
                    <div style="width: 180px; flex-shrink: 0;">
                        @if(!empty($sceneMemory['styleBible']['referenceImage']))
                            <div style="position: relative;">
                                <img src="{{ $sceneMemory['styleBible']['referenceImage'] }}"
                                     style="width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2);">
                                {{-- Source indicator --}}
                                <div style="position: absolute; top: 0.25rem; left: 0.25rem; background: rgba(0,0,0,0.7); padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.55rem; color: rgba(255,255,255,0.8);">
                                    {{ ($sceneMemory['styleBible']['referenceImageSource'] ?? '') === 'upload' ? '📤 ' . __('Uploaded') : '🎨 ' . __('AI Generated') }}
                                </div>
                                {{-- Remove button --}}
                                <button type="button"
                                        wire:click="removeStyleReference"
                                        style="position: absolute; top: 0.25rem; right: 0.25rem; background: rgba(239,68,68,0.9); border: none; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white; font-size: 0.8rem;"
                                        title="{{ __('Remove') }}">&times;</button>
                            </div>
                        @else
                            <div style="width: 100%; aspect-ratio: 16/9; background: rgba(255,255,255,0.05); border: 2px dashed rgba(255,255,255,0.2); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <span style="font-size: 1.5rem; color: rgba(255,255,255,0.3);">🖼️</span>
                                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 0.25rem;">{{ __('No reference yet') }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Upload/Generate Buttons --}}
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem;">
                        {{-- Generate Button --}}
                        <button type="button"
                                wire:click="generateStyleReference"
                                wire:loading.attr="disabled"
                                wire:target="generateStyleReference"
                                {{ $isGeneratingStyleRef ? 'disabled' : '' }}
                                style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.5rem; color: white; font-size: 0.8rem; cursor: pointer; font-weight: 500; {{ $isGeneratingStyleRef ? 'opacity: 0.5;' : '' }}">
                            <span wire:loading.remove wire:target="generateStyleReference">🎨 {{ __('Generate Reference from Settings') }}</span>
                            <span wire:loading wire:target="generateStyleReference">{{ __('Generating...') }}</span>
                        </button>

                        {{-- Upload Button --}}
                        <div x-data="{ uploading: false }" style="position: relative;">
                            <input type="file"
                                   wire:model="styleImageUpload"
                                   accept="image/*"
                                   x-on:livewire-upload-start="uploading = true"
                                   x-on:livewire-upload-finish="uploading = false; $wire.uploadStyleReference()"
                                   x-on:livewire-upload-error="uploading = false"
                                   style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 1;">
                            <button type="button"
                                    style="width: 100%; padding: 0.6rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.8); font-size: 0.8rem; cursor: pointer; font-weight: 500;">
                                <template x-if="!uploading">
                                    <span>📤 {{ __('Upload Reference Image') }}</span>
                                </template>
                                <template x-if="uploading">
                                    <span>{{ __('Uploading...') }}</span>
                                </template>
                            </button>
                        </div>

                        <p style="color: rgba(255,255,255,0.4); font-size: 0.65rem; margin: 0;">
                            💡 {{ __('The reference image helps maintain visual consistency across all scenes.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick Templates --}}
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Quick Templates') }}</label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" wire:click="applyStyleTemplate('cinematic')" style="padding: 0.4rem 0.75rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.35rem; color: #c4b5fd; font-size: 0.75rem; cursor: pointer;">🎬 {{ __('Cinematic') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('documentary')" style="padding: 0.4rem 0.75rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.35rem; color: #6ee7b7; font-size: 0.75rem; cursor: pointer;">🎥 {{ __('Documentary') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('anime')" style="padding: 0.4rem 0.75rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.35rem; color: #f9a8d4; font-size: 0.75rem; cursor: pointer;">🎌 {{ __('Anime') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('noir')" style="padding: 0.4rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.75rem; cursor: pointer;">🖤 {{ __('Film Noir') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('3d')" style="padding: 0.4rem 0.75rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.35rem; color: #fcd34d; font-size: 0.75rem; cursor: pointer;">🎮 {{ __('3D Stylized') }}</button>
                    <button type="button" wire:click="applyStyleTemplate('photorealistic')" style="padding: 0.4rem 0.75rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.35rem; color: #67e8f9; font-size: 0.75rem; cursor: pointer;">📷 {{ __('Photorealistic') }}</button>
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
