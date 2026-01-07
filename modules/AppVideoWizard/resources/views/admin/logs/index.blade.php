@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="mb-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Generation Logs') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 mx-auto text-primary-700">{{ __('AI Generation Logs') }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.video-wizard.logs.analytics') }}" class="btn btn-outline-primary">
                    <i class="fa fa-chart-bar me-1"></i> {{ __('Analytics') }}
                </a>
                <a href="{{ route('admin.video-wizard.logs.export', request()->all()) }}" class="btn btn-outline-secondary">
                    <i class="fa fa-download me-1"></i> {{ __('Export CSV') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">{{ __('Prompt') }}</label>
                    <select name="prompt_slug" class="form-select form-select-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($prompts as $slug => $name)
                            <option value="{{ $slug }}" {{ request('prompt_slug') === $slug ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('Status') }}</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('From') }}</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('To') }}</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa fa-filter me-1"></i> {{ __('Filter') }}
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.video-wizard.logs.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        {{ __('Clear') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('Prompt') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Project') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Tokens') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Cost') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="small text-muted">#{{ $log->id }}</td>
                                <td class="small">
                                    <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <code class="small">{{ $log->prompt_slug }}</code>
                                    @if($log->prompt_version)
                                        <span class="badge bg-secondary badge-sm">v{{ $log->prompt_version }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->user?->name ?? 'N/A' }}</td>
                                <td class="small">
                                    @if($log->project)
                                        <a href="{{ route('app.video-wizard.edit', $log->project_id) }}" target="_blank">
                                            {{ Str::limit($log->project->name, 20) }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success">{{ __('Success') }}</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger" title="{{ $log->error_message }}">{{ __('Failed') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ $log->status }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->tokens_used ? number_format($log->tokens_used) : '-' }}</td>
                                <td class="small">{{ $log->formatted_duration }}</td>
                                <td class="small">{{ $log->formatted_cost }}</td>
                                <td>
                                    <a href="{{ route('admin.video-wizard.logs.show', $log) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View Details') }}">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="fa fa-inbox fs-40 opacity-50"></i>
                                    </div>
                                    <p class="text-muted">{{ __('No logs found.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
