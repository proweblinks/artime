<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
            <i class="fa-light fa-bolt text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Viral Hook Lab') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Generate attention-grabbing hooks with effectiveness scores') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Generate Hooks') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Platform') }}</span></label>
                        <select wire:model="platform" class="select select-bordered select-sm w-full">
                            @foreach($platforms as $key => $p)
                                <option value="{{ $key }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Topic') }}</span></label>
                        <textarea wire:model="topic" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="{{ __('What is your video about?') }}"></textarea>
                        @error('topic') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Hook Style') }}</span></label>
                        <select wire:model="hookStyle" class="select select-bordered select-sm w-full">
                            @foreach($hookStyles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Number of Hooks') }}</span></label>
                        <input type="range" wire:model="count" min="3" max="10" class="range range-sm range-primary" step="1">
                        <div class="text-xs text-center text-base-content/60 mt-1">{{ $count }} {{ __('hooks') }}</div>
                    </div>

                    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="generate">
                            <i class="fa-light fa-bolt mr-1"></i>{{ __('Generate Hooks') }}
                        </span>
                        <span wire:loading wire:target="generate">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Generating...') }}
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
            @if($result && isset($result['hooks']))
                <div class="space-y-3">
                    @foreach($result['hooks'] as $index => $hook)
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="text-xs text-base-content/40 mb-1">#{{ $index + 1 }}</div>
                                        @if(isset($hook['score']))
                                            <div class="radial-progress text-xs {{ $hook['score'] >= 80 ? 'text-success' : ($hook['score'] >= 60 ? 'text-warning' : 'text-error') }}" style="--value:{{ $hook['score'] }}; --size:2.5rem; --thickness:3px;">
                                                {{ $hook['score'] }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-sm">{{ $hook['text'] ?? $hook }}</p>
                                        @if(isset($hook['explanation']))
                                            <p class="text-xs text-base-content/60 mt-2">{{ $hook['explanation'] }}</p>
                                        @endif
                                    </div>
                                    <button onclick="navigator.clipboard.writeText('{{ addslashes($hook['text'] ?? $hook) }}')" class="btn btn-ghost btn-xs shrink-0">
                                        <i class="fa-light fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-bolt text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a topic to generate viral hooks') }}</p>
                    <p class="text-sm mt-1">{{ __('Each hook comes with an effectiveness score') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
