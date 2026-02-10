<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0, step: 0, tipIndex: 0,
    tips: [
        'The first 3 seconds determine if someone watches your video',
        'Questions as hooks increase engagement by 60%',
        'Surprising facts trigger curiosity and boost retention',
        'Emotional hooks outperform logical ones 3:1'
    ],
    steps: [
        { label: 'Analyzing Topic', icon: 'fa-magnifying-glass' },
        { label: 'Studying Patterns', icon: 'fa-chart-mixed' },
        { label: 'Generating Hooks', icon: 'fa-bolt' },
        { label: 'Scoring Impact', icon: 'fa-star' }
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
        <h2 class="aith-card-title"><span class="aith-emoji">⚡</span> {{ __('Viral Hook Lab') }}</h2>

        <div class="aith-feature-box aith-feat-yellow">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Generate attention-grabbing hooks') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Multiple hook styles to choose from') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Effectiveness score for each hook') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Explanations of why each hook works') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Topic') }}</label>
            <textarea wire:model="topic" class="aith-textarea" rows="2" placeholder="{{ __('What is your video about?') }}"></textarea>
            @error('topic') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Hook Style') }}</label>
            <select wire:model="hookStyle" class="aith-select">
                @foreach($hookStyles as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Number of Hooks') }}: <strong x-text="$wire.count">{{ $count }}</strong></label>
            <div class="aith-range-wrap">
                <input type="range" wire:model="count" min="3" max="10" step="1" class="aith-range">
                <div style="display:flex; justify-content:space-between;">
                    <span class="aith-range-val">3</span>
                    <span class="aith-range-val">10</span>
                </div>
            </div>
        </div>

        <button wire:click="generate" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="generate">
                <i class="fa-light fa-bolt"></i> {{ __('Generate Hooks') }}
            </span>
            <span wire:loading wire:target="generate">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Generating...') }}
            </span>
        </button>

        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">⚡</span> {{ __('Crafting hooks...') }}</div>
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

    @if($result && isset($result['hooks']))
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">⚡</span> {{ __('Generated Hooks') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>

        @foreach($result['hooks'] as $index => $hook)
        @php
            $hookText = is_array($hook) ? ($hook['text'] ?? '') : $hook;
            $score = is_array($hook) ? ($hook['score'] ?? null) : null;
            $explanation = is_array($hook) ? ($hook['explanation'] ?? null) : null;
        @endphp
        <div class="aith-result-item">
            <div class="aith-result-row">
                <div style="display:flex; align-items:flex-start; gap:0.75rem; flex:1;">
                    <div style="text-align:center; min-width: 3rem;">
                        <div style="font-size:0.6875rem; color:#94a3b8; margin-bottom:0.25rem;">#{{ $index + 1 }}</div>
                        @if($score)
                        <div class="aith-score-gauge" style="width:44px; height:44px;">
                            <svg viewBox="0 0 120 120">
                                <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                                <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                                    stroke="{{ $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-dasharray="314"
                                    stroke-dashoffset="{{ 314 - (314 * $score / 100) }}"/>
                            </svg>
                            <div class="aith-score-val" style="font-size:0.75rem; color: {{ $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444') }}">{{ $score }}</div>
                        </div>
                        @endif
                    </div>
                    <div style="flex:1;">
                        <div class="aith-result-text" style="font-weight:500;">{{ $hookText }}</div>
                        @if($explanation)
                        <p style="font-size:0.75rem; color:#64748b; margin:0.375rem 0 0;">{{ $explanation }}</p>
                        @endif
                    </div>
                </div>
                <button class="aith-copy-btn" onclick="aithCopyToClipboard(this.closest('.aith-result-item').querySelector('.aith-result-text').innerText, this)">
                    <i class="fa-light fa-copy"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
