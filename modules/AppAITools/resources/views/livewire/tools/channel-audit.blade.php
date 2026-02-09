<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 flex items-center justify-center">
            <i class="fa-light fa-clipboard-check text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Channel Audit Pro') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Comprehensive channel analysis with growth recommendations') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Audit Channel') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Platform') }}</span></label>
                        <select wire:model="platform" class="select select-bordered select-sm w-full">
                            @foreach($platforms as $key => $p)
                                <option value="{{ $key }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Channel URL') }}</span></label>
                        <input type="url" wire:model="channelUrl" class="input input-bordered input-sm w-full" placeholder="https://youtube.com/@channel">
                        @error('channelUrl') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <button wire:click="audit" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="audit">
                            <i class="fa-light fa-clipboard-check mr-1"></i>{{ __('Run Audit') }}
                        </span>
                        <span wire:loading wire:target="audit">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Auditing...') }}
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
                    {{-- Overall Score --}}
                    @if(isset($result['overall_score']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center gap-6">
                                    <div class="radial-progress text-primary text-2xl font-bold" style="--value:{{ $result['overall_score'] }}; --size:5rem; --thickness:6px;">
                                        {{ $result['overall_score'] }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ __('Overall Score') }}</h3>
                                        <p class="text-sm text-base-content/60">{{ $result['overall_summary'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Category Scores --}}
                    @if(isset($result['categories']))
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($result['categories'] as $cat)
                                <div class="card bg-base-200 border border-base-300">
                                    <div class="card-body p-4 text-center">
                                        <div class="radial-progress text-sm mx-auto {{ ($cat['score'] ?? 0) >= 70 ? 'text-success' : (($cat['score'] ?? 0) >= 40 ? 'text-warning' : 'text-error') }}" style="--value:{{ $cat['score'] ?? 0 }}; --size:3rem; --thickness:4px;">
                                            {{ $cat['score'] ?? 0 }}
                                        </div>
                                        <div class="text-xs font-medium mt-2">{{ $cat['name'] ?? '' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Metrics --}}
                    @if(isset($result['metrics']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-chart-mixed mr-2"></i>{{ __('Key Metrics') }}</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach($result['metrics'] as $metric)
                                        <div class="text-center">
                                            <div class="text-xl font-bold text-primary">{{ $metric['value'] ?? '-' }}</div>
                                            <div class="text-xs text-base-content/60">{{ $metric['label'] ?? '' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
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
                                            @php $priority = $rec['priority'] ?? 'low'; @endphp
                                            <span class="badge badge-sm {{ $priority === 'high' ? 'badge-error' : ($priority === 'medium' ? 'badge-warning' : 'badge-info') }}">{{ $priority }}</span>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium">{{ $rec['title'] ?? '' }}</div>
                                                <div class="text-xs text-base-content/60 mt-1">{{ $rec['description'] ?? $rec['text'] ?? '' }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-clipboard-check text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a channel URL to run a comprehensive audit') }}</p>
                    <p class="text-sm mt-1">{{ __('Get scores, metrics, and growth recommendations') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
