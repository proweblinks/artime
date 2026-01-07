@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.prompts.index') }}">{{ __('Prompts') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.prompts.edit', $prompt) }}">{{ $prompt->name }}</a></li>
                <li class="breadcrumb-item active">{{ __('History') }}</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Version History') }}: {{ $prompt->name }}</div>
                <small class="text-muted">{{ __('Current version:') }} v{{ $prompt->version }}</small>
            </div>
            <a href="{{ route('admin.video-wizard.prompts.edit', $prompt) }}" class="btn btn-outline-primary">
                <i class="fa fa-edit me-1"></i> {{ __('Edit Current') }}
            </a>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Current Version -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fa fa-star me-1"></i> {{ __('Current Version') }} (v{{ $prompt->version }})</span>
                <span class="badge bg-light text-primary">{{ __('Active') }}</span>
            </div>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded small" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;">{{ $prompt->prompt_template }}</pre>
        </div>
    </div>

    <!-- Version History -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('Previous Versions') }}</h5>
        </div>
        <div class="card-body p-0">
            @forelse($history as $h)
                <div class="border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-secondary me-2">v{{ $h->version }}</span>
                            <small class="text-muted">{{ $h->created_at->format('M d, Y H:i') }}</small>
                            @if($h->changedBy)
                                <small class="text-muted ms-2">{{ __('by') }} {{ $h->changedBy->name }}</small>
                            @endif
                        </div>
                        <form action="{{ route('admin.video-wizard.prompts.rollback', [$prompt, $h->version]) }}" method="POST" class="d-inline" onsubmit="return confirm('Rollback to version {{ $h->version }}? This will create a new version.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="fa fa-undo me-1"></i> {{ __('Rollback') }}
                            </button>
                        </form>
                    </div>
                    @if($h->change_notes)
                        <p class="mb-2 small"><strong>{{ __('Notes:') }}</strong> {{ $h->change_notes }}</p>
                    @endif
                    <details class="small">
                        <summary class="text-primary cursor-pointer">{{ __('View prompt template') }}</summary>
                        <pre class="bg-light p-3 rounded mt-2" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap;">{{ $h->prompt_template }}</pre>
                    </details>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-history fs-40 opacity-50"></i>
                    <p class="mt-3">{{ __('No version history yet.') }}</p>
                </div>
            @endforelse
        </div>
        @if($history->hasPages())
            <div class="card-footer bg-white">
                {{ $history->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
