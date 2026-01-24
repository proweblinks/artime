{{-- Location Bible Modal --}}
@if($showLocationBibleModal ?? false)
<div class="vw-modal-overlay"
     wire:key="location-bible-modal-{{ $editLocationIndex ?? 'main' }}"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 920px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">📍 {{ __('Location Bible') }}</h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Define locations for consistent environments across scenes') }}</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                @if(!empty($storyBible['locations']) && $storyBible['status'] === 'ready')
                    <button type="button"
                            wire:click="syncStoryBibleToLocationBible"
                            style="padding: 0.3rem 0.6rem; background: linear-gradient(135deg, #f59e0b, #ec4899); border: none; border-radius: 0.35rem; color: white; font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        🔄 {{ __('Sync from Story Bible') }}
                        <span style="background: rgba(255,255,255,0.2); padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.55rem;">{{ count($storyBible['locations']) }}</span>
                    </button>
                @endif
                <button type="button" wire:click="closeLocationBibleModal" style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
            </div>
        </div>

        {{-- Story Bible sync indicator with loading state --}}
        @if($isSyncingLocationBible ?? false)
            <div style="padding: 0.5rem 1rem; background: rgba(245,158,11,0.15); border-bottom: 1px solid rgba(245,158,11,0.3); color: #fcd34d; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem;">
                <div style="width: 14px; height: 14px; border: 2px solid rgba(245,158,11,0.3); border-top-color: #f59e0b; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                <span>{{ __('Syncing locations from Story Bible...') }}</span>
            </div>
        @elseif(!empty($storyBible['locations']) && $storyBible['status'] === 'ready')
            <div style="padding: 0.35rem 1rem; background: rgba(16,185,129,0.1); border-bottom: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; font-size: 0.65rem; display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #10b981;">✓</span>
                📖 {{ __('Synced') }} {{ count($storyBible['locations']) }} {{ __('locations from Story Bible') }}
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
            {{-- Locations List (Left Panel) --}}
            <div style="width: 200px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.75rem; overflow-y: auto;">
                {{-- Add Location Button --}}
                <button type="button"
                        wire:click="addLocation"
                        style="width: 100%; padding: 0.4rem; background: rgba(245, 158, 11, 0.2); border: 1px dashed rgba(245, 158, 11, 0.5); border-radius: 0.375rem; color: #f59e0b; font-size: 0.7rem; cursor: pointer; margin-bottom: 0.35rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <span style="font-size: 1rem;">+</span> {{ __('Add Location') }}
                </button>

                {{-- Auto-detect Button --}}
                @if(count($script['scenes'] ?? []) > 0)
                <button type="button"
                        wire:click="autoDetectLocations"
                        wire:loading.attr="disabled"
                        wire:target="autoDetectLocations"
                        style="width: 100%; padding: 0.35rem; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.375rem; color: #a78bfa; font-size: 0.6rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <span wire:loading.remove wire:target="autoDetectLocations">🔍 {{ __('Auto-detect from Script') }}</span>
                    <span wire:loading wire:target="autoDetectLocations">{{ __('Detecting...') }}</span>
                </button>
                @endif

                {{-- Generate All Missing References Button --}}
                @php
                    $locationsNeedingRefs = collect($sceneMemory['locationBible']['locations'] ?? [])->filter(function($loc) {
                        return empty($loc['referenceImageBase64']) || ($loc['referenceImageStatus'] ?? '') !== 'ready';
                    })->count();
                @endphp
                @if($locationsNeedingRefs > 0)
                <button type="button"
                        wire:click="generateAllMissingLocationReferences"
                        wire:loading.attr="disabled"
                        wire:target="generateAllMissingLocationReferences"
                        style="width: 100%; padding: 0.35rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.375rem; color: #10b981; font-size: 0.6rem; cursor: pointer; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <span wire:loading.remove wire:target="generateAllMissingLocationReferences">✨ {{ __('Generate All References') }} ({{ $locationsNeedingRefs }})</span>
                    <span wire:loading wire:target="generateAllMissingLocationReferences">{{ __('Generating...') }}</span>
                </button>
                @endif

                {{-- Location Items List --}}
                @if(count($sceneMemory['locationBible']['locations'] ?? []) === 0)
                    <div style="text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem; padding: 1rem;">
                        @if(count($script['scenes'] ?? []) > 0)
                            {{ __('No locations yet.') }}<br>{{ __('Click "Auto-detect" to analyze your script.') }}
                        @else
                            {{ __('No locations yet.') }}<br>{{ __('Add one to get started.') }}
                        @endif
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach($sceneMemory['locationBible']['locations'] ?? [] as $index => $location)
                            <div wire:click="editLocation({{ $index }})"
                                 style="padding: 0.5rem; background: {{ $editingLocationIndex === $index ? 'rgba(245, 158, 11, 0.2)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $editingLocationIndex === $index ? 'rgba(245, 158, 11, 0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                {{-- Location Thumbnail --}}
                                <div style="width: 50px; height: 35px; border-radius: 0.35rem; overflow: hidden; flex-shrink: 0; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                                    @if(!empty($location['referenceImage']))
                                        <img src="{{ $location['referenceImage'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @elseif(($location['referenceImageStatus'] ?? '') === 'generating')
                                        <div style="width: 0.8rem; height: 0.8rem; border: 2px solid rgba(245,158,11,0.3); border-top-color: #f59e0b; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                                    @else
                                        <span style="font-size: 1rem; color: rgba(255,255,255,0.3);">📍</span>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="color: white; font-size: 0.7rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $location['name'] ?? __('Unnamed') }}</div>
                                    <div style="color: rgba(255,255,255,0.4); font-size: 0.55rem;">{{ ucfirst($location['type'] ?? 'exterior') }} · {{ ucfirst($location['timeOfDay'] ?? 'day') }}</div>
                                    @if(!empty($location['referenceImage']))
                                        <div style="color: #10b981; font-size: 0.5rem;">✓ {{ __('Reference') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Location Editor (Right Panel) --}}
            <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem; overflow-y: auto;">
                @if(count($sceneMemory['locationBible']['locations'] ?? []) > 0)
                    @php
                        $editIndex = $editingLocationIndex ?? 0;
                        $currentLocation = $sceneMemory['locationBible']['locations'][$editIndex] ?? null;
                    @endphp

                    @if($currentLocation)
                        {{-- Top Section: Reference Image + Basic Info --}}
                        <div style="display: flex; gap: 0.75rem;">
                            {{-- Reference Image Preview --}}
                            <div style="width: 170px; flex-shrink: 0;">
                                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.65rem; margin-bottom: 0.25rem;">{{ __('Reference Image') }}</label>
                                <div style="width: 170px; height: 100px; background: rgba(0,0,0,0.3); border: 1px dashed rgba(139,92,246,0.3); border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                    @if(!empty($currentLocation['referenceImage']))
                                        <img src="{{ $currentLocation['referenceImage'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        <button type="button"
                                                wire:click="removeLocationReference({{ $editIndex }})"
                                                style="position: absolute; top: 0.25rem; right: 0.25rem; width: 20px; height: 20px; background: rgba(239,68,68,0.9); border: none; border-radius: 50%; color: white; font-size: 0.6rem; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                                                title="{{ __('Remove') }}">✕</button>
                                    @else
                                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                                            <div style="font-size: 1.5rem; margin-bottom: 0.2rem;">🏞️</div>
                                            <div style="font-size: 0.6rem;">{{ __('No reference') }}</div>
                                        </div>
                                    @endif
                                </div>
                                <button type="button"
                                        wire:click="generateLocationReference({{ $editIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateLocationReference"
                                        {{ $isGeneratingLocationRef ? 'disabled' : '' }}
                                        style="width: 100%; margin-top: 0.35rem; padding: 0.35rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.25rem; color: white; font-size: 0.65rem; cursor: pointer; {{ $isGeneratingLocationRef ? 'opacity: 0.5;' : '' }}">
                                    <span wire:loading.remove wire:target="generateLocationReference">🎨 {{ __('Generate Reference') }}</span>
                                    <span wire:loading wire:target="generateLocationReference">{{ __('Generating...') }}</span>
                                </button>

                                {{-- Upload Button & Input --}}
                                <div x-data="{ uploading: false }" style="position: relative; margin-top: 0.25rem;">
                                    <input type="file"
                                           wire:model="locationImageUpload"
                                           accept="image/*"
                                           x-on:livewire-upload-start="uploading = true"
                                           x-on:livewire-upload-finish="uploading = false; $wire.uploadLocationReference({{ $editIndex }})"
                                           x-on:livewire-upload-error="uploading = false"
                                           style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 1;">
                                    <button type="button"
                                            style="width: 100%; padding: 0.35rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.25rem; color: rgba(255,255,255,0.8); font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                                        <template x-if="!uploading">
                                            <span>📤 {{ __('Upload Image') }}</span>
                                        </template>
                                        <template x-if="uploading">
                                            <span>{{ __('Uploading...') }}</span>
                                        </template>
                                    </button>
                                </div>

                                {{-- Source indicator --}}
                                @if(!empty($currentLocation['referenceImage']) && !empty($currentLocation['referenceImageSource']))
                                    <div style="text-align: center; margin-top: 0.15rem; font-size: 0.5rem; color: rgba(255,255,255,0.4);">
                                        {{ $currentLocation['referenceImageSource'] === 'upload' ? __('Uploaded') : __('AI Generated') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Basic Info --}}
                            <div style="flex: 1;">
                                {{-- Location Name --}}
                                <div style="margin-bottom: 0.4rem;">
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.65rem; margin-bottom: 0.15rem;">{{ __('Location Name') }}</label>
                                    <input type="text"
                                           wire:model.blur="sceneMemory.locationBible.locations.{{ $editIndex }}.name"
                                           placeholder="{{ __('e.g., Downtown Office, Forest Clearing...') }}"
                                           style="width: 100%; padding: 0.35rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.3rem; color: white; font-size: 0.8rem;">
                                </div>

                                {{-- Quick Templates --}}
                                <div style="margin-bottom: 0.4rem;">
                                    <div style="font-size: 0.55rem; color: rgba(255,255,255,0.5); margin-bottom: 0.15rem;">{{ __('Quick Templates') }}</div>
                                    <div style="display: flex; gap: 0.2rem; flex-wrap: wrap;">
                                        <button type="button" wire:click="applyLocationTemplate('urban-night')" style="padding: 0.15rem 0.3rem; background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.4); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🌃 {{ __('Urban Night') }}</button>
                                        <button type="button" wire:click="applyLocationTemplate('forest')" style="padding: 0.15rem 0.3rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🌲 {{ __('Forest') }}</button>
                                        <button type="button" wire:click="applyLocationTemplate('tech-lab')" style="padding: 0.15rem 0.3rem; background: rgba(6, 182, 212, 0.2); border: 1px solid rgba(6, 182, 212, 0.4); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🔬 {{ __('Tech Lab') }}</button>
                                        <button type="button" wire:click="applyLocationTemplate('desert')" style="padding: 0.15rem 0.3rem; background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🏜️ {{ __('Desert') }}</button>
                                        <button type="button" wire:click="applyLocationTemplate('industrial')" style="padding: 0.15rem 0.3rem; background: rgba(107, 114, 128, 0.3); border: 1px solid rgba(107, 114, 128, 0.5); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🏭 {{ __('Industrial') }}</button>
                                        <button type="button" wire:click="applyLocationTemplate('space')" style="padding: 0.15rem 0.3rem; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); border-radius: 0.15rem; color: white; font-size: 0.5rem; cursor: pointer;">🚀 {{ __('Space') }}</button>
                                    </div>
                                </div>

                                {{-- Type, Time, Weather --}}
                                <div style="display: flex; gap: 0.35rem; margin-bottom: 0.4rem;">
                                    <div style="flex: 1;">
                                        <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Type') }}</label>
                                        <select wire:model.change="sceneMemory.locationBible.locations.{{ $editIndex }}.type"
                                                style="width: 100%; padding: 0.3rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                                            <option value="exterior">{{ __('Exterior') }}</option>
                                            <option value="interior">{{ __('Interior') }}</option>
                                            <option value="abstract">{{ __('Abstract') }}</option>
                                        </select>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Time of Day') }}</label>
                                        <select wire:model.change="sceneMemory.locationBible.locations.{{ $editIndex }}.timeOfDay"
                                                style="width: 100%; padding: 0.3rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                                            <option value="day">{{ __('Day') }}</option>
                                            <option value="night">{{ __('Night') }}</option>
                                            <option value="dawn">{{ __('Dawn') }}</option>
                                            <option value="dusk">{{ __('Dusk') }}</option>
                                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                                        </select>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Weather') }}</label>
                                        <select wire:model.change="sceneMemory.locationBible.locations.{{ $editIndex }}.weather"
                                                style="width: 100%; padding: 0.3rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                                            <option value="clear">{{ __('Clear') }}</option>
                                            <option value="cloudy">{{ __('Cloudy') }}</option>
                                            <option value="rainy">{{ __('Rainy') }}</option>
                                            <option value="foggy">{{ __('Foggy') }}</option>
                                            <option value="stormy">{{ __('Stormy') }}</option>
                                            <option value="snowy">{{ __('Snowy') }}</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Mood / Atmosphere --}}
                                <div style="margin-bottom: 0.4rem;">
                                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Mood / Atmosphere') }}</label>
                                    <select wire:model.change="sceneMemory.locationBible.locations.{{ $editIndex }}.mood"
                                            style="width: 100%; padding: 0.3rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                                        <option value="neutral">{{ __('Neutral') }}</option>
                                        <option value="tense">{{ __('Tense / Dramatic') }}</option>
                                        <option value="peaceful">{{ __('Peaceful / Serene') }}</option>
                                        <option value="mysterious">{{ __('Mysterious / Eerie') }}</option>
                                        <option value="energetic">{{ __('Energetic / Dynamic') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div style="margin-bottom: 0.4rem;">
                            <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Visual Description') }}</label>
                            <textarea wire:model.blur="sceneMemory.locationBible.locations.{{ $editIndex }}.description"
                                      placeholder="{{ __('Describe the environment in detail: architecture, colors, textures, key elements...') }}"
                                      style="width: 100%; padding: 0.35rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.3rem; color: white; font-size: 0.7rem; min-height: 45px; resize: none;"></textarea>
                        </div>

                        {{-- Lighting Style --}}
                        <div style="margin-bottom: 0.4rem;">
                            <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.55rem; margin-bottom: 0.1rem;">{{ __('Lighting Style (optional)') }}</label>
                            <input type="text"
                                   wire:model.blur="sceneMemory.locationBible.locations.{{ $editIndex }}.lightingStyle"
                                   placeholder="{{ __('e.g., Neon signs with wet surface reflections, dramatic rim lighting') }}"
                                   style="width: 100%; padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.25rem; color: white; font-size: 0.65rem;">
                        </div>

                        {{-- Scene Assignment --}}
                        <div style="margin-bottom: 0.35rem;">
                            @php
                                $assignedScenes = $currentLocation['scenes'] ?? [];
                                $assignedScenesCount = count($assignedScenes);
                                $totalScenes = count($script['scenes'] ?? []);
                                $allScenesAssigned = $assignedScenesCount === $totalScenes && $totalScenes > 0;
                            @endphp
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ __('Used in Scenes') }}</label>
                                <span style="font-size: 0.55rem; color: {{ $assignedScenesCount > 0 ? '#f59e0b' : 'rgba(255,255,255,0.4)' }};">
                                    {{ $assignedScenesCount }}/{{ $totalScenes }} {{ __('scenes') }}
                                </span>
                            </div>
                            @if($assignedScenesCount === 0 && $totalScenes > 0)
                                <div style="padding: 0.35rem 0.5rem; margin-bottom: 0.25rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.25rem;">
                                    <span style="color: #fca5a5; font-size: 0.55rem;">⚠️ {{ __('Please assign at least one scene to this location') }}</span>
                                </div>
                            @endif
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; align-items: center;">
                                @foreach($script['scenes'] ?? [] as $sceneIdx => $scene)
                                    @php
                                        $isAssigned = in_array($sceneIdx, $assignedScenes);
                                    @endphp
                                    <button type="button"
                                            wire:click.debounce.300ms="toggleLocationScene({{ $editIndex }}, {{ $sceneIdx }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleLocationScene"
                                            style="width: 28px; height: 28px; border-radius: 0.3rem; border: 2px solid {{ $isAssigned ? '#f59e0b' : 'rgba(255,255,255,0.2)' }}; background: {{ $isAssigned ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'rgba(255,255,255,0.05)' }}; color: {{ $isAssigned ? 'white' : 'rgba(255,255,255,0.5)' }}; cursor: pointer; font-size: 0.7rem; font-weight: {{ $isAssigned ? '700' : '500' }}; transition: all 0.15s ease; {{ $isAssigned ? 'box-shadow: 0 2px 8px rgba(245,158,11,0.4);' : '' }}">
                                        {{ $sceneIdx + 1 }}
                                    </button>
                                @endforeach
                                @if($totalScenes > 0)
                                    <button type="button"
                                            wire:click.debounce.300ms="applyLocationToAllScenes({{ $editIndex }})"
                                            wire:loading.attr="disabled"
                                            wire:target="applyLocationToAllScenes"
                                            style="padding: 0.25rem 0.6rem; border-radius: 0.3rem; border: 2px solid {{ $allScenesAssigned ? '#10b981' : 'rgba(16, 185, 129, 0.4)' }}; background: {{ $allScenesAssigned ? 'linear-gradient(135deg, #10b981, #059669)' : 'rgba(16, 185, 129, 0.15)' }}; color: {{ $allScenesAssigned ? 'white' : '#6ee7b7' }}; cursor: pointer; font-size: 0.6rem; font-weight: 600; margin-left: 0.25rem; {{ $allScenesAssigned ? 'box-shadow: 0 2px 8px rgba(16,185,129,0.3);' : '' }}">
                                        {{ __('All') }}
                                    </button>
                                @endif
                            </div>
                            @if(empty($script['scenes']))
                                <div style="color: rgba(255,255,255,0.4); font-size: 0.55rem; padding: 0.35rem;">
                                    {{ __('No scenes available yet') }}
                                </div>
                            @endif
                        </div>

                        {{-- State History (Expandable) --}}
                        @php
                            $assignedScenes = $currentLocation['scenes'] ?? [];
                            $hasAssignedScenes = count($assignedScenes) >= 2;
                        @endphp
                        <div x-data="{ stateOpen: false, newStateScene: '', newStateText: '' }" style="margin-top: 0.5rem;">
                            <button type="button"
                                    @click="stateOpen = !stateOpen"
                                    style="display: flex; align-items: center; gap: 0.25rem; width: 100%; background: none; border: none; padding: 0; cursor: pointer; margin-bottom: 0.25rem;">
                                <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem; transition: transform 0.2s;" :style="stateOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.65rem;">{{ __('State History') }}</span>
                                <span style="color: rgba(255,255,255,0.4); font-size: 0.5rem; margin-left: 0.2rem;">({{ count($currentLocation['stateChanges'] ?? []) }})</span>
                            </button>

                            <div x-show="stateOpen" x-collapse>
                                <p style="color: rgba(255,255,255,0.5); font-size: 0.55rem; margin: 0 0 0.35rem 0;">
                                    {{ __('Track how this location changes across scenes (e.g., pristine → damaged)') }}
                                </p>

                                {{-- Current State Changes --}}
                                <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.5rem;">
                                    @forelse($currentLocation['stateChanges'] ?? [] as $stateIdx => $stateChange)
                                        @php
                                            // Support both new (sceneIndex/stateDescription) and old (scene/state) field names
                                            $stateSceneIdx = $stateChange['sceneIndex'] ?? $stateChange['scene'] ?? 0;
                                            $stateDesc = $stateChange['stateDescription'] ?? $stateChange['state'] ?? '';
                                        @endphp
                                        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius: 0.35rem;">
                                            <span style="background: rgba(245,158,11,0.3); color: #fcd34d; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600;">
                                                S{{ $stateSceneIdx + 1 }}
                                            </span>
                                            <span style="flex: 1; color: rgba(255,255,255,0.8); font-size: 0.7rem;">
                                                {{ $stateDesc }}
                                            </span>
                                            <button type="button"
                                                    wire:click="removeLocationState({{ $editIndex }}, {{ $stateIdx }})"
                                                    style="background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; padding: 0; line-height: 1; font-size: 0.75rem;"
                                                    title="{{ __('Remove') }}">&times;</button>
                                        </div>
                                    @empty
                                        <div style="color: rgba(255,255,255,0.4); font-size: 0.65rem; font-style: italic; padding: 0.25rem 0;">
                                            {{ __('No state changes defined yet') }}
                                        </div>
                                    @endforelse
                                </div>

                                {{-- Add New State Change --}}
                                @if($hasAssignedScenes)
                                    <div style="display: flex; gap: 0.35rem; margin-bottom: 0.5rem;">
                                        <select x-model="newStateScene"
                                                style="width: 70px; padding: 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.7rem;">
                                            <option value="">{{ __('Scene') }}</option>
                                            @foreach($assignedScenes as $sceneIdx)
                                                <option value="{{ $sceneIdx }}">S{{ $sceneIdx + 1 }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text"
                                               x-model="newStateText"
                                               @keydown.enter.prevent="if(newStateScene !== '' && newStateText.trim()) { $wire.addLocationState({{ $editIndex }}, parseInt(newStateScene), newStateText.trim()); newStateScene = ''; newStateText = ''; }"
                                               placeholder="{{ __('State description (e.g., damaged, foggy)') }}"
                                               style="flex: 1; padding: 0.35rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.7rem;">
                                        <button type="button"
                                                @click="if(newStateScene !== '' && newStateText.trim()) { $wire.addLocationState({{ $editIndex }}, parseInt(newStateScene), newStateText.trim()); newStateScene = ''; newStateText = ''; }"
                                                style="padding: 0.35rem 0.5rem; background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.4); border-radius: 0.35rem; color: #fcd34d; font-size: 0.65rem; cursor: pointer;">
                                            + {{ __('Add') }}
                                        </button>
                                    </div>

                                    {{-- State Presets --}}
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <span style="color: rgba(255,255,255,0.4); font-size: 0.6rem; margin-right: 0.25rem;">{{ __('Presets:') }}</span>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'destruction')" style="padding: 0.2rem 0.4rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.25rem; color: #fca5a5; font-size: 0.55rem; cursor: pointer;">💥 {{ __('Destruction') }}</button>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'time-of-day')" style="padding: 0.2rem 0.4rem; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); border-radius: 0.25rem; color: #fcd34d; font-size: 0.55rem; cursor: pointer;">🌅 {{ __('Time') }}</button>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'weather-change')" style="padding: 0.2rem 0.4rem; background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.2); border-radius: 0.25rem; color: #67e8f9; font-size: 0.55rem; cursor: pointer;">🌧️ {{ __('Weather') }}</button>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'abandonment')" style="padding: 0.2rem 0.4rem; background: rgba(107,114,128,0.2); border: 1px solid rgba(107,114,128,0.3); border-radius: 0.25rem; color: #9ca3af; font-size: 0.55rem; cursor: pointer;">🏚️ {{ __('Abandon') }}</button>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'transformation')" style="padding: 0.2rem 0.4rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.55rem; cursor: pointer;">✨ {{ __('Transform') }}</button>
                                        <button type="button" wire:click="applyLocationStatePreset({{ $editIndex }}, 'tension')" style="padding: 0.2rem 0.4rem; background: rgba(220,38,38,0.1); border: 1px solid rgba(220,38,38,0.2); border-radius: 0.25rem; color: #fca5a5; font-size: 0.55rem; cursor: pointer;">⚡ {{ __('Tension') }}</button>
                                    </div>
                                @else
                                    <div style="color: rgba(255,255,255,0.4); font-size: 0.65rem; font-style: italic;">
                                        {{ __('Assign at least 2 scenes to add state changes') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Delete Location Button --}}
                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <button type="button"
                                    wire:click="removeLocation({{ $editIndex }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this location?') }}"
                                    style="padding: 0.3rem 0.6rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.3rem; color: #ef4444; font-size: 0.65rem; cursor: pointer;">
                                🗑️ {{ __('Delete Location') }}
                            </button>
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">📍</span>
                        <p style="font-size: 0.85rem; margin-bottom: 0.35rem;">{{ __('No locations defined') }}</p>
                        <p style="font-size: 0.7rem;">{{ __('Add a location or auto-detect from your script') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div style="font-size: 0.6rem; color: rgba(255,255,255,0.5);">
                @if($sceneMemory['locationBible']['enabled'] ?? false)
                    ✓ {{ __('Location Bible is active') }}
                @else
                    ⚠ {{ __('Enable toggle to apply') }}
                @endif
            </div>
            <div style="display: flex; gap: 0.4rem; align-items: center;">
                <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer; font-size: 0.65rem; color: rgba(255,255,255,0.7);">
                    <input type="checkbox" wire:model.live="sceneMemory.locationBible.enabled" style="accent-color: #f59e0b;">
                    {{ __('Enable') }}
                </label>
                <button type="button"
                        wire:click="closeLocationBibleModal"
                        style="padding: 0.4rem 0.8rem; background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 0.35rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.75rem;">
                    {{ __('Done') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for batch location reference generation polling --}}
<script>
(function() {
    // Prevent duplicate listener registration
    if (window.locBiblePollingInitialized) {
        console.log('[LocationBible] Polling already initialized, skipping');
        return;
    }
    window.locBiblePollingInitialized = true;

    // Location Bible batch generation polling
    let locGenPollingActive = false;
    let locGenRetryCount = 0;
    const locGenMaxRetries = 50;

    console.log('[LocationBible] Setting up Livewire event listener for continue-location-reference-generation');

    Livewire.on('continue-location-reference-generation', function(data) {
        console.log('[LocationBible] Received continue-location-reference-generation event', data);
        if (!locGenPollingActive) {
            locGenPollingActive = true;
            locGenRetryCount = 0;
            pollNextLocationReference();
        }
    });

    function pollNextLocationReference() {
        if (locGenRetryCount >= locGenMaxRetries) {
            console.log('[LocationBible] Max retries reached, stopping polling');
            locGenPollingActive = false;
            return;
        }

        locGenRetryCount++;
        console.log('[LocationBible] Polling for next reference, attempt', locGenRetryCount);

        // Use setTimeout to give Livewire time to update state
        setTimeout(function() {
            // Find the Livewire component
            const wireEl = Array.from(document.querySelectorAll('*')).find(function(el) {
                return el.hasAttribute('wire:id');
            });
            const wireId = wireEl ? wireEl.getAttribute('wire:id') : null;
            const component = wireId ? Livewire.find(wireId) : null;

            if (component && component.$wire) {
                component.$wire.generateNextPendingLocationReference().then(function(result) {
                    console.log('[LocationBible] generateNextPendingLocationReference result:', result);
                    if (result && result.remaining > 0) {
                        // More references to generate, continue polling
                        console.log('[LocationBible] ' + result.remaining + ' references remaining');
                        setTimeout(pollNextLocationReference, 1500);
                    } else {
                        console.log('[LocationBible] All references generated, stopping polling');
                        locGenPollingActive = false;
                    }
                }).catch(function(error) {
                    console.error('[LocationBible] Error generating reference:', error);
                    locGenPollingActive = false;
                });
            } else {
                console.warn('[LocationBible] Livewire component not found');
                locGenPollingActive = false;
            }
        }, 500);
    }
})();
</script>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
