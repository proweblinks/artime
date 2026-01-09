@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.index') }}">{{ __('Cinematography') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.genre-presets.index') }}">{{ __('Genre Presets') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Edit Genre Preset') }}: {{ $genrePreset->name }}</div>
    </div>
</div>

<div class="container py-4">
    <form action="{{ route('admin.video-wizard.cinematography.genre-presets.update', $genrePreset) }}" method="POST">
        @csrf
        @method('PUT')
        @include('appvideowizard::admin.cinematography.genre-presets._form', ['preset' => $genrePreset])
    </form>
</div>
@endsection
