<div class="story-mode-page" x-data="{ showDetail: @js($detailProjectId) }"
     x-init="$watch('$wire.detailProjectId', v => showDetail = v)">

    {{-- Main Content --}}
    <div class="story-mode-main py-4 px-3 px-lg-5" style="max-width: 960px; margin: 0 auto;">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: #fff; font-size: 1.75rem;">
                {{ __('Create a short video in your own style') }}
            </h2>
            <p class="text-muted mb-0">{{ __('Enter a prompt, pick a style and voice, and get a professionally edited video.') }}</p>
        </div>

        {{-- Active Project Progress — poll only when actually generating --}}
        @if($this->activeProject && $this->activeProject->isGenerating())
            <div wire:poll.10s class="card border-0 mb-4" style="background: #1a1a1a; border-radius: 12px;">
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
        <div class="border-0 mb-4 p-4" style="background: #1a1a1a !important; border-radius: 12px;"
             x-data="{
                 promptText: @js($prompt),
                 resolution: @js($videoResolution),
                 quality: @js($videoQuality),
                 aspectRatio: @js($aspectRatio),
                 showSettings: false,
                 placeholders: [
                     '{{ __("A cat astronaut floating through a neon galaxy...") }}',
                     '{{ __("Morning routine of a robot barista in Tokyo...") }}',
                     '{{ __("Time-lapse of a flower blooming in the desert...") }}',
                     '{{ __("A detective solving a mystery in a rain-soaked city...") }}',
                     '{{ __("An underwater adventure with bioluminescent creatures...") }}'
                 ],
                 placeholderIdx: 0,
                 init() {
                     this.$watch('quality', (val) => {
                         if (val === 'fast' && this.resolution === '480p') {
                             this.resolution = '720p';
                             $wire.set('videoResolution', '720p');
                         }
                     });
                     setInterval(() => {
                         this.placeholderIdx = (this.placeholderIdx + 1) % this.placeholders.length;
                     }, 4000);
                 },
                 cycleAspect() {
                     const cycle = { '9:16': '16:9', '16:9': '1:1', '1:1': '9:16' };
                     this.aspectRatio = cycle[this.aspectRatio] || '9:16';
                     $wire.set('aspectRatio', this.aspectRatio);
                 },
                 cycleResolution() {
                     if (this.quality === 'fast') {
                         this.resolution = this.resolution === '720p' ? '1080p' : '720p';
                     } else {
                         const cycle = { '480p': '720p', '720p': '1080p', '1080p': '480p' };
                         this.resolution = cycle[this.resolution] || '480p';
                     }
                     $wire.set('videoResolution', this.resolution);
                 },
                 cycleQuality() {
                     this.quality = this.quality === 'pro' ? 'fast' : 'pro';
                     $wire.set('videoQuality', this.quality);
                 }
             }">
                <textarea
                    x-model="promptText"
                    wire:model.live.debounce.500ms="prompt"
                    class="form-control border-0 text-white"
                    rows="4"
                    :placeholder="placeholders[placeholderIdx]"
                    style="resize: none; font-size: 1rem; line-height: 1.6; box-shadow: none; background: transparent !important;"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Attached File Preview --}}
                @if($attachedFile)
                    <div class="d-flex align-items-center gap-2 mt-2 px-1">
                        <div class="d-flex align-items-center gap-2 px-3 py-1" style="background: #2a2a2a; border-radius: 20px; font-size: 0.8rem; color: #ccc;">
                            <i class="fa-light fa-file" style="color: #f97316;"></i>
                            <span style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $attachedFile->getClientOriginalName() }}
                            </span>
                            <span class="text-muted">({{ number_format($attachedFile->getSize() / 1024, 0) }}KB)</span>
                            <button wire:click="removeAttachedFile" type="button"
                                    class="btn btn-sm p-0 border-0" style="color: #888; line-height: 1;">
                                <i class="fa-light fa-xmark"></i>
                            </button>
                        </div>
                        <div wire:loading wire:target="attachedFile">
                            <i class="fa-light fa-spinner-third fa-spin" style="color: #f97316; font-size: 0.8rem;"></i>
                        </div>
                    </div>
                @endif

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top: 1px solid #333;">
                    <div class="d-flex align-items-center gap-2 position-relative">
                        {{-- Attach Button --}}
                        <input type="file" x-ref="fileInput" wire:model="attachedFile" class="d-none"
                               accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                        <button @click="$refs.fileInput.click()" type="button"
                                class="btn btn-sm border-0 d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px; background: #2a2a2a; border-radius: 8px; color: #ccc;">
                            <i class="fa-light fa-paperclip"></i>
                        </button>

                        {{-- Settings Popover --}}
                        <div class="position-relative">
                            <button @click="showSettings = !showSettings" type="button"
                                    class="btn btn-sm border-0 d-flex align-items-center justify-content-center"
                                    :style="showSettings ? 'width:32px;height:32px;background:#f97316;border-radius:8px;color:#fff' : 'width:32px;height:32px;background:#2a2a2a;border-radius:8px;color:#ccc'">
                                <i class="fa-light fa-sliders"></i>
                            </button>
                            <div x-show="showSettings" x-cloak
                                 @click.away="showSettings = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 transform -translate-y-1"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 style="position: absolute; bottom: calc(100% + 8px); left: 0; z-index: 50; background: #2a2a2a; border-radius: 10px; padding: 12px; min-width: 220px; box-shadow: 0 8px 30px rgba(0,0,0,0.5);">
                                <div style="font-size: 0.65rem; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">
                                    {{ __('Settings') }}
                                </div>
                                {{-- Aspect Ratio --}}
                                <button @click="cycleAspect()" type="button"
                                        class="btn w-100 border-0 d-flex align-items-center justify-content-between px-2 py-2"
                                        style="background: transparent; color: #ccc; font-size: 0.85rem; border-radius: 6px;"
                                        onmouseover="this.style.background='#333'" onmouseout="this.style.background='transparent'">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-expand" style="width: 16px; text-align: center; color: #888;"></i>
                                        {{ __('Aspect Ratio') }}
                                    </span>
                                    <span class="d-flex align-items-center gap-1" style="color: #f97316;">
                                        <span x-text="aspectRatio"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.7rem; color: #666;"></i>
                                    </span>
                                </button>
                                {{-- Resolution --}}
                                <button @click="cycleResolution()" type="button"
                                        class="btn w-100 border-0 d-flex align-items-center justify-content-between px-2 py-2"
                                        style="background: transparent; color: #ccc; font-size: 0.85rem; border-radius: 6px;"
                                        onmouseover="this.style.background='#333'" onmouseout="this.style.background='transparent'">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-display" style="width: 16px; text-align: center; color: #888;"></i>
                                        {{ __('Resolution') }}
                                    </span>
                                    <span class="d-flex align-items-center gap-1" style="color: #f97316;">
                                        <span x-text="resolution"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.7rem; color: #666;"></i>
                                    </span>
                                </button>
                                {{-- Quality --}}
                                <button @click="cycleQuality()" type="button"
                                        class="btn w-100 border-0 d-flex align-items-center justify-content-between px-2 py-2"
                                        style="background: transparent; color: #ccc; font-size: 0.85rem; border-radius: 6px;"
                                        onmouseover="this.style.background='#333'" onmouseout="this.style.background='transparent'">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-gauge-high" style="width: 16px; text-align: center; color: #888;"></i>
                                        {{ __('Quality') }}
                                    </span>
                                    <span class="d-flex align-items-center gap-1" style="color: #f97316;">
                                        <span x-text="quality.charAt(0).toUpperCase() + quality.slice(1)"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.7rem; color: #666;"></i>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Voice Button --}}
                        <button wire:click="openVoiceModal" type="button"
                                class="btn btn-sm border-0 d-flex align-items-center gap-1"
                                style="height: 32px; background: #2a2a2a; color: #ccc; border-radius: 8px; font-size: 0.8rem; padding: 0 10px;">
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
                        <div wire:key="project-{{ $project->id }}">
                            @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                        </div>
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
                        <div wire:key="gallery-{{ $project->id }}">
                            @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                        </div>
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
