@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.dashboard') }}">Stock Library</a></li>
                        <li class="breadcrumb-item active">Browse Media</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Browse Media</div>
                <p class="text-muted mb-0 small">{{ $items->total() }} items found</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stock-media.browse', array_merge(request()->query(), ['view' => 'grid'])) }}"
                   class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="fa-light fa-grid-2"></i>
                </a>
                <a href="{{ route('admin.stock-media.browse', array_merge(request()->query(), ['view' => 'table'])) }}"
                   class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="fa-light fa-list"></i>
                </a>
                <a href="{{ route('admin.stock-media.upload') }}" class="btn btn-primary btn-sm">
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
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.stock-media.browse') }}" class="row g-2 align-items-end">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search title, tags..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                {{ ucwords(str_replace('-', ' ', $cat)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                        <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Videos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Orientation</label>
                    <select name="orientation" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="landscape" {{ request('orientation') === 'landscape' ? 'selected' : '' }}>Landscape</option>
                        <option value="portrait" {{ request('orientation') === 'portrait' ? 'selected' : '' }}>Portrait</option>
                        <option value="square" {{ request('orientation') === 'square' ? 'selected' : '' }}>Square</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="fa-light fa-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.stock-media.browse', ['view' => $viewMode]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-light fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    <div id="bulk-bar" class="card border-0 shadow-sm mb-3 d-none" x-data="{ action: '' }">
        <div class="card-body py-2">
            <form method="POST" action="{{ route('admin.stock-media.bulk-action') }}" class="d-flex align-items-center gap-2"
                  id="bulk-form">
                @csrf
                <span class="text-muted small"><span id="bulk-count">0</span> selected</span>
                <select name="action" class="form-select form-select-sm" style="width: 160px;" x-model="action">
                    <option value="">Choose action...</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="move">Move Category</option>
                    <option value="delete">Delete</option>
                </select>
                <template x-if="action === 'move'">
                    <select name="target_category" class="form-select form-select-sm" style="width: 160px;">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ ucwords(str_replace('-', ' ', $cat)) }}</option>
                        @endforeach
                    </select>
                </template>
                <button type="submit" class="btn btn-sm btn-primary"
                        onclick="return confirm('Apply this action to selected items?')">Apply</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearBulk()">Cancel</button>
            </form>
        </div>
    </div>

    @if($viewMode === 'grid')
        {{-- Grid View --}}
        <div class="row g-3">
            @forelse($items as $item)
                @include('appvideowizard::admin.stock._media-card', ['item' => $item])
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fa-light fa-photo-film fs-40 text-muted opacity-50 mb-3 d-block"></i>
                        <p class="text-muted">No media items found.</p>
                        <a href="{{ route('admin.stock-media.upload') }}" class="btn btn-primary btn-sm">Upload Media</a>
                    </div>
                </div>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th style="width: 60px;">Thumb</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Dimensions</th>
                            <th>Duration</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="{{ !$item->is_active ? 'opacity-50' : '' }}">
                                <td class="align-middle">
                                    <input type="checkbox" name="bulk_ids[]" value="{{ $item->id }}" class="form-check-input bulk-checkbox">
                                </td>
                                <td class="align-middle">
                                    <img src="{{ $item->getThumbnailUrl() }}" alt=""
                                         class="rounded" style="width: 48px; height: 36px; object-fit: cover;"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2248%22 height=%2236%22><rect fill=%22%23ddd%22 width=%2248%22 height=%2236%22/></svg>'">
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('admin.stock-media.edit', $item) }}" class="text-decoration-none">
                                        <div class="text-truncate" style="max-width: 200px;">{{ $item->title }}</div>
                                    </a>
                                    <div class="text-muted" style="font-size: 10px;">{{ $item->filename }}</div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                        {{ ucwords(str_replace('-', ' ', $item->category)) }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-{{ $item->type === 'image' ? 'success' : 'info' }}">{{ $item->type }}</span>
                                </td>
                                <td class="align-middle small text-muted">{{ $item->width }}x{{ $item->height }}</td>
                                <td class="align-middle small text-muted">
                                    {{ $item->duration ? gmdate('i:s', (int)$item->duration) : '-' }}
                                </td>
                                <td class="align-middle small text-muted">
                                    @if($item->file_size > 1048576)
                                        {{ number_format($item->file_size / 1048576, 1) }} MB
                                    @else
                                        {{ number_format($item->file_size / 1024, 0) }} KB
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <form action="{{ route('admin.stock-media.toggle', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link p-0">
                                            <i class="fa fa-{{ $item->is_active ? 'check-circle text-success' : 'circle text-muted' }}"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="align-middle">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('admin.stock-media.edit', $item) }}"><i class="fa-light fa-pen me-2"></i>Edit</a></li>
                                            <li>
                                                <form action="{{ route('admin.stock-media.toggle', $item) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item">
                                                        <i class="fa-light fa-{{ $item->is_active ? 'eye-slash' : 'eye' }} me-2"></i>
                                                        {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.stock-media.destroy', $item) }}" method="POST"
                                                      onsubmit="return confirm('Delete this item?')">
                                                    @csrf @method('DELETE')
                                                    <button class="dropdown-item text-danger"><i class="fa-light fa-trash me-2"></i>Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fa-light fa-photo-film fs-40 text-muted opacity-50 mb-3 d-block"></i>
                                    <p class="text-muted">No media items found.</p>
                                    <a href="{{ route('admin.stock-media.upload') }}" class="btn btn-primary btn-sm">Upload Media</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    @if($items->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $items->links() }}
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulkBar = document.getElementById('bulk-bar');
    const bulkCount = document.getElementById('bulk-count');
    const bulkForm = document.getElementById('bulk-form');
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.bulk-checkbox');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.bulk-checkbox:checked');
        if (checked.length > 0) {
            bulkBar.classList.remove('d-none');
            bulkCount.textContent = checked.length;

            // Sync hidden inputs to bulk form
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                bulkForm.appendChild(input);
            });
        } else {
            bulkBar.classList.add('d-none');
        }
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkBar));

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateBulkBar();
        });
    }

    window.clearBulk = function() {
        checkboxes.forEach(cb => { cb.checked = false; });
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };
});
</script>
@endsection
