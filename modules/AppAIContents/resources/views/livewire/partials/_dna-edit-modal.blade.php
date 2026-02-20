{{-- DNA Edit Modals --}}
@if($editingField)
<div class="cs-modal-overlay" wire:click.self="closeEdit" x-data x-transition>

    <div class="cs-modal" @click.stop>
        <div class="cs-modal-header">
            <h2>
                @switch($editingField)
                    @case('brand_name') {{ __('Edit Brand Name') }} @break
                    @case('tagline') {{ __('Edit Tagline') }} @break
                    @case('business_overview') {{ __('Edit Business Overview') }} @break
                    @case('colors') {{ __('Edit Colors') }} @break
                    @case('fonts') {{ __('Edit Fonts') }} @break
                    @case('brand_values') {{ __('Edit Brand Values') }} @break
                    @case('brand_aesthetic') {{ __('Edit Brand Aesthetic') }} @break
                    @case('brand_tone') {{ __('Edit Tone of Voice') }} @break
                    @case('language') {{ __('Edit Language') }} @break
                @endswitch
            </h2>
            <button class="cs-modal-close" wire:click="closeEdit">
                <i class="fa-light fa-xmark"></i>
            </button>
        </div>

        @if($editingField === 'brand_name')
            <div style="margin-bottom: 20px;">
                <input type="text" class="cs-input" wire:model="editBrandName" placeholder="{{ __('Brand name') }}">
            </div>

        @elseif($editingField === 'tagline')
            <div style="margin-bottom: 20px;">
                <input type="text" class="cs-input" wire:model="editTagline" placeholder="{{ __('Your brand tagline') }}" style="font-family: var(--cs-font-serif); font-style: italic;">
            </div>

        @elseif($editingField === 'business_overview')
            <div style="margin-bottom: 20px;">
                <textarea class="cs-input" wire:model="editOverview" rows="5" placeholder="{{ __('Describe your business...') }}"></textarea>
            </div>

        @elseif($editingField === 'colors')
            <div style="margin-bottom: 20px;">
                {{-- Current colors --}}
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; justify-content: center;">
                    @foreach($editColors as $index => $color)
                        <div class="cs-color-swatch" style="background: {{ $color }};" title="{{ $color }}">
                            <div class="cs-swatch-remove" wire:click="removeColor({{ $index }})" style="display: flex;">
                                <i class="fa-light fa-xmark" style="font-size: 9px;"></i>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Add color --}}
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="color" wire:model="newColorHex" style="width: 48px; height: 40px; border: none; border-radius: 8px; cursor: pointer;">
                    <input type="text" class="cs-input" wire:model="newColorHex" placeholder="#hex" style="flex: 1; font-family: monospace;">
                    <button class="cs-btn cs-btn-secondary cs-btn-sm" wire:click="addColor">
                        {{ __('Add color') }}
                    </button>
                </div>
            </div>

        @elseif($editingField === 'language')
            <div style="margin-bottom: 20px;">
                <select class="cs-input" wire:model="editLanguage" style="cursor: pointer;">
                    @foreach([
                        'Afrikaans', 'Albanian', 'Amharic', 'Arabic', 'Armenian', 'Azerbaijani',
                        'Basque', 'Belarusian', 'Bengali', 'Bosnian', 'Bulgarian', 'Burmese',
                        'Catalan', 'Chinese', 'Croatian', 'Czech',
                        'Danish', 'Dutch',
                        'English', 'Estonian', 'Ethiopian',
                        'Filipino', 'Finnish', 'French',
                        'Galician', 'Georgian', 'German', 'Greek', 'Gujarati',
                        'Haitian Creole', 'Hausa', 'Hebrew', 'Hindi', 'Hungarian',
                        'Icelandic', 'Igbo', 'Indonesian', 'Irish', 'Italian',
                        'Japanese', 'Javanese',
                        'Kannada', 'Kazakh', 'Khmer', 'Korean', 'Kurdish', 'Kyrgyz',
                        'Lao', 'Latvian', 'Lithuanian', 'Luxembourgish',
                        'Macedonian', 'Malagasy', 'Malay', 'Malayalam', 'Maltese', 'Maori', 'Marathi', 'Mongolian',
                        'Nepali', 'Norwegian',
                        'Odia',
                        'Pashto', 'Persian', 'Polish', 'Portuguese', 'Punjabi',
                        'Romanian', 'Russian',
                        'Samoan', 'Serbian', 'Sesotho', 'Shona', 'Sindhi', 'Sinhala', 'Slovak', 'Slovenian', 'Somali', 'Spanish', 'Sundanese', 'Swahili', 'Swedish',
                        'Tajik', 'Tamil', 'Tatar', 'Telugu', 'Thai', 'Turkish', 'Turkmen',
                        'Ukrainian', 'Urdu', 'Uzbek',
                        'Vietnamese',
                        'Welsh',
                        'Xhosa',
                        'Yiddish', 'Yoruba',
                        'Zulu',
                    ] as $lang)
                        <option value="{{ $lang }}" @if($editLanguage === $lang) selected @endif>{{ $lang }}</option>
                    @endforeach
                </select>
                <p style="font-size: 12px; color: var(--cs-text-muted); margin-top: 8px;">
                    {{ __('All AI-generated content (campaigns, creatives, suggestions) will be written in this language.') }}
                </p>
            </div>

        @elseif(in_array($editingField, ['brand_values', 'brand_aesthetic', 'brand_tone']))
            @php
                $chips = match($editingField) {
                    'brand_values' => $editBrandValues,
                    'brand_aesthetic' => $editBrandAesthetic,
                    'brand_tone' => $editBrandTone,
                    default => [],
                };
            @endphp
            <div style="margin-bottom: 20px;">
                {{-- Current chips --}}
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
                    @foreach($chips as $index => $chip)
                        <span class="cs-chip">
                            {{ $chip }}
                            <span class="cs-chip-remove" wire:click="removeChip('{{ $editingField }}', {{ $index }})">
                                <i class="fa-light fa-xmark"></i>
                            </span>
                        </span>
                    @endforeach
                </div>

                {{-- Add chip --}}
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="cs-input" wire:model="newChipValue"
                           wire:keydown.enter="addChip('{{ $editingField }}')"
                           placeholder="{{ __('Type and press Enter') }}"
                           style="flex: 1;">
                    <button class="cs-btn cs-btn-secondary cs-btn-sm" wire:click="addChip('{{ $editingField }}')">
                        {{ __('Add') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div style="display: flex; justify-content: flex-end; gap: 8px; padding-top: 12px; border-top: 1px solid var(--cs-border);">
            <button class="cs-btn cs-btn-ghost" wire:click="closeEdit">{{ __('Cancel') }}</button>
            <button class="cs-btn cs-btn-primary" wire:click="saveField">{{ __('Apply') }}</button>
        </div>
    </div>

</div>
@endif
