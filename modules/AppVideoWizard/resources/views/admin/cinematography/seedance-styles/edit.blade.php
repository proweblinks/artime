@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.index') }}">{{ __('Cinematography') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.seedance-styles') }}">{{ __('Seedance Styles') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Edit Seedance Style') }}: {{ $seedanceStyle->name }}</div>
        <p class="text-muted mb-0 small">{{ __('Modify this Seedance style preset') }}</p>
    </div>
</div>

<div class="container py-4">
    <form action="{{ route('admin.video-wizard.cinematography.seedance-styles.update', $seedanceStyle) }}" method="POST">
        @csrf @method('PUT')
        @include('appvideowizard::admin.cinematography.seedance-styles._form')
    </form>
</div>
@endsection
