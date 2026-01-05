{{-- Step 6: Assembly --}}
<div>
    <h2 class="text-xl font-bold mb-2">{{ __('Assemble Your Video') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Configure transitions, music, and captions') }}</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Preview --}}
        <div class="card bg-base-100">
            <div class="card-body">
                <h3 class="card-title text-lg">
                    <i class="fa-light fa-play-circle mr-2"></i>
                    {{ __('Preview') }}
                </h3>
                <div class="aspect-video bg-black rounded-lg overflow-hidden mt-4">
                    <canvas id="preview-canvas" class="w-full h-full"></canvas>
                </div>
                <div class="flex items-center gap-4 mt-4">
                    <button class="btn btn-circle btn-sm" id="play-btn">
                        <i class="fa-light fa-play"></i>
                    </button>
                    <input type="range" class="range range-xs flex-1" id="timeline-slider" min="0" max="100" value="0">
                    <span class="text-sm font-mono" id="time-display">0:00 / 0:00</span>
                </div>
            </div>
        </div>

        {{-- Settings --}}
        <div class="space-y-4">
            {{-- Transitions --}}
            <div class="card bg-base-100">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <i class="fa-light fa-shuffle mr-2"></i>
                        {{ __('Transitions') }}
                    </h3>
                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text">{{ __('Default Transition') }}</span>
                        </label>
                        <select wire:model.live="assembly.defaultTransition" class="select select-bordered">
                            <option value="fade">{{ __('Fade') }}</option>
                            <option value="cut">{{ __('Cut') }}</option>
                            <option value="slide-left">{{ __('Slide Left') }}</option>
                            <option value="slide-right">{{ __('Slide Right') }}</option>
                            <option value="zoom-in">{{ __('Zoom In') }}</option>
                            <option value="zoom-out">{{ __('Zoom Out') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Captions --}}
            <div class="card bg-base-100">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <i class="fa-light fa-closed-captioning mr-2"></i>
                        {{ __('Captions') }}
                    </h3>

                    <div class="form-control mt-4">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Enable Captions') }}</span>
                            <input type="checkbox" wire:model.live="assembly.captions.enabled" class="toggle toggle-primary">
                        </label>
                    </div>

                    @if($assembly['captions']['enabled'])
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">{{ __('Style') }}</span>
                                </label>
                                <select wire:model.live="assembly.captions.style" class="select select-bordered select-sm">
                                    @foreach($captionStyles as $styleId => $style)
                                        <option value="{{ $styleId }}">{{ $style['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">{{ __('Position') }}</span>
                                </label>
                                <select wire:model.live="assembly.captions.position" class="select select-bordered select-sm">
                                    <option value="top">{{ __('Top') }}</option>
                                    <option value="middle">{{ __('Middle') }}</option>
                                    <option value="bottom">{{ __('Bottom') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-control mt-4">
                            <label class="label">
                                <span class="label-text">{{ __('Size') }}: {{ $assembly['captions']['size'] ?? 1 }}x</span>
                            </label>
                            <input type="range"
                                   wire:model.live="assembly.captions.size"
                                   min="0.5" max="2" step="0.1"
                                   class="range range-sm range-primary">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Background Music --}}
            <div class="card bg-base-100">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <i class="fa-light fa-music mr-2"></i>
                        {{ __('Background Music') }}
                    </h3>

                    <div class="form-control mt-4">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Enable Music') }}</span>
                            <input type="checkbox" wire:model.live="assembly.music.enabled" class="toggle toggle-primary">
                        </label>
                    </div>

                    @if($assembly['music']['enabled'])
                        <div class="form-control mt-4">
                            <label class="label">
                                <span class="label-text">{{ __('Volume') }}: {{ $assembly['music']['volume'] ?? 30 }}%</span>
                            </label>
                            <input type="range"
                                   wire:model.live="assembly.music.volume"
                                   min="0" max="100" step="5"
                                   class="range range-sm range-primary">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize preview engine when step loads
    document.addEventListener('livewire:navigated', () => {
        initPreviewEngine();
    });
</script>
@endpush
