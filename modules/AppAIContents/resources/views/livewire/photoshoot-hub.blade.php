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
                {{-- 6 Sample Preview Thumbnails (3x2 grid) with SVG scene illustrations --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                    {{-- Studio --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #f5f5f5, #e0e0e0); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="8" y="10" width="12" height="10" rx="1.5" fill="rgba(0,0,0,0.12)"/><rect x="10" y="6" width="8" height="4" rx="1" fill="rgba(0,0,0,0.08)"/><circle cx="14" cy="15" r="3" fill="rgba(0,0,0,0.06)" stroke="rgba(0,0,0,0.15)" stroke-width="0.8"/><line x1="14" y1="3" x2="14" y2="6" stroke="rgba(0,0,0,0.1)" stroke-width="0.8"/><circle cx="14" cy="2.5" r="1.5" fill="rgba(0,0,0,0.08)"/><line x1="3" y1="20" x2="25" y2="20" stroke="rgba(0,0,0,0.06)" stroke-width="0.5"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(0,0,0,0.3); text-transform: uppercase; letter-spacing: 0.5px;">Studio</span>
                    </div>
                    {{-- Lifestyle --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #fff8e1, #ffcc80); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><circle cx="21" cy="6" r="3" fill="rgba(255,160,0,0.2)"/><path d="M4 22 Q8 12, 14 16 Q18 8, 24 14 L24 22 Z" fill="rgba(139,69,19,0.1)"/><rect x="10" y="13" width="6" height="8" rx="1" fill="rgba(139,69,19,0.15)"/><circle cx="13" cy="11" r="2" fill="rgba(139,69,19,0.12)"/><path d="M6 18 Q7 15, 9 18" stroke="rgba(76,175,80,0.25)" stroke-width="0.8" fill="none"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(139,69,19,0.35); text-transform: uppercase; letter-spacing: 0.5px;">Lifestyle</span>
                    </div>
                    {{-- Outdoor --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #e8f5e9, #81c784); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><circle cx="22" cy="5" r="2.5" fill="rgba(255,235,59,0.3)"/><path d="M2 22 L8 12 L14 18 L19 10 L26 22 Z" fill="rgba(27,94,32,0.12)"/><path d="M6 22 L10 15 L14 22" fill="rgba(27,94,32,0.08)"/><rect x="12" y="16" width="4" height="6" rx="0.5" fill="rgba(27,94,32,0.15)"/><line x1="2" y1="22" x2="26" y2="22" stroke="rgba(27,94,32,0.1)" stroke-width="0.5"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(27,94,32,0.35); text-transform: uppercase; letter-spacing: 0.5px;">Outdoor</span>
                    </div>
                    {{-- Flat Lay --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #fce4ec, #f8bbd0); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="3" y="3" width="22" height="22" rx="2" fill="rgba(136,14,79,0.05)" stroke="rgba(136,14,79,0.1)" stroke-width="0.5"/><rect x="5" y="5" width="7" height="7" rx="1.5" fill="rgba(136,14,79,0.1)"/><circle cx="19" cy="8.5" r="3.5" fill="rgba(136,14,79,0.08)" stroke="rgba(136,14,79,0.12)" stroke-width="0.5"/><rect x="5" y="15" width="5" height="8" rx="1" fill="rgba(136,14,79,0.07)"/><rect x="13" y="14" width="9" height="5" rx="1" fill="rgba(136,14,79,0.06)"/><rect x="14" y="21" width="6" height="3" rx="0.8" fill="rgba(136,14,79,0.09)"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(136,14,79,0.3); text-transform: uppercase; letter-spacing: 0.5px;">Flat Lay</span>
                    </div>
                    {{-- Dramatic --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #1a1a2e, #2d2d44); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="9" y="8" width="10" height="14" rx="1.5" fill="rgba(255,255,255,0.08)"/><ellipse cx="14" cy="24" rx="8" ry="1" fill="rgba(255,255,255,0.04)"/><path d="M11 4 L14 1 L17 4" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/><line x1="14" y1="1" x2="14" y2="8" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/><circle cx="14" cy="15" r="2" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.12)" stroke-width="0.5"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.5px;">Dramatic</span>
                    </div>
                    {{-- Tech --}}
                    <div style="aspect-ratio: 1; border-radius: 8px; background: linear-gradient(135deg, #0d0d1a, #1a237e); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="6" y="8" width="16" height="12" rx="2" fill="rgba(3,252,244,0.06)" stroke="rgba(3,252,244,0.2)" stroke-width="0.6"/><rect x="8" y="10" width="12" height="8" rx="1" fill="rgba(3,252,244,0.04)"/><line x1="10" y1="22" x2="18" y2="22" stroke="rgba(3,252,244,0.15)" stroke-width="0.8"/><line x1="12" y1="20" x2="12" y2="22" stroke="rgba(3,252,244,0.1)" stroke-width="0.5"/><line x1="16" y1="20" x2="16" y2="22" stroke="rgba(3,252,244,0.1)" stroke-width="0.5"/><circle cx="14" cy="14" r="1.5" fill="rgba(3,252,244,0.12)"/><path d="M10 13 L12.5 14 L10 15" stroke="rgba(3,252,244,0.2)" stroke-width="0.5" fill="none"/></svg>
                        <span style="font-size: 8px; font-weight: 600; color: rgba(3,252,244,0.4); text-transform: uppercase; letter-spacing: 0.5px;">Tech</span>
                    </div>
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
                        'studio-white' => [
                            'gradient' => 'linear-gradient(135deg, #f5f5f5, #e0e0e0)',
                            'color' => 'rgba(0,0,0,0.4)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><rect x="10" y="50" width="40" height="2" rx="1" fill="rgba(0,0,0,0.06)"/><ellipse cx="30" cy="50" rx="14" ry="2" fill="rgba(0,0,0,0.04)"/><rect x="20" y="22" width="20" height="28" rx="3" fill="rgba(0,0,0,0.1)"/><rect x="23" y="18" width="14" height="4" rx="1.5" fill="rgba(0,0,0,0.06)"/><circle cx="30" cy="36" r="5" fill="rgba(0,0,0,0.05)" stroke="rgba(0,0,0,0.12)" stroke-width="1"/><line x1="5" y1="10" x2="5" y2="50" stroke="rgba(0,0,0,0.06)" stroke-width="1"/><circle cx="5" cy="8" r="3" fill="rgba(0,0,0,0.05)"/><line x1="55" y1="10" x2="55" y2="50" stroke="rgba(0,0,0,0.06)" stroke-width="1"/><circle cx="55" cy="8" r="3" fill="rgba(0,0,0,0.05)"/></svg>',
                        ],
                        'lifestyle' => [
                            'gradient' => 'linear-gradient(135deg, #fff8e1, #ffcc80)',
                            'color' => 'rgba(139,69,19,0.5)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><circle cx="48" cy="10" r="5" fill="rgba(255,160,0,0.2)"/><path d="M0 55 Q10 35, 25 45 Q35 30, 50 40 L60 38 L60 72 L0 72 Z" fill="rgba(139,69,19,0.08)"/><rect x="18" y="28" width="12" height="24" rx="2" fill="rgba(139,69,19,0.12)"/><circle cx="24" cy="23" r="5" fill="rgba(139,69,19,0.1)"/><rect x="35" y="34" width="10" height="18" rx="2" fill="rgba(139,69,19,0.08)"/><path d="M8 48 Q12 40, 16 48" stroke="rgba(76,175,80,0.2)" stroke-width="1.2" fill="none"/><path d="M44 48 Q47 42, 50 48" stroke="rgba(76,175,80,0.15)" stroke-width="1" fill="none"/></svg>',
                        ],
                        'outdoor' => [
                            'gradient' => 'linear-gradient(135deg, #e8f5e9, #81c784)',
                            'color' => 'rgba(27,94,32,0.5)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><circle cx="46" cy="10" r="5" fill="rgba(255,235,59,0.25)"/><path d="M0 72 L0 50 L15 25 L30 45 L40 20 L60 42 L60 72 Z" fill="rgba(27,94,32,0.1)"/><path d="M0 72 L0 55 L20 35 L35 55 L60 45 L60 72 Z" fill="rgba(27,94,32,0.07)"/><rect x="23" y="38" width="10" height="16" rx="1.5" fill="rgba(27,94,32,0.13)"/><circle cx="28" cy="34" r="4" fill="rgba(27,94,32,0.1)"/><path d="M5 48 L8 42 L11 48" fill="rgba(27,94,32,0.08)"/><path d="M48 40 L51 33 L54 40" fill="rgba(27,94,32,0.08)"/></svg>',
                        ],
                        'flat-lay' => [
                            'gradient' => 'linear-gradient(135deg, #fce4ec, #f8bbd0)',
                            'color' => 'rgba(136,14,79,0.4)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><rect x="4" y="4" width="52" height="64" rx="3" fill="rgba(136,14,79,0.04)" stroke="rgba(136,14,79,0.08)" stroke-width="0.5"/><rect x="8" y="8" width="18" height="18" rx="3" fill="rgba(136,14,79,0.08)"/><circle cx="40" cy="17" r="9" fill="rgba(136,14,79,0.06)" stroke="rgba(136,14,79,0.1)" stroke-width="0.5"/><rect x="8" y="32" width="12" height="20" rx="2" fill="rgba(136,14,79,0.06)"/><rect x="24" y="30" width="22" height="12" rx="2" fill="rgba(136,14,79,0.05)"/><rect x="26" y="48" width="16" height="8" rx="1.5" fill="rgba(136,14,79,0.07)"/><circle cx="15" cy="60" r="4" fill="rgba(136,14,79,0.04)" stroke="rgba(136,14,79,0.08)" stroke-width="0.5"/><rect x="46" y="48" width="6" height="14" rx="1" fill="rgba(136,14,79,0.05)"/></svg>',
                        ],
                        'dramatic' => [
                            'gradient' => 'linear-gradient(135deg, #1a1a2e, #2d2d44)',
                            'color' => 'rgba(255,255,255,0.5)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><defs><radialGradient id="dspot"><stop offset="0%" stop-color="rgba(255,255,255,0.08)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><ellipse cx="30" cy="30" rx="18" ry="24" fill="url(#dspot)"/><rect x="22" y="18" width="16" height="34" rx="3" fill="rgba(255,255,255,0.07)"/><circle cx="30" cy="32" r="4" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" stroke-width="0.8"/><ellipse cx="30" cy="58" rx="16" ry="2.5" fill="rgba(255,255,255,0.03)"/><path d="M26 10 L30 5 L34 10" stroke="rgba(255,255,255,0.12)" stroke-width="0.8" fill="none"/><line x1="30" y1="5" x2="30" y2="18" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/></svg>',
                        ],
                        'seasonal' => [
                            'gradient' => 'linear-gradient(135deg, #e8eaf6, #9fa8da)',
                            'color' => 'rgba(26,35,126,0.4)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><circle cx="12" cy="10" r="2" fill="rgba(26,35,126,0.12)"/><circle cx="30" cy="6" r="1.5" fill="rgba(26,35,126,0.1)"/><circle cx="48" cy="12" r="2.5" fill="rgba(26,35,126,0.08)"/><path d="M10 60 Q20 40, 30 50 Q40 35, 50 45 L50 65 L10 65 Z" fill="rgba(26,35,126,0.06)"/><rect x="22" y="26" width="16" height="28" rx="2.5" fill="rgba(26,35,126,0.1)"/><path d="M15 22 L18 16 L21 22 L15 22" fill="rgba(26,35,126,0.06)"/><path d="M40 20 L44 12 L48 20 L40 20" fill="rgba(26,35,126,0.06)"/><circle cx="30" cy="40" r="4" fill="rgba(26,35,126,0.06)" stroke="rgba(26,35,126,0.12)" stroke-width="0.5"/></svg>',
                        ],
                        'minimalist' => [
                            'gradient' => 'linear-gradient(135deg, #eceff1, #cfd8dc)',
                            'color' => 'rgba(0,0,0,0.35)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><rect x="20" y="20" width="20" height="32" rx="2" fill="rgba(0,0,0,0.07)"/><line x1="15" y1="58" x2="45" y2="58" stroke="rgba(0,0,0,0.06)" stroke-width="0.8"/><circle cx="30" cy="36" r="3" fill="rgba(0,0,0,0.04)" stroke="rgba(0,0,0,0.08)" stroke-width="0.6"/></svg>',
                        ],
                        'luxury' => [
                            'gradient' => 'linear-gradient(135deg, #3e2723, #5d4037)',
                            'color' => 'rgba(255,215,0,0.5)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><rect x="18" y="16" width="24" height="36" rx="3" fill="rgba(255,215,0,0.06)" stroke="rgba(255,215,0,0.15)" stroke-width="0.6"/><circle cx="30" cy="34" r="6" fill="rgba(255,215,0,0.04)" stroke="rgba(255,215,0,0.12)" stroke-width="0.6"/><path d="M24 58 L30 54 L36 58" stroke="rgba(255,215,0,0.15)" stroke-width="0.6" fill="none"/><line x1="10" y1="62" x2="50" y2="62" stroke="rgba(255,215,0,0.08)" stroke-width="0.5"/><rect x="27" y="12" width="6" height="4" rx="1" fill="rgba(255,215,0,0.1)"/></svg>',
                        ],
                        'tech' => [
                            'gradient' => 'linear-gradient(135deg, #0d0d1a, #1a237e)',
                            'color' => 'rgba(3,252,244,0.6)',
                            'svg' => '<svg viewBox="0 0 60 72" fill="none"><rect x="10" y="16" width="40" height="28" rx="3" fill="rgba(3,252,244,0.04)" stroke="rgba(3,252,244,0.15)" stroke-width="0.6"/><rect x="14" y="20" width="32" height="20" rx="1.5" fill="rgba(3,252,244,0.03)"/><line x1="20" y1="50" x2="40" y2="50" stroke="rgba(3,252,244,0.12)" stroke-width="0.8"/><line x1="26" y1="44" x2="26" y2="50" stroke="rgba(3,252,244,0.08)" stroke-width="0.5"/><line x1="34" y1="44" x2="34" y2="50" stroke="rgba(3,252,244,0.08)" stroke-width="0.5"/><circle cx="30" cy="30" r="4" fill="rgba(3,252,244,0.06)" stroke="rgba(3,252,244,0.15)" stroke-width="0.5"/><path d="M22 28 L26 30 L22 32" stroke="rgba(3,252,244,0.15)" stroke-width="0.6" fill="none"/><rect x="18" y="56" width="24" height="6" rx="1" fill="rgba(3,252,244,0.03)" stroke="rgba(3,252,244,0.08)" stroke-width="0.4"/></svg>',
                        ],
                    ];
                @endphp
                <div class="cs-template-grid">
                    @foreach($templates as $template)
                        @php $visual = $templateVisuals[$template['id']] ?? ['gradient' => 'linear-gradient(135deg, #eee, #ddd)', 'color' => 'rgba(0,0,0,0.3)', 'svg' => '']; @endphp
                        <div class="cs-card cs-card-clickable {{ $selectedTemplate === $template['id'] ? 'cs-template-thumb selected' : '' }}"
                             wire:click="selectTemplate('{{ $template['id'] }}')"
                             style="padding: 0; overflow: hidden; aspect-ratio: 3/4; position: relative; border: {{ $selectedTemplate === $template['id'] ? '2px solid var(--cs-primary)' : '2px solid transparent' }};">
                            <div style="width: 100%; height: 100%; background: {{ $visual['gradient'] }}; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; padding: 8px;">
                                <div style="width: 100%; flex: 1; display: flex; align-items: center; justify-content: center;">
                                    {!! $visual['svg'] ?? '' !!}
                                </div>
                                <span style="font-size: 9px; font-weight: 600; color: {{ $visual['color'] }}; text-transform: uppercase; letter-spacing: 0.5px;">{{ $template['name'] }}</span>
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
                      dir="auto"
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

    {{-- Multi-Stage Loading State --}}
    @if($isGenerating)
        <div x-data="{
            stages: [
                { icon: 'fa-light fa-magnifying-glass-plus', label: '{{ __("Analyzing product") }}' },
                { icon: 'fa-light fa-palette', label: '{{ __("Applying style") }}' },
                { icon: 'fa-light fa-images', label: '{{ __("Generating compositions") }}' },
                { icon: 'fa-light fa-sparkles', label: '{{ __("Polishing images") }}' }
            ],
            current: 0,
            progress: 0,
            elapsed: 0,
            init() {
                // Advance stages every ~8 seconds
                this._stageInterval = setInterval(() => {
                    if (this.current < this.stages.length - 1) {
                        this.current++;
                    }
                }, 8000);
                // Asymptotic progress: approaches 90% but never reaches it
                this._progressInterval = setInterval(() => {
                    this.elapsed++;
                    this.progress = Math.min(90, 90 * (1 - Math.exp(-this.elapsed / 20)));
                }, 500);
            },
            destroy() {
                clearInterval(this._stageInterval);
                clearInterval(this._progressInterval);
            },
            get estimate() {
                if (this.current <= 1) return '{{ __("About 30 seconds remaining") }}';
                if (this.current === 2) return '{{ __("Almost there...") }}';
                return '{{ __("Finishing up...") }}';
            }
        }" x-init="init()" style="text-align: center; margin-top: 40px; padding: 32px 0;">

            {{-- Animated Icon --}}
            <div style="font-size: 36px; color: var(--cs-primary-text); margin-bottom: 16px; height: 44px; display: flex; align-items: center; justify-content: center;">
                <template x-for="(stage, i) in stages" :key="i">
                    <i x-show="current === i" :class="stage.icon" style="animation: cs-pulse-fade 2s ease-in-out infinite;"
                       x-transition:enter="transition ease-out duration-300"
                       x-transition:enter-start="opacity-0 transform scale-75"
                       x-transition:enter-end="opacity-100 transform scale-100"></i>
                </template>
            </div>

            {{-- Stage Label --}}
            <p style="color: var(--cs-text); font-size: 14px; font-weight: 500; margin: 0 0 12px 0; min-height: 20px;"
               x-text="stages[current].label"></p>

            {{-- Stage Progress Dots --}}
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px;">
                <template x-for="(stage, i) in stages" :key="'dot-'+i">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div :style="{
                            width: current === i ? '10px' : '7px',
                            height: current === i ? '10px' : '7px',
                            borderRadius: '50%',
                            background: i <= current ? 'var(--cs-primary, #03fcf4)' : 'var(--cs-border, rgba(255,255,255,0.1))',
                            transition: 'all 0.4s ease',
                            boxShadow: current === i ? '0 0 8px var(--cs-primary, rgba(3,252,244,0.4))' : 'none'
                        }"></div>
                        <div x-show="i < stages.length - 1" style="width: 24px; height: 1px;"
                             :style="{ background: i < current ? 'var(--cs-primary, #03fcf4)' : 'var(--cs-border, rgba(255,255,255,0.1))', transition: 'background 0.4s ease' }"></div>
                    </div>
                </template>
            </div>

            {{-- Progress Bar --}}
            <div style="max-width: 280px; margin: 0 auto 12px auto; height: 3px; background: var(--cs-border, rgba(255,255,255,0.08)); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; border-radius: 2px; background: var(--cs-primary, #03fcf4); transition: width 0.5s ease;"
                     :style="{ width: progress + '%' }"></div>
            </div>

            {{-- Time Estimate --}}
            <p style="color: var(--cs-text-muted); font-size: 12px; margin: 0;" x-text="estimate"></p>
        </div>

        <style>
            @keyframes cs-pulse-fade {
                0%, 100% { opacity: 0.6; transform: scale(1); }
                50% { opacity: 1; transform: scale(1.08); }
            }
        </style>
    @endif
</div>
