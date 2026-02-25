{{-- Voice Selection Modal --}}
@if($showVoiceModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 10100;"
     wire:click.self="$set('showVoiceModal', false)">
    <div class="card border-0" style="background: #1a1a1a; border-radius: 16px; width: 480px; max-height: 80vh; overflow-y: auto;">
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <h5 class="mb-0 text-white fw-bold">{{ __('Select Voice') }}</h5>
            <button wire:click="$set('showVoiceModal', false)" type="button" class="btn-close btn-close-white"></button>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="list-group list-group-flush">
                @foreach($this->voices as $voice)
                    <button wire:click="selectVoice('{{ $voice['id'] }}', '{{ $voice['provider'] }}')"
                            type="button"
                            class="list-group-item list-group-item-action border-0 d-flex align-items-center gap-3 px-3 py-3"
                            style="background: {{ $selectedVoice === $voice['id'] ? '#2a2a1a' : 'transparent' }}; border-radius: 10px; color: #fff;">

                        {{-- Voice Icon --}}
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 40px; height: 40px; border-radius: 50%; background: {{ $selectedVoice === $voice['id'] ? '#f97316' : '#2a2a2a' }};">
                            @if($voice['gender'] === 'female')
                                <i class="fa-light fa-venus" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#f472b6' }};"></i>
                            @elseif($voice['gender'] === 'male')
                                <i class="fa-light fa-mars" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#60a5fa' }};"></i>
                            @else
                                <i class="fa-light fa-microphone" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#a78bfa' }};"></i>
                            @endif
                        </div>

                        {{-- Voice Info --}}
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size: 0.9rem;">{{ $voice['name'] }}</div>
                            <small class="text-muted">{{ $voice['description'] }}</small>
                        </div>

                        {{-- Provider Badge --}}
                        @if($voice['provider'])
                            <span class="badge" style="background: #2a2a2a; color: #888; font-size: 0.65rem;">
                                {{ ucfirst($voice['provider']) }}
                            </span>
                        @endif

                        {{-- Selected Check --}}
                        @if($selectedVoice === $voice['id'])
                            <i class="fa-solid fa-check-circle" style="color: #f97316;"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
