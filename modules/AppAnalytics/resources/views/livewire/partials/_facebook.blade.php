{{-- Facebook Analytics Tab --}}
@if(!empty($platformData['success']))
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Page Fans') }}</span>
                        <i class="fa-brands fa-facebook text-blue-500 fs-14"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['page_fans'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Impressions') }}</span>
                        <i class="fa-light fa-eye fs-14 text-blue-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['page_impressions'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Engaged Users') }}</span>
                        <i class="fa-light fa-users fs-14 text-indigo-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['page_engaged_users'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-none border-gray-200 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-gray-500 fs-12 fw-5">{{ __('Post Engagements') }}</span>
                        <i class="fa-light fa-heart fs-14 text-rose-400"></i>
                    </div>
                    <div class="fw-7 fs-22 text-gray-800">{{ number_format($platformData['metrics']['page_post_engagements'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    @if(!empty($dailyData['daily']))
        <div class="card shadow-none border-gray-200 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('Engagement Over Time') }}</h6>
            </div>
            <div class="card-body">
                <div id="fb-chart" style="height: 300px;"
                     x-data="{
                        init() {
                            const daily = @js($dailyData['daily']);
                            if (daily.length && typeof Main !== 'undefined' && Main.Chart) {
                                Main.Chart('fb-chart', {
                                    categories: daily.map(d => d.date),
                                    series: [
                                        { name: '{{ __("Impressions") }}', data: daily.map(d => d.page_impressions || 0) },
                                        { name: '{{ __("Engaged Users") }}', data: daily.map(d => d.page_engaged_users || 0) },
                                        { name: '{{ __("Post Engagements") }}', data: daily.map(d => d.page_post_engagements || 0) }
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
                <h6 class="fw-6 mb-0 text-gray-700">{{ __('Top Posts') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 fs-13">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5">{{ __('Post') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Likes') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Comments') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Shares') }}</th>
                                <th class="border-0 py-2 px-3 text-gray-500 fw-5 text-end">{{ __('Impressions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($postsData['posts'], 0, 10) as $post)
                                <tr>
                                    <td class="py-2 px-3">
                                        <div class="text-truncate" style="max-width: 300px;">{{ $post['message'] }}</div>
                                        <div class="text-gray-400 fs-11">{{ $post['created_time'] }}</div>
                                    </td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['likes']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['comments']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['shares']) }}</td>
                                    <td class="py-2 px-3 text-end fw-5">{{ number_format($post['impressions']) }}</td>
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
        <i class="fa-brands fa-facebook fs-48 text-gray-300 mb-3 d-block"></i>
        <h6 class="text-gray-500 fw-5">{{ __('No Facebook data available') }}</h6>
        <p class="text-gray-400 fs-13">{{ __('Make sure your Facebook Page is connected and has the required permissions.') }}</p>
    </div>
@endif
