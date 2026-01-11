{{-- AI Edit with Mask Modal --}}
@if($showAIEditModal)
{{-- Define the Alpine component BEFORE it's used --}}
<script>
// Only define if not already defined (prevents duplicate definitions)
if (typeof window.aiEditCanvas === 'undefined') {
    window.aiEditCanvas = function() {
        return {
            canvas: null,
            ctx: null,
            isDrawing: false,
            brushSize: 30,
            isApplying: false,

            initCanvas() {
                this.$nextTick(() => {
                    this.canvas = document.getElementById('edit-mask-canvas');
                    if (this.canvas) {
                        this.ctx = this.canvas.getContext('2d');
                    }
                    // Sync brush size with Livewire
                    if (this.$wire && this.$wire.aiEditBrushSize) {
                        this.brushSize = this.$wire.aiEditBrushSize;
                    }
                });
            },

            onImageLoad() {
                const img = document.getElementById('edit-source-image');
                if (img && this.canvas) {
                    this.canvas.width = img.naturalWidth;
                    this.canvas.height = img.naturalHeight;
                    this.clearMask();
                }
            },

            getCoordinates(e) {
                const rect = this.canvas.getBoundingClientRect();
                const scaleX = this.canvas.width / rect.width;
                const scaleY = this.canvas.height / rect.height;

                if (e.touches) {
                    return {
                        x: (e.touches[0].clientX - rect.left) * scaleX,
                        y: (e.touches[0].clientY - rect.top) * scaleY
                    };
                }
                return {
                    x: (e.clientX - rect.left) * scaleX,
                    y: (e.clientY - rect.top) * scaleY
                };
            },

            startDrawing(e) {
                this.isDrawing = true;
                const coords = this.getCoordinates(e);
                this.ctx.beginPath();
                this.ctx.moveTo(coords.x, coords.y);
            },

            draw(e) {
                if (!this.isDrawing) return;

                const coords = this.getCoordinates(e);

                this.ctx.lineTo(coords.x, coords.y);
                this.ctx.strokeStyle = 'rgba(236, 72, 153, 0.5)';
                this.ctx.lineWidth = this.brushSize;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.stroke();

                // Also draw a filled circle for better coverage
                this.ctx.beginPath();
                this.ctx.arc(coords.x, coords.y, this.brushSize / 2, 0, Math.PI * 2);
                this.ctx.fillStyle = 'rgba(236, 72, 153, 0.5)';
                this.ctx.fill();

                this.ctx.beginPath();
                this.ctx.moveTo(coords.x, coords.y);
            },

            stopDrawing() {
                this.isDrawing = false;
            },

            clearMask() {
                if (this.ctx && this.canvas) {
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                }
            },

            getMaskData() {
                if (!this.canvas) return '';

                // Create a black and white mask
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = this.canvas.width;
                tempCanvas.height = this.canvas.height;
                const tempCtx = tempCanvas.getContext('2d');

                // Fill with black (areas to keep)
                tempCtx.fillStyle = 'black';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

                // Get the drawn areas and make them white (areas to edit)
                const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const maskData = tempCtx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);

                for (let i = 0; i < imageData.data.length; i += 4) {
                    // If there's any paint (alpha > 0), make it white
                    if (imageData.data[i + 3] > 0) {
                        maskData.data[i] = 255;     // R
                        maskData.data[i + 1] = 255; // G
                        maskData.data[i + 2] = 255; // B
                        maskData.data[i + 3] = 255; // A
                    }
                }

                tempCtx.putImageData(maskData, 0, 0);
                return tempCanvas.toDataURL('image/png');
            },

            async applyEdit() {
                if (this.isApplying) return;

                this.isApplying = true;
                const maskData = this.getMaskData();

                try {
                    await this.$wire.applyAIEdit(maskData);
                } finally {
                    this.isApplying = false;
                }
            }
        };
    };
}
</script>

<div class="vw-modal-overlay"
     x-data="aiEditCanvas()"
     x-init="initCanvas()"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(236,72,153,0.3); border-radius: 1rem; width: 100%; max-width: 900px; max-height: 95vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">{{ __('AI Edit') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Paint over areas you want to change, then describe the edit') }}</p>
            </div>
            <button type="button" wire:click="closeAIEditModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
            @php
                $storyboardScene = $storyboard['scenes'][$aiEditSceneIndex] ?? null;
            @endphp

            {{-- Canvas Container --}}
            <div style="flex: 1; display: flex; gap: 1rem;">
                {{-- Image/Canvas Area --}}
                <div style="flex: 2; position: relative;">
                    <div style="position: relative; border-radius: 0.5rem; overflow: hidden; background: rgba(0,0,0,0.5);">
                        @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                            {{-- Original Image --}}
                            <img id="edit-source-image"
                                 src="{{ $storyboardScene['imageUrl'] }}"
                                 alt="Scene {{ $aiEditSceneIndex + 1 }}"
                                 style="width: 100%; display: block;"
                                 crossorigin="anonymous"
                                 @load="onImageLoad()">
                            {{-- Mask Canvas Overlay --}}
                            <canvas id="edit-mask-canvas"
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: crosshair;"
                                    @mousedown="startDrawing($event)"
                                    @mousemove="draw($event)"
                                    @mouseup="stopDrawing()"
                                    @mouseleave="stopDrawing()"
                                    @touchstart.prevent="startDrawing($event)"
                                    @touchmove.prevent="draw($event)"
                                    @touchend="stopDrawing()"></canvas>
                        @else
                            <div style="height: 300px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.5);">
                                {{ __('No image available') }}
                            </div>
                        @endif
                    </div>

                    {{-- Brush Controls --}}
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ __('Brush') }}:</span>
                            <input type="range"
                                   min="10"
                                   max="100"
                                   x-model="brushSize"
                                   wire:model.live="aiEditBrushSize"
                                   style="width: 100px; accent-color: #ec4899;">
                            <span style="color: white; font-size: 0.75rem; min-width: 35px;" x-text="brushSize + 'px'"></span>
                        </div>
                        <button type="button"
                                @click="clearMask()"
                                style="padding: 0.4rem 0.75rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.35rem; color: #ef4444; font-size: 0.75rem; cursor: pointer;">
                            {{ __('Clear Mask') }}
                        </button>
                        <div style="margin-left: auto; display: flex; align-items: center; gap: 0.35rem;">
                            <div style="width: 12px; height: 12px; background: rgba(236,72,153,0.5); border-radius: 50%;"></div>
                            <span style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">{{ __('Painted area will be edited') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Edit Controls --}}
                <div style="flex: 1; display: flex; flex-direction: column; gap: 1rem;">
                    {{-- Edit Prompt --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ __('What do you want to change?') }}</label>
                        <textarea wire:model.live="aiEditPrompt"
                                  placeholder="{{ __('Describe your desired changes...') }}"
                                  style="width: 100%; height: 100px; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; resize: none;"></textarea>
                    </div>

                    {{-- Quick Suggestions --}}
                    <div>
                        <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.75rem; margin-bottom: 0.5rem;">{{ __('Quick Edits') }}</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                            @foreach([
                                'Remove object',
                                'Change background',
                                'Add more light',
                                'Make darker',
                                'Add blur',
                                'Change color to blue',
                                'Add person',
                                'Remove text',
                            ] as $suggestion)
                                <button type="button"
                                        wire:click="$set('aiEditPrompt', '{{ $suggestion }}')"
                                        style="padding: 0.3rem 0.6rem; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.25rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                                    {{ __($suggestion) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tips --}}
                    <div style="background: rgba(236,72,153,0.08); border: 1px solid rgba(236,72,153,0.2); border-radius: 0.5rem; padding: 0.75rem;">
                        <div style="color: #f472b6; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.35rem;">{{ __('Tips') }}</div>
                        <ul style="color: rgba(255,255,255,0.6); font-size: 0.7rem; margin: 0; padding-left: 1rem; line-height: 1.5;">
                            <li>{{ __('Paint over the exact area you want to change') }}</li>
                            <li>{{ __('Be specific in your description') }}</li>
                            <li>{{ __('Use "remove" to delete objects') }}</li>
                            <li>{{ __('Use "add" or "change to" for modifications') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            @if($error)
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; padding: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #ef4444; font-size: 0.85rem;">
                        <span>{{ $error }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <button type="button"
                    wire:click="closeAIEditModal"
                    style="padding: 0.6rem 1rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                {{ __('Cancel') }}
            </button>
            <button type="button"
                    @click="applyEdit()"
                    :disabled="isApplying || !$wire.aiEditPrompt"
                    style="padding: 0.6rem 1.5rem; background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;"
                    :style="(isApplying || !$wire.aiEditPrompt) ? 'opacity: 0.5; cursor: not-allowed;' : ''">
                <span x-show="!isApplying">{{ __('Apply Edit') }}</span>
                <span x-show="isApplying" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Applying...') }}
                </span>
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
@endif
