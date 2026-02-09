@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.creator-hub.settings') }}">{{ __('Creator Hub') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Settings') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Creator Hub Settings') }}</div>
    </div>
</div>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- YouTube API Keys --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-brands fa-youtube text-danger me-2"></i>{{ __('YouTube API Keys') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ __('Add multiple YouTube Data API v3 keys for automatic rotation. Keys rotate after each API call to avoid daily quota limits (10,000 units/day per key).') }}</p>

                    <form action="{{ route('admin.creator-hub.youtube-keys.save') }}" method="POST" id="youtubeKeysForm">
                        @csrf
                        <div id="keysContainer">
                            @forelse($youtubeKeys as $index => $keyData)
                                <div class="key-row border rounded p-3 mb-3" data-index="{{ $index }}">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label small">{{ __('Label') }}</label>
                                            <input type="text" name="keys[{{ $index }}][label]" class="form-control form-control-sm" value="{{ $keyData['label'] }}" placeholder="Key {{ $index + 1 }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small">{{ __('API Key') }}</label>
                                            <input type="text" name="keys[{{ $index }}][key]" class="form-control form-control-sm font-monospace" value="{{ $keyData['key'] }}" placeholder="AIza..." required>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check form-switch mt-2">
                                                <input type="hidden" name="keys[{{ $index }}][active]" value="0">
                                                <input type="checkbox" name="keys[{{ $index }}][active]" class="form-check-input" value="1" {{ ($keyData['active'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label small">{{ __('Active') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 test-key-btn" data-index="{{ $index }}" title="{{ __('Test Key') }}">
                                                <i class="fa-light fa-flask-vial"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-key-btn" title="{{ __('Remove') }}">
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="test-result mt-2" style="display:none;"></div>
                                </div>
                            @empty
                                <div class="text-muted text-center py-3" id="noKeysMessage">
                                    {{ __('No API keys configured. Add your first key below.') }}
                                </div>
                            @endforelse
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addKeyBtn">
                                <i class="fa-light fa-plus me-1"></i>{{ __('Add Key') }}
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-light fa-floppy-disk me-1"></i>{{ __('Save Keys') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- General Settings --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa-light fa-gear me-2"></i>{{ __('General Settings') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.creator-hub.settings.general') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">{{ __('Key Rotation Mode') }}</label>
                            <select name="rotation_mode" class="form-select form-select-sm">
                                <option value="round-robin" {{ $rotationMode === 'round-robin' ? 'selected' : '' }}>{{ __('Round Robin (sequential)') }}</option>
                                <option value="random" {{ $rotationMode === 'random' ? 'selected' : '' }}>{{ __('Random') }}</option>
                            </select>
                            <div class="form-text">{{ __('How keys are selected for each API call.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">{{ __('Default Platform') }}</label>
                            <select name="default_platform" class="form-select form-select-sm">
                                <option value="youtube" {{ $defaultPlatform === 'youtube' ? 'selected' : '' }}>YouTube</option>
                                <option value="tiktok" {{ $defaultPlatform === 'tiktok' ? 'selected' : '' }}>TikTok</option>
                                <option value="instagram" {{ $defaultPlatform === 'instagram' ? 'selected' : '' }}>Instagram</option>
                                <option value="linkedin" {{ $defaultPlatform === 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                                <option value="general" {{ $defaultPlatform === 'general' ? 'selected' : '' }}>Multi-Platform</option>
                            </select>
                            <div class="form-text">{{ __('Pre-selected platform for AI tools.') }}</div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fa-light fa-floppy-disk me-1"></i>{{ __('Save Settings') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Key Status --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa-light fa-circle-info me-2"></i>{{ __('Key Status') }}</h5>
                </div>
                <div class="card-body">
                    @php
                        $activeCount = collect($youtubeKeys)->where('active', true)->count();
                        $totalCount = count($youtubeKeys);
                    @endphp
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">{{ __('Total Keys') }}</span>
                        <span class="badge bg-secondary">{{ $totalCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">{{ __('Active Keys') }}</span>
                        <span class="badge bg-success">{{ $activeCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">{{ __('Daily Quota Est.') }}</span>
                        <span class="badge bg-primary">{{ number_format($activeCount * 10000) }} {{ __('units') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let keyIndex = {{ count($youtubeKeys) }};

    // Add key
    document.getElementById('addKeyBtn').addEventListener('click', function() {
        const noMsg = document.getElementById('noKeysMessage');
        if (noMsg) noMsg.remove();

        const html = `
            <div class="key-row border rounded p-3 mb-3" data-index="${keyIndex}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Label</label>
                        <input type="text" name="keys[${keyIndex}][label]" class="form-control form-control-sm" value="Key ${keyIndex + 1}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">API Key</label>
                        <input type="text" name="keys[${keyIndex}][key]" class="form-control form-control-sm font-monospace" placeholder="AIza..." required>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="keys[${keyIndex}][active]" value="0">
                            <input type="checkbox" name="keys[${keyIndex}][active]" class="form-check-input" value="1" checked>
                            <label class="form-check-label small">Active</label>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 test-key-btn" data-index="${keyIndex}" title="Test Key">
                            <i class="fa-light fa-flask-vial"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-key-btn" title="Remove">
                            <i class="fa-light fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="test-result mt-2" style="display:none;"></div>
            </div>`;
        document.getElementById('keysContainer').insertAdjacentHTML('beforeend', html);
        keyIndex++;
    });

    // Remove key
    document.getElementById('keysContainer').addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-key-btn');
        if (removeBtn) {
            removeBtn.closest('.key-row').remove();
        }
    });

    // Test key
    document.getElementById('keysContainer').addEventListener('click', function(e) {
        const testBtn = e.target.closest('.test-key-btn');
        if (!testBtn) return;

        const row = testBtn.closest('.key-row');
        const keyInput = row.querySelector('input[name*="[key]"]');
        const resultDiv = row.querySelector('.test-result');

        if (!keyInput.value) return;

        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fa-light fa-spinner fa-spin"></i>';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<small class="text-muted">Testing...</small>';

        fetch('{{ route("admin.creator-hub.youtube-keys.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ key: keyInput.value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<small class="text-success"><i class="fa-light fa-check-circle me-1"></i>' + data.message + '</small>';
            } else {
                resultDiv.innerHTML = '<small class="text-danger"><i class="fa-light fa-circle-xmark me-1"></i>' + data.message + '</small>';
            }
        })
        .catch(() => {
            resultDiv.innerHTML = '<small class="text-danger">Connection error</small>';
        })
        .finally(() => {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fa-light fa-flask-vial"></i>';
        });
    });
});
</script>
@endsection
