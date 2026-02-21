{{-- Shot Face Correction Modal --}}
@if($showShotFaceCorrectionModal)
<div class="sfc-overlay" wire:click.self="closeShotFaceCorrectionModal">
    <div class="sfc-modal">
        {{-- Header --}}
        <div class="sfc-header">
            <div class="sfc-title">
                <svg class="sfc-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ __('Face Correction') }}</span>
            </div>
            <button wire:click="closeShotFaceCorrectionModal" class="sfc-close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="sfc-content">
            <div class="sfc-grid">
                {{-- Left: Image Previews --}}
                <div class="sfc-images">
                    <div class="sfc-image-section">
                        <h3 class="sfc-section-title">{{ __('Original Shot') }}</h3>
                        @if($shotFaceCorrectionData['originalImageUrl'])
                        <div class="sfc-image-frame">
                            <img src="{{ $shotFaceCorrectionData['originalImageUrl'] }}" alt="{{ __('Original shot') }}">
                        </div>
                        @endif
                    </div>

                    @if($shotFaceCorrectionData['correctedImageUrl'])
                    <div class="sfc-image-section">
                        <h3 class="sfc-section-title sfc-success">{{ __('Corrected Result') }}</h3>
                        <div class="sfc-image-frame sfc-corrected">
                            <img src="{{ $shotFaceCorrectionData['correctedImageUrl'] }}" alt="{{ __('Corrected shot') }}">
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right: Character Selection --}}
                <div class="sfc-controls">
                    <h3 class="sfc-section-title">{{ __('Select Characters to Correct') }}</h3>
                    <p class="sfc-hint">{{ __('Choose which character faces should be corrected using their Bible portraits.') }}</p>

                    <div class="sfc-char-list">
                        @foreach($shotFaceCorrectionData['availableCharacters'] ?? [] as $char)
                            @php
                                $isSelected = in_array($char['index'], $shotFaceCorrectionData['selectedCharacters'] ?? []);
                                $charData = $sceneMemory['characterBible']['characters'][$char['index']] ?? [];
                                $thumbSrc = !empty($charData['referenceImageBase64'])
                                    ? 'data:image/png;base64,' . $charData['referenceImageBase64']
                                    : (!empty($charData['referenceImage']) ? $charData['referenceImage'] : null);
                            @endphp
                            <label class="sfc-char-item {{ $isSelected ? 'selected' : '' }}" wire:click="toggleShotFaceCorrectionCharacter({{ $char['index'] }})">
                                <div class="sfc-checkbox {{ $isSelected ? 'checked' : '' }}">
                                    @if($isSelected)
                                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>

                                @if($thumbSrc)
                                <img src="{{ $thumbSrc }}" class="sfc-char-thumb" alt="{{ $char['name'] }}">
                                @else
                                <div class="sfc-char-placeholder">{{ substr($char['name'], 0, 1) }}</div>
                                @endif

                                <div class="sfc-char-info">
                                    <span class="sfc-char-name">{{ $char['name'] }}</span>
                                    @if($char['inScene'])
                                    <span class="sfc-in-scene">{{ __('In this scene') }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach

                        @if(empty($shotFaceCorrectionData['availableCharacters']))
                        <div class="sfc-no-chars">
                            <p>{{ __('No characters with reference portraits found.') }}</p>
                            <p>{{ __('Generate character portraits in the Character Bible first.') }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Error Message --}}
                    @if($shotFaceCorrectionData['error'])
                    <div class="sfc-error">
                        {{ $shotFaceCorrectionData['error'] }}
                    </div>
                    @endif

                    {{-- Processing Status --}}
                    @if($shotFaceCorrectionData['status'] === 'processing')
                    <div class="sfc-processing">
                        <div class="sfc-spinner"></div>
                        <span>{{ __('Applying face correction...') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="sfc-footer">
            <button wire:click="closeShotFaceCorrectionModal" class="sfc-btn sfc-btn-secondary">
                {{ __('Cancel') }}
            </button>

            @if($shotFaceCorrectionData['status'] === 'ready')
            <button wire:click="saveCorrectedShot" class="sfc-btn sfc-btn-success">
                {{ __('Save Corrected Shot') }}
            </button>
            @else
            <button wire:click="applyFaceCorrectionToShot"
                    {{ empty($shotFaceCorrectionData['selectedCharacters']) || $shotFaceCorrectionData['status'] === 'processing' ? 'disabled' : '' }}
                    class="sfc-btn sfc-btn-primary">
                <span wire:loading.remove wire:target="applyFaceCorrectionToShot">{{ __('Apply Face Correction') }}</span>
                <span wire:loading wire:target="applyFaceCorrectionToShot">{{ __('Processing...') }}</span>
            </button>
            @endif
        </div>
    </div>
</div>

<style>
/* Shot Face Correction Modal Styles */
.sfc-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000400; /* Popup-within-modal layer */
    backdrop-filter: blur(4px);
}

.sfc-modal {
    background: linear-gradient(145deg, #1a1a2e 0%, #16162a 100%);
    border-radius: 1rem;
    max-width: 900px;
    width: 95%;
    max-height: 90vh;
    overflow: hidden;
    border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3), 0 0 40px rgba(var(--vw-primary-rgb), 0.06);
}

.sfc-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--vw-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.2);
}

