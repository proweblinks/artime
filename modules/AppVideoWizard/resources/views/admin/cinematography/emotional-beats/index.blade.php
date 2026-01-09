@extends('admin.layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Cinematography') }}</div>
                    <h2 class="page-title">{{ __('Emotional Beats (Three-Act Structure)') }}</h2>
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
                            <div class="subheader">{{ __('Total Beats') }}</div>
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
                <div class="card bg-azure-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Three-Act Structure') }}</div>
                        </div>
                        <div class="h1 mb-0">3 {{ __('Acts') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-purple-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Story Positions') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ count($positions) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Three-Act Visual -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-film me-2"></i>{{ __('Three-Act Narrative Structure') }}</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="card bg-blue-lt border-0 h-100">
                            <div class="card-body">
                                <h4 class="text-blue">{{ __('Act 1: Setup') }}</h4>
                                <p class="text-muted mb-2">~25% {{ __('of runtime') }}</p>
                                <div class="d-flex flex-column gap-1">
                                    @if(isset($beatsByAct['act1']))
                                        @foreach($beatsByAct['act1']->take(5) as $beat)
                                            <span class="badge bg-blue">{{ $beat->name }}</span>
                                        @endforeach
                                        @if($beatsByAct['act1']->count() > 5)
                                            <span class="text-muted small">+{{ $beatsByAct['act1']->count() - 5 }} {{ __('more') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-green-lt border-0 h-100">
                            <div class="card-body">
                                <h4 class="text-green">{{ __('Act 2: Confrontation') }}</h4>
                                <p class="text-muted mb-2">~50% {{ __('of runtime') }}</p>
                                <div class="d-flex flex-column gap-1">
                                    @if(isset($beatsByAct['act2']))
                                        @foreach($beatsByAct['act2']->take(5) as $beat)
                                            <span class="badge bg-green">{{ $beat->name }}</span>
                                        @endforeach
                                        @if($beatsByAct['act2']->count() > 5)
                                            <span class="text-muted small">+{{ $beatsByAct['act2']->count() - 5 }} {{ __('more') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-red-lt border-0 h-100">
                            <div class="card-body">
                                <h4 class="text-red">{{ __('Act 3: Resolution') }}</h4>
                                <p class="text-muted mb-2">~25% {{ __('of runtime') }}</p>
                                <div class="d-flex flex-column gap-1">
                                    @if(isset($beatsByAct['act3']))
                                        @foreach($beatsByAct['act3']->take(5) as $beat)
                                            <span class="badge bg-red">{{ $beat->name }}</span>
                                        @endforeach
                                        @if($beatsByAct['act3']->count() > 5)
                                            <span class="text-muted small">+{{ $beatsByAct['act3']->count() - 5 }} {{ __('more') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.video-wizard.cinematography.emotional-beats') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Story Position') }}</label>
                        <select name="position" class="form-select">
                            <option value="">{{ __('All Positions') }}</option>
                            @foreach($positions as $value => $label)
                                <option value="{{ $value }}" {{ request('position') == $value ? 'selected' : '' }}>
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

        <!-- Emotional Beats Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('All Emotional Beats') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Beat') }}</th>
                            <th>{{ __('Story Position') }}</th>
                            <th>{{ __('Intensity') }}</th>
                            <th>{{ __('Recommended Shots') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="w-1">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($beats as $beat)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="font-weight-medium">{{ $beat->name }}</div>
                                            <div class="text-muted small">{{ $beat->slug }}</div>
                                            @if($beat->description)
                                                <div class="text-muted small mt-1">{{ Str::limit($beat->description, 60) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $positionColors = [
                                            'act1_setup' => 'blue',
                                            'act1_catalyst' => 'blue',
                                            'act2_rising' => 'green',
                                            'act2_midpoint' => 'green',
                                            'act2_crisis' => 'green',
                                            'act3_climax' => 'red',
                                            'act3_resolution' => 'red',
                                            'standalone' => 'purple',
                                        ];
                                        $color = $positionColors[$beat->story_position] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}-lt text-{{ $color }}">
                                        {{ $positions[$beat->story_position] ?? $beat->story_position }}
                                    </span>
                                </td>
                                <td>
                                    @if($beat->intensity_level)
                                        <div class="progress progress-sm" style="width: 80px;">
                                            <div class="progress-bar bg-{{ $beat->intensity_level > 7 ? 'danger' : ($beat->intensity_level > 4 ? 'warning' : 'success') }}"
                                                 style="width: {{ $beat->intensity_level * 10 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $beat->intensity_level }}/10</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $shots = is_string($beat->recommended_shot_types) ? json_decode($beat->recommended_shot_types, true) : $beat->recommended_shot_types;
                                    @endphp
                                    @if(!empty($shots))
                                        @foreach(array_slice($shots, 0, 3) as $shot)
                                            <span class="badge bg-secondary-lt me-1">{{ $shot }}</span>
                                        @endforeach
                                        @if(count($shots) > 3)
                                            <span class="badge bg-secondary">+{{ count($shots) - 3 }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($beat->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.video-wizard.cinematography.emotional-beats.toggle', $beat) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $beat->is_active ? 'warning' : 'success' }}"
                                                title="{{ $beat->is_active ? __('Deactivate') : __('Activate') }}">
                                            <i class="fa fa-{{ $beat->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-center">
                {{ $beats->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
