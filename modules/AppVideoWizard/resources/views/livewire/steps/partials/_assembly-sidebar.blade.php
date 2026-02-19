{{--
    Assembly Studio Sidebar Component
    Navigation and quick actions panel
--}}

<div class="vw-sidebar">
    {{-- Project Info --}}
    <div class="vw-sidebar-section project-info">
        <div class="vw-sidebar-label">{{ __('Project') }}</div>
        <div class="vw-sidebar-value">{{ Str::limit($projectName ?? 'Untitled', 20) }}</div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="vw-sidebar-nav">
        <button
            type="button"
            @click="activeTab = 'scenes'"
            :class="{ 'active': activeTab === 'scenes' }"
            class="vw-nav-btn"
        >
            <span class="vw-nav-icon">📹</span>
            <span class="vw-nav-label">{{ __('Scenes') }}</span>
            <span class="vw-nav-badge">{{ count($script['scenes'] ?? []) }}</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'text'"
            :class="{ 'active': activeTab === 'text' }"
            class="vw-nav-btn"
        >
            <span class="vw-nav-icon">💬</span>
            <span class="vw-nav-label">{{ __('Captions') }}</span>
            <span x-show="captionsEnabled" class="vw-nav-dot green"></span>
        </button>

        <button
            type="button"
            @click="activeTab = 'audio'"
            :class="{ 'active': activeTab === 'audio' }"
            class="vw-nav-btn"
        >
            <span class="vw-nav-icon">🎵</span>
            <span class="vw-nav-label">{{ __('Audio') }}</span>
            <span x-show="musicEnabled" class="vw-nav-dot green"></span>
        </button>

        <button
            type="button"
            @click="activeTab = 'transitions'"
            :class="{ 'active': activeTab === 'transitions' }"
            class="vw-nav-btn"
        >
            <span class="vw-nav-icon">✨</span>
            <span class="vw-nav-label">{{ __('Transitions') }}</span>
        </button>
    </div>

    {{-- Spacer --}}
    <div class="vw-sidebar-spacer"></div>

    {{-- Quick Actions --}}
    <div class="vw-sidebar-section quick-actions">
        <div class="vw-sidebar-label">{{ __('Quick Actions') }}</div>

        <button
            type="button"
            @click="loadPreview()"
            class="vw-quick-btn preview"
            :disabled="isLoading"
        >
            <span x-text="isLoading ? '...' : '▶'"></span>
            <span x-text="isLoading ? '{{ __('Loading') }}' : (isReady ? '{{ __('Reload') }}' : '{{ __('Preview') }}')"></span>
        </button>

        <button
            type="button"
            @click="$dispatch('open-export-modal')"
            class="vw-quick-btn export"
        >
            <span>🚀</span>
            <span>{{ __('Export') }}</span>
        </button>
    </div>

    {{-- Duration Display --}}
    <div class="vw-sidebar-section duration-display">
        <div class="vw-duration-label">{{ __('Total Duration') }}</div>
        <div class="vw-duration-value" x-text="formatTime(totalDuration)">0:00</div>
    </div>

    {{-- Status Indicator --}}
    <div class="vw-sidebar-status" :class="{ 'ready': isReady }">
        <span class="vw-status-dot"></span>
        <span class="vw-status-text" x-text="isReady ? '{{ __('Ready') }}' : '{{ __('Not loaded') }}'"></span>
    </div>
</div>

<style>
    .vw-sidebar {
        width: 200px;
        min-width: 200px;
        background: rgba(15, 15, 25, 0.98);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        flex-direction: column;
        padding: 1rem 0;
    }

    .vw-sidebar-section {
        padding: 0.75rem 1rem;
    }

    .vw-sidebar-section.project-info {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 0.5rem;
    }

    .vw-sidebar-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.35rem;
    }

    .vw-sidebar-value {
        font-size: 0.85rem;
        color: white;
        font-weight: 500;
    }

    .vw-sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0 0.5rem;
    }

    .vw-nav-btn {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.5rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
        width: 100%;
    }

    .vw-nav-btn:hover {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-nav-btn.active {
        background: linear-gradient(135deg, rgba(3, 252, 244, 0.2), rgba(6, 182, 212, 0.15));
        color: white;
        border-left: 3px solid #03fcf4;
    }

    .vw-nav-icon {
        font-size: 1rem;
        width: 24px;
        text-align: center;
    }

    .vw-nav-label {
        flex: 1;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .vw-nav-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        background: rgba(3, 252, 244, 0.3);
        color: #67e8f9;
        border-radius: 0.25rem;
        font-weight: 600;
    }

    .vw-nav-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .vw-nav-dot.green {
        background: #10b981;
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
    }

    .vw-sidebar-spacer {
        flex: 1;
    }

    .vw-sidebar-section.quick-actions {
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        padding-top: 1rem;
    }

    .vw-quick-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.6rem;
        border-radius: 0.5rem;
        border: none;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 0.5rem;
    }

    .vw-quick-btn.preview {
        background: rgba(3, 252, 244, 0.2);
        border: 1px solid rgba(3, 252, 244, 0.3);
        color: #67e8f9;
    }

    .vw-quick-btn.preview:hover:not(:disabled) {
        background: rgba(3, 252, 244, 0.3);
    }

    .vw-quick-btn.preview:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .vw-quick-btn.export {
        background: linear-gradient(135deg, #03fcf4, #06b6d4);
        color: #0a2e2e;
    }

    .vw-quick-btn.export:hover {
        box-shadow: 0 2px 10px rgba(3, 252, 244, 0.4);
    }

    .vw-sidebar-section.duration-display {
        text-align: center;
        padding: 1rem;
        margin: 0 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
    }

    .vw-duration-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.25rem;
    }

    .vw-duration-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        font-family: 'SF Mono', Monaco, monospace;
    }

    .vw-sidebar-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        margin: 0.75rem;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: 0.5rem;
    }

    .vw-sidebar-status.ready {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .vw-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #f59e0b;
    }

    .vw-sidebar-status.ready .vw-status-dot {
        background: #10b981;
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
    }

    .vw-status-text {
        font-size: 0.75rem;
        color: #f59e0b;
        font-weight: 500;
    }

    .vw-sidebar-status.ready .vw-status-text {
        color: #10b981;
    }

    @media (max-width: 1200px) {
        .vw-sidebar {
            width: 180px;
            min-width: 180px;
        }
    }

    @media (max-width: 768px) {
        .vw-sidebar {
            display: none;
        }
    }
</style>
