@extends('layouts.app')

@section('title', __('AI Tools'))

@section('css')
<style>
    .main.aith-dark-main {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%) !important;
    }
    .main.aith-dark-main > .border-bottom {
        display: none !important;
    }
</style>
@endsection

@section('content')
@livewire('app-ai-tools::tools-hub')
@endsection

@section('script')
<script>
    (function(){
        var m = document.querySelector('.main');
        if (m) m.classList.add('aith-dark-main');
    })();
</script>
@endsection
