@extends('admin.layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Cinematography') }}</div>
                    <h2 class="page-title">{{ __('Shot Types') }}</h2>
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
                            <div class="subheader">{{ __('Total Shot Types') }}</div>
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
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Inactive') }}</div>
                        </div>
                        <div class="h1 mb-0 text-muted">{{ $stats['total'] - $stats['active'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Categories') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ count($categories) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.video-wizard.cinematography.shot-types') }}" class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search by name, slug...') }}"
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-search me-1"></i>{{ __('Filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Shot Types by Category -->
        @php
            $groupedShots = $shotTypes->groupBy('category');
        @endphp

        @foreach($categories as $catValue => $catLabel)
            @if(isset($groupedShots[$catValue]) && $groupedShots[$catValue]->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            @switch($catValue)
                                @case('framing')
                                    <i class="fa fa-crop text-primary me-2"></i>
                                    @break
                                @case('angle')
                                    <i class="fa fa-arrows-alt text-success me-2"></i>
                                    @break
                                @case('movement')
                                    <i class="fa fa-video-camera text-info me-2"></i>
                                    @break
                                @case('focus')
                                    <i class="fa fa-bullseye text-warning me-2"></i>
                                    @break
                                @case('special')
                                    <i class="fa fa-magic text-danger me-2"></i>
                                    @break
                            @endswitch
                            {{ $catLabel }}
                        </h3>
                        <div class="card-actions">
                            <span class="badge bg-secondary">{{ $groupedShots[$catValue]->count() }} {{ __('shots') }}</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Shot Type') }}</th>
                                    <th>{{ __('Camera Specs') }}</th>
                                    <th>{{ __('Duration') }}</th>
                                    <th>{{ __('Emotional Beats') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="w-1">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedShots[$catValue] as $shot)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-medium">{{ $shot->name }}</div>
                                                    <div class="text-muted small">{{ $shot->slug }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($shot->default_lens || $shot->default_aperture)
                                                <span class="text-muted">
                                                    {{ $shot->default_lens ?? '-' }}
                                                    @if($shot->default_aperture)
                                                        <span class="mx-1">|</span> {{ $shot->default_aperture }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $shot->typical_duration_min ?? 3 }}-{{ $shot->typical_duration_max ?? 8 }}s
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $beats = is_string($shot->emotional_beats) ? json_decode($shot->emotional_beats, true) : $shot->emotional_beats;
                                            @endphp
                                            @if(!empty($beats))
                                                @foreach(array_slice($beats, 0, 2) as $beat)
                                                    <span class="badge bg-purple-lt me-1">{{ $beat }}</span>
                                                @endforeach
                                                @if(count($beats) > 2)
                                                    <span class="badge bg-secondary">+{{ count($beats) - 2 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($shot->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('admin.video-wizard.cinematography.shot-types.edit', $shot) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.video-wizard.cinematography.shot-types.toggle', $shot) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $shot->is_active ? 'warning' : 'success' }}"
                                                            title="{{ $shot->is_active ? __('Deactivate') : __('Activate') }}">
                                                        <i class="fa fa-{{ $shot->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
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
            {{ $shotTypes->withQueryString()->links() }}
        </div>
    </div>
@endsection
