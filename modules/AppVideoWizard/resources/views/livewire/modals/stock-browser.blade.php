{{-- Stock Media Browser Modal --}}
<div x-data="{
    isOpen: false,
    sceneIndex: 0,
    searchQuery: '',
    mediaType: 'image',
    results: [],
    isSearching: false,
    suggestedQuery: '',
    alternativeQueries: [],
    loadingAiSuggestions: false,
    sceneDescription: '',

    open(sceneIndex, sceneDescription = '') {
        this.sceneIndex = sceneIndex;
        this.searchQuery = '';
        this.results = [];
        this.sceneDescription = sceneDescription;
        this.isOpen = true;
        this.loadingAiSuggestions = true;

        // Extract basic keywords from scene description
        if (sceneDescription) {
            this.suggestedQuery = this.extractKeywords(sceneDescription);
            this.searchQuery = this.suggestedQuery;
        }

        // Generate AI suggestions via Livewire
        $wire.generateStockSuggestions(sceneIndex).then(result => {
            if (result && result.primaryQuery) {
                this.suggestedQuery = result.primaryQuery;
                this.searchQuery = result.primaryQuery;
                this.alternativeQueries = result.alternatives || [];
            }
            this.loadingAiSuggestions = false;
        }).catch(() => {
            this.loadingAiSuggestions = false;
        });
    },

    extractKeywords(text) {
        const stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'this', 'that', 'it', 'and', 'or'];
        const words = text.toLowerCase().split(/\s+/)
            .filter(w => w.length > 3 && !stopWords.includes(w))
            .slice(0, 4);
        return words.join(' ');
    },

    close() {
        this.isOpen = false;
        this.results = [];
        this.alternativeQueries = [];
    },

    search() {
        if (!this.searchQuery.trim()) return;
        this.isSearching = true;
        this.results = [];

        $wire.searchStockMedia(this.searchQuery, this.mediaType, this.sceneIndex);
    },

    applySuggestion(query) {
        this.searchQuery = query;
        this.search();
    },

    selectMedia(media) {
        this.isSearching = true;
        $wire.selectStockMedia(this.sceneIndex, media.url, media.id, media.type);
    }
}"
@open-stock-browser.window="open($event.detail.sceneIndex, $event.detail.sceneDescription || '')"
@stock-media-results.window="results = $event.detail.results; isSearching = false;"
@stock-media-selected.window="close(); isSearching = false;"
x-show="isOpen"
x-cloak
class="vw-modal-overlay"
style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         @click.away="close()"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(3,252,244,0.3); border-radius: 1rem; width: 100%; max-width: 950px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">📷 {{ __('Stock Media Browser') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Free royalty-free media from Pexels') }}</p>
            </div>
            <button type="button" @click="close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Search Bar --}}
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
                <button type="button" @click="search()" :disabled="isSearching" style="padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #03fcf4, #06b6d4); border: none; border-radius: 0.5rem; color: #0a2e2e; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; min-width: 100px; justify-content: center;">
                    <span x-show="!isSearching">🔍 {{ __('Search') }}</span>
                    <span x-show="isSearching" style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></span>
                </button>
            </div>
        </div>

        {{-- AI Suggestions Section --}}
        <div style="padding: 0.6rem 1.25rem; background: rgba(3, 252, 244, 0.1); border-bottom: 1px solid rgba(255,255,255,0.1);">
            {{-- Loading State --}}
            <template x-if="loadingAiSuggestions">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 14px; height: 14px; border: 2px solid rgba(3,252,244,0.3); border-top-color: #67e8f9; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></span>
                    <span style="color: #67e8f9; font-size: 0.75rem;">{{ __('AI analyzing your scene...') }}</span>
                </div>
            </template>

            {{-- Suggestions Display --}}
            <template x-if="!loadingAiSuggestions && suggestedQuery">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <span style="color: #67e8f9; font-size: 0.7rem;">🧠 {{ __('AI Smart Suggestions') }}:</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        {{-- Primary Suggestion --}}
                        <button type="button"
                                @click="applySuggestion(suggestedQuery)"
                                style="padding: 0.3rem 0.6rem; background: linear-gradient(135deg, rgba(3,252,244,0.4), rgba(236,72,153,0.3)); border: 1px solid rgba(3,252,244,0.5); border-radius: 1rem; color: white; cursor: pointer; font-size: 0.75rem; font-weight: 500;">
                            ✨ <span x-text="suggestedQuery"></span>
                        </button>
                        {{-- Alternative Suggestions --}}
                        <template x-for="query in alternativeQueries" :key="query">
                            <button type="button"
                                    @click="applySuggestion(query)"
                                    style="padding: 0.3rem 0.6rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 1rem; color: rgba(255,255,255,0.8); cursor: pointer; font-size: 0.7rem;"
                                    x-text="query">
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Quick Search Tags --}}
            <template x-if="!loadingAiSuggestions && !suggestedQuery">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <span style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">🏷️ {{ __('Quick Search') }}:</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                        @foreach(['nature', 'business', 'technology', 'people', 'abstract', 'city', 'office', 'lifestyle'] as $tag)
                            <button type="button"
                                    @click="applySuggestion('{{ $tag }}')"
                                    style="padding: 0.25rem 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 1rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.7rem;">
                                {{ ucfirst(__($tag)) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>
        </div>

        {{-- Results Grid --}}
        <div style="flex: 1; overflow-y: auto; padding: 1rem 1.25rem;">
            {{-- Searching State --}}
            <template x-if="isSearching && results.length === 0">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; color: rgba(255,255,255,0.6); gap: 1rem;">
                    <div style="width: 2rem; height: 2rem; border: 3px solid rgba(3,252,244,0.3); border-top-color: #03fcf4; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                    <span>{{ __('Searching...') }}</span>
                </div>
            </template>

            {{-- No Results State --}}
            <template x-if="!isSearching && results.length === 0 && searchQuery">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; color: rgba(255,255,255,0.6); gap: 1rem;">
                    <span style="font-size: 3rem;">🔍</span>
                    <p>{{ __('No results found. Try a different search term.') }}</p>
                </div>
            </template>

            {{-- Initial State --}}
            <template x-if="!isSearching && results.length === 0 && !searchQuery">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; color: rgba(255,255,255,0.5); gap: 1rem;">
                    <span style="font-size: 4rem;">📷</span>
                    <p style="font-size: 1rem;">{{ __('Search for stock media') }}</p>
                    <p style="font-size: 0.8rem;">{{ __('Use AI suggestions or enter your own search term') }}</p>
                </div>
            </template>

            {{-- Results Grid --}}
            <div x-show="results.length > 0" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
                <template x-for="media in results" :key="media.id">
                    <div @click="selectMedia(media)"
                         style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; overflow: hidden; cursor: pointer; transition: all 0.2s;"
                         :style="{ 'border-color': media.selected ? '#03fcf4' : '' }"
                         onmouseover="this.style.borderColor='rgba(3,252,244,0.5)'; this.style.transform='translateY(-2px)'"
                         onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.transform='none'">
                        <div style="position: relative; aspect-ratio: 16/10; background: rgba(0,0,0,0.3);">
                            <img :src="media.thumbnail || media.preview" :alt="media.id" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            {{-- Video Duration Badge --}}
                            <template x-if="media.type === 'video'">
                                <div style="position: absolute; bottom: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.8); color: white; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; display: flex; align-items: center; gap: 0.25rem;">
                                    <span>▶</span>
                                    <span x-text="media.duration ? Math.round(media.duration) + 's' : ''"></span>
                                </div>
                            </template>
                            {{-- Source Badge --}}
                            <div style="position: absolute; top: 0.35rem; left: 0.35rem; background: rgba(0,0,0,0.7); color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.6rem; text-transform: uppercase;" x-text="media.source"></div>
                        </div>
                        <div style="padding: 0.5rem 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: capitalize;" x-text="media.type"></span>
                            <span style="font-size: 0.65rem; color: rgba(255,255,255,0.4); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100px;" x-text="'by ' + media.author"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Footer with Attribution --}}
        <div style="padding: 0.75rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div style="color: rgba(255,255,255,0.4); font-size: 0.7rem;">
                {{ __('Photos and videos provided by') }}
                <a href="https://www.pexels.com" target="_blank" style="color: #06b6d4; text-decoration: none;">Pexels</a>
            </div>
            <button type="button"
                    @click="close()"
                    style="padding: 0.5rem 1rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.85rem;">
                {{ __('Close') }}
            </button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }
</style>
