@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="mb-0">
                <div class="fw-7 fs-20 mx-auto mb-2 text-primary-700">{{ __('Video Creator Admin') }}</div>
                <div class="fw-5 text-gray-700">{{ __('Manage AI prompts, production types, and view generation logs.') }}</div>
            </div>
            <div>
                <form action="{{ route('admin.video-wizard.clear-cache') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-refresh me-1"></i> {{ __('Clear Cache') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-12 text-white-50">{{ __('Total Generations') }}</div>
                            <div class="fs-24 fw-bold">{{ number_format($stats['total_generations']) }}</div>
                        </div>
                        <i class="fa fa-magic fs-30 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-12 text-white-50">{{ __('Success Rate') }}</div>
                            <div class="fs-24 fw-bold">{{ $stats['success_rate'] }}%</div>
                        </div>
                        <i class="fa fa-check-circle fs-30 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-12 text-white-50">{{ __('Active Prompts') }}</div>
                            <div class="fs-24 fw-bold">{{ $activePromptCount }} / {{ $promptCount }}</div>
                        </div>
                        <i class="fa fa-file-alt fs-30 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-12 text-dark-50">{{ __('Est. Cost (30d)') }}</div>
                            <div class="fs-24 fw-bold">${{ number_format($stats['total_cost'], 2) }}</div>
                        </div>
                        <i class="fa fa-dollar-sign fs-30 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="fa fa-file-code text-primary fs-20"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">{{ __('AI Prompts') }}</h5>
                            <small class="text-muted">{{ $promptCount }} {{ __('prompts configured') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Edit AI prompts, manage versions, test with sample data, and configure model settings.') }}
                    </p>
                    <a href="{{ route('admin.video-wizard.prompts.index') }}" class="btn btn-primary btn-sm">
                        {{ __('Manage Prompts') }} <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="fa fa-film text-success fs-20"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">{{ __('Production Types') }}</h5>
                            <small class="text-muted">{{ $productionTypeCount }} {{ __('types') }}, {{ $subtypeCount }} {{ __('subtypes') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Configure video production categories like Social, Movie, Educational, and their subtypes.') }}
                    </p>
                    <a href="{{ route('admin.video-wizard.production-types.index') }}" class="btn btn-success btn-sm">
                        {{ __('Manage Types') }} <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="fa fa-chart-line text-info fs-20"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">{{ __('Logs & Analytics') }}</h5>
                            <small class="text-muted">{{ number_format($stats['total_tokens']) }} {{ __('tokens used') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('View generation history, analyze performance, track costs, and debug issues.') }}
                    </p>
                    <a href="{{ route('admin.video-wizard.logs.index') }}" class="btn btn-info btn-sm">
                        {{ __('View Logs') }} <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Recent Generations') }}</h5>
            <a href="{{ route('admin.video-wizard.logs.analytics') }}" class="btn btn-sm btn-outline-primary">
                {{ __('View Analytics') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('Prompt') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Duration') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="small text-muted">{{ $log->created_at->diffForHumans() }}</td>
                                <td><code class="small">{{ $log->prompt_slug }}</code></td>
                                <td>{{ $log->user?->name ?? 'N/A' }}</td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success">{{ __('Success') }}</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger">{{ __('Failed') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ $log->status }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->formatted_duration }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    {{ __('No generation logs yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
