{{-- Character Bible Modal --}}
@if($showCharacterBibleModal ?? false)
<div class="vw-modal-overlay"
     wire:key="character-bible-modal-{{ $editCharacterIndex ?? 'main' }}"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 880px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">👤 {{ __('Character Bible') }}</h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Define consistent character appearances with reference images') }}</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                @if(!empty($storyBible['characters']) && $storyBible['status'] === 'ready')
                    <button type="button"
                            wire:click="syncStoryBibleToCharacterBible"
                            style="padding: 0.3rem 0.6rem; background: linear-gradient(135deg, #f59e0b, #ec4899); border: none; border-radius: 0.35rem; color: white; font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        🔄 {{ __('Sync from Story Bible') }}
                        <span style="background: rgba(255,255,255,0.2); padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.55rem;">{{ count($storyBible['characters']) }}</span>
                    </button>
                @endif
                <button type="button" wire:click="closeCharacterBibleModal" style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
            </div>
        </div>

        {{-- Story Bible sync indicator with loading state --}}
        @if($isSyncingCharacterBible ?? false)
            <div style="padding: 0.5rem 1rem; background: rgba(139,92,246,0.15); border-bottom: 1px solid rgba(139,92,246,0.3); color: #c4b5fd; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem;">
                <div style="width: 14px; height: 14px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                <span>{{ __('Syncing characters from Story Bible...') }}</span>
            </div>
        @elseif(!empty($storyBible['characters']) && $storyBible['status'] === 'ready')
            <div style="padding: 0.35rem 1rem; background: rgba(16,185,129,0.1); border-bottom: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; font-size: 0.65rem; display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #10b981;">✓</span>
                📖 {{ __('Synced') }} {{ count($storyBible['characters']) }} {{ __('characters from Story Bible') }}
            </div>
        @endif

        {{-- Error Display --}}
        @if($error)
            <div style="padding: 0.5rem 1rem; background: rgba(239,68,68,0.15); border-bottom: 1px solid rgba(239,68,68,0.3); color: #fca5a5; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <span>⚠️</span>
                <span>{{ $error }}</span>
                <button type="button" wire:click="$set('error', null)" style="margin-left: auto; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 0.9rem;">&times;</button>
            </div>
        @endif

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 0.75rem; display: flex; gap: 0.75rem;">
            {{-- Characters List (Left Panel) --}}
            <div style="width: 190px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.75rem;">
                <button type="button"
                        wire:click="addCharacter"
                        style="width: 100%; padding: 0.4rem; background: transparent; border: 2px dashed rgba(139,92,246,0.4); border-radius: 0.375rem; color: #c4b5fd; font-size: 0.7rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <span>+</span> {{ __('Add Character') }}
                </button>
                @if(count($script['scenes'] ?? []) > 0)
                    <button type="button"
                            wire:click="autoDetectCharacters"
                            wire:loading.attr="disabled"
                            wire:target="autoDetectCharacters"
                            style="width: 100%; padding: 0.35rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: rgba(255,255,255,0.7); font-size: 0.6rem; cursor: pointer; margin-bottom: 0.5rem;">
                        <span wire:loading.remove wire:target="autoDetectCharacters">🔍 {{ __('Auto-detect from Script') }}</span>
                        <span wire:loading wire:target="autoDetectCharacters">{{ __('Detecting...') }}</span>
                    </button>
                @endif

                {{-- Generate All Missing Portraits Button --}}
                @php
                    $charactersNeedingPortraits = collect($sceneMemory['characterBible']['characters'] ?? [])->filter(function($char) {
                        return empty($char['referenceImageBase64']) || ($char['referenceImageStatus'] ?? '') !== 'ready';
                    })->count();
                @endphp
                @if($charactersNeedingPortraits > 0)
                <button type="button"
                        wire:click="generateAllMissingCharacterReferences"
                        wire:loading.attr="disabled"
                        wire:target="generateAllMissingCharacterReferences"
                        style="width: 100%; padding: 0.35rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.375rem; color: #10b981; font-size: 0.6rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <span wire:loading.remove wire:target="generateAllMissingCharacterReferences">✨ {{ __('Generate All Portraits') }} ({{ $charactersNeedingPortraits }})</span>
                    <span wire:loading wire:target="generateAllMissingCharacterReferences">{{ __('Generating...') }}</span>
                </button>
                @endif

                {{-- Character Items --}}
                <div style="display: flex; flex-direction: column; gap: 0.35rem; max-height: 380px; overflow-y: auto;">
                    @forelse($sceneMemory['characterBible']['characters'] ?? [] as $index => $character)
                        <div wire:click="editCharacter({{ $index }})"
                             style="padding: 0.4rem; background: {{ ($editingCharacterIndex ?? 0) === $index ? 'rgba(139,92,246,0.15)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ ($editingCharacterIndex ?? 0) === $index ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.375rem; cursor: pointer; display: flex; gap: 0.4rem; align-items: center;">
                            {{-- Portrait Thumbnail --}}
                            <div style="width: 35px; height: 45px; border-radius: 0.25rem; overflow: hidden; background: rgba(0,0,0,0.3); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                @if(!empty($character['referenceImage']))
                                    <img src="{{ $character['referenceImage'] }}" alt="{{ $character['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <span style="color: rgba(255,255,255,0.3); font-size: 0.9rem;">👤</span>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; color: white; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $character['name'] ?: __('Unnamed') }}</div>
                                <div style="color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-top: 0.1rem;">
                                    {{ count($character['scenes'] ?? $character['appliedScenes'] ?? []) }} {{ __('scenes') }}
                                    @if(!empty($character['referenceImage']))
                                        <span style="color: #10b981;">• ✓</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 1rem; color: rgba(255,255,255,0.4); font-size: 0.65rem; text-align: center;">
                            {{ __('No characters defined yet') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Character Editor (Right Panel) --}}
            <div style="flex: 1; display: flex; flex-direction: column; overflow-y: auto;">
                @php
                    $characters = $sceneMemory['characterBible']['characters'] ?? [];
                    $editIndex = $editingCharacterIndex ?? 0;
                    $currentChar = $characters[$editIndex] ?? null;
                @endphp

                @if($currentChar)
                    <div style="display: flex; gap: 0.75rem;">
                        {{-- Portrait Preview --}}
                        <div style="width: 130px; flex-shrink: 0;">
                            <div style="width: 130px; height: 155px; border-radius: 0.5rem; overflow: hidden; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; position: relative;">
                                @if(!empty($currentChar['referenceImage']))
                                    <img src="{{ $currentChar['referenceImage'] }}" alt="{{ $currentChar['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button type="button"
                                            wire:click="removeCharacterPortrait({{ $editIndex }})"
                                            style="position: absolute; top: 0.25rem; right: 0.25rem; width: 20px; height: 20px; border-radius: 50%; background: rgba(239,68,68,0.9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;"
                                            title="{{ __('Remove portrait') }}">
                                        ×
                                    </button>
                                @elseif($isGeneratingPortrait ?? false)
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                                        <div style="width: 20px; height: 20px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                                        <span style="color: rgba(255,255,255,0.5); font-size: 0.55rem;">{{ __('Generating...') }}</span>
                                    </div>
                                @else
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem; color: rgba(255,255,255,0.4);">
                                        <span style="font-size: 2rem;">👤</span>
                                        <span style="font-size: 0.55rem;">{{ __('No portrait yet') }}</span>
                                    </div>
                                @endif
                            </div>
                            {{-- Portrait Actions --}}
                            <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.35rem;">
                                {{-- Generate Button --}}
                                <button type="button"
                                        wire:click="generateCharacterPortrait({{ $editIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateCharacterPortrait"
                                        style="width: 100%; padding: 0.35rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.3rem; color: white; font-size: 0.6rem; cursor: pointer; font-weight: 600;">
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
                                            style="width: 100%; padding: 0.35rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.3rem; color: rgba(255,255,255,0.8); font-size: 0.6rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
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
                                    <div style="text-align: center; font-size: 0.5rem; color: rgba(255,255,255,0.4);">
                                        {{ $currentChar['referenceImageSource'] === 'upload' ? __('Uploaded') : __('AI Generated') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Character Fields --}}
                        <div style="flex: 1;">
                            {{-- Character Name --}}
                            <div style="margin-bottom: 0.4rem;">
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Character Name') }}</label>
                                <input type="text"
                                       wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.name"
                                       placeholder="{{ __('e.g., Sarah, The Detective...') }}"
                                       style="width: 100%; padding: 0.4rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.75rem;">
                            </div>

                            {{-- Quick Templates --}}
                            <div style="margin-bottom: 0.4rem;">
                                <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.2rem;">{{ __('Quick Templates') }}</label>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'action-hero')" style="padding: 0.2rem 0.4rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.2rem; color: #fca5a5; font-size: 0.55rem; cursor: pointer;">🦸 {{ __('Action Hero') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'tech-pro')" style="padding: 0.2rem 0.4rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.2rem; color: #67e8f9; font-size: 0.55rem; cursor: pointer;">💻 {{ __('Tech Pro') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'mysterious')" style="padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.2rem; color: #c4b5fd; font-size: 0.55rem; cursor: pointer;">🕵️ {{ __('Mysterious') }}</button>
                                    <button type="button" wire:click="applyCharacterTemplate({{ $editIndex }}, 'narrator')" style="padding: 0.2rem 0.4rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.2rem; color: #fcd34d; font-size: 0.55rem; cursor: pointer;">🎙️ {{ __('Narrator') }}</button>
                                </div>
                            </div>

                            {{-- Visual Description --}}
                            <div style="margin-bottom: 0.4rem;">
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Visual Description') }}</label>
                                <textarea wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.description"
                                          placeholder="{{ __('e.g., Mid-30s woman with short dark hair, sharp features, wears a leather jacket...') }}"
                                          style="width: 100%; padding: 0.4rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.7rem; min-height: 55px; resize: vertical;"></textarea>
                            </div>

                            {{-- Character Traits (Expandable) --}}
                            <div x-data="{ traitsOpen: false, newTrait: '' }" style="margin-bottom: 0.4rem;">
                                <button type="button"
                                        @click="traitsOpen = !traitsOpen"
                                        style="display: flex; align-items: center; gap: 0.25rem; width: 100%; background: none; border: none; padding: 0; cursor: pointer; margin-bottom: 0.25rem;">
                                    <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem; transition: transform 0.2s;" :style="traitsOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                    <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ __('Character Traits') }}</span>
                                    <span style="color: rgba(255,255,255,0.4); font-size: 0.5rem; margin-left: 0.2rem;">({{ count($currentChar['traits'] ?? []) }})</span>
                                </button>

                                <div x-show="traitsOpen" x-collapse>
                                    {{-- Current Traits --}}
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.35rem;">
                                        @forelse($currentChar['traits'] ?? [] as $traitIdx => $trait)
                                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.75rem; color: #c4b5fd; font-size: 0.55rem;">
                                                {{ $trait }}
                                                <button type="button"
                                                        wire:click="removeCharacterTrait({{ $editIndex }}, {{ $traitIdx }})"
                                                        style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; padding: 0; line-height: 1; font-size: 0.6rem;"
                                                        title="{{ __('Remove') }}">&times;</button>
                                            </span>
                                        @empty
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.55rem; font-style: italic;">{{ __('No traits added yet') }}</span>
                                        @endforelse
                                    </div>

                                    {{-- Add New Trait --}}
                                    <div style="display: flex; gap: 0.25rem; margin-bottom: 0.35rem;">
                                        <input type="text"
                                               x-model="newTrait"
                                               @keydown.enter.prevent="if(newTrait.trim()) { $wire.addCharacterTrait({{ $editIndex }}, newTrait.trim()); newTrait = ''; }"
                                               placeholder="{{ __('Add trait (e.g., confident, mysterious)') }}"
                                               style="flex: 1; padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                        <button type="button"
                                                @click="if(newTrait.trim()) { $wire.addCharacterTrait({{ $editIndex }}, newTrait.trim()); newTrait = ''; }"
                                                style="padding: 0.3rem 0.5rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.55rem; cursor: pointer;">
                                            + {{ __('Add') }}
                                        </button>
                                    </div>

                                    {{-- Trait Presets --}}
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">
                                        <span style="color: rgba(255,255,255,0.4); font-size: 0.5rem; margin-right: 0.2rem;">{{ __('Presets:') }}</span>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'hero')" style="padding: 0.15rem 0.3rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.2rem; color: #fca5a5; font-size: 0.5rem; cursor: pointer;">🦸 {{ __('Hero') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'villain')" style="padding: 0.15rem 0.3rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.2rem; color: #c4b5fd; font-size: 0.5rem; cursor: pointer;">🦹 {{ __('Villain') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'mentor')" style="padding: 0.15rem 0.3rem; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); border-radius: 0.2rem; color: #fcd34d; font-size: 0.5rem; cursor: pointer;">🧙 {{ __('Mentor') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'professional')" style="padding: 0.15rem 0.3rem; background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.2); border-radius: 0.2rem; color: #67e8f9; font-size: 0.5rem; cursor: pointer;">💼 {{ __('Pro') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'mysterious')" style="padding: 0.15rem 0.3rem; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 0.2rem; color: #a5b4fc; font-size: 0.5rem; cursor: pointer;">🔮 {{ __('Mysterious') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'comic')" style="padding: 0.15rem 0.3rem; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 0.2rem; color: #6ee7b7; font-size: 0.5rem; cursor: pointer;">🎭 {{ __('Comic') }}</button>
                                        <button type="button" wire:click="applyTraitPreset({{ $editIndex }}, 'leader')" style="padding: 0.15rem 0.3rem; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 0.2rem; color: #fcd34d; font-size: 0.5rem; cursor: pointer;">👑 {{ __('Leader') }}</button>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════════════════════════════ --}}
                            {{-- CHARACTER DNA - Consolidated Section (All collapsed by default) --}}
                            {{-- ═══════════════════════════════════════════════════════════════════ --}}
                            @php
                                $hasDNA = !empty($currentChar['hair']['style'] ?? '') ||
                                          !empty($currentChar['hair']['color'] ?? '') ||
                                          !empty($currentChar['wardrobe']['outfit'] ?? '') ||
                                          !empty($currentChar['makeup']['style'] ?? '') ||
                                          !empty($currentChar['accessories'] ?? []);
                            @endphp

                            <div x-data="{ dnaOpen: false, presetsOpen: false }" style="margin-bottom: 0.4rem; padding: 0.35rem; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.35rem;">
                                {{-- DNA Section Header --}}
                                <button type="button"
                                        @click="dnaOpen = !dnaOpen"
                                        style="display: flex; align-items: center; gap: 0.25rem; width: 100%; background: none; border: none; padding: 0; cursor: pointer;">
                                    <span style="color: #c4b5fd; font-size: 0.6rem; transition: transform 0.2s;" :style="dnaOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                    <span style="color: #c4b5fd; font-size: 0.65rem; font-weight: 600;">🧬 {{ __('Character DNA') }}</span>
                                    <span style="color: rgba(255,255,255,0.4); font-size: 0.5rem; margin-left: 0.2rem;">({{ __('Hair, Wardrobe, Makeup, Accessories') }})</span>
                                    @if($hasDNA)
                                        <span style="color: #10b981; font-size: 0.5rem; margin-left: auto;">✓ {{ __('defined') }}</span>
                                    @endif
                                </button>

                                <div x-show="dnaOpen" x-collapse style="margin-top: 0.35rem;">
                                    {{-- Extract DNA from Portrait Button --}}
                                    @php
                                        $hasPortrait = !empty($currentChar['referenceImageBase64']) || !empty($currentChar['referenceImage']);
                                        $portraitReady = ($currentChar['referenceImageStatus'] ?? '') === 'ready';
                                    @endphp
                                    <div style="margin-bottom: 0.4rem;">
                                        <button type="button"
                                                wire:click="extractDNAFromPortrait({{ $editIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="extractDNAFromPortrait({{ $editIndex }})"
                                                {{ !$hasPortrait || !$portraitReady ? 'disabled' : '' }}
                                                style="width: 100%; padding: 0.4rem 0.5rem; background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(6,182,212,0.2)); border: 1px solid rgba(16,185,129,0.5); border-radius: 0.3rem; color: #6ee7b7; font-size: 0.6rem; font-weight: 600; cursor: {{ $hasPortrait && $portraitReady ? 'pointer' : 'not-allowed' }}; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.3rem; {{ !$hasPortrait || !$portraitReady ? 'opacity: 0.5;' : '' }}"
                                                title="{{ $hasPortrait && $portraitReady ? __('Analyze portrait image and auto-fill all DNA fields') : __('Generate a portrait first') }}">
                                            <span wire:loading.remove wire:target="extractDNAFromPortrait({{ $editIndex }})">🔍 {{ __('Extract DNA from Portrait') }}</span>
                                            <span wire:loading wire:target="extractDNAFromPortrait({{ $editIndex }})">⏳ {{ __('Analyzing...') }}</span>
                                        </button>
                                        @if(!$hasPortrait || !$portraitReady)
                                            <p style="color: rgba(255,255,255,0.4); font-size: 0.45rem; text-align: center; margin-top: 0.2rem;">{{ __('Generate a portrait first to extract DNA') }}</p>
                                        @else
                                            <p style="color: rgba(16,185,129,0.7); font-size: 0.45rem; text-align: center; margin-top: 0.2rem;">{{ __('Analyzes portrait to fill hair, wardrobe, accessories, etc.') }}</p>
                                        @endif
                                    </div>

                                    {{-- Quick Look Presets (Collapsible) --}}
                                    <div style="margin-bottom: 0.35rem;">
                                        <button type="button"
                                                @click="presetsOpen = !presetsOpen"
                                                style="display: flex; align-items: center; gap: 0.2rem; background: none; border: none; padding: 0; cursor: pointer; margin-bottom: 0.2rem;">
                                            <span style="color: rgba(255,255,255,0.5); font-size: 0.5rem; transition: transform 0.2s;" :style="presetsOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                            <span style="color: rgba(255,255,255,0.6); font-size: 0.55rem;">✨ {{ __('Quick Presets') }}</span>
                                        </button>
                                        <div x-show="presetsOpen" x-collapse style="display: flex; flex-wrap: wrap; gap: 0.2rem;">
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'corporate-female')" style="padding: 0.15rem 0.3rem; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); border-radius: 0.2rem; color: #a5b4fc; font-size: 0.45rem; cursor: pointer;">👩‍💼 {{ __('Corp F') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'corporate-male')" style="padding: 0.15rem 0.3rem; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); border-radius: 0.2rem; color: #a5b4fc; font-size: 0.45rem; cursor: pointer;">👨‍💼 {{ __('Corp M') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'tech-female')" style="padding: 0.15rem 0.3rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.2rem; color: #67e8f9; font-size: 0.45rem; cursor: pointer;">👩‍💻 {{ __('Tech F') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'tech-male')" style="padding: 0.15rem 0.3rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.2rem; color: #67e8f9; font-size: 0.45rem; cursor: pointer;">👨‍💻 {{ __('Tech M') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'action-hero-female')" style="padding: 0.15rem 0.3rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.2rem; color: #fca5a5; font-size: 0.45rem; cursor: pointer;">🦸‍♀️ {{ __('Action F') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'action-hero-male')" style="padding: 0.15rem 0.3rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.2rem; color: #fca5a5; font-size: 0.45rem; cursor: pointer;">🦸‍♂️ {{ __('Action M') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'scientist-female')" style="padding: 0.15rem 0.3rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.2rem; color: #6ee7b7; font-size: 0.45rem; cursor: pointer;">👩‍🔬 {{ __('Sci F') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'scientist-male')" style="padding: 0.15rem 0.3rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.2rem; color: #6ee7b7; font-size: 0.45rem; cursor: pointer;">👨‍🔬 {{ __('Sci M') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'cyberpunk')" style="padding: 0.15rem 0.3rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.2rem; color: #f9a8d4; font-size: 0.45rem; cursor: pointer;">🤖 {{ __('Cyber') }}</button>
                                            <button type="button" wire:click="applyCharacterLookPreset({{ $editIndex }}, 'fantasy-warrior')" style="padding: 0.15rem 0.3rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.2rem; color: #fcd34d; font-size: 0.45rem; cursor: pointer;">⚔️ {{ __('Fantasy') }}</button>
                                        </div>
                                    </div>

                                    {{-- Hair (inline compact) --}}
                                    <div style="margin-bottom: 0.3rem; padding: 0.25rem; background: rgba(255,255,255,0.03); border-radius: 0.25rem;">
                                        <div style="display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.2rem;">
                                            <span style="color: rgba(255,255,255,0.6); font-size: 0.55rem; min-width: 2rem;">💇 {{ __('Hair') }}</span>
                                            @if(!empty($currentChar['hair']['style'] ?? '') || !empty($currentChar['hair']['color'] ?? ''))
                                                <span style="color: #10b981; font-size: 0.45rem;">✓</span>
                                            @endif
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.2rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.hair.color"
                                                   placeholder="{{ __('Color') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.hair.style"
                                                   placeholder="{{ __('Style') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.hair.length"
                                                   placeholder="{{ __('Length') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.hair.texture"
                                                   placeholder="{{ __('Texture') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                        </div>
                                    </div>

                                    {{-- Wardrobe (inline compact) --}}
                                    <div style="margin-bottom: 0.3rem; padding: 0.25rem; background: rgba(255,255,255,0.03); border-radius: 0.25rem;">
                                        <div style="display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.2rem;">
                                            <span style="color: rgba(255,255,255,0.6); font-size: 0.55rem; min-width: 2rem;">👔 {{ __('Wardrobe') }}</span>
                                            @if(!empty($currentChar['wardrobe']['outfit'] ?? ''))
                                                <span style="color: #10b981; font-size: 0.45rem;">✓</span>
                                            @endif
                                        </div>
                                        <textarea wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.wardrobe.outfit"
                                                  placeholder="{{ __('Outfit description (e.g., fitted black jacket, slim pants)') }}"
                                                  style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem; min-height: 28px; resize: none; margin-bottom: 0.2rem;"></textarea>
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.2rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.wardrobe.colors"
                                                   placeholder="{{ __('Colors') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.wardrobe.style"
                                                   placeholder="{{ __('Style') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.wardrobe.footwear"
                                                   placeholder="{{ __('Footwear') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                        </div>
                                    </div>

                                    {{-- Makeup & Accessories (side by side) --}}
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem;">
                                        {{-- Makeup --}}
                                        <div style="padding: 0.25rem; background: rgba(255,255,255,0.03); border-radius: 0.25rem;">
                                            <div style="display: flex; align-items: center; gap: 0.2rem; margin-bottom: 0.2rem;">
                                                <span style="color: rgba(255,255,255,0.6); font-size: 0.55rem;">💄 {{ __('Makeup') }}</span>
                                                @if(!empty($currentChar['makeup']['style'] ?? ''))
                                                    <span style="color: #10b981; font-size: 0.45rem;">✓</span>
                                                @endif
                                            </div>
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.makeup.style"
                                                   placeholder="{{ __('Style (minimal, bold...)') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem; margin-bottom: 0.15rem;">
                                            <input type="text" wire:model.blur="sceneMemory.characterBible.characters.{{ $editIndex }}.makeup.details"
                                                   placeholder="{{ __('Details') }}"
                                                   style="width: 100%; padding: 0.2rem 0.3rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.55rem;">
                                        </div>

                                        {{-- Accessories --}}
                                        <div x-data="{ newAcc: '' }" style="padding: 0.25rem; background: rgba(255,255,255,0.03); border-radius: 0.25rem;">
                                            <div style="display: flex; align-items: center; gap: 0.2rem; margin-bottom: 0.2rem;">
                                                <span style="color: rgba(255,255,255,0.6); font-size: 0.55rem;">💍 {{ __('Accessories') }}</span>
                                                <span style="color: rgba(255,255,255,0.4); font-size: 0.45rem;">({{ count($currentChar['accessories'] ?? []) }})</span>
                                            </div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.15rem; margin-bottom: 0.15rem; min-height: 18px;">
                                                @foreach($currentChar['accessories'] ?? [] as $accIdx => $accessory)
                                                    <span style="display: inline-flex; align-items: center; gap: 0.1rem; padding: 0.1rem 0.25rem; background: rgba(251,191,36,0.2); border: 1px solid rgba(251,191,36,0.4); border-radius: 0.5rem; color: #fcd34d; font-size: 0.45rem;">
                                                        {{ $accessory }}
                                                        <button type="button" wire:click="removeCharacterAccessory({{ $editIndex }}, {{ $accIdx }})" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; padding: 0; line-height: 1; font-size: 0.5rem;">&times;</button>
                                                    </span>
                                                @endforeach
                                            </div>
                                            <div style="display: flex; gap: 0.15rem;">
                                                <input type="text" x-model="newAcc"
                                                       @keydown.enter.prevent="if(newAcc.trim()) { $wire.addCharacterAccessory({{ $editIndex }}, newAcc.trim()); newAcc = ''; }"
                                                       placeholder="{{ __('Add...') }}"
                                                       style="flex: 1; padding: 0.15rem 0.25rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.2rem; color: white; font-size: 0.5rem;">
                                                <button type="button"
                                                        @click="if(newAcc.trim()) { $wire.addCharacterAccessory({{ $editIndex }}, newAcc.trim()); newAcc = ''; }"
                                                        style="padding: 0.15rem 0.25rem; background: rgba(251,191,36,0.2); border: 1px solid rgba(251,191,36,0.4); border-radius: 0.2rem; color: #fcd34d; font-size: 0.45rem; cursor: pointer;">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════════════════════════════ --}}
                            {{-- CHARACTER VOICE - For Multitalk lip-sync and voiceover --}}
                            {{-- ═══════════════════════════════════════════════════════════════════ --}}
                            @php
                                $voiceSettings = $currentChar['voice'] ?? [];
                                $hasVoice = !empty($voiceSettings['id']);
                            @endphp

                            <div x-data="{ voiceOpen: false }" style="margin-bottom: 0.4rem; padding: 0.35rem; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 0.35rem;">
                                {{-- Voice Section Header --}}
                                <button type="button"
                                        @click="voiceOpen = !voiceOpen"
                                        style="display: flex; align-items: center; gap: 0.25rem; width: 100%; background: none; border: none; padding: 0; cursor: pointer;">
                                    <span style="color: #6ee7b7; font-size: 0.6rem; transition: transform 0.2s;" :style="voiceOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                    <span style="color: #6ee7b7; font-size: 0.65rem; font-weight: 600;">🎙️ {{ __('Character Voice') }}</span>
                                    <span style="color: rgba(255,255,255,0.4); font-size: 0.5rem; margin-left: 0.2rem;">({{ __('TTS & Lip-sync') }})</span>
                                    @if($hasVoice)
                                        <span style="color: #10b981; font-size: 0.5rem; margin-left: auto;">✓ {{ $voiceSettings['id'] ?? '' }}</span>
                                    @endif
                                    @if($currentChar['isNarrator'] ?? false)
                                        <span style="background: rgba(251,191,36,0.3); color: #fcd34d; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.45rem; font-weight: 600;">{{ __('NARRATOR') }}</span>
                                    @endif
                                </button>

                                <div x-show="voiceOpen" x-collapse style="margin-top: 0.35rem;">
                                    {{-- Voice Presets --}}
                                    <div style="margin-bottom: 0.35rem;">
                                        <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.5rem; margin-bottom: 0.15rem;">{{ __('Voice Presets') }}</label>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'hero-male')" style="padding: 0.15rem 0.3rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.2rem; color: #67e8f9; font-size: 0.45rem; cursor: pointer;">🦸‍♂️ {{ __('Hero M') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'hero-female')" style="padding: 0.15rem 0.3rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.2rem; color: #f9a8d4; font-size: 0.45rem; cursor: pointer;">🦸‍♀️ {{ __('Hero F') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'villain-male')" style="padding: 0.15rem 0.3rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.2rem; color: #c4b5fd; font-size: 0.45rem; cursor: pointer;">🦹‍♂️ {{ __('Villain M') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'villain-female')" style="padding: 0.15rem 0.3rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.2rem; color: #c4b5fd; font-size: 0.45rem; cursor: pointer;">🦹‍♀️ {{ __('Villain F') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'mentor')" style="padding: 0.15rem 0.3rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.2rem; color: #fcd34d; font-size: 0.45rem; cursor: pointer;">🧙 {{ __('Mentor') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'narrator')" style="padding: 0.15rem 0.3rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.2rem; color: #6ee7b7; font-size: 0.45rem; cursor: pointer;">📖 {{ __('Narrator') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'child')" style="padding: 0.15rem 0.3rem; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); border-radius: 0.2rem; color: #fcd34d; font-size: 0.45rem; cursor: pointer;">👶 {{ __('Child') }}</button>
                                            <button type="button" wire:click="applyCharacterVoicePreset({{ $editIndex }}, 'elder')" style="padding: 0.15rem 0.3rem; background: rgba(107,114,128,0.2); border: 1px solid rgba(107,114,128,0.4); border-radius: 0.2rem; color: #d1d5db; font-size: 0.45rem; cursor: pointer;">👴 {{ __('Elder') }}</button>
                                        </div>
                                    </div>

                                    {{-- Voice ID & Gender (side by side) --}}
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.3rem; margin-bottom: 0.3rem;">
                                        {{-- Voice ID --}}
                                        <div>
                                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.5rem; margin-bottom: 0.1rem;">{{ __('Voice') }}</label>
                                            <select wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.voice.id"
                                                    style="width: 100%; padding: 0.25rem 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                                <option value="">{{ __('Auto (by gender)') }}</option>
                                                <option value="alloy">Alloy ({{ __('Neutral/Balanced') }})</option>
                                                <option value="echo">Echo ({{ __('Male/Deep') }})</option>
                                                <option value="fable">Fable ({{ __('British/Expressive') }})</option>
                                                <option value="onyx">Onyx ({{ __('Male/Strong') }})</option>
                                                <option value="nova">Nova ({{ __('Female/Warm') }})</option>
                                                <option value="shimmer">Shimmer ({{ __('Female/Soft') }})</option>
                                            </select>
                                        </div>

                                        {{-- Gender --}}
                                        <div>
                                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.5rem; margin-bottom: 0.1rem;">{{ __('Gender') }}</label>
                                            <select wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.voice.gender"
                                                    style="width: 100%; padding: 0.25rem 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                                <option value="">{{ __('Auto-detect') }}</option>
                                                <option value="male">{{ __('Male') }}</option>
                                                <option value="female">{{ __('Female') }}</option>
                                                <option value="neutral">{{ __('Neutral') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Style & Pitch (side by side) --}}
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.3rem; margin-bottom: 0.3rem;">
                                        {{-- Voice Style --}}
                                        <div>
                                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.5rem; margin-bottom: 0.1rem;">{{ __('Style') }}</label>
                                            <select wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.voice.style"
                                                    style="width: 100%; padding: 0.25rem 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                                <option value="natural">{{ __('Natural') }}</option>
                                                <option value="warm">{{ __('Warm') }}</option>
                                                <option value="authoritative">{{ __('Authoritative') }}</option>
                                                <option value="energetic">{{ __('Energetic') }}</option>
                                                <option value="calm">{{ __('Calm') }}</option>
                                                <option value="confident">{{ __('Confident') }}</option>
                                                <option value="storytelling">{{ __('Storytelling') }}</option>
                                                <option value="intense">{{ __('Intense') }}</option>
                                            </select>
                                        </div>

                                        {{-- Pitch --}}
                                        <div>
                                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.5rem; margin-bottom: 0.1rem;">{{ __('Pitch') }}</label>
                                            <select wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.voice.pitch"
                                                    style="width: 100%; padding: 0.25rem 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                                <option value="low">{{ __('Low') }}</option>
                                                <option value="medium">{{ __('Medium') }}</option>
                                                <option value="high">{{ __('High') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Speed Slider --}}
                                    <div style="margin-bottom: 0.35rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.1rem;">
                                            <label style="color: rgba(255,255,255,0.6); font-size: 0.5rem;">{{ __('Speed') }}</label>
                                            <span style="color: rgba(255,255,255,0.5); font-size: 0.5rem;">{{ number_format($voiceSettings['speed'] ?? 1.0, 2) }}x</span>
                                        </div>
                                        <input type="range"
                                               wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.voice.speed"
                                               min="0.5" max="2.0" step="0.05"
                                               style="width: 100%; height: 4px; accent-color: #10b981;">
                                    </div>

                                    {{-- Speaking Role & Narrator (side by side) --}}
                                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.5rem; padding-top: 0.3rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                        {{-- Speaking Role --}}
                                        <div>
                                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.5rem; margin-bottom: 0.1rem;">{{ __('Speaking Role') }}</label>
                                            <select wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.speakingRole"
                                                    style="width: 100%; padding: 0.25rem 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.6rem;">
                                                <option value="dialogue">{{ __('Dialogue') }} - {{ __('Speaks in scenes') }}</option>
                                                <option value="monologue">{{ __('Monologue') }} - {{ __('Thinking/narrating') }}</option>
                                                <option value="narrator">{{ __('Narrator') }} - {{ __('Tells the story') }}</option>
                                                <option value="silent">{{ __('Silent') }} - {{ __('No speaking') }}</option>
                                            </select>
                                        </div>

                                        {{-- Is Narrator Toggle --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-end;">
                                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer; padding: 0.25rem 0.4rem; background: {{ ($currentChar['isNarrator'] ?? false) ? 'rgba(251,191,36,0.25)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ ($currentChar['isNarrator'] ?? false) ? 'rgba(251,191,36,0.5)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.25rem;">
                                                <input type="checkbox"
                                                       wire:model.live="sceneMemory.characterBible.characters.{{ $editIndex }}.isNarrator"
                                                       style="accent-color: #f59e0b; width: 12px; height: 12px;">
                                                <span style="color: {{ ($currentChar['isNarrator'] ?? false) ? '#fcd34d' : 'rgba(255,255,255,0.6)' }}; font-size: 0.55rem; font-weight: 500;">📖 {{ __('Narrator') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Help text --}}
                                    <p style="color: rgba(255,255,255,0.4); font-size: 0.45rem; margin-top: 0.25rem; line-height: 1.3;">
                                        {{ __('Voice settings are used for TTS voiceover generation and Multitalk lip-sync. Mark as Narrator if this character narrates the story.') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Appears in Scenes --}}
                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ __('Appears in Scenes') }}</label>
                            @php
                                $characterScenes = $currentChar['scenes'] ?? $currentChar['appliedScenes'] ?? [];
                                $appliedCount = count($characterScenes);
                                $totalScenes = count($script['scenes'] ?? []);
                            @endphp
                            <span style="font-size: 0.55rem; color: {{ $appliedCount > 0 ? '#10b981' : 'rgba(255,255,255,0.4)' }};">
                                {{ $appliedCount }}/{{ $totalScenes }} {{ __('scenes') }}
                            </span>
                        </div>
                        @if($appliedCount === 0 && $totalScenes > 0)
                            <div style="padding: 0.35rem 0.5rem; margin-bottom: 0.3rem; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.25rem;">
                                <span style="color: #fca5a5; font-size: 0.55rem;">⚠️ {{ __('Please assign at least one scene to this character') }}</span>
                            </div>
                        @endif
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            @foreach($script['scenes'] ?? [] as $sceneIndex => $scene)
                                @php
                                    $isApplied = in_array($sceneIndex, $characterScenes);
                                @endphp
                                <button type="button"
                                        wire:click.debounce.300ms="toggleCharacterScene({{ $editIndex }}, {{ $sceneIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleCharacterScene"
                                        style="width: 28px; height: 28px; border-radius: 0.3rem; border: 2px solid {{ $isApplied ? '#8b5cf6' : 'rgba(255,255,255,0.2)' }}; background: {{ $isApplied ? 'linear-gradient(135deg, #8b5cf6, #7c3aed)' : 'rgba(255,255,255,0.05)' }}; color: {{ $isApplied ? 'white' : 'rgba(255,255,255,0.5)' }}; cursor: pointer; font-size: 0.7rem; font-weight: {{ $isApplied ? '700' : '500' }}; transition: all 0.15s ease; {{ $isApplied ? 'box-shadow: 0 2px 8px rgba(139,92,246,0.4);' : '' }}">
                                    {{ $sceneIndex + 1 }}
                                </button>
                            @endforeach
                            @if(count($script['scenes'] ?? []) > 0)
                                @php
                                    $allScenesApplied = $appliedCount === $totalScenes;
                                @endphp
                                <button type="button"
                                        wire:click.debounce.300ms="applyCharacterToAllScenes({{ $editIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="applyCharacterToAllScenes"
                                        style="padding: 0.25rem 0.6rem; border-radius: 0.3rem; border: 2px solid {{ $allScenesApplied ? '#10b981' : 'rgba(16,185,129,0.4)' }}; background: {{ $allScenesApplied ? 'linear-gradient(135deg, #10b981, #059669)' : 'rgba(16,185,129,0.15)' }}; color: {{ $allScenesApplied ? 'white' : '#6ee7b7' }}; cursor: pointer; font-size: 0.6rem; font-weight: 600; margin-left: 0.3rem; {{ $allScenesApplied ? 'box-shadow: 0 2px 8px rgba(16,185,129,0.3);' : '' }}">
                                    {{ __('All') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Delete Character --}}
                    <div style="margin-top: 0.5rem; padding-top: 0.5rem;">
                        <button type="button"
                                wire:click="removeCharacter({{ $editIndex }})"
                                wire:confirm="{{ __('Are you sure you want to delete this character?') }}"
                                style="padding: 0.3rem 0.6rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #f87171; font-size: 0.65rem; cursor: pointer;">
                            🗑️ {{ __('Delete Character') }}
                        </button>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">👤</span>
                        <p style="margin: 0; font-size: 0.8rem;">{{ __('Add a character to get started') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <label style="display: flex; align-items: center; gap: 0.4rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.characterBible.enabled" style="accent-color: #8b5cf6;">
                {{ __('Enable Character Bible') }}
            </label>
            <button type="button"
                    wire:click="closeCharacterBibleModal"
                    style="padding: 0.4rem 0.9rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.35rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.75rem;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>

{{-- JavaScript for batch portrait generation polling --}}
<script>
(function() {
    // Prevent duplicate listener registration
    if (window.charBiblePollingInitialized) {
        console.log('[CharacterBible] Polling already initialized, skipping');
        return;
    }
    window.charBiblePollingInitialized = true;

    // Character Bible batch generation polling
    let charGenPollingActive = false;
    let charGenRetryCount = 0;
    const charGenMaxRetries = 50;

    console.log('[CharacterBible] Setting up Livewire event listener for continue-character-reference-generation');

    Livewire.on('continue-character-reference-generation', function(data) {
        console.log('[CharacterBible] Received continue-character-reference-generation event', data);
        if (!charGenPollingActive) {
            charGenPollingActive = true;
            charGenRetryCount = 0;
            pollNextCharacterPortrait();
        }
    });

    function pollNextCharacterPortrait() {
        if (charGenRetryCount >= charGenMaxRetries) {
            console.log('[CharacterBible] Max retries reached, stopping polling');
            charGenPollingActive = false;
            return;
        }

        charGenRetryCount++;
        console.log('[CharacterBible] Polling for next portrait, attempt', charGenRetryCount);

        // Use setTimeout to give Livewire time to update state
        setTimeout(function() {
            // Find the Livewire component
            const wireEl = Array.from(document.querySelectorAll('*')).find(function(el) {
                return el.hasAttribute('wire:id');
            });
            const wireId = wireEl ? wireEl.getAttribute('wire:id') : null;
            const component = wireId ? Livewire.find(wireId) : null;

            if (component && component.$wire) {
                component.$wire.generateNextPendingCharacterPortrait().then(function(result) {
                    console.log('[CharacterBible] generateNextPendingCharacterPortrait result:', result);
                    if (result && result.remaining > 0) {
                        // More portraits to generate, continue polling
                        console.log('[CharacterBible] ' + result.remaining + ' portraits remaining');
                        setTimeout(pollNextCharacterPortrait, 1500);
                    } else {
                        console.log('[CharacterBible] All portraits generated, stopping polling');
                        charGenPollingActive = false;
                    }
                }).catch(function(error) {
                    console.error('[CharacterBible] Error generating portrait:', error);
                    charGenPollingActive = false;
                });
            } else {
                console.warn('[CharacterBible] Livewire component not found');
                charGenPollingActive = false;
            }
        }, 500);
    }
})();
</script>
@endif
