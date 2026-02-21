{{-- Step 2: Concept Development --}}
<style>
    /* Scoped CSS for Concept Step - uses parent selector for specificity instead of !important */
    .vw-concept-step .vw-concept-card {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-concept-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-concept-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .vw-concept-step .vw-concept-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--vw-text);
        margin: 0;
    }

    .vw-concept-step .vw-concept-subtitle {
        font-size: 0.875rem;
        color: var(--vw-text-secondary);
        margin-top: 0.25rem;
    }

    .vw-concept-step .vw-context-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--vw-bg-elevated);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-context-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--vw-text-secondary);
        font-size: 0.875rem;
    }

    .vw-concept-step .vw-context-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text-secondary);
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .vw-concept-step .vw-context-badge.accent {
        background: rgba(236, 72, 153, 0.1);
        color: #be185d;
    }

    .vw-concept-step .vw-context-arrow {
        color: var(--vw-text-muted);
    }

    .vw-concept-step .vw-context-duration {
        color: #16a34a;
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* Visual Mode Selector - Master Style Authority - PROMINENT POSITIONING */
    .vw-concept-step .vw-visual-mode-section {
        background: rgba(var(--vw-primary-rgb), 0.03);
        border: none;
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-visual-mode-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-concept-step .vw-visual-mode-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--vw-primary);
    }

    .vw-concept-step .vw-visual-mode-badge {
        background: var(--vw-primary);
        color: var(--vw-text-bright);
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .vw-concept-step .vw-visual-mode-options {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .vw-concept-step .vw-visual-mode-option {
        flex: 1;
        min-width: 140px;
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: 0.5rem;
        padding: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-visual-mode-option:hover {
        background: var(--vw-bg-hover);
        box-shadow: var(--vw-clay-hover);
        transform: translateY(-2px);
    }

    .vw-concept-step .vw-visual-mode-option.active {
        background: rgba(var(--vw-primary-rgb), 0.06);
        box-shadow: var(--vw-clay-active);
    }

    .vw-concept-step .vw-visual-mode-option.active .vw-mode-label {
        color: var(--vw-primary);
    }

    .vw-concept-step .vw-mode-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .vw-concept-step .vw-mode-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-concept-step .vw-mode-desc {
        font-size: 0.7rem;
        color: var(--vw-text-muted);
        margin-top: 0.25rem;
    }

    .vw-concept-step .vw-field-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--vw-text);
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-textarea {
        width: 100%;
        min-height: 140px;
        background: var(--vw-bg-surface);
        border: none;
        border-radius: 0.5rem;
        padding: 1rem;
        color: var(--vw-text);
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        transition: box-shadow 0.2s;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-textarea:focus {
        outline: none;
        box-shadow: var(--vw-clay-active);
    }

    .vw-concept-step .vw-textarea::placeholder {
        color: var(--vw-text-muted);
    }

    .vw-concept-step .vw-textarea.enhanced {
        background: rgba(16, 185, 129, 0.05);
        box-shadow: var(--vw-clay-active);
    }

    .vw-concept-step .vw-input {
        width: 100%;
        background: var(--vw-bg-surface);
        border: none;
        border-radius: 0.5rem;
        padding: 0.875rem 1rem;
        color: var(--vw-text);
        font-size: 0.95rem;
        transition: box-shadow 0.2s;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-input:focus {
        outline: none;
        box-shadow: var(--vw-clay-active);
    }

    .vw-concept-step .vw-input::placeholder {
        color: var(--vw-text-muted);
    }

    .vw-concept-step .vw-enhance-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.75rem;
    }

    .vw-concept-step .vw-enhance-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--vw-primary);
        color: var(--vw-text-bright);
        padding: 0.75rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .vw-concept-step .vw-enhance-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--vw-primary-rgb), 0.15);
    }

    .vw-concept-step .vw-enhance-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vw-concept-step .vw-enhance-hint {
        display: inline-block;
        color: var(--vw-text-muted);
        font-size: 0.8rem;
    }

    .vw-concept-step .vw-enhanced-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        animation: vw-fade-in 0.3s ease-out;
    }

    .vw-concept-step .vw-undo-btn {
        background: transparent;
        border: none;
        color: var(--vw-text-secondary);
        padding: 0.5rem 0.75rem;
        border-radius: 0.35rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-undo-btn:hover {
        color: var(--vw-text);
        box-shadow: var(--vw-clay-hover);
        transform: translateY(-2px);
    }

    .vw-concept-step .vw-field-group {
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-field-note {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: var(--vw-text-muted);
        margin-bottom: 0.5rem;
    }

    .vw-concept-step .vw-field-note .warning {
        color: #fbbf24;
    }

    .vw-concept-step .vw-field-helper {
        font-size: 0.8rem;
        color: var(--vw-text-muted);
        margin-top: 0.5rem;
    }

    .vw-concept-step .vw-generate-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        background: var(--vw-primary);
        color: var(--vw-text-bright);
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 1rem;
    }

    .vw-concept-step .vw-generate-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(var(--vw-primary-rgb), 0.18);
    }

    .vw-concept-step .vw-generate-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vw-concept-step .vw-divider {
        height: 1px;
        background: var(--vw-border);
        margin: 1.5rem 0;
    }

    /* Spinner Animation */
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes vw-fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .vw-concept-step .vw-loading-inner {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .vw-concept-step .vw-loading-inner.lg {
        gap: 0.5rem;
    }

    /* Enhancement Meta Tags */
    .vw-concept-step .vw-enhancement-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--vw-border);
    }

    .vw-concept-step .vw-enhancement-tag {
        background: var(--vw-bg-elevated);
        border: none;
        padding: 0.25rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.75rem;
        color: var(--vw-text-secondary);
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-enhancement-tag.mood {
        background: rgba(var(--vw-primary-rgb), 0.06);
        color: var(--vw-primary);
    }

    .vw-concept-step .vw-enhancement-tag.tone {
        background: rgba(236, 72, 153, 0.08);
        color: #be185d;
    }

    /* Your Concept Results Card */
    .vw-concept-step .vw-your-concept-card {
        background: var(--vw-bg-surface);
        border: none;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-your-concept-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .vw-concept-step .vw-your-concept-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        background: var(--vw-primary);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .vw-concept-step .vw-main-concept-card {
        background: rgba(var(--vw-primary-rgb), 0.03);
        border: none;
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-main-concept-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--vw-text);
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-main-concept-text {
        color: var(--vw-text-secondary);
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .vw-concept-step .vw-concept-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .vw-concept-step .vw-concept-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .vw-concept-step .vw-concept-badge.engaging {
        background: rgba(6, 182, 212, 0.1);
        color: #0891b2;
    }

    .vw-concept-step .vw-concept-badge.professional {
        background: rgba(16, 185, 129, 0.1);
        color: #16a34a;
    }

    /* Alternative Directions */
    .vw-concept-step .vw-alt-directions-label {
        font-size: 0.85rem;
        color: var(--vw-text-muted);
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-alt-directions-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .vw-concept-step .vw-alt-directions-grid {
            grid-template-columns: 1fr;
        }
    }

    .vw-concept-step .vw-alt-card {
        background: var(--vw-bg-elevated);
        border: none;
        border-radius: 0.5rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--vw-clay);
    }

    .vw-concept-step .vw-alt-card:hover {
        background: var(--vw-bg-hover);
        box-shadow: var(--vw-clay-hover);
        transform: translateY(-2px);
    }

    .vw-concept-step .vw-alt-card.selected {
        background: rgba(var(--vw-primary-rgb), 0.06);
        box-shadow: var(--vw-clay-active);
    }

    .vw-concept-step .vw-alt-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--vw-text);
        margin-bottom: 0.25rem;
    }

    .vw-concept-step .vw-alt-card-subtitle {
        font-size: 0.8rem;
        color: var(--vw-text-muted);
    }

    .vw-concept-step .vw-generate-different-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        background: transparent;
        border: 1px dashed var(--vw-border);
        color: var(--vw-text-secondary);
        padding: 1rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-concept-step .vw-generate-different-btn:hover {
        border-color: var(--vw-border-accent);
        color: var(--vw-primary);
        background: var(--vw-primary-soft);
    }

    /* Detection Summary Panel (Phase 1.5) */
    .vw-concept-step .detection-summary-panel {
        animation: vw-fade-in 0.3s ease-out;
    }

    .vw-concept-step .vw-loading-opacity {
        opacity: 0.6;
        pointer-events: none;
    }

    .vw-concept-step .vw-error-alert {
        background: rgba(239, 68, 68, 0.06);
        border: none;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        color: var(--vw-danger);
        box-shadow: var(--vw-clay);
    }
