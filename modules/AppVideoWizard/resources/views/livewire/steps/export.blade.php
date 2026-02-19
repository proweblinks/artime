{{-- Step 7: Export --}}
<style>
    .vw-export-step {
        width: 100%;
    }

    .vw-export-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(var(--vw-primary-rgb), 0.08) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-export-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-export-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-export-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-export-subtitle {
        font-size: 0.85rem !important;
        color: var(--vw-text-secondary) !important;
        margin-top: 0.15rem !important;
    }

    /* Summary Stats */
    .vw-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .vw-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-summary-stat {
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--vw-border);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
    }

    .vw-summary-stat-icon {
        font-size: 1.5rem;
        margin-bottom: 0.35rem;
    }

    .vw-summary-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--vw-text);
    }

    .vw-summary-stat-label {
        font-size: 0.7rem;
        color: var(--vw-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.25rem;
    }

    /* Export Settings */
    .vw-export-settings {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 640px) {
        .vw-export-settings {
            grid-template-columns: 1fr;
        }
    }

    .vw-setting-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-setting-label {
        font-size: 0.75rem;
        color: var(--vw-text-secondary);
    }

    .vw-quality-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .vw-quality-btn {
        padding: 0.75rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.02);
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.75rem;
        text-align: center;
        transition: all 0.2s;
    }

    .vw-quality-btn:hover {
        border-color: var(--vw-border-accent);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-quality-btn.selected {
        border-color: var(--vw-primary);
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text);
    }

    .vw-quality-btn.selected.recommended {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.2);
    }

    .vw-quality-name {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .vw-quality-desc {
        font-size: 0.65rem;
        color: var(--vw-text-secondary);
        margin-top: 0.15rem;
    }

    .vw-quality-btn.selected .vw-quality-desc {
        color: var(--vw-text);
    }

    .vw-recommended-badge {
        font-size: 0.55rem;
        padding: 0.1rem 0.3rem;
        background: rgba(16, 185, 129, 0.3);
        color: #10b981;
        border-radius: 0.25rem;
        margin-top: 0.25rem;
        display: inline-block;
    }

    .vw-format-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .vw-format-btn {
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid var(--vw-border);
        background: rgba(0,0,0,0.02);
        color: var(--vw-text);
        cursor: pointer;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.2s;
        font-weight: 600;
    }

    .vw-format-btn:hover {
        border-color: var(--vw-border-accent);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-format-btn.selected {
        border-color: var(--vw-primary);
        background: rgba(var(--vw-primary-rgb), 0.08);
        color: var(--vw-text);
    }

    /* Export Progress */
    .vw-export-progress {
        background: rgba(var(--vw-primary-rgb), 0.04);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .vw-progress-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-progress-spinner {
        width: 24px;
        height: 24px;
        border: 3px solid rgba(var(--vw-primary-rgb), 0.12);
        border-top-color: var(--vw-primary);
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-progress-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-progress-bar-container {
        height: 8px;
        background: var(--vw-border);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .vw-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--vw-primary), #06b6d4);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .vw-progress-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--vw-text-secondary);
    }

    /* Export Button */
    .vw-export-btn-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .vw-export-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1rem 3rem;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: var(--vw-text);
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
    }

    .vw-export-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.4);
    }

    .vw-export-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vw-export-btn-icon {
        font-size: 1.25rem;
    }

    /* Credits Info */
    .vw-credits-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .vw-credits-icon {
        font-size: 1rem;
    }

    .vw-credits-text {
        font-size: 0.8rem;
        color: var(--vw-text);
    }

    .vw-credits-value {
        font-weight: 600;
        color: #d97706;
    }

    /* Success State */
    .vw-export-success {
        text-align: center;
        padding: 2rem;
    }

    .vw-success-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .vw-success-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #10b981;
        margin-bottom: 0.5rem;
    }

    .vw-success-subtitle {
        font-size: 0.9rem;
        color: var(--vw-text-secondary);
        margin-bottom: 1.5rem;
    }

    .vw-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, var(--vw-primary), #06b6d4);
        border: none;
        color: var(--vw-text);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-download-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px var(--vw-border-accent);
    }
