{{-- Transcript Preview/Edit Modal --}}
@if($showTranscriptModal)
<div class="d-flex align-items-center justify-content-center"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10100;">
    <div class="card border-0" style="background: #ffffff; border: 1px solid #eef1f5; border-radius: 16px; width: {{ $creativeMode ? '620px' : '560px' }}; max-height: 85vh; display: flex; flex-direction: column; transition: width 0.2s ease; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background: transparent;">
            <div>
                <h5 class="mb-1 fw-bold" style="color: var(--at-text, #1a1a2e);">{{ __('Review Transcript') }}</h5>
                <small style="color: var(--at-text-muted, #94a0b8);">{{ __('Edit the narration script before generating your video') }}</small>
            </div>
            <button wire:click="$set('showTranscriptModal', false)" type="button" class="btn-close"></button>
        </div>

        {{-- Body --}}
        <div class="card-body p-4 pt-2" style="overflow-y: auto;">

            {{-- Creative Mode: Concept Badge + Actions --}}
            @if($creativeMode && $creativeConceptTitle)
                <div class="mb-3">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="utv-concept-badge mb-1">
                                <i class="fa-light fa-wand-magic-sparkles"></i>
                                {{ $creativeConceptTitle }}
                            </div>
                            @if($creativeConceptPitch)
                                <p class="mb-0 mt-1" style="color: var(--at-text-secondary, #5a6178); font-size: 0.78rem; line-height: 1.4;">{{ $creativeConceptPitch }}</p>
                            @endif
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button wire:click="shuffleCreativeConcept" type="button"
                                    class="btn btn-sm d-flex align-items-center gap-1"
                                    style="background: #f5f7fa; color: #7c3aed; border-radius: 8px; font-size: 0.75rem; border: none; white-space: nowrap;"
                                    wire:loading.attr="disabled" wire:target="shuffleCreativeConcept">
                                <i class="fa-light fa-shuffle" wire:loading.class="fa-spin" wire:target="shuffleCreativeConcept"></i>
                                {{ __('Shuffle') }}
                            </button>
                            <button wire:click="generateMoreIdeas" type="button"
                                    class="btn btn-sm d-flex align-items-center gap-1"
                                    style="background: #f5f7fa; color: #7c3aed; border-radius: 8px; font-size: 0.75rem; border: none; white-space: nowrap;"
                                    wire:loading.attr="disabled" wire:target="generateMoreIdeas">
                                <i class="fa-light fa-lightbulb" wire:loading.class="fa-spin" wire:target="generateMoreIdeas"></i>
                                {{ __('More Ideas') }}
                            </button>
                        </div>
                    </div>

                    {{-- Loading state for concept generation --}}
                    @if($isGeneratingConcepts)
                        <div class="d-flex align-items-center gap-2 p-3 mb-2" style="background: rgba(139,92,246,0.06); border-radius: 10px;">
                            <i class="fa-light fa-spinner-third fa-spin" style="color: #7c3aed;"></i>
                            <span style="color: #7c3aed; font-size: 0.82rem;">{{ __('Brainstorming alternative angles...') }}</span>
                        </div>
                    @endif

                    {{-- Concept Cards Panel --}}
                    @if($showConceptCards && !empty($alternativeConcepts))
                        <div class="d-flex flex-column gap-2 mb-2">
                            @foreach($alternativeConcepts as $idx => $concept)
                                @php
                                    $toneClass = 'utv-tone-' . ($concept['tone'] ?? 'intellectual');
                                @endphp
                                <div wire:click="selectCreativeConcept({{ $idx }})" class="utv-concept-card"
                                     wire:loading.class="opacity-50" wire:target="selectCreativeConcept">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1" style="font-size: 0.85rem; color: var(--at-text, #1a1a2e);">
                                                {{ $concept['title'] ?? 'Untitled' }}
                                            </div>
                                            <p class="mb-0" style="color: var(--at-text-secondary, #5a6178); font-size: 0.78rem; line-height: 1.4;">
                                                {{ $concept['pitch'] ?? '' }}
                                            </p>
                                        </div>
                                        <span class="utv-concept-tone-badge {{ $toneClass }}">
                                            {{ $concept['tone'] ?? 'creative' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Title --}}
            @if($generatedTitle)
                <div class="mb-3">
                    <label class="form-label small" style="color: var(--at-text-muted, #94a0b8);">{{ __('Title') }}</label>
                    <input type="text" wire:model.defer="generatedTitle"
                           class="form-control border-0 fw-semibold"
                           style="background: #f5f7fa; border-radius: 8px; color: var(--at-text, #1a1a2e);">
                </div>
            @endif

            {{-- Transcript Textarea --}}
            <div class="mb-3">
                <label class="form-label small" style="color: var(--at-text-muted, #94a0b8);">{{ __('Narration Script') }}</label>
                <textarea wire:model="editableTranscript"
                          class="form-control border-0"
                          rows="12"
                          style="background: #f5f7fa; border-radius: 8px; resize: vertical; font-size: 0.95rem; line-height: 1.7; color: var(--at-text, #1a1a2e);"
                          placeholder="{{ __('Your narration script will appear here...') }}"></textarea>
            </div>

            {{-- Word Count --}}
            <div class="d-flex align-items-center justify-content-between">
                <small style="color: var(--at-text-muted, #94a0b8);">
                    <i class="fa-light fa-text me-1"></i>
                    {{ $transcriptWordCount }} / {{ $this->calculateMaxWords($videoDuration) }} {{ __('words') }}
                </small>
                <small style="color: var(--at-text-muted, #94a0b8);">
                    <i class="fa-light fa-clock me-1"></i>
                    ~{{ (int) round(($transcriptWordCount / 140) * 60) }}s {{ __('estimated') }}
                </small>
            </div>

            @if($transcriptWordCount > $this->calculateMaxWords($videoDuration))
                <div class="alert border-0 mt-2 py-2 px-3" style="background: rgba(245,158,11,0.08); color: #d97706; border-radius: 8px; font-size: 0.8rem;">
                    <i class="fa-light fa-triangle-exclamation me-1"></i>
                    {{ __('Transcript exceeds maximum word count. Consider shortening for best results.') }}
                </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2 d-flex gap-3" style="background: transparent;">
            <button wire:click="$set('showTranscriptModal', false)" type="button"
                    class="btn flex-grow-1" style="background: #f5f7fa; color: var(--at-text-secondary, #5a6178); border-radius: 10px;">
                {{ __('Cancel') }}
            </button>
            <button wire:click="confirmTranscript" type="button"
                    class="btn flex-grow-1 fw-semibold" style="background: #03fcf4; color: #0a2e2e; border-radius: 10px;"
                    wire:loading.attr="disabled" wire:target="confirmTranscript"
                    {{ empty($editableTranscript) ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="confirmTranscript">
                    <i class="fa-light fa-images me-1"></i>
                    {{ __('Select Media') }}
                </span>
                <span wire:loading wire:target="confirmTranscript">
                    <i class="fa-light fa-spinner-third fa-spin me-1"></i>
                    {{ __('Finding media...') }}
                </span>
            </button>
        </div>
    </div>
</div>
@endif
