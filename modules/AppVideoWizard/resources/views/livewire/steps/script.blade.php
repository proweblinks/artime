{{-- Step 3: Script Generation --}}
<style>
    .vw-script-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-script-header {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-script-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-script-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-script-subtitle {
        font-size: 0.875rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        margin-top: 0.25rem !important;
    }

    /* Direct Concept Card */
    .vw-direct-concept-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(88, 28, 135, 0.2) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.5rem !important;
        position: relative !important;
    }

    .vw-direct-concept-label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        color: #f472b6 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-direct-concept-badges {
        position: absolute !important;
        top: 1rem !important;
        right: 1rem !important;
        display: flex !important;
        gap: 0.5rem !important;
        flex-wrap: wrap !important;
    }

    .vw-type-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.25rem 0.625rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
    }

    .vw-type-badge.passthrough {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
    }

    .vw-type-badge.production {
        background: rgba(139, 92, 246, 0.2) !important;
        color: #c4b5fd !important;
    }

    .vw-type-badge.subtype {
        background: rgba(236, 72, 153, 0.2) !important;
        color: #f9a8d4 !important;
    }

    .vw-direct-concept-title {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-direct-concept-text {
        color: rgba(255, 255, 255, 0.8) !important;
        line-height: 1.7 !important;
        font-size: 0.95rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-concept-meta {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 1rem !important;
    }

    .vw-concept-meta-left {
        display: flex !important;
        align-items: center !important;
        gap: 1.5rem !important;
        flex-wrap: wrap !important;
    }

    .vw-concept-meta-item {
        display: flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        font-size: 0.85rem !important;
    }

    .vw-concept-meta-item span:first-child {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .vw-concept-meta-item span:last-child {
        color: #34d399 !important;
        font-weight: 500 !important;
    }

    .vw-concept-meta-item.duration span:last-child {
        color: #f472b6 !important;
    }

    .vw-char-count {
        font-size: 0.8rem !important;
        color: #34d399 !important;
    }

    /* Selector Sections */
    .vw-selector-section {
        margin-bottom: 1.5rem !important;
    }

    .vw-selector-label {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-selector-sublabel {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        font-weight: 400 !important;
        margin-left: 0.5rem !important;
    }

    .vw-selector-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-selector-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-selector-btn {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-align: center !important;
    }

    .vw-selector-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-selector-btn.selected {
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
        color: #ffffff !important;
    }

    .vw-selector-btn-title {
        font-weight: 600 !important;
        font-size: 0.9rem !important;
    }

    .vw-selector-btn-subtitle {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.25rem !important;
    }

    .vw-selector-btn.selected .vw-selector-btn-subtitle {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    /* Additional Instructions */
    .vw-instructions-textarea {
        width: 100% !important;
        min-height: 100px !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 1rem !important;
        color: #ffffff !important;
        font-size: 0.95rem !important;
        line-height: 1.6 !important;
        resize: vertical !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }

    .vw-instructions-textarea:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-instructions-textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    /* Generate Button */
    .vw-generate-script-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        width: 100% !important;
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #06b6d4 100%) !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        border: none !important;
        cursor: pointer !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-top: 1.5rem !important;
    }

    .vw-generate-script-btn:hover:not(:disabled) {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 25px rgba(236, 72, 153, 0.4) !important;
    }

    .vw-generate-script-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-cost-estimate {
        text-align: center !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        margin-top: 0.75rem !important;
    }

    /* Loading Bar */
    .vw-loading-bar {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.75rem !important;
        background: linear-gradient(135deg, #9333ea 0%, #a855f7 100%) !important;
        padding: 1rem 1.5rem !important;
        border-radius: 0.5rem !important;
        margin-bottom: 1.5rem !important;
        color: white !important;
        font-weight: 500 !important;
    }

    .vw-spinner {
        width: 20px !important;
        height: 20px !important;
        border: 2px solid rgba(255, 255, 255, 0.3) !important;
        border-top-color: white !important;
        border-radius: 50% !important;
        animation: vw-spin 0.8s linear infinite !important;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    /* Script Results Section */
    .vw-script-results {
        margin-top: 1.5rem !important;
    }

    .vw-scene-card {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-scene-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-scene-number {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 28px !important;
        height: 28px !important;
        background: rgba(139, 92, 246, 0.3) !important;
        border-radius: 50% !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #c4b5fd !important;
        margin-right: 0.75rem !important;
    }

    .vw-scene-title {
        font-weight: 600 !important;
        color: #ffffff !important;
    }

    .vw-scene-duration {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        background: rgba(0, 0, 0, 0.3) !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 0.25rem !important;
    }

    .vw-scene-narration {
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 0.9rem !important;
        line-height: 1.6 !important;
    }
</style>

<div class="vw-script-step">
    {{-- Loading Bar --}}
    <div wire:loading wire:target="generateScript" class="vw-loading-bar">
        <span class="vw-spinner"></span>
        <span>{{ __('Generating your script...') }}</span>
    </div>

    <div class="vw-script-card">
        {{-- Error Message --}}
        @if($error)
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; color: #fca5a5;">
                <span style="margin-right: 0.5rem;">⚠️</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-script-header">
            <div class="vw-script-icon">✨</div>
            <div>
                <h2 class="vw-script-title">{{ __('Generate Your Script') }}</h2>
                <p class="vw-script-subtitle">{{ __('AI will create a professional cinematic script based on your settings') }}</p>
            </div>
        </div>

        {{-- Direct Concept Summary --}}
        @php
            $productionTypes = config('appvideowizard.production_types', []);
            $typeName = $productionTypes[$productionType]['name'] ?? ucfirst($productionType ?? 'movie');
            $subtypeName = '';
            if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype])) {
                $subtypeName = $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'];
            }
            $durationMin = floor($targetDuration / 60);
            $durationSec = $targetDuration % 60;
            $durationText = $durationMin > 0 ? ($durationMin . 'm' . ($durationSec > 0 ? ' ' . $durationSec . 's' : '')) : ($durationSec . 's');
            $conceptText = $concept['refinedConcept'] ?: $concept['rawInput'];
            $charCount = strlen($conceptText);
        @endphp

        <div class="vw-direct-concept-card">
            <div class="vw-direct-concept-badges">
                <span class="vw-type-badge passthrough">✓ {{ __('Pass-through') }}</span>
                @if($productionType)
                    <span class="vw-type-badge production">🎬 {{ $typeName }}</span>
                @endif
                @if($subtypeName)
                    <span class="vw-type-badge subtype">{{ $subtypeName }}</span>
                @endif
            </div>

            <div class="vw-direct-concept-label">
                📝 {{ __('YOUR DIRECT CONCEPT') }}
            </div>

            <h3 class="vw-direct-concept-title">{{ Str::limit($concept['logline'] ?? $typeName, 50) ?: 'A' }}</h3>

            <p class="vw-direct-concept-text">{{ $conceptText }}</p>

            <div class="vw-concept-meta">
                <div class="vw-concept-meta-left">
                    @if(!empty($concept['suggestedMood']))
                        <div class="vw-concept-meta-item">
                            <span>{{ __('Mood:') }}</span>
                            <span>{{ $concept['suggestedMood'] }}</span>
                        </div>
                    @endif
                    @if(!empty($concept['suggestedTone']))
                        <div class="vw-concept-meta-item">
                            <span>{{ __('Tone:') }}</span>
                            <span>{{ $concept['suggestedTone'] }}</span>
                        </div>
                    @endif
                    <div class="vw-concept-meta-item duration">
                        <span>{{ __('Duration:') }}</span>
                        <span>{{ $durationText }}</span>
                    </div>
                </div>
                <div class="vw-char-count">{{ $charCount }} {{ __('chars') }}</div>
            </div>
        </div>

        {{-- Script Tone Selector --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">{{ __('Script Tone') }}</div>
            <div class="vw-selector-grid">
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'engaging' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'engaging')">
                    <span class="vw-selector-btn-title">{{ __('Engaging') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'professional' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'professional')">
                    <span class="vw-selector-btn-title">{{ __('Professional') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'casual' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'casual')">
                    <span class="vw-selector-btn-title">{{ __('Casual') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'inspirational' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'inspirational')">
                    <span class="vw-selector-btn-title">{{ __('Inspirational') }}</span>
                </button>
            </div>
        </div>

        {{-- Content Depth Selector --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">
                {{ __('Content Depth') }}
                <span class="vw-selector-sublabel">— {{ __('How much detail in the narration') }}</span>
            </div>
            <div class="vw-selector-grid">
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'quick' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'quick')">
                    <span class="vw-selector-btn-title">⚡ {{ __('Quick') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Key points only') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'standard' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'standard')">
                    <span class="vw-selector-btn-title">📝 {{ __('Standard') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Balanced content') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'detailed' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'detailed')">
                    <span class="vw-selector-btn-title">📚 {{ __('Detailed') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Examples & stats') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'deep' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'deep')">
                    <span class="vw-selector-btn-title">🔬 {{ __('Deep Dive') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Full analysis') }}</span>
                </button>
            </div>
        </div>

        {{-- Additional Instructions --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">
                {{ __('Additional Instructions') }}
                <span class="vw-selector-sublabel">({{ __('optional') }})</span>
            </div>
            <textarea wire:model.blur="additionalInstructions"
                      class="vw-instructions-textarea"
                      placeholder="{{ __('Any specific requirements? e.g., Include a personal story, mention specific products, focus on beginners...') }}"></textarea>
        </div>

        {{-- Generate Button --}}
        <button class="vw-generate-script-btn"
                wire:click="$dispatch('generate-script')"
                wire:loading.attr="disabled"
                wire:target="generateScript">
            <span wire:loading.remove wire:target="generateScript">🚀 {{ __('Generate Script with AI') }}</span>
            <span wire:loading wire:target="generateScript">
                <span class="vw-spinner" style="width: 18px; height: 18px;"></span>
                {{ __('Generating...') }}
            </span>
        </button>

        <p class="vw-cost-estimate">{{ __('Estimated cost: ~5 tokens • Powered by') }} {{ get_option('ai_platform', 'GPT-4o') }}</p>
    </div>

    {{-- Script Results (shown after generation) --}}
    @if(!empty($script['scenes']) && count($script['scenes']) > 0)
        <div class="vw-script-card vw-script-results">
            <div class="vw-script-header">
                <div>
                    <h3 class="vw-script-title">{{ $script['title'] ?? __('Your Script') }}</h3>
                    <p class="vw-script-subtitle">
                        {{ count($script['scenes']) }} {{ __('scenes') }} •
                        {{ array_sum(array_column($script['scenes'], 'duration')) }}s {{ __('total') }}
                    </p>
                </div>
                <button style="background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.3); color: #c4b5fd; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem;"
                        wire:click="$dispatch('generate-script')"
                        wire:loading.attr="disabled">
                    🔄 {{ __('Regenerate') }}
                </button>
            </div>

            @if(!empty($script['hook']))
                <div style="background: rgba(236, 72, 153, 0.1); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                    <span style="color: #f472b6; font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">{{ __('Hook') }}</span>
                    <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem;">{{ $script['hook'] }}</p>
                </div>
            @endif

            @foreach($script['scenes'] as $index => $scene)
                <div class="vw-scene-card">
                    <div class="vw-scene-header">
                        <div style="display: flex; align-items: center;">
                            <span class="vw-scene-number">{{ $index + 1 }}</span>
                            <span class="vw-scene-title">{{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}</span>
                        </div>
                        <span class="vw-scene-duration">{{ $scene['duration'] ?? 15 }}s</span>
                    </div>
                    <p class="vw-scene-narration">{{ $scene['narration'] ?? '' }}</p>
                    @if(!empty($scene['visualDescription']))
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-top: 0.75rem; font-style: italic;">
                            🎬 {{ $scene['visualDescription'] }}
                        </p>
                    @endif
                </div>
            @endforeach

            @if(!empty($script['cta']))
                <div style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">
                    <span style="color: #22d3ee; font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">{{ __('Call to Action') }}</span>
                    <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem;">{{ $script['cta'] }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
