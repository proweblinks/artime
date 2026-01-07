{{-- Scene Edit Modal (Enhanced from Edit Prompt) --}}
<div x-data="{ isOpen: false, sceneIndex: 0, activeTab: 'visual' }"
     @open-edit-prompt-modal.window="isOpen = true; sceneIndex = $event.detail.sceneIndex; activeTab = 'visual'"
     @close-edit-prompt-modal.window="isOpen = false"
     x-show="isOpen"
     x-cloak
     class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="isOpen = false"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">✏️ {{ __('Edit Scene') }} {{ $editPromptSceneIndex + 1 }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Modify scene properties and visual description') }}</p>
            </div>
            <button type="button" @click="isOpen = false" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Tab Navigation --}}
        <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; gap: 0.5rem;">
            <button type="button"
                    @click="activeTab = 'visual'"
                    :style="activeTab === 'visual' ? 'background: rgba(139,92,246,0.2); border-color: rgba(139,92,246,0.5); color: #c4b5fd;' : ''"
                    style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                🎨 {{ __('Visual') }}
            </button>
            <button type="button"
                    @click="activeTab = 'content'"
                    :style="activeTab === 'content' ? 'background: rgba(139,92,246,0.2); border-color: rgba(139,92,246,0.5); color: #c4b5fd;' : ''"
                    style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                📝 {{ __('Content') }}
            </button>
            <button type="button"
                    @click="activeTab = 'timing'"
                    :style="activeTab === 'timing' ? 'background: rgba(139,92,246,0.2); border-color: rgba(139,92,246,0.5); color: #c4b5fd;' : ''"
                    style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.8rem; font-weight: 500;">
                ⏱️ {{ __('Timing') }}
            </button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;">
            {{-- Current Image Preview (Always visible) --}}
            @if($showEditPromptModal && isset($storyboard['scenes'][$editPromptSceneIndex]['imageUrl']))
                <div style="margin-bottom: 1.25rem; display: flex; gap: 1rem;">
                    <div style="width: 200px; border-radius: 0.5rem; overflow: hidden; aspect-ratio: 16/9; background: rgba(0,0,0,0.3); flex-shrink: 0;">
                        <img src="{{ $storyboard['scenes'][$editPromptSceneIndex]['imageUrl'] }}"
                             alt="Current scene image"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: white; font-size: 0.9rem; margin-bottom: 0.25rem;">
                            {{ $script['scenes'][$editPromptSceneIndex]['title'] ?? __('Scene') . ' ' . ($editPromptSceneIndex + 1) }}
                        </div>
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-bottom: 0.5rem;">
                            ⏱️ {{ $editSceneDuration }}s • ↔️ {{ ucfirst($editSceneTransition) }}
                        </div>
                        @if($storyboard['scenes'][$editPromptSceneIndex]['source'] ?? 'ai' === 'stock')
                            <span style="font-size: 0.6rem; padding: 0.15rem 0.4rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.25rem; color: #6ee7b7;">📷 {{ __('Stock Media') }}</span>
                        @else
                            <span style="font-size: 0.6rem; padding: 0.15rem 0.4rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.25rem; color: #c4b5fd;">🎨 {{ __('AI Generated') }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Visual Tab --}}
            <div x-show="activeTab === 'visual'">
                {{-- Edit Prompt Textarea --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Visual Description / Image Prompt') }}</label>
                    <textarea wire:model="editPromptText"
                              placeholder="{{ __('Describe what you want to see in this scene...') }}"
                              style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 120px; resize: vertical;"></textarea>
                </div>

                {{-- Suggested Edits --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Quick Add') }}</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                        <button type="button" wire:click="appendToPrompt('cinematic lighting, dramatic shadows')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Cinematic lighting') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('shallow depth of field, bokeh background')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Shallow DOF') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('golden hour lighting, warm tones')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Golden hour') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('wide angle shot, establishing view')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Wide shot') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('close-up shot, detailed')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Close-up') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('moody atmosphere, volumetric lighting')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('Moody') }}
                        </button>
                        <button type="button" wire:click="appendToPrompt('high contrast, dramatic shadows')" style="padding: 0.35rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                            + {{ __('High contrast') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Content Tab --}}
            <div x-show="activeTab === 'content'">
                {{-- Narration --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Narration / Voiceover Text') }}</label>
                    <textarea wire:model="editSceneNarration"
                              placeholder="{{ __('Enter the narration or dialogue for this scene...') }}"
                              style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 150px; resize: vertical;"></textarea>
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.7rem; margin-top: 0.35rem;">
                        💡 {{ __('This text will be used for voiceover generation and scene context.') }}
                    </p>
                </div>
            </div>

            {{-- Timing Tab --}}
            <div x-show="activeTab === 'timing'">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    {{-- Duration --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Duration (seconds)') }}</label>
                        <div style="display: flex; gap: 0.5rem;">
                            @foreach([3, 5, 8, 10, 15] as $duration)
                                <button type="button"
                                        wire:click="$set('editSceneDuration', {{ $duration }})"
                                        style="flex: 1; padding: 0.6rem; border-radius: 0.5rem; border: 1px solid {{ $editSceneDuration === $duration ? 'rgba(6,182,212,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $editSceneDuration === $duration ? 'rgba(6,182,212,0.2)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 0.85rem; font-weight: 600;">
                                    {{ $duration }}s
                                </button>
                            @endforeach
                        </div>
                        <div style="margin-top: 0.75rem;">
                            <input type="range" min="1" max="30" wire:model.live="editSceneDuration"
                                   style="width: 100%; accent-color: #06b6d4;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.65rem; color: rgba(255,255,255,0.4);">
                                <span>1s</span>
                                <span>{{ $editSceneDuration }}s</span>
                                <span>30s</span>
                            </div>
                        </div>
                    </div>

                    {{-- Transition --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Transition to Next Scene') }}</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            @php
                                $transitions = [
                                    'cut' => ['icon' => '✂️', 'label' => 'Cut'],
                                    'fade' => ['icon' => '🌑', 'label' => 'Fade'],
                                    'dissolve' => ['icon' => '💫', 'label' => 'Dissolve'],
                                    'wipe' => ['icon' => '➡️', 'label' => 'Wipe'],
                                    'zoom' => ['icon' => '🔍', 'label' => 'Zoom'],
                                    'slide' => ['icon' => '📤', 'label' => 'Slide'],
                                ];
                            @endphp
                            @foreach($transitions as $value => $trans)
                                <button type="button"
                                        wire:click="$set('editSceneTransition', '{{ $value }}')"
                                        style="padding: 0.5rem; border-radius: 0.35rem; border: 1px solid {{ $editSceneTransition === $value ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $editSceneTransition === $value ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <span>{{ $trans['icon'] }}</span>
                                    <span>{{ __($trans['label']) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <button type="button"
                    wire:click="saveSceneProperties"
                    style="padding: 0.6rem 1.25rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                💾 {{ __('Save Only') }}
            </button>
            <div style="display: flex; gap: 0.75rem;">
                <button type="button"
                        @click="isOpen = false; $wire.closeEditPrompt()"
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
</div>
