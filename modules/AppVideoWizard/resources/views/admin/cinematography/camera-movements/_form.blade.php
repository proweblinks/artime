@php
    $movement = $cameraMovement ?? null;
@endphp

<div class="row">
    <div class="col-lg-8">
        <!-- Basic Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-info-circle me-2"></i>{{ __('Basic Information') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $movement?->name) }}" required placeholder="Dolly Zoom">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Slug') }} <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $movement?->slug) }}" {{ $movement ? 'readonly' : 'required' }} pattern="[a-z0-9-]+"
                               placeholder="dolly-zoom">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">{{ __('Lowercase letters, numbers, and dashes only') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" {{ old('category', $movement?->category) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Intensity') }} <span class="text-danger">*</span></label>
                        <select name="intensity" class="form-select @error('intensity') is-invalid @enderror" required>
                            @foreach($intensities as $value => $label)
                                <option value="{{ $value }}" {{ old('intensity', $movement?->intensity ?? 'moderate') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('intensity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">{{ __('How dramatic/noticeable the movement is') }}</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2" placeholder="Describe when and why to use this movement...">{{ old('description', $movement?->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Prompt Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-code me-2"></i>{{ __('Prompt Settings') }}</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-terminal text-primary me-1"></i>
                        {{ __('Prompt Syntax') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="prompt_syntax" class="form-control @error('prompt_syntax') is-invalid @enderror"
                           value="{{ old('prompt_syntax', $movement?->prompt_syntax) }}" required
                           placeholder="camera pushes in toward the subject">
                    @error('prompt_syntax')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('The exact text that will be inserted into the AI video prompt') }}</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-random text-info me-1"></i>
                        {{ __('Ending State') }}
                    </label>
                    <input type="text" name="ending_state" class="form-control @error('ending_state') is-invalid @enderror"
                           value="{{ old('ending_state', $movement?->ending_state) }}"
                           placeholder="closer to subject, static">
                    @error('ending_state')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Describes camera position after the movement (for continuity)') }}</small>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-arrow-right text-success me-1"></i>
                        {{ __('Natural Continuation') }}
                    </label>
                    <select name="natural_continuation" class="form-select @error('natural_continuation') is-invalid @enderror">
                        <option value="">{{ __('None') }}</option>
                        @foreach($allMovements as $slug => $name)
                            <option value="{{ $slug }}" {{ old('natural_continuation', $movement?->natural_continuation) == $slug ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('natural_continuation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Suggested movement for the next shot (for visual continuity)') }}</small>
                </div>
            </div>
        </div>

        <!-- Duration Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-clock me-2"></i>{{ __('Duration Settings') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Minimum Duration (seconds)') }}</label>
                        <input type="number" name="typical_duration_min" class="form-control @error('typical_duration_min') is-invalid @enderror"
                               value="{{ old('typical_duration_min', $movement?->typical_duration_min ?? 2) }}" min="1" max="60">
                        @error('typical_duration_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Maximum Duration (seconds)') }}</label>
                        <input type="number" name="typical_duration_max" class="form-control @error('typical_duration_max') is-invalid @enderror"
                               value="{{ old('typical_duration_max', $movement?->typical_duration_max ?? 5) }}" min="1" max="60">
                        @error('typical_duration_max')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <small class="text-muted">{{ __('Recommended duration range for this movement') }}</small>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-cog me-2"></i>{{ __('Status') }}</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $movement?->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    <div class="small text-muted">{{ __('Inactive movements are hidden from users') }}</div>
                </div>
            </div>
        </div>

        <!-- Stackable With -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-layer-group me-2"></i>{{ __('Stackable With') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $currentStackable = old('stackable_with', $movement?->stackable_with ?? []);
                    if (is_string($currentStackable)) {
                        $currentStackable = json_decode($currentStackable, true) ?? [];
                    }
                @endphp
                <div style="max-height: 200px; overflow-y: auto;">
                    @foreach($allMovements as $slug => $name)
                        @if(!$movement || $slug !== $movement->slug)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="stackable_with[]"
                                       value="{{ $slug }}" id="stackable_{{ $slug }}"
                                       {{ in_array($slug, $currentStackable) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="stackable_{{ $slug }}">
                                    {{ $name }}
                                </label>
                            </div>
                        @endif
                    @endforeach
                </div>
                <small class="text-muted mt-2 d-block">{{ __('Movements that can be combined with this one (e.g., dolly + pan)') }}</small>
            </div>
        </div>

        <!-- Best For Shot Types -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-video-camera me-2"></i>{{ __('Best For Shot Types') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $currentShotTypes = old('best_for_shot_types', $movement?->best_for_shot_types ?? []);
                    if (is_string($currentShotTypes)) {
                        $currentShotTypes = json_decode($currentShotTypes, true) ?? [];
                    }
                @endphp
                <div style="max-height: 200px; overflow-y: auto;">
                    @foreach($shotTypes as $slug => $name)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="best_for_shot_types[]"
                                   value="{{ $slug }}" id="shot_{{ $slug }}"
                                   {{ in_array($slug, $currentShotTypes) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="shot_{{ $slug }}">
                                {{ $name }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <small class="text-muted mt-2 d-block">{{ __('Shot types where this movement works best') }}</small>
            </div>
        </div>

        <!-- Best For Emotions -->
        @isset($emotions)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-heart me-2"></i>{{ __('Best For Emotions') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $currentEmotions = old('best_for_emotions', $movement?->best_for_emotions ?? []);
                    if (is_string($currentEmotions)) {
                        $currentEmotions = json_decode($currentEmotions, true) ?? [];
                    }
                @endphp
                <div style="max-height: 200px; overflow-y: auto;">
                    @foreach($emotions as $key => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="best_for_emotions[]"
                                   value="{{ $key }}" id="emotion_{{ $key }}"
                                   {{ in_array($key, $currentEmotions) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="emotion_{{ $key }}">
                                {{ $label }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <small class="text-muted mt-2 d-block">{{ __('Emotional contexts where this movement excels') }}</small>
            </div>
        </div>
        @endisset

        <!-- Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa fa-save me-1"></i>
                    {{ $movement ? __('Update Movement') : __('Create Movement') }}
                </button>
                <a href="{{ route('admin.video-wizard.cinematography.camera-movements') }}" class="btn btn-outline-secondary w-100">
                    {{ __('Cancel') }}
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from name (only on create)
@if(!$movement)
document.querySelector('input[name="name"]').addEventListener('blur', function() {
    const slugInput = document.querySelector('input[name="slug"]');
    if (!slugInput.value) {
        slugInput.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
});
@endif
</script>
@endpush
