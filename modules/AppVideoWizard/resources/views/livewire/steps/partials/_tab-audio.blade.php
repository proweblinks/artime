{{--
    Audio Tab Content - Phase 4
    Smart Audio AI, volume controls, music library browser
--}}

<div class="vw-audio-tab" x-data="{
    activeSection: 'mix',
    showMusicBrowser: false,
    musicCategory: 'all',
    musicSearch: '',
    selectedTrack: '{{ $assembly['music']['trackId'] ?? '' }}',
    isPlayingPreview: false,
    previewTrackId: null,
    smartAudioEnabled: {{ ($assembly['audioMix']['smartAudio'] ?? true) ? 'true' : 'false' }},
    audioAnalyzing: false,

    // Music library data
    musicTracks: [
        { id: 'upbeat-corporate-1', name: 'Corporate Uplift', artist: 'Studio AI', duration: '2:45', category: 'corporate', mood: 'upbeat', bpm: 120 },
        { id: 'cinematic-epic-1', name: 'Epic Journey', artist: 'Soundscape', duration: '3:20', category: 'cinematic', mood: 'dramatic', bpm: 90 },
        { id: 'chill-ambient-1', name: 'Peaceful Dreams', artist: 'Ambient Lab', duration: '4:10', category: 'ambient', mood: 'calm', bpm: 70 },
        { id: 'energetic-pop-1', name: 'Energy Burst', artist: 'Pop Factory', duration: '2:30', category: 'pop', mood: 'energetic', bpm: 128 },
        { id: 'inspiring-piano-1', name: 'Rising Hope', artist: 'Keys Master', duration: '3:05', category: 'emotional', mood: 'inspiring', bpm: 85 },
        { id: 'tech-innovation-1', name: 'Digital Future', artist: 'Tech Sounds', duration: '2:55', category: 'technology', mood: 'modern', bpm: 110 },
        { id: 'happy-ukulele-1', name: 'Sunny Days', artist: 'Acoustic Vibes', duration: '2:20', category: 'acoustic', mood: 'happy', bpm: 100 },
        { id: 'dramatic-orchestra-1', name: 'Grand Finale', artist: 'Symphony AI', duration: '3:45', category: 'cinematic', mood: 'epic', bpm: 95 },
        { id: 'lofi-chill-1', name: 'Late Night Study', artist: 'Lo-Fi Beats', duration: '3:30', category: 'lofi', mood: 'relaxed', bpm: 75 },
        { id: 'motivational-rock-1', name: 'Champion Rise', artist: 'Rock Studio', duration: '2:50', category: 'rock', mood: 'powerful', bpm: 135 },
    ],

    get filteredTracks() {
        return this.musicTracks.filter(track => {
            const matchCategory = this.musicCategory === 'all' || track.category === this.musicCategory;
            const matchSearch = !this.musicSearch ||
                track.name.toLowerCase().includes(this.musicSearch.toLowerCase()) ||
                track.artist.toLowerCase().includes(this.musicSearch.toLowerCase()) ||
                track.mood.toLowerCase().includes(this.musicSearch.toLowerCase());
            return matchCategory && matchSearch;
        });
    },

    selectTrack(trackId) {
        this.selectedTrack = trackId;
        $wire.set('assembly.music.trackId', trackId);
        this.updateMusicSetting('trackId', trackId);
    },

    togglePreview(trackId) {
        if (this.previewTrackId === trackId && this.isPlayingPreview) {
            this.isPlayingPreview = false;
            this.previewTrackId = null;
        } else {
            this.previewTrackId = trackId;
            this.isPlayingPreview = true;
        }
    },

    analyzeAudio() {
        this.audioAnalyzing = true;
        setTimeout(() => {
            this.audioAnalyzing = false;
            // Simulate AI analysis result
            $wire.set('assembly.audioMix.voiceVolume', 100);
            $wire.set('assembly.music.volume', 25);
            $wire.set('assembly.audioMix.ducking', true);
        }, 2000);
    }
}">
    {{-- Section Tabs --}}
    <div class="vw-audio-tabs">
        <button type="button" @click="activeSection = 'mix'" :class="{ 'active': activeSection === 'mix' }" class="vw-audio-tab-btn">
            <span>🎚️</span> {{ __('Mix') }}
        </button>
        <button type="button" @click="activeSection = 'voice'" :class="{ 'active': activeSection === 'voice' }" class="vw-audio-tab-btn">
            <span>🎙️</span> {{ __('Voice') }}
        </button>
        <button type="button" @click="activeSection = 'music'" :class="{ 'active': activeSection === 'music' }" class="vw-audio-tab-btn">
            <span>🎵</span> {{ __('Music') }}
        </button>
    </div>

    {{-- MIX SECTION --}}
    <div x-show="activeSection === 'mix'" x-cloak class="vw-section-content">
        {{-- Smart Audio AI --}}
        <div class="vw-smart-audio-card">
            <div class="vw-smart-header">
                <div class="vw-smart-icon">🤖</div>
                <div class="vw-smart-text">
                    <span class="vw-smart-title">{{ __('Smart Audio AI') }}</span>
                    <span class="vw-smart-desc">{{ __('Auto-balance voice and music') }}</span>
                </div>
                <label class="vw-toggle-switch">
                    <input
                        type="checkbox"
                        x-model="smartAudioEnabled"
                        wire:model.live="assembly.audioMix.smartAudio"
                    >
                    <span class="vw-toggle-slider"></span>
                </label>
            </div>

            <div x-show="smartAudioEnabled" x-collapse class="vw-smart-content">
                <button
                    type="button"
                    @click="analyzeAudio()"
                    :disabled="audioAnalyzing"
                    class="vw-analyze-btn"
                >
                    <span x-show="!audioAnalyzing">✨ {{ __('Analyze & Optimize') }}</span>
                    <span x-show="audioAnalyzing" class="vw-analyzing">
                        <span class="vw-spinner"></span> {{ __('Analyzing...') }}
                    </span>
                </button>
                <p class="vw-smart-hint">{{ __('AI will analyze your voiceover and set optimal music levels') }}</p>
            </div>
        </div>

        {{-- Audio Mix Visualization --}}
        <div class="vw-audio-mixer">
            <div class="vw-mixer-header">
                <span>{{ __('Audio Levels') }}</span>
            </div>

            {{-- Voice Channel --}}
            <div class="vw-channel">
                <div class="vw-channel-header">
                    <span class="vw-channel-icon voice">🎙️</span>
                    <span class="vw-channel-name">{{ __('Voiceover') }}</span>
                    <span class="vw-channel-value">{{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%</span>
                </div>
                <div class="vw-channel-meter">
                    <div class="vw-meter-fill voice" style="width: {{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%;"></div>
                </div>
                <input
                    type="range"
                    wire:model.live="assembly.audioMix.voiceVolume"
                    min="0" max="100"
                    class="vw-channel-slider voice"
                >
            </div>

            {{-- Music Channel --}}
            <div class="vw-channel" :class="{ 'disabled': !musicEnabled }">
                <div class="vw-channel-header">
                    <span class="vw-channel-icon music">🎵</span>
                    <span class="vw-channel-name">{{ __('Music') }}</span>
                    <span class="vw-channel-value">{{ $assembly['music']['volume'] ?? 30 }}%</span>
                </div>
                <div class="vw-channel-meter">
                    <div class="vw-meter-fill music" style="width: {{ $assembly['music']['volume'] ?? 30 }}%;"></div>
                </div>
                <input
                    type="range"
                    wire:model.live="assembly.music.volume"
                    x-on:input="updateMusicSetting('volume', parseInt($event.target.value))"
                    min="0" max="100" step="5"
                    class="vw-channel-slider music"
                    :disabled="!musicEnabled"
                >
            </div>

            {{-- Visual Bars --}}
            <div class="vw-mix-visual">
                <div class="vw-visual-bar voice" :style="{ height: '{{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%' }"></div>
                <div class="vw-visual-bar voice" :style="{ height: '{{ ($assembly['audioMix']['voiceVolume'] ?? 100) * 0.9 }}%' }"></div>
                <div class="vw-visual-bar voice" :style="{ height: '{{ ($assembly['audioMix']['voiceVolume'] ?? 100) * 0.75 }}%' }"></div>
                <div class="vw-visual-bar music" :style="{ height: '{{ $assembly['music']['volume'] ?? 30 }}%' }"></div>
                <div class="vw-visual-bar music" :style="{ height: '{{ ($assembly['music']['volume'] ?? 30) * 0.85 }}%' }"></div>
                <div class="vw-visual-bar music" :style="{ height: '{{ ($assembly['music']['volume'] ?? 30) * 0.7 }}%' }"></div>
            </div>
        </div>

        {{-- Audio Ducking --}}
        <div class="vw-ducking-card">
            <div class="vw-setting-row">
                <div class="vw-ducking-info">
                    <span class="vw-ducking-icon">📉</span>
                    <div>
                        <span class="vw-ducking-title">{{ __('Auto-Duck Music') }}</span>
                        <span class="vw-ducking-desc">{{ __('Lower music during speech') }}</span>
                    </div>
                </div>
                <label class="vw-toggle-switch small">
                    <input
                        type="checkbox"
                        wire:model.live="assembly.audioMix.ducking"
                        {{ ($assembly['audioMix']['ducking'] ?? true) ? 'checked' : '' }}
                    >
                    <span class="vw-toggle-slider"></span>
                </label>
            </div>

            @if($assembly['audioMix']['ducking'] ?? true)
                <div class="vw-duck-settings">
                    <div class="vw-setting-row">
                        <span class="vw-setting-label">{{ __('Duck Amount') }}</span>
                        <span class="vw-setting-value">{{ $assembly['audioMix']['duckAmount'] ?? 50 }}%</span>
                    </div>
                    <input
                        type="range"
                        wire:model.live="assembly.audioMix.duckAmount"
                        min="20" max="80" step="5"
                        class="vw-range-slider"
                    >
                </div>
            @endif
        </div>
    </div>

    {{-- VOICE SECTION --}}
    <div x-show="activeSection === 'voice'" x-cloak class="vw-section-content">
        {{-- Voice Volume --}}
        <div class="vw-voice-section">
            <div class="vw-section-title">
                <span>🎙️</span> {{ __('Voiceover Settings') }}
            </div>

            <div class="vw-voice-control">
                <div class="vw-control-header">
                    <span class="vw-control-label">{{ __('Master Volume') }}</span>
                    <span class="vw-control-value">{{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%</span>
                </div>
                <input
                    type="range"
                    wire:model.live="assembly.audioMix.voiceVolume"
                    min="0" max="100"
                    class="vw-range-slider voice"
                >
            </div>

            {{-- Voice Processing --}}
            <div class="vw-processing-card">
                <div class="vw-processing-header">
                    <span>⚡</span> {{ __('Voice Processing') }}
                </div>

                <div class="vw-process-option">
                    <div class="vw-option-info">
                        <span class="vw-option-name">{{ __('Normalize Volume') }}</span>
                        <span class="vw-option-desc">{{ __('Even out loud/quiet parts') }}</span>
                    </div>
                    <label class="vw-toggle-switch small">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.audioMix.normalize"
                            {{ ($assembly['audioMix']['normalize'] ?? true) ? 'checked' : '' }}
                        >
                        <span class="vw-toggle-slider"></span>
                    </label>
                </div>

                <div class="vw-process-option">
                    <div class="vw-option-info">
                        <span class="vw-option-name">{{ __('Noise Reduction') }}</span>
                        <span class="vw-option-desc">{{ __('Remove background noise') }}</span>
                    </div>
                    <label class="vw-toggle-switch small">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.audioMix.noiseReduction"
                            {{ ($assembly['audioMix']['noiseReduction'] ?? false) ? 'checked' : '' }}
                        >
                        <span class="vw-toggle-slider"></span>
                    </label>
                </div>

                <div class="vw-process-option">
                    <div class="vw-option-info">
                        <span class="vw-option-name">{{ __('Voice Enhancement') }}</span>
                        <span class="vw-option-desc">{{ __('Boost clarity & presence') }}</span>
                    </div>
                    <label class="vw-toggle-switch small">
                        <input
                            type="checkbox"
                            wire:model.live="assembly.audioMix.voiceEnhance"
                            {{ ($assembly['audioMix']['voiceEnhance'] ?? false) ? 'checked' : '' }}
                        >
                        <span class="vw-toggle-slider"></span>
                    </label>
                </div>
            </div>

            {{-- Voice Preset --}}
            <div class="vw-voice-presets">
                <div class="vw-presets-label">{{ __('Quick Presets') }}</div>
                <div class="vw-preset-buttons">
                    <button type="button" wire:click="applyVoicePreset('natural')" class="vw-preset-btn {{ ($assembly['audioMix']['voicePreset'] ?? 'natural') === 'natural' ? 'active' : '' }}">
                        🎯 {{ __('Natural') }}
                    </button>
                    <button type="button" wire:click="applyVoicePreset('broadcast')" class="vw-preset-btn {{ ($assembly['audioMix']['voicePreset'] ?? '') === 'broadcast' ? 'active' : '' }}">
                        📻 {{ __('Broadcast') }}
                    </button>
                    <button type="button" wire:click="applyVoicePreset('warm')" class="vw-preset-btn {{ ($assembly['audioMix']['voicePreset'] ?? '') === 'warm' ? 'active' : '' }}">
                        ☀️ {{ __('Warm') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MUSIC SECTION --}}
    <div x-show="activeSection === 'music'" x-cloak class="vw-section-content">
        {{-- Music Enable Toggle --}}
        <div class="vw-music-toggle">
            <div class="vw-toggle-content">
                <span class="vw-toggle-icon">🎵</span>
                <div class="vw-toggle-text">
                    <span class="vw-toggle-title">{{ __('Background Music') }}</span>
                    <span class="vw-toggle-desc">{{ __('Add music to your video') }}</span>
                </div>
            </div>
            <label class="vw-toggle-switch">
                <input
                    type="checkbox"
                    wire:model.live="assembly.music.enabled"
                    x-on:change="musicEnabled = $event.target.checked; updateMusicSetting('enabled', $event.target.checked)"
                    {{ ($assembly['music']['enabled'] ?? false) ? 'checked' : '' }}
                >
                <span class="vw-toggle-slider"></span>
            </label>
        </div>

        {{-- Music Controls --}}
        <div class="vw-music-controls" :class="{ 'disabled': !musicEnabled }">
            {{-- Selected Track --}}
            <div class="vw-selected-track" x-show="selectedTrack">
                <div class="vw-track-art">🎵</div>
                <div class="vw-track-info">
                    <span class="vw-track-name" x-text="musicTracks.find(t => t.id === selectedTrack)?.name || 'No track'"></span>
                    <span class="vw-track-artist" x-text="musicTracks.find(t => t.id === selectedTrack)?.artist || ''"></span>
                </div>
                <button type="button" @click="showMusicBrowser = true" class="vw-change-btn">
                    {{ __('Change') }}
                </button>
            </div>

            {{-- Browse Button --}}
            <button
                type="button"
                @click="showMusicBrowser = true"
                x-show="!selectedTrack"
                class="vw-browse-music-btn"
            >
                <span class="vw-browse-icon">🎶</span>
                <span class="vw-browse-text">{{ __('Browse Music Library') }}</span>
            </button>

            {{-- Volume Control --}}
            <div class="vw-music-volume">
                <div class="vw-volume-header">
                    <span class="vw-volume-label">{{ __('Music Volume') }}</span>
                    <span class="vw-volume-value">{{ $assembly['music']['volume'] ?? 30 }}%</span>
                </div>
                <input
                    type="range"
                    wire:model.live="assembly.music.volume"
                    x-on:input="updateMusicSetting('volume', parseInt($event.target.value))"
                    min="0" max="100" step="5"
                    class="vw-range-slider music"
                >
                <div class="vw-volume-labels">
                    <span>{{ __('Subtle') }}</span>
                    <span>{{ __('Loud') }}</span>
                </div>
            </div>

            {{-- Fade Controls --}}
            <div class="vw-fade-controls">
                <div class="vw-fade-item">
                    <label>{{ __('Fade In') }}</label>
                    <select wire:model.live="assembly.music.fadeIn" class="vw-select-sm">
                        <option value="0">{{ __('None') }}</option>
                        <option value="1">1s</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                        <option value="5">5s</option>
                    </select>
                </div>
                <div class="vw-fade-item">
                    <label>{{ __('Fade Out') }}</label>
                    <select wire:model.live="assembly.music.fadeOut" class="vw-select-sm">
                        <option value="0">{{ __('None') }}</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                        <option value="5">5s</option>
                    </select>
                </div>
            </div>

            {{-- Loop Settings --}}
            <div class="vw-loop-control">
                <div class="vw-loop-info">
                    <span class="vw-loop-icon">🔁</span>
                    <span class="vw-loop-label">{{ __('Loop Music') }}</span>
                </div>
                <label class="vw-toggle-switch small">
                    <input
                        type="checkbox"
                        wire:model.live="assembly.music.loop"
                        {{ ($assembly['music']['loop'] ?? true) ? 'checked' : '' }}
                    >
                    <span class="vw-toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    {{-- Music Browser Modal --}}
    <div
        x-show="showMusicBrowser"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="vw-music-browser-overlay"
        @click.self="showMusicBrowser = false"
        x-cloak
    >
        <div class="vw-music-browser" @click.stop>
            <div class="vw-browser-header">
                <h3>🎵 {{ __('Music Library') }}</h3>
                <button type="button" @click="showMusicBrowser = false" class="vw-browser-close">×</button>
            </div>

            {{-- Search & Filter --}}
            <div class="vw-browser-filters">
                <div class="vw-search-box">
                    <span class="vw-search-icon">🔍</span>
                    <input
                        type="text"
                        x-model="musicSearch"
                        placeholder="{{ __('Search tracks...') }}"
                        class="vw-search-input"
                    >
                </div>
                <div class="vw-category-tabs">
                    <button @click="musicCategory = 'all'" :class="{ 'active': musicCategory === 'all' }" class="vw-cat-btn">{{ __('All') }}</button>
                    <button @click="musicCategory = 'corporate'" :class="{ 'active': musicCategory === 'corporate' }" class="vw-cat-btn">{{ __('Corporate') }}</button>
                    <button @click="musicCategory = 'cinematic'" :class="{ 'active': musicCategory === 'cinematic' }" class="vw-cat-btn">{{ __('Cinematic') }}</button>
                    <button @click="musicCategory = 'ambient'" :class="{ 'active': musicCategory === 'ambient' }" class="vw-cat-btn">{{ __('Ambient') }}</button>
                    <button @click="musicCategory = 'pop'" :class="{ 'active': musicCategory === 'pop' }" class="vw-cat-btn">{{ __('Pop') }}</button>
                    <button @click="musicCategory = 'emotional'" :class="{ 'active': musicCategory === 'emotional' }" class="vw-cat-btn">{{ __('Emotional') }}</button>
                </div>
            </div>

            {{-- Track List --}}
            <div class="vw-track-list">
                <template x-for="track in filteredTracks" :key="track.id">
                    <div
                        class="vw-track-item"
                        :class="{ 'selected': selectedTrack === track.id }"
                        @click="selectTrack(track.id)"
                    >
                        <div class="vw-track-play" @click.stop="togglePreview(track.id)">
                            <span x-show="!(previewTrackId === track.id && isPlayingPreview)">▶</span>
                            <span x-show="previewTrackId === track.id && isPlayingPreview">⏸</span>
                        </div>
                        <div class="vw-track-details">
                            <span class="vw-track-title" x-text="track.name"></span>
                            <span class="vw-track-meta">
                                <span x-text="track.artist"></span> •
                                <span x-text="track.duration"></span> •
                                <span x-text="track.bpm + ' BPM'"></span>
                            </span>
                        </div>
                        <div class="vw-track-mood" x-text="track.mood"></div>
                        <div class="vw-track-check" x-show="selectedTrack === track.id">✓</div>
                    </div>
                </template>

                <div x-show="filteredTracks.length === 0" class="vw-no-tracks">
                    <span>🎵</span>
                    <p>{{ __('No tracks found') }}</p>
                </div>
            </div>

            {{-- Browser Footer --}}
            <div class="vw-browser-footer">
                <button type="button" @click="showMusicBrowser = false" class="vw-btn-secondary">
                    {{ __('Cancel') }}
                </button>
                <button type="button" @click="showMusicBrowser = false" class="vw-btn-primary" :disabled="!selectedTrack">
                    {{ __('Select Track') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Audio Tips --}}
    <div class="vw-audio-tips">
        <div class="vw-tip-icon">💡</div>
        <div class="vw-tip-text">
            {{ __('Pro tip: Enable Smart Audio AI to automatically set the perfect balance between voice and music.') }}
        </div>
    </div>
</div>

<style>
    .vw-audio-tab {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Section Tabs */
    .vw-audio-tabs {
        display: flex;
        gap: 0.25rem;
        padding: 0.25rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-audio-tab-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 0.35rem;
        transition: all 0.2s;
    }

    .vw-audio-tab-btn:hover {
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-audio-tab-btn.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.2));
        color: white;
    }

    .vw-section-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Smart Audio AI Card */
    .vw-smart-audio-card {
        padding: 0.75rem;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(6, 182, 212, 0.1));
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.5rem;
    }

    .vw-smart-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .vw-smart-icon {
        font-size: 1.5rem;
    }

    .vw-smart-text {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .vw-smart-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-smart-desc {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-smart-content {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-analyze-btn {
        width: 100%;
        padding: 0.6rem;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        border-radius: 0.4rem;
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-analyze-btn:hover:not(:disabled) {
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-analyze-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .vw-analyzing {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .vw-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .vw-smart-hint {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.5rem;
        text-align: center;
    }

    /* Audio Mixer */
    .vw-audio-mixer {
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-mixer-header {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.75rem;
    }

    .vw-channel {
        margin-bottom: 0.75rem;
    }

    .vw-channel.disabled {
        opacity: 0.4;
    }

    .vw-channel-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .vw-channel-icon {
        font-size: 0.9rem;
    }

    .vw-channel-name {
        flex: 1;
        font-size: 0.8rem;
        color: white;
    }

    .vw-channel-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #a78bfa;
    }

    .vw-channel-meter {
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.35rem;
    }

    .vw-meter-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s;
    }

    .vw-meter-fill.voice {
        background: linear-gradient(90deg, #8b5cf6, #a78bfa);
    }

    .vw-meter-fill.music {
        background: linear-gradient(90deg, #06b6d4, #22d3ee);
    }

    .vw-channel-slider {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        cursor: pointer;
    }

    .vw-channel-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid white;
    }

    .vw-channel-slider.voice::-webkit-slider-thumb {
        background: #8b5cf6;
    }

    .vw-channel-slider.music::-webkit-slider-thumb {
        background: #06b6d4;
    }

    /* Mix Visual */
    .vw-mix-visual {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 4px;
        height: 50px;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-visual-bar {
        width: 8px;
        border-radius: 4px;
        animation: pulse 1.5s ease-in-out infinite;
    }

    .vw-visual-bar.voice {
        background: linear-gradient(to top, #8b5cf6, #a78bfa);
    }

    .vw-visual-bar.music {
        background: linear-gradient(to top, #06b6d4, #22d3ee);
        animation-delay: 0.2s;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }

    /* Ducking Card */
    .vw-ducking-card {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-ducking-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-ducking-icon {
        font-size: 1rem;
    }

    .vw-ducking-title {
        display: block;
        font-size: 0.8rem;
        color: white;
    }

    .vw-ducking-desc {
        display: block;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-duck-settings {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Voice Section */
    .vw-voice-section {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .vw-section-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-voice-control {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-control-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.4rem;
    }

    .vw-control-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-control-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #8b5cf6;
    }

    /* Processing Card */
    .vw-processing-card {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-processing-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.6rem;
    }

    .vw-process-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .vw-process-option:last-child {
        border-bottom: none;
    }

    .vw-option-info {
        display: flex;
        flex-direction: column;
    }

    .vw-option-name {
        font-size: 0.75rem;
        color: white;
    }

    .vw-option-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Voice Presets */
    .vw-voice-presets {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-presets-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    .vw-preset-buttons {
        display: flex;
        gap: 0.4rem;
    }

    .vw-preset-btn {
        flex: 1;
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-preset-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-preset-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: white;
    }

    /* Music Toggle */
    .vw-music-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
    }

    .vw-toggle-content {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .vw-toggle-icon {
        font-size: 1.25rem;
    }

    .vw-toggle-text {
        display: flex;
        flex-direction: column;
    }

    .vw-toggle-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-toggle-desc {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Toggle Switch */
    .vw-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .vw-toggle-switch.small {
        width: 36px;
        height: 20px;
    }

    .vw-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .vw-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: 0.3s;
        border-radius: 24px;
    }

    .vw-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .vw-toggle-switch.small .vw-toggle-slider:before {
        height: 14px;
        width: 14px;
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider:before {
        transform: translateX(20px);
    }

    .vw-toggle-switch.small input:checked + .vw-toggle-slider:before {
        transform: translateX(16px);
    }

    /* Music Controls */
    .vw-music-controls {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        transition: opacity 0.3s;
    }

    .vw-music-controls.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .vw-selected-track {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem;
        background: rgba(6, 182, 212, 0.1);
        border: 1px solid rgba(6, 182, 212, 0.3);
        border-radius: 0.5rem;
    }

    .vw-track-art {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(6, 182, 212, 0.2);
        border-radius: 0.35rem;
        font-size: 1.25rem;
    }

    .vw-track-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .vw-track-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-track-artist {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-change-btn {
        padding: 0.4rem 0.6rem;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 0.35rem;
        color: white;
        font-size: 0.7rem;
        cursor: pointer;
    }

    .vw-browse-music-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1.5rem;
        background: rgba(0, 0, 0, 0.2);
        border: 2px dashed rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-browse-music-btn:hover {
        border-color: rgba(6, 182, 212, 0.5);
        background: rgba(6, 182, 212, 0.1);
    }

    .vw-browse-icon {
        font-size: 2rem;
    }

    .vw-browse-text {
        font-size: 0.8rem;
    }

    /* Music Volume */
    .vw-music-volume {
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-volume-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.4rem;
    }

    .vw-volume-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-volume-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #06b6d4;
    }

    .vw-volume-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.25rem;
    }

    .vw-range-slider {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        cursor: pointer;
    }

    .vw-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        cursor: pointer;
        border: 2px solid white;
    }

    .vw-range-slider.voice::-webkit-slider-thumb {
        background: #8b5cf6;
    }

    .vw-range-slider.music::-webkit-slider-thumb {
        background: #06b6d4;
    }

    /* Fade Controls */
    .vw-fade-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .vw-fade-item {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .vw-fade-item label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-select-sm {
        padding: 0.4rem 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: white;
        font-size: 0.75rem;
        cursor: pointer;
    }

    /* Loop Control */
    .vw-loop-control {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.6rem;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0.4rem;
    }

    .vw-loop-info {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-loop-icon {
        font-size: 0.9rem;
    }

    .vw-loop-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }

    /* Music Browser Overlay */
    .vw-music-browser-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        backdrop-filter: blur(4px);
    }

    .vw-music-browser {
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.98), rgba(20, 20, 35, 0.98));
        border: 1px solid rgba(6, 182, 212, 0.3);
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .vw-browser-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-browser-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: white;
        margin: 0;
    }

    .vw-browser-close {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 1.25rem;
        cursor: pointer;
    }

    .vw-browser-filters {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-search-box {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .vw-search-icon {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-search-input {
        flex: 1;
        border: none;
        background: transparent;
        color: white;
        font-size: 0.85rem;
        outline: none;
    }

    .vw-search-input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-category-tabs {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .vw-cat-btn {
        padding: 0.35rem 0.6rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-cat-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-cat-btn.active {
        background: rgba(6, 182, 212, 0.2);
        border-color: rgba(6, 182, 212, 0.4);
        color: #22d3ee;
    }

    .vw-track-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem;
    }

    .vw-track-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid transparent;
        border-radius: 0.5rem;
        margin-bottom: 0.35rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-track-item:hover {
        background: rgba(6, 182, 212, 0.1);
        border-color: rgba(6, 182, 212, 0.2);
    }

    .vw-track-item.selected {
        background: rgba(6, 182, 212, 0.15);
        border-color: rgba(6, 182, 212, 0.4);
    }

    .vw-track-play {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(6, 182, 212, 0.2);
        border-radius: 50%;
        color: #22d3ee;
        font-size: 0.8rem;
    }

    .vw-track-details {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .vw-track-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-track-meta {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-track-mood {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 0.25rem;
        color: rgba(255, 255, 255, 0.6);
        text-transform: capitalize;
    }

    .vw-track-check {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #10b981;
        border-radius: 50%;
        color: white;
        font-size: 0.75rem;
    }

    .vw-no-tracks {
        text-align: center;
        padding: 2rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-no-tracks span {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.5rem;
    }

    .vw-browser-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-btn-secondary,
    .vw-btn-primary {
        padding: 0.6rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .vw-btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-btn-primary {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
    }

    .vw-btn-primary:hover:not(:disabled) {
        box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
    }

    .vw-btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Setting Rows */
    .vw-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-setting-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-setting-value {
        font-size: 0.75rem;
        font-weight: 600;
        color: #8b5cf6;
    }

    /* Audio Tips */
    .vw-audio-tips {
        display: flex;
        gap: 0.6rem;
        padding: 0.6rem;
        background: rgba(6, 182, 212, 0.1);
        border: 1px solid rgba(6, 182, 212, 0.2);
        border-radius: 0.5rem;
    }

    .vw-tip-icon {
        font-size: 1rem;
    }

    .vw-tip-text {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.4;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
