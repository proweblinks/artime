<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" style="max-width: 900px;">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.index') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back to Hub') }}
        </a>
    </div>

    {{-- Header --}}
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff; margin: 0 0 0.5rem;">
            <span style="font-size: 1.5rem;">🧰</span> {{ __('More AI Tools') }}
        </h1>
        <p style="font-size: 0.9375rem; color: rgba(255,255,255,0.5); margin: 0;">
            {{ __('Additional AI-powered tools for content creators') }}
        </p>
    </div>

    {{-- Sub-Tools Grid --}}
    <div class="aith-grid-2">
        @foreach($subTools as $key => $tool)
        <a href="{{ route($tool['route']) }}" class="aith-subtool-card">
            <div class="aith-subtool-icon {{ $tool['color'] ?? 'aith-icon-blue' }}"
                style="background: {{ $tool['gradient'] ?? 'linear-gradient(135deg, #6366f1, #7c3aed)' }};">
                <i class="{{ $tool['icon'] }}"></i>
            </div>
            <div>
                <h3 class="aith-subtool-name">{{ __($tool['name']) }}</h3>
                <p class="aith-subtool-desc">{{ __($tool['description']) }}</p>
                @if(isset($tool['credits']) && $tool['credits'] > 0)
                <div class="aith-subtool-credits">
                    <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-coins"></i> {{ $tool['credits'] }} {{ __('credits') }}</span>
                </div>
                @endif
            </div>
        </a>
        @endforeach
    </div>

</div>
</div>
