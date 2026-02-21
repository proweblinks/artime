@extends('layouts.app')

@section('title', __('AI Tools'))

@section('css')
<style>
    .main {
        background: #ffffff !important;
        overflow-y: auto !important;
    }
    .main > .border-bottom {
        display: none !important;
    }
</style>
@endsection

@section('content')
@livewire('app-ai-tools::tools-hub')
@endsection
