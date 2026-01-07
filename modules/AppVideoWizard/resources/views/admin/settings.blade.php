@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Settings') }}</li>
            </ol>
        </nav>
        <div class="fw-7 fs-20 text-primary-700">{{ __('Video Creator Settings') }}</div>
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
        <div class="col-lg-6">
            <!-- Credit Costs -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Credit Costs') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('These values are configured in config/appvideowizard.php') }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Operation') }}</th>
                                    <th class="text-end">{{ __('Credits') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($config['credit_costs'] ?? [] as $key => $cost)
                                    <tr>
                                        <td>{{ Str::title(str_replace('_', ' ', $key)) }}</td>
                                        <td class="text-end"><span class="badge bg-primary">{{ $cost }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- AI Models -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('AI Models') }}</h5>
                </div>
                <div class="card-body">
                    @foreach($config['ai_models'] ?? [] as $type => $model)
                        <div class="mb-3">
                            <label class="form-label small text-muted">{{ Str::title($type) }}</label>
                            <div class="d-flex gap-2">
                                <span class="badge bg-secondary">{{ $model['provider'] ?? 'N/A' }}</span>
                                <span class="badge bg-info">{{ $model['model'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Available Voices -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Available Voices') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Voice') }}</th>
                                    <th>{{ __('Gender') }}</th>
                                    <th>{{ __('Style') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($config['voices'] ?? [] as $id => $voice)
                                    <tr>
                                        <td>
                                            <span class="me-2">{{ $voice['icon'] ?? '' }}</span>
                                            {{ $voice['name'] }}
                                        </td>
                                        <td class="small">{{ $voice['gender'] ?? '-' }}</td>
                                        <td class="small">{{ $voice['style'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transitions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('Scene Transitions') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Transition') }}</th>
                                    <th class="text-end">{{ __('Duration') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($config['transitions'] ?? [] as $id => $transition)
                                    <tr>
                                        <td>
                                            <span class="me-2">{{ $transition['icon'] ?? '' }}</span>
                                            {{ $transition['name'] }}
                                        </td>
                                        <td class="text-end">{{ $transition['duration'] }}s</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
