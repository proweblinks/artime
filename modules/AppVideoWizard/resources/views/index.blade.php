@extends('layouts.app')

@section('title', __('Video Creator'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('appvideowizard::video-wizard', ['project' => $project])
</div>
@endsection
