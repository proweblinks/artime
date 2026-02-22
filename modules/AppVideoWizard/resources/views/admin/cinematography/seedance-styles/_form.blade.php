@php
    $style = $seedanceStyle ?? null;
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
                               value="{{ old('name', $style?->name) }}" required
                               id="styleName" oninput="generateSlug()">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Slug') }} <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $style?->slug) }}"
                               {{ $style ? 'readonly' : 'required' }}
                               id="styleSlug" pattern="[a-z0-9-]+">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" {{ old('category', $style?->category) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Sort Order') }}</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', $style?->sort_order ?? 0) }}" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $style?->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prompt Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-terminal me-2"></i>{{ __('Prompt Settings') }}</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Prompt Syntax') }} <span class="text-danger">*</span></label>
                    <input type="text" name="prompt_syntax" class="form-control @error('prompt_syntax') is-invalid @enderror"
                           value="{{ old('prompt_syntax', $style?->prompt_syntax) }}" required maxlength="512"
                           placeholder="e.g. Cinematic, photorealistic">
                    <small class="text-muted">{{ __('Exact text injected into the Seedance prompt. Keep concise.') }}</small>
                    @error('prompt_syntax') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-sliders me-2"></i>{{ __('Status') }}</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="is_default" value="0">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                           id="isDefault" {{ old('is_default', $style?->is_default) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isDefault">{{ __('Default for category') }}</label>
                </div>
            </div>
        </div>

        <!-- Compatible Genres -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-tags me-2"></i>{{ __('Compatible Genres') }}</h6>
            </div>
            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                @php
                    $genres = ['drama', 'thriller', 'action', 'romance', 'comedy', 'horror', 'sci-fi', 'fantasy', 'documentary', 'commercial', 'lifestyle', 'nature', 'music-video', 'indie', 'noir', 'period', 'animation'];
                    $currentGenres = old('compatible_genres', $style?->compatible_genres ?? []);
                @endphp
                @foreach($genres as $genre)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="compatible_genres[]"
                               value="{{ $genre }}" id="genre_{{ $genre }}"
                               {{ in_array($genre, $currentGenres) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="genre_{{ $genre }}">{{ ucfirst($genre) }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Compatible Moods -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fa fa-heart me-2"></i>{{ __('Compatible Moods') }}</h6>
            </div>
            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                @php
                    $moods = ['dramatic', 'tense', 'romantic', 'epic', 'warm', 'cold', 'mysterious', 'playful', 'nostalgic', 'intense', 'serene', 'dark', 'vibrant', 'intimate', 'powerful', 'dreamy'];
                    $currentMoods = old('compatible_moods', $style?->compatible_moods ?? []);
                @endphp
                @foreach($moods as $mood)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="compatible_moods[]"
                               value="{{ $mood }}" id="mood_{{ $mood }}"
                               {{ in_array($mood, $currentMoods) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="mood_{{ $mood }}">{{ ucfirst($mood) }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa fa-save me-1"></i>{{ $style ? __('Update Style') : __('Create Style') }}
                </button>
                <a href="{{ route('admin.video-wizard.cinematography.seedance-styles') }}" class="btn btn-outline-secondary w-100">
                    {{ __('Cancel') }}
                </a>
            </div>
        </div>
    </div>
</div>

@if(!$style)
<script>
function generateSlug() {
    const name = document.getElementById('styleName').value;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    document.getElementById('styleSlug').value = slug;
}
</script>
@endif
