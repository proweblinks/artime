{{-- AI Insights Tab --}}
<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h6 class="fw-6 text-gray-700 mb-1">{{ __('AI-Powered Insights') }}</h6>
            <p class="text-gray-400 fs-12 mb-0">{{ __('Get smart recommendations and performance analysis powered by AI.') }}</p>
        </div>
        <button wire:click="generateAIInsights" wire:loading.attr="disabled" class="btn btn-sm btn-dark">
            <i class="fa-light fa-sparkles me-1" wire:loading.class="fa-spin" wire:target="generateAIInsights"></i>
            {{ __('Generate Insights') }}
        </button>
    </div>

    @if(!empty($aiInsights))
        <div class="row g-3">
            @foreach($aiInsights as $insight)
                <div class="col-12">
                    <div class="card shadow-none border-gray-200">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-8">
                                @php
                                    $typeConfig = match($insight['insight_type'] ?? '') {
                                        'weekly_summary' => ['icon' => 'fa-chart-line', 'color' => 'text-indigo-500', 'label' => __('Weekly Summary')],
                                        'content_tips' => ['icon' => 'fa-lightbulb', 'color' => 'text-amber-500', 'label' => __('Content Tips')],
                                        'best_times' => ['icon' => 'fa-clock', 'color' => 'text-emerald-500', 'label' => __('Best Times')],
                                        'trend_alert' => ['icon' => 'fa-bell', 'color' => 'text-rose-500', 'label' => __('Trend Alert')],
                                        default => ['icon' => 'fa-sparkles', 'color' => 'text-blue-500', 'label' => __('Insight')],
                                    };
                                @endphp
                                <i class="fa-light {{ $typeConfig['icon'] }} {{ $typeConfig['color'] }}"></i>
                                <span class="fw-6 fs-14 text-gray-700">{{ $typeConfig['label'] }}</span>
                                @if($insight['social_network'] ?? null)
                                    <span class="badge bg-gray-100 text-gray-600 fs-11">{{ ucfirst($insight['social_network']) }}</span>
                                @endif
                            </div>
                            <span class="text-gray-400 fs-11">
                                {{ $insight['period_start'] ?? '' }} — {{ $insight['period_end'] ?? '' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="prose fs-13 text-gray-600 lh-lg">
                                {!! nl2br(e($insight['content'] ?? '')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <i class="fa-light fa-sparkles fs-48 text-gray-300 mb-3 d-block"></i>
            <h6 class="text-gray-500 fw-5">{{ __('No insights generated yet') }}</h6>
            <p class="text-gray-400 fs-13 mb-3">{{ __('Click "Generate Insights" to get AI-powered analysis of your social media performance.') }}</p>
            <p class="text-gray-400 fs-12">{{ __('Each generation uses 1 AI credit.') }}</p>
        </div>
    @endif
</div>
