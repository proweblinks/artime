@extends('layouts.app')

@section('title', __('Video Creator'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('appvideowizard::video-wizard', ['project' => $project])
</div>
@endsection

@section('script')
{{-- Debug: Check if Livewire loads --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof Livewire === 'undefined') {
                console.error('Livewire JS not loaded! Auto-injection may have failed.');
                // Show error to user
                var errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'position:fixed;top:0;left:0;right:0;background:red;color:white;padding:10px;text-align:center;z-index:9999;';
                errorDiv.innerHTML = 'Error: Livewire JavaScript failed to load. Check if Livewire auto-injection is working.';
                document.body.prepend(errorDiv);
            } else {
                console.log('Livewire loaded successfully:', Livewire);
            }
        }, 1000);
    });
</script>
@endsection
