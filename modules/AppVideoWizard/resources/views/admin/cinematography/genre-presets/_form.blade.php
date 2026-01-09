@php
    $preset = $preset ?? null;
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
                               value="{{ old('name', $preset?->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Slug') }} <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $preset?->slug) }}" required pattern="[a-z0-9-]+"
                               placeholder="lowercase-with-dashes">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">{{ __('Lowercase letters, numbers, and dashes only') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" {{ old('category', $preset?->category) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2">{{ old('description', $preset?->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Cinematography Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-film me-2"></i>{{ __('Cinematography Settings') }}</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-video-camera text-primary me-1"></i>
                        {{ __('Camera Language') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="camera_language" class="form-control @error('camera_language') is-invalid @enderror"
                              rows="2" required placeholder="slow dolly, low angles, stabilized gimbal, anamorphic lens feel">{{ old('camera_language', $preset?->camera_language) }}</textarea>
                    @error('camera_language')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Camera movements, angles, and techniques characteristic of this genre') }}</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-palette text-success me-1"></i>
                        {{ __('Color Grade') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="color_grade" class="form-control @error('color_grade') is-invalid @enderror"
                              rows="2" required placeholder="desaturated teal shadows, amber highlights, crushed blacks">{{ old('color_grade', $preset?->color_grade) }}</textarea>
                    @error('color_grade')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Color grading style: shadows, highlights, saturation, contrast') }}</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-lightbulb text-warning me-1"></i>
                        {{ __('Lighting') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="lighting" class="form-control @error('lighting') is-invalid @enderror"
                              rows="2" required placeholder="harsh single-source, dramatic rim lights, deep shadows">{{ old('lighting', $preset?->lighting) }}</textarea>
                    @error('lighting')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Lighting style and techniques for this genre') }}</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-cloud text-info me-1"></i>
                        {{ __('Atmosphere') }}
                    </label>
                    <textarea name="atmosphere" class="form-control @error('atmosphere') is-invalid @enderror"
                              rows="2" placeholder="smoke, rain reflections, wet surfaces, urban grit">{{ old('atmosphere', $preset?->atmosphere) }}</textarea>
                    @error('atmosphere')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Environmental and atmospheric elements') }}</small>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">
                        <i class="fa fa-magic text-danger me-1"></i>
                        {{ __('Style') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="style" class="form-control @error('style') is-invalid @enderror"
                              rows="2" required placeholder="ultra-cinematic photoreal, noir thriller, high contrast">{{ old('style', $preset?->style) }}</textarea>
                    @error('style')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Overall visual style and aesthetic references') }}</small>
                </div>
            </div>
        </div>

        <!-- Prompt Customization -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-code me-2"></i>{{ __('Prompt Customization') }}</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Prompt Prefix') }}</label>
                    <textarea name="prompt_prefix" class="form-control @error('prompt_prefix') is-invalid @enderror"
                              rows="2" placeholder="Added before the main prompt...">{{ old('prompt_prefix', $preset?->prompt_prefix) }}</textarea>
                    @error('prompt_prefix')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">{{ __('Prompt Suffix') }}</label>
                    <textarea name="prompt_suffix" class="form-control @error('prompt_suffix') is-invalid @enderror"
                              rows="2" placeholder="Added after the main prompt...">{{ old('prompt_suffix', $preset?->prompt_suffix) }}</textarea>
                    @error('prompt_suffix')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-cog me-2"></i>{{ __('Settings') }}</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $preset?->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    <div class="small text-muted">{{ __('Inactive presets are hidden from users') }}</div>
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="is_default" value="0">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default"
                           {{ old('is_default', $preset?->is_default) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default">{{ __('Default Preset') }}</label>
                    <div class="small text-muted">{{ __('Used when no genre is specified') }}</div>
                </div>
            </div>
        </div>

        <!-- Lens Preferences -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-camera me-2"></i>{{ __('Lens Preferences') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $lensPrefs = old('lens_preferences', $preset?->lens_preferences ?? []);
                    $defaultLenses = ['establishing' => '', 'medium' => '', 'close-up' => '', 'detail' => ''];
                    $lensPrefs = array_merge($defaultLenses, is_array($lensPrefs) ? $lensPrefs : []);
                @endphp

                @foreach(['establishing' => 'Establishing Shot', 'medium' => 'Medium Shot', 'close-up' => 'Close-Up', 'detail' => 'Detail Shot'] as $key => $label)
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ __($label) }}</label>
                        <input type="text" name="lens_preferences[{{ $key }}]" class="form-control form-control-sm"
                               value="{{ $lensPrefs[$key] ?? '' }}" placeholder="e.g., 24mm wide-angle">
                    </div>
                @endforeach
                <small class="text-muted">{{ __('Recommended lens for each shot type') }}</small>
            </div>
        </div>

        <!-- Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa fa-save me-1"></i>
                    {{ $preset ? __('Update Preset') : __('Create Preset') }}
                </button>
                <a href="{{ route('admin.video-wizard.cinematography.genre-presets.index') }}" class="btn btn-outline-secondary w-100">
                    {{ __('Cancel') }}
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('blur', function() {
    const slugInput = document.querySelector('input[name="slug"]');
    if (!slugInput.value) {
        slugInput.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
});
</script>
@endpush
