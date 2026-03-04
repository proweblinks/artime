{{-- Reusable media card for grid view --}}
<div class="col-md-6 col-lg-4 col-xl-3">
    <div class="card border-0 shadow-sm h-100 {{ !$item->is_active ? 'opacity-50' : '' }}">
        <div class="position-relative">
            <input type="checkbox" name="bulk_ids[]" value="{{ $item->id }}"
                   class="form-check-input position-absolute bulk-checkbox"
                   style="top: 8px; left: 8px; z-index: 2;">

            <a href="{{ route('admin.stock-media.edit', $item) }}">
                <img src="{{ $item->getThumbnailUrl() }}"
                     alt="{{ $item->title }}"
                     class="card-img-top"
                     style="height: 180px; object-fit: cover;"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22180%22><rect fill=%22%23e9ecef%22 width=%22300%22 height=%22180%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%236c757d%22 font-size=%2214%22>No Preview</text></svg>'">
            </a>

            {{-- Type badge --}}
            <span class="position-absolute badge bg-{{ $item->type === 'image' ? 'success' : 'info' }}"
                  style="top: 8px; right: 8px;">
                <i class="fa-light fa-{{ $item->type === 'image' ? 'image' : 'video' }} me-1"></i>{{ $item->type }}
            </span>

            {{-- Active indicator --}}
            @if(!$item->is_active)
                <span class="position-absolute badge bg-danger" style="bottom: 8px; right: 8px;">Inactive</span>
            @endif

            {{-- Duration for videos --}}
            @if($item->type === 'video' && $item->duration)
                <span class="position-absolute badge bg-dark bg-opacity-75" style="bottom: 8px; left: 8px;">
                    {{ gmdate('i:s', (int)$item->duration) }}
                </span>
            @endif
        </div>

        <div class="card-body py-2 px-3">
            <div class="text-truncate small fw-medium" title="{{ $item->title }}">{{ $item->title }}</div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 10px;">
                    {{ ucwords(str_replace('-', ' ', $item->category)) }}
                </span>
                <span class="text-muted" style="font-size: 10px;">
                    {{ $item->width }}x{{ $item->height }}
                </span>
            </div>
        </div>

        <div class="card-footer bg-transparent border-0 pt-0 pb-2 px-3">
            <div class="d-flex gap-1">
                <a href="{{ route('admin.stock-media.edit', $item) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="fa-light fa-pen"></i>
                </a>
                <form action="{{ route('admin.stock-media.toggle', $item) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-{{ $item->is_active ? 'warning' : 'success' }}" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                        <i class="fa-light fa-{{ $item->is_active ? 'eye-slash' : 'eye' }}"></i>
                    </button>
                </form>
                <form action="{{ route('admin.stock-media.destroy', $item) }}" method="POST"
                      onsubmit="return confirm('Delete this item?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fa-light fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
