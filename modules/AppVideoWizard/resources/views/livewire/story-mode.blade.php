<div class="story-mode-page" x-data="{ showDetail: @js($detailProjectId) }"
     x-init="$watch('$wire.detailProjectId', v => showDetail = v)">

    {{-- ===== TOP ZONE: Header + Styles + Input (centered) ===== --}}
    <div class="story-top-zone">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: var(--at-text, #1a1a2e); font-size: 1.6rem; letter-spacing: -0.02em;">
                {{ __('Create a short video in your own style') }}
            </h2>
            <p class="mb-0" style="color: var(--at-text-secondary, #5a6178); font-size: 0.92rem;">{{ __('Visualize your prompt, script, or audio in one click.') }}</p>
        </div>

        {{-- Active Project Progress — poll only when actually generating --}}
        @if($this->activeProject && $this->activeProject->isGenerating())
            <div wire:poll.10s class="card border-0 mb-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-1" style="color: var(--at-text, #1a1a2e);">
                                <i class="fa-light fa-spinner-third fa-spin me-2" style="color: #03fcf4;"></i>
                                {{ __('Generating Video') }}
                            </h6>
                            <small style="color: var(--at-text-muted, #94a0b8);">{{ $this->activeProject->current_stage ?? 'Processing...' }}</small>
                        </div>
                        <span class="badge" style="background: #03fcf4; color: #0a2e2e; font-size: 0.85rem;">
                            {{ $this->activeProject->progress_percent }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px; background: #f1f4f8; border-radius: 3px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $this->activeProject->progress_percent }}%; background: linear-gradient(90deg, #03fcf4, #00d4cc); border-radius: 3px; transition: width 0.5s ease;"
                             aria-valuenow="{{ $this->activeProject->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small style="color: var(--at-text-muted, #94a0b8);">{{ $this->activeProject->title }}</small>
                        <small style="color: var(--at-text-muted, #94a0b8);">{{ $this->activeProject->progress_percent }}% complete</small>
                    </div>
                </div>
            </div>
        @endif

        {{-- Style Picker --}}
        @include('appvideowizard::livewire.story-mode.partials._style-picker')

        {{-- Input Area --}}
        <div class="story-input-card mb-4"
             x-data="{
                 promptText: @js($prompt),
                 aspectRatio: @js($aspectRatio),
                 resolution: @js($videoResolution),
                 quality: @js($videoQuality),
                 imageModel: @js($imageModel),
                 imageModelNames: { nanobanana2: 'NanoBanana 2', nanobanana: 'NanoBanana', hidream: 'HiDream' },
                 imageModelCosts: { nanobanana2: '3t', nanobanana: '1t', hidream: '2t' },
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
                 },
                 cycleImageModel() {
                     const cycle = { nanobanana2: 'nanobanana', nanobanana: 'hidream', hidream: 'nanobanana2' };
                     this.imageModel = cycle[this.imageModel] || 'nanobanana2';
                     $wire.set('imageModel', this.imageModel);
                 }
             }">
                <textarea
                    x-model="promptText"
                    wire:model.live.debounce.500ms="prompt"
                    class="form-control border-0"
                    rows="3"
                    :placeholder="placeholders[placeholderIdx]"
                    style="resize: none; font-size: 0.95rem; line-height: 1.6; box-shadow: none; background: transparent !important; padding: 16px 16px 8px; color: var(--at-text, #1a1a2e);"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Attached File Preview --}}
                @if($attachedFile)
                    <div class="d-flex align-items-center gap-2 px-3 pb-2">
                        <div class="d-flex align-items-center gap-2 px-3 py-1" style="background: rgba(3,252,244,0.06); border-radius: 20px; font-size: 0.78rem; color: var(--at-text-secondary, #5a6178);">
                            <i class="fa-light fa-file" style="color: #0891b2;"></i>
                            <span style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $attachedFile->getClientOriginalName() }}
                            </span>
                            <span style="color: var(--at-text-muted, #94a0b8);">({{ number_format($attachedFile->getSize() / 1024, 0) }}KB)</span>
                            <button wire:click="removeAttachedFile" type="button"
                                    class="btn btn-sm p-0 border-0" style="color: var(--at-text-muted, #94a0b8); line-height: 1;">
                                <i class="fa-light fa-xmark"></i>
                            </button>
                        </div>
                        <div wire:loading wire:target="attachedFile">
                            <i class="fa-light fa-spinner-third fa-spin" style="color: #03fcf4; font-size: 0.8rem;"></i>
                        </div>
                    </div>
                @endif

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between px-3 pb-3 pt-2" style="border-top: 1px solid #eef1f5;">
                    <div class="d-flex align-items-center gap-1">
                        {{-- Attach Button --}}
                        <input type="file" x-ref="fileInput" wire:model="attachedFile" class="d-none"
                               accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                        <button @click="$refs.fileInput.click()" type="button" class="story-tool-btn">
                            <i class="fa-light fa-paperclip"></i>
                        </button>

                        {{-- Settings Popover (Aspect, Resolution, Quality) --}}
                        <div class="position-relative">
                            <button @click="showSettings = !showSettings" type="button"
                                    class="story-tool-btn" :class="showSettings ? 'active' : ''">
                                <i class="fa-light fa-sliders"></i>
                            </button>
                            <div x-show="showSettings" x-cloak
                                 @click.away="showSettings = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="story-settings-popover">
                                <div class="story-settings-label">{{ __('Video') }}</div>

                                {{-- Aspect Ratio --}}
                                <button @click="cycleAspect()" type="button" class="story-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-mobile-screen story-settings-icon"></i>
                                        {{ __('Aspect Ratio') }}
                                    </span>
                                    <span class="story-settings-value">
                                        <span x-text="aspectRatio"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                {{-- Resolution --}}
                                <button @click="cycleResolution()" type="button" class="story-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-display story-settings-icon"></i>
                                        {{ __('Resolution') }}
                                    </span>
                                    <span class="story-settings-value">
                                        <span x-text="resolution"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                {{-- Quality --}}
                                <button @click="cycleQuality()" type="button" class="story-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-gauge-high story-settings-icon"></i>
                                        {{ __('Quality') }}
                                    </span>
                                    <span class="story-settings-value">
                                        <span x-text="quality.charAt(0).toUpperCase() + quality.slice(1)"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                <div style="border-top: 1px solid #eef1f5; margin: 4px 0;"></div>
                                <div class="story-settings-label">{{ __('Image') }}</div>

                                {{-- AI Model --}}
                                <button @click="cycleImageModel()" type="button" class="story-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-image story-settings-icon"></i>
                                        {{ __('AI Model') }}
                                    </span>
                                    <span class="story-settings-value">
                                        <span x-text="imageModelNames[imageModel] + ' ' + imageModelCosts[imageModel]"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Voice Button --}}
                        <button wire:click="openVoiceModal" type="button" class="story-tool-btn">
                            <i class="fa-light fa-waveform-lines"></i>
                            <span>{{ $selectedVoice === 'auto' ? __('Voice') : $selectedVoice }}</span>
                        </button>
                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <button wire:click="submitPrompt" type="button"
                                class="story-submit-btn"
                                :class="promptText.length > 9 ? 'active' : ''"
                                :disabled="promptText.length < 10 || {{ $isGeneratingScript ? 'true' : 'false' }}">
                            @if($isGeneratingScript)
                                <i class="fa-light fa-spinner-third fa-spin"></i>
                            @else
                                <i class="fa-light fa-arrow-up"></i>
                            @endif
                        </button>
                    </div>
                </div>
        </div>

        {{-- Error Messages --}}
        @if(session('error'))
            <div class="alert alert-danger border-0 mb-4" style="background: rgba(239,68,68,0.06); color: #ef4444; border-radius: 12px;">
                <i class="fa-light fa-triangle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- ===== BOTTOM ZONE: Galleries (full width) ===== --}}
    <div class="story-gallery-zone">

        {{-- User's Recent Projects --}}
        @if($this->userProjects->isNotEmpty())
            @php
                $grouped = $this->userProjects->groupBy('aspect_ratio');
                $ratioOrder = ['16:9', '1:1', '9:16'];
                $ratioLabels = ['16:9' => __('Landscape'), '1:1' => __('Square'), '9:16' => __('Portrait')];
                $ratioIcons = ['16:9' => 'fa-rectangle-wide', '1:1' => 'fa-square', '9:16' => 'fa-rectangle-vertical'];
                $totalCount = $this->userProjects->count();
                $hasMultipleRatios = $grouped->count() > 1;
            @endphp
            <div class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem; color: var(--at-text, #1a1a2e);">
                        {{ __('My Projects') }}
                        <span style="font-weight: 400; color: var(--at-text-muted, #94a0b8); font-size: 0.85rem; margin-left: 6px;">{{ $totalCount }}</span>
                    </h5>
                </div>

                @foreach($ratioOrder as $ratio)
                    @if($grouped->has($ratio))
                        @php $ratioProjects = $grouped[$ratio]; @endphp
                        <div class="mb-4" x-data="{ expanded: false }">
                            @if($hasMultipleRatios)
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-light {{ $ratioIcons[$ratio] }}" style="font-size: 0.8rem; color: var(--at-text-muted, #94a0b8);"></i>
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--at-text-secondary, #5a6178);">{{ $ratioLabels[$ratio] }}</span>
                                    <span style="font-size: 0.75rem; color: var(--at-text-muted, #94a0b8);">{{ $ratioProjects->count() }}</span>
                                </div>
                            @endif
                            <div class="story-project-grid {{ $ratio === '16:9' ? 'landscape' : ($ratio === '1:1' ? 'square' : '') }}">
                                @foreach($ratioProjects as $idx => $project)
                                    <div x-show="expanded || {{ $idx }} < 8" style="{{ $idx >= 8 ? 'display:none;' : '' }}">
                                        @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                                    </div>
                                @endforeach
                            </div>
                            @if($ratioProjects->count() > 8)
                                <div class="text-center mt-3" x-show="!expanded">
                                    <button @click="expanded = true" type="button" class="story-show-more-btn">
                                        <i class="fa-light fa-chevron-down me-1"></i>
                                        {{ __('Show More') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Gallery --}}
        @if(isset($galleryProjects) && $galleryProjects->isNotEmpty())
            @php
                $galleryGrouped = $galleryProjects->groupBy('aspect_ratio');
                $galleryHasMultiple = $galleryGrouped->count() > 1;
            @endphp
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem; color: var(--at-text, #1a1a2e);">
                        {{ __('Explore') }}
                    </h5>
                </div>

                @foreach($ratioOrder as $ratio)
                    @if($galleryGrouped->has($ratio))
                        @php $galleryRatioProjects = $galleryGrouped[$ratio]; @endphp
                        <div class="mb-4" x-data="{ expanded: false }">
                            @if($galleryHasMultiple)
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-light {{ $ratioIcons[$ratio] }}" style="font-size: 0.8rem; color: var(--at-text-muted, #94a0b8);"></i>
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--at-text-secondary, #5a6178);">{{ $ratioLabels[$ratio] }}</span>
                                    <span style="font-size: 0.75rem; color: var(--at-text-muted, #94a0b8);">{{ $galleryRatioProjects->count() }}</span>
                                </div>
                            @endif
                            <div class="story-project-grid {{ $ratio === '16:9' ? 'landscape' : ($ratio === '1:1' ? 'square' : '') }}">
                                @foreach($galleryRatioProjects as $gIdx => $project)
                                    <div x-show="expanded || {{ $gIdx }} < 8" style="{{ $gIdx >= 8 ? 'display:none;' : '' }}">
                                        @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                                    </div>
                                @endforeach
                            </div>
                            @if($galleryRatioProjects->count() > 8)
                                <div class="text-center mt-3" x-show="!expanded">
                                    <button @click="expanded = true" type="button" class="story-show-more-btn">
                                        <i class="fa-light fa-chevron-down me-1"></i>
                                        {{ __('Show More') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Script Generation Overlay --}}
    @include('appvideowizard::livewire.partials._script-generation-overlay')

    {{-- Image Sourcing Overlay --}}
    @if($isSourcingImages)
        <div class="d-flex align-items-center justify-content-center"
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;">
            <div class="text-center">
                <i class="fa-light fa-spinner-third fa-spin mb-3" style="font-size: 2rem; color: #03fcf4;"></i>
                <p class="fw-semibold mb-1" style="color: #fff;">{{ __('Finding images for your scenes...') }}</p>
                <p class="text-muted" style="font-size: 0.85rem;">{{ __('Searching stock photos and video clips') }}</p>
            </div>
        </div>
    @endif

    {{-- Modals --}}
    @include('appvideowizard::livewire.story-mode.partials._transcript-modal')
    @include('appvideowizard::livewire.url-to-video.partials._image-selection-modal')
    @include('appvideowizard::livewire.url-to-video.partials._stock-library-browser')
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
            background: #ffffff !important;
        }
        .story-mode-page .form-control,
        .story-mode-page .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
            background: transparent !important;
        }
        .story-mode-page .card {
            background: #ffffff !important;
            border: 1px solid #eef1f5;
            box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));
        }

        /* Top zone — centered, comfortable max-width */
        .story-top-zone {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 20px 0;
        }

        /* Gallery zone — full width with padding */
        .story-gallery-zone {
            padding: 0 24px 40px;
        }
        @media (min-width: 992px) {
            .story-gallery-zone { padding: 0 40px 40px; }
        }

        /* Input card */
        .story-input-card {
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 14px;
            position: relative;
            overflow: visible;
            box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));
        }

        /* Toolbar buttons — transparent, minimal */
        .story-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: #5a6178;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .story-tool-btn:hover {
            background: rgba(3,252,244,0.06);
            color: #1a1a2e;
        }
        .story-tool-btn i {
            font-size: 0.9rem;
        }
        .story-tool-btn.active {
            background: rgba(3,252,244,0.08);
            color: #0891b2;
        }

        /* Settings popover */
        .story-settings-popover {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 0;
            z-index: 50;
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 12px;
            padding: 10px;
            min-width: 220px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .story-settings-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #94a0b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 2px 6px 6px;
        }
        .story-settings-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 7px 6px;
            background: transparent;
            border: none;
            border-radius: 6px;
            color: #1a1a2e;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .story-settings-row:hover {
            background: rgba(3,252,244,0.06);
        }
        .story-settings-icon {
            width: 16px;
            text-align: center;
            color: #94a0b8;
            font-size: 0.8rem;
        }
        .story-settings-value {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #0891b2;
            font-size: 0.8rem;
        }

        /* Submit button */
        .story-submit-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #eef1f5;
            color: #94a0b8;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-size: 0.85rem;
        }
        .story-submit-btn.active {
            background: #03fcf4;
            color: #0a2e2e;
        }
        .story-submit-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Style picker */
        .style-thumb {
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .style-thumb:hover {
            border-color: #d0d5dd;
            transform: translateY(-2px);
        }
        .style-thumb.selected {
            border-color: #03fcf4;
            box-shadow: 0 0 0 1px #03fcf4;
        }

        /* Project grid — 4 columns responsive (portrait default) */
        .story-project-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .story-project-grid.landscape {
            grid-template-columns: repeat(3, 1fr);
        }
        .story-project-grid.square {
            grid-template-columns: repeat(4, 1fr);
        }
        @media (max-width: 1200px) {
            .story-project-grid { grid-template-columns: repeat(3, 1fr); }
            .story-project-grid.landscape { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .story-project-grid { grid-template-columns: repeat(2, 1fr); }
            .story-project-grid.landscape { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .story-project-grid { grid-template-columns: 1fr; }
            .story-project-grid.landscape { grid-template-columns: 1fr; }
        }

        /* Project cards */
        .project-card-wrap {
        }
        .project-card {
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
            border: 1px solid #eef1f5;
            position: relative;
        }
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
        .project-card:hover .story-card-play-overlay {
            opacity: 1;
        }
        .project-card img,
        .project-card video {
            display: block;
            width: 100%;
            height: auto;
        }
        .story-card-play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.25);
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
            z-index: 2;
        }
        .story-card-play-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.92);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .story-card-play-icon i {
            font-size: 1rem;
            color: #1a1a2e;
            margin-left: 2px;
        }
        .story-card-meta {
            padding: 10px 12px;
        }
        .story-card-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--at-text, #1a1a2e);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        .story-card-date {
            font-size: 0.72rem;
            color: var(--at-text-muted, #94a0b8);
            margin-top: 2px;
        }
        .story-show-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 24px;
            background: #f5f7fa;
            border: 1px solid #eef1f5;
            border-radius: 10px;
            color: var(--at-text-secondary, #5a6178);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .story-show-more-btn:hover {
            background: #eef1f5;
            border-color: #d0d5dd;
        }
    </style>
</div>
