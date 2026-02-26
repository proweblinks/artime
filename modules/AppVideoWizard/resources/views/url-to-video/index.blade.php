@extends('layouts.app')

@section('title', __('URL to Video'))

@section('content')
<div class="container-fluid px-0">
    @livewire('appvideowizard::url-to-video', ['sharedProject' => $sharedProject ?? null])
</div>
@endsection
