{{-- Step 1: Platform & Format Selection --}}
<div>
    <h2 class="text-xl font-bold mb-2">{{ __('Choose Your Platform') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Select where your video will be published') }}</p>

    {{-- Platform Selection --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
        @foreach($platforms as $id => $platformConfig)
            <div wire:click="selectPlatform('{{ $id }}')"
                 class="card bg-base-100 cursor-pointer hover:bg-primary/10 transition-all {{ $platform === $id ? 'ring-2 ring-primary' : '' }}">
                <div class="card-body items-center text-center p-4">
                    <i class="{{ $platformConfig['icon'] }} text-2xl {{ $platform === $id ? 'text-primary' : '' }}"></i>
                    <h3 class="font-semibold text-sm mt-2">{{ $platformConfig['name'] }}</h3>
                    <p class="text-xs text-base-content/60">{{ $platformConfig['description'] }}</p>
                    @if($platform === $id)
                        <div class="badge badge-primary badge-sm mt-2">{{ __('Selected') }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Format Selection --}}
    <h3 class="text-lg font-semibold mb-4">{{ __('Video Format') }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($formats as $id => $formatConfig)
            <div wire:click="selectFormat('{{ $id }}')"
                 class="card bg-base-100 cursor-pointer hover:bg-primary/10 transition-all {{ $format === $id ? 'ring-2 ring-primary' : '' }}">
                <div class="card-body items-center text-center p-4">
                    <div class="w-12 h-16 border-2 rounded flex items-center justify-center {{ $format === $id ? 'border-primary' : 'border-base-content/20' }}"
                         style="aspect-ratio: {{ str_replace(':', '/', $formatConfig['aspectRatio']) }};">
                        <span class="text-xs font-mono">{{ $formatConfig['aspectRatio'] }}</span>
                    </div>
                    <h3 class="font-semibold text-sm mt-2">{{ $formatConfig['name'] }}</h3>
                    <p class="text-xs text-base-content/60">{{ $formatConfig['description'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Duration Slider --}}
    <h3 class="text-lg font-semibold mb-4">{{ __('Target Duration') }}</h3>
    <div class="flex items-center gap-4">
        <input type="range" wire:model.live="targetDuration"
               min="{{ $platform ? $platforms[$platform]['minDuration'] ?? 15 : 15 }}"
               max="{{ $platform ? $platforms[$platform]['maxDuration'] ?? 300 : 300 }}"
               class="range range-primary flex-1" />
        <div class="badge badge-lg badge-outline">{{ $targetDuration }}s</div>
    </div>
    <div class="flex justify-between text-xs text-base-content/60 mt-1">
        <span>{{ $platform ? $platforms[$platform]['minDuration'] ?? 15 : 15 }}s</span>
        <span>{{ $platform ? $platforms[$platform]['maxDuration'] ?? 300 : 300 }}s</span>
    </div>

    {{-- Production Type --}}
    <h3 class="text-lg font-semibold mb-4 mt-8">{{ __('Production Type') }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($productionTypes as $typeId => $type)
            <div class="collapse collapse-arrow bg-base-100 {{ $productionType === $typeId ? 'ring-2 ring-primary' : '' }}">
                <input type="radio" name="production-type" wire:click="selectProductionType('{{ $typeId }}')" {{ $productionType === $typeId ? 'checked' : '' }} />
                <div class="collapse-title text-md font-medium">
                    <i class="{{ $type['icon'] }} mr-2"></i>
                    {{ $type['name'] }}
                    <span class="text-xs text-base-content/60 ml-2">{{ $type['description'] }}</span>
                </div>
                <div class="collapse-content">
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        @foreach($type['subTypes'] as $subId => $subType)
                            <button wire:click="selectProductionType('{{ $typeId }}', '{{ $subId }}')"
                                    class="btn btn-sm {{ $productionSubtype === $subId ? 'btn-primary' : 'btn-ghost' }}">
                                <i class="{{ $subType['icon'] }} mr-1"></i>
                                {{ $subType['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
