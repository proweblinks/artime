<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-yellow-500 flex items-center justify-center">
            <i class="fa-light fa-image text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('AI Thumbnails') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Generate eye-catching thumbnails with AI') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Generate Thumbnail') }}</h3>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Video Title') }}</span></label>
                        <input type="text" wire:model="title" class="input input-bordered input-sm w-full" placeholder="{{ __('Enter your video title') }}">
                        @error('title') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Style') }}</span></label>
                        <select wire:model="style" class="select select-bordered select-sm w-full">
                            @foreach($styles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Aspect Ratio') }}</span></label>
                        <div class="flex gap-2">
                            @foreach(['16:9' => 'Landscape', '9:16' => 'Portrait', '1:1' => 'Square'] as $ratio => $label)
                                <label class="flex-1">
                                    <input type="radio" wire:model="aspectRatio" value="{{ $ratio }}" class="hidden peer">
                                    <div class="text-center p-2 rounded-lg border border-base-300 cursor-pointer peer-checked:border-primary peer-checked:bg-primary/10 text-xs">
                                        {{ $label }}<br><span class="text-base-content/40">{{ $ratio }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">{{ __('Custom Prompt') }}</span>
                            <span class="label-text-alt text-base-content/40">{{ __('Optional') }}</span>
                        </label>
                        <textarea wire:model="customPrompt" class="textarea textarea-bordered textarea-sm w-full" rows="3" placeholder="{{ __('Additional details for the thumbnail...') }}"></textarea>
                    </div>

                    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="generate">
                            <i class="fa-light fa-wand-magic-sparkles mr-1"></i>{{ __('Generate') }}
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
            @if($result && isset($result['images']))
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($result['images'] as $image)
                            <div class="card bg-base-200 border border-base-300 overflow-hidden">
                                <figure>
                                    <img src="{{ $image['url'] ?? asset($image['path'] ?? '') }}" alt="Generated thumbnail" class="w-full">
                                </figure>
                                <div class="card-body p-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ $image['url'] ?? asset($image['path'] ?? '') }}" download class="btn btn-ghost btn-xs">
                                            <i class="fa-light fa-download mr-1"></i>{{ __('Download') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-image text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a title and style to generate thumbnails') }}</p>
                    <p class="text-sm mt-1">{{ __('AI will create eye-catching thumbnails for your content') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
