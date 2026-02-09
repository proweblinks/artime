@extends('layouts.app')

@section('title', __('AI Tools'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('app-ai-tools::tools-hub')
</div>
@endsection
