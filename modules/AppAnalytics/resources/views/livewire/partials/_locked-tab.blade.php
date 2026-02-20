{{-- Upgrade Modal --}}
<div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
     style="background: rgba(0,0,0,0.4); z-index: 1050;"
     x-show="showUpgrade"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="$wire.closeUpgradeModal()">

    <div class="bg-white rounded-4 shadow-lg p-5 text-center" style="max-width: 420px; width: 90%;">
        @php
            $platformInfo = match($upgradePlatform) {
                'facebook' => ['icon' => 'fa-brands fa-facebook', 'color' => '#1877F2', 'name' => 'Facebook'],
                'instagram' => ['icon' => 'fa-brands fa-instagram', 'color' => '#E4405F', 'name' => 'Instagram'],
                'youtube' => ['icon' => 'fa-brands fa-youtube', 'color' => '#FF0000', 'name' => 'YouTube'],
                'linkedin' => ['icon' => 'fa-brands fa-linkedin', 'color' => '#0A66C2', 'name' => 'LinkedIn'],
                'tiktok' => ['icon' => 'fa-brands fa-tiktok', 'color' => '#010101', 'name' => 'TikTok'],
                'x' => ['icon' => 'fa-brands fa-x-twitter', 'color' => '#000000', 'name' => 'X'],
                default => ['icon' => 'fa-light fa-chart-mixed', 'color' => '#6366F1', 'name' => ucfirst($upgradePlatform)],
            };
        @endphp

        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
             style="width: 64px; height: 64px; background: {{ $platformInfo['color'] }}15;">
            <i class="{{ $platformInfo['icon'] }} fs-28" style="color: {{ $platformInfo['color'] }}"></i>
        </div>

        <h5 class="fw-7 text-gray-800 mb-2">{{ __('Unlock :platform Analytics', ['platform' => $platformInfo['name']]) }}</h5>
        <p class="text-gray-500 fs-13 mb-4">
            {{ __('Get detailed insights into your :platform performance including engagement metrics, top posts, audience demographics, and AI-powered recommendations.', ['platform' => $platformInfo['name']]) }}
        </p>

        <div class="d-flex gap-8 justify-content-center">
            <button wire:click="closeUpgradeModal" class="btn btn-sm btn-outline-secondary px-4">
                {{ __('Maybe Later') }}
            </button>
            <a href="{{ url('app/profile#plans') }}" class="btn btn-sm btn-dark px-4">
                <i class="fa-light fa-arrow-up-right me-1"></i> {{ __('Upgrade Plan') }}
            </a>
        </div>
    </div>
</div>
