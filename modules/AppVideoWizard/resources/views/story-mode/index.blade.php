@extends('layouts.app')

@section('title', __('Story Mode'))

@section('content')
<div class="container-fluid px-0">
    @livewire('appvideowizard::story-mode', ['sharedProject' => $sharedProject ?? null])
</div>
@endsection
