<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-purple-500 flex items-center justify-center">
            <i class="fa-light fa-swords text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Thumbnail Arena') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('AI-powered head-to-head thumbnail comparison') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Upload Panel --}}
        <div>
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Thumbnail A') }}</h3>
                    <div class="form-control">
                        <input type="file" wire:model="thumbnail1" accept="image/*" class="file-input file-input-bordered file-input-sm w-full">
                        @error('thumbnail1') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    @if($thumbnail1)
                        <div class="mt-3">
                            <img src="{{ $thumbnail1->temporaryUrl() }}" alt="Thumbnail A" class="w-full rounded-lg">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Thumbnail B') }}</h3>
                    <div class="form-control">
                        <input type="file" wire:model="thumbnail2" accept="image/*" class="file-input file-input-bordered file-input-sm w-full">
                        @error('thumbnail2') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    @if($thumbnail2)
                        <div class="mt-3">
                            <img src="{{ $thumbnail2->temporaryUrl() }}" alt="Thumbnail B" class="w-full rounded-lg">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Compare Button --}}
    <div class="text-center my-6">
        <button wire:click="compare" wire:loading.attr="disabled" class="btn btn-primary" {{ $isLoading ? 'disabled' : '' }}>
            <span wire:loading.remove wire:target="compare">
                <i class="fa-light fa-swords mr-2"></i>{{ __('Compare Thumbnails') }}
            </span>
            <span wire:loading wire:target="compare">
                <i class="fa-light fa-spinner-third fa-spin mr-2"></i>{{ __('Analyzing...') }}
            </span>
        </button>
    </div>

    @if(session('error'))
        <div class="alert alert-error text-sm mb-4">{{ session('error') }}</div>
    @endif

    {{-- Results --}}
    @if($result)
        <div class="space-y-4">
            {{-- Winner --}}
            @if(isset($result['winner']))
                <div class="card bg-base-200 border border-primary">
                    <div class="card-body text-center">
                        <div class="text-lg font-bold text-primary">
                            <i class="fa-light fa-trophy mr-2"></i>
                            {{ __('Winner: Thumbnail') }} {{ $result['winner'] }}
                        </div>
                        @if(isset($result['winner_reason']))
                            <p class="text-sm text-base-content/60 mt-1">{{ $result['winner_reason'] }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Side-by-Side Scores --}}
            @if(isset($result['analysis']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach(['A', 'B'] as $label)
                        @php $analysis = $result['analysis'][strtolower($label)] ?? $result['analysis'][$label] ?? null; @endphp
                        @if($analysis)
                            <div class="card bg-base-200 border border-base-300">
                                <div class="card-body">
                                    <h3 class="font-semibold mb-3">{{ __('Thumbnail') }} {{ $label }}</h3>
                                    @if(isset($analysis['scores']))
                                        <div class="space-y-3">
                                            @foreach($analysis['scores'] as $category => $score)
                                                <div>
                                                    <div class="flex justify-between text-xs mb-1">
                                                        <span>{{ ucfirst(str_replace('_', ' ', $category)) }}</span>
                                                        <span>{{ $score }}/100</span>
                                                    </div>
                                                    <progress class="progress {{ $score >= 70 ? 'progress-success' : ($score >= 40 ? 'progress-warning' : 'progress-error') }} w-full" value="{{ $score }}" max="100"></progress>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(isset($analysis['feedback']))
                                        <div class="mt-3 text-xs text-base-content/60">{{ $analysis['feedback'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Improvement Tips --}}
            @if(isset($result['improvements']))
                <div class="card bg-base-200 border border-base-300">
                    <div class="card-body">
                        <h3 class="font-semibold mb-3"><i class="fa-light fa-lightbulb mr-2"></i>{{ __('Improvement Tips') }}</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach($result['improvements'] as $tip)
                                <li class="flex items-start gap-2">
                                    <i class="fa-light fa-check-circle text-primary mt-0.5"></i>
                                    <span>{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
