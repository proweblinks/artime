<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0, step: 0, tipIndex: 0,
    tips: [
        'A/B testing thumbnails can increase CTR by up to 50%',
        'Eye contact in thumbnails builds instant connection',
        'Bright, contrasting colors stand out in recommendations',
        'Clean composition with 3 or fewer elements performs best'
    ],
    steps: [
        { label: 'Loading Images', icon: 'fa-images' },
        { label: 'Analyzing Design', icon: 'fa-eye' },
        { label: 'Comparing Elements', icon: 'fa-scale-balanced' },
        { label: 'Scoring Results', icon: 'fa-trophy' }
    ],
    interval: null, tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / 25), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = this.steps.length;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    }
}"
x-init="Livewire.hook('message.processed', (msg, comp) => { if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading(); });">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-panel').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">⚔️</span> {{ __('Thumbnail Arena') }}</h2>

        <div class="aith-feature-box aith-feat-red">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Compare two thumbnails head-to-head') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('AI scores design, color, composition') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Get a clear winner recommendation') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Actionable improvement tips') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-grid-2">
            {{-- Thumbnail A --}}
            <div class="aith-form-group">
                <label class="aith-label">{{ __('Thumbnail A') }}</label>
                <div class="aith-upload-zone {{ $thumbnail1 ? 'aith-has-file' : '' }}" onclick="this.querySelector('input').click()">
                    <input type="file" wire:model="thumbnail1" accept="image/*" style="display:none;">
                    @if($thumbnail1)
                    <img src="{{ $thumbnail1->temporaryUrl() }}" alt="Thumbnail A" class="aith-upload-preview">
                    @else
                    <div>
                        <i class="fa-light fa-cloud-arrow-up" style="font-size:2rem; color:#94a3b8; display:block; margin-bottom:0.5rem;"></i>
                        <div style="font-size:0.8125rem; color:#64748b;">{{ __('Click to upload') }}</div>
                        <div style="font-size:0.6875rem; color:#94a3b8;">{{ __('PNG, JPG up to 5MB') }}</div>
                    </div>
                    @endif
                </div>
                @error('thumbnail1') <div class="aith-field-error">{{ $message }}</div> @enderror
            </div>

            {{-- Thumbnail B --}}
            <div class="aith-form-group">
                <label class="aith-label">{{ __('Thumbnail B') }}</label>
                <div class="aith-upload-zone {{ $thumbnail2 ? 'aith-has-file' : '' }}" onclick="this.querySelector('input').click()">
                    <input type="file" wire:model="thumbnail2" accept="image/*" style="display:none;">
                    @if($thumbnail2)
                    <img src="{{ $thumbnail2->temporaryUrl() }}" alt="Thumbnail B" class="aith-upload-preview">
                    @else
                    <div>
                        <i class="fa-light fa-cloud-arrow-up" style="font-size:2rem; color:#94a3b8; display:block; margin-bottom:0.5rem;"></i>
                        <div style="font-size:0.8125rem; color:#64748b;">{{ __('Click to upload') }}</div>
                        <div style="font-size:0.6875rem; color:#94a3b8;">{{ __('PNG, JPG up to 5MB') }}</div>
                    </div>
                    @endif
                </div>
                @error('thumbnail2') <div class="aith-field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <button wire:click="compare" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="compare">
                <i class="fa-light fa-swords"></i> {{ __('Compare Thumbnails') }}
            </span>
            <span wire:loading wire:target="compare">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Analyzing...') }}
            </span>
        </button>

        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">⚔️</span> {{ __('Comparing thumbnails...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar"><div class="aith-progress-fill" :style="'width:' + progress + '%'"></div></div>
            <div class="aith-steps-grid" style="grid-template-columns: repeat(2, 1fr);">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon"><i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i></span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    {{-- Results --}}
    @if($result)

    {{-- Winner --}}
    @if(isset($result['winner']))
    <div class="aith-winner-banner">
        <div class="aith-trophy">🏆</div>
        <h4>{{ __('Winner: Thumbnail') }} {{ $result['winner'] }}</h4>
        @if(isset($result['winner_reason']))
        <p>{{ $result['winner_reason'] }}</p>
        @endif
    </div>
    @endif

    {{-- Side-by-Side Analysis --}}
    @if(isset($result['analysis']))
    <div class="aith-grid-2">
        @foreach(['A', 'B'] as $label)
        @php $analysis = $result['analysis'][strtolower($label)] ?? $result['analysis'][$label] ?? null; @endphp
        @if($analysis)
        <div class="aith-card" style="margin-bottom:0;">
            <h3 class="aith-section-title">{{ __('Thumbnail') }} {{ $label }}
                @if(isset($result['winner']) && $result['winner'] === $label)
                <span class="aith-badge aith-badge-success">{{ __('Winner') }}</span>
                @endif
            </h3>
            @if(isset($analysis['scores']))
            @foreach($analysis['scores'] as $category => $score)
            <div class="aith-bar-wrap">
                <div class="aith-bar-label">
                    <span>{{ ucfirst(str_replace('_', ' ', $category)) }}</span>
                    <span>{{ $score }}/100</span>
                </div>
                <div class="aith-bar-track">
                    <div class="aith-bar-value {{ $score >= 70 ? 'aith-bar-green' : ($score >= 40 ? 'aith-bar-yellow' : 'aith-bar-red') }}"
                        style="width: {{ $score }}%"></div>
                </div>
            </div>
            @endforeach
            @endif
            @if(isset($analysis['feedback']))
            <p style="font-size:0.8125rem; color:#64748b; margin-top:0.75rem;">{{ $analysis['feedback'] }}</p>
            @endif
        </div>
        @endif
        @endforeach
    </div>
    @endif

    {{-- Improvement Tips --}}
    @if(isset($result['improvements']))
    <div class="aith-card">
        <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Improvement Tips') }}</h3>
        @foreach($result['improvements'] as $tip)
        <div class="aith-result-item">
            <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                <i class="fa-light fa-check-circle" style="color:#7c3aed; margin-top:2px; flex-shrink:0;"></i>
                <span class="aith-result-text">{{ $tip }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div style="text-align:center; margin-top:1rem;">
        <button class="aith-btn-secondary" wire:click="$set('result', null)">
            <i class="fa-light fa-arrow-rotate-left"></i> {{ __('Compare New Thumbnails') }}
        </button>
    </div>
    @endif

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
