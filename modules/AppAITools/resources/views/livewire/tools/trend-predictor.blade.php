<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Creators who ride trends early get 10x more views',
        'Trending topics have a 72-hour golden window',
        'Combining 2 trends creates unique viral content',
        'Regional trends often go global within weeks',
        'Hashtags from trending topics boost discoverability'
    ],
    steps: [
        { label: 'Scanning Niche', icon: 'fa-radar' },
        { label: 'Finding Trends', icon: 'fa-arrow-trend-up' },
        { label: 'Predicting Growth', icon: 'fa-crystal-ball' },
        { label: 'Generating Ideas', icon: 'fa-lightbulb' },
        { label: 'Building Hashtags', icon: 'fa-hashtag' }
    ],
    interval: null, tipInterval: null,
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
        <h2 class="aith-card-title"><span class="aith-emoji">🔮</span> {{ __('Trend Predictor') }}</h2>

        <div class="aith-feature-box aith-feat-cyan">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Discover current hot trends in your niche') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Predict upcoming trend growth') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Get ready-to-use video ideas') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Find trending hashtags') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Niche / Topic') }}</label>
            <input type="text" wire:model="niche" class="aith-input" placeholder="{{ __('e.g. AI tutorials, cooking, fitness') }}">
            @error('niche') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Region') }}</label>
            <select wire:model="region" class="aith-select">
                <option value="US">United States</option>
                <option value="GB">United Kingdom</option>
                <option value="CA">Canada</option>
                <option value="AU">Australia</option>
                <option value="IN">India</option>
                <option value="DE">Germany</option>
                <option value="FR">France</option>
                <option value="BR">Brazil</option>
                <option value="JP">Japan</option>
                <option value="KR">South Korea</option>
            </select>
        </div>

        <button wire:click="predict" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="predict">
                <i class="fa-light fa-crystal-ball"></i> {{ __('Predict Trends') }}
            </span>
            <span wire:loading wire:target="predict">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Predicting...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🔮</span> {{ __('Predicting trends...') }}</div>
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

    {{-- Current Trends --}}
    @if(isset($result['current_trends']) && count($result['current_trends']) > 0)
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">🔥</span> {{ __('Current Trends') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>
        <div class="aith-grid-3">
            @foreach($result['current_trends'] as $trend)
            @php $status = is_array($trend) ? ($trend['status'] ?? 'stable') : 'stable'; @endphp
            <div class="aith-result-item" style="margin-bottom:0">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem;">
                    <i class="fa-light {{ $status === 'rising' ? 'fa-arrow-trend-up aith-trend-up' : ($status === 'declining' ? 'fa-arrow-trend-down aith-trend-down' : 'fa-minus aith-trend-stable') }}"></i>
                    <strong style="font-size:0.8125rem; color:#1e293b;">{{ is_array($trend) ? ($trend['topic'] ?? '') : $trend }}</strong>
                </div>
                @if(is_array($trend) && isset($trend['description']))
                <p style="font-size:0.75rem; color:#64748b; margin:0;">{{ $trend['description'] }}</p>
                @endif
                @if(is_array($trend) && isset($trend['confidence']))
                <span class="aith-badge aith-badge-purple" style="margin-top:0.375rem;">{{ $trend['confidence'] }}% confidence</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Predicted Trends --}}
    @if(isset($result['predicted_trends']) && count($result['predicted_trends']) > 0)
    <div class="aith-card" style="background: linear-gradient(135deg, #f5f3ff, #eef2ff); border-color: #e9d5ff;">
        <h3 class="aith-section-title" style="color:#6d28d9;"><i class="fa-light fa-crystal-ball"></i> {{ __('Predicted Trends') }}</h3>
        @foreach($result['predicted_trends'] as $trend)
        <div class="aith-result-item" style="background:#fff;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <strong style="font-size:0.8125rem; color:#1e293b;">{{ is_array($trend) ? ($trend['topic'] ?? '') : $trend }}</strong>
                @if(is_array($trend) && isset($trend['confidence']))
                <span class="aith-badge aith-badge-purple">{{ $trend['confidence'] }}%</span>
                @endif
            </div>
            @if(is_array($trend) && isset($trend['reasoning']))
            <p style="font-size:0.75rem; color:#64748b; margin:0.375rem 0 0;">{{ $trend['reasoning'] }}</p>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Content Ideas --}}
    @if(isset($result['content_ideas']) && count($result['content_ideas']) > 0)
    <div class="aith-card">
        <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Video Ideas') }}</h3>
        @foreach($result['content_ideas'] as $i => $idea)
        <div class="aith-result-item">
            <div class="aith-result-row">
                <div style="display:flex; align-items:center; gap:0.75rem; flex:1;">
                    <span style="font-size:0.875rem; font-weight:700; color:#7c3aed; min-width:1.5rem;">{{ $i + 1 }}.</span>
                    <div class="aith-result-text">{{ is_array($idea) ? ($idea['title'] ?? '') : $idea }}</div>
                </div>
                <button class="aith-copy-btn" onclick="aithCopyToClipboard('{{ addslashes(is_array($idea) ? ($idea['title'] ?? '') : $idea) }}', this)">
                    <i class="fa-light fa-copy"></i>
                </button>
            </div>
            @if(is_array($idea) && isset($idea['estimated_views']))
            <div style="margin-top:0.25rem; margin-left:2.25rem;">
                <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-eye"></i> ~{{ $idea['estimated_views'] }} views</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Hashtags --}}
    @if(isset($result['hashtags']) && count($result['hashtags']) > 0)
    <div class="aith-card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
            <h3 class="aith-section-title" style="margin:0"><i class="fa-light fa-hashtag"></i> {{ __('Trending Hashtags') }}</h3>
            <button class="aith-copy-btn" onclick="aithCopyToClipboard('{{ addslashes(implode(' ', $result['hashtags'])) }}', this)">
                <i class="fa-light fa-copy"></i> {{ __('Copy All') }}
            </button>
        </div>
        <div class="aith-tags-wrap">
            @foreach($result['hashtags'] as $tag)
            <span class="aith-tag">{{ $tag }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @endif

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
