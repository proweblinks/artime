@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Cinematography') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Professional Cinematography System') }}</div>
                <p class="text-muted mb-0 small">{{ __('Manage genre presets, shot types, emotional beats, and camera specifications') }}</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.cinematography.clear-caches') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa fa-refresh me-1"></i> {{ __('Clear Caches') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.cinematography.export-all') }}" class="btn btn-outline-primary">
                    <i class="fa fa-download me-1"></i> {{ __('Export All') }}
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

    <!-- Overview Cards -->
    <div class="row g-4 mb-4">
        <!-- Genre Presets -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-palette fa-2x text-primary"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['genrePresets']['active'] }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Genre Presets') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Camera language, color grades, and lighting for different genres') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['genrePresets']['total'] }} {{ __('total') }} &bull; {{ $stats['genrePresets']['categories'] }} {{ __('categories') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.genre-presets.index') }}" class="btn btn-sm btn-primary">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shot Types -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-video fa-2x text-success"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['shotTypes']['active'] }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Shot Types') }}</h5>
                    <p class="text-muted small mb-3">{{ __('50+ professional shot types based on StudioBinder guide') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['shotTypes']['total'] }} {{ __('total') }} &bull; {{ $stats['shotTypes']['categories'] }} {{ __('categories') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.shot-types') }}" class="btn btn-sm btn-success">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emotional Beats -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-heart-pulse fa-2x text-warning"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['emotionalBeats']['active'] }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Emotional Beats') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Three-act narrative beats for story-driven shot selection') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['emotionalBeats']['total'] }} {{ __('total') }} &bull; {{ $stats['emotionalBeats']['positions'] }} {{ __('positions') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.emotional-beats') }}" class="btn btn-sm btn-warning">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Story Structures -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-sitemap fa-2x text-info"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['storyStructures']['active'] }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Story Structures') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Three-Act, Hero\'s Journey, Save the Cat, and more') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['storyStructures']['total'] }} {{ __('structures') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.story-structures') }}" class="btn btn-sm btn-info">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Specs -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-camera fa-2x text-secondary"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['cameraSpecs']['active'] }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Camera Specs') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Lens specifications and film stock looks for AI prompts') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['cameraSpecs']['lenses'] }} {{ __('lenses') }} &bull; {{ $stats['cameraSpecs']['filmStocks'] }} {{ __('film stocks') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.camera-specs') }}" class="btn btn-sm btn-secondary">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Movements (Motion Intelligence) -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-arrows-alt fa-2x text-danger"></i>
                        </div>
                        <span class="badge bg-success">{{ $stats['cameraMovements']['active'] ?? 0 }} {{ __('active') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Camera Movements') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Motion Intelligence: 25+ professional camera movements for AI video') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ $stats['cameraMovements']['total'] ?? 0 }} {{ __('total') }} &bull; {{ $stats['cameraMovements']['categories'] ?? 0 }} {{ __('categories') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.camera-movements') }}" class="btn btn-sm btn-danger">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shot Continuity (Phase 3) -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-link fa-2x text-primary"></i>
                        </div>
                        <span class="badge bg-primary">{{ __('Phase 3') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Shot Continuity') }}</h5>
                    <p class="text-muted small mb-3">{{ __('30-degree rule, coverage patterns, and professional shot sequencing') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ __('Coverage patterns & rules') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.continuity') }}" class="btn btn-sm btn-primary">
                            {{ __('View') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coverage Patterns (Phase 4) -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fa fa-magnifying-glass-chart fa-2x text-info"></i>
                        </div>
                        <span class="badge bg-info">{{ __('Phase 4') }}</span>
                    </div>
                    <h5 class="card-title">{{ __('Coverage Patterns') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Scene type detection and auto-classification for professional coverage') }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ __('15+ scene patterns') }}</span>
                        <a href="{{ route('admin.video-wizard.cinematography.coverage-patterns.index') }}" class="btn btn-sm btn-info">
                            {{ __('Manage') }} <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Preview -->
    @if($samples['genre'])
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fa fa-eye me-2"></i>{{ __('Sample Prompt Preview') }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="small text-muted fw-semibold">{{ __('Genre') }}</label>
                    <p class="mb-2">{{ $samples['genre']->name }} <span class="badge bg-light text-dark">{{ $samples['genre']->category }}</span></p>

                    @if($samples['shot'])
                    <label class="small text-muted fw-semibold">{{ __('Shot Type') }}</label>
                    <p class="mb-2">{{ $samples['shot']->name }} <span class="badge bg-light text-dark">{{ $samples['shot']->category }}</span></p>
                    @endif

                    @if($samples['beat'])
                    <label class="small text-muted fw-semibold">{{ __('Emotional Beat') }}</label>
                    <p class="mb-0">{{ $samples['beat']->name }} <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $samples['beat']->story_position) }}</span></p>
                    @endif
                </div>
                <div class="col-md-8">
                    <label class="small text-muted fw-semibold">{{ __('Generated Prompt Elements') }}</label>
                    <div class="bg-dark text-light rounded p-3 small" style="font-family: monospace;">
                        <span class="text-info">// Camera Language</span><br>
                        {{ $samples['genre']->camera_language }}<br><br>
                        <span class="text-info">// Color Grade</span><br>
                        {{ $samples['genre']->color_grade }}<br><br>
                        <span class="text-info">// Lighting</span><br>
                        {{ $samples['genre']->lighting }}<br><br>
                        <span class="text-info">// Style</span><br>
                        {{ $samples['genre']->style }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
