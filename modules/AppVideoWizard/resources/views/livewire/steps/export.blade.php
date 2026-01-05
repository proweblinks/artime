{{-- Step 7: Export --}}
<div x-data="{ exporting: false, progress: 0, status: 'idle' }">
    <h2 class="text-xl font-bold mb-2">{{ __('Export Your Video') }}</h2>
    <p class="text-base-content/60 mb-6">{{ __('Render and download your final video') }}</p>

    {{-- Export Summary --}}
    <div class="card bg-base-100 mb-6">
        <div class="card-body">
            <h3 class="card-title text-lg">
                <i class="fa-light fa-video mr-2"></i>
                {{ __('Video Summary') }}
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div class="stat bg-base-200 rounded-lg p-4">
                    <div class="stat-title text-xs">{{ __('Platform') }}</div>
                    <div class="stat-value text-lg">
                        {{ $platform ? config("appvideowizard.platforms.{$platform}.name") : '-' }}
                    </div>
                </div>

                <div class="stat bg-base-200 rounded-lg p-4">
                    <div class="stat-title text-xs">{{ __('Duration') }}</div>
                    <div class="stat-value text-lg">{{ $targetDuration }}s</div>
                </div>

                <div class="stat bg-base-200 rounded-lg p-4">
                    <div class="stat-title text-xs">{{ __('Scenes') }}</div>
                    <div class="stat-value text-lg">{{ count($script['scenes'] ?? []) }}</div>
                </div>

                <div class="stat bg-base-200 rounded-lg p-4">
                    <div class="stat-title text-xs">{{ __('Format') }}</div>
                    <div class="stat-value text-lg">{{ $aspectRatio }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Settings --}}
    <div class="card bg-base-100 mb-6">
        <div class="card-body">
            <h3 class="card-title text-lg">
                <i class="fa-light fa-cog mr-2"></i>
                {{ __('Export Settings') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">{{ __('Quality') }}</span>
                    </label>
                    <select class="select select-bordered" x-model="quality">
                        <option value="720p">720p (HD)</option>
                        <option value="1080p" selected>1080p (Full HD)</option>
                        <option value="4k">4K (Ultra HD)</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">{{ __('Format') }}</span>
                    </label>
                    <select class="select select-bordered" x-model="format">
                        <option value="mp4" selected>MP4</option>
                        <option value="webm">WebM</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Progress --}}
    <div x-show="exporting" x-cloak class="card bg-base-100 mb-6">
        <div class="card-body">
            <h3 class="card-title text-lg">
                <span class="loading loading-spinner loading-sm mr-2"></span>
                {{ __('Exporting...') }}
            </h3>

            <div class="mt-4">
                <progress class="progress progress-primary w-full" :value="progress" max="100"></progress>
                <div class="flex justify-between text-sm text-base-content/60 mt-2">
                    <span x-text="status"></span>
                    <span x-text="progress + '%'"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Button --}}
    <div class="flex justify-center">
        <button class="btn btn-primary btn-lg"
                x-show="!exporting"
                @click="startExport()"
                wire:click="$dispatch('start-export')">
            <i class="fa-light fa-download mr-2"></i>
            {{ __('Export Video') }}
        </button>
    </div>

    {{-- Credits Info --}}
    <div class="alert mt-6">
        <i class="fa-light fa-info-circle"></i>
        <span>{{ __('Exporting this video will use :credits credits.', ['credits' => config('appvideowizard.credit_costs.video_export', 15)]) }}</span>
    </div>
</div>

<script>
function startExport() {
    // This would be handled by Livewire/Alpine integration
    this.exporting = true;
    this.progress = 0;
    this.status = 'Preparing...';
}
</script>
