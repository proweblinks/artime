@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.dashboard') }}">Stock Library</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Stock Library Settings</div>
                <p class="text-muted mb-0 small">Configuration and maintenance tools</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stock-media.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fa-light fa-arrow-left me-1"></i> Back
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

    <div class="row g-4">
        {{-- Storage Info --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fa-light fa-hard-drive me-2"></i>Storage Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" style="width: 140px;">Storage Path</td>
                            <td><code class="small">{{ $stockRoot }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Items</td>
                            <td><strong>{{ number_format($totalItems) }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Size</td>
                            <td>
                                <strong>
                                    @if($totalSize > 1073741824)
                                        {{ number_format($totalSize / 1073741824, 2) }} GB
                                    @elseif($totalSize > 1048576)
                                        {{ number_format($totalSize / 1048576, 1) }} MB
                                    @else
                                        {{ number_format($totalSize / 1024, 0) }} KB
                                    @endif
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Public URL</td>
                            <td><code class="small">{{ url('/public/stock-media/') }}</code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pexels Integration --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fa-light fa-plug me-2"></i>Pexels Integration</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-{{ $hasPexels ? 'success' : 'secondary' }} me-2">
                            {{ $hasPexels ? 'Connected' : 'Not Configured' }}
                        </span>
                        <span class="text-muted small">
                            {{ $hasPexels ? 'Pexels API key is set' : 'Set API key to enable Pexels stock photos' }}
                        </span>
                    </div>
                    <form action="{{ route('admin.stock-media.settings.save') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Pexels API Key</label>
                            <input type="password" name="pexels_api_key" class="form-control form-control-sm"
                                   value="{{ $hasPexels ? '********' : '' }}"
                                   placeholder="Enter Pexels API key">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save API Key</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Maintenance Tools --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fa-light fa-wrench me-2"></i>Maintenance</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.stock-media.reindex') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100"
                                    onclick="return confirm('Scan stock-media directory and index new files?')">
                                <i class="fa-light fa-arrows-rotate me-2"></i> Reindex Stock Media
                                <span class="text-muted small d-block">Scan for new files and add to database</span>
                            </button>
                        </form>

                        <form action="{{ route('admin.stock-media.reindex') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="btn btn-outline-warning w-100"
                                    onclick="return confirm('Force reindex will update ALL existing entries. Continue?')">
                                <i class="fa-light fa-arrows-rotate me-2"></i> Force Reindex (All)
                                <span class="text-muted small d-block">Re-scan and update ALL files including existing</span>
                            </button>
                        </form>

                        <form action="{{ route('admin.stock-media.reindex') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="clean" value="1">
                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Remove DB entries for files that no longer exist on disk?')">
                                <i class="fa-light fa-broom me-2"></i> Clean Orphaned Entries
                                <span class="text-muted small d-block">Remove DB records for deleted files</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reindex Output --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fa-light fa-terminal me-2"></i>Command Output</h6>
                </div>
                <div class="card-body">
                    @if(session('reindex_output'))
                        <pre class="bg-dark text-light p-3 rounded small mb-0" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;">{{ session('reindex_output') }}</pre>
                    @else
                        <div class="text-center py-4 text-muted small">
                            <i class="fa-light fa-terminal fs-20 opacity-50 mb-2 d-block"></i>
                            Run a maintenance command to see output here
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
