<div class="analytics-dashboard" x-data="{ showUpgrade: @entangle('showUpgradeModal') }">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between px-4 py-3 bg-white border-bottom">
        <div class="d-flex align-items-center gap-12">
            <h5 class="fw-6 mb-0 text-gray-800">
                <i class="fa-light fa-chart-mixed text-indigo-500 me-2"></i>{{ __('Analytics') }}
            </h5>
        </div>
        <div class="d-flex align-items-center gap-8">
            {{-- Date range selector --}}
            <div class="btn-group btn-group-sm" role="group">
                @foreach(['7' => '7d', '14' => '14d', '30' => '30d', '90' => '90d'] as $value => $label)
                    <button type="button"
                            wire:click="setDateRange('{{ $value }}')"
                            class="btn {{ $dateRange === (string)$value ? 'btn-dark' : 'btn-outline-secondary' }} fs-12 px-3">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Account selector (shown on platform tabs) --}}
            @if($this->platformAccounts && count($this->platformAccounts) > 1)
                <select wire:model.live="selectedAccountId" class="form-select form-select-sm w-auto fs-12">
                    @foreach($this->platformAccounts as $acct)
                        <option value="{{ $acct['id'] }}">{{ $acct['name'] }}</option>
                    @endforeach
                </select>
            @endif

            {{-- Refresh button --}}
            <button wire:click="refreshData" wire:loading.attr="disabled" class="btn btn-sm btn-outline-secondary" title="{{ __('Refresh') }}">
                <i class="fa-light fa-arrows-rotate" wire:loading.class="fa-spin"></i>
            </button>
        </div>
    </div>

    {{-- Tab navigation --}}
    <div class="d-flex align-items-center gap-4 px-4 py-2 bg-white border-bottom overflow-x-auto" style="white-space: nowrap;">
        {{-- Overview tab --}}
        <button wire:click="switchTab('overview')"
                class="btn btn-sm px-3 py-2 fs-13 {{ $activeTab === 'overview' ? 'btn-dark' : 'btn-ghost text-gray-600' }}">
            <i class="fa-light fa-grid-2 me-1"></i> {{ __('Overview') }}
        </button>

        {{-- Platform tabs --}}
        @foreach($this->availablePlatforms as $key => $platform)
            @if($platform['accessible'])
                <button wire:click="switchTab('{{ $key }}')"
                        class="btn btn-sm px-3 py-2 fs-13 {{ $activeTab === $key ? 'btn-dark' : 'btn-ghost text-gray-600' }}"
                        @if(!$platform['connected']) title="{{ __('Not connected') }}" @endif>
                    <i class="{{ $platform['icon'] }} me-1" style="color: {{ $activeTab === $key ? '#fff' : $platform['color'] }}"></i>
                    {{ $platform['name'] }}
                    @if(!$platform['connected'])
                        <i class="fa-light fa-circle-exclamation text-warning ms-1 fs-11" title="{{ __('No account connected') }}"></i>
                    @endif
                </button>
            @else
                <button wire:click="showUpgradePrompt('{{ $key }}')"
                        class="btn btn-sm px-3 py-2 fs-13 btn-ghost text-gray-400">
                    <i class="{{ $platform['icon'] }} me-1"></i>
                    {{ $platform['name'] }}
                    <i class="fa-light fa-lock ms-1 fs-11"></i>
                </button>
            @endif
        @endforeach

        {{-- AI Insights tab --}}
        @if($this->canAccessAIInsights())
            <button wire:click="switchTab('ai_insights')"
                    class="btn btn-sm px-3 py-2 fs-13 {{ $activeTab === 'ai_insights' ? 'btn-dark' : 'btn-ghost text-gray-600' }}">
                <i class="fa-light fa-sparkles me-1 {{ $activeTab === 'ai_insights' ? '' : 'text-amber-500' }}"></i> {{ __('AI Insights') }}
            </button>
        @endif
    </div>

    {{-- Content area --}}
    <div class="px-4 py-4" style="min-height: 500px;">
        {{-- Loading state --}}
        <div wire:loading.flex class="align-items-center justify-content-center py-5">
            <div class="spinner-border text-indigo-500" role="status">
                <span class="visually-hidden">{{ __('Loading...') }}</span>
            </div>
            <span class="ms-3 text-gray-500 fs-14">{{ __('Loading analytics data...') }}</span>
        </div>

        {{-- Error state --}}
        @if($errorMessage)
            <div class="alert alert-warning d-flex align-items-center gap-8 fs-14" wire:loading.remove>
                <i class="fa-light fa-triangle-exclamation"></i>
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Tab content --}}
        <div wire:loading.remove>
            @if($activeTab === 'overview')
                @include('appanalytics::livewire.partials._overview')
            @elseif($activeTab === 'ai_insights')
                @include('appanalytics::livewire.partials._ai-insights')
            @elseif($activeTab === 'facebook')
                @include('appanalytics::livewire.partials._facebook')
            @elseif($activeTab === 'instagram')
                @include('appanalytics::livewire.partials._instagram')
            @elseif($activeTab === 'youtube')
                @include('appanalytics::livewire.partials._youtube')
            @elseif($activeTab === 'linkedin')
                @include('appanalytics::livewire.partials._linkedin')
            @endif
        </div>
    </div>

    {{-- Upgrade Modal --}}
    @if($showUpgradeModal)
        @include('appanalytics::livewire.partials._locked-tab')
    @endif
</div>
