@php
    $scene = $this->scene;
    $imageUrl = $this->imageUrl;
    $status = $this->imageStatus;
    $source = $this->imageSource;
    $hasMultiShot = $this->hasMultiShot;
    $decomposed = $this->decomposed;

    // Scene ID for wire:click actions (use sceneId for normalized, index for JSON)
    $sceneIdForAction = $scene['id'] ?? $sceneIndex;
@endphp

<div class="vw-scene-card" wire:key="scene-card-{{ $sceneId }}">
    @if($scene)
        {{-- Floating Toolbar (appears on hover) --}}
        <div class="vw-floating-toolbar">
            @if($imageUrl)
                <button type="button"
                        class="vw-floating-toolbar-btn primary"
                        wire:click="$parent.openImageStudio('scene', {{ $sceneIndex }})"
                        title="{{ __('Edit with AI') }}">
                    {{ __('Edit') }}
                </button>
                <button type="button"
                        class="vw-floating-toolbar-btn"
                        wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                        wire:loading.attr="disabled"
                        title="{{ __('Regenerate') }}">
                    {{ __('Regen') }}
                </button>
                <div class="vw-floating-toolbar-divider"></div>
                <button type="button"
                        class="vw-floating-toolbar-btn"
                        wire:click="$parent.openUpscaleModal({{ $sceneIndex }})"
                        title="{{ __('Upscale') }}">
                    Upscale
                </button>
                <button type="button"
                        class="vw-floating-toolbar-btn"
                        wire:click="$parent.openMultiShotModal({{ $sceneIndex }})"
                        title="{{ __('Multi-shot') }}">
                    Multi-shot
                </button>
                <button type="button"
                        class="vw-floating-toolbar-btn"
                        wire:click="$parent.openStockBrowser({{ $sceneIndex }})"
                        title="{{ __('Stock media') }}">
                    Stock
                </button>
            @else
                <button type="button"
                        class="vw-floating-toolbar-btn primary"
                        wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                        wire:loading.attr="disabled"
                        title="{{ __('Generate with AI') }}">
                    {{ __('Generate') }}
                </button>
                <button type="button"
                        class="vw-floating-toolbar-btn"
                        wire:click="$parent.openStockBrowser({{ $sceneIndex }})"
                        title="{{ __('Browse stock') }}">
                    {{ __('Stock') }}
                </button>
            @endif
        </div>

        {{-- Image Container with Overlays --}}
        <div style="position: relative;">
            {{-- Scene Number Badge - Always visible, top-left --}}
            <div style="position: absolute; top: 0.75rem; left: 0.75rem; background: rgba(0,0,0,0.8); color: white; padding: 0.35rem 0.75rem; border-radius: 0.35rem; font-size: 0.9rem; font-weight: 600; z-index: 10;">
                {{ __('Scene') }} {{ $sceneIndex + 1 }}
            </div>

            {{-- Multi-Shot Badge - Compact, top right --}}
            @if($hasMultiShot && !empty($decomposed['shots']))
                <div style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10; display: flex; align-items: center; gap: 0.35rem;">
                    <span style="background: linear-gradient(135deg, #03fcf4, #06b6d4); color: #0a2e2e; padding: 0.25rem 0.5rem; border-radius: 0.3rem; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                        {{ count($decomposed['shots']) }} shots
                    </span>
                </div>
            @endif

            {{-- Main Image Content Area --}}
            <div class="vw-scene-image-container">
                @if($status === 'generating')
                    {{-- Generation Preview --}}
                    <div class="vw-generation-preview"
                         x-data="{
                             progress: 0,
                             status: '{{ __('Initializing...') }}',
                             stages: [
                                 { p: 10, s: '{{ __('Processing prompt...') }}' },
                                 { p: 25, s: '{{ __('Generating base...') }}' },
                                 { p: 45, s: '{{ __('Adding details...') }}' },
                                 { p: 65, s: '{{ __('Refining image...') }}' },
                                 { p: 85, s: '{{ __('Final touches...') }}' },
                                 { p: 95, s: '{{ __('Almost ready...') }}' }
                             ],
                             init() { this.runProgress(); },
                             async runProgress() {
                                 for (let i = 0; i < this.stages.length; i++) {
                                     await new Promise(r => setTimeout(r, 2000 + Math.random() * 1500));
                                     this.progress = this.stages[i].p;
                                     this.status = this.stages[i].s;
                                 }
                             }
                         }"
                         style="height: 220px; background: linear-gradient(135deg, rgba(3,252,244,0.1), rgba(6,182,212,0.1)); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; inset: 0; background: linear-gradient(45deg, rgba(3,252,244,0.1), rgba(6,182,212,0.1), rgba(236,72,153,0.1)); background-size: 400% 400%; animation: vw-gradient-shift 4s ease infinite;"></div>
                        <div style="position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, rgba(3,252,244,0.3), rgba(6,182,212,0.3)); display: flex; align-items: center; justify-content: center; animation: vw-pulse 1.5s ease-in-out infinite;">
                                <span style="font-size: 1.5rem;">AI</span>
                            </div>
                            <div style="width: 180px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                <div :style="'width: ' + progress + '%'" style="height: 100%; background: linear-gradient(90deg, #03fcf4, #06b6d4); border-radius: 3px; transition: width 0.5s ease;"></div>
                            </div>
                            <div x-text="status" style="font-size: 0.85rem; color: white; font-weight: 500;"></div>
                        </div>
                        <button type="button"
                                wire:click="$parent.cancelImageGeneration({{ $sceneIndex }})"
                                wire:confirm="{{ __('Cancel this generation?') }}"
                                style="position: absolute; bottom: 0.75rem; right: 0.75rem; padding: 0.35rem 0.75rem; border-radius: 0.35rem; border: 1px solid rgba(239,68,68,0.4); background: rgba(239,68,68,0.15); color: #f87171; cursor: pointer; font-size: 0.7rem; z-index: 10;">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                @elseif($imageUrl)
                    {{-- Image Ready --}}
                    <img src="{{ $imageUrl }}"
                         alt="Scene {{ $sceneIndex + 1 }}"
                         class="vw-scene-image"
                         loading="lazy"
                         data-scene-id="{{ $sceneIdForAction }}"
                         data-retry-count="0"
                         onload="this.dataset.loaded='true';"
                         onerror="this.onerror=null; this.style.display='none'; this.parentElement.querySelector('.vw-image-placeholder').style.display='flex';">
                    <div class="vw-image-placeholder" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); gap: 0.5rem;">
                        <span style="font-size: 1.5rem;">Image</span>
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __('Image not available') }}</span>
                        <button type="button"
                                wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                                style="padding: 0.3rem 0.6rem; border-radius: 0.3rem; border: 1px solid rgba(3,252,244,0.5); background: rgba(3,252,244,0.3); color: white; cursor: pointer; font-size: 0.65rem;">
                            {{ __('Regenerate') }}
                        </button>
                    </div>

                    @php
                        $isVideo = $source === 'stock-video';
                        $sourceBgColor = $source === 'stock' ? 'rgba(16,185,129,0.9)' : ($isVideo ? 'rgba(6,182,212,0.9)' : 'rgba(3,252,244,0.9)');
                        $sourceLabel = $source === 'stock' ? __('Stock') : ($isVideo ? __('Video') : __('AI'));
                    @endphp

                    {{-- Video Play Icon Overlay --}}
                    @if($isVideo)
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 48px; height: 48px; background: rgba(0,0,0,0.6); border-radius: 50%; display: flex; align-items: center; justify-content: center; pointer-events: none; z-index: 5;">
                            <div style="width: 0; height: 0; border-left: 14px solid white; border-top: 8px solid transparent; border-bottom: 8px solid transparent; margin-left: 3px;"></div>
                        </div>
                    @endif

                    {{-- Source Badge - Below scene number --}}
                    <div style="position: absolute; top: 3rem; left: 0.75rem; background: {{ $sourceBgColor }}; color: white; padding: 0.3rem 0.6rem; border-radius: 0.3rem; font-size: 0.8rem; z-index: 10;">
                        {{ $sourceLabel }}
                    </div>

                    {{-- Action Buttons Overlay - Bottom of image --}}
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 1rem; display: flex; gap: 0.6rem; z-index: 10;">
                        <button type="button"
                                wire:click="$parent.openImageStudio('scene', {{ $sceneIndex }})"
                                style="flex: 1; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(236,72,153,0.5); background: linear-gradient(135deg, rgba(236,72,153,0.3), rgba(3,252,244,0.3)); color: white; cursor: pointer; font-size: 0.85rem;"
                                title="{{ __('Edit with AI') }}">
                            {{ __('Edit') }}
                        </button>
                        <button type="button"
                                wire:click="$parent.openEditPromptModal({{ $sceneIndex }})"
                                style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.85rem;"
                                title="{{ __('Modify prompt') }}">
                            Prompt
                        </button>
                        <button type="button"
                                wire:click="$parent.openStockBrowser({{ $sceneIndex }})"
                                style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(16,185,129,0.5); background: rgba(16,185,129,0.2); color: white; cursor: pointer; font-size: 0.85rem;"
                                title="{{ __('Browse stock media') }}">
                            Stock
                        </button>
                        <button type="button"
                                wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                                wire:loading.attr="disabled"
                                style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.85rem;"
                                title="{{ __('Regenerate with AI') }}">
                            Regen
                        </button>
                        <button type="button"
                                wire:click="$parent.openMultiShotModal({{ $sceneIndex }})"
                                wire:loading.attr="disabled"
                                style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(3,252,244,0.6); background: linear-gradient(135deg, rgba(3,252,244,0.4), rgba(6,182,212,0.3)); color: white; cursor: pointer; font-size: 0.85rem; font-weight: 600;"
                                title="{{ __('Multi-shot decomposition') }}">
                            Shots
                        </button>
                    </div>
                @elseif($status === 'error')
                    {{-- Error State --}}
                    <div style="height: 220px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <span style="font-size: 1.25rem;">Warning</span>
                            <span style="color: #ef4444; font-size: 0.9rem;">{{ __('Generation failed') }}</span>
                        </div>
                        <div style="display: flex; gap: 0.75rem; width: 100%; max-width: 320px;">
                            <button type="button"
                                    wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                                    wire:loading.attr="disabled"
                                    style="flex: 1; padding: 0.75rem 0.5rem; background: linear-gradient(135deg, rgba(3,252,244,0.3), rgba(6,182,212,0.3)); border: 1px solid rgba(3,252,244,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                <span>{{ __('Retry AI') }}</span>
                            </button>
                            <button type="button"
                                    wire:click="$parent.openStockBrowser({{ $sceneIndex }})"
                                    style="flex: 1; padding: 0.75rem 0.5rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                <span>{{ __('Use Stock') }}</span>
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Empty/Pending State --}}
                    <div wire:loading
                         wire:target="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                         style="display: none;">
                        <div class="vw-spinner"></div>
                        <span class="vw-generating-text">{{ __('Generating...') }}</span>
                    </div>
                    <div class="vw-scene-empty"
                         wire:loading.remove
                         wire:target="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')">
                        <div class="vw-empty-center">
                            <div class="vw-empty-icon-float">Scene</div>
                            <div class="vw-scene-empty-text">{{ __('Choose image source') }}</div>
                            <div class="vw-scene-empty-buttons">
                                <button type="button"
                                        class="vw-scene-empty-btn ai"
                                        wire:click="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')">
                                    <span class="vw-scene-empty-btn-icon" wire:loading.remove wire:target="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')">AI</span>
                                    <span class="vw-scene-empty-btn-label" wire:loading.remove wire:target="$parent.generateImage({{ $sceneIndex }}, '{{ $sceneIdForAction }}')">{{ __('AI Generate') }}</span>
                                </button>
                                <button type="button"
                                        class="vw-scene-empty-btn stock"
                                        wire:click="$parent.openStockBrowser({{ $sceneIndex }})">
                                    <span class="vw-scene-empty-btn-icon">Stock</span>
                                    <span class="vw-scene-empty-btn-label">{{ __('Stock Media') }}</span>
                                    <span class="vw-scene-empty-btn-cost">{{ __('FREE') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Multi-Shot Timeline (if decomposed) --}}
        @if($hasMultiShot && !empty($decomposed['shots']))
            <div wire:key="multi-shot-timeline-{{ $sceneIndex }}"
                 x-data="{ expanded: false }"
                 style="padding: 0.4rem 0.5rem; border-top: 1px solid rgba(3,252,244,0.15); background: rgba(3,252,244,0.04);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                        <button type="button" @click="expanded = !expanded" style="background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.6); font-size: 0.6rem; padding: 0;">
                            <span x-text="expanded ? 'v' : '>'"></span>
                        </button>
                        <span style="font-size: 0.55rem; color: rgba(255,255,255,0.5); font-weight: 600;">
                            {{ count($decomposed['shots']) }} {{ __('shots') }}
                        </span>
                    </div>
                    <button type="button"
                            wire:click="$parent.openMultiShotModal({{ $sceneIndex }})"
                            wire:loading.attr="disabled"
                            class="vw-edit-shots-btn">
                        {{ __('Edit Shots') }}
                    </button>
                </div>
                <div x-show="expanded" x-transition style="overflow: hidden;">
                    <div style="display: flex; flex-direction: row; gap: 0.25rem; overflow-x: auto; padding: 0.25rem 0;">
                        @foreach($decomposed['shots'] as $shotIdx => $shot)
                            @php
                                $shotStatus = $shot['imageStatus'] ?? $shot['status'] ?? 'pending';
                                $hasImage = $shotStatus === 'ready' && !empty($shot['imageUrl']);
                            @endphp
                            <div wire:key="shot-thumb-{{ $sceneIndex }}-{{ $shotIdx }}"
                                 style="cursor: pointer; position: relative; border-radius: 0.35rem; overflow: hidden; border: 2px solid {{ $hasImage ? 'rgba(16,185,129,0.5)' : 'rgba(255,255,255,0.1)' }}; flex-shrink: 0; width: 90px;"
                                 wire:click="$parent.openMultiShotModal({{ $sceneIndex }})">
                                <div style="aspect-ratio: 16/10; position: relative;">
                                    @if($hasImage)
                                        <img src="{{ $shot['imageUrl'] }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03);">
                                            <span style="font-size: 1rem;">#{{ $shotIdx + 1 }}</span>
                                        </div>
                                    @endif
                                    <div style="position: absolute; top: 2px; left: 2px; background: rgba(0,0,0,0.75); color: white; padding: 0.1rem 0.25rem; border-radius: 0.15rem; font-size: 0.5rem; font-weight: 600;">
                                        #{{ $shotIdx + 1 }}
                                    </div>
                                    @if(isset($shot['duration']))
                                        <div style="position: absolute; bottom: 2px; right: 2px; background: rgba(0,0,0,0.8); color: white; padding: 0.05rem 0.2rem; border-radius: 0.1rem; font-size: 0.45rem;">
                                            {{ $shot['duration'] }}s
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Narration/Dialogue Info --}}
        @php
            $speechSegments = $scene['speechSegments'] ?? [];
            $narration = $scene['narration'] ?? '';
            $totalSegments = count($speechSegments);
        @endphp

        @if(!empty($speechSegments) || !empty($narration))
            <div style="padding: 0.3rem 0.75rem;">
                <div class="vw-scene-dialogue" style="display: flex; justify-content: space-between; align-items: center;">
                    <button
                        type="button"
                        wire:click="$parent.openSceneTextInspector({{ $sceneIndex }})"
                        class="vw-voice-types-btn"
                        style="display: flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.5rem; background: linear-gradient(135deg, rgba(3,252,244,0.2), rgba(6,182,212,0.15)); border: 1px solid rgba(3,252,244,0.4); border-radius: 0.3rem; cursor: pointer; transition: all 0.2s;"
                    >
                        <span style="font-size: 0.75rem;">Voice</span>
                        <span style="font-weight: 600; font-size: 0.7rem; color: white;">{{ __('Voice Types') }}</span>
                        @if($totalSegments > 0)
                            <span style="opacity: 0.6; font-size: 0.6rem; color: rgba(255,255,255,0.7);">({{ $totalSegments }})</span>
                        @endif
                    </button>
                    <button
                        type="button"
                        wire:click="$parent.openSceneTextInspector({{ $sceneIndex }})"
                        class="vw-inspect-btn"
                        title="{{ __('Full scene details') }}"
                        style="background: rgba(3, 252, 244, 0.15); border: 1px solid rgba(3, 252, 244, 0.3); color: #67e8f9; padding: 0.1rem 0.3rem; border-radius: 0.2rem; font-size: 0.55rem; cursor: pointer;"
                    >
                        {{ __('Inspect') }}
                    </button>
                </div>
            </div>
        @endif
    @else
        {{-- Scene data unavailable --}}
        <div style="height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem;">
            <span style="font-size: 1.25rem; margin-bottom: 0.5rem;">Error</span>
            <span style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">{{ __('Scene data unavailable') }}</span>
        </div>
    @endif
</div>
