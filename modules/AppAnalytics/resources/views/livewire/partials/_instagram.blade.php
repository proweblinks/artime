{{-- Instagram Analytics Tab --}}
@if(!empty($platformData['success']))
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Followers') }}</span>
                        <i class="fa-brands fa-instagram fs-14" style="color: #E4405F"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['follower_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Impressions') }}</span>
                        <i class="fa-light fa-eye fs-14 text-purple-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['impressions'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Reach') }}</span>
                        <i class="fa-light fa-signal-bars fs-14 text-indigo-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['reach'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Profile Views') }}</span>
                        <i class="fa-light fa-user fs-14 text-rose-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['profile_views'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    @if(!empty($dailyData['daily']))
        <div class="card shadow-none border-gray-200 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('Instagram Insights Over Time') }}</h6>
            </div>
            <div class="card-body">
                <div id="ig-chart" style="height: 300px;"
                     x-data="{
                        init() {
                            const daily = @js($dailyData['daily']);
                            if (daily.length && typeof Main !== 'undefined' && Main.Chart) {
                                Main.Chart('ig-chart', {
                                    categories: daily.map(d => d.date),
                                    series: [
                                        { name: '{{ __("Impressions") }}', data: daily.map(d => d.impressions || 0) },
                                        { name: '{{ __("Reach") }}', data: daily.map(d => d.reach || 0) },
                                        { name: '{{ __("Profile Views") }}', data: daily.map(d => d.profile_views || 0) }
                                    ]
                                });
                            }
                        }
                     }">
                </div>
            </div>
        </div>
    @endif

    {{-- Top Posts --}}
    @if(!empty($postsData['posts']))
        <div class="card shadow-none border-gray-200">
            <div class="card-header bg-white py-3">
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('Top Media') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 fs-13">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5">{{ __('Caption') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5">{{ __('Type') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Likes') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Comments') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Reach') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Saved') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($postsData['posts'], 0, 10) as $post)
                                <tr>
                                    <td class="py-2 px-3">
                                        <div class="text-truncate" style="max-width: 250px;">{{ $post['caption'] }}</div>
                                        <div class="text-gray-400 fs-11">{{ $post['timestamp'] }}</div>
                                    </td>
                                    <td class="py-2 px-3">
                                        <span class="badge bg-gray-100 text-gray-600 fs-11">{{ $post['media_type'] }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['likes']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['comments']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['reach']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['saved']) }}</td>
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
        <i class="fa-brands fa-instagram fs-48 text-gray-300 mb-3 d-block"></i>
        <h6 class="text-gray-500 fw-5">{{ __('No Instagram data available') }}</h6>
        <p class="text-gray-400 fs-13">{{ __('Make sure your Instagram Business/Creator account is connected.') }}</p>
    </div>
@endif
