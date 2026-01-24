{{-- Story Bible Modal - Phase 1: Bible-First Architecture --}}
@if($showStoryBibleModal ?? false)
<div class="vw-modal-overlay"
     wire:key="story-bible-modal"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.75rem; width: 100%; max-width: 1000px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: linear-gradient(135deg, rgba(251,191,36,0.1), rgba(245,158,11,0.05));">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.25rem;">📖</span>
                    {{ __('Story Bible') }}
                    @if($storyBible['status'] === 'ready')
                        <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('READY') }}</span>
                    @elseif($storyBible['status'] === 'generating')
                        <span style="background: rgba(251,191,36,0.2); color: #fbbf24; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('GENERATING...') }}</span>
                    @else
                        <span style="background: rgba(156,163,175,0.2); color: #9ca3af; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('NOT GENERATED') }}</span>
                    @endif
                </h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('The DNA that constrains all video generation - characters, locations, visual style') }}</p>
            </div>
            <button type="button" wire:click="closeStoryBibleModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Error Display --}}
        @if($error)
            <div style="padding: 0.5rem 1rem; background: rgba(239,68,68,0.15); border-bottom: 1px solid rgba(239,68,68,0.3); color: #fca5a5; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <span>{{ $error }}</span>
                <button type="button" wire:click="$set('error', null)" style="margin-left: auto; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 0.9rem;">&times;</button>
            </div>
        @endif

        {{-- Warning if Bible is ready but missing critical data --}}
        @if($storyBible['status'] === 'ready' && (empty($storyBible['characters']) || empty($storyBible['locations'])))
            <div style="padding: 0.5rem 1rem; background: rgba(251,191,36,0.15); border-bottom: 1px solid rgba(251,191,36,0.3); color: #fcd34d; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <span>⚠️</span>
                <span>{{ __('Story Bible is incomplete') }}:
                    @if(empty($storyBible['characters'])) {{ __('No characters defined') }}@endif
                    @if(empty($storyBible['characters']) && empty($storyBible['locations'])) {{ __('and') }} @endif
                    @if(empty($storyBible['locations'])) {{ __('No locations defined') }}@endif
                    - {{ __('Click "Regenerate Story Bible" or add them manually') }}
                </span>
            </div>
        @endif

        {{-- Tabs --}}
        <div style="display: flex; gap: 0.25rem; padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); flex-shrink: 0; background: rgba(0,0,0,0.2); overflow-x: auto;">
            @foreach(['overview' => ['icon' => '📋', 'label' => 'Overview'], 'characters' => ['icon' => '👥', 'label' => 'Characters'], 'locations' => ['icon' => '🏛️', 'label' => 'Locations'], 'style' => ['icon' => '🎨', 'label' => 'Visual Style'], 'cinematography' => ['icon' => '🎬', 'label' => 'Cinematography']] as $tabKey => $tabInfo)
                <button type="button"
                        wire:click="setStoryBibleTab('{{ $tabKey }}')"
                        style="padding: 0.4rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: all 0.2s; border: 1px solid {{ $storyBibleTab === $tabKey ? 'rgba(251,191,36,0.5)' : 'transparent' }}; background: {{ $storyBibleTab === $tabKey ? 'rgba(251,191,36,0.15)' : 'transparent' }}; color: {{ $storyBibleTab === $tabKey ? '#fcd34d' : 'rgba(255,255,255,0.6)' }};">
                    <span>{{ $tabInfo['icon'] }}</span>
                    <span>{{ __($tabInfo['label']) }}</span>
                    @if($tabKey === 'characters')
                        <span style="background: rgba(255,255,255,0.1); padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.6rem;">{{ count($storyBible['characters'] ?? []) }}</span>
                    @elseif($tabKey === 'locations')
                        <span style="background: rgba(255,255,255,0.1); padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.6rem;">{{ count($storyBible['locations'] ?? []) }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1rem; position: relative;">

            {{-- Loading Overlay when generating --}}
            @if($storyBible['status'] === 'generating')
                <div style="position: absolute; inset: 0; background: rgba(20,20,35,0.9); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; gap: 1rem;">
                    <div style="width: 48px; height: 48px; border: 3px solid rgba(251,191,36,0.2); border-top-color: #fbbf24; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                    <div style="text-align: center;">
                        <div style="color: #fcd34d; font-size: 0.9rem; font-weight: 600;">{{ __('Generating Story Bible...') }}</div>
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 0.25rem;">{{ __('Creating characters, locations, and structure') }}</div>
                    </div>
                </div>
            @endif

            {{-- OVERVIEW TAB --}}
            @if($storyBibleTab === 'overview')
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    {{-- Left Column: Core Info --}}
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        {{-- Title --}}
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Title') }}</label>
                            <input type="text"
                                   wire:model.blur="storyBible.title"
                                   placeholder="{{ __('Video title...') }}"
                                   style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.85rem;">
                        </div>

                        {{-- Logline --}}
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Logline') }}</label>
                            <textarea wire:model.blur="storyBible.logline"
                                      placeholder="{{ __('One-sentence summary of your story...') }}"
                                      style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.8rem; min-height: 60px; resize: vertical;"></textarea>
                        </div>

                        {{-- Theme & Tone Row --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Theme') }}</label>
                                <input type="text"
                                       wire:model.blur="storyBible.theme"
                                       placeholder="{{ __('Core message...') }}"
                                       style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                            </div>
                            <div>
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Tone') }}</label>
                                <input type="text"
                                       wire:model.blur="storyBible.tone"
                                       placeholder="{{ __('Emotional feel...') }}"
                                       style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                            </div>
                        </div>

                        {{-- Structure Template --}}
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Story Structure') }}</label>
                            <div style="display: flex; gap: 0.5rem;">
                                @foreach(['three-act' => '3-Act', 'five-act' => '5-Act', 'heros-journey' => "Hero's Journey"] as $key => $label)
                                    <button type="button"
                                            wire:click="setStoryBibleStructure('{{ $key }}')"
                                            style="flex: 1; padding: 0.4rem 0.5rem; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; border: 1px solid {{ ($storyBible['structureTemplate'] ?? 'three-act') === $key ? 'rgba(251,191,36,0.5)' : 'rgba(255,255,255,0.15)' }}; background: {{ ($storyBible['structureTemplate'] ?? 'three-act') === $key ? 'rgba(251,191,36,0.15)' : 'rgba(255,255,255,0.05)' }}; color: {{ ($storyBible['structureTemplate'] ?? 'three-act') === $key ? '#fcd34d' : 'rgba(255,255,255,0.7)' }};">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Acts Structure --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Act Structure') }}</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 280px; overflow-y: auto; padding-right: 0.25rem;">
                            @forelse($storyBible['acts'] ?? [] as $actIndex => $act)
                                <div style="padding: 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.375rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <span style="font-weight: 600; color: #fcd34d; font-size: 0.75rem;">{{ __('Act') }} {{ $act['actNumber'] ?? ($actIndex + 1) }}: {{ $act['name'] ?? '' }}</span>
                                        <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ $act['percentage'] ?? 0 }}%</span>
                                    </div>
                                    <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.7rem; line-height: 1.4;">{{ $act['description'] ?? '' }}</p>
                                    @if(!empty($act['turningPoint']))
                                        <p style="margin: 0.25rem 0 0 0; color: #f472b6; font-size: 0.65rem;"><strong>{{ __('Turning Point') }}:</strong> {{ $act['turningPoint'] }}</p>
                                    @endif
                                </div>
                            @empty
                                <div style="padding: 1rem; text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem;">
                                    {{ __('No acts defined yet. Generate Story Bible to create structure.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Generate Button --}}
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <button type="button"
                            wire:click="generateStoryBible"
                            @if($isGeneratingStoryBible) disabled @endif
                            style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #f59e0b 0%, #ec4899 100%); border: none; border-radius: 0.5rem; color: white; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; {{ $isGeneratingStoryBible ? 'opacity: 0.7;' : '' }}">
                        @if($isGeneratingStoryBible)
                            <div style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                            {{ __('Generating Story Bible...') }}
                        @elseif($storyBible['status'] === 'ready')
                            🔄 {{ __('Regenerate Story Bible') }}
                        @else
                            ✨ {{ __('Generate Story Bible') }}
                        @endif
                    </button>
                    <p style="text-align: center; color: rgba(255,255,255,0.4); font-size: 0.65rem; margin-top: 0.5rem;">{{ __('AI will create title, characters, locations, and structure based on your concept') }}</p>
                </div>
            @endif

            {{-- CHARACTERS TAB --}}
            @if($storyBibleTab === 'characters')
                <div style="display: flex; gap: 0.75rem; height: calc(100% - 40px);">
                    {{-- Characters List --}}
                    <div style="width: 200px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.75rem;">
                        <button type="button"
                                wire:click="addBibleCharacter"
                                style="width: 100%; padding: 0.4rem; background: transparent; border: 2px dashed rgba(251,191,36,0.4); border-radius: 0.375rem; color: #fcd34d; font-size: 0.7rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                            <span>+</span> {{ __('Add Character') }}
                        </button>

                        <div style="display: flex; flex-direction: column; gap: 0.35rem; max-height: 350px; overflow-y: auto;">
                            @forelse($storyBible['characters'] ?? [] as $charIndex => $char)
                                <div wire:click="editBibleCharacter({{ $charIndex }})"
                                     style="padding: 0.5rem; background: {{ $editingBibleCharacterIndex === $charIndex ? 'rgba(251,191,36,0.15)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $editingBibleCharacterIndex === $charIndex ? 'rgba(251,191,36,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.375rem; cursor: pointer;">
                                    <div style="font-weight: 600; color: white; font-size: 0.75rem; margin-bottom: 0.15rem;">{{ $char['name'] ?: __('Unnamed') }}</div>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <span style="background: rgba(139,92,246,0.2); color: #c4b5fd; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem;">{{ ucfirst($char['role'] ?? 'supporting') }}</span>
                                        @if(!empty($char['description']))
                                            <span style="color: #10b981; font-size: 0.55rem;">✓ {{ __('desc') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 1rem; color: rgba(255,255,255,0.4); font-size: 0.65rem; text-align: center;">
                                    {{ __('No characters defined yet') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Character Editor --}}
                    <div style="flex: 1; overflow-y: auto;">
                        @php
                            $characters = $storyBible['characters'] ?? [];
                            $currentChar = $characters[$editingBibleCharacterIndex] ?? null;
                        @endphp

                        @if($currentChar)
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                {{-- Name & Role --}}
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem;">
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Character Name') }}</label>
                                        <input type="text"
                                               wire:model.blur="storyBible.characters.{{ $editingBibleCharacterIndex }}.name"
                                               placeholder="{{ __('Full name...') }}"
                                               style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.8rem;">
                                    </div>
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Role') }}</label>
                                        <select wire:model.change="storyBible.characters.{{ $editingBibleCharacterIndex }}.role"
                                                style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                                            <option value="protagonist">{{ __('Protagonist') }}</option>
                                            <option value="antagonist">{{ __('Antagonist') }}</option>
                                            <option value="supporting">{{ __('Supporting') }}</option>
                                            <option value="narrator">{{ __('Narrator') }}</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Visual Description (CRITICAL for AI image generation) --}}
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">
                                        {{ __('Visual Description') }}
                                        <span style="color: #f59e0b;">({{ __('Critical for AI consistency') }})</span>
                                    </label>
                                    <textarea wire:model.blur="storyBible.characters.{{ $editingBibleCharacterIndex }}.description"
                                              placeholder="{{ __('Age, gender, ethnicity, build, hair color/style, eye color, distinctive features, typical clothing...') }}"
                                              style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 80px; resize: vertical;"></textarea>
                                    <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.4); font-size: 0.55rem;">{{ __('Be specific - this description will be used in every image prompt featuring this character') }}</p>
                                </div>

                                {{-- Character Arc --}}
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Character Arc') }}</label>
                                    <textarea wire:model.blur="storyBible.characters.{{ $editingBibleCharacterIndex }}.arc"
                                              placeholder="{{ __('How does this character change throughout the story?') }}"
                                              style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.7rem; min-height: 50px; resize: vertical;"></textarea>
                                </div>

                                {{-- Traits --}}
                                <div x-data="{ newTrait: '' }">
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Traits') }}</label>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.35rem;">
                                        @forelse($currentChar['traits'] ?? [] as $traitIdx => $trait)
                                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.75rem; color: #c4b5fd; font-size: 0.6rem;">
                                                {{ $trait }}
                                                <button type="button" wire:click="removeBibleCharacterTrait({{ $editingBibleCharacterIndex }}, {{ $traitIdx }})" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; padding: 0; line-height: 1; font-size: 0.7rem;">&times;</button>
                                            </span>
                                        @empty
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.6rem; font-style: italic;">{{ __('No traits') }}</span>
                                        @endforelse
                                    </div>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <input type="text"
                                               x-model="newTrait"
                                               @keydown.enter.prevent="if(newTrait.trim()) { $wire.addBibleCharacterTrait({{ $editingBibleCharacterIndex }}, newTrait.trim()); newTrait = ''; }"
                                               placeholder="{{ __('Add trait...') }}"
                                               style="flex: 1; padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                                        <button type="button"
                                                @click="if(newTrait.trim()) { $wire.addBibleCharacterTrait({{ $editingBibleCharacterIndex }}, newTrait.trim()); newTrait = ''; }"
                                                style="padding: 0.3rem 0.5rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.6rem; cursor: pointer;">
                                            +
                                        </button>
                                    </div>
                                </div>

                                {{-- Delete Character --}}
                                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                    <button type="button"
                                            wire:click="removeBibleCharacter({{ $editingBibleCharacterIndex }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this character?') }}"
                                            style="padding: 0.3rem 0.6rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #f87171; font-size: 0.65rem; cursor: pointer;">
                                        {{ __('Delete Character') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: rgba(255,255,255,0.4);">
                                <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">👤</span>
                                <p style="margin: 0; font-size: 0.8rem;">{{ __('Add a character or generate Story Bible') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- LOCATIONS TAB --}}
            @if($storyBibleTab === 'locations')
                <div style="display: flex; gap: 0.75rem; height: calc(100% - 40px);">
                    {{-- Locations List --}}
                    <div style="width: 200px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.75rem;">
                        <button type="button"
                                wire:click="addBibleLocation"
                                style="width: 100%; padding: 0.4rem; background: transparent; border: 2px dashed rgba(6,182,212,0.4); border-radius: 0.375rem; color: #67e8f9; font-size: 0.7rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                            <span>+</span> {{ __('Add Location') }}
                        </button>

                        <div style="display: flex; flex-direction: column; gap: 0.35rem; max-height: 350px; overflow-y: auto;">
                            @forelse($storyBible['locations'] ?? [] as $locIndex => $loc)
                                <div wire:click="editBibleLocation({{ $locIndex }})"
                                     style="padding: 0.5rem; background: {{ $editingBibleLocationIndex === $locIndex ? 'rgba(6,182,212,0.15)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $editingBibleLocationIndex === $locIndex ? 'rgba(6,182,212,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.375rem; cursor: pointer;">
                                    <div style="font-weight: 600; color: white; font-size: 0.75rem; margin-bottom: 0.15rem;">{{ $loc['name'] ?: __('Unnamed') }}</div>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <span style="background: rgba(6,182,212,0.2); color: #67e8f9; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem;">{{ ucfirst($loc['type'] ?? 'interior') }}</span>
                                        <span style="background: rgba(251,191,36,0.2); color: #fcd34d; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem;">{{ $loc['timeOfDay'] ?? 'day' }}</span>
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 1rem; color: rgba(255,255,255,0.4); font-size: 0.65rem; text-align: center;">
                                    {{ __('No locations defined yet') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Location Editor --}}
                    <div style="flex: 1; overflow-y: auto;">
                        @php
                            $locations = $storyBible['locations'] ?? [];
                            $currentLoc = $locations[$editingBibleLocationIndex] ?? null;
                        @endphp

                        @if($currentLoc)
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                {{-- Name --}}
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Location Name') }}</label>
                                    <input type="text"
                                           wire:model.blur="storyBible.locations.{{ $editingBibleLocationIndex }}.name"
                                           placeholder="{{ __('e.g., Corporate Office, Beach at Sunset...') }}"
                                           style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.8rem;">
                                </div>

                                {{-- Type & Time of Day --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Type') }}</label>
                                        <select wire:model.change="storyBible.locations.{{ $editingBibleLocationIndex }}.type"
                                                style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.7rem;">
                                            <option value="interior">{{ __('Interior') }}</option>
                                            <option value="exterior">{{ __('Exterior') }}</option>
                                            <option value="abstract">{{ __('Abstract') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Time of Day') }}</label>
                                        <select wire:model.change="storyBible.locations.{{ $editingBibleLocationIndex }}.timeOfDay"
                                                style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.7rem;">
                                            <option value="day">{{ __('Day') }}</option>
                                            <option value="night">{{ __('Night') }}</option>
                                            <option value="dawn">{{ __('Dawn') }}</option>
                                            <option value="dusk">{{ __('Dusk') }}</option>
                                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">{{ __('Atmosphere') }}</label>
                                        <input type="text"
                                               wire:model.blur="storyBible.locations.{{ $editingBibleLocationIndex }}.atmosphere"
                                               placeholder="{{ __('tense, peaceful...') }}"
                                               style="width: 100%; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.7rem;">
                                    </div>
                                </div>

                                {{-- Visual Description (CRITICAL for AI image generation) --}}
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.6rem; margin-bottom: 0.15rem;">
                                        {{ __('Visual Description') }}
                                        <span style="color: #06b6d4;">({{ __('Critical for AI consistency') }})</span>
                                    </label>
                                    <textarea wire:model.blur="storyBible.locations.{{ $editingBibleLocationIndex }}.description"
                                              placeholder="{{ __('Architecture, materials, colors, key objects, textures, lighting conditions...') }}"
                                              style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 100px; resize: vertical;"></textarea>
                                    <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.4); font-size: 0.55rem;">{{ __('Be specific - this description will be used in every image prompt set in this location') }}</p>
                                </div>

                                {{-- Delete Location --}}
                                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                    <button type="button"
                                            wire:click="removeBibleLocation({{ $editingBibleLocationIndex }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this location?') }}"
                                            style="padding: 0.3rem 0.6rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #f87171; font-size: 0.65rem; cursor: pointer;">
                                        {{ __('Delete Location') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: rgba(255,255,255,0.4);">
                                <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">🏛️</span>
                                <p style="margin: 0; font-size: 0.8rem;">{{ __('Add a location or generate Story Bible') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- VISUAL STYLE TAB --}}
            @if($storyBibleTab === 'style')
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    {{-- Visual Mode --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.35rem; text-transform: uppercase;">{{ __('Visual Mode') }}</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                            @foreach(['cinematic-realistic' => ['label' => 'Cinematic Realistic', 'desc' => 'Photorealistic, live-action'], 'stylized-animation' => ['label' => 'Stylized Animation', 'desc' => 'Animated, illustrated'], 'mixed-hybrid' => ['label' => 'Mixed Hybrid', 'desc' => 'Combines styles']] as $mode => $info)
                                <button type="button"
                                        wire:click="$set('storyBible.visualStyle.mode', '{{ $mode }}')"
                                        style="padding: 0.75rem; border-radius: 0.5rem; cursor: pointer; text-align: center; border: 2px solid {{ ($storyBible['visualStyle']['mode'] ?? 'cinematic-realistic') === $mode ? 'rgba(236,72,153,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ ($storyBible['visualStyle']['mode'] ?? 'cinematic-realistic') === $mode ? 'rgba(236,72,153,0.15)' : 'rgba(255,255,255,0.03)' }};">
                                    <div style="font-weight: 600; color: {{ ($storyBible['visualStyle']['mode'] ?? 'cinematic-realistic') === $mode ? '#f9a8d4' : 'white' }}; font-size: 0.8rem; margin-bottom: 0.25rem;">{{ $info['label'] }}</div>
                                    <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ $info['desc'] }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Color Palette & Lighting --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Color Palette') }}</label>
                            <textarea wire:model.blur="storyBible.visualStyle.colorPalette"
                                      placeholder="{{ __('e.g., Cool blues with warm amber accents, desaturated earth tones...') }}"
                                      style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 70px; resize: vertical;"></textarea>
                        </div>
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Lighting Style') }}</label>
                            <textarea wire:model.blur="storyBible.visualStyle.lighting"
                                      placeholder="{{ __('e.g., Natural window light with dramatic shadows, soft diffused studio lighting...') }}"
                                      style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 70px; resize: vertical;"></textarea>
                        </div>
                    </div>

                    {{-- Camera Language & References --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Camera Language') }}</label>
                            <textarea wire:model.blur="storyBible.visualStyle.cameraLanguage"
                                      placeholder="{{ __('e.g., Steady handheld, smooth dolly movements, intimate close-ups...') }}"
                                      style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 70px; resize: vertical;"></textarea>
                        </div>
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Visual References') }}</label>
                            <textarea wire:model.blur="storyBible.visualStyle.references"
                                      placeholder="{{ __('e.g., Blade Runner meets The Social Network, Pixar style with moody lighting...') }}"
                                      style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem; min-height: 70px; resize: vertical;"></textarea>
                        </div>
                    </div>

                    {{-- Pacing --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.35rem; text-transform: uppercase;">{{ __('Pacing') }}</label>
                        <div style="display: flex; gap: 0.5rem;">
                            @foreach(['fast' => 'Fast', 'balanced' => 'Balanced', 'contemplative' => 'Contemplative'] as $pace => $label)
                                <button type="button"
                                        wire:click="$set('storyBible.pacing.overall', '{{ $pace }}')"
                                        style="flex: 1; padding: 0.5rem; border-radius: 0.375rem; cursor: pointer; border: 1px solid {{ ($storyBible['pacing']['overall'] ?? 'balanced') === $pace ? 'rgba(16,185,129,0.5)' : 'rgba(255,255,255,0.15)' }}; background: {{ ($storyBible['pacing']['overall'] ?? 'balanced') === $pace ? 'rgba(16,185,129,0.15)' : 'rgba(255,255,255,0.03)' }}; color: {{ ($storyBible['pacing']['overall'] ?? 'balanced') === $pace ? '#6ee7b7' : 'rgba(255,255,255,0.7)' }}; font-size: 0.75rem;">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Emotional Beats --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Emotional Journey') }}</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            @forelse($storyBible['pacing']['emotionalBeats'] ?? [] as $beat)
                                <span style="padding: 0.25rem 0.5rem; background: rgba(236,72,153,0.2); border: 1px solid rgba(236,72,153,0.4); border-radius: 0.375rem; color: #f9a8d4; font-size: 0.7rem;">{{ $beat }}</span>
                            @empty
                                <span style="color: rgba(255,255,255,0.4); font-size: 0.7rem; font-style: italic;">{{ __('Generate Story Bible to define emotional journey') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- CINEMATOGRAPHY TAB --}}
            @if($storyBibleTab === 'cinematography')
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    {{-- Description --}}
                    <div style="padding: 0.75rem; background: rgba(236,72,153,0.1); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.5rem;">
                        <p style="color: rgba(255,255,255,0.8); font-size: 0.75rem; margin: 0;">
                            <strong style="color: #f9a8d4;">🎬 Cinematography Patterns</strong> control how scenes are decomposed into shots.
                            These professional filmmaking patterns ensure coherent storytelling through proper shot sequences, camera movements, and visual continuity.
                        </p>
                    </div>

                    {{-- Auto-Detect Toggle --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem;">
                        <div>
                            <div style="color: white; font-weight: 600; font-size: 0.85rem;">{{ __('Auto-Detect Patterns') }}</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">{{ __('AI automatically detects which patterns to use based on scene content') }}</div>
                        </div>
                        <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                            <input type="checkbox"
                                   wire:model.live="storyBible.cinematography.autoDetect"
                                   style="opacity: 0; width: 0; height: 0;">
                            <span style="position: absolute; cursor: pointer; inset: 0; background: {{ ($storyBible['cinematography']['autoDetect'] ?? true) ? 'linear-gradient(135deg, #ec4899, #f97316)' : 'rgba(255,255,255,0.2)' }}; border-radius: 24px; transition: 0.3s;"></span>
                            <span style="position: absolute; left: {{ ($storyBible['cinematography']['autoDetect'] ?? true) ? '22px' : '2px' }}; top: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: 0.3s;"></span>
                        </label>
                    </div>

                    {{-- Global Rules --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Global Cinematography Rules') }}</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                            @php
                                $globalRules = $storyBible['cinematography']['globalRules'] ?? [];
                            @endphp
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.375rem; cursor: pointer;">
                                <input type="checkbox" wire:model.live="storyBible.cinematography.globalRules.enforce180Rule"
                                       {{ ($globalRules['enforce180Rule'] ?? true) ? 'checked' : '' }}
                                       style="accent-color: #ec4899;">
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">{{ __('Enforce 180° Rule') }}</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.375rem; cursor: pointer;">
                                <input type="checkbox" wire:model.live="storyBible.cinematography.globalRules.enforceEyeline"
                                       {{ ($globalRules['enforceEyeline'] ?? true) ? 'checked' : '' }}
                                       style="accent-color: #ec4899;">
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">{{ __('Maintain Eyeline Match') }}</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.375rem; cursor: pointer;">
                                <input type="checkbox" wire:model.live="storyBible.cinematography.globalRules.enforceMatchCuts"
                                       {{ ($globalRules['enforceMatchCuts'] ?? true) ? 'checked' : '' }}
                                       style="accent-color: #ec4899;">
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">{{ __('Match Action Across Cuts') }}</span>
                            </label>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.375rem;">
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">{{ __('Min Shot Variety:') }}</span>
                                <input type="number" wire:model.blur="storyBible.cinematography.globalRules.minShotVariety"
                                       min="2" max="6" value="{{ $globalRules['minShotVariety'] ?? 3 }}"
                                       style="width: 50px; padding: 0.25rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.25rem; color: white; font-size: 0.75rem; text-align: center;">
                            </div>
                        </div>
                    </div>

                    {{-- Dialogue Settings --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Dialogue Pattern Settings') }}</label>
                        <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.75rem;">{{ __('Default Pattern:') }}</span>
                                <select wire:model.live="storyBible.cinematography.dialogueSettings.defaultPattern"
                                        style="flex: 1; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                                    <option value="shot_reverse_shot">{{ __('Shot/Reverse Shot (Standard)') }}</option>
                                    <option value="over_shoulder">{{ __('Over-the-Shoulder') }}</option>
                                    <option value="two_shot">{{ __('Two-Shot Coverage') }}</option>
                                    <option value="interview_coverage">{{ __('Interview Style') }}</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" wire:model.live="storyBible.cinematography.dialogueSettings.insertReactions"
                                           style="accent-color: #ec4899;">
                                    <span style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">{{ __('Auto-insert reaction shots') }}</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" wire:model.live="storyBible.cinematography.dialogueSettings.matchEyelines"
                                           style="accent-color: #ec4899;">
                                    <span style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">{{ __('Match character eyelines') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Action Settings --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Action Pattern Settings') }}</label>
                        <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.75rem;">{{ __('Default Pattern:') }}</span>
                                <select wire:model.live="storyBible.cinematography.actionSettings.defaultPattern"
                                        style="flex: 1; padding: 0.4rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.375rem; color: white; font-size: 0.75rem;">
                                    <option value="action_reaction">{{ __('Action/Reaction (Standard)') }}</option>
                                    <option value="object_reveal">{{ __('Object Reveal (Amulet Pattern)') }}</option>
                                    <option value="chase_sequence">{{ __('Chase Sequence') }}</option>
                                    <option value="fight_coverage">{{ __('Fight Coverage') }}</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" wire:model.live="storyBible.cinematography.actionSettings.useInsertShots"
                                           style="accent-color: #ec4899;">
                                    <span style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">{{ __('Use insert shots for objects') }}</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" wire:model.live="storyBible.cinematography.actionSettings.matchAction"
                                           style="accent-color: #ec4899;">
                                    <span style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">{{ __('Match action across cuts') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Available Patterns Preview --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Available Cinematic Patterns') }} <span style="color: rgba(255,255,255,0.4);">(33 patterns)</span></label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; max-height: 200px; overflow-y: auto; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 0.5rem;">
                            @php
                                $patternCategories = [
                                    'Dialogue' => ['shot_reverse_shot', 'over_shoulder', 'two_shot', 'interview_coverage'],
                                    'Action' => ['action_reaction', 'object_reveal', 'chase_sequence', 'fight_coverage'],
                                    'Suspense' => ['bomb_under_table', 'ticking_clock', 'creeping_reveal', 'stalker_pov'],
                                    'Reveals' => ['pov_discovery', 'camera_reveal', 'scale_reveal', 'magic_reveal'],
                                    'Emotional' => ['emotional_intimacy', 'lingering_shot', 'reflective_moment', 'isolated_character'],
                                    'Transitions' => ['match_cut', 'flashback', 'parallel_editing', 'time_compression_montage'],
                                ];
                            @endphp
                            @foreach($patternCategories as $category => $patterns)
                                <div style="padding: 0.5rem; background: rgba(255,255,255,0.03); border-radius: 0.375rem;">
                                    <div style="font-weight: 600; color: #f9a8d4; font-size: 0.7rem; margin-bottom: 0.35rem;">{{ $category }}</div>
                                    @foreach($patterns as $pattern)
                                        <div style="color: rgba(255,255,255,0.6); font-size: 0.65rem; padding: 0.15rem 0;">• {{ ucwords(str_replace('_', ' ', $pattern)) }}</div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                @if($storyBible['status'] === 'ready')
                    <span style="color: #10b981; font-size: 0.7rem;">
                        ✓ {{ count($storyBible['characters'] ?? []) }} {{ __('characters') }},
                        {{ count($storyBible['locations'] ?? []) }} {{ __('locations') }}
                    </span>
                @else
                    <span style="color: rgba(255,255,255,0.4); font-size: 0.65rem; font-style: italic;">
                        {{ __('All tabs (Characters, Locations, Style) are generated together as one Story Bible') }}
                    </span>
                @endif

                @if($storyBible['status'] === 'ready')
                    <button type="button"
                            wire:click="resetStoryBible"
                            wire:confirm="{{ __('This will clear the Story Bible. Continue?') }}"
                            style="padding: 0.3rem 0.6rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #f87171; font-size: 0.65rem; cursor: pointer;">
                        {{ __('Reset') }}
                    </button>
                @endif
            </div>

            <button type="button"
                    wire:click="closeStoryBibleModal"
                    style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #f59e0b, #ec4899); border: none; border-radius: 0.375rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                {{ __('Done') }}
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }
</style>
@endif
