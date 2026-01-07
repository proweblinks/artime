@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="mb-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Prompts') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 mx-auto text-primary-700">{{ __('AI Prompts Management') }}</div>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.prompts.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('This will create/update default prompts. Continue?')">
                        <i class="fa fa-download me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.prompts.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('New Prompt') }}
                </a>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search prompts...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search me-1"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prompts List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%">{{ __('Prompt') }}</th>
                            <th>{{ __('Model') }}</th>
                            <th>{{ __('Version') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prompts as $prompt)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $prompt->name }}</div>
                                    <code class="small text-muted">{{ $prompt->slug }}</code>
                                    @if($prompt->description)
                                        <div class="small text-muted mt-1">{{ Str::limit($prompt->description, 60) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $prompt->model }}</span>
                                    <div class="small text-muted">T: {{ $prompt->temperature }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-info">v{{ $prompt->version }}</span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input prompt-toggle" type="checkbox" role="switch"
                                            data-url="{{ route('admin.video-wizard.prompts.toggle', $prompt) }}"
                                            {{ $prompt->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    {{ $prompt->updated_at->format('M d, Y') }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.video-wizard.prompts.edit', $prompt) }}" class="btn btn-outline-primary" title="{{ __('Edit') }}">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.video-wizard.prompts.history', $prompt) }}" class="btn btn-outline-secondary" title="{{ __('History') }}">
                                            <i class="fa fa-history"></i>
                                        </a>
                                        <form action="{{ route('admin.video-wizard.prompts.destroy', $prompt) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Delete') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="fa fa-file-alt fs-40 opacity-50"></i>
                                    </div>
                                    <p class="text-muted">{{ __('No prompts found.') }}</p>
                                    <a href="{{ route('admin.video-wizard.prompts.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus me-1"></i> {{ __('Create First Prompt') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($prompts->hasPages())
            <div class="card-footer bg-white">
                {{ $prompts->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.prompt-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        fetch(this.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(r => r.json()).catch(e => {
            this.checked = !this.checked;
            alert('Failed to toggle status');
        });
    });
});
</script>
@endpush
@endsection
