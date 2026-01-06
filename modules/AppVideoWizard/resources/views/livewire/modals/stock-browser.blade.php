{{-- Stock Media Browser Modal --}}
<div x-data="{
    isOpen: false,
    sceneIndex: 0,
    searchQuery: '',
    mediaType: 'image',
    results: [],
    isSearching: false,

    open(sceneIndex) {
        this.sceneIndex = sceneIndex;
        this.searchQuery = '';
        this.results = [];
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
        this.results = [];
    },

    search() {
        if (!this.searchQuery.trim()) return;
        this.isSearching = true;
        this.results = [];

        $wire.searchStockMedia(this.searchQuery, this.mediaType, this.sceneIndex);
    },

    selectMedia(media) {
        this.isSearching = true;
        $wire.selectStockMedia(this.sceneIndex, media.url, media.id, media.type);
    }
}"
@open-stock-browser.window="open($event.detail.sceneIndex)"
@stock-media-results.window="results = $event.detail.results; isSearching = false;"
@stock-media-selected.window="close(); isSearching = false;"
x-show="isOpen"
x-cloak
class="vw-modal-overlay"
style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="close()"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 900px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">📷 {{ __('Stock Media Browser') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Free royalty-free media from Pexels') }}</p>
            </div>
            <button type="button" @click="close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Search --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; gap: 0.5rem;">
                <input type="text"
                       x-model="searchQuery"
                       @keydown.enter="search()"
                       placeholder="{{ __('Search for images or videos...') }}"
                       style="flex: 1; padding: 0.75rem 1rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.9rem;">
                <select x-model="mediaType" style="padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem;">
                    <option value="image">📷 {{ __('Images') }}</option>
                    <option value="video">🎬 {{ __('Videos') }}</option>
                </select>
                <button type="button" @click="search()" :disabled="isSearching" style="padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; min-width: 100px; justify-content: center;">
                    <span x-show="!isSearching">🔍 {{ __('Search') }}</span>
                    <span x-show="isSearching" style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></span>
                </button>
            </div>
        </div>

        {{-- Results --}}
        <div style="flex: 1; overflow-y: auto; padding: 1rem 1.25rem;">
            <template x-if="isSearching && results.length === 0">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; color: rgba(255,255,255,0.6); gap: 1rem;">
                    <div style="width: 2rem; height: 2rem; border: 3px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                    <span>{{ __('Searching...') }}</span>
                </div>
            </template>

            <template x-if="!isSearching && results.length === 0 && searchQuery">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; color: rgba(255,255,255,0.6); gap: 1rem;">
                    <span style="font-size: 3rem;">🔍</span>
                    <p>{{ __('No results found. Try a different search term.') }}</p>
                </div>
            </template>

            <div x-show="results.length > 0" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
                <template x-for="media in results" :key="media.id">
                    <div @click="selectMedia(media)"
                         style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; overflow: hidden; cursor: pointer; transition: all 0.2s;"
                         :style="{ 'border-color': media.selected ? '#8b5cf6' : '' }">
                        <div style="position: relative; aspect-ratio: 16/10; background: rgba(0,0,0,0.3);">
                            <img :src="media.thumbnail || media.preview" :alt="media.id" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <template x-if="media.type === 'video'">
                                <div style="position: absolute; bottom: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.8); color: white; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; display: flex; align-items: center; gap: 0.25rem;">
                                    <span>▶</span>
                                    <span x-text="media.duration ? Math.round(media.duration) + 's' : ''"></span>
                                </div>
                            </template>
                        </div>
                        <div style="padding: 0.5rem 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: capitalize;" x-text="media.source"></span>
                            <span style="font-size: 0.65rem; color: rgba(255,255,255,0.4); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100px;" x-text="'by ' + media.author"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
