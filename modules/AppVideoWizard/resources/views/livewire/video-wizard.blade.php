<div class="video-wizard min-h-screen bg-base-100" x-data="{ showPreview: false }">
    {{-- Wizard Header --}}
    <div class="text-center py-8 mb-6">
        <h1 class="text-3xl md:text-4xl font-extrabold mb-2" style="background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 50%, #10b981 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            {{ __('Video Creation Wizard') }}
        </h1>
        <p class="text-base-content/60">{{ __('Create professional AI-generated videos from scratch') }}</p>
    </div>

    {{-- Stepper --}}
    <div class="flex justify-center items-center gap-1 px-4 mb-10 overflow-x-auto pb-4">
        @foreach($stepTitles as $step => $title)
            @php
                $isActive = $currentStep === $step;
                $isCompleted = $currentStep > $step;
                $isReachable = $step <= $maxReachedStep + 1;
            @endphp

            <div @if($isReachable) wire:click="goToStep({{ $step }})" @endif
                 class="flex items-center gap-2 px-3 md:px-4 py-2 md:py-3 rounded-full transition-all whitespace-nowrap flex-shrink-0
                        @if($isActive) bg-primary text-primary-content shadow-lg @elseif($isCompleted) bg-success/20 text-success @else bg-base-200 text-base-content/60 hover:bg-base-300 @endif"
                 style="cursor: {{ $isReachable ? 'pointer' : 'not-allowed' }}; {{ !$isReachable ? 'opacity: 0.4;' : '' }}">
                <div class="w-6 h-6 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold
                            @if($isActive) bg-primary-content text-primary @elseif($isCompleted) bg-success text-success-content @else bg-base-300 @endif">
                    @if($isCompleted)
                        ✓
                    @else
                        {{ $step }}
                    @endif
                </div>
                <span class="text-xs md:text-sm font-medium hidden sm:inline">{{ $title }}</span>
            </div>

            @if($step < 7)
                <div class="w-4 md:w-8 h-0.5 flex-shrink-0 hidden sm:block @if($isCompleted) bg-success @else bg-base-300 @endif"></div>
            @endif
        @endforeach
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="alert alert-error mb-6 mx-4 max-w-4xl lg:mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ $error }}</span>
            <button class="btn btn-ghost btn-sm" wire:click="$set('error', null)">✕</button>
        </div>
    @endif

    {{-- Step Content --}}
    <div class="px-4 max-w-4xl mx-auto">
        @switch($currentStep)
            @case(1)
                @include('appvideowizard::livewire.steps.platform')
                @break

            @case(2)
                @include('appvideowizard::livewire.steps.concept')
                @break

            @case(3)
                @include('appvideowizard::livewire.steps.script')
                @break

            @case(4)
                @include('appvideowizard::livewire.steps.storyboard')
                @break

            @case(5)
                @include('appvideowizard::livewire.steps.animation')
                @break

            @case(6)
                @include('appvideowizard::livewire.steps.assembly')
                @break

            @case(7)
                @include('appvideowizard::livewire.steps.export')
                @break
        @endswitch
    </div>

    {{-- Navigation --}}
    <div class="flex justify-between items-center mt-10 px-4 pb-10 max-w-4xl mx-auto">
        <button wire:click="previousStep"
                class="btn btn-ghost gap-2"
                {{ $currentStep <= 1 ? 'disabled' : '' }}>
            ← {{ __('Previous') }}
        </button>

        <div class="flex items-center gap-2">
            @if($isSaving)
                <span class="loading loading-spinner loading-sm text-primary"></span>
                <span class="text-sm text-base-content/60">{{ __('Saving...') }}</span>
            @endif
        </div>

        @if($currentStep < 7)
            <button wire:click="nextStep" class="btn btn-primary gap-2">
                {{ __('Continue') }} →
            </button>
        @else
            <button wire:click="saveProject" class="btn btn-success gap-2">
                {{ __('Export Video') }}
            </button>
        @endif
    </div>
</div>
