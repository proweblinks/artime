{{-- Style Picker - Horizontal scrollable row --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-3 overflow-auto pb-2" style="scrollbar-width: thin; scrollbar-color: #333 transparent;">

        @foreach($styles as $style)
            <div wire:click="selectStyle({{ $style->id }})"
                 class="style-thumb flex-shrink-0 text-center {{ $selectedStyleId === $style->id ? 'selected' : '' }}"
                 style="width: 90px;">
                {{-- Thumbnail --}}
                <div class="position-relative" style="width: 80px; height: 80px; margin: 0 auto; border-radius: 8px; overflow: hidden; background: #2a2a2a;">
                    @if($style->thumbnail_url)
                        <img src="{{ $style->thumbnail_url }}" alt="{{ $style->name }}"
                             class="w-100 h-100" style="object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            @php
                                $iconMap = [
                                    'illustration' => 'fa-pen-nib',
                                    'animation' => 'fa-cube',
                                    'artistic' => 'fa-palette',
                                    'realistic' => 'fa-camera',
                                    'custom' => 'fa-wand-magic',
                                ];
                                $icon = $iconMap[$style->category] ?? 'fa-image';
                            @endphp
                            <i class="fa-light {{ $icon }}" style="font-size: 1.5rem; color: #666;"></i>
                        </div>
                    @endif

                    @if($selectedStyleId === $style->id)
                        <div class="position-absolute top-0 end-0 m-1">
                            <i class="fa-solid fa-check-circle" style="color: #f97316; font-size: 0.8rem;"></i>
                        </div>
                    @endif
                </div>

                {{-- Label --}}
                <small class="d-block mt-1 text-truncate" style="color: {{ $selectedStyleId === $style->id ? '#f97316' : '#aaa' }}; font-size: 0.7rem;">
                    {{ $style->name }}
                </small>
            </div>
        @endforeach

        {{-- Add Custom Style --}}
        <div wire:click="openStyleModal"
             class="style-thumb flex-shrink-0 text-center"
             style="width: 90px;">
            <div style="width: 80px; height: 80px; margin: 0 auto; border-radius: 8px; background: #1f1f1f; border: 2px dashed #444;"
                 class="d-flex align-items-center justify-content-center">
                <i class="fa-light fa-plus" style="font-size: 1.5rem; color: #666;"></i>
            </div>
            <small class="d-block mt-1" style="color: #aaa; font-size: 0.7rem;">{{ __('Add style') }}</small>
        </div>
    </div>
</div>
