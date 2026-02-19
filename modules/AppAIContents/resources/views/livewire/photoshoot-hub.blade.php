<div
    @if($isGenerating)
        wire:poll.3s="pollResults"
    @endif
>
    {{-- Page Header --}}
    <div class="cs-page-header">
        <div class="cs-page-icon"><i class="fa-light fa-camera-retro"></i></div>
        <h1>{{ __('Photoshoot') }}</h1>
        <p>{{ __('Create professional product photography and images with AI.') }}</p>
    </div>

    @if($mode === 'menu')
        {{-- ━━━ Two Entry Points ━━━ --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 700px; margin: 0 auto;">
            {{-- Product Photoshoot --}}
            <div class="cs-card cs-card-clickable" wire:click="selectMode('template')"
                 style="padding: 40px 24px; text-align: center;">
                <div style="font-size: 40px; color: var(--cs-primary-text); margin-bottom: 12px;">
                    <i class="fa-light fa-bag-shopping"></i>
                </div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                    {{ __('Create a product photoshoot') }}
                </h3>
                <p style="font-size: 13px; color: var(--cs-text-muted); margin: 0;">
                    {{ __('Upload your product and choose from professional templates.') }}
                </p>
            </div>

            {{-- Free-form --}}
            <div class="cs-card cs-card-clickable" wire:click="selectMode('freeform')"
                 style="padding: 40px 24px; text-align: center;">
                <div style="font-size: 40px; color: var(--cs-primary-text); margin-bottom: 12px;">
                    <i class="fa-light fa-wand-magic-sparkles"></i>
                </div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                    {{ __('Generate or edit an image') }}
                </h3>
                <p style="font-size: 13px; color: var(--cs-text-muted); margin: 0;">
                    {{ __('Describe what you want or edit existing images with AI.') }}
                </p>
            </div>
        </div>

        {{-- Recent Photoshoots --}}
        @if($recentPhotoshoots->isNotEmpty())
            <div class="cs-section-label" style="margin-top: 40px;">{{ __('Recent Photoshoots') }}</div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                @foreach($recentPhotoshoots as $ps)
                    @foreach(($ps->results ?? []) as $result)
                        @if(!empty($result['url']))
                            <div style="aspect-ratio: 1; border-radius: var(--cs-radius); overflow: hidden;">
                                <img src="{{ $result['url'] }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        @endif

    @elseif($mode === 'template')
        {{-- ━━━ Product Photoshoot Template Flow ━━━ --}}
        <div class="cs-breadcrumb" wire:click="goBack">
            <i class="fa-light fa-arrow-left"></i>
            {{ __('Back to Photoshoot') }}
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            {{-- Left: Product Image Upload --}}
            <div>
                <div class="cs-section-label">{{ __('Product Image') }}</div>
                @if($productImagePath)
                    <div style="border-radius: var(--cs-radius-lg); overflow: hidden; position: relative;">
                        <img src="{{ Storage::disk('public')->url($productImagePath) }}" alt="" style="width: 100%; border-radius: var(--cs-radius-lg);">
                    </div>
                @else
                    <label class="cs-upload-area" style="display: block; cursor: pointer;">
                        <input type="file" wire:model="productImage" style="display: none;" accept="image/*">
                        <div style="font-size: 36px; color: var(--cs-primary-text); margin-bottom: 8px;">
                            <i class="fa-light fa-plus"></i>
                        </div>
                        <div style="font-size: 14px; color: var(--cs-primary-text); font-weight: 500;">
                            {{ __('Select Image') }}
                        </div>
                    </label>
                @endif
            </div>

            {{-- Right: Template Grid --}}
            <div>
                <div class="cs-section-label">{{ __('Choose a Template') }}</div>
                <div class="cs-template-grid">
                    @foreach($templates as $template)
                        <div class="cs-card cs-card-clickable {{ $selectedTemplate === $template['id'] ? 'cs-template-thumb selected' : '' }}"
                             wire:click="selectTemplate('{{ $template['id'] }}')"
                             style="padding: 16px; text-align: center; aspect-ratio: 3/4; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <i class="{{ $template['icon'] }}" style="font-size: 24px; color: var(--cs-primary-text); margin-bottom: 8px;"></i>
                            <span style="font-size: 12px; font-weight: 500;">{{ $template['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Aspect Ratio + Generate --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px;">
            <div class="cs-ingredients">
                <div class="cs-ingredient-btn" x-data="{ open: false }" @click="open = !open" style="position: relative;">
                    <i class="fa-light fa-crop-simple"></i>
                    <span>{{ $aspectRatio === '9:16' ? 'Story (9:16)' : ($aspectRatio === '1:1' ? 'Square (1:1)' : 'Feed (4:5)') }}</span>
                    <i class="fa-light fa-chevron-down" style="font-size: 10px;"></i>

                    <div class="cs-dropdown" x-show="open" @click.outside="open = false" x-transition style="left: 0; right: auto;">
                        <div class="cs-dropdown-item" wire:click="setAspectRatio('9:16')" @click="open = false">Story (9:16)</div>
                        <div class="cs-dropdown-item" wire:click="setAspectRatio('1:1')" @click="open = false">Square (1:1)</div>
                        <div class="cs-dropdown-item" wire:click="setAspectRatio('4:5')" @click="open = false">Feed (4:5)</div>
                    </div>
                </div>
            </div>

            <button class="cs-btn cs-btn-primary"
                    wire:click="generate"
                    @if(!$productImagePath || !$selectedTemplate || $isGenerating) disabled @endif>
                <i class="fa-light fa-sparkles"></i>
                {{ $isGenerating ? __('Generating...') : __('Create Photoshoot') }}
            </button>
        </div>

    @elseif($mode === 'freeform')
        {{-- ━━━ Free-form Image Generation ━━━ --}}
        <div class="cs-breadcrumb" wire:click="goBack">
            <i class="fa-light fa-arrow-left"></i>
            {{ __('Back to Photoshoot') }}
        </div>

        <div class="cs-card" style="padding: 24px;">
            <textarea class="cs-input cs-input-lg"
                      wire:model.live="prompt"
                      placeholder="{{ __('Edit this image (Press + to add image)...') }}"
                      rows="4"></textarea>

            {{-- Reference Images --}}
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                @foreach($referenceImagePaths as $index => $path)
                    <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; position: relative;">
                        <img src="{{ Storage::disk('public')->url($path) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        <button wire:click="removeReferenceImage({{ $index }})"
                                style="position: absolute; top: 2px; right: 2px; width: 16px; height: 16px; border-radius: 50%; background: var(--cs-danger); color: white; border: none; cursor: pointer; font-size: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-light fa-xmark"></i>
                        </button>
                    </div>
                @endforeach
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <div class="cs-ingredients">
                    @if(count($referenceImagePaths) < 3)
                        <label class="cs-ingredient-btn" style="cursor: pointer;">
                            <input type="file" wire:model="referenceImages" style="display: none;" accept="image/*" multiple>
                            <i class="fa-light fa-plus"></i>
                            {{ __('Add Images') }}
                            <span style="font-size: 11px; color: var(--cs-text-muted);">({{ count($referenceImagePaths) }}/3)</span>
                        </label>
                    @endif

                    <div class="cs-ingredient-btn" x-data="{ open: false }" @click="open = !open" style="position: relative;">
                        <i class="fa-light fa-crop-simple"></i>
                        <span>{{ $aspectRatio === '9:16' ? 'Story (9:16)' : ($aspectRatio === '1:1' ? 'Square (1:1)' : 'Feed (4:5)') }}</span>
                        <div class="cs-dropdown" x-show="open" @click.outside="open = false" x-transition style="left: 0; right: auto;">
                            <div class="cs-dropdown-item" wire:click="setAspectRatio('9:16')" @click="open = false">Story (9:16)</div>
                            <div class="cs-dropdown-item" wire:click="setAspectRatio('1:1')" @click="open = false">Square (1:1)</div>
                            <div class="cs-dropdown-item" wire:click="setAspectRatio('4:5')" @click="open = false">Feed (4:5)</div>
                        </div>
                    </div>
                </div>

                <button class="cs-btn cs-btn-primary"
                        wire:click="generate"
                        @if(empty(trim($prompt)) || $isGenerating) disabled @endif>
                    <i class="fa-light fa-sparkles"></i>
                    {{ $isGenerating ? __('Generating...') : __('Generate') }}
                </button>
            </div>

            <div class="cs-disclaimer">{{ __('Content Studio can make mistakes, so double-check it.') }}</div>
        </div>
    @endif

    {{-- ━━━ Results ━━━ --}}
    @if(!empty($results))
        <div class="cs-section-label" style="margin-top: 32px;">{{ __('Results') }}</div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
            @foreach($results as $result)
                @if(!empty($result['url']))
                    <div class="cs-card" style="overflow: hidden;">
                        <img src="{{ $result['url'] }}" alt="" style="width: 100%; display: block;">
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Loading State --}}
    @if($isGenerating)
        <div style="text-align: center; margin-top: 40px;">
            <div style="font-size: 36px; color: var(--cs-primary-text); margin-bottom: 12px;">
                <i class="fa-light fa-camera-retro fa-spin-pulse"></i>
            </div>
            <p style="color: var(--cs-text-muted); font-size: 14px;">{{ __('Generating your images...') }}</p>
            <div class="cs-progress" style="max-width: 200px; margin: 12px auto;">
                <div class="cs-progress-bar" style="width: 60%; animation: cs-shimmer 2s ease-in-out infinite;"></div>
            </div>
        </div>
    @endif
</div>
