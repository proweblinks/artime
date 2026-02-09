<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center">
            <i class="fa-light fa-arrow-trend-up text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Trend Predictor') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Predict upcoming trends and get content ideas') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Predict Trends') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Platform') }}</span></label>
                        <select wire:model="platform" class="select select-bordered select-sm w-full">
                            @foreach($platforms as $key => $p)
                                <option value="{{ $key }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Niche / Topic') }}</span></label>
                        <input type="text" wire:model="niche" class="input input-bordered input-sm w-full" placeholder="{{ __('e.g. AI tutorials, cooking, fitness') }}">
                        @error('niche') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Region') }}</span></label>
                        <select wire:model="region" class="select select-bordered select-sm w-full">
                            <option value="US">United States</option>
                            <option value="GB">United Kingdom</option>
                            <option value="CA">Canada</option>
                            <option value="AU">Australia</option>
                            <option value="IN">India</option>
                            <option value="DE">Germany</option>
                            <option value="FR">France</option>
                            <option value="BR">Brazil</option>
                            <option value="JP">Japan</option>
                            <option value="KR">South Korea</option>
                        </select>
                    </div>

                    <button wire:click="predict" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="predict">
                            <i class="fa-light fa-crystal-ball mr-1"></i>{{ __('Predict Trends') }}
                        </span>
                        <span wire:loading wire:target="predict">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Predicting...') }}
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
                    {{-- Current Trends --}}
                    @if(isset($result['current_trends']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-fire mr-2 text-orange-500"></i>{{ __('Current Trends') }}</h3>
                                <div class="space-y-3">
                                    @foreach($result['current_trends'] as $trend)
                                        <div class="flex items-center gap-3 p-3 bg-base-300 rounded-lg">
                                            @php $status = $trend['status'] ?? 'stable'; @endphp
                                            <i class="fa-light {{ $status === 'rising' ? 'fa-arrow-trend-up text-success' : ($status === 'declining' ? 'fa-arrow-trend-down text-error' : 'fa-minus text-warning') }}"></i>
                                            <div class="flex-1">
                                                <div class="font-medium text-sm">{{ $trend['topic'] ?? $trend }}</div>
                                                @if(isset($trend['description']))
                                                    <div class="text-xs text-base-content/60">{{ $trend['description'] }}</div>
                                                @endif
                                            </div>
                                            @if(isset($trend['confidence']))
                                                <div class="badge badge-sm badge-ghost">{{ $trend['confidence'] }}%</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Predicted Trends --}}
                    @if(isset($result['predicted_trends']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-crystal-ball mr-2 text-purple-500"></i>{{ __('Predicted Trends') }}</h3>
                                <div class="space-y-3">
                                    @foreach($result['predicted_trends'] as $trend)
                                        <div class="p-3 bg-base-300 rounded-lg">
                                            <div class="font-medium text-sm">{{ $trend['topic'] ?? $trend }}</div>
                                            @if(isset($trend['reasoning']))
                                                <div class="text-xs text-base-content/60 mt-1">{{ $trend['reasoning'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Content Ideas --}}
                    @if(isset($result['content_ideas']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-lightbulb mr-2 text-yellow-500"></i>{{ __('Content Ideas') }}</h3>
                                <div class="space-y-2">
                                    @foreach($result['content_ideas'] as $idea)
                                        <div class="flex items-center gap-2 p-3 bg-base-300 rounded-lg group">
                                            <span class="flex-1 text-sm">{{ $idea['title'] ?? $idea }}</span>
                                            <button onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText)" class="btn btn-ghost btn-xs opacity-0 group-hover:opacity-100">
                                                <i class="fa-light fa-copy"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-arrow-trend-up text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a niche to predict upcoming trends') }}</p>
                    <p class="text-sm mt-1">{{ __('Get content ideas before they go viral') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
