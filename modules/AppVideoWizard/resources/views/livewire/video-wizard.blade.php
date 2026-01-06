<div class="video-wizard min-h-screen" x-data="{ showPreview: false }">
    {{-- Embedded CSS for Stepper (ensures styles aren't overridden) --}}
    <style>
        .vw-stepper {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 0.25rem !important;
            padding: 1rem 0.5rem !important;
            margin-bottom: 2rem !important;
            overflow-x: auto !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .vw-stepper::-webkit-scrollbar { display: none !important; }

        .vw-step {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            background: rgba(0, 0, 0, 0.05) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 2rem !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .vw-step:hover { background: rgba(0, 0, 0, 0.08) !important; }
        .vw-step.active {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(6, 182, 212, 0.15) 100%) !important;
            border-color: rgba(139, 92, 246, 0.5) !important;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.15) !important;
        }
        .vw-step.completed {
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        .vw-step.disabled {
            opacity: 0.4 !important;
            cursor: not-allowed !important;
        }

        .vw-step-number {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            border-radius: 50% !important;
            background: rgba(0, 0, 0, 0.1) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            flex-shrink: 0 !important;
            color: inherit !important;
        }
        .vw-step.active .vw-step-number {
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
            color: white !important;
        }
        .vw-step.completed .vw-step-number {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .vw-step-label {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: rgba(0, 0, 0, 0.6) !important;
        }
        .vw-step.active .vw-step-label { color: #8b5cf6 !important; }
        .vw-step.completed .vw-step-label { color: #10b981 !important; }

        .vw-connector {
            width: 20px !important;
            height: 2px !important;
            background: rgba(0, 0, 0, 0.1) !important;
            flex-shrink: 0 !important;
        }
        .vw-connector.completed { background: rgba(16, 185, 129, 0.5) !important; }

        @media (max-width: 768px) {
            .vw-stepper { justify-content: flex-start !important; padding: 0.75rem !important; }
            .vw-step { padding: 0.4rem 0.75rem !important; }
            .vw-step-label { display: none !important; }
            .vw-step-number { width: 24px !important; height: 24px !important; min-width: 24px !important; }
        }

        /* Navigation */
        .vw-navigation {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-top: 2.5rem !important;
            padding: 0 1rem 2.5rem !important;
            max-width: 56rem !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .vw-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            border: none !important;
        }

        .vw-btn:disabled {
            opacity: 0.4 !important;
            cursor: not-allowed !important;
        }

        .vw-btn-ghost {
            background: transparent !important;
            color: rgba(0, 0, 0, 0.6) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        .vw-btn-ghost:hover:not(:disabled) {
            background: rgba(0, 0, 0, 0.05) !important;
        }

        .vw-btn-primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3) !important;
        }

        .vw-btn-primary:hover:not(:disabled) {
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .vw-btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important;
        }

        .vw-btn-success:hover:not(:disabled) {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .vw-saving-indicator {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-size: 0.875rem !important;
            color: rgba(0, 0, 0, 0.5) !important;
        }

        .vw-spinner {
            width: 16px !important;
            height: 16px !important;
            border: 2px solid rgba(139, 92, 246, 0.2) !important;
            border-top-color: #8b5cf6 !important;
            border-radius: 50% !important;
            animation: vw-spin 0.8s linear infinite !important;
        }

        @keyframes vw-spin {
            to { transform: rotate(360deg); }
        }

        .vw-btn-save {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            padding: 0.5rem 1rem !important;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            border-radius: 0.5rem !important;
            color: white !important;
            font-weight: 500 !important;
            font-size: 0.85rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .vw-btn-save:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4) !important;
        }
    </style>

    {{-- JavaScript for URL updates --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('update-browser-url', ({ projectId }) => {
                if (projectId) {
                    const url = new URL(window.location);
                    url.searchParams.set('project', projectId);
                    window.history.replaceState({}, '', url);
                }
            });
        });
    </script>

    {{-- Wizard Header --}}
    <div style="text-align: center; padding: 2rem 1rem 1rem;">
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 50%, #10b981 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            {{ __('Video Creation Wizard') }}
        </h1>
        <p style="color: rgba(0,0,0,0.5); font-size: 0.95rem;">{{ __('Create professional AI-generated videos from scratch') }}</p>
    </div>

    {{-- Stepper --}}
    <div class="vw-stepper">
        @foreach($stepTitles as $step => $title)
            @php
                $isActive = $currentStep === $step;
                $isCompleted = $currentStep > $step;
                $isReachable = $step <= $maxReachedStep + 1;
            @endphp

            <div @if($isReachable) wire:click="goToStep({{ $step }})" @endif
                 class="vw-step {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }} {{ !$isReachable ? 'disabled' : '' }}"
                 style="cursor: {{ $isReachable ? 'pointer' : 'not-allowed' }};">
                <div class="vw-step-number">
                    @if($isCompleted)
                        ✓
                    @else
                        {{ $step }}
                    @endif
                </div>
                <span class="vw-step-label">{{ $title }}</span>
            </div>

            @if($step < 7)
                <div class="vw-connector {{ $isCompleted ? 'completed' : '' }}"></div>
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
    <div class="vw-navigation">
        <button type="button" wire:click="previousStep"
                class="vw-btn vw-btn-ghost"
                {{ $currentStep <= 1 ? 'disabled' : '' }}>
            ← {{ __('Previous') }}
        </button>

        <div class="vw-saving-indicator">
            @if($isSaving)
                <span class="vw-spinner"></span>
                <span>{{ __('Saving...') }}</span>
            @else
                <button type="button" wire:click="saveProject" wire:loading.attr="disabled" class="vw-btn-save" title="{{ __('Save Project') }}">
                    <span wire:loading.remove wire:target="saveProject">💾 {{ __('Save') }}</span>
                    <span wire:loading wire:target="saveProject">⏳ {{ __('Saving...') }}</span>
                </button>
                @if($projectId)
                    <span style="margin-left: 0.5rem; font-size: 0.75rem; color: rgba(0,0,0,0.4);">ID: {{ $projectId }}</span>
                @endif
            @endif
        </div>

        @if($currentStep < 7)
            <button type="button" wire:click="nextStep" class="vw-btn vw-btn-primary">
                {{ __('Continue') }} →
            </button>
        @else
            <button type="button" wire:click="saveProject" class="vw-btn vw-btn-success">
                {{ __('Export Video') }}
            </button>
        @endif
    </div>
</div>
