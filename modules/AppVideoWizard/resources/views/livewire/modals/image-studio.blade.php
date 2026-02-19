{{-- Universal AI Image Studio Modal — Edit + Reimagine any image --}}
@if($showImageStudioModal)
<div class="vw-modal-overlay"
     x-data="{ activeTab: @entangle('imageStudioTab') }"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 95vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: #a78bfa; margin-right: 0.4rem;"></i>
                    {{ __('AI Image Studio') }}
                </h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.5); font-size: 0.8rem;">
                    @if(($imageStudioTarget['type'] ?? '') === 'clone')
                        {{ __('Edit or reimagine your first frame') }}
                    @elseif(($imageStudioTarget['type'] ?? '') === 'shot')
                        {{ __('Scene') }} {{ ($imageStudioTarget['sceneIndex'] ?? 0) + 1 }}, {{ __('Shot') }} {{ ($imageStudioTarget['shotIndex'] ?? 0) + 1 }}
                    @else
                        {{ __('Scene') }} {{ ($imageStudioTarget['sceneIndex'] ?? 0) + 1 }}
                    @endif
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                @if(($imageStudioTarget['type'] ?? '') !== 'clone')
                    <button type="button"
                            wire:click="openAssetHistory('{{ $imageStudioTarget['type'] ?? 'scene' }}', {{ $imageStudioTarget['sceneIndex'] ?? 0 }}{{ ($imageStudioTarget['type'] ?? '') === 'shot' ? ', ' . ($imageStudioTarget['shotIndex'] ?? 0) : '' }})"
                            style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.8rem; cursor: pointer; padding: 0.35rem 0.65rem; display: flex; align-items: center; gap: 0.3rem;"
                            title="{{ __('View history') }}">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 0.75rem;"></i> {{ __('History') }}
                    </button>
                @endif
                <button type="button" wire:click="closeImageStudio" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
            </div>
        </div>

        {{-- Tab Bar --}}
        <div style="padding: 0.75rem 1.25rem 0; display: flex; gap: 0.5rem;">
            <button type="button"
                    @click="activeTab = 'edit'"
                    :style="activeTab === 'edit'
                        ? 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid rgba(139,92,246,0.4); border-bottom: none; background: rgba(139,92,246,0.15); color: #c4b5fd; font-size: 0.85rem; font-weight: 500; cursor: pointer;'
                        : 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid transparent; border-bottom: none; background: none; color: rgba(255,255,255,0.5); font-size: 0.85rem; font-weight: 500; cursor: pointer;'">
                <i class="fa-solid fa-pen-to-square" style="margin-right: 0.3rem;"></i> {{ __('Edit') }}
            </button>
            <button type="button"
                    @click="activeTab = 'reimagine'"
                    :style="activeTab === 'reimagine'
                        ? 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid rgba(236,72,153,0.4); border-bottom: none; background: rgba(236,72,153,0.15); color: #f9a8d4; font-size: 0.85rem; font-weight: 500; cursor: pointer;'
                        : 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid transparent; border-bottom: none; background: none; color: rgba(255,255,255,0.5); font-size: 0.85rem; font-weight: 500; cursor: pointer;'">
                <i class="fa-solid fa-palette" style="margin-right: 0.3rem;"></i> {{ __('Reimagine') }}
            </button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">

            {{-- Image Preview --}}
            <div style="position: relative; border-radius: 0.5rem; overflow: hidden; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08);">
                @if(!empty($imageStudioTarget['imageUrl']))
                    <img src="{{ $imageStudioTarget['imageUrl'] }}" alt="{{ __('Image preview') }}"
                         style="width: 100%; max-height: 320px; object-fit: contain; display: block; background: #000;">
                @else
                    <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4);">
                        {{ __('No image available') }}
                    </div>
                @endif

                {{-- Loading overlay (wire:loading.flex so Livewire hides by default, shows as flex when loading) --}}
                <div wire:loading.flex wire:target="applyImageStudioEdit, uploadedStudioImage"
                     style="position: absolute; inset: 0; background: rgba(0,0,0,0.7); flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border: 3px solid rgba(139,92,246,0.3); border-top-color: #a78bfa; border-radius: 50%; animation: imgStudioSpin 1s linear infinite;"></div>
                    <span style="color: #c4b5fd; font-size: 0.85rem;">{{ __('Applying changes...') }}</span>
                </div>
            </div>

            {{-- Undo / Reset controls --}}
            @if(!empty($imageStudioTarget['editStack']) || ($imageStudioTarget['originalUrl'] && $imageStudioTarget['originalUrl'] !== ($imageStudioTarget['imageUrl'] ?? '')))
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    @if(!empty($imageStudioTarget['editStack']))
                        <button type="button"
                                wire:click="undoImageStudioEdit"
                                style="padding: 0.35rem 0.75rem; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); border-radius: 0.35rem; color: #fbbf24; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-rotate-left" style="font-size: 0.7rem;"></i> {{ __('Undo Last') }}
                        </button>
                    @endif
                    @if($imageStudioTarget['originalUrl'] && $imageStudioTarget['originalUrl'] !== ($imageStudioTarget['imageUrl'] ?? ''))
                        <button type="button"
                                wire:click="resetImageStudioToOriginal"
                                style="padding: 0.35rem 0.75rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.35rem; color: #f87171; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-arrow-rotate-right" style="font-size: 0.7rem;"></i> {{ __('Reset to Original') }}
                        </button>
                    @endif
                </div>
            @endif

            {{-- Error message --}}
            @if($imageStudioError)
                <div style="padding: 0.6rem 0.85rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; color: #fca5a5; font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ $imageStudioError }}
                </div>
            @endif

            {{-- ============ EDIT TAB ============ --}}
            <div x-show="activeTab === 'edit'" x-cloak>
                {{-- Quick Action Presets --}}
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.5rem;">{{ __('Quick Actions') }}</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        @php
                            $editPresets = [
                                ['label' => __('Remove Watermark'), 'icon' => 'fa-solid fa-eraser', 'auto' => true, 'prompt' => 'Remove any watermarks, logos, or text overlays from this image while preserving the underlying content naturally'],
                                ['label' => __('Remove Object'), 'icon' => 'fa-solid fa-trash-can', 'auto' => true, 'prompt' => 'Remove unwanted objects from this image and fill the area naturally with the surrounding background'],
                                ['label' => __('Add Object'), 'icon' => 'fa-solid fa-plus', 'auto' => false, 'prompt' => 'Add a '],
                                ['label' => __('Change Background'), 'icon' => 'fa-solid fa-image', 'auto' => false, 'prompt' => 'Change the background of this image to '],
                                ['label' => __('Enhance Quality'), 'icon' => 'fa-solid fa-sparkles', 'auto' => true, 'prompt' => 'Enhance the quality of this image. Improve sharpness, lighting, color balance, and overall visual clarity while maintaining the original content and style'],
                                ['label' => __('Fix Lighting'), 'icon' => 'fa-solid fa-sun', 'auto' => true, 'prompt' => 'Fix the lighting in this image. Balance exposure, reduce harsh shadows, brighten dark areas, and create natural even lighting'],
                            ];
                        @endphp

                        @foreach($editPresets as $idx => $preset)
                            <button type="button"
                                    @if($preset['auto'])
                                        wire:click="applyImageStudioEdit('{{ addslashes($preset['prompt']) }}')"
                                    @else
                                        wire:click="$set('imageStudioPrompt', '{{ addslashes($preset['prompt']) }}')"
                                        x-on:click="$nextTick(() => document.getElementById('studio-edit-prompt').focus())"
                                    @endif
                                    style="padding: 0.4rem 0.7rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.25); border-radius: 2rem; color: #c4b5fd; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: background 0.2s, border-color 0.2s;"
                                    onmouseover="this.style.background='rgba(139,92,246,0.2)'; this.style.borderColor='rgba(139,92,246,0.5)';"
                                    onmouseout="this.style.background='rgba(139,92,246,0.1)'; this.style.borderColor='rgba(139,92,246,0.25)';">
                                <i class="{{ $preset['icon'] }}" style="font-size: 0.7rem;"></i>
                                {{ $preset['label'] }}
                                @if($preset['auto'])
                                    <i class="fa-solid fa-bolt" style="font-size: 0.55rem; color: #fbbf24; margin-left: 0.15rem;" title="{{ __('Auto-applies') }}"></i>
                                @endif
                            </button>
                        @endforeach

                        {{-- Upload Image button --}}
                        <label style="padding: 0.4rem 0.7rem; background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.25); border-radius: 2rem; color: #7dd3fc; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: background 0.2s, border-color 0.2s; position: relative;"
                               onmouseover="this.style.background='rgba(56,189,248,0.2)'; this.style.borderColor='rgba(56,189,248,0.5)';"
                               onmouseout="this.style.background='rgba(56,189,248,0.1)'; this.style.borderColor='rgba(56,189,248,0.25)';">
                            <input type="file" accept="image/*" wire:model="uploadedStudioImage"
                                   style="position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;" />
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 0.7rem;"></i>
                            <span wire:loading.remove wire:target="uploadedStudioImage">{{ __('Upload Image') }}</span>
                            <span wire:loading wire:target="uploadedStudioImage"><i class="fa-solid fa-spinner fa-spin"></i></span>
                        </label>
                    </div>
                </div>

                {{-- Custom prompt textarea --}}
                <div>
                    <label for="studio-edit-prompt" style="display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.35rem;">{{ __('Custom Edit Prompt') }}</label>
                    <textarea id="studio-edit-prompt"
                              wire:model.blur="imageStudioPrompt"
                              rows="3"
                              placeholder="{{ __('Describe what you want to change...') }}"
                              style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: white; font-size: 0.85rem; resize: vertical; outline: none; font-family: inherit;"
                              onfocus="this.style.borderColor='rgba(139,92,246,0.5)'"
                              onblur="this.style.borderColor='rgba(255,255,255,0.12)'"></textarea>
                </div>
            </div>

            {{-- ============ REIMAGINE TAB ============ --}}
            <div x-show="activeTab === 'reimagine'" x-cloak
                 x-data="{ selectedStyle: null }">

                {{-- Explanation --}}
                <p style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.45); font-size: 0.75rem; line-height: 1.4;">
                    {{ __('Reimagine transforms the entire scene into a different world — characters, environment, clothing, and atmosphere all change to match the chosen reality.') }}
                </p>

                {{-- Style Grid --}}
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.5rem;">{{ __('Choose a World') }}</label>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.4rem;">
                        @php
                            $reimagineStyles = [
                                'anime' => ['name' => __('Anime World'), 'icon' => 'fa-solid fa-star', 'color' => '#f472b6'],
                                'ghibli' => ['name' => __('Studio Ghibli'), 'icon' => 'fa-solid fa-cloud', 'color' => '#34d399'],
                                'pixar' => ['name' => __('Pixar / Disney'), 'icon' => 'fa-solid fa-cube', 'color' => '#60a5fa'],
                                'cyberpunk' => ['name' => __('Cyberpunk 2077'), 'icon' => 'fa-solid fa-microchip', 'color' => '#06b6d4'],
                                'art_deco_1920s' => ['name' => __('1920s Art Deco'), 'icon' => 'fa-solid fa-building-columns', 'color' => '#d97706'],
                                'medieval' => ['name' => __('Medieval Fantasy'), 'icon' => 'fa-solid fa-chess-rook', 'color' => '#a3e635'],
                                'post_apocalyptic' => ['name' => __('Post-Apocalyptic'), 'icon' => 'fa-solid fa-radiation', 'color' => '#78716c'],
                                'retro_80s' => ['name' => __('Retro 80s'), 'icon' => 'fa-solid fa-compact-disc', 'color' => '#e879f9'],
                                'dark_gothic' => ['name' => __('Dark Gothic'), 'icon' => 'fa-solid fa-skull', 'color' => '#6b7280'],
                                'comic_book' => ['name' => __('Comic Book'), 'icon' => 'fa-solid fa-bolt', 'color' => '#ef4444'],
                                'steampunk' => ['name' => __('Steampunk'), 'icon' => 'fa-solid fa-gear', 'color' => '#b45309'],
                                'ancient_mythology' => ['name' => __('Ancient World'), 'icon' => 'fa-solid fa-landmark', 'color' => '#fbbf24'],
                            ];
                        @endphp

                        @foreach($reimagineStyles as $key => $style)
                            <button type="button"
                                    @click="selectedStyle = '{{ $key }}'"
                                    wire:click="reimagineImageStudio('{{ $key }}')"
                                    :class="selectedStyle === '{{ $key }}' ? 'img-studio-style-active' : 'img-studio-style'"
                                    style="padding: 0.5rem 0.35rem; border-radius: 0.5rem; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 0.3rem; transition: all 0.2s;"
                                    :style="selectedStyle === '{{ $key }}'
                                        ? 'background: {{ $style['color'] }}18; border: 2px solid {{ $style['color'] }}; transform: translateY(-1px);'
                                        : 'background: rgba(255,255,255,0.04); border: 2px solid rgba(255,255,255,0.08);'">
                                <i class="{{ $style['icon'] }}" style="font-size: 1rem; color: {{ $style['color'] }};"></i>
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.65rem; font-weight: 500; text-align: center; line-height: 1.15;">{{ $style['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Reimagine prompt textarea --}}
                <div style="margin-bottom: 0.75rem;">
                    <label for="studio-reimagine-prompt" style="display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.35rem;">
                        {{ __('Reimagine Description') }}
                        <span style="color: rgba(255,255,255,0.3); font-weight: 400;">— {{ __('customize or write your own') }}</span>
                    </label>
                    <textarea id="studio-reimagine-prompt"
                              wire:model.blur="imageStudioPrompt"
                              rows="3"
                              placeholder="{{ __('Choose a world above, or describe your own: "1920s silent film era", "Underwater coral kingdom", "Ancient Egyptian palace"...') }}"
                              style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: white; font-size: 0.85rem; resize: vertical; outline: none; font-family: inherit;"
                              onfocus="this.style.borderColor='rgba(236,72,153,0.5)'"
                              onblur="this.style.borderColor='rgba(255,255,255,0.12)'"></textarea>
                </div>

                {{-- Reimagine Apply Button --}}
                <button type="button"
                        wire:click="applyImageStudioEdit"
                        wire:loading.attr="disabled"
                        wire:target="applyImageStudioEdit"
                        style="width: 100%; padding: 0.65rem 1rem; background: linear-gradient(135deg, #8b5cf6, #ec4899); border: none; border-radius: 0.5rem; color: white; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem; transition: opacity 0.2s;"
                        onmouseover="this.style.opacity='0.9'"
                        onmouseout="this.style.opacity='1'">
                    <span wire:loading.remove wire:target="applyImageStudioEdit">
                        <i class="fa-solid fa-wand-magic-sparkles" style="margin-right: 0.3rem;"></i> {{ __('Reimagine') }}
                    </span>
                    <span wire:loading wire:target="applyImageStudioEdit">
                        <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.3rem;"></i> {{ __('Reimagining...') }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button"
                    wire:click="closeImageStudio"
                    style="padding: 0.45rem 1rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer;">
                {{ __('Close') }}
            </button>
            {{-- Apply Edit button (Edit tab only) --}}
            <button type="button"
                    x-show="activeTab === 'edit'"
                    wire:click="applyImageStudioEdit"
                    wire:loading.attr="disabled"
                    wire:target="applyImageStudioEdit"
                    style="padding: 0.45rem 1rem; background: linear-gradient(135deg, #8b5cf6, #6d28d9); border: none; border-radius: 0.5rem; color: white; font-size: 0.85rem; font-weight: 500; cursor: pointer;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'">
                <span wire:loading.remove wire:target="applyImageStudioEdit">
                    <i class="fa-solid fa-check" style="margin-right: 0.3rem;"></i> {{ __('Apply Edit') }}
                </span>
                <span wire:loading wire:target="applyImageStudioEdit">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.3rem;"></i> {{ __('Applying...') }}
                </span>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes imgStudioSpin {
        to { transform: rotate(360deg); }
    }
</style>
@endif
