{{-- Step 7: Export --}}
<style>
    .vw-export-step {
        width: 100%;
    }

    .vw-export-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
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
        color: rgba(255, 255, 255, 0.5) !important;
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
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
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
        color: white;
    }

    .vw-summary-stat-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
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
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-quality-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .vw-quality-btn {
        padding: 0.75rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.03);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.75rem;
        text-align: center;
        transition: all 0.2s;
    }

    .vw-quality-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-quality-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        color: white;
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
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.15rem;
    }

    .vw-quality-btn.selected .vw-quality-desc {
        color: rgba(255, 255, 255, 0.7);
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
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.03);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.2s;
        font-weight: 600;
    }

    .vw-format-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-format-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        color: white;
    }

    /* Export Progress */
    .vw-export-progress {
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.3);
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
        border: 3px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-progress-title {
        font-size: 1rem;
        font-weight: 600;
        color: white;
    }

    .vw-progress-bar-container {
        height: 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .vw-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #06b6d4);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .vw-progress-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.6);
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
        color: white;
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
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-credits-value {
        font-weight: 600;
        color: #fbbf24;
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
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 1.5rem;
    }

    .vw-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-download-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }
</style>

<div class="vw-export-step" x-data="{ exporting: false, progress: 0, status: 'idle', exportedUrl: null }">
    <div class="vw-export-card">
        {{-- Header --}}
        <div class="vw-export-header">
            <div class="vw-export-icon">🎬</div>
            <div style="flex: 1;">
                <h2 class="vw-export-title">{{ __('Export Your Video') }}</h2>
                <p class="vw-export-subtitle">{{ __('Final step - render and download your creation') }}</p>
            </div>
        </div>

        {{-- Summary Stats --}}
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
                <div class="vw-summary-stat-value">{{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="vw-summary-stat-label">{{ __('Duration') }}</div>
            </div>
            <div class="vw-summary-stat">
                <div class="vw-summary-stat-icon">🎬</div>
                <div class="vw-summary-stat-value">{{ count($script['scenes'] ?? []) }}</div>
                <div class="vw-summary-stat-label">{{ __('Scenes') }}</div>
            </div>
            <div class="vw-summary-stat">
                <div class="vw-summary-stat-icon">📐</div>
                <div class="vw-summary-stat-value">{{ $aspectRatio }}</div>
                <div class="vw-summary-stat-label">{{ __('Aspect Ratio') }}</div>
            </div>
        </div>

        {{-- Export Settings --}}
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
            <button type="button" class="vw-download-btn" @click="downloadVideo()">
                📥 {{ __('Download Video') }}
            </button>
        </div>

        {{-- Export Button --}}
        <div class="vw-export-btn-container" x-show="!exporting && !exportedUrl">
            <button type="button"
                    class="vw-export-btn"
                    @click="startExport()"
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
    </div>
</div>

<script>
function startExport() {
    Alpine.$data(document.querySelector('.vw-export-step')).exporting = true;
    Alpine.$data(document.querySelector('.vw-export-step')).progress = 0;
    Alpine.$data(document.querySelector('.vw-export-step')).status = 'Preparing assets...';

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

function downloadVideo() {
    // In production, this would download the actual video file
    alert('Video download starting... (This is a demo)');
}
</script>
