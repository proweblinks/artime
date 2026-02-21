@extends('layouts.app')

@section('title', __('Content Studio'))

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
@livewire('app-ai-contents::content-hub')
@endsection
