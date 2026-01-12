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
                        <li class="breadcrumb-item active">{{ __('Coverage Patterns') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Coverage Patterns') }}</div>
                <p class="text-muted mb-0 small">{{ __('Scene type detection and professional shot sequence patterns') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.video-wizard.cinematography.coverage-patterns.export') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-download me-1"></i> {{ __('Export') }}
                </a>
                <form action="{{ route('admin.video-wizard.cinematography.coverage-patterns.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('This will add/update default patterns. Continue?')">
                        <i class="fa fa-magic me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <a href="{{ route('admin.video-wizard.cinematography.coverage-patterns.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('New Pattern') }}
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
                            <div class="text-muted small">{{ __('Total Patterns') }}</div>
                            <div class="text-success small">{{ $stats['active'] }} {{ __('active') }}</div>
                        </div>
                    </div>
                    @if(!empty($stats['byType']))
                        <div class="mt-2">
                            @foreach($stats['byType'] as $type => $count)
                                <span class="badge bg-light text-dark me-1">{{ ucfirst($type) }}: {{ $count }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <form class="row g-2" method="GET">
                        <div class="col-auto">
                            <select name="scene_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('All Scene Types') }}</option>
                                @foreach($sceneTypes as $value => $label)
                                    <option value="{{ $value }}" {{ request('scene_type') == $value ? 'selected' : '' }}>
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
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search patterns...') }}" value="{{ request('search') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            @if(request()->hasAny(['scene_type', 'status', 'search']))
                                <a href="{{ route('admin.video-wizard.cinematography.coverage-patterns.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Detection Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h6 class="mb-0"><i class="fa fa-flask me-2"></i>{{ __('Test Pattern Detection') }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <textarea id="testText" class="form-control" rows="2" placeholder="{{ __('Enter scene narration or description to test detection...') }}"></textarea>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100 h-100" onclick="testDetection()">
                        <i class="fa fa-magnifying-glass me-1"></i> {{ __('Test Detection') }}
                    </button>
                </div>
            </div>
            <div id="detectionResult" class="mt-3 d-none">
                <div class="alert alert-info mb-0">
                    <strong>{{ __('Best Match:') }}</strong> <span id="bestMatchName"></span>
                    <span class="badge bg-primary ms-2" id="bestMatchScore"></span>
                    <br>
                    <small class="text-muted" id="bestMatchType"></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Patterns Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>{{ __('Pattern') }}</th>
                        <th>{{ __('Scene Type') }}</th>
                        <th>{{ __('Shot Sequence') }}</th>
                        <th>{{ __('Pacing') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Usage') }}</th>
                        <th style="width: 100px;">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patterns as $pattern)
                        <tr class="{{ !$pattern->is_active ? 'opacity-50' : '' }}">
                            <td class="align-middle">
                                <form action="{{ route('admin.video-wizard.cinematography.coverage-patterns.toggle', $pattern) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-link p-0" title="{{ $pattern->is_active ? __('Deactivate') : __('Activate') }}">
                                        <i class="fa fa-{{ $pattern->is_active ? 'check-circle text-success' : 'circle text-muted' }}"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="align-middle">
                                <div class="fw-semibold">{{ $pattern->name }}</div>
                                <code class="small text-muted">{{ $pattern->slug }}</code>
                                @if($pattern->is_system)
                                    <span class="badge bg-secondary ms-1">{{ __('System') }}</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @php
                                    $typeColors = [
                                        'dialogue' => 'primary',
                                        'action' => 'danger',
                                        'emotional' => 'info',
                                        'montage' => 'warning',
                                        'establishing' => 'success',
                                        'flashback' => 'secondary',
                                        'dream' => 'dark',
                                        'interview' => 'info',
                                        'transition' => 'light',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $typeColors[$pattern->scene_type] ?? 'secondary' }}">
                                    {{ ucfirst($pattern->scene_type) }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach(array_slice($pattern->shot_sequence ?? [], 0, 4) as $shot)
                                        <span class="badge bg-light text-dark border">{{ ucwords(str_replace('-', ' ', $shot)) }}</span>
                                    @endforeach
                                    @if(count($pattern->shot_sequence ?? []) > 4)
                                        <span class="badge bg-light text-muted">+{{ count($pattern->shot_sequence) - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="text-muted small">{{ ucfirst($pattern->recommended_pacing) }}</span>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-{{ $pattern->priority >= 80 ? 'success' : ($pattern->priority >= 60 ? 'warning' : 'secondary') }}">
                                    {{ $pattern->priority }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <small class="text-muted">
                                    {{ $pattern->usage_count }} {{ __('uses') }}
                                    @if($pattern->success_rate)
                                        <br>{{ number_format($pattern->success_rate, 0) }}% {{ __('success') }}
                                    @endif
                                </small>
                            </td>
                            <td class="align-middle">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.video-wizard.cinematography.coverage-patterns.edit', $pattern) }}">
                                                <i class="fa fa-edit me-2"></i> {{ __('Edit') }}
                                            </a>
                                        </li>
                                        @if(!$pattern->is_system)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.video-wizard.cinematography.coverage-patterns.destroy', $pattern) }}" method="POST" onsubmit="return confirm('{{ __('Delete this pattern?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa fa-trash me-2"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fa fa-list-check fs-40 text-muted opacity-50 mb-3 d-block"></i>
                                <p class="text-muted mb-3">{{ __('No coverage patterns found.') }}</p>
                                <a href="{{ route('admin.video-wizard.cinematography.coverage-patterns.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus me-1"></i> {{ __('Create First Pattern') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($patterns->hasPages())
        <div class="mt-4">
            {{ $patterns->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function testDetection() {
    const text = document.getElementById('testText').value;
    if (!text.trim()) {
        alert('{{ __("Please enter some text to test.") }}');
        return;
    }

    fetch('{{ route("admin.video-wizard.cinematography.coverage-patterns.test-detection") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ text: text })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.bestMatch) {
            document.getElementById('bestMatchName').textContent = data.bestMatch.name;
            document.getElementById('bestMatchScore').textContent = data.bestMatch.score + '% confidence';
            document.getElementById('bestMatchType').textContent = 'Scene Type: ' + data.bestMatch.sceneType + ' | Shots: ' + data.bestMatch.shotSequence.join(' → ');
            document.getElementById('detectionResult').classList.remove('d-none');
        } else {
            document.getElementById('bestMatchName').textContent = 'No match found';
            document.getElementById('bestMatchScore').textContent = '';
            document.getElementById('bestMatchType').textContent = 'Try different text or adjust pattern keywords';
            document.getElementById('detectionResult').classList.remove('d-none');
        }
    })
    .catch(err => {
        alert('Error testing detection');
        console.error(err);
    });
}
</script>
@endpush
@endsection
