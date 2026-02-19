{{--
    Transitions Tab Content
    Scene transition settings and effects
--}}

<div class="vw-transitions-tab">
    {{-- Global Transition Setting --}}
    <div class="vw-transition-section">
        <div class="vw-section-header">
            <span>✨</span> {{ __('Global Transition') }}
        </div>
        <p class="vw-section-desc">
            {{ __('Set a default transition for all scenes') }}
        </p>

        <div class="vw-transition-grid">
            @php
                $transitions = [
                    'cut' => ['icon' => '⬛', 'label' => 'Cut'],
                    'fade' => ['icon' => '🌑', 'label' => 'Fade'],
                    'slide-left' => ['icon' => '⬅️', 'label' => 'Slide Left'],
                    'slide-right' => ['icon' => '➡️', 'label' => 'Slide Right'],
                    'zoom-in' => ['icon' => '🔍', 'label' => 'Zoom In'],
                    'zoom-out' => ['icon' => '🔎', 'label' => 'Zoom Out'],
                ];
            @endphp

            @foreach($transitions as $value => $trans)
                <button
                    type="button"
                    wire:click="setGlobalTransition('{{ $value }}')"
                    class="vw-transition-option {{ ($assembly['transitions']['global'] ?? 'fade') === $value ? 'active' : '' }}"
                >
                    <span class="vw-trans-icon">{{ $trans['icon'] }}</span>
                    <span class="vw-trans-label">{{ __($trans['label']) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Transition Duration --}}
    <div class="vw-transition-section">
        <div class="vw-section-header">
            <span>⏱️</span> {{ __('Transition Duration') }}
        </div>

        <div class="vw-duration-options">
            @foreach([0.3 => 'Fast', 0.5 => 'Normal', 0.8 => 'Slow', 1.0 => 'Very Slow'] as $duration => $label)
                <button
                    type="button"
                    wire:click="$set('assembly.transitions.duration', {{ $duration }})"
                    class="vw-duration-btn {{ ($assembly['transitions']['duration'] ?? 0.5) == $duration ? 'active' : '' }}"
                >
                    {{ __($label) }}
                    <span class="vw-duration-val">{{ $duration }}s</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Per-Scene Transitions --}}
    <div class="vw-transition-section">
        <div class="vw-section-header">
            <span>📹</span> {{ __('Scene Transitions') }}
        </div>
        <p class="vw-section-desc">
            {{ __('Override transitions for individual scenes') }}
        </p>

        <div class="vw-scene-transitions">
            @foreach($script['scenes'] ?? [] as $index => $scene)
                @if($index > 0)
                    <div class="vw-scene-trans-item">
                        <div class="vw-trans-between">
                            <span class="vw-scene-num">{{ $index }}</span>
                            <span class="vw-trans-arrow">→</span>
                            <span class="vw-scene-num">{{ $index + 1 }}</span>
                        </div>
                        <select
                            wire:model.live="assembly.transitions.scenes.{{ $index }}"
                            class="vw-trans-select"
                        >
                            <option value="">{{ __('Use Global') }}</option>
                            @foreach($transitions as $value => $trans)
                                <option value="{{ $value }}">{{ $trans['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @endforeach

            @if(count($script['scenes'] ?? []) <= 1)
                <div class="vw-empty-state">
                    <span class="vw-empty-icon">📹</span>
                    <p>{{ __('Add more scenes to customize individual transitions') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Transition Preview Hint --}}
    <div class="vw-transition-hint">
        <div class="vw-hint-icon">💡</div>
        <div class="vw-hint-text">
            {{ __('Click Preview to see transitions in action. Fade transitions work best for most videos.') }}
        </div>
    </div>
</div>

<style>
    .vw-transitions-tab {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .vw-transition-section {
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.5rem;
    }

    .vw-section-desc {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.75rem;
    }

    .vw-transition-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .vw-transition-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        padding: 0.75rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-transition-option:hover {
        border-color: rgba(3, 252, 244, 0.3);
        background: rgba(3, 252, 244, 0.1);
    }

    .vw-transition-option.active {
        border-color: #03fcf4;
        background: linear-gradient(135deg, rgba(3, 252, 244, 0.2), rgba(6, 182, 212, 0.15));
        color: white;
    }

    .vw-trans-icon {
        font-size: 1.25rem;
    }

    .vw-trans-label {
        font-size: 0.7rem;
        font-weight: 500;
    }

    .vw-duration-options {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .vw-duration-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        padding: 0.6rem;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .vw-duration-btn:hover {
        border-color: rgba(3, 252, 244, 0.3);
    }

    .vw-duration-btn.active {
        border-color: #03fcf4;
        background: rgba(3, 252, 244, 0.15);
        color: white;
    }

    .vw-duration-val {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-duration-btn.active .vw-duration-val {
        color: #67e8f9;
    }

    .vw-scene-transitions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-height: 200px;
        overflow-y: auto;
    }

    .vw-scene-trans-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.6rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.4rem;
    }

    .vw-trans-between {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-scene-num {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(3, 252, 244, 0.2);
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #67e8f9;
    }

    .vw-trans-arrow {
        color: rgba(255, 255, 255, 0.3);
        font-size: 0.75rem;
    }

    .vw-trans-select {
        padding: 0.35rem 0.5rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.3);
        color: white;
        font-size: 0.75rem;
        cursor: pointer;
    }

    .vw-empty-state {
        text-align: center;
        padding: 1.5rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-empty-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.5rem;
    }

    .vw-empty-state p {
        font-size: 0.75rem;
        margin: 0;
    }

    .vw-transition-hint {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(6, 182, 212, 0.1);
        border: 1px solid rgba(6, 182, 212, 0.2);
        border-radius: 0.5rem;
    }

    .vw-hint-icon {
        font-size: 1rem;
    }

    .vw-hint-text {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.4;
    }
</style>
