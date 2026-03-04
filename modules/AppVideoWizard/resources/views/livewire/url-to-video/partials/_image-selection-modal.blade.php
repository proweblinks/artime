{{-- Image Selection Modal for Real Images Mode --}}
@if($showImageSelectionModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;"
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
         lightbox: { show: false, url: '', type: 'image', title: '', stockId: null, reported: false },
         openLightbox(candidate) {
             this.lightbox = {
                 show: true,
                 url: candidate.url || '',
                 type: (candidate.type === 'video') ? 'video' : 'image',
                 title: candidate.title || '',
                 stockId: candidate.stock_id || null,
                 reported: false,
             };
         },
         closeLightbox() {
             this.lightbox.show = false;
         },
         reportLightbox() {
             if (!this.lightbox.stockId || this.lightbox.reported) return;
             if (!confirm('Report this media as inappropriate or broken?')) return;
             fetch('/api/stock-media/' + this.lightbox.stockId + '/report', {
                 method: 'POST',
                 headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}
             }).then(() => { this.lightbox.reported = true; }).catch(() => {});
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
         },
         // AI Studio state
         studioActiveScene: @js($activeStudioScene ?: ''),
         editingImagePrompt: {},
         editingVideoPrompt: {},
         studioPreviewType: 'image',
         setStudioScene(sceneId) {
             this.studioActiveScene = sceneId;
             this.studioPreviewType = 'image';
             $wire.setActiveStudioScene(sceneId);
         },
     }">
    @php
        $allAI = $this->areAllScenesAI();
        $isAIStudioMode = $allAI && !empty($sceneVisualScript);
    @endphp
    <div class="card border-0 d-flex flex-column"
         style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 16px; width: {{ $isAIStudioMode ? '1200px' : '720px' }}; max-width: 95vw; max-height: 90vh; box-shadow: 0 8px 30px rgba(0,0,0,0.12); transition: width 0.3s ease;">

        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <div>
                @if($isAIStudioMode)
                    <h5 class="mb-1 fw-bold" style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-wand-magic-sparkles me-2" style="color: #7c3aed;"></i>
                        {{ __('Interactive AI Studio') }}
                    </h5>
                    <small style="color: var(--at-text-muted, #94a0b8);">{{ __('Edit prompts, generate images & video clips per scene') }}</small>
                @else
                    <h5 class="mb-1 fw-bold" style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-images me-2" style="color: #0891b2;"></i>
                        {{ __('Select Images for Your Video') }}
                    </h5>
                    <small style="color: var(--at-text-muted, #94a0b8);">{{ __('Choose a clip for each scene from your stock library') }}</small>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <button wire:click="toggleAllScenesAI" type="button"
                        class="utv-pill-btn {{ $allAI ? 'active-ai' : '' }}"
                        title="{{ $allAI ? __('Revert to stock images') : __('Set all scenes to AI') }}">
                    <i class="fa-light fa-wand-magic-sparkles"></i>
                    <span>{{ $allAI ? __('AI Mode ON') : __('AI Mode') }}</span>
                </button>
                <button wire:click="backToTranscript" type="button" class="btn-close"></button>
            </div>
        </div>

        {{-- Visual Style Picker (AI Studio mode only) --}}
        @if($isAIStudioMode || ($allAI && !empty($sceneVisualScript)))
            <div class="px-4 pt-1 pb-2" style="border-bottom: 1px solid #eef1f5;">
                <div class="d-flex align-items-center gap-2" style="overflow-x: auto; scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <span style="font-size: 0.7rem; color: var(--at-text-muted, #94a0b8); white-space: nowrap; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Style') }}</span>
                    @foreach($this->getVisualStylePresets() as $styleId => $styleConfig)
                        <button wire:click="setVisualStyle('{{ $styleId }}')" type="button"
                                class="d-flex align-items-center gap-1 px-3 py-1"
                                style="border: 2px solid {{ $selectedVisualStyle === $styleId ? $styleConfig['color'] : 'transparent' }};
                                       background: {{ $selectedVisualStyle === $styleId ? $styleConfig['color'] . '15' : '#f8f9fb' }};
                                       border-radius: 20px; cursor: pointer; white-space: nowrap; font-size: 0.75rem;
                                       color: {{ $selectedVisualStyle === $styleId ? $styleConfig['color'] : '#6b7280' }};
                                       font-weight: {{ $selectedVisualStyle === $styleId ? '600' : '500' }};
                                       transition: all 0.15s ease; flex-shrink: 0;">
                            <i class="{{ $styleConfig['icon'] }}" style="font-size: 0.8rem;"></i>
                            {{ __($styleConfig['name']) }}
                        </button>
                    @endforeach
                    @if(!empty($sceneVisualScript))
                        <button wire:click="regenerateAllPrompts" type="button"
                                class="d-flex align-items-center gap-1 px-3 py-1"
                                style="border: 1px dashed #7c3aed40; background: transparent; border-radius: 20px;
                                       cursor: pointer; white-space: nowrap; font-size: 0.7rem; color: #7c3aed;
                                       font-weight: 500; flex-shrink: 0;"
                                title="{{ __('Regenerate all prompts with the selected style') }}">
                            <i class="fa-light fa-arrows-rotate" style="font-size: 0.7rem;"></i>
                            {{ __('Regen Prompts') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Visual Script Loading State --}}
        @if($isGeneratingVisualScript)
            <div class="card-body p-4 pt-2" style="overflow-y: auto;">
                <div class="d-flex flex-column align-items-center justify-content-center py-5">
                    <div class="mb-3" style="width: 40px; height: 40px; border: 3px solid #7c3aed20; border-top-color: #7c3aed; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p class="mb-1 fw-semibold" style="color: var(--at-text, #1a1a2e); font-size: 0.9rem;">{{ __('Generating Visual Script...') }}</p>
                    <small style="color: var(--at-text-muted, #94a0b8);">{{ __('AI is creating image and video prompts for each scene') }}</small>
                </div>
            </div>
        @elseif($isAIStudioMode)
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- AI STUDIO: Two-column layout                                   --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="card-body p-0 d-flex" style="overflow: hidden; flex: 1; min-height: 0;"
             @if($this->hasProcessingVideos()) wire:poll.5s="pollAllVideoStatuses" @endif>

            {{-- LEFT COLUMN: Scene List (scrollable) --}}
            <div class="utv-studio-left" style="width: 520px; flex-shrink: 0; overflow-y: auto; border-right: 1px solid #eef1f5; padding: 16px;">
                @foreach($sceneImageCandidates as $sceneId => $candidates)
                    @php
                        $sceneIndex = (int) str_replace('scene_', '', $sceneId);
                        $segment = $generatedSegments[$sceneIndex] ?? [];
                        $sceneText = $segment['text'] ?? '';
                        $sceneDuration = $segment['estimated_duration'] ?? 6;
                        $visual = $sceneVisualScript[$sceneId] ?? [];
                        $isGeneratingImage = $sceneImageGenerating[$sceneId] ?? false;
                        $videoStatus = $sceneVideoStatus[$sceneId] ?? 'idle';
                        $hasGeneratedImage = !empty($sceneGeneratedImages[$sceneId]);
                        $hasGeneratedVideo = !empty($sceneGeneratedVideos[$sceneId]);
                        $isSplitScene = !empty($segment['split_from']);
                        $splitPart = $segment['split_part'] ?? null;

                        // Check if this scene has a selected image
                        $selection = $selectedSceneImages[$sceneId] ?? [];
                        $sceneSelections = is_array($selection) ? $selection : [];
                        $hasImageSelected = !empty($sceneSelections);
                        $selectedImageUrl = null;
                        $selectedVideoUrl = null;
                        if ($hasImageSelected) {
                            $lastIdx = end($sceneSelections);
                            $c = $candidates[(int) $lastIdx] ?? null;
                            if ($c) {
                                if (($c['type'] ?? 'image') === 'video') {
                                    $selectedVideoUrl = $c['url'] ?? null;
                                    $selectedImageUrl = $c['thumbnail'] ?? $c['url'] ?? null;
                                } else {
                                    $selectedImageUrl = $c['url'] ?? null;
                                }
                            }
                        }
                    @endphp

                    <div class="utv-studio-scene mb-3 p-3 {{ $activeStudioScene === $sceneId ? 'active' : '' }}"
                         @click="setStudioScene('{{ $sceneId }}')"
                         style="background: {{ $activeStudioScene === $sceneId ? '#f8f5ff' : '#ffffff' }}; border: 1px solid {{ $activeStudioScene === $sceneId ? '#7c3aed40' : '#eef1f5' }}; border-radius: 12px; cursor: pointer; transition: all 0.2s;">

                        {{-- Scene header --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge" style="background: rgba(124,58,237,0.1); color: #7c3aed; font-size: 0.7rem; font-weight: 600;">
                                {{ __('Scene') }} {{ $sceneIndex + 1 }}
                            </span>
                            <span class="badge" style="background: #1e3a5f30; color: #38bdf8; font-size: 0.65rem;">
                                <i class="fa-light fa-clock me-1"></i>{{ number_format($sceneDuration, 1) }}s
                            </span>
                            @if($isSplitScene)
                                <span class="badge" style="background: #f59e0b20; color: #d97706; font-size: 0.6rem;">
                                    {{ __('Part') }} {{ $splitPart }}/2
                                </span>
                            @endif
                            @if($hasGeneratedImage)
                                <span class="badge" style="background: #22c55e20; color: #22c55e; font-size: 0.6rem;">
                                    <i class="fa-light fa-image me-1"></i>{{ __('Image') }}
                                </span>
                            @endif
                            @if($hasGeneratedVideo || $videoStatus === 'completed')
                                <span class="badge" style="background: #22c55e20; color: #22c55e; font-size: 0.6rem;">
                                    <i class="fa-light fa-video me-1"></i>{{ __('Video') }}
                                </span>
                            @elseif($videoStatus === 'processing')
                                <span class="badge" style="background: #7c3aed20; color: #7c3aed; font-size: 0.6rem;">
                                    <i class="fa-light fa-spinner-third fa-spin me-1"></i>{{ __('Rendering') }}
                                </span>
                            @endif
                        </div>

                        {{-- Narration text (truncated) --}}
                        @if(!empty($sceneText))
                            <p class="mb-2" style="font-size: 0.75rem; color: var(--at-text-secondary, #5a6178); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $sceneText }}
                            </p>
                        @endif

                        {{-- Image Prompt (editable) --}}
                        @if(!empty($visual['image_prompt']))
                            <div class="mb-2" @click.stop>
                                <label class="d-flex align-items-center gap-1 mb-1" style="font-size: 0.68rem; color: #7c3aed; font-weight: 600;">
                                    <i class="fa-light fa-image"></i> {{ __('Image Prompt') }}
                                </label>
                                <div x-show="!editingImagePrompt['{{ $sceneId }}']"
                                     @click="editingImagePrompt['{{ $sceneId }}'] = true; $nextTick(() => $refs.imgPrompt_{{ $sceneIndex }}?.focus())"
                                     class="utv-studio-prompt" style="font-size: 0.75rem; color: var(--at-text, #1a1a2e); line-height: 1.4; padding: 6px 8px; background: #f5f7fa; border-radius: 6px; border: 1px solid transparent; cursor: text; min-height: 32px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                     title="{{ __('Click to edit') }}">
                                    {{ $visual['image_prompt'] }}
                                </div>
                                <textarea x-show="editingImagePrompt['{{ $sceneId }}']" x-cloak
                                          x-ref="imgPrompt_{{ $sceneIndex }}"
                                          wire:blur="updateSceneImagePrompt('{{ $sceneId }}', $event.target.value)"
                                          @blur="editingImagePrompt['{{ $sceneId }}'] = false"
                                          @keydown.escape="editingImagePrompt['{{ $sceneId }}'] = false"
                                          class="form-control form-control-sm border-0"
                                          style="background: #f5f7fa; border-radius: 6px; font-size: 0.75rem; resize: vertical; min-height: 60px; color: var(--at-text, #1a1a2e);"
                                          rows="3">{{ $visual['image_prompt'] }}</textarea>
                            </div>
                        @endif

                        {{-- Video Prompt (editable) --}}
                        @if(!empty($visual['video_action']))
                            <div class="mb-2" @click.stop>
                                <label class="d-flex align-items-center gap-1 mb-1" style="font-size: 0.68rem; color: #d97706; font-weight: 600;">
                                    <i class="fa-light fa-video"></i> {{ __('Video Prompt') }}
                                </label>
                                <div x-show="!editingVideoPrompt['{{ $sceneId }}']"
                                     @click="editingVideoPrompt['{{ $sceneId }}'] = true; $nextTick(() => $refs.vidPrompt_{{ $sceneIndex }}?.focus())"
                                     class="utv-studio-prompt" style="font-size: 0.75rem; color: var(--at-text, #1a1a2e); line-height: 1.4; padding: 6px 8px; background: #f5f7fa; border-radius: 6px; border: 1px solid transparent; cursor: text; min-height: 28px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                     title="{{ __('Click to edit') }}">
                                    {{ $visual['video_action'] }}
                                </div>
                                <textarea x-show="editingVideoPrompt['{{ $sceneId }}']" x-cloak
                                          x-ref="vidPrompt_{{ $sceneIndex }}"
                                          wire:blur="updateSceneVideoPrompt('{{ $sceneId }}', $event.target.value)"
                                          @blur="editingVideoPrompt['{{ $sceneId }}'] = false"
                                          @keydown.escape="editingVideoPrompt['{{ $sceneId }}'] = false"
                                          class="form-control form-control-sm border-0"
                                          style="background: #f5f7fa; border-radius: 6px; font-size: 0.75rem; resize: vertical; min-height: 44px; color: var(--at-text, #1a1a2e);"
                                          rows="2">{{ $visual['video_action'] }}</textarea>
                            </div>
                        @endif

                        {{-- Generation Buttons --}}
                        <div class="d-flex gap-2 mt-2" @click.stop>
                            {{-- Generate Image --}}
                            <button wire:click="generateSceneAIImage('{{ $sceneId }}')"
                                    type="button"
                                    class="utv-studio-gen-btn"
                                    @if($isGeneratingImage) disabled @endif
                                    style="flex: 1;">
                                @if($isGeneratingImage)
                                    <i class="fa-light fa-spinner-third fa-spin"></i>
                                    <span>{{ __('Generating...') }}</span>
                                @elseif($hasGeneratedImage)
                                    <i class="fa-light fa-arrows-rotate"></i>
                                    <span>{{ __('Regenerate Image') }}</span>
                                @else
                                    <i class="fa-light fa-image"></i>
                                    <span>{{ __('Generate Image') }}</span>
                                @endif
                            </button>

                            {{-- Generate Video (requires image) --}}
                            <button wire:click="generateSceneAIVideo('{{ $sceneId }}')"
                                    type="button"
                                    class="utv-studio-gen-btn utv-studio-gen-btn--video"
                                    @if(!$hasImageSelected || $videoStatus === 'submitting' || $videoStatus === 'processing') disabled @endif
                                    style="flex: 1;">
                                @if($videoStatus === 'submitting')
                                    <i class="fa-light fa-spinner-third fa-spin"></i>
                                    <span>{{ __('Submitting...') }}</span>
                                @elseif($videoStatus === 'processing')
                                    <i class="fa-light fa-spinner-third fa-spin"></i>
                                    <span>{{ __('Rendering...') }}</span>
                                @elseif($videoStatus === 'completed' || $hasGeneratedVideo)
                                    <i class="fa-light fa-arrows-rotate"></i>
                                    <span>{{ __('Regenerate Video') }}</span>
                                @elseif($videoStatus === 'failed')
                                    <i class="fa-light fa-triangle-exclamation"></i>
                                    <span>{{ __('Retry Video') }}</span>
                                @else
                                    <i class="fa-light fa-video"></i>
                                    <span>{{ __('Generate Video') }}</span>
                                @endif
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- RIGHT COLUMN: Preview Panel --}}
            <div class="utv-studio-right d-flex flex-column align-items-center" style="flex: 1; overflow-y: auto; padding: 16px; background: #fafbfc;">

                @php
                    // Get active scene data for preview
                    $activeId = $activeStudioScene ?: array_key_first($sceneVisualScript ?? []) ?: '';
                    $activeIndex = (int) str_replace('scene_', '', $activeId);
                    $activeVisual = $sceneVisualScript[$activeId] ?? [];
                    $activeSelection = $selectedSceneImages[$activeId] ?? [];
                    $activeCandidates = $sceneImageCandidates[$activeId] ?? [];
                    $activeImageUrl = null;
                    $activeVideoUrl = null;
                    $activeIsGeneratingImg = $sceneImageGenerating[$activeId] ?? false;
                    $activeVideoStatus = $sceneVideoStatus[$activeId] ?? 'idle';

                    if (is_array($activeSelection) && !empty($activeSelection)) {
                        $lastIdx = end($activeSelection);
                        $c = $activeCandidates[(int) $lastIdx] ?? null;
                        if ($c) {
                            if (($c['type'] ?? 'image') === 'video') {
                                $activeVideoUrl = $c['url'] ?? null;
                                $activeImageUrl = $c['thumbnail'] ?? $c['url'] ?? null;
                            } else {
                                $activeImageUrl = $c['url'] ?? null;
                            }
                        }
                    }
                @endphp

                {{-- Phone frame preview --}}
                <div class="mb-3" style="width: 100%; max-width: 280px;">
                    <div class="utv-phone-frame" style="position: relative; width: 100%; padding-top: 177.78%; background: #0a0a0a; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        @if($activeIsGeneratingImg)
                            {{-- Image generating skeleton --}}
                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <div style="width: 36px; height: 36px; border: 3px solid #7c3aed40; border-top-color: #7c3aed; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                <span style="color: #a78bfa; font-size: 0.78rem;">{{ __('Generating image...') }}</span>
                            </div>
                        @elseif($activeVideoUrl && ($activeVideoStatus === 'completed' || !empty($sceneGeneratedVideos[$activeId])))
                            {{-- Video preview --}}
                            <video src="{{ $activeVideoUrl }}" muted loop autoplay playsinline
                                   style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;"></video>
                            <div style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #22c55e; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                <i class="fa-light fa-video me-1"></i>{{ __('AI Video') }}
                            </div>
                        @elseif($activeVideoStatus === 'processing')
                            {{-- Video rendering --}}
                            @if($activeImageUrl)
                                <img src="{{ $activeImageUrl }}" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.4;">
                            @endif
                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <div style="width: 36px; height: 36px; border: 3px solid #d9770640; border-top-color: #d97706; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                <span style="color: #fbbf24; font-size: 0.78rem;">{{ __('Rendering video...') }}</span>
                            </div>
                        @elseif($activeImageUrl)
                            {{-- Image preview --}}
                            <img src="{{ $activeImageUrl }}" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #a78bfa; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                <i class="fa-light fa-image me-1"></i>{{ __('AI Image') }}
                            </div>
                        @else
                            {{-- Empty state --}}
                            <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fa-light fa-image" style="font-size: 2rem; color: #333;"></i>
                                <span style="color: #666; font-size: 0.78rem;">{{ __('Generate an image to preview') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Scene info below phone --}}
                @if(!empty($activeVisual))
                    <div class="text-center mb-3" style="max-width: 280px;">
                        <span class="badge" style="background: rgba(124,58,237,0.1); color: #7c3aed; font-size: 0.72rem;">
                            {{ __('Scene') }} {{ $activeIndex + 1 }}
                        </span>
                        <span class="badge" style="background: #1e3a5f30; color: #38bdf8; font-size: 0.68rem;">
                            {{ $activeVisual['mood'] ?? 'professional' }}
                        </span>
                        <span class="badge" style="background: #f59e0b20; color: #d97706; font-size: 0.68rem;">
                            {{ $activeVisual['camera_motion'] ?? '' }}
                        </span>
                    </div>
                @endif

                {{-- Generation History (thumbnails of all versions) --}}
                @php $activeImages = $sceneGeneratedImages[$activeId] ?? []; @endphp
                @if(!empty($activeImages))
                    <div style="max-width: 280px; width: 100%;">
                        <label style="font-size: 0.7rem; color: var(--at-text-muted, #94a0b8); font-weight: 600; display: block; margin-bottom: 6px;">
                            {{ __('Generation History') }}
                        </label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($activeImages as $gi => $genImg)
                                <div class="position-relative" style="width: 48px; height: 48px; border-radius: 6px; overflow: hidden; border: 1px solid #eef1f5;">
                                    <img src="{{ $genImg['url'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <span style="position: absolute; bottom: 1px; right: 1px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.5rem; padding: 1px 3px; border-radius: 2px;">v{{ $gi + 1 }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Video History --}}
                @php $activeVids = $sceneGeneratedVideos[$activeId] ?? []; @endphp
                @if(!empty($activeVids))
                    <div style="max-width: 280px; width: 100%; margin-top: 12px;">
                        <label style="font-size: 0.7rem; color: var(--at-text-muted, #94a0b8); font-weight: 600; display: block; margin-bottom: 6px;">
                            {{ __('Video Clips') }}
                        </label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($activeVids as $vi => $genVid)
                                <div class="position-relative" style="width: 48px; height: 48px; border-radius: 6px; overflow: hidden; border: 1px solid #eef1f5; background: #0a0a0a;">
                                    <video src="{{ $genVid['url'] }}" muted style="width: 100%; height: 100%; object-fit: cover;"
                                           @mouseenter="$el.play()" @mouseleave="$el.pause(); $el.currentTime = 0;"></video>
                                    <span style="position: absolute; bottom: 1px; right: 1px; background: rgba(0,0,0,0.7); color: #22c55e; font-size: 0.5rem; padding: 1px 3px; border-radius: 2px;">
                                        <i class="fa-solid fa-play" style="font-size: 0.35rem;"></i> v{{ $vi + 1 }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Summary stats --}}
                @php
                    $studioImageCount = 0;
                    $studioVideoCount = 0;
                    $studioPendingCount = 0;
                    foreach ($sceneImageCandidates as $sid => $cands) {
                        if (!empty($sceneGeneratedImages[$sid])) $studioImageCount++;
                        if (!empty($sceneGeneratedVideos[$sid]) || ($sceneVideoStatus[$sid] ?? '') === 'completed') $studioVideoCount++;
                        else $studioPendingCount++;
                    }
                @endphp
                <div class="mt-auto pt-3 text-center" style="max-width: 280px; width: 100%; font-size: 0.75rem; color: var(--at-text-muted, #94a0b8);">
                    <span style="color: #22c55e;"><i class="fa-light fa-image me-1"></i>{{ $studioImageCount }}</span> {{ __('images') }}
                    <span class="mx-1">&middot;</span>
                    <span style="color: #22c55e;"><i class="fa-light fa-video me-1"></i>{{ $studioVideoCount }}</span> {{ __('videos') }}
                    @if($studioPendingCount > 0)
                        <span class="mx-1">&middot;</span>
                        <span style="color: #94a0b8;">{{ $studioPendingCount }} {{ __('pending') }}</span>
                    @endif
                </div>
            </div>
        </div>
        @else
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- STOCK MODE: Original single-column layout                      --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
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
                    $selection = $selectedSceneImages[$sceneId] ?? [];
                    $isAI = $selection === 'ai';
                    $isAnimated = $sceneAnimateWithAI[$sceneId] ?? false;
                    // Multi-clip: selection is an array of candidate indices
                    $sceneSelections = is_array($selection) ? $selection : [];
                    $isVideoSelected = false;
                    $selectedCandidate = null;
                    if (!$isAI && !empty($sceneSelections)) {
                        $lastSelIdx = end($sceneSelections);
                        $selectedCandidate = $candidates[(int) $lastSelIdx] ?? null;
                        $isVideoSelected = $selectedCandidate && ($selectedCandidate['type'] ?? 'image') === 'video';
                    }
                    $videoTrim = $isVideoSelected ? ($sceneVideoEdits[$sceneId] ?? null) : null;
                    $sceneDuration = $generatedSegments[$sceneIndex]['estimated_duration'] ?? 6;
                    $clipDuration = $isVideoSelected ? ($selectedCandidate['duration'] ?? 0) : 0;
                    $isAutoTrimmed = $videoTrim && $clipDuration > $sceneDuration;
                @endphp

                <div class="utv-scene-row mb-4">
                    {{-- Scene header --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
                            <span class="badge" style="background: rgba(3,252,244,0.1); color: #0891b2; font-size: 0.7rem; font-weight: 600;">
                                {{ __('Scene') }} {{ $sceneIndex + 1 }}
                            </span>
                            @if($isAI)
                                <span class="badge" style="background: #7c3aed20; color: #a78bfa; font-size: 0.65rem;">
                                    <i class="fa-light fa-wand-magic-sparkles me-1"></i>{{ __('AI Image') }}
                                </span>
                            @elseif(!empty($sceneSelections))
                                <span class="badge" style="background: rgba(3,252,244,0.1); color: #0891b2; font-size: 0.65rem;">
                                    <i class="fa-light fa-check me-1"></i>{{ count($sceneSelections) }} {{ __('selected') }}
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
                            {{-- Duration badge --}}
                            <span class="badge" style="background: #1e3a5f30; color: #38bdf8; font-size: 0.65rem;">
                                <i class="fa-light fa-clock me-1"></i>{{ number_format($sceneDuration, 1) }}s
                            </span>
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

                    {{-- Scene narration text + duration (always visible) --}}
                    @if(!empty($sceneText))
                        <div class="mb-2 d-flex align-items-start gap-2">
                            <p class="mb-0 flex-grow-1" style="font-size: 0.78rem; color: var(--at-text-secondary, #5a6178); line-height: 1.5;">
                                {{ $sceneText }}
                            </p>
                            <span class="badge flex-shrink-0" style="background: #1e3a5f; color: #38bdf8; font-size: 0.72rem; white-space: nowrap;">
                                <i class="fa-light fa-clock me-1"></i>{{ number_format($sceneDuration, 1) }}s
                            </span>
                        </div>
                    @endif

                    {{-- Selected clips strip with duration accumulator --}}
                    @php
                        $totalClipDuration = 0;
                        $selectedClipDetails = [];
                        foreach ($sceneSelections as $pos => $selIdx) {
                            $c = $candidates[(int) $selIdx] ?? null;
                            if ($c) {
                                $dur = ($c['type'] ?? 'image') === 'video' ? (float)($c['duration'] ?? 0) : 0;
                                $totalClipDuration += $dur;
                                $selectedClipDetails[] = ['idx' => $selIdx, 'pos' => $pos, 'candidate' => $c, 'duration' => $dur];
                            }
                        }
                        $durationDiff = $totalClipDuration - $sceneDuration;
                        $hasEnoughClips = $totalClipDuration >= $sceneDuration - 1.0;

                        // Calculate per-clip usage: how much of each clip will actually play in final export
                        $clipUsage = [];
                        $remaining = $sceneDuration;
                        foreach ($selectedClipDetails as $di => $detail) {
                            $used = min($detail['duration'], max(0, $remaining));
                            $isTrimmed = $detail['duration'] > 0 && $used < $detail['duration'] - 0.5;
                            $clipUsage[$di] = ['used' => $used, 'trimmed' => $isTrimmed];
                            $remaining -= $used;
                        }
                    @endphp

                    @if(!empty($selectedClipDetails))
                        <div class="mb-2 p-2 d-flex align-items-center gap-2" style="background: #f8fafb; border-radius: 8px; border: 1px solid {{ $hasEnoughClips ? '#15803d' : '#92400e' }}30;">
                            {{-- Mini thumbnails of selected clips with usage indicators --}}
                            @foreach($selectedClipDetails as $di => $detail)
                                @php $usage = $clipUsage[$di] ?? ['used' => $detail['duration'], 'trimmed' => false]; @endphp
                                <div class="position-relative" style="width: 48px; height: 48px; flex-shrink: 0;">
                                    <img src="{{ $detail['candidate']['thumbnail'] ?? $detail['candidate']['url'] }}"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid {{ $usage['trimmed'] ? '#f59e0b' : '#eef1f5' }};">
                                    @if($detail['duration'] > 0)
                                        <span style="position: absolute; bottom: 1px; right: 1px; background: rgba(0,0,0,0.8); color: #fff; font-size: 0.55rem; padding: 1px 3px; border-radius: 3px;">
                                            {{ gmdate('i:s', (int) $detail['duration']) }}
                                        </span>
                                    @endif
                                    @if($usage['trimmed'])
                                        <span style="position: absolute; top: 1px; left: 1px; background: rgba(245,158,11,0.9); color: #fff; font-size: 0.5rem; padding: 1px 3px; border-radius: 3px;" title="{{ __('Will use') }} {{ number_format($usage['used'], 1) }}s {{ __('of') }} {{ number_format($detail['duration'], 1) }}s">
                                            <i class="fa-solid fa-scissors" style="font-size: 0.4rem;"></i> {{ number_format($usage['used'], 1) }}s
                                        </span>
                                    @endif
                                    <button wire:click="removeSceneClip('{{ $sceneId }}', {{ $detail['pos'] }})"
                                            type="button"
                                            style="position: absolute; top: -4px; right: -4px; width: 16px; height: 16px; background: #ef4444; color: #fff; border: none; border-radius: 50%; font-size: 0.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            @endforeach

                            {{-- Duration summary --}}
                            <div class="ms-auto text-end" style="font-size: 0.72rem; white-space: nowrap;">
                                <span style="color: {{ $hasEnoughClips ? '#22c55e' : '#f59e0b' }}; font-weight: 600;">
                                    {{ number_format($totalClipDuration, 1) }}s / {{ number_format($sceneDuration, 1) }}s
                                </span>
                                @if($durationDiff >= 0)
                                    <small class="d-block" style="color: #22c55e;"><i class="fa-light fa-check me-1"></i>{{ __('Covered') }}</small>
                                @else
                                    <small class="d-block" style="color: #f59e0b;">{{ __('Need') }} {{ number_format(abs($durationDiff), 1) }}s {{ __('more') }}</small>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Search input (toggled) — searches Artime Stock --}}
                    <div x-show="showSearch['{{ $sceneId }}']" x-cloak class="mb-2"
                         x-data="{ sceneQuery: '' }">
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text"
                                   x-model="sceneQuery"
                                   @keydown.enter="$wire.executeSceneSearch('{{ $sceneId }}', sceneQuery)"
                                   class="form-control form-control-sm border-0"
                                   style="background: #f5f7fa; border-radius: 8px; font-size: 0.82rem; flex: 1; color: var(--at-text, #1a1a2e);"
                                   placeholder="{{ __('Search stock library...') }}">
                            <button @click="$wire.executeSceneSearch('{{ $sceneId }}', sceneQuery)" type="button"
                                    class="btn btn-sm" style="background: #03fcf4; color: #0a2e2e; border-radius: 8px; white-space: nowrap;">
                                <i class="fa-light fa-magnifying-glass me-1"></i>{{ __('Search') }}
                            </button>
                            @php $externalFallback = ($sceneSearchSuggestions[$sceneId] ?? [])[0] ?? ''; @endphp
                            <button @click="$wire.searchExternalStock('{{ $sceneId }}', sceneQuery || '{{ addslashes($externalFallback) }}')" type="button"
                                    class="btn btn-sm" style="background: #f5f7fa; color: #5a6178; border: 1px solid #eef1f5; border-radius: 8px; white-space: nowrap; font-size: 0.75rem;"
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
                                        if (!$isAI && is_array($selection) && in_array($idx, $selection, true)) {
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
                                        <span class="utv-source-badge" @if(($candidate['source'] ?? '') === 'previous_selection') style="background: rgba(3,252,244,0.85); color: #0a2e2e;" @endif>
                                            @if(($candidate['source'] ?? '') === 'previous_selection')
                                                {{ __('Previous') }}
                                            @elseif($candidate['source'] === 'artime_stock')
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
                                        @if(!empty($candidate['stock_id']))
                                            <span class="utv-report-btn"
                                                  x-data="{ reported: false }"
                                                  @click.stop="if (reported) return; if (!confirm('Report this media as inappropriate or broken?')) return; fetch('/api/stock-media/{{ $candidate['stock_id'] }}/report', { method: 'POST', headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'} }).then(r => { reported = true; if (r.status === 409) { /* already reported */ } }).catch(() => {})"
                                                  :class="{ 'utv-report-btn--reported': reported }"
                                                  :title="reported ? '{{ __('Reported') }}' : '{{ __('Report this media') }}'">
                                                <i class="fa-solid fa-flag" style="font-size: 9px;"></i>
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                                {{-- Load More button at end of row --}}
                                <button x-data="{ loading: false }"
                                        @click="if (loading) return; loading = true; $wire.loadMoreCandidates('{{ $sceneId }}').then(() => { loading = false; }).catch(() => { loading = false; })"
                                        :disabled="loading"
                                        type="button"
                                        class="utv-load-more-btn"
                                        style="flex: 0 0 80px; min-width: 80px; height: 100%; min-height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; background: #f5f7fa; border: 1px dashed #d0d5dd; border-radius: 8px; color: #94a0b8; font-size: 0.7rem; cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.background='#eef1f5';this.style.color='#1a1a2e';this.style.borderColor='#0891b2'"
                                        onmouseout="this.style.background='#f5f7fa';this.style.color='#94a0b8';this.style.borderColor='#d0d5dd'">
                                    <i class="fa-light" :class="loading ? 'fa-spinner-third fa-spin' : 'fa-arrow-right'"></i>
                                    <span x-show="!loading">{{ __('More') }}</span>
                                    <span x-show="loading" x-cloak>...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 p-3" style="background: #f5f7fa; border-radius: 10px;">
                            <i class="fa-light fa-image-slash" style="color: #94a0b8;"></i>
                            <span style="color: var(--at-text-secondary, #5a6178); font-size: 0.82rem;">{{ __('No matching images found. Use Search, Upload, or AI.') }}</span>
                        </div>
                    @endif

                    {{-- Search suggestion chips (search stock library) --}}
                    @if(!empty($sceneSearchSuggestions[$sceneId] ?? []))
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                            <span style="color: #94a0b8; font-size: 0.72rem;">{{ __('Try:') }}</span>
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
                foreach ($sceneImageCandidates as $sid => $cands) {
                    $sel = $selectedSceneImages[$sid] ?? [];
                    if ($sel === 'ai') {
                        $aiCount++;
                    } elseif (!empty($sel) && is_array($sel)) {
                        foreach ($sel as $si) {
                            $c = $cands[(int) $si] ?? null;
                            if ($c && ($c['type'] ?? 'image') === 'video') $videoCount++;
                            else $realCount++;
                        }
                    } else {
                        $unselected++;
                    }
                    if (!empty($sceneAnimateWithAI[$sid])) {
                        $animatedCount++;
                    }
                }
            @endphp
            <div class="d-flex align-items-center gap-3 p-3 mt-2" style="background: #f5f7fa; border-radius: 10px; font-size: 0.82rem;">
                @if($realCount > 0)
                    <span style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-camera me-1" style="color: #22c55e;"></i>
                        {{ $realCount }} {{ __('real image') }}{{ $realCount > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($videoCount > 0)
                    <span style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-clapperboard-play me-1" style="color: #38bdf8;"></i>
                        {{ $videoCount }} {{ __('free clip') }}{{ $videoCount > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($animatedCount > 0)
                    <span style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-sparkles me-1" style="color: #fbbf24;"></i>
                        {{ $animatedCount }} {{ __('animated') }}
                    </span>
                @endif
                @if($aiCount > 0)
                    <span style="color: var(--at-text, #1a1a2e);">
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
        @endif {{-- End AI Studio vs Stock mode --}}

        {{-- Video Edit Modal (trim + flip) --}}
        <div x-show="videoEdit.show" x-cloak
             @click.self="videoEdit.show = false"
             @keydown.escape.window="videoEdit.show = false"
             style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:10300;display:flex;align-items:center;justify-content:center;">
            <div class="utv-crop-dialog" style="width:480px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold" style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-scissors me-2" style="color:#0891b2;"></i>
                        {{ __('Edit Video Clip') }}
                    </h6>
                    <button @click="videoEdit.show = false" type="button" class="btn-close btn-close-sm"></button>
                </div>
                {{-- Video preview --}}
                <div style="background:#000;border-radius:10px;overflow:hidden;margin-bottom:16px;max-height:200px;display:flex;align-items:center;justify-content:center;">
                    <video :src="videoEdit.url" controls muted style="max-width:100%;max-height:200px;border-radius:10px;"
                           :style="(videoEdit.flipH ? 'transform:scaleX(-1);' : '') + (videoEdit.flipV ? 'transform:scaleY(-1);' : '')"></video>
                </div>
                {{-- Scene duration info --}}
                <div class="d-flex align-items-center gap-2 mb-3 p-2" style="background:#f5f7fa;border-radius:8px;">
                    <i class="fa-light fa-clock" style="color:#38bdf8;font-size:0.78rem;"></i>
                    <span style="color:var(--at-text-secondary, #5a6178);font-size:0.75rem;">
                        {{ __('Scene needs') }} <span x-text="parseFloat(videoEdit.sceneDuration).toFixed(1) + 's'" style="color:#38bdf8;font-weight:600;"></span>
                        {{ __('— clip is') }} <span x-text="parseFloat(videoEdit.duration).toFixed(1) + 's'" style="color:var(--at-text, #1a1a2e);font-weight:600;"></span>
                    </span>
                </div>
                {{-- Trim controls --}}
                <div class="mb-3">
                    <label class="d-flex align-items-center justify-content-between mb-1">
                        <span style="color:var(--at-text, #1a1a2e);font-size:0.78rem;font-weight:600;">{{ __('Trim Start') }}</span>
                        <span style="color:#0891b2;font-size:0.75rem;font-weight:600;" x-text="parseFloat(videoEdit.trimStart).toFixed(1) + 's'"></span>
                    </label>
                    <input type="range" x-model="videoEdit.trimStart" min="0" :max="videoEdit.duration" step="0.1"
                           class="utv-range-slider" style="width:100%;">
                </div>
                <div class="mb-3">
                    <label class="d-flex align-items-center justify-content-between mb-1">
                        <span style="color:var(--at-text, #1a1a2e);font-size:0.78rem;font-weight:600;">{{ __('Trim End') }}</span>
                        <span style="color:#0891b2;font-size:0.75rem;font-weight:600;" x-text="parseFloat(videoEdit.trimEnd).toFixed(1) + 's'"></span>
                    </label>
                    <input type="range" x-model="videoEdit.trimEnd" min="0" :max="videoEdit.duration" step="0.1"
                           class="utv-range-slider" style="width:100%;">
                </div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="color:var(--at-text-secondary, #5a6178);font-size:0.75rem;">
                        {{ __('Selected:') }} <span x-text="Math.max(0, (parseFloat(videoEdit.trimEnd) - parseFloat(videoEdit.trimStart))).toFixed(1) + 's'" style="color:#0891b2;font-weight:600;"></span>
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
                    <button @click="videoEdit.show = false" class="btn btn-sm" style="background:#f5f7fa;color:var(--at-text-secondary, #5a6178);border-radius:8px;">
                        {{ __('Cancel') }}
                    </button>
                    <button @click="saveVideoEdit()" class="btn btn-sm fw-semibold" style="background:#03fcf4;color:#0a2e2e;border-radius:8px;">
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
            <template x-if="lightbox.stockId">
                <button @click.stop="reportLightbox()" type="button"
                        :class="lightbox.reported ? 'opacity-50' : ''"
                        :disabled="lightbox.reported"
                        style="margin-top:10px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:#fff;padding:6px 16px;border-radius:8px;font-size:0.78rem;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.3)';this.style.borderColor='rgba(239,68,68,0.5)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.1)';this.style.borderColor='rgba(255,255,255,0.2)'">
                    <i class="fa-solid fa-flag" :style="lightbox.reported ? 'color:#ef4444' : ''"></i>
                    <span x-text="lightbox.reported ? '{{ __('Reported') }}' : '{{ __('Report Media') }}'"></span>
                </button>
            </template>
        </div>

        {{-- Crop Position Modal --}}
        <div x-show="showCropModal" x-cloak
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10200; display: flex; align-items: center; justify-content: center;"
             @click.self="showCropModal = false">
            <div class="utv-crop-dialog">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold" style="color: var(--at-text, #1a1a2e);">
                        <i class="fa-light fa-crop me-2" style="color: #0891b2;"></i>
                        {{ __('Adjust Image Position') }}
                    </h6>
                    <button @click="showCropModal = false" type="button" class="btn-close btn-close-sm"></button>
                </div>
                <p class="mb-3" style="color: var(--at-text-secondary, #5a6178); font-size: 0.78rem;">{{ __('Drag the frame to select which part of the image appears in the vertical video.') }}</p>
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
                    <button @click="showCropModal = false" class="btn btn-sm" style="background:#f5f7fa;color:var(--at-text-secondary, #5a6178);border-radius:8px;">
                        {{ __('Cancel') }}
                    </button>
                    <button @click="saveCrop()" class="btn btn-sm fw-semibold" style="background:#03fcf4;color:#0a2e2e;border-radius:8px;">
                        <i class="fa-light fa-check me-1"></i>
                        {{ __('Save Position') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2" style="background: transparent;">
            {{-- Video Settings --}}
            <div class="d-flex align-items-center gap-3 mb-3 p-3" style="background: #f5f7fa; border-radius: 10px;"
                 x-data="{
                     resolution: @js($videoResolution),
                     quality: @js($videoQuality),
                     init() {
                         this.$watch('quality', (val) => {
                             if (val === 'fast' && this.resolution === '480p') {
                                 this.resolution = '720p';
                                 $wire.set('videoResolution', '720p');
                             }
                             $wire.set('videoQuality', val);
                         });
                         this.$watch('resolution', (val) => {
                             $wire.set('videoResolution', val);
                         });
                     }
                 }">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="fa-light fa-film" style="font-size: 0.85rem; color: var(--at-text-muted, #94a0b8);"></i>
                    <small style="white-space: nowrap; color: var(--at-text-muted, #94a0b8);">{{ __('Resolution') }}</small>
                    <select x-model="resolution"
                            class="form-select form-select-sm border-0"
                            style="background: #ffffff; border-radius: 6px; font-size: 0.8rem; width: auto; padding: 4px 28px 4px 10px; color: var(--at-text, #1a1a2e);">
                        <option value="480p" x-show="quality !== 'fast'">480p</option>
                        <option value="720p">720p</option>
                        <option value="1080p">1080p</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="fa-light fa-gauge-high" style="font-size: 0.85rem; color: var(--at-text-muted, #94a0b8);"></i>
                    <small style="white-space: nowrap; color: var(--at-text-muted, #94a0b8);">{{ __('Quality') }}</small>
                    <select x-model="quality"
                            class="form-select form-select-sm border-0"
                            style="background: #ffffff; border-radius: 6px; font-size: 0.8rem; width: auto; padding: 4px 28px 4px 10px; color: var(--at-text, #1a1a2e);">
                        <option value="pro">Pro</option>
                        <option value="fast">Fast</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-3">
                <button wire:click="backToTranscript" type="button"
                        class="btn flex-grow-1" style="background: #f5f7fa; color: var(--at-text-secondary, #5a6178); border-radius: 10px;">
                    <i class="fa-light fa-arrow-left me-1"></i>
                    {{ __('Back') }}
                </button>
                <button wire:click="confirmImageSelection" type="button"
                        class="btn flex-grow-1 fw-semibold" style="background: #03fcf4; color: #0a2e2e; border-radius: 10px;">
                    @if($isAIStudioMode)
                        <i class="fa-light fa-rocket me-1"></i>
                        {{ __('Generate Final Video') }}
                    @else
                        <i class="fa-light fa-video me-1"></i>
                        {{ __('Generate Video') }}
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .utv-scene-row {
        padding: 14px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #eef1f5;
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
        background: linear-gradient(to right, #ffffff, transparent);
    }
    .utv-thumb-row-wrap::after {
        right: 0;
        background: linear-gradient(to left, #ffffff, transparent);
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
        background: #f5f7fa;
        cursor: pointer;
        padding: 0;
        transition: border-color 0.15s;
    }
    .utv-image-thumb:hover {
        border-color: rgba(3, 252, 244, 0.4);
    }
    .utv-image-thumb.selected {
        border-color: #03fcf4;
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
        background: #03fcf4;
        color: #0a2e2e;
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
        color: #fff;
        background: rgba(0,0,0,0.6);
        padding: 2px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }
    .utv-report-btn {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.5);
        color: #ccc;
        border-radius: 4px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.15s, background 0.15s, color 0.15s;
        z-index: 5;
    }
    .utv-image-thumb:hover .utv-report-btn { opacity: 1; }
    .utv-report-btn:hover { background: rgba(220,38,38,0.8); color: #fff; }
    .utv-report-btn--reported {
        opacity: 1 !important;
        background: rgba(220,38,38,0.85);
        color: #fff;
        pointer-events: none;
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
        background: #f5f7fa;
        color: #5a6178;
        font-size: 0.78rem;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .utv-img-action-btn:hover {
        background: #eef1f5;
        color: #1a1a2e;
    }
    /* Pill button style for AI Image + Animate */
    .utv-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0 10px;
        height: 30px;
        border-radius: 15px;
        border: 1px solid #eef1f5;
        background: #f5f7fa;
        color: #5a6178;
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
        background: #eef1f5;
        color: #1a1a2e;
        border-color: #d0d5dd;
    }
    .utv-pill-btn.active-ai {
        background: rgba(124, 58, 237, 0.08);
        color: #7c3aed;
        border-color: rgba(124, 58, 237, 0.3);
        box-shadow: 0 0 8px rgba(124, 58, 237, 0.1);
    }
    .utv-pill-btn.active-animate {
        background: rgba(245, 158, 11, 0.08);
        color: #d97706;
        border-color: rgba(245, 158, 11, 0.3);
        box-shadow: 0 0 8px rgba(245, 158, 11, 0.1);
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
        background: rgba(124, 58, 237, 0.1);
        backdrop-filter: blur(4px);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .utv-ai-overlay:hover {
        background: rgba(124, 58, 237, 0.18);
    }
    .utv-ai-overlay > i {
        font-size: 1.5rem;
        color: #7c3aed;
    }
    .utv-ai-overlay > span {
        color: #7c3aed;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .utv-ai-overlay > small {
        color: rgba(124, 58, 237, 0.6);
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
        color: #94a0b8;
        font-size: 0.7rem;
        cursor: pointer;
        transition: color 0.15s, background 0.15s;
    }
    .utv-text-toggle-btn:hover {
        color: #5a6178;
        background: #f5f7fa;
    }
    .utv-text-toggle-btn.active {
        color: #0891b2;
        background: rgba(3, 252, 244, 0.08);
    }
    /* Expanded scene text */
    .utv-scene-text-full {
        color: var(--at-text-secondary, #5a6178);
        font-size: 0.78rem;
        line-height: 1.5;
        padding: 8px 10px;
        background: #f5f7fa;
        border-radius: 8px;
        border-left: 2px solid rgba(3, 252, 244, 0.4);
    }
    /* Search filter pills */
    .utv-search-filter {
        display: flex;
        gap: 2px;
        background: #f5f7fa;
        border-radius: 6px;
        padding: 2px;
        flex-shrink: 0;
    }
    .utv-filter-pill {
        padding: 3px 8px;
        border-radius: 4px;
        border: none;
        background: transparent;
        color: #94a0b8;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .utv-filter-pill:hover {
        color: #5a6178;
    }
    .utv-filter-pill.active {
        background: rgba(3, 252, 244, 0.12);
        color: #0891b2;
    }
    .utv-suggestion-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 20px;
        border: 1px solid rgba(3, 252, 244, 0.3);
        background: transparent;
        color: #0891b2;
        font-size: 0.72rem;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .utv-suggestion-chip:hover {
        background: rgba(3, 252, 244, 0.06);
        border-color: #03fcf4;
    }
    .utv-crop-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 22px;
        height: 22px;
        border-radius: 4px;
        border: none;
        background: rgba(3, 252, 244, 0.9);
        color: #0a2e2e;
        font-size: 0.55rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        transition: background 0.15s;
    }
    .utv-crop-btn:hover {
        background: #03fcf4;
    }
    .utv-crop-dialog {
        background: #ffffff;
        border: 1px solid #eef1f5;
        border-radius: 16px;
        padding: 24px;
        width: 520px;
        max-width: 90vw;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .utv-crop-container {
        position: relative;
        width: 100%;
        height: 320px;
        background: #f5f7fa;
        border-radius: 10px;
        overflow: hidden;
    }
    .utv-crop-frame {
        position: absolute;
        border: 2px solid #03fcf4;
        border-radius: 4px;
        cursor: move;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);
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
        background: #eef1f5;
        border-radius: 3px;
        outline: none;
    }
    .utv-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #03fcf4;
        cursor: pointer;
        border: 2px solid #ffffff;
    }
    .utv-range-slider::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #03fcf4;
        cursor: pointer;
        border: 2px solid #ffffff;
    }
    .utv-crop-frame-label {
        position: absolute;
        top: 4px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.6rem;
        font-weight: 600;
        color: #0891b2;
        background: rgba(255,255,255,0.9);
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

    /* ═══════════════════════════════════════════════════════════ */
    /* AI Studio styles                                           */
    /* ═══════════════════════════════════════════════════════════ */
    .utv-studio-scene {
        transition: all 0.2s;
    }
    .utv-studio-scene:hover {
        border-color: #7c3aed40 !important;
        background: #faf8ff !important;
    }
    .utv-studio-scene.active {
        border-color: #7c3aed60 !important;
        background: #f8f5ff !important;
    }
    .utv-studio-prompt:hover {
        border-color: #d0d5dd !important;
        background: #eef1f5 !important;
    }
    .utv-studio-gen-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #7c3aed30;
        background: rgba(124, 58, 237, 0.05);
        color: #7c3aed;
        font-size: 0.72rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .utv-studio-gen-btn:hover:not(:disabled) {
        background: rgba(124, 58, 237, 0.12);
        border-color: #7c3aed50;
    }
    .utv-studio-gen-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .utv-studio-gen-btn--video {
        border-color: #d9770630;
        background: rgba(217, 119, 6, 0.05);
        color: #d97706;
    }
    .utv-studio-gen-btn--video:hover:not(:disabled) {
        background: rgba(217, 119, 6, 0.12);
        border-color: #d9770650;
    }
    .utv-studio-gen-btn i {
        font-size: 0.72rem;
    }
    .utv-studio-left::-webkit-scrollbar {
        width: 4px;
    }
    .utv-studio-left::-webkit-scrollbar-thumb {
        background: #d0d5dd;
        border-radius: 2px;
    }
    .utv-studio-left::-webkit-scrollbar-track {
        background: transparent;
    }
    .utv-studio-right::-webkit-scrollbar {
        width: 4px;
    }
    .utv-studio-right::-webkit-scrollbar-thumb {
        background: #d0d5dd;
        border-radius: 2px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endif
