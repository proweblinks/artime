{{-- YouTube Analytics Tab --}}
@if(!empty($platformData['success']))
    @php
        $stats = $platformData['channel_stats'] ?? [];
        $analytics = $platformData['analytics'] ?? [];
    @endphp

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Subscribers') }}</span>
                        <i class="fa-brands fa-youtube fs-14 text-red-500"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($stats['subscriber_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Views') }}</span>
                        <i class="fa-light fa-play fs-14 text-red-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($analytics['views'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Watch Time (min)') }}</span>
                        <i class="fa-light fa-clock fs-14 text-orange-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($analytics['estimated_minutes_watched'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Net Subscribers') }}</span>
                        <i class="fa-light fa-user-plus fs-14 text-emerald-400"></i>
                    </div>
                    @php
                        $netSubs = ($analytics['subscribers_gained'] ?? 0) - ($analytics['subscribers_lost'] ?? 0);
                    @endphp
                    <div class="fw-7 fs-22 {{ $netSubs >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $netSubs >= 0 ? '+' : '' }}{{ number_format($netSubs) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Additional KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="text-gray-500 fs-12 fw-5 mb-1">{{ __('Likes') }}</div>
                    <div class="fw-6 fs-18 text-gray-800">{{ number_format($analytics['likes'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="text-gray-500 fs-12 fw-5 mb-1">{{ __('Comments') }}</div>
                    <div class="fw-6 fs-18 text-gray-800">{{ number_format($analytics['comments'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="text-gray-500 fs-12 fw-5 mb-1">{{ __('Avg View Duration') }}</div>
                    @php
                        $avgDuration = $analytics['average_view_duration'] ?? 0;
                        $mins = floor($avgDuration / 60);
                        $secs = $avgDuration % 60;
                    @endphp
                    <div class="fw-6 fs-18 text-gray-800">{{ $mins }}m {{ $secs }}s</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="text-gray-500 fs-12 fw-5 mb-1">{{ __('Total Videos') }}</div>
                    <div class="fw-6 fs-18 text-gray-800">{{ number_format($stats['video_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    @if(!empty($dailyData['daily']))
        <div class="card shadow-none border-gray-200 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('YouTube Performance Over Time') }}</h6>
            </div>
            <div class="card-body">
                <div id="yt-chart" style="height: 300px;"
                     x-data="{
                        init() {
                            const daily = @js($dailyData['daily']);
                            if (daily.length && typeof Main !== 'undefined' && Main.Chart) {
                                Main.Chart('yt-chart', {
                                    categories: daily.map(d => d.date),
                                    series: [
                                        { name: '{{ __("Views") }}', data: daily.map(d => d.views || 0) },
                                        { name: '{{ __("Likes") }}', data: daily.map(d => d.likes || 0) },
                                        { name: '{{ __("Subscribers Gained") }}', data: daily.map(d => d.subscribers_gained || 0) }
                                    ]
                                });
                            }
                        }
                     }">
                </div>
            </div>
        </div>
    @endif

    {{-- Top Videos --}}
    @if(!empty($postsData['posts']))
        <div class="card shadow-none border-gray-200">
            <div class="card-header bg-white py-3">
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('Top Videos') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 fs-13">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5">{{ __('Video') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Views') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Likes') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Comments') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($postsData['posts'], 0, 10) as $video)
                                <tr>
                                    <td class="py-2 px-3">
                                        <div class="d-flex align-items-center gap-8">
                                            @if($video['thumbnail'])
                                                <img src="{{ $video['thumbnail'] }}" class="rounded" style="width: 48px; height: 36px; object-fit: cover;" alt="">
                                            @endif
                                            <div>
                                                <div class="text-truncate fw-5" style="max-width: 250px;">{{ $video['title'] }}</div>
                                                <div class="text-gray-400 fs-11">{{ $video['published_at'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($video['views']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($video['likes']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($video['comments']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@elseif(!$errorMessage)
    <div class="text-center py-5">
        <i class="fa-brands fa-youtube fs-48 text-gray-300 mb-3 d-block"></i>
        <h6 class="text-gray-500 fw-5">{{ __('No YouTube data available') }}</h6>
        <p class="text-gray-400 fs-13">{{ __('Make sure your YouTube channel is connected and YouTube Analytics API is enabled.') }}</p>
    </div>
@endif
