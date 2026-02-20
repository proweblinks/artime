<div>
    @if($isGenerating)
        <div wire:poll.3s="pollResults" style="display:none;"></div>
    @endif

    {{-- Page Header --}}
    <div class="cs-page-header">
        <div class="cs-page-icon"><i class="fa-light fa-camera-retro"></i></div>
        <h1>{{ __('Photoshoot') }}</h1>
        <p>{{ __('Choose a guided template for professional product shots or use our flexible editor to create anything you can imagine.') }}</p>
    </div>

    @if($mode === 'menu')
        {{-- ━━━ Two Entry Points with Visual Preview ━━━ --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 900px; margin: 0 auto;">
            {{-- Product Photoshoot --}}
            <div class="cs-card cs-card-clickable" wire:click="selectMode('template')" style="padding: 24px; overflow: hidden;">
                <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 4px 0;">
                    {{ __('Create a product photoshoot') }}
                </h3>
                <p style="font-size: 12px; color: var(--cs-text-muted); margin: 0 0 16px 0;">
                    {{ __('Choose a product image and templates to get professional shots') }}
                </p>
                {{-- 6 Sample Preview Thumbnails (3x2 grid) --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                    @php
                        $previewGradients = [
                            'linear-gradient(135deg, #fce4ec, #f8bbd0)',
                            'linear-gradient(135deg, #e8f5e9, #c8e6c9)',
                            'linear-gradient(135deg, #fff3e0, #ffe0b2)',
                            'linear-gradient(135deg, #e3f2fd, #bbdefb)',
                            'linear-gradient(135deg, #fce4ec, #f48fb1)',
                            'linear-gradient(135deg, #e0f7fa, #80deea)',
                        ];
                    @endphp
                    @foreach($previewGradients as $gradient)
                        <div style="aspect-ratio: 1; border-radius: 8px; background: {{ $gradient }}; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-light fa-bag-shopping" style="font-size: 16px; color: rgba(0,0,0,0.2);"></i>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Free-form --}}
            <div class="cs-card cs-card-clickable" wire:click="selectMode('freeform')" style="padding: 24px; overflow: hidden;">
                <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 4px 0;">
                    {{ __('Generate or edit an image') }}
                </h3>
                <p style="font-size: 12px; color: var(--cs-text-muted); margin: 0 0 16px 0;">
                    {{ __('Describe the image you want with a prompt or edit an existing one') }}
                </p>
                {{-- Single Large Preview --}}
                <div style="aspect-ratio: 16/10; border-radius: 10px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                    <div style="position: absolute; inset: 0; background: radial-gradient(circle at 30% 50%, rgba(3,252,244,0.15), transparent 60%);"></div>
                    <div style="text-align: center; position: relative; z-index: 1;">
                        <i class="fa-light fa-wand-magic-sparkles" style="font-size: 32px; color: rgba(3,252,244,0.6); margin-bottom: 8px; display: block;"></i>
                        <span style="font-size: 11px; color: rgba(255,255,255,0.4);">{{ __('AI-powered generation') }}</span>
                    </div>
                </div>
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
                        <img src="{{ url('/public/storage/' . $productImagePath) }}" alt="" style="width: 100%; border-radius: var(--cs-radius-lg);">
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
                <div class="cs-section-label">{{ __('Photoshoot Templates') }}</div>
                @php
                    $templateVisuals = [
                        'studio-white' => ['gradient' => 'linear-gradient(135deg, #f5f5f5, #e0e0e0)', 'color' => 'rgba(0,0,0,0.15)'],
                        'lifestyle' => ['gradient' => 'linear-gradient(135deg, #fff8e1, #ffcc80)', 'color' => 'rgba(139,69,19,0.2)'],
                        'outdoor' => ['gradient' => 'linear-gradient(135deg, #e8f5e9, #81c784)', 'color' => 'rgba(27,94,32,0.2)'],
                        'flat-lay' => ['gradient' => 'linear-gradient(135deg, #fce4ec, #f8bbd0)', 'color' => 'rgba(136,14,79,0.15)'],
                        'dramatic' => ['gradient' => 'linear-gradient(135deg, #1a1a2e, #2d2d44)', 'color' => 'rgba(255,255,255,0.15)'],
                        'seasonal' => ['gradient' => 'linear-gradient(135deg, #e8eaf6, #9fa8da)', 'color' => 'rgba(26,35,126,0.15)'],
                        'minimalist' => ['gradient' => 'linear-gradient(135deg, #eceff1, #cfd8dc)', 'color' => 'rgba(0,0,0,0.1)'],
                        'luxury' => ['gradient' => 'linear-gradient(135deg, #3e2723, #5d4037)', 'color' => 'rgba(255,215,0,0.2)'],
                        'tech' => ['gradient' => 'linear-gradient(135deg, #0d0d1a, #1a237e)', 'color' => 'rgba(3,252,244,0.2)'],
                    ];
                @endphp
                <div class="cs-template-grid">
                    @foreach($templates as $template)
                        @php $visual = $templateVisuals[$template['id']] ?? ['gradient' => 'linear-gradient(135deg, #eee, #ddd)', 'color' => 'rgba(0,0,0,0.1)']; @endphp
                        <div class="cs-card cs-card-clickable {{ $selectedTemplate === $template['id'] ? 'cs-template-thumb selected' : '' }}"
                             wire:click="selectTemplate('{{ $template['id'] }}')"
                             style="padding: 0; overflow: hidden; aspect-ratio: 3/4; position: relative; border: {{ $selectedTemplate === $template['id'] ? '2px solid var(--cs-primary)' : '2px solid transparent' }};">
                            <div style="width: 100%; height: 100%; background: {{ $visual['gradient'] }}; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;">
                                <i class="{{ $template['icon'] }}" style="font-size: 20px; color: {{ $visual['color'] }};"></i>
                                <span style="font-size: 10px; font-weight: 500; color: {{ $visual['color'] }}; text-transform: uppercase; letter-spacing: 0.5px;">{{ $template['name'] }}</span>
                            </div>
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
                        <img src="{{ url('/public/storage/' . $path) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
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
