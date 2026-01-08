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

        /* Modern Transition Overlay */
        .vw-transition-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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
            background: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
            border-radius: 1.5rem;
            padding: 2.5rem 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
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
            border-top-color: #8b5cf6;
            border-right-color: #06b6d4;
            animation: vw-spin 1s linear infinite;
        }

        .vw-transition-spinner::after {
            border-bottom-color: #10b981;
            border-left-color: #f59e0b;
            animation: vw-spin 1.5s linear infinite reverse;
            inset: 8px;
        }

        .vw-transition-message {
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
        }

        .vw-transition-submessage {
            font-size: 0.875rem;
            color: rgba(0, 0, 0, 0.5);
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
                style="position: absolute; top: 1.5rem; right: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; border-radius: 0.5rem; color: white; font-weight: 500; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.4)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(99, 102, 241, 0.3)';">
            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            {{ __('My Projects') }}
        </button>

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
            <button type="button"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                    wire:loading.class="vw-btn-loading"
                    class="vw-btn vw-btn-primary {{ $isTransitioning ? 'vw-btn-loading' : '' }}"
                    {{ $isTransitioning ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="nextStep,handleDeferredSceneMemoryPopulation">
                    {{ __('Continue') }} →
                </span>
                <span wire:loading wire:target="nextStep,handleDeferredSceneMemoryPopulation">
                    {{ __('Loading...') }}
                </span>
            </button>
        @else
            <button type="button" wire:click="saveProject" class="vw-btn vw-btn-success">
                {{ __('Export Video') }}
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
