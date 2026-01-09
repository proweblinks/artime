@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.index') }}">{{ __('Cinematography') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Genre Presets') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Genre Presets') }}</div>
                <p class="text-muted mb-0 small">{{ __('Camera language, color grades, lighting, and style for each genre') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.video-wizard.cinematography.genre-presets.export') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-download me-1"></i> {{ __('Export') }}
                </a>
                <a href="{{ route('admin.video-wizard.cinematography.genre-presets.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('New Preset') }}
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

    <!-- Stats & Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold text-primary">{{ $stats['total'] }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Total Presets') }}</div>
                            <div class="text-success small">{{ $stats['active'] }} {{ __('active') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <form class="row g-2" method="GET">
                        <div class="col-auto">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Status') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search presets...') }}" value="{{ request('search') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            @if(request()->hasAny(['category', 'status', 'search']))
                                <a href="{{ route('admin.video-wizard.cinematography.genre-presets.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Presets Grid -->
    <div class="row g-4">
        @forelse($presets as $preset)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 {{ !$preset->is_active ? 'opacity-50' : '' }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <div>
                            <span class="badge bg-{{ $preset->category == 'cinematic' ? 'primary' : ($preset->category == 'documentary' ? 'success' : ($preset->category == 'horror' ? 'danger' : 'secondary')) }}">
                                {{ ucfirst($preset->category) }}
                            </span>
                            @if($preset->is_default)
                                <span class="badge bg-warning text-dark">{{ __('Default') }}</span>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.video-wizard.cinematography.genre-presets.edit', $preset) }}">
                                        <i class="fa fa-edit me-2"></i> {{ __('Edit') }}
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('admin.video-wizard.cinematography.genre-presets.clone', $preset) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fa fa-copy me-2"></i> {{ __('Clone') }}
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <form action="{{ route('admin.video-wizard.cinematography.genre-presets.toggle', $preset) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fa fa-{{ $preset->is_active ? 'eye-slash' : 'eye' }} me-2"></i>
                                            {{ $preset->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.video-wizard.cinematography.genre-presets.destroy', $preset) }}" method="POST" onsubmit="return confirm('Delete this preset?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fa fa-trash me-2"></i> {{ __('Delete') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-1">{{ $preset->name }}</h6>
                        <code class="small text-muted">{{ $preset->slug }}</code>

                        @if($preset->description)
                            <p class="text-muted small mt-2 mb-3">{{ Str::limit($preset->description, 80) }}</p>
                        @endif

                        <div class="mt-3">
                            <div class="mb-2">
                                <span class="badge bg-light text-dark me-1"><i class="fa fa-video-camera me-1"></i> {{ __('Camera') }}</span>
                                <small class="text-muted">{{ Str::limit($preset->camera_language, 50) }}</small>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-light text-dark me-1"><i class="fa fa-palette me-1"></i> {{ __('Color') }}</span>
                                <small class="text-muted">{{ Str::limit($preset->color_grade, 50) }}</small>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark me-1"><i class="fa fa-lightbulb me-1"></i> {{ __('Light') }}</span>
                                <small class="text-muted">{{ Str::limit($preset->lighting, 50) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <a href="{{ route('admin.video-wizard.cinematography.genre-presets.edit', $preset) }}" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fa fa-edit me-1"></i> {{ __('Edit Preset') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fa fa-palette fs-40 text-muted opacity-50 mb-3"></i>
                        <p class="text-muted">{{ __('No genre presets found.') }}</p>
                        <a href="{{ route('admin.video-wizard.cinematography.genre-presets.create') }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus me-1"></i> {{ __('Create First Preset') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($presets->hasPages())
        <div class="mt-4">
            {{ $presets->links() }}
        </div>
    @endif
</div>
@endsection
