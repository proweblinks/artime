{{-- Transcript Preview/Edit Modal --}}
@if($showTranscriptModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 10100;">
    <div class="card border-0" style="background: #1a1a1a; border-radius: 16px; width: 560px; max-height: 85vh; display: flex; flex-direction: column;">
        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <div>
                <h5 class="mb-1 text-white fw-bold">{{ __('Review Transcript') }}</h5>
                <small class="text-muted">{{ __('Edit the narration script before generating your video') }}</small>
            </div>
            <button wire:click="$set('showTranscriptModal', false)" type="button" class="btn-close btn-close-white"></button>
        </div>

        {{-- Body --}}
        <div class="card-body p-4 pt-2" style="overflow-y: auto;">
            {{-- Title --}}
            @if($generatedTitle)
                <div class="mb-3">
                    <label class="form-label text-muted small">{{ __('Title') }}</label>
                    <input type="text" wire:model.defer="generatedTitle"
                           class="form-control border-0 text-white fw-semibold"
                           style="background: #2a2a2a; border-radius: 8px;">
                </div>
            @endif

            {{-- Transcript Textarea --}}
            <div class="mb-3">
                <label class="form-label text-muted small">{{ __('Narration Script') }}</label>
                <textarea wire:model="editableTranscript"
                          class="form-control border-0 text-white"
                          rows="12"
                          style="background: #2a2a2a; border-radius: 8px; resize: vertical; font-size: 0.95rem; line-height: 1.7;"
                          placeholder="{{ __('Your narration script will appear here...') }}"></textarea>
            </div>

            {{-- Word Count --}}
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">
                    <i class="fa-light fa-text me-1"></i>
                    {{ $transcriptWordCount }} / {{ get_option('story_mode_max_words', 450) }} {{ __('words') }}
                </small>
                <small class="text-muted">
                    <i class="fa-light fa-clock me-1"></i>
                    ~{{ (int) round(($transcriptWordCount / 140) * 60) }}s {{ __('estimated') }}
                </small>
            </div>

            @if($transcriptWordCount > (int) get_option('story_mode_max_words', 450))
                <div class="alert border-0 mt-2 py-2 px-3" style="background: #3d2b15; color: #f97316; border-radius: 8px; font-size: 0.8rem;">
                    <i class="fa-light fa-triangle-exclamation me-1"></i>
                    {{ __('Transcript exceeds maximum word count. Consider shortening for best results.') }}
                </div>
            @endif

            {{-- Video Settings --}}
            <div class="d-flex align-items-center gap-3 mt-3 p-3" style="background: #2a2a2a; border-radius: 10px;"
                 x-data="{
                     resolution: @js($videoResolution),
                     quality: @js($videoQuality),
                     init() {
                         this.$watch('quality', (val) => {
                             if (val === 'fast' && this.resolution === '480p') {
                                 this.resolution = '720p';
                                 $wire.set('videoResolution', '720p');
                             }
                             $wire.set('videoQuality', val);
                         });
                         this.$watch('resolution', (val) => {
                             $wire.set('videoResolution', val);
                         });
                     }
                 }">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="fa-light fa-film text-muted" style="font-size: 0.85rem;"></i>
                    <small class="text-muted" style="white-space: nowrap;">{{ __('Resolution') }}</small>
                    <select x-model="resolution"
                            class="form-select form-select-sm border-0 text-white"
                            style="background: #1a1a1a; border-radius: 6px; font-size: 0.8rem; width: auto; padding: 4px 28px 4px 10px;">
                        <option value="480p" x-show="quality !== 'fast'">480p</option>
                        <option value="720p">720p</option>
                        <option value="1080p">1080p</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="fa-light fa-gauge-high text-muted" style="font-size: 0.85rem;"></i>
                    <small class="text-muted" style="white-space: nowrap;">{{ __('Quality') }}</small>
                    <select x-model="quality"
                            class="form-select form-select-sm border-0 text-white"
                            style="background: #1a1a1a; border-radius: 6px; font-size: 0.8rem; width: auto; padding: 4px 28px 4px 10px;">
                        <option value="pro">Pro</option>
                        <option value="fast">Fast</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2 d-flex gap-3" style="background: transparent;">
            <button wire:click="$set('showTranscriptModal', false)" type="button"
                    class="btn flex-grow-1" style="background: #2a2a2a; color: #ccc; border-radius: 10px;">
                {{ __('Cancel') }}
            </button>
            <button wire:click="confirmTranscript" type="button"
                    class="btn flex-grow-1 fw-semibold" style="background: #f97316; color: #fff; border-radius: 10px;"
                    {{ empty($editableTranscript) ? 'disabled' : '' }}>
                <i class="fa-light fa-video me-1"></i>
                {{ __('Generate Video') }}
            </button>
        </div>
    </div>
</div>
@endif
