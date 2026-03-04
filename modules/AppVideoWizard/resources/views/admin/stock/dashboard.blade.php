@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ url('admin') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Stock Library</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Stock Media Library</div>
                <p class="text-muted mb-0 small">Manage your curated stock media collection</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stock-media.browse') }}" class="btn btn-outline-secondary">
                    <i class="fa-light fa-grid-2 me-1"></i> Browse
                </a>
                <a href="{{ route('admin.stock-media.upload') }}" class="btn btn-primary">
                    <i class="fa-light fa-cloud-arrow-up me-1"></i> Upload
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

    {{-- Stat Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fa-light fa-photo-film fa-2x text-primary"></i>
                        </div>
                        <span class="badge bg-primary">Total</span>
                    </div>
                    <div class="fs-30 fw-bold text-primary">{{ number_format($totalItems) }}</div>
                    <p class="text-muted small mb-0">Total Media Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fa-light fa-image fa-2x text-success"></i>
                        </div>
                        <span class="badge bg-success">Images</span>
                    </div>
                    <div class="fs-30 fw-bold text-success">{{ number_format($totalImages) }}</div>
                    <p class="text-muted small mb-0">Image Files</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fa-light fa-video fa-2x text-info"></i>
                        </div>
                        <span class="badge bg-info">Videos</span>
                    </div>
                    <div class="fs-30 fw-bold text-info">{{ number_format($totalVideos) }}</div>
                    <p class="text-muted small mb-0">Video Files</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fa-light fa-folder-tree fa-2x text-warning"></i>
                        </div>
                        <span class="badge bg-warning text-dark">Categories</span>
                    </div>
                    <div class="fs-30 fw-bold text-warning">{{ $categories->count() }}</div>
                    <p class="text-muted small mb-0">
                        <span class="text-success">{{ $activeCount }}</span> active,
                        <span class="text-danger">{{ $inactiveCount }}</span> inactive
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Category Breakdown --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Category Breakdown</h6>
                    <a href="{{ route('admin.stock-media.categories') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Images</th>
                                <th class="text-center">Videos</th>
                                <th class="text-center">Total</th>
                                <th class="text-end">Storage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryStats as $cat)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.stock-media.browse', ['category' => $cat->category]) }}" class="text-decoration-none">
                                            <i class="fa-light fa-folder text-warning me-1"></i>
                                            {{ ucwords(str_replace('-', ' ', $cat->category)) }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $cat->images }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $cat->videos }}</span>
                                    </td>
                                    <td class="text-center fw-bold">{{ $cat->total }}</td>
                                    <td class="text-end text-muted small">
                                        @if($cat->storage_size > 1073741824)
                                            {{ number_format($cat->storage_size / 1073741824, 1) }} GB
                                        @elseif($cat->storage_size > 1048576)
                                            {{ number_format($cat->storage_size / 1048576, 1) }} MB
                                        @else
                                            {{ number_format($cat->storage_size / 1024, 0) }} KB
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No categories found. <a href="{{ route('admin.stock-media.upload') }}">Upload media</a> to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Additions --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Additions</h6>
                    <a href="{{ route('admin.stock-media.browse') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($recentItems as $item)
                        <div class="d-flex align-items-center px-3 py-2 border-bottom">
                            <div class="flex-shrink-0 me-3" style="width: 48px; height: 48px;">
                                <img src="{{ $item->getThumbnailUrl() }}"
                                     alt="{{ $item->title }}"
                                     class="rounded"
                                     style="width: 48px; height: 48px; object-fit: cover;"
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2248%22 height=%2248%22><rect fill=%22%23ddd%22 width=%2248%22 height=%2248%22/></svg>'">
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="text-truncate small fw-medium">{{ $item->title }}</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <span class="badge bg-{{ $item->type === 'image' ? 'success' : 'info' }}" style="font-size: 10px;">{{ $item->type }}</span>
                                    {{ ucwords(str_replace('-', ' ', $item->category)) }}
                                </div>
                            </div>
                            <a href="{{ route('admin.stock-media.edit', $item) }}" class="btn btn-sm btn-link text-muted">
                                <i class="fa-light fa-pen"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">No items yet</div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.stock-media.upload') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa-light fa-cloud-arrow-up me-1"></i> Upload Media
                        </a>
                        <a href="{{ route('admin.stock-media.browse') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-light fa-grid-2 me-1"></i> Browse All Media
                        </a>
                        <form action="{{ route('admin.stock-media.reindex') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-sm w-100"
                                    onclick="return confirm('Run stock media reindex?')">
                                <i class="fa-light fa-arrows-rotate me-1"></i> Reindex Files
                            </button>
                        </form>
                        <form action="{{ route('admin.stock-media.reindex') }}" method="POST">
                            @csrf
                            <input type="hidden" name="clean" value="1">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                    onclick="return confirm('Remove orphaned entries?')">
                                <i class="fa-light fa-broom me-1"></i> Clean Orphaned
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
