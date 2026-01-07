@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.prompts.index') }}">{{ __('Prompts') }}</a></li>
                <li class="breadcrumb-item active">{{ $prompt->name }}</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Edit Prompt') }}: {{ $prompt->name }}</div>
                <code class="small">{{ $prompt->slug }}</code>
                <span class="badge bg-info ms-2">v{{ $prompt->version }}</span>
            </div>
            <div>
                <a href="{{ route('admin.video-wizard.prompts.history', $prompt) }}" class="btn btn-outline-secondary">
                    <i class="fa fa-history me-1"></i> {{ __('Version History') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.video-wizard.prompts.update', $prompt) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- Main Editor -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Prompt Template') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Name') }}</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $prompt->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $prompt->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>{{ __('Prompt Template') }}</span>
                                <small class="text-muted">{{ __('Use {{variable}} for placeholders') }}</small>
                            </label>
                            <textarea name="prompt_template" id="promptTemplate" class="form-control font-monospace @error('prompt_template') is-invalid @enderror" rows="20" required>{{ old('prompt_template', $prompt->prompt_template) }}</textarea>
                            @error('prompt_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Available Variables -->
                        @if(!empty($placeholders))
                            <div class="alert alert-info small">
                                <strong>{{ __('Detected Variables:') }}</strong>
                                @foreach($placeholders as $var)
                                    <code class="ms-2">@{{ {{ $var }} }}</code>
                                @endforeach
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">{{ __('Change Notes') }} <small class="text-muted">({{ __('optional') }})</small></label>
                            <input type="text" name="change_notes" class="form-control" placeholder="{{ __('Brief description of changes...') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Settings -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('AI Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <select name="model" class="form-select">
                                <option value="gpt-4" {{ $prompt->model === 'gpt-4' ? 'selected' : '' }}>GPT-4</option>
                                <option value="gpt-4-turbo" {{ $prompt->model === 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                                <option value="gpt-4o" {{ $prompt->model === 'gpt-4o' ? 'selected' : '' }}>GPT-4o</option>
                                <option value="gpt-3.5-turbo" {{ $prompt->model === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                <option value="claude-3-opus" {{ $prompt->model === 'claude-3-opus' ? 'selected' : '' }}>Claude 3 Opus</option>
                                <option value="claude-3-sonnet" {{ $prompt->model === 'claude-3-sonnet' ? 'selected' : '' }}>Claude 3 Sonnet</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Temperature') }} <small class="text-muted">(0.0 - 2.0)</small></label>
                            <input type="number" name="temperature" class="form-control" value="{{ old('temperature', $prompt->temperature) }}" min="0" max="2" step="0.1">
                            <small class="text-muted">{{ __('Lower = more focused, Higher = more creative') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Max Tokens') }}</label>
                            <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', $prompt->max_tokens) }}" min="100" max="100000">
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ $prompt->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">{{ __('Active') }}</label>
                        </div>
                    </div>
                </div>

                <!-- Test Prompt -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('Test Prompt') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">{{ __('Preview how the prompt will look with sample variables.') }}</p>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="previewBtn">
                            <i class="fa fa-eye me-1"></i> {{ __('Preview Compiled') }}
                        </button>
                    </div>
                </div>

                <!-- Recent History -->
                @if($history->count() > 0)
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">{{ __('Recent Versions') }}</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach($history->take(5) as $h)
                                <div class="list-group-item small">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary">v{{ $h->version }}</span>
                                        <span class="text-muted">{{ $h->created_at->format('M d, Y') }}</span>
                                    </div>
                                    @if($h->change_notes)
                                        <small class="text-muted d-block mt-1">{{ $h->change_notes }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Save Button -->
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save me-1"></i> {{ __('Save Changes') }}
                    </button>
                    <a href="{{ route('admin.video-wizard.prompts.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Compiled Prompt Preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="previewContent" class="bg-light p-3 rounded" style="white-space: pre-wrap; max-height: 500px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('previewBtn').addEventListener('click', function() {
    const template = document.getElementById('promptTemplate').value;

    // Simple variable replacement with sample values
    const sampleVars = {
        topic: 'How to cook pasta',
        tone: 'engaging',
        toneGuide: 'conversational, energetic, keeps viewers hooked',
        contentDepth: 'detailed',
        depthGuide: 'Include examples and supporting details',
        duration: 60,
        minutes: 1,
        targetWords: 140,
        sceneCount: 4,
        wordsPerScene: 35,
        avgSceneDuration: 15,
        additionalInstructions: ''
    };

    let compiled = template;
    for (const [key, value] of Object.entries(sampleVars)) {
        compiled = compiled.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), value);
    }

    document.getElementById('previewContent').textContent = compiled;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
});
</script>
@endpush
@endsection
