@extends('layouts.app')

@section('title', __($title))

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('app-ai-tools::' . $component)
</div>
@endsection
