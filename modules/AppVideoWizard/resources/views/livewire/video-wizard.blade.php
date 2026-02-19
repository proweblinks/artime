{{-- Load Video Preview Engine and Controller Scripts using @assets directive --}}
{{-- @assets ensures scripts load BEFORE component HTML is processed, so previewController is available for Alpine x-data --}}
@assets
<script src="{{ asset('modules/appvideowizard/js/video-preview-engine.js') }}"></script>
<script src="{{ asset('modules/appvideowizard/js/preview-controller.js') }}"></script>
@endassets

<div class="video-wizard min-h-screen"
     x-data="{ showPreview: false }">

    {{-- Design System (tokens, components, animations) --}}
    @include('appvideowizard::livewire.partials._vw-design-system')

    {{-- Stepper & Navigation CSS --}}
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
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid var(--vw-border) !important;
            border-radius: 2rem !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .vw-step:hover { background: rgba(255, 255, 255, 0.07) !important; }
        .vw-step.active {
            background: var(--vw-primary-soft) !important;
            border-color: var(--vw-primary) !important;
            box-shadow: var(--vw-shadow-glow) !important;
        }
        .vw-step.completed {
            background: var(--vw-success-soft) !important;
            border-color: var(--vw-border-success) !important;
        }
        .vw-step.disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
        }

        .vw-step-number {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.06) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            flex-shrink: 0 !important;
            color: var(--vw-text-secondary) !important;
        }
        .vw-step.active .vw-step-number {
            background: var(--vw-primary) !important;
            color: white !important;
        }
        .vw-step.completed .vw-step-number {
            background: var(--vw-success) !important;
            color: white !important;
        }

        .vw-step-label {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: var(--vw-text-muted) !important;
        }
        .vw-step.active .vw-step-label { color: var(--vw-text) !important; }
        .vw-step.completed .vw-step-label { color: #6ee7b7 !important; }

        .vw-connector {
            width: 20px !important;
            height: 2px !important;
            background: var(--vw-border) !important;
            flex-shrink: 0 !important;
        }
        .vw-connector.completed { background: rgba(34, 197, 94, 0.4) !important; }

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
            padding: 1rem 1.5rem 2rem !important;
            max-width: 56rem !important;
            margin-left: auto !important;
            margin-right: auto !important;
            border-top: 1px solid var(--vw-border) !important;
        }

        .vw-nav-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.65rem 1.25rem !important;
            border-radius: var(--vw-radius) !important;
            font-weight: 600 !important;
            font-size: var(--vw-text-base) !important;
            cursor: pointer !important;
            transition: all 150ms ease !important;
            border: none !important;
            font-family: var(--vw-font) !important;
        }

        .vw-nav-btn:disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
        }

        .vw-nav-btn--ghost {
            background: rgba(255, 255, 255, 0.04) !important;
            color: var(--vw-text-secondary) !important;
            border: 1px solid var(--vw-border) !important;
        }
        .vw-nav-btn--ghost:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.08) !important;
            color: var(--vw-text) !important;
        }

        .vw-nav-btn--primary {
            background: var(--vw-primary) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(var(--vw-primary-rgb), 0.3) !important;
        }
        .vw-nav-btn--primary:hover:not(:disabled) {
            background: var(--vw-primary-hover) !important;
            box-shadow: 0 6px 20px rgba(var(--vw-primary-rgb), 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .vw-nav-btn--success {
            background: var(--vw-success) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3) !important;
        }
        .vw-nav-btn--success:hover:not(:disabled) {
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .vw-saving-indicator {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-size: var(--vw-text-base) !important;
            color: var(--vw-text-muted) !important;
        }

        .vw-nav-spinner {
            width: 16px !important;
            height: 16px !important;
            border: 2px solid rgba(var(--vw-primary-rgb), 0.2) !important;
            border-top-color: var(--vw-primary) !important;
            border-radius: 50% !important;
            animation: vw-spin 0.8s linear infinite !important;
        }

        .vw-btn-save {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            padding: 0.5rem 1rem !important;
            background: var(--vw-success) !important;
            border: none !important;
            border-radius: var(--vw-radius) !important;
            color: white !important;
            font-weight: 600 !important;
            font-size: var(--vw-text-sm) !important;
            cursor: pointer !important;
            transition: all 150ms ease !important;
        }
        .vw-btn-save:hover {
            filter: brightness(1.1) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4) !important;
        }

        /* Transition Overlay */
        .vw-transition-overlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 20, 55, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: vw-fadeIn 0.3s ease;
        }

        @keyframes vw-fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .vw-transition-content {
            background: var(--vw-bg-surface);
            border: 1px solid var(--vw-border);
            border-radius: var(--vw-radius-xl);
            padding: 2.5rem 3rem;
            box-shadow: var(--vw-shadow-lg);
            text-align: center;
            max-width: 400px;
            animation: vw-slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes vw-slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .vw-transition-spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .vw-transition-spinner::before,
        .vw-transition-spinner::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 4px solid transparent;
        }

        .vw-transition-spinner::before {
            border-top-color: var(--vw-primary);
            border-right-color: #22d3ee;
            animation: vw-spin 1s linear infinite;
        }

        .vw-transition-spinner::after {
            border-bottom-color: var(--vw-success);
            border-left-color: var(--vw-warning);
            animation: vw-spin 1.5s linear infinite reverse;
            inset: 8px;
        }

        .vw-transition-message {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--vw-text);
            margin-bottom: 0.75rem;
        }

        .vw-transition-submessage {
            font-size: var(--vw-text-base);
            color: var(--vw-text-muted);
        }

        /* Button loading state */
        .vw-btn-loading {
            position: relative;
            pointer-events: none;
        }

        .vw-btn-loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: vw-spin 0.8s linear infinite;
            right: 1rem;
        }
    </style>

    {{-- JavaScript for URL updates and async step transitions --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('update-browser-url', ({ projectId }) => {
                if (projectId) {
                    const url = new URL(window.location);
                    url.searchParams.set('project', projectId);
                    window.history.replaceState({}, '', url);
                }
            });

            // Handle deferred scene memory population after step transition
            // This triggers after the view renders, preventing UI blocking
            Livewire.on('step-changed', ({ step, needsPopulation }) => {
                if (needsPopulation) {
                    // Small delay to ensure view has rendered
                    setTimeout(() => {
                        @this.call('handleDeferredSceneMemoryPopulation');
                    }, 100);
                }
            });
        });
    </script>

    {{-- Modern Transition Overlay --}}
    @if($isTransitioning)
        <div class="vw-transition-overlay" wire:key="transition-overlay">
            <div class="vw-transition-content">
                <div class="vw-transition-spinner"></div>
                <div class="vw-transition-message">
                    {{ $transitionMessage ?? __('Preparing next step...') }}
                </div>
                <div class="vw-transition-submessage">
                    {{ __('This may take a few seconds') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Wizard Header --}}
    <div style="position: relative; text-align: center; padding: 2rem 1rem 1rem;">
        {{-- My Projects Button (Top Right) --}}
        <button wire:click="openProjectManager"
                class="vw-btn vw-btn--primary vw-btn--sm"
                style="position: absolute; top: 1.5rem; right: 1rem;">
            <i class="fas fa-folder-open" style="font-size: 0.85rem;"></i>
            {{ __('My Projects') }}
        </button>

        <h1 class="vw-page-title">
            {{ __('Video Creation Wizard') }}
        </h1>
        <p class="vw-page-subtitle">{{ __('Create professional AI-generated videos from scratch') }}</p>
    </div>

    {{-- Stepper --}}
    @php $maxSteps = $this->getMaxSteps(); $displayNum = 0; @endphp
    <div class="vw-stepper">
        @foreach($stepTitles as $step => $title)
            @if($step <= $maxSteps && !empty($title))
            @php
                $displayNum++;
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
                        {{ $displayNum }}
                    @endif
                </div>
                <span class="vw-step-label">{{ $title }}</span>
            </div>

            @if(!$loop->last)
                @php
                    $hasNextVisible = false;
                    foreach(array_slice($stepTitles, $step, null, true) as $ns => $nt) {
                        if ($ns > $step && !empty($nt)) { $hasNextVisible = true; break; }
                    }
                @endphp
                @if($hasNextVisible)
                    <div class="vw-connector {{ $isCompleted ? 'completed' : '' }}"></div>
                @endif
            @endif
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
                class="vw-nav-btn vw-nav-btn--ghost"
                {{ $currentStep <= 1 ? 'disabled' : '' }}>
            <i class="fas fa-arrow-left" style="font-size: 0.8rem;"></i> {{ __('Previous') }}
        </button>

        <div class="vw-saving-indicator">
            @if($isSaving)
                <span class="vw-nav-spinner"></span>
                <span>{{ __('Saving...') }}</span>
            @else
                <button type="button" wire:click="saveProject" wire:loading.attr="disabled" class="vw-btn-save" title="{{ __('Save Project') }}">
                    <span wire:loading.remove wire:target="saveProject"><i class="fas fa-floppy-disk"></i> {{ __('Save') }}</span>
                    <span wire:loading wire:target="saveProject"><i class="fas fa-spinner fa-spin"></i> {{ __('Saving...') }}</span>
                </button>
                @if($projectId)
                    <span class="vw-project-id">ID: {{ $projectId }}</span>
                @endif
            @endif
        </div>

        @if($currentStep < $maxSteps)
            <button type="button"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                    wire:loading.class="vw-btn-loading"
                    class="vw-nav-btn vw-nav-btn--primary {{ $isTransitioning ? 'vw-btn-loading' : '' }}"
                    {{ $isTransitioning ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="nextStep,handleDeferredSceneMemoryPopulation">
                    {{ __('Continue') }} <i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i>
                </span>
                <span wire:loading wire:target="nextStep,handleDeferredSceneMemoryPopulation">
                    {{ __('Loading...') }}
                </span>
            </button>
        @else
            <button type="button" wire:click="saveProject" class="vw-nav-btn vw-nav-btn--success">
                <i class="fas fa-download" style="font-size: 0.8rem;"></i> {{ __('Export Video') }}
            </button>
        @endif
    </div>

    {{-- Project Manager Modal --}}
    @include('appvideowizard::livewire.modals.project-manager')

    {{-- Multi-Shot Decomposition Modal --}}
    @include('appvideowizard::livewire.modals.multi-shot')

    {{-- Shot Preview Modal --}}
    @include('appvideowizard::livewire.modals.shot-preview')

    {{-- Frame Capture Modal --}}
    @include('appvideowizard::livewire.modals.frame-capture')

    {{-- Story Bible Modal (Phase 1: Bible-First Architecture) --}}
    @include('appvideowizard::livewire.modals.story-bible')

    {{-- Writer's Room Modal (Phase 2: Professional Writing Interface) --}}
    @include('appvideowizard::livewire.modals.writers-room')

    {{-- Scene Text Inspector Modal --}}
    @include('appvideowizard::livewire.modals.scene-text-inspector')

    {{-- Debug Console Logger --}}
    @if(config('app.debug', false) || session('login_as') === 'admin')
    <script>
    (function() {
        // Video Wizard Debug Logger
        const VWDebug = {
            enabled: true,
            prefix: '🎬 [VideoWizard]',

            log(...args) {
                if (this.enabled) console.log(this.prefix, ...args);
            },

            error(...args) {
                console.error(this.prefix, '❌', ...args);
            },

            warn(...args) {
                console.warn(this.prefix, '⚠️', ...args);
            },

            success(...args) {
                if (this.enabled) console.log(this.prefix, '✅', ...args);
            },

            api(action, data) {
                if (this.enabled) {
                    console.groupCollapsed(this.prefix + ' API: ' + action);
                    console.log('Data:', data);
                    console.log('Time:', new Date().toISOString());
                    console.groupEnd();
                }
            }
        };

        // Make globally accessible
        window.VWDebug = VWDebug;

        // Livewire event listeners
        document.addEventListener('livewire:init', () => {
            VWDebug.log('Livewire initialized');

            // Track all Livewire dispatched events
            Livewire.hook('message.sent', ({ component, commit, respond }) => {
                const calls = commit.calls || [];
                calls.forEach(call => {
                    VWDebug.api('Method Call: ' + call.method, call.params);
                });
            });

            Livewire.hook('message.received', ({ component, response }) => {
                if (response.effects?.html) {
                    VWDebug.log('Component updated');
                }
                if (response.effects?.dispatches) {
                    response.effects.dispatches.forEach(d => {
                        VWDebug.success('Event dispatched:', d.name, d.params);
                    });
                }
            });

            Livewire.hook('message.failed', ({ component, message }) => {
                VWDebug.error('Request failed:', message);
            });

            // Listen for specific Video Wizard events
            const vwEvents = [
                'concept-enhanced', 'ideas-generated', 'script-generated',
                'scene-regenerated', 'visual-prompt-generated', 'voiceover-text-generated',
                'image-generated', 'storyboard-generated', 'scene-added', 'scene-deleted'
            ];

            vwEvents.forEach(event => {
                Livewire.on(event, (data) => {
                    VWDebug.success('Event: ' + event, data || '');
                });
            });

            // Listen for server-side debug events (connected with Admin Generation Logs)
            Livewire.on('vw-debug', (eventData) => {
                const data = eventData[0] || eventData;
                const action = data.action || 'unknown';
                const message = data.message || '';
                const level = data.level || 'log';
                const details = data.data || {};

                console.groupCollapsed(VWDebug.prefix + ' Server: ' + action);
                console.log('Message:', message);
                if (Object.keys(details).length > 0) {
                    console.log('Details:', details);
                }
                console.log('Time:', new Date().toISOString());
                console.log('💡 View full logs at: Admin > Video Creator > Logs');
                console.groupEnd();

                // Also log to main console based on level
                switch (level) {
                    case 'error':
                        VWDebug.error(action + ':', message);
                        break;
                    case 'warn':
                        VWDebug.warn(action + ':', message);
                        break;
                    default:
                        VWDebug.log(action + ':', message);
                }
            });
        });

        // Track errors
        @if($error ?? null)
        VWDebug.error('Server Error:', @json($error));
        @endif

        // Log initial state
        VWDebug.log('Component mounted', {
            projectId: {{ $projectId ?? 'null' }},
            currentStep: {{ $currentStep ?? 1 }},
            platform: '{{ $platform ?? "unknown" }}',
            productionType: '{{ $productionType ?? "unknown" }}'
        });

        // Help text
        console.log('%c🎬 Video Wizard Debug Mode Active', 'background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;');
        console.log('%cUse window.VWDebug to control logging. Set VWDebug.enabled = false to disable.', 'color: #666;');
    })();
    </script>
    @endif
</div>