.sfc-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: #d97706;
}

.sfc-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: #d97706;
}

.sfc-close {
    padding: 0.5rem;
    background: transparent;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.sfc-close:hover {
    background: var(--vw-border);
        color: var(--vw-text);
}

.sfc-close svg {
    width: 1.25rem;
    height: 1.25rem;
}

.sfc-content {
    padding: 1.5rem;
    overflow-y: auto;
    max-height: calc(90vh - 140px);
}

.sfc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .sfc-grid {
        grid-template-columns: 1fr;
    }
}

.sfc-images {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.sfc-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #9ca3af;
    margin-bottom: 0.75rem;
}

.sfc-section-title.sfc-success {
    color: #10b981;
}

.sfc-image-frame {
    position: relative;
    aspect-ratio: 16 / 9;
    background: #0d0d14;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--vw-border);
}

.sfc-image-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sfc-image-frame.sfc-corrected {
    border: 2px solid rgba(16, 185, 129, 0.5);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
}

.sfc-controls {
    display: flex;
    flex-direction: column;
}

.sfc-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.sfc-char-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.sfc-char-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(0,0,0,0.02);
    border: 1px solid rgba(0,0,0,0.04);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.sfc-char-item:hover {
    background: rgba(0,0,0,0.03);
    border-color: var(--vw-border);
}

.sfc-char-item.selected {
    background: rgba(var(--vw-primary-rgb), 0.06);
    border-color: var(--vw-border-focus);
}

.sfc-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--vw-text-secondary);
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.sfc-checkbox.checked {
    background: var(--vw-primary);
    border-color: var(--vw-primary);
}

.sfc-checkbox svg {
    width: 0.875rem;
    height: 0.875rem;
        color: var(--vw-text);
}

.sfc-char-thumb {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--vw-border);
}

.sfc-char-placeholder {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: var(--vw-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
        color: var(--vw-text);
}

.sfc-char-info {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.sfc-char-name {
    font-weight: 600;
        color: var(--vw-text);
    font-size: 0.875rem;
}

.sfc-in-scene {
    font-size: 0.7rem;
    color: #10b981;
}

.sfc-no-chars {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
    background: rgba(0,0,0,0.02);
    border-radius: 0.75rem;
    border: 1px dashed var(--vw-border);
}

.sfc-no-chars p:first-child {
    color: #9ca3af;
    margin-bottom: 0.5rem;
}

.sfc-error {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.5rem;
    color: #dc2626;
    font-size: 0.875rem;
}

.sfc-processing {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: rgba(var(--vw-primary-rgb), 0.06);
    border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
    border-radius: 0.5rem;
    color: var(--vw-text-secondary);
    font-size: 0.875rem;
}

.sfc-spinner {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid rgba(var(--vw-primary-rgb), 0.12);
    border-top-color: var(--vw-primary);
    border-radius: 50%;
    animation: sfc-spin 0.8s linear infinite;
}

@keyframes sfc-spin {
    to { transform: rotate(360deg); }
}

.sfc-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--vw-border);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    background: rgba(0, 0, 0, 0.2);
}

.sfc-btn {
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.sfc-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sfc-btn-secondary {
    background: var(--vw-border);
    color: #d1d5db;
}

.sfc-btn-secondary:hover:not(:disabled) {
    background: var(--vw-border);
}

.sfc-btn-primary {
    background: var(--vw-primary);
        color: var(--vw-text);
}

.sfc-btn-primary:hover:not(:disabled) {
    box-shadow: 0 4px 15px var(--vw-border-accent);
    transform: translateY(-1px);
}

.sfc-btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
        color: var(--vw-text);
}

.sfc-btn-success:hover:not(:disabled) {
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}
</style>
@endif
