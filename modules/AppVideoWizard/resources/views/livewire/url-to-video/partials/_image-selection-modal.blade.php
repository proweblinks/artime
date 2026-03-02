{{-- Image Selection Modal for Real Images Mode --}}
@if($showImageSelectionModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10100;"
     x-data="{
         showSearch: {},
         expandedScenes: {},
         toggleSearch(sceneId) {
             this.showSearch[sceneId] = !this.showSearch[sceneId];
         },
         toggleSceneText(sceneId) {
             this.expandedScenes[sceneId] = !this.expandedScenes[sceneId];
         },
         // Video hover preview state
         hoverTimer: null,
         activePreviewEl: null,
         previewVideo(candidate, event) {
             const btn = event.currentTarget;
             if (this.hoverTimer) clearTimeout(this.hoverTimer);
             this.hoverTimer = setTimeout(() => {
                 if (!candidate.url) return;
                 const video = document.createElement('video');
                 video.src = candidate.url;
                 video.muted = true;
                 video.loop = true;
                 video.autoplay = true;
                 video.playsInline = true;
                 video.className = 'utv-hover-video';
                 video.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:3;pointer-events:none;border-radius:6px;';
                 btn.style.position = 'relative';
                 btn.appendChild(video);
                 this.activePreviewEl = video;
             }, 200);
         },
         stopPreview() {
             if (this.hoverTimer) { clearTimeout(this.hoverTimer); this.hoverTimer = null; }
             if (this.activePreviewEl) {
                 this.activePreviewEl.pause();
                 this.activePreviewEl.remove();
                 this.activePreviewEl = null;
             }
         },
         // Lightbox state
         lightbox: { show: false, url: '', type: 'image', title: '' },
         openLightbox(candidate) {
             this.lightbox = {
                 show: true,
                 url: candidate.url || '',
                 type: (candidate.type === 'video') ? 'video' : 'image',
                 title: candidate.title || '',
             };
         },
         closeLightbox() {
             this.lightbox.show = false;
         },
         // Video edit modal state
         videoEdit: { show: false, sceneId: '', url: '', duration: 0, sceneDuration: 0, trimStart: 0, trimEnd: 0, flipH: false, flipV: false },
         openVideoEdit(sceneId, candidate) {
             const dur = candidate.duration || 10;
             const existing = $wire.sceneVideoEdits?.[sceneId] || null;
             // Get scene duration from generated segments
             const sceneIndex = parseInt(sceneId.replace('scene_', ''));
             const segments = $wire.generatedSegments || [];
             const sceneDur = (segments[sceneIndex] && segments[sceneIndex].estimated_duration) ? segments[sceneIndex].estimated_duration : 6;
             this.videoEdit = {
                 show: true,
                 sceneId: sceneId,
                 url: candidate.url || '',
                 duration: dur,
                 sceneDuration: sceneDur,
                 trimStart: existing ? existing.trimStart : 0,
                 trimEnd: existing ? existing.trimEnd : Math.min(dur, sceneDur),
                 flipH: existing ? existing.flipH : false,
                 flipV: existing ? existing.flipV : false,
             };
         },
         saveVideoEdit() {
             $wire.updateSceneVideoEdit(
                 this.videoEdit.sceneId,
                 parseFloat(this.videoEdit.trimStart),
                 parseFloat(this.videoEdit.trimEnd),
                 this.videoEdit.flipH,
                 this.videoEdit.flipV,
             );
             this.videoEdit.show = false;
         },
         // Crop modal state
         showCropModal: false,
         cropSceneId: '',
         cropImageUrl: '',
         cropImgW: 0,
         cropImgH: 0,
         cropFocalX: 0.5,
         cropFocalY: 0.5,
         cropDragging: false,
         cropContainerW: 0,
         cropContainerH: 0,
         cropFrameW: 0,
         cropFrameH: 0,
         openCropModal(sceneId, imgUrl, w, h) {
             this.cropSceneId = sceneId;
             this.cropImageUrl = imgUrl;
             this.cropImgW = w || 1600;
             this.cropImgH = h || 900;
             this.cropFocalX = 0.5;
             this.cropFocalY = 0.5;
             this.showCropModal = true;
             this.$nextTick(() => { this.initCropFrame(); });
         },
         initCropFrame() {
             const container = this.$refs.cropContainer;
             if (!container) return;
             const img = this.$refs.cropImage;
             if (!img) return;
             const onLoad = () => {
                 this.cropContainerW = container.offsetWidth;
                 this.cropContainerH = container.offsetHeight;
                 // 9:16 frame proportional to container
                 const frameRatio = 9 / 16;
                 this.cropFrameH = this.cropContainerH * 0.85;
                 this.cropFrameW = this.cropFrameH * frameRatio;
                 if (this.cropFrameW > this.cropContainerW * 0.9) {
                     this.cropFrameW = this.cropContainerW * 0.9;
                     this.cropFrameH = this.cropFrameW / frameRatio;
                 }
             };
             if (img.complete) { onLoad(); } else { img.onload = onLoad; }
         },
         get cropFrameStyle() {
             const left = this.cropFocalX * (this.cropContainerW - this.cropFrameW);
             const top = this.cropFocalY * (this.cropContainerH - this.cropFrameH);
             return `width:${this.cropFrameW}px;height:${this.cropFrameH}px;left:${left}px;top:${top}px;`;
         },
         startDragCrop(e) {
             e.preventDefault();
             this.cropDragging = true;
             const startPageX = e.pageX;
             const startPageY = e.pageY;
             const startFX = this.cropFocalX;
             const startFY = this.cropFocalY;
             const maxX = this.cropContainerW - this.cropFrameW;
             const maxY = this.cropContainerH - this.cropFrameH;
             const onMove = (ev) => {
                 if (!this.cropDragging) return;
                 const dx = ev.pageX - startPageX;
                 const dy = ev.pageY - startPageY;
                 this.cropFocalX = Math.max(0, Math.min(1, startFX + (maxX > 0 ? dx / maxX : 0)));
                 this.cropFocalY = Math.max(0, Math.min(1, startFY + (maxY > 0 ? dy / maxY : 0)));
             };
             const onUp = () => {
                 this.cropDragging = false;
                 window.removeEventListener('mousemove', onMove);
                 window.removeEventListener('mouseup', onUp);
             };
             window.addEventListener('mousemove', onMove);
             window.addEventListener('mouseup', onUp);
         },
         saveCrop() {
             $wire.updateSceneCrop(this.cropSceneId, this.cropFocalX, this.cropFocalY);
             this.showCropModal = false;
         }
     }">
    <div class="card border-0 d-flex flex-column"
         style="background: #1a1a1a; border-radius: 16px; width: 720px; max-height: 90vh;">

        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <div>
                <h5 class="mb-1 text-white fw-bold">
                    <i class="fa-light fa-images me-2" style="color: #f97316;"></i>
                    {{ __('Select Images for Your Video') }}
                </h5>
                <small style="color: #999;">{{ __('Choose a clip for each scene from your stock library') }}</small>
            </div>
            <button wire:click="backToTranscript" type="button" class="btn-close btn-close-white"></button>
        </div>

        {{-- Body (scrollable) --}}
        <div class="card-body p-4 pt-2" style="overflow-y: auto;">
            @foreach($sceneImageCandidates as $sceneId => $candidates)
                @php
                    $sceneIndex = (int) str_replace('scene_', '', $sceneId);
                    $sceneText = '';
                    foreach ($generatedSegments as $i => $seg) {
                        if ($i === $sceneIndex) {
                            $sceneText = $seg['text'] ?? '';
                            break;
                        }
                    }
                    if (empty($sceneText) && !empty($editableTranscript)) {
                        $allSegments = array_filter(preg_split('/\n{2,}/', $editableTranscript));
                        $sceneText = array_values($allSegments)[$sceneIndex] ?? '';
                    }
                    $selection = $selectedSceneImages[$sceneId] ?? null;
                    $isAI = $selection === 'ai';
                    $isAnimated = $sceneAnimateWithAI[$sceneId] ?? false;
                    // Check if selected candidate is a video
                    $selectedCandidate = null;
                    if (!$isAI && $selection !== null) {
                        $candidatesList = $sceneImageCandidates[$sceneId] ?? [];
                        $selectedCandidate = (is_int($selection) || (is_string($selection) && ctype_digit($selection)))
                            ? ($candidatesList[(int) $selection] ?? null) : null;
                    }
                    $isVideoSelected = $selectedCandidate && ($selectedCandidate['type'] ?? 'image') === 'video';
                    $videoTrim = $isVideoSelected ? ($sceneVideoEdits[$sceneId] ?? null) : null;
                    $sceneDuration = $generatedSegments[$sceneIndex]['estimated_duration'] ?? 6;
                    $clipDuration = $isVideoSelected ? ($selectedCandidate['duration'] ?? 0) : 0;
                    $isAutoTrimmed = $videoTrim && $clipDuration > $sceneDuration;
                @endphp

                <div class="utv-scene-row mb-4">
                    {{-- Scene header --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
                            <span class="badge" style="background: #2a2a2a; color: #f97316; font-size: 0.7rem; font-weight: 600;">
                                {{ __('Scene') }} {{ $sceneIndex + 1 }}
                            </span>
                            @if($isAI)
                                <span class="badge" style="background: #7c3aed20; color: #a78bfa; font-size: 0.65rem;">
                                    <i class="fa-light fa-wand-magic-sparkles me-1"></i>{{ __('AI Image') }}
                                </span>
                            @elseif($selection !== null && $selection !== 'ai')
                                <span class="badge" style="background: #f9731620; color: #f97316; font-size: 0.65rem;">
                                    <i class="fa-light fa-check me-1"></i>{{ __('Selected') }}
                                </span>
                            @elseif(empty($candidates))
                                <span class="badge" style="background: #ef444420; color: #f87171; font-size: 0.65rem;">
                                    <i class="fa-light fa-triangle-exclamation me-1"></i>{{ __('No images') }}
                                </span>
                            @endif
                            @if($isAnimated)
                                <span class="badge" style="background: #f59e0b20; color: #fbbf24; font-size: 0.65rem;">
                                    <i class="fa-light fa-sparkles me-1"></i>{{ __('Animated') }}
                                </span>
                            @endif
                            {{-- Text toggle button --}}
                            @if(!empty($sceneText))
                                <button @click="toggleSceneText('{{ $sceneId }}')" type="button"
                                        class="utv-text-toggle-btn"
                                        :class="{ 'active': expandedScenes['{{ $sceneId }}'] }">
                                    <i class="fa-light fa-align-left"></i>
                                </button>
                            @endif
                        </div>

                        {{-- Action buttons --}}
                        <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-3">
                            {{-- AI Image pill button --}}
                            <button wire:click="markSceneForAI('{{ $sceneId }}')" type="button"
                                    class="utv-pill-btn {{ $isAI ? 'active-ai' : '' }}"
                                    title="{{ __('Use AI Image') }}">
                                <i class="fa-light fa-wand-magic-sparkles"></i>
                                <span>{{ __('AI Image') }}</span>
                            </button>
                            {{-- Animate toggle (hidden for video clips) --}}
                            @if(!$isVideoSelected)
                                <button wire:click="toggleSceneAnimation('{{ $sceneId }}')" type="button"
                                        class="utv-pill-btn {{ $isAnimated ? 'active-animate' : '' }}"
                                        title="{{ $isAnimated ? __('Animation enabled (Seedance)') : __('Enable AI animation') }}">
                                    <i class="fa-light fa-sparkles"></i>
                                    <span>{{ __('Animate') }}</span>
                                </button>
                            @endif
                            <button @click="toggleSearch('{{ $sceneId }}')" type="button"
                                    class="utv-img-action-btn"
                                    title="{{ __('Search More') }}">
                                <i class="fa-light fa-magnifying-glass"></i>
                            </button>
                            <button wire:click="openLibraryBrowser('{{ $sceneId }}')" type="button"
                                    class="utv-img-action-btn"
                                    title="{{ __('Browse Library') }}">
                                <i class="fa-light fa-photo-film"></i>
                            </button>
                            <label class="utv-img-action-btn mb-0" title="{{ __('Upload Image') }}" style="cursor: pointer;">
                                <i class="fa-light fa-cloud-arrow-up"></i>
                                <input type="file" accept="image/*" class="d-none"
                                       wire:model="uploadedSceneImage"
                                       x-on:change="$wire.set('uploadTargetScene', '{{ $sceneId }}')">
                            </label>
                        </div>
                    </div>

                    {{-- Collapsible scene text --}}
                    @if(!empty($sceneText))
                        <div x-show="expandedScenes['{{ $sceneId }}']" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-1"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="mb-2">
                            <p class="utv-scene-text-full mb-0">{{ $sceneText }}</p>
                        </div>
                    @endif

                    {{-- Search input (toggled) — searches Artime Stock --}}
                    <div x-show="showSearch['{{ $sceneId }}']" x-cloak class="mb-2"
                         x-data="{ sceneQuery: '' }">
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text"
                                   x-model="sceneQuery"
                                   @keydown.enter="$wire.executeSceneSearch('{{ $sceneId }}', sceneQuery)"
                                   class="form-control form-control-sm border-0 text-white"
                                   style="background: #2a2a2a; border-radius: 8px; font-size: 0.82rem; flex: 1;"
                                   placeholder="{{ __('Search stock library...') }}">
                            <button @click="$wire.executeSceneSearch('{{ $sceneId }}', sceneQuery)" type="button"
                                    class="btn btn-sm" style="background: #f97316; color: #fff; border-radius: 8px; white-space: nowrap;">
                                <i class="fa-light fa-magnifying-glass me-1"></i>{{ __('Search') }}
                            </button>
                            <button @click="$wire.searchExternalStock('{{ $sceneId }}', sceneQuery)" type="button"
                                    class="btn btn-sm" style="background: #2a2a2a; color: #888; border: 1px solid #444; border-radius: 8px; white-space: nowrap; font-size: 0.75rem;"
                                    title="{{ __('Search Pexels, Pixabay & Wikimedia') }}">
                                <i class="fa-light fa-globe me-1"></i>{{ __('External') }}
                            </button>
                        </div>
                        {{-- Search feedback --}}
                        @if(session('searchSuccess'))
                            <small class="d-block mt-1" style="color: #22c55e; font-size: 0.75rem;">{{ session('searchSuccess') }}</small>
                        @endif
                        @if(session('searchError'))
                            <small class="d-block mt-1" style="color: #f87171; font-size: 0.75rem;">{{ session('searchError') }}</small>
                        @endif
                    </div>

                    {{-- Image thumbnails row --}}
                    @if(!empty($candidates))
                        <div class="utv-thumb-row-wrap">
                            {{-- AI overlay when AI image selected --}}
                            @if($isAI)
                                <div class="utv-ai-overlay" wire:click="markSceneForAI('{{ $sceneId }}')">
                                    <i class="fa-light fa-wand-magic-sparkles"></i>
                                    <span>{{ __('Will Generate with AI') }}</span>
                                    <small>{{ __('Click to choose a stock image instead') }}</small>
                                </div>
                            @endif
                            <div class="utv-thumb-row"
                                 x-data="{
                                     isDragging: false,
                                     startX: 0,
                                     scrollStart: 0,
                                     hasDragged: false,
                                     init() {
                                         const el = this.$el;
                                         el.addEventListener('mousedown', (e) => {
                                             this.isDragging = true;
                                             this.hasDragged = false;
                                             this.startX = e.pageX;
                                             this.scrollStart = el.scrollLeft;
                                             el.style.cursor = 'grabbing';
                                             el.style.userSelect = 'none';
                                         });
                                         window.addEventListener('mousemove', (e) => {
                                             if (!this.isDragging) return;
                                             const dx = e.pageX - this.startX;
                                             if (Math.abs(dx) > 3) this.hasDragged = true;
                                             el.scrollLeft = this.scrollStart - dx;
                                         });
                                         window.addEventListener('mouseup', () => {
                                             if (this.isDragging) {
                                                 this.isDragging = false;
                                                 el.style.cursor = 'grab';
                                                 el.style.userSelect = '';
                                             }
                                         });
                                         el.addEventListener('click', (e) => {
                                             if (this.hasDragged) {
                                                 e.stopPropagation();
                                                 e.preventDefault();
                                             }
                                         }, true);
                                     }
                                 }">
                                @foreach($candidates as $idx => $candidate)
                                    @php
                                        $isSelected = false;
                                        if (!$isAI && is_array($selection) && ($selection['url'] ?? '') === ($candidate['url'] ?? '')) {
                                            $isSelected = true;
                                        } elseif (!$isAI && is_int($selection) && $selection === $idx) {
                                            $isSelected = true;
                                        }
                                    @endphp
                                    @php $isVideoCandidate = ($candidate['type'] ?? 'image') === 'video'; @endphp
                                    <button wire:click="selectSceneImage('{{ $sceneId }}', {{ $idx }})"
                                            type="button"
                                            class="utv-image-thumb {{ $isSelected ? 'selected' : '' }}"
                                            @if($isVideoCandidate)
                                                @mouseenter="previewVideo(@js($candidate), $event)"
                                                @mouseleave="stopPreview()"
                                                @dblclick.stop="openLightbox(@js($candidate))"
                                            @else
                                                @dblclick.stop="openLightbox(@js($candidate))"
                                            @endif>
                                        <img src="{{ $candidate['thumbnail'] ?? $candidate['url'] }}"
                                             alt="{{ $candidate['title'] ?? 'Image ' . ($idx + 1) }}"
                                             loading="lazy"
                                             draggable="false">
                                        @if($isVideoCandidate)
                                            <div class="utv-video-badge">
                                                <i class="fa-solid fa-play"></i>
                                                {{ $candidate['duration'] ? gmdate('i:s', $candidate['duration']) : '' }}
                                            </div>
                                        @endif
                                        @if($isSelected)
                                            <div class="utv-thumb-check">
                                                <i class="fa-solid fa-check"></i>
                                            </div>
                                            @if($isVideoCandidate)
                                                @php
                                                    $thisTrim = $sceneVideoEdits[$sceneId] ?? null;
                                                    $thisDur = $candidate['duration'] ?? 0;
                                                    $thisSceneDur = $generatedSegments[$sceneIndex]['estimated_duration'] ?? 6;
                                                    $isTrimmed = $thisTrim && $thisDur > $thisSceneDur;
                                                @endphp
                                                @if($isTrimmed)
                                                    <div class="utv-trim-badge" @click.stop="openVideoEdit('{{ $sceneId }}', @js($candidate))" title="{{ __('Click to adjust trim') }}">
                                                        <i class="fa-light fa-scissors"></i>
                                                        {{ gmdate('i:s', (int)($thisTrim['trimStart'] ?? 0)) }}-{{ gmdate('i:s', (int)($thisTrim['trimEnd'] ?? $thisSceneDur)) }}
                                                    </div>
                                                @endif
                                                <button @click.stop="openVideoEdit('{{ $sceneId }}', @js($candidate))"
                                                        class="utv-crop-btn" title="{{ __('Trim & Flip') }}">
                                                    <i class="fa-light fa-scissors"></i>
                                                </button>
                                            @else
                                                <button @click.stop="openCropModal('{{ $sceneId }}', '{{ $candidate['thumbnail'] ?? $candidate['url'] }}', {{ $candidate['width'] ?? 0 }}, {{ $candidate['height'] ?? 0 }})"
                                                        class="utv-crop-btn" title="{{ __('Adjust Position') }}">
                                                    <i class="fa-light fa-crop"></i>
                                                </button>
                                            @endif
                                        @endif
                                        <span class="utv-source-badge">
                                            @if($candidate['source'] === 'artime_stock')
                                                {{ __('Stock') }}
                                            @elseif($candidate['source'] === 'article')
                                                {{ __('Article') }}
                                            @elseif($candidate['source'] === 'pexels')
                                                {{ __('Pexels') }}
                                            @elseif($candidate['source'] === 'pixabay')
                                                {{ __('Pixabay') }}
                                            @elseif($candidate['source'] === 'upload')
                                                {{ __('Upload') }}
                                            @else
                                                {{ __('Wiki') }}
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 p-3" style="background: #1a1a1a; border-radius: 10px;">
                            <i class="fa-light fa-image-slash" style="color: #666;"></i>
                            <span style="color: #999; font-size: 0.82rem;">{{ __('No matching images found. Use Search, Upload, or AI.') }}</span>
                        </div>
                    @endif

                    {{-- Search suggestion chips (search stock library) --}}
                    @if(!empty($sceneSearchSuggestions[$sceneId] ?? []))
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                            <span style="color: #666; font-size: 0.72rem;">{{ __('Try:') }}</span>
                            @foreach($sceneSearchSuggestions[$sceneId] as $suggestion)
                                <button wire:click="executeSceneSearch('{{ $sceneId }}', '{{ addslashes($suggestion) }}')"
                                        type="button" class="utv-suggestion-chip">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Summary --}}
            @php
                $realCount = 0;
                $videoCount = 0;
                $aiCount = 0;
                $animatedCount = 0;
                $unselected = 0;
                foreach ($selectedSceneImages as $sid => $sel) {
                    if ($sel === 'ai') {
                        $aiCount++;
                    } elseif ($sel !== null) {
                        $candidates = $sceneImageCandidates[$sid] ?? [];
                        $selected = (is_int($sel) || (is_string($sel) && ctype_digit($sel)))
                            ? ($candidates[(int) $sel] ?? null) : null;
                        if ($selected && ($selected['type'] ?? 'image') === 'video') {
                            $videoCount++;
                        } else {
                            $realCount++;
                        }
                    } else {
                        $unselected++;
                    }
                    // Count animated scenes
                    if (!empty($sceneAnimateWithAI[$sid])) {
                        $animatedCount++;
                    }
                }
            @endphp
            <div class="d-flex align-items-center gap-3 p-3 mt-2" style="background: #111; border-radius: 10px; font-size: 0.82rem;">
                @if($realCount > 0)
                    <span style="color: #ccc;">
                        <i class="fa-light fa-camera me-1" style="color: #22c55e;"></i>
                        {{ $realCount }} {{ __('real image') }}{{ $realCount > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($videoCount > 0)
                    <span style="color: #ccc;">
                        <i class="fa-light fa-clapperboard-play me-1" style="color: #38bdf8;"></i>
                        {{ $videoCount }} {{ __('free clip') }}{{ $videoCount > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($animatedCount > 0)
                    <span style="color: #ccc;">
                        <i class="fa-light fa-sparkles me-1" style="color: #fbbf24;"></i>
                        {{ $animatedCount }} {{ __('animated') }}
                    </span>
                @endif
                @if($aiCount > 0)
                    <span style="color: #ccc;">
                        <i class="fa-light fa-wand-magic-sparkles me-1" style="color: #a78bfa;"></i>
                        {{ $aiCount }} {{ __('AI') }}
                    </span>
                @endif
                @if($unselected > 0)
                    <span style="color: #f87171;">
                        <i class="fa-light fa-triangle-exclamation me-1"></i>
                        {{ $unselected }} {{ __('unselected') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Video Edit Modal (trim + flip) --}}
        <div x-show="videoEdit.show" x-cloak
             @click.self="videoEdit.show = false"
             @keydown.escape.window="videoEdit.show = false"
             style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.88);z-index:10300;display:flex;align-items:center;justify-content:center;">
            <div class="utv-crop-dialog" style="width:480px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 text-white fw-bold">
                        <i class="fa-light fa-scissors me-2" style="color:#f97316;"></i>
                        {{ __('Edit Video Clip') }}
                    </h6>
                    <button @click="videoEdit.show = false" type="button" class="btn-close btn-close-white btn-close-sm"></button>
                </div>
                {{-- Video preview --}}
                <div style="background:#000;border-radius:10px;overflow:hidden;margin-bottom:16px;max-height:200px;display:flex;align-items:center;justify-content:center;">
                    <video :src="videoEdit.url" controls muted style="max-width:100%;max-height:200px;border-radius:10px;"
                           :style="(videoEdit.flipH ? 'transform:scaleX(-1);' : '') + (videoEdit.flipV ? 'transform:scaleY(-1);' : '')"></video>
                </div>
                {{-- Scene duration info --}}
                <div class="d-flex align-items-center gap-2 mb-3 p-2" style="background:#111;border-radius:8px;">
                    <i class="fa-light fa-clock" style="color:#38bdf8;font-size:0.78rem;"></i>
                    <span style="color:#999;font-size:0.75rem;">
                        {{ __('Scene needs') }} <span x-text="parseFloat(videoEdit.sceneDuration).toFixed(1) + 's'" style="color:#38bdf8;font-weight:600;"></span>
                        {{ __('— clip is') }} <span x-text="parseFloat(videoEdit.duration).toFixed(1) + 's'" style="color:#ccc;font-weight:600;"></span>
                    </span>
                </div>
                {{-- Trim controls --}}
                <div class="mb-3">
                    <label class="d-flex align-items-center justify-content-between mb-1">
                        <span style="color:#ccc;font-size:0.78rem;font-weight:600;">{{ __('Trim Start') }}</span>
                        <span style="color:#f97316;font-size:0.75rem;font-weight:600;" x-text="parseFloat(videoEdit.trimStart).toFixed(1) + 's'"></span>
                    </label>
                    <input type="range" x-model="videoEdit.trimStart" min="0" :max="videoEdit.duration" step="0.1"
                           class="utv-range-slider" style="width:100%;">
                </div>
                <div class="mb-3">
                    <label class="d-flex align-items-center justify-content-between mb-1">
                        <span style="color:#ccc;font-size:0.78rem;font-weight:600;">{{ __('Trim End') }}</span>
                        <span style="color:#f97316;font-size:0.75rem;font-weight:600;" x-text="parseFloat(videoEdit.trimEnd).toFixed(1) + 's'"></span>
                    </label>
                    <input type="range" x-model="videoEdit.trimEnd" min="0" :max="videoEdit.duration" step="0.1"
                           class="utv-range-slider" style="width:100%;">
                </div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="color:#999;font-size:0.75rem;">
                        {{ __('Selected:') }} <span x-text="Math.max(0, (parseFloat(videoEdit.trimEnd) - parseFloat(videoEdit.trimStart))).toFixed(1) + 's'" style="color:#f97316;font-weight:600;"></span>
                        <template x-if="Math.abs(parseFloat(videoEdit.trimEnd) - parseFloat(videoEdit.trimStart) - videoEdit.sceneDuration) > 1">
                            <span style="color:#f59e0b;font-size:0.7rem;margin-left:6px;">
                                <i class="fa-light fa-triangle-exclamation" style="font-size:0.65rem;"></i>
                                <span x-text="(parseFloat(videoEdit.trimEnd) - parseFloat(videoEdit.trimStart)) > videoEdit.sceneDuration ? '{{ __('Longer than scene') }}' : '{{ __('Shorter than scene') }}'"></span>
                            </span>
                        </template>
                    </span>
                </div>
                {{-- Flip controls --}}
                <div class="d-flex gap-3 mt-3 mb-3">
                    <button @click="videoEdit.flipH = !videoEdit.flipH" type="button"
                            class="utv-pill-btn flex-grow-1 justify-content-center"
                            :class="videoEdit.flipH ? 'active-animate' : ''">
                        <i class="fa-light fa-arrows-left-right"></i>
                        <span>{{ __('Flip Horizontal') }}</span>
                    </button>
                    <button @click="videoEdit.flipV = !videoEdit.flipV" type="button"
                            class="utv-pill-btn flex-grow-1 justify-content-center"
                            :class="videoEdit.flipV ? 'active-animate' : ''">
                        <i class="fa-light fa-arrows-up-down"></i>
                        <span>{{ __('Flip Vertical') }}</span>
                    </button>
                </div>
                {{-- Save / Cancel --}}
                <div class="d-flex gap-2 justify-content-end">
                    <button @click="videoEdit.show = false" class="btn btn-sm" style="background:#2a2a2a;color:#ccc;border-radius:8px;">
                        {{ __('Cancel') }}
                    </button>
                    <button @click="saveVideoEdit()" class="btn btn-sm fw-semibold" style="background:#f97316;color:#fff;border-radius:8px;">
                        <i class="fa-light fa-check me-1"></i>
                        {{ __('Apply') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Lightbox Overlay (dblclick on any candidate) --}}
        <div x-show="lightbox.show" x-cloak
             @click.self="closeLightbox()"
             @keydown.escape.window="closeLightbox()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.92);z-index:10400;display:flex;align-items:center;justify-content:center;flex-direction:column;">
            <button @click="closeLightbox()" type="button"
                    style="position:absolute;top:16px;right:16px;z-index:2;background:rgba(255,255,255,0.1);border:none;color:#fff;width:36px;height:36px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                    title="Close">
                <i class="fa-light fa-xmark"></i>
            </button>
            <div style="max-width:90vw;max-height:80vh;display:flex;align-items:center;justify-content:center;">
                <template x-if="lightbox.type === 'video'">
                    <video :src="lightbox.url" controls autoplay style="max-width:90vw;max-height:80vh;border-radius:12px;"></video>
                </template>
                <template x-if="lightbox.type !== 'video'">
                    <img :src="lightbox.url" :alt="lightbox.title" style="max-width:90vw;max-height:80vh;border-radius:12px;object-fit:contain;">
                </template>
            </div>
            <p x-text="lightbox.title" style="color:#999;font-size:0.82rem;margin-top:12px;text-align:center;max-width:600px;"></p>
        </div>

        {{-- Crop Position Modal --}}
        <div x-show="showCropModal" x-cloak
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); z-index: 10200; display: flex; align-items: center; justify-content: center;"
             @click.self="showCropModal = false">
            <div class="utv-crop-dialog">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 text-white fw-bold">
                        <i class="fa-light fa-crop me-2" style="color: #f97316;"></i>
                        {{ __('Adjust Image Position') }}
                    </h6>
                    <button @click="showCropModal = false" type="button" class="btn-close btn-close-white btn-close-sm"></button>
                </div>
                <p class="mb-3" style="color: #999; font-size: 0.78rem;">{{ __('Drag the frame to select which part of the image appears in the vertical video.') }}</p>
                <div class="utv-crop-container" x-ref="cropContainer">
                    <img :src="cropImageUrl" x-ref="cropImage" draggable="false" style="width: 100%; height: 100%; object-fit: contain;">
                    <div class="utv-crop-frame"
                         x-ref="cropFrame"
                         @mousedown="startDragCrop($event)"
                         :style="cropFrameStyle">
                        <div class="utv-crop-frame-label">9:16</div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 justify-content-end">
                    <button @click="showCropModal = false" class="btn btn-sm" style="background:#2a2a2a;color:#ccc;border-radius:8px;">
                        {{ __('Cancel') }}
                    </button>
                    <button @click="saveCrop()" class="btn btn-sm fw-semibold" style="background:#f97316;color:#fff;border-radius:8px;">
                        <i class="fa-light fa-check me-1"></i>
                        {{ __('Save Position') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2 d-flex gap-3" style="background: transparent;">
            <button wire:click="backToTranscript" type="button"
                    class="btn flex-grow-1" style="background: #2a2a2a; color: #ccc; border-radius: 10px;">
                <i class="fa-light fa-arrow-left me-1"></i>
                {{ __('Back') }}
            </button>
            <button wire:click="confirmImageSelection" type="button"
                    class="btn flex-grow-1 fw-semibold" style="background: #f97316; color: #fff; border-radius: 10px;">
                <i class="fa-light fa-video me-1"></i>
                {{ __('Generate Video') }}
            </button>
        </div>
    </div>
</div>

<style>
    .utv-scene-row {
        padding: 14px;
        background: #111;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.04);
    }
    .utv-thumb-row-wrap {
        position: relative;
    }
    .utv-thumb-row-wrap::before,
    .utv-thumb-row-wrap::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 24px;
        z-index: 2;
        pointer-events: none;
    }
    .utv-thumb-row-wrap::before {
        left: 0;
        background: linear-gradient(to right, #111, transparent);
    }
    .utv-thumb-row-wrap::after {
        right: 0;
        background: linear-gradient(to left, #111, transparent);
    }
    .utv-thumb-row {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 4px 0;
        cursor: grab;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .utv-thumb-row::-webkit-scrollbar { display: none; }
    .utv-image-thumb {
        position: relative;
        flex-shrink: 0;
        width: 140px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        background: #222;
        cursor: pointer;
        padding: 0;
        transition: border-color 0.15s;
    }
    .utv-image-thumb:hover {
        border-color: rgba(249, 115, 22, 0.4);
    }
    .utv-image-thumb.selected {
        border-color: #f97316;
    }
    .utv-image-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .utv-video-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        font-size: 0.58rem;
        font-weight: 600;
        color: #fff;
        background: rgba(0,0,0,0.75);
        padding: 2px 6px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 3px;
        z-index: 1;
    }
    .utv-video-badge i {
        font-size: 0.5rem;
    }
    .utv-thumb-check {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #f97316;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
    }
    .utv-source-badge {
        position: absolute;
        bottom: 3px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.6rem;
        font-weight: 600;
        color: #ccc;
        background: rgba(0,0,0,0.7);
        padding: 2px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }
    /* Small icon-only action buttons (search, upload) */
    .utv-img-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: none;
        background: #2a2a2a;
        color: #888;
        font-size: 0.78rem;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .utv-img-action-btn:hover {
        background: #333;
        color: #ccc;
    }
    /* Pill button style for AI Image + Animate */
    .utv-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0 10px;
        height: 30px;
        border-radius: 15px;
        border: 1px solid #333;
        background: #2a2a2a;
        color: #888;
        font-size: 0.72rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .utv-pill-btn i {
        font-size: 0.72rem;
    }
    .utv-pill-btn:hover {
        background: #333;
        color: #ccc;
        border-color: #444;
    }
    .utv-pill-btn.active-ai {
        background: rgba(124, 58, 237, 0.15);
        color: #a78bfa;
        border-color: rgba(124, 58, 237, 0.4);
        box-shadow: 0 0 8px rgba(124, 58, 237, 0.2);
    }
    .utv-pill-btn.active-animate {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.4);
        box-shadow: 0 0 8px rgba(245, 158, 11, 0.2);
    }
    /* AI overlay on thumbnails */
    .utv-ai-overlay {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: rgba(124, 58, 237, 0.15);
        backdrop-filter: blur(4px);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .utv-ai-overlay:hover {
        background: rgba(124, 58, 237, 0.22);
    }
    .utv-ai-overlay > i {
        font-size: 1.5rem;
        color: #a78bfa;
    }
    .utv-ai-overlay > span {
        color: #c4b5fd;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .utv-ai-overlay > small {
        color: rgba(196, 181, 253, 0.6);
        font-size: 0.68rem;
    }
    /* Text toggle button */
    .utv-text-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 5px;
        border: none;
        background: transparent;
        color: #555;
        font-size: 0.7rem;
        cursor: pointer;
        transition: color 0.15s, background 0.15s;
    }
    .utv-text-toggle-btn:hover {
        color: #999;
        background: #2a2a2a;
    }
    .utv-text-toggle-btn.active {
        color: #f97316;
        background: rgba(249, 115, 22, 0.1);
    }
    /* Expanded scene text */
    .utv-scene-text-full {
        color: rgba(255,255,255,0.55);
        font-size: 0.78rem;
        line-height: 1.5;
        padding: 8px 10px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        border-left: 2px solid rgba(249, 115, 22, 0.3);
    }
    /* Search filter pills */
    .utv-search-filter {
        display: flex;
        gap: 2px;
        background: #1a1a1a;
        border-radius: 6px;
        padding: 2px;
        flex-shrink: 0;
    }
    .utv-filter-pill {
        padding: 3px 8px;
        border-radius: 4px;
        border: none;
        background: transparent;
        color: #666;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .utv-filter-pill:hover {
        color: #999;
    }
    .utv-filter-pill.active {
        background: #333;
        color: #fff;
    }
    .utv-suggestion-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 20px;
        border: 1px solid rgba(249, 115, 22, 0.3);
        background: transparent;
        color: #f97316;
        font-size: 0.72rem;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .utv-suggestion-chip:hover {
        background: rgba(249, 115, 22, 0.1);
        border-color: #f97316;
    }
    .utv-crop-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 22px;
        height: 22px;
        border-radius: 4px;
        border: none;
        background: rgba(249, 115, 22, 0.9);
        color: #fff;
        font-size: 0.55rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        transition: background 0.15s;
    }
    .utv-crop-btn:hover {
        background: #f97316;
    }
    .utv-crop-dialog {
        background: #1a1a1a;
        border-radius: 16px;
        padding: 24px;
        width: 520px;
        max-width: 90vw;
    }
    .utv-crop-container {
        position: relative;
        width: 100%;
        height: 320px;
        background: #000;
        border-radius: 10px;
        overflow: hidden;
    }
    .utv-crop-frame {
        position: absolute;
        border: 2px solid #f97316;
        border-radius: 4px;
        cursor: move;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55);
        z-index: 2;
        transition: none;
    }
    /* Hover video preview on thumbnails */
    .utv-hover-video {
        transition: opacity 0.15s ease-in;
    }
    /* Range slider for trim controls */
    .utv-range-slider {
        -webkit-appearance: none;
        appearance: none;
        height: 6px;
        background: #333;
        border-radius: 3px;
        outline: none;
    }
    .utv-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #f97316;
        cursor: pointer;
        border: 2px solid #1a1a1a;
    }
    .utv-range-slider::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #f97316;
        cursor: pointer;
        border: 2px solid #1a1a1a;
    }
    .utv-crop-frame-label {
        position: absolute;
        top: 4px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.6rem;
        font-weight: 600;
        color: #f97316;
        background: rgba(0,0,0,0.7);
        padding: 1px 6px;
        border-radius: 3px;
    }
    /* Auto-trim badge on selected video clips */
    .utv-trim-badge {
        position: absolute;
        bottom: 22px;
        left: 4px;
        font-size: 0.52rem;
        font-weight: 600;
        color: #38bdf8;
        background: rgba(0,0,0,0.8);
        padding: 2px 5px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        gap: 3px;
        z-index: 2;
        cursor: pointer;
        white-space: nowrap;
        border: 1px solid rgba(56,189,248,0.3);
        transition: border-color 0.15s;
    }
    .utv-trim-badge:hover {
        border-color: rgba(56,189,248,0.6);
    }
    .utv-trim-badge i {
        font-size: 0.48rem;
    }
</style>
@endif
