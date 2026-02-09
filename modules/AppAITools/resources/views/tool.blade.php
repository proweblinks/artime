@extends('layouts.app')

@section('title', __($title))

@section('css')
<style>
    html.aith-dark-page,
    html.aith-dark-page body {
        background: #0f172a !important;
    }
    .main.aith-dark-main {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%) !important;
        overflow-y: auto !important;
    }
    .main.aith-dark-main > .border-bottom {
        display: none !important;
    }
</style>
@endsection

@section('content')
@livewire('app-ai-tools::' . $component)
@endsection

@section('script')
<script>
    (function(){
        document.documentElement.classList.add('aith-dark-page');
        var m = document.querySelector('.main');
        if (m) m.classList.add('aith-dark-main');
    })();
</script>
@endsection
