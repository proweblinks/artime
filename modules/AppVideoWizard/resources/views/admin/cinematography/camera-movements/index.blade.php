@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.cinematography.index') }}">{{ __('Cinematography') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Camera Movements') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Camera Movements') }}</div>
                <p class="text-muted mb-0 small">{{ __('Motion Intelligence: Professional camera movement presets for AI video animation') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.video-wizard.cinematography.camera-movements.export') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-download me-1"></i> {{ __('Export') }}
                </a>
                <form action="{{ route('admin.video-wizard.cinematography.camera-movements.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('This will add/update default movements. Continue?')">
                        <i class="fa fa-magic me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.cinematography.camera-movements.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('New Movement') }}
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats & Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-30 fw-bold text-primary">{{ $stats['total'] }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Total Movements') }}</div>
                            <div class="text-success small">{{ $stats['active'] }} {{ __('active') }}</div>
                        </div>
                    </div>
                    @if(!empty($stats['byCategory']))
                        <div class="mt-2">
                            @foreach($stats['byCategory'] as $cat => $count)
                                <span class="badge bg-light text-dark me-1">{{ ucfirst(str_replace('_', ' ', $cat)) }}: {{ $count }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <form class="row g-2" method="GET">
                        <div class="col-auto">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach($categories as $value => $label)
                                    <option value="{{ $value }}" {{ request('category') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="intensity" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Intensities') }}</option>
                                @foreach($intensities as $value => $label)
                                    <option value="{{ $value }}" {{ request('intensity') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Status') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search movements...') }}" value="{{ request('search') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            @if(request()->hasAny(['category', 'intensity', 'status', 'search']))
                                <a href="{{ route('admin.video-wizard.cinematography.camera-movements') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>{{ __('Movement') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Prompt Syntax') }}</th>
                        <th>{{ __('Intensity') }}</th>
                        <th>{{ __('Seedance') }}</th>
                        <th>{{ __('Duration') }}</th>
                        <th>{{ __('Stackable') }}</th>
                        <th style="width: 100px;">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr class="{{ !$movement->is_active ? 'opacity-50' : '' }}">
                            <td class="align-middle">
                                <form action="{{ route('admin.video-wizard.cinematography.camera-movements.toggle', $movement) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-link p-0" title="{{ $movement->is_active ? __('Deactivate') : __('Activate') }}">
                                        <i class="fa fa-{{ $movement->is_active ? 'check-circle text-success' : 'circle text-muted' }}"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="align-middle">
                                <div class="fw-semibold">{{ $movement->name }}</div>
                                <code class="small text-muted">{{ $movement->slug }}</code>
                                @if($movement->description)
                                    <div class="small text-muted mt-1">{{ Str::limit($movement->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-{{ $movement->category == 'zoom' ? 'primary' : ($movement->category == 'dolly' ? 'success' : ($movement->category == 'crane' ? 'info' : ($movement->category == 'pan_tilt' ? 'warning' : ($movement->category == 'arc' ? 'danger' : 'secondary')))) }}">
                                    {{ $movement->getCategoryLabel() }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <code class="small">{{ Str::limit($movement->prompt_syntax, 40) }}</code>
                            </td>
                            <td class="align-middle">
                                @php
                                    $intensityColors = [
                                        'subtle' => 'secondary',
                                        'moderate' => 'info',
                                        'dynamic' => 'warning',
                                        'intense' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $intensityColors[$movement->intensity] ?? 'secondary' }}">
                                    {{ $movement->getIntensityLabel() }}
                                </span>
                            </td>
                            <td class="align-middle">
                                @if($movement->seedance_compatible)
                                    <span class="badge bg-success" title="{{ $movement->seedance_prompt_syntax }}">
                                        <i class="fa fa-check me-1"></i>{{ __('Compatible') }}
                                    </span>
                                    @if($movement->seedance_shot_size)
                                        <div class="small text-muted mt-1">{{ $movement->seedance_shot_size }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fa fa-exclamation-triangle me-1"></i>{{ __('v1.5 only') }}
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <small class="text-muted">
                                    {{ $movement->typical_duration_min ?? '?' }}-{{ $movement->typical_duration_max ?? '?' }}s
                                </small>
                            </td>
                            <td class="align-middle">
                                @php
                                    // Ensure stackable_with is always an array (handles legacy string data)
                                    $rawStackable = $movement->stackable_with;
                                    $stackable = is_array($rawStackable)
                                        ? $rawStackable
                                        : (is_string($rawStackable) ? (json_decode($rawStackable, true) ?? []) : []);
                                    $stackCount = count($stackable);
                                @endphp
                                @if($stackCount > 0)
                                    <span class="badge bg-light text-dark" title="{{ implode(', ', $stackable) }}">
                                        <i class="fa fa-layer-group me-1"></i>{{ $stackCount }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.video-wizard.cinematography.camera-movements.edit', $movement) }}">
                                                <i class="fa fa-edit me-2"></i> {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="testPrompt({{ $movement->id }}, '{{ $movement->slug }}')">
                                                <i class="fa fa-play me-2"></i> {{ __('Test Prompt') }}
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.video-wizard.cinematography.camera-movements.destroy', $movement) }}" method="POST" onsubmit="return confirm('{{ __('Delete this movement?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fa fa-trash me-2"></i> {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fa fa-video-camera fs-40 text-muted opacity-50 mb-3 d-block"></i>
                                <p class="text-muted mb-3">{{ __('No camera movements found.') }}</p>
                                <a href="{{ route('admin.video-wizard.cinematography.camera-movements.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus me-1"></i> {{ __('Create First Movement') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($movements->hasPages())
        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    @endif

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.video-wizard.cinematography.camera-movements.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Import Camera Movements') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('JSON File') }}</label>
                            <input type="file" name="file" class="form-control" accept=".json" required>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="fa fa-info-circle me-1"></i>
                            {{ __('Existing movements with the same slug will be updated.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Test Prompt Modal -->
    <div class="modal fade" id="testPromptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Test Movement Prompt') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Primary Movement') }}</label>
                        <input type="text" id="testPrimaryMovement" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Secondary Movement (Stack)') }}</label>
                        <select id="testSecondaryMovement" class="form-select">
                            <option value="">{{ __('None') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Intensity') }}</label>
                        <select id="testIntensity" class="form-select">
                            @foreach($intensities as $value => $label)
                                <option value="{{ $value }}" {{ $value == 'moderate' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary mb-3" onclick="runPromptTest()">
                        <i class="fa fa-play me-1"></i> {{ __('Generate Prompt') }}
                    </button>
                    <div id="testResult" class="d-none">
                        <label class="form-label">{{ __('Generated Prompt') }}</label>
                        <pre class="bg-light p-3 rounded small" id="generatedPrompt"></pre>
                        <div class="row small text-muted">
                            <div class="col">{{ __('Stacked:') }} <span id="stackedResult"></span></div>
                            <div class="col">{{ __('Intensity:') }} <span id="intensityResult"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentMovementId = null;
let currentMovementSlug = null;

function testPrompt(id, slug) {
    currentMovementId = id;
    currentMovementSlug = slug;
    document.getElementById('testPrimaryMovement').value = slug;
    document.getElementById('testResult').classList.add('d-none');

    // Fetch stackable movements
    fetch(`{{ url('admin/video-wizard/cinematography/camera-movements') }}/${id}/stackable`)
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('testSecondaryMovement');
            select.innerHTML = '<option value="">{{ __("None") }}</option>';
            if (data.stackable) {
                data.stackable.forEach(m => {
                    select.innerHTML += `<option value="${m.slug}">${m.name}</option>`;
                });
            }
        });

    new bootstrap.Modal(document.getElementById('testPromptModal')).show();
}

function runPromptTest() {
    const secondary = document.getElementById('testSecondaryMovement').value;
    const intensity = document.getElementById('testIntensity').value;

    fetch(`{{ url('admin/video-wizard/cinematography/camera-movements') }}/${currentMovementId}/test-prompt`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            secondary_movement: secondary,
            intensity: intensity
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('generatedPrompt').textContent = data.prompt;
            document.getElementById('stackedResult').textContent = data.stacked ? 'Yes' : 'No';
            document.getElementById('intensityResult').textContent = data.intensity;
            document.getElementById('testResult').classList.remove('d-none');
        }
    });
}
</script>
@endpush
@endsection
