{{-- Style Picker - Horizontal scrollable row --}}
<div class="mb-4">
    <div class="d-flex align-items-start gap-3 overflow-auto pb-2" style="scrollbar-width: thin; scrollbar-color: #333 transparent;">

        {{-- Add Custom Style (first) --}}
        <div wire:click="openStyleModal"
             class="style-thumb flex-shrink-0 text-center"
             style="width: 76px;">
            <div style="width: 64px; height: 64px; margin: 0 auto; border-radius: 10px; background: #141414; border: 1.5px dashed #333;"
                 class="d-flex align-items-center justify-content-center">
                <i class="fa-light fa-plus" style="font-size: 1.1rem; color: #555;"></i>
            </div>
            <small class="d-block mt-1" style="color: #666; font-size: 0.65rem;">{{ __('Add style') }}</small>
        </div>

        @foreach($styles as $style)
            <div wire:click="selectStyle({{ $style->id }})"
                 class="style-thumb flex-shrink-0 text-center {{ $selectedStyleId === $style->id ? 'selected' : '' }}"
                 style="width: 76px;">
                {{-- Thumbnail --}}
                <div class="position-relative" style="width: 64px; height: 64px; margin: 0 auto; border-radius: 10px; overflow: hidden; background: #141414;">
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
                            <i class="fa-light {{ $icon }}" style="font-size: 1.2rem; color: #444;"></i>
                        </div>
                    @endif

                    @if($selectedStyleId === $style->id)
                        <div class="position-absolute top-0 end-0 m-1">
                            <i class="fa-solid fa-check-circle" style="color: #f97316; font-size: 0.7rem;"></i>
                        </div>
                    @endif
                </div>

                {{-- Label --}}
                <small class="d-block mt-1 text-truncate" style="color: {{ $selectedStyleId === $style->id ? '#f97316' : '#888' }}; font-size: 0.65rem;">
                    {{ $style->name }}
                </small>
            </div>
        @endforeach
    </div>
</div>
