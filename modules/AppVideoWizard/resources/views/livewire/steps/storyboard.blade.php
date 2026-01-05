{{-- Step 4: Storyboard --}}
<div>
    <h2 class="text-xl font-bold mb-2">{{ __('Create Storyboard') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Generate AI images for each scene in your video') }}</p>

    @if(empty($script['scenes']))
        <div class="alert alert-warning">
            <i class="fa-light fa-exclamation-triangle"></i>
            <span>{{ __('Please generate a script first before creating the storyboard.') }}</span>
        </div>
    @else
        {{-- Bulk Actions --}}
        <div class="flex justify-between items-center mb-6">
            <div class="flex gap-2">
                <button class="btn btn-primary"
                        wire:click="$dispatch('generate-all-images')"
                        wire:loading.attr="disabled">
                    <i class="fa-light fa-images mr-2"></i>
                    {{ __('Generate All Images') }}
                </button>
            </div>
            <div class="text-sm text-base-content/60">
                {{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }} / {{ count($script['scenes']) }}
                {{ __('images generated') }}
            </div>
        </div>

        {{-- Scene Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($script['scenes'] as $index => $scene)
                @php
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                @endphp
                <div class="card bg-base-100">
                    <figure class="relative aspect-video bg-base-300">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $scene['title'] }}" class="w-full h-full object-cover">
                            <div class="absolute top-2 right-2">
                                <span class="badge badge-success badge-sm">
                                    <i class="fa-light fa-check mr-1"></i>
                                    {{ __('Generated') }}
                                </span>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-base-content/40">
                                <i class="fa-light fa-image text-3xl mb-2"></i>
                                <span class="text-sm">{{ __('No image yet') }}</span>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-sm">
                                    <span class="badge badge-outline badge-sm mr-2">{{ $index + 1 }}</span>
                                    {{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}
                                </h3>
                                <p class="text-xs text-base-content/60 mt-1 line-clamp-2">
                                    {{ $scene['visualDescription'] ?? $scene['narration'] }}
                                </p>
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-2">
                            @if($imageUrl)
                                <button class="btn btn-ghost btn-xs"
                                        wire:click="$dispatch('regenerate-image', { sceneIndex: {{ $index }} })">
                                    <i class="fa-light fa-arrows-rotate mr-1"></i>
                                    {{ __('Regenerate') }}
                                </button>
                            @else
                                <button class="btn btn-primary btn-xs"
                                        wire:click="$dispatch('generate-image', { sceneIndex: {{ $index }}, sceneId: '{{ $scene['id'] }}' })"
                                        wire:loading.attr="disabled">
                                    <i class="fa-light fa-sparkles mr-1"></i>
                                    {{ __('Generate') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
