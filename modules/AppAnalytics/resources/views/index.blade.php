@extends('layouts.app')

@section('title', __('Analytics'))

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
@livewire('app-analytics::analytics-dashboard')
@endsection
