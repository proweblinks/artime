{{-- Stock Library Browser Modal --}}
@if($showLibraryBrowser)
<div style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:10200;display:flex;align-items:center;justify-content:center;"
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
         style="background:#ffffff;border:1px solid #eef1f5;border-radius:16px;width:780px;max-height:90vh;box-shadow:0 8px 30px rgba(0,0,0,0.12);">

        {{-- Header --}}
        <div class="card-header border-0 d-flex align-items-center justify-content-between p-4 pb-2" style="background:transparent;">
            <div>
                <h5 class="mb-1 fw-bold" style="color:var(--at-text, #1a1a2e);">
                    <i class="fa-light fa-photo-film me-2" style="color:#0891b2;"></i>
                    {{ __('Stock Library') }}
                </h5>
                <small style="color:var(--at-text-muted, #94a0b8);">{{ __('Browse or search your media library') }}</small>
            </div>
            <button wire:click="$set('showLibraryBrowser', false)" type="button" class="btn-close"></button>
        </div>

        {{-- Search bar --}}
        <div class="px-4 pb-2">
            <div class="d-flex gap-2">
                <input type="text"
                       x-model="libraryQuery"
                       @keydown.enter="$wire.searchLibrary(libraryQuery)"
                       class="form-control form-control-sm border-0"
                       style="background:#f5f7fa;border-radius:8px;font-size:0.82rem;flex:1;color:var(--at-text, #1a1a2e);"
                       placeholder="{{ __('Search clips, images...') }}">
                <button @click="$wire.searchLibrary(libraryQuery)" type="button"
                        class="btn btn-sm" style="background:#03fcf4;color:#0a2e2e;border-radius:8px;white-space:nowrap;">
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

        {{-- Sort & Filter toolbar --}}
        @if(!empty($libraryActiveCategory) || !empty($librarySearchQuery))
            <div class="px-4 pb-2 d-flex align-items-center gap-2 flex-wrap">
                {{-- Type filter chips --}}
                <div class="d-flex gap-1">
                    <button wire:click="updateLibraryTypeFilter('video')" type="button"
                            class="utv-lib-filter-chip {{ $libraryTypeFilter === 'video' ? 'active' : '' }}">
                        <i class="fa-light fa-video" style="font-size:0.65rem;"></i> {{ __('Videos') }}
                    </button>
                    <button wire:click="updateLibraryTypeFilter('image')" type="button"
                            class="utv-lib-filter-chip {{ $libraryTypeFilter === 'image' ? 'active' : '' }}">
                        <i class="fa-light fa-image" style="font-size:0.65rem;"></i> {{ __('Images') }}
                    </button>
                </div>

                <span style="color:#d0d5dd;">|</span>

                {{-- Sort options --}}
                <div class="d-flex gap-1">
                    <button wire:click="updateLibrarySort('title')" type="button"
                            class="utv-lib-filter-chip {{ $librarySort === 'title' ? 'active' : '' }}">
                        <i class="fa-light fa-arrow-down-a-z" style="font-size:0.65rem;"></i> {{ __('A-Z') }}
                    </button>
                    <button wire:click="updateLibrarySort('shortest')" type="button"
                            class="utv-lib-filter-chip {{ $librarySort === 'shortest' ? 'active' : '' }}">
                        <i class="fa-light fa-clock" style="font-size:0.65rem;"></i> {{ __('Shortest') }}
                    </button>
                    <button wire:click="updateLibrarySort('longest')" type="button"
                            class="utv-lib-filter-chip {{ $librarySort === 'longest' ? 'active' : '' }}">
                        <i class="fa-light fa-hourglass" style="font-size:0.65rem;"></i> {{ __('Longest') }}
                    </button>
                    <button wire:click="updateLibrarySort('newest')" type="button"
                            class="utv-lib-filter-chip {{ $librarySort === 'newest' ? 'active' : '' }}">
                        <i class="fa-light fa-sparkles" style="font-size:0.65rem;"></i> {{ __('Newest') }}
                    </button>
                </div>

                {{-- Result count --}}
                <span class="ms-auto" style="font-size:0.7rem;color:#94a0b8;">
                    {{ count($libraryCategoryResults) }}{{ $libraryHasMore ? '+' : '' }} {{ __('items') }}
                </span>
            </div>
        @endif

        {{-- Results grid --}}
        <div class="card-body p-4 pt-2" style="overflow-y:auto;">
            @if(!empty($libraryCategoryResults))
                <div class="utv-lib-grid">
                    @foreach($libraryCategoryResults as $idx => $item)
                        @php $isVideo = ($item['type'] ?? 'image') === 'video'; @endphp
                        <div class="utv-lib-item-wrap" x-data="{ reported: false }">
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
                            @if(!empty($item['stock_id']))
                                <span class="utv-lib-report-btn"
                                      @click.stop="if (reported) return; if (!confirm('Report this media as inappropriate or broken?')) return; fetch('/api/stock-media/{{ $item['stock_id'] }}/report', { method: 'POST', headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'} }).then(r => { reported = true; }).catch(() => {})"
                                      :class="{ 'utv-lib-report-btn--reported': reported }"
                                      :title="reported ? '{{ __('Reported') }}' : '{{ __('Report this media') }}'">
                                    <i class="fa-solid fa-flag" style="font-size:9px;"></i>
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($libraryHasMore)
                    <div class="d-flex justify-content-center mt-3">
                        <button wire:click="loadMoreLibrary" wire:loading.attr="disabled" type="button"
                                class="btn btn-sm" style="background:#f5f7fa;color:#5a6178;border:1px solid #eef1f5;border-radius:8px;font-size:0.8rem;padding:8px 24px;">
                            <span wire:loading.remove wire:target="loadMoreLibrary">
                                <i class="fa-light fa-arrow-down me-1"></i>{{ __('Load More') }}
                            </span>
                            <span wire:loading wire:target="loadMoreLibrary">
                                <i class="fa-light fa-spinner-third fa-spin me-1"></i>{{ __('Loading...') }}
                            </span>
                        </button>
                    </div>
                @endif
            @elseif(!empty($libraryActiveCategory) || !empty($librarySearchQuery))
                <div class="d-flex flex-column align-items-center justify-content-center py-5" style="color:#94a0b8;">
                    <i class="fa-light fa-image-slash mb-2" style="font-size:2rem;"></i>
                    <span style="font-size:0.85rem;">{{ __('No results found') }}</span>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-5" style="color:#94a0b8;">
                    <i class="fa-light fa-photo-film mb-2" style="font-size:2rem;"></i>
                    <span style="font-size:0.85rem;">{{ __('Select a category or search to browse') }}</span>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 p-4 pt-2" style="background:transparent;">
            <button wire:click="$set('showLibraryBrowser', false)" type="button"
                    class="btn w-100" style="background:#f5f7fa;color:var(--at-text-secondary, #5a6178);border-radius:10px;">
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
        border: 1px solid #eef1f5;
        background: #f5f7fa;
        color: #5a6178;
        font-size: 0.72rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .utv-lib-category-chip:hover {
        background: #eef1f5;
        color: #1a1a2e;
        border-color: #d0d5dd;
    }
    .utv-lib-category-chip.active {
        background: rgba(3,252,244,0.1);
        color: #0891b2;
        border-color: rgba(3,252,244,0.3);
    }
    .utv-lib-category-count {
        font-size: 0.62rem;
        color: #94a0b8;
        font-weight: 600;
    }
    .utv-lib-category-chip.active .utv-lib-category-count {
        color: rgba(8,145,178,0.7);
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
        background: #f5f7fa;
        cursor: pointer;
        padding: 0;
        transition: border-color 0.15s, transform 0.15s;
    }
    .utv-lib-item:hover {
        border-color: rgba(3,252,244,0.4);
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
        background: linear-gradient(transparent, rgba(0,0,0,0.6));
        color: #fff;
        font-size: 0.62rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .utv-lib-item-wrap {
        position: relative;
    }
    .utv-lib-report-btn {
        position: absolute;
        bottom: 6px;
        right: 6px;
        z-index: 4;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(0,0,0,0.5);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.15s, background 0.15s;
    }
    .utv-lib-item-wrap:hover .utv-lib-report-btn {
        opacity: 1;
    }
    .utv-lib-report-btn:hover {
        background: rgba(239,68,68,0.8);
    }
    .utv-lib-report-btn--reported {
        opacity: 1 !important;
        background: rgba(239,68,68,0.8);
        pointer-events: none;
    }
    .utv-lib-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        border: 1px solid #eef1f5;
        background: #f5f7fa;
        color: #5a6178;
        font-size: 0.68rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .utv-lib-filter-chip:hover {
        background: #eef1f5;
        color: #1a1a2e;
        border-color: #d0d5dd;
    }
    .utv-lib-filter-chip.active {
        background: rgba(3,252,244,0.12);
        color: #0891b2;
        border-color: rgba(3,252,244,0.35);
    }
</style>
@endif
