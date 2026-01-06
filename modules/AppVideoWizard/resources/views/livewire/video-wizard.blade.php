<div class="video-wizard min-h-screen" x-data="{ showPreview: false }">
    {{-- Wizard Header --}}
    <div class="wizard-header text-center py-6 mb-4">
        <h1 class="text-3xl font-extrabold bg-gradient-to-r from-purple-500 via-cyan-500 to-emerald-500 bg-clip-text text-transparent mb-2">
            {{ __('Video Creation Wizard') }}
        </h1>
        <p class="text-base-content/60">{{ __('Create professional AI-generated videos from scratch') }}</p>
    </div>

    {{-- Stepper --}}
    <div class="wizard-stepper flex justify-center items-center gap-1 px-2 mb-8 overflow-x-auto pb-2">
        @foreach($stepTitles as $step => $title)
            @php
                $isActive = $currentStep === $step;
                $isCompleted = $currentStep > $step;
                $isReachable = $step <= $maxReachedStep + 1;
            @endphp

            <div @if($isReachable) wire:click="goToStep({{ $step }})" @endif
                 class="wizard-step flex items-center gap-2 px-4 py-3 rounded-2xl border transition-all whitespace-nowrap flex-shrink-0
                        {{ $isActive ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/20 border-purple-500/50 shadow-lg shadow-purple-500/20' : '' }}
                        {{ $isCompleted ? 'bg-emerald-500/10 border-emerald-500/30' : '' }}
                        {{ !$isActive && !$isCompleted ? 'bg-base-content/5 border-base-content/10 hover:bg-base-content/10' : '' }}"
                 style="cursor: {{ $isReachable ? 'pointer' : 'not-allowed' }}; {{ !$isReachable ? 'opacity: 0.4;' : '' }}">
                <div class="step-number w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                            {{ $isActive ? 'bg-gradient-to-r from-purple-500 to-cyan-500 text-white' : '' }}
                            {{ $isCompleted ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}
                            {{ !$isActive && !$isCompleted ? 'bg-base-content/10' : '' }}">
                    @if($isCompleted)
                        <i class="fa-solid fa-check text-xs"></i>
                    @else
                        {{ $step }}
                    @endif
                </div>
                <span class="step-label text-sm font-medium {{ $isActive ? 'text-white' : 'text-base-content/70' }} hidden md:inline">
                    {{ $title }}
                </span>
            </div>

            @if($step < 7)
                <div class="step-connector w-5 h-0.5 {{ $isCompleted ? 'bg-emerald-500/50' : 'bg-base-content/10' }} flex-shrink-0 hidden sm:block"></div>
            @endif
        @endforeach
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="alert alert-error mb-4 mx-4">
            <i class="fa-light fa-exclamation-circle"></i>
            <span>{{ $error }}</span>
            <button class="btn btn-ghost btn-sm" wire:click="$set('error', null)">
                <i class="fa-light fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Step Content --}}
    <div class="wizard-content px-4 max-w-4xl mx-auto">
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
    <div class="wizard-navigation flex justify-between items-center mt-8 px-4 pb-8 max-w-4xl mx-auto">
        <button wire:click="previousStep"
                class="btn btn-ghost gap-2"
                {{ $currentStep <= 1 ? 'disabled' : '' }}>
            <i class="fa-light fa-arrow-left"></i>
            {{ __('Previous') }}
        </button>

        <div class="flex items-center gap-2">
            @if($isSaving)
                <span class="loading loading-spinner loading-sm text-primary"></span>
                <span class="text-sm text-base-content/60">{{ __('Saving...') }}</span>
            @endif
        </div>

        @if($currentStep < 7)
            <button wire:click="nextStep" class="btn btn-primary gap-2">
                {{ __('Continue') }}
                <i class="fa-light fa-arrow-right"></i>
            </button>
        @else
            <button wire:click="saveProject" class="btn btn-success gap-2">
                <i class="fa-light fa-download"></i>
                {{ __('Export Video') }}
            </button>
        @endif
    </div>
</div>
