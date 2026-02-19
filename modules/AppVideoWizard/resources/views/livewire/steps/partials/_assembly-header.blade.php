{{--
    Assembly Studio Header Component
    Top bar with project info, stats, and action buttons
--}}

<div class="vw-fullscreen-header">
    {{-- Left: Logo & Project Name --}}
    <div class="vw-header-left">
        <div class="vw-header-logo">
            <span class="vw-logo-icon">🎬</span>
            <span class="vw-logo-text">Video Creation Wizard</span>
        </div>
        <div class="vw-header-divider"></div>
        <div class="vw-project-name" title="{{ $projectName ?? 'Untitled Video' }}">
            {{ Str::limit($projectName ?? 'Untitled Video', 30) }}
        </div>
    </div>

    {{-- Center: Stats --}}
    <div class="vw-header-center">
        <div class="vw-header-stats">
            <span class="vw-stat">
                <span class="vw-stat-icon">📹</span>
                <span class="vw-stat-value">{{ count($script['scenes'] ?? []) }}</span>
                <span class="vw-stat-label">{{ __('scenes') }}</span>
            </span>
            <span class="vw-stat-sep">•</span>
            <span class="vw-stat">
                <span class="vw-stat-icon">⏱️</span>
                <span class="vw-stat-value" x-text="formatTime(totalDuration)">0:00</span>
            </span>
            <span class="vw-stat-sep">•</span>
            <span class="vw-stat">
                <span class="vw-stat-icon">📐</span>
                <span class="vw-stat-value">{{ $aspectRatio }}</span>
            </span>
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="vw-header-right">
        <button
            type="button"
            wire:click="saveProject"
            class="vw-header-btn secondary"
            title="{{ __('Save Project') }}"
        >
            <span>💾</span>
            <span class="vw-btn-text">{{ __('Save') }}</span>
        </button>

        <button
            type="button"
            wire:click="previousStep"
            class="vw-header-btn secondary"
            title="{{ __('Back to Animation') }}"
        >
            <span>←</span>
            <span class="vw-btn-text">{{ __('Back') }}</span>
        </button>

        <button
            type="button"
            @click="$dispatch('open-export-modal')"
            class="vw-header-btn primary"
            title="{{ __('Export Video') }}"
        >
            <span>🚀</span>
            <span class="vw-btn-text">{{ __('Export') }}</span>
        </button>
    </div>
</div>

<style>
    .vw-fullscreen-header {
        height: 56px;
        min-height: 56px;
        background: rgba(20, 20, 30, 0.98);
        border-bottom: 1px solid rgba(3, 252, 244, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        backdrop-filter: blur(10px);
    }

    .vw-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .vw-header-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-logo-icon {
        font-size: 1.25rem;
    }

    .vw-logo-text {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
    }

    .vw-header-divider {
        width: 1px;
        height: 24px;
        background: rgba(255, 255, 255, 0.15);
    }

    .vw-project-name {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .vw-header-center {
        display: flex;
        align-items: center;
    }

    .vw-header-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.4rem 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-stat {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
    }

    .vw-stat-icon {
        font-size: 0.75rem;
    }

    .vw-stat-value {
        color: white;
        font-weight: 600;
    }

    .vw-stat-label {
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-stat-sep {
        color: rgba(255, 255, 255, 0.2);
    }

    .vw-header-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-header-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .vw-header-btn > span:first-child {
        flex-shrink: 0;
        line-height: 1;
    }

    .vw-header-btn.secondary {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: white;
    }

    .vw-header-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .vw-header-btn.primary {
        background: linear-gradient(135deg, #03fcf4, #06b6d4);
        color: #0a2e2e;
        box-shadow: 0 2px 10px rgba(3, 252, 244, 0.3);
    }

    .vw-header-btn.primary:hover {
        box-shadow: 0 4px 15px rgba(3, 252, 244, 0.4);
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .vw-btn-text {
            display: none;
        }

        .vw-header-btn {
            padding: 0.5rem 0.75rem;
        }

        .vw-logo-text {
            display: none;
        }

        .vw-project-name {
            max-width: 100px;
        }
    }
</style>
