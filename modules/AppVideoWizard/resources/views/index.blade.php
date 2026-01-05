@extends('layouts.app')

@section('title', __('Video Creator'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <livewire:video-wizard :project="$project ?? null" />
</div>
@endsection

@push('scripts')
<script src="{{ Module::asset('appvideowizard:js/video-preview-engine.js') }}"></script>
@endpush
