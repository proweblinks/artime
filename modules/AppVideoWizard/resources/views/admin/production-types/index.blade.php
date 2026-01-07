@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="mb-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Production Types') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 mx-auto text-primary-700">{{ __('Production Types Management') }}</div>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.production-types.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('This will create/update production types from config. Continue?')">
                        <i class="fa fa-download me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.production-types.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('New Type') }}
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

    <!-- Production Types Tree -->
    @forelse($types as $type)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    @if($type->icon)
                        <span class="me-2">
                            @if(str_starts_with($type->icon, 'fa-'))
                                <i class="fa {{ $type->icon }}"></i>
                            @else
                                {{ $type->icon }}
                            @endif
                        </span>
                    @endif
                    <h5 class="mb-0">{{ $type->name }}</h5>
                    <code class="ms-2 small text-muted">{{ $type->slug }}</code>
                    @if(!$type->is_active)
                        <span class="badge bg-secondary ms-2">{{ __('Inactive') }}</span>
                    @endif
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.video-wizard.production-types.create', ['parent_id' => $type->id]) }}" class="btn btn-outline-success" title="{{ __('Add Subtype') }}">
                        <i class="fa fa-plus"></i>
                    </a>
                    <a href="{{ route('admin.video-wizard.production-types.edit', $type) }}" class="btn btn-outline-primary" title="{{ __('Edit') }}">
                        <i class="fa fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-outline-secondary type-toggle" data-url="{{ route('admin.video-wizard.production-types.toggle', $type) }}" data-active="{{ $type->is_active ? '1' : '0' }}" title="{{ $type->is_active ? __('Deactivate') : __('Activate') }}">
                        <i class="fa {{ $type->is_active ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                    </button>
                    <form action="{{ route('admin.video-wizard.production-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $type->name }} and all subtypes?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" title="{{ __('Delete') }}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            @if($type->description)
                <div class="card-body py-2">
                    <small class="text-muted">{{ $type->description }}</small>
                </div>
            @endif

            <!-- Subtypes -->
            @if($type->children->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px"></th>
                                <th>{{ __('Subtype') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Duration') }}</th>
                                <th>{{ __('Narration') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($type->children as $subtype)
                                <tr>
                                    <td class="text-center">
                                        @if($subtype->icon)
                                            @if(str_starts_with($subtype->icon, 'fa-'))
                                                <i class="fa {{ $subtype->icon }}"></i>
                                            @else
                                                {{ $subtype->icon }}
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $subtype->name }}</div>
                                        <code class="small text-muted">{{ $subtype->slug }}</code>
                                    </td>
                                    <td class="small text-muted">{{ Str::limit($subtype->description, 50) }}</td>
                                    <td class="small">
                                        @if($subtype->suggested_duration_min || $subtype->suggested_duration_max)
                                            {{ $subtype->suggested_duration_min ?? '?' }}-{{ $subtype->suggested_duration_max ?? '?' }}s
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($subtype->default_narration)
                                            <span class="badge bg-info">{{ $subtype->default_narration }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($subtype->is_active)
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.video-wizard.production-types.edit', $subtype) }}" class="btn btn-outline-primary" title="{{ __('Edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.video-wizard.production-types.destroy', $subtype) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $subtype->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body text-center text-muted py-4">
                    <p class="mb-2">{{ __('No subtypes yet.') }}</p>
                    <a href="{{ route('admin.video-wizard.production-types.create', ['parent_id' => $type->id]) }}" class="btn btn-sm btn-outline-success">
                        <i class="fa fa-plus me-1"></i> {{ __('Add Subtype') }}
                    </a>
                </div>
            @endif
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="text-muted mb-3">
                    <i class="fa fa-film fs-40 opacity-50"></i>
                </div>
                <p class="text-muted">{{ __('No production types found.') }}</p>
                <a href="{{ route('admin.video-wizard.production-types.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus me-1"></i> {{ __('Create First Type') }}
                </a>
            </div>
        </div>
    @endforelse
</div>

@push('scripts')
<script>
document.querySelectorAll('.type-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        fetch(this.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(r => r.json()).then(data => {
            location.reload();
        }).catch(e => {
            alert('Failed to toggle status');
        });
    });
});
</script>
@endpush
@endsection
