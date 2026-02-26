<div class="utv-page" x-data="{ showDetail: @js($detailProjectId) }"
     x-init="$watch('$wire.detailProjectId', v => showDetail = v)">

    {{-- ===== TOP ZONE: Header + Input (centered) ===== --}}
    <div class="utv-top-zone">

        {{-- Header --}}
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-2" style="color: #fff; font-size: 1.6rem; letter-spacing: -0.02em;">
                {{ __('Turn any link into a video') }}
            </h2>
            <p class="mb-0" style="color: #888; font-size: 0.92rem;">{{ __('Paste an article, YouTube video, or social post') }}</p>
        </div>

        {{-- Active Project Progress --}}
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

        {{-- Input Area --}}
        <div class="utv-input-card mb-4"
             x-data="{
                 promptText: @js($prompt),
                 aspectRatio: @js($aspectRatio),
                 resolution: @js($videoResolution),
                 quality: @js($videoQuality),
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
                 }
             }">
                <textarea
                    x-model="promptText"
                    wire:model.live.debounce.800ms="prompt"
                    class="form-control border-0 text-white"
                    rows="3"
                    :placeholder="placeholders[placeholderIdx]"
                    style="resize: none; font-size: 0.95rem; line-height: 1.6; box-shadow: none; background: transparent !important; padding: 16px 16px 8px;"
                    {{ $isGeneratingScript ? 'disabled' : '' }}
                ></textarea>

                {{-- Content Preview Badge --}}
                @if(!empty($extractedPreview) || $isExtracting)
                    <div class="d-flex align-items-center gap-2 px-3 pb-2">
                        @if($isExtracting)
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: rgba(255,255,255,0.04); border-radius: 10px; font-size: 0.82rem; color: #888;">
                                <i class="fa-light fa-spinner-third fa-spin" style="color: #f97316;"></i>
                                <span>{{ __('Detecting content...') }}</span>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: rgba(255,255,255,0.04); border-radius: 10px; max-width: 100%; overflow: hidden;">
                                {{-- Source type badge --}}
                                @php
                                    $badgeColors = [
                                        'youtube_video' => '#ff0000',
                                        'linkedin' => '#0a66c2',
                                        'twitter' => '#1da1f2',
                                        'news' => '#f59e0b',
                                        'newsletter' => '#10b981',
                                        'article' => '#f97316',
                                        'prompt' => '#8b5cf6',
                                    ];
                                    $badgeColor = $badgeColors[$extractedPreview['source_type'] ?? 'article'] ?? '#f97316';
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
                                    <span style="font-size: 0.78rem; color: #ccc; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 360px;">
                                        {{ $extractedPreview['title'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Narrative Style Pills --}}
                <div class="utv-style-pills-row px-3 pb-2">
                    @foreach($this->narrativePresets as $preset)
                        <button wire:click="$set('narrativeStyle', '{{ $preset['key'] }}')" type="button"
                                class="utv-style-pill {{ $narrativeStyle === $preset['key'] ? 'active' : '' }}">
                            <i class="{{ $preset['icon'] }}"></i>
                            <span>{{ $preset['name'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Bottom Toolbar --}}
                <div class="d-flex align-items-center justify-content-between px-3 pb-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
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
                                <div class="utv-settings-label">{{ __('Settings') }}</div>

                                <button @click="cycleAspect()" type="button" class="utv-settings-row">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fa-light fa-mobile-screen utv-settings-icon"></i>
                                        {{ __('Aspect Ratio') }}
                                    </span>
                                    <span class="utv-settings-value">
                                        <span x-text="aspectRatio"></span>
                                        <i class="fa-light fa-chevron-right" style="font-size: 0.65rem; opacity: 0.4;"></i>
                                    </span>
                                </button>

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

                        {{-- Real Images Toggle --}}
                        <button wire:click="$toggle('useRealImages')" type="button"
                                class="utv-tool-btn {{ $useRealImages ? 'active' : '' }}">
                            <i class="fa-light fa-camera"></i>
                            <span>{{ $useRealImages ? __('Real Images') : __('AI Images') }}</span>
                        </button>

                        {{-- Edit Images (direct access when candidates already sourced) --}}
                        @if($useRealImages && !empty($sceneImageCandidates))
                            <button wire:click="openImageSelection" type="button"
                                    class="utv-tool-btn active">
                                <i class="fa-light fa-images"></i>
                                <span>{{ __('Edit Images') }}</span>
                            </button>
                        @endif
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
            <div class="alert alert-danger border-0 mb-4" style="background: #3d1515; color: #f87171; border-radius: 12px;">
                <i class="fa-light fa-triangle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- ===== BOTTOM ZONE: Gallery (full width) ===== --}}
    <div class="utv-gallery-zone">
        @if($this->userProjects->isNotEmpty())
            <div class="mb-5">
                <h5 class="fw-bold text-white mb-3" style="font-size: 1.1rem;">{{ __('My Projects') }}</h5>
                <div class="utv-masonry">
                    @foreach($this->userProjects->take(8) as $project)
                        @include('appvideowizard::livewire.url-to-video.partials._project-card', ['project' => $project])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Image Sourcing Overlay --}}
    @if($isSourcingImages)
        <div class="d-flex align-items-center justify-content-center"
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 10100;">
            <div class="text-center">
                <i class="fa-light fa-spinner-third fa-spin mb-3" style="font-size: 2rem; color: #f97316;"></i>
                <p class="text-white fw-semibold mb-1">{{ __('Finding real images for your scenes...') }}</p>
                <p class="text-muted" style="font-size: 0.85rem;">{{ __('Searching article photos and Wikimedia Commons') }}</p>
            </div>
        </div>
    @endif

    {{-- Modals --}}
    @include('appvideowizard::livewire.url-to-video.partials._transcript-modal')
    @include('appvideowizard::livewire.url-to-video.partials._image-selection-modal')

    {{-- Voice Modal (reuse same structure) --}}
    @if($showVoiceModal)
    <div class="d-flex align-items-center justify-content-center"
         style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 10100;"
         wire:click.self="$set('showVoiceModal', false)">
        <div class="card border-0" style="background: #1a1a1a; border-radius: 16px; width: 480px; max-height: 80vh; overflow-y: auto;">
            <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
                <h5 class="mb-0 text-white fw-bold">{{ __('Select Voice') }}</h5>
                <button wire:click="$set('showVoiceModal', false)" type="button" class="btn-close btn-close-white"></button>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="list-group list-group-flush">
                    @foreach($this->voices as $voice)
                        <button wire:click="selectVoice('{{ $voice['id'] }}', '{{ $voice['provider'] }}')"
                                type="button"
                                class="list-group-item list-group-item-action border-0 d-flex align-items-center gap-3 px-3 py-3"
                                style="background: {{ $selectedVoice === $voice['id'] ? '#2a2a1a' : 'transparent' }}; border-radius: 10px; color: #fff;">
                            <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 40px; height: 40px; border-radius: 50%; background: {{ $selectedVoice === $voice['id'] ? '#f97316' : '#2a2a2a' }};">
                                @if($voice['gender'] === 'female')
                                    <i class="fa-light fa-venus" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#f472b6' }};"></i>
                                @elseif($voice['gender'] === 'male')
                                    <i class="fa-light fa-mars" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#60a5fa' }};"></i>
                                @else
                                    <i class="fa-light fa-microphone" style="color: {{ $selectedVoice === $voice['id'] ? '#fff' : '#a78bfa' }};"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size: 0.9rem;">{{ $voice['name'] }}</div>
                                <small class="text-muted">{{ $voice['description'] }}</small>
                            </div>
                            @if($voice['provider'])
                                <span class="badge" style="background: #2a2a2a; color: #888; font-size: 0.65rem;">{{ ucfirst($voice['provider']) }}</span>
                            @endif
                            @if($selectedVoice === $voice['id'])
                                <i class="fa-solid fa-check-circle" style="color: #f97316;"></i>
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
            background: #000 !important;
        }
        .utv-page .form-control,
        .utv-page .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
            background: transparent !important;
        }
        .utv-page .card {
            background: #1a1a1a !important;
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
            background: #141414;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            position: relative;
            overflow: visible;
        }
        .utv-tool-btn {
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
        .utv-tool-btn:hover {
            background: rgba(255,255,255,0.08);
            color: #ccc;
        }
        .utv-tool-btn i { font-size: 0.9rem; }
        .utv-tool-btn.active {
            background: rgba(255,255,255,0.1);
            color: #f97316;
        }
        .utv-settings-popover {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 0;
            z-index: 50;
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 10px;
            min-width: 220px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
        }
        .utv-settings-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #555;
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
            color: #ccc;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .utv-settings-row:hover { background: rgba(255,255,255,0.06); }
        .utv-settings-icon {
            width: 16px;
            text-align: center;
            color: #666;
            font-size: 0.8rem;
        }
        .utv-settings-value {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f97316;
            font-size: 0.8rem;
        }
        .utv-submit-btn {
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
        .utv-submit-btn.active {
            background: #f97316;
            color: #fff;
        }
        .utv-submit-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .utv-masonry {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
        }
        .utv-project-card {
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #111;
            position: relative;
        }
        .utv-project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        }
        .utv-project-card img {
            display: block;
            width: 100%;
            height: auto;
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
            background: rgba(255,255,255,0.05);
            color: #888;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }
        .utv-style-pill:hover {
            background: rgba(255,255,255,0.1);
            color: #ccc;
        }
        .utv-style-pill i { font-size: 0.75rem; }
        .utv-style-pill.active {
            background: #f97316;
            color: #fff;
        }
    </style>
</div>
