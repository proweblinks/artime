@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.logs.index') }}">{{ __('Logs') }}</a></li>
                <li class="breadcrumb-item active">#{{ $log->id }}</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Generation Log') }} #{{ $log->id }}</div>
                <small class="text-muted">{{ $log->created_at->format('M d, Y H:i:s') }}</small>
            </div>
            <div>
                @if($log->status === 'success')
                    <span class="badge bg-success fs-14 px-3 py-2">{{ __('Success') }}</span>
                @elseif($log->status === 'failed')
                    <span class="badge bg-danger fs-14 px-3 py-2">{{ __('Failed') }}</span>
                @else
                    <span class="badge bg-warning fs-14 px-3 py-2">{{ $log->status }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Metadata -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Details') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ __('Prompt') }}</dt>
                        <dd class="col-sm-7"><code>{{ $log->prompt_slug }}</code></dd>

                        <dt class="col-sm-5">{{ __('Version') }}</dt>
                        <dd class="col-sm-7">{{ $log->prompt_version ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">{{ __('User') }}</dt>
                        <dd class="col-sm-7">{{ $log->user?->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">{{ __('Project') }}</dt>
                        <dd class="col-sm-7">
                            @if($log->project)
                                <a href="{{ route('app.video-wizard.edit', $log->project_id) }}" target="_blank">
                                    {{ $log->project->name }}
                                </a>
                            @else
                                N/A
                            @endif
                        </dd>

                        <dt class="col-sm-5">{{ __('Tokens Used') }}</dt>
                        <dd class="col-sm-7">{{ $log->tokens_used ? number_format($log->tokens_used) : 'N/A' }}</dd>

                        <dt class="col-sm-5">{{ __('Duration') }}</dt>
                        <dd class="col-sm-7">{{ $log->formatted_duration }}</dd>

                        <dt class="col-sm-5">{{ __('Est. Cost') }}</dt>
                        <dd class="col-sm-7">{{ $log->formatted_cost }}</dd>
                    </dl>
                </div>
            </div>

            @if($log->error_message)
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fa fa-exclamation-triangle me-1"></i> {{ __('Error') }}</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 small text-danger" style="white-space: pre-wrap;">{{ $log->error_message }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <!-- Input Data -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Input Data') }}</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small mb-0" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap;">{{ json_encode($log->input_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>

            <!-- Output Data -->
            @if($log->output_data)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Output Data') }}</h5>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded small mb-0" style="max-height: 500px; overflow-y: auto; white-space: pre-wrap;">{{ json_encode($log->output_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
