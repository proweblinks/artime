{{-- Character Bible Modal --}}
@if($showCharacterBibleModal ?? false)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 900px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">👤 {{ __('Character Bible') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Define consistent character appearances with reference images') }}</p>
            </div>
            <button type="button" wire:click="closeCharacterBibleModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; gap: 1.25rem;">
            {{-- Characters List (Left Panel) --}}
            <div style="width: 220px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 1.25rem;">
                <button type="button"
                        wire:click="addCharacter"
                        style="width: 100%; padding: 0.6rem; background: transparent; border: 2px dashed rgba(139,92,246,0.4); border-radius: 0.5rem; color: #c4b5fd; font-size: 0.8rem; cursor: pointer; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                    <span>+</span> {{ __('Add Character') }}
                </button>
                @if(count($script['scenes'] ?? []) > 0)
                    <button type="button"
                            wire:click="autoDetectCharacters"
                            wire:loading.attr="disabled"
                            wire:target="autoDetectCharacters"
                            style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer; margin-bottom: 1rem;">
                        <span wire:loading.remove wire:target="autoDetectCharacters">🔍 {{ __('Auto-detect from Script') }}</span>
                        <span wire:loading wire:target="autoDetectCharacters">{{ __('Detecting...') }}</span>
                    </button>
                @endif

                {{-- Character Items --}}
                <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 350px; overflow-y: auto;">
                    @forelse($sceneMemory['characterBible']['characters'] ?? [] as $index => $character)
                        <div wire:click="editCharacter({{ $index }})"
                             style="padding: 0.6rem; background: {{ ($editingCharacterIndex ?? 0) === $index ? 'rgba(139,92,246,0.15)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ ($editingCharacterIndex ?? 0) === $index ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; cursor: pointer; display: flex; gap: 0.6rem; align-items: center;">
                            {{-- Portrait Thumbnail --}}
                            <div style="width: 40px; height: 50px; border-radius: 0.35rem; overflow: hidden; background: rgba(0,0,0,0.3); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                @if(!empty($character['referenceImage']))
                                    <img src="{{ $character['referenceImage'] }}" alt="{{ $character['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <span style="color: rgba(255,255,255,0.3); font-size: 1rem;">👤</span>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; color: white; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $character['name'] ?: __('Unnamed') }}</div>
                                <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-top: 0.15rem;">
                                    {{ count($character['appliedScenes'] ?? []) }} {{ __('scenes') }}
                                    @if(!empty($character['referenceImage']))
                                        <span style="color: #10b981;">• ✓</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 1.5rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; text-align: center;">
                            {{ __('No characters defined yet') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Character Editor (Right Panel) --}}
            <div style="flex: 1; display: flex; flex-direction: column;">
                @php
                    $characters = $sceneMemory['characterBible']['characters'] ?? [];
                    $editIndex = $editingCharacterIndex ?? 0;
                    $currentChar = $characters[$editIndex] ?? null;
                @endphp

                @if($currentChar)
                    <div style="display: flex; gap: 1.25rem;">
                        {{-- Portrait Preview --}}
                        <div style="width: 150px; flex-shrink: 0;">
                            <div style="width: 150px; height: 180px; border-radius: 0.75rem; overflow: hidden; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; position: relative;">
                                @if(!empty($currentChar['referenceImage']))
                                    <img src="{{ $currentChar['referenceImage'] }}" alt="{{ $currentChar['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button type="button"
                                            wire:click="removeCharacterPortrait({{ $editIndex }})"
                                            style="position: absolute; top: 0.35rem; right: 0.35rem; width: 24px; height: 24px; border-radius: 50%; background: rgba(239,68,68,0.9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;"
                                            title="{{ __('Remove portrait') }}">
                                        ×
                                    </button>
                                @elseif($isGeneratingPortrait ?? false)
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                        <div style="width: 24px; height: 24px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                                        <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('Generating...') }}</span>
                                    </div>
                                @else
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.4);">
                                        <span style="font-size: 2.5rem;">👤</span>
                                        <span style="font-size: 0.65rem;">{{ __('No portrait yet') }}</span>
                                    </div>
                                @endif
                            </div>
                            {{-- Portrait Actions --}}
                            <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">
                                {{-- Generate Button --}}
                                <button type="button"
                                        wire:click="generateCharacterPortrait({{ $editIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateCharacterPortrait"
                                        style="width: 100%; padding: 0.5rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.4rem; color: white; font-size: 0.7rem; cursor: pointer; font-weight: 600;">
                                    <span wire:loading.remove wire:target="generateCharacterPortrait">🎨 {{ empty($currentChar['referenceImage']) ? __('Generate') : __('Regenerate') }}</span>
                                    <span wire:loading wire:target="generateCharacterPortrait">...</span>
                                </button>

                                {{-- Upload Button & Input --}}
                                <div x-data="{ uploading: false }" style="position: relative;">
                                    <input type="file"
                                           wire:model="characterImageUpload"
                                           accept="image/*"
                                           x-on:livewire-upload-start="uploading = true"
                                           x-on:livewire-upload-finish="uploading = false; $wire.uploadCharacterPortrait({{ $editIndex }})"
                                           x-on:livewire-upload-error="uploading = false"
                                           style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 1;">
                                    <button type="button"
                                            style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.4rem; color: rgba(255,255,255,0.8); font-size: 0.7rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                        <template x-if="!uploading">
                                            <span>📤 {{ __('Upload Image') }}</span>
                                        </template>
                                        <template x-if="uploading">
                                            <span>{{ __('Uploading...') }}</span>
                                        </template>
                                    </button>
                                </div>

                                {{-- Source indicator --}}
                                @if(!empty($currentChar['referenceImage']) && !empty($currentChar['referenceImageSource']))
                                    <div style="text-align: center; font-size: 0.6rem; color: rgba(255,255,255,0.4);">
                                        {{ $currentChar['referenceImageSource'] === 'upload' ? __('Uploaded') : __('AI Generated') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Character Fields --}}
                        <div style="flex: 1;">
                            {{-- Character Name --}}
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.7rem; margin-bottom: 0.25rem;">{{ __('Character Name') }}</label>
                                <input type="text"
                                       wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.name"
                                       placeholder="{{ __('e.g., Sarah, The Detective...') }}"
                                       style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem;">
                            </div>

                            {{-- Quick Templates --}}
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.35rem;">{{ __('Quick Templates') }}</label>
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'action-hero')" style="padding: 0.3rem 0.6rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.3rem; color: #fca5a5; font-size: 0.65rem; cursor: pointer;">🦸 {{ __('Action Hero') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'tech-pro')" style="padding: 0.3rem 0.6rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.3rem; color: #67e8f9; font-size: 0.65rem; cursor: pointer;">💻 {{ __('Tech Pro') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'mysterious')" style="padding: 0.3rem 0.6rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.3rem; color: #c4b5fd; font-size: 0.65rem; cursor: pointer;">🕵️ {{ __('Mysterious') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'narrator')" style="padding: 0.3rem 0.6rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.3rem; color: #fcd34d; font-size: 0.65rem; cursor: pointer;">🎙️ {{ __('Narrator') }}</button>
                                </div>
                            </div>

                            {{-- Visual Description --}}
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.7rem; margin-bottom: 0.25rem;">{{ __('Visual Description') }}</label>
                                <textarea wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.description"
                                          placeholder="{{ __('e.g., Mid-30s woman with short dark hair, sharp features, wears a leather jacket...') }}"
                                          style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.8rem; min-height: 80px; resize: vertical;"></textarea>
                            </div>

                            {{-- Character Traits (Expandable) --}}
                            <div x-data="{ traitsOpen: true, newTrait: '' }" style="margin-bottom: 0.75rem;">
                                <button type="button"
                                        @click="traitsOpen = !traitsOpen"
                                        style="display: flex; align-items: center; gap: 0.35rem; width: 100%; background: none; border: none; padding: 0; cursor: pointer; margin-bottom: 0.35rem;">
                                    <span style="color: rgba(255,255,255,0.6); font-size: 0.7rem; transition: transform 0.2s;" :style="traitsOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                    <span style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Character Traits') }}</span>
                                    <span style="color: rgba(255,255,255,0.4); font-size: 0.6rem; margin-left: 0.25rem;">({{ count($currentChar['traits'] ?? []) }})</span>
                                </button>

                                <div x-show="traitsOpen" x-collapse>
                                    {{-- Current Traits --}}
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.5rem;">
                                        @forelse($currentChar['traits'] ?? [] as $traitIdx => $trait)
                                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 1rem; color: #c4b5fd; font-size: 0.65rem;">
                                                {{ $trait }}
                                                <button type="button"
                                                        wire:click="removeCharacterTrait({{ $editIndex }}, {{ $traitIdx }})"
                                                        style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; padding: 0; line-height: 1; font-size: 0.7rem;"
                                                        title="{{ __('Remove') }}">&times;</button>
                                            </span>
                                        @empty
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.65rem; font-style: italic;">{{ __('No traits added yet') }}</span>
                                        @endforelse
                                    </div>

                                    {{-- Add New Trait --}}
                                    <div style="display: flex; gap: 0.35rem; margin-bottom: 0.5rem;">
                                        <input type="text"
                                               x-model="newTrait"
                                               @keydown.enter.prevent="if(newTrait.trim()) { $wire.addCharacterTrait({{ $editIndex }}, newTrait.trim()); newTrait = ''; }"
                                               placeholder="{{ __('Add trait (e.g., confident, mysterious)') }}"
                                               style="flex: 1; padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.7rem;">
                                        <button type="button"
                                                @click="if(newTrait.trim()) { $wire.addCharacterTrait({{ $editIndex }}, newTrait.trim()); newTrait = ''; }"
                                                style="padding: 0.4rem 0.6rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.35rem; color: #c4b5fd; font-size: 0.65rem; cursor: pointer;">
                                            + {{ __('Add') }}
                                        </button>
                                    </div>

                                    {{-- Trait Presets --}}
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <span style="color: rgba(255,255,255,0.4); font-size: 0.6rem; margin-right: 0.25rem;">{{ __('Presets:') }}</span>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'hero')" style="padding: 0.2rem 0.4rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.25rem; color: #fca5a5; font-size: 0.55rem; cursor: pointer;">🦸 {{ __('Hero') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'villain')" style="padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.55rem; cursor: pointer;">🦹 {{ __('Villain') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'mentor')" style="padding: 0.2rem 0.4rem; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); border-radius: 0.25rem; color: #fcd34d; font-size: 0.55rem; cursor: pointer;">🧙 {{ __('Mentor') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'professional')" style="padding: 0.2rem 0.4rem; background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.2); border-radius: 0.25rem; color: #67e8f9; font-size: 0.55rem; cursor: pointer;">💼 {{ __('Pro') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'mysterious')" style="padding: 0.2rem 0.4rem; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 0.25rem; color: #a5b4fc; font-size: 0.55rem; cursor: pointer;">🔮 {{ __('Mysterious') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'comic')" style="padding: 0.2rem 0.4rem; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 0.25rem; color: #6ee7b7; font-size: 0.55rem; cursor: pointer;">🎭 {{ __('Comic') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'leader')" style="padding: 0.2rem 0.4rem; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 0.25rem; color: #fcd34d; font-size: 0.55rem; cursor: pointer;">👑 {{ __('Leader') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Appears in Scenes --}}
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.7rem; margin-bottom: 0.5rem;">{{ __('Appears in Scenes') }}</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                            @foreach($script['scenes'] ?? [] as $sceneIndex => $scene)
                                @php
                                    $isApplied = in_array($sceneIndex, $currentChar['appliedScenes'] ?? []);
                                @endphp
                                <button type="button"
                                        wire:click="toggleCharacterScene({{ $editIndex }}, {{ $sceneIndex }})"
                                        style="width: 32px; height: 32px; border-radius: 0.35rem; border: 1px solid {{ $isApplied ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $isApplied ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)' }}; color: {{ $isApplied ? '#c4b5fd' : 'rgba(255,255,255,0.5)' }}; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                    {{ $sceneIndex + 1 }}
                                </button>
                            @endforeach
                            @if(count($script['scenes'] ?? []) > 0)
                                <button type="button"
                                        wire:click="applyCharacterToAllScenes({{ $editIndex }})"
                                        style="padding: 0.35rem 0.6rem; border-radius: 0.35rem; border: 1px solid rgba(16,185,129,0.4); background: rgba(16,185,129,0.15); color: #6ee7b7; cursor: pointer; font-size: 0.65rem; margin-left: 0.5rem;">
                                    {{ __('All') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Delete Character --}}
                    <div style="margin-top: auto; padding-top: 1rem;">
                        <button type="button"
                                wire:click="removeCharacter({{ $editIndex }})"
                                wire:confirm="{{ __('Are you sure you want to delete this character?') }}"
                                style="padding: 0.5rem 1rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.5rem; color: #f87171; font-size: 0.75rem; cursor: pointer;">
                            🗑️ {{ __('Delete Character') }}
                        </button>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 3rem; margin-bottom: 1rem;">👤</span>
                        <p style="margin: 0;">{{ __('Add a character to get started') }}</p>
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
                    wire:click="closeCharacterBibleModal"
                    style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>
@endif
