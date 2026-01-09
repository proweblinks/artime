@extends('admin.layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Cinematography') }}</div>
                    <h2 class="page-title">{{ __('Story Structures') }}</h2>
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
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Total Structures') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Active') }}</div>
                        </div>
                        <div class="h1 mb-0 text-success">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-4">
                <div class="card bg-primary-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('Default Structure') }}</div>
                        </div>
                        <div class="h4 mb-0">
                            @php $default = $structures->where('is_default', true)->first(); @endphp
                            {{ $default ? $default->name : __('None set') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card mb-4 bg-azure-lt border-0">
            <div class="card-body">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fa fa-info-circle fa-2x text-azure"></i>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ __('About Story Structures') }}</h4>
                        <p class="mb-0 text-muted">
                            {{ __('Story structures define how scenes are distributed across acts and how dramatic tension builds throughout the narrative. The system uses these structures to create professional pacing in generated videos.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Story Structures Grid -->
        <div class="row row-deck row-cards">
            @foreach($structures as $structure)
                <div class="col-md-6">
                    <div class="card {{ $structure->is_default ? 'border-primary' : '' }}">
                        <div class="card-header">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    @if($structure->is_default)
                                        <i class="fa fa-star text-warning me-2"></i>
                                    @endif
                                    {{ $structure->name }}
                                </h3>
                                <div>
                                    @if($structure->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ $structure->description ?? __('No description') }}</p>

                            <!-- Act Distribution Visual -->
                            @php
                                $actDist = is_string($structure->act_distribution) ? json_decode($structure->act_distribution, true) : $structure->act_distribution;
                            @endphp
                            @if(!empty($actDist))
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Act Distribution') }}</label>
                                    <div class="progress progress-lg mb-2" style="height: 24px;">
                                        @foreach($actDist as $act => $percent)
                                            @php
                                                $colors = ['act1' => 'primary', 'act2' => 'success', 'act3' => 'danger'];
                                                $labels = ['act1' => 'Act 1', 'act2' => 'Act 2', 'act3' => 'Act 3'];
                                            @endphp
                                            <div class="progress-bar bg-{{ $colors[$act] ?? 'secondary' }}" style="width: {{ $percent }}%">
                                                <span class="small">{{ $labels[$act] ?? $act }}: {{ $percent }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Min/Max Scenes -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="small text-muted">{{ __('Min Scenes') }}</div>
                                        <div class="h4 mb-0">{{ $structure->min_scenes ?? 3 }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="small text-muted">{{ __('Max Scenes') }}</div>
                                        <div class="h4 mb-0">{{ $structure->max_scenes ?? 12 }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pacing Curve -->
                            @php
                                $pacingCurve = is_string($structure->pacing_curve) ? json_decode($structure->pacing_curve, true) : $structure->pacing_curve;
                            @endphp
                            @if(!empty($pacingCurve))
                                <div class="mb-0">
                                    <label class="form-label">{{ __('Pacing Curve') }}</label>
                                    <div class="d-flex justify-content-between align-items-end" style="height: 50px;">
                                        @foreach($pacingCurve as $point)
                                            <div class="bg-primary rounded" style="width: 8%; height: {{ $point * 100 }}%; min-height: 4px;"></div>
                                        @endforeach
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">{{ __('Start') }}</small>
                                        <small class="text-muted">{{ __('End') }}</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <div class="btn-list">
                                    <form action="{{ route('admin.video-wizard.cinematography.story-structures.toggle', $structure) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $structure->is_active ? 'warning' : 'success' }}">
                                            <i class="fa fa-{{ $structure->is_active ? 'pause' : 'play' }} me-1"></i>
                                            {{ $structure->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </form>
                                </div>
                                @if(!$structure->is_default && $structure->is_active)
                                    <form action="{{ route('admin.video-wizard.cinematography.story-structures.set-default', $structure) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-star me-1"></i>{{ __('Set as Default') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
