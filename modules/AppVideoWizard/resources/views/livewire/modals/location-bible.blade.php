{{-- Location Bible Modal --}}
<div x-data="{ isOpen: false, editingIndex: 0 }"
     @open-location-bible-modal.window="isOpen = true"
     @close-location-bible-modal.window="isOpen = false"
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
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">📍 {{ __('Location Bible') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Define locations for consistent environments') }}</p>
            </div>
            <button type="button" @click="isOpen = false" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; gap: 1.25rem;">
            {{-- Locations List --}}
            <div style="width: 220px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 1.25rem;">
                <button type="button"
                        wire:click="addLocation"
                        style="width: 100%; padding: 0.6rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; color: #c4b5fd; font-size: 0.8rem; cursor: pointer; margin-bottom: 0.75rem;">
                    + {{ __('Add Location') }}
                </button>
                <button type="button"
                        wire:click="autoDetectLocations"
                        style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                    🔍 {{ __('Auto-detect from Script') }}
                </button>

                {{-- Quick Templates --}}
                <div style="margin-bottom: 1rem;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Quick Templates') }}</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                        <button type="button" wire:click="applyLocationTemplate('urban')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🏙️ {{ __('Urban') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('forest')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🌲 {{ __('Forest') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('office')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🏢 {{ __('Office') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('studio')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🎬 {{ __('Studio') }}</button>
                    </div>
                </div>

                {{-- Location Items --}}
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse($sceneMemory['locationBible']['locations'] ?? [] as $index => $location)
                        <div wire:click="editLocation({{ $index }})"
                             style="padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
                             :style="editingIndex === {{ $index }} ? 'border-color: #8b5cf6; background: rgba(139,92,246,0.1)' : ''">
                            <div style="font-weight: 600; color: white; font-size: 0.85rem;">{{ $location['name'] ?? __('Unnamed') }}</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 0.25rem;">
                                {{ $location['type'] ?? 'exterior' }} • {{ $location['timeOfDay'] ?? 'day' }}
                            </div>
                        </div>
                    @empty
                        <div style="padding: 1rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; text-align: center;">
                            {{ __('No locations defined yet') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Location Editor --}}
            <div style="flex: 1;">
                @if(count($sceneMemory['locationBible']['locations'] ?? []) > 0)
                    @php $editIndex = 0; @endphp
                    {{-- Location Name --}}
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Location Name') }}</label>
                        <input type="text"
                               wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.name"
                               placeholder="{{ __('e.g., Downtown Office, Forest Clearing...') }}"
                               style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem;">
                    </div>

                    {{-- Type & Time --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Type') }}</label>
                            <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.type"
                                    style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.8rem;">
                                <option value="exterior">{{ __('Exterior') }}</option>
                                <option value="interior">{{ __('Interior') }}</option>
                                <option value="abstract">{{ __('Abstract') }}</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Time of Day') }}</label>
                            <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.timeOfDay"
                                    style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.8rem;">
                                <option value="day">{{ __('Day') }}</option>
                                <option value="night">{{ __('Night') }}</option>
                                <option value="dawn">{{ __('Dawn') }}</option>
                                <option value="dusk">{{ __('Dusk') }}</option>
                                <option value="golden-hour">{{ __('Golden Hour') }}</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Weather') }}</label>
                            <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.weather"
                                    style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.8rem;">
                                <option value="clear">{{ __('Clear') }}</option>
                                <option value="cloudy">{{ __('Cloudy') }}</option>
                                <option value="rainy">{{ __('Rainy') }}</option>
                                <option value="foggy">{{ __('Foggy') }}</option>
                                <option value="stormy">{{ __('Stormy') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Description') }}</label>
                        <textarea wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.description"
                                  placeholder="{{ __('e.g., Modern glass skyscraper with clean lines, floor-to-ceiling windows overlooking the city...') }}"
                                  style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 100px; resize: vertical;"></textarea>
                    </div>

                    {{-- Generate Reference Button --}}
                    <button type="button"
                            wire:click="generateLocationReference({{ $editIndex }})"
                            wire:loading.attr="disabled"
                            style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                        <span wire:loading.remove wire:target="generateLocationReference">🎨 {{ __('Generate Reference Image') }}</span>
                        <span wire:loading wire:target="generateLocationReference">{{ __('Generating...') }}</span>
                    </button>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 3rem; margin-bottom: 1rem;">📍</span>
                        <p>{{ __('Add a location to get started') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.locationBible.enabled" style="accent-color: #8b5cf6;">
                {{ __('Enable Location Bible') }}
            </label>
            <button type="button"
                    @click="isOpen = false"
                    style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>
