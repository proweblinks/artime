@extends('layouts.app')

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.stock-media.dashboard') }}">Stock Library</a></li>
                        <li class="breadcrumb-item active">Upload</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">Upload Media</div>
                <p class="text-muted mb-0 small">Upload images and videos to the stock library</p>
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
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
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
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" x-data="uploadManager()">
                <div class="card-body">
                    <form action="{{ route('admin.stock-media.upload.store') }}" method="POST" enctype="multipart/form-data"
                          id="upload-form">
                        @csrf

                        {{-- Category Selection --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" x-model="category">
                                    <option value="">Select category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ ucwords(str_replace('-', ' ', $cat)) }}</option>
                                    @endforeach
                                    <option value="__new__">+ New Category</option>
                                </select>
                            </div>
                            <div class="col-md-6" x-show="category === '__new__'" x-cloak>
                                <label class="form-label">New Category Name</label>
                                <input type="text" name="new_category" class="form-control"
                                       placeholder="e.g. architecture">
                            </div>
                        </div>

                        {{-- Drop Zone --}}
                        <div class="border-2 border-dashed rounded-3 p-5 text-center mb-3"
                             :class="{ 'border-primary bg-primary bg-opacity-10': dragging, 'border-secondary': !dragging }"
                             @dragover.prevent="dragging = true"
                             @dragleave.prevent="dragging = false"
                             @drop.prevent="handleDrop($event)"
                             @click="$refs.fileInput.click()"
                             style="cursor: pointer;">
                            <i class="fa-light fa-cloud-arrow-up fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-1">Drag & drop files here or click to browse</p>
                            <p class="text-muted small mb-0">Supported: JPG, PNG, WebP, MP4, MOV, WebM (max 200MB each)</p>
                        </div>

                        <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.webm"
                               x-ref="fileInput" class="d-none" @change="handleFiles($event)">

                        {{-- File List --}}
                        <template x-if="files.length > 0">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><span x-text="files.length"></span> file(s) selected</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearFiles()">Clear All</button>
                                </div>
                                <div class="list-group">
                                    <template x-for="(file, index) in files" :key="index">
                                        <div class="list-group-item d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <template x-if="file.preview">
                                                    <img :src="file.preview" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                                </template>
                                                <template x-if="!file.preview">
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fa-light fa-video text-muted"></i>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex-grow-1 min-width-0">
                                                <div class="text-truncate small fw-medium" x-text="file.name"></div>
                                                <div class="text-muted" style="font-size: 11px;" x-text="formatSize(file.size)"></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link text-danger" @click="removeFile(index)">
                                                <i class="fa-light fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <button type="submit" class="btn btn-primary"
                                :disabled="files.length === 0 || (!category || category === '')"
                                @click="submitting = true">
                            <span x-show="!submitting">
                                <i class="fa-light fa-cloud-arrow-up me-1"></i> Upload <span x-text="files.length"></span> File(s)
                            </span>
                            <span x-show="submitting" x-cloak>
                                <span class="spinner-border spinner-border-sm me-1"></span> Uploading...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Upload Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i>
                            <strong>Images:</strong> JPG, PNG, WebP
                        </li>
                        <li class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i>
                            <strong>Videos:</strong> MP4, MOV, WebM
                        </li>
                        <li class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i>
                            Max file size: 200MB per file
                        </li>
                        <li class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i>
                            Duplicates are detected by SHA256 checksum
                        </li>
                        <li class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i>
                            Video thumbnails are auto-generated
                        </li>
                        <li>
                            <i class="fa-light fa-check text-success me-2"></i>
                            Title and tags are derived from filename
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Existing Categories</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($categories as $cat)
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small">
                                <i class="fa-light fa-folder text-warning me-1"></i>
                                {{ ucwords(str_replace('-', ' ', $cat)) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">No categories yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function uploadManager() {
    return {
        files: [],
        dragging: false,
        category: '',
        submitting: false,

        handleDrop(event) {
            this.dragging = false;
            this.addFiles(event.dataTransfer.files);
        },

        handleFiles(event) {
            this.addFiles(event.target.files);
        },

        addFiles(fileList) {
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                const entry = { name: file.name, size: file.size, file: file, preview: null };

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => { entry.preview = e.target.result; };
                    reader.readAsDataURL(file);
                }

                this.files.push(entry);
            }

            // Sync to the native file input via DataTransfer
            this.syncFileInput();
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.syncFileInput();
        },

        clearFiles() {
            this.files = [];
            this.syncFileInput();
        },

        syncFileInput() {
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f.file));
            this.$refs.fileInput.files = dt.files;
        },

        formatSize(bytes) {
            if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            return (bytes / 1024).toFixed(0) + ' KB';
        }
    };
}
</script>
@endsection
