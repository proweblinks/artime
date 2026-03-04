{{-- Voice Selection Modal --}}
@if($showVoiceModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;"
     wire:click.self="$set('showVoiceModal', false)">
    <div class="card border-0" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 16px; width: 480px; max-height: 80vh; overflow-y: auto; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <h5 class="mb-0 fw-bold" style="color: var(--at-text, #1a1a2e);">{{ __('Select Voice') }}</h5>
            <button wire:click="$set('showVoiceModal', false)" type="button" class="btn-close"></button>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="list-group list-group-flush">
                @foreach($this->voices as $voice)
                    <button wire:click="selectVoice('{{ $voice['id'] }}', '{{ $voice['provider'] }}')"
                            type="button"
                            class="list-group-item list-group-item-action border-0 d-flex align-items-center gap-3 px-3 py-3"
                            style="background: {{ $selectedVoice === $voice['id'] ? 'rgba(3,252,244,0.06)' : 'transparent' }}; border-radius: 10px; color: var(--at-text, #1a1a2e);">

                        {{-- Voice Icon --}}
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 40px; height: 40px; border-radius: 50%; background: {{ $selectedVoice === $voice['id'] ? '#03fcf4' : '#f5f7fa' }};">
                            @if($voice['gender'] === 'female')
                                <i class="fa-light fa-venus" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#f472b6' }};"></i>
                            @elseif($voice['gender'] === 'male')
                                <i class="fa-light fa-mars" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#60a5fa' }};"></i>
                            @else
                                <i class="fa-light fa-microphone" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#a78bfa' }};"></i>
                            @endif
                        </div>

                        {{-- Voice Info --}}
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size: 0.9rem;">{{ $voice['name'] }}</div>
                            <small style="color: var(--at-text-muted, #94a0b8);">{{ $voice['description'] }}</small>
                        </div>

                        {{-- Provider Badge --}}
                        @if($voice['provider'])
                            <span class="badge" style="background: #f5f7fa; color: var(--at-text-secondary, #5a6178); font-size: 0.65rem;">
                                {{ ucfirst($voice['provider']) }}
                            </span>
                        @endif

                        {{-- Selected Check --}}
                        @if($selectedVoice === $voice['id'])
                            <i class="fa-solid fa-check-circle" style="color: #03fcf4;"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
