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
                        <li class="breadcrumb-item active">{{ __('Seedance Styles') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Seedance Styles') }}</div>
                <p class="text-muted mb-0 small">{{ __('Visual, lighting, and color treatment styles for Seedance-native prompts') }}</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.cinematography.seedance-styles.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-database me-1"></i>{{ __('Seed Defaults') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.cinematography.seedance-styles.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus me-1"></i>{{ __('New Style') }}
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

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-24 fw-bold text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted">{{ __('Total') }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-24 fw-bold text-success">{{ $stats['active'] }}</div>
                <small class="text-muted">{{ __('Active') }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-24 fw-bold text-info">{{ $stats['visual'] }}</div>
                <small class="text-muted">{{ __('Visual') }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-24 fw-bold text-warning">{{ $stats['lighting'] }}</div>
                <small class="text-muted">{{ __('Lighting') }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-24 fw-bold text-danger">{{ $stats['color'] }}</div>
                <small class="text-muted">{{ __('Color') }}</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">{{ __('All Categories') }}</option>
                        <option value="visual" {{ request('category') === 'visual' ? 'selected' : '' }}>{{ __('Visual') }}</option>
                        <option value="lighting" {{ request('category') === 'lighting' ? 'selected' : '' }}>{{ __('Lighting') }}</option>
                        <option value="color" {{ request('category') === 'color' ? 'selected' : '' }}>{{ __('Color') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Styles Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px"></th>
                        <th>{{ __('Style') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Prompt Syntax') }}</th>
                        <th>{{ __('Default') }}</th>
                        <th style="width: 100px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($styles as $style)
                    <tr class="{{ !$style->is_active ? 'opacity-50' : '' }}">
                        <td>
                            <form action="{{ route('admin.video-wizard.cinematography.seedance-styles.toggle', $style) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm p-0 border-0" title="{{ $style->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fa fa-{{ $style->is_active ? 'toggle-on text-success' : 'toggle-off text-muted' }} fs-18"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $style->name }}</div>
                            <small class="text-muted">{{ $style->slug }}</small>
                            @if($style->description)
                                <div class="small text-muted mt-1" style="max-width: 300px;">{{ Str::limit($style->description, 80) }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $categoryColors = ['visual' => 'info', 'lighting' => 'warning', 'color' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $categoryColors[$style->category] ?? 'secondary' }}">
                                {{ ucfirst($style->category) }}
                            </span>
                        </td>
                        <td>
                            <code class="small">{{ Str::limit($style->prompt_syntax, 60) }}</code>
                        </td>
                        <td>
                            @if($style->is_default)
                                <span class="badge bg-success">{{ __('Default') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.video-wizard.cinematography.seedance-styles.edit', $style) }}">
                                            <i class="fa fa-edit me-2"></i>{{ __('Edit') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.video-wizard.cinematography.seedance-styles.destroy', $style) }}" method="POST"
                                              onsubmit="return confirm('Delete this style?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fa fa-trash me-2"></i>{{ __('Delete') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            {{ __('No styles found. Click "Seed Defaults" to load the Seedance 2.0 style vocabulary.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $styles->withQueryString()->links() }}
</div>
@endsection
