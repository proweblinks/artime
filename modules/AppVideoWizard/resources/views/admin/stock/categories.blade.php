@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.dashboard') }}">Stock Library</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Categories</div>
                <p class="text-muted mb-0 small">Manage media categories and folders</p>
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
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">All Categories ({{ $categoryStats->count() }})</h6>
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
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryStats as $cat)
                                <tr>
                                    <td class="align-middle">
                                        <a href="{{ route('admin.stock-media.browse', ['category' => $cat->category]) }}" class="text-decoration-none">
                                            <i class="fa-light fa-folder text-warning me-2"></i>
                                            <strong>{{ ucwords(str_replace('-', ' ', $cat->category)) }}</strong>
                                        </a>
                                        <div class="text-muted" style="font-size: 11px; margin-left: 24px;">{{ $cat->category }}/</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $cat->images }}</span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $cat->videos }}</span>
                                    </td>
                                    <td class="text-center align-middle fw-bold">{{ $cat->total }}</td>
                                    <td class="text-end align-middle text-muted small">
                                        @if($cat->storage_size > 1073741824)
                                            {{ number_format($cat->storage_size / 1073741824, 1) }} GB
                                        @elseif($cat->storage_size > 1048576)
                                            {{ number_format($cat->storage_size / 1048576, 1) }} MB
                                        @else
                                            {{ number_format(($cat->storage_size ?? 0) / 1024, 0) }} KB
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#renameModal"
                                                    onclick="document.getElementById('rename-old').value='{{ $cat->category }}'; document.getElementById('rename-new').value='{{ $cat->category }}';">
                                                <i class="fa-light fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    onclick="document.getElementById('delete-category').value='{{ $cat->category }}'; document.getElementById('delete-label').textContent='{{ ucwords(str_replace('-', ' ', $cat->category)) }}';">
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-light fa-folder-tree fs-40 opacity-50 mb-3 d-block"></i>
                                        No categories found. Upload media to create categories.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Disk Folders --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Disk Folders</h6>
                </div>
                <div class="card-body p-0">
                    @php
                        $dbCategories = $categoryStats->pluck('category')->toArray();
                    @endphp
                    @forelse($diskCategories as $dir)
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small">
                                <i class="fa-light fa-folder text-warning me-1"></i> {{ $dir }}
                            </span>
                            @if(!in_array($dir, $dbCategories))
                                <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 10px;">Not indexed</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 10px;">Indexed</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">No folders found</div>
                    @endforelse
                </div>
                <div class="card-footer bg-transparent">
                    <form action="{{ route('admin.stock-media.reindex') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100"
                                onclick="return confirm('Index all unindexed folders?')">
                            <i class="fa-light fa-arrows-rotate me-1"></i> Reindex All
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Rename Modal --}}
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.stock-media.categories.update') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Rename Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="old_name" id="rename-old">
                    <div class="mb-3">
                        <label class="form-label">New Name</label>
                        <input type="text" name="new_name" id="rename-new" class="form-control" required>
                        <div class="form-text">Use lowercase with hyphens (e.g. art-craft)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" x-data="{ deleteMode: 'reassign' }">
            <form id="delete-form" method="POST"
                  onsubmit="return confirm('Are you sure? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h6 class="modal-title">Delete Category: <span id="delete-label"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Choose what to do with the items in this category:</p>

                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" value="reassign"
                                   id="mode-reassign" x-model="deleteMode">
                            <label class="form-check-label" for="mode-reassign">
                                Reassign items to another category
                            </label>
                        </div>
                        <div x-show="deleteMode === 'reassign'" class="ms-4 mb-3">
                            <select name="reassign_to" class="form-select form-select-sm">
                                @foreach($categoryStats as $cat)
                                    <option value="{{ $cat->category }}">{{ ucwords(str_replace('-', ' ', $cat->category)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" value="delete_all"
                                   id="mode-delete" x-model="deleteMode">
                            <label class="form-check-label text-danger" for="mode-delete">
                                Delete all items permanently
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="delete-category">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function() {
        setTimeout(function() {
            const cat = document.getElementById('delete-category').value;
            const form = document.getElementById('delete-form');
            form.action = '{{ url("admin/stock-media/categories") }}/' + encodeURIComponent(cat);
        }, 10);
    });
});
</script>
@endsection
