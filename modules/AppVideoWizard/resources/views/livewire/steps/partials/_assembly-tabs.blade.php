{{--
    Assembly Studio Tabbed Panel Component
    Contains tabs for Scenes, Text, Audio, Transitions
--}}

<div class="vw-tabs-panel">
    {{-- Tab Headers --}}
    <div class="vw-tabs-header">
        <button
            type="button"
            @click="activeTab = 'scenes'"
            :class="{ 'active': activeTab === 'scenes' }"
            class="vw-tab-btn"
        >
            {{ __('Scenes') }}
        </button>
        <button
            type="button"
            @click="activeTab = 'text'"
            :class="{ 'active': activeTab === 'text' }"
            class="vw-tab-btn"
        >
            {{ __('TEXT') }}
        </button>
        <button
            type="button"
            @click="activeTab = 'audio'"
            :class="{ 'active': activeTab === 'audio' }"
            class="vw-tab-btn"
        >
            {{ __('Audio') }}
        </button>
        <button
            type="button"
            @click="activeTab = 'transitions'"
            :class="{ 'active': activeTab === 'transitions' }"
            class="vw-tab-btn"
        >
            {{ __('Transitions') }}
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="vw-tabs-content">
        {{-- Scenes Tab --}}
        <div x-show="activeTab === 'scenes'" x-cloak class="vw-tab-pane">
            @include('appvideowizard::livewire.steps.partials._tab-scenes')
        </div>

        {{-- Text/Captions Tab --}}
        <div x-show="activeTab === 'text'" x-cloak class="vw-tab-pane">
            @include('appvideowizard::livewire.steps.partials._tab-text')
        </div>

        {{-- Audio Tab --}}
        <div x-show="activeTab === 'audio'" x-cloak class="vw-tab-pane">
            @include('appvideowizard::livewire.steps.partials._tab-audio')
        </div>

        {{-- Transitions Tab --}}
        <div x-show="activeTab === 'transitions'" x-cloak class="vw-tab-pane">
            @include('appvideowizard::livewire.steps.partials._tab-transitions')
        </div>
    </div>
</div>

<style>
    .vw-tabs-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: rgba(20, 20, 30, 0.95);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-tabs-header {
        display: flex;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-tab-btn {
        flex: 1;
        padding: 0.85rem 1rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        cursor: pointer;
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
    }

    .vw-tab-btn:hover {
        color: rgba(255, 255, 255, 0.8);
        background: rgba(255, 255, 255, 0.03);
    }

    .vw-tab-btn.active {
        color: white;
        border-bottom-color: #03fcf4;
        background: rgba(3, 252, 244, 0.1);
    }

    .vw-tabs-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .vw-tab-pane {
        height: 100%;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
