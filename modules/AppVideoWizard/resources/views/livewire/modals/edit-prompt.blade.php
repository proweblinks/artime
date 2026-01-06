{{-- Edit Prompt Modal --}}
<div x-data="{ isOpen: false, sceneIndex: 0 }"
     @open-edit-prompt-modal.window="isOpen = true; sceneIndex = $event.detail.sceneIndex"
     @close-edit-prompt-modal.window="isOpen = false"
     x-show="isOpen"
     x-cloak
     class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="isOpen = false"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 700px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">✏️ {{ __('Edit Scene Prompt') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Modify the visual description for this scene') }}</p>
            </div>
            <button type="button" @click="isOpen = false" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;">
            {{-- Current Image Preview --}}
            @if($showEditPromptModal && isset($storyboard['scenes'][$editPromptSceneIndex]['imageUrl']))
                <div style="margin-bottom: 1.25rem; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; background: rgba(0,0,0,0.3);">
                    <img src="{{ $storyboard['scenes'][$editPromptSceneIndex]['imageUrl'] }}"
                         alt="Current scene image"
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @endif

            {{-- Scene Info --}}
            @if($showEditPromptModal && isset($script['scenes'][$editPromptSceneIndex]))
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.5rem;">
                    <div style="font-weight: 600; color: white; font-size: 0.9rem; margin-bottom: 0.25rem;">
                        {{ __('Scene') }} {{ $editPromptSceneIndex + 1 }}: {{ $script['scenes'][$editPromptSceneIndex]['title'] ?? '' }}
                    </div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">
                        {{ Str::limit($script['scenes'][$editPromptSceneIndex]['narration'] ?? '', 150) }}
                    </div>
                </div>
            @endif

            {{-- Edit Prompt Textarea --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Visual Description') }}</label>
                <textarea wire:model="editPromptText"
                          placeholder="{{ __('Describe what you want to see in this scene...') }}"
                          style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 120px; resize: vertical;"></textarea>
            </div>

            {{-- Suggested Edits --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Suggested Edits') }}</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                    <button type="button"
                            wire:click="appendToPrompt('cinematic lighting, dramatic shadows')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Cinematic lighting') }}
                    </button>
                    <button type="button"
                            wire:click="appendToPrompt('shallow depth of field, bokeh background')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Shallow DOF') }}
                    </button>
                    <button type="button"
                            wire:click="appendToPrompt('golden hour lighting, warm tones')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Golden hour') }}
                    </button>
                    <button type="button"
                            wire:click="appendToPrompt('wide angle shot, establishing view')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Wide shot') }}
                    </button>
                    <button type="button"
                            wire:click="appendToPrompt('close-up shot, detailed')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Close-up') }}
                    </button>
                    <button type="button"
                            wire:click="appendToPrompt('moody atmosphere, volumetric lighting')"
                            style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                        + {{ __('Moody') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button"
                    @click="isOpen = false"
                    style="padding: 0.6rem 1.25rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                {{ __('Cancel') }}
            </button>
            <button type="button"
                    wire:click="saveAndRegeneratePrompt"
                    wire:loading.attr="disabled"
                    style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                <span wire:loading.remove wire:target="saveAndRegeneratePrompt">🎨 {{ __('Save & Regenerate') }}</span>
                <span wire:loading wire:target="saveAndRegeneratePrompt">{{ __('Generating...') }}</span>
            </button>
        </div>
    </div>
</div>
