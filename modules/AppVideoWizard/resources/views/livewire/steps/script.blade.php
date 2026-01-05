{{-- Step 3: Script Generation --}}
<div x-data="{ selectedScene: null, editingScene: null }">
    <h2 class="text-xl font-bold mb-2">{{ __('Generate Your Script') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('AI will create a scene-by-scene script for your video') }}</p>

    {{-- Generate Script Button --}}
    @if(empty($script['scenes']))
        <div class="text-center py-12">
            <i class="fa-light fa-scroll text-5xl text-base-content/30 mb-4"></i>
            <h3 class="text-lg font-semibold mb-2">{{ __('Ready to Generate Script') }}</h3>
            <p class="text-base-content/60 mb-4">{{ __('Based on your concept, AI will create a detailed script with scenes') }}</p>
            <button class="btn btn-primary btn-lg"
                    wire:click="$dispatch('generate-script')"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="$dispatch('generate-script')">
                    <i class="fa-light fa-sparkles mr-2"></i>
                    {{ __('Generate Script') }}
                </span>
                <span wire:loading wire:target="$dispatch('generate-script')">
                    <span class="loading loading-spinner mr-2"></span>
                    {{ __('Generating...') }}
                </span>
            </button>
        </div>
    @else
        {{-- Script Overview --}}
        <div class="card bg-base-100 mb-6">
            <div class="card-body">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="card-title">{{ $script['title'] ?? __('Untitled') }}</h3>
                        <p class="text-sm text-base-content/60">
                            {{ count($script['scenes']) }} {{ __('scenes') }} &bull;
                            {{ array_sum(array_column($script['scenes'], 'duration')) }}s {{ __('total') }}
                        </p>
                    </div>
                    <button class="btn btn-ghost btn-sm"
                            wire:click="$dispatch('generate-script')"
                            wire:loading.attr="disabled">
                        <i class="fa-light fa-arrows-rotate mr-1"></i>
                        {{ __('Regenerate') }}
                    </button>
                </div>

                @if(!empty($script['hook']))
                    <div class="mt-4 p-3 bg-primary/10 rounded-lg">
                        <span class="badge badge-primary badge-sm mb-1">{{ __('Hook') }}</span>
                        <p>{{ $script['hook'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Scenes List --}}
        <h3 class="text-lg font-semibold mb-4">{{ __('Scenes') }}</h3>
        <div class="space-y-4">
            @foreach($script['scenes'] as $index => $scene)
                <div class="card bg-base-100 {{ $editingScene === $index ? 'ring-2 ring-primary' : '' }}"
                     x-data="{ expanded: false }">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="badge badge-lg badge-outline">{{ $index + 1 }}</div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-semibold">{{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-ghost badge-sm">{{ $scene['duration'] }}s</span>
                                        <button class="btn btn-ghost btn-xs" @click="expanded = !expanded">
                                            <i class="fa-light" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-sm text-base-content/80 mt-2" x-show="!expanded" x-cloak>
                                    {{ \Illuminate\Support\Str::limit($scene['narration'], 100) }}
                                </p>

                                <div x-show="expanded" x-cloak class="mt-4 space-y-4">
                                    <div>
                                        <label class="label label-text font-semibold text-xs">{{ __('Narration') }}</label>
                                        <p class="text-sm">{{ $scene['narration'] }}</p>
                                    </div>
                                    <div>
                                        <label class="label label-text font-semibold text-xs">{{ __('Visual Description') }}</label>
                                        <p class="text-sm text-base-content/60">{{ $scene['visualDescription'] ?? __('No visual description') }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button class="btn btn-ghost btn-xs">
                                            <i class="fa-light fa-edit mr-1"></i>
                                            {{ __('Edit') }}
                                        </button>
                                        <button class="btn btn-ghost btn-xs text-error">
                                            <i class="fa-light fa-trash mr-1"></i>
                                            {{ __('Remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CTA --}}
        @if(!empty($script['cta']))
            <div class="card bg-base-100 mt-4">
                <div class="card-body p-4">
                    <span class="badge badge-secondary badge-sm mb-1">{{ __('Call to Action') }}</span>
                    <p>{{ $script['cta'] }}</p>
                </div>
            </div>
        @endif
    @endif
</div>
