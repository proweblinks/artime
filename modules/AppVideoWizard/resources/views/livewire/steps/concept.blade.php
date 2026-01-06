{{-- Step 2: Concept Development --}}
<style>
    .vw-concept-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-concept-header {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-concept-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-concept-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-concept-subtitle {
        font-size: 0.875rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        margin-top: 0.25rem !important;
    }

    .vw-context-bar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-context-left {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 0.875rem !important;
    }

    .vw-context-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: rgba(139, 92, 246, 0.3) !important;
        color: #c4b5fd !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
    }

    .vw-context-badge.accent {
        background: rgba(236, 72, 153, 0.3) !important;
        color: #f9a8d4 !important;
    }

    .vw-context-arrow {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-context-duration {
        color: #34d399 !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
    }

    .vw-field-label {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-textarea {
        width: 100% !important;
        min-height: 140px !important;
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

    .vw-textarea:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-input {
        width: 100% !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 0.875rem 1rem !important;
        color: #ffffff !important;
        font-size: 0.95rem !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }

    .vw-input:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-input::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-enhance-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
        color: white !important;
        padding: 0.75rem 1.25rem !important;
        border-radius: 0.5rem !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        border: none !important;
        cursor: pointer !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-top: 0.75rem !important;
    }

    .vw-enhance-btn:hover:not(:disabled) {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4) !important;
    }

    .vw-enhance-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-enhance-hint {
        display: inline-block !important;
        margin-left: 0.75rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        font-size: 0.8rem !important;
    }

    .vw-field-group {
        margin-bottom: 1.5rem !important;
    }

    .vw-field-note {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-bottom: 0.5rem !important;
    }

    .vw-field-note .warning {
        color: #fbbf24 !important;
    }

    .vw-field-helper {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        margin-top: 0.5rem !important;
    }

    .vw-generate-btn {
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
        margin-top: 1rem !important;
    }

    .vw-generate-btn:hover:not(:disabled) {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 25px rgba(236, 72, 153, 0.4) !important;
    }

    .vw-generate-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-divider {
        height: 1px !important;
        background: rgba(255, 255, 255, 0.1) !important;
        margin: 1.5rem 0 !important;
    }

    /* Refined Concept Results */
    .vw-result-card {
        background: rgba(16, 185, 129, 0.1) !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-top: 1.5rem !important;
    }

    .vw-result-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        color: #34d399 !important;
        font-weight: 600 !important;
        margin-bottom: 1rem !important;
    }

    .vw-result-text {
        color: rgba(255, 255, 255, 0.85) !important;
        line-height: 1.7 !important;
        white-space: pre-wrap !important;
    }

    .vw-badges {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
        margin-top: 1rem !important;
    }

    .vw-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 0.25rem 0.75rem !important;
        background: rgba(139, 92, 246, 0.2) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 1rem !important;
        font-size: 0.8rem !important;
        color: #c4b5fd !important;
    }
</style>

<div class="vw-concept-step">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; color: #fca5a5;">
                <span style="margin-right: 0.5rem;">⚠️</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-concept-header">
            <div class="vw-concept-icon">💡</div>
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
                        🎬 {{ $typeName }}
                    </span>
                    @if($subtypeName)
                        <span class="vw-context-arrow">→</span>
                        <span class="vw-context-badge accent">
                            🎯 {{ $subtypeName }}
                        </span>
                    @endif
                </div>
                <div class="vw-context-duration">{{ $durationText }}</div>
            </div>
        @endif

        {{-- Main Input --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __("What's your video about?") }}</label>
            <textarea wire:model.blur="concept.rawInput"
                      class="vw-textarea"
                      placeholder="{{ __("Describe your idea, theme, or story... Be creative! Examples:
• A mysterious figure discovers an ancient power
• The untold story of a small town's greatest secret
• A journey through impossible landscapes
• An entrepreneur's rise from nothing") }}"></textarea>

            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;">
                <button class="vw-enhance-btn"
                        wire:click="enhanceConcept"
                        wire:loading.attr="disabled"
                        wire:target="enhanceConcept">
                    <span wire:loading.remove wire:target="enhanceConcept">✨ {{ __('Enhance with AI') }}</span>
                    <span wire:loading wire:target="enhanceConcept">
                        <span class="vw-spinner" style="width: 14px; height: 14px; border-width: 2px;"></span>
                        {{ __('Enhancing...') }}
                    </span>
                </button>
                <span class="vw-enhance-hint">{{ __('Auto-extracts styles & fills all fields') }}</span>
            </div>
        </div>

        {{-- AI Enhanced Results --}}
        @if(!empty($concept['refinedConcept']))
            <div class="vw-result-card">
                <div class="vw-result-header">
                    ✨ {{ __('AI-Enhanced Concept') }}
                </div>
                <p class="vw-result-text">{{ $concept['refinedConcept'] }}</p>

                @if(!empty($concept['logline']))
                    <div class="vw-divider"></div>
                    <div style="margin-top: 1rem;">
                        <span style="color: rgba(255,255,255,0.7); font-weight: 600;">{{ __('Logline:') }}</span>
                        <p style="color: rgba(255,255,255,0.6); font-style: italic; margin-top: 0.25rem;">{{ $concept['logline'] }}</p>
                    </div>
                @endif

                <div class="vw-badges">
                    @if(!empty($concept['suggestedMood']))
                        <span class="vw-badge">{{ __('Mood:') }} {{ $concept['suggestedMood'] }}</span>
                    @endif
                    @if(!empty($concept['suggestedTone']))
                        <span class="vw-badge">{{ __('Tone:') }} {{ $concept['suggestedTone'] }}</span>
                    @endif
                    @if(!empty($concept['keyElements']))
                        @foreach($concept['keyElements'] as $element)
                            <span class="vw-badge">{{ $element }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        <div class="vw-divider"></div>

        {{-- Style Inspiration --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __('Style Inspiration (Optional)') }}</label>
            <div class="vw-field-note">
                <span class="warning">⚠️</span>
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
                wire:target="generateIdeas"
                @if(empty($concept['rawInput'])) disabled @endif>
            <span wire:loading.remove wire:target="generateIdeas">✨ {{ __('Generate Unique Ideas') }}</span>
            <span wire:loading wire:target="generateIdeas">
                <span class="vw-spinner" style="width: 18px; height: 18px; border-width: 2px;"></span>
                {{ __('Generating...') }}
            </span>
        </button>
    </div>
</div>
