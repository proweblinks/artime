{{-- Image Selection Modal for Real Images Mode --}}
@if($showImageSelectionModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10100;"
     x-data="{
         searchInputs: {},
         showSearch: {},
         toggleSearch(sceneId) {
             this.showSearch[sceneId] = !this.showSearch[sceneId];
         },
         submitSearch(sceneId) {
             const query = this.searchInputs[sceneId] || '';
             if (query.trim().length >= 2) {
                 $wire.searchMoreImages(sceneId, query.trim());
                 this.showSearch[sceneId] = false;
                 this.searchInputs[sceneId] = '';
             }
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
                <small style="color: #999;">{{ __('Choose a real image for each scene, or let AI generate one') }}</small>
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
                @endphp

                <div class="utv-scene-row mb-4">
                    {{-- Scene header --}}
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge" style="background: #2a2a2a; color: #f97316; font-size: 0.7rem; font-weight: 600;">
                                    {{ __('Scene') }} {{ $sceneIndex + 1 }}
                                </span>
                                @if($isAI)
                                    <span class="badge" style="background: #7c3aed20; color: #a78bfa; font-size: 0.65rem;">
                                        <i class="fa-light fa-wand-magic-sparkles me-1"></i>{{ __('AI will generate') }}
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
                            </div>
                            <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 0.8rem; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                {{ Str::limit($sceneText, 180) }}
                            </p>
                        </div>

                        {{-- Action buttons --}}
                        <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-3">
                            <button wire:click="markSceneForAI('{{ $sceneId }}')" type="button"
                                    class="utv-img-action-btn {{ $isAI ? 'active-ai' : '' }}"
                                    title="{{ __('Use AI Image') }}">
                                <i class="fa-light fa-wand-magic-sparkles"></i>
                            </button>
                            <button @click="toggleSearch('{{ $sceneId }}')" type="button"
                                    class="utv-img-action-btn"
                                    title="{{ __('Search More') }}">
                                <i class="fa-light fa-magnifying-glass"></i>
                            </button>
                            <label class="utv-img-action-btn mb-0" title="{{ __('Upload Image') }}" style="cursor: pointer;">
                                <i class="fa-light fa-cloud-arrow-up"></i>
                                <input type="file" accept="image/*" class="d-none"
                                       wire:model="uploadedSceneImage"
                                       x-on:change="$wire.set('uploadTargetScene', '{{ $sceneId }}')">
                            </label>
                        </div>
                    </div>

                    {{-- Search input (toggled) --}}
                    <div x-show="showSearch['{{ $sceneId }}']" x-cloak class="mb-2">
                        <div class="d-flex gap-2">
                            <input type="text"
                                   x-model="searchInputs['{{ $sceneId }}']"
                                   @keydown.enter="submitSearch('{{ $sceneId }}')"
                                   class="form-control form-control-sm border-0 text-white"
                                   style="background: #2a2a2a; border-radius: 8px; font-size: 0.82rem;"
                                   placeholder="{{ __('Search Wikimedia Commons...') }}">
                            <button @click="submitSearch('{{ $sceneId }}')" type="button"
                                    class="btn btn-sm" style="background: #f97316; color: #fff; border-radius: 8px; white-space: nowrap;">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </div>

                    {{-- Image thumbnails row --}}
                    @if(!empty($candidates))
                        <div class="utv-thumb-row-wrap">
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
                                    <button wire:click="selectSceneImage('{{ $sceneId }}', {{ $idx }})"
                                            type="button"
                                            class="utv-image-thumb {{ $isSelected ? 'selected' : '' }}">
                                        <img src="{{ $candidate['thumbnail'] ?? $candidate['url'] }}"
                                             alt="{{ $candidate['title'] ?? 'Image ' . ($idx + 1) }}"
                                             loading="lazy"
                                             draggable="false">
                                        @if($isSelected)
                                            <div class="utv-thumb-check">
                                                <i class="fa-solid fa-check"></i>
                                            </div>
                                        @endif
                                        <span class="utv-source-badge">
                                            {{ $candidate['source'] === 'article' ? __('Article') : __('Wiki') }}
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

                    {{-- Search suggestion chips --}}
                    @if(!empty($sceneSearchSuggestions[$sceneId] ?? []))
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                            <span style="color: #666; font-size: 0.72rem;">{{ __('Try:') }}</span>
                            @foreach($sceneSearchSuggestions[$sceneId] as $suggestion)
                                <button wire:click="searchMoreImages('{{ $sceneId }}', '{{ addslashes($suggestion) }}')"
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
                $aiCount = 0;
                $unselected = 0;
                foreach ($selectedSceneImages as $sid => $sel) {
                    if ($sel === 'ai') {
                        $aiCount++;
                    } elseif ($sel !== null) {
                        $realCount++;
                    } else {
                        $unselected++;
                    }
                }
            @endphp
            <div class="d-flex align-items-center gap-3 p-3 mt-2" style="background: #111; border-radius: 10px; font-size: 0.82rem;">
                <span style="color: #ccc;">
                    <i class="fa-light fa-camera me-1" style="color: #22c55e;"></i>
                    {{ $realCount }} {{ __('real') }}
                </span>
                <span style="color: #ccc;">
                    <i class="fa-light fa-wand-magic-sparkles me-1" style="color: #a78bfa;"></i>
                    {{ $aiCount }} {{ __('AI') }}
                </span>
                @if($unselected > 0)
                    <span style="color: #f87171;">
                        <i class="fa-light fa-triangle-exclamation me-1"></i>
                        {{ $unselected }} {{ __('unselected') }}
                    </span>
                @endif
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
    .utv-img-action-btn.active-ai {
        background: #7c3aed30;
        color: #a78bfa;
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
</style>
@endif
