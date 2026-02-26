<div class="story-mode-page" x-data="{ showDetail: @js($detailProjectId) }"
     x-init="$watch('$wire.detailProjectId', v => showDetail = v)">

    {{-- Main Content --}}
    <div class="story-mode-main py-4 px-3 px-lg-5" style="max-width: 900px; margin: 0 auto;">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: #fff; font-size: 1.6rem; letter-spacing: -0.02em;">
                {{ __('Create a short video in your own style') }}
            </h2>
            <p class="mb-0" style="color: #888; font-size: 0.92rem;">{{ __('Visualize your prompt, script, or audio in one click.') }}</p>
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
        <div class="story-input-card mb-4"
             x-data="{
                 promptText: @js($prompt),
                 aspectRatio: @js($aspectRatio),
                 placeholders: [
                     '{{ __("A cat astronaut floating through a neon galaxy...") }}',
                     '{{ __("Morning routine of a robot barista in Tokyo...") }}',
                     '{{ __("Time-lapse of a flower blooming in the desert...") }}',
                     '{{ __("A detective solving a mystery in a rain-soaked city...") }}',
                     '{{ __("An underwater adventure with bioluminescent creatures...") }}'
                 ],
                 placeholderIdx: 0,
                 init() {
                     setInterval(() => {
                         this.placeholderIdx = (this.placeholderIdx + 1) % this.placeholders.length;
                     }, 4000);
                 },
                 cycleAspect() {
                     const cycle = { '9:16': '16:9', '16:9': '1:1', '1:1': '9:16' };
                     this.aspectRatio = cycle[this.aspectRatio] || '9:16';
                     $wire.set('aspectRatio', this.aspectRatio);
                 }
             }">
                <textarea
                    x-model="promptText"
                    wire:model.live.debounce.500ms="prompt"
                    class="form-control border-0 text-white"
                    rows="3"
                    :placeholder="placeholders[placeholderIdx]"
                    style="resize: none; font-size: 0.95rem; line-height: 1.6; box-shadow: none; background: transparent !important; padding: 16px 16px 8px;"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Attached File Preview --}}
                @if($attachedFile)
                    <div class="d-flex align-items-center gap-2 px-3 pb-2">
                        <div class="d-flex align-items-center gap-2 px-3 py-1" style="background: rgba(255,255,255,0.06); border-radius: 20px; font-size: 0.78rem; color: #aaa;">
                            <i class="fa-light fa-file" style="color: #f97316;"></i>
                            <span style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $attachedFile->getClientOriginalName() }}
                            </span>
                            <span style="color: #666;">({{ number_format($attachedFile->getSize() / 1024, 0) }}KB)</span>
                            <button wire:click="removeAttachedFile" type="button"
                                    class="btn btn-sm p-0 border-0" style="color: #666; line-height: 1;">
                                <i class="fa-light fa-xmark"></i>
                            </button>
                        </div>
                        <div wire:loading wire:target="attachedFile">
                            <i class="fa-light fa-spinner-third fa-spin" style="color: #f97316; font-size: 0.8rem;"></i>
                        </div>
                    </div>
                @endif

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between px-3 pb-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
                    <div class="d-flex align-items-center gap-1">
                        {{-- Attach Button --}}
                        <input type="file" x-ref="fileInput" wire:model="attachedFile" class="d-none"
                               accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                        <button @click="$refs.fileInput.click()" type="button" class="story-tool-btn">
                            <i class="fa-light fa-paperclip"></i>
                        </button>

                        {{-- Aspect Ratio --}}
                        <button @click="cycleAspect()" type="button" class="story-tool-btn">
                            <i class="fa-light fa-mobile-screen"></i>
                            <span x-text="aspectRatio"></span>
                        </button>

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
            <div class="alert alert-danger border-0 mb-4" style="background: #3d1515; color: #f87171; border-radius: 12px;">
                <i class="fa-light fa-triangle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- User's Recent Projects --}}
        @if($this->userProjects->isNotEmpty())
            <div class="mb-4">
                <h5 class="fw-bold text-white mb-3" style="font-size: 1.1rem;">{{ __('My Projects') }}</h5>
                <div class="story-masonry">
                    @foreach($this->userProjects->take(8) as $project)
                        @include('appvideowizard::livewire.story-mode.partials._project-card', ['project' => $project])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Gallery --}}
        @if(isset($galleryProjects) && $galleryProjects->isNotEmpty())
            <div class="mb-4">
                <h5 class="fw-bold text-white mb-3" style="font-size: 1.1rem;">{{ __('Top story mode') }}</h5>
                <div class="story-masonry">
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
            background: #000 !important;
        }
        .story-mode-page .form-control,
        .story-mode-page .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
            background: transparent !important;
        }
        .story-mode-page .card {
            background: #1a1a1a !important;
        }

        /* Input card */
        .story-input-card {
            background: #141414;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        /* Toolbar buttons — transparent, minimal like Opus Pro */
        .story-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: #888;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .story-tool-btn:hover {
            background: rgba(255,255,255,0.08);
            color: #ccc;
        }
        .story-tool-btn i {
            font-size: 0.9rem;
        }

        /* Submit button */
        .story-submit-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #333;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-size: 0.85rem;
        }
        .story-submit-btn.active {
            background: #f97316;
            color: #fff;
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
            border-color: #444;
            transform: translateY(-2px);
        }
        .style-thumb.selected {
            border-color: #f97316;
            box-shadow: 0 0 0 1px #f97316;
        }

        /* Masonry gallery */
        .story-masonry {
            column-count: 2;
            column-gap: 12px;
        }
        @media (min-width: 576px) {
            .story-masonry { column-count: 3; }
        }
        @media (min-width: 768px) {
            .story-masonry { column-count: 4; }
        }
        @media (min-width: 992px) {
            .story-masonry { column-count: 5; }
        }

        /* Project cards */
        .project-card-wrap {
            break-inside: avoid;
            margin-bottom: 12px;
        }
        .project-card {
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #111;
            position: relative;
        }
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        }
        .project-card img {
            display: block;
            width: 100%;
            height: auto;
        }
    </style>
</div>
