{{-- Stock Library Browser Modal --}}
@if($showLibraryBrowser)
<div style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.88);z-index:10200;display:flex;align-items:center;justify-content:center;"
     x-data="{
         libraryQuery: '',
         hoverTimer: null,
         activePreviewEl: null,
         previewLibraryVideo(url, event) {
             const btn = event.currentTarget;
             if (this.hoverTimer) clearTimeout(this.hoverTimer);
             this.hoverTimer = setTimeout(() => {
                 if (!url) return;
                 const video = document.createElement('video');
                 video.src = url;
                 video.muted = true;
                 video.loop = true;
                 video.autoplay = true;
                 video.playsInline = true;
                 video.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:3;pointer-events:none;border-radius:8px;';
                 btn.style.position = 'relative';
                 btn.appendChild(video);
                 this.activePreviewEl = video;
             }, 200);
         },
         stopLibraryPreview() {
             if (this.hoverTimer) { clearTimeout(this.hoverTimer); this.hoverTimer = null; }
             if (this.activePreviewEl) {
                 this.activePreviewEl.pause();
                 this.activePreviewEl.remove();
                 this.activePreviewEl = null;
             }
         }
     }">
    <div class="card border-0 d-flex flex-column"
         style="background:#1a1a1a;border-radius:16px;width:780px;max-height:90vh;">

        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background:transparent;">
            <div>
                <h5 class="mb-1 text-white fw-bold">
                    <i class="fa-light fa-photo-film me-2" style="color:#f97316;"></i>
                    {{ __('Stock Library') }}
                </h5>
                <small style="color:#999;">{{ __('Browse or search your media library') }}</small>
            </div>
            <button wire:click="$set('showLibraryBrowser', false)" type="button" class="btn-close btn-close-white"></button>
        </div>

        {{-- Search bar --}}
        <div class="px-4 pb-2">
            <div class="d-flex gap-2">
                <input type="text"
                       x-model="libraryQuery"
                       @keydown.enter="$wire.searchLibrary(libraryQuery)"
                       class="form-control form-control-sm border-0 text-white"
                       style="background:#2a2a2a;border-radius:8px;font-size:0.82rem;flex:1;"
                       placeholder="{{ __('Search clips, images...') }}">
                <button @click="$wire.searchLibrary(libraryQuery)" type="button"
                        class="btn btn-sm" style="background:#f97316;color:#fff;border-radius:8px;white-space:nowrap;">
                    <i class="fa-light fa-magnifying-glass me-1"></i>{{ __('Search') }}
                </button>
            </div>
        </div>

        {{-- Category chips --}}
        @if(!empty($libraryCategories))
            <div class="px-4 pb-2">
                <div class="d-flex flex-wrap gap-1" style="max-height:80px;overflow-y:auto;">
                    @foreach($libraryCategories as $category => $count)
                        <button wire:click="loadLibraryCategory('{{ addslashes($category) }}')" type="button"
                                class="utv-lib-category-chip {{ $libraryActiveCategory === $category ? 'active' : '' }}">
                            {{ $category }}
                            <span class="utv-lib-category-count">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Results grid --}}
        <div class="card-body p-4 pt-2" style="overflow-y:auto;">
            @if(!empty($libraryCategoryResults))
                <div class="utv-lib-grid">
                    @foreach($libraryCategoryResults as $idx => $item)
                        @php $isVideo = ($item['type'] ?? 'image') === 'video'; @endphp
                        <button wire:click="selectFromLibrary({{ $idx }})" type="button"
                                class="utv-lib-item"
                                @if($isVideo)
                                    @mouseenter="previewLibraryVideo('{{ $item['url'] ?? '' }}', $event)"
                                    @mouseleave="stopLibraryPreview()"
                                @endif>
                            <img src="{{ $item['thumbnail'] ?? $item['url'] }}"
                                 alt="{{ $item['title'] ?? '' }}"
                                 loading="lazy" draggable="false">
                            @if($isVideo)
                                <div class="utv-video-badge">
                                    <i class="fa-solid fa-play"></i>
                                    {{ !empty($item['duration']) ? gmdate('i:s', $item['duration']) : '' }}
                                </div>
                            @endif
                            <span class="utv-lib-item-title">{{ Str::limit($item['title'] ?? '', 24) }}</span>
                        </button>
                    @endforeach
                </div>
            @elseif(!empty($libraryActiveCategory) || !empty($libraryQuery))
                <div class="d-flex flex-column align-items-center justify-content-center py-5" style="color:#666;">
                    <i class="fa-light fa-image-slash mb-2" style="font-size:2rem;"></i>
                    <span style="font-size:0.85rem;">{{ __('No results found') }}</span>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-5" style="color:#555;">
                    <i class="fa-light fa-photo-film mb-2" style="font-size:2rem;"></i>
                    <span style="font-size:0.85rem;">{{ __('Select a category or search to browse') }}</span>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2" style="background:transparent;">
            <button wire:click="$set('showLibraryBrowser', false)" type="button"
                    class="btn w-100" style="background:#2a2a2a;color:#ccc;border-radius:10px;">
                <i class="fa-light fa-arrow-left me-1"></i>
                {{ __('Back to Selection') }}
            </button>
        </div>
    </div>
</div>

<style>
    .utv-lib-category-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        border: 1px solid #333;
        background: #2a2a2a;
        color: #aaa;
        font-size: 0.72rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .utv-lib-category-chip:hover {
        background: #333;
        color: #ccc;
        border-color: #444;
    }
    .utv-lib-category-chip.active {
        background: rgba(249,115,22,0.15);
        color: #f97316;
        border-color: rgba(249,115,22,0.4);
    }
    .utv-lib-category-count {
        font-size: 0.62rem;
        color: #666;
        font-weight: 600;
    }
    .utv-lib-category-chip.active .utv-lib-category-count {
        color: rgba(249,115,22,0.7);
    }
    .utv-lib-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    .utv-lib-item {
        position: relative;
        aspect-ratio: 4/3;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid transparent;
        background: #222;
        cursor: pointer;
        padding: 0;
        transition: border-color 0.15s, transform 0.15s;
    }
    .utv-lib-item:hover {
        border-color: rgba(249,115,22,0.5);
        transform: scale(1.02);
    }
    .utv-lib-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .utv-lib-item-title {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 4px 8px;
        background: linear-gradient(transparent, rgba(0,0,0,0.8));
        color: #ccc;
        font-size: 0.62rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endif
