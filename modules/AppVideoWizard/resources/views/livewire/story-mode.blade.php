<div class="story-mode-page" x-data="{ showDetail: @entangle('detailProjectId') }">

    {{-- Main Content --}}
    <div class="story-mode-main py-4 px-3 px-lg-5" style="max-width: 960px; margin: 0 auto;">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: #fff; font-size: 1.75rem;">
                {{ __('Create a short video in your own style') }}
            </h2>
            <p class="text-muted mb-0">{{ __('Enter a prompt, pick a style and voice, and get a professionally edited video.') }}</p>
        </div>

        {{-- Active Project Progress --}}
        @if($this->activeProject && $this->activeProject->isGenerating())
            <div wire:poll.5s class="card border-0 mb-4" style="background: #1a1a1a; border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-1 text-white">
                                <i class="fa-light fa-spinner-third fa-spin me-2" style="color: #f97316;"></i>
                                {{ __('Generating Video') }}
                            </h6>
                            <small class="text-muted">{{ $this->activeProject->current_stage ?? 'Processing...' }}</small>
                        </div>
                        <span class="badge" style="background: #f97316; font-size: 0.85rem;">
                            {{ $this->activeProject->progress_percent }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px; background: #333; border-radius: 3px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $this->activeProject->progress_percent }}%; background: #f97316; border-radius: 3px; transition: width 0.5s ease;"
                             aria-valuenow="{{ $this->activeProject->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">{{ $this->activeProject->title }}</small>
                        <small class="text-muted">{{ $this->activeProject->progress_percent }}% complete</small>
                    </div>
                </div>
            </div>
        @endif

        {{-- Style Picker --}}
        @include('appvideowizard::livewire.story-mode.partials._style-picker')

        {{-- Input Area --}}
        <div class="border-0 mb-4 p-4" style="background: #1a1a1a !important; border-radius: 12px;" x-data="{ promptText: @entangle('prompt') }">
                <textarea
                    x-model="promptText"
                    wire:model.live.debounce.500ms="prompt"
                    class="form-control border-0 text-white"
                    rows="4"
                    placeholder="{{ __('Describe the video you want to make...') }}"
                    style="resize: none; font-size: 1rem; line-height: 1.6; box-shadow: none; background: transparent !important;"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top: 1px solid #333;">
                    <div class="d-flex align-items-center gap-3">
                        {{-- Aspect Ratio --}}
                        <select wire:model="aspectRatio" class="form-select form-select-sm border-0"
                                style="width: auto; background: #2a2a2a !important; color: #ccc; border-radius: 8px; font-size: 0.8rem;">
                            <option value="9:16">9:16</option>
                            <option value="16:9">16:9</option>
                            <option value="1:1">1:1</option>
                        </select>

                        {{-- Voice Button --}}
                        <button wire:click="openVoiceModal" type="button"
                                class="btn btn-sm border-0 d-flex align-items-center gap-1"
                                style="background: #2a2a2a !important; color: #ccc; border-radius: 8px; font-size: 0.8rem;">
                            <i class="fa-light fa-microphone"></i>
                            <span>{{ $selectedVoice === 'auto' ? __('Voice') : $selectedVoice }}</span>
                        </button>
                    </div>

                    {{-- Submit Button --}}
                    <button wire:click="submitPrompt" type="button"
                            class="btn d-flex align-items-center justify-content-center"
                            :style="promptText.length > 9 ? 'width:42px;height:42px;border-radius:50%;background:#f97316;border:none;color:#fff' : 'width:42px;height:42px;border-radius:50%;background:#333;border:none;color:#fff'"
                            :disabled="promptText.length < 10 || {{ $isGeneratingScript ? 'true' : 'false' }}">
                        @if($isGeneratingScript)
                            <i class="fa-light fa-spinner-third fa-spin"></i>
                        @else
                            <i class="fa-light fa-arrow-up"></i>
                        @endif
                    </button>
                </div>
        </div>

        {{-- Error Messages --}}
        @if(session('error'))
            <div class="alert alert-danger border-0 mb-4" style="background: #3d1515; color: #f87171; border-radius: 12px;">
                <i class="fa-light fa-triangle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- User's Recent Projects --}}
        @if($this->userProjects->isNotEmpty())
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-white mb-0">{{ __('My Projects') }}</h5>
                </div>
                <div class="row g-3">
                    @foreach($this->userProjects->take(8) as $project)
                        @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Gallery --}}
        @if(isset($galleryProjects) && $galleryProjects->isNotEmpty())
            <div class="mb-4">
                <h5 class="fw-bold text-white mb-3">{{ __('Top story mode') }}</h5>
                <div class="row g-3">
                    @foreach($galleryProjects as $project)
                        @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Modals --}}
    @include('appvideowizard::livewire.story-mode.partials._transcript-modal')
    @include('appvideowizard::livewire.story-mode.partials._voice-modal')
    @include('appvideowizard::livewire.story-mode.partials._style-modal')

    {{-- Project Detail Overlay --}}
    @if($this->detailProject)
        @include('appvideowizard::livewire.story-mode.partials._project-detail', ['project' => $this->detailProject])
    @endif

    {{-- Page-level styles --}}
    <style>
        .story-mode-page {
            min-height: 100vh;
            background: #0a0a0a !important;
        }
        .story-mode-page .form-control,
        .story-mode-page .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
            background: transparent !important;
        }
        .story-mode-page .form-select:focus {
            box-shadow: none !important;
        }
        .story-mode-page .card {
            background: #1a1a1a !important;
        }
        .style-thumb {
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .style-thumb:hover {
            border-color: #555;
            transform: translateY(-2px);
        }
        .style-thumb.selected {
            border-color: #f97316;
            box-shadow: 0 0 0 1px #f97316;
        }
        .project-card {
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #1a1a1a;
        }
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
    </style>
</div>
