@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="mb-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.logs.index') }}">{{ __('Logs') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Analytics') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 mx-auto text-primary-700">{{ __('Generation Analytics') }}</div>
            </div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 days') }}</option>
                        <option value="14" {{ $days == 14 ? 'selected' : '' }}>{{ __('Last 14 days') }}</option>
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 days') }}</option>
                        <option value="60" {{ $days == 60 ? 'selected' : '' }}>{{ __('Last 60 days') }}</option>
                        <option value="90" {{ $days == 90 ? 'selected' : '' }}>{{ __('Last 90 days') }}</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Overview Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-white-50">{{ __('Total') }}</div>
                    <div class="fs-24 fw-bold">{{ number_format($stats['total_generations']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-white-50">{{ __('Success') }}</div>
                    <div class="fs-24 fw-bold">{{ number_format($stats['successful']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-white-50">{{ __('Failed') }}</div>
                    <div class="fs-24 fw-bold">{{ number_format($stats['failed']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-white-50">{{ __('Success Rate') }}</div>
                    <div class="fs-24 fw-bold">{{ $stats['success_rate'] }}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-white-50">{{ __('Tokens') }}</div>
                    <div class="fs-24 fw-bold">{{ number_format($stats['total_tokens'] / 1000, 1) }}K</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <div class="fs-11 text-dark-50">{{ __('Est. Cost') }}</div>
                    <div class="fs-24 fw-bold">${{ number_format($stats['total_cost'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Stats by Prompt -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('By Prompt') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Prompt') }}</th>
                                    <th class="text-center">{{ __('Count') }}</th>
                                    <th class="text-center">{{ __('Success') }}</th>
                                    <th class="text-end">{{ __('Cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byPrompt as $stat)
                                    <tr>
                                        <td><code class="small">{{ $stat->prompt_slug }}</code></td>
                                        <td class="text-center">{{ number_format($stat->total_count) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $stat->success_rate >= 90 ? 'bg-success' : ($stat->success_rate >= 70 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ $stat->success_rate }}%
                                            </span>
                                        </td>
                                        <td class="text-end small">${{ number_format($stat->total_cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">{{ __('No data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Top Users') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('User') }}</th>
                                    <th class="text-center">{{ __('Generations') }}</th>
                                    <th class="text-center">{{ __('Tokens') }}</th>
                                    <th class="text-end">{{ __('Cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topUsers as $user)
                                    <tr>
                                        <td>{{ $user->user?->name ?? 'Unknown' }}</td>
                                        <td class="text-center">{{ number_format($user->generation_count) }}</td>
                                        <td class="text-center small">{{ number_format($user->total_tokens / 1000, 1) }}K</td>
                                        <td class="text-end small">${{ number_format($user->total_cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">{{ __('No data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Performance (Last 7 days)') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">{{ __('Avg Duration') }}</div>
                            <div class="fs-20 fw-semibold">{{ number_format($performance['avg_duration'] / 1000, 2) }}s</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('P50 Duration') }}</div>
                            <div class="fs-20 fw-semibold">{{ number_format($performance['p50_duration'] / 1000, 2) }}s</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('P95 Duration') }}</div>
                            <div class="fs-20 fw-semibold">{{ number_format($performance['p95_duration'] / 1000, 2) }}s</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">{{ __('P99 Duration') }}</div>
                            <div class="fs-20 fw-semibold">{{ number_format($performance['p99_duration'] / 1000, 2) }}s</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Errors -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Recent Errors (Last 7 days)') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Prompt') }}</th>
                                    <th>{{ __('Error') }}</th>
                                    <th class="text-center">{{ __('Count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($errors as $error)
                                    <tr>
                                        <td><code class="small">{{ $error->prompt_slug }}</code></td>
                                        <td class="small text-danger">{{ Str::limit($error->error_message, 40) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">{{ $error->count }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            <i class="fa fa-check-circle text-success me-1"></i> {{ __('No errors') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
