<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Top creators study competitors for 2+ hours per week',
        'Competitor titles reveal what keywords are working right now',
        'Weak spots in competitor videos are your biggest opportunities',
        'Analyzing top 10 competitors can reveal niche gaps',
        'Better thumbnails alone can double your CTR'
    ],
    steps: [
        { label: 'Fetching Video', icon: 'fa-download' },
        { label: 'Analyzing Channel', icon: 'fa-user-magnifying-glass' },
        { label: 'Evaluating SEO', icon: 'fa-magnifying-glass-chart' },
        { label: 'Finding Weaknesses', icon: 'fa-crosshairs' },
        { label: 'Generating Strategy', icon: 'fa-chess' }
    ],
    interval: null,
    tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / 20), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = this.steps.length;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    }
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
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-panel').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    {{-- Form Card --}}
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🔍</span> {{ __('Competitor Analysis') }}</h2>

        <div class="aith-feature-box aith-feat-red">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Full SEO breakdown of competitor video') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Identify strengths to learn from') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Spot weaknesses you can exploit') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Get better title suggestions') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Competitor Video URL') }}</label>
            <input type="url" wire:model="competitorUrl" class="aith-input" placeholder="https://youtube.com/watch?v=...">
            @error('competitorUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Your Video URL') }} <span class="aith-label-hint">({{ __('Optional') }})</span></label>
            <input type="url" wire:model="myUrl" class="aith-input" placeholder="https://youtube.com/watch?v=...">
        </div>

        <button wire:click="analyze" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="analyze">
                <i class="fa-light fa-magnifying-glass-chart"></i> {{ __('Analyze Competitor') }}
            </span>
            <span wire:loading wire:target="analyze">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Analyzing...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🔍</span> {{ __('Analyzing competitor...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar">
                <div class="aith-progress-fill" :style="'width:' + progress + '%'"></div>
            </div>
            <div class="aith-steps-grid">
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
    @if($result)
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">📊</span> {{ __('Analysis Results') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>

        {{-- Scores --}}
        @if(isset($result['score']))
        <div class="aith-grid-3" style="margin-bottom: 1.25rem;">
            <div class="aith-score-mini">
                <div class="aith-score-num" style="color: {{ ($result['score'] ?? 0) >= 70 ? '#10b981' : (($result['score'] ?? 0) >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $result['score'] }}</div>
                <div class="aith-score-name">{{ __('SEO Score') }}</div>
            </div>
            @if(isset($result['difficulty']))
            <div class="aith-score-mini">
                <div class="aith-score-num" style="color: #f59e0b;">{{ $result['difficulty'] }}</div>
                <div class="aith-score-name">{{ __('Difficulty') }}</div>
            </div>
            @endif
            @if(isset($result['beatable']))
            <div class="aith-score-mini">
                <div class="aith-score-num" style="color: #7c3aed;">{{ $result['beatable'] ? '✅' : '⚠️' }}</div>
                <div class="aith-score-name">{{ __('Beatable?') }}</div>
            </div>
            @endif
        </div>
        @endif

        {{-- SWOT --}}
        @if(isset($result['swot']))
        <div class="aith-grid-2" style="margin-bottom: 1rem;">
            @if(isset($result['swot']['strengths']))
            <div class="aith-sw-box aith-sw-strength">
                <h5><i class="fa-light fa-shield-check"></i> {{ __('Strengths') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['swot']['strengths'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(isset($result['swot']['weaknesses']))
            <div class="aith-sw-box aith-sw-weakness">
                <h5><i class="fa-light fa-triangle-exclamation"></i> {{ __('Weaknesses') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['swot']['weaknesses'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(isset($result['swot']['opportunities']))
            <div class="aith-sw-box aith-sw-opportunity">
                <h5><i class="fa-light fa-lightbulb"></i> {{ __('Opportunities') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['swot']['opportunities'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(isset($result['swot']['threats']))
            <div class="aith-sw-box aith-sw-threat">
                <h5><i class="fa-light fa-bolt"></i> {{ __('Threats') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['swot']['threats'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        {{-- Recommendations --}}
        @if(isset($result['recommendations']))
        <h3 class="aith-section-title" style="margin-top: 1rem;"><i class="fa-light fa-lightbulb"></i> {{ __('Recommendations') }}</h3>
        @foreach($result['recommendations'] as $rec)
        <div class="aith-result-item">
            <div class="aith-result-row">
                @php $priority = is_array($rec) ? ($rec['priority'] ?? 'low') : 'medium'; @endphp
                <span class="aith-badge {{ $priority === 'high' ? 'aith-badge-high' : ($priority === 'medium' ? 'aith-badge-medium' : 'aith-badge-low') }}">{{ $priority }}</span>
                <div style="flex:1">
                    <div class="aith-result-text">{{ is_array($rec) ? ($rec['text'] ?? '') : $rec }}</div>
                </div>
            </div>
        </div>
        @endforeach
        @endif

        {{-- Strategy --}}
        @if(isset($result['summary']))
        <div class="aith-strategy-banner">
            <h4><i class="fa-light fa-chess"></i> {{ __('Strategy Summary') }}</h4>
            <p>{{ $result['summary'] }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
