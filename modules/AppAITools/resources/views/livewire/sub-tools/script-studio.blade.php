<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center">
            <i class="fa-light fa-scroll text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Script Studio') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Generate complete video scripts with hooks, sections and CTAs') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Generate Script') }}</h3>

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
                        <textarea wire:model="topic" class="textarea textarea-bordered textarea-sm w-full" rows="3" placeholder="{{ __('What is your video about?') }}"></textarea>
                        @error('topic') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Duration') }}</span></label>
                        <select wire:model="duration" class="select select-bordered select-sm w-full">
                            @foreach($durations as $key => $d)
                                <option value="{{ $key }}">{{ $d['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Style') }}</span></label>
                        <select wire:model="style" class="select select-bordered select-sm w-full">
                            @foreach($styles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="generate">
                            <i class="fa-light fa-scroll mr-1"></i>{{ __('Generate Script') }}
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
            @if($result)
                <div class="space-y-4">
                    {{-- Script Stats --}}
                    @if(isset($result['word_count']))
                        <div class="flex gap-4">
                            <div class="badge badge-lg badge-ghost">
                                <i class="fa-light fa-text-size mr-1"></i>
                                {{ number_format($result['word_count']) }} {{ __('words') }}
                            </div>
                            @if(isset($result['estimated_duration']))
                                <div class="badge badge-lg badge-ghost">
                                    <i class="fa-light fa-clock mr-1"></i>
                                    {{ $result['estimated_duration'] }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Script Content --}}
                    <div class="card bg-base-200 border border-base-300">
                        <div class="card-body">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold"><i class="fa-light fa-scroll mr-2"></i>{{ __('Script') }}</h3>
                                <button onclick="navigator.clipboard.writeText(document.getElementById('script-content').innerText)" class="btn btn-ghost btn-xs">
                                    <i class="fa-light fa-copy mr-1"></i>{{ __('Copy') }}
                                </button>
                            </div>
                            <div id="script-content" class="prose prose-sm max-w-none whitespace-pre-wrap bg-base-300 p-4 rounded-lg text-sm">{{ $result['script'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-scroll text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a topic to generate a complete script') }}</p>
                    <p class="text-sm mt-1">{{ __('Including hook, sections, and call-to-action') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
