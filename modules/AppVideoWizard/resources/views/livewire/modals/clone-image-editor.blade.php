{{-- Clone Image Editor Modal — AI Image Studio (Edit + Reimagine) --}}
@if($showCloneImageEditorModal)
<div class="vw-modal-overlay"
     x-data="{ activeTab: @entangle('cloneImageEditorTab') }"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(var(--vw-primary-rgb), 0.12); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 95vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--vw-border); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: var(--vw-text); font-size: 1.1rem; font-weight: 600;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--vw-primary); margin-right: 0.4rem;"></i>
                    {{ __('AI Image Studio') }}
                </h3>
                <p style="margin: 0.25rem 0 0 0; color: var(--vw-text-secondary); font-size: 0.8rem;">{{ __('Edit or reimagine your first frame before creating video') }}</p>
            </div>
            <button type="button" wire:click="closeCloneImageEditor" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Tab Bar --}}
        <div style="padding: 0.75rem 1.25rem 0; display: flex; gap: 0.5rem;">
            <button type="button"
                    @click="activeTab = 'edit'"
                    :style="activeTab === 'edit'
                        ? 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid var(--vw-border-accent); border-bottom: none; background: rgba(var(--vw-primary-rgb), 0.06); color: var(--vw-text-secondary); font-size: 0.85rem; font-weight: 500; cursor: pointer;'
                        : 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid transparent; border-bottom: none; background: none; color: var(--vw-text-secondary); font-size: 0.85rem; font-weight: 500; cursor: pointer;'">
                <i class="fa-solid fa-pen-to-square" style="margin-right: 0.3rem;"></i> {{ __('Edit') }}
            </button>
            <button type="button"
                    @click="activeTab = 'reimagine'"
                    :style="activeTab === 'reimagine'
                        ? 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid rgba(236,72,153,0.4); border-bottom: none; background: rgba(236,72,153,0.15); color: #be185d; font-size: 0.85rem; font-weight: 500; cursor: pointer;'
                        : 'padding: 0.5rem 1rem; border-radius: 0.5rem 0.5rem 0 0; border: 1px solid transparent; border-bottom: none; background: none; color: var(--vw-text-secondary); font-size: 0.85rem; font-weight: 500; cursor: pointer;'">
                <i class="fa-solid fa-palette" style="margin-right: 0.3rem;"></i> {{ __('Reimagine') }}
            </button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid rgba(0,0,0,0.04);">

            {{-- Image Preview (shared between tabs) --}}
            <div style="position: relative; border-radius: 0.5rem; overflow: hidden; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.04);">
                @if(!empty($videoAnalysisResult['firstFrameUrl']))
                    <img src="{{ $videoAnalysisResult['firstFrameUrl'] }}" alt="{{ __('First frame preview') }}"
                         style="width: 100%; max-height: 320px; object-fit: contain; display: block; background: #000;">
                @else
                    <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: var(--vw-text-secondary);">
                        {{ __('No image available') }}
                    </div>
                @endif

                {{-- Loading overlay --}}
                <div wire:loading wire:target="applyCloneImageEdit, reimagineCloneImage"
                     style="position: absolute; inset: 0; background: rgba(0,0,0,0.7); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border: 3px solid rgba(var(--vw-primary-rgb), 0.12); border-top-color: var(--vw-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <span style="color: var(--vw-text-secondary); font-size: 0.85rem;">{{ __('Applying changes...') }}</span>
                </div>
            </div>

            {{-- Undo / Reset controls --}}
            @if(!empty($cloneImageEditHistory) || ($originalFirstFrameUrl && $originalFirstFrameUrl !== ($videoAnalysisResult['firstFrameUrl'] ?? '')))
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    @if(!empty($cloneImageEditHistory))
                        <button type="button"
                                wire:click="undoCloneImageEdit"
                                style="padding: 0.35rem 0.75rem; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); border-radius: 0.35rem; color: #d97706; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-rotate-left" style="font-size: 0.7rem;"></i> {{ __('Undo Last') }}
                        </button>
                    @endif
                    @if($originalFirstFrameUrl && $originalFirstFrameUrl !== ($videoAnalysisResult['firstFrameUrl'] ?? ''))
                        <button type="button"
                                wire:click="resetCloneImageToOriginal"
                                style="padding: 0.35rem 0.75rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.35rem; color: #f87171; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-arrow-rotate-right" style="font-size: 0.7rem;"></i> {{ __('Reset to Original') }}
                        </button>
                    @endif
                </div>
            @endif

            {{-- Error message --}}
            @if($cloneImageEditError)
                <div style="padding: 0.6rem 0.85rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; color: #dc2626; font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ $cloneImageEditError }}
                </div>
            @endif

            {{-- ============ EDIT TAB ============ --}}
            <div x-show="activeTab === 'edit'" x-cloak>
                {{-- Quick Action Presets --}}
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.5rem;">{{ __('Quick Actions') }}</label>
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
                                        wire:click="$set('cloneImageEditPrompt', '{{ addslashes($preset['prompt']) }}')"
                                        x-on:click="$nextTick(() => $wire.applyCloneImageEdit())"
                                    @else
                                        wire:click="$set('cloneImageEditPrompt', '{{ addslashes($preset['prompt']) }}')"
                                        x-on:click="$nextTick(() => document.getElementById('clone-edit-prompt').focus())"
                                    @endif
                                    style="padding: 0.4rem 0.7rem; background: rgba(var(--vw-primary-rgb), 0.04); border: 1px solid rgba(var(--vw-primary-rgb), 0.08); border-radius: 2rem; color: var(--vw-text-secondary); font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: background 0.2s, border-color 0.2s;"
                                    onmouseover="this.style.background='rgba(var(--vw-primary-rgb), 0.08)'; this.style.borderColor='var(--vw-border-focus)';"
                                    onmouseout="this.style.background='rgba(var(--vw-primary-rgb), 0.04)'; this.style.borderColor='rgba(var(--vw-primary-rgb), 0.08)';">
                                <i class="{{ $preset['icon'] }}" style="font-size: 0.7rem;"></i>
                                {{ $preset['label'] }}
                                @if($preset['auto'])
                                    <i class="fa-solid fa-bolt" style="font-size: 0.55rem; color: #d97706; margin-left: 0.15rem;" title="{{ __('Auto-applies') }}"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Custom prompt textarea --}}
                <div>
                    <label for="clone-edit-prompt" style="display: block; color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.35rem;">{{ __('Custom Edit Prompt') }}</label>
                    <textarea id="clone-edit-prompt"
                              wire:model.blur="cloneImageEditPrompt"
                              rows="3"
                              placeholder="{{ __('Describe what you want to change...') }}"
                              style="width: 100%; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: white; font-size: 0.85rem; resize: vertical; outline: none; font-family: inherit;"
                              onfocus="this.style.borderColor='var(--vw-border-focus)'"
                              onblur="this.style.borderColor='var(--vw-border)'"></textarea>
                </div>
            </div>

            {{-- ============ REIMAGINE TAB ============ --}}
            <div x-show="activeTab === 'reimagine'" x-cloak>
                {{-- Style Grid --}}
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.5rem;">{{ __('Choose a Style') }}</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                        @php
                            $reimagineStyles = [
                                'anime' => ['name' => __('Anime / Manga'), 'icon' => 'fa-solid fa-star', 'color' => '#f472b6'],
                                'ghibli' => ['name' => __('Studio Ghibli'), 'icon' => 'fa-solid fa-cloud', 'color' => '#34d399'],
                                'pixar' => ['name' => __('Pixar 3D'), 'icon' => 'fa-solid fa-cube', 'color' => '#60a5fa'],
                                'oil_painting' => ['name' => __('Oil Painting'), 'icon' => 'fa-solid fa-palette', 'color' => '#f59e0b'],
                                'watercolor' => ['name' => __('Watercolor'), 'icon' => 'fa-solid fa-droplet', 'color' => '#0891b2'],
                                'comic_book' => ['name' => __('Comic Book'), 'icon' => 'fa-solid fa-bolt', 'color' => '#ef4444'],
                                'cyberpunk' => ['name' => __('Cyberpunk'), 'icon' => 'fa-solid fa-microchip', 'color' => '#06b6d4'],
                                'vintage_film' => ['name' => __('Vintage Film'), 'icon' => 'fa-solid fa-film', 'color' => '#d97706'],
                                'dark_gothic' => ['name' => __('Dark Gothic'), 'icon' => 'fa-solid fa-skull', 'color' => '#6b7280'],
                                'minimalist' => ['name' => __('Minimalist'), 'icon' => 'fa-solid fa-minus', 'color' => '#e2e8f0'],
                                'sketch' => ['name' => __('Pencil Sketch'), 'icon' => 'fa-solid fa-pencil', 'color' => '#9ca3af'],
                                'pop_art' => ['name' => __('Pop Art'), 'icon' => 'fa-solid fa-shapes', 'color' => '#d97706'],
                            ];
                        @endphp

                        @foreach($reimagineStyles as $key => $style)
                            <button type="button"
                                    wire:click="reimagineCloneImage('{{ $key }}')"
                                    style="padding: 0.6rem 0.5rem; background: rgba(0,0,0,0.02); border: 1px solid var(--vw-border); border-radius: 0.5rem; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 0.35rem; transition: background 0.2s, border-color 0.2s, transform 0.2s;"
                                    onmouseover="this.style.background='rgba(0,0,0,0.04)'; this.style.borderColor='{{ $style['color'] }}50'; this.style.transform='translateY(-1px)';"
                                    onmouseout="this.style.background='rgba(0,0,0,0.02)'; this.style.borderColor='var(--vw-border)'; this.style.transform='translateY(0)';">
                                <i class="{{ $style['icon'] }}" style="font-size: 1.1rem; color: {{ $style['color'] }};"></i>
                                <span style="color: var(--vw-text); font-size: 0.7rem; font-weight: 500; text-align: center; line-height: 1.2;">{{ $style['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Custom reimagine prompt --}}
                <div>
                    <label for="clone-reimagine-prompt" style="display: block; color: var(--vw-text-secondary); font-size: 0.75rem; font-weight: 500; margin-bottom: 0.35rem;">{{ __('Custom Style Description') }}</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <textarea id="clone-reimagine-prompt"
                                  wire:model.blur="cloneImageEditPrompt"
                                  rows="2"
                                  placeholder="{{ __('Describe a custom style, e.g. "Medieval tapestry", "Neon noir"...') }}"
                                  style="flex: 1; background: rgba(0,0,0,0.03); border: 1px solid var(--vw-border); border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: white; font-size: 0.85rem; resize: none; outline: none; font-family: inherit;"
                                  onfocus="this.style.borderColor='rgba(236,72,153,0.5)'"
                                  onblur="this.style.borderColor='var(--vw-border)'"></textarea>
                        <button type="button"
                                wire:click="applyCloneImageEdit"
                                wire:loading.attr="disabled"
                                wire:target="applyCloneImageEdit"
                                style="padding: 0.5rem 0.85rem; background: var(--vw-primary); border: none; border-radius: 0.5rem; color: white; font-size: 0.8rem; font-weight: 500; cursor: pointer; white-space: nowrap; align-self: flex-end;"
                                onmouseover="this.style.opacity='0.9'"
                                onmouseout="this.style.opacity='1'">
                            <i class="fa-solid fa-palette" style="margin-right: 0.3rem;"></i> {{ __('Apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--vw-border); display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button"
                    wire:click="closeCloneImageEditor"
                    style="padding: 0.45rem 1rem; background: rgba(0,0,0,0.04); border: 1px solid var(--vw-border); border-radius: 0.5rem; color: var(--vw-text); font-size: 0.85rem; cursor: pointer;">
                {{ __('Close') }}
            </button>
            <button type="button"
                    x-show="activeTab === 'edit'"
                    wire:click="applyCloneImageEdit"
                    wire:loading.attr="disabled"
                    wire:target="applyCloneImageEdit"
                    style="padding: 0.45rem 1rem; background: linear-gradient(135deg, var(--vw-primary), #6d28d9); border: none; border-radius: 0.5rem; color: white; font-size: 0.85rem; font-weight: 500; cursor: pointer;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'">
                <span wire:loading.remove wire:target="applyCloneImageEdit">
                    <i class="fa-solid fa-check" style="margin-right: 0.3rem;"></i> {{ __('Apply Edit') }}
                </span>
                <span wire:loading wire:target="applyCloneImageEdit">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.3rem;"></i> {{ __('Applying...') }}
                </span>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endif
