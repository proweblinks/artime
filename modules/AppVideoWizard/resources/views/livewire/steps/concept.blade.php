{{-- Step 2: Concept Development --}}
<div x-data="{ isImproving: false }">
    <h2 class="text-xl font-bold mb-2">{{ __('Develop Your Concept') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Describe your video idea and let AI help you refine it') }}</p>

    {{-- Raw Input --}}
    <div class="form-control mb-6">
        <label class="label">
            <span class="label-text font-semibold">{{ __('Your Video Idea') }}</span>
        </label>
        <textarea wire:model.blur="concept.rawInput"
                  class="textarea textarea-bordered h-32"
                  placeholder="{{ __('Describe your video idea in a few sentences. What story do you want to tell? What message do you want to convey?') }}"></textarea>
    </div>

    {{-- Improve with AI Button --}}
    <button class="btn btn-secondary mb-6"
            wire:click="$dispatch('improve-concept')"
            x-bind:disabled="isImproving"
            @click="isImproving = true"
            wire:loading.attr="disabled"
            wire:target="$dispatch('improve-concept')">
        <span wire:loading.remove wire:target="$dispatch('improve-concept')">
            <i class="fa-light fa-sparkles mr-2"></i>
            {{ __('Improve with AI') }}
        </span>
        <span wire:loading wire:target="$dispatch('improve-concept')">
            <span class="loading loading-spinner loading-sm mr-2"></span>
            {{ __('Improving...') }}
        </span>
    </button>

    {{-- Refined Concept --}}
    @if(!empty($concept['refinedConcept']))
        <div class="card bg-base-100 mb-6">
            <div class="card-body">
                <h3 class="card-title text-lg">
                    <i class="fa-light fa-sparkles text-secondary mr-2"></i>
                    {{ __('AI-Enhanced Concept') }}
                </h3>
                <p class="whitespace-pre-wrap">{{ $concept['refinedConcept'] }}</p>

                @if(!empty($concept['logline']))
                    <div class="divider"></div>
                    <div>
                        <span class="font-semibold">{{ __('Logline:') }}</span>
                        <p class="italic text-base-content/80">{{ $concept['logline'] }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 mt-4">
                    @if(!empty($concept['suggestedMood']))
                        <div class="badge badge-outline">{{ __('Mood:') }} {{ $concept['suggestedMood'] }}</div>
                    @endif
                    @if(!empty($concept['suggestedTone']))
                        <div class="badge badge-outline">{{ __('Tone:') }} {{ $concept['suggestedTone'] }}</div>
                    @endif
                </div>

                @if(!empty($concept['keyElements']))
                    <div class="mt-4">
                        <span class="font-semibold text-sm">{{ __('Key Elements:') }}</span>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($concept['keyElements'] as $element)
                                <span class="badge badge-primary badge-outline">{{ $element }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Additional Settings --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Style Reference (Optional)') }}</span>
            </label>
            <input type="text" wire:model.blur="concept.styleReference"
                   class="input input-bordered"
                   placeholder="{{ __('e.g., "cinematic like Christopher Nolan"') }}">
        </div>

        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Target Audience') }}</span>
            </label>
            <input type="text" wire:model.blur="concept.targetAudience"
                   class="input input-bordered"
                   placeholder="{{ __('e.g., "young professionals aged 25-35"') }}">
        </div>
    </div>
</div>
