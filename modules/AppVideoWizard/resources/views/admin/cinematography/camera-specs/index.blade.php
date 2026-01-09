@extends('admin.layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Cinematography') }}</div>
                    <h2 class="page-title">{{ __('Camera Specifications') }}</h2>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.video-wizard.cinematography.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-xl">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="fa fa-check me-2"></i></div>
                    <div>{{ session('success') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <!-- Stats Row -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Total Specs') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Active') }}</div>
                        </div>
                        <div class="h1 mb-0 text-success">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-cyan-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Lenses') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ $stats['lenses'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-orange-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Film Stocks') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ $stats['filmStocks'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.video-wizard.cinematography.camera-specs') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select">
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" {{ request('category') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-search me-1"></i>{{ __('Filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Specs by Category -->
        @php
            $groupedSpecs = $specs->groupBy('category');
        @endphp

        @foreach($categories as $catValue => $catLabel)
            @if(isset($groupedSpecs[$catValue]) && $groupedSpecs[$catValue]->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            @switch($catValue)
                                @case('lens')
                                    <i class="fa fa-circle-o text-cyan me-2"></i>
                                    @break
                                @case('camera_body')
                                    <i class="fa fa-camera text-purple me-2"></i>
                                    @break
                                @case('film_stock')
                                    <i class="fa fa-film text-orange me-2"></i>
                                    @break
                                @case('format')
                                    <i class="fa fa-expand text-blue me-2"></i>
                                    @break
                            @endswitch
                            {{ $catLabel }}
                        </h3>
                        <div class="card-actions">
                            <span class="badge bg-secondary">{{ $groupedSpecs[$catValue]->count() }} {{ __('items') }}</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('AI Prompt Text') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="w-1">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedSpecs[$catValue] as $spec)
                                    <tr>
                                        <td>
                                            <div class="font-weight-medium">{{ $spec->name }}</div>
                                            <div class="text-muted small">{{ $spec->slug }}</div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ Str::limit($spec->description, 50) ?: '-' }}</span>
                                        </td>
                                        <td>
                                            @if($spec->prompt_text)
                                                <code class="small">{{ Str::limit($spec->prompt_text, 60) }}</code>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($spec->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.video-wizard.cinematography.camera-specs.toggle', $spec) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $spec->is_active ? 'warning' : 'success' }}"
                                                        title="{{ $spec->is_active ? __('Deactivate') : __('Activate') }}">
                                                    <i class="fa fa-{{ $spec->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $specs->withQueryString()->links() }}
        </div>

        <!-- Info Card -->
        <div class="card mt-4 bg-blue-lt border-0">
            <div class="card-body">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fa fa-lightbulb-o fa-2x text-blue"></i>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ __('About Camera Specifications') }}</h4>
                        <p class="mb-0 text-muted">
                            {{ __('Camera specs are used to enhance AI prompts with professional cinematography terminology. Lenses define focal lengths and characteristics, while film stocks provide color grading and texture references. These are automatically added to prompts based on shot type and genre preset selections.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
