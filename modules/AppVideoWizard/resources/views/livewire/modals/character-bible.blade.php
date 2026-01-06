{{-- Character Bible Modal --}}
<div x-data="{
    isOpen: false,
    editingIndex: null,
    newCharacter: { name: '', description: '', appliedScenes: [] }
}"
     @open-character-bible-modal.window="isOpen = true"
     @close-character-bible-modal.window="isOpen = false"
     x-show="isOpen"
     x-cloak
     class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="isOpen = false"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 900px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">👤 {{ __('Character Bible') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Define characters for visual consistency') }}</p>
            </div>
            <button type="button" @click="isOpen = false" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; gap: 1.25rem;">
            {{-- Characters List --}}
            <div style="width: 220px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 1.25rem;">
                <button type="button"
                        wire:click="addCharacter"
                        style="width: 100%; padding: 0.6rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; color: #c4b5fd; font-size: 0.8rem; cursor: pointer; margin-bottom: 0.75rem;">
                    + {{ __('Add Character') }}
                </button>
                <button type="button"
                        wire:click="autoDetectCharacters"
                        style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                    🔍 {{ __('Auto-detect from Script') }}
                </button>

                {{-- Character Items --}}
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse($sceneMemory['characterBible']['characters'] ?? [] as $index => $character)
                        <div wire:click="editCharacter({{ $index }})"
                             style="padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
                             :style="editingIndex === {{ $index }} ? 'border-color: #8b5cf6; background: rgba(139,92,246,0.1)' : ''">
                            <div style="font-weight: 600; color: white; font-size: 0.85rem;">{{ $character['name'] ?? __('Unnamed') }}</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 0.25rem;">
                                {{ count($character['appliedScenes'] ?? []) }} {{ __('scenes') }}
                            </div>
                        </div>
                    @empty
                        <div style="padding: 1rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; text-align: center;">
                            {{ __('No characters defined yet') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Character Editor --}}
            <div style="flex: 1;">
                @if(count($sceneMemory['characterBible']['characters'] ?? []) > 0)
                    @php $editIndex = 0; @endphp
                    {{-- Character Name --}}
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Character Name') }}</label>
                        <input type="text"
                               wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.name"
                               placeholder="{{ __('e.g., Sarah, The Detective...') }}"
                               style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem;">
                    </div>

                    {{-- Character Description --}}
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Physical Description') }}</label>
                        <textarea wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.description"
                                  placeholder="{{ __('e.g., Mid-30s woman with short dark hair, sharp features, wears a leather jacket...') }}"
                                  style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 100px; resize: vertical;"></textarea>
                    </div>

                    {{-- Applied Scenes --}}
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Appears in Scenes') }}</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                            @foreach($script['scenes'] ?? [] as $sceneIndex => $scene)
                                <label style="display: flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.35rem; cursor: pointer; font-size: 0.75rem; color: rgba(255,255,255,0.7);">
                                    <input type="checkbox"
                                           value="{{ $sceneIndex }}"
                                           wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.appliedScenes"
                                           style="accent-color: #8b5cf6;">
                                    {{ $sceneIndex + 1 }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Generate Portrait Button --}}
                    <button type="button"
                            wire:click="generateCharacterPortrait({{ $editIndex }})"
                            wire:loading.attr="disabled"
                            style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                        <span wire:loading.remove wire:target="generateCharacterPortrait">🎨 {{ __('Generate Reference Portrait') }}</span>
                        <span wire:loading wire:target="generateCharacterPortrait">{{ __('Generating...') }}</span>
                    </button>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 3rem; margin-bottom: 1rem;">👤</span>
                        <p>{{ __('Add a character to get started') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.characterBible.enabled" style="accent-color: #8b5cf6;">
                {{ __('Enable Character Bible') }}
            </label>
            <button type="button"
                    @click="isOpen = false"
                    style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>
