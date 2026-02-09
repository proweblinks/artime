<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0, step: 0, tipIndex: 0,
    tips: [
        'Great scripts start with a hook in the first 5 seconds',
        'Structure your script: Hook → Problem → Solution → CTA',
        'Write like you talk - conversational scripts perform 40% better',
        'Include pattern interrupts every 60 seconds to keep viewers'
    ],
    steps: [
        { label: 'Analyzing Topic', icon: 'fa-magnifying-glass' },
        { label: 'Structuring Script', icon: 'fa-list-tree' },
        { label: 'Writing Hook', icon: 'fa-bolt' },
        { label: 'Generating Sections', icon: 'fa-paragraph' },
        { label: 'Adding CTA', icon: 'fa-bullhorn' }
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
x-init="Livewire.hook('message.processed', (msg, comp) => { if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading(); });">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-ss').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">📝</span> {{ __('Script Studio') }}</h2>

        <div class="aith-feature-box aith-feat-blue">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Generate complete video scripts') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Attention-grabbing hooks') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Structured sections with transitions') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Effective call-to-actions') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Topic') }}</label>
            <textarea wire:model="topic" class="aith-textarea" rows="3" placeholder="{{ __('What is your video about?') }}"></textarea>
            @error('topic') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-grid-2">
            <div class="aith-form-group">
                <label class="aith-label">{{ __('Duration') }}</label>
                <select wire:model="duration" class="aith-select">
                    @foreach($durations as $key => $d)
                    <option value="{{ $key }}">{{ $d['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aith-form-group">
                <label class="aith-label">{{ __('Style') }}</label>
                <select wire:model="style" class="aith-select">
                    @foreach($styles as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button wire:click="generate" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="generate">
                <i class="fa-light fa-scroll"></i> {{ __('Generate Script') }}
            </span>
            <span wire:loading wire:target="generate">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Generating...') }}
            </span>
        </button>

        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">📝</span> {{ __('Writing script...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar"><div class="aith-progress-fill" :style="'width:' + progress + '%'"></div></div>
            <div class="aith-steps-grid">
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

    @if($result)
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">✨</span> {{ __('Your Script') }}</h2>
            <div style="display:flex; gap:0.5rem;">
                <button class="aith-copy-btn" onclick="aithCopyToClipboard(document.getElementById('aith-script-content').innerText, this)">
                    <i class="fa-light fa-copy"></i> {{ __('Copy Script') }}
                </button>
                <button class="aith-btn-secondary" wire:click="$set('result', null)">
                    <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
                </button>
            </div>
        </div>

        @if(isset($result['word_count']) || isset($result['estimated_duration']))
        <div style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
            @if(isset($result['word_count']))
            <span class="aith-badge aith-badge-ghost" style="padding: 0.375rem 0.75rem;">
                <i class="fa-light fa-text-size"></i> {{ number_format($result['word_count']) }} {{ __('words') }}
            </span>
            @endif
            @if(isset($result['estimated_duration']))
            <span class="aith-badge aith-badge-ghost" style="padding: 0.375rem 0.75rem;">
                <i class="fa-light fa-clock"></i> {{ $result['estimated_duration'] }}
            </span>
            @endif
        </div>
        @endif

        <div id="aith-script-content" class="aith-result-pre">{{ $result['script'] ?? '' }}</div>
    </div>
    @endif

    @if(count($history) > 0)
    <div id="aith-history-ss" class="aith-card" style="display:none; margin-top: 1rem;">
        <h3 class="aith-section-title"><i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent Scripts') }}</h3>
        @foreach($history as $item)
        <div class="aith-result-item" style="cursor:default;">
            <div class="aith-result-text">{{ $item['title'] ?? 'Untitled' }}</div>
            <div style="font-size:0.6875rem; color:#94a3b8; margin-top:0.25rem;">{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans() }}</div>
        </div>
        @endforeach
    </div>
    <style>#aith-history-ss.aith-open { display: block !important; }</style>
    @endif

</div>
</div>
