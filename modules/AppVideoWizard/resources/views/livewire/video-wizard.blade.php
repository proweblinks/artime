<div class="video-wizard" x-data="{ showPreview: false }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <input type="text" wire:model.blur="projectName"
                   class="input input-ghost text-2xl font-bold p-0 h-auto focus:bg-base-200"
                   placeholder="{{ __('Untitled Video') }}">
            <p class="text-sm text-base-content/60 mt-1">
                {{ __('Step :current of :total', ['current' => $currentStep, 'total' => 7]) }}
                @if($isSaving)
                    <span class="loading loading-spinner loading-xs ml-2"></span>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.video-wizard.projects') }}" class="btn btn-ghost btn-sm">
                <i class="fa-light fa-folder mr-2"></i>
                {{ __('My Projects') }}
            </a>
            <button wire:click="saveProject" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                <i class="fa-light fa-save mr-2"></i>
                {{ __('Save') }}
            </button>
        </div>
    </div>

    {{-- Progress Steps --}}
    <div class="mb-8">
        <ul class="steps steps-horizontal w-full">
            @foreach($stepTitles as $step => $title)
                <li class="step {{ $currentStep >= $step ? 'step-primary' : '' }} {{ $isStepCompleted($step) ? 'step-success' : '' }}"
                    wire:click="goToStep({{ $step }})"
                    style="cursor: {{ $step <= $maxReachedStep + 1 ? 'pointer' : 'not-allowed' }}">
                    <span class="hidden md:inline text-xs">{{ $title }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="alert alert-error mb-4">
            <i class="fa-light fa-exclamation-circle"></i>
            <span>{{ $error }}</span>
            <button class="btn btn-ghost btn-sm" wire:click="$set('error', null)">
                <i class="fa-light fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Step Content --}}
    <div class="card bg-base-200">
        <div class="card-body">
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
    </div>

    {{-- Navigation --}}
    <div class="flex justify-between mt-6">
        <button wire:click="previousStep" class="btn btn-ghost" {{ $currentStep <= 1 ? 'disabled' : '' }}>
            <i class="fa-light fa-arrow-left mr-2"></i>
            {{ __('Previous') }}
        </button>

        @if($currentStep < 7)
            <button wire:click="nextStep" class="btn btn-primary">
                {{ __('Next') }}
                <i class="fa-light fa-arrow-right ml-2"></i>
            </button>
        @endif
    </div>
</div>
