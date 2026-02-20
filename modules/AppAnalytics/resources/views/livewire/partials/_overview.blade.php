{{-- Cross-platform overview --}}
<div class="row g-3 mb-4">
    {{-- KPI Cards --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-none border-gray-200 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-500 fs-12 fw-5">{{ __('Total Followers') }}</span>
                    <i class="fa-light fa-users text-indigo-400 fs-16"></i>
                </div>
                <div class="fw-7 fs-22 text-gray-800">{{ number_format($overviewData['total_followers'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-none border-gray-200 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-500 fs-12 fw-5">{{ __('Total Reach') }}</span>
                    <i class="fa-light fa-eye text-blue-400 fs-16"></i>
                </div>
                <div class="fw-7 fs-22 text-gray-800">{{ number_format($overviewData['total_reach'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-none border-gray-200 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-500 fs-12 fw-5">{{ __('Total Engagement') }}</span>
                    <i class="fa-light fa-heart text-rose-400 fs-16"></i>
                </div>
                <div class="fw-7 fs-22 text-gray-800">{{ number_format($overviewData['total_engagement'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-none border-gray-200 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-500 fs-12 fw-5">{{ __('Platforms') }}</span>
                    <i class="fa-light fa-globe text-emerald-400 fs-16"></i>
                </div>
                <div class="fw-7 fs-22 text-gray-800">{{ count($overviewData['platforms'] ?? []) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Per-platform breakdown --}}
@if(!empty($overviewData['platforms']))
    <div class="card shadow-none border-gray-200 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-6 mb-0 text-gray-700">{{ __('Platform Breakdown') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 fs-13">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-0 py-2 px-3 text-gray-500 fw-5">{{ __('Platform') }}</th>
                            <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Followers') }}</th>
                            <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Reach') }}</th>
                            <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Engagement') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overviewData['platforms'] as $platform => $data)
                            @php
                                $config = $this->availablePlatforms[$platform] ?? [];
                            @endphp
                            <tr class="cursor-pointer" wire:click="switchTab('{{ $platform }}')">
                                <td class="py-2 px-3">
                                    <div class="d-flex align-items-center gap-8">
                                        <i class="{{ $config['icon'] ?? '' }}" style="color: {{ $config['color'] ?? '#666' }}"></i>
                                        <span class="fw-5">{{ $config['name'] ?? ucfirst($platform) }}</span>
                                        @if(($data['accounts'] ?? 1) > 1)
                                            <span class="text-gray-400 fs-12">{{ $data['accounts'] }} {{ __('accounts') }}</span>
                                        @elseif($data['name'] ?? '')
                                            <span class="text-gray-400 fs-12">{{ $data['name'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-end fw-5">{{ number_format($data['followers'] ?? 0) }}</td>
                                <td class="py-2 px-3 text-end fw-5">{{ number_format($data['reach'] ?? 0) }}</td>
                                <td class="py-2 px-3 text-end fw-5">{{ number_format($data['engagement'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="text-center py-5">
        <i class="fa-light fa-chart-mixed fs-48 text-gray-300 mb-3 d-block"></i>
        <h6 class="text-gray-500 fw-5">{{ __('No analytics data yet') }}</h6>
        <p class="text-gray-400 fs-13 mb-3">{{ __('Connect your social media accounts in Channels to start seeing analytics.') }}</p>
        <a href="{{ url('app/channels') }}" class="btn btn-sm btn-dark">
            <i class="fa-light fa-plus me-1"></i> {{ __('Connect Channels') }}
        </a>
    </div>
@endif