</style>

<div class="vw-concept-step">
    @if($isSocialContent ?? false)
        @include('appvideowizard::livewire.steps.partials._social-concept')
        @include('appvideowizard::livewire.modals.image-studio')
        @include('appvideowizard::livewire.modals.asset-history-panel')
    @else
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right: 0.5rem;"></i>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-concept-header">
            <div class="vw-concept-icon"><i class="fa-solid fa-lightbulb"></i></div>
            <div>
                <h2 class="vw-concept-title">{{ __('Develop Your Concept') }}</h2>
                <p class="vw-concept-subtitle">{{ __("Tell us what you want to create - we'll generate unique ideas") }}</p>
            </div>
        </div>

        {{-- Context Bar --}}
        @if($productionType)
            @php
                $productionTypes = config('appvideowizard.production_types', []);
                $typeName = $productionTypes[$productionType]['name'] ?? ucfirst($productionType);
                $subtypeName = '';
                if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype])) {
                    $subtypeName = $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'];
                }
                $durationMin = floor($targetDuration / 60);
                $durationSec = $targetDuration % 60;
                $durationText = $durationMin > 0 ? ($durationMin . 'm' . ($durationSec > 0 ? ' ' . $durationSec . 's' : '')) : ($durationSec . 's');
            @endphp
            <div class="vw-context-bar">
                <div class="vw-context-left">
                    <span>{{ __('Creating:') }}</span>
                    <span class="vw-context-badge">
                        <i class="fa-solid fa-film"></i> {{ $typeName }}
                    </span>
                    @if($subtypeName)
                        <span class="vw-context-arrow">→</span>
                        <span class="vw-context-badge accent">
                            <i class="fa-solid fa-crosshairs"></i> {{ $subtypeName }}
                        </span>
                    @endif
                </div>
                <div class="vw-context-duration">{{ $durationText }}</div>
            </div>
        @endif

        {{-- Visual Mode Selector - MASTER STYLE AUTHORITY - Prominent at top --}}
        <div class="vw-visual-mode-section">
            <div class="vw-visual-mode-header">
                <span class="vw-visual-mode-title"><i class="fa-solid fa-palette"></i> {{ __('Visual Style') }}</span>
                <span class="vw-visual-mode-badge">{{ __('MASTER') }}</span>
            </div>
            <div class="vw-visual-mode-options">
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? 'cinematic-realistic') === 'cinematic-realistic' ? 'active' : '' }}"
                     wire:click="setVisualMode('cinematic-realistic')">
                    <div class="vw-mode-icon"><i class="fa-solid fa-film"></i></div>
                    <div class="vw-mode-label">{{ __('Cinematic Realistic') }}</div>
                    <div class="vw-mode-desc">{{ __('Live-action, photorealistic, Hollywood quality') }}</div>
                </div>
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? '') === 'stylized-animation' ? 'active' : '' }}"
                     wire:click="setVisualMode('stylized-animation')">
                    <div class="vw-mode-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <div class="vw-mode-label">{{ __('Stylized Animation') }}</div>
                    <div class="vw-mode-desc">{{ __('2D/3D animation, cartoon, anime') }}</div>
                </div>
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? '') === 'mixed-hybrid' ? 'active' : '' }}"
                     wire:click="setVisualMode('mixed-hybrid')">
                    <div class="vw-mode-icon"><i class="fa-solid fa-masks-theater"></i></div>
                    <div class="vw-mode-label">{{ __('Mixed / Hybrid') }}</div>
                    <div class="vw-mode-desc">{{ __('Combination of styles') }}</div>
                </div>
            </div>
        </div>

        {{-- Main Input --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __("What's your video about?") }}</label>
            <textarea wire:model.blur="concept.rawInput"
                      class="vw-textarea {{ !empty($concept['refinedConcept']) ? 'enhanced' : '' }}"
                      placeholder="{{ __("Describe your idea, theme, or story... Be creative! Examples:
