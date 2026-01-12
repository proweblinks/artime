@extends('admin.layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Cinematography / Shot Types') }}</div>
                    <h2 class="page-title">{{ __('Edit Shot Type') }}: {{ $shotType->name }}</h2>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.video-wizard.cinematography.shot-types') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>{{ __('Back to Shot Types') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-xl">
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <h4 class="alert-title">{{ __('Validation Errors') }}</h4>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.video-wizard.cinematography.shot-types.update', $shotType) }}" method="POST">
            @csrf
            @method('PUT')

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
                                           value="{{ old('name', $shotType->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Slug') }}</label>
                                    <input type="text" class="form-control bg-light" value="{{ $shotType->slug }}" disabled>
                                    <small class="text-muted">{{ __('Slug cannot be changed') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Category') }}</label>
                                    <input type="text" class="form-control bg-light"
                                           value="{{ $categories[$shotType->category] ?? $shotType->category }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Description') }}</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                              rows="2">{{ old('description', $shotType->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Camera Specifications -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-camera me-2"></i>{{ __('Camera Specifications') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Default Lens') }}</label>
                                    <input type="text" name="default_lens" class="form-control @error('default_lens') is-invalid @enderror"
                                           value="{{ old('default_lens', $shotType->default_lens) }}"
                                           placeholder="e.g., 50mm prime, 24-70mm zoom">
                                    @error('default_lens')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Default Aperture') }}</label>
                                    <input type="text" name="default_aperture" class="form-control @error('default_aperture') is-invalid @enderror"
                                           value="{{ old('default_aperture', $shotType->default_aperture) }}"
                                           placeholder="e.g., f/1.4, f/2.8">
                                    @error('default_aperture')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Camera Specs (AI Prompt Text)') }}</label>
                                    <textarea name="camera_specs" class="form-control @error('camera_specs') is-invalid @enderror"
                                              rows="3" placeholder="Detailed camera specifications for AI prompt...">{{ old('camera_specs', $shotType->camera_specs) }}</textarea>
                                    @error('camera_specs')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Technical specifications included in AI prompts') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motion & Duration -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-clock-o me-2"></i>{{ __('Motion & Duration') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Typical Duration (Min)') }}</label>
                                    <div class="input-group">
                                        <input type="number" name="typical_duration_min" class="form-control @error('typical_duration_min') is-invalid @enderror"
                                               value="{{ old('typical_duration_min', $shotType->typical_duration_min ?? 3) }}"
                                               min="1" max="60">
                                        <span class="input-group-text">{{ __('seconds') }}</span>
                                    </div>
                                    @error('typical_duration_min')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Typical Duration (Max)') }}</label>
                                    <div class="input-group">
                                        <input type="number" name="typical_duration_max" class="form-control @error('typical_duration_max') is-invalid @enderror"
                                               value="{{ old('typical_duration_max', $shotType->typical_duration_max ?? 8) }}"
                                               min="1" max="60">
                                        <span class="input-group-text">{{ __('seconds') }}</span>
                                    </div>
                                    @error('typical_duration_max')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Motion Description') }}</label>
                                    <textarea name="motion_description" class="form-control @error('motion_description') is-invalid @enderror"
                                              rows="2" placeholder="How the camera moves in this shot type...">{{ old('motion_description', $shotType->motion_description) }}</textarea>
                                    @error('motion_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motion Intelligence (Phase 2) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-video me-2 text-danger"></i>{{ __('Motion Intelligence') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Primary Camera Movement') }}</label>
                                    <select name="primary_movement" class="form-select @error('primary_movement') is-invalid @enderror">
                                        <option value="">{{ __('-- Auto Select --') }}</option>
                                        @foreach($cameraMovements ?? [] as $slug => $name)
                                            <option value="{{ $slug }}" {{ old('primary_movement', $shotType->primary_movement) == $slug ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('primary_movement')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Recommended camera movement for this shot type') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Movement Intensity') }}</label>
                                    <select name="movement_intensity" class="form-select @error('movement_intensity') is-invalid @enderror">
                                        <option value="static" {{ old('movement_intensity', $shotType->movement_intensity) == 'static' ? 'selected' : '' }}>{{ __('Static (No Movement)') }}</option>
                                        <option value="subtle" {{ old('movement_intensity', $shotType->movement_intensity) == 'subtle' ? 'selected' : '' }}>{{ __('Subtle (Slow/Gentle)') }}</option>
                                        <option value="moderate" {{ old('movement_intensity', $shotType->movement_intensity ?? 'moderate') == 'moderate' ? 'selected' : '' }}>{{ __('Moderate (Balanced)') }}</option>
                                        <option value="dynamic" {{ old('movement_intensity', $shotType->movement_intensity) == 'dynamic' ? 'selected' : '' }}>{{ __('Dynamic (Energetic)') }}</option>
                                        <option value="intense" {{ old('movement_intensity', $shotType->movement_intensity) == 'intense' ? 'selected' : '' }}>{{ __('Intense (Dramatic)') }}</option>
                                    </select>
                                    @error('movement_intensity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Typical Ending State') }}</label>
                                    <input type="text" name="typical_ending" class="form-control @error('typical_ending') is-invalid @enderror"
                                           value="{{ old('typical_ending', $shotType->typical_ending) }}"
                                           placeholder="e.g., subject centered, camera at eye level">
                                    @error('typical_ending')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('How the shot typically ends (for continuity with next shot)') }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Stackable Movements') }}</label>
                                    @php
                                        $currentStackable = is_string($shotType->stackable_movements)
                                            ? json_decode($shotType->stackable_movements, true)
                                            : ($shotType->stackable_movements ?? []);
                                        $currentStackable = $currentStackable ?? [];
                                    @endphp
                                    <div class="row g-2">
                                        @foreach($cameraMovements ?? [] as $slug => $name)
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="stackable_movements[]" value="{{ $slug }}" id="stack_{{ $slug }}"
                                                           {{ in_array($slug, $currentStackable) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="stack_{{ $slug }}">{{ $name }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted mt-2 d-block">{{ __('Secondary movements that can be combined with this shot type') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Prompt Template -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-film me-2 text-success"></i>{{ __('Video Animation Prompt Template') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-0">
                                <label class="form-label fw-semibold">{{ __('Video Prompt Template') }}</label>
                                <textarea name="video_prompt_template" class="form-control font-monospace @error('video_prompt_template') is-invalid @enderror"
                                          rows="4" placeholder="{style} {shot_name}, {subject} {action}, {camera_movement}, {lighting}">{{ old('video_prompt_template', $shotType->video_prompt_template) }}</textarea>
                                @error('video_prompt_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    {{ __('Template for video animation prompts. Variables:') }}<br>
                                    <code>{style}</code> {{ __('visual style') }},
                                    <code>{shot_name}</code> {{ __('shot type name') }},
                                    <code>{subject}</code> {{ __('who/what in frame') }},
                                    <code>{action}</code> {{ __('subject action') }},
                                    <code>{camera_movement}</code> {{ __('camera motion') }},
                                    <code>{lighting}</code> {{ __('lighting description') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Image AI Prompt Template -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-code me-2"></i>{{ __('AI Image Prompt Template') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-0">
                                <label class="form-label fw-semibold">{{ __('Prompt Template') }}</label>
                                <textarea name="prompt_template" class="form-control font-monospace @error('prompt_template') is-invalid @enderror"
                                          rows="4" placeholder="Template for generating AI prompts with this shot type...">{{ old('prompt_template', $shotType->prompt_template) }}</textarea>
                                @error('prompt_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    {{ __('Use placeholders like {subject}, {action}, {setting} for dynamic content') }}
                                </small>
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
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                       {{ old('is_active', $shotType->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                <div class="small text-muted">{{ __('Inactive shot types are not used in generation') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Emotional Beats -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-heart me-2"></i>{{ __('Best For Emotional Beats') }}</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $currentBeats = is_string($shotType->emotional_beats)
                                    ? json_decode($shotType->emotional_beats, true)
                                    : ($shotType->emotional_beats ?? []);
                                $currentBeats = $currentBeats ?? [];
                            @endphp
                            <div class="row g-2">
                                @foreach($emotionalBeats as $slug => $name)
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="emotional_beats[]" value="{{ $slug }}" id="beat_{{ $slug }}"
                                                   {{ in_array($slug, $currentBeats) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="beat_{{ $slug }}">{{ $name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-2">{{ __('Select which emotional beats this shot type works best for') }}</small>
                        </div>
                    </div>

                    <!-- Best For Genres -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fa fa-film me-2"></i>{{ __('Best For Genres') }}</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $currentGenres = is_string($shotType->best_for_genres)
                                    ? json_decode($shotType->best_for_genres, true)
                                    : ($shotType->best_for_genres ?? []);
                                $currentGenres = $currentGenres ?? [];
                                $genreOptions = [
                                    'documentary' => 'Documentary',
                                    'cinematic' => 'Cinematic',
                                    'horror' => 'Horror',
                                    'comedy' => 'Comedy',
                                    'social' => 'Social',
                                    'commercial' => 'Commercial',
                                    'experimental' => 'Experimental',
                                    'educational' => 'Educational',
                                ];
                            @endphp
                            <div class="row g-2">
                                @foreach($genreOptions as $slug => $name)
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="best_for_genres[]" value="{{ $slug }}" id="genre_{{ $slug }}"
                                                   {{ in_array($slug, $currentGenres) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="genre_{{ $slug }}">{{ $name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fa fa-save me-1"></i>
                                {{ __('Update Shot Type') }}
                            </button>
                            <a href="{{ route('admin.video-wizard.cinematography.shot-types') }}" class="btn btn-outline-secondary w-100">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
