{{-- Step 5: Animation & Voiceover --}}
<div>
    <h2 class="text-xl font-bold mb-2">{{ __('Animation & Voiceover') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Add voiceovers and animations to bring your scenes to life') }}</p>

    @if(empty($script['scenes']))
        <div class="alert alert-warning">
            <i class="fa-light fa-exclamation-triangle"></i>
            <span>{{ __('Please generate a script first.') }}</span>
        </div>
    @else
        {{-- Voiceover Settings --}}
        <div class="card bg-base-100 mb-6">
            <div class="card-body">
                <h3 class="card-title text-lg">
                    <i class="fa-light fa-microphone mr-2"></i>
                    {{ __('Voiceover Settings') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ __('Voice') }}</span>
                        </label>
                        <select wire:model.live="animation.voiceover.voice" class="select select-bordered">
                            <option value="alloy">Alloy (Neutral)</option>
                            <option value="echo">Echo (Male)</option>
                            <option value="fable">Fable (Storytelling)</option>
                            <option value="onyx">Onyx (Deep Male)</option>
                            <option value="nova">Nova (Female)</option>
                            <option value="shimmer">Shimmer (Bright Female)</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ __('Speed') }}: {{ $animation['voiceover']['speed'] ?? 1.0 }}x</span>
                        </label>
                        <input type="range"
                               wire:model.live="animation.voiceover.speed"
                               min="0.5" max="2.0" step="0.1"
                               class="range range-primary" />
                        <div class="flex justify-between text-xs text-base-content/60 mt-1">
                            <span>0.5x</span>
                            <span>1x</span>
                            <span>2x</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary"
                            wire:click="$dispatch('generate-all-voiceovers')"
                            wire:loading.attr="disabled">
                        <i class="fa-light fa-volume-up mr-2"></i>
                        {{ __('Generate All Voiceovers') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Scene List with Audio --}}
        <h3 class="text-lg font-semibold mb-4">{{ __('Scenes') }}</h3>
        <div class="space-y-4">
            @foreach($script['scenes'] as $index => $scene)
                @php
                    $animationScene = $animation['scenes'][$index] ?? null;
                    $voiceoverUrl = $animationScene['voiceoverUrl'] ?? null;
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                @endphp
                <div class="card bg-base-100">
                    <div class="card-body p-4">
                        <div class="flex gap-4">
                            {{-- Thumbnail --}}
                            <div class="w-32 h-20 rounded-lg overflow-hidden bg-base-300 flex-shrink-0">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <div class="flex items-center justify-center h-full text-base-content/40">
                                        <i class="fa-light fa-image"></i>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-semibold">
                                        <span class="badge badge-outline badge-sm mr-2">{{ $index + 1 }}</span>
                                        {{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}
                                    </h4>
                                    <span class="badge badge-ghost badge-sm">{{ $scene['duration'] }}s</span>
                                </div>

                                <p class="text-sm text-base-content/60 mt-1 line-clamp-2">
                                    {{ $scene['narration'] }}
                                </p>

                                {{-- Audio Player --}}
                                <div class="mt-3 flex items-center gap-2">
                                    @if($voiceoverUrl)
                                        <audio controls class="h-8 flex-1">
                                            <source src="{{ $voiceoverUrl }}" type="audio/mpeg">
                                        </audio>
                                        <button class="btn btn-ghost btn-xs"
                                                wire:click="$dispatch('regenerate-voiceover', { sceneIndex: {{ $index }} })">
                                            <i class="fa-light fa-arrows-rotate"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-sm"
                                                wire:click="$dispatch('generate-voiceover', { sceneIndex: {{ $index }}, sceneId: '{{ $scene['id'] }}' })"
                                                wire:loading.attr="disabled">
                                            <i class="fa-light fa-microphone mr-1"></i>
                                            {{ __('Generate Voiceover') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
