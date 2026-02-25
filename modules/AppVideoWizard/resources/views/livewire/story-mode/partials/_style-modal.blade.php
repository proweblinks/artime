{{-- Custom Style Creation Modal --}}
@if($showStyleModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 10100;"
     wire:click.self="$set('showStyleModal', false)">
    <div class="card border-0" style="background: #1a1a1a; border-radius: 16px; width: 480px;">
        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <h5 class="mb-0 text-white fw-bold">{{ __('Custom Style') }}</h5>
            <button wire:click="$set('showStyleModal', false)" type="button" class="btn-close btn-close-white"></button>
        </div>

        {{-- Body --}}
        <div class="card-body p-4 pt-2">
            <p class="text-muted small mb-3">
                {{ __('Describe your desired visual style. This instruction will be applied to all generated images.') }}
            </p>

            {{-- Style Name --}}
            <div class="mb-3">
                <label class="form-label text-muted small">{{ __('Style Name') }}</label>
                <input type="text" wire:model.defer="customStyleName"
                       class="form-control border-0 text-white"
                       style="background: #2a2a2a; border-radius: 8px;"
                       placeholder="{{ __('e.g., Retro Pixel Art') }}">
            </div>

            {{-- Style Instruction --}}
            <div class="mb-3">
                <label class="form-label text-muted small">{{ __('Style Description') }}</label>
                <textarea wire:model.defer="customStyleInstruction"
                          class="form-control border-0 text-white"
                          rows="4"
                          style="background: #2a2a2a; border-radius: 8px; resize: vertical; font-size: 0.9rem;"
                          placeholder="{{ __('Describe the visual style in detail: colors, textures, rendering technique, artistic references...') }}"></textarea>
            </div>

            {{-- Reference Image Upload --}}
            <div class="mb-3">
                <label class="form-label text-muted small">{{ __('Reference Image (Optional)') }}</label>
                <input type="file" wire:model="customStyleImage"
                       class="form-control border-0 text-muted"
                       style="background: #2a2a2a; border-radius: 8px; font-size: 0.85rem;"
                       accept="image/*">
                <small class="text-muted d-block mt-1">{{ __('Upload an image to use as a visual reference for the style.') }}</small>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-0 d-flex gap-3" style="background: transparent;">
            <button wire:click="$set('showStyleModal', false)" type="button"
                    class="btn flex-grow-1" style="background: #2a2a2a; color: #ccc; border-radius: 10px;">
                {{ __('Cancel') }}
            </button>
            <button wire:click="saveCustomStyle" type="button"
                    class="btn flex-grow-1 fw-semibold" style="background: #f97316; color: #fff; border-radius: 10px;">
                <i class="fa-light fa-check me-1"></i>
                {{ __('Apply Style') }}
            </button>
        </div>
    </div>
</div>
@endif
