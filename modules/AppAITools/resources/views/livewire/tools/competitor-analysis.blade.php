<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
            <i class="fa-light fa-magnifying-glass-chart text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Competitor Analysis') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Deep-dive analysis of competitor content strategy') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Analyze Competitor') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Platform') }}</span></label>
                        <select wire:model="platform" class="select select-bordered select-sm w-full">
                            @foreach($platforms as $key => $p)
                                <option value="{{ $key }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Competitor Video URL') }}</span></label>
                        <input type="url" wire:model="competitorUrl" class="input input-bordered input-sm w-full" placeholder="https://...">
                        @error('competitorUrl') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">{{ __('Your Video URL') }}</span>
                            <span class="label-text-alt text-base-content/40">{{ __('Optional') }}</span>
                        </label>
                        <input type="url" wire:model="myUrl" class="input input-bordered input-sm w-full" placeholder="https://...">
                    </div>

                    <button wire:click="analyze" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="analyze">
                            <i class="fa-light fa-magnifying-glass-chart mr-1"></i>{{ __('Analyze') }}
                        </span>
                        <span wire:loading wire:target="analyze">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Analyzing...') }}
                        </span>
                    </button>

                    @if(session('error'))
                        <div class="alert alert-error mt-3 text-sm">{{ session('error') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Results Panel --}}
        <div class="lg:col-span-2">
            @if($result)
                <div class="space-y-4">
                    {{-- Score --}}
                    @if(isset($result['score']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center gap-4">
                                    <div class="radial-progress text-primary" style="--value:{{ $result['score'] }}; --size:4rem;">
                                        {{ $result['score'] }}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">{{ __('Competitor Score') }}</h3>
                                        <p class="text-sm text-base-content/60">{{ $result['summary'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- SWOT Analysis --}}
                    @if(isset($result['swot']))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach(['strengths' => ['S', 'text-success', 'bg-success/10'], 'weaknesses' => ['W', 'text-error', 'bg-error/10'], 'opportunities' => ['O', 'text-info', 'bg-info/10'], 'threats' => ['T', 'text-warning', 'bg-warning/10']] as $key => [$letter, $color, $bg])
                                <div class="card {{ $bg }} border border-base-300">
                                    <div class="card-body p-4">
                                        <h4 class="font-semibold {{ $color }} text-sm mb-2">{{ $letter }} - {{ ucfirst($key) }}</h4>
                                        <ul class="space-y-1 text-sm">
                                            @foreach($result['swot'][$key] ?? [] as $item)
                                                <li class="flex items-start gap-2"><span class="{{ $color }}">•</span> {{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Recommendations --}}
                    @if(isset($result['recommendations']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-lightbulb mr-2"></i>{{ __('Recommendations') }}</h3>
                                <div class="space-y-3">
                                    @foreach($result['recommendations'] as $rec)
                                        <div class="flex items-start gap-3 p-3 bg-base-300 rounded-lg">
                                            <span class="badge badge-sm {{ ($rec['priority'] ?? '') === 'high' ? 'badge-error' : (($rec['priority'] ?? '') === 'medium' ? 'badge-warning' : 'badge-info') }}">{{ $rec['priority'] ?? 'low' }}</span>
                                            <span class="text-sm">{{ $rec['text'] ?? $rec }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-magnifying-glass-chart text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a competitor video URL to analyze') }}</p>
                    <p class="text-sm mt-1">{{ __('Get SWOT analysis and actionable recommendations') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
