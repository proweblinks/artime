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
                        <li class="breadcrumb-item active">{{ __('Shot Continuity') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Shot Continuity System') }}</div>
                <p class="text-muted mb-0 small">{{ __('Professional shot sequencing, 30-degree rule, and coverage patterns for Hollywood-quality editing') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.video-wizard.settings') }}?category=shot_continuity" class="btn btn-outline-secondary">
                    <i class="fa fa-cog me-1"></i> {{ __('Settings') }}
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

    <!-- Status Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold {{ $enabled ? 'text-success' : 'text-muted' }}">
                                <i class="fa fa-{{ $enabled ? 'check-circle' : 'times-circle' }}"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('System Status') }}</div>
                            <div class="{{ $enabled ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $enabled ? __('Enabled') : __('Disabled') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold text-primary">{{ count($coveragePatterns) }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Coverage Patterns') }}</div>
                            <div class="text-dark">{{ __('Scene Types') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold text-info">{{ count($shotCompatibility) }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Shot Types') }}</div>
                            <div class="text-dark">{{ __('In Compatibility Matrix') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold text-warning">{{ $minScore }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Min Score') }}</div>
                            <div class="text-dark">{{ __('For Warnings') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coverage Patterns -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-1">
                        <i class="fa fa-list-check text-primary me-2"></i>
                        {{ __('Coverage Patterns') }}
                    </h5>
                    <p class="text-muted small mb-0">{{ __('Professional shot sequences for different scene types') }}</p>
                </div>
                <div class="card-body">
                    <div class="accordion" id="coverageAccordion">
                        @foreach($coveragePatterns as $type => $pattern)
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }} bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#pattern{{ $loop->index }}">
                                        <span class="badge bg-{{ $type == 'dialogue' ? 'primary' : ($type == 'action' ? 'danger' : ($type == 'emotional' ? 'info' : ($type == 'montage' ? 'warning' : 'secondary'))) }} me-2">
                                            {{ ucfirst($type) }}
                                        </span>
                                        <span class="small text-muted">{{ count($pattern) }} {{ __('shots') }}</span>
                                    </button>
                                </h2>
                                <div id="pattern{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                                    <div class="accordion-body pt-2">
                                        <div class="d-flex flex-wrap gap-2">
                                            @php
                                                asort($pattern);
                                            @endphp
                                            @foreach($pattern as $shot => $order)
                                                <div class="shot-sequence-item">
                                                    <span class="badge bg-light text-dark border">
                                                        <span class="badge bg-secondary rounded-pill me-1">{{ $order }}</span>
                                                        {{ ucwords(str_replace('-', ' ', $shot)) }}
                                                    </span>
                                                </div>
                                                @if(!$loop->last)
                                                    <i class="fa fa-arrow-right text-muted small align-self-center"></i>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="mt-3 small text-muted">
                                            @switch($type)
                                                @case('dialogue')
                                                    {{ __('Standard dialogue coverage: Establish scene, show both characters, then focus on each speaker with coverage shots and reactions.') }}
                                                    @break
                                                @case('action')
                                                    {{ __('Action sequences: Wide establishing context, follow action with tracking, punctuate with close-ups and inserts.') }}
                                                    @break
                                                @case('emotional')
                                                    {{ __('Emotional beats: Build from context to intimacy, ending with extreme close-ups for peak emotion.') }}
                                                    @break
                                                @case('montage')
                                                    {{ __('Montage sequences: Mix establishing and detail shots for visual variety and pacing.') }}
                                                    @break
                                                @case('establishing')
                                                    {{ __('Establishing sequences: Wide to narrow progression, placing subjects in their environment.') }}
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Rules & Features -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-1">
                        <i class="fa fa-gavel text-warning me-2"></i>
                        {{ __('Continuity Rules') }}
                    </h5>
                    <p class="text-muted small mb-0">{{ __('Professional filmmaking rules enforced by the system') }}</p>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <!-- 30-Degree Rule -->
                        <div class="list-group-item d-flex px-0">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-{{ $rules['30_degree'] ? 'success' : 'secondary' }} p-2">
                                    <i class="fa fa-rotate"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ __('30-Degree Rule') }}</h6>
                                <p class="text-muted small mb-0">
                                    {{ __('Camera must move at least 30 degrees between shots of similar size to avoid jarring cuts.') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $rules['30_degree'] ? 'success' : 'secondary' }}">
                                    {{ $rules['30_degree'] ? __('On') : __('Off') }}
                                </span>
                            </div>
                        </div>

                        <!-- Jump Cut Detection -->
                        <div class="list-group-item d-flex px-0">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-{{ $rules['jump_cut'] ? 'success' : 'secondary' }} p-2">
                                    <i class="fa fa-scissors"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ __('Jump Cut Detection') }}</h6>
                                <p class="text-muted small mb-0">
                                    {{ __('Flags cuts where same subject has minor position change without angle or size change.') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $rules['jump_cut'] ? 'success' : 'secondary' }}">
                                    {{ $rules['jump_cut'] ? __('On') : __('Off') }}
                                </span>
                            </div>
                        </div>

                        <!-- Movement Flow -->
                        <div class="list-group-item d-flex px-0">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-{{ $rules['movement_flow'] ? 'success' : 'secondary' }} p-2">
                                    <i class="fa fa-wind"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ __('Movement Flow') }}</h6>
                                <p class="text-muted small mb-0">
                                    {{ __('Analyzes camera movement continuity between shots (e.g., dynamic -> static transitions).') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $rules['movement_flow'] ? 'success' : 'secondary' }}">
                                    {{ $rules['movement_flow'] ? __('On') : __('Off') }}
                                </span>
                            </div>
                        </div>

                        <!-- Coverage Patterns -->
                        <div class="list-group-item d-flex px-0">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-{{ $rules['coverage_patterns'] ? 'success' : 'secondary' }} p-2">
                                    <i class="fa fa-list-check"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ __('Coverage Patterns') }}</h6>
                                <p class="text-muted small mb-0">
                                    {{ __('Suggests professional coverage based on scene type (dialogue, action, etc.).') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $rules['coverage_patterns'] ? 'success' : 'secondary' }}">
                                    {{ $rules['coverage_patterns'] ? __('On') : __('Off') }}
                                </span>
                            </div>
                        </div>

                        <!-- Auto Optimize -->
                        <div class="list-group-item d-flex px-0">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-{{ $rules['auto_optimize'] ? 'success' : 'secondary' }} p-2">
                                    <i class="fa fa-wand-magic-sparkles"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ __('Auto-Optimization') }}</h6>
                                <p class="text-muted small mb-0">
                                    {{ __('Automatically inserts transition shots (cutaways, reactions) when needed.') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $rules['auto_optimize'] ? 'success' : 'secondary' }}">
                                    {{ $rules['auto_optimize'] ? __('On') : __('Off') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shot Compatibility Matrix -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-1">
                <i class="fa fa-table-cells text-info me-2"></i>
                {{ __('Shot Compatibility Matrix') }}
            </h5>
            <p class="text-muted small mb-0">{{ __('Transition quality scores between shot types (higher = smoother transition)') }}</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 text-center" style="font-size: 11px;">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start" style="min-width: 100px;">{{ __('From / To') }}</th>
                            @foreach(array_keys($shotCompatibility) as $toShot)
                                <th class="text-center" style="writing-mode: vertical-lr; transform: rotate(180deg); min-width: 30px; height: 80px;">
                                    {{ ucwords(str_replace('-', ' ', $toShot)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shotCompatibility as $fromShot => $toShots)
                            <tr>
                                <td class="text-start bg-light fw-semibold">
                                    {{ ucwords(str_replace('-', ' ', $fromShot)) }}
                                </td>
                                @foreach(array_keys($shotCompatibility) as $toShot)
                                    @php
                                        $score = $toShots[$toShot] ?? 50;
                                        $colorClass = $score >= 85 ? 'success' : ($score >= 70 ? 'info' : ($score >= 60 ? 'warning' : 'danger'));
                                    @endphp
                                    <td class="bg-{{ $colorClass }} bg-opacity-{{ $score >= 85 ? '50' : ($score >= 70 ? '25' : '10') }}">
                                        @if(isset($toShots[$toShot]))
                                            {{ $score }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light border-0">
                <div class="d-flex gap-4 justify-content-center small">
                    <span><span class="badge bg-success">&nbsp;</span> 85+ {{ __('Excellent') }}</span>
                    <span><span class="badge bg-info">&nbsp;</span> 70-84 {{ __('Good') }}</span>
                    <span><span class="badge bg-warning">&nbsp;</span> 60-69 {{ __('Acceptable') }}</span>
                    <span><span class="badge bg-danger">&nbsp;</span> &lt;60 {{ __('Awkward') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Movement Continuity Matrix -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-1">
                <i class="fa fa-video text-success me-2"></i>
                {{ __('Movement Continuity Matrix') }}
            </h5>
            <p class="text-muted small mb-0">{{ __('How well camera movement intensities flow between consecutive shots') }}</p>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-start">{{ __('From / To') }}</th>
                                    @foreach(array_keys($movementContinuity) as $intensity)
                                        <th>{{ ucfirst($intensity) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($movementContinuity as $fromIntensity => $toIntensities)
                                    <tr>
                                        <td class="text-start bg-light fw-semibold">{{ ucfirst($fromIntensity) }}</td>
                                        @foreach(array_keys($movementContinuity) as $toIntensity)
                                            @php
                                                $score = $toIntensities[$toIntensity] ?? 50;
                                                $colorClass = $score >= 85 ? 'success' : ($score >= 70 ? 'info' : ($score >= 60 ? 'warning' : 'danger'));
                                            @endphp
                                            <td class="bg-{{ $colorClass }} bg-opacity-25">
                                                {{ $score }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded p-3 h-100">
                        <h6><i class="fa fa-info-circle text-info me-1"></i> {{ __('Movement Intensities') }}</h6>
                        <dl class="mb-0 small">
                            <dt class="text-muted">{{ __('Static') }}</dt>
                            <dd>{{ __('Locked off, no movement') }}</dd>
                            <dt class="text-muted">{{ __('Subtle') }}</dt>
                            <dd>{{ __('Slow, gentle movement') }}</dd>
                            <dt class="text-muted">{{ __('Moderate') }}</dt>
                            <dd>{{ __('Smooth, controlled motion') }}</dd>
                            <dt class="text-muted">{{ __('Dynamic') }}</dt>
                            <dd>{{ __('Energetic, active movement') }}</dd>
                            <dt class="text-muted">{{ __('Intense') }}</dt>
                            <dd class="mb-0">{{ __('Dramatic, rapid motion') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.shot-sequence-item {
    animation: fadeIn 0.3s ease-in;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>
@endsection
