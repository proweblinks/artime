{{-- Custom Style Creation Modal --}}
@if($showStyleModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;"
     wire:click.self="$set('showStyleModal', false)">
    <div class="card border-0" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 16px; width: 480px; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <h5 class="mb-0 fw-bold" style="color: var(--at-text, #1a1a2e);">{{ __('Custom Style') }}</h5>
            <button wire:click="$set('showStyleModal', false)" type="button" class="btn-close"></button>
        </div>

        {{-- Body --}}
        <div class="card-body p-4 pt-2">
            <p class="small mb-3" style="color: var(--at-text-muted, #94a0b8);">
                {{ __('Describe your desired visual style. This instruction will be applied to all generated images.') }}
            </p>

            {{-- Style Name --}}
            <div class="mb-3">
                <label class="form-label small" style="color: var(--at-text-muted, #94a0b8);">{{ __('Style Name') }}</label>
                <input type="text" wire:model.defer="customStyleName"
                       class="form-control border-0"
                       style="background: #f5f7fa; border-radius: 8px; color: var(--at-text, #1a1a2e);"
                       placeholder="{{ __('e.g., Retro Pixel Art') }}">
            </div>

            {{-- Style Instruction --}}
            <div class="mb-3">
                <label class="form-label small" style="color: var(--at-text-muted, #94a0b8);">{{ __('Style Description') }}</label>
                <textarea wire:model.defer="customStyleInstruction"
                          class="form-control border-0"
                          rows="4"
                          style="background: #f5f7fa; border-radius: 8px; resize: vertical; font-size: 0.9rem; color: var(--at-text, #1a1a2e);"
                          placeholder="{{ __('Describe the visual style in detail: colors, textures, rendering technique, artistic references...') }}"></textarea>
            </div>

            {{-- Reference Image Upload --}}
            <div class="mb-3">
                <label class="form-label small" style="color: var(--at-text-muted, #94a0b8);">{{ __('Reference Image (Optional)') }}</label>
                <input type="file" wire:model="customStyleImage"
                       class="form-control border-0"
                       style="background: #f5f7fa; border-radius: 8px; font-size: 0.85rem; color: var(--at-text-secondary, #5a6178);"
                       accept="image/*">
                <small class="d-block mt-1" style="color: var(--at-text-muted, #94a0b8);">{{ __('Upload an image to use as a visual reference for the style.') }}</small>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-0 d-flex gap-3" style="background: transparent;">
            <button wire:click="$set('showStyleModal', false)" type="button"
                    class="btn flex-grow-1" style="background: #f5f7fa; color: var(--at-text-secondary, #5a6178); border-radius: 10px;">
                {{ __('Cancel') }}
            </button>
            <button wire:click="saveCustomStyle" type="button"
                    class="btn flex-grow-1 fw-semibold" style="background: #03fcf4; color: #0a2e2e; border-radius: 10px;">
                <i class="fa-light fa-check me-1"></i>
                {{ __('Apply Style') }}
            </button>
        </div>
    </div>
</div>
@endif
