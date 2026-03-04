@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.dashboard') }}">Stock Library</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.browse') }}">Browse</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Edit Media Item</div>
                <p class="text-muted mb-0 small">{{ $stockMedia->filename }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stock-media.browse') }}" class="btn btn-outline-secondary">
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Preview --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    @if($stockMedia->type === 'video')
                        <video controls class="w-100 rounded" style="max-height: 400px;">
                            <source src="{{ $stockMedia->getPublicUrl() }}" type="{{ $stockMedia->mime_type }}">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <img src="{{ $stockMedia->getPublicUrl() }}"
                             alt="{{ $stockMedia->title }}"
                             class="w-100 rounded"
                             style="max-height: 400px; object-fit: contain; background: #f8f9fa;">
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <h6 class="mb-3">File Information</h6>
                    <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                        <tr>
                            <td class="text-muted" style="width: 100px;">Filename</td>
                            <td class="text-break">{{ $stockMedia->filename }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Path</td>
                            <td class="text-break"><code class="small">{{ $stockMedia->path }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Checksum</td>
                            <td class="text-break"><code class="small">{{ Str::limit($stockMedia->checksum, 20) }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dimensions</td>
                            <td>{{ $stockMedia->width }} x {{ $stockMedia->height }} px</td>
                        </tr>
                        <tr>
                            <td class="text-muted">File Size</td>
                            <td>
                                @if($stockMedia->file_size > 1048576)
                                    {{ number_format($stockMedia->file_size / 1048576, 2) }} MB
                                @else
                                    {{ number_format($stockMedia->file_size / 1024, 0) }} KB
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">MIME Type</td>
                            <td>{{ $stockMedia->mime_type }}</td>
                        </tr>
                        @if($stockMedia->type === 'video')
                            <tr>
                                <td class="text-muted">Duration</td>
                                <td>{{ $stockMedia->duration ? number_format($stockMedia->duration, 2) . 's' : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">FPS</td>
                                <td>{{ $stockMedia->fps ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Created</td>
                            <td>{{ $stockMedia->created_at->format('M j, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Updated</td>
                            <td>{{ $stockMedia->updated_at->format('M j, Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Edit Form --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Edit Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.stock-media.update', $stockMedia) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" name="title" id="title" class="form-control"
                                   value="{{ old('title', $stockMedia->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $stockMedia->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags <span class="text-muted small">(comma-separated)</span></label>
                            <input type="text" name="tags" id="tags" class="form-control"
                                   value="{{ old('tags', $stockMedia->tags) }}"
                                   placeholder="nature, landscape, scenic">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $stockMedia->category) === $cat ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('-', ' ', $cat)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="orientation" class="form-label">Orientation</label>
                                <select name="orientation" id="orientation" class="form-select">
                                    <option value="landscape" {{ old('orientation', $stockMedia->orientation) === 'landscape' ? 'selected' : '' }}>Landscape</option>
                                    <option value="portrait" {{ old('orientation', $stockMedia->orientation) === 'portrait' ? 'selected' : '' }}>Portrait</option>
                                    <option value="square" {{ old('orientation', $stockMedia->orientation) === 'square' ? 'selected' : '' }}>Square</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           id="is_active" {{ old('is_active', $stockMedia->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-light fa-check me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>

                    <div class="border-top pt-3 mt-3">
                        <form action="{{ route('admin.stock-media.destroy', $stockMedia) }}" method="POST"
                              onsubmit="return confirm('Permanently delete this item and its file?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fa-light fa-trash me-1"></i> Delete This Item
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
