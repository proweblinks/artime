@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.prompts.index') }}">{{ __('Prompts') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Create New Prompt') }}</div>
    </div>
</div>

<div class="container py-4">
    <form action="{{ route('admin.video-wizard.prompts.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Prompt Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Slug') }} <small class="text-muted">({{ __('unique identifier') }})</small></label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required placeholder="e.g., script_generation">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g., Script Generation">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Brief description of what this prompt does...') }}">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>{{ __('Prompt Template') }}</span>
                                <small class="text-muted">{{ __('Use') }} &#123;&#123;variable&#125;&#125; {{ __('for placeholders') }}</small>
                            </label>
                            <textarea name="prompt_template" class="form-control font-monospace @error('prompt_template') is-invalid @enderror" rows="20" required placeholder="{{ __('Enter your prompt template here...') }}">{{ old('prompt_template') }}</textarea>
                            @error('prompt_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('AI Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <select name="model" class="form-select">
                                <option value="gpt-4">GPT-4</option>
                                <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                <option value="gpt-4o">GPT-4o</option>
                                <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                <option value="claude-3-opus">Claude 3 Opus</option>
                                <option value="claude-3-sonnet">Claude 3 Sonnet</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Temperature') }} <small class="text-muted">(0.0 - 2.0)</small></label>
                            <input type="number" name="temperature" class="form-control" value="{{ old('temperature', '0.7') }}" min="0" max="2" step="0.1">
                            <small class="text-muted">{{ __('Lower = more focused, Higher = more creative') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Max Tokens') }}</label>
                            <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', '4000') }}" min="100" max="100000">
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">{{ __('Active') }}</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save me-1"></i> {{ __('Create Prompt') }}
                    </button>
                    <a href="{{ route('admin.video-wizard.prompts.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