• A mysterious figure discovers an ancient power
• The untold story of a small town's greatest secret
• A journey through impossible landscapes
• An entrepreneur's rise from nothing") }}"></textarea>

            <div class="vw-enhance-row">
                @if(!empty($concept['refinedConcept']))
                    {{-- Show enhanced badge with undo option --}}
                    <span class="vw-enhanced-badge">
                        ✓ {{ __('AI Enhanced') }}
                    </span>
                    <button type="button" class="vw-undo-btn" wire:click="dismissEnhancement">
                        ↩ {{ __('Undo Enhancement') }}
                    </button>
                @else
                    {{-- Show enhance button --}}
                    <button class="vw-enhance-btn"
                            wire:click="enhanceConcept"
                            wire:loading.attr="disabled"
                            wire:target="enhanceConcept, generateIdeas"
                            @if(empty($concept['rawInput'])) disabled @endif>
                        <span wire:loading.remove wire:target="enhanceConcept"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Enhance with AI') }}</span>
                        <span wire:loading wire:target="enhanceConcept">
                            <span class="vw-loading-inner">
                                <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                </svg>
                                {{ __('Enhancing...') }}
                            </span>
                        </span>
                    </button>
                    <span class="vw-enhance-hint">{{ __('Auto-improves your concept with AI') }}</span>
                @endif
            </div>

            {{-- Enhancement Meta Tags (mood, tone, etc.) - shows when enhanced --}}
            @if(!empty($concept['suggestedMood']) || !empty($concept['suggestedTone']) || !empty($concept['keyElements']))
                <div class="vw-enhancement-meta">
                    @if(!empty($concept['suggestedMood']))
                        <span class="vw-enhancement-tag mood"><i class="fa-solid fa-masks-theater"></i> {{ ucfirst($concept['suggestedMood']) }}</span>
                    @endif
                    @if(!empty($concept['suggestedTone']))
                        <span class="vw-enhancement-tag tone"><i class="fa-solid fa-crosshairs"></i> {{ ucfirst($concept['suggestedTone']) }}</span>
                    @endif
                    @if(!empty($concept['targetAudience']))
                        <span class="vw-enhancement-tag"><i class="fa-solid fa-users"></i> {{ $concept['targetAudience'] }}</span>
                    @endif
                    @if(!empty($concept['keyElements']) && is_array($concept['keyElements']))
                        @foreach(array_slice($concept['keyElements'], 0, 3) as $element)
                            <span class="vw-enhancement-tag">{{ $element }}</span>
                        @endforeach
                    @endif
                </div>
            @endif
        </div>

        <div class="vw-divider"></div>

        {{-- Style Inspiration --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __('Style Inspiration (Optional)') }}</label>
            <div class="vw-field-note">
                <span class="warning"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span>{{ __('This is for VISUAL STYLE only - your content will be 100% original') }}</span>
            </div>
            <input type="text"
                   wire:model.blur="concept.styleReference"
                   class="vw-input"
                   placeholder="{{ __("e.g., 'Breaking Bad cinematography', 'Wes Anderson color palette', 'documentary noir'") }}">
            <p class="vw-field-helper">{{ __("We'll capture the visual FEEL without copying any content or characters") }}</p>
        </div>

        {{-- Things to Avoid --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __('Things to Avoid (Optional)') }}</label>
            <input type="text"
                   wire:model.blur="concept.avoidElements"
                   class="vw-input"
                   placeholder="{{ __("e.g., 'specific brand names', 'real people', 'trademarked characters'") }}">
        </div>

        {{-- Generate Button --}}
        <button class="vw-generate-btn"
                wire:click="generateIdeas"
                wire:loading.attr="disabled"
                wire:target="generateIdeas, enhanceConcept"
                @if(empty($concept['rawInput'])) disabled @endif>
            <span wire:loading.remove wire:target="generateIdeas"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Generate Unique Ideas') }}</span>
            <span wire:loading wire:target="generateIdeas">
                <span class="vw-loading-inner lg">
                    <svg style="width: 18px; height: 18px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Generating...') }}
                </span>
            </span>
        </button>
    </div>

    {{-- Your Concept Results Section --}}
    @if(!empty($conceptVariations) && count($conceptVariations) > 0)
        <div class="vw-your-concept-card">
            {{-- Header --}}
            <div class="vw-your-concept-header">
                <div class="vw-your-concept-icon"><i class="fa-solid fa-film"></i></div>
                <div>
                    <h3 class="vw-concept-title">{{ __('Your Concept') }}</h3>
                    <p class="vw-concept-subtitle">{{ __('Select a concept direction below') }}</p>
                </div>
            </div>

            {{-- Main Selected Concept Card --}}
            @php
                $selectedVariation = $conceptVariations[$selectedConceptIndex] ?? $conceptVariations[0] ?? null;
            @endphp
            @if($selectedVariation)
                <div class="vw-main-concept-card">
                    <h4 class="vw-main-concept-title">{{ $selectedVariation['title'] ?? (__($productionType ?? 'movie') . ' ' . __('Concept') . ' ' . ($selectedConceptIndex + 1)) }}</h4>
                    <p class="vw-main-concept-text">{{ $selectedVariation['concept'] ?? $concept['refinedConcept'] ?? $concept['rawInput'] }}</p>
                    <div class="vw-concept-badges">
                        @if(!empty($concept['suggestedTone']))
                            <span class="vw-concept-badge engaging"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ ucfirst($concept['suggestedTone']) }}</span>
                        @else
                            <span class="vw-concept-badge engaging"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Engaging') }}</span>
                        @endif
                        @if(!empty($concept['suggestedMood']))
                            <span class="vw-concept-badge professional"><i class="fa-solid fa-crosshairs"></i> {{ ucfirst($concept['suggestedMood']) }}</span>
                        @else
                            <span class="vw-concept-badge professional"><i class="fa-solid fa-crosshairs"></i> {{ __('Professional') }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Alternative Directions --}}
            <div class="vw-alt-directions-label">
                {{ __('Alternative Directions') }} <span style="color: var(--vw-text-muted);">({{ __('click to switch') }})</span>
            </div>
            <div class="vw-alt-directions-grid" wire:loading.class="vw-loading-opacity" wire:target="selectConceptVariation">
                @foreach($conceptVariations as $index => $variation)
                    <div class="vw-alt-card {{ $selectedConceptIndex === $index ? 'selected' : '' }}"
                         wire:click="selectConceptVariation({{ $index }})"
                         wire:loading.attr="disabled"
                         wire:target="selectConceptVariation"
                         style="{{ $selectedConceptIndex === $index ? '' : 'cursor: pointer;' }}">
                        <div class="vw-alt-card-title">{{ ($index + 1) }}. {{ $variation['title'] ?? (__($productionType ?? 'movie') . ' ' . __('Concept') . ' ' . ($index + 1)) }}</div>
                        <div class="vw-alt-card-subtitle">{{ $variation['angle'] ?? ucfirst($variation['strengths'][0] ?? __('Engaging')) }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Generate Different Concepts Button --}}
            <button class="vw-generate-different-btn"
                    wire:click="generateDifferentConcepts"
                    wire:loading.attr="disabled"
                    wire:target="generateDifferentConcepts">
                <span wire:loading.remove wire:target="generateDifferentConcepts"><i class="fa-solid fa-film"></i> {{ __('Generate Different Concepts') }}</span>
                <span wire:loading wire:target="generateDifferentConcepts">
                    <span class="vw-loading-inner">
                        <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Generating...') }}
                    </span>
                </span>
            </button>
        </div>
    @endif

    {{-- Character Intelligence UI removed in Phase 1.5 --}}
    {{-- Replaced with automatic Detection Summary --}}

    {{-- Detection Summary Panel (Phase 1.5) --}}
    @if(!empty($detectionSummary['totalSegments']))
    <div class="detection-summary-panel mt-6 p-4 bg-white/70 rounded-lg border border-base-300">
        <h4 class="text-sm font-medium text-base-content/70 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Detection Summary
            <span class="text-xs text-base-content/50 ml-auto">Auto-detected from script</span>
        </h4>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            {{-- Total Segments --}}
            <div class="bg-base-200/50 rounded p-2 text-center">
                <div class="text-lg font-semibold text-base-content">{{ $detectionSummary['totalSegments'] ?? 0 }}</div>
                <div class="text-xs text-base-content/50">Segments</div>
            </div>

            {{-- Needs Lip-sync --}}
            <div class="bg-base-200/50 rounded p-2 text-center">
                <div class="text-lg font-semibold text-primary">{{ $detectionSummary['needsLipSync'] ?? 0 }}</div>
                <div class="text-xs text-base-content/50">Lip-sync</div>
            </div>

            {{-- Voiceover Only --}}
            <div class="bg-base-200/50 rounded p-2 text-center">
                <div class="text-lg font-semibold text-primary">{{ $detectionSummary['voiceoverOnly'] ?? 0 }}</div>
                <div class="text-xs text-base-content/50">Voiceover</div>
            </div>

            {{-- Duration --}}
            <div class="bg-base-200/50 rounded p-2 text-center">
                <div class="text-lg font-semibold text-success">{{ number_format(($detectionSummary['estimatedDuration'] ?? 0) / 60, 1) }}m</div>
                <div class="text-xs text-base-content/50">Est. Duration</div>
            </div>
        </div>

        {{-- Characters Detected --}}
        @if(!empty($detectionSummary['characters']))
        <div class="border-t border-base-300 pt-3 mt-3">
            <div class="text-xs text-base-content/50 mb-2">Characters Detected</div>
            <div class="flex flex-wrap gap-2">
                @foreach($detectionSummary['characters'] as $character)
                <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs
                    {{ $character['voiceId'] ? 'bg-success/10 text-success border border-success/30' : 'bg-warning/10 text-warning border border-warning/30' }}">
                    <span>{{ $character['name'] }}</span>
                    @if($character['voiceId'])
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="text-xs text-base-content/50 mt-2">
                <span class="text-success">&#9679;</span> Voice assigned
                <span class="ml-3 text-warning">&#9679;</span> Needs voice in Character Bible
            </div>
        </div>
        @endif

        {{-- Speech Types Breakdown --}}
        @if(!empty($detectionSummary['speechTypes']))
        <div class="border-t border-base-300 pt-3 mt-3">
            <div class="text-xs text-base-content/50 mb-2">Speech Types</div>
            <div class="flex flex-wrap gap-2">
                @foreach($detectionSummary['speechTypes'] as $type => $count)
                <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs bg-base-200/50 text-base-content/70">
                    <span class="capitalize">{{ $type }}</span>
                    <span class="text-base-content/50">({{ $count }})</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
    @endif {{-- end @else (non-social content) --}}
</div>