</style>

<div class="vw-export-step" x-data="{ exporting: false, progress: 0, status: 'idle', exportedUrl: null, quality: '1080p', format: 'mp4' }">
    <div class="vw-export-card">
        {{-- Header --}}
        <div class="vw-export-header">
            <div class="vw-export-icon">🎬</div>
            <div style="flex: 1;">
                <h2 class="vw-export-title">{{ __('Export Your Video') }}</h2>
                <p class="vw-export-subtitle">{{ __('Final step - render and download your creation') }}</p>
            </div>
        </div>

        {{-- Social Content: Direct Video Preview & Download --}}
        @if($isSocialContent ?? false)
            @php
                $socialShot = $multiShotMode['decomposedScenes'][0]['shots'][0] ?? [];
                $socialVideoUrl = $socialShot['videoUrl'] ?? null;
                $socialVideoStatus = $socialShot['videoStatus'] ?? 'pending';
            @endphp

            @if($socialVideoUrl && $socialVideoStatus === 'ready')
                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; align-items: flex-start;">
                    {{-- Video Preview --}}
                    <div style="width: 240px; min-width: 240px; aspect-ratio: 9/16; border-radius: 0.75rem; overflow: hidden; border: 2px solid rgba(var(--vw-primary-rgb), 0.12); background: #000;">
                        <video src="{{ $socialVideoUrl }}" controls loop playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                    </div>
                    {{-- Info & Download --}}
                    <div style="flex: 1;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.5rem;">
                            {{ $script['title'] ?? __('Your Video') }}
                        </div>
                        <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1rem; line-height: 1.5;">
                            {{ $concept['socialContent']['character'] ?? '' }} &mdash;
                            {{ $concept['socialContent']['situation'] ?? '' }}
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem;">
                            <div style="padding: 0.5rem 0.75rem; background: rgba(var(--vw-primary-rgb), 0.04); border: 1px solid rgba(var(--vw-primary-rgb), 0.12); border-radius: 0.5rem; font-size: 0.75rem; color: var(--vw-primary);">
                                📐 {{ $aspectRatio }}
                            </div>
                            <div style="padding: 0.5rem 0.75rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; font-size: 0.75rem; color: #16a34a;">
                                ✓ {{ __('Ready to download') }}
                            </div>
                        </div>
                        <a href="{{ $socialVideoUrl }}" download="{{ Str::slug($script['title'] ?? 'viral-video') }}.mp4"
                           style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--vw-primary) 0%, #6d28d9 100%); color: white; border-radius: 0.75rem; font-weight: 600; font-size: 1rem; text-decoration: none; transition: all 0.2s; cursor: pointer;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px var(--vw-border-accent)'"
                           onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            📥 {{ __('Download Video') }}
                        </a>
                    </div>
                </div>
            @else
                <div style="text-align: center; padding: 2rem; color: #94a3b8; margin-bottom: 1.5rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
                    <div>{{ __('No video available. Go back to the Create step and animate your content first.') }}</div>
                    <button type="button" wire:click="previousStep" style="margin-top: 1rem; padding: 0.5rem 1rem; background: rgba(var(--vw-primary-rgb), 0.08); border: 1px solid var(--vw-border-accent); border-radius: 0.5rem; color: var(--vw-primary); cursor: pointer;">
                        ← {{ __('Back to Create') }}
                    </button>
                </div>
            @endif
        @endif

        {{-- Summary Stats (hidden for social content) --}}
        @if(!($isSocialContent ?? false))
        @php
            $exportStats = $this->getAssemblyStats();
            $isMultiShotExport = $exportStats['mode'] === 'multi-shot';
        @endphp
        <div class="vw-summary-grid">
            <div class="vw-summary-stat">
                <div class="vw-summary-stat-icon">📺</div>
                <div class="vw-summary-stat-value">
                    {{ $platform ? config("appvideowizard.platforms.{$platform}.name", ucfirst($platform)) : '-' }}
                </div>
                <div class="vw-summary-stat-label">{{ __('Platform') }}</div>
            </div>
            <div class="vw-summary-stat">
                <div class="vw-summary-stat-icon">⏱️</div>
                <div class="vw-summary-stat-value">
                    @if($isMultiShotExport && $exportStats['totalDuration'] > 0)
                        {{ $exportStats['formattedDuration'] }}
                    @else
                        {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
                <div class="vw-summary-stat-label">{{ __('Duration') }}</div>
            </div>
            <div class="vw-summary-stat">
                <div class="vw-summary-stat-icon">🎬</div>
                <div class="vw-summary-stat-value">{{ count($script['scenes'] ?? []) }}</div>
                <div class="vw-summary-stat-label">{{ __('Scenes') }}</div>
            </div>
            @if($isMultiShotExport)
                <div class="vw-summary-stat" style="background: rgba(var(--vw-primary-rgb), 0.04); border-color: rgba(var(--vw-primary-rgb), 0.12);">
                    <div class="vw-summary-stat-icon">🎥</div>
                    <div class="vw-summary-stat-value" style="color: var(--vw-primary);">{{ $exportStats['videoCount'] }}</div>
                    <div class="vw-summary-stat-label">{{ __('Clips') }}</div>
                </div>
            @else
                <div class="vw-summary-stat">
                    <div class="vw-summary-stat-icon">📐</div>
                    <div class="vw-summary-stat-value">{{ $aspectRatio }}</div>
                    <div class="vw-summary-stat-label">{{ __('Aspect Ratio') }}</div>
                </div>
            @endif
        </div>
        @endif

        {{-- Hollywood Multi-Shot Mode Badge (not for social content) --}}
        @if(!($isSocialContent ?? false) && ($isMultiShotExport ?? false))
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.04), rgba(6, 182, 212, 0.1)); border: 1px solid rgba(var(--vw-primary-rgb), 0.12); border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <span style="font-size: 1.25rem;">🎬</span>
                <div style="flex: 1;">
                    <div style="font-size: 0.85rem; color: white; font-weight: 600;">{{ __('Hollywood Multi-Shot Mode') }}</div>
                    <div style="font-size: 0.7rem; color: var(--vw-text-secondary);">
                        {{ $exportStats['sceneCount'] }} {{ __('scenes') }} •
                        {{ $exportStats['videoCount'] }} {{ __('shot clips') }} •
                        {{ $aspectRatio }}
                    </div>
                </div>
                @if($exportStats['isReady'])
                    <span style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; background: rgba(16, 185, 129, 0.3); color: #10b981;">
                        {{ __('Ready') }}
                    </span>
                @else
                    <span style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; background: rgba(245, 158, 11, 0.3); color: #f59e0b;">
                        {{ $exportStats['progress'] }}% {{ __('Complete') }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Export Settings (hidden for social content — video is already ready) --}}
        @if(!($isSocialContent ?? false))
        <div class="vw-export-settings" x-show="!exporting && !exportedUrl">
            {{-- Quality Selection --}}
            <div class="vw-setting-group">
                <div class="vw-setting-label">🎯 {{ __('Quality') }}</div>
                <div class="vw-quality-grid">
                    <button type="button"
                            class="vw-quality-btn"
                            :class="{ 'selected': quality === '720p' }"
                            @click="quality = '720p'">
                        <div class="vw-quality-name">720p</div>
                        <div class="vw-quality-desc">HD</div>
                    </button>
                    <button type="button"
                            class="vw-quality-btn recommended"
                            :class="{ 'selected': quality === '1080p' }"
                            @click="quality = '1080p'">
                        <div class="vw-quality-name">1080p</div>
                        <div class="vw-quality-desc">Full HD</div>
                        <span class="vw-recommended-badge">{{ __('Recommended') }}</span>
                    </button>
                    <button type="button"
                            class="vw-quality-btn"
                            :class="{ 'selected': quality === '4k' }"
                            @click="quality = '4k'">
                        <div class="vw-quality-name">4K</div>
                        <div class="vw-quality-desc">Ultra HD</div>
                    </button>
                </div>
            </div>

            {{-- Format Selection --}}
            <div class="vw-setting-group">
                <div class="vw-setting-label">📁 {{ __('Format') }}</div>
                <div class="vw-format-grid">
                    <button type="button"
                            class="vw-format-btn"
                            :class="{ 'selected': format === 'mp4' }"
                            @click="format = 'mp4'">
                        📹 MP4
                    </button>
                    <button type="button"
                            class="vw-format-btn"
                            :class="{ 'selected': format === 'webm' }"
                            @click="format = 'webm'">
                        🌐 WebM
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(!($isSocialContent ?? false))
        {{-- Export Progress --}}
        <div class="vw-export-progress" x-show="exporting" x-cloak>
            <div class="vw-progress-header">
                <div class="vw-progress-spinner"></div>
                <span class="vw-progress-title">{{ __('Exporting your video...') }}</span>
            </div>
            <div class="vw-progress-bar-container">
                <div class="vw-progress-bar" :style="'width: ' + progress + '%'"></div>
            </div>
            <div class="vw-progress-info">
                <span x-text="status">{{ __('Preparing...') }}</span>
                <span x-text="progress + '%'">0%</span>
            </div>
        </div>

        {{-- Success State --}}
        <div class="vw-export-success" x-show="exportedUrl" x-cloak>
            <div class="vw-success-icon">🎉</div>
            <div class="vw-success-title">{{ __('Export Complete!') }}</div>
            <div class="vw-success-subtitle">{{ __('Your video is ready for download') }}</div>
            <button type="button" class="vw-download-btn" @click="window.downloadVideo && window.downloadVideo()">
                📥 {{ __('Download Video') }}
            </button>
        </div>

        {{-- Export Button --}}
        <div class="vw-export-btn-container" x-show="!exporting && !exportedUrl">
            <button type="button"
                    class="vw-export-btn"
                    @click="exporting = true; progress = 0; status = 'Preparing assets...'; window.startExport && window.startExport()"
                    wire:click="$dispatch('start-export')">
                <span class="vw-export-btn-icon">🚀</span>
                <span>{{ __('Export Video') }}</span>
            </button>
        </div>

        {{-- Credits Info --}}
        <div class="vw-credits-info">
            <span class="vw-credits-icon">💳</span>
            <span class="vw-credits-text">
                {{ __('This export will use') }}
                <span class="vw-credits-value">{{ config('appvideowizard.credit_costs.video_export', 15) }}</span>
                {{ __('credits') }}
            </span>
        </div>
        @endif
    </div>
</div>

<script>
window.startExport = function() {
    const el = document.querySelector('.vw-export-step');
    if (!el) return;
    const data = Alpine.$data(el);
    data.exporting = true;
    data.progress = 0;
    data.status = 'Preparing assets...';

    // Simulate export progress (in production, this would be real API calls)
    let progress = 0;
    const statuses = [
        'Preparing assets...',
        'Rendering scenes...',
        'Adding voiceovers...',
        'Applying transitions...',
        'Encoding video...',
        'Finalizing...'
    ];

    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
            setTimeout(() => {
                Alpine.$data(document.querySelector('.vw-export-step')).exporting = false;
                Alpine.$data(document.querySelector('.vw-export-step')).exportedUrl = '#';
            }, 500);
        }

        Alpine.$data(document.querySelector('.vw-export-step')).progress = Math.floor(progress);
        Alpine.$data(document.querySelector('.vw-export-step')).status = statuses[Math.min(Math.floor(progress / 20), statuses.length - 1)];
    }, 500);
}

window.downloadVideo = function() {
    // In production, this would download the actual video file
    alert('Video download starting... (This is a demo)');
}
</script>
