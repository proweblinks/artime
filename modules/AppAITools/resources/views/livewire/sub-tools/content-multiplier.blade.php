<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center">
            <i class="fa-light fa-clone text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Content Multiplier') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Repurpose your content into multiple formats') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Content Input') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Original Script / Transcript') }}</span></label>
                        <textarea wire:model="originalContent" class="textarea textarea-bordered textarea-sm w-full" rows="8" placeholder="{{ __('Paste your video script or transcript here...') }}"></textarea>
                        @error('originalContent') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Output Formats') }}</span></label>
                        <div class="space-y-2">
                            @foreach($formats as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedFormats" value="{{ $key }}" class="checkbox checkbox-sm checkbox-primary">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedFormats') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <button wire:click="multiply" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="multiply">
                            <i class="fa-light fa-clone mr-1"></i>{{ __('Multiply Content') }}
                        </span>
                        <span wire:loading wire:target="multiply">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Processing...') }}
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
            @if($result && isset($result['outputs']))
                <div x-data="{ activeTab: '{{ array_key_first($result['outputs']) }}' }">
                    {{-- Tabs --}}
                    <div class="tabs tabs-bordered mb-4 flex-wrap">
                        @foreach($result['outputs'] as $format => $content)
                            <a class="tab tab-sm" :class="activeTab === '{{ $format }}' ? 'tab-active' : ''" @click="activeTab = '{{ $format }}'">
                                {{ $formats[$format] ?? ucfirst(str_replace('_', ' ', $format)) }}
                            </a>
                        @endforeach
                    </div>

                    {{-- Tab Content --}}
                    @foreach($result['outputs'] as $format => $content)
                        <div x-show="activeTab === '{{ $format }}'" x-cloak>
                            <div class="card bg-base-200 border border-base-300">
                                <div class="card-body">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="font-semibold text-sm">{{ $formats[$format] ?? ucfirst(str_replace('_', ' ', $format)) }}</h3>
                                        <button onclick="navigator.clipboard.writeText(this.closest('.card-body').querySelector('.content-output').innerText)" class="btn btn-ghost btn-xs">
                                            <i class="fa-light fa-copy mr-1"></i>{{ __('Copy') }}
                                        </button>
                                    </div>
                                    <div class="content-output whitespace-pre-wrap text-sm bg-base-300 p-4 rounded-lg">{{ $content }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-clone text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Paste your content and select output formats') }}</p>
                    <p class="text-sm mt-1">{{ __('Transform one piece of content into many') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
