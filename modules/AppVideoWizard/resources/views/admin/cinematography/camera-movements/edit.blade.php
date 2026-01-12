@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.index') }}">{{ __('Cinematography') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.camera-movements') }}">{{ __('Camera Movements') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Edit Camera Movement') }}: {{ $cameraMovement->name }}</div>
        <p class="text-muted mb-0 small">
            <span class="badge bg-{{ $cameraMovement->category == 'zoom' ? 'primary' : ($cameraMovement->category == 'dolly' ? 'success' : ($cameraMovement->category == 'crane' ? 'info' : ($cameraMovement->category == 'pan_tilt' ? 'warning' : ($cameraMovement->category == 'arc' ? 'danger' : 'secondary')))) }} me-1">
                {{ $cameraMovement->getCategoryLabel() }}
            </span>
            <code>{{ $cameraMovement->slug }}</code>
        </p>
    </div>
</div>

<div class="container py-4">
    <form action="{{ route('admin.video-wizard.cinematography.camera-movements.update', $cameraMovement) }}" method="POST">
        @csrf
        @method('PUT')
        @include('appvideowizard::admin.cinematography.camera-movements._form')
    </form>
</div>
@endsection
