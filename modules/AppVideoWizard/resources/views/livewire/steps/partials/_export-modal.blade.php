{{-- Phase 6: Export Modal - Platform Selection, Quality, Progress Tracking --}}
@php
    $exportConfig = $assembly['export'] ?? [];
    $isExporting = ($assembly['assemblyStatus'] ?? 'pending') === 'rendering';
    $exportComplete = ($assembly['assemblyStatus'] ?? 'pending') === 'complete';
    $renderProgress = $assembly['renderProgress'] ?? 0;
    $finalVideoUrl = $assembly['finalVideoUrl'] ?? null;
@endphp

<div
    x-show="showExportModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="vw-modal-backdrop vw-export-backdrop"
    @click.self="!exporting && (showExportModal = false)"
    x-cloak
>
    <div
        class="vw-export-modal-full"
        x-data="{
            // Export state
            exporting: @js($isExporting),
            exportComplete: @js($exportComplete),
            progress: @js($renderProgress),
            currentScene: 0,
            totalScenes: {{ count($script['scenes'] ?? []) }},
            finalVideoUrl: @js($finalVideoUrl),

            // Export settings
            platform: '{{ $exportConfig['platform'] ?? 'youtube' }}',
            quality: '{{ $exportConfig['quality'] ?? '1080p' }}',
            format: '{{ $exportConfig['format'] ?? 'mp4' }}',
            codec: '{{ $exportConfig['codec'] ?? 'h264' }}',
            fps: {{ $exportConfig['fps'] ?? 30 }},
            bitrate: '{{ $exportConfig['bitrate'] ?? 'auto' }}',
            audioCodec: '{{ $exportConfig['audioCodec'] ?? 'aac' }}',
            audioBitrate: {{ $exportConfig['audioBitrate'] ?? 192 }},

            // UI state
            activeSection: 'platform',
            showAdvanced: false,

            // Platform presets
            platforms: {
                youtube: {
                    name: 'YouTube',
                    icon: '▶️',
                    color: '#FF0000',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '12000',
                    aspectRatio: '16:9',
                    maxDuration: 43200,
                    description: 'Best for longer content'
                },
                tiktok: {
                    name: 'TikTok',
                    icon: '🎵',
                    color: '#00F2EA',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '6000',
                    aspectRatio: '9:16',
                    maxDuration: 600,
                    description: 'Vertical short-form'
                },
                instagram_reels: {
                    name: 'Instagram Reels',
                    icon: '📸',
                    color: '#E1306C',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '8000',
                    aspectRatio: '9:16',
                    maxDuration: 90,
                    description: 'Vertical short videos'
                },
                instagram_feed: {
                    name: 'Instagram Feed',
                    icon: '📷',
                    color: '#C13584',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '8000',
                    aspectRatio: '1:1',
                    maxDuration: 60,
                    description: 'Square format'
                },
                twitter: {
                    name: 'X (Twitter)',
                    icon: '🐦',
                    color: '#1DA1F2',
                    quality: '720p',
                    fps: 30,
                    bitrate: '5000',
                    aspectRatio: '16:9',
                    maxDuration: 140,
                    description: 'Short clips'
                },
                facebook: {
                    name: 'Facebook',
                    icon: '👥',
                    color: '#1877F2',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '8000',
                    aspectRatio: '16:9',
                    maxDuration: 14400,
                    description: 'Social sharing'
                },
                linkedin: {
                    name: 'LinkedIn',
                    icon: '💼',
                    color: '#0A66C2',
                    quality: '1080p',
                    fps: 30,
                    bitrate: '8000',
                    aspectRatio: '16:9',
                    maxDuration: 600,
                    description: 'Professional content'
                },
                custom: {
                    name: 'Custom',
                    icon: '⚙️',
                    color: '#8b5cf6',
                    quality: '1080p',
                    fps: 30,
                    bitrate: 'auto',
                    aspectRatio: null,
                    maxDuration: null,
                    description: 'Full control'
                }
            },

            // Quality options
            qualities: [
                { value: '4k', label: '4K (2160p)', width: 3840, height: 2160, premium: true },
                { value: '1440p', label: '2K (1440p)', width: 2560, height: 1440, premium: true },
                { value: '1080p', label: 'Full HD (1080p)', width: 1920, height: 1080, premium: false },
                { value: '720p', label: 'HD (720p)', width: 1280, height: 720, premium: false },
                { value: '480p', label: 'SD (480p)', width: 854, height: 480, premium: false }
            ],

            // Format options
            formats: [
                { value: 'mp4', label: 'MP4', description: 'Universal compatibility', icon: '🎬' },
                { value: 'mov', label: 'MOV', description: 'Apple/ProRes quality', icon: '🍎' },
                { value: 'webm', label: 'WebM', description: 'Web optimized', icon: '🌐' },
                { value: 'gif', label: 'GIF', description: 'Animated preview', icon: '🖼️' }
            ],

            // FPS options
            fpsOptions: [24, 25, 30, 50, 60],

            // Methods
            selectPlatform(key) {
                this.platform = key;
                const preset = this.platforms[key];
                if (preset && key !== 'custom') {
                    this.quality = preset.quality;
                    this.fps = preset.fps;
                    this.bitrate = preset.bitrate;
                }
                $wire.updateExportSetting('platform', key);
            },

            updateSetting(key, value) {
                this[key] = value;
                $wire.updateExportSetting(key, value);
            },

            getEstimatedFileSize() {
                const duration = {{ $this->getTotalDuration() ?? 60 }};
                const bitrateKbps = this.bitrate === 'auto' ? 8000 : parseInt(this.bitrate);
                const audioBitrateKbps = this.audioBitrate;
                const totalBitrate = bitrateKbps + audioBitrateKbps;
                const sizeMB = (totalBitrate * duration) / 8 / 1024;
                return sizeMB < 1024 ? sizeMB.toFixed(1) + ' MB' : (sizeMB / 1024).toFixed(2) + ' GB';
            },

            getResolution() {
                const q = this.qualities.find(q => q.value === this.quality);
                return q ? `${q.width}×${q.height}` : '1920×1080';
            },

            // Polling interval reference
            pollInterval: null,
            statusMessage: '',

            startExport() {
                this.exporting = true;
                this.progress = 0;
                this.currentScene = 0;
                this.statusMessage = 'Starting export...';
                $wire.startVideoExport();

                // Start polling for status updates (Phase 7)
                this.startPolling();
            },

            startPolling() {
                // Poll every 3 seconds for status updates
                this.pollInterval = setInterval(() => {
                    if (!this.exporting || this.exportComplete) {
                        this.stopPolling();
                        return;
                    }
                    $wire.pollExportStatus();
                }, 3000);
            },

            stopPolling() {
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
            },

            cancelExport() {
                if (confirm('Are you sure you want to cancel the export?')) {
                    this.stopPolling();
                    this.exporting = false;
                    $wire.cancelVideoExport();
                }
            },

            downloadVideo() {
                if (this.finalVideoUrl) {
                    window.open(this.finalVideoUrl, '_blank');
                }
            },

            formatDuration(seconds) {
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = Math.floor(seconds % 60);
                if (h > 0) {
                    return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                }
                return `${m}:${s.toString().padStart(2, '0')}`;
            }
        }"
        x-init="
            // Listen for export progress updates from Livewire
            Livewire.on('export-progress', (data) => {
                progress = data.progress || 0;
                currentScene = data.currentScene || 0;
                statusMessage = data.message || 'Processing...';
                if (data.complete) {
                    exportComplete = true;
                    exporting = false;
                    finalVideoUrl = data.videoUrl || null;
                    stopPolling();
                }
            });

            // Listen for export started
            Livewire.on('export-started', (data) => {
                console.log('Export started:', data.jobId);
            });

            // Listen for export error
            Livewire.on('export-error', (data) => {
                exporting = false;
                stopPolling();
                alert('Export failed: ' + (data.message || 'Unknown error'));
            });

            // Cleanup on component destroy
            $cleanup(() => {
                stopPolling();
            });
        "
        @click.stop
    >
        {{-- Modal Header --}}
        <div class="vw-export-header">
            <div class="vw-export-title">
                <span class="vw-export-icon">🚀</span>
                <h3>{{ __('Export Video') }}</h3>
            </div>
            <button
                type="button"
                @click="!exporting && (showExportModal = false)"
                class="vw-modal-close"
                :disabled="exporting"
            >×</button>
        </div>

        {{-- Export Content --}}
        <div class="vw-export-content" x-show="!exporting && !exportComplete">
            {{-- Video Summary --}}
            <div class="vw-export-summary-bar">
                <div class="vw-summary-stat">
                    <span class="vw-stat-icon">📹</span>
                    <span class="vw-stat-value">{{ count($script['scenes'] ?? []) }}</span>
                    <span class="vw-stat-label">{{ __('Scenes') }}</span>
                </div>
                <div class="vw-summary-stat">
                    <span class="vw-stat-icon">⏱️</span>
                    <span class="vw-stat-value" x-text="formatDuration(totalDuration || {{ $this->getTotalDuration() ?? 0 }})"></span>
                    <span class="vw-stat-label">{{ __('Duration') }}</span>
                </div>
                <div class="vw-summary-stat">
                    <span class="vw-stat-icon">📐</span>
                    <span class="vw-stat-value">{{ $aspectRatio }}</span>
                    <span class="vw-stat-label">{{ __('Aspect') }}</span>
                </div>
                <div class="vw-summary-stat">
                    <span class="vw-stat-icon">💾</span>
                    <span class="vw-stat-value" x-text="getEstimatedFileSize()"></span>
                    <span class="vw-stat-label">{{ __('Est. Size') }}</span>
                </div>
            </div>

            {{-- Section Tabs --}}
            <div class="vw-export-tabs">
                <button
                    type="button"
                    @click="activeSection = 'platform'"
                    :class="{'active': activeSection === 'platform'}"
                    class="vw-export-tab"
                >
                    <span>📱</span> {{ __('Platform') }}
                </button>
                <button
                    type="button"
                    @click="activeSection = 'quality'"
                    :class="{'active': activeSection === 'quality'}"
                    class="vw-export-tab"
                >
                    <span>🎥</span> {{ __('Quality') }}
                </button>
                <button
                    type="button"
                    @click="activeSection = 'format'"
                    :class="{'active': activeSection === 'format'}"
                    class="vw-export-tab"
                >
                    <span>📁</span> {{ __('Format') }}
                </button>
            </div>

            {{-- Platform Selection --}}
            <div class="vw-export-section" x-show="activeSection === 'platform'">
                <div class="vw-platform-grid">
                    <template x-for="(preset, key) in platforms" :key="key">
                        <button
                            type="button"
                            @click="selectPlatform(key)"
                            :class="{'active': platform === key}"
                            class="vw-platform-card"
                            :style="platform === key ? `border-color: ${preset.color}; box-shadow: 0 0 20px ${preset.color}30` : ''"
                        >
                            <span class="vw-platform-icon" x-text="preset.icon"></span>
                            <span class="vw-platform-name" x-text="preset.name"></span>
                            <span class="vw-platform-desc" x-text="preset.description"></span>
                            <div class="vw-platform-specs" x-show="platform === key">
                                <span x-text="preset.quality"></span>
                                <span>•</span>
                                <span x-text="preset.fps + ' fps'"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Quality Settings --}}
            <div class="vw-export-section" x-show="activeSection === 'quality'">
                {{-- Resolution --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>📏</span> {{ __('Resolution') }}
                    </label>
                    <div class="vw-quality-options">
                        <template x-for="q in qualities" :key="q.value">
                            <button
                                type="button"
                                @click="updateSetting('quality', q.value)"
                                :class="{'active': quality === q.value, 'premium': q.premium}"
                                class="vw-quality-btn"
                            >
                                <span class="vw-quality-label" x-text="q.label"></span>
                                <span class="vw-quality-res" x-text="q.width + '×' + q.height"></span>
                                <span x-show="q.premium" class="vw-premium-badge">PRO</span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Frame Rate --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>🎞️</span> {{ __('Frame Rate') }}
                    </label>
                    <div class="vw-fps-options">
                        <template x-for="f in fpsOptions" :key="f">
                            <button
                                type="button"
                                @click="updateSetting('fps', f)"
                                :class="{'active': fps === f}"
                                class="vw-fps-btn"
                            >
                                <span x-text="f"></span>
                                <span class="vw-fps-unit">fps</span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Bitrate --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>📊</span> {{ __('Video Bitrate') }}
                    </label>
                    <div class="vw-bitrate-input">
                        <select x-model="bitrate" @change="updateSetting('bitrate', bitrate)" class="vw-select">
                            <option value="auto">{{ __('Auto (Recommended)') }}</option>
                            <option value="4000">4 Mbps (Low)</option>
                            <option value="6000">6 Mbps</option>
                            <option value="8000">8 Mbps</option>
                            <option value="12000">12 Mbps</option>
                            <option value="16000">16 Mbps</option>
                            <option value="20000">20 Mbps (High)</option>
                            <option value="30000">30 Mbps (Ultra)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Format Settings --}}
            <div class="vw-export-section" x-show="activeSection === 'format'">
                {{-- Container Format --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>📦</span> {{ __('Container Format') }}
                    </label>
                    <div class="vw-format-options">
                        <template x-for="f in formats" :key="f.value">
                            <button
                                type="button"
                                @click="updateSetting('format', f.value)"
                                :class="{'active': format === f.value}"
                                class="vw-format-btn"
                            >
                                <span class="vw-format-icon" x-text="f.icon"></span>
                                <span class="vw-format-label" x-text="f.label"></span>
                                <span class="vw-format-desc" x-text="f.description"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Video Codec --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>🎬</span> {{ __('Video Codec') }}
                    </label>
                    <select x-model="codec" @change="updateSetting('codec', codec)" class="vw-select">
                        <option value="h264">H.264 (AVC) - Best compatibility</option>
                        <option value="h265">H.265 (HEVC) - Better compression</option>
                        <option value="vp9">VP9 - Web optimized</option>
                        <option value="av1">AV1 - Best quality/size</option>
                        <option value="prores">ProRes - Professional editing</option>
                    </select>
                </div>

                {{-- Audio Settings --}}
                <div class="vw-setting-group">
                    <label class="vw-setting-label">
                        <span>🔊</span> {{ __('Audio Settings') }}
                    </label>
                    <div class="vw-audio-settings">
                        <div class="vw-audio-option">
                            <label>{{ __('Codec') }}</label>
                            <select x-model="audioCodec" @change="updateSetting('audioCodec', audioCodec)" class="vw-select-sm">
                                <option value="aac">AAC</option>
                                <option value="mp3">MP3</option>
                                <option value="opus">Opus</option>
                                <option value="pcm">PCM (Lossless)</option>
                            </select>
                        </div>
                        <div class="vw-audio-option">
                            <label>{{ __('Bitrate') }}</label>
                            <select x-model="audioBitrate" @change="updateSetting('audioBitrate', parseInt(audioBitrate))" class="vw-select-sm">
                                <option value="128">128 kbps</option>
                                <option value="192">192 kbps</option>
                                <option value="256">256 kbps</option>
                                <option value="320">320 kbps</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Export Summary --}}
            <div class="vw-export-final-summary">
                <div class="vw-final-setting">
                    <span class="vw-final-label">{{ __('Platform') }}</span>
                    <span class="vw-final-value" x-text="platforms[platform]?.name || 'Custom'"></span>
                </div>
                <div class="vw-final-setting">
                    <span class="vw-final-label">{{ __('Quality') }}</span>
                    <span class="vw-final-value" x-text="getResolution() + ' @ ' + fps + 'fps'"></span>
                </div>
                <div class="vw-final-setting">
                    <span class="vw-final-label">{{ __('Format') }}</span>
                    <span class="vw-final-value" x-text="format.toUpperCase() + ' / ' + codec.toUpperCase()"></span>
                </div>
            </div>
        </div>

        {{-- Exporting Progress View --}}
        <div class="vw-export-progress-view" x-show="exporting && !exportComplete">
            <div class="vw-progress-header">
                <div class="vw-progress-spinner"></div>
                <h4>{{ __('Rendering your video...') }}</h4>
                <p class="vw-progress-subtitle" x-text="statusMessage || '{{ __('This may take a few minutes depending on video length and quality settings.') }}'"></p>
            </div>

            {{-- Overall Progress --}}
            <div class="vw-progress-main">
                <div class="vw-progress-bar-large">
                    <div class="vw-progress-fill-animated" :style="`width: ${progress}%`"></div>
                </div>
                <div class="vw-progress-stats">
                    <span class="vw-progress-percent" x-text="progress + '%'"></span>
                    <span class="vw-progress-scene">
                        {{ __('Scene') }} <span x-text="currentScene + 1"></span> / <span x-text="totalScenes"></span>
                    </span>
                </div>
            </div>

            {{-- Scene Progress Indicators --}}
            <div class="vw-scene-indicators">
                <label class="vw-indicators-label">{{ __('Scene Progress') }}</label>
                <div class="vw-scene-dots">
                    @foreach($script['scenes'] ?? [] as $index => $scene)
                        <div
                            class="vw-scene-dot"
                            :class="{
                                'completed': currentScene > {{ $index }},
                                'current': currentScene === {{ $index }},
                                'pending': currentScene < {{ $index }}
                            }"
                            title="{{ __('Scene') }} {{ $index + 1 }}"
                        >
                            <span class="vw-dot-number">{{ $index + 1 }}</span>
                            <div class="vw-dot-progress" x-show="currentScene === {{ $index }}">
                                <div class="vw-dot-fill" :style="`width: ${(progress % (100 / totalScenes)) * totalScenes}%`"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Processing Steps --}}
            <div class="vw-processing-steps">
                <div class="vw-step" :class="{'active': progress < 10, 'done': progress >= 10}">
                    <span class="vw-step-icon">🎨</span>
                    <span>{{ __('Preparing assets...') }}</span>
                </div>
                <div class="vw-step" :class="{'active': progress >= 10 && progress < 80, 'done': progress >= 80}">
                    <span class="vw-step-icon">🎬</span>
                    <span>{{ __('Rendering scenes...') }}</span>
                </div>
                <div class="vw-step" :class="{'active': progress >= 80 && progress < 95, 'done': progress >= 95}">
                    <span class="vw-step-icon">🔊</span>
                    <span>{{ __('Processing audio...') }}</span>
                </div>
                <div class="vw-step" :class="{'active': progress >= 95, 'done': progress >= 100}">
                    <span class="vw-step-icon">📦</span>
                    <span>{{ __('Finalizing export...') }}</span>
                </div>
            </div>
        </div>

        {{-- Export Complete View --}}
        <div class="vw-export-complete-view" x-show="exportComplete">
            <div class="vw-complete-icon">✅</div>
            <h4>{{ __('Export Complete!') }}</h4>
            <p class="vw-complete-text">{{ __('Your video has been successfully rendered and is ready for download.') }}</p>

            {{-- Video Preview Thumbnail --}}
            <div class="vw-complete-preview">
                <div class="vw-preview-thumb">
                    <span>🎬</span>
                </div>
                <div class="vw-preview-info">
                    <span class="vw-preview-name">{{ $project->title ?? 'video' }}.{{ $exportConfig['format'] ?? 'mp4' }}</span>
                    <span class="vw-preview-meta" x-text="getResolution() + ' • ' + fps + 'fps • ' + getEstimatedFileSize()"></span>
                </div>
            </div>

            {{-- Download Actions --}}
            <div class="vw-complete-actions">
                <button type="button" @click="downloadVideo()" class="vw-download-btn primary">
                    <span>⬇️</span> {{ __('Download Video') }}
                </button>
                <button type="button" @click="showExportModal = false" class="vw-download-btn secondary">
                    {{ __('Close') }}
                </button>
            </div>

            {{-- Share Options --}}
            <div class="vw-share-options">
                <span class="vw-share-label">{{ __('Share to:') }}</span>
                <div class="vw-share-buttons">
                    <button type="button" class="vw-share-btn youtube" title="YouTube">▶️</button>
                    <button type="button" class="vw-share-btn tiktok" title="TikTok">🎵</button>
                    <button type="button" class="vw-share-btn instagram" title="Instagram">📸</button>
                    <button type="button" class="vw-share-btn twitter" title="X">🐦</button>
                    <button type="button" class="vw-share-btn link" title="Copy Link">🔗</button>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="vw-export-footer">
            <template x-if="!exporting && !exportComplete">
                <div class="vw-footer-actions">
                    <button type="button" @click="showExportModal = false" class="vw-export-btn secondary">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" @click="startExport()" class="vw-export-btn primary">
                        <span>🚀</span> {{ __('Start Export') }}
                    </button>
                </div>
            </template>
            <template x-if="exporting && !exportComplete">
                <div class="vw-footer-actions">
                    <button type="button" @click="cancelExport()" class="vw-export-btn danger">
                        <span>✕</span> {{ __('Cancel Export') }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    /* Export Modal Full */
    .vw-export-modal-full {
        background: linear-gradient(135deg, rgba(20, 20, 35, 0.98), rgba(15, 15, 30, 0.98));
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 1.25rem;
        width: 95%;
        max-width: 680px;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 60px rgba(139, 92, 246, 0.15);
    }

    /* Export Header */
    .vw-export-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-export-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-export-icon {
        font-size: 1.5rem;
    }

    .vw-export-title h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        margin: 0;
    }

    /* Export Content */
    .vw-export-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem;
    }

    /* Summary Bar */
    .vw-export-summary-bar {
        display: flex;
        justify-content: space-around;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
    }

    .vw-summary-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-stat-icon {
        font-size: 1.25rem;
    }

    .vw-stat-value {
        font-size: 1rem;
        font-weight: 600;
        color: white;
    }

    .vw-stat-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Section Tabs */
    .vw-export-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.25rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-export-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.6rem;
        background: transparent;
        border: none;
        border-radius: 0.4rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-export-tab:hover {
        color: white;
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-export-tab.active {
        color: white;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.2));
    }

    /* Export Section */
    .vw-export-section {
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Platform Grid */
    .vw-platform-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    .vw-platform-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.6rem;
        cursor: pointer;
        transition: all 0.25s;
        min-height: 100px;
    }

    .vw-platform-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .vw-platform-card.active {
        background: rgba(139, 92, 246, 0.15);
    }

    .vw-platform-icon {
        font-size: 1.5rem;
        margin-bottom: 0.35rem;
    }

    .vw-platform-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-align: center;
    }

    .vw-platform-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
        text-align: center;
        margin-top: 0.2rem;
    }

    .vw-platform-specs {
        display: flex;
        gap: 0.35rem;
        font-size: 0.6rem;
        color: #8b5cf6;
        margin-top: 0.4rem;
    }

    /* Setting Group */
    .vw-setting-group {
        margin-bottom: 1.25rem;
    }

    .vw-setting-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: white;
        margin-bottom: 0.6rem;
    }

    /* Quality Options */
    .vw-quality-options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .vw-quality-btn {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.6rem 0.8rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 100px;
    }

    .vw-quality-btn:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .vw-quality-btn.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.15));
        border-color: #8b5cf6;
    }

    .vw-quality-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-quality-res {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-premium-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        padding: 0.15rem 0.35rem;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        border-radius: 0.25rem;
        font-size: 0.5rem;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
    }

    /* FPS Options */
    .vw-fps-options {
        display: flex;
        gap: 0.5rem;
    }

    .vw-fps-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-fps-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-fps-btn.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.15));
        border-color: #8b5cf6;
    }

    .vw-fps-unit {
        font-size: 0.55rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Select */
    .vw-select,
    .vw-select-sm {
        width: 100%;
        padding: 0.6rem 0.8rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .vw-select-sm {
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
    }

    .vw-select:focus,
    .vw-select-sm:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .vw-select option,
    .vw-select-sm option {
        background: #1a1a2e;
        color: white;
    }

    /* Format Options */
    .vw-format-options {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    .vw-format-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-format-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-format-btn.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(6, 182, 212, 0.15));
        border-color: #8b5cf6;
    }

    .vw-format-icon {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }

    .vw-format-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-format-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
        text-align: center;
    }

    /* Audio Settings */
    .vw-audio-settings {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .vw-audio-option {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .vw-audio-option label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Final Summary */
    .vw-export-final-summary {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .vw-final-setting {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
    }

    .vw-final-label {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
    }

    .vw-final-value {
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
    }

    /* Progress View */
    .vw-export-progress-view {
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .vw-progress-header {
        margin-bottom: 2rem;
    }

    .vw-progress-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid rgba(139, 92, 246, 0.2);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        margin: 0 auto 1rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .vw-progress-header h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        margin: 0 0 0.5rem 0;
    }

    .vw-progress-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        margin: 0;
    }

    /* Main Progress Bar */
    .vw-progress-main {
        margin-bottom: 1.5rem;
    }

    .vw-progress-bar-large {
        height: 12px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .vw-progress-fill-animated {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4, #8b5cf6);
        background-size: 200% 100%;
        border-radius: 6px;
        transition: width 0.3s ease;
        animation: shimmer 2s linear infinite;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .vw-progress-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .vw-progress-percent {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
    }

    .vw-progress-scene {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Scene Indicators */
    .vw-scene-indicators {
        margin-bottom: 1.5rem;
    }

    .vw-indicators-label {
        display: block;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-scene-dots {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .vw-scene-dot {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        overflow: hidden;
    }

    .vw-scene-dot.completed {
        background: linear-gradient(135deg, #10b981, #059669);
        border-color: #10b981;
    }

    .vw-scene-dot.current {
        border-color: #8b5cf6;
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.5);
        animation: pulse 1.5s ease infinite;
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 15px rgba(139, 92, 246, 0.5); }
        50% { box-shadow: 0 0 25px rgba(139, 92, 246, 0.8); }
    }

    .vw-dot-number {
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
        z-index: 1;
    }

    .vw-dot-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: rgba(139, 92, 246, 0.3);
    }

    .vw-dot-fill {
        height: 100%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        transition: width 0.3s;
    }

    /* Processing Steps */
    .vw-processing-steps {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        text-align: left;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.4);
        transition: all 0.3s;
    }

    .vw-step.active {
        color: white;
        background: rgba(139, 92, 246, 0.1);
        border-radius: 0.4rem;
    }

    .vw-step.done {
        color: #10b981;
    }

    .vw-step-icon {
        font-size: 1rem;
    }

    /* Complete View */
    .vw-export-complete-view {
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .vw-complete-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        animation: bounceIn 0.5s ease;
    }

    @keyframes bounceIn {
        0% { transform: scale(0); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .vw-export-complete-view h4 {
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
        margin: 0 0 0.5rem 0;
    }

    .vw-complete-text {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
        margin: 0 0 1.5rem 0;
    }

    .vw-complete-preview {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .vw-preview-thumb {
        width: 60px;
        height: 45px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 0.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .vw-preview-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .vw-preview-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
    }

    .vw-preview-meta {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Download Actions */
    .vw-complete-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1.5rem;
    }

    .vw-download-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .vw-download-btn.primary {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        color: white;
    }

    .vw-download-btn.primary:hover {
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
    }

    .vw-download-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-download-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    /* Share Options */
    .vw-share-options {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-share-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-share-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .vw-share-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-share-btn:hover {
        transform: scale(1.1);
    }

    .vw-share-btn.youtube:hover { background: #FF0000; }
    .vw-share-btn.tiktok:hover { background: #00F2EA; }
    .vw-share-btn.instagram:hover { background: #E1306C; }
    .vw-share-btn.twitter:hover { background: #1DA1F2; }
    .vw-share-btn.link:hover { background: #8b5cf6; }

    /* Export Footer */
    .vw-export-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
    }

    .vw-footer-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .vw-export-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .vw-export-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .vw-export-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .vw-export-btn.primary {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        color: white;
    }

    .vw-export-btn.primary:hover {
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-export-btn.danger {
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid rgba(239, 68, 68, 0.4);
        color: #ef4444;
    }

    .vw-export-btn.danger:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    /* Responsive */
    @media (max-width: 600px) {
        .vw-platform-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .vw-format-options {
            grid-template-columns: repeat(2, 1fr);
        }

        .vw-quality-options {
            flex-direction: column;
        }

        .vw-quality-btn {
            width: 100%;
        }

        .vw-export-summary-bar {
            flex-wrap: wrap;
            gap: 1rem;
        }

        .vw-summary-stat {
            width: calc(50% - 0.5rem);
        }

        .vw-scene-dot {
            width: 30px;
            height: 30px;
        }

        .vw-dot-number {
            font-size: 0.6rem;
        }
    }
</style>
