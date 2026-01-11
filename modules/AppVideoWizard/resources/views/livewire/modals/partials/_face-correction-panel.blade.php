{{-- Face Correction Panel (shown inside Frame Capture modal) --}}
<div style="border-top: 1px solid rgba(255,255,255,0.1); padding: 1rem 1.25rem; background: rgba(0,0,0,0.2);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div style="font-size: 0.9rem; font-weight: 600; color: white; display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">🎭</span> {{ __('Fix Character Faces') }}
        </div>
        <button type="button" wire:click="closeFaceCorrectionPanel" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 1rem;">✕</button>
    </div>

    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem; margin-bottom: 1rem;">
        {{ __('Select characters whose faces need correction. Their Character Bible portraits will be used as reference.') }}
    </div>

    {{-- Character Selection --}}
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
        @foreach($characters as $index => $char)
            @php
                $isSelected = in_array($index, $selectedFaceCorrectionCharacters ?? []);
            @endphp
            <div wire:click="toggleFaceCorrectionCharacter({{ $index }})"
                 style="cursor: pointer; padding: 0.5rem; background: {{ $isSelected ? 'rgba(139, 92, 246, 0.2)' : 'rgba(255,255,255,0.05)' }}; border: 2px solid {{ $isSelected ? 'rgba(139, 92, 246, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.5rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                @if(!empty($char['referenceImage']))
                    <img src="{{ $char['referenceImage'] }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 0.25rem;">
                @else
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 0.25rem; display: flex; align-items: center; justify-content: center;">
                        <span style="color: rgba(255,255,255,0.4);">👤</span>
                    </div>
                @endif
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: white;">{{ $char['name'] ?? 'Character' }}</div>
                    <div style="font-size: 0.6rem; color: {{ $isSelected ? 'rgba(139, 92, 246, 0.8)' : 'rgba(255,255,255,0.3)' }};">
                        {{ $isSelected ? '✓ ' . __('Selected') : __('Click to select') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Before/After Preview --}}
    @if($faceCorrectionStatus === 'done' || $faceCorrectionStatus === 'processing')
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <div style="flex: 1;">
                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); margin-bottom: 0.25rem;">{{ __('BEFORE') }}</div>
                <div style="border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; overflow: hidden; aspect-ratio: 16/9;">
                    @if($capturedFrame)
                        <img src="{{ $capturedFrame }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @endif
                </div>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); margin-bottom: 0.25rem;">{{ __('AFTER') }}</div>
                <div style="border: 1px solid {{ $faceCorrectionStatus === 'done' ? 'rgba(16, 185, 129, 0.5)' : 'rgba(255,255,255,0.2)' }}; border-radius: 0.5rem; overflow: hidden; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                    @if($faceCorrectionStatus === 'processing')
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                            <div style="width: 24px; height: 24px; border: 2px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <span style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">{{ __('Processing...') }}</span>
                        </div>
                    @elseif($correctedFrameUrl)
                        <img src="{{ $correctedFrameUrl }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span style="color: rgba(255,255,255,0.3); font-size: 0.75rem;">{{ __('Click "Apply Correction"') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Progress indicator --}}
    @if($faceCorrectionStatus === 'processing')
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 0.5rem; border: 1px solid rgba(139, 92, 246, 0.3);">
                <div style="width: 20px; height: 20px; border: 2px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="color: white; font-size: 0.85rem;">{{ __('Correcting faces with AI...') }}</div>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div style="display: flex; gap: 0.75rem;">
        <button type="button"
                wire:click="applyFaceCorrection"
                wire:loading.attr="disabled"
                wire:target="applyFaceCorrection"
                @if($faceCorrectionStatus === 'processing') disabled @endif
                style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; {{ $faceCorrectionStatus === 'processing' ? 'opacity: 0.5;' : '' }}">
            <span wire:loading.remove wire:target="applyFaceCorrection">🎨 {{ __('Apply Correction') }}</span>
            <span wire:loading wire:target="applyFaceCorrection">
                <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                </svg>
                {{ __('Processing...') }}
            </span>
        </button>

        <button type="button"
                wire:click="saveCorrectedFrame"
                @if(!$correctedFrameUrl || $faceCorrectionStatus !== 'done') disabled @endif
                style="flex: 1; padding: 0.75rem; background: {{ $correctedFrameUrl && $faceCorrectionStatus === 'done' ? 'linear-gradient(135deg, #10b981, #059669)' : 'rgba(16, 185, 129, 0.2)' }}; border: {{ $correctedFrameUrl && $faceCorrectionStatus === 'done' ? 'none' : '1px solid rgba(16, 185, 129, 0.3)' }}; border-radius: 0.5rem; color: {{ $correctedFrameUrl && $faceCorrectionStatus === 'done' ? 'white' : 'rgba(255,255,255,0.5)' }}; font-weight: 600; cursor: {{ $correctedFrameUrl && $faceCorrectionStatus === 'done' ? 'pointer' : 'not-allowed' }}; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            ✓ {{ __('Save Corrected Frame') }}
        </button>
    </div>
</div>
