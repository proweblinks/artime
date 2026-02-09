<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Thumbnails account for 90% of a video\'s first impression',
        'Faces with emotions increase CTR by 35%',
        'High contrast colors stand out in search results',
        'Text on thumbnails should be readable at small sizes'
    ],
    steps: [
        { label: 'Preparing', icon: 'fa-palette' },
        { label: 'Optimizing Prompt', icon: 'fa-wand-magic-sparkles' },
        { label: 'Generating Images', icon: 'fa-image' },
        { label: 'Finalizing', icon: 'fa-check-double' }
    ],
    interval: null, tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 1.5;
            else if (this.progress < 60) this.progress += 0.8;
            else if (this.progress < 85) this.progress += 0.4;
            else if (this.progress < 95) this.progress += 0.15;
            this.step = Math.min(Math.floor(this.progress / 25), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = this.steps.length;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    },
    selectedRatio: @entangle('aspectRatio')
}"
x-init="
    Livewire.hook('message.processed', (msg, comp) => {
        if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading();
    });
">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.index') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-th').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    {{-- Form Card --}}
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🎨</span> {{ __('AI Thumbnails') }}</h2>

        <div class="aith-feature-box aith-feat-pink">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Generate eye-catching thumbnail designs') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Multiple styles to choose from') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Custom aspect ratios for any platform') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Add your own creative direction') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Video Title') }}</label>
            <input type="text" wire:model="title" class="aith-input" placeholder="{{ __('Enter your video title') }}">
            @error('title') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Style') }}</label>
            <select wire:model="style" class="aith-select">
                @foreach($styles as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Aspect Ratio') }}</label>
            <div class="aith-radio-group">
                @foreach(['16:9' => 'Landscape', '9:16' => 'Portrait', '1:1' => 'Square'] as $ratio => $label)
                <div class="aith-radio-option {{ $aspectRatio === $ratio ? 'aith-selected' : '' }}"
                    @click="selectedRatio = '{{ $ratio }}'">
                    <div style="font-weight:600;">{{ $label }}</div>
                    <div style="font-size:0.6875rem; color:#94a3b8; margin-top:2px;">{{ $ratio }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Custom Prompt') }} <span class="aith-label-hint">({{ __('Optional') }})</span></label>
            <textarea wire:model="customPrompt" class="aith-textarea" rows="3" placeholder="{{ __('Additional details for the thumbnail...') }}"></textarea>
        </div>

        <button wire:click="generate" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="generate">
                <i class="fa-light fa-wand-magic-sparkles"></i> {{ __('Generate Thumbnail') }}
            </span>
            <span wire:loading wire:target="generate">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Generating...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🎨</span> {{ __('Creating thumbnail...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar">
                <div class="aith-progress-fill" :style="'width:' + progress + '%'"></div>
            </div>
            <div class="aith-steps-grid" style="grid-template-columns: repeat(2, 1fr);">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon">
                            <i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i>
                        </span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    {{-- Results --}}
    @if($result && isset($result['images']))
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">✨</span> {{ __('Generated Thumbnails') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('Create New') }}
            </button>
        </div>

        @foreach($result['images'] as $i => $image)
        <div style="margin-bottom: 1rem; {{ $i > 0 ? 'padding-top: 1rem; border-top: 1px solid #e2e8f0;' : '' }}">
            <img src="{{ $image['url'] ?? asset($image['path'] ?? '') }}" alt="Generated thumbnail"
                style="width: 100%; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; justify-content: flex-end;">
                <a href="{{ $image['url'] ?? asset($image['path'] ?? '') }}" download class="aith-btn-secondary">
                    <i class="fa-light fa-download"></i> {{ __('Download') }}
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- History Panel --}}
    @if(count($history) > 0)
    <div id="aith-history-th" class="aith-card" style="display:none; margin-top: 1rem;">
        <h3 class="aith-section-title"><i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent Thumbnails') }}</h3>
        @foreach($history as $item)
        <div class="aith-result-item" style="cursor:default;">
            <div class="aith-result-text">{{ $item['title'] ?? 'Untitled' }}</div>
            <div style="font-size:0.6875rem; color:#94a3b8; margin-top:0.25rem;">{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans() }}</div>
        </div>
        @endforeach
    </div>
    <style>#aith-history-th.aith-open { display: block !important; }</style>
    @endif

</div>
</div>
