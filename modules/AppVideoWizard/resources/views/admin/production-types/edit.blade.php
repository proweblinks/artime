@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.production-types.index') }}">{{ __('Production Types') }}</a></li>
                <li class="breadcrumb-item active">{{ $productionType->name }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Edit Production Type') }}: {{ $productionType->name }}</div>
    </div>
</div>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.video-wizard.production-types.update', $productionType) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Type Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Parent Type') }}</label>
                            <select name="parent_id" class="form-select">
                                <option value="">{{ __('-- Main Type (no parent) --') }}</option>
                                @foreach($parentTypes as $parent)
                                    <option value="{{ $parent->id }}" {{ $productionType->parent_id == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Slug') }}</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $productionType->slug) }}" required>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $productionType->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Icon') }}</label>
                                <input type="text" name="icon" class="form-control" value="{{ old('icon', $productionType->icon) }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('Description') }}</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description', $productionType->description) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Default Narration') }}</label>
                            <select name="default_narration" class="form-select">
                                <option value="">{{ __('None') }}</option>
                                <option value="voiceover" {{ $productionType->default_narration === 'voiceover' ? 'selected' : '' }}>{{ __('Voiceover') }}</option>
                                <option value="dialogue" {{ $productionType->default_narration === 'dialogue' ? 'selected' : '' }}>{{ __('Dialogue') }}</option>
                                <option value="narrator" {{ $productionType->default_narration === 'narrator' ? 'selected' : '' }}>{{ __('Narrator') }}</option>
                                <option value="music" {{ $productionType->default_narration === 'music' ? 'selected' : '' }}>{{ __('Music Only') }}</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">{{ __('Min Duration') }} <small class="text-muted">(s)</small></label>
                                <input type="number" name="suggested_duration_min" class="form-control" value="{{ old('suggested_duration_min', $productionType->suggested_duration_min) }}" min="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ __('Max Duration') }} <small class="text-muted">(s)</small></label>
                                <input type="number" name="suggested_duration_max" class="form-control" value="{{ old('suggested_duration_max', $productionType->suggested_duration_max) }}" min="1">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ $productionType->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">{{ __('Active') }}</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save me-1"></i> {{ __('Save Changes') }}
                    </button>
                    <a href="{{ route('admin.video-wizard.production-types.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
