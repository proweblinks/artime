{{-- Prompt Comparison Component
     Shows expanded Hollywood prompt by default, with toggle to reveal original.
     Responsive: side-by-side on wide screens (>1200px), stacked on narrow.

     Expected variables:
     - $originalPrompt: string - Brief original prompt
     - $expandedPrompt: string - Hollywood-quality expanded prompt
     - $expansionMethod: string - 'llm' or 'template'
--}}

@php
    // Calculate word counts
    $originalWords = str_word_count($originalPrompt ?? '');
    $expandedWords = str_word_count($expandedPrompt ?? '');

    // Calculate character counts
    $originalChars = strlen($originalPrompt ?? '');
    $expandedChars = strlen($expandedPrompt ?? '');

    // Estimate tokens (rough: words * 1.3)
    $originalTokens = round($originalWords * 1.3);
    $expandedTokens = round($expandedWords * 1.3);

    // Expansion ratio
    $expansionRatio = $originalWords > 0 ? round($expandedWords / $originalWords, 1) : 0;
@endphp

<div class="vw-prompt-comparison" x-data="{ showPrompt: false, showOriginal: false }">
    {{-- Compact prompt summary — always visible --}}
    <div class="vw-prompt-summary">
        @if($expansionMethod === 'llm')
            <span class="vw-prompt-method-badge vw-prompt-method-llm" title="{{ __('LLM-expanded') }}">
                <i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('AI') }}
            </span>
        @else
            <span class="vw-prompt-method-badge vw-prompt-method-template" title="{{ __('Template-expanded') }}">
                <i class="fa-solid fa-file-lines"></i> {{ __('Template') }}
            </span>
        @endif
        <span class="vw-prompt-stat-value" style="font-size: 0.65rem;">{{ $expandedWords }} {{ __('words') }}</span>
        @if($expansionRatio > 1)
            <span class="vw-prompt-expansion-badge" title="{{ __('Expansion ratio') }}">{{ $expansionRatio }}x</span>
        @endif
        <span style="flex: 1;"></span>
        <button type="button"
                class="vw-prompt-toggle"
                @click="showPrompt = !showPrompt"
                :class="{ 'active': showPrompt }">
            <i class="fa-solid" :class="showPrompt ? 'fa-eye-slash' : 'fa-eye'"></i>
            <span x-text="showPrompt ? '{{ __('Hide') }}' : '{{ __('Show prompt') }}'"></span>
        </button>
    </div>

    {{-- Expandable prompt details --}}
    <div x-show="showPrompt" x-collapse x-cloak>
        {{-- Full stats --}}
        <div class="vw-prompt-stats">
            <span class="vw-prompt-stat">
                <span class="vw-prompt-stat-label">{{ __('Words') }}</span>
                <span class="vw-prompt-stat-value">{{ $originalWords }} &rarr; {{ $expandedWords }}</span>
            </span>
            <span class="vw-prompt-stat">
                <span class="vw-prompt-stat-label">{{ __('Chars') }}</span>
                <span class="vw-prompt-stat-value">{{ number_format($originalChars) }} &rarr; {{ number_format($expandedChars) }}</span>
            </span>
            <span class="vw-prompt-stat">
                <span class="vw-prompt-stat-label">{{ __('Tokens') }}</span>
                <span class="vw-prompt-stat-value">~{{ $originalTokens }} &rarr; ~{{ $expandedTokens }}</span>
            </span>
        </div>

        {{-- Toggle original --}}
        <button type="button"
                class="vw-prompt-toggle"
                @click="showOriginal = !showOriginal"
                :class="{ 'active': showOriginal }"
                style="margin: 0.25rem 0;">
            <i class="fa-solid" :class="showOriginal ? 'fa-eye-slash' : 'fa-eye'"></i>
            <span x-text="showOriginal ? '{{ __('Hide original') }}' : '{{ __('Show original') }}'"></span>
        </button>

        {{-- Prompt Display Area --}}
        <div class="vw-prompt-display" :class="{ 'comparison-mode': showOriginal }">
            <div class="vw-prompt-panel vw-prompt-expanded">
                <div class="vw-prompt-panel-header">
                    <span class="vw-prompt-panel-label">{{ __('Hollywood Prompt') }}</span>
                </div>
                <div class="vw-prompt-panel-content">
                    {{ $expandedPrompt }}
                </div>
            </div>
            <div class="vw-prompt-panel vw-prompt-original" x-show="showOriginal" x-transition.opacity>
                <div class="vw-prompt-panel-header">
                    <span class="vw-prompt-panel-label">{{ __('Original') }}</span>
                </div>
                <div class="vw-prompt-panel-content">
                    {{ $originalPrompt }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .vw-prompt-comparison {
        border-top: 1px solid var(--at-border, rgba(255, 255, 255, 0.05));
        padding: 0.5rem 0.75rem;
    }

    .vw-prompt-summary {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-prompt-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.5rem;
        margin-top: 0.5rem;
    }

    .vw-prompt-stat {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.65rem;
    }

    .vw-prompt-stat-label {
        color: var(--at-text-muted, rgba(255, 255, 255, 0.4));
    }

    .vw-prompt-stat-value {
        color: var(--at-text, rgba(255, 255, 255, 0.7));
        font-family: monospace;
    }

    .vw-prompt-expansion-badge {
        padding: 0.15rem 0.4rem;
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-weight: 600;
    }

    .vw-prompt-method-badge {
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.6rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-prompt-method-llm {
        background: rgba(3, 252, 244, 0.15);
        color: #67e8f9;
    }

    .vw-prompt-method-template {
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
    }

    .vw-prompt-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.5rem;
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--at-border, rgba(255, 255, 255, 0.1));
        border-radius: 0.3rem;
        color: var(--at-text-muted, rgba(255, 255, 255, 0.6));
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .vw-prompt-toggle:hover {
        background: rgba(0,0,0,0.06);
        color: var(--at-text, rgba(255, 255, 255, 0.8));
    }

    .vw-prompt-toggle.active {
        background: rgba(3, 252, 244, 0.1);
        border-color: rgba(3, 252, 244, 0.25);
        color: var(--at-primary, #67e8f9);
    }

    .vw-prompt-display {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    /* Responsive side-by-side layout */
    @media (min-width: 1200px) {
        .vw-prompt-display.comparison-mode {
            flex-direction: row;
        }

        .vw-prompt-display.comparison-mode .vw-prompt-panel {
            flex: 1;
        }
    }

    .vw-prompt-panel {
        background: rgba(0, 0, 0, 0.02);
        border: 1px solid var(--at-border, rgba(255, 255, 255, 0.05));
        border-radius: 0.4rem;
        overflow: hidden;
    }

    .vw-prompt-panel-header {
        padding: 0.35rem 0.5rem;
        background: rgba(0, 0, 0, 0.02);
        border-bottom: 1px solid var(--at-border, rgba(255, 255, 255, 0.05));
    }

    .vw-prompt-panel-label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--at-text-muted, rgba(255, 255, 255, 0.4));
        font-weight: 600;
    }

    .vw-prompt-panel-content {
        padding: 0.5rem;
        font-size: 0.7rem;
        color: var(--at-text, rgba(255, 255, 255, 0.7));
        line-height: 1.5;
        max-height: 150px;
        overflow-y: auto;
    }

    .vw-prompt-expanded .vw-prompt-panel-label {
        color: var(--at-primary, rgba(3, 252, 244, 0.7));
    }

    .vw-prompt-original .vw-prompt-panel-label {
        color: #f97316;
    }

    .vw-prompt-original {
        border-color: rgba(249, 115, 22, 0.2);
    }
</style>
