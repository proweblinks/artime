{{-- Reusable media card for grid view --}}
<div class="col-md-6 col-lg-4 col-xl-3 mb-2">
    <div class="card border-0 shadow-sm overflow-hidden {{ !$item->is_active ? 'opacity-50' : '' }}">
        {{-- Thumbnail with overlays --}}
        <div class="position-relative" style="height: 160px; overflow: hidden; background: #f1f3f5;">
            <input type="checkbox" name="bulk_ids[]" value="{{ $item->id }}"
                   class="form-check-input position-absolute bulk-checkbox"
                   style="top: 8px; left: 8px; z-index: 2;">

            <a href="{{ route('admin.stock-media.edit', $item) }}" class="d-block w-100 h-100">
                <img src="{{ $item->getThumbnailUrl() }}"
                     alt="{{ $item->title }}"
                     class="w-100 h-100"
                     style="object-fit: cover; display: block;"
                     onerror="this.style.display='none'">
            </a>

            <span class="position-absolute badge bg-{{ $item->type === 'image' ? 'success' : 'info' }}"
                  style="top: 8px; right: 8px; font-size: 10px;">
                <i class="fa-light fa-{{ $item->type === 'image' ? 'image' : 'video' }} me-1"></i>{{ $item->type }}
            </span>

            @if(!$item->is_active)
                <span class="position-absolute badge bg-danger" style="bottom: 8px; right: 8px; font-size: 10px;">Inactive</span>
            @endif

            @if($item->type === 'video' && $item->duration)
                <span class="position-absolute badge bg-dark bg-opacity-75" style="bottom: 8px; left: 8px; font-size: 10px;">
                    {{ gmdate('i:s', (int)$item->duration) }}
                </span>
            @endif
        </div>

        {{-- Info --}}
        <div class="px-3 py-2" style="min-height: 52px;">
            <div class="text-truncate small fw-medium" title="{{ $item->title }}">{{ $item->title }}</div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 10px;">
                    {{ ucwords(str_replace('-', ' ', $item->category)) }}
                </span>
                <span class="text-muted" style="font-size: 10px;">{{ $item->width }}x{{ $item->height }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="border-top px-3 py-2 d-flex gap-1">
            <a href="{{ route('admin.stock-media.edit', $item) }}" class="btn btn-sm btn-outline-primary flex-grow-1" style="font-size: 11px;">
                <i class="fa-light fa-pen"></i>
            </a>
            <form action="{{ route('admin.stock-media.toggle', $item) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-{{ $item->is_active ? 'warning' : 'success' }}" style="font-size: 11px;" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="fa-light fa-{{ $item->is_active ? 'eye-slash' : 'eye' }}"></i>
                </button>
            </form>
            <form action="{{ route('admin.stock-media.destroy', $item) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this item?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size: 11px;">
                    <i class="fa-light fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
