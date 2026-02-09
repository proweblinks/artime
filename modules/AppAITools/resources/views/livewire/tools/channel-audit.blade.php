<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Channels that audit monthly grow 3x faster',
        'Consistent upload schedule boosts algorithm favor by 40%',
        'Engagement rate matters more than subscriber count',
        'SEO-optimized channels get 70% more organic traffic',
        'First 48 hours determine a video\'s lifetime performance'
    ],
    steps: [
        { label: 'Fetching Channel', icon: 'fa-download' },
        { label: 'Analyzing SEO', icon: 'fa-magnifying-glass' },
        { label: 'Checking Content', icon: 'fa-film' },
        { label: 'Measuring Engagement', icon: 'fa-heart' },
        { label: 'Evaluating Growth', icon: 'fa-chart-line' },
        { label: 'Building Roadmap', icon: 'fa-map' }
    ],
    interval: null, tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / (100 / 6)), 5);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = 6;
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
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-ca2').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    {{-- Form Card --}}
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">📋</span> {{ __('Channel Audit Pro') }}</h2>

        <div class="aith-feature-box aith-feat-emerald">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Comprehensive SEO health check') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Content strategy evaluation') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Engagement analysis & benchmarks') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Personalized growth roadmap') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Channel URL') }}</label>
            <input type="url" wire:model="channelUrl" class="aith-input" placeholder="https://youtube.com/@channel">
            @error('channelUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
            <div style="font-size: 0.6875rem; color: #94a3b8; margin-top: 0.375rem;">{{ __('Supports youtube.com/@handle, /channel/ID, or /c/name formats') }}</div>
        </div>

        <button wire:click="audit" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="audit">
                <i class="fa-light fa-clipboard-check"></i> {{ __('Run Audit') }}
            </span>
            <span wire:loading wire:target="audit">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Auditing...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">📋</span> {{ __('Running audit...') }}</div>
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

    {{-- Overall Score --}}
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">📊</span> {{ __('Audit Results') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>

        @if(isset($result['overall_score']))
        <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.25rem; flex-wrap: wrap;">
            <div class="aith-score-gauge">
                <svg viewBox="0 0 120 120">
                    <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                    <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                        stroke="{{ $result['overall_score'] >= 70 ? '#10b981' : ($result['overall_score'] >= 40 ? '#f59e0b' : '#ef4444') }}"
                        stroke-dasharray="314"
                        stroke-dashoffset="{{ 314 - (314 * $result['overall_score'] / 100) }}"/>
                </svg>
                <div class="aith-score-val" style="color: {{ $result['overall_score'] >= 70 ? '#10b981' : ($result['overall_score'] >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $result['overall_score'] }}</div>
            </div>
            <div style="flex:1;">
                <div style="font-size:1.125rem; font-weight:700; color:#1e293b;">{{ __('Overall Score') }}</div>
                @if(isset($result['overall_summary']))
                <p style="font-size:0.8125rem; color:#64748b; margin:0.375rem 0 0;">{{ $result['overall_summary'] }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Category Scores --}}
        @if(isset($result['categories']))
        <div class="aith-grid-4" style="margin-bottom:1.25rem;">
            @foreach($result['categories'] as $cat)
            @php $score = $cat['score'] ?? 0; @endphp
            <div class="aith-score-mini">
                <div class="aith-score-num" style="color: {{ $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $score }}</div>
                <div class="aith-score-name">{{ $cat['name'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Metrics --}}
        @if(isset($result['metrics']))
        <h3 class="aith-section-title"><i class="fa-light fa-chart-mixed"></i> {{ __('Key Metrics') }}</h3>
        <div class="aith-grid-4" style="margin-bottom:1.25rem;">
            @foreach($result['metrics'] as $metric)
            <div class="aith-score-mini">
                <div class="aith-score-num" style="color:#7c3aed; font-size:1.375rem;">{{ $metric['value'] ?? '-' }}</div>
                <div class="aith-score-name">{{ $metric['label'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Strengths & Weaknesses --}}
    @if(isset($result['strengths']) || isset($result['weaknesses']))
    <div class="aith-grid-2">
        @if(isset($result['strengths']))
        <div class="aith-card" style="margin-bottom:0;">
            <div class="aith-sw-box aith-sw-strength" style="margin-bottom:0;">
                <h5><i class="fa-light fa-shield-check"></i> {{ __('Strengths') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['strengths'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        @if(isset($result['weaknesses']))
        <div class="aith-card" style="margin-bottom:0;">
            <div class="aith-sw-box aith-sw-weakness" style="margin-bottom:0;">
                <h5><i class="fa-light fa-triangle-exclamation"></i> {{ __('Weaknesses') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($result['weaknesses'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Recommendations --}}
    @if(isset($result['recommendations']))
    <div class="aith-card">
        <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Recommendations') }}</h3>
        @foreach($result['recommendations'] as $rec)
        <div class="aith-result-item">
            <div class="aith-result-row">
                @php $priority = is_array($rec) ? ($rec['priority'] ?? 'low') : 'medium'; @endphp
                <span class="aith-badge {{ $priority === 'high' ? 'aith-badge-high' : ($priority === 'medium' ? 'aith-badge-medium' : 'aith-badge-low') }}">{{ strtoupper($priority) }}</span>
                <div style="flex:1">
                    @if(is_array($rec) && isset($rec['title']))
                    <div style="font-weight:600; font-size:0.8125rem; color:#1e293b; margin-bottom:0.25rem;">{{ $rec['title'] }}</div>
                    @endif
                    <div class="aith-result-text">{{ is_array($rec) ? ($rec['description'] ?? $rec['text'] ?? '') : $rec }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @endif

    {{-- History Panel --}}
    @if(count($history) > 0)
    <div id="aith-history-ca2" class="aith-card" style="display:none; margin-top: 1rem;">
        <h3 class="aith-section-title"><i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent Audits') }}</h3>
        @foreach($history as $item)
        <div class="aith-result-item" style="cursor:default;">
            <div class="aith-result-row">
                <div style="flex:1">
                    <div class="aith-result-text">{{ $item['title'] ?? 'Untitled' }}</div>
                    <div style="font-size:0.6875rem; color:#94a3b8; margin-top:0.25rem;">{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans() }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <style>#aith-history-ca2.aith-open { display: block !important; }</style>
    @endif

</div>
</div>
