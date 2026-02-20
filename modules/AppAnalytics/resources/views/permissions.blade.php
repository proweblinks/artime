<div class="card b-r-6 border-gray-300 mb-3">
    <div class="card-header">
        <div class="form-check">
            <input class="form-check-input prevent-toggle" type="checkbox" value="1" id="appanalytics" name="permissions[appanalytics]" @checked( array_key_exists("appanalytics", $permissions ) )>
            <label class="fw-6 fs-14 text-gray-700 ms-2" for="appanalytics">
                {{ __("Analytics") }}
            </label>
        </div>
        <input class="form-control d-none" name="labels[appanalytics]" type="text" value="Analytics">
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Platform analytics checkboxes --}}
            <div class="col-md-12 allow_analytics">
                <div class="mb-4">
                    <div class="d-flex gap-8 justify-content-between border-bottom mb-3 pb-2">
                        <div class="fw-5 text-gray-800 fs-14 mb-2">{{ __('Platform Analytics') }}</div>
                        <div class="form-check">
                            <input class="form-check-input checkbox-all" data-checkbox-parent=".allow_analytics" type="checkbox" value="" id="allow_analytics">
                            <label class="form-check-label" for="allow_analytics">
                                {{ __('Select All') }}
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        @php
                            $platforms = [
                                'facebook' => ['name' => 'Facebook Analytics', 'icon' => 'fa-brands fa-facebook', 'color' => '#1877F2'],
                                'instagram' => ['name' => 'Instagram Analytics', 'icon' => 'fa-brands fa-instagram', 'color' => '#E4405F'],
                                'youtube' => ['name' => 'YouTube Analytics', 'icon' => 'fa-brands fa-youtube', 'color' => '#FF0000'],
                                'linkedin' => ['name' => 'LinkedIn Analytics', 'icon' => 'fa-brands fa-linkedin', 'color' => '#0A66C2'],
                                'tiktok' => ['name' => 'TikTok Analytics', 'icon' => 'fa-brands fa-tiktok', 'color' => '#010101'],
                                'x' => ['name' => 'X/Twitter Analytics', 'icon' => 'fa-brands fa-x-twitter', 'color' => '#000000'],
                            ];
                        @endphp
                        @foreach($platforms as $platformKey => $platformInfo)
                            @php
                                $key = 'appanalytics.' . $platformKey;
                                $labelValue = old("labels.$key", $labels[$key] ?? $platformInfo['name']);
                            @endphp
                            <div class="col-md-4 mb-3">
                                <div class="form-check mb-1">
                                    <input class="form-check-input checkbox-item"
                                           type="checkbox"
                                           name="permissions[{{ $key }}]"
                                           value="1"
                                           id="{{ $key }}"
                                           @checked(array_key_exists($key, $permissions))>
                                    <label class="form-check-label mt-1 text-truncate" for="{{ $key }}">
                                        <i class="{{ $platformInfo['icon'] }} me-1" style="color: {{ $platformInfo['color'] }}"></i>
                                        {{ $platformInfo['name'] }}
                                    </label>
                                </div>
                                <input class="form-control form-control-sm d-none"
                                       type="text"
                                       name="labels[{{ $key }}]"
                                       value="{{ $labelValue }}"
                                       placeholder="{{ __('Custom label') }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- AI Insights toggle --}}
            <div class="col-md-12">
                <div class="mb-0">
                    <div class="form-check">
                        @php
                            $aiKey = 'appanalytics.ai_insights';
                        @endphp
                        <input class="form-check-input"
                               type="checkbox"
                               name="permissions[{{ $aiKey }}]"
                               value="1"
                               id="{{ $aiKey }}"
                               @checked(array_key_exists($aiKey, $permissions))>
                        <label class="form-check-label mt-1" for="{{ $aiKey }}">
                            <i class="fa-light fa-sparkles text-amber-500 me-1"></i>
                            {{ __('AI-Powered Insights') }}
                        </label>
                    </div>
                    <input class="form-control form-control-sm d-none"
                           type="text"
                           name="labels[{{ $aiKey }}]"
                           value="{{ old("labels.$aiKey", $labels[$aiKey] ?? 'AI-Powered Insights') }}"
                           placeholder="{{ __('Custom label') }}">
                    <div class="text-gray-500 fs-11 ms-4 mt-1">{{ __('Allow users to generate AI-powered analytics insights (uses AI credits).') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
