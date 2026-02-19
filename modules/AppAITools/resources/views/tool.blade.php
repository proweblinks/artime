@extends('layouts.app')

@section('title', __($title))

@section('css')
<style>
    .main {
        background: #f0f4f8 !important;
        overflow-y: auto !important;
    }
    .main > .border-bottom {
        display: none !important;
    }
</style>
@endsection

@section('content')
@livewire('app-ai-tools::' . $component)
@endsection
