<div class="utv-page" x-data="{ showDetail: @js($detailProjectId) }"
     x-init="$watch('$wire.detailProjectId', v => showDetail = v)">

    {{-- ===== TOP ZONE: Header + Input (centered) ===== --}}
    <div class="utv-top-zone">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: var(--at-text, #1a1a2e); font-size: 1.6rem; letter-spacing: -0.02em;">
                {{ __('Turn any link into a video') }}
            </h2>
            <p class="mb-0" style="color: var(--at-text-secondary, #5a6178); font-size: 0.92rem;">{{ __('Paste an article, YouTube video, or social post') }}</p>
        </div>

        {{-- Active Project Progress --}}
        @if($this->activeProject && $this->activeProject->isGenerating())
            <div wire:poll.10s class="card border-0 mb-4" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 12px; box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-1" style="color: var(--at-text, #1a1a2e);">
                                <i class="fa-light fa-spinner-third fa-spin me-2" style="color: #03fcf4;"></i>
                                {{ __('Generating Video') }}
                            </h6>
                            <small class="text-muted">{{ $this->activeProject->current_stage ?? 'Processing...' }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button wire:click="cancelProject({{ $this->activeProject->id }})"
                                    wire:confirm="{{ __('Cancel this video generation?') }}"
                                    wire:loading.attr="disabled"
                                    type="button"
                                    class="btn btn-sm px-3"
                                    style="background: transparent; color: #ef4444; border: 1px solid #eef1f5; border-radius: 8px; font-size: 0.78rem;">
                                <i class="fa-light fa-xmark me-1"></i>{{ __('Cancel') }}
                            </button>
                            <span class="badge" style="background: #03fcf4; color: #0a2e2e; font-size: 0.85rem;">
                                {{ $this->activeProject->progress_percent }}%
                            </span>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px; background: #f1f4f8; border-radius: 3px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $this->activeProject->progress_percent }}%; background: linear-gradient(90deg, #03fcf4, #00d4cc); border-radius: 3px; transition: width 0.5s ease;"
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

        {{-- Input Area --}}
        <div class="utv-input-card mb-4"
             x-data="{
                 promptText: @js($prompt),
                 aspectRatio: $wire.entangle('aspectRatio'),
                 resolution: @js($videoResolution),
                 quality: @js($videoQuality),
                 imageModel: $wire.entangle('imageModel'),
                 imageModelNames: { nanobanana2: 'NanoBanana 2', nanobanana: 'NanoBanana', hidream: 'HiDream' },
                 imageModelCosts: { nanobanana2: '3t', nanobanana: '1t', hidream: '2t' },
                 showSettings: false,
                 placeholders: [
                     '{{ __("Paste a YouTube link or article URL to create a video...") }}',
                     '{{ __("https://techcrunch.com/2026/02/ai-startups-funding...") }}',
                     '{{ __("Turn a LinkedIn post into a professional video...") }}',
                     '{{ __("Create a news explainer from any article...") }}',
                     '{{ __("https://youtube.com/watch?v=... — reimagine as a short") }}'
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
                    wire:model.live.debounce.800ms="prompt"
                    class="form-control border-0"
                    rows="3"
                    :placeholder="placeholders[placeholderIdx]"
                    style="resize: none; font-size: 0.95rem; line-height: 1.6; box-shadow: none; background: transparent !important; padding: 16px 16px 8px;"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Content Preview Badge --}}
                @if(!empty($extractedPreview) || $isExtracting)
                    <div class="d-flex align-items-center gap-2 px-3 pb-2">
                        @if($isExtracting)
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: #f5f7fa; border-radius: 10px; font-size: 0.82rem; color: #5a6178;">
                                <i class="fa-light fa-spinner-third fa-spin" style="color: #03fcf4;"></i>
                                <span>{{ __('Detecting content...') }}</span>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: #f5f7fa; border-radius: 10px; max-width: 100%; overflow: hidden;">
                                {{-- Source type badge --}}
                                @php
                                    $badgeColors = [
                                        'youtube_video' => '#ff0000',
                                        'linkedin' => '#0a66c2',
                                        'twitter' => '#1da1f2',
                                        'news' => '#f59e0b',
                                        'newsletter' => '#10b981',
                                        'article' => '#0891b2',
                                        'prompt' => '#8b5cf6',
                                    ];
                                    $badgeColor = $badgeColors[$extractedPreview['source_type'] ?? 'article'] ?? '#0891b2';
                                    $badgeIcons = [
                                        'youtube_video' => 'fa-brands fa-youtube',
                                        'linkedin' => 'fa-brands fa-linkedin',
                                        'twitter' => 'fa-brands fa-x-twitter',
                                        'news' => 'fa-light fa-newspaper',
                                        'newsletter' => 'fa-light fa-envelope-open-text',
                                        'article' => 'fa-light fa-globe',
                                        'prompt' => 'fa-light fa-pen',
                                    ];
                                    $badgeIcon = $badgeIcons[$extractedPreview['source_type'] ?? 'article'] ?? 'fa-light fa-globe';
                                @endphp
                                <span style="background: {{ $badgeColor }}20; color: {{ $badgeColor }}; font-size: 0.7rem; padding: 3px 8px; border-radius: 5px; font-weight: 600; white-space: nowrap;">
                                    <i class="{{ $badgeIcon }} me-1"></i>
                                    {{ str_replace('_', ' ', ucfirst($extractedPreview['source_type'] ?? 'Article')) }}
                                </span>

                                {{-- Thumbnail --}}
                                @if(!empty($extractedPreview['thumbnail']))
                                    <img src="{{ $extractedPreview['thumbnail'] }}" alt=""
                                         style="width: 28px; height: 28px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
                                @endif

                                {{-- Title --}}
                                @if(!empty($extractedPreview['title']))
                                    <span style="font-size: 0.78rem; color: var(--at-text-secondary, #5a6178); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 360px;">
                                        {{ $extractedPreview['title'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Mode Toggle + Narrative Style Pills --}}
                <div class="px-3 pb-2">
                    {{-- Standard / Creative / Film toggle --}}
                    <div class="utv-mode-toggle mb-2">
                        <button wire:click="clearFilmTemplate" type="button"
                                class="utv-mode-btn {{ !$creativeMode && !$filmMode ? 'active' : '' }}">
                            <i class="fa-light fa-list-music"></i>
                            {{ __('Standard') }}
                        </button>
                        <button wire:click="$set('creativeMode', true)" type="button"
                                class="utv-mode-btn {{ $creativeMode ? 'active creative' : '' }}">
                            <i class="fa-light fa-wand-magic-sparkles"></i>
                            {{ __('Creative') }}
                        </button>
                        <button wire:click="$set('filmMode', true)" type="button"
                                class="utv-mode-btn {{ $filmMode ? 'active film' : '' }}">
                            <i class="fa-light fa-clapperboard"></i>
                            {{ __('Film') }}
                        </button>
                    </div>

                    @if($filmMode)
                        {{-- Film: template cards --}}
                        <div class="utv-film-templates mb-1">
                            @foreach($this->filmTemplates as $tmpl)
                                <button wire:click="selectFilmTemplate('{{ $tmpl['slug'] }}')" type="button"
                                        class="utv-film-card {{ $selectedFilmTemplate === $tmpl['slug'] ? 'active' : '' }}">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="{{ $tmpl['icon'] }}" style="color: {{ $tmpl['color'] }}; font-size: 0.9rem;"></i>
                                        <span class="fw-semibold" style="font-size: 0.82rem;">{{ $tmpl['name'] }}</span>
                                    </div>
                                    <div style="font-size: 0.72rem; color: #5a6178; line-height: 1.4;">{{ $tmpl['description'] }}</div>
                                    <div class="d-flex align-items-center gap-3 mt-1" style="font-size: 0.65rem; color: #94a0b8;">
                                        <span><i class="fa-light fa-users me-1"></i>{{ $tmpl['character_count'] }} characters</span>
                                        <span><i class="fa-light fa-timer me-1"></i>{{ $tmpl['duration'] }}s</span>
                                        <span><i class="fa-light fa-rectangle-wide me-1"></i>{{ $tmpl['aspect_ratio'] }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                        @if($selectedFilmTemplate)
                            <div class="utv-film-banner">
                                <i class="fa-light fa-film"></i>
                                <span>{{ __('Screenplay mode: AI writes character dialogue, no narrator') }}</span>
                            </div>
                        @else
                            <div class="utv-film-banner" style="background: rgba(168,85,247,0.04); border-color: rgba(168,85,247,0.1); color: #7c3aed;">
                                <i class="fa-light fa-hand-pointer"></i>
                                <span>{{ __('Select a film template above to get started') }}</span>
                            </div>
                        @endif
                    @elseif(!$creativeMode)
                        {{-- Standard: show 12 narrative style pills --}}
                        <div class="utv-style-pills-row">
                            @foreach($this->narrativePresets as $preset)
                                <button wire:click="$set('narrativeStyle', '{{ $preset['key'] }}')" type="button"
                                        class="utv-style-pill {{ $narrativeStyle === $preset['key'] ? 'active' : '' }}">
                                    <i class="{{ $preset['icon'] }}"></i>
                                    <span>{{ $preset['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- Creative: info banner --}}
                        <div class="utv-creative-banner">
                            <i class="fa-light fa-sparkles"></i>
                            <span>{{ __('AI will invent a unique creative angle for your topic') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between px-3 pb-3 pt-2" style="border-top: 1px solid #eef1f5;">
                    <div class="d-flex align-items-center gap-1">
                        {{-- Settings Popover --}}
                        <div class="position-relative">
                            <button @click="showSettings = !showSettings" type="button"
                                    class="utv-tool-btn" :class="showSettings ? 'active' : ''">
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
                                 class="utv-settings-popover">
                                <button @click="cycleAspect()" type="button" class="utv-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-crop-simple utv-settings-icon"></i>
                                        {{ __('Aspect Ratio') }}
                                    </span>
                                    <span class="utv-settings-value">
                                        <span x-text="aspectRatio"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                <div style="border-top: 1px solid #eef1f5; margin: 4px 0;"></div>
                                <div class="utv-settings-label">{{ __('Image') }}</div>

                                <button @click="cycleImageModel()" type="button" class="utv-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-image utv-settings-icon"></i>
                                        {{ __('AI Model') }}
                                    </span>
                                    <span class="utv-settings-value">
                                        <span x-text="imageModelNames[imageModel]"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                <div style="border-top: 1px solid #eef1f5; margin: 4px 0;"></div>
                                <div class="utv-settings-label">{{ __('Video') }}</div>

                                <button @click="cycleResolution()" type="button" class="utv-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-display utv-settings-icon"></i>
                                        {{ __('Resolution') }}
                                    </span>
                                    <span class="utv-settings-value">
                                        <span x-text="resolution"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

                                <button @click="cycleQuality()" type="button" class="utv-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-gauge-high utv-settings-icon"></i>
                                        {{ __('Quality') }}
                                    </span>
                                    <span class="utv-settings-value">
                                        <span x-text="quality.charAt(0).toUpperCase() + quality.slice(1)"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Voice Button --}}
                        <button wire:click="openVoiceModal" type="button" class="utv-tool-btn">
                            <i class="fa-light fa-waveform-lines"></i>
                            <span>{{ $selectedVoice === 'auto' ? __('Voice') : $selectedVoice }}</span>
                        </button>

                        {{-- Duration Button --}}
                        <div class="position-relative" x-data="{ showDuration: false }">
                            <button @click="showDuration = !showDuration" type="button"
                                    class="utv-tool-btn" :class="showDuration ? 'active' : ''">
                                <i class="fa-light fa-timer"></i>
                                <span>{{ collect($this->durationPresets)->firstWhere('value', $videoDuration)['label'] ?? '1 min' }}</span>
                            </button>
                            <div x-show="showDuration" x-cloak
                                 @click.away="showDuration = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="utv-settings-popover">
                                <div class="utv-settings-label">{{ __('Video Duration') }}</div>
                                @foreach($this->durationPresets as $preset)
                                    <button wire:click="$set('videoDuration', {{ $preset['value'] }})"
                                            @click="showDuration = false"
                                            type="button" class="utv-settings-row">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="fa-light fa-clock utv-settings-icon"></i>
                                            {{ $preset['label'] }}
                                        </span>
                                        @if($videoDuration === $preset['value'])
                                            <i class="fa-solid fa-check" style="color: #03fcf4; font-size: 0.75rem;"></i>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <button wire:click="submitPrompt" type="button"
                                class="utv-submit-btn"
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
            <div class="alert alert-danger border-0 mb-4" style="background: rgba(239,68,68,0.08); color: #ef4444; border-radius: 12px;">
                <i class="fa-light fa-triangle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- ===== BOTTOM ZONE: Gallery (full width) ===== --}}
    <div class="utv-gallery-zone">
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
                            <div class="utv-project-grid {{ $ratio === '16:9' ? 'landscape' : ($ratio === '1:1' ? 'square' : '') }}">
                                @foreach($ratioProjects as $idx => $project)
                                    <div x-show="expanded || {{ $idx }} < 8" style="{{ $idx >= 8 ? 'display:none;' : '' }}">
                                        @include('appvideowizard::livewire.url-to-video.partials._project-card', ['project' => $project])
                                    </div>
                                @endforeach
                            </div>
                            @if($ratioProjects->count() > 8)
                                <div class="text-center mt-3" x-show="!expanded">
                                    <button @click="expanded = true" type="button" class="utv-show-more-btn">
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

    {{-- Image Sourcing Overlay --}}
    @if($isSourcingImages)
        <div class="d-flex align-items-center justify-content-center"
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;">
            <div class="text-center">
                <i class="fa-light fa-spinner-third fa-spin mb-3" style="font-size: 2rem; color: #03fcf4;"></i>
                <p class="fw-semibold mb-1" style="color: #fff;">{{ __('Finding real images for your scenes...') }}</p>
                <p class="text-muted" style="font-size: 0.85rem;">{{ __('Searching article photos and Wikimedia Commons') }}</p>
            </div>
        </div>
    @endif

    {{-- Script Generation Overlay --}}
    @include('appvideowizard::livewire.partials._script-generation-overlay')

    {{-- Modals --}}
    @include('appvideowizard::livewire.url-to-video.partials._transcript-modal')
    @include('appvideowizard::livewire.url-to-video.partials._image-selection-modal')
    @include('appvideowizard::livewire.url-to-video.partials._stock-library-browser')

    {{-- Voice Modal (reuse same structure) --}}
    @if($showVoiceModal)
    <div class="d-flex align-items-center justify-content-center"
         style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;"
         wire:click.self="$set('showVoiceModal', false)">
        <div class="card border-0" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 16px; width: 480px; max-height: 80vh; overflow-y: auto; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
            <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
                <h5 class="mb-0 fw-bold" style="color: var(--at-text, #1a1a2e);">{{ __('Select Voice') }}</h5>
                <button wire:click="$set('showVoiceModal', false)" type="button" class="btn-close"></button>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="list-group list-group-flush">
                    @foreach($this->voices as $voice)
                        <button wire:click="selectVoice('{{ $voice['id'] }}', '{{ $voice['provider'] }}')"
                                type="button"
                                class="list-group-item list-group-item-action border-0 d-flex align-items-center gap-3 px-3 py-3"
                                style="background: {{ $selectedVoice === $voice['id'] ? 'rgba(3,252,244,0.06)' : 'transparent' }}; border-radius: 10px; color: var(--at-text, #1a1a2e);">
                            <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 40px; height: 40px; border-radius: 50%; background: {{ $selectedVoice === $voice['id'] ? '#03fcf4' : '#f5f7fa' }};">
                                @if($voice['gender'] === 'female')
                                    <i class="fa-light fa-venus" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#f472b6' }};"></i>
                                @elseif($voice['gender'] === 'male')
                                    <i class="fa-light fa-mars" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#60a5fa' }};"></i>
                                @else
                                    <i class="fa-light fa-microphone" style="color: {{ $selectedVoice === $voice['id'] ? '#0a2e2e' : '#a78bfa' }};"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size: 0.9rem;">{{ $voice['name'] }}</div>
                                <small class="text-muted">{{ $voice['description'] }}</small>
                            </div>
                            @if($voice['provider'])
                                <span class="badge" style="background: #f5f7fa; color: #5a6178; font-size: 0.65rem;">{{ ucfirst($voice['provider']) }}</span>
                            @endif
                            @if($selectedVoice === $voice['id'])
                                <i class="fa-solid fa-check-circle" style="color: #0891b2;"></i>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Project Detail Overlay --}}
    @if($this->detailProject)
        @include('appvideowizard::livewire.url-to-video.partials._project-detail', ['project' => $this->detailProject])
    @endif

    {{-- Page-level styles --}}
    <style>
        .utv-page {
            min-height: 100vh;
            background: #ffffff !important;
        }
        .utv-page .form-control,
        .utv-page .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
            background: transparent !important;
            color: var(--at-text, #1a1a2e) !important;
        }
        .utv-page .form-control::placeholder {
            color: var(--at-text-muted, #94a0b8) !important;
        }
        .utv-page .card {
            background: #ffffff !important;
            border: 1px solid #eef1f5;
            box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));
        }
        .utv-top-zone {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 20px 0;
        }
        .utv-gallery-zone {
            padding: 0 24px 40px;
        }
        @media (min-width: 992px) {
            .utv-gallery-zone { padding: 0 40px 40px; }
        }
        .utv-input-card {
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 14px;
            position: relative;
            overflow: visible;
            box-shadow: var(--at-glass, 0 4px 24px rgba(0,0,0,0.06));
        }
        .utv-tool-btn {
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
        .utv-tool-btn:hover {
            background: rgba(3,252,244,0.06);
            color: #1a1a2e;
        }
        .utv-tool-btn i { font-size: 0.9rem; }
        .utv-tool-btn.active {
            background: rgba(3,252,244,0.1);
            color: #0891b2;
        }
        .utv-settings-popover {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 0;
            z-index: 50;
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 12px;
            padding: 10px;
            min-width: 220px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        .utv-settings-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #94a0b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 2px 6px 6px;
        }
        .utv-settings-row {
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
        .utv-settings-row:hover { background: rgba(3,252,244,0.06); }
        .utv-settings-icon {
            width: 16px;
            text-align: center;
            color: #94a0b8;
            font-size: 0.8rem;
        }
        .utv-settings-value {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #0891b2;
            font-size: 0.8rem;
        }
        .utv-submit-btn {
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
        .utv-submit-btn.active {
            background: #03fcf4;
            color: #0a2e2e;
        }
        .utv-submit-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .utv-project-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .utv-project-grid.landscape {
            grid-template-columns: repeat(3, 1fr);
        }
        .utv-project-grid.square {
            grid-template-columns: repeat(4, 1fr);
        }
        @media (max-width: 1200px) {
            .utv-project-grid { grid-template-columns: repeat(3, 1fr); }
            .utv-project-grid.landscape { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .utv-project-grid { grid-template-columns: repeat(2, 1fr); }
            .utv-project-grid.landscape { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .utv-project-grid { grid-template-columns: 1fr; }
            .utv-project-grid.landscape { grid-template-columns: 1fr; }
        }
        .utv-project-card {
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
            border: 1px solid #eef1f5;
            position: relative;
        }
        .utv-project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
        .utv-project-card:hover .utv-card-play-overlay {
            opacity: 1;
        }
        .utv-project-card img,
        .utv-project-card video {
            display: block;
            width: 100%;
            height: auto;
        }
        .utv-card-play-overlay {
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
        .utv-card-play-icon {
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
        .utv-card-play-icon i {
            font-size: 1rem;
            color: #1a1a2e;
            margin-left: 2px;
        }
        .utv-card-meta {
            padding: 10px 12px;
        }
        .utv-card-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--at-text, #1a1a2e);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        .utv-card-date {
            font-size: 0.72rem;
            color: var(--at-text-muted, #94a0b8);
            margin-top: 2px;
        }
        .utv-show-more-btn {
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
        .utv-show-more-btn:hover {
            background: #eef1f5;
            border-color: #d0d5dd;
        }
        .utv-style-pills-row {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-top: 4px;
            padding-bottom: 4px;
        }
        .utv-style-pills-row::-webkit-scrollbar { display: none; }
        .utv-style-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            border: none;
            background: #f5f7fa;
            color: #5a6178;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }
        .utv-style-pill:hover {
            background: #eef1f5;
            color: #1a1a2e;
        }
        .utv-style-pill i { font-size: 0.75rem; }
        .utv-style-pill.active {
            background: #03fcf4;
            color: #0a2e2e;
        }
        /* Mode Toggle */
        .utv-mode-toggle {
            display: inline-flex;
            gap: 2px;
            background: #f5f7fa;
            border-radius: 10px;
            padding: 3px;
        }
        .utv-mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 14px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #5a6178;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .utv-mode-btn:hover { color: #1a1a2e; }
        .utv-mode-btn i { font-size: 0.8rem; }
        .utv-mode-btn.active {
            background: rgba(3,252,244,0.12);
            color: #0891b2;
        }
        .utv-mode-btn.active.creative {
            background: rgba(139,92,246,0.1);
            color: #7c3aed;
        }
        .utv-creative-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(139,92,246,0.06);
            border: 1px solid rgba(139,92,246,0.12);
            border-radius: 10px;
            color: #7c3aed;
            font-size: 0.8rem;
        }
        .utv-creative-banner i { font-size: 0.85rem; }
        /* Concept Badge */
        .utv-concept-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(168,85,247,0.08));
            border: 1px solid rgba(139,92,246,0.2);
            border-radius: 10px;
            color: #7c3aed;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .utv-concept-badge i { font-size: 0.75rem; }
        /* Concept Cards */
        .utv-concept-card {
            padding: 12px 14px;
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }
        .utv-concept-card:hover {
            border-color: rgba(139,92,246,0.3);
            background: #f8fafb;
        }
        .utv-concept-tone-badge {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .utv-tone-funny { background: rgba(245,158,11,0.1); color: #d97706; }
        .utv-tone-emotional { background: rgba(236,72,153,0.1); color: #db2777; }
        .utv-tone-intellectual { background: rgba(96,165,250,0.1); color: #2563eb; }
        .utv-tone-provocative { background: rgba(239,68,68,0.1); color: #dc2626; }
        .utv-tone-whimsical { background: rgba(168,85,247,0.1); color: #7c3aed; }
        .utv-tone-dramatic { background: rgba(251,146,60,0.1); color: #ea580c; }
        /* Film Mode */
        .utv-mode-btn.active.film {
            background: rgba(168,85,247,0.1);
            color: #a855f7;
        }
        .utv-film-templates {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 4px 0;
        }
        .utv-film-templates::-webkit-scrollbar { display: none; }
        .utv-film-card {
            flex: 0 0 220px;
            padding: 12px 14px;
            background: #ffffff;
            border: 1px solid #eef1f5;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
            text-align: left;
        }
        .utv-film-card:hover {
            border-color: rgba(168,85,247,0.3);
            background: #faf8ff;
        }
        .utv-film-card.active {
            border-color: #a855f7;
            background: rgba(168,85,247,0.04);
            box-shadow: 0 0 0 1px rgba(168,85,247,0.2);
        }
        .utv-film-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(168,85,247,0.06);
            border: 1px solid rgba(168,85,247,0.12);
            border-radius: 10px;
            color: #a855f7;
            font-size: 0.8rem;
        }
        .utv-film-banner i { font-size: 0.85rem; }
    </style>
</div>
