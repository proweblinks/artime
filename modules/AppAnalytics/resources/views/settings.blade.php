@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Analytics Settings') }}"
        description="{{ __('Configure analytics module settings') }}"
    >
    </x-sub-header>
@endsection

@section('content')
<div class="container max-w-800 pb-5">
    <form class="actionForm" action="{{ url_admin("settings/save") }}">
        <div class="card shadow-none border-gray-300 mb-4">
            <div class="card-body">
                <div class="row">
                    {{-- Status --}}
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">{{ __('Status') }}</label>
                            <div class="d-flex gap-8 flex-column flex-lg-row flex-md-column">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_status" value="1" id="analytics_status_1" {{ get_option("analytics_status", 1) == 1 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_status_1">
                                        {{ __('Enable') }}
                                    </label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_status" value="0" id="analytics_status_0" {{ get_option("analytics_status", 1) == 0 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_status_0">
                                        {{ __('Disable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cache TTL --}}
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label for="analytics_cache_ttl" class="form-label">{{ __('Cache TTL (minutes)') }}</label>
                            <div class="text-gray-600 fs-12 mb-2">{{ __("How long to cache API responses before refreshing. Default is 360 minutes (6 hours).") }}</div>
                            <input class="form-control" name="analytics_cache_ttl" id="analytics_cache_ttl" type="number" min="5" value="{{ get_option("analytics_cache_ttl", 360) }}">
                        </div>
                    </div>

                    {{-- AI Insights --}}
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">{{ __('AI Insights') }}</label>
                            <div class="text-gray-600 fs-12 mb-2">{{ __("Enable AI-powered analytics insights and recommendations.") }}</div>
                            <div class="d-flex gap-8 flex-column flex-lg-row flex-md-column">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_ai_insights" value="1" id="analytics_ai_insights_1" {{ get_option("analytics_ai_insights", 1) == 1 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_ai_insights_1">
                                        {{ __('Enable') }}
                                    </label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_ai_insights" value="0" id="analytics_ai_insights_0" {{ get_option("analytics_ai_insights", 1) == 0 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_ai_insights_0">
                                        {{ __('Disable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AI Model --}}
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label for="analytics_ai_model" class="form-label">{{ __('AI Model for Insights') }}</label>
                            <div class="text-gray-600 fs-12 mb-2">{{ __("Which AI model to use for generating analytics insights.") }}</div>
                            <select class="form-select" name="analytics_ai_model" id="analytics_ai_model">
                                <option value="gpt-4o-mini" {{ get_option("analytics_ai_model", "gpt-4o-mini") == "gpt-4o-mini" ? "selected" : "" }}>GPT-4o Mini (Cost-effective)</option>
                                <option value="gpt-4o" {{ get_option("analytics_ai_model", "gpt-4o-mini") == "gpt-4o" ? "selected" : "" }}>GPT-4o (Higher quality)</option>
                                <option value="gemini-2.5-flash" {{ get_option("analytics_ai_model", "gpt-4o-mini") == "gemini-2.5-flash" ? "selected" : "" }}>Gemini 2.5 Flash</option>
                            </select>
                        </div>
                    </div>

                    {{-- Daily Snapshots --}}
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">{{ __('Daily Snapshots') }}</label>
                            <div class="text-gray-600 fs-12 mb-2">{{ __("Automatically capture daily metrics for historical trend data.") }}</div>
                            <div class="d-flex gap-8 flex-column flex-lg-row flex-md-column">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_daily_snapshots" value="1" id="analytics_daily_snapshots_1" {{ get_option("analytics_daily_snapshots", 1) == 1 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_daily_snapshots_1">
                                        {{ __('Enable') }}
                                    </label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="analytics_daily_snapshots" value="0" id="analytics_daily_snapshots_0" {{ get_option("analytics_daily_snapshots", 1) == 0 ? "checked" : "" }}>
                                    <label class="form-check-label mt-1" for="analytics_daily_snapshots_0">
                                        {{ __('Disable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="col-md-12">
                        <div class="alert alert-primary fs-13">
                            <i class="fa-light fa-circle-info me-1"></i>
                            {{ __("Analytics data is fetched from each platform's API using existing OAuth tokens. No additional API keys are needed.") }}
                            <br>
                            <strong>{{ __("Note:") }}</strong> {{ __("YouTube Analytics requires the YouTube Analytics API to be enabled in your Google Cloud Console (separate from the YouTube Data API).") }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-dark b-r-10 w-100">
                {{ __('Save changes') }}
            </button>
        </div>
    </form>
</div>
@endsection
